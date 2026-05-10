<?php
declare(strict_types=1);

/* PowerNews - PHP and MySQL based news script                         */
/* Copyright (c) 2001-2026 PowerScripts                                 */

/* MIT License - See LICENSE file for full license text                 */
/* https://github.com/schubertnico/PowerNews.git                        */

// Validierte Parameter
$newsid = pn_get_id('newsid');
$edit = pn_get_string('edit', 10);
$editcomments = pn_get_string('editcomments', 10);
$catid = pn_get_id('catid');
$title = pn_get_string('title', 150);
$delete = pn_get_string('delete', 10);
$moretext = pn_get_string('moretext', 65535);

if ($pnadmin['canreadnews'] == 'YES' && $pnadmin['canwritenews'] == 'YES') {
    if ($newsid > 0) {
        $editnews = new news();
        $error = $editnews->checknews($newsid);

        if ($error !== '' && $error !== '0') {
            ?>
            <div class="alert alert-danger" role="alert">
                <?php echo pnadmin_escape($error); ?>
                <div class="mt-2"><a href="index.php?page=news&amp;subpage=show" class="btn btn-sm btn-outline-secondary">Zur&uuml;ck zur Liste</a></div>
            </div>
            <?php
        } else {
            if ($edit === 'YES') {
                if ($editcomments === 'YES') {
                    $error = $editnews->checkcomment($_POST['commentid'] ?? [], $_POST['commenttext'] ?? []);

                    if ($error !== '' && $error !== '0') {
                        ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo pnadmin_escape($error); ?>
                            <div class="mt-2"><a href="index.php?page=news&amp;subpage=edit&amp;newsid=<?php echo pn_int($newsid); ?>" class="btn btn-sm btn-outline-secondary">Zur&uuml;ck</a></div>
                        </div>
                        <?php
                    } else {
                        $error = $editnews->editcomment($_POST['commentid'] ?? [], $_POST['commenttext'] ?? [], $_POST['commentdelete'] ?? []);

                        if ($error !== '' && $error !== '0') {
                            ?>
                            <div class="alert alert-danger" role="alert">
                                <?php echo pnadmin_escape($error); ?>
                                <div class="mt-2"><a href="index.php?page=news&amp;subpage=edit&amp;newsid=<?php echo pn_int($newsid); ?>" class="btn btn-sm btn-outline-secondary">Zur&uuml;ck zum Formular</a></div>
                            </div>
                            <?php
                        } else {
                            ?>
                            <div class="alert alert-success" role="alert">
                                <?php echo L_NEWS_COMMENTSEDITED; ?>
                                <div class="mt-2"><a href="index.php?page=news&amp;subpage=edit&amp;newsid=<?php echo pn_int($newsid); ?>" class="btn btn-sm btn-success">Zur&uuml;ck</a></div>
                            </div>
                            <?php
                        }
                    }
                } else {
                    $text = pn_post_string('text', 65535);
                    $status = pn_validate_status($_POST['status'] ?? '');
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
                            <div class="mt-2"><a href="index.php?page=news&amp;subpage=edit&amp;newsid=<?php echo pn_int($newsid); ?>" class="btn btn-sm btn-outline-secondary">Zur&uuml;ck zum Formular</a></div>
                        </div>
                        <?php
                    } else {
                        $error = $editnews->editnews($newsid, $catid, $title, $text, $moretext, $status, $delete, $rl_title, $rl_url, $rl_target, $time);

                        if ($error !== '' && $error !== '0') {
                            ?>
                            <div class="alert alert-danger" role="alert">
                                <?php echo pnadmin_escape($error); ?>
                                <div class="mt-2"><a href="index.php?page=news&amp;subpage=edit&amp;newsid=<?php echo pn_int($newsid); ?>" class="btn btn-sm btn-outline-secondary">Zur&uuml;ck zum Formular</a></div>
                            </div>
                            <?php
                        } else {
                            if ($delete === 'YES') {
                                ?>
                                <div class="alert alert-success" role="alert">
                                    <?php echo L_NEWS_NEWSDELETED; ?>
                                    <div class="mt-2"><a href="index.php?page=news&amp;subpage=show" class="btn btn-sm btn-success">Zur&uuml;ck zur Liste</a></div>
                                </div>
                                <?php
                            } else {
                                ?>
                                <div class="alert alert-success" role="alert">
                                    <?php echo L_NEWS_NEWSEDITED; ?>
                                    <div class="mt-2"><a href="index.php?page=news&amp;subpage=edit&amp;newsid=<?php echo pn_int($newsid); ?>" class="btn btn-sm btn-success">Erneut bearbeiten</a></div>
                                </div>
                                <?php
                            }
                        }
                    }
                }
            } else {
                $data = $editnews->getnewsdata($newsid);

                if (isset($data['showemail']) && $data['showemail'] == 'YES') {
                    $showemail = 'checked';
                }
                ?>
          <form action="index.php?page=news&amp;subpage=edit&amp;edit=YES&amp;newsid=<?php echo pn_int($newsid); ?>" method="post" novalidate>
              <fieldset>
                  <legend class="h6"><?php echo L_NEWS_EDITNEWS; ?></legend>

                  <div class="pn-danger-action mb-3">
                      <div class="form-check">
                          <input class="form-check-input" type="checkbox" name="delete" value="YES" id="pn_delete" aria-describedby="pn_delete_help">
                          <label class="form-check-label fw-bold text-danger" for="pn_delete"><?php echo L_NEWS_DELETE; ?></label>
                          <div id="pn_delete_help" class="form-text"><strong>Achtung:</strong> <?php echo L_NEWS_DELETE_DESC; ?></div>
                      </div>
                  </div>

<?php if ($pnconfig['categories'] == 'YES') { ?>
                  <div class="mb-3">
                      <label class="form-label fw-bold"><?php echo L_NEWS_CATEGORY; ?></label>
<?php
                      $news = new news();
                      $news->getcatdropdown($data['catid']);
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
                              $this_day = date('d', $data['time']);
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
                              $this_month = date('m', $data['time']);
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
                              $this_year = date('Y', $data['time']);
                              ?><option value="<?php echo $this_year; ?>" selected><?php echo $this_year; ?></option><?php
                              ?><option value="<?php echo ++$this_year; ?>"><?php echo $this_year; ?></option><?php
?>
                          </select>
                          <span aria-hidden="true">&#64;</span>
                          <select class="form-select form-select-sm w-auto" name="time[hour]" aria-label="<?php echo L_NEWS_HOUR; ?>">
                              <option value=""><?php echo L_NEWS_HOUR; ?></option>
<?php
                              $this_hour = date('H', $data['time']);
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
                              $this_min = date('i', $data['time']);
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
                      <input class="form-control" name="title" id="pn_title" maxlength="150" value="<?php echo pnadmin_escape($data['title']); ?>" required aria-describedby="pn_title_help">
                      <div id="pn_title_help" class="form-text"><?php echo L_NEWS_TITLE_DESC; ?></div>
                  </div>

                  <div class="mb-3">
                      <label for="pn_text" class="form-label fw-bold"><?php echo L_NEWS_TEXT; ?></label>
                      <textarea class="form-control" name="text" id="pn_text" rows="10" required aria-describedby="pn_text_help"><?php echo pnadmin_escape($data['text']); ?></textarea>
                      <div id="pn_text_help" class="form-text">
                          <?php echo L_NEWS_TEXT_DESC; ?>
                      </div>
                  </div>

<?php if ($pnconfig['moretext'] == 'YES') { ?>
                  <div class="mb-3">
                      <label for="pn_moretext" class="form-label fw-bold"><?php echo L_NEWS_LONGTEXT; ?></label>
                      <textarea class="form-control" name="moretext" id="pn_moretext" rows="10" aria-describedby="pn_moretext_help"><?php echo pnadmin_escape($data['moretext']); ?></textarea>
                      <div id="pn_moretext_help" class="form-text"><?php echo L_NEWS_LONGTEXT_DESC; ?></div>
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
                              /* Get related links */
                              $links = explode("\n", (string) $data['relatedlinks']);
                              $counter = count($links);
                              $link = [];
                              for ($i = 0; $i < $counter; ++$i) {
                                  $link[$i] = explode('!@!@!', $links[$i]);
                              }

                              /* List forms for related links */
                              for ($i = 0; $i < $pnconfig['relatedlinks_num']; ++$i) {
                                  ?>
                                  <tr>
                                      <td><input class="form-control form-control-sm" name="rl_title[]" maxlength="50" value="<?php echo pnadmin_escape($link[$i][0] ?? ''); ?>" aria-label="Related Link Title"></td>
                                      <td><input class="form-control form-control-sm" name="rl_url[]" maxlength="250" value="<?php echo pnadmin_escape($link[$i][1] ?? ''); ?>" aria-label="Related Link URL"></td>
                                      <td>
                                          <select class="form-select form-select-sm" name="rl_target[]" aria-label="Related Link Target">
<?php
                                              $tcounter = count($pn_config['rltargets']);
                                              for ($i2 = 0; $i2 < $tcounter; ++$i2) {
                                                  ?><option value="<?php echo pnadmin_escape($pn_config['rltargets'][$i2]); ?>" <?php echo ($pn_config['rltargets'][$i2] ?? '') == ($link[$i][2] ?? '') ? 'selected' : ''; ?>><?php echo pnadmin_escape($pn_config['rltargets'][$i2]); ?></option><?php
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

                  <div class="mb-3">
                      <label for="pn_status" class="form-label fw-bold"><?php echo L_NEWS_STATUS; ?></label>
                      <select class="form-select" name="status" id="pn_status" aria-describedby="pn_status_help">
                          <option value="Activated" <?php if ($data['status'] == 'Activated') {
                              echo 'selected';
                          } ?>><?php echo L_ALL_ACTIVATED; ?></option>
                          <option value="Deactivated" <?php if ($data['status'] == 'Deactivated') {
                              echo 'selected';
                          } ?>><?php echo L_ALL_DEACTIVATED; ?></option>
                          <option value="Unchecked" <?php if ($data['status'] == 'Unchecked') {
                              echo 'selected';
                          } ?>><?php echo L_ALL_UNCHECKED; ?></option>
                      </select>
                      <div id="pn_status_help" class="form-text"><?php echo L_NEWS_STATUS_DESC; ?></div>
                  </div>

                  <div class="d-flex gap-2">
                      <button type="submit" class="btn btn-primary"><?php echo L_NEWS_EDITNEWS; ?></button>
                      <button type="reset" class="btn btn-outline-secondary"><?php echo L_ALL_RESETDATA; ?></button>
                  </div>
              </fieldset>
          </form>
          <?php
          if ($pnconfig['comments'] == 'YES' && $pnadmin['canreadcomments'] == 'YES') {
              ?>
            <form action="index.php?page=news&amp;subpage=edit&amp;edit=YES&amp;newsid=<?php echo pn_int($newsid); ?>&amp;editcomments=YES" method="post" class="mt-4" novalidate>
                <fieldset>
                    <legend class="h6"><?php echo L_NEWS_EDITCOMMENTS; ?></legend>
<?php
                    $editnews->getcomments($newsid);
?>
                    <div class="d-flex gap-2 mt-3">
                        <button type="submit" class="btn btn-primary"><?php echo L_NEWS_EDITCOMMENTS; ?></button>
                        <button type="reset" class="btn btn-outline-secondary"><?php echo L_ALL_RESETDATA; ?></button>
                    </div>
                </fieldset>
            </form>
            <?php
          }
            }
        }
    } else {
        ?>
        <div class="alert alert-info" role="alert">
            <?php echo L_NEWS_CHOOSENEWS; ?>
            <div class="mt-2"><a href="index.php?page=news&amp;subpage=show" class="btn btn-sm btn-primary">Zur&uuml;ck zur Liste</a></div>
        </div>
        <?php
    }
} else {
    ?><div class="alert alert-danger mb-0" role="alert"><?php echo L_ALL_ACCESSDENIED; ?></div><?php
}
?>
