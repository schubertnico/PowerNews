<?php

declare(strict_types=1);

namespace PowerNews\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use PowerNews\Tests\DatabaseTestCase;

/**
 * Regressionstests für B28 (eingesendete Links erscheinen nie, zwei Speicherformate) und
 * B37 (Link-URLs im Admin ohne Schemaprüfung).
 */
class RelatedLinksTest extends DatabaseTestCase
{
    private function time(): array
    {
        return ['day' => '2', 'month' => '3', 'year' => '2025', 'hour' => '9', 'min' => '15'];
    }

    private function renderNews(string $relatedlinks): string
    {
        global $pnconfig;

        $pnconfig['relatedlinks'] = 'YES';
        $template = new \pn_template();

        return $this->captureOutput(fn () => $template->news(11, 'Autor', time() - 60, 'Allgemein', 'Titel', 'Text', 0, 'YES', '', $relatedlinks));
    }

    #[Test]
    public function submitted_links_are_stored_as_json_and_shown(): void
    {
        global $pn_config, $pn_handler, $pnconfig;

        $pnconfig['sendnews'] = 'YES';
        $pnconfig['newssending'] = 'Guests/Registered';
        $pnconfig['categories'] = 'NO';
        $pnconfig['relatedlinks'] = 'YES';
        $this->setGet(['pndata' => ['send' => 'YES']]);
        $this->setPost(['pndata' => [
            'title' => 'Eingesendet mit Links',
            'text' => 'Text',
            'rl_title' => ['Vereinsseite', 'Böse'],
            'rl_url' => ['https://verein.example.org/', 'javascript:alert(1)'],
            'rl_target' => ['_main', '_blank'],
        ]]);

        $this->captureOutput(fn () => (new \pn_news())->sendnews());

        $result = mysqli_query($pn_handler, "SELECT relatedlinks FROM " . $pn_config['newstable'] . " WHERE title = 'Eingesendet mit Links' ORDER BY id DESC LIMIT 1");
        $stored = (string) mysqli_fetch_row($result)[0];
        $this->assertSame([['title' => 'Vereinsseite', 'url' => 'https://verein.example.org/', 'target' => '_main']], json_decode($stored, true));

        $output = $this->renderNews($stored);
        $this->assertStringContainsString('href="https://verein.example.org/"', $output);
        // „_main“ (Frameset-Zeit) steht für das Hauptfenster, also dasselbe Fenster.
        $this->assertStringContainsString('target="_self"', $output);
        $this->assertStringContainsString('Vereinsseite', $output);
    }

    #[Test]
    public function admin_uses_the_same_format(): void
    {
        global $pn_config, $pn_handler;

        $userId = $this->insertTestUser('link_admin', 'link_admin@example.com');
        $this->loginAsUser($userId, 'link_admin', 'link_admin@example.com');

        $error = (new \news())->addnews('Admin mit Links', 'Text', 0, '', ['Impressum', 'Extern'], ['/impressum', 'https://example.org'], ['_blank', '_main'], $this->time());

        $this->assertSame('', $error);
        $newsId = (int) mysqli_insert_id($pn_handler);
        $this->assertSame(
            [['title' => 'Impressum', 'url' => '/impressum', 'target' => '_blank'], ['title' => 'Extern', 'url' => 'https://example.org', 'target' => '_main']],
            pn_relatedlinks_decode((string) $this->fetchRow($pn_config['newstable'], $newsId)['relatedlinks']),
        );
    }

    #[Test]
    public function admin_rejects_script_schemes(): void
    {
        global $pn_config;

        $userId = $this->insertTestUser('link_admin2', 'link_admin2@example.com');
        $this->loginAsUser($userId, 'link_admin2', 'link_admin2@example.com');
        $news = new \news();

        foreach (['javascript:alert(1)', 'JaVaScRiPt:alert(1)', 'data:text/html,<b>x</b>', 'vbscript:x', " javascript:alert(1)", '/\\evil.example'] as $url) {
            $this->assertSame(\L_NEWS_INVALIDLINK, $news->addnews('Böser Link', 'Text', 0, '', ['Klick'], [$url], ['_blank'], $this->time()), $url);
        }

        $newsId = $this->insertTestNews($userId, 0, 'Bestehend', 'Text');
        $this->assertSame(\L_NEWS_INVALIDLINK, $news->editnews($newsId, 0, 'Bestehend', 'Text', '', 'Activated', 'NO', ['Klick'], ['javascript:alert(1)'], ['_blank'], $this->time()));
        $this->assertSame('', (string) $this->fetchRow($pn_config['newstable'], $newsId)['relatedlinks']);
    }

    #[Test]
    public function unknown_targets_fall_back_to_the_first_configured_one(): void
    {
        [$links] = pn_relatedlinks_from_input(['A'], ['https://a.example'], ['_evil" onmouseover="x'], ['_blank', '_main'], false);

        $this->assertSame('_blank', $links[0]['target']);
    }

    #[Test]
    public function legacy_line_format_is_still_displayed_but_never_unsafe_links(): void
    {
        $legacy = "Verein!@!@!https://verein.example.org!@!@!_blank\nBöse!@!@!javascript:alert(1)!@!@!_blank\n";

        $output = $this->renderNews($legacy);

        $this->assertStringContainsString('href="https://verein.example.org"', $output);
        $this->assertStringNotContainsString('javascript:', $output);
    }

    #[Test]
    public function migration_converts_legacy_links_to_json(): void
    {
        global $pn_config, $pn_handler;

        mysqli_query($pn_handler, "DELETE FROM pn_migrations WHERE name = '3.12-relatedlinks-json'");
        $userId = $this->insertTestUser('legacy_links', 'legacy_links@example.com');
        $newsId = $this->insertTestNews($userId, 0, 'Altformat', 'Text');
        $legacy = "Verein!@!@!https://verein.example.org!@!@!_main\nBöse!@!@!javascript:alert(1)!@!@!_blank\n";
        $stmt = mysqli_prepare($pn_handler, 'UPDATE ' . $pn_config['newstable'] . ' SET relatedlinks = ? WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'si', $legacy, $newsId);
        mysqli_stmt_execute($stmt);

        pn_run_migrations($pn_handler, $pn_config);

        $this->assertSame(
            '[{"title":"Verein","url":"https://verein.example.org","target":"_main"}]',
            (string) $this->fetchRow($pn_config['newstable'], $newsId)['relatedlinks'],
        );
    }

    #[Test]
    public function url_check_allows_only_http_and_relative_paths(): void
    {
        $this->assertTrue(pn_relatedlink_url_allowed('https://example.org/pfad?x=1', false));
        $this->assertTrue(pn_relatedlink_url_allowed('http://example.org', false));
        $this->assertTrue(pn_relatedlink_url_allowed('//cdn.example.org/a', false));
        $this->assertTrue(pn_relatedlink_url_allowed('news.php?newsid=2', true));
        $this->assertFalse(pn_relatedlink_url_allowed('news.php?newsid=2', false));
        $this->assertFalse(pn_relatedlink_url_allowed('ftp://example.org', true));
        $this->assertFalse(pn_relatedlink_url_allowed('javascript:alert(1)', true));
        $this->assertFalse(pn_relatedlink_url_allowed("java\tscript:alert(1)", true));
        $this->assertFalse(pn_relatedlink_url_allowed('', true));
    }
}
