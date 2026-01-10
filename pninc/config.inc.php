<?php
declare(strict_types=1);
/************************************************************************/
/* PowerNews - PHP and MySQL based news script                          */
/* Copyright (c) 2001-2024 PowerScripts                                 */
/*                                                                      */
/* MIT License - See LICENSE file for full license text                 */
/* https://github.com/schubertnico/PowerNews.git                        */
/************************************************************************/

/*
 * Configuration file for PowerNews
 *
 * Database credentials can be set via environment variables:
 * - PN_DB_HOST: MySQL server hostname
 * - PN_DB_USER: MySQL username
 * - PN_DB_PASS: MySQL password
 * - PN_DB_NAME: MySQL database name
 *
 * Or edit the fallback values below (not recommended for production)
 */

// Error logging configuration
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../logs/php-error.log');
ini_set('display_errors', '0');

// MySQL settings - prefer environment variables for security
$pn_config['mysqlhost'] = getenv('PN_DB_HOST') ?: 'localhost';
$pn_config['mysqluser'] = getenv('PN_DB_USER') ?: 'root';
$pn_config['mysqlpass'] = getenv('PN_DB_PASS') ?: '';
$pn_config['mysqldata'] = getenv('PN_DB_NAME') ?: 'powernews';


// The names of the tables - don't change, only if you want to install PN two times
$pn_config['cattable'] = "pn_categories";
$pn_config['commenttable'] = "pn_comments";
$pn_config['configtable'] = "pn_config";
$pn_config['newstable'] = "pn_news";
$pn_config['permissionstable'] = "pn_permissions";
$pn_config['templatetable'] = "pn_templates";
$pn_config['usertable'] = "pn_users";


// The names of the extern PowerNews files - check the example ones
$pn_config['newsfile'] = "index.php";
$pn_config['detailfile'] = "news.php";
$pn_config['commentfile'] = "comments.php";
$pn_config['userfile'] = "user.php";
$pn_config['archivefile'] = "archive.php";
$pn_config['sendnewsfile'] = "sendnews.php";

// Activate puffer in admin center - TRUE or FALSE - Can cause problems on some webservers
$pn_config['acpuffer'] = true;

// Select your language for PowerNews
$pn_config['language'] = "german-du";

// Array with targets for related links - example: $pn_config['rltargets'] = array("_blank", "_main", "_top");
$pn_config['rltargets'] = ["_blank", "_main"];


// Please DO NOT EDIT the following code

$pn_config['version'] = "3.00";

// Connect to mySQL Server and select database
if (!isset($pn_handler)) {
    try {
        $pn_handler = mysqli_connect(
            $pn_config['mysqlhost'],
            $pn_config['mysqluser'],
            $pn_config['mysqlpass']
        );
        if (!$pn_handler) {
            throw new Exception("PowerNews: mySQL connection failed! Error: " . mysqli_connect_error());
        }

        // Set charset to UTF-8
        mysqli_set_charset($pn_handler, 'utf8mb4');

        $db_selected = mysqli_select_db($pn_handler, $pn_config['mysqldata']);
        if (!$db_selected) {
            throw new Exception("PowerNews: mySQL database-selection failed! Error: " . mysqli_error($pn_handler));
        }
    } catch (Exception $e) {
        error_log($e->getMessage());
        die("<center><b>Database connection error. Please check your configuration.</b></center>");
    }
}
