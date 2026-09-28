<?php

declare(strict_types=1);

namespace PowerNews\Tests\Integration;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PowerNews\Tests\DatabaseTestCase;
use PowerNews\Tests\Helpers\MockSmtpServer;

/**
 * Keine Passwörter im Klartext per Mail: Registrierung mit selbst gewähltem Passwort,
 * Einladung per Einmal-Link für vom Admin angelegte Konten, eigene Betreffzeilen je Mailart.
 */
class AccountInvitationTest extends DatabaseTestCase
{
    use MockSmtpServer;

    private const string PASSWORD = 'Sonnenblume-26';

    /** @var array<string, mixed> */
    private array $serverBackup = [];

    /** @var array<string, mixed>|null */
    private ?array $mailBackup = null;

    protected function setUp(): void
    {
        global $pn_config, $pnconfig;

        parent::setUp();
        $this->serverBackup = $_SERVER;
        $this->mailBackup = $pn_config['mail'] ?? null;
        $_SERVER['REMOTE_ADDR'] = '198.51.100.' . random_int(1, 250);
        $pnconfig['url'] = 'https://www.news.example.org/pn';
        $pnconfig['email'] = 'news@example.org';
    }

    protected function tearDown(): void
    {
        global $pn_config;

        $_SERVER = $this->serverBackup;
        $pn_config['mail'] = $this->mailBackup;
        parent::tearDown();
    }

    /**
     * Führt $action aus, während Mails an den Mock-SMTP-Server gehen, und liefert den
     * Nachrichtentext der (einen) verschickten Mail samt Kopfzeilen.
     */
    private function captureMail(callable $action, string $scenario = '8bitmime'): string
    {
        global $pn_config;

        [, $transcript] = $this->converse($scenario, function (int $port) use (&$pn_config, $action): string {
            $pn_config['mail'] = ['transport' => 'smtp', 'host' => '127.0.0.1', 'port' => $port, 'encryption' => 'none', 'user' => '', 'password' => ''];

            return $this->captureOutput($action);
        });

        return $this->data($transcript);
    }

    private static function subject(string $mail): string
    {
        preg_match('/^Subject: (.*)$/m', $mail, $match);

        return mb_decode_mimeheader($match[1] ?? '');
    }

    /**
     * @return array<string, mixed>
     */
    private function account(string $nickname): array
    {
        global $pn_handler, $pn_config;

        $result = pn_query_by_string($pn_handler, 'SELECT * FROM ' . $pn_config['usertable'] . ' WHERE nickname = ?', $nickname);
        $row = $result ? mysqli_fetch_assoc($result) : null;
        $this->assertIsArray($row, 'Konto ' . $nickname . ' fehlt');

        return $row;
    }

    /**
     * @param array<string, string> $data
     */
    private function register(array $data): string
    {
        $this->setGet(['pndata' => ['send' => 'YES']]);
        $this->setPost(['pndata' => $data]);

        return $this->captureOutput(fn () => (new \pn_user())->register());
    }

    #[Test]
    public function registration_stores_the_chosen_password_and_sends_a_welcome_without_it(): void
    {
        $output = '';
        $mail = $this->captureMail(function () use (&$output): void {
            $output = $this->register(['nickname' => 'selbstwahl', 'email' => 'selbstwahl@example.org', 'password' => self::PASSWORD, 'password2' => self::PASSWORD]);
            echo $output;
        });

        $this->assertStringContainsString(\L_USR_REGISTERED, $output);
        $account = $this->account('selbstwahl');
        $this->assertTrue(password_verify(self::PASSWORD, (string) $account['password']));
        $this->assertSame('NO', $account['showemail'], 'Privacy by Default');

        $this->assertSame(sprintf(\L_EMAIL_SUBJECT_REGISTER, 'news.example.org'), self::subject($mail));
        $this->assertStringContainsString('https://www.news.example.org/pn/user.php?page=login', $mail);
        $this->assertStringNotContainsString(self::PASSWORD, $mail);
        $this->assertStringNotContainsString('Passwort:', $mail);
        $this->assertDoesNotMatchRegularExpression('/\b(Du|Dich|Dein|Sie|Ihr|Ihre)\b/', $mail, 'Default-Template ohne Anrede');
    }

    #[Test]
    public function registration_succeeds_even_if_the_welcome_mail_fails(): void
    {
        $output = '';
        $this->captureMail(function () use (&$output): void {
            $output = $this->register(['nickname' => 'ohnepost', 'email' => 'ohnepost@example.org', 'password' => self::PASSWORD, 'password2' => self::PASSWORD]);
        }, 'rcpt-rejected');

        $this->assertStringContainsString(\L_USR_REGISTERED, $output);
        $this->assertTrue(password_verify(self::PASSWORD, (string) $this->account('ohnepost')['password']));
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function badPasswords(): array
    {
        return [
            'verschieden' => [self::PASSWORD, self::PASSWORD . 'x', \L_USR_PASSNOTEQUAL],
            'zu kurz' => ['kurz-7x', 'kurz-7x', \L_USR_PASSWORDTOOSHORT],
            'leer' => ['', '', \L_USR_PASSWORDTOOSHORT],
            'zu lang' => [str_repeat('ä', 37), str_repeat('ä', 37), \L_USR_PASSWORDTOOLONG],
        ];
    }

    #[Test]
    #[DataProvider('badPasswords')]
    public function registration_rejects_invalid_passwords(string $password, string $repeat, string $message): void
    {
        global $pn_handler;

        $output = $this->register(['nickname' => 'abgelehnt', 'email' => 'abgelehnt@example.org', 'password' => $password, 'password2' => $repeat]);

        $this->assertStringContainsString($message, $output);
        $this->assertSame(0, mysqli_num_rows(pn_query_by_string($pn_handler, 'SELECT id FROM pn_users WHERE nickname = ?', 'abgelehnt')));
    }

    #[Test]
    public function password_rules_match_the_installer(): void
    {
        $this->assertSame('', pn_password_problem('acht-zei', 'acht-zei'));
        $this->assertSame('short', pn_password_problem('sieben7', 'sieben7'));
        $this->assertSame('mismatch', pn_password_problem('acht-zei', 'acht-zeI'));
        $this->assertSame('', pn_password_problem(str_repeat('ä', 36), str_repeat('ä', 36)), '72 Byte sind erlaubt');
        $this->assertSame('long', pn_password_problem(str_repeat('a', 73), str_repeat('a', 73)));
    }

    #[Test]
    public function register_form_offers_both_password_fields(): void
    {
        $form = $this->captureOutput(fn () => (new \pn_user())->register());

        foreach (['pn_nickname', 'pn_email', 'pn_password', 'pn_password2', 'pn_showemail'] as $id) {
            $this->assertSame(1, substr_count($form, 'id="' . $id . '"'), $id);
        }
        $this->assertStringContainsString('name="pndata[password]"', $form);
        $this->assertStringContainsString('name="pndata[password2]"', $form);
        $this->assertStringContainsString('autocomplete="new-password"', $form);
    }

    #[Test]
    public function old_register_forms_get_the_password_fields_added(): void
    {
        $legacy = '<form action="user.php?pndata[send]=YES" method="post"><input name="pndata[nickname]"><button type="submit">Los</button></form>';

        $form = \pn_template::withpasswordfields($legacy);

        $this->assertStringContainsString('id="pn_password"', $form);
        $this->assertStringContainsString('id="pn_password2"', $form);
        $this->assertLessThan(strpos($form, '<button type="submit">'), strpos($form, 'id="pn_password2"'), 'Felder stehen vor der Schaltfläche');
        $this->assertSame($form, \pn_template::withpasswordfields($form), 'Kein zweites Mal');
        $this->assertStringContainsString('name="pndata[password]"', \pn_template::withpasswordfields('<form><p>x</p></form>'));
    }

    #[Test]
    public function unset_or_empty_passwords_never_log_in(): void
    {
        $this->assertFalse(pn_verify_password('', PN_PASSWORD_UNSET));
        $this->assertFalse(pn_verify_password('!unset', PN_PASSWORD_UNSET));
        $this->assertFalse(pn_verify_password('', ''), 'Leerer Wert wäre sonst ein gültiges Base64-Altpasswort');
        $this->assertFalse(pnadmin_verify_password('', ''));
        $this->assertFalse(pnadmin_verify_password('', PN_PASSWORD_UNSET));
        $this->assertFalse(pn_password_is_set(PN_PASSWORD_UNSET));
        $this->assertTrue(pn_password_is_set(pn_hash_password('x')));
    }

    #[Test]
    public function admin_invitation_creates_an_account_without_password_and_a_48_hour_link(): void
    {
        global $pn_handler;

        $user = new \user();
        $mail = $this->captureMail(function () use ($user): void {
            $this->assertSame('', $user->adduser('Moritz', 'moritz@example.org', 'NO', 'YES'));
        });

        $account = $this->account('Moritz');
        $this->assertSame(PN_PASSWORD_UNSET, $account['password']);
        $this->assertSame('NO', $account['showemail']);
        $this->assertTrue($user->linksent);
        $this->assertMatchesRegularExpression('#^https://www\.news\.example\.org/pn/user\.php\?page=resetpassword&token=[a-f0-9]{64}$#', $user->passwordlink);

        $row = mysqli_fetch_assoc(pn_query_by_id($pn_handler, 'SELECT expires - created AS lifetime, ip FROM pn_password_resets WHERE userid = ?', (int) $account['id']));
        $this->assertSame(PN_INVITE_LIFETIME, (int) $row['lifetime']);
        $this->assertSame('', $row['ip'], 'Zählt nicht zur Bremse für „Passwort vergessen“ des Admins');

        $this->assertSame(sprintf(\L_EMAIL_SUBJECT_INVITE, 'news.example.org'), self::subject($mail));
        $this->assertStringContainsString($user->passwordlink, $mail);
        $this->assertStringContainsString('48 Stunden', $mail);
        $this->assertStringNotContainsString('Passwort:', $mail);
        $this->assertDoesNotMatchRegularExpression('/\b(Du|Dich|Dein|Sie|Ihr|Ihre)\b/', $mail);

        // Ohne Passwort keine Anmeldung, weder im Frontend noch im Admin.
        $this->assertStringContainsString(\L_USR_LOGINFAILED, (new \login())->checklogin('Moritz', ''));
    }

    #[Test]
    public function the_invitation_page_welcomes_and_sets_the_password(): void
    {
        global $pn_handler;

        $user = new \user();
        $this->assertSame('', $user->adduser('Einlader', 'einlader@example.org', 'NO', 'NO'));
        $this->assertFalse($user->linksent);
        parse_str((string) parse_url($user->passwordlink, PHP_URL_QUERY), $query);
        $token = (string) $query['token'];

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->setGet(['page' => 'resetpassword', 'token' => $token]);
        $form = $this->captureOutput(fn () => (new \pn_user())->resetpassword());
        $this->assertStringContainsString('id="pn_passwordform" data-purpose="invite"', $form);
        $this->assertStringContainsString(\L_USR_INVITETITLE, $form);
        $this->assertStringContainsString(sprintf(\L_USR_INVITEINTRO, 'Einlader'), $form);
        $this->assertStringContainsString('id="pn_newpassword"', $form);
        $this->assertStringContainsString('id="pn_newpassword2"', $form);

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $this->setPost(['pndata' => ['token' => $token, 'password' => self::PASSWORD, 'password2' => self::PASSWORD]]);
        $done = $this->captureOutput(fn () => (new \pn_user())->resetpassword());

        $this->assertStringContainsString(\L_USR_INVITEDONE, $done);
        $this->assertTrue(pn_verify_password(self::PASSWORD, (string) $this->account('Einlader')['password']));
        $this->assertSame(0, mysqli_num_rows(pn_query_by_string($pn_handler, 'SELECT r.id FROM pn_password_resets r JOIN pn_users u ON u.id = r.userid WHERE u.nickname = ?', 'Einlader')), 'Einmal-Link verbraucht');
    }

    #[Test]
    public function the_reset_page_keeps_its_own_wording(): void
    {
        global $pn_handler;

        $userId = $this->insertTestUser('zuruecksetzer', 'zuruecksetzer@example.org', 'altes-passwort');
        $token = pn_password_token_issue($pn_handler, $userId, PN_RESET_LIFETIME);

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->setGet(['page' => 'resetpassword', 'token' => $token]);
        $form = $this->captureOutput(fn () => (new \pn_user())->resetpassword());

        $this->assertStringContainsString('data-purpose="reset"', $form);
        $this->assertStringContainsString(\L_USR_RESETTITLE, $form);
        $this->assertStringNotContainsString(\L_USR_INVITETITLE, $form);
    }

    #[Test]
    public function add_user_page_shows_the_link_once_without_mail(): void
    {
        $output = $this->renderAdminPage('users_add.inc.php', ['add' => 'YES'], ['nickname' => 'Linkempfang', 'email' => 'linkempfang@example.org']);

        $this->assertStringContainsString('id="pn_useradded"', $output);
        $this->assertMatchesRegularExpression('#<input type="text" class="form-control font-monospace" id="pn_invitelink" value="https://www\.news\.example\.org/pn/user\.php\?page=resetpassword&amp;token=[a-f0-9]{64}" readonly#', $output);
        $this->assertStringNotContainsString('id="pn_invitemailfailed"', $output);
        $this->assertStringNotContainsString('id="pn_invitesent"', $output);
    }

    #[Test]
    public function add_user_page_reports_the_sent_invitation_without_showing_the_link(): void
    {
        $output = '';
        $this->captureMail(function () use (&$output): void {
            $output = $this->renderAdminPage('users_add.inc.php', ['add' => 'YES'], ['nickname' => 'Postweg', 'email' => 'postweg@example.org', 'sendemail' => 'YES']);
        });

        $this->assertStringContainsString('id="pn_invitesent"', $output);
        $this->assertStringContainsString('postweg@example.org', $output);
        $this->assertStringNotContainsString('id="pn_invitelink"', $output);
    }

    #[Test]
    public function add_user_page_shows_the_link_when_the_invitation_mail_fails(): void
    {
        $output = '';
        $this->captureMail(function () use (&$output): void {
            $output = $this->renderAdminPage('users_add.inc.php', ['add' => 'YES'], ['nickname' => 'Postfehler', 'email' => 'postfehler@example.org', 'sendemail' => 'YES']);
        }, 'rcpt-rejected');

        $this->assertStringContainsString('id="pn_invitemailfailed"', $output);
        $this->assertStringContainsString('id="pn_invitelink"', $output);
    }

    #[Test]
    public function add_user_form_labels_the_invitation(): void
    {
        $form = $this->renderAdminPage('users_add.inc.php', []);

        $this->assertStringContainsString('<label class="form-check-label fw-bold" for="pn_sendemail">' . \L_USR_SENDINVITE . '</label>', $form);
        $this->assertStringContainsString('id="pn_sendemail" checked', $form);
    }

    #[Test]
    public function new_password_from_the_admin_is_a_reset_link_and_keeps_the_old_password(): void
    {
        global $pn_handler;

        $userId = $this->insertTestUser('altkonto', 'altkonto@example.org', 'altes-passwort');
        $user = new \user();
        $mail = $this->captureMail(function () use ($user, $userId): void {
            $this->assertSame('', $user->edituser('altkonto', 'altkonto@example.org', 'NO', 'YES', 'Activated', 'NO', $userId, ''));
        });

        $this->assertTrue($user->linksent);
        $this->assertTrue(pn_verify_password('altes-passwort', (string) $this->account('altkonto')['password']), 'Das bisherige Passwort gilt weiter.');
        $row = mysqli_fetch_assoc(pn_query_by_id($pn_handler, 'SELECT expires - created AS lifetime FROM pn_password_resets WHERE userid = ?', $userId));
        $this->assertSame(PN_RESET_LIFETIME, (int) $row['lifetime']);
        $this->assertSame(sprintf(\L_EMAIL_SUBJECT_RESET, 'news.example.org'), self::subject($mail));
        $this->assertStringContainsString('page=resetpassword&token=', $mail);
        $this->assertStringNotContainsString('Passwort:', $mail);
    }

    #[Test]
    public function new_password_for_an_invited_account_repeats_the_invitation(): void
    {
        global $pn_handler;

        $user = new \user();
        $this->assertSame('', $user->adduser('Nachzuegler', 'nachzuegler@example.org', 'NO', 'NO'));
        $userId = (int) $this->account('Nachzuegler')['id'];

        $mail = $this->captureMail(function () use ($user, $userId): void {
            $this->assertSame('', $user->edituser('Nachzuegler', 'nachzuegler@example.org', 'NO', 'YES', 'Activated', 'NO', $userId, ''));
        });

        $this->assertSame(sprintf(\L_EMAIL_SUBJECT_INVITE, 'news.example.org'), self::subject($mail));
        $result = pn_query_by_id($pn_handler, 'SELECT MAX(expires - created) FROM pn_password_resets WHERE userid = ?', $userId);
        $this->assertSame(PN_INVITE_LIFETIME, (int) mysqli_fetch_row($result)[0]);
    }

    #[Test]
    public function data_change_mail_has_its_own_subject_and_no_password(): void
    {
        $userId = $this->insertTestUser('datenmail', 'datenmail@example.org');
        $mail = $this->captureMail(function () use ($userId): void {
            (new \user())->edituser('datenmail', 'datenmail@example.org', 'NO', 'NO', 'Activated', 'YES', $userId, '');
        });

        $this->assertSame(sprintf(\L_EMAIL_SUBJECT_EDIT, 'news.example.org'), self::subject($mail));
        $this->assertStringContainsString('Nickname: datenmail', $mail);
        $this->assertStringNotContainsString('Passwort:', $mail);
    }

    #[Test]
    public function forgot_password_mail_has_its_own_subject(): void
    {
        $this->insertTestUser('vergessen', 'vergessen@example.org', 'altes-passwort');
        $mail = $this->captureMail(function (): void {
            $this->setGet(['page' => 'senddata']);
            $this->setPost(['pndata' => ['searchstring' => 'vergessen']]);
            (new \pn_user())->senddata();
        });

        $this->assertSame(sprintf(\L_EMAIL_SUBJECT_RESET, 'news.example.org'), self::subject($mail));
        $this->assertNotSame(self::subject($mail), sprintf(\L_EMAIL_SUBJECT_INVITE, 'news.example.org'));
    }

    #[Test]
    public function site_name_comes_from_the_configured_url(): void
    {
        global $pnconfig;

        $this->assertSame('news.example.org', pn_site_name());
        $pnconfig['url'] = 'https://example.com:8443';
        $this->assertSame('example.com', pn_site_name());
        $pnconfig['url'] = '';
        $this->assertSame('PowerNews', pn_site_name());
    }
}
