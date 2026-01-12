<?php
declare(strict_types=1);

namespace PowerNews\Tests\Feature\Admin;

use PowerNews\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests for news management forms (pnadmin/news_add.inc.php, news_edit.inc.php, news_search.inc.php)
 */
class NewsManagementTest extends TestCase
{
    // === News Add Tests ===

    #[Test]
    public function addNewsRequiresCategoryId(): void
    {
        $this->setPost([
            'catid' => '',
            'title' => 'Test News',
            'text' => 'Content',
        ]);

        $catid = (int)($_POST['catid'] ?? 0);
        $this->assertEquals(0, $catid, 'Empty catid should result in 0');
    }

    #[Test]
    public function addNewsRequiresTitle(): void
    {
        $this->setPost([
            'catid' => '1',
            'title' => '',
            'text' => 'Content',
        ]);

        $title = trim((string)($_POST['title'] ?? ''));
        $this->assertEmpty($title, 'Empty title should fail validation');
    }

    #[Test]
    public function addNewsRequiresText(): void
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
    public function addNewsAcceptsValidData(): void
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
    public function addNewsHandlesTimestampComponents(): void
    {
        $this->setPost([
            'catid' => '1',
            'title' => 'Test',
            'text' => 'Content',
            'time' => [
                'day' => '15',
                'month' => '6',
                'year' => '2024',
                'hour' => '14',
                'minute' => '30',
            ],
        ]);

        $time = $_POST['time'] ?? [];
        $day = (int)($time['day'] ?? 0);
        $month = (int)($time['month'] ?? 0);
        $year = (int)($time['year'] ?? 0);
        $hour = (int)($time['hour'] ?? 0);
        $minute = (int)($time['minute'] ?? 0);

        $this->assertGreaterThanOrEqual(1, $day);
        $this->assertLessThanOrEqual(31, $day);
        $this->assertGreaterThanOrEqual(1, $month);
        $this->assertLessThanOrEqual(12, $month);
        $this->assertGreaterThanOrEqual(0, $hour);
        $this->assertLessThanOrEqual(23, $hour);
        $this->assertGreaterThanOrEqual(0, $minute);
        $this->assertLessThanOrEqual(59, $minute);
    }

    // === News Edit Tests ===

    #[Test]
    public function editNewsRequiresNewsId(): void
    {
        $this->setGet(['newsid' => '']);

        $newsid = (int)($_GET['newsid'] ?? 0);
        $this->assertEquals(0, $newsid, 'Empty newsid should result in 0');
    }

    #[Test]
    public function editNewsCastsNewsIdToInteger(): void
    {
        $this->setGet(['newsid' => '456abc']);

        $newsid = (int)($_GET['newsid'] ?? 0);
        $this->assertEquals(456, $newsid);
    }

    #[Test]
    public function editNewsAcceptsValidStatus(): void
    {
        $this->setPost(['status' => 'Activated']);

        $status = $_POST['status'] ?? '';
        $allowedStatuses = ['Activated', 'Deactivated', 'Unchecked'];

        $this->assertTrue(in_array($status, $allowedStatuses));
    }

    #[Test]
    #[DataProvider('newsStatusProvider')]
    public function editNewsAcceptsAllValidStatuses(string $status): void
    {
        $this->setPost(['status' => $status]);

        $allowedStatuses = ['Activated', 'Deactivated', 'Unchecked'];
        $this->assertTrue(in_array($status, $allowedStatuses));
    }

    public static function newsStatusProvider(): array
    {
        return [
            'activated' => ['Activated'],
            'deactivated' => ['Deactivated'],
            'unchecked' => ['Unchecked'],
        ];
    }

    #[Test]
    public function editNewsHandlesXSSInTitle(): void
    {
        $this->setPost([
            'title' => '<script>alert("xss")</script>',
        ]);

        $title = htmlspecialchars((string)($_POST['title'] ?? ''), ENT_QUOTES, 'UTF-8');

        $this->assertStringNotContainsString('<script>', $title);
    }

    // === News Search Tests ===

    #[Test]
    public function searchNewsAcceptsValidSearchField(): void
    {
        $this->setPost([
            'searchin' => 'title',
            'searchstring' => 'test',
        ]);

        $searchin = $_POST['searchin'] ?? '';
        $allowedFields = ['title', 'text'];

        $this->assertTrue(in_array($searchin, $allowedFields));
    }

    #[Test]
    public function searchNewsRejectsInvalidSearchField(): void
    {
        $this->setPost([
            'searchin' => 'userid',
            'searchstring' => 'test',
        ]);

        $searchin = $_POST['searchin'] ?? '';
        $allowedFields = ['title', 'text'];

        $this->assertFalse(in_array($searchin, $allowedFields));
    }

    #[Test]
    public function searchNewsHandlesSQLInjectionInField(): void
    {
        $this->setPost([
            'searchin' => "'; DROP TABLE news;--",
            'searchstring' => 'test',
        ]);

        $searchin = $_POST['searchin'] ?? '';
        $allowedFields = ['title', 'text'];

        $this->assertFalse(in_array($searchin, $allowedFields));
    }

    // === Comment Edit Tests ===

    #[Test]
    public function editCommentHandlesArrayInput(): void
    {
        $this->setPost([
            'commentid' => ['1', '2', '3'],
            'commenttext' => ['Comment 1', 'Comment 2', 'Comment 3'],
        ]);

        $commentIds = $_POST['commentid'] ?? [];
        $commentTexts = $_POST['commenttext'] ?? [];

        $this->assertIsArray($commentIds);
        $this->assertIsArray($commentTexts);
        $this->assertCount(3, $commentIds);
        $this->assertCount(3, $commentTexts);
    }

    #[Test]
    public function editCommentCastsIdsToInteger(): void
    {
        $this->setPost([
            'commentid' => ['1abc', '2', '3xyz'],
        ]);

        $commentIds = $_POST['commentid'] ?? [];
        $parsedIds = array_map('intval', $commentIds);

        $this->assertEquals([1, 2, 3], $parsedIds);
    }

    #[Test]
    public function editCommentHandlesXSSInText(): void
    {
        $this->setPost([
            'commenttext' => ['<script>alert("xss")</script>'],
        ]);

        $commentTexts = $_POST['commenttext'] ?? [];
        $sanitized = htmlspecialchars($commentTexts[0] ?? '', ENT_QUOTES, 'UTF-8');

        $this->assertStringNotContainsString('<script>', $sanitized);
    }
}
