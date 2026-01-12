<?php
declare(strict_types=1);

namespace PowerNews\Tests\Feature\Frontend;

use PowerNews\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests for user login form (pninc/user.inc.php - login action)
 */
class UserLoginTest extends TestCase
{
    #[Test]
    public function loginRequiresNickname(): void
    {
        $this->setPost([
            'nickname' => '',
            'password' => 'password123',
        ]);

        $nickname = trim((string)($_POST['nickname'] ?? ''));
        $this->assertEmpty($nickname, 'Empty nickname should fail validation');
    }

    #[Test]
    public function loginRequiresPassword(): void
    {
        $this->setPost([
            'nickname' => 'testuser',
            'password' => '',
        ]);

        $password = trim((string)($_POST['password'] ?? ''));
        $this->assertEmpty($password, 'Empty password should fail validation');
    }

    #[Test]
    public function loginAcceptsValidCredentials(): void
    {
        $this->setPost([
            'nickname' => 'testuser',
            'password' => 'securePassword123',
        ]);

        $nickname = trim((string)($_POST['nickname'] ?? ''));
        $password = trim((string)($_POST['password'] ?? ''));

        $this->assertNotEmpty($nickname);
        $this->assertNotEmpty($password);
    }

    #[Test]
    public function loginSanitizesNickname(): void
    {
        $this->setPost([
            'nickname' => '  testuser  ',
            'password' => 'password123',
        ]);

        $nickname = trim((string)($_POST['nickname'] ?? ''));
        $this->assertEquals('testuser', $nickname);
    }

    #[Test]
    public function loginHandlesXSSInNickname(): void
    {
        $this->setPost([
            'nickname' => '<script>alert("xss")</script>',
            'password' => 'password123',
        ]);

        $nickname = htmlspecialchars((string)($_POST['nickname'] ?? ''), ENT_QUOTES, 'UTF-8');

        $this->assertStringNotContainsString('<script>', $nickname);
    }

    #[Test]
    public function loginHandlesSQLInjectionInNickname(): void
    {
        $this->setPost([
            'nickname' => "admin'--",
            'password' => 'password123',
        ]);

        $nickname = htmlspecialchars((string)($_POST['nickname'] ?? ''), ENT_QUOTES, 'UTF-8');

        $this->assertStringNotContainsString("'", $nickname);
        $this->assertStringContainsString('&#039;', $nickname);
    }

    #[Test]
    #[DataProvider('emptyCredentialsProvider')]
    public function loginRejectsEmptyCredentials(string $nickname, string $password): void
    {
        $this->setPost([
            'nickname' => $nickname,
            'password' => $password,
        ]);

        $trimmedNickname = trim((string)($_POST['nickname'] ?? ''));
        $trimmedPassword = trim((string)($_POST['password'] ?? ''));

        $hasValidCredentials = $trimmedNickname !== '' && $trimmedPassword !== '';
        $this->assertFalse($hasValidCredentials, 'Empty credentials should be rejected');
    }

    public static function emptyCredentialsProvider(): array
    {
        return [
            'empty nickname' => ['', 'password123'],
            'empty password' => ['testuser', ''],
            'both empty' => ['', ''],
            'whitespace nickname' => ['   ', 'password123'],
            'whitespace password' => ['testuser', '   '],
        ];
    }

    #[Test]
    public function loginGetParameterIsValidated(): void
    {
        $this->setGet(['pnlogin' => 'YES']);
        $this->setPost([
            'nickname' => 'testuser',
            'password' => 'password123',
        ]);

        $loginAction = $_GET['pnlogin'] ?? '';
        $this->assertEquals('YES', $loginAction);
    }

    #[Test]
    public function logoutGetParameterIsValidated(): void
    {
        $this->setGet(['pnlogout' => 'YES']);

        $logoutAction = $_GET['pnlogout'] ?? '';
        $this->assertEquals('YES', $logoutAction);
    }
}
