<?php
declare(strict_types=1);

/* PowerNews - PHP and MySQL based news script                          */
/* Copyright (c) 2001-2026 PowerScripts                                 */

/* MIT License - See LICENSE file for full license text                 */
/* https://github.com/schubertnico/PowerNews.git                        */

require_once __DIR__ . '/core.inc.php';

/**
 * Helper function to escape output for HTML.
 */
function pn_escape(string|int|float|null $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/**
 * Konvertiert ein strftime-aehnliches Datums-/Zeitformat (z.B. "%d.%m.%Y", "%H:%M")
 * in ein PHP-date()-kompatibles Format. Wird benoetigt, da die Konfiguration historisch
 * im strftime-Format abgelegt wurde, aber DateTime::format() das PHP-date-Format erwartet.
 * Bereits PHP-date-Formate (ohne %) werden unveraendert zurueckgegeben.
 */
function pn_convert_date_format(string $format): string
{
    if ($format === '' || strpos($format, '%') === false) {
        return $format;
    }

    $map = [
        '%d' => 'd', '%e' => 'j', '%j' => 'z',
        '%a' => 'D', '%A' => 'l', '%w' => 'w',
        '%m' => 'm', '%n' => 'n',
        '%b' => 'M', '%B' => 'F', '%h' => 'M',
        '%y' => 'y', '%Y' => 'Y',
        '%H' => 'H', '%k' => 'G', '%I' => 'h', '%l' => 'g',
        '%M' => 'i', '%S' => 's',
        '%p' => 'A', '%P' => 'a',
        '%T' => 'H:i:s', '%R' => 'H:i', '%r' => 'h:i:s A',
        '%D' => 'm/d/y', '%F' => 'Y-m-d',
        '%s' => 'U', '%z' => 'O', '%Z' => 'T',
        '%U' => 'W', '%V' => 'W', '%W' => 'W',
        '%C' => '', '%g' => 'y', '%G' => 'Y',
        '%%' => '%',
    ];

    $result = '';
    $len = strlen($format);

    for ($i = 0; $i < $len; ++$i) {
        if ($format[$i] === '%' && $i + 1 < $len) {
            $token = substr($format, $i, 2);

            if (isset($map[$token])) {
                $result .= $map[$token];
                ++$i;
                continue;
            }
        }

        // Escape literal characters that have meaning in date()
        if (strpos('dDjlNSwzWFmMntLoYyaABgGhHisuveIOPpTZcrU', $format[$i]) !== false) {
            $result .= '\\' . $format[$i];
        } else {
            $result .= $format[$i];
        }
    }

    return $result;
}

/**
 * Formatiert einen Zeitpunkt mit einem Datums- oder Zeitformat der Konfiguration
 * (strftime- oder date()-Schreibweise). Wochentage und Monatsnamen (l, D, F, M bzw. %A, %a,
 * %B, %b) kommen aus der Sprachdatei, bei german-du und german-sie also auf Deutsch.
 * Sprachdateien ohne diese Namen behalten die englischen Namen von PHP.
 */
function pn_format_date(int $timestamp, string $format): string
{
    $datetime = new DateTime();
    $datetime->setTimestamp($timestamp);
    $weekday = (int) $datetime->format('w');
    $month = (int) $datetime->format('n') - 1;
    $months = array_map(static fn (string $name): string => defined($name) ? (string) constant($name) : '', [
        'L_TEMPL_JANUARY', 'L_TEMPL_FEBRUARY', 'L_TEMPL_MARCH', 'L_TEMPL_APRIL', 'L_TEMPL_MAY', 'L_TEMPL_JUNE',
        'L_TEMPL_JULY', 'L_TEMPL_AUGUST', 'L_TEMPL_SEPTEMBER', 'L_TEMPL_OCTOBER', 'L_TEMPL_NOVEMBER', 'L_TEMPL_DECEMBER',
    ]);
    $pick = static function (string $constant, int $index): string {
        $list = defined($constant) ? constant($constant) : null;

        return is_array($list) && is_string($list[$index] ?? null) ? $list[$index] : '';
    };
    $names = [
        'l' => $pick('L_DATE_WEEKDAYS', $weekday),
        'D' => $pick('L_DATE_WEEKDAYS_SHORT', $weekday),
        'F' => $months[$month],
        'M' => $pick('L_DATE_MONTHS_SHORT', $month),
    ];
    $phpFormat = pn_convert_date_format($format);
    $localized = '';
    $length = strlen($phpFormat);

    for ($i = 0; $i < $length; ++$i) {
        $char = $phpFormat[$i];

        if ($char === '\\' && $i + 1 < $length) {
            $localized .= $char . $phpFormat[++$i];
        } elseif (is_string($names[$char] ?? null) && $names[$char] !== '') {
            // Name als Literal einsetzen: Buchstaben maskieren, damit format() sie nicht auswertet.
            $localized .= (string) preg_replace('/([A-Za-z])/', '\\\\$1', $names[$char]);
        } else {
            $localized .= $char;
        }
    }

    return $datetime->format($localized);
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

    // Konten ohne festgelegtes Passwort (Einladung) lassen sich nie anmelden.
    if (!pn_password_is_set($storedHash)) {
        return false;
    }

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
            echo '<p class="text-center text-muted mb-0">' . L_NEWS_NOHEADLINES . '</p>';
        } else {
            $template = new pn_template();

            while ($row = mysqli_fetch_array($result)) {
                $category = $this->getcatname((int) $row['catid']);

                if (!$template->headline((int) $row['id'], (int) $row['time'], $category, (string) $row['title'])) {
                    die('<div class="alert alert-danger" role="alert">' . L_TEMPL_CANNOTLOADTEMPL . '</div>');
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
            echo '<div class="alert alert-info" role="alert">' . L_NEWS_NONEWS . '</div>';
        } else {
            $template = new pn_template();

            while ($row = mysqli_fetch_array($result)) {
                $category = $this->getcatname((int) $row['catid']);
                $author = $this->getauthor((int) $row['userid']);
                $comments = $this->getcommentnum((int) $row['id']);

                if (!$template->news((int) $row['id'], $author, (int) $row['time'], $category, (string) $row['title'], (string) $row['text'], $comments, 'NO', (string) $row['moretext'], $row['relatedlinks'])) {
                    die('<div class="alert alert-danger" role="alert">' . L_TEMPL_CANNOTLOADTEMPL . '</div>');
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

            if (!$template->news((int) $row['id'], $author, (int) $row['time'], $category, (string) $row['title'], (string) $row['text'], $comments, 'YES', (string) $row['moretext'], $row['relatedlinks'])) {
                die('<div class="alert alert-danger" role="alert">' . L_TEMPL_CANNOTLOADTEMPL . '</div>');
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
            $cstmt = mysqli_prepare($pn_handler, 'SELECT * FROM ' . $pn_config['commenttable'] . ' WHERE newsid = ? AND time <= ? ORDER BY time ASC, id ASC');
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
                    $template->comment((int) $crow['id'], (int) $crow['newsid'], $userdata, (int) $crow['time'], (string) $crow['text']);
                }
            } else {
                ?><div class="alert alert-info" role="alert"><?php echo L_NEWS_NOCOMMENTS; ?></div><?php
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
            $category['name'] = (string) $category['name'];
            $category['description'] = ((isset($category['description']) && trim($category['description']) !== '') ? trim($category['description']) : '');

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
                ?><div class="alert alert-warning" role="alert"><?php echo L_NEWS_CANNOTPOSTCOMMENTS; ?></div><?php
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

        $template = new pn_template();
        $backToNews = $pn_config['detailfile'] . '?newsid=' . $newsid . '&showcomments=YES';

        // CSRF-Token pruefen (IMP-003)
        if (!pn_csrf_verify($_POST['csrf_token'] ?? null)) {
            $template->message(L_ALL_CSRFINVALID, $backToNews, 'danger');

            return;
        }

        $text = trim($text);

        if ($text === '' || $text === '0') {
            $template->message(L_ALL_FILLALL, $backToNews, 'danger');

            return;
        }

        // Length limit (BUG-038)
        $maxLen = 5000;

        if (mb_strlen($text) > $maxLen) {
            $template->message(sprintf(L_NEWS_COMMENTTOOLONG, $maxLen), $backToNews, 'danger');

            return;
        }

        // Validate newsid exists and is activated (BUG-029)
        $stmt = mysqli_prepare($pn_handler, 'SELECT id FROM ' . $pn_config['newstable'] . " WHERE id = ? AND status = 'Activated'");
        mysqli_stmt_bind_param($stmt, 'i', $newsid);
        mysqli_stmt_execute($stmt);

        if (mysqli_num_rows(mysqli_stmt_get_result($stmt)) !== 1) {
            $template->message(L_NEWS_NEWSNOTFOUND, $pn_config['newsfile']);

            return;
        }

        // IP nur aus REMOTE_ADDR bzw. hinter konfigurierten Proxys (B23)
        $remoteAddr = pn_client_ip();

        $now = time();
        $spamprotectiontime = $now - (int) $pnconfig['spamprotection'];

        $stmt = mysqli_prepare($pn_handler, 'SELECT id FROM ' . $pn_config['commenttable'] . ' WHERE ip = ? AND time >= ?');
        mysqli_stmt_bind_param($stmt, 'si', $remoteAddr, $spamprotectiontime);
        mysqli_stmt_execute($stmt);

        if (mysqli_num_rows(mysqli_stmt_get_result($stmt)) > 0) {
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
            $template->message(L_NEWS_TIMEBETWEEN2COMMENTS . " ({$sp_time} {$sp_unit})", $backToNews, 'warning');

            return;
        }

        if (($pnconfig['commentwriting'] ?? '') === 'Registered' && (($pnuser['loggedin'] ?? 'NO') !== 'YES')) {
            $template->message(L_NEWS_CANNOTPOSTCOMMENTS, $pn_config['userfile'] . '?page=login', 'warning');

            return;
        }

        $userId = (($pnuser['loggedin'] ?? 'NO') === 'YES') ? (int) $pnuser['id'] : 0;
        $stmt = mysqli_prepare($pn_handler, 'INSERT INTO ' . $pn_config['commenttable'] . ' (newsid, userid, time, text, ip) VALUES(?, ?, ?, ?, ?)');
        mysqli_stmt_bind_param($stmt, 'iiiss', $newsid, $userId, $now, $text, $remoteAddr);
        mysqli_stmt_execute($stmt);

        $template->message(L_NEWS_COMMENTPOSTED, $pn_config['detailfile'] . "?newsid={$newsid}&showcomments=YES", 'success');
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

        // Gewählten Monat merken: Die Suche schickt keinen Monat mit, die Auswahl soll danach
        // nicht auf den laufenden Monat springen.
        [$_POST['pndata']['showyear'], $_POST['pndata']['showmonth']] = self::archivemonth($_POST['pndata'], $now);
        $_POST['pndata']['yearselect'] = $this->getyearsforarchive();
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

                        if (!$template->news((int) $row['id'], $author, (int) $row['time'], $category, (string) $row['title'], (string) $row['text'], $comments, 'NO', (string) $row['moretext'], $row['relatedlinks'])) {
                            die('<div class="alert alert-danger" role="alert">' . L_TEMPL_CANNOTLOADTEMPL . '</div>');
                        }
                    }
                } else {
                    $template->message(L_NEWS_NONEWSFOUND, $pn_config['archivefile']);
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

                        if (!$template->news((int) $row['id'], $author, (int) $row['time'], $category, (string) $row['title'], (string) $row['text'], $comments, 'NO', (string) $row['moretext'], $row['relatedlinks'])) {
                            die('<div class="alert alert-danger" role="alert">' . L_TEMPL_CANNOTLOADTEMPL . '</div>');
                        }
                    }
                } else {
                    // Leerer Monat: Hinweis statt nur des Formulars (B13).
                    $template->message(L_NEWS_NONEWSINMONTH, $pn_config['archivefile']);
                }
                break;
        }
    }

    /**
     * Jahr und Monat für das Archiv: aus dem Formular (und dann in der Sitzung gemerkt), sonst
     * die zuletzt gewählten, sonst der laufende Monat.
     *
     * @param array<string, mixed> $pndata
     *
     * @return array{0: string, 1: string}
     */
    public static function archivemonth(array $pndata, int $now): array
    {
        $year = filter_var($pndata['showyear'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1970, 'max_range' => 2100]]);
        $month = filter_var($pndata['showmonth'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 12]]);

        if ($year !== false && $month !== false) {
            $_SESSION['pn_archive_month'] = [(string) $year, sprintf('%02d', $month)];
        }

        $remembered = $_SESSION['pn_archive_month'] ?? null;

        if (is_array($remembered) && count($remembered) === 2) {
            return [(string) $remembered[0], (string) $remembered[1]];
        }

        return [date('Y', $now), date('m', $now)];
    }

    public function getyearsforarchive(): string
    {
        global $pn_config, $pn_handler;

        $yearselect = "<select class=\"form-select\" name=\"pndata[showyear]\" id=\"pn_showyear\">\n";
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
                    $catselect = "<select class=\"form-select\" name=\"pndata[catid]\" id=\"pn_catid\">\n";
                    $catselect .= '<option value="">' . L_NEWS_CHOOSECAT . "</option>\n";

                    while ($catrow = mysqli_fetch_array($catresult)) {
                        $catselect .= '<option value="' . (int) $catrow['id'] . '">' . pn_escape((string) $catrow['name']) . "</option>\n";
                    }
                    $catselect .= '</select>';
                } else {
                    $catselect = '<span style="color: #cc0000; font-weight: bold;">' . L_NEWS_NOCATS_ERROR . '</span>';
                    $noCategoriesAvailable = true;
                }
            } else {
                $catselect = L_NEWS_CATSDEACTIVATED;
            }

            $title = $text = $moretext = $relatedlinks = '';
            $catid = 0;

            if (isset($_POST['pndata'])) {
                $title = pn_validate_string($_POST['pndata']['title'] ?? '', 150);
                $text = pn_validate_string($_POST['pndata']['text'] ?? '', 65000);
                $moretext = pn_validate_string($_POST['pndata']['moretext'] ?? '', 65000);
                $catid = (int) ($_POST['pndata']['catid'] ?? 0);

                // Gleiches Format wie im Admin (B28); Besucher dürfen nur http(s)-Adressen angeben,
                // das Ziel stammt aus $pn_config['rltargets'].
                [$links] = pn_relatedlinks_from_input(
                    is_array($_POST['pndata']['rl_title'] ?? null) ? $_POST['pndata']['rl_title'] : [],
                    is_array($_POST['pndata']['rl_url'] ?? null) ? $_POST['pndata']['rl_url'] : [],
                    is_array($_POST['pndata']['rl_target'] ?? null) ? $_POST['pndata']['rl_target'] : [],
                    (array) ($pn_config['rltargets'] ?? []),
                    false,
                );
                $relatedlinks = pn_relatedlinks_encode($links);
            }

            // Check who can send news
            if (($pnconfig['newssending'] ?? 'Registered') === 'Registered' && (($pnuser['loggedin'] ?? 'NO') !== 'YES')) {
                $template->message(L_NEWS_CANNOTSENDNEWS, $pn_config['userfile'] . '?page=login', 'warning');
            } elseif ($noCategoriesAvailable) {
                // Show error message when categories are required but none exist
                $template->message(L_NEWS_NOCATS_CANNOT_SEND, $pn_config['newsfile'], 'warning');
            } else {
                $sendFlag = $_GET['pndata']['send'] ?? '';

                if ($sendFlag == 'YES') {
                    // CSRF-Token pruefen (IMP-003)
                    if (!pn_csrf_verify($_POST['csrf_token'] ?? null)) {
                        $template->message(L_ALL_CSRFINVALID, $pn_config['sendnewsfile'], 'danger');

                        return;
                    }

                    if (!trim($title) || !trim($text) || ($pnconfig['categories'] == 'YES' && !$catid)) {
                        // Specific error message for missing category
                        if ($pnconfig['categories'] == 'YES' && !$catid && trim($title) && trim($text)) {
                            $template->message(L_NEWS_SELECTCAT_ERROR, $pn_config['sendnewsfile'], 'danger');
                        } else {
                            $template->message(L_ALL_FILLALL, $pn_config['sendnewsfile'], 'danger');
                        }
                    } else {
                        $now = time();
                        $userId = (int) ($pnuser['id'] ?? 0);
                        $status = 'Unchecked';

                        $stmt = mysqli_prepare($pn_handler, 'INSERT INTO ' . $pn_config['newstable'] . ' (userid, time, catid, title, text, moretext, status, relatedlinks) VALUES(?, ?, ?, ?, ?, ?, ?, ?)');
                        mysqli_stmt_bind_param($stmt, 'iiisssss', $userId, $now, $catid, $title, $text, $moretext, $status, $relatedlinks);
                        mysqli_stmt_execute($stmt);

                        $template->message(L_NEWS_NEWSSENTIN, $pn_config['newsfile'], 'success');
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
            $template->message(L_NEWS_NONEWSSENDIN, $pn_config['newsfile'], 'warning');
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

    /**
     * Meldung zu einem Ergebnis von pn_password_problem().
     */
    public static function passwordmessage(string $problem): string
    {
        return match ($problem) {
            'mismatch' => L_USR_PASSNOTEQUAL,
            'long' => L_USR_PASSWORDTOOLONG,
            default => L_USR_PASSWORDTOOSHORT,
        };
    }

    /**
     * Registrierung: Der Besucher wählt sein Passwort selbst (zweimal eingegeben, Regeln wie im
     * Installer). Die Bestätigungsmail enthält kein Passwort, nur Begrüßung und Anmeldelink.
     */
    public function register(): void
    {
        global $pn_config, $pn_handler;

        $template = new pn_template();
        $sendFlag = $_GET['pndata']['send'] ?? '';

        if ($sendFlag !== 'YES') {
            $template->registerform();

            return;
        }

        $registerUrl = $pn_config['userfile'];

        // CSRF-Token pruefen (IMP-003)
        if (!pn_csrf_verify($_POST['csrf_token'] ?? null)) {
            $template->message(L_ALL_CSRFINVALID, $registerUrl, 'danger');

            return;
        }

        $nickname = pn_validate_nickname($_POST['pndata']['nickname'] ?? '');
        $email = pn_validate_email($_POST['pndata']['email'] ?? '');
        $showemail = pn_validate_yesno($_POST['pndata']['showemail'] ?? 'NO', 'NO');
        $password = is_string($_POST['pndata']['password'] ?? null) ? $_POST['pndata']['password'] : '';
        $password2 = is_string($_POST['pndata']['password2'] ?? null) ? $_POST['pndata']['password2'] : '';

        if ($nickname === '' || $email === '') {
            $template->message(L_USR_INVALIDREGISTRATION, $registerUrl, 'danger');

            return;
        }

        $problem = pn_password_problem($password, $password2);

        if ($problem !== '') {
            $template->message(self::passwordmessage($problem), $registerUrl, 'danger');

            return;
        }

        $stmt = mysqli_prepare($pn_handler, 'SELECT id FROM ' . $pn_config['usertable'] . ' WHERE nickname = ? OR email = ?');
        mysqli_stmt_bind_param($stmt, 'ss', $nickname, $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) !== 0) {
            $template->message(L_USR_USRALREADYEXISTS, $registerUrl, 'danger');

            return;
        }

        $hashedPassword = pn_hash_password($password);
        $now = time();
        $status = 'Activated';

        try {
            $stmt = mysqli_prepare($pn_handler, 'INSERT INTO ' . $pn_config['usertable'] . ' (nickname, email, password, registered, showemail, status) VALUES(?, ?, ?, ?, ?, ?)');
            mysqli_stmt_bind_param($stmt, 'sssiss', $nickname, $email, $hashedPassword, $now, $showemail, $status);
            mysqli_stmt_execute($stmt);
        } catch (mysqli_sql_exception $e) {
            error_log('[register] ' . $e->getMessage());
            $template->message(L_USR_REGISTRATIONFAILED, $registerUrl, 'danger');

            return;
        }

        // Die Bestätigung ist nur eine Begrüßung: Das Konto funktioniert auch ohne sie.
        $mailer = new pn_email();

        if (!$mailer->registeremail($nickname, $email)) {
            error_log('[register] Bestätigungsmail an ' . $email . ' konnte nicht versendet werden.');
        }

        $template->message(L_USR_REGISTERED, $pn_config['userfile'] . '?page=login', 'success');
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

        $userId = (int) $pnuser['id'];
        $token = pn_session_create($pn_handler, $userId, 'frontend');
        pn_session_cookie(PN_COOKIE_FRONTEND, $userId . ':' . $token, time() + PN_SESSION_FRONTEND_LIFETIME);

        $pnuser['loggedin'] = 'YES';

        return $pnuser;
    }

    // Check cookie of user
    public function checkcookie(): ?array
    {
        global $pn_config, $pn_handler;

        $session = pn_session_parse_cookie(PN_COOKIE_FRONTEND);

        if ($session === null) {
            return null;
        }

        [$userId, $token] = $session;
        $tokenHash = pn_session_hash($token, 'frontend');
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
        global $pn_config, $pn_handler, $pnuser;

        $template = new pn_template();
        $loginFlag = $_GET['pndata']['login'] ?? '';

        if ($loginFlag !== 'YES') {
            // Bereits angemeldet: head.inc.php leitet vor jeder Ausgabe zum Profil weiter.
            // Ist PowerNews anders eingebunden, erscheint ein Link statt einer leeren Seite (B32).
            if (($pnuser['loggedin'] ?? 'NO') === 'YES') {
                $template->message(L_USR_ALREADYLOGGEDIN, $pn_config['userfile'] . '?page=profile');

                return;
            }
            $template->loginform();

            return;
        }

        $loginUrl = $pn_config['userfile'] . '?page=login';

        // CSRF-Token pruefen (IMP-003)
        if (!pn_csrf_verify($_POST['csrf_token'] ?? null)) {
            $template->message(L_ALL_CSRFINVALID, $loginUrl, 'danger');

            return;
        }

        $nickname = trim($_POST['pndata']['nickname'] ?? '');
        $password = $_POST['pndata']['password'] ?? '';
        $ip = pn_client_ip();

        if ($nickname === '' || $password === '') {
            $template->message(L_ALL_FILLALL, $loginUrl, 'danger');

            return;
        }

        // Fehlversuchsbremse nur je IP-Adresse (B40): Fehlversuche gegen einen fremden
        // Nickname sperren dessen Inhaber nicht aus.
        if (pn_login_throttled($pn_handler, $ip)) {
            $template->message(L_USR_TOOMANYATTEMPTS, $loginUrl, 'danger');

            return;
        }

        // Fetch user
        $stmt = mysqli_prepare($pn_handler, 'SELECT * FROM ' . $pn_config['usertable'] . ' WHERE nickname = ?');
        mysqli_stmt_bind_param($stmt, 's', $nickname);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $valid = false;

        if (mysqli_num_rows($result) === 1) {
            $row = mysqli_fetch_array($result);

            if (($row['status'] ?? 'Activated') === 'Activated'
                && pn_verify_password($password, $row['password'], (int) $row['id'])) {
                $valid = true;
            }
        } else {
            // Gleiche Rechenzeit wie bei vorhandenen Konten (BUG-046), mit gültigem bcrypt-Hash
            password_verify($password, PN_DUMMY_PASSWORD_HASH);
        }

        pn_login_record($pn_handler, $ip, $nickname, $valid);

        if ($valid) {
            $template->message(L_USR_LOGGEDIN, $pn_config['userfile'] . '?page=profile', 'success');
        } else {
            // Unified message (BUG-010)
            $template->message(L_USR_LOGINFAILED, $loginUrl, 'danger');
        }
    }

    // Send data to user
    public function senddata(): void
    {
        global $pn_config, $pn_handler;

        $template = new pn_template();
        $search = trim($_POST['pndata']['searchstring'] ?? '');
        $genericMsg = L_USR_DATAREQUESTSENT;

        if ($search === '') {
            $template->senddataform();

            return;
        }

        $senddataUrl = $pn_config['userfile'] . '?page=senddata';

        // CSRF-Token pruefen (IMP-003)
        if (!pn_csrf_verify($_POST['csrf_token'] ?? null)) {
            $template->message(L_ALL_CSRFINVALID, $senddataUrl, 'danger');

            return;
        }

        // Eigene Bremse für „Passwort vergessen“ (B40): Anfragen zählen nicht mehr als
        // Login-Fehlversuche und sperren damit keine fremden Konten.
        pn_password_resets_prepare($pn_handler);
        $ip = pn_client_ip();

        if (pn_password_reset_requests($pn_handler, $ip) >= PN_RESET_MAX_PER_IP) {
            $template->message(L_USR_TOOMANYREQUESTS, $senddataUrl, 'danger');

            return;
        }

        // Specific lookup: email if it contains @, else nickname (avoids BUG-022 collision)
        $isEmail = filter_var($search, FILTER_VALIDATE_EMAIL) !== false;
        $sql = $isEmail
            ? 'SELECT id, nickname, email FROM ' . $pn_config['usertable'] . " WHERE email = ? AND status = 'Activated'"
            : 'SELECT id, nickname, email FROM ' . $pn_config['usertable'] . " WHERE nickname = ? AND status = 'Activated'";
        $stmt = mysqli_prepare($pn_handler, $sql);
        mysqli_stmt_bind_param($stmt, 's', $search);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        // B22: Kein Sofort-Reset mehr. Das bisherige Passwort bleibt gültig; der Inhaber
        // bekommt einen Einmal-Link (60 Minuten) und legt das neue Passwort selbst fest.
        $userId = is_array($row) ? (int) $row['id'] : 0;
        $send = $userId > 0 && !pn_password_reset_recent($pn_handler, $userId);
        $token = bin2hex(random_bytes(32));
        pn_password_reset_store($pn_handler, $send ? $userId : 0, $token, $ip);

        if ($send && is_array($row)) {
            $mailer = new pn_email();
            $mailer->dataemail((string) $row['nickname'], (string) $row['email'], $this->resetlink($token));
        }

        // Always reply generic (BUG-021)
        $template->message($genericMsg, $pn_config['userfile'] . '?page=login', 'success');
    }

    /**
     * Absoluter Link zum Festlegen eines neuen Passworts. Basis ist die konfigurierte URL,
     * nie der Host-Header der Anfrage (sonst ließe sich der Link auf fremde Server umbiegen).
     */
    public function resetlink(string $token): string
    {
        return pn_password_link($token);
    }

    /**
     * Seite „Passwort festlegen“ hinter dem Link aus der Reset-Mail (B22) bzw. der Einladung
     * eines Admins. Einladung heißt: Das Konto hat noch kein Passwort; Überschrift, Einleitung
     * und Erfolgsmeldung begrüßen dann statt vom Zurücksetzen zu sprechen.
     */
    public function resetpassword(): void
    {
        global $pn_config, $pn_handler;

        $template = new pn_template();
        $token = (string) ($_POST['pndata']['token'] ?? $_GET['token'] ?? '');

        pn_password_resets_prepare($pn_handler);
        $user = pn_password_reset_user($pn_handler, $pn_config, $token);

        if ($user === null) {
            $template->message(L_USR_RESETINVALID, $pn_config['userfile'] . '?page=senddata', 'danger');

            return;
        }

        $invite = !pn_password_is_set((string) $user['password']);

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->resetform($user, $token, '', $invite);

            return;
        }

        if (!pn_csrf_verify($_POST['csrf_token'] ?? null)) {
            $this->resetform($user, $token, L_ALL_CSRFINVALID, $invite);

            return;
        }

        $password = is_string($_POST['pndata']['password'] ?? null) ? $_POST['pndata']['password'] : '';
        $password2 = is_string($_POST['pndata']['password2'] ?? null) ? $_POST['pndata']['password2'] : '';
        $problem = pn_password_problem($password, $password2);

        if ($problem !== '') {
            $this->resetform($user, $token, self::passwordmessage($problem), $invite);

            return;
        }

        pn_password_reset_complete($pn_handler, $pn_config, (int) $user['id'], $password);
        $template->message($invite ? L_USR_INVITEDONE : L_USR_PASSWORDRESET, $pn_config['userfile'] . '?page=login', 'success');
    }

    // Print out usermenu
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

    // Edit userprofile
    public function profile(): void
    {
        global $pn_config, $pnuser, $pn_handler;

        $template = new pn_template();

        if (!isset($pnuser) || ($pnuser['loggedin'] ?? 'NO') !== 'YES') {
            $template->message(L_USR_NOTLOGGEDIN, $pn_config['userfile'] . '?page=login', 'warning');

            return;
        }

        $sendFlag = $_GET['pndata']['send'] ?? '';

        if ($sendFlag !== 'YES') {
            $template->profileform($pnuser);

            return;
        }

        $profileUrl = $pn_config['userfile'] . '?page=profile';

        // CSRF-Token pruefen (IMP-003)
        if (!pn_csrf_verify($_POST['csrf_token'] ?? null)) {
            $template->message(L_ALL_CSRFINVALID, $profileUrl, 'danger');

            return;
        }

        $nickname = pn_validate_nickname($_POST['pndata']['nickname'] ?? '');
        $email = pn_validate_email($_POST['pndata']['email'] ?? '');
        $password = (string) ($_POST['pndata']['password'] ?? '');
        $password2 = (string) ($_POST['pndata']['password2'] ?? '');
        $showemail = pn_validate_yesno($_POST['pndata']['showemail'] ?? 'NO', 'NO');
        $realname = pn_validate_string($_POST['pndata']['realname'] ?? '', 100);
        $city = pn_validate_string($_POST['pndata']['city'] ?? '', 100);
        $age = pn_validate_int_range($_POST['pndata']['age'] ?? 0, 0, 150, 0);
        $homepage = pn_validate_url($_POST['pndata']['homepage'] ?? '');
        $homepageInput = trim((string) ($_POST['pndata']['homepage'] ?? ''));
        $icq = isset($_POST['pndata']['icq'])
            ? pn_validate_int_range($_POST['pndata']['icq'], 0, 2147483647, 0)
            : (int) ($pnuser['icq'] ?? 0);

        if ($nickname === '' || $email === '' || ($homepageInput !== '' && $homepage === '')) {
            $template->message(L_USR_INVALIDPROFILE, $profileUrl, 'danger');

            return;
        }

        // Passwort-Felder sind optional
        $updatePw = false;
        $hashedPassword = '';

        if ($password !== '' || $password2 !== '') {
            $problem = pn_password_problem($password, $password2);

            if ($problem !== '') {
                $template->message(self::passwordmessage($problem), $profileUrl, 'danger');

                return;
            }
            $hashedPassword = pn_hash_password($password);
            $updatePw = true;
        }

        $userId = (int) $pnuser['id'];
        $stmt = mysqli_prepare($pn_handler, 'SELECT id FROM ' . $pn_config['usertable'] . ' WHERE (nickname = ? OR email = ?) AND id != ?');
        mysqli_stmt_bind_param($stmt, 'ssi', $nickname, $email, $userId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) !== 0) {
            $template->message(L_USR_NICKNAMEOREMAILALREADYUSED, $profileUrl, 'danger');

            return;
        }

        if ($updatePw) {
            $sql = 'UPDATE ' . $pn_config['usertable']
                . ' SET nickname = ?, email = ?, password = ?, showemail = ?, realname = ?, city = ?, age = ?, homepage = ?, icq = ? WHERE id = ?';
            $stmt = mysqli_prepare($pn_handler, $sql);
            mysqli_stmt_bind_param($stmt, 'ssssssissi', $nickname, $email, $hashedPassword, $showemail, $realname, $city, $age, $homepage, $icq, $userId);
        } else {
            $sql = 'UPDATE ' . $pn_config['usertable']
                . ' SET nickname = ?, email = ?, showemail = ?, realname = ?, city = ?, age = ?, homepage = ?, icq = ? WHERE id = ?';
            $stmt = mysqli_prepare($pn_handler, $sql);
            mysqli_stmt_bind_param($stmt, 'sssssisii', $nickname, $email, $showemail, $realname, $city, $age, $homepage, $icq, $userId);
        }
        mysqli_stmt_execute($stmt);

        if ($updatePw) {
            // Neues Passwort: alle Sitzungen dieses Kontos beenden (B26).
            pn_sessions_delete_for_user($pn_handler, $userId);
        }

        $template->message(L_USR_PROFILEEDITED, $pn_config['userfile'] . '?page=profile', 'success');
    }

    // Logout
    public function logout(): void
    {
        global $pnuser, $pn_config;
        $template = new pn_template();

        if (isset($pnuser) && is_array($pnuser) && ($pnuser['loggedin'] ?? 'NO') === 'YES') {
            $template->logout($pnuser);
        } else {
            $template->message(L_USR_CANNOTLOGOUT, $pn_config['userfile'] . '?page=login', 'warning');
        }
    }

    // Delete usercookie
    public function delusercookie(): void
    {
        global $pn_config, $pn_handler, $pnuser;

        $session = pn_session_parse_cookie(PN_COOKIE_FRONTEND);

        if ($session !== null) {
            pn_session_delete($pn_handler, $session[1], 'frontend');
        }

        pn_session_cookie(PN_COOKIE_FRONTEND, '', time() - 3600);
        unset($pnuser, $_COOKIE[PN_COOKIE_FRONTEND]);

        if (!headers_sent()) {
            header('Location: ./' . $pn_config['userfile'] . '?page=login');
        }
    }

    /**
     * Formular für das neue Passwort (Einladung oder Zurücksetzen).
     *
     * @param array<string, mixed> $user
     */
    private function resetform(array $user, string $token, string $error, bool $invite): void
    {
        global $pn_config;

        $action = pn_escape($pn_config['userfile']) . '?page=resetpassword';
        $nickname = pn_escape((string) $user['nickname']);
        ?>
<form accept-charset="UTF-8" action="<?php echo $action; ?>" method="post" class="card mb-4" id="pn_passwordform" data-purpose="<?php echo $invite ? 'invite' : 'reset'; ?>">
    <h2 class="card-header h6 mb-0"><?php echo $invite ? L_USR_INVITETITLE : L_USR_RESETTITLE; ?></h2>
    <div class="card-body">
<?php if ($error !== '') { ?>
        <div class="alert alert-danger" role="alert"><?php echo $error; ?></div>
<?php } ?>
        <p><?php echo sprintf($invite ? L_USR_INVITEINTRO : L_USR_RESETINTRO, $nickname); ?></p>
        <div class="mb-3">
            <label for="pn_newpassword" class="form-label fw-bold"><?php echo $invite ? L_USR_PASSWORDLABEL : L_USR_NEWPASSWORD; ?></label>
            <input type="password" class="form-control" name="pndata[password]" id="pn_newpassword" minlength="8" maxlength="72" autocomplete="new-password" required aria-describedby="pn_newpassword_help">
            <div id="pn_newpassword_help" class="form-text"><?php echo L_USR_PASSWORDHINT; ?></div>
        </div>
        <div class="mb-3">
            <label for="pn_newpassword2" class="form-label fw-bold"><?php echo $invite ? L_USR_PASSWORDREPEATLABEL : L_USR_REPEATNEWPASSWORD; ?></label>
            <input type="password" class="form-control" name="pndata[password2]" id="pn_newpassword2" minlength="8" maxlength="72" autocomplete="new-password" required>
        </div>
        <button type="submit" class="btn btn-primary"><?php echo L_USR_SAVEPASSWORD; ?></button>
        <input type="hidden" name="pndata[token]" value="<?php echo pn_escape($token); ?>">
        <input type="hidden" name="csrf_token" value="<?php echo pn_escape(pn_csrf_token()); ?>">
    </div>
</form>
        <?php
    }
}

//#################################################################################################

// E-Mail class
class pn_email
{
    // Bestätigung der Registrierung: Begrüßung und Anmeldelink, nie ein Passwort
    public function registeremail(string $nickname, string $email): bool
    {
        global $pnconfig;
        $template = new pn_template();
        $registeremail = $template->registeremail($nickname, $email);

        if ($registeremail) {
            return pn_send_mail($email, sprintf(L_EMAIL_SUBJECT_REGISTER, pn_site_name()), $registeremail, L_EMAIL_AUTHOR, (string) $pnconfig['email']);
        }

        return false;
    }

    // Mail „Passwort vergessen“ mit Einmal-Link (B22)
    public function dataemail(string $nickname, string $email, string $resetlink): bool
    {
        global $pnconfig;
        $template = new pn_template();
        $dataemail = $template->dataemail($nickname, $email, $resetlink);

        if ($dataemail) {
            return pn_send_mail($email, sprintf(L_EMAIL_SUBJECT_RESET, pn_site_name()), $dataemail, L_EMAIL_AUTHOR, (string) $pnconfig['email']);
        }

        return false;
    }
}

//#################################################################################################

// Template class
class pn_template
{
    /**
     * Gibt eine Meldung über das Template „message“ aus (B29). $type steuert Farbe und
     * Überschrift: success, danger, warning oder info. Der Rücklink erscheint als eigene
     * Schaltfläche ({LINK}, {LINKTEXT}), nicht mehr als verlinkter Meldungstext.
     */
    public function message(string $text, string $link, string $type = 'info'): bool
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

            $headings = ['success' => L_MSG_SUCCESS, 'danger' => L_MSG_DANGER, 'warning' => L_MSG_WARNING, 'info' => L_MSG_INFO];
            $type = isset($headings[$type]) ? $type : 'info';

            echo pn_template_fill((string) $message, [
                'MESSAGE' => $text,
                'LINK' => pn_escape($link),
                'TYPE' => $type,
                'HEADING' => $headings[$type],
                'LINKTEXT' => L_MSG_CONTINUE,
            ]);

            return true;
        }

        return false;
    }

    /**
     * HTML für den Platzhalter {CATPIC}: das Kategoriebild, wenn Kategoriebilder aktiviert
     * sind und die Kategorie ein Bild hat, sonst ein Leerstring (der Platzhalter bleibt nie
     * sichtbar stehen).
     */
    public function categorypic(mixed $category): string
    {
        global $pnconfig;

        if (($pnconfig['categorypics'] ?? 'NO') !== 'YES' || !is_array($category)) {
            return '';
        }

        $picture = basename(trim((string) ($category['picture'] ?? '')));

        if ($picture === '') {
            return '';
        }

        return '<img src="./pngfx/categories/' . pn_escape($picture) . '" class="pn-catpic float-end ms-3 mb-2" alt="' . pn_escape((string) ($category['name'] ?? '')) . '">';
    }

    // BB replacements
    public function bbreplace(string $text): string
    {
        global $pnconfig;

        $text = preg_replace("!\[(?i)b\]!", '<b>', $text);
        $text = preg_replace("!\[/(?i)b\]!", '</b>', (string) $text);
        $text = preg_replace("!\[(?i)u\]!", '<u>', (string) $text);
        $text = preg_replace("!\[/(?i)u\]!", '</u>', (string) $text);
        $text = preg_replace("!\[(?i)i\]!", '<i>', (string) $text);
        $text = preg_replace("!\[/(?i)i\]!", '</i>', (string) $text);
        // [url=Adresse]Text[/url] und [url]Adresse[/url]: nur http(s), ohne Schema gilt https://.
        $link = static function (string $address, string $label, string $original): string {
            $href = pn_bbcode_url($address);

            return $href === null ? $original : '<a href="' . $href . '" target="_blank" rel="noopener noreferrer">' . $label . '</a>';
        };
        $text = preg_replace_callback('!\[url=([^\]]+)\](.+?)\[/url\]!i', static fn (array $m): string => $link($m[1], $m[2], $m[0]), (string) $text);
        $text = preg_replace_callback('!\[url\]([^\[]+?)\[/url\]!i', static fn (array $m): string => $link($m[1], $m[1], $m[0]), (string) $text);
        $text = preg_replace("!\[(?i)email\]([a-zA-Z0-9-._]+@[a-zA-Z0-9-.]+)\[/(?i)email\]!", '<a href="mailto:\\1">\\1</a>', (string) $text);

        // Whitelist [img] to own host only (BUG-048)
        $ownHost = parse_url((string) ($pnconfig['url'] ?? ''), PHP_URL_HOST) ?: 'localhost';
        $allowedImgHosts = '#^https?://(localhost|127\.0\.0\.1|' . preg_quote($ownHost, '#') . ')(/.*)?$#i';
        $text = preg_replace_callback(
            "!\[(?i)img\]([a-zA-Z0-9:/\?\[\]=.@-]+)\[(?i)/img\]!",
            static function ($m) use ($allowedImgHosts) {
                $url = $m[1];

                if (preg_match($allowedImgHosts, $url)) {
                    return '<img src="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" alt="" border="0">';
                }

                return '[img]' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '[/img]';
            },
            (string) $text,
        );

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

            // Always escape user-provided content (BUG-025)
            $title = htmlspecialchars((string) $title, ENT_QUOTES, 'UTF-8');

            if ($pnconfig['bbcode'] == 'Comments/News' || $pnconfig['bbcode'] == 'News') {
                $title = $this->bbreplace($title);
            }

            if ($pnconfig['smilies'] == 'Comments/News' || $pnconfig['smilies'] == 'News') {
                $title = $this->smiliereplace($title);
            }

            $date = pn_format_date($time, (string) $pnconfig['dateformat']);
            $timeStr = pn_format_date($time, (string) $pnconfig['timeformat']);

            echo pn_template_fill((string) $headline, [
                'ID' => $id,
                'DATE' => $date,
                'TIME' => $timeStr,
                'CATEGORY' => is_array($category) ? pn_escape($category['name']) : (string) $category,
                'TITLE' => $title,
                'CATPIC' => $this->categorypic($category),
                'CATID' => is_array($category) ? (string) $category['id'] : '',
            ]);

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

            // Always escape user-provided content (BUG-025)
            $title = htmlspecialchars((string) $title, ENT_QUOTES, 'UTF-8');
            $text = htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
            $moretext = htmlspecialchars((string) $moretext, ENT_QUOTES, 'UTF-8');

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

            $date = pn_format_date($time, (string) $pnconfig['dateformat']);
            $timeStr = pn_format_date($time, (string) $pnconfig['timeformat']);

            $more = '';

            if ($pnconfig['moretext'] == 'YES' && $moretext) {
                if ($details === 'YES') {
                    $text = '<b>' . $text . '</b><br><br>' . $moretext;
                } else {
                    $more = '[ <a href="' . pn_escape($pn_config['detailfile']) . '?newsid=' . $id . '">' . L_NEWS_MORE . '</a> ]';
                }
            }

            // Related Links auswerten. {RELATEDLINKS} muss IMMER ersetzt werden,
            // damit der Platzhalter-Text nicht im Output sichtbar bleibt, wenn die
            // Funktion in der Konfiguration deaktiviert ist oder keine Links gepflegt wurden.
            $rlinks = '';

            if ($pnconfig['relatedlinks'] == 'YES') {
                foreach (pn_relatedlinks_decode($relatedlinks) as $link) {
                    // Auch Altdaten mit fremdem Schema (javascript: …) nie als Link ausgeben (B37).
                    if (pn_relatedlink_url_allowed($link['url'], true)) {
                        $rlinks .= (string) $this->relatedlinks($link['title'], $link['url'], pn_relatedlink_target($link['target']));
                    }
                }
            }
            $news = pn_template_fill((string) $news, [
                'ID' => $id,
                'AUTHOR' => $author,
                'DATE' => $date,
                'TIME' => $timeStr,
                'CATEGORY' => is_array($category) ? pn_escape($category['name']) : (string) $category,
                'CATID' => is_array($category) ? (string) $category['id'] : '',
                'TITLE' => $title,
                'MORE' => $more,
                'TEXT' => $text,
                'COMMENTS' => $comments,
                'CATPIC' => $this->categorypic($category),
                'RELATEDLINKS' => $rlinks,
            ]);

            // Optionale Related-Links-Section ueber Konditional-Marker
            // <!--RELATEDLINKS_START-->...<!--RELATEDLINKS_END--> komplett ausblenden,
            // wenn keine Links generiert wurden. So bleibt die Sidebar im Template
            // gestaltbar, verschwindet aber automatisch bei leerem Inhalt.
            $newsStr = $news;

            // <!--COMMENTS_START-->…<!--COMMENTS_END--> nur zeigen, wenn Kommentare aktiv sind (B48).
            if (($pnconfig['comments'] ?? 'NO') === 'YES') {
                $newsStr = (string) preg_replace('/<!--\s*COMMENTS_(START|END)\s*-->/', '', $newsStr);
            } else {
                $newsStr = (string) preg_replace('/<!--\s*COMMENTS_START\s*-->.*?<!--\s*COMMENTS_END\s*-->/s', '', $newsStr);
            }

            if ($rlinks === '') {
                $newsStr = (string) preg_replace('/<!--\s*RELATEDLINKS_START\s*-->.*?<!--\s*RELATEDLINKS_END\s*-->/s', '', $newsStr);
            } else {
                $newsStr = (string) preg_replace('/<!--\s*RELATEDLINKS_(START|END)\s*-->/', '', $newsStr);
            }
            $news = $newsStr;

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

            // Always escape user-provided content (BUG-025)
            $text = htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');

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

            $date = pn_format_date($time, (string) $pnconfig['dateformat']);
            $timeStr = pn_format_date($time, (string) $pnconfig['timeformat']);

            echo pn_template_fill((string) $comment, [
                'ID' => $id,
                'AUTHOR' => $user,
                'DATE' => $date,
                'TIME' => $timeStr,
                'TEXT' => $text,
            ]);

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

            return pn_template_fill((string) $relatedlink, [
                'TITLE' => pn_escape($title),
                'URL' => pn_escape($url),
                'TARGET' => pn_escape($target),
            ]);
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

            echo pn_template_fill((string) $commentform, [
                'NEWSID' => $newsid,
                'NAME' => pn_escape($name),
                'CSRF' => pn_csrf_token(),
            ]);

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
            echo pn_template_fill(self::withpasswordfields((string) $registerform), ['CSRF' => pn_csrf_token()]);

            return true;
        }

        return false;
    }

    /**
     * Registrierungsformulare bis 3.12 (und eigene Anpassungen davon) haben keine Passwortfelder;
     * seit 3.12 wählt der Besucher sein Passwort selbst. Fehlen die Felder, werden sie vor der
     * ersten Absende-Schaltfläche (sonst vor </form>) eingefügt.
     */
    public static function withpasswordfields(string $form): string
    {
        if (str_contains($form, 'pndata[password]')) {
            return $form;
        }

        $fields = '<div class="row g-2 mb-3"><div class="col-12 col-md-6"><label for="pn_password" class="form-label fw-bold">' . L_USR_PASSWORDLABEL . '</label>'
            . '<input type="password" class="form-control" name="pndata[password]" id="pn_password" minlength="8" maxlength="72" autocomplete="new-password" required aria-describedby="pn_password_help"></div>'
            . '<div class="col-12 col-md-6"><label for="pn_password2" class="form-label fw-bold">' . L_USR_PASSWORDREPEATLABEL . '</label>'
            . '<input type="password" class="form-control" name="pndata[password2]" id="pn_password2" minlength="8" maxlength="72" autocomplete="new-password" required></div>'
            . '<div id="pn_password_help" class="form-text">' . L_USR_PASSWORDHINT . '</div></div>';

        foreach (['<button type="submit"', '<input type="submit"', '</form>'] as $anchor) {
            $position = stripos($form, $anchor);

            if ($position !== false) {
                return substr($form, 0, $position) . $fields . substr($form, $position);
            }
        }

        return $form . $fields;
    }

    /**
     * Text der Bestätigungsmail nach der Registrierung. Vorlagen, die noch ein Passwort
     * verschicken wollten ({PASSWORD}), ersetzt der Standardtext aus der Sprachdatei.
     */
    public function registeremail(string $nickname, string $email): string|false
    {
        $text = pn_mail_from_template('registeremail', '', 'PASSWORD', L_USR_REGISTERMAIL_BODY, [
            'NICKNAME' => $nickname,
            'EMAIL' => $email,
        ]);

        return $text ?? false;
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
            echo pn_template_fill((string) $loginform, ['CSRF' => pn_csrf_token()]);

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
            echo pn_template_fill((string) $senddataform, ['CSRF' => pn_csrf_token()]);

            return true;
        }

        return false;
    }

    /**
     * Text der Mail „Passwort vergessen“ (B22). Sie enthält einen Einmal-Link, nie ein
     * Passwort. Vorlagen bis 3.11 kennen {RESETLINK} nicht; dann gilt der Standardtext
     * aus der Sprachdatei.
     */
    public function dataemail(string $nickname, string $email, string $resetlink): string|false
    {
        $text = pn_mail_from_template('dataemail', 'RESETLINK', '', L_USR_RESETMAIL_BODY, [
            'NICKNAME' => $nickname,
            'EMAIL' => $email,
            'RESETLINK' => $resetlink,
            'VALIDMINUTES' => intdiv(PN_RESET_LIFETIME, 60),
        ]);

        return $text ?? false;
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

            echo pn_template_fill((string) $profileform, [
                'NICKNAME' => pn_escape($pnuser['nickname']),
                'EMAIL' => pn_escape($pnuser['email']),
                'SHOWEMAIL' => (($pnuser['showemail'] ?? '') == 'YES') ? 'checked' : '',
                // Das Passwort wird nie angezeigt, der Benutzer gibt bei Bedarf ein neues ein.
                'PASSWORD' => '',
                'REALNAME' => pn_escape($pnuser['realname'] ?? ''),
                'CITY' => pn_escape($pnuser['city'] ?? ''),
                'AGE' => pn_escape($pnuser['age'] ?? ''),
                'HOMEPAGE' => pn_escape($pnuser['homepage'] ?? ''),
                'ICQ' => pn_escape($pnuser['icq'] ?? ''),
                'CSRF' => pn_csrf_token(),
            ]);

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

            echo pn_template_fill((string) $logout, [
                'NICKNAME' => pn_escape($pnuser['nickname']),
                'CSRF' => pn_csrf_token(),
            ]);

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

            $monthNames = [
                1 => L_TEMPL_JANUARY, 2 => L_TEMPL_FEBRUARY, 3 => L_TEMPL_MARCH, 4 => L_TEMPL_APRIL,
                5 => L_TEMPL_MAY, 6 => L_TEMPL_JUNE, 7 => L_TEMPL_JULY, 8 => L_TEMPL_AUGUST,
                9 => L_TEMPL_SEPTEMBER, 10 => L_TEMPL_OCTOBER, 11 => L_TEMPL_NOVEMBER, 12 => L_TEMPL_DECEMBER,
            ];
            $selectedMonth = (int) ($pndata['showmonth'] ?? 0);
            $monthselect = "<select class=\"form-select\" name=\"pndata[showmonth]\" id=\"pn_showmonth\">\n";

            foreach ($monthNames as $month => $monthname) {
                $monthselect .= '<option value="' . $month . '"' . ($selectedMonth === $month ? ' selected' : '') . '>' . pn_escape($monthname) . "</option>\n";
            }
            $monthselect .= "</select>\n";

            echo pn_template_fill((string) $archive, [
                'SELECTYEAR' => $pndata['yearselect'] ?? '',
                'SELECTMONTH' => $monthselect,
                'SEARCHSTRING' => pn_escape($pndata['searchstring'] ?? ''),
            ]);

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

            $relatedlinks = '';

            if ($pnconfig['relatedlinks'] == 'YES') {
                $targets = '';
                $counter = count($pn_config['rltargets']);

                for ($i = 0; $i < $counter; ++$i) {
                    $targets .= '<option value="' . pn_escape($pn_config['rltargets'][$i]) . '">' . pn_escape(pn_relatedlink_target_label((string) $pn_config['rltargets'][$i])) . '</option>';
                }
                $relatedlinks = '<div class="mb-3"><span class="form-label fw-bold d-block">' . L_NEWS_RELATEDLINKS . '</span>'
                    . '<div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead><tr><th>' . L_NEWS_RL_TITLE . '</th><th>' . L_NEWS_RL_URL . '</th><th>' . L_NEWS_RL_TARGET . '</th></tr></thead><tbody>';

                for ($i = 0; $i < (int) $pnconfig['relatedlinks_num']; ++$i) {
                    $relatedlinks .= '<tr><td><input class="form-control form-control-sm" name="pndata[rl_title][]" maxlength="50" aria-label="' . L_NEWS_RL_TITLE . '"></td>'
                        . '<td><input class="form-control form-control-sm" type="url" name="pndata[rl_url][]" maxlength="250" placeholder="https://" aria-label="' . L_NEWS_RL_URL . '"></td>'
                        . '<td><select class="form-select form-select-sm" name="pndata[rl_target][]" aria-label="' . L_NEWS_RL_TARGET . '">' . $targets . '</select></td></tr>';
                }
                $relatedlinks .= '</tbody></table></div></div>';
            }
            echo pn_template_fill((string) $sendnewsform, [
                'USER' => $user,
                'CATEGORYSELECT' => $catselect,
                'RELATEDLINKS' => $relatedlinks,
                'CSRF' => pn_csrf_token(),
            ]);

            return true;
        }

        return false;
    }
}

//#################################################################################################

function pn_cpi(): void
{
    // Frueher rendete diese Funktion eine zusaetzliche "PowerNews x.x © Copyright by PowerScripts"-
    // Zeile direkt unter dem News/Archive/Detail-Inhalt. Mit dem Bootstrap-5-Layout uebernimmt
    // dieser Aufgabe der globale Footer in footer.inc.php (Brand + Copyright in einer Zeile),
    // damit die Information nicht doppelt erscheint. Funktion bleibt fuer API-Kompatibilitaet
    // erhalten, gibt aber bewusst keinen Output mehr aus.
}
