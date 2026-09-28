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

if ($pnadmin['canreadnews'] == 'YES' && $pnadmin['canwritenews'] == 'YES') {
    if ($newsid > 0) {
        $editnews = new news();
        $error = $editnews->checknews($newsid);

        if ($error !== '' && $error !== '0') {
            ?>
            <div class="alert alert-danger" role="alert">
                <?php echo pnadmin_escape($error); ?>
                <div class="mt-2"><a href="index.php?page=news&amp;subpage=show" class="btn btn-sm btn-outline-secondary"><?php echo L_ALL_BACKTOLIST; ?></a></div>
            </div>
            <?php
        } else {
            if ($edit === 'YES') {
                if ($editcomments === 'YES' && $pnadmin['canwritecomments'] !== 'YES') {
                    // Kommentare ändern oder löschen nur mit „Kommentare schreiben“ (B39).
                    ?><div class="alert alert-danger mb-0" role="alert"><?php echo L_ALL_ACCESSDENIED; ?></div><?php
                } elseif ($editcomments === 'YES') {
                    $error = $editnews->checkcomment($_POST['commentid'] ?? [], $_POST['commenttext'] ?? []);

                    if ($error !== '' && $error !== '0') {
                        ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo pnadmin_escape($error); ?>
                            <div class="mt-2"><a href="index.php?page=news&amp;subpage=edit&amp;newsid=<?php echo pn_int($newsid); ?>" class="btn btn-sm btn-outline-secondary"><?php echo L_ALL_BACK; ?></a></div>
                        </div>
                        <?php
                    } else {
                        $error = $editnews->editcomment($_POST['commentid'] ?? [], $_POST['commenttext'] ?? [], $_POST['commentdelete'] ?? []);

                        if ($error !== '' && $error !== '0') {
                            ?>
                            <div class="alert alert-danger" role="alert">
                                <?php echo pnadmin_escape($error); ?>
                                <div class="mt-2"><a href="index.php?page=news&amp;subpage=edit&amp;newsid=<?php echo pn_int($newsid); ?>" class="btn btn-sm btn-outline-secondary"><?php echo L_ALL_BACKTOFORM; ?></a></div>
                            </div>
                            <?php
                        } elseif ($editnews->commentschanged === 0 && $editnews->commentsdeleted === 0) {
                            ?>
                            <div class="alert alert-info" role="alert">
                                <?php echo L_NEWS_NOCOMMENTCHANGES; ?>
                                <div class="mt-2"><a href="index.php?page=news&amp;subpage=edit&amp;newsid=<?php echo pn_int($newsid); ?>" class="btn btn-sm btn-outline-secondary"><?php echo L_ALL_BACK; ?></a></div>
                            </div>
                            <?php
                        } else {
                            ?>
                            <div class="alert alert-success" role="alert">
                                <?php echo L_NEWS_COMMENTSEDITED; ?>
                                <div class="mt-2"><a href="index.php?page=news&amp;subpage=edit&amp;newsid=<?php echo pn_int($newsid); ?>" class="btn btn-sm btn-success"><?php echo L_ALL_BACK; ?></a></div>
                            </div>
                            <?php
                        }
                    }
                } else {
                    // Alle Formularwerte kommen per POST (B02). Felder, die das Formular je nach
                    // Konfiguration nicht anzeigt, behalten ihren gespeicherten Wert.
                    $current = $editnews->getnewsdata($newsid) ?? [];
                    $delete = pn_validate_yesno($_POST['delete'] ?? '');
                    $title = pn_post_string('title', 150);
                    $text = pn_post_string('text', 65535);
                    $catid = isset($_POST['catid']) ? pn_post_id('catid') : (int) ($current['catid'] ?? 0);
                    $moretext = isset($_POST['moretext']) ? pn_post_string('moretext', 65535) : (string) ($current['moretext'] ?? '');
                    $status = pn_validate_status($_POST['status'] ?? '', (string) ($current['status'] ?? 'Activated'));
                    $rl_title = is_array($_POST['rl_title'] ?? null) ? $_POST['rl_title'] : [];
                    $rl_url = is_array($_POST['rl_url'] ?? null) ? $_POST['rl_url'] : [];
                    $rl_target = is_array($_POST['rl_target'] ?? null) ? $_POST['rl_target'] : [];
                    $time = is_array($_POST['time'] ?? null) ? $_POST['time'] : [];

                    if ($delete !== 'YES' && (($pnconfig['categories'] == 'YES' && $catid === 0) || $title === '' || $text === '')) {
                        ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo L_NEWS_TITLEANDTEXTNEEDED; ?>
                            <?php if ($pnconfig['categories'] == 'YES') {
                                echo L_NEWS_ALSOCATEGORY;
                            } ?>!
                            <div class="mt-2"><a href="index.php?page=news&amp;subpage=edit&amp;newsid=<?php echo pn_int($newsid); ?>" class="btn btn-sm btn-outline-secondary"><?php echo L_ALL_BACKTOFORM; ?></a></div>
                        </div>
                        <?php
                    } else {
                        $error = $editnews->editnews($newsid, $catid, $title, $text, $moretext, $status, $delete, $rl_title, $rl_url, $rl_target, $time);

                        if ($error !== '' && $error !== '0') {
                            ?>
                            <div class="alert alert-danger" role="alert">
                                <?php echo pnadmin_escape($error); ?>
                                <div class="mt-2"><a href="index.php?page=news&amp;subpage=edit&amp;newsid=<?php echo pn_int($newsid); ?>" class="btn btn-sm btn-outline-secondary"><?php echo L_ALL_BACKTOFORM; ?></a></div>
                            </div>
                            <?php
                        } else {
                            if ($delete === 'YES') {
                                ?>
                                <div class="alert alert-success" role="alert">
                                    <?php echo L_NEWS_NEWSDELETED; ?>
                                    <div class="mt-2"><a href="index.php?page=news&amp;subpage=show" class="btn btn-sm btn-success"><?php echo L_ALL_BACKTOLIST; ?></a></div>
                                </div>
                                <?php
                            } else {
                                ?>
                                <div class="alert alert-success" role="alert">
                                    <?php echo L_NEWS_NEWSEDITED; ?>
                                    <div class="mt-2"><a href="index.php?page=news&amp;subpage=edit&amp;newsid=<?php echo pn_int($newsid); ?>" class="btn btn-sm btn-success"><?php echo L_ALL_EDITAGAIN; ?></a></div>
                                </div>
                                <?php
                            }
                        }
                    }
                }
            } else {
                $data = $editnews->getnewsdata($newsid) ?? [];
                ?>
          <form action="index.php?page=news&amp;subpage=edit&amp;edit=YES&amp;newsid=<?php echo pn_int($newsid); ?>" method="post" novalidate><?php echo pnadmin_csrf_field(); ?>
              <fieldset>
                  <legend class="h6"><?php echo L_NEWS_EDITNEWS; ?></legend>

                  <div class="pn-danger-action mb-3">
                      <div class="form-check">
                          <input class="form-check-input" type="checkbox" name="delete" value="YES" id="pn_delete" aria-describedby="pn_delete_help">
                          <label class="form-check-label fw-bold text-danger" for="pn_delete"><?php echo L_NEWS_DELETE; ?></label>
                          <div id="pn_delete_help" class="form-text"><strong><?php echo L_ALL_ATTENTION; ?></strong> <?php echo L_NEWS_DELETE_DESC; ?></div>
                      </div>
                  </div>

<?php if ($pnconfig['categories'] == 'YES') { ?>
                  <div class="mb-3">
                      <label class="form-label fw-bold"><?php echo L_NEWS_CATEGORY; ?></label>
<?php $editnews->getcatdropdown((int) $data['catid']); ?>
                      <div class="form-text"><?php echo L_NEWS_CATEGORY_DESC; ?></div>
                  </div>
<?php } ?>

                  <div class="mb-3">
                      <label class="form-label fw-bold"><?php echo L_NEWS_TIME; ?></label>
                      <div class="d-flex flex-wrap gap-2 align-items-center">
                          <?php $editnews->timeselect((int) $data['time']); ?>
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
                          <?php echo L_NEWS_TEXT_DESC; ?> <?php echo $editnews->formathint(); ?>
                      </div>
                  </div>

<?php if ($pnconfig['moretext'] == 'YES') { ?>
                  <div class="mb-3">
                      <label for="pn_moretext" class="form-label fw-bold"><?php echo L_NEWS_LONGTEXT; ?></label>
                      <textarea class="form-control" name="moretext" id="pn_moretext" rows="10" aria-describedby="pn_moretext_help"><?php echo pnadmin_escape($data['moretext']); ?></textarea>
                      <div id="pn_moretext_help" class="form-text"><?php echo L_NEWS_LONGTEXT_DESC; ?> <?php echo $editnews->formathint(); ?></div>
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
                              /* Gespeicherte Links (JSON oder Altformat) */
                              $link = [];

    foreach (pn_relatedlinks_decode((string) $data['relatedlinks']) as $stored) {
        $link[] = [$stored['title'], $stored['url'], $stored['target']];
    }

    /* List forms for related links */
    for ($i = 0; $i < $pnconfig['relatedlinks_num']; ++$i) {
        ?>
                                  <tr>
                                      <td><input class="form-control form-control-sm" name="rl_title[]" maxlength="50" value="<?php echo pnadmin_escape($link[$i][0] ?? ''); ?>" aria-label="<?php echo L_NEWS_RL_TITLE; ?>"></td>
                                      <td><input class="form-control form-control-sm" name="rl_url[]" maxlength="250" value="<?php echo pnadmin_escape($link[$i][1] ?? ''); ?>" aria-label="<?php echo L_NEWS_RL_URL; ?>"></td>
                                      <td>
                                          <select class="form-select form-select-sm" name="rl_target[]" aria-label="<?php echo L_NEWS_RL_TARGET; ?>">
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
          if ($pnconfig['comments'] == 'YES' && $pnadmin['canreadcomments'] == 'YES' && $pnadmin['canwritecomments'] !== 'YES') {
              ?>
            <section class="mt-4">
                <h2 class="h6"><?php echo L_NEWS_COMMENTS; ?></h2>
<?php
                $editnews->getcommentsreadonly($newsid);
              ?>
            </section>
            <?php
          } elseif ($pnconfig['comments'] == 'YES' && $pnadmin['canreadcomments'] == 'YES') {
              ?>
            <form action="index.php?page=news&amp;subpage=edit&amp;edit=YES&amp;newsid=<?php echo pn_int($newsid); ?>&amp;editcomments=YES" method="post" class="mt-4" novalidate><?php echo pnadmin_csrf_field(); ?>
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
            <div class="mt-2"><a href="index.php?page=news&amp;subpage=show" class="btn btn-sm btn-primary"><?php echo L_ALL_BACKTOLIST; ?></a></div>
        </div>
        <?php
    }
} else {
    ?><div class="alert alert-danger mb-0" role="alert"><?php echo L_ALL_ACCESSDENIED; ?></div><?php
}
?>
