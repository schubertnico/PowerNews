<?php

declare(strict_types=1);

namespace PowerNews\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use PowerNews\Tests\DatabaseTestCase;

/**
 * Regressionstests für das Default-Template 3.12 und seine Migration: B13 (Archiv), B15
 * (Pseudo-Umlaute), B29 (Meldungstypen), B44 (ICQ, „Passwort vergessen“) und B48
 * ({CATPIC}, Kommentarlink).
 */
class DefaultTemplateTest extends DatabaseTestCase
{
    private int $templateId = 0;

    protected function setUp(): void
    {
        global $pn_handler, $pnconfig;

        parent::setUp();

        // Eigenes Template mit dem Inhalt 3.12, damit die Tests nicht vom Zustand der
        // Test-Datenbank abhängen.
        $fields = pn_default_template();
        $columns = array_merge(['title'], array_keys($fields));
        $values = array_merge(['Test 3.12 ' . uniqid()], array_values($fields));
        $stmt = mysqli_prepare($pn_handler, 'INSERT INTO pn_templates (' . implode(', ', $columns) . ') VALUES (' . implode(', ', array_fill(0, count($columns), '?')) . ')');
        mysqli_stmt_bind_param($stmt, str_repeat('s', count($values)), ...$values);
        mysqli_stmt_execute($stmt);
        $this->templateId = (int) mysqli_insert_id($pn_handler);
        $pnconfig['template'] = $this->templateId;
    }

    protected function tearDown(): void
    {
        global $pn_handler;

        mysqli_query($pn_handler, 'DELETE FROM pn_templates WHERE id = ' . $this->templateId);
        parent::tearDown();
    }

    #[Test]
    public function messages_show_their_type_and_a_separate_link_button(): void
    {
        $template = new \pn_template();

        $success = $this->captureOutput(fn () => $template->message('Gespeichert', 'user.php?page=profile&x=1', 'success'));
        $this->assertStringContainsString('alert-success', $success);
        $this->assertStringContainsString(\L_MSG_SUCCESS, $success);
        $this->assertStringContainsString('<a href="user.php?page=profile&amp;x=1" class="btn btn-sm btn-outline-dark">' . \L_MSG_CONTINUE . '</a>', $success);
        $this->assertStringNotContainsString('alert-link', $success, 'Der Meldungstext ist kein Link mehr.');

        $error = $this->captureOutput(fn () => $template->message('Falsch', 'index.php', 'danger'));
        $this->assertStringContainsString('alert-danger', $error);
        $this->assertStringContainsString(\L_MSG_DANGER, $error);

        $unknown = $this->captureOutput(fn () => $template->message('?', 'index.php', 'fantasie'));
        $this->assertStringContainsString('alert-info', $unknown);
    }

    #[Test]
    public function login_failure_is_shown_as_error(): void
    {
        $this->insertTestUser('typtest', 'typtest@example.com', 'richtig-123');
        $this->setGet(['page' => 'login', 'pndata' => ['login' => 'YES']]);
        $this->setPost(['pndata' => ['nickname' => 'typtest', 'password' => 'falsch']]);

        $output = $this->captureOutput(fn () => (new \pn_user())->login());

        $this->assertStringContainsString('alert-danger', $output);
        $this->assertStringContainsString(\L_USR_LOGINFAILED, $output);
    }

    #[Test]
    public function comment_link_only_appears_when_comments_are_enabled(): void
    {
        global $pnconfig;

        $template = new \pn_template();

        $pnconfig['comments'] = 'NO';
        $off = $this->captureOutput(fn () => $template->news(5, 'Autor', time() - 60, 'Allgemein', 'T', 'Text', 3, 'NO'));
        $this->assertStringNotContainsString('showcomments=YES', $off);
        $this->assertStringNotContainsString('COMMENTS_', $off);

        $pnconfig['comments'] = 'YES';
        $on = $this->captureOutput(fn () => $template->news(5, 'Autor', time() - 60, 'Allgemein', 'T', 'Text', 3, 'NO'));
        $this->assertStringContainsString('news.php?newsid=5&showcomments=YES', $on);
        $this->assertStringNotContainsString('COMMENTS_', $on);
    }

    #[Test]
    public function category_picture_is_rendered_or_removed(): void
    {
        global $pnconfig;

        $template = new \pn_template();
        $category = ['id' => 3, 'name' => 'Sport', 'picture' => 'cat_0123456789abcdef.png'];

        $pnconfig['categorypics'] = 'YES';
        $with = $this->captureOutput(fn () => $template->news(6, 'Autor', time() - 60, $category, 'T', 'Text', 0, 'NO'));
        $this->assertStringContainsString('<img src="./pngfx/categories/cat_0123456789abcdef.png"', $with);

        $pnconfig['categorypics'] = 'NO';
        $without = $this->captureOutput(fn () => $template->news(6, 'Autor', time() - 60, $category, 'T', 'Text', 0, 'NO'));
        $this->assertStringNotContainsString('<img', $without);
        $this->assertStringNotContainsString('{CATPIC}', $without);
    }

    #[Test]
    public function archive_uses_translated_months_and_reports_empty_months(): void
    {
        $this->setPost(['pndata' => ['showyear' => '1999', 'showmonth' => '3']]);

        $output = $this->captureOutput(fn () => (new \pn_news())->archive());

        $this->assertStringContainsString('<select class="form-select" name="pndata[showmonth]" id="pn_showmonth">', $output);
        $this->assertStringContainsString('<option value="3" selected>' . \L_TEMPL_MARCH . '</option>', $output);
        $this->assertStringContainsString('id="pn_showyear"', $output);
        $this->assertStringContainsString('<label for="pn_showmonth"', $output);
        $this->assertStringContainsString(\L_NEWS_NONEWSINMONTH, $output);
    }

    #[Test]
    public function new_default_template_has_no_icq_and_no_pseudo_umlauts(): void
    {
        $fields = pn_default_template();

        $this->assertStringNotContainsString('ICQ', $fields['profileform']);
        $this->assertStringContainsString('Passwort vergessen', $fields['usermenu']);
        $this->assertStringContainsString('{RESETLINK}', $fields['dataemail']);
        $this->assertStringNotContainsString('{PASSWORD}', $fields['dataemail']);
        foreach ($fields as $name => $content) {
            $this->assertDoesNotMatchRegularExpression('/\b(ausfuehr|fuer|ueber|pruef|zurueck)\w*/i', $content, $name);
            $this->assertStringNotContainsString('eMail', $content, $name);
        }
    }

    #[Test]
    public function profile_keeps_icq_when_the_template_has_no_field(): void
    {
        global $pn_handler, $pn_config;

        $userId = $this->insertTestUser('icqnutzer', 'icqnutzer@example.com');
        mysqli_query($pn_handler, 'UPDATE ' . $pn_config['usertable'] . ' SET icq = 123456 WHERE id = ' . $userId);
        $this->loginAsUser($userId, 'icqnutzer', 'icqnutzer@example.com');
        global $pnuser;
        $pnuser['icq'] = 123456;

        $this->setGet(['pndata' => ['send' => 'YES']]);
        $this->setPost(['pndata' => ['nickname' => 'icqnutzer', 'email' => 'icqnutzer@example.com', 'realname' => 'Ina']]);
        $this->captureOutput(fn () => (new \pn_user())->profile());

        $this->assertSame(123456, (int) $this->fetchRow($pn_config['usertable'], $userId)['icq']);
    }

    #[Test]
    public function migration_updates_only_unmodified_fields_of_the_311_template(): void
    {
        global $pn_handler, $pn_config;

        // 3.11-Stand des Default-Templates aus der powernews.sql von 3.11 in eine eigene Tabelle laden.
        $dump = str_replace("\r\n", "\n", (string) file_get_contents(__DIR__ . '/../Fixtures/powernews-3.11.sql'));
        $this->assertSame(1, preg_match('/^INSERT INTO `pn_templates` VALUES \(1,.*\);$/m', $dump, $match));
        mysqli_query($pn_handler, 'DROP TABLE IF EXISTS pn_templates_legacy');
        mysqli_query($pn_handler, 'CREATE TABLE pn_templates_legacy LIKE pn_templates');
        mysqli_query($pn_handler, str_replace('INSERT INTO `pn_templates`', 'INSERT INTO `pn_templates_legacy`', rtrim($match[0], ';')));

        // Ein Betreiber hat das Feld „comment“ angepasst.
        mysqli_query($pn_handler, "UPDATE pn_templates_legacy SET comment = '<div class=\"eigenes\">{TEXT}</div>' WHERE id = 1");

        $config = $pn_config;
        $config['templatetable'] = 'pn_templates_legacy';
        $report = pn_migration_default_template($pn_handler, $config);

        $row = (array) mysqli_fetch_assoc(mysqli_query($pn_handler, 'SELECT * FROM pn_templates_legacy WHERE id = 1'));
        $fields = pn_default_template();
        $this->assertSame($fields['message'], $row['message']);
        $this->assertSame($fields['profileform'], $row['profileform']);
        $this->assertSame($fields['dataemail'], $row['dataemail']);
        $this->assertSame('<div class="eigenes">{TEXT}</div>', $row['comment'], 'Angepasste Felder bleiben unverändert.');
        $this->assertStringContainsString('Template-Felder', $report);

        mysqli_query($pn_handler, 'DROP TABLE pn_templates_legacy');
    }
}
