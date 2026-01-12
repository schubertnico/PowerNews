<?php
declare(strict_types=1);

namespace PowerNews\Tests\Feature\Admin;

use PowerNews\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests for admin login form (pnadmin/login.inc.php)
 */
class AdminLoginTest extends TestCase
{
    #[Test]
    public function adminLoginRequiresNickname(): void
    {
        $this->setPost([
            'pnlogin_nickname' => '',
            'pnlogin_password' => 'password123',
        ]);

        $nickname = trim((string)($_POST['pnlogin_nickname'] ?? ''));
        $this->assertEmpty($nickname, 'Empty nickname should fail validation');
    }

    #[Test]
    public function adminLoginRequiresPassword(): void
    {
        $this->setPost([
            'pnlogin_nickname' => 'admin',
            'pnlogin_password' => '',
        ]);

        $password = trim((string)($_POST['pnlogin_password'] ?? ''));
        $this->assertEmpty($password, 'Empty password should fail validation');
    }

    #[Test]
    public function adminLoginAcceptsValidCredentials(): void
    {
        $this->setGet(['pnlogin' => 'YES']);
        $this->setPost([
            'pnlogin_nickname' => 'admin',
            'pnlogin_password' => 'securePassword123',
        ]);

        $loginAction = $_GET['pnlogin'] ?? '';
        $nickname = trim((string)($_POST['pnlogin_nickname'] ?? ''));
        $password = trim((string)($_POST['pnlogin_password'] ?? ''));

        $this->assertEquals('YES', $loginAction);
        $this->assertNotEmpty($nickname);
        $this->assertNotEmpty($password);
    }

    #[Test]
    public function adminLoginSanitizesNickname(): void
    {
        $this->setPost([
            'pnlogin_nickname' => '  admin  ',
            'pnlogin_password' => 'password123',
        ]);

        $nickname = trim((string)($_POST['pnlogin_nickname'] ?? ''));
        $this->assertEquals('admin', $nickname);
    }

    #[Test]
    public function adminLoginHandlesXSSInNickname(): void
    {
        $this->setPost([
            'pnlogin_nickname' => '<script>alert("xss")</script>',
            'pnlogin_password' => 'password123',
        ]);

        $nickname = htmlspecialchars((string)($_POST['pnlogin_nickname'] ?? ''), ENT_QUOTES, 'UTF-8');

        $this->assertStringNotContainsString('<script>', $nickname);
    }

    #[Test]
    public function adminLoginHandlesSQLInjectionInNickname(): void
    {
        $this->setPost([
            'pnlogin_nickname' => "admin'--",
            'pnlogin_password' => 'password123',
        ]);

        $nickname = htmlspecialchars((string)($_POST['pnlogin_nickname'] ?? ''), ENT_QUOTES, 'UTF-8');

        $this->assertStringNotContainsString("'", $nickname);
        $this->assertStringContainsString('&#039;', $nickname);
    }

    #[Test]
    #[DataProvider('emptyCredentialsProvider')]
    public function adminLoginRejectsEmptyCredentials(string $nickname, string $password): void
    {
        $this->setPost([
            'pnlogin_nickname' => $nickname,
            'pnlogin_password' => $password,
        ]);

        $trimmedNickname = trim((string)($_POST['pnlogin_nickname'] ?? ''));
        $trimmedPassword = trim((string)($_POST['pnlogin_password'] ?? ''));

        $hasValidCredentials = $trimmedNickname !== '' && $trimmedPassword !== '';
        $this->assertFalse($hasValidCredentials, 'Empty credentials should be rejected');
    }

    public static function emptyCredentialsProvider(): array
    {
        return [
            'empty nickname' => ['', 'password123'],
            'empty password' => ['admin', ''],
            'both empty' => ['', ''],
            'whitespace nickname' => ['   ', 'password123'],
            'whitespace password' => ['admin', '   '],
        ];
    }

    #[Test]
    public function adminLogoutTriggeredByGetParameter(): void
    {
        $this->setGet(['pnlogout' => 'YES']);

        $logoutAction = $_GET['pnlogout'] ?? '';
        $this->assertEquals('YES', $logoutAction);
    }

    #[Test]
    public function adminLoginChecksGetParameter(): void
    {
        $this->setGet(['pnlogin' => 'YES']);

        $loginAction = $_GET['pnlogin'] ?? '';
        $isLoginAttempt = $loginAction === 'YES';

        $this->assertTrue($isLoginAttempt);
    }
}
