<?php

declare(strict_types=1);

namespace PowerNews\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use PowerNews\Tests\DatabaseTestCase;

/**
 * Kommentare erscheinen im Admin und im Frontend chronologisch aufsteigend; „Kommentare
 * editieren“ speichert nur geänderte Kommentare.
 */
class CommentOrderAndSavingTest extends DatabaseTestCase
{
    private int $newsId = 0;

    /** @var array<string, int> */
    private array $ids = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetDatabase();
        $this->newsId = $this->insertTestNews(1, 0, 'Reihenfolge', 'Text');
        // Absichtlich nicht in zeitlicher Reihenfolge angelegt: IDs und Zeiten laufen auseinander.
        $this->ids['mitte'] = $this->insertTestComment($this->newsId, 0, 'Zweiter Kommentar', time() - 2000);
        $this->ids['erster'] = $this->insertTestComment($this->newsId, 0, 'Erster Kommentar', time() - 3000);
        $this->ids['letzter'] = $this->insertTestComment($this->newsId, 0, 'Dritter Kommentar', time() - 1000);
    }

    private function assertChronological(string $output): void
    {
        $first = strpos($output, 'Erster Kommentar');
        $second = strpos($output, 'Zweiter Kommentar');
        $third = strpos($output, 'Dritter Kommentar');

        $this->assertNotFalse($first);
        $this->assertTrue($first < $second && $second < $third, 'Älteste zuerst');
    }

    #[Test]
    public function admin_lists_comments_oldest_first(): void
    {
        $this->assertChronological($this->captureOutput(fn () => (new \news())->getcomments($this->newsId)));
        $this->assertChronological($this->captureOutput(fn () => (new \news())->getcommentsreadonly($this->newsId)));
    }

    #[Test]
    public function frontend_lists_comments_oldest_first(): void
    {
        $this->assertChronological($this->captureOutput(fn () => (new \pn_news())->comments($this->newsId)));
    }

    #[Test]
    public function only_changed_comments_are_saved(): void
    {
        global $pn_handler;

        mysqli_query($pn_handler, "UPDATE pn_comments SET text = 'Zeile 1\nZeile 2' WHERE id = " . $this->ids['erster']);
        $news = new \news();

        // Der Browser schickt CRLF; inhaltlich ist der erste Kommentar unverändert.
        $error = $news->editcomment(
            [$this->ids['erster'], $this->ids['mitte'], $this->ids['letzter']],
            ["Zeile 1\r\nZeile 2", 'Zweiter Kommentar, korrigiert', 'Dritter Kommentar'],
            [(string) $this->ids['letzter']],
        );

        $this->assertSame('', $error);
        $this->assertSame(1, $news->commentschanged);
        $this->assertSame(1, $news->commentsdeleted);
        $this->assertSame("Zeile 1\nZeile 2", pnadmin_comment_text($this->ids['erster']), 'Nicht neu gespeichert (sonst stünde dort CRLF)');
        $this->assertSame('Zweiter Kommentar, korrigiert', pnadmin_comment_text($this->ids['mitte']));
        $this->assertNull(pnadmin_comment_text($this->ids['letzter']));
    }

    #[Test]
    public function saving_without_changes_says_so(): void
    {
        $output = $this->renderAdminPage('news_edit.inc.php', ['newsid' => (string) $this->newsId, 'edit' => 'YES', 'editcomments' => 'YES'], [
            'commentid' => [(string) $this->ids['erster'], (string) $this->ids['mitte']],
            'commenttext' => ['Erster Kommentar', 'Zweiter Kommentar'],
        ]);

        $this->assertStringContainsString(\L_NEWS_NOCOMMENTCHANGES, $output);
        $this->assertStringNotContainsString(\L_NEWS_COMMENTSEDITED, $output);

        $changed = $this->renderAdminPage('news_edit.inc.php', ['newsid' => (string) $this->newsId, 'edit' => 'YES', 'editcomments' => 'YES'], [
            'commentid' => [(string) $this->ids['erster']],
            'commenttext' => ['Erster Kommentar!'],
        ]);
        $this->assertStringContainsString(\L_NEWS_COMMENTSEDITED, $changed);
    }

    #[Test]
    public function text_comparison_ignores_line_endings_only(): void
    {
        $this->assertTrue(pnadmin_same_text("a\r\nb", "a\nb"));
        $this->assertTrue(pnadmin_same_text('a', 'a'));
        $this->assertFalse(pnadmin_same_text('a ', 'a'));
        $this->assertFalse(pnadmin_same_text('a', null));
    }
}
