# Userbereichs-Bugs – PowerNews

Audit-Datum: 2026-04-23
Auditor: Senior-QA
Anwendung: PowerNews (Newsletter-Script)
Base-URL Admin: http://localhost:8087/
Mailpit: http://localhost:8033
User-Area-Endpoint: /user.php

Hinweis: Dieses Dokument dokumentiert den Audit-Stand vom 2026-04-23. Alle Bugs sind mittlerweile behoben worden (siehe git log 2026-04-23 bis 2026-04-24). Die Einträge bleiben als Audit-Referenz erhalten.

---

## BUG-001: Registrierungs-E-Mail wird nie zugestellt (sendmail fehlt im Container)

- **Bereich:** Registrierung / E-Mail-Versand
- **URL / Route:** `POST /user.php?pndata[send]=YES`
- **Reproduktionsschritte:**
  1. `/user.php` aufrufen
  2. Nickname und gültige E-Mail eintragen, submit
  3. Erfolgsmeldung „Du hast Dich erfolgreich registriert und solltest in einigen Momenten eine E-Mail mit Deinen Benutzerdaten erhalten" erscheint
  4. Mailpit (http://localhost:8033) prüfen → keine Mail
  5. Im Container `docker exec powernews_web ls /usr/sbin/sendmail` → nicht vorhanden
- **Erwartet:** Eine E-Mail mit auto-generiertem Passwort landet im Mailpit. Alternativ: wenn Versand fehlschlägt, Fehlermeldung statt Erfolg und Rückabwicklung des DB-Inserts.
- **Tatsächlich:** DB-Eintrag wird geschrieben, aber PHP `mail()` läuft ins Leere, weil `/usr/sbin/sendmail` im Container fehlt. Kein SMTP-Relay zu Mailpit (`mailpit:1025`) konfiguriert. Nutzer bekommt kein Passwort und kann sich nie einloggen. Account bleibt verwaist in der DB.
- **Fehlerart:** Funktional / Mail / Infrastruktur
- **Schweregrad:** Kritisch
- **Konsole / Stacktrace:** keine PHP-Fehler im Log, `mail()` gibt nur bool zurück (return value wird nicht ausgewertet bei Register)
- **Netzwerkhinweise:** keine SMTP-Verbindungen von `powernews_web` → `powernews_mailpit:1025`
- **Status:** Fixed

---

## BUG-002: Erfolgsmeldung bei fehlgeschlagenem Mailversand (Registrierung)

- **Bereich:** Registrierung / Fehlerbehandlung
- **URL / Route:** `POST /user.php?pndata[send]=YES`
- **Reproduktionsschritte:**
  1. Registrierung durchführen (siehe BUG-001)
  2. Meldung „Du hast Dich erfolgreich registriert..." wird angezeigt, obwohl kein Mailversand stattfand
- **Erwartet:** Der Rückgabewert von `pn_email::registeremail()` sollte geprüft werden. Bei `false` sollte der Nutzer eine Fehlermeldung erhalten und der User-Datensatz nicht persistiert werden oder zumindest markiert werden.
- **Tatsächlich:** `pninc/functions.inc.php` Zeilen 684–686: Rückgabewert von `$pemail->registeremail()` wird ignoriert.
- **Fehlerart:** Funktional / Fehlerbehandlung
- **Schweregrad:** Hoch
- **Konsole / Stacktrace:** –
- **Netzwerkhinweise:** –
- **Status:** Fixed

---

## BUG-003: Legacy-Default-User „powernews" mit base64-Passwort ausgeliefert

- **Bereich:** Installation / Default-Datenbank
- **URL / Route:** initial DB-Dump `powernews.sql`, sichtbar in `pn_users` Zeile id=1
- **Reproduktionsschritte:**
  1. `docker exec powernews_db mariadb -upowernews -ppowernews powernews -e "SELECT password FROM pn_users WHERE id=1;"`
  2. Ergebnis: `cG93ZXJuZXdz` (base64 von „powernews")
  3. Login-Versuch mit Nickname `powernews` / Passwort `powernews` funktioniert (siehe `pn_verify_password` → Legacy-Pfad).
- **Erwartet:** Kein ausgelieferter Default-Account mit öffentlich bekanntem, base64-„verschlüsseltem" Passwort. Falls ein Seed-User nötig ist, Bcrypt-Hash und Zwangspasswortänderung.
- **Tatsächlich:** Frisch installiertes System erlaubt Login mit `powernews / powernews`. Base64 ist keine Hash-Funktion; wer die DB lesen kann, erhält den Klartext.
- **Fehlerart:** Sicherheit / Default-Credentials
- **Schweregrad:** Kritisch
- **Konsole / Stacktrace:** –
- **Netzwerkhinweise:** –
- **Status:** Fixed

---

## BUG-004: Keine HTTP-Sicherheits-Header

- **Bereich:** Global
- **URL / Route:** alle Seiten von `http://localhost:8087/`
- **Reproduktionsschritte:**
  1. `curl -I http://localhost:8087/user.php`
  2. Response prüfen
- **Erwartet:** Mindestens `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY` (oder CSP `frame-ancestors`), `Content-Security-Policy`, `Referrer-Policy`, `Permissions-Policy`.
- **Tatsächlich:** Nur `Date`, `Server`, `Content-Type`. Keine Security-Header. `Server: Apache/2.4.65 (Debian)` gibt zusätzlich Version preis.
- **Fehlerart:** Sicherheit / Konfiguration
- **Schweregrad:** Mittel
- **Konsole / Stacktrace:** –
- **Netzwerkhinweise:** Response-Header unvollständig
- **Status:** Fixed

---

## BUG-005: Response-Charset ISO-8859-15 bei UTF-8-Inhalten

- **Bereich:** Global / Content-Type
- **URL / Route:** alle Seiten
- **Reproduktionsschritte:**
  1. `curl -I http://localhost:8087/user.php`
  2. `Content-Type: text/html; charset=ISO-8859-15`
  3. Gleichzeitig `default_charset = "UTF-8"` in `.docker/php.ini`
- **Erwartet:** Konsistente Zeichensatz-Deklaration, passend zu den gelieferten Inhalten (UTF-8).
- **Tatsächlich:** Server liefert `ISO-8859-15`, Meta-Tags/JS gehen von UTF-8 aus. Umlaute in Fehlermeldungen werden teils als HTML-Entities geliefert (`ausf&uuml;llen`), aber der Charset-Header bleibt Latin-9. Risiko: Encoding-Inkonsistenzen, XSS-Filter-Bypass.
- **Fehlerart:** Konfiguration / Darstellung
- **Schweregrad:** Mittel
- **Konsole / Stacktrace:** –
- **Netzwerkhinweise:** Header
- **Status:** Fixed

---

## BUG-006: Silent Insert-Failure bei überlangen Registrierungs-Eingaben

- **Bereich:** Registrierung
- **URL / Route:** `POST /user.php?pndata[send]=YES`
- **Reproduktionsschritte:**
  1. Nickname mit 300 Zeichen senden (`nickname` varchar(100) in `pn_users`)
  2. Erfolgsmeldung erscheint
  3. `SELECT * FROM pn_users WHERE email='long@example.com'` → kein Datensatz
- **Erwartet:** Validierung auf Client- oder Serverseite, Fehlermeldung („Nickname zu lang, max. 100 Zeichen"), Formular zurück.
- **Tatsächlich:** `STRICT_TRANS_TABLES` + prepared Statement → MariaDB wirft Fehler. `mysqli_stmt_execute` liefert `false`, Return-Wert wird nicht geprüft. Nutzer bekommt falsche Erfolgsmeldung. Kein Log im PHP-Error-Log.
- **Fehlerart:** Funktional / Validierung / Fehlerbehandlung
- **Schweregrad:** Hoch
- **Konsole / Stacktrace:** –
- **Netzwerkhinweise:** HTTP 200 trotz Fehlschlag
- **Status:** Fixed

---

## BUG-007: Gespeicherter XSS-Payload im Nickname

- **Bereich:** Registrierung / Persistenz
- **URL / Route:** `POST /user.php?pndata[send]=YES` → `pn_users.nickname`
- **Reproduktionsschritte:**
  1. Nickname `<script>alert(1)</script>` registrieren
  2. In DB ist der Wert 1:1 gespeichert
  3. Stellen, an denen der Nickname ausgegeben wird (Kommentar-Autor, Profil-Ansicht, evtl. Nachrichten-Autor), müssen alle sauber escapen. Escaping findet in vielen Teilen statt (`pn_escape`), aber eine Änderung an einer Stelle gefährdet die Sicherheit der gesamten App.
- **Erwartet:** Serverseitige Input-Validierung des Nicknames (Whitelist: `[A-Za-z0-9_.-]{3,30}` o.ä.), keine Speicherung von HTML-Payloads.
- **Tatsächlich:** Keine Input-Validierung, jeglicher String wird akzeptiert. Dependant-Ausgaben müssen jederzeit escapen, sonst Stored-XSS.
- **Fehlerart:** Sicherheit / Validierung
- **Schweregrad:** Hoch
- **Konsole / Stacktrace:** –
- **Netzwerkhinweise:** –
- **Status:** Fixed

---

## BUG-008: Whitespace-only und leere Nicknames akzeptiert

- **Bereich:** Registrierung
- **URL / Route:** `POST /user.php?pndata[send]=YES`
- **Reproduktionsschritte:**
  1. Nickname `   ` (drei Leerzeichen) + gültige E-Mail absenden
  2. Erfolg, User id wird vergeben, nickname = `   `
- **Erwartet:** Trimming + Mindestlänge-Prüfung (z.B. ≥ 3 Zeichen, Regex auf Nicht-Whitespace).
- **Tatsächlich:** Nur `!$nickname` Check, d.h. PHP-falsy (leerer String). `   ` ist truthy. Account wird erstellt.
- **Fehlerart:** Validierung
- **Schweregrad:** Mittel
- **Konsole / Stacktrace:** –
- **Netzwerkhinweise:** –
- **Status:** Fixed

---

## BUG-009: Authentifizierungs-Bypass über gefälschten Cookie (Token wird nicht verifiziert)

- **Bereich:** Session / Cookie-Validierung
- **URL / Route:** jede geschützte Route, z.B. `GET /user.php?page=profile`
- **Reproduktionsschritte:**
  1. Beliebigen Cookie-String bauen: `base64("1@@@@@AAAA")` = `MUBAQEBAQUFBQQ==`
  2. `curl -b "pncookie=MUBAQEBAQUFBQQ==" http://localhost:8087/user.php?page=profile`
  3. Profilseite des Nutzers mit id=1 (Default-Admin „powernews") wird ausgeliefert
- **Erwartet:** Cookie-Token muss serverseitig gegen einen in der DB gespeicherten Wert (z.B. hashed Session-Token pro User) geprüft werden; ohne gültiges Match → Ablehnen.
- **Tatsächlich:** `pn_user::checkcookie()` in `pninc/functions.inc.php` Zeilen 740–771:
  ```
  $parts = explode('@@@@@', $cookiestring);
  $userId = (int) $parts[0];
  SELECT * FROM users WHERE id = ?
  → wenn gefunden: loggedin = YES
  ```
  Der zweite Teil (`$parts[1]` – der Token) wird NIE gegen eine DB- oder Session-Referenz geprüft. Der Login-Vorgang in `setusercookie()` generiert zwar einen zufälligen 32-Byte-Token (`random_bytes(32)`), speichert ihn aber nirgends. Er fungiert rein als „irgendwas, um das explode-Format zu erfüllen".
  
  Konsequenz: Wer eine User-ID kennt, kann sich als dieser Nutzer einloggen. Auch der Admin (id=1) ist damit trivial übernehmbar.
- **Fehlerart:** Sicherheit / Authentifizierung
- **Schweregrad:** Kritisch
- **Konsole / Stacktrace:** –
- **Netzwerkhinweise:** keine besondere Auffälligkeit, nur der Cookie-Header ist manipuliert
- **Status:** Fixed

---

## BUG-010: User-Enumeration durch unterschiedliche Login-Fehlermeldungen

- **Bereich:** Login
- **URL / Route:** `POST /user.php?page=login&pndata[login]=YES`
- **Reproduktionsschritte:**
  1. Login mit Nickname `doesnotexist` + beliebigem Passwort → „Es existiert kein Benutzer mit diesem Nickname!"
  2. Login mit Nickname `qauser1` + falschem Passwort → „Das angegebene Passwort ist nicht korrekt!"
- **Erwartet:** Einheitliche Fehlermeldung („Nickname oder Passwort falsch"), keine Unterscheidung zwischen nicht-existent und falsches Passwort.
- **Tatsächlich:** Zwei unterschiedliche Meldungen, wodurch ein Angreifer eine Liste gültiger Nicknames zusammenstellen kann (Credential Stuffing, Social Engineering).
- **Fehlerart:** Sicherheit / Information Disclosure
- **Schweregrad:** Hoch
- **Konsole / Stacktrace:** –
- **Netzwerkhinweise:** –
- **Status:** Fixed

---

## BUG-011: Kein Brute-Force-Schutz / Rate-Limiting auf Login

- **Bereich:** Login
- **URL / Route:** `POST /user.php?page=login&pndata[login]=YES`
- **Reproduktionsschritte:**
  1. 20× hintereinander mit falschem Passwort anmelden
  2. Alle Anfragen werden gleich schnell beantwortet, kein Lockout, kein CAPTCHA, kein Delay
- **Erwartet:** Nach 5–10 Fehlversuchen z.B. temporäres Lock des Accounts oder IP-Rate-Limit, und/oder ansteigendes Delay/Captcha.
- **Tatsächlich:** Kein Lockout, kein Delay. bcrypt-Cost=12 liefert zwar ~200ms, aber das reicht bei lokalem Angreifer für viele Versuche pro Minute.
- **Fehlerart:** Sicherheit / Brute-Force
- **Schweregrad:** Hoch
- **Konsole / Stacktrace:** –
- **Netzwerkhinweise:** –
- **Status:** Fixed

---

## BUG-012: Fatal-Error bei ungültigem Base64 im Cookie (Uncaught TypeError in checkcookie)

- **Bereich:** Cookie-Handler
- **URL / Route:** jede Seite mit Cookie-Check
- **Reproduktionsschritte:**
  1. Cookie setzen: `pncookie=NOT_BASE64!!!`
  2. `/user.php` aufrufen
- **Erwartet:** Ungültiges Base64 → `checkcookie()` liefert `null`, Anwender wird als unlogged-in behandelt, Seite lädt normal.
- **Tatsächlich:** `base64_decode($string, true)` gibt `false` zurück, `explode('@@@@@', false)` wirft `TypeError`. Seite bricht ab (weißer Screen), Fehler im PHP-Log:
  ```
  PHP Fatal error: Uncaught TypeError: explode(): Argument #2 ($string) must be of type string, false given in /var/www/html/pninc/functions.inc.php:749
  ```
- **Fehlerart:** Fehlerbehandlung / Stabilität
- **Schweregrad:** Hoch (DoS, Crash)
- **Konsole / Stacktrace:** siehe PHP-Log `logs/php-error.log`
- **Netzwerkhinweise:** leere Response, HTTP 500 in manchen Konstellationen
- **Status:** Fixed

---

## BUG-013: PHP-Warnings „access array offset on null" in usermenu/logout

- **Bereich:** usermenu / logout / Session-Prüfung
- **URL / Route:** z.B. `/user.php?page=logout` ohne gültigen Cookie
- **Reproduktionsschritte:**
  1. `/user.php?page=logout` ohne Cookie aufrufen
  2. `logs/php-error.log` prüfen
- **Erwartet:** Sauberer Null-Check.
- **Tatsächlich:**
  ```
  PHP Warning: Trying to access array offset on null in /var/www/html/pninc/functions.inc.php on line 860  (usermenu)
  PHP Warning: Trying to access array offset on null in /var/www/html/pninc/functions.inc.php on line 874  (profile)
  ```
  Die Zugriffe erfolgen auf `$pnuser['loggedin']`, obwohl `$pnuser` null sein kann.
- **Fehlerart:** Funktional / Code-Qualität
- **Schweregrad:** Niedrig (produziert Logs, sicherheitsunkritisch)
- **Konsole / Stacktrace:** PHP-Log
- **Netzwerkhinweise:** –
- **Status:** Fixed

---

## BUG-014: Deaktivierte User können sich trotzdem einloggen

- **Bereich:** Login / Account-Status
- **URL / Route:** `POST /user.php?page=login&pndata[login]=YES`
- **Reproduktionsschritte:**
  1. In DB: `UPDATE pn_users SET status='Deactivated' WHERE nickname='qauser1';`
  2. Login-Versuch mit korrektem Passwort
  3. Erfolg: Cookie wird gesetzt, Nutzer sieht „Du hast Dich erfolgreich eingeloggt!"
- **Erwartet:** Login muss für `status = 'Deactivated'` abgelehnt werden.
- **Tatsächlich:** `pn_user::login()` prüft das `status`-Feld gar nicht. Die Spalte existiert in `pn_users`, wird aber weder in `login()` noch in `setusercookie()` noch in `checkcookie()` geprüft.
- **Fehlerart:** Funktional / Sicherheit
- **Schweregrad:** Hoch
- **Konsole / Stacktrace:** –
- **Netzwerkhinweise:** –
- **Status:** Fixed

---

## BUG-015: Profil-Update erfordert zwingend Passwort (kein reines Datenupdate möglich)

- **Bereich:** Profil
- **URL / Route:** `POST /user.php?page=profile&pndata[send]=YES`
- **Reproduktionsschritte:**
  1. Eingeloggt mit gültigem Cookie
  2. Profil-Formular öffnen, nur realname/city ändern wollen
  3. Passwort-Felder leer lassen
  4. Submit → Meldung „Du musst alle Felder ausfüllen!"
- **Erwartet:** Passwort-Felder sollten optional sein. Nur wenn der Nutzer neue Passwörter einträgt, werden diese gesetzt. Sonst bleibt das alte Passwort bestehen.
- **Tatsächlich:** In `profile()` wird `if (!$nickname || !$email || !$password || !$password2)` geprüft. Das zwingt bei jeder Profiländerung zum Neusetzen des Passworts. Extrem nutzerfeindlich.
- **Fehlerart:** Funktional / UX
- **Schweregrad:** Hoch
- **Konsole / Stacktrace:** –
- **Netzwerkhinweise:** –
- **Status:** Fixed

---

## BUG-016: Fatal-Error bei leerem `age`-Feld in Profil-Update

- **Bereich:** Profil / Validierung
- **URL / Route:** `POST /user.php?page=profile&pndata[send]=YES`
- **Reproduktionsschritte:**
  1. Profil-Formular absenden mit `pndata[age] = ""`
  2. PHP-Fatal-Error im Log, Nutzer sieht halb-gerenderte Seite ohne Footer/Inhalt
- **Erwartet:** Leeres age → 0 oder unchanged, saubere Handhabung.
- **Tatsächlich:** MySQL wirft `Incorrect integer value: '' for column 'age'`. Exception unbehandelt, Output bricht mitten im HTML ab.
  ```
  PHP Fatal error: Uncaught mysqli_sql_exception: Incorrect integer value: '' for column `powernews`.`pn_users`.`age` at row 1 in /var/www/html/pninc/functions.inc.php:908
  ```
  Analog für `icq` (int(10)), falls leer submitted.
- **Fehlerart:** Funktional / Fehlerbehandlung
- **Schweregrad:** Hoch
- **Konsole / Stacktrace:** siehe Log
- **Netzwerkhinweise:** HTTP 200, aber Antwortkörper abgebrochen
- **Status:** Fixed

---

## BUG-017: Fatal-Error bei zu langem realname/city/homepage in Profil-Update

- **Bereich:** Profil / Validierung
- **URL / Route:** `POST /user.php?page=profile&pndata[send]=YES`
- **Reproduktionsschritte:**
  1. Profil mit realname=300 Zeichen absenden
  2. Fatal Error (`Data too long for column`) im Log
- **Erwartet:** Validierung auf Maxlänge, Fehlermeldung, Formular zurück mit Fehlerhinweis.
- **Tatsächlich:** Keine Validierung, MySQL lehnt ab, PHP-Fatal. Output abgebrochen.
- **Fehlerart:** Funktional / Validierung
- **Schweregrad:** Hoch
- **Konsole / Stacktrace:** PHP-Log
- **Netzwerkhinweise:** HTTP 200, Body abgebrochen
- **Status:** Fixed

---

## BUG-018: Stored-Data im Profil ohne Inhaltsvalidierung (homepage als `javascript:` URL)

- **Bereich:** Profil / Felder
- **URL / Route:** `POST /user.php?page=profile&pndata[send]=YES`
- **Reproduktionsschritte:**
  1. Profil speichern mit `pndata[homepage] = javascript:alert(1)`
  2. DB enthält den Wert 1:1
- **Erwartet:** URL-Validierung: nur `http://` oder `https://`. Oder zumindest bei Render als Link sauberes `rel="noopener noreferrer"` + Schema-Whitelist.
- **Tatsächlich:** Jeder String wird gespeichert. Falls/sobald die Homepage irgendwo als `<a href="…">` gerendert wird (Profil-Ansicht öffentlicher Nutzer, News-Autor-Info), droht Click-to-XSS.
- **Fehlerart:** Sicherheit / Validierung
- **Schweregrad:** Mittel (erst bei Ausgabe-Stelle exploitable)
- **Konsole / Stacktrace:** –
- **Netzwerkhinweise:** –
- **Status:** Fixed

---

## BUG-019: Schema akzeptiert unplausible age-Werte

- **Bereich:** Profil / DB-Schema
- **URL / Route:** `POST /user.php?page=profile&pndata[send]=YES`
- **Reproduktionsschritte:**
  1. Profil mit `age=500` speichern
  2. DB: `age=500` ist gespeichert
- **Erwartet:** Sinnvolle Range (0–150), Validierung auf UI/Server.
- **Tatsächlich:** `age int(2)` ist nur Display-Width-Hint, echte int-Range. Werte wie 500, 99999 werden akzeptiert. Kein Range-Check im Code.
- **Fehlerart:** Validierung / Persistenz
- **Schweregrad:** Niedrig
- **Konsole / Stacktrace:** –
- **Netzwerkhinweise:** –
- **Status:** Fixed

---

## BUG-020: Passwort-Reset-DoS – Fremdes Passwort ohne Auth änderbar

- **Bereich:** Passwort vergessen (`senddata`)
- **URL / Route:** `POST /user.php?page=senddata`
- **Reproduktionsschritte:**
  1. Anonymer Aufruf: `curl -X POST /user.php?page=senddata -d 'pndata[searchstring]=qauser1'`
  2. Response: „Die Daten-E-Mail konnte nicht gesendet werden" (weil sendmail fehlt, siehe BUG-001)
  3. DB prüfen: `SELECT password FROM pn_users WHERE nickname='qauser1'` → Hash ist geändert, neues Passwort wurde erzeugt
  4. Login mit altem Passwort schlägt fehl → legitimer User ausgesperrt
- **Erwartet:** Passwort erst dann in der DB ersetzen, wenn der Mailversand erfolgreich war. Oder: Reset-Token per Mail versenden, Nutzer setzt Passwort selbst über Reset-Link.
- **Tatsächlich:** In `senddata()` wird zuerst `UPDATE password` ausgeführt, dann `dataemail()`. Wenn die Mail scheitert, bleibt das neue Passwort in der DB und ist niemandem bekannt → permanent ausgesperrt. Ohne Login und ohne CAPTCHA ist dieser Endpunkt ein triviales DoS/Account-Lockout-Tool gegen beliebige Nicknames/E-Mails.
- **Fehlerart:** Sicherheit / Funktional
- **Schweregrad:** Kritisch
- **Konsole / Stacktrace:** –
- **Netzwerkhinweise:** –
- **Status:** Fixed

---

## BUG-021: User-Enumeration über senddata (unterschiedliche Fehlermeldungen)

- **Bereich:** Passwort vergessen (`senddata`)
- **URL / Route:** `POST /user.php?page=senddata`
- **Reproduktionsschritte:**
  1. `pndata[searchstring]=existiert_nicht` → „Es ist kein Benutzer mit diesem Nickname/dieser E-Mail Adresse angemeldet!"
  2. `pndata[searchstring]=qauser1` → „Die Daten-E-Mail konnte nicht gesendet werden" (oder Erfolg)
- **Erwartet:** Stets dieselbe Meldung, egal ob Nutzer existiert („Falls ein Konto mit diesen Daten existiert, haben wir eine E-Mail versendet").
- **Tatsächlich:** Unterschiedliche Responses lassen sich zur Enumeration gültiger Accounts nutzen.
- **Fehlerart:** Sicherheit / Information Disclosure
- **Schweregrad:** Hoch
- **Konsole / Stacktrace:** –
- **Netzwerkhinweise:** –
- **Status:** Fixed

---

## BUG-022: senddata – „Zu viele Benutzer gefunden" bei Nick/Email-Kollision (DoS-Vektor)

- **Bereich:** Passwort vergessen
- **URL / Route:** `POST /user.php?page=senddata`
- **Reproduktionsschritte:**
  1. User A: nickname=`opfer`, email=`opfer@example.com`
  2. Angreifer registriert User B mit nickname=`opfer@example.com`
  3. senddata mit Suchstring `opfer@example.com` trifft beide User (A via email, B via nickname)
  4. Response: „Es sind zu viele Benutzer gefunden worden. Bitte spezifiziere Deine Suchangabe!"
- **Erwartet:** Suchstring eindeutig. Entweder bei Registrierung verhindern, dass Nickname eine bereits genutzte E-Mail-Adresse ist, oder bei senddata explizit nur auf `email` suchen (wenn `@` enthalten) bzw. auf `nickname`.
- **Tatsächlich:** Angreifer kann für jeden bekannten Nutzer einen „Kollisions-Account" anlegen und damit den Passwort-Reset des Opfers dauerhaft blockieren.
- **Fehlerart:** Funktional / Sicherheit
- **Schweregrad:** Hoch
- **Konsole / Stacktrace:** –
- **Netzwerkhinweise:** –
- **Status:** Fixed

---

## BUG-023: Cookie bleibt nach Logout gültig (serverseitige Invalidierung fehlt)

- **Bereich:** Logout / Session
- **URL / Route:** `GET /user.php?page=logout&pndata[logout]=YES`
- **Reproduktionsschritte:**
  1. Login als User (Cookie `pncookie=X` erhalten)
  2. Cookie-Wert separat sichern (z.B. Angreifer hat ihn abgegriffen)
  3. Legitimer Logout via Link
  4. Mit dem alten Cookie erneut auf `/user.php?page=profile` zugreifen → Profil wird weiterhin ausgeliefert
- **Erwartet:** Server invalidierung des Session-Tokens. Gestohlene Cookies dürfen nach Logout nicht weiter funktionieren.
- **Tatsächlich:** `delusercookie()` sendet nur das Lösch-Cookie ans Browser. Serverseitig existiert keine Session-Tabelle; das Validierungs-Kriterium ist allein die User-ID im Cookie (siehe BUG-009). Ein gestohlener Cookie lebt bis zum Ablauf (360 Tage) weiter.
- **Fehlerart:** Sicherheit / Session-Management
- **Schweregrad:** Kritisch
- **Konsole / Stacktrace:** –
- **Netzwerkhinweise:** –
- **Status:** Fixed

---

## BUG-024: Logout via GET – CSRF-Vektor

- **Bereich:** Logout
- **URL / Route:** `GET /user.php?page=logout&pndata[logout]=YES`
- **Reproduktionsschritte:**
  1. Angreifer-Seite enthält: `<img src="http://target/user.php?page=logout&pndata[logout]=YES">`
  2. Eingeloggter Opfer-Nutzer besucht die Seite
  3. Der Browser sendet GET mit Cookie → Opfer wird ausgeloggt
- **Erwartet:** Logout über POST + CSRF-Token.
- **Tatsächlich:** Reiner GET-Request ohne Token. Trivial über Image-Tags/Link-Preloads auszulösen. Eher Nuisance als Datenverlust, aber potentiell nervige Social-Engineering-Kombination.
- **Fehlerart:** Sicherheit / CSRF
- **Schweregrad:** Mittel
- **Konsole / Stacktrace:** –
- **Netzwerkhinweise:** –
- **Status:** Fixed

---

## BUG-025: Stored-XSS in News-Titel/Text bei Default-Config (html='News')

- **Bereich:** Sendnews / News-Rendering
- **URL / Route:** `POST /sendnews.php?pndata[send]=YES` → Rendering in `index.php` / `news.php`
- **Reproduktionsschritte:**
  1. Eingeloggt (oder als Gast, siehe BUG-027) eine News einreichen mit Titel `<script>alert('xss')</script>`
  2. Admin aktiviert die News (`status = 'Activated'`)
  3. `/index.php` abrufen → Titel wird ungefiltert als HTML ausgegeben, Skript läuft
- **Erwartet:** News-Titel/-Text müssen stets escaped werden (oder nur ein definiertes Set BBCode-artiger Tags erlaubt sein).
- **Tatsächlich:** In `pn_template::headline()` und `pn_template::news()` wird `htmlentities()` nur angewendet, wenn `pnconfig['html'] == 'Comments'` oder `'NO'`. Der Default `html = 'News'` liefert Titel/Text 1:1 aus der DB. Kombiniert mit Sendnews-Einreichung = Stored-XSS durch beliebige registrierte Nutzer (oder sogar Gäste).
- **Fehlerart:** Sicherheit / Stored-XSS
- **Schweregrad:** Kritisch
- **Konsole / Stacktrace:** `<script>alert('xss')</script>` im gelieferten HTML
- **Netzwerkhinweise:** –
- **Status:** Fixed

---

## BUG-026: addslashes + Prepared-Statement = doppelte Escaping / Backslash-Leaks in News/Comments

- **Bereich:** Sendnews, Kommentare
- **URL / Route:** `/sendnews.php`, `/comments.php`
- **Reproduktionsschritte:**
  1. Kommentar mit `It's great` posten
  2. DB enthält `It\'s great`
  3. Beim Rendern wird `stripslashes` teils angewendet, teils nicht
- **Erwartet:** `addslashes()` hat mit prepared Statements nichts zu suchen – SQL-Injektion ist ohnehin durch bind_param ausgeschlossen. Doppeltes Escaping verändert Nutzerinhalte.
- **Tatsächlich:** `pn_news::sendnews()` Zeile 546–548 und `postcomment()` Zeile 348 wenden `addslashes()` auf die Nutzer-Eingabe vor dem Insert an. Das legacy-magic-quotes-Verhalten ist nicht nötig und führt an manchen Ausgabe-Stellen zu `\"`, `\'` in den Texten.
- **Fehlerart:** Funktional / Code-Qualität / Persistenz
- **Schweregrad:** Mittel
- **Konsole / Stacktrace:** DB-Ausgabe: `<script>alert(\'xss\')</script>` (Comment id=1)
- **Netzwerkhinweise:** –
- **Status:** Fixed

---

## BUG-027: Gäste können News einreichen (newssending-Default ist „Guests/Registered")

- **Bereich:** Sendnews / Policy
- **URL / Route:** `POST /sendnews.php?pndata[send]=YES`
- **Reproduktionsschritte:**
  1. Ohne Cookie/Login: News mit beliebigem Titel/Text + catid=1 posten
  2. Erfolg; `userid = 0` in `pn_news`
- **Erwartet:** Default-Konfig sollte restriktiv sein. Fresh install mit „Guests/Registered" kann kommerzielle/feindliche Contents ungeprüft einspielen.
- **Tatsächlich:** `pn_config.newssending = Guests/Registered` wird im Initial-Dump gesetzt. Hier könnte ohne Admin-Kontrolle massenhaft News eingereicht werden. Zwar ist `status = 'Unchecked'`, aber bei hoher Einreichungsrate droht DoS (Datenbank, Moderationsaufwand).
- **Fehlerart:** Konfiguration / Sicherheit
- **Schweregrad:** Mittel
- **Konsole / Stacktrace:** –
- **Netzwerkhinweise:** –
- **Status:** Fixed

---

## BUG-028: Initialer DB-Dump enthält keine aktive Kategorie → Sendnews ist blockiert

- **Bereich:** Installation / Sendnews
- **URL / Route:** `GET /sendnews.php`
- **Reproduktionsschritte:**
  1. Fresh Install (z.B. `docker compose up --build`)
  2. `/sendnews.php` aufrufen → Meldung „News können derzeit nicht eingesendet werden, da keine Kategorien verfügbar sind"
- **Erwartet:** Mindestens eine Demo-Kategorie, damit Nutzer sofort loslegen können. Oder: saubere Admin-UX, die auf fehlende Kategorien hinweist.
- **Tatsächlich:** `pn_categories` ist nach Install leer. Funktion unbenutzbar, ohne dass ein Admin erst manuell eine Kategorie anlegt.
- **Fehlerart:** Funktional / Onboarding
- **Schweregrad:** Mittel
- **Konsole / Stacktrace:** –
- **Netzwerkhinweise:** –
- **Status:** Fixed

---

## BUG-029: Kommentar zu nicht existierender/deaktivierter News einfügbar

- **Bereich:** Kommentare
- **URL / Route:** `POST /comments.php?newsid=9999`
- **Reproduktionsschritte:**
  1. `curl -X POST /comments.php?newsid=9999 -d 'text=test'`
  2. (nach Ablauf der Spam-Frist) → Kommentar wird in `pn_comments` mit `newsid=9999` eingefügt
- **Erwartet:** `postcomment()` sollte prüfen, ob die News existiert und Status `Activated` hat. Sonst 404 / Fehlermeldung, kein Insert.
- **Tatsächlich:** In `pn_news::postcomment()` wird der `$newsid` nicht gegen die Newstabelle geprüft. Waisen-Kommentare können beliebig eingefügt werden.
- **Fehlerart:** Funktional / Datenintegrität
- **Schweregrad:** Mittel
- **Konsole / Stacktrace:** –
- **Netzwerkhinweise:** –
- **Status:** Fixed

---

## BUG-030: Comment-Spam-Protection via REMOTE_ADDR scheitert hinter Reverse-Proxy/Docker-NAT

- **Bereich:** Kommentare / Spam-Protection
- **URL / Route:** `POST /comments.php?newsid=X`
- **Reproduktionsschritte:**
  1. Mehrere verschiedene Clients im selben Docker-Netz (oder via Proxy) kommentieren
  2. `REMOTE_ADDR` = Docker-Bridge-IP `192.168.0.1`, identisch für alle Clients
  3. Spam-Protection sperrt alle Clients gleichzeitig aus (30 s)
- **Erwartet:** `X-Forwarded-For` oder `X-Real-IP` auswerten (mit Proxy-Whitelist); ansonsten zusätzliches Lockout je User/Nickname, nicht nur je IP.
- **Tatsächlich:** Statt echter Client-IPs landet die Proxy-IP in `pn_comments.ip`. In Docker/LB-Setups ist die Spam-Protection damit praktisch wirkungslos und gleichzeitig falsch restriktiv.
- **Fehlerart:** Funktional / Konfiguration
- **Schweregrad:** Mittel
- **Konsole / Stacktrace:** –
- **Netzwerkhinweise:** alle Kommentare mit IP `192.168.0.1` gespeichert
- **Status:** Fixed

---

## BUG-031: Keine Formular-Labels / Accessibility fehlt

- **Bereich:** Registrier-, Login-, Profil-, Senddata-Formulare
- **URL / Route:** alle User-Formulare
- **Reproduktionsschritte:**
  1. DevTools Console: `document.querySelectorAll('label').length` → 0
  2. Keine `for`-Attribute, keine `aria-label`, keine `label`-Elemente
- **Erwartet:** Jedes `<input>` sollte per `<label for="id">` oder `aria-label` zugeordnet sein. Screenreader-Nutzer hören sonst nur „Textfeld" ohne Kontext.
- **Tatsächlich:** Tabellen-Layout mit visueller Bezeichnung, aber semantisch keine Labels. Keyboard-Navigation funktioniert, aber Barrierefreiheit stark eingeschränkt.
- **Fehlerart:** UI / Accessibility
- **Schweregrad:** Mittel
- **Konsole / Stacktrace:** –
- **Netzwerkhinweise:** –
- **Status:** Fixed

---

## BUG-032: Passwort-Feld-maxlength=25 beschränkt starke Passwörter

- **Bereich:** Login-Formular (und Profil)
- **URL / Route:** `/user.php?page=login`, `/user.php?page=profile`
- **Reproduktionsschritte:**
  1. DevTools: `document.querySelector('input[name="pndata[password]"]').maxLength` → 25
- **Erwartet:** Mindestens 64+, besser keine Begrenzung (bcrypt arbeitet intern mit 72).
- **Tatsächlich:** Sicherheitsorientierte Nutzer werden gezwungen, kurze Passwörter zu verwenden. Das Client-Maxlength lässt sich zwar umgehen, aber Passwort-Manager füllen entsprechend kurz.
- **Fehlerart:** Sicherheit / UX
- **Schweregrad:** Niedrig
- **Konsole / Stacktrace:** –
- **Netzwerkhinweise:** –
- **Status:** Fixed

---

## BUG-033: Password-Formular ohne `autocomplete`-Hints

- **Bereich:** Login-, Register-, Profil-Formulare
- **URL / Route:** alle Benutzerformulare
- **Reproduktionsschritte:**
  1. Input-Felder haben `autocomplete=""` (leer) oder gar kein Attribut
- **Erwartet:** `autocomplete="username"` bzw. `autocomplete="current-password"` / `"new-password"`. Moderne Passwort-Manager sparen damit Reibung; ohne Hints befüllen sie inkonsistent.
- **Tatsächlich:** Browser rät selbst, manchmal werden E-Mail in Nickname-Feld gefüllt u.ä.
- **Fehlerart:** UX / Security-UX
- **Schweregrad:** Niedrig
- **Konsole / Stacktrace:** –
- **Netzwerkhinweise:** –
- **Status:** Fixed

---

## BUG-034: UTF-8-Eingaben (Umlaute/Emojis) führen zu Fatal-Error bei Registrierung

- **Bereich:** Registrierung / Encoding
- **URL / Route:** `POST /user.php?pndata[send]=YES`
- **Reproduktionsschritte:**
  1. Response-Header ist `charset=ISO-8859-15` (BUG-005)
  2. Browser sendet Formular-Daten in ISO-8859-15 (z.B. „Müller" als `M\xFCller`)
  3. MySQL-Verbindung ist per `mysqli_set_charset($pn_handler, 'utf8mb4')` auf UTF-8 gesetzt
  4. `pn_users.nickname` (utf8mb4) lehnt Latin-1-Bytes ab
  5. Fatal: `Incorrect string value: '\xFCller\xDF' for column 'nickname'`
- **Erwartet:** Charset-Kette durchgängig UTF-8 (HTTP-Header, Meta-Tag, DB, Form). Umlaute und Emojis funktionieren ohne Fehler.
- **Tatsächlich:** Registrierung mit deutschen Umlauten im Nickname schlägt fehl, Account wird nicht angelegt, der Nutzer sieht halb-gerenderten HTML-Abbruch.
- **Fehlerart:** Funktional / Encoding / Fehlerbehandlung
- **Schweregrad:** Hoch (betrifft praktisch jeden deutschen Nickname)
- **Konsole / Stacktrace:** siehe `logs/php-error.log`
- **Netzwerkhinweise:** HTTP 200, Body abgebrochen
- **Status:** Fixed

---

## BUG-035: Triple-Charset-Deklaration (HTTP-Header, Meta-Tag, DB divergieren)

- **Bereich:** Global / Rendering
- **URL / Route:** alle User-Seiten
- **Reproduktionsschritte:**
  1. HTTP-Header: `Content-Type: text/html; charset=ISO-8859-15`
  2. Meta-Tag: `<meta http-equiv="Content-Type" content="text/html; charset=windows-1252">`
  3. DB: `DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci`
- **Erwartet:** Genau eine Charset-Wahrheit in der ganzen Kette.
- **Tatsächlich:** Drei unterschiedliche Charsets. Browser verwendet HTTP-Header-Wert (ISO-8859-15), ignoriert Meta. Server-Konfig `default_charset = "UTF-8"` wird durch `header('Content-Type: text/html; charset=ISO-8859-15')` in `pninc/head.inc.php:23` überschrieben.
- **Fehlerart:** Konfiguration / Encoding
- **Schweregrad:** Hoch
- **Konsole / Stacktrace:** –
- **Netzwerkhinweise:** Header vs Meta divergieren
- **Status:** Fixed

---

## BUG-036: Session-Fixation – existierendes Cookie überlebt Login

- **Bereich:** Login / Session
- **URL / Route:** `POST /user.php?page=login&pndata[login]=YES`
- **Reproduktionsschritte:**
  1. Angreifer setzt Opfer vor dem Login einen Cookie `pncookie=base64("3@@@@@ANGREIFERTOKEN")`
  2. Opfer loggt sich ein → Server setzt neuen Cookie per `Set-Cookie`, Browser überschreibt
  3. Angreifer behält aber weiterhin seinen ursprünglichen Cookie (er hat ihn ja vorher selbst gespeichert)
  4. Mit dem alten (Pre-Login) Cookie: voller Zugriff auf Opferprofil
- **Erwartet:** Alle vor dem Login existierenden Sessions für diesen User müssen invalidiert werden; Server muss serverseitig Tokens tracken (siehe BUG-009).
- **Tatsächlich:** Konsequenz von BUG-009. Der Token wird nie gegen eine DB geprüft; jede beliebige base64(userId@@@@@*) funktioniert permanent.
- **Fehlerart:** Sicherheit / Session-Management
- **Schweregrad:** Kritisch
- **Konsole / Stacktrace:** –
- **Netzwerkhinweise:** –
- **Status:** Fixed

---

## BUG-037: Delimiter-Injection in Relatedlinks (News einsenden)

- **Bereich:** Sendnews / Relatedlinks
- **URL / Route:** `POST /sendnews.php?pndata[send]=YES`
- **Reproduktionsschritte:**
  1. News einsenden mit
     - `pndata[rl_title][]=Inject!@!@!evil_url!@!@!_blank`
     - `pndata[rl_url][]=https://evil.example.com`
     - `pndata[rl_target][]=_blank`
  2. In DB `pn_news.relatedlinks` steht
     `Inject!@!@!evil_url!@!@!_blank!@!@!https://evil.example.com!@!@!_blank\n`
  3. Beim Parsen (Split auf `!@!@!`) entstehen zusätzliche Einträge, Angreifer kann Titel + Ziel-URL beliebig manipulieren
- **Erwartet:** Strikte Schema-basierte Speicherung (JSON) oder Delimiter-Whitelist-Escape für die Eingabefelder.
- **Tatsächlich:** `pn_news::sendnews()` Zeile 556 serialisiert die Felder mit `!@!@!` ohne Escape. Einsender kann zusätzliche Links einschleusen.
- **Fehlerart:** Sicherheit / Datenintegrität
- **Schweregrad:** Mittel
- **Konsole / Stacktrace:** –
- **Netzwerkhinweise:** –
- **Status:** Fixed

---

## BUG-038: Kommentare ohne serverseitige Längenbegrenzung

- **Bereich:** Kommentare
- **URL / Route:** `POST /comments.php?newsid=X`
- **Reproduktionsschritte:**
  1. Kommentar mit 60 000 Zeichen posten
  2. Erfolg, DB-Eintrag mit LENGTH=60001
  3. Text-Column ist `TEXT` (MySQL-Limit ~64 KiB)
- **Erwartet:** Sinnvolle Grenze (z.B. 5 000 Zeichen), spätestens am Server.
- **Tatsächlich:** Keine Längenprüfung. Mit Script können Kommentare bis Column-Limit gepostet werden, dann DoS via Spamblock-Bypass (durch IP-Rotation möglich).
- **Fehlerart:** Validierung / Performance
- **Schweregrad:** Mittel
- **Konsole / Stacktrace:** –
- **Netzwerkhinweise:** –
- **Status:** Fixed

---

## BUG-039: news.php komplett gebrochen (TypeError durch strict_types)

- **Bereich:** News-Details / User-Flow
- **URL / Route:** `GET /news.php?newsid=X`
- **Reproduktionsschritte:**
  1. `curl http://localhost:8087/news.php?newsid=1` → HTTP 200, aber Body bricht nach 2 KB mitten im Menü ab
  2. Log: `Uncaught TypeError: pn_news::details(): Argument #1 ($newsid) must be of type int, string given, called in /var/www/html/pninc/details.inc.php:24`
- **Erwartet:** Details werden gerendert, Kommentare darunter.
- **Tatsächlich:** `pninc/details.inc.php:24` ruft `$pn_news->details($_GET['newsid'])` auf. Durch `declare(strict_types=1)` akzeptiert PHP 8 nicht mehr implizit den String aus `$_GET`. **Jeder Aufruf** der News-Detail-Seite kracht. Direkte Auswirkung auf Userbereich: Nach jedem erfolgreichen Kommentar leitet das System nach `news.php?newsid=…` um → Nutzer sieht kaputte Seite.
- **Fehlerart:** Funktional / Code-Qualität
- **Schweregrad:** Kritisch (User-facing permanent broken flow)
- **Konsole / Stacktrace:** siehe Log
- **Netzwerkhinweise:** HTTP 200, abgebrochener Body
- **Status:** Fixed

---

## BUG-040: install.php ohne Authentifizierung öffentlich – DB-Reset möglich

- **Bereich:** Installer / Global
- **URL / Route:** `GET/POST /install.php`
- **Reproduktionsschritte:**
  1. Beliebiger Browser ruft `http://localhost:8087/install.php` auf → Formular
  2. POST `install=YES`
  3. `install.php:76–83` liest `powernews.sql` und führt **DROP TABLE IF EXISTS** + CREATE TABLE + Initial-Inserts aus
  4. Alle User-Accounts, News, Kommentare, Kategorien, Templates weg; Default-Admin `powernews/powernews` kommt zurück
- **Erwartet:** Nach Erst-Installation muss `install.php` wie im Text angekündigt gelöscht werden. Solange sie lebt: Auth-Zwang oder Abbruch, wenn Config-Tabellen bereits populated sind.
- **Tatsächlich:** Die Datei empfiehlt zwar das Löschen im Erfolgstext, aber technisch ist sie jederzeit aufrufbar und triggert destructive SQL. Kombiniert mit User-Area: beliebige Nutzerdaten können von Angreifern gelöscht werden.
- **Fehlerart:** Sicherheit / Konfiguration
- **Schweregrad:** Kritisch
- **Konsole / Stacktrace:** –
- **Netzwerkhinweise:** –
- **Status:** Fixed

---

## BUG-041: phpinfo.php öffentlich zugänglich

- **Bereich:** Debug / Information Disclosure
- **URL / Route:** `GET /phpinfo.php`
- **Reproduktionsschritte:**
  1. `curl http://localhost:8087/phpinfo.php` → 84 KB phpinfo-Output
  2. Enthält PHP-Version, geladene Module, `$_SERVER`, Environment, include_path, etc.
- **Erwartet:** phpinfo darf nicht deployed werden. Höchstens lokal, IP-gate oder Secret-Query-Param.
- **Tatsächlich:** `phpinfo.php` enthält schlicht `echo phpinfo();`. Ohne Auth aufrufbar. Reconnaissance-Schatz.
- **Fehlerart:** Sicherheit / Info-Disclosure
- **Schweregrad:** Hoch
- **Konsole / Stacktrace:** –
- **Netzwerkhinweise:** –
- **Status:** Fixed

---

## BUG-042: convert.php ohne Authentifizierung

- **Bereich:** Migrations-Tool
- **URL / Route:** `GET/POST /convert.php`
- **Reproduktionsschritte:**
  1. `curl http://localhost:8087/convert.php` → Konvertierungs-Tool-Form
  2. POST mit `newspro_dir=<Pfad>` → liest lokale Dateien
- **Erwartet:** Nach Migration löschen oder Admin-Auth davorschalten.
- **Tatsächlich:** Auch ohne Admin-Login ansprechbar. Path-Validierung gegen Traversal ist vorhanden, aber Tool sollte trotzdem nicht öffentlich sein.
- **Fehlerart:** Sicherheit / Konfiguration
- **Schweregrad:** Mittel
- **Konsole / Stacktrace:** –
- **Netzwerkhinweise:** –
- **Status:** Fixed

---

## BUG-043: update.php ohne Authentifizierung

- **Bereich:** Update-Skript
- **URL / Route:** `GET/POST /update.php`
- **Reproduktionsschritte:**
  1. Aufruf ohne Cookie
  2. Formular wird ausgeliefert
- **Erwartet:** Nach Upgrade löschen oder Auth-Schutz.
- **Tatsächlich:** Öffentlich zugänglich, kann Schema-Upgrades triggern.
- **Fehlerart:** Sicherheit / Konfiguration
- **Schweregrad:** Mittel
- **Konsole / Stacktrace:** –
- **Netzwerkhinweise:** –
- **Status:** Fixed

---

## BUG-044: `/logs/php-error.log` ist via HTTP lesbar

- **Bereich:** Logs / Information Disclosure
- **URL / Route:** `GET /logs/php-error.log`
- **Reproduktionsschritte:**
  1. `curl -s http://localhost:8087/logs/php-error.log`
  2. Ergebnis: komplettes Error-Log mit Stacktraces, Datei-Pfaden, SQL-Queries, Template-Namen
- **Erwartet:** `.htaccess` im `logs/`-Verzeichnis mit `Deny from all` oder Log-Ordner außerhalb des Web-Roots.
- **Tatsächlich:** Datei ohne Zugriffsschutz. Attacker kann interne Struktur, Datei-Pfade, Exception-Messages und erzeugte SQL-Fragmente mitlesen. Hilft beim Erkunden von Bugs wie BUG-012 und ermöglicht gezielte Angriffe.
- **Fehlerart:** Sicherheit / Information Disclosure
- **Schweregrad:** Hoch
- **Konsole / Stacktrace:** –
- **Netzwerkhinweise:** HTTP 200 mit 9907 Byte Log-Inhalt
- **Status:** Fixed

---

## BUG-045: `/.git/`-Verzeichnis öffentlich zugänglich

- **Bereich:** Version-Control / Information Disclosure
- **URL / Route:** `GET /.git/config`, analog `HEAD`, `index`, `logs/HEAD`, `refs/heads/*`
- **Reproduktionsschritte:**
  1. `curl http://localhost:8087/.git/config` → liefert git-Config-Datei
- **Erwartet:** Dot-Verzeichnisse per Apache-Direktive (`<DirectoryMatch "^/.*/\.">`/`Require all denied`) blockieren.
- **Tatsächlich:** Beliebiger Zugriff möglich. Tools wie `git-dumper` ziehen komplette History, inklusive gelöschter Commits, Dev-Kommentare, ggf. Credentials.
- **Fehlerart:** Sicherheit / Information Disclosure
- **Schweregrad:** Hoch
- **Konsole / Stacktrace:** –
- **Netzwerkhinweise:** –
- **Status:** Fixed

---

## BUG-046: Timing-Attacke für User-Enumeration trotz gleicher Fehlermeldung wirksam

- **Bereich:** Login
- **URL / Route:** `POST /user.php?page=login&pndata[login]=YES`
- **Reproduktionsschritte:**
  1. Login mit gültigem Nickname + falschem Passwort: ~375 ms (bcrypt verify läuft)
  2. Login mit unbekanntem Nickname: ~72 ms (kein bcrypt)
  3. Differenz > 300 ms, übers Netz messbar
- **Erwartet:** Konstantes Timing – bei „User not found" ebenfalls einen Dummy-bcrypt-Check durchführen, oder fixen Delay.
- **Tatsächlich:** Selbst wenn die Fehlermeldung vereinheitlicht wird (IMP-010), bleibt der Timing-Unterschied ein Enumeration-Kanal.
- **Fehlerart:** Sicherheit / Timing
- **Schweregrad:** Mittel
- **Konsole / Stacktrace:** –
- **Netzwerkhinweise:** HTTP-Response-Timing messbar
- **Status:** Fixed

---

## BUG-047: Login-Formular wird auch eingeloggtem Nutzer ausgeliefert

- **Bereich:** Login / UX
- **URL / Route:** `GET /user.php?page=login`
- **Reproduktionsschritte:**
  1. Mit gültigem Cookie aufrufen
  2. Seite zeigt weiter die Login-Felder an, obwohl das Menü bereits „Logout" enthält
- **Erwartet:** Redirect zu `profile` oder Hinweistext „Du bist bereits eingeloggt, möchtest Du den Account wechseln?"
- **Tatsächlich:** `pn_user::login()` prüft `$pnuser['loggedin']` nicht. Verwirrend für Nutzer.
- **Fehlerart:** UX / Funktional
- **Schweregrad:** Niedrig
- **Konsole / Stacktrace:** –
- **Netzwerkhinweise:** –
- **Status:** Fixed

---

## BUG-048: BBCode `[img]` erlaubt Tracking-Pixel aus externen Quellen in Kommentaren

- **Bereich:** Kommentare / BBCode
- **URL / Route:** `POST /comments.php?newsid=X` → Rendering via `pn_template::comment()`
- **Reproduktionsschritte:**
  1. Kommentar mit `text=[img]http://evil.example.com/track[/img]` posten
  2. In DB wird der Text gespeichert
  3. Beim Rendern (wenn `pnconfig['bbcode']` für Comments aktiv) entsteht `<img src="http://evil.example.com/track" border="0">`
- **Erwartet:** Whitelist vertrauenswürdiger Image-Hosts oder Proxy-URL; Nutzer-Bilder nicht direkt als externe Ressource einbinden.
- **Tatsächlich:** Jeder Gast-Kommentar kann Tracker aus Fremd-Domains einbinden; IP-/Cookie-Tracking aller Seiten-Besucher möglich.
- **Fehlerart:** Sicherheit / Privatsphäre
- **Schweregrad:** Mittel
- **Konsole / Stacktrace:** –
- **Netzwerkhinweise:** –
- **Status:** Fixed

---

## Weitere Bugs folgen (chronologisch während Audit-Fortschritt).
