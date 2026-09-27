<?php

declare(strict_types=1);

namespace PowerNews\Tests\Integration;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PowerNews\Tests\DatabaseTestCase;

/**
 * Regressionstests für B36: CSRF-Token auf allen Admin-Formularen, zentrale Prüfung jeder
 * schreibenden Anfrage und Logout nur per POST.
 */
class AdminCsrfTest extends DatabaseTestCase
{
    /** @var array<string, mixed> */
    private array $serverBackup = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->serverBackup = $_SERVER;
    }

    protected function tearDown(): void
    {
        global $pnadminsession;

        $pnadminsession = null;
        $_SERVER = $this->serverBackup;
        parent::tearDown();
    }

    /**
     * @return array<string, array{0: string, 1: array<string, string>}>
     */
    public static function formPages(): array
    {
        return [
            'News schreiben' => ['news_add.inc.php', ['page' => 'news', 'subpage' => 'add']],
            'News bearbeiten' => ['news_edit.inc.php', ['page' => 'news', 'subpage' => 'edit', 'newsid' => '__NEWS__']],
            'Kategorie anlegen' => ['categories_add.inc.php', ['page' => 'categories', 'subpage' => 'add']],
            'Kategorie bearbeiten' => ['categories_edit.inc.php', ['page' => 'categories', 'subpage' => 'edit', 'catid' => '1']],
            'Benutzer anlegen' => ['users_add.inc.php', ['page' => 'users', 'subpage' => 'add']],
            'Benutzer bearbeiten' => ['users_edit.inc.php', ['page' => 'users', 'subpage' => 'edit', 'userid' => '__USER__']],
            'Rechte vergeben' => ['permissions_add.inc.php', ['page' => 'permissions', 'subpage' => 'add']],
            'Rechte bearbeiten' => ['permissions_edit.inc.php', ['page' => 'permissions', 'subpage' => 'edit', 'userid' => '__USER__']],
            'Konfiguration' => ['configuration.inc.php', ['page' => 'configuration']],
            'Profil' => ['profile.inc.php', ['page' => 'profile']],
            'Template anlegen' => ['templates_add.inc.php', ['page' => 'templates', 'subpage' => 'add']],
            'Template bearbeiten' => ['templates_edit.inc.php', ['page' => 'templates', 'subpage' => 'edit', 'templateid' => '__TEMPLATE__']],
            'Login' => ['login.inc.php', []],
        ];
    }

    /**
     * @param array<string, string> $get
     */
    #[Test]
    #[DataProvider('formPages')]
    public function every_post_form_carries_a_csrf_token(string $file, array $get): void
    {
        global $pnconfig, $pn_handler;

        $pnconfig['categories'] = 'YES';
        $pnconfig['comments'] = 'YES';
        $userId = $this->insertTestUser('csrf_' . substr(md5($file), 0, 8), 'csrf_' . substr(md5($file), 0, 8) . '@example.com');
        $this->insertTestPermissions($userId);
        $newsId = $this->insertTestNews($userId, 1, 'CSRF-Test', 'Text');
        $this->insertTestComment($newsId, 0, 'Kommentar');
        $this->loginAsUser($userId, 'csrf', 'csrf@example.com');

        // Eigenes Template, damit der Test nicht vom Zustand des Default-Templates abhängt.
        $columns = ['title', 'message', 'headline', 'news', 'comment', 'usermenu', 'usermenu2', 'relatedlinks', 'commentform', 'registerform', 'loginform', 'logout', 'senddataform', 'profileform', 'archive', 'sendnewsform', 'addemail', 'editemail', 'registeremail', 'dataemail'];
        mysqli_query($pn_handler, 'INSERT INTO pn_templates (' . implode(', ', $columns) . ") VALUES ('CSRF " . uniqid() . "'" . str_repeat(", 'x'", count($columns) - 1) . ')');
        $templateId = (int) mysqli_insert_id($pn_handler);

        foreach ($get as $key => $value) {
            $get[$key] = str_replace(['__NEWS__', '__USER__', '__TEMPLATE__'], [(string) $newsId, (string) $userId, (string) $templateId], $value);
        }

        $output = $this->renderAdminPage($file, $get);

        $forms = preg_match_all('/<form [^>]*method="post"/', $output);
        $this->assertGreaterThan(0, $forms, 'Die Seite muss ein POST-Formular enthalten.');
        $this->assertSame($forms, preg_match_all('/name="csrf_token" value="[a-f0-9]{64}"/', $output));
    }

    #[Test]
    public function get_request_with_action_flag_is_rejected(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = ['page' => 'permissions', 'subpage' => 'edit', 'edit' => 'YES', 'userid' => '1'];
        $_POST = [];

        $this->assertTrue(pnadmin_guard_request());
        $this->assertArrayNotHasKey('edit', $_GET, 'Ohne Aktionsparameter zeigt die Seite nur ihr Formular.');
    }

    #[Test]
    public function logout_via_get_link_is_rejected(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = ['pnlogout' => 'YES'];

        $this->assertTrue(pnadmin_guard_request());
        $this->assertArrayNotHasKey('pnlogout', $_GET);
    }

    #[Test]
    public function post_without_or_with_wrong_token_is_rejected(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $_GET = ['page' => 'news', 'subpage' => 'add', 'add' => 'YES'];
        $_POST = ['title' => 'Untergeschoben', 'text' => 'Text'];
        $this->assertTrue(pnadmin_guard_request());
        $this->assertSame([], $_POST);

        $_GET = ['page' => 'news', 'subpage' => 'add', 'add' => 'YES'];
        $_POST = ['title' => 'Untergeschoben', 'csrf_token' => str_repeat('0', 64)];
        $this->assertTrue(pnadmin_guard_request());
        $this->assertSame([], $_POST);
    }

    #[Test]
    public function post_with_valid_token_passes(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET = ['page' => 'news', 'subpage' => 'add', 'add' => 'YES'];
        $_POST = ['title' => 'Echt', 'csrf_token' => pnadmin_csrf_token()];

        $this->assertFalse(pnadmin_guard_request());
        $this->assertSame('YES', $_GET['add']);
        $this->assertSame('Echt', $_POST['title']);
    }

    #[Test]
    public function plain_page_views_are_not_affected(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = ['page' => 'news', 'subpage' => 'search', 'search' => 'YES', 'searchstring' => 'x'];

        $this->assertFalse(pnadmin_guard_request());
        $this->assertSame('YES', $_GET['search']);
    }

    #[Test]
    public function token_is_bound_to_the_admin_session(): void
    {
        global $pnadminsession;

        $pnadminsession = [5, str_repeat('a', 64)];
        $token = pnadmin_csrf_token();
        $this->assertTrue(pnadmin_csrf_verify($token));

        $pnadminsession = [5, str_repeat('b', 64)];
        $this->assertFalse(pnadmin_csrf_verify($token), 'Token einer fremden Sitzung gilt nicht.');
        $this->assertFalse(pnadmin_csrf_verify(''));
        $this->assertFalse(pnadmin_csrf_verify(null));
    }

    #[Test]
    public function logout_button_is_a_post_form(): void
    {
        $index = (string) file_get_contents(__DIR__ . '/../../pnadmin/index.php');

        $this->assertStringContainsString('<form action="index.php?pnlogout=YES" method="post"', $index);
        $this->assertStringNotContainsString('href="index.php?pnlogout=YES"', $index);
    }
}
