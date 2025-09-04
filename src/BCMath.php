<?php

/**
 * BCMath Emulation Class.
 *
 * PHP version 5 and 7
 *
 * @author    Jim Wigginton <terrafrost@php.net>
 * @copyright 2019 Jim Wigginton
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 */

namespace bcmath_compat;

use phpseclib3\Math\BigInteger;

/**
 * BCMath Emulation Class.
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 *
 * @method static string add(string $num1, string $num2, int|null $scale = null)
 * @method static string sub(string $num1, string $num2, int|null $scale = null)
 * @method static string mul(string $num1, string $num2, int|null $scale = null)
 * @method static string div(string $num1, string $num2, int|null $scale = null)
 * @method static string mod(string $num1, string $num2, int|null $scale = null)
 * @method static int comp(string $num1, string $num2, int|null $scale = null)
 * @method static string pow(string $num, string $exponent, int|null $scale = null)
 * @method static string powmod(string $base, string $exponent, string $modulus, int|null $scale = null)
 * @method static string sqrt(string $operand, int|null $scale = null)
 * @method static string floor(string $num, int|null $scale = null)
 * @method static string ceil(string $num, int|null $scale = null)
 * @method static string round(string $num, int $precision = 0, int $mode = 1) // $mode default is PHP_ROUND_HALF_UP (1)
 * @method static int scale(int|null $scale = null)
 */
abstract class BCMath
{
    /**
     * Default scale parameter for all bc math functions.
     */
    private static ?int $scale = null;

    /**
     * Set or get default scale parameter for all bc math functions.
     *
     * Uses the PHP 7.3+ behavior
     *
     * @param null|int $scale optional
     */
    public static function scale(?int $scale = null): ?int
    {
        if (func_num_args() > 1) {
            throw new \ArgumentCountError('bcscale() expects at most 1 argument, '.func_num_args().' given');
        }

        if ($scale !== null) {
            self::$scale = $scale;
        }

        return self::$scale;
    }

    /**
     * Formats numbers.
     *
     * Places the decimal place at the appropriate place, adds trailing 0's as appropriate, etc
     *
     * @param null|int $scale
     * @param int $pad
     */
    public static function format(BigInteger $x, $scale, $pad = 0): string
    {
        $sign = self::isNegative($x) ? '-' : '';
        $x = str_replace('-', '', (string) $x);

        if (strlen($x) != $pad) {
            $x = str_pad($x, $pad, '0', STR_PAD_LEFT);
        }
        $temp = $pad ? substr_replace($x, '.', -$pad, 0) : $x;
        $temp = explode('.', $temp);
        if ($temp[0] == '') {
            $temp[0] = '0';
        }
        if (isset($temp[1])) {
            $temp[1] = substr($temp[1], 0, $scale);
            $temp[1] = str_pad($temp[1], $scale, '0');
        } elseif ($scale) {
            $temp[1] = str_repeat('0', $scale);
        }

        return $sign.rtrim(implode('.', $temp), '.');
    }

    /**
     * Negativity Test.
     *
     * @param BigInteger $x
     */
    public static function isNegative($x): bool
    {
        return $x->compare(new BigInteger()) < 0;
    }

    /**
     * Add two arbitrary precision numbers.
     */
    public static function add(string $num1, string $num2, ?int $scale = null): string
    {
        // Handle input validation and type conversion internally
        if (!is_numeric($num1)) {
            $num1 = '0';
        }
        if (!is_numeric($num2)) {
            $num2 = '0';
        }

        // Use default scale if not provided
        if ($scale === null) {
            if (!isset(self::$scale)) {
                $defaultScale = ini_get('bcmath.scale');
                self::$scale = $defaultScale !== false ? max((int) $defaultScale, 0) : 0;
            }
            $scale = self::$scale;
        }

        // Convert to exploded form for decimal processing
        $num1Parts = explode('.', $num1);
        $num2Parts = explode('.', $num2);

        // Ensure both have decimal parts
        if (!isset($num1Parts[1])) {
            $num1Parts[1] = '';
        }
        if (!isset($num2Parts[1])) {
            $num2Parts[1] = '';
        }

        // Pad decimal parts to same length
        $maxPad = max(strlen($num1Parts[1]), strlen($num2Parts[1]));
        $num1Parts[1] = str_pad($num1Parts[1], $maxPad, '0');
        $num2Parts[1] = str_pad($num2Parts[1], $maxPad, '0');

        // Convert to BigInteger for calculation
        $num1Big = new BigInteger($num1Parts[0].$num1Parts[1]);
        $num2Big = new BigInteger($num2Parts[0].$num2Parts[1]);

        $result = $num1Big->add($num2Big);

        $formatted = self::format($result, $scale, $maxPad);

        // Normalize -0.000 to 0.000
        return preg_match('#^-0\.?0*$#', $formatted) ? substr($formatted, 1) : $formatted;
    }

    /**
     * Subtract one arbitrary precision number from another.
     */
    public static function sub(string $num1, string $num2, ?int $scale = null): string
    {
        // Handle input validation and type conversion internally
        if (!is_numeric($num1)) {
            $num1 = '0';
        }
        if (!is_numeric($num2)) {
            $num2 = '0';
        }

        // Use default scale if not provided
        if ($scale === null) {
            if (!isset(self::$scale)) {
                $defaultScale = ini_get('bcmath.scale');
                self::$scale = $defaultScale !== false ? max((int) $defaultScale, 0) : 0;
            }
            $scale = self::$scale;
        }

        // Convert to exploded form for decimal processing
        $num1Parts = explode('.', $num1);
        $num2Parts = explode('.', $num2);

        // Ensure both have decimal parts
        if (!isset($num1Parts[1])) {
            $num1Parts[1] = '';
        }
        if (!isset($num2Parts[1])) {
            $num2Parts[1] = '';
        }

        // Pad decimal parts to same length
        $maxPad = max(strlen($num1Parts[1]), strlen($num2Parts[1]));
        $num1Parts[1] = str_pad($num1Parts[1], $maxPad, '0');
        $num2Parts[1] = str_pad($num2Parts[1], $maxPad, '0');

        // Convert to BigInteger for calculation
        $num1Big = new BigInteger($num1Parts[0].$num1Parts[1]);
        $num2Big = new BigInteger($num2Parts[0].$num2Parts[1]);

        $result = $num1Big->subtract($num2Big);

        $formatted = self::format($result, $scale, $maxPad);

        // Normalize -0.000 to 0.000
        return preg_match('#^-0\.?0*$#', $formatted) ? substr($formatted, 1) : $formatted;
    }

    /**
     * Multiply two arbitrary precision numbers.
     */
    public static function mul(string $num1, string $num2, ?int $scale = null): string
    {
        // Handle input validation and type conversion internally
        if (!is_numeric($num1)) {
            $num1 = '0';
        }
        if (!is_numeric($num2)) {
            $num2 = '0';
        }

        // Use default scale if not provided
        if ($scale === null) {
            if (!isset(self::$scale)) {
                $defaultScale = ini_get('bcmath.scale');
                self::$scale = $defaultScale !== false ? max((int) $defaultScale, 0) : 0;
            }
            $scale = self::$scale;
        }

        // Early zero check
        if ($num1 === '0' || $num2 === '0') {
            $result = '0';
            if ($scale) {
                $result .= '.'.str_repeat('0', $scale);
            }

            return $result;
        }

        // Convert to exploded form for decimal processing
        $num1Parts = explode('.', $num1);
        $num2Parts = explode('.', $num2);

        // Ensure both have decimal parts
        if (!isset($num1Parts[1])) {
            $num1Parts[1] = '';
        }
        if (!isset($num2Parts[1])) {
            $num2Parts[1] = '';
        }

        // Pad decimal parts to same length
        $maxPad = max(strlen($num1Parts[1]), strlen($num2Parts[1]));
        $num1Parts[1] = str_pad($num1Parts[1], $maxPad, '0');
        $num2Parts[1] = str_pad($num2Parts[1], $maxPad, '0');

        // Convert to BigInteger for calculation
        $num1Big = new BigInteger($num1Parts[0].$num1Parts[1]);
        $num2Big = new BigInteger($num2Parts[0].$num2Parts[1]);

        $result = $num1Big->abs()->multiply($num2Big->abs());
        $sign = ((self::isNegative($num1Big) ^ self::isNegative($num2Big)) !== 0) ? '-' : '';

        $formatted = $sign.self::format($result, $scale, 2 * $maxPad);

        // Normalize -0.000 to 0.000
        return preg_match('#^-0\.?0*$#', $formatted) ? substr($formatted, 1) : $formatted;
    }

    /**
     * Divide two arbitrary precision numbers.
     */
    public static function div(string $num1, string $num2, ?int $scale = null): string
    {
        // Handle input validation and type conversion internally
        if (!is_numeric($num1)) {
            $num1 = '0';
        }
        if (!is_numeric($num2)) {
            $num2 = '0';
        }

        // Use default scale if not provided
        if ($scale === null) {
            if (!isset(self::$scale)) {
                $defaultScale = ini_get('bcmath.scale');
                self::$scale = $defaultScale !== false ? max((int) $defaultScale, 0) : 0;
            }
            $scale = self::$scale;
        }

        // Division by zero check
        if ($num2 === '0') {
            // < PHP 8.0 triggered a warning
            // >= PHP 8.0 throws an exception
            throw new \DivisionByZeroError('Division by zero');
        }

        // Convert to exploded form for decimal processing
        $num1Parts = explode('.', $num1);
        $num2Parts = explode('.', $num2);

        // Ensure both have decimal parts
        if (!isset($num1Parts[1])) {
            $num1Parts[1] = '';
        }
        if (!isset($num2Parts[1])) {
            $num2Parts[1] = '';
        }

        // Pad decimal parts to same length
        $maxPad = max(strlen($num1Parts[1]), strlen($num2Parts[1]));
        $num1Parts[1] = str_pad($num1Parts[1], $maxPad, '0');
        $num2Parts[1] = str_pad($num2Parts[1], $maxPad, '0');

        // Convert to BigInteger for calculation
        $num1Big = new BigInteger($num1Parts[0].$num1Parts[1]);
        $num2Big = new BigInteger($num2Parts[0].$num2Parts[1]);

        $temp = '1'.str_repeat('0', $scale);
        $temp = new BigInteger($temp);
        [$quotient] = $num1Big->multiply($temp)->divide($num2Big);

        $formatted = self::format($quotient, $scale, $scale);

        // Normalize -0.000 to 0.000
        return preg_match('#^-0\.?0*$#', $formatted) ? substr($formatted, 1) : $formatted;
    }

    /**
     * Get modulus of an arbitrary precision number.
     *
     * Uses the PHP 7.2+ behavior
     */
    public static function mod(string $num1, string $num2, ?int $scale = null): string
    {
        // Handle input validation and type conversion internally
        if (!is_numeric($num1)) {
            $num1 = '0';
        }
        if (!is_numeric($num2)) {
            $num2 = '0';
        }

        // Use default scale if not provided
        if ($scale === null) {
            if (!isset(self::$scale)) {
                $defaultScale = ini_get('bcmath.scale');
                self::$scale = $defaultScale !== false ? max((int) $defaultScale, 0) : 0;
            }
            $scale = self::$scale;
        }

        // Division by zero check
        if ($num2 === '0') {
            // < PHP 8.0 triggered a warning
            // >= PHP 8.0 throws an exception
            throw new \DivisionByZeroError('Division by zero');
        }

        // Convert to exploded form for decimal processing
        $num1Parts = explode('.', $num1);
        $num2Parts = explode('.', $num2);

        // Ensure both have decimal parts
        if (!isset($num1Parts[1])) {
            $num1Parts[1] = '';
        }
        if (!isset($num2Parts[1])) {
            $num2Parts[1] = '';
        }

        // Pad decimal parts to same length
        $maxPad = max(strlen($num1Parts[1]), strlen($num2Parts[1]));
        $num1Parts[1] = str_pad($num1Parts[1], $maxPad, '0');
        $num2Parts[1] = str_pad($num2Parts[1], $maxPad, '0');

        // Convert to BigInteger for calculation
        $num1Big = new BigInteger($num1Parts[0].$num1Parts[1]);
        $num2Big = new BigInteger($num2Parts[0].$num2Parts[1]);

        [$quotient] = $num1Big->divide($num2Big);
        $remainder = $num2Big->multiply($quotient);
        $result = $num1Big->subtract($remainder);

        $formatted = self::format($result, $scale, $maxPad);

        // Normalize -0.000 to 0.000
        return preg_match('#^-0\.?0*$#', $formatted) ? substr($formatted, 1) : $formatted;
    }

    /**
     * Compare two arbitrary precision numbers.
     */
    public static function comp(string $num1, string $num2, ?int $scale = null): int
    {
        // Handle input validation and type conversion internally
        if (!is_numeric($num1)) {
            $num1 = '0';
        }
        if (!is_numeric($num2)) {
            $num2 = '0';
        }

        // Use default scale if not provided
        if ($scale === null) {
            $scale = 0; // comp uses 0 as default scale
        }

        // Convert to exploded form for decimal processing
        $num1Parts = explode('.', $num1);
        $num2Parts = explode('.', $num2);

        // Ensure both have decimal parts
        if (!isset($num1Parts[1])) {
            $num1Parts[1] = '';
        }
        if (!isset($num2Parts[1])) {
            $num2Parts[1] = '';
        }

        // Apply scale truncation
        $num1Parts[1] = substr($num1Parts[1], 0, $scale);
        $num2Parts[1] = substr($num2Parts[1], 0, $scale);

        // Convert to BigInteger for comparison
        $num1Big = new BigInteger($num1Parts[0].$num1Parts[1]);
        $num2Big = new BigInteger($num2Parts[0].$num2Parts[1]);

        return $num1Big->compare($num2Big);
    }

    /**
     * Raise an arbitrary precision number to another.
     *
     * Uses the PHP 7.2+ behavior
     */
    public static function pow(string $base, string $exponent, ?int $scale = null): string
    {
        // Handle input validation and type conversion internally
        if (!is_numeric($base)) {
            $base = '0';
        }
        if (!is_numeric($exponent)) {
            $exponent = '0';
        }

        // Use default scale if not provided
        if ($scale === null) {
            if (!isset(self::$scale)) {
                $defaultScale = ini_get('bcmath.scale');
                self::$scale = $defaultScale !== false ? max((int) $defaultScale, 0) : 0;
            }
            $scale = self::$scale;
        }

        if ($exponent === '0') {
            $result = '1';
            if ($scale) {
                $result .= '.'.str_repeat('0', $scale);
            }

            return $result;
        }

        $min = defined('PHP_INT_MIN') ? PHP_INT_MIN : ~PHP_INT_MAX;
        if (self::comp($exponent, (string) PHP_INT_MAX) > 0 || self::comp($exponent, (string) $min) < 0) {
            throw new \ValueError('bcpow(): Argument #2 ($exponent) is too large');
        }

        // Convert to exploded form for decimal processing
        $baseParts = explode('.', $base);
        if (!isset($baseParts[1])) {
            $baseParts[1] = '';
        }

        // Pad decimal parts
        $maxPad = strlen($baseParts[1]);
        $baseParts[1] = str_pad($baseParts[1], $maxPad, '0');

        // Convert to BigInteger for calculation
        $baseBig = new BigInteger($baseParts[0].$baseParts[1]);

        $sign = self::isNegative($baseBig) ? '-' : '';
        $baseBig = $baseBig->abs();

        $r = new BigInteger(1);
        $exponentBig = new BigInteger($exponent);
        $absExponent = self::isNegative($exponentBig) ? substr($exponent, 1) : $exponent;
        for ($i = 0; $i < $absExponent; $i++) {
            $r = $r->multiply($baseBig);
        }

        if ($exponent < 0) {
            $temp = '1'.str_repeat('0', $scale + $maxPad * (int) $absExponent);
            $temp = new BigInteger($temp);
            [$r] = $temp->divide($r);
            $finalPad = $scale;
        } else {
            $finalPad = $maxPad * (int) $absExponent;
        }

        return $sign.self::format($r, $scale, $finalPad);
    }

    /**
     * Raise an arbitrary precision number to another, reduced by a specified modulus.
     */
    public static function powmod(string $base, string $exponent, string $modulus, ?int $scale = null): string
    {
        // Check argument count to match bcpowmod() behavior
        if (func_num_args() > 4) {
            throw new \ArgumentCountError('bcpowmod() expects at most 4 arguments, '.func_num_args().' given');
        }

        // Handle input validation and type conversion internally
        if (!is_numeric($base)) {
            $base = '0';
        }
        if (!is_numeric($exponent)) {
            $exponent = '0';
        }
        if (!is_numeric($modulus)) {
            $modulus = '0';
        }

        // For powmod, if scale is not provided, return integer result (no decimal places)
        if ($scale === null) {
            $scale = 0;
        }

        // Remove fractional parts for integer-only operations
        $baseInt = explode('.', $base)[0];
        $exponentInt = explode('.', $exponent)[0];
        $modulusInt = explode('.', $modulus)[0];

        if ($exponentInt[0] == '-' || $modulusInt === '0') {
            // < PHP 8.0 returned false
            // >= PHP 8.0 throws an exception
            throw new \ValueError('bcpowmod(): Argument #2 ($exponent) must be greater than or equal to 0');
        }
        if ($modulusInt[0] == '-') {
            $modulusInt = substr($modulusInt, 1);
        }
        if ($exponentInt === '0') {
            return $scale !== 0
                ? '1.'.str_repeat('0', $scale)
                : '1';
        }

        $x = new BigInteger($baseInt);
        $e = new BigInteger($exponentInt);
        $n = new BigInteger($modulusInt);

        $z = $x->powMod($e, $n);

        return $scale !== 0
            ? "{$z}.".str_repeat('0', $scale)
            : "{$z}";
    }

    /**
     * Get the square root of an arbitrary precision number.
     */
    public static function sqrt(string $num, ?int $scale = null): string
    {
        // the following is based off of the following URL:
        // https://en.wikipedia.org/wiki/Methods_of_computing_square_roots#Decimal_(base_10)

        if (!is_numeric($num)) {
            return '0';
        }

        // Use default scale if not provided
        if ($scale === null) {
            if (!isset(self::$scale)) {
                $defaultScale = ini_get('bcmath.scale');
                self::$scale = $defaultScale !== false ? max((int) $defaultScale, 0) : 0;
            }
            $scale = self::$scale;
        }
        $temp = explode('.', $num);
        $decStart = ceil(strlen($temp[0]) / 2);
        $numStr = implode('', $temp);
        if (strlen($numStr) % 2 !== 0) {
            $numStr = "0{$numStr}";
        }
        $parts = str_split($numStr, 2);
        $parts = array_map('intval', $parts);
        $i = 0;
        $p = 0; // for the first step, p = 0
        $c = $parts[$i];
        $result = '';
        while (true) {
            // determine the greatest digit x such that x(20p+x) <= c
            for ($x = 1; $x <= 10; $x++) {
                if ($x * (20 * $p + $x) > $c) {
                    $x--;

                    break;
                }
            }
            $result .= $x;
            $y = $x * (20 * $p + $x);
            $p = 10 * $p + $x;
            $c = 100 * ($c - $y);
            if (isset($parts[++$i])) {
                $c += $parts[$i];
            }
            if ((!$c && $i >= $decStart) || $i - $decStart == $scale) {
                break;
            }
            if ($decStart == $i) {
                $result .= '.';
            }
        }

        $result = explode('.', $result);
        if (isset($result[1])) {
            $result[1] = str_pad($result[1], $scale, '0');
        } elseif ($scale) {
            $result[1] = str_repeat('0', $scale);
        }

        return implode('.', $result);
    }

    /**
     * Round down to the nearest integer.
     */
    public static function floor(string $num): string
    {
        if (!is_numeric($num)) {
            if (version_compare(PHP_VERSION, '8.4', '>=')) {
                throw new \ValueError('bcfloor(): Argument #1 ($num) is not well-formed');
            }
            trigger_error('bcfloor(): Argument #1 ($num) is not well-formed', E_USER_WARNING);

            return '0';
        }

        // floor() always returns integer (no scale parameter)
        $result = self::div($num, '1', 0);

        // For negative numbers with fractional parts, we need to subtract 1
        if (str_contains($num, '.') && $num[0] === '-') {
            $fractionalPart = substr($num, strpos($num, '.') + 1);
            if (ltrim($fractionalPart, '0') !== '') {
                $result = self::sub($result, '1', 0);
            }
        }

        return $result;
    }

    /**
     * Round up to the nearest integer.
     */
    public static function ceil(string $num): string
    {
        if (!is_numeric($num)) {
            if (version_compare(PHP_VERSION, '8.4', '>=')) {
                throw new \ValueError('bcceil(): Argument #1 ($num) is not well-formed');
            }
            trigger_error('bcceil(): Argument #1 ($num) is not well-formed', E_USER_WARNING);

            return '0';
        }

        // ceil() always returns integer (no scale parameter)
        $result = self::div($num, '1', 0);

        // For positive numbers with fractional parts, we need to add 1
        if (str_contains($num, '.') && $num[0] !== '-') {
            $fractionalPart = substr($num, strpos($num, '.') + 1);
            if (ltrim($fractionalPart, '0') !== '') {
                $result = self::add($result, '1', 0);
            }
        }

        return $result;
    }

    /**
     * Round to a given decimal place.
     */
    public static function round(string $num, int $precision = 0, int $mode = PHP_ROUND_HALF_UP): string
    {
        if (!is_numeric($num)) {
            if (version_compare(PHP_VERSION, '8.4', '>=')) {
                throw new \ValueError('bcround(): Argument #1 ($num) is not well-formed');
            }
            trigger_error('bcround(): Argument #1 ($num) is not well-formed', E_USER_WARNING);

            return '0';
        }

        // Based on: https://stackoverflow.com/a/1653826
        if ($precision < 0) {
            // When precision is negative, we round to the left of the decimal point
            $absPrecision = abs($precision);
            $factor = self::pow('10', (string) $absPrecision, max($absPrecision, 0));
            $shifted = self::div($num, $factor, 10); // Use a high precision for intermediate calculation

            // Apply rounding
            $rounded = self::bcroundHelper($shifted, 0, $mode);

            // Shift back
            return self::mul($rounded, $factor, 0);
        }

        return self::bcroundHelper($num, $precision, $mode);
    }

    /**
     * Helper function for bcround.
     */
    public static function bcroundHelper(string $number, int $precision, int $mode = PHP_ROUND_HALF_UP): string
    {
        if (!str_contains($number, '.')) {
            $number .= '.0';
        }

        // Extract sign
        $sign = '';
        if ($number[0] === '-') {
            $sign = '-';
            $number = substr($number, 1);
        }

        // Add 0.5 * 10^(-$precision) for rounding (for HALF_UP mode)
        if ($mode === PHP_ROUND_HALF_UP) {
            $addition = '0.'.str_repeat('0', $precision).'5';
            $number = self::add($number, $addition, $precision + 1);
        } elseif ($mode === PHP_ROUND_HALF_DOWN) {
            // For HALF_DOWN, we need to check the digit at precision+1
            [$int, $dec] = explode('.', $number);
            if (isset($dec[$precision])) {
                $digit = (int) $dec[$precision];
                if ($digit == 5 && (!isset($dec[$precision + 1]) || ltrim(substr($dec, $precision + 1), '0') === '')) {
                    // Exactly 0.5, don't round up
                } elseif ($digit > 5 || ($digit == 5 && ltrim(substr($dec, $precision + 1), '0') !== '')) {
                    $addition = '0.'.str_repeat('0', $precision).'1';
                    $number = self::add($number, $addition, $precision + 1);
                }
            }
        } else {
            // For other modes, use PHP's round and convert back
            $rounded = round((float) ($sign.$number), $precision, $mode);

            return number_format($rounded, $precision, '.', '');
        }

        // Truncate to the desired precision
        $pos = strpos($number, '.');
        if ($pos !== false) {
            if ($precision > 0) {
                $number = substr($number, 0, $pos + $precision + 1);
                // Pad with zeros if necessary
                $currentPrecision = strlen($number) - $pos - 1;
                if ($currentPrecision < $precision) {
                    $number .= str_repeat('0', $precision - $currentPrecision);
                }
            } else {
                $number = substr($number, 0, $pos);
            }
        }

        return $sign.$number;
    }

    /**
     * __callStatic Magic Method - Simple wrapper for public methods.
     *
     * @param array<int, null|BCMath|bool|int|string|string[]> $arguments
     */
    public static function __callStatic(string $name, array $arguments): int|string
    {
        // Simple delegation to corresponding public static methods
        switch ($name) {
            case 'add':
                return self::add($arguments[0] ?? '0', $arguments[1] ?? '0', $arguments[2] ?? null);

            case 'sub':
                return self::sub($arguments[0] ?? '0', $arguments[1] ?? '0', $arguments[2] ?? null);

            case 'mul':
                return self::mul($arguments[0] ?? '0', $arguments[1] ?? '0', $arguments[2] ?? null);

            case 'div':
                return self::div($arguments[0] ?? '0', $arguments[1] ?? '0', $arguments[2] ?? null);

            case 'mod':
                return self::mod($arguments[0] ?? '0', $arguments[1] ?? '0', $arguments[2] ?? null);

            case 'comp':
                return self::comp($arguments[0] ?? '0', $arguments[1] ?? '0', $arguments[2] ?? null);

            case 'pow':
                return self::pow($arguments[0] ?? '0', $arguments[1] ?? '0', $arguments[2] ?? null);

            case 'powmod':
                if (count($arguments) > 4) {
                    throw new \ArgumentCountError('bcpowmod() expects at most 4 arguments, '.count($arguments).' given');
                }

                return self::powmod($arguments[0] ?? '0', $arguments[1] ?? '0', $arguments[2] ?? '1', $arguments[3] ?? null);

            case 'sqrt':
                return self::sqrt($arguments[0] ?? '0', $arguments[1] ?? null);

            case 'floor':
                return self::floor($arguments[0] ?? '0');

            case 'ceil':
                return self::ceil($arguments[0] ?? '0');

            case 'round':
                return self::round($arguments[0] ?? '0', $arguments[1] ?? 0, $arguments[2] ?? PHP_ROUND_HALF_UP);

            case 'scale':
                if (count($arguments) > 1) {
                    throw new \ArgumentCountError('bcscale() expects at most 1 argument, '.count($arguments).' given');
                }

                return self::scale($arguments[0] ?? null);

            default:
                throw new \BadMethodCallException("Unknown method: {$name}");
        }
    }
}
