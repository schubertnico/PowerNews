<?php
/*
Hier wird die head.inc.php in die Datei eingefügt.
Es ist wichtig, dass es vor dem <?PHP keine anderen Zeichen in der Datei gibt!
*/
include __DIR__ . '/pninc/head.inc.php';
?>
<html>
<head>
<title>PowerNews 3.0</title>
<meta http-equiv="Content-Type" content="text/html; charset=windows-1252">
<meta content="powerscripts.org" name="author">

<style>
<!--
  BODY, TABLE, TR, TD {
    font-family: Verdana;
    font-size: 11px;
    color: #000000;
  }

  A {
    color: #000080;
    text-decoration: none;
  }

  A:HOVER {
    text-decoration: underline;
  }

-->
</style>
</head>

<body text="#000000" vlink="#000080" alink="#000080" link="#000080" bgcolor="#FFFFFF" scroll="auto">
<center>

<!--Hinweis auf die Readme und dass es sich hierbei nur um Beispieldateien handelt!-->
<table border="0" cellpadding="0" cellspacing="0" width="50%">
<tr><td bgcolor="#000000">
  <table border="0" cellpadding="3" cellspacing="1" width="100%">
  <tr><td bgcolor="#FFFFFF">
  <font family="Verdana" size="2" color="#FF0000">
  <b>Bitte beachten</b><br>
  <br>
  Bitte lesen Sie sich die <a href="readme.html" target="_blank">ReadMe</a> durch falls Sie Schwierigkeiten haben.<br>
  Dieses Design ist nur zu Testzwecken eingerichtet, Sie sollten PowerNews also an Ihr eigenes Seitendesign anpassen. Es müssen weder Dateinamen noch HTML Strukturen übernommen werden!
  </font>
  </td></tr>
  </table>
</td></tr>
</table>

<br>

<!--Das kleine Menü der Seite-->

<table cellspacing="0" cellpadding="0" width="90%" border="0">
<tr><td bgcolor="#000000">
  <table border="0" cellpadding="3" cellspacing="1" width="100%">
  <tr><td width="125" valign="top" bgcolor="#DEDFDE">
  <b>News</b><br>
  &raquo; <a href="index.php">Home</a><br>
  &raquo; <a href="archive.php">Archiv</a><br>
  &raquo; <a href="sendnews.php">News einsenden</a><br>
  <br>
  <br>
  <b>Benutzer</b><br>
  <?php
    /*
      Hier wird die usermenu.inc.php Datei eingefügt um das Benutzermenü anzeigen zu lassen.
    */
    include __DIR__ . '/pninc/usermenu.inc.php';
?><br>
  <br>
  &raquo; <a href="./pnadmin/">Adminbereich</a><br>
  <br>
  </td><td witdh="*" valign="top" bgcolor="#ADAEAD">