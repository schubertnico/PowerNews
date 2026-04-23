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

/* Include config file */
header('Content-Type: text/html; charset=ISO-8859-15');
@include __DIR__ . '/pninc/config.inc.php';
@include __DIR__ . '/pnadmin/functions.inc.php';

/* Needed PHP Version */
$need_php_version = '8.2.0';

/* Needed mySQL Version */
$need_mysql_version = '10.3';

/* Current PowerNews version */
$thisversion = '3.00';
?>
<html>
<head>
    <title>PowerNews Installation</title>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-15">
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
    }

    A:HOVER {
        color: #000000;
    }

    -->
</style>
<body bgcolor="#FFFFF0" text="#000000" link="#000080" alink="#000080" vlink="#000080">

<center>
    <table border="0" cellpadding="0" cellspacing="0" width="75%">
        <tr>
            <td bgcolor="#000000">
                <table border="0" cellpadding="3" cellspacing="1" width="100%">
                    <tr>
                        <td bgcolor="#C0C0C0">

                          <?php
                          $installLockFile = __DIR__ . '/pninc/install.lock';
                          if (isset($_POST['install']) && $_POST['install'] == 'YES') {

                              if (file_exists($installLockFile)) {
                                  http_response_code(403);
                                  echo '<b>Installation bereits erfolgt.</b><br>Zum Neuinstallieren bitte <code>pninc/install.lock</code> entfernen.';
                              } else {
                                  $sqlCommands = readDump('powernews.sql');
                                  $counter = count($sqlCommands);

                                  for ($i = 0; $i < $counter; ++$i) {
                                      mysqli_query($pn_handler, $sqlCommands[$i]);
                                  }
                                  echo count($sqlCommands) . " mySQL Befehle ausgef&uuml;hrt.<br><br>\n";
                                  echo "Tabellenstruktur erstellt<br><br>\n";
                                  echo "Standardkonfiguration geladen<br><br>\n";

                                  @file_put_contents($installLockFile, 'installed ' . date('c'));

                                  echo "<br>Installation erfolgreich! Bitte l&ouml;schen Sie die <b>install.php</b> und die <b>update.php</b> - <a href=\"./pnadmin/\">Adminbereich</a>\n";
                              }

                          } else {
                              ?>
                              Bitte fahren Sie mit der Installation von PowerNews nur fort, wenn alle Vorraussetzungen erf&uuml;llt (gr&uuml;n) sind. F&uuml;r die einzelnen Installationsschritte lesen Sie bitte die
                              <a href="readme.html" target="_blank">ReadMe</a>.<br>
                              <br>

                              <center>
                                  <table border="0" cellpadding="3" cellspacing="3">
                                      <tr>
                                          <td width="150">
                                              &nbsp;
                                          </td>
                                          <td width="175">
                                              <b>Ben&ouml;tigt</b>
                                          </td>
                                          <td width="175">
                                              <b>Vorhanden</b>
                                          </td>
                                      </tr>

                                      <tr>
                                          <td>
                                              <b>PHP Version</b>
                                          </td>
                                          <td>
                                              <?php echo $need_php_version; ?> oder h&ouml;her
                                          </td>
                                          <td>
                                              <?php
      if (phpversion() >= $need_php_version) {
          echo '<font color="#008000">' . phpversion() . '</font>';
      } else {
          $error = 'PHP VERSION';
          echo '<font color="#FF0000">' . phpversion() . '</font>';
      }
                              ?>
                                          </td>
                                      </tr>

                                      <tr>
                                          <td>
                                              <b>upload_max_filesize</b> (nur f&uuml;r Bildupload)
                                          </td>
                                          <td>
                                              &gt; 0M
                                          </td>
                                          <td>
                                              <?php
      if (get_cfg_var('upload_max_filesize') > 0) {
          echo '<font
                                                      color="#008000">' . get_cfg_var('upload_max_filesize') . '</font>';
      } else {
          echo '<font
                                                      color="#FF0000">' . get_cfg_var('upload_max_filesize') . '</font>';
      }
                              ?>
                                          </td>
                                      </tr>

                                      <tr>
                                          <td>
                                              <b>mySQL</b>
                                          </td>
                                          <td>
                                              <?php echo $need_mysql_version; ?> oder h&ouml;her
                                          </td>
                                          <td>
                                              <?php
  if (isset($pn_handler)) {
      $server_version = mysqli_get_server_info($pn_handler);
      $version_parts = explode('-', $server_version);
      $numeric_version = $version_parts[0];

      if (version_compare($numeric_version, $need_mysql_version, '>=')) {
          echo "<font color=\"#008000\">{$numeric_version}</font>";
      } else {
          $error = 'MYSQL';
          echo "<font color=\"#FF0000\">{$numeric_version}</font>";
      }
  } else {
      echo '<font color="#FF0000">Unknown version</font>';
  }
                              ?>

                                          </td>
                                      </tr>
                                  </table>
                              </center>

                            <?php if (!isset($error)) { ?>
                                  <p align="center">
                                  <form action="install.php" method="post">
                                      <input type="hidden" name="install" value="YES">
                                      <input type="submit" value="Installieren">
                                  </form>
                                  </p>
                              <?php
                            }
                          }
?>

                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</center>

<p align="center" class="copyright"><font size="1">PowerNews <?php echo $thisversion; ?> &copy; Copyright 2002 by <a
                href="http://www.powerscripts.org" target="_blank">PowerScripts</a></font></p>

</body>
</html>