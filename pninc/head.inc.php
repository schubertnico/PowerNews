<?php

/* PowerNews - PHP and MySQL based news script                          */
/* Copyright (c) 2001-2026 PowerScripts                                 */

/* MIT License - See LICENSE file for full license text                 */
/* https://github.com/schubertnico/PowerNews.git                        */

// Set error reporting
error_reporting(E_ALL & ~E_NOTICE);

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

// Session vor jeder Ausgabe starten, damit das CSRF-Token den Weg GET -> POST übersteht
// (HttpOnly, SameSite=Lax, Secure unter HTTPS).
pn_php_session_start();

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
    $pnuser = $pnuserlogin->setusercookie() ?? ['loggedin' => 'NO'];
} elseif (
    isset($_GET['page']) && $_GET['page'] === 'logout'
    && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
    && pn_csrf_verify($_POST['csrf_token'] ?? null)
    && !empty($_COOKIE[PN_COOKIE_FRONTEND])
) {
    $pnuserlogout = new pn_user();
    $pnuserlogout->delusercookie();
    exit;
}

if (($pnuser['loggedin'] ?? 'NO') !== 'YES' && !empty($_COOKIE[PN_COOKIE_FRONTEND])) {
    $pnusercheck = new pn_user();
    // Ungültige oder abgelaufene Cookies ergeben einen Gast, nie null (B33).
    $pnuser = $pnusercheck->checkcookie() ?? ['loggedin' => 'NO'];
}

// Eingeloggt „Login“ aufrufen: vor jeder Ausgabe zum Profil weiterleiten (B32).
if (($pnuser['loggedin'] ?? 'NO') === 'YES'
    && ($_GET['page'] ?? '') === 'login'
    && !isset($_GET['pndata']['login'])
    && basename((string) ($_SERVER['SCRIPT_NAME'] ?? '')) === $pn_config['userfile']
    && !headers_sent()
) {
    header('Location: ./' . $pn_config['userfile'] . '?page=profile');
    exit;
}

setlocale(LC_TIME, 'de_DE');
?>