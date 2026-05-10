<?php
declare(strict_types=1);

/* PowerNews - PHP and MySQL based news script                         */
/* Copyright (c) 2001-2026 PowerScripts                                 */

/* MIT License - See LICENSE file for full license text                 */
/* https://github.com/schubertnico/PowerNews.git                        */

if ($pnadmin['canreadtemplates'] == 'YES' && $pnadmin['canwritetemplates'] == 'YES') {

    if ((int) ($_GET['templateid'] ?? 0)) {
        $edittemplate = new template();
        $error = $edittemplate->checktemplate((int) ($_GET['templateid'] ?? 0));

        // Default-Template (id=1) darf editiert, aber NICHT geloescht werden.
        // Beim Loeschen wuerde die Vorlage fuer "Template hinzufuegen" wegbrechen.
        $isDefaultTemplate = ((int) (int) ($_GET['templateid'] ?? 0) === 1);

        if ($error !== '' && $error !== '0') {
            ?>
            <div class="alert alert-danger" role="alert">
                <?php echo pnadmin_escape($error); ?>
                <div class="mt-2"><a href="index.php?page=templates&amp;subpage=show" class="btn btn-sm btn-outline-secondary">Zur&uuml;ck zur Liste</a></div>
            </div>
            <?php
        } else {
            if (isset($_GET['edit']) && $_GET['edit'] == 'YES') {
                $edittemplate->edittemplate(
                    (int) (int) ($_GET['templateid'] ?? 0),
                    $_POST['delete'] ?? '',
                    TemplateData::fromPost(),
                );
            } else {
                $data = $edittemplate->gettemplatedata((int) ($_GET['templateid'] ?? 0));
                ?>
<?php if ($isDefaultTemplate) { ?>
          <div class="alert alert-warning" role="alert">
              <strong>Hinweis:</strong> Du editierst das <strong>Default-Template</strong> (ID&nbsp;1). Dieses Template wird beim Anlegen neuer Templates als Grundlage kopiert. &Auml;nderungen wirken sich also auch auf alle k&uuml;nftig neu angelegten Templates aus. L&ouml;schen ist aus diesem Grund nicht m&ouml;glich.
          </div>
<?php } ?>
          <form action="index.php?page=templates&amp;subpage=edit&amp;edit=YES&amp;templateid=<?php echo (int) ($_GET['templateid'] ?? 0); ?>" method="post" novalidate>
              <fieldset>
                  <legend class="h6"><?php echo L_TEMPL_EDITTEMPLATE; ?></legend>

                  <h2 class="h6 fw-bold mt-4 mb-3 border-bottom pb-2"><?php echo L_TEMPL_GENERAL; ?></h2>

<?php if (!$isDefaultTemplate) { ?>
                  <div class="pn-danger-action mb-3">
                      <div class="form-check">
                          <input class="form-check-input" type="checkbox" name="delete" value="YES" id="pn_tdelete" aria-describedby="pn_tdelete_help">
                          <label class="form-check-label fw-bold text-danger" for="pn_tdelete"><?php echo L_TEMPL_DELETE; ?></label>
                          <div id="pn_tdelete_help" class="form-text"><strong>Achtung:</strong> <?php echo L_TEMPL_DELETE_DESC; ?></div>
                      </div>
                  </div>
<?php } ?>

                  <div class="mb-3">
                      <label for="pn_ttitle" class="form-label fw-bold"><?php echo L_TEMPL_TITLE; ?></label>
                      <input class="form-control" name="title" id="pn_ttitle" maxlength="100" value="<?php echo pnadmin_escape($data['title']); ?>" required aria-describedby="pn_ttitle_help">
                      <div id="pn_ttitle_help" class="form-text"><?php echo L_TEMPL_TITLE_DESC; ?></div>
                  </div>

                  <h2 class="h6 fw-bold mt-4 mb-3 border-bottom pb-2"><?php echo L_TEMPL_OUTPUT; ?></h2>

                  <div class="mb-3">
                      <label for="pn_t_message" class="form-label fw-bold"><?php echo L_TEMPL_MESSAGE; ?></label>
                      <textarea class="form-control font-monospace small" name="message" id="pn_t_message" rows="6" aria-describedby="pn_t_message_help"><?php echo pnadmin_escape($data['message']); ?></textarea>
                      <div id="pn_t_message_help" class="form-text"><?php echo L_TEMPL_MESSAGE_DESC; ?> <code>{LINK}</code> <code>{MESSAGE}</code></div>
                  </div>

                  <div class="mb-3">
                      <label for="pn_t_headline" class="form-label fw-bold"><?php echo L_TEMPL_HEADLINES; ?></label>
                      <textarea class="form-control font-monospace small" name="headline" id="pn_t_headline" rows="3" aria-describedby="pn_t_headline_help"><?php echo pnadmin_escape($data['headline']); ?></textarea>
                      <div id="pn_t_headline_help" class="form-text"><?php echo L_TEMPL_HEADLINES_DESC; ?> <code>{ID}</code> <code>{DATE}</code> <code>{TIME}</code> <code>{CATEGORY}</code> <code>{TITLE}</code> <code>{CATPIC}</code> <code>{CATID}</code></div>
                  </div>

                  <div class="mb-3">
                      <label for="pn_t_news" class="form-label fw-bold"><?php echo L_TEMPL_NEWS; ?></label>
                      <textarea class="form-control font-monospace small" name="news" id="pn_t_news" rows="8" aria-describedby="pn_t_news_help"><?php echo pnadmin_escape($data['news']); ?></textarea>
                      <div id="pn_t_news_help" class="form-text"><?php echo L_TEMPL_NEWS_DESC; ?> <code>{ID}</code> <code>{DATE}</code> <code>{TIME}</code> <code>{CATEGORY}</code> <code>{TITLE}</code> <code>{CATPIC}</code> <code>{CATID}</code> <code>{AUTHOR}</code> <code>{TEXT}</code> <code>{COMMENTS}</code> <code>{RELATEDLINKS}</code> <code>{MORE}</code></div>
                  </div>

                  <div class="mb-3">
                      <label for="pn_t_comment" class="form-label fw-bold"><?php echo L_TEMPL_COMMENTS; ?></label>
                      <textarea class="form-control font-monospace small" name="comment" id="pn_t_comment" rows="6" aria-describedby="pn_t_comment_help"><?php echo pnadmin_escape($data['comment']); ?></textarea>
                      <div id="pn_t_comment_help" class="form-text"><?php echo L_TEMPL_COMMENTS_DESC; ?> <code>{ID}</code> <code>{DATE}</code> <code>{TIME}</code> <code>{AUTHOR}</code> <code>{TEXT}</code></div>
                  </div>

                  <div class="mb-3">
                      <label for="pn_t_usermenu" class="form-label fw-bold"><?php echo L_TEMPL_USERMENU; ?> 1</label>
                      <textarea class="form-control font-monospace small" name="usermenu" id="pn_t_usermenu" rows="4" aria-describedby="pn_t_usermenu_help"><?php echo pnadmin_escape($data['usermenu']); ?></textarea>
                      <div id="pn_t_usermenu_help" class="form-text"><?php echo L_TEMPL_USERMENU1_DESC; ?></div>
                  </div>

                  <div class="mb-3">
                      <label for="pn_t_usermenu2" class="form-label fw-bold"><?php echo L_TEMPL_USERMENU; ?> 2</label>
                      <textarea class="form-control font-monospace small" name="usermenu2" id="pn_t_usermenu2" rows="4" aria-describedby="pn_t_usermenu2_help"><?php echo pnadmin_escape($data['usermenu2']); ?></textarea>
                      <div id="pn_t_usermenu2_help" class="form-text"><?php echo L_TEMPL_USERMENU2_DESC; ?></div>
                  </div>

                  <div class="mb-3">
                      <label for="pn_t_relatedlinks" class="form-label fw-bold"><?php echo L_TEMPL_RELATEDLINKS; ?></label>
                      <textarea class="form-control font-monospace small" name="relatedlinks" id="pn_t_relatedlinks" rows="3" aria-describedby="pn_t_relatedlinks_help"><?php echo pnadmin_escape($data['relatedlinks']); ?></textarea>
                      <div id="pn_t_relatedlinks_help" class="form-text"><?php echo L_TEMPL_RELATEDLINKS_DESC; ?> <code>{TITLE}</code> <code>{URL}</code> <code>{TARGET}</code></div>
                  </div>

                  <h2 class="h6 fw-bold mt-4 mb-3 border-bottom pb-2"><?php echo L_TEMPL_INPUT; ?></h2>

                  <div class="mb-3">
                      <label for="pn_t_commentform" class="form-label fw-bold"><?php echo L_TEMPL_COMMENTFORM; ?></label>
                      <textarea class="form-control font-monospace small" name="commentform" id="pn_t_commentform" rows="8"><?php echo pnadmin_escape($data['commentform']); ?></textarea>
                      <div class="form-text"><?php echo L_TEMPL_COMMENTFORM_DESC; ?> <code>{NEWSID}</code> <code>{NAME}</code> <em><?php echo L_TEMPL_FORMTARGET; ?>:</em> <?php echo pnadmin_escape($pn_config['commentfile']); ?>?newsid={NEWSID}</div>
                  </div>

                  <div class="mb-3">
                      <label for="pn_t_registerform" class="form-label fw-bold"><?php echo L_TEMPL_REGISTERFORM; ?></label>
                      <textarea class="form-control font-monospace small" name="registerform" id="pn_t_registerform" rows="8"><?php echo pnadmin_escape($data['registerform']); ?></textarea>
                      <div class="form-text"><?php echo L_TEMPL_REGISTERFORM_DESC; ?> <em><?php echo L_TEMPL_FORMTARGET; ?>:</em> <?php echo pnadmin_escape($pn_config['userfile']); ?>?pndata[send]=YES</div>
                  </div>

                  <div class="mb-3">
                      <label for="pn_t_loginform" class="form-label fw-bold"><?php echo L_TEMPL_LOGINFORM; ?></label>
                      <textarea class="form-control font-monospace small" name="loginform" id="pn_t_loginform" rows="8"><?php echo pnadmin_escape($data['loginform']); ?></textarea>
                      <div class="form-text"><?php echo L_TEMPL_LOGINFORM_DESC; ?> <em><?php echo L_TEMPL_FORMTARGET; ?>:</em> <?php echo pnadmin_escape($pn_config['userfile']); ?>?page=login&amp;pndata[login]=YES</div>
                  </div>

                  <div class="mb-3">
                      <label for="pn_t_logout" class="form-label fw-bold"><?php echo L_TEMPL_LOGOUTFORM; ?></label>
                      <textarea class="form-control font-monospace small" name="logout" id="pn_t_logout" rows="6"><?php echo pnadmin_escape($data['logout']); ?></textarea>
                      <div class="form-text"><?php echo L_TEMPL_LOGOUTFORM_DESC; ?> <code>{NICKNAME}</code> <em><?php echo L_TEMPL_LINKTARGET; ?></em> <?php echo pnadmin_escape($pn_config['userfile']); ?>?page=logout&amp;pndata[logout]=YES</div>
                  </div>

                  <div class="mb-3">
                      <label for="pn_t_senddataform" class="form-label fw-bold"><?php echo L_TEMPL_SENDDATAFORM; ?></label>
                      <textarea class="form-control font-monospace small" name="senddataform" id="pn_t_senddataform" rows="8"><?php echo pnadmin_escape($data['senddataform']); ?></textarea>
                      <div class="form-text"><?php echo L_TEMPL_SENDDATAFORM_DESC; ?> <em><?php echo L_TEMPL_FORMTARGET; ?>:</em> <?php echo pnadmin_escape($pn_config['userfile']); ?>?page=senddata</div>
                  </div>

                  <div class="mb-3">
                      <label for="pn_t_profileform" class="form-label fw-bold"><?php echo L_TEMPL_PROFILEFORM; ?></label>
                      <textarea class="form-control font-monospace small" name="profileform" id="pn_t_profileform" rows="10"><?php echo pnadmin_escape($data['profileform']); ?></textarea>
                      <div class="form-text"><?php echo L_TEMPL_PROFILEFORM_DESC; ?> <code>{NICKNAME}</code> <code>{EMAIL}</code> <code>{SHOWEMAIL}</code> <code>{PASSWORD}</code> <em><?php echo L_TEMPL_FORMTARGET; ?>:</em> <?php echo pnadmin_escape($pn_config['userfile']); ?>?page=profile&amp;pndata[send]=YES</div>
                  </div>

                  <div class="mb-3">
                      <label for="pn_t_archive" class="form-label fw-bold"><?php echo L_TEMPL_ARCHIVEFORM; ?></label>
                      <textarea class="form-control font-monospace small" name="archive" id="pn_t_archive" rows="8"><?php echo pnadmin_escape($data['archive']); ?></textarea>
                      <div class="form-text"><?php echo L_TEMPL_ARCHIVEFORM_DESC; ?> <code>{SELECTYEAR}</code> <code>{SELECTMONTH}</code> <code>{SEARCHSTRING}</code></div>
                  </div>

                  <div class="mb-3">
                      <label for="pn_t_sendnewsform" class="form-label fw-bold"><?php echo L_TEMPL_SENDNEWSFORM; ?></label>
                      <textarea class="form-control font-monospace small" name="sendnewsform" id="pn_t_sendnewsform" rows="10"><?php echo pnadmin_escape($data['sendnewsform']); ?></textarea>
                      <div class="form-text"><?php echo L_TEMPL_SENDNEWSFORM_DESC; ?> <code>{USER}</code> <code>{CATEGORYSELECT}</code> <code>{RELATEDLINKS}</code></div>
                  </div>

                  <h2 class="h6 fw-bold mt-4 mb-3 border-bottom pb-2"><?php echo L_TEMPL_EMAILS; ?></h2>

                  <div class="mb-3">
                      <label for="pn_t_addemail" class="form-label fw-bold"><?php echo L_TEMPL_USERADDEDMAIL; ?></label>
                      <textarea class="form-control font-monospace small" name="addemail" id="pn_t_addemail" rows="6"><?php echo pnadmin_escape($data['addemail']); ?></textarea>
                      <div class="form-text"><?php echo L_TEMPL_USERADDEDMAIL_DESC; ?> <code>{NICKNAME}</code> <code>{URL}</code> <code>{EMAIL}</code> <code>{PASSWORD}</code></div>
                  </div>

                  <div class="mb-3">
                      <label for="pn_t_editemail" class="form-label fw-bold"><?php echo L_TEMPL_USEREDITEDMAIL; ?></label>
                      <textarea class="form-control font-monospace small" name="editemail" id="pn_t_editemail" rows="6"><?php echo pnadmin_escape($data['editemail']); ?></textarea>
                      <div class="form-text"><?php echo L_TEMPL_USEREDITEDMAIL_DESC; ?> <code>{NICKNAME}</code> <code>{URL}</code> <code>{EMAIL}</code> <code>{PASSWORD}</code></div>
                  </div>

                  <div class="mb-3">
                      <label for="pn_t_registeremail" class="form-label fw-bold"><?php echo L_TEMPL_USERREGISTEREDMAIL; ?></label>
                      <textarea class="form-control font-monospace small" name="registeremail" id="pn_t_registeremail" rows="6"><?php echo pnadmin_escape($data['registeremail']); ?></textarea>
                      <div class="form-text"><?php echo L_TEMPL_USERREGISTEREDMAIL_DESC; ?> <code>{NICKNAME}</code> <code>{URL}</code> <code>{EMAIL}</code> <code>{PASSWORD}</code></div>
                  </div>

                  <div class="mb-3">
                      <label for="pn_t_dataemail" class="form-label fw-bold"><?php echo L_TEMPL_DATAMAIL; ?></label>
                      <textarea class="form-control font-monospace small" name="dataemail" id="pn_t_dataemail" rows="6"><?php echo pnadmin_escape($data['dataemail']); ?></textarea>
                      <div class="form-text"><?php echo L_TEMPL_DATAMAIL_DESC; ?> <code>{NICKNAME}</code> <code>{URL}</code> <code>{EMAIL}</code> <code>{PASSWORD}</code></div>
                  </div>

                  <div class="d-flex gap-2">
                      <button type="submit" class="btn btn-primary"><?php echo L_TEMPL_EDITTEMPLATE; ?></button>
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
            <?php echo L_TEMPL_CHOOSETEMPLATE; ?>
            <div class="mt-2"><a href="index.php?page=templates&amp;subpage=show" class="btn btn-sm btn-primary">Zur&uuml;ck zur Liste</a></div>
        </div>
        <?php
    }

} else {
    ?><div class="alert alert-danger mb-0" role="alert"><?php echo L_ALL_ACCESSDENIED; ?></div><?php
}
?>
