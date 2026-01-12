<?php

declare(strict_types=1);

/* PowerNews - PHP and MySQL based news script                         */
/* Copyright (c) 2001-2024 PowerScripts                                 */

/* MIT License - See LICENSE file for full license text                 */
/* https://github.com/schubertnico/PowerNews.git                        */

/**
 * Zentrale Escape-Funktionen für sichere Ausgabe.
 *
 * Diese Funktionen escapen Strings für verschiedene Kontexte,
 * um XSS und andere Injection-Angriffe zu verhindern.
 */

/**
 * Escaped einen String für HTML-Kontext.
 *
 * Verwendung: Innerhalb von HTML-Tags
 * Beispiel: <p><?= pn_html($text) ?></p>
 *
 * @param string|null $string Der zu escapende String
 *
 * @return string Escaped String
 */
function pn_html(?string $string): string
{
    if ($string === null) {
        return '';
    }

    return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Escaped einen String für HTML-Attribut-Kontext.
 *
 * Verwendung: Innerhalb von HTML-Attributen
 * Beispiel: <input value="<?= pn_attr($value) ?>">
 *
 * @param string|null $string Der zu escapende String
 *
 * @return string Escaped String
 */
function pn_attr(?string $string): string
{
    if ($string === null) {
        return '';
    }

    return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Escaped einen String für JavaScript-Kontext.
 *
 * Verwendung: Innerhalb von JavaScript-Strings
 * Beispiel: <script>var name = <?= pn_js($name) ?>;</script>
 *
 * @param string|null $string Der zu escapende String
 *
 * @return string JSON-encodierter String (mit Anführungszeichen)
 */
function pn_js(?string $string): string
{
    if ($string === null) {
        return '""';
    }

    return json_encode($string, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR);
}

/**
 * Escaped einen String für URL-Parameter.
 *
 * Verwendung: In URL-Query-Strings
 * Beispiel: <a href="page.php?name=<?= pn_url($name) ?>">
 *
 * @param string|null $string Der zu escapende String
 *
 * @return string URL-encodierter String
 */
function pn_url(?string $string): string
{
    if ($string === null) {
        return '';
    }

    return rawurlencode($string);
}

/**
 * Escaped einen String für CSS-Kontext.
 *
 * Verwendung: Innerhalb von CSS-Werten
 * Beispiel: <div style="background: <?= pn_css($color) ?>">
 *
 * @param string|null $string Der zu escapende String
 *
 * @return string Escaped String (nur alphanumerisch + sichere Zeichen)
 */
function pn_css(?string $string): string
{
    if ($string === null) {
        return '';
    }

    // Nur sichere CSS-Zeichen erlauben
    return preg_replace('/[^a-zA-Z0-9#\-_\.\s%,()]/', '', $string) ?? '';
}

/**
 * Escaped einen Integer für sichere Ausgabe.
 *
 * Verwendung: Für numerische Werte in jedem Kontext
 * Beispiel: <input type="hidden" value="<?= pn_int($id) ?>">
 *
 * @param mixed $value Der zu escapende Wert
 *
 * @return int Integer-Wert
 */
function pn_int(mixed $value): int
{
    return (int) $value;
}

/**
 * Gibt einen sicheren Dateinamen zurück.
 *
 * Entfernt alle potenziell gefährlichen Zeichen aus Dateinamen.
 *
 * @param string|null $filename Der Dateiname
 *
 * @return string Sicherer Dateiname
 */
function pn_filename(?string $filename): string
{
    if ($filename === null) {
        return '';
    }
    // Entferne Path-Traversal und gefährliche Zeichen
    $filename = basename($filename);
    $filename = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $filename) ?? '';

    // Verhindere versteckte Dateien und doppelte Erweiterungen
    return ltrim($filename, '.');
}

/**
 * Shortcut für häufige Escape-Operationen.
 *
 * Escaped für HTML-Kontext (Standard-Fall)
 *
 * @param string|null $string Der zu escapende String
 *
 * @return string Escaped String
 */
function e(?string $string): string
{
    return pn_html($string);
}

/**
 * Gibt escaped HTML aus (Echo-Wrapper).
 *
 * @param string|null $string Der auszugebende String
 */
function eh(?string $string): void
{
    echo pn_html($string);
}

/**
 * Prüft ob ein String potenziell gefährliche Inhalte enthält.
 *
 * @param string $string Der zu prüfende String
 *
 * @return bool True wenn potenziell gefährlich
 */
function pn_is_dangerous(string $string): bool
{
    $patterns = [
        '/<script/i',
        '/javascript:/i',
        '/on\w+\s*=/i',  // onclick=, onerror=, etc.
        '/<iframe/i',
        '/<object/i',
        '/<embed/i',
        '/<link/i',
        '/<style/i',
        '/expression\s*\(/i',
        '/url\s*\(/i',
    ];

    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $string)) {
            return true;
        }
    }

    return false;
}

/**
 * Entfernt HTML-Tags aus einem String.
 *
 * @param string|null $string Der zu bereinigende String
 * @param array $allowedTags Erlaubte Tags (z.B. ['b', 'i', 'u'])
 *
 * @return string Bereinigter String
 */
function pn_strip_tags(?string $string, array $allowedTags = []): string
{
    if ($string === null) {
        return '';
    }

    if (empty($allowedTags)) {
        return strip_tags($string);
    }
    $allowed = '<' . implode('><', $allowedTags) . '>';

    return strip_tags($string, $allowed);
}
