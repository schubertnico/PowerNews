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

/** Cookie der Frontend-Anmeldung (Format „userId:token“). */
const PN_COOKIE_FRONTEND = 'pncookie';

/** Cookie der Admin-Anmeldung (Format „userId:token“), getrennt vom Frontend (B04/B33). */
const PN_COOKIE_ADMIN = 'pnadmincookie';

/** Laufzeit einer Frontend-Sitzung in Sekunden (30 Tage). */
const PN_SESSION_FRONTEND_LIFETIME = 2592000;

/** Admin-Sitzungen enden nach 8 Stunden ohne Aktivität (gleitende Verlängerung) ... */
const PN_SESSION_ADMIN_IDLE = 28800;

/** ... und spätestens 24 Stunden nach der Anmeldung. */
const PN_SESSION_ADMIN_MAX = 86400;

/**
 * Hash eines Sitzungstokens für pn_sessions. Admin-Token werden mit Präfix gehasht, damit
 * ein Frontend-Token nie als Admin-Sitzung gilt und umgekehrt.
 */
function pn_session_hash(string $token, string $scope): string
{
    return hash('sha256', $scope === 'admin' ? 'admin:' . $token : $token);
}

/**
 * Liest ein Sitzungscookie im Format „userId:token“.
 *
 * @return array{0: int, 1: string}|null
 */
function pn_session_parse_cookie(string $name): ?array
{
    $value = $_COOKIE[$name] ?? null;

    if (!is_string($value) || $value === '') {
        return null;
    }

    $parts = explode(':', $value, 2);

    if (count($parts) !== 2 || preg_match('/^[a-f0-9]{64}$/', $parts[1]) !== 1) {
        return null;
    }

    $userId = filter_var($parts[0], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

    return $userId === false ? null : [$userId, $parts[1]];
}

/**
 * Entfernt abgelaufene Sitzungen (B26).
 */
function pn_sessions_purge_expired(mysqli $db): void
{
    $now = time();
    $stmt = mysqli_prepare($db, 'DELETE FROM pn_sessions WHERE expires <= ?');
    mysqli_stmt_bind_param($stmt, 'i', $now);
    mysqli_stmt_execute($stmt);
}

/**
 * Beendet alle Sitzungen eines Benutzers, z. B. nach einer Passwortänderung (B26).
 */
function pn_sessions_delete_for_user(mysqli $db, int $userId): void
{
    $stmt = mysqli_prepare($db, 'DELETE FROM pn_sessions WHERE userid = ?');
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
}

/**
 * Legt eine neue Sitzung an und liefert das Token für das Cookie.
 */
function pn_session_create(mysqli $db, int $userId, string $scope): string
{
    pn_sessions_purge_expired($db);

    $token = bin2hex(random_bytes(32));
    $tokenHash = pn_session_hash($token, $scope);
    $now = time();
    $expires = $now + ($scope === 'admin' ? PN_SESSION_ADMIN_IDLE : PN_SESSION_FRONTEND_LIFETIME);
    $userAgent = mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
    $ip = pn_client_ip();

    $stmt = mysqli_prepare($db, 'INSERT INTO pn_sessions (userid, token_hash, created, expires, user_agent, ip) VALUES (?, ?, ?, ?, ?, ?)');
    mysqli_stmt_bind_param($stmt, 'isiiss', $userId, $tokenHash, $now, $expires, $userAgent, $ip);
    mysqli_stmt_execute($stmt);

    return $token;
}

/**
 * Prüft eine Sitzung. Admin-Sitzungen werden bei Aktivität verlängert (höchstens bis
 * PN_SESSION_ADMIN_MAX nach der Anmeldung).
 */
function pn_session_validate(mysqli $db, int $userId, string $token, string $scope): bool
{
    $tokenHash = pn_session_hash($token, $scope);
    $now = time();
    $stmt = mysqli_prepare($db, 'SELECT id, created, expires FROM pn_sessions WHERE userid = ? AND token_hash = ? AND expires > ?');
    mysqli_stmt_bind_param($stmt, 'isi', $userId, $tokenHash, $now);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if (!is_array($row)) {
        return false;
    }

    if ($scope === 'admin') {
        $sessionId = (int) $row['id'];
        $renewed = min($now + PN_SESSION_ADMIN_IDLE, (int) $row['created'] + PN_SESSION_ADMIN_MAX);

        if ($renewed <= $now) {
            pn_session_delete($db, $token, $scope);

            return false;
        }

        // Nur schreiben, wenn sich die Laufzeit spürbar verlängert (höchstens alle 5 Minuten).
        if ($renewed - (int) $row['expires'] > 300) {
            $update = mysqli_prepare($db, 'UPDATE pn_sessions SET expires = ? WHERE id = ?');
            mysqli_stmt_bind_param($update, 'ii', $renewed, $sessionId);
            mysqli_stmt_execute($update);
        }
    }

    return true;
}

/**
 * Löscht die Sitzung zu einem Token.
 */
function pn_session_delete(mysqli $db, string $token, string $scope): void
{
    $tokenHash = pn_session_hash($token, $scope);
    $stmt = mysqli_prepare($db, 'DELETE FROM pn_sessions WHERE token_hash = ?');
    mysqli_stmt_bind_param($stmt, 's', $tokenHash);
    mysqli_stmt_execute($stmt);
}

/**
 * Setzt oder löscht ein Sitzungscookie (HttpOnly, SameSite=Strict, Secure unter HTTPS).
 * $expires = 0 erzeugt ein Cookie, das mit dem Browser endet.
 */
function pn_session_cookie(string $name, string $value, int $expires): void
{
    if (headers_sent()) {
        return;
    }

    setcookie($name, $value, [
        'expires' => $expires,
        'path' => '/',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
}

/** Gültigkeit eines Links zum Festlegen eines neuen Passworts in Sekunden (60 Minuten). */
const PN_RESET_LIFETIME = 3600;

/** Höchstzahl an „Passwort vergessen“-Anfragen je IP-Adresse und Stunde. */
const PN_RESET_MAX_PER_IP = 5;

/** Mindestabstand zwischen zwei Reset-Mails an dasselbe Konto in Sekunden. */
const PN_RESET_MIN_INTERVAL = 300;

/**
 * Legt die Tabelle für Einmal-Token an (falls das Update noch nicht gelaufen ist) und
 * entfernt abgelaufene Einträge.
 */
function pn_password_resets_prepare(mysqli $db): void
{
    mysqli_query(
        $db,
        'CREATE TABLE IF NOT EXISTS pn_password_resets ('
        . '`id` int(11) NOT NULL AUTO_INCREMENT, `userid` int(11) NOT NULL, `token_hash` char(64) NOT NULL, '
        . '`created` int(14) NOT NULL, `expires` int(14) NOT NULL, `ip` varchar(64) NOT NULL DEFAULT \'\', '
        . 'PRIMARY KEY (`id`), KEY `idx_token` (`token_hash`), KEY `idx_userid` (`userid`), KEY `idx_ip_created` (`ip`, `created`)'
        . ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci'
    );

    $now = time();
    $stmt = mysqli_prepare($db, 'DELETE FROM pn_password_resets WHERE expires <= ?');
    mysqli_stmt_bind_param($stmt, 'i', $now);
    mysqli_stmt_execute($stmt);
}

/**
 * Anzahl der Reset-Anfragen einer IP-Adresse in der letzten Stunde. Anfragen für unbekannte
 * Konten zählen mit (Zeile mit userid 0), damit sich Adressen nicht durchprobieren lassen.
 */
function pn_password_reset_requests(mysqli $db, string $ip): int
{
    $since = time() - 3600;
    $stmt = mysqli_prepare($db, 'SELECT COUNT(*) FROM pn_password_resets WHERE ip = ? AND created > ?');
    mysqli_stmt_bind_param($stmt, 'si', $ip, $since);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_row(mysqli_stmt_get_result($stmt));

    return (int) ($row[0] ?? 0);
}

/**
 * Wurde für das Konto gerade erst ein Link verschickt? Verhindert Mailfluten an Dritte.
 */
function pn_password_reset_recent(mysqli $db, int $userId): bool
{
    $since = time() - PN_RESET_MIN_INTERVAL;
    $stmt = mysqli_prepare($db, 'SELECT id FROM pn_password_resets WHERE userid = ? AND created > ?');
    mysqli_stmt_bind_param($stmt, 'ii', $userId, $since);
    mysqli_stmt_execute($stmt);

    return mysqli_num_rows(mysqli_stmt_get_result($stmt)) > 0;
}

/**
 * Speichert eine Reset-Anfrage. Gespeichert wird nur der Hash des Tokens.
 */
function pn_password_reset_store(mysqli $db, int $userId, string $token, string $ip): void
{
    $tokenHash = hash('sha256', $token);
    $now = time();
    $expires = $now + PN_RESET_LIFETIME;
    $stmt = mysqli_prepare($db, 'INSERT INTO pn_password_resets (userid, token_hash, created, expires, ip) VALUES (?, ?, ?, ?, ?)');
    mysqli_stmt_bind_param($stmt, 'isiis', $userId, $tokenHash, $now, $expires, $ip);
    mysqli_stmt_execute($stmt);
}

/**
 * Liefert das freigeschaltete Konto zu einem gültigen, nicht abgelaufenen Reset-Token.
 *
 * @param array<string, mixed> $pn_config
 *
 * @return array<string, mixed>|null
 */
function pn_password_reset_user(mysqli $db, array $pn_config, string $token): ?array
{
    if (preg_match('/^[a-f0-9]{64}$/', $token) !== 1) {
        return null;
    }

    $tokenHash = hash('sha256', $token);
    $now = time();
    $stmt = mysqli_prepare(
        $db,
        'SELECT u.* FROM ' . $pn_config['usertable'] . ' u INNER JOIN pn_password_resets r ON r.userid = u.id '
        . "WHERE r.token_hash = ? AND r.expires > ? AND u.status = 'Activated'"
    );
    mysqli_stmt_bind_param($stmt, 'si', $tokenHash, $now);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    return is_array($row) ? $row : null;
}

/**
 * Setzt das neue Passwort, verwirft alle Reset-Token des Kontos und beendet alle Sitzungen.
 *
 * @param array<string, mixed> $pn_config
 */
function pn_password_reset_complete(mysqli $db, array $pn_config, int $userId, string $password): void
{
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = mysqli_prepare($db, 'UPDATE ' . $pn_config['usertable'] . ' SET password = ? WHERE id = ?');
    mysqli_stmt_bind_param($stmt, 'si', $hash, $userId);
    mysqli_stmt_execute($stmt);

    $cleanup = mysqli_prepare($db, 'DELETE FROM pn_password_resets WHERE userid = ?');
    mysqli_stmt_bind_param($cleanup, 'i', $userId);
    mysqli_stmt_execute($cleanup);

    pn_sessions_delete_for_user($db, $userId);
}

/**
 * Startet die PHP-Session (für CSRF-Token) mit sicheren Cookie-Parametern, sofern noch
 * keine läuft und noch nichts ausgegeben wurde.
 */
function pn_php_session_start(): void
{
    if (session_status() === PHP_SESSION_ACTIVE || headers_sent()) {
        return;
    }

    session_set_cookie_params([
        'path' => '/',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

/**
 * Prüft die Adresse eines weiterführenden Links (B37). Erlaubt sind http(s)-Adressen und,
 * wenn $allowRelative gesetzt ist, relative Pfade wie „/impressum“ oder „news.php?newsid=2“.
 * Andere Schemata (javascript:, data:, …), Leer-/Steuerzeichen und Backslashes nie.
 */
function pn_relatedlink_url_allowed(string $url, bool $allowRelative): bool
{
    if ($url === '' || preg_match('/[\s\x00-\x1F\x7F\\\\]/', $url) === 1) {
        return false;
    }

    if (preg_match('#^[a-z][a-z0-9+.\-]*:#i', $url) === 1) {
        return preg_match('#^https?://#i', $url) === 1 && filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    if (str_starts_with($url, '//')) {
        return filter_var('https:' . $url, FILTER_VALIDATE_URL) !== false;
    }

    return $allowRelative;
}

/**
 * Liest gespeicherte weiterführende Links (B28). Einheitliches Format ist JSON
 * ([{"title":…,"url":…,"target":…}]); das zeilenweise Format bis 3.11
 * („Titel!@!@!URL!@!@!Ziel“) wird weiterhin erkannt.
 *
 * @return list<array{title: string, url: string, target: string}>
 */
function pn_relatedlinks_decode(string $stored): array
{
    $stored = trim($stored);
    $links = [];

    if ($stored === '') {
        return [];
    }

    if (str_starts_with($stored, '[')) {
        $decoded = json_decode($stored, true);

        foreach (is_array($decoded) ? $decoded : [] as $item) {
            if (is_array($item) && is_string($item['title'] ?? null) && is_string($item['url'] ?? null)) {
                $links[] = ['title' => $item['title'], 'url' => $item['url'], 'target' => is_string($item['target'] ?? null) ? $item['target'] : ''];
            }
        }

        return $links;
    }

    foreach (explode("\n", str_replace("\r", '', $stored)) as $line) {
        $parts = explode('!@!@!', $line);

        if (count($parts) >= 2 && trim($parts[0]) !== '' && trim($parts[1]) !== '') {
            $links[] = ['title' => trim($parts[0]), 'url' => trim($parts[1]), 'target' => trim($parts[2] ?? '')];
        }
    }

    return $links;
}

/**
 * Speichert weiterführende Links als JSON (leere Liste: Leerstring).
 *
 * @param list<array{title: string, url: string, target: string}> $links
 */
function pn_relatedlinks_encode(array $links): string
{
    if ($links === []) {
        return '';
    }

    return (string) json_encode($links, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

/**
 * Baut die Linkliste aus den Formularfeldern. Zeilen ohne Titel und URL werden übersprungen,
 * das Ziel muss in der Liste $pn_config['rltargets'] stehen (sonst gilt der erste Eintrag).
 *
 * @param array<mixed> $titles
 * @param array<mixed> $urls
 * @param array<mixed> $targets
 * @param array<mixed> $allowedTargets
 *
 * @return array{0: list<array{title: string, url: string, target: string}>, 1: bool} Links und „ungültige URL gefunden“
 */
function pn_relatedlinks_from_input(array $titles, array $urls, array $targets, array $allowedTargets, bool $allowRelative): array
{
    $allowed = array_values(array_filter(array_map('strval', $allowedTargets), static fn (string $target): bool => $target !== ''));
    $fallback = $allowed[0] ?? '_blank';
    $links = [];
    $invalid = false;

    foreach (array_values($titles) as $index => $title) {
        $title = mb_substr(trim(is_scalar($title) ? (string) $title : ''), 0, 100);
        $url = trim(is_scalar($urls[$index] ?? null) ? (string) $urls[$index] : '');

        if ($title === '' && $url === '') {
            continue;
        }

        if ($title === '' || mb_strlen($url) > 250 || !pn_relatedlink_url_allowed($url, $allowRelative)) {
            $invalid = true;
            continue;
        }

        $target = is_scalar($targets[$index] ?? null) ? (string) $targets[$index] : '';
        $links[] = ['title' => $title, 'url' => $url, 'target' => in_array($target, $allowed, true) ? $target : $fallback];
    }

    return [$links, $invalid];
}
