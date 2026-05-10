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

/* Include config file */
header('Content-Type: text/html; charset=UTF-8');
@include __DIR__ . '/pninc/config.inc.php';
@include __DIR__ . '/pnadmin/functions.inc.php';

/* Needed PHP Version */
$need_php_version = '8.2.0';

/* Needed mySQL Version */
$need_mysql_version = '10.3';

/* Current PowerNews version */
$thisversion = '3.11';
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PowerNews Installation</title>
    <link href="./assets/bootstrap/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container my-4">
    <div class="card shadow-sm">
        <h1 class="card-header h5 mb-0">PowerNews Installation</h1>
        <div class="card-body">
<?php
        $installLockFile = __DIR__ . '/pninc/install.lock';
        if (isset($_POST['install']) && $_POST['install'] == 'YES') {

            if (file_exists($installLockFile)) {
                http_response_code(403);
                ?>
                <div class="alert alert-warning" role="alert">
                    <strong>Installation bereits erfolgt.</strong><br>
                    Zum Neuinstallieren bitte <code>pninc/install.lock</code> entfernen.
                </div>
                <?php
            } else {
                $sqlCommands = readDump('powernews.sql');
                $counter = count($sqlCommands);

                for ($i = 0; $i < $counter; ++$i) {
                    mysqli_query($pn_handler, $sqlCommands[$i]);
                }
                ?>
                <div class="alert alert-success">
                    <p class="mb-1"><?php echo count($sqlCommands); ?> mySQL Befehle ausgef&uuml;hrt.</p>
                    <p class="mb-1">Tabellenstruktur erstellt.</p>
                    <p class="mb-1">Standardkonfiguration geladen.</p>
                </div>
                <?php

                // Create admin user with random bcrypt password (BUG-003)
                $adminNickname = 'admin';
                $adminEmail = 'admin@localhost';
                $adminPassword = bin2hex(random_bytes(8));
                $adminHash = password_hash($adminPassword, PASSWORD_DEFAULT);
                $nowTs = time();
                $stmt = mysqli_prepare($pn_handler, "INSERT INTO pn_users (nickname, email, password, registered, showemail, status) VALUES (?, ?, ?, ?, 'NO', 'Activated')");
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, 'sssi', $adminNickname, $adminEmail, $adminHash, $nowTs);
                    mysqli_stmt_execute($stmt);
                    $adminId = mysqli_insert_id($pn_handler);

                    $stmt2 = mysqli_prepare($pn_handler, "INSERT INTO pn_permissions (userid, canreadtemplates, canwritetemplates, canreadconfig, canwriteconfig, canreadusers, canwriteusers, canreadpermissions, canwritepermissions, canreadcategories, canwritecategories, canreadnews, canwritenews, canreadcomments, canwritecomments) VALUES (?, 'YES','YES','YES','YES','YES','YES','YES','YES','YES','YES','YES','YES','YES','YES')");
                    if ($stmt2) {
                        mysqli_stmt_bind_param($stmt2, 'i', $adminId);
                        mysqli_stmt_execute($stmt2);
                    }
                    ?>
                    <div class="alert alert-info">
                        <strong>Admin-Zugang erstellt:</strong><br>
                        Nickname: <code><?php echo htmlspecialchars($adminNickname); ?></code><br>
                        Passwort: <code><?php echo htmlspecialchars($adminPassword); ?></code><br>
                        <strong class="text-danger">Bitte sofort notieren und nach erstem Login im Profil &auml;ndern.</strong>
                    </div>
                    <?php
                }

                @file_put_contents($installLockFile, 'installed ' . date('c'));
                ?>
                <div class="alert alert-success mb-0">
                    Installation erfolgreich! Bitte l&ouml;schen Sie die <strong>install.php</strong> und die <strong>update.php</strong> &mdash;
                    <a href="./pnadmin/" class="alert-link">Adminbereich</a>.
                </div>
                <?php
            }

        } else {
            ?>
            <p>
                Bitte fahren Sie mit der Installation von PowerNews nur fort, wenn alle Vorraussetzungen erf&uuml;llt (gr&uuml;n) sind. F&uuml;r die einzelnen Installationsschritte lesen Sie bitte die
                <a href="readme.html" target="_blank" rel="noopener noreferrer">ReadMe</a>.
            </p>

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
                            <td><?php echo $need_php_version; ?> oder h&ouml;her</td>
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
                            <td><?php echo $need_mysql_version; ?> oder h&ouml;her</td>
                            <td>
<?php
                                if (isset($pn_handler)) {
                                    $server_version = mysqli_get_server_info($pn_handler);
                                    $version_parts = explode('-', $server_version);
                                    $numeric_version = $version_parts[0];

                                    if (version_compare($numeric_version, $need_mysql_version, '>=')) {
                                        ?><span class="badge text-bg-success"><?php echo htmlspecialchars($numeric_version); ?></span><?php
                                    } else {
                                        $error = 'MYSQL';
                                        ?><span class="badge text-bg-danger"><?php echo htmlspecialchars($numeric_version); ?></span><?php
                                    }
                                } else {
                                    ?><span class="badge text-bg-danger">Unknown version</span><?php
                                }
?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

<?php if (!isset($error)) { ?>
            <form action="install.php" method="post">
                <input type="hidden" name="install" value="YES">
                <button type="submit" class="btn btn-primary">Installieren</button>
            </form>
<?php
            }
        }
?>

        </div>
    </div>

    <p class="text-center text-muted small mt-4 mb-0">PowerNews <?php echo htmlspecialchars($thisversion); ?> &copy; Copyright 2002 by <a href="https://www.powerscripts.org" target="_blank" rel="noopener noreferrer">PowerScripts</a></p>
</div>

<script src="./assets/bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>
