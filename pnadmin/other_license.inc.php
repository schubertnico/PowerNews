<?php

/* PowerNews - PHP and MySQL based news script                          */
/* Copyright (c) 2001-2026 PowerScripts                                 */

/* MIT License - See LICENSE file for full license text                 */
/* https://github.com/schubertnico/PowerNews.git                        */

?>
<div class="card pn-admin-card mb-4">
    <h2 class="card-header h6 mb-0"><?php echo L_OTHER_LICENSE; ?></h2>
    <div class="card-body">
        <p class="mb-3"><?php echo L_OTHER_LICENSE_DESC; ?></p>
<?php $pnlicense = is_file(dirname(__DIR__) . '/LICENSE') ? (string) file_get_contents(dirname(__DIR__) . '/LICENSE') : ''; ?>
<?php if ($pnlicense !== '') { ?>
        <textarea class="form-control font-monospace small" name="license" rows="20" readonly aria-label="<?php echo L_OTHER_LICENSE; ?>"><?php echo pnadmin_escape($pnlicense); ?></textarea>
<?php } else {
    ?>
        <div class="alert alert-warning mb-0" role="alert"><?php echo L_OTHER_NOLOCALLICENSE; ?></div>
<?php
} ?>
    </div>
</div>
