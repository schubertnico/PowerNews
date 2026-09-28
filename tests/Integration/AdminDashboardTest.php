<?php

declare(strict_types=1);

namespace PowerNews\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use PowerNews\Tests\DatabaseTestCase;

/**
 * Admin-Startseite mit Hinweisen auf Neues (ungeprüfte Einsendungen, neue Kommentare) und
 * News-Liste mit Filter nach Status.
 */
class AdminDashboardTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->resetDatabase();
    }

    #[Test]
    public function start_page_counts_unchecked_submissions_and_links_the_filter(): void
    {
        $this->insertTestNews(1, 0, 'Eingesendet 1', 'Text', 'Unchecked');
        $this->insertTestNews(1, 0, 'Eingesendet 2', 'Text', 'Unchecked');
        $this->insertTestNews(1, 0, 'Freigegeben', 'Text', 'Activated');

        $output = $this->renderAdminPage('main.inc.php', []);

        $this->assertStringContainsString('id="pn_dashboard"', $output);
        $this->assertStringContainsString(sprintf(\L_DASH_UNCHECKED_MANY, '<strong id="pn_dash_unchecked_count">2</strong>'), $output);
        $this->assertStringContainsString('<a class="btn btn-sm btn-outline-primary" id="pn_dash_unchecked_link" href="index.php?page=news&amp;subpage=show&amp;status=Unchecked">' . \L_DASH_UNCHECKED_LINK . '</a>', $output);
    }

    #[Test]
    public function one_or_no_submission_reads_well(): void
    {
        $none = $this->renderAdminPage('main.inc.php', []);
        $this->assertStringContainsString(sprintf(\L_DASH_UNCHECKED_MANY, '<strong id="pn_dash_unchecked_count">0</strong>'), $none);
        $this->assertStringNotContainsString('id="pn_dash_unchecked_link"', $none);

        $this->insertTestNews(1, 0, 'Eine Einsendung', 'Text', 'Unchecked');
        $one = $this->renderAdminPage('main.inc.php', []);
        $this->assertStringContainsString(sprintf(\L_DASH_UNCHECKED_ONE, '<strong id="pn_dash_unchecked_count">1</strong>'), $one);
    }

    #[Test]
    public function start_page_lists_comments_of_the_last_seven_days(): void
    {
        $userId = $this->insertTestUser('kommentator', 'kommentator@example.org');
        $newsId = $this->insertTestNews(1, 0, 'Sommerfest', 'Text');
        $recent = $this->insertTestComment($newsId, $userId, 'Toll!', time() - 3600);
        $guest = $this->insertTestComment($newsId, 0, 'Gastbeitrag', time() - 2 * 86400);
        $this->insertTestComment($newsId, 0, 'Alt', time() - 8 * 86400);

        $output = $this->renderAdminPage('main.inc.php', []);

        $this->assertStringContainsString(sprintf(\L_DASH_COMMENTS, 7, '<strong id="pn_dash_comments_count">2</strong>'), $output);
        $this->assertStringContainsString('<a class="pn-dash-comment" href="index.php?page=news&amp;subpage=edit&amp;newsid=' . $newsId . '#pn_comment_' . $recent . '">Sommerfest</a> &ndash; kommentator,', $output);
        $this->assertStringContainsString('#pn_comment_' . $guest . '">Sommerfest</a> &ndash; ' . \L_NEWS_GUEST . ',', $output);
        $this->assertSame(2, substr_count($output, 'class="pn-dash-comment"'), 'Der Kommentar von vor acht Tagen fehlt.');
        $this->assertLessThan(strpos($output, '#pn_comment_' . $guest), strpos($output, '#pn_comment_' . $recent), 'Neueste zuerst');
    }

    #[Test]
    public function hints_follow_the_permissions(): void
    {
        $newsId = $this->insertTestNews(1, 0, 'Ohne Link', 'Text', 'Unchecked');
        $this->insertTestComment($newsId, 0, 'Hallo', time() - 60);

        $noNews = $this->renderAdminPage('main.inc.php', [], [], ['canreadnews' => 'NO', 'canwritenews' => 'NO']);
        $this->assertStringNotContainsString('id="pn_dash_unchecked"', $noNews);
        $this->assertStringContainsString('id="pn_dash_comments"', $noNews);
        $this->assertStringNotContainsString('class="pn-dash-comment"', $noNews, 'Ohne Recht auf die Bearbeitung kein Link');
        $this->assertStringContainsString('Ohne Link &ndash;', $noNews);

        $nothing = $this->renderAdminPage('main.inc.php', [], [], ['canreadnews' => 'NO', 'canreadcomments' => 'NO']);
        $this->assertStringNotContainsString('id="pn_dashboard"', $nothing);
        $this->assertStringContainsString(\L_ALL_WELCOME, $nothing);
    }

    #[Test]
    public function news_list_filters_by_status(): void
    {
        $this->insertTestNews(1, 0, 'Ungeprüft A', 'Text', 'Unchecked');
        $this->insertTestNews(1, 0, 'Aktiv B', 'Text', 'Activated');
        $this->insertTestNews(1, 0, 'Aus C', 'Text', 'Deactivated');

        $filtered = $this->renderAdminPage('news_show.inc.php', ['status' => 'Unchecked']);
        $this->assertStringContainsString('Ungeprüft A', $filtered);
        $this->assertStringNotContainsString('Aktiv B', $filtered);
        $this->assertStringNotContainsString('Aus C', $filtered);
        $this->assertStringContainsString('<a class="btn btn-sm btn-primary" id="pn_filter_unchecked" href="index.php?page=news&amp;subpage=show&amp;status=Unchecked" aria-current="page">' . \L_ALL_UNCHECKED . ' (1)</a>', $filtered);
        $this->assertStringContainsString('<a class="btn btn-sm btn-outline-primary" id="pn_filter_all" href="index.php?page=news&amp;subpage=show">', $filtered);
        $this->assertStringContainsString('href="index.php?page=news&amp;subpage=show&amp;status=Unchecked&amp;current=0"', $filtered, 'Seitenwechsel behält den Filter');

        $all = $this->renderAdminPage('news_show.inc.php', ['status' => 'irgendwas']);
        foreach (['Ungeprüft A', 'Aktiv B', 'Aus C'] as $title) {
            $this->assertStringContainsString($title, $all);
        }
        $this->assertStringContainsString('id="pn_filter_all" href="index.php?page=news&amp;subpage=show" aria-current="page"', $all);

        $empty = $this->renderAdminPage('news_show.inc.php', ['status' => 'Deactivated']);
        $this->assertStringContainsString('Aus C', $empty);
        $this->assertSame(1, (new \news())->countnews('Deactivated'));
        $this->assertSame(3, (new \news())->countnews());
    }

    #[Test]
    public function comment_cards_have_anchors_for_the_links(): void
    {
        $newsId = $this->insertTestNews(1, 0, 'Anker', 'Text');
        $commentId = $this->insertTestComment($newsId, 0, 'Sprungziel');

        $output = $this->captureOutput(fn () => (new \news())->getcomments($newsId));

        $this->assertStringContainsString('<div class="card mb-3" id="pn_comment_' . $commentId . '">', $output);
    }
}
