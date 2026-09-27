<?php

declare(strict_types=1);

namespace PowerNews\Tests\Unit\Installer;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PowerNews\Installer\InstallState;
use PowerNews\LocalConfig;

require_once __DIR__ . '/../../../pninc/installer/autoload.php';

/**
 * Sperrlogik des Installers (Befunde B07 und B47).
 */
final class InstallStateTest extends TestCase
{
    private string $root = '';

    protected function setUp(): void
    {
        parent::setUp();
        $this->root = InstallerFixture::createRoot();
    }

    protected function tearDown(): void
    {
        InstallerFixture::removeRoot($this->root);
        parent::tearDown();
    }

    /**
     * @return iterable<string, array{bool, bool, bool|null, bool, string|null}>
     */
    public static function decisions(): iterable
    {
        yield 'frischer Upload, keine Datenbank' => [false, false, null, true, null];
        yield 'leere Datenbank per Umgebung' => [false, false, false, false, null];
        yield 'leere Datenbank mit Vorgaben' => [false, false, false, true, null];
        yield 'Sperrdatei' => [true, false, null, true, InstallState::REASON_LOCK_FILE];
        yield 'Sperrdatei hat Vorrang' => [true, true, true, false, InstallState::REASON_LOCK_FILE];
        yield 'lokale Konfiguration' => [false, true, null, true, InstallState::REASON_LOCAL_CONFIG];
        yield 'Konfigurationszeile in der DB' => [false, false, true, true, InstallState::REASON_DATABASE];
        yield 'DB per Umgebung nicht erreichbar' => [false, false, null, false, InstallState::REASON_UNREACHABLE];
    }

    #[Test]
    #[DataProvider('decisions')]
    public function lockReasonCoversAllCriteria(bool $lockFile, bool $localConfig, ?bool $database, bool $defaults, ?string $expected): void
    {
        $this->assertSame($expected, InstallState::lockReason($lockFile, $localConfig, $database, $defaults));
    }

    #[Test]
    public function freshUploadIsNotLocked(): void
    {
        $this->assertNull(InstallState::detectLockReason($this->root, LocalConfig::DEFAULT_DB, static fn (): ?bool => null));
    }

    #[Test]
    public function lockFileInPnincOrLogsLocksWithoutAskingTheDatabase(): void
    {
        foreach (InstallState::LOCK_FILES as $lockFile) {
            $root = InstallerFixture::createRoot();
            file_put_contents($root . '/' . $lockFile, 'x');

            $probe = function (): ?bool {
                $this->fail('Die Datenbank darf nicht befragt werden, wenn eine Datei die Sperre belegt.');
            };

            $this->assertSame(InstallState::REASON_LOCK_FILE, InstallState::detectLockReason($root, LocalConfig::DEFAULT_DB, $probe));
            $this->assertSame($lockFile, InstallState::existingLockFile($root));
            InstallerFixture::removeRoot($root);
        }
    }

    #[Test]
    public function localConfigLocks(): void
    {
        file_put_contents($this->root . '/' . LocalConfig::RELATIVE_PATH, "<?php\nreturn [];\n");

        $this->assertSame(InstallState::REASON_LOCAL_CONFIG, InstallState::detectLockReason($this->root, LocalConfig::DEFAULT_DB, static fn (): ?bool => null));
    }

    #[Test]
    public function configRowInTheDatabaseLocks(): void
    {
        $this->assertSame(InstallState::REASON_DATABASE, InstallState::detectLockReason($this->root, LocalConfig::DEFAULT_DB, static fn (): ?bool => true));
    }

    #[Test]
    public function unreachableConfiguredDatabaseLocks(): void
    {
        $db = ['host' => 'db', 'port' => 3306, 'user' => 'powernews', 'password' => 'x', 'database' => 'powernews'];

        $this->assertSame(InstallState::REASON_UNREACHABLE, InstallState::detectLockReason($this->root, $db, static fn (): ?bool => null));
        $this->assertNull(InstallState::detectLockReason($this->root, $db, static fn (): ?bool => false), 'Erreichbar und leer: Installation erlaubt');
    }

    #[Test]
    public function lockFileIsWrittenToPnincFirst(): void
    {
        $this->assertSame('pninc/install.lock', InstallState::lockTarget($this->root));
        $this->assertSame('pninc/install.lock', InstallState::writeLockFile($this->root, '2026-09-28 10:00:00'));
        $this->assertStringContainsString('2026-09-28 10:00:00', (string) file_get_contents($this->root . '/pninc/install.lock'));
    }

    #[Test]
    public function lockFileFallsBackToLogsWhenPnincIsMissing(): void
    {
        rmdir($this->root . '/pninc');

        $this->assertSame('logs/install.lock', InstallState::lockTarget($this->root));
        $this->assertSame('logs/install.lock', InstallState::writeLockFile($this->root, '2026-09-28 10:00:00'));
        $this->assertFileExists($this->root . '/logs/install.lock');
    }

    #[Test]
    public function missingLockLocationIsReportedInsteadOfSilentlyContinuing(): void
    {
        rmdir($this->root . '/pninc');
        rmdir($this->root . '/logs');

        $this->assertNull(InstallState::lockTarget($this->root));
        $this->assertNull(InstallState::writeLockFile($this->root, '2026-09-28 10:00:00'));
    }
}
