<?php

$finder = (new PhpCsFixer\Finder())
              ->in(__DIR__.'/src')
              ->in(__DIR__.'/lib')
              ->in(__DIR__.'/tests')
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@PER-CS' => true,
        '@PHP81Migration' => true,
        '@PHPUnit100Migration:risky' => true,
    ])
    ->setFinder($finder)
;
