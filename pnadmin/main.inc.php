<?php

/* PowerNews - PHP and MySQL based news script                          */
/* Copyright (c) 2001-2026 PowerScripts                                 */

/* MIT License - See LICENSE file for full license text                 */
/* https://github.com/schubertnico/PowerNews.git                        */

?>
<div class="card pn-admin-card">
    <h1 class="card-header h5 mb-0"><?php echo L_TITLE_MAIN; ?></h1>
    <div class="card-body">
<?php if (str_contains((string) ($pnconfig['url'] ?? ''), 'powerscripts.org') || str_ends_with((string) ($pnconfig['email'] ?? ''), '@powerscripts.org')) { ?>
        <div class="alert alert-warning" role="alert"><?php echo L_ALL_DEFAULTCONFIGWARNING; ?></div>
<?php } ?>
<?php
$dashboard = new dashboard();
$dashboard->render($pnadmin);
?>
        <p class="mb-0"><?php echo L_ALL_WELCOME; ?></p>
    </div>
</div>
