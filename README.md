# PowerNews v3.0

Ein PHP- und MySQL-basiertes News-System.

## Beschreibung

PowerNews ist ein leichtgewichtiges News-Management-System, das es ermöglicht, News-Artikel, Kategorien, Benutzer und Templates zu verwalten. Es wurde ursprünglich 2001 entwickelt und für PHP 8.4 modernisiert.

## Anforderungen

- PHP 8.4 oder höher
- MariaDB 10.3+ oder MySQL 8.0+
- Apache mit mod_rewrite (optional)

## Installation

### Mit Docker (empfohlen)

1. Repository klonen:
   ```bash
   git clone https://github.com/schubertnico/PowerNews.git
   cd PowerNews
   ```

2. Composer-Abhängigkeiten installieren:
   ```bash
   composer install
   ```

3. Docker-Container starten:
   ```bash
   cd .docker
   docker compose up -d --build
   ```

4. Anwendung aufrufen:
   - Hauptseite: http://localhost:8087/
   - Admin-Panel: http://localhost:8087/pnadmin/
   - Mailpit (E-Mail-Test): http://localhost:8033/

### Manuelle Installation

1. Dateien auf den Webserver hochladen
2. Datenbank erstellen und `powernews.sql` importieren
3. Umgebungsvariablen konfigurieren oder `pninc/config.inc.php` anpassen

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
| Web | 8087 | Apache/PHP-Webserver |
| MariaDB | 3317 | Datenbank |
| Mailpit SMTP | 1033 | E-Mail-Empfang |
| Mailpit Web | 8033 | E-Mail-Webinterface |

## Nutzung

### Admin-Login

Nach der Installation können Sie sich im Admin-Panel anmelden. Der Standard-Admin wird bei der Installation der Datenbank erstellt.

### Funktionen

- **News-Verwaltung**: Erstellen, Bearbeiten und Löschen von News-Artikeln
- **Kategorien**: Organisation von News in Kategorien
- **Benutzer-Verwaltung**: Benutzerregistrierung und -verwaltung
- **Templates**: Anpassbare Ausgabe-Templates
- **Kommentare**: Benutzerkommentare zu News-Artikeln
- **Archiv**: Durchsuchbares News-Archiv

## Entwicklung

### Code-Qualität prüfen

```bash
composer run phpstan
```

### Code modernisieren mit Rector

```bash
# Vorschau der Änderungen
composer run rector:dry

# Änderungen anwenden
composer run rector
```

## Migration auf PHP 8.4

Diese Version enthält umfassende Änderungen zur Modernisierung des Codes:

### Sicherheitsfixes

- **SQL-Injection**: Alle Datenbankabfragen verwenden jetzt Prepared Statements
- **Passwort-Hashing**: Umstellung von Base64 auf bcrypt (`password_hash`/`password_verify`)
- **XSS-Prävention**: Alle Ausgaben werden mit `htmlspecialchars()` escaped
- **Cookie-Sicherheit**: HttpOnly, Secure und SameSite-Flags aktiviert
- **Path-Traversal**: Pfad-Validierung in `convert.php`
- **Credentials**: Datenbankzugangsdaten über Umgebungsvariablen statt Hardcoding

### PHP 8.4-Kompatibilität

- `declare(strict_types=1)` in allen PHP-Dateien
- Deprecated Functions ersetzt:
  - `strftime()` → `DateTime::format()`
  - `srand()` entfernt (nicht mehr benötigt)
  - `rand()` → `random_int()`
- Moderne PHP-Syntax und Type Hints

### Auto-Upgrade für Legacy-Passwörter

Bestehende Benutzer mit Base64-kodierten Passwörtern werden automatisch beim nächsten Login auf bcrypt aktualisiert. Keine manuellen Migrationsschritte erforderlich.

## Lizenz

MIT License - siehe [LICENSE](LICENSE) für Details.

Copyright (c) 2001-2024 PowerScripts

## Repository

https://github.com/schubertnico/PowerNews.git
