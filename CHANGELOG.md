# Changelog

## 3.12 – 28.09.2026

### Video-Anleitungen

Zu PowerNews 3.12 gibt es acht Video-Anleitungen: Installation,
Grundeinstellungen, Kategorien, News schreiben, Redakteure und Rechte,
Leser/Kommentare/Einsendungen, Einbinden und Templates – zu finden auf der
Projektseite <https://www.powerscripts.org/projects-1.html>.

### Konten und E-Mails: keine Passwörter im Klartext

- **Registrierung mit eigenem Passwort:** Besucher wählen ihr Passwort bei der
  Registrierung selbst (zweimal, mindestens 8 Zeichen, höchstens 72 Byte wie im
  Installer) und können sich sofort anmelden. Die Bestätigungsmail begrüßt und
  nennt den Anmeldelink, enthält aber kein Passwort. Eigene
  Registrierungsformulare ohne Passwortfelder ergänzt PowerNews automatisch.
- **Einladung statt Zufallspasswort:** „Benutzer hinzufügen“ im Admin legt das
  Konto ohne nutzbares Passwort an und verschickt eine Einladung mit Einmal-Link
  (48 Stunden gültig, `PN_INVITE_LIFETIME`), über den der Benutzer sein
  Passwort selbst festlegt. Ohne Mail zeigt die Erfolgsmeldung den Link einmal
  an. „Neues Passwort“ beim Bearbeiten schickt ebenfalls nur noch einen Link;
  die Seite „Passwort festlegen“ unterscheidet Einladung und Zurücksetzen.
- Konten ohne Passwort und leere Passwortfelder lassen sich nie anmelden.
- **Eigene Betreffzeilen** je Mailart aus der Sprachdatei, mit dem Namen der
  Website: „Willkommen bei …“, „Ihr Zugang zu …“, „Neues Passwort für …“,
  „Ihre Kontodaten bei … wurden geändert“ (Du-Form und Englisch entsprechend)
  statt überall „PowerNews-Benachrichtigung“.
- **Datenschutz:** Das Häkchen „E-Mail-Adresse im Profil anzeigen“ heißt jetzt
  „Namen mit E-Mail-Adresse verlinken (öffentlich sichtbar)“ – ein öffentliches
  Profil gibt es nicht, der Autorname unter News und Kommentaren wird zum
  Mail-Link. Neue Konten verlinken ihre Adresse nicht (Vorgabe „nein“).

### E-Mail-Versand

- **Neu: Versand über einen SMTP-Server** – unverschlüsselt, per STARTTLS
  (meist Port 587) oder SSL/TLS (meist Port 465), mit Anmeldung per AUTH PLAIN
  oder AUTH LOGIN. Das Zertifikat des Servers wird immer geprüft; ohne
  Verschlüsselung meldet sich PowerNews nur an, wenn „none“ ausdrücklich
  eingestellt ist.
- Standard bleibt die PHP-Funktion `mail()` des Servers – bestehende
  Installationen senden unverändert weiter.
- Einstellungen in `pninc/config.local.php` (Abschnitt `mail`) oder über die
  Umgebungsvariablen `PN_MAIL_TRANSPORT`, `PN_MAIL_HOST`, `PN_MAIL_PORT`,
  `PN_MAIL_ENCRYPTION`, `PN_MAIL_USER` und `PN_MAIL_PASS`.
- Web-Installer, Schritt „Website“: optionaler Abschnitt „E-Mail-Versand“ mit
  Versandart, SMTP-Server, Port (Vorschlag passend zur Verschlüsselung),
  Verschlüsselung, Benutzername und Passwort sowie „Test-Mail senden“; die
  Zusammenfassung in Schritt 5 zeigt die Angaben ohne Passwort.
- Absender (From) ist die Absenderadresse aus der Konfiguration, Reply-To
  dieselbe Adresse; Betreff und Namen nach RFC 2047, Text in UTF-8,
  SMTP-Punktverdopplung, 8BITMIME bzw. quoted-printable.
- Fehler landen mit ihrem Grund im Fehlerprotokoll (z. B. „Anmeldung (AUTH
  PLAIN): Server antwortet 535 …“), Passwörter nie.

### Sicherheit

- „Passwort vergessen“ mit Einmal-Link statt Sofort-Reset; Admin-Login mit
  Fehlversuchsbremse und einheitlicher Meldung
- Getrennte Cookies, Admin-Sitzungen 8 h/max. 24 h, kein Passwort-Hash als
  Sitzungsnachweis
- CSRF-Schutz im Admin, Logout per POST; IP nur aus REMOTE_ADDR (Proxys
  konfigurierbar)
- Sicherer Bild-Upload; Rechteprüfungen für Admin-Konten, Kommentare und
  Rechteliste; nur http(s)-Links
- `pngfx/categories/.htaccess`: Im Verzeichnis der Kategoriebilder werden nur
  Bilder ausgeliefert und keine Skripte ausgeführt.
- BB-Code `[url]` erzeugt nur noch http(s)-Links (`[url]https://…[/url]`
  wurde zuvor zu `http://https://…`); ohne Schema gilt https://, dazu
  `[url=Adresse]Text[/url]`. `javascript:`, `data:` & Co. bleiben Text.
- Übersichtsseiten der Admin-Bereiche prüfen das Leserecht; Navigation,
  Schnellzugriff und Unterseiten-Knöpfe zeigen nur, was das Konto darf.

### Fehlerbehebungen

- News bearbeiten, löschen und freischalten funktioniert; Terminwahl mit
  Datumsprüfung; Suche nach ID
- Kein HTTP 500 mehr in Konfiguration, Profil, Kategorien und nach dem
  Frontend-Login
- Apostrophe, $10 und \1 bleiben erhalten; eingesendete Links erscheinen;
  Konfiguration behält BB-Code/Smilies
- Eine News in einer deaktivierten Kategorie wandert beim Bearbeiten nicht
  mehr still in eine andere Kategorie; die Kategorie bleibt vorgewählt und ist
  als „(deaktiviert)“ gekennzeichnet.
- Kommentare stehen in Admin und Frontend chronologisch aufsteigend;
  „Kommentare editieren“ speichert nur geänderte Kommentare.
- Archiv: Nach einer Suche bleibt der gewählte Monat ausgewählt.
- Datums- und Zeitformat: 50 Zeichen wie die Datenbankspalte, keine stille
  Kürzung auf 20 Zeichen mehr.

### Administration

- Die Startseite zeigt „Zu prüfen: N Einsendungen“ mit Link auf die
  gefilterte News-Liste und die neuen Kommentare der letzten 7 Tage.
- News-Liste mit Filter nach Status (Alle, Ungeprüft, Aktiviert, Deaktiviert).

### Sprache & Oberfläche

- Echte Umlaute, Tippfehler behoben, deutsche Monatsnamen; Meldungen mit Farbe
  und Knopf „Weiter“
- Wochentage und Monatsnamen im Frontend in der Sprache der Installation
  (Datumsformat mit l, D, F, M bzw. %A, %a, %B, %b).
- Default-Template ohne Anrede: Die Texte passen zu Du, Sie und jeder Sprache
  (z. B. „Wirklich ausloggen, {NICKNAME}?“). Texte außerhalb des Templates
  kommen weiter aus den Sprachdateien german-du, german-sie und english.
- „Schlagzeilen“ statt „Headlines“ in Admin und Frontend; Ziele weiterführender
  Links als „Neues Fenster“/„Gleiches Fenster“ statt `_blank`/`_main`.
- Admin-Hilfe in german-sie und english neu auf dem Stand 3.12 (bisher die
  deutsche Hilfe von 2002).
- Version aus einer Konstante, MIT-Lizenz, lesbare Navbar, ohne Testhinweis,
  UTF-8-Mails
- Default-Template 3.12: Kategoriebild `{CATPIC}`, „Weiterführende Links“,
  „Passwort vergessen“, Kommentarlink nur bei aktivierten Kommentaren.

### Installation und Update

- **Neuer Web-Installer** `install.php` in fünf Schritten: Systemprüfung,
  Datenbank, Website, Administrator, Abschluss – Bootstrap 5, CSRF-Schutz,
  Post/Redirect/Get, Fehlermeldungen direkt am Feld. Details in
  [INSTALLATION.md](INSTALLATION.md).
- Die Installation ist wieder abschließbar: Semikolons in HTML-Entitäten der
  Templates zerschneiden keine SQL-Anweisungen mehr (Befund B01).
- Datenbankzugang, Seiten-URL, Absenderadresse, Sprache, E-Mail-Versand und
  das Administrator-Konto (Nickname, E-Mail, Passwort) werden abgefragt – kein
  Konto `admin` mit Zufallspasswort und keine Vorgaben auf powerscripts.org
  mehr (B21, B06 zum Teil).
- MySQL 8.0+ und MariaDB 10.3+ werden getrennt erkannt; MySQL 8 wird nicht
  mehr als „zu alt“ abgewiesen (B27).
- Zugangsdaten, Sprache und Mailversand stehen in `pninc/config.local.php`, die
  bei Updates erhalten bleibt. Rangfolge: `config.local.php` >
  Umgebungsvariablen `PN_DB_*` (neu: `PN_DB_PORT`) bzw. `PN_MAIL_*` > Vorgaben.
  Ist `pninc/` schreibgeschützt, bietet der Installer die Datei zum
  Herunterladen an.
- Dauerhafte Sperre über Sperrdatei, `config.local.php` oder eine Zeile in
  `pn_config`; gesperrt antwortet der Installer sofort mit HTTP 403. Ohne
  schreibbaren Ort für die Sperrdatei startet die Installation nicht. Tabellen
  werden nie verworfen (B07, B47).
- `powernews.sql`: kein `DROP TABLE`, keine Testvorlagen „ftghgf“, „dsfs“,
  „dfgdfg“ (B18), keine verwaiste Rechtezeile, neutrale Vorgaben für URL und
  Absender, alle Tabellen in `utf8mb4`, Kommentare sauber in UTF-8, das
  Default-Template 3.12 sowie die neuen Tabellen `pn_password_resets` und
  `pn_migrations`.
- **`update.php` neu:** zeigt Version, Datenbankserver und Herkunft der
  Zugangsdaten und führt nur die nötigen Schritte aus (fehlende Tabellen wie
  `pn_password_resets`, Testvorlagen, verwaiste Rechte, Migrationen, Sperre
  des Installers) – jederzeit wiederholbar (B34).
- **Migrationen 3.11 → 3.12** (`pninc/migrations.inc.php`, ausgeführt von
  `update.php`, vermerkt in `pn_migrations`): überzählige Backslashes
  entfernen, alte Admin-Sitzungen beenden, Tabelle für „Passwort vergessen“,
  weiterführende Links ins JSON-Format, unveränderte Felder des
  Default-Templates auf 3.12. Eigene Template-Anpassungen bleiben erhalten;
  wie sich die neuen Texte übernehmen lassen, steht in
  [INSTALLATION.md](INSTALLATION.md#templates-nach-dem-update).
- Docker-Stack: `.docker/dev-seed.sql` legt einen Entwicklungs-Admin an
  (`admin` / `powernews-dev`); nicht im Release.
- Release-Archiv per `.gitattributes` ohne Tests, interne Dokumentation,
  Docker- und Analyse-Konfiguration (B41); ReadMe-Link zeigt auf
  `README.html` (B42).

### Entfernt

- `convert.php` (Import aus NewsPro): seit 3.0 nicht lauffähig, NewsPro wird
  seit rund 20 Jahren nicht mehr gepflegt (B35).
- `pn_update.sql`; Updates von PowerNews 2.x werden nicht mehr unterstützt.
- `readDump()` im Adminbereich: ungenutzt, seit der Installer `powernews.sql`
  mit einem eigenen Zerleger einliest.
