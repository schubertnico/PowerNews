<?php

declare(strict_types=1);

/* PowerNews - PHP and MySQL based news script                         */
/* Copyright (c) 2001-2026 PowerScripts                                 */

/* MIT License - See LICENSE file for full license text                 */
/* https://github.com/schubertnico/PowerNews.git                        */

/**
 * Zentrale Validierungsfunktionen für User-Input.
 *
 * Alle Funktionen geben sichere, validierte Werte zurück.
 * Bei ungültigem Input wird ein sicherer Standardwert zurückgegeben.
 */

/**
 * Validiert und castet eine ID aus User-Input.
 *
 * @param mixed $input Der zu validierende Wert
 *
 * @return int Positive Integer-ID oder 0 bei ungültigem Input
 */
function pn_validate_id(mixed $input): int
{
    if ($input === null || $input === '') {
        return 0;
    }
    $id = filter_var($input, FILTER_VALIDATE_INT);

    return ($id !== false && $id > 0) ? $id : 0;
}

/**
 * Validiert einen String aus User-Input.
 *
 * @param mixed $input Der zu validierende Wert
 * @param int $maxLength Maximale Länge (default: 255)
 *
 * @return string Getrimmter String mit maximaler Länge
 */
function pn_validate_string(mixed $input, int $maxLength = 255): string
{
    if ($input === null) {
        return '';
    }
    $str = trim((string) $input);

    return mb_substr($str, 0, $maxLength, 'UTF-8');
}

/**
 * Validiert einen Wert gegen eine Whitelist.
 *
 * @param mixed $input Der zu validierende Wert
 * @param array $allowed Liste der erlaubten Werte
 * @param mixed $default Standardwert bei ungültigem Input
 *
 * @return mixed Der validierte Wert oder der Standardwert
 */
function pn_validate_whitelist(mixed $input, array $allowed, mixed $default): mixed
{
    return in_array($input, $allowed, true) ? $input : $default;
}

/**
 * Validiert eine Email-Adresse.
 *
 * @param mixed $input Der zu validierende Wert
 *
 * @return string Validierte Email oder leerer String
 */
function pn_validate_email(mixed $input): string
{
    if ($input === null || $input === '') {
        return '';
    }
    $email = filter_var(trim((string) $input), FILTER_VALIDATE_EMAIL);

    return $email !== false ? $email : '';
}

/**
 * Validiert eine URL (nur http/https).
 *
 * @param mixed $input Der zu validierende Wert
 *
 * @return string Validierte URL oder leerer String
 */
function pn_validate_url(mixed $input): string
{
    if ($input === null || $input === '') {
        return '';
    }
    $url = trim((string) $input);

    if (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
        return '';
    }
    $validated = filter_var($url, FILTER_VALIDATE_URL);

    return $validated !== false ? $validated : '';
}

/**
 * Validiert einen Boolean-Wert (YES/NO).
 *
 * @param mixed $input Der zu validierende Wert
 * @param string $default Standardwert ('YES' oder 'NO')
 *
 * @return string 'YES' oder 'NO'
 */
function pn_validate_yesno(mixed $input, string $default = 'NO'): string
{
    if ($input === 'YES') {
        return 'YES';
    }

    if ($input === 'NO') {
        return 'NO';
    }

    return $default === 'YES' ? 'YES' : 'NO';
}

/**
 * Validiert einen Status-Wert.
 *
 * @param mixed $input Der zu validierende Wert
 * @param string $default Standardwert
 *
 * @return string Validierter Status
 */
function pn_validate_status(mixed $input, string $default = 'Unchecked'): string
{
    $allowed = ['Activated', 'Deactivated', 'Unchecked'];

    return pn_validate_whitelist($input, $allowed, $default);
}

/**
 * Validiert einen Integer-Wert in einem Bereich.
 *
 * @param mixed $input Der zu validierende Wert
 * @param int $min Minimaler Wert
 * @param int $max Maximaler Wert
 * @param int $default Standardwert
 *
 * @return int Validierter Integer
 */
function pn_validate_int_range(mixed $input, int $min, int $max, int $default): int
{
    $value = filter_var($input, FILTER_VALIDATE_INT);

    if ($value === false) {
        return $default;
    }

    if ($value < $min) {
        return $min;
    }

    if ($value > $max) {
        return $max;
    }

    return $value;
}

/**
 * Validiert Datum-Komponenten.
 *
 * @param mixed $day Tag (1-31)
 * @param mixed $month Monat (1-12)
 * @param mixed $year Jahr (1970-2100)
 *
 * @return array{day: int, month: int, year: int} Validierte Datums-Komponenten
 */
function pn_validate_date(mixed $day, mixed $month, mixed $year): array
{
    return [
        'day' => pn_validate_int_range($day, 1, 31, (int) date('d')),
        'month' => pn_validate_int_range($month, 1, 12, (int) date('m')),
        'year' => pn_validate_int_range($year, 1970, 2100, (int) date('Y')),
    ];
}

/**
 * Validiert Zeit-Komponenten.
 *
 * @param mixed $hour Stunde (0-23)
 * @param mixed $minute Minute (0-59)
 *
 * @return array{hour: int, minute: int} Validierte Zeit-Komponenten
 */
function pn_validate_time(mixed $hour, mixed $minute): array
{
    return [
        'hour' => pn_validate_int_range($hour, 0, 23, (int) date('H')),
        'minute' => pn_validate_int_range($minute, 0, 59, (int) date('i')),
    ];
}

/**
 * Holt und validiert einen GET-Parameter als ID.
 *
 * @param string $key Parameter-Name
 *
 * @return int Validierte ID oder 0
 */
function pn_get_id(string $key): int
{
    return pn_validate_id($_GET[$key] ?? null);
}

/**
 * Holt und validiert einen POST-Parameter als ID.
 *
 * @param string $key Parameter-Name
 *
 * @return int Validierte ID oder 0
 */
function pn_post_id(string $key): int
{
    return pn_validate_id($_POST[$key] ?? null);
}

/**
 * Holt und validiert einen GET-Parameter als String.
 *
 * @param string $key Parameter-Name
 * @param int $maxLength Maximale Länge
 *
 * @return string Validierter String
 */
function pn_get_string(string $key, int $maxLength = 255): string
{
    return pn_validate_string($_GET[$key] ?? null, $maxLength);
}

/**
 * Holt und validiert einen POST-Parameter als String.
 *
 * @param string $key Parameter-Name
 * @param int $maxLength Maximale Länge
 *
 * @return string Validierter String
 */
function pn_post_string(string $key, int $maxLength = 255): string
{
    return pn_validate_string($_POST[$key] ?? null, $maxLength);
}

/**
 * Holt und validiert einen POST-Parameter als Email.
 *
 * @param string $key Parameter-Name
 *
 * @return string Validierte Email oder leerer String
 */
function pn_post_email(string $key): string
{
    return pn_validate_email($_POST[$key] ?? null);
}

/**
 * Holt und validiert einen GET/POST-Parameter gegen Whitelist.
 *
 * @param string $key Parameter-Name
 * @param array $allowed Erlaubte Werte
 * @param mixed $default Standardwert
 * @param string $method 'GET' oder 'POST'
 *
 * @return mixed Validierter Wert
 */
function pn_input_whitelist(string $key, array $allowed, mixed $default, string $method = 'GET'): mixed
{
    $source = ($method === 'POST') ? $_POST : $_GET;

    return pn_validate_whitelist($source[$key] ?? null, $allowed, $default);
}

/**
 * Validiert einen Nickname: 3-30 Zeichen, erlaubte Zeichen: A-Z, a-z, 0-9, ._-,
 * deutsche Umlaute und ß.
 */
function pn_validate_nickname(mixed $input): string
{
    if ($input === null) {
        return '';
    }
    $trimmed = trim((string) $input);

    if (!preg_match('/^[A-Za-z\x{00C4}\x{00D6}\x{00DC}\x{00E4}\x{00F6}\x{00FC}\x{00DF}0-9_.\-]{3,30}$/u', $trimmed)) {
        return '';
    }

    return $trimmed;
}

/**
 * Session-gebundener CSRF-Token (Getter + Verify).
 */
function pn_csrf_token(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (empty($_SESSION['pn_csrf_token'])) {
        $_SESSION['pn_csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['pn_csrf_token'];
}

function pn_csrf_verify(mixed $token): bool
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $stored = $_SESSION['pn_csrf_token'] ?? '';

    if ($stored === '' || !is_string($token)) {
        return false;
    }

    return hash_equals($stored, $token);
}
