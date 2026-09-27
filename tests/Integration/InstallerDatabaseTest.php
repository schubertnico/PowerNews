<?php

declare(strict_types=1);

namespace PowerNews\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use PowerNews\Installer\DatabaseSetup;
use PowerNews\Installer\InstallState;
use PowerNews\Installer\Schema;
use RuntimeException;

/**
 * Web-Installer gegen eine echte Datenbank: Verbindungstest, Einspielen,
 * Administrator-Login, Schutz bestehender Tabellen, Aufräumen nach Fehlern.
 */
final class InstallerDatabaseTest extends InstallerDatabaseTestCase
{
    #[Test]
    public function connectionTestReportsASupportedServerAndAnEmptyDatabase(): void
    {
        $check = DatabaseSetup::inspect(self::connect(), self::$db);

        $this->assertTrue($check['ok']);
        $this->assertNotNull($check['server']);
        $this->assertTrue($check['server']->isSupported(), $check['server']->label());
        $this->assertSame([], $check['tables']);
    }

    #[Test]
    public function wrongPasswordYieldsCode1045WithoutException(): void
    {
        $check = DatabaseSetup::inspect(self::connect(), ['password' => 'sicher-falsch'] + self::$db);

        $this->assertFalse($check['ok']);
        $this->assertContains($check['code'], [1045, 1698]);
        $this->assertStringContainsString('Benutzername oder Passwort', DatabaseSetup::friendlyError($check['code']));
    }

    #[Test]
    public function installCreatesSchemaSettingsAdminConfigAndLock(): void
    {
        $outcome = $this->install();

        $this->assertSame(1, $outcome['admin_id']);
        $this->assertTrue($outcome['config_written']);
        $this->assertSame('pninc/install.lock', $outcome['lock_file']);
        $this->assertFileExists($this->root . '/pninc/install.lock');

        $local = require $this->root . '/pninc/config.local.php';
        $this->assertIsArray($local);
        $this->assertSame(self::$db, $local['db']);

        $mysqli = $this->mysqli();
        $this->assertEqualsCanonicalizing(
            Schema::tableNames(Schema::fromFile($this->root . '/powernews.sql')),
            DatabaseSetup::existingTables($mysqli),
        );
        $this->assertSame('1', (string) self::value($mysqli, 'SELECT COUNT(*) FROM pn_config'));
        $this->assertSame('http://localhost:8229', self::value($mysqli, 'SELECT url FROM pn_config'));
        $this->assertSame('news@example.org', self::value($mysqli, 'SELECT email FROM pn_config'));
        $this->assertSame('1', (string) self::value($mysqli, 'SELECT COUNT(*) FROM pn_templates'), 'Nur „Default“ (B18)');
        // Template vollständig (B01): alle HTML-Entitäten mit ihren Semikolons sind angekommen.
        $templateSql = implode("\n", array_filter(
            Schema::fromFile($this->root . '/powernews.sql'),
            static fn (string $statement): bool => Schema::insertedTable($statement) === 'pn_templates',
        ));
        $templateRow = (string) self::value($mysqli, "SELECT CONCAT_WS('|', message, headline, news, comment, usermenu, usermenu2, relatedlinks,
            commentform, registerform, loginform, logout, senddataform, profileform, archive, sendnewsform, addemail, editemail,
            registeremail, dataemail) FROM pn_templates WHERE id = 1");
        $entities = (int) preg_match_all('/&[a-z]+;/', $templateRow);
        $this->assertGreaterThan(10, $entities);
        $this->assertSame((int) preg_match_all('/&[a-z]+;/', $templateSql), $entities);
        $this->assertSame('Allgemein', self::value($mysqli, 'SELECT name FROM pn_categories WHERE id = 1'));

        $result = $mysqli->query('SELECT * FROM pn_users');
        $this->assertInstanceOf(\mysqli_result::class, $result);
        $this->assertSame(1, $result->num_rows);
        $admin = $result->fetch_assoc();
        $this->assertIsArray($admin);
        $this->assertSame('Redaktion', $admin['nickname']);
        $this->assertSame('redaktion@example.org', $admin['email']);
        $this->assertSame('NO', $admin['showemail']);
        $this->assertSame('Activated', $admin['status']);
        $this->assertTrue(pnadmin_verify_password(self::ADMIN_PASSWORD, (string) $admin['password']));

        $perms = $mysqli->query('SELECT * FROM pn_permissions');
        $this->assertInstanceOf(\mysqli_result::class, $perms);
        $this->assertSame(1, $perms->num_rows);
        $row = $perms->fetch_assoc();
        $this->assertIsArray($row);
        $this->assertSame('1', (string) $row['userid']);
        foreach ($row as $column => $value) {
            if (str_starts_with((string) $column, 'can')) {
                $this->assertSame('YES', $value, $column);
            }
        }

        $this->assertTrue(DatabaseSetup::hasConfigRow($mysqli));
        $this->assertSame(InstallState::REASON_LOCK_FILE, InstallState::detectLockReason($this->root, self::$db, static fn (): ?bool => null));
        unlink($this->root . '/pninc/install.lock');
        $this->assertSame(InstallState::REASON_LOCAL_CONFIG, InstallState::detectLockReason($this->root, self::$db, static fn (): ?bool => null));
        unlink($this->root . '/pninc/config.local.php');
        $probe = static fn (): ?bool => DatabaseSetup::hasConfigRow(DatabaseSetup::connect(self::$db));
        $this->assertSame(InstallState::REASON_DATABASE, InstallState::detectLockReason($this->root, self::$db, $probe));
        $mysqli->close();
    }

    #[Test]
    public function newAdminCanLogInWithNicknameAndPassword(): void
    {
        $this->install('Jörg_Müller');

        global $pn_handler, $pn_config;
        $savedHandler = $pn_handler;
        $savedConfig = $pn_config;
        $pn_handler = $this->mysqli();
        $pn_config = pn_test_setup_config();

        try {
            $login = new \login();
            $this->assertSame(L_USR_WRONGPW, $login->checklogin('Jörg_Müller', 'falsch'));
            $this->assertSame('loggedin', @$login->checklogin('Jörg_Müller', self::ADMIN_PASSWORD));
        } finally {
            $pn_handler->close();
            $pn_handler = $savedHandler;
            $pn_config = $savedConfig;
        }
    }

    #[Test]
    public function existingTablesAreNeverTouched(): void
    {
        $mysqli = $this->mysqli();
        $mysqli->query('CREATE TABLE pn_news (id int) ENGINE=InnoDB');
        $mysqli->query('INSERT INTO pn_news VALUES (42)');

        $this->assertSame(['pn_news'], DatabaseSetup::inspect(self::connect(), self::$db)['tables']);

        try {
            $this->install();
            $this->fail('Eine Datenbank mit pn_-Tabellen darf nicht installiert werden.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Es wurde nichts verändert', $e->getMessage());
        }

        $this->assertSame(['pn_news'], DatabaseSetup::existingTables($mysqli));
        $this->assertSame('42', (string) self::value($mysqli, 'SELECT id FROM pn_news'));
        $this->assertFileDoesNotExist($this->root . '/pninc/config.local.php');
        $this->assertNull(InstallState::existingLockFile($this->root));
        $mysqli->close();
    }

    #[Test]
    public function failedInstallRemovesOnlyTheTablesItCreated(): void
    {
        $mysqli = $this->mysqli();
        $mysqli->query('CREATE TABLE other_app (id int) ENGINE=InnoDB');

        $sql = (string) file_get_contents($this->root . '/powernews.sql');
        $broken = (string) preg_replace('/^INSERT INTO `pn_templates` VALUES .*$/m', 'INSERT INTO `pn_templates` VALUES (1);', $sql);
        file_put_contents($this->root . '/powernews.sql', $broken);

        try {
            $this->install();
            $this->fail('Ein fehlerhaftes Schema muss abbrechen.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Bereits angelegte Tabellen wurden wieder entfernt', $e->getMessage());
        }

        $this->assertSame([], DatabaseSetup::existingTables($mysqli));
        $this->assertSame('1', (string) self::value($mysqli, "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'other_app'"));
        $this->assertFileDoesNotExist($this->root . '/pninc/config.local.php');
        $this->assertNull(InstallState::existingLockFile($this->root));
        $mysqli->close();
    }

    #[Test]
    public function hasConfigRowIsFalseForAnEmptyDatabase(): void
    {
        $mysqli = $this->mysqli();

        $this->assertFalse(DatabaseSetup::hasConfigRow($mysqli));
        $mysqli->close();
    }
}
