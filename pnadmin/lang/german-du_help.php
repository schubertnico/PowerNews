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
/* Komplett ueberarbeitet auf den aktuellen Funktionsumfang. */
?>
<div id="pn-help-top"></div>

<p class="lead">Diese Hilfe beschreibt den aktuellen Funktionsumfang des PowerNews-Adminbereichs. Klicke auf einen der Bereiche, um direkt zur Erklärung zu springen.</p>

<nav class="card mb-4">
    <div class="card-body">
        <h2 class="h6 fw-bold mb-2">Inhalt</h2>
        <ul class="mb-0">
            <li><a href="#help-news">News</a></li>
            <li><a href="#help-categories">Kategorien</a></li>
            <li><a href="#help-users">Benutzer</a></li>
            <li><a href="#help-permissions">Berechtigungen</a></li>
            <li><a href="#help-templates">Templates</a></li>
            <li><a href="#help-configuration">Konfiguration</a></li>
            <li><a href="#help-profile">Eigenes Profil</a></li>
            <li><a href="#help-other">Sonstiges (BB-Code, Smilies, Lizenz)</a></li>
        </ul>
    </div>
</nav>

<!-- ==================== NEWS ==================== -->
<section id="help-news" class="mb-4">
    <h2 class="h5 fw-bold border-bottom pb-2">News</h2>
    <ul>
        <li><a href="#help-news-add">News einsenden</a></li>
        <li><a href="#help-news-show">News auflisten</a></li>
        <li><a href="#help-news-edit">News editieren / löschen / Kommentare moderieren</a></li>
        <li><a href="#help-news-search">News suchen</a></li>
        <li><a href="#help-news-status">Status-Erklärung</a></li>
    </ul>

    <div id="help-news-add" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">News einsenden</h3>
        <p>Über <a href="index.php?page=news&amp;subpage=add">News &gt; News einsenden</a> kannst Du einen neuen Eintrag anlegen. Pflichtfelder sind <strong>Titel</strong> und <strong>Text</strong>; ist die Kategorie-Funktion aktiv, ist zusätzlich eine <strong>Kategorie</strong> erforderlich.</p>
        <p>Der Veröffentlichungszeitpunkt wird per Tag/Monat/Jahr und Stunde:Minute gesetzt. Liegt der Zeitpunkt in der Zukunft, erscheint die News erst dann auf der Startseite.</p>
        <p>Ist <em>Textaufteilung</em> in der Konfiguration aktiviert, kannst Du zusätzlich einen <strong>langen Text</strong> hinterlegen. Dieser erscheint erst auf der Detailseite. Sind <em>Related Links</em> aktiv, kannst Du pro Eintrag mehrere Verweis-Titel/-URLs/-Targets eintragen.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Zurück nach oben</a></p>
    </div>

    <div id="help-news-show" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">News auflisten</h3>
        <p>Unter <a href="index.php?page=news&amp;subpage=show">News &gt; News anzeigen</a> findest Du alle Beiträge. Pro Seite werden 25 Einträge angezeigt; mit der Pagination am oberen und unteren Rand der Tabelle springst Du zwischen den Seiten. Klick auf den Titel öffnet die Detail-/Editieransicht.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Zurück nach oben</a></p>
    </div>

    <div id="help-news-edit" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">News editieren / löschen / Kommentare moderieren</h3>
        <p>Wähle eine News in der <a href="index.php?page=news&amp;subpage=show">News-Liste</a> oder im <a href="index.php?page=news&amp;subpage=search">Suchergebnis</a> aus. Du kannst alle Felder, die Kategorie, das Veröffentlichungsdatum sowie den Status ändern.</p>
        <p>Die rot umrandete Box <strong>News löschen</strong> entfernt den Eintrag <strong>endgültig</strong> aus der Datenbank. Wenn Du eine News nur ausblenden willst, setze stattdessen den Status auf <em>Deaktiviert</em>.</p>
        <p>Sind Kommentare in der <a href="#help-configuration">Konfiguration</a> aktiviert, erscheint unterhalb des News-Formulars ein zweiter Bereich zum <strong>Editieren der Kommentare</strong>. Du kannst den Text jedes Kommentars überarbeiten und über die rot markierte Checkbox einzelne Kommentare löschen.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Zurück nach oben</a></p>
    </div>

    <div id="help-news-search" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">News suchen</h3>
        <p>Unter <a href="index.php?page=news&amp;subpage=search">News &gt; News suchen</a> kannst Du in den Feldern Titel, Text, ID oder – falls aktiviert – Langer Text nach einem Begriff suchen. Das Ergebnis wird als Tabelle wie unter <a href="#help-news-show">News anzeigen</a> dargestellt.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Zurück nach oben</a></p>
    </div>

    <div id="help-news-status" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Status-Erklärung</h3>
        <p>In der News-Liste zeigt eine Status-Markierung den aktuellen Zustand:</p>
        <ul>
            <li><span class="badge text-bg-success">Aktiviert</span> &ndash; Die News ist freigegeben, im Frontend sichtbar und kommentierbar (sofern der Veröffentlichungszeitpunkt erreicht ist).</li>
            <li><span class="badge text-bg-warning">Ungeprüft</span> &ndash; Die News wurde von einem Besucher eingesendet und wartet auf eine Freigabe. Sie erscheint im Frontend nicht.</li>
            <li><span class="badge text-bg-danger">Deaktiviert</span> &ndash; Die News ist im Frontend nicht sichtbar.</li>
        </ul>
        <p class="text-end mb-0"><a href="#pn-help-top">Zurück nach oben</a></p>
    </div>
</section>

<!-- ==================== KATEGORIEN ==================== -->
<section id="help-categories" class="mb-4">
    <h2 class="h5 fw-bold border-bottom pb-2">Kategorien</h2>
    <p>Kategorien stehen nur zur Verfügung, wenn die Option <em>Kategorien</em> in der <a href="#help-configuration-categories">Konfiguration</a> aktiviert ist.</p>
    <ul>
        <li><a href="#help-categories-add">Kategorie hinzufügen</a></li>
        <li><a href="#help-categories-edit">Kategorie editieren</a></li>
        <li><a href="#help-categories-deactivate">Kategorie deaktivieren statt löschen</a></li>
    </ul>

    <div id="help-categories-add" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Kategorie hinzufügen</h3>
        <p>Über <a href="index.php?page=categories&amp;subpage=add">Kategorien &gt; Kategorie anlegen</a> trägst Du Name und Beschreibung ein. Ist <em>Kategorie-Bilder</em> aktiv, kannst Du zusätzlich eine Grafik (z.&nbsp;B. ein PNG/GIF) hochladen, die in den Templates über den Platzhalter <code>{CATPIC}</code> erscheinen kann.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Zurück nach oben</a></p>
    </div>

    <div id="help-categories-edit" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Kategorie editieren</h3>
        <p>In der <a href="index.php?page=categories&amp;subpage=show">Kategorie-Liste</a> wählst Du den gewünschten Eintrag aus. Du kannst Name, Beschreibung und Status anpassen. Wenn ein neues Bild hochgeladen werden soll, aktiviere zuerst <em>Bild hochladen</em> und wähle anschließend die Datei aus.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Zurück nach oben</a></p>
    </div>

    <div id="help-categories-deactivate" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Kategorie deaktivieren statt löschen</h3>
        <p>Ein Hartlöschen ist absichtlich nicht möglich, weil daran zugeordnete News verwaist würden. Setze stattdessen den Status auf <em>Deaktiviert</em>: Die Kategorie verschwindet aus den Auswahlfeldern und im Frontend, bleibende News behalten ihre Zuordnung.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Zurück nach oben</a></p>
    </div>
</section>

<!-- ==================== BENUTZER ==================== -->
<section id="help-users" class="mb-4">
    <h2 class="h5 fw-bold border-bottom pb-2">Benutzer</h2>
    <ul>
        <li><a href="#help-users-add">Benutzer hinzufügen</a></li>
        <li><a href="#help-users-show">Benutzerliste</a></li>
        <li><a href="#help-users-search">Benutzer suchen</a></li>
        <li><a href="#help-users-edit">Benutzer editieren / deaktivieren</a></li>
    </ul>

    <div id="help-users-add" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Benutzer hinzufügen</h3>
        <p>Im Regelfall registrieren sich Benutzer selbst über <code>user.php</code> im Frontend. Über <a href="index.php?page=users&amp;subpage=add">Benutzer &gt; Benutzer anlegen</a> kannst Du dennoch manuell ein Konto erstellen.</p>
        <p>Beim Anlegen wird ein zufälliges Passwort generiert. Lass die Option <em>E-Mail mit Zugangsdaten senden</em> aktiv, damit der neue Benutzer seine Zugangsdaten per Mail erhält.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Zurück nach oben</a></p>
    </div>

    <div id="help-users-show" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Benutzerliste</h3>
        <p><a href="index.php?page=users&amp;subpage=show">Benutzer &gt; Benutzer anzeigen</a> liefert eine paginierte Liste aller Konten. Die Spalte <strong>Admin</strong> zeigt mit einem Badge an, ob Berechtigungen vergeben sind, die Spalte <strong>Status</strong>, ob das Konto aktiv ist.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Zurück nach oben</a></p>
    </div>

    <div id="help-users-search" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Benutzer suchen</h3>
        <p>Unter <a href="index.php?page=users&amp;subpage=search">Benutzer &gt; Benutzer suchen</a> filterst Du nach Nickname, E-Mail oder ID. Klick im Ergebnis auf den Nickname öffnet die Bearbeitung.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Zurück nach oben</a></p>
    </div>

    <div id="help-users-edit" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Benutzer editieren / deaktivieren</h3>
        <p>Klick in der Liste auf den Nickname, um den Benutzer zu öffnen. Bearbeitbar sind Nickname, E-Mail und Status. Mit <em>Neues Passwort generieren</em> wird ein zufälliges Passwort erzeugt – setzt Du gleichzeitig <em>E-Mail senden</em>, bekommt der Benutzer es per Mail zugeschickt.</p>
        <p>Ein direktes Hartlöschen ist nicht möglich. Setze den Status auf <em>Deaktiviert</em>: Der Benutzer kann sich nicht mehr einloggen, keine Kommentare schreiben und keine News einsenden. Deaktivierte Konten können auch keine Adminrechte erhalten.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Zurück nach oben</a></p>
    </div>
</section>

<!-- ==================== BERECHTIGUNGEN ==================== -->
<section id="help-permissions" class="mb-4">
    <h2 class="h5 fw-bold border-bottom pb-2">Berechtigungen</h2>
    <p>Berechtigungen entscheiden, welche Module ein Benutzer im Adminbereich lesen oder schreiben darf. Es gibt sieben Bereiche – Templates, Konfiguration, Benutzer, Berechtigungen, Kategorien, News, Kommentare – jeweils mit getrenntem Lese- und Schreibrecht.</p>
    <ul>
        <li><a href="#help-permissions-add">Berechtigungen hinzufügen</a></li>
        <li><a href="#help-permissions-show">Berechtigungen anzeigen</a></li>
        <li><a href="#help-permissions-edit">Berechtigungen editieren / löschen</a></li>
    </ul>

    <div id="help-permissions-add" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Berechtigungen hinzufügen</h3>
        <p>Über <a href="index.php?page=permissions&amp;subpage=add">Berechtigungen &gt; Berechtigung anlegen</a> wählst Du den Nickname eines bestehenden Benutzers und setzt seine Lese-/Schreibrechte. Standardmäßig sind <em>News</em> und <em>Kommentare</em> bereits angehakt. Der Benutzer wird nicht automatisch über seine neuen Rechte informiert – kommuniziere es bitte selbst.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Zurück nach oben</a></p>
    </div>

    <div id="help-permissions-show" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Berechtigungen anzeigen</h3>
        <p>Die <a href="index.php?page=permissions&amp;subpage=show">Berechtigungs-Liste</a> zeigt alle Admin-Konten mit einer Übersichts-Matrix der Lese- und Schreibrechte. Ein <span class="badge text-bg-success">&check;</span> bedeutet erlaubt, ein <span class="badge text-bg-secondary">&minus;</span> nicht erlaubt.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Zurück nach oben</a></p>
    </div>

    <div id="help-permissions-edit" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Berechtigungen editieren / löschen</h3>
        <p>Klick in der Liste auf den Nickname, um die Rechte anzupassen. Über die rot umrandete Box <strong>Berechtigungen löschen</strong> kannst Du dem Benutzer sämtliche Adminrechte wieder entziehen – das eigentliche Benutzerkonto bleibt davon unberührt.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Zurück nach oben</a></p>
    </div>
</section>

<!-- ==================== TEMPLATES ==================== -->
<section id="help-templates" class="mb-4">
    <h2 class="h5 fw-bold border-bottom pb-2">Templates</h2>
    <p>Templates steuern das Aussehen der HTML-Ausgabe (Headlines, News-Box, Kommentare, Formulare etc.). Das Template <strong>Default</strong> (id&nbsp;1) ist mit dem Bootstrap-5-Layout gefüllt und kann nicht gelöscht werden.</p>
    <ul>
        <li><a href="#help-templates-add">Template hinzufügen</a></li>
        <li><a href="#help-templates-show">Templates auflisten</a></li>
        <li><a href="#help-templates-edit">Template editieren / löschen</a></li>
        <li><a href="#help-templates-default">Default-Template</a></li>
    </ul>

    <div id="help-templates-add" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Template hinzufügen</h3>
        <p>Unter <a href="index.php?page=templates&amp;subpage=add">Templates &gt; Template anlegen</a> gibst Du einen Titel an. Das Default-Template wird automatisch als Vorlage kopiert, sodass Du eine vollständige, funktionierende Basis hast.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Zurück nach oben</a></p>
    </div>

    <div id="help-templates-show" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Templates auflisten</h3>
        <p>Unter <a href="index.php?page=templates&amp;subpage=show">Templates &gt; Templates anzeigen</a> siehst Du alle vorhandenen Templates. Klick auf den Namen öffnet die Bearbeitung.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Zurück nach oben</a></p>
    </div>

    <div id="help-templates-edit" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Template editieren / löschen</h3>
        <p>Im Editor sind die einzelnen Bausteine in drei Bereiche gruppiert:</p>
        <ul>
            <li><strong>Ausgabe</strong> &ndash; Nachrichten-Box, Headline, News, Kommentar, Usermenü, Related Links.</li>
            <li><strong>Eingabe</strong> &ndash; Formulare für Kommentar, Registrierung, Login, Logout, Daten-Senden, Profil, Archiv und News-Einsenden.</li>
            <li><strong>E-Mails</strong> &ndash; Texte für Anlegen-/Editieren-/Registrieren-/Daten-senden-Mails.</li>
        </ul>
        <p>Innerhalb der Felder werden Platzhalter wie <code>{ID}</code>, <code>{TITLE}</code>, <code>{DATE}</code>, <code>{TIME}</code>, <code>{AUTHOR}</code>, <code>{TEXT}</code>, <code>{COMMENTS}</code>, <code>{NICKNAME}</code>, <code>{EMAIL}</code>, <code>{URL}</code>, <code>{PASSWORD}</code> und <code>{CSRF}</code> ersetzt. Welche Platzhalter wo erlaubt sind, ist direkt in der Beschreibung jedes Felds aufgeführt.</p>
        <p>Die rot umrandete Box <strong>Template löschen</strong> entfernt das Template komplett. Achte vorher in der <a href="#help-configuration-template">Konfiguration</a> darauf, dass es nicht als aktives Template ausgewählt ist.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Zurück nach oben</a></p>
    </div>

    <div id="help-templates-default" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Default-Template</h3>
        <p>Das <a href="index.php?page=templates&amp;subpage=edit&amp;templateid=1">Default-Template</a> kann weder editiert noch gelöscht werden. Es bildet die Grundlage für alle eigenen Templates und stellt sicher, dass die Anwendung beim Anlegen eines neuen Templates immer eine funktionsfähige Vorlage hat.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Zurück nach oben</a></p>
    </div>
</section>

<!-- ==================== KONFIGURATION ==================== -->
<section id="help-configuration" class="mb-4">
    <h2 class="h5 fw-bold border-bottom pb-2">Konfiguration</h2>
    <p>Die <a href="index.php?page=configuration">Konfiguration</a> enthält die globalen Einstellungen. Änderungen wirken sich sofort auf den Frontend-Bereich aus.</p>
    <ul>
        <li><a href="#help-configuration-categories">Kategorien</a></li>
        <li><a href="#help-configuration-catpics">Kategorie-Bilder</a></li>
        <li><a href="#help-configuration-comments">Kommentare</a></li>
        <li><a href="#help-configuration-writecomments">Wer darf Kommentare schreiben?</a></li>
        <li><a href="#help-configuration-moretext">Textaufteilung (kurz / lang)</a></li>
        <li><a href="#help-configuration-sendnews">News einsenden erlauben</a></li>
        <li><a href="#help-configuration-newssending">Wer darf News einsenden?</a></li>
        <li><a href="#help-configuration-smilies">Smilies</a></li>
        <li><a href="#help-configuration-bbcode">BB-Code</a></li>
        <li><a href="#help-configuration-html">HTML</a></li>
        <li><a href="#help-configuration-dateformat">Datums- &amp; Zeitformat</a></li>
        <li><a href="#help-configuration-template">Aktives Template</a></li>
        <li><a href="#help-configuration-url">URL</a></li>
        <li><a href="#help-configuration-email">Versand-E-Mail</a></li>
        <li><a href="#help-configuration-headlines">Anzahl Headlines</a></li>
        <li><a href="#help-configuration-news">Anzahl News auf der Startseite</a></li>
        <li><a href="#help-configuration-spamprotection">Spamschutz</a></li>
        <li><a href="#help-configuration-relatedlinks">Related Links</a></li>
        <li><a href="#help-configuration-relatedlinks-num">Anzahl Related Links</a></li>
    </ul>

    <div id="help-configuration-categories" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Kategorien</h3>
        <p>Aktiviert oder deaktiviert die Kategorien-Funktion. Bei Aktivierung wird beim News-Schreiben eine Kategorie verlangt; ohne Kategorien werden News einer Pseudo-Kategorie zugeordnet.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Zurück nach oben</a></p>
    </div>

    <div id="help-configuration-catpics" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Kategorie-Bilder</h3>
        <p>Aktiviert die Möglichkeit, Kategorien Bilder zuzuweisen, die in Templates über <code>{CATPIC}</code> ausgegeben werden. Die Option erscheint nur, wenn <em>Kategorien</em> aktiv sind.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Zurück nach oben</a></p>
    </div>

    <div id="help-configuration-comments" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Kommentare</h3>
        <p>Schaltet das gesamte Kommentar-System global ein oder aus.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Zurück nach oben</a></p>
    </div>

    <div id="help-configuration-writecomments" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Wer darf Kommentare schreiben?</h3>
        <p>Sind Kommentare aktiviert, kannst Du zwischen <em>Gäste &amp; Registrierte</em> und <em>Nur Registrierte</em> wählen.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Zurück nach oben</a></p>
    </div>

    <div id="help-configuration-moretext" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Textaufteilung (kurz / lang)</h3>
        <p>Erlaubt einen kurzen Text auf der Startseite und einen ergänzenden langen Text auf der Detailseite. Bei deaktivierter Option wird nur der Haupttext genutzt.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Zurück nach oben</a></p>
    </div>

    <div id="help-configuration-sendnews" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">News einsenden erlauben</h3>
        <p>Schaltet das Frontend-Formular <code>sendnews.php</code> generell ein oder aus.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Zurück nach oben</a></p>
    </div>

    <div id="help-configuration-newssending" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Wer darf News einsenden?</h3>
        <p>Wahl zwischen <em>Gäste &amp; Registrierte</em> und <em>Nur Registrierte</em>. Eingesendete News landen mit Status <em>Ungeprüft</em> in der Liste und müssen manuell freigegeben werden.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Zurück nach oben</a></p>
    </div>

    <div id="help-configuration-smilies" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Smilies</h3>
        <p>Wählt, in welchen Bereichen Smilie-Codes (siehe <a href="#help-other-smilies">Sonstiges &gt; Smilies</a>) ersetzt werden: <em>Aus</em>, <em>Nur Kommentare</em>, <em>Nur News</em> oder <em>Beide</em>.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Zurück nach oben</a></p>
    </div>

    <div id="help-configuration-bbcode" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">BB-Code</h3>
        <p>Analog zu Smilies: bestimmt, wo BB-Codes wie <code>[b]</code>, <code>[i]</code>, <code>[url]</code> ausgewertet werden. Die unterstützten Codes findest Du unter <a href="#help-other-bbcode">Sonstiges &gt; BB-Code</a>.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Zurück nach oben</a></p>
    </div>

    <div id="help-configuration-html" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">HTML</h3>
        <p>Diese Option würde rohes HTML in News bzw. Kommentaren zulassen. Aus Sicherheitsgründen werden Eingaben aber grundsätzlich escapt – die Einstellung steht aus Kompatibilitätsgründen weiterhin im Formular, hat aber keine Wirkung. Verwende stattdessen BB-Code, um Formatierung zuzulassen.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Zurück nach oben</a></p>
    </div>

    <div id="help-configuration-dateformat" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Datums- &amp; Zeitformat</h3>
        <p>Format-Strings für die Datums- bzw. Zeitausgabe. Unterstützt sind sowohl PHP-<code>date()</code>-Tokens (z.&nbsp;B. <code>d.m.Y</code> / <code>H:i</code>) als auch klassische strftime-Tokens (<code>%d.%m.%Y</code> / <code>%H:%M</code>) – Letztere werden intern automatisch konvertiert.</p>
        <table class="table table-sm align-middle mt-2">
            <thead>
                <tr><th>Token</th><th>Bedeutung</th><th>Beispiel</th></tr>
            </thead>
            <tbody>
                <tr><td><code>d</code> / <code>%d</code></td><td>Tag (01–31)</td><td>10</td></tr>
                <tr><td><code>m</code> / <code>%m</code></td><td>Monat (01–12)</td><td>05</td></tr>
                <tr><td><code>Y</code> / <code>%Y</code></td><td>Jahr 4-stellig</td><td>2026</td></tr>
                <tr><td><code>H</code> / <code>%H</code></td><td>Stunde (00–23)</td><td>17</td></tr>
                <tr><td><code>i</code> / <code>%M</code></td><td>Minuten (00–59)</td><td>30</td></tr>
            </tbody>
        </table>
        <p class="text-end mb-0"><a href="#pn-help-top">Zurück nach oben</a></p>
    </div>

    <div id="help-configuration-template" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Aktives Template</h3>
        <p>Wählt das Template, das im Frontend ausgespielt wird. Eigene Templates legst Du unter <a href="#help-templates-add">Templates &gt; Template anlegen</a> an.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Zurück nach oben</a></p>
    </div>

    <div id="help-configuration-url" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">URL</h3>
        <p>Volle URL zum PowerNews-Verzeichnis (ohne abschließenden Slash). Wird in den E-Mails referenziert (z.&nbsp;B. <em>http://www.example.tld/news</em>, wenn der Adminbereich unter <em>http://www.example.tld/news/pnadmin</em> liegt).</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Zurück nach oben</a></p>
    </div>

    <div id="help-configuration-email" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Versand-E-Mail</h3>
        <p>Absender-Adresse für alle automatisch versendeten Mails (Registrierung, Profilbearbeitung, Daten-senden). Eine korrekte, zustellbare Adresse ist wichtig, damit Mails nicht im Spam landen.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Zurück nach oben</a></p>
    </div>

    <div id="help-configuration-headlines" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Anzahl Headlines</h3>
        <p>Wieviele Headlines werden auf der Startseite angezeigt? Nur ganzzahlige Werte (1–99 sinnvoll).</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Zurück nach oben</a></p>
    </div>

    <div id="help-configuration-news" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Anzahl News auf der Startseite</h3>
        <p>Wieviele News-Einträge werden auf der Startseite angezeigt? Ältere Einträge sind weiter über das Archiv erreichbar.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Zurück nach oben</a></p>
    </div>

    <div id="help-configuration-spamprotection" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Spamschutz</h3>
        <p>Mindestabstand in Sekunden zwischen zwei Kommentaren von derselben IP-Adresse. Werte zwischen 1 und 999 sind möglich.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Zurück nach oben</a></p>
    </div>

    <div id="help-configuration-relatedlinks" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Related Links</h3>
        <p>Aktiviert die Eingabe von Verweis-Listen pro News. Die Position im Layout legst Du im Template über <code>{RELATEDLINKS}</code> fest.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Zurück nach oben</a></p>
    </div>

    <div id="help-configuration-relatedlinks-num" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Anzahl Related Links</h3>
        <p>Wieviele Eingabezeilen für Related Links zeigt das News-Formular an. Maximum 99.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Zurück nach oben</a></p>
    </div>
</section>

<!-- ==================== EIGENES PROFIL ==================== -->
<section id="help-profile" class="mb-4">
    <h2 class="h5 fw-bold border-bottom pb-2">Eigenes Profil</h2>
    <p>Über die Statusleiste rechts oben (<em>Profil bearbeiten</em>) oder direkt unter <a href="index.php?page=profile">Profil</a> kannst Du Deinen eigenen Account bearbeiten.</p>
    <p>Pflichtfelder sind <strong>Nickname</strong> und <strong>E-Mail</strong>. Mit der Option <em>E-Mail im Profil anzeigen</em> bestimmst Du, ob Deine Mail im Frontend (z.&nbsp;B. als Autor einer News) sichtbar ist. Die beiden Passwort-Felder lass leer, wenn Du das Passwort nicht ändern möchtest.</p>
    <p class="text-end mb-0"><a href="#pn-help-top">Zurück nach oben</a></p>
</section>

<!-- ==================== SONSTIGES ==================== -->
<section id="help-other" class="mb-4">
    <h2 class="h5 fw-bold border-bottom pb-2">Sonstiges</h2>
    <ul>
        <li><a href="#help-other-bbcode">BB-Code-Referenz</a></li>
        <li><a href="#help-other-smilies">Smilies-Übersicht</a></li>
        <li><a href="#help-other-license">Lizenz</a></li>
        <li><a href="#help-other-external">Externe Seite</a></li>
        <li><a href="#help-other-about">Über PowerNews</a></li>
    </ul>

    <div id="help-other-bbcode" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">BB-Code-Referenz</h3>
        <p>Die folgenden BB-Codes können in News bzw. Kommentaren verwendet werden, sofern sie in der <a href="#help-configuration-bbcode">Konfiguration</a> aktiviert sind.</p>
        <div class="table-responsive">
            <table class="table table-sm align-middle">
                <thead>
                    <tr><th>Eingabe</th><th>Ausgabe</th></tr>
                </thead>
                <tbody>
                    <tr><td><code>[b]PowerNews[/b]</code></td><td><strong>PowerNews</strong></td></tr>
                    <tr><td><code>[u]PowerNews[/u]</code></td><td><u>PowerNews</u></td></tr>
                    <tr><td><code>[i]PowerNews[/i]</code></td><td><em>PowerNews</em></td></tr>
                    <tr><td><code>[url]http://example.org[/url]</code></td><td><a href="https://example.org" target="_blank" rel="noopener noreferrer">http://example.org</a></td></tr>
                    <tr><td><code>[email]info@example.org[/email]</code></td><td><a href="mailto:info@example.org">info@example.org</a></td></tr>
                    <tr><td><code>[img]http://example.org/x.png[/img]</code></td><td>(Bild aus erlaubter Domain – sonst bleibt der Code stehen)</td></tr>
                </tbody>
            </table>
        </div>
        <p class="form-text">Der <code>[img]</code>-Tag akzeptiert aus Sicherheitsgründen nur Bilder von der eigenen Domain (Whitelist).</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Zurück nach oben</a></p>
    </div>

    <div id="help-other-smilies" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Smilies-Übersicht</h3>
        <p>Folgende Smilie-Codes werden ersetzt, sofern Smilies in der <a href="#help-configuration-smilies">Konfiguration</a> aktiviert sind:</p>
        <div class="table-responsive">
            <table class="table table-sm align-middle">
                <thead>
                    <tr><th>Eingabe</th><th>Ausgabe</th></tr>
                </thead>
                <tbody>
                    <tr><td><code>:)</code></td><td><img src="../pngfx/smilies/smile.gif" width="15" height="15" alt="lächeln"></td></tr>
                    <tr><td><code>;)</code></td><td><img src="../pngfx/smilies/wink.gif" width="15" height="15" alt="zwinkern"></td></tr>
                    <tr><td><code>:))</code></td><td><img src="../pngfx/smilies/laugh.gif" width="15" height="15" alt="lachen"></td></tr>
                    <tr><td><code>:D</code></td><td><img src="../pngfx/smilies/bigsmile.gif" width="15" height="15" alt="breit lachen"></td></tr>
                    <tr><td><code>:P</code></td><td><img src="../pngfx/smilies/tongue.gif" width="15" height="15" alt="Zunge"></td></tr>
                    <tr><td><code>:(</code></td><td><img src="../pngfx/smilies/sad.gif" width="15" height="15" alt="traurig"></td></tr>
                    <tr><td><code>:?:</code></td><td><img src="../pngfx/smilies/confused.gif" width="15" height="22" alt="verwirrt"></td></tr>
                </tbody>
            </table>
        </div>
        <p class="text-end mb-0"><a href="#pn-help-top">Zurück nach oben</a></p>
    </div>

    <div id="help-other-license" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Lizenz</h3>
        <p>Den vollständigen GPL-Lizenztext findest Du unter <a href="index.php?page=other&amp;subpage=license">Sonstiges &gt; Lizenz</a>.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Zurück nach oben</a></p>
    </div>

    <div id="help-other-external" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Externe Seite</h3>
        <p>Der Button <em>Externe Seite</em> oben rechts in der Navbar führt direkt zur Frontend-Startseite – nützlich, um nach einer Änderung schnell die Außensicht zu prüfen.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Zurück nach oben</a></p>
    </div>

    <div id="help-other-about" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Über PowerNews</h3>
        <p>PowerNews ist ein PHP-/MySQL-basiertes News-Skript der Entwicklungsgruppe <a href="https://www.powerscripts.org" target="_blank" rel="noopener noreferrer">PowerScripts</a>, ursprünglich von Stefan Krämer geschrieben. Die aktuelle 3.x-Version nutzt Bootstrap 5 für ein modernes, responsives Layout und enthält ein vollständiges Benutzer- und Berechtigungssystem.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Zurück nach oben</a></p>
    </div>
</section>
