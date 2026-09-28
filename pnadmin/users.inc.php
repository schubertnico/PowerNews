<?php

/* PowerNews - PHP and MySQL based news script                          */
/* Copyright (c) 2001-2026 PowerScripts                                 */

/* MIT License - See LICENSE file for full license text                 */
/* https://github.com/schubertnico/PowerNews.git                        */

?>
<div class="card pn-admin-card mb-4">
    <h1 class="card-header h5 mb-0"><?php echo L_TITLE_USERS; ?></h1>
    <div class="card-body">
<?php
if (isset($_GET['subpage']) && $_GET['subpage']) {
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

    $requested_file = $_GET['page'] . '_' . $_GET['subpage'] . '.inc.php';

    if (in_array($requested_file, $allowed_files, true) && file_exists($requested_file)) {
        include $requested_file;
    } else {
        ?><div class="alert alert-warning mb-0" role="alert"><?php echo L_ALL_SUBPAGENOTFOUND; ?></div><?php
    }
} elseif (!pnadmin_can_read($pnadmin, 'users')) {
    // Übersicht nur mit Leserecht; Unterseiten prüfen ihre Rechte selbst.
    ?><div class="alert alert-danger mb-0" role="alert"><?php echo L_ALL_ACCESSDENIED; ?></div><?php
} else {
    ?>
        <p class="mb-0 text-muted"><?php echo L_ALL_CHOOSESUBPAGE; ?></p>
    <?php
}
?>
    </div>
</div>
