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

  if ($pnadmin['canwritecategories'] == "YES") {
    if ($pnconfig['categories'] == "YES") {
      if (isset($_GET['add']) && $_GET['add'] == "YES") {
        if (!$_POST['name'] || !$_POST['description'] || $pnconfig['categorypics'] == "YES" && !$_FILES['picture']) {
          ?><center><a href="javascript:history.back()"><?PHP  echo L_CAT_FILLALL; ?>
          <?PHP if ($pnconfig['categorypics'] == "YES") { echo L_CAT_ANDPIC; } ?>!</a></center><?PHP
        } else {
          $category = new category;
          $error = $category->addcat(((isset($_POST['name']) && trim((string) $_POST['name']) !== '') ? trim((string) $_POST['name']):''),
            ((isset($_POST['description']) && trim((string) $_POST['description']) !== '') ? trim((string) $_POST['description']):''),
            (isset($_FILES['picture']) && is_array($_FILES['picture']) && !empty($_FILES['picture']['tmp_name']) ? $_FILES['picture'] : []));
          if ($error !== '' && $error !== '0') {
            ?><center><a href="javascript:history.back()"><?PHP echo $error; ?></a></center><?PHP
          } else {
            ?><center><a href="index.php?page=categories&subpage=add"><?PHP  echo L_CAT_CATADDED; ?></a></center><?PHP
          }
        }
      } else {
        ?>
        <center>
        <form action="index.php?page=categories&subpage=add&add=YES" method="post" enctype="multipart/form-data">
        <table border="0" cellpadding="4" cellspacing="0">
        <tr><td colspan="2" align="center">
        <b><?PHP  echo L_CAT_ADDCAT; ?></b>
        </td></tr>
        <tr><td>
        <b><?PHP  echo L_CAT_TITLE; ?></b><br>
        <small class="info"><?PHP  echo L_CAT_TITLEDESC; ?></small>
        </td><td>
        <input name="name" size="25" maxlength="100">
        </td></tr>
        <tr><td valign="top">
        <b><?PHP  echo L_CAT_DESCRIPTION; ?></b><br>
        <small class="info"><?PHP  echo L_CAT_DESCRIPTIONDESC; ?></small>
        </td><td>
        <textarea name="description" cols="50" rows="3"></textarea>
        </td></tr>
        <?PHP if ($pnconfig['categorypics'] == "YES") { ?>
          <tr><td>
          <b><?PHP  echo L_CAT_PIC; ?></b><br>
          <small class="info"><?PHP  echo L_CAT_PICDESC; ?></small>
          </td><td>
          <input name="picture" type="file" size="25">
          </td></tr>
        <?PHP } ?>
        <tr><td colspan="2" align="center">
        <input type="submit" value="<?PHP  echo L_CAT_ADDNEWCAT; ?>">
        </td></tr>
        </table>
        </form>
        </center>
        <?PHP
      }
    } else {
      ?><center><?PHP  echo L_CAT_CATSAREDEACTIVATED; ?></center><?PHP
    }

  } else {
    ?><center><?PHP  echo L_ALL_ACCESSDENIED; ?></center><?PHP
  }
?>
