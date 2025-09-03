<?php

/**
 * bcmath polyfill.
 *
 * PHP versions 5 and 7
 *
 * LICENSE: Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @author    Jim Wigginton <terrafrost@php.net>
 * @copyright 2019 Jim Wigginton
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 *
 * @see      http://phpseclib.sourceforge.net
 */

use bcmath_compat\BCMath;

if (!function_exists('bcadd')) {
    /**
     * Add two arbitrary precision numbers.
     *
     * @param string $left_operand
     * @param string $right_operand
     * @param null|int $scale optional
     */
    function bcadd($left_operand, $right_operand, $scale = null): string
    {
        return BCMath::add($left_operand, $right_operand, $scale);
    }

    /**
     * Compare two arbitrary precision numbers.
     *
     * @param string $left_operand
     * @param string $right_operand
     * @param null|int $scale optional
     */
    function bccomp($left_operand, $right_operand, $scale = null): int
    {
        return BCMath::comp($left_operand, $right_operand, $scale);
    }

    /**
     * Divide two arbitrary precision numbers.
     *
     * @param string $dividend
     * @param string $divisor
     * @param null|int $scale optional
     */
    function bcdiv($dividend, $divisor, $scale = null): string
    {
        return BCMath::div($dividend, $divisor, $scale);
    }

    /**
     * Get modulus of an arbitrary precision number.
     *
     * @param string $dividend
     * @param string $divisor
     * @param null|int $scale optional
     */
    function bcmod($dividend, $divisor, $scale = null): string
    {
        return BCMath::mod($dividend, $divisor, $scale);
    }

    /**
     * Multiply two arbitrary precision numbers.
     *
     * @param string $dividend
     * @param string $divisor
     * @param null|int $scale optional
     */
    function bcmul($dividend, $divisor, $scale = null): string
    {
        return BCMath::mul($dividend, $divisor, $scale);
    }

    /**
     * Raise an arbitrary precision number to another.
     *
     * @param string $base
     * @param string $exponent
     * @param null|int $scale optional
     */
    function bcpow($base, $exponent, $scale = null): string
    {
        return BCMath::pow($base, $exponent, $scale);
    }

    /**
     * Raise an arbitrary precision number to another, reduced by a specified modulus.
     *
     * @param string $base
     * @param string $exponent
     * @param string $modulus
     * @param null|int $scale optional
     */
    function bcpowmod($base, $exponent, $modulus, $scale = null): string
    {
        return BCMath::powmod($base, $exponent, $modulus, $scale);
    }

    /**
     * Set or get default scale parameter for all bc math functions.
     *
     * @param null|int $scale
     */
    function bcscale($scale = null): int
    {
        return BCMath::scale($scale);
    }

    /**
     * Get the square root of an arbitrary precision number.
     *
     * @param string $operand
     * @param null|int $scale optional
     */
    function bcsqrt($operand, $scale = null): string
    {
        return BCMath::sqrt($operand, $scale);
    }

    /**
     * Subtract one arbitrary precision number from another.
     *
     * @param string $left_operand
     * @param string $right_operand
     * @param null|int $scale optional
     */
    function bcsub($left_operand, $right_operand, $scale = null): string
    {
        return BCMath::sub($left_operand, $right_operand, $scale);
    }
}

if (!function_exists('bcfloor')) {
    /**
     * Round down to the nearest integer (PHP 8.4+).
     *
     * @param string $operand
     * @param null|int $scale optional
     */
    function bcfloor($operand, $scale = null): string
    {
        return BCMath::floor($operand, $scale);
    }
}

if (!function_exists('bcceil')) {
    /**
     * Round up to the nearest integer (PHP 8.4+).
     *
     * @param string $operand
     * @param null|int $scale optional
     */
    function bcceil($operand, $scale = null): string
    {
        return BCMath::ceil($operand, $scale);
    }
}

if (!function_exists('bcround')) {
    /**
     * Round to a given decimal place (PHP 8.4+).
     *
     * @param string $operand
     * @param int $precision optional
     * @param int $mode optional
     */
    function bcround($operand, $precision = 0, $mode = PHP_ROUND_HALF_UP): string
    {
        return BCMath::round($operand, $precision, $mode);
    }
}

// the following were introduced in PHP 7.0.0
if (!class_exists('Error')) {
    class Error extends Exception {}

    class ArithmeticError extends Error {}

    class DivisionByZeroError extends ArithmeticError {}

    class TypeError extends Error {}
}

// the following was introduced in PHP 7.1.0
if (!class_exists('ArgumentCountError')) {
    class ArgumentCountError extends TypeError {}
}

// the following was introduced in PHP 8.0.0
if (!class_exists('ValueError')) {
    class ValueError extends Error {}
}
