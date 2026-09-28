<?php

declare(strict_types=1);

/* PowerNews - PHP and MySQL based news script                          */
/* Copyright (c) 2001-2026 PowerScripts                                 */

/* MIT License - See LICENSE file for full license text                 */
/* https://github.com/schubertnico/PowerNews.git                        */

namespace PowerNews\Installer;

use PowerNews\LocalConfig;

/**
 * Einstieg des Web-Installers (aufgerufen von install.php).
 *
 * Verbindet Session, Controller und Ausgabe. Die eigentliche Logik steckt in
 * Controller und den übrigen Klassen dieses Verzeichnisses.
 */
final class Installer
{
    public const string SESSION_NAME = 'PNINSTALL';

    public static function run(string $rootDir): never
    {
        ini_set('display_errors', '0');
        ini_set('log_errors', '1');
        ini_set('error_log', $rootDir . '/logs/php-error.log');

        self::sendHeaders();
        self::startSession();

        require_once $rootDir . '/pninc/validation.inc.php';
        $csrfToken = pn_csrf_token();

        $wizard = Wizard::fromSession($_SESSION[Wizard::SESSION_KEY] ?? null);

        // Für die Sperre zählt der Zugang aus Umgebungsvariablen bzw. Vorgaben;
        // gibt es config.local.php, ist der Installer ohnehin gesperrt.
        $db = LocalConfig::fromEnvironment(static fn (string $name): string|false => getenv($name));

        $controller = new Controller(
            $rootDir,
            $wizard,
            $csrfToken,
            $db,
            static fn (): ?bool => self::probe($db),
            DatabaseSetup::connect(...),
        );

        try {
            $method = is_string($_SERVER['REQUEST_METHOD'] ?? null) ? $_SERVER['REQUEST_METHOD'] : 'GET';
            $response = $controller->handle($method, $_GET, $_POST, $_SERVER);
        } catch (\Throwable $e) {
            error_log('PowerNews-Installer: unerwarteter Fehler ' . $e::class . ' in ' . basename($e->getFile()) . ':' . $e->getLine());
            self::fail();
        }

        foreach ($controller->events() as $event) {
            error_log('PowerNews-Installer: ' . $event);
        }

        $_SESSION[Wizard::SESSION_KEY] = $wizard->toSession();

        if ($response->renewSession) {
            session_regenerate_id(true);
            unset($_SESSION['pn_csrf_token']);
        }

        self::emit($response, $wizard);
    }

    /**
     * Prüft die konfigurierte Datenbank für die Sperrlogik.
     *
     * @param array{host: string, port: int, user: string, password: string, database: string} $db
     */
    private static function probe(#[\SensitiveParameter] array $db): ?bool
    {
        try {
            $mysqli = DatabaseSetup::connect($db);
            $installed = DatabaseSetup::hasConfigRow($mysqli);
            $mysqli->close();

            return $installed;
        } catch (\mysqli_sql_exception) {
            return null;
        }
    }

    private static function emit(Response $response, Wizard $wizard): never
    {
        if ($response->kind === Response::REDIRECT) {
            header('Location: ' . $response->location, true, 303);
            exit;
        }

        if ($response->kind === Response::DOWNLOAD) {
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $response->title . '"');
            header('Content-Length: ' . strlen($response->content));
            echo $response->content;
            exit;
        }

        http_response_code($response->status);

        try {
            View::render($response->template, $response->title, 'Installation', $response->step, $wizard->completed(), $response->args);
        } catch (\Throwable $e) {
            error_log('PowerNews-Installer: Vorlage nicht darstellbar (' . $e->getMessage() . ')');
            self::fail();
        }

        exit;
    }

    private static function fail(): never
    {
        if (!headers_sent()) {
            http_response_code(500);
        }
        echo '<!DOCTYPE html><html lang="de"><head><meta charset="UTF-8"><title>Installationsfehler</title></head>'
            . '<body style="font-family:system-ui,sans-serif;max-width:40rem;margin:3rem auto;padding:0 1rem">'
            . '<h1>Unerwarteter Fehler</h1><p>Der Installer konnte die Anfrage nicht verarbeiten. Details stehen in '
            . '<code>logs/php-error.log</code>. Bitte laden Sie die Dateien von PowerNews vollständig neu hoch und versuchen Sie es erneut.</p>'
            . '</body></html>';
        exit;
    }

    private static function sendHeaders(): void
    {
        header('Content-Type: text/html; charset=UTF-8');
        header('Cache-Control: no-store, max-age=0');
        header('X-Frame-Options: DENY');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: no-referrer');
        header('X-Robots-Tag: noindex, nofollow');
    }

    private static function startSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_name(self::SESSION_NAME);
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => Requirements::isHttps($_SERVER),
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
        session_start();
    }
}
