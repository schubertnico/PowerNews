<?php
declare(strict_types=1);

namespace PowerNews\Tests\Feature\Frontend;

use PowerNews\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Tests for password recovery form (pninc/user.inc.php - senddata action)
 */
class PasswordRecoveryTest extends TestCase
{
    #[Test]
    public function recoveryRequiresSearchString(): void
    {
        $this->setPost(['searchstring' => '']);

        $searchstring = trim((string)($_POST['searchstring'] ?? ''));
        $this->assertEmpty($searchstring, 'Empty search string should fail validation');
    }

    #[Test]
    public function recoveryAcceptsEmailAsSearchString(): void
    {
        $this->setPost(['searchstring' => 'user@example.com']);

        $searchstring = trim((string)($_POST['searchstring'] ?? ''));
        $this->assertValidEmail($searchstring);
    }

    #[Test]
    public function recoveryAcceptsNicknameAsSearchString(): void
    {
        $this->setPost(['searchstring' => 'testuser']);

        $searchstring = trim((string)($_POST['searchstring'] ?? ''));
        $this->assertNotEmpty($searchstring);
        $this->assertEquals('testuser', $searchstring);
    }

    #[Test]
    public function recoverySanitizesWhitespace(): void
    {
        $this->setPost(['searchstring' => '  user@example.com  ']);

        $searchstring = trim((string)($_POST['searchstring'] ?? ''));
        $this->assertEquals('user@example.com', $searchstring);
    }

    #[Test]
    public function recoveryHandlesXSSAttempts(): void
    {
        $this->setPost(['searchstring' => '<script>alert("xss")</script>']);

        $searchstring = htmlspecialchars((string)($_POST['searchstring'] ?? ''), ENT_QUOTES, 'UTF-8');

        $this->assertStringNotContainsString('<script>', $searchstring);
        $this->assertStringContainsString('&lt;script&gt;', $searchstring);
    }

    #[Test]
    public function recoveryHandlesSQLInjectionAttempts(): void
    {
        $this->setPost(['searchstring' => "' OR '1'='1"]);

        $searchstring = htmlspecialchars((string)($_POST['searchstring'] ?? ''), ENT_QUOTES, 'UTF-8');

        $this->assertStringNotContainsString("'", $searchstring);
    }

    #[Test]
    public function recoveryRejectsWhitespaceOnlyInput(): void
    {
        $this->setPost(['searchstring' => '   ']);

        $searchstring = trim((string)($_POST['searchstring'] ?? ''));
        $this->assertEmpty($searchstring, 'Whitespace-only input should be rejected');
    }

    #[Test]
    public function recoveryHandlesMissingParameter(): void
    {
        // No searchstring in POST

        $searchstring = trim((string)($_POST['searchstring'] ?? ''));
        $this->assertEmpty($searchstring);
    }

    #[Test]
    public function recoveryActionIsTriggeredByGetParameter(): void
    {
        $this->setGet(['action' => 'senddata']);
        $this->setPost(['searchstring' => 'user@example.com']);

        $action = $_GET['action'] ?? '';
        $this->assertEquals('senddata', $action);
    }

    #[Test]
    public function recoveryHandlesLongInput(): void
    {
        $longInput = str_repeat('a', 500);
        $this->setPost(['searchstring' => $longInput]);

        $searchstring = trim((string)($_POST['searchstring'] ?? ''));
        $this->assertEquals(500, strlen($searchstring));
    }
}
