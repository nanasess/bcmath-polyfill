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

use bcmath_compat\Math\BigInteger;

/**
 * BCMath Emulation Class.
 *
 * Provides arbitrary precision arithmetic operations using phpseclib's BigInteger
 * (phpseclib 3 or 4, resolved through the bcmath_compat\Math\BigInteger alias).
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
    private const MODULO_BY_ZERO_MESSAGE = 'Modulo by zero';

    /**
     * Canonical rounding-mode tokens shared by the string-digit rounding core.
     * They mirror the names of PHP 8.4's RoundingMode enum cases.
     */
    private const ROUND_HALF_AWAY_FROM_ZERO = 'HalfAwayFromZero';
    private const ROUND_HALF_TOWARDS_ZERO = 'HalfTowardsZero';
    private const ROUND_HALF_EVEN = 'HalfEven';
    private const ROUND_HALF_ODD = 'HalfOdd';
    private const ROUND_TOWARDS_ZERO = 'TowardsZero';
    private const ROUND_AWAY_FROM_ZERO = 'AwayFromZero';
    private const ROUND_NEGATIVE_INFINITY = 'NegativeInfinity';
    private const ROUND_POSITIVE_INFINITY = 'PositiveInfinity';

    /**
     * Validate and normalize two input numbers.
     *
     * Returns both numbers in the canonical form of self::normalizeNumber().
     * The scale must be validated before calling this, because the native
     * extension reports an invalid scale before a malformed number.
     *
     * @return string[] Array containing normalized [$num1, $num2]
     *
     * @throws \ValueError if inputs are not well-formed
     */
    protected static function validateAndNormalizeInputs(string $num1, string $num2, string $function): array
    {
        return [
            self::normalizeNumber($num1, $function, 1, 'num1'),
            self::normalizeNumber($num2, $function, 2, 'num2'),
        ];
    }

    /**
     * Resolve the scale parameter, using default if null.
     *
     * Implements Phase 2 of the standard 5-phase processing pattern.
     * Uses bcmath.scale INI setting as fallback when no scale is provided.
     *
     * When the native bcmath extension is not loaded, PHP does not register the
     * 'bcmath.scale' INI entry, so ini_get('bcmath.scale') returns false. The value
     * given in php.ini or with -d is still readable through get_cfg_var(), so it is
     * used as the initial scale. ini_set('bcmath.scale', ...) at runtime cannot be
     * supported; use bcscale() instead.
     */
    protected static function resolveScale(?int $scale = null): int
    {
        if ($scale === null) {
            self::$scale ??= self::defaultScaleFromIni();
            $scale = self::$scale;
        }

        return $scale;
    }

    /**
     * Read the initial scale from the bcmath.scale INI setting.
     *
     * The value is parsed like the native extension (zend_ini_parse_quantity()), so
     * "010" is 8, "0x10" is 16 and "1k" is 1024. A value outside 0..2147483647 is
     * rejected and the default 0 is kept. The native extension warns about an
     * invalid value at startup; the polyfill reads it lazily, so it stays silent.
     *
     * ini_parse_quantity() is available on PHP 8.2+. On PHP 8.1 only plain decimal
     * integers are accepted, and anything else falls back to 0.
     */
    private static function defaultScaleFromIni(): int
    {
        $value = ini_get('bcmath.scale');
        if ($value === false) {
            $value = get_cfg_var('bcmath.scale');
        }
        if (!is_string($value)) {
            return 0;
        }

        if (function_exists('ini_parse_quantity')) {
            $scale = @ini_parse_quantity($value);
            // On PHP 8.1 PHPStan does not know this function and types the result as mixed,
            // while on PHP 8.2+ it reports this guard as always-true. Keep it for 8.1.
            if (!is_int($scale)) { // @phpstan-ignore function.alreadyNarrowedType
                return 0;
            }
        } elseif (preg_match('/^\s*\+?0*(\d{1,10})\s*$/D', $value, $matches) === 1) {
            $digits = $matches[1];
            // Compare as strings before the (int) cast, which saturates to 2147483647 on 32-bit PHP
            if (strlen($digits) === 10 && strcmp($digits, '2147483647') > 0) {
                return 0;
            }
            $scale = (int) $digits;
        } else {
            return 0;
        }

        return $scale >= 0 && $scale <= 2147483647 ? $scale : 0;
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
     * Validate a number argument and return it in canonical form.
     *
     * Accepts what the native extension accepts: an optional sign, integer
     * digits and an optional decimal point followed by fraction digits, where
     * either run of digits may be empty ('', '.', '-', '+.5' and '5.' are all
     * valid). A NUL byte is rejected, although the native extension, which
     * reads the argument as a C string, ignores anything from the first NUL on.
     *
     * The canonical form is '-'?(0|[1-9][0-9]*)('.'[0-9]+)?: the '+' sign and
     * the leading zeros of the integer part are removed, an empty integer part
     * becomes '0', and zero never has a '-' sign. The fraction digits are kept
     * as given, including trailing zeros.
     *
     * @param string $num Number string to validate
     * @param string $function Function name for error message
     * @param int $argNumber Argument number for error message
     * @param string $argName Argument name for error message
     *
     * @throws \ValueError if number is not well-formed
     */
    private static function normalizeNumber(string $num, string $function, int $argNumber, string $argName): string
    {
        if (preg_match('/^([+-]?)(\d*)(?:\.(\d*))?$/D', $num, $matches) !== 1) {
            throw new \ValueError("{$function}(): Argument #{$argNumber} (\${$argName}) is not well-formed");
        }

        $integer = ltrim($matches[2], '0');
        if ($integer === '') {
            $integer = '0';
        }
        $fraction = $matches[3] ?? '';
        $isZero = $integer === '0' && rtrim($fraction, '0') === '';
        $sign = $matches[1] === '-' && !$isZero ? '-' : '';

        return $sign.$integer.($fraction !== '' ? '.'.$fraction : '');
    }

    /**
     * The integer value of a normalized number, which must not have a fractional part.
     *
     * A fraction of zeros is accepted, as by the native extension on PHP 8.3+
     * (the polyfill follows PHP 8.4+ on every version, see #110).
     *
     * @param string $num Number in the canonical form of self::normalizeNumber()
     * @param string $function Function name for error message
     * @param int $argNumber Argument number for error message
     * @param string $argName Argument name for error message
     *
     * @return string '-'?(0|[1-9][0-9]*), without '-' for zero
     *
     * @throws \ValueError if the number has a non-zero fractional part
     */
    private static function integerPart(string $num, string $function, int $argNumber, string $argName): string
    {
        [$integer, $fraction] = self::parseDecimalNumber($num);
        if (rtrim($fraction, '0') !== '') {
            throw new \ValueError("{$function}(): Argument #{$argNumber} (\${$argName}) cannot have a fractional part");
        }

        // A normalized zero has no sign, so the sign of $integer is already right.
        return $integer;
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

        $parts[1] ??= '';

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
        $maxPad = max(strlen($num1Dec), strlen($num2Dec));
        $num1Dec = str_pad($num1Dec, $maxPad, '0');
        $num2Dec = str_pad($num2Dec, $maxPad, '0');

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
        if ($result !== '' && $result[0] === '-' && strspn($result, '0.', 1) === strlen($result) - 1) {
            return substr($result, 1);
        }

        return $result;
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
     * Check for modulo by zero.
     *
     * @throws \DivisionByZeroError If divisor is zero
     */
    private static function checkModuloByZero(string $divisor): void
    {
        if (self::isZero($divisor)) {
            throw new \DivisionByZeroError(self::MODULO_BY_ZERO_MESSAGE);
        }
    }

    /**
     * Compare two normalized numbers on their digit strings, without big integer arithmetic.
     *
     * Fraction digits beyond $scale are ignored, so a number that is zero within
     * the scale compares as zero whatever its sign ('-0.001' equals '0' at scale 2).
     *
     * @param string $num1 Number in the canonical form of self::normalizeNumber()
     * @param string $num2 Number in the canonical form of self::normalizeNumber()
     *
     * @return int -1, 0 or 1
     */
    private static function compareNormalized(string $num1, string $num2, int $scale): int
    {
        [$negative1, $integer1, $fraction1] = self::splitForComparison($num1, $scale);
        [$negative2, $integer2, $fraction2] = self::splitForComparison($num2, $scale);

        if ($negative1 !== $negative2) {
            return $negative1 ? -1 : 1;
        }

        // The integer parts have no leading zeros, so the longer one is larger. The
        // fractions have no trailing zeros, so their byte order is their numeric order.
        // Digit strings are compared with strcmp(), because <=> would compare them as
        // numbers (and lose precision beyond a float).
        $magnitude = strlen($integer1) <=> strlen($integer2);
        if ($magnitude === 0) {
            $magnitude = strcmp($integer1, $integer2) <=> 0;
        }
        if ($magnitude === 0) {
            $magnitude = strcmp($fraction1, $fraction2) <=> 0;
        }

        return $negative1 ? -$magnitude : $magnitude;
    }

    /**
     * Split a normalized number for self::compareNormalized().
     *
     * @param string $num Number in the canonical form of self::normalizeNumber()
     *
     * @return array{0: bool, 1: string, 2: string} whether the number is negative within
     *                                               the scale, its integer digits, and its
     *                                               fraction digits up to $scale without trailing zeros
     */
    private static function splitForComparison(string $num, int $scale): array
    {
        $negative = $num[0] === '-';
        if ($negative) {
            $num = substr($num, 1);
        }

        $point = strpos($num, '.');
        if ($point === false) {
            return [$negative, $num, ''];
        }

        $integer = substr($num, 0, $point);
        $fraction = rtrim(substr($num, $point + 1, $scale), '0');

        return [$negative && ($integer !== '0' || $fraction !== ''), $integer, $fraction];
    }

    /**
     * Validate the argument for bcfloor()/bcceil() and round it to an integer
     * using the shared string-digit rounding core.
     *
     * @param string $num The number to process
     * @param string $functionName Function name for error messages
     * @param string $mode Canonical rounding mode token (self::ROUND_NEGATIVE_INFINITY or self::ROUND_POSITIVE_INFINITY)
     *
     * @return string The processed result
     */
    private static function roundToInteger(string $num, string $functionName, string $mode): string
    {
        $num = self::normalizeNumber($num, $functionName, 1, 'num');

        return self::roundDigits($num, 0, $mode);
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

        // Like the native bcscale(), return the scale before the change
        $oldScale = self::resolveScale();

        if ($scale !== null) {
            self::validateScale($scale, 'bcscale', 1);
            self::$scale = $scale;
        }

        return $oldScale;
    }

    /**
     * Formats numbers.
     *
     * Places the decimal place at the appropriate place, adds trailing 0's as appropriate, etc
     */
    public static function format(BigInteger $x, ?int $scale = null, int $pad = 0): string
    {
        $digits = (string) $x;
        $sign = '';
        if ($digits[0] === '-') {
            $sign = '-';
            $digits = substr($digits, 1);
        }

        if ($pad <= 0) {
            return $scale ? $sign.$digits.'.'.str_repeat('0', $scale) : $sign.$digits;
        }

        if (strlen($digits) <= $pad) {
            // No integer digits: the integer part is zero
            $integer = '0';
            $fraction = str_pad($digits, $pad, '0', STR_PAD_LEFT);
        } else {
            $integer = substr($digits, 0, -$pad);
            $fraction = substr($digits, -$pad);
        }

        // A null scale keeps every fraction digit
        if ($scale !== null) {
            $fraction = $pad > $scale ? substr($fraction, 0, $scale) : str_pad($fraction, $scale, '0');
        }

        return $fraction !== '' ? $sign.$integer.'.'.$fraction : $sign.$integer;
    }

    /**
     * Negativity Test.
     */
    public static function isNegative(BigInteger $x): bool
    {
        return $x->isNegative();
    }

    /**
     * Add two arbitrary precision numbers.
     *
     * @throws \ValueError if inputs are not well-formed
     */
    public static function add(string $num1, string $num2, ?int $scale = null): string
    {
        // Phase 1: Scale resolution and validation (the native extension checks the scale first)
        $scale = self::resolveScale($scale);
        self::validateScale($scale, 'bcadd', 3);

        // Phase 2: Argument validation and normalization
        [$num1, $num2] = self::validateAndNormalizeInputs($num1, $num2, 'bcadd');

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
        // Phase 1: Scale resolution and validation (the native extension checks the scale first)
        $scale = self::resolveScale($scale);
        self::validateScale($scale, 'bcsub', 3);

        // Phase 2: Argument validation and normalization
        [$num1, $num2] = self::validateAndNormalizeInputs($num1, $num2, 'bcsub');

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
        // Phase 1: Scale resolution and validation (the native extension checks the scale first)
        $scale = self::resolveScale($scale);
        self::validateScale($scale, 'bcmul', 3);

        // Phase 2: Argument validation and normalization
        [$num1, $num2] = self::validateAndNormalizeInputs($num1, $num2, 'bcmul');

        // Phase 3: Early zero check and number processing
        $earlyZero = self::checkEarlyZero($num1, $num2, $scale);
        if ($earlyZero !== null) {
            return $earlyZero;
        }
        [$num1Big, $num2Big, $maxPad] = self::prepareBigIntegerInputs($num1, $num2);

        // Phase 4: Calculation execution
        $result = $num1Big->multiply($num2Big);

        // Phase 5: Result formatting
        return self::formatFinalResult($result, $scale, 2 * $maxPad);
    }

    /**
     * Divide two arbitrary precision numbers.
     *
     * @throws \DivisionByZeroError When divisor is zero
     * @throws \ValueError if inputs are not well-formed
     */
    public static function div(string $num1, string $num2, ?int $scale = null): string
    {
        // Phase 1: Scale resolution and validation (the native extension checks the scale first)
        $scale = self::resolveScale($scale);
        self::validateScale($scale, 'bcdiv', 3);

        // Phase 2: Argument validation and normalization
        [$num1, $num2] = self::validateAndNormalizeInputs($num1, $num2, 'bcdiv');

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
        // Phase 1: Scale resolution and validation (the native extension checks the scale first)
        $scale = self::resolveScale($scale);
        self::validateScale($scale, 'bcmod', 3);

        // Phase 2: Argument validation and normalization
        [$num1, $num2] = self::validateAndNormalizeInputs($num1, $num2, 'bcmod');

        // Phase 3: Division by zero check and number processing
        self::checkModuloByZero($num2);
        [$num1Big, $num2Big, $maxPad] = self::prepareBigIntegerInputs($num1, $num2);

        // Phase 4: Calculation execution
        [$quotient] = $num1Big->divide($num2Big);
        $remainder = $num2Big->multiply($quotient);
        $result = $num1Big->subtract($remainder);

        // Phase 5: Result formatting
        return self::formatFinalResult($result, $scale, $maxPad);
    }

    /**
     * Get the quotient and remainder of an arbitrary precision division.
     *
     * The quotient is always computed at scale 0 (integer division) and the
     * remainder honours $scale, matching PHP 8.4's native bcdivmod(). Because it
     * is built on self::div()/self::mod(), it works without the native bcmath
     * extension (unlike symfony/polyfill-php84, which delegates to native bc).
     *
     * @return string[] the quotient (index 0) and remainder (index 1)
     *
     * @throws \DivisionByZeroError When divisor is zero
     * @throws \ValueError if inputs are not well-formed
     */
    public static function divmod(string $num1, string $num2, ?int $scale = null): array
    {
        // Validate operands and scale under the bcdivmod() name for correct messages.
        $scale = self::resolveScale($scale);
        self::validateScale($scale, 'bcdivmod', 3);
        [$num1, $num2] = self::validateAndNormalizeInputs($num1, $num2, 'bcdivmod');
        self::checkDivisionByZero($num2);

        // Divide once for the quotient, then derive the remainder as
        // num1 - num2 * quotient. This avoids the second division that separate
        // div()/mod() calls would perform, and matches bcmod()'s truncated
        // remainder convention (phpseclib's divide() returns a non-negative
        // remainder, which differs for negative operands).
        [$num1Big, $num2Big, $maxPad] = self::prepareBigIntegerInputs($num1, $num2);
        [$quotient] = $num1Big->divide($num2Big);
        $remainder = $num1Big->subtract($num2Big->multiply($quotient));

        return [
            self::normalizeZeroResult(self::format($quotient, 0, 0)),
            self::formatFinalResult($remainder, $scale, $maxPad),
        ];
    }

    /**
     * Compare two arbitrary precision numbers.
     *
     * @throws \ValueError if inputs are not well-formed
     */
    public static function comp(string $num1, string $num2, ?int $scale = null): int
    {
        // Phase 1: Scale resolution and validation (the native extension checks the scale first)
        $scale = self::resolveScale($scale);
        self::validateScale($scale, 'bccomp', 3);

        // Phase 2: Argument validation and normalization
        [$num1, $num2] = self::validateAndNormalizeInputs($num1, $num2, 'bccomp');

        // Phase 3: Comparison of the digit strings
        return self::compareNormalized($num1, $num2, $scale);
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
        // Phase 1: Scale resolution and validation (the native extension checks the scale first)
        $scale = self::resolveScale($scale);
        self::validateScale($scale, 'bcpow', 3);

        // Phase 2: Argument validation and normalization
        $base = self::normalizeNumber($base, 'bcpow', 1, 'num');
        $exponent = self::integerPart(self::normalizeNumber($exponent, 'bcpow', 2, 'exponent'), 'bcpow', 2, 'exponent');

        // Phase 3: Early special case handling
        if ($exponent === self::DEFAULT_NUMBER) {
            $result = '1';
            if ($scale !== 0) {
                $result .= '.'.str_repeat('0', $scale);
            }

            return $result;
        }

        // Validate exponent range before looking at the base, like the native extension.
        // Its absolute value must fit in an int, so PHP_INT_MIN is rejected too.
        $absExponent = ltrim($exponent, '-');
        if (strlen($absExponent) > strlen((string) PHP_INT_MAX)
            || (strlen($absExponent) === strlen((string) PHP_INT_MAX) && strcmp($absExponent, (string) PHP_INT_MAX) > 0)) {
            throw new \ValueError('bcpow(): Argument #2 ($exponent) is too large');
        }

        // Handle special case: 0 to any power
        if (self::isZero($base)) {
            // Check for negative power of zero - PHP 8.4+ behavior
            if (str_starts_with($exponent, '-')) {
                throw new \DivisionByZeroError('Negative power of zero');
            }

            $result = '0';
            if ($scale !== 0) {
                $result .= '.'.str_repeat('0', $scale);
            }

            return $result;
        }

        // Phase 4: Number processing
        $baseParts = explode('.', $base);
        $baseParts[1] ??= '';

        // Pad decimal parts
        $maxPad = strlen($baseParts[1]);
        $baseParts[1] = str_pad($baseParts[1], $maxPad, '0');

        // Convert to BigInteger for calculation
        $baseBig = new BigInteger($baseParts[0].$baseParts[1]);

        $baseIsNegative = $base[0] === '-';
        $baseBig = $baseBig->abs();

        // Phase 5: Calculation execution
        // Square-and-multiply: about 2 * log2(exponent) multiplications instead of one
        // per unit of the exponent. The exponent fits in an int (checked above).
        $r = new BigInteger(1);
        $square = $baseBig;
        for ($n = (int) $absExponent; $n > 0; $n >>= 1) {
            if (($n & 1) === 1) {
                $r = $r->multiply($square);
            }
            if ($n > 1) {
                $square = $square->multiply($square);
            }
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
        // A negative base gives a negative result for an odd exponent, unless the result is zero.
        if ($baseIsNegative && self::lastDigitValue($absExponent) % 2 === 1 && !self::isZero($result)) {
            $result = '-'.$result;
        }

        return $result;
    }

    /**
     * Raise an arbitrary precision number to another, reduced by a specified modulus.
     *
     * @throws \ArgumentCountError When more than 4 arguments provided
     * @throws \ValueError When an argument has a fractional part or the exponent is negative
     * @throws \DivisionByZeroError When the modulus is zero
     */
    public static function powmod(string $base, string $exponent, string $modulus, ?int $scale = null): string
    {
        // Phase 1: Argument validation
        if (func_num_args() > 4) {
            throw new \ArgumentCountError('bcpowmod() expects at most 4 arguments, '.func_num_args().' given');
        }

        // The native extension checks the scale before the numbers.
        $scale = self::resolveScale($scale);
        self::validateScale($scale, 'bcpowmod', 4);

        $base = self::normalizeNumber($base, 'bcpowmod', 1, 'num');
        $exponent = self::normalizeNumber($exponent, 'bcpowmod', 2, 'exponent');
        $modulus = self::normalizeNumber($modulus, 'bcpowmod', 3, 'modulus');

        // The native extension checks the arguments in order: each must be an
        // integer, the exponent must not be negative and the modulus not zero.
        $baseInt = self::integerPart($base, 'bcpowmod', 1, 'num');
        $exponentInt = self::integerPart($exponent, 'bcpowmod', 2, 'exponent');
        if (str_starts_with($exponentInt, '-')) {
            throw new \ValueError('bcpowmod(): Argument #2 ($exponent) must be greater than or equal to 0');
        }
        $modulusInt = self::integerPart($modulus, 'bcpowmod', 3, 'modulus');
        if ($modulusInt === self::DEFAULT_NUMBER) {
            throw new \DivisionByZeroError(self::MODULO_BY_ZERO_MESSAGE);
        }

        // Phase 4: Calculation execution
        // The result is |x|^e mod |n|, with the sign of x^e (the remainder of a
        // truncated division), so the sign of the modulus does not matter.
        // Anything mod ±1 is 0, even for a zero exponent.
        if (ltrim($modulusInt, '-') === '1') {
            $z = self::DEFAULT_NUMBER;
        } elseif ($exponentInt === self::DEFAULT_NUMBER) {
            $z = '1';
        } else {
            $x = new BigInteger($baseInt);
            $e = new BigInteger($exponentInt);
            $n = new BigInteger(ltrim($modulusInt, '-'));
            $z = (string) $x->abs()->powMod($e, $n);
            if ($x->isNegative() && $e->isOdd() && $z !== self::DEFAULT_NUMBER) {
                $z = '-'.$z;
            }
        }

        // Phase 5: Result formatting
        return $scale !== 0
            ? "{$z}.".str_repeat('0', $scale)
            : "{$z}";
    }

    /**
     * Get the square root of an arbitrary precision number.
     *
     * The result is truncated to $scale digits, like the native extension:
     * floor(sqrt(num) * 10^scale) / 10^scale.
     *
     * @throws \ValueError if inputs are not well-formed or the number is negative
     */
    public static function sqrt(string $num, ?int $scale = null): string
    {
        // The native extension checks the scale before the number.
        $scale = self::resolveScale($scale);
        self::validateScale($scale, 'bcsqrt', 2);

        $num = self::normalizeNumber($num, 'bcsqrt', 1, 'num');

        // A normalized zero has no sign, so any '-' means a negative number.
        if (str_starts_with($num, '-')) {
            throw new \ValueError('bcsqrt(): Argument #1 ($num) must be greater than or equal to 0');
        }

        // With num = D / 10^f, floor(sqrt(num) * 10^scale) = floor(sqrt(D * 10^(2 * scale - f))).
        // When 2 * scale < f, dropping the extra digits of D does not change the result,
        // because floor(sqrt(floor(x))) = floor(sqrt(x)).
        [$integer, $fraction] = self::parseDecimalNumber($num);
        $digits = $integer.$fraction;
        $shift = 2 * $scale - strlen($fraction);
        $digits = $shift >= 0 ? $digits.str_repeat('0', $shift) : substr($digits, 0, $shift);

        $root = self::integerSquareRoot(new BigInteger($digits === '' ? '0' : $digits));

        return self::format($root, $scale, $scale);
    }

    /**
     * floor(sqrt(n)) for a non-negative integer, by Newton's method.
     */
    private static function integerSquareRoot(BigInteger $n): BigInteger
    {
        $one = new BigInteger(1);
        if ($n->compare($one) <= 0) {
            return $n;
        }

        // Start from a power of ten that is at least sqrt(n). From above, Newton's
        // method decreases monotonically to floor(sqrt(n)) and then stops decreasing.
        $x = new BigInteger('1'.str_repeat('0', intdiv(strlen((string) $n) + 1, 2)));
        while (true) {
            [$quotient] = $n->divide($x);
            // Halve with a shift, not divide(): with phpseclib's PHP64 engine, a quotient
            // returned by divide() gives a wrong result when it is used as a divisor
            // (seen with phpseclib 3.0.47 and 4.0.4).
            $y = $x->add($quotient)->bitwise_rightShift(1);
            if ($y->compare($x) >= 0) {
                return $x;
            }
            $x = $y;
        }
    }

    /**
     * Round down to the nearest integer.
     *
     * Equivalent to rounding towards negative infinity.
     *
     * @throws \ValueError if inputs are not well-formed
     */
    public static function floor(string $num): string
    {
        return self::roundToInteger($num, 'bcfloor', self::ROUND_NEGATIVE_INFINITY);
    }

    /**
     * Round up to the nearest integer.
     *
     * Equivalent to rounding towards positive infinity.
     *
     * @throws \ValueError if inputs are not well-formed
     */
    public static function ceil(string $num): string
    {
        return self::roundToInteger($num, 'bcceil', self::ROUND_POSITIVE_INFINITY);
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
        // PHP's native bcround() (8.4+) rejects a $precision above INT_MAX before
        // performing any computation. Without this guard a huge precision triggers
        // an out-of-memory fatal via str_repeat() in roundDigits(). Mirror
        // php-src's bcmath_check_precision(): only the upper bound overflows int.
        if ($precision > 2147483647) {
            throw new \ValueError(
                'bcround(): Argument #2 ($precision) must be between '.PHP_INT_MIN.' and 2147483647'
            );
        }

        $num = self::normalizeNumber($num, 'bcround', 1, 'num');

        return self::roundDigits($num, $precision, self::convertRoundingMode($mode));
    }

    /**
     * Helper function for bcround.
     *
     * Retained as a public entry point for backward compatibility. It accepts the
     * legacy integer PHP_ROUND_* modes and delegates to round() so it shares the
     * same input, precision and mode validation.
     */
    public static function bcroundHelper(string $number, int $precision, int $mode = PHP_ROUND_HALF_UP): string
    {
        return self::round($number, $precision, $mode);
    }

    /**
     * Normalise a rounding mode (RoundingMode enum or legacy PHP_ROUND_* int)
     * to one of the canonical self::ROUND_* string tokens.
     *
     * Note: the parameter type declaration is intentionally omitted to prevent
     * PHP's type coercion. With an int|\RoundingMode hint, a float such as 1.5
     * would be silently truncated to int (1), bypassing validation.
     *
     * @param int|\RoundingMode $mode
     *
     * @throws \ValueError If an invalid rounding mode is provided
     */
    private static function convertRoundingMode($mode): string
    {
        // RoundingMode enum support (both native PHP 8.4+ and polyfill PHP 8.1-8.3)
        if (enum_exists('RoundingMode') && $mode instanceof \RoundingMode) {
            return match ($mode) {
                \RoundingMode::HalfAwayFromZero => self::ROUND_HALF_AWAY_FROM_ZERO,
                \RoundingMode::HalfTowardsZero => self::ROUND_HALF_TOWARDS_ZERO,
                \RoundingMode::HalfEven => self::ROUND_HALF_EVEN,
                \RoundingMode::HalfOdd => self::ROUND_HALF_ODD,
                \RoundingMode::TowardsZero => self::ROUND_TOWARDS_ZERO,
                \RoundingMode::AwayFromZero => self::ROUND_AWAY_FROM_ZERO,
                \RoundingMode::NegativeInfinity => self::ROUND_NEGATIVE_INFINITY,
                \RoundingMode::PositiveInfinity => self::ROUND_POSITIVE_INFINITY,
                // Unreachable: RoundingMode has exactly the eight cases above.
                // Present because PHPStan binds \RoundingMode to Rector's scoped
                // polyfill class (not our enum) and cannot prove exhaustiveness.
                default => throw new \ValueError('Unsupported RoundingMode'),
            };
        }

        // Backward compatibility for the legacy PHP_ROUND_* integer constants.
        if (is_int($mode)) {
            return match ($mode) {
                PHP_ROUND_HALF_UP => self::ROUND_HALF_AWAY_FROM_ZERO,
                PHP_ROUND_HALF_DOWN => self::ROUND_HALF_TOWARDS_ZERO,
                PHP_ROUND_HALF_EVEN => self::ROUND_HALF_EVEN,
                PHP_ROUND_HALF_ODD => self::ROUND_HALF_ODD,
                default => throw new \ValueError('Invalid rounding mode provided'),
            };
        }

        throw new \ValueError('Invalid rounding mode provided');
    }

    /**
     * Core arbitrary-precision rounding using pure string/digit arithmetic.
     *
     * Handles every RoundingMode (including the directional modes) and both
     * positive and negative precision without ever converting to float, so the
     * result stays exact for arbitrarily large or high-precision inputs.
     *
     * String-digit rounding approach inspired by symfony/polyfill-php84 (MIT).
     *
     * @param string $number A well-formed numeric string
     * @param int $precision Decimal places to round to (may be negative)
     * @param string $mode One of the self::ROUND_* canonical tokens
     */
    private static function roundDigits(string $number, int $precision, string $mode): string
    {
        // Split off an optional sign.
        $sign = 1;
        if ($number !== '' && ($number[0] === '-' || $number[0] === '+')) {
            if ($number[0] === '-') {
                $sign = -1;
            }
            $number = substr($number, 1);
        }

        // Split into integer and fractional digit runs.
        if (str_contains($number, '.')) {
            [$intPart, $fracPart] = explode('.', $number, 2);
        } else {
            $intPart = $number;
            $fracPart = '';
        }
        if ($intPart === '') {
            $intPart = '0';
        }
        $intPart = self::stripLeadingZeros($intPart);

        // Partition the digits at the rounding position into a "kept" run
        // (scaledInt) and the "dropped" run (scaledFrac).
        if ($precision >= 0) {
            $fracLength = strlen($fracPart);
            if ($precision <= $fracLength) {
                $scaledInt = $intPart.substr($fracPart, 0, $precision);
                $scaledFrac = substr($fracPart, $precision);
            } else {
                $scaledInt = $intPart.$fracPart.str_repeat('0', $precision - $fracLength);
                $scaledFrac = '';
            }
        } else {
            $shift = -$precision;
            $intLength = strlen($intPart);
            if ($shift <= $intLength) {
                $splitPos = $intLength - $shift;
                $scaledInt = substr($intPart, 0, $splitPos);
                $scaledInt = $scaledInt === '' ? '0' : $scaledInt;
                $scaledFrac = substr($intPart, $splitPos).$fracPart;
            } else {
                $scaledInt = '0';
                // applyRounding() only inspects the leading dropped digit (always
                // '0' here) and whether any non-zero digit follows, so we avoid
                // materialising ($shift - $intLength) zeros for a huge negative
                // precision. '01' preserves "leading 0, non-zero tail"; '0' means
                // everything dropped is zero.
                $scaledFrac = trim($intPart.$fracPart, '0') !== '' ? '01' : '0';
            }
        }

        $roundedInt = self::applyRounding($scaledInt, $scaledFrac, $sign, $mode);
        $isZero = trim($roundedInt, '0') === '';
        $result = self::placeDecimalPoint($roundedInt, $precision);

        if ($sign === -1 && !$isZero) {
            return '-'.$result;
        }

        return $result;
    }

    /**
     * Decide whether the kept digits must be incremented for the given mode and
     * return the (still unscaled) rounded integer run.
     *
     * @param int $sign 1 for a non-negative value, -1 for a negative value
     */
    private static function applyRounding(string $intPart, string $fracPart, int $sign, string $mode): string
    {
        $intPart = self::stripLeadingZeros($intPart);

        // Nothing is dropped -> no rounding needed.
        if ($fracPart === '' || trim($fracPart, '0') === '') {
            return $intPart;
        }

        $firstDigit = $fracPart[0];
        $tailNonZero = trim(substr($fracPart, 1), '0') !== '';
        $isGreaterThanHalf = $firstDigit > '5' || ($firstDigit === '5' && $tailNonZero);
        $isExactlyHalf = $firstDigit === '5' && !$tailNonZero;

        $increase = match ($mode) {
            self::ROUND_TOWARDS_ZERO => false,
            self::ROUND_AWAY_FROM_ZERO => true,
            self::ROUND_POSITIVE_INFINITY => $sign > 0,
            self::ROUND_NEGATIVE_INFINITY => $sign < 0,
            self::ROUND_HALF_AWAY_FROM_ZERO => $isGreaterThanHalf || $isExactlyHalf,
            self::ROUND_HALF_TOWARDS_ZERO => $isGreaterThanHalf,
            self::ROUND_HALF_EVEN => $isGreaterThanHalf || ($isExactlyHalf && self::lastDigitValue($intPart) % 2 === 1),
            self::ROUND_HALF_ODD => $isGreaterThanHalf || ($isExactlyHalf && self::lastDigitValue($intPart) % 2 === 0),
            default => throw new \ValueError('Invalid rounding mode provided'),
        };

        if ($increase) {
            $intPart = self::incrementDigitString($intPart);
        }

        return self::stripLeadingZeros($intPart);
    }

    /**
     * Re-insert the decimal point after rounding, restoring the requested number
     * of fractional digits (or trailing zeros for negative precision).
     */
    private static function placeDecimalPoint(string $roundedInt, int $precision): string
    {
        if ($precision > 0) {
            if (strlen($roundedInt) <= $precision) {
                $roundedInt = str_pad($roundedInt, $precision + 1, '0', STR_PAD_LEFT);
            }
            $intDigits = substr($roundedInt, 0, -$precision);
            $fracDigits = substr($roundedInt, -$precision);
            $intDigits = self::stripLeadingZeros($intDigits === '' ? '0' : $intDigits);

            return $intDigits.'.'.$fracDigits;
        }

        if ($precision === 0) {
            return self::stripLeadingZeros($roundedInt);
        }

        // A zero result stays '0' regardless of scale; short-circuit before
        // allocating -$precision trailing zeros for a huge negative precision.
        if (trim($roundedInt, '0') === '') {
            return '0';
        }

        return self::stripLeadingZeros($roundedInt.str_repeat('0', -$precision));
    }

    /**
     * Strip leading zeros, keeping at least a single '0'.
     */
    private static function stripLeadingZeros(string $digits): string
    {
        $digits = ltrim($digits, '0');

        return $digits === '' ? '0' : $digits;
    }

    /**
     * Add one to a non-negative run of decimal digits, propagating any carry.
     */
    private static function incrementDigitString(string $digits): string
    {
        $digits = $digits === '' ? '0' : $digits;
        $index = strlen($digits) - 1;
        $carry = 1;
        while ($index >= 0 && $carry === 1) {
            $value = ord($digits[$index]) - 48 + $carry;
            $carry = $value >= 10 ? 1 : 0;
            $digits[$index] = chr(48 + $value % 10);
            --$index;
        }

        return $carry === 1 ? '1'.$digits : $digits;
    }

    /**
     * Return the numeric value of the last digit in a run (0 when empty).
     */
    private static function lastDigitValue(string $digits): int
    {
        $length = strlen($digits);

        return $length > 0 ? ord($digits[$length - 1]) - 48 : 0;
    }
}
