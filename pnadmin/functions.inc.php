<?php
declare(strict_types=1);

/* PowerNews - PHP and MySQL based news script                          */
/* Copyright (c) 2001-2026 PowerScripts                                 */

/* MIT License - See LICENSE file for full license text                 */
/* https://github.com/schubertnico/PowerNews.git                        */

require_once __DIR__ . '/../pninc/core.inc.php';
require_once __DIR__ . '/../pninc/migrations.inc.php';

/**
 * Helper function to escape output for HTML (admin).
 */
function pnadmin_escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

/**
 * CSRF-Token für Admin-Formulare (B36). Für angemeldete Admins wird es aus dem geheimen
 * Sitzungstoken abgeleitet und gilt so lange wie die Admin-Sitzung (kein Ablauf nach
 * 24 Minuten wie bei der PHP-Session). Vor dem Login dient das Token der PHP-Session.
 */
function pnadmin_csrf_token(): string
{
    global $pnadminsession;

    if (is_array($pnadminsession) && isset($pnadminsession[1])) {
        return hash_hmac('sha256', 'pn-admin-csrf', (string) $pnadminsession[1]);
    }

    return pn_csrf_token();
}

/**
 * Prüft ein CSRF-Token aus einem Admin-Formular.
 */
function pnadmin_csrf_verify(mixed $token): bool
{
    global $pnadminsession;

    if (!is_string($token) || $token === '') {
        return false;
    }

    if (is_array($pnadminsession) && isset($pnadminsession[1])
        && hash_equals(hash_hmac('sha256', 'pn-admin-csrf', (string) $pnadminsession[1]), $token)) {
        return true;
    }

    return pn_csrf_verify($token);
}

/**
 * Verstecktes Formularfeld mit dem CSRF-Token.
 */
function pnadmin_csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . pnadmin_escape(pnadmin_csrf_token()) . '">';
}

/**
 * Zentrale Prüfung jeder schreibenden Admin-Anfrage (B36): Aktionen (add/edit/Login/Logout
 * oder jedes POST) brauchen POST und ein gültiges CSRF-Token. Sonst werden die Aktions-
 * parameter und POST-Daten verworfen; die Seite zeigt dann nur ihr Formular.
 *
 * @return bool true, wenn die Anfrage abgewiesen wurde
 */
function pnadmin_guard_request(): bool
{
    $flags = ['pnlogin', 'pnlogout', 'add', 'edit', 'editcomments'];
    $isPost = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
    $isAction = $isPost;

    foreach ($flags as $flag) {
        if (($_GET[$flag] ?? '') === 'YES') {
            $isAction = true;
        }
    }

    if (!$isAction || ($isPost && pnadmin_csrf_verify($_POST['csrf_token'] ?? null))) {
        return false;
    }

    $_POST = [];
    $_FILES = [];

    foreach ($flags as $flag) {
        unset($_GET[$flag]);
    }

    return true;
}

/**
 * Führt ein Prepared Statement aus und fängt Datenbankfehler ab. Statt eines HTTP-500
 * (Fatal Error durch mysqli_sql_exception) erhält der Aufrufer false und kann eine
 * verständliche Meldung anzeigen. Die technische Ursache landet im Fehlerlog.
 */
function pnadmin_execute(mysqli_stmt|false $stmt): bool
{
    if ($stmt === false) {
        return false;
    }

    try {
        return mysqli_stmt_execute($stmt);
    } catch (mysqli_sql_exception $e) {
        error_log('[PowerNews] Datenbankfehler: ' . $e->getMessage());

        return false;
    }
}

/**
 * Baut die WHERE-Bedingung für die Admin-Suche. Textfelder werden per LIKE durchsucht,
 * „id“ als exakter Zahlenvergleich. Unbekannte Felder fallen auf das erste Textfeld zurück.
 *
 * @param list<string> $textFields
 *
 * @return array{0: string, 1: string, 2: int|string}
 */
function pnadmin_search_condition(string $searchin, string $searchstring, array $textFields): array
{
    if ($searchin === 'id') {
        $id = filter_var(trim($searchstring), FILTER_VALIDATE_INT);

        return ['id = ?', 'i', $id === false ? 0 : $id];
    }

    if (!in_array($searchin, $textFields, true)) {
        $searchin = $textFields[0];
    }

    return ['`' . $searchin . '` LIKE ?', 's', '%' . $searchstring . '%'];
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
 * Prüft, ob das Admin-Cookie zu einem eingeloggten Admin mit canwriteconfig=YES gehört.
 * Gibt das kombinierte User+Permissions-Array zurück oder null.
 *
 * Wird von update.php und convert.php genutzt, um den Admin-Zugriff zu verifizieren,
 * ohne dass der volle phpheader-Flow benötigt wird.
 */
function pnadmin_auth_check(): ?array
{
    global $pn_config, $pn_handler;

    $session = pn_session_parse_cookie(PN_COOKIE_ADMIN);

    if ($session === null) {
        return null;
    }

    [$userId, $token] = $session;
    $user = (new getadmin())->getuserdata($userId, $token);

    if (($user['loggedin'] ?? 'NO') !== 'YES') {
        return null;
    }

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
    /**
     * Prüft die Admin-Anmeldung (B09): Fehlversuchsbremse wie im Frontend, eine einheitliche
     * Meldung für unbekannte Nicknames, falsche Passwörter, deaktivierte Konten und fehlende
     * Rechte sowie Protokollierung jedes Versuchs in pn_login_attempts.
     */
    public function checklogin(string $nickname, string $password): string
    {
        global $pn_handler, $pn_config;

        $ip = pn_client_ip();

        if (pn_login_throttled($pn_handler, $ip)) {
            return L_USR_TOOMANYATTEMPTS;
        }

        $stmt = mysqli_prepare($pn_handler, 'SELECT * FROM ' . $pn_config['usertable'] . ' WHERE nickname = ?');
        mysqli_stmt_bind_param($stmt, 's', $nickname);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $valid = false;
        $userId = 0;

        if (mysqli_num_rows($result) === 1) {
            $row = mysqli_fetch_array($result);
            $userId = (int) $row['id'];
            $valid = pnadmin_verify_password($password, (string) $row['password'], $userId)
                && ($row['status'] ?? 'Activated') === 'Activated'
                && $this->checkpermissions($userId) === '';
        } else {
            // Gleiche Rechenzeit wie bei vorhandenen Konten (keine Benutzer-Enumeration).
            password_verify($password, PN_DUMMY_PASSWORD_HASH);
        }

        pn_login_record($pn_handler, $ip, $nickname, $valid);

        if (!$valid) {
            error_log(sprintf('[PowerNews] Fehlgeschlagener Admin-Login für Nickname "%s" von %s', addcslashes(mb_substr($nickname, 0, 100), "\0..\37\"\\"), $ip));

            return L_USR_LOGINFAILED;
        }

        $this->logincookie($userId);

        return 'loggedin';
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

    /**
     * Legt eine Admin-Sitzung an (B25/B26): Token nur in pn_sessions (gehasht), Laufzeit
     * 8 Stunden mit gleitender Verlängerung, Cookie „pnadmincookie“ endet mit dem Browser.
     */
    public function logincookie(int $userid): void
    {
        global $pncookie, $pn_handler;

        $token = pn_session_create($pn_handler, $userid, 'admin');
        $cookiestring = $userid . ':' . $token;
        pn_session_cookie(PN_COOKIE_ADMIN, $cookiestring, 0);

        $pncookie = $cookiestring;
    }

    public function logout(): void
    {
        global $pn_handler;

        $session = pn_session_parse_cookie(PN_COOKIE_ADMIN);

        if ($session !== null) {
            pn_session_delete($pn_handler, $session[1], 'admin');
        }

        pn_session_cookie(PN_COOKIE_ADMIN, '', time() - 3600);
        unset($_COOKIE[PN_COOKIE_ADMIN]);
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
            return pn_template_fill((string) $addemail, [
                'NICKNAME' => $nickname,
                'EMAIL' => $email,
                'PASSWORD' => $password,
                'URL' => (string) $pnconfig['url'],
            ]);
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
            return pn_template_fill((string) $editemail, [
                'NICKNAME' => $nickname,
                'EMAIL' => $email,
                'PASSWORD' => $password,
                'URL' => (string) $pnconfig['url'],
            ]);
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
                <div class="mt-2"><a href="index.php?page=templates&amp;subpage=add" class="btn btn-sm btn-outline-secondary"><?php echo L_ALL_BACKTOFORM; ?></a></div>
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
                        <div class="mt-2"><a href="index.php?page=templates&amp;subpage=show" class="btn btn-sm btn-success"><?php echo L_ALL_BACKTOLIST; ?></a></div>
                    </div>
                    <?php
                } else {
                    ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo L_TEMPL_TEMPLATEALREADYEXISTS; ?>
                        <div class="mt-2"><a href="index.php?page=templates&amp;subpage=add" class="btn btn-sm btn-outline-secondary"><?php echo L_ALL_BACKTOFORM; ?></a></div>
                    </div>
                    <?php
                }
            } else {
                ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo L_TEMPL_NOSTANDARDTEMPLATE; ?>
                    <div class="mt-2"><a href="index.php?page=templates" class="btn btn-sm btn-outline-secondary"><?php echo L_ALL_BACK; ?></a></div>
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
                <div class="mt-2"><a href="index.php?page=templates&amp;subpage=edit&amp;templateid=<?php echo $templateid; ?>" class="btn btn-sm btn-outline-secondary"><?php echo L_ALL_BACKTOFORM; ?></a></div>
            </div>
            <?php
        } else {
            // Default-Template (id=1) darf editiert werden, aber NICHT geloescht.
            if ($templateid === 1 && $delete === 'YES') {
                ?>
                <div class="alert alert-warning" role="alert">
                    <?php echo L_TEMPL_DEFAULTNOTDELETABLE; ?>
                    <div class="mt-2"><a href="index.php?page=templates&amp;subpage=edit&amp;templateid=1" class="btn btn-sm btn-outline-secondary"><?php echo L_ALL_BACKTOFORM; ?></a></div>
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
                        <div class="mt-2"><a href="index.php?page=templates&amp;subpage=show" class="btn btn-sm btn-success"><?php echo L_ALL_BACKTOLIST; ?></a></div>
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
                        <div class="mt-2"><a href="index.php?page=templates&amp;subpage=edit&amp;templateid=<?php echo $templateid; ?>" class="btn btn-sm btn-success"><?php echo L_ALL_EDITAGAIN; ?></a></div>
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
     * Holt die Benutzerdaten und prüft die Admin-Sitzung. Als Nachweis gilt ausschließlich
     * ein gültiges Sitzungstoken aus pn_sessions; der gespeicherte Passwort-Hash wird nicht
     * mehr akzeptiert (B25).
     */
    public function getuserdata(int $userid, string $token): array
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

            if (preg_match('/^[a-f0-9]{64}$/', $token) === 1 && pn_session_validate($pn_handler, $userid, $token, 'admin')) {
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

        // Dieselben Regeln wie bei der Registrierung im Frontend (B45).
        if (pn_validate_nickname($nickname) === '') {
            return L_USR_INVALIDNICKNAME;
        }

        if (pn_validate_email($email) === '') {
            return L_USR_WRONGEMAIL;
        }

        $stmt = mysqli_prepare($pn_handler, 'SELECT * FROM ' . $pn_config['usertable'] . ' WHERE nickname = ? OR email = ?');
        mysqli_stmt_bind_param($stmt, 'ss', $nickname, $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $num = mysqli_num_rows($result);

        if ($num == 0) {
            $password = $this->generate_password();
            $hashedPassword = pnadmin_hash_password($password);
            $showemail = pn_validate_yesno($showemail, 'NO');
            $now = time();
            $status = 'Activated';
            $stmt = mysqli_prepare($pn_handler, 'INSERT INTO ' . $pn_config['usertable'] . ' (nickname, email, password, registered, showemail, status) VALUES(?, ?, ?, ?, ?, ?)');
            mysqli_stmt_bind_param($stmt, 'sssiss', $nickname, $email, $hashedPassword, $now, $showemail, $status);

            if (!pnadmin_execute($stmt)) {
                return L_USR_SAVEFAILED;
            }

            if ($sendemail === 'YES') {
                $emailObj = new email();
                $emailObj->addemail($nickname, $email, $password);
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
            ?><li class="page-item disabled"><span class="page-link">[ <?php echo L_ALL_NOPAGES; ?> ]</span></li><?php
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
        } elseif (pn_validate_nickname($nickname) === '') {
            $error = L_USR_INVALIDNICKNAME;
        } else {
            $stmt = mysqli_prepare($pn_handler, 'SELECT * FROM ' . $pn_config['usertable'] . ' WHERE (nickname = ? OR email = ?) AND id != ?');
            mysqli_stmt_bind_param($stmt, 'ssi', $nickname, $email, $userid);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $num = mysqli_num_rows($result);

            if ($num == 0) {
                if (pn_validate_email($email) !== '') {
                    if ($newpassword === 'YES') {
                        $password = $this->generate_password();
                        $hashedPassword = pnadmin_hash_password($password);
                        $stmt = mysqli_prepare($pn_handler, 'UPDATE ' . $pn_config['usertable'] . ' SET password = ? WHERE id = ?');
                        mysqli_stmt_bind_param($stmt, 'si', $hashedPassword, $userid);
                        mysqli_stmt_execute($stmt);
                        pn_sessions_delete_for_user($pn_handler, $userid);
                    }

                    $showemail = pn_validate_yesno($showemail, 'NO');
                    $status = (string) pn_validate_whitelist($status, ['Activated', 'Deactivated'], 'Activated');
                    $stmt = mysqli_prepare($pn_handler, 'UPDATE ' . $pn_config['usertable'] . ' SET nickname = ?, email = ?, showemail = ?, status = ? WHERE id = ?');
                    mysqli_stmt_bind_param($stmt, 'ssssi', $nickname, $email, $showemail, $status, $userid);

                    if (!pnadmin_execute($stmt)) {
                        return L_USR_SAVEFAILED;
                    }

                    if ($status === 'Deactivated') {
                        pn_sessions_delete_for_user($pn_handler, $userid);
                    }

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

        [$where, $type, $value] = pnadmin_search_condition($searchin, $searchstring, ['nickname', 'email']);
        $stmt = mysqli_prepare($pn_handler, 'SELECT * FROM ' . $pn_config['usertable'] . ' WHERE ' . $where);
        mysqli_stmt_bind_param($stmt, $type, $value);
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
                ?><li class="page-item<?php echo $isActive; ?>"><a class="page-link" href="index.php?page=users&amp;subpage=search&amp;search=YES&amp;searchin=<?php echo pnadmin_escape($searchin); ?>&amp;searchstring=<?php echo pnadmin_escape(rawurlencode($searchstring)); ?>&amp;current=<?php echo $current; ?>"><?php echo $i; ?></a></li><?php
            }
        }
    }

    public function searchuser(string $searchin, string $searchstring, int $current): void
    {
        global $pn_config, $pn_handler;

        [$where, $type, $value] = pnadmin_search_condition($searchin, $searchstring, ['nickname', 'email']);
        $stmt = mysqli_prepare($pn_handler, 'SELECT * FROM ' . $pn_config['usertable'] . ' WHERE ' . $where . ' ORDER BY nickname LIMIT ?, 25');
        mysqli_stmt_bind_param($stmt, $type . 'i', $value, $current);
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
            // Passwort nur prüfen, wenn eines eingegeben wurde
            $changePassword = ($password !== '' || $password2 !== '');
            $showemail = pn_validate_yesno($showemail, 'NO');

            if (pn_validate_nickname($nickname) === '') {
                $error = L_USR_INVALIDNICKNAME;
            } elseif ($changePassword && $password !== $password2) {
                $error = L_USR_PWNOTCONFIRMED;
            } elseif ($changePassword && strlen($password) < 8) {
                $error = L_USR_PASSWORDTOOSHORT;
            } elseif (pn_validate_email($email) === '') {
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

                    if (!pnadmin_execute($stmt3)) {
                        $error = L_USR_SAVEFAILED;
                    } elseif ($changePassword) {
                        // Neues Passwort: alle Sitzungen beenden, auch die aktuelle (B26).
                        pn_sessions_delete_for_user($pn_handler, $userid);
                    }
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

                if (!pnadmin_execute($stmt)) {
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
                            return '<span class="badge text-bg-success" aria-label="' . L_ALL_YES . '">&check;</span>';
                        }
                        return '<span class="badge text-bg-secondary" aria-label="' . L_ALL_NO . '">&minus;</span>';
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

                if (!pnadmin_execute($stmt)) {
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

                if (!pnadmin_execute($stmt)) {
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

            if (!pnadmin_execute($stmt)) {
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
    /** pn_categories.description ist ein TINYTEXT (255 Byte, Umlaute zählen doppelt). */
    public const DESCRIPTION_MAX_BYTES = 255;

    public function addcat(string $name, string $description, array $picture = []): string
    {
        global $pn_config, $pnconfig, $pn_handler;
        $error = '';

        if (strlen($description) > self::DESCRIPTION_MAX_BYTES) {
            return L_CAT_DESCRIPTIONTOOLONG;
        }

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
                    $status = 'Activated';
                    $stmt = mysqli_prepare($pn_handler, 'INSERT INTO ' . $pn_config['cattable'] . ' (name, description, picture, status) VALUES(?, ?, ?, ?)');
                    mysqli_stmt_bind_param($stmt, 'ssss', $name, $description, $pic, $status);

                    if (!pnadmin_execute($stmt)) {
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
                    <td><a href="index.php?page=categories&amp;subpage=edit&amp;catid=<?php echo (int) $row['id']; ?>"><?php echo pnadmin_escape((string) $row['name']); ?></a></td>
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
            return mysqli_fetch_array($result);
        }

        return null;
    }

    public function editcat(string $name, string $description, string $uploadpic = '', array $picture = [], string $status = '', int $catid = 0): string
    {
        global $pn_config, $pnconfig, $pn_handler;
        $error = '';

        if (strlen($description) > self::DESCRIPTION_MAX_BYTES) {
            return L_CAT_DESCRIPTIONTOOLONG;
        }

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
                        $stmt3 = mysqli_prepare($pn_handler, 'UPDATE ' . $pn_config['cattable'] . ' SET name = ?, description = ?, picture = ?, status = ? WHERE id = ?');
                        mysqli_stmt_bind_param($stmt3, 'ssssi', $name, $description, $pic, $status, $catid);

                        if (!pnadmin_execute($stmt3)) {
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
                ?><option value="<?php echo (int) $row['id']; ?>" <?php if ($catid == $row['id']) { ?>selected<?php } ?>><?php echo pnadmin_escape((string) $row['name']); ?></option><?php
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

        $addtime = $time === [] ? time() : self::parsetime($time);

        if ($addtime === null) {
            return L_NEWS_INVALIDDATE;
        }

        $status = 'Activated';
        $userId = (int) $pnuser['id'];

        $stmt = mysqli_prepare($pn_handler, 'INSERT INTO ' . $pn_config['newstable'] . ' (userid, time, catid, title, text, moretext, status, relatedlinks) VALUES(?, ?, ?, ?, ?, ?, ?, ?)');
        mysqli_stmt_bind_param($stmt, 'iiisssss', $userId, $addtime, $catid, $title, $text, $moretext, $status, $relatedlinks);

        if (!pnadmin_execute($stmt)) {
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

            return (string) $name;
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
                    <td><a href="index.php?page=news&amp;subpage=edit&amp;newsid=<?php echo (int) $row['id']; ?>"><?php echo pnadmin_escape((string) $row['title']); ?></a></td>
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
            return mysqli_fetch_array($result);
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
                            <textarea class="form-control" name="commenttext[]" id="pn_commenttext_<?php echo (int) $row['id']; ?>" rows="4" aria-describedby="pn_commenttext_help_<?php echo (int) $row['id']; ?>"><?php echo pnadmin_escape((string) $row['text']); ?></textarea>
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
            $ctext = (string) ($commenttext[$i] ?? '');

            $stmt = mysqli_prepare($pn_handler, 'UPDATE ' . $pn_config['commenttable'] . ' SET text = ? WHERE id = ?');
            mysqli_stmt_bind_param($stmt, 'si', $ctext, $cid);

            if (!pnadmin_execute($stmt)) {
                $error = L_NEWS_COMMENTEDITERROR;
            }

            $commentDeleteCount = count($commentdelete);

            for ($i2 = 0; $i2 < $commentDeleteCount; ++$i2) {
                if ((int) $commentdelete[$i2] === $cid) {
                    $stmt2 = mysqli_prepare($pn_handler, 'DELETE FROM ' . $pn_config['commenttable'] . ' WHERE id = ?');
                    mysqli_stmt_bind_param($stmt2, 'i', $cid);

                    if (!pnadmin_execute($stmt2)) {
                        $error = L_NEWS_COMMENTEDITERROR;
                    }
                }
            }
        }

        return $error;
    }

    /**
     * Speichert eine bearbeitete News oder löscht sie samt Kommentaren ($delete === 'YES').
     *
     * Leere Arrays für die weiterführenden Links bedeuten „unverändert lassen“: Ist die
     * Funktion in der Konfiguration abgeschaltet, sendet das Formular keine Linkfelder,
     * und vorhandene Links dürfen dann nicht verloren gehen.
     */
    public function editnews(int $newsid, int $catid, string $title, string $text, string $moretext, string $status, string $delete, array $rl_title, array $rl_url, array $rl_target, array $time): string
    {
        global $pn_config, $pn_handler;

        if ($delete === 'YES') {
            return $this->deletenews($newsid);
        }

        $current = $this->getnewsdata($newsid);

        if ($current === null) {
            return L_NEWS_CHOOSENEWS;
        }

        $relatedlinks = (string) $current['relatedlinks'];

        if ($rl_title !== [] || $rl_url !== []) {
            $relatedlinks = '';
            $counter = count($rl_title);

            for ($i = 0; $i < $counter; ++$i) {
                if (trim((string) $rl_title[$i]) && trim((string) ($rl_url[$i] ?? ''))) {
                    $relatedlinks .= $rl_title[$i] . '!@!@!' . $rl_url[$i] . '!@!@!' . ($rl_target[$i] ?? '') . "\n";
                }
            }
        }

        $newtime = self::parsetime($time);

        if ($newtime === null) {
            return L_NEWS_INVALIDDATE;
        }

        $stmt = mysqli_prepare($pn_handler, 'UPDATE ' . $pn_config['newstable'] . ' SET time = ?, catid = ?, title = ?, text = ?, moretext = ?, status = ?, relatedlinks = ? WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'iisssssi', $newtime, $catid, $title, $text, $moretext, $status, $relatedlinks, $newsid);

        if (!pnadmin_execute($stmt)) {
            return L_NEWS_NEWSNOTEDITED;
        }

        return '';
    }

    /**
     * Löscht eine News und alle zugehörigen Kommentare.
     */
    public function deletenews(int $newsid): string
    {
        global $pn_config, $pn_handler;

        $stmt = mysqli_prepare($pn_handler, 'DELETE FROM ' . $pn_config['newstable'] . ' WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'i', $newsid);

        if (!pnadmin_execute($stmt)) {
            return L_NEWS_NEWSNOTDELETED;
        }

        $cstmt = mysqli_prepare($pn_handler, 'DELETE FROM ' . $pn_config['commenttable'] . ' WHERE newsid = ?');
        mysqli_stmt_bind_param($cstmt, 'i', $newsid);
        mysqli_stmt_execute($cstmt);

        return '';
    }

    /**
     * Wandelt die Formularwerte des Erscheinungstermins in einen Zeitstempel um.
     * Liefert null, wenn ein Wert fehlt oder das Datum nicht existiert (z. B. 31.02.).
     */
    public static function parsetime(array $time): ?int
    {
        $parts = [];

        foreach (['day' => [1, 31], 'month' => [1, 12], 'year' => [1970, 2100], 'hour' => [0, 23], 'min' => [0, 59]] as $key => [$min, $max]) {
            $value = filter_var($time[$key] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => $min, 'max_range' => $max]]);

            if ($value === false) {
                return null;
            }
            $parts[$key] = $value;
        }

        if (!checkdate($parts['month'], $parts['day'], $parts['year'])) {
            return null;
        }

        $timestamp = mktime($parts['hour'], $parts['min'], 0, $parts['month'], $parts['day'], $parts['year']);

        return $timestamp === false ? null : $timestamp;
    }

    /**
     * Gibt die Auswahlfelder für den Erscheinungstermin aus. Die Jahresliste reicht zehn
     * Jahre zurück (Archivpflege) und fünf Jahre voraus und enthält immer das Jahr der News.
     */
    public function timeselect(int $timestamp): void
    {
        $selected = [
            'day' => (int) date('j', $timestamp),
            'month' => (int) date('n', $timestamp),
            'year' => (int) date('Y', $timestamp),
            'hour' => (int) date('G', $timestamp),
            'min' => (int) date('i', $timestamp),
        ];
        $currentYear = (int) date('Y');
        $monthNames = [
            1 => L_NEWS_JANUARY, 2 => L_NEWS_FEBRUARY, 3 => L_NEWS_MARCH, 4 => L_NEWS_APRIL,
            5 => L_NEWS_MAY, 6 => L_NEWS_JUNE, 7 => L_NEWS_JULY, 8 => L_NEWS_AUGUST,
            9 => L_NEWS_SEPTEMBER, 10 => L_NEWS_OCTOBER, 11 => L_NEWS_NOVEMBER, 12 => L_NEWS_DECEMBER,
        ];
        $fields = [
            'day' => [L_NEWS_DAY, range(1, 31)],
            'month' => [L_NEWS_MONTH, range(1, 12)],
            'year' => [L_NEWS_YEAR, range(min($currentYear - 10, $selected['year']), max($currentYear + 5, $selected['year']))],
            'hour' => [L_NEWS_HOUR, range(0, 23)],
            'min' => [L_NEWS_MIN, range(0, 59)],
        ];

        foreach ($fields as $key => [$label, $values]) {
            if ($key === 'hour') {
                echo '<span aria-hidden="true">&#64;</span>';
            } elseif ($key === 'min') {
                echo '<span aria-hidden="true">:</span>';
            }
            echo '<select class="form-select form-select-sm w-auto" name="time[' . $key . ']" aria-label="' . pnadmin_escape($label) . '">';

            foreach ($values as $value) {
                $text = match ($key) {
                    'month' => $monthNames[$value],
                    'year' => (string) $value,
                    default => sprintf('%02d', $value),
                };
                echo '<option value="' . $value . '"' . ($value === $selected[$key] ? ' selected' : '') . '>' . pnadmin_escape($text) . '</option>';
            }
            echo '</select>';
        }
    }

    /**
     * Hinweis unter den Textfeldern, ob BB-Code in News ausgewertet wird.
     */
    public function formathint(): string
    {
        global $pnconfig;

        $active = in_array($pnconfig['bbcode'] ?? 'NO', ['News', 'Comments/News'], true);

        return '(<a href="index.php?page=other&amp;subpage=help#help-other-bbcode" target="_blank" rel="noopener noreferrer">'
            . L_NEWS_BBCODE . '</a> <strong>' . ($active ? L_NEWS_ON : L_NEWS_OFF) . '</strong>)';
    }

    public function listsearchpages(string $searchin, string $searchstring): void
    {
        global $pn_config, $pn_handler;

        [$where, $type, $value] = pnadmin_search_condition($searchin, $searchstring, ['title', 'text', 'moretext']);
        $stmt = mysqli_prepare($pn_handler, 'SELECT * FROM ' . $pn_config['newstable'] . ' WHERE ' . $where);
        mysqli_stmt_bind_param($stmt, $type, $value);
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
                ?><li class="page-item<?php echo $isActive; ?>"><a class="page-link" href="index.php?page=news&amp;subpage=search&amp;search=YES&amp;searchin=<?php echo pnadmin_escape($searchin); ?>&amp;searchstring=<?php echo pnadmin_escape(rawurlencode($searchstring)); ?>&amp;current=<?php echo $current; ?>"><?php echo $i; ?></a></li><?php
            }
        }
    }

    public function searchnews(string $searchin, string $searchstring, int $current): void
    {
        global $pn_config, $pnconfig, $pn_handler;

        [$where, $type, $value] = pnadmin_search_condition($searchin, $searchstring, ['title', 'text', 'moretext']);
        $stmt = mysqli_prepare($pn_handler, 'SELECT * FROM ' . $pn_config['newstable'] . ' WHERE ' . $where . ' ORDER BY id DESC LIMIT ?, 25');
        mysqli_stmt_bind_param($stmt, $type . 'i', $value, $current);
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
                    <td><a href="index.php?page=news&amp;subpage=edit&amp;newsid=<?php echo (int) $row['id']; ?>"><?php echo pnadmin_escape((string) $row['title']); ?></a></td>
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
