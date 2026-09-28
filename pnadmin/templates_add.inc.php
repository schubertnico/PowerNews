<?php

/* PowerNews - PHP and MySQL based news script                          */
/* Copyright (c) 2001-2026 PowerScripts                                 */

/* MIT License - See LICENSE file for full license text                 */
/* https://github.com/schubertnico/PowerNews.git                        */

if ($pnadmin['canwritetemplates'] == 'YES') {

    if (isset($_GET['add']) && $_GET['add'] == 'YES') {
        $addtemplate = new template();
        $addtemplate->addtemplate();
    } else {
        ?>
      <form action="index.php?page=templates&amp;subpage=add&amp;add=YES" method="post" novalidate><?php echo pnadmin_csrf_field(); ?>
          <fieldset>
              <legend class="h6"><?php echo L_TEMPL_ADDTEMPLATE; ?></legend>

              <div class="mb-3">
                  <label for="pn_title" class="form-label fw-bold"><?php echo L_TEMPL_TITLE; ?></label>
                  <input class="form-control" name="pndata[title]" id="pn_title" maxlength="100" required aria-describedby="pn_title_help">
                  <div id="pn_title_help" class="form-text"><?php echo L_TEMPL_TITLE_DESC; ?></div>
              </div>

              <button type="submit" class="btn btn-primary"><?php echo L_TEMPL_ADDTEMPLATE; ?></button>
          </fieldset>
      </form>
      <?php
    }

} else {
    ?><div class="alert alert-danger mb-0" role="alert"><?php echo L_ALL_ACCESSDENIED; ?></div><?php
}
?>
