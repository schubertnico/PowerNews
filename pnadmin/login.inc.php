<?php

/* PowerNews - PHP and MySQL based news script                          */
/* Copyright (c) 2001-2026 PowerScripts                                 */

/* MIT License - See LICENSE file for full license text                 */
/* https://github.com/schubertnico/PowerNews.git                        */

?>
<div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-6">
        <div class="card pn-admin-card shadow-sm">
            <h1 class="card-header h5 mb-0"><?php echo L_TITLE_LOGIN; ?></h1>
            <div class="card-body">
<?php if (!isset($loginerror)) { ?>
                <form action="index.php?pnlogin=YES" method="post" novalidate><?php echo pnadmin_csrf_field(); ?>
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
                    <a href="./" class="btn btn-sm btn-outline-secondary"><?php echo L_USR_BACKTOLOGIN; ?></a>
                </div>
<?php } ?>
            </div>
        </div>
    </div>
</div>
