<?php

declare(strict_types=1);

namespace PowerNews\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use PowerNews\Tests\DatabaseTestCase;

/**
 * Regressionstests für B21: Zeichensatz-Kopfzeilen, Absender aus der Konfiguration und
 * keine leere Passwortzeile.
 */
class MailTest extends DatabaseTestCase
{
    #[Test]
    public function mails_declare_utf8_and_encode_the_sender_name(): void
    {
        $headers = pn_mail_headers('PowerNews Müller', 'news@example.org');

        $this->assertSame('1.0', $headers['MIME-Version']);
        $this->assertSame('text/plain; charset=UTF-8', $headers['Content-Type']);
        $this->assertSame('8bit', $headers['Content-Transfer-Encoding']);
        $this->assertStringContainsString('=?UTF-8?B?', $headers['From']);
        $this->assertSame('PowerNews Müller <news@example.org>', mb_decode_mimeheader($headers['From']));
        $this->assertStringEndsWith('<news@example.org>', $headers['From']);
    }

    #[Test]
    public function header_injection_via_addresses_is_impossible(): void
    {
        $this->assertFalse(pn_send_mail("opfer@example.org\r\nBcc: alle@example.org", 'Betreff', 'Text', 'PowerNews', 'news@example.org'));
        $this->assertFalse(pn_send_mail('opfer@example.org', 'Betreff', 'Text', 'PowerNews', "news@example.org\r\nBcc: x@example.org"));
        $this->assertStringNotContainsString("\n", pn_mail_headers("Name\r\nBcc: x@example.org", 'news@example.org')['From']);
    }

    #[Test]
    public function empty_password_line_is_removed(): void
    {
        $template = "Hallo {NICKNAME},\r\n\r\nNickname: {NICKNAME}\r\neMail: {EMAIL}\r\nPasswort: {PASSWORD}\r\n\r\nGruß";

        $without = pn_mail_text($template, ['NICKNAME' => 'max', 'EMAIL' => 'max@example.org', 'PASSWORD' => '']);
        $this->assertStringNotContainsString('Passwort', $without);
        $this->assertStringContainsString("eMail: max@example.org\n\nGruß", $without);

        $with = pn_mail_text($template, ['NICKNAME' => 'max', 'EMAIL' => 'max@example.org', 'PASSWORD' => 'Abc12345']);
        $this->assertStringContainsString('Passwort: Abc12345', $with);
    }

    #[Test]
    public function admin_edit_mail_contains_no_empty_password(): void
    {
        global $pn_handler, $pnconfig;

        // Template im Stand 3.11 (mit Passwortzeile) für den Test anlegen.
        $columns = ['title', 'message', 'headline', 'news', 'comment', 'usermenu', 'usermenu2', 'relatedlinks', 'commentform', 'registerform', 'loginform', 'logout', 'senddataform', 'profileform', 'archive', 'sendnewsform', 'addemail', 'editemail', 'registeremail', 'dataemail'];
        $values = array_fill(0, count($columns), 'x');
        $values[0] = 'Mailtest ' . uniqid();
        $values[17] = "Hallo {NICKNAME},\n\nNickname: {NICKNAME}\nPasswort: {PASSWORD}\n\nURL: {URL}";
        $stmt = mysqli_prepare($pn_handler, 'INSERT INTO pn_templates (' . implode(', ', $columns) . ') VALUES (' . implode(', ', array_fill(0, count($columns), '?')) . ')');
        mysqli_stmt_bind_param($stmt, str_repeat('s', count($values)), ...$values);
        mysqli_stmt_execute($stmt);
        $pnconfig['template'] = (int) mysqli_insert_id($pn_handler);
        $pnconfig['url'] = 'https://news.example.org';

        $mail = (new \template())->editemail('max', 'max@example.org', '');

        $this->assertIsString($mail);
        $this->assertStringNotContainsString('Passwort', $mail);
        $this->assertStringContainsString('URL: https://news.example.org', $mail);
        mysqli_query($pn_handler, 'DELETE FROM pn_templates WHERE id = ' . (int) $pnconfig['template']);
    }

    #[Test]
    public function send_mail_checkbox_is_not_preselected_when_editing(): void
    {
        $userId = $this->insertTestUser('mailcheck', 'mailcheck@example.com');

        $output = $this->renderAdminPage('users_edit.inc.php', ['userid' => (string) $userId]);

        $this->assertMatchesRegularExpression('/<input class="form-check-input" type="checkbox" name="sendemail" value="YES" id="pn_sendemail" aria-describedby="pn_sendemail_help">/', $output);
    }

    #[Test]
    public function start_page_warns_about_factory_mail_settings(): void
    {
        global $pnconfig;

        $pnconfig['url'] = 'http://www.powerscripts.org';
        $pnconfig['email'] = 'daemon@powerscripts.org';
        $this->assertStringContainsString(\L_ALL_DEFAULTCONFIGWARNING, $this->renderAdminPage('main.inc.php', []));

        $pnconfig['url'] = 'https://news.example.org';
        $pnconfig['email'] = 'news@example.org';
        $this->assertStringNotContainsString(\L_ALL_DEFAULTCONFIGWARNING, $this->renderAdminPage('main.inc.php', []));
    }
}
