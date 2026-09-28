<?php

declare(strict_types=1);

namespace PowerNews\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use PowerNews\Tests\DatabaseTestCase;

/**
 * Regressionstests für B08 (Bild-Upload ohne Typprüfung), B24 („Benutzer schreiben“
 * übernimmt Admin-Konten), B38 (Rechteliste löscht über die falsche Spalte) und
 * B39 („Kommentare schreiben“ wird nicht geprüft).
 */
class AdminPermissionSecurityTest extends DatabaseTestCase
{
    private const PNG_1X1 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';
    private const GIF_1X1 = 'R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';

    /** @var list<string> */
    private array $tempFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $file) {
            @unlink($file);
        }
        parent::tearDown();
    }

    private function tempFile(string $content): string
    {
        $path = (string) tempnam(sys_get_temp_dir(), 'pnimg');
        file_put_contents($path, $content);
        $this->tempFiles[] = $path;

        return $path;
    }

    /**
     * @param array<string, string> $perms
     */
    private function setPermissions(int $userId, array $perms): void
    {
        global $pn_handler;

        $this->insertTestPermissions($userId, 'NO');
        foreach ($perms as $field => $value) {
            $stmt = mysqli_prepare($pn_handler, 'UPDATE pn_permissions SET `' . $field . '` = ? WHERE userid = ?');
            mysqli_stmt_bind_param($stmt, 'si', $value, $userId);
            mysqli_stmt_execute($stmt);
        }
    }

    #[Test]
    public function only_real_images_with_matching_extension_are_accepted(): void
    {
        $category = new \category();
        $png = $this->tempFile((string) base64_decode(self::PNG_1X1, true));
        $gif = $this->tempFile((string) base64_decode(self::GIF_1X1, true));
        $script = $this->tempFile('<?php echo "unsicher"; ?>');

        $this->assertSame('png', $category->validatepicture($png, 'logo.png'));
        $this->assertSame('gif', $category->validatepicture($gif, 'Logo.GIF'));

        $this->assertNull($category->validatepicture($script, 'shell.php'), 'PHP-Endung');
        $this->assertNull($category->validatepicture($script, 'bild.png'), 'Skript mit Bildendung');
        $this->assertNull($category->validatepicture($png, 'bild.php'), 'Bildinhalt mit PHP-Endung');
        $this->assertNull($category->validatepicture($png, 'bild.gif'), 'Endung passt nicht zum Inhalt');
        $this->assertNull($category->validatepicture($png, 'bild.png.php'));
        $this->assertNull($category->validatepicture($this->tempFile(''), 'leer.png'));
    }

    #[Test]
    public function uploads_are_only_taken_from_real_uploads_and_renamed(): void
    {
        $png = $this->tempFile((string) base64_decode(self::PNG_1X1, true));

        // Kein echter Upload (is_uploaded_file) -> abgelehnt, nichts wird kopiert.
        $this->assertSame('', (new \category())->storepicture(['name' => 'logo.png', 'tmp_name' => $png, 'error' => UPLOAD_ERR_OK]));
        $this->assertSame('', (new \category())->storepicture(['name' => 'logo.png', 'tmp_name' => $png, 'error' => UPLOAD_ERR_PARTIAL]));
    }

    #[Test]
    public function orphaned_permission_rows_are_deleted_by_their_own_id(): void
    {
        global $pn_handler;

        // Benutzer mit hoher, fester ID und eigener Rechtezeile.
        $userId = 700000 + random_int(1, 99999);
        mysqli_query($pn_handler, "INSERT INTO pn_users (id, nickname, email, password, registered) VALUES ($userId, 'rechte_$userId', 'rechte_$userId@example.com', 'x', 0)");
        $this->insertTestPermissions($userId);

        // Verwaiste Zeile eines gelöschten Benutzers, deren eigene id zufällig gleich der
        // id des Benutzers oben ist. Bis 3.11 löschte die Liste dann „WHERE userid = <id>“,
        // also die Rechte des falschen Benutzers.
        $ghostUserId = $userId + 500000;
        mysqli_query($pn_handler, "INSERT INTO pn_permissions (id, userid) VALUES ($userId, $ghostUserId)");

        $this->captureOutput(fn () => (new \permissions())->listpermissions());

        $this->assertSame(1, mysqli_num_rows(pn_query_by_id($pn_handler, 'SELECT id FROM pn_permissions WHERE userid = ?', $userId)), 'Die Rechte des existierenden Benutzers bleiben erhalten.');
        $this->assertSame(0, mysqli_num_rows(pn_query_by_id($pn_handler, 'SELECT id FROM pn_permissions WHERE userid = ?', $ghostUserId)), 'Die verwaiste Zeile ist entfernt.');
    }

    #[Test]
    public function user_manager_cannot_take_over_admin_accounts(): void
    {
        $managerId = $this->insertTestUser('benutzerverwalter', 'verwalter@example.com');
        $this->setPermissions($managerId, ['canreadusers' => 'YES', 'canwriteusers' => 'YES']);
        $adminId = $this->insertTestUser('chefadmin', 'chefadmin@example.com');
        $this->insertTestPermissions($adminId);
        $readerId = $this->insertTestUser('normalleser', 'normalleser@example.com');
        $this->loginAsUser($managerId, 'benutzerverwalter', 'verwalter@example.com');
        $managerPerms = (new \getadmin())->getpermissions($managerId);

        $form = $this->renderAdminPage('users_edit.inc.php', ['userid' => (string) $adminId], [], $managerPerms);
        $this->assertStringContainsString(\L_USR_NOTALLOWEDTOEDIT, $form);
        $this->assertStringNotContainsString('id="pn_newpassword"', $form);

        $this->renderAdminPage('users_edit.inc.php', ['userid' => (string) $adminId, 'edit' => 'YES'], [
            'nickname' => 'chefadmin',
            'email' => 'angreifer@example.com',
            'newpassword' => 'YES',
            'status' => 'Activated',
        ], $managerPerms);
        $this->assertSame('chefadmin@example.com', (new \user())->getuserdata($adminId)['email']);

        // Normale Konten darf der Verwalter weiter bearbeiten.
        $this->assertTrue((new \user())->caneditaccount($managerPerms, $managerId, $readerId));
    }

    #[Test]
    public function permission_writers_and_equal_rights_may_edit_admin_accounts(): void
    {
        $adminId = $this->insertTestUser('ziel_admin', 'ziel_admin@example.com');
        $this->setPermissions($adminId, ['canreadnews' => 'YES', 'canwritenews' => 'YES']);
        $user = new \user();

        $this->assertTrue($user->caneditaccount(['canwritepermissions' => 'YES'], 1, $adminId));
        $this->assertTrue($user->caneditaccount(['canreadnews' => 'YES', 'canwritenews' => 'YES', 'canwriteusers' => 'YES'], 1, $adminId));
        $this->assertFalse($user->caneditaccount(['canreadnews' => 'YES', 'canwriteusers' => 'YES'], 1, $adminId));
        $this->assertTrue($user->caneditaccount([], $adminId, $adminId), 'Das eigene Konto ist immer erlaubt.');
    }

    #[Test]
    public function comments_are_read_only_without_write_permission(): void
    {
        global $pn_config, $pnconfig;

        $pnconfig['comments'] = 'YES';
        $userId = $this->insertTestUser('nur_lesen', 'nur_lesen@example.com');
        $newsId = $this->insertTestNews($userId, 0, 'Kommentierte News', 'Text');
        $commentId = $this->insertTestComment($newsId, 0, 'Originalkommentar');
        $perms = ['canwritecomments' => 'NO'];

        $form = $this->renderAdminPage('news_edit.inc.php', ['newsid' => (string) $newsId], [], $perms);
        $this->assertStringContainsString('Originalkommentar', $form);
        $this->assertStringNotContainsString('name="commenttext[]"', $form);
        $this->assertStringNotContainsString('editcomments=YES', $form);

        $output = $this->renderAdminPage('news_edit.inc.php', ['newsid' => (string) $newsId, 'edit' => 'YES', 'editcomments' => 'YES'], [
            'commentid' => [(string) $commentId],
            'commenttext' => ['Manipuliert'],
            'commentdelete' => [(string) $commentId],
        ], $perms);

        $this->assertStringContainsString(\L_ALL_ACCESSDENIED, $output);
        $this->assertSame('Originalkommentar', $this->fetchRow($pn_config['commenttable'], $commentId)['text']);
    }
}
