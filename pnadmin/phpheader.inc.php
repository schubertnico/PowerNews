<?php
declare(strict_types=1);

/* PowerNews - PHP and MySQL based news script                         */
/* Copyright (c) 2001-2026 PowerScripts                                 */

/* MIT License - See LICENSE file for full license text                 */
/* https://github.com/schubertnico/PowerNews.git                        */

// Set error reporting - all errors logged but not displayed
error_reporting(E_ALL);

// Check if config file exists and include it
if (file_exists('../pninc/config.inc.php')) {
    include __DIR__ . '/../pninc/config.inc.php';
} else {
    echo '<div style="font-family:system-ui;margin:2rem;padding:1rem;border:1px solid #dc3545;color:#842029;background:#f8d7da;border-radius:.375rem;">File <strong>config.inc.php</strong> was not found!</div>';
    exit;
}

// Check if functions file exists and include it
if (file_exists('functions.inc.php')) {
    include __DIR__ . '/functions.inc.php';
} else {
    echo '<div style="font-family:system-ui;margin:2rem;padding:1rem;border:1px solid #dc3545;color:#842029;background:#f8d7da;border-radius:.375rem;">File <strong>functions.inc.php</strong> was not found!</div>';
    exit;
}

// Include DTO classes
if (file_exists(__DIR__ . '/dto.inc.php')) {
    include __DIR__ . '/dto.inc.php';
}

// Check if language file exists and include it
if (file_exists('./lang/' . $pn_config['language'] . '.php')) {
    include './lang/' . $pn_config['language'] . '.php';
} else {
    echo '<div style="font-family:system-ui;margin:2rem;padding:1rem;border:1px solid #dc3545;color:#842029;background:#f8d7da;border-radius:.375rem;">Language file <strong>' . htmlspecialchars((string) $pn_config['language'], ENT_QUOTES, 'UTF-8') . '.php</strong> was not found</div>';
    exit;
}

// Set pnloggedin to default status
$pnloggedin = 'NO';

//var_dump($_GET, $_POST);
// Check login and set cookie
if (isset($_GET['pnlogin']) && $_GET['pnlogin'] == 'YES') {
    if (!isset($_POST['pnlogin_nickname']) || !isset($_POST['pnlogin_password'])) {
        $loginerror = L_USR_NICKANDPW;
    } else {
        $newlogin = new login();
        $loginerror = $newlogin->checklogin($_POST['pnlogin_nickname'], $_POST['pnlogin_password']);

        if ($loginerror === 'loggedin') {
            $pnloggedin = 'YES';
        }
    }
} elseif (isset($_GET['pnlogout']) && $_GET['pnlogout'] == 'YES') {
    $logout = new login();
    $logout->logout();
    $pnloggedin = 'NO';
    unset($_COOKIE['pncookie']);
}

//var_dump($loginerror, $pnloggedin, $_COOKIE);

// Check if cookie was set and read data
if (isset($_COOKIE['pncookie']) && $_COOKIE['pncookie']) {
    $cookiecontent = base64_decode((string) $_COOKIE['pncookie'], true);
    $cookiecontent = explode('@@@@@', $cookiecontent);
    $checkdata = new getadmin();
    $pnuser = $checkdata->getuserdata((int) $cookiecontent[0], $cookiecontent[1]);
    $pnloggedin = $pnuser['loggedin'];

    if ($pnloggedin == 'YES') {
        $pnadmin = $checkdata->getpermissions((int) $cookiecontent[0]);
        $pnloggedin = $pnadmin['loggedin'];
    } else {
        $pnloggedin = 'NO';
    }
} else {
    $pnloggedin = 'NO';
}

// Get configuration data
$cresult = mysqli_query($pn_handler, 'SELECT * FROM ' . $pn_config['configtable']);
$cnum = mysqli_num_rows($cresult);

if ($cnum == 1) {
    $pnconfig = mysqli_fetch_array($cresult);
    //var_dump($pnconfig);exit;
} else {
    ?>
    <div style="font-family:system-ui;margin:2rem;padding:1rem;border:1px solid #dc3545;color:#842029;background:#f8d7da;border-radius:.375rem;">There are too many configurations or no one!</div><?php
    exit;
}

// Status- und Submenu-Renderer fuer den Adminkopf instanziieren.
// Ohne diese Instanz blieb die Statusleiste leer ("kein Logout sichtbar").
$individualmenus = new menus();
?>