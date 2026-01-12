<?php

/* PowerNews is a PHP and mySQL based newsscript - www.powerscripts.org */
/* Copyright (C) 2001-2023 PowerScripts                                 */

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
header('Content-Type: text/html; charset=ISO-8859-15');

// Check if config file exists and include
if (file_exists(__DIR__ . '/config.inc.php')) {
    include __DIR__ . '/config.inc.php';
} else {
    echo "<center>File <b>config.inc.php</b> wasn't found!</center>";
    exit;
}

// Check if functions file exists and include
if (file_exists(__DIR__ . '/functions.inc.php')) {
    include __DIR__ . '/functions.inc.php';
} else {
    echo "<center>File <b>functions.inc.php</b> wasn't found!</center>";
    exit;
}

// Check if language file exists and include
if (file_exists(__DIR__ . '/lang/' . $pn_config['language'] . '.php')) {
    include __DIR__ . '/lang/' . $pn_config['language'] . '.php';
} else {
    echo '<center>File <b>' .
      $pn_config['language'] . "
</b> wasn't found!</center>";
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
      <center>No PowerNews configuration found. Please check the mySQL table <b><?php
          echo $pn_config['configtable']; ?></b></center><?php
    } elseif ($cnum > 1) {
        ?>
      <center>Too many PowerNEws configurations found. Please check the mySQL table <b><?php
          echo $pn_config['configtable']; ?></b></center><?php
    }
    exit;
}

$pnuser['loggedin'] = 'NO';

if (isset($_GET['page']) && $_GET['page'] == 'login' && isset($_GET['pndata']['login']) && $_GET['pndata']['login'] == 'YES' && !empty($_POST['pndata']['nickname']) && !empty($_POST['pndata']['password'])) {
    $pnuserlogin = new pn_user();
    $pnuser = $pnuserlogin->setusercookie();
} elseif (isset($_GET['page']) && $_GET['page'] == 'logout' && isset($_COOKIE['pncookie']) && $_COOKIE['pncookie'] && isset($_GET['pndata']['logout']) && $_GET['pndata']['logout'] == 'YES') {
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