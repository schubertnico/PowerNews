<?php

declare(strict_types=1);

namespace PowerNews\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use PowerNews\Tests\DatabaseTestCase;

/**
 * Regressionstests für das Bearbeiten, Löschen und Freischalten von News über die
 * Admin-Seite pnadmin/news_edit.inc.php (B02), die Terminwahl (B43) und die Suche (B30).
 */
class AdminNewsEditPageTest extends DatabaseTestCase
{
    private function validTime(): array
    {
        return ['day' => '15', 'month' => '6', 'year' => '2025', 'hour' => '12', 'min' => '30'];
    }

    #[Test]
    public function edit_form_saves_title_text_and_status_from_post(): void
    {
        global $pn_config;

        $userId = $this->insertTestUser('edit_author', 'edit_author@example.com');
        $catId = $this->insertTestCategory('Edit-Kategorie');
        $newsId = $this->insertTestNews($userId, $catId, 'Alter Titel', 'Alter Text', 'Unchecked');

        $output = $this->renderAdminPage('news_edit.inc.php', ['newsid' => (string) $newsId, 'edit' => 'YES'], [
            'catid' => (string) $catId,
            'title' => 'Neuer Titel',
            'text' => 'Neuer Text',
            'status' => 'Activated',
            'time' => $this->validTime(),
        ]);

        $this->assertStringContainsString('alert-success', $output);
        $row = $this->fetchRow($pn_config['newstable'], $newsId);
        $this->assertSame('Neuer Titel', $row['title']);
        $this->assertSame('Neuer Text', $row['text']);
        $this->assertSame('Activated', $row['status'], 'Eingesendete News müssen freigeschaltet werden können.');
        $this->assertSame(mktime(12, 30, 0, 6, 15, 2025), (int) $row['time']);
    }

    #[Test]
    public function delete_checkbox_removes_news_and_its_comments(): void
    {
        global $pn_config, $pn_handler;

        $userId = $this->insertTestUser('del_author', 'del_author@example.com');
        $newsId = $this->insertTestNews($userId, 0, 'Wird gelöscht', 'Text');
        $this->insertTestComment($newsId, 0, 'Kommentar zur gelöschten News');

        $output = $this->renderAdminPage('news_edit.inc.php', ['newsid' => (string) $newsId, 'edit' => 'YES'], [
            'delete' => 'YES',
            'title' => 'Wird gelöscht',
            'text' => 'Text',
            'status' => 'Activated',
            'time' => $this->validTime(),
        ]);

        $this->assertStringContainsString(\L_NEWS_NEWSDELETED, $output);
        $this->assertNull($this->fetchRow($pn_config['newstable'], $newsId));
        $result = pn_query_by_id($pn_handler, 'SELECT id FROM ' . $pn_config['commenttable'] . ' WHERE newsid = ?', $newsId);
        $this->assertSame(0, mysqli_num_rows($result));
    }

    #[Test]
    public function invalid_date_is_rejected_and_news_stays_unchanged(): void
    {
        global $pn_config;

        $userId = $this->insertTestUser('date_author', 'date_author@example.com');
        $catId = $this->insertTestCategory('Datum-Kategorie');
        $newsId = $this->insertTestNews($userId, $catId, 'Datumstest', 'Text');

        $output = $this->renderAdminPage('news_edit.inc.php', ['newsid' => (string) $newsId, 'edit' => 'YES'], [
            'catid' => (string) $catId,
            'title' => 'Geändert',
            'text' => 'Text',
            'status' => 'Activated',
            'time' => ['day' => '31', 'month' => '2', 'year' => '2026', 'hour' => '10', 'min' => '0'],
        ]);

        $this->assertStringContainsString(pnadmin_escape(\L_NEWS_INVALIDDATE), $output);
        $this->assertSame('Datumstest', $this->fetchRow($pn_config['newstable'], $newsId)['title']);
    }

    #[Test]
    public function hidden_fields_keep_their_stored_values(): void
    {
        global $pn_config, $pn_handler, $pnconfig;

        $userId = $this->insertTestUser('keep_author', 'keep_author@example.com');
        $catId = $this->insertTestCategory('Behalten');
        $newsId = $this->insertTestNews($userId, $catId, 'Mit Langtext', 'Kurztext');
        $stmt = mysqli_prepare($pn_handler, 'UPDATE ' . $pn_config['newstable'] . ' SET moretext = ?, relatedlinks = ? WHERE id = ?');
        $moretext = 'Gespeicherter Langtext';
        $links = "Verein!@!@!https://example.org!@!@!_blank\n";
        mysqli_stmt_bind_param($stmt, 'ssi', $moretext, $links, $newsId);
        mysqli_stmt_execute($stmt);

        // Textaufteilung und Related Links sind ausgeschaltet: das Formular sendet die Felder nicht.
        $pnconfig['moretext'] = 'NO';
        $pnconfig['relatedlinks'] = 'NO';

        $this->renderAdminPage('news_edit.inc.php', ['newsid' => (string) $newsId, 'edit' => 'YES'], [
            'catid' => (string) $catId,
            'title' => 'Mit Langtext (bearbeitet)',
            'text' => 'Kurztext',
            'status' => 'Activated',
            'time' => $this->validTime(),
        ]);

        $row = $this->fetchRow($pn_config['newstable'], $newsId);
        $this->assertSame('Mit Langtext (bearbeitet)', $row['title']);
        $this->assertSame('Gespeicherter Langtext', $row['moretext']);
        $this->assertStringContainsString('https://example.org', $row['relatedlinks']);
    }

    #[Test]
    public function edit_form_offers_past_years_for_backdating(): void
    {
        $userId = $this->insertTestUser('year_author', 'year_author@example.com');
        $newsId = $this->insertTestNews($userId, 0, 'Alte News', 'Text', 'Activated', mktime(8, 0, 0, 3, 1, 2012));

        $output = $this->renderAdminPage('news_edit.inc.php', ['newsid' => (string) $newsId]);

        $this->assertMatchesRegularExpression('/<option value="2012" selected>2012<\/option>/', $output);
        $this->assertStringContainsString('<option value="' . ((int) date('Y') - 10) . '"', $output);
    }

    #[Test]
    public function parsetime_rejects_impossible_dates(): void
    {
        $this->assertNull(\news::parsetime(['day' => '31', 'month' => '2', 'year' => '2026', 'hour' => '0', 'min' => '0']));
        $this->assertNull(\news::parsetime(['day' => '1', 'month' => '1', 'year' => '2026', 'hour' => '24', 'min' => '0']));
        $this->assertNull(\news::parsetime([]));
        $this->assertSame(mktime(0, 0, 0, 2, 29, 2028), \news::parsetime(['day' => '29', 'month' => '2', 'year' => '2028', 'hour' => '0', 'min' => '0']));
    }

    #[Test]
    public function search_by_id_matches_exactly(): void
    {
        $userId = $this->insertTestUser('search_author', 'search_author@example.com');
        $newsId = $this->insertTestNews($userId, 0, 'Suche nach ID', 'Text');

        $output = $this->renderAdminPage('news_search.inc.php', [
            'search' => 'YES',
            'searchin' => 'id',
            'searchstring' => (string) $newsId,
        ]);

        $this->assertStringContainsString('newsid=' . $newsId . '"', $output);
        $this->assertSame(1, substr_count($output, 'subpage=edit&amp;newsid='));
    }

    #[Test]
    public function search_in_moretext_finds_long_text(): void
    {
        global $pn_config, $pn_handler;

        $userId = $this->insertTestUser('more_author', 'more_author@example.com');
        $newsId = $this->insertTestNews($userId, 0, 'Langtextsuche', 'Kurz');
        $stmt = mysqli_prepare($pn_handler, 'UPDATE ' . $pn_config['newstable'] . ' SET moretext = ? WHERE id = ?');
        $moretext = 'Einzigartiges Wort Zitronenfalter im Langtext';
        mysqli_stmt_bind_param($stmt, 'si', $moretext, $newsId);
        mysqli_stmt_execute($stmt);

        $output = $this->renderAdminPage('news_search.inc.php', [
            'search' => 'YES',
            'searchin' => 'moretext',
            'searchstring' => 'Zitronenfalter',
        ]);

        $this->assertStringContainsString('newsid=' . $newsId . '"', $output);
    }

    #[Test]
    public function user_search_by_id_matches_exactly(): void
    {
        $userId = $this->insertTestUser('id_search_user', 'id_search_user@example.com');

        $output = $this->renderAdminPage('users_search.inc.php', [
            'search' => 'YES',
            'searchin' => 'id',
            'searchstring' => (string) $userId,
        ]);

        $this->assertStringContainsString('id_search_user', $output);
        $this->assertSame(1, substr_count($output, 'subpage=edit&amp;userid='));
    }

    #[Test]
    public function format_hint_reflects_combined_bbcode_setting(): void
    {
        global $pnconfig;

        $pnconfig['bbcode'] = 'Comments/News';
        $hint = (new \news())->formathint();

        $this->assertStringContainsString('#help-other-bbcode', $hint);
        $this->assertStringContainsString(\L_NEWS_ON, $hint);
    }
}
