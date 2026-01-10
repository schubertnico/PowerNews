<?PHP
  /*
    Einfügen der header.inc.php mit dem Designcode bis zum Hauptinhalt der Seite
    Um die Grundstruktur der Seite zu editieren muss die header.inc.php und die footer.inc.php editiert
    werden
  */
  include(__DIR__ . "/header.inc.php");
?>

<?PHP
  /*
    Die details.inc.php wird an der Stelle eingefügt an der die Newsdetails samt Kommentaren angezeigt werden
    sollen
  */
  include(__DIR__ . "/pninc/details.inc.php");
?>

<?PHP
  /*
    Einfügen der footer.inc.php mit dem Designcode der nach dem Hauptinhalt der Seite kommt
  */
  include(__DIR__ . "/footer.inc.php");
?>