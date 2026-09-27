<?php

declare(strict_types=1);

/* PowerNews - PHP and MySQL based news script                          */
/* Copyright (c) 2001-2026 PowerScripts                                 */

/* MIT License - See LICENSE file for full license text                 */
/* https://github.com/schubertnico/PowerNews.git                        */

namespace PowerNews\Installer;

use mysqli;
use mysqli_sql_exception;
use PowerNews\LocalConfig;

/**
 * Verbindungstest, Prüfung auf bestehende Tabellen und das Einspielen von
 * Schema, Website-Einstellungen und Administrator – mit mysqli, wie das
 * übrige PowerNews.
 *
 * @phpstan-import-type DbConfig from LocalConfig
 * @phpstan-import-type WebsiteSettings from FormValidator
 *
 * @phpstan-type AdminData array{nickname: string, email: string, password_hash: string}
 */
final class DatabaseSetup
{
    public const int CONNECT_TIMEOUT = 5;

    /**
     * Rechte des ersten Administrators: alles.
     */
    private const array PERMISSION_COLUMNS = [
        'canreadtemplates', 'canwritetemplates', 'canreadconfig', 'canwriteconfig',
        'canreadusers', 'canwriteusers', 'canreadpermissions', 'canwritepermissions',
        'canreadcategories', 'canwritecategories', 'canreadnews', 'canwritenews',
        'canreadcomments', 'canwritecomments',
    ];

    /**
     * Baut eine eigene Verbindung, damit der Installer beliebige Zugangsdaten
     * prüfen kann. Wirft mysqli_sql_exception bei Fehlern.
     *
     * @param DbConfig $db
     */
    public static function connect(#[\SensitiveParameter] array $db): \mysqli
    {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        $mysqli = mysqli_init();

        if ($mysqli === false) {
            throw new \mysqli_sql_exception('mysqli_init() ist fehlgeschlagen.');
        }

        $mysqli->options(MYSQLI_OPT_CONNECT_TIMEOUT, self::CONNECT_TIMEOUT);

        // Warnungen wie „getaddrinfo failed“ nicht ausgeben – der Fehler kommt als Exception.
        set_error_handler(static fn (): bool => true);

        try {
            $mysqli->real_connect($db['host'], $db['user'], $db['password'], $db['database'], $db['port']);
        } finally {
            restore_error_handler();
        }

        $mysqli->set_charset('utf8mb4');

        return $mysqli;
    }

    /**
     * Verbindungstest für Schritt 2: Serverversion und vorhandene pn_-Tabellen.
     *
     * @param \Closure(DbConfig): \mysqli $connect
     * @param DbConfig $db
     *
     * @return array{ok: bool, code: int, server: ServerVersion|null, tables: list<string>}
     *                                                                                      code = MySQL-Fehlernummer, wenn ok false ist
     */
    public static function inspect(\Closure $connect, #[\SensitiveParameter] array $db): array
    {
        try {
            $mysqli = $connect($db);
            $server = ServerVersion::parse(self::serverVersion($mysqli));
            $tables = self::existingTables($mysqli);
            $mysqli->close();
        } catch (\mysqli_sql_exception $e) {
            return ['ok' => false, 'code' => $e->getCode(), 'server' => null, 'tables' => []];
        }

        return ['ok' => true, 'code' => 0, 'server' => $server, 'tables' => $tables];
    }

    /**
     * Versionskennung des Servers, z. B. „10.11.15-MariaDB-ubu2204“.
     */
    public static function serverVersion(\mysqli $mysqli): string
    {
        $result = $mysqli->query('SELECT VERSION()');
        $row = $result instanceof \mysqli_result ? $result->fetch_row() : null;

        return is_array($row) && is_string($row[0] ?? null) ? $row[0] : $mysqli->server_info;
    }

    /**
     * Vorhandene Tabellen mit dem Präfix pn_.
     *
     * @return list<string>
     */
    public static function existingTables(\mysqli $mysqli): array
    {
        $result = $mysqli->query("SHOW TABLES LIKE 'pn\\_%'");

        if (!$result instanceof \mysqli_result) {
            return [];
        }

        $tables = [];

        while (($row = $result->fetch_row()) !== null && $row !== false) {
            if (is_string($row[0] ?? null)) {
                $tables[] = $row[0];
            }
        }

        return $tables;
    }

    /**
     * Ist PowerNews in dieser Datenbank eingerichtet?
     *
     * @return bool|null true = pn_config hat mindestens eine Zeile,
     *                   false = Tabelle fehlt oder ist leer,
     *                   null = unklar (z. B. fehlende Rechte)
     */
    public static function hasConfigRow(\mysqli $mysqli): ?bool
    {
        try {
            $result = $mysqli->query('SELECT COUNT(*) FROM pn_config');
        } catch (\mysqli_sql_exception $e) {
            return $e->getCode() === 1146 ? false : null;
        }

        $row = $result instanceof \mysqli_result ? $result->fetch_row() : null;

        return is_array($row) && (int) ($row[0] ?? 0) > 0;
    }

    /**
     * Spielt Schema, Website-Einstellungen und Administrator ein.
     *
     * Schlägt ein Schritt fehl, werden die in diesem Lauf angelegten Tabellen
     * wieder entfernt, damit ein erneuter Versuch auf einer leeren Datenbank
     * beginnt. Bereits vorhandene Tabellen werden nie angefasst – deshalb
     * muss der Aufrufer vorher prüfen, dass keine pn_-Tabellen existieren.
     *
     * @param list<string> $statements
     * @param WebsiteSettings $website
     * @param AdminData $admin
     *
     * @throws \mysqli_sql_exception bei Datenbankfehlern (angelegte Tabellen sind dann entfernt)
     * @throws \UnexpectedValueException bei einem unvollständigen oder veränderten Schema
     *
     * @return int ID des Administrators
     */
    public static function install(
        \mysqli $mysqli,
        array $statements,
        array $website,
        #[\SensitiveParameter]
        array $admin,
        int $now,
    ): int {
        Schema::assertInstallable($statements);

        $created = [];

        try {
            foreach ($statements as $statement) {
                $mysqli->query($statement);
                $table = Schema::createdTable($statement);

                if ($table !== null) {
                    $created[] = $table;
                }
            }

            self::saveWebsite($mysqli, $website);

            return self::createAdmin($mysqli, $admin, $now);
        } catch (\mysqli_sql_exception|\UnexpectedValueException $e) {
            self::dropTables($mysqli, $created);

            throw $e;
        }
    }

    /**
     * Verständliche Fehlermeldung zu einer MySQL-Fehlernummer, ohne
     * Zugangsdaten. Die Originalmeldung des Servers nennt u. a. Benutzer und
     * Host und wird deshalb nie angezeigt.
     */
    public static function friendlyError(int $code): string
    {
        return match ($code) {
            1045, 1698 => 'Die Anmeldung am Datenbankserver ist fehlgeschlagen: Benutzername oder Passwort ist falsch.',
            1044 => 'Der Benutzer hat keine Berechtigung für diese Datenbank.',
            1049 => 'Die Datenbank existiert nicht. Bitte legen Sie sie zuerst an (z. B. im Kundenmenü Ihres Hosters) oder prüfen Sie den Namen.',
            1130 => 'Der Datenbankserver lässt keine Verbindungen von diesem Webserver zu.',
            2002, 2003 => 'Der Datenbankserver ist nicht erreichbar. Bitte prüfen Sie Server und Port.',
            2005 => 'Der Datenbankserver ist unbekannt. Bitte prüfen Sie die Schreibweise des Servernamens.',
            2006, 2013 => 'Die Verbindung zum Datenbankserver wurde unterbrochen. Bitte versuchen Sie es erneut.',
            1142, 1227 => 'Dem Datenbank-Benutzer fehlen Rechte (benötigt werden CREATE, DROP, SELECT, INSERT, UPDATE, DELETE, INDEX und ALTER).',
            1050 => 'In der Datenbank gibt es bereits eine PowerNews-Tabelle. Es wurde nichts überschrieben.',
            1366 => 'Eine Eingabe enthält Zeichen, die die Datenbank nicht speichern kann.',
            0 => 'Die Datenbank ist nicht erreichbar oder hat die Anfrage abgelehnt.',
            default => sprintf('Die Datenbank hat die Anfrage abgelehnt (Fehlercode %d).', $code),
        };
    }

    /**
     * Seiten-URL und Absenderadresse in die (einzige) Zeile von pn_config.
     *
     * @param WebsiteSettings $website
     */
    private static function saveWebsite(\mysqli $mysqli, array $website): void
    {
        $result = $mysqli->query('SELECT COUNT(*) FROM pn_config');
        $row = $result instanceof \mysqli_result ? $result->fetch_row() : null;

        if (!is_array($row) || (int) ($row[0] ?? 0) !== 1) {
            throw new \UnexpectedValueException('Die Schemadatei ' . Schema::FILENAME . ' enthält keine Grundeinstellungen (genau eine Zeile in pn_config).');
        }

        $stmt = self::prepare($mysqli, 'UPDATE pn_config SET url = ?, email = ?');
        $stmt->bind_param('ss', $website['url'], $website['email']);
        $stmt->execute();
        $stmt->close();
    }

    /**
     * Legt den Administrator mit allen Rechten an. Das Passwort liegt nur als
     * Hash vor (password_hash() mit PASSWORD_DEFAULT wie pnadmin_hash_password()).
     *
     * @param AdminData $admin
     */
    private static function createAdmin(\mysqli $mysqli, #[\SensitiveParameter] array $admin, int $now): int
    {
        $stmt = self::prepare(
            $mysqli,
            "INSERT INTO pn_users (nickname, email, password, registered, showemail, status) VALUES (?, ?, ?, ?, 'NO', 'Activated')",
        );
        $stmt->bind_param('sssi', $admin['nickname'], $admin['email'], $admin['password_hash'], $now);
        $stmt->execute();
        $stmt->close();

        $adminId = (int) $mysqli->insert_id;

        if ($adminId <= 0) {
            throw new \UnexpectedValueException('Der Administrator konnte nicht angelegt werden.');
        }

        $columns = implode(', ', self::PERMISSION_COLUMNS);
        $values = implode(', ', array_fill(0, count(self::PERMISSION_COLUMNS), "'YES'"));
        $stmt = self::prepare($mysqli, 'INSERT INTO pn_permissions (userid, ' . $columns . ') VALUES (?, ' . $values . ')');
        $stmt->bind_param('i', $adminId);
        $stmt->execute();
        $stmt->close();

        return $adminId;
    }

    /**
     * Vorbereitete Anweisung; mysqli_report(STRICT) wirft bei Fehlern bereits
     * selbst, die Prüfung auf false ist nur die Absicherung dafür.
     */
    private static function prepare(\mysqli $mysqli, string $sql): \mysqli_stmt
    {
        $stmt = $mysqli->prepare($sql);

        if ($stmt === false) {
            throw new \mysqli_sql_exception('Die Anweisung konnte nicht vorbereitet werden.', $mysqli->errno);
        }

        return $stmt;
    }

    /**
     * @param list<string> $tables in diesem Lauf angelegte Tabellen
     */
    private static function dropTables(\mysqli $mysqli, array $tables): void
    {
        foreach (array_reverse($tables) as $table) {
            if (preg_match('/^' . Schema::TABLE_PREFIX . '[a-z_]+$/', $table) !== 1) {
                continue;
            }

            try {
                $mysqli->query('DROP TABLE IF EXISTS `' . $table . '`');
            } catch (\mysqli_sql_exception $e) {
                // Aufräumen ist bestmöglich; der ursprüngliche Fehler zählt.
                error_log('PowerNews-Installer: Tabelle ' . $table . ' konnte nicht entfernt werden (Fehlercode ' . $e->getCode() . ').');
            }
        }
    }
}
