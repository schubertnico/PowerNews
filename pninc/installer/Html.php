<?php

declare(strict_types=1);

/* PowerNews - PHP and MySQL based news script                          */
/* Copyright (c) 2001-2026 PowerScripts                                 */

/* MIT License - See LICENSE file for full license text                 */
/* https://github.com/schubertnico/PowerNews.git                        */

namespace PowerNews\Installer;

/**
 * Bootstrap-5-Formularbausteine für Installer und Update.
 *
 * Jedes Feld bekommt eine id, die dem name-Attribut entspricht; die
 * Fehlermeldung eines Felds hat die id „<feld>-error“, der Hilfetext
 * „<feld>-help“ (für Tests und Videoaufnahmen). Alle Werte werden maskiert.
 */
final class Html
{
    public static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Eingabefeld mit Label, Hilfetext und Fehlermeldung.
     *
     * @param array<string, string> $errors Fehlermeldungen je Feldname
     * @param array<string, string|int|bool> $attributes zusätzliche Attribute (true = ohne Wert, false = weglassen)
     */
    public static function input(
        string $name,
        string $label,
        string $value,
        array $errors,
        array $attributes = [],
        string $help = '',
    ): string {
        $error = $errors[$name] ?? '';
        $attributes += ['type' => 'text'];

        return '<div class="mb-3">'
            . self::label($name, $label, isset($attributes['required']) && $attributes['required'] === true)
            . '<input id="' . self::escape($name) . '" name="' . self::escape($name) . '"'
            . ' class="form-control' . ($error !== '' ? ' is-invalid' : '') . '"'
            . ' value="' . self::escape($value) . '"'
            . self::attributes($attributes)
            . self::describedBy($name, $help, $error)
            . ($error !== '' ? ' aria-invalid="true"' : '')
            . '>'
            . self::feedback($name, $error)
            . self::help($name, $help)
            . '</div>';
    }

    /**
     * Auswahlliste.
     *
     * @param array<string, string> $options Wert => Beschriftung
     * @param array<string, string> $errors
     */
    public static function select(
        string $name,
        string $label,
        array $options,
        string $selected,
        array $errors,
        string $help = '',
    ): string {
        $error = $errors[$name] ?? '';
        $html = '<div class="mb-3">'
            . self::label($name, $label, true)
            . '<select id="' . self::escape($name) . '" name="' . self::escape($name) . '"'
            . ' class="form-select' . ($error !== '' ? ' is-invalid' : '') . '" required'
            . self::describedBy($name, $help, $error)
            . ($error !== '' ? ' aria-invalid="true"' : '')
            . '>';

        foreach ($options as $value => $text) {
            $html .= '<option value="' . self::escape($value) . '"'
                . ($value === $selected ? ' selected' : '') . '>'
                . self::escape($text) . '</option>';
        }

        return $html . '</select>'
            . self::feedback($name, $error)
            . self::help($name, $help)
            . '</div>';
    }

    /**
     * Meldung als Bootstrap-Alert (leer, wenn keine Meldung).
     */
    public static function alert(string $message, string $type, string $id): string
    {
        if ($message === '') {
            return '';
        }

        $type = in_array($type, ['success', 'danger', 'warning', 'info'], true) ? $type : 'info';
        $role = $type === 'danger' || $type === 'warning' ? 'alert' : 'status';

        return '<div id="' . self::escape($id) . '" class="alert alert-' . $type . '" role="' . $role . '">'
            . self::escape($message) . '</div>';
    }

    /**
     * Verstecktes Feld mit dem CSRF-Token.
     */
    public static function csrfField(string $token): string
    {
        return '<input type="hidden" name="csrf_token" value="' . self::escape($token) . '">';
    }

    private static function label(string $name, string $label, bool $required): string
    {
        return '<label for="' . self::escape($name) . '" class="form-label fw-semibold">'
            . self::escape($label)
            . ($required ? ' <span class="text-danger" aria-hidden="true">*</span>' : '')
            . '</label>';
    }

    /**
     * @param array<string, string|int|bool> $attributes
     */
    private static function attributes(array $attributes): string
    {
        $html = '';

        foreach ($attributes as $key => $value) {
            if ($value === false) {
                continue;
            }
            $html .= ' ' . self::escape($key);

            if ($value !== true) {
                $html .= '="' . self::escape((string) $value) . '"';
            }
        }

        return $html;
    }

    private static function describedBy(string $name, string $help, string $error): string
    {
        $ids = [];

        if ($error !== '') {
            $ids[] = $name . '-error';
        }

        if ($help !== '') {
            $ids[] = $name . '-help';
        }

        return $ids === [] ? '' : ' aria-describedby="' . self::escape(implode(' ', $ids)) . '"';
    }

    private static function help(string $name, string $help): string
    {
        if ($help === '') {
            return '';
        }

        return '<div id="' . self::escape($name . '-help') . '" class="form-text">' . self::escape($help) . '</div>';
    }

    private static function feedback(string $name, string $message): string
    {
        if ($message === '') {
            return '';
        }

        return '<div id="' . self::escape($name . '-error') . '" class="invalid-feedback">' . self::escape($message) . '</div>';
    }
}
