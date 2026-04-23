<?php

/**
 * Reproduction fixture for issue #56.
 *
 * Simulates Rector 2.4.x's scoped polyfill that pre-declares a global
 * `class RoundingMode` via `class_alias`, and then loads this package's
 * RoundingMode polyfill. The guard in lib/RoundingMode.php must skip
 * enum declaration to avoid a "Cannot declare enum" fatal error.
 */
class RoundingMode
{
    public const HalfAwayFromZero = 'halfawayfromzero';
    public const HalfTowardsZero = 'halftowardszero';
    public const HalfEven = 'halfeven';
    public const HalfOdd = 'halfodd';
    public const TowardsZero = 'towardszero';
    public const AwayFromZero = 'awayfromzero';
    public const NegativeInfinity = 'negativeinfinity';
    public const PositiveInfinity = 'positiveinfinity';
}

require __DIR__.'/../../lib/RoundingMode.php';

echo "OK\n";
