<?php

declare(strict_types=1);

namespace PowerNews\Tests\Unit\Installer;

use mysqli;
use mysqli_sql_exception;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PowerNews\Installer\Controller;
use PowerNews\Installer\InstallState;
use PowerNews\Installer\Response;
use PowerNews\Installer\Wizard;
use PowerNews\LocalConfig;

require_once __DIR__ . '/../../../pninc/installer/autoload.php';

/**
 * Ablauf des Installers ohne Datenbank: Schritte, CSRF, PRG, Sperre,
 * Abschlussseite. Die Wege mit echter Datenbank prüft
 * tests/Integration/InstallerDatabaseTest.php.
 */
final class ControllerTest extends TestCase
{
    private const string TOKEN = 'csrf-token-123';

    private const array DB = ['host' => 'sql.example.org', 'port' => 3306, 'user' => 'news_user', 'password' => 'Lichtblick-DB26', 'database' => 'news_db'];

    private const array SERVER = ['HTTP_HOST' => 'localhost:8229', 'SCRIPT_NAME' => '/install.php', 'REQUEST_METHOD' => 'GET'];

    private string $root = '';

    private Wizard $wizard;

    private int $connects = 0;

    private ?mysqli_sql_exception $connectError = null;

    /** @var list<array{0: array<string, mixed>, 1: string, 2: string}> verschickte Test-Mails (Einstellungen, Empfänger, Absender) */
    private array $testMails = [];

    /** @var array{ok: bool, message: string} Ergebnis der nächsten Test-Mail */
    private array $testMailOutcome = ['ok' => true, 'message' => 'Der Mailserver hat die Test-Mail angenommen.'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->root = InstallerFixture::createRoot();
        $this->wizard = new Wizard();
        $this->connects = 0;
        $this->connectError = new mysqli_sql_exception("Access denied for user 'news_user'@'10.0.0.5' (using password: YES)", 1045);
    }

    protected function tearDown(): void
    {
        InstallerFixture::removeRoot($this->root);
        parent::tearDown();
    }

    private function controller(?bool $probe = null): Controller
    {
        return new Controller(
            $this->root,
            $this->wizard,
            self::TOKEN,
            LocalConfig::DEFAULT_DB,
            static fn (): ?bool => $probe,
            function (array $db): mysqli {
                ++$this->connects;

                throw $this->connectError ?? new mysqli_sql_exception('keine Testdatenbank', 2002);
            },
            function (array $mail, string $recipient, string $sender): array {
                $this->testMails[] = [$mail, $recipient, $sender];

                return $this->testMailOutcome;
            },
        );
    }

    private const array SITE = ['site_url' => 'http://localhost:8229', 'site_email' => 'news@example.org', 'site_language' => 'german-du'];

    /** @var list<string> Protokolleinträge der letzten Anfrage */
    private array $lastEvents = [];

    /**
     * @return array<string, string>
     */
    private function smtpInput(): array
    {
        return self::SITE + [
            'mail_transport' => 'smtp',
            'smtp_host' => 'smtp.example.org',
            'smtp_port' => '',
            'smtp_encryption' => 'starttls',
            'smtp_user' => 'news@example.org',
            'smtp_password' => 'Postfach-Geheim-26',
        ];
    }

    /**
     * @param array<string, mixed> $query
     */
    private function get(array $query = [], ?Controller $controller = null): Response
    {
        return ($controller ?? $this->controller())->handle('GET', $query, [], self::SERVER);
    }

    /**
     * @param array<string, mixed> $post
     */
    private function post(int $step, array $post, ?Controller $controller = null): Response
    {
        $controller ??= $this->controller();
        $response = $controller->handle('POST', ['step' => (string) $step], $post + ['csrf_token' => self::TOKEN], self::SERVER);
        $this->lastEvents = $controller->events();

        return $response;
    }

    private function atStep(int $step): void
    {
        $this->wizard->completeRequirements();
        if ($step > Wizard::STEP_DATABASE) {
            $this->wizard->storeDatabase(self::DB, 'MariaDB 10.11.15');
        }
        if ($step > Wizard::STEP_WEBSITE) {
            $this->wizard->storeWebsite(['url' => 'http://localhost:8229', 'email' => 'news@example.org', 'language' => 'german-du', 'mail' => LocalConfig::DEFAULT_MAIL]);
        }
        if ($step > Wizard::STEP_ADMIN) {
            $this->wizard->storeAdmin('admin', 'admin@example.org', password_hash('sicher-genug', PASSWORD_DEFAULT));
        }
    }

    // ── Schritt 1 und Schrittfolge ──

    #[Test]
    public function firstCallShowsTheRequirements(): void
    {
        $response = $this->get();

        $this->assertSame(Response::PAGE, $response->kind);
        $this->assertSame(200, $response->status);
        $this->assertSame('requirements', $response->template);
        $this->assertSame('Systemprüfung', $response->title);
        $this->assertSame(self::TOKEN, $response->args['csrf'] ?? null);
        $this->assertTrue($response->args['allOk'] ?? false);
    }

    #[Test]
    public function laterStepsRedirectToTheFirstReachableStep(): void
    {
        $response = $this->get(['step' => '5']);

        $this->assertSame(Response::REDIRECT, $response->kind);
        $this->assertSame(303, $response->status);
        $this->assertSame('install.php?step=1', $response->location);
    }

    #[Test]
    public function postWithoutValidCsrfTokenChangesNothing(): void
    {
        $response = $this->controller()->handle('POST', [], ['action' => 'requirements', 'csrf_token' => 'falsch'], self::SERVER);

        $this->assertSame('requirements', $response->template);
        $this->assertStringContainsString('Sitzung ist abgelaufen', (string) ($response->args['message'] ?? ''));
        $this->assertSame(0, $this->wizard->completed());
    }

    #[Test]
    public function requirementsStepRedirectsToTheDatabase(): void
    {
        $response = $this->post(1, ['action' => 'requirements']);

        $this->assertSame(Response::REDIRECT, $response->kind);
        $this->assertSame('install.php?step=2', $response->location);
        $this->assertSame(Wizard::STEP_REQUIREMENTS, $this->wizard->completed());
    }

    #[Test]
    public function unknownActionIsReported(): void
    {
        $response = $this->post(1, ['action' => 'drop_everything']);

        $this->assertSame(Response::PAGE, $response->kind);
        $this->assertStringContainsString('Unbekannte Aktion', (string) ($response->args['message'] ?? ''));
    }

    #[Test]
    public function actionOfAnUnreachableStepRedirects(): void
    {
        $response = $this->post(5, ['action' => 'finish']);

        $this->assertSame(Response::REDIRECT, $response->kind);
        $this->assertSame('install.php?step=1', $response->location);
        $this->assertSame(0, $this->connects);
    }

    // ── Schritt 2: Datenbank ──

    #[Test]
    public function invalidDatabaseFieldsAreShownWithoutConnecting(): void
    {
        $this->atStep(Wizard::STEP_DATABASE);

        $response = $this->post(2, ['action' => 'database', 'db_host' => 'localhost:3306', 'db_name' => '', 'db_user' => 'u', 'db_password' => 'geheim']);

        $this->assertSame('database', $response->template);
        $this->assertSame(['db_host', 'db_name'], array_keys((array) ($response->args['errors'] ?? [])));
        $this->assertSame(['db_host' => 'localhost:3306', 'db_port' => '', 'db_name' => '', 'db_user' => 'u'], $response->args['old'] ?? null);
        $this->assertSame(0, $this->connects);
    }

    #[Test]
    public function wrongPasswordIsShownAtThePasswordFieldWithoutCredentials(): void
    {
        $this->atStep(Wizard::STEP_DATABASE);
        $controller = $this->controller();

        $response = $this->post(2, $this->databaseInput(), $controller);

        $errors = (array) ($response->args['errors'] ?? []);
        $this->assertSame(['db_password'], array_keys($errors));
        $this->assertStringContainsString('Benutzername oder Passwort ist falsch', (string) $errors['db_password']);
        $this->assertSame(1, $this->connects);
        $this->assertSame(['Verbindungstest fehlgeschlagen (Fehlercode 1045).'], $controller->events());

        // Angezeigt werden nur die eigenen Texte, nie die Serverantwort mit Benutzer und Host.
        $shown = serialize([$response->args['errors'] ?? null, $response->args['message'] ?? null]) . implode(' ', $controller->events());
        foreach (['news_user', '10.0.0.5', 'Lichtblick-DB26', 'Access denied'] as $secret) {
            $this->assertStringNotContainsString($secret, $shown);
        }
        $this->assertArrayNotHasKey('db_password', (array) ($response->args['old'] ?? []));
        $this->assertSame(Wizard::STEP_REQUIREMENTS, $this->wizard->completed());
    }

    #[Test]
    public function unknownHostIsShownAtTheHostField(): void
    {
        $this->atStep(Wizard::STEP_DATABASE);
        $this->connectError = new mysqli_sql_exception('php_network_getaddresses: getaddrinfo for nope failed', 2005);

        $response = $this->post(2, $this->databaseInput());

        $this->assertSame(['db_host'], array_keys((array) ($response->args['errors'] ?? [])));
    }

    #[Test]
    public function otherDatabaseErrorsAreShownAsGeneralMessage(): void
    {
        $this->atStep(Wizard::STEP_DATABASE);
        $this->connectError = new mysqli_sql_exception('Too many connections', 1040);

        $response = $this->post(2, $this->databaseInput());

        $this->assertSame([], $response->args['errors'] ?? null);
        $this->assertSame('Die Datenbank hat die Anfrage abgelehnt (Fehlercode 1040).', $response->args['message'] ?? null);
    }

    // ── Schritt 3 und 4 ──

    #[Test]
    public function websiteStepIsPrefilledFromTheRequestAndShowsTheServerCheck(): void
    {
        $this->atStep(Wizard::STEP_WEBSITE);
        $this->wizard->setNotice('success', 'Verbindung hergestellt: MariaDB 10.11.15 – geeignet');

        $response = $this->get(['step' => '3']);

        $this->assertSame('website', $response->template);
        $this->assertSame(
            ['site_url' => 'http://localhost:8229', 'site_email' => '', 'site_language' => 'german-du', 'mail_transport' => 'mail', 'smtp_host' => '', 'smtp_port' => '587', 'smtp_encryption' => 'starttls', 'smtp_user' => ''],
            $response->args['old'] ?? null,
        );
        $this->assertSame('success', ($response->args['notice'] ?? [])['type'] ?? null);
        $this->assertNull($response->args['mailTest'] ?? null);
        $this->assertFalse($response->args['passwordStored'] ?? true);
    }

    #[Test]
    public function websiteStepStoresTheSettings(): void
    {
        $this->atStep(Wizard::STEP_WEBSITE);

        $response = $this->post(3, ['action' => 'website', 'site_url' => 'http://localhost:8229/', 'site_email' => 'news@example.org', 'site_language' => 'english']);

        $this->assertSame('install.php?step=4', $response->location);
        $this->assertSame(['url' => 'http://localhost:8229', 'email' => 'news@example.org', 'language' => 'english', 'mail' => LocalConfig::DEFAULT_MAIL], $this->wizard->website());
        $this->assertSame([], $this->testMails, 'Weiter verschickt keine Test-Mail');
    }

    // ── Schritt 3: Test-Mail ──

    #[Test]
    public function mailTestStoresTheSettingsSendsToTheSenderAndRedirects(): void
    {
        $this->atStep(Wizard::STEP_WEBSITE);

        $response = $this->post(3, $this->smtpInput() + ['action' => Wizard::ACTION_MAIL_TEST]);

        $this->assertSame(Response::REDIRECT, $response->kind);
        $this->assertSame('install.php?step=3#smtp-test-result', $response->location);
        $this->assertCount(1, $this->testMails);
        [$mail, $recipient, $sender] = $this->testMails[0];
        $this->assertSame(['transport' => 'smtp', 'host' => 'smtp.example.org', 'port' => 587, 'encryption' => 'starttls', 'user' => 'news@example.org', 'password' => 'Postfach-Geheim-26'], $mail);
        $this->assertSame('news@example.org', $recipient, 'Ohne Administrator an die Absenderadresse');
        $this->assertSame('news@example.org', $sender);
        $this->assertSame('smtp', $this->wizard->website()['mail']['transport'] ?? null);
        $this->assertSame(['Test-Mail per smtp angenommen.'], $this->lastEvents);

        $page = $this->get(['step' => '3']);
        $this->assertSame(['type' => 'success', 'message' => 'Der Mailserver hat die Test-Mail angenommen.'], $page->args['mailTest'] ?? null);
        $this->assertTrue($page->args['passwordStored'] ?? false);
        $this->assertSame('smtp.example.org', ($page->args['old'] ?? [])['smtp_host'] ?? null);
        $this->assertStringNotContainsString('Postfach-Geheim-26', serialize($page->args));
        $this->assertNull($this->get(['step' => '3'])->args['mailTest'] ?? null, 'Das Ergebnis erscheint nur einmal');
    }

    #[Test]
    public function mailTestGoesToTheAdministratorOnceKnown(): void
    {
        $this->atStep(Wizard::STEP_FINISH);

        $this->post(3, self::SITE + ['action' => Wizard::ACTION_MAIL_TEST, 'mail_transport' => 'mail']);

        $this->assertSame('admin@example.org', $this->testMails[0][1] ?? null);
        $this->assertSame('admin@example.org', $this->get(['step' => '3'])->args['testRecipient'] ?? null);
    }

    #[Test]
    public function failedMailTestIsShownAsDanger(): void
    {
        $this->atStep(Wizard::STEP_WEBSITE);
        $this->testMailOutcome = ['ok' => false, 'message' => 'Die Test-Mail an news@example.org konnte nicht verschickt werden. Verbindungsaufbau fehlgeschlagen: Connection refused'];

        $this->post(3, $this->smtpInput() + ['action' => Wizard::ACTION_MAIL_TEST]);

        $this->assertSame('danger', ($this->get(['step' => '3'])->args['mailTest'] ?? [])['type'] ?? null);
        $this->assertSame(['Test-Mail per smtp fehlgeschlagen.'], $this->lastEvents);
    }

    #[Test]
    public function mailTestWithInvalidFieldsSendsNothing(): void
    {
        $this->atStep(Wizard::STEP_WEBSITE);

        $response = $this->post(3, ['smtp_host' => ''] + $this->smtpInput() + ['action' => Wizard::ACTION_MAIL_TEST]);

        $this->assertSame('website', $response->template);
        $this->assertSame(['smtp_host'], array_keys((array) ($response->args['errors'] ?? [])));
        $this->assertArrayNotHasKey('smtp_password', (array) ($response->args['old'] ?? []));
        $this->assertStringNotContainsString('Postfach-Geheim-26', serialize($response->args));
        $this->assertSame([], $this->testMails);
    }

    #[Test]
    public function mailTestNeedsAValidCsrfToken(): void
    {
        $this->atStep(Wizard::STEP_WEBSITE);

        $response = $this->controller()->handle('POST', ['step' => '3'], $this->smtpInput() + ['action' => Wizard::ACTION_MAIL_TEST, 'csrf_token' => 'falsch'], self::SERVER);

        $this->assertSame('website', $response->template);
        $this->assertStringContainsString('Sitzung ist abgelaufen', (string) ($response->args['message'] ?? ''));
        $this->assertSame([], $this->testMails);
    }

    #[Test]
    public function mailTestsAreLimitedPerSession(): void
    {
        $this->atStep(Wizard::STEP_WEBSITE);

        for ($i = 0; $i < Wizard::MAX_MAIL_TESTS; ++$i) {
            $this->assertSame(Response::REDIRECT, $this->post(3, $this->smtpInput() + ['action' => Wizard::ACTION_MAIL_TEST])->kind);
        }
        $response = $this->post(3, $this->smtpInput() + ['action' => Wizard::ACTION_MAIL_TEST]);

        $this->assertSame('website', $response->template);
        $this->assertStringContainsString('bereits ' . Wizard::MAX_MAIL_TESTS . ' Test-Mails', (string) ($response->args['message'] ?? ''));
        $this->assertCount(Wizard::MAX_MAIL_TESTS, $this->testMails);
    }

    #[Test]
    public function mailTestIsOnlyPossibleViaPost(): void
    {
        $this->atStep(Wizard::STEP_WEBSITE);

        $response = $this->controller()->handle('GET', ['step' => '3', 'action' => Wizard::ACTION_MAIL_TEST], [], self::SERVER);

        $this->assertSame('website', $response->template);
        $this->assertSame([], $this->testMails);
    }

    #[Test]
    public function adminStepStoresOnlyAHashThatTheAdminLoginAccepts(): void
    {
        $this->atStep(Wizard::STEP_ADMIN);

        $response = $this->post(4, [
            'action' => 'admin',
            'admin_nickname' => 'Redaktion',
            'admin_email' => 'redaktion@example.org',
            'admin_password' => 'Sonnenschein-2026',
            'admin_password_confirm' => 'Sonnenschein-2026',
        ]);

        $this->assertSame('install.php?step=5', $response->location);
        $admin = $this->wizard->admin();
        $this->assertNotNull($admin);
        $this->assertSame('Redaktion', $admin['nickname']);
        $this->assertStringStartsWith('$2y$', $admin['password_hash']);
        $this->assertFalse(pnadmin_is_legacy_password($admin['password_hash']));
        $this->assertTrue(pnadmin_verify_password('Sonnenschein-2026', $admin['password_hash']), 'Gleiche Prüfung wie der Admin-Login');
    }

    #[Test]
    public function adminErrorsNeverEchoThePassword(): void
    {
        $this->atStep(Wizard::STEP_ADMIN);

        $response = $this->post(4, ['action' => 'admin', 'admin_nickname' => 'x', 'admin_email' => 'a@b.de', 'admin_password' => 'Sonnenschein-2026', 'admin_password_confirm' => 'anders']);

        $this->assertSame('admin', $response->template);
        $this->assertStringNotContainsString('Sonnenschein-2026', serialize($response->args));
        $this->assertArrayHasKey('admin_nickname', (array) ($response->args['errors'] ?? []));
    }

    // ── Schritt 5: Abschluss ──

    #[Test]
    public function summaryShowsWhereConfigAndLockWillBeWritten(): void
    {
        $this->atStep(Wizard::STEP_FINISH);

        $response = $this->get(['step' => '5']);

        $this->assertSame('finish', $response->template);
        $this->assertTrue($response->args['configWritable'] ?? false);
        $this->assertSame('pninc/install.lock', $response->args['lockTarget'] ?? null);
    }

    #[Test]
    public function finishRefusesToStartWithoutAWritableLockLocation(): void
    {
        $this->atStep(Wizard::STEP_FINISH);
        rmdir($this->root . '/pninc');
        rmdir($this->root . '/logs');

        $response = $this->post(5, ['action' => 'finish']);

        $this->assertSame('finish', $response->template);
        $this->assertStringContainsString('Sperrdatei', (string) ($response->args['message'] ?? ''));
        $this->assertStringContainsString('Es wurde nichts verändert', (string) ($response->args['message'] ?? ''));
        $this->assertSame(0, $this->connects, 'Die Datenbank wird gar nicht erst berührt');
        $this->assertNull($this->wizard->done());
    }

    #[Test]
    public function finishReportsDatabaseErrorsWithoutCredentials(): void
    {
        $this->atStep(Wizard::STEP_FINISH);
        $this->connectError = new mysqli_sql_exception("Can't connect to server on 'sql.example.org'", 2002);

        $response = $this->post(5, ['action' => 'finish']);

        $message = (string) ($response->args['message'] ?? '');
        $this->assertStringContainsString('nicht erreichbar', $message);
        $this->assertStringContainsString('Es wurde nichts verändert', $message);
        $this->assertStringNotContainsString('sql.example.org', $message);
        $this->assertFileDoesNotExist($this->root . '/pninc/install.lock');
        $this->assertFileDoesNotExist($this->root . '/pninc/config.local.php');
    }

    // ── Sperre ──

    #[Test]
    public function lockFileBlocksEveryRequestWith403(): void
    {
        file_put_contents($this->root . '/pninc/install.lock', 'x');
        $this->atStep(Wizard::STEP_FINISH);

        foreach ([$this->get(), $this->post(5, ['action' => 'finish'])] as $response) {
            $this->assertSame(403, $response->status);
            $this->assertSame('locked', $response->template);
            $this->assertSame(InstallState::REASON_LOCK_FILE, $response->args['reason'] ?? null);
            $this->assertSame('pninc/install.lock', $response->args['lockFile'] ?? null);
        }
        $this->assertSame(0, $this->connects, 'Gesperrt: keine Datenbankaktion');
    }

    #[Test]
    public function configRowInTheDatabaseBlocksWith403(): void
    {
        $response = $this->get([], $this->controller(true));

        $this->assertSame(403, $response->status);
        $this->assertSame(InstallState::REASON_DATABASE, $response->args['reason'] ?? null);
    }

    #[Test]
    public function localConfigBlocksWith403(): void
    {
        file_put_contents($this->root . '/pninc/config.local.php', "<?php\nreturn [];\n");

        $this->assertSame(InstallState::REASON_LOCAL_CONFIG, $this->get()->args['reason'] ?? null);
    }

    // ── Abschlussseite ──

    #[Test]
    public function doneSessionOffersTheConfigDownloadOnlyWithValidToken(): void
    {
        $this->atStep(Wizard::STEP_FINISH);
        $this->wizard->finish(false, "<?php\nreturn ['db' => ['password' => 'x']];\n", 'logs/install.lock');

        $page = $this->get();
        $this->assertSame('done', $page->template);
        $this->assertSame(200, $page->status);
        $this->assertFalse($page->args['configPresent'] ?? true);

        $refused = $this->controller()->handle('POST', [], ['action' => 'download_config', 'csrf_token' => 'falsch'], self::SERVER);
        $this->assertSame(Response::PAGE, $refused->kind);

        $download = $this->post(0, ['action' => 'download_config']);
        $this->assertSame(Response::DOWNLOAD, $download->kind);
        $this->assertSame('config.local.php', $download->title);
        $this->assertStringContainsString("'password' => 'x'", $download->content);
    }

    #[Test]
    public function uploadedConfigRemovesTheDownloadFromTheSession(): void
    {
        $this->atStep(Wizard::STEP_FINISH);
        $this->wizard->finish(false, "<?php\nreturn [];\n", 'logs/install.lock');
        file_put_contents($this->root . '/pninc/config.local.php', "<?php\nreturn [];\n");

        $page = $this->get();

        $this->assertTrue($page->args['configPresent'] ?? false);
        $this->assertSame('', $this->wizard->done()['config_source'] ?? null);
        $this->assertSame(Response::PAGE, $this->post(0, ['action' => 'download_config'])->kind);
    }

    /**
     * @return array<string, string>
     */
    private function databaseInput(): array
    {
        return [
            'action' => 'database',
            'db_host' => 'sql.example.org',
            'db_port' => '3306',
            'db_name' => 'news_db',
            'db_user' => 'news_user',
            'db_password' => 'Lichtblick-DB26',
        ];
    }
}
