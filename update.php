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

error_reporting(E_ALL & ~E_NOTICE);

/* Include config und pnadmin-Funktionen (Fehler sichtbar lassen). */
include __DIR__ . '/pninc/config.inc.php';
include __DIR__ . '/pnadmin/functions.inc.php';

/* Admin-Auth (BUG-043): pncookie via pn_sessions verifizieren und canwriteconfig=YES verlangen. */
$adminInfo = pnadmin_auth_check();
if ($adminInfo === null || ($adminInfo['canwriteconfig'] ?? 'NO') !== 'YES') {
    http_response_code(403);
    echo '<div style="font-family:system-ui;margin:2rem;padding:1rem;border:1px solid #dc3545;color:#842029;background:#f8d7da;border-radius:.375rem;"><strong>Nur f&uuml;r Admins.</strong> Bitte im <a href="./pnadmin/">Adminbereich</a> einloggen.</div>';
    exit;
}

/* Needed PHP Version */
$need_php_version = '4.1.0';

/* Needed mySQL Version */
$need_mysql_version = '3.00';

/* Needed installed PowerNews version*/
$need_pn_version = '2.5.5';

/* Current PowerNews version */
$thisversion = '3.11';

/* Current installed PowerNews version */
$currentversion = $pn_config['version'];
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PowerNews Update</title>
    <link href="./assets/bootstrap/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container my-4">
    <div class="card shadow-sm">
        <h1 class="card-header h5 mb-0">PowerNews Update</h1>
        <div class="card-body">
<?php
  if ($_GET['updatenow'] == 'YES') {
      switch ($_GET['page']) {
          case '2':
              ?>
              <h2 class="h6 fw-bold">Schritt 2</h2>
              <p>mySQL Tabellen werden ge&auml;ndert:</p>
              <?php
              $sqlCommands = readDump('pn_update.sql');
              $counter = count($sqlCommands);

              for ($i = 0; $i < $counter; ++$i) {
                  mysqli_query($pn_handler, $sqlCommands[$i]);
              }
              ?>
              <div class="alert alert-success"><?php echo count($sqlCommands); ?> &Auml;nderungen an mySQL Tabellen durchgef&uuml;hrt!</div>

              <div class="alert alert-warning">
                  <strong>Achtung:</strong> Aufgrund des Updates ist es n&ouml;tig, dass die <a href="pnadmin/index.php?page=configuration" class="alert-link">Konfiguration</a> erneut editiert wird.
              </div>

              <a href="pnadmin/index.php" class="btn btn-success btn-lg">UPDATE ABGESCHLOSSEN</a>
              <?php
              break;
          default:
              ?>
              <h2 class="h6 fw-bold">Schritt 1</h2>
              <p>Bitte &uuml;berschreiben Sie mindestens die folgenden Dateien mit den neuen Versionen aus dem ZIP File:</p>
              <ul>
                  <li>pninc/head.inc.php</li>
                  <li>pninc/functions.inc.php</li>
                  <li>pnadmin/phpheader.inc.php</li>
                  <li>pnadmin/news_edit.inc.php</li>
                  <li>pnadmin/news_show.inc.php</li>
                  <li>pnadmin/users_show.inc.php</li>
                  <li>pnadmin/configuration.php</li>
                  <li>pnadmin/lang/german-du.php</li>
                  <li>pnadmin/lang/german-sie.php</li>
                  <li>pnadmin/functions.inc.php</li>
                  <li>update.php</li>
                  <li>readme.html</li>
                  <li>changelog.html</li>
                  <li>pn_update.sql</li>
              </ul>
              <p>Alternativ k&ouml;nnen Sie die &Auml;nderungen in diesen Dateien auch mit Hilfe des
              <a href="changelog.html">Changelogs</a> manuell durchf&uuml;hren.</p>

              <a href="update.php?updatenow=YES&page=2" class="btn btn-primary btn-lg">WEITER</a>
              <?php
              break;
      }
  } else {
      $error = '';
      ?>
        <h1 class="h5 fw-bold">PowerNews Update <?php echo htmlspecialchars((string) $currentversion); ?> auf <?php echo htmlspecialchars($thisversion); ?></h1>
        <p>&Uuml;ber diese Datei k&ouml;nnen Sie Ihre PowerNews Version auf die aktuelle Version updaten. Bitte beachten Sie, vorher auf Version <?php echo htmlspecialchars($need_pn_version); ?> upzudaten, ansonsten kann es zu Datenverlust kommen.</p>

        <p>Bitte fahren Sie mit dem Update von PowerNews nur fort, wenn alle Vorraussetzungen erf&uuml;llt (gr&uuml;n) sind. F&uuml;r die einzelnen Updateschritte lesen Sie bitte die <a href="readme.html" target="_blank" rel="noopener noreferrer">ReadMe</a>.</p>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th></th>
                        <th>Ben&ouml;tigt</th>
                        <th>Vorhanden</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>PHP Version</strong></td>
                        <td><?php echo htmlspecialchars($need_php_version); ?> oder h&ouml;her</td>
                        <td>
<?php
                            if (phpversion() >= $need_php_version) {
                                ?><span class="badge text-bg-success"><?php echo phpversion(); ?></span><?php
                            } else {
                                $error = 'PHP VERSION';
                                ?><span class="badge text-bg-danger"><?php echo phpversion(); ?></span><?php
                            }
?>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>upload_max_filesize</strong> (nur f&uuml;r Bildupload)</td>
                        <td>&gt; 0M</td>
                        <td>
<?php
                            $uploadMax = get_cfg_var('upload_max_filesize');
                            if (is_array($uploadMax)) {
                                $uploadMax = (string) ($uploadMax[0] ?? '0');
                            } elseif ($uploadMax === false) {
                                $uploadMax = '0';
                            }
                            if ((int) $uploadMax > 0) {
                                ?><span class="badge text-bg-success"><?php echo htmlspecialchars($uploadMax); ?></span><?php
                            } else {
                                ?><span class="badge text-bg-danger"><?php echo htmlspecialchars($uploadMax); ?></span><?php
                            }
?>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>mySQL</strong></td>
                        <td><?php echo htmlspecialchars($need_mysql_version); ?> oder h&ouml;her</td>
                        <td>
<?php
                            if (mysqli_get_server_info($pn_handler) >= $need_mysql_version) {
                                ?><span class="badge text-bg-success"><?php echo htmlspecialchars(mysqli_get_server_info($pn_handler)); ?></span><?php
                            } else {
                                $error = 'MYSQL';
                                ?><span class="badge text-bg-danger"><?php echo htmlspecialchars(mysqli_get_server_info($pn_handler)); ?></span><?php
                            }
?>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>PowerNews Version</strong></td>
                        <td><?php echo htmlspecialchars($need_pn_version); ?></td>
                        <td>
<?php
                            if ($need_pn_version == $currentversion) {
                                ?><span class="badge text-bg-success"><?php echo htmlspecialchars((string) $currentversion); ?></span><?php
                            } else {
                                $error = 'POWERNEWS';
                                ?><span class="badge text-bg-danger"><?php echo htmlspecialchars((string) $currentversion); ?></span><?php
                            }
?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

<?php
        if ($error) {
            ?><div class="alert alert-danger" role="alert"><strong>WARNUNG:</strong> Nicht alle Vorraussetzungen sind erf&uuml;llt, das Update sollte nicht durchgef&uuml;hrt werden.</div><?php
        }
?>

        <a href="update.php?updatenow=YES" class="btn btn-primary btn-lg">UPDATEN</a>
      <?php
  }
?>
        </div>
    </div>

    <p class="text-center text-muted small mt-4 mb-0">PowerNews <?php echo htmlspecialchars($thisversion); ?> &copy; 2003-2004 <a href="https://www.powerscripts.org" target="_blank" rel="noopener noreferrer">PowerScripts</a></p>
</div>

<script src="./assets/bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>
