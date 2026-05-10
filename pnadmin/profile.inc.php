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
    <h1 class="card-header h5 mb-0"><?php echo L_TITLE_PROFILE; ?></h1>
    <div class="card-body">
<?php
  $profile = new profile();

if (isset($_GET['edit']) && $_GET['edit'] == 'YES') {
    $nickname = trim((string) ($_POST['nickname'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $showemail = $_POST['showemail'] ?? '';
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    if (!$nickname || !$email) {
        ?>
        <div class="alert alert-warning" role="alert">
            Nickname und Email m&uuml;ssen ausgef&uuml;llt werden.
            <div class="mt-2"><a href="index.php?page=profile" class="btn btn-sm btn-outline-secondary">Zur&uuml;ck zum Formular</a></div>
        </div>
        <?php
    } else {
        $error = $profile->edit($nickname, $email, $showemail, $password, $password2, $pnuser['id']);

        if ($error !== '' && $error !== '0') {
            ?>
            <div class="alert alert-danger" role="alert">
                <?php echo pnadmin_escape($error); ?>
                <div class="mt-2"><a href="index.php?page=profile" class="btn btn-sm btn-outline-secondary">Zur&uuml;ck zum Formular</a></div>
            </div>
            <?php
        } else {
            ?>
            <div class="alert alert-success" role="alert">
                <?php echo L_USR_PROFILEEDITED; ?>
                <div class="mt-2"><a href="index.php?page=profile" class="btn btn-sm btn-success">Erneut bearbeiten</a></div>
            </div>
            <?php
        }
    }
} else {
    $data = $profile->getdata($pnuser['id']);

    $showemail = '';

    if ($data['showemail'] == 'YES') {
        $showemail = 'checked';
    }
    ?>
    <form action="index.php?page=profile&amp;edit=YES" method="post" novalidate>
        <fieldset>
            <legend class="h6"><?php echo L_USR_EDITPROFILE; ?></legend>

            <div class="mb-3">
                <label for="pn_nickname" class="form-label fw-bold"><?php echo L_USR_NICKNAME; ?></label>
                <input class="form-control" name="nickname" id="pn_nickname" maxlength="100" value="<?php echo htmlspecialchars($data['nickname'], ENT_QUOTES, 'UTF-8'); ?>" required aria-describedby="pn_nickname_help">
                <div id="pn_nickname_help" class="form-text"><?php echo L_USR_NICKNAME_DESC_PROF; ?></div>
            </div>

            <div class="mb-3">
                <label for="pn_email" class="form-label fw-bold"><?php echo L_USR_EMAIL; ?></label>
                <input type="email" class="form-control" name="email" id="pn_email" maxlength="250" value="<?php echo htmlspecialchars($data['email'], ENT_QUOTES, 'UTF-8'); ?>" required aria-describedby="pn_email_help">
                <div id="pn_email_help" class="form-text"><?php echo L_USR_EMAIL_DESC_PROF; ?></div>
            </div>

            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="showemail" value="YES" id="pn_showemail" <?php echo $showemail; ?> aria-describedby="pn_showemail_help">
                <label class="form-check-label fw-bold" for="pn_showemail"><?php echo L_USR_SHOWEMAIL; ?></label>
                <div id="pn_showemail_help" class="form-text"><?php echo L_USR_SHOWEMAIL_DESC_PROF; ?></div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold" for="pn_password"><?php echo L_USR_PASSWORD; ?></label>
                <div class="row g-2">
                    <div class="col-12 col-md-6">
                        <input type="password" class="form-control" name="password" id="pn_password" maxlength="25" value="" autocomplete="new-password" placeholder="Neues Passwort (leer = keine &Auml;nderung)">
                    </div>
                    <div class="col-12 col-md-6">
                        <input type="password" class="form-control" name="password2" id="pn_password2" maxlength="25" value="" autocomplete="new-password" placeholder="Passwort wiederholen">
                    </div>
                </div>
                <div class="form-text"><?php echo L_USR_PASSWORD_DESC_PROF; ?></div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><?php echo L_USR_EDITPROFILE; ?></button>
                <button type="reset" class="btn btn-outline-secondary"><?php echo L_ALL_RESETDATA; ?></button>
            </div>
        </fieldset>
    </form>
    <p class="pn-help mt-3 mb-0"><?php echo L_USR_PROFILE_DESC; ?></p>
    <?php
}
?>
    </div>
</div>
