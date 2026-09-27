<?php

declare(strict_types=1);

namespace PowerNews\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use PowerNews\Tests\DatabaseTestCase;

/**
 * Regressionstests für B09 (Admin-Login ohne Fehlversuchsbremse, Benutzer-Enumeration),
 * B23 (IP aus X-Forwarded-For) und B40 (Kontosperre über den Nickname).
 */
class LoginThrottleTest extends DatabaseTestCase
{
    /** @var array<string, mixed> */
    private array $serverBackup = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->serverBackup = $_SERVER;
        putenv('PN_TRUSTED_PROXIES');
    }

    protected function tearDown(): void
    {
        global $pn_config;

        $_SERVER = $this->serverBackup;
        unset($pn_config['trustedproxies']);
        putenv('PN_TRUSTED_PROXIES');
        parent::tearDown();
    }

    #[Test]
    public function admin_login_messages_do_not_reveal_whether_an_account_exists(): void
    {
        $noPermId = $this->insertTestUser('ohne_rechte', 'ohne_rechte@example.com', 'richtiges-passwort');
        $adminId = $this->insertTestUser('mit_rechten', 'mit_rechten@example.com', 'richtiges-passwort');
        $this->insertTestPermissions($adminId);
        $deactivatedId = $this->insertTestUser('gesperrt', 'gesperrt@example.com', 'richtiges-passwort', 'Deactivated');
        $this->insertTestPermissions($deactivatedId);
        $login = new \login();

        $messages = [
            $login->checklogin('gibt_es_nicht', 'egal'),
            $login->checklogin('mit_rechten', 'falsch'),
            $login->checklogin('ohne_rechte', 'richtiges-passwort'),
            $login->checklogin('gesperrt', 'richtiges-passwort'),
        ];

        $this->assertSame([\L_USR_LOGINFAILED], array_values(array_unique($messages)));
        $this->assertGreaterThan(0, $noPermId);
    }

    #[Test]
    public function admin_login_is_throttled_after_ten_failures_from_one_ip(): void
    {
        global $pn_handler;

        $_SERVER['REMOTE_ADDR'] = '198.51.100.23';
        $adminId = $this->insertTestUser('bremse_admin', 'bremse_admin@example.com', 'richtiges-passwort');
        $this->insertTestPermissions($adminId);
        $login = new \login();

        for ($i = 0; $i < \PN_LOGIN_MAX_FAILURES; ++$i) {
            $this->assertSame(\L_USR_LOGINFAILED, $login->checklogin('bremse_admin', 'falsch-' . $i));
        }

        // Auch das richtige Passwort wird jetzt abgewiesen, ohne Prüfung.
        $this->assertSame(\L_USR_TOOMANYATTEMPTS, @$login->checklogin('bremse_admin', 'richtiges-passwort'));

        $result = mysqli_query($pn_handler, "SELECT COUNT(*) FROM pn_login_attempts WHERE ip = '198.51.100.23' AND success = 'NO'");
        $this->assertSame(\PN_LOGIN_MAX_FAILURES, (int) mysqli_fetch_row($result)[0], 'Jeder Fehlversuch wird protokolliert.');

        // Von einer anderen Adresse aus kann sich der Inhaber weiterhin anmelden.
        $_SERVER['REMOTE_ADDR'] = '203.0.113.7';
        $this->assertSame('loggedin', @$login->checklogin('bremse_admin', 'richtiges-passwort'));
    }

    #[Test]
    public function failures_against_a_nickname_do_not_lock_out_its_owner(): void
    {
        global $pn_handler;

        // Zehn Fehlversuche von einer fremden Adresse gegen den Nickname „opfer“ ...
        $now = time();
        for ($i = 0; $i < 12; ++$i) {
            $ip = '192.0.2.' . $i;
            $stmt = mysqli_prepare($pn_handler, "INSERT INTO pn_login_attempts (ip, nickname, success, attempted_at) VALUES (?, 'opfer', 'NO', ?)");
            mysqli_stmt_bind_param($stmt, 'si', $ip, $now);
            mysqli_stmt_execute($stmt);
        }

        // ... sperren den Inhaber an seiner eigenen Adresse nicht.
        $this->assertFalse(pn_login_throttled($pn_handler, '198.51.100.99'));
    }

    #[Test]
    public function forwarded_header_is_ignored_without_trusted_proxy(): void
    {
        $_SERVER['REMOTE_ADDR'] = '198.51.100.5';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '10.9.8.7';

        $this->assertSame('198.51.100.5', pn_client_ip());
    }

    #[Test]
    public function forwarded_header_is_used_behind_configured_proxy(): void
    {
        global $pn_config;

        $pn_config['trustedproxies'] = ['10.0.0.0/8'];
        $_SERVER['REMOTE_ADDR'] = '10.1.2.3';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '1.2.3.4, 198.51.100.77, 10.4.4.4';

        // Von rechts die erste Adresse, die kein vertrauenswürdiger Proxy ist.
        $this->assertSame('198.51.100.77', pn_client_ip());

        // Nicht vertrauenswürdige Absender dürfen den Kopf nicht setzen.
        $_SERVER['REMOTE_ADDR'] = '198.51.100.200';
        $this->assertSame('198.51.100.200', pn_client_ip());
    }

    #[Test]
    public function trusted_proxies_can_be_set_by_environment(): void
    {
        putenv('PN_TRUSTED_PROXIES=2001:db8::/32, 127.0.0.1');
        $_SERVER['REMOTE_ADDR'] = '2001:db8::1';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '203.0.113.50';

        $this->assertSame('203.0.113.50', pn_client_ip());
        $this->assertTrue(pn_ip_in_list('127.0.0.1', pn_trusted_proxies()));
        $this->assertFalse(pn_ip_in_list('127.0.0.2', pn_trusted_proxies()));
        $this->assertFalse(pn_ip_in_list('kein-ip', ['0.0.0.0/0']));
    }

    #[Test]
    public function comment_spam_protection_uses_remote_addr(): void
    {
        global $pn_config, $pn_handler, $pnconfig;

        $pnconfig['commentwriting'] = 'Guests/Registered';
        $pnconfig['spamprotection'] = 600;
        $userId = $this->insertTestUser('spam_author', 'spam_author@example.com');
        $newsId = $this->insertTestNews($userId, 0, 'Spamschutz', 'Text');
        $_SERVER['REMOTE_ADDR'] = '198.51.100.44';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '1.1.1.1';

        $this->setPost(['text' => 'Erster Kommentar']);
        $this->captureOutput(fn () => (new \pn_news())->postcomment($newsId, 'Erster Kommentar'));

        // Ein gefälschter Kopf umgeht den Spamschutz nicht mehr.
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '2.2.2.2';
        $this->setPost(['text' => 'Zweiter Kommentar']);
        $output = $this->captureOutput(fn () => (new \pn_news())->postcomment($newsId, 'Zweiter Kommentar'));

        $this->assertStringContainsString(\L_NEWS_TIMEBETWEEN2COMMENTS, $output);
        $result = pn_query_by_id($pn_handler, 'SELECT ip FROM ' . $pn_config['commenttable'] . ' WHERE newsid = ?', $newsId);
        $this->assertSame([['198.51.100.44']], mysqli_fetch_all($result));
    }

    #[Test]
    public function dummy_hash_is_a_real_bcrypt_hash(): void
    {
        $info = password_get_info(\PN_DUMMY_PASSWORD_HASH);

        $this->assertSame('bcrypt', $info['algoName']);
        $this->assertFalse(password_verify('', \PN_DUMMY_PASSWORD_HASH));
    }
}
