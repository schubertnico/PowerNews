<?php

declare(strict_types=1);

namespace PowerNews\Tests\Integration;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PowerNews\Tests\DatabaseTestCase;

/**
 * Übersichtsseiten der Admin-Bereiche, Hauptnavigation, Schnellzugriff und Unterseiten-Knöpfe
 * richten sich nach den Rechten des Kontos.
 */
class AdminSectionAccessTest extends DatabaseTestCase
{
    /** Rechte eines Redakteurs: News und Kommentare lesen und schreiben. */
    private const array EDITOR = [
        'canreadtemplates' => 'NO', 'canwritetemplates' => 'NO',
        'canreadconfig' => 'NO', 'canwriteconfig' => 'NO',
        'canreadusers' => 'NO', 'canwriteusers' => 'NO',
        'canreadpermissions' => 'NO', 'canwritepermissions' => 'NO',
        'canreadcategories' => 'NO', 'canwritecategories' => 'NO',
        'canreadnews' => 'YES', 'canwritenews' => 'YES',
        'canreadcomments' => 'YES', 'canwritecomments' => 'YES',
    ];

    /**
     * @return array<string, array{string, string}>
     */
    public static function sections(): array
    {
        return [
            'Templates' => ['templates', 'canreadtemplates'],
            'Benutzer' => ['users', 'canreadusers'],
            'Berechtigungen' => ['permissions', 'canreadpermissions'],
            'Kategorien' => ['categories', 'canreadcategories'],
            'News' => ['news', 'canreadnews'],
        ];
    }

    #[Test]
    #[DataProvider('sections')]
    public function overview_pages_need_the_read_permission(string $section, string $right): void
    {
        $denied = $this->renderAdminPage($section . '.inc.php', ['page' => $section], [], [$right => 'NO']);
        $this->assertStringContainsString(\L_ALL_ACCESSDENIED, $denied);
        $this->assertStringNotContainsString(\L_ALL_CHOOSESUBPAGE, $denied);

        $allowed = $this->renderAdminPage($section . '.inc.php', ['page' => $section], [], [$right => 'YES']);
        $this->assertStringContainsString(\L_ALL_CHOOSESUBPAGE, $allowed);
        $this->assertStringNotContainsString(\L_ALL_ACCESSDENIED, $allowed);
    }

    #[Test]
    public function other_section_is_open_for_every_admin(): void
    {
        $output = $this->renderAdminPage('other.inc.php', ['page' => 'other'], [], self::EDITOR);

        $this->assertStringContainsString(\L_ALL_CHOOSESUBPAGE, $output);
        $this->assertTrue(pnadmin_can_read(self::EDITOR, 'other'));
        $this->assertTrue(pnadmin_can_read(self::EDITOR, 'profile'));
    }

    #[Test]
    public function write_only_subpages_keep_their_own_check(): void
    {
        // Die Bereichsseite bindet die Unterseite ein; deren eigene Prüfung zählt.
        $output = $this->renderAdminPage('users_add.inc.php', ['page' => 'users', 'subpage' => 'add'], [], ['canreadusers' => 'NO', 'canwriteusers' => 'YES']);

        $this->assertStringContainsString('id="pn_nickname"', $output);
        $this->assertStringNotContainsString(\L_ALL_ACCESSDENIED, $output);
    }

    #[Test]
    public function navigation_shows_only_readable_sections(): void
    {
        $nav = pnadmin_nav(self::EDITOR, 'news');

        $this->assertStringContainsString('<a class="nav-link active" aria-current="page" href="index.php?page=news">' . \L_MENU_NEWS . '</a>', $nav);
        $this->assertStringContainsString('href="index.php?page=other"', $nav);
        foreach (['templates', 'users', 'permissions', 'configuration', 'categories'] as $hidden) {
            $this->assertStringNotContainsString('href="index.php?page=' . $hidden . '"', $nav, $hidden);
        }

        $all = pnadmin_nav(array_map(static fn (): string => 'YES', self::EDITOR), 'main');
        $this->assertSame(7, substr_count($all, 'class="nav-link"'));
    }

    #[Test]
    public function quick_links_follow_the_permissions(): void
    {
        $this->assertSame(
            [['index.php?page=news&subpage=add', \L_NEWS_WRITENEWS], ['index.php?page=news&subpage=show', \L_NEWS_SHOWNEWS]],
            pnadmin_quicklinks(self::EDITOR),
        );
        $this->assertSame([], pnadmin_quicklinks(['canreadconfig' => 'YES']), 'Ohne passendes Recht kein Schnellzugriff');
        $this->assertCount(3, pnadmin_quicklinks(['canwritenews' => 'YES', 'canreadnews' => 'YES', 'canreadusers' => 'YES']));
    }

    #[Test]
    public function subpage_buttons_follow_the_permissions(): void
    {
        $menus = new \menus();

        $news = $this->captureOutput(fn () => $menus->submenu('news', self::EDITOR));
        $this->assertSame(3, substr_count($news, 'class="btn btn-sm'));

        $users = $this->captureOutput(fn () => $menus->submenu('users', ['canreadusers' => 'NO', 'canwriteusers' => 'YES']));
        $this->assertStringContainsString('href="index.php?page=users&amp;subpage=add"', $users);
        $this->assertStringNotContainsString('subpage=show', $users);
        $this->assertStringNotContainsString('subpage=search', $users);

        $this->assertSame('', trim($this->captureOutput(fn () => $menus->submenu('templates', self::EDITOR))));
        $this->assertStringContainsString('subpage=help', $this->captureOutput(fn () => $menus->submenu('other', self::EDITOR)));
    }
}
