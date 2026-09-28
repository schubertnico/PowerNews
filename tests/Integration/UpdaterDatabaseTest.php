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

    #[Test]
    public function freshInstallIsAlreadyUpToDate(): void
    {
        $this->install();
        $mysqli = $this->mysqli();

        $updater = new Updater($mysqli, $this->root, Schema::fromFile($this->root . '/powernews.sql'), pn_test_setup_config());

        $this->assertSame([], array_filter($updater->status(), static fn (array $step): bool => $step['pending']));
        $this->assertSame([], \pn_migrations_pending($mysqli));
        $this->assertSame(pn_default_template(), array_intersect_key(self::row($mysqli, 'SELECT * FROM pn_templates WHERE id = 1'), pn_default_template()));
        $mysqli->close();
    }

    #[Test]
    public function updaterBringsADatabaseOfRelease311To312(): void
    {
        $mysqli = $this->mysqli();

        // Datenbank einer 3.11-Installation: Schema samt Testvorlagen und verwaister
        // Rechtezeile aus der damaligen powernews.sql …
        foreach (Schema::fromFile(__DIR__ . '/../Fixtures/powernews-3.11.sql') as $statement) {
            $mysqli->query($statement);
        }
        // … plus Betrieb: Admin (id 2 wie beim alten Installer), News mit addslashes() und
        // Link im Zeilenformat, eine 360 Tage gültige Admin-Sitzung.
        $mysqli->query("INSERT INTO pn_users (id, nickname, email, password, registered) VALUES (2, 'admin', 'admin@localhost', 'x', 0)");
        $mysqli->query("INSERT INTO pn_permissions (userid, canreadconfig, canwriteconfig) VALUES (2, 'YES', 'YES')");
        $stmt = $mysqli->prepare("INSERT INTO pn_news (userid, time, catid, title, text, moretext, relatedlinks) VALUES (2, 0, 1, ?, 'Text', '', ?)");
        $this->assertNotFalse($stmt);
        $title = addslashes('Peter\'s "Aktion"');
        $links = "Verein!@!@!https://verein.example.org!@!@!_blank\nBöse!@!@!javascript:alert(1)!@!@!_blank";
        $stmt->bind_param('ss', $title, $links);
        $stmt->execute();
        $newsId = (int) $mysqli->insert_id;
        $now = time();
        $mysqli->query("INSERT INTO pn_sessions (userid, token_hash, created, expires) VALUES (2, REPEAT('a', 64), {$now}, {$now} + 360 * 86400)");

        $updater = new Updater($mysqli, $this->root, Schema::fromFile($this->root . '/powernews.sql'), pn_test_setup_config());
        $this->assertSame(['pn_password_resets', 'pn_migrations'], $updater->missingTables());
        $this->assertSame(
            [Updater::STEP_TABLES, Updater::STEP_TEMPLATES, Updater::STEP_PERMISSIONS, Updater::STEP_MIGRATIONS, Updater::STEP_LOCK],
            array_column(array_filter($updater->status(), static fn (array $step): bool => $step['pending']), 'id'),
        );

        $results = $updater->run('2026-09-28 12:00:00');

        foreach ($results as $result) {
            $this->assertTrue($result['ok'], $result['label'] . ': ' . $result['message']);
        }
        $this->assertSame([Updater::STEP_TABLES, Updater::STEP_TEMPLATES, Updater::STEP_PERMISSIONS, Updater::STEP_MIGRATIONS, Updater::STEP_LOCK], array_column($results, 'id'));
        $this->assertSame(
            '1 Datensatz von überzähligen Backslashes bereinigt. 1 langlaufende Admin-Sitzung beendet. Tabelle pn_password_resets angelegt. '
            . '1 News mit weiterführenden Links ins JSON-Format überführt. 14 Template-Felder auf den Stand 3.12 gebracht.',
            $results[3]['message'],
            'Nur die 14 geänderten Felder des Default-Templates; die Testvorlagen sind vorher schon entfernt',
        );

        // Neue Tabelle für „Passwort vergessen“, Migrationen vermerkt
        $this->assertContains('pn_password_resets', DatabaseSetup::existingTables($mysqli));
        $this->assertSame([], \pn_migrations_pending($mysqli));
        $this->assertSame('5', (string) self::value($mysqli, 'SELECT COUNT(*) FROM pn_migrations WHERE applied_at > 0'));

        // Inhalte auf dem Stand 3.12
        $news = self::row($mysqli, 'SELECT title, relatedlinks FROM pn_news WHERE id = ' . $newsId);
        $this->assertSame('Peter\'s "Aktion"', $news['title']);
        $this->assertSame('[{"title":"Verein","url":"https://verein.example.org","target":"_blank"}]', $news['relatedlinks']);
        $this->assertSame(pn_default_template(), array_intersect_key(self::row($mysqli, 'SELECT * FROM pn_templates WHERE id = 1'), pn_default_template()));
        $this->assertSame('1', (string) self::value($mysqli, 'SELECT GROUP_CONCAT(id) FROM pn_templates'));
        $this->assertSame('2', (string) self::value($mysqli, 'SELECT GROUP_CONCAT(userid) FROM pn_permissions'));
        $this->assertSame('0', (string) self::value($mysqli, 'SELECT COUNT(*) FROM pn_sessions'), 'Alte Admin-Sitzung beendet');

        $this->assertSame([], array_filter($updater->status(), static fn (array $step): bool => $step['pending']));
        $this->assertSame([], $updater->run('2026-09-28 12:05:00'), 'Zweiter Lauf: nichts mehr zu tun');
        $mysqli->close();
    }

    /**
     * @return array<string, mixed>
     */
    private static function row(\mysqli $mysqli, string $sql): array
    {
        $result = $mysqli->query($sql);
        $row = $result instanceof \mysqli_result ? $result->fetch_assoc() : null;

        return is_array($row) ? $row : [];
    }
}
