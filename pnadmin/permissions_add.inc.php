<?PHP
/************************************************************************/
/* PowerNews is a PHP and mySQL based newsscript - www.powerscripts.org */
/* Copyright (C) 2001-2023 PowerScripts                                 */
/*                                                                      */
/* This program is free software; you can redistribute it and/or modify */
/* it under the terms of the GNU General Public License as published by */
/* the Free Software Foundation; either version 2 of the License, or    */
/* (at your option) any later version.                                  */
/*                                                                      */
/* This program is distributed in the hope that it will be useful,      */
/* but WITHOUT ANY WARRANTY; without even the implied warranty of       */
/* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        */
/* GNU General Public License for more details.                         */
/*                                                                      */
/* You should have received a copy of the GNU General Public License    */
/* along with this program; if not, write to the Free Software          */
/* Foundation, Inc., 59 Temple Place, Suite 330, Boston,                */
/* MA  02111-1307  USA                                                  */
/************************************************************************/

  if ($pnadmin['canwritepermissions'] == "YES") {

    if (isset($_GET['add']) && $_GET['add'] == "YES") {
      if (!$_POST['user']) {
        ?><center><a href="javascript:history.back()"><?PHP echo L_PERM_INSERTNICK; ?></a></center><?PHP
      } else {
        $permissions = new permissions;

        $error = $permissions->addpermissions($_POST['user'], $_POST['canreadtemplates'], $_POST['canwritetemplates'], $_POST['canreadconfig'], $_POST['canwriteconfig'], $_POST['canreadusers'], $_POST['canwriteusers'], $_POST['canreadpermissions'], $_POST['canwritepermissions'], $_POST['canreadcategories'], $_POST['canwritecategories'], $_POST['canreadnews'], $_POST['canwritenews'], $_POST['canreadcomments'], $_POST['canwritecomments']);
        if ($error !== '' && $error !== '0') {
          ?><center><a href="javascript:history.back()"><?PHP echo $error; ?></a></center><?PHP
        } else {
          ?><center><a href="index.php?page=permissions&subpage=show"><?PHP echo L_PERM_PERMISSIONADDED; ?></a></center><?PHP
        }

      }
    } else {
      ?>
      <center>
      <form action="index.php?page=permissions&subpage=add&add=YES" method="post">
      <table border="0" cellpadding="4" cellspacing="0">
      <tr><td colspan="2" align="center">
      <b><?PHP echo L_PERM_ADDPERMISSIONS; ?></b>
      </td></tr>
      <tr><td>
      <b><?PHP echo L_PERM_NICK; ?></b><br>
      <small class="info"><?PHP echo L_PERM_NICK_DESC; ?></small>
      </td><td>
      <input name="user" size="25" maxlength="100">
      </td></tr>
      <tr><td valign="top">
      <b><?PHP echo L_PERM_PERMISSIONS; ?></b><br>
      <small class="info"><?PHP echo L_PERM_PERMISSIONS; ?></small>
      </td><td valign="top">
        <table border="0" cellpadding="3" cellspacing="0">
        <tr><td width="150">
        <b><?PHP echo L_PERM_SECTION; ?></b>
        </td><td width="50" align="center">
        <b><?PHP echo L_PERM_READ; ?></b>
        </td><td width="75" align="center">
        <b><?PHP echo L_PERM_WRITE; ?></b>
        </td></tr>
        <tr><td>
        <?PHP echo L_PERM_TEMPLATES; ?>
        </td><td align="center">
        <input type="checkbox" name="canreadtemplates" value="YES">
        </td><td align="center">
        <input type="checkbox" name="canwritetemplates" value="YES">
        </td></tr>
        <tr><td>
        <?PHP echo L_PERM_CONFIG; ?>
        </td><td align="center">
        <input type="checkbox" name="canreadconfig" value="YES">
        </td><td align="center">
        <input type="checkbox" name="canwriteconfig" value="YES">
        </td></tr>
        <tr><td>
        <?PHP echo L_PERM_USER; ?>
        </td><td align="center">
        <input type="checkbox" name="canreadusers" value="YES">
        </td><td align="center">
        <input type="checkbox" name="canwriteusers" value="YES">
        </td></tr>
        <tr><td>
        <?PHP echo L_PERM_PERMISSIONS; ?>
        </td><td align="center">
        <input type="checkbox" name="canreadpermissions" value="YES">
        </td><td align="center">
        <input type="checkbox" name="canwritepermissions" value="YES">
        </td></tr>
        <tr><td>
        <?PHP echo L_PERM_CATS; ?>
        </td><td align="center">
        <input type="checkbox" name="canreadcategories" value="YES">
        </td><td align="center">
        <input type="checkbox" name="canwritecategories" value="YES">
        </td></tr>
        <tr><td>
        <?PHP echo L_PERM_NEWS; ?>
        </td><td align="center">
        <input type="checkbox" name="canreadnews" value="YES" checked>
        </td><td align="center">
        <input type="checkbox" name="canwritenews" value="YES" checked>
        </td></tr>
        <tr><td>
        <?PHP echo L_PERM_COMMENTS; ?>
        </td><td align="center">
        <input type="checkbox" name="canreadcomments" value="YES" checked>
        </td><td align="center">
        <input type="checkbox" name="canwritecomments" value="YES" checked>
        </td></tr>
        </table>
      </td></tr>
      <tr><td colspan="2" align="center">
      <input type="submit" value="<?PHP echo L_PERM_ADDPERMISSIONS; ?>">
      </td></tr>
      </table>
      </form>
      </center>
      <?PHP
    }

  } else {
    ?><center><?PHP echo L_ALL_ACCESSDENIED; ?></center><?PHP
  }
?>
