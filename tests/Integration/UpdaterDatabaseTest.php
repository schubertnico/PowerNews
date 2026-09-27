<?php

declare(strict_types=1);

namespace PowerNews\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use PowerNews\Installer\DatabaseSetup;
use PowerNews\Installer\InstallState;
use PowerNews\Installer\Schema;
use PowerNews\Installer\Updater;

/**
 * update.php: Datenbankschritte beim Update einer 3.x-Installation.
 */
final class UpdaterDatabaseTest extends InstallerDatabaseTestCase
{
    #[Test]
    public function updaterRepairsA311DatabaseAndIsIdempotent(): void
    {
        $this->install();
        unlink($this->root . '/pninc/install.lock');

        // Stand einer 3.11-Installation aus der alten powernews.sql nachstellen.
        $mysqli = $this->mysqli();
        $mysqli->query('DROP TABLE pn_login_attempts');
        $mysqli->query("INSERT INTO pn_templates (id, title, message, headline, news, comment, usermenu, usermenu2, relatedlinks, commentform, registerform, loginform, logout, senddataform, profileform, archive, sendnewsform, addemail, editemail, registeremail, dataemail)
            SELECT n, t, message, headline, news, comment, usermenu, usermenu2, relatedlinks, commentform, registerform, loginform, logout, senddataform, profileform, archive, sendnewsform, addemail, editemail, registeremail, dataemail
            FROM pn_templates, (SELECT 2 AS n, 'ftghgf' AS t UNION SELECT 3, 'dsfs' UNION SELECT 4, 'dfgdfg') junk WHERE id = 1");
        $mysqli->query("INSERT INTO pn_templates (id, title, message, headline, news, comment, usermenu, usermenu2, relatedlinks, commentform, registerform, loginform, logout, senddataform, profileform, archive, sendnewsform, addemail, editemail, registeremail, dataemail)
            SELECT 5, 'Eigenes', message, headline, news, comment, usermenu, usermenu2, relatedlinks, commentform, registerform, loginform, logout, senddataform, profileform, archive, sendnewsform, addemail, editemail, registeremail, dataemail FROM pn_templates WHERE id = 1");
        $mysqli->query("INSERT INTO pn_permissions (userid, canwriteconfig) VALUES (999, 'YES')");
        $mysqli->query('UPDATE pn_config SET template = 3');

        $updater = new Updater($mysqli, $this->root, Schema::fromFile($this->root . '/powernews.sql'));
        $pending = array_column(array_filter($updater->status(), static fn (array $step): bool => $step['pending']), 'id');
        $this->assertSame([Updater::STEP_TABLES, Updater::STEP_TEMPLATES, Updater::STEP_PERMISSIONS, Updater::STEP_LOCK], $pending);
        $this->assertSame(['ftghgf', 'dfgdfg'], $updater->junkTemplates(), 'Das eingestellte Template „dsfs“ bleibt');

        $results = $updater->run('2026-09-28 11:00:00');

        $this->assertCount(4, $results);
        foreach ($results as $result) {
            $this->assertTrue($result['ok'], $result['label'] . ': ' . $result['message']);
        }
        $this->assertContains('pn_login_attempts', DatabaseSetup::existingTables($mysqli));
        $this->assertSame('1,3,5', (string) self::value($mysqli, 'SELECT GROUP_CONCAT(id ORDER BY id) FROM pn_templates'));
        $this->assertSame('1', (string) self::value($mysqli, 'SELECT GROUP_CONCAT(userid) FROM pn_permissions'));
        $this->assertSame('pninc/install.lock', InstallState::existingLockFile($this->root));

        $this->assertSame([], array_filter($updater->status(), static fn (array $step): bool => $step['pending'] && $step['id'] !== Updater::STEP_TEMPLATES));
        $mysqli->query('UPDATE pn_config SET template = 1');
        $this->assertSame(['dsfs'], $updater->junkTemplates());
        $this->assertSame([Updater::STEP_TEMPLATES], array_column($updater->run('2026-09-28 11:05:00'), 'id'));
        $this->assertSame([], $updater->run('2026-09-28 11:10:00'), 'Zweiter Lauf: nichts mehr zu tun');
        $mysqli->close();
    }
}
