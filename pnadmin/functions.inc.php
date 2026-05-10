<?php
declare(strict_types=1);

/* PowerNews - PHP and MySQL based news script                          */
/* Copyright (c) 2001-2026 PowerScripts                                 */

/* MIT License - See LICENSE file for full license text                 */
/* https://github.com/schubertnico/PowerNews.git                        */

/**
 * Helper function to escape output for HTML (admin).
 */
function pnadmin_escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

/**
 * Check if password is legacy base64 encoded (not bcrypt).
 */
function pnadmin_is_legacy_password(string $hash): bool
{
    return !str_starts_with($hash, '$2y$') && !str_starts_with($hash, '$2a$') && !str_starts_with($hash, '$argon');
}

/**
 * Verify password with auto-upgrade from legacy base64 to bcrypt.
 */
function pnadmin_verify_password(string $password, string $storedHash, ?int $userId = null): bool
{
    global $pn_config, $pn_handler;

    if (pnadmin_is_legacy_password($storedHash)) {
        if (base64_encode($password) === $storedHash) {
            if ($userId !== null) {
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = mysqli_prepare($pn_handler, 'UPDATE ' . $pn_config['usertable'] . ' SET password = ? WHERE id = ?');

                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, 'si', $newHash, $userId);
                    mysqli_stmt_execute($stmt);
                }
            }

            return true;
        }

        return false;
    }

    return password_verify($password, $storedHash);
}

/**
 * Hash password using bcrypt.
 */
function pnadmin_hash_password(string $password): string
{
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Prueft, ob der aktuelle pncookie einen eingeloggten Admin mit canwriteconfig=YES
 * in pn_permissions hinterlegt. Gibt das kombinierte User+Permissions-Array zurueck
 * oder null.
 *
 * Wird von update.php und convert.php genutzt, um den Admin-Zugriff zu verifizieren,
 * ohne dass der volle phpheader-Flow benoetigt wird.
 */
function pnadmin_auth_check(): ?array
{
    global $pn_config, $pn_handler;

    if (empty($_COOKIE['pncookie'])) {
        return null;
    }

    $decoded = base64_decode((string) $_COOKIE['pncookie'], true);
    if ($decoded === false) {
        return null;
    }

    $parts = explode('@@@@@', $decoded, 2);
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

    // User muss Activated sein UND eine gueltige, nicht abgelaufene Session in pn_sessions haben.
    $stmt = mysqli_prepare(
        $pn_handler,
        'SELECT u.* FROM ' . $pn_config['usertable'] . ' u '
        . 'INNER JOIN pn_sessions s ON s.userid = u.id '
        . 'WHERE u.id = ? AND s.token_hash = ? AND s.expires > ? AND u.status = ' . "'Activated'"
    );
    if (!$stmt) {
        return null;
    }
    mysqli_stmt_bind_param($stmt, 'isi', $userId, $tokenHash, $now);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if (mysqli_num_rows($result) !== 1) {
        return null;
    }
    $user = mysqli_fetch_array($result);

    // Permissions laden.
    $pstmt = mysqli_prepare($pn_handler, 'SELECT * FROM ' . $pn_config['permissionstable'] . ' WHERE userid = ?');
    if (!$pstmt) {
        return null;
    }
    mysqli_stmt_bind_param($pstmt, 'i', $userId);
    mysqli_stmt_execute($pstmt);
    $presult = mysqli_stmt_get_result($pstmt);
    if (mysqli_num_rows($presult) !== 1) {
        return null;
    }
    $perms = mysqli_fetch_array($presult);

    // Admin-Kriterium fuer update/convert: canwriteconfig=YES ist Pflicht (admins haben eh alle YES).
    if (($perms['canwriteconfig'] ?? 'NO') !== 'YES') {
        return null;
    }

    return array_merge(is_array($user) ? $user : [], is_array($perms) ? $perms : []);
}

// Function for checking logindata
class login
{
    public function checklogin(string $nickname, string $password): string
    {
        global $pn_handler, $pn_config;

        $error = '';
        $stmt = mysqli_prepare($pn_handler, 'SELECT * FROM ' . $pn_config['usertable'] . ' WHERE nickname = ?');
        mysqli_stmt_bind_param($stmt, 's', $nickname);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $num = mysqli_num_rows($result);

        if ($num == 1) {
            $row = mysqli_fetch_array($result);

            if (pnadmin_verify_password($password, $row['password'], (int) $row['id'])) {
                $error = $this->checkpermissions((int) $row['id']);

                if ($error === '' || $error === '0') {
                    $this->logincookie((int) $row['id'], $row['password']);
                    $error = 'loggedin';
                }
            } else {
                $error = L_USR_WRONGPW;
            }
        } else {
            $error = L_USR_NOUSR;
        }

        return $error;
    }

    public function checkpermissions(int $userid): string
    {
        global $pn_config, $pn_handler;

        $stmt = mysqli_prepare($pn_handler, 'SELECT * FROM ' . $pn_config['permissionstable'] . ' WHERE userid = ?');
        mysqli_stmt_bind_param($stmt, 'i', $userid);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $num = mysqli_num_rows($result);

        if ($num != 1) {
            return L_USR_NOADMIN;
        }

        return '';
    }

    public function logincookie(int $userid, string $password): void
    {
        global $pncookie, $pn_handler;

        // Neue Session (analog pn_user::setusercookie): userId:token in pn_sessions speichern,
        // Cookie weiterhin als base64(userid@@@@@token) schreiben, damit phpheader.inc.php
        // das Format parsen kann. Das Passwort-Argument wird nicht mehr im Cookie abgelegt.
        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $now = time();
        $expires = $now + 360 * 24 * 3600;
        $ua = substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
        $ip = substr((string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? ''), 0, 64);
        $ip = substr(explode(',', $ip)[0], 0, 64);

        $stmt = mysqli_prepare($pn_handler, 'INSERT INTO pn_sessions (userid, token_hash, created, expires, user_agent, ip) VALUES (?, ?, ?, ?, ?, ?)');
        mysqli_stmt_bind_param($stmt, 'isiiss', $userid, $tokenHash, $now, $expires, $ua, $ip);
        mysqli_stmt_execute($stmt);

        $cookiestring = base64_encode($userid . '@@@@@' . $token);

        setcookie('pncookie', $cookiestring, [
            'expires' => $expires,
            'path' => '/',
            'secure' => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Strict',
        ]);

        $pncookie = $cookiestring;
    }

    public function logout(): void
    {
        global $pn_handler;

        // Session-Row in pn_sessions entfernen, falls Cookie vorhanden.
        if (!empty($_COOKIE['pncookie'])) {
            $decoded = base64_decode((string) $_COOKIE['pncookie'], true);
            if ($decoded !== false) {
                $parts = explode('@@@@@', $decoded, 2);
                if (count($parts) === 2 && preg_match('/^[a-f0-9]{64}$/', $parts[1])) {
                    $tokenHash = hash('sha256', $parts[1]);
                    $stmt = mysqli_prepare($pn_handler, 'DELETE FROM pn_sessions WHERE token_hash = ?');
                    if ($stmt) {
                        mysqli_stmt_bind_param($stmt, 's', $tokenHash);
                        mysqli_stmt_execute($stmt);
                    }
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
    }
}

//###############################################################################################

class template
{
    public function addemail(string $nickname, string $email, string $password): string|false
    {
        global $pnconfig, $pn_config, $pn_handler;

        $templateId = (int) $pnconfig['template'];
        $stmt = mysqli_prepare($pn_handler, 'SELECT addemail FROM ' . $pn_config['templatetable'] . ' WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'i', $templateId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $num = mysqli_num_rows($result);

        if ($num == 1) {
            [$addemail] = mysqli_fetch_array($result);
            $addemail = preg_replace('!{NICKNAME}!', $nickname, (string) $addemail);
            $addemail = preg_replace('!{EMAIL}!', $email, $addemail);
            $addemail = preg_replace('!{PASSWORD}!', $password, $addemail);

            return preg_replace('!{URL}!', (string) $pnconfig['url'], $addemail);
        }

        return false;
    }

    public function editemail(string $nickname, string $email, string $password): string|false
    {
        global $pnconfig, $pn_config, $pn_handler;

        $templateId = (int) $pnconfig['template'];
        $stmt = mysqli_prepare($pn_handler, 'SELECT editemail FROM ' . $pn_config['templatetable'] . ' WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'i', $templateId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $num = mysqli_num_rows($result);

        if ($num == 1) {
            [$editemail] = mysqli_fetch_array($result);
            $editemail = preg_replace('!{NICKNAME}!', $nickname, (string) $editemail);
            $editemail = preg_replace('!{EMAIL}!', $email, $editemail);
            $editemail = preg_replace('!{PASSWORD}!', $password, $editemail);

            return preg_replace('!{URL}!', (string) $pnconfig['url'], $editemail);
        }

        return false;
    }

    public function addtemplate(): void
    {
        global $pn_config, $pn_handler;
        $pndata = $_POST['pndata'] ?? [];

        if (empty($pndata['title'])) {
            ?>
            <div class="alert alert-warning" role="alert">
                <?php echo L_TEMPL_TITLENEEDED; ?>
                <div class="mt-2"><a href="index.php?page=templates&amp;subpage=add" class="btn btn-sm btn-outline-secondary">Zur&uuml;ck zum Formular</a></div>
            </div>
            <?php
        } else {
            $result = mysqli_query($pn_handler, 'SELECT * FROM ' . $pn_config['templatetable'] . " WHERE id = '1'");
            $num = mysqli_num_rows($result);

            if ($num == 1) {
                $title = $pndata['title'];
                $stmt = mysqli_prepare($pn_handler, 'SELECT * FROM ' . $pn_config['templatetable'] . ' WHERE title = ?');
                mysqli_stmt_bind_param($stmt, 's', $title);
                mysqli_stmt_execute($stmt);
                $res = mysqli_stmt_get_result($stmt);
                $nu = mysqli_num_rows($res);

                if ($nu == 0) {
                    $row = mysqli_fetch_array($result);
                    $stmt = mysqli_prepare($pn_handler, 'INSERT INTO ' . $pn_config['templatetable'] . ' (title, message, headline, news, comment, usermenu, usermenu2, relatedlinks, commentform, registerform, loginform, logout, senddataform, profileform, archive, sendnewsform, addemail, editemail, registeremail, dataemail) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                    mysqli_stmt_bind_param(
                        $stmt,
                        'ssssssssssssssssssss',
                        $title,
                        $row['message'],
                        $row['headline'],
                        $row['news'],
                        $row['comment'],
                        $row['usermenu'],
                        $row['usermenu2'],
                        $row['relatedlinks'],
                        $row['commentform'],
                        $row['registerform'],
                        $row['loginform'],
                        $row['logout'],
                        $row['senddataform'],
                        $row['profileform'],
                        $row['archive'],
                        $row['sendnewsform'],
                        $row['addemail'],
                        $row['editemail'],
                        $row['registeremail'],
                        $row['dataemail'],
                    );
                    mysqli_stmt_execute($stmt);
                    ?>
                    <div class="alert alert-success" role="alert">
                        <?php echo L_TEMPL_TEMPLATEADDED; ?>
                        <div class="mt-2"><a href="index.php?page=templates&amp;subpage=show" class="btn btn-sm btn-success">Zur&uuml;ck zur Liste</a></div>
                    </div>
                    <?php
                } else {
                    ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo L_TEMPL_TEMPLATEALREADYEXISTS; ?>
                        <div class="mt-2"><a href="index.php?page=templates&amp;subpage=add" class="btn btn-sm btn-outline-secondary">Zur&uuml;ck zum Formular</a></div>
                    </div>
                    <?php
                }
            } else {
                ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo L_TEMPL_NOSTANDARDTEMPLATE; ?>
                    <div class="mt-2"><a href="index.php?page=templates" class="btn btn-sm btn-outline-secondary">Zur&uuml;ck</a></div>
                </div>
                <?php
            }
        }
    }

    public function listtemplates(): void
    {
        global $pn_config, $pn_handler;

        $result = mysqli_query($pn_handler, 'SELECT id, title FROM ' . $pn_config['templatetable']);
        $num = mysqli_num_rows($result);

        if ($num > 0) {
            while ($row = mysqli_fetch_array($result)) {
                ?>
                <tr>
                    <td><a href="index.php?page=templates&amp;subpage=edit&amp;templateid=<?php echo (int) $row['id']; ?>"><?php echo pnadmin_escape($row['title']); ?></a></td>
                </tr>
                <?php
            }
        } else {
            ?><tr><td class="text-center text-muted"><?php echo L_TEMPL_NOTEMPLATES; ?></td></tr><?php
        }
    }

    public function edittemplate(int $templateid, string $delete, TemplateData $data): void
    {
        global $pn_config, $pn_handler;

        if (!$data->isValid()) {
            ?>
            <div class="alert alert-danger" role="alert">
                <?php echo L_TEMPL_INSERTALL; ?>
                <div class="mt-2"><a href="index.php?page=templates&amp;subpage=edit&amp;templateid=<?php echo $templateid; ?>" class="btn btn-sm btn-outline-secondary">Zur&uuml;ck zum Formular</a></div>
            </div>
            <?php
        } else {
            // Default-Template (id=1) darf editiert werden, aber NICHT geloescht.
            if ($templateid === 1 && $delete === 'YES') {
                ?>
                <div class="alert alert-warning" role="alert">
                    Das Default-Template (ID&nbsp;1) kann nicht gel&ouml;scht werden, weil es als Vorlage f&uuml;r alle neuen Templates dient. Du kannst es weiterhin editieren.
                    <div class="mt-2"><a href="index.php?page=templates&amp;subpage=edit&amp;templateid=1" class="btn btn-sm btn-outline-secondary">Zur&uuml;ck zum Formular</a></div>
                </div>
                <?php
            } else {
                if ($delete === 'YES') {
                    $stmt = mysqli_prepare($pn_handler, 'DELETE FROM ' . $pn_config['templatetable'] . ' WHERE id = ?');
                    mysqli_stmt_bind_param($stmt, 'i', $templateid);
                    mysqli_stmt_execute($stmt);
                    ?>
                    <div class="alert alert-success" role="alert">
                        <?php echo L_TEMPL_TEMPLATEDELETED; ?>
                        <div class="mt-2"><a href="index.php?page=templates&amp;subpage=show" class="btn btn-sm btn-success">Zur&uuml;ck zur Liste</a></div>
                    </div>
                    <?php
                } else {
                    $stmt = mysqli_prepare($pn_handler, 'UPDATE ' . $pn_config['templatetable'] . ' SET title = ?, message = ?, headline = ?, news = ?, comment = ?, usermenu = ?, usermenu2 = ?, relatedlinks = ?, commentform = ?, registerform = ?, loginform = ?, logout = ?, senddataform = ?, profileform = ?, archive = ?, sendnewsform = ?, addemail = ?, editemail = ?, registeremail = ?, dataemail = ? WHERE id = ?');
                    $title = $data->title;
                    $message = $data->message;
                    $headline = $data->headline;
                    $news = $data->news;
                    $comment = $data->comment;
                    $usermenu = $data->usermenu;
                    $usermenu2 = $data->usermenu2;
                    $relatedlinks = $data->relatedlinks;
                    $commentform = $data->commentform;
                    $registerform = $data->registerform;
                    $loginform = $data->loginform;
                    $logout = $data->logout;
                    $senddataform = $data->senddataform;
                    $profileform = $data->profileform;
                    $archive = $data->archive;
                    $sendnewsform = $data->sendnewsform;
                    $addemail = $data->addemail;
                    $editemail = $data->editemail;
                    $registeremail = $data->registeremail;
                    $dataemail = $data->dataemail;
                    mysqli_stmt_bind_param(
                        $stmt,
                        'ssssssssssssssssssssi',
                        $title,
                        $message,
                        $headline,
                        $news,
                        $comment,
                        $usermenu,
                        $usermenu2,
                        $relatedlinks,
                        $commentform,
                        $registerform,
                        $loginform,
                        $logout,
                        $senddataform,
                        $profileform,
                        $archive,
                        $sendnewsform,
                        $addemail,
                        $editemail,
                        $registeremail,
                        $dataemail,
                        $templateid,
                    );
                    mysqli_stmt_execute($stmt);
                    ?>
                    <div class="alert alert-success" role="alert">
                        <?php echo L_TEMPL_TEMPLATEEDITED; ?>
                        <div class="mt-2"><a href="index.php?page=templates&amp;subpage=edit&amp;templateid=<?php echo $templateid; ?>" class="btn btn-sm btn-success">Erneut bearbeiten</a></div>
                    </div>
                    <?php
                }
            }
        }
    }

    public function checktemplate(int $templateid): string
    {
        global $pn_config, $pn_handler;

        $stmt = mysqli_prepare($pn_handler, 'SELECT id FROM ' . $pn_config['templatetable'] . ' WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'i', $templateid);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $num = mysqli_num_rows($result);

        if ($num != 1) {
            return L_TEMPL_RIGHTTEMPLATENEEDED;
        }

        return '';
    }

    public function gettemplatedata(int $templateid): array
    {
        global $pn_config, $pn_handler;

        $stmt = mysqli_prepare($pn_handler, 'SELECT * FROM ' . $pn_config['templatetable'] . ' WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'i', $templateid);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $num = mysqli_num_rows($result);

        if ($num == 1) {
            return mysqli_fetch_array($result);
        }

        return [];
    }
}

//###############################################################################################

class email
{
    public function addemail(string $nickname, string $email, string $password): bool
    {
        global $pnconfig;
        $template = new template();
        $addemail = $template->addemail($nickname, $email, $password);

        if ($addemail) {
            $headers = 'From: ' . L_EMAIL_AUTHOR . ' <' . $pnconfig['email'] . '>';

            return mail($email, L_EMAIL_SUBJECT, $addemail, $headers);
        }

        return false;
    }

    public function editemail(string $nickname, string $email, string $password): bool
    {
        global $pnconfig;
        $template = new template();
        $editemail = $template->editemail($nickname, $email, $password);

        if ($editemail) {
            $headers = 'From: ' . L_EMAIL_AUTHOR . ' <' . $pnconfig['email'] . '>';

            return mail($email, L_EMAIL_SUBJECT, $editemail, $headers);
        }

        return false;
    }
}

//###############################################################################################

class getadmin
{
    /**
     * Holt User-Daten und validiert die Session.
     * Der zweite Parameter ist entweder (neu) ein 64-hex Session-Token, dessen sha256
     * in pn_sessions existieren und nicht abgelaufen sein muss, oder (legacy) der
     * bcrypt-Passwort-Hash aus der DB - Abwaertskompatibilitaet fuer aeltere Tests
     * und etwaige bestehende Cookies.
     */
    public function getuserdata(int $userid, string $password): array
    {
        global $pn_config, $pn_handler;

        $stmt = mysqli_prepare($pn_handler, 'SELECT * FROM ' . $pn_config['usertable'] . ' WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'i', $userid);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $num = mysqli_num_rows($result);

        $user = ['loggedin' => 'NO'];

        if ($num == 1) {
            $user = mysqli_fetch_array($result);
            $user['loggedin'] = 'NO';

            // User muss aktiviert sein.
            if (($user['status'] ?? 'Activated') !== 'Activated') {
                return $user;
            }

            // Neuer Pfad: 64-hex Token, Session-Row in pn_sessions pruefen.
            if (preg_match('/^[a-f0-9]{64}$/', $password)) {
                $tokenHash = hash('sha256', $password);
                $now = time();
                $sstmt = mysqli_prepare($pn_handler, 'SELECT id FROM pn_sessions WHERE userid = ? AND token_hash = ? AND expires > ?');
                if ($sstmt) {
                    mysqli_stmt_bind_param($sstmt, 'isi', $userid, $tokenHash, $now);
                    mysqli_stmt_execute($sstmt);
                    $sres = mysqli_stmt_get_result($sstmt);
                    if (mysqli_num_rows($sres) === 1) {
                        $user['loggedin'] = 'YES';
                    }
                }
            } elseif ($password !== '' && $password === $user['password']) {
                // Legacy-Pfad: Vergleich mit gespeichertem Passwort-Hash.
                $user['loggedin'] = 'YES';
            }
        }

        return $user;
    }

    public function getpermissions(int $userid): array
    {
        global $pn_config, $pn_handler;

        $stmt = mysqli_prepare($pn_handler, 'SELECT * FROM ' . $pn_config['permissionstable'] . ' WHERE userid = ?');
        mysqli_stmt_bind_param($stmt, 'i', $userid);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $num = mysqli_num_rows($result);

        if ($num == 1) {
            $admin = mysqli_fetch_array($result);
            $admin['loggedin'] = 'YES';
        } else {
            $admin = ['loggedin' => 'NO'];
        }

        return $admin;
    }
}

//###############################################################################################

class menus
{
    public function statusmenu(string $loggedin, string $username): void
    {
        if ($loggedin === 'NO') {
            echo L_USR_PLEASELOGIN;
        } else {
            echo L_USR_HELLO . ' ' . pnadmin_escape($username);
            ?> - [ <a href="index.php?page=profile"><?php echo L_USR_EDITPROFILE; ?></a> |<a href="index.php?pnlogout=YES"><?php echo L_USR_LOGOUT; ?></a> ]<?php
        }
    }

    public function submenu(string $page): void
    {
        global $pnconfig;

        // Aktuelle Subpage fuer aktiven Tab-Style ermitteln.
        $activeSub = isset($_GET['subpage']) ? (string) $_GET['subpage'] : '';

        // Erzeugt einen einzelnen Subpage-Link als Bootstrap-Outline-Button.
        // Die Klasse wechselt zu "btn-primary" (gefuellt), wenn der Tab aktiv ist.
        $renderItem = static function (string $href, string $label, bool $active = false, ?string $target = null): void {
            $classes = $active ? 'btn btn-sm btn-primary' : 'btn btn-sm btn-outline-primary';
            $targetAttr = $target !== null ? ' target="' . htmlspecialchars($target, ENT_QUOTES, 'UTF-8') . '" rel="noopener noreferrer"' : '';
            ?><a class="<?php echo $classes; ?>" href="<?php echo htmlspecialchars($href, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $targetAttr; ?>><?php echo $label; ?></a><?php
        };

        switch ($page) {
            case 'templates':
                $renderItem('index.php?page=templates&subpage=add', L_MENU_ADDTEMPLATE, $activeSub === 'add');
                $renderItem('index.php?page=templates&subpage=show', L_MENU_SHOWTEMPLATES, $activeSub === 'show' || $activeSub === 'edit');
                break;
            case 'users':
                $renderItem('index.php?page=users&subpage=add', L_MENU_ADDUSER, $activeSub === 'add');
                $renderItem('index.php?page=users&subpage=show', L_MENU_SHOWUSER, $activeSub === 'show' || $activeSub === 'edit');
                $renderItem('index.php?page=users&subpage=search', L_MENU_SEARCHUSER, $activeSub === 'search');
                break;
            case 'permissions':
                $renderItem('index.php?page=permissions&subpage=add', L_MENU_ADDPERMISSIONS, $activeSub === 'add');
                $renderItem('index.php?page=permissions&subpage=show', L_MENU_SHOWPERMISSIONS, $activeSub === 'show' || $activeSub === 'edit');
                break;
            case 'configuration':
                $renderItem('index.php?page=configuration', L_MENU_EDITCONFIG, true);
                break;
            case 'categories':
                if ($pnconfig['categories'] == 'YES') {
                    $renderItem('index.php?page=categories&subpage=add', L_MENU_ADDCAT, $activeSub === 'add');
                    $renderItem('index.php?page=categories&subpage=show', L_MENU_SHOWCATS, $activeSub === 'show' || $activeSub === 'edit');
                } else {
                    ?><span class="badge text-bg-secondary"><?php echo L_MENU_CATSDEACTIVATED; ?></span><?php
                }
                break;
            case 'news':
                $renderItem('index.php?page=news&subpage=add', L_MENU_ADDNEWS, $activeSub === 'add');
                $renderItem('index.php?page=news&subpage=show', L_MENU_SHOWNEWS, $activeSub === 'show' || $activeSub === 'edit');
                $renderItem('index.php?page=news&subpage=search', L_MENU_SEARCHNEWS, $activeSub === 'search');
                break;
            case 'other':
                $renderItem('index.php?page=other&subpage=help', L_MENU_HELP, $activeSub === 'help');
                $renderItem('index.php?page=other&subpage=license', L_MENU_LICENSE, $activeSub === 'license');
                $renderItem('https://www.powerscripts.org', L_MENU_PSHP, false, '_ps');
                break;
            default:
                ?><span class="text-muted"><?php echo L_MENU_CHOOSESECTION; ?></span><?php
                break;
        }
    }
}

//###############################################################################################

class user
{
    public function generate_password(): string
    {
        $pwarray = array_merge(range('a', 'z'), range('A', 'Z'), range('0', '9'));
        $pwacount = count($pwarray);
        $password = '';

        for ($i = 0; $i < 8; ++$i) {
            $letter = random_int(0, $pwacount - 1);
            $password .= $pwarray[$letter];
        }

        return $password;
    }

    public function adduser(string $nickname, string $email, string $showemail, string $sendemail): string
    {
        global $pn_config, $pn_handler;
        $error = '';

        $stmt = mysqli_prepare($pn_handler, 'SELECT * FROM ' . $pn_config['usertable'] . ' WHERE nickname = ? OR email = ?');
        mysqli_stmt_bind_param($stmt, 'ss', $nickname, $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $num = mysqli_num_rows($result);

        if ($num == 0) {
            if (preg_match("!^[_a-zA-Z0-9.-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}!", $email)) {
                $password = $this->generate_password();
                $hashedPassword = pnadmin_hash_password($password);

                if ($showemail === '' || $showemail === '0') {
                    $showemail = 'NO';
                }

                $now = time();
                $status = 'Activated';
                $stmt = mysqli_prepare($pn_handler, 'INSERT INTO ' . $pn_config['usertable'] . ' (nickname, email, password, registered, showemail, status) VALUES(?, ?, ?, ?, ?, ?)');
                mysqli_stmt_bind_param($stmt, 'sssiss', $nickname, $email, $hashedPassword, $now, $showemail, $status);
                mysqli_stmt_execute($stmt);

                if ($sendemail === 'YES') {
                    $emailObj = new email();
                    $emailObj->addemail($nickname, $email, $password);
                }
            } else {
                $error = L_USR_WRONGEMAIL;
            }
        } else {
            $error = L_USR_USRALREADYEXISTS;
        }

        return $error;
    }

    public function listpages(): void
    {
        global $pn_config, $pn_handler;

        $result = mysqli_query($pn_handler, 'SELECT * FROM ' . $pn_config['usertable']);
        $num = mysqli_num_rows($result);

        if ($num == 0) {
            ?><li class="page-item disabled"><span class="page-link">[ Keine Seiten ]</span></li><?php
        } else {
            $pagenum = (int) ceil($num / 25);
            $activeCurrent = (int) ($_GET['current'] ?? 0);

            for ($i = 1; $i <= $pagenum; ++$i) {
                $i2 = $i - 1;
                $current = $i2 * 25;
                $isActive = $current === $activeCurrent ? ' active' : '';
                ?><li class="page-item<?php echo $isActive; ?>"><a class="page-link" href="index.php?page=users&subpage=show&current=<?php echo $current; ?>"><?php echo $i; ?></a></li><?php
            }
        }
    }

    public function checkadmin(int $userid): string
    {
        global $pn_config, $pn_handler;

        $stmt = mysqli_prepare($pn_handler, 'SELECT * FROM ' . $pn_config['permissionstable'] . ' WHERE userid = ?');
        mysqli_stmt_bind_param($stmt, 'i', $userid);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $num = mysqli_num_rows($result);

        return ($num == 0) ? 'NO' : 'YES';
    }

    public function listusers(int $current): void
    {
        global $pn_config, $pn_handler;

        $stmt = mysqli_prepare($pn_handler, 'SELECT * FROM ' . $pn_config['usertable'] . ' ORDER BY nickname LIMIT ?, 25');
        mysqli_stmt_bind_param($stmt, 'i', $current);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $num = mysqli_num_rows($result);

        if ($num == 0) {
            ?>
            <tr><td colspan="5" class="text-center text-muted">
            <?php echo L_USR_NOUSRINDB; ?>
            </td></tr>
            <?php
        } else {
            while ($row = mysqli_fetch_array($result)) {
                ?>
                <tr>
                    <td><a href="index.php?page=users&amp;subpage=edit&amp;userid=<?php echo (int) $row['id']; ?>"><?php echo pnadmin_escape($row['nickname']); ?></a></td>
                    <td><a href="mailto:<?php echo pnadmin_escape($row['email']); ?>"><?php echo pnadmin_escape($row['email']); ?></a></td>
                    <td class="text-center">
                <?php
                if ($row['showemail'] == 'YES') {
                    ?><span class="badge text-bg-success"><?php echo L_ALL_YES; ?></span><?php
                } else {
                    ?><span class="badge text-bg-secondary"><?php echo L_ALL_NO; ?></span><?php
                }
                ?>
                    </td>
                    <td class="text-center">
                <?php
                if ($this->checkadmin((int) $row['id']) === 'YES') {
                    ?><span class="badge text-bg-primary"><?php echo L_ALL_YES; ?></span><?php
                } else {
                    ?><span class="badge text-bg-secondary"><?php echo L_ALL_NO; ?></span><?php
                }
                ?>
                    </td>
                    <td class="text-center">
                <?php
                if ($row['status'] == 'Activated') {
                    ?><span class="badge text-bg-success"><?php echo L_ALL_ACTIVATED; ?></span><?php
                } else {
                    ?><span class="badge text-bg-danger"><?php echo L_ALL_DEACTIVATED; ?></span><?php
                }
                ?>
                    </td>
                </tr>
                <?php
            }
        }
    }

    public function checkuser(int $userid): string
    {
        global $pn_config, $pn_handler;

        $stmt = mysqli_prepare($pn_handler, 'SELECT * FROM ' . $pn_config['usertable'] . ' WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'i', $userid);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $num = mysqli_num_rows($result);

        if ($num != 1) {
            return L_USR_NOUSR;
        }

        return '';
    }

    public function getuserdata(int $userid): ?array
    {
        global $pn_config, $pn_handler;

        $stmt = mysqli_prepare($pn_handler, 'SELECT * FROM ' . $pn_config['usertable'] . ' WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'i', $userid);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $num = mysqli_num_rows($result);

        if ($num == 1) {
            return mysqli_fetch_array($result);
        }

        return null;
    }

    public function edituser(string $nickname, string $email, string $showemail, string $newpassword, string $status, string $sendemail, int $userid, string $password): string
    {
        global $pn_config, $pn_handler;
        $error = '';

        if (!$nickname || !$email) {
            $error = L_USR_INSERTNICKNAMEANDEMAIL;
        } else {
            $stmt = mysqli_prepare($pn_handler, 'SELECT * FROM ' . $pn_config['usertable'] . ' WHERE (nickname = ? OR email = ?) AND id != ?');
            mysqli_stmt_bind_param($stmt, 'ssi', $nickname, $email, $userid);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $num = mysqli_num_rows($result);

            if ($num == 0) {
                if (preg_match("!^[_a-zA-Z0-9.-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}!", $email)) {
                    if ($newpassword === 'YES') {
                        $password = $this->generate_password();
                        $hashedPassword = pnadmin_hash_password($password);
                        $stmt = mysqli_prepare($pn_handler, 'UPDATE ' . $pn_config['usertable'] . ' SET password = ? WHERE id = ?');
                        mysqli_stmt_bind_param($stmt, 'si', $hashedPassword, $userid);
                        mysqli_stmt_execute($stmt);
                    }

                    if ($showemail === '' || $showemail === '0') {
                        $showemail = 'NO';
                    }

                    $stmt = mysqli_prepare($pn_handler, 'UPDATE ' . $pn_config['usertable'] . ' SET nickname = ?, email = ?, showemail = ?, status = ? WHERE id = ?');
                    mysqli_stmt_bind_param($stmt, 'ssssi', $nickname, $email, $showemail, $status, $userid);
                    mysqli_stmt_execute($stmt);

                    if ($sendemail === 'YES') {
                        $emailObj = new email();
                        $emailObj->editemail($nickname, $email, $password);
                    }
                } else {
                    $error = L_USR_WRONGEMAIL;
                }
            } else {
                $error = L_USR_USRALREADYEXISTS;
            }
        }

        return $error;
    }

    public function listsearchpages(string $searchin, string $searchstring): void
    {
        global $pn_config, $pn_handler;

        $allowedFields = ['nickname', 'email'];

        if (!in_array($searchin, $allowedFields, true)) {
            $searchin = 'nickname';
        }

        $searchPattern = '%' . $searchstring . '%';
        $stmt = mysqli_prepare($pn_handler, 'SELECT * FROM ' . $pn_config['usertable'] . ' WHERE `' . $searchin . '` LIKE ?');
        mysqli_stmt_bind_param($stmt, 's', $searchPattern);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $num = mysqli_num_rows($result);

        if ($num == 0) {
            ?><li class="page-item disabled"><span class="page-link">[ <?php echo L_ALL_NOPAGES; ?> ]</span></li><?php
        } else {
            $pagenum = (int) ceil($num / 25);
            $activeCurrent = (int) ($_GET['current'] ?? 0);

            for ($i = 1; $i <= $pagenum; ++$i) {
                $i2 = $i - 1;
                $current = $i2 * 25;
                $isActive = $current === $activeCurrent ? ' active' : '';
                ?><li class="page-item<?php echo $isActive; ?>"><a class="page-link" href="index.php?page=users&subpage=search&searchin=<?php echo pnadmin_escape($searchin); ?>&searchstring=<?php echo pnadmin_escape($searchstring); ?>&current=<?php echo $current; ?>"><?php echo $i; ?></a></li><?php
            }
        }
    }

    public function searchuser(string $searchin, string $searchstring, int $current): void
    {
        global $pn_config, $pn_handler;

        $allowedFields = ['nickname', 'email'];

        if (!in_array($searchin, $allowedFields, true)) {
            $searchin = 'nickname';
        }

        $searchPattern = '%' . $searchstring . '%';
        $stmt = mysqli_prepare($pn_handler, 'SELECT * FROM ' . $pn_config['usertable'] . ' WHERE `' . $searchin . '` LIKE ? ORDER BY nickname LIMIT ?, 25');
        mysqli_stmt_bind_param($stmt, 'si', $searchPattern, $current);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $num = mysqli_num_rows($result);

        if ($num == 0) {
            ?>
            <tr><td colspan="5" class="text-center text-muted">
            <?php echo L_USR_NOUSRFOUND; ?>
            </td></tr>
            <?php
        } else {
            while ($row = mysqli_fetch_array($result)) {
                ?>
                <tr>
                    <td><a href="index.php?page=users&amp;subpage=edit&amp;userid=<?php echo (int) $row['id']; ?>"><?php echo pnadmin_escape($row['nickname']); ?></a></td>
                    <td><a href="mailto:<?php echo pnadmin_escape($row['email']); ?>"><?php echo pnadmin_escape($row['email']); ?></a></td>
                    <td class="text-center">
                <?php
                if ($row['showemail'] == 'YES') {
                    ?><span class="badge text-bg-success"><?php echo L_ALL_YES; ?></span><?php
                } else {
                    ?><span class="badge text-bg-secondary"><?php echo L_ALL_NO; ?></span><?php
                }
                ?>
                    </td>
                    <td class="text-center">
                <?php
                if ($this->checkadmin((int) $row['id']) === 'YES') {
                    ?><span class="badge text-bg-primary"><?php echo L_ALL_YES; ?></span><?php
                } else {
                    ?><span class="badge text-bg-secondary"><?php echo L_ALL_NO; ?></span><?php
                }
                ?>
                    </td>
                    <td class="text-center">
                <?php
                if ($row['status'] == 'Activated') {
                    ?><span class="badge text-bg-success"><?php echo L_ALL_ACTIVATED; ?></span><?php
                } else {
                    ?><span class="badge text-bg-danger"><?php echo L_ALL_DEACTIVATED; ?></span><?php
                }
                ?>
                    </td>
                </tr>
                <?php
            }
        }
    }
}

//###############################################################################################

class profile
{
    public function getdata(int $userid): ?array
    {
        global $pn_config, $pn_handler;

        $stmt = mysqli_prepare($pn_handler, 'SELECT * FROM ' . $pn_config['usertable'] . ' WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'i', $userid);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $num = mysqli_num_rows($result);

        if ($num == 1) {
            return mysqli_fetch_array($result);
        }

        return null;
    }

    public function edit(string $nickname, string $email, string $showemail, string $password, string $password2, int $userid): string
    {
        global $pn_config, $pn_handler;
        $error = '';

        $stmt = mysqli_prepare($pn_handler, 'SELECT * FROM ' . $pn_config['usertable'] . ' WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'i', $userid);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $num = mysqli_num_rows($result);

        if ($num == 1) {
            // Passwort nur prüfen wenn eines eingegeben wurde
            $changePassword = ($password !== '' || $password2 !== '');

            if ($changePassword && $password !== $password2) {
                $error = L_USR_PWNOTCONFIRMED;
            } elseif (!preg_match("!^[_a-zA-Z0-9.-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}!", $email)) {
                $error = L_USR_WRONGEMAIL;
            } else {
                $stmt2 = mysqli_prepare($pn_handler, 'SELECT * FROM ' . $pn_config['usertable'] . ' WHERE (nickname = ? OR email = ?) AND id != ?');
                mysqli_stmt_bind_param($stmt2, 'ssi', $nickname, $email, $userid);
                mysqli_stmt_execute($stmt2);
                $result2 = mysqli_stmt_get_result($stmt2);
                $num2 = mysqli_num_rows($result2);

                if ($num2 == 0) {
                    if ($changePassword) {
                        // Mit Passwort-Änderung
                        $hashedPassword = pnadmin_hash_password($password);
                        $stmt3 = mysqli_prepare($pn_handler, 'UPDATE ' . $pn_config['usertable'] . ' SET nickname = ?, email = ?, showemail = ?, password = ? WHERE id = ?');
                        mysqli_stmt_bind_param($stmt3, 'ssssi', $nickname, $email, $showemail, $hashedPassword, $userid);
                    } else {
                        // Ohne Passwort-Änderung
                        $stmt3 = mysqli_prepare($pn_handler, 'UPDATE ' . $pn_config['usertable'] . ' SET nickname = ?, email = ?, showemail = ? WHERE id = ?');
                        mysqli_stmt_bind_param($stmt3, 'sssi', $nickname, $email, $showemail, $userid);
                    }
                    mysqli_stmt_execute($stmt3);
                } else {
                    $error = L_USR_USRALREADYEXISTS;
                }
            }
        } else {
            $error = L_USR_NOUSR;
        }

        return $error;
    }
}

//###############################################################################################

class permissions
{
    public function checkuser(string $user): int|false
    {
        global $pn_config, $pn_handler;

        $stmt = mysqli_prepare($pn_handler, 'SELECT id, status FROM ' . $pn_config['usertable'] . ' WHERE nickname = ?');
        mysqli_stmt_bind_param($stmt, 's', $user);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $num = mysqli_num_rows($result);

        if ($num == 1) {
            [$id, $status] = mysqli_fetch_array($result);

            if ($status == 'Activated') {
                return (int) $id;
            }
        }

        return false;
    }

    public function checkadmin(int $userid): bool
    {
        global $pn_config, $pn_handler;

        $stmt = mysqli_prepare($pn_handler, 'SELECT * FROM ' . $pn_config['permissionstable'] . ' WHERE userid = ?');
        mysqli_stmt_bind_param($stmt, 'i', $userid);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        return mysqli_num_rows($result) == 1;
    }

    public function addpermissions(string $user, PermissionsData $perms): string
    {
        global $pn_config, $pn_handler;
        $error = '';

        $userid = $this->checkuser($user);

        if ($userid !== false) {
            if (!$this->checkadmin($userid)) {
                $stmt = mysqli_prepare($pn_handler, 'INSERT INTO ' . $pn_config['permissionstable'] . ' (userid, canreadtemplates, canwritetemplates, canreadconfig, canwriteconfig, canreadusers, canwriteusers, canreadpermissions, canwritepermissions, canreadcategories, canwritecategories, canreadnews, canwritenews, canreadcomments, canwritecomments) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                $canreadtemplates = $perms->canreadtemplates;
                $canwritetemplates = $perms->canwritetemplates;
                $canreadconfig = $perms->canreadconfig;
                $canwriteconfig = $perms->canwriteconfig;
                $canreadusers = $perms->canreadusers;
                $canwriteusers = $perms->canwriteusers;
                $canreadpermissions = $perms->canreadpermissions;
                $canwritepermissions = $perms->canwritepermissions;
                $canreadcategories = $perms->canreadcategories;
                $canwritecategories = $perms->canwritecategories;
                $canreadnews = $perms->canreadnews;
                $canwritenews = $perms->canwritenews;
                $canreadcomments = $perms->canreadcomments;
                $canwritecomments = $perms->canwritecomments;
                mysqli_stmt_bind_param(
                    $stmt,
                    'issssssssssssss',
                    $userid,
                    $canreadtemplates,
                    $canwritetemplates,
                    $canreadconfig,
                    $canwriteconfig,
                    $canreadusers,
                    $canwriteusers,
                    $canreadpermissions,
                    $canwritepermissions,
                    $canreadcategories,
                    $canwritecategories,
                    $canreadnews,
                    $canwritenews,
                    $canreadcomments,
                    $canwritecomments,
                );

                if (!mysqli_stmt_execute($stmt)) {
                    $error = L_PERM_CANTWRITETODB;
                }
            } else {
                $error = L_PERM_ALREADYADMIN;
            }
        } else {
            $error = L_PERM_USERNOTEXISTING;
        }

        return $error;
    }

    public function listpermissions(): void
    {
        global $pn_config, $pn_handler;

        $result = mysqli_query($pn_handler, 'SELECT * FROM ' . $pn_config['permissionstable'] . ' ORDER BY id');
        $num = mysqli_num_rows($result);

        if ($num == 0) {
            ?><tr><td colspan="2" class="text-center text-muted"><?php echo L_PERM_NOPERMISSIONS; ?></td></tr><?php
        } else {
            while ($row = mysqli_fetch_array($result)) {
                $stmt = mysqli_prepare($pn_handler, 'SELECT nickname FROM ' . $pn_config['usertable'] . ' WHERE id = ?');
                $rowUserId = (int) $row['userid'];
                mysqli_stmt_bind_param($stmt, 'i', $rowUserId);
                mysqli_stmt_execute($stmt);
                $result2 = mysqli_stmt_get_result($stmt);
                $num2 = mysqli_num_rows($result2);

                if ($num2 == 1) {
                    [$nickname] = mysqli_fetch_array($result2);
                    $permIcon = static function (string $value): string {
                        if ($value == 'YES') {
                            return '<span class="badge text-bg-success" aria-label="ja">&check;</span>';
                        }
                        return '<span class="badge text-bg-secondary" aria-label="nein">&minus;</span>';
                    };
                    ?>
                    <tr>
                        <td class="align-top">
                            <a href="index.php?page=permissions&amp;subpage=edit&amp;userid=<?php echo (int) $row['userid']; ?>"><?php echo pnadmin_escape($nickname); ?></a>
                        </td>
                        <td>
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th><?php echo L_PERM_SECTION; ?></th>
                                            <th class="text-center"><?php echo L_PERM_TEMPLATES; ?></th>
                                            <th class="text-center"><?php echo L_PERM_CONFIG; ?></th>
                                            <th class="text-center"><?php echo L_PERM_USER; ?></th>
                                            <th class="text-center"><?php echo L_PERM_PERMISSIONS; ?></th>
                                            <th class="text-center"><?php echo L_PERM_CATS; ?></th>
                                            <th class="text-center"><?php echo L_PERM_NEWS; ?></th>
                                            <th class="text-center"><?php echo L_PERM_COMMENTS; ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><strong><?php echo L_PERM_READ; ?></strong></td>
                                            <td class="text-center"><?php echo $permIcon((string) $row['canreadtemplates']); ?></td>
                                            <td class="text-center"><?php echo $permIcon((string) $row['canreadconfig']); ?></td>
                                            <td class="text-center"><?php echo $permIcon((string) $row['canreadusers']); ?></td>
                                            <td class="text-center"><?php echo $permIcon((string) $row['canreadpermissions']); ?></td>
                                            <td class="text-center"><?php echo $permIcon((string) $row['canreadcategories']); ?></td>
                                            <td class="text-center"><?php echo $permIcon((string) $row['canreadnews']); ?></td>
                                            <td class="text-center"><?php echo $permIcon((string) $row['canreadcomments']); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong><?php echo L_PERM_WRITE; ?></strong></td>
                                            <td class="text-center"><?php echo $permIcon((string) $row['canwritetemplates']); ?></td>
                                            <td class="text-center"><?php echo $permIcon((string) $row['canwriteconfig']); ?></td>
                                            <td class="text-center"><?php echo $permIcon((string) $row['canwriteusers']); ?></td>
                                            <td class="text-center"><?php echo $permIcon((string) $row['canwritepermissions']); ?></td>
                                            <td class="text-center"><?php echo $permIcon((string) $row['canwritecategories']); ?></td>
                                            <td class="text-center"><?php echo $permIcon((string) $row['canwritenews']); ?></td>
                                            <td class="text-center"><?php echo $permIcon((string) $row['canwritecomments']); ?></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </td>
                    </tr>
                    <?php
                } else {
                    $stmt = mysqli_prepare($pn_handler, 'DELETE FROM ' . $pn_config['permissionstable'] . ' WHERE userid = ?');
                    $rowId = (int) $row['id'];
                    mysqli_stmt_bind_param($stmt, 'i', $rowId);
                    mysqli_stmt_execute($stmt);
                }
            }
        }
    }

    public function getdata(int $userid): ?array
    {
        global $pn_config, $pn_handler;

        $stmt = mysqli_prepare($pn_handler, 'SELECT nickname FROM ' . $pn_config['usertable'] . ' WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'i', $userid);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $num = mysqli_num_rows($result);

        if ($num == 1) {
            [$nickname] = mysqli_fetch_array($result);

            $stmt2 = mysqli_prepare($pn_handler, 'SELECT * FROM ' . $pn_config['permissionstable'] . ' WHERE userid = ?');
            mysqli_stmt_bind_param($stmt2, 'i', $userid);
            mysqli_stmt_execute($stmt2);
            $result2 = mysqli_stmt_get_result($stmt2);
            $num2 = mysqli_num_rows($result2);

            if ($num2 == 1) {
                $data = mysqli_fetch_array($result2);
                $data['nickname'] = $nickname;

                return $data;
            }
        }

        return null;
    }

    public function editpermissions(int $userid, PermissionsData $perms, string $delete): string
    {
        global $pn_config, $pn_handler;
        $error = '';

        if ($this->checkadmin($userid)) {
            if ($delete === 'YES') {
                $stmt = mysqli_prepare($pn_handler, 'DELETE FROM ' . $pn_config['permissionstable'] . ' WHERE userid = ?');
                mysqli_stmt_bind_param($stmt, 'i', $userid);

                if (!mysqli_stmt_execute($stmt)) {
                    $error = L_PERM_PERMISSIONSNOTDELETED;
                }
            } else {
                $stmt = mysqli_prepare($pn_handler, 'UPDATE ' . $pn_config['permissionstable'] . ' SET canreadtemplates = ?, canwritetemplates = ?, canreadconfig = ?, canwriteconfig = ?, canreadusers = ?, canwriteusers = ?, canreadpermissions = ?, canwritepermissions = ?, canreadcategories = ?, canwritecategories = ?, canreadnews = ?, canwritenews = ?, canreadcomments = ?, canwritecomments = ? WHERE userid = ?');
                $canreadtemplates = $perms->canreadtemplates;
                $canwritetemplates = $perms->canwritetemplates;
                $canreadconfig = $perms->canreadconfig;
                $canwriteconfig = $perms->canwriteconfig;
                $canreadusers = $perms->canreadusers;
                $canwriteusers = $perms->canwriteusers;
                $canreadpermissions = $perms->canreadpermissions;
                $canwritepermissions = $perms->canwritepermissions;
                $canreadcategories = $perms->canreadcategories;
                $canwritecategories = $perms->canwritecategories;
                $canreadnews = $perms->canreadnews;
                $canwritenews = $perms->canwritenews;
                $canreadcomments = $perms->canreadcomments;
                $canwritecomments = $perms->canwritecomments;
                mysqli_stmt_bind_param(
                    $stmt,
                    'ssssssssssssssi',
                    $canreadtemplates,
                    $canwritetemplates,
                    $canreadconfig,
                    $canwriteconfig,
                    $canreadusers,
                    $canwriteusers,
                    $canreadpermissions,
                    $canwritepermissions,
                    $canreadcategories,
                    $canwritecategories,
                    $canreadnews,
                    $canwritenews,
                    $canreadcomments,
                    $canwritecomments,
                    $userid,
                );

                if (!mysqli_stmt_execute($stmt)) {
                    $error = L_PERM_CANNOTWRITETODB;
                }
            }
        } else {
            $error = L_PERM_NOADMIN;
        }

        return $error;
    }
}

//###############################################################################################

class configuration
{
    public function editconfig(ConfigData $config): string
    {
        global $pn_config, $pn_handler;
        $error = '';

        if (!$config->isValid()) {
            if ($config->email === '') {
                $error = L_CONF_WRONGEMAIL;
            } else {
                $error = L_CONF_WRONGURL;
            }
        } else {
            // Extract to local variables for bind_param (requires references, incompatible with readonly)
            $categories = $config->categories;
            $categorypics = $config->categorypics;
            $comments = $config->comments;
            $commentwriting = $config->commentwriting;
            $moretext = $config->moretext;
            $sendnews = $config->sendnews;
            $newssending = $config->newssending;
            $smilies = $config->smilies;
            $bbcode = $config->bbcode;
            $html = $config->html;
            $dateformat = $config->dateformat;
            $timeformat = $config->timeformat;
            $template = $config->template;
            $url = $config->url;
            $email = $config->email;
            $headlines = $config->headlines;
            $news = $config->news;
            $spamprotection = $config->spamprotection;
            $relatedlinks = $config->relatedlinks;
            $relatedlinks_num = $config->relatedlinks_num;

            $stmt = mysqli_prepare($pn_handler, 'UPDATE ' . $pn_config['configtable'] . ' SET categories = ?, categorypics = ?, comments = ?, commentwriting = ?, moretext = ?, sendnews = ?, newssending = ?, smilies = ?, bbcode = ?, html = ?, dateformat = ?, timeformat = ?, template = ?, url = ?, email = ?, headlines = ?, news = ?, spamprotection = ?, relatedlinks = ?, relatedlinks_num = ?');
            mysqli_stmt_bind_param(
                $stmt,
                'ssssssssssssississsi',
                $categories,
                $categorypics,
                $comments,
                $commentwriting,
                $moretext,
                $sendnews,
                $newssending,
                $smilies,
                $bbcode,
                $html,
                $dateformat,
                $timeformat,
                $template,
                $url,
                $email,
                $headlines,
                $news,
                $spamprotection,
                $relatedlinks,
                $relatedlinks_num,
            );

            if (!mysqli_stmt_execute($stmt)) {
                $error = L_CONF_EDITFAILED;
            }
        }

        return $error;
    }

    public function listtemplates(): void
    {
        global $pn_config, $pnconfig, $pn_handler;

        $result = mysqli_query($pn_handler, 'SELECT * FROM ' . $pn_config['templatetable'] . ' ORDER BY id');
        $num = mysqli_num_rows($result);

        if ($num == 0) {
            ?><option value=""><?php echo L_CONF_NOTEMPLATES; ?></option><?php
        } else {
            while ($row = mysqli_fetch_array($result)) {
                ?><option value="<?php echo (int) $row['id']; ?>" <?php if ($pnconfig['template'] == $row['id']) { ?>selected<?php } ?>><?php echo pnadmin_escape($row['title']); ?></option><?php
            }
        }
    }
}

//###############################################################################################

class category
{
    public function addcat(string $name, string $description, array $picture = []): string
    {
        global $pn_config, $pnconfig, $pn_handler;
        $error = '';

        $stmt = mysqli_prepare($pn_handler, 'SELECT id FROM ' . $pn_config['cattable'] . ' WHERE name = ?');
        mysqli_stmt_bind_param($stmt, 's', $name);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $num = mysqli_num_rows($result);

        if ($num > 0) {
            $error = L_CAT_OTHERCATWITHTITLEEXISTS;
        } else {
            if (preg_match('![./\\\\:*?<>|"]+!', $name)) {
                $error = L_CAT_WRONGCATTITLE;
            } else {
                $pic = '';

                if ($pnconfig['categorypics'] == 'YES' && !empty($picture['name'])) {
                    $pic = basename((string) $picture['name']);
                    $targetPath = '../pngfx/categories/' . $pic;

                    if (!move_uploaded_file($picture['tmp_name'], $targetPath)) {
                        $error = L_CAT_PICUPLOADERROR;
                    }
                }

                if ($error === '') {
                    $name = addslashes($name);
                    $description = addslashes($description);
                    $status = 'Activated';
                    $stmt = mysqli_prepare($pn_handler, 'INSERT INTO ' . $pn_config['cattable'] . ' (name, description, picture, status) VALUES(?, ?, ?, ?)');
                    mysqli_stmt_bind_param($stmt, 'ssss', $name, $description, $pic, $status);

                    if (!mysqli_stmt_execute($stmt)) {
                        $error = L_CAT_CATADDERROR;
                    }
                }
            }
        }

        return $error;
    }

    public function listcats(): void
    {
        global $pn_config, $pn_handler;

        $result = mysqli_query($pn_handler, 'SELECT * FROM ' . $pn_config['cattable'] . ' ORDER BY name');
        $num = mysqli_num_rows($result);

        if ($num == 0) {
            ?><tr><td colspan="3" class="text-center text-muted"><?php echo L_CAT_NOCATSAVAILABLE; ?></td></tr><?php
        } else {
            while ($row = mysqli_fetch_array($result)) {
                ?>
                <tr>
                    <td><a href="index.php?page=categories&amp;subpage=edit&amp;catid=<?php echo (int) $row['id']; ?>"><?php echo pnadmin_escape(stripslashes((string) $row['name'])); ?></a></td>
                    <td><?php echo pnadmin_escape($row['description']); ?></td>
                    <td class="text-center">
                <?php
                if ($row['status'] == 'Activated') {
                    ?><span class="badge text-bg-success"><?php echo L_ALL_ACTIVATED; ?></span><?php
                } else {
                    ?><span class="badge text-bg-danger"><?php echo L_ALL_DEACTIVATED; ?></span><?php
                }
                ?>
                    </td>
                </tr>
                <?php
            }
        }
    }

    public function checkcat(int $catid): string
    {
        global $pn_config, $pn_handler;

        $stmt = mysqli_prepare($pn_handler, 'SELECT id FROM ' . $pn_config['cattable'] . ' WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'i', $catid);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $num = mysqli_num_rows($result);

        if ($num != 1) {
            return L_CAT_NONEXISTINGCAT;
        }

        return '';
    }

    public function getcatdata(int $catid): ?array
    {
        global $pn_config, $pn_handler;

        $stmt = mysqli_prepare($pn_handler, 'SELECT * FROM ' . $pn_config['cattable'] . ' WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'i', $catid);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $num = mysqli_num_rows($result);

        if ($num == 1) {
            $data = mysqli_fetch_array($result);
            $data['name'] = stripslashes((string) $data['name']);
            $data['description'] = stripslashes((string) $data['description']);

            return $data;
        }

        return null;
    }

    public function editcat(string $name, string $description, string $uploadpic = '', array $picture = [], string $status = '', int $catid = 0): string
    {
        global $pn_config, $pnconfig, $pn_handler;
        $error = '';

        $stmt = mysqli_prepare($pn_handler, 'SELECT * FROM ' . $pn_config['cattable'] . ' WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'i', $catid);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $num = mysqli_num_rows($result);

        if ($num != 1) {
            $error = L_CAT_NONEXISTINGCAT;
        } else {
            $row = mysqli_fetch_array($result);

            $stmt2 = mysqli_prepare($pn_handler, 'SELECT id FROM ' . $pn_config['cattable'] . ' WHERE name = ? AND id != ?');
            mysqli_stmt_bind_param($stmt2, 'si', $name, $catid);
            mysqli_stmt_execute($stmt2);
            $result2 = mysqli_stmt_get_result($stmt2);
            $num2 = mysqli_num_rows($result2);

            if ($num2 != 0) {
                $error = L_CAT_OTHERCATWITHTITLEEXISTS;
            } else {
                if (preg_match('![./\\\\:*?<>|"]+!', $name)) {
                    $error = L_CAT_WRONGCATTITLE;
                } else {
                    $pic = $row['picture'];

                    if ($pnconfig['categorypics'] == 'YES' && $uploadpic === 'YES' && !empty($picture['name'])) {
                        $pic = basename((string) $picture['name']);

                        if ($row['picture']) {
                            $oldPicPath = '../pngfx/categories/' . $row['picture'];

                            if (is_file($oldPicPath)) {
                                unlink($oldPicPath);
                            }
                        }

                        if (!move_uploaded_file($picture['tmp_name'], "../pngfx/categories/{$pic}")) {
                            $error = L_CAT_PICUPLOADERROR;
                        }
                    }

                    if ($error === '') {
                        $name = addslashes($name);
                        $description = addslashes($description);
                        $stmt3 = mysqli_prepare($pn_handler, 'UPDATE ' . $pn_config['cattable'] . ' SET name = ?, description = ?, picture = ?, status = ? WHERE id = ?');
                        mysqli_stmt_bind_param($stmt3, 'ssssi', $name, $description, $pic, $status, $catid);

                        if (!mysqli_stmt_execute($stmt3)) {
                            $error = L_CAT_CATEDITERROR;
                        }
                    }
                }
            }
        }

        return $error;
    }
}

//###############################################################################################

class news
{
    public function getcatdropdown(int $catid = 0): void
    {
        global $pn_config, $pn_handler;

        $result = mysqli_query($pn_handler, 'SELECT * FROM ' . $pn_config['cattable'] . " WHERE status = 'Activated' ORDER BY name");
        $num = mysqli_num_rows($result);

        if ($num > 0) {
            ?><select class="form-select" name="catid" id="pn_catid" aria-label="<?php echo L_NEWS_CATEGORY; ?>"><?php
            if ($catid === 0) {
                ?><option value=""><?php echo L_NEWS_CHOOSECAT; ?></option><?php
            }

            while ($row = mysqli_fetch_array($result)) {
                ?><option value="<?php echo (int) $row['id']; ?>" <?php if ($catid == $row['id']) { ?>selected<?php } ?>><?php echo pnadmin_escape(stripslashes((string) $row['name'])); ?></option><?php
            }
            ?></select><?php
        } else {
            ?><div class="alert alert-warning mb-0" role="alert"><?php echo L_NEWS_NOCATSAVAILABLE; ?></div><?php
        }
    }

    public function addnews(string $title, string $text, int $catid = 0, string $moretext = '', array $rl_title = [], array $rl_url = [], array $rl_target = [], array $time = []): string
    {
        global $pn_config, $pnuser, $pn_handler;
        $error = '';
        $relatedlinks = '';

        if ($rl_title !== []) {
            $counter = count($rl_title);

            for ($i = 0; $i < $counter; ++$i) {
                if (trim((string) $rl_title[$i]) && trim((string) $rl_url[$i])) {
                    $relatedlinks .= $rl_title[$i] . '!@!@!' . $rl_url[$i] . '!@!@!' . ($rl_target[$i] ?? '') . "\n";
                }
            }
        }

        $addtime = mktime((int) ($time['hour'] ?? 0), (int) ($time['min'] ?? 0), 0, (int) ($time['month'] ?? 1), (int) ($time['day'] ?? 1), (int) ($time['year'] ?? date('Y')));
        $title = addslashes($title);
        $text = addslashes($text);
        $moretext = addslashes($moretext);
        $status = 'Activated';
        $userId = (int) $pnuser['id'];

        $stmt = mysqli_prepare($pn_handler, 'INSERT INTO ' . $pn_config['newstable'] . ' (userid, time, catid, title, text, moretext, status, relatedlinks) VALUES(?, ?, ?, ?, ?, ?, ?, ?)');
        mysqli_stmt_bind_param($stmt, 'iiisssss', $userId, $addtime, $catid, $title, $text, $moretext, $status, $relatedlinks);

        if (!mysqli_stmt_execute($stmt)) {
            return L_NEWS_ADDINGFAILED;
        }

        return $error;
    }

    public function listpages(): void
    {
        global $pn_config, $pn_handler;

        $result = mysqli_query($pn_handler, 'SELECT id FROM ' . $pn_config['newstable']);
        $num = mysqli_num_rows($result);

        if ($num == 0) {
            ?><li class="page-item disabled"><span class="page-link">[ <?php echo L_ALL_NOPAGES; ?> ]</span></li><?php
        } else {
            $pagenum = (int) ceil($num / 25);
            $activeCurrent = (int) ($_GET['current'] ?? 0);

            for ($i = 1; $i <= $pagenum; ++$i) {
                $i2 = $i - 1;
                $current = $i2 * 25;
                $isActive = $current === $activeCurrent ? ' active' : '';
                ?><li class="page-item<?php echo $isActive; ?>"><a class="page-link" href="index.php?page=news&subpage=show&current=<?php echo $current; ?>"><?php echo $i; ?></a></li><?php
            }
        }
    }

    public function getcatname(int $catid): string
    {
        global $pn_config, $pn_handler;

        $stmt = mysqli_prepare($pn_handler, 'SELECT name FROM ' . $pn_config['cattable'] . ' WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'i', $catid);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $num = mysqli_num_rows($result);

        if ($num == 1) {
            [$name] = mysqli_fetch_array($result);

            return stripslashes((string) $name);
        }

        return L_NEWS_BADCAT;
    }

    public function listnews(int $current): void
    {
        global $pn_config, $pnconfig, $pn_handler;

        $stmt = mysqli_prepare($pn_handler, 'SELECT * FROM ' . $pn_config['newstable'] . ' ORDER BY time DESC LIMIT ?, 25');
        mysqli_stmt_bind_param($stmt, 'i', $current);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $num = mysqli_num_rows($result);

        $colCount = ($pnconfig['categories'] == 'YES') ? 4 : 3;

        if ($num == 0) {
            ?>
            <tr><td colspan="<?php echo $colCount; ?>" class="text-center text-muted">
            <?php echo L_NEWS_NONEWS; ?>
            </td></tr>
            <?php
        } else {
            while ($row = mysqli_fetch_array($result)) {
                ?>
                <tr>
                    <td><?php echo date('d.m.Y', (int) $row['time']); ?></td>
                <?php if ($pnconfig['categories'] == 'YES') { ?>
                    <td><?php echo pnadmin_escape($this->getcatname((int) $row['catid'])); ?></td>
                <?php } ?>
                    <td><a href="index.php?page=news&amp;subpage=edit&amp;newsid=<?php echo (int) $row['id']; ?>"><?php echo pnadmin_escape(stripslashes((string) $row['title'])); ?></a></td>
                    <td class="text-center">
                <?php
                if ($row['status'] == 'Activated') {
                    ?><span class="badge text-bg-success"><?php echo L_ALL_ACTIVATED; ?></span><?php
                } elseif ($row['status'] == 'Unchecked') {
                    ?><span class="badge text-bg-warning"><?php echo L_ALL_UNCHECKED; ?></span><?php
                } else {
                    ?><span class="badge text-bg-danger"><?php echo L_ALL_DEACTIVATED; ?></span><?php
                }
                ?>
                    </td>
                </tr>
                <?php
            }
        }
    }

    public function checknews(int $newsid): string
    {
        global $pn_config, $pn_handler;

        $stmt = mysqli_prepare($pn_handler, 'SELECT id FROM ' . $pn_config['newstable'] . ' WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'i', $newsid);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $num = mysqli_num_rows($result);

        if ($num != 1) {
            return L_NEWS_CHOOSENEWS;
        }

        return '';
    }

    public function getnewsdata(int $newsid): ?array
    {
        global $pn_config, $pn_handler;

        $stmt = mysqli_prepare($pn_handler, 'SELECT * FROM ' . $pn_config['newstable'] . ' WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'i', $newsid);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $num = mysqli_num_rows($result);

        if ($num == 1) {
            $row = mysqli_fetch_array($result);
            $row['title'] = stripslashes((string) $row['title']);
            $row['text'] = stripslashes((string) $row['text']);
            $row['moretext'] = stripslashes((string) $row['moretext']);

            return $row;
        }

        return null;
    }

    public function getcomments(int $newsid): void
    {
        global $pn_config, $pnconfig, $pn_handler;

        $stmt = mysqli_prepare($pn_handler, 'SELECT * FROM ' . $pn_config['commenttable'] . ' WHERE newsid = ? ORDER BY id DESC');
        mysqli_stmt_bind_param($stmt, 'i', $newsid);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $num = mysqli_num_rows($result);

        if ($num == 0) {
            ?><div class="alert alert-info mb-0" role="alert"><?php echo L_NEWS_NOCOMMENTS; ?></div><?php
        } else {
            while ($row = mysqli_fetch_array($result)) {
                ?>
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="small text-muted mb-2">
                            <?php echo L_NEWS_WRITTENBY; ?>
                            <?php if ($row['userid'] == '0') {
                                echo L_NEWS_GUEST;
                            } else {
                                echo $this->getcommentauthor((int) $row['userid']);
                            } ?>
                            <?php echo L_NEWS_ONDATE; ?> <?php echo date('d.m.Y', (int) $row['time']); ?>
                            <?php echo L_NEWS_AT; ?> <?php echo date('H:i', (int) $row['time']); ?>
                            (IP: <?php echo pnadmin_escape($row['ip']); ?>)
                        </div>
                        <input type="hidden" name="commentid[]" value="<?php echo (int) $row['id']; ?>">

                        <div class="mb-3">
                            <label class="form-label fw-bold" for="pn_commenttext_<?php echo (int) $row['id']; ?>"><?php echo L_NEWS_TEXT; ?></label>
                            <textarea class="form-control" name="commenttext[]" id="pn_commenttext_<?php echo (int) $row['id']; ?>" rows="4" aria-describedby="pn_commenttext_help_<?php echo (int) $row['id']; ?>"><?php echo pnadmin_escape(stripslashes((string) $row['text'])); ?></textarea>
                            <div id="pn_commenttext_help_<?php echo (int) $row['id']; ?>" class="form-text"><?php echo L_NEWS_COMMENTEXT_DESC; ?></div>
                        </div>

                        <div class="pn-danger-action">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="commentdelete[]" value="<?php echo (int) $row['id']; ?>" id="pn_commentdelete_<?php echo (int) $row['id']; ?>">
                                <label class="form-check-label fw-bold text-danger" for="pn_commentdelete_<?php echo (int) $row['id']; ?>"><?php echo L_NEWS_DELETECOMMENT; ?></label>
                            </div>
                        </div>
                    </div>
                </div>
                <?php
            }
        }
    }

    public function getcommentauthor(int $userid): string
    {
        global $pn_config, $pn_handler;

        $stmt = mysqli_prepare($pn_handler, 'SELECT id, nickname FROM ' . $pn_config['usertable'] . ' WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'i', $userid);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $num = mysqli_num_rows($result);

        if ($num == 1) {
            $row = mysqli_fetch_array($result);

            return '<a href="index.php?page=users&subpage=edit&userid=' . (int) $row['id'] . '">' . pnadmin_escape($row['nickname']) . '</a>';
        }

        return L_NEWS_GUEST;
    }

    public function checkcomment(array $commentid, array $commenttext): string
    {
        global $pn_config, $pn_handler;
        $error = '';
        $counter = count($commentid);

        for ($i = 0; $i < $counter; ++$i) {
            $cid = (int) $commentid[$i];
            $stmt = mysqli_prepare($pn_handler, 'SELECT id FROM ' . $pn_config['commenttable'] . ' WHERE id = ?');
            mysqli_stmt_bind_param($stmt, 'i', $cid);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $num = mysqli_num_rows($result);

            if ($num != 1) {
                $error = L_NEWS_ONECOMMENTWRONG;
            } elseif (trim($commenttext[$i] ?? '') === '' || trim($commenttext[$i] ?? '') === '0') {
                $error = L_NEWS_NOCOMMENTTEXT;
            }
        }

        return $error;
    }

    public function editcomment(array $commentid, array $commenttext, array $commentdelete): string
    {
        global $pn_config, $pn_handler;
        $error = '';
        $counter = count($commentid);

        for ($i = 0; $i < $counter; ++$i) {
            $cid = (int) $commentid[$i];
            $ctext = addslashes($commenttext[$i] ?? '');

            $stmt = mysqli_prepare($pn_handler, 'UPDATE ' . $pn_config['commenttable'] . ' SET text = ? WHERE id = ?');
            mysqli_stmt_bind_param($stmt, 'si', $ctext, $cid);

            if (!mysqli_stmt_execute($stmt)) {
                $error = L_NEWS_COMMENTEDITERROR;
            }

            $commentDeleteCount = count($commentdelete);

            for ($i2 = 0; $i2 < $commentDeleteCount; ++$i2) {
                if ((int) $commentdelete[$i2] === $cid) {
                    $stmt2 = mysqli_prepare($pn_handler, 'DELETE FROM ' . $pn_config['commenttable'] . ' WHERE id = ?');
                    mysqli_stmt_bind_param($stmt2, 'i', $cid);

                    if (!mysqli_stmt_execute($stmt2)) {
                        $error = L_NEWS_COMMENTEDITERROR;
                    }
                }
            }
        }

        return $error;
    }

    public function editnews(int $newsid, int $catid, string $title, string $text, string $moretext, string $status, string $delete, array $rl_title, array $rl_url, array $rl_target, array $time): string
    {
        global $pn_config, $pn_handler;
        $relatedlinks = '';
        $counter = count($rl_title);

        for ($i = 0; $i < $counter; ++$i) {
            if (trim((string) $rl_title[$i]) && trim((string) $rl_url[$i])) {
                $relatedlinks .= $rl_title[$i] . '!@!@!' . $rl_url[$i] . '!@!@!' . ($rl_target[$i] ?? '') . "\n";
            }
        }

        $newtime = mktime((int) ($time['hour'] ?? 0), (int) ($time['min'] ?? 0), 0, (int) ($time['month'] ?? 1), (int) ($time['day'] ?? 1), (int) ($time['year'] ?? date('Y')));
        $error = '';

        if ($delete === 'YES') {
            $stmt = mysqli_prepare($pn_handler, 'DELETE FROM ' . $pn_config['newstable'] . ' WHERE id = ?');
            mysqli_stmt_bind_param($stmt, 'i', $newsid);

            if (!mysqli_stmt_execute($stmt)) {
                $error = L_NEWS_NEWSNOTDELETED;
            }
        } else {
            $title = addslashes($title);
            $text = addslashes($text);
            $moretext = addslashes($moretext);
            $stmt = mysqli_prepare($pn_handler, 'UPDATE ' . $pn_config['newstable'] . ' SET time = ?, catid = ?, title = ?, text = ?, moretext = ?, status = ?, relatedlinks = ? WHERE id = ?');
            mysqli_stmt_bind_param($stmt, 'iisssssi', $newtime, $catid, $title, $text, $moretext, $status, $relatedlinks, $newsid);

            if (!mysqli_stmt_execute($stmt)) {
                $error = L_NEWS_NEWSNOTEDITED;
            }
        }

        return $error;
    }

    public function listsearchpages(string $searchin, string $searchstring): void
    {
        global $pn_config, $pn_handler;

        $allowedFields = ['title', 'text'];

        if (!in_array($searchin, $allowedFields, true)) {
            $searchin = 'title';
        }

        $searchPattern = '%' . $searchstring . '%';
        $stmt = mysqli_prepare($pn_handler, 'SELECT * FROM ' . $pn_config['newstable'] . ' WHERE `' . $searchin . '` LIKE ?');
        mysqli_stmt_bind_param($stmt, 's', $searchPattern);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $num = mysqli_num_rows($result);

        if ($num == 0) {
            ?><li class="page-item disabled"><span class="page-link">[ <?php echo L_ALL_NOPAGES; ?> ]</span></li><?php
        } else {
            $pagenum = (int) ceil($num / 25);
            $activeCurrent = (int) ($_GET['current'] ?? 0);

            for ($i = 1; $i <= $pagenum; ++$i) {
                $i2 = $i - 1;
                $current = $i2 * 25;
                $isActive = $current === $activeCurrent ? ' active' : '';
                ?><li class="page-item<?php echo $isActive; ?>"><a class="page-link" href="index.php?page=news&subpage=search&searchin=<?php echo pnadmin_escape($searchin); ?>&searchstring=<?php echo pnadmin_escape($searchstring); ?>&current=<?php echo $current; ?>"><?php echo $i; ?></a></li><?php
            }
        }
    }

    public function searchnews(string $searchin, string $searchstring, int $current): void
    {
        global $pn_config, $pnconfig, $pn_handler;

        $allowedFields = ['title', 'text'];

        if (!in_array($searchin, $allowedFields, true)) {
            $searchin = 'title';
        }

        $searchPattern = '%' . $searchstring . '%';
        $stmt = mysqli_prepare($pn_handler, 'SELECT * FROM ' . $pn_config['newstable'] . ' WHERE `' . $searchin . '` LIKE ? ORDER BY id DESC LIMIT ?, 25');
        mysqli_stmt_bind_param($stmt, 'si', $searchPattern, $current);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $num = mysqli_num_rows($result);

        $colCount = ($pnconfig['categories'] == 'YES') ? 4 : 3;

        if ($num == 0) {
            ?>
            <tr><td colspan="<?php echo $colCount; ?>" class="text-center text-muted">
            <?php echo L_NEWS_NONEWS; ?>
            </td></tr>
            <?php
        } else {
            while ($row = mysqli_fetch_array($result)) {
                ?>
                <tr>
                    <td><?php echo date('d.m.Y', (int) $row['time']); ?></td>
                <?php if ($pnconfig['categories'] == 'YES') { ?>
                    <td><?php echo pnadmin_escape($this->getcatname((int) $row['catid'])); ?></td>
                <?php } ?>
                    <td><a href="index.php?page=news&amp;subpage=edit&amp;newsid=<?php echo (int) $row['id']; ?>"><?php echo pnadmin_escape(stripslashes((string) $row['title'])); ?></a></td>
                    <td class="text-center">
                <?php
                if ($row['status'] == 'Activated') {
                    ?><span class="badge text-bg-success"><?php echo L_ALL_ACTIVATED; ?></span><?php
                } elseif ($row['status'] == 'Unchecked') {
                    ?><span class="badge text-bg-warning"><?php echo L_ALL_UNCHECKED; ?></span><?php
                } else {
                    ?><span class="badge text-bg-danger"><?php echo L_ALL_DEACTIVATED; ?></span><?php
                }
                ?>
                    </td>
                </tr>
                <?php
            }
        }
    }
}

//###############################################################################################

function readDump(string $dumpFile): array
{
    $sql = '';
    $content = file($dumpFile);

    if ($content === false) {
        return [];
    }
    $counter = count($content);

    for ($i = 0; $i < $counter; ++$i) {
        if (!preg_match('/^#/', $content[$i])) {
            $sql .= trim($content[$i]);
        }
    }

    $command = [];
    $sql_len = strlen($sql);

    for ($i = 0; $i < $sql_len; ++$i) {
        $char = $sql[$i];

        if ($char === ';') {
            $command[] = substr($sql, 0, $i);
            $sql = substr($sql, $i + 1, $sql_len);
            $sql_len = strlen($sql);
            $i = -1;
        }
    }

    return $command;
}
