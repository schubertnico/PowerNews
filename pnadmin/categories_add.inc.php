<?php
declare(strict_types=1);

/* PowerNews - PHP and MySQL based news script                         */
/* Copyright (c) 2001-2024 PowerScripts                                 */

/* MIT License - See LICENSE file for full license text                 */
/* https://github.com/schubertnico/PowerNews.git                        */

// Validierte Parameter
$add = pn_get_string('add', 10);

if ($pnadmin['canwritecategories'] == 'YES') {
    if ($pnconfig['categories'] == 'YES') {
        if ($add === 'YES') {
            $name = pn_post_string('name', 100);
            $description = pn_post_string('description', 1000);
            $picture = (isset($_FILES['picture']) && is_array($_FILES['picture']) && !empty($_FILES['picture']['tmp_name'])) ? $_FILES['picture'] : [];

            if ($name === '' || $description === '' || ($pnconfig['categorypics'] == 'YES' && empty($picture))) {
                ?><center><a href="javascript:history.back()"><?php echo L_CAT_FILLALL; ?>
          <?php if ($pnconfig['categorypics'] == 'YES') {
              echo L_CAT_ANDPIC;
          } ?>!</a></center><?php
            } else {
                $category = new category();
                $error = $category->addcat($name, $description, $picture);

                if ($error !== '' && $error !== '0') {
                    ?><center><a href="javascript:history.back()"><?php echo pnadmin_escape($error); ?></a></center><?php
                } else {
                    ?><center><a href="index.php?page=categories&subpage=add"><?php echo L_CAT_CATADDED; ?></a></center><?php
                }
            }
        } else {
            ?>
        <center>
        <form action="index.php?page=categories&subpage=add&add=YES" method="post" enctype="multipart/form-data">
        <table border="0" cellpadding="4" cellspacing="0">
        <tr><td colspan="2" align="center">
        <b><?php echo L_CAT_ADDCAT; ?></b>
        </td></tr>
        <tr><td>
        <b><?php echo L_CAT_TITLE; ?></b><br>
        <small class="info"><?php echo L_CAT_TITLEDESC; ?></small>
        </td><td>
        <input name="name" size="25" maxlength="100">
        </td></tr>
        <tr><td valign="top">
        <b><?php echo L_CAT_DESCRIPTION; ?></b><br>
        <small class="info"><?php echo L_CAT_DESCRIPTIONDESC; ?></small>
        </td><td>
        <textarea name="description" cols="50" rows="3"></textarea>
        </td></tr>
        <?php if ($pnconfig['categorypics'] == 'YES') { ?>
          <tr><td>
          <b><?php echo L_CAT_PIC; ?></b><br>
          <small class="info"><?php echo L_CAT_PICDESC; ?></small>
          </td><td>
          <input name="picture" type="file" size="25">
          </td></tr>
        <?php } ?>
        <tr><td colspan="2" align="center">
        <input type="submit" value="<?php echo L_CAT_ADDNEWCAT; ?>">
        </td></tr>
        </table>
        </form>
        </center>
        <?php
        }
    } else {
        ?><center><?php echo L_CAT_CATSAREDEACTIVATED; ?></center><?php
    }

} else {
    ?><center><?php echo L_ALL_ACCESSDENIED; ?></center><?php
}
?>
