<?php
declare(strict_types=1);
/************************************************************************/
/* PowerNews - PHP and MySQL based news script                         */
/* Copyright (c) 2001-2024 PowerScripts                                 */
/*                                                                      */
/* MIT License - See LICENSE file for full license text                 */
/* https://github.com/schubertnico/PowerNews.git                        */
/************************************************************************/

  if ($pnadmin['canreadpermissions'] == "YES" && $pnadmin['canwritepermissions'] == "YES") {

    if (!$_GET['userid']) {
      ?><center><a href="./page=permissions&subpage=show"><?php echo L_PERM_CHOOSEADMIN; ?></a></center><?php
    } else {
      $user = new user;
      $error = $user->checkuser($_GET['userid']);
      $permissions = new permissions;
      if ($error !== '' && $error !== '0') {
        ?><center><a href="index.php?page=permissions&subpage=show"><?php echo pnadmin_escape($error); ?></a></center><?php
      } else {
        if (isset($_GET['edit']) && $_GET['edit'] == "YES") {
          $error = $permissions->editpermissions(((isset($_GET['userid']) && trim((string) $_GET['userid']) !== '') ? intval($_GET['userid']):0),
            ((isset($_POST['canreadtemplates']) && trim((string) $_POST['canreadtemplates']) !== '') ? trim((string) $_POST['canreadtemplates']):'NO'),
            ((isset($_POST['canwritetemplates']) && trim((string) $_POST['canwritetemplates']) !== '') ? trim((string) $_POST['canwritetemplates']):'NO'),
            ((isset($_POST['canreadconfig']) && trim((string) $_POST['canreadconfig']) !== '') ? trim((string) $_POST['canreadconfig']):'NO'),
            ((isset($_POST['canwriteconfig']) && trim((string) $_POST['canwriteconfig']) !== '') ? trim((string) $_POST['canwriteconfig']):'NO'),
            ((isset($_POST['canreadusers']) && trim((string) $_POST['canreadusers']) !== '') ? trim((string) $_POST['canreadusers']):'NO'),
            ((isset($_POST['canwriteusers']) && trim((string) $_POST['canwriteusers']) !== '') ? trim((string) $_POST['canwriteusers']):'NO'),
            ((isset($_POST['canreadpermissions']) && trim((string) $_POST['canreadpermissions']) !== '') ? trim((string) $_POST['canreadpermissions']):'NO'),
            ((isset($_POST['canwritepermissions']) && trim((string) $_POST['canwritepermissions']) !== '') ? trim((string) $_POST['canwritepermissions']):'NO'),
            ((isset($_POST['canreadcategories']) && trim((string) $_POST['canreadcategories']) !== '') ? trim((string) $_POST['canreadcategories']):'NO'),
            ((isset($_POST['canwritecategories']) && trim((string) $_POST['canwritecategories']) !== '') ? trim((string) $_POST['canwritecategories']):'NO'),
            ((isset($_POST['canreadnews']) && trim((string) $_POST['canreadnews']) !== '') ? trim((string) $_POST['canreadnews']):'NO'),
            ((isset($_POST['canwritenews']) && trim((string) $_POST['canwritenews']) !== '') ? trim((string) $_POST['canwritenews']):'NO'),
            ((isset($_POST['canreadcomments']) && trim((string) $_POST['canreadcomments']) !== '') ? trim((string) $_POST['canreadcomments']):'NO'),
            ((isset($_POST['canwritecomments']) && trim((string) $_POST['canwritecomments']) !== '') ? trim((string) $_POST['canwritecomments']):'NO'),
            ((isset($_POST['delete']) && trim((string) $_POST['delete']) !== '') ? trim((string) $_POST['delete']):'')
          );
          if ($error !== '' && $error !== '0') {
            ?><center><a href="javascript:history.back()"><?php echo pnadmin_escape($error); ?></a></center><?php
          } else {
            if (isset($_POST['delete']) && $_POST['delete'] == "YES") {
              ?><center><a href="index.php?page=permissions&subpage=show"><?php echo L_PERM_PERMISSIONSDELETED; ?></a></center><?php
            } else {
              ?><center><a href="index.php?page=permissions&subpage=show"><?php echo L_PERM_PERMISSIONSEDITED; ?></a></center><?php
            }
          }
        } else {
          $data = $permissions->getdata($_GET['userid']);
          ?>
          <center>
          <form action="index.php?page=permissions&subpage=edit&edit=YES&userid=<?php echo pnadmin_escape($_GET['userid']); ?>" method="post">
          <table border="0" cellpadding="4" cellspacing="0">
          <tr><td colspan="2" align="center">
          <b><?php echo L_PERM_EDITPERMISSIONS; ?></b>
          </td></tr>
          <tr><td>
          <b><?php echo L_PERM_DELETE; ?></b><br>
          <small class="info"><?php echo L_PERM_DELETE_DESC; ?></small>
          </td><td>
          <input type="checkbox" name="delete" value="YES">
          </td></tr>
          <tr><td>
          <b><?php echo L_PERM_NICK; ?></b><br>
          <small class="info"><?php echo L_PERM_NICK_DESC; ?></small>
          </td><td>
          <?php echo pnadmin_escape($data['nickname']); ?>
          </td></tr>
          <tr><td valign="top">
          <b><?php echo L_PERM_PERMISSIONS; ?></b><br>
          <small class="info"><?php echo L_PERM_PERMISSIONS_DESC; ?></small>
          </td><td valign="top">
            <table border="0" cellpadding="3" cellspacing="0">
            <tr><td width="150">
            <b><?php echo L_PERM_SECTION; ?></b>
            </td><td width="50" align="center">
            <b><?php echo L_PERM_READ; ?></b>
            </td><td width="75" align="center">
            <b><?php echo L_PERM_WRITE; ?></b>
            </td></tr>
            <tr><td>
            <?php echo L_PERM_TEMPLATES; ?>
            </td><td align="center">
            <input type="checkbox" name="canreadtemplates" value="YES" <?php if ($data['canreadtemplates'] == "YES") { ?>checked<?php } ?>>
            </td><td align="center">
            <input type="checkbox" name="canwritetemplates" value="YES" <?php if ($data['canwritetemplates'] == "YES") { ?>checked<?php } ?>>
            </td></tr>
            <tr><td>
            <?php echo L_PERM_CONFIG; ?>
            </td><td align="center">
            <input type="checkbox" name="canreadconfig" value="YES" <?php if ($data['canreadconfig'] == "YES") { ?>checked<?php } ?>>
            </td><td align="center">
            <input type="checkbox" name="canwriteconfig" value="YES" <?php if ($data['canwriteconfig'] == "YES") { ?>checked<?php } ?>>
            </td></tr>
            <tr><td>
            <?php echo L_PERM_USER; ?>
            </td><td align="center">
            <input type="checkbox" name="canreadusers" value="YES" <?php if ($data['canreadusers'] == "YES") { ?>checked<?php } ?>>
            </td><td align="center">
            <input type="checkbox" name="canwriteusers" value="YES" <?php if ($data['canwriteusers'] == "YES") { ?>checked<?php } ?>>
            </td></tr>
            <tr><td>
            <?php echo L_PERM_PERMISSIONS; ?>
            </td><td align="center">
            <input type="checkbox" name="canreadpermissions" value="YES" <?php if ($data['canreadpermissions'] == "YES") { ?>checked<?php } ?>>
            </td><td align="center">
            <input type="checkbox" name="canwritepermissions" value="YES" <?php if ($data['canwritepermissions'] == "YES") { ?>checked<?php } ?>>
            </td></tr>
            <tr><td>
            <?php echo L_PERM_CATS; ?>
            </td><td align="center">
            <input type="checkbox" name="canreadcategories" value="YES" <?php if ($data['canreadcategories'] == "YES") { ?>checked<?php } ?>>
            </td><td align="center">
            <input type="checkbox" name="canwritecategories" value="YES" <?php if ($data['canwritecategories'] == "YES") { ?>checked<?php } ?>>
            </td></tr>
            <tr><td>
            <?php echo L_PERM_NEWS; ?>
            </td><td align="center">
            <input type="checkbox" name="canreadnews" value="YES" <?php if ($data['canreadnews'] == "YES") { ?>checked<?php } ?>>
            </td><td align="center">
            <input type="checkbox" name="canwritenews" value="YES" <?php if ($data['canwritenews'] == "YES") { ?>checked<?php } ?>>
            </td></tr>
            <tr><td>
            <?php echo L_PERM_COMMENTS; ?>
            </td><td align="center">
            <input type="checkbox" name="canreadcomments" value="YES" <?php if ($data['canreadcomments'] == "YES") { ?>checked<?php } ?>>
            </td><td align="center">
            <input type="checkbox" name="canwritecomments" value="YES" <?php if ($data['canwritecomments'] == "YES") { ?>checked<?php } ?>>
            </td></tr>
            </table>
          </td></tr>
          <tr><td colspan="2" align="center">
          <input type="submit" value="<?php echo L_PERM_EDITPERMISSIONS; ?>"> <input type="reset" value="<?php echo L_ALL_RESETDATA; ?>">
          </td></tr>
          </table>
          </form>
          </center>
          <?php
        }
      }
    }

  } else {
    ?><center><?php echo L_ALL_ACCESSDENIED; ?></center><?php
  }
?>
