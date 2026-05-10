<?php
declare(strict_types=1);

/* PowerNews - PHP and MySQL based news script                         */
/* Copyright (c) 2001-2026 PowerScripts                                 */

/* MIT License - See LICENSE file for full license text                 */
/* https://github.com/schubertnico/PowerNews.git                        */

// Validierte Parameter
$catid = pn_get_id('catid');
$edit = pn_get_string('edit', 10);

if ($pnadmin['canreadcategories'] == 'YES' && $pnadmin['canwritecategories'] == 'YES') {
    if ($pnconfig['categories'] == 'YES') {
        if ($catid > 0) {
            $editcat = new category();
            $error = $editcat->checkcat($catid);

            if ($error !== '' && $error !== '0') {
                ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo pnadmin_escape($error); ?>
                    <div class="mt-2"><a href="index.php?page=categories&amp;subpage=show" class="btn btn-sm btn-outline-secondary">Zur&uuml;ck zur Liste</a></div>
                </div>
                <?php
            } else {
                if ($edit === 'YES') {
                    $name = pn_post_string('name', 100);
                    $description = pn_post_string('description', 1000);
                    $uploadpic = pn_validate_yesno($_POST['uploadpic'] ?? '');
                    $picture = (isset($_FILES['picture']) && is_array($_FILES['picture']) && !empty($_FILES['picture']['tmp_name'])) ? $_FILES['picture'] : [];
                    $status = pn_validate_status($_POST['status'] ?? '');

                    if ($name === '' || $description === '' || ($pnconfig['categorypics'] == 'YES' && $uploadpic == 'YES' && empty($picture))) {
                        ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo L_CAT_FILLALL; ?>
                            <?php if ($pnconfig['categorypics'] == 'YES' && $uploadpic == 'YES') {
                                echo L_CAT_ANDPIC;
                            } ?>!
                            <div class="mt-2"><a href="index.php?page=categories&amp;subpage=edit&amp;catid=<?php echo pn_int($catid); ?>" class="btn btn-sm btn-outline-secondary">Zur&uuml;ck zum Formular</a></div>
                        </div>
                        <?php
                    } else {
                        $error = $editcat->editcat($name, $description, $uploadpic, $picture, $status, $catid);

                        if ($error !== '' && $error !== '0') {
                            ?>
                            <div class="alert alert-danger" role="alert">
                                <?php echo pnadmin_escape($error); ?>
                                <div class="mt-2"><a href="index.php?page=categories&amp;subpage=edit&amp;catid=<?php echo pn_int($catid); ?>" class="btn btn-sm btn-outline-secondary">Zur&uuml;ck zum Formular</a></div>
                            </div>
                            <?php
                        } else {
                            ?>
                            <div class="alert alert-success" role="alert">
                                <?php echo L_CAT_EDITED; ?>
                                <div class="mt-2"><a href="index.php?page=categories&amp;subpage=show" class="btn btn-sm btn-success">Zur&uuml;ck zur Liste</a></div>
                            </div>
                            <?php
                        }
                    }
                } else {
                    $data = $editcat->getcatdata($catid);
                    ?>
            <form action="index.php?page=categories&amp;subpage=edit&amp;edit=YES&amp;catid=<?php echo pn_int($catid); ?>" method="post" enctype="multipart/form-data" novalidate>
                <fieldset>
                    <legend class="h6"><?php echo L_CAT_EDITCAT; ?></legend>

                    <div class="mb-3">
                        <label for="pn_name" class="form-label fw-bold"><?php echo L_CAT_TITLE; ?></label>
                        <input class="form-control" name="name" id="pn_name" maxlength="100" value="<?php echo pnadmin_escape($data['name']); ?>" required aria-describedby="pn_name_help">
                        <div id="pn_name_help" class="form-text"><?php echo L_CAT_TITLEDESC; ?></div>
                    </div>

                    <div class="mb-3">
                        <label for="pn_description" class="form-label fw-bold"><?php echo L_CAT_DESCRIPTION; ?></label>
                        <textarea class="form-control" name="description" id="pn_description" rows="3" aria-describedby="pn_description_help"><?php echo pnadmin_escape($data['description']); ?></textarea>
                        <div id="pn_description_help" class="form-text"><?php echo L_CAT_DESCRIPTIONDESC; ?></div>
                    </div>

<?php if ($pnconfig['categorypics'] == 'YES') { ?>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="uploadpic" value="YES" id="pn_uploadpic" aria-describedby="pn_uploadpic_help">
                        <label class="form-check-label fw-bold" for="pn_uploadpic"><?php echo L_CAT_UPLOADPIC; ?></label>
                        <div id="pn_uploadpic_help" class="form-text"><?php echo L_CAT_UPLOADPICDESC; ?> (<a href="../pngfx/categories/<?php echo pnadmin_escape($data['picture']); ?>" target="_blank" rel="noopener noreferrer"><?php echo L_CAT_CURRENTPIC; ?></a>)</div>
                    </div>

                    <div class="mb-3">
                        <label for="pn_picture" class="form-label fw-bold"><?php echo L_CAT_PIC; ?></label>
                        <input class="form-control" name="picture" type="file" id="pn_picture" aria-describedby="pn_picture_help">
                        <div id="pn_picture_help" class="form-text"><?php echo L_CAT_PICDESC; ?></div>
                    </div>
<?php } ?>

                    <div class="mb-3">
                        <label for="pn_status" class="form-label fw-bold"><?php echo L_CAT_STATUS; ?></label>
                        <select class="form-select" name="status" id="pn_status" aria-describedby="pn_status_help">
                            <option value="Activated" <?php if ($data['status'] == 'Activated') {
                                echo 'selected';
                            } ?>><?php echo L_ALL_ACTIVATED; ?></option>
                            <option value="Deactivated" <?php if ($data['status'] == 'Deactivated') {
                                echo 'selected';
                            } ?>><?php echo L_ALL_DEACTIVATED; ?></option>
                        </select>
                        <div id="pn_status_help" class="form-text"><?php echo L_CAT_STATUSDESC; ?></div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><?php echo L_CAT_EDITCAT; ?></button>
                        <button type="reset" class="btn btn-outline-secondary"><?php echo L_ALL_RESETDATA; ?></button>
                    </div>
                </fieldset>
            </form>
            <?php
                }
            }
        } else {
            ?>
            <div class="alert alert-info" role="alert">
                <?php echo L_CAT_CHOOSECAT; ?>
                <div class="mt-2"><a href="index.php?page=categories&amp;subpage=show" class="btn btn-sm btn-primary">Zur&uuml;ck zur Liste</a></div>
            </div>
            <?php
        }
    } else {
        ?><div class="alert alert-warning mb-0" role="alert"><?php echo L_CAT_CATSAREDEACTIVATED; ?></div><?php
    }
} else {
    ?><div class="alert alert-danger mb-0" role="alert"><?php echo L_ALL_ACCESSDENIED; ?></div><?php
}
?>
