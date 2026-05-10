<?php

/* PowerNews is a PHP and mySQL based newsscript - www.powerscripts.org */
/* Copyright (C) 2001-2026 PowerScripts                                 */

/* This program is free software; you can redistribute it and/or modify */
/* it under the terms of the GNU General Public License as published by */
/* the Free Software Foundation; either version 2 of the License, or    */
/* (at your option) any later version.                                  */

/* This program is distributed in the hope that it will be useful,      */
/* but WITHOUT ANY WARRANTY; without even the implied warranty of       */
/* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        */
/* GNU General Public License for more details.                         */

/* You should have received a copy of the GNU General Public License    */
/* along with this program; if not, write to the Free Software          */
/* Foundation, Inc., 59 Temple Place, Suite 330, Boston,                */
/* MA  02111-1307  USA                                                  */

// Set error reporting
error_reporting(E_ALL & ~E_NOTICE);

// Session early-start so CSRF-Token survives GET->POST form-roundtrip.
// Without this, pn_csrf_token() might try to session_start() after output began.
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: text/html; charset=UTF-8');

// Check if config file exists and include
if (file_exists(__DIR__ . '/config.inc.php')) {
    include __DIR__ . '/config.inc.php';
} else {
    echo '<div style="font-family:system-ui;margin:2rem;padding:1rem;border:1px solid #dc3545;color:#842029;background:#f8d7da;border-radius:.375rem;">File <strong>config.inc.php</strong> was not found!</div>';
    exit;
}

// Check if functions file exists and include
if (file_exists(__DIR__ . '/functions.inc.php')) {
    include __DIR__ . '/functions.inc.php';
} else {
    echo '<div style="font-family:system-ui;margin:2rem;padding:1rem;border:1px solid #dc3545;color:#842029;background:#f8d7da;border-radius:.375rem;">File <strong>functions.inc.php</strong> was not found!</div>';
    exit;
}

// Check if language file exists and include
if (file_exists(__DIR__ . '/lang/' . $pn_config['language'] . '.php')) {
    include __DIR__ . '/lang/' . $pn_config['language'] . '.php';
} else {
    echo '<div style="font-family:system-ui;margin:2rem;padding:1rem;border:1px solid #dc3545;color:#842029;background:#f8d7da;border-radius:.375rem;">File <strong>' .
      htmlspecialchars((string) $pn_config['language'], ENT_QUOTES, 'UTF-8') . '</strong> was not found!</div>';
    exit;
}

// Get configuration data
$cresult = mysqli_query($pn_handler, 'SELECT * FROM ' . $pn_config['configtable'] . '');
$cnum = mysqli_num_rows($cresult);

if ($cnum == 1) {
    $pnconfig = mysqli_fetch_array($cresult);
} else {
    if ($cnum == 0) {
        ?>
      <div style="font-family:system-ui;margin:2rem;padding:1rem;border:1px solid #dc3545;color:#842029;background:#f8d7da;border-radius:.375rem;">No PowerNews configuration found. Please check the mySQL table <strong><?php
          echo htmlspecialchars((string) $pn_config['configtable'], ENT_QUOTES, 'UTF-8'); ?></strong></div><?php
    } elseif ($cnum > 1) {
        ?>
      <div style="font-family:system-ui;margin:2rem;padding:1rem;border:1px solid #dc3545;color:#842029;background:#f8d7da;border-radius:.375rem;">Too many PowerNews configurations found. Please check the mySQL table <strong><?php
          echo htmlspecialchars((string) $pn_config['configtable'], ENT_QUOTES, 'UTF-8'); ?></strong></div><?php
    }
    exit;
}

$pnuser['loggedin'] = 'NO';

if (isset($_GET['page']) && $_GET['page'] == 'login'
    && isset($_GET['pndata']['login']) && $_GET['pndata']['login'] == 'YES'
    && !empty($_POST['pndata']['nickname']) && !empty($_POST['pndata']['password'])
    && pn_csrf_verify($_POST['csrf_token'] ?? null)) {
    $pnuserlogin = new pn_user();
    $pnuser = $pnuserlogin->setusercookie();
} elseif (
    isset($_GET['page']) && $_GET['page'] === 'logout'
    && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
    && pn_csrf_verify($_POST['csrf_token'] ?? null)
    && !empty($_COOKIE['pncookie'])
) {
    $pnuserlogout = new pn_user();
    $pnuserlogout->delusercookie();
    unset($pnuser, $_COOKIE['pncookie']);

    $pnuser['loggedin'] = 'NO';
}

if (isset($_COOKIE['pncookie']) && $_COOKIE['pncookie']) {
    $pnusercheck = new pn_user();
    $pnuser = $pnusercheck->checkcookie();
}

setlocale(LC_TIME, 'de_DE');
?>