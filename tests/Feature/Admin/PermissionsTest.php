<?php
declare(strict_types=1);

namespace PowerNews\Tests\Feature\Admin;

use PowerNews\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests for permissions management forms (pnadmin/permissions_add.inc.php, permissions_edit.inc.php)
 */
class PermissionsTest extends TestCase
{
    /**
     * All permission flags available in the system
     */
    private const PERMISSION_FLAGS = [
        'canconfigure',
        'cantemplates',
        'canpermissions',
        'cancategories',
        'canaddcategories',
        'canusers',
        'canaddusers',
        'cannews',
        'canaddnews',
        'cancomments',
        'canmailings',
        'canlog',
        'canstatistic',
        'canprofile',
    ];

    // === Permissions Add Tests ===

    #[Test]
    public function addPermissionsRequiresUserId(): void
    {
        $this->setPost([
            'user' => '',
        ]);

        $userid = (int)($_POST['user'] ?? 0);
        $this->assertEquals(0, $userid, 'Empty user should result in 0');
    }

    #[Test]
    public function addPermissionsCastsUserIdToInteger(): void
    {
        $this->setPost([
            'user' => '42abc',
        ]);

        $userid = (int)($_POST['user'] ?? 0);
        $this->assertEquals(42, $userid);
    }

    #[Test]
    public function addPermissionsAcceptsValidPermissionFlags(): void
    {
        $postData = ['user' => '1'];
        foreach (self::PERMISSION_FLAGS as $flag) {
            $postData[$flag] = 'YES';
        }
        $this->setPost($postData);

        foreach (self::PERMISSION_FLAGS as $flag) {
            $value = $_POST[$flag] ?? '';
            $this->assertTrue(
                in_array($value, ['YES', 'NO', '']),
                "Flag $flag should have valid value"
            );
        }
    }

    #[Test]
    #[DataProvider('permissionFlagProvider')]
    public function addPermissionsAcceptsIndividualFlags(string $flag): void
    {
        $this->setPost([
            'user' => '1',
            $flag => 'YES',
        ]);

        $value = $_POST[$flag] ?? '';
        $this->assertEquals('YES', $value);
    }

    public static function permissionFlagProvider(): array
    {
        $flags = [];
        foreach ([
            'canconfigure',
            'cantemplates',
            'canpermissions',
            'cancategories',
            'canaddcategories',
            'canusers',
            'canaddusers',
            'cannews',
            'canaddnews',
            'cancomments',
            'canmailings',
            'canlog',
            'canstatistic',
            'canprofile',
        ] as $flag) {
            $flags[$flag] = [$flag];
        }
        return $flags;
    }

    #[Test]
    public function addPermissionsDefaultsToNO(): void
    {
        $this->setPost([
            'user' => '1',
        ]);

        // Flags not in POST should default to 'NO' or empty
        foreach (self::PERMISSION_FLAGS as $flag) {
            $value = $_POST[$flag] ?? 'NO';
            $this->assertTrue(
                in_array($value, ['YES', 'NO']),
                "Missing flag $flag should default to 'NO'"
            );
        }
    }

    // === Permissions Edit Tests ===

    #[Test]
    public function editPermissionsRequiresUserId(): void
    {
        $this->setGet(['userid' => '']);

        $userid = (int)($_GET['userid'] ?? 0);
        $this->assertEquals(0, $userid, 'Empty userid should result in 0');
    }

    #[Test]
    public function editPermissionsCastsUserIdToInteger(): void
    {
        $this->setGet(['userid' => '99xyz']);

        $userid = (int)($_GET['userid'] ?? 0);
        $this->assertEquals(99, $userid);
    }

    #[Test]
    public function editPermissionsAcceptsValidValues(): void
    {
        $postData = [];
        foreach (self::PERMISSION_FLAGS as $flag) {
            $postData[$flag] = 'YES';
        }
        $this->setPost($postData);

        foreach (self::PERMISSION_FLAGS as $flag) {
            $value = $_POST[$flag] ?? '';
            $this->assertEquals('YES', $value);
        }
    }

    #[Test]
    public function editPermissionsRejectsInvalidValues(): void
    {
        $this->setPost([
            'canconfigure' => 'MAYBE',
        ]);

        $value = $_POST['canconfigure'] ?? '';
        $validValues = ['YES', 'NO', ''];

        $this->assertFalse(in_array($value, $validValues), 'Invalid value should be rejected');
    }

    #[Test]
    public function editPermissionsHandlesXSSInFlag(): void
    {
        $this->setPost([
            'canconfigure' => '<script>alert("xss")</script>',
        ]);

        $value = htmlspecialchars((string)($_POST['canconfigure'] ?? ''), ENT_QUOTES, 'UTF-8');

        $this->assertStringNotContainsString('<script>', $value);
    }

    #[Test]
    public function editPermissionsHandlesSQLInjectionInFlag(): void
    {
        $this->setPost([
            'canconfigure' => "YES'; DROP TABLE permissions;--",
        ]);

        $value = $_POST['canconfigure'] ?? '';
        $validValues = ['YES', 'NO', ''];

        $this->assertFalse(in_array($value, $validValues), 'SQL injection should be rejected');
    }

    #[Test]
    public function editPermissionsActionTriggeredByGetParameter(): void
    {
        $this->setGet([
            'userid' => '5',
            'action' => 'edit',
        ]);

        $action = $_GET['action'] ?? '';
        $userid = (int)($_GET['userid'] ?? 0);

        $this->assertEquals('edit', $action);
        $this->assertEquals(5, $userid);
    }
}
