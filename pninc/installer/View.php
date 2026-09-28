<?php

declare(strict_types=1);

/* PowerNews - PHP and MySQL based news script                          */
/* Copyright (c) 2001-2026 PowerScripts                                 */

/* MIT License - See LICENSE file for full license text                 */
/* https://github.com/schubertnico/PowerNews.git                        */

namespace PowerNews\Installer;

/**
 * Gibt eine Seite aus Layout und Inhaltsvorlage aus (templates/*.php).
 *
 * Jede Vorlage liefert per `return` eine Funktion; direkt aufgerufen gibt
 * sie nichts aus.
 */
final class View
{
    /**
     * Vorlagen, die gerendert werden dürfen.
     */
    public const array TEMPLATES = ['requirements', 'database', 'website', 'admin', 'finish', 'done', 'locked', 'update'];

    /**
     * @param array<string, mixed> $args benannte Argumente für die Vorlage
     * @param string $badge Kennzeichnung in der Kopfzeile, z. B. „Installation“
     * @param int $step aktueller Schritt (0 = ohne Schrittanzeige)
     * @param int $completed höchster erledigter Schritt
     */
    public static function render(string $template, string $title, string $badge, int $step, int $completed, array $args): void
    {
        if (!in_array($template, self::TEMPLATES, true)) {
            throw new \RuntimeException('Unbekannte Vorlage.');
        }

        $layout = self::load('layout');
        $body = self::load($template);

        $layout($title, $badge, $step, $completed, static function () use ($body, $args): void {
            $body(...$args);
        });
    }

    private static function load(string $name): callable
    {
        $file = __DIR__ . '/templates/' . $name . '.php';
        $callable = is_file($file) ? require $file : null;

        if (!is_callable($callable)) {
            throw new \RuntimeException('Die Vorlage ' . $name . '.php fehlt oder ist beschädigt. Bitte laden Sie das Verzeichnis pninc/installer/ erneut hoch.');
        }

        return $callable;
    }
}
