<?php
declare(strict_types=1);
/************************************************************************/
/* PowerNews - PHP and MySQL based news script                         */
/* Copyright (c) 2001-2024 PowerScripts                                 */
/*                                                                      */
/* MIT License - See LICENSE file for full license text                 */
/* https://github.com/schubertnico/PowerNews.git                        */
/************************************************************************/

  if ($pnadmin['canreadcategories'] == "YES" && $pnadmin['canwritecategories'] == "YES") {
    if ($pnconfig['categories'] == "YES") {
      if ($_GET['catid']) {
        $editcat = new category;
        $error = $editcat->checkcat($_GET['catid']);
        if ($error !== '' && $error !== '0') {
          ?><center><a href="index.php?page=categories&subpage=show"><?php echo pnadmin_escape($error); ?></a></center><?php
        } else {
          if ($_GET['edit'] == "YES") {
            $uploadpic = $_POST['uploadpic'] ?? '';
            if (!$_POST['name'] || !$_POST['description'] || $pnconfig['categories'] && $uploadpic == "YES" && !$_FILES['picture']) {
              ?><center><a href="javascript:history.back()"><?php echo L_CAT_FILLALL; ?>
          <?php if ($pnconfig['categorypics'] == "YES" && $uploadpic == "YES") { echo L_CAT_ANDPIC; } ?>!</a></center><?php
            } else {
              $error = $editcat->editcat($_POST['name'], $_POST['description'], $_POST['uploadpic'], $_FILES['picture'], $_POST['status'], $_GET['catid']);
              if ($error !== '' && $error !== '0') {
                ?><center><a href="javascript:history.back()"><?php echo pnadmin_escape($error); ?></a></center><?php
              } else {
                ?><center><a href="index.php?page=categories&subpage=show"><?php echo L_CAT_EDITED; ?></a></center><?php
              }
            }
          } else {
            $data = $editcat->getcatdata($_GET['catid']);
            ?>
            <center>
            <form action="index.php?page=categories&subpage=edit&edit=YES&catid=<?php echo pnadmin_escape($_GET['catid']); ?>" method="post" enctype="multipart/form-data">
            <table border="0" cellpadding="4" cellspacing="0">
            <tr><td colspan="2" align="center">
            <b><?php echo L_CAT_EDITCAT; ?></b>
            </td></tr>
            <tr><td>
            <b><?php echo L_CAT_TITLE; ?></b><br>
            <small class="info"><?php echo L_CAT_TITLEDESC; ?></small>
            </td><td>
            <input name="name" size="25" maxlength="100" value="<?php echo pnadmin_escape($data['name']); ?>">
            </td></tr>
            <tr><td valign="top">
            <b><?php echo L_CAT_DESCRIPTION; ?></b><br>
            <small class="info"><?php echo L_CAT_DESCRIPTIONDESC; ?></small>
            </td><td>
            <textarea name="description" cols="50" rows="3"><?php echo pnadmin_escape($data['description']); ?></textarea>
            </td></tr>
            <?php if ($pnconfig['categorypics'] == "YES") { ?>
              <tr><td>
              <b><?php echo L_CAT_UPLOADPIC; ?></b><br>
              <small class="info"><?php echo L_CAT_UPLOADPICDESC; ?>
              </td><td>
              <input type="checkbox" name="uploadpic" value="YES"> (<a href="../pngfx/categories/<?php echo pnadmin_escape($data['picture']); ?>" target="_blank"><?php echo L_CAT_CURRENTPIC; ?></a>)
              </td></tr>
              <tr><td>
              <b><?php echo L_CAT_PIC; ?></b><br>
              <small class="info"><?php echo L_CAT_PICDESC; ?></small>
              </td><td>
              <input name="picture" type="file" size="25">
              </td></tr>
            <?php } ?>
            <tr><td>
            <b><?php echo L_CAT_STATUS; ?></b><br>
            <small class="info"><?php echo L_CAT_STATUSDESC; ?></small>
            </td><td>
            <select name="status" size="1">
              <option value="Activated" <?php if ($data['status'] == "Activated") { echo "selected"; } ?>><?php echo L_ALL_ACTIVATED; ?>
              <option value="Deactivated" <?php if ($data['status'] == "Deactivated") { echo "selected"; } ?>><?php echo L_ALL_DEACTIVATED; ?>
            </select>
            </td></tr>
            <tr><td colspan="2" align="center">
            <input type="submit" value="<?php echo L_CAT_EDITCAT; ?>"> <input type="reset" value="<?php echo L_ALL_RESETDATA; ?>">
            </td></tr>
            </table>
            </form>
            </center>
            <?php
          }
        }
      } else {
        ?><center><a href="index.php?page=categories&subpage=show"><?php echo L_CAT_CHOOSECAT; ?></a></center><?php
      }
    } else {
      ?><center><?php echo L_CAT_CATSAREDEACTIVATED; ?></center><?php
    }
  } else {
    ?><center><?php echo L_ALL_ACCESSDENIED; ?></center><?php
  }
?>
