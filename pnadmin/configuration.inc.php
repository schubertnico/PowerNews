<?php

/* PowerNews is a PHP and mySQL based newsscript - www.powerscripts.org */
/* Copyright (C) 2001-2026 PowerScripts                                 */

/* This program is free software; you can redistribute it and/or modify */
/* it under the terms of the GNU General Public License as published by */
/* the Free Software Foundation; either version 2 of the License, or    */
/* (at your option) any later version.                                  */

/* This program is distributed in the hope that it will be useful,      */
/* but WITHOUT ANY WARRANTY; without even the implied warranty of       */
/* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        */
/* GNU General Public License for more details.                         */

/* You should have received a copy of the GNU General Public License    */
/* along with this program; if not, write to the Free Software          */
/* Foundation, Inc., 59 Temple Place, Suite 330, Boston,                */
/* MA  02111-1307  USA                                                  */

?>
<div class="card pn-admin-card mb-4">
    <h1 class="card-header h5 mb-0"><?php echo L_TITLE_CONFIGURATION; ?></h1>
    <div class="card-body">
<?php

if ($pnadmin['canreadconfig'] == 'YES') {

    $pn_configuration = new configuration();

    if (isset($_GET['edit']) && $_GET['edit'] == 'YES') {
        if ($pnadmin['canwriteconfig'] == 'YES') {
            $configData = ConfigData::fromPost();

            if ($configData->dateformat === '' || $configData->timeformat === '' || $configData->url === '' || $configData->email === '') {
                ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo L_CONF_FILLALL; ?>
                    <div class="mt-2"><a href="index.php?page=configuration" class="btn btn-sm btn-outline-secondary">Zur&uuml;ck zum Formular</a></div>
                </div>
                <?php
            } else {
                $error = $pn_configuration->editconfig($configData);

                if ($error !== '' && $error !== '0') {
                    ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo $error; ?>
                        <div class="mt-2"><a href="index.php?page=configuration" class="btn btn-sm btn-outline-secondary">Zur&uuml;ck zum Formular</a></div>
                    </div>
                    <?php
                } else {
                    ?>
                    <div class="alert alert-success" role="alert">
                        <?php echo L_CONF_EDITED; ?>
                        <div class="mt-2"><a href="index.php?page=configuration" class="btn btn-sm btn-success">Erneut bearbeiten</a></div>
                    </div>
                    <?php
                }
            }
        } else {
            ?><div class="alert alert-danger mb-0" role="alert"><?php echo L_ALL_ACCESSDENIED; ?></div><?php
        }
    } else {
        ?>
      <form action="index.php?page=configuration&amp;edit=YES" method="post" novalidate>

          <div class="mb-3">
              <label class="form-label fw-bold"><?php echo L_CONF_CATEGORIES; ?></label>
              <div>
                  <div class="form-check form-check-inline">
                      <input class="form-check-input" type="radio" name="categories" value="YES" id="cfg_cat_yes" <?php if ($pnconfig['categories'] == 'YES') { echo 'checked'; } ?>>
                      <label class="form-check-label" for="cfg_cat_yes"><?php echo L_ALL_YES; ?></label>
                  </div>
                  <div class="form-check form-check-inline">
                      <input class="form-check-input" type="radio" name="categories" value="NO" id="cfg_cat_no" <?php if ($pnconfig['categories'] == 'NO') { echo 'checked'; } ?>>
                      <label class="form-check-label" for="cfg_cat_no"><?php echo L_ALL_NO; ?></label>
                  </div>
              </div>
              <div class="form-text"><?php echo L_CONF_CATEGORIES_DESC; ?></div>
          </div>

<?php if ($pnconfig['categories'] == 'YES') { ?>
          <div class="mb-3">
              <label class="form-label fw-bold"><?php echo L_CONF_CATPICS; ?></label>
              <div>
                  <div class="form-check form-check-inline">
                      <input class="form-check-input" type="radio" name="categorypics" value="YES" id="cfg_catpics_yes" <?php if ($pnconfig['categorypics'] == 'YES') { echo 'checked'; } ?>>
                      <label class="form-check-label" for="cfg_catpics_yes"><?php echo L_ALL_YES; ?></label>
                  </div>
                  <div class="form-check form-check-inline">
                      <input class="form-check-input" type="radio" name="categorypics" value="NO" id="cfg_catpics_no" <?php if ($pnconfig['categorypics'] == 'NO') { echo 'checked'; } ?>>
                      <label class="form-check-label" for="cfg_catpics_no"><?php echo L_ALL_NO; ?></label>
                  </div>
              </div>
              <div class="form-text"><?php echo L_CONF_CATPICS_DESC; ?></div>
          </div>
<?php } ?>

          <div class="mb-3">
              <label class="form-label fw-bold"><?php echo L_CONF_COMMENTS; ?></label>
              <div>
                  <div class="form-check form-check-inline">
                      <input class="form-check-input" type="radio" name="comments" value="YES" id="cfg_com_yes" <?php if ($pnconfig['comments'] == 'YES') { echo 'checked'; } ?>>
                      <label class="form-check-label" for="cfg_com_yes"><?php echo L_ALL_YES; ?></label>
                  </div>
                  <div class="form-check form-check-inline">
                      <input class="form-check-input" type="radio" name="comments" value="NO" id="cfg_com_no" <?php if ($pnconfig['comments'] == 'NO') { echo 'checked'; } ?>>
                      <label class="form-check-label" for="cfg_com_no"><?php echo L_ALL_NO; ?></label>
                  </div>
              </div>
              <div class="form-text"><?php echo L_CONF_COMMENTS_DESC; ?></div>
          </div>

<?php if ($pnconfig['comments'] == 'YES') { ?>
          <div class="mb-3">
              <label for="cfg_commentwriting" class="form-label fw-bold"><?php echo L_CONF_WRITECOMMENTS; ?></label>
              <select class="form-select" name="commentwriting" id="cfg_commentwriting" aria-describedby="cfg_commentwriting_help">
                  <option value="Guests/Registered" <?php if ($pnconfig['commentwriting'] == 'Guests/Registered') { echo 'selected'; } ?>><?php echo L_CONF_GUESTSANDREGS; ?></option>
                  <option value="Registered" <?php if ($pnconfig['commentwriting'] == 'Registered') { echo 'selected'; } ?>><?php echo L_CONF_REGS; ?></option>
              </select>
              <div id="cfg_commentwriting_help" class="form-text"><?php echo L_CONF_WRITECOMMENTS_DESC; ?></div>
          </div>
<?php } ?>

          <div class="mb-3">
              <label class="form-label fw-bold"><?php echo L_CONF_MORETEXT; ?></label>
              <div>
                  <div class="form-check form-check-inline">
                      <input class="form-check-input" type="radio" name="moretext" value="YES" id="cfg_mtext_yes" <?php if ($pnconfig['moretext'] == 'YES') { echo 'checked'; } ?>>
                      <label class="form-check-label" for="cfg_mtext_yes"><?php echo L_ALL_YES; ?></label>
                  </div>
                  <div class="form-check form-check-inline">
                      <input class="form-check-input" type="radio" name="moretext" value="NO" id="cfg_mtext_no" <?php if ($pnconfig['moretext'] == 'NO') { echo 'checked'; } ?>>
                      <label class="form-check-label" for="cfg_mtext_no"><?php echo L_ALL_NO; ?></label>
                  </div>
              </div>
              <div class="form-text"><?php echo L_CONF_MORETEXT_DESC; ?></div>
          </div>

          <div class="mb-3">
              <label class="form-label fw-bold"><?php echo L_CONF_SENDIN; ?></label>
              <div>
                  <div class="form-check form-check-inline">
                      <input class="form-check-input" type="radio" name="sendnews" value="YES" id="cfg_sendnews_yes" <?php if ($pnconfig['sendnews'] == 'YES') { echo 'checked'; } ?>>
                      <label class="form-check-label" for="cfg_sendnews_yes"><?php echo L_ALL_YES; ?></label>
                  </div>
                  <div class="form-check form-check-inline">
                      <input class="form-check-input" type="radio" name="sendnews" value="NO" id="cfg_sendnews_no" <?php if ($pnconfig['sendnews'] == 'NO') { echo 'checked'; } ?>>
                      <label class="form-check-label" for="cfg_sendnews_no"><?php echo L_ALL_NO; ?></label>
                  </div>
              </div>
          </div>

<?php if ($pnconfig['sendnews'] == 'YES') { ?>
          <div class="mb-3">
              <label for="cfg_newssending" class="form-label fw-bold"><?php echo L_CONF_SENDNEWS; ?></label>
              <select class="form-select" name="newssending" id="cfg_newssending" aria-describedby="cfg_newssending_help">
                  <option value="Guests/Registered" <?php if ($pnconfig['newssending'] == 'Guests/Registered') { echo 'selected'; } ?>><?php echo L_CONF_GUESTSANDREGS; ?></option>
                  <option value="Registered" <?php if ($pnconfig['newssending'] == 'Registered') { echo 'selected'; } ?>><?php echo L_CONF_REGS; ?></option>
              </select>
              <div id="cfg_newssending_help" class="form-text"><?php echo L_CONF_SENDNEWS_DESC; ?></div>
          </div>
<?php } ?>

          <div class="mb-3">
              <label for="cfg_smilies" class="form-label fw-bold"><?php echo L_CONF_SMILIES; ?></label>
              <select class="form-select" name="smilies" id="cfg_smilies" aria-describedby="cfg_smilies_help">
                  <option value="NO" <?php if ($pnconfig['smilies'] == 'NO') { echo 'selected'; } ?>><?php echo L_ALL_NO; ?></option>
                  <option value="Comments" <?php if ($pnconfig['smilies'] == 'Comments') { echo 'selected'; } ?>><?php echo L_CONF_COMMENTS; ?></option>
                  <option value="Comments/News" <?php if ($pnconfig['smilies'] == 'Comments/News') { echo 'selected'; } ?>><?php echo L_CONF_COMMENTSANDNEWS; ?></option>
                  <option value="News" <?php if ($pnconfig['smilies'] == 'News') { echo 'selected'; } ?>><?php echo L_CONF_NEWS; ?></option>
              </select>
              <div id="cfg_smilies_help" class="form-text"><?php echo L_CONF_SMILIES_DESC; ?></div>
          </div>

          <div class="mb-3">
              <label for="cfg_bbcode" class="form-label fw-bold"><?php echo L_CONF_BBCODE; ?></label>
              <select class="form-select" name="bbcode" id="cfg_bbcode" aria-describedby="cfg_bbcode_help">
                  <option value="NO" <?php if ($pnconfig['bbcode'] == 'NO') { echo 'selected'; } ?>><?php echo L_ALL_NO; ?></option>
                  <option value="Comments" <?php if ($pnconfig['bbcode'] == 'Comments') { echo 'selected'; } ?>><?php echo L_CONF_COMMENTS; ?></option>
                  <option value="Comments/News" <?php if ($pnconfig['bbcode'] == 'Comments/News') { echo 'selected'; } ?>><?php echo L_CONF_COMMENTSANDNEWS; ?></option>
                  <option value="News" <?php if ($pnconfig['bbcode'] == 'News') { echo 'selected'; } ?>><?php echo L_CONF_NEWS; ?></option>
              </select>
              <div id="cfg_bbcode_help" class="form-text"><?php echo L_CONF_BBCODE_DESC; ?></div>
          </div>

          <div class="mb-3">
              <label for="cfg_html" class="form-label fw-bold"><?php echo L_CONF_HTML; ?></label>
              <select class="form-select" name="html" id="cfg_html" aria-describedby="cfg_html_help">
                  <option value="NO" <?php if ($pnconfig['html'] == 'NO') { echo 'selected'; } ?>><?php echo L_ALL_NO; ?></option>
                  <option value="Comments" <?php if ($pnconfig['html'] == 'Comments') { echo 'selected'; } ?>><?php echo L_CONF_COMMENTS; ?></option>
                  <option value="Comments/News" <?php if ($pnconfig['html'] == 'Comments/News') { echo 'selected'; } ?>><?php echo L_CONF_COMMENTSANDNEWS; ?></option>
                  <option value="News" <?php if ($pnconfig['html'] == 'News') { echo 'selected'; } ?>><?php echo L_CONF_NEWS; ?></option>
              </select>
              <div id="cfg_html_help" class="form-text"><?php echo L_CONF_HTML_DESC; ?></div>
          </div>

          <div class="row g-3 mb-3">
              <div class="col-12 col-md-6">
                  <label for="cfg_dateformat" class="form-label fw-bold"><?php echo L_CONF_DATEFORMAT; ?></label>
                  <input class="form-control" name="dateformat" id="cfg_dateformat" maxlength="50" value="<?php echo pnadmin_escape($pnconfig['dateformat']); ?>" required aria-describedby="cfg_dateformat_help">
                  <div id="cfg_dateformat_help" class="form-text"><?php echo L_CONF_DATEFORMAT_DESC; ?></div>
              </div>
              <div class="col-12 col-md-6">
                  <label for="cfg_timeformat" class="form-label fw-bold"><?php echo L_CONF_TIMEFORMAT; ?></label>
                  <input class="form-control" name="timeformat" id="cfg_timeformat" maxlength="50" value="<?php echo pnadmin_escape($pnconfig['timeformat']); ?>" required aria-describedby="cfg_timeformat_help">
                  <div id="cfg_timeformat_help" class="form-text"><?php echo L_CONF_TIMEFORMAT_DESC; ?></div>
              </div>
          </div>

          <div class="mb-3">
              <label for="cfg_template" class="form-label fw-bold"><?php echo L_CONF_TEMPLATE; ?></label>
              <select class="form-select" name="template" id="cfg_template" aria-describedby="cfg_template_help">
                  <?php $pn_configuration->listtemplates(); ?>
              </select>
              <div id="cfg_template_help" class="form-text"><?php echo L_CONF_TEMPLATE_DESC; ?></div>
          </div>

          <div class="mb-3">
              <label for="cfg_url" class="form-label fw-bold"><?php echo L_CONF_URL; ?></label>
              <input class="form-control" name="url" id="cfg_url" maxlength="250" value="<?php echo pnadmin_escape($pnconfig['url']); ?>" required aria-describedby="cfg_url_help">
              <div id="cfg_url_help" class="form-text"><?php echo L_CONF_URL_DESC; ?></div>
          </div>

          <div class="mb-3">
              <label for="cfg_email" class="form-label fw-bold"><?php echo L_CONF_EMAIL; ?></label>
              <input type="email" class="form-control" name="email" id="cfg_email" maxlength="250" value="<?php echo pnadmin_escape($pnconfig['email']); ?>" required aria-describedby="cfg_email_help">
              <div id="cfg_email_help" class="form-text"><?php echo L_CONF_EMAIL_DESC; ?></div>
          </div>

          <div class="row g-3 mb-3">
              <div class="col-12 col-md-4">
                  <label for="cfg_headlines" class="form-label fw-bold"><?php echo L_CONF_HEADLINES; ?></label>
                  <input class="form-control" name="headlines" id="cfg_headlines" maxlength="2" value="<?php echo pnadmin_escape($pnconfig['headlines']); ?>" aria-describedby="cfg_headlines_help">
                  <div id="cfg_headlines_help" class="form-text"><?php echo L_CONF_HEADLINES_DESC; ?></div>
              </div>
              <div class="col-12 col-md-4">
                  <label for="cfg_news" class="form-label fw-bold"><?php echo L_CONF_NEWS; ?></label>
                  <input class="form-control" name="news" id="cfg_news" maxlength="2" value="<?php echo pnadmin_escape($pnconfig['news']); ?>">
              </div>
              <div class="col-12 col-md-4">
                  <label for="cfg_spam" class="form-label fw-bold"><?php echo L_CONF_SPAMPROTECT; ?></label>
                  <input class="form-control" name="spamprotection" id="cfg_spam" maxlength="3" value="<?php echo pnadmin_escape($pnconfig['spamprotection']); ?>" aria-describedby="cfg_spam_help">
                  <div id="cfg_spam_help" class="form-text"><?php echo L_CONF_SPAMPROTECT_DESC; ?></div>
              </div>
          </div>

          <div class="mb-3">
              <label class="form-label fw-bold"><?php echo L_CONF_RELATEDLINKS; ?></label>
              <div>
                  <div class="form-check form-check-inline">
                      <input class="form-check-input" type="radio" name="relatedlinks" value="YES" id="cfg_rl_yes" <?php if ($pnconfig['relatedlinks'] == 'YES') { echo 'checked'; } ?>>
                      <label class="form-check-label" for="cfg_rl_yes"><?php echo L_ALL_YES; ?></label>
                  </div>
                  <div class="form-check form-check-inline">
                      <input class="form-check-input" type="radio" name="relatedlinks" value="NO" id="cfg_rl_no" <?php if ($pnconfig['relatedlinks'] == 'NO') { echo 'checked'; } ?>>
                      <label class="form-check-label" for="cfg_rl_no"><?php echo L_ALL_NO; ?></label>
                  </div>
              </div>
              <div class="form-text"><?php echo L_CONF_RELATEDLINKS_DESC; ?></div>
          </div>

<?php if ($pnconfig['relatedlinks'] == 'YES') { ?>
          <div class="mb-3">
              <label for="cfg_rl_num" class="form-label fw-bold"><?php echo L_CONF_RELATEDLINKS_NUM; ?></label>
              <input class="form-control" name="relatedlinks_num" id="cfg_rl_num" maxlength="2" value="<?php echo pnadmin_escape($pnconfig['relatedlinks_num']); ?>" aria-describedby="cfg_rl_num_help">
              <div id="cfg_rl_num_help" class="form-text"><?php echo L_CONF_RELATEDLINKS_NUM_DESC; ?></div>
          </div>
<?php } ?>

          <div class="d-flex gap-2">
              <button type="submit" class="btn btn-primary"><?php echo L_CONF_EDITCONFIG; ?></button>
              <button type="reset" class="btn btn-outline-secondary"><?php echo L_ALL_RESETDATA; ?></button>
          </div>
      </form>
      <?php
      }

  } else {
      ?><div class="alert alert-danger mb-0" role="alert"><?php echo L_ALL_ACCESSDENIED; ?></div><?php
  }

?>
    </div>
</div>
