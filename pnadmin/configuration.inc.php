<?PHP
/************************************************************************/
/* PowerNews is a PHP and mySQL based newsscript - www.powerscripts.org */
/* Copyright (C) 2001-2023 PowerScripts                                 */
/*                                                                      */
/* This program is free software; you can redistribute it and/or modify */
/* it under the terms of the GNU General Public License as published by */
/* the Free Software Foundation; either version 2 of the License, or    */
/* (at your option) any later version.                                  */
/*                                                                      */
/* This program is distributed in the hope that it will be useful,      */
/* but WITHOUT ANY WARRANTY; without even the implied warranty of       */
/* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        */
/* GNU General Public License for more details.                         */
/*                                                                      */
/* You should have received a copy of the GNU General Public License    */
/* along with this program; if not, write to the Free Software          */
/* Foundation, Inc., 59 Temple Place, Suite 330, Boston,                */
/* MA  02111-1307  USA                                                  */
/************************************************************************/
?>
<tr><td bgcolor="#3F5070" align="center"><b>

       <b class="headline"><?PHP echo L_TITLE_CONFIGURATION; ?></b>

</b></td></tr>

</td><td bgcolor="#001F3F" valign="top">

<?PHP

  if ($pnadmin['canreadconfig'] == "YES") {

    $pn_configuration = new configuration;
    if (isset($_GET['edit']) && $_GET['edit'] == "YES") {
      if ($pnadmin['canwriteconfig'] == "YES") {
        if (!trim((string) $_GET['dateformat']) || !trim((string) $_GET['timeformat']) || !trim((string) $_GET['url']) || !trim((string) $_GET['email']) || !trim((string) $_GET['headlines']) || !trim((string) $_GET['news']) || !trim((string) $_GET['spamprotection'])) {
          ?><center><a href="javascript:history.back()"><?PHP echo L_CONF_FILLALL; ?></a></center><?PHP
        } else {
          $error = $pn_configuration->editconfig($_GET['categories'], $_GET['categorypics'], $_GET['comments'], $_GET['commentwriting'], $_GET['moretext'],
            $_GET['sendnews'], $_GET['newssending'], $_POST['smilies'], $_POST['bbcode'], $_POST['html'], $_GET['dateformat'], $_GET['timeformat'],
            $_GET['template'], $_GET['url'], $_GET['email'], $_GET['headlines'], $_GET['news'], $_GET['spamprotection'], $_GET['relatedlinks'], $_GET['relatedlinks_num']);
          if ($error !== '' && $error !== '0') {
            ?><center><a href="javascript:history.back()"><?PHP echo $error; ?></a></center><?PHP
          } else {
            ?><center><a href="index.php?page=configuration"><?PHP echo L_CONF_EDITED; ?></a></center><?PHP
          }

        }
      } else {
        ?><center><?PHP echo L_ALL_ACCESSDENIED; ?></center><?PHP
      }
    } else {
      ?>
      <center>
      <form action="index.php?page=configuration&edit=YES" method="post">
      <table border="0" cellpadding="4" cellspacing="0">
      <tr><td>
      <b><?PHP echo L_CONF_CATEGORIES; ?></b><br>
      <small class="info"><?PHP echo L_CONF_CATEGORIES_DESC; ?></small>
      </td><td>
      <input type="radio" name="categories" value="YES" <?PHP if ($pnconfig['categories'] == "YES") { echo "checked"; } ?> > <?PHP echo L_ALL_YES; ?>
      <input type="radio" name="categories" value="NO" <?PHP if ($pnconfig['categories'] == "NO") { echo "checked"; } ?>> <?PHP echo L_ALL_NO; ?>
      </td></tr>
      <?PHP if ($pnconfig['categories'] == "YES") { ?>
      <tr><td>
      <b><?PHP echo L_CONF_CATPICS; ?></b><br>
      <small class="info"><?PHP echo L_CONF_CATPICS_DESC; ?></small>
      </td><td>
      <input type="radio" name="categorypics" value="YES" <?PHP if ($pnconfig['categorypics'] == "YES") { echo "checked"; } ?> > <?PHP echo L_ALL_YES; ?>
      <input type="radio" name="categorypics" value="NO" <?PHP if ($pnconfig['categorypics'] == "NO") { echo "checked"; } ?>> <?PHP echo L_ALL_NO; ?>
      </td></tr>
      <?PHP } ?>
      <tr><td>
      <b><?PHP echo L_CONF_COMMENTS; ?></b><br>
      <small class="info"><?PHP echo L_CONF_COMMENTS_DESC; ?></small>
      </td><td>
      <input type="radio" name="comments" value="YES" <?PHP if ($pnconfig['comments'] == "YES") { echo "checked"; } ?> > <?PHP echo L_ALL_YES; ?>
      <input type="radio" name="comments" value="NO" <?PHP if ($pnconfig['comments'] == "NO") { echo "checked"; } ?>> <?PHP echo L_ALL_NO; ?>
      </td></tr>
      <?PHP if ($pnconfig['comments'] == "YES") { ?>
      <tr><td>
      <b><?PHP echo L_CONF_WRITECOMMENTS; ?></b><br>
      <small class="info"><?PHP echo L_CONF_WRITECOMMENTS_DESC; ?></small>
      </td><td>
      <select name="commentwriting" size="1">
        <option value="Guests/Registered" <?PHP if ($pnconfig['commentwriting'] == "Guests/Registered") { echo "selected"; } ?>><?PHP echo L_CONF_GUESTSANDREGS; ?></option>
        <option value="Registered" <?PHP if ($pnconfig['commentwriting'] == "Registered") { echo "selected"; } ?>><?PHP echo L_CONF_REGS; ?></option>
      </select>
      </td></tr>
      <?PHP } ?>
      <tr><td>
      <b><?PHP echo L_CONF_MORETEXT; ?></b><br>
      <small class="info"><?PHP echo L_CONF_MORETEXT_DESC; ?></small>
      </td><td>
      <input type="radio" name="moretext" value="YES" <?PHP if ($pnconfig['moretext'] == "YES") { echo "checked"; } ?> > <?PHP echo L_ALL_YES; ?>
      <input type="radio" name="moretext" value="NO" <?PHP if ($pnconfig['moretext'] == "NO") { echo "checked"; } ?>> <?PHP echo L_ALL_NO; ?>
      </td></tr>
      <tr><td>
      <b><?PHP echo L_CONF_SENDIN; ?></b><br>
      <small class="info"></small>
      </td><td>
      <input type="radio" name="sendnews" value="YES" <?PHP if ($pnconfig['sendnews'] == "YES") { echo "checked"; } ?> > <?PHP echo L_ALL_YES; ?>
      <input type="radio" name="sendnews" value="NO" <?PHP if ($pnconfig['sendnews'] == "NO") { echo "checked"; } ?>> <?PHP echo L_ALL_NO; ?>
      </td></tr>
      <?PHP if ($pnconfig['sendnews'] == "YES") { ?>
      <tr><td>
      <b><?PHP echo L_CONF_SENDNEWS; ?></b><br>
      <small class="info"><?PHP echo L_CONF_SENDNEWS_DESC; ?></small>
      </td><td>
       <select name="newssending" size="1">
        <option value="Guests/Registered" <?PHP if ($pnconfig['newssending'] == "Guests/Registered") { echo "selected"; } ?>><?PHP echo L_CONF_GUESTSANDREGS; ?></option>
        <option value="Registered" <?PHP if ($pnconfig['newssending'] == "Registered") { echo "selected"; } ?>><?PHP echo L_CONF_REGS; ?></option>
      </select>
      </td></tr>
      <?PHP } ?>
      <tr><td>
      <b><?PHP echo L_CONF_SMILIES; ?></b><br>
      <small class="info"><?PHP echo L_CONF_SMILIES_DESC; ?></small>
      </td><td>
      <select name="smilies" size="1">
        <option value="NO" <?PHP if ($pnconfig['smilies'] == "NO") { echo "selected"; } ?>><?PHP echo L_ALL_NO; ?></option>
        <option value="Comments" <?PHP if ($pnconfig['smilies'] == "Comments") { echo "selected"; } ?>><?PHP echo L_CONF_COMMENTS; ?></option>
        <option value="Comments/News" <?PHP if ($pnconfig['smilies'] == "Comments/News") { echo "selected"; } ?>><?PHP echo L_CONF_COMMENTSANDNEWS; ?></option>
        <option value="News" <?PHP if ($pnconfig['smilies'] == "News") { echo "selected"; } ?>><?PHP echo L_CONF_NEWS; ?></option>
      </select>
      </td></tr>
      <tr><td>
      <b><?PHP echo L_CONF_BBCODE; ?></b><br>
      <small class="info"><?PHP echo L_CONF_BBCODE_DESC; ?></small>
      </td><td>
      <select name="bbcode" size="1">
        <option value="NO" <?PHP if ($pnconfig['bbcode'] == "NO") { echo "selected"; } ?>><?PHP echo L_ALL_NO; ?></option>
        <option value="Comments" <?PHP if ($pnconfig['bbcode'] == "Comments") { echo "selected"; } ?>><?PHP echo L_CONF_COMMENTS; ?></option>
        <option value="Comments/News" <?PHP if ($pnconfig['bbcode'] == "Comments/News") { echo "selected"; } ?>><?PHP echo L_CONF_COMMENTSANDNEWS; ?></option>
        <option value="News" <?PHP if ($pnconfig['bbcode'] == "News") { echo "selected"; } ?>><?PHP echo L_CONF_NEWS; ?></option>
      </select>
      </td></tr>
      <tr><td>
      <b><?PHP echo L_CONF_HTML; ?></b><br>
      <small class="info"><?PHP echo L_CONF_HTML_DESC; ?></small>
      </td><td>
      <select name="html" size="1">
        <option value="NO" <?PHP if ($pnconfig['html'] == "NO") { echo "selected"; } ?>><?PHP echo L_ALL_NO; ?></option>
        <option value="Comments" <?PHP if ($pnconfig['html'] == "Comments") { echo "selected"; } ?>><?PHP echo L_CONF_COMMENTS; ?></option>
        <option value="Comments/News" <?PHP if ($pnconfig['html'] == "Comments/News") { echo "selected"; } ?>><?PHP echo L_CONF_COMMENTSANDNEWS; ?></option>
        <option value="News" <?PHP if ($pnconfig['html'] == "News") { echo "selected"; } ?>><?PHP echo L_CONF_NEWS; ?></option>
      </select>
      </td></tr>
      <tr><td>
      <b><?PHP echo L_CONF_DATEFORMAT; ?></b><br>
      <small class="info"><?PHP echo L_CONF_DATEFORMAT_DESC; ?></small>
      </td><td>
      <input name="dateformat" size="25" maxlength="50" value="<?PHP echo $pnconfig['dateformat']; ?>">
      </td></tr>
      <tr><td>
      <b><?PHP echo L_CONF_TIMEFORMAT; ?></b><br>
      <small class="info"><?PHP echo L_CONF_TIMEFORMAT_DESC; ?></small>
      </td><td>
      <input name="timeformat" size="25" maxlength="50" value="<?PHP echo $pnconfig['timeformat']; ?>">
      </td></tr>
      <tr><td>
      <b><?PHP echo L_CONF_TEMPLATE; ?></b><br>
      <small class="info"><?PHP echo L_CONF_TEMPLATE_DESC; ?></small>
      </td><td>
      <select name="template" size="1">
        <?PHP $pn_configuration->listtemplates(); ?>
      </select>
      </td></tr>
      <tr><td>
      <b><?PHP echo L_CONF_URL; ?></b><br>
      <small class="info"><?PHP echo L_CONF_URL_DESC; ?></small>
      </td><td>
      <input name="url" size="25" maxlength="250" value="<?PHP echo $pnconfig['url']; ?>">
      </td></tr>
      <tr><td>
      <b><?PHP echo L_CONF_EMAIL; ?></b><br>
      <small class="info"><?PHP echo L_CONF_EMAIL_DESC; ?></small>
      </td><td>
      <input name="email" size="25" maxlength="250" value="<?PHP echo $pnconfig['email']; ?>">
      </td></tr>
      <tr><td>
      <b><?PHP echo L_CONF_HEADLINES; ?></b><br>
      <small class="info"><?PHP echo L_CONF_HEADLINES_DESC; ?></small>
      </td><td>
      <input name="headlines" size="1" maxlength="2" value="<?PHP echo $pnconfig['headlines']; ?>">
      </td></tr>
      <tr><td>
      <b><?PHP echo L_CONF_NEWS; ?></b><br>
      <small class="info"></small>
      </td><td>
      <input name="news" size="1" maxlength="2" value="<?PHP echo $pnconfig['news']; ?>">
      </td></tr>
      <tr><td>
      <b><?PHP echo L_CONF_SPAMPROTECT; ?></b><br>
      <small class="info"><?PHP echo L_CONF_SPAMPROTECT_DESC; ?></small>
      </td><td>
      <input name="spamprotection" size="2" maxlength="3" value="<?PHP echo $pnconfig['spamprotection']; ?>">
      </td></tr>
      <tr><td>
      <b><?PHP echo L_CONF_RELATEDLINKS; ?></b><br>
      <small class="info"><?PHP echo L_CONF_RELATEDLINKS_DESC; ?></small>
      </td><td>
      <input type="radio" name="relatedlinks" value="YES" <?PHP if ($pnconfig['relatedlinks'] == "YES") { echo "checked"; } ?> > <?PHP echo L_ALL_YES; ?>
      <input type="radio" name="relatedlinks" value="NO" <?PHP if ($pnconfig['relatedlinks'] == "NO") { echo "checked"; } ?>> <?PHP echo L_ALL_NO; ?>
      </td></tr>
      <?PHP if ($pnconfig['relatedlinks'] == "YES") { ?>
      <tr><td>
      <b><?PHP echo L_CONF_RELATEDLINKS_NUM; ?></b><br>
      <small class="info"><?PHP echo L_CONF_RELATEDLINKS_NUM_DESC; ?></small>
      </td><td>
      <input name="relatedlinks_num" size="1" maxlength="2" value="<?PHP echo $pnconfig['relatedlinks_num']; ?>">
      </td></tr>
      <?PHP } ?>
      <tr><td colspan="2" align="center">
      <input type="submit" value="<?PHP echo L_CONF_EDITCONFIG; ?>"> <input type="reset" value="<?PHP echo L_ALL_RESETDATA; ?>">
      </td></tr>
      </table>
      </form>
      </center>
      <?PHP
    }

  } else {
    ?><center><?PHP echo L_ALL_ACCESSDENIED; ?></center><?PHP
  }

?>

</td></tr>