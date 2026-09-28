<?php

declare(strict_types=1);

/* PowerNews - PHP and MySQL based news script                          */
/* Copyright (c) 2001-2026 PowerScripts                                 */

/* MIT License - See LICENSE file for full license text                 */
/* https://github.com/schubertnico/PowerNews.git                        */

namespace PowerNews\Installer;

use PowerNews\LocalConfig;
use PowerNews\Mailer;

/**
 * Prüft die Formulare der Installer-Schritte 2 bis 4.
 *
 * Jede Methode liefert die bereinigten Werte und Fehlermeldungen, deren
 * Schlüssel dem Feldnamen (und der id) im Formular entspricht.
 *
 * @phpstan-import-type DbConfig from LocalConfig
 * @phpstan-import-type MailConfig from LocalConfig
 *
 * @phpstan-type WebsiteSettings array{url: string, email: string, language: string, mail: MailConfig}
 * @phpstan-type AdminInput array{nickname: string, email: string, password: string}
 */
final class FormValidator
{
    public const int DEFAULT_DB_PORT = 3306;

    public const int HOST_MAX = 255;

    public const int DB_NAME_MAX = 64;

    public const int DB_USER_MAX = 80;

    public const int DB_PASSWORD_MAX = 255;

    /**
     * Spaltenbreite von pn_config.url, pn_config.email und pn_users.email.
     */
    public const int URL_MAX = 250;

    public const int EMAIL_MAX = 250;

    /**
     * Nickname-Regeln wie bei der Registrierung (pn_validate_nickname()).
     */
    public const int NICKNAME_MIN = 3;

    public const int NICKNAME_MAX = 30;

    public const string NICKNAME_PATTERN = '/^[A-Za-z\x{00C4}\x{00D6}\x{00DC}\x{00E4}\x{00F6}\x{00FC}\x{00DF}0-9_.\-]{3,30}$/u';

    /**
     * Wie beim Passwortwechsel im Profil mindestens 8 Zeichen. bcrypt
     * berücksichtigt höchstens 72 Byte – längere Passwörter würden
     * unbemerkt abgeschnitten und werden deshalb abgelehnt.
     */
    public const int PASSWORD_MIN = 8;

    public const int PASSWORD_MAX_BYTES = 72;

    /**
     * Zugangsdaten des E-Mail-Postfachs für den SMTP-Versand.
     */
    public const int SMTP_USER_MAX = 255;

    public const int SMTP_PASSWORD_MAX = 255;

    /**
     * Schritt 2: Datenbankzugang. Das Passwort wird nicht getrimmt.
     *
     * @param array<array-key, mixed> $input
     *
     * @return array{values: DbConfig, errors: array<string, string>}
     */
    public static function database(#[\SensitiveParameter] array $input): array
    {
        $host = self::text($input, 'db_host');
        $port = self::port(self::text($input, 'db_port'));
        $name = self::text($input, 'db_name');
        $user = self::text($input, 'db_user');
        $password = is_string($input['db_password'] ?? null) ? $input['db_password'] : '';

        $errors = array_filter([
            'db_host' => self::fieldError(
                $host,
                self::isHostname($host),
                'Bitte geben Sie den Datenbankserver an (oft „localhost“).',
                'Der Servername enthält ungültige Zeichen. Erlaubt sind ein Rechnername wie „sql.example.org“ oder eine IP-Adresse – den Port bitte ins eigene Feld.',
            ),
            'db_port' => $port === null ? 'Der Port muss eine Zahl zwischen 1 und 65535 sein.' : null,
            'db_name' => self::fieldError(
                $name,
                preg_match('/^[A-Za-z0-9_$-]{1,' . self::DB_NAME_MAX . '}$/', $name) === 1,
                'Bitte geben Sie den Namen der Datenbank an.',
                'Der Datenbankname darf nur Buchstaben, Ziffern sowie _ - $ enthalten (höchstens 64 Zeichen).',
            ),
            'db_user' => self::fieldError(
                $user,
                self::isPlainText($user, self::DB_USER_MAX),
                'Bitte geben Sie den Datenbank-Benutzer an.',
                'Der Benutzername ist zu lang oder enthält ungültige Zeichen.',
            ),
            'db_password' => strlen($password) > self::DB_PASSWORD_MAX || str_contains($password, "\0")
                ? 'Das Passwort ist zu lang oder enthält ungültige Zeichen.'
                : null,
        ]);

        return [
            'values' => [
                'host' => $host,
                'port' => $port ?? self::DEFAULT_DB_PORT,
                'user' => $user,
                'password' => $password,
                'database' => $name,
            ],
            'errors' => $errors,
        ];
    }

    /**
     * Schritt 3: Adresse der Website, Absenderadresse, Sprache und Mailversand.
     *
     * @param array<array-key, mixed> $input
     * @param MailConfig|null $previousMail bereits gespeicherter Mailversand – ein leeres
     *                                      Passwortfeld behält dessen Passwort (gleicher Benutzer)
     *
     * @return array{values: WebsiteSettings, errors: array<string, string>}
     */
    public static function website(#[\SensitiveParameter] array $input, #[\SensitiveParameter] ?array $previousMail = null): array
    {
        $url = rtrim(self::text($input, 'site_url'), '/');
        $email = self::text($input, 'site_email');
        $language = self::text($input, 'site_language');

        $errors = array_filter([
            'site_url' => self::fieldError(
                $url,
                self::isWebUrl($url) && strlen($url) <= self::URL_MAX,
                'Bitte geben Sie die Adresse Ihrer Website an.',
                'Bitte geben Sie eine vollständige Adresse mit http:// oder https:// an (höchstens 250 Zeichen).',
            ),
            'site_email' => self::fieldError(
                $email,
                self::isEmail($email),
                'Bitte geben Sie die Absenderadresse für E-Mails an.',
                'Bitte geben Sie eine gültige E-Mail-Adresse an (höchstens 250 Zeichen).',
            ),
            'site_language' => isset(LocalConfig::LANGUAGES[$language]) ? null : 'Bitte wählen Sie eine Sprache aus der Liste.',
        ]);

        [$mail, $mailErrors] = self::mail($input, $previousMail);

        return [
            'values' => ['url' => $url, 'email' => $email, 'language' => $language, 'mail' => $mail],
            'errors' => $errors + $mailErrors,
        ];
    }

    /**
     * Schritt 4: Administrator. Angemeldet wird später mit Nickname und
     * Passwort; das Passwort wird wie im Login-Formular nicht getrimmt.
     *
     * @param array<array-key, mixed> $input
     *
     * @return array{values: AdminInput, errors: array<string, string>}
     */
    public static function admin(#[\SensitiveParameter] array $input): array
    {
        $nickname = self::text($input, 'admin_nickname');
        $email = self::text($input, 'admin_email');
        $password = is_string($input['admin_password'] ?? null) ? $input['admin_password'] : '';
        $confirm = is_string($input['admin_password_confirm'] ?? null) ? $input['admin_password_confirm'] : '';

        $errors = array_filter([
            'admin_nickname' => self::fieldError(
                $nickname,
                self::isNickname($nickname),
                'Bitte geben Sie einen Nickname an.',
                'Der Nickname muss 3 bis 30 Zeichen lang sein und darf nur Buchstaben (auch Umlaute), Ziffern sowie . _ - enthalten.',
            ),
            'admin_email' => self::fieldError(
                $email,
                self::isEmail($email),
                'Bitte geben Sie Ihre E-Mail-Adresse an.',
                'Bitte geben Sie eine gültige E-Mail-Adresse an (höchstens 250 Zeichen).',
            ),
            'admin_password' => self::passwordError($password),
        ]);

        if (!isset($errors['admin_password']) && $password !== $confirm) {
            $errors['admin_password_confirm'] = 'Die beiden Passwörter stimmen nicht überein.';
        }

        return [
            'values' => ['nickname' => $nickname, 'email' => $email, 'password' => $password],
            'errors' => $errors,
        ];
    }

    /**
     * Formularwerte des Mailversands für Schritt 3 (ohne Passwort – das wird nie
     * ausgegeben). Für einen neuen SMTP-Server ist STARTTLS auf Port 587 vorbelegt –
     * das verlangen die meisten Hoster.
     *
     * @param MailConfig $mail
     *
     * @return array<string, string>
     */
    public static function mailFormValues(#[\SensitiveParameter] array $mail): array
    {
        if ($mail['transport'] !== Mailer::TRANSPORT_SMTP) {
            return [
                'mail_transport' => Mailer::TRANSPORT_MAIL,
                'smtp_host' => '',
                'smtp_port' => (string) Mailer::DEFAULT_PORTS[Mailer::ENCRYPTION_STARTTLS],
                'smtp_encryption' => Mailer::ENCRYPTION_STARTTLS,
                'smtp_user' => '',
            ];
        }

        return [
            'mail_transport' => Mailer::TRANSPORT_SMTP,
            'smtp_host' => $mail['host'],
            'smtp_port' => (string) Mailer::effectivePort($mail['port'], $mail['encryption']),
            'smtp_encryption' => $mail['encryption'],
            'smtp_user' => $mail['user'],
        ];
    }

    /**
     * Rechnername, IPv4-Adresse oder IPv6-Adresse (ohne Klammern). Doppelpunkte
     * nur bei IPv6, damit „host:3306“ oder „p:host“ nicht als Server durchgehen.
     */
    public static function isHostname(string $host): bool
    {
        if (strlen($host) > self::HOST_MAX) {
            return false;
        }

        if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false) {
            return true;
        }

        return preg_match('/^[A-Za-z0-9](?:[A-Za-z0-9_.-]*[A-Za-z0-9])?$/', $host) === 1;
    }

    public static function isWebUrl(string $url): bool
    {
        if (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
            return false;
        }

        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    public static function isEmail(string $email): bool
    {
        return $email !== ''
            && strlen($email) <= self::EMAIL_MAX
            && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function isNickname(string $nickname): bool
    {
        return preg_match(self::NICKNAME_PATTERN, $nickname) === 1;
    }

    public static function passwordError(#[\SensitiveParameter] string $password): ?string
    {
        if ($password === '') {
            return 'Bitte geben Sie ein Passwort an.';
        }

        if (!mb_check_encoding($password, 'UTF-8') || mb_strlen($password, 'UTF-8') < self::PASSWORD_MIN) {
            return 'Das Passwort muss mindestens ' . self::PASSWORD_MIN . ' Zeichen lang sein.';
        }

        if (strlen($password) > self::PASSWORD_MAX_BYTES) {
            return 'Das Passwort darf höchstens ' . self::PASSWORD_MAX_BYTES . ' Zeichen lang sein (Umlaute und Sonderzeichen zählen doppelt).';
        }

        return null;
    }

    /**
     * Mailversand: „mail“ (Standard) braucht keine weiteren Angaben, die SMTP-Felder
     * werden dann ignoriert. Bei „smtp“ ist der Server Pflicht, ein leerer Port ergibt
     * den üblichen Port der Verschlüsselung, der Benutzer ist optional – mit Benutzer
     * ist ein Passwort Pflicht.
     *
     * @param array<array-key, mixed> $input
     * @param MailConfig|null $previous
     *
     * @return array{0: MailConfig, 1: array<string, string>}
     */
    private static function mail(#[\SensitiveParameter] array $input, #[\SensitiveParameter] ?array $previous): array
    {
        $transport = self::text($input, 'mail_transport');

        if ($transport === '' || $transport === Mailer::TRANSPORT_MAIL) {
            return [LocalConfig::DEFAULT_MAIL, []];
        }

        if ($transport !== Mailer::TRANSPORT_SMTP) {
            return [LocalConfig::DEFAULT_MAIL, ['mail_transport' => 'Bitte wählen Sie eine Versandart aus der Liste.']];
        }

        $host = self::text($input, 'smtp_host');
        $encryption = Mailer::normalizeEncryption(self::text($input, 'smtp_encryption'));
        $port = self::smtpPort(self::text($input, 'smtp_port'), $encryption ?? Mailer::ENCRYPTION_NONE);
        [$user, $password, $credentialErrors] = self::smtpCredentials($input, $host, $previous);

        $errors = array_filter([
            'smtp_host' => self::fieldError(
                $host,
                self::isHostname($host),
                'Bitte geben Sie den SMTP-Server an, z. B. „smtp.ihr-hoster.de“.',
                'Der SMTP-Server enthält ungültige Zeichen. Bitte nur den Namen angeben, z. B. „smtp.ihr-hoster.de“ – den Port ins eigene Feld.',
            ),
            'smtp_port' => $port === null ? 'Der Port muss eine Zahl zwischen 1 und 65535 sein.' : null,
            'smtp_encryption' => $encryption === null ? 'Bitte wählen Sie eine Verschlüsselung aus der Liste.' : null,
        ]);

        return [
            [
                'transport' => Mailer::TRANSPORT_SMTP,
                'host' => $host,
                'port' => $port ?? Mailer::DEFAULT_PORTS[Mailer::ENCRYPTION_NONE],
                'encryption' => $encryption ?? Mailer::ENCRYPTION_NONE,
                'user' => $user,
                'password' => $password,
            ],
            $errors + $credentialErrors,
        ];
    }

    /**
     * Benutzername und Passwort des Postfachs. Das Passwort wird nicht getrimmt; ohne
     * Benutzer wird keines gespeichert. Ein leeres Passwortfeld behält das bereits
     * eingegebene Passwort, solange Server und Benutzer gleich bleiben.
     *
     * @param array<array-key, mixed> $input
     * @param MailConfig|null $previous
     *
     * @return array{0: string, 1: string, 2: array<string, string>} Benutzer, Passwort, Fehler
     */
    private static function smtpCredentials(#[\SensitiveParameter] array $input, string $host, #[\SensitiveParameter] ?array $previous): array
    {
        $user = self::text($input, 'smtp_user');

        if ($user === '') {
            return ['', '', []];
        }

        if (!self::isPlainText($user, self::SMTP_USER_MAX)) {
            return [$user, '', ['smtp_user' => 'Der Benutzername ist zu lang oder enthält ungültige Zeichen.']];
        }

        $password = is_string($input['smtp_password'] ?? null) ? $input['smtp_password'] : '';

        if ($password === '' && $previous !== null && $previous['user'] === $user && $previous['host'] === $host) {
            $password = $previous['password'];
        }

        $error = match (true) {
            $password === '' => 'Bitte geben Sie das Passwort des E-Mail-Postfachs an.',
            strlen($password) > self::SMTP_PASSWORD_MAX || str_contains($password, "\0") => 'Das Passwort ist zu lang oder enthält ungültige Zeichen.',
            default => null,
        };

        return [$user, $password, $error === null ? [] : ['smtp_password' => $error]];
    }

    /**
     * Leerer Wert ergibt den üblichen Port der Verschlüsselung, ungültiger Wert null.
     */
    private static function smtpPort(string $value, string $encryption): ?int
    {
        if ($value === '') {
            return Mailer::effectivePort(0, $encryption);
        }

        $port = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 65535]]);

        return is_int($port) ? $port : null;
    }

    /**
     * Leerer Wert ergibt den Standardport 3306, ungültiger Wert null.
     */
    private static function port(string $value): ?int
    {
        if ($value === '') {
            return self::DEFAULT_DB_PORT;
        }

        $port = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 65535]]);

        return is_int($port) ? $port : null;
    }

    /**
     * Fehlermeldung für ein Pflichtfeld: eigene Meldung für leere Eingaben,
     * null bei gültigem Wert.
     */
    private static function fieldError(string $value, bool $valid, string $emptyMessage, string $invalidMessage): ?string
    {
        if ($value === '') {
            return $emptyMessage;
        }

        return $valid ? null : $invalidMessage;
    }

    /**
     * Nicht leer, gültiges UTF-8, höchstens $max Zeichen, keine Steuerzeichen.
     */
    private static function isPlainText(string $value, int $max): bool
    {
        return $value !== ''
            && mb_check_encoding($value, 'UTF-8')
            && mb_strlen($value, 'UTF-8') <= $max
            && preg_match('/[\x00-\x1F\x7F]/', $value) !== 1;
    }

    /**
     * @param array<array-key, mixed> $input
     */
    private static function text(array $input, string $key): string
    {
        $value = $input[$key] ?? '';

        return is_string($value) ? trim($value) : '';
    }
}
