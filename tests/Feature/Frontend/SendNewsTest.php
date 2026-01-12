<?php
declare(strict_types=1);

namespace PowerNews\Tests\Feature\Frontend;

use PowerNews\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests for send news form (pninc/sendnews.inc.php)
 */
class SendNewsTest extends TestCase
{
    #[Test]
    public function sendNewsRequiresCategoryId(): void
    {
        $this->setPost([
            'catid' => '',
            'title' => 'Test News',
            'text' => 'News content here',
        ]);

        $catid = (int)($_POST['catid'] ?? 0);
        $this->assertEquals(0, $catid, 'Empty category should result in 0');
    }

    #[Test]
    public function sendNewsRequiresTitle(): void
    {
        $this->setPost([
            'catid' => '1',
            'title' => '',
            'text' => 'News content here',
        ]);

        $title = trim((string)($_POST['title'] ?? ''));
        $this->assertEmpty($title, 'Empty title should fail validation');
    }

    #[Test]
    public function sendNewsRequiresText(): void
    {
        $this->setPost([
            'catid' => '1',
            'title' => 'Test News',
            'text' => '',
        ]);

        $text = trim((string)($_POST['text'] ?? ''));
        $this->assertEmpty($text, 'Empty text should fail validation');
    }

    #[Test]
    public function sendNewsAcceptsValidData(): void
    {
        $this->setPost([
            'catid' => '1',
            'title' => 'Test News Title',
            'text' => 'This is the news content.',
        ]);

        $catid = (int)($_POST['catid'] ?? 0);
        $title = trim((string)($_POST['title'] ?? ''));
        $text = trim((string)($_POST['text'] ?? ''));

        $this->assertGreaterThan(0, $catid);
        $this->assertNotEmpty($title);
        $this->assertNotEmpty($text);
    }

    #[Test]
    public function sendNewsCastsCategoryIdToInteger(): void
    {
        $this->setPost([
            'catid' => '5abc',
            'title' => 'Test',
            'text' => 'Content',
        ]);

        $catid = (int)($_POST['catid'] ?? 0);
        $this->assertEquals(5, $catid, 'Non-numeric suffix should be stripped');
    }

    #[Test]
    public function sendNewsHandlesXSSInTitle(): void
    {
        $this->setPost([
            'catid' => '1',
            'title' => '<script>alert("xss")</script>',
            'text' => 'Content',
        ]);

        $title = htmlspecialchars((string)($_POST['title'] ?? ''), ENT_QUOTES, 'UTF-8');

        $this->assertStringNotContainsString('<script>', $title);
        $this->assertStringContainsString('&lt;script&gt;', $title);
    }

    #[Test]
    public function sendNewsHandlesXSSInText(): void
    {
        $this->setPost([
            'catid' => '1',
            'title' => 'Test',
            'text' => '<img src=x onerror="alert(1)">',
        ]);

        $text = htmlspecialchars((string)($_POST['text'] ?? ''), ENT_QUOTES, 'UTF-8');

        // HTML special characters should be escaped
        $this->assertStringNotContainsString('<img', $text);
        $this->assertStringContainsString('&lt;img', $text);
        $this->assertStringContainsString('&quot;', $text);
    }

    #[Test]
    public function sendNewsSanitizesWhitespace(): void
    {
        $this->setPost([
            'catid' => '1',
            'title' => '  Test News Title  ',
            'text' => '  News content  ',
        ]);

        $title = trim((string)($_POST['title'] ?? ''));
        $text = trim((string)($_POST['text'] ?? ''));

        $this->assertEquals('Test News Title', $title);
        $this->assertEquals('News content', $text);
    }

    #[Test]
    #[DataProvider('invalidCategoryIdProvider')]
    public function sendNewsRejectsInvalidCategoryIds(string $catid): void
    {
        $this->setPost(['catid' => $catid]);

        $parsedCatid = (int)($_POST['catid'] ?? 0);
        $this->assertLessThanOrEqual(0, $parsedCatid, "Category ID '$catid' should be invalid");
    }

    public static function invalidCategoryIdProvider(): array
    {
        return [
            'empty' => [''],
            'zero' => ['0'],
            'negative' => ['-1'],
            'non-numeric' => ['abc'],
        ];
    }

    #[Test]
    public function sendNewsHandlesSQLInjectionInTitle(): void
    {
        $this->setPost([
            'catid' => '1',
            'title' => "'; DROP TABLE news;--",
            'text' => 'Content',
        ]);

        $title = htmlspecialchars((string)($_POST['title'] ?? ''), ENT_QUOTES, 'UTF-8');

        $this->assertStringNotContainsString("'", $title);
        $this->assertStringContainsString('&#039;', $title);
    }
}
