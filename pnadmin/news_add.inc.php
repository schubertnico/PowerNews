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

  if ($pnadmin['canwritenews'] == "YES") {

    if (isset($_GET['add']) && $_GET['add'] == "YES") {
      if ($pnconfig['categories'] == "YES" && !$_POST['catid'] || !$_POST['title'] || !$_POST['text']) {
        ?><center><a href="javascript:history.back()"><?PHP echo L_NEWS_TITLEANDTEXTNEEDED; ?>
        <?PHP if ($pnconfig['categories'] == "YES") { echo L_NEWS_ALSOCATEGORY; } ?>!</a></center><?PHP
      } else {
        $news = new news;
        $error = $news->addnews(
                ((isset($_POST['title']) && trim((string) $_POST['title']) !== '') ? trim((string) $_POST['title']):''),
                ((isset($_POST['text']) && trim((string) $_POST['text']) !== '') ? trim((string) $_POST['text']):''),
                ((isset($_POST['catid']) && trim($_POST['catid']) !== '') ? intval($_POST['catid']):0),
                ((isset($_POST['moretext']) && trim($_POST['moretext']) !== '') ? trim($_POST['moretext']):''),
                ((isset($_POST['rl_title']) && is_array($_POST['rl_title'])) ? $_POST['rl_title']:[]),
                ((isset($_POST['rl_url']) && is_array($_POST['rl_url'])) ? $_POST['rl_url']:[]),
                ((isset($_POST['rl_target']) && is_array($_POST['rl_target'])) ? $_POST['rl_target']:[]),
                ((isset($_POST['time']) && is_array($_POST['time'])) ? $_POST['time']:[])
        );
        if ($error !== '' && $error !== '0') {
          ?><center><a href="javascript:history.back()"><?PHP echo $error; ?></a></center><?PHP
        } else {
          ?><center><a href="index.php?page=news&subpage=add"><?PHP echo L_NEWS_NEWSADDED; ?></a></center><?PHP
        }
      }
    } else {
      ?>
      <center>
      <form action="index.php?page=news&subpage=add&add=YES" method="post">
      <table border="0" cellpadding="4" cellspacing="0">
      <tr><td colspan="2" align="center">
      <b><?PHP echo L_NEWS_WRITENEWS; ?></b>
      </td></tr>
      <?PHP if ($pnconfig['categories'] == "YES") { ?>
        <tr><td>
        <b><?PHP echo L_NEWS_CATEGORY; ?></b><br>
        <small class="info"><?PHP echo L_NEWS_CATEGORY_DESC; ?></small>
        </td><td>
        <?PHP
          $news = new news;
          $news->getcatdropdown();
        ?>
        </td></tr>
      <?PHP } ?>
      <tr><td>
      <b><?PHP echo L_NEWS_TIME; ?></b><br>
      <small class="info"><?PHP echo L_NEWS_TIME_DESC; ?></small>
      </td><td>
      <select name="time[day]" size="1">
        <option value=""><?PHP echo L_NEWS_DAY; ?></option>
        <option value=""></option>
        <?php
          $this_day = date("d");
          for ($i = 1; $i < 32; $i++) {
            if ($i < 10) {
              ?><option value="<?PHP echo $i; ?>" <?PHP echo $this_day == $i ? "selected" : ""; ?>>0<?PHP echo $i; ?></option><?php
            } else {
							?><option value="<?PHP echo $i; ?>" <?PHP echo $this_day == $i ? "selected" : ""; ?>><?PHP echo $i; ?></option><?php
            }
          }
        ?>
      </select>
      <select name="time[month]" size="1">
        <option value=""><?PHP echo L_NEWS_MONTH; ?></option>
        <option value=""></option>
        <?php
          $this_month = date("m");
          for ($i = 1; $i < 13; $i++) {
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
            ?><option value="<?PHP echo $i; ?>" <?PHP echo $this_month == $i ? "selected" : ""; ?>><?PHP echo $month; ?></option><?php
          }
        ?>
      </select>
      <select name="time[year]" size="1">
        <option value=""><?PHP echo L_NEWS_YEAR; ?></option>
        <option value=""></option>
        <?php
          $this_year = date("Y");
          ?><option value="<?PHP echo $this_year; ?>" selected><?PHP echo $this_year; ?></option><?php
          ?><option value="<?PHP echo ++$this_year; ?>"><?PHP echo $this_year; ?></option><?php
        ?>
      </select>
      @
      <select name="time[hour]" size="1">
        <option value=""><?PHP echo L_NEWS_HOUR; ?></option>
        <option value=""></option>
        <?php
          $this_hour = date("H");
          for ($i = 0; $i < 24; $i++) {
            if ($i < 10) {
              ?><option value="<?PHP echo $i; ?>" <?PHP echo $this_hour == $i ? "selected" : ""; ?>>0<?PHP echo $i; ?></option><?php
            } else {
							?><option value="<?PHP echo $i; ?>" <?PHP echo $this_hour == $i ? "selected" : ""; ?>><?PHP echo $i; ?></option><?php
            }
          }
        ?>
      </select>
      :
      <select name="time[min]" size="1">
        <option value=""><?PHP echo L_NEWS_MIN; ?></option>
        <option value=""></option>
        <?php
          $this_min = date("i");
          for ($i = 0; $i < 60; $i++) {
            if ($i < 10) {
              ?><option value="<?PHP echo $i; ?>" <?PHP echo $this_min == $i ? "selected" : ""; ?>>0<?PHP echo $i; ?></option><?php
            } else {
							?><option value="<?PHP echo $i; ?>" <?PHP echo $this_min == $i ? "selected" : ""; ?>><?PHP echo $i; ?></option><?php
            }
          }
        ?>
      </select>
      </td></tr>
      <tr><td>
      <b><?PHP echo L_NEWS_TITLE; ?></b><br>
      <small class="info"><?PHP echo L_NEWS_TITLE_DESC; ?></small>
      </td><td>
      <input name="title" size="50" maxlength="150">
      </td></tr>
      <tr><td valign="top">
      <b><?PHP echo L_NEWS_TEXT; ?></b><br>
      <small class="info"><?PHP echo L_NEWS_TEXT_DESC; ?> (<a href="index.php?page=other&subpage=help#other.html" target="_blank">HTML</a>
      <?PHP
        if ($pnconfig['html'] == "News" || $pnconfig['html'] == "Comments & News") {
          ?><b><?PHP echo L_NEWS_ON; ?></b><?PHP
        } else {
          ?><b><?PHP echo L_NEWS_OFF; ?></b> <?PHP
        }
        ?>/<a href="index.php?page=other&subpage=help#other.bbcode" target="_blank">BB Code</a> <?PHP
        if ($pnconfig['bbcode'] == "News" || $pnconfig['bbcode'] == "Comments & News") {
          ?><b><?PHP echo L_NEWS_ON; ?></b>)<?PHP
        } else {
          ?><b><?PHP echo L_NEWS_OFF; ?></b>)<?PHP
        }
      ?>
      </small>
      </td><td>
      <textarea name="text" cols="75" rows="15"></textarea>
      </td></tr>
      <?PHP if ($pnconfig['moretext'] == "YES") { ?>
        <tr><td valign="top">
        <b><?PHP echo L_NEWS_LONGTEXT; ?></b><br>
        <small class="info"><?PHP echo L_NEWS_LONGTEXT_DESC; ?> (<a href="index.php?page=other&subpage=help#other.html" target="_blank">HTML</a>
        <?PHP
          if ($pnconfig['html'] == "News" || $pnconfig['html'] == "Comments & News") {
            ?><b><?PHP echo L_NEWS_ON; ?></b><?PHP
          } else {
            ?><b><?PHP echo L_NEWS_OFF; ?></b><?PHP
          }
          ?>/<a href="index.php?page=other&subpage=help#news.bbcode" target="_blank">BB Code</a> <?php
          if ($pnconfig['bbcode'] == "News" || $pnconfig['bbcode'] == "Comments & News") {
            ?><b><?PHP echo L_NEWS_ON; ?></b>)<?PHP
          } else {
            ?><b><?PHP echo L_NEWS_OFF; ?></b>)<?PHP
          }
        ?>
        </small>
        </td><td>
        <textarea name="moretext" cols="75" rows="20"></textarea>
        </td></tr>
      <?PHP } ?>
      <?PHP if ($pnconfig['relatedlinks'] == "YES") { ?>
        <tr><td valign="top">
        <b><?PHP echo L_NEWS_RELATEDLINKS; ?></b><br>
        <small class="info"><?PHP echo L_NEWS_RELATEDLINKS_DESC; ?></small>
        </td><td>
          <table border="0" cellpadding="3" cellspacing="0" width="100%">
          <tr><td>
          <b><?PHP echo L_NEWS_RL_TITLE; ?></b>
          </td><td>
          <b><?PHP echo L_NEWS_RL_URL; ?></b>
          </td><td>
          <b><?PHP echo L_NEWS_RL_TARGET; ?></b>
          </td></tr>
        <?php

          /* List forms for related links */
          for ($i = 0; $i < $pnconfig['relatedlinks_num']; $i++) {
            ?>
            <tr><td>
            <input name="rl_title[]" size="25" maxlegnth="50">
            </td><td>
            <input name="rl_url[]" size="25" maxlength="250">
            </td><td>
            <select name="rl_target[]" size="1">
              <?PHP
                $counter = count($pn_config['rltargets']);
                for ($i2 = 0; $i2 < $counter; $i2++) {
                  ?><option value="<?PHP echo $pn_config['rltargets'][$i2]; ?>"><?PHP echo $pn_config['rltargets'][$i2]; ?></option><?php
                }
              ?>
            </select>
            </td></tr>
            <?PHP
          }
        ?>
          </table>
        </td></tr>
      <?PHP } ?>
      <tr><td colspan="2" align="center">
      <input type="submit" value="<?PHP echo L_NEWS_WRITENEWS; ?>">
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
