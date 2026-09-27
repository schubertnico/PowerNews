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
        $this->assertSame(LocalConfig::SOURCE_DEFAULTS, $settings['source']);
        $this->assertTrue(LocalConfig::isDefaultDatabase($settings['db']));
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
    }

    private function lint(string $file): string
    {
        $output = [];
        exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($file) . ' 2>&1', $output, $exitCode);

        return $exitCode === 0 ? '' : implode("\n", $output);
    }
}
