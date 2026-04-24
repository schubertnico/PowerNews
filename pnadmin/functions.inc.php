<?php
declare(strict_types=1);

/* PowerNews - PHP and MySQL based news script                          */
/* Copyright (c) 2001-2024 PowerScripts                                 */

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
            ?><center><a href="javascript:history.back()"><?php echo L_TEMPL_TITLENEEDED; ?></a></center><?php
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
                    ?><center><a href="index.php?page=templates&subpage=show"><?php echo L_TEMPL_TEMPLATEADDED; ?></a></center><?php
                } else {
                    ?><center><a href="javascript:history.back()"><?php echo L_TEMPL_TEMPLATEALREADYEXISTS; ?></a></center><?php
                }
            } else {
                ?><center><a href="javascript:history.back()"><?php echo L_TEMPL_NOSTANDARDTEMPLATE; ?></a></center><?php
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
                <tr><td>
                <a href="index.php?page=templates&subpage=edit&templateid=<?php echo (int) $row['id']; ?>"><?php echo pnadmin_escape($row['title']); ?></a>
                </td></tr>
                <?php
            }
        } else {
            ?><tr><td align="center"><?php echo L_TEMPL_NOTEMPLATES; ?></td></tr><?php
        }
    }

    public function edittemplate(int $templateid, string $delete, TemplateData $data): void
    {
        global $pn_config, $pn_handler;

        if (!$data->isValid()) {
            ?><center><a href="javascript:history.back()"><?php echo L_TEMPL_INSERTALL; ?></a></center><?php
        } else {
            if ($templateid === 1) {
                ?><center><a href="javascript:history.back()"><?php echo L_TEMPL_NOSTANDARDEDIT; ?></a></center><?php
            } else {
                if ($delete === 'YES') {
                    $stmt = mysqli_prepare($pn_handler, 'DELETE FROM ' . $pn_config['templatetable'] . ' WHERE id = ?');
                    mysqli_stmt_bind_param($stmt, 'i', $templateid);
                    mysqli_stmt_execute($stmt);
                    ?><center><a href="index.php?page=templates&subpage=show"><?php echo L_TEMPL_TEMPLATEDELETED; ?></a></center><?php
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
                    ?><center><a href="index.php?page=templates&subpage=edit&templateid=<?php echo $templateid; ?>"><?php echo L_TEMPL_TEMPLATEEDITED; ?></a></center><?php
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

        switch ($page) {
            case 'templates':
                ?>
                &raquo; <a href="index.php?page=templates&subpage=add"><?php echo L_MENU_ADDTEMPLATE; ?></a><br>
                &raquo; <a href="index.php?page=templates&subpage=show"><?php echo L_MENU_SHOWTEMPLATES; ?></a><br>
                <?php
                break;
            case 'users':
                ?>
                &raquo; <a href="index.php?page=users&subpage=add"><?php echo L_MENU_ADDUSER; ?></a><br>
                &raquo; <a href="index.php?page=users&subpage=show"><?php echo L_MENU_SHOWUSER; ?></a><br>
                &raquo; <a href="index.php?page=users&subpage=search"><?php echo L_MENU_SEARCHUSER; ?></a><br>
                <?php
                break;
            case 'permissions':
                ?>
                &raquo; <a href="index.php?page=permissions&subpage=add"><?php echo L_MENU_ADDPERMISSIONS; ?></a><br>
                &raquo; <a href="index.php?page=permissions&subpage=show"><?php echo L_MENU_SHOWPERMISSIONS; ?></a><br>
                <?php
                break;
            case 'configuration':
                ?>
                &raquo; <a href="index.php?page=configuration"><?php echo L_MENU_EDITCONFIG; ?></a><br>
                <?php
                break;
            case 'categories':
                if ($pnconfig['categories'] == 'YES') {
                    ?>
                    &raquo; <a href="index.php?page=categories&subpage=add"><?php echo L_MENU_ADDCAT; ?></a><br>
                    &raquo; <a href="index.php?page=categories&subpage=show"><?php echo L_MENU_SHOWCATS; ?></a><br>
                    <?php
                } else {
                    ?>&raquo; <?php echo L_MENU_CATSDEACTIVATED; ?><?php
                }
                break;
            case 'news':
                ?>
                &raquo; <a href="index.php?page=news&subpage=add"><?php echo L_MENU_ADDNEWS; ?></a><br>
                &raquo; <a href="index.php?page=news&subpage=show"><?php echo L_MENU_SHOWNEWS; ?></a><br>
                &raquo; <a href="index.php?page=news&subpage=search"><?php echo L_MENU_SEARCHNEWS; ?></a><br>
                <?php
                break;
            case 'other':
                ?>
                &raquo; <a href="index.php?page=other&subpage=help"><?php echo L_MENU_HELP; ?></a><br>
                &raquo; <a href="index.php?page=other&subpage=license"><?php echo L_MENU_LICENSE; ?></a><br>
                &raquo; <a href="http://www.powerscripts.org" target="_ps"><?php echo L_MENU_PSHP; ?></a><br>
                <?php
                break;
            default:
                ?>&raquo; <?php echo L_MENU_CHOOSESECTION; ?><?php
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
            ?>[ Keine Seiten ]<?php
        } else {
            $pagenum = (int) ceil($num / 25);

            for ($i = 1; $i <= $pagenum; ++$i) {
                $i2 = $i - 1;
                $current = $i2 * 25;
                ?>| <a href="index.php?page=users&subpage=show&current=<?php echo $current; ?>"><?php echo $i; ?></a> <?php
            }
            ?> |<?php
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
            <tr><td colspan="9" align="center">
            <?php echo L_USR_NOUSRINDB; ?>
            </td></tr>
            <?php
        } else {
            while ($row = mysqli_fetch_array($result)) {
                ?>
                <tr><td>
                <a href="index.php?page=users&subpage=edit&userid=<?php echo (int) $row['id']; ?>"><?php echo pnadmin_escape($row['nickname']); ?></a>
                </td><td>
                </td><td>
                <a href="mailto:<?php echo pnadmin_escape($row['email']); ?>"><?php echo pnadmin_escape($row['email']); ?></a>
                </td><td>
                </td><td align="center">
                <?php
                if ($row['showemail'] == 'YES') {
                    ?><img src="./gfx/yes.gif" width="15" height="15" border="0" alt="<?php echo L_ALL_YES; ?>"><?php
                } else {
                    ?><img src="./gfx/no.gif" width="15" height="15" border="0" alt="<?php echo L_ALL_NO; ?>"><?php
                }
                ?>
                </td><td>
                </td><td align="center">
                <?php
                if ($this->checkadmin((int) $row['id']) === 'YES') {
                    ?><img src="./gfx/yes.gif" width="15" height="15" border="0" alt="<?php echo L_ALL_YES; ?>"><?php
                } else {
                    ?><img src="./gfx/no.gif" width="15" height="15" border="0" alt="<?php echo L_ALL_NO; ?>"><?php
                }
                ?>
                </td><td>
                </td><td align="center">
                <?php
                if ($row['status'] == 'Activated') {
                    ?><img src="./gfx/yes.gif" width="15" height="15" border="0" alt="<?php echo L_ALL_ACTIVATED; ?>"><?php
                } else {
                    ?><img src="./gfx/no.gif" width="15" height="15" border="0" alt="<?php echo L_ALL_DEACTIVATED; ?>"><?php
                }
                ?>
                </td></tr>
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
            ?>[ <?php echo L_ALL_NOPAGES; ?> ]<?php
        } else {
            $pagenum = (int) ceil($num / 25);

            for ($i = 1; $i <= $pagenum; ++$i) {
                $i2 = $i - 1;
                $current = $i2 * 25;
                ?>| <a href="index.php?page=users&subpage=search&searchin=<?php echo pnadmin_escape($searchin); ?>&searchstring=<?php echo pnadmin_escape($searchstring); ?>&current=<?php echo $current; ?>"><?php echo $i; ?></a> <?php
            }
            ?> |<?php
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
            <tr><td colspan="9" align="center">
            <?php echo L_USR_NOUSRFOUND; ?>
            </td></tr>
            <?php
        } else {
            while ($row = mysqli_fetch_array($result)) {
                ?>
                <tr><td>
                <a href="index.php?page=users&subpage=edit&userid=<?php echo (int) $row['id']; ?>"><?php echo pnadmin_escape($row['nickname']); ?></a>
                </td><td>
                </td><td>
                <a href="mailto:<?php echo pnadmin_escape($row['email']); ?>"><?php echo pnadmin_escape($row['email']); ?></a>
                </td><td>
                </td><td align="center">
                <?php
                if ($row['showemail'] == 'YES') {
                    ?><img src="./gfx/yes.gif" width="15" height="15" border="0" alt="<?php echo L_ALL_YES; ?>"><?php
                } else {
                    ?><img src="./gfx/no.gif" width="15" height="15" border="0" alt="<?php echo L_ALL_NO; ?>"><?php
                }
                ?>
                </td><td>
                </td><td align="center">
                <?php
                if ($this->checkadmin((int) $row['id']) === 'YES') {
                    ?><img src="./gfx/yes.gif" width="15" height="15" border="0" alt="<?php echo L_ALL_YES; ?>"><?php
                } else {
                    ?><img src="./gfx/no.gif" width="15" height="15" border="0" alt="<?php echo L_ALL_NO; ?>"><?php
                }
                ?>
                </td><td>
                </td><td align="center">
                <?php
                if ($row['status'] == 'Activated') {
                    ?><img src="./gfx/yes.gif" width="15" height="15" border="0" alt="<?php echo L_ALL_ACTIVATED; ?>"><?php
                } else {
                    ?><img src="./gfx/no.gif" width="15" height="15" border="0" alt="<?php echo L_ALL_DEACTIVATED; ?>"><?php
                }
                ?>
                </td></tr>
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
            ?><tr><td colspan="2" align="center"><?php echo L_PERM_NOPERMISSIONS; ?></td></tr><?php
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
                    ?>
                    <tr><td valign="top">
                    <a href="index.php?page=permissions&subpage=edit&userid=<?php echo (int) $row['userid']; ?>"><?php echo pnadmin_escape($nickname); ?></a>
                    </td><td valign="top">
                      <table border="0" cellpadding="2" cellspacing="2">
                      <tr><td>
                      <u><?php echo L_PERM_SECTION; ?></u>
                      </td><td>
                      <u><?php echo L_PERM_TEMPLATES; ?></u>
                      </td><td>
                      <u><?php echo L_PERM_CONFIG; ?></u>
                      </td><td>
                      <u><?php echo L_PERM_USER; ?></u>
                      </td><td>
                      <u><?php echo L_PERM_PERMISSIONS; ?></u>
                      </td><td>
                      <u><?php echo L_PERM_CATS; ?></u>
                      </td><td>
                      <u><?php echo L_PERM_NEWS; ?></u>
                      </td><td>
                      <u><?php echo L_PERM_COMMENTS; ?></u>
                      </td></tr>
                      <tr><td>
                      <u><?php echo L_PERM_READ; ?></u>
                      </td><td align="center">
                      <?php if ($row['canreadtemplates'] == 'YES') { ?><img src="./gfx/yes.gif" border="0" width="15" height="15"><?php } else { ?><img src="./gfx/no.gif" border="0" width="15" height="15"><?php } ?>
                      </td><td align="center">
                      <?php if ($row['canreadconfig'] == 'YES') { ?><img src="./gfx/yes.gif" border="0" width="15" height="15"><?php } else { ?><img src="./gfx/no.gif" border="0" width="15" height="15"><?php } ?>
                      </td><td align="center">
                      <?php if ($row['canreadusers'] == 'YES') { ?><img src="./gfx/yes.gif" border="0" width="15" height="15"><?php } else { ?><img src="./gfx/no.gif" border="0" width="15" height="15"><?php } ?>
                      </td><td align="center">
                      <?php if ($row['canreadpermissions'] == 'YES') { ?><img src="./gfx/yes.gif" border="0" width="15" height="15"><?php } else { ?><img src="./gfx/no.gif" border="0" width="15" height="15"><?php } ?>
                      </td><td align="center">
                      <?php if ($row['canreadcategories'] == 'YES') { ?><img src="./gfx/yes.gif" border="0" width="15" height="15"><?php } else { ?><img src="./gfx/no.gif" border="0" width="15" height="15"><?php } ?>
                      </td><td align="center">
                      <?php if ($row['canreadnews'] == 'YES') { ?><img src="./gfx/yes.gif" border="0" width="15" height="15"><?php } else { ?><img src="./gfx/no.gif" border="0" width="15" height="15"><?php } ?>
                      </td><td align="center">
                      <?php if ($row['canreadcomments'] == 'YES') { ?><img src="./gfx/yes.gif" border="0" width="15" height="15"><?php } else { ?><img src="./gfx/no.gif" border="0" width="15" height="15"><?php } ?>
                      </td></tr>
                      <tr><td>
                      <u><?php echo L_PERM_WRITE; ?></u>
                      </td><td align="center">
                      <?php if ($row['canwritetemplates'] == 'YES') { ?><img src="./gfx/yes.gif" border="0" width="15" height="15"><?php } else { ?><img src="./gfx/no.gif" border="0" width="15" height="15"><?php } ?>
                      </td><td align="center">
                      <?php if ($row['canwriteconfig'] == 'YES') { ?><img src="./gfx/yes.gif" border="0" width="15" height="15"><?php } else { ?><img src="./gfx/no.gif" border="0" width="15" height="15"><?php } ?>
                      </td><td align="center">
                      <?php if ($row['canwriteusers'] == 'YES') { ?><img src="./gfx/yes.gif" border="0" width="15" height="15"><?php } else { ?><img src="./gfx/no.gif" border="0" width="15" height="15"><?php } ?>
                      </td><td align="center">
                      <?php if ($row['canwritepermissions'] == 'YES') { ?><img src="./gfx/yes.gif" border="0" width="15" height="15"><?php } else { ?><img src="./gfx/no.gif" border="0" width="15" height="15"><?php } ?>
                      </td><td align="center">
                      <?php if ($row['canwritecategories'] == 'YES') { ?><img src="./gfx/yes.gif" border="0" width="15" height="15"><?php } else { ?><img src="./gfx/no.gif" border="0" width="15" height="15"><?php } ?>
                      </td><td align="center">
                      <?php if ($row['canwritenews'] == 'YES') { ?><img src="./gfx/yes.gif" border="0" width="15" height="15"><?php } else { ?><img src="./gfx/no.gif" border="0" width="15" height="15"><?php } ?>
                      </td><td align="center">
                      <?php if ($row['canwritecomments'] == 'YES') { ?><img src="./gfx/yes.gif" border="0" width="15" height="15"><?php } else { ?><img src="./gfx/no.gif" border="0" width="15" height="15"><?php } ?>
                      </td></tr>
                      </table>
                    </td></tr>
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
            ?><tr><td colspan="3" align="center"><?php echo L_CAT_NOCATSAVAILABLE; ?></a></td></tr><?php
        } else {
            while ($row = mysqli_fetch_array($result)) {
                ?>
                <tr><td>
                <a href="index.php?page=categories&subpage=edit&catid=<?php echo (int) $row['id']; ?>"><?php echo pnadmin_escape(stripslashes((string) $row['name'])); ?></a>
                </td><td>
                </td><td>
                <?php echo pnadmin_escape($row['description']); ?>
                </td><td>
                </td><td align="center">
                <?php
                if ($row['status'] == 'Activated') {
                    ?><img src="./gfx/yes.gif" border="0" width="15" height="15" alt="<?php echo L_ALL_ACTIVATED; ?>"><?php
                } else {
                    ?><img src="./gfx/no.gif" border="0" width="15" height="15" alt="<?php echo L_ALL_DEACTIVATED; ?>"><?php
                }
                ?>
                </td></tr>
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
            ?><select name="catid" size="1"><?php
            if ($catid === 0) {
                ?><option value=""><?php echo L_NEWS_CHOOSECAT; ?></option><?php
            }

            while ($row = mysqli_fetch_array($result)) {
                ?><option value="<?php echo (int) $row['id']; ?>" <?php if ($catid == $row['id']) { ?>selected<?php } ?>><?php echo pnadmin_escape(stripslashes((string) $row['name'])); ?></option><?php
            }
            ?></select><?php
        } else {
            echo L_NEWS_NOCATSAVAILABLE;
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
            ?>[ <?php echo L_ALL_NOPAGES; ?> ]<?php
        } else {
            $pagenum = (int) ceil($num / 25);

            for ($i = 1; $i <= $pagenum; ++$i) {
                $i2 = $i - 1;
                $current = $i2 * 25;
                ?>| <a href="index.php?page=news&subpage=show&current=<?php echo $current; ?>"><?php echo $i; ?></a> <?php
            }
            ?> |<?php
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

        if ($num == 0) {
            ?>
            <tr><td colspan="7" align="center">
            <?php echo L_NEWS_NONEWS; ?>
            </td></tr>
            <?php
        } else {
            while ($row = mysqli_fetch_array($result)) {
                ?>
                <tr><td>
                <?php echo date('d.m.Y', (int) $row['time']); ?>
                </td><?php if ($pnconfig['categories'] == 'YES') { ?><td>
                &nbsp;
                </td><td>
                <?php echo pnadmin_escape($this->getcatname((int) $row['catid'])); ?>
                </td><?php } ?><td>
                &nbsp;
                </td><td>
                <a href="index.php?page=news&subpage=edit&newsid=<?php echo (int) $row['id']; ?>"><?php echo pnadmin_escape(stripslashes((string) $row['title'])); ?></a>
                </td><td>
                &nbsp;
                </td><td align="center">
                <?php
                if ($row['status'] == 'Activated') {
                    ?><img src="./gfx/yes.gif" width="15" height="15" alt="<?php echo L_ALL_ACTIVATED; ?>"><?php
                } elseif ($row['status'] == 'Unchecked') {
                    ?><img src="./gfx/uc.gif" width="15" height="15" alt="<?php echo L_ALL_UNCHECKED; ?>"><?php
                } else {
                    ?><img src="./gfx/no.gif" width="15" height="15" alt="<?php echo L_ALL_DEACTIVATED; ?>"><?php
                }
                ?>
                </td></tr>
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
            ?><tr><td colspan="2" align="center"><?php echo L_NEWS_NOCOMMENTS; ?></td></tr><?php
        } else {
            while ($row = mysqli_fetch_array($result)) {
                ?>
                <tr><td>
                <b><?php echo L_NEWS_DELETECOMMENT; ?></b><br>
                <small class="info"><?php echo L_NEWS_DELETECOMMENT; ?></small>
                </td><td>
                <input type="hidden" name="commentid[]" value="<?php echo (int) $row['id']; ?>">
                <input type="checkbox" name="commentdelete[]" value="<?php echo (int) $row['id']; ?>">
                </td></tr>
                <tr><td valign="top">
                <b><?php echo L_NEWS_INFO; ?></b><br>
                <small class="info"><?php echo L_NEWS_INFO_DESC; ?></small>
                </td><td>
                <?php echo L_NEWS_WRITTENBY; ?> <?php if ($row['userid'] == '0') {
                    echo L_NEWS_GUEST;
                } else {
                    echo $this->getcommentauthor((int) $row['userid']);
                } ?> <?php echo L_NEWS_ONDATE; ?> <?php echo date('d.m.Y', (int) $row['time']); ?> <?php echo L_NEWS_AT; ?> <?php echo date('H:i', (int) $row['time']); ?> (IP: <?php echo pnadmin_escape($row['ip']); ?>)
                </td></tr>
                <tr><td valign="top">
                <b><?php echo L_NEWS_TEXT; ?></b><br>
                <small class="info"><?php echo L_NEWS_COMMENTEXT_DESC; ?> (HMTL
                <?php
                if ($pnconfig['html'] == 'Comments' || $pnconfig['html'] == 'Comments/News') {
                    ?><b><?php echo L_NEWS_ON; ?></b>/<a href="index.php?page=other&subpage=help#news.bbcode">BB Code</a> <?php
                } else {
                    ?><b><?php echo L_NEWS_OFF; ?></b>/<a href="index.php?page=other&subpage=help#news.bbcode">BB Code</a> <?php
                }

                if ($pnconfig['bbcode'] == 'Comments' || $pnconfig['bbcode'] == 'Comments/News') {
                    ?><b><?php echo L_NEWS_ON; ?></b>)<?php
                } else {
                    ?><b><?php echo L_NEWS_OFF; ?></b>)<?php
                }
                ?>
                </small>
                </td><td>
                <textarea name="commenttext[]" cols="60" rows="5"><?php echo pnadmin_escape(stripslashes((string) $row['text'])); ?></textarea>
                </td></tr>
                <tr><td height="20" colspan="2">
                &nbsp;
                </td></tr>
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
            ?>[ <?php echo L_ALL_NOPAGES; ?> ]<?php
        } else {
            $pagenum = (int) ceil($num / 25);

            for ($i = 1; $i <= $pagenum; ++$i) {
                $i2 = $i - 1;
                $current = $i2 * 25;
                ?>| <a href="index.php?page=news&subpage=search&searchin=<?php echo pnadmin_escape($searchin); ?>&searchstring=<?php echo pnadmin_escape($searchstring); ?>&current=<?php echo $current; ?>"><?php echo $i; ?></a> <?php
            }
            ?> |<?php
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

        if ($num == 0) {
            ?>
            <tr><td colspan="7" align="center">
            <?php echo L_NEWS_NONEWS; ?>
            </td></tr>
            <?php
        } else {
            while ($row = mysqli_fetch_array($result)) {
                ?>
                <tr><td>
                <?php echo date('d.m.Y', (int) $row['time']); ?>
                </td><?php if ($pnconfig['categories'] == 'YES') { ?><td>
                &nbsp;
                </td><td>
                <?php echo pnadmin_escape($this->getcatname((int) $row['catid'])); ?>
                </td><?php } ?><td>
                &nbsp;
                </td><td>
                <a href="index.php?page=news&subpage=edit&newsid=<?php echo (int) $row['id']; ?>"><?php echo pnadmin_escape(stripslashes((string) $row['title'])); ?></a>
                </td><td>
                &nbsp;
                </td><td align="center">
                <?php
                if ($row['status'] == 'Activated') {
                    ?><img src="./gfx/yes.gif" width="15" height="15" alt="<?php echo L_ALL_ACTIVATED; ?>"><?php
                } elseif ($row['status'] == 'Unchecked') {
                    ?><img src="./gfx/uc.gif" width="15" height="15" alt="<?php echo L_ALL_UNCHECKED; ?>"><?php
                } else {
                    ?><img src="./gfx/no.gif" width="15" height="15" alt="<?php echo L_ALL_DEACTIVATED; ?>"><?php
                }
                ?>
                </td></tr>
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
