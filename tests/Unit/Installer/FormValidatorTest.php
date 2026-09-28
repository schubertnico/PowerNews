<?php

declare(strict_types=1);

namespace PowerNews\Tests\Unit\Installer;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PowerNews\Installer\FormValidator;
use PowerNews\LocalConfig;

require_once __DIR__ . '/../../../pninc/installer/autoload.php';

/**
 * Eingabeprüfung der Installer-Schritte 2 bis 4.
 */
final class FormValidatorTest extends TestCase
{
    // ── Schritt 2: Datenbank ──

    #[Test]
    public function validDatabaseInputIsAccepted(): void
    {
        $result = FormValidator::database([
            'db_host' => ' sql.example.org ',
            'db_port' => '3307',
            'db_name' => 'news_db',
            'db_user' => 'news_user',
            'db_password' => ' Lichtblick-DB26 ',
        ]);

        $this->assertSame([], $result['errors']);
        $this->assertSame([
            'host' => 'sql.example.org',
            'port' => 3307,
            'user' => 'news_user',
            'password' => ' Lichtblick-DB26 ',
            'database' => 'news_db',
        ], $result['values'], 'Passwort wird nicht getrimmt');
    }

    #[Test]
    public function emptyPortMeansDefaultPortAndEmptyPasswordIsAllowed(): void
    {
        $result = FormValidator::database(['db_host' => 'localhost', 'db_port' => '', 'db_name' => 'pn', 'db_user' => 'root']);

        $this->assertSame([], $result['errors']);
        $this->assertSame(3306, $result['values']['port']);
        $this->assertSame('', $result['values']['password']);
    }

    #[Test]
    public function missingDatabaseFieldsReportEveryField(): void
    {
        $result = FormValidator::database([]);

        $this->assertSame(['db_host', 'db_name', 'db_user'], array_keys($result['errors']));
    }

    /**
     * @return iterable<string, array{array<string, string>, string}>
     */
    public static function invalidDatabaseInput(): iterable
    {
        $valid = ['db_host' => 'localhost', 'db_port' => '3306', 'db_name' => 'pn', 'db_user' => 'root', 'db_password' => ''];

        yield 'Host mit Port' => [['db_host' => 'localhost:3306'] + $valid, 'db_host'];
        yield 'Host mit Persistent-Präfix' => [['db_host' => 'p:localhost'] + $valid, 'db_host'];
        yield 'Host mit Semikolon' => [['db_host' => 'db;port=1'] + $valid, 'db_host'];
        yield 'Host mit Leerzeichen' => [['db_host' => 'sql example'] + $valid, 'db_host'];
        yield 'Port zu groß' => [['db_port' => '70000'] + $valid, 'db_port'];
        yield 'Port keine Zahl' => [['db_port' => 'abc'] + $valid, 'db_port'];
        yield 'Port null' => [['db_port' => '0'] + $valid, 'db_port'];
        yield 'Datenbankname mit Punkt' => [['db_name' => 'news.db'] + $valid, 'db_name'];
        yield 'Datenbankname zu lang' => [['db_name' => str_repeat('a', 65)] + $valid, 'db_name'];
        yield 'Benutzer mit Steuerzeichen' => [['db_user' => "ro\tot"] + $valid, 'db_user'];
        yield 'Benutzer zu lang' => [['db_user' => str_repeat('u', 81)] + $valid, 'db_user'];
        yield 'Passwort mit NUL' => [['db_password' => "a\0b"] + $valid, 'db_password'];
        yield 'Passwort zu lang' => [['db_password' => str_repeat('p', 256)] + $valid, 'db_password'];
    }

    /**
     * @param array<string, string> $input
     */
    #[Test]
    #[DataProvider('invalidDatabaseInput')]
    public function invalidDatabaseInputIsReportedAtTheField(array $input, string $field): void
    {
        $result = FormValidator::database($input);

        $this->assertSame([$field], array_keys($result['errors']));
    }

    #[Test]
    public function ipAddressesAreValidHosts(): void
    {
        $this->assertTrue(FormValidator::isHostname('127.0.0.1'));
        $this->assertTrue(FormValidator::isHostname('::1'));
        $this->assertTrue(FormValidator::isHostname('2001:db8::10'));
        $this->assertTrue(FormValidator::isHostname('db'));
        $this->assertFalse(FormValidator::isHostname('-db'));
        $this->assertFalse(FormValidator::isHostname(str_repeat('a', 256)));
    }

    #[Test]
    public function nonStringInputIsIgnored(): void
    {
        $result = FormValidator::database(['db_host' => ['x'], 'db_name' => 5, 'db_user' => null, 'db_password' => ['p']]);

        $this->assertArrayHasKey('db_host', $result['errors']);
        $this->assertSame('', $result['values']['password']);
    }

    // ── Schritt 3: Website ──

    #[Test]
    public function validWebsiteInputIsNormalised(): void
    {
        $result = FormValidator::website([
            'site_url' => ' https://www.example.org/news/ ',
            'site_email' => 'noreply@example.org',
            'site_language' => 'german-sie',
        ]);

        $this->assertSame([], $result['errors']);
        $this->assertSame(
            ['url' => 'https://www.example.org/news', 'email' => 'noreply@example.org', 'language' => 'german-sie', 'mail' => LocalConfig::DEFAULT_MAIL],
            $result['values'],
            'Ohne Angaben zum Mailversand bleibt es bei PHP-mail des Servers',
        );
    }

    // ── Schritt 3: E-Mail-Versand ──

    private const array SITE = ['site_url' => 'http://localhost:8229', 'site_email' => 'news@example.org', 'site_language' => 'german-du'];

    #[Test]
    public function phpMailIgnoresTheSmtpFields(): void
    {
        $result = FormValidator::website(self::SITE + ['mail_transport' => 'mail', 'smtp_host' => 'ungültig host', 'smtp_port' => 'abc', 'smtp_user' => 'x', 'smtp_password' => '']);

        $this->assertSame([], $result['errors']);
        $this->assertSame(LocalConfig::DEFAULT_MAIL, $result['values']['mail']);
    }

    #[Test]
    public function smtpSettingsAreTakenOverWithUntrimmedPassword(): void
    {
        $result = FormValidator::website(self::SITE + [
            'mail_transport' => 'smtp',
            'smtp_host' => ' smtp.example.org ',
            'smtp_port' => '',
            'smtp_encryption' => 'ssl',
            'smtp_user' => ' news@example.org ',
            'smtp_password' => " geheim'\"$?> ",
        ]);

        $this->assertSame([], $result['errors']);
        $this->assertSame(
            ['transport' => 'smtp', 'host' => 'smtp.example.org', 'port' => 465, 'encryption' => 'ssl', 'user' => 'news@example.org', 'password' => " geheim'\"$?> "],
            $result['values']['mail'],
            'Leerer Port: üblicher Port der Verschlüsselung',
        );
    }

    #[Test]
    public function smtpWithoutLoginNeedsNoPassword(): void
    {
        $result = FormValidator::website(self::SITE + ['mail_transport' => 'smtp', 'smtp_host' => 'localhost', 'smtp_port' => '25', 'smtp_encryption' => 'none', 'smtp_user' => '', 'smtp_password' => 'wird verworfen']);

        $this->assertSame([], $result['errors']);
        $this->assertSame('', $result['values']['mail']['password']);
        $this->assertSame(25, $result['values']['mail']['port']);
    }

    /**
     * @return iterable<string, array{array<string, string>, string}>
     */
    public static function invalidMailInput(): iterable
    {
        $smtp = ['mail_transport' => 'smtp', 'smtp_host' => 'smtp.example.org', 'smtp_port' => '587', 'smtp_encryption' => 'starttls', 'smtp_user' => 'news@example.org', 'smtp_password' => 'geheim'];

        yield 'Versandart unbekannt' => [['mail_transport' => 'sendmail'] + $smtp, 'mail_transport'];
        yield 'Server fehlt' => [['smtp_host' => ''] + $smtp, 'smtp_host'];
        yield 'Server mit Port' => [['smtp_host' => 'smtp.example.org:587'] + $smtp, 'smtp_host'];
        yield 'Server mit Schema' => [['smtp_host' => 'smtps://smtp.example.org'] + $smtp, 'smtp_host'];
        yield 'Port zu groß' => [['smtp_port' => '70000'] + $smtp, 'smtp_port'];
        yield 'Port Text' => [['smtp_port' => 'abc'] + $smtp, 'smtp_port'];
        yield 'Verschlüsselung unbekannt' => [['smtp_encryption' => 'tls'] + $smtp, 'smtp_encryption'];
        yield 'Passwort fehlt' => [['smtp_password' => ''] + $smtp, 'smtp_password'];
        yield 'Passwort mit NUL' => [['smtp_password' => "a\0b"] + $smtp, 'smtp_password'];
        yield 'Passwort zu lang' => [['smtp_password' => str_repeat('x', 256)] + $smtp, 'smtp_password'];
        yield 'Benutzer mit Zeilenumbruch' => [['smtp_user' => "news\r\nRCPT TO:<x@y.z>"] + $smtp, 'smtp_user'];
    }

    /**
     * @param array<string, string> $input
     */
    #[Test]
    #[DataProvider('invalidMailInput')]
    public function invalidMailInputIsReportedAtTheField(array $input, string $field): void
    {
        $this->assertSame([$field], array_keys(FormValidator::website(self::SITE + $input)['errors']));
    }

    #[Test]
    public function emptyPasswordKeepsTheStoredOneForTheSameServerAndUser(): void
    {
        $previous = ['transport' => 'smtp', 'host' => 'smtp.example.org', 'port' => 587, 'encryption' => 'starttls', 'user' => 'news@example.org', 'password' => 'bereits-gespeichert'];
        $input = self::SITE + ['mail_transport' => 'smtp', 'smtp_host' => 'smtp.example.org', 'smtp_port' => '587', 'smtp_encryption' => 'starttls', 'smtp_user' => 'news@example.org', 'smtp_password' => ''];

        $this->assertSame('bereits-gespeichert', FormValidator::website($input, $previous)['values']['mail']['password']);
        $this->assertArrayHasKey('smtp_password', FormValidator::website(['smtp_user' => 'anders@example.org'] + $input, $previous)['errors']);
        $this->assertArrayHasKey('smtp_password', FormValidator::website(['smtp_host' => 'mail.example.org'] + $input, $previous)['errors'], 'Anderer Server: Passwort neu eingeben');
    }

    #[Test]
    public function mailFormValuesNeverContainThePassword(): void
    {
        $this->assertSame(
            ['mail_transport' => 'smtp', 'smtp_host' => 'smtp.example.org', 'smtp_port' => '465', 'smtp_encryption' => 'ssl', 'smtp_user' => 'news@example.org'],
            FormValidator::mailFormValues(['transport' => 'smtp', 'host' => 'smtp.example.org', 'port' => 0, 'encryption' => 'ssl', 'user' => 'news@example.org', 'password' => 'x']),
        );
        $this->assertSame(
            ['mail_transport' => 'mail', 'smtp_host' => '', 'smtp_port' => '587', 'smtp_encryption' => 'starttls', 'smtp_user' => ''],
            FormValidator::mailFormValues(LocalConfig::DEFAULT_MAIL),
            'Für einen neuen SMTP-Server ist STARTTLS auf Port 587 vorbelegt',
        );
    }

    /**
     * @return iterable<string, array{array<string, string>, string}>
     */
    public static function invalidWebsiteInput(): iterable
    {
        $valid = ['site_url' => 'http://localhost:8229', 'site_email' => 'news@example.org', 'site_language' => 'german-du'];

        yield 'URL leer' => [['site_url' => ''] + $valid, 'site_url'];
        yield 'URL ohne Schema' => [['site_url' => 'www.example.org'] + $valid, 'site_url'];
        yield 'URL mit javascript:' => [['site_url' => 'javascript:alert(1)'] + $valid, 'site_url'];
        yield 'URL mit ftp' => [['site_url' => 'ftp://example.org'] + $valid, 'site_url'];
        yield 'URL zu lang' => [['site_url' => 'https://example.org/' . str_repeat('a', 240)] + $valid, 'site_url'];
        yield 'Absender leer' => [['site_email' => ''] + $valid, 'site_email'];
        yield 'Absender ungültig' => [['site_email' => 'kein-at-zeichen'] + $valid, 'site_email'];
        yield 'Sprache unbekannt' => [['site_language' => 'french'] + $valid, 'site_language'];
    }

    /**
     * @param array<string, string> $input
     */
    #[Test]
    #[DataProvider('invalidWebsiteInput')]
    public function invalidWebsiteInputIsReportedAtTheField(array $input, string $field): void
    {
        $this->assertSame([$field], array_keys(FormValidator::website($input)['errors']));
    }

    // ── Schritt 4: Administrator ──

    #[Test]
    public function validAdminInputIsAccepted(): void
    {
        $result = FormValidator::admin([
            'admin_nickname' => 'Jörg_Müller-1',
            'admin_email' => 'joerg@example.org',
            'admin_password' => ' Geheim#2026 ',
            'admin_password_confirm' => ' Geheim#2026 ',
        ]);

        $this->assertSame([], $result['errors']);
        $this->assertSame('Jörg_Müller-1', $result['values']['nickname']);
        $this->assertSame(' Geheim#2026 ', $result['values']['password'], 'Das Login-Formular trimmt nicht – der Installer auch nicht');
    }

    /**
     * @return iterable<string, array{array<string, string>, string}>
     */
    public static function invalidAdminInput(): iterable
    {
        $valid = [
            'admin_nickname' => 'admin',
            'admin_email' => 'admin@example.org',
            'admin_password' => 'sicher-genug',
            'admin_password_confirm' => 'sicher-genug',
        ];

        yield 'Nickname leer' => [['admin_nickname' => ''] + $valid, 'admin_nickname'];
        yield 'Nickname zu kurz' => [['admin_nickname' => 'ab'] + $valid, 'admin_nickname'];
        yield 'Nickname zu lang' => [['admin_nickname' => str_repeat('a', 31)] + $valid, 'admin_nickname'];
        yield 'Nickname mit Leerzeichen' => [['admin_nickname' => 'Max Müller'] + $valid, 'admin_nickname'];
        yield 'Nickname mit HTML' => [['admin_nickname' => '<b>admin</b>'] + $valid, 'admin_nickname'];
        yield 'E-Mail ungültig' => [['admin_email' => 'admin@'] + $valid, 'admin_email'];
        yield 'E-Mail leer' => [['admin_email' => ''] + $valid, 'admin_email'];
        yield 'Passwort leer' => [['admin_password' => '', 'admin_password_confirm' => ''] + $valid, 'admin_password'];
        yield 'Passwort zu kurz' => [['admin_password' => 'kurz12', 'admin_password_confirm' => 'kurz12'] + $valid, 'admin_password'];
        yield 'Passwort über 72 Byte' => [
            ['admin_password' => str_repeat('ä', 37), 'admin_password_confirm' => str_repeat('ä', 37)] + $valid,
            'admin_password',
        ];
        yield 'Wiederholung abweichend' => [['admin_password_confirm' => 'sicher-genug!'] + $valid, 'admin_password_confirm'];
    }

    /**
     * @param array<string, string> $input
     */
    #[Test]
    #[DataProvider('invalidAdminInput')]
    public function invalidAdminInputIsReportedAtTheField(array $input, string $field): void
    {
        $this->assertSame([$field], array_keys(FormValidator::admin($input)['errors']));
    }

    #[Test]
    public function nicknameRulesMatchTheRegistration(): void
    {
        foreach (['abc', 'Ärger.ÖÜß', 'a_b-c.d', str_repeat('x', 30)] as $nickname) {
            $this->assertTrue(FormValidator::isNickname($nickname), $nickname);
            $this->assertSame($nickname, pn_validate_nickname($nickname), 'Gleiche Regel wie pn_validate_nickname()');
        }
    }

    #[Test]
    public function passwordOfExactly72BytesIsAccepted(): void
    {
        $this->assertNull(FormValidator::passwordError(str_repeat('a', 72)));
        $this->assertNotNull(FormValidator::passwordError(str_repeat('a', 73)));
        $this->assertNull(FormValidator::passwordError('äöüßÄÖÜ!'), '8 Zeichen, auch wenn es 15 Byte sind');
    }
}
