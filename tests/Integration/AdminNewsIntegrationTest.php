<?php

declare(strict_types=1);

namespace PowerNews\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use PowerNews\Tests\DatabaseTestCase;

class AdminNewsIntegrationTest extends DatabaseTestCase
{
    private \news $news;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetDatabase();
        $this->news = new \news();
    }

    // ── addnews ──

    #[Test]
    public function addnews_returns_empty_string_on_success(): void
    {
        $userId = $this->insertTestUser('author', 'author@test.com');
        $this->loginAsUser($userId, 'author', 'author@test.com');

        $result = $this->news->addnews(
            'Test Title',
            'Test body text',
            0,
            '',
            [],
            [],
            [],
            ['hour' => '12', 'min' => '30', 'month' => '6', 'day' => '15', 'year' => '2025'],
        );

        $this->assertSame('', $result);
    }

    #[Test]
    public function addnews_persists_news_to_database(): void
    {
        global $pn_handler, $pn_config;

        $userId = $this->insertTestUser('author', 'author@test.com');
        $this->loginAsUser($userId, 'author', 'author@test.com');

        $this->news->addnews(
            'Persisted Title',
            'Persisted body',
            0,
            'More text here',
            [],
            [],
            [],
            ['hour' => '10', 'min' => '0', 'month' => '1', 'day' => '1', 'year' => '2025'],
        );

        $result = mysqli_query($pn_handler, 'SELECT * FROM ' . $pn_config['newstable'] . " WHERE title LIKE '%Persisted Title%'");
        $this->assertSame(1, mysqli_num_rows($result));
    }

    #[Test]
    public function addnews_with_category(): void
    {
        $userId = $this->insertTestUser('author', 'author@test.com');
        $catId = $this->insertTestCategory('Tech');
        $this->loginAsUser($userId, 'author', 'author@test.com');

        $result = $this->news->addnews(
            'Categorized News',
            'Body',
            $catId,
            '',
            [],
            [],
            [],
            ['hour' => '0', 'min' => '0', 'month' => '1', 'day' => '1', 'year' => '2025'],
        );

        $this->assertSame('', $result);
    }

    #[Test]
    public function addnews_with_related_links(): void
    {
        global $pn_handler, $pn_config;

        $userId = $this->insertTestUser('author', 'author@test.com');
        $this->loginAsUser($userId, 'author', 'author@test.com');

        $this->news->addnews(
            'News With Links',
            'Body',
            0,
            '',
            ['Link 1', 'Link 2'],
            ['http://example.com', 'http://test.com'],
            ['_blank', '_main'],
            ['hour' => '0', 'min' => '0', 'month' => '1', 'day' => '1', 'year' => '2025'],
        );

        $result = mysqli_query($pn_handler, 'SELECT relatedlinks FROM ' . $pn_config['newstable'] . " WHERE title LIKE '%News With Links%'");
        $row = mysqli_fetch_assoc($result);

        $this->assertStringContainsString('Link 1', $row['relatedlinks']);
        $this->assertStringContainsString('http://example.com', $row['relatedlinks']);
    }

    // ── getcatdropdown ──

    #[Test]
    public function getcatdropdown_outputs_no_cats_message_when_empty(): void
    {
        $this->truncateAll();

        $output = $this->captureOutput(fn() => $this->news->getcatdropdown());

        $this->assertStringContainsString(L_NEWS_NOCATSAVAILABLE, $output);
    }

    #[Test]
    public function getcatdropdown_outputs_select_with_categories(): void
    {
        $this->insertTestCategory('Sports');
        $this->insertTestCategory('Politics');

        $output = $this->captureOutput(fn() => $this->news->getcatdropdown());

        $this->assertStringContainsString('<select', $output);
        $this->assertStringContainsString('Sports', $output);
        $this->assertStringContainsString('Politics', $output);
    }

    #[Test]
    public function getcatdropdown_marks_selected_category(): void
    {
        $catId = $this->insertTestCategory('Selected');

        $output = $this->captureOutput(fn() => $this->news->getcatdropdown($catId));

        $this->assertStringContainsString('selected', $output);
    }

    #[Test]
    public function getcatdropdown_shows_choose_option_when_catid_zero(): void
    {
        $this->insertTestCategory('Any');

        $output = $this->captureOutput(fn() => $this->news->getcatdropdown(0));

        $this->assertStringContainsString(L_NEWS_CHOOSECAT, $output);
    }

    // ── getcatname ──

    #[Test]
    public function getcatname_returns_name_for_valid_category(): void
    {
        $catId = $this->insertTestCategory('Technology');

        $result = $this->news->getcatname($catId);

        $this->assertSame('Technology', $result);
    }

    #[Test]
    public function getcatname_returns_bad_cat_for_invalid_category(): void
    {
        $result = $this->news->getcatname(999999);

        $this->assertSame(L_NEWS_BADCAT, $result);
    }

    // ── listnews ──

    #[Test]
    public function listnews_outputs_no_news_message_when_empty(): void
    {
        $output = $this->captureOutput(fn() => $this->news->listnews(0));

        $this->assertStringContainsString(L_NEWS_NONEWS, $output);
    }

    #[Test]
    public function listnews_outputs_news_rows(): void
    {
        $userId = $this->insertTestUser('author', 'author@test.com');
        $newsId = $this->insertTestNews($userId, 0, 'My News Title', 'Body');

        $output = $this->captureOutput(fn() => $this->news->listnews(0));

        $this->assertStringContainsString('My News Title', $output);
        $this->assertStringContainsString('<tr>', $output);
    }

    // ── checknews ──

    #[Test]
    public function checknews_returns_empty_string_for_existing_news(): void
    {
        $userId = $this->insertTestUser('author', 'author@test.com');
        $newsId = $this->insertTestNews($userId, 0, 'Check Me', 'Body');

        $result = $this->news->checknews($newsId);

        $this->assertSame('', $result);
    }

    #[Test]
    public function checknews_returns_error_for_nonexistent_news(): void
    {
        $result = $this->news->checknews(999999);

        $this->assertSame(L_NEWS_CHOOSENEWS, $result);
    }

    // ── getnewsdata ──

    #[Test]
    public function getnewsdata_returns_array_for_existing_news(): void
    {
        $userId = $this->insertTestUser('author', 'author@test.com');
        $newsId = $this->insertTestNews($userId, 0, 'Data News', 'Body content');

        $data = $this->news->getnewsdata($newsId);

        $this->assertIsArray($data);
        $this->assertSame('Data News', $data['title']);
        $this->assertSame('Body content', $data['text']);
    }

    #[Test]
    public function getnewsdata_returns_null_for_nonexistent_news(): void
    {
        $data = $this->news->getnewsdata(999999);

        $this->assertNull($data);
    }

    #[Test]
    public function getnewsdata_stripslashes_title_text_moretext(): void
    {
        global $pn_handler, $pn_config;

        $userId = $this->insertTestUser('author', 'author@test.com');
        $title = addslashes("It's a test");
        $text = addslashes("Body with 'quotes'");
        $moretext = addslashes("More 'text'");
        $time = time();
        $status = 'Activated';
        $relatedlinks = '';

        $stmt = mysqli_prepare($pn_handler, 'INSERT INTO ' . $pn_config['newstable'] . ' (userid, time, catid, title, text, moretext, status, relatedlinks) VALUES(?, ?, 0, ?, ?, ?, ?, ?)');
        mysqli_stmt_bind_param($stmt, 'iisssss', $userId, $time, $title, $text, $moretext, $status, $relatedlinks);
        mysqli_stmt_execute($stmt);
        $newsId = (int) mysqli_insert_id($pn_handler);

        $data = $this->news->getnewsdata($newsId);

        $this->assertSame("It's a test", $data['title']);
        $this->assertSame("Body with 'quotes'", $data['text']);
        $this->assertSame("More 'text'", $data['moretext']);
    }

    // ── editnews ──

    #[Test]
    public function editnews_returns_empty_string_on_valid_edit(): void
    {
        $userId = $this->insertTestUser('author', 'author@test.com');
        $newsId = $this->insertTestNews($userId, 0, 'Original', 'Body');

        $result = $this->news->editnews(
            $newsId,
            0,
            'Updated Title',
            'Updated body',
            'Updated more',
            'Activated',
            'NO',
            [],
            [],
            [],
            ['hour' => '12', 'min' => '0', 'month' => '6', 'day' => '15', 'year' => '2025'],
        );

        $this->assertSame('', $result);
    }

    #[Test]
    public function editnews_updates_data_in_database(): void
    {
        $userId = $this->insertTestUser('author', 'author@test.com');
        $newsId = $this->insertTestNews($userId, 0, 'Before', 'Before body');

        $this->news->editnews(
            $newsId,
            0,
            'After',
            'After body',
            'After more',
            'Deactivated',
            'NO',
            [],
            [],
            [],
            ['hour' => '0', 'min' => '0', 'month' => '1', 'day' => '1', 'year' => '2025'],
        );

        $data = $this->news->getnewsdata($newsId);
        $this->assertSame('After', $data['title']);
        $this->assertSame('After body', $data['text']);
        $this->assertSame('Deactivated', $data['status']);
    }

    #[Test]
    public function editnews_deletes_news_when_delete_is_yes(): void
    {
        $userId = $this->insertTestUser('author', 'author@test.com');
        $newsId = $this->insertTestNews($userId, 0, 'To Delete', 'Body');

        $result = $this->news->editnews(
            $newsId,
            0,
            'To Delete',
            'Body',
            '',
            'Activated',
            'YES',
            [],
            [],
            [],
            ['hour' => '0', 'min' => '0', 'month' => '1', 'day' => '1', 'year' => '2025'],
        );

        $this->assertSame('', $result);
        $this->assertNull($this->news->getnewsdata($newsId));
    }

    // ── getcommentauthor ──

    #[Test]
    public function getcommentauthor_returns_link_for_existing_user(): void
    {
        $userId = $this->insertTestUser('commenter', 'commenter@test.com');

        $result = $this->news->getcommentauthor($userId);

        $this->assertStringContainsString('commenter', $result);
        $this->assertStringContainsString('<a href=', $result);
        $this->assertStringContainsString("userid=$userId", $result);
    }

    #[Test]
    public function getcommentauthor_returns_guest_for_nonexistent_user(): void
    {
        $result = $this->news->getcommentauthor(999999);

        $this->assertSame(L_NEWS_GUEST, $result);
    }

    // ── checkcomment ──

    #[Test]
    public function checkcomment_returns_empty_string_for_valid_comments(): void
    {
        $userId = $this->insertTestUser('author', 'author@test.com');
        $newsId = $this->insertTestNews($userId, 0, 'News', 'Body');
        $commentId = $this->insertTestComment($newsId, $userId, 'Valid comment');

        $result = $this->news->checkcomment([$commentId], ['Valid comment']);

        $this->assertSame('', $result);
    }

    #[Test]
    public function checkcomment_returns_error_for_invalid_comment_id(): void
    {
        $result = $this->news->checkcomment([999999], ['Some text']);

        $this->assertSame(L_NEWS_ONECOMMENTWRONG, $result);
    }

    #[Test]
    public function checkcomment_returns_error_for_empty_comment_text(): void
    {
        $userId = $this->insertTestUser('author', 'author@test.com');
        $newsId = $this->insertTestNews($userId, 0, 'News', 'Body');
        $commentId = $this->insertTestComment($newsId, $userId, 'Original text');

        $result = $this->news->checkcomment([$commentId], ['']);

        $this->assertSame(L_NEWS_NOCOMMENTTEXT, $result);
    }

    // ── editcomment ──

    #[Test]
    public function editcomment_updates_comment_text(): void
    {
        global $pn_handler, $pn_config;

        $userId = $this->insertTestUser('author', 'author@test.com');
        $newsId = $this->insertTestNews($userId, 0, 'News', 'Body');
        $commentId = $this->insertTestComment($newsId, $userId, 'Old text');

        $result = $this->news->editcomment([$commentId], ['New text'], []);

        $this->assertSame('', $result);

        $stmt = mysqli_prepare($pn_handler, 'SELECT text FROM ' . $pn_config['commenttable'] . ' WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'i', $commentId);
        mysqli_stmt_execute($stmt);
        $dbResult = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($dbResult);

        $this->assertStringContainsString('New text', $row['text']);
    }

    #[Test]
    public function editcomment_deletes_comment_when_marked_for_deletion(): void
    {
        global $pn_handler, $pn_config;

        $userId = $this->insertTestUser('author', 'author@test.com');
        $newsId = $this->insertTestNews($userId, 0, 'News', 'Body');
        $commentId = $this->insertTestComment($newsId, $userId, 'To delete');

        $result = $this->news->editcomment([$commentId], ['To delete'], [$commentId]);

        $this->assertSame('', $result);

        $stmt = mysqli_prepare($pn_handler, 'SELECT id FROM ' . $pn_config['commenttable'] . ' WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'i', $commentId);
        mysqli_stmt_execute($stmt);
        $dbResult = mysqli_stmt_get_result($stmt);

        $this->assertSame(0, mysqli_num_rows($dbResult));
    }

    // ── searchnews ──

    #[Test]
    public function searchnews_outputs_no_news_when_no_results(): void
    {
        $output = $this->captureOutput(fn() => $this->news->searchnews('title', 'nonexistent', 0));

        $this->assertStringContainsString(L_NEWS_NONEWS, $output);
    }

    #[Test]
    public function searchnews_outputs_matching_rows(): void
    {
        $userId = $this->insertTestUser('author', 'author@test.com');
        $this->insertTestNews($userId, 0, 'Searchable Article', 'Body');

        $output = $this->captureOutput(fn() => $this->news->searchnews('title', 'Searchable', 0));

        $this->assertStringContainsString('Searchable Article', $output);
        $this->assertStringContainsString('<tr>', $output);
    }

    #[Test]
    public function searchnews_searches_in_text_field(): void
    {
        $userId = $this->insertTestUser('author', 'author@test.com');
        $this->insertTestNews($userId, 0, 'Generic Title', 'UniqueBodyContent');

        $output = $this->captureOutput(fn() => $this->news->searchnews('text', 'UniqueBody', 0));

        $this->assertStringContainsString('Generic Title', $output);
    }

    // ── listpages ──

    #[Test]
    public function listpages_outputs_no_pages_when_empty(): void
    {
        $output = $this->captureOutput(fn() => $this->news->listpages());

        $this->assertStringContainsString(L_ALL_NOPAGES, $output);
    }

    #[Test]
    public function listpages_outputs_page_links_when_news_exists(): void
    {
        $userId = $this->insertTestUser('author', 'author@test.com');
        $this->insertTestNews($userId, 0, 'Page News', 'Body');

        $output = $this->captureOutput(fn() => $this->news->listpages());

        // Pagination ist jetzt Bootstrap-konform: <li class="page-item"><a class="page-link">...</a></li>.
        $this->assertStringContainsString('1', $output);
        $this->assertStringContainsString('page-item', $output);
        $this->assertStringContainsString('page-link', $output);
    }

    // ── listsearchpages ──

    #[Test]
    public function listsearchpages_outputs_no_pages_when_no_results(): void
    {
        $output = $this->captureOutput(fn() => $this->news->listsearchpages('title', 'nonexistent'));

        $this->assertStringContainsString(L_ALL_NOPAGES, $output);
    }

    #[Test]
    public function listsearchpages_outputs_page_links_when_results_exist(): void
    {
        $userId = $this->insertTestUser('author', 'author@test.com');
        $this->insertTestNews($userId, 0, 'FindableTitle', 'Body');

        $output = $this->captureOutput(fn() => $this->news->listsearchpages('title', 'Findable'));

        // Pagination ist jetzt Bootstrap-konform.
        $this->assertStringContainsString('1', $output);
        $this->assertStringContainsString('page-item', $output);
        $this->assertStringContainsString('page-link', $output);
    }
}
