<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPrivateMethodParameterRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPrivateMethodRector;
use Rector\DeadCode\Rector\If_\RemoveAlwaysTrueIfConditionRector;
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
        RemoveAlwaysTrueIfConditionRector::class => [
            __DIR__ . '/tests/BCMathTest.php',
        ],
        UnwrapFutureCompatibleIfPhpVersionRector::class,
        RemoveUnusedPrivateMethodParameterRector::class,
        RemoveUnusedPrivateMethodRector::class,
    ]);
