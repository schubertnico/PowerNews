<?php

declare(strict_types=1);

/* PowerNews - PHP and MySQL based news script                          */
/* Copyright (c) 2001-2026 PowerScripts                                 */

/* MIT License - See LICENSE file for full license text                 */
/* https://github.com/schubertnico/PowerNews.git                        */

/*
 * Datenbank-Migrationen für Updates bestehender Installationen.
 *
 * Jede Migration läuft genau einmal. Ausgeführte Migrationen werden in der Tabelle
 * pn_migrations vermerkt, ein erneuter Aufruf ist deshalb gefahrlos. Aufruf aus dem
 * Update-Skript (oder direkt nach einer Neuinstallation):
 *
 *     $log = pn_run_migrations($pn_handler, $pn_config);
 *
 * Rückgabe: Name der Migration => kurzer Bericht (deutsch).
 */

/**
 * Alle bekannten Migrationen in Ausführungsreihenfolge.
 *
 * @return array<string, callable(mysqli, array<string, mixed>): string>
 */
function pn_migrations(): array
{
    return [
        '3.12-unslash-content' => 'pn_migration_unslash_content',
        '3.12-purge-legacy-admin-sessions' => 'pn_migration_purge_legacy_sessions',
    ];
}

/**
 * Führt alle noch nicht angewendeten Migrationen aus.
 *
 * @param array<string, mixed> $pn_config
 *
 * @return array<string, string>
 */
function pn_run_migrations(mysqli $db, array $pn_config): array
{
    mysqli_query(
        $db,
        'CREATE TABLE IF NOT EXISTS pn_migrations ('
        . '`name` varchar(100) NOT NULL, `applied_at` int(14) NOT NULL, PRIMARY KEY (`name`)'
        . ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci'
    );

    $applied = [];
    $result = mysqli_query($db, 'SELECT `name` FROM pn_migrations');

    if ($result instanceof mysqli_result) {
        while ($row = mysqli_fetch_row($result)) {
            $applied[(string) $row[0]] = true;
        }
    }

    $log = [];

    foreach (pn_migrations() as $name => $migration) {
        if (isset($applied[$name])) {
            continue;
        }

        $log[$name] = $migration($db, $pn_config);

        $stmt = mysqli_prepare($db, 'INSERT INTO pn_migrations (`name`, applied_at) VALUES (?, ?)');
        $now = time();
        mysqli_stmt_bind_param($stmt, 'si', $name, $now);
        mysqli_stmt_execute($stmt);
    }

    return $log;
}

/**
 * Macht ein addslashes() rückgängig, aber nur, wenn der Wert eindeutig eine addslashes-Ausgabe
 * ist: jeder Backslash maskiert \, ', " oder 0, und es gibt keine unmaskierten Anführungszeichen.
 * Texte wie „C:\Users“ oder „Peter's“ (ohne Backslash) bleiben unverändert.
 */
function pn_unslash_legacy(string $value): string
{
    if (!str_contains($value, '\\')) {
        return $value;
    }

    if (preg_match('/^(?:[^\\\\\'"\x00]|\\\\[\\\\\'"0])*$/s', $value) !== 1) {
        return $value;
    }

    return stripslashes($value);
}

/**
 * B03: Bis 3.11 wurden News, Kategorien und im Admin bearbeitete Kommentare zusätzlich mit
 * addslashes() gespeichert. Diese Migration entfernt die überzähligen Backslashes einmalig.
 *
 * @param array<string, mixed> $pn_config
 */
function pn_migration_unslash_content(mysqli $db, array $pn_config): string
{
    $targets = [
        (string) ($pn_config['newstable'] ?? 'pn_news') => ['title', 'text', 'moretext'],
        (string) ($pn_config['cattable'] ?? 'pn_categories') => ['name', 'description'],
        (string) ($pn_config['commenttable'] ?? 'pn_comments') => ['text'],
    ];
    $changed = 0;

    foreach ($targets as $table => $columns) {
        $conditions = implode(' OR ', array_map(static fn (string $column): string => 'LOCATE(CHAR(92), `' . $column . '`) > 0', $columns));
        $result = mysqli_query($db, 'SELECT id, `' . implode('`, `', $columns) . '` FROM `' . $table . '` WHERE ' . $conditions);

        if (!$result instanceof mysqli_result) {
            continue;
        }

        while ($row = mysqli_fetch_assoc($result)) {
            $updates = [];

            foreach ($columns as $column) {
                $original = (string) $row[$column];
                $cleaned = pn_unslash_legacy($original);

                if ($cleaned !== $original) {
                    $updates[$column] = $cleaned;
                }
            }

            if ($updates === []) {
                continue;
            }

            $set = implode(', ', array_map(static fn (string $column): string => '`' . $column . '` = ?', array_keys($updates)));
            $stmt = mysqli_prepare($db, 'UPDATE `' . $table . '` SET ' . $set . ' WHERE id = ?');
            $params = array_values($updates);
            $params[] = (int) $row['id'];
            mysqli_stmt_bind_param($stmt, str_repeat('s', count($updates)) . 'i', ...$params);
            mysqli_stmt_execute($stmt);
            ++$changed;
        }
    }

    return sprintf('%d Datensätze von überzähligen Backslashes bereinigt.', $changed);
}

/**
 * B25/B26: Admin-Sitzungen liefen bis 3.11 fast ein Jahr und wurden mit demselben Hash wie
 * Frontend-Sitzungen gespeichert. Sie werden beendet; abgelaufene Sitzungen entfernt.
 *
 * @param array<string, mixed> $pn_config
 */
function pn_migration_purge_legacy_sessions(mysqli $db, array $pn_config): string
{
    $maxLifetime = PN_SESSION_FRONTEND_LIFETIME + 86400;
    $stmt = mysqli_prepare($db, 'DELETE FROM pn_sessions WHERE expires - created > ?');
    mysqli_stmt_bind_param($stmt, 'i', $maxLifetime);
    mysqli_stmt_execute($stmt);
    $removed = mysqli_stmt_affected_rows($stmt);
    pn_sessions_purge_expired($db);

    return sprintf('%d langlaufende Admin-Sitzungen beendet.', max(0, (int) $removed));
}
