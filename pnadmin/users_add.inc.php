<?php
declare(strict_types=1);

/* PowerNews - PHP and MySQL based news script                         */
/* Copyright (c) 2001-2026 PowerScripts                                 */

/* MIT License - See LICENSE file for full license text                 */
/* https://github.com/schubertnico/PowerNews.git                        */

// Validierte Parameter
$add = pn_get_string('add', 10);

if ($pnadmin['canwriteusers'] == 'YES') {

    if ($add === 'YES') {
        $nickname = pn_post_string('nickname', 100);
        $email = pn_post_string('email', 250);
        $showemail = pn_validate_yesno($_POST['showemail'] ?? '');
        $sendemail = pn_validate_yesno($_POST['sendemail'] ?? '');

        if ($nickname === '' || $email === '') {
            ?>
            <div class="alert alert-danger" role="alert">
                <?php echo L_USR_NICKANDEMAIL; ?>
                <div class="mt-2"><a href="index.php?page=users&amp;subpage=add" class="btn btn-sm btn-outline-secondary">Zur&uuml;ck zum Formular</a></div>
            </div>
            <?php
        } else {
            $user = new user();
            $uerror = $user->adduser($nickname, $email, $showemail, $sendemail);

            if ($uerror !== '' && $uerror !== '0') {
                ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo pnadmin_escape($uerror); ?>
                    <div class="mt-2"><a href="index.php?page=users&amp;subpage=add" class="btn btn-sm btn-outline-secondary">Zur&uuml;ck zum Formular</a></div>
                </div>
                <?php
            } else {
                ?>
                <div class="alert alert-success" role="alert">
                    <?php echo L_USR_USRADDED; ?>
                    <div class="mt-2"><a href="index.php?page=users&amp;subpage=add" class="btn btn-sm btn-success">Weiteren Benutzer anlegen</a></div>
                </div>
                <?php
            }
        }

    } else {
        ?>
      <form action="index.php?page=users&amp;subpage=add&amp;add=YES" method="post" novalidate>
          <fieldset>
              <legend class="h6"><?php echo L_USR_ADDUSR; ?></legend>

              <div class="mb-3">
                  <label for="pn_nickname" class="form-label fw-bold"><?php echo L_USR_NICKNAME; ?></label>
                  <input class="form-control" name="nickname" id="pn_nickname" maxlength="100" required aria-describedby="pn_nickname_help">
                  <div id="pn_nickname_help" class="form-text"><?php echo L_USR_NICKNAME_DESC; ?></div>
              </div>

              <div class="mb-3">
                  <label for="pn_email" class="form-label fw-bold"><?php echo L_USR_EMAIL; ?></label>
                  <input type="email" class="form-control" name="email" id="pn_email" maxlength="250" required aria-describedby="pn_email_help">
                  <div id="pn_email_help" class="form-text"><?php echo L_USR_EMAIL_DESC; ?></div>
              </div>

              <div class="form-check mb-3">
                  <input class="form-check-input" type="checkbox" name="showemail" value="YES" id="pn_showemail" aria-describedby="pn_showemail_help">
                  <label class="form-check-label fw-bold" for="pn_showemail"><?php echo L_USR_SHOWEMAIL; ?></label>
                  <div id="pn_showemail_help" class="form-text"><?php echo L_USR_SHOWEMAIL_DESC; ?></div>
              </div>

              <div class="form-check mb-3">
                  <input class="form-check-input" type="checkbox" name="sendemail" value="YES" id="pn_sendemail" checked aria-describedby="pn_sendemail_help">
                  <label class="form-check-label fw-bold" for="pn_sendemail"><?php echo L_USR_SENDMAIL; ?></label>
                  <div id="pn_sendemail_help" class="form-text"><?php echo L_USR_SENDMAIL_DESC; ?></div>
              </div>

              <button type="submit" class="btn btn-primary"><?php echo L_USR_ADDUSR; ?></button>
          </fieldset>
      </form>
      <?php
    }

} else {
    ?><div class="alert alert-danger mb-0" role="alert"><?php echo L_ALL_ACCESSDENIED; ?></div><?php
}
?>
