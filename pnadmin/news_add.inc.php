<?php
declare(strict_types=1);

/* PowerNews - PHP and MySQL based news script                         */
/* Copyright (c) 2001-2024 PowerScripts                                 */

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
            ?><center><a href="javascript:history.back()"><?php echo L_NEWS_TITLEANDTEXTNEEDED; ?>
        <?php if ($pnconfig['categories'] == 'YES') {
            echo L_NEWS_ALSOCATEGORY;
        } ?>!</a></center><?php
        } else {
            $news = new news();
            $error = $news->addnews($title, $text, $catid, $moretext, $rl_title, $rl_url, $rl_target, $time);

            if ($error !== '' && $error !== '0') {
                ?><center><a href="javascript:history.back()"><?php echo pnadmin_escape($error); ?></a></center><?php
            } else {
                ?><center><a href="index.php?page=news&subpage=add"><?php echo L_NEWS_NEWSADDED; ?></a></center><?php
            }
        }
    } else {
        ?>
      <center>
      <form action="index.php?page=news&subpage=add&add=YES" method="post">
      <table border="0" cellpadding="4" cellspacing="0">
      <tr><td colspan="2" align="center">
      <b><?php echo L_NEWS_WRITENEWS; ?></b>
      </td></tr>
      <?php if ($pnconfig['categories'] == 'YES') { ?>
        <tr><td>
        <b><?php echo L_NEWS_CATEGORY; ?></b><br>
        <small class="info"><?php echo L_NEWS_CATEGORY_DESC; ?></small>
        </td><td>
        <?php
            $news = new news();
          $news->getcatdropdown();
          ?>
        </td></tr>
      <?php } ?>
      <tr><td>
      <b><?php echo L_NEWS_TIME; ?></b><br>
      <small class="info"><?php echo L_NEWS_TIME_DESC; ?></small>
      </td><td>
      <select name="time[day]" size="1">
        <option value=""><?php echo L_NEWS_DAY; ?></option>
        <option value=""></option>
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
      <select name="time[month]" size="1">
        <option value=""><?php echo L_NEWS_MONTH; ?></option>
        <option value=""></option>
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
      <select name="time[year]" size="1">
        <option value=""><?php echo L_NEWS_YEAR; ?></option>
        <option value=""></option>
        <?php
          $this_year = date('Y');
        ?><option value="<?php echo $this_year; ?>" selected><?php echo $this_year; ?></option><?php
        ?><option value="<?php echo ++$this_year; ?>"><?php echo $this_year; ?></option><?php
        ?>
      </select>
      @
      <select name="time[hour]" size="1">
        <option value=""><?php echo L_NEWS_HOUR; ?></option>
        <option value=""></option>
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
      :
      <select name="time[min]" size="1">
        <option value=""><?php echo L_NEWS_MIN; ?></option>
        <option value=""></option>
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
      </td></tr>
      <tr><td>
      <b><?php echo L_NEWS_TITLE; ?></b><br>
      <small class="info"><?php echo L_NEWS_TITLE_DESC; ?></small>
      </td><td>
      <input name="title" size="50" maxlength="150">
      </td></tr>
      <tr><td valign="top">
      <b><?php echo L_NEWS_TEXT; ?></b><br>
      <small class="info"><?php echo L_NEWS_TEXT_DESC; ?> (<a href="index.php?page=other&subpage=help#other.html" target="_blank">HTML</a>
      <?php
        if ($pnconfig['html'] == 'News' || $pnconfig['html'] == 'Comments & News') {
            ?><b><?php echo L_NEWS_ON; ?></b><?php
        } else {
            ?><b><?php echo L_NEWS_OFF; ?></b> <?php
        }
        ?>/<a href="index.php?page=other&subpage=help#other.bbcode" target="_blank">BB Code</a> <?php
        if ($pnconfig['bbcode'] == 'News' || $pnconfig['bbcode'] == 'Comments & News') {
            ?><b><?php echo L_NEWS_ON; ?></b>)<?php
        } else {
            ?><b><?php echo L_NEWS_OFF; ?></b>)<?php
        }
        ?>
      </small>
      </td><td>
      <textarea name="text" cols="75" rows="15"></textarea>
      </td></tr>
      <?php if ($pnconfig['moretext'] == 'YES') { ?>
        <tr><td valign="top">
        <b><?php echo L_NEWS_LONGTEXT; ?></b><br>
        <small class="info"><?php echo L_NEWS_LONGTEXT_DESC; ?> (<a href="index.php?page=other&subpage=help#other.html" target="_blank">HTML</a>
        <?php
            if ($pnconfig['html'] == 'News' || $pnconfig['html'] == 'Comments & News') {
                ?><b><?php echo L_NEWS_ON; ?></b><?php
            } else {
                ?><b><?php echo L_NEWS_OFF; ?></b><?php
            }
          ?>/<a href="index.php?page=other&subpage=help#news.bbcode" target="_blank">BB Code</a> <?php
          if ($pnconfig['bbcode'] == 'News' || $pnconfig['bbcode'] == 'Comments & News') {
              ?><b><?php echo L_NEWS_ON; ?></b>)<?php
          } else {
              ?><b><?php echo L_NEWS_OFF; ?></b>)<?php
          }
          ?>
        </small>
        </td><td>
        <textarea name="moretext" cols="75" rows="20"></textarea>
        </td></tr>
      <?php } ?>
      <?php if ($pnconfig['relatedlinks'] == 'YES') { ?>
        <tr><td valign="top">
        <b><?php echo L_NEWS_RELATEDLINKS; ?></b><br>
        <small class="info"><?php echo L_NEWS_RELATEDLINKS_DESC; ?></small>
        </td><td>
          <table border="0" cellpadding="3" cellspacing="0" width="100%">
          <tr><td>
          <b><?php echo L_NEWS_RL_TITLE; ?></b>
          </td><td>
          <b><?php echo L_NEWS_RL_URL; ?></b>
          </td><td>
          <b><?php echo L_NEWS_RL_TARGET; ?></b>
          </td></tr>
        <?php

            /* List forms for related links */
            for ($i = 0; $i < $pnconfig['relatedlinks_num']; ++$i) {
                ?>
            <tr><td>
            <input name="rl_title[]" size="25" maxlegnth="50">
            </td><td>
            <input name="rl_url[]" size="25" maxlength="250">
            </td><td>
            <select name="rl_target[]" size="1">
              <?php
                    $counter = count($pn_config['rltargets']);

                for ($i2 = 0; $i2 < $counter; ++$i2) {
                    ?><option value="<?php echo $pn_config['rltargets'][$i2]; ?>"><?php echo $pn_config['rltargets'][$i2]; ?></option><?php
                }
                ?>
            </select>
            </td></tr>
            <?php
            }
          ?>
          </table>
        </td></tr>
      <?php } ?>
      <tr><td colspan="2" align="center">
      <input type="submit" value="<?php echo L_NEWS_WRITENEWS; ?>">
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
