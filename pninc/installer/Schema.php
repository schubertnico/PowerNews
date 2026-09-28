<?php

declare(strict_types=1);

/* PowerNews - PHP and MySQL based news script                          */
/* Copyright (c) 2001-2026 PowerScripts                                 */

/* MIT License - See LICENSE file for full license text                 */
/* https://github.com/schubertnico/PowerNews.git                        */

namespace PowerNews\Installer;

/**
 * Liest powernews.sql und zerlegt die Datei in einzelne Anweisungen.
 *
 * Web-Installer, update.php, Tests und `mysql < powernews.sql` verwenden
 * dieselbe Schemaquelle. Der Splitter beachtet Zeichenketten, Bezeichner und
 * Kommentare: Semikolons in HTML-Entitäten wie `&nbsp;` innerhalb der
 * Templates trennen keine Anweisung (Befund B01).
 */
final class Schema
{
    public const string FILENAME = 'powernews.sql';

    public const string TABLE_PREFIX = 'pn_';

    /**
     * Anweisungen, die der Installer ausführen darf. Alles andere (DROP,
     * ALTER, DELETE, fremde Tabellen …) wird abgelehnt, bevor die Datenbank
     * berührt wird.
     */
    private const array ALLOWED = [
        '/^CREATE\s+TABLE\s+`?pn_[a-z_]+`?\s*\(/i',
        '/^INSERT\s+INTO\s+`?pn_[a-z_]+`?[\s(]/i',
        '/^SET\s+NAMES\s+utf8mb4$/i',
    ];

    /**
     * @throws \RuntimeException wenn die Datei nicht lesbar ist
     *
     * @return list<string>
     */
    public static function fromFile(string $path): array
    {
        $sql = is_file($path) && is_readable($path) ? file_get_contents($path) : false;

        if ($sql === false) {
            throw new \RuntimeException('Die Schemadatei ' . basename($path) . ' fehlt oder ist nicht lesbar. Bitte laden Sie sie erneut hoch.');
        }

        return self::split($sql);
    }

    /**
     * @return list<string>
     */
    public static function split(string $sql): array
    {
        if (str_starts_with($sql, "\xEF\xBB\xBF")) {
            $sql = substr($sql, 3);
        }

        $statements = [];
        $current = '';
        $length = strlen($sql);
        $pos = 0;

        while ($pos < $length) {
            $char = $sql[$pos];

            $commentEnd = self::commentEnd($sql, $pos);

            if ($commentEnd !== null) {
                $current .= ' ';
                $pos = $commentEnd;

                continue;
            }

            if ($char === "'" || $char === '"' || $char === '`') {
                $end = self::quotedEnd($sql, $pos);
                $current .= substr($sql, $pos, $end - $pos);
                $pos = $end;

                continue;
            }

            if ($char === ';') {
                self::addStatement($statements, $current);
                $current = '';
                ++$pos;

                continue;
            }

            $current .= $char;
            ++$pos;
        }

        self::addStatement($statements, $current);

        return $statements;
    }

    /**
     * Prüft, dass nur erlaubte Anweisungen enthalten sind und jede
     * pn_-Tabelle genau einmal angelegt wird.
     *
     * @param list<string> $statements
     *
     * @throws \UnexpectedValueException bei einer unerwarteten Anweisung
     */
    public static function assertInstallable(array $statements): void
    {
        if (self::tableNames($statements) === []) {
            throw new \UnexpectedValueException('Die Schemadatei ' . self::FILENAME . ' enthält keine Tabellen. Bitte laden Sie sie erneut hoch.');
        }

        foreach ($statements as $statement) {
            if (!self::isAllowed($statement)) {
                throw new \UnexpectedValueException(
                    'Die Schemadatei ' . self::FILENAME . ' enthält eine unerwartete Anweisung („'
                    . mb_substr(preg_replace('/\s+/', ' ', $statement) ?? '', 0, 40) . ' …“). '
                    . 'Bitte verwenden Sie die unveränderte Datei aus dem Release-Archiv.',
                );
            }
        }

        $tables = self::tableNames($statements);

        if (count($tables) !== count(array_unique($tables))) {
            throw new \UnexpectedValueException('Die Schemadatei ' . self::FILENAME . ' legt eine Tabelle mehrfach an.');
        }
    }

    public static function isAllowed(string $statement): bool
    {
        foreach (self::ALLOWED as $pattern) {
            if (preg_match($pattern, $statement) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Name der Tabelle, die eine CREATE-TABLE-Anweisung anlegt, sonst null.
     */
    public static function createdTable(string $statement): ?string
    {
        return self::tableOf('/^\s*CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`?([A-Za-z0-9_]+)`?/i', $statement);
    }

    /**
     * Name der Tabelle, in die eine INSERT-Anweisung schreibt, sonst null.
     */
    public static function insertedTable(string $statement): ?string
    {
        return self::tableOf('/^\s*INSERT\s+INTO\s+`?([A-Za-z0-9_]+)`?/i', $statement);
    }

    /**
     * @param list<string> $statements
     *
     * @return list<string>
     */
    public static function tableNames(array $statements): array
    {
        $tables = [];

        foreach ($statements as $statement) {
            $table = self::createdTable($statement);

            if ($table !== null) {
                $tables[] = $table;
            }
        }

        return $tables;
    }

    private static function tableOf(string $pattern, string $statement): ?string
    {
        return preg_match($pattern, $statement, $match) === 1 ? $match[1] : null;
    }

    /**
     * @param list<string> $statements
     */
    private static function addStatement(array &$statements, string $statement): void
    {
        $statement = trim($statement);

        if ($statement !== '') {
            $statements[] = $statement;
        }
    }

    /**
     * Beginnt an $pos ein Kommentar, liefert die Position direkt dahinter
     * (bei Zeilenkommentaren: den Zeilenumbruch), sonst null.
     */
    private static function commentEnd(string $sql, int $pos): ?int
    {
        $char = $sql[$pos];
        $next = $sql[$pos + 1] ?? '';

        $isLineComment = $char === '#'
            || ($char === '-' && $next === '-' && in_array($sql[$pos + 2] ?? "\n", [' ', "\t", "\r", "\n"], true));

        if ($isLineComment) {
            $end = strpos($sql, "\n", $pos);

            return $end === false ? strlen($sql) : $end;
        }

        if ($char === '/' && $next === '*') {
            $end = strpos($sql, '*/', $pos + 2);

            return $end === false ? strlen($sql) : $end + 2;
        }

        return null;
    }

    /**
     * Position direkt hinter dem schließenden Anführungszeichen. Beachtet
     * Backslash-Escapes (nicht in Bezeichnern) und verdoppelte
     * Anführungszeichen.
     */
    private static function quotedEnd(string $sql, int $start): int
    {
        $quote = $sql[$start];
        $length = strlen($sql);
        $pos = $start + 1;

        while ($pos < $length) {
            $char = $sql[$pos];

            if ($char === '\\' && $quote !== '`') {
                $pos += 2;

                continue;
            }

            if ($char === $quote) {
                if (($sql[$pos + 1] ?? '') !== $quote) {
                    return $pos + 1;
                }
                $pos += 2;

                continue;
            }

            ++$pos;
        }

        return $length;
    }
}
