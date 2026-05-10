<?php
/*
Hier wird die head.inc.php in die Datei eingef�gt.
Es ist wichtig, dass es vor dem <?PHP keine anderen Zeichen in der Datei gibt!
*/
include __DIR__ . '/pninc/head.inc.php';
?><!doctype html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="author" content="powerscripts.org">
<title>PowerNews 3.0</title>
<link href="./assets/bootstrap/bootstrap.min.css" rel="stylesheet">
<style>
  body {
    font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    font-size: 0.95rem;
    background-color: #f8f9fa;
    color: #212529;
    --bs-secondary-color: #212529;
    --bs-tertiary-color: #212529;
    --bs-link-color: #0a58ca;
    --bs-link-hover-color: #084298;
  }
  /* Etwas dunkleres Link-Blau fuer bessere Lesbarkeit auf hellem Hintergrund (WCAG AA). */
  a {
    color: #0a58ca;
  }
  a:hover {
    color: #084298;
  }
  /* Keine grauen muted-Texte: alles in dunkler Schrift fuer maximale Lesbarkeit. */
  .text-muted,
  .form-text,
  small,
  .small {
    color: #212529 !important;
  }
  .copyright {
    font-size: 0.75rem;
    color: #212529 !important;
  }
  /* Outline-Secondary-Buttons komplett schwarze Schrift, kein Grau. */
  .btn-outline-secondary {
    color: #000000;
    border-color: #212529;
    background-color: #ffffff;
  }
  .btn-outline-secondary:hover,
  .btn-outline-secondary:focus,
  .btn-outline-secondary:active {
    color: #ffffff !important;
    background-color: #212529 !important;
    border-color: #212529 !important;
  }
  .pn-section-title {
    background-color: #e9ecef;
    border-bottom: 1px solid #dee2e6;
    padding: 0.5rem 0.75rem;
    font-weight: 600;
  }
  .pn-content {
    background-color: #ffffff;
  }
  .pn-news-card .card-header {
    font-weight: 600;
  }
</style>
</head>
<body>
<!--Hinweis auf die Readme und dass es sich hierbei nur um Beispieldateien handelt!-->
<div class="container my-3">
  <div class="alert alert-warning border" role="alert">
    <strong>Bitte beachten</strong><br>
    Bitte lesen Sie sich die <a href="readme.html" target="_blank" rel="noopener noreferrer" class="alert-link">ReadMe</a> durch falls Sie Schwierigkeiten haben.<br>
    Dieses Design ist nur zu Testzwecken eingerichtet, Sie sollten PowerNews also an Ihr eigenes Seitendesign anpassen. Es m&uuml;ssen weder Dateinamen noch HTML Strukturen &uuml;bernommen werden!
  </div>
</div>

<!--Das kleine Men� der Seite-->
<div class="container">
  <div class="row g-3">
    <aside class="col-12 col-md-3">
      <nav class="card" aria-label="Hauptnavigation">
        <div class="card-body">
          <h2 class="h6 fw-bold border-bottom pb-2">News</h2>
          <ul class="list-unstyled mb-3">
            <li>&raquo; <a href="index.php">Home</a></li>
            <li>&raquo; <a href="archive.php">Archiv</a></li>
            <li>&raquo; <a href="sendnews.php">News einsenden</a></li>
          </ul>

          <h2 class="h6 fw-bold border-bottom pb-2">Benutzer</h2>
          <div class="mb-3">
<?php
    /*
      Hier wird die usermenu.inc.php Datei eingef�gt um das Benutzermen� anzeigen zu lassen.
    */
    include __DIR__ . '/pninc/usermenu.inc.php';
?>
          </div>
          <div>
            &raquo; <a href="./pnadmin/">Adminbereich</a>
          </div>
        </div>
      </nav>
    </aside>

    <main class="col-12 col-md-9" id="pn-main">
