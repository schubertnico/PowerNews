<?php

declare(strict_types=1);

/* PowerNews - PHP and MySQL based news script                          */
/* Copyright (c) 2001-2026 PowerScripts                                 */

/* MIT License - See LICENSE file for full license text                 */
/* https://github.com/schubertnico/PowerNews.git                        */

namespace PowerNews\Installer;

use PowerNews\LocalConfig;

/**
 * Fortschritt des Installers in der Session.
 *
 * Gespeichert werden nur die Eingaben, die der letzte Schritt braucht. Das
 * Administrator-Passwort liegt ausschließlich als Hash vor; das
 * Datenbankpasswort wird nach Abschluss aus der Session entfernt.
 *
 * @phpstan-import-type DbConfig from LocalConfig
 * @phpstan-import-type WebsiteSettings from FormValidator
 * @phpstan-import-type AdminData from DatabaseSetup
 *
 * @phpstan-type DoneInfo array{config_written: bool, config_source: string, lock_file: string, admin_nickname: string, site_url: string}
 * @phpstan-type Notice array{type: string, message: string}
 */
final class Wizard
{
    public const string SESSION_KEY = 'pn_installer';

    public const int STEP_REQUIREMENTS = 1;

    public const int STEP_DATABASE = 2;

    public const int STEP_WEBSITE = 3;

    public const int STEP_ADMIN = 4;

    public const int STEP_FINISH = 5;

    public const array STEPS = [
        self::STEP_REQUIREMENTS => 'Systemprüfung',
        self::STEP_DATABASE => 'Datenbank',
        self::STEP_WEBSITE => 'Website',
        self::STEP_ADMIN => 'Administrator',
        self::STEP_FINISH => 'Abschluss',
    ];

    /**
     * POST-Aktionen (Formularfeld „action“) und der Schritt, zu dem sie gehören.
     */
    public const array ACTIONS = [
        'requirements' => self::STEP_REQUIREMENTS,
        'database' => self::STEP_DATABASE,
        'website' => self::STEP_WEBSITE,
        'admin' => self::STEP_ADMIN,
        'finish' => self::STEP_FINISH,
    ];

    private const array NOTICE_TYPES = ['success', 'danger', 'warning', 'info'];

    private int $completed = 0;

    /** @var DbConfig|null */
    private ?array $database = null;

    private string $serverLabel = '';

    /** @var WebsiteSettings|null */
    private ?array $website = null;

    /** @var AdminData|null */
    private ?array $admin = null;

    /** @var DoneInfo|null */
    private ?array $done = null;

    /** @var Notice|null einmalige Meldung nach einer Weiterleitung */
    private ?array $notice = null;

    /**
     * Stellt den Zustand aus der Session wieder her. Unvollständige oder
     * manipulierte Daten führen zu einem früheren Schritt, nie zu einem Fehler.
     */
    public static function fromSession(#[\SensitiveParameter] mixed $data): self
    {
        $wizard = new self();

        if (!is_array($data)) {
            return $wizard;
        }

        $completed = $data['completed'] ?? 0;
        $wizard->completed = is_int($completed) ? max(0, min($completed, self::STEP_FINISH)) : 0;
        $wizard->database = self::parseDatabase($data['database'] ?? null);
        $serverLabel = $data['server'] ?? '';
        $wizard->serverLabel = is_string($serverLabel) ? $serverLabel : '';
        $wizard->website = self::parseWebsite($data['website'] ?? null);
        $wizard->admin = self::parseAdmin($data['admin'] ?? null);
        $wizard->done = self::parseDone($data['done'] ?? null);
        $wizard->notice = self::parseNotice($data['notice'] ?? null);

        return $wizard;
    }

    /**
     * @return array{completed: int, database: DbConfig|null, server: string, website: WebsiteSettings|null, admin: AdminData|null, done: DoneInfo|null, notice: Notice|null}
     */
    public function toSession(): array
    {
        return [
            'completed' => $this->completed,
            'database' => $this->database,
            'server' => $this->serverLabel,
            'website' => $this->website,
            'admin' => $this->admin,
            'done' => $this->done,
            'notice' => $this->notice,
        ];
    }

    /**
     * Höchster Schritt, dessen Daten vollständig vorliegen.
     */
    public function completed(): int
    {
        $completed = $this->completed;

        if ($this->database === null) {
            $completed = min($completed, self::STEP_REQUIREMENTS);
        }

        if ($this->website === null) {
            $completed = min($completed, self::STEP_DATABASE);
        }

        if ($this->admin === null) {
            $completed = min($completed, self::STEP_WEBSITE);
        }

        return $completed;
    }

    /**
     * Begrenzt einen angefragten Schritt auf die bereits erreichbaren.
     */
    public function allowedStep(int $requested): int
    {
        $maxStep = min($this->completed() + 1, self::STEP_FINISH);

        return max(self::STEP_REQUIREMENTS, min($requested, $maxStep));
    }

    public function canEnter(int $step): bool
    {
        return $this->allowedStep($step) === $step;
    }

    public function completeRequirements(): void
    {
        $this->completed = max($this->completed, self::STEP_REQUIREMENTS);
    }

    /**
     * @param DbConfig $database
     */
    public function storeDatabase(#[\SensitiveParameter] array $database, string $serverLabel): void
    {
        $this->database = $database;
        $this->serverLabel = $serverLabel;
        $this->completed = max($this->completed, self::STEP_DATABASE);
    }

    /**
     * @param WebsiteSettings $website
     */
    public function storeWebsite(array $website): void
    {
        $this->website = $website;
        $this->completed = max($this->completed, self::STEP_WEBSITE);
    }

    public function storeAdmin(string $nickname, string $email, #[\SensitiveParameter] string $passwordHash): void
    {
        $this->admin = ['nickname' => $nickname, 'email' => $email, 'password_hash' => $passwordHash];
        $this->completed = max($this->completed, self::STEP_ADMIN);
    }

    /**
     * Schließt die Installation ab und verwirft alle Zugangsdaten. Nur wenn
     * config.local.php von Hand angelegt werden muss, bleibt ihr Inhalt bis
     * dahin in der Session.
     *
     * @param string $lockFile relativer Pfad der Sperrdatei, leer wenn sie fehlt
     */
    public function finish(bool $configWritten, #[\SensitiveParameter] string $configSource, string $lockFile): void
    {
        $this->done = [
            'config_written' => $configWritten,
            'config_source' => $configWritten ? '' : $configSource,
            'lock_file' => $lockFile,
            'admin_nickname' => $this->admin['nickname'] ?? '',
            'site_url' => $this->website['url'] ?? '',
        ];
        $this->completed = self::STEP_FINISH;
        $this->database = null;
        $this->website = null;
        $this->admin = null;
        $this->notice = null;
    }

    /**
     * Die von Hand angelegte config.local.php liegt inzwischen vor – ihr
     * Inhalt wird in der Session nicht mehr gebraucht.
     */
    public function forgetConfigSource(): void
    {
        if ($this->done !== null) {
            $this->done['config_source'] = '';
        }
    }

    /**
     * Meldung für die nächste Seite (nach der Weiterleitung).
     */
    public function setNotice(string $type, string $message): void
    {
        $this->notice = ['type' => in_array($type, self::NOTICE_TYPES, true) ? $type : 'info', 'message' => $message];
    }

    /**
     * Liefert die Meldung einmal und vergisst sie dann.
     *
     * @return Notice|null
     */
    public function takeNotice(): ?array
    {
        $notice = $this->notice;
        $this->notice = null;

        return $notice;
    }

    /**
     * @return DbConfig|null
     */
    public function database(): ?array
    {
        return $this->database;
    }

    /**
     * Erkannter Datenbankserver, z. B. „MariaDB 10.11.15“.
     */
    public function serverLabel(): string
    {
        return $this->serverLabel;
    }

    /**
     * @return WebsiteSettings|null
     */
    public function website(): ?array
    {
        return $this->website;
    }

    /**
     * @return AdminData|null
     */
    public function admin(): ?array
    {
        return $this->admin;
    }

    /**
     * @return DoneInfo|null
     */
    public function done(): ?array
    {
        return $this->done;
    }

    /**
     * Vorschlag für die Adresse der Website aus der aktuellen Anfrage, z. B.
     * https://example.com/news für /news/install.php.
     *
     * @param array<array-key, mixed> $server $_SERVER
     */
    public static function suggestSiteUrl(array $server): string
    {
        $host = is_string($server['HTTP_HOST'] ?? null) ? $server['HTTP_HOST'] : '';

        if (preg_match('/^[A-Za-z0-9.-]+(?::\d{1,5})?$|^\[[0-9A-Fa-f:.]+\](?::\d{1,5})?$/', $host) !== 1) {
            return '';
        }

        $scheme = Requirements::isHttps($server) ? 'https' : 'http';
        $script = is_string($server['SCRIPT_NAME'] ?? null) ? $server['SCRIPT_NAME'] : '/install.php';
        $path = rtrim(str_replace('\\', '/', dirname($script)), '/');

        if (preg_match('#^[A-Za-z0-9._~/%-]*$#', $path) !== 1) {
            $path = '';
        }

        return $scheme . '://' . $host . $path;
    }

    /**
     * Vorschlag für die Absenderadresse: noreply@<Domain der Website>.
     */
    public static function suggestSender(string $siteUrl): string
    {
        $host = parse_url($siteUrl, PHP_URL_HOST);

        if (!is_string($host) || $host === '') {
            return '';
        }

        $candidate = 'noreply@' . (str_starts_with(strtolower($host), 'www.') ? substr($host, 4) : $host);

        return filter_var($candidate, FILTER_VALIDATE_EMAIL) !== false ? $candidate : '';
    }

    /**
     * @return DbConfig|null
     */
    private static function parseDatabase(mixed $data): ?array
    {
        if (!is_array($data) || !is_int($data['port'] ?? null)) {
            return null;
        }

        $strings = self::strings($data, ['host', 'user', 'password', 'database']);

        if ($strings === null) {
            return null;
        }

        return [
            'host' => $strings['host'],
            'port' => $data['port'],
            'user' => $strings['user'],
            'password' => $strings['password'],
            'database' => $strings['database'],
        ];
    }

    /**
     * @return WebsiteSettings|null
     */
    private static function parseWebsite(mixed $data): ?array
    {
        $strings = is_array($data) ? self::strings($data, ['url', 'email', 'language']) : null;

        if ($strings === null) {
            return null;
        }

        return ['url' => $strings['url'], 'email' => $strings['email'], 'language' => $strings['language']];
    }

    /**
     * @return AdminData|null
     */
    private static function parseAdmin(mixed $data): ?array
    {
        $strings = is_array($data) ? self::strings($data, ['nickname', 'email', 'password_hash']) : null;

        if ($strings === null) {
            return null;
        }

        return ['nickname' => $strings['nickname'], 'email' => $strings['email'], 'password_hash' => $strings['password_hash']];
    }

    /**
     * @return DoneInfo|null
     */
    private static function parseDone(mixed $data): ?array
    {
        if (!is_array($data) || !is_bool($data['config_written'] ?? null)) {
            return null;
        }

        $strings = self::strings($data, ['config_source', 'lock_file', 'admin_nickname', 'site_url']);

        if ($strings === null) {
            return null;
        }

        return [
            'config_written' => $data['config_written'],
            'config_source' => $strings['config_source'],
            'lock_file' => $strings['lock_file'],
            'admin_nickname' => $strings['admin_nickname'],
            'site_url' => $strings['site_url'],
        ];
    }

    /**
     * @return Notice|null
     */
    private static function parseNotice(mixed $data): ?array
    {
        $strings = is_array($data) ? self::strings($data, ['type', 'message']) : null;

        if ($strings === null || !in_array($strings['type'], self::NOTICE_TYPES, true)) {
            return null;
        }

        return ['type' => $strings['type'], 'message' => $strings['message']];
    }

    /**
     * Liefert die angegebenen Schlüssel, wenn alle Zeichenketten sind.
     *
     * @param array<array-key, mixed> $data
     * @param list<string> $keys
     *
     * @return array<string, string>|null
     */
    private static function strings(array $data, array $keys): ?array
    {
        $result = [];

        foreach ($keys as $key) {
            $value = $data[$key] ?? null;

            if (!is_string($value)) {
                return null;
            }
            $result[$key] = $value;
        }

        return $result;
    }
}
