<?php

declare(strict_types=1);

namespace PowerNews\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use PowerNews\Tests\DatabaseTestCase;

/**
 * Eine News in einer deaktivierten Kategorie bearbeiten: Bis 3.12 bot die Auswahl nur aktive
 * Kategorien an, die erste war vorgewählt, und beim Speichern wanderte die News still in eine
 * andere Kategorie.
 */
class DeactivatedCategoryTest extends DatabaseTestCase
{
    private int $active = 0;

    private int $inactive = 0;

    protected function setUp(): void
    {
        global $pnconfig;

        parent::setUp();
        $this->resetDatabase();
        $pnconfig['categories'] = 'YES';
        $this->active = $this->insertTestCategory('Aktuelles');
        $this->inactive = $this->insertTestCategory('Zeltlager 2019', 'Deactivated');
    }

    /**
     * @return array<string, string>
     */
    private static function time(): array
    {
        return ['day' => '1', 'month' => '6', 'year' => '2024', 'hour' => '12', 'min' => '0'];
    }

    #[Test]
    public function edit_form_keeps_the_deactivated_category_selected(): void
    {
        $newsId = $this->insertTestNews(1, $this->inactive, 'Altes Lager', 'Text');

        $form = $this->renderAdminPage('news_edit.inc.php', ['newsid' => (string) $newsId]);

        $this->assertStringContainsString('<option value="' . $this->inactive . '" selected>Zeltlager 2019 (' . \L_NEWS_CATINACTIVE . ')</option>', $form);
        $this->assertStringContainsString('<option value="' . $this->active . '">Aktuelles</option>', $form);
        $this->assertStringNotContainsString(\L_NEWS_CHOOSECAT, $form);
    }

    #[Test]
    public function saving_keeps_the_deactivated_category(): void
    {
        $newsId = $this->insertTestNews(1, $this->inactive, 'Altes Lager', 'Text');

        $output = $this->renderAdminPage('news_edit.inc.php', ['newsid' => (string) $newsId, 'edit' => 'YES'], [
            'catid' => (string) $this->inactive,
            'title' => 'Altes Lager, korrigiert',
            'text' => 'Text',
            'status' => 'Activated',
            'time' => self::time(),
        ]);

        $this->assertStringContainsString(\L_NEWS_NEWSEDITED, $output);
        $this->assertSame($this->inactive, (int) (new \news())->getnewsdata($newsId)['catid']);
    }

    #[Test]
    public function other_news_cannot_be_moved_into_a_deactivated_category(): void
    {
        $newsId = $this->insertTestNews(1, $this->active, 'Aktuell', 'Text');

        $error = (new \news())->editnews($newsId, $this->inactive, 'Aktuell', 'Text', '', 'Activated', 'NO', [], [], [], self::time());

        $this->assertSame(\L_NEWS_CATNOTACTIVE, $error);
        $this->assertSame($this->active, (int) (new \news())->getnewsdata($newsId)['catid']);
        $this->assertSame(\L_NEWS_CATNOTACTIVE, (new \news())->addnews('Neu', 'Text', $this->inactive, '', [], [], [], self::time()));
        $this->assertSame(\L_NEWS_CATNOTACTIVE, (new \news())->addnews('Neu', 'Text', 999999, '', [], [], [], self::time()));
    }

    #[Test]
    public function new_news_offer_only_active_categories(): void
    {
        $form = $this->renderAdminPage('news_add.inc.php', []);

        $this->assertStringContainsString('<option value="" selected>' . \L_NEWS_CHOOSECAT . '</option>', $form);
        $this->assertStringContainsString('>Aktuelles</option>', $form);
        $this->assertStringNotContainsString('Zeltlager 2019', $form);
    }

    #[Test]
    public function missing_category_forces_a_choice(): void
    {
        $newsId = $this->insertTestNews(1, 424242, 'Verwaist', 'Text');

        $form = $this->renderAdminPage('news_edit.inc.php', ['newsid' => (string) $newsId]);

        $this->assertStringContainsString('<option value="" selected>' . \L_NEWS_CHOOSECAT . '</option>', $form);
        $this->assertStringNotContainsString('" selected>Aktuelles', $form, 'Keine stille Vorauswahl der ersten Kategorie');
    }
}
