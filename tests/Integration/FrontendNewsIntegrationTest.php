<?php

declare(strict_types=1);

namespace PowerNews\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use PowerNews\Tests\DatabaseTestCase;

class FrontendNewsIntegrationTest extends DatabaseTestCase
{
    private \pn_news $news;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetDatabase();
        $this->news = new \pn_news();
    }

    // ── getcommentnum ──

    #[Test]
    public function getcommentnum_returns_zero_when_no_comments(): void
    {
        $userId = $this->insertTestUser('author', 'author@example.com', 'pass123', 'Activated', 'NO');
        $catId = $this->insertTestCategory('General', 'Activated', 'General news');
        $newsId = $this->insertTestNews($userId, $catId, 'Test Article', 'Body text', 'Activated', time());

        $this->assertSame(0, $this->news->getcommentnum($newsId));
    }

    #[Test]
    public function getcommentnum_returns_correct_count_with_multiple_comments(): void
    {
        $userId = $this->insertTestUser('author', 'author@example.com', 'pass123', 'Activated', 'NO');
        $catId = $this->insertTestCategory('General', 'Activated', 'General news');
        $newsId = $this->insertTestNews($userId, $catId, 'Test Article', 'Body text', 'Activated', time());

        $this->insertTestComment($newsId, $userId, 'Comment one', time());
        $this->insertTestComment($newsId, $userId, 'Comment two', time());
        $this->insertTestComment($newsId, $userId, 'Comment three', time());

        $this->assertSame(3, $this->news->getcommentnum($newsId));
    }

    // ── getauthor ──

    #[Test]
    public function getauthor_returns_linked_name_when_showemail_yes(): void
    {
        $userId = $this->insertTestUser('visible', 'visible@example.com', 'pass123', 'Activated', 'YES');

        $result = $this->news->getauthor($userId);

        $this->assertStringContainsString('mailto:visible@example.com', $result);
        $this->assertStringContainsString('visible', $result);
        $this->assertStringStartsWith('<a ', $result);
    }

    #[Test]
    public function getauthor_returns_plain_name_when_showemail_no(): void
    {
        $userId = $this->insertTestUser('hidden', 'hidden@example.com', 'pass123', 'Activated', 'NO');

        $result = $this->news->getauthor($userId);

        $this->assertStringNotContainsString('mailto:', $result);
        $this->assertStringContainsString('hidden', $result);
    }

    #[Test]
    public function getauthor_returns_unknown_for_nonexistent_user(): void
    {
        $result = $this->news->getauthor(999999);

        $this->assertSame(L_NEWS_UNKNOWN, $result);
    }

    // ── getcatname ──

    #[Test]
    public function getcatname_returns_deactivated_message_for_catid_zero(): void
    {
        $result = $this->news->getcatname(0);

        $this->assertSame(L_NEWS_CATSDEACTIVATED, $result);
    }

    #[Test]
    public function getcatname_returns_array_for_valid_category(): void
    {
        $catId = $this->insertTestCategory('Tech', 'Activated', 'Technology news');

        $result = $this->news->getcatname($catId);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('pic', $result);
        $this->assertArrayHasKey('description', $result);
        $this->assertSame('Tech', $result['name']);
    }

    #[Test]
    public function getcatname_returns_wrong_cat_for_invalid_catid(): void
    {
        $result = $this->news->getcatname(999999);

        $this->assertSame(L_NEWS_WRONGCAT, $result);
    }

    // ── headlines ──

    #[Test]
    public function headlines_outputs_no_headlines_when_no_news(): void
    {
        $catId = $this->insertTestCategory('Empty', 'Activated', 'Empty category');

        $output = $this->captureOutput(fn() => $this->news->headlines($catId));

        $this->assertStringContainsString(L_NEWS_NOHEADLINES, $output);
    }

    #[Test]
    public function headlines_outputs_all_headlines_when_catid_zero(): void
    {
        $userId = $this->insertTestUser('writer', 'writer@example.com', 'pass123', 'Activated', 'NO');
        $catId = $this->insertTestCategory('News', 'Activated', 'News category');
        $this->insertTestNews($userId, $catId, 'First Headline', 'First body', 'Activated', time());
        $this->insertTestNews($userId, $catId, 'Second Headline', 'Second body', 'Activated', time());

        $output = $this->captureOutput(fn() => $this->news->headlines(0));

        $this->assertStringContainsString('First Headline', $output);
        $this->assertStringContainsString('Second Headline', $output);
    }

    #[Test]
    public function headlines_outputs_filtered_headlines_for_specific_category(): void
    {
        $userId = $this->insertTestUser('writer', 'writer@example.com', 'pass123', 'Activated', 'NO');
        $cat1 = $this->insertTestCategory('Sports', 'Activated', 'Sports news');
        $cat2 = $this->insertTestCategory('Tech', 'Activated', 'Tech news');
        $this->insertTestNews($userId, $cat1, 'Sports Title', 'Sports body', 'Activated', time());
        $this->insertTestNews($userId, $cat2, 'Tech Title', 'Tech body', 'Activated', time());

        $output = $this->captureOutput(fn() => $this->news->headlines($cat1));

        $this->assertStringContainsString('Sports Title', $output);
        $this->assertStringNotContainsString('Tech Title', $output);
    }

    // ── news ──

    #[Test]
    public function news_outputs_no_news_when_empty(): void
    {
        $catId = $this->insertTestCategory('Empty', 'Activated', 'Empty category');

        $output = $this->captureOutput(fn() => $this->news->news($catId));

        $this->assertStringContainsString(L_NEWS_NONEWS, $output);
    }

    #[Test]
    public function news_outputs_entries_when_news_exists(): void
    {
        $userId = $this->insertTestUser('writer', 'writer@example.com', 'pass123', 'Activated', 'NO');
        $catId = $this->insertTestCategory('General', 'Activated', 'General news');
        $this->insertTestNews($userId, $catId, 'Published Article', 'Article body content', 'Activated', time());

        $output = $this->captureOutput(fn() => $this->news->news($catId));

        $this->assertStringContainsString('Published Article', $output);
    }

    // ── details ──

    #[Test]
    public function details_outputs_news_detail_for_valid_id(): void
    {
        $userId = $this->insertTestUser('writer', 'writer@example.com', 'pass123', 'Activated', 'NO');
        $catId = $this->insertTestCategory('General', 'Activated', 'General news');
        $newsId = $this->insertTestNews($userId, $catId, 'Detail Article', 'Detailed body text', 'Activated', time());

        $output = $this->captureOutput(fn() => $this->news->details($newsId));

        $this->assertStringContainsString('Detail Article', $output);
    }

    #[Test]
    public function details_outputs_choose_news_for_invalid_id(): void
    {
        $output = $this->captureOutput(fn() => $this->news->details(999999));

        $this->assertStringContainsString(L_NEWS_CHOOSENEWS, $output);
    }

    // ── comments ──

    #[Test]
    public function comments_outputs_comments_when_they_exist(): void
    {
        $userId = $this->insertTestUser('commenter', 'commenter@example.com', 'pass123', 'Activated', 'NO');
        $catId = $this->insertTestCategory('General', 'Activated', 'General news');
        $newsId = $this->insertTestNews($userId, $catId, 'Commented Article', 'Body', 'Activated', time());
        $this->insertTestComment($newsId, $userId, 'A test comment text', time());

        $output = $this->captureOutput(fn() => $this->news->comments($newsId));

        $this->assertStringContainsString('A test comment text', $output);
    }

    #[Test]
    public function comments_outputs_no_comments_when_none_exist(): void
    {
        $userId = $this->insertTestUser('writer', 'writer@example.com', 'pass123', 'Activated', 'NO');
        $catId = $this->insertTestCategory('General', 'Activated', 'General news');
        $newsId = $this->insertTestNews($userId, $catId, 'No Comments Article', 'Body', 'Activated', time());

        $output = $this->captureOutput(fn() => $this->news->comments($newsId));

        $this->assertStringContainsString(L_NEWS_NOCOMMENTS, $output);
    }

    // ── commentform ──

    #[Test]
    public function commentform_shows_form_for_logged_in_user_when_registered_only(): void
    {
        global $pnconfig;
        $pnconfig['commentwriting'] = 'Registered';

        $userId = $this->insertTestUser('member', 'member@example.com', 'pass123', 'Activated', 'NO');
        $catId = $this->insertTestCategory('General', 'Activated', 'General news');
        $newsId = $this->insertTestNews($userId, $catId, 'Form Article', 'Body', 'Activated', time());
        $this->loginAsUser($userId, 'member', 'member@example.com');

        $output = $this->captureOutput(fn() => $this->news->commentform($newsId));

        $this->assertStringContainsString('<form', $output);
        $this->assertStringContainsString('member', $output);
    }

    #[Test]
    public function commentform_denies_guest_when_registered_only(): void
    {
        global $pnconfig;
        $pnconfig['commentwriting'] = 'Registered';

        $userId = $this->insertTestUser('writer', 'writer@example.com', 'pass123', 'Activated', 'NO');
        $catId = $this->insertTestCategory('General', 'Activated', 'General news');
        $newsId = $this->insertTestNews($userId, $catId, 'Form Article', 'Body', 'Activated', time());

        $output = $this->captureOutput(fn() => $this->news->commentform($newsId));

        $this->assertStringContainsString(L_NEWS_CANNOTPOSTCOMMENTS, $output);
    }

    #[Test]
    public function commentform_shows_form_for_guest_when_guests_allowed(): void
    {
        global $pnconfig;
        $pnconfig['commentwriting'] = 'Guests & Registered';

        $userId = $this->insertTestUser('writer', 'writer@example.com', 'pass123', 'Activated', 'NO');
        $catId = $this->insertTestCategory('General', 'Activated', 'General news');
        $newsId = $this->insertTestNews($userId, $catId, 'Guest Form Article', 'Body', 'Activated', time());

        $output = $this->captureOutput(fn() => $this->news->commentform($newsId));

        $this->assertStringContainsString('<form', $output);
        $this->assertStringContainsString('Guest', $output);
    }

    // ── getyearsforarchive ──

    #[Test]
    public function getyearsforarchive_returns_html_select(): void
    {
        $userId = $this->insertTestUser('writer', 'writer@example.com', 'pass123', 'Activated', 'NO');
        $catId = $this->insertTestCategory('General', 'Activated', 'General news');
        $this->insertTestNews($userId, $catId, 'Archive Article', 'Body', 'Activated', time());

        $result = $this->news->getyearsforarchive();

        $this->assertIsString($result);
        $this->assertStringContainsString('<option', $result);
        $this->assertStringContainsString((string) date('Y'), $result);
    }
}
