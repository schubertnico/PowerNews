<?php

/* PowerNews - PHP and MySQL based news script                          */
/* Copyright (c) 2001-2026 PowerScripts                                 */

/* MIT License - See LICENSE file for full license text                 */
/* https://github.com/schubertnico/PowerNews.git                        */

if ($pnadmin['canreadpermissions'] == 'YES') {
    ?>
    <div class="table-responsive">
        <table class="table table-striped pn-admin-table align-middle">
            <thead>
                <tr>
                    <th style="min-width: 150px"><?php echo L_PERM_NICK; ?></th>
                    <th><?php echo L_PERM_PERMISSIONS; ?></th>
                </tr>
            </thead>
            <tbody>
<?php
                $permissions = new permissions();
    $permissions->listpermissions();
    ?>
            </tbody>
        </table>
    </div>
    <p class="pn-help mb-0"><?php echo L_PERM_SHOW_DESC; ?></p>
    <?php
} else {
    ?><div class="alert alert-danger mb-0" role="alert"><?php echo L_ALL_ACCESSDENIED; ?></div><?php
}
?>
