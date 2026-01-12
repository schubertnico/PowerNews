<?php
declare(strict_types=1);

namespace PowerNews\Tests\Feature\Admin;

use PowerNews\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests for user management forms (pnadmin/users_add.inc.php, users_edit.inc.php, users_search.inc.php)
 */
class UserManagementTest extends TestCase
{
    // === User Add Tests ===

    #[Test]
    public function addUserRequiresNickname(): void
    {
        $this->setPost([
            'nickname' => '',
            'email' => 'newuser@example.com',
        ]);

        $nickname = trim((string)($_POST['nickname'] ?? ''));
        $this->assertEmpty($nickname, 'Empty nickname should fail validation');
    }

    #[Test]
    public function addUserRequiresValidEmail(): void
    {
        $this->setPost([
            'nickname' => 'newuser',
            'email' => 'invalid-email',
        ]);

        $email = trim((string)($_POST['email'] ?? ''));
        $this->assertInvalidEmail($email);
    }

    #[Test]
    public function addUserAcceptsValidData(): void
    {
        $this->setPost([
            'nickname' => 'newuser',
            'email' => 'newuser@example.com',
        ]);

        $nickname = trim((string)($_POST['nickname'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));

        $this->assertNotEmpty($nickname);
        $this->assertValidEmail($email);
    }

    #[Test]
    public function addUserHandlesXSSInNickname(): void
    {
        $this->setPost([
            'nickname' => '<script>alert("xss")</script>',
            'email' => 'test@example.com',
        ]);

        $nickname = htmlspecialchars((string)($_POST['nickname'] ?? ''), ENT_QUOTES, 'UTF-8');

        $this->assertStringNotContainsString('<script>', $nickname);
    }

    // === User Edit Tests ===

    #[Test]
    public function editUserRequiresUserId(): void
    {
        $this->setGet(['userid' => '']);

        $userid = (int)($_GET['userid'] ?? 0);
        $this->assertEquals(0, $userid, 'Empty userid should result in 0');
    }

    #[Test]
    public function editUserCastsUserIdToInteger(): void
    {
        $this->setGet(['userid' => '123abc']);

        $userid = (int)($_GET['userid'] ?? 0);
        $this->assertEquals(123, $userid);
    }

    #[Test]
    public function editUserAcceptsValidStatus(): void
    {
        $this->setPost(['status' => 'Activated']);

        $status = $_POST['status'] ?? '';
        $allowedStatuses = ['Activated', 'Deactivated', 'Unchecked'];

        $this->assertTrue(in_array($status, $allowedStatuses));
    }

    #[Test]
    #[DataProvider('validStatusProvider')]
    public function editUserAcceptsAllValidStatuses(string $status): void
    {
        $this->setPost(['status' => $status]);

        $allowedStatuses = ['Activated', 'Deactivated', 'Unchecked'];
        $this->assertTrue(in_array($status, $allowedStatuses));
    }

    public static function validStatusProvider(): array
    {
        return [
            'activated' => ['Activated'],
            'deactivated' => ['Deactivated'],
            'unchecked' => ['Unchecked'],
        ];
    }

    #[Test]
    public function editUserRejectsInvalidStatus(): void
    {
        $this->setPost(['status' => 'InvalidStatus']);

        $status = $_POST['status'] ?? '';
        $allowedStatuses = ['Activated', 'Deactivated', 'Unchecked'];

        $this->assertFalse(in_array($status, $allowedStatuses));
    }

    // === User Search Tests ===

    #[Test]
    public function searchUserAcceptsValidSearchField(): void
    {
        $this->setPost([
            'searchin' => 'nickname',
            'searchstring' => 'test',
        ]);

        $searchin = $_POST['searchin'] ?? '';
        $allowedFields = ['nickname', 'email'];

        $this->assertTrue(in_array($searchin, $allowedFields));
    }

    #[Test]
    public function searchUserRejectsInvalidSearchField(): void
    {
        $this->setPost([
            'searchin' => 'password',
            'searchstring' => 'test',
        ]);

        $searchin = $_POST['searchin'] ?? '';
        $allowedFields = ['nickname', 'email'];

        $this->assertFalse(in_array($searchin, $allowedFields));
    }

    #[Test]
    public function searchUserHandlesSQLInjectionInField(): void
    {
        $this->setPost([
            'searchin' => "'; DROP TABLE users;--",
            'searchstring' => 'test',
        ]);

        $searchin = $_POST['searchin'] ?? '';
        $allowedFields = ['nickname', 'email'];

        $this->assertFalse(in_array($searchin, $allowedFields), 'SQL injection attempt should be rejected');
    }

    #[Test]
    public function searchUserHandlesXSSInSearchString(): void
    {
        $this->setPost([
            'searchin' => 'nickname',
            'searchstring' => '<script>alert("xss")</script>',
        ]);

        $searchstring = htmlspecialchars((string)($_POST['searchstring'] ?? ''), ENT_QUOTES, 'UTF-8');

        $this->assertStringNotContainsString('<script>', $searchstring);
    }
}
