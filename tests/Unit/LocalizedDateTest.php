<?php

declare(strict_types=1);

namespace PowerNews\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Wochentage und Monatsnamen im Frontend folgen der Sprachdatei (bis 3.12 immer englisch,
 * weil DateTime::format() nur englische Namen kennt).
 */
final class LocalizedDateTest extends TestCase
{
    /** Sonntag, 14. März 2021, 12:05 Uhr (Europe/Berlin). */
    private const int SUNDAY_IN_MARCH = 1615719900;

    private function formatIn(string $language, string $format): string
    {
        // Argumente als Liste, ohne Shell: Unter Windows würde escapeshellarg() „%“ entfernen.
        $process = proc_open(
            [PHP_BINARY, __DIR__ . '/../Helpers/format-date.php', $language, (string) self::SUNDAY_IN_MARCH, $format],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
        );
        $this->assertIsResource($process);
        $output = (string) stream_get_contents($pipes[1]);
        $errors = (string) stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $this->assertSame(0, proc_close($process), $errors);

        return $output;
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function formats(): array
    {
        return [
            'Sie, date()-Format' => ['german-sie', 'l, j. F Y', 'Sonntag, 14. März 2021'],
            'Du, date()-Format kurz' => ['german-du', 'D, d. M Y', 'So, 14. Mär 2021'],
            'Sie, strftime-Format' => ['german-sie', '%A, %d. %B %Y', 'Sonntag, 14. März 2021'],
            'Sie, strftime kurz' => ['german-sie', '%a %d.%m.', 'So 14.03.'],
            'englisch' => ['english', 'l, F jS Y', 'Sunday, March 14th 2021'],
            'maskierte Buchstaben bleiben' => ['german-sie', '\\l\\D \\F: D', 'lD F: So'],
            'Uhrzeit unverändert' => ['german-du', '%H:%M', '12:05'],
        ];
    }

    #[Test]
    #[DataProvider('formats')]
    public function names_follow_the_language_file(string $language, string $format, string $expected): void
    {
        $this->assertSame($expected, $this->formatIn($language, $format));
    }

    #[Test]
    public function language_files_define_all_names(): void
    {
        foreach (['german-du', 'german-sie', 'english'] as $language) {
            $source = (string) file_get_contents(__DIR__ . '/../../pninc/lang/' . $language . '.php');
            $this->assertMatchesRegularExpression("/define\\('L_DATE_WEEKDAYS', \\[('[^']+', ){6}'[^']+'\\]\\);/u", $source, $language);
            $this->assertMatchesRegularExpression("/define\\('L_DATE_WEEKDAYS_SHORT', \\[('[^']+', ){6}'[^']+'\\]\\);/u", $source, $language);
            $this->assertMatchesRegularExpression("/define\\('L_DATE_MONTHS_SHORT', \\[('[^']+', ){11}'[^']+'\\]\\);/u", $source, $language);
        }
    }

    #[Test]
    public function in_process_english_names_are_used(): void
    {
        $previous = date_default_timezone_get();
        date_default_timezone_set('Europe/Berlin');

        try {
            $this->assertSame('Sun 14 Mar', pn_format_date(self::SUNDAY_IN_MARCH, 'D j M'));
        } finally {
            date_default_timezone_set($previous);
        }
    }
}
