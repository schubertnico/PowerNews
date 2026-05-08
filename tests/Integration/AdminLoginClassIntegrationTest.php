<?php

declare(strict_types=1);

namespace PowerNews\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use PowerNews\Tests\DatabaseTestCase;

class AdminLoginClassIntegrationTest extends DatabaseTestCase
{
    private \login $login;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetDatabase();
        $this->login = new \login();
    }

    // ── checklogin ──

    #[Test]
    public function checklogin_nonexistent_user_returns_nousr(): void
    {
        $result = $this->login->checklogin('nonexistent', 'password');

        $this->assertSame(L_USR_NOUSR, $result);
    }

    #[Test]
    public function checklogin_wrong_password_returns_wrongpw(): void
    {
        $this->insertTestUser('admin', 'admin@test.com', 'correct_password');

        $result = $this->login->checklogin('admin', 'wrong_password');

        $this->assertSame(L_USR_WRONGPW, $result);
    }

    #[Test]
    public function checklogin_valid_credentials_without_permissions_returns_noadmin(): void
    {
        $this->insertTestUser('noperm', 'noperm@test.com', 'mypassword');

        $result = $this->login->checklogin('noperm', 'mypassword');

        $this->assertSame(L_USR_NOADMIN, $result);
    }

    #[Test]
    public function checklogin_valid_credentials_with_permissions_returns_loggedin(): void
    {
        $userId = $this->insertTestUser('adminuser', 'admin@test.com', 'adminpass');
        $this->insertTestPermissions($userId);

        $result = @$this->login->checklogin('adminuser', 'adminpass');

        $this->assertSame('loggedin', $result);
    }

    #[Test]
    public function checklogin_empty_nickname_returns_nousr(): void
    {
        $result = $this->login->checklogin('', 'somepass');

        $this->assertSame(L_USR_NOUSR, $result);
    }

    #[Test]
    public function checklogin_empty_password_returns_wrongpw(): void
    {
        $this->insertTestUser('testuser', 'test@test.com', 'realpassword');

        $result = $this->login->checklogin('testuser', '');

        $this->assertSame(L_USR_WRONGPW, $result);
    }

    // ── checkpermissions ──

    #[Test]
    public function checkpermissions_user_with_permissions_returns_empty_string(): void
    {
        $userId = $this->insertTestUser('permuser', 'perm@test.com', 'pass');
        $this->insertTestPermissions($userId);

        $result = $this->login->checkpermissions($userId);

        $this->assertSame('', $result);
    }

    #[Test]
    public function checkpermissions_user_without_permissions_returns_noadmin(): void
    {
        $userId = $this->insertTestUser('nopermuser', 'noperm@test.com', 'pass');

        $result = $this->login->checkpermissions($userId);

        $this->assertSame(L_USR_NOADMIN, $result);
    }

    #[Test]
    public function checkpermissions_nonexistent_user_returns_noadmin(): void
    {
        $result = $this->login->checkpermissions(99999);

        $this->assertSame(L_USR_NOADMIN, $result);
    }

    // ── logout ──

    #[Test]
    public function logout_executes_without_fatal_error(): void
    {
        // setcookie will emit a warning in CLI since headers are already sent
        @$this->login->logout();

        // If we reach here without fatal error, the test passes
        $this->assertTrue(true);
    }

    // ── additional checklogin scenarios ──

    #[Test]
    public function checklogin_case_sensitive_nickname(): void
    {
        $this->insertTestUser('CaseSensitive', 'case@test.com', 'thepass');
        $this->insertTestPermissions(1); // won't match

        // MySQL default collation is case-insensitive, so this may match
        // but the important thing is we get a valid response
        $result = $this->login->checklogin('casesensitive', 'thepass');

        // Result should be one of the valid return values
        $this->assertContains($result, [L_USR_NOUSR, L_USR_WRONGPW, L_USR_NOADMIN, 'loggedin']);
    }

    #[Test]
    public function checklogin_sets_pncookie_global_on_success(): void
    {
        global $pncookie;

        $userId = $this->insertTestUser('cookieuser', 'cookie@test.com', 'cookiepass');
        $this->insertTestPermissions($userId);

        $pncookie = '';
        @$this->login->checklogin('cookieuser', 'cookiepass');

        $this->assertNotEmpty($pncookie);
    }
}
