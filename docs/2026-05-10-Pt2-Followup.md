# Bootstrap-5-Iteration – Nachzieher (Pt 2)

**Datum:** 2026-05-10 (Spaetlauf)
**Vorgaenger:** [`2026-05-10-Bootstrap5-Migration.md`](2026-05-10-Bootstrap5-Migration.md)
**Scope:** Folge-Korrekturen direkt nach dem Bootstrap-5-Hauptrefactor – Login-Status sichtbar machen,
Brotkrumen-Navigation, Default-Template-Editierbarkeit, Version- und Copyright-Bump,
`{RELATEDLINKS}`-Bug, doppelte Copyright-Anzeige.

---

## 1. Login-Status sichtbar (kritischer UX-Bug)

**Problem:** Im Adminbereich war nicht erkennbar, ob man eingeloggt war – kein "Hallo admin",
kein Logout-Button. Der User vermutete sogar einen Sicherheitsfehler ("kann ich ohne Login Einstellungen aendern?").

**Ursache:** `pnadmin/index.php` rief `$individualmenus->statusmenu()` auf, aber die Klasse `menus`
wurde nirgendwo instanziiert. `isset($individualmenus)` schuetzte nur vor dem Crash und liess die
Statuszeile leer.

**Fix:**
- `$individualmenus = new menus();` in `pnadmin/phpheader.inc.php` direkt nach dem Konfig-Laden.
- **Login-Status, Profil-Link und Logout-Button direkt in die Navbar verlegt** statt in eine
  versteckte Status-Bar:
  - "Hallo **admin**" als `navbar-text`
  - "Profil bearbeiten"-Button (`btn-outline-light`)
  - "Externe Seite"-Button (`btn-outline-light`)
  - **"Logout"-Button** (`btn-warning`, gelb – auffaellig)

**Sicherheits-Check:** Verifiziert per curl, dass POSTs auf `?page=configuration&edit=YES` ohne
Cookie tatsaechlich nichts in der DB aendern (nur Login-Form rendert). Es war ein UX-Problem,
keine Lecks.

---

## 2. Logo aus Adminnavbar entfernt

`<img src="./powernews.gif">` raus, nur Brand-Text "PowerNews 3.10 AdminCenter" bleibt.
Auf Wunsch des Users.

---

## 3. Brotkrumen-Submenu komplett neu (zwei Iterationen)

### 3.1 Kaputtes Original-Submenu

**Symptom:** Auf den Sektionsuebersichten erschienen einsame `»`-Zeichen in einer Reihe statt
sauberer Navigation.

**Ursache:** `menus::submenu()` gab Markup wie `&raquo; <a>...</a><br>` aus. Im
`d-flex flex-wrap gap-2`-Container wurden die `»`-Zeichen zu eigenen Flex-Items und die `<br>`-Tags
brachen das Layout.

**Fix 1 (Pillen-Tab):** `submenu()` neu mit Bootstrap-Outline-Buttons als Pillen-Navigation
(`btn-sm btn-outline-primary`). Aktuelle Subpage wird automatisch als gefuellter
`btn-primary` markiert.

### 3.2 Echte Breadcrumb-Navigation

**Feedback:** "`Benutzer › [Benutzer hinzufuegen] [Benutzer anzeigen] [Benutzer suchen]` ist kein
Brotkrumen-Menue!" – User unterschied Tab-Navigation von echter Breadcrumb.

**Fix 2 (Breadcrumb + Tab):**
- **Echte Bootstrap-Breadcrumb** (`<nav class="breadcrumb">`) mit Pfad-Anzeige:
  - `Start › Benutzer › Anlegen` (mit `aria-current="page"` auf der aktiven Seite)
  - "Start" und "Benutzer" sind anklickbare Links, "Anlegen" ist die aktive Seite.
- Lokalisierte Sektions- und Subpage-Namen (`add → Anlegen`, `show → Anzeigen`,
  `edit → Bearbeiten`, `search → Suchen`, `help → Hilfe`, `license → Lizenz`).
- **Tab-Navigation bleibt zusaetzlich erhalten** als Schnellzugriff-Card (vom User explizit
  gewuenscht).

### 3.3 Doppelte Buttons entfernt

Auf den Sektionsuebersichten (`?page=users` ohne Subpage) wurden die drei Subpage-Buttons
sowohl im Tab-Submenu **als auch** in der Inhalts-Card angezeigt. Die doppelten Buttons sind
aus den Router-Files (`users.inc.php`, `news.inc.php`, `categories.inc.php`,
`permissions.inc.php`, `templates.inc.php`, `other.inc.php`) entfernt; nur der Hinweistext
"Bitte waehlen Sie eine Unterseite" bleibt.

---

## 4. Default-Template (id=1) editierbar

**Problem:** Beim Aufruf von `?page=templates&subpage=edit&templateid=1` blieb die
Karte komplett leer.

**Ursache 1 (PHP 8.4 strict_types):** `template::checktemplate()` erwartete `int`,
bekam aber `'1'` als `string` aus `$_GET['templateid']` → TypeError, Page-Render
brach ab. Fix: `(int) ($_GET['templateid'] ?? 0)` in `templates_edit.inc.php`.

**Ursache 2 (Schreibschutz):** Das Default-Template war frueher hart gegen Editieren
gesperrt. Das ist UX-Mist (Form sichtbar, Speichern → Fehler). **Neue Logik:** Default-Template
**darf editiert** werden, nur **Loeschen** bleibt blockiert (sonst bricht die Vorlage fuer
"Template anlegen" weg).

**UI:** Warnungs-Alert an erster Stelle der Edit-Page:
> "Hinweis: Du editierst das Default-Template (ID 1). Dieses Template wird beim Anlegen
> neuer Templates als Grundlage kopiert. Aenderungen wirken sich also auch auf alle
> kuenftig neu angelegten Templates aus. Loeschen ist aus diesem Grund nicht moeglich."

Die Delete-Checkbox wird fuer ID 1 nicht gerendert.

---

## 5. Version 3.00 → 3.10 + Copyright 2001-2026

**Versions-Bump in 5 Stellen:**
- `pninc/config.inc.php`: `$pn_config['version'] = '3.10';`
- `pnadmin/index.php`: `$psdesignversion = '3.10';`
- `install.php`, `update.php`: `$thisversion = '3.10';`
- `README.md`: Titel `# PowerNews v3.10`

**Copyright in 57 Dateien:** alle `2001-2023` und `2001-2024` → `2001-2026`. Betroffen:
- Alle PHP-Lizenzheader in `pninc/`, `pnadmin/`, Frontend, Utility-Skripten.
- Footer-Link im Adminbereich (`pnadmin/index.php`).

---

## 6. `{RELATEDLINKS}`-Platzhalter-Bug

**Symptom:** Auf der Frontend-Startseite und im News-Detail erschien wortwoertlich
`{RELATEDLINKS}` als Text in einer "Related Links"-Card, obwohl die Funktion in der
Konfiguration deaktiviert war.

**Ursache:** `pn_template::news()` ersetzte `{RELATEDLINKS}` nur, wenn
`$pnconfig['relatedlinks'] == 'YES'`. Bei deaktivierter Funktion blieb der Platzhalter
unverarbeitet stehen.

**Fix:**
- `{RELATEDLINKS}` wird jetzt **immer** ersetzt – durch HTML-Listen-Items wenn
  Funktion aktiv und Daten vorhanden, sonst durch leeren String.
- **Konditionale Sidebar:** Das Default-Template hat die "Related Links"-Sidebar in
  HTML-Comment-Marker eingewickelt:
  ```html
  <!--RELATEDLINKS_START-->
  <div class="border rounded p-2 bg-light mt-3">
      <strong class="d-block mb-2">Related Links</strong>
      <ul class="list-unstyled small mb-0">{RELATEDLINKS}</ul>
  </div>
  <!--RELATEDLINKS_END-->
  ```
  PHP entfernt den ganzen Block automatisch wenn keine Links da sind, sodass kein
  leerer "Related Links"-Header erscheint.
- DB-Default-Template (`pn_templates.id=1`) und `powernews.sql` synchronisiert.

---

## 7. Doppeltes Copyright entfernt

**Symptom:** Unter jeder News stand "PowerNews 3.10 © Copyright 2003 by PowerScripts" und
zusaetzlich der globale Footer "PowerNews — PowerScripts".

**Ursache:** Die Hilfsfunktion `pn_cpi()` in `pninc/functions.inc.php` rendete eine eigene
Copyright-Zeile, aufgerufen am Ende von `archive.inc.php`, `comments.inc.php`,
`details.inc.php`, `news.inc.php`, `sendnews.inc.php` und `user.inc.php`. Mit dem neuen
zentralen Footer in `footer.inc.php` redundant.

**Fix:** `pn_cpi()` ist jetzt ein No-Op (gibt nichts mehr aus), bleibt aber als Funktion
fuer API-Kompatibilitaet erhalten. Der globale Footer uebernimmt die Copyright-Anzeige.

---

## 8. Verifikation

| Check | Tool | Ergebnis |
|-------|------|----------|
| PHP-Syntax | `php -l` | 0 Fehler |
| Statische Analyse | `vendor/bin/phpstan analyse --memory-limit=1G` | Level 8, **0 Fehler** |
| Login-Buttons sichtbar nach Login | Chrome MCP DOM | "Hallo admin", Profil, Externe, Logout (gelb) |
| Login-Buttons unsichtbar ohne Login | Chrome MCP DOM | nur Brand + "Externe Seite" |
| Breadcrumb auf 6 Routen | curl | `Start › Benutzer › Anlegen` etc. |
| `{RELATEDLINKS}`-Literal sichtbar? | Chrome MCP | nein |
| Doppeltes Copyright? | Chrome MCP | nein, nur 1× "PowerScripts" |
| Default-Template-Edit lädt Form | Chrome MCP | hasForm: true, hasTitleInput: true, Warnungs-Alert da |

---

## 9. Geänderte Dateien (in dieser Folge-Iteration)

- `pnadmin/index.php` (Navbar mit Login-Status, Logo weg, Breadcrumb, Schnellzugriff-Card)
- `pnadmin/phpheader.inc.php` (`$individualmenus = new menus();`)
- `pnadmin/functions.inc.php` (Submenu auf Bootstrap-Pillen, Default-Template-Loesch-Schutz lockerer)
- `pnadmin/templates_edit.inc.php` (`(int)`-Cast, Default-Template-Editierbarkeit, Warnungs-Alert)
- `pnadmin/users.inc.php`, `news.inc.php`, `categories.inc.php`, `permissions.inc.php`,
  `templates.inc.php`, `other.inc.php` (doppelte Subpage-Buttons aus Default-Card entfernt)
- `pninc/functions.inc.php` (`{RELATEDLINKS}` immer ersetzen, konditionale Sidebar, `pn_cpi()` no-op)
- `pninc/config.inc.php` (`$pn_config['version'] = '3.10'`)
- `pnadmin/index.php`, `install.php`, `update.php` (Version 3.10, Copyright 2026)
- `powernews.sql` (Default-Template mit `<!--RELATEDLINKS_START/END-->` Markern)
- `README.md` (`v3.10` im Titel)
- 57 PHP-Lizenzheader (Copyright 2001-2026)
- Diese Doku: `docs/2026-05-10-Pt2-Followup.md`
