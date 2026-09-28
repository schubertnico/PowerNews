<?php

declare(strict_types=1);

namespace PowerNews\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PowerNews\LocalConfig;
use PowerNews\Mailer;
use PowerNews\Tests\Helpers\MockSmtpServer;

require_once __DIR__ . '/../../pninc/mailer.inc.php';
require_once __DIR__ . '/../../pninc/localconfig.inc.php';

/**
 * Versand per mail() und per SMTP (gegen einen Mock-Server): Dialog, Anmeldung,
 * Verschlüsselung, Fehlermeldungen und Protokoll ohne Zugangsdaten.
 */
final class MailerTest extends TestCase
{
    use MockSmtpServer;

    private const string USER = 'news@example.org';

    private const string PASSWORD = 'Geh3im!Pässwort';

    private string $logFile = '';

    private string|false $previousErrorLog = false;

    protected function setUp(): void
    {
        parent::setUp();
        // Fehlerprotokoll des Mailers in eine eigene Datei, damit die Tests prüfen
        // können, was dort landet (und die Ausgabe sauber bleibt).
        $this->logFile = (string) tempnam(sys_get_temp_dir(), 'pn-mailer-log-');
        $this->previousErrorLog = ini_set('error_log', $this->logFile);
    }

    protected function tearDown(): void
    {
        ini_set('error_log', $this->previousErrorLog === false ? '' : $this->previousErrorLog);
        @unlink($this->logFile);
        parent::tearDown();
    }

    // ── Konfiguration ──

    /**
     * @return iterable<string, array{string, string|null}>
     */
    public static function transports(): iterable
    {
        yield 'mail' => ['mail', 'mail'];
        yield 'leer' => ['', 'mail'];
        yield 'smtp' => ['smtp', 'smtp'];
        yield 'Großbuchstaben und Leerzeichen' => [' SMTP ', 'smtp'];
        yield 'sendmail gibt es nicht' => ['sendmail', null];
        yield 'Tippfehler' => ['smpt', null];
    }

    #[Test]
    #[DataProvider('transports')]
    public function transportIsNormalised(string $value, ?string $expected): void
    {
        $this->assertSame($expected, Mailer::normalizeTransport($value));
    }

    /**
     * @return iterable<string, array{string, string|null}>
     */
    public static function encryptions(): iterable
    {
        yield 'none' => ['none', 'none'];
        yield 'leer' => ['', 'none'];
        yield 'starttls' => ['starttls', 'starttls'];
        yield 'Großbuchstaben und Leerzeichen' => [' STARTTLS ', 'starttls'];
        yield 'ssl' => ['ssl', 'ssl'];
        yield 'tls ist mehrdeutig' => ['tls', null];
        yield 'Tippfehler' => ['startls', null];
    }

    #[Test]
    #[DataProvider('encryptions')]
    public function encryptionIsNormalised(string $value, ?string $expected): void
    {
        $this->assertSame($expected, Mailer::normalizeEncryption($value));
    }

    #[Test]
    public function portZeroMeansTheUsualPortOfTheEncryption(): void
    {
        $this->assertSame(['none' => 25, 'starttls' => 587, 'ssl' => 465], Mailer::DEFAULT_PORTS);
        $this->assertSame(587, Mailer::effectivePort(0, 'starttls'));
        $this->assertSame(465, Mailer::effectivePort(0, ' SSL '));
        $this->assertSame(25, Mailer::effectivePort(0, 'unbekannt'));
        $this->assertSame(2525, Mailer::effectivePort(2525, 'starttls'));
        $this->assertSame(25, Mailer::effectivePort(70000, 'none'));
    }

    #[Test]
    public function describeNamesTheWayButNeverTheCredentials(): void
    {
        $smtp = Mailer::fromConfig(['transport' => 'smtp', 'host' => 'smtp.example.org', 'port' => 0, 'encryption' => 'starttls', 'user' => self::USER, 'password' => self::PASSWORD]);

        $this->assertSame('über smtp.example.org:587 (Verschlüsselung starttls, mit Anmeldung)', $smtp->describe());
        $this->assertSame('über PHP mail()', Mailer::fromConfig([])->describe());
        $this->assertSame('über PHP mail()', Mailer::fromConfig(LocalConfig::DEFAULT_MAIL)->describe());
        $this->assertStringNotContainsString(self::USER, $smtp->describe());
    }

    #[Test]
    public function withTlsOptionsAndWithMailFunctionReturnCopies(): void
    {
        $mailer = new Mailer();

        $this->assertNotSame($mailer, $mailer->withTlsOptions(['cafile' => '/tmp/ca.pem']));
        $this->assertNotSame($mailer, $mailer->withMailFunction(static fn (): bool => true));
    }

    // ── Versandart „mail“ (bisheriges Verhalten) ──

    #[Test]
    public function mailTransportHandsEncodedHeadersToPhpMail(): void
    {
        $calls = [];
        $mailer = Mailer::fromConfig([])->withMailFunction(static function (string $to, string $subject, string $body, array $headers) use (&$calls): bool {
            $calls[] = [$to, $subject, $body, $headers];

            return true;
        });

        $this->assertTrue($mailer->send('max@example.org', 'Grüße aus PowerNews', "Hallo Max Müller,\r\nZeile 2", 'news@example.org', 'PowerNews'));
        $this->assertSame('', $mailer->lastError());
        $this->assertCount(1, $calls);

        [$to, $subject, $body, $headers] = $calls[0];
        $this->assertSame('max@example.org', $to);
        $this->assertStringStartsWith('=?UTF-8?B?', $subject);
        $this->assertSame('Grüße aus PowerNews', mb_decode_mimeheader($subject));
        $this->assertSame("Hallo Max Müller,\nZeile 2", $body, 'mail() bekommt LF-Zeilenenden wie bisher');
        $this->assertSame('PowerNews <news@example.org>', $headers['From']);
        $this->assertSame('news@example.org', $headers['Reply-To']);
        $this->assertSame('text/plain; charset=UTF-8', $headers['Content-Type']);
        $this->assertSame('', (string) file_get_contents($this->logFile));
    }

    #[Test]
    public function refusedPhpMailIsLoggedWithTheReason(): void
    {
        $mailer = Mailer::fromConfig(['transport' => 'mail'])->withMailFunction(static function (): bool {
            trigger_error('mail(): Failed to connect to mailserver at "localhost" port 25', E_USER_WARNING);

            return false;
        });

        $this->assertFalse($mailer->send('max@example.org', 'Betreff', 'Text', 'news@example.org', 'PowerNews'));
        $this->assertStringStartsWith('Die PHP-Funktion mail() hat die Nachricht nicht angenommen (Failed to connect to mailserver', $mailer->lastError());
        $this->assertStringContainsString('[PowerNews] E-Mail-Versand über PHP mail() fehlgeschlagen – Die PHP-Funktion mail()', (string) file_get_contents($this->logFile));
    }

    #[Test]
    public function invalidAddressesAreRefusedBeforeAnythingIsSent(): void
    {
        $sent = false;
        $mailer = Mailer::fromConfig([])->withMailFunction(static function () use (&$sent): bool {
            $sent = true;

            return true;
        });

        $this->assertFalse($mailer->send("opfer@example.org\r\nBcc: alle@example.org", 's', 'b', 'news@example.org'));
        $this->assertSame('Ungültige Empfängeradresse.', $mailer->lastError());
        $this->assertFalse($mailer->send('opfer@example.org', 's', 'b', 'kein-absender'));
        $this->assertStringStartsWith('Ungültige Absenderadresse', $mailer->lastError());
        $this->assertFalse($sent);
        $this->assertStringContainsString('fehlgeschlagen – Ungültige Empfängeradresse.', (string) file_get_contents($this->logFile));
    }

    #[Test]
    public function unknownTransportIsNeverSentSomehow(): void
    {
        $mailer = Mailer::fromConfig(['transport' => 'sendmail'])->withMailFunction(static fn (): bool => true);

        $this->assertFalse($mailer->send('to@example.com', 's', 'b', 'from@example.com'));
        $this->assertSame('Unbekannte Versandart „sendmail“ – erlaubt sind mail und smtp.', $mailer->lastError());
    }

    #[Test]
    public function wrongTypesInTheConfigurationFailLoudly(): void
    {
        $this->assertFalse(Mailer::fromConfig(['transport' => true])->withMailFunction(static fn (): bool => true)->send('to@example.com', 's', 'b', 'from@example.com'));

        $mailer = Mailer::fromConfig(['transport' => 'smtp', 'host' => '127.0.0.1', 'port' => 1, 'encryption' => ['starttls']]);
        $this->assertFalse($mailer->send('to@example.com', 's', 'b', 'from@example.com'));
        $this->assertStringStartsWith('Unbekannte Verschlüsselung', $mailer->lastError());
    }

    // ── Versandart „smtp“ ──

    #[Test]
    public function smtpConversationDeliversTheMessage(): void
    {
        [$result, $transcript] = $this->converse('ok', fn (int $port): bool => $this->smtp($port)->send(
            'to@example.com',
            'Hallo Welt',
            "Zeile 1\nZeile 2",
            'from@example.com',
            'PowerNews',
        ));

        $this->assertTrue($result);
        $this->assertSame(
            ['EHLO', 'MAIL FROM:<from@example.com>', 'RCPT TO:<to@example.com>', 'DATA', 'QUIT'],
            $this->commands($transcript),
        );
        $data = $this->data($transcript);
        $this->assertStringContainsString("From: PowerNews <from@example.com>\nTo: to@example.com\nReply-To: from@example.com\nSubject: Hallo Welt", $data);
        $this->assertMatchesRegularExpression('/^Date: .+\d{4} \d\d:\d\d:\d\d [+-]\d{4}$/m', $data);
        $this->assertMatchesRegularExpression('/^Message-ID: <[0-9a-f]{32}@example\.com>$/m', $data);
        $this->assertSame('', (string) file_get_contents($this->logFile));
    }

    #[Test]
    public function umlautsGoAsQuotedPrintableUnless8bitmimeIsOffered(): void
    {
        [, $plain] = $this->converse('ok', fn (int $port): bool => $this->smtp($port)->send('to@example.com', 'Grüße', 'Schöne Grüße', 'from@example.com'));
        [, $eightBit] = $this->converse('8bitmime', fn (int $port): bool => $this->smtp($port)->send('to@example.com', 'Grüße', 'Schöne Grüße', 'from@example.com'));

        $this->assertContains('MAIL FROM:<from@example.com>', $this->commands($plain));
        $this->assertStringContainsString("Content-Transfer-Encoding: quoted-printable\n\nSch=C3=B6ne Gr=C3=BC=C3=9Fe", $this->data($plain));
        $this->assertStringContainsString('Subject: =?UTF-8?B?' . base64_encode('Grüße') . '?=', $this->data($plain));

        $this->assertContains('MAIL FROM:<from@example.com> BODY=8BITMIME', $this->commands($eightBit));
        $this->assertStringContainsString('Content-Transfer-Encoding: 8bit', $this->data($eightBit));
    }

    #[Test]
    public function dotStuffingIsAppliedOnTheWire(): void
    {
        [$result, $transcript] = $this->converse('ok', fn (int $port): bool => $this->smtp($port)->send(
            'to@example.com',
            's',
            "Hallo\n.\nRCPT TO:<victim@example.org>\n.Punkt",
            'from@example.com',
        ));

        $this->assertTrue($result);
        $this->assertStringContainsString("D: ..\nD: RCPT TO:<victim@example.org>\nD: ..Punkt\n", $transcript);
        $this->assertStringNotContainsString('C: RCPT TO:<victim@example.org>', $transcript);
    }

    #[Test]
    public function ehloUsesHostnameOrAddressLiteral(): void
    {
        [, $transcript] = $this->converse('ok', fn (int $port): bool => $this->smtp($port)->send('to@example.com', 's', 'b', 'from@example.com'));

        $this->assertMatchesRegularExpression('/^C: EHLO (?:[a-z0-9.-]+\.[a-z0-9-]+|\[[0-9.]+\]|\[IPv6:[0-9a-f:]+\])$/m', $transcript);
    }

    #[Test]
    public function multilineGreetingIsUnderstood(): void
    {
        [$result] = $this->converse('multiline-220', fn (int $port): bool => $this->smtp($port)->send('to@example.com', 's', 'b', 'from@example.com'));

        $this->assertTrue($result);
    }

    #[Test]
    public function unexpectedReplyIsReportedWithCodeAndText(): void
    {
        $mailer = null;
        [$result] = $this->converse('reject-helo', function (int $port) use (&$mailer): bool {
            $mailer = $this->smtp($port);

            return $mailer->send('to@example.com', 's', 'b', 'from@example.com');
        });

        $this->assertFalse($result);
        $this->assertInstanceOf(Mailer::class, $mailer);
        // Nach „500“ auf EHLO versucht der Mailer HELO – der Server hat da schon aufgelegt.
        $this->assertSame('HELO: Der Server hat die Verbindung beendet.', $mailer->lastError());
    }

    #[Test]
    public function rejectedRecipientIsReported(): void
    {
        $mailer = null;
        [$result] = $this->converse('rcpt-rejected', function (int $port) use (&$mailer): bool {
            $mailer = $this->smtp($port);

            return $mailer->send('to@example.com', 's', 'b', 'from@example.com');
        });

        $this->assertFalse($result);
        $this->assertInstanceOf(Mailer::class, $mailer);
        $this->assertSame('Empfänger (RCPT TO): Server antwortet 550 5.1.1 Mailbox unavailable', $mailer->lastError());
    }

    #[Test]
    public function ehloFallsBackToHeloForOldServers(): void
    {
        [$result, $transcript] = $this->converse('ehlo-unknown', fn (int $port): bool => $this->smtp($port)->send('to@example.com', 's', 'b', 'from@example.com'));

        $this->assertTrue($result);
        $this->assertSame(['EHLO', 'HELO', 'MAIL FROM:<from@example.com>', 'RCPT TO:<to@example.com>', 'DATA', 'QUIT'], $this->commands($transcript));
    }

    #[Test]
    public function ehloRejectionIsFatalWhenLoginIsNeeded(): void
    {
        [$result, $transcript] = $this->converse('ehlo-unknown', fn (int $port): bool => $this->smtp($port, 'none', self::USER, self::PASSWORD)->send('to@example.com', 's', 'b', 'from@example.com'));

        $this->assertFalse($result);
        $this->assertSame(['EHLO'], $this->commands($transcript));
        $this->assertStringContainsString('EHLO: Server antwortet 502 5.5.1 Command not implemented', (string) file_get_contents($this->logFile));
    }

    #[Test]
    public function missingQuitReplyDoesNotFailAnAcceptedMail(): void
    {
        [$result] = $this->converse('no-quit', fn (int $port): bool => $this->smtp($port)->send('to@example.com', 's', 'b', 'from@example.com'));

        $this->assertTrue($result);
    }

    #[Test]
    public function silentServerRunsIntoTimeout(): void
    {
        $mailer = null;
        [$result] = $this->converse('silent', static function (int $port) use (&$mailer): bool {
            $mailer = new Mailer('smtp', '127.0.0.1', $port, 'none', '', '', 1);

            return $mailer->send('to@example.com', 's', 'b', 'from@example.com');
        });

        $this->assertFalse($result);
        $this->assertInstanceOf(Mailer::class, $mailer);
        $this->assertSame('Begrüßung: Zeitüberschreitung – der Server antwortet nicht.', $mailer->lastError());
    }

    #[Test]
    public function unreachableServerIsReported(): void
    {
        // Port 1 ist auf 127.0.0.1 mit hoher Wahrscheinlichkeit nicht belegt.
        $mailer = new Mailer('smtp', '127.0.0.1', 1, 'none', '', '', 1);

        $this->assertFalse($mailer->send('to@example.com', 's', 'b', 'from@example.com'));
        $this->assertStringStartsWith('Verbindungsaufbau fehlgeschlagen: ', $mailer->lastError());
        $this->assertStringContainsString('[PowerNews] E-Mail-Versand über 127.0.0.1:1 (Verschlüsselung none, ohne Anmeldung) fehlgeschlagen', (string) file_get_contents($this->logFile));
    }

    #[Test]
    public function missingHostIsReported(): void
    {
        $mailer = Mailer::fromConfig(['transport' => 'smtp', 'host' => ' ']);

        $this->assertFalse($mailer->send('to@example.com', 's', 'b', 'from@example.com'));
        $this->assertSame('Es ist kein SMTP-Server eingestellt.', $mailer->lastError());
    }

    // ── Anmeldung ──

    #[Test]
    public function authPlainSendsCredentialsAfterEhlo(): void
    {
        [$result, $transcript] = $this->converse('auth-plain', fn (int $port): bool => $this->smtp($port, 'none', self::USER, self::PASSWORD)->send('to@example.com', 's', 'b', 'from@example.com'));

        $this->assertTrue($result);
        $this->assertSame(
            ['EHLO', 'AUTH PLAIN ' . base64_encode("\0" . self::USER . "\0" . self::PASSWORD), 'MAIL FROM:<from@example.com> BODY=8BITMIME', 'RCPT TO:<to@example.com>', 'DATA', 'QUIT'],
            $this->commands($transcript),
        );
    }

    #[Test]
    public function authLoginIsUsedWhenPlainIsNotOffered(): void
    {
        [$result, $transcript] = $this->converse('auth-login', fn (int $port): bool => $this->smtp($port, 'none', self::USER, self::PASSWORD)->send('to@example.com', 's', 'b', 'from@example.com'));

        $this->assertTrue($result);
        $this->assertSame(
            ['EHLO', 'AUTH LOGIN', base64_encode(self::USER), base64_encode(self::PASSWORD), 'MAIL FROM:<from@example.com>', 'RCPT TO:<to@example.com>', 'DATA', 'QUIT'],
            $this->commands($transcript),
        );
    }

    #[Test]
    public function legacyAuthAnnouncementIsUnderstood(): void
    {
        [$result, $transcript] = $this->converse('auth-legacy', fn (int $port): bool => $this->smtp($port, 'none', self::USER, self::PASSWORD)->send('to@example.com', 's', 'b', 'from@example.com'));

        $this->assertTrue($result);
        $this->assertContains('AUTH LOGIN', $this->commands($transcript));
    }

    #[Test]
    public function noLoginWithoutUserEvenIfTheServerOffersIt(): void
    {
        [$result, $transcript] = $this->converse('auth-optional', fn (int $port): bool => $this->smtp($port, 'none', '', 'ungenutzt')->send('to@example.com', 's', 'b', 'from@example.com'));

        $this->assertTrue($result);
        $this->assertStringNotContainsString('AUTH', $transcript);
    }

    #[Test]
    public function rejectedLoginIsLoggedWithCodeButWithoutCredentials(): void
    {
        $mailer = null;
        [$result, $transcript] = $this->converse('auth-rejected', function (int $port) use (&$mailer): bool {
            $mailer = $this->smtp($port, 'none', self::USER, self::PASSWORD);

            return $mailer->send('to@example.com', 's', 'b', 'from@example.com');
        });

        $this->assertFalse($result);
        $this->assertStringNotContainsString('MAIL FROM', $transcript);
        $this->assertInstanceOf(Mailer::class, $mailer);
        $this->assertSame(
            'Anmeldung (AUTH PLAIN): Server antwortet 535 5.7.8 Authentication credentials invalid – Benutzername und Passwort prüfen.',
            $mailer->lastError(),
        );

        $log = (string) file_get_contents($this->logFile);
        $this->assertStringContainsString('(Verschlüsselung none, mit Anmeldung) fehlgeschlagen – Anmeldung (AUTH PLAIN): Server antwortet 535', $log);
        $this->assertCredentialsNotIn($log);
        $this->assertCredentialsNotIn($mailer->lastError());
    }

    #[Test]
    public function unsupportedAuthMethodIsReported(): void
    {
        $mailer = null;
        [$result, $transcript] = $this->converse('auth-cram', function (int $port) use (&$mailer): bool {
            $mailer = $this->smtp($port, 'none', self::USER, self::PASSWORD);

            return $mailer->send('to@example.com', 's', 'b', 'from@example.com');
        });

        $this->assertFalse($result);
        $this->assertSame(['EHLO'], $this->commands($transcript));
        $this->assertInstanceOf(Mailer::class, $mailer);
        $this->assertSame('Anmeldung: Der Server bietet nur CRAM-MD5 an, unterstützt werden PLAIN und LOGIN.', $mailer->lastError());
    }

    #[Test]
    public function userWithoutAuthSupportIsAnError(): void
    {
        $mailer = null;
        [$result] = $this->converse('no-auth', function (int $port) use (&$mailer): bool {
            $mailer = $this->smtp($port, 'none', self::USER, self::PASSWORD);

            return $mailer->send('to@example.com', 's', 'b', 'from@example.com');
        });

        $this->assertFalse($result);
        $this->assertInstanceOf(Mailer::class, $mailer);
        $this->assertStringStartsWith('Anmeldung: Der Server bietet keine Anmeldung (AUTH) an – viele Server erlauben sie erst nach STARTTLS.', $mailer->lastError());
    }

    // ── Verschlüsselung ──

    #[Test]
    public function starttlsNotOfferedAbortsBeforeCredentialsAreSent(): void
    {
        $mailer = null;
        [$result, $transcript] = $this->converse('no-starttls', function (int $port) use (&$mailer): bool {
            $mailer = $this->smtp($port, 'starttls', self::USER, self::PASSWORD);

            return $mailer->send('to@example.com', 's', 'b', 'from@example.com');
        });

        $this->assertFalse($result);
        // Kein Rückfall auf eine unverschlüsselte Anmeldung
        $this->assertSame(['EHLO'], $this->commands($transcript));
        $this->assertCredentialsNotIn($transcript);
        $this->assertInstanceOf(Mailer::class, $mailer);
        $this->assertStringStartsWith('STARTTLS: Der Server bietet keine Verschlüsselung per STARTTLS an', $mailer->lastError());
        $this->assertStringContainsString('(Verschlüsselung starttls, mit Anmeldung) fehlgeschlagen – STARTTLS:', (string) file_get_contents($this->logFile));
    }

    #[Test]
    public function failedTlsHandshakeIsHandled(): void
    {
        $mailer = null;
        [$result, $transcript] = $this->converse('starttls-broken', function (int $port) use (&$mailer): bool {
            $mailer = $this->smtp($port, 'starttls', self::USER, self::PASSWORD);

            return $mailer->send('to@example.com', 's', 'b', 'from@example.com');
        });

        $this->assertFalse($result);
        $this->assertSame(['EHLO', 'STARTTLS'], $this->commands($transcript));
        $this->assertCredentialsNotIn($transcript);
        $this->assertInstanceOf(Mailer::class, $mailer);
        $this->assertStringStartsWith('STARTTLS: TLS-Aushandlung fehlgeschlagen: ', $mailer->lastError());
        $this->assertCredentialsNotIn((string) file_get_contents($this->logFile));
    }

    #[Test]
    public function starttlsRefusedByTheServer(): void
    {
        $mailer = null;
        [$result, $transcript] = $this->converse('starttls-refused', function (int $port) use (&$mailer): bool {
            $mailer = $this->smtp($port, 'starttls', self::USER, self::PASSWORD);

            return $mailer->send('to@example.com', 's', 'b', 'from@example.com');
        });

        $this->assertFalse($result);
        $this->assertSame(['EHLO', 'STARTTLS'], $this->commands($transcript));
        $this->assertInstanceOf(Mailer::class, $mailer);
        $this->assertSame('STARTTLS: Server antwortet 454 4.7.0 TLS not available due to temporary reason', $mailer->lastError());
    }

    #[Test]
    public function starttlsIsUsedWithoutLoginToo(): void
    {
        [$result, $transcript] = $this->converse('starttls-broken', fn (int $port): bool => $this->smtp($port, 'starttls')->send('to@example.com', 's', 'b', 'from@example.com'));

        $this->assertFalse($result);
        $this->assertSame(['EHLO', 'STARTTLS'], $this->commands($transcript));
    }

    #[Test]
    public function implicitTlsAgainstAPlainServerFailsCleanly(): void
    {
        $mailer = null;
        [$result, $transcript] = $this->converse('ok', function (int $port) use (&$mailer): bool {
            $mailer = $this->smtp($port, 'ssl', self::USER, self::PASSWORD);

            return $mailer->send('to@example.com', 's', 'b', 'from@example.com');
        });

        $this->assertFalse($result);
        $this->assertStringNotContainsString('EHLO', $transcript);
        $this->assertCredentialsNotIn($transcript);
        $this->assertInstanceOf(Mailer::class, $mailer);
        $this->assertStringStartsWith('SSL/TLS-Verbindung fehlgeschlagen: ', $mailer->lastError());
        $this->assertStringContainsString('(Verschlüsselung ssl, mit Anmeldung) fehlgeschlagen', (string) file_get_contents($this->logFile));
    }

    #[Test]
    public function unknownEncryptionIsNeverSentUnencrypted(): void
    {
        $mailer = new Mailer('smtp', '127.0.0.1', 1, 'tls-irgendwie', self::USER, self::PASSWORD, 1);

        $this->assertFalse($mailer->send('to@example.com', 's', 'b', 'from@example.com'));
        $this->assertSame('Unbekannte Verschlüsselung „tls-irgendwie“ – erlaubt sind none, starttls und ssl.', $mailer->lastError());
        $this->assertCredentialsNotIn((string) file_get_contents($this->logFile));
    }

    #[Test]
    public function fromConfigReadsHostPortCredentialsAndEncryption(): void
    {
        $mailer = null;
        [$result, $transcript] = $this->converse('no-starttls', static function (int $port) use (&$mailer): bool {
            $mailer = Mailer::fromConfig([
                'transport' => 'smtp',
                'host' => '127.0.0.1',
                'port' => (string) $port,
                'user' => self::USER,
                'password' => self::PASSWORD,
                'encryption' => ' STARTTLS ',
            ]);

            return $mailer->send('to@example.com', 's', 'b', 'from@example.com');
        });

        $this->assertFalse($result);
        $this->assertSame(['EHLO'], $this->commands($transcript));
        $this->assertInstanceOf(Mailer::class, $mailer);
        $this->assertStringStartsWith('STARTTLS:', $mailer->lastError());
    }

    // ── Hilfsfunktionen ──

    #[Test]
    public function extensionsAreParsed(): void
    {
        $extensions = Mailer::parseExtensions([
            '250-mail.example.com Hello [10.0.0.5]',
            '250-SIZE 35882577',
            '250-auth login plain',
            '250-AUTH=LOGIN',
            '250-STARTTLS',
            '250 8BITMIME',
        ]);

        $this->assertSame(['SIZE', 'AUTH', 'STARTTLS', '8BITMIME'], array_keys($extensions));
        $this->assertSame(['LOGIN', 'PLAIN'], $extensions['AUTH']);
        $this->assertSame([], $extensions['STARTTLS']);
        $this->assertSame([], Mailer::parseExtensions(['250 mail.example.com']));
    }

    /**
     * @return iterable<string, array{array<string, string>, string|false, string|false, string}>
     */
    public static function heloNames(): iterable
    {
        yield 'Servername der Website' => [['SERVER_NAME' => 'News.Example.com'], 'web01', '10.0.0.5:41234', 'news.example.com'];
        yield 'Hostname des Servers' => [['SERVER_NAME' => 'localhost'], 'web01.hoster.example', '10.0.0.5:41234', 'web01.hoster.example'];
        yield 'IPv4-Adressliteral' => [[], 'a1b2c3d4', '10.0.0.5:41234', '[10.0.0.5]'];
        yield 'IPv6-Adressliteral' => [[], false, '[2001:db8::5]:41234', '[IPv6:2001:db8::5]'];
        yield 'Schadcode im Host-Header' => [['SERVER_NAME' => "evil.example\r\nRCPT TO:<x@y.z>"], false, false, 'localhost.localdomain'];
        yield 'nichts bekannt' => [[], false, false, 'localhost.localdomain'];
    }

    /**
     * @param array<string, string> $server
     */
    #[Test]
    #[DataProvider('heloNames')]
    public function heloNameIsAHostnameOrAnAddressLiteral(array $server, string|false $hostname, string|false $localAddress, string $expected): void
    {
        $this->assertSame($expected, Mailer::heloName($server, $hostname, $localAddress));
    }

    private function smtp(int $port, string $encryption = 'none', string $user = '', string $password = ''): Mailer
    {
        return new Mailer('smtp', '127.0.0.1', $port, $encryption, $user, $password, 5);
    }

    private function assertCredentialsNotIn(string $text): void
    {
        $this->assertStringNotContainsString(self::PASSWORD, $text);
        $this->assertStringNotContainsString(base64_encode(self::PASSWORD), $text);
        $this->assertStringNotContainsString(base64_encode("\0" . self::USER . "\0" . self::PASSWORD), $text);
        $this->assertStringNotContainsString('AUTH PLAIN ', $text);
    }
}
