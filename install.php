<?php

declare(strict_types=1);

/* PowerNews - PHP and MySQL based news script                          */
/* Copyright (c) 2001-2026 PowerScripts                                 */

/* MIT License - See LICENSE file for full license text                 */
/* https://github.com/schubertnico/PowerNews.git                        */

/*
 * Web-Installer (Einstieg)
 *
 * Schritte: 1 Systemprüfung, 2 Datenbank, 3 Website, 4 Administrator,
 * 5 Abschluss. Die Logik liegt in pninc/installer/ (per .htaccess gesperrt).
 * Nach der Installation sperrt sich der Installer; diese Datei danach bitte
 * vom Server löschen. Anleitung: INSTALLATION.md.
 *
 * Diese Datei verwendet bewusst keine neue PHP-Syntax: Auch ältere
 * PHP-Versionen können sie lesen und zeigen eine verständliche Meldung,
 * statt mit einem Syntaxfehler abzubrechen.
 */

// Muss zu PowerNews\Installer\Requirements::MIN_PHP passen.
$pnRequiredPhp = '8.4.0';

if (version_compare(PHP_VERSION, $pnRequiredPhp, '<')) {
    header('Content-Type: text/html; charset=UTF-8', true, 500);
    echo '<!DOCTYPE html><html lang="de"><head><meta charset="UTF-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<title>PHP-Version zu alt · PowerNews-Installation</title></head>'
        . '<body style="font-family:system-ui,sans-serif;max-width:40rem;margin:3rem auto;padding:0 1rem">'
        . '<h1>PHP-Version zu alt</h1>'
        . '<p>PowerNews benötigt PHP ' . htmlspecialchars($pnRequiredPhp, ENT_QUOTES, 'UTF-8')
        . ' oder neuer. Auf diesem Server läuft PHP ' . htmlspecialchars(PHP_VERSION, ENT_QUOTES, 'UTF-8') . '.</p>'
        . '<p>Bei den meisten Hostern lässt sich die PHP-Version im Kundenmenü umstellen.</p>'
        . '</body></html>';
    exit;
}

if (!is_file(__DIR__ . '/pninc/installer/autoload.php')) {
    header('Content-Type: text/html; charset=UTF-8', true, 500);
    echo '<!DOCTYPE html><html lang="de"><head><meta charset="UTF-8"><title>Installer unvollständig</title></head>'
        . '<body style="font-family:system-ui,sans-serif;max-width:40rem;margin:3rem auto;padding:0 1rem">'
        . '<h1>Installer unvollständig</h1>'
        . '<p>Das Verzeichnis <code>pninc/installer/</code> fehlt. Bitte laden Sie alle Dateien von PowerNews erneut hoch.</p>'
        . '</body></html>';
    exit;
}

require __DIR__ . '/pninc/installer/autoload.php';

PowerNews\Installer\Installer::run(__DIR__);
