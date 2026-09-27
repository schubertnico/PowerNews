<?php

declare(strict_types=1);

/* PowerNews - PHP and MySQL based news script                          */
/* Copyright (c) 2001-2026 PowerScripts                                 */

/* MIT License - See LICENSE file for full license text                 */
/* https://github.com/schubertnico/PowerNews.git                        */

/*
 * Gemeinsame Hilfsfunktionen für Frontend (pninc/) und Administration (pnadmin/).
 * Wird von beiden functions.inc.php per require_once geladen.
 */

/**
 * Ersetzt Platzhalter wie {TITLE} in einem Template.
 *
 * Alle Platzhalter werden in einem Durchgang ersetzt (strtr). Nutzerinhalte werden damit
 * weder als Regex-Rückverweis ausgewertet („$10“, „\1“ bleiben erhalten) noch ein zweites
 * Mal nach Platzhaltern durchsucht (ein Titel „{TEXT}“ bleibt wörtlich stehen).
 *
 * @param array<string, string|int> $values Platzhaltername ohne Klammern => Wert
 */
function pn_template_fill(string $template, array $values): string
{
    $pairs = [];

    foreach ($values as $name => $value) {
        $pairs['{' . $name . '}'] = (string) $value;
    }

    return strtr($template, $pairs);
}

/**
 * Gültiger bcrypt-Hash eines zufälligen, verworfenen Passworts. Wird geprüft, wenn ein
 * Nickname nicht existiert, damit die Antwortzeit keinen Hinweis auf vorhandene Konten gibt.
 */
const PN_DUMMY_PASSWORD_HASH = '$2y$12$qIx3UfESNOAkRaunNV0VheRdEfFyKBUwKjrOVHkvMC7fVQuJAWcRG';

/** Fehlversuche pro IP-Adresse, ab denen Logins für PN_LOGIN_WINDOW Sekunden gesperrt sind. */
const PN_LOGIN_MAX_FAILURES = 10;

/** Beobachtungsfenster der Fehlversuchsbremse in Sekunden. */
const PN_LOGIN_WINDOW = 900;

/**
 * Liste vertrauenswürdiger Proxys (einzelne IPs oder CIDR-Bereiche) aus
 * $pn_config['trustedproxies'] (Array oder kommagetrennt) und der Umgebungsvariablen
 * PN_TRUSTED_PROXIES. Ab Werk leer.
 *
 * @return list<string>
 */
function pn_trusted_proxies(): array
{
    global $pn_config;

    $entries = $pn_config['trustedproxies'] ?? [];
    $entries = is_array($entries) ? $entries : explode(',', (string) $entries);
    $env = getenv('PN_TRUSTED_PROXIES');

    if (is_string($env) && $env !== '') {
        $entries = array_merge($entries, explode(',', $env));
    }

    $list = [];

    foreach ($entries as $entry) {
        $entry = trim((string) $entry);

        if ($entry !== '') {
            $list[] = $entry;
        }
    }

    return $list;
}

/**
 * Prüft, ob eine IP-Adresse in einer Liste aus IPs und CIDR-Bereichen (IPv4/IPv6) liegt.
 *
 * @param list<string> $list
 */
function pn_ip_in_list(string $ip, array $list): bool
{
    if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
        return false;
    }

    $packed = (string) inet_pton($ip);

    foreach ($list as $entry) {
        $parts = explode('/', $entry, 2);

        if (filter_var($parts[0], FILTER_VALIDATE_IP) === false) {
            continue;
        }

        $network = (string) inet_pton($parts[0]);

        if (strlen($network) !== strlen($packed)) {
            continue;
        }

        $maxBits = strlen($packed) * 8;
        $bits = isset($parts[1]) ? filter_var($parts[1], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => $maxBits]]) : $maxBits;

        if ($bits === false) {
            continue;
        }

        $bytes = intdiv($bits, 8);
        $rest = $bits % 8;

        if (substr($packed, 0, $bytes) !== substr($network, 0, $bytes)) {
            continue;
        }

        if ($rest === 0) {
            return true;
        }

        $mask = (0xFF << (8 - $rest)) & 0xFF;

        if ((ord($packed[$bytes]) & $mask) === (ord($network[$bytes]) & $mask)) {
            return true;
        }
    }

    return false;
}

/**
 * IP-Adresse des Besuchers für Spamschutz, Fehlversuchsbremse und Protokoll.
 *
 * Maßgeblich ist REMOTE_ADDR. X-Forwarded-For wird nur ausgewertet, wenn die Anfrage von
 * einem konfigurierten vertrauenswürdigen Proxy kommt; dann gilt die erste Adresse von
 * rechts, die nicht selbst ein vertrauenswürdiger Proxy ist.
 */
function pn_client_ip(): string
{
    $remote = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
    $trusted = pn_trusted_proxies();

    if ($trusted === [] || !pn_ip_in_list($remote, $trusted)) {
        return substr($remote, 0, 64);
    }

    $chain = array_reverse(explode(',', (string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? '')));

    foreach ($chain as $hop) {
        $hop = trim($hop);

        if (filter_var($hop, FILTER_VALIDATE_IP) === false) {
            break;
        }

        if (!pn_ip_in_list($hop, $trusted)) {
            return substr($hop, 0, 64);
        }

        $remote = $hop;
    }

    return substr($remote, 0, 64);
}

/**
 * Fehlversuchsbremse für Frontend- und Admin-Login: gesperrt wird nur die IP-Adresse, von
 * der die Fehlversuche kommen. Fehlversuche gegen einen Nickname sperren damit nicht den
 * rechtmäßigen Inhaber von einer anderen Adresse aus.
 */
function pn_login_throttled(mysqli $db, string $ip): bool
{
    $since = time() - PN_LOGIN_WINDOW;
    $stmt = mysqli_prepare($db, "SELECT COUNT(*) FROM pn_login_attempts WHERE ip = ? AND success = 'NO' AND attempted_at > ?");
    mysqli_stmt_bind_param($stmt, 'si', $ip, $since);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_row(mysqli_stmt_get_result($stmt));

    return (int) ($row[0] ?? 0) >= PN_LOGIN_MAX_FAILURES;
}

/**
 * Protokolliert einen Login-Versuch. Nach einem erfolgreichen Login werden Einträge, die
 * älter als ein Tag sind, entfernt.
 */
function pn_login_record(mysqli $db, string $ip, string $nickname, bool $success): void
{
    $now = time();
    $flag = $success ? 'YES' : 'NO';
    $nickname = mb_substr($nickname, 0, 100);
    $stmt = mysqli_prepare($db, 'INSERT INTO pn_login_attempts (ip, nickname, success, attempted_at) VALUES (?, ?, ?, ?)');
    mysqli_stmt_bind_param($stmt, 'sssi', $ip, $nickname, $flag, $now);
    mysqli_stmt_execute($stmt);

    if ($success) {
        $cutoff = $now - 86400;
        $cleanup = mysqli_prepare($db, 'DELETE FROM pn_login_attempts WHERE attempted_at < ?');
        mysqli_stmt_bind_param($cleanup, 'i', $cutoff);
        mysqli_stmt_execute($cleanup);
    }
}
