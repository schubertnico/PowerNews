<?php

declare(strict_types=1);

/* PowerNews - PHP and MySQL based news script                          */
/* Copyright (c) 2001-2026 PowerScripts                                 */

/* MIT License - See LICENSE file for full license text                 */
/* https://github.com/schubertnico/PowerNews.git                        */

namespace PowerNews\Installer;

use PowerNews\LocalConfig;

/**
 * Liest die Teile des Installer-Zustands aus der Session (Wizard::fromSession()).
 *
 * Jede Methode liefert entweder vollständige, typrichtige Daten oder null.
 * Unvollständige oder manipulierte Sitzungen führen so zu einem früheren
 * Schritt, nie zu einem Fehler.
 *
 * @phpstan-import-type DbConfig from LocalConfig
 * @phpstan-import-type MailConfig from LocalConfig
 * @phpstan-import-type WebsiteSettings from FormValidator
 * @phpstan-import-type AdminData from DatabaseSetup
 * @phpstan-import-type DoneInfo from Wizard
 * @phpstan-import-type Notice from Wizard
 */
final class SessionData
{
    /**
     * Erlaubte Typen einer Meldung (Farbe des Bootstrap-Alerts).
     */
    public const array NOTICE_TYPES = ['success', 'danger', 'warning', 'info'];

    /**
     * @return DbConfig|null
     */
    public static function database(#[\SensitiveParameter] mixed $data): ?array
    {
        if (!is_array($data) || !is_int($data['port'] ?? null)) {
            return null;
        }

        $strings = self::strings($data, ['host', 'user', 'password', 'database']);

        if ($strings === null) {
            return null;
        }

        return [
            'host' => $strings['host'],
            'port' => $data['port'],
            'user' => $strings['user'],
            'password' => $strings['password'],
            'database' => $strings['database'],
        ];
    }

    /**
     * @return WebsiteSettings|null
     */
    public static function website(#[\SensitiveParameter] mixed $data): ?array
    {
        $strings = is_array($data) ? self::strings($data, ['url', 'email', 'language']) : null;
        // Sitzungen ohne Mailversand (vor 3.12) nutzen die Vorgabe mail().
        $mail = is_array($data) && array_key_exists('mail', $data) ? self::mail($data['mail']) : LocalConfig::DEFAULT_MAIL;

        if ($strings === null || $mail === null) {
            return null;
        }

        return ['url' => $strings['url'], 'email' => $strings['email'], 'language' => $strings['language'], 'mail' => $mail];
    }

    /**
     * @return MailConfig|null
     */
    public static function mail(#[\SensitiveParameter] mixed $data): ?array
    {
        if (!is_array($data) || !is_int($data['port'] ?? null)) {
            return null;
        }

        $strings = self::strings($data, ['transport', 'host', 'encryption', 'user', 'password']);

        if ($strings === null) {
            return null;
        }

        return [
            'transport' => $strings['transport'],
            'host' => $strings['host'],
            'port' => $data['port'],
            'encryption' => $strings['encryption'],
            'user' => $strings['user'],
            'password' => $strings['password'],
        ];
    }

    /**
     * @return AdminData|null
     */
    public static function admin(#[\SensitiveParameter] mixed $data): ?array
    {
        $strings = is_array($data) ? self::strings($data, ['nickname', 'email', 'password_hash']) : null;

        if ($strings === null) {
            return null;
        }

        return ['nickname' => $strings['nickname'], 'email' => $strings['email'], 'password_hash' => $strings['password_hash']];
    }

    /**
     * @return DoneInfo|null
     */
    public static function done(#[\SensitiveParameter] mixed $data): ?array
    {
        if (!is_array($data) || !is_bool($data['config_written'] ?? null)) {
            return null;
        }

        $strings = self::strings($data, ['config_source', 'lock_file', 'admin_nickname', 'site_url']);

        if ($strings === null) {
            return null;
        }

        return [
            'config_written' => $data['config_written'],
            'config_source' => $strings['config_source'],
            'lock_file' => $strings['lock_file'],
            'admin_nickname' => $strings['admin_nickname'],
            'site_url' => $strings['site_url'],
        ];
    }

    /**
     * @return Notice|null
     */
    public static function notice(mixed $data): ?array
    {
        $strings = is_array($data) ? self::strings($data, ['type', 'message']) : null;

        if ($strings === null || !in_array($strings['type'], self::NOTICE_TYPES, true)) {
            return null;
        }

        return ['type' => $strings['type'], 'message' => $strings['message']];
    }

    /**
     * Nicht negative Ganzzahl, sonst 0.
     */
    public static function count(mixed $data): int
    {
        return is_int($data) ? max(0, $data) : 0;
    }

    /**
     * Liefert die angegebenen Schlüssel, wenn alle Zeichenketten sind.
     *
     * @param array<array-key, mixed> $data
     * @param list<string> $keys
     *
     * @return array<string, string>|null
     */
    private static function strings(#[\SensitiveParameter] array $data, array $keys): ?array
    {
        $result = [];

        foreach ($keys as $key) {
            $value = $data[$key] ?? null;

            if (!is_string($value)) {
                return null;
            }
            $result[$key] = $value;
        }

        return $result;
    }
}
