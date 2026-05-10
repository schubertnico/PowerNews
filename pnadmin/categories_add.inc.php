<?php
declare(strict_types=1);

/* PowerNews - PHP and MySQL based news script                         */
/* Copyright (c) 2001-2026 PowerScripts                                 */

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
                ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo L_CAT_FILLALL; ?>
                    <?php if ($pnconfig['categorypics'] == 'YES') {
                        echo L_CAT_ANDPIC;
                    } ?>!
                    <div class="mt-2"><a href="index.php?page=categories&amp;subpage=add" class="btn btn-sm btn-outline-secondary">Zur&uuml;ck zum Formular</a></div>
                </div>
                <?php
            } else {
                $category = new category();
                $error = $category->addcat($name, $description, $picture);

                if ($error !== '' && $error !== '0') {
                    ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo pnadmin_escape($error); ?>
                        <div class="mt-2"><a href="index.php?page=categories&amp;subpage=add" class="btn btn-sm btn-outline-secondary">Zur&uuml;ck zum Formular</a></div>
                    </div>
                    <?php
                } else {
                    ?>
                    <div class="alert alert-success" role="alert">
                        <?php echo L_CAT_CATADDED; ?>
                        <div class="mt-2"><a href="index.php?page=categories&amp;subpage=add" class="btn btn-sm btn-success">Weitere Kategorie anlegen</a></div>
                    </div>
                    <?php
                }
            }
        } else {
            ?>
        <form action="index.php?page=categories&amp;subpage=add&amp;add=YES" method="post" enctype="multipart/form-data" novalidate>
            <fieldset>
                <legend class="h6"><?php echo L_CAT_ADDCAT; ?></legend>

                <div class="mb-3">
                    <label for="pn_name" class="form-label fw-bold"><?php echo L_CAT_TITLE; ?></label>
                    <input class="form-control" name="name" id="pn_name" maxlength="100" required aria-describedby="pn_name_help">
                    <div id="pn_name_help" class="form-text"><?php echo L_CAT_TITLEDESC; ?></div>
                </div>

                <div class="mb-3">
                    <label for="pn_description" class="form-label fw-bold"><?php echo L_CAT_DESCRIPTION; ?></label>
                    <textarea class="form-control" name="description" id="pn_description" rows="3" required aria-describedby="pn_description_help"></textarea>
                    <div id="pn_description_help" class="form-text"><?php echo L_CAT_DESCRIPTIONDESC; ?></div>
                </div>

<?php if ($pnconfig['categorypics'] == 'YES') { ?>
                <div class="mb-3">
                    <label for="pn_picture" class="form-label fw-bold"><?php echo L_CAT_PIC; ?></label>
                    <input class="form-control" name="picture" type="file" id="pn_picture" aria-describedby="pn_picture_help">
                    <div id="pn_picture_help" class="form-text"><?php echo L_CAT_PICDESC; ?></div>
                </div>
<?php } ?>

                <button type="submit" class="btn btn-primary"><?php echo L_CAT_ADDNEWCAT; ?></button>
            </fieldset>
        </form>
        <?php
        }
    } else {
        ?><div class="alert alert-warning mb-0" role="alert"><?php echo L_CAT_CATSAREDEACTIVATED; ?></div><?php
    }

} else {
    ?><div class="alert alert-danger mb-0" role="alert"><?php echo L_ALL_ACCESSDENIED; ?></div><?php
}
?>
