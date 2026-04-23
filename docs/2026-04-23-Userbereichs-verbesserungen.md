# Userbereichs-Improvements – PowerNews

Audit-Datum: 2026-04-23
Auditor: Senior-QA

Hinweis: Nur Workflow- und UX-Verbesserungen. Keine Bugs (siehe bugs.md).

---

## IMP-001: Fehlermeldungs-Seite nutzt Link statt Button

- **Bereich:** Fehlermeldungs-Template (global)
- **URL / Route:** alle Fehler-Rücksprünge (z.B. nach leerem Submit auf `user.php`)
- **Beobachtung:** Fehlermeldung wird als `<a href="javascript:history.back()">…</a>` dargestellt. Der Text ist selbst der Link.
- **Problem im Workflow:**
  - Semantisch falsch (Link für Aktion statt Button)
  - `javascript:history.back()` funktioniert nicht, wenn JS deaktiviert ist
  - Bei direktem Aufruf via Bookmark / gespeicherte URL führt `history.back()` woanders hin
  - Form-Inhalte gehen beim Zurückspringen oft verloren (browserabhängig)
  - Keine sichtbare visuelle Unterscheidung von „Nachricht" und „Aktion"
- **Auswirkung:** Nutzer klicken irritiert auf Fehlermeldungstext, weil sie nicht als Link erkennbar ist. Wenn der Browser die Felder nicht wiederherstellt, müssen alle Eingaben erneut gemacht werden.
- **Verbesserungsvorschlag:** Fehlermeldung als eigene Meldung anzeigen, darunter ein Button „Zurück" und die Formularfelder mit vorbefüllten (validen) Werten neu ausliefern.
- **Priorität:** Hoch

---

## IMP-002: Kein Passwortfeld bei Registrierung (nur E-Mail-gebundener Zugang)

- **Bereich:** Registrierungsformular
- **URL / Route:** `GET /user.php`
- **Beobachtung:** Nutzer kann bei Registrierung kein eigenes Passwort setzen. Das System generiert ein zufälliges 8-stelliges Passwort und verschickt es per Mail.
- **Problem im Workflow:**
  - Ohne funktionierenden Mailversand (siehe BUG-001) unmöglich sich einzuloggen
  - Nutzer muss sich ein vom System erzeugtes Kryptopasswort merken oder sofort ändern
  - Einmalpasswort wird im Klartext per E-Mail versendet (unsicher bei abgefangener Mail)
  - Passwort-Länge 8 Zeichen ist heute zu kurz
- **Auswirkung:** Hohe Absprungrate bei Registrierung, Medienbruch (Browser → Mail-Client → Browser), Sicherheitsrisiko durch Klartextversand.
- **Verbesserungsvorschlag:** Passwort-Feld + Passwort-Wiederholung im Registrierungsformular; E-Mail-Bestätigung nur zum Account-Activation-Link ohne Passwort.
- **Priorität:** Hoch

---

## IMP-003: Kein CSRF-Token an Formularen

- **Bereich:** Alle Formulare (Register, Login, Profile, Senddata, Kommentar, Sendnews)
- **URL / Route:** sämtliche POST-Routen
- **Beobachtung:** Keines der Formulare enthält einen CSRF-Token.
- **Problem im Workflow:** Ein eingeloggter Nutzer könnte durch Klick auf einen externen Link sein Profil ändern, Mails versenden, Kommentare posten oder Passwörter resetten.
- **Auswirkung:** CSRF-Anfälligkeit. Profil-Übernahme durch bösartige Fremdseiten.
- **Verbesserungsvorschlag:** Ein Session-gebundener CSRF-Token in jedem Formular, Validierung serverseitig vor jeder Aktion.
- **Priorität:** Hoch

---

## IMP-004: Send-Flag via GET-Query statt POST

- **Bereich:** Submit-Mechanik aller Userformulare
- **URL / Route:** z.B. `POST /user.php?pndata[send]=YES`
- **Beobachtung:** Der Aktion-Trigger (`send=YES`, `login=YES`) steht in der URL (GET). Nur die Daten sind POST.
- **Problem im Workflow:**
  - Wenn Nutzer die URL kopiert/teilt, sind „action-flags" inkludiert
  - Nach einem erfolgreichen Submit ist die URL permanent mit `?pndata[send]=YES` versehen → Reload-Verhalten uneinheitlich
  - Zurückbutton in Browser kann beim Zurückspringen verwirrende Submit-Versuche auslösen
- **Auswirkung:** Verwirrung bei Reload, Shareability, Security-Lint-Warnungen.
- **Verbesserungsvorschlag:** Action-Flag via Hidden-Input im Formular senden, nicht via URL. Nach Success-Redirect sauberer GET auf Ziel-URL.
- **Priorität:** Mittel

---

## IMP-005: Sprachbruch / fehlende i18n-Konsistenz

- **Bereich:** Fehlermeldungen / Sprache
- **URL / Route:** global
- **Beobachtung:** Sprach-Strings in `pninc/lang/` hartkodiert, Mischung aus Du-Form („Du musst…") und Sie-Form kann auftreten, keine Language-Switch-Logik für Nutzer.
- **Problem im Workflow:** Kein konsistenter Ton. Mehrsprachigkeit bei Bedarf nur über Code-Änderung.
- **Auswirkung:** Professionalität leidet.
- **Verbesserungsvorschlag:** Einheitliche Anrede, optionaler Sprach-Switch.
- **Priorität:** Niedrig

---

## IMP-006: Keine Server-Version im Response-Header verstecken

- **Bereich:** Apache-Konfig
- **URL / Route:** alle Responses
- **Beobachtung:** Header `Server: Apache/2.4.65 (Debian)`
- **Problem im Workflow:** Versions-Disclosure erleichtert gezielte Angriffe.
- **Auswirkung:** Sicherheits-Reconnaissance.
- **Verbesserungsvorschlag:** `ServerTokens Prod` und `ServerSignature Off` in Apache-Konfig.
- **Priorität:** Niedrig

---

## IMP-007: Profil-Formular – Passwort optional machen

- **Bereich:** Profil
- **URL / Route:** `GET /user.php?page=profile`
- **Beobachtung:** Die Passwort-Felder sind beim Öffnen leer, aber trotzdem Pflicht. Um nur die E-Mail zu ändern, muss das Passwort neu vergeben werden.
- **Problem im Workflow:** Nutzer, die ihr Passwort nicht wechseln wollen, müssen es trotzdem eintragen – jede Profiländerung erzwingt ein Passwort-Update. Das Feld auf dem Formular deutet genau das nicht an.
- **Auswirkung:** Ungewolltes Passwort-Neusetzen, Fehleingaben, Nutzer wählen schwache Passwörter um schnell fertig zu sein.
- **Verbesserungsvorschlag:** Passwort-Felder optional. Nur wenn ein Wert eingegeben wird, prüfen und übernehmen. Zusätzlich Info-Text: „Leer lassen, um das aktuelle Passwort beizubehalten".
- **Priorität:** Hoch

---

## IMP-008: Passwort-Stärke-Anzeige + Mindestanforderungen

- **Bereich:** Registrierung / Profil
- **URL / Route:** `/user.php`, `/user.php?page=profile`
- **Beobachtung:** Auto-generierte Passwörter sind 8 Zeichen. Profil akzeptiert 1-Zeichen-Passwörter. Keine Stärkeindikation.
- **Problem im Workflow:** Nutzer können triviale Passwörter setzen; System signalisiert kein Risiko.
- **Auswirkung:** Brute-Force, Credential-Stuffing einfacher.
- **Verbesserungsvorschlag:** Mindestlänge 12, kein reines Dictionary-Wort (zxcvbn-ähnlich), visuelle Stärkeanzeige, optional Zwang zu Sonderzeichen.
- **Priorität:** Hoch

---

## IMP-009: Nicknames whitelisten

- **Bereich:** Registrierung / Profil
- **URL / Route:** `POST /user.php?pndata[send]=YES`
- **Beobachtung:** Jede Unicode-/HTML-/Space-only-/Symbol-Zeichenfolge ist akzeptabel.
- **Problem im Workflow:** Unbrauchbare Nicknames („   ", `<script>…</script>`, Emojis), schwierige Autor-Anzeige, Stored-Inhalte müssen überall escaped werden.
- **Auswirkung:** Moderation, Spam, Lesbarkeitsprobleme.
- **Verbesserungsvorschlag:** Regex-Whitelist (z.B. `^[A-Za-zÄÖÜäöüß0-9_.\-]{3,30}$`), Trimming, Validation client- und serverseitig.
- **Priorität:** Mittel

---

## IMP-010: Einheitliche Fehlermeldungen für User-Enumeration-Prävention

- **Bereich:** Login, Senddata
- **URL / Route:** `/user.php?page=login`, `/user.php?page=senddata`
- **Beobachtung:** Unterschiedliche Meldungen bei nicht vorhandenem vs. falschem Passwort.
- **Problem im Workflow:** Attacker-Aufklärung.
- **Auswirkung:** s. BUG-010, BUG-021.
- **Verbesserungsvorschlag:** Generische Messages („Login fehlgeschlagen", „Wenn ein Account existiert, haben wir eine E-Mail versendet").
- **Priorität:** Hoch

---

## IMP-011: CAPTCHA/Proof-of-Work auf Registrierung & Senddata

- **Bereich:** Registrierung, Passwort-Reset
- **URL / Route:** `/user.php`, `/user.php?page=senddata`
- **Beobachtung:** Kein Bot-Schutz. Automatisierte Massen-Registrierung möglich.
- **Problem im Workflow:** Fake-Accounts, Spam-News, Reset-Flood.
- **Auswirkung:** Ops-Aufwand, DoS.
- **Verbesserungsvorschlag:** hCaptcha/Turnstile auf Registrier- und Senddata-Formularen.
- **Priorität:** Mittel

---

## IMP-012: Formulare modernisieren (CSS/Responsive, Labels, ARIA)

- **Bereich:** gesamtes User-Frontend
- **URL / Route:** alle User-Formulare
- **Beobachtung:** 90er-Jahre-Tabellen-Layout, keine Media-Queries, keine Labels, Font-Tags, inline BGColor.
- **Problem im Workflow:** Schlechte UX auf Mobilgeräten, Screenreader-Probleme, kein modernes Corporate Design.
- **Auswirkung:** Unprofessioneller Eindruck.
- **Verbesserungsvorschlag:** HTML5 + semantische Labels, CSS Grid/Flex, Responsive Breakpoints; Templates aus DB in modernes Markup überführen.
- **Priorität:** Mittel

---

## IMP-013: Redirect statt Inline-Message mit `history.back()`

- **Bereich:** Fehlerbehandlung / Flash-Messages
- **URL / Route:** alle Negativ-Pfade
- **Beobachtung:** Nach Fehleingabe muss Nutzer manuell klicken, Eingaben werden möglicherweise zurückgesetzt.
- **Problem im Workflow:** Siehe IMP-001, eng verwandt. Außerdem kein Flash-Message-Pattern (Session-basierte Message, die nach Redirect-Empfang einmal angezeigt wird).
- **Auswirkung:** Nutzer-Frust, Doppeleingaben.
- **Verbesserungsvorschlag:** POST → Redirect → GET mit Flash-Message in der Session.
- **Priorität:** Hoch

---

## IMP-014: Konsistente Anrede / Ton-Einheitlichkeit

- **Bereich:** Fehlertexte / Begrüßungen / Menüs
- **URL / Route:** alle Nachrichten
- **Beobachtung:** Mischung aus Du-Form („Du musst alle Felder ausfüllen") und Sie-Form („Bitte lesen Sie sich die ReadMe durch") innerhalb derselben Seite.
- **Problem im Workflow:** Unprofessioneller Eindruck, Marken-Ton inkonsistent.
- **Auswirkung:** Zielgruppen-Fit unklar.
- **Verbesserungsvorschlag:** Einheitliche Anrede; optional via Config-Feature (Du/Sie).
- **Priorität:** Niedrig

---

## IMP-015: Kategorie-Seed in Installation

- **Bereich:** Installer / DB-Initial-Dump
- **URL / Route:** `install.php` / `powernews.sql`
- **Beobachtung:** Nach Fresh-Install sind keine Kategorien vorhanden, Sendnews ist damit blockiert (siehe BUG-028).
- **Problem im Workflow:** Nutzer testet die Seite, stößt auf Deadend.
- **Auswirkung:** Abbruch bei Erstnutzung, Setup-Hürde.
- **Verbesserungsvorschlag:** Ein bis zwei Standard-Kategorien automatisch anlegen (z.B. „Allgemein", „Aus dem Admin"). Alternativ im Installer/Setup-Assistent abfragen.
- **Priorität:** Mittel

---

## IMP-016: Einheitliche Menü-Sichtbarkeit (Usermenu ein-/ausgeloggt)

- **Bereich:** Usermenu in allen Seiten
- **URL / Route:** global
- **Beobachtung:** Menü-Einträge springen je Login-Status (Registrieren/Login/Senddata ↔ Profil/Logout); nicht klar, was „News einsenden" kann (beschränkt laut config).
- **Problem im Workflow:** Nutzer klickt auf „News einsenden" und bekommt nur dann Form, wenn Kategorien + Login passen.
- **Auswirkung:** Unklarheit, nutzlose Klicks.
- **Verbesserungsvorschlag:** Deaktivierte Menü-Einträge (gray out) wenn Voraussetzung fehlt, mit Tooltip.
- **Priorität:** Niedrig

---

## IMP-017: Strukturierte Log-Datei für Security-Events

- **Bereich:** Logging
- **URL / Route:** Login, Registrierung, Senddata, Profilwechsel
- **Beobachtung:** Keine separaten Audit-Logs. `logs/php-error.log` enthält nur PHP-Fehler.
- **Problem im Workflow:** Forensik nach Vorfällen schwierig.
- **Auswirkung:** Keine Nachvollziehbarkeit bei Account-Übernahme, Brute-Force usw.
- **Verbesserungsvorschlag:** Dedizierte Security-Events (Login, Login-Fehler, Passwort-Reset, Profiländerung) in separaten Log-File + strukturiertes Format (JSON).
- **Priorität:** Mittel

---

## IMP-018: Passwort-Reset via Link statt neuer Zufallswert

- **Bereich:** Senddata
- **URL / Route:** `POST /user.php?page=senddata`
- **Beobachtung:** Aktuell wird ein neues Passwort erzeugt und verschickt. Während der Nutzer noch nicht auf die Mail reagiert hat, ist sein Account praktisch nicht nutzbar.
- **Problem im Workflow:** Wird die Mail abgefangen → Account kompromittiert. Schlägt die Mail fehl → Account ausgesperrt (siehe BUG-020).
- **Auswirkung:** Security + Support-Ticket-Last.
- **Verbesserungsvorschlag:** Zeitlich begrenzter Reset-Link (Token in DB, `expires_at`, one-use); Nutzer klickt Link → setzt neues Passwort selbst. Account bleibt währenddessen aktiv.
- **Priorität:** Hoch

---

## IMP-019: Remember-me / Sitzungsdauer einstellbar

- **Bereich:** Login
- **URL / Route:** `/user.php?page=login`
- **Beobachtung:** Cookie läuft fest 360 Tage, kein „Angemeldet bleiben"-Schalter.
- **Problem im Workflow:** Nutzer an fremden Geräten können nicht wählen; Konto bleibt dauerhaft angemeldet.
- **Auswirkung:** Sicherheitsrisiko an shared devices; Trust-Problem.
- **Verbesserungsvorschlag:** Checkbox „Angemeldet bleiben" (14 Tage) vs. Session-Only; außerdem sichtbare Ablauf-Anzeige.
- **Priorität:** Mittel

---

## IMP-020: Accept-Charset="UTF-8" an allen Formularen

- **Bereich:** alle User-Formulare
- **URL / Route:** global
- **Beobachtung:** `<form accept-charset="">` leer, Page-Charset ISO-8859-15 → Browser sendet Formular-Daten nicht in UTF-8.
- **Problem im Workflow:** Umlaute/Emojis scheitern (siehe BUG-034/035). Selbst wenn später der Page-Charset auf UTF-8 umgestellt wird, kann ein `accept-charset="UTF-8"` Übergangsprobleme abfedern.
- **Auswirkung:** Registrierung/Profil für Nutzer mit Umlauten im Namen bricht.
- **Verbesserungsvorschlag:** Ein einmaliger Charset-Sweep: `header('Content-Type: text/html; charset=UTF-8')`, Meta-Tag UTF-8, `<form accept-charset="UTF-8">`, DB bleibt utf8mb4.
- **Priorität:** Hoch

---

## IMP-021: Installer-/Debug-Tools hart entfernen oder per Umgebung sperren

- **Bereich:** install.php, update.php, convert.php, phpinfo.php
- **URL / Route:** alle genannten Endpunkte
- **Beobachtung:** Tools aus dem Setup verbleiben im Web-Root mit HTTP-200.
- **Problem im Workflow:** Kein Zwang, sie zu löschen. Mahnhinweis steht nur in Erfolgs-Meldung.
- **Auswirkung:** Produktions-Risiko, siehe BUG-040 bis BUG-043.
- **Verbesserungsvorschlag:**
  - Post-Install-Lockfile: `install.done` unter `pninc/` setzen; install.php verweigert ohne Admin-Cookie jede weitere Aktion, wenn Lockfile existiert.
  - phpinfo.php aus dem Repo entfernen (nur lokales Debug, über `.gitignore`).
  - update.php und convert.php hinter Admin-Login legen.
- **Priorität:** Hoch

---

## IMP-022: POST/GET-Strikte Trennung – kein Mix von `?pndata[send]=YES` + `<form method="POST">`

- **Bereich:** Router-Layer des Userbereichs
- **URL / Route:** global
- **Beobachtung:** Aktionen werden über GET-Flags gesteuert, Daten über POST-Body. Dadurch:
  - Refresh nach Submit wiederholt Aktion
  - Weitergabe der URL mit Action-Flag möglich
  - `$_GET['pndata']` ist Array-Dereferenz, was an einigen Stellen zu „Trying to access array offset on null" in Logs führt
- **Problem im Workflow:** Fragile Routing-Logik.
- **Auswirkung:** Bugs wie BUG-013 (Warnings), IMP-004.
- **Verbesserungsvorschlag:** Eindeutige Controller-Routen, Aktionen nur via POST, Ziel-Redirect nach Erfolg. Eventuell Framework-ähnlicher Front-Controller.
- **Priorität:** Mittel

---

## IMP-023: Kommentare serverseitig auf Max-Länge limitieren

- **Bereich:** Kommentare
- **URL / Route:** `POST /comments.php?newsid=X`
- **Beobachtung:** Kein Längen-Check (siehe BUG-038).
- **Problem im Workflow:** Spam-Wall-of-Text und DoS möglich.
- **Auswirkung:** DB-Last, schlechte Lesbarkeit.
- **Verbesserungsvorschlag:** Max-Länge 5000 Zeichen, vor Insert prüfen + UI-Hint.
- **Priorität:** Mittel

---

## IMP-024: Referentielle Integrität für Kommentare (newsid-Fremdschlüssel)

- **Bereich:** DB / Kommentare
- **URL / Route:** `pn_comments`
- **Beobachtung:** `pn_comments.newsid` referenziert keine existierende `pn_news.id` (siehe BUG-029).
- **Problem im Workflow:** Waisen-Kommentare, UI-Inkonsistenzen.
- **Auswirkung:** Datenverschmutzung.
- **Verbesserungsvorschlag:** Foreign Key `pn_comments.newsid → pn_news.id ON DELETE CASCADE`. Falls MyISAM-Engine-bedingt nicht möglich: Engine auf InnoDB umstellen.
- **Priorität:** Mittel

---

## IMP-025: Engine-Upgrade MyISAM → InnoDB

- **Bereich:** Gesamtschema
- **URL / Route:** DB-Ebene
- **Beobachtung:** Tabellen sind `ENGINE=MyISAM`. Kein FK, keine Transaktionen, Crash-Recovery begrenzt.
- **Problem im Workflow:** Konsistenz- und Integritätsprobleme, wie in BUG-020 (Reset-Lockout) und BUG-029 (Waisen-Kommentare).
- **Auswirkung:** Schwache Datenintegrität, schlechte Recovery.
- **Verbesserungsvorschlag:** InnoDB, UTF8MB4, FK-Constraints, transaktionaler Registrierungs/Login-Flow.
- **Priorität:** Mittel

---

## IMP-026: Apache-Regeln: Dot-Directories und Log-Verzeichnisse sperren

- **Bereich:** Apache-Konfiguration
- **URL / Route:** global
- **Beobachtung:** `/.git/config`, `/logs/php-error.log` erreichbar.
- **Problem im Workflow:** Information Disclosure (siehe BUG-044, BUG-045).
- **Auswirkung:** Security-Fingerprinting, Quellcode-Leak.
- **Verbesserungsvorschlag:**
  - `.htaccess` in root mit `RedirectMatch 404 /\.git`
  - `logs/.htaccess` mit `Require all denied`
  - Alternativ `logs/` außerhalb des Web-Roots ablegen
- **Priorität:** Hoch

---

## IMP-027: Constant-Time-Check bei Login auch für nicht existierende Nutzer

- **Bereich:** Login
- **URL / Route:** `POST /user.php?page=login&pndata[login]=YES`
- **Beobachtung:** Timing verrät existierende Nickname (BUG-046).
- **Problem im Workflow:** User-Enumeration selbst bei vereinheitlichter Fehlermeldung.
- **Auswirkung:** Credential-Stuffing einfacher.
- **Verbesserungsvorschlag:** Bei „User not found" zusätzlich einen `password_verify` gegen ein Dummy-Hash ausführen (oder `hash_equals` + fixen Delay), sodass die Antwortzeit konstant bleibt.
- **Priorität:** Hoch

---

## IMP-028: BBCode `[img]` auf vertrauenswürdige Quellen beschränken

- **Bereich:** Kommentare / BBCode
- **URL / Route:** `pn_news::bbreplace()`
- **Beobachtung:** `[img]URL[/img]` rendert beliebige externe URLs.
- **Problem im Workflow:** Tracking-Pixel, Referrer-Leak, optisches Vertrauen-Pishing.
- **Auswirkung:** Siehe BUG-048.
- **Verbesserungsvorschlag:**
  - Image-Upload auf eigenem Server + nur lokale URLs erlauben, ODER
  - Whitelist (z.B. eigene Domain, imgur, Gravatar), ODER
  - Proxy-URL `/img_proxy?src=…` mit Rendering im eigenen Kontext.
- **Priorität:** Mittel

---

## IMP-029: Login-Flow: bei bereits eingeloggtem Nutzer auf Profile redirecten

- **Bereich:** Login
- **URL / Route:** `GET /user.php?page=login`
- **Beobachtung:** Formular wird auch eingeloggten Usern gezeigt (BUG-047).
- **Problem im Workflow:** Verwirrend, keine klare Status-Anzeige.
- **Auswirkung:** UX-Reibung.
- **Verbesserungsvorschlag:** `if ($pnuser['loggedin'] == 'YES') { redirect('user.php?page=profile'); }` oder Banner „Du bist eingeloggt als X. Account wechseln? → Logout".
- **Priorität:** Niedrig

---

## Weitere Improvements folgen.
