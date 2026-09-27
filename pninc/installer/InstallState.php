<?php

declare(strict_types=1);

/* PowerNews - PHP and MySQL based news script                          */
/* Copyright (c) 2001-2026 PowerScripts                                 */

/* MIT License - See LICENSE file for full license text                 */
/* https://github.com/schubertnico/PowerNews.git                        */

namespace PowerNews\Installer;

use PowerNews\LocalConfig;

/**
 * Entscheidet, ob der Web-Installer laufen darf (Befunde B07 und B47).
 *
 * Der Installer ist gesperrt, sobald eines davon zutrifft:
 *   1. eine Sperrdatei existiert (pninc/install.lock oder logs/install.lock),
 *   2. pninc/config.local.php existiert,
 *   3. die konfigurierte Datenbank enthält eine Zeile in pn_config,
 *   4. per Umgebungsvariablen ist eine Datenbank eingerichtet, die gerade
 *      nicht erreichbar ist – ein Datenbankausfall darf den Installer nicht
 *      wieder öffnen.
 *
 * Gesperrt heißt: HTTP 403 mit Hinweisseite, bevor irgendetwas anderes
 * passiert. Der Installer verwirft niemals Tabellen.
 */
final class InstallState
{
    /**
     * Mögliche Orte der Sperrdatei, bevorzugter zuerst. logs/ muss laut
     * Systemprüfung beschreibbar sein, deshalb lässt sich die Sperre immer
     * setzen – auch wenn pninc/ schreibgeschützt ist.
     */
    public const array LOCK_FILES = ['pninc/install.lock', 'logs/install.lock'];

    public const string REASON_LOCK_FILE = 'lockfile';

    public const string REASON_LOCAL_CONFIG = 'config';

    public const string REASON_DATABASE = 'database';

    public const string REASON_UNREACHABLE = 'unreachable';

    /**
     * Reine Entscheidungslogik.
     *
     * @param bool|null $databaseInstalled true = pn_config hat eine Zeile,
     *                                     false = Datenbank erreichbar, aber ohne PowerNews,
     *                                     null = nicht erreichbar oder unklar
     * @param bool $defaultConfig Datenbank-Zugang entspricht den Vorgaben
     */
    public static function lockReason(
        bool $lockFileExists,
        bool $localConfigExists,
        ?bool $databaseInstalled,
        bool $defaultConfig,
    ): ?string {
        if ($lockFileExists) {
            return self::REASON_LOCK_FILE;
        }

        if ($localConfigExists) {
            return self::REASON_LOCAL_CONFIG;
        }

        if ($databaseInstalled === true) {
            return self::REASON_DATABASE;
        }

        if ($databaseInstalled === null && !$defaultConfig) {
            return self::REASON_UNREACHABLE;
        }

        return null;
    }

    /**
     * Ermittelt die Sperre. Die Datenbank wird nur befragt, wenn keine Datei
     * die Frage schon beantwortet.
     *
     * @param array<array-key, mixed> $db wirksamer Datenbank-Zugang
     * @param callable(): ?bool $probe prüft die konfigurierte Datenbank
     */
    public static function detectLockReason(string $rootDir, #[\SensitiveParameter] array $db, callable $probe): ?string
    {
        if (self::existingLockFile($rootDir) !== null) {
            return self::REASON_LOCK_FILE;
        }

        if (is_file($rootDir . '/' . LocalConfig::RELATIVE_PATH)) {
            return self::REASON_LOCAL_CONFIG;
        }

        return self::lockReason(false, false, $probe(), LocalConfig::isDefaultDatabase($db));
    }

    /**
     * Relativer Pfad der vorhandenen Sperrdatei oder null.
     */
    public static function existingLockFile(string $rootDir): ?string
    {
        foreach (self::LOCK_FILES as $lockFile) {
            if (is_file($rootDir . '/' . $lockFile)) {
                return $lockFile;
            }
        }

        return null;
    }

    /**
     * Relativer Pfad, an dem die Sperrdatei angelegt würde, oder null, wenn
     * keines der Verzeichnisse beschreibbar ist.
     */
    public static function lockTarget(string $rootDir): ?string
    {
        foreach (self::LOCK_FILES as $lockFile) {
            if (Requirements::isWritableDir(dirname($rootDir . '/' . $lockFile))) {
                return $lockFile;
            }
        }

        return null;
    }

    /**
     * Schreibt die Sperrdatei und liefert ihren relativen Pfad – oder null,
     * wenn das nicht möglich war. Der Aufrufer muss null sichtbar melden.
     */
    public static function writeLockFile(string $rootDir, string $timestamp): ?string
    {
        $content = 'PowerNews installiert am ' . preg_replace('/[^0-9: .-]/', '', $timestamp) . "\n"
            . "Solange diese Datei existiert, verweigert install.php jeden Aufruf.\n";

        foreach (self::LOCK_FILES as $lockFile) {
            $path = $rootDir . '/' . $lockFile;

            if (!Requirements::isWritableDir(dirname($path))) {
                continue;
            }

            set_error_handler(static fn (): bool => true);

            try {
                $written = file_put_contents($path, $content, LOCK_EX);
            } finally {
                restore_error_handler();
            }

            if ($written !== false && is_file($path)) {
                return $lockFile;
            }
        }

        return null;
    }
}
