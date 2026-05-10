<?php
declare(strict_types=1);

/* PowerNews - PHP and MySQL based news script                         */
/* Copyright (c) 2001-2026 PowerScripts                                 */

/* MIT License - See LICENSE file for full license text                 */
/* https://github.com/schubertnico/PowerNews.git                        */

// Validierte Parameter
$userid = pn_get_id('userid');
$edit = pn_get_string('edit', 10);

if ($pnadmin['canreadusers'] == 'YES' && $pnadmin['canwriteusers'] == 'YES') {
    if ($userid > 0) {
        $edituser = new user();
        $error = $edituser->checkuser($userid);

        if ($error !== '' && $error !== '0') {
            ?>
            <div class="alert alert-danger" role="alert">
                <?php echo pnadmin_escape($error); ?>
                <div class="mt-2"><a href="index.php?page=users&amp;subpage=show" class="btn btn-sm btn-outline-secondary">Zur&uuml;ck zur Liste</a></div>
            </div>
            <?php
        } else {
            if ($edit === 'YES') {
                $error = $edituser->edituser(
                    pn_post_string('nickname', 100),
                    pn_post_string('email', 250),
                    pn_validate_yesno($_POST['showemail'] ?? ''),
                    pn_validate_yesno($_POST['newpassword'] ?? ''),
                    pn_validate_status($_POST['status'] ?? ''),
                    pn_validate_yesno($_POST['sendemail'] ?? ''),
                    $userid,
                    pn_post_string('password', 100),
                );

                if ($error !== '' && $error !== '0') {
                    ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo pnadmin_escape($error); ?>
                        <div class="mt-2"><a href="index.php?page=users&amp;subpage=edit&amp;userid=<?php echo pn_int($userid); ?>" class="btn btn-sm btn-outline-secondary">Zur&uuml;ck zum Formular</a></div>
                    </div>
                    <?php
                } else {
                    ?>
                    <div class="alert alert-success" role="alert">
                        <?php echo L_USR_USREDITED; ?>
                        <div class="mt-2"><a href="index.php?page=users&amp;subpage=show" class="btn btn-sm btn-success">Zur&uuml;ck zur Liste</a></div>
                    </div>
                    <?php
                }
            } else {
                $data = $edituser->getuserdata($userid);

                if ($data['showemail'] == 'YES') {
                    $showemail = 'checked';
                }
                ?>
          <form action="index.php?page=users&amp;subpage=edit&amp;edit=YES&amp;userid=<?php echo pn_int($userid); ?>" method="post" novalidate>
              <fieldset>
                  <legend class="h6"><?php echo L_USR_EDITUSR; ?></legend>

                  <div class="mb-3">
                      <label for="pn_nickname" class="form-label fw-bold"><?php echo L_USR_NICKNAME; ?></label>
                      <input class="form-control" name="nickname" id="pn_nickname" maxlength="100" value="<?php echo pnadmin_escape($data['nickname']); ?>" required aria-describedby="pn_nickname_help">
                      <div id="pn_nickname_help" class="form-text"><?php echo L_USR_NICKNAME_DESC; ?></div>
                  </div>

                  <div class="mb-3">
                      <label for="pn_email" class="form-label fw-bold"><?php echo L_USR_EMAIL; ?></label>
                      <input type="email" class="form-control" name="email" id="pn_email" maxlength="250" value="<?php echo pnadmin_escape($data['email']); ?>" required aria-describedby="pn_email_help">
                      <div id="pn_email_help" class="form-text"><?php echo L_USR_EMAIL_DESC; ?></div>
                  </div>

                  <div class="form-check mb-3">
                      <input class="form-check-input" type="checkbox" name="showemail" value="YES" id="pn_showemail" <?php echo (isset($showemail) && trim($showemail) !== '') ? trim($showemail) : ''; ?> aria-describedby="pn_showemail_help">
                      <label class="form-check-label fw-bold" for="pn_showemail"><?php echo L_USR_SHOWEMAIL; ?></label>
                      <div id="pn_showemail_help" class="form-text"><?php echo L_USR_SHOWEMAIL_DESC; ?></div>
                  </div>

                  <div class="form-check mb-3">
                      <input class="form-check-input" type="checkbox" name="newpassword" value="YES" id="pn_newpassword" aria-describedby="pn_newpassword_help">
                      <label class="form-check-label fw-bold" for="pn_newpassword"><?php echo L_USR_NEWPW; ?></label>
                      <div id="pn_newpassword_help" class="form-text"><?php echo L_USR_NEWPW_DESC; ?></div>
                  </div>

                  <div class="mb-3">
                      <label for="pn_status" class="form-label fw-bold"><?php echo L_USR_STATUS; ?></label>
                      <select class="form-select" name="status" id="pn_status" aria-describedby="pn_status_help">
                          <option value="Activated" <?php if ($data['status'] == 'Activated') {
                              echo 'selected';
                          } ?>><?php echo L_ALL_ACTIVATED; ?></option>
                          <option value="Deactivated" <?php if ($data['status'] == 'Deactivated') {
                              echo 'selected';
                          } ?>><?php echo L_ALL_DEACTIVATED; ?></option>
                      </select>
                      <div id="pn_status_help" class="form-text"><?php echo L_USR_STATUS_DESC; ?></div>
                  </div>

                  <div class="form-check mb-3">
                      <input class="form-check-input" type="checkbox" name="sendemail" value="YES" id="pn_sendemail" checked>
                      <label class="form-check-label fw-bold" for="pn_sendemail"><?php echo L_USR_SENDMAIL; ?></label>
                  </div>

                  <div class="d-flex gap-2">
                      <button type="submit" class="btn btn-primary"><?php echo L_USR_EDITUSR; ?></button>
                      <button type="reset" class="btn btn-outline-secondary"><?php echo L_ALL_RESETDATA; ?></button>
                  </div>
              </fieldset>
          </form>
          <?php
            }
        }
    } else {
        ?>
        <div class="alert alert-info" role="alert">
            <?php echo L_USR_CHOOSEUSER; ?>
            <div class="mt-2"><a href="index.php?page=users&amp;subpage=show" class="btn btn-sm btn-primary">Zur&uuml;ck zur Liste</a></div>
        </div>
        <?php
    }
} else {
    ?><div class="alert alert-danger mb-0" role="alert"><?php echo L_ALL_ACCESSDENIED; ?></div><?php
}
?>
