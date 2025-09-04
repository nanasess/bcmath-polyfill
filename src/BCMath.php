<?php

/**
 * BCMath Emulation Class.
 *
 * PHP version 8.1+
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
 */
abstract class BCMath
{
    /**
     * Default scale parameter for all bc math functions.
     */
    private static ?int $scale = null;

    /**
     * Common constants used throughout the class.
     */
    private const DEFAULT_NUMBER = '0';
    private const DIVISION_BY_ZERO_MESSAGE = 'Division by zero';

    /**
     * Validate and normalize two input numbers.
     *
     * @param string $num1 First number
     * @param string $num2 Second number
     *
     * @return string[] Array containing normalized [$num1, $num2]
     */
    private static function validateAndNormalizeInputs(string $num1, string $num2): array
    {
        if (!is_numeric($num1)) {
            $num1 = self::DEFAULT_NUMBER;
        }
        if (!is_numeric($num2)) {
            $num2 = self::DEFAULT_NUMBER;
        }

        return [$num1, $num2];
    }

    /**
     * Resolve the scale parameter, using default if null.
     *
     * @param null|int $scale Scale parameter
     *
     * @return int Resolved scale value
     */
    private static function resolveScale(?int $scale = null): int
    {
        if ($scale === null) {
            if (!isset(self::$scale)) {
                $defaultScale = ini_get('bcmath.scale');
                self::$scale = $defaultScale !== false ? max((int) $defaultScale, 0) : 0;
            }
            $scale = self::$scale;
        }

        return $scale;
    }

    /**
     * Parse a decimal number into integer and fractional parts.
     *
     * @param string $num Number to parse
     *
     * @return string[] Array containing [integer_part, fractional_part]
     */
    private static function parseDecimalNumber(string $num): array
    {
        $parts = explode('.', $num);

        // Ensure both parts exist
        if (!isset($parts[1])) {
            $parts[1] = '';
        }

        return [$parts[0], $parts[1]];
    }

    /**
     * Prepare two numbers for BigInteger operations by parsing and padding.
     *
     * @param string $num1 First number
     * @param string $num2 Second number
     *
     * @return array{0: BigInteger, 1: BigInteger, 2: int} Array containing [num1Big, num2Big, maxPad]
     */
    private static function prepareBigIntegerInputs(string $num1, string $num2): array
    {
        // Parse decimal numbers
        [$num1Int, $num1Dec] = self::parseDecimalNumber($num1);
        [$num2Int, $num2Dec] = self::parseDecimalNumber($num2);

        // Pad decimal parts to same length
        $maxPad = max(strlen((string) $num1Dec), strlen((string) $num2Dec));
        $num1Dec = str_pad((string) $num1Dec, $maxPad, '0');
        $num2Dec = str_pad((string) $num2Dec, $maxPad, '0');

        // Convert to BigInteger for calculation
        $num1Big = new BigInteger($num1Int.$num1Dec);
        $num2Big = new BigInteger($num2Int.$num2Dec);

        return [$num1Big, $num2Big, $maxPad];
    }

    /**
     * Format the final result from BigInteger calculation.
     *
     * @param BigInteger $result Calculation result
     * @param int $scale Desired scale
     * @param int $pad Padding for decimal adjustment
     *
     * @return string Formatted result
     */
    private static function formatFinalResult(BigInteger $result, int $scale, int $pad = 0): string
    {
        $formatted = self::format($result, $scale, $pad);

        return self::normalizeZeroResult($formatted);
    }

    /**
     * Normalize negative zero results to positive zero.
     *
     * @param string $result Result to normalize
     *
     * @return string Normalized result
     */
    private static function normalizeZeroResult(string $result): string
    {
        // Normalize -0.000 to 0.000
        return preg_match('#^-0\.?0*$#', $result) ? substr($result, 1) : $result;
    }

    /**
     * Handle early zero check for multiplication.
     *
     * @param string $num1 First number
     * @param string $num2 Second number
     * @param int $scale Scale for result
     *
     * @return null|string Returns formatted zero result or null if not zero
     */
    private static function checkEarlyZero(string $num1, string $num2, int $scale): ?string
    {
        if ($num1 === self::DEFAULT_NUMBER || $num2 === self::DEFAULT_NUMBER) {
            $result = self::DEFAULT_NUMBER;
            if ($scale !== 0) {
                $result .= '.'.str_repeat(self::DEFAULT_NUMBER, $scale);
            }

            return $result;
        }

        return null;
    }

    /**
     * Check for division by zero and throw exception.
     *
     * @param string $divisor Divisor to check
     *
     * @throws \DivisionByZeroError If divisor is zero
     */
    private static function checkDivisionByZero(string $divisor): void
    {
        if ($divisor === self::DEFAULT_NUMBER) {
            throw new \DivisionByZeroError(self::DIVISION_BY_ZERO_MESSAGE);
        }
    }

    /**
     * Resolve scale for comparison operations (defaults to 0).
     *
     * @param null|int $scale Scale parameter
     *
     * @return int Resolved scale value
     */
    private static function resolveScaleForComparison(?int $scale = null): int
    {
        if ($scale === null) {
            $scale = 0; // comp uses 0 as default scale
        }

        return $scale;
    }

    /**
     * Prepare numbers for comparison with scale truncation.
     *
     * @param string $num1 First number
     * @param string $num2 Second number
     * @param int $scale Scale for truncation
     *
     * @return BigInteger[] Array containing [num1Big, num2Big]
     */
    private static function prepareForComparison(string $num1, string $num2, int $scale): array
    {
        // Parse decimal numbers
        [$num1Int, $num1Dec] = self::parseDecimalNumber($num1);
        [$num2Int, $num2Dec] = self::parseDecimalNumber($num2);

        // Apply scale truncation
        $num1Dec = substr((string) $num1Dec, 0, $scale);
        $num2Dec = substr((string) $num2Dec, 0, $scale);

        // Convert to BigInteger for comparison
        $num1Big = new BigInteger($num1Int.$num1Dec);
        $num2Big = new BigInteger($num2Int.$num2Dec);

        return [$num1Big, $num2Big];
    }

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
     */
    public static function format(BigInteger $x, ?int $scale = null, int $pad = 0): string
    {
        $sign = self::isNegative($x) ? '-' : '';
        $x = str_replace('-', '', (string) $x);

        if (strlen($x) != $pad) {
            $x = str_pad($x, $pad, '0', STR_PAD_LEFT);
        }
        $temp = $pad !== 0 ? substr_replace($x, '.', -$pad, 0) : $x;
        $temp = explode('.', $temp);
        if ($temp[0] == '') {
            $temp[0] = '0';
        }
        if (isset($temp[1])) {
            $temp[1] = substr($temp[1], 0, $scale);
            $temp[1] = str_pad($temp[1], (int) $scale, '0');
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
        // Phase 1: Argument validation
        [$num1, $num2] = self::validateAndNormalizeInputs($num1, $num2);

        // Phase 2: Scale resolution
        $scale = self::resolveScale($scale);

        // Phase 3: Number processing
        [$num1Big, $num2Big, $maxPad] = self::prepareBigIntegerInputs($num1, $num2);

        // Phase 4: Calculation execution
        $result = $num1Big->add($num2Big);

        // Phase 5: Result formatting
        return self::formatFinalResult($result, $scale, $maxPad);
    }

    /**
     * Subtract one arbitrary precision number from another.
     */
    public static function sub(string $num1, string $num2, ?int $scale = null): string
    {
        // Phase 1: Argument validation
        [$num1, $num2] = self::validateAndNormalizeInputs($num1, $num2);

        // Phase 2: Scale resolution
        $scale = self::resolveScale($scale);

        // Phase 3: Number processing
        [$num1Big, $num2Big, $maxPad] = self::prepareBigIntegerInputs($num1, $num2);

        // Phase 4: Calculation execution
        $result = $num1Big->subtract($num2Big);

        // Phase 5: Result formatting
        return self::formatFinalResult($result, $scale, $maxPad);
    }

    /**
     * Multiply two arbitrary precision numbers.
     */
    public static function mul(string $num1, string $num2, ?int $scale = null): string
    {
        // Phase 1: Argument validation
        [$num1, $num2] = self::validateAndNormalizeInputs($num1, $num2);

        // Phase 2: Scale resolution
        $scale = self::resolveScale($scale);

        // Phase 2.5: Early zero check
        $earlyZero = self::checkEarlyZero($num1, $num2, $scale);
        if ($earlyZero !== null) {
            return $earlyZero;
        }

        // Phase 3: Number processing
        [$num1Big, $num2Big, $maxPad] = self::prepareBigIntegerInputs($num1, $num2);

        // Phase 4: Calculation execution with sign handling
        $result = $num1Big->abs()->multiply($num2Big->abs());
        $sign = ((self::isNegative($num1Big) ^ self::isNegative($num2Big)) !== 0) ? '-' : '';

        // Phase 5: Result formatting with sign
        $formatted = $sign.self::format($result, $scale, 2 * $maxPad);

        return self::normalizeZeroResult($formatted);
    }

    /**
     * Divide two arbitrary precision numbers.
     */
    public static function div(string $num1, string $num2, ?int $scale = null): string
    {
        // Phase 1: Argument validation
        [$num1, $num2] = self::validateAndNormalizeInputs($num1, $num2);

        // Phase 2: Scale resolution
        $scale = self::resolveScale($scale);

        // Phase 2.5: Division by zero check
        self::checkDivisionByZero($num2);

        // Phase 3: Number processing
        [$num1Big, $num2Big, $maxPad] = self::prepareBigIntegerInputs($num1, $num2);

        // Phase 4: Calculation execution with scale adjustment
        $temp = '1'.str_repeat('0', $scale);
        $temp = new BigInteger($temp);
        [$quotient] = $num1Big->multiply($temp)->divide($num2Big);

        // Phase 5: Result formatting with division-specific scale
        $formatted = self::format($quotient, $scale, $scale);

        return self::normalizeZeroResult($formatted);
    }

    /**
     * Get modulus of an arbitrary precision number.
     *
     * Uses the PHP 7.2+ behavior
     */
    public static function mod(string $num1, string $num2, ?int $scale = null): string
    {
        // Phase 1: Argument validation
        [$num1, $num2] = self::validateAndNormalizeInputs($num1, $num2);

        // Phase 2: Scale resolution
        $scale = self::resolveScale($scale);

        // Phase 2.5: Division by zero check
        self::checkDivisionByZero($num2);

        // Phase 3: Number processing
        [$num1Big, $num2Big, $maxPad] = self::prepareBigIntegerInputs($num1, $num2);

        // Phase 4: Calculation execution with modulus logic
        [$quotient] = $num1Big->divide($num2Big);
        $remainder = $num2Big->multiply($quotient);
        $result = $num1Big->subtract($remainder);

        // Phase 5: Result formatting
        return self::formatFinalResult($result, $scale, $maxPad);
    }

    /**
     * Compare two arbitrary precision numbers.
     */
    public static function comp(string $num1, string $num2, ?int $scale = null): int
    {
        // Phase 1: Argument validation
        [$num1, $num2] = self::validateAndNormalizeInputs($num1, $num2);

        // Phase 2: Scale resolution (special for comparison)
        $scale = self::resolveScaleForComparison($scale);

        // Phase 3: Number processing (special for comparison)
        [$num1Big, $num2Big] = self::prepareForComparison($num1, $num2, $scale);

        // Phase 4: Calculation execution
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

        // Remove any fractional part
        if (str_contains($num, '.')) {
            $dotPos = (int) strpos($num, '.');
            $integerPart = substr($num, 0, $dotPos);
            $fractionalPart = substr($num, $dotPos + 1);

            // For negative numbers with fractional parts, we need to subtract 1
            if ($num[0] === '-' && ltrim($fractionalPart, '0') !== '') {
                return self::sub($integerPart, '1', 0);
            }

            return $integerPart === '' || $integerPart === '-' ? '0' : $integerPart;
        }

        return $num;
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

        // Remove any fractional part
        if (str_contains($num, '.')) {
            $dotPos = (int) strpos($num, '.');
            $integerPart = substr($num, 0, $dotPos);
            $fractionalPart = substr($num, $dotPos + 1);

            // For positive numbers with fractional parts, we need to add 1
            if ($num[0] !== '-' && ltrim($fractionalPart, '0') !== '') {
                $integerPart = $integerPart === '' ? '0' : $integerPart;

                return self::add($integerPart, '1', 0);
            }

            return $integerPart === '' || $integerPart === '-' ? '0' : $integerPart;
        }

        return $num;
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
            // Ensure mode is within valid range (PHP_ROUND_HALF_UP to PHP_ROUND_HALF_ODD)
            $validMode = max(PHP_ROUND_HALF_UP, min(PHP_ROUND_HALF_ODD, $mode));
            $rounded = round((float) ($sign.$number), $precision, $validMode);

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

        $result = $sign.$number;

        // Handle negative zero case
        if ($result === '-0' || $result === '-0.' || preg_match('/^-0\.0+$/', $result)) {
            $result = ltrim($result, '-');
            if ($result === '0' || $result === '0.' || preg_match('/^0\.0+$/', $result)) {
                $result = $precision > 0 ? '0.'.str_repeat('0', $precision) : '0';
            }
        }

        return $result;
    }
}
