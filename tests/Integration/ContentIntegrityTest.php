<?php

declare(strict_types=1);

namespace PowerNews\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use PowerNews\Tests\DatabaseTestCase;

/**
 * Regressionstests für B03 (Backslashes vor Apostroph und Anführungszeichen) und
 * B10 (Dollar- und Backslash-Folgen verschwinden aus Inhalten).
 */
class ContentIntegrityTest extends DatabaseTestCase
{
    private const QUOTED = 'Peter\'s "Sonder"-Aktion für $10 bzw. \1';

    #[Test]
    public function admin_news_is_stored_without_added_backslashes(): void
    {
        global $pn_config, $pn_handler;

        $userId = $this->insertTestUser('slash_author', 'slash_author@example.com');
        $this->loginAsUser($userId, 'slash_author', 'slash_author@example.com');
        $news = new \news();
        $time = ['day' => '1', 'month' => '2', 'year' => '2025', 'hour' => '8', 'min' => '0'];

        $this->assertSame('', $news->addnews(self::QUOTED, self::QUOTED, 0, 'Langtext mit C:\temp', [], [], [], $time));
        $newsId = (int) mysqli_insert_id($pn_handler);

        $row = $this->fetchRow($pn_config['newstable'], $newsId);
        $this->assertSame(self::QUOTED, $row['title']);
        $this->assertSame(self::QUOTED, $row['text']);
        $this->assertSame('Langtext mit C:\temp', $row['moretext']);

        $news->editnews($newsId, 0, 'O\'Neill', 'Text "zitiert"', '', 'Activated', 'NO', [], [], [], $time);
        $row = $this->fetchRow($pn_config['newstable'], $newsId);
        $this->assertSame('O\'Neill', $row['title']);
        $this->assertSame('Text "zitiert"', $row['text']);
    }

    #[Test]
    public function category_and_edited_comment_are_stored_unchanged(): void
    {
        global $pn_config, $pn_handler;

        $category = new \category();
        $this->assertSame('', $category->addcat('Jugend\'s Ecke', 'Für "alle" ab 12'));
        $catId = (int) mysqli_insert_id($pn_handler);
        $row = $this->fetchRow($pn_config['cattable'], $catId);
        $this->assertSame('Jugend\'s Ecke', $row['name']);
        $this->assertSame('Für "alle" ab 12', $row['description']);

        $userId = $this->insertTestUser('comment_author', 'comment_author@example.com');
        $newsId = $this->insertTestNews($userId, 0, 'Kommentarziel', 'Text');
        $commentId = $this->insertTestComment($newsId, 0, 'alt');
        $news = new \news();
        $this->assertSame('', $news->editcomment([(string) $commentId], ['Wann gibt\'s die "Fotos"?'], []));
        $this->assertSame('Wann gibt\'s die "Fotos"?', $this->fetchRow($pn_config['commenttable'], $commentId)['text']);
    }

    #[Test]
    public function frontend_templates_keep_dollar_and_backslash_sequences(): void
    {
        global $pnconfig;

        $pnconfig['bbcode'] = 'NO';
        $pnconfig['smilies'] = 'NO';
        $template = new \pn_template();

        $news = $this->captureOutput(fn () => $template->news(7, 'Autor', time() - 60, 'Allgemein', 'Preis: $10', 'kostet $10 bzw. \1 und $1', 0, 'NO'));
        $this->assertStringContainsString('Preis: $10', $news);
        $this->assertStringContainsString('kostet $10 bzw. \1 und $1', $news);

        $comment = $this->captureOutput(fn () => $template->comment(1, 7, ['id' => 0], time(), 'Nur $10?'));
        $this->assertStringContainsString('Nur $10?', $comment);

        $message = $this->captureOutput(fn () => $template->message('Betrag $10 gespeichert', 'index.php'));
        $this->assertStringContainsString('Betrag $10 gespeichert', $message);
    }

    #[Test]
    public function placeholders_inside_user_content_are_not_expanded(): void
    {
        global $pnconfig;

        $pnconfig['bbcode'] = 'NO';
        $pnconfig['smilies'] = 'NO';
        $template = new \pn_template();

        $output = $this->captureOutput(fn () => $template->news(8, 'Autor', time() - 60, 'Allgemein', 'Titel {TEXT}', 'Geheimer Text', 0, 'NO'));

        $this->assertStringContainsString('Titel {TEXT}', $output);
        $this->assertSame(1, substr_count($output, 'Geheimer Text'));
    }

    #[Test]
    public function frontend_shows_apostrophes_without_backslashes(): void
    {
        global $pnconfig;

        $pnconfig['bbcode'] = 'NO';
        $template = new \pn_template();

        $output = $this->captureOutput(fn () => $template->news(9, 'Autor', time() - 60, 'Allgemein', 'Peter\'s "Aktion"', 'Text', 0, 'NO'));

        $this->assertStringContainsString('Peter&#039;s &quot;Aktion&quot;', $output);
        $this->assertStringNotContainsString('\\', $output);
    }

    #[Test]
    public function unslash_only_reverts_genuine_addslashes_output(): void
    {
        $this->assertSame('Peter\'s "Aktion"', pn_unslash_legacy(addslashes('Peter\'s "Aktion"')));
        $this->assertSame('O\'Neill', pn_unslash_legacy('O\\\'Neill'));
        $this->assertSame('C:\Users\test', pn_unslash_legacy('C:\Users\test'), 'Einzelne Backslashes bleiben.');
        $this->assertSame('It\'s \"x\"', pn_unslash_legacy('It\'s \"x\"'), 'Unmaskierte Quotes: kein addslashes-Ergebnis.');
        $this->assertSame('ohne', pn_unslash_legacy('ohne'));
    }

    #[Test]
    public function migration_cleans_legacy_rows_exactly_once(): void
    {
        global $pn_config, $pn_handler;

        mysqli_query($pn_handler, 'DROP TABLE IF EXISTS pn_migrations');

        $userId = $this->insertTestUser('legacy_author', 'legacy_author@example.com');
        $slashed = $this->insertTestNews($userId, 0, addslashes('Legacy "Titel" von O\'Neill'), addslashes('Kosten: 5 \\ Stück'));
        $untouched = $this->insertTestNews($userId, 0, 'Pfad C:\temp', 'Frontend-Text mit \' ohne addslashes');
        $catId = $this->insertTestCategory(addslashes('Legacy\'s'), 'Activated',addslashes('Beschreibung "neu"'));
        $commentId = $this->insertTestComment($slashed, 0, addslashes('Gibt\'s "Fotos"?'));

        $log = pn_run_migrations($pn_handler, $pn_config);

        $this->assertArrayHasKey('3.12-unslash-content', $log);
        $this->assertSame('Legacy "Titel" von O\'Neill', $this->fetchRow($pn_config['newstable'], $slashed)['title']);
        $this->assertSame('Kosten: 5 \\ Stück', $this->fetchRow($pn_config['newstable'], $slashed)['text']);
        $this->assertSame('Pfad C:\temp', $this->fetchRow($pn_config['newstable'], $untouched)['title']);
        $this->assertSame('Frontend-Text mit \' ohne addslashes', $this->fetchRow($pn_config['newstable'], $untouched)['text']);
        $this->assertSame('Legacy\'s', $this->fetchRow($pn_config['cattable'], $catId)['name']);
        $this->assertSame('Beschreibung "neu"', $this->fetchRow($pn_config['cattable'], $catId)['description']);
        $this->assertSame('Gibt\'s "Fotos"?', $this->fetchRow($pn_config['commenttable'], $commentId)['text']);

        // Zweiter Lauf: nichts mehr zu tun, auch neue Backslash-Folgen bleiben unangetastet.
        $again = $this->insertTestNews($userId, 0, 'Neu \\\\ doppelt', 'x');
        $this->assertSame([], array_intersect_key(pn_run_migrations($pn_handler, $pn_config), ['3.12-unslash-content' => true]));
        $this->assertSame('Neu \\\\ doppelt', $this->fetchRow($pn_config['newstable'], $again)['title']);
    }
}
