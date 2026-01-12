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

if ($pnadmin['canreadnews'] == 'YES') {
    ?>
    <center>
    <?php
      $news = new news();
    $news->listpages();
    ?>
    <br><br>
    <table border="0" cellpadding="4" cellspacing="0">
    <tr><td>
    <b><?php echo L_NEWS_DATE; ?></b>
    </td><?php if ($pnconfig['categories'] == 'YES') { ?><td width="30">
    &nbsp;
    </td><td>
    <b><?php echo L_NEWS_CATEGORY; ?></b>
    </td><?php } ?><td width="30">
    &nbsp;
    </td><td>
    <b><?php echo L_NEWS_TITLE; ?></b>
    </td><td width="30">
    &nbsp;
    </td><td>
    <b><?php echo L_NEWS_STATUS; ?></b>
    </td></tr>
    <?php
      if (!isset($_GET['current'])) {
          $_GET['current'] = '0';
      }
    $news->listnews($_GET['current']);
    ?>
    </table>
    <br>
    <?php
      $news->listpages();
    ?>
    </center><br>
    <?php echo L_NEWS_SHOW_DESC; ?>
    <?php

} else {
    ?><center><?php echo L_ALL_ACCESSDENIED; ?></center><?php
}
?>