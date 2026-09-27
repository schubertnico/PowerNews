<?php
/*
  Einfügen der header.inc.php mit dem Designcode bis zum Hauptinhalt der Seite
  Um die Grundstruktur der Seite zu editieren muss die header.inc.php und die footer.inc.php editiert
  werden
*/
include __DIR__ . '/header.inc.php';
?>

<!--AB HIER WERDEN DIE HEADLINES UND DIE UMLIEGENDE TABELLE ANGEZEIGT-->
<section class="card mb-4">
  <h1 class="pn-section-title h6 mb-0">Headlines</h1>
  <div class="card-body pn-content">
<?php
      /*
        Die headlines.inc.php fügt an der Stelle an der sie eingebaut ist die X neuesten Headlines ein
      */
      include __DIR__ . '/pninc/headlines.inc.php';
?>
  </div>
</section>
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
