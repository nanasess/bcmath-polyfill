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
 * Provides arbitrary precision arithmetic operations using phpseclib3's BigInteger.
 * All arithmetic methods follow a standardized 5-phase processing pattern:
 *
 * **Standard 5-Phase Processing Pattern:**
 * - **Phase 1:** Argument validation and normalization
 * - **Phase 2:** Scale resolution (using INI defaults if needed)
 * - **Phase 3:** Number processing and BigInteger preparation
 * - **Phase 4:** Calculation execution using BigInteger operations
 * - **Phase 5:** Result formatting and normalization
 *
 * **Architecture Guidelines:**
 * - Helper methods are `protected` to enable extensibility
 * - Consistent error handling with appropriate exception types
 * - Optimized early returns for common cases (zero multiplication)
 * - Type-safe validation with comprehensive constraint checking
 *
 * @author Jim Wigginton <terrafrost@php.net>
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
     * Converts non-numeric inputs to '0' to match bcmath behavior.
     * This method implements Phase 1 of the standard 5-phase processing pattern.
     *
     * @return string[] Array containing normalized [$num1, $num2]
     *
     * @throws \ValueError if inputs are not well-formed
     */
    protected static function validateAndNormalizeInputs(string $num1, string $num2, string $function): array
    {
        self::validateNumberString($num1, $function, 1, 'num1');
        self::validateNumberString($num2, $function, 2, 'num2');

        return [$num1, $num2];
    }

    /**
     * Resolve the scale parameter, using default if null.
     *
     * Implements Phase 2 of the standard 5-phase processing pattern.
     * Uses bcmath.scale INI setting as fallback when no scale is provided.
     */
    protected static function resolveScale(?int $scale = null): int
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
     * Validate scale parameter for bcmath functions.
     *
     * @param int $scale Scale to validate
     * @param string $function Function name for error message
     * @param int $argNumber Argument number for error message
     *
     * @throws \ValueError if scale is invalid
     */
    private static function validateScale(int $scale, string $function, int $argNumber): void
    {
        if ($scale < 0 || $scale > 2147483647) {
            throw new \ValueError("{$function}(): Argument #{$argNumber} (\$scale) must be between 0 and 2147483647");
        }
    }

    /**
     * Validate number string according to bcmath str2num rules.
     *
     * @param string $num Number string to validate
     * @param string $function Function name for error message
     * @param int $argNumber Argument number for error message
     * @param string $argName Argument name for error message
     *
     * @throws \ValueError if number is not well-formed
     */
    private static function validateNumberString(string $num, string $function, int $argNumber, string $argName): void
    {
        // Empty string is valid (treated as '0')
        if ($num === '') {
            return;
        }

        // Check for common malformed patterns that should throw ValueError
        $malformedPatterns = [
            '/\s/',            // Any whitespace
            '/[eE]/',          // Scientific notation
            '/,/',             // Comma instead of decimal point
            '/[^\d\-\+\.]/',   // Invalid characters
            '/\..*\./',        // Multiple decimal points
            '/[+-].*[+-]/',    // Multiple signs
            '/[+-]$/',         // Sign at end without digits
            '/^[+-]\./',       // Sign followed immediately by decimal (like "+.")
        ];

        foreach ($malformedPatterns as $pattern) {
            if (preg_match($pattern, $num)) {
                throw new \ValueError("{$function}(): Argument #{$argNumber} (\${$argName}) is not well-formed");
            }
        }

        // Check for special float values
        $upperNum = strtoupper($num);
        if (in_array($upperNum, ['INF', '-INF', 'INFINITY', '-INFINITY', 'NAN'], true)) {
            throw new \ValueError("{$function}(): Argument #{$argNumber} (\${$argName}) is not well-formed");
        }

        // Additional validation: must be numeric after basic pattern checks
        if (!is_numeric($num)) {
            throw new \ValueError("{$function}(): Argument #{$argNumber} (\${$argName}) is not well-formed");
        }
    }

    /**
     * Parse a decimal number into integer and fractional parts.
     *
     * Handles numbers without decimal points by adding empty fractional part.
     * Used in Phase 3 of the standard processing pattern.
     *
     * @return string[] Array containing [integer_part, fractional_part]
     */
    protected static function parseDecimalNumber(string $num): array
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
     * Parses decimal numbers, pads fractional parts to equal length, and converts
     * to BigInteger for precise arithmetic. Implements Phase 3 processing.
     *
     * @return array{0: BigInteger, 1: BigInteger, 2: int} Array containing [num1Big, num2Big, maxPad]
     */
    protected static function prepareBigIntegerInputs(string $num1, string $num2): array
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
     * Applies scale formatting and zero normalization. Implements Phase 5
     * of the standard processing pattern for most arithmetic operations.
     */
    protected static function formatFinalResult(BigInteger $result, int $scale, int $pad = 0): string
    {
        $formatted = self::format($result, $scale, $pad);

        return self::normalizeZeroResult($formatted);
    }

    /**
     * Normalize negative zero results to positive zero.
     *
     * Converts "-0.000" to "0.000" to match bcmath behavior.
     * Used in result formatting phase to ensure consistent output.
     */
    private static function normalizeZeroResult(string $result): string
    {
        // Normalize -0.000 to 0.000
        return preg_match('#^-0\.?0*$#', $result) ? substr($result, 1) : $result;
    }

    /**
     * Handle early zero check for multiplication.
     *
     * Returns formatted zero result when either operand is zero, providing
     * significant performance optimization for multiplication operations.
     *
     * @return null|string Formatted zero result or null if no early return needed
     */
    private static function checkEarlyZero(string $num1, string $num2, int $scale): ?string
    {
        // Optimized early return with consistent constant usage
        if ($num1 === self::DEFAULT_NUMBER || $num2 === self::DEFAULT_NUMBER) {
            return $scale !== 0 ? self::DEFAULT_NUMBER.'.'.str_repeat(self::DEFAULT_NUMBER, $scale) : self::DEFAULT_NUMBER;
        }

        return null;
    }

    /**
     * Check if a numeric string represents zero.
     *
     * Handles various zero formats: '0', '0.00', '-0.00', '+0.000', etc.
     * Uses consistent normalization logic for reliable zero detection.
     *
     * Examples of inputs that return true:
     * - '0', '0.0', '0.00', '0.000'
     * - '-0', '-0.0', '-0.00', '-0.000'
     * - '+0', '+0.0', '+0.00', '+0.000'
     * - '00', '00.00', '000.000'
     *
     * Examples of inputs that return false:
     * - '1', '0.1', '0.001', '-0.001'
     * - 'abc', '', '.'
     *
     * @param string $number The numeric string to check
     *
     * @return bool True if the number is zero, false otherwise
     */
    private static function isZero(string $number): bool
    {
        $normalized = ltrim($number, '+-');
        $normalized = ltrim($normalized, '0');
        $normalized = ltrim($normalized, '.');
        $normalized = rtrim($normalized, '0');

        return $normalized === '' || $normalized === '.';
    }

    /**
     * Check if a string starts with a negative sign after trimming whitespace.
     *
     * @param string $num The string to check
     *
     * @return bool True if the string starts with '-' after trimming, false otherwise
     */
    private static function startsWithNegativeSign(string $num): bool
    {
        $trimmed = ltrim($num);

        return $trimmed !== '' && $trimmed[0] === '-';
    }

    /**
     * Check for division by zero and throw exception.
     *
     * @throws \DivisionByZeroError If divisor is zero
     */
    private static function checkDivisionByZero(string $divisor): void
    {
        if (self::isZero($divisor)) {
            throw new \DivisionByZeroError(self::DIVISION_BY_ZERO_MESSAGE);
        }
    }

    /**
     * Validate and extract integer parts from numeric strings for integer-only operations.
     *
     * This method extracts the integer portion from decimal numbers and validates
     * that they meet the requirements for integer-only operations like powmod().
     *
     * @param string[] $numbers Array of numeric strings to validate
     * @param string[] $names Array of parameter names for error messages
     * @param array<string, int[]> $constraints Array of validation constraints:
     *   - 'non_negative' => [indices] for parameters that must be >= 0
     *   - 'non_zero' => [indices] for parameters that cannot be zero
     *
     * @return string[] Array of validated integer strings
     *
     * @throws \ValueError If validation constraints are violated
     */
    protected static function validateIntegerInputs(array $numbers, array $names = [], array $constraints = []): array
    {
        $results = [];

        foreach ($numbers as $index => $number) {
            // Extract integer part
            $parts = explode('.', $number, 2);
            $intPart = $parts[0];

            // Handle empty or zero cases
            if ($intPart === '' || $intPart === '0') {
                $intPart = '0';
            }

            $paramName = $names[$index] ?? 'Argument #'.($index + 1);

            // Check non-zero constraint
            if (isset($constraints['non_zero']) && in_array($index, $constraints['non_zero'], true) && $intPart === self::DEFAULT_NUMBER) {
                throw new \ValueError("{$paramName} cannot be zero");
            }

            // Check non-negative constraint
            if (isset($constraints['non_negative']) && in_array($index, $constraints['non_negative'], true) && self::startsWithNegativeSign($intPart)) {
                throw new \ValueError("{$paramName} must be greater than or equal to 0");
            }

            // Handle negative numbers by removing the sign if allowed
            if (self::startsWithNegativeSign($intPart)
                && (!isset($constraints['non_negative']) || !in_array($index, $constraints['non_negative'], true))) {
                $trimmedIntPart = ltrim($intPart);
                $intPart = substr($trimmedIntPart, 1);
            }

            $results[] = $intPart;
        }

        return $results;
    }

    /**
     * Resolve scale for comparison operations (defaults to 0).
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
     * @return BigInteger[] Array containing [num1Big, num2Big]
     */
    private static function prepareForComparison(string $num1, string $num2, int $scale): array
    {
        // Parse decimal numbers
        [$num1Int, $num1Dec] = self::parseDecimalNumber($num1);
        [$num2Int, $num2Dec] = self::parseDecimalNumber($num2);

        // Apply scale truncation and padding
        $num1Dec = substr((string) $num1Dec, 0, $scale);
        $num2Dec = substr((string) $num2Dec, 0, $scale);

        // Pad decimal parts to the same length (scale)
        $num1Dec = str_pad($num1Dec, $scale, '0', STR_PAD_RIGHT);
        $num2Dec = str_pad($num2Dec, $scale, '0', STR_PAD_RIGHT);

        // Convert to BigInteger for comparison
        $num1Big = new BigInteger($num1Int.$num1Dec);
        $num2Big = new BigInteger($num2Int.$num2Dec);

        return [$num1Big, $num2Big];
    }

    /**
     * Handle rounding operations with negative zero normalization.
     *
     * @param string $num The number to process
     * @param string $functionName Function name for error messages
     * @param callable(string, string, string): ?string $fractionHandler Handler for numbers with non-zero fractional parts
     *
     * @return string The processed result
     */
    private static function normalizeZeroForRounding(string $num, string $functionName, callable $fractionHandler): string
    {
        self::validateNumberString($num, $functionName, 1, 'num');

        if (!is_numeric($num)) {
            if (version_compare(PHP_VERSION, '8.4', '>=')) {
                throw new \ValueError($functionName.'(): Argument #1 ($num) is not well-formed');
            }
            trigger_error($functionName.'(): Argument #1 ($num) is not well-formed', E_USER_WARNING);

            return '0';
        }

        // Handle the case where input is exactly '-0' (no decimal point)
        if ($num === '-0') {
            return '0';
        }

        // Remove any fractional part
        if (str_contains($num, '.')) {
            $dotPos = (int) strpos($num, '.');
            $integerPart = substr($num, 0, $dotPos);
            $fractionalPart = substr($num, $dotPos + 1);

            // Check if there's a non-zero fractional part
            $hasNonZeroFraction = ltrim($fractionalPart, '0') !== '';

            if ($hasNonZeroFraction) {
                $result = $fractionHandler($num, $integerPart, $fractionalPart);
                if ($result !== null) {
                    return $result;
                }
            }

            // Handle special cases: empty, '-', or '-0' should return '0'
            if ($integerPart === '' || $integerPart === '-' || $integerPart === '-0') {
                return '0';
            }

            return $integerPart;
        }

        return $num;
    }

    /**
     * Set or get default scale parameter for all bc math functions.
     *
     * Uses the PHP 7.3+ behavior
     */
    public static function scale(?int $scale = null): ?int
    {
        if (func_num_args() > 1) {
            throw new \ArgumentCountError('bcscale() expects at most 1 argument, '.func_num_args().' given');
        }

        if ($scale !== null) {
            self::validateScale($scale, 'bcscale', 1);
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

        if (strlen($x) !== $pad) {
            $x = str_pad($x, $pad, '0', STR_PAD_LEFT);
        }
        $temp = $pad !== 0 ? substr_replace($x, '.', -$pad, 0) : $x;
        $temp = explode('.', $temp);
        if ($temp[0] === '') {
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
     */
    public static function isNegative(BigInteger $x): bool
    {
        return $x->compare(new BigInteger()) < 0;
    }

    /**
     * Add two arbitrary precision numbers.
     *
     * @throws \ValueError if inputs are not well-formed
     */
    public static function add(string $num1, string $num2, ?int $scale = null): string
    {
        // Phase 1: Argument validation
        [$num1, $num2] = self::validateAndNormalizeInputs($num1, $num2, 'bcadd');

        // Phase 2: Scale resolution and validation
        $scale = self::resolveScale($scale);
        self::validateScale($scale, 'bcadd', 3);

        // Phase 3: Number processing
        [$num1Big, $num2Big, $maxPad] = self::prepareBigIntegerInputs($num1, $num2);

        // Phase 4: Calculation execution
        $result = $num1Big->add($num2Big);

        // Phase 5: Result formatting
        return self::formatFinalResult($result, $scale, $maxPad);
    }

    /**
     * Subtract one arbitrary precision number from another.
     *
     * @throws \ValueError if inputs are not well-formed
     */
    public static function sub(string $num1, string $num2, ?int $scale = null): string
    {
        // Phase 1: Argument validation
        [$num1, $num2] = self::validateAndNormalizeInputs($num1, $num2, 'bcsub');

        // Phase 2: Scale resolution and validation
        $scale = self::resolveScale($scale);
        self::validateScale($scale, 'bcsub', 3);

        // Phase 3: Number processing
        [$num1Big, $num2Big, $maxPad] = self::prepareBigIntegerInputs($num1, $num2);

        // Phase 4: Calculation execution
        $result = $num1Big->subtract($num2Big);

        // Phase 5: Result formatting
        return self::formatFinalResult($result, $scale, $maxPad);
    }

    /**
     * Multiply two arbitrary precision numbers.
     *
     * @throws \ValueError if inputs are not well-formed
     */
    public static function mul(string $num1, string $num2, ?int $scale = null): string
    {
        // Phase 1: Argument validation
        [$num1, $num2] = self::validateAndNormalizeInputs($num1, $num2, 'bcmul');

        // Phase 2: Scale resolution and validation
        $scale = self::resolveScale($scale);
        self::validateScale($scale, 'bcmul', 3);

        // Phase 3: Early zero check and number processing
        $earlyZero = self::checkEarlyZero($num1, $num2, $scale);
        if ($earlyZero !== null) {
            return $earlyZero;
        }
        [$num1Big, $num2Big, $maxPad] = self::prepareBigIntegerInputs($num1, $num2);

        // Phase 4: Calculation execution
        $result = $num1Big->abs()->multiply($num2Big->abs());
        $sign = ((self::isNegative($num1Big) ^ self::isNegative($num2Big)) !== 0) ? '-' : '';

        // Phase 5: Result formatting
        $formatted = $sign.self::format($result, $scale, 2 * $maxPad);

        return self::normalizeZeroResult($formatted);
    }

    /**
     * Divide two arbitrary precision numbers.
     *
     * @throws \DivisionByZeroError When divisor is zero
     * @throws \ValueError if inputs are not well-formed
     */
    public static function div(string $num1, string $num2, ?int $scale = null): string
    {
        // Phase 1: Argument validation
        [$num1, $num2] = self::validateAndNormalizeInputs($num1, $num2, 'bcdiv');

        // Phase 2: Scale resolution and validation
        $scale = self::resolveScale($scale);
        self::validateScale($scale, 'bcdiv', 3);

        // Phase 3: Division by zero check and number processing
        self::checkDivisionByZero($num2);
        [$num1Big, $num2Big, $maxPad] = self::prepareBigIntegerInputs($num1, $num2);

        // Phase 4: Calculation execution
        $temp = '1'.str_repeat('0', $scale);
        $temp = new BigInteger($temp);
        [$quotient] = $num1Big->multiply($temp)->divide($num2Big);

        // Phase 5: Result formatting
        $formatted = self::format($quotient, $scale, $scale);

        return self::normalizeZeroResult($formatted);
    }

    /**
     * Get modulus of an arbitrary precision number.
     *
     * Uses the PHP 7.2+ behavior
     *
     * @throws \DivisionByZeroError When divisor is zero
     * @throws \ValueError if inputs are not well-formed
     */
    public static function mod(string $num1, string $num2, ?int $scale = null): string
    {
        // Phase 1: Argument validation
        [$num1, $num2] = self::validateAndNormalizeInputs($num1, $num2, 'bcmod');

        // Phase 2: Scale resolution and validation
        $scale = self::resolveScale($scale);
        self::validateScale($scale, 'bcmod', 3);

        // Phase 3: Division by zero check and number processing
        self::checkDivisionByZero($num2);
        [$num1Big, $num2Big, $maxPad] = self::prepareBigIntegerInputs($num1, $num2);

        // Phase 4: Calculation execution
        [$quotient] = $num1Big->divide($num2Big);
        $remainder = $num2Big->multiply($quotient);
        $result = $num1Big->subtract($remainder);

        // Phase 5: Result formatting
        return self::formatFinalResult($result, $scale, $maxPad);
    }

    /**
     * Compare two arbitrary precision numbers.
     *
     * @throws \ValueError if inputs are not well-formed
     */
    public static function comp(string $num1, string $num2, ?int $scale = null): int
    {
        // Phase 1: Argument validation
        [$num1, $num2] = self::validateAndNormalizeInputs($num1, $num2, 'bccomp');

        // Phase 2: Scale resolution and validation
        $scale = self::resolveScaleForComparison($scale);
        self::validateScale($scale, 'bccomp', 3);

        // Phase 3: Number processing
        [$num1Big, $num2Big] = self::prepareForComparison($num1, $num2, $scale);

        // Phase 4: Calculation execution
        return $num1Big->compare($num2Big);
    }

    /**
     * Raise an arbitrary precision number to another.
     *
     * Uses the PHP 7.2+ behavior
     *
     * @throws \ValueError When exponent is too large for integer range
     */
    public static function pow(string $base, string $exponent, ?int $scale = null): string
    {
        // Phase 1: Argument validation
        self::validateNumberString($base, 'bcpow', 1, 'base');
        self::validateNumberString($exponent, 'bcpow', 2, 'exponent');

        // Phase 2: Scale resolution and validation
        $scale = self::resolveScale($scale);
        self::validateScale($scale, 'bcpow', 3);

        // Phase 3: Early special case handling
        if ($exponent === self::DEFAULT_NUMBER || $exponent === '-0' || $exponent === '-0.0') {
            $result = '1';
            if ($scale !== 0) {
                $result .= '.'.str_repeat('0', $scale);
            }

            return $result;
        }

        // Normalize inputs
        [$base, $exponent] = self::validateAndNormalizeInputs($base, $exponent, 'bcpow');

        // Handle special case: 0 to any power
        if (self::isZero($base)) {
            // Check for negative power of zero - PHP 8.4+ behavior
            if (self::isNegative(new BigInteger($exponent))) {
                throw new \DivisionByZeroError('Negative power of zero');
            }

            $result = '0';
            if ($scale !== 0) {
                $result .= '.'.str_repeat('0', $scale);
            }

            return $result;
        }

        // Validate exponent range
        $min = defined('PHP_INT_MIN') ? PHP_INT_MIN : ~PHP_INT_MAX;
        if (self::comp($exponent, (string) PHP_INT_MAX) > 0 || self::comp($exponent, (string) $min) < 0) {
            throw new \ValueError('bcpow(): Argument #2 ($exponent) is too large');
        }

        // Phase 4: Number processing
        $baseParts = explode('.', $base);
        if (!isset($baseParts[1])) {
            $baseParts[1] = '';
        }

        // Pad decimal parts
        $maxPad = strlen($baseParts[1]);
        $baseParts[1] = str_pad($baseParts[1], $maxPad, '0');

        // Convert to BigInteger for calculation
        $baseBig = new BigInteger($baseParts[0].$baseParts[1]);

        $baseIsNegative = self::isNegative($baseBig);
        $baseBig = $baseBig->abs();

        // Phase 5: Calculation execution
        $r = new BigInteger(1);
        $exponentBig = new BigInteger($exponent);
        $absExponent = self::isNegative($exponentBig) ? substr($exponent, 1) : $exponent;
        for ($i = 0; $i < $absExponent; $i++) {
            $r = $r->multiply($baseBig);
        }

        // Phase 5: Result formatting
        if ($exponent < 0) {
            $temp = '1'.str_repeat('0', $scale + $maxPad * (int) $absExponent);
            $temp = new BigInteger($temp);
            [$r] = $temp->divide($r);
            $finalPad = $scale;
        } else {
            $finalPad = $maxPad * (int) $absExponent;
        }

        // Format the result first
        $result = self::format($r, $scale, $finalPad);

        // Determine if we should apply negative sign
        // For negative base: negative sign only if exponent is odd integer
        if ($baseIsNegative) {
            // Check if exponent is an odd integer
            $exponentIsOddInteger = false;
            if (!str_contains($exponent, '.') || rtrim(substr($exponent, strpos($exponent, '.')), '0') === '.') {
                $exponentInt = (int) $exponent;
                $exponentIsOddInteger = ($exponentInt % 2 !== 0);
            }

            // Apply negative sign only if:
            // 1. Exponent is odd integer
            // 2. Result is not effectively zero
            if ($exponentIsOddInteger && !self::isZero($result)) {
                $result = '-'.$result;
            }
        }

        return $result;
    }

    /**
     * Raise an arbitrary precision number to another, reduced by a specified modulus.
     *
     * @throws \ArgumentCountError When more than 4 arguments provided
     * @throws \ValueError When exponent is negative or modulus is zero
     */
    public static function powmod(string $base, string $exponent, string $modulus, ?int $scale = null): string
    {
        // Phase 1: Argument validation
        if (func_num_args() > 4) {
            throw new \ArgumentCountError('bcpowmod() expects at most 4 arguments, '.func_num_args().' given');
        }

        self::validateNumberString($base, 'bcpowmod', 1, 'base');
        self::validateNumberString($exponent, 'bcpowmod', 2, 'exponent');
        self::validateNumberString($modulus, 'bcpowmod', 3, 'modulus');

        // Phase 2: Scale resolution and validation
        if ($scale === null) {
            $scale = 0;
        }
        self::validateScale($scale, 'bcpowmod', 4);

        // Phase 3: Number processing and validation
        [$baseInt, $exponentInt, $modulusInt] = self::validateIntegerInputs(
            [$base, $exponent, $modulus],
            ['bcpowmod(): Argument #1 ($base)', 'bcpowmod(): Argument #2 ($exponent)', 'bcpowmod(): Argument #3 ($modulus)'],
            [
                'non_negative' => [1], // exponent must be non-negative
                'non_zero' => [2],      // modulus cannot be zero
            ]
        );
        if ($exponentInt === self::DEFAULT_NUMBER) {
            return $scale !== 0
                ? '1.'.str_repeat('0', $scale)
                : '1';
        }

        // Phase 4: Calculation execution
        $x = new BigInteger($baseInt);
        $e = new BigInteger($exponentInt);
        $n = new BigInteger($modulusInt);

        $z = $x->powMod($e, $n);

        // Phase 5: Result formatting
        return $scale !== 0
            ? "{$z}.".str_repeat('0', $scale)
            : "{$z}";
    }

    /**
     * Get the square root of an arbitrary precision number.
     *
     * @throws \ValueError if inputs are not well-formed
     */
    public static function sqrt(string $num, ?int $scale = null): string
    {
        // the following is based off of the following URL:
        // https://en.wikipedia.org/wiki/Methods_of_computing_square_roots#Decimal_(base_10)

        // Argument validation
        self::validateNumberString($num, 'bcsqrt', 1, 'num');

        // Use default scale if not provided (needed for early zero return)
        if ($scale === null) {
            if (!isset(self::$scale)) {
                $defaultScale = ini_get('bcmath.scale');
                self::$scale = $defaultScale !== false ? max((int) $defaultScale, 0) : 0;
            }
            $scale = self::$scale;
        }
        self::validateScale($scale, 'bcsqrt', 2);

        // Check for negative numbers (except negative zero)
        if (self::startsWithNegativeSign($num) && !self::isZero($num)) {
            throw new \ValueError('bcsqrt(): Argument #1 ($num) must be greater than or equal to 0');
        }

        // Handle zero case early (including negative zero)
        if (self::isZero($num)) {
            return $scale !== 0 ? '0.'.str_repeat('0', $scale) : '0';
        }

        $temp = explode('.', $num);
        $integerPart = $temp[0];
        $decimalPart = $temp[1] ?? '';

        // Special handling for numbers < 1
        $leadingZeroPairs = 0;
        $skipIntegerPart = false;
        if ($integerPart === '0' && $decimalPart !== '') {
            $skipIntegerPart = true;
            // Count leading zeros in decimal part
            $leadingZeros = strspn($decimalPart, '0');
            // For decimals < 1, each pair of leading zeros in the input produces one leading zero in the result.
            $leadingZeroPairs = (int) floor($leadingZeros / 2);

            // Now we need to create proper pairs from the decimal part
            // If odd number of leading zeros, the last zero pairs with first non-zero digit
            if ($leadingZeros % 2 === 1) {
                // Skip the paired zeros, keep the odd zero with remaining digits
                $decimalPart = substr($decimalPart, $leadingZeros - 1);
            } else {
                // Skip all the leading zeros as they're all paired
                $decimalPart = substr($decimalPart, $leadingZeros);
            }

            // Now pad the decimal part if needed
            if (strlen($decimalPart) % 2 !== 0) {
                $decimalPart .= '0';
            }

            // For numbers < 1, we only process the decimal part
            $numStr = $decimalPart;
        } else {
            // For numbers >= 1, process normally
            // Pad integer part on the left if odd length
            if (strlen($integerPart) % 2 !== 0) {
                $integerPart = '0'.$integerPart;
            }

            // Pad decimal part on the right if odd length
            if (strlen($decimalPart) % 2 !== 0) {
                $decimalPart .= '0';
            }

            // Create combined string
            $numStr = $integerPart.$decimalPart;
        }

        // Calculate how many digits the integer part of the result should have
        // For numbers >= 1: ceil(n/2) where n is the number of integer digits
        // For numbers < 1: 0 (the result will also be < 1)
        $integerResultDigits = ($temp[0] === '0') ? 0 : (int) ceil(strlen($temp[0]) / 2);

        // Create array of digit pairs
        $parts = str_split($numStr, 2);
        $parts = array_map('intval', $parts);

        $i = 0;
        $p = 0; // for the first step, p = 0
        $c = $parts[$i];
        $result = '';
        $digitCount = 0; // Track how many result digits we've generated

        while (true) {
            // determine the greatest digit x such that x(20p+x) <= c
            for ($x = 1; $x <= 10; $x++) {
                if ($x * (20 * $p + $x) > $c) {
                    $x--;

                    break;
                }
            }
            $result .= $x;
            $digitCount++;

            // Add decimal point after we've generated all integer digits
            if ($digitCount === $integerResultDigits && $scale > 0) {
                $result .= '.';
            }

            $y = $x * (20 * $p + $x);
            $p = 10 * $p + $x;
            $c = 100 * ($c - $y);
            if (isset($parts[++$i])) {
                $c += $parts[$i];
            }

            // Check if we should stop
            $decimalDigits = $digitCount - $integerResultDigits;
            if ((!$c && $digitCount >= $integerResultDigits) || ($decimalDigits >= $scale && $scale >= 0)) {
                break;
            }
        }

        // For numbers < 1, format the result properly
        if ($integerResultDigits === 0) {
            // If scale is 0 and result would be < 1, return '0'
            if ($scale === 0) {
                return '0';
            }

            if ($leadingZeroPairs > 0) {
                // Result should be 0.{leadingZeroPairs zeros}{result}
                $result = '0.'.str_repeat('0', $leadingZeroPairs).$result;
            } else {
                // No leading zeros, but still < 1, so add '0.' prefix
                $result = '0.'.$result;
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
     *
     * @throws \ValueError if inputs are not well-formed
     */
    public static function floor(string $num): string
    {
        return self::normalizeZeroForRounding($num, 'bcfloor', static function (string $num, string $integerPart, string $fractionalPart): ?string {
            // For negative numbers with fractional parts, we need to subtract 1
            if (self::startsWithNegativeSign($num)) {
                return self::sub($integerPart, '1', 0);
            }

            return null; // Let the common logic handle this case
        });
    }

    /**
     * Round up to the nearest integer.
     *
     * @throws \ValueError if inputs are not well-formed
     */
    public static function ceil(string $num): string
    {
        return self::normalizeZeroForRounding($num, 'bcceil', static function (string $num, string $integerPart, string $fractionalPart): ?string {
            // For positive numbers with fractional parts, we need to add 1
            if (!self::startsWithNegativeSign($num)) {
                $integerPart = $integerPart === '' ? '0' : $integerPart;

                return self::add($integerPart, '1', 0);
            }

            return null; // Let the common logic handle this case
        });
    }

    /**
     * Round to a given decimal place.
     *
     * @param string $num The value to round
     * @param int $precision The number of decimal digits to round to
     * @param int|\RoundingMode $mode The rounding mode (PHP8.4+ supports RoundingMode enum)
     *
     * @throws \ValueError if inputs are not well-formed
     */
    public static function round(string $num, int $precision = 0, $mode = PHP_ROUND_HALF_UP): string
    {
        self::validateNumberString($num, 'bcround', 1, 'num');

        if (!is_numeric($num)) {
            if (version_compare(PHP_VERSION, '8.4', '>=')) {
                throw new \ValueError('bcround(): Argument #1 ($num) is not well-formed');
            }
            trigger_error('bcround(): Argument #1 ($num) is not well-formed', E_USER_WARNING);

            return '0';
        }

        // Convert RoundingMode enum to integer constant for PHP 8.4+ compatibility
        $roundingMode = self::convertRoundingMode($mode);

        // Based on: https://stackoverflow.com/a/1653826
        if ($precision < 0) {
            // When precision is negative, we round to the left of the decimal point
            $absPrecision = abs($precision);
            $factor = self::pow('10', (string) $absPrecision, max($absPrecision, 0));
            $shifted = self::div($num, $factor, 10); // Use a high precision for intermediate calculation

            // Apply rounding
            $rounded = self::bcroundHelper($shifted, 0, $roundingMode);

            // Shift back
            return self::mul($rounded, $factor, 0);
        }

        return self::bcroundHelper($num, $precision, $roundingMode);
    }

    /**
     * Convert RoundingMode enum to integer constant for backward compatibility.
     *
     * Note: Parameter type declaration is intentionally omitted to prevent PHP's type coercion.
     * With int|\RoundingMode type hint, float values like 1.5 would be auto-converted to int (1),
     * preventing proper validation and exception throwing for invalid types.
     *
     * @param int|\RoundingMode $mode
     *
     * @return int The corresponding PHP_ROUND_* constant
     *
     * @throws \ValueError If an invalid rounding mode is provided
     */
    private static function convertRoundingMode($mode): int
    {
        // RoundingMode enum support (both native PHP 8.4+ and polyfill PHP 8.1-8.3)
        if (enum_exists('RoundingMode') && $mode instanceof \RoundingMode) {
            return match ($mode) {
                \RoundingMode::HalfAwayFromZero => PHP_ROUND_HALF_UP,
                \RoundingMode::HalfTowardsZero => PHP_ROUND_HALF_DOWN,
                \RoundingMode::HalfEven => PHP_ROUND_HALF_EVEN,
                \RoundingMode::HalfOdd => PHP_ROUND_HALF_ODD,
                // TODO: Support additional modes if needed
                \RoundingMode::NegativeInfinity => throw new \ValueError('RoundingMode::NegativeInfinity is not supported'),
                \RoundingMode::TowardsZero => throw new \ValueError('RoundingMode::TowardsZero is not supported'),
                \RoundingMode::AwayFromZero => throw new \ValueError('RoundingMode::AwayFromZero is not supported'), // @phpstan-ignore-line
                default => throw new \ValueError('Unsupported RoundingMode')
            };
        }

        // Backward compatibility for PHP_ROUND_* constants
        if (is_int($mode)) {
            return $mode;
        }

        throw new \ValueError('Invalid rounding mode provided');
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
        if (self::startsWithNegativeSign($number)) {
            $sign = '-';
            $trimmedNumber = ltrim($number);
            $number = substr($trimmedNumber, 1);
        } else {
            $number = ltrim($number);
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
                if ($digit === 5 && (!isset($dec[$precision + 1]) || ltrim(substr($dec, $precision + 1), '0') === '')) {
                    // Exactly 0.5, don't round up
                } elseif ($digit > 5 || ($digit === 5 && ltrim(substr($dec, $precision + 1), '0') !== '')) {
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
            if ($result === self::DEFAULT_NUMBER || $result === self::DEFAULT_NUMBER.'.' || preg_match('/^0\.0+$/', $result)) {
                $result = $precision > 0 ? '0.'.str_repeat('0', $precision) : '0';
            }
        }

        return $result;
    }
}
