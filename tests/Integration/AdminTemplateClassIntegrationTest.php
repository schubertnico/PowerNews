<?php

declare(strict_types=1);

namespace PowerNews\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use PowerNews\Tests\DatabaseTestCase;

class AdminTemplateClassIntegrationTest extends DatabaseTestCase
{
    private \template $template;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetDatabase();
        $this->template = new \template();
    }

    // ── addemail ──

    #[Test]
    public function addemail_replaces_nickname_placeholder(): void
    {
        $result = $this->template->addemail('JohnDoe', 'john@example.com', 'secret123');

        $this->assertIsString($result);
        $this->assertStringContainsString('JohnDoe', $result);
        $this->assertStringNotContainsString('{NICKNAME}', $result);
    }

    #[Test]
    public function addemail_replaces_email_placeholder(): void
    {
        $result = $this->template->addemail('user', 'user@example.com', 'pass');

        $this->assertIsString($result);
        $this->assertStringContainsString('user@example.com', $result);
        $this->assertStringNotContainsString('{EMAIL}', $result);
    }

    #[Test]
    public function addemail_replaces_password_placeholder(): void
    {
        $result = $this->template->addemail('user', 'user@example.com', 'mypassword');

        $this->assertIsString($result);
        $this->assertStringContainsString('mypassword', $result);
        $this->assertStringNotContainsString('{PASSWORD}', $result);
    }

    #[Test]
    public function addemail_replaces_url_placeholder(): void
    {
        global $pnconfig;

        $result = $this->template->addemail('user', 'user@example.com', 'pass');

        $this->assertIsString($result);
        $this->assertStringNotContainsString('{URL}', $result);
        if (!empty($pnconfig['url'])) {
            $this->assertStringContainsString($pnconfig['url'], $result);
        }
    }

    // ── editemail ──

    #[Test]
    public function editemail_replaces_all_placeholders(): void
    {
        $result = $this->template->editemail('EditUser', 'edit@example.com', 'editpass');

        $this->assertIsString($result);
        $this->assertStringContainsString('EditUser', $result);
        $this->assertStringContainsString('edit@example.com', $result);
        $this->assertStringContainsString('editpass', $result);
        $this->assertStringNotContainsString('{NICKNAME}', $result);
        $this->assertStringNotContainsString('{EMAIL}', $result);
        $this->assertStringNotContainsString('{PASSWORD}', $result);
        $this->assertStringNotContainsString('{URL}', $result);
    }

    // ── listtemplates ──

    #[Test]
    public function listtemplates_outputs_template_rows(): void
    {
        $output = $this->captureOutput(fn() => $this->template->listtemplates());

        // The standard template (id=1) exists from schema seed
        $this->assertStringContainsString('<tr>', $output);
        $this->assertStringContainsString('<a href=', $output);
    }

    #[Test]
    public function listtemplates_outputs_no_templates_when_table_empty(): void
    {
        global $pn_handler, $pn_config;

        mysqli_query($pn_handler, 'TRUNCATE TABLE ' . $pn_config['templatetable']);

        $output = $this->captureOutput(fn() => $this->template->listtemplates());

        $this->assertStringContainsString(L_TEMPL_NOTEMPLATES, $output);
    }

    // ── checktemplate ──

    #[Test]
    public function checktemplate_returns_empty_string_for_valid_template(): void
    {
        // Template id=1 exists from schema seed
        $result = $this->template->checktemplate(1);

        $this->assertSame('', $result);
    }

    #[Test]
    public function checktemplate_returns_error_for_invalid_template(): void
    {
        $result = $this->template->checktemplate(999999);

        $this->assertSame(L_TEMPL_RIGHTTEMPLATENEEDED, $result);
    }

    // ── gettemplatedata ──

    #[Test]
    public function gettemplatedata_returns_array_for_valid_template(): void
    {
        $data = $this->template->gettemplatedata(1);

        $this->assertIsArray($data);
        $this->assertNotEmpty($data);
        $this->assertArrayHasKey('title', $data);
        $this->assertArrayHasKey('message', $data);
        $this->assertArrayHasKey('headline', $data);
        $this->assertArrayHasKey('news', $data);
    }

    #[Test]
    public function gettemplatedata_returns_empty_array_for_invalid_template(): void
    {
        $data = $this->template->gettemplatedata(999999);

        $this->assertSame([], $data);
    }

    // ── edittemplate ──

    #[Test]
    public function edittemplate_blocks_delete_for_template_id_1(): void
    {
        // Default-Template (id=1) darf editiert werden, aber NICHT geloescht.
        // Loesch-Versuch muss eine Warnung produzieren und die Zeile NICHT entfernen.
        $data = $this->makeValidTemplateData();

        $output = $this->captureOutput(fn() => $this->template->edittemplate(1, 'YES', $data));

        $this->assertStringContainsString('Default-Template', $output);
        $this->assertStringContainsString('nicht gel', $output); // "nicht geloescht"

        // Zeile darf nicht weg sein.
        global $pn_handler, $pn_config;
        $r = mysqli_query($pn_handler, 'SELECT id FROM ' . $pn_config['templatetable'] . ' WHERE id = 1');
        $this->assertSame(1, mysqli_num_rows($r));
    }

    #[Test]
    public function edittemplate_allows_edit_of_template_id_1(): void
    {
        // Default-Template (id=1) darf jetzt editiert werden (vorher gesperrt).
        $data = $this->makeValidTemplateData('Default Edited Title');

        $output = $this->captureOutput(fn() => $this->template->edittemplate(1, 'NO', $data));

        $this->assertStringContainsString(L_TEMPL_TEMPLATEEDITED, $output);
    }

    #[Test]
    public function edittemplate_outputs_insert_all_when_data_invalid(): void
    {
        // Add a template first so we can edit it (not id=1)
        $templateId = $this->addTestTemplate('Editable Template');

        $invalidData = new \TemplateData(); // all empty = invalid

        $output = $this->captureOutput(fn() => $this->template->edittemplate($templateId, 'NO', $invalidData));

        $this->assertStringContainsString(L_TEMPL_INSERTALL, $output);
    }

    #[Test]
    public function edittemplate_outputs_edited_message_on_valid_edit(): void
    {
        $templateId = $this->addTestTemplate('To Edit');
        $data = $this->makeValidTemplateData('Edited Title');

        $output = $this->captureOutput(fn() => $this->template->edittemplate($templateId, 'NO', $data));

        $this->assertStringContainsString(L_TEMPL_TEMPLATEEDITED, $output);
    }

    #[Test]
    public function edittemplate_outputs_deleted_message_on_delete(): void
    {
        $templateId = $this->addTestTemplate('To Delete');
        $data = $this->makeValidTemplateData();

        $output = $this->captureOutput(fn() => $this->template->edittemplate($templateId, 'YES', $data));

        $this->assertStringContainsString(L_TEMPL_TEMPLATEDELETED, $output);
    }

    #[Test]
    public function edittemplate_delete_removes_from_database(): void
    {
        $templateId = $this->addTestTemplate('Delete Me');
        $data = $this->makeValidTemplateData();

        $this->template->edittemplate($templateId, 'YES', $data);

        $result = $this->template->checktemplate($templateId);
        $this->assertSame(L_TEMPL_RIGHTTEMPLATENEEDED, $result);
    }

    // ── addtemplate ──

    #[Test]
    public function addtemplate_outputs_title_needed_when_title_empty(): void
    {
        $this->setPost(['pndata' => ['title' => '']]);

        $output = $this->captureOutput(fn() => $this->template->addtemplate());

        $this->assertStringContainsString(L_TEMPL_TITLENEEDED, $output);
    }

    #[Test]
    public function addtemplate_outputs_added_message_on_valid_title(): void
    {
        $this->setPost(['pndata' => ['title' => 'Brand New Template']]);

        $output = $this->captureOutput(fn() => $this->template->addtemplate());

        $this->assertStringContainsString(L_TEMPL_TEMPLATEADDED, $output);
    }

    #[Test]
    public function addtemplate_outputs_already_exists_on_duplicate(): void
    {
        // Get the standard template title
        $standardData = $this->template->gettemplatedata(1);
        $existingTitle = $standardData['title'];

        $this->setPost(['pndata' => ['title' => $existingTitle]]);

        $output = $this->captureOutput(fn() => $this->template->addtemplate());

        $this->assertStringContainsString(L_TEMPL_TEMPLATEALREADYEXISTS, $output);
    }

    #[Test]
    public function addtemplate_creates_template_in_database(): void
    {
        global $pn_handler, $pn_config;

        $this->setPost(['pndata' => ['title' => 'DB Check Template']]);
        $this->template->addtemplate();

        $stmt = mysqli_prepare($pn_handler, 'SELECT * FROM ' . $pn_config['templatetable'] . ' WHERE title = ?');
        $title = 'DB Check Template';
        mysqli_stmt_bind_param($stmt, 's', $title);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $this->assertSame(1, mysqli_num_rows($result));
    }

    // ── Helper methods ──

    private function addTestTemplate(string $title): int
    {
        global $pn_handler, $pn_config;

        // Copy from standard template (id=1)
        $result = mysqli_query($pn_handler, 'SELECT * FROM ' . $pn_config['templatetable'] . " WHERE id = '1'");
        $row = mysqli_fetch_array($result);

        $stmt = mysqli_prepare($pn_handler, 'INSERT INTO ' . $pn_config['templatetable'] . ' (title, message, headline, news, comment, usermenu, usermenu2, relatedlinks, commentform, registerform, loginform, logout, senddataform, profileform, archive, sendnewsform, addemail, editemail, registeremail, dataemail) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        mysqli_stmt_bind_param(
            $stmt,
            'ssssssssssssssssssss',
            $title,
            $row['message'],
            $row['headline'],
            $row['news'],
            $row['comment'],
            $row['usermenu'],
            $row['usermenu2'],
            $row['relatedlinks'],
            $row['commentform'],
            $row['registerform'],
            $row['loginform'],
            $row['logout'],
            $row['senddataform'],
            $row['profileform'],
            $row['archive'],
            $row['sendnewsform'],
            $row['addemail'],
            $row['editemail'],
            $row['registeremail'],
            $row['dataemail'],
        );
        mysqli_stmt_execute($stmt);

        return (int) mysqli_insert_id($pn_handler);
    }

    private function makeValidTemplateData(string $title = 'Valid Title'): \TemplateData
    {
        return new \TemplateData(
            title: $title,
            message: 'message content',
            headline: 'headline content',
            news: 'news content',
            comment: 'comment content',
            usermenu: 'usermenu content',
            usermenu2: 'usermenu2 content',
            relatedlinks: 'relatedlinks content',
            commentform: 'commentform content',
            registerform: 'registerform content',
            loginform: 'loginform content',
            logout: 'logout content',
            senddataform: 'senddataform content',
            profileform: 'profileform content',
            archive: 'archive content',
            sendnewsform: 'sendnewsform content',
            addemail: 'addemail content',
            editemail: 'editemail content',
            registeremail: 'registeremail content',
            dataemail: 'dataemail content',
        );
    }
}
