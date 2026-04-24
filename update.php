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

error_reporting(E_ALL & ~E_NOTICE);

/* Include config und pnadmin-Funktionen (Fehler sichtbar lassen). */
include __DIR__ . '/pninc/config.inc.php';
include __DIR__ . '/pnadmin/functions.inc.php';

/* Admin-Auth (BUG-043): pncookie via pn_sessions verifizieren und canwriteconfig=YES verlangen. */
$adminInfo = pnadmin_auth_check();
if ($adminInfo === null || ($adminInfo['canwriteconfig'] ?? 'NO') !== 'YES') {
    http_response_code(403);
    echo '<center><b>Nur f&uuml;r Admins. Bitte im <a href="./pnadmin/">Adminbereich</a> einloggen.</b></center>';
    exit;
}

/* Needed PHP Version */
$need_php_version = '4.1.0';

/* Needed mySQL Version */
$need_mysql_version = '3.00';

/* Needed installed PowerNews version*/
$need_pn_version = '2.5.5';

/* Current PowerNews version */
$thisversion = '3.00';

/* Current installed PowerNews version */
$currentversion = $pn_config['version'];
?>
<html>
<head>
  <title>PowerNews Update</title>
</head>
<style>
<!--
  BODY, TR, TD, P {
    font-family: Verdana;
    font-size: 10px;
    color: #000000
  }

  INPUT, TEXTAREA, SELECT {
    font-family: Verdana;
    font-size: 10px;
    border-color: #000000;
    border-width: 1px;
  }

  A {
    color: #000080;
		text-decoration: none;
  }

  A:HOVER {
    color: #000000;
		text-decoration: none;
  }
-->
</style>
<body bgcolor="#FFFFF0" text="#000000" link="#000080" alink="#000080" vlink="#000080">

<center>
<table border="0" cellpadding="0" cellspacing="0" width="75%">
<tr><td bgcolor="#000000">
  <table border="0" cellpadding="3" cellspacing="1" width="100%">
  <tr><td bgcolor="#C0C0C0">
<?php
  if ($_GET['updatenow'] == 'YES') {
      switch ($_GET['page']) {
          case '2':
              ?>
        <h2>Schritt 2</h2>
				mySQL Tabellen werden ge�ndert:<br><br>
        <?php
          $sqlCommands = readDump('pn_update.sql');
              $counter = count($sqlCommands);

              for ($i = 0; $i < $counter; ++$i) {
                  mysqli_query($pn_handler, $sqlCommands[$i]);
              }
              echo count($sqlCommands) . " �nderungen an mySQL Tabellen durchgef�hrt!<br><br>\n";

              ?>
        <b>Achtung:</b> Aufgrund des Updates ist es n�tig, dass die <a href="pnadmin/index.php?page=configuration">Konfiguration</a> erneut editiert wird!
				<p align="center">
          <span style="font-size: 20px; text-weight: bold"><a href="pnadmin/index.php">UPDATE ABGESCHLOSSEN</a></span>
        </p>
				<?php
                  break;
          default:
              ?>
        <h2>Schritt 1</h2>
				Bitte &uuml;berschreiben Sie mindestens die folgenden Dateien mit den neuen Versionen aus dem ZIP File:
				<br>
				<ul type="square">
					<li>pninc/head.inc.php
					<li>pninc/functions.inc.php
					<li>pnadmin/phpheader.inc.php
					<li>pnadmin/news_edit.inc.php
					<li>pnadmin/news_show.inc.php
					<li>pnadmin/users_show.inc.php
					<li>pnadmin/configuration.php
					<li>pnadmin/lang/german-du.php
					<li>pnadmin/lang/german-sie.php
          <li>pnadmin/functions.inc.php
					<li>update.php
					<li>readme.html
          <li>changelog.html
          <li>pn_update.sql
				</ul>
        Alternativ k�nnen Sie die �nderungen in diesen Dateien auch mit Hilfe des
        <a href="changelog.html">Changelogs</a> manuell durchf�hren.
        <p align="center">
          <span style="font-size: 20px; text-weight: bold"><a href="update.php?updatenow=YES&page=2">WEITER</a></span>
        </p>
        <?php
                  break;
      }
  } else {
      $error = '';
      ?>
		<h1>PowerNews Update <?php echo $currentversion; ?> auf <?php echo $thisversion; ?></h1>
		&Uuml;ber diese Datei k&ouml;nnen Sie Ihre PowerNews Version auf die aktuelle Version updaten. Bitte beachten Sie,
		vorher auf Version <?php echo $need_pn_version; ?> upzudaten, ansonsten kann es zu Datenverlust kommen.<br>
		<br>
 		<br>
    Bitte fahren Sie mit dem Update von PowerNews nur fort, wenn alle Vorraussetzungen erf&uuml;llt (gr&uuml;n) sind.
		F&uuml;r die einzelnen Updateschritte lesen Sie bitte die <a href="readme.html" target="_blank">ReadMe</a>.<br>
    <br>

    <center>
    <table border="0" cellpadding="3" cellspacing="3">
    <tr><td width="150">
    &nbsp;
    </td><td width="175">
    <b>Ben&ouml;tigt</b>
    </td><td width="175">
    <b>Vorhanden</b>
    </td></tr>

    <tr><td>
    <b>PHP Version</b>
    </td><td>
    <?php echo $need_php_version; ?> oder h&ouml;her
    </td><td>
    <?php
      if (phpversion() >= $need_php_version) {
          echo '<font color="#008000">' . phpversion() . '</font>';
      } else {
          $error = 'PHP VERSION';
          echo '<font color="#FF0000">' . phpversion() . '</font>';
      }
      ?>
    </td></tr>

    <tr><td>
    <b>upload_max_filesize</b> (nur f&uuml;r Bildupload)
    </td><td>
    &gt; 0M
    </td><td>
    <?php
        if (get_cfg_var('upload_max_filesize') > 0) {
            echo '<font color="#008000">' . get_cfg_var('upload_max_filesize') . '</font>';
        } else {
            echo '<font color="#FF0000">' . get_cfg_var('upload_max_filesize') . '</font>';
        }
      ?>
    </td></tr>

    <tr><td>
    <b>mySQL</b>
    </td><td>
    <?php echo $need_mysql_version; ?> oder h&ouml;her
    </td><td>
    <?php
        if (mysqli_get_server_info($pn_handler) >= $need_mysql_version) {
            echo '<font color="#008000">' . mysqli_get_server_info($pn_handler) . '</font>';
        } else {
            $error = 'MYSQL';
            echo '<font color="#FF0000">' . mysqli_get_server_info($pn_handler) . '</font>';
        }
      ?>
    </td></tr>

		<tr><td>
    	<b>PowerNews Version</b>
    </td><td>
    	<?php echo $need_pn_version; ?>
    </td><td>
    <?php
        if ($need_pn_version == $currentversion) {
            echo '<font color="#008000">' . $currentversion . '</font>';
        } else {
            $error = 'POWERNEWS';
            echo '<font color="#FF0000">' . $currentversion . '</font>';
        }
      ?>
    </td></tr>
    </table>
    </center>

    <?php
            if ($error) {
                ?><br><br><center><span style="font-weight: bold; color: #FF0000">WARNUNG: NICHT ALLE VORRAUSSETZUNGEN SIND ERF&Uuml;LLT, DAS UPDATE SOLLTE NICHT DURCHGEF&Uuml;HRT WERDEN</span></center><?php
            }
      ?>
    <p align="center">
    	<span style="font-size: 20px; text-weight: bold"><a href="update.php?updatenow=YES">UPDATEN</a></span>
    </p>

		<?php
  }
?>

  </td></tr>
  </table>
</td></tr>
</table>
</center>

<p align="center" class="copyright"><font size="1">PowerNews <?php echo $thisversion; ?> &copy; 2003-2004 <a href="http://www.powerscripts.org" target="_blank">PowerScripts</a></font></p>

</body>
</html>