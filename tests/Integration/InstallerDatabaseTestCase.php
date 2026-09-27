<?php

declare(strict_types=1);

namespace PowerNews\Tests\Integration;

use Closure;
use mysqli;
use mysqli_sql_exception;
use PHPUnit\Framework\TestCase;
use PowerNews\Installer\DatabaseSetup;
use PowerNews\Installer\Setup;
use PowerNews\LocalConfig;
use PowerNews\Tests\Unit\Installer\InstallerFixture;

require_once __DIR__ . '/../../pninc/installer/autoload.php';

/**
 * Grundlage für Installer- und Update-Tests gegen eine echte Datenbank
 * (MariaDB oder MySQL).
 *
 * Verwendet eine eigene Datenbank „<PN_DB_NAME>_installer_test“, die vor
 * jedem Test neu angelegt wird, damit die übrigen Integrationstests
 * unberührt bleiben. Fehlen Server oder das Recht CREATE DATABASE, werden
 * die Tests übersprungen.
 */
abstract class InstallerDatabaseTestCase extends TestCase
{
    protected const array WEBSITE = ['url' => 'http://localhost:8229', 'email' => 'news@example.org', 'language' => 'german-du'];

    protected const string ADMIN_PASSWORD = "Lichtblick-26 'ä\"\\";

    protected static ?mysqli $server = null;

    /** @var array{host: string, port: int, user: string, password: string, database: string} */
    protected static array $db = LocalConfig::DEFAULT_DB;

    protected string $root = '';

    public static function setUpBeforeClass(): void
    {
        $env = LocalConfig::fromEnvironment(static fn (string $name): string|false => getenv($name));
        self::$db = array_replace($env, ['database' => $env['database'] . '_installer_test']);

        try {
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            self::$server = new mysqli($env['host'], $env['user'], $env['password'], null, $env['port']);
            self::$server->query('CREATE DATABASE IF NOT EXISTS `' . self::$db['database'] . '` CHARACTER SET utf8mb4');
        } catch (mysqli_sql_exception $e) {
            self::$server = null;
        }
    }

    public static function tearDownAfterClass(): void
    {
        self::$server?->query('DROP DATABASE IF EXISTS `' . self::$db['database'] . '`');
        self::$server?->close();
        self::$server = null;
    }

    protected function setUp(): void
    {
        parent::setUp();
        if (self::$server === null) {
            $this->markTestSkipped('Kein Datenbankserver mit CREATE-DATABASE-Recht erreichbar (PN_DB_*).');
        }

        self::$server->query('DROP DATABASE IF EXISTS `' . self::$db['database'] . '`');
        self::$server->query('CREATE DATABASE `' . self::$db['database'] . '` CHARACTER SET utf8mb4');
        $this->root = InstallerFixture::createRoot();
    }

    protected function tearDown(): void
    {
        InstallerFixture::removeRoot($this->root);
        parent::tearDown();
    }

    protected static function connect(): Closure
    {
        return DatabaseSetup::connect(...);
    }

    protected function mysqli(): mysqli
    {
        return DatabaseSetup::connect(self::$db);
    }

    /**
     * @return array{admin_id: int, config_written: bool, config_source: string, lock_file: string|null}
     */
    protected function install(string $nickname = 'Redaktion'): array
    {
        return Setup::run($this->root, self::connect(), self::$db, self::WEBSITE, [
            'nickname' => $nickname,
            'email' => 'redaktion@example.org',
            'password_hash' => password_hash(self::ADMIN_PASSWORD, PASSWORD_DEFAULT),
        ], '2026-09-28 10:00:00');
    }

    protected static function value(mysqli $mysqli, string $sql): mixed
    {
        $result = $mysqli->query($sql);
        $row = $result instanceof \mysqli_result ? $result->fetch_row() : null;

        return is_array($row) ? $row[0] : null;
    }
}
