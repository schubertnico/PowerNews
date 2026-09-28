<?php

declare(strict_types=1);

namespace PowerNews\Tests\Unit\Installer;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PowerNews\LocalConfig;

require_once __DIR__ . '/../../../pninc/installer/autoload.php';

/**
 * pninc/config.local.php: Rangfolge, Erzeugung und Schreiben.
 */
final class LocalConfigTest extends TestCase
{
    private string $dir = '';

    protected function setUp(): void
    {
        parent::setUp();
        $this->dir = sys_get_temp_dir() . '/pn_localconfig_' . bin2hex(random_bytes(6));
        mkdir($this->dir);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->dir . '/*') ?: [] as $file) {
            unlink($file);
        }
        rmdir($this->dir);
        parent::tearDown();
    }

    /**
     * @param array<string, string> $vars
     *
     * @return callable(string): (string|false)
     */
    private static function env(array $vars): callable
    {
        return static fn (string $name): string|false => $vars[$name] ?? false;
    }

    // ── Rangfolge: config.local.php > Umgebungsvariablen > Vorgaben ──

    #[Test]
    public function withoutEnvironmentAndFileTheDefaultsApply(): void
    {
        $settings = LocalConfig::load(self::env([]), $this->dir . '/config.local.php');

        $this->assertSame(LocalConfig::DEFAULT_DB, $settings['db']);
        $this->assertSame('german-du', $settings['language']);
        $this->assertSame(LocalConfig::DEFAULT_MAIL, $settings['mail']);
        $this->assertSame('mail', $settings['mail']['transport'], 'Bestandsinstallationen senden weiter mit mail()');
        $this->assertSame(LocalConfig::SOURCE_DEFAULTS, $settings['source']);
        $this->assertTrue(LocalConfig::isDefaultDatabase($settings['db']));
    }

    // ── Mailversand: config.local.php > PN_MAIL_* > Vorgaben ──

    #[Test]
    public function mailEnvironmentVariablesOverrideTheDefaults(): void
    {
        $settings = LocalConfig::load(self::env([
            'PN_MAIL_TRANSPORT' => ' SMTP ',
            'PN_MAIL_HOST' => 'smtp.example.org',
            'PN_MAIL_PORT' => '2525',
            'PN_MAIL_ENCRYPTION' => 'STARTTLS',
            'PN_MAIL_USER' => 'news@example.org',
            'PN_MAIL_PASS' => ' mit Leerzeichen ',
        ]), $this->dir . '/config.local.php');

        $this->assertSame(
            ['transport' => 'smtp', 'host' => 'smtp.example.org', 'port' => 2525, 'encryption' => 'starttls', 'user' => 'news@example.org', 'password' => ' mit Leerzeichen '],
            $settings['mail'],
            'Das Passwort wird nicht getrimmt',
        );
        $this->assertSame(LocalConfig::SOURCE_DEFAULTS, $settings['source'], 'Die Herkunft bezieht sich auf die Datenbank');
    }

    #[Test]
    public function emptyOrInvalidMailEnvironmentValuesFallBackToDefaults(): void
    {
        $mail = LocalConfig::mailFromEnvironment(self::env(['PN_MAIL_TRANSPORT' => '', 'PN_MAIL_HOST' => '  ', 'PN_MAIL_PORT' => '70000']));

        $this->assertSame(LocalConfig::DEFAULT_MAIL, $mail);
    }

    #[Test]
    public function localMailSettingsOverrideTheEnvironmentKeyByKey(): void
    {
        $file = $this->dir . '/config.local.php';
        file_put_contents($file, "<?php\nreturn ['mail' => ['transport' => 'smtp', 'port' => 465, 'encryption' => 'ssl', 'user' => 42]];\n");

        $settings = LocalConfig::load(self::env(['PN_MAIL_HOST' => 'mailpit', 'PN_MAIL_USER' => 'env-user', 'PN_MAIL_PASS' => 'env-pass']), $file);

        $this->assertSame(
            ['transport' => 'smtp', 'host' => 'mailpit', 'port' => 465, 'encryption' => 'ssl', 'user' => 'env-user', 'password' => 'env-pass'],
            $settings['mail'],
            'Fehlende oder falsch typisierte Werte kommen aus der Umgebung',
        );
        $this->assertSame(LocalConfig::DEFAULT_MAIL, LocalConfig::applyMail(LocalConfig::DEFAULT_MAIL, 'kein Array'));
    }

    #[Test]
    public function localFileWithoutMailSectionKeepsTheEnvironment(): void
    {
        $file = $this->dir . '/config.local.php';
        file_put_contents($file, "<?php\nreturn ['language' => 'english'];\n");

        $settings = LocalConfig::load(self::env(['PN_MAIL_TRANSPORT' => 'smtp', 'PN_MAIL_HOST' => 'mailpit']), $file);

        $this->assertSame('smtp', $settings['mail']['transport']);
        $this->assertSame('mailpit', $settings['mail']['host']);
    }

    #[Test]
    public function environmentVariablesOverrideTheDefaults(): void
    {
        $settings = LocalConfig::load(self::env([
            'PN_DB_HOST' => 'db',
            'PN_DB_PORT' => '3317',
            'PN_DB_USER' => 'powernews',
            'PN_DB_PASS' => '0',
            'PN_DB_NAME' => 'powernews_test',
        ]), $this->dir . '/config.local.php');

        $this->assertSame(
            ['host' => 'db', 'port' => 3317, 'user' => 'powernews', 'password' => '0', 'database' => 'powernews_test'],
            $settings['db'],
            'Auch das Passwort „0“ wird übernommen',
        );
        $this->assertSame(LocalConfig::SOURCE_ENVIRONMENT, $settings['source']);
    }

    #[Test]
    public function emptyOrInvalidEnvironmentValuesFallBackToDefaults(): void
    {
        $db = LocalConfig::fromEnvironment(self::env(['PN_DB_HOST' => '  ', 'PN_DB_PORT' => '99999', 'PN_DB_USER' => '']));

        $this->assertSame(LocalConfig::DEFAULT_DB, $db);
    }

    #[Test]
    public function localFileOverridesEnvironmentVariables(): void
    {
        $file = $this->dir . '/config.local.php';
        file_put_contents($file, LocalConfig::render(
            ['host' => 'sql.example.org', 'port' => 3306, 'user' => 'news_user', 'password' => 'Lichtblick-DB26', 'database' => 'news_db'],
            'german-sie',
            '2026-09-28 10:00:00',
        ));

        $settings = LocalConfig::load(self::env(['PN_DB_HOST' => 'db', 'PN_DB_USER' => 'powernews']), $file);

        $this->assertSame('sql.example.org', $settings['db']['host']);
        $this->assertSame('news_user', $settings['db']['user']);
        $this->assertSame('Lichtblick-DB26', $settings['db']['password']);
        $this->assertSame('news_db', $settings['db']['database']);
        $this->assertSame('german-sie', $settings['language']);
        $this->assertSame(LocalConfig::SOURCE_FILE, $settings['source']);
    }

    #[Test]
    public function partialOrInvalidLocalValuesKeepTheOtherSources(): void
    {
        $base = ['host' => 'db', 'port' => 3306, 'user' => 'u', 'password' => 'p', 'database' => 'd'];

        $settings = LocalConfig::apply($base, 'german-du', [
            'db' => ['host' => 'other', 'port' => '3307', 'user' => 42],
            'language' => 'klingonisch',
        ]);

        $this->assertSame(['host' => 'other', 'port' => 3306, 'user' => 'u', 'password' => 'p', 'database' => 'd'], $settings['db']);
        $this->assertSame('german-du', $settings['language']);
        $this->assertSame($base, LocalConfig::apply($base, 'german-du', 'kein Array')['db']);
    }

    // ── Erzeugen: Sonderzeichen im Passwort ──

    /**
     * @return iterable<string, array{string}>
     */
    public static function specialPasswords(): iterable
    {
        yield 'Anführungszeichen' => ['a\'b"c'];
        yield 'Backslashes' => ['C:\\pfad\\\\zu\\'];
        yield 'Dollar und geschweifte Klammern' => ['$pn_config{$x}${y}'];
        yield 'PHP-Endtag' => ['geheim?>'];
        yield 'Kommentarende und -anfang' => ['*/ /* # //'];
        yield 'Zeilenumbrüche und Tab' => ["zeile1\nzeile2\r\n\tende"];
        yield 'NUL-Byte' => ["vor\0nach"];
        yield 'Umlaute und Emoji' => ['Grüße-äöüß-€-😀'];
        yield 'Leerzeichen am Rand' => ['  leer  '];
        yield 'Heredoc-Marker' => ["<<<EOT\nx\nEOT;"];
        yield 'nur 0' => ['0'];
        yield 'leer' => [''];
    }

    #[Test]
    #[DataProvider('specialPasswords')]
    public function renderedFileReturnsTheSmtpPasswordUnchanged(string $password): void
    {
        $mail = ['transport' => 'smtp', 'host' => 'smtp.example.org', 'port' => 587, 'encryption' => 'starttls', 'user' => "news'user@example.org", 'password' => $password];
        $file = $this->dir . '/config.local.php';
        file_put_contents($file, LocalConfig::render(LocalConfig::DEFAULT_DB, 'german-du', '2026-09-28 10:00:00', $mail));

        $this->assertSame('', $this->lint($file), 'Die erzeugte Datei muss gültiges PHP sein');

        $loaded = require $file;
        $this->assertIsArray($loaded);
        $this->assertSame($mail, $loaded['mail']);
        $this->assertSame($mail, LocalConfig::load(self::env([]), $file)['mail']);
    }

    #[Test]
    #[DataProvider('specialPasswords')]
    public function renderedFileReturnsThePasswordUnchanged(string $password): void
    {
        $db = ['host' => 'sql.example.org', 'port' => 3306, 'user' => "news'user", 'password' => $password, 'database' => 'news_db'];
        $file = $this->dir . '/config.local.php';
        file_put_contents($file, LocalConfig::render($db, 'english', '2026-09-28 10:00:00'));

        $this->assertSame('', $this->lint($file), 'Die erzeugte Datei muss gültiges PHP sein');

        $loaded = require $file;
        $this->assertIsArray($loaded);
        $this->assertSame($db, $loaded['db']);
        $this->assertSame('english', $loaded['language']);
    }

    #[Test]
    public function renderedFileHasNoSideEffectsAndSanitisesTheTimestamp(): void
    {
        $source = LocalConfig::render(LocalConfig::DEFAULT_DB, 'unbekannt', "2026-09-28 */ echo 'x'; /*");

        $this->assertStringStartsWith("<?php\n\ndeclare(strict_types=1);", $source);
        $this->assertStringContainsString("'language' => 'german-du'", $source, 'Unbekannte Sprache wird zur Vorgabe');
        $this->assertStringContainsString("'transport' => 'mail'", $source, 'Ohne Angabe: Versand mit mail()');
        $this->assertStringNotContainsString("echo 'x'", $source);
        $this->assertStringNotContainsString('$', $source, 'Keine Variablen, nur return [...]');
        $this->assertStringNotContainsString('?>', $source);
    }

    // ── Schreiben ──

    #[Test]
    public function writeFileCreatesTheFileExclusively(): void
    {
        $file = $this->dir . '/config.local.php';

        $this->assertTrue(LocalConfig::writeFile($file, "<?php\nreturn [];\n"));
        $this->assertSame("<?php\nreturn [];\n", file_get_contents($file));
        $this->assertFalse(LocalConfig::writeFile($file, 'anderer Inhalt'), 'Eine vorhandene Datei wird nie überschrieben');
        $this->assertSame("<?php\nreturn [];\n", file_get_contents($file));
    }

    #[Test]
    public function writeFileFailsQuietlyWhenTheDirectoryIsMissing(): void
    {
        $this->assertFalse(LocalConfig::writeFile($this->dir . '/fehlt/config.local.php', 'x'));
    }

    #[Test]
    public function configIncLoadsTheLocalFileBeforeEnvironmentVariables(): void
    {
        $configInc = (string) file_get_contents(__DIR__ . '/../../../pninc/config.inc.php');

        $this->assertStringContainsString("require_once __DIR__ . '/localconfig.inc.php';", $configInc);
        $this->assertStringContainsString('PowerNews\LocalConfig::load(', $configInc);
        $this->assertStringContainsString("\$pn_config['mysqlport']", $configInc);
        $this->assertStringContainsString("\$pn_config['language'] = \$pn_local['language'];", $configInc);
        $this->assertStringContainsString("\$pn_config['mail'] = \$pn_local['mail'];", $configInc);
        $this->assertStringContainsString("\$pn_config['version'] = PN_VERSION;", $configInc, 'Eine Quelle für die Version');
    }

    private function lint(string $file): string
    {
        $output = [];
        exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($file) . ' 2>&1', $output, $exitCode);

        return $exitCode === 0 ? '' : implode("\n", $output);
    }
}
