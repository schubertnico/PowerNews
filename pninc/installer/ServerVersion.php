<?php

declare(strict_types=1);

/* PowerNews - PHP and MySQL based news script                          */
/* Copyright (c) 2001-2026 PowerScripts                                 */

/* MIT License - See LICENSE file for full license text                 */
/* https://github.com/schubertnico/PowerNews.git                        */

namespace PowerNews\Installer;

/**
 * Erkennt Typ und Version des Datenbankservers.
 *
 * MariaDB und MySQL zählen getrennt (Befund B27): MariaDB 10.11 ist neuer als
 * MySQL 8.0, obwohl die Nummer größer ist – ein gemeinsamer Vergleich mit
 * „10.3“ hat MySQL 8 fälschlich abgewiesen.
 */
final readonly class ServerVersion
{
    public const string MARIADB = 'MariaDB';

    public const string MYSQL = 'MySQL';

    public const string MIN_MARIADB = '10.3';

    public const string MIN_MYSQL = '8.0';

    private function __construct(
        public string $type,
        public string $version,
    ) {
    }

    /**
     * Wertet die Versionskennung aus, z. B. aus `SELECT VERSION()` oder
     * mysqli_get_server_info():
     *   „10.11.15-MariaDB-ubu2204“, „5.5.5-10.3.39-MariaDB-log“, „8.0.39“,
     *   „8.4.2“, „8.0.36-28“ (Percona Server).
     * Liefert null, wenn keine Versionsnummer erkennbar ist.
     */
    public static function parse(string $versionString): ?self
    {
        $isMariaDb = stripos($versionString, 'mariadb') !== false;

        // Ältere MariaDB-Server stellen für alte Clients „5.5.5-“ voran.
        $cleaned = $isMariaDb ? (string) preg_replace('/^5\.5\.5-/', '', trim($versionString)) : trim($versionString);

        if (preg_match('/^(\d+)\.(\d+)(?:\.(\d+))?/', $cleaned, $match) !== 1) {
            return null;
        }

        $version = $match[1] . '.' . $match[2] . '.' . ($match[3] ?? '0');

        return new self($isMariaDb ? self::MARIADB : self::MYSQL, $version);
    }

    public function minimum(): string
    {
        return $this->type === self::MARIADB ? self::MIN_MARIADB : self::MIN_MYSQL;
    }

    public function isSupported(): bool
    {
        return version_compare($this->version, $this->minimum(), '>=');
    }

    /**
     * z. B. „MariaDB 10.11.15“.
     */
    public function label(): string
    {
        return $this->type . ' ' . $this->version;
    }

    /**
     * Anforderung als Text für Systemprüfung und Fehlermeldungen.
     */
    public static function requirement(): string
    {
        return 'MySQL ' . self::MIN_MYSQL . ' oder neuer bzw. MariaDB ' . self::MIN_MARIADB . ' oder neuer';
    }
}
