<?php
declare(strict_types=1);

/* PowerNews - PHP and MySQL based news script                          */
/* Copyright (c) 2001-2024 PowerScripts                                 */

/* MIT License - See LICENSE file for full license text                 */
/* https://github.com/schubertnico/PowerNews.git                        */

/**
 * Helper function to escape output for HTML.
 */
function pn_escape(string|int|float|null $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/**
 * Helper function for prepared statement with single integer parameter.
 */
function pn_query_by_id(mysqli $handler, string $query, int $id): mysqli_result|false
{
    $stmt = mysqli_prepare($handler, $query);

    if (!$stmt) {
        return false;
    }
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);

    return mysqli_stmt_get_result($stmt);
}

/**
 * Helper function for prepared statement with single string parameter.
 */
function pn_query_by_string(mysqli $handler, string $query, string $value): mysqli_result|false
{
    $stmt = mysqli_prepare($handler, $query);

    if (!$stmt) {
        return false;
    }
    mysqli_stmt_bind_param($stmt, 's', $value);
    mysqli_stmt_execute($stmt);

    return mysqli_stmt_get_result($stmt);
}

/**
 * Check if password is legacy base64 encoded (not bcrypt).
 */
function pn_is_legacy_password(string $hash): bool
{
    return !str_starts_with($hash, '$2y$') && !str_starts_with($hash, '$2a$') && !str_starts_with($hash, '$argon');
}

/**
 * Verify password with auto-upgrade from legacy base64 to bcrypt.
 */
function pn_verify_password(string $password, string $storedHash, ?int $userId = null): bool
{
    global $pn_config, $pn_handler;

    if (pn_is_legacy_password($storedHash)) {
        // Legacy base64 password - verify and upgrade
        if (base64_encode($password) === $storedHash) {
            // Password matches, upgrade to bcrypt
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
function pn_hash_password(string $password): string
{
    return password_hash($password, PASSWORD_DEFAULT);
}

// Class for news
class pn_news
{
    // List headlines
    public function headlines(int $catid, string $current = '0'): void
    {
        global $pn_config, $pnconfig, $pn_handler;

        $now = time();

        if ($catid > 0) {
            $stmt = mysqli_prepare($pn_handler, 'SELECT id, time, catid, title FROM ' . $pn_config['newstable'] . " WHERE time <= ? AND status = 'Activated' AND catid = ? ORDER BY time DESC LIMIT " . (int) $pnconfig['headlines']);
            mysqli_stmt_bind_param($stmt, 'ii', $now, $catid);
        } else {
            $stmt = mysqli_prepare($pn_handler, 'SELECT id, time, catid, title FROM ' . $pn_config['newstable'] . " WHERE time <= ? AND status = 'Activated' ORDER BY time DESC LIMIT " . (int) $pnconfig['headlines']);
            mysqli_stmt_bind_param($stmt, 'i', $now);
        }

        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $num = mysqli_num_rows($result);

        if ($num == 0) {
            echo '<center>' . L_NEWS_NOHEADLINES . '</center>';
        } else {
            $template = new pn_template();

            while ($row = mysqli_fetch_array($result)) {
                $category = $this->getcatname((int) $row['catid']);

                if (!$template->headline((int) $row['id'], (int) $row['time'], $category, stripslashes((string) $row['title']))) {
                    die('<center>' . L_TEMPL_CANNOTLOADTEMPL . '</center>');
                }
            }
        }
    }

    // List news
    public function news(int $catid, string $current = '0'): void
    {
        global $pn_config, $pnconfig, $pn_handler;

        $now = time();

        if ($catid > 0) {
            $stmt = mysqli_prepare($pn_handler, 'SELECT * FROM ' . $pn_config['newstable'] . " WHERE time <= ? AND catid = ? AND status = 'Activated' ORDER BY time DESC LIMIT " . (int) $pnconfig['news']);
            mysqli_stmt_bind_param($stmt, 'ii', $now, $catid);
        } else {
            $stmt = mysqli_prepare($pn_handler, 'SELECT * FROM ' . $pn_config['newstable'] . " WHERE time <= ? AND status = 'Activated' ORDER BY time DESC LIMIT " . (int) $pnconfig['news']);
            mysqli_stmt_bind_param($stmt, 'i', $now);
        }

        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $num = mysqli_num_rows($result);

        if ($num == 0) {
            echo '<center>' . L_NEWS_NONEWS . '</center>';
        } else {
            $template = new pn_template();

            while ($row = mysqli_fetch_array($result)) {
                $category = $this->getcatname((int) $row['catid']);
                $author = $this->getauthor((int) $row['userid']);
                $comments = $this->getcommentnum((int) $row['id']);

                if (!$template->news((int) $row['id'], $author, (int) $row['time'], $category, stripslashes((string) $row['title']), stripslashes((string) $row['text']), $comments, 'NO', stripslashes((string) $row['moretext']), $row['relatedlinks'])) {
                    die('<center>' . L_TEMPL_CANNOTLOADTEMPL . '</center>');
                }
            }
        }
    }

    // Post news details
    public function details(int $newsid): void
    {
        global $pn_config, $pn_newsexist, $pn_handler;

        $now = time();

        $stmt = mysqli_prepare($pn_handler, 'SELECT * FROM ' . $pn_config['newstable'] . " WHERE id = ? AND time <= ? AND status = 'Activated'");
        mysqli_stmt_bind_param($stmt, 'ii', $newsid, $now);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $num = mysqli_num_rows($result);

        $template = new pn_template();

        if ($num == 1) {
            $pn_newsexist = 'YES';
            $row = mysqli_fetch_array($result);
            $category = $this->getcatname((int) $row['catid']);
            $author = $this->getauthor((int) $row['userid']);
            $comments = $this->getcommentnum((int) $row['id']);

            if (!$template->news((int) $row['id'], $author, (int) $row['time'], $category, stripslashes((string) $row['title']), stripslashes((string) $row['text']), $comments, 'YES', stripslashes((string) $row['moretext']), $row['relatedlinks'])) {
                die('<center>' . L_TEMPL_CANNOTLOADTEMPL . '</center>');
            }
        } else {
            $pn_newsexist = 'NO';
            $template->message(L_NEWS_CHOOSENEWS, $pn_config['newsfile']);
        }
    }

    // List comments for news
    public function comments(int $newsid): void
    {
        global $pn_config, $pn_handler;

        $now = time();

        $stmt = mysqli_prepare($pn_handler, 'SELECT * FROM ' . $pn_config['newstable'] . " WHERE id = ? AND time <= ? AND status = 'Activated'");
        mysqli_stmt_bind_param($stmt, 'ii', $newsid, $now);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $num = mysqli_num_rows($result);

        if ($num == 1) {
            // News exists, fetch comments
            $cstmt = mysqli_prepare($pn_handler, 'SELECT * FROM ' . $pn_config['commenttable'] . ' WHERE newsid = ? AND time <= ? ORDER BY time');
            mysqli_stmt_bind_param($cstmt, 'ii', $newsid, $now);
            mysqli_stmt_execute($cstmt);
            $cresult = mysqli_stmt_get_result($cstmt);
            $cnum = mysqli_num_rows($cresult);

            if ($cnum > 0) {
                while ($crow = mysqli_fetch_array($cresult)) {
                    $template = new pn_template();

                    if ($crow['userid'] != 0) {
                        $user = new pn_user();
                        $userdata = $user->getuser((int) $crow['userid']);
                    } else {
                        $userdata = [];
                        $userdata['id'] = '0';
                        $userdata['nickname'] = L_NEWS_GUEST;
                    }
                    $template->comment((int) $crow['id'], (int) $crow['newsid'], $userdata, (int) $crow['time'], stripslashes((string) $crow['text']));
                }
            } else {
                ?><p align="center"><?php echo L_NEWS_NOCOMMENTS; ?></p><?php
            }
        }
    }

    // Get number of comments for a newspost
    public function getcommentnum(int $newsid): int
    {
        global $pn_config, $pn_handler;

        $stmt = mysqli_prepare($pn_handler, 'SELECT id FROM ' . $pn_config['commenttable'] . ' WHERE newsid = ?');
        mysqli_stmt_bind_param($stmt, 'i', $newsid);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        return mysqli_num_rows($result);
    }

    // Get author name and email
    public function getauthor(int $userid): string
    {
        global $pn_config, $pn_handler;

        $stmt = mysqli_prepare($pn_handler, 'SELECT nickname, email, showemail FROM ' . $pn_config['usertable'] . ' WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'i', $userid);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $num = mysqli_num_rows($result);

        if ($num == 1) {
            $row = mysqli_fetch_array($result);

            if ($row['showemail'] == 'YES') {
                $author = '<a href="mailto:' . pn_escape($row['email']) . '">' . pn_escape($row['nickname']) . '</a>';
            } else {
                $author = pn_escape($row['nickname']);
            }
        } else {
            $author = L_NEWS_UNKNOWN;
        }

        return $author;
    }

    // Get name of category
    public function getcatname(int $catid): mixed
    {
        global $pn_config, $pn_handler;

        if ($catid === 0) {
            return L_NEWS_CATSDEACTIVATED;
        }
        $stmt = mysqli_prepare($pn_handler, 'SELECT id, name, picture FROM ' . $pn_config['cattable'] . ' WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'i', $catid);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $num = mysqli_num_rows($result);

        if ($num == 1) {
            $category = mysqli_fetch_array($result);
            $category['pic'] = '<img src="./pngfx/categories/' . pn_escape($category['picture']) . '" border="0" alt="' . pn_escape($category['name']) . '">';
            $category['name'] = stripslashes((string) $category['name']);
            $category['description'] = stripslashes(((isset($category['description']) && trim($category['description']) !== '') ? trim($category['description']) : ''));

            return $category;
        }

        return L_NEWS_WRONGCAT;
    }

    // Post form for posting comments
    public function commentform(int $newsid): void
    {
        global $pnconfig, $pnuser;

        if ($pnconfig['commentwriting'] == 'Registered') {
            if ($pnuser['loggedin'] == 'YES') {
                $template = new pn_template();
                $template->commentform($pnuser['nickname'], $newsid);
            } else {
                ?><p align="center"><?php echo L_NEWS_CANNOTPOSTCOMMENTS; ?></p><?php
            }
        } else {
            $template = new pn_template();

            if ($pnuser['loggedin'] == 'YES') {
                $template->commentform($pnuser['nickname'], $newsid);
            } else {
                $template->commentform(L_NEWS_GUEST, $newsid);
            }
        }
    }

    // Post comment
    public function postcomment(int $newsid, string $text): void
    {
        global $pn_config, $pnconfig, $pnuser, $pn_handler;

        $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '';
        $template = new pn_template();

        if (trim($text) !== '' && trim($text) !== '0') {
            $now = time();
            $spamprotectiontime = $now - (int) $pnconfig['spamprotection'];

            $stmt = mysqli_prepare($pn_handler, 'SELECT * FROM ' . $pn_config['commenttable'] . ' WHERE ip = ? AND time >= ?');
            mysqli_stmt_bind_param($stmt, 'si', $remoteAddr, $spamprotectiontime);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $num = mysqli_num_rows($result);

            if ($num == 0) {
                $text = addslashes($text);
                $query = '';

                if ($pnconfig['commentwriting'] == 'Registered') {
                    if ($pnuser['loggedin'] == 'YES') {
                        $stmt = mysqli_prepare($pn_handler, 'INSERT INTO ' . $pn_config['commenttable'] . ' (newsid, userid, time, text, ip) VALUES(?, ?, ?, ?, ?)');
                        mysqli_stmt_bind_param($stmt, 'iiiss', $newsid, $pnuser['id'], $now, $text, $remoteAddr);
                        mysqli_stmt_execute($stmt);
                        $query = 'done';
                    } else {
                        $template->message(L_NEWS_CANNOTPOSTCOMMENTS, $pn_config['userfile'] . '?page=login');
                    }
                } else {
                    $userId = ($pnuser['loggedin'] == 'YES') ? (int) $pnuser['id'] : 0;
                    $stmt = mysqli_prepare($pn_handler, 'INSERT INTO ' . $pn_config['commenttable'] . ' (newsid, userid, time, text, ip) VALUES(?, ?, ?, ?, ?)');
                    mysqli_stmt_bind_param($stmt, 'iiiss', $newsid, $userId, $now, $text, $remoteAddr);
                    mysqli_stmt_execute($stmt);
                    $query = 'done';
                }

                if ($query !== '') {
                    $template->message(L_NEWS_COMMENTPOSTED, $pn_config['detailfile'] . "?newsid={$newsid}&showcomments=YES");
                }
            } else {
                if ($pnconfig['spamprotection'] >= 3600) {
                    $sp_time = round($pnconfig['spamprotection'] / 3600, 1);
                    $sp_unit = L_NEWS_HOURS;
                } elseif ($pnconfig['spamprotection'] >= 60) {
                    $sp_time = round($pnconfig['spamprotection'] / 60, 1);
                    $sp_unit = L_NEWS_MINUTES;
                } else {
                    $sp_time = $pnconfig['spamprotection'];
                    $sp_unit = L_NEWS_SECONDS;
                }

                $template->message(L_NEWS_TIMEBETWEEN2COMMENTS . " ({$sp_time} {$sp_unit})", 'javascript:history.back()');
            }
        } else {
            $template->message(L_ALL_FILLALL, 'javascript:history.back()');
        }
    }

    // print archive
    public function archive(): void
    {
        global $pn_config, $pn_handler;

        $template = new pn_template();
        $now = time();

        if (!isset($_POST['pndata']) || !is_array($_POST['pndata'])) {
            $_POST['pndata'] = [];
        }

        if (!isset($_POST['pndata']['showyear']) || !$_POST['pndata']['showyear']) {
            $_POST['pndata']['showyear'] = date('Y', $now);
        }
        $_POST['pndata']['yearselect'] = $this->getyearsforarchive();

        if (!isset($_POST['pndata']['showmonth']) || !$_POST['pndata']['showmonth']) {
            $_POST['pndata']['showmonth'] = date('m', $now);
        }
        $template->archive($_POST['pndata']);

        $searchType = $_POST['pndata']['type'] ?? ($_GET['pndata']['type'] ?? '');

        switch ($searchType) {
            case 'search':
                $searchString = $_POST['pndata']['searchstring'] ?? '';
                $searchPattern = '%' . $searchString . '%';

                $stmt = mysqli_prepare($pn_handler, 'SELECT * FROM ' . $pn_config['newstable'] . " WHERE ((title LIKE ?) OR (text LIKE ?) OR (moretext LIKE ?)) AND (status = 'Activated') AND (time <= ?) ORDER BY time DESC");
                mysqli_stmt_bind_param($stmt, 'sssi', $searchPattern, $searchPattern, $searchPattern, $now);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $num = mysqli_num_rows($result);

                if ($num != 0) {
                    while ($row = mysqli_fetch_array($result)) {
                        $category = $this->getcatname((int) $row['catid']);
                        $author = $this->getauthor((int) $row['userid']);
                        $comments = $this->getcommentnum((int) $row['id']);

                        if (!$template->news((int) $row['id'], $author, (int) $row['time'], $category, stripslashes((string) $row['title']), stripslashes((string) $row['text']), $comments, 'NO', stripslashes((string) $row['moretext']), $row['relatedlinks'])) {
                            die('<center>' . L_TEMPL_CANNOTLOADTEMPL . '</center>');
                        }
                    }
                } else {
                    $template->message(L_NEWS_NONEWSFOUND, 'javascript:history.back()');
                }
                break;

            default:
                if (!isset($_POST['pndata']['showyear']) || !$_POST['pndata']['showyear']) {
                    $_POST['pndata']['showyear'] = date('Y', $now);
                }

                if (!isset($_POST['pndata']['showmonth']) || !$_POST['pndata']['showmonth']) {
                    $_POST['pndata']['showmonth'] = date('m', $now);
                }

                if (isset($_POST['pndata']['showmonth']) && $_POST['pndata']['showmonth'] == '12') {
                    $endmonthyear = (int) $_POST['pndata']['showyear'] + 1;
                    $nextmonth = 1;
                } else {
                    $endmonthyear = (int) $_POST['pndata']['showyear'];
                    $nextmonth = (int) $_POST['pndata']['showmonth'] + 1;
                }

                $startofmonth = mktime(0, 0, 0, (int) $_POST['pndata']['showmonth'], 1, (int) $_POST['pndata']['showyear']);
                $endofmonth = mktime(0, 0, 0, $nextmonth, 1, $endmonthyear);

                $stmt = mysqli_prepare($pn_handler, 'SELECT * FROM ' . $pn_config['newstable'] . " WHERE (time > ?) AND (time < ?) AND (status = 'Activated') AND (time <= ?) ORDER BY time DESC");
                mysqli_stmt_bind_param($stmt, 'iii', $startofmonth, $endofmonth, $now);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $num = mysqli_num_rows($result);

                if ($num != 0) {
                    while ($row = mysqli_fetch_array($result)) {
                        $category = $this->getcatname((int) $row['catid']);
                        $author = $this->getauthor((int) $row['userid']);
                        $comments = $this->getcommentnum((int) $row['id']);

                        if (!$template->news((int) $row['id'], $author, (int) $row['time'], $category, stripslashes((string) $row['title']), stripslashes((string) $row['text']), $comments, 'NO', stripslashes((string) $row['moretext']), $row['relatedlinks'])) {
                            die('<center>' . L_TEMPL_CANNOTLOADTEMPL . '</center>');
                        }
                    }
                }
                break;
        }
    }

    public function getyearsforarchive(): string
    {
        global $pn_config, $pn_handler;

        $yearselect = "<select name=\"pndata[showyear]\" size=\"1\">\n";
        $now = time();
        $thisyear = (int) date('Y', $now);

        $result = mysqli_query($pn_handler, 'SELECT * FROM ' . $pn_config['newstable'] . " WHERE status = 'Activated' ORDER BY TIME LIMIT 1");
        $num = mysqli_num_rows($result);

        if ($num == 1) {
            $row = mysqli_fetch_array($result);
            $datetime = new DateTime();
            $datetime->setTimestamp((int) $row['time']);
            $firstyear = (int) $datetime->format('Y');
            $years = $firstyear;

            while ($years <= $thisyear) {
                $selectedYear = $_POST['pndata']['showyear'] ?? $thisyear;
                $select = $years == $selectedYear ? 'selected' : '';
                $yearselect .= "<option value=\"{$years}\" {$select}>{$years}</option>\n";
                ++$years;
            }
        } else {
            $yearselect .= "<option value=\"{$thisyear}\">{$thisyear}</option>\n";
        }

        return $yearselect . '</select>';
    }

    // User sendnews
    public function sendnews(): void
    {
        global $pn_config, $pnconfig, $pnuser, $pn_handler;

        $template = new pn_template();

        if ($pnconfig['sendnews'] == 'YES') {
            // Check if categories are enabled and generate selectbox
            $noCategoriesAvailable = false;

            if ($pnconfig['categories'] == 'YES') {
                $catresult = mysqli_query($pn_handler, 'SELECT * FROM ' . $pn_config['cattable'] . " WHERE status = 'Activated'");
                $catnum = mysqli_num_rows($catresult);

                if ($catnum > 0) {
                    $catselect = "<select name=\"pndata[catid]\" size=\"1\">\n";
                    $catselect .= '<option value="">' . L_NEWS_CHOOSECAT . "</option>\n";

                    while ($catrow = mysqli_fetch_array($catresult)) {
                        $catselect .= '<option value="' . (int) $catrow['id'] . '">' . pn_escape(stripslashes((string) $catrow['name'])) . "</option>\n";
                    }
                    $catselect .= '</select>';
                } else {
                    $catselect = '<span style="color: #cc0000; font-weight: bold;">' . L_NEWS_NOCATS_ERROR . '</span>';
                    $noCategoriesAvailable = true;
                }
            } else {
                $catselect = L_NEWS_CATSDEACTIVATED;
            }

            $title = $text = $moretext = $catid = $relatedlinks = '';

            if (isset($_POST['pndata'])) {
                $title = addslashes($_POST['pndata']['title'] ?? '');
                $text = addslashes($_POST['pndata']['text'] ?? '');
                $moretext = addslashes($_POST['pndata']['moretext'] ?? '');
                $catid = (int) ($_POST['pndata']['catid'] ?? 0);

                if (isset($_POST['pndata']['rl_title']) && is_array($_POST['pndata']['rl_title'])) {
                    $counter = count($_POST['pndata']['rl_title']);

                    for ($i = 0; $i < $counter; ++$i) {
                        if (trim((string) $_POST['pndata']['rl_title'][$i]) && trim((string) $_POST['pndata']['rl_url'][$i])) {
                            $relatedlinks .= $_POST['pndata']['rl_title'][$i] . '!@!@!' . $_POST['pndata']['rl_url'][$i] . '!@!@!' . $_POST['pndata']['rl_target'][$i] . "\n";
                        }
                    }
                }
            }

            // Check who can send news
            if ($pnconfig['newssending'] == 'Registered' && $pnuser['loggedin'] == 'NO') {
                $template->message(L_NEWS_CANNOTSENDNEWS, $pn_config['userfile'] . '?page=login');
            } elseif ($noCategoriesAvailable) {
                // Show error message when categories are required but none exist
                $template->message(L_NEWS_NOCATS_CANNOT_SEND, $pn_config['newsfile']);
            } else {
                $sendFlag = $_GET['pndata']['send'] ?? '';

                if ($sendFlag == 'YES') {
                    if (!trim($title) || !trim($text) || ($pnconfig['categories'] == 'YES' && !$catid)) {
                        // Specific error message for missing category
                        if ($pnconfig['categories'] == 'YES' && !$catid && trim($title) && trim($text)) {
                            $template->message(L_NEWS_SELECTCAT_ERROR, 'javascript:history.back()');
                        } else {
                            $template->message(L_ALL_FILLALL, 'javascript:history.back()');
                        }
                    } else {
                        $now = time();
                        $userId = (int) ($pnuser['id'] ?? 0);
                        $status = 'Unchecked';

                        $stmt = mysqli_prepare($pn_handler, 'INSERT INTO ' . $pn_config['newstable'] . ' (userid, time, catid, title, text, moretext, status, relatedlinks) VALUES(?, ?, ?, ?, ?, ?, ?, ?)');
                        mysqli_stmt_bind_param($stmt, 'iiisssss', $userId, $now, $catid, $title, $text, $moretext, $status, $relatedlinks);
                        mysqli_stmt_execute($stmt);

                        $template->message(L_NEWS_NEWSSENTIN, $pn_config['newsfile']);
                    }
                } else {
                    if ($pnuser['loggedin'] == 'YES') {
                        $user = '<a href="mailto:' . pn_escape($pnuser['email']) . '">' . pn_escape($pnuser['nickname']) . '</a>';
                    } else {
                        $user = L_NEWS_GUEST;
                    }
                    $template->sendnewsform($user, $catselect);
                }
            }
        } else {
            $template->message(L_NEWS_NONEWSSENDIN, $pn_config['newsfile']);
        }
    }
}

//#################################################################################################

// User class
class pn_user
{
    // Get userinfo to id
    public function getuser(int $userid): ?array
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

    // Generate password
    public function generate_password(): string
    {
        $pwarray = array_merge(
            range('a', 'z'),
            range('A', 'Z'),
            range('0', '9'),
        );

        $pwacount = count($pwarray);
        $password = '';

        for ($i = 0; $i < 8; ++$i) {
            $letter = random_int(0, $pwacount - 1);
            $password .= $pwarray[$letter];
        }

        return $password;
    }

    // Register new user
    public function register(): void
    {
        global $pn_config, $pn_handler;

        $template = new pn_template();
        $sendFlag = $_GET['pndata']['send'] ?? '';

        if ($sendFlag == 'YES') {
            $nickname = $_POST['pndata']['nickname'] ?? '';
            $email = $_POST['pndata']['email'] ?? '';

            if (!$nickname || !$email) {
                $template->message(L_ALL_FILLALL, 'javascript:history.back()');
            } else {
                $stmt = mysqli_prepare($pn_handler, 'SELECT * FROM ' . $pn_config['usertable'] . ' WHERE nickname = ? OR email = ?');
                mysqli_stmt_bind_param($stmt, 'ss', $nickname, $email);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $num = mysqli_num_rows($result);

                if ($num != 0) {
                    $template->message(L_USR_USRALREADYEXISTS, 'javascript:history.back()');
                } else {
                    if (!preg_match("!^[_a-zA-Z0-9.-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$!", (string) $email)) {
                        $template->message(L_USR_WRONGEMAIL, 'javascript:history.back()');
                    } else {
                        $password = $this->generate_password();
                        $hashedPassword = pn_hash_password($password);
                        $now = time();
                        $showemail = $_POST['pndata']['showemail'] ?? 'NO';

                        $stmt = mysqli_prepare($pn_handler, 'INSERT INTO ' . $pn_config['usertable'] . ' (nickname, email, password, registered, showemail) VALUES(?, ?, ?, ?, ?)');
                        mysqli_stmt_bind_param($stmt, 'sssis', $nickname, $email, $hashedPassword, $now, $showemail);
                        mysqli_stmt_execute($stmt);

                        $pemail = new pn_email();
                        $pemail->registeremail($nickname, $email, $password);
                        $template->message(L_USR_REGISTERED, $pn_config['userfile'] . '?page=login');
                    }
                }
            }
        } else {
            $template->registerform();
        }
    }

    // Set cookie for user
    public function setusercookie(): ?array
    {
        global $pn_config, $pn_handler;

        $nickname = $_POST['pndata']['nickname'] ?? '';
        $password = $_POST['pndata']['password'] ?? '';

        $stmt = mysqli_prepare($pn_handler, 'SELECT * FROM ' . $pn_config['usertable'] . ' WHERE nickname = ?');
        mysqli_stmt_bind_param($stmt, 's', $nickname);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) !== 1) {
            return null;
        }

        $pnuser = mysqli_fetch_array($result);

        if (($pnuser['status'] ?? 'Activated') === 'Deactivated') {
            return null;
        }

        if (!pn_verify_password($password, $pnuser['password'], (int) $pnuser['id'])) {
            return null;
        }

        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $now = time();
        $expires = $now + 3600 * 24 * 30;
        $userId = (int) $pnuser['id'];
        $ua = substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
        $ip = substr((string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? ''), 0, 64);
        $ip = substr(explode(',', $ip)[0], 0, 64);

        $stmt = mysqli_prepare($pn_handler, 'INSERT INTO pn_sessions (userid, token_hash, created, expires, user_agent, ip) VALUES (?, ?, ?, ?, ?, ?)');
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

    // Check cookie of user
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

        $stmt = mysqli_prepare($pn_handler, 'SELECT u.* FROM ' . $pn_config['usertable'] . ' u INNER JOIN pn_sessions s ON s.userid = u.id WHERE u.id = ? AND s.token_hash = ? AND s.expires > ? AND u.status = ' . "'Activated'");
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

    // Login user
    public function login(): void
    {
        global $pn_config, $pn_handler;

        $template = new pn_template();
        $loginFlag = $_GET['pndata']['login'] ?? '';

        if ($loginFlag == 'YES') {
            $nickname = $_POST['pndata']['nickname'] ?? '';
            $password = $_POST['pndata']['password'] ?? '';

            if (!$nickname || !$password) {
                $template->message(L_ALL_FILLALL, 'javascript:history.back()');
            } else {
                $stmt = mysqli_prepare($pn_handler, 'SELECT * FROM ' . $pn_config['usertable'] . ' WHERE nickname = ?');
                mysqli_stmt_bind_param($stmt, 's', $nickname);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $num = mysqli_num_rows($result);

                if ($num == 1) {
                    $row = mysqli_fetch_array($result);

                    if (pn_verify_password($password, $row['password'], (int) $row['id'])) {
                        $template->message(L_USR_LOGGEDIN, $pn_config['userfile'] . '?page=profile');
                    } else {
                        $template->message(L_USR_WRONGPASSWORD, 'javascript:history.back()');
                    }
                } else {
                    $template->message(L_USR_NOUSR, 'javascript:history.back()');
                }
            }
        } else {
            $template->loginform();
        }
    }

    // Send data to user
    public function senddata(): void
    {
        global $pn_config, $pn_handler;

        $template = new pn_template();
        $searchstring = $_POST['pndata']['searchstring'] ?? '';

        if ($searchstring) {
            $stmt = mysqli_prepare($pn_handler, 'SELECT nickname, email FROM ' . $pn_config['usertable'] . ' WHERE nickname = ? OR email = ?');
            mysqli_stmt_bind_param($stmt, 'ss', $searchstring, $searchstring);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $num = mysqli_num_rows($result);

            if ($num == 0) {
                $template->message(L_USR_NOUSRREGISTERED, 'javascript:history.back()');
            } elseif ($num > 1) {
                $template->message(L_USR_TOOMANYSEARCHRESULTS, 'javascript:history.back()');
            } elseif ($num == 1) {
                $row = mysqli_fetch_array($result);
                // Generate new password instead of sending stored one
                $newPassword = $this->generate_password();
                $hashedPassword = pn_hash_password($newPassword);

                // Update password in database
                $stmt = mysqli_prepare($pn_handler, 'UPDATE ' . $pn_config['usertable'] . ' SET password = ? WHERE nickname = ?');
                mysqli_stmt_bind_param($stmt, 'ss', $hashedPassword, $row['nickname']);
                mysqli_stmt_execute($stmt);

                $pnemail = new pn_email();

                if ($pnemail->dataemail($row['nickname'], $row['email'], $newPassword)) {
                    $template->message(L_USR_DATASENT, $pn_config['userfile'] . '?page=login');
                } else {
                    $template->message(L_USR_CANTSENDMAIL, 'javascript:history.back()');
                }
            }
        } else {
            $template->senddataform();
        }
    }

    // Print out usermenu
    public function usermenu(): void
    {
        global $pnuser;
        $template = new pn_template();

        if ($pnuser['loggedin'] == 'YES') {
            $template->usermenu2();
        } else {
            $template->usermenu();
        }
    }

    // Edit userprofile
    public function profile(): void
    {
        global $pn_config, $pnuser, $pn_handler;

        $template = new pn_template();

        if ($pnuser['loggedin'] == 'YES') {
            $sendFlag = $_GET['pndata']['send'] ?? '';

            if ($sendFlag == 'YES') {
                $nickname = $_POST['pndata']['nickname'] ?? '';
                $email = $_POST['pndata']['email'] ?? '';
                $password = $_POST['pndata']['password'] ?? '';
                $password2 = $_POST['pndata']['password2'] ?? '';

                if (!$nickname || !$email || !$password || !$password2) {
                    $template->message(L_ALL_FILLALL, 'javascript:history.back()');
                } else {
                    if (!preg_match("!^[_a-zA-Z0-9.-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}!", (string) $email)) {
                        $template->message(L_USR_WRONGEMAIL, 'javascript:history.back()');
                    } else {
                        if ($password == $password2) {
                            $stmt = mysqli_prepare($pn_handler, 'SELECT * FROM ' . $pn_config['usertable'] . ' WHERE (nickname = ? OR email = ?) AND id != ?');
                            $userId = (int) $pnuser['id'];
                            mysqli_stmt_bind_param($stmt, 'ssi', $nickname, $email, $userId);
                            mysqli_stmt_execute($stmt);
                            $result = mysqli_stmt_get_result($stmt);
                            $num = mysqli_num_rows($result);

                            if ($num == 0) {
                                $hashedPassword = pn_hash_password($password);
                                $showemail = $_POST['pndata']['showemail'] ?? 'NO';
                                $realname = $_POST['pndata']['realname'] ?? '';
                                $city = $_POST['pndata']['city'] ?? '';
                                $age = $_POST['pndata']['age'] ?? '';
                                $homepage = $_POST['pndata']['homepage'] ?? '';
                                $icq = $_POST['pndata']['icq'] ?? '';

                                $stmt = mysqli_prepare($pn_handler, 'UPDATE ' . $pn_config['usertable'] . ' SET nickname = ?, email = ?, password = ?, showemail = ?, realname = ?, city = ?, age = ?, homepage = ?, icq = ? WHERE id = ?');
                                mysqli_stmt_bind_param($stmt, 'sssssssssi', $nickname, $email, $hashedPassword, $showemail, $realname, $city, $age, $homepage, $icq, $userId);
                                mysqli_stmt_execute($stmt);

                                $template->message(L_USR_PROFILEEDITED, $pn_config['userfile'] . '?page=profile');
                            } else {
                                $template->message(L_USR_NICKNAMEOREMAILALREADYUSED, 'javascript:history.back()');
                            }
                        } else {
                            $template->message(L_USR_PASSNOTEQUAL, 'javascript:history.back()');
                        }
                    }
                }
            } else {
                $template->profileform($pnuser);
            }
        } else {
            $template->message(L_USR_NOTLOGGEDIN, $pn_config['userfile'] . '?page=login');
        }
    }

    // Logout
    public function logout(): void
    {
        global $pnuser, $pn_config;
        $template = new pn_template();

        if ($pnuser['loggedin'] == 'YES') {
            $template->logout($pnuser);
        } else {
            $template->message(L_USR_CANNOTLOGOUT, $pn_config['userfile'] . '?page=login');
        }
    }

    // Delete usercookie
    public function delusercookie(): void
    {
        global $pn_config, $pn_handler, $pnuser;

        if (!empty($_COOKIE['pncookie'])) {
            $parts = explode(':', (string) $_COOKIE['pncookie'], 2);
            if (count($parts) === 2 && preg_match('/^[a-f0-9]{64}$/', $parts[1])) {
                $tokenHash = hash('sha256', $parts[1]);
                $stmt = mysqli_prepare($pn_handler, 'DELETE FROM pn_sessions WHERE token_hash = ?');
                mysqli_stmt_bind_param($stmt, 's', $tokenHash);
                mysqli_stmt_execute($stmt);
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
}

//#################################################################################################

// E-Mail class
class pn_email
{
    // Sending register-E-Mail
    public function registeremail(string $nickname, string $email, string $password): bool
    {
        global $pnconfig;
        $template = new pn_template();
        $registeremail = $template->registeremail($nickname, $email, $password);

        if ($registeremail) {
            $headers = 'From: ' . L_EMAIL_AUTHOR . ' <' . $pnconfig['email'] . '>';

            return mail($email, L_EMAIL_TITLE, $registeremail, $headers);
        }

        return false;
    }

    // Sending data-E-Mail
    public function dataemail(string $nickname, string $email, string $password): bool
    {
        global $pnconfig;
        $template = new pn_template();
        $dataemail = $template->dataemail($nickname, $email, $password);

        if ($dataemail) {
            $headers = 'From: ' . L_EMAIL_AUTHOR . ' <' . $pnconfig['email'] . '>';

            return mail($email, L_EMAIL_TITLE, $dataemail, $headers);
        }

        return false;
    }
}

//#################################################################################################

// Template class
class pn_template
{
    // Get template for standard message
    public function message(string $text, string $link): bool
    {
        global $pn_config, $pnconfig, $pn_handler;

        $templateId = (int) $pnconfig['template'];
        $stmt = mysqli_prepare($pn_handler, 'SELECT message FROM ' . $pn_config['templatetable'] . ' WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'i', $templateId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $num = mysqli_num_rows($result);

        if ($num == 1) {
            [$message] = mysqli_fetch_array($result);

            $message = preg_replace('!{MESSAGE}!', $text, (string) $message);
            $message = preg_replace('!{LINK}!', $link, $message);

            echo $message;

            return true;
        }

        return false;
    }

    // BB replacements
    public function bbreplace(string $text): string
    {
        $text = preg_replace("!\[(?i)b\]!", '<b>', $text);
        $text = preg_replace("!\[/(?i)b\]!", '</b>', (string) $text);
        $text = preg_replace("!\[(?i)u\]!", '<u>', (string) $text);
        $text = preg_replace("!\[/(?i)u\]!", '</u>', (string) $text);
        $text = preg_replace("!\[(?i)i\]!", '<i>', (string) $text);
        $text = preg_replace("!\[/(?i)i\]!", '</i>', (string) $text);
        $text = preg_replace("!\[(?i)url\](http://|ftp://)([a-zA-Z0-9:/\?\[\]=.@-]+)\[/(?i)url\]+!", '<a href="\\1\\2" target="_blank">\\1\\2</a>', (string) $text);
        $text = preg_replace("!\[(?i)url\]([a-zA-Z0-9:/\?\[\]=.@-]+)\[/(?i)url\]+!", '<a href="http://\\1" target="_blank">\\1</a>', (string) $text);
        $text = preg_replace("!\[(?i)email\]([a-zA-Z0-9-._]+@[a-zA-Z0-9-.]+)\[/(?i)email\]!", '<a href="mailto:\\1">\\1</a>', (string) $text);
        $text = preg_replace("!\[(?i)img\]([a-zA-Z0-9:/\?\[\]=.@-]+)\[(?i)/img\]!", '<img src="\\1" border="0">', (string) $text);

        return preg_replace("!\n!", '<br>', (string) $text);
    }

    // Smilie replacements
    public function smiliereplace(string $text): string
    {
        $text = preg_replace("!:\)\)!", '<img src="./pngfx/smilies/laugh.gif" width="15" height="15" border="0">', $text);
        $text = preg_replace("!:\)!", '<img src="./pngfx/smilies/smile.gif" width="15" height="15" border="0">', (string) $text);
        $text = preg_replace("!;\)!", '<img src="./pngfx/smilies/wink.gif" width="15" height="15" border="0">', (string) $text);
        $text = preg_replace('!:(?i)p!', '<img src="./pngfx/smilies/tongue.gif" width="15" height="15" border="0">', (string) $text);
        $text = preg_replace('!:(?i)D!', '<img src="./pngfx/smilies/bigsmile.gif" width="15" height="15" border="0">', (string) $text);
        $text = preg_replace("!:\(!", '<img src="./pngfx/smilies/sad.gif" width="15" height="15" border="0">', (string) $text);

        return preg_replace("!:\?:!", '<img src="./pngfx/smilies/confused.gif" width="15" height="22" border="0">', (string) $text);
    }

    // Get template for headline
    public function headline(int $id, int $time, mixed $category, string $title): bool
    {
        global $pn_config, $pnconfig, $pn_handler;

        $templateId = (int) $pnconfig['template'];
        $stmt = mysqli_prepare($pn_handler, 'SELECT headline FROM ' . $pn_config['templatetable'] . ' WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'i', $templateId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $num = mysqli_num_rows($result);

        if ($num == 1) {
            [$headline] = mysqli_fetch_array($result);

            if ($pnconfig['html'] == 'Comments' || $pnconfig['html'] == 'NO') {
                $title = htmlentities($title);
            }

            if ($pnconfig['bbcode'] == 'Comments/News' || $pnconfig['bbcode'] == 'News') {
                $title = $this->bbreplace($title);
            }

            if ($pnconfig['smilies'] == 'Comments/News' || $pnconfig['smilies'] == 'News') {
                $title = $this->smiliereplace($title);
            }

            $datetime = new DateTime();
            $datetime->setTimestamp($time);
            $date = $datetime->format($pnconfig['dateformat']);
            $timeStr = $datetime->format($pnconfig['timeformat']);

            $headline = preg_replace('!{ID}!', (string) $id, (string) $headline);
            $headline = preg_replace('!{DATE}!', $date, $headline);
            $headline = preg_replace('!{TIME}!', $timeStr, $headline);
            $headline = preg_replace('!{CATEGORY}!', is_array($category) ? $category['name'] : (string) $category, $headline);
            $headline = preg_replace('!{TITLE}!', $title, $headline);
            $headline = preg_replace('!{CATPIC}!', is_array($category) ? $category['pic'] : '', $headline);
            $headline = preg_replace('!{CATID}!', is_array($category) ? (string) $category['id'] : '', $headline);

            echo $headline;

            return true;
        }

        return false;
    }

    // Get template for news entry
    public function news(int $id, string $author, int $time, mixed $category, string $title, string $text, int $comments, string $details, string $moretext = '', string $relatedlinks = ''): bool
    {
        global $pn_config, $pnconfig, $pn_handler;

        $templateId = (int) $pnconfig['template'];
        $stmt = mysqli_prepare($pn_handler, 'SELECT news FROM ' . $pn_config['templatetable'] . ' WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'i', $templateId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $num = mysqli_num_rows($result);

        if ($num == 1) {
            [$news] = mysqli_fetch_array($result);

            if ($pnconfig['html'] == 'Comments' || $pnconfig['html'] == 'NO') {
                $title = htmlentities($title);
                $text = htmlentities($text);
                $moretext = htmlentities($moretext);
            }

            if ($pnconfig['bbcode'] == 'Comments/News' || $pnconfig['bbcode'] == 'News') {
                $title = $this->bbreplace($title);
                $text = $this->bbreplace($text);
                $moretext = $this->bbreplace($moretext);
            }

            if ($pnconfig['smilies'] == 'Comments/News' || $pnconfig['smilies'] == 'News') {
                $title = $this->smiliereplace($title);
                $text = $this->smiliereplace($text);
                $moretext = $this->smiliereplace($moretext);
            }

            $datetime = new DateTime();
            $datetime->setTimestamp($time);
            $date = $datetime->format($pnconfig['dateformat']);
            $timeStr = $datetime->format($pnconfig['timeformat']);

            $news = preg_replace('!{ID}!', (string) $id, $news);
            $news = preg_replace('!{AUTHOR}!', $author, $news);
            $news = preg_replace('!{DATE}!', $date, $news);
            $news = preg_replace('!{TIME}!', $timeStr, $news);
            $news = preg_replace('!{CATEGORY}!', is_array($category) ? $category['name'] : (string) $category, $news);
            $news = preg_replace('!{CATID}!', is_array($category) ? (string) $category['id'] : '', $news);
            $news = preg_replace('!{TITLE}!', $title, $news);

            if ($pnconfig['moretext'] == 'YES' && $moretext) {
                if ($details === 'YES') {
                    $text = '<b>' . $text . '</b><br><br>' . $moretext;
                    $news = preg_replace('!{MORE}!', '', $news);
                } else {
                    $news = preg_replace('!{MORE}!', '[ <a href="' . $pn_config['detailfile'] . '?newsid=' . $id . '">' . L_NEWS_MORE . '</a> ]', $news);
                }
            } else {
                $news = preg_replace('!{MORE}!', '', $news);
            }

            $news = preg_replace('!{TEXT}!', $text, $news);
            $news = preg_replace('!{COMMENTS}!', (string) $comments, $news);

            if ($pnconfig['categorypics'] == 'YES') {
                $news = preg_replace('!{CATPIC}!', is_array($category) ? $category['pic'] : '', $news);
            }

            if ($pnconfig['relatedlinks'] == 'YES') {
                $links = explode("\n", $relatedlinks);
                $rlinks = '';
                $linksCount = count($links) - 1;

                for ($i = 0; $i < $linksCount; ++$i) {
                    $link = explode('!@!@!', $links[$i]);

                    if (isset($link[0], $link[1], $link[2])) {
                        $rlinks .= $this->relatedlinks($link[0], $link[1], $link[2]);
                    }
                }
                $news = preg_replace('!{RELATEDLINKS}!', $rlinks, $news);
            }

            echo $news;

            return true;
        }

        return false;
    }

    // Get template for comment
    public function comment(int $id, int $newsid, array $author, int $time, string $text): bool
    {
        global $pn_config, $pnconfig, $pn_handler;

        $templateId = (int) $pnconfig['template'];
        $stmt = mysqli_prepare($pn_handler, 'SELECT comment FROM ' . $pn_config['templatetable'] . ' WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'i', $templateId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $num = mysqli_num_rows($result);

        if ($num == 1) {
            [$comment] = mysqli_fetch_array($result);

            if ($pnconfig['html'] == 'News' || $pnconfig['html'] == 'NO') {
                $text = htmlentities($text);
            }

            if ($pnconfig['bbcode'] == 'Comments/News' || $pnconfig['bbcode'] == 'Comments') {
                $text = $this->bbreplace($text);
            }

            if ($pnconfig['smilies'] == 'Comments/News' || $pnconfig['smilies'] == 'Comments') {
                $text = $this->smiliereplace($text);
            }

            if ($author['id'] == 0) {
                $user = L_NEWS_GUEST;
            } else {
                if (($author['showemail'] ?? '') == 'YES') {
                    $user = '<a href="mailto:' . pn_escape($author['email'] ?? '') . '">' . pn_escape($author['nickname'] ?? '') . '</a>';
                } else {
                    $user = pn_escape($author['nickname'] ?? '');
                }
            }

            $datetime = new DateTime();
            $datetime->setTimestamp($time);
            $date = $datetime->format($pnconfig['dateformat']);
            $timeStr = $datetime->format($pnconfig['timeformat']);

            $comment = preg_replace('!{ID}!', (string) $id, (string) $comment);
            $comment = preg_replace('!{AUTHOR}!', $user, $comment);
            $comment = preg_replace('!{DATE}!', $date, $comment);
            $comment = preg_replace('!{TIME}!', $timeStr, $comment);
            $comment = preg_replace('!{TEXT}!', $text, $comment);

            echo $comment;

            return true;
        }

        return false;
    }

    // Get template for related links
    public function relatedlinks(string $title, string $url, string $target): string|false
    {
        global $pn_config, $pnconfig, $pn_handler;

        $templateId = (int) $pnconfig['template'];
        $stmt = mysqli_prepare($pn_handler, 'SELECT relatedlinks FROM ' . $pn_config['templatetable'] . ' WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'i', $templateId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $num = mysqli_num_rows($result);

        if ($num == 1) {
            [$relatedlink] = mysqli_fetch_array($result);

            $relatedlink = preg_replace('!{TITLE}!', pn_escape($title), (string) $relatedlink);
            $relatedlink = preg_replace('!{URL}!', pn_escape($url), $relatedlink);

            return preg_replace('!{TARGET}!', pn_escape($target), $relatedlink);
        }

        return false;
    }

    // Get template for comment form
    public function commentform(string $name, int $newsid): bool
    {
        global $pn_config, $pnconfig, $pn_handler;

        $templateId = (int) $pnconfig['template'];
        $stmt = mysqli_prepare($pn_handler, 'SELECT commentform FROM ' . $pn_config['templatetable'] . ' WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'i', $templateId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $num = mysqli_num_rows($result);

        if ($num == 1) {
            [$commentform] = mysqli_fetch_array($result);

            $commentform = preg_replace('!{NEWSID}!', (string) $newsid, (string) $commentform);
            $commentform = preg_replace('!{NAME}!', pn_escape($name), $commentform);

            echo $commentform;

            return true;
        }

        return false;
    }

    // Get template for registration form
    public function registerform(): bool
    {
        global $pn_config, $pnconfig, $pn_handler;

        $templateId = (int) $pnconfig['template'];
        $stmt = mysqli_prepare($pn_handler, 'SELECT registerform FROM ' . $pn_config['templatetable'] . ' WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'i', $templateId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $num = mysqli_num_rows($result);

        if ($num == 1) {
            [$registerform] = mysqli_fetch_array($result);
            echo $registerform;

            return true;
        }

        return false;
    }

    // Get template for register-E-Mail
    public function registeremail(string $nickname, string $email, string $password): string|false
    {
        global $pnconfig, $pn_config, $pn_handler;

        $templateId = (int) $pnconfig['template'];
        $stmt = mysqli_prepare($pn_handler, 'SELECT registeremail FROM ' . $pn_config['templatetable'] . ' WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'i', $templateId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $num = mysqli_num_rows($result);

        if ($num == 1) {
            [$registeremail] = mysqli_fetch_array($result);

            $registeremail = preg_replace('!{NICKNAME}!', $nickname, (string) $registeremail);
            $registeremail = preg_replace('!{EMAIL}!', $email, $registeremail);
            $registeremail = preg_replace('!{PASSWORD}!', $password, $registeremail);

            return preg_replace('!{URL}!', (string) $pnconfig['url'], $registeremail);
        }

        return false;
    }

    // Get template for login form
    public function loginform(): bool
    {
        global $pn_config, $pnconfig, $pn_handler;

        $templateId = (int) $pnconfig['template'];
        $stmt = mysqli_prepare($pn_handler, 'SELECT loginform FROM ' . $pn_config['templatetable'] . ' WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'i', $templateId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $num = mysqli_num_rows($result);

        if ($num == 1) {
            [$loginform] = mysqli_fetch_array($result);
            echo $loginform;

            return true;
        }

        return false;
    }

    // Get template for senddata form
    public function senddataform(): bool
    {
        global $pn_config, $pnconfig, $pn_handler;

        $templateId = (int) $pnconfig['template'];
        $stmt = mysqli_prepare($pn_handler, 'SELECT senddataform FROM ' . $pn_config['templatetable'] . ' WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'i', $templateId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $num = mysqli_num_rows($result);

        if ($num == 1) {
            [$senddataform] = mysqli_fetch_array($result);
            echo $senddataform;

            return true;
        }

        return false;
    }

    // Get template for data-E-Mail
    public function dataemail(string $nickname, string $email, string $password): string|false
    {
        global $pnconfig, $pn_config, $pn_handler;

        $templateId = (int) $pnconfig['template'];
        $stmt = mysqli_prepare($pn_handler, 'SELECT dataemail FROM ' . $pn_config['templatetable'] . ' WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'i', $templateId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $num = mysqli_num_rows($result);

        if ($num == 1) {
            [$dataemail] = mysqli_fetch_array($result);

            $dataemail = preg_replace('!{NICKNAME}!', $nickname, (string) $dataemail);
            $dataemail = preg_replace('!{EMAIL}!', $email, $dataemail);
            $dataemail = preg_replace('!{PASSWORD}!', $password, $dataemail);

            return preg_replace('!{URL}!', (string) $pnconfig['url'], $dataemail);
        }

        return false;
    }

    // Get template for usermenu
    public function usermenu(): bool
    {
        global $pnconfig, $pn_config, $pn_handler;

        $templateId = (int) $pnconfig['template'];
        $stmt = mysqli_prepare($pn_handler, 'SELECT usermenu FROM ' . $pn_config['templatetable'] . ' WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'i', $templateId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $num = mysqli_num_rows($result);

        if ($num == 1) {
            [$usermenu] = mysqli_fetch_array($result);
            echo $usermenu;

            return true;
        }

        return false;
    }

    // Get template for usermenu2
    public function usermenu2(): bool
    {
        global $pnconfig, $pn_config, $pn_handler;

        $templateId = (int) $pnconfig['template'];
        $stmt = mysqli_prepare($pn_handler, 'SELECT usermenu2 FROM ' . $pn_config['templatetable'] . ' WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'i', $templateId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $num = mysqli_num_rows($result);

        if ($num == 1) {
            [$usermenu2] = mysqli_fetch_array($result);
            echo $usermenu2;

            return true;
        }

        return false;
    }

    // Get template for userprofile
    public function profileform(array $pnuser): bool
    {
        global $pn_config, $pnconfig, $pn_handler;

        $templateId = (int) $pnconfig['template'];
        $stmt = mysqli_prepare($pn_handler, 'SELECT profileform FROM ' . $pn_config['templatetable'] . ' WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'i', $templateId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $num = mysqli_num_rows($result);

        if ($num == 1) {
            [$profileform] = mysqli_fetch_array($result);

            $profileform = preg_replace('!{NICKNAME}!', pn_escape($pnuser['nickname']), (string) $profileform);
            $profileform = preg_replace('!{EMAIL}!', pn_escape($pnuser['email']), $profileform);

            if (($pnuser['showemail'] ?? '') == 'YES') {
                $profileform = preg_replace('!{SHOWEMAIL}!', 'checked', $profileform);
            }
            // Don't display password - user must enter new one
            $profileform = preg_replace('!{PASSWORD}!', '', $profileform);
            $profileform = preg_replace('!{REALNAME}!', pn_escape($pnuser['realname'] ?? ''), $profileform);
            $profileform = preg_replace('!{CITY}!', pn_escape($pnuser['city'] ?? ''), $profileform);
            $profileform = preg_replace('!{AGE}!', pn_escape($pnuser['age'] ?? ''), $profileform);
            $profileform = preg_replace('!{HOMEPAGE}!', pn_escape($pnuser['homepage'] ?? ''), $profileform);
            $profileform = preg_replace('!{ICQ}!', pn_escape($pnuser['icq'] ?? ''), $profileform);

            echo $profileform;

            return true;
        }

        return false;
    }

    // Get template for logout
    public function logout(array $pnuser): bool
    {
        global $pn_config, $pnconfig, $pn_handler;

        $templateId = (int) $pnconfig['template'];
        $stmt = mysqli_prepare($pn_handler, 'SELECT logout FROM ' . $pn_config['templatetable'] . ' WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'i', $templateId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $num = mysqli_num_rows($result);

        if ($num == 1) {
            [$logout] = mysqli_fetch_array($result);

            $logout = preg_replace('!{NICKNAME}!', pn_escape($pnuser['nickname']), (string) $logout);

            echo $logout;

            return true;
        }

        return false;
    }

    // Get template for archive
    public function archive(array $pndata): bool
    {
        global $pn_config, $pnconfig, $pn_handler;

        $templateId = (int) $pnconfig['template'];
        $stmt = mysqli_prepare($pn_handler, 'SELECT archive FROM ' . $pn_config['templatetable'] . ' WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'i', $templateId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $num = mysqli_num_rows($result);

        if ($num == 1) {
            [$archive] = mysqli_fetch_array($result);

            $monthselect = "<select name=\"pndata[showmonth]\" size=\"1\">\n";

            for ($month = 1; $month < 13; ++$month) {
                $datetime = new DateTime();
                $thisyear = $_POST['pndata']['thisyear'] ?? date('Y');
                $datetime->setDate((int) $thisyear, $month, 1);
                $datetime->setTime(0, 0, 0);
                $monthname = $datetime->format('F');
                $selectedMonth = $pndata['showmonth'] ?? '';
                $monthselect .= $selectedMonth == $month ? '<option value="' . $month . '" selected>' . pn_escape($monthname) . '</option>' : '<option value="' . $month . '">' . pn_escape($monthname) . '</option>';
            }
            $monthselect .= "</select>\n";

            $archive = preg_replace('!{SELECTYEAR}!', $pndata['yearselect'] ?? '', (string) $archive);
            $archive = preg_replace('!{SELECTMONTH}!', $monthselect, $archive);
            $archive = preg_replace('!{SEARCHSTRING}!', pn_escape($pndata['searchstring'] ?? ''), $archive);

            echo $archive;

            return true;
        }

        return false;
    }

    // Get template for sendnewsform
    public function sendnewsform(string $user, string $catselect): bool
    {
        global $pn_config, $pnconfig, $pn_handler;

        $templateId = (int) $pnconfig['template'];
        $stmt = mysqli_prepare($pn_handler, 'SELECT sendnewsform FROM ' . $pn_config['templatetable'] . ' WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'i', $templateId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $num = mysqli_num_rows($result);

        if ($num == 1) {
            [$sendnewsform] = mysqli_fetch_array($result);

            $sendnewsform = preg_replace('!{USER}!', $user, (string) $sendnewsform);
            $sendnewsform = preg_replace('!{CATEGORYSELECT}!', $catselect, $sendnewsform);

            $relatedlinks = '';

            if ($pnconfig['relatedlinks'] == 'YES') {
                $targets = '';
                $counter = count($pn_config['rltargets']);

                for ($i = 0; $i < $counter; ++$i) {
                    $targets .= '<option value="' . pn_escape($pn_config['rltargets'][$i]) . '">' . pn_escape($pn_config['rltargets'][$i]) . '</option>';
                }
                $relatedlinks = '<table border="0" cellpadding="3" cellspacing="0" width="100%"><tr><td><b>' . L_NEWS_RL_TITLE . '</b></td><td><b>' . L_NEWS_RL_URL . '</b></td><td><b>' . L_NEWS_RL_TARGET . '</b></td></tr>';

                for ($i = 0; $i < (int) $pnconfig['relatedlinks_num']; ++$i) {
                    $relatedlinks .= '<tr><td><input name="pndata[rl_title][]" size="25" maxlength="50"></td><td><input name="pndata[rl_url][]" size="25" maxlength="250"></td><td><select name="pndata[rl_target][]" size="1">';
                    $relatedlinks .= $targets;
                    $relatedlinks .= '</select></td></tr>';
                }
                $relatedlinks .= '</table>';
            }
            $sendnewsform = preg_replace('!{RELATEDLINKS}!', $relatedlinks, $sendnewsform);

            echo $sendnewsform;

            return true;
        }

        return false;
    }
}

//#################################################################################################

function pn_cpi(): void
{
    global $pn_config;
    ?><p align="center" class="copyright"><font size="1">PowerNews <?php echo pn_escape($pn_config['version']); ?> &copy; Copyright 2003 by <a href="https://www.powerscripts.org" target="_blank">PowerScripts</a></font></p><?php
}
