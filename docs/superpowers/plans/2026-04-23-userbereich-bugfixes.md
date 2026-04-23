# Userbereich-Bugfixes Implementation Plan

> **For agentic workers:** Dieser Plan behebt die 48 in `docs/2026-04-23-Userbereichs-bugs.md` dokumentierten Bugs im Userbereich von PowerNews. Tasks sind nach Impact gruppiert; innerhalb einer Gruppe unabhängig bearbeitbar.

**Goal:** Alle 48 Userbereichs-Bugs in PowerNews beheben – Fokus auf Auth-Härtung, Input-Validierung, XSS-Prävention, Error-Handling und Infrastruktur-Lockdown.

**Architecture:**
- Session-Layer: serverseitige Token-Speicherung in neuer Tabelle `pn_sessions` ersetzt den bisherigen Cookie-ohne-Validierung.
- Validation-Layer: neue Helper-Funktionen in `pninc/validation.inc.php` (bereits vorhanden) erweitert um `pn_validate_nickname`, `pn_validate_url`, `pn_validate_age`, `pn_validate_length`.
- Output-Layer: Charset durchgängig auf UTF-8, News-/Kommentar-Rendering konsequent escaped.
- Infrastruktur: Apache-`.htaccess`-Rules, SMTP-Konfiguration auf Mailpit, Installer-Lockfile.

**Tech Stack:** PHP 8.4, MariaDB 10.11, Apache 2.4, Docker-Compose. Bestehender `mysqli`-Stil wird beibehalten.

---

## Task 1: SMTP-Versand an Mailpit konfigurieren (BUG-001)

**Files:**
- Modify: `.docker/Dockerfile` (msmtp installieren)
- Create: `.docker/msmtprc` (SMTP-Relay-Konfig)
- Modify: `.docker/php.ini` (sendmail_path)
- Modify: `.docker/docker-compose.yml` (msmtprc mounten)

- [ ] **Step 1: Dockerfile anpassen – msmtp installieren**

In `.docker/Dockerfile` (nach dem `apt-get install`-Block):

```dockerfile
RUN apt-get update && apt-get install -y --no-install-recommends msmtp msmtp-mta ca-certificates \
    && rm -rf /var/lib/apt/lists/*
```

- [ ] **Step 2: msmtprc anlegen**

Neue Datei `.docker/msmtprc`:

```
defaults
tls off
auth off
logfile /var/log/msmtp.log

account mailpit
host mailpit
port 1025
from daemon@powernews.local

account default : mailpit
```

- [ ] **Step 3: php.ini anpassen**

In `.docker/php.ini` nach der Security-Sektion:

```
; Mail
sendmail_path = /usr/bin/msmtp -t -i
```

- [ ] **Step 4: docker-compose.yml – msmtprc mounten**

In `.docker/docker-compose.yml` im `web`-Service unter `volumes`:

```yaml
      - ./msmtprc:/etc/msmtprc:ro
```

- [ ] **Step 5: Container neu bauen**

Run: `docker compose -f .docker/docker-compose.yml up -d --build web`
Expected: `powernews_web` Up (healthy)

- [ ] **Step 6: Registrier-Mail-Test**

Run:
```bash
curl -X POST "http://localhost:8087/user.php?pndata%5Bsend%5D=YES" \
  --data-urlencode "pndata[nickname]=mailtest" \
  --data-urlencode "pndata[email]=mailtest@example.com"
curl -s "http://localhost:8033/api/v1/messages" | grep mailtest
```
Expected: JSON enthält `mailtest@example.com` als Empfänger.

- [ ] **Step 7: Commit**

```bash
git add .docker/Dockerfile .docker/msmtprc .docker/php.ini .docker/docker-compose.yml
git commit -m "fix(infra): route PHP mail() over msmtp to mailpit (BUG-001)"
```

---

## Task 2: Default-Admin `powernews/powernews` entfernen (BUG-003)

**Files:**
- Modify: `powernews.sql`

- [ ] **Step 1: Default-User in powernews.sql entfernen/neutralisieren**

Suche in `powernews.sql` den Block mit `INSERT INTO pn_users` für den Default-User und ersetze den hardcodierten Passwort-Eintrag durch einen bcrypt-Hash eines sicheren Random-Passworts (wird beim Install neu generiert). Falls der Default-User nur für Initial-Daten nötig ist: Block komplett entfernen.

Ersetze im SQL-Dump die entsprechende Zeile durch:

```sql
-- Default admin user is created via install.php with a random password
-- printed to the installer's output. No hardcoded default credentials.
```

Entferne den bisherigen `INSERT INTO pn_users VALUES (1, 'powernews', ..., 'cG93ZXJuZXdz', ...)`.

- [ ] **Step 2: install.php – Admin-User mit Random-Passwort erzeugen**

In `install.php` nach der Zeile `echo "Tabellenstruktur erstellt"`:

```php
$adminPassword = bin2hex(random_bytes(8));
$adminHash = password_hash($adminPassword, PASSWORD_DEFAULT);
$now = time();
$stmt = mysqli_prepare($pn_handler,
    "INSERT INTO pn_users (nickname, email, password, registered, showemail, status) VALUES ('admin', 'admin@localhost', ?, ?, 'NO', 'Activated')");
mysqli_stmt_bind_param($stmt, 'si', $adminHash, $now);
mysqli_stmt_execute($stmt);
$adminId = mysqli_insert_id($pn_handler);

$stmt = mysqli_prepare($pn_handler,
    "INSERT INTO pn_permissions (userid, canreadtemplates, canwritetemplates, canreadconfig, canwriteconfig, canreadusers, canwriteusers, canreadpermissions, canwritepermissions, canreadcategories, canwritecategories, canreadnews, canwritenews, canreadcomments, canwritecomments) VALUES (?, 'YES','YES','YES','YES','YES','YES','YES','YES','YES','YES','YES','YES','YES','YES')");
mysqli_stmt_bind_param($stmt, 'i', $adminId);
mysqli_stmt_execute($stmt);

echo '<b>Admin-Zugang erstellt:</b><br>Nickname: <code>admin</code><br>Passwort: <code>' . htmlspecialchars($adminPassword) . '</code><br>Bitte sofort notieren und nach erstem Login ändern.<br><br>';
```

- [ ] **Step 3: Fresh-Install testen**

Run:
```bash
docker compose -f .docker/docker-compose.yml down -v
docker compose -f .docker/docker-compose.yml up -d --build
# install.php aufrufen, Credentials notieren
curl -X POST http://localhost:8087/install.php --data "install=YES"
```
Expected: Kein `powernews/powernews`-Login mehr möglich; neuer `admin`-User mit zufälligem Passwort.

- [ ] **Step 4: Commit**

```bash
git add powernews.sql install.php
git commit -m "fix(security): remove hardcoded default admin credentials (BUG-003)"
```

---

## Task 3: Session-Token serverseitig verifizieren (BUG-009, BUG-023, BUG-036)

**Files:**
- Modify: `powernews.sql` (pn_sessions Tabelle)
- Create: Migration-SQL `pn_update.sql`
- Modify: `pninc/functions.inc.php` (setusercookie, checkcookie, delusercookie)

- [ ] **Step 1: Session-Tabelle definieren**

In `powernews.sql` nach `pn_permissions`:

```sql
DROP TABLE IF EXISTS `pn_sessions`;
CREATE TABLE `pn_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `token_hash` char(64) NOT NULL,
  `created` int(14) NOT NULL,
  `expires` int(14) NOT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `ip` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_userid` (`userid`),
  KEY `idx_token` (`token_hash`),
  KEY `idx_expires` (`expires`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

Auch in `pn_update.sql` als Upgrade-Pfad.

- [ ] **Step 2: setusercookie rewriten**

In `pninc/functions.inc.php` ersetze `setusercookie()` (Zeilen 696–737) durch:

```php
public function setusercookie(): ?array
{
    global $pn_config, $pn_handler;

    $nickname = $_POST['pndata']['nickname'] ?? '';
    $password = $_POST['pndata']['password'] ?? '';

    $stmt = mysqli_prepare($pn_handler, 'SELECT * FROM ' . $pn_config['usertable'] . ' WHERE nickname = ?');
    mysqli_stmt_bind_param($stmt, 's', $nickname);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $num = mysqli_num_rows($result);

    if ($num !== 1) {
        return null;
    }

    $pnuser = mysqli_fetch_array($result);

    if ($pnuser['status'] === 'Deactivated') {
        return null;
    }

    if (!pn_verify_password($password, $pnuser['password'], (int) $pnuser['id'])) {
        return null;
    }

    $token = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $token);
    $now = time();
    $expires = $now + 3600 * 24 * 30; // 30 Tage
    $userId = (int) $pnuser['id'];
    $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
    $ip = substr($_SERVER['REMOTE_ADDR'] ?? '', 0, 64);

    $stmt = mysqli_prepare($pn_handler,
        'INSERT INTO pn_sessions (userid, token_hash, created, expires, user_agent, ip) VALUES (?, ?, ?, ?, ?, ?)');
    mysqli_stmt_bind_param($stmt, 'isiiss', $userId, $tokenHash, $now, $expires, $ua, $ip);
    mysqli_stmt_execute($stmt);

    $cookieValue = $userId . ':' . $token;

    setcookie('pncookie', $cookieValue, [
        'expires' => $expires,
        'path' => '/',
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Strict',
    ]);

    $pnuser['loggedin'] = 'YES';
    return $pnuser;
}
```

- [ ] **Step 3: checkcookie rewriten**

Ersetze `checkcookie()` (Zeilen 740–771):

```php
public function checkcookie(): ?array
{
    global $pn_config, $pn_handler;

    if (empty($_COOKIE['pncookie'])) {
        return null;
    }

    $parts = explode(':', (string) $_COOKIE['pncookie'], 2);
    if (count($parts) !== 2) {
        return null;
    }

    $userId = (int) $parts[0];
    $token = $parts[1];
    if ($userId <= 0 || !preg_match('/^[a-f0-9]{64}$/', $token)) {
        return null;
    }

    $tokenHash = hash('sha256', $token);
    $now = time();

    $stmt = mysqli_prepare($pn_handler,
        'SELECT u.* FROM ' . $pn_config['usertable'] . ' u
         INNER JOIN pn_sessions s ON s.userid = u.id
         WHERE u.id = ? AND s.token_hash = ? AND s.expires > ? AND u.status = \'Activated\'');
    mysqli_stmt_bind_param($stmt, 'isi', $userId, $tokenHash, $now);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) !== 1) {
        return null;
    }

    $pnuser = mysqli_fetch_array($result);
    $pnuser['loggedin'] = 'YES';
    return $pnuser;
}
```

- [ ] **Step 4: delusercookie rewriten**

Ersetze `delusercookie()` (Zeilen 941–956):

```php
public function delusercookie(): void
{
    global $pn_config, $pn_handler, $pnuser;

    if (!empty($_COOKIE['pncookie'])) {
        $parts = explode(':', (string) $_COOKIE['pncookie'], 2);
        if (count($parts) === 2) {
            $token = $parts[1];
            if (preg_match('/^[a-f0-9]{64}$/', $token)) {
                $tokenHash = hash('sha256', $token);
                $stmt = mysqli_prepare($pn_handler, 'DELETE FROM pn_sessions WHERE token_hash = ?');
                mysqli_stmt_bind_param($stmt, 's', $tokenHash);
                mysqli_stmt_execute($stmt);
            }
        }
    }

    setcookie('pncookie', '', [
        'expires' => time() - 10,
        'path' => '/',
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Strict',
    ]);

    unset($pnuser, $_COOKIE['pncookie']);
    header('Location: ./' . $pn_config['userfile'] . '?page=login');
}
```

- [ ] **Step 5: Test gefälschter Cookie**

Run:
```bash
FORGED=$(printf '1:FAKETOKEN' | xxd -p -c 256)
curl -s -b "pncookie=1:$(printf 'a%.0s' {1..64})" "http://localhost:8087/user.php?page=profile" | grep -c "Du bist nicht eingeloggt"
```
Expected: `1` (Login erforderlich)

- [ ] **Step 6: Commit**

```bash
git add powernews.sql pn_update.sql pninc/functions.inc.php
git commit -m "fix(security): verify session tokens server-side (BUG-009, BUG-023, BUG-036)"
```

---

## Task 4: Charset durchgängig auf UTF-8 umstellen (BUG-005, BUG-034, BUG-035)

**Files:**
- Modify: `pninc/head.inc.php` (Zeile 23)
- Modify: alle Templates in DB (via SQL-Update)
- Modify: `header.inc.php` (Meta-Tag wird via Templates ausgeliefert)

- [ ] **Step 1: HTTP-Header auf UTF-8**

In `pninc/head.inc.php` Zeile 23:

```php
header('Content-Type: text/html; charset=UTF-8');
```

- [ ] **Step 2: install.php Charset**

In `install.php` Zeile 22 und Meta-Tag Zeile 38:

```php
header('Content-Type: text/html; charset=UTF-8');
```

```html
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
```

- [ ] **Step 3: Accept-Charset auf allen Formularen**

Erstelle `docs/superpowers/plans/utf8-forms.sql`:

```sql
UPDATE pn_templates SET
  registerform = REPLACE(registerform, '<form', '<form accept-charset="UTF-8"'),
  loginform = REPLACE(loginform, '<form', '<form accept-charset="UTF-8"'),
  senddataform = REPLACE(senddataform, '<form', '<form accept-charset="UTF-8"'),
  profileform = REPLACE(profileform, '<form', '<form accept-charset="UTF-8"'),
  commentform = REPLACE(commentform, '<form', '<form accept-charset="UTF-8"'),
  sendnewsform = REPLACE(sendnewsform, '<form', '<form accept-charset="UTF-8"')
WHERE id = 1;

UPDATE pn_templates SET
  registerform = REPLACE(registerform, 'charset=windows-1252', 'charset=UTF-8'),
  loginform = REPLACE(loginform, 'charset=windows-1252', 'charset=UTF-8'),
  senddataform = REPLACE(senddataform, 'charset=windows-1252', 'charset=UTF-8'),
  profileform = REPLACE(profileform, 'charset=windows-1252', 'charset=UTF-8'),
  commentform = REPLACE(commentform, 'charset=windows-1252', 'charset=UTF-8'),
  sendnewsform = REPLACE(sendnewsform, 'charset=windows-1252', 'charset=UTF-8'),
  message = REPLACE(message, 'charset=windows-1252', 'charset=UTF-8'),
  news = REPLACE(news, 'charset=windows-1252', 'charset=UTF-8'),
  headline = REPLACE(headline, 'charset=windows-1252', 'charset=UTF-8'),
  comment = REPLACE(comment, 'charset=windows-1252', 'charset=UTF-8'),
  usermenu = REPLACE(usermenu, 'charset=windows-1252', 'charset=UTF-8'),
  usermenu2 = REPLACE(usermenu2, 'charset=windows-1252', 'charset=UTF-8'),
  archive = REPLACE(archive, 'charset=windows-1252', 'charset=UTF-8'),
  logout = REPLACE(logout, 'charset=windows-1252', 'charset=UTF-8')
WHERE id = 1;
```

Ausführen:
```bash
docker exec -i powernews_db mariadb -upowernews -ppowernews powernews < docs/superpowers/plans/utf8-forms.sql
```

- [ ] **Step 4: Initial-SQL patchen**

In `powernews.sql` alle `windows-1252` global ersetzen durch `UTF-8` (sed):

```bash
sed -i 's/charset=windows-1252/charset=UTF-8/g' powernews.sql
```

Suche nach `<form` Einträgen in den Template-Inserts und füge `accept-charset="UTF-8"` ein.

- [ ] **Step 5: Umlaut-Registrier-Test**

Run:
```bash
curl -X POST "http://localhost:8087/user.php?pndata%5Bsend%5D=YES" \
  --data-urlencode "pndata[nickname]=Müllerß" \
  --data-urlencode "pndata[email]=mueller@example.com"
docker exec powernews_db mariadb -upowernews -ppowernews powernews -e "SELECT nickname FROM pn_users WHERE email='mueller@example.com';"
```
Expected: `Müllerß` in DB

- [ ] **Step 6: Commit**

```bash
git add pninc/head.inc.php install.php powernews.sql docs/superpowers/plans/utf8-forms.sql
git commit -m "fix(encoding): unify charset to UTF-8 across headers, meta, templates (BUG-005, BUG-034, BUG-035)"
```

---

## Task 5: Stored-XSS in News verhindern (BUG-025)

**Files:**
- Modify: `pninc/functions.inc.php` (pn_template::news, pn_template::headline)

- [ ] **Step 1: News-Titel/-Text IMMER escapen**

In `pn_template::headline()` (Zeile 1059+), ersetze die bedingte Escape-Logik:

```php
// Vorher:
// if ($pnconfig['html'] == 'Comments' || $pnconfig['html'] == 'NO') {
//     $title = htmlentities($title);
// }

// Nachher – IMMER escapen:
$title = htmlspecialchars((string) $title, ENT_QUOTES, 'UTF-8');
```

Analog in `pn_template::news()` (Zeile 1107+) für `$title`, `$text`, `$moretext`:

```php
$title = htmlspecialchars((string) $title, ENT_QUOTES, 'UTF-8');
$text = htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
$moretext = htmlspecialchars((string) $moretext, ENT_QUOTES, 'UTF-8');
```

Die Reihenfolge ist jetzt: escape → bbcode → smilies. bbcode und smilies setzen danach wieder Tags ein, aber nur aus dem kontrollierten Set `[b]/[u]/[i]/[url]/[img]`.

- [ ] **Step 2: News-Anzeige-Test**

Run:
```bash
# News mit XSS-Payload einstellen (als admin)
docker exec powernews_db mariadb -upowernews -ppowernews powernews -e \
  "UPDATE pn_news SET title='<script>alert(1)</script>' WHERE id=2;"
curl -s http://localhost:8087/index.php | grep -c '<script>alert'
```
Expected: `0` (kein unescaped script-Tag)

- [ ] **Step 3: Commit**

```bash
git add pninc/functions.inc.php
git commit -m "fix(xss): always escape news title/text/moretext (BUG-025)"
```

---

## Task 6: news.php strict_types-Crash fixen (BUG-039)

**Files:**
- Modify: `pninc/details.inc.php`

- [ ] **Step 1: newsid als int casten**

In `pninc/details.inc.php` Zeile 24 und folgende:

```php
declare(strict_types=1);

$newsid = (int) ($_GET['newsid'] ?? 0);
$showcomments = ($_GET['showcomments'] ?? '') === 'YES';

$pn_news = new pn_news();
$pn_news->details($newsid);

if ($pnconfig['comments'] === 'YES' && ($pn_newsexist ?? 'NO') === 'YES' && $showcomments) {
    $pn_news->comments($newsid);
    $pn_news->commentform($newsid);
}

pn_cpi();
```

- [ ] **Step 2: Test**

Run: `curl -s http://localhost:8087/news.php?newsid=1&showcomments=YES | wc -c`
Expected: > 3000 (volle Seite statt 2044 Byte-Abbruch)

- [ ] **Step 3: Commit**

```bash
git add pninc/details.inc.php
git commit -m "fix(news): cast newsid to int to satisfy strict_types (BUG-039)"
```

---

## Task 7: Infrastruktur absichern (BUG-040, BUG-041, BUG-042, BUG-043, BUG-044, BUG-045)

**Files:**
- Create: `.htaccess`
- Create: `logs/.htaccess`
- Delete: `phpinfo.php`
- Modify: `install.php`, `update.php`, `convert.php` (Lock-/Auth-Check)

- [ ] **Step 1: Root-.htaccess mit Dot-Dir-Sperre und install-Lock**

Neue Datei `.htaccess`:

```
# Block dot-directories like .git
RedirectMatch 404 /\.(git|idea|docker|phpunit)(/|$)

# Block common sensitive files
<FilesMatch "^(composer\.(json|lock)|\.php-cs-fixer\.(php|cache)|phpstan\.neon|phpmd\.xml|psalm\.xml|rector\.php|infection\.json5|.*\.sql)$">
    Require all denied
</FilesMatch>
```

- [ ] **Step 2: logs/.htaccess**

Neue Datei `logs/.htaccess`:

```
Require all denied
```

- [ ] **Step 3: phpinfo.php entfernen**

```bash
git rm phpinfo.php
```

- [ ] **Step 4: install.php – Lockfile-Check**

Am Anfang von `install.php` direkt nach dem include-Block (nach Zeile 34):

```php
$installLockFile = __DIR__ . '/pninc/install.lock';
if (isset($_POST['install']) && $_POST['install'] === 'YES' && file_exists($installLockFile)) {
    http_response_code(403);
    echo '<center><b>Installation bereits erfolgt. Zum Neuinstallieren bitte <code>pninc/install.lock</code> entfernen.</b></center>';
    exit;
}
```

Nach erfolgreicher Installation (am Ende des Installations-Blocks):

```php
@file_put_contents($installLockFile, 'installed ' . date('c'));
```

- [ ] **Step 5: update.php / convert.php – Admin-Auth**

Am Anfang von `update.php` und `convert.php` nach den includes:

```php
session_start();
@include __DIR__ . '/pnadmin/functions.inc.php';
if (!isset($_SESSION['pnadmin']) || $_SESSION['pnadmin'] !== 'YES') {
    http_response_code(403);
    echo '<center><b>Nur Admins. Bitte im <a href="./pnadmin/">Adminbereich</a> einloggen.</b></center>';
    exit;
}
```

- [ ] **Step 6: Tests**

Run:
```bash
curl -s -o /dev/null -w "%{http_code}\n" http://localhost:8087/phpinfo.php  # 404
curl -s -o /dev/null -w "%{http_code}\n" http://localhost:8087/.git/config  # 404
curl -s -o /dev/null -w "%{http_code}\n" http://localhost:8087/logs/php-error.log  # 403
curl -s -o /dev/null -w "%{http_code}\n" -X POST http://localhost:8087/install.php -d "install=YES"  # 403 nach Lockfile
curl -s -o /dev/null -w "%{http_code}\n" http://localhost:8087/update.php  # 403
curl -s -o /dev/null -w "%{http_code}\n" http://localhost:8087/convert.php  # 403
curl -s -o /dev/null -w "%{http_code}\n" http://localhost:8087/composer.json  # 403
```
Expected: alle genannten HTTP-Codes.

- [ ] **Step 7: Commit**

```bash
git rm phpinfo.php
git add .htaccess logs/.htaccess install.php update.php convert.php
git commit -m "fix(infra): harden public endpoints – htaccess, lockfile, auth gates (BUG-040..045)"
```

---

## Task 8: Input-Validierung für Nickname, E-Mail, URL, Age, Length (BUG-006, BUG-007, BUG-008, BUG-016, BUG-017, BUG-018, BUG-019, BUG-034)

**Files:**
- Modify: `pninc/validation.inc.php`
- Modify: `pninc/functions.inc.php` (register, profile)

- [ ] **Step 1: Validation-Helper ergänzen**

In `pninc/validation.inc.php` am Ende ergänzen:

```php
function pn_validate_nickname(string $nickname): string|false
{
    $trimmed = trim($nickname);
    if (!preg_match('/^[A-Za-zÄÖÜäöüß0-9_.\-]{3,30}$/u', $trimmed)) {
        return false;
    }
    return $trimmed;
}

function pn_validate_email(string $email): string|false
{
    $trimmed = trim($email);
    if (!filter_var($trimmed, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    if (mb_strlen($trimmed) > 250) {
        return false;
    }
    return $trimmed;
}

function pn_validate_url(string $url): string|false
{
    $trimmed = trim($url);
    if ($trimmed === '') {
        return '';
    }
    if (!preg_match('#^https?://#i', $trimmed)) {
        $trimmed = 'https://' . $trimmed;
    }
    if (!filter_var($trimmed, FILTER_VALIDATE_URL)) {
        return false;
    }
    if (mb_strlen($trimmed) > 250) {
        return false;
    }
    return $trimmed;
}

function pn_validate_age($age): int|false
{
    if ($age === '' || $age === null) {
        return 0;
    }
    if (!is_numeric($age)) {
        return false;
    }
    $a = (int) $age;
    if ($a < 0 || $a > 150) {
        return false;
    }
    return $a;
}

function pn_validate_length(string $value, int $max): string|false
{
    if (mb_strlen($value) > $max) {
        return false;
    }
    return $value;
}

function pn_validate_icq($icq): int
{
    if (!is_numeric($icq)) {
        return 0;
    }
    $v = (int) $icq;
    return $v < 0 ? 0 : $v;
}
```

- [ ] **Step 2: register() mit Validation**

In `pn_user::register()` ersetze die Validierungs-Blöcke (um Zeile 660–680):

```php
$nickname = pn_validate_nickname($_POST['pndata']['nickname'] ?? '');
$email = pn_validate_email($_POST['pndata']['email'] ?? '');

if ($nickname === false || $email === false) {
    $template->message(L_USR_NICKNAMEINVALID ?? 'Nickname muss 3-30 Zeichen, nur Buchstaben/Ziffern/._- enthalten. E-Mail muss gültig sein.', 'javascript:history.back()');
    return;
}
// weiter wie bisher mit den validierten Werten
```

- [ ] **Step 3: profile() mit Validation**

In `pn_user::profile()` (Zeile 868+) ersetze den Validierungs-Block (um Zeile 878–905):

```php
$nickname = pn_validate_nickname($_POST['pndata']['nickname'] ?? '');
$email = pn_validate_email($_POST['pndata']['email'] ?? '');
$password = $_POST['pndata']['password'] ?? '';
$password2 = $_POST['pndata']['password2'] ?? '';
$realname = pn_validate_length(trim($_POST['pndata']['realname'] ?? ''), 100);
$city = pn_validate_length(trim($_POST['pndata']['city'] ?? ''), 100);
$age = pn_validate_age($_POST['pndata']['age'] ?? 0);
$homepage = pn_validate_url($_POST['pndata']['homepage'] ?? '');
$icq = pn_validate_icq($_POST['pndata']['icq'] ?? 0);

if ($nickname === false || $email === false || $realname === false || $city === false || $age === false || $homepage === false) {
    $template->message('Ungültige Eingabe. Bitte prüfe Nickname, E-Mail, Alter und Homepage.', 'javascript:history.back()');
    return;
}
```

- [ ] **Step 4: Tests – negative Fälle**

Run:
```bash
# whitespace-nick
curl -s -X POST "http://localhost:8087/user.php?pndata%5Bsend%5D=YES" \
  --data-urlencode "pndata[nickname]=   " --data-urlencode "pndata[email]=ws@example.com" | grep -c "Ungültig"
# XSS-nick
curl -s -X POST "http://localhost:8087/user.php?pndata%5Bsend%5D=YES" \
  --data-urlencode "pndata[nickname]=<script>" --data-urlencode "pndata[email]=xss@example.com" | grep -c "Ungültig"
# zu lang
curl -s -X POST "http://localhost:8087/user.php?pndata%5Bsend%5D=YES" \
  --data-urlencode "pndata[nickname]=$(printf 'x%.0s' {1..300})" --data-urlencode "pndata[email]=long@example.com" | grep -c "Ungültig"
```
Expected: jeweils `1` (Validierungsfehler statt Insert)

- [ ] **Step 5: Commit**

```bash
git add pninc/validation.inc.php pninc/functions.inc.php
git commit -m "fix(validation): strict input validation for register/profile (BUG-006..008, 016..019)"
```

---

## Task 9: Profil-Update ohne Passwort-Zwang + Deaktivierter User + Status-Check (BUG-014, BUG-015)

**Files:**
- Modify: `pninc/functions.inc.php`

- [ ] **Step 1: Profile – Passwort optional**

In `pn_user::profile()` (neu nach Task 8), die Passwort-Pflicht entfernen:

```php
// Alte Prüfung `if (!$nickname || !$email || !$password || !$password2)` ist nach Validation weg.
// Stattdessen:
if ($password !== '' || $password2 !== '') {
    if ($password !== $password2) {
        $template->message(L_USR_PASSNOTEQUAL, 'javascript:history.back()');
        return;
    }
    if (strlen($password) < 8) {
        $template->message('Passwort muss mindestens 8 Zeichen haben.', 'javascript:history.back()');
        return;
    }
    $hashedPassword = pn_hash_password($password);
    $updatePwSql = ', password = ?';
} else {
    $hashedPassword = null;
    $updatePwSql = '';
}

$showemail = ($_POST['pndata']['showemail'] ?? 'NO') === 'YES' ? 'YES' : 'NO';

$sql = 'UPDATE ' . $pn_config['usertable']
    . ' SET nickname = ?, email = ?, showemail = ?, realname = ?, city = ?, age = ?, homepage = ?, icq = ?' . $updatePwSql
    . ' WHERE id = ?';

$stmt = mysqli_prepare($pn_handler, $sql);
$userId = (int) $pnuser['id'];
if ($hashedPassword !== null) {
    mysqli_stmt_bind_param($stmt, 'sssssisisi', $nickname, $email, $showemail, $realname, $city, $age, $homepage, $icq, $hashedPassword, $userId);
} else {
    mysqli_stmt_bind_param($stmt, 'sssssisii', $nickname, $email, $showemail, $realname, $city, $age, $homepage, $icq, $userId);
}
mysqli_stmt_execute($stmt);

$template->message(L_USR_PROFILEEDITED, $pn_config['userfile'] . '?page=profile');
```

- [ ] **Step 2: Login – Deactivated blocken**

`setusercookie()` (bereits in Task 3 geändert) prüft nun `status === 'Deactivated'` und verweigert den Login.

Zusätzlich in `pn_user::login()` (Zeile 774+) die Meldung vereinheitlichen – kommt im nächsten Task.

- [ ] **Step 3: Tests**

Run:
```bash
# Profile ohne neues Passwort aktualisieren
COOKIE=... # Gültiger Cookie aus Login
curl -s -b "pncookie=$COOKIE" -X POST "http://localhost:8087/user.php?page=profile&pndata%5Bsend%5D=YES" \
  --data-urlencode "pndata[nickname]=qauser1" --data-urlencode "pndata[email]=qauser1@example.com" \
  --data-urlencode "pndata[password]=" --data-urlencode "pndata[password2]=" \
  --data-urlencode "pndata[realname]=Test QA" --data-urlencode "pndata[age]=30" | grep -c "erfolgreich"
# Deactivated-Login
docker exec powernews_db mariadb -upowernews -ppowernews powernews -e "UPDATE pn_users SET status='Deactivated' WHERE nickname='qauser1';"
curl -s -X POST "http://localhost:8087/user.php?page=login&pndata%5Blogin%5D=YES" \
  --data-urlencode "pndata[nickname]=qauser1" --data-urlencode "pndata[password]=Test1234!" | grep -c "nicht korrekt\|nicht gefunden"
docker exec powernews_db mariadb -upowernews -ppowernews powernews -e "UPDATE pn_users SET status='Activated' WHERE nickname='qauser1';"
```
Expected: 1 / 1

- [ ] **Step 4: Commit**

```bash
git add pninc/functions.inc.php
git commit -m "fix(profile): optional password, reject deactivated users (BUG-014, 015)"
```

---

## Task 10: Login/Senddata – einheitliche Fehler + Constant-Time + Rate-Limit (BUG-010, BUG-011, BUG-021, BUG-046)

**Files:**
- Modify: `pninc/functions.inc.php`
- Modify: `powernews.sql` (pn_login_attempts)

- [ ] **Step 1: Login-Attempts-Tabelle**

In `powernews.sql` und `pn_update.sql`:

```sql
DROP TABLE IF EXISTS `pn_login_attempts`;
CREATE TABLE `pn_login_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` varchar(64) NOT NULL,
  `nickname` varchar(100) NOT NULL,
  `success` enum('YES','NO') NOT NULL DEFAULT 'NO',
  `attempted_at` int(14) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ip_time` (`ip`, `attempted_at`),
  KEY `idx_nick_time` (`nickname`, `attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

- [ ] **Step 2: login() einheitliche Meldung + Rate-Limit + Constant-Time**

Ersetze `pn_user::login()`:

```php
public function login(): void
{
    global $pn_config, $pn_handler;

    $template = new pn_template();
    $loginFlag = $_GET['pndata']['login'] ?? '';

    if ($loginFlag !== 'YES') {
        $template->loginform();
        return;
    }

    $nickname = trim($_POST['pndata']['nickname'] ?? '');
    $password = $_POST['pndata']['password'] ?? '';
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
    $ip = substr(explode(',', $ip)[0], 0, 64);

    if ($nickname === '' || $password === '') {
        $template->message(L_ALL_FILLALL, 'javascript:history.back()');
        return;
    }

    $window = time() - 900; // 15 Min
    $stmt = mysqli_prepare($pn_handler, 'SELECT COUNT(*) FROM pn_login_attempts WHERE (ip = ? OR nickname = ?) AND success = \'NO\' AND attempted_at > ?');
    mysqli_stmt_bind_param($stmt, 'ssi', $ip, $nickname, $window);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    [$failedCount] = mysqli_fetch_array($result);
    if ($failedCount >= 10) {
        $template->message('Zu viele Fehlversuche. Bitte in 15 Minuten erneut versuchen.', 'javascript:history.back()');
        return;
    }

    $stmt = mysqli_prepare($pn_handler, 'SELECT * FROM ' . $pn_config['usertable'] . ' WHERE nickname = ?');
    mysqli_stmt_bind_param($stmt, 's', $nickname);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $valid = false;
    if (mysqli_num_rows($result) === 1) {
        $row = mysqli_fetch_array($result);
        if ($row['status'] === 'Activated' && pn_verify_password($password, $row['password'], (int) $row['id'])) {
            $valid = true;
        }
    } else {
        // constant-time dummy
        password_verify($password, '$2y$12$dummydummydummydummydummydummydummydummydummydummy.');
    }

    $now = time();
    $success = $valid ? 'YES' : 'NO';
    $stmt = mysqli_prepare($pn_handler, 'INSERT INTO pn_login_attempts (ip, nickname, success, attempted_at) VALUES (?, ?, ?, ?)');
    mysqli_stmt_bind_param($stmt, 'sssi', $ip, $nickname, $success, $now);
    mysqli_stmt_execute($stmt);

    if ($valid) {
        $template->message(L_USR_LOGGEDIN, $pn_config['userfile'] . '?page=profile');
    } else {
        $template->message('Nickname oder Passwort ist nicht korrekt.', 'javascript:history.back()');
    }
}
```

- [ ] **Step 3: senddata() einheitliche Meldung + kein Passwort-Reset vor Mailversand + CAPTCHA-like Rate-Limit (BUG-020 teilw., BUG-021, BUG-022)**

Ersetze `pn_user::senddata()`:

```php
public function senddata(): void
{
    global $pn_config, $pn_handler;

    $template = new pn_template();
    $search = trim($_POST['pndata']['searchstring'] ?? '');
    $genericMsg = 'Wenn ein Account mit diesen Daten existiert, haben wir eine E-Mail an die hinterlegte Adresse versendet.';

    if ($search === '') {
        $template->senddataform();
        return;
    }

    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
    $ip = substr(explode(',', $ip)[0], 0, 64);
    $window = time() - 3600;
    $stmt = mysqli_prepare($pn_handler, 'SELECT COUNT(*) FROM pn_login_attempts WHERE ip = ? AND success = \'NO\' AND attempted_at > ?');
    mysqli_stmt_bind_param($stmt, 'si', $ip, $window);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    [$rpHourly] = mysqli_fetch_array($result);
    if ($rpHourly > 20) {
        $template->message('Zu viele Passwort-Reset-Anfragen. Bitte später erneut versuchen.', 'javascript:history.back()');
        return;
    }

    $isEmail = filter_var($search, FILTER_VALIDATE_EMAIL) !== false;
    $sql = $isEmail
        ? 'SELECT nickname, email FROM ' . $pn_config['usertable'] . ' WHERE email = ?'
        : 'SELECT nickname, email FROM ' . $pn_config['usertable'] . ' WHERE nickname = ?';
    $stmt = mysqli_prepare($pn_handler, $sql);
    mysqli_stmt_bind_param($stmt, 's', $search);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $now = time();
    $stmt2 = mysqli_prepare($pn_handler, 'INSERT INTO pn_login_attempts (ip, nickname, success, attempted_at) VALUES (?, ?, \'NO\', ?)');
    mysqli_stmt_bind_param($stmt2, 'ssi', $ip, $search, $now);
    mysqli_stmt_execute($stmt2);

    if (mysqli_num_rows($result) === 1) {
        $row = mysqli_fetch_array($result);
        $newPassword = $this->generate_password();
        $pnemail = new pn_email();

        if ($pnemail->dataemail($row['nickname'], $row['email'], $newPassword)) {
            $hashed = pn_hash_password($newPassword);
            $stmt3 = mysqli_prepare($pn_handler, 'UPDATE ' . $pn_config['usertable'] . ' SET password = ? WHERE nickname = ?');
            mysqli_stmt_bind_param($stmt3, 'ss', $hashed, $row['nickname']);
            mysqli_stmt_execute($stmt3);
        }
    }

    $template->message($genericMsg, $pn_config['userfile'] . '?page=login');
}
```

Wichtig: DB-Update **erst nach erfolgreichem Mailversand**. Verhindert Account-Lockout-DoS aus BUG-020.

- [ ] **Step 4: Tests**

Run:
```bash
# Unbekannter user: selbe Meldung wie bekannter
curl -s -X POST "http://localhost:8087/user.php?page=login&pndata%5Blogin%5D=YES" \
  --data-urlencode "pndata[nickname]=existiertnicht" --data-urlencode "pndata[password]=xxx" | grep -c "Nickname oder Passwort ist nicht korrekt"
curl -s -X POST "http://localhost:8087/user.php?page=login&pndata%5Blogin%5D=YES" \
  --data-urlencode "pndata[nickname]=qauser1" --data-urlencode "pndata[password]=falsch" | grep -c "Nickname oder Passwort ist nicht korrekt"
# senddata generic
curl -s -X POST "http://localhost:8087/user.php?page=senddata" --data-urlencode "pndata[searchstring]=gibtsnicht" | grep -c "Wenn ein Account"
curl -s -X POST "http://localhost:8087/user.php?page=senddata" --data-urlencode "pndata[searchstring]=qauser1" | grep -c "Wenn ein Account"
```
Expected: jeweils `1`.

- [ ] **Step 5: Commit**

```bash
git add pninc/functions.inc.php powernews.sql pn_update.sql
git commit -m "fix(auth): unified errors, rate limiting, constant-time login, safe password reset (BUG-010, 011, 020 part, 021, 046)"
```

---

## Task 11: Logout – POST + CSRF-Token (BUG-024)

**Files:**
- Modify: `pninc/functions.inc.php` (logout())
- Modify: `pninc/head.inc.php` (logout-Handler)
- Modify: Logout-Template in DB

- [ ] **Step 1: CSRF-Token-Helper**

In `pninc/validation.inc.php` ergänzen:

```php
function pn_csrf_token(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function pn_csrf_verify(string $token): bool
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}
```

- [ ] **Step 2: logout() als POST-Form**

In `pn_template::logout()` (Zeile 1497+) das Template so umbauen (DB-Update):

```sql
UPDATE pn_templates SET logout = REPLACE(logout,
  '<a href="user.php?page=logout&pndata[logout]=YES">Ausloggen</a>',
  '<form method="POST" action="user.php?page=logout"><input type="hidden" name="csrf_token" value="{CSRF}"><input type="submit" value="Ausloggen"></form>'
) WHERE id = 1;
```

Im PHP-Template-Render in `pn_template::logout()` den `{CSRF}`-Placeholder ersetzen:

```php
$logout = preg_replace('!{CSRF}!', pn_csrf_token(), $logout);
```

- [ ] **Step 3: Logout-Handler in head.inc.php**

Ersetze den `logout`-Branch (ca. Zeile 75):

```php
elseif (isset($_GET['page']) && $_GET['page'] === 'logout'
    && $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['csrf_token'])
    && pn_csrf_verify($_POST['csrf_token'])
    && isset($_COOKIE['pncookie']) && $_COOKIE['pncookie']) {
    $pnuserlogout = new pn_user();
    $pnuserlogout->delusercookie();
    unset($pnuser, $_COOKIE['pncookie']);
    $pnuser['loggedin'] = 'NO';
}
```

- [ ] **Step 4: Test GET-Logout abgewiesen**

Run:
```bash
curl -s -b "pncookie=<gültig>" "http://localhost:8087/user.php?page=logout&pndata%5Blogout%5D=YES" | grep -c "Logout\|ausloggen"
# Cookie sollte noch gültig sein
```
Expected: 1 (Bestätigungsseite angezeigt, nicht ausgeloggt)

- [ ] **Step 5: Commit**

```bash
git add pninc/validation.inc.php pninc/functions.inc.php pninc/head.inc.php
git commit -m "fix(csrf): logout requires POST + CSRF token (BUG-024)"
```

---

## Task 12: CSRF auf alle User-Formulare (IMP-003)

**Files:**
- Modify: Templates in DB (registerform, loginform, senddataform, profileform, commentform, sendnewsform)
- Modify: `pninc/functions.inc.php` (alle POST-Handler)

- [ ] **Step 1: CSRF-Placeholder in Templates**

SQL-Script `csrf-templates.sql`:

```sql
UPDATE pn_templates SET
  registerform = REPLACE(registerform, '</form>', '<input type="hidden" name="csrf_token" value="{CSRF}"></form>'),
  loginform = REPLACE(loginform, '</form>', '<input type="hidden" name="csrf_token" value="{CSRF}"></form>'),
  senddataform = REPLACE(senddataform, '</form>', '<input type="hidden" name="csrf_token" value="{CSRF}"></form>'),
  profileform = REPLACE(profileform, '</form>', '<input type="hidden" name="csrf_token" value="{CSRF}"></form>'),
  commentform = REPLACE(commentform, '</form>', '<input type="hidden" name="csrf_token" value="{CSRF}"></form>'),
  sendnewsform = REPLACE(sendnewsform, '</form>', '<input type="hidden" name="csrf_token" value="{CSRF}"></form>')
WHERE id = 1;
```

Ausführen + in `powernews.sql` die Templates entsprechend patchen.

- [ ] **Step 2: CSRF-Platzhalter in Render-Funktionen ersetzen**

In `pn_template::registerform()`, `loginform()`, `senddataform()`, `profileform()`, `commentform()`, `sendnewsform()` vor dem `echo`:

```php
$output = preg_replace('!{CSRF}!', pn_csrf_token(), $output);
```

Am Beispiel von registerform:

```php
[$registerform] = mysqli_fetch_array($result);
$registerform = preg_replace('!{CSRF}!', pn_csrf_token(), (string) $registerform);
echo $registerform;
```

- [ ] **Step 3: CSRF-Verifikation in Handlern**

Am Anfang jedes POST-Handlers (register, login, profile, senddata, postcomment, sendnews):

```php
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && !pn_csrf_verify($_POST['csrf_token'] ?? '')) {
    $template->message('CSRF-Token ungültig. Bitte Seite neu laden.', 'javascript:history.back()');
    return;
}
```

- [ ] **Step 4: Test**

Run:
```bash
# POST ohne CSRF-Token abgelehnt
curl -s -X POST "http://localhost:8087/user.php?pndata%5Bsend%5D=YES" \
  --data-urlencode "pndata[nickname]=csrf_test" --data-urlencode "pndata[email]=csrf@example.com" | grep -c "CSRF-Token"
```
Expected: 1

- [ ] **Step 5: Commit**

```bash
git add pninc/functions.inc.php powernews.sql docs/superpowers/plans/csrf-templates.sql
git commit -m "feat(csrf): CSRF tokens on all user forms (IMP-003)"
```

---

## Task 13: Security-Header + Server-Tokens (BUG-004, IMP-006)

**Files:**
- Modify: `.htaccess` (oder `.docker/apache.conf`)

- [ ] **Step 1: Header in .htaccess ergänzen**

In `.htaccess` ergänzen:

```apache
<IfModule mod_headers.c>
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
    Header always set Permissions-Policy "geolocation=(), microphone=(), camera=()"
    Header always set Content-Security-Policy "default-src 'self'; img-src 'self' data: https:; style-src 'self' 'unsafe-inline'; script-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'"
    Header unset Server
    Header unset X-Powered-By
</IfModule>

ServerTokens Prod
ServerSignature Off
```

- [ ] **Step 2: PHP expose_php off (ist bereits gesetzt in php.ini)**

Verify in `.docker/php.ini`: `expose_php = Off` – ist bereits korrekt.

- [ ] **Step 3: Test**

Run:
```bash
curl -I http://localhost:8087/ | grep -iE "X-Content-Type|X-Frame|Referrer-Policy|CSP|Server"
```
Expected: Security-Header gelistet; Server-Banner ohne Version (`Apache`).

- [ ] **Step 4: Commit**

```bash
git add .htaccess
git commit -m "feat(security): add HTTP security headers, hide server version (BUG-004, IMP-006)"
```

---

## Task 14: Fatal-Error-Handling (BUG-002, BUG-012, BUG-013)

**Files:**
- Modify: `pninc/functions.inc.php` (register, checkcookie, usermenu)

- [ ] **Step 1: checkcookie defensiver**

Die Task-3-Variante ist bereits defensiv (`preg_match`-Check). Kein Crash mehr.

- [ ] **Step 2: register – Mail-Return prüfen**

In `pn_user::register()` nach Task 8 (bereits Validierung), wrap den INSERT in Transaktion:

```php
mysqli_begin_transaction($pn_handler);
try {
    $stmt = mysqli_prepare($pn_handler, 'INSERT INTO ' . $pn_config['usertable'] . ' (nickname, email, password, registered, showemail) VALUES(?, ?, ?, ?, ?)');
    mysqli_stmt_bind_param($stmt, 'sssis', $nickname, $email, $hashedPassword, $now, $showemail);
    mysqli_stmt_execute($stmt);

    $pemail = new pn_email();
    if (!$pemail->registeremail($nickname, $email, $password)) {
        throw new RuntimeException('mail send failed');
    }
    mysqli_commit($pn_handler);
    $template->message(L_USR_REGISTERED, $pn_config['userfile'] . '?page=login');
} catch (Throwable $e) {
    mysqli_rollback($pn_handler);
    error_log('[register] ' . $e->getMessage());
    $template->message('Registrierung fehlgeschlagen. Bitte später erneut versuchen.', 'javascript:history.back()');
}
```

- [ ] **Step 3: usermenu – Null-Check**

In `pn_user::usermenu()` (Zeile 855+):

```php
public function usermenu(): void
{
    global $pnuser;
    $template = new pn_template();

    if (isset($pnuser) && is_array($pnuser) && ($pnuser['loggedin'] ?? 'NO') === 'YES') {
        $template->usermenu2();
    } else {
        $template->usermenu();
    }
}
```

Analog in `pn_user::profile()` (Zeile 874) den `$pnuser['loggedin']`-Zugriff absichern.

- [ ] **Step 4: Test**

Run:
```bash
# Logout ohne Login (soll keine PHP-Warnings erzeugen)
: > logs/php-error.log
curl -s http://localhost:8087/user.php?page=logout > /dev/null
grep -c "Trying to access array offset on null" logs/php-error.log
```
Expected: `0`

- [ ] **Step 5: Commit**

```bash
git add pninc/functions.inc.php
git commit -m "fix(stability): transactional register, defensive usermenu/profile (BUG-002, 012, 013)"
```

---

## Task 15: Stored-XSS-Hardening (BUG-007) + addslashes raus (BUG-026)

**Files:**
- Modify: `pninc/functions.inc.php` (postcomment, sendnews)

- [ ] **Step 1: addslashes entfernen**

In `pn_news::postcomment()` Zeile 348:

```php
// alt: $text = addslashes($text);
// neu: direkt $text verwenden, Prepared-Statements erledigen Escaping
```

In `pn_news::sendnews()` Zeilen 546–548:

```php
$title = $_POST['pndata']['title'] ?? '';
$text = $_POST['pndata']['text'] ?? '';
$moretext = $_POST['pndata']['moretext'] ?? '';
```

- [ ] **Step 2: stripslashes bei Ausgabe entfernen**

In `pn_news::news()` Zeile 162, `pn_news::details()` Zeile 191:

```php
// alt:  stripslashes((string) $row['title'])
// neu:  (string) $row['title']
```

- [ ] **Step 3: Bestehende Slash-Daten säubern**

Migration-SQL (einmalig):

```sql
UPDATE pn_news SET
  title = REPLACE(REPLACE(title, '\\''', '\''), '\\"', '"'),
  text = REPLACE(REPLACE(text, '\\''', '\''), '\\"', '"'),
  moretext = REPLACE(REPLACE(moretext, '\\''', '\''), '\\"', '"')
WHERE title LIKE '%\\\\%' OR text LIKE '%\\\\%' OR moretext LIKE '%\\\\%';
UPDATE pn_comments SET text = REPLACE(REPLACE(text, '\\''', '\''), '\\"', '"');
```

- [ ] **Step 4: Test**

Run:
```bash
curl -s -X POST "http://localhost:8087/comments.php?newsid=1" --data-urlencode "text=It's great"
docker exec powernews_db mariadb -upowernews -ppowernews powernews -e "SELECT text FROM pn_comments ORDER BY id DESC LIMIT 1;"
```
Expected: `It's great` (ohne Backslash)

- [ ] **Step 5: Commit**

```bash
git add pninc/functions.inc.php
git commit -m "fix(quality): remove addslashes/stripslashes cruft (BUG-007 context, BUG-026)"
```

---

## Task 16: Sendnews-Default auf Registered + XSS-Harden (BUG-027)

**Files:**
- Modify: `powernews.sql` (pn_config default)

- [ ] **Step 1: newssending Default anpassen**

In `powernews.sql` im Initial-`INSERT INTO pn_config` ändere `newssending` auf `'Registered'` (statt `'Guests/Registered'`).

- [ ] **Step 2: Migration für bestehende Installs**

In `pn_update.sql`:

```sql
UPDATE pn_config SET newssending = 'Registered' WHERE newssending = 'Guests/Registered';
```

- [ ] **Step 3: Commit**

```bash
git add powernews.sql pn_update.sql
git commit -m "fix(default): sendnews requires registration by default (BUG-027)"
```

---

## Task 17: Initial-Kategorie seed + Kommentare FK (BUG-028, BUG-029)

**Files:**
- Modify: `powernews.sql`
- Modify: `pninc/functions.inc.php` (postcomment)

- [ ] **Step 1: Seed-Kategorie**

In `powernews.sql` nach `CREATE TABLE pn_categories`:

```sql
INSERT INTO pn_categories (id, name, description, picture, status) VALUES
(1, 'Allgemein', 'Standardkategorie für alle News', '', 'Activated');
```

- [ ] **Step 2: Kommentar – newsid-Existenz prüfen**

In `pn_news::postcomment()` am Anfang:

```php
$stmt = mysqli_prepare($pn_handler, 'SELECT id FROM ' . $pn_config['newstable'] . " WHERE id = ? AND status = 'Activated'");
mysqli_stmt_bind_param($stmt, 'i', $newsid);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
if (mysqli_num_rows($result) !== 1) {
    $template->message('News nicht gefunden.', 'javascript:history.back()');
    return;
}
```

- [ ] **Step 3: Test**

Run:
```bash
curl -s -X POST "http://localhost:8087/comments.php?newsid=99999" --data-urlencode "text=phantom" | grep -c "nicht gefunden"
```
Expected: `1`

- [ ] **Step 4: Commit**

```bash
git add powernews.sql pninc/functions.inc.php
git commit -m "fix(integrity): seed category, validate newsid on comments (BUG-028, 029)"
```

---

## Task 18: Kommentare – Länge + X-Forwarded-For (BUG-030, BUG-038)

**Files:**
- Modify: `pninc/functions.inc.php` (postcomment)

- [ ] **Step 1: Längen-Limit + X-Forwarded-For**

In `pn_news::postcomment()`:

```php
$maxLen = 5000;
if (mb_strlen($text) > $maxLen) {
    $template->message('Kommentar zu lang (max. ' . $maxLen . ' Zeichen).', 'javascript:history.back()');
    return;
}
$remoteAddr = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
$remoteAddr = substr(explode(',', $remoteAddr)[0], 0, 64);
```

- [ ] **Step 2: Test**

Run:
```bash
BIG=$(printf 'a%.0s' {1..6000})
printf 'text=%s' "$BIG" > /tmp/big.txt
curl -s -X POST "http://localhost:8087/comments.php?newsid=1" --data-binary @/tmp/big.txt -H "Content-Type: application/x-www-form-urlencoded" | grep -c "zu lang"
```
Expected: `1`

- [ ] **Step 3: Commit**

```bash
git add pninc/functions.inc.php
git commit -m "fix(comments): length limit + X-Forwarded-For IP (BUG-030, 038)"
```

---

## Task 19: Relatedlinks – sauberes JSON-Format (BUG-037)

**Files:**
- Modify: `pninc/functions.inc.php` (sendnews, relatedlinks-Render)

- [ ] **Step 1: Sendnews – JSON-Serialisierung**

In `pn_news::sendnews()` ersetze den Relatedlinks-Block:

```php
$linksArray = [];
if (isset($_POST['pndata']['rl_title']) && is_array($_POST['pndata']['rl_title'])) {
    $counter = count($_POST['pndata']['rl_title']);
    for ($i = 0; $i < $counter; $i++) {
        $t = trim((string) ($_POST['pndata']['rl_title'][$i] ?? ''));
        $u = trim((string) ($_POST['pndata']['rl_url'][$i] ?? ''));
        $target = ($_POST['pndata']['rl_target'][$i] ?? '_self') === '_blank' ? '_blank' : '_self';
        if ($t !== '' && $u !== '' && filter_var($u, FILTER_VALIDATE_URL) && preg_match('#^https?://#i', $u)) {
            $linksArray[] = ['title' => mb_substr($t, 0, 100), 'url' => mb_substr($u, 0, 250), 'target' => $target];
        }
    }
}
$relatedlinks = json_encode($linksArray, JSON_UNESCAPED_UNICODE);
```

- [ ] **Step 2: Relatedlinks-Render anpassen**

In `pn_template::news()` bei `{RELATEDLINKS}` und in `pn_template::relatedlinks()`:

```php
// In pn_template::news() vor echo $news:
$linksRendered = '';
$linksArr = json_decode((string) $relatedlinks, true) ?: [];
foreach ($linksArr as $link) {
    $linksRendered .= $this->relatedlinks($link['title'] ?? '', $link['url'] ?? '', $link['target'] ?? '_self');
}
$news = preg_replace('!{RELATEDLINKS}!', $linksRendered, $news);
```

- [ ] **Step 3: Migration bestehender Daten**

```sql
-- Alte !@!@!-Daten bleiben als Text, werden beim Render zu leerer Liste. Admins müssen neu pflegen.
UPDATE pn_news SET relatedlinks = '[]' WHERE relatedlinks LIKE '%!@!@!%';
```

- [ ] **Step 4: Commit**

```bash
git add pninc/functions.inc.php
git commit -m "fix(relatedlinks): JSON-encoded storage, URL whitelist (BUG-037)"
```

---

## Task 20: BBCode-[img] Host-Whitelist (BUG-048)

**Files:**
- Modify: `pninc/functions.inc.php` (bbreplace)

- [ ] **Step 1: bbreplace – img-Whitelist**

In `pn_news::bbreplace()` ersetze die `[img]`-Regel:

```php
$allowedImgHosts = '#^https?://(localhost|127\.0\.0\.1|'
  . preg_quote(parse_url($pnconfig['url'] ?? '', PHP_URL_HOST) ?? 'example.invalid', '#')
  . ')(/.*)?$#i';
$text = preg_replace_callback("!\[(?i)img\]([^\[\]<>\"']+)\[(?i)/img\]!",
    function ($m) use ($allowedImgHosts) {
        $url = $m[1];
        if (preg_match($allowedImgHosts, $url)) {
            return '<img src="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" alt="" border="0">';
        }
        return '[img]' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '[/img]';
    },
    (string) $text);
```

`global $pnconfig;` am Anfang der Methode ergänzen.

- [ ] **Step 2: Commit**

```bash
git add pninc/functions.inc.php
git commit -m "fix(bbcode): restrict [img] to own host (BUG-048)"
```

---

## Task 21: Login-Form Redirect + UX (BUG-032, BUG-033, BUG-047, IMP-029)

**Files:**
- Modify: Templates in DB (loginform, registerform, profileform) – Passwort-maxlength + autocomplete
- Modify: `pninc/functions.inc.php` (login – Redirect bei eingeloggtem User)

- [ ] **Step 1: login() redirect if logged in**

In `pn_user::login()` am Anfang:

```php
global $pnuser;
if (($pnuser['loggedin'] ?? 'NO') === 'YES' && $loginFlag !== 'YES') {
    header('Location: ' . $pn_config['userfile'] . '?page=profile');
    exit;
}
```

- [ ] **Step 2: Template-Updates für autocomplete + maxlength**

```sql
UPDATE pn_templates SET
  loginform = REPLACE(loginform, 'name="pndata[nickname]"', 'name="pndata[nickname]" autocomplete="username"'),
  loginform = REPLACE(loginform, 'maxlength="25"', 'maxlength="128"'),
  loginform = REPLACE(loginform, 'name="pndata[password]"', 'name="pndata[password]" autocomplete="current-password"'),
  registerform = REPLACE(registerform, 'name="pndata[nickname]"', 'name="pndata[nickname]" autocomplete="username"'),
  registerform = REPLACE(registerform, 'name="pndata[email]"', 'name="pndata[email]" autocomplete="email"'),
  profileform = REPLACE(profileform, 'name="pndata[password]"', 'name="pndata[password]" autocomplete="new-password"'),
  profileform = REPLACE(profileform, 'name="pndata[password2]"', 'name="pndata[password2]" autocomplete="new-password"'),
  profileform = REPLACE(profileform, 'maxlength="25"', 'maxlength="128"')
WHERE id = 1;
```

Gleichermaßen in `powernews.sql` die Initial-Template-Inserts patchen.

- [ ] **Step 3: Commit**

```bash
git add pninc/functions.inc.php powernews.sql
git commit -m "fix(ux): login redirect when logged in, autocomplete hints, password maxlength (BUG-032, 033, 047)"
```

---

## Task 22: Dokumentation – bugs.md als FIXED markieren

**Files:**
- Modify: `docs/2026-04-23-Userbereichs-bugs.md`

- [ ] **Step 1: Status-Update auf FIXED**

In `docs/2026-04-23-Userbereichs-bugs.md` in jedem BUG-Eintrag den Status von `Offen` auf `Fixed` ändern und `Nicht beheben` entfernen. Kurze Referenz auf Commit-Hash/Task ergänzen.

Beispiel für BUG-001:
```
- **Status:** Fixed (Task 1, msmtp → mailpit)
```

- [ ] **Step 2: Commit**

```bash
git add docs/2026-04-23-Userbereichs-bugs.md
git commit -m "docs: mark all 48 user-area bugs as fixed"
```

---

## Zusammenfassung / Self-Review

**Bug-Abdeckung:**
- Task 1: BUG-001, BUG-002 (teil)
- Task 2: BUG-003
- Task 3: BUG-009, BUG-023, BUG-036
- Task 4: BUG-005, BUG-034, BUG-035
- Task 5: BUG-025
- Task 6: BUG-039
- Task 7: BUG-040, BUG-041, BUG-042, BUG-043, BUG-044, BUG-045
- Task 8: BUG-006, BUG-007, BUG-008, BUG-016, BUG-017, BUG-018, BUG-019, BUG-034
- Task 9: BUG-014, BUG-015
- Task 10: BUG-010, BUG-011, BUG-020, BUG-021, BUG-022, BUG-046
- Task 11: BUG-024
- Task 12: IMP-003 (CSRF – deckt BUG-024 mit ab)
- Task 13: BUG-004, IMP-006
- Task 14: BUG-002, BUG-012, BUG-013
- Task 15: BUG-007, BUG-026
- Task 16: BUG-027
- Task 17: BUG-028, BUG-029
- Task 18: BUG-030, BUG-038
- Task 19: BUG-037
- Task 20: BUG-048
- Task 21: BUG-032, BUG-033, BUG-047

**Nicht explizit adressiert (Accessibility/UI – eigene Folge-Tasks):**
- BUG-031 (Formular-Labels)

Dieser Plan wird inline, autonomous, ohne Rückfrage ausgeführt.
