<?php

/* PowerNews - PHP and MySQL based news script                          */
/* Copyright (c) 2001-2026 PowerScripts                                 */

/* MIT License - See LICENSE file for full license text                 */
/* https://github.com/schubertnico/PowerNews.git                        */

if ($pnadmin['canreadusers'] == 'YES') {
    $listusers = new user();
    if (!isset($_GET['current'])) {
        $_GET['current'] = '0';
    }
    ?>
    <nav aria-label="<?php echo L_ALL_PAGINATION_TOP; ?>" class="mb-3">
        <ul class="pagination pagination-sm mb-0 flex-wrap"><?php $listusers->listpages(); ?></ul>
    </nav>

    <div class="table-responsive">
        <table class="table table-striped table-hover pn-admin-table align-middle">
            <thead>
                <tr>
                    <th><?php echo L_USR_NICKNAME; ?></th>
                    <th><?php echo L_USR_EMAIL; ?></th>
                    <th class="text-center"><?php echo L_USR_SHOWEMAIL; ?></th>
                    <th class="text-center"><?php echo L_USR_ADMIN; ?></th>
                    <th class="text-center"><?php echo L_USR_STATUS; ?></th>
                </tr>
            </thead>
            <tbody>
                <?php $listusers->listusers((int) $_GET['current']); ?>
            </tbody>
        </table>
    </div>

    <nav aria-label="<?php echo L_ALL_PAGINATION_BOTTOM; ?>" class="mb-3">
        <ul class="pagination pagination-sm mb-0 flex-wrap"><?php $listusers->listpages(); ?></ul>
    </nav>

    <p class="pn-help mb-0"><?php echo L_USR_SHOWUSR_DESC; ?></p>
    <?php
} else {
    ?><div class="alert alert-danger mb-0" role="alert"><?php echo L_ALL_ACCESSDENIED; ?></div><?php
}
?>
