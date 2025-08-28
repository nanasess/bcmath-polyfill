<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/lib',
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withPhpSets(php81: true)
    ->withDeadCodeLevel(0)
    ->withCodeQualityLevel(0)
    ->withComposerBased(phpunit: true)
    ->withPreparedSets(typeDeclarations: true);
