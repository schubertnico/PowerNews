<?php

declare(strict_types=1);

namespace PowerNews\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Die Admin-Hilfe gibt es in drei Sprachen mit demselben Aufbau und dem Stand 3.12
 * (german-sie und english waren bis 3.12 die alte deutsche Hilfe von 2002).
 */
final class HelpFilesTest extends TestCase
{
    private static function help(string $language): string
    {
        return (string) file_get_contents(__DIR__ . '/../../pnadmin/lang/' . $language . '_help.php');
    }

    /**
     * @return list<string>
     */
    private static function ids(string $html): array
    {
        preg_match_all('/\bid="([^"]+)"/', $html, $matches);

        return $matches[1];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function languages(): array
    {
        return ['german-du' => ['german-du'], 'german-sie' => ['german-sie'], 'english' => ['english']];
    }

    #[Test]
    public function all_languages_share_the_same_anchors(): void
    {
        $reference = self::ids(self::help('german-du'));

        $this->assertContains('help-other-bbcode', $reference, 'Ziel des Links unter den News-Textfeldern');
        $this->assertContains('help-configuration-dateformat', $reference, 'Ziel des Links in der Konfiguration');
        $this->assertSame($reference, self::ids(self::help('german-sie')));
        $this->assertSame($reference, self::ids(self::help('english')));
    }

    #[Test]
    #[DataProvider('languages')]
    public function help_describes_the_current_behaviour(string $language): void
    {
        $html = self::help($language);

        $this->assertStringContainsString('86400', $html, 'Spamschutz 0 bis 86400 Sekunden');
        $this->assertStringNotContainsString('999', $html);
        $this->assertStringContainsString('%d.%m.%Y', $html, 'strftime-Schreibweise');
        $this->assertStringContainsString('<code>d.m.Y</code>', $html, 'date()-Schreibweise');
        $this->assertStringNotContainsString('target="_blank"', $html);
        $this->assertStringNotContainsString('<a name=', $html);
        $this->assertStringContainsString('{INVITELINK}', $html);
        $this->assertStringContainsString('48', $html, 'Einladung 48 Stunden');
    }

    #[Test]
    public function german_help_is_correct_about_templates_and_categories(): void
    {
        foreach (['german-du', 'german-sie'] as $language) {
            $html = self::help($language);
            $this->assertMatchesRegularExpression('/Default-Template<\/a> \(ID&nbsp;1\) (kannst Du|können Sie) editieren, aber nicht löschen/', $html, $language);
            $this->assertStringNotContainsString('weder editiert noch gelöscht', $html, $language);
            $this->assertStringNotContainsString('setzten', $html, $language);
            $this->assertMatchesRegularExpression('/Status der Kategorie auf <em>Deaktiviert<\/em>/', $html, $language);
            $this->assertStringNotContainsString('Headline', $html, $language);
            $this->assertStringContainsString('Einladung per E-Mail senden', $html, $language);
        }
    }

    #[Test]
    public function sie_help_does_not_use_du(): void
    {
        $text = strip_tags(self::help('german-sie'));

        $this->assertDoesNotMatchRegularExpression('/\b(Du|Dich|Dir|Dein\w*|kannst|willst|musst|legst|wählst|findest|suchst|gibst|filterst|bearbeitest|hast|Klicke|Wähle|Setze)\b/u', $text);
        $this->assertStringContainsString('Klicken Sie', $text);
    }
}
