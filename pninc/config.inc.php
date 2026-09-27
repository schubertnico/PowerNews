<?php

declare(strict_types=1);

/* PowerNews - PHP and MySQL based news script                          */
/* Copyright (c) 2001-2026 PowerScripts                                 */

/* MIT License - See LICENSE file for full license text                 */
/* https://github.com/schubertnico/PowerNews.git                        */

/*
 * Configuration file for PowerNews
 *
 * Zugangsdaten zur Datenbank bitte NICHT hier eintragen – diese Datei wird
 * bei jedem Update überschrieben. Rangfolge (höchste zuerst):
 *   1. pninc/config.local.php (legt der Web-Installer install.php an)
 *   2. Umgebungsvariablen PN_DB_HOST, PN_DB_PORT, PN_DB_USER, PN_DB_PASS, PN_DB_NAME
 *   3. Vorgaben aus PowerNews\LocalConfig::DEFAULT_DB (localhost, root, powernews)
 * Details: INSTALLATION.md, Abschnitt „Konfiguration“.
 */

// Error logging configuration
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../logs/php-error.log');
ini_set('display_errors', '0');

// MySQL settings and language: config.local.php > environment variables > defaults
require_once __DIR__ . '/localconfig.inc.php';
$pn_local = PowerNews\LocalConfig::load(
    static fn (string $name): string|false => getenv($name),
    __DIR__ . '/' . PowerNews\LocalConfig::FILENAME,
);
$pn_config['mysqlhost'] = $pn_local['db']['host'];
$pn_config['mysqlport'] = $pn_local['db']['port'];
$pn_config['mysqluser'] = $pn_local['db']['user'];
$pn_config['mysqlpass'] = $pn_local['db']['password'];
$pn_config['mysqldata'] = $pn_local['db']['database'];

// The names of the tables - don't change, only if you want to install PN two times
$pn_config['cattable'] = 'pn_categories';
$pn_config['commenttable'] = 'pn_comments';
$pn_config['configtable'] = 'pn_config';
$pn_config['newstable'] = 'pn_news';
$pn_config['permissionstable'] = 'pn_permissions';
$pn_config['templatetable'] = 'pn_templates';
$pn_config['usertable'] = 'pn_users';

// The names of the extern PowerNews files - check the example ones
$pn_config['newsfile'] = 'index.php';
$pn_config['detailfile'] = 'news.php';
$pn_config['commentfile'] = 'comments.php';
$pn_config['userfile'] = 'user.php';
$pn_config['archivefile'] = 'archive.php';
$pn_config['sendnewsfile'] = 'sendnews.php';

// Activate puffer in admin center - TRUE or FALSE - Can cause problems on some webservers
$pn_config['acpuffer'] = true;

// Select your language for PowerNews (german-du, german-sie, english) - config.local.php may override it
$pn_config['language'] = $pn_local['language'];
unset($pn_local);

// Array with targets for related links - example: $pn_config['rltargets'] = array("_blank", "_main", "_top");
$pn_config['rltargets'] = ['_blank', '_main'];

// Please DO NOT EDIT the following code

$pn_config['version'] = '3.11';

// Connect to mySQL Server and select database
if (!isset($pn_handler)) {
    try {
        $pn_handler = mysqli_connect(
            $pn_config['mysqlhost'],
            $pn_config['mysqluser'],
            $pn_config['mysqlpass'],
            null,
            $pn_config['mysqlport'],
        );

        if (!$pn_handler) {
            throw new Exception('PowerNews: mySQL connection failed! Error: ' . mysqli_connect_error());
        }

        // Set charset to UTF-8
        mysqli_set_charset($pn_handler, 'utf8mb4');

        $db_selected = mysqli_select_db($pn_handler, $pn_config['mysqldata']);

        if (!$db_selected) {
            throw new Exception('PowerNews: mySQL database-selection failed! Error: ' . mysqli_error($pn_handler));
        }
    } catch (Exception $e) {
        error_log($e->getMessage());
        die('<div style="font-family:system-ui;margin:2rem;padding:1rem;border:1px solid #dc3545;color:#842029;background:#f8d7da;border-radius:.375rem;"><strong>Database connection error.</strong> Please check your configuration.</div>');
    }
}

// Include security layers
require_once __DIR__ . '/validation.inc.php';
require_once __DIR__ . '/escape.inc.php';
