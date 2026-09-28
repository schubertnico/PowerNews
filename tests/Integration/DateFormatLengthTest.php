<?php

declare(strict_types=1);

namespace PowerNews\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use PowerNews\Tests\DatabaseTestCase;

/**
 * Datums- und Zeitformat: Das Formular erlaubte 50 Zeichen (wie die Spalte), gespeichert wurden
 * still nur 20. Jetzt gelten überall 50 Zeichen, längere Eingaben werden abgewiesen.
 */
class DateFormatLengthTest extends DatabaseTestCase
{
    /**
     * @param array<string, string> $overrides
     *
     * @return array<string, string>
     */
    private function configPost(array $overrides): array
    {
        return array_merge([
            'categories' => 'YES', 'comments' => 'YES', 'commentwriting' => 'Registered', 'moretext' => 'NO',
            'sendnews' => 'YES', 'newssending' => 'Registered', 'smilies' => 'Comments', 'bbcode' => 'Comments/News',
            'dateformat' => 'd.m.Y', 'timeformat' => 'H:i', 'template' => '1', 'url' => 'https://news.example.org',
            'email' => 'redaktion@example.org', 'headlines' => '10', 'news' => '10', 'spamprotection' => '30', 'relatedlinks' => 'NO',
        ], $overrides);
    }

    private function storedFormats(): array
    {
        global $pn_handler;

        return (array) mysqli_fetch_assoc(mysqli_query($pn_handler, 'SELECT dateformat, timeformat FROM pn_config'));
    }

    #[Test]
    public function formats_up_to_fifty_characters_are_saved_completely(): void
    {
        $date = 'l, \d\e\n j. F Y \(\K\W W\)';
        $time = 'H:i \U\h\r \(T\)';
        $this->assertGreaterThan(20, mb_strlen($date));

        $output = $this->renderAdminPage('configuration.inc.php', ['page' => 'configuration', 'edit' => 'YES'], $this->configPost(['dateformat' => $date, 'timeformat' => $time]));

        $this->assertStringContainsString(\L_CONF_EDITED, $output);
        $this->assertSame(['dateformat' => $date, 'timeformat' => $time], $this->storedFormats());

        $exact = str_repeat('d', 50);
        $this->renderAdminPage('configuration.inc.php', ['page' => 'configuration', 'edit' => 'YES'], $this->configPost(['dateformat' => $exact]));
        $this->assertSame($exact, $this->storedFormats()['dateformat']);
    }

    #[Test]
    public function longer_formats_are_rejected_instead_of_cut(): void
    {
        $before = $this->storedFormats();

        $output = $this->renderAdminPage('configuration.inc.php', ['page' => 'configuration', 'edit' => 'YES'], $this->configPost(['timeformat' => str_repeat('H', 51)]));

        $this->assertStringContainsString(sprintf(\L_CONF_FORMATTOOLONG, 50), $output);
        $this->assertStringNotContainsString(\L_CONF_EDITED, $output);
        $this->assertSame($before, $this->storedFormats());
    }

    #[Test]
    public function form_limits_both_fields_to_the_column_length(): void
    {
        $form = $this->renderAdminPage('configuration.inc.php', ['page' => 'configuration']);

        $this->assertStringContainsString('name="dateformat" id="cfg_dateformat" maxlength="50"', $form);
        $this->assertStringContainsString('name="timeformat" id="cfg_timeformat" maxlength="50"', $form);
        $this->assertSame(50, \ConfigData::FORMAT_MAX_LENGTH);
    }
}
