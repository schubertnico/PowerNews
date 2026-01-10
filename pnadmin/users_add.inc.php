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

  if ($pnadmin['canwriteusers'] == "YES") {


    if (isset($_GET['add']) && $_GET['add'] == "YES") {
      if (isset($_POST['nickname']) && $_POST['nickname'] == '' || isset($_POST['email']) && $_POST['email'] == '') {
        ?><center><a href="javascript:history.back()"><?PHP echo L_USR_NICKANDEMAIL; ?></a></center><?PHP
      } else {
        $user = new user;
        $uerror = $user->adduser($_POST['nickname'], $_POST['email'], ((isset($_POST['showemail']) && trim((string) $_POST['showemail']) !== '') ? trim((string) $_POST['showemail']):''), ((isset($_POST['sendemail']) && trim((string) $_POST['sendemail']) !== '') ? trim((string) $_POST['sendemail']):''));
        if ($uerror !== '' && $uerror !== '0') {
          ?><center><a href="javascript:history.back()"><?PHP echo $uerror; ?></a></center><?PHP
        } else {
          ?><center><a href="index.php?page=users&subpage=add"><?PHP echo L_USR_USRADDED; ?></a></center><?PHP
        }
      }

    } else {
      ?><br>
      <center>
      <form action="index.php?page=users&subpage=add&add=YES" method="post">
      <table border="0" cellpadding="4" cellspacing="0">
      <tr><td colspan="2" align="center">
      <b><?PHP echo L_USR_ADDUSR; ?></b>
      </td></tr>
      <tr><td>
      <b><?PHP echo L_USR_NICKNAME; ?></b><br>
      <small class="info"><?PHP echo L_USR_NICKNAME_DESC; ?></small>
      </td><td>
      <input name="nickname" size="25" maxlength="100">
      </td></tr>
      <tr><td>
      <b><?PHP echo L_USR_EMAIL; ?></b><br>
      <small class="info"><?PHP echo L_USR_EMAIL_DESC; ?></small>
      </td><td>
      <input name="email" size="25" maxlength="250">
      </td></tr>
      <tr><td>
      <b><?PHP echo L_USR_SHOWEMAIL; ?></b><br>
      <small class="info"><?PHP echo L_USR_SHOWEMAIL_DESC; ?></small>
      </td><td>
      <input type="checkbox" name="showemail" value="YES">
      </td></tr>
      <tr><td>
      <b><?PHP echo L_USR_SENDMAIL; ?></b><br>
      <small class="info"><?PHP echo L_USR_SENDMAIL_DESC; ?></small>
      </td><td>
      <input type="checkbox" name="sendemail" value="YES" checked>
      </td></tr>
      <tr><td colspan="2" align="center">
      <input type="submit" value="<?PHP echo L_USR_ADDUSR; ?>">
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
