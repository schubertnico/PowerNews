<?php

declare(strict_types=1);

namespace PowerNews\Tests\Unit\Installer;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PowerNews\Installer\View;
use PowerNews\Installer\Wizard;
use PowerNews\LocalConfig;

require_once __DIR__ . '/../../../pninc/installer/autoload.php';

/**
 * Schritt 3 („E-Mail-Versand“) und die Zusammenfassung in Schritt 5, so wie sie
 * der Browser bekommt: Feld-IDs für Tests und Videoaufnahmen, kein Passwort im HTML,
 * kein Inline-JavaScript.
 */
final class MailTemplatesTest extends TestCase
{
    private const array SMTP = ['transport' => 'smtp', 'host' => 'smtp.example.org', 'port' => 587, 'encryption' => 'starttls', 'user' => 'news@example.org', 'password' => 'Postfach-Geheim-26'];

    #[Test]
    public function websiteStepOffersTheMailSection(): void
    {
        $html = $this->website([], null, false, '');

        foreach (['mail_transport', 'smtp_host', 'smtp_port', 'smtp_encryption', 'smtp_user', 'smtp_password'] as $field) {
            $this->assertMatchesRegularExpression('/<(input|select) id="' . $field . '" name="' . $field . '"/', $html, $field);
        }
        $this->assertStringContainsString('<option value="mail" selected>PHP-mail des Servers</option>', $html);
        $this->assertStringContainsString('<option value="smtp">SMTP-Server</option>', $html);
        $this->assertStringContainsString('<option value="starttls" selected>STARTTLS (meist Port 587)</option>', $html);
        $this->assertStringContainsString('<p class="small mb-3" id="smtp-help">Die Angaben finden Sie im Kundenmenü Ihres Hosters beim E-Mail-Postfach.</p>', $html);
        $this->assertMatchesRegularExpression('/<button type="submit" name="action" value="mail_test" class="btn btn-outline-primary" id="btn-smtp-test">Test-Mail senden<\/button>/', $html);
        $this->assertStringContainsString('data-default-ports="{&quot;none&quot;:25,&quot;starttls&quot;:587,&quot;ssl&quot;:465}"', $html);
        $this->assertStringContainsString('<fieldset class="border rounded p-3 mb-3" id="smtp-fields">', $html, 'Ohne JavaScript sind alle Felder sichtbar');
        $this->assertStringNotContainsString('id="smtp-test-result"', $html);
        $this->assertStringContainsString('an die Absenderadresse.', $html);
    }

    #[Test]
    public function enterKeySubmitsNextAndNotTheTestMail(): void
    {
        $html = $this->website([], null, false, '');

        $this->assertLessThan(
            (int) strpos($html, 'id="btn-smtp-test"'),
            (int) strpos($html, 'id="btn-website-next"'),
            'Die erste Schaltfläche im Formular ist „Weiter“',
        );
    }

    #[Test]
    public function testResultAndStoredPasswordAreShownWithoutThePassword(): void
    {
        $html = $this->website(
            ['mail_transport' => 'smtp', 'smtp_host' => 'smtp.example.org', 'smtp_port' => '465', 'smtp_encryption' => 'ssl', 'smtp_user' => 'news@example.org'],
            ['type' => 'danger', 'message' => 'Die Test-Mail an admin@example.org konnte nicht verschickt werden. <b>x</b>'],
            true,
            'admin@example.org',
        );

        $this->assertStringContainsString('<div id="smtp-test-result" class="alert alert-danger" role="alert">Die Test-Mail an admin@example.org konnte nicht verschickt werden. &lt;b&gt;x&lt;/b&gt;</div>', $html);
        $this->assertStringContainsString('<option value="smtp" selected>SMTP-Server</option>', $html);
        $this->assertStringContainsString('id="smtp_host" name="smtp_host" class="form-control" value="smtp.example.org"', $html);
        $this->assertStringContainsString('id="smtp_password" name="smtp_password" class="form-control" value=""', $html);
        $this->assertStringContainsString('Leer lassen, um das bereits eingegebene Passwort zu behalten.', $html);
        $this->assertStringContainsString('an die E-Mail-Adresse des Administrators (admin@example.org).', $html);
    }

    #[Test]
    public function layoutLoadsTheScriptFileAndShowsTheVersion(): void
    {
        $html = $this->website([], null, false, '');

        $this->assertStringContainsString('<script src="assets/installer.js" defer></script>', $html);
        $this->assertDoesNotMatchRegularExpression('/<script(?![^>]*\bsrc=)[^>]*>/', $html, 'Kein Inline-JavaScript (CSP)');
        $this->assertStringContainsString('PowerNews ' . PN_VERSION . ' &copy; ' . PN_COPYRIGHT_YEARS, $html);
        $this->assertFileExists(__DIR__ . '/../../../assets/installer.js');
    }

    #[Test]
    public function summaryShowsTheSmtpSettingsWithoutPassword(): void
    {
        $html = $this->finish(self::SMTP);

        $this->assertStringContainsString('id="summary-mail"', $html);
        $this->assertStringContainsString('id="summary-mail-transport">SMTP-Server</dd>', $html);
        $this->assertStringContainsString('id="summary-smtp-host">smtp.example.org:587</dd>', $html);
        $this->assertStringContainsString('id="summary-smtp-encryption">STARTTLS (meist Port 587)</dd>', $html);
        $this->assertStringContainsString('id="summary-smtp-user">als news@example.org (Passwort gespeichert)</dd>', $html);
        $this->assertStringContainsString('href="install.php?step=3#mail-settings" id="edit-mail"', $html);
        $this->assertStringNotContainsString('Postfach-Geheim-26', $html);
    }

    #[Test]
    public function summaryShowsPhpMailWithoutServerDetails(): void
    {
        $html = $this->finish(LocalConfig::DEFAULT_MAIL);

        $this->assertStringContainsString('id="summary-mail-transport">PHP-mail des Servers</dd>', $html);
        $this->assertStringNotContainsString('summary-smtp-host', $html);
    }

    /**
     * @param array<string, string> $old
     * @param array{type: string, message: string}|null $mailTest
     */
    private function website(array $old, ?array $mailTest, bool $passwordStored, string $testRecipient): string
    {
        return $this->render('website', Wizard::STEP_WEBSITE, [
            'csrf' => 'token',
            'errors' => [],
            'message' => '',
            'old' => $old + ['site_url' => 'https://news.example.org', 'site_email' => 'news@example.org', 'site_language' => 'german-du'],
            'notice' => null,
            'mailTest' => $mailTest,
            'passwordStored' => $passwordStored,
            'testRecipient' => $testRecipient,
        ]);
    }

    /**
     * @param array{transport: string, host: string, port: int, encryption: string, user: string, password: string} $mail
     */
    private function finish(array $mail): string
    {
        $wizard = new Wizard();
        $wizard->completeRequirements();
        $wizard->storeDatabase(['host' => 'db', 'port' => 3306, 'user' => 'u', 'password' => 'p', 'database' => 'd'], 'MariaDB 10.11.15');
        $wizard->storeWebsite(['url' => 'https://news.example.org', 'email' => 'news@example.org', 'language' => 'german-du', 'mail' => $mail]);
        $wizard->storeAdmin('Redaktion', 'redaktion@example.org', 'hash');

        return $this->render('finish', Wizard::STEP_FINISH, [
            'csrf' => 'token',
            'errors' => [],
            'message' => '',
            'wizard' => $wizard,
            'configWritable' => true,
            'lockTarget' => 'pninc/install.lock',
        ]);
    }

    /**
     * @param array<string, mixed> $args
     */
    private function render(string $template, int $step, array $args): string
    {
        ob_start();

        try {
            View::render($template, 'Titel', 'Installation', $step, $step - 1, $args);
        } finally {
            $html = (string) ob_get_clean();
        }

        return $html;
    }
}
