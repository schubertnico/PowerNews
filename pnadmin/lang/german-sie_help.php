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

/* German help file written by PowerScripts (Stefan Kraemer) */
?>
<a name="#top"></a>
<ul type="square">
  <li><a href="#user">Benutzer</a>
  <li><a href="#permissions">Berechtigungen</a>
  <li><a href="#configuration">Konfiguration</a>
  <li><a href="#templates">Templates</a>
  <li><a href="#categories">Kategorien</a>
  <li><a href="#news">News</a>
  <li><a href="#other">Sonstiges</a>
</ul>

<!-- USER -->

  <a name="#user"></a><b>BENUTZER</b>
  <ul>
    <li><a href="#user.add">Benutzer hinzuf&uuml;gen</a>
    <li><a href="#user.edit">Benutzer editieren</a>
    <li><a href="#user.delete">Benutzer l&ouml;schen</a>
  </ul>

    <blockquote>
    <a name="#user.add"></a><b>BENUTZER HINZUF&uuml;GEN</b><br>
    Normalerweise registrieren sich die Benutzer auf der externen Seite. Sollte man dieses Feature jedoch
    nicht eingebaut haben, so kann man &uuml;ber den Adminbereich
    <a href="index.php?page=users&subpage=add">Benutzer hinzuf&uuml;gen</a>. Diese Funktion ist wichtig, da ein
    Benutzer erst Administrator werden kann wenn er registriert ist. Es wird empfohlen dem neuen Benutzer eine
    eMail mit seinen Daten zukommen zu lassen damit dieser auch dar&uuml;ber informiert ist, dass er jetzt
    registriert ist.
    <div align="right">[ <a href="#top">top</a> ]</div>
    </blockquote>

    <blockquote>
    <a name="#user.edit"></a><b>BENUTZER EDITIEREN</b><br>
    Es ist m&ouml;glich die Benutzer &uuml;ber den Adminbereich zu editieren. Um einen Benutzer auszuw&auml;hlen kann man
    diesen entweder aus der <a href="index.php?page=users&subpage=show">Benutzerliste</a> ausw&auml;hlen oder ihn
    &uuml;ber das <a href="index.php?page=users&subpage=search">Suchformular</a> suchen.<br>
    Wenn der Status eines Benutzers auf <b>Deaktiviert</b> steht, so kann dieser keine Kommentare posten und
    keine News einsenden. Au&szlig;erdem ist es dann auch nicht m&ouml;glich dem Benutzer Adminrechte zu geben.<br>
    Sollte man Nickname oder Passwort des Benutzers editieren so ist es von Vorteil, wenn man ihm eine eMail
    mit seinen neuen Logindaten zukommen l&auml;sst.
    <div align="right">[ <a href="#top">top</a> ]</div>
    </blockquote>

    <blockquote>
    <a name="#user.delete"></a><b>BENUTZER L&ouml;SCHEN</b><br>
    Zur Zeit ist es nicht m&ouml;glich Benutzer zu l&ouml;schen, jedoch kann der Status des Benutzers auf
    <b>Deaktiviert</b> gesetzt werden.
    <div align="right">[ <a href="#top">top</a> ]</div>
    </blockquote>

<!-- PERMISSIONS -->

  <a name="#permissions"></a><b>BERECHTIGUNGEN</b>
  <ul>
    <li><a href="#permissions.add">Berechtigungen hinzuf&uuml;gen</a>
    <li><a href="#permissions.edit">Berechtigungen editieren</a>
    <li><a href="#permissions.delete">Berechtigungen l&ouml;schen</a>
  </ul>

    <blockquote>
    <a name="#permissions.add"></a><b>BERECHTIGUNGEN HINZUF&uuml;GEN</b><br>
    Berechtigungen werden hinzugef&uuml;gt indem man den korrekten Nicknamen des Benutzers angibt der
    Adminrechte bekommen soll und dann ausw&auml;hlt welche Berechtigungen der Benutzer bekommt.<br>
    Der Benutzer wird nach dem
    <a href="index.php?page=permissions&subpage=add">Hinzuf&uuml;gen der Berechtigungen</a> nicht &uuml;ber seine
    Adminrechte informiert, man sollte ihn deshalb selbst aufkl&auml;ren.
    <div align="right">[ <a href="#top">top</a> ]</div>
    </blockquote>

    <blockquote>
    <a name="#permissions.edit"></a><b>BERECHTIGUNGEN EDITIEREN</b><br>
    Um die Berechtigungen zu editieren w&auml;hlt man den Benutzer dessen Berechtigungen man editieren will
    in der <a href="index.php?page=permissions&subpage=show">Berechtigunsliste</a> aus. Hier kann man die
    Berechtigungen des Benutzers schon auf einen Blick erfassen. Mit einem Klick auf den Nickname des
    Admins kann man dann die Rechte editieren.
    <div align="right">[ <a href="#top">top</a> ]</div>
    </blockquote>

    <blockquote>
    <a name="#permissions.delete"></a><b>BERECHTIGUNGEN L&ouml;SCHEN</b><br>
    Um die Berechtigungen eines Benutzers zu l&ouml;schen geht man vor wie beim
    <a href="#permissions.edit">Berechtigungen editieren</a> nur aktiviert man das H&auml;ckchen bei
    <b>L&ouml;schen</b>.
    <div align="right">[ <a href="#top">top</a> ]</div>
    </blockquote>

<!-- CONFIGURATION -->

  <a name="#configuration"></a><b>KONFIGURATION</b>
  <ul>
    <li><a href="#configuration.categories">Kategorien</a>
    <li><a href="#configuration.catpics">Kategoriebilder</a>
    <li><a href="#configuration.comments">Kommentare</a>
    <li><a href="#configuration.writecomments">Kommentare schreiben</a>
    <li><a href="#configuration.longtext">Textaufteilung</a>
    <li><a href="#configuration.sendnews">Einsenden Funktion</a>
    <li><a href="#configuration.newssending">News einsenden</a>
    <li><a href="#configuration.smilies">Smilies</a>
    <li><a href="#configuration.bbcode">BB Code</a>
    <li><a href="#configuration.html">HTML</a>
    <li><a href="#configuration.dateformat">Datums- & Zeitformat</a>
    <li><a href="#configuration.template">Template</a>
    <li><a href="#configuration.url">URL</a>
    <li><a href="#configuration.email">eMail</a>
    <li><a href="#configuration.headlines">Headlines</a>
    <li><a href="#configuration.news">News</a>
    <li><a href="#configuration.spamprotection">Spamschutz</a>
  </ul>

    <blockquote>
    <a name="#configuration.categories"></a><b>KATEGORIEN</b><br>
    &uuml;ber diese Einstellung kann man ausw&auml;hlen, ob man Newskategorien verwenden will oder nicht. Sollte diese
    Option aktiviert sein, so muss mindestens eine Kategorie bestehen damit man News schreiben kann.
    <div align="right">[ <a href="#top">top</a> ]</div>
    </blockquote>

    <blockquote>
    <a name="#configuration.catpics"></a><b>KATEGORIEBILDER</b><br>
    Wenn man Bilder f&uuml;r die Newskategorien verwenden m&ouml;chte, dann muss diese Option aktiviert sein. Sollte
    man generell keine <a href="#configuration.categories">Kategorien</a> erlauben, so ist diese Option
    deaktiviert.
    <div align="right">[ <a href="#top">top</a> ]</div>
    </blockquote>

    <blockquote>
    <a name="#configuration.comments"></a><b>KOMMENTARE</b><br>
    &uuml;ber diese Option kann man einstellen ob Kommentare zu Newseintr&auml;gen erlaubt sind oder nicht.
    <div align="right">[ <a href="#top">top</a> ]</div>
    </blockquote>

    <blockquote>
    <a name="#configuration.writecomments"></a><b>KOMMENTARE SCHREIBEN</b><br>
    Hier kann man einstellen wer alles Newskommentare schreiben darf. Sollten keine Kommentare erlaubt sein
    so ist diese Einstellung deaktiviert.
    <div align="right">[ <a href="#top">top</a> ]</div>
    </blockquote>

    <blockquote>
    <a name="#configuration.longtext"></a><b>TEXTAUFTEILUNG</b><br>
    Wenn diese Option aktiviert ist, so kann man beim Newsschreiben einen kurzen und einen langen Text
    angeben. Der kurze Text wird auf der Startseite angezeigt, der lange als Erg&auml;nzung wenn man sich die
    News ganz ansieht.
    <div align="right">[ <a href="#top">top</a> ]</div>
    </blockquote>

    <blockquote>
    <a name="#configuration.sendnews"></a><b>EINSENDEN FUNKTION</b><br>
    Diese Option regelt ob Benutzer News einsenden d&uuml;rfen oder nicht.
    <div align="right">[ <a href="#top">top</a> ]</div>
    </blockquote>

    <blockquote>
    <a name="#configuration.newssending"></a><b>NEWS EINSENDEN</b><br>
    Durch diese Option kann man bestimmen ob nur registrierte Benutzer News einsenden k&ouml;nnen oder auch G&auml;ste.
    <div align="right">[ <a href="#top">top</a> ]</div>
    </blockquote>

    <blockquote>
    <a name="#configuration.smilies"></a><b>SMILIES</b><br>
    Hier kann man w&auml;hlen ob Smilies erlaubt werden sollen und wenn ja wo. Unter <a href="#other">Sonstiges</a>
    sind alle m&ouml;glichen <a href="#other.smilies">Smilies</a> aufgelistet.
    <div align="right">[ <a href="#top">top</a> ]</div>
    </blockquote>

    <blockquote>
    <a name="#configuration.bbcode"></a><b>BB CODE</b><br>
    Hier kann man w&auml;hlen ob BB Code erlaubt werden soll und wenn ja wo. Unter <a href="#other">Sonstiges</a>
    sind alle m&ouml;glichen <a href="#other.bbcode">BB Codes</a> aufgelistet.
    <div align="right">[ <a href="#top">top</a> ]</div>
    </blockquote>

    <blockquote>
    <a name="#configuration.html"></a><b>HTML</b><br>
    Hier kann man w&auml;hlen ob HTML erlaubt werden soll und wenn ja wo. Unter <a href="#other">Sonstiges</a>
    ist eine &uuml;bersicht der wichtigsten <a href="#other.html">HTML Befehle</a> zu finden.
    <div align="right">[ <a href="#top">top</a> ]</div>
    </blockquote>

    <blockquote>
    <a name="#configuration.dateformat"></a><b>DATUMS- & ZEITFORMAT</b><br>
    &uuml;ber diese Option kann man einstellen wie das Datum und die Zeit ausgegeben wird. Dabei sind eine Vielzahl
    von Platzhaltern m&ouml;glich, hier ein Auszug:<br>
    <ul type="square">
      <li><b>%d</b> f&uuml;r den Tag (01-31)
      <li><b>%m</b> f&uuml;r den Monat (01-12)
      <li><b>%Y</b> f&uuml;r das Jahr (z.B. 2002)
      <li><b>%H</b> f&uuml;r die Stunden (00-23)
      <li><b>%M</b> f&uuml;r die Minuten (01-59)
    </ul>
    Die gesamte Liste von Platzhaltern finden Sie auf der
    <a href="http://www.php.net/manual/de/function.strftime.php" target="_blank">PHP Webseite</a>.
    <div align="right">[ <a href="#top">top</a> ]</div>
    </blockquote>

    <blockquote>
    <a name="#configuration.template"></a><b>TEMPLATE</b><br>
    &uuml;ber diese Option kann das aktive Template gew&auml;hlt werden. &uuml;ber die
    <a href="index.php?page=templates&subpage=add">Template hinzuf&uuml;gen</a> Funktion k&ouml;nnen weitere Templates
    erstellt werden.
    <div align="right">[ <a href="#top">top</a> ]</div>
    </blockquote>

    <blockquote>
    <a name="#configuration.url"></a><b>URL</b><br>
    Hier muss die korrekte URL zum PowerNews Verzeichnis angegeben werden. Sollte sich der Adminbereich also
    zum Beispiel unter <b>http://www.host.tld/dir/pnadmin</b> befinden, so muss der korrekte Pfad
    <b>http://www.host.tld/dir</b> lauten. Die URL wird ohne den letzten Slash angegeben.
    <div align="right">[ <a href="#top">top</a> ]</div>
    </blockquote>

    <blockquote>
    <a name="#configuration.email"></a><b>EMAIL</b><br>
    Die hier angegebene Adresse wird verwendet um alle automatisch generierten eMails abzuschicken. Es ist
    wichtig, dass hier eine korrekte eMail Adresse steht, da der Mailverkehr ansonsten nicht korrekt
    ausgef&uuml;hrt werden k&ouml;nnte.
    <div align="right">[ <a href="#top">top</a> ]</div>
    </blockquote>

    <blockquote>
    <a name="#configuration.headlines"></a><b>HEADLINES</b><br>
    Hier wird die Anzahl der Headlines angegeben die an der Stelle angezeigt werden wo die Headline Datei
    eingebunden wurde.
    <div align="right">[ <a href="#top">top</a> ]</div>
    </blockquote>

    <blockquote>
    <a name="#configuration.news"></a><b>NEWS</b><br>
    Hier wird die Anzahl der Headlines angegeben die an der Stelle angezeigt werden wo die Headline Datei
    eingebunden wurde.
    <div align="right">[ <a href="#top">top</a> ]</div>
    </blockquote>

    <blockquote>
    <a name="#configuration.spamprotection"></a><b>SPAMSCHUTZ</b><br>
    Hier wird die Zwangspause beim Posten von 2 Kommentaren hintereinander in Sekunden eingestellt.
    Als Minimum muss 1 Sekunde eingestellt werden, das Maximum liegt bei 999 Sekunden (16,65 Minuten).
    <div align="right">[ <a href="#top">top</a> ]</div>
    </blockquote>

    <blockquote>
    <a name="#configuration.relatedlinks"></a><b>RELATED LINKS</b><br>
    Hier k&ouml;nnen Sie bestimmen ob man bei den News Related Links verwenden kann. Diese Links werden dann bei den
    News an der in den Templates gew&auml;hlten Stelle eingef&uuml;gt.
    <div align="right">[ <a href="#top">top</a> ]</div>
    </blockquote>

    <blockquote>
    <a name="#configuration.relatedlinks_num"></a><b>ANZAHL RELATED LINKS</b><br>
    Hier wird die Anzahl der Related Links bestimmt. F&uuml;r jeden Related Link werden beim
    <a href="index.php?page=news&subpage=add">News schreiben</a> Formular 2 Felder f&uuml;r den Titel des Links und
    das Ziel hinzugef&uuml;gt. Die maximale Anzahl der Links liegt bei 99.
    <div align="right">[ <a href="#top">top</a> ]</div>
    </blockquote>

<!-- TEMPLATES -->

  <a name="#templates"></a><b>TEMPLATES</b>
  <ul>
    <li><a href="#templates.add">Templates hinzuf&uuml;gen</a>
    <li><a href="#templates.edit">Templates editieren</a>
    <li><a href="#templates.default">Default Template</a>
  </ul>

    <blockquote>
    <a name="#templates.add"></a><b>TEMPLATES HINZUF&uuml;GEN</b><br>
    Unter <a href="index.php?page=templates&subpage=add">Template hinzuf&uuml;gen</a> wird ein Titel f&uuml;r das neue
    Template angegeben. Dann wird das Standardtemplate als Grundlage f&uuml;r das neue Template eingef&uuml;gt.<br>
    Das Template kann dann &uuml;ber <a href="index.php?page=templates&subpage=show">Template Auswahl</a> gew&auml;hlt
    und editiert werden.
    </blockquote>

    <blockquote>
    <a name="#templates.edit"></a><b>TEMPLATES EDITIEREN</b><br>
    Nach dem <a href="index.php?page=templates&subpage=show">Ausw&auml;hlen des Templates</a> kann dieses editiert
    werden. Die fett geschriebenen Platzhalter in geschweiften Klammern (z.B. <b>{ID}</b>) stehen f&uuml;r
    verschiedene dynamische Inhalte. Betrachtet das
    <a href="index.php?page=templates&subpage=edit&templateid=1">Default Template</a> um einen Einblick in die
    Funktion zu bekommen.
    </blockquote>

    <blockquote>
    <a name="#templates.default"></a><b>DEFAULT TEMPLATE</b><br>
    Das <a href="index.php?page=templates&subpage=edit&templateid=1">Default Template</a> dient als Vorlage f&uuml;r
    eigene Templates. Das Default Template kann weder editiert noch gel&ouml;scht werden.<br>
    Jedes neu erstellte Template ent&auml;hlt anfangs die Inhalte des Default Templates, andere Templates k&ouml;nnen
    jedoch an das eigene Design angepasst werden.
    </blockquote>

<!-- CATEGORIES -->

  <a name="#categories"></a><b>KATEGORIEN</b>
  <ul>
    <li><a href="#categories.add">Kategorien hinzuf&uuml;gen</a>
    <li><a href="#categories.edit">Kategorien editieren</a>
    <li><a href="#categories.delete">Kategorien l&ouml;schen</a>
  </ul>

    <blockquote>
    <a name="#categories.add"></a><b>KATEGORIEN HINZUF&uuml;GEN</b><br>
    &uuml;ber den Punkt <a href="index.php?page=categories&subpage=add">Kategorie hinzuf&uuml;gen</a> kann man, wenn die
    Kategorien &uuml;ber die <a href="#configuration.categories">Konfiguration</a> freigeschaltet sind, neue
    Kategorien erstellen, eine Beschreibung f&uuml;r dieselbe angeben und, falls
    <a href="#configuration.catpics">aktiviert</a>, ein Bild f&uuml;r diese Kategorie hochladen.<br>
    Die Beschreibung der Kategorie darf h&ouml;chstens 255 Zeichen lang sein!
    <div align="right">[ <a href="#top">top</a> ]</div>
    </blockquote>

    <blockquote>
    <a name="#categories.edit"></a><b>KATEGORIEN EDITIEREN</b><br>
    &uuml;ber die <a href="index.php?page=categories&subpage=show">Kategorienliste</a> kann man eine Kategorie
    ausw&auml;hlen welche man dann editieren kann. Wenn man f&uuml;r die Kategorie ein neues Bild hochladen m&ouml;chte, dann
    muss man zuerst die Checkbox neben "Bild hochladen" aktivieren und dann ein neues Bild von der Festplatte
    ausw&auml;hlen.
    <div align="right">[ <a href="#top">top</a> ]</div>
    </blockquote>

    <blockquote>
    <a name="#categories.delete"></a><b>KATEGORIEN L&ouml;SCHEN</b><br>
    Kategorien k&ouml;nnen nicht direkt gel&ouml;scht werden, da davon die News betroffen werden. Es ist aber m&ouml;glich
    den Status der News auf "Deaktiviert" zu setzten, dadurch ist es nicht m&ouml;glich weiterhin News in diese
    Kategorie zu posten und die Kategorie wird aus der Kategorieauswahl entfernt.
    <div align="right">[ <a href="#top">top</a> ]</div>
    </blockquote>

<!-- NEWS -->

  <a name="#news"></a><b>NEWS</b>
  <ul>
    <li><a href="#news.add">News schreiben</a>
    <li><a href="#news.list">News auflisten</a>
    <li><a href="#news.edit">News editieren/l&ouml;schen</a>
    <li><a href="#news.search">News suchen</a>
  </ul>

    <blockquote>
    <a name="#news.add"></a><b>NEWS SCHREIBEN</b><br>
    Unter <a href="index.php?page=news&subpage=add">News schreiben</a> kann man neue Newseintr&auml;ge
    erstellen.<br>
    Dabei w&auml;hlt man, falls aktiviert, zuerst eine Kategorie in der die News erscheinen sollen, danach gibt
    man den Titel des Eintrags an und macht sich dann an den Newstext. Sollte die Funktion aktiviert sein,
    so kann man noch einen langen Text angeben. Dabei erscheint dann nur der normale Text auf der Mainpage,
    der lange Text wird dann erst bei einem klick auf den Mehr-Link angezeigt.<br>
    Sollte es erw&uuml;nscht sein, so kann man das Datum des Newsreleases auch manuell einstellen. Als
    Standardeinstellung wird das momentane Datum und die momentane Zeit verwendet. Die News erscheinen erst
    auf der Startseite und im Archiv wenn der eingestellte Termin vor&uuml;ber ist.<br>
    Wenn die <a href="#configuration.relatedlinks">Related Links</a> in der Konfiguration aktiviert sind kann
    man unter dem Newstext noch eine Anzahl von Links mit Titel und Zielfenster angeben.<br>
    In der Kurzbeschreibung unter dem Titel des Feldes kann man lesen ob man HTML/BB Code verwenden darf.
    <div align="right">[ <a href="#top">top</a> ]</div>
    </blockquote>

    <blockquote>
    <a name="#news.list"></a><b>NEWS AUFLISTEN</b><br>
    Unter <a href="index.php?page=news&subpage=show">News auflisten</a> k&ouml;nnen alle bisherigen Newseintrage
    eingesehen werden. Unter Status gibt es drei m&ouml;gliche Grafiken:
      <ul type="square">
        <li><b>Gr&uuml;ner Haken</b><br>
            Die News sind freigeschaltet und aktiv. Das hei&szlig;t jeder kann die News extern lesen und
            kommentieren (falls Funktion aktiviert).
        <li><b>Grauer Strich</b><br>
            Der graue Strich bedeutet, dass die News von einem Besucher der Seite eingesendet wurden, aber
            noch nicht freigeschaltet wurden. Die News erscheinen extern nicht und sind auch nicht
            kommentierbar.
        <li><b>Rotes Kreuz</b><br>
            Das rote Kreuz zeigt an, dass die News deaktiviert sind. Das bedeutet, dass sie extern nicht
            einsehbar und nicht kommentierbar sind.
      </ul>
    <div align="right">[ <a href="#top">top</a> ]</div>
    </blockquote>

    <blockquote>
    <a name="#news.edit"></a><b>NEWS EDITIEREN/L&ouml;SCHEN</b><br>
    Um News zu editieren w&auml;hlt man diese direkt aus der
    <a href="index.php?page=news&subpage=show">Auflistung</a> oder aus dem
    <a href="index.php?page=news&subpage=search">Suchergebnis</a> aus.<br>
    Wenn man ein H&auml;ckchen bei L&ouml;schen setzt und die News dann editiert so werden diese total aus der
    Datenbank gel&ouml;scht und sind nicht wieder herstellbar.<br>
    Es ist m&ouml;glich die News einfach von der externen Seite zu nehmen indem man den Status auf Deaktiviert
    setzt.<br>
    Ansonsten gilt das gleiche wie bei <a href="#news.add">News schreiben</a>.
    <div align="right">[ <a href="#top">top</a> ]</div>
    </blockquote>

    <blockquote>
    <a name="#news.search"></a><b>NEWS SUCHEN</b><br>
    Da mit der Zeit die Anzahl der Newseintr&auml;ge un&uuml;berschaubar wird und man sich sehr lange durch die
    <a href="index.php?page=news&subpage=show">Liste</a> klicken muss um den gew&uuml;nschten Eintrag zu finden,
    gibt es die M&ouml;glichkeit <a href="index.php?page=news&subpage=search">News zu suchen</a>.<br>
    Hier w&auml;hlt man einfach in welchem der Felder (Titel, Text, ID, Langer Text) man nach welchem Wort suchen
    will. Das Ergebnis wird als Liste wie unter <a href="#news.list">News auflisten</a> beschrieben
    dargestellt.
    <div align="right">[ <a href="#top">top</a> ]</div>
    </blockquote>

<!-- SONSTIGES -->

  <a name="#other"></a><b>SONSTIGES</b>
  <ul>
    <li><a href="#other.bbcode">BB Code</a>
    <li><a href="#other.html">HTML</a>
    <li><a href="#other.smilies">Smilies</a>
    <li><a href="#other.about">&uuml;ber PowerNews</a>
  </ul>

    <blockquote>
    <a name="#other.bbcode"></a><b>BB CODE</b><br>
    Die folgenden BB Codes k&ouml;nnen in den News und in den Kommentaren (falls aktiviert) verwendet werden.
    Die Kommandos selbst sind <b>fett</b> gekennzeichnet.<br>
    <br>
    <center>
      <table border="0" cellpadding="3" cellspacing="3">
      <tr><td>
      <u><b>Eingabe</b></u>
      </td><td width="25">
      </td><td>
      <u><b>Ausgabe</b></u>
      </td></tr>
      <tr><td>
      <b>[b]</b>PowerNews<b>[/b]</b>
      </td><td>
      </td><td>
      <b>PowerNews</b>
      </td></tr>
      <tr><td>
      <b>[u]</b>PowerNews<b>[/u]</b>
      </td><td>
      </td><td>
      <u>PowerNews</u>
      </td></tr>
      <tr><td>
      <b>[i]</b>PowerNews<b>[/i]</b>
      </td><td>
      </td><td>
      <i>PowerNews</i>
      </td></tr>
      <tr><td>
      <b>[url]</b>http://www.powerscripts.org<b>[/url]</b><br>
      <b>[url]</b>www.powerscripts.org<b>[/url]</b>
      </td><td>
      </td><td>
      <a href="http://www.powerscripts.org" target="_blank">http://www.powerscripts.org</a><br>
      <a href="http://www.powerscripts.org" target="_blanK">www.powerscripts.org</a>
      </td></tr>
      <tr><td>
      <b>[email]</b>info@powerscripts.org<b>[/email]</b>
      </td><td>
      </td><td>
      <a href="mailto:info@powerscripts.org">info@powerscripts.org</a>
      </td></tr>
      <tr><td>
      <b>[img]</b>http://www.powerscripts.org/gfx/psbutton.gif<b>[/img]</b>
      </td><td>
      </td><td>
      <img src="http://www.powerscripts.org/gfx/psbutton.gif" border="0">
      </td></tr>
      </table>
    </center>
    <div align="right">[ <a href="#top">top</a> ]</div>
    </blockquote>

    <blockquote>
    <a name="#other.html"></a><b>HTML</b><br>
    HTML ist eine Formatierungssprache f&uuml;r das Internet. Mit Hilfe der <u>H</u>yper<u>t</u>ext <u>M</u>arkup
    <u>L</u>anguage kann man Tabellen erstellen, Schriftarten festlegen, Texte fett, kursiv oder unterstrichen
    schreiben etc.<br>
    F&uuml;r einen Webmaster sind zumindest grundlegende HTML Kenntnisse erforderlich. Zu empfehlen ist hier das
    HTML Nachschlagewerk <a href="http://selfhtml.teamone.de/" target="_blank">SelfHTML</a>.
    <div align="right">[ <a href="#top">top</a> ]</div>
    </blockquote>

    <blockquote>
    <a name="#other.smilies"></a><b>SMILIES</b><br>
    Die folgenden Smilies sind f&uuml;r News und Kommentare (falls aktiviert) verf&uuml;gbar:<br>
    <br>
    <center>
      <table border="0" cellpadding="3" cellspacing="3">
      <tr><td>
      <u><b>Eingabe</b></u>
      </td><td width="25">
      </td><td>
      <u><b>Ausgabe</b></u>
      </td></tr>
      <tr><td align="center">
      <b>:)</b>
      </td><td>
      </td><td align="center">
      <img src="../pngfx/smilies/smile.gif" width="15" height="15" border="0">
      </td></tr>
      <tr><td align="center">
      <b>;)</b>
      </td><td>
      </td><td align="center">
      <img src="../pngfx/smilies/wink.gif" width="15" height="15" border="0">
      </td></tr>
      <tr><td align="center">
      <b>:))</b>
      </td><td>
      </td><td align="center">
      <img src="../pngfx/smilies/laugh.gif" width="15" height="15" border="0">
      </td></tr>
      <tr><td align="center">
      <b>:D</b>
      </td><td>
      </td><td align="center">
      <img src="../pngfx/smilies/bigsmile.gif" width="15" height="15" border="0">
      </td></tr>
      <tr><td align="center">
      <b>:P</b>
      </td><td>
      </td><td align="center">
      <img src="../pngfx/smilies/tongue.gif" width="15" height="15" border="0">
      </td></tr>
      <tr><td align="center">
      <b>:(</b>
      </td><td>
      </td><td align="center">
      <img src="../pngfx/smilies/sad.gif" width="15" height="15" border="0">
      </td></tr>
      <tr><td align="center">
      <b>:?:</b>
      </td><td>
      </td><td align="center">
      <img src="../pngfx/smilies/confused.gif" width="15" height="22" border="0">
      </td></tr>
      </table>
    </center>
    <div align="right">[ <a href="#top">top</a> ]</div>
    </blockquote>

    <blockquote>
    <a name="#other.about"></a><b>&uuml;BER POWERNEWS</b><br>
    PowerNews ist ein Projekt der <a href="http://www.powerscripts.org" target="_blank">PowerScripts</a>
    Entwicklungsgruppe und wurde von <b>Stefan Kraemer</b> programmiert.<br>
    PowerNews ist ein leistungsstarkes Newssystem dass &uuml;ber eine mySQL Datenbank eine komfortable und
    schnelle Newsverwaltung erm&ouml;glicht.<br>
    Zudem ist ab Version 2.5 ein eigenes Benutzersystem integriert dass die Verwaltung noch mal leichter
    gestaltet.<br>
    PowerNews bietet somit auch eine ideale M&ouml;glichkeit f&uuml;r den Aufbau eines ganzen Communitysystems.
    <div align="right">[ <a href="#top">top</a> ]</div>
    </blockquote>
