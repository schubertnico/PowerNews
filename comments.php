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
          Die comments.inc.php bewirkt das hinzufügen eines Kommentars und gibt eine Nachricht an den Browser aus
          die dort erscheint wo die Datei eingebunden wurde
        */
        include __DIR__ . '/pninc/comments.inc.php';
?>

<?php
  /*
    Einfügen der footer.inc.php mit dem Designcode der nach dem Hauptinhalt der Seite kommt
  */
  include __DIR__ . '/footer.inc.php';
?>