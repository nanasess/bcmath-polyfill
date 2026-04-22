<?php

/**
 * Baseline fixture for issue #56 regression tests.
 *
 * Loads lib/RoundingMode.php without any pre-existing `RoundingMode`
 * symbol and verifies the polyfill enum is declared as expected on
 * PHP 8.1-8.3.
 */
require __DIR__.'/../../lib/RoundingMode.php';

echo enum_exists('RoundingMode') ? "ENUM_DEFINED\n" : "ENUM_MISSING\n";
