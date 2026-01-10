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

  if ($pnadmin['canreadusers'] == "YES") {
    if (isset($_GET['search']) && $_GET['search'] == "YES") {
      if (!isset($_GET['searchstring'])) {
        ?><center><a href="javascript:history.back()"><?PHP echo L_USR_SEARCHSTRINGNEEDED; ?></a></center><?PHP
      } else {
        ?>
        <center>
        <?PHP
          $searchuser = new user;
          $searchuser->listsearchpages($_GET['searchin'], $_GET['searchstring']);
        ?>
        <br><br>
        <table border="0" cellpadding="4" cellspacing="0">
        <tr><td>
        <b><?PHP echo L_USR_NICKNAME; ?></b>
        </td><td width="30">
        &nbsp;
        </td><td>
        <b><?PHP echo L_USR_EMAIL; ?></b>
        </td><td width="30">
        &nbsp;
        </td><td>
        <b><?PHP echo L_USR_SHOWEMAIL; ?></b>
        </td><td width="30">
        &nbsp;
        </td><td>
        <b><?PHP echo L_USR_ADMIN; ?></b>
        </td><td width="30">
        &nbsp;
        </td><td>
        <b><?PHP echo L_USR_STATUS; ?></b>
        </td></tr>
        <?PHP
          if (!isset($_GET['current']) ) { $_GET['current'] = "0"; }
          $searchuser->searchuser($_GET['searchin'], $_GET['searchstring'], $_GET['current']);
        ?>
        </table>
        <br>
        <?PHP $searchuser->listsearchpages($_GET['searchin'], $_GET['searchstring']); ?>
        </center><br>
        <br>
        <small><?PHP echo L_USR_SHOWUSR_DESC; ?></small>
        <?PHP
      }
    } else {
      ?>
      <center>
      <form action="./" method="get">
      <input type="hidden" name="page" value="users">
      <input type="hidden" name="subpage" value="search">
      <input type="hidden" name="search" value="YES">
      <table border="0" cellpadding="4" cellspacing="0">
      <tr><td colspan="2" align="center">
      <b><?PHP echo L_USR_SEARCHUSR; ?></b>
      </td></tr>
      <tr><td>
      <b><?PHP echo L_USR_SEARCHFIELD; ?></b><br>
      <small class="info"><?PHP echo L_USR_SEARCHFIELD_DESC; ?></small>
      </td><td>
      <select name="searchin" size="1">
        <option value="nickname"><?PHP echo L_USR_NICKNAME; ?></option>
        <option value="email"><?PHP echo L_USR_EMAIL; ?></option>
        <option value="id"><?PHP echo L_USR_USRID; ?></option>
      </select>
      </td></tr>
      <tr><td>
      <b><?PHP echo L_USR_SEARCHSTRING; ?></b><br>
      <small class="info"><?PHP echo L_USR_SEARCHSTRING_DESC; ?></small>
      </td><td>
      <input name="searchstring" size="25" maxlength="250">
      </td></tr>
      <tr><td colspan="2" align="center">
      <input type="submit" value="<?PHP echo L_USR_SEARCHUSR; ?>">
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
