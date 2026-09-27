<?php

declare(strict_types=1);

namespace PowerNews\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use PowerNews\Tests\DatabaseTestCase;

/**
 * Regressionstests für B22 („Daten senden“ setzt Passwörter ohne Bestätigung zurück) und
 * B40 (Anfragen zählen als Login-Fehlversuch und sperren fremde Konten).
 */
class PasswordResetTest extends DatabaseTestCase
{
    /** @var array<string, mixed> */
    private array $serverBackup = [];

    protected function setUp(): void
    {
        global $pn_handler;

        parent::setUp();
        $this->serverBackup = $_SERVER;
        $_SERVER['REMOTE_ADDR'] = '203.0.113.' . random_int(1, 250);
        pn_password_resets_prepare($pn_handler);
        mysqli_query($pn_handler, 'DELETE FROM pn_password_resets');
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->serverBackup;
        parent::tearDown();
    }

    private function passwordHash(int $userId): string
    {
        return (string) (new \profile())->getdata($userId)['password'];
    }

    private function requestReset(string $search): string
    {
        $this->setGet(['page' => 'senddata']);
        $this->setPost(['pndata' => ['searchstring' => $search]]);

        return $this->captureOutput(fn () => @(new \pn_user())->senddata());
    }

    /**
     * Legt einen Reset-Eintrag an und liefert das Klartext-Token (wie im Mail-Link).
     */
    private function issueToken(int $userId): string
    {
        global $pn_handler;

        $token = bin2hex(random_bytes(32));
        pn_password_reset_store($pn_handler, $userId, $token, '203.0.113.1');

        return $token;
    }

    #[Test]
    public function request_keeps_the_old_password_and_stores_only_a_token_hash(): void
    {
        global $pn_handler;

        $userId = $this->insertTestUser('vergesslich', 'vergesslich@example.com', 'altes-passwort');
        $before = $this->passwordHash($userId);

        $output = $this->requestReset('vergesslich');

        $this->assertStringContainsString(\L_USR_DATAREQUESTSENT, $output);
        $this->assertSame($before, $this->passwordHash($userId), 'Das alte Passwort bleibt gültig.');

        $result = pn_query_by_id($pn_handler, 'SELECT token_hash, expires - created AS lifetime FROM pn_password_resets WHERE userid = ?', $userId);
        $row = mysqli_fetch_assoc($result);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $row['token_hash']);
        $this->assertSame(\PN_RESET_LIFETIME, (int) $row['lifetime']);
    }

    #[Test]
    public function reset_mail_contains_a_link_but_no_password(): void
    {
        global $pnconfig;

        $pnconfig['url'] = 'https://news.example.org/';
        $link = (new \pn_user())->resetlink(str_repeat('f', 64));
        $this->assertSame('https://news.example.org/user.php?page=resetpassword&token=' . str_repeat('f', 64), $link);

        $mail = (new \pn_template())->dataemail('vergesslich', 'vergesslich@example.com', $link);

        $this->assertIsString($mail);
        $this->assertStringContainsString($link, $mail);
        $this->assertStringNotContainsString('{PASSWORD}', $mail);
        $this->assertStringNotContainsString('{RESETLINK}', $mail);
    }

    #[Test]
    public function reset_link_ignores_the_request_host(): void
    {
        global $pnconfig;

        $pnconfig['url'] = 'https://news.example.org';
        $_SERVER['HTTP_HOST'] = 'evil.example.net';

        $this->assertStringStartsWith('https://news.example.org/', (new \pn_user())->resetlink(str_repeat('a', 64)));
    }

    #[Test]
    public function valid_token_lets_the_owner_choose_a_new_password(): void
    {
        global $pn_handler;

        $userId = $this->insertTestUser('neuespw', 'neuespw@example.com', 'altes-passwort');
        pn_session_create($pn_handler, $userId, 'frontend');
        $token = $this->issueToken($userId);

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->setGet(['page' => 'resetpassword', 'token' => $token]);
        $form = $this->captureOutput(fn () => (new \pn_user())->resetpassword());
        $this->assertStringContainsString('id="pn_newpassword"', $form);

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $this->setGet(['page' => 'resetpassword']);
        $this->setPost(['pndata' => ['token' => $token, 'password' => 'neues-passwort', 'password2' => 'neues-passwort']]);
        $output = $this->captureOutput(fn () => (new \pn_user())->resetpassword());

        $this->assertStringContainsString(\L_USR_PASSWORDRESET, $output);
        $this->assertTrue(password_verify('neues-passwort', $this->passwordHash($userId)));

        $sessions = pn_query_by_id($pn_handler, 'SELECT COUNT(*) FROM pn_sessions WHERE userid = ?', $userId);
        $this->assertSame(0, (int) mysqli_fetch_row($sessions)[0], 'Alle Sitzungen enden.');

        // Das Token ist verbraucht.
        $this->setPost(['pndata' => ['token' => $token, 'password' => 'drittes-passwort', 'password2' => 'drittes-passwort']]);
        $again = $this->captureOutput(fn () => (new \pn_user())->resetpassword());
        $this->assertStringContainsString(\L_USR_RESETINVALID, $again);
        $this->assertTrue(password_verify('neues-passwort', $this->passwordHash($userId)));
    }

    #[Test]
    public function expired_or_unknown_tokens_are_rejected(): void
    {
        global $pn_handler;

        $userId = $this->insertTestUser('abgelaufen', 'abgelaufen@example.com', 'altes-passwort');
        $token = $this->issueToken($userId);
        mysqli_query($pn_handler, 'UPDATE pn_password_resets SET expires = ' . (time() - 1) . ' WHERE userid = ' . $userId);

        $_SERVER['REQUEST_METHOD'] = 'GET';
        foreach ([$token, str_repeat('0', 64), 'kein-token'] as $candidate) {
            $this->setGet(['page' => 'resetpassword', 'token' => $candidate]);
            $output = $this->captureOutput(fn () => (new \pn_user())->resetpassword());
            $this->assertStringContainsString(\L_USR_RESETINVALID, $output);
        }
    }

    #[Test]
    public function new_password_must_be_confirmed_and_long_enough(): void
    {
        $userId = $this->insertTestUser('kurzpw', 'kurzpw@example.com', 'altes-passwort');
        $token = $this->issueToken($userId);
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $this->setPost(['pndata' => ['token' => $token, 'password' => 'kurz', 'password2' => 'kurz']]);
        $this->assertStringContainsString(\L_USR_PASSWORDTOOSHORT, $this->captureOutput(fn () => (new \pn_user())->resetpassword()));

        $this->setPost(['pndata' => ['token' => $token, 'password' => 'lang-genug-1', 'password2' => 'lang-genug-2']]);
        $this->assertStringContainsString(\L_USR_PASSNOTEQUAL, $this->captureOutput(fn () => (new \pn_user())->resetpassword()));

        $this->assertTrue(password_verify('altes-passwort', $this->passwordHash($userId)));
    }

    #[Test]
    public function requests_do_not_count_as_login_failures(): void
    {
        global $pn_handler;

        $this->insertTestUser('fremdkonto', 'fremdkonto@example.com', 'altes-passwort');

        for ($i = 0; $i < 3; ++$i) {
            $this->requestReset('fremdkonto');
        }

        $result = mysqli_query($pn_handler, "SELECT COUNT(*) FROM pn_login_attempts WHERE nickname = 'fremdkonto'");
        $this->assertSame(0, (int) mysqli_fetch_row($result)[0]);
    }

    #[Test]
    public function requests_are_limited_per_ip_and_per_account(): void
    {
        global $pn_handler;

        $userId = $this->insertTestUser('flut', 'flut@example.com', 'altes-passwort');

        for ($i = 0; $i < \PN_RESET_MAX_PER_IP; ++$i) {
            $this->assertStringContainsString(\L_USR_DATAREQUESTSENT, $this->requestReset('flut'));
        }

        // Nur ein gültiger Link je Konto und 5 Minuten, weitere Anfragen verschicken nichts.
        $result = pn_query_by_id($pn_handler, 'SELECT COUNT(*) FROM pn_password_resets WHERE userid = ?', $userId);
        $this->assertSame(1, (int) mysqli_fetch_row($result)[0]);

        $this->assertStringContainsString(\L_USR_TOOMANYREQUESTS, $this->requestReset('flut'));
    }

    #[Test]
    public function unknown_accounts_get_the_same_answer(): void
    {
        $this->assertStringContainsString(\L_USR_DATAREQUESTSENT, $this->requestReset('gibt-es-nicht@example.com'));
    }
}
