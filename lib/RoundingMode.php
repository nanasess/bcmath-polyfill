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
 * - Native RoundingMode enum doesn't already exist (PHP < 8.4)
 */
// @phpstan-ignore-next-line
if (!enum_exists('RoundingMode') && version_compare(PHP_VERSION, '8.1', '>=')) {
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
