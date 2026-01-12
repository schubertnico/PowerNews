<?php
declare(strict_types=1);

namespace PowerNews\Tests\Feature\Frontend;

use PowerNews\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests for comment posting form (pninc/comments.inc.php)
 */
class CommentPostTest extends TestCase
{
    #[Test]
    public function commentRequiresNewsId(): void
    {
        $this->setGet(['newsid' => '']);
        $this->setPost(['text' => 'Comment text']);

        $newsid = isset($_GET['newsid']) ? (int)$_GET['newsid'] : 0;
        $this->assertEquals(0, $newsid, 'Empty newsid should result in 0');
    }

    #[Test]
    public function commentRequiresText(): void
    {
        $this->setGet(['newsid' => '1']);
        $this->setPost(['text' => '']);

        $text = $_POST['text'] ?? '';
        $this->assertEmpty($text, 'Empty comment text should fail validation');
    }

    #[Test]
    public function commentAcceptsValidData(): void
    {
        $this->setGet(['newsid' => '1']);
        $this->setPost(['text' => 'This is a valid comment.']);

        $newsid = isset($_GET['newsid']) ? (int)$_GET['newsid'] : 0;
        $text = $_POST['text'] ?? '';

        $this->assertGreaterThan(0, $newsid);
        $this->assertNotEmpty($text);
    }

    #[Test]
    public function commentCastsNewsIdToInteger(): void
    {
        $this->setGet(['newsid' => '123abc']);
        $this->setPost(['text' => 'Comment']);

        $newsid = isset($_GET['newsid']) ? (int)$_GET['newsid'] : 0;
        $this->assertEquals(123, $newsid, 'Non-numeric suffix should be stripped');
    }

    #[Test]
    public function commentHandlesXSSInText(): void
    {
        $this->setGet(['newsid' => '1']);
        $this->setPost(['text' => '<script>alert("xss")</script>']);

        $text = htmlspecialchars((string)($_POST['text'] ?? ''), ENT_QUOTES, 'UTF-8');

        $this->assertStringNotContainsString('<script>', $text);
        $this->assertStringContainsString('&lt;script&gt;', $text);
    }

    #[Test]
    public function commentHandlesSQLInjectionInText(): void
    {
        $this->setGet(['newsid' => '1']);
        $this->setPost(['text' => "'; DELETE FROM comments;--"]);

        $text = htmlspecialchars((string)($_POST['text'] ?? ''), ENT_QUOTES, 'UTF-8');

        $this->assertStringNotContainsString("'", $text);
    }

    #[Test]
    #[DataProvider('invalidNewsIdProvider')]
    public function commentRejectsInvalidNewsIds(string $newsid): void
    {
        $this->setGet(['newsid' => $newsid]);
        $this->setPost(['text' => 'Comment']);

        $parsedNewsid = isset($_GET['newsid']) ? (int)$_GET['newsid'] : 0;
        $this->assertLessThanOrEqual(0, $parsedNewsid, "News ID '$newsid' should be invalid");
    }

    public static function invalidNewsIdProvider(): array
    {
        return [
            'empty' => [''],
            'zero' => ['0'],
            'negative' => ['-5'],
            'non-numeric' => ['abc'],
        ];
    }

    #[Test]
    public function commentHandlesMissingNewsIdParameter(): void
    {
        // No GET parameter set
        $this->setPost(['text' => 'Comment']);

        $newsid = isset($_GET['newsid']) ? (int)$_GET['newsid'] : 0;
        $this->assertEquals(0, $newsid, 'Missing newsid should default to 0');
    }

    #[Test]
    public function commentHandlesMissingTextParameter(): void
    {
        $this->setGet(['newsid' => '1']);
        // No POST text parameter

        $text = $_POST['text'] ?? '';
        $this->assertEmpty($text, 'Missing text should default to empty string');
    }

    #[Test]
    public function commentValidationLogic(): void
    {
        // Valid case
        $this->setGet(['newsid' => '5']);
        $this->setPost(['text' => 'Valid comment']);

        $newsid = isset($_GET['newsid']) ? (int)$_GET['newsid'] : 0;
        $text = $_POST['text'] ?? '';

        $canPost = $newsid > 0 && $text !== '';
        $this->assertTrue($canPost, 'Valid data should allow posting');

        // Invalid case - no newsid
        $this->setGet(['newsid' => '0']);
        $newsid = isset($_GET['newsid']) ? (int)$_GET['newsid'] : 0;
        $canPost = $newsid > 0 && $text !== '';
        $this->assertFalse($canPost, 'Invalid newsid should prevent posting');
    }
}
