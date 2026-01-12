<?php
/*
  Einfügen der header.inc.php mit dem Designcode bis zum Hauptinhalt der Seite
  Um die Grundstruktur der Seite zu editieren muss die header.inc.php und die footer.inc.php editiert
  werden
*/
include __DIR__ . '/header.inc.php';
?>

  <!--AB HIER WERDEN DIE HEADLINES UND DIE UMLIEGENDE TABELLE ANGEZEIGT-->
  <table border="0" cellpadding="3" cellspacing="0" width="100%">
  <tr><td bgcolor="#8C8E8C">
  <b>Headlines</b>
  </td></tr>
  <tr><td bgcolor="#DEDFDE">
    <?php
      /*
        Die headlines.inc.php fügt an der Stelle an der sie eingebaut ist die X neuesten Headlines ein
      */
      include __DIR__ . '/pninc/headlines.inc.php';
?>
  </td></tr>
  </table><br>
  <!--ENDE DES HEADLINE BEREICHS - START DER NORMALEN NEWS-->

  <?php
/*
  Die news.inc.php fügt die X neusten Newseinträge an der Stelle ein wo sie eingebaut ist
*/
include __DIR__ . '/pninc/news.inc.php';
?>

<?php
/*
  Einfügen der footer.inc.php mit dem Designcode der nach dem Hauptinhalt der Seite kommt
*/
include __DIR__ . '/footer.inc.php';
?>