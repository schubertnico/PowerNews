<?php

declare(strict_types=1);

namespace PowerNews\Tests\Unit\Installer;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PowerNews\Installer\SmtpCheck;
use PowerNews\LocalConfig;
use PowerNews\Mailer;
use PowerNews\Tests\Helpers\MockSmtpServer;

require_once __DIR__ . '/../../../pninc/installer/autoload.php';

/**
 * „Test-Mail senden“ im Installer: Beschreibung ohne Passwort, ehrliche
 * Meldungen für mail() und SMTP.
 */
final class SmtpCheckTest extends TestCase
{
    use MockSmtpServer;

    private const array SMTP = [
        'transport' => 'smtp',
        'host' => 'smtp.example.org',
        'port' => 587,
        'encryption' => 'starttls',
        'user' => 'news@example.org',
        'password' => 'geheim-Ä1',
    ];

    private string|false $previousErrorLog = false;

    private string $logFile = '';

    protected function setUp(): void
    {
        parent::setUp();
        $this->logFile = (string) tempnam(sys_get_temp_dir(), 'pn-smtpcheck-log-');
        $this->previousErrorLog = ini_set('error_log', $this->logFile);
    }

    protected function tearDown(): void
    {
        ini_set('error_log', $this->previousErrorLog === false ? '' : $this->previousErrorLog);
        @unlink($this->logFile);
        parent::tearDown();
    }

    #[Test]
    public function describeNamesEverythingButThePassword(): void
    {
        $this->assertSame('SMTP-Server smtp.example.org:587, STARTTLS, Anmeldung als news@example.org', SmtpCheck::describe(self::SMTP));
        $this->assertSame('SMTP-Server localhost:465, SSL/TLS, ohne Anmeldung', SmtpCheck::describe(['host' => 'localhost', 'port' => 0, 'encryption' => 'ssl', 'user' => '', 'password' => ''] + self::SMTP));
        $this->assertSame('PHP-mail des Servers', SmtpCheck::describe(LocalConfig::DEFAULT_MAIL));
    }

    #[Test]
    public function bodyContainsTheSettingsButNoPassword(): void
    {
        $body = SmtpCheck::body(self::SMTP, 'news@example.org');

        $this->assertStringContainsString('Versand: SMTP-Server smtp.example.org:587, STARTTLS, Anmeldung als news@example.org', $body);
        $this->assertStringContainsString('Absender: news@example.org', $body);
        $this->assertStringNotContainsString('geheim', $body);
    }

    #[Test]
    public function acceptedSmtpTestMailIsReportedHonestly(): void
    {
        $mail = ['host' => '127.0.0.1', 'encryption' => 'none', 'user' => '', 'password' => ''] + self::SMTP;

        [$outcome, $transcript] = $this->converse('ok', static fn (int $port): array => SmtpCheck::send(
            new Mailer('smtp', '127.0.0.1', $port, 'none', '', '', 5),
            ['port' => $port] + $mail,
            'admin@example.org',
            'news@example.org',
        ));

        $this->assertTrue($outcome['ok']);
        $this->assertStringStartsWith('Der Mailserver hat die Test-Mail an admin@example.org angenommen.', $outcome['message']);
        $this->assertStringContainsString('Spam-Ordner', $outcome['message']);
        $this->assertContains('MAIL FROM:<news@example.org>', $this->commands($transcript));
        $this->assertContains('RCPT TO:<admin@example.org>', $this->commands($transcript));
        $this->assertStringContainsString('D: Subject: ' . SmtpCheck::SUBJECT . "\n", $transcript);
        $this->assertStringContainsString('D: Sie m=C3=BCssen nichts weiter tun.', $transcript, 'Ohne 8BITMIME: quoted-printable');
        $this->assertStringContainsString("D: From: PowerNews <news@example.org>\n", $transcript);
        $this->assertStringContainsString("D: Reply-To: news@example.org\n", $transcript);
    }

    #[Test]
    public function acceptedPhpMailIsReportedAsHandedOver(): void
    {
        $calls = 0;
        $mailer = Mailer::fromConfig(LocalConfig::DEFAULT_MAIL)->withMailFunction(static function () use (&$calls): bool {
            ++$calls;

            return true;
        });

        $outcome = SmtpCheck::send($mailer, LocalConfig::DEFAULT_MAIL, 'news@example.org', 'news@example.org');

        $this->assertTrue($outcome['ok']);
        $this->assertSame(1, $calls);
        $this->assertStringStartsWith('Die PHP-Funktion mail() hat die Test-Mail an news@example.org übernommen.', $outcome['message']);
    }

    #[Test]
    public function failureShowsTheReasonWithoutCredentials(): void
    {
        [$outcome] = $this->converse('auth-rejected', static fn (int $port): array => SmtpCheck::send(
            new Mailer('smtp', '127.0.0.1', $port, 'none', 'news@example.org', 'geheim-Ä1', 5),
            self::SMTP,
            'admin@example.org',
            'news@example.org',
        ));

        $this->assertFalse($outcome['ok']);
        $this->assertSame(
            'Die Test-Mail an admin@example.org konnte nicht verschickt werden. Anmeldung (AUTH PLAIN): Server antwortet 535 5.7.8 '
            . 'Authentication credentials invalid – Benutzername und Passwort prüfen.',
            $outcome['message'],
        );
        $this->assertStringNotContainsString('geheim', $outcome['message']);
        $this->assertStringNotContainsString('geheim', (string) file_get_contents($this->logFile));
    }

    #[Test]
    public function runBuildsTheMailerFromTheSettings(): void
    {
        $outcome = SmtpCheck::run(['host' => '127.0.0.1', 'port' => 1, 'encryption' => 'none'] + self::SMTP, 'admin@example.org', 'news@example.org');

        $this->assertFalse($outcome['ok']);
        $this->assertStringContainsString('Verbindungsaufbau fehlgeschlagen', $outcome['message']);
    }
}
