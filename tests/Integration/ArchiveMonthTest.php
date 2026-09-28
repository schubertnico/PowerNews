<?php

declare(strict_types=1);

namespace PowerNews\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use PowerNews\Tests\DatabaseTestCase;

/**
 * Archiv: Nach einer Suche springt die Monatsauswahl nicht mehr auf den laufenden Monat.
 */
class ArchiveMonthTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->resetDatabase();
        // Älteste News im März 2021, damit das Jahr 2021 in der Auswahl steht.
        $this->insertTestNews(1, 0, 'Frühlingsfest', 'Blumen und Musik', 'Activated', mktime(12, 0, 0, 3, 14, 2021));
    }

    private function archive(): string
    {
        return $this->captureOutput(fn () => (new \pn_news())->archive());
    }

    #[Test]
    public function search_keeps_the_chosen_month_and_year(): void
    {
        $this->setPost(['pndata' => ['showyear' => '2021', 'showmonth' => '3']]);
        $month = $this->archive();
        $this->assertStringContainsString('Frühlingsfest', $month);
        $this->assertStringContainsString('<option value="3" selected>', $month);
        $this->assertStringContainsString('<option value="2021" selected>', $month);

        // Die Suche schickt nur den Suchbegriff mit.
        $this->setGet(['pndata' => ['type' => 'search']]);
        $this->setPost(['pndata' => ['searchstring' => 'Blumen']]);
        $search = $this->archive();

        $this->assertStringContainsString('Frühlingsfest', $search);
        $this->assertStringContainsString('<option value="3" selected>', $search);
        $this->assertStringContainsString('<option value="2021" selected>', $search);
        $this->assertStringContainsString('value="Blumen"', $search);
    }

    #[Test]
    public function without_a_choice_the_current_month_is_shown(): void
    {
        $this->setGet(['pndata' => ['type' => 'search']]);
        $this->setPost(['pndata' => ['searchstring' => 'Blumen']]);
        $output = $this->archive();

        $this->assertStringContainsString('<option value="' . (int) date('n') . '" selected>', $output);
        $this->assertStringContainsString('<option value="' . date('Y') . '" selected>', $output);
    }

    #[Test]
    public function invalid_values_are_ignored(): void
    {
        $this->assertSame([date('Y'), date('m')], \pn_news::archivemonth(['showyear' => '2021', 'showmonth' => '13'], time()));
        $this->assertSame([date('Y'), date('m')], \pn_news::archivemonth(['showyear' => 'x', 'showmonth' => '3'], time()));
        $this->assertSame(['2022', '07'], \pn_news::archivemonth(['showyear' => '2022', 'showmonth' => '7'], time()));
        $this->assertSame(['2022', '07'], \pn_news::archivemonth([], time()), 'gemerkt');
    }
}
