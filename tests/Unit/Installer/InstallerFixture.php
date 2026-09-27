<?php

declare(strict_types=1);

namespace PowerNews\Tests\Unit\Installer;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Temporäres PowerNews-Verzeichnis für Installer-Tests: pninc/, logs/ und
 * eine Kopie von powernews.sql.
 */
final class InstallerFixture
{
    public static function createRoot(): string
    {
        $root = sys_get_temp_dir() . '/pn_installer_' . bin2hex(random_bytes(6));
        mkdir($root . '/pninc', 0o777, true);
        mkdir($root . '/logs');
        copy(__DIR__ . '/../../../powernews.sql', $root . '/powernews.sql');

        return $root;
    }

    public static function removeRoot(string $root): void
    {
        if (!is_dir($root)) {
            return;
        }

        $items = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        /** @var SplFileInfo $item */
        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }

        rmdir($root);
    }
}
