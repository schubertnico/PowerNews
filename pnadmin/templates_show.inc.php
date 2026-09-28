<?php

/* PowerNews - PHP and MySQL based news script                          */
/* Copyright (c) 2001-2026 PowerScripts                                 */

/* MIT License - See LICENSE file for full license text                 */
/* https://github.com/schubertnico/PowerNews.git                        */

if ($pnadmin['canreadtemplates'] == 'YES') {
    ?>
    <div class="table-responsive">
        <table class="table table-striped table-hover pn-admin-table align-middle">
            <thead>
                <tr>
                    <th><?php echo L_TEMPL_TITLE; ?></th>
                </tr>
            </thead>
            <tbody>
<?php
                $template = new template();
                $template->listtemplates();
?>
            </tbody>
        </table>
    </div>
    <p class="pn-help mb-0"><?php echo L_TEMPL_SHOW_DESC; ?></p>
    <?php
} else {
    ?><div class="alert alert-danger mb-0" role="alert"><?php echo L_ALL_ACCESSDENIED; ?></div><?php
}
?>
