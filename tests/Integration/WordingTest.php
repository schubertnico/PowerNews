<?php

declare(strict_types=1);

namespace PowerNews\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use PowerNews\Tests\DatabaseTestCase;

/**
 * Kleinigkeiten in den Texten: Schlagzeilen statt Headlines, Schlusspunkt, lesbare Linkziele.
 */
class WordingTest extends DatabaseTestCase
{
    /**
     * @return array<string, string> Konstante => Text
     */
    private static function constants(string $file): array
    {
        preg_match_all("/^define\\('([A-Z0-9_]+)', '((?:[^'\\\\]|\\\\.)*)'\\);$/m", (string) file_get_contents(__DIR__ . '/../../' . $file), $matches);

        return array_combine($matches[1], array_map('stripslashes', $matches[2]));
    }

    #[Test]
    public function german_texts_say_schlagzeilen(): void
    {
        foreach (['pninc/lang/german-du.php', 'pninc/lang/german-sie.php', 'pnadmin/lang/german-du.php', 'pnadmin/lang/german-sie.php'] as $file) {
            foreach (self::constants($file) as $name => $text) {
                $this->assertStringNotContainsStringIgnoringCase('headline', $text, $file . ': ' . $name);
            }
        }

        $this->assertSame('Keine Schlagzeilen vorhanden', self::constants('pninc/lang/german-sie.php')['L_NEWS_NOHEADLINES']);
        $this->assertSame('Schlagzeilen', self::constants('pnadmin/lang/german-du.php')['L_CONF_HEADLINES']);
        $this->assertSame('Schlagzeilen', self::constants('pnadmin/lang/german-sie.php')['L_TEMPL_HEADLINES']);
    }

    #[Test]
    public function registration_message_ends_with_a_full_stop(): void
    {
        foreach (['german-du', 'german-sie', 'english'] as $language) {
            $this->assertStringEndsWith('.', self::constants('pninc/lang/' . $language . '.php')['L_USR_REGISTERED'], $language);
        }
    }

    #[Test]
    public function link_targets_have_readable_labels(): void
    {
        $this->assertSame(\L_RL_TARGET_BLANK, pn_relatedlink_target_label('_blank'));
        $this->assertSame(\L_RL_TARGET_SELF, pn_relatedlink_target_label('_self'));
        $this->assertSame(\L_RL_TARGET_SELF, pn_relatedlink_target_label('_main'), '„_main“ ist das Hauptfenster');
        $this->assertSame(sprintf(\L_RL_TARGET_NAMED, 'vorschau'), pn_relatedlink_target_label('vorschau'));
        $this->assertSame('_self', pn_relatedlink_target('_main'));
        $this->assertSame('_blank', pn_relatedlink_target(''));

        foreach (['pninc/lang/german-sie.php', 'pnadmin/lang/german-du.php'] as $file) {
            $this->assertSame('Neues Fenster', self::constants($file)['L_RL_TARGET_BLANK'], $file);
            $this->assertSame('Gleiches Fenster', self::constants($file)['L_RL_TARGET_SELF'], $file);
        }
    }

    #[Test]
    public function forms_show_labels_instead_of_raw_targets(): void
    {
        global $pnconfig;

        $pnconfig['relatedlinks'] = 'YES';
        $pnconfig['relatedlinks_num'] = 1;
        $admin = $this->renderAdminPage('news_add.inc.php', []);
        $this->assertStringContainsString('<option value="_blank">' . \L_RL_TARGET_BLANK . '</option>', $admin);
        $this->assertStringContainsString('<option value="_main">' . \L_RL_TARGET_SELF . '</option>', $admin);
        $this->assertStringNotContainsString('>_blank<', $admin);

        $pnconfig['sendnews'] = 'YES';
        $pnconfig['newssending'] = 'Guests/Registered';
        $pnconfig['categories'] = 'NO';
        $front = $this->captureOutput(fn () => (new \pn_news())->sendnews());
        $this->assertStringContainsString('<option value="_blank">' . \L_RL_TARGET_BLANK . '</option>', $front);
        $this->assertStringNotContainsString('>_main<', $front);
    }
}
