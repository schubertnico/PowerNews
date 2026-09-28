<?php

/* PowerNews - PHP and MySQL based news script                          */
/* Copyright (c) 2001-2026 PowerScripts                                 */

/* MIT License - See LICENSE file for full license text                 */
/* https://github.com/schubertnico/PowerNews.git                        */

if ($pnadmin['canreadcategories'] == 'YES') {
    if ($pnconfig['categories'] == 'YES') {
        $category = new category();
        ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover pn-admin-table align-middle">
                <thead>
                    <tr>
                        <th><?php echo L_CAT_TITLE; ?></th>
                        <th><?php echo L_CAT_DESCRIPTION; ?></th>
                        <th class="text-center"><?php echo L_CAT_STATUS; ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php $category->listcats(); ?>
                </tbody>
            </table>
        </div>
        <p class="pn-help mb-0"><?php echo L_CAT_CLICKFORDETAILS; ?></p>
        <?php
    } else {
        ?><div class="alert alert-warning mb-0" role="alert"><?php echo L_CAT_CATSAREDEACTIVATED; ?></div><?php
    }
} else {
    ?><div class="alert alert-danger mb-0" role="alert"><?php echo L_ALL_ACCESSDENIED; ?></div><?php
}
?>
