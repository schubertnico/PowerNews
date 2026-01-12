<?php
declare(strict_types=1);

/* PowerNews - PHP and MySQL based news script                         */
/* Copyright (c) 2001-2024 PowerScripts                                 */

/* MIT License - See LICENSE file for full license text                 */
/* https://github.com/schubertnico/PowerNews.git                        */

if ($pnadmin['canreadtemplates'] == 'YES' && $pnadmin['canwritetemplates'] == 'YES') {

    if ($_GET['templateid']) {
        $edittemplate = new template();
        $error = $edittemplate->checktemplate($_GET['templateid']);

        if ($error !== '' && $error !== '0') {
            ?><center><a href="index.php?page=templates&subpage=show"><?php echo pnadmin_escape($error); ?></a></center><?php
        } else {
            if (isset($_GET['edit']) && $_GET['edit'] == 'YES') {
                $edittemplate->edittemplate(
                    (int) $_GET['templateid'],
                    $_POST['delete'] ?? '',
                    TemplateData::fromPost(),
                );
            } else {
                $data = $edittemplate->gettemplatedata($_GET['templateid']);
                ?>
          <center>
          <form action="index.php?page=templates&subpage=edit&edit=YES&templateid=<?php echo pnadmin_escape($_GET['templateid']); ?>" method="post">
          <table border="0" cellpadding="4" cellspacing="0">
          <tr><td colspan="2" align="center">
          <b><?php echo L_TEMPL_EDITTEMPLATE; ?></b>
          </td></tr>
          <tr><td colspan="2" align="center" bgcolor="#3F5070">
          <b class="headline"><?php echo L_TEMPL_GENERAL; ?></b>
          </td></tr>
          <tr><td>
          <b><?php echo L_TEMPL_DELETE; ?></b><br>
          <small class="info"><?php echo L_TEMPL_DELETE_DESC; ?></small>
          </td><td>
          <input type="checkbox" name="delete" value="YES">
          </td></tr>
          <tr><td>
          <b><?php echo L_TEMPL_TITLE; ?></b><br>
          <small class="info"><?php echo L_TEMPL_TITLE_DESC; ?></small>
          </td><td>
          <input name="title" size="50" maxlength="100" value="<?php echo pnadmin_escape($data['title']); ?>">
          </td></tr>
          <tr><td colspan="2" align="center" bgcolor="#3F5070">
          <b class="headline"><?php echo L_TEMPL_OUTPUT; ?></b>
          </td></tr>
          <tr><td valign="top">
          <b><?php echo L_TEMPL_MESSAGE; ?></b><br>
          <small class="info"><?php echo L_TEMPL_MESSAGE_DESC; ?><br><br>
          <b>{LINK}</b> - <b>{MESSAGE}</b>
          </small>
          </td><td>
          <textarea name="message" cols="100" rows="20"><?php echo pnadmin_escape($data['message']); ?></textarea>
          </td></tr>
          <tr><td valign="top">
          <b><?php echo L_TEMPL_HEADLINES; ?></b><br>
          <small class="info"><?php echo L_TEMPL_HEADLINES_DESC; ?><br><br>
          <b>{ID}</b> - <b>{DATE}</b> - <b>{TIME}</b> - <b>{CATEGORY}</b> - <b>{TITLE}</b> - <b>{CATPIC}</b> - <b>{CATID}</b>
          </small>
          </td><td>
          <textarea name="headline" cols="100" rows="5"><?php echo pnadmin_escape($data['headline']); ?></textarea>
          </td></tr>
          <tr><td valign="top">
          <b><?php echo L_TEMPL_NEWS; ?></b><br>
          <small class="info"><?php echo L_TEMPL_NEWS_DESC; ?><br><br>
          <b>{ID}</b> - <b>{DATE}</b> - <b>{TIME}</b> - <b>{CATEGORY}</b> - <b>{TITLE}</b> - <b>{CATPIC}</b> - <b>{CATID}</b> - <b>{AUTHOR}</b> - <b>{TEXT}</b> - <b>{COMMENTS}</b> - <b>{RELATEDLINKS}</b> - <b>{MORE}</b>
          </small>
          </td><td>
          <textarea name="news" cols="100" rows="20"><?php echo pnadmin_escape($data['news']); ?></textarea>
          </td></tr>
          <tr><td valign="top">
          <b><?php echo L_TEMPL_COMMENTS; ?></b><br>
          <small class="info"><?php echo L_TEMPL_COMMENTS_DESC; ?><br><br>
          <b>{ID}</b> - <b>{DATE}</b> - <b>{TIME}</b> - <b>{AUTHOR}</b> - <b>{TEXT}</b>
          </small>
          </td><td>
          <textarea name="comment" cols="100" rows="20"><?php echo pnadmin_escape($data['comment']); ?></textarea>
          </td></tr>
          <tr><td valign="top">
          <b><?php echo L_TEMPL_USERMENU; ?></b><br>
          <small class="info"><?php echo L_TEMPL_USERMENU1_DESC; ?></small>
          </td><td>
          <textarea name="usermenu" cols="100" rows="10"><?php echo pnadmin_escape($data['usermenu']); ?></textarea>
          </td></tr>
           <tr><td valign="top">
          <b><?php echo L_TEMPL_USERMENU; ?></b><br>
          <small class="info"><?php echo L_TEMPL_USERMENU2_DESC; ?></small>
          </td><td>
          <textarea name="usermenu2" cols="100" rows="10"><?php echo pnadmin_escape($data['usermenu2']); ?></textarea>
          </td></tr>
          <tr><td valign="top">
          <b><?php echo L_TEMPL_RELATEDLINKS; ?></b><br>
          <small class="info"><?php echo L_TEMPL_RELATEDLINKS_DESC; ?><br>
          <br>
          <b>{TITLE}</b> - <b>{URL}</b> - <b>{TARGET}</b></small>
          </td><td>
          <textarea name="relatedlinks" cols="100" rows="5"><?php echo pnadmin_escape($data['relatedlinks']); ?></textarea>
          </td></tr>
          <tr><td colspan="2" align="center" bgcolor="#3F5070">
          <b class="headline"><?php echo L_TEMPL_INPUT; ?></b>
          </td></tr>
          <tr><td valign="top">
          <b><?php echo L_TEMPL_COMMENTFORM; ?></b><br>
          <small class="info"><?php echo L_TEMPL_COMMENTFORM_DESC; ?><br><br>
          <b>{NEWSID}</b> - <b>{NAME}</b><br>
          <br>
          <u><?php echo L_TEMPL_FORMTARGET; ?>:</u> <?php echo pnadmin_escape($pn_config['commentfile']); ?>?newsid={NEWSID}
          </small>
          </td><td>
          <textarea name="commentform" cols="100" rows="20"><?php echo pnadmin_escape($data['commentform']); ?></textarea>
          </td></tr>
          <tr><td valign="top">
          <b><?php echo L_TEMPL_REGISTERFORM; ?></b><br>
          <small class="info"><?php echo L_TEMPL_REGISTERFORM_DESC; ?>n<br>
          <br>
          <u><?php echo L_TEMPL_FORMTARGET; ?>:</u> <?php echo pnadmin_escape($pn_config['userfile']); ?>?pndata[send]=YES
          </small>
          </td><td>
          <textarea name="registerform" cols="100" rows="20"><?php echo pnadmin_escape($data['registerform']); ?></textarea>
          </td></tr>
          <tr><td valign="top">
          <b><?php echo L_TEMPL_LOGINFORM; ?></b><br>
          <small class="info"><?php echo L_TEMPL_LOGINFORM_DESC; ?><br>
          <br>
          <u><?php echo L_TEMPL_FORMTARGET; ?>:</u> <?php echo pnadmin_escape($pn_config['userfile']); ?>?page=login&pndata[login]=YES
          </small>
          </td><td>
          <textarea name="loginform" cols="100" rows="20"><?php echo pnadmin_escape($data['loginform']); ?></textarea>
          </td></tr>
          <tr><td valign="top">
          <b><?php echo L_TEMPL_LOGOUTFORM; ?></b><br>
          <small class="info"><?php echo L_TEMPL_LOGOUTFORM_DESC; ?><br>
          <br>
          <b>{NICKNAME}</b><br>
          <br>
          <u><?php echo L_TEMPL_LINKTARGET; ?></u> <?php echo pnadmin_escape($pn_config['userfile']); ?>?page=logout&pndata[logout]=YES
          </small>
          </td><td>
          <textarea name="logout" cols="100" rows="20"><?php echo pnadmin_escape($data['logout']); ?></textarea>
          </td></tr>
          <tr><td valign="top">
          <b><?php echo L_TEMPL_SENDDATAFORM; ?></b><br>
          <small class="info"><?php echo L_TEMPL_SENDDATAFORM_DESC; ?><br>
          <br>
          <u><?php echo L_TEMPL_FORMTARGET; ?>:</u> <?php echo pnadmin_escape($pn_config['userfile']); ?>?page=senddata
          </small>
          </td><td>
          <textarea name="senddataform" cols="100" rows="20"><?php echo pnadmin_escape($data['senddataform']); ?></textarea>
          </td></tr>
          <tr><td valign="top">
          <b><?php echo L_TEMPL_PROFILEFORM; ?></b><br>
          <small class="info"><?php echo L_TEMPL_PROFILEFORM_DESC; ?><br>
          <br><b>{NICKNAME}</b> - <b>{EMAIL}</b> - <b>{SHOWEMAIL}</b> - <b>{PASSWORD}</b><br>
          <br>
          <u><?php echo L_TEMPL_FORMTARGET; ?>:</u> <?php echo pnadmin_escape($pn_config['userfile']); ?>?page=profile&pndata[send]=YES
          </small>
          </small>
          </td><td>
          <textarea name="profileform" cols="100" rows="20"><?php echo pnadmin_escape($data['profileform']); ?></textarea>
          </td></tr>
          <tr><td valign="top">
          <b><?php echo L_TEMPL_ARCHIVEFORM; ?></b><br>
          <small class="info"><?php echo L_TEMPL_ARCHIVEFORM_DESC; ?><br><br>
          <b>{SELECTYEAR}</b> - <b>{SELECTMONTH}</b> - <b>{SEARCHSTRING}</b><br>
          <br>
          <u><?php echo L_TEMPL_FORMTARGET; ?> 1:</u> <?php echo pnadmin_escape($pn_config['archivefile']); ?><br>
          <u><?php echo L_TEMPL_FORMTARGET; ?> 2:</u> <?php echo pnadmin_escape($pn_config['archivefile']); ?>?pndata[type]=search<br>
          </small>
          </td><td>
          <textarea name="archive" cols="100" rows="20"><?php echo pnadmin_escape($data['archive']); ?></textarea>
          </td></tr>
          <tr><td valign="top">
          <b><?php echo L_TEMPL_SENDNEWSFORM; ?></b><br>
          <small class="info"><?php echo L_TEMPL_SENDNEWSFORM_DESC; ?><br>
          <br>
          <b>{USER}</b> - <b>{CATEGORYSELECT}</b> - <b>{RELATEDLINKS}</b><br>
          <br>
          <u><?php echo L_TEMPL_FORMTARGET; ?>:</u> <?php echo pnadmin_escape($pn_config['sendnewsfile']); ?>?pndata[send]=YES<br>
          </small>
          </td><td>
          <textarea name="sendnewsform" cols="100" rows="20"><?php echo pnadmin_escape($data['sendnewsform']); ?></textarea>
          </td></tr>
           <tr><td colspan="2" align="center" bgcolor="#3F5070">
          <b class="headline"><?php echo L_TEMPL_EMAILS; ?></b>
          </td></tr>
          <tr><td valign="top">
          <b><?php echo L_TEMPL_USERADDEDMAIL; ?></b><br>
          <small class="info"><?php echo L_TEMPL_USERADDEDMAIL_DESC; ?><br><br>
          <b>{NICKNAME}</b> - <b>{URL}</b> - <b>{EMAIL}</b> - <b>{PASSWORD}</b>
          </small>
          </td><td>
          <textarea name="addemail" cols="100" rows="20"><?php echo pnadmin_escape($data['addemail']); ?></textarea>
          </td></tr>
          <tr><td valign="top">
          <b><?php echo L_TEMPL_USEREDITEDMAIL; ?></b><br>
          <small class="info"><?php echo L_TEMPL_USEREDITEDMAIL_DESC; ?><br><br>
          <b>{NICKNAME}</b> - <b>{URL}</b> - <b>{EMAIL}</b> - <b>{PASSWORD}</b>
          </small>
          </td><td>
          <textarea name="editemail" cols="100" rows="20"><?php echo pnadmin_escape($data['editemail']); ?></textarea>
          </td></tr>
          <tr><td valign="top">
          <b><?php echo L_TEMPL_USERREGISTEREDMAIL; ?></b><br>
          <small class="info"><?php echo L_TEMPL_USERREGISTEREDMAIL_DESC; ?><br><br>
          <b>{NICKNAME}</b> - <b>{URL}</b> - <b>{EMAIL}</b> - <b>{PASSWORD}</b>
          </small>
          </td><td>
          <textarea name="registeremail" cols="100" rows="20"><?php echo pnadmin_escape($data['registeremail']); ?></textarea>
          </td></tr>
          <tr><td valign="top">
          <b><?php echo L_TEMPL_DATAMAIL; ?></b><br>
          <small class="info"><?php echo L_TEMPL_DATAMAIL_DESC; ?><br><br>
          <b>{NICKNAME}</b> - <b>{URL}</b> - <b>{EMAIL}</b> - <b>{PASSWORD}</b>
          </small>
          </td><td>
          <textarea name="dataemail" cols="100" rows="20"><?php echo pnadmin_escape($data['dataemail']); ?></textarea>
          </td></tr>
          <tr><td colspan="2" align="center">
          <input type="submit" value="<?php echo L_TEMPL_EDITTEMPLATE; ?>"> <input type="reset" value="<?php echo L_ALL_RESETDATA; ?>">
          </td></tr>
          </table>
          </form>
          </center>
          <?php
            }
        }
    } else {
        ?><center><a href="index.php?page=templates&subpage=show"><?php echo L_TEMPL_CHOOSETEMPLATE; ?></a></center><?php
    }

} else {
    ?><center><?php echo L_ALL_ACCESSDENIED; ?></center><?php
}
?>
