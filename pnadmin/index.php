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

$startoutput = explode(' ', microtime());

// Check if phpheader file exists and include it
if (@file_exists('phpheader.inc.php')) {
    include __DIR__ . '/phpheader.inc.php';
} else {
    echo '<div style="font-family:system-ui;margin:2rem;padding:1rem;border:1px solid #dc3545;color:#842029;background:#f8d7da;border-radius:.375rem;">File <strong>phpheader.inc.php</strong> was not found!</div>';
    exit;
}

if ($pn_config['acpuffer'] == true) {
    //  echo '1';exit;
    ob_start('ob_gzhandler');
}

$psdesignscript = 'PowerNews';
$psdesignversion = '3.10';

// Determine current page for active navigation state
$currentPage = $_GET['page'] ?? 'main';
$currentSubpage = $_GET['subpage'] ?? '';

// Vor jeder Seite den Login-Status pruefen, um die Navigation/QuickLinks
// nur eingeloggten Nutzern zu zeigen. Andernfalls vermittelt eine sichtbare
// Adminnavigation faelschlich, dass man bereits angemeldet ist.
$pnloggedin ??= 'NO';
$isLoggedIn = ($pnloggedin === 'YES');
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo pnadmin_escape($psdesignscript . ' ' . $psdesignversion); ?> &mdash; AdminCenter</title>
    <link href="../assets/bootstrap/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="./poweradmin.css" type="text/css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .pn-admin-shell {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .pn-admin-content {
            flex: 1;
        }
        .pn-admin-card .card-header {
            background-color: #0d6efd;
            color: #ffffff;
            font-weight: 600;
        }
        .pn-admin-status {
            background-color: #e7f1ff;
            border-bottom: 1px solid #cfe2ff;
            color: #084298;
        }
        .pn-admin-status a {
            color: #084298;
            text-decoration: underline;
        }
        .pn-admin-status a:hover {
            color: #052c65;
        }
        .table.pn-admin-table th {
            background-color: #f1f3f5;
            color: #212529;
        }
        .pn-help,
        .form-text {
            color: #212529;
            font-size: 0.85em;
        }
        .pn-danger-action {
            border: 2px solid #dc3545;
            border-radius: 0.375rem;
            padding: 0.5rem;
            background-color: #fff5f5;
        }
        /*
         * Global keine grauen Texte. Das alte Bootstrap-Default-Grau (#6c757d /
         * --bs-secondary-color) ist auf hellem Hintergrund schlecht lesbar, daher
         * werden alle muted-/secondary-Klassen auf Schwarz/Dunkel uebersteuert.
         */
        body.pn-admin-body {
            color: #212529;
            --bs-secondary-color: #212529;
            --bs-tertiary-color: #212529;
            --bs-link-color: #0a58ca;
            --bs-link-hover-color: #084298;
        }
        body.pn-admin-body input,
        body.pn-admin-body textarea,
        body.pn-admin-body select {
            color: #212529;
            background-color: #ffffff;
        }
        body.pn-admin-body .text-muted,
        body.pn-admin-body .form-text,
        body.pn-admin-body .pn-help,
        body.pn-admin-body small,
        body.pn-admin-body .small {
            color: #212529 !important;
        }
        body.pn-admin-body .link-secondary {
            color: #0a58ca !important;
            text-decoration: underline;
        }
        body.pn-admin-body .link-secondary:hover {
            color: #084298 !important;
        }
        /* Outline-Secondary-Buttons komplett schwarze Schrift, kein Grau. */
        body.pn-admin-body .btn-outline-secondary {
            color: #000000;
            border-color: #212529;
            background-color: #ffffff;
        }
        body.pn-admin-body .btn-outline-secondary:hover,
        body.pn-admin-body .btn-outline-secondary:focus,
        body.pn-admin-body .btn-outline-secondary:active {
            color: #ffffff !important;
            background-color: #212529 !important;
            border-color: #212529 !important;
        }
        /* Footer und Copyright in voller dunkler Schrift. */
        body.pn-admin-body footer.border-top,
        body.pn-admin-body footer.border-top * {
            color: #212529 !important;
        }
        body.pn-admin-body footer.border-top a {
            color: #0a58ca !important;
            text-decoration: underline;
        }
    </style>
</head>
<body class="pn-admin-body">
<div class="pn-admin-shell">
<header>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark" aria-label="Hauptnavigation">
        <div class="container-fluid">
            <a class="navbar-brand" href="./">
                <?php echo pnadmin_escape($psdesignscript . ' ' . $psdesignversion); ?> AdminCenter
            </a>
<?php if ($isLoggedIn) { ?>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#pnAdminNav" aria-controls="pnAdminNav" aria-expanded="false" aria-label="Navigation umschalten">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="pnAdminNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link<?php echo $currentPage === 'templates' ? ' active' : ''; ?>" href="index.php?page=templates"><?php echo L_MENU_TEMPLATES; ?></a></li>
                    <li class="nav-item"><a class="nav-link<?php echo $currentPage === 'users' ? ' active' : ''; ?>" href="index.php?page=users"><?php echo L_MENU_USERS; ?></a></li>
                    <li class="nav-item"><a class="nav-link<?php echo $currentPage === 'permissions' ? ' active' : ''; ?>" href="index.php?page=permissions"><?php echo L_MENU_PERMISSIONS; ?></a></li>
                    <li class="nav-item"><a class="nav-link<?php echo $currentPage === 'configuration' ? ' active' : ''; ?>" href="index.php?page=configuration"><?php echo L_MENU_CONFIG; ?></a></li>
                    <li class="nav-item"><a class="nav-link<?php echo $currentPage === 'categories' ? ' active' : ''; ?>" href="index.php?page=categories"><?php echo L_MENU_CATEGORIES; ?></a></li>
                    <li class="nav-item"><a class="nav-link<?php echo $currentPage === 'news' ? ' active' : ''; ?>" href="index.php?page=news"><?php echo L_MENU_NEWS; ?></a></li>
                    <li class="nav-item"><a class="nav-link<?php echo $currentPage === 'other' ? ' active' : ''; ?>" href="index.php?page=other"><?php echo L_MENU_OTHER; ?></a></li>
                </ul>
                <div class="d-flex align-items-center gap-2 flex-wrap">
<?php if (isset($pnuser['nickname'])) { ?>
                    <span class="navbar-text text-light small">
                        <?php echo L_USR_HELLO; ?> <strong><?php echo pnadmin_escape($pnuser['nickname']); ?></strong>
                    </span>
                    <a class="btn btn-outline-light btn-sm" href="index.php?page=profile"><?php echo L_USR_EDITPROFILE; ?></a>
<?php } ?>
                    <a class="btn btn-outline-light btn-sm" href="../"><?php echo L_MENU_EXTERN; ?></a>
                    <a class="btn btn-warning btn-sm" href="index.php?pnlogout=YES"><?php echo L_USR_LOGOUT; ?></a>
                </div>
            </div>
<?php } else { ?>
            <a class="btn btn-outline-light btn-sm ms-auto" href="../"><?php echo L_MENU_EXTERN; ?></a>
<?php } ?>
        </div>
    </nav>

<?php if ($isLoggedIn) { ?>
    <div class="pn-admin-status">
        <div class="container-fluid py-2 d-flex flex-wrap justify-content-end align-items-center small">
            <div>
                <strong><?php echo L_QUICKLINKS; ?>:</strong>
                <a href="index.php?page=news&amp;subpage=add"><?php echo L_NEWS_WRITENEWS; ?></a> |
                <a href="index.php?page=news&amp;subpage=show"><?php echo L_NEWS_SHOWNEWS; ?></a> |
                <a href="index.php?page=users&amp;subpage=search"><?php echo L_USR_SEARCHUSR; ?></a>
            </div>
        </div>
    </div>
<?php } ?>
</header>

<main class="pn-admin-content py-4">
    <div class="container-fluid">
<?php if ($isLoggedIn && isset($_GET['page']) && $_GET['page']) { ?>
<?php
        // Lokalisierte Sektions- und Subpage-Namen fuer die Brotkrumen-Navigation.
        $sectionLabels = [
            'templates'     => 'Templates',
            'users'         => 'Benutzer',
            'permissions'   => 'Berechtigungen',
            'configuration' => 'Konfiguration',
            'categories'    => 'Kategorien',
            'news'          => 'News',
            'other'         => 'Sonstiges',
            'profile'       => 'Profil',
            'main'          => 'Start',
        ];
        $subpageLabels = [
            'add'     => 'Anlegen',
            'show'    => 'Anzeigen',
            'edit'    => 'Bearbeiten',
            'search'  => 'Suchen',
            'help'    => 'Hilfe',
            'license' => 'Lizenz',
        ];
        $sectionKey = (string) $_GET['page'];
        $sectionLabel = $sectionLabels[$sectionKey] ?? ucfirst($sectionKey);
        $subpageKey = isset($_GET['subpage']) ? (string) $_GET['subpage'] : '';
        $subpageLabel = $subpageKey !== '' ? ($subpageLabels[$subpageKey] ?? ucfirst($subpageKey)) : '';
?>
        <nav aria-label="Brotkrumen-Navigation" class="mb-3">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="index.php">Start</a></li>
<?php if ($subpageLabel === '') { ?>
                <li class="breadcrumb-item active" aria-current="page"><?php echo pnadmin_escape($sectionLabel); ?></li>
<?php } else { ?>
                <li class="breadcrumb-item"><a href="index.php?page=<?php echo pnadmin_escape($sectionKey); ?>"><?php echo pnadmin_escape($sectionLabel); ?></a></li>
                <li class="breadcrumb-item active" aria-current="page"><?php echo pnadmin_escape($subpageLabel); ?></li>
<?php } ?>
            </ol>
        </nav>

<?php if (isset($individualmenus)) { ?>
        <nav aria-label="Schnellzugriff" class="card mb-3">
            <div class="card-body py-2 d-flex flex-wrap gap-2 align-items-center">
<?php
                $individualmenus->submenu($sectionKey);
?>
            </div>
        </nav>
<?php } ?>
<?php } ?>

<?php
if (!$isLoggedIn) {
    include __DIR__ . '/login.inc.php';
} else {
    $allowed_files = [
        'login.inc.php',
        'news.inc.php',
        'other_license.inc.php',
        'users_show.inc.php',
        'news_edit.inc.php',
        'other.inc.php',
        'news_search.inc.php',
        'profile.inc.php',
        'templates_edit.inc.php',
        'templates_show.inc.php',
        'users_search.inc.php',
        'news_show.inc.php',
        'categories_add.inc.php',
        'categories_show.inc.php',
        'permissions.inc.php',
        'permissions_add.inc.php',
        'permissions_edit.inc.php',
        'main.inc.php',
        'templates.inc.php',
        'users_add.inc.php',
        'other_help.inc.php',
        'templates_add.inc.php',
        'users_edit.inc.php',
        'news_add.inc.php',
        'users.inc.php',
        'categories.inc.php',
        'permissions_show.inc.php',
        'categories_edit.inc.php',
        'configuration.inc.php',
    ];

    if (!isset($_GET['page'])) {
        $_GET['page'] = 'main';
    }

    $file_to_include = $_GET['page'] . '.inc.php';

    if (in_array($file_to_include, $allowed_files, true) && file_exists($file_to_include)) {
        include $file_to_include;
    } else {
        ?>
        <div class="card pn-admin-card">
            <h1 class="card-header h5 mb-0"><?php echo L_TITLE_DOCUMENTNOTFOUND; ?></h1>
            <div class="card-body">
                <div class="alert alert-warning mb-0" role="alert"><?php echo L_ALL_NOPAGE; ?></div>
            </div>
        </div>
        <?php
    }
}
?>
    </div>
</main>

<footer class="border-top bg-white py-3 mt-auto">
    <div class="container-fluid text-center small">
<?php
    $endoutput = explode(' ', microtime());
    $startop = (float) $startoutput[1] + (float) $startoutput[0];
    $endop = (float) $endoutput[1] + (float) $endoutput[0];
    $outputtime = round($endop - $startop, 3);
?>
        <?php echo L_ALL_PAGECREATEDIN; ?> <?php echo pnadmin_escape((string) $outputtime); ?> <?php echo L_ALL_SECONDSBY; ?>
        <a href="https://www.powerscripts.org" target="_blank" rel="noopener noreferrer"><?php echo pnadmin_escape($psdesignscript . ' ' . $pn_config['version']); ?> &copy; 2001-2026 PowerScripts</a>
    </div>
</footer>
</div>

<script src="../assets/bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php if ($pn_config['acpuffer'] == true) {
    ob_implicit_flush();
} ?>
