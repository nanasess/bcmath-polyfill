<?php

/**
 * BCMath Emulation Class
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
 * BCMath Emulation Class
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 * @access  public
 *
 * @method static string add(string $num1, string $num2, int $scale = 0)
 * @method static string sub(string $num1, string $num2, int $scale = 0)
 * @method static string mul(string $num1, string $num2, int $scale = 0)
 * @method static string div(string $num1, string $num2, int $scale = 0)
 * @method static string mod(string $num1, string $num2, int $scale = 0)
 * @method static int comp(string $num1, string $num2, int $scale = 0)
 * @method static string pow(string $num, string $exponent, int $scale = 0)
 * @method static string powmod(string $base, string $exponent, string $modulus, int $scale = 0)
 * @method static string sqrt(string $operand, int $scale = 0)
 * @method static string floor(string $num, int $scale = 0)
 * @method static string ceil(string $num, int $scale = 0)
 * @method static string round(string $num, int $precision = 0, int $mode = 1) // $mode default is PHP_ROUND_HALF_UP (1)
 */
abstract class BCMath
{
    /**
     * Default scale parameter for all bc math functions
     */
    private static ?int $scale = null;

    /**
     * Set or get default scale parameter for all bc math functions
     *
     * Uses the PHP 7.3+ behavior
     *
     * @param ?int $scale optional
     */
    private static function scale($scale = null): ?int
    {
        if (isset($scale)) {
            self::$scale = (int) $scale;
        }
        return self::$scale;
    }

    /**
     * Formats numbers
     *
     * Places the decimal place at the appropriate place, adds trailing 0's as appropriate, etc
     *
     * @param string $x
     * @param int $scale
     * @param int $pad
     */
    private static function format($x, $scale, $pad = 0): string
    {
        $sign = self::isNegative($x) ? '-' : '';
        $x = str_replace('-', '', $x);

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
        return $sign . rtrim(implode('.', $temp), '.');
    }

    /**
     * Negativity Test
     *
     * @param BigInteger $x
     */
    private static function isNegative($x): bool
    {
        return $x->compare(new BigInteger()) < 0;
    }

    /**
     * Add two arbitrary precision numbers
     *
     * @param int $scale
     * @param int $pad
     */
    private static function add(BigInteger $x, BigInteger $y, $scale, $pad = 0): string
    {
        $z = $x->add($y);

        return self::format($z, $scale, $pad);
    }

    /**
     * Subtract one arbitrary precision number from another
     *
     * @param int $scale
     * @param int $pad
     */
    private static function sub(BigInteger $x, BigInteger $y, $scale, $pad = 0): string
    {
        $z = $x->subtract($y);

        return self::format($z, $scale, $pad);
    }

    /**
     * Multiply two arbitrary precision numbers
     *
     * @param BigInteger $x
     * @param BigInteger $y
     * @param int $scale
     * @param int $pad
     */
    private static function mul($x, $y, $scale, $pad = 0): string
    {
        if ($x == '0' || $y == '0') {
            $r = '0';
            if ($scale) {
                $r .= '.' . str_repeat('0', $scale);
            }
            return $r;
        }

        $z = $x->abs()->multiply($y->abs());
        $sign = ((self::isNegative($x) ^ self::isNegative($y)) !== 0) ? '-' : '';

        return $sign . self::format($z, $scale, 2 * $pad);
    }

    /**
     * Divide two arbitrary precision numbers
     *
     * @param BigInteger $x
     * @param BigInteger $y
     * @param int $scale
     * @param int $pad
     */
    private static function div($x, $y, $scale, $pad = 0): string
    {
        if ($y == '0') {
            // < PHP 8.0 triggered a warning
            // >= PHP 8.0 throws an exception
            throw new \DivisionByZeroError('Division by zero');
        }

        $temp = '1' . str_repeat('0', $scale);
        $temp = new BigInteger($temp);
        [$q] = $x->multiply($temp)->divide($y);

        return self::format($q, $scale, $scale);
    }

    /**
     * Get modulus of an arbitrary precision number
     *
     * Uses the PHP 7.2+ behavior
     *
     * @param BigInteger $x
     * @param BigInteger $y
     * @param int $scale
     * @param int $pad
     */
    private static function mod($x, $y, $scale, $pad = 0): string
    {
        if ($y == '0') {
            // < PHP 8.0 triggered a warning
            // >= PHP 8.0 throws an exception
            throw new \DivisionByZeroError('Division by zero');
        }

        [$q] = $x->divide($y);
        $z = $y->multiply($q);
        $z = $x->subtract($z);

        return self::format($z, $scale, $pad);
    }

    /**
     * Compare two arbitrary precision numbers
     *
     * @param string $x
     * @param string $y
     * @param ?int $scale
     * @param int $pad
     */
    private static function comp($x, $y, $scale = 0, $pad = 0): int
    {
        $x = new BigInteger($x[0] . substr($x[1], 0, $scale));
        $y = new BigInteger($y[0] . substr($y[1], 0, $scale));

        return $x->compare($y);
    }

    /**
     * Raise an arbitrary precision number to another
     *
     * Uses the PHP 7.2+ behavior
     *
     * @param BigInteger $x
     * @param string $y
     * @param int $scale
     * @param int $pad
     */
    private static function pow($x, $y, $scale, $pad = 0): string
    {
        if ($y == '0') {
            $r = '1';
            if ($scale) {
                $r .= '.' . str_repeat('0', $scale);
            }
            return $r;
        }

        $min = defined('PHP_INT_MIN') ? PHP_INT_MIN : ~PHP_INT_MAX;
        if (bccomp($y, PHP_INT_MAX) > 0 || bccomp($y, $min) <= 0) {
            throw new \ValueError('bcpow(): Argument #2 ($exponent) is too large');
        }

        $sign = self::isNegative($x) ? '-' : '';
        $x = $x->abs();

        $r = new BigInteger(1);

        for ($i = 0; $i < abs($y); $i++) {
            $r = $r->multiply($x);
        }

        if ($y < 0) {
            $temp = '1' . str_repeat('0', $scale + $pad * abs($y));
            $temp = new BigInteger($temp);
            [$r] = $temp->divide($r);
            $pad = $scale;
        } else {
            $pad *= abs($y);
        }

        return $sign . self::format($r, $scale, $pad);
    }

    /**
     * Raise an arbitrary precision number to another, reduced by a specified modulus
     *
     * @param string $x
     * @param string $e
     * @param string $n
     * @param int $scale
     * @param int $pad
     */
    private static function powmod($x, $e, $n, $scale, $pad = 0): string
    {
        if ($e[0] == '-' || $n == '0') {
            // < PHP 8.0 returned false
            // >= PHP 8.0 throws an exception
            throw new \ValueError('bcpowmod(): Argument #2 ($exponent) must be greater than or equal to 0');
        }
        if ($n[0] == '-') {
            $n = substr($n, 1);
        }
        if ($e == '0') {
            return $scale
                ? '1.' . str_repeat('0', $scale)
                : '1';
        }

        $x = new BigInteger($x);
        $e = new BigInteger($e);
        $n = new BigInteger($n);

        $z = $x->powMod($e, $n);

        return $scale
            ? "$z." . str_repeat('0', $scale)
            : "$z";
    }

    /**
     * Get the square root of an arbitrary precision number
     *
     * @param string $n
     * @param ?int $scale
     * @param int $pad
     */
    private static function sqrt($n, $scale = 0, $pad = 0): string
    {
        // the following is based off of the following URL:
        // https://en.wikipedia.org/wiki/Methods_of_computing_square_roots#Decimal_(base_10)

        if (!is_numeric($n)) {
            return '0';
        }
        $temp = explode('.', $n);
        $decStart = ceil(strlen($temp[0]) / 2);
        $n = implode('', $temp);
        if (strlen($n) % 2 !== 0) {
            $n = "0$n";
        }
        $parts = str_split($n, 2);
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
            if ((!$c && $i >= $decStart)  || $i - $decStart == $scale) {
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
     * Round down to the nearest integer
     *
     * @param string $n
     * @param int $scale
     * @param int $pad
     */
    private static function floor($n, $scale, $pad = 0): string
    {
        if (!is_numeric($n)) {
            if (version_compare(PHP_VERSION, '8.4', '>=')) {
                throw new \ValueError('bcfloor(): Argument #1 ($num) is not well-formed');
            }
            trigger_error('bcfloor(): Argument #1 ($num) is not well-formed', E_USER_WARNING);
            return '0';
        }

        if ($scale == 0) {
            // When scale is 0, just get the integer part
            $result = bcdiv($n, '1', 0);

            // For negative numbers with fractional parts, we need to subtract 1
            if (str_contains($n, '.') && $n[0] === '-') {
                $fractionalPart = substr($n, strpos($n, '.') + 1);
                if (ltrim($fractionalPart, '0') !== '') {
                    $result = bcsub($result, '1', 0);
                }
            }

            return $result;
        } else {
            // When scale > 0, truncate to the specified decimal places
            // Simply use bcdiv with the desired scale, which truncates
            return bcdiv($n, '1', $scale);
        }
    }

    /**
     * Round up to the nearest integer
     *
     * @param string $n
     * @param int $scale
     * @param int $pad
     */
    private static function ceil($n, $scale, $pad = 0): string
    {
        if (!is_numeric($n)) {
            if (version_compare(PHP_VERSION, '8.4', '>=')) {
                throw new \ValueError('bcceil(): Argument #1 ($num) is not well-formed');
            }
            trigger_error('bcceil(): Argument #1 ($num) is not well-formed', E_USER_WARNING);
            return '0';
        }

        if ($scale == 0) {
            // When scale is 0, just get the integer part
            $result = bcdiv($n, '1', 0);

            // For positive numbers with fractional parts, we need to add 1
            if (str_contains($n, '.') && $n[0] !== '-') {
                $fractionalPart = substr($n, strpos($n, '.') + 1);
                if (ltrim($fractionalPart, '0') !== '') {
                    $result = bcadd($result, '1', 0);
                }
            }

            return $result;
        } else {
            // When scale > 0, ceil to the specified decimal places
            // Multiply by 10^scale, ceil, then divide back
            $factor = bcpow('10', (string) $scale);
            $shifted = bcmul($n, $factor, 10); // Use high precision for intermediate calculation

            // Get the ceiling of the shifted value
            $ceiledShifted = bcdiv($shifted, '1', 0);

            // For positive numbers with fractional parts, we need to add 1
            if (str_contains($shifted, '.') && $shifted[0] !== '-') {
                $fractionalPart = substr($shifted, strpos($shifted, '.') + 1);
                if (ltrim($fractionalPart, '0') !== '') {
                    $ceiledShifted = bcadd($ceiledShifted, '1', 0);
                }
            }

            // Divide back to get the result with proper scale
            return bcdiv($ceiledShifted, $factor, $scale);
        }
    }

    /**
     * Round to a given decimal place
     *
     * @param string $n
     * @param int $precision
     * @param int $mode
     * @param int $pad
     */
    private static function round($n, $precision, $mode = PHP_ROUND_HALF_UP, $pad = 0): string
    {
        if (!is_numeric($n)) {
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
            $factor = bcpow('10', (string) $absPrecision);
            $shifted = bcdiv($n, $factor, 10); // Use a high precision for intermediate calculation

            // Apply rounding
            $rounded = self::bcroundHelper($shifted, 0, $mode);

            // Shift back
            return bcmul($rounded, $factor, 0);
        } else {
            return self::bcroundHelper($n, $precision, $mode);
        }
    }

    /**
     * Helper function for bcround
     *
     * @param string $number
     * @param int $precision
     * @param int $mode
     */
    private static function bcroundHelper($number, $precision, $mode = PHP_ROUND_HALF_UP): string
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
            $addition = '0.' . str_repeat('0', $precision) . '5';
            $number = bcadd($number, $addition, $precision + 1);
        } elseif ($mode === PHP_ROUND_HALF_DOWN) {
            // For HALF_DOWN, we need to check the digit at precision+1
            [$int, $dec] = explode('.', $number);
            if (isset($dec[$precision])) {
                $digit = (int) $dec[$precision];
                if ($digit == 5 && (!isset($dec[$precision + 1]) || ltrim(substr($dec, $precision + 1), '0') === '')) {
                    // Exactly 0.5, don't round up
                } elseif ($digit > 5 || ($digit == 5 && ltrim(substr($dec, $precision + 1), '0') !== '')) {
                    $addition = '0.' . str_repeat('0', $precision) . '1';
                    $number = bcadd($number, $addition, $precision + 1);
                }
            }
        } else {
            // For other modes, use PHP's round and convert back
            $rounded = round((float) ($sign . $number), $precision, $mode);
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

        return $sign . $number;
    }

    /**
     * __callStatic Magic Method
     *
     * @param string $name
     * @param array $arguments
     */
    public static function __callStatic($name, $arguments)
    {
        static $params = [
            'add' => 3,
            'comp' => 3,
            'div' => 3,
            'mod' => 3,
            'mul' => 3,
            'pow' => 3,
            'powmod' => 4,
            'scale' => 1,
            'sqrt' => 2,
            'sub' => 3,
            'floor' => 2,
            'ceil' => 2,
            'round' => 4,
        ];
        $cnt = count($arguments);

        // Special handling for round which can have 1-3 parameters
        if ($name === 'round') {
            if ($cnt < 1) {
                throw new \ArgumentCountError("bcround() expects at least 1 parameter, " . $cnt . " given");
            }
            if ($cnt > 3) {
                throw new \ArgumentCountError("bcround() expects at most 3 parameters, " . $cnt . " given");
            }
        } else {
            if ($cnt < $params[$name] - 1) {
                $min = $params[$name] - 1;
                throw new \ArgumentCountError("bc$name() expects at least $min parameters, " . $cnt . " given");
            }
            if ($cnt > $params[$name]) {
                $str = "bc$name() expects at most {$params[$name]} parameters, " . $cnt . " given";
                throw new \ArgumentCountError($str);
            }
        }
        // For round, we only need the first parameter as a number
        $numbers = $name === 'round' ? array_slice($arguments, 0, 1) : array_slice($arguments, 0, $params[$name] - 1);

        $ints = [];
        switch ($name) {
            case 'pow':
                $ints = array_slice($numbers, count($numbers) - 1);
                $numbers = array_slice($numbers, 0, count($numbers) - 1);
                $names = ['exponent'];
                break;
            case 'powmod':
                $ints = $numbers;
                $numbers = [];
                $names = ['base', 'exponent', 'modulus'];
                break;
            case 'sqrt':
            case 'floor':
            case 'ceil':
                $names = ['num'];
                break;
            case 'round':
                $names = ['num', 'precision', 'mode'];
                break;
            default:
                $names = ['num1', 'num2'];
        }
        foreach ($ints as $i => &$int) {
            if (!is_numeric($int)) {
                $int = '0';
            }
            $pos = strpos($int, '.');
            if ($pos !== false) {
                $int = substr($int, 0, $pos);
                throw new \ValueError("bc$name(): Argument #2 (\$$names[$i]) cannot have a fractional part");
            }
        }
        foreach ($numbers as $i => $arg) {
            $num = $i + 1;
            switch (true) {
                case is_bool($arg):
                case is_numeric($arg):
                case is_string($arg):
                case is_object($arg) && method_exists($arg, '__toString'):
                    if (!is_bool($arg) && !is_numeric("$arg")) {
                        throw new \ValueError("bc$name: bcmath function argument is not well-formed");
                    }
                    break;
                    // PHP >= 8.1 has deprecated the passing of nulls to string parameters
                case is_null($arg):
                    $error = "bc$name(): Passing null to parameter #$num (\$$names[$i]) of type string is deprecated";
                    trigger_error($error, E_USER_DEPRECATED);
                    break;
                default:
                    $type = get_debug_type($arg);
                    $error = "bc$name(): Argument #$num (\$$names[$i]) must be of type string, $type given";
                    throw new \TypeError($error);
            }
        }
        if (!isset(self::$scale)) {
            $scale = ini_get('bcmath.scale');
            self::$scale = $scale !== false ? max(intval($scale), 0) : 0;
        }
        // For round, scale is the second parameter (precision)
        $scale = $name === 'round' ? $arguments[1] ?? self::$scale : $arguments[$params[$name] - 1] ?? self::$scale;
        switch (true) {
            case is_bool($scale):
            case is_numeric($scale):
            case is_string($scale) && preg_match('#0-9\.#', $scale[0]):
                break;
            default:
                $type = get_debug_type($scale);
                $str = "bc$name(): Argument #$params[$name] (\$scale) must be of type ?int, string given";
                throw new \TypeError($str);
        }
        $scale = (int) $scale;
        // For bcround, negative precision is allowed
        if ($scale < 0 && $name !== 'round') {
            throw new \ValueError("bc$name(): Argument #$params[$name] (\$scale) must be between 0 and 2147483647");
        }

        $pad = 0;
        foreach ($numbers as &$num) {
            if (is_bool($num)) {
                $num = $num ? '1' : '0';
            } elseif (!is_numeric($num)) {
                $num = '0';
            }
            $num = explode('.', $num);
            if (isset($num[1])) {
                $pad = max($pad, strlen($num[1]));
            }
        }
        switch ($name) {
            case 'add':
            case 'sub':
            case 'mul':
            case 'div':
            case 'mod':
            case 'pow':
                foreach ($numbers as &$num) {
                    if (!isset($num[1])) {
                        $num[1] = '';
                    }
                    $num[1] = str_pad($num[1], $pad, '0');
                    $num = new BigInteger($num[0] . $num[1]);
                }
                break;
            case 'comp':
                foreach ($numbers as &$num) {
                    if (!isset($num[1])) {
                        $num[1] = '';
                    }
                    $num[1] = str_pad($num[1], $pad, '0');
                }
                break;
            case 'sqrt':
            case 'floor':
            case 'ceil':
            case 'round':
                $numbers = [$arguments[0]];
        }

        // Special handling for round function which has a mode parameter
        if ($name === 'round') {
            // bcround can have 1, 2, or 3 parameters
            // Get the mode from the original arguments if provided
            $originalArgs = $arguments;
            $originalCnt = count($originalArgs);
            $precision = ($originalCnt >= 2) ? $originalArgs[1] : $scale;
            $mode = ($originalCnt >= 3) ? $originalArgs[2] : PHP_ROUND_HALF_UP;
            $arguments = array_merge($numbers, [$precision, $mode, $pad]);
        } else {
            $arguments = array_merge($numbers, $ints, [$scale, $pad]);
        }

        $result = call_user_func_array(self::class . "::$name", $arguments);
        return preg_match('#^-0\.?0*$#', (string) $result) ? substr((string) $result, 1) : $result;
    }
}
