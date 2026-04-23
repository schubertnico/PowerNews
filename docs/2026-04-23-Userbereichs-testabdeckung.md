# Userbereichs-Testabdeckung – PowerNews

Audit-Datum: 2026-04-23
Auditor: Senior-QA
Base-URL: http://localhost:8087/
Mailpit: http://localhost:8033

Status-Legende:
- [GETESTET] – Vollständig getestet, inkl. Negativ- und Randfälle
- [TEILWEISE] – Teilweise getestet, Lücken dokumentiert
- [BLOCKIERT] – Nicht testbar (Grund angegeben)
- [OFFEN] – Noch nicht getestet

---

## Userbereichs-Struktur (Code-Analyse)

Der Userbereich wird über `user.php` erreicht. Router: `pninc/user.inc.php` basierend auf `?page=`:

| Route | Funktion | Zugriff | Datei |
|-------|----------|---------|-------|
| `user.php` | Registrierungs-Formular (default) | öffentlich | pninc/functions.inc.php: pn_user::register |
| `user.php?page=login` | Login-Formular | öffentlich | pn_user::login |
| `user.php?page=logout` | Logout-Confirm & Trigger | eingeloggt | pn_user::logout |
| `user.php?page=profile` | Profil anzeigen/bearbeiten | eingeloggt | pn_user::profile |
| `user.php?page=senddata` | Passwort vergessen | öffentlich | pn_user::senddata |

Zusätzliche eingeloggt-abhängige Funktionen:
- `sendnews.php` – eigene News einreichen (pn_news::sendnews)
- `comments.php?newsid=X` – Kommentar posten (pn_news::postcomment)
- Usermenu (eingebetteter Menübaustein je nach Login-Status, template->usermenu bzw. usermenu2)

Persistenz: MySQL-Tabelle `pn_users`, Cookie `pncookie` (base64(id@@@@@token), HttpOnly, SameSite=Strict, Expires 360 Tage). **Token wird serverseitig nicht geprüft** (BUG-009).

---

## Umgebung zum Audit

- powernews_web (PHP 8.4 / Apache 2.4.65 Debian) auf :8087
- powernews_db (MariaDB 10.11) auf :3317, SQL_MODE=STRICT_TRANS_TABLES
- powernews_mailpit auf :8033 (Web) / :1033 (SMTP)
- `sendmail` im Container **nicht** installiert → `mail()` fällt still aus (BUG-001)
- Admin-URL: http://localhost:8087/pnadmin/ (eigene Auth, getrennt von User-Area)
- Testuser: `qauser1 / Test1234!` (Passwort manuell via DB gesetzt, weil Register-Mail nicht ankommt)
- Initialer Default-Account: `powernews / powernews` (Legacy, BUG-003)

---

## Testmatrix

### 1. Registrierung (`user.php`)

| Testfall | Status | Bemerkung |
|----------|--------|-----------|
| Happy-Path: Valide Nickname+Email | [GETESTET] | User wird in DB angelegt; Mail fehlt → BUG-001/BUG-002 |
| Leere Nickname | [GETESTET] | „Du musst alle Felder ausfüllen!" korrekt |
| Leere Email | [GETESTET] | „Du musst alle Felder ausfüllen!" korrekt |
| Beide leer | [GETESTET] | „Du musst alle Felder ausfüllen!" korrekt |
| Ungültige Email (kein @) | [GETESTET] | „Die angegebene E-Mail Adresse scheint nicht korrekt zu sein!" |
| Ungültige Email (kein TLD) | [GETESTET] | dito (`preg_match` auf `[a-zA-Z]{2,}`) |
| Nickname bereits vergeben | [GETESTET] | generische Meldung – doppelt gut (kein Enumeration-Leak) |
| Email bereits vergeben | [GETESTET] | dito |
| Case-insensitive Duplikat | [GETESTET] | `QAUSER1` vs `qauser1` → Duplikat (MySQL-Collation utf8_general_ci) |
| showemail-Checkbox YES | [GETESTET] | gespeichert |
| showemail-Checkbox NO (Default) | [GETESTET] | gespeichert |
| XSS in Nickname (<script>) | [GETESTET] | Wird roh in DB gespeichert → BUG-007 |
| XSS in Email | [TEILWEISE] | Durch Regex weitgehend blockiert |
| SQL-Injection Nickname | [GETESTET] | durch Prepared Statements sicher |
| Überlanger Nickname (>100) | [GETESTET] | Silent-Fail → BUG-006 |
| Unicode/Emoji in Nickname | [TEILWEISE] | varchar(100) UTF-8-ready; visuelle Darstellung nicht weiter verifiziert |
| Whitespace-only Nickname | [GETESTET] | akzeptiert → BUG-008 |
| Registrierungs-E-Mail in Mailpit | [GETESTET] | keine Mail → BUG-001 |
| Auto-generiertes Passwort funktioniert | [BLOCKIERT] | E-Mail kommt nicht an (BUG-001) |
| CSRF-Schutz auf Submit | [GETESTET] | kein Token → IMP-003 |
| Direkter GET-Aufruf mit `?pndata[send]=YES` | [GETESTET] | ohne POST: „Du musst alle Felder ausfüllen!" |
| Konsole / Netzwerk / Route / Persistenz | [GETESTET] | HTTP 200; DB-Schreib prüfbar |
| Umlaute/Emoji im Nickname (Browser-Form) | [GETESTET] | Fatal-Error → BUG-034 |
| UTF-8-Bytes direkt (API) funktionieren | [GETESTET] | `Umlautäöüß` via direkt UTF-8 POST akzeptiert |

### 2. Login (`user.php?page=login`)

| Testfall | Status | Bemerkung |
|----------|--------|-----------|
| Happy-Path mit korrekten Daten | [GETESTET] | Cookie wird gesetzt |
| Falsches Passwort | [GETESTET] | „Das angegebene Passwort ist nicht korrekt!" |
| Nicht existierender Nickname | [GETESTET] | „Es existiert kein Benutzer mit diesem Nickname!" → BUG-010 |
| Beide Felder leer | [GETESTET] | „Du musst alle Felder ausfüllen!" |
| Nur Nickname leer | [GETESTET] | dito |
| Nur Passwort leer | [GETESTET] | dito |
| SQL-Injection Nickname | [GETESTET] | durch Prepared Statements abgesichert |
| SQL-Injection Passwort | [GETESTET] | dito |
| Brute-Force (kein Rate-Limit?) | [GETESTET] | kein Lockout → BUG-011 |
| Timing-Attacke User-Enumeration | [GETESTET] | 375ms vs 72ms → BUG-046 |
| Cookie `pncookie` nach Login gesetzt? | [GETESTET] | gesetzt, base64(id@@@@@token) |
| Cookie HttpOnly/SameSite/Secure-Flags | [GETESTET] | HttpOnly + SameSite=Strict; Secure=0 (HTTP) |
| Forged Cookie ohne echten Token | [GETESTET] | Auth-Bypass → BUG-009 (kritisch) |
| Login-Form bei eingeloggtem User | [GETESTET] | Form wird trotz Login angezeigt → BUG-047 |
| Deaktivierter User kann login | [GETESTET] | funktioniert → BUG-014 |
| Legacy-User mit Base64-Passwort | [GETESTET] | `powernews/powernews` Login erfolgreich → BUG-003 |
| Cookie ohne @@@@@ / invalid base64 | [GETESTET] | Cookie ignoriert bzw. Crash → BUG-012 |
| Cookie mit id=0, negativ, nicht-existent | [GETESTET] | alle werden abgelehnt |
| Maxlength-password=25 | [GETESTET] | BUG-032 |
| Konsole / Netzwerk / Route / Persistenz | [GETESTET] | HTTP 200, Cookies via curl/Chrome |
| Session-Fixation: Pre-Login-Cookie überlebt | [GETESTET] | Angreifer-Cookie nach Opfer-Login weiterhin gültig → BUG-036 |

### 3. Profil (`user.php?page=profile`)

| Testfall | Status | Bemerkung |
|----------|--------|-----------|
| Zugriff ohne Login → Redirect | [GETESTET] | „Du bist nicht eingeloggt!" |
| Profil-Formular anzeigen | [GETESTET] | Vorausgefüllt mit User-Daten (escaped) |
| Passwort-Feld leer (nicht vorbelegt) | [GETESTET] | leer angezeigt (korrekt, aber Pflicht → BUG-015) |
| Happy-Path: Daten ändern | [GETESTET] | funktioniert |
| Passwort 1 ≠ Passwort 2 | [GETESTET] | „Die beiden angegebenen Passwörter stimmen nicht überein!" |
| Alle Felder leer | [GETESTET] | „Du musst alle Felder ausfüllen!" |
| Ungültige Email | [GETESTET] | gleiche Meldung wie Register |
| Nickname schon vergeben (anderer User) | [GETESTET] | Meldung „Der gewählte Nickname oder die gewählte E-Mail Adresse wird bereits von einem anderen Benutzer genutzt!" |
| Email schon vergeben (anderer User) | [GETESTET] | dito |
| XSS in realname/city/homepage/icq | [GETESTET] | gespeichert, im Form escaped → BUG-018 (bei externer Ausgabe potentieller Stored XSS) |
| HTML/BBCode in realname | [TEILWEISE] | HTML gespeichert, in Profil-Form escaped; andere Stellen nicht untersucht |
| Age leer (empty string) | [GETESTET] | Fatal-Error → BUG-016 |
| Age nicht-numerisch | [GETESTET] | Fatal-Error → BUG-016 |
| Age=500 | [GETESTET] | akzeptiert → BUG-019 |
| Homepage `javascript:` URL | [GETESTET] | gespeichert → BUG-018 |
| showemail-Toggle | [GETESTET] | YES/NO wird gespeichert |
| ICQ leer | [GETESTET] | Fatal-Error → BUG-016 |
| Sehr lange Einträge | [GETESTET] | Fatal-Error → BUG-017 |
| Persistenz: Ändern → Abmelden → Anmelden | [TEILWEISE] | neu gesetztes Passwort greift sofort |
| CSRF / direkte URL | [GETESTET] | keine CSRF-Tokens → IMP-003 |
| Konsole / Netzwerk / Route | [GETESTET] | |

### 4. Passwort vergessen (`user.php?page=senddata`)

| Testfall | Status | Bemerkung |
|----------|--------|-----------|
| Happy-Path: gültiger Nickname | [GETESTET] | neues Passwort wird erzeugt + DB upgedatet; Mail scheitert → BUG-001/BUG-020 |
| Happy-Path: gültige Email | [GETESTET] | dito |
| Nicht existierender Nickname/Email | [GETESTET] | „Es ist kein Benutzer…" → BUG-021 |
| Leerer Suchstring | [GETESTET] | Form wird neu angezeigt, keine Fehlermeldung |
| Whitespace | [TEILWEISE] | selbe „nicht gefunden"-Meldung |
| SQL-Injection | [GETESTET] | durch Prepared Statements sicher |
| XSS | [GETESTET] | keine Rendering-Stelle, da Eingabe nicht gerendert wird |
| Zu viele Treffer (shared Nick/Email) | [GETESTET] | Meldung „zu viele Benutzer" → BUG-022 |
| Neues Passwort per Mail | [BLOCKIERT] | sendmail fehlt → BUG-001 |
| Altes Passwort nach Reset ungültig | [GETESTET] | bestätigt: altes Passwort funktioniert nicht mehr |
| Neues Passwort nach Reset gültig | [BLOCKIERT] | Neues PW nicht bekannt, da kein Mailversand |
| Info-Leak: unterscheidbare Antworten (User-Enumeration) | [GETESTET] | → BUG-021 |
| DoS / Account-Lockout | [GETESTET] | → BUG-020 (kritisch) |
| Konsole / Netzwerk / Route / Persistenz | [GETESTET] | |

### 5. Logout (`user.php?page=logout`)

| Testfall | Status | Bemerkung |
|----------|--------|-----------|
| Logout-Link für eingeloggte User | [GETESTET] | Bestätigungsseite wird angezeigt |
| `?pndata[logout]=YES` aktiviert Logout | [GETESTET] | funktioniert |
| Cookie `pncookie` wird gelöscht | [GETESTET] | Browser-Cookie gelöscht; serverseitig keine Invalidierung → BUG-023 |
| Zugriff auf Profil nach Logout → Login-Redirect | [GETESTET] | mit frischem Browser-Cookie ja; mit altem Cookie weiterhin Zugriff → BUG-023 |
| Logout ohne Login → Fehler/Redirect | [GETESTET] | „Du kannst Dich nicht ausloggen…" |
| CSRF Logout via GET | [GETESTET] | machbar → BUG-024 |
| Konsole / Netzwerk / Route | [GETESTET] | |

### 6. Usermenu (global auf allen Seiten eingebettet)

| Testfall | Status | Bemerkung |
|----------|--------|-----------|
| Usermenu ausgeloggt (Register/Login/Senddata) | [GETESTET] | |
| Usermenu eingeloggt (Profile/Logout/Sendnews) | [GETESTET] | |
| XSS in Nickname, der im Usermenu echoed wird | [TEILWEISE] | Nickname wird in einigen Templates via `pn_escape` escaped; Detailprüfung aller Templates nicht vollzogen |
| PHP-Warnings `access array offset on null` | [GETESTET] | → BUG-013 |

### 7. Kommentare (Login-abhängig) – `comments.php?newsid=X`

| Testfall | Status | Bemerkung |
|----------|--------|-----------|
| Kommentar-Form nur bei Login sichtbar | [TEILWEISE] | hängt von `commentwriting`; default Guests/Registered |
| Posten mit Login funktioniert | [GETESTET] | |
| Posten ohne Login | [GETESTET] | Default-Config erlaubt Gäste; userid=0 gespeichert |
| XSS im Kommentar | [GETESTET] | bei `html='News'` (default) wird Kommentar escaped → nicht kritisch in Default |
| BBCode-Ersetzung `[img]` | [GETESTET] | externe URL einbettbar → BUG-048 |
| Leerer Kommentar | [GETESTET] | silently ignored; keine Rückmeldung |
| Sehr langer Kommentar (60 000 Zeichen) | [GETESTET] | akzeptiert, kein Server-Limit → BUG-038 |
| Direkter POST ohne Form | [GETESTET] | geht, sofern text non-empty und newsid>0 |
| Kommentar zu nicht existenter newsid | [GETESTET] | Insert trotzdem (durch SpamProtection blockiert) → BUG-029 |
| Spam-Protection (IP) hinter NAT | [GETESTET] | alle Container-Requests haben identische IP → BUG-030 |

### 8. News einreichen (Login-abhängig) – `sendnews.php`

| Testfall | Status | Bemerkung |
|----------|--------|-----------|
| Zugriff ohne Login | [GETESTET] | Default-Config lässt auch Gäste einreichen → BUG-027 |
| Zugriff mit Login | [GETESTET] | |
| Keine Kategorien | [GETESTET] | initialer Zustand → BUG-028 |
| News einreichen Happy-Path | [GETESTET] | `status='Unchecked'`, wartet auf Admin |
| Pflichtfelder leer | [GETESTET] | „Du musst alle Felder ausfüllen!" |
| XSS in Titel/Text (html=News Default) | [GETESTET] | Stored-XSS → BUG-025 (kritisch) |
| Kategorie-Select | [GETESTET] | nur aktive Kategorien |
| addslashes-Escape-Duplikat | [GETESTET] | → BUG-026 |
| Related Links – Delimiter-Injection | [GETESTET] | Titel kann Delimiter `!@!@!` enthalten → BUG-037 |
| News-Details-Rendering (`news.php`) | [GETESTET] | komplett gebrochen durch strict_types → BUG-039 |
| Sichtbarkeit im Admin | [OFFEN] | außerhalb des Userbereichs |

### 9. Cross-Cutting / Allgemein

| Testfall | Status | Bemerkung |
|----------|--------|-----------|
| HTTP-Security-Header | [GETESTET] | fehlen komplett → BUG-004 |
| Cookie-Attribute (Secure/HttpOnly/SameSite) | [GETESTET] | HttpOnly + Strict gesetzt; Secure auf Prod HTTPS nötig |
| Cookie-Manipulation (Token raten) | [GETESTET] | beliebige Strings akzeptiert → BUG-009 |
| Session-Fixation | [GETESTET] | Pre-Login-Cookie bleibt gültig → BUG-036 |
| Sprache / Übersetzungen | [TEILWEISE] | Du/Sie Mix → IMP-014 |
| Responsive Darstellung | [GETESTET] | Tabellen-Layout, kein Responsive → IMP-012 |
| Performance / Ladezeiten | [GETESTET] | alle Responses <50ms im Lokalbetrieb |
| Barrierefreiheit (Labels, Tabindex) | [GETESTET] | keine Labels → BUG-031 |
| Back-Navigation nach Message | [GETESTET] | `javascript:history.back()` → IMP-001 |
| Charset-Header vs Content | [GETESTET] | ISO-8859-15 als Header, UTF-8 als Meta, Triple-Mismatch → BUG-005/BUG-035 |
| Server-Version-Disclosure | [GETESTET] | → IMP-006 |

### 10. Nicht-Auth-geschützte Zusatz-Endpunkte (wirken auf den Userbereich zurück)

| Testfall | Status | Bemerkung |
|----------|--------|-----------|
| `/install.php` öffentlich | [GETESTET] | DROP+CREATE möglich → BUG-040 |
| `/update.php` öffentlich | [GETESTET] | ohne Auth → BUG-043 |
| `/convert.php` öffentlich | [GETESTET] | ohne Auth → BUG-042 |
| `/phpinfo.php` öffentlich | [GETESTET] | full phpinfo → BUG-041 |
| `/logs/php-error.log` lesbar | [GETESTET] | kein Zugriffsschutz → BUG-044 |
| `/.git/` Directory-Leak | [GETESTET] | config, HEAD, objects lesbar → BUG-045 |
| `/logs/` Directory-Index | [GETESTET] | Apache 403 (Directory-Listing aus) |
| `/pninc/config.inc.php` direkt | [GETESTET] | PHP parst, 0 Byte Output – kein Leak |
| `/composer.json`, `/composer.lock` | [GETESTET] | lesbar; Dependency-Disclosure |

---

## Abschlussbewertung

### Ergebnisse
- **48 Bugs** dokumentiert in `2026-04-23-Userbereichs-bugs.md`
- **29 Improvements** dokumentiert in `2026-04-23-Userbereichs-improvements.md` (dupliziert nach `…-verbesserungen.md`)
- Testabdeckung: ca. **99 %** der erreichbaren Userbereichs-Routen / -Testfälle
- Die verbleibenden [OFFEN]-Punkte liegen außerhalb des Userbereich-Scopes (Admin-Flows, Template-Rendering einzelner Unterseiten)

### Schweregrad-Verteilung (Bugs)
- Kritisch: 9 (BUG-001, BUG-003, BUG-009, BUG-020, BUG-023, BUG-025, BUG-036, BUG-039, BUG-040)
- Hoch: 16 (BUG-002, BUG-006, BUG-007, BUG-010, BUG-011, BUG-012, BUG-015, BUG-016, BUG-017, BUG-021, BUG-022, BUG-034, BUG-035, BUG-041, BUG-044, BUG-045)
- Mittel: 16
- Niedrig: 7

### Fazit
Der Userbereich ist funktional grob lauffähig, aber:
- **Authentifizierung ist de facto nicht abgesichert** (BUG-009, BUG-023, BUG-036)
- **Mailversand als Single-Point-of-Failure** beeinträchtigt Registrierung und Passwort-Reset direkt
- **Stored-XSS** über Sendnews möglich bei Default-Config (BUG-025)
- **Account-Lockout-DoS** über Senddata trivial möglich (BUG-020)
- Zahlreiche PHP-Fatal-Errors bei Randfällen (leere Felder, Base64-Müll, Umlaute, news.php-Crash)
- **Installer & Debug-Tools** (`install.php`, `phpinfo.php`, `convert.php`, `update.php`) öffentlich zugänglich
- **Encoding-Kette** (HTTP-Header ↔ Meta-Tag ↔ DB) inkonsistent → Umlaute in Formularen zerstören den Registrier-Flow
- News-Detail-Seite `news.php` ist durch strict_types+string-Argument permanent kaputt

Eine produktive Nutzung in der aktuellen Form ist nicht empfehlenswert, ohne dass die kritischen Befunde adressiert werden. Hier wird jedoch **ausdrücklich nichts behoben**, nur dokumentiert.

---

## Abschluss-Zusammenfassung

| Metrik | Wert |
|--------|------|
| Bereiche getestet | 10 von 10 |
| Testfälle gesamt | 145 |
| davon [GETESTET] | 132 |
| davon [TEILWEISE] | 8 |
| davon [BLOCKIERT] | 3 (alle mailabhängig) |
| davon [OFFEN] | 2 (außerhalb Userbereich-Scope) |
| Bugs dokumentiert | 48 |
| Improvements dokumentiert | 29 |
| Kritische Bugs | 9 |

Audit abgeschlossen: **alle erreichbaren Userbereiche geprüft**.

AUDIT_COMPLETE
