<?php

declare(strict_types=1);

/* PowerNews - PHP and MySQL based news script                          */
/* Copyright (c) 2001-2026 PowerScripts                                 */

/* MIT License - See LICENSE file for full license text                 */
/* https://github.com/schubertnico/PowerNews.git                        */

/*
 * Lädt die Klassen von Installer und Update (Namensraum PowerNews\Installer)
 * sowie PowerNews\LocalConfig. Gibt nichts aus und startet nichts.
 */

require_once dirname(__DIR__) . '/localconfig.inc.php';

spl_autoload_register(static function (string $class): void {
    $prefix = 'PowerNews\\Installer\\';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $name = substr($class, strlen($prefix));
    $file = __DIR__ . '/' . $name . '.php';

    if (preg_match('/^[A-Za-z]+$/', $name) === 1 && is_file($file)) {
        require $file;
    }
});
