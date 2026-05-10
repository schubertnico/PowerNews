<?php
declare(strict_types=1);

/* Include config und pnadmin-Funktionen (Fehler sichtbar lassen). */
include __DIR__ . '/pninc/config.inc.php';
include __DIR__ . '/pnadmin/functions.inc.php';

/* Admin-Auth (BUG-042): pncookie via pn_sessions verifizieren und canwriteconfig=YES verlangen. */
$adminInfo = pnadmin_auth_check();
if ($adminInfo === null || ($adminInfo['canwriteconfig'] ?? 'NO') !== 'YES') {
    http_response_code(403);
    echo '<div style="font-family:system-ui;margin:2rem;padding:1rem;border:1px solid #dc3545;color:#842029;background:#f8d7da;border-radius:.375rem;"><strong>Nur f&uuml;r Admins.</strong> Bitte im <a href="./pnadmin/">Adminbereich</a> einloggen.</div>';
    exit;
}
?><!doctype html>
<html lang="de">
<?php

/* PowerNews - PHP and MySQL based news script                         */
/* Copyright (c) 2001-2026 PowerScripts                                 */

/* MIT License - See LICENSE file for full license text                 */
/* https://github.com/schubertnico/PowerNews.git                        */

  /**
   * Escape HTML output to prevent XSS.
   */
  function convert_escape(string $value): string
  {
      return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
  }

/**
 * Validate path to prevent path traversal attacks
 * Returns the safe path or false if invalid.
 */
function validate_path(string $requestedPath, string $baseDir): string|false
{
    // Normalize path
    $realBasePath = realpath($baseDir);

    if ($realBasePath === false) {
        return false;
    }

    // Check if the requested path exists
    $realRequestedPath = realpath($requestedPath);

    if ($realRequestedPath === false) {
        return false;
    }

    // Ensure the requested path is within or equals the base directory
    // This prevents directory traversal attacks
    if (!str_starts_with($realRequestedPath, $realBasePath)) {
        return false;
    }

    return $realRequestedPath;
}

/* Config bereits oben inkludiert (vor dem Auth-Gate). */
?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PowerNews Converter</title>
    <link href="./assets/bootstrap/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container my-4">
    <div class="card shadow-sm">
        <h1 class="card-header h5 mb-0">PowerNews Converter</h1>
        <div class="card-body">

<?php
  if ($_GET['from'] ?? '') {
      switch ($_GET['from']) {
          default:
              ?>
              <div class="alert alert-warning">
                  <a href="convert.php" class="alert-link">Es muss ein g&uuml;ltiges Newsscript ausgew&auml;hlt worden sein!</a>
              </div>
              <?php
          break;
          case 'NewsPro':
              switch ($_GET['page'] ?? '') {
                  default:
                      $defaultPath = str_replace('/convert.php', '', $_SERVER['SCRIPT_FILENAME'] ?? '');
                      ?>
                      <p>Bitte geben Sie hier den vollen Pfad zu Ihrer <strong>newsdat.txt</strong> Datei an:</p>
                      <form action="convert.php?from=NewsPro&page=2" method="post" class="row g-2 align-items-end">
                          <div class="col-12 col-md-9">
                              <label for="np_dir" class="form-label small">Pfad (Kein / am Ende)</label>
                              <input class="form-control" name="newspro_dir" id="np_dir" maxlength="100" value="<?php echo convert_escape($defaultPath); ?>">
                          </div>
                          <div class="col-12 col-md-3">
                              <button type="submit" class="btn btn-primary w-100">Weiter</button>
                          </div>
                      </form>
                      <p class="form-text mt-2">Bitte beachten Sie, dass alle News dem Hauptadmin und der ersten Kategorie zugeordnet werden.</p>
                      <?php
                  break;
                  case '2':
                      // Get the base directory (this script's directory)
                      $baseDir = dirname($_SERVER['SCRIPT_FILENAME'] ?? __DIR__);
                      $requestedDir = $_POST['newspro_dir'] ?? '';

                      // Validate the path to prevent path traversal
                      $safePath = validate_path($requestedDir, $baseDir);

                      if ($safePath === false) {
                          ?>
                          <div class="alert alert-danger">
                              <a href="convert.php?from=NewsPro" class="alert-link">Ung&uuml;ltiger Pfad! Der Pfad muss innerhalb des PowerNews-Verzeichnisses liegen.</a>
                          </div>
                          <?php
                      } elseif (file_exists($safePath . '/newsdat.txt')) {
                          $newsdata = file($safePath . '/newsdat.txt');
                          $newsdata = implode('', $newsdata);
                          $newsdata = preg_replace('!``x``x!', '!@!@!', $newsdata);
                          $newsdata = explode('!@!@!', (string) $newsdata);
                          $news_num = count($newsdata);

                          ?><div class="alert alert-info"><strong><?php echo convert_escape((string) $news_num); ?></strong> Newseintr&auml;ge gefunden (kann eine falsche Zahl sein).</div><?php

                          $newscopied = 0;

                          for ($i = 0; $i < $news_num; ++$i) {
                              $newsdetails = explode('``x', $newsdata[$i]);
                              $extras = explode(',', $newsdetails[5] ?? '');

                              if (($newsdetails[4] ?? '') !== '') {
                                  $newsdetails[1] = str_replace("'", '&lsquo;', $newsdetails[1] ?? '');
                                  $newsdetails[4] = str_replace("'", '&lsquo;', $newsdetails[4] ?? '');

                                  if (($newsdetails[6] ?? '') != '') {
                                      $newstext = $newsdetails[6];
                                      $moretext = $newsdetails[1];
                                  } else {
                                      $newstext = $newsdetails[1];
                                      $moretext = '';
                                  }

                                  // Use prepared statement to prevent SQL injection
                                  $stmt = mysqli_prepare($pn_handler, 'INSERT INTO ' . $pn_config['newstable'] . ' (userid, time, catid, title, text, moretext, status) VALUES (?, ?, ?, ?, ?, ?, ?)');

                                  if ($stmt) {
                                      $userid = 1;
                                      $time = (int) ($extras[0] ?? 0);
                                      $catid = 1;
                                      $title = $newsdetails[4];
                                      $status = 'Activated';
                                      mysqli_stmt_bind_param($stmt, 'iiissss', $userid, $time, $catid, $title, $newstext, $moretext, $status);
                                      mysqli_stmt_execute($stmt);
                                      mysqli_stmt_close($stmt);
                                  }

                                  ++$newscopied;
                              }
                          }

                          ?>
                          <div class="alert alert-success"><strong><?php echo convert_escape((string) $newscopied); ?></strong> Newseintr&auml;ge erfolgreich &uuml;bernommen!</div>
                          <a href="<?php echo convert_escape($pn_config['newsfile']); ?>" class="btn btn-primary">Hauptseite</a>
                          <p class="form-text mt-3 mb-0">Bitte l&ouml;schen Sie die <strong>convert.php</strong>.</p>
                          <?php

                      } else {
                          ?>
                          <div class="alert alert-danger">
                              <a href="convert.php?from=NewsPro" class="alert-link">Das angegebene Verzeichnis scheint nicht korrekt zu sein! (<strong>newsdat.txt</strong> wurde nicht gefunden!)</a>
                          </div>
                          <?php
                      }
                      break;
              }
              break;
      }
  } else {
      ?>
        <p>Bitte w&auml;hlen Sie ein Newsscript von dem Sie Ihre Daten konvertieren wollen. <strong>PowerNews</strong> muss hierf&uuml;r allerdings schon korrekt installiert worden sein.</p>
        <ul class="list-unstyled">
            <li>&raquo; <a href="convert.php?from=NewsPro">NewsPro</a> (Nur News)</li>
        </ul>
      <?php
  }
?>

        </div>
    </div>

    <p class="text-center text-muted small mt-4 mb-0">PowerNews 3.0 &copy; Copyright 2024 by <a href="https://www.powerscripts.org" target="_blank" rel="noopener noreferrer">PowerScripts</a></p>
</div>

<script src="./assets/bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>
