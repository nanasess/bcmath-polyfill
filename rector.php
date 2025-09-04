<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\If_\UnwrapFutureCompatibleIfPhpVersionRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/lib',
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withPhpSets(php81: true)
    ->withComposerBased(phpunit: true)
    ->withPreparedSets(typeDeclarations: true, deadCode: true, codeQuality: true)
    ->withSkip([
        UnwrapFutureCompatibleIfPhpVersionRector::class,
    ]);
