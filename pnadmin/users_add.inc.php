<?php
declare(strict_types=1);

/* PowerNews - PHP and MySQL based news script                         */
/* Copyright (c) 2001-2024 PowerScripts                                 */

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
            ?><center><a href="javascript:history.back()"><?php echo L_USR_NICKANDEMAIL; ?></a></center><?php
        } else {
            $user = new user();
            $uerror = $user->adduser($nickname, $email, $showemail, $sendemail);

            if ($uerror !== '' && $uerror !== '0') {
                ?><center><a href="javascript:history.back()"><?php echo pnadmin_escape($uerror); ?></a></center><?php
            } else {
                ?><center><a href="index.php?page=users&subpage=add"><?php echo L_USR_USRADDED; ?></a></center><?php
            }
        }

    } else {
        ?><br>
      <center>
      <form action="index.php?page=users&subpage=add&add=YES" method="post">
      <table border="0" cellpadding="4" cellspacing="0">
      <tr><td colspan="2" align="center">
      <b><?php echo L_USR_ADDUSR; ?></b>
      </td></tr>
      <tr><td>
      <b><?php echo L_USR_NICKNAME; ?></b><br>
      <small class="info"><?php echo L_USR_NICKNAME_DESC; ?></small>
      </td><td>
      <input name="nickname" size="25" maxlength="100">
      </td></tr>
      <tr><td>
      <b><?php echo L_USR_EMAIL; ?></b><br>
      <small class="info"><?php echo L_USR_EMAIL_DESC; ?></small>
      </td><td>
      <input name="email" size="25" maxlength="250">
      </td></tr>
      <tr><td>
      <b><?php echo L_USR_SHOWEMAIL; ?></b><br>
      <small class="info"><?php echo L_USR_SHOWEMAIL_DESC; ?></small>
      </td><td>
      <input type="checkbox" name="showemail" value="YES">
      </td></tr>
      <tr><td>
      <b><?php echo L_USR_SENDMAIL; ?></b><br>
      <small class="info"><?php echo L_USR_SENDMAIL_DESC; ?></small>
      </td><td>
      <input type="checkbox" name="sendemail" value="YES" checked>
      </td></tr>
      <tr><td colspan="2" align="center">
      <input type="submit" value="<?php echo L_USR_ADDUSR; ?>">
      </td></tr>
      </table>
      </form>
      </center>
      <?php
    }

} else {
    ?><center><?php echo L_ALL_ACCESSDENIED; ?></center><?php
}
?>
