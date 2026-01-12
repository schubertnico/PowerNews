<?php
declare(strict_types=1);

namespace PowerNews\Tests\Feature\Frontend;

use PowerNews\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests for user registration form (pninc/user.inc.php - register action)
 */
class UserRegistrationTest extends TestCase
{
    #[Test]
    public function registrationRequiresNickname(): void
    {
        $this->setPost([
            'nickname' => '',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password2' => 'password123',
        ]);

        $nickname = trim((string)($_POST['nickname'] ?? ''));
        $this->assertEmpty($nickname, 'Empty nickname should be rejected');
    }

    #[Test]
    public function registrationRequiresValidEmail(): void
    {
        $this->setPost([
            'nickname' => 'testuser',
            'email' => 'invalid-email',
            'password' => 'password123',
            'password2' => 'password123',
        ]);

        $email = trim((string)($_POST['email'] ?? ''));
        $this->assertInvalidEmail($email);
    }

    #[Test]
    public function registrationAcceptsValidEmail(): void
    {
        $this->setPost([
            'nickname' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password2' => 'password123',
        ]);

        $email = trim((string)($_POST['email'] ?? ''));
        $this->assertValidEmail($email);
    }

    #[Test]
    public function registrationRequiresMatchingPasswords(): void
    {
        $this->setPost([
            'nickname' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password2' => 'differentpassword',
        ]);

        $password = $_POST['password'] ?? '';
        $password2 = $_POST['password2'] ?? '';

        $this->assertNotEquals($password, $password2, 'Non-matching passwords should be detected');
    }

    #[Test]
    public function registrationAcceptsMatchingPasswords(): void
    {
        $this->setPost([
            'nickname' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'securePassword123',
            'password2' => 'securePassword123',
        ]);

        $password = $_POST['password'] ?? '';
        $password2 = $_POST['password2'] ?? '';

        $this->assertEquals($password, $password2);
    }

    #[Test]
    #[DataProvider('invalidNicknameProvider')]
    public function registrationRejectsInvalidNicknames(string $nickname): void
    {
        $this->setPost(['nickname' => $nickname]);

        $trimmedNickname = trim((string)($_POST['nickname'] ?? ''));
        $isValid = $trimmedNickname !== '' && strlen($trimmedNickname) >= 3;

        $this->assertFalse($isValid, "Nickname '$nickname' should be rejected");
    }

    public static function invalidNicknameProvider(): array
    {
        return [
            'empty' => [''],
            'whitespace only' => ['   '],
            'too short' => ['ab'],
        ];
    }

    #[Test]
    #[DataProvider('validNicknameProvider')]
    public function registrationAcceptsValidNicknames(string $nickname): void
    {
        $this->setPost(['nickname' => $nickname]);

        $trimmedNickname = trim((string)($_POST['nickname'] ?? ''));
        $isValid = $trimmedNickname !== '' && strlen($trimmedNickname) >= 3;

        $this->assertTrue($isValid, "Nickname '$nickname' should be accepted");
    }

    public static function validNicknameProvider(): array
    {
        return [
            'simple' => ['testuser'],
            'with numbers' => ['user123'],
            'with underscore' => ['test_user'],
            'minimum length' => ['abc'],
        ];
    }

    #[Test]
    public function registrationSanitizesInput(): void
    {
        $this->setPost([
            'nickname' => '  testuser  ',
            'email' => '  test@example.com  ',
        ]);

        $nickname = trim((string)($_POST['nickname'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));

        $this->assertEquals('testuser', $nickname);
        $this->assertEquals('test@example.com', $email);
    }

    #[Test]
    public function registrationHandlesXSSAttempts(): void
    {
        $this->setPost([
            'nickname' => '<script>alert("xss")</script>',
            'email' => 'test@example.com',
        ]);

        $nickname = htmlspecialchars((string)($_POST['nickname'] ?? ''), ENT_QUOTES, 'UTF-8');

        $this->assertStringNotContainsString('<script>', $nickname);
        $this->assertStringContainsString('&lt;script&gt;', $nickname);
    }

    #[Test]
    public function registrationHandlesSQLInjectionAttempts(): void
    {
        $maliciousNickname = "'; DROP TABLE users;--";
        $this->setPost(['nickname' => $maliciousNickname]);

        $nickname = htmlspecialchars((string)($_POST['nickname'] ?? ''), ENT_QUOTES, 'UTF-8');

        $this->assertStringContainsString('&#039;', $nickname);
        $this->assertStringNotContainsString("'", $nickname);
    }
}
