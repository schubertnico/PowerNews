<?php

/* PowerNews - PHP and MySQL based news script                          */
/* Copyright (c) 2001-2026 PowerScripts                                 */

/* MIT License - See LICENSE file for full license text                 */
/* https://github.com/schubertnico/PowerNews.git                        */

$startoutput = explode(' ', microtime());

// Check if phpheader file exists and include it
if (@file_exists('phpheader.inc.php')) {
    include __DIR__ . '/phpheader.inc.php';
} else {
    echo '<div style="font-family:system-ui;margin:2rem;padding:1rem;border:1px solid #dc3545;color:#842029;background:#f8d7da;border-radius:.375rem;">Die Datei <strong>phpheader.inc.php</strong> wurde nicht gefunden!</div>';
    exit;
}

if ($pn_config['acpuffer'] == true) {
    //  echo '1';exit;
    ob_start('ob_gzhandler');
}

$psdesignscript = 'PowerNews';
$psdesignversion = PN_VERSION;

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
    <title><?php echo pnadmin_escape($psdesignscript . ' ' . $psdesignversion); ?> &mdash; <?php echo L_ALL_ADMINCENTER; ?></title>
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
            /* Sperrung per CSS statt "K O N F I G U R A T I O N" im Text (Screenreader lesen ganze Wörter). */
            letter-spacing: 0.06em;
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
        /* Die Navbar ist dunkel: dort bleibt kleine Schrift hell (B17). */
        body.pn-admin-body .navbar .navbar-text,
        body.pn-admin-body .navbar .small {
            color: #f8f9fa !important;
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
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark" aria-label="<?php echo L_ALL_MAINNAV; ?>">
        <div class="container-fluid">
            <a class="navbar-brand" href="./">
                <?php echo pnadmin_escape($psdesignscript . ' ' . $psdesignversion); ?><span class="d-none d-xxl-inline"> &ndash; <?php echo L_ALL_ADMINCENTER; ?></span>
            </a>
<?php if ($isLoggedIn) { ?>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#pnAdminNav" aria-controls="pnAdminNav" aria-expanded="false" aria-label="<?php echo L_ALL_TOGGLENAV; ?>">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="pnAdminNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
<?php echo pnadmin_nav($pnadmin, (string) $currentPage); ?>
                </ul>
                <div class="d-flex align-items-center gap-2 flex-wrap">
<?php if (isset($pnuser['nickname'])) { ?>
                    <span class="navbar-text text-light small">
                        <?php echo L_USR_HELLO; ?> <strong><?php echo pnadmin_escape($pnuser['nickname']); ?></strong>
                    </span>
                    <a class="btn btn-outline-light btn-sm" href="index.php?page=profile"><?php echo L_USR_EDITPROFILE; ?></a>
<?php } ?>
                    <a class="btn btn-outline-light btn-sm" href="../"><?php echo L_MENU_EXTERN; ?></a>
                    <form action="index.php?pnlogout=YES" method="post" class="m-0"><?php echo pnadmin_csrf_field(); ?><button type="submit" class="btn btn-warning btn-sm"><?php echo L_USR_LOGOUT; ?></button></form>
                </div>
            </div>
<?php } else { ?>
            <a class="btn btn-outline-light btn-sm ms-auto" href="../"><?php echo L_MENU_EXTERN; ?></a>
<?php } ?>
        </div>
    </nav>

<?php $quicklinks = $isLoggedIn ? pnadmin_quicklinks($pnadmin) : []; ?>
<?php if ($quicklinks !== []) { ?>
    <div class="pn-admin-status">
        <div class="container-fluid py-2 d-flex flex-wrap justify-content-end align-items-center small">
            <div>
                <strong><?php echo L_QUICKLINKS; ?>:</strong>
                <?php echo implode(' | ', array_map(static fn (array $link): string => '<a href="' . pnadmin_escape($link[0]) . '">' . $link[1] . '</a>', $quicklinks)); ?>
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
        'templates' => L_MENU_TEMPLATES,
        'users' => L_MENU_USERS,
        'permissions' => L_MENU_PERMISSIONS,
        'configuration' => L_MENU_CONFIG,
        'categories' => L_MENU_CATEGORIES,
        'news' => L_MENU_NEWS,
        'other' => L_MENU_OTHER,
        'profile' => L_TITLE_PROFILE,
        'main' => L_ALL_START,
    ];
    $subpageLabels = [
        'add' => L_SUB_ADD,
        'show' => L_SUB_SHOW,
        'edit' => L_SUB_EDIT,
        'search' => L_SUB_SEARCH,
        'help' => L_SUB_HELP,
        'license' => L_SUB_LICENSE,
    ];
    $sectionKey = (string) $_GET['page'];
    $sectionLabel = $sectionLabels[$sectionKey] ?? ucfirst($sectionKey);
    $subpageKey = isset($_GET['subpage']) ? (string) $_GET['subpage'] : '';
    $subpageLabel = $subpageKey !== '' ? ($subpageLabels[$subpageKey] ?? ucfirst($subpageKey)) : '';
    ?>
        <nav aria-label="<?php echo L_ALL_BREADCRUMB; ?>" class="mb-3">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="index.php"><?php echo L_ALL_START; ?></a></li>
<?php if ($subpageLabel === '') { ?>
                <li class="breadcrumb-item active" aria-current="page"><?php echo pnadmin_escape($sectionLabel); ?></li>
<?php } else { ?>
                <li class="breadcrumb-item"><a href="index.php?page=<?php echo pnadmin_escape($sectionKey); ?>"><?php echo pnadmin_escape($sectionLabel); ?></a></li>
                <li class="breadcrumb-item active" aria-current="page"><?php echo pnadmin_escape($subpageLabel); ?></li>
<?php } ?>
            </ol>
        </nav>

<?php
    // Unterseiten-Knöpfe nur mit passendem Recht; ohne sichtbaren Knopf entfällt die Leiste.
    $submenuHtml = '';

    if (isset($individualmenus)) {
        ob_start();
        $individualmenus->submenu($sectionKey, $pnadmin);
        $submenuHtml = (string) ob_get_clean();
    }
    ?>
<?php if (trim($submenuHtml) !== '') { ?>
        <nav aria-label="<?php echo L_QUICKLINKS; ?>" class="card mb-3">
            <div class="card-body py-2 d-flex flex-wrap gap-2 align-items-center">
<?php echo $submenuHtml; ?>
            </div>
        </nav>
<?php } ?>
<?php } ?>

<?php if (!empty($pncsrferror)) { ?>
        <div class="alert alert-danger" role="alert"><?php echo L_ALL_CSRFINVALID; ?></div>
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
        <a href="https://www.powerscripts.org" target="_blank" rel="noopener noreferrer"><?php echo pnadmin_escape($psdesignscript . ' ' . PN_VERSION); ?> &copy; <?php echo PN_COPYRIGHT_YEARS; ?> PowerScripts</a>
    </div>
</footer>
</div>

<script src="../assets/bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php if ($pn_config['acpuffer'] == true) {
    ob_implicit_flush();
} ?>
