<?php

declare(strict_types=1);

namespace PowerNews\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use PowerNews\Tests\DatabaseTestCase;

class AdminPermissionsIntegrationTest extends DatabaseTestCase
{
    private \permissions $permissions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetDatabase();
        $this->permissions = new \permissions();
    }

    private function makePerms(string $value = 'YES'): \PermissionsData
    {
        return new \PermissionsData(
            canreadtemplates: $value,
            canwritetemplates: $value,
            canreadconfig: $value,
            canwriteconfig: $value,
            canreadusers: $value,
            canwriteusers: $value,
            canreadpermissions: $value,
            canwritepermissions: $value,
            canreadcategories: $value,
            canwritecategories: $value,
            canreadnews: $value,
            canwritenews: $value,
            canreadcomments: $value,
            canwritecomments: $value,
        );
    }

    // ── checkuser ──

    #[Test]
    public function checkuser_returns_user_id_for_existing_activated_user(): void
    {
        $userId = $this->insertTestUser('activeuser', 'active@example.com', 'pass', 'Activated');

        $result = $this->permissions->checkuser('activeuser');

        $this->assertSame($userId, $result);
    }

    #[Test]
    public function checkuser_returns_false_for_nonexistent_user(): void
    {
        $result = $this->permissions->checkuser('nonexistent');

        $this->assertFalse($result);
    }

    #[Test]
    public function checkuser_returns_false_for_deactivated_user(): void
    {
        $this->insertTestUser('deactivated', 'deactivated@example.com', 'pass', 'Deactivated');

        $result = $this->permissions->checkuser('deactivated');

        $this->assertFalse($result);
    }

    // ── checkadmin ──

    #[Test]
    public function checkadmin_returns_true_for_user_with_permissions(): void
    {
        $userId = $this->insertTestUser('admin', 'admin@example.com');
        $this->insertTestPermissions($userId, 'YES');

        $result = $this->permissions->checkadmin($userId);

        $this->assertTrue($result);
    }

    #[Test]
    public function checkadmin_returns_false_for_user_without_permissions(): void
    {
        $userId = $this->insertTestUser('regular', 'regular@example.com');

        $result = $this->permissions->checkadmin($userId);

        $this->assertFalse($result);
    }

    #[Test]
    public function checkadmin_returns_false_for_nonexistent_user(): void
    {
        $result = $this->permissions->checkadmin(999999);

        $this->assertFalse($result);
    }

    // ── addpermissions ──

    #[Test]
    public function addpermissions_with_valid_user_returns_empty_string(): void
    {
        $this->insertTestUser('newadmin', 'newadmin@example.com', 'pass', 'Activated');

        $result = $this->permissions->addpermissions('newadmin', $this->makePerms());

        $this->assertSame('', $result);
    }

    #[Test]
    public function addpermissions_creates_permissions_in_database(): void
    {
        $userId = $this->insertTestUser('newadmin', 'newadmin@example.com', 'pass', 'Activated');

        $this->permissions->addpermissions('newadmin', $this->makePerms('YES'));

        $this->assertTrue($this->permissions->checkadmin($userId));
    }

    #[Test]
    public function addpermissions_stores_correct_permission_values(): void
    {
        $userId = $this->insertTestUser('mixedperms', 'mixedperms@example.com', 'pass', 'Activated');

        $perms = new \PermissionsData(
            canreadtemplates: 'YES',
            canwritetemplates: 'NO',
            canreadconfig: 'YES',
            canwriteconfig: 'NO',
            canreadusers: 'YES',
            canwriteusers: 'YES',
            canreadpermissions: 'NO',
            canwritepermissions: 'NO',
            canreadcategories: 'YES',
            canwritecategories: 'NO',
            canreadnews: 'YES',
            canwritenews: 'YES',
            canreadcomments: 'NO',
            canwritecomments: 'NO',
        );

        $this->permissions->addpermissions('mixedperms', $perms);

        $data = $this->permissions->getdata($userId);
        $this->assertSame('YES', $data['canreadtemplates']);
        $this->assertSame('NO', $data['canwritetemplates']);
        $this->assertSame('YES', $data['canreadconfig']);
        $this->assertSame('NO', $data['canwriteconfig']);
    }

    #[Test]
    public function addpermissions_for_existing_admin_returns_error(): void
    {
        $userId = $this->insertTestUser('alreadyadmin', 'alreadyadmin@example.com', 'pass', 'Activated');
        $this->insertTestPermissions($userId, 'YES');

        $result = $this->permissions->addpermissions('alreadyadmin', $this->makePerms());

        $this->assertSame(L_PERM_ALREADYADMIN, $result);
    }

    #[Test]
    public function addpermissions_for_nonexistent_user_returns_error(): void
    {
        $result = $this->permissions->addpermissions('nonexistent', $this->makePerms());

        $this->assertSame(L_PERM_USERNOTEXISTING, $result);
    }

    #[Test]
    public function addpermissions_for_deactivated_user_returns_error(): void
    {
        $this->insertTestUser('inactive', 'inactive@example.com', 'pass', 'Deactivated');

        $result = $this->permissions->addpermissions('inactive', $this->makePerms());

        $this->assertSame(L_PERM_USERNOTEXISTING, $result);
    }

    // ── getdata ──

    #[Test]
    public function getdata_returns_permissions_array_for_admin_user(): void
    {
        $userId = $this->insertTestUser('getadmin', 'getadmin@example.com', 'pass', 'Activated');
        $this->insertTestPermissions($userId, 'YES');

        $data = $this->permissions->getdata($userId);

        $this->assertIsArray($data);
        $this->assertArrayHasKey('nickname', $data);
        $this->assertSame('getadmin', $data['nickname']);
        $this->assertSame('YES', $data['canreadtemplates']);
        $this->assertSame('YES', $data['canwritenews']);
    }

    #[Test]
    public function getdata_returns_null_for_non_admin_user(): void
    {
        $userId = $this->insertTestUser('noadmin', 'noadmin@example.com');

        $data = $this->permissions->getdata($userId);

        $this->assertNull($data);
    }

    #[Test]
    public function getdata_returns_null_for_nonexistent_user(): void
    {
        $data = $this->permissions->getdata(999999);

        $this->assertNull($data);
    }

    // ── editpermissions ──

    #[Test]
    public function editpermissions_updates_permissions_successfully(): void
    {
        $userId = $this->insertTestUser('editadmin', 'editadmin@example.com', 'pass', 'Activated');
        $this->insertTestPermissions($userId, 'YES');

        $newPerms = new \PermissionsData(
            canreadtemplates: 'NO',
            canwritetemplates: 'NO',
            canreadconfig: 'YES',
            canwriteconfig: 'YES',
            canreadusers: 'NO',
            canwriteusers: 'NO',
            canreadpermissions: 'YES',
            canwritepermissions: 'YES',
            canreadcategories: 'NO',
            canwritecategories: 'NO',
            canreadnews: 'YES',
            canwritenews: 'YES',
            canreadcomments: 'NO',
            canwritecomments: 'NO',
        );

        $result = $this->permissions->editpermissions($userId, $newPerms, 'NO');

        $this->assertSame('', $result);

        $data = $this->permissions->getdata($userId);
        $this->assertSame('NO', $data['canreadtemplates']);
        $this->assertSame('YES', $data['canreadconfig']);
        $this->assertSame('NO', $data['canreadusers']);
        $this->assertSame('YES', $data['canreadpermissions']);
    }

    #[Test]
    public function editpermissions_with_delete_removes_permissions(): void
    {
        $userId = $this->insertTestUser('deladmin', 'deladmin@example.com', 'pass', 'Activated');
        $this->insertTestPermissions($userId, 'YES');

        $result = $this->permissions->editpermissions($userId, $this->makePerms(), 'YES');

        $this->assertSame('', $result);
        $this->assertFalse($this->permissions->checkadmin($userId));
    }

    #[Test]
    public function editpermissions_for_non_admin_returns_error(): void
    {
        $userId = $this->insertTestUser('notadmin', 'notadmin@example.com');

        $result = $this->permissions->editpermissions($userId, $this->makePerms(), 'NO');

        $this->assertSame(L_PERM_NOADMIN, $result);
    }

    #[Test]
    public function editpermissions_for_nonexistent_user_returns_error(): void
    {
        $result = $this->permissions->editpermissions(999999, $this->makePerms(), 'NO');

        $this->assertSame(L_PERM_NOADMIN, $result);
    }

    #[Test]
    public function editpermissions_delete_then_getdata_returns_null(): void
    {
        $userId = $this->insertTestUser('delcheck', 'delcheck@example.com', 'pass', 'Activated');
        $this->insertTestPermissions($userId, 'YES');

        $this->permissions->editpermissions($userId, $this->makePerms(), 'YES');

        $this->assertNull($this->permissions->getdata($userId));
    }
}
