<?php

/* PowerNews is a PHP and mySQL based newsscript - www.powerscripts.org */
/* Copyright (C) 2001-2023 PowerScripts                                 */

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
<tr><td bgcolor="#3F5070" align="center"><b>

       <b class="headline"><?php echo L_TITLE_PROFILE; ?></b>

</b></td></tr>

</td><td bgcolor="#001F3F" valign="top">

<?php
  $profile = new profile();

if (isset($_GET['edit']) && $_GET['edit'] == 'YES') {
    $nickname = trim((string) ($_POST['nickname'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $showemail = $_POST['showemail'] ?? '';
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    if (!$nickname || !$email) {
        ?><center><a href="javascript:history.back()">Nickname und Email m&uuml;ssen ausgef&uuml;llt werden</a></center><?php
    } else {
        $error = $profile->edit($nickname, $email, $showemail, $password, $password2, $pnuser['id']);

        if ($error !== '' && $error !== '0') {
            ?><center><a href="javascript:history.back()"><?php echo $error; ?></a></center><?php
        } else {
            ?><center><a href="index.php?page=profile"><?php echo L_USR_PROFILEEDITED; ?></a></center><?php
        }
    }
} else {
    $data = $profile->getdata($pnuser['id']);

    $showemail = '';

    if ($data['showemail'] == 'YES') {
        $showemail = 'checked';
    }
    ?>
    <center>
    <form action="index.php?page=profile&edit=YES" method="post">
    <table border="0" cellpadding="4" cellspacing="0">
    <tr><td colspan="2" align="center">
    <b><?php echo L_USR_EDITPROFILE; ?></b>
    </td></tr>
    <tr><td>
    <b><?php echo L_USR_NICKNAME; ?></b><br>
    <small class="info"><?php echo L_USR_NICKNAME_DESC_PROF; ?></small>
    </td><td>
    <input name="nickname" size="25" maxlength="100" value="<?php echo htmlspecialchars($data['nickname'], ENT_QUOTES, 'UTF-8'); ?>">
    </td></tr>
    <tr><td>
    <b><?php echo L_USR_EMAIL; ?></b><br>
    <small class="info"><?php echo L_USR_EMAIL_DESC_PROF; ?></small>
    </td><td>
    <input name="email" size="25" maxlength="250" value="<?php echo htmlspecialchars($data['email'], ENT_QUOTES, 'UTF-8'); ?>">
    </td></tr>
    <tr><td>
    <b><?php echo L_USR_SHOWEMAIL; ?></b><br>
    <small class="info"><?php echo L_USR_SHOWEMAIL_DESC_PROF; ?></small>
    </td><td>
    <input type="checkbox" name="showemail" value="YES" <?php echo $showemail; ?>>
    </td></tr>
    <tr><td valign="top">
    <b><?php echo L_USR_PASSWORD; ?></b><br>
    <small class="info"><?php echo L_USR_PASSWORD_DESC_PROF; ?></small>
    </td><td>
    <input type="password" name="password" value="" size="25" maxlength="25" placeholder="Neues Passwort (leer = keine &Auml;nderung)"><br>
    <input type="password" name="password2" value="" size="25" maxlength="25" placeholder="Passwort wiederholen">
    </td></tr>
    <tr><td colspan="2" align="center">
    <input type="submit" value="<?php echo L_USR_EDITPROFILE; ?>"> <input type="reset" value="<?php echo L_ALL_RESETDATA; ?>">
    </td></tr>
    </table>
    </form>
    </center>
    <br>
    <?php echo L_USR_PROFILE_DESC; ?>
    <?php
}
?>

</td></tr>
