<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\If_\UnwrapFutureCompatibleIfPhpVersionRector;
use Rector\TypeDeclaration\Rector\StmtsAwareInterface\SafeDeclareStrictTypesRector;

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
        // Tests rely on implicit int/bool→string coercion to verify native bcmath
        // extension compatibility; declaring strict types would break them.
        SafeDeclareStrictTypesRector::class,
        __DIR__ . '/tests/php-src',
    ]);
