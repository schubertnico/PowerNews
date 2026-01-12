<?php
declare(strict_types=1);

/* PowerNews - PHP and MySQL based news script                         */
/* Copyright (c) 2001-2024 PowerScripts                                 */

/* MIT License - See LICENSE file for full license text                 */
/* https://github.com/schubertnico/PowerNews.git                        */

// Validierte Parameter
$userid = pn_get_id('userid');
$edit = pn_get_string('edit', 10);

if ($pnadmin['canreadpermissions'] == 'YES' && $pnadmin['canwritepermissions'] == 'YES') {

    if ($userid === 0) {
        ?><center><a href="./page=permissions&subpage=show"><?php echo L_PERM_CHOOSEADMIN; ?></a></center><?php
    } else {
        $user = new user();
        $error = $user->checkuser($userid);
        $permissions = new permissions();

        if ($error !== '' && $error !== '0') {
            ?><center><a href="index.php?page=permissions&subpage=show"><?php echo pnadmin_escape($error); ?></a></center><?php
        } else {
            if ($edit === 'YES') {
                $delete = pn_validate_yesno($_POST['delete'] ?? '');
                $permsData = PermissionsData::fromPost();
                $error = $permissions->editpermissions($userid, $permsData, $delete);

                if ($error !== '' && $error !== '0') {
                    ?><center><a href="javascript:history.back()"><?php echo pnadmin_escape($error); ?></a></center><?php
                } else {
                    if ($delete === 'YES') {
                        ?><center><a href="index.php?page=permissions&subpage=show"><?php echo L_PERM_PERMISSIONSDELETED; ?></a></center><?php
                    } else {
                        ?><center><a href="index.php?page=permissions&subpage=show"><?php echo L_PERM_PERMISSIONSEDITED; ?></a></center><?php
                    }
                }
            } else {
                $data = $permissions->getdata($userid);
                ?>
          <center>
          <form action="index.php?page=permissions&subpage=edit&edit=YES&userid=<?php echo pn_int($userid); ?>" method="post">
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
            <input type="checkbox" name="canreadtemplates" value="YES" <?php if ($data['canreadtemplates'] == 'YES') { ?>checked<?php } ?>>
            </td><td align="center">
            <input type="checkbox" name="canwritetemplates" value="YES" <?php if ($data['canwritetemplates'] == 'YES') { ?>checked<?php } ?>>
            </td></tr>
            <tr><td>
            <?php echo L_PERM_CONFIG; ?>
            </td><td align="center">
            <input type="checkbox" name="canreadconfig" value="YES" <?php if ($data['canreadconfig'] == 'YES') { ?>checked<?php } ?>>
            </td><td align="center">
            <input type="checkbox" name="canwriteconfig" value="YES" <?php if ($data['canwriteconfig'] == 'YES') { ?>checked<?php } ?>>
            </td></tr>
            <tr><td>
            <?php echo L_PERM_USER; ?>
            </td><td align="center">
            <input type="checkbox" name="canreadusers" value="YES" <?php if ($data['canreadusers'] == 'YES') { ?>checked<?php } ?>>
            </td><td align="center">
            <input type="checkbox" name="canwriteusers" value="YES" <?php if ($data['canwriteusers'] == 'YES') { ?>checked<?php } ?>>
            </td></tr>
            <tr><td>
            <?php echo L_PERM_PERMISSIONS; ?>
            </td><td align="center">
            <input type="checkbox" name="canreadpermissions" value="YES" <?php if ($data['canreadpermissions'] == 'YES') { ?>checked<?php } ?>>
            </td><td align="center">
            <input type="checkbox" name="canwritepermissions" value="YES" <?php if ($data['canwritepermissions'] == 'YES') { ?>checked<?php } ?>>
            </td></tr>
            <tr><td>
            <?php echo L_PERM_CATS; ?>
            </td><td align="center">
            <input type="checkbox" name="canreadcategories" value="YES" <?php if ($data['canreadcategories'] == 'YES') { ?>checked<?php } ?>>
            </td><td align="center">
            <input type="checkbox" name="canwritecategories" value="YES" <?php if ($data['canwritecategories'] == 'YES') { ?>checked<?php } ?>>
            </td></tr>
            <tr><td>
            <?php echo L_PERM_NEWS; ?>
            </td><td align="center">
            <input type="checkbox" name="canreadnews" value="YES" <?php if ($data['canreadnews'] == 'YES') { ?>checked<?php } ?>>
            </td><td align="center">
            <input type="checkbox" name="canwritenews" value="YES" <?php if ($data['canwritenews'] == 'YES') { ?>checked<?php } ?>>
            </td></tr>
            <tr><td>
            <?php echo L_PERM_COMMENTS; ?>
            </td><td align="center">
            <input type="checkbox" name="canreadcomments" value="YES" <?php if ($data['canreadcomments'] == 'YES') { ?>checked<?php } ?>>
            </td><td align="center">
            <input type="checkbox" name="canwritecomments" value="YES" <?php if ($data['canwritecomments'] == 'YES') { ?>checked<?php } ?>>
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
