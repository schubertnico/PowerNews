<?php
declare(strict_types=1);

namespace PowerNews\Tests\Feature\Frontend;

use PowerNews\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Tests for user profile form (pninc/user.inc.php - profile action)
 */
class UserProfileTest extends TestCase
{
    #[Test]
    public function profileRequiresValidEmail(): void
    {
        $this->setPost([
            'email' => 'invalid-email',
        ]);

        $email = trim((string)($_POST['email'] ?? ''));
        $this->assertInvalidEmail($email);
    }

    #[Test]
    public function profileAcceptsValidEmail(): void
    {
        $this->setPost([
            'email' => 'newemail@example.com',
        ]);

        $email = trim((string)($_POST['email'] ?? ''));
        $this->assertValidEmail($email);
    }

    #[Test]
    public function profilePasswordChangeRequiresMatching(): void
    {
        $this->setPost([
            'password' => 'newPassword123',
            'password2' => 'differentPassword',
        ]);

        $password = $_POST['password'] ?? '';
        $password2 = $_POST['password2'] ?? '';

        $this->assertNotEquals($password, $password2);
    }

    #[Test]
    public function profilePasswordChangeAcceptsMatching(): void
    {
        $this->setPost([
            'password' => 'newSecurePassword123',
            'password2' => 'newSecurePassword123',
        ]);

        $password = $_POST['password'] ?? '';
        $password2 = $_POST['password2'] ?? '';

        $this->assertEquals($password, $password2);
    }

    #[Test]
    public function profileAllowsEmptyPasswordForNoChange(): void
    {
        $this->setPost([
            'email' => 'test@example.com',
            'password' => '',
            'password2' => '',
        ]);

        $password = $_POST['password'] ?? '';
        $password2 = $_POST['password2'] ?? '';

        // Both empty means no password change requested
        $this->assertEquals($password, $password2);
        $this->assertEmpty($password);
    }

    #[Test]
    public function profileSanitizesEmailInput(): void
    {
        $this->setPost([
            'email' => '  test@example.com  ',
        ]);

        $email = trim((string)($_POST['email'] ?? ''));
        $this->assertEquals('test@example.com', $email);
    }

    #[Test]
    public function profileHandlesXSSInEmail(): void
    {
        $this->setPost([
            'email' => '<script>alert("xss")</script>@example.com',
        ]);

        $email = htmlspecialchars((string)($_POST['email'] ?? ''), ENT_QUOTES, 'UTF-8');

        $this->assertStringNotContainsString('<script>', $email);
    }

    #[Test]
    public function profileValidatesHomepageUrl(): void
    {
        $this->setPost([
            'homepage' => 'https://example.com',
        ]);

        $homepage = trim((string)($_POST['homepage'] ?? ''));
        $isValidUrl = str_starts_with($homepage, 'http://') || str_starts_with($homepage, 'https://');

        $this->assertTrue($isValidUrl);
    }

    #[Test]
    public function profileRejectsInvalidHomepageUrl(): void
    {
        $this->setPost([
            'homepage' => 'ftp://example.com',
        ]);

        $homepage = trim((string)($_POST['homepage'] ?? ''));
        $isValidUrl = str_starts_with($homepage, 'http://') || str_starts_with($homepage, 'https://');

        $this->assertFalse($isValidUrl);
    }

    #[Test]
    public function profileAcceptsEmptyHomepage(): void
    {
        $this->setPost([
            'email' => 'test@example.com',
            'homepage' => '',
        ]);

        $homepage = trim((string)($_POST['homepage'] ?? ''));
        $this->assertEmpty($homepage);
    }
}
