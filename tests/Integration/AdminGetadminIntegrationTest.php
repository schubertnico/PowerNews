<?php

declare(strict_types=1);

namespace PowerNews\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use PowerNews\Tests\DatabaseTestCase;

class AdminGetadminIntegrationTest extends DatabaseTestCase
{
    private \getadmin $getadmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetDatabase();
        $this->getadmin = new \getadmin();
    }

    // ── getuserdata ──

    #[Test]
    public function getuserdata_valid_session_token_returns_loggedin_yes(): void
    {
        global $pn_handler;

        $userId = $this->insertTestUser('validuser', 'valid@test.com', 'testpass123');
        $token = pn_session_create($pn_handler, $userId, 'admin');

        $userData = $this->getadmin->getuserdata($userId, $token);

        $this->assertSame('YES', $userData['loggedin']);
        $this->assertSame('validuser', $userData['nickname']);
        $this->assertSame('valid@test.com', $userData['email']);
    }

    #[Test]
    public function getuserdata_rejects_stored_password_hash_as_session_proof(): void
    {
        // B25: Der Passwort-Hash aus der Datenbank (Backup, Dump) ist kein Sitzungsnachweis.
        $userId = $this->insertTestUser('hashuser', 'hash@test.com', 'testpass123');
        $storedHash = (new \profile())->getdata($userId)['password'];

        $userData = $this->getadmin->getuserdata($userId, $storedHash);

        $this->assertSame('NO', $userData['loggedin']);
    }

    #[Test]
    public function getuserdata_valid_user_wrong_password_returns_loggedin_no(): void
    {
        $userId = $this->insertTestUser('wrongpwuser', 'wrong@test.com', 'realpass');

        $userData = $this->getadmin->getuserdata($userId, 'totally_wrong_hash');

        $this->assertSame('NO', $userData['loggedin']);
        // User data is still returned even with wrong password
        $this->assertSame('wrongpwuser', $userData['nickname']);
    }

    #[Test]
    public function getuserdata_nonexistent_user_returns_loggedin_no(): void
    {
        $userData = $this->getadmin->getuserdata(99999, 'anypassword');

        $this->assertSame('NO', $userData['loggedin']);
        $this->assertArrayNotHasKey('nickname', $userData);
    }

    #[Test]
    public function getuserdata_returns_user_array_with_all_fields(): void
    {
        global $pn_handler;

        $userId = $this->insertTestUser('fulluser', 'full@test.com', 'pass');
        $token = pn_session_create($pn_handler, $userId, 'admin');

        $userData = $this->getadmin->getuserdata($userId, $token);

        $this->assertSame('YES', $userData['loggedin']);
        $this->assertArrayHasKey('id', $userData);
        $this->assertArrayHasKey('nickname', $userData);
        $this->assertArrayHasKey('email', $userData);
        $this->assertArrayHasKey('password', $userData);
    }

    // ── getpermissions ──

    #[Test]
    public function getpermissions_user_with_permissions_returns_loggedin_yes(): void
    {
        $userId = $this->insertTestUser('permadmin', 'perm@test.com', 'pass');
        $this->insertTestPermissions($userId, 'YES');

        $perms = $this->getadmin->getpermissions($userId);

        $this->assertSame('YES', $perms['loggedin']);
        $this->assertArrayHasKey('userid', $perms);
        $this->assertSame($userId, (int) $perms['userid']);
    }

    #[Test]
    public function getpermissions_user_without_permissions_returns_loggedin_no(): void
    {
        $userId = $this->insertTestUser('nopermadmin', 'noperm@test.com', 'pass');

        $perms = $this->getadmin->getpermissions($userId);

        $this->assertSame('NO', $perms['loggedin']);
        $this->assertArrayNotHasKey('userid', $perms);
    }

    #[Test]
    public function getpermissions_nonexistent_user_returns_loggedin_no(): void
    {
        $perms = $this->getadmin->getpermissions(99999);

        $this->assertSame('NO', $perms['loggedin']);
    }

    #[Test]
    public function getpermissions_returns_all_permission_fields(): void
    {
        $userId = $this->insertTestUser('allperms', 'allperms@test.com', 'pass');
        $this->insertTestPermissions($userId, 'YES');

        $perms = $this->getadmin->getpermissions($userId);

        $this->assertSame('YES', $perms['loggedin']);
        $this->assertSame('YES', $perms['canreadtemplates']);
        $this->assertSame('YES', $perms['canwritetemplates']);
        $this->assertSame('YES', $perms['canreadconfig']);
        $this->assertSame('YES', $perms['canwriteconfig']);
        $this->assertSame('YES', $perms['canreadusers']);
        $this->assertSame('YES', $perms['canwriteusers']);
        $this->assertSame('YES', $perms['canreadpermissions']);
        $this->assertSame('YES', $perms['canwritepermissions']);
        $this->assertSame('YES', $perms['canreadcategories']);
        $this->assertSame('YES', $perms['canwritecategories']);
        $this->assertSame('YES', $perms['canreadnews']);
        $this->assertSame('YES', $perms['canwritenews']);
        $this->assertSame('YES', $perms['canreadcomments']);
        $this->assertSame('YES', $perms['canwritecomments']);
    }

    #[Test]
    public function getpermissions_with_partial_permissions(): void
    {
        $userId = $this->insertTestUser('partialperms', 'partial@test.com', 'pass');
        $this->insertTestPermissions($userId, 'NO');

        $perms = $this->getadmin->getpermissions($userId);

        $this->assertSame('YES', $perms['loggedin']);
        $this->assertSame('NO', $perms['canreadtemplates']);
        $this->assertSame('NO', $perms['canwritetemplates']);
    }
}
