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

<b class="headline"><?PHP echo L_TITLE_LOGIN; ?></b>

</b></td></tr>

</td><td bgcolor="#001F3F" valign="top" align="center">

  <?PHP if (!isset($loginerror)) { ?>

    <form action="index.php?pnlogin=YES" method="post">
        <table border="0" cellpadding="4" cellspacing="0">
            <tr>
                <td>
                    <b><?PHP echo L_USR_NICKNAME; ?></b>
                </td>
                <td>
                    <input name="pnlogin_nickname" size="25" maxlength="100">
                </td>
            </tr>
            <tr>
                <td>
                    <b><?PHP echo L_USR_PASSWORD; ?></b>
                </td>
                <td>
                    <input name="pnlogin_password" size="25" maxlength="100" type="password">
                </td>
            </tr>
            <tr>
                <td colspan="2" align="center">
                    <input type="submit" value="<?PHP echo L_USR_LOGIN; ?>">
                </td>
            </tr>
        </table>
    </form>
    <br>

    <small><?PHP echo L_USR_COOKIESMUSTBEENABLED; ?></small>

  <?PHP } elseif ($loginerror == "loggedin") { ?>

    <center><a href="./"><?PHP echo L_USR_LOGINOK; ?></a></center>

  <?PHP } else { ?>

    <center><a href="javascript:history.back()"><?PHP echo $loginerror; ?></a></center>

  <?PHP } ?>

</td></tr>
