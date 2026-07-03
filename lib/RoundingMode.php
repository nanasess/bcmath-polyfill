<?php

/**
 * RoundingMode enum polyfill for PHP 8.1-8.3.
 *
 * This enum mirrors PHP 8.4's native RoundingMode, which is a *pure* enum
 * (no backing type). Keeping it backing-less here ensures the polyfill behaves
 * identically to the native enum on 8.4+ (e.g. no `->value`, `from()`/`tryFrom()`),
 * avoiding an 8.1-8.3 vs. 8.4+ inconsistency.
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
    enum RoundingMode
    {
        case HalfAwayFromZero;
        case HalfTowardsZero;
        case HalfEven;
        case HalfOdd;
        case TowardsZero;
        case AwayFromZero;
        case NegativeInfinity;
        case PositiveInfinity;
    }
}
