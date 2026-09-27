<?php

declare(strict_types=1);

/* PowerNews - PHP and MySQL based news script                          */
/* Copyright (c) 2001-2026 PowerScripts                                 */

/* MIT License - See LICENSE file for full license text                 */
/* https://github.com/schubertnico/PowerNews.git                        */

namespace PowerNews\Installer;

use PowerNews\LocalConfig;

/**
 * „Jetzt installieren“: Schema einspielen, Administrator anlegen,
 * config.local.php schreiben und den Installer sperren.
 *
 * @phpstan-import-type DbConfig from LocalConfig
 * @phpstan-import-type WebsiteSettings from FormValidator
 * @phpstan-import-type AdminData from DatabaseSetup
 *
 * @phpstan-type Outcome array{admin_id: int, config_written: bool, config_source: string, lock_file: string|null}
 */
final class Setup
{
    /**
     * @param \Closure(DbConfig): \mysqli $connect baut die Datenbankverbindung auf
     * @param DbConfig $database Zugang, der in config.local.php landet
     * @param WebsiteSettings $website
     * @param AdminData $admin
     *
     * @throws \RuntimeException mit verständlicher Meldung; die Datenbank ist dann unverändert
     *                           bzw. die in diesem Lauf angelegten Tabellen sind wieder entfernt
     *
     * @return Outcome
     */
    public static function run(
        string $rootDir,
        \Closure $connect,
        #[\SensitiveParameter]
        array $database,
        array $website,
        #[\SensitiveParameter]
        array $admin,
        string $timestamp,
    ): array {
        // Ohne Sperrdatei kein Abschluss (Befund B07) – geprüft, bevor die Datenbank berührt wird.
        if (InstallState::lockTarget($rootDir) === null) {
            throw new \RuntimeException(
                'Die Sperrdatei kann weder in pninc/ noch in logs/ angelegt werden. Ohne sie ließe sich der Installer später erneut aufrufen. '
                . 'Bitte machen Sie logs/ beschreibbar und versuchen Sie es erneut. Es wurde nichts verändert.',
            );
        }

        $statements = Schema::fromFile($rootDir . '/' . Schema::FILENAME);
        Schema::assertInstallable($statements);

        // Die Originalmeldungen des Servers nennen Benutzer und Host – sie werden nie weitergegeben.
        try {
            $mysqli = $connect($database);
            $tables = DatabaseSetup::existingTables($mysqli);
        } catch (\mysqli_sql_exception $e) {
            throw new \RuntimeException(
                'Die Installation ist fehlgeschlagen. ' . DatabaseSetup::friendlyError($e->getCode()) . ' Es wurde nichts verändert.',
                $e->getCode(),
            );
        }

        if ($tables !== []) {
            $mysqli->close();

            throw new \RuntimeException('Die Datenbank enthält inzwischen PowerNews-Tabellen. Es wurde nichts verändert.');
        }

        try {
            $adminId = DatabaseSetup::install($mysqli, $statements, $website, $admin, time());
        } catch (\mysqli_sql_exception $e) {
            throw new \RuntimeException(
                'Die Installation ist fehlgeschlagen. ' . DatabaseSetup::friendlyError($e->getCode()) . ' Bereits angelegte Tabellen wurden wieder entfernt.',
                $e->getCode(),
            );
        } finally {
            $mysqli->close();
        }

        $source = LocalConfig::render($database, $website['language'], $timestamp);

        return [
            'admin_id' => $adminId,
            'config_written' => LocalConfig::writeFile($rootDir . '/' . LocalConfig::RELATIVE_PATH, $source),
            'config_source' => $source,
            'lock_file' => InstallState::writeLockFile($rootDir, $timestamp),
        ];
    }
}
