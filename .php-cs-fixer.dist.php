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
        '@PhpCsFixer' => true,
        'fully_qualified_strict_types' => false,
        'yoda_style' => false,
        'php_unit_data_provider_method_order' => false,
        'phpdoc_align' => false,
        'ordered_class_elements' => false,
        'increment_style' => false,
        '@PhpCsFixer:risky' => true,
        'strict_comparison' => false,
        'native_function_invocation' => false,
        'php_unit_test_case_static_method_calls' => false,
    ])
    ->setFinder($finder)
;
