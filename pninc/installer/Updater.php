<?php

declare(strict_types=1);

/* PowerNews - PHP and MySQL based news script                          */
/* Copyright (c) 2001-2026 PowerScripts                                 */

/* MIT License - See LICENSE file for full license text                 */
/* https://github.com/schubertnico/PowerNews.git                        */

namespace PowerNews\Installer;

/**
 * Datenbankseitige Schritte beim Update einer bestehenden 3.x-Installation
 * auf 3.12 (update.php, Befund B34).
 *
 * Jeder Schritt prüft zuerst am Datenbestand, ob er nötig ist, und ist
 * beliebig oft ausführbar. Es gibt keine gespeicherte Versionsnummer, die
 * falsch sein könnte – maßgeblich ist der tatsächliche Zustand.
 *
 * @phpstan-type StepStatus array{id: string, label: string, detail: string, pending: bool}
 * @phpstan-type StepResult array{id: string, label: string, ok: bool, message: string}
 */
final readonly class Updater
{
    public const string STEP_TABLES = 'tables';

    public const string STEP_TEMPLATES = 'templates';

    public const string STEP_PERMISSIONS = 'permissions';

    public const string STEP_LOCK = 'lock';

    /**
     * Testvorlagen, die 3.11 versehentlich ausgeliefert hat (Befund B18).
     * Gelöscht wird nur bei passender id UND passendem Titel.
     */
    public const array JUNK_TEMPLATES = [2 => 'ftghgf', 3 => 'dsfs', 4 => 'dfgdfg'];

    /**
     * @param list<string> $statements Anweisungen aus powernews.sql
     */
    public function __construct(
        private \mysqli $mysqli,
        private string $rootDir,
        private array $statements,
    ) {
    }

    /**
     * Zustand aller Schritte.
     *
     * @return list<StepStatus>
     */
    public function status(): array
    {
        $missing = $this->missingTables();
        $junk = $this->junkTemplates();
        $orphans = $this->orphanPermissionCount();
        $lockFile = InstallState::existingLockFile($this->rootDir);

        return [
            [
                'id' => self::STEP_TABLES,
                'label' => 'Fehlende Tabellen anlegen',
                'detail' => $missing === []
                    ? 'Alle Tabellen aus powernews.sql sind vorhanden.'
                    : 'Es fehlen: ' . implode(', ', $missing) . '. Sie werden samt Grunddaten angelegt; bestehende Tabellen bleiben unverändert.',
                'pending' => $missing !== [],
            ],
            [
                'id' => self::STEP_TEMPLATES,
                'label' => 'Testvorlagen aus 3.11 entfernen',
                'detail' => $junk === []
                    ? 'Keine der versehentlich ausgelieferten Vorlagen „ftghgf“, „dsfs“, „dfgdfg“ vorhanden.'
                    : 'Gefunden: ' . implode(', ', $junk) . '. Gelöscht wird nur, was nicht als Standard-Template eingestellt ist.',
                'pending' => $junk !== [],
            ],
            [
                'id' => self::STEP_PERMISSIONS,
                'label' => 'Verwaiste Rechte-Einträge entfernen',
                'detail' => $orphans === 0
                    ? 'Jeder Eintrag in pn_permissions gehört zu einem vorhandenen Benutzer.'
                    : self::entries($orphans) . ' in pn_permissions ohne Benutzer (3.11 lieferte einen für die nicht vorhandene Benutzer-ID 1 aus).',
                'pending' => $orphans > 0,
            ],
            [
                'id' => self::STEP_LOCK,
                'label' => 'Web-Installer sperren',
                'detail' => $lockFile === null
                    ? 'Legt ' . (InstallState::lockTarget($this->rootDir) ?? InstallState::LOCK_FILES[0]) . ' an, damit install.php auch ohne config.local.php gesperrt bleibt.'
                    : 'Sperrdatei ' . $lockFile . ' vorhanden.',
                'pending' => $lockFile === null,
            ],
        ];
    }

    /**
     * Führt alle nötigen Schritte aus.
     *
     * @return list<StepResult>
     */
    public function run(string $timestamp): array
    {
        $results = [];

        foreach ($this->status() as $step) {
            if (!$step['pending']) {
                continue;
            }

            try {
                $message = match ($step['id']) {
                    self::STEP_TABLES => $this->createMissingTables(),
                    self::STEP_TEMPLATES => $this->deleteJunkTemplates(),
                    self::STEP_PERMISSIONS => $this->deleteOrphanPermissions(),
                    default => $this->writeLock($timestamp),
                };
                $results[] = ['id' => $step['id'], 'label' => $step['label'], 'ok' => true, 'message' => $message];
            } catch (\mysqli_sql_exception $e) {
                $results[] = ['id' => $step['id'], 'label' => $step['label'], 'ok' => false, 'message' => DatabaseSetup::friendlyError($e->getCode())];
            } catch (\UnexpectedValueException $e) {
                $results[] = ['id' => $step['id'], 'label' => $step['label'], 'ok' => false, 'message' => $e->getMessage()];
            }
        }

        return $results;
    }

    /**
     * Tabellen aus powernews.sql, die in der Datenbank fehlen.
     *
     * @return list<string>
     */
    public function missingTables(): array
    {
        return array_values(array_diff(Schema::tableNames($this->statements), DatabaseSetup::existingTables($this->mysqli)));
    }

    /**
     * Titel der vorhandenen Testvorlagen, die nicht als Standard eingestellt sind.
     *
     * @return list<string>
     */
    public function junkTemplates(): array
    {
        $active = (int) ($this->firstValue('SELECT template FROM pn_config LIMIT 1') ?? 0);

        try {
            $result = $this->mysqli->query('SELECT id, title FROM pn_templates WHERE id IN (2, 3, 4) ORDER BY id');
        } catch (\mysqli_sql_exception) {
            // Tabelle fehlt: Das erledigt zuerst der Schritt „Fehlende Tabellen anlegen“.
            return [];
        }

        if (!$result instanceof \mysqli_result) {
            return [];
        }

        $junk = [];

        while (($row = $result->fetch_assoc()) !== null && $row !== false) {
            $id = (int) ($row['id'] ?? 0);

            if ($id !== $active && (self::JUNK_TEMPLATES[$id] ?? null) === ($row['title'] ?? null)) {
                $junk[] = self::JUNK_TEMPLATES[$id];
            }
        }

        return $junk;
    }

    public function orphanPermissionCount(): int
    {
        return (int) ($this->firstValue(
            'SELECT COUNT(*) FROM pn_permissions p LEFT JOIN pn_users u ON u.id = p.userid WHERE u.id IS NULL',
        ) ?? 0);
    }

    private function createMissingTables(): string
    {
        Schema::assertInstallable($this->statements);
        $missing = $this->missingTables();

        foreach ($this->statements as $statement) {
            $table = Schema::createdTable($statement) ?? Schema::insertedTable($statement);

            if ($table !== null && in_array($table, $missing, true)) {
                $this->mysqli->query($statement);
            }
        }

        return 'Angelegt: ' . implode(', ', $missing) . '.';
    }

    private function deleteJunkTemplates(): string
    {
        $deleted = [];

        foreach ($this->junkTemplates() as $title) {
            $id = (int) array_search($title, self::JUNK_TEMPLATES, true);
            $stmt = $this->mysqli->prepare('DELETE FROM pn_templates WHERE id = ? AND title = ?');

            if ($stmt === false) {
                throw new \UnexpectedValueException('Die Vorlagen konnten nicht gelöscht werden.');
            }
            $stmt->bind_param('is', $id, $title);
            $stmt->execute();
            $stmt->close();
            $deleted[] = $title;
        }

        return 'Gelöscht: ' . implode(', ', $deleted) . '.';
    }

    private function deleteOrphanPermissions(): string
    {
        $this->mysqli->query('DELETE p FROM pn_permissions p LEFT JOIN pn_users u ON u.id = p.userid WHERE u.id IS NULL');

        return self::entries((int) $this->mysqli->affected_rows) . ' entfernt.';
    }

    private function writeLock(string $timestamp): string
    {
        $lockFile = InstallState::writeLockFile($this->rootDir, $timestamp);

        if ($lockFile === null) {
            throw new \UnexpectedValueException(
                'Die Sperrdatei konnte weder in pninc/ noch in logs/ angelegt werden. Bitte legen Sie pninc/install.lock von Hand an oder löschen Sie install.php.',
            );
        }

        return 'Angelegt: ' . $lockFile . '.';
    }

    /**
     * „1 Eintrag“ bzw. „3 Einträge“.
     */
    private static function entries(int $count): string
    {
        return $count . ($count === 1 ? ' Eintrag' : ' Einträge');
    }

    /**
     * Erster Wert der ersten Zeile; null, wenn die Abfrage scheitert (z. B.
     * weil eine Tabelle noch fehlt).
     */
    private function firstValue(string $sql): ?string
    {
        try {
            $result = $this->mysqli->query($sql);
        } catch (\mysqli_sql_exception) {
            return null;
        }

        $row = $result instanceof \mysqli_result ? $result->fetch_row() : null;
        $value = is_array($row) ? ($row[0] ?? null) : null;

        return is_scalar($value) ? (string) $value : null;
    }
}
