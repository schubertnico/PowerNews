<?php
declare(strict_types=1);
?><html>
<?php

/* PowerNews - PHP and MySQL based news script                         */
/* Copyright (c) 2001-2024 PowerScripts                                 */

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

/* Include config file */
include __DIR__ . '/pninc/config.inc.php';
?>
<head>
<title>PowerNews Converter</title>
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
<body text="#000000" bgcolor="#FFFFF0" link="#000080" alink="#000080" vlink="#000080">

<center>
<table border="0" cellpadding="0" cellspacing="0" width="75%">
<tr><td bgcolor="#000000">
  <table border="0" cellpadding="3" cellspacing="1" width="100%">
  <tr><td bgcolor="#C0C0C0">

<?php
  if ($_GET['from'] ?? '') {
      switch ($_GET['from']) {
          default:
              ?><p align="center"><a href="convert.php">Es muss ein g&uuml;ltiges Newsscript ausgew&auml;hlt worden sein!</a></P><?php
      break;
          case 'NewsPro':
              switch ($_GET['page'] ?? '') {
                  default:
                      $defaultPath = str_replace('/convert.php', '', $_SERVER['SCRIPT_FILENAME'] ?? '');
                      ?>
            Bitte geben Sie hier den vollen Pfad zu Ihrer <b>newsdat.txt</b> Datei an:<br>
            <form action="convert.php?from=NewsPro&page=2" method="post">
            <input name="newspro_dir" size="50" maxlength="100" value="<?php echo convert_escape($defaultPath); ?>"> (Kein / am Ende)
            <input type="submit" value="Weiter">
            </form>
            Bitte beachten Sie, dass alle News dem Hauptadmin und der ersten Kategorie zugeordnet werden!
            <?php
          break;
                  case '2':
                      // Get the base directory (this script's directory)
                      $baseDir = dirname($_SERVER['SCRIPT_FILENAME'] ?? __DIR__);
                      $requestedDir = $_POST['newspro_dir'] ?? '';

                      // Validate the path to prevent path traversal
                      $safePath = validate_path($requestedDir, $baseDir);

                      if ($safePath === false) {
                          ?><p align="center"><a href="convert.php?from=NewsPro">Ung&uuml;ltiger Pfad! Der Pfad muss innerhalb des PowerNews-Verzeichnisses liegen.</a></p><?php
                      } elseif (file_exists($safePath . '/newsdat.txt')) {
                          $newsdata = file($safePath . '/newsdat.txt');
                          $newsdata = implode('', $newsdata);
                          $newsdata = preg_replace('!``x``x!', '!@!@!', $newsdata);
                          $newsdata = explode('!@!@!', (string) $newsdata);
                          $news_num = count($newsdata);

                          echo '<b>' . convert_escape((string) $news_num) . "</b> Newseintr&auml;ge gefunden (kann eine falsche Zahl sein)<br><br>\n\n";

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

                          echo '<b>' . convert_escape((string) $newscopied) . "</b> Newseintr&auml;ge erfolgreich &uuml;bernommen!<br><br>\n\n";

                          ?><p align="center"><a href="<?php echo convert_escape($pn_config['newsfile']); ?>">Hauptseite</a></p><?php
                          ?><p align="center"><small>Bitte l&ouml;schen Sie die <b>convert.php</b></small><?php

                      } else {
                          ?><p align="center"><a href="convert.php?from=NewsPro">Das angegebene Verzeichnis scheint nicht korrekt zu sein! (<b>newsdat.txt</b> wurde nicht gefunden!)</a></p><?php
                      }
                      break;
              }
              break;
      }
  } else {
      ?>
    Bitte w&auml;hlen Sie ein Newsscript von dem Sie Ihre Daten konvertieren wollen. <b>PowerNews</b> muss hierf&uuml;r allerdings schon korrekt installiert worden sein!<br>
    <ul type="square">
      <li><a href="convert.php?from=NewsPro">NewsPro</a> (Nur News)
    </ul>
    <?php
  }
?>

  </td></tr>
  </table>
</td></tr>
</table>
</center>

<p align="center" class="copyright"><font size="1">PowerNews 3.0 &copy; Copyright 2024 by <a href="http://www.powerscripts.org" target="_blank">PowerScripts</a></font></p>

</body>
</html>
