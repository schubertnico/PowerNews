<?php
declare(strict_types=1);

namespace PowerNews\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;

/**
 * Base TestCase for integration tests that require a database connection.
 */
abstract class DatabaseTestCase extends BaseTestCase
{
    protected static ?\mysqli $dbHandler = null;
    protected static bool $schemaSetUp = false;

    protected function setUp(): void
    {
        parent::setUp();

        if (self::$dbHandler === null) {
            try {
                self::$dbHandler = pn_test_connect_db();
            } catch (\RuntimeException $e) {
                $this->markTestSkipped('Test database not available: ' . $e->getMessage());
            }
        }

        if (!self::$schemaSetUp) {
            pn_test_setup_schema(self::$dbHandler);
            self::$schemaSetUp = true;
        }

        $this->initGlobals();
        $this->clearSuperglobals();
    }

    protected function tearDown(): void
    {
        $this->clearSuperglobals();
        parent::tearDown();
    }

    protected function initGlobals(): void
    {
        global $pn_handler, $pn_config, $pnconfig, $pnuser, $pncookie;

        $pn_handler = self::$dbHandler;
        $pn_config = pn_test_setup_config();
        $pnconfig = pn_test_get_pnconfig($pn_handler, $pn_config);
        $pnuser = [
            'loggedin' => 'NO',
            'id' => 0,
            'nickname' => '',
            'email' => '',
        ];
        $pncookie = '';
    }

    protected function clearSuperglobals(): void
    {
        $_GET = [];
        $_POST = [];
        $_COOKIE = [];
        $_FILES = [];

        // Tests, die durch CSRF-geschuetzte POST-Pfade laufen, brauchen ein
        // gueltiges Token. Token vorab in $_POST und Session ablegen, damit
        // Methoden wie pn_news::postcomment, pn_user::register, etc. nicht
        // schon an pn_csrf_verify() abbrechen. Tests, die das CSRF-Verhalten
        // selbst pruefen, koennen $_POST['csrf_token'] gezielt unset()-en.
        if (function_exists('pn_csrf_token')) {
            $_POST['csrf_token'] = pn_csrf_token();
        }
    }

    /**
     * Tabellen wirklich leeren (kein Re-Seed). Fuer Tests, die einen
     * komplett leeren Zustand brauchen (z.B. "no categories"-Pfade).
     */
    protected function truncateAll(): void
    {
        global $pn_handler, $pn_config;

        $tables = [
            $pn_config['newstable'],
            $pn_config['commenttable'],
            $pn_config['cattable'],
            $pn_config['permissionstable'],
            $pn_config['usertable'],
        ];

        foreach ($tables as $table) {
            mysqli_query($pn_handler, "TRUNCATE TABLE $table");
        }
    }

    protected function setGet(array $data): void
    {
        $_GET = $data;
    }

    protected function setPost(array $data): void
    {
        $_POST = $data;
        if (function_exists('pn_csrf_token') && !isset($_POST['csrf_token'])) {
            $_POST['csrf_token'] = pn_csrf_token();
        }
    }

    protected function setCookie(array $data): void
    {
        $_COOKIE = $data;
    }

    /**
     * Truncate all tables and re-insert seed data.
     */
    protected function resetDatabase(): void
    {
        global $pn_handler, $pn_config;

        $tables = [
            $pn_config['newstable'],
            $pn_config['commenttable'],
            $pn_config['cattable'],
            $pn_config['permissionstable'],
            $pn_config['usertable'],
        ];

        foreach ($tables as $table) {
            mysqli_query($pn_handler, "TRUNCATE TABLE $table");
        }

        // Re-seed default data
        pn_test_setup_schema(self::$dbHandler);
    }

    /**
     * Insert a test user and return their ID.
     */
    protected function insertTestUser(
        string $nickname = 'testuser',
        string $email = 'test@example.com',
        string $password = 'testpass',
        string $status = 'Activated',
        string $showemail = 'NO'
    ): int {
        global $pn_handler, $pn_config;

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $now = time();

        $stmt = mysqli_prepare(
            $pn_handler,
            'INSERT INTO ' . $pn_config['usertable'] .
            ' (nickname, email, password, registered, showemail, status) VALUES(?, ?, ?, ?, ?, ?)'
        );
        mysqli_stmt_bind_param($stmt, 'sssiss', $nickname, $email, $hashedPassword, $now, $showemail, $status);
        mysqli_stmt_execute($stmt);

        return (int)mysqli_insert_id($pn_handler);
    }

    /**
     * Insert a test category and return its ID.
     */
    protected function insertTestCategory(
        string $name = 'Test Category',
        string $status = 'Activated',
        string $description = 'Test description'
    ): int {
        global $pn_handler, $pn_config;

        $stmt = mysqli_prepare(
            $pn_handler,
            'INSERT INTO ' . $pn_config['cattable'] .
            ' (name, description, picture, status) VALUES(?, ?, ?, ?)'
        );
        $pic = '';
        mysqli_stmt_bind_param($stmt, 'ssss', $name, $description, $pic, $status);
        mysqli_stmt_execute($stmt);

        return (int)mysqli_insert_id($pn_handler);
    }

    /**
     * Insert a test news article and return its ID.
     */
    protected function insertTestNews(
        int $userId = 1,
        int $catId = 0,
        string $title = 'Test News',
        string $text = 'Test news text',
        string $status = 'Activated',
        ?int $time = null
    ): int {
        global $pn_handler, $pn_config;

        $time = $time ?? time() - 3600; // 1 hour ago by default
        $moretext = '';
        $relatedlinks = '';

        $stmt = mysqli_prepare(
            $pn_handler,
            'INSERT INTO ' . $pn_config['newstable'] .
            ' (userid, time, catid, title, text, moretext, status, relatedlinks) VALUES(?, ?, ?, ?, ?, ?, ?, ?)'
        );
        mysqli_stmt_bind_param($stmt, 'iiisssss', $userId, $time, $catId, $title, $text, $moretext, $status, $relatedlinks);
        mysqli_stmt_execute($stmt);

        return (int)mysqli_insert_id($pn_handler);
    }

    /**
     * Insert a test comment and return its ID.
     */
    protected function insertTestComment(
        int $newsId,
        int $userId = 0,
        string $text = 'Test comment',
        ?int $time = null
    ): int {
        global $pn_handler, $pn_config;

        $time = $time ?? time() - 1800;
        $ip = '127.0.0.1';

        $stmt = mysqli_prepare(
            $pn_handler,
            'INSERT INTO ' . $pn_config['commenttable'] .
            ' (newsid, userid, time, text, ip) VALUES(?, ?, ?, ?, ?)'
        );
        mysqli_stmt_bind_param($stmt, 'iiiss', $newsId, $userId, $time, $text, $ip);
        mysqli_stmt_execute($stmt);

        return (int)mysqli_insert_id($pn_handler);
    }

    /**
     * Insert test permissions for a user.
     */
    protected function insertTestPermissions(int $userId, string $allPerms = 'YES'): int
    {
        global $pn_handler, $pn_config;

        $stmt = mysqli_prepare(
            $pn_handler,
            'INSERT INTO ' . $pn_config['permissionstable'] .
            ' (userid, canreadtemplates, canwritetemplates, canreadconfig, canwriteconfig, canreadusers, canwriteusers, canreadpermissions, canwritepermissions, canreadcategories, canwritecategories, canreadnews, canwritenews, canreadcomments, canwritecomments) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        mysqli_stmt_bind_param(
            $stmt,
            'issssssssssssss',
            $userId,
            $allPerms, $allPerms, $allPerms, $allPerms,
            $allPerms, $allPerms, $allPerms, $allPerms,
            $allPerms, $allPerms, $allPerms, $allPerms,
            $allPerms, $allPerms
        );
        mysqli_stmt_execute($stmt);

        return (int)mysqli_insert_id($pn_handler);
    }

    /**
     * Set up the user as logged in for testing.
     */
    protected function loginAsUser(int $userId, string $nickname = 'testuser', string $email = 'test@example.com'): void
    {
        global $pnuser;
        $pnuser = [
            'loggedin' => 'YES',
            'id' => $userId,
            'nickname' => $nickname,
            'email' => $email,
        ];
    }

    /**
     * Capture output from a callable.
     */
    protected function captureOutput(callable $fn): string
    {
        ob_start();
        $fn();
        return ob_get_clean() ?: '';
    }
}
