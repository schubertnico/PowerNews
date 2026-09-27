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
 *   3. Vorgaben aus DEFAULT_DB bzw. DEFAULT_LANGUAGE
 *
 * Die Datei liefert per `return` ein Array und setzt keine globalen Variablen.
 * Sie wird von pninc/config.inc.php, dem Installer und update.php verwendet
 * und gehört deshalb nicht zum löschbaren Installer.
 *
 * @phpstan-type DbConfig array{host: string, port: int, user: string, password: string, database: string}
 * @phpstan-type Settings array{db: DbConfig, language: string, source: string}
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
        $source = self::isDefaultDatabase($db) ? self::SOURCE_DEFAULTS : self::SOURCE_ENVIRONMENT;

        if (!is_file($localFile)) {
            return ['db' => $db, 'language' => self::DEFAULT_LANGUAGE, 'source' => $source];
        }

        $settings = self::apply($db, self::DEFAULT_LANGUAGE, require $localFile);
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
     * Überlagert Datenbank-Zugang und Sprache mit dem Inhalt der
     * config.local.php. Unbekannte Schlüssel und Werte mit falschem Typ
     * werden ignoriert.
     *
     * @param DbConfig $db
     *
     * @return Settings
     */
    public static function apply(
        #[\SensitiveParameter]
        array $db,
        string $language,
        #[\SensitiveParameter]
        mixed $local,
    ): array {
        if (!is_array($local)) {
            return ['db' => $db, 'language' => $language, 'source' => self::SOURCE_FILE];
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
            'source' => self::SOURCE_FILE,
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
     */
    public static function render(#[\SensitiveParameter] array $db, string $language, string $generatedAt): string
    {
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
            ' * Umgebungsvariablen PN_DB_*. Bei einem Update bleibt die Datei erhalten.',
            ' *',
            ' * language: german-du, german-sie oder english',
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
