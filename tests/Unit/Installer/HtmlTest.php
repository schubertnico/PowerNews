<?php

declare(strict_types=1);

namespace PowerNews\Tests\Unit\Installer;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PowerNews\Installer\Html;

require_once __DIR__ . '/../../../pninc/installer/autoload.php';

/**
 * Formularbausteine: eindeutige ids, Fehlermeldung je Feld, Maskierung.
 */
final class HtmlTest extends TestCase
{
    #[Test]
    public function inputHasIdLabelAndNoErrorWhenValid(): void
    {
        $html = Html::input('db_host', 'Datenbankserver', 'localhost', [], ['required' => true, 'maxlength' => 255], 'Meist localhost');

        $this->assertStringContainsString('<label for="db_host"', $html);
        $this->assertStringContainsString('id="db_host" name="db_host"', $html);
        $this->assertStringContainsString('value="localhost"', $html);
        $this->assertStringContainsString(' required', $html);
        $this->assertStringContainsString('maxlength="255"', $html);
        $this->assertStringContainsString('id="db_host-help"', $html);
        $this->assertStringContainsString('aria-describedby="db_host-help"', $html);
        $this->assertStringNotContainsString('db_host-error', $html);
        $this->assertStringNotContainsString('is-invalid', $html);
    }

    #[Test]
    public function errorIsShownBelowTheFieldWithItsOwnId(): void
    {
        $html = Html::input('db_name', 'Name', 'x', ['db_name' => 'Die Datenbank existiert nicht.']);

        $this->assertStringContainsString('class="form-control is-invalid"', $html);
        $this->assertStringContainsString('aria-invalid="true"', $html);
        $this->assertStringContainsString('<div id="db_name-error" class="invalid-feedback">Die Datenbank existiert nicht.</div>', $html);
        $this->assertStringContainsString('aria-describedby="db_name-error"', $html);
    }

    #[Test]
    public function valuesAndMessagesAreEscaped(): void
    {
        $html = Html::input('site_url', 'URL <b>', '"><script>alert(1)</script>', ['site_url' => '<i>Fehler</i>']);

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('value="&quot;&gt;&lt;script&gt;alert(1)&lt;/script&gt;"', $html);
        $this->assertStringContainsString('URL &lt;b&gt;', $html);
        $this->assertStringContainsString('&lt;i&gt;Fehler&lt;/i&gt;', $html);
    }

    #[Test]
    public function falseAttributesAreOmittedAndTrueAttributesHaveNoValue(): void
    {
        $html = Html::input('db_password', 'Passwort', '', [], ['type' => 'password', 'readonly' => false, 'autofocus' => true]);

        $this->assertStringContainsString('type="password"', $html);
        $this->assertStringNotContainsString('readonly', $html);
        $this->assertStringContainsString(' autofocus', $html);
        $this->assertStringNotContainsString('autofocus="', $html);
    }

    #[Test]
    public function selectMarksTheChosenOption(): void
    {
        $html = Html::select('site_language', 'Sprache', ['german-du' => 'Deutsch (Du)', 'english' => 'English'], 'english', ['site_language' => 'Bitte wählen']);

        $this->assertStringContainsString('<select id="site_language" name="site_language"', $html);
        $this->assertStringContainsString('<option value="english" selected>English</option>', $html);
        $this->assertStringContainsString('<option value="german-du">Deutsch (Du)</option>', $html);
        $this->assertStringContainsString('id="site_language-error"', $html);
    }

    #[Test]
    public function alertIsEmptyWithoutMessageAndUsesKnownTypesOnly(): void
    {
        $this->assertSame('', Html::alert('', 'danger', 'installer-message'));
        $this->assertStringContainsString('id="installer-message" class="alert alert-danger" role="alert"', Html::alert('Fehler', 'danger', 'installer-message'));
        $this->assertStringContainsString('class="alert alert-info" role="status"', Html::alert('x', 'evil" onclick="x', 'a'));
    }

    #[Test]
    public function csrfFieldIsHidden(): void
    {
        $this->assertSame('<input type="hidden" name="csrf_token" value="abc&quot;">', Html::csrfField('abc"'));
    }
}
