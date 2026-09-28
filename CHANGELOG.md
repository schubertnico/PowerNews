# Changelog

## 3.12 (in Vorbereitung)

### Installation und Update

- **Neuer Web-Installer** `install.php` in fünf Schritten: Systemprüfung,
  Datenbank, Website, Administrator, Abschluss – Bootstrap 5, CSRF-Schutz,
  Post/Redirect/Get, Fehlermeldungen direkt am Feld. Details in
  [INSTALLATION.md](INSTALLATION.md).
- Die Installation ist wieder abschließbar: Semikolons in HTML-Entitäten der
  Templates zerschneiden keine SQL-Anweisungen mehr (Befund B01).
- Datenbankzugang, Seiten-URL, Absenderadresse, Sprache und das
  Administrator-Konto (Nickname, E-Mail, Passwort) werden abgefragt – kein
  Konto `admin` mit Zufallspasswort und keine Vorgaben auf powerscripts.org
  mehr (B21, B06 zum Teil).
- MySQL 8.0+ und MariaDB 10.3+ werden getrennt erkannt; MySQL 8 wird nicht
  mehr als „zu alt“ abgewiesen (B27).
- Zugangsdaten und Sprache stehen in `pninc/config.local.php`, die bei Updates
  erhalten bleibt. Rangfolge: `config.local.php` > Umgebungsvariablen `PN_DB_*`
  (neu: `PN_DB_PORT`) > Vorgaben. Ist `pninc/` schreibgeschützt, bietet der
  Installer die Datei zum Herunterladen an.
- Dauerhafte Sperre über Sperrdatei, `config.local.php` oder eine Zeile in
  `pn_config`; gesperrt antwortet der Installer sofort mit HTTP 403. Ohne
  schreibbaren Ort für die Sperrdatei startet die Installation nicht. Tabellen
  werden nie verworfen (B07, B47).
- `powernews.sql`: kein `DROP TABLE`, keine Testvorlagen „ftghgf“, „dsfs“,
  „dfgdfg“ (B18), keine verwaiste Rechtezeile, neutrale Vorgaben für URL und
  Absender, alle Tabellen in `utf8mb4`, Kommentare sauber in UTF-8.
- **`update.php` neu:** zeigt Version, Datenbankserver und Herkunft der
  Zugangsdaten und führt nur die nötigen Schritte aus (fehlende Tabellen,
  Testvorlagen, verwaiste Rechte, Sperre des Installers) – jederzeit
  wiederholbar (B34).
- Docker-Stack: `.docker/dev-seed.sql` legt einen Entwicklungs-Admin an
  (`admin` / `powernews-dev`); nicht im Release.
- Release-Archiv per `.gitattributes` ohne Tests, interne Dokumentation,
  Docker- und Analyse-Konfiguration (B41); ReadMe-Link zeigt auf
  `README.html` (B42).

### Entfernt

- `convert.php` (Import aus NewsPro): seit 3.0 nicht lauffähig, NewsPro wird
  seit rund 20 Jahren nicht mehr gepflegt (B35).
- `pn_update.sql`; Updates von PowerNews 2.x werden nicht mehr unterstützt.
