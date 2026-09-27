<?php

declare(strict_types=1);

/* PowerNews - PHP and MySQL based news script                          */
/* Copyright (c) 2001-2026 PowerScripts                                 */

/* MIT License - See LICENSE file for full license text                 */
/* https://github.com/schubertnico/PowerNews.git                        */

namespace PowerNews\Installer;

use PowerNews\LocalConfig;

/**
 * Schritt 1 des Web-Installers: PHP-Version, Erweiterungen und Schreibrechte.
 *
 * @phpstan-type Check array{id: string, label: string, ok: bool, required: bool, detail: string}
 */
final class Requirements
{
    /**
     * Muss zu $pnRequiredPhp in install.php passen.
     */
    public const string MIN_PHP = '8.4.0';

    /**
     * @param array<array-key, mixed> $server $_SERVER
     * @param callable(string): bool $extensionLoaded z. B. extension_loaded(...)
     *
     * @return list<Check>
     */
    public static function check(string $rootDir, array $server, string $phpVersion, callable $extensionLoaded): array
    {
        $phpOk = self::phpVersionOk($phpVersion);
        $mysqliOk = $extensionLoaded('mysqli');
        $mbstringOk = $extensionLoaded('mbstring');
        $schemaOk = is_readable($rootDir . '/' . Schema::FILENAME);
        $logsOk = self::isWritableDir($rootDir . '/logs');
        $pnincOk = self::canWriteLocalConfig($rootDir);
        $httpsOk = self::isHttps($server);

        return [
            self::item('php', 'PHP ' . self::MIN_PHP . ' oder neuer', $phpOk, true, 'Gefunden: PHP ' . $phpVersion . '.'),
            self::item(
                'mysqli',
                'PHP-Erweiterung mysqli',
                $mysqliOk,
                true,
                $mysqliOk ? 'Vorhanden.' : 'Fehlt – bitte beim Hoster bzw. in der php.ini aktivieren. PowerNews greift ausschließlich über mysqli auf die Datenbank zu.',
            ),
            self::item(
                'mbstring',
                'PHP-Erweiterung mbstring',
                $mbstringOk,
                true,
                $mbstringOk ? 'Vorhanden.' : 'Fehlt – bitte beim Hoster bzw. in der php.ini aktivieren.',
            ),
            self::item(
                'schema',
                'Schemadatei ' . Schema::FILENAME . ' lesbar',
                $schemaOk,
                true,
                $schemaOk ? 'Vorhanden.' : 'Bitte ' . Schema::FILENAME . ' vollständig in das PowerNews-Verzeichnis hochladen.',
            ),
            self::item(
                'logs',
                'Verzeichnis logs/ beschreibbar',
                $logsOk,
                true,
                $logsOk
                    ? 'Beschreibbar. Hier landen Fehlerprotokolle und – falls nötig – die Sperrdatei des Installers.'
                    : 'Bitte die Rechte von logs/ anpassen (je nach Hoster 755, 775 oder 777, im FTP-Programm „Schreibrechte“).',
            ),
            self::item(
                'pninc',
                'Verzeichnis pninc/ beschreibbar (für config.local.php und install.lock)',
                $pnincOk,
                false,
                $pnincOk
                    ? 'Der Installer legt ' . LocalConfig::RELATIVE_PATH . ' und die Sperrdatei selbst an.'
                    : 'Nicht beschreibbar – kein Problem: Am Ende bietet der Installer ' . LocalConfig::FILENAME . ' zum Herunterladen an, und die Sperrdatei kommt nach logs/.',
            ),
            self::item(
                'dbserver',
                'Datenbankserver: ' . ServerVersion::requirement(),
                true,
                false,
                'Wird in Schritt 2 geprüft, sobald die Zugangsdaten eingegeben sind.',
            ),
            self::item(
                'https',
                'Verschlüsselte Verbindung (HTTPS)',
                $httpsOk,
                false,
                $httpsOk
                    ? 'Die Verbindung ist verschlüsselt.'
                    : 'Die Seite wurde ohne HTTPS aufgerufen. Zugangsdaten werden dann unverschlüsselt übertragen – rufen Sie den Installer nach Möglichkeit über https:// auf.',
            ),
        ];
    }

    /**
     * @param list<Check> $checks
     */
    public static function allRequiredMet(array $checks): bool
    {
        foreach ($checks as $check) {
            if ($check['required'] && !$check['ok']) {
                return false;
            }
        }

        return true;
    }

    public static function phpVersionOk(string $version): bool
    {
        return version_compare($version, self::MIN_PHP, '>=');
    }

    /**
     * @param array<array-key, mixed> $server $_SERVER
     */
    public static function isHttps(array $server): bool
    {
        $https = $server['HTTPS'] ?? '';

        if (is_string($https) && $https !== '' && strtolower($https) !== 'off') {
            return true;
        }

        $port = $server['SERVER_PORT'] ?? '';

        return (is_string($port) || is_int($port)) && (string) $port === '443';
    }

    /**
     * Kann der Installer pninc/config.local.php anlegen?
     */
    public static function canWriteLocalConfig(string $rootDir): bool
    {
        return !file_exists($rootDir . '/' . LocalConfig::RELATIVE_PATH) && self::isWritableDir($rootDir . '/pninc');
    }

    public static function isWritableDir(string $directory): bool
    {
        return is_dir($directory) && is_writable($directory);
    }

    /**
     * @return Check
     */
    private static function item(string $id, string $label, bool $ok, bool $required, string $detail): array
    {
        return ['id' => $id, 'label' => $label, 'ok' => $ok, 'required' => $required, 'detail' => $detail];
    }
}
