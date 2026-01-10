<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/pnadmin',
        __DIR__ . '/pninc',
        __DIR__ . '/index.php',
        __DIR__ . '/news.php',
        __DIR__ . '/comments.php',
        __DIR__ . '/archive.php',
        __DIR__ . '/user.php',
        __DIR__ . '/sendnews.php',
        __DIR__ . '/convert.php',
        __DIR__ . '/install.php',
        __DIR__ . '/update.php',
        __DIR__ . '/header.inc.php',
        __DIR__ . '/footer.inc.php',
    ])
    ->withSkip([
        __DIR__ . '/vendor',
        __DIR__ . '/.docker',
        __DIR__ . '/logs',
    ])
    ->withPhpSets(php84: true)
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
        earlyReturn: true,
    );
