<?php

/* PowerNews is a PHP and mySQL based newsscript - www.powerscripts.org */
/* Copyright (C) 2001-2023 PowerScripts                                 */

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
<tr><td bgcolor="#3F5070" align="center"><b>

       <b class="headline"><?php echo L_TITLE_CONFIGURATION; ?></b>

</b></td></tr>

</td><td bgcolor="#001F3F" valign="top">

<?php

  if ($pnadmin['canreadconfig'] == 'YES') {

      $pn_configuration = new configuration();

      if (isset($_GET['edit']) && $_GET['edit'] == 'YES') {
          if ($pnadmin['canwriteconfig'] == 'YES') {
              $configData = ConfigData::fromPost();

              if ($configData->dateformat === '' || $configData->timeformat === '' || $configData->url === '' || $configData->email === '') {
                  ?><center><a href="javascript:history.back()"><?php echo L_CONF_FILLALL; ?></a></center><?php
              } else {
                  $error = $pn_configuration->editconfig($configData);

                  if ($error !== '' && $error !== '0') {
                      ?><center><a href="javascript:history.back()"><?php echo $error; ?></a></center><?php
                  } else {
                      ?><center><a href="index.php?page=configuration"><?php echo L_CONF_EDITED; ?></a></center><?php
                  }
              }
          } else {
              ?><center><?php echo L_ALL_ACCESSDENIED; ?></center><?php
          }
      } else {
          ?>
      <center>
      <form action="index.php?page=configuration&edit=YES" method="post">
      <table border="0" cellpadding="4" cellspacing="0">
      <tr><td>
      <b><?php echo L_CONF_CATEGORIES; ?></b><br>
      <small class="info"><?php echo L_CONF_CATEGORIES_DESC; ?></small>
      </td><td>
      <input type="radio" name="categories" value="YES" <?php if ($pnconfig['categories'] == 'YES') {
          echo 'checked';
      } ?> > <?php echo L_ALL_YES; ?>
      <input type="radio" name="categories" value="NO" <?php if ($pnconfig['categories'] == 'NO') {
          echo 'checked';
      } ?>> <?php echo L_ALL_NO; ?>
      </td></tr>
      <?php if ($pnconfig['categories'] == 'YES') { ?>
      <tr><td>
      <b><?php echo L_CONF_CATPICS; ?></b><br>
      <small class="info"><?php echo L_CONF_CATPICS_DESC; ?></small>
      </td><td>
      <input type="radio" name="categorypics" value="YES" <?php if ($pnconfig['categorypics'] == 'YES') {
          echo 'checked';
      } ?> > <?php echo L_ALL_YES; ?>
      <input type="radio" name="categorypics" value="NO" <?php if ($pnconfig['categorypics'] == 'NO') {
          echo 'checked';
      } ?>> <?php echo L_ALL_NO; ?>
      </td></tr>
      <?php } ?>
      <tr><td>
      <b><?php echo L_CONF_COMMENTS; ?></b><br>
      <small class="info"><?php echo L_CONF_COMMENTS_DESC; ?></small>
      </td><td>
      <input type="radio" name="comments" value="YES" <?php if ($pnconfig['comments'] == 'YES') {
          echo 'checked';
      } ?> > <?php echo L_ALL_YES; ?>
      <input type="radio" name="comments" value="NO" <?php if ($pnconfig['comments'] == 'NO') {
          echo 'checked';
      } ?>> <?php echo L_ALL_NO; ?>
      </td></tr>
      <?php if ($pnconfig['comments'] == 'YES') { ?>
      <tr><td>
      <b><?php echo L_CONF_WRITECOMMENTS; ?></b><br>
      <small class="info"><?php echo L_CONF_WRITECOMMENTS_DESC; ?></small>
      </td><td>
      <select name="commentwriting" size="1">
        <option value="Guests/Registered" <?php if ($pnconfig['commentwriting'] == 'Guests/Registered') {
            echo 'selected';
        } ?>><?php echo L_CONF_GUESTSANDREGS; ?></option>
        <option value="Registered" <?php if ($pnconfig['commentwriting'] == 'Registered') {
            echo 'selected';
        } ?>><?php echo L_CONF_REGS; ?></option>
      </select>
      </td></tr>
      <?php } ?>
      <tr><td>
      <b><?php echo L_CONF_MORETEXT; ?></b><br>
      <small class="info"><?php echo L_CONF_MORETEXT_DESC; ?></small>
      </td><td>
      <input type="radio" name="moretext" value="YES" <?php if ($pnconfig['moretext'] == 'YES') {
          echo 'checked';
      } ?> > <?php echo L_ALL_YES; ?>
      <input type="radio" name="moretext" value="NO" <?php if ($pnconfig['moretext'] == 'NO') {
          echo 'checked';
      } ?>> <?php echo L_ALL_NO; ?>
      </td></tr>
      <tr><td>
      <b><?php echo L_CONF_SENDIN; ?></b><br>
      <small class="info"></small>
      </td><td>
      <input type="radio" name="sendnews" value="YES" <?php if ($pnconfig['sendnews'] == 'YES') {
          echo 'checked';
      } ?> > <?php echo L_ALL_YES; ?>
      <input type="radio" name="sendnews" value="NO" <?php if ($pnconfig['sendnews'] == 'NO') {
          echo 'checked';
      } ?>> <?php echo L_ALL_NO; ?>
      </td></tr>
      <?php if ($pnconfig['sendnews'] == 'YES') { ?>
      <tr><td>
      <b><?php echo L_CONF_SENDNEWS; ?></b><br>
      <small class="info"><?php echo L_CONF_SENDNEWS_DESC; ?></small>
      </td><td>
       <select name="newssending" size="1">
        <option value="Guests/Registered" <?php if ($pnconfig['newssending'] == 'Guests/Registered') {
            echo 'selected';
        } ?>><?php echo L_CONF_GUESTSANDREGS; ?></option>
        <option value="Registered" <?php if ($pnconfig['newssending'] == 'Registered') {
            echo 'selected';
        } ?>><?php echo L_CONF_REGS; ?></option>
      </select>
      </td></tr>
      <?php } ?>
      <tr><td>
      <b><?php echo L_CONF_SMILIES; ?></b><br>
      <small class="info"><?php echo L_CONF_SMILIES_DESC; ?></small>
      </td><td>
      <select name="smilies" size="1">
        <option value="NO" <?php if ($pnconfig['smilies'] == 'NO') {
            echo 'selected';
        } ?>><?php echo L_ALL_NO; ?></option>
        <option value="Comments" <?php if ($pnconfig['smilies'] == 'Comments') {
            echo 'selected';
        } ?>><?php echo L_CONF_COMMENTS; ?></option>
        <option value="Comments/News" <?php if ($pnconfig['smilies'] == 'Comments/News') {
            echo 'selected';
        } ?>><?php echo L_CONF_COMMENTSANDNEWS; ?></option>
        <option value="News" <?php if ($pnconfig['smilies'] == 'News') {
            echo 'selected';
        } ?>><?php echo L_CONF_NEWS; ?></option>
      </select>
      </td></tr>
      <tr><td>
      <b><?php echo L_CONF_BBCODE; ?></b><br>
      <small class="info"><?php echo L_CONF_BBCODE_DESC; ?></small>
      </td><td>
      <select name="bbcode" size="1">
        <option value="NO" <?php if ($pnconfig['bbcode'] == 'NO') {
            echo 'selected';
        } ?>><?php echo L_ALL_NO; ?></option>
        <option value="Comments" <?php if ($pnconfig['bbcode'] == 'Comments') {
            echo 'selected';
        } ?>><?php echo L_CONF_COMMENTS; ?></option>
        <option value="Comments/News" <?php if ($pnconfig['bbcode'] == 'Comments/News') {
            echo 'selected';
        } ?>><?php echo L_CONF_COMMENTSANDNEWS; ?></option>
        <option value="News" <?php if ($pnconfig['bbcode'] == 'News') {
            echo 'selected';
        } ?>><?php echo L_CONF_NEWS; ?></option>
      </select>
      </td></tr>
      <tr><td>
      <b><?php echo L_CONF_HTML; ?></b><br>
      <small class="info"><?php echo L_CONF_HTML_DESC; ?></small>
      </td><td>
      <select name="html" size="1">
        <option value="NO" <?php if ($pnconfig['html'] == 'NO') {
            echo 'selected';
        } ?>><?php echo L_ALL_NO; ?></option>
        <option value="Comments" <?php if ($pnconfig['html'] == 'Comments') {
            echo 'selected';
        } ?>><?php echo L_CONF_COMMENTS; ?></option>
        <option value="Comments/News" <?php if ($pnconfig['html'] == 'Comments/News') {
            echo 'selected';
        } ?>><?php echo L_CONF_COMMENTSANDNEWS; ?></option>
        <option value="News" <?php if ($pnconfig['html'] == 'News') {
            echo 'selected';
        } ?>><?php echo L_CONF_NEWS; ?></option>
      </select>
      </td></tr>
      <tr><td>
      <b><?php echo L_CONF_DATEFORMAT; ?></b><br>
      <small class="info"><?php echo L_CONF_DATEFORMAT_DESC; ?></small>
      </td><td>
      <input name="dateformat" size="25" maxlength="50" value="<?php echo $pnconfig['dateformat']; ?>">
      </td></tr>
      <tr><td>
      <b><?php echo L_CONF_TIMEFORMAT; ?></b><br>
      <small class="info"><?php echo L_CONF_TIMEFORMAT_DESC; ?></small>
      </td><td>
      <input name="timeformat" size="25" maxlength="50" value="<?php echo $pnconfig['timeformat']; ?>">
      </td></tr>
      <tr><td>
      <b><?php echo L_CONF_TEMPLATE; ?></b><br>
      <small class="info"><?php echo L_CONF_TEMPLATE_DESC; ?></small>
      </td><td>
      <select name="template" size="1">
        <?php $pn_configuration->listtemplates(); ?>
      </select>
      </td></tr>
      <tr><td>
      <b><?php echo L_CONF_URL; ?></b><br>
      <small class="info"><?php echo L_CONF_URL_DESC; ?></small>
      </td><td>
      <input name="url" size="25" maxlength="250" value="<?php echo $pnconfig['url']; ?>">
      </td></tr>
      <tr><td>
      <b><?php echo L_CONF_EMAIL; ?></b><br>
      <small class="info"><?php echo L_CONF_EMAIL_DESC; ?></small>
      </td><td>
      <input name="email" size="25" maxlength="250" value="<?php echo $pnconfig['email']; ?>">
      </td></tr>
      <tr><td>
      <b><?php echo L_CONF_HEADLINES; ?></b><br>
      <small class="info"><?php echo L_CONF_HEADLINES_DESC; ?></small>
      </td><td>
      <input name="headlines" size="1" maxlength="2" value="<?php echo $pnconfig['headlines']; ?>">
      </td></tr>
      <tr><td>
      <b><?php echo L_CONF_NEWS; ?></b><br>
      <small class="info"></small>
      </td><td>
      <input name="news" size="1" maxlength="2" value="<?php echo $pnconfig['news']; ?>">
      </td></tr>
      <tr><td>
      <b><?php echo L_CONF_SPAMPROTECT; ?></b><br>
      <small class="info"><?php echo L_CONF_SPAMPROTECT_DESC; ?></small>
      </td><td>
      <input name="spamprotection" size="2" maxlength="3" value="<?php echo $pnconfig['spamprotection']; ?>">
      </td></tr>
      <tr><td>
      <b><?php echo L_CONF_RELATEDLINKS; ?></b><br>
      <small class="info"><?php echo L_CONF_RELATEDLINKS_DESC; ?></small>
      </td><td>
      <input type="radio" name="relatedlinks" value="YES" <?php if ($pnconfig['relatedlinks'] == 'YES') {
          echo 'checked';
      } ?> > <?php echo L_ALL_YES; ?>
      <input type="radio" name="relatedlinks" value="NO" <?php if ($pnconfig['relatedlinks'] == 'NO') {
          echo 'checked';
      } ?>> <?php echo L_ALL_NO; ?>
      </td></tr>
      <?php if ($pnconfig['relatedlinks'] == 'YES') { ?>
      <tr><td>
      <b><?php echo L_CONF_RELATEDLINKS_NUM; ?></b><br>
      <small class="info"><?php echo L_CONF_RELATEDLINKS_NUM_DESC; ?></small>
      </td><td>
      <input name="relatedlinks_num" size="1" maxlength="2" value="<?php echo $pnconfig['relatedlinks_num']; ?>">
      </td></tr>
      <?php } ?>
      <tr><td colspan="2" align="center">
      <input type="submit" value="<?php echo L_CONF_EDITCONFIG; ?>"> <input type="reset" value="<?php echo L_ALL_RESETDATA; ?>">
      </td></tr>
      </table>
      </form>
      </center>
      <?php
      }

  } else {
      ?><center><?php echo L_ALL_ACCESSDENIED; ?></center><?php
  }

?>

</td></tr>