<?php
declare(strict_types=1);
/**
 * PHPUnit Bootstrap for PowerNews Tests
 */

// Autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Define test constants
define('POWERNEWS_TEST', true);

// Load frontend language file first
require_once __DIR__ . '/../pninc/lang/english.php';

// Load admin language file - suppress "constant already defined" notices for overlaps
$adminLangFile = __DIR__ . '/../pnadmin/lang/english.php';
$adminLangContent = file_get_contents($adminLangFile);
// Extract define() calls and only execute those for undefined constants
preg_match_all("/define\('([^']+)',\s*(.+?)\);/s", $adminLangContent, $matches, PREG_SET_ORDER);
foreach ($matches as $match) {
    if (!defined($match[1])) {
        define($match[1], eval('return ' . $match[2] . ';'));
    }
}

// Load validation and escape layers (needed by DTOs and other code)
require_once __DIR__ . '/../pninc/validation.inc.php';
require_once __DIR__ . '/../pninc/escape.inc.php';

// Load source files - these define all classes and functions
require_once __DIR__ . '/../pninc/functions.inc.php';
require_once __DIR__ . '/../pnadmin/dto.inc.php';
require_once __DIR__ . '/../pnadmin/functions.inc.php';

/**
 * Connect to the test database and set up global variables.
 */
function pn_test_connect_db(): mysqli
{
    $host = getenv('PN_DB_HOST') ?: 'localhost';
    $port = (int)(getenv('PN_DB_PORT') ?: 3317);
    $user = getenv('PN_DB_USER') ?: 'powernews';
    $pass = getenv('PN_DB_PASS') ?: 'powernews';
    $name = getenv('PN_DB_NAME') ?: 'powernews';

    $handler = @mysqli_connect($host, $user, $pass, $name, $port);
    if (!$handler) {
        throw new RuntimeException('Test DB connection failed: ' . mysqli_connect_error());
    }
    mysqli_set_charset($handler, 'utf8mb4');

    return $handler;
}

/**
 * Set up global $pn_config with table names.
 */
function pn_test_setup_config(): array
{
    return [
        'cattable' => 'pn_categories',
        'commenttable' => 'pn_comments',
        'configtable' => 'pn_config',
        'newstable' => 'pn_news',
        'permissionstable' => 'pn_permissions',
        'templatetable' => 'pn_templates',
        'usertable' => 'pn_users',
        'newsfile' => 'index.php',
        'detailfile' => 'news.php',
        'commentfile' => 'comments.php',
        'userfile' => 'user.php',
        'archivefile' => 'archive.php',
        'sendnewsfile' => 'sendnews.php',
        'version' => '3.00',
        'rltargets' => ['_blank', '_main'],
        'language' => 'english',
        'acpuffer' => true,
    ];
}

/**
 * Set up the test database schema using the SQL dump.
 * Handles semicolons inside quoted strings correctly.
 */
function pn_test_setup_schema(mysqli $handler): void
{
    $dumpFile = __DIR__ . '/../powernews.sql';
    $content = file_get_contents($dumpFile);
    if ($content === false) {
        throw new RuntimeException('Cannot read SQL dump: ' . $dumpFile);
    }

    // Remove comment lines
    $lines = explode("\n", $content);
    $sql = '';
    foreach ($lines as $line) {
        $trimmed = ltrim($line);
        if ($trimmed !== '' && !str_starts_with($trimmed, '#')) {
            $sql .= $line . "\n";
        }
    }

    // Parse SQL respecting quoted strings
    $commands = [];
    $current = '';
    $inSingleQuote = false;
    $escaped = false;
    $len = strlen($sql);

    for ($i = 0; $i < $len; $i++) {
        $char = $sql[$i];

        if ($escaped) {
            $current .= $char;
            $escaped = false;
            continue;
        }

        if ($char === '\\') {
            $current .= $char;
            $escaped = true;
            continue;
        }

        if ($char === "'") {
            $inSingleQuote = !$inSingleQuote;
            $current .= $char;
            continue;
        }

        if ($char === ';' && !$inSingleQuote) {
            $trimmed = trim($current);
            if ($trimmed !== '') {
                $commands[] = $trimmed;
            }
            $current = '';
            continue;
        }

        $current .= $char;
    }

    $trimmed = trim($current);
    if ($trimmed !== '') {
        $commands[] = $trimmed;
    }

    foreach ($commands as $command) {
        @mysqli_query($handler, $command);
    }
}

/**
 * Get default pnconfig from the config table.
 */
function pn_test_get_pnconfig(mysqli $handler, array $pn_config): array
{
    $result = mysqli_query($handler, 'SELECT * FROM ' . $pn_config['configtable']);
    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    // Default config values
    return [
        'categories' => 'YES',
        'categorypics' => 'NO',
        'comments' => 'YES',
        'commentwriting' => 'Guests & Registered',
        'moretext' => 'NO',
        'sendnews' => 'YES',
        'newssending' => 'Guests & Registered',
        'smilies' => 'Comments',
        'bbcode' => 'Comments/News',
        'html' => 'News',
        'dateformat' => 'd.m.Y',
        'timeformat' => 'H:i',
        'template' => 1,
        'url' => 'http://www.powerscripts.org',
        'email' => 'daemon@powerscripts.org',
        'headlines' => 10,
        'news' => 10,
        'spamprotection' => 30,
        'relatedlinks' => 'NO',
        'relatedlinks_num' => 5,
    ];
}
