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

       <b class="headline"><?PHP echo L_TITLE_TEMPLATES; ?></b>

</b></td></tr>

</td><td bgcolor="#001F3F" valign="top">

<?PHP
  if (isset($_GET['subpage']) && $_GET['subpage']) {
    $allowed_files = [
      'login.inc.php',
      'news.inc.php',
      'other_license.inc.php',
      'users_show.inc.php',
      'news_edit.inc.php',
      'other.inc.php',
      'news_search.inc.php',
      'profile.inc.php',
      'templates_edit.inc.php',
      'templates_show.inc.php',
      'users_search.inc.php',
      'news_show.inc.php',
      'categories_add.inc.php',
      'categories_show.inc.php',
      'permissions.inc.php',
      'permissions_add.inc.php',
      'permissions_edit.inc.php',
      'main.inc.php',
      'templates.inc.php',
      'users_add.inc.php',
      'other_help.inc.php',
      'templates_add.inc.php',
      'users_edit.inc.php',
      'news_add.inc.php',
      'users.inc.php',
      'categories.inc.php',
      'permissions_show.inc.php',
      'categories_edit.inc.php'
    ];

    $requested_file = $_GET['page'] . "_" . $_GET['subpage'] . ".inc.php";

    if (in_array($requested_file, $allowed_files) && file_exists($requested_file)) {
      include($requested_file);
    } else {
      ?><center><?PHP echo L_ALL_SUBPAGENOTFOUND; ?></center><?PHP
    }
  } else {
    ?><center><?PHP echo L_ALL_CHOOSESUBPAGE; ?><br><br><a href="index.php?page=templates&subpage=add"><?PHP echo L_TEMPL_ADDTEMPLATE; ?></a> | <a href="index.php?page=templates&subpage=show"><?PHP echo L_TEMPL_SHOWTEMPLATES; ?></a></center><?PHP
  }
?>

</td></tr>
