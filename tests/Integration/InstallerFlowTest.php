<?php

declare(strict_types=1);

namespace PowerNews\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use PowerNews\Installer\Controller;
use PowerNews\Installer\DatabaseSetup;
use PowerNews\Installer\InstallState;
use PowerNews\Installer\Response;
use PowerNews\Installer\Wizard;
use PowerNews\LocalConfig;

/**
 * Kompletter Ablauf des Installers über den Controller – wie ein Browser,
 * aber ohne Webserver: jede Antwort landet in der Sitzung, bevor der nächste
 * Schritt kommt.
 */
final class InstallerFlowTest extends InstallerDatabaseTestCase
{
    private const string TOKEN = 'flow-token';

    private const array SERVER = ['HTTP_HOST' => 'news.example.org', 'SCRIPT_NAME' => '/news/install.php', 'HTTPS' => 'on'];

    /** SMTP-Passwort mit Zeichen, die in PHP-Quelltext gefährlich wären. */
    private const string SMTP_PASSWORD = 'Post\'fach"$pn?>\\ 26';

    /** @var array<string, mixed> Sitzung zwischen den Anfragen */
    private array $session = [];

    /** @var list<array{0: array<string, mixed>, 1: string, 2: string}> verschickte Test-Mails */
    private array $testMails = [];

    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $post
     */
    private function request(string $method, array $query, array $post = []): Response
    {
        $wizard = Wizard::fromSession($this->session[Wizard::SESSION_KEY] ?? null);
        $controller = new Controller(
            $this->root,
            $wizard,
            self::TOKEN,
            LocalConfig::DEFAULT_DB,
            static fn (): ?bool => null,
            DatabaseSetup::connect(...),
            function (array $mail, string $recipient, string $sender): array {
                $this->testMails[] = [$mail, $recipient, $sender];

                return ['ok' => true, 'message' => 'Der Mailserver hat die Test-Mail an ' . $recipient . ' angenommen.'];
            },
        );
        $response = $controller->handle($method, $query, $post === [] ? [] : $post + ['csrf_token' => self::TOKEN], self::SERVER);
        $this->session[Wizard::SESSION_KEY] = $wizard->toSession();

        return $response;
    }

    /**
     * @param array<string, string> $post
     */
    private function submit(int $step, array $post): Response
    {
        return $this->request('POST', ['step' => (string) $step], $post);
    }

    #[Test]
    public function allStepsInstallPowerNewsAndLockTheInstaller(): void
    {
        $this->assertSame('requirements', $this->request('GET', [])->template);
        $this->assertSame('install.php?step=2', $this->submit(1, ['action' => 'requirements'])->location);

        $step2 = $this->submit(2, [
            'action' => 'database',
            'db_host' => self::$db['host'],
            'db_port' => (string) self::$db['port'],
            'db_name' => self::$db['database'],
            'db_user' => self::$db['user'],
            'db_password' => self::$db['password'],
        ]);
        $this->assertSame('install.php?step=3', $step2->location, (string) ($step2->args['message'] ?? ''));

        $website = $this->request('GET', ['step' => '3']);
        $this->assertSame('https://news.example.org/news', ($website->args['old'] ?? [])['site_url'] ?? null);
        $this->assertSame('noreply@news.example.org', ($website->args['old'] ?? [])['site_email'] ?? null);
        $this->assertStringContainsString('geeignet', ($website->args['notice'] ?? [])['message'] ?? '');

        $website = [
            'site_url' => 'https://news.example.org/news',
            'site_email' => 'noreply@news.example.org',
            'site_language' => 'german-sie',
            'mail_transport' => 'smtp',
            'smtp_host' => 'smtp.example.org',
            'smtp_port' => '',
            'smtp_encryption' => 'ssl',
            'smtp_user' => 'noreply@news.example.org',
            'smtp_password' => self::SMTP_PASSWORD,
        ];
        $this->assertSame('install.php?step=3#smtp-test-result', $this->submit(3, ['action' => 'mail_test'] + $website)->location);
        $this->assertSame('noreply@news.example.org', $this->testMails[0][1] ?? null);
        $this->assertStringContainsString('angenommen', ($this->request('GET', ['step' => '3'])->args['mailTest'] ?? [])['message'] ?? '');

        // Das Passwort muss nicht erneut eingegeben werden.
        $this->assertSame('install.php?step=4', $this->submit(3, ['action' => 'website', 'smtp_password' => ''] + $website)->location);
        $this->assertSame('install.php?step=5', $this->submit(4, [
            'action' => 'admin',
            'admin_nickname' => 'Redaktion',
            'admin_email' => 'redaktion@example.org',
            'admin_password' => self::ADMIN_PASSWORD,
            'admin_password_confirm' => self::ADMIN_PASSWORD,
        ])->location);

        $summary = $this->request('GET', ['step' => '5']);
        $this->assertSame('finish', $summary->template);

        $finish = $this->submit(5, ['action' => 'finish']);
        $this->assertSame(Response::REDIRECT, $finish->kind);
        $this->assertTrue($finish->renewSession, 'Nach dem Abschluss neue Session-ID und neues Token');

        $done = $this->request('GET', []);
        $this->assertSame('done', $done->template);
        $this->assertSame('pninc/install.lock', ($done->args['done'] ?? [])['lock_file'] ?? null);
        $this->assertTrue(($done->args['done'] ?? [])['config_written'] ?? false);

        $local = require $this->root . '/pninc/config.local.php';
        $this->assertIsArray($local);
        $this->assertSame(self::$db, $local['db']);
        $this->assertSame('german-sie', $local['language']);
        $this->assertSame(
            ['transport' => 'smtp', 'host' => 'smtp.example.org', 'port' => 465, 'encryption' => 'ssl', 'user' => 'noreply@news.example.org', 'password' => self::SMTP_PASSWORD],
            $local['mail'],
        );
        $this->assertSame($local['mail'], LocalConfig::load(static fn (): false => false, $this->root . '/pninc/config.local.php')['mail']);

        $mysqli = $this->mysqli();
        $this->assertSame('https://news.example.org/news', self::value($mysqli, 'SELECT url FROM pn_config'));
        $this->assertSame('Redaktion', self::value($mysqli, 'SELECT nickname FROM pn_users WHERE id = 1'));
        $mysqli->close();

        // Eine neue Sitzung (anderer Besucher) sieht nur noch die Sperrseite.
        $this->session = [];
        $locked = $this->request('GET', []);
        $this->assertSame(403, $locked->status);
        $this->assertSame(InstallState::REASON_LOCK_FILE, $locked->args['reason'] ?? null);
    }

    #[Test]
    public function existingPowerNewsTablesStopTheDatabaseStep(): void
    {
        $mysqli = $this->mysqli();
        $mysqli->query('CREATE TABLE pn_users (id int) ENGINE=InnoDB');
        $mysqli->close();

        $this->submit(1, ['action' => 'requirements']);
        $response = $this->submit(2, [
            'action' => 'database',
            'db_host' => self::$db['host'],
            'db_port' => (string) self::$db['port'],
            'db_name' => self::$db['database'],
            'db_user' => self::$db['user'],
            'db_password' => self::$db['password'],
        ]);

        $this->assertSame('database', $response->template);
        $this->assertSame(['pn_users'], $response->args['tables'] ?? null);
        $this->assertStringContainsString('überschreibt keine bestehenden Daten', (string) ($response->args['message'] ?? ''));
        $this->assertSame(Wizard::STEP_REQUIREMENTS, Wizard::fromSession($this->session[Wizard::SESSION_KEY] ?? null)->completed());
    }
}
