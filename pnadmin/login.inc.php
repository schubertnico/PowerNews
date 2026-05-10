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
<div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-6">
        <div class="card pn-admin-card shadow-sm">
            <h1 class="card-header h5 mb-0"><?php echo L_TITLE_LOGIN; ?></h1>
            <div class="card-body">
<?php if (!isset($loginerror)) { ?>
                <form action="index.php?pnlogin=YES" method="post" novalidate>
                    <div class="mb-3">
                        <label for="pn_login_nick" class="form-label fw-bold"><?php echo L_USR_NICKNAME; ?></label>
                        <input class="form-control" name="pnlogin_nickname" id="pn_login_nick" maxlength="100" autocomplete="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="pn_login_pw" class="form-label fw-bold"><?php echo L_USR_PASSWORD; ?></label>
                        <input class="form-control" name="pnlogin_password" id="pn_login_pw" maxlength="100" type="password" autocomplete="current-password" required>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary"><?php echo L_USR_LOGIN; ?></button>
                    </div>
                </form>
                <p class="pn-help mt-3 mb-0"><?php echo L_USR_COOKIESMUSTBEENABLED; ?></p>
<?php } elseif ($loginerror == 'loggedin') { ?>
                <div class="alert alert-success mb-0" role="alert">
                    <a href="./" class="alert-link"><?php echo L_USR_LOGINOK; ?></a>
                </div>
<?php } else { ?>
                <div class="alert alert-danger mb-0" role="alert">
                    <p class="mb-2"><?php echo pnadmin_escape($loginerror); ?></p>
                    <a href="./" class="btn btn-sm btn-outline-secondary">Zur Anmeldung</a>
                </div>
<?php } ?>
            </div>
        </div>
    </div>
</div>
