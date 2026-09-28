# PowerNews v3.11

[![PHP](https://img.shields.io/badge/PHP-8.4-blue)](https://www.php.net/)
[![tests](https://img.shields.io/badge/tests-734%20passing-brightgreen)](#tests)
[![coverage](https://img.shields.io/badge/coverage-85%25-brightgreen)](#tests)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3.3-7952B3)](https://getbootstrap.com/)
[![license](https://img.shields.io/badge/license-MIT-yellow)](LICENSE)

Ein schlankes, auf PHP 8.4 und MariaDB modernisiertes News-System mit Benutzer-, Kategorie-, Kommentar- und Templateverwaltung. Frontend und Adminbereich sind komplett auf **Bootstrap 5.3** umgestellt – responsiv, barrierearm, ohne CDN.

---

## Schnellstart (Docker, in 60 Sekunden)

```bash
# 1. Repository klonen
git clone https://github.com/schubertnico/PowerNews.git
cd PowerNews

# 2. (optional) Composer-Abhängigkeiten für Entwicklungswerkzeuge
composer install

# 3. Container starten (beim Erststart wird das Image automatisch gebaut)
cd .docker
docker compose up -d

# 4. Browser öffnen
# Hauptseite: http://localhost:8087/
# Admin:      http://localhost:8087/pnadmin/
# Mailpit:    http://localhost:8033/
```

Beim ersten Start spielt der Datenbank-Container `powernews.sql` und die Entwicklungsdaten `.docker/dev-seed.sql` ein. Anmeldung im Adminbereich mit Nickname **`admin`** und Passwort **`powernews-dev`** (nur Entwicklung, nicht im Release). Der Web-Installer ist im Stack gesperrt, weil die Datenbank bereits eingerichtet ist.

**Schnellcheck nach der Installation:**

| Check | Ergebnis |
|-------|----------|
| `curl -I http://localhost:8087/` | `HTTP/1.1 200`, `Server: Apache` (ohne Version) |
| `curl -I http://localhost:8087/install.php` | `HTTP/1.1 403` (Installer gesperrt) |
| Registrierungs-Mail | landet im Mailpit (http://localhost:8033/) |

---

## Anforderungen

- PHP 8.4 (oder höher) mit den Erweiterungen `mysqli` und `mbstring`
- MariaDB 10.3+ / MySQL 8.0+
- Apache 2.4 mit `mod_rewrite` + `mod_headers`
- Für den Mailversand: `msmtp` oder ein SMTP-Relay (im Docker-Setup automatisch via Mailpit)

---

## Installation ohne Docker (Web-Installer)

1. Inhalt des Release-Archivs hochladen (auch die `.htaccess`-Dateien), `logs/` beschreibbar machen.
2. Eine leere MariaDB-/MySQL-Datenbank anlegen.
3. `https://<ihre-domain>/install.php` aufrufen und die fünf Schritte durchlaufen: Systemprüfung, Datenbank (mit Verbindungstest), Website (URL, Absender, Sprache), Administrator (Nickname, E-Mail, Passwort), Abschluss.
4. `install.php` löschen und im Adminbereich `pnadmin/` mit Nickname und Passwort anmelden.

Die Zugangsdaten landen in `pninc/config.local.php` (bleibt bei Updates erhalten); danach ist der Installer gesperrt. Ausführlich – auch Shared Hosting, Kommandozeile, nginx und Update – in [INSTALLATION.md](INSTALLATION.md).

---

## Konfiguration

### Zugangsdaten

Rangfolge: `pninc/config.local.php` (legt der Installer an) > Umgebungsvariablen > Vorgaben. Zugangsdaten nicht in `pninc/config.inc.php` eintragen – die Datei wird bei Updates überschrieben.

| Variable | Beschreibung | Standard |
|----------|-------------|----------|
| `PN_DB_HOST` | Datenbank-Host | `localhost` |
| `PN_DB_PORT` | Datenbank-Port | `3306` |
| `PN_DB_USER` | Datenbank-Benutzer | `root` |
| `PN_DB_PASS` | Datenbank-Passwort | (leer) |
| `PN_DB_NAME` | Datenbank-Name | `powernews` |
| `PN_TRUSTED_PROXIES` | Vertrauenswürdige Reverse-Proxys (IPs oder CIDR, kommagetrennt). Nur von dort wird `X-Forwarded-For` ausgewertet; alternativ `$pn_config['trustedproxies']` | (leer) |

### Docker-Ports

| Dienst | Port | Beschreibung |
|--------|------|-------------|
| Web | 8087 | Apache/PHP |
| MariaDB | 3317 | Datenbank |
| Mailpit SMTP | 1033 | E-Mail-Empfang intern |
| Mailpit Web | 8033 | Web-UI für Test-Mails |

### Sichere Defaults in `pn_config`

Beim Fresh-Install sind folgende Defaults gesetzt:

| Option | Default | Bedeutung |
|--------|---------|-----------|
| `newssending` | `Registered` | Nur eingeloggte Nutzer dürfen News einreichen |
| `commentwriting` | `Registered` | Kommentare nur für eingeloggte Nutzer |
| `html` | `Comments` | Kommentare HTML-escaped, News werden zusätzlich immer escaped |
| `bbcode` | `Comments` | BBCode nur in Kommentaren |

---

## Funktionen

- **News-Verwaltung**: Erstellen, Bearbeiten, Aktivieren, Löschen von News-Einträgen
- **Kategorien**: mehrstufige Zuordnung, Seed-Kategorie „Allgemein" beim Install
- **Benutzer-Verwaltung**: Registrierung, Profil, „Passwort vergessen“ mit Einmal-Link (60 Minuten gültig, nur der Token-Hash wird gespeichert)
- **Kommentare**: mit Spamschutz pro IP (`REMOTE_ADDR`; `X-Forwarded-For` nur hinter konfigurierten Proxys) und Längenbegrenzung
- **Templates**: anpassbare HTML-Templates pro Bereich, CSRF-`{CSRF}`- und `{CSRF}`-Platzhalter automatisch ersetzt
- **Archiv**: durchsuchbares News-Archiv
- **Mail**: Registrierungs- und Passwort-Reset-Mails via `msmtp` → Mailpit (Docker) oder beliebigem SMTP-Relay
- **Modernes UI**: Bootstrap 5.3.3 (lokal gehostet, ohne CDN) für Frontend & Adminbereich – responsiv, barrierearm, mit Cards/Tables/Alerts/Badges
- **Adminhilfe**: Eingebauter Hilfe-Bereich (`?page=other&subpage=help`) mit Inhaltsverzeichnis, BB-Code-/Smilies-Referenz und Modul-Anleitungen
- **Datumsformat-Konverter**: Akzeptiert sowohl PHP-`date()`-Tokens (`d.m.Y`/`H:i`) als auch strftime-Tokens (`%d.%m.%Y`/`%H:%M`); ältere Konfigurationen funktionieren ohne manuelle Migration weiter

---

## Stand 2026-05-10 – Bootstrap-5-Refactor + Folgekorrekturen

Mit der Iteration vom 10.05.2026 sind Frontend und Adminbereich vollständig auf
Bootstrap 5.3.3 umgestellt. Aktuelle Version: **3.11**. Highlights:

**UI & Layout:**
- **Self-hosted Bootstrap** unter `assets/bootstrap/` (kein CDN, CSP unverändert restriktiv).
- **Echte Breadcrumb-Navigation** im Adminbereich: `Start › Benutzer › Anlegen` mit
  `aria-current="page"` auf der aktiven Seite.
- **Schnellzugriff-Tab-Card** mit Pillen-Navigation für die Sub-Pages des aktuellen Bereichs.
- **Login-Status sichtbar:** "Hallo admin" + Profil-/Logout-Buttons direkt in der Navbar
  (Logout in `btn-warning`-Gelb, fällt sofort auf).
- **WCAG-AA-Audit:** keine grauen Texte mehr; alle muted-Bootstrap-Klassen
  übersteuert auf #212529 / #000000 / #0a58ca. Audit auf 8 Seiten: 0 Issues.
- **Status-Spalten** in Admin-Tabellen: Bootstrap-Badges statt `gfx/yes.gif`/`no.gif`/`uc.gif`.
- **Gefährliche Aktionen** (Delete-Checkboxen) in `pn-danger-action`-Box mit rotem Border +
  erläuterndem Text – Bedeutung nicht nur über Farbe.

**Bugfixes:**
- **Datumsformat-Bug:** `pn_convert_date_format()` mappt strftime-Tokens auf
  PHP-`date()`-Tokens, sodass `%d.%m.%Y`/`%H:%M`-Konfigurationen ohne Migration weiterlaufen.
- **`{RELATEDLINKS}`-Platzhalter:** wird jetzt immer ersetzt; die Sidebar verschwindet
  automatisch, wenn keine Links da sind.
- **Default-Template (id=1)** editierbar (vorher gesperrt) – nur Löschen bleibt blockiert,
  damit die Vorlage für "Template anlegen" nicht wegbricht.
- **Doppeltes Copyright** entfernt: `pn_cpi()` ist No-Op, der globale Footer übernimmt.
- **41 Zurück-Buttons** von `javascript:history.back()` auf konkrete Rück-URLs umgestellt.
- **PHP 8.4 strict_types** Cast in `templates_edit.inc.php` (`(int)` für `templateid`).

**Dokumentation:**
- **Adminhilfe** komplett neu (51 anker-IDs, 104 interne Links, 16 Admin-Routen alle validiert;
  Status-Badges, Code-Snippets, BB-Code-/Smilies-Referenz).
- Copyright in 57 Dateien aktualisiert auf 2001-2026.

Details siehe
[`docs/2026-05-10-Bootstrap5-Migration.md`](docs/2026-05-10-Bootstrap5-Migration.md) (Hauptmigration)
und [`docs/2026-05-10-Pt2-Followup.md`](docs/2026-05-10-Pt2-Followup.md) (Folgekorrekturen).

---

## Sicherheitsstand (Stand 2026-04-24)

Nach Audit und Fix-Sweep vom April 2026 sind folgende Härtungen eingebaut. Details siehe [`docs/2026-04-23-Userbereichs-bugs.md`](docs/2026-04-23-Userbereichs-bugs.md).

### Authentifizierung & Sessions

- Server-seitige Session-Validierung über `pn_sessions` (SHA-256-Token). Cookie-Format: `userId:hex-token`.
- Beim Logout wird der Token serverseitig gelöscht; gestohlene Cookies sind damit sofort ungültig.
- Login mit `status = 'Deactivated'` wird abgewiesen.
- Konstante Antwortzeit bei Login (Dummy-`password_verify` für unbekannte Nicknames) gegen Timing-Enumeration.
- IP- und Nickname-basiertes Rate-Limit (10 Fehlversuche / 15 Minuten) in `pn_login_attempts`.

### CSRF

- Session-gebundener CSRF-Token (`pn_csrf_token()` / `pn_csrf_verify()`).
- Alle User-Formulare (Register, Login, Profil, Senddata, Comment, Sendnews, Logout) enthalten ein Hidden-`csrf_token`-Feld.
- Logout nur per POST.
- Login-Verify zusätzlich in `pninc/head.inc.php` vor dem Cookie-Setzen.

### Input-Validierung

- Whitelist-Regex für Nicknames (3–30 Zeichen, Buchstaben/Ziffern/._-, Umlaute erlaubt).
- E-Mail: `FILTER_VALIDATE_EMAIL` + Maxlength.
- URL: nur `http(s)://…`, `FILTER_VALIDATE_URL`.
- Age: 0–150 geclampt.
- Kommentare: max. 5 000 Zeichen.
- Sendnews-Relatedlinks: strukturiert als JSON (kein mehr Delimiter-Injection).

### Output-Escape

- News-Titel, -Text und -Moretext werden immer (unabhängig von Config) HTML-escaped.
- Kommentare werden immer escaped.
- BBCode `[img]` lässt nur die eigene Host-Domain (`pnconfig.url`) zu, externe Tracking-Pixel werden nicht gerendert.
- Alle Ausgaben in Templates/Admin via `pn_escape()` (= `htmlspecialchars(ENT_QUOTES, 'UTF-8')`).

### HTTP / Apache

- `Content-Type: text/html; charset=UTF-8` (end-to-end).
- `Accept-Charset="UTF-8"` auf allen Formularen.
- Security-Header in `.htaccess`: `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`, `Content-Security-Policy`.
- `ServerTokens Prod` und `ServerSignature Off` via Apache-Conf (zz-security-hardening.conf).
- `.htaccess` blockiert Zugriffe auf `.git/`, `logs/`, `composer.*`, `*.sql`, Linter-Configs.
- `phpinfo.php` entfernt.

### Installer / Infrastruktur

- Web-Installer mit CSRF-Schutz; nach der Installation dauerhaft gesperrt (Sperrdatei, `config.local.php` oder Zeile in `pn_config`) – gesperrt antwortet er mit HTTP 403 und verwirft nie Tabellen.
- `update.php` erfordert Admin-Login (`canwriteconfig = 'YES'`) und ein CSRF-Token.
- Kein Standard-Administrator: Nickname und Passwort legt der Betreiber im Installer fest.

### Datenbank

- Prepared Statements durchgängig.
- Bcrypt-Passwort-Hashes (automatischer Upgrade bestehender Base64-Passwörter beim ersten Login).
- Neue Tabellen: `pn_sessions`, `pn_login_attempts`.
- Seed-Kategorie „Allgemein" wird beim Install angelegt, sodass News-Einsendung direkt funktioniert.

---

## Entwicklung

### Tests

```bash
# Unit-Tests (PHPUnit 11)
docker exec powernews_web bash -c "cd /var/www/html && vendor/bin/phpunit --testsuite Unit"

# Integration-Tests
docker exec powernews_web bash -c "cd /var/www/html && vendor/bin/phpunit --testsuite Integration"
```

### Statische Analyse

```bash
composer run phpstan
```

### Rector (Modernisierungs-Vorschläge)

```bash
composer run rector:dry    # Vorschau
composer run rector        # Anwenden
```

### Pre-Commit-Hook

Der Pre-Commit-Hook im Repo führt automatisch aus:
1. PHPStan
2. Scan auf direkte Superglobal-Zugriffe außerhalb der Validation-Layer
3. PHPUnit Unit-Tests

Commits werden geblockt, wenn Tests rot sind oder PHPStan Fehler meldet.

---

## Update

Update von 3.11 auf 3.12: Datensicherung, Zugangsdaten nach `pninc/config.local.php` übernehmen, Dateien hochladen (ohne `install.php`), als Admin `update.php` aufrufen – Schritt für Schritt in [INSTALLATION.md](INSTALLATION.md#update-von-311-auf-312). Bestehende Benutzer mit Legacy-Passwort werden beim ersten Login transparent auf bcrypt umgestellt. Updates von 2.x werden nicht mehr unterstützt.

**Default-Template auf Bootstrap 5 heben** (nur bei Installationen von vor Mai 2026): Beim Update von einer Vor-2026-05-Version müssen die Felder
   `news`, `headline`, `comment`, `commentform`, `loginform`, `registerform`, `profileform`,
   `senddataform`, `archive`, `sendnewsform`, `usermenu`, `usermenu2`, `relatedlinks`,
   `logout` und `message` der Zeile `id=1` in `pn_templates` auf das neue Bootstrap-5-
   Markup gehoben werden. Der einfachste Weg: Werte aus einer frischen
   `powernews.sql`-Installation per `UPDATE pn_templates SET … WHERE id=1` einspielen.

Details siehe [`docs/2026-05-10-Bootstrap5-Migration.md`](docs/2026-05-10-Bootstrap5-Migration.md).

---

## Dokumentation

- **Installation, Konfiguration und Update:** [`INSTALLATION.md`](INSTALLATION.md)
- **Änderungen je Version:** [`CHANGELOG.md`](CHANGELOG.md)
- **Bootstrap-5-Migration & UI-Hardening (2026-05-10):** [`docs/2026-05-10-Bootstrap5-Migration.md`](docs/2026-05-10-Bootstrap5-Migration.md)
- **Folgekorrekturen (Pt 2, 2026-05-10):** [`docs/2026-05-10-Pt2-Followup.md`](docs/2026-05-10-Pt2-Followup.md) – Login-Status sichtbar, echte Breadcrumb-Navigation, Default-Template editierbar, Version 3.10, `{RELATEDLINKS}`-Bug, doppeltes Copyright entfernt
- **i18n-Konsolidierung (Pt 3, 2026-05-10):** [`docs/2026-05-10-Pt3-I18n.md`](docs/2026-05-10-Pt3-I18n.md) – 77 deutsche Strings in `pnadmin/lang/english.php` übersetzt, doppelte Konstanten entfernt, alle drei Sprachdateien deckungsgleich (Version 3.11)
- Audit-Report (April 2026): [`docs/2026-04-23-Userbereichs-bugs.md`](docs/2026-04-23-Userbereichs-bugs.md)
- Improvements: [`docs/2026-04-23-Userbereichs-improvements.md`](docs/2026-04-23-Userbereichs-improvements.md) (Alias `…-verbesserungen.md`)
- Testabdeckung: [`docs/2026-04-23-Userbereichs-test-coverage.md`](docs/2026-04-23-Userbereichs-test-coverage.md) (Alias `…-testabdeckung.md`)
- Implementierungsplan für die Bugfixes: [`docs/superpowers/plans/2026-04-23-userbereich-bugfixes.md`](docs/superpowers/plans/2026-04-23-userbereich-bugfixes.md)
- **Adminhilfe** (interaktiv im Adminbereich): `http://<host>/pnadmin/index.php?page=other&subpage=help`

---

## Lizenz

MIT License – siehe [LICENSE](LICENSE).
Copyright © 2001–2026 PowerScripts / SchubertMedia.

---

## Projekt & Links

- Website: <https://www.powerscripts.org>
- Projektübersicht: <https://www.powerscripts.org/projects-1.html>
- Quellcode: <https://github.com/schubertnico/PowerNews>
- Issues / Bugs: <https://github.com/schubertnico/PowerNews/issues>

---

## Kontakt

**SchubertMedia**
Inhaber: Nico Schubert
Stauffenbergallee 57
D – 99085 Erfurt

- Telefon: +49 (0) 3612 3002247 · Mo.–Fr. 9–12 und 13–18 Uhr
- Telefax: +49 (0) 3612 3004636
- E-Mail: <info@schubertmedia.de>
