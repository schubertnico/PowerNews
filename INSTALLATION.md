# PowerNews – Installation und Update

Diese Anleitung beschreibt die Installation mit dem Web-Installer (auch auf
Shared Hosting ohne Shell), die Installation per Kommandozeile, die
Konfiguration und das Update von 3.11 auf 3.12.

## Inhalt

1. [Systemanforderungen](#systemanforderungen)
2. [Installation mit dem Web-Installer](#installation-mit-dem-web-installer)
3. [Sperre des Installers](#sperre-des-installers)
4. [Konfiguration](#konfiguration)
5. [Installation ohne Web-Installer (Kommandozeile)](#installation-ohne-web-installer-kommandozeile)
6. [Update von 3.11 auf 3.12](#update-von-311-auf-312)
7. [Webserver: Apache und nginx](#webserver-apache-und-nginx)
8. [Docker-Entwicklungsumgebung](#docker-entwicklungsumgebung)
9. [Release-Archiv erstellen](#release-archiv-erstellen)

---

## Systemanforderungen

- PHP 8.4 oder neuer mit den Erweiterungen `mysqli` und `mbstring`
- MySQL 8.0 oder neuer bzw. MariaDB 10.3 oder neuer
- Apache 2.4 (mit `mod_headers`) oder nginx, siehe [Webserver](#webserver-apache-und-nginx)
- eine **leere** Datenbank samt Benutzer mit den Rechten CREATE, DROP, SELECT,
  INSERT, UPDATE, DELETE, INDEX und ALTER
- Schreibrechte für `logs/`; damit der Installer `pninc/config.local.php`
  selbst anlegen kann, auch für `pninc/` (sonst laden Sie die Datei von Hand hoch)
- für den Mailversand die PHP-Funktion `mail()` des Hosters
- empfohlen: HTTPS, damit die Zugangsdaten verschlüsselt übertragen werden

---

## Installation mit dem Web-Installer

Der Web-Installer legt die Tabellen aus `powernews.sql` an, erstellt Ihr
Administrator-Konto, trägt Seiten-URL und Absenderadresse ein und speichert die
Zugangsdaten in `pninc/config.local.php`. Danach sperrt er sich selbst.

### Schritt für Schritt (Shared Hosting)

1. **Dateien hochladen.** Den Inhalt des Release-Archivs per FTP/SFTP in das
   Zielverzeichnis kopieren, z. B. `/news/`. Auch die versteckten
   `.htaccess`-Dateien übertragen (im FTP-Programm „versteckte Dateien anzeigen“
   einschalten) – sie schützen `pninc/`, `logs/` und die Zugangsdaten.
2. **Rechte setzen.** `logs/` muss für PHP beschreibbar sein (je nach Hoster
   `755`, `775` oder `777`). Ist auch `pninc/` beschreibbar, legt der Installer
   `pninc/config.local.php` selbst an.
3. **Datenbank anlegen.** Im Kundenmenü des Hosters eine neue MySQL- bzw.
   MariaDB-Datenbank erstellen (Zeichensatz `utf8mb4`, falls wählbar) und Server,
   Port, Datenbankname, Benutzer und Passwort notieren.
4. **Installer aufrufen:** `https://ihre-domain.de/news/install.php`
5. **Assistent durchlaufen:**

   | Schritt | Inhalt |
   |---------|--------|
   | 1 Systemprüfung | PHP-Version, `mysqli`, `mbstring`, Lesbarkeit von `powernews.sql`, Schreibrechte für `logs/` und `pninc/`, Hinweis auf HTTPS. Rote Punkte müssen behoben werden, gelbe sind Hinweise. |
   | 2 Datenbank | Server (meist `localhost`, bei manchen Hostern ein eigener Name wie `sql.example.org`), Port (`3306`), Name der Datenbank, Benutzer, Passwort. Die Verbindung wird sofort geprüft, ebenso die Version: MySQL ab 8.0, MariaDB ab 10.3. Fehler erscheinen am betroffenen Feld, ohne Zugangsdaten zu wiederholen. Enthält die Datenbank schon `pn_`-Tabellen, bricht der Installer ab, statt etwas zu überschreiben. |
   | 3 Website | Adresse der Website (aus der aktuellen Anfrage vorbelegt – bitte prüfen, sie steht in den Links der E-Mails), Absenderadresse für E-Mails (am besten ein Postfach Ihrer eigenen Domain) und Sprache (Deutsch Du-Form, Deutsch Sie-Form, English). |
   | 4 Administrator | Nickname (3–30 Zeichen: Buchstaben einschließlich Umlauten, Ziffern sowie `. _ -`), E-Mail-Adresse, Passwort (8 bis 72 Zeichen) mit Wiederholung. |
   | 5 Abschluss | Zusammenfassung mit „Ändern“-Links. „Jetzt installieren“ legt Tabellen und Administrator an, schreibt `pninc/config.local.php` und die Sperrdatei. |

6. **`install.php` löschen.** Der Installer ist danach gesperrt, gehört aber nicht
   auf eine laufende Website. Das Verzeichnis `pninc/installer/` bleibt: Es wird
   für `update.php` gebraucht und ist von außen nicht abrufbar.
7. **Anmelden** im Adminbereich `https://ihre-domain.de/news/pnadmin/` mit
   **Nickname und Passwort**. Unter „Konfiguration“ stellen Sie Kommentare,
   News-Einsendungen, Datumsformat usw. ein.

### Wenn `pninc/` nicht beschreibbar ist

Dann bietet die Abschlussseite „config.local.php herunterladen“ an; der Inhalt
steht zusätzlich zum Kopieren darunter. Laden Sie die Datei per FTP nach
`pninc/` (neben `config.inc.php`), setzen Sie die Rechte auf `640` und laden Sie
die Abschlussseite neu. Bis dahin erreicht PowerNews die Datenbank nicht. Die
Sperrdatei legt der Installer in diesem Fall in `logs/` an.

### Sicherheitshinweise

- Solange PowerNews nicht eingerichtet ist, kann jeder den Installer aufrufen,
  der die Adresse kennt. Deshalb: hochladen, installieren, `install.php` löschen –
  am besten in einem Zug.
- Nach Möglichkeit über HTTPS installieren.
- Zugangsdaten stehen nur in `pninc/config.local.php`. Der Installer schreibt sie
  weder in Protokolle noch in Fehlermeldungen; das Administrator-Passwort liegt
  auch in der Sitzung nur als Hash vor.
- Der Installer führt aus `powernews.sql` ausschließlich `CREATE TABLE` und
  `INSERT INTO` für `pn_`-Tabellen aus. Er verwirft niemals Tabellen – nur wenn
  die Installation mittendrin scheitert, entfernt er die Tabellen, die er in
  diesem Lauf selbst angelegt hat.

---

## Sperre des Installers

`install.php` verweigert jeden Aufruf mit HTTP 403 und dem Hinweis, die Datei zu
löschen, sobald eine dieser Bedingungen gilt:

- eine Sperrdatei existiert: `pninc/install.lock` oder `logs/install.lock`
  (legt der Installer am Ende an, ebenso `update.php`),
- `pninc/config.local.php` existiert,
- die konfigurierte Datenbank enthält eine Zeile in `pn_config`,
- per Umgebungsvariablen ist eine Datenbank eingerichtet, die gerade nicht
  erreichbar ist – ein Datenbankausfall öffnet den Installer also nicht wieder.

Kann weder in `pninc/` noch in `logs/` eine Sperrdatei angelegt werden, beginnt
die Installation gar nicht erst.

**Wirklich neu installieren:** Datensicherung anlegen, `pninc/config.local.php`
und die Sperrdatei löschen, eine leere Datenbank verwenden (oder die
`pn_`-Tabellen selbst entfernen) und `install.php` erneut hochladen.

---

## Konfiguration

### Rangfolge der Zugangsdaten

`pninc/config.inc.php` ermittelt Datenbankzugang und Sprache in dieser
Reihenfolge (höchste zuerst):

1. `pninc/config.local.php` – legt der Web-Installer an
2. Umgebungsvariablen `PN_DB_HOST`, `PN_DB_PORT`, `PN_DB_USER`, `PN_DB_PASS`,
   `PN_DB_NAME` (Docker, `SetEnv`, PHP-FPM)
3. Vorgaben: `localhost`, Port `3306`, Benutzer `root` ohne Passwort, Datenbank
   `powernews`, Sprache `german-du`

Tragen Sie Zugangsdaten nicht in `config.inc.php` ein – die Datei wird bei jedem
Update überschrieben. `config.local.php` bleibt dagegen erhalten.

### `pninc/config.local.php` von Hand anlegen

```php
<?php

declare(strict_types=1);

return [
    'db' => [
        'host' => 'sql.example.org',
        'port' => 3306,
        'user' => 'news_user',
        'password' => 'Ihr-Passwort',
        'database' => 'news_db',
    ],
    // german-du, german-sie oder english
    'language' => 'german-du',
];
```

Die Datei gibt nur ein Array zurück. Fehlende Schlüssel übernehmen den Wert aus
den Umgebungsvariablen bzw. Vorgaben. Rechte `640` setzen; die Datei ist per
`pninc/.htaccess` gesperrt.

### Umgebungsvariablen

| Variable | Bedeutung | Vorgabe |
|----------|-----------|---------|
| `PN_DB_HOST` | Datenbankserver | `localhost` |
| `PN_DB_PORT` | Port | `3306` |
| `PN_DB_USER` | Benutzer | `root` |
| `PN_DB_PASS` | Passwort | (leer) |
| `PN_DB_NAME` | Datenbank | `powernews` |

---

## Installation ohne Web-Installer (Kommandozeile)

```bash
# 1. Schema einspielen (bricht ab, wenn schon pn_-Tabellen existieren)
mysql -u news_user -p news_db < powernews.sql

# 2. Passwort-Hash für den Administrator erzeugen
php -r 'echo password_hash("Ihr-Passwort", PASSWORD_DEFAULT), PHP_EOL;'
```

```sql
-- 3. Administrator mit allen Rechten, Seiten-URL und Absender
INSERT INTO pn_users (nickname, email, password, registered, showemail, status)
VALUES ('Redaktion', 'redaktion@example.org', '<Hash aus Schritt 2>', UNIX_TIMESTAMP(), 'NO', 'Activated');
INSERT INTO pn_permissions (userid, canreadtemplates, canwritetemplates, canreadconfig, canwriteconfig,
    canreadusers, canwriteusers, canreadpermissions, canwritepermissions, canreadcategories,
    canwritecategories, canreadnews, canwritenews, canreadcomments, canwritecomments)
VALUES (LAST_INSERT_ID(), 'YES', 'YES', 'YES', 'YES', 'YES', 'YES', 'YES', 'YES', 'YES', 'YES', 'YES', 'YES', 'YES', 'YES');
UPDATE pn_config SET url = 'https://www.example.org/news', email = 'noreply@example.org';
```

4. `pninc/config.local.php` anlegen (siehe [Konfiguration](#konfiguration)).
5. `install.php` löschen.

---

## Update von 3.11 auf 3.12

1. **Datensicherung** von Datenbank und Dateien anlegen.
2. **Zugangsdaten sichern.** Haben Sie sie in 3.11 direkt in
   `pninc/config.inc.php` eingetragen, legen Sie *vor* dem Hochladen
   `pninc/config.local.php` mit denselben Werten an (siehe
   [Konfiguration](#konfiguration)) – die neue `config.inc.php` enthält nur noch
   Vorgaben. Umgebungsvariablen gelten unverändert weiter.
3. **Dateien hochladen** und dabei vorhandene überschreiben – `install.php`
   weglassen, für ein Update wird sie nicht gebraucht. Vom Server löschen:
   `convert.php` und `pn_update.sql` (entfallen in 3.12). Eigene Anpassungen an
   `header.inc.php` und `footer.inc.php` vorher sichern.
4. **Im Adminbereich anmelden** (Recht „Konfiguration schreiben“) und
   `https://ihre-domain.de/news/update.php` aufrufen. Die Seite zeigt, welche
   Schritte nötig sind, und führt sie mit „Update ausführen“ aus:
   - fehlende Tabellen aus `powernews.sql` anlegen (z. B. `pn_login_attempts`),
   - die mit 3.11 ausgelieferten Testvorlagen „ftghgf“, „dsfs“ und „dfgdfg“
     entfernen – nicht, wenn eine davon als Standard-Template eingestellt ist,
   - verwaiste Einträge in `pn_permissions` löschen,
   - den Web-Installer per Sperrdatei sperren.

   Jeder Schritt prüft selbst, ob er nötig ist; ein zweiter Aufruf ändert nichts.
5. **Konfiguration prüfen:** Im Adminbereich unter „Konfiguration“ URL und
   E-Mail-Adresse kontrollieren. 3.11 lieferte `http://www.powerscripts.org`
   und `daemon@powerscripts.org` als Vorgabe aus.

**Installationen von vor April 2026** (ohne Tabelle `pn_sessions`): Die Anmeldung
im Adminbereich braucht diese Tabelle. Spielen Sie die beiden Anweisungen
`CREATE TABLE pn_sessions` und `CREATE TABLE pn_login_attempts` aus
`powernews.sql` vorher per phpMyAdmin ein; danach wie oben.

**Updates von 2.x** werden nicht mehr unterstützt.

---

## Webserver: Apache und nginx

**Apache:** Die mitgelieferten `.htaccess`-Dateien sperren `pninc/` (samt
`config.local.php`, `install.lock` und `pninc/installer/`), `logs/`,
`pnadmin/lang/`, `*.sql`, `*.md` und `*.inc.php`. Sie wirken auch, wenn PowerNews
in einem Unterverzeichnis liegt. Voraussetzung: `AllowOverride All` (bei Hostern
üblich).

**nginx** wertet keine `.htaccess`-Dateien aus. Ergänzen Sie im `server`-Block
(Pfad anpassen, wenn PowerNews in einem Unterverzeichnis liegt):

```nginx
location ~ ^/(pninc|logs|pnadmin/lang)/ { deny all; }
location ~ \.(sql|md|lock|log)$         { deny all; }
location ~ \.inc\.php$                  { deny all; }
```

---

## Docker-Entwicklungsumgebung

```bash
cd .docker
docker compose up -d
```

Beim ersten Start mit leerem Datenbank-Volume spielt der Container
`powernews.sql` und danach `.docker/dev-seed.sql` ein. Der Dev-Seed legt einen
Administrator mit bekanntem Passwort an und setzt URL und Absender auf den Stack:

| | |
|---|---|
| Adminbereich | http://localhost:8087/pnadmin/ |
| Nickname / Passwort | `admin` / `powernews-dev` |
| Mailpit | http://localhost:8033/ |

Der Dev-Seed ist nur für die Entwicklung gedacht und nicht im Release-Archiv.
Weil die per `PN_DB_*` konfigurierte Datenbank eingerichtet ist, ist
`install.php` im Stack gesperrt (HTTP 403). Die Integrationstests setzen die
Datenbank zurück; danach fehlt der Dev-Admin – `docker compose down -v` und ein
Neustart spielen ihn wieder ein.

---

## Release-Archiv erstellen

```bash
git archive --format=zip --prefix=powernews/ -o powernews.zip HEAD
git archive --format=tar HEAD | tar -t      # Inhalt prüfen
```

`.gitattributes` schließt Entwicklungsdateien aus: `tests/`, `docs/`, `.docker/`
(mit Dev-Seed), `todos/`, `.github/`, `phpunit.xml`, `phpstan.neon`, `psalm.xml`,
`phpmd*`, `rector.php`, `infection.json5`, `.php-cs-fixer.php`, `composer.json`,
`composer.lock`, `.gitignore` und `.gitattributes`. Textdateien landen mit
LF-Zeilenenden im Archiv.
