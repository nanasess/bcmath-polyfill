<?php

/**
 * RoundingMode enum polyfill for PHP 8.1-8.3.
 *
 * This enum provides the same interface as PHP 8.4's native RoundingMode enum,
 * but TowardsZero, AwayFromZero, NegativeInfinity, and PositiveInfinity will throw exceptions
 * when used in PHP < 8.4 to maintain compatibility expectations.
 *
 * The enum is only defined if:
 * - PHP version is 8.1 or higher (enum support)
 * - No class/enum named RoundingMode is already declared in the global namespace.
 *   Since PHP 8.1 `class_exists()` returns true for enums too, this single check
 *   covers both the native PHP 8.4+ RoundingMode enum and the Rector 2.4+
 *   scoped polyfill that exposes a class via class_alias.
 */
// The version_compare check is defensive runtime safety. composer.json constrains
// PHP to >=8.1, so PHPStan always infers this as always-true; silence that.
// @phpstan-ignore-next-line booleanAnd.rightAlwaysTrue
if (!class_exists('RoundingMode', false) && version_compare(PHP_VERSION, '8.1', '>=')) {
    enum RoundingMode: string
    {
        case HalfAwayFromZero = 'half_away_from_zero';
        case HalfTowardsZero = 'half_towards_zero';
        case HalfEven = 'half_even';
        case HalfOdd = 'half_odd';
        case TowardsZero = 'towards_zero';
        case AwayFromZero = 'away_from_zero';
        case NegativeInfinity = 'negative_infinity';
        case PositiveInfinity = 'positive_infinity';
    }
}
