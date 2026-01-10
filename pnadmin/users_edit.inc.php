<?php
declare(strict_types=1);
/************************************************************************/
/* PowerNews - PHP and MySQL based news script                         */
/* Copyright (c) 2001-2024 PowerScripts                                 */
/*                                                                      */
/* MIT License - See LICENSE file for full license text                 */
/* https://github.com/schubertnico/PowerNews.git                        */
/************************************************************************/

  if ($pnadmin['canreadusers'] == "YES" && $pnadmin['canwriteusers'] == "YES") {
    if ($_GET['userid']) {
      $edituser = new user;
      $error = $edituser->checkuser($_GET['userid']);
      if ($error !== '' && $error !== '0') {
        ?><center><a href="index.php?page=users&subpage=show"><?php echo pnadmin_escape($error); ?></a></center><?php
      } else {
        if (isset($_GET['edit']) && $_GET['edit'] == "YES") {
          $error = $edituser->edituser($_POST['nickname'], $_POST['email'], $_POST['showemail'], $_POST['newpassword'], $_POST['status'], $_POST['sendemail'], $_GET['userid'], $_POST['password']);
          if ($error !== '' && $error !== '0') {
            ?><center><a href="javascript:history.back()"><?php echo pnadmin_escape($error); ?></a></center><?php
          } else {
            ?><center><a href="index.php?page=users&subpage=show"><?php echo L_USR_USREDITED; ?></a></center><?php
          }
        } else {
          $data = $edituser->getuserdata($_GET['userid']);
          if ($data['showemail'] == "YES") {
            $showemail = "checked";
          }
          ?>
          <center>
          <form action="index.php?page=users&subpage=edit&edit=YES&userid=<?php echo pnadmin_escape($_GET['userid']); ?>" method="post">
          <table border="0" cellpadding="4" cellspacing="0">
          <tr><td colspan="2" align="center">
          <b><?php echo L_USR_EDITUSR; ?></b>
          </td></tr>
          <tr><td>
          <b><?php echo L_USR_NICKNAME; ?></b><br>
          <small class="info"><?php echo L_USR_NICKNAME_DESC; ?></small>
          </td><td>
          <input name="nickname" size="25" maxlength="100" value="<?php echo pnadmin_escape($data['nickname']); ?>">
          </td></tr>
          <tr><td>
          <b><?php echo L_USR_EMAIL; ?></b><br>
          <small class="info"><?php echo L_USR_EMAIL_DESC; ?></small>
          </td><td>
          <input name="email" size="25" maxlength="250" value="<?php echo pnadmin_escape($data['email']); ?>">
          </td></tr>
          <tr><td>
          <b><?php echo L_USR_SHOWEMAIL; ?></b><br>
          <small class="info"><?php echo L_USR_SHOWEMAIL_DESC; ?></small>
          </td><td>
          <input type="checkbox" name="showemail" value="YES" <?php echo ((isset($showemail) && trim($showemail) !== '') ? trim($showemail):''); ?>>
          </td></tr>
          <tr><td>
          <b><?php echo L_USR_NEWPW; ?></b><br>
          <small class="info"><?php echo L_USR_NEWPW_DESC; ?></small>
          </td><td>
          <input type="checkbox" name="newpassword" value="YES">
          </td></tr>
          <tr><td>
          <b><?php echo L_USR_STATUS; ?></b><br>
          <small class="info"><?php echo L_USR_STATUS_DESC; ?></small>
          </td><td>
          <select name="status" size="1">
            <option value="Activated" <?php if ($data['status'] == "Activated") { echo "selected"; } ?>><?php echo L_ALL_ACTIVATED; ?>
            <option value="Deactivated" <?php if ($data['status'] == "Deactivated") { echo "selected"; } ?>><?php echo L_ALL_DEACTIVATED; ?>
          </select>
          </td></tr>
          <tr><td>
          <b><?php echo L_USR_SENDMAIL; ?></b><br>
          <small class="info"><?php echo L_USR_SENDMAIL; ?></small>
          </td><td>
          <input type="checkbox" name="sendemail" value="YES" checked>
          </td></tr>
          <tr><td colspan="2" align="center">
          <input type="submit" value="<?php echo L_USR_EDITUSR; ?>"> <input type="reset" value="<?php echo L_ALL_RESETDATA; ?>">
          </td></tr>
          </table>
          </form>
          </center>
          <?php
        }
      }
    } else {
      ?><center><a href="index.php?page=users&subpage=show"><?php echo L_USR_CHOOSEUSER; ?></a></center><?php
    }
  } else {
    ?><center><?php echo L_ALL_ACCESSDENIED; ?></center><?php
  }
?>
