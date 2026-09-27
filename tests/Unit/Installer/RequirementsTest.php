<?php

declare(strict_types=1);

namespace PowerNews\Tests\Unit\Installer;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PowerNews\Installer\Requirements;

require_once __DIR__ . '/../../../pninc/installer/autoload.php';

/**
 * Schritt 1: Systemprüfung.
 */
final class RequirementsTest extends TestCase
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
     * @param list<array{id: string, label: string, ok: bool, required: bool, detail: string}> $checks
     *
     * @return array<string, array{id: string, label: string, ok: bool, required: bool, detail: string}>
     */
    private static function byId(array $checks): array
    {
        return array_column($checks, null, 'id');
    }

    #[Test]
    public function allRequirementsMetOnAPreparedServer(): void
    {
        $checks = Requirements::check($this->root, ['HTTPS' => 'on'], '8.4.0', static fn (string $ext): bool => true);

        $this->assertTrue(Requirements::allRequiredMet($checks));
        $this->assertSame(
            ['php', 'mysqli', 'mbstring', 'schema', 'logs', 'pninc', 'dbserver', 'https'],
            array_column($checks, 'id'),
        );
        foreach ($checks as $check) {
            $this->assertTrue($check['ok'], $check['id']);
        }
    }

    #[Test]
    public function oldPhpOrMissingExtensionBlocksTheInstallation(): void
    {
        $checks = self::byId(Requirements::check($this->root, [], '8.3.12', static fn (string $ext): bool => $ext !== 'mysqli'));

        $this->assertFalse($checks['php']['ok']);
        $this->assertTrue($checks['php']['required']);
        $this->assertFalse($checks['mysqli']['ok']);
        $this->assertTrue($checks['mbstring']['ok']);
        $this->assertFalse(Requirements::allRequiredMet(array_values($checks)));
    }

    #[Test]
    public function readOnlyPnincIsOnlyAHintButMissingLogsBlocks(): void
    {
        rmdir($this->root . '/pninc');
        $checks = self::byId(Requirements::check($this->root, [], PHP_VERSION, static fn (string $ext): bool => true));

        $this->assertFalse($checks['pninc']['ok']);
        $this->assertFalse($checks['pninc']['required'], 'Ohne pninc/-Schreibrecht gibt es config.local.php zum Herunterladen');
        $this->assertStringContainsString('Herunterladen', $checks['pninc']['detail']);
        $this->assertTrue(Requirements::allRequiredMet(array_values($checks)));

        rmdir($this->root . '/logs');
        $checks = Requirements::check($this->root, [], PHP_VERSION, static fn (string $ext): bool => true);
        $this->assertFalse(Requirements::allRequiredMet($checks));
    }

    #[Test]
    public function missingSchemaFileBlocks(): void
    {
        unlink($this->root . '/powernews.sql');
        $checks = self::byId(Requirements::check($this->root, [], PHP_VERSION, static fn (string $ext): bool => true));

        $this->assertFalse($checks['schema']['ok']);
        $this->assertFalse(Requirements::allRequiredMet(array_values($checks)));
    }

    #[Test]
    public function existingLocalConfigMeansTheInstallerMayNotWriteIt(): void
    {
        $this->assertTrue(Requirements::canWriteLocalConfig($this->root));
        file_put_contents($this->root . '/pninc/config.local.php', '<?php return [];');
        $this->assertFalse(Requirements::canWriteLocalConfig($this->root));
    }

    #[Test]
    public function phpVersionComparisonUsesVersionCompare(): void
    {
        $this->assertTrue(Requirements::phpVersionOk('8.4.0'));
        $this->assertTrue(Requirements::phpVersionOk('8.10.0'), 'Kein Zeichenkettenvergleich wie in 3.11');
        $this->assertFalse(Requirements::phpVersionOk('8.3.99'));
    }

    #[Test]
    public function httpsIsDetectedFromServerVariables(): void
    {
        $this->assertTrue(Requirements::isHttps(['HTTPS' => 'on']));
        $this->assertTrue(Requirements::isHttps(['SERVER_PORT' => '443']));
        $this->assertTrue(Requirements::isHttps(['SERVER_PORT' => 443]));
        $this->assertFalse(Requirements::isHttps(['HTTPS' => 'off']));
        $this->assertFalse(Requirements::isHttps(['SERVER_PORT' => '80']));
        $this->assertFalse(Requirements::isHttps([]));
    }
}
