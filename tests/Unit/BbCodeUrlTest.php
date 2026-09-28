<?php

declare(strict_types=1);

namespace PowerNews\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * BB-Code [url]: https-Adressen wurden bis 3.12 zu „http://https://…“, andere Schemata
 * dürfen nie zum Link werden.
 */
final class BbCodeUrlTest extends TestCase
{
    /**
     * Wie in pn_template::news(): erst maskieren, dann BB-Code auswerten.
     */
    private function render(string $text): string
    {
        return (new \pn_template())->bbreplace(htmlspecialchars($text, ENT_QUOTES, 'UTF-8'));
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function links(): array
    {
        return [
            'http' => ['[url]http://example.org/a?b=1&c=2[/url]', 'http://example.org/a?b=1&amp;c=2', 'http://example.org/a?b=1&amp;c=2'],
            'https' => ['[url]https://www.example.org/news[/url]', 'https://www.example.org/news', 'https://www.example.org/news'],
            'ohne Schema' => ['[url]www.example.org/pfad[/url]', 'https://www.example.org/pfad', 'www.example.org/pfad'],
            'ohne Schema mit Port' => ['[url]example.org:8080/x[/url]', 'https://example.org:8080/x', 'example.org:8080/x'],
            'Großschreibung' => ['[URL]https://example.org[/URL]', 'https://example.org', 'https://example.org'],
            'url= mit https' => ['[url=https://example.org/seite]Zur Seite[/url]', 'https://example.org/seite', 'Zur Seite'],
            'url= ohne Schema' => ['[url=example.org]Beispiel[/url]', 'https://example.org', 'Beispiel'],
            'url= in Anführungszeichen' => ['[url="https://example.org/x"]X[/url]', 'https://example.org/x', 'X'],
            'Umlaut im Pfad' => ['[url]https://example.org/Müller[/url]', 'https://example.org/M%C3%BCller', 'https://example.org/Müller'],
        ];
    }

    #[Test]
    #[DataProvider('links')]
    public function url_codes_become_http_or_https_links(string $input, string $href, string $label): void
    {
        $html = $this->render($input);

        $this->assertStringContainsString('<a href="' . $href . '" target="_blank" rel="noopener noreferrer">' . $label . '</a>', $html);
        $this->assertStringNotContainsString('http://https://', $html);
        $this->assertStringNotContainsString('[url', strtolower($html));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function forbidden(): array
    {
        return [
            'javascript' => ['[url]javascript:alert(1)[/url]'],
            'javascript groß' => ['[url]JavaScript:alert(1)[/url]'],
            'data' => ['[url]data:text/html;base64,PHNjcmlwdD4=[/url]'],
            'ftp' => ['[url]ftp://example.org/datei[/url]'],
            'mailto' => ['[url]mailto:a@example.org[/url]'],
            'url= javascript' => ['[url=javascript:alert(1)]Klick[/url]'],
            'url= data in Anführungszeichen' => ['[url="data:text/html,x"]Klick[/url]'],
            'Attribut einschleusen' => ['[url=https://example.org" onmouseover="alert(1)]Klick[/url]'],
            'Leerzeichen' => ['[url]https://example.org/a b[/url]'],
        ];
    }

    #[Test]
    #[DataProvider('forbidden')]
    public function other_schemes_never_become_links(string $input): void
    {
        $html = $this->render($input);

        $this->assertStringNotContainsString('<a ', $html);
        $this->assertStringNotContainsString('href=', $html);
        $this->assertSame(htmlspecialchars($input, ENT_QUOTES, 'UTF-8'), $html, 'Der BB-Code bleibt als Text stehen.');
    }

    #[Test]
    public function several_links_in_one_text_stay_separate(): void
    {
        $html = $this->render("[url]https://eins.example[/url] und [url=zwei.example]zwei[/url]\n[url]drei.example[/url]");

        $this->assertStringContainsString('<a href="https://eins.example"', $html);
        $this->assertStringContainsString('<a href="https://zwei.example" target="_blank" rel="noopener noreferrer">zwei</a>', $html);
        $this->assertStringContainsString('<br><a href="https://drei.example"', $html);
    }
}
