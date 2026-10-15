<?php

declare(strict_types=1);

/**
 * Prüft den echten Ladeweg eines Einstiegs: Lädt nur die Dateien, die auch im Betrieb
 * eingebunden werden, und meldet jeden Aufruf einer Funktion bzw. jede Klasse, die in
 * diesem Ladeweg nicht definiert ist.
 *
 *   php load-path-check.php admin|update|frontend [zusätzliche-datei.php ...]
 *
 * Läuft in einem eigenen PHP-Prozess, weil tests/bootstrap.php Frontend- und
 * Adminfunktionen gemeinsam lädt. Dort fiel nicht auf, dass pnadmin_comment_text() in
 * 3.12 pn_query_by_id() aufrief, das nur in pninc/functions.inc.php stand: „News
 * bearbeiten“ > „Kommentare editieren“ endete im Adminbereich mit HTTP 500.
 *
 * Geprüft werden die Dateien des Einstiegs selbst und alle Projektdateien, die der
 * Ladeweg einbindet. Ausgabe: JSON-Liste der Fundstellen, leer wenn alles definiert ist.
 */

set_error_handler(static function (int $severity, string $message, string $file, int $line): never {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

$pnCheckRoot = (string) realpath(__DIR__ . '/../..');
$pnCheckChain = $argv[1] ?? '';
$pnCheckExtra = array_slice($argv, 2);

/*
 * config.inc.php baut die Datenbankverbindung nur auf, wenn $pn_handler noch nicht gesetzt
 * ist. Für die Prüfung genügt der Ladeweg, eine Datenbank wird nicht gebraucht.
 */
$pn_handler = false;

switch ($pnCheckChain) {
    case 'admin':
        // wie pnadmin/phpheader.inc.php (Einstieg pnadmin/index.php)
        chdir($pnCheckRoot . '/pnadmin');
        include $pnCheckRoot . '/pninc/config.inc.php';
        include $pnCheckRoot . '/pnadmin/functions.inc.php';
        include $pnCheckRoot . '/pnadmin/dto.inc.php';
        include $pnCheckRoot . '/pnadmin/lang/english.php';
        // alle Seiten, die index.php einbindet, und die Sprach- und Hilfedateien
        $pnCheckEntries = array_merge(glob($pnCheckRoot . '/pnadmin/*.php') ?: [], glob($pnCheckRoot . '/pnadmin/lang/*.php') ?: []);
        break;

    case 'update':
        // wie update.php
        chdir($pnCheckRoot);
        include $pnCheckRoot . '/pninc/config.inc.php';
        include $pnCheckRoot . '/pnadmin/functions.inc.php';
        require $pnCheckRoot . '/pninc/installer/autoload.php';
        $pnCheckEntries = [$pnCheckRoot . '/update.php'];
        break;

    case 'frontend':
        // wie pninc/head.inc.php (Einstieg index.php, news.php, comments.php …)
        chdir($pnCheckRoot);
        include $pnCheckRoot . '/pninc/config.inc.php';
        include $pnCheckRoot . '/pninc/functions.inc.php';
        include $pnCheckRoot . '/pninc/lang/english.php';
        $pnCheckEntries = array_map(static fn (string $file): string => $pnCheckRoot . '/' . $file, [
            'index.php', 'news.php', 'comments.php', 'archive.php', 'user.php', 'sendnews.php',
            'header.inc.php', 'footer.inc.php',
            'pninc/archive.inc.php', 'pninc/comments.inc.php', 'pninc/details.inc.php', 'pninc/headlines.inc.php',
            'pninc/news.inc.php', 'pninc/sendnews.inc.php', 'pninc/user.inc.php', 'pninc/usermenu.inc.php',
        ]);
        break;

    default:
        fwrite(STDERR, "Aufruf: php load-path-check.php admin|update|frontend [datei.php ...]\n");
        exit(2);
}

// config.inc.php leitet Fehler ins Protokoll um; die Prüfung meldet sie auf STDERR.
ini_set('log_errors', '0');
ini_set('display_errors', 'stderr');

/**
 * Top-Level-Funktionen und Klassen aller Projektdateien, nur für den Hinweis, wo ein
 * fehlender Name tatsächlich definiert ist.
 *
 * @return array<string, string> kleingeschriebener Name => relativer Dateipfad
 */
function pn_check_declarations(string $root): array
{
    $files = [];
    foreach (['', 'pninc/', 'pninc/lang/', 'pninc/installer/', 'pnadmin/', 'pnadmin/lang/'] as $dir) {
        $files = array_merge($files, glob($root . '/' . $dir . '*.php') ?: []);
    }

    $declared = [];
    foreach ($files as $file) {
        $tokens = PhpToken::tokenize((string) file_get_contents((string) $file));
        $namespace = '';
        $depth = 0;
        foreach ($tokens as $i => $token) {
            $depth += pn_check_depth_change($token);
            if ($token->is(T_NAMESPACE)) {
                $namespace = pn_check_namespace_name($tokens, $i);
                continue;
            }
            $isFunction = $token->is(T_FUNCTION) && $depth === 0;
            $isClass = $token->is([T_CLASS, T_INTERFACE, T_TRAIT, T_ENUM]) && !pn_check_prev($tokens, $i)?->is([T_DOUBLE_COLON, T_NEW]);
            $next = pn_check_next($tokens, $i);
            if (($isFunction || $isClass) && $next !== null && $next->is(T_STRING)) {
                $name = ltrim($namespace . '\\' . $next->text, '\\');
                $declared[strtolower($name)] = substr((string) $file, strlen($root) + 1);
            }
        }
    }

    return $declared;
}

function pn_check_depth_change(PhpToken $token): int
{
    if ($token->text === '{' || $token->is([T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES])) {
        return 1;
    }

    return $token->text === '}' ? -1 : 0;
}

/**
 * @param list<PhpToken> $tokens
 */
function pn_check_next(array $tokens, int $i): ?PhpToken
{
    for ($j = $i + 1, $n = count($tokens); $j < $n; ++$j) {
        if (!$tokens[$j]->isIgnorable()) {
            return $tokens[$j];
        }
    }

    return null;
}

/**
 * @param list<PhpToken> $tokens
 */
function pn_check_prev(array $tokens, int $i): ?PhpToken
{
    for ($j = $i - 1; $j >= 0; --$j) {
        if (!$tokens[$j]->isIgnorable()) {
            return $tokens[$j];
        }
    }

    return null;
}

/**
 * @param list<PhpToken> $tokens
 */
function pn_check_namespace_name(array $tokens, int $i): string
{
    $next = pn_check_next($tokens, $i);

    return $next !== null && $next->is([T_STRING, T_NAME_QUALIFIED]) ? $next->text : '';
}

/**
 * Liest eine Anweisung „use A\B [as C], …;“ auf oberster Ebene.
 *
 * @param list<PhpToken> $tokens
 *
 * @return array{0: 'class'|'function'|'const', 1: array<string, string>} Art und Alias => voller Name
 */
function pn_check_use_statement(array $tokens, int $i): array
{
    $kind = 'class';
    $map = [];
    $name = '';
    $alias = '';
    $expectAlias = false;

    for ($j = $i + 1, $n = count($tokens); $j < $n && $tokens[$j]->text !== ';'; ++$j) {
        $token = $tokens[$j];
        if ($token->is(T_FUNCTION)) {
            $kind = 'function';
        } elseif ($token->is(T_CONST)) {
            $kind = 'const';
        } elseif ($token->is(T_AS)) {
            $expectAlias = true;
        } elseif ($token->is([T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED])) {
            if ($expectAlias) {
                $alias = $token->text;
            } else {
                $name = ltrim($token->text, '\\');
            }
        } elseif ($token->text === ',') {
            $map = pn_check_add_alias($map, $name, $alias);
            [$name, $alias, $expectAlias] = ['', '', false];
        }
    }

    return [$kind, pn_check_add_alias($map, $name, $alias)];
}

/**
 * @param array<string, string> $map
 *
 * @return array<string, string>
 */
function pn_check_add_alias(array $map, string $name, string $alias): array
{
    if ($name !== '') {
        $short = $alias !== '' ? $alias : substr((string) strrchr('\\' . $name, '\\'), 1);
        $map[strtolower($short)] = $name;
    }

    return $map;
}

/**
 * @param array<string, string> $uses
 */
function pn_check_class_name(string $name, string $namespace, array $uses): string
{
    if (str_starts_with($name, '\\')) {
        return substr($name, 1);
    }

    $parts = explode('\\', $name, 2);
    $first = strtolower($parts[0]);
    if (isset($uses[$first])) {
        return $uses[$first] . (isset($parts[1]) ? '\\' . $parts[1] : '');
    }

    return ltrim($namespace . '\\' . $name, '\\');
}

/**
 * Fundstellen einer Datei: aufgerufene Funktionen und verwendete Klassen, die es im
 * geladenen Stand nicht gibt.
 *
 * @return list<array{file: string, line: int, kind: string, name: string}>
 */
function pn_check_file(string $file, string $root): array
{
    $tokens = PhpToken::tokenize((string) file_get_contents($file));
    $relative = str_replace('\\', '/', substr($file, strlen($root) + 1));
    $namespace = '';
    $uses = ['class' => [], 'function' => [], 'const' => []];
    $depth = 0;
    $found = [];

    foreach ($tokens as $i => $token) {
        $depth += pn_check_depth_change($token);

        if ($token->is(T_NAMESPACE) && pn_check_next($tokens, $i)?->is([T_STRING, T_NAME_QUALIFIED])) {
            $namespace = pn_check_namespace_name($tokens, $i);
            continue;
        }

        if ($token->is(T_USE) && $depth === 0 && pn_check_next($tokens, $i)?->text !== '(') {
            [$kind, $map] = pn_check_use_statement($tokens, $i);
            $uses[$kind] = $map + $uses[$kind];
            continue;
        }

        if (!$token->is([T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED, T_NAME_RELATIVE])) {
            continue;
        }

        $prev = pn_check_prev($tokens, $i);
        $next = pn_check_next($tokens, $i);
        if ($prev !== null && $prev->is([T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR, T_DOUBLE_COLON, T_FUNCTION, T_CONST, T_USE, T_NAMESPACE, T_ATTRIBUTE, T_CLASS, T_INTERFACE, T_TRAIT, T_ENUM, T_GOTO])) {
            continue;
        }

        $isClassReference = ($prev !== null && $prev->is([T_NEW, T_INSTANCEOF])) || ($next !== null && $next->is(T_DOUBLE_COLON));
        if ($isClassReference) {
            if (in_array(strtolower($token->text), ['self', 'static', 'parent'], true)) {
                continue;
            }
            $class = pn_check_class_name($token->text, $namespace, $uses['class']);
            if (!class_exists($class) && !interface_exists($class) && !enum_exists($class) && !trait_exists($class)) {
                $found[] = ['file' => $relative, 'line' => $token->line, 'kind' => 'Klasse', 'name' => $class];
            }
            continue;
        }

        if ($next === null || $next->text !== '(') {
            continue;
        }

        $name = $token->text;
        if (str_starts_with($name, '\\')) {
            $candidates = [substr($name, 1)];
        } elseif (!str_contains($name, '\\') && isset($uses['function'][strtolower($name)])) {
            $candidates = [$uses['function'][strtolower($name)]];
        } elseif (str_contains($name, '\\')) {
            $candidates = [pn_check_class_name($name, $namespace, $uses['class'])];
        } else {
            // Unqualifiziert im Namensraum: erst Namensraum\name, dann global
            $candidates = array_unique([ltrim($namespace . '\\' . $name, '\\'), $name]);
        }

        if (array_filter($candidates, 'function_exists') === []) {
            $found[] = ['file' => $relative, 'line' => $token->line, 'kind' => 'Funktion', 'name' => end($candidates)];
        }
    }

    return $found;
}

$pnCheckQueue = [];
foreach (array_merge($pnCheckEntries, $pnCheckExtra) as $file) {
    $path = realpath($file);
    if ($path === false) {
        fwrite(STDERR, 'Datei fehlt: ' . $file . "\n");
        exit(2);
    }
    $pnCheckQueue[] = $path;
}

$pnCheckDeclared = pn_check_declarations($pnCheckRoot);
$pnCheckDone = [];
$pnCheckFindings = [];

/*
 * Arbeitsliste: Einstiegsdateien und alle eingebundenen Projektdateien. Klassen, die beim
 * Prüfen per Autoloader nachgeladen werden, kommen dabei ebenfalls an die Reihe.
 */
while ($pnCheckQueue !== []) {
    $file = array_shift($pnCheckQueue);
    if (isset($pnCheckDone[$file])) {
        continue;
    }
    $pnCheckDone[$file] = true;

    foreach (pn_check_file($file, $pnCheckRoot) as $finding) {
        $finding['declaredIn'] = $pnCheckDeclared[strtolower($finding['name'])] ?? null;
        $pnCheckFindings[] = $finding;
    }

    foreach (get_included_files() as $included) {
        $included = (string) realpath($included);
        $inProject = str_starts_with($included, $pnCheckRoot . DIRECTORY_SEPARATOR)
            && !str_starts_with($included, $pnCheckRoot . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR)
            && !str_starts_with($included, $pnCheckRoot . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR);
        if ($inProject && !isset($pnCheckDone[$included])) {
            $pnCheckQueue[] = $included;
        }
    }
}

echo json_encode(['checked' => count($pnCheckDone), 'findings' => $pnCheckFindings], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
