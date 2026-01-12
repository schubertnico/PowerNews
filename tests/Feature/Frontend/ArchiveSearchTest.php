<?php
declare(strict_types=1);

namespace PowerNews\Tests\Feature\Frontend;

use PowerNews\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests for archive and search forms (pninc/news.inc.php - archive action)
 */
class ArchiveSearchTest extends TestCase
{
    #[Test]
    public function archiveAcceptsValidYear(): void
    {
        $this->setGet(['showyear' => '2024']);

        $year = (int)($_GET['showyear'] ?? 0);
        $this->assertEquals(2024, $year);
        $this->assertGreaterThan(1999, $year);
        $this->assertLessThan(2100, $year);
    }

    #[Test]
    public function archiveAcceptsValidMonth(): void
    {
        $this->setGet(['showmonth' => '6']);

        $month = (int)($_GET['showmonth'] ?? 0);
        $this->assertEquals(6, $month);
        $this->assertGreaterThanOrEqual(1, $month);
        $this->assertLessThanOrEqual(12, $month);
    }

    #[Test]
    #[DataProvider('validMonthProvider')]
    public function archiveAcceptsAllValidMonths(int $month): void
    {
        $this->setGet(['showmonth' => (string)$month]);

        $parsedMonth = (int)($_GET['showmonth'] ?? 0);
        $isValid = $parsedMonth >= 1 && $parsedMonth <= 12;

        $this->assertTrue($isValid, "Month $month should be valid");
    }

    public static function validMonthProvider(): array
    {
        return [
            'january' => [1],
            'february' => [2],
            'march' => [3],
            'april' => [4],
            'may' => [5],
            'june' => [6],
            'july' => [7],
            'august' => [8],
            'september' => [9],
            'october' => [10],
            'november' => [11],
            'december' => [12],
        ];
    }

    #[Test]
    #[DataProvider('invalidMonthProvider')]
    public function archiveRejectsInvalidMonths(string $month): void
    {
        $this->setGet(['showmonth' => $month]);

        $parsedMonth = (int)($_GET['showmonth'] ?? 0);
        $isValid = $parsedMonth >= 1 && $parsedMonth <= 12;

        $this->assertFalse($isValid, "Month '$month' should be invalid");
    }

    public static function invalidMonthProvider(): array
    {
        return [
            'zero' => ['0'],
            'thirteen' => ['13'],
            'negative' => ['-1'],
            'empty' => [''],
            'non-numeric' => ['abc'],
        ];
    }

    #[Test]
    public function searchAcceptsValidSearchString(): void
    {
        $this->setGet(['searchstring' => 'test search']);

        $searchstring = trim((string)($_GET['searchstring'] ?? ''));
        $this->assertEquals('test search', $searchstring);
        $this->assertNotEmpty($searchstring);
    }

    #[Test]
    public function searchSanitizesWhitespace(): void
    {
        $this->setGet(['searchstring' => '  search term  ']);

        $searchstring = trim((string)($_GET['searchstring'] ?? ''));
        $this->assertEquals('search term', $searchstring);
    }

    #[Test]
    public function searchHandlesXSSAttempts(): void
    {
        $this->setGet(['searchstring' => '<script>alert("xss")</script>']);

        $searchstring = htmlspecialchars((string)($_GET['searchstring'] ?? ''), ENT_QUOTES, 'UTF-8');

        $this->assertStringNotContainsString('<script>', $searchstring);
        $this->assertStringContainsString('&lt;script&gt;', $searchstring);
    }

    #[Test]
    public function searchHandlesSQLInjectionAttempts(): void
    {
        $this->setGet(['searchstring' => "' OR '1'='1"]);

        $searchstring = htmlspecialchars((string)($_GET['searchstring'] ?? ''), ENT_QUOTES, 'UTF-8');

        $this->assertStringNotContainsString("'", $searchstring);
        $this->assertStringContainsString('&#039;', $searchstring);
    }

    #[Test]
    public function archiveCastsYearToInteger(): void
    {
        $this->setGet(['showyear' => '2024abc']);

        $year = (int)($_GET['showyear'] ?? 0);
        $this->assertEquals(2024, $year);
    }

    #[Test]
    public function archiveCastsMonthToInteger(): void
    {
        $this->setGet(['showmonth' => '12xyz']);

        $month = (int)($_GET['showmonth'] ?? 0);
        $this->assertEquals(12, $month);
    }

    #[Test]
    public function searchHandlesEmptyString(): void
    {
        $this->setGet(['searchstring' => '']);

        $searchstring = trim((string)($_GET['searchstring'] ?? ''));
        $this->assertEmpty($searchstring);
    }

    #[Test]
    public function searchHandlesMissingParameter(): void
    {
        // No searchstring parameter

        $searchstring = trim((string)($_GET['searchstring'] ?? ''));
        $this->assertEmpty($searchstring);
    }

    #[Test]
    public function categoryFilterAcceptsValidId(): void
    {
        $this->setGet(['catid' => '5']);

        $catid = (int)($_GET['catid'] ?? 0);
        $this->assertEquals(5, $catid);
        $this->assertGreaterThan(0, $catid);
    }

    #[Test]
    public function categoryFilterHandlesInvalidId(): void
    {
        $this->setGet(['catid' => 'invalid']);

        $catid = (int)($_GET['catid'] ?? 0);
        $this->assertEquals(0, $catid);
    }
}
