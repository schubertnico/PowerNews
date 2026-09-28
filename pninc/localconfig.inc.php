<?php

declare(strict_types=1);

/* PowerNews - PHP and MySQL based news script                          */
/* Copyright (c) 2001-2026 PowerScripts                                 */

/* MIT License - See LICENSE file for full license text                 */
/* https://github.com/schubertnico/PowerNews.git                        */

namespace PowerNews;

/**
 * Lokale Konfiguration (pninc/config.local.php).
 *
 * Rangfolge der Zugangsdaten (höchste zuerst):
 *   1. pninc/config.local.php – vom Web-Installer geschrieben oder von Hand angelegt
 *   2. Umgebungsvariablen PN_DB_HOST, PN_DB_PORT, PN_DB_USER, PN_DB_PASS, PN_DB_NAME
 *      bzw. für den Mailversand PN_MAIL_TRANSPORT, PN_MAIL_HOST, PN_MAIL_PORT,
 *      PN_MAIL_ENCRYPTION, PN_MAIL_USER, PN_MAIL_PASS
 *   3. Vorgaben aus DEFAULT_DB, DEFAULT_MAIL bzw. DEFAULT_LANGUAGE
 *
 * Jeder Wert wird einzeln überlagert: Fehlt ein Schlüssel in config.local.php, gilt
 * der Wert aus der Umgebung bzw. die Vorgabe.
 *
 * Die Datei liefert per `return` ein Array und setzt keine globalen Variablen.
 * Sie wird von pninc/config.inc.php, dem Installer und update.php verwendet
 * und gehört deshalb nicht zum löschbaren Installer.
 *
 * @phpstan-type DbConfig array{host: string, port: int, user: string, password: string, database: string}
 * @phpstan-type MailConfig array{transport: string, host: string, port: int, encryption: string, user: string, password: string}
 * @phpstan-type Settings array{db: DbConfig, language: string, mail: MailConfig, source: string}
 */
final class LocalConfig
{
    /**
     * Dateiname innerhalb von pninc/.
     */
    public const string FILENAME = 'config.local.php';

    /**
     * Pfad relativ zum PowerNews-Verzeichnis (für Meldungen und die Sperrlogik).
     */
    public const string RELATIVE_PATH = 'pninc/config.local.php';

    public const array DEFAULT_DB = [
        'host' => 'localhost',
        'port' => 3306,
        'user' => 'root',
        'password' => '',
        'database' => 'powernews',
    ];

    public const string DEFAULT_LANGUAGE = 'german-du';

    /**
     * Mailversand ab Werk: PHP-Funktion mail() des Servers (wie bis 3.11). Die übrigen
     * Werte gelten nur für transport „smtp“; port 0 = üblicher Port der Verschlüsselung
     * (none 25, starttls 587, ssl 465).
     */
    public const array DEFAULT_MAIL = [
        'transport' => 'mail',
        'host' => 'localhost',
        'port' => 0,
        'encryption' => 'none',
        'user' => '',
        'password' => '',
    ];

    /**
     * Sprachdateien in pninc/lang/ und pnadmin/lang/ (Wert => Beschriftung).
     */
    public const array LANGUAGES = [
        'german-du' => 'Deutsch (Du-Form)',
        'german-sie' => 'Deutsch (Sie-Form)',
        'english' => 'English',
    ];

    /**
     * Umgebungsvariablen je Schlüssel in DbConfig.
     */
    public const array ENVIRONMENT = [
        'host' => 'PN_DB_HOST',
        'port' => 'PN_DB_PORT',
        'user' => 'PN_DB_USER',
        'password' => 'PN_DB_PASS',
        'database' => 'PN_DB_NAME',
    ];

    /**
     * Umgebungsvariablen je Schlüssel in MailConfig.
     */
    public const array MAIL_ENVIRONMENT = [
        'transport' => 'PN_MAIL_TRANSPORT',
        'host' => 'PN_MAIL_HOST',
        'port' => 'PN_MAIL_PORT',
        'encryption' => 'PN_MAIL_ENCRYPTION',
        'user' => 'PN_MAIL_USER',
        'password' => 'PN_MAIL_PASS',
    ];

    public const string SOURCE_FILE = 'file';

    public const string SOURCE_ENVIRONMENT = 'environment';

    public const string SOURCE_DEFAULTS = 'defaults';

    /**
     * Wirksame Einstellungen: Vorgaben, überlagert von Umgebungsvariablen,
     * überlagert von config.local.php (falls vorhanden).
     *
     * @param callable(string): (string|false) $getenv z. B. static fn (string $name): string|false => getenv($name)
     *
     * @return Settings
     */
    public static function load(callable $getenv, string $localFile): array
    {
        $db = self::fromEnvironment($getenv);
        $mail = self::mailFromEnvironment($getenv);
        $source = self::isDefaultDatabase($db) ? self::SOURCE_DEFAULTS : self::SOURCE_ENVIRONMENT;

        if (!is_file($localFile)) {
            return ['db' => $db, 'language' => self::DEFAULT_LANGUAGE, 'mail' => $mail, 'source' => $source];
        }

        $settings = self::apply($db, self::DEFAULT_LANGUAGE, require $localFile, $mail);
        $settings['source'] = self::SOURCE_FILE;

        return $settings;
    }

    /**
     * Datenbank-Zugang aus den Umgebungsvariablen PN_DB_*. Leere oder
     * ungültige Werte ergeben die Vorgabe; das Passwort wird genau so
     * übernommen, wie es gesetzt ist.
     *
     * @param callable(string): (string|false) $getenv
     *
     * @return DbConfig
     */
    public static function fromEnvironment(callable $getenv): array
    {
        $value = static function (string $key) use ($getenv): ?string {
            $raw = $getenv(self::ENVIRONMENT[$key]);

            if (!is_string($raw)) {
                return null;
            }

            return $key === 'password' ? $raw : (trim($raw) !== '' ? trim($raw) : null);
        };

        $port = filter_var($value('port'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 65535]]);

        return [
            'host' => $value('host') ?? self::DEFAULT_DB['host'],
            'port' => is_int($port) ? $port : self::DEFAULT_DB['port'],
            'user' => $value('user') ?? self::DEFAULT_DB['user'],
            'password' => $value('password') ?? self::DEFAULT_DB['password'],
            'database' => $value('database') ?? self::DEFAULT_DB['database'],
        ];
    }

    /**
     * Mailversand aus den Umgebungsvariablen PN_MAIL_*. Leere oder ungültige Werte
     * ergeben die Vorgabe; das Passwort wird genau so übernommen, wie es gesetzt ist.
     * Versandart und Verschlüsselung prüft erst der Mailer – ein Tippfehler führt dort
     * zu einer Fehlermeldung statt zu einem stillen Rückfall.
     *
     * @param callable(string): (string|false) $getenv
     *
     * @return MailConfig
     */
    public static function mailFromEnvironment(callable $getenv): array
    {
        $value = static function (string $key) use ($getenv): ?string {
            $raw = $getenv(self::MAIL_ENVIRONMENT[$key]);

            if (!is_string($raw)) {
                return null;
            }

            return $key === 'password' ? $raw : (trim($raw) !== '' ? trim($raw) : null);
        };

        $port = filter_var($value('port'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 65535]]);

        return [
            'transport' => strtolower($value('transport') ?? self::DEFAULT_MAIL['transport']),
            'host' => $value('host') ?? self::DEFAULT_MAIL['host'],
            'port' => is_int($port) ? $port : self::DEFAULT_MAIL['port'],
            'encryption' => strtolower($value('encryption') ?? self::DEFAULT_MAIL['encryption']),
            'user' => $value('user') ?? self::DEFAULT_MAIL['user'],
            'password' => $value('password') ?? self::DEFAULT_MAIL['password'],
        ];
    }

    /**
     * Überlagert Datenbank-Zugang, Sprache und Mailversand mit dem Inhalt der
     * config.local.php. Unbekannte Schlüssel und Werte mit falschem Typ
     * werden ignoriert.
     *
     * @param DbConfig $db
     * @param MailConfig $mail Mailversand aus Umgebung bzw. Vorgaben
     *
     * @return Settings
     */
    public static function apply(
        #[\SensitiveParameter]
        array $db,
        string $language,
        #[\SensitiveParameter]
        mixed $local,
        #[\SensitiveParameter]
        array $mail = self::DEFAULT_MAIL,
    ): array {
        if (!is_array($local)) {
            return ['db' => $db, 'language' => $language, 'mail' => $mail, 'source' => self::SOURCE_FILE];
        }

        $override = is_array($local['db'] ?? null) ? $local['db'] : [];
        $localLanguage = $local['language'] ?? null;
        $port = $override['port'] ?? null;

        return [
            'db' => [
                'host' => self::stringOr($override, 'host', $db['host']),
                'port' => is_int($port) && $port >= 1 && $port <= 65535 ? $port : $db['port'],
                'user' => self::stringOr($override, 'user', $db['user']),
                'password' => self::stringOr($override, 'password', $db['password']),
                'database' => self::stringOr($override, 'database', $db['database']),
            ],
            'language' => is_string($localLanguage) && isset(self::LANGUAGES[$localLanguage]) ? $localLanguage : $language,
            'mail' => self::applyMail($mail, $local['mail'] ?? null),
            'source' => self::SOURCE_FILE,
        ];
    }

    /**
     * Überlagert den Mailversand mit dem Abschnitt 'mail' der config.local.php.
     *
     * @param MailConfig $mail
     *
     * @return MailConfig
     */
    public static function applyMail(#[\SensitiveParameter] array $mail, #[\SensitiveParameter] mixed $local): array
    {
        if (!is_array($local)) {
            return $mail;
        }

        $port = $local['port'] ?? null;

        return [
            'transport' => strtolower(trim(self::stringOr($local, 'transport', $mail['transport']))),
            'host' => trim(self::stringOr($local, 'host', $mail['host'])),
            'port' => is_int($port) && $port >= 0 && $port <= 65535 ? $port : $mail['port'],
            'encryption' => strtolower(trim(self::stringOr($local, 'encryption', $mail['encryption']))),
            'user' => self::stringOr($local, 'user', $mail['user']),
            'password' => self::stringOr($local, 'password', $mail['password']),
        ];
    }

    /**
     * Entspricht der Datenbank-Zugang den ausgelieferten Vorgaben? Dann ist
     * PowerNews weder per Umgebungsvariablen noch per config.local.php
     * eingerichtet.
     *
     * @param array<array-key, mixed> $db
     */
    public static function isDefaultDatabase(#[\SensitiveParameter] array $db): bool
    {
        foreach (self::DEFAULT_DB as $key => $default) {
            if (($db[$key] ?? null) !== $default) {
                return false;
            }
        }

        return true;
    }

    /**
     * Erzeugt den PHP-Quelltext der config.local.php.
     *
     * Alle Werte werden per var_export() als PHP-Literale geschrieben. So
     * bleiben Sonderzeichen in Passwörtern (' " \ $ ?> Zeilenumbrüche …)
     * unverändert und können den Code nicht verändern.
     *
     * @param DbConfig $db
     * @param MailConfig $mail
     */
    public static function render(
        #[\SensitiveParameter]
        array $db,
        string $language,
        string $generatedAt,
        #[\SensitiveParameter]
        array $mail = self::DEFAULT_MAIL,
    ): string {
        $generatedAt = (string) preg_replace('/[^0-9: .-]/', '', $generatedAt);
        $language = isset(self::LANGUAGES[$language]) ? $language : self::DEFAULT_LANGUAGE;

        $lines = [
            '<?php',
            '',
            'declare(strict_types=1);',
            '',
            '/*',
            ' * PowerNews – lokale Konfiguration',
            ' *',
            ' * Erzeugt vom Web-Installer am ' . $generatedAt . '.',
            ' *',
            ' * Diese Datei enthält Zugangsdaten: nicht weitergeben und nicht in ein',
            ' * Repository einchecken. Werte hier haben Vorrang vor den',
            ' * Umgebungsvariablen PN_DB_* und PN_MAIL_*. Bei einem Update bleibt die',
            ' * Datei erhalten.',
            ' *',
            ' * language: german-du, german-sie oder english',
            ' * mail.transport: mail (PHP-Funktion mail() des Servers) oder smtp',
            ' * mail.encryption: none, starttls oder ssl',
            ' * mail.port: 0 = Standard der Verschlüsselung (none 25, starttls 587, ssl 465)',
            ' */',
            '',
            'return [',
            "    'db' => [",
            "        'host' => " . var_export($db['host'], true) . ',',
            "        'port' => " . var_export($db['port'], true) . ',',
            "        'user' => " . var_export($db['user'], true) . ',',
            "        'password' => " . var_export($db['password'], true) . ',',
            "        'database' => " . var_export($db['database'], true) . ',',
            '    ],',
            "    'language' => " . var_export($language, true) . ',',
            "    'mail' => [",
            "        'transport' => " . var_export($mail['transport'], true) . ',',
            "        'host' => " . var_export($mail['host'], true) . ',',
            "        'port' => " . var_export($mail['port'], true) . ',',
            "        'encryption' => " . var_export($mail['encryption'], true) . ',',
            "        'user' => " . var_export($mail['user'], true) . ',',
            "        'password' => " . var_export($mail['password'], true) . ',',
            '    ],',
            '];',
        ];

        return implode("\n", $lines) . "\n";
    }

    /**
     * Legt die Datei exklusiv an (niemals überschreiben) und setzt die
     * Rechte auf 0640. Liefert false, wenn das nicht möglich war.
     */
    public static function writeFile(string $path, #[\SensitiveParameter] string $content): bool
    {
        $directory = dirname($path);

        if (file_exists($path) || !is_dir($directory) || !is_writable($directory)) {
            return false;
        }

        return self::quietly(static function () use ($path, $content): bool {
            $handle = fopen($path, 'x');

            if ($handle === false) {
                return false;
            }

            $written = fwrite($handle, $content);
            fclose($handle);

            if ($written !== strlen($content)) {
                unlink($path);

                return false;
            }

            chmod($path, 0o640);

            return true;
        });
    }

    /**
     * Führt eine Dateioperation aus, ohne dass PHP-Warnungen ausgegeben
     * werden (z. B. bei fehlenden Rechten); das Ergebnis zählt.
     *
     * @param callable(): bool $operation
     */
    private static function quietly(callable $operation): bool
    {
        set_error_handler(static fn (): bool => true);

        try {
            return $operation();
        } finally {
            restore_error_handler();
        }
    }

    /**
     * @param array<array-key, mixed> $source
     */
    private static function stringOr(array $source, string $key, string $default): string
    {
        $value = $source[$key] ?? null;

        return is_string($value) ? $value : $default;
    }
}
