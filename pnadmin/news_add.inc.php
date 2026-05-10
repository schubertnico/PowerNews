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
                <div class="mt-2"><a href="index.php?page=news&amp;subpage=add" class="btn btn-sm btn-outline-secondary">Zur&uuml;ck zum Formular</a></div>
            </div>
            <?php
        } else {
            $news = new news();
            $error = $news->addnews($title, $text, $catid, $moretext, $rl_title, $rl_url, $rl_target, $time);

            if ($error !== '' && $error !== '0') {
                ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo pnadmin_escape($error); ?>
                    <div class="mt-2"><a href="index.php?page=news&amp;subpage=add" class="btn btn-sm btn-outline-secondary">Zur&uuml;ck zum Formular</a></div>
                </div>
                <?php
            } else {
                ?>
                <div class="alert alert-success" role="alert">
                    <?php echo L_NEWS_NEWSADDED; ?>
                    <div class="mt-2"><a href="index.php?page=news&amp;subpage=add" class="btn btn-sm btn-success">Weitere News einsenden</a></div>
                </div>
                <?php
            }
        }
    } else {
        ?>
      <form action="index.php?page=news&amp;subpage=add&amp;add=YES" method="post" novalidate>
          <fieldset>
              <legend class="h6"><?php echo L_NEWS_WRITENEWS; ?></legend>

<?php if ($pnconfig['categories'] == 'YES') { ?>
              <div class="mb-3">
                  <label class="form-label fw-bold"><?php echo L_NEWS_CATEGORY; ?></label>
                  <?php
                  $news = new news();
                  $news->getcatdropdown();
                  ?>
                  <div class="form-text"><?php echo L_NEWS_CATEGORY_DESC; ?></div>
              </div>
<?php } ?>

              <div class="mb-3">
                  <label class="form-label fw-bold"><?php echo L_NEWS_TIME; ?></label>
                  <div class="d-flex flex-wrap gap-2 align-items-center">
                      <select class="form-select form-select-sm w-auto" name="time[day]" aria-label="<?php echo L_NEWS_DAY; ?>">
                          <option value=""><?php echo L_NEWS_DAY; ?></option>
<?php
                          $this_day = date('d');
                          for ($i = 1; $i < 32; ++$i) {
                              if ($i < 10) {
                                  ?><option value="<?php echo $i; ?>" <?php echo $this_day == $i ? 'selected' : ''; ?>>0<?php echo $i; ?></option><?php
                              } else {
                                  ?><option value="<?php echo $i; ?>" <?php echo $this_day == $i ? 'selected' : ''; ?>><?php echo $i; ?></option><?php
                              }
                          }
?>
                      </select>
                      <select class="form-select form-select-sm w-auto" name="time[month]" aria-label="<?php echo L_NEWS_MONTH; ?>">
                          <option value=""><?php echo L_NEWS_MONTH; ?></option>
<?php
                          $this_month = date('m');
                          for ($i = 1; $i < 13; ++$i) {
                              if ($i === 1) {
                                  $month = L_NEWS_JANUARY;
                              } elseif ($i === 2) {
                                  $month = L_NEWS_FEBRUARY;
                              } elseif ($i === 3) {
                                  $month = L_NEWS_MARCH;
                              } elseif ($i === 4) {
                                  $month = L_NEWS_APRIL;
                              } elseif ($i === 5) {
                                  $month = L_NEWS_MAY;
                              } elseif ($i === 6) {
                                  $month = L_NEWS_JUNE;
                              } elseif ($i === 7) {
                                  $month = L_NEWS_JULY;
                              } elseif ($i === 8) {
                                  $month = L_NEWS_AUGUST;
                              } elseif ($i === 9) {
                                  $month = L_NEWS_SEPTEMBER;
                              } elseif ($i === 10) {
                                  $month = L_NEWS_OCTOBER;
                              } elseif ($i === 11) {
                                  $month = L_NEWS_NOVEMBER;
                              } elseif ($i === 12) {
                                  $month = L_NEWS_DECEMBER;
                              }
                              ?><option value="<?php echo $i; ?>" <?php echo $this_month == $i ? 'selected' : ''; ?>><?php echo $month; ?></option><?php
                          }
?>
                      </select>
                      <select class="form-select form-select-sm w-auto" name="time[year]" aria-label="<?php echo L_NEWS_YEAR; ?>">
                          <option value=""><?php echo L_NEWS_YEAR; ?></option>
<?php
                          $this_year = date('Y');
                          ?><option value="<?php echo $this_year; ?>" selected><?php echo $this_year; ?></option><?php
                          ?><option value="<?php echo ++$this_year; ?>"><?php echo $this_year; ?></option><?php
?>
                      </select>
                      <span aria-hidden="true">&#64;</span>
                      <select class="form-select form-select-sm w-auto" name="time[hour]" aria-label="<?php echo L_NEWS_HOUR; ?>">
                          <option value=""><?php echo L_NEWS_HOUR; ?></option>
<?php
                          $this_hour = date('H');
                          for ($i = 0; $i < 24; ++$i) {
                              if ($i < 10) {
                                  ?><option value="<?php echo $i; ?>" <?php echo $this_hour == $i ? 'selected' : ''; ?>>0<?php echo $i; ?></option><?php
                              } else {
                                  ?><option value="<?php echo $i; ?>" <?php echo $this_hour == $i ? 'selected' : ''; ?>><?php echo $i; ?></option><?php
                              }
                          }
?>
                      </select>
                      <span aria-hidden="true">:</span>
                      <select class="form-select form-select-sm w-auto" name="time[min]" aria-label="<?php echo L_NEWS_MIN; ?>">
                          <option value=""><?php echo L_NEWS_MIN; ?></option>
<?php
                          $this_min = date('i');
                          for ($i = 0; $i < 60; ++$i) {
                              if ($i < 10) {
                                  ?><option value="<?php echo $i; ?>" <?php echo $this_min == $i ? 'selected' : ''; ?>>0<?php echo $i; ?></option><?php
                              } else {
                                  ?><option value="<?php echo $i; ?>" <?php echo $this_min == $i ? 'selected' : ''; ?>><?php echo $i; ?></option><?php
                              }
                          }
?>
                      </select>
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
                      <?php echo L_NEWS_TEXT_DESC; ?>
                      (<a href="index.php?page=other&amp;subpage=help#other.html" target="_blank" rel="noopener noreferrer">HTML</a>
<?php
                      if ($pnconfig['html'] == 'News' || $pnconfig['html'] == 'Comments & News') {
                          ?><strong><?php echo L_NEWS_ON; ?></strong><?php
                      } else {
                          ?><strong><?php echo L_NEWS_OFF; ?></strong><?php
                      }
?> /
                      <a href="index.php?page=other&amp;subpage=help#other.bbcode" target="_blank" rel="noopener noreferrer">BB Code</a>
<?php
                      if ($pnconfig['bbcode'] == 'News' || $pnconfig['bbcode'] == 'Comments & News') {
                          ?><strong><?php echo L_NEWS_ON; ?></strong><?php
                      } else {
                          ?><strong><?php echo L_NEWS_OFF; ?></strong><?php
}
?>)
                  </div>
              </div>

<?php if ($pnconfig['moretext'] == 'YES') { ?>
              <div class="mb-3">
                  <label for="pn_moretext" class="form-label fw-bold"><?php echo L_NEWS_LONGTEXT; ?></label>
                  <textarea class="form-control" name="moretext" id="pn_moretext" rows="10" aria-describedby="pn_moretext_help"></textarea>
                  <div id="pn_moretext_help" class="form-text">
                      <?php echo L_NEWS_LONGTEXT_DESC; ?>
                      (<a href="index.php?page=other&amp;subpage=help#other.html" target="_blank" rel="noopener noreferrer">HTML</a>
<?php
                      if ($pnconfig['html'] == 'News' || $pnconfig['html'] == 'Comments & News') {
                          ?><strong><?php echo L_NEWS_ON; ?></strong><?php
                      } else {
                          ?><strong><?php echo L_NEWS_OFF; ?></strong><?php
                      }
?> /
                      <a href="index.php?page=other&amp;subpage=help#news.bbcode" target="_blank" rel="noopener noreferrer">BB Code</a>
<?php
                      if ($pnconfig['bbcode'] == 'News' || $pnconfig['bbcode'] == 'Comments & News') {
                          ?><strong><?php echo L_NEWS_ON; ?></strong><?php
                      } else {
                          ?><strong><?php echo L_NEWS_OFF; ?></strong><?php
                      }
?>)
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
                                  <td><input class="form-control form-control-sm" name="rl_title[]" maxlength="50" aria-label="Related Link Title"></td>
                                  <td><input class="form-control form-control-sm" name="rl_url[]" maxlength="250" aria-label="Related Link URL"></td>
                                  <td>
                                      <select class="form-select form-select-sm" name="rl_target[]" aria-label="Related Link Target">
<?php
                                          $counter = count($pn_config['rltargets']);
                                          for ($i2 = 0; $i2 < $counter; ++$i2) {
                                              ?><option value="<?php echo $pn_config['rltargets'][$i2]; ?>"><?php echo $pn_config['rltargets'][$i2]; ?></option><?php
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
