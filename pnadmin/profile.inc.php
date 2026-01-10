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
?>
<tr><td bgcolor="#3F5070" align="center"><b>

       <b class="headline"><?PHP echo L_TITLE_PROFILE; ?></b>

</b></td></tr>

</td><td bgcolor="#001F3F" valign="top">

<?PHP
  $profile = new profile;
  if ($_GET['edit'] == "YES") {
    if (!trim((string) $_POST['nickname']) || !trim((string) $_POST['email']) || !trim((string) $_GET['password']) || !trim((string) $_GET['password2'])) {
      ?><center><a href="javascript:history.back()">Es m&uuml;ssen alle Felder ausgef&uuml;llt werden</a></center><?PHP
    } else {
      $error = $profile->edit($_GET['nickname'], $_GET['email'], $_GET['showemail'], $_GET['password'], $_GET['password2'], $pnuser['id']);
      if ($error !== '' && $error !== '0') {
        ?><center><a href="javascript:history.back()"><?PHP echo $error; ?></a></center><?PHP
      } else {
        ?><center><a href="index.php?page=profile"><?PHP echo L_USR_PROFILEEDITED; ?></a></center><?PHP
      }
    }
  } else {
    $data = $profile->getdata($pnuser['id']);

    $showemail = '';
    if ($data['showemail'] == "YES") { $showemail = "checked"; }
    ?>
    <center>
    <form action="index.php?page=profile&edit=YES" method="post">
    <table border="0" cellpadding="4" cellspacing="0">
    <tr><td colspan="2" align="center">
    <b><?PHP echo L_USR_EDITPROFILE; ?></b>
    </td></tr>
    <tr><td>
    <b><?PHP echo L_USR_NICKNAME; ?></b><br>
    <small class="info"><?PHP echo L_USR_NICKNAME_DESC_PROF; ?></small>
    </td><td>
    <input name="nickname" size="25" maxlength="100" value="<?PHP echo $data['nickname']; ?>">
    </td></tr>
    <tr><td>
    <b><?PHP echo L_USR_EMAIL; ?></b><br>
    <small class="info"><?PHP echo L_USR_EMAIL_DESC_PROF; ?></small>
    </td><td>
    <input name="email" size="25" maxlength="250" value="<?PHP echo $data['email']; ?>">
    </td></tr>
    <tr><td>
    <b><?PHP echo L_USR_SHOWEMAIL; ?></b><br>
    <small class="info"><?PHP echo L_USR_SHOWEMAIL_DESC_PROF; ?></small>
    </td><td>
    <input type="checkbox" name="showemail" value="YES" <?PHP echo $showemail; ?>>
    </td></tr>
    <tr><td valign="top">
    <b><?PHP echo L_USR_PASSWORD; ?></b><br>
    <small class="info"><?PHP echo L_USR_PASSWORD_DESC_PROF; ?></small>
    </td><td>
    <input type="password" name="password" value="<?PHP echo base64_decode((string) $data['password']); ?>" size="25" maxlength="25"><br>
    <input type="password" name="password2" value="<?PHP echo base64_decode((string) $data['password']); ?>" size="25" maxlength="25">
    </td></tr>
    <tr><td colspan="2" align="center">
    <input type="submit" value="<?PHP echo L_USR_EDITPROFILE; ?>"> <input type="reset" value="<?PHP echo L_ALL_RESETDATA; ?>">
    </td></tr>
    </table>
    </form>
    </center>
    <br>
    <?PHP echo L_USR_PROFILE_DESC; ?>
    <?PHP
  }
?>

</td></tr>
