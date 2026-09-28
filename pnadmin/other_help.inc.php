<?php

/* PowerNews - PHP and MySQL based news script                          */
/* Copyright (c) 2001-2026 PowerScripts                                 */

/* MIT License - See LICENSE file for full license text                 */
/* https://github.com/schubertnico/PowerNews.git                        */

?>
<div class="card pn-admin-card mb-4">
    <h2 class="card-header h6 mb-0"><?php echo L_OTHER_HELP; ?></h2>
    <div class="card-body">
<?php
if (@file_exists('./lang/' . $pn_config['language'] . '_help.php')) {
    include './lang/' . $pn_config['language'] . '_help.php';
} else {
    include __DIR__ . '/404.inc.php';
}
?>
    </div>
</div>
