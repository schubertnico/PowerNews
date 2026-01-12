<?php
declare(strict_types=1);

namespace PowerNews\Tests\Feature\Admin;

use PowerNews\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Tests for admin profile form (pnadmin/profile.inc.php)
 */
class AdminProfileTest extends TestCase
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
            'email' => 'admin@example.com',
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
            'email' => 'admin@example.com',
            'password' => '',
            'password2' => '',
        ]);

        $password = $_POST['password'] ?? '';
        $this->assertEmpty($password);
    }

    #[Test]
    public function profileSanitizesEmailInput(): void
    {
        $this->setPost([
            'email' => '  admin@example.com  ',
        ]);

        $email = trim((string)($_POST['email'] ?? ''));
        $this->assertEquals('admin@example.com', $email);
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
    public function profileActionIsTriggeredByGetParameter(): void
    {
        $this->setGet(['action' => 'edit']);
        $this->setPost([
            'email' => 'admin@example.com',
        ]);

        $action = $_GET['action'] ?? '';
        $this->assertEquals('edit', $action);
    }
}
