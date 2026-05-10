<?php

/* PowerNews is a PHP and mySQL based newsscript - www.powerscripts.org */
/* Copyright (C) 2001-2026 PowerScripts                                 */

/* This program is free software; you can redistribute it and/or modify */
/* it under the terms of the GNU General Public License as published by */
/* the Free Software Foundation; either version 2 of the License, or    */
/* (at your option) any later version.                                  */

/* This program is distributed in the hope that it will be useful,      */
/* but WITHOUT ANY WARRANTY; without even the implied warranty of       */
/* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        */
/* GNU General Public License for more details.                         */

/* You should have received a copy of the GNU General Public License    */
/* along with this program; if not, write to the Free Software          */
/* Foundation, Inc., 59 Temple Place, Suite 330, Boston,                */
/* MA  02111-1307  USA                                                  */

?>
<div class="card pn-admin-card mb-4">
    <h2 class="card-header h6 mb-0">Lizenz</h2>
    <div class="card-body">
        <p class="mb-3"><?php echo L_OTHER_LICENSE_DESC; ?></p>
<?php if (@file_exists('./gnulicense.txt')) { ?>
        <textarea class="form-control font-monospace small" name="gnulicense" rows="20" readonly aria-label="GNU License"><?php include __DIR__ . '/gnulicense.txt'; ?></textarea>
<?php } else {
    ?>
        <div class="alert alert-warning mb-0" role="alert"><?php echo L_OTHER_NOLOCALLICENSE; ?></div>
<?php
} ?>
    </div>
</div>
