<?php

declare(strict_types=1);

/* PowerNews - PHP and MySQL based news script                          */
/* Copyright (c) 2001-2026 PowerScripts                                 */

/* MIT License - See LICENSE file for full license text                 */
/* https://github.com/schubertnico/PowerNews.git                        */

namespace PowerNews;

require_once __DIR__ . '/mailmessage.inc.php';
require_once __DIR__ . '/smtpconnection.inc.php';

/**
 * Versand aller PowerNews-Mails: Registrierung, „Passwort vergessen“, Mails aus dem
 * Adminbereich und die Test-Mail des Installers.
 *
 * Zwei Versandarten (Konfiguration: pninc/config.local.php bzw. PN_MAIL_*):
 *  - „mail“ (Standard, bisheriges Verhalten): PHP-Funktion mail() des Servers. Der
 *    Server bzw. Hoster verschickt die Mail über sendmail_path (z. B. msmtp).
 *  - „smtp“: eigener SMTP-Dialog mit dem Postausgangsserver – unverschlüsselt, per
 *    STARTTLS (meist Port 587) oder von Beginn an per SSL/TLS (meist Port 465), mit
 *    Anmeldung per AUTH PLAIN oder AUTH LOGIN. Das Zertifikat wird immer geprüft; über
 *    eine unverschlüsselte Verbindung meldet sich der Mailer nur an, wenn „none“
 *    ausdrücklich eingestellt ist.
 *
 * Kopfzeilen und Text baut MailMessage (From und Reply-To = Absenderadresse aus der
 * Konfiguration, RFC 2047, UTF-8). Fehler landen mit ihrem Grund im Fehlerprotokoll,
 * Passwort und AUTH-Zeilen nie.
 */
final class Mailer
{
    public const string TRANSPORT_MAIL = 'mail';

    public const string TRANSPORT_SMTP = 'smtp';

    public const string ENCRYPTION_NONE = 'none';

    public const string ENCRYPTION_STARTTLS = 'starttls';

    public const string ENCRYPTION_SSL = 'ssl';

    /**
     * Erlaubte Verschlüsselungen und ihr üblicher Port (Port 0 in der
     * Konfiguration = dieser Standard).
     */
    public const array DEFAULT_PORTS = [
        self::ENCRYPTION_NONE => 25,
        self::ENCRYPTION_STARTTLS => 587,
        self::ENCRYPTION_SSL => 465,
    ];

    /** Sekunden für Verbindungsaufbau und jede Antwort des SMTP-Servers. */
    public const int TIMEOUT = 10;

    /** @var array<string, mixed> zusätzliche SSL-Kontextoptionen, siehe withTlsOptions() */
    private array $tlsOptions = [];

    /** @var \Closure(string, string, string, array<string, string>): bool */
    private \Closure $mailFunction;

    private string $lastError = '';

    /**
     * @param int $port 0 = üblicher Port der Verschlüsselung (DEFAULT_PORTS)
     */
    public function __construct(
        private string $transport = self::TRANSPORT_MAIL,
        private string $host = 'localhost',
        private int $port = 0,
        private string $encryption = self::ENCRYPTION_NONE,
        private string $username = '',
        #[\SensitiveParameter]
        private string $password = '',
        private int $timeout = self::TIMEOUT,
    ) {
        $this->mailFunction = static fn (string $to, string $subject, string $body, array $headers): bool => mail($to, $subject, $body, $headers);
    }

    /**
     * Mailer aus der Konfiguration ($pn_config['mail'], siehe LocalConfig).
     * Fehlt ein Wert, gilt die Vorgabe (Versand per mail()); ein Wert mit falschem
     * Typ führt zu einem Fehler beim Senden, nie zu einem stillen Rückfall.
     *
     * @param array<array-key, mixed> $config
     */
    public static function fromConfig(#[\SensitiveParameter] array $config): self
    {
        $port = $config['port'] ?? 0;

        // Versandart und Verschlüsselung mit falschem Typ: absichtlich ungültig („?“), damit
        // nie stillschweigend anders (etwa unverschlüsselt) gesendet wird.
        return new self(
            self::stringOr($config, 'transport', self::TRANSPORT_MAIL, '?'),
            self::stringOr($config, 'host', 'localhost', 'localhost'),
            is_int($port) || (is_string($port) && ctype_digit($port)) ? (int) $port : 0,
            self::stringOr($config, 'encryption', self::ENCRYPTION_NONE, '?'),
            self::stringOr($config, 'user', '', ''),
            self::stringOr($config, 'password', '', ''),
        );
    }

    /**
     * Versandart aus der Konfiguration: mail oder smtp (Groß-/Kleinschreibung egal,
     * leer = mail). Unbekannte Werte ergeben null – dann wird nicht gesendet.
     */
    public static function normalizeTransport(string $value): ?string
    {
        $value = strtolower(trim($value));

        return match ($value) {
            '', self::TRANSPORT_MAIL => self::TRANSPORT_MAIL,
            self::TRANSPORT_SMTP => self::TRANSPORT_SMTP,
            default => null,
        };
    }

    /**
     * Verschlüsselung aus der Konfiguration: none, starttls oder ssl
     * (Groß-/Kleinschreibung egal, leer = none). Unbekannte Werte ergeben null.
     */
    public static function normalizeEncryption(string $value): ?string
    {
        $value = strtolower(trim($value));

        if ($value === '') {
            return self::ENCRYPTION_NONE;
        }

        return array_key_exists($value, self::DEFAULT_PORTS) ? $value : null;
    }

    /**
     * Wirksamer Port: der eingestellte oder der übliche Port der Verschlüsselung.
     */
    public static function effectivePort(int $port, string $encryption): int
    {
        if ($port >= 1 && $port <= 65535) {
            return $port;
        }

        return self::DEFAULT_PORTS[self::normalizeEncryption($encryption) ?? self::ENCRYPTION_NONE];
    }

    /**
     * Zusätzliche SSL-Kontextoptionen, z. B. ['cafile' => '/pfad/ca.pem'] für einen
     * Mailserver mit eigener Zertifizierungsstelle (Testumgebung). Die
     * Zertifikatsprüfung bleibt eingeschaltet, solange sie hier nicht ausdrücklich
     * abgeschaltet wird.
     *
     * @param array<string, mixed> $options
     */
    public function withTlsOptions(array $options): self
    {
        $clone = clone $this;
        $clone->tlsOptions = $options + $this->tlsOptions;

        return $clone;
    }

    /**
     * Ersetzt PHPs mail() (für Tests der Versandart „mail“).
     *
     * @param \Closure(string, string, string, array<string, string>): bool $mailFunction
     */
    public function withMailFunction(\Closure $mailFunction): self
    {
        $clone = clone $this;
        $clone->mailFunction = $mailFunction;

        return $clone;
    }

    /**
     * Grund des letzten Fehlschlags von send() ohne Zugangsdaten, sonst leer.
     */
    public function lastError(): string
    {
        return $this->lastError;
    }

    /**
     * Versandweg in Worten, ohne Benutzername und Passwort – z. B. „über PHP mail()“
     * oder „über smtp.example.org:587 (Verschlüsselung starttls, mit Anmeldung)“.
     */
    public function describe(): string
    {
        if (self::normalizeTransport($this->transport) !== self::TRANSPORT_SMTP) {
            return self::normalizeTransport($this->transport) === null
                ? 'mit unbekannter Versandart „' . SmtpConnection::clean($this->transport) . '“'
                : 'über PHP mail()';
        }

        return sprintf(
            'über %s:%d (Verschlüsselung %s, %s)',
            SmtpConnection::clean($this->host),
            self::effectivePort($this->port, $this->encryption),
            SmtpConnection::clean(trim($this->encryption) === '' ? self::ENCRYPTION_NONE : $this->encryption),
            $this->username !== '' ? 'mit Anmeldung' : 'ohne Anmeldung',
        );
    }

    /**
     * Verschickt eine Text-Mail.
     *
     * @param string $fromAddress Absenderadresse (From und Reply-To, bei SMTP auch MAIL FROM)
     * @param string $fromName Absendername, z. B. „PowerNews“
     *
     * @return bool true, wenn mail() bzw. der SMTP-Server die Mail angenommen hat
     */
    public function send(string $to, string $subject, string $body, string $fromAddress, string $fromName = ''): bool
    {
        $this->lastError = self::addressError($to, $fromAddress);

        try {
            if ($this->lastError !== '') {
                throw new \RuntimeException($this->lastError);
            }

            match (self::normalizeTransport($this->transport)) {
                self::TRANSPORT_MAIL => $this->sendWithMailFunction($to, $subject, $body, $fromAddress, $fromName),
                self::TRANSPORT_SMTP => $this->sendWithSmtp($to, $subject, $body, $fromAddress, $fromName),
                default => throw new \RuntimeException(
                    'Unbekannte Versandart „' . SmtpConnection::clean($this->transport) . '“ – erlaubt sind mail und smtp.',
                ),
            };
        } catch (\RuntimeException $e) {
            $this->lastError = $e->getMessage();
            error_log('[PowerNews] E-Mail-Versand ' . $this->describe() . ' fehlgeschlagen – ' . $this->lastError);

            return false;
        }

        return true;
    }

    /**
     * Erweiterungen aus der EHLO-Antwort, z. B. ['STARTTLS' => [], 'AUTH' => ['PLAIN', 'LOGIN']].
     * Versteht auch die alte Schreibweise „AUTH=LOGIN“.
     *
     * @param list<string> $lines Antwortzeilen ohne Zeilenende
     *
     * @return array<string, list<string>>
     */
    public static function parseExtensions(array $lines): array
    {
        $extensions = [];

        foreach (array_slice($lines, 1) as $line) {
            $words = preg_split('/[\s=]+/', strtoupper(trim(substr($line, 4))), -1, PREG_SPLIT_NO_EMPTY);

            if ($words === false || $words === []) {
                continue;
            }
            $keyword = array_shift($words);
            $extensions[$keyword] = array_values(array_unique(array_merge($extensions[$keyword] ?? [], $words)));
        }

        return $extensions;
    }

    /**
     * Name für EHLO/HELO (RFC 5321, 4.1.3): vollständiger Hostname des Webservers,
     * sonst die eigene IP-Adresse als Adressliteral.
     *
     * @param array<array-key, mixed> $server $_SERVER
     * @param string|false $hostname gethostname()
     * @param string|false $localAddress eigene Adresse der Verbindung, z. B. „10.0.0.5:41234“
     */
    public static function heloName(array $server, string|false $hostname, string|false $localAddress): string
    {
        foreach ([$server['SERVER_NAME'] ?? null, $hostname] as $candidate) {
            if (is_string($candidate) && self::isFqdn($candidate)) {
                return strtolower($candidate);
            }
        }

        $ip = is_string($localAddress) ? trim((string) preg_replace('/:\d+$/', '', $localAddress), '[]') : '';

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
            return '[' . $ip . ']';
        }

        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false ? '[IPv6:' . $ip . ']' : 'localhost.localdomain';
    }

    private function sendWithMailFunction(string $to, string $subject, string $body, string $fromAddress, string $fromName): void
    {
        $warnings = [];
        set_error_handler(static function (int $errno, string $errstr) use (&$warnings): bool {
            $warnings[] = (string) preg_replace('/^\w+\(\): /', '', $errstr);

            return true;
        });

        try {
            $accepted = ($this->mailFunction)(
                MailMessage::cleanAddress($to),
                MailMessage::encodeHeader($subject, strlen('Subject: ')),
                str_replace("\r\n", "\n", MailMessage::lineEndings($body)),
                MailMessage::headers($fromName, $fromAddress),
            );
        } finally {
            restore_error_handler();
        }

        if (!$accepted) {
            throw new \RuntimeException(
                'Die PHP-Funktion mail() hat die Nachricht nicht angenommen'
                . ($warnings !== [] ? ' (' . SmtpConnection::clean(implode(' / ', $warnings)) . ')' : '')
                . ' – bitte den Mailversand des Servers prüfen oder einen SMTP-Server einstellen.',
            );
        }
    }

    private function sendWithSmtp(string $to, string $subject, string $body, string $fromAddress, string $fromName): void
    {
        $mode = self::normalizeEncryption($this->encryption);

        if ($mode === null) {
            throw new \RuntimeException(
                'Unbekannte Verschlüsselung „' . SmtpConnection::clean($this->encryption) . '“ – erlaubt sind none, starttls und ssl.',
            );
        }

        if (trim($this->host) === '') {
            throw new \RuntimeException('Es ist kein SMTP-Server eingestellt.');
        }

        if ($mode !== self::ENCRYPTION_NONE && !extension_loaded('openssl')) {
            throw new \RuntimeException('Für verschlüsselten Versand fehlt die PHP-Erweiterung openssl.');
        }

        $port = self::effectivePort($this->port, $mode);
        $connection = $mode === self::ENCRYPTION_SSL
            ? SmtpConnection::tls($this->host, $port, $this->timeout, $this->tlsOptions)
            : SmtpConnection::plain($this->host, $port, $this->timeout, $this->tlsOptions);

        try {
            $extensions = $this->greet($connection, $mode);

            if ($this->username !== '') {
                $this->authenticate($connection, $mode, $extensions);
            }

            // 8-Bit-Text nur, wenn der Server 8BITMIME anbietet (RFC 6152), sonst quoted-printable.
            $eightBit = array_key_exists('8BITMIME', $extensions);
            $message = MailMessage::build(
                $to,
                $subject,
                $body,
                $fromAddress,
                $fromName,
                $eightBit ? MailMessage::ENCODING_8BIT : MailMessage::ENCODING_QUOTED_PRINTABLE,
            );

            $connection->command('MAIL FROM:<' . MailMessage::cleanAddress($fromAddress) . '>' . ($eightBit ? ' BODY=8BITMIME' : ''), 'Absender (MAIL FROM)', [250]);
            $connection->command('RCPT TO:<' . MailMessage::cleanAddress($to) . '>', 'Empfänger (RCPT TO)', [250, 251]);
            $connection->command('DATA', 'DATA', [354]);
            $connection->write(MailMessage::dotStuff($message) . ".\r\n", 'Nachricht');
            $connection->expect('Nachricht', [250]);
            $connection->quit();
        } finally {
            $connection->close();
        }
    }

    /**
     * Begrüßung, EHLO und – falls eingestellt – STARTTLS.
     *
     * @return array<string, list<string>> angebotene Erweiterungen
     */
    private function greet(SmtpConnection $connection, string $mode): array
    {
        $connection->expect('Begrüßung', [220]);
        $extensions = $this->hello($connection, $mode !== self::ENCRYPTION_NONE || $this->username !== '');

        if ($mode !== self::ENCRYPTION_STARTTLS) {
            return $extensions;
        }

        if (!array_key_exists('STARTTLS', $extensions)) {
            throw new \RuntimeException(
                'STARTTLS: Der Server bietet keine Verschlüsselung per STARTTLS an – '
                . 'bitte Port und Verschlüsselung prüfen (STARTTLS meist Port 587, SSL/TLS meist Port 465).',
            );
        }

        $connection->command('STARTTLS', 'STARTTLS', [220]);
        $connection->enableTls('STARTTLS');

        // Nach STARTTLS gilt nichts mehr aus der unverschlüsselten Sitzung (RFC 3207, 4.2).
        return $this->hello($connection, true);
    }

    /**
     * EHLO, bei alten Servern ohne ESMTP ersatzweise HELO.
     *
     * @return array<string, list<string>> angebotene Erweiterungen
     */
    private function hello(SmtpConnection $connection, bool $needsEsmtp): array
    {
        $name = self::heloName($_SERVER, gethostname(), $connection->localAddress());
        $connection->write('EHLO ' . $name . "\r\n", 'EHLO');
        $reply = $connection->readReply('EHLO');

        if ($reply['code'] === 250) {
            return self::parseExtensions($reply['lines']);
        }

        if ($needsEsmtp || $reply['code'] < 500) {
            throw new \RuntimeException(SmtpConnection::replyError('EHLO', $reply));
        }

        $connection->command('HELO ' . $name, 'HELO', [250]);

        return [];
    }

    /**
     * Anmeldung per AUTH PLAIN (bevorzugt) oder AUTH LOGIN. Fehlermeldungen enthalten
     * nur die Antworten des Servers, nie die gesendeten Zeilen.
     *
     * @param array<string, list<string>> $extensions
     */
    private function authenticate(SmtpConnection $connection, string $mode, array $extensions): void
    {
        if ($mode !== self::ENCRYPTION_NONE && !$connection->isEncrypted()) {
            throw new \RuntimeException('Anmeldung: abgebrochen, weil die Verbindung nicht verschlüsselt ist.');
        }

        $methods = $extensions['AUTH'] ?? null;

        if ($methods === null) {
            throw new \RuntimeException(
                'Anmeldung: Der Server bietet keine Anmeldung (AUTH) an'
                . ($mode === self::ENCRYPTION_NONE ? ' – viele Server erlauben sie erst nach STARTTLS.' : '.'),
            );
        }

        if (in_array('PLAIN', $methods, true)) {
            $connection->command('AUTH PLAIN ' . base64_encode("\0" . $this->username . "\0" . $this->password), 'Anmeldung (AUTH PLAIN)', [235]);

            return;
        }

        if (in_array('LOGIN', $methods, true)) {
            $connection->command('AUTH LOGIN', 'Anmeldung (AUTH LOGIN)', [334]);
            $connection->command(base64_encode($this->username), 'Anmeldung (AUTH LOGIN)', [334]);
            $connection->command(base64_encode($this->password), 'Anmeldung (AUTH LOGIN)', [235]);

            return;
        }

        throw new \RuntimeException(
            'Anmeldung: Der Server bietet nur ' . SmtpConnection::clean(implode(', ', $methods)) . ' an, unterstützt werden PLAIN und LOGIN.',
        );
    }

    /**
     * Fehlermeldung für ungültige Adressen, sonst leer. FILTER_VALIDATE_EMAIL lässt
     * keine Zeilenumbrüche zu – Kopfzeilen lassen sich so nicht einschleusen.
     */
    private static function addressError(string $to, string $fromAddress): string
    {
        if (filter_var($to, FILTER_VALIDATE_EMAIL) === false) {
            return 'Ungültige Empfängeradresse.';
        }

        return filter_var($fromAddress, FILTER_VALIDATE_EMAIL) === false
            ? 'Ungültige Absenderadresse – bitte im Adminbereich unter „Konfiguration“ eine gültige E-Mail-Adresse eintragen.'
            : '';
    }

    /**
     * Zeichenkette aus der Konfiguration: $default, wenn der Schlüssel fehlt,
     * $invalid bei einem anderen Typ.
     *
     * @param array<array-key, mixed> $config
     */
    private static function stringOr(#[\SensitiveParameter] array $config, string $key, string $default, string $invalid): string
    {
        $value = $config[$key] ?? null;

        if ($value === null) {
            return $default;
        }

        return is_string($value) ? $value : $invalid;
    }

    private static function isFqdn(string $name): bool
    {
        return strlen($name) <= 253
            && preg_match('/^(?:[A-Za-z0-9](?:[A-Za-z0-9-]{0,61}[A-Za-z0-9])?\.)+[A-Za-z0-9](?:[A-Za-z0-9-]{0,61}[A-Za-z0-9])?$/', $name) === 1;
    }
}
