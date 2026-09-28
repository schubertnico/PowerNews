<?php
declare(strict_types=1);

/* PowerNews - PHP and MySQL based news script                         */
/* Copyright (c) 2001-2026 PowerScripts                                 */

/* MIT License - See LICENSE file for full license text                 */
/* https://github.com/schubertnico/PowerNews.git                        */

// Validierte Parameter
$add = pn_get_string('add', 10);

if ($pnadmin['canwritenews'] == 'YES') {

    if ($add === 'YES') {
        $title = pn_post_string('title', 150);
        $text = pn_post_string('text', 65535);
        $catid = pn_post_id('catid');
        $moretext = pn_post_string('moretext', 65535);
        $rl_title = $_POST['rl_title'] ?? [];
        $rl_url = $_POST['rl_url'] ?? [];
        $rl_target = $_POST['rl_target'] ?? [];
        $time = $_POST['time'] ?? [];

        if (($pnconfig['categories'] == 'YES' && $catid === 0) || $title === '' || $text === '') {
            ?>
            <div class="alert alert-danger" role="alert">
                <?php echo L_NEWS_TITLEANDTEXTNEEDED; ?>
                <?php if ($pnconfig['categories'] == 'YES') {
                    echo L_NEWS_ALSOCATEGORY;
                } ?>!
                <div class="mt-2"><a href="index.php?page=news&amp;subpage=add" class="btn btn-sm btn-outline-secondary"><?php echo L_ALL_BACKTOFORM; ?></a></div>
            </div>
            <?php
        } else {
            $news = new news();
            $error = $news->addnews($title, $text, $catid, $moretext, $rl_title, $rl_url, $rl_target, $time);

            if ($error !== '' && $error !== '0') {
                ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo pnadmin_escape($error); ?>
                    <div class="mt-2"><a href="index.php?page=news&amp;subpage=add" class="btn btn-sm btn-outline-secondary"><?php echo L_ALL_BACKTOFORM; ?></a></div>
                </div>
                <?php
            } else {
                ?>
                <div class="alert alert-success" role="alert">
                    <?php echo L_NEWS_NEWSADDED; ?>
                    <div class="mt-2"><a href="index.php?page=news&amp;subpage=add" class="btn btn-sm btn-success"><?php echo L_NEWS_WRITEMORE; ?></a></div>
                </div>
                <?php
            }
        }
    } else {
        $news = new news();
        ?>
      <form action="index.php?page=news&amp;subpage=add&amp;add=YES" method="post" novalidate><?php echo pnadmin_csrf_field(); ?>
          <fieldset>
              <legend class="h6"><?php echo L_NEWS_WRITENEWS; ?></legend>

<?php if ($pnconfig['categories'] == 'YES') { ?>
              <div class="mb-3">
                  <label class="form-label fw-bold"><?php echo L_NEWS_CATEGORY; ?></label>
                  <?php $news->getcatdropdown(); ?>
                  <div class="form-text"><?php echo L_NEWS_CATEGORY_DESC; ?></div>
              </div>
<?php } ?>

              <div class="mb-3">
                  <label class="form-label fw-bold"><?php echo L_NEWS_TIME; ?></label>
                  <div class="d-flex flex-wrap gap-2 align-items-center">
                      <?php $news->timeselect(time()); ?>
                  </div>
                  <div class="form-text"><?php echo L_NEWS_TIME_DESC; ?></div>
              </div>

              <div class="mb-3">
                  <label for="pn_title" class="form-label fw-bold"><?php echo L_NEWS_TITLE; ?></label>
                  <input class="form-control" name="title" id="pn_title" maxlength="150" required aria-describedby="pn_title_help">
                  <div id="pn_title_help" class="form-text"><?php echo L_NEWS_TITLE_DESC; ?></div>
              </div>

              <div class="mb-3">
                  <label for="pn_text" class="form-label fw-bold"><?php echo L_NEWS_TEXT; ?></label>
                  <textarea class="form-control" name="text" id="pn_text" rows="10" required aria-describedby="pn_text_help"></textarea>
                  <div id="pn_text_help" class="form-text">
                      <?php echo L_NEWS_TEXT_DESC; ?> <?php echo $news->formathint(); ?>
                  </div>
              </div>

<?php if ($pnconfig['moretext'] == 'YES') { ?>
              <div class="mb-3">
                  <label for="pn_moretext" class="form-label fw-bold"><?php echo L_NEWS_LONGTEXT; ?></label>
                  <textarea class="form-control" name="moretext" id="pn_moretext" rows="10" aria-describedby="pn_moretext_help"></textarea>
                  <div id="pn_moretext_help" class="form-text">
                      <?php echo L_NEWS_LONGTEXT_DESC; ?> <?php echo $news->formathint(); ?>
                  </div>
              </div>
<?php } ?>

<?php if ($pnconfig['relatedlinks'] == 'YES') { ?>
              <div class="mb-3">
                  <label class="form-label fw-bold"><?php echo L_NEWS_RELATEDLINKS; ?></label>
                  <div class="form-text mb-2"><?php echo L_NEWS_RELATEDLINKS_DESC; ?></div>
                  <div class="table-responsive">
                      <table class="table table-sm align-middle">
                          <thead>
                              <tr>
                                  <th><?php echo L_NEWS_RL_TITLE; ?></th>
                                  <th><?php echo L_NEWS_RL_URL; ?></th>
                                  <th><?php echo L_NEWS_RL_TARGET; ?></th>
                              </tr>
                          </thead>
                          <tbody>
<?php
                          /* List forms for related links */
                          for ($i = 0; $i < $pnconfig['relatedlinks_num']; ++$i) {
                              ?>
                              <tr>
                                  <td><input class="form-control form-control-sm" name="rl_title[]" maxlength="50" aria-label="<?php echo L_NEWS_RL_TITLE; ?>"></td>
                                  <td><input class="form-control form-control-sm" name="rl_url[]" maxlength="250" aria-label="<?php echo L_NEWS_RL_URL; ?>"></td>
                                  <td>
                                      <select class="form-select form-select-sm" name="rl_target[]" aria-label="<?php echo L_NEWS_RL_TARGET; ?>">
<?php
                                          $counter = count($pn_config['rltargets']);

                              for ($i2 = 0; $i2 < $counter; ++$i2) {
                                  ?><option value="<?php echo pnadmin_escape($pn_config['rltargets'][$i2]); ?>"><?php echo pnadmin_escape(pn_relatedlink_target_label((string) $pn_config['rltargets'][$i2])); ?></option><?php
                              }
                              ?>
                                      </select>
                                  </td>
                              </tr>
<?php
                          }
    ?>
                          </tbody>
                      </table>
                  </div>
              </div>
<?php } ?>

              <button type="submit" class="btn btn-primary"><?php echo L_NEWS_WRITENEWS; ?></button>
          </fieldset>
      </form>
      <?php
    }
} else {
    ?><div class="alert alert-danger mb-0" role="alert"><?php echo L_ALL_ACCESSDENIED; ?></div><?php
}
?>
