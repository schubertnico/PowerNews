# PowerNews v3.0

Ein schlankes, auf PHP 8.4 und MariaDB modernisiertes News-System mit Benutzer-, Kategorie-, Kommentar- und Templateverwaltung.

---

## Schnellstart (Docker, in 60 Sekunden)

```bash
# 1. Repository klonen
git clone https://github.com/schubertnico/PowerNews.git
cd PowerNews

# 2. (optional) Composer-Abhängigkeiten für Entwicklungswerkzeuge
composer install

# 3. Container bauen & starten
cd .docker
docker compose up -d --build

# 4. Browser öffnen
# Hauptseite: http://localhost:8087/
# Admin:      http://localhost:8087/pnadmin/
# Mailpit:    http://localhost:8033/
```

Beim ersten Aufruf von `http://localhost:8087/install.php` (**einmalig**) wird ein frischer Admin-Zugang mit zufälligem Passwort erzeugt und auf der Seite angezeigt. Bitte notieren und anschließend im Adminbereich das Passwort ändern. Anschließend wird automatisch `pninc/install.lock` gesetzt, damit die Datenbank nicht mehr überschrieben werden kann.

**Schnellcheck nach der Installation:**

| Check | Ergebnis |
|-------|----------|
| `curl -I http://localhost:8087/` | `HTTP/1.1 200`, `Server: Apache` (ohne Version) |
| `curl http://localhost:8087/install.php` (Post) | Hinweis „Installation bereits erfolgt" |
| Registrierungs-Mail | landet im Mailpit (http://localhost:8033/) |

---

## Anforderungen

- PHP 8.4 (oder höher)
- MariaDB 10.3+ / MySQL 8.0+
- Apache 2.4 mit `mod_rewrite` + `mod_headers`
- Für den Mailversand: `msmtp` oder ein SMTP-Relay (im Docker-Setup automatisch via Mailpit)

---

## Installation ohne Docker

1. Dateien ins Web-Root hochladen.
2. MariaDB-/MySQL-Datenbank anlegen.
3. Zugangsdaten per Umgebungsvariablen (siehe unten) oder direkt in `pninc/config.inc.php` hinterlegen.
4. `http://<dein-host>/install.php` einmalig aufrufen – das Skript legt die Tabellen an und erzeugt einen Admin mit Random-Passwort.
5. `install.php`, `update.php`, `convert.php` nach der Installation löschen (oder hinter eine Admin-Auth lassen – sie sind im Auslieferungszustand bereits serverseitig auth-gegated).

---

## Konfiguration

### Umgebungsvariablen

| Variable | Beschreibung | Standard |
|----------|-------------|----------|
| `PN_DB_HOST` | Datenbank-Host | `localhost` |
| `PN_DB_USER` | Datenbank-Benutzer | `root` |
| `PN_DB_PASS` | Datenbank-Passwort | (leer) |
| `PN_DB_NAME` | Datenbank-Name | `powernews` |

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
- **Benutzer-Verwaltung**: Registrierung, Profil, Passwort-vergessen mit zeitlich begrenztem Reset
- **Kommentare**: mit Rate-Limiting pro IP (inkl. `X-Forwarded-For`) und Längenbegrenzung
- **Templates**: anpassbare HTML-Templates pro Bereich, CSRF-`{CSRF}`- und `{CSRF}`-Platzhalter automatisch ersetzt
- **Archiv**: durchsuchbares News-Archiv
- **Mail**: Registrierungs- und Passwort-Reset-Mails via `msmtp` → Mailpit (Docker) oder beliebigem SMTP-Relay

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

- `install.php` wird nach erstem Lauf durch Lockfile (`pninc/install.lock`) gesperrt.
- `update.php` und `convert.php` erfordern Admin-Login (`canwriteconfig = 'YES'`).
- Kein hardcodierter `powernews/powernews`-Default-Account mehr.

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

## Migration von PowerNews ≤ 2.x auf 3.0

1. Backup der Datenbank + Dateien anlegen.
2. Dateien durch die 3.0-Version ersetzen.
3. `http://<host>/update.php` als eingeloggter Admin aufrufen (neue Auth-Gate-Prüfung greift automatisch).
4. Bestehende User mit Legacy-Passwort werden beim ersten Login transparent auf bcrypt hochgehasht.
5. `update.php` nach dem Update löschen.

Details siehe [`changelog.html`](changelog.html).

---

## Dokumentation

- Audit-Report: `docs/2026-04-23-Userbereichs-bugs.md`
- Improvements: `docs/2026-04-23-Userbereichs-improvements.md` (Alias `…-verbesserungen.md`)
- Testabdeckung: `docs/2026-04-23-Userbereichs-test-coverage.md` (Alias `…-testabdeckung.md`)
- Implementierungsplan für die Bugfixes: `docs/superpowers/plans/2026-04-23-userbereich-bugfixes.md`

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
