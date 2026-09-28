<?php

/* PowerNews - PHP and MySQL based news script                          */
/* Copyright (c) 2001-2026 PowerScripts                                 */

/* MIT License - See LICENSE file for full license text                 */
/* https://github.com/schubertnico/PowerNews.git                        */

/* German help file (Sie-Form) written by PowerScripts (Stefan Kraemer) */
/* Stand PowerNews 3.12; german-du_help.php und english_help.php haben denselben Aufbau. */
?>
<div id="pn-help-top"></div>

<p class="lead">Diese Hilfe beschreibt den Adminbereich von PowerNews. Klicken Sie auf einen Bereich, um direkt zur Erklärung zu springen.</p>

<nav class="card mb-4">
    <div class="card-body">
        <h2 class="h6 fw-bold mb-2">Inhalt</h2>
        <ul class="mb-0">
            <li><a href="#help-start">Startseite</a></li>
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

<!-- ==================== STARTSEITE ==================== -->
<section id="help-start" class="mb-4">
    <h2 class="h5 fw-bold border-bottom pb-2">Startseite</h2>
    <p>Die Startseite zeigt, was Aufmerksamkeit braucht: <strong>Zu prüfen</strong> nennt die Zahl der von Besuchern eingesendeten News mit Status <em>Ungeprüft</em> und führt mit <em>Einsendungen prüfen</em> direkt zur gefilterten News-Liste. <strong>Kommentare</strong> zählt die neuen Kommentare der letzten 7 Tage und verlinkt die fünf neuesten.</p>
    <p>Navigation, Schnellzugriff und Startseite zeigen nur, wofür Ihr Konto das Recht hat. Ohne Leserecht für einen Bereich erscheint er nicht im Menü; ein direkter Aufruf endet mit „Zugriff verweigert“.</p>
    <p class="text-end mb-0"><a href="#pn-help-top">Zurück nach oben</a></p>
</section>

<!-- ==================== NEWS ==================== -->
<section id="help-news" class="mb-4">
    <h2 class="h5 fw-bold border-bottom pb-2">News</h2>
    <ul>
        <li><a href="#help-news-add">News schreiben</a></li>
        <li><a href="#help-news-show">News anzeigen und filtern</a></li>
        <li><a href="#help-news-edit">News editieren, löschen, Kommentare moderieren</a></li>
        <li><a href="#help-news-search">News suchen</a></li>
        <li><a href="#help-news-status">Status-Erklärung</a></li>
    </ul>

    <div id="help-news-add" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">News schreiben</h3>
        <p>Über <a href="index.php?page=news&amp;subpage=add">News &gt; News schreiben</a> legen Sie einen neuen Eintrag an. Pflichtfelder sind <strong>Titel</strong> und <strong>Text</strong>; ist die Kategorie-Funktion aktiv, ist zusätzlich eine <strong>Kategorie</strong> nötig. Zur Auswahl stehen nur aktive Kategorien.</p>
        <p>Den Erscheinungstermin wählen Sie mit Tag, Monat, Jahr, Stunde und Minute. Liegt er in der Zukunft, erscheint die News erst dann auf der Startseite und im Archiv.</p>
        <p>Ist <em>Textaufteilung</em> in der Konfiguration aktiviert, können Sie zusätzlich einen <strong>langen Text</strong> hinterlegen, der erst auf der Detailseite erscheint. Sind <em>weiterführende Links</em> aktiv, tragen Sie je Link Titel, Adresse (http://, https:// oder ein relativer Pfad) und Ziel ein: <em>Neues Fenster</em> oder <em>Gleiches Fenster</em>.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Zurück nach oben</a></p>
    </div>

    <div id="help-news-show" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">News anzeigen und filtern</h3>
        <p>Unter <a href="index.php?page=news&amp;subpage=show">News &gt; News anzeigen</a> finden Sie alle Beiträge, die neuesten zuerst, 25 pro Seite. Über der Liste filtern Sie nach Status: <em>Alle</em>, <em>Ungeprüft</em> (mit Anzahl), <em>Aktiviert</em> oder <em>Deaktiviert</em>. Ein Klick auf den Titel öffnet die Bearbeitung.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Zurück nach oben</a></p>
    </div>

    <div id="help-news-edit" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">News editieren, löschen, Kommentare moderieren</h3>
        <p>Wählen Sie eine News in der <a href="index.php?page=news&amp;subpage=show">News-Liste</a> oder im <a href="index.php?page=news&amp;subpage=search">Suchergebnis</a> aus. Sie können alle Felder, die Kategorie, den Erscheinungstermin und den Status ändern. Steht die News in einer inzwischen deaktivierten Kategorie, bleibt diese Kategorie vorgewählt und ist mit „(deaktiviert)“ gekennzeichnet; die News wandert beim Speichern nicht in eine andere Kategorie.</p>
        <p>Die rot umrandete Box <strong>Löschen</strong> entfernt den Eintrag samt Kommentaren <strong>endgültig</strong>. Wollen Sie eine News nur ausblenden, setzen Sie den Status auf <em>Deaktiviert</em>.</p>
        <p>Sind Kommentare aktiviert, stehen sie unter dem Formular in zeitlicher Reihenfolge, der älteste zuerst. Mit dem Recht <em>Kommentare schreiben</em> können Sie Texte überarbeiten und einzelne Kommentare über die rot markierte Checkbox löschen; gespeichert werden nur Kommentare, die Sie tatsächlich geändert haben.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Zurück nach oben</a></p>
    </div>

    <div id="help-news-search" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">News suchen</h3>
        <p>Unter <a href="index.php?page=news&amp;subpage=search">News &gt; News suchen</a> suchen Sie in Titel, Text, ID oder – falls aktiviert – im langen Text. Das Ergebnis erscheint als Tabelle wie unter <a href="#help-news-show">News anzeigen</a>.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Zurück nach oben</a></p>
    </div>

    <div id="help-news-status" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Status-Erklärung</h3>
        <ul>
            <li><span class="badge text-bg-success">Aktiviert</span> &ndash; Die News ist freigegeben, im Frontend sichtbar und kommentierbar (sobald der Erscheinungstermin erreicht ist).</li>
            <li><span class="badge text-bg-warning">Ungeprüft</span> &ndash; Ein Besucher hat die News eingesendet, sie wartet auf Ihre Freigabe und erscheint im Frontend nicht.</li>
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
        <p>Über <a href="index.php?page=categories&amp;subpage=add">Kategorien &gt; Kategorie hinzufügen</a> tragen Sie Titel und Beschreibung (höchstens 255 Zeichen) ein. Ist <em>Kategorie-Bilder</em> aktiv, können Sie zusätzlich ein Bild (GIF, JPG oder PNG, höchstens 2&nbsp;MB) hochladen, das im Template über <code>{CATPIC}</code> erscheint.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Zurück nach oben</a></p>
    </div>

    <div id="help-categories-edit" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Kategorie editieren</h3>
        <p>In der <a href="index.php?page=categories&amp;subpage=show">Kategorie-Liste</a> wählen Sie den gewünschten Eintrag aus und ändern Titel, Beschreibung und Status. Für ein neues Bild aktivieren Sie zuerst <em>Bild hochladen</em> und wählen dann die Datei aus.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Zurück nach oben</a></p>
    </div>

    <div id="help-categories-deactivate" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Kategorie deaktivieren statt löschen</h3>
        <p>Kategorien lassen sich nicht löschen, weil sonst die zugeordneten News verwaisen würden. Setzen Sie stattdessen den Status der Kategorie auf <em>Deaktiviert</em>: Sie verschwindet aus der Auswahl für neue News und aus dem Einsendeformular. Bestehende News behalten ihre Kategorie, auch wenn Sie sie bearbeiten.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Zurück nach oben</a></p>
    </div>
</section>

<!-- ==================== BENUTZER ==================== -->
<section id="help-users" class="mb-4">
    <h2 class="h5 fw-bold border-bottom pb-2">Benutzer</h2>
    <ul>
        <li><a href="#help-users-add">Benutzer hinzufügen (Einladung)</a></li>
        <li><a href="#help-users-show">Benutzerliste</a></li>
        <li><a href="#help-users-search">Benutzer suchen</a></li>
        <li><a href="#help-users-edit">Benutzer editieren / deaktivieren</a></li>
    </ul>

    <div id="help-users-add" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Benutzer hinzufügen (Einladung)</h3>
        <p>Im Regelfall registrieren sich Benutzer selbst über <code>user.php</code> und wählen dabei ihr Passwort. Über <a href="index.php?page=users&amp;subpage=add">Benutzer &gt; Benutzer hinzufügen</a> legen Sie ein Konto trotzdem selbst an, etwa für eine neue Redakteurin.</p>
        <p>PowerNews verschickt dabei <strong>kein Passwort</strong>. Das neue Konto bekommt eine Einladung mit einem Einmal-Link, über den der Benutzer sein Passwort selbst festlegt; der Link gilt 48 Stunden. Bis dahin ist keine Anmeldung möglich. Mit <em>Einladung per E-Mail senden</em> geht der Link per Mail raus; ohne Häkchen zeigt PowerNews ihn Ihnen nach dem Speichern einmal an, damit Sie ihn selbst weitergeben. Ist der Link abgelaufen, hilft „Passwort vergessen“ auf der Website oder erneut <em>Link für ein neues Passwort senden</em> beim Bearbeiten.</p>
        <p>Admin-Rechte vergeben Sie danach unter <a href="#help-permissions">Berechtigungen</a>.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Zurück nach oben</a></p>
    </div>

    <div id="help-users-show" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Benutzerliste</h3>
        <p><a href="index.php?page=users&amp;subpage=show">Benutzer &gt; Benutzer anzeigen</a> listet alle Konten, 25 pro Seite. Die Spalte <strong>Mit E-Mail verlinkt</strong> zeigt, ob der Name des Benutzers unter News und Kommentaren zum Mail-Link wird, <strong>Admin</strong>, ob Berechtigungen vergeben sind, und <strong>Status</strong>, ob das Konto aktiv ist.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Zurück nach oben</a></p>
    </div>

    <div id="help-users-search" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Benutzer suchen</h3>
        <p>Unter <a href="index.php?page=users&amp;subpage=search">Benutzer &gt; Benutzer suchen</a> suchen Sie nach Nickname, E-Mail-Adresse oder ID. Ein Klick auf den Nickname im Ergebnis öffnet die Bearbeitung.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Zurück nach oben</a></p>
    </div>

    <div id="help-users-edit" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Benutzer editieren / deaktivieren</h3>
        <p>Klicken Sie in der Liste auf den Nickname. Bearbeitbar sind Nickname, E-Mail-Adresse, die Verlinkung des Namens mit der E-Mail-Adresse und der Status. <em>Link für ein neues Passwort senden</em> schickt dem Benutzer einen Einmal-Link, über den er sein Passwort selbst neu festlegt; bis dahin gilt das bisherige. Hat das Konto noch kein Passwort, geht stattdessen die Einladung erneut raus. Mit <em>E-Mail senden</em> bekommt der Benutzer die geänderten Daten (ohne Passwort) per Mail.</p>
        <p>Löschen lässt sich ein Konto nicht. Setzen Sie den Status auf <em>Deaktiviert</em>: Der Benutzer kann sich nicht mehr einloggen, keine Kommentare schreiben und keine News einsenden; laufende Anmeldungen enden sofort. Deaktivierte Konten können keine Adminrechte erhalten.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Zurück nach oben</a></p>
    </div>
</section>

<!-- ==================== BERECHTIGUNGEN ==================== -->
<section id="help-permissions" class="mb-4">
    <h2 class="h5 fw-bold border-bottom pb-2">Berechtigungen</h2>
    <p>Berechtigungen legen fest, welche Bereiche ein Benutzer im Adminbereich lesen oder schreiben darf. Es gibt sieben Bereiche – Templates, Konfiguration, Benutzer, Berechtigungen, Kategorien, News, Kommentare – jeweils mit getrenntem Lese- und Schreibrecht. Das Leserecht entscheidet, ob ein Bereich in der Navigation erscheint.</p>
    <ul>
        <li><a href="#help-permissions-add">Berechtigungen hinzufügen</a></li>
        <li><a href="#help-permissions-show">Berechtigungen anzeigen</a></li>
        <li><a href="#help-permissions-edit">Berechtigungen editieren / löschen</a></li>
    </ul>

    <div id="help-permissions-add" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Berechtigungen hinzufügen</h3>
        <p>Über <a href="index.php?page=permissions&amp;subpage=add">Berechtigungen &gt; Berechtigungen hinzufügen</a> geben Sie den Nickname eines bestehenden Benutzers an und setzen seine Lese- und Schreibrechte. Vorausgewählt sind <em>News</em> und <em>Kommentare</em>, mehr braucht eine Redakteurin nicht. <em>Berechtigungen schreiben</em> und <em>Templates schreiben</em> sind faktisch Admin-Rechte. Der Benutzer wird nicht automatisch über seine neuen Rechte informiert.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Zurück nach oben</a></p>
    </div>

    <div id="help-permissions-show" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Berechtigungen anzeigen</h3>
        <p>Die <a href="index.php?page=permissions&amp;subpage=show">Berechtigungs-Liste</a> zeigt alle Admin-Konten mit einer Übersicht der Lese- und Schreibrechte. <span class="badge text-bg-success">&check;</span> bedeutet erlaubt, <span class="badge text-bg-secondary">&minus;</span> nicht erlaubt.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Zurück nach oben</a></p>
    </div>

    <div id="help-permissions-edit" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Berechtigungen editieren / löschen</h3>
        <p>Klicken Sie in der Liste auf den Nickname, um die Rechte anzupassen. Die rot umrandete Box <strong>Löschen</strong> entzieht dem Benutzer sämtliche Adminrechte; das Benutzerkonto selbst bleibt bestehen.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Zurück nach oben</a></p>
    </div>
</section>

<!-- ==================== TEMPLATES ==================== -->
<section id="help-templates" class="mb-4">
    <h2 class="h5 fw-bold border-bottom pb-2">Templates</h2>
    <p>Templates steuern die HTML-Ausgabe (Schlagzeilen, News, Kommentare, Formulare) und die Texte der E-Mails.</p>
    <ul>
        <li><a href="#help-templates-add">Template hinzufügen</a></li>
        <li><a href="#help-templates-show">Templates anzeigen</a></li>
        <li><a href="#help-templates-edit">Template editieren / löschen</a></li>
        <li><a href="#help-templates-default">Default-Template</a></li>
    </ul>

    <div id="help-templates-add" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Template hinzufügen</h3>
        <p>Unter <a href="index.php?page=templates&amp;subpage=add">Templates &gt; Template hinzufügen</a> geben Sie einen Titel an. Als Inhalt übernimmt PowerNews das Default-Template, so haben Sie eine vollständige, funktionierende Grundlage.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Zurück nach oben</a></p>
    </div>

    <div id="help-templates-show" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Templates anzeigen</h3>
        <p>Unter <a href="index.php?page=templates&amp;subpage=show">Templates &gt; Templates anzeigen</a> sehen Sie alle vorhandenen Templates. Ein Klick auf den Namen öffnet die Bearbeitung.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Zurück nach oben</a></p>
    </div>

    <div id="help-templates-edit" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Template editieren / löschen</h3>
        <p>Im Editor sind die Bausteine in drei Bereiche gegliedert:</p>
        <ul>
            <li><strong>Ausgabe</strong> &ndash; Meldung, Schlagzeile, News, Kommentar, Benutzermenüs, weiterführender Link.</li>
            <li><strong>Formulare und Eingaben</strong> &ndash; Kommentar, Registrierung (mit den Passwortfeldern <code>pndata[password]</code> und <code>pndata[password2]</code>), Login, Logout, Passwort vergessen, Profil, Archiv und News einsenden.</li>
            <li><strong>E-Mails</strong> &ndash; Einladung, Datenänderung, Registrierung und Passwort vergessen.</li>
        </ul>
        <p>Platzhalter wie <code>{ID}</code>, <code>{TITLE}</code>, <code>{DATE}</code>, <code>{TIME}</code>, <code>{AUTHOR}</code>, <code>{TEXT}</code>, <code>{NICKNAME}</code>, <code>{EMAIL}</code> und <code>{CSRF}</code> werden ersetzt; welche wo gelten, steht unter jedem Feld. In den E-Mails stehen zusätzlich <code>{SITE}</code> (Name der Website), <code>{URL}</code>, <code>{LOGINLINK}</code>, <code>{INVITELINK}</code> mit <code>{VALIDHOURS}</code> (Einladung) und <code>{RESETLINK}</code> mit <code>{VALIDMINUTES}</code> (Passwort vergessen) zur Verfügung. Passwörter verschickt PowerNews nie; eine Zeile mit <code>{PASSWORD}</code> aus älteren Templates entfällt. Fehlt einer E-Mail der Link-Platzhalter, gilt der Standardtext aus der Sprachdatei.</p>
        <p>Die Texte des Default-Templates kommen ohne Anrede aus und passen deshalb zu jeder Sprache. Die rot umrandete Box <strong>Löschen</strong> entfernt ein Template komplett; achten Sie vorher darauf, dass es in der <a href="#help-configuration-template">Konfiguration</a> nicht aktiv ist.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Zurück nach oben</a></p>
    </div>

    <div id="help-templates-default" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Default-Template</h3>
        <p>Das <a href="index.php?page=templates&amp;subpage=edit&amp;templateid=1">Default-Template</a> (ID&nbsp;1) können Sie editieren, aber nicht löschen: Es dient als Vorlage für jedes neu angelegte Template. Änderungen daran wirken sich deshalb auch auf alle künftig angelegten Templates aus.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Zurück nach oben</a></p>
    </div>
</section>

<!-- ==================== KONFIGURATION ==================== -->
<section id="help-configuration" class="mb-4">
    <h2 class="h5 fw-bold border-bottom pb-2">Konfiguration</h2>
    <p>Die <a href="index.php?page=configuration">Konfiguration</a> enthält die globalen Einstellungen. Änderungen wirken sofort im Frontend.</p>
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
        <li><a href="#help-configuration-email">Absender-E-Mail</a></li>
        <li><a href="#help-configuration-headlines">Anzahl Schlagzeilen</a></li>
        <li><a href="#help-configuration-news">Anzahl News auf der Startseite</a></li>
        <li><a href="#help-configuration-spamprotection">Spamschutz</a></li>
        <li><a href="#help-configuration-relatedlinks">Weiterführende Links</a></li>
        <li><a href="#help-configuration-relatedlinks-num">Anzahl weiterführender Links</a></li>
    </ul>

    <div id="help-configuration-categories" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Kategorien</h3>
        <p>Schaltet die Kategorien ein oder aus. Sind sie aktiv, verlangt das Schreiben einer News eine Kategorie.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Zurück nach oben</a></p>
    </div>

    <div id="help-configuration-catpics" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Kategorie-Bilder</h3>
        <p>Erlaubt Bilder für Kategorien, die Templates über <code>{CATPIC}</code> ausgeben. Die Option erscheint nur, wenn <em>Kategorien</em> aktiv sind.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Zurück nach oben</a></p>
    </div>

    <div id="help-configuration-comments" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Kommentare</h3>
        <p>Schaltet die Kommentare global ein oder aus.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Zurück nach oben</a></p>
    </div>

    <div id="help-configuration-writecomments" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Wer darf Kommentare schreiben?</h3>
        <p>Sind Kommentare aktiviert, wählen Sie zwischen <em>Gäste &amp; Registrierte</em> und <em>Registrierte</em>.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Zurück nach oben</a></p>
    </div>

    <div id="help-configuration-moretext" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Textaufteilung (kurz / lang)</h3>
        <p>Erlaubt einen kurzen Text für die Startseite und einen langen Text, der zusätzlich auf der Detailseite erscheint.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Zurück nach oben</a></p>
    </div>

    <div id="help-configuration-sendnews" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">News einsenden erlauben</h3>
        <p>Schaltet das Formular <code>sendnews.php</code> im Frontend ein oder aus.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Zurück nach oben</a></p>
    </div>

    <div id="help-configuration-newssending" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Wer darf News einsenden?</h3>
        <p>Wahl zwischen <em>Gäste &amp; Registrierte</em> und <em>Registrierte</em>. Eingesendete News landen mit Status <em>Ungeprüft</em> in der Liste; die Startseite des Adminbereichs zeigt, wie viele auf Ihre Freigabe warten.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Zurück nach oben</a></p>
    </div>

    <div id="help-configuration-smilies" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Smilies</h3>
        <p>Legt fest, wo Smilie-Codes (siehe <a href="#help-other-smilies">Sonstiges &gt; Smilies</a>) als Bild erscheinen: <em>Nein</em>, <em>Kommentare</em>, <em>News</em> oder <em>Kommentare &amp; News</em>.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Zurück nach oben</a></p>
    </div>

    <div id="help-configuration-bbcode" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">BB-Code</h3>
        <p>Legt wie bei den Smilies fest, wo BB-Codes wie <code>[b]</code>, <code>[i]</code> und <code>[url]</code> ausgewertet werden. Die unterstützten Codes finden Sie unter <a href="#help-other-bbcode">Sonstiges &gt; BB-Code</a>.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Zurück nach oben</a></p>
    </div>

    <div id="help-configuration-html" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">HTML</h3>
        <p>HTML in News und Kommentaren wird aus Sicherheitsgründen nie ausgewertet, sondern als Text angezeigt. Die Einstellung bleibt aus Kompatibilitätsgründen gespeichert, erscheint aber nicht mehr im Formular. Für Formatierungen gibt es BB-Code.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Zurück nach oben</a></p>
    </div>

    <div id="help-configuration-dateformat" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Datums- &amp; Zeitformat</h3>
        <p>Beide Felder nehmen ein Format mit höchstens 50 Zeichen auf. PowerNews versteht zwei Schreibweisen:</p>
        <ul>
            <li><strong>date()-Schreibweise</strong> (empfohlen), z.&nbsp;B. <code>d.m.Y</code> und <code>H:i</code>. Buchstaben ohne Bedeutung stehen mit vorangestelltem Backslash im Text, etwa <code>H:i \U\h\r</code> für „17:30 Uhr“.</li>
            <li><strong>strftime-Schreibweise</strong> mit Prozentzeichen, z.&nbsp;B. <code>%d.%m.%Y</code> und <code>%H:%M</code> (Vorgabe bei Neuinstallationen). PowerNews rechnet sie intern in die date()-Schreibweise um.</li>
        </ul>
        <p>Wochentage und Monatsnamen erscheinen in der Sprache der Installation, bei deutscher Sprache also „Sonntag, 14. März 2021“.</p>
        <table class="table table-sm align-middle mt-2">
            <thead>
                <tr><th>date()</th><th>strftime</th><th>Bedeutung</th><th>Beispiel</th></tr>
            </thead>
            <tbody>
                <tr><td><code>d</code></td><td><code>%d</code></td><td>Tag, zweistellig</td><td>07</td></tr>
                <tr><td><code>j</code></td><td><code>%e</code></td><td>Tag ohne führende Null</td><td>7</td></tr>
                <tr><td><code>l</code> / <code>D</code></td><td><code>%A</code> / <code>%a</code></td><td>Wochentag / kurz</td><td>Sonntag / So</td></tr>
                <tr><td><code>m</code></td><td><code>%m</code></td><td>Monat, zweistellig</td><td>03</td></tr>
                <tr><td><code>F</code> / <code>M</code></td><td><code>%B</code> / <code>%b</code></td><td>Monatsname / kurz</td><td>März / Mär</td></tr>
                <tr><td><code>Y</code></td><td><code>%Y</code></td><td>Jahr, vierstellig</td><td>2026</td></tr>
                <tr><td><code>H</code></td><td><code>%H</code></td><td>Stunde (00–23)</td><td>17</td></tr>
                <tr><td><code>i</code></td><td><code>%M</code></td><td>Minute (00–59)</td><td>30</td></tr>
            </tbody>
        </table>
        <p class="mb-0">Alle Zeichen der date()-Schreibweise nennt das <a href="https://www.php.net/manual/de/datetime.format.php" rel="noopener noreferrer">PHP-Handbuch</a>.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Zurück nach oben</a></p>
    </div>

    <div id="help-configuration-template" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Aktives Template</h3>
        <p>Wählt das Template, das im Frontend gilt. Eigene Templates legen Sie unter <a href="#help-templates-add">Templates &gt; Template hinzufügen</a> an.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Zurück nach oben</a></p>
    </div>

    <div id="help-configuration-url" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">URL</h3>
        <p>Vollständige Adresse des PowerNews-Verzeichnisses ohne abschließenden Schrägstrich, z.&nbsp;B. <em>https://www.example.org/news</em>, wenn der Adminbereich unter <em>https://www.example.org/news/pnadmin</em> liegt. Aus ihr entstehen die Links in allen E-Mails (Anmeldung, Einladung, Passwort vergessen) und der Name der Website im Betreff.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Zurück nach oben</a></p>
    </div>

    <div id="help-configuration-email" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Absender-E-Mail</h3>
        <p>Absenderadresse aller automatischen Mails (Registrierung, Einladung, Datenänderung, Passwort vergessen). Eine korrekte, zustellbare Adresse ist wichtig, damit die Mails nicht im Spam landen.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Zurück nach oben</a></p>
    </div>

    <div id="help-configuration-headlines" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Anzahl Schlagzeilen</h3>
        <p>Wie viele Schlagzeilen die Datei <code>pninc/headlines.inc.php</code> dort ausgibt, wo sie eingebunden ist (1 bis 99).</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Zurück nach oben</a></p>
    </div>

    <div id="help-configuration-news" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Anzahl News auf der Startseite</h3>
        <p>Wie viele News die Startseite zeigt (1 bis 99). Ältere Einträge bleiben über das Archiv erreichbar.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Zurück nach oben</a></p>
    </div>

    <div id="help-configuration-spamprotection" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Spamschutz</h3>
        <p>Mindestabstand in Sekunden zwischen zwei Kommentaren von derselben IP-Adresse. Erlaubt sind 0 bis 86400 Sekunden (ein Tag); 0 schaltet die Sperre ab.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Zurück nach oben</a></p>
    </div>

    <div id="help-configuration-relatedlinks" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Weiterführende Links</h3>
        <p>Erlaubt zu jeder News eine Liste weiterführender Links. Wo sie erscheint, legt das Template über <code>{RELATEDLINKS}</code> fest.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Zurück nach oben</a></p>
    </div>

    <div id="help-configuration-relatedlinks-num" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Anzahl weiterführender Links</h3>
        <p>Wie viele Eingabezeilen für weiterführende Links die News-Formulare anbieten (1 bis 20).</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Zurück nach oben</a></p>
    </div>
</section>

<!-- ==================== EIGENES PROFIL ==================== -->
<section id="help-profile" class="mb-4">
    <h2 class="h5 fw-bold border-bottom pb-2">Eigenes Profil</h2>
    <p>Über <em>Profil editieren</em> oben rechts oder direkt unter <a href="index.php?page=profile">Profil</a> bearbeiten Sie Ihr eigenes Konto.</p>
    <p>Pflichtfelder sind <strong>Nickname</strong> und <strong>E-Mail</strong>. Mit <em>Namen mit E-Mail-Adresse verlinken (öffentlich sichtbar)</em> wird Ihr Name unter Ihren News und Kommentaren zum Mail-Link; die Adresse ist dann für alle Besucher sichtbar. Ein öffentliches Profil gibt es nicht. Die beiden Passwortfelder lassen Sie leer, wenn Sie das Passwort nicht ändern wollen; ein neues Passwort braucht mindestens 8 Zeichen und beendet alle Anmeldungen.</p>
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
        <p>Die folgenden BB-Codes gelten in News bzw. Kommentaren, sofern sie in der <a href="#help-configuration-bbcode">Konfiguration</a> aktiviert sind.</p>
        <div class="table-responsive">
            <table class="table table-sm align-middle">
                <thead>
                    <tr><th>Eingabe</th><th>Ausgabe</th></tr>
                </thead>
                <tbody>
                    <tr><td><code>[b]PowerNews[/b]</code></td><td><strong>PowerNews</strong></td></tr>
                    <tr><td><code>[u]PowerNews[/u]</code></td><td><u>PowerNews</u></td></tr>
                    <tr><td><code>[i]PowerNews[/i]</code></td><td><em>PowerNews</em></td></tr>
                    <tr><td><code>[url]https://www.example.org[/url]</code></td><td><a href="https://www.example.org" rel="noopener noreferrer">https://www.example.org</a></td></tr>
                    <tr><td><code>[url]www.example.org[/url]</code></td><td><a href="https://www.example.org" rel="noopener noreferrer">www.example.org</a> (ohne Schema gilt https://)</td></tr>
                    <tr><td><code>[url=https://www.example.org]Beispiel[/url]</code></td><td><a href="https://www.example.org" rel="noopener noreferrer">Beispiel</a></td></tr>
                    <tr><td><code>[email]info@example.org[/email]</code></td><td><a href="mailto:info@example.org">info@example.org</a></td></tr>
                    <tr><td><code>[img]https://www.example.org/bild.png[/img]</code></td><td>(Bild von der eigenen Domain – sonst bleibt der Code stehen)</td></tr>
                </tbody>
            </table>
        </div>
        <p class="form-text">Links öffnen in einem neuen Fenster. <code>[url]</code> akzeptiert nur http- und https-Adressen; andere Schemata wie <code>javascript:</code> bleiben als Text stehen. <code>[img]</code> zeigt aus Sicherheitsgründen nur Bilder der eigenen Domain.</p>
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
        <p>PowerNews steht unter der MIT-Lizenz. Den vollständigen Text finden Sie unter <a href="index.php?page=other&amp;subpage=license">Sonstiges &gt; Lizenzbedingungen</a>.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Zurück nach oben</a></p>
    </div>

    <div id="help-other-external" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Externe Seite</h3>
        <p>Der Knopf <em>Externe Seite</em> oben rechts führt zur Startseite der Website – praktisch, um nach einer Änderung die Außensicht zu prüfen.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Zurück nach oben</a></p>
    </div>

    <div id="help-other-about" class="ms-3 mb-3">
        <h3 class="h6 fw-bold">Über PowerNews</h3>
        <p>PowerNews ist ein News-Skript für PHP und MySQL/MariaDB der Entwicklungsgruppe <a href="https://www.powerscripts.org" rel="noopener noreferrer">PowerScripts</a>, ursprünglich von Stefan Krämer geschrieben. Version 3 nutzt Bootstrap 5 und bringt ein Benutzer- und Berechtigungssystem mit.</p>
        <p class="text-end mb-0"><a href="#pn-help-top">Zurück nach oben</a></p>
    </div>
</section>
