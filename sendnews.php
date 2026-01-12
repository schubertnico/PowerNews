<?php
/*
  Einfügen der header.inc.php mit dem Designcode bis zum Hauptinhalt der Seite
  Um die Grundstruktur der Seite zu editieren muss die header.inc.php und die footer.inc.php editiert
  werden
*/
include __DIR__ . '/header.inc.php';
?>

      <?php
        /*
          Die sendnews.inc.php wird an der Stelle eingefügt an der das News senden Formular angezeigt werden
          soll
        */
        include __DIR__ . '/pninc/sendnews.inc.php';
?>

<?php
  /*
    Einfügen der footer.inc.php mit dem Designcode der nach dem Hauptinhalt der Seite kommt
  */
  include __DIR__ . '/footer.inc.php';
?>