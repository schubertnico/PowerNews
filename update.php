<?php

declare(strict_types=1);

/* PowerNews - PHP and MySQL based news script                          */
/* Copyright (c) 2001-2026 PowerScripts                                 */

/* MIT License - See LICENSE file for full license text                 */
/* https://github.com/schubertnico/PowerNews.git                        */

/*
 * Update einer bestehenden PowerNews-3.x-Installation (Datenbankteil).
 *
 * Vorher: Datensicherung, neue Dateien hochladen (install.php weglassen),
 * Zugangsdaten in pninc/config.local.php – siehe INSTALLATION.md, Abschnitt
 * „Update von 3.11 auf 3.12“. Nur für angemeldete Admins mit dem Recht
 * „Konfiguration schreiben“. Jeder Schritt prüft selbst, ob er nötig ist.
 */

use PowerNews\Installer\DatabaseSetup;
use PowerNews\Installer\Schema;
use PowerNews\Installer\ServerVersion;
use PowerNews\Installer\Updater;
use PowerNews\Installer\View;
use PowerNews\LocalConfig;

include __DIR__ . '/pninc/config.inc.php';
include __DIR__ . '/pnadmin/functions.inc.php';
require __DIR__ . '/pninc/installer/autoload.php';

header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-store, max-age=0');
header('X-Robots-Tag: noindex, nofollow');

/* Admin-Auth (BUG-043): pncookie via pn_sessions verifizieren und canwriteconfig=YES verlangen. */
$adminInfo = pnadmin_auth_check();

if ($adminInfo === null || ($adminInfo['canwriteconfig'] ?? 'NO') !== 'YES') {
    http_response_code(403);
    echo '<!DOCTYPE html><html lang="de"><head><meta charset="UTF-8"><title>PowerNews-Update</title></head>'
        . '<body style="font-family:system-ui,sans-serif;max-width:40rem;margin:3rem auto;padding:0 1rem">'
        . '<h1>Nur für Admins</h1><p>Bitte melden Sie sich zuerst im <a href="./pnadmin/">Adminbereich</a> an '
        . '(Recht „Konfiguration schreiben“) und rufen Sie <code>update.php</code> dann erneut auf.</p></body></html>';
    exit;
}

$csrfToken = pn_csrf_token();
$message = '';

try {
    $updater = new Updater($pn_handler, __DIR__, Schema::fromFile(__DIR__ . '/' . Schema::FILENAME), $pn_config);
} catch (RuntimeException $e) {
    $updater = null;
    $message = $e->getMessage();
}

if ($updater !== null && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (pn_csrf_verify($_POST['csrf_token'] ?? null)) {
        $_SESSION['pn_update_results'] = $updater->run(date('Y-m-d H:i:s'));
        header('Location: update.php', true, 303);
        exit;
    }
    $message = 'Ihre Sitzung ist abgelaufen oder Cookies sind blockiert. Bitte starten Sie das Update erneut.';
}

$results = $_SESSION['pn_update_results'] ?? null;
unset($_SESSION['pn_update_results']);

$server = ServerVersion::parse(DatabaseSetup::serverVersion($pn_handler));
$source = LocalConfig::load(static fn (string $name): string|false => getenv($name), __DIR__ . '/pninc/' . LocalConfig::FILENAME)['source'];

View::render('update', 'Datenbank aktualisieren', 'Update', 0, 0, [
    'csrf' => $csrfToken,
    'message' => $message,
    'version' => PN_VERSION,
    'phpVersion' => PHP_VERSION,
    'server' => $server,
    'configSource' => $source,
    'steps' => $updater?->status() ?? [],
    'results' => is_array($results) ? $results : null,
]);
