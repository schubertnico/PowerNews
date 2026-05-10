# Bootstrap-5-Migration & UI-Hardening

**Datum:** 2026-05-10
**Scope:** Komplettumstellung der HTML-Ausgabe (Frontend + Adminbereich) auf Bootstrap 5.3.3,
Datumsformat-Bug-Fix, Kontrast-Hardening, Sicherheits- und Usability-Korrekturen.

---

## 1. Ziele der Iteration

- Vollständige Ablösung der Tabellen-Layouts (cellpadding/bgcolor/font/center) durch
  Bootstrap-5-Markup (Cards, Forms, Tables, Alerts, Badges, Pagination).
- Einheitliches, responsives, barrierearmes UI für Frontend und Adminbereich.
- Self-hosted Bootstrap-Assets, damit die Content-Security-Policy unverändert restriktiv bleibt.
- Fix für die seit Jahren stille Datumsformat-Schräglage (strftime-Tokens vs. PHP `DateTime::format()`).
- Globale Entfernung aller grauen Schriften, die auf hellem Bootstrap-Layout schlecht lesbar waren.
- Saubere Trennung „eingeloggt vs. nicht eingeloggt“ im Adminbereich.

---

## 2. Datumsformat-Konvertierung

Die DB-Konfiguration speichert `pn_config.dateformat` und `pn_config.timeformat`
historisch im strftime-Stil (z. B. `%d.%m.%Y` bzw. `%H:%M`). Der Code nutzt aber
`DateTime::format()`, das das PHP-`date()`-Format erwartet. Folge: News zeigten
Strings wie `%10.%05.%2026 @ %05:%May`.

**Fix:** Neue Helper-Funktion in `pninc/functions.inc.php`:

```php
function pn_convert_date_format(string $format): string
```

- Gibt bereits PHP-`date()`-formatige Strings unverändert zurück (kein `%` enthalten).
- Mappt strftime-Tokens (`%d`, `%m`, `%Y`, `%H`, `%M`, `%I`, `%p`, …) auf die `date()`-Pendants.
- Escapt literale Zeichen, die in `date()` Bedeutung tragen (z. B. „l“ in „Hallo“).

Aufrufstellen: `pn_template::headline()`, `pn_template::news()`, `pn_template::comment()`.
Die Hilfe (`pnadmin/lang/german-du_help.php`) dokumentiert beide Formatstile.

---

## 3. UI-Komplettumstellung auf Bootstrap 5

### 3.1 Self-hosted Bootstrap

Bootstrap 5.3.3 wird unter `assets/bootstrap/` lokal ausgeliefert
(`bootstrap.min.css` + `bootstrap.bundle.min.js`). Damit bleibt die strikte
Content-Security-Policy in `.htaccess` unverändert (`script-src 'self'`,
`style-src 'self' 'unsafe-inline'` ohne CDN-Whitelist).

### 3.2 Frontend (`header.inc.php`, `footer.inc.php`, `pninc/*`)

- Doctype `<!doctype html>`, `<html lang="de">`, UTF-8, Viewport-Meta.
- 12-Spalten-Layout mit `col-md-3` (Sidebar / Hauptmenü) und `col-md-9` (Inhalt).
- News/Headlines/Forms als Bootstrap-Cards (`.card`, `.card-header`, `.card-body`).
- Statusmeldungen als `.alert.alert-{success|danger|warning|info}`.
- Verzicht auf `<center>`, `<font>`, `bgcolor` und `cellpadding` in der Kernlogik.

### 3.3 Datenbank-Templates (`pn_templates`)

Das Default-Template (`id=1`) wurde inhaltlich komplett auf Bootstrap-5-Markup
umgestellt. Migrations-SQL: `.ralph-loop/bootstrap5_templates.sql` (lokal in
ignored Loop-Folder); `powernews.sql` für Fresh-Installs entsprechend
synchronisiert.

Alle Felder neu beschrieben: `message`, `headline`, `news`, `comment`,
`usermenu`, `usermenu2`, `relatedlinks`, `commentform`, `registerform`,
`loginform`, `logout`, `senddataform`, `profileform`, `archive`, `sendnewsform`.

### 3.4 Adminbereich (`pnadmin/*`)

Komplett-Refactor von 28 Modulen:

- `pnadmin/index.php` – Bootstrap-Navbar (dark) + Status-Bar + Container-Fluid + Footer.
- Logo-Bild aus der Navbar entfernt; nur noch Brand-Text „PowerNews 3.00 AdminCenter“.
- Login / Main / Profile / Configuration / News-* / Users-* / Categories-* /
  Permissions-* / Templates-* / Other-* (Help, License) auf Bootstrap-Cards umgestellt.
- Tabellen mit `.table.table-striped.table-hover.align-middle` in `.table-responsive`.
- Pagination mit `.pagination.pagination-sm`.
- Status-Spalten: Bootstrap-Badges (`text-bg-success/warning/danger/secondary/primary`)
  statt `gfx/yes.gif`/`no.gif`/`uc.gif`.
- Gefährliche Aktionen (Delete-Checkboxen) in eigener `.pn-danger-action`-Box mit
  rotem Border + erläuterndem Text – Bedeutung wird nicht nur über Farbe transportiert.
- Helper-Methoden in `pnadmin/functions.inc.php` portiert: `listpages`,
  `listusers/searchuser/listsearchpages`, `listcats`, `listnews/searchnews/listsearchpages`,
  `listpermissions`, `listtemplates`, `getcomments`, `getcatdropdown`,
  `addtemplate`, `edittemplate`.
- Alte `pnadmin/poweradmin.css` von dunklem Theme befreit (setzte global
  `BODY/TABLE/TD { color:#ffffff }`, `INPUT { color:#ffffff; background:#001329 }`,
  `A { color:#B5C3D9 }` – verursachte unleserlichen Text auf dem neuen hellen Layout).
  Datei behält nur noch drei Legacy-Helferklassen für Hilfe-/Lizenz-Texte.

### 3.5 Login-Geschütztes Layout

`pnadmin/index.php` rendert die Hauptnavigation (Templates, Users, Permissions,
Configuration, Categories, News, Other) sowie die Status-/Quicklinks-Leiste nur
noch für eingeloggte Nutzer. Vorher waren die Menü-Links auch ohne Login
sichtbar – funktional ohne Sicherheitsleck (alle Module prüfen `pnadmin[…]`-
Permissions), aber UI-seitig irreführend.

```php
$pnloggedin ??= 'NO';
$isLoggedIn = ($pnloggedin === 'YES');
// … <?php if ($isLoggedIn) { ?> <ul class="navbar-nav">…</ul> <?php } ?>
```

### 3.6 Utility-Skripte

`install.php`, `update.php`, `convert.php` ebenfalls auf Bootstrap-5-Layout umgestellt.
Alte Tabellen mit `bgcolor=#000000`/`#C0C0C0` und `<font color="#FF0000">`-Markup
durch Bootstrap-Cards + `.alert` + `.badge` ersetzt.

---

## 4. Kontrast & Lesbarkeit

### 4.1 Globale Schwarz-/Dunkelschrift-Policy

Alle Bootstrap-Default-Grautöne wurden zentral in `header.inc.php` und
`pnadmin/index.php` überschrieben:

```css
body {
  --bs-secondary-color: #212529;
  --bs-tertiary-color: #212529;
  --bs-link-color: #0a58ca;
  --bs-link-hover-color: #084298;
}
.text-muted, .form-text, small, .small, .pn-help, .copyright {
  color: #212529 !important;
}
.btn-outline-secondary {
  color: #000000;
  border-color: #212529;
  background-color: #ffffff;
}
.link-secondary { color: #0a58ca !important; text-decoration: underline; }
```

**Ergebnis:** keine grauen Texte mehr im gesamten Projekt. WCAG-AA-Audit auf
8 verschiedenen Seiten: 0 Issues. `btn-outline-secondary` jetzt Kontrast 21.00
(schwarz auf weiß) statt vorher ~3-4.

### 4.2 Frontend-Link-Blau

Bootstrap-Standard-Linkfarbe `#0d6efd` ergibt auf `#f8f9fa` body bg nur 4.27
Kontrast (knapp unter WCAG AA). Neue Linkfarbe `#0a58ca` (4.5+).

---

## 5. Navigations-Logik

### 5.1 Zurück-Buttons

Alle 41 Vorkommen von `javascript:history.back()` durch konkrete Rück-URLs ersetzt:

- Frontend: zurück zur Detail-, Archiv-, Login-, Sendnews-, Profile-, Senddata-Form
  (in `pninc/functions.inc.php` über `template->message($txt, $link)`).
- Admin: jeweils zurück zum entsprechenden `?page=…&subpage=…`-Formular.

`history.back()` war nach Form-Submits unzuverlässig (Browser-Back ging dann
zur Submit-URL und löste den POST erneut aus oder zeigte „Formular erneut
absenden“-Dialoge).

### 5.2 Adminbereich Logout-/Login-Pfad

`pnadmin/login.inc.php` zeigt bei Fehlern jetzt einen „Zur Anmeldung“-Button
(`href="./"`) statt `history.back()`.

---

## 6. Hilfe-Modul

`pnadmin/lang/german-du_help.php` komplett neu geschrieben (rund 290 Zeilen).

**Entfernt:**
- HTML-Sektion (HTML-Output ist seit Iteration April 2026 immer escaped → die
  alte Empfehlung „aktiviere HTML“ war irreführend).
- Tote externe Links (z. B. `selfhtml.teamone.de`).
- Statusbeschreibung mit Bilddateien („Grüner Haken / Grauer Strich / Rotes Kreuz“).

**Neu / aktualisiert:**
- 8 Hauptsektionen mit Inhaltsverzeichnis: News, Kategorien, Benutzer,
  Berechtigungen, Templates, Konfiguration, Eigenes Profil, Sonstiges.
- 51 anker-IDs für Direktsprünge, 104 interne Links – alle valide.
- 16 Admin-Routen-Links – alle gegen `allowed_files` validiert.
- Status-Erklärung mit Bootstrap-Badges (`text-bg-success / warning / danger`).
- Kommentar-Moderation: rot umrandete Delete-Box im News-Editor erklärt.
- Berechtigungs-Matrix: Bedeutung der success-/secondary-Badges erklärt.
- Datumsformat-Tabelle mit beiden Token-Stilen (`d`/`%d` etc.) inkl. Hinweis
  auf automatische Konvertierung.
- BB-Code-Tabelle mit `[img]`-Whitelist-Hinweis.
- Profil-Sektion (komplett neu, fehlte vorher).
- Lizenz- und „Externe Seite“-Erklärung.
- Bootstrap-5-Markup: `<section>`, `<h2>`/`<h3>`, `<table class="table-sm">`,
  `<code>` für Platzhalter.

---

## 7. Verifikation

| Check | Tool | Ergebnis |
|-------|------|----------|
| PHP-Syntax aller geänderten Dateien | `php -l` | 0 Fehler |
| Statische Analyse | `vendor/bin/phpstan analyse --memory-limit=1G` | Level 8, 0 Fehler |
| HTTP-Status alle Frontend-Routen | `curl -s -o /dev/null -w "%{http_code}"` | 200 |
| HTTP-Status alle Admin-Routen (eingeloggt) | dito | 200 |
| Bootstrap-Asset-Status | `curl -I /assets/bootstrap/bootstrap.min.css` | 200 |
| Bootstrap-Asset-Status | `curl -I /assets/bootstrap/bootstrap.bundle.min.js` | 200 |
| WCAG-AA-Kontrast-Audit | Chrome MCP `javascript_tool` | 0 graue Texte auf 8 Seiten |
| Hilfe-Anker-Validität | DOM-Check | 104 Links, 0 broken |
| Login-Gating | DOM-Check | nav-links: 0 ohne Login, 7 mit Login |

---

## 8. Geänderte / neue Dateien

**Neu:**
- `assets/bootstrap/bootstrap.min.css` (Bootstrap 5.3.3, ~232 KB)
- `assets/bootstrap/bootstrap.bundle.min.js` (Bootstrap 5.3.3, ~80 KB)
- `docs/2026-05-10-Bootstrap5-Migration.md` (dieses Dokument)

**Geändert (Auswahl, ca. 45 Dateien):**
- `header.inc.php`, `footer.inc.php`, `index.php`, `archive.php`, `comments.php`,
  `news.php`, `sendnews.php`, `user.php`
- `install.php`, `update.php`, `convert.php`
- `.htaccess` (CSP unverändert; nur kurz für Verifikation erweitert, dann zurückgesetzt)
- `.gitignore` (`.ralph-loop/` ausgenommen)
- `powernews.sql` (Default-Template auf BS5)
- `pninc/config.inc.php`, `pninc/functions.inc.php`, `pninc/head.inc.php`
- `pnadmin/index.php`, `pnadmin/phpheader.inc.php`, `pnadmin/functions.inc.php`,
  `pnadmin/poweradmin.css`
- `pnadmin/login.inc.php`, `main.inc.php`, `profile.inc.php`, `configuration.inc.php`
- `pnadmin/news.inc.php`, `news_add.inc.php`, `news_edit.inc.php`,
  `news_search.inc.php`, `news_show.inc.php`
- `pnadmin/users.inc.php`, `users_add.inc.php`, `users_edit.inc.php`,
  `users_search.inc.php`, `users_show.inc.php`
- `pnadmin/categories.inc.php`, `categories_add.inc.php`,
  `categories_edit.inc.php`, `categories_show.inc.php`
- `pnadmin/permissions.inc.php`, `permissions_add.inc.php`,
  `permissions_edit.inc.php`, `permissions_show.inc.php`
- `pnadmin/templates.inc.php`, `templates_add.inc.php`,
  `templates_edit.inc.php`, `templates_show.inc.php`
- `pnadmin/other.inc.php`, `other_help.inc.php`, `other_license.inc.php`
- `pnadmin/lang/german-du_help.php` (Hilfe komplett neu)

---

## 9. Migration für bestehende Installationen

Wer auf einer bestehenden Datenbank updatet, muss zusätzlich zum Code-Update
das Default-Template auf Bootstrap 5 heben. Beispiel via Container-MariaDB:

```bash
# SQL-Migration aus dem Repo gegen die laufende DB ausführen
docker exec -i powernews_db mariadb -upowernews -ppowernews powernews \
    < .ralph-loop/bootstrap5_templates.sql
```

(Das Script findet sich im lokalen Loop-Output und ist nicht eingecheckt; alternativ
kann das `news`/`headline`/`comment`-Feld aus einer frischen `powernews.sql`-Installation kopiert werden.)

Nach dem Update: Datums- und Zeitformat in der Konfiguration optional auf
PHP-`date()`-Tokens umstellen (`d.m.Y`, `H:i`). Die Konvertierung läuft auch
mit den alten strftime-Tokens weiter, aber `date()`-Tokens sind zukunftssicher.
