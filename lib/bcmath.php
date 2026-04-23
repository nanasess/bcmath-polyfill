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
     * @param null|int $scale optional
     */
    function bcadd(string $left_operand, string $right_operand, ?int $scale = null): string
    {
        return BCMath::add($left_operand, $right_operand, $scale);
    }

    /**
     * Compare two arbitrary precision numbers.
     *
     * @param null|int $scale optional
     */
    function bccomp(string $left_operand, string $right_operand, ?int $scale = null): int
    {
        return BCMath::comp($left_operand, $right_operand, $scale);
    }

    /**
     * Divide two arbitrary precision numbers.
     *
     * @param null|int $scale optional
     */
    function bcdiv(string $dividend, string $divisor, ?int $scale = null): string
    {
        return BCMath::div($dividend, $divisor, $scale);
    }

    /**
     * Get modulus of an arbitrary precision number.
     *
     * @param null|int $scale optional
     */
    function bcmod(string $dividend, string $divisor, ?int $scale = null): string
    {
        return BCMath::mod($dividend, $divisor, $scale);
    }

    /**
     * Multiply two arbitrary precision numbers.
     *
     * @param null|int $scale optional
     */
    function bcmul(string $dividend, string $divisor, ?int $scale = null): string
    {
        return BCMath::mul($dividend, $divisor, $scale);
    }

    /**
     * Raise an arbitrary precision number to another.
     *
     * @param null|int $scale optional
     */
    function bcpow(string $num, string $exponent, ?int $scale = null): string
    {
        return BCMath::pow($num, $exponent, $scale);
    }

    /**
     * Raise an arbitrary precision number to another, reduced by a specified modulus.
     *
     * @param null|int $scale optional
     */
    function bcpowmod(string $num, string $exponent, string $modulus, ?int $scale = null): string
    {
        return BCMath::powmod($num, $exponent, $modulus, $scale);
    }

    /**
     * Set or get default scale parameter for all bc math functions.
     */
    function bcscale(?int $scale = null): ?int
    {
        return BCMath::scale($scale);
    }

    /**
     * Get the square root of an arbitrary precision number.
     *
     * @param null|int $scale optional
     */
    function bcsqrt(string $operand, ?int $scale = null): string
    {
        return BCMath::sqrt($operand, $scale);
    }

    /**
     * Subtract one arbitrary precision number from another.
     *
     * @param null|int $scale optional
     */
    function bcsub(string $left_operand, string $right_operand, ?int $scale = null): string
    {
        return BCMath::sub($left_operand, $right_operand, $scale);
    }
}

if (!function_exists('bcfloor')) {
    /**
     * Round down to the nearest integer (PHP 8.4+).
     */
    function bcfloor(string $operand): string
    {
        return BCMath::floor($operand);
    }
}

if (!function_exists('bcceil')) {
    /**
     * Round up to the nearest integer (PHP 8.4+).
     */
    function bcceil(string $operand): string
    {
        return BCMath::ceil($operand);
    }
}

if (!function_exists('bcround')) {
    /**
     * Round to a given decimal place (PHP 8.4+).
     *
     * @param int $precision optional
     * @param int $mode optional
     */
    function bcround(string $operand, int $precision = 0, $mode = PHP_ROUND_HALF_UP): string
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
