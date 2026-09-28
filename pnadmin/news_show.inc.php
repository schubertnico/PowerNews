<?php

/* PowerNews - PHP and MySQL based news script                          */
/* Copyright (c) 2001-2026 PowerScripts                                 */

/* MIT License - See LICENSE file for full license text                 */
/* https://github.com/schubertnico/PowerNews.git                        */

if ($pnadmin['canreadnews'] == 'YES') {
    $news = new news();
    $status = (string) pn_validate_whitelist($_GET['status'] ?? '', news::STATUSES, '');

    if (!isset($_GET['current'])) {
        $_GET['current'] = '0';
    }
    $news->statusfilter($status);
    ?>
    <nav aria-label="<?php echo L_ALL_PAGINATION_TOP; ?>" class="mb-3">
        <ul class="pagination pagination-sm mb-0 flex-wrap"><?php $news->listpages($status); ?></ul>
    </nav>

    <div class="table-responsive">
        <table class="table table-striped table-hover pn-admin-table align-middle">
            <thead>
                <tr>
                    <th><?php echo L_NEWS_DATE; ?></th>
<?php if ($pnconfig['categories'] == 'YES') { ?>
                    <th><?php echo L_NEWS_CATEGORY; ?></th>
<?php } ?>
                    <th><?php echo L_NEWS_TITLE; ?></th>
                    <th class="text-center"><?php echo L_NEWS_STATUS; ?></th>
                </tr>
            </thead>
            <tbody>
                <?php $news->listnews((int) $_GET['current'], $status); ?>
            </tbody>
        </table>
    </div>

    <nav aria-label="<?php echo L_ALL_PAGINATION_BOTTOM; ?>" class="mb-3">
        <ul class="pagination pagination-sm mb-0 flex-wrap"><?php $news->listpages($status); ?></ul>
    </nav>

    <p class="pn-help mb-0"><?php echo L_NEWS_SHOW_DESC; ?></p>
    <?php
} else {
    ?><div class="alert alert-danger mb-0" role="alert"><?php echo L_ALL_ACCESSDENIED; ?></div><?php
}
?>
