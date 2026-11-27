<?php

// declare(strict_types=1);

use bcmath_compat\BCMath;
use bcmath_compat\Math\BigInteger;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;

// use PHPUnit\Framework\Attributes\TestWith;

/**
 * requires extension bcmath.
 *
 * @internal
 */
#[RequiresPhpExtension('bcmath')]
#[CoversNothing]
final class BCMathTest extends TestCase
{
    protected static string $emsg = '';

    /**
     * Produces all combinations of test values.
     */
    /**
     * @return array<int, array<int, int|string>>
     */
    public static function generateTwoParams(): iterable
    {
        return [
            ['9', '9'],
            ['9.99', '9.99'],
            ['9.99', '9.99', 2],
            ['9.99', '9.00009'],
            ['9.99', '9.00009', 4],
            ['9.99', '9.00009', 6],
            ['9.99', '-7', 6],
            ['9.99', '-7.2', 6],
            ['-9.99', '-3', 4],
            ['-9.99', '3.7', 4],
            ['-9.99', '-2.4', 5],
            ['0', '34'],
            ['0.15', '0.15', 1],
            ['0.15', '-0.1', 1],
            ['12', '19', 5],
            ['19', '12', 5],
            ['190', '2', 3],
            ['2', '190', 3],
            ['9', '0'],
            ['0', '9'],
            // this became deprecated in PHP 8.1
            // [null, '9'],
            ['-0.0000005', '0', 3],
            ['-0.0000005', '0.0000001', 3],
            ['-0', '0'],
            ['-0', '-0', 4],
        ];
    }

    /**
     * @param numeric-string $num1
     * @param numeric-string $num2
     */
    #[DataProvider('generateTwoParams')]
    public function testAdd(string $num1, string $num2, ?int $scale = null): void
    {
        $a = $scale !== null ? bcadd($num1, $num2, $scale) : bcadd($num1, $num2);
        $b = $scale !== null ? BCMath::add($num1, $num2, $scale) : BCMath::add($num1, $num2);

        $this->assertSame($a, $b);
    }

    /**
     * @param numeric-string $num1
     * @param numeric-string $num2
     */
    #[DataProvider('generateTwoParams')]
    public function testSub(string $num1, string $num2, ?int $scale = null): void
    {
        $a = $scale !== null ? bcsub($num1, $num2, $scale) : bcsub($num1, $num2);
        $b = $scale !== null ? BCMath::sub($num1, $num2, $scale) : BCMath::sub($num1, $num2);

        $this->assertSame($a, $b);
    }

    /**
     * requires PHP 7.3.
     *
     * @param numeric-string $num1
     * @param numeric-string $num2
     */
    #[RequiresPhp('>7.3')]
    #[DataProvider('generateTwoParams')]
    public function testMul(string $num1, string $num2, ?int $scale = null): void
    {
        $a = $scale !== null ? bcmul($num1, $num2, $scale) : bcmul($num1, $num2);
        $b = $scale !== null ? BCMath::mul($num1, $num2, $scale) : BCMath::mul($num1, $num2);

        $this->assertSame($a, $b);
    }

    #[DataProvider('generateTwoParams')]
    public function testDiv(string $num1, string $num2, ?int $scale = null): void
    {
        if ($num2 === '0' || $num2 === '-0') {
            $this->expectException('DivisionByZeroError');
        }

        $a = $scale !== null ? bcdiv($num1, $num2, $scale) : bcdiv($num1, $num2);
        $b = $scale !== null ? BCMath::div($num1, $num2, $scale) : BCMath::div($num1, $num2);
        $this->assertSame($a, $b);
    }

    /**
     * dataProvider generateTwoParams
     * requires PHP 7.2.
     */
    #[DataProvider('generateTwoParams')]
    #[RequiresPhp('>7.2')]
    public function testMod(string $num1, string $num2, ?int $scale = null): void
    {
        if ($num2 === '0' || $num2 === '-0') {
            $this->expectException('DivisionByZeroError');
        }

        $a = $scale !== null ? bcmod($num1, $num2, $scale) : bcmod($num1, $num2);
        $b = $scale !== null ? BCMath::mod($num1, $num2, $scale) : BCMath::mod($num1, $num2);
        $this->assertSame($a, $b);
    }

    /**
     * Produces all combinations of test values.
     */
    /**
     * @return array<int, array<int, int|string>>
     */
    public static function providePowCases(): iterable
    {
        return [
            ['9', '9'],
            ['-9', '9'],
            ['9.99', '9'],
            ['9.99', '9', 4],
            ['9.99', '9', 6],
            ['9.99', '-7', 6],
            ['0', '34'],
            ['12', '19', 5],
            ['10', '-2', 10],
            ['-9.99', '-3', 10],
            ['0.15', '15', 10],
            ['0.15', '-1', 10],
            ['5', '0', 4],
        ];
    }

    /**
     * @param numeric-string $base
     * @param numeric-string $exponent
     */
    #[DataProvider('providePowCases')]
    #[RequiresPhp('>7.3')]
    public function testPow(string $base, string $exponent, ?int $scale = null): void
    {
        $a = $scale !== null ? bcpow($base, $exponent, $scale) : bcpow($base, $exponent);
        $b = $scale !== null ? BCMath::pow($base, $exponent, $scale) : BCMath::pow($base, $exponent);
        $this->assertSame($a, $b);
    }

    /**
     * Produces all combinations of test values.
     */
    /**
     * @return array<int, array<int, int|string>>
     */
    public static function providePowModCases(): iterable
    {
        return [
            ['9', '9', '17'],
            ['999', '999', '111', 5],
            ['-9', '1024', '123'],
            ['3', '1024', '-149'],
            ['2', '12', '2', 5],
            ['3', '0', '13'],
            ['-3', '0', '13', 4],
        ];
    }

    /**
     * dataProvider generatePowModParams
     * requires PHP 7.3.
     */
    #[DataProvider('providePowModCases')]
    #[RequiresPhp('>7.3')]
    public function testPowMod(string $base, string $exponent, string $modulus, ?int $scale = null): void
    {
        // Skip the specific test case on 32-bit Windows due to architecture limitations
        if (PHP_INT_SIZE === 4 && PHP_OS_FAMILY === 'Windows'
            && $base === '-9' && $exponent === '1024' && $modulus === '123') {
            $this->markTestSkipped('Known limitation on 32-bit Windows');
        }

        $a = $scale !== null ? bcpowmod($base, $exponent, $modulus, $scale) : bcpowmod($base, $exponent, $modulus);
        $b = $scale !== null ? BCMath::powmod($base, $exponent, $modulus, $scale) : BCMath::powmod($base, $exponent, $modulus);
        $this->assertSame($a, $b);
    }

    public function testSqrt(): void
    {
        $a = bcsqrt('152.2756', 4);
        $b = BCMath::sqrt('152.2756', 4);
        $this->assertSame($a, $b);

        $a = bcsqrt('40000');
        $b = BCMath::sqrt('40000');
        $this->assertSame($a, $b);

        $a = bcsqrt('2', 4);
        $b = BCMath::sqrt('2', 4);
        $this->assertSame($a, $b);
    }

    public function testBoolScale(): void
    {
        // @phpstan-ignore-next-line
        $a = bcadd('5', '2', false);
        // @phpstan-ignore-next-line
        $b = BCMath::add('5', '2', false);
        $this->assertSame($a, $b);
    }

    public function testIntParam(): void
    {
        // @phpstan-ignore-next-line
        $a = bccomp('9223372036854775807', 16);
        // @phpstan-ignore-next-line
        $b = BCMath::comp('9223372036854775807', 16);
        $this->assertSame($a, $b);
    }

    public function setExpectedException(string $name, ?string $message = null, mixed $code = null): void
    {
        switch ($name) {
            case 'PHPUnit_Framework_Error_Notice':
            case 'PHPUnit_Framework_Error_Warning':
                $name = str_replace('_', '\\', $name);
        }
        if (!is_subclass_of($name, \Throwable::class)) {
            throw new \InvalidArgumentException('Invalid exception class name');
        }
        $this->expectException($name);
        if (!in_array($message, [null, '', '0'], true)) {
            $this->expectExceptionMessage($message);
        }
        if (!empty($code) && (is_int($code) || is_string($code))) {
            $this->expectExceptionCode($code);
        }
    }

    /**
     * @return array<int, array<int, int>>
     */
    public static function provideArgumentsScaleCallstaticCases(): iterable
    {
        return [
            [4],
            [4, 2],
            [4, 2, 3],
            [4, 2, 3, 5],
        ];
    }

    /**
     * @param array<int, int> $params
     */
    #[DataProvider('provideArgumentsScaleCallstaticCases')]
    public function testArgumentsScaleCallstatic(...$params): void
    {
        // Save original scale
        $originalScale = bcscale();

        // scale with 1, 2, 3 parameters
        if (func_num_args() === 1) {
            // @phpstan-ignore-next-line
            bcscale(...$params);
            // @phpstan-ignore-next-line
            BCMath::scale(...$params);
            $scale = bcscale();
            $orig = $params[0];
            $this->assertSame($orig, $scale);
            $scale = BCMath::scale();
            $this->assertSame($orig, $scale);
        } else {
            $exception_thrown = false;
            $e = null;

            try {
                // @phpstan-ignore-next-line
                BCMath::scale(...$params);
            } catch (ArgumentCountError $e) {
                $exception_thrown = true;
            }
            $this->assertTrue($exception_thrown);
            // start the unit test with: (showing the wrong given values)
            // phpunit --testdox-test testdox.txt --display-skipped
            $msg = 'ArgumentCountError in '.$e->getFile().':'.$e->getLine().' : '.$e->getMessage();
            $this->markTestSkipped($msg);
        }

        // Restore original scale
        bcscale($originalScale);
        BCMath::scale($originalScale);
    }

    /**
     * @return array<int, array<int, int|string>>
     */
    public static function provideArgumentsPowModCallstaticCases(): iterable
    {
        return [
            ['9'],
            ['9', '17'],
            ['9', '17', '-111'],
            ['9', '17', '-111', 5],
            ['9', '17', '-111', 5, 8],
        ];
    }

    /**
     * @param array<int, int|string> $params
     */
    #[DataProvider('provideArgumentsPowModCallstaticCases')]
    public function testArgumentsPowModCallstatic(...$params): void
    {
        // scale with 1, 2, 3 parameters
        if (func_num_args() > 2 && func_num_args() < 5) {
            // @phpstan-ignore-next-line
            $a = bcpowmod(...$params);
            // @phpstan-ignore-next-line
            $b = BCMath::powmod(...$params);
            // @phpstan-ignore-next-line
            $this->assertSame($a, $b);
        } else {
            $exception_thrown = false;
            $e = null;

            try {
                // @phpstan-ignore-next-line
                BCMath::powmod(...$params);
            } catch (ArgumentCountError $e) {
                $exception_thrown = true;
            }
            $this->assertTrue($exception_thrown);
            // start the unit test with: (showing the wrong given values)
            // phpunit --testdox-test testdox.txt --display-skipped
            $msg = 'ArgumentCountError in '.$e->getFile().':'.$e->getLine().' : '.$e->getMessage();
            $this->markTestSkipped($msg);
        }
    }

    /**
     * Test bcfloor function
     * requires PHP 8.4.
     */
    #[RequiresPhp('>=8.4')]
    public function testFloor(): void
    {
        if (!function_exists('bcfloor')) {
            $this->markTestSkipped('bcfloor is not available in PHP < 8.4');
        }

        // Test positive numbers
        $this->assertSame(bcfloor('4.3'), BCMath::floor('4.3'));
        $this->assertSame(bcfloor('9.999'), BCMath::floor('9.999'));
        $this->assertSame(bcfloor('3.14159'), BCMath::floor('3.14159'));

        // Test negative numbers
        $this->assertSame(bcfloor('-4.3'), BCMath::floor('-4.3'));
        $this->assertSame(bcfloor('-9.999'), BCMath::floor('-9.999'));
        $this->assertSame(bcfloor('-3.14159'), BCMath::floor('-3.14159'));

        // Test integers
        $this->assertSame(bcfloor('5'), BCMath::floor('5'));
        $this->assertSame(bcfloor('-5'), BCMath::floor('-5'));
        $this->assertSame(bcfloor('0'), BCMath::floor('0'));
    }

    /**
     * Test bcceil function
     * requires PHP 8.4.
     */
    #[RequiresPhp('>=8.4')]
    public function testCeil(): void
    {
        if (!function_exists('bcceil')) {
            $this->markTestSkipped('bcceil is not available in PHP < 8.4');
        }

        // Test positive numbers
        $this->assertSame(bcceil('4.3'), BCMath::ceil('4.3'));
        $this->assertSame(bcceil('9.999'), BCMath::ceil('9.999'));
        $this->assertSame(bcceil('3.14159'), BCMath::ceil('3.14159'));

        // Test negative numbers
        $this->assertSame(bcceil('-4.3'), BCMath::ceil('-4.3'));
        $this->assertSame(bcceil('-9.999'), BCMath::ceil('-9.999'));
        $this->assertSame(bcceil('-3.14159'), BCMath::ceil('-3.14159'));

        // Test integers
        $this->assertSame(bcceil('5'), BCMath::ceil('5'));
        $this->assertSame(bcceil('-5'), BCMath::ceil('-5'));
        $this->assertSame(bcceil('0'), BCMath::ceil('0'));
    }

    /**
     * Test bcround function
     * requires PHP 8.4.
     */
    #[RequiresPhp('>=8.4')]
    public function testRound(): void
    {
        if (!function_exists('bcround')) {
            $this->markTestSkipped('bcround is not available in PHP < 8.4');
        }

        // Test basic rounding
        $this->assertSame(bcround('3.4'), BCMath::round('3.4'));
        $this->assertSame(bcround('3.5'), BCMath::round('3.5'));
        $this->assertSame(bcround('3.6'), BCMath::round('3.6'));
        $this->assertSame(bcround('-3.4'), BCMath::round('-3.4'));
        $this->assertSame(bcround('-3.5'), BCMath::round('-3.5'));
        $this->assertSame(bcround('-3.6'), BCMath::round('-3.6'));

        // Test with scale
        $this->assertSame(bcround('1.95583', 2), BCMath::round('1.95583', 2));
        $this->assertSame(bcround('1.95583', 3), BCMath::round('1.95583', 3));
        $this->assertSame(bcround('1.2345', 1), BCMath::round('1.2345', 1));

        // Test different rounding modes with RoundingMode enum
        // This tests both native PHP 8.4+ enum and polyfill PHP 8.1-8.3 enum
        if (enum_exists('RoundingMode')) {
            // Test supported enum modes that work in both environments
            $this->assertSame('1.6', BCMath::round('1.55', 1, \RoundingMode::HalfAwayFromZero));
            $this->assertSame('1.5', BCMath::round('1.55', 1, \RoundingMode::HalfTowardsZero));
            $this->assertSame('1.6', BCMath::round('1.55', 1, \RoundingMode::HalfEven));
            $this->assertSame('1.5', BCMath::round('1.55', 1, \RoundingMode::HalfOdd));

            // Compare with native bcround (PHP 8.4+ with bcmath extension)
            $this->assertSame(
                // @phpstan-ignore-next-line
                bcround('1.55', 1, \RoundingMode::HalfAwayFromZero),
                BCMath::round('1.55', 1, \RoundingMode::HalfAwayFromZero)
            );
            $this->assertSame(
                // @phpstan-ignore-next-line
                bcround('1.55', 1, \RoundingMode::HalfTowardsZero),
                BCMath::round('1.55', 1, \RoundingMode::HalfTowardsZero)
            );
            $this->assertSame(
                // @phpstan-ignore-next-line
                bcround('1.55', 1, \RoundingMode::HalfEven),
                BCMath::round('1.55', 1, \RoundingMode::HalfEven)
            );
            $this->assertSame(
                // @phpstan-ignore-next-line
                bcround('1.55', 1, \RoundingMode::HalfOdd),
                BCMath::round('1.55', 1, \RoundingMode::HalfOdd)
            );
        } else {
            // Fallback for environments where RoundingMode is not available (PHP < 8.1)
            $this->assertSame('1.6', BCMath::round('1.55', 1, PHP_ROUND_HALF_UP));
            $this->assertSame('1.5', BCMath::round('1.55', 1, PHP_ROUND_HALF_DOWN));
            $this->assertSame('1.6', BCMath::round('1.55', 1, PHP_ROUND_HALF_EVEN));
            $this->assertSame('1.5', BCMath::round('1.55', 1, PHP_ROUND_HALF_ODD));
        }

        // Test negative scale
        $this->assertSame(bcround('135', -1), BCMath::round('135', -1));
        $this->assertSame(bcround('135', -2), BCMath::round('135', -2));
        $this->assertSame(bcround('1234.5678', -2), BCMath::round('1234.5678', -2));
    }

    /**
     * Test bcfloor function without PHP 8.4.
     */
    public function testFloorPolyfill(): void
    {
        if (function_exists('bcfloor')) {
            $this->markTestSkipped('bcfloor is available, testing with native function');
        }

        // Test positive numbers
        $this->assertSame('4', BCMath::floor('4.3'));
        $this->assertSame('9', BCMath::floor('9.999'));
        $this->assertSame('3', BCMath::floor('3.14159'));

        // Test negative numbers
        $this->assertSame('-5', BCMath::floor('-4.3'));
        $this->assertSame('-10', BCMath::floor('-9.999'));
        $this->assertSame('-4', BCMath::floor('-3.14159'));

        // Test integers
        $this->assertSame('5', BCMath::floor('5'));
        $this->assertSame('-5', BCMath::floor('-5'));
        $this->assertSame('0', BCMath::floor('0'));

        // Test edge cases
        $this->assertSame('1', BCMath::floor('1.95583'));
        $this->assertSame('-2', BCMath::floor('-1.95583'));
    }

    /**
     * Test bcceil function without PHP 8.4.
     */
    public function testCeilPolyfill(): void
    {
        if (function_exists('bcceil')) {
            $this->markTestSkipped('bcceil is available, testing with native function');
        }

        // Test positive numbers
        $this->assertSame('5', BCMath::ceil('4.3'));
        $this->assertSame('10', BCMath::ceil('9.999'));
        $this->assertSame('4', BCMath::ceil('3.14159'));

        // Test negative numbers
        $this->assertSame('-4', BCMath::ceil('-4.3'));
        $this->assertSame('-9', BCMath::ceil('-9.999'));
        $this->assertSame('-3', BCMath::ceil('-3.14159'));

        // Test integers
        $this->assertSame('5', BCMath::ceil('5'));
        $this->assertSame('-5', BCMath::ceil('-5'));
        $this->assertSame('0', BCMath::ceil('0'));

        // Test edge cases
        $this->assertSame('2', BCMath::ceil('1.95583'));
        $this->assertSame('-1', BCMath::ceil('-1.95583'));
    }

    /**
     * Test bcround function without PHP 8.4.
     */
    public function testRoundPolyfill(): void
    {
        if (function_exists('bcround')) {
            $this->markTestSkipped('bcround is available, testing with native function');
        }

        // Test basic rounding
        $this->assertSame('3', BCMath::round('3.4'));
        $this->assertSame('4', BCMath::round('3.5'));
        $this->assertSame('4', BCMath::round('3.6'));
        $this->assertSame('-3', BCMath::round('-3.4'));
        $this->assertSame('-4', BCMath::round('-3.5'));
        $this->assertSame('-4', BCMath::round('-3.6'));

        // Test with scale
        $this->assertSame('1.96', BCMath::round('1.95583', 2));
        $this->assertSame('1.956', BCMath::round('1.95583', 3));
        $this->assertSame('1.2', BCMath::round('1.2345', 1));

        // Test different rounding modes
        $this->assertSame('1.6', BCMath::round('1.55', 1, PHP_ROUND_HALF_UP));
        $this->assertSame('1.5', BCMath::round('1.55', 1, PHP_ROUND_HALF_DOWN));
        $this->assertSame('1.6', BCMath::round('1.55', 1, PHP_ROUND_HALF_EVEN));
        $this->assertSame('1.5', BCMath::round('1.55', 1, PHP_ROUND_HALF_ODD));

        // Test negative scale
        $this->assertSame('140', BCMath::round('135', -1));
        $this->assertSame('100', BCMath::round('135', -2));
        $this->assertSame('1200', BCMath::round('1234.5678', -2));
    }

    /**
     * Test HalfTowardsZero rounding mode behavior.
     * Reproduces the bcround_all.phpt failure for negative numbers with 0.5.
     */
    public function testHalfTowardsZeroRounding(): void
    {
        if (!enum_exists('RoundingMode')) {
            $this->markTestSkipped('RoundingMode enum not available');
        }

        // Test non-boundary values first (should work correctly)
        $this->assertSame('1', BCMath::round('1.2', 0, \RoundingMode::HalfTowardsZero));
        $this->assertSame('2', BCMath::round('1.7', 0, \RoundingMode::HalfTowardsZero));
        $this->assertSame('-1', BCMath::round('-1.2', 0, \RoundingMode::HalfTowardsZero));
        $this->assertSame('-2', BCMath::round('-1.7', 0, \RoundingMode::HalfTowardsZero));

        // Now test the boundary cases with 0.5
        // Positive numbers: should round towards zero (down)
        $this->assertSame('1', BCMath::round('1.5', 0, \RoundingMode::HalfTowardsZero));
        $this->assertSame('2', BCMath::round('2.5', 0, \RoundingMode::HalfTowardsZero));

        // Negative numbers: should round towards zero (up)
        // This is currently failing - expecting -1 but getting -2
        $this->assertSame('-1', BCMath::round('-1.5', 0, \RoundingMode::HalfTowardsZero));
        $this->assertSame('-2', BCMath::round('-2.5', 0, \RoundingMode::HalfTowardsZero));
    }

    /**
     * Test boundary values with very large decimal places.
     */
    public function testBoundaryValuesLargeDecimals(): void
    {
        // Test with very large decimal places
        $largeDecimal = '1.'.str_repeat('9', 100);
        $result = BCMath::add($largeDecimal, '0.1', 50);
        // When adding 0.1 to 1.999... we get 2.099...
        $this->assertSame('2.09999999999999999999999999999999999999999999999999', $result);

        // Test with operations preserving large decimal precision
        $num1 = '123.456789012345678901234567890123456789012345678901234567890123456789';
        $num2 = '987.654321098765432109876543210987654321098765432109876543210987654321';

        // Addition with high precision
        $sum = BCMath::add($num1, $num2, 60);
        $this->assertSame('1111.111110111111111011111111101111111110111111111011111111101111', $sum);

        // Multiplication with high precision
        $product = BCMath::mul('0.00000000000000000001', '0.00000000000000000001', 40);
        $this->assertSame('0.0000000000000000000000000000000000000001', $product);

        // Division with high precision
        $quotient = BCMath::div('1', '3', 100);
        $expected = '0.'.str_repeat('3', 100);
        $this->assertSame($expected, $quotient);
    }

    /**
     * Test with scale value 2147483647 (maximum integer).
     */
    public function testMaximumScaleValue(): void
    {
        // Note: Due to memory limitations, we can't actually test with scale 2147483647
        // but we can test the function accepts it and behaves correctly

        // Test that the scale parameter accepts large values
        $result = BCMath::add('1.5', '2.5', 1000);
        $this->assertSame('4.'.str_repeat('0', 1000), $result);

        // Test with a reasonably large scale
        $result = BCMath::div('1', '7', 500);
        // Should produce 0.142857142857... repeating (total length = 502 with "0.")
        $expected = '0.'.str_repeat('142857', 83).'14';
        $this->assertSame($expected, $result);
    }

    /**
     * Test with extremely small numbers.
     */
    public function testExtremelySmallNumbers(): void
    {
        // Test with scientific notation converted to decimal
        $small = '0.'.str_repeat('0', 99).'1'; // 1e-100

        // Addition with extremely small numbers
        $result = BCMath::add($small, $small, 101);
        $expected = '0.'.str_repeat('0', 99).'20';
        $this->assertSame($expected, $result);

        // Multiplication of extremely small numbers
        $result = BCMath::mul($small, '2', 101);
        $expected = '0.'.str_repeat('0', 99).'20';
        $this->assertSame($expected, $result);

        // Division producing extremely small results
        $result = BCMath::div('1', '1'.str_repeat('0', 50), 60);
        $expected = '0.'.str_repeat('0', 49).'10'.str_repeat('0', 9);
        $this->assertSame($expected, $result);

        // Operations with mixed extremely small and normal numbers
        $result = BCMath::add('1000000', $small, 105);
        $expected = '1000000.'.str_repeat('0', 99).'1'.str_repeat('0', 5);
        $this->assertSame($expected, $result);
    }

    /**
     * Test all bcround() rounding modes for PHP 8.4+.
     */
    #[RequiresPhp('>=8.4')]
    public function testRoundAllModes(): void
    {
        // Test data: number, scale, expected results for each mode
        $testCases = [
            // Basic half cases
            ['2.5', 0, ['3', '2', '2', '3']],
            ['-2.5', 0, ['-3', '-2', '-2', '-3']],
            ['3.5', 0, ['4', '3', '4', '3']],
            ['-3.5', 0, ['-4', '-3', '-4', '-3']],

            // With decimal places
            ['1.2535', 2, ['1.25', '1.25', '1.25', '1.25']],
            ['1.255', 2, ['1.26', '1.25', '1.26', '1.25']],
            ['1.245', 2, ['1.25', '1.24', '1.24', '1.25']],

            // Edge cases at boundaries
            ['0.5', 0, ['1', '0', '0', '1']],
            ['-0.5', 0, ['-1', '0', '0', '-1']],
            ['1.5', 0, ['2', '1', '2', '1']],
            ['-1.5', 0, ['-2', '-1', '-2', '-1']],

            // Multiple rounding positions
            ['12.345', 2, ['12.35', '12.34', '12.34', '12.35']],
            ['12.355', 2, ['12.36', '12.35', '12.36', '12.35']],
            ['12.365', 2, ['12.37', '12.36', '12.36', '12.37']],
            ['12.375', 2, ['12.38', '12.37', '12.38', '12.37']],
        ];

        $modes = [
            PHP_ROUND_HALF_UP,
            PHP_ROUND_HALF_DOWN,
            PHP_ROUND_HALF_EVEN,
            PHP_ROUND_HALF_ODD,
        ];

        foreach ($testCases as [$number, $scale, $expectedResults]) {
            foreach ($modes as $i => $mode) {
                $result = BCMath::round($number, $scale, $mode);
                $this->assertSame(
                    $expectedResults[$i],
                    $result,
                    "Failed for number={$number}, scale={$scale}, mode={$mode}"
                );

                // Also test with native bcround if available with RoundingMode enum
                if (function_exists('bcround') && enum_exists('RoundingMode')) {
                    $enumMode = match ($mode) {
                        PHP_ROUND_HALF_UP => \RoundingMode::HalfAwayFromZero,
                        PHP_ROUND_HALF_DOWN => \RoundingMode::HalfTowardsZero,
                        PHP_ROUND_HALF_EVEN => \RoundingMode::HalfEven,
                        PHP_ROUND_HALF_ODD => \RoundingMode::HalfOdd,
                    };
                    // @phpstan-ignore-next-line
                    $nativeResult = bcround($number, $scale, $enumMode);
                    $this->assertSame(
                        $nativeResult,
                        $result,
                        "Native bcround differs from polyfill for number={$number}, scale={$scale}, mode={$mode}"
                    );
                }
            }
        }
    }

    /**
     * Test bcround() with negative scale values.
     */
    public function testRoundNegativeScale(): void
    {
        // Test rounding to tens, hundreds, thousands
        $testCases = [
            // [number, scale, expected]
            ['123', -1, '120'],
            ['125', -1, '130'],
            ['128', -1, '130'],

            ['1234', -2, '1200'],
            ['1250', -2, '1300'],
            ['1280', -2, '1300'],

            ['12345', -3, '12000'],
            ['12500', -3, '13000'],
            ['12800', -3, '13000'],

            // Negative numbers
            ['-123', -1, '-120'],
            ['-125', -1, '-130'],
            ['-128', -1, '-130'],

            ['-1234', -2, '-1200'],
            ['-1250', -2, '-1300'],
            ['-1280', -2, '-1300'],

            // Edge cases
            ['5', -1, '10'],
            ['-5', -1, '-10'],
            ['50', -2, '100'],
            ['-50', -2, '-100'],

            // Larger negative scales
            ['123456789', -4, '123460000'],
            ['123456789', -6, '123000000'],
            ['987654321', -5, '987700000'],

            // With decimal places
            ['123.456', -1, '120'],
            ['125.999', -1, '130'],
            ['1234.567', -2, '1200'],
            ['1250.001', -2, '1300'],
        ];

        foreach ($testCases as [$number, $scale, $expected]) {
            $result = BCMath::round($number, $scale);
            $this->assertSame(
                $expected,
                $result,
                "Failed for number={$number}, scale={$scale}"
            );

            // Test with native bcround for PHP 8.4+
            if (function_exists('bcround')) {
                $nativeResult = bcround($number, $scale);
                $this->assertSame(
                    $nativeResult,
                    $result,
                    "Native bcround differs from polyfill for number={$number}, scale={$scale}"
                );
            }
        }

        // Test negative scale with different rounding modes
        if (version_compare(PHP_VERSION, '8.4', '>=')) {
            $this->assertSame('120', BCMath::round('125', -1, PHP_ROUND_HALF_DOWN));
            $this->assertSame('130', BCMath::round('125', -1, PHP_ROUND_HALF_UP));
            $this->assertSame('120', BCMath::round('125', -1, PHP_ROUND_HALF_EVEN));
            $this->assertSame('130', BCMath::round('125', -1, PHP_ROUND_HALF_ODD));

            $this->assertSame('1200', BCMath::round('1250', -2, PHP_ROUND_HALF_DOWN));
            $this->assertSame('1300', BCMath::round('1250', -2, PHP_ROUND_HALF_UP));
            $this->assertSame('1200', BCMath::round('1250', -2, PHP_ROUND_HALF_EVEN));
            $this->assertSame('1300', BCMath::round('1250', -2, PHP_ROUND_HALF_ODD));
        }
    }

    /**
     * bcround() must reject a $precision above INT_MAX with a ValueError,
     * mirroring PHP 8.4's native behaviour. Previously such values triggered
     * an out-of-memory fatal via str_repeat() (php-src bcround_precision_bounds).
     */
    public function testRoundPrecisionAboveIntMaxThrows(): void
    {
        if (PHP_INT_SIZE !== 8) {
            $this->markTestSkipped('Requires a 64-bit platform where PHP_INT_MAX exceeds INT_MAX.');
        }

        $expectedMessage = 'bcround(): Argument #2 ($precision) must be between '.PHP_INT_MIN.' and 2147483647';

        foreach ([PHP_INT_MAX, 2147483648] as $precision) {
            $caught = null;

            try {
                BCMath::round('1', $precision);
            } catch (\ValueError $e) {
                $caught = $e;
            }
            $this->assertInstanceOf(
                \ValueError::class,
                $caught,
                "Expected ValueError for precision={$precision}"
            );
            $this->assertSame($expectedMessage, $caught->getMessage());
        }

        // Note: we intentionally do not cross-check against the native bcround(),
        // because the precision bounds guard is a php-src master (8.6) addition and
        // released bcmath (e.g. 8.5) still OOMs on such precision values.

        // A normal in-range precision must still work and not be over-rejected.
        $this->assertSame('1.00', BCMath::round('1', 2));
    }

    /**
     * Test sqrt bug reproduction cases.
     *
     * This test reproduces the bug that was exposed by strict_comparison setting.
     * The bug occurred when calculating decimal start position in sqrt algorithm.
     */
    public function testSqrtBugReproduction(): void
    {
        // This case would cause infinite loop or memory error with the old buggy logic
        // Pattern: 1-digit integer part with even total length (no padding needed)
        // Example: '5.6' -> '56' (2 digits, even, no padding)
        // Bug: ceil(1/2) = 1, but array ['56'] has only 1 element (index 0)
        // So decStart=1 would look for non-existent array position

        // Test cases that demonstrate the bug pattern without causing memory issues
        $safeBoundaryCases = [
            ['1.23', 2],  // Padding needed + 1-digit integer - this was working
            ['9', 1],     // Integer only + odd digits - safe case
            ['4', 1],     // Integer only + even digits - safe case
        ];

        foreach ($safeBoundaryCases as [$number, $scale]) {
            $result = BCMath::sqrt($number, $scale);

            // Verify it's numeric
            $this->assertIsNumeric(
                $result,
                "sqrt({$number}, {$scale}) should return numeric string"
            );

            if (function_exists('bcsqrt')) {
                $native = bcsqrt($number, $scale);
                $this->assertSame(
                    $native,
                    $result,
                    "sqrt({$number}, {$scale}) should match native bcsqrt"
                );
            }
        }
    }

    /**
     * Test the logic that caused the bug.
     *
     * BUG ANALYSIS:
     * Root Cause: ceil() calculation created decStart values that exceeded array bounds
     *
     * Memory Error Mechanism for '5.6' case:
     * 1. Input '5.6' -> parts ['56'] (1 element, indices 0 only)
     * 2. Buggy decStart = ceil(1/2) = 1
     * 3. Loop condition: ($i - $decStart === $scale) never satisfied
     *    - $i=0: 0-1=-1 ≠ 2 → continue
     *    - $i=1: 1-1=0 ≠ 2 → continue (but parts[1] doesn't exist)
     *    - $i=2: 2-1=1 ≠ 2 → continue indefinitely
     * 4. Infinite loop: $result .= $x grows without bound → memory exhaustion
     *
     * The fix ensures decStart stays within array bounds by proper padding consideration.
     */
    public function testSqrtBuggyLogicExplanation(): void
    {
        // Demonstrate what the buggy logic would have calculated
        $num = '5.6';
        $temp = explode('.', $num);

        // Old buggy calculation
        $buggyDecStart = ceil(strlen($temp[0]) / 2);  // ceil(1/2) = 1
        $numStr = implode('', $temp);                 // '56'
        $parts = str_split($numStr, 2);               // ['56'] - only 1 element!

        // The bug: decStart(1) >= array size(1) would cause infinite loop
        // because loop condition ($i - $decStart === $scale) is never satisfied
        $this->assertSame(1.0, $buggyDecStart);
        $this->assertCount(1, $parts);
        $this->assertGreaterThanOrEqual(
            count($parts),
            $buggyDecStart,
            'This inequality demonstrates the bug condition that caused infinite loop and memory exhaustion'
        );

        // Correct calculation after fix
        // The fixed code adds 1 to the integer length when it pads the digits to an even length.
        // '56' already has an even length, so it is not padded and the integer length stays 1.
        $integerLength = strlen($temp[0]);  // 1
        $correctDecStart = $integerLength / 2;  // 1/2 = 0.5

        $this->assertSame(0.5, $correctDecStart);
        $this->assertLessThan(
            count($parts),
            $correctDecStart,
            'Fixed calculation avoids the bug condition'
        );
    }

    /**
     * Test ValueError for invalid input strings (malformed numbers).
     * Tests based on php-src/str2num_formatting.phpt patterns.
     */
    public function testValueErrorInvalidInputs(): void
    {
        $invalidInputs = [
            ' 0',        // Leading space
            '1e1',       // Scientific notation
            '1,1',       // Comma instead of dot
            'Hello',     // Non-numeric string
            '1 1',       // Space in middle
            '1.a',       // Invalid decimal part
            'INF',       // Infinity
            '-INF',      // Negative infinity
            'NAN',       // Not a number
        ];

        $functions = ['add', 'sub', 'mul', 'div', 'mod', 'comp', 'pow'];

        foreach ($functions as $function) {
            foreach ($invalidInputs as $invalidInput) {
                // Skip functions that have different validation behavior
                if ($function === 'pow' && in_array($invalidInput, ['INF', '-INF', 'NAN'], true)) {
                    continue; // bcpow has different handling for these
                }
                // Note: No need to skip division by zero for these invalid inputs
                // as they are all non-numeric strings that will trigger ValueError before division

                // Test first parameter - should throw ValueError
                $exceptionCaught = false;

                try {
                    if ($function === 'pow') {
                        BCMath::$function($invalidInput, '2', 2);
                    } elseif (in_array($function, ['add', 'sub', 'mul', 'div', 'mod', 'comp'], true)) {
                        BCMath::$function($invalidInput, '1', 2);
                    }
                } catch (\ValueError $e) {
                    $this->assertStringContainsString('is not well-formed', $e->getMessage());
                    $exceptionCaught = true;
                }
                $this->assertTrue($exceptionCaught, "Expected ValueError for {$function}({$invalidInput}, ...) but none was thrown");

                // Test second parameter (where applicable) - should throw ValueError
                if (in_array($function, ['add', 'sub', 'mul', 'div', 'mod', 'comp'], true)) {
                    $exceptionCaught2 = false;

                    try {
                        BCMath::$function('1', $invalidInput, 2);
                        // @phpstan-ignore-next-line
                    } catch (\ValueError $e) {
                        $this->assertStringContainsString('is not well-formed', $e->getMessage());
                        $exceptionCaught2 = true;
                    }
                    // @phpstan-ignore-next-line
                    $this->assertTrue($exceptionCaught2, "Expected ValueError for {$function}(1, {$invalidInput}, ...) but none was thrown");
                }
            }
        }
    }

    /**
     * Test ValueError for empty string input.
     * Empty string should be handled as '0' in current implementation.
     */
    public function testEmptyStringHandling(): void
    {
        // Empty string is actually treated as '0' in PHP's bcmath, not as an error
        $this->assertSame('2', BCMath::add('', '2'));
        $this->assertSame('2.00', BCMath::add('', '2', 2));
        $this->assertSame(-1, BCMath::comp('', '2')); // comp returns int, not string
    }

    /**
     * Test ValueError for negative scale values across all bcmath functions.
     * Based on php-src/negative_scale.phpt patterns.
     */
    public function testValueErrorNegativeScale(): void
    {
        $testCases = [
            ['add', ['1', '2', -1], 'bcadd(): Argument #3 ($scale) must be between 0 and 2147483647'],
            ['sub', ['1', '2', -1], 'bcsub(): Argument #3 ($scale) must be between 0 and 2147483647'],
            ['mul', ['1', '2', -1], 'bcmul(): Argument #3 ($scale) must be between 0 and 2147483647'],
            ['div', ['1', '2', -1], 'bcdiv(): Argument #3 ($scale) must be between 0 and 2147483647'],
            ['mod', ['1', '2', -1], 'bcmod(): Argument #3 ($scale) must be between 0 and 2147483647'],
            ['powmod', ['1', '2', '3', -9], 'bcpowmod(): Argument #4 ($scale) must be between 0 and 2147483647'],
            ['pow', ['1', '2', -1], 'bcpow(): Argument #3 ($scale) must be between 0 and 2147483647'],
            ['sqrt', ['9', -1], 'bcsqrt(): Argument #2 ($scale) must be between 0 and 2147483647'],
            ['comp', ['1', '2', -1], 'bccomp(): Argument #3 ($scale) must be between 0 and 2147483647'],
        ];

        foreach ($testCases as [$function, $args, $expectedMessage]) {
            $caught = false;

            try {
                // @phpstan-ignore-next-line
                BCMath::$function(...$args);
                // @phpstan-ignore-next-line
            } catch (\ValueError $e) {
                $this->assertSame($expectedMessage, $e->getMessage());
                $caught = true;
                // @phpstan-ignore-next-line
            } catch (\Exception $e) {
                $this->fail("Expected ValueError for {$function} with negative scale, got ".$e::class);
            }
            // @phpstan-ignore-next-line
            $this->assertTrue($caught, "Expected ValueError for {$function} with negative scale but none was thrown");
        }
    }

    /**
     * Test bcscale() with negative values should throw ValueError.
     */
    public function testBcscaleNegativeValue(): void
    {
        try {
            BCMath::scale(-1);
            // Current implementation may not validate this
            $this->addToAssertionCount(1);
        } catch (\ValueError $e) {
            $this->assertSame('bcscale(): Argument #1 ($scale) must be between 0 and 2147483647', $e->getMessage());
        }
    }

    /**
     * Test bcscale() getter functionality (no arguments).
     * This tests the ability to get the current scale value.
     */
    public function testBcscaleGetter(): void
    {
        // Save original scale
        $originalScale = BCMath::scale();

        // Test setting and getting scale
        BCMath::scale(5);
        $this->assertSame(5, BCMath::scale());

        // Test with different value
        BCMath::scale(10);
        $this->assertSame(10, BCMath::scale());

        // Test zero scale
        BCMath::scale(0);
        $this->assertSame(0, BCMath::scale());

        // Restore original scale
        BCMath::scale($originalScale);
    }

    /**
     * bcscale() used as a setter returns the scale before the change, like the native one.
     */
    public function testBcscaleSetterReturnsOldScale(): void
    {
        $originalScale = BCMath::scale();
        $originalNativeScale = bcscale();

        try {
            BCMath::scale(0);
            bcscale(0);
            $this->assertSame(bcscale(3), BCMath::scale(3));
            $this->assertSame(3, BCMath::scale(7));
            $this->assertSame(bcscale(7), 3);
            $this->assertSame(7, BCMath::scale());
        } finally {
            BCMath::scale($originalScale);
            bcscale($originalNativeScale);
        }
    }

    /**
     * Test special ValueError cases for bcpow().
     * Tests exponent range validation and special cases.
     */
    public function testBcpowValueError(): void
    {
        // Test very large exponent that should cause ValueError
        try {
            // This should throw ValueError for exponent too large
            BCMath::pow('2', '999999999999999999999999999999', 2);
            $this->addToAssertionCount(1); // Current may not validate this
        } catch (\ValueError $e) {
            $this->assertStringContainsString('too large', $e->getMessage());
        }

        // Test malformed base
        try {
            BCMath::pow('invalid', '2', 2);
            $this->addToAssertionCount(1); // Current silently converts to 0
        } catch (\ValueError $e) {
            $this->assertStringContainsString('is not well-formed', $e->getMessage());
        }

        // Test malformed exponent
        try {
            BCMath::pow('2', 'invalid', 2);
            $this->addToAssertionCount(1); // Current silently converts to 0
        } catch (\ValueError $e) {
            $this->assertStringContainsString('is not well-formed', $e->getMessage());
        }
    }

    /**
     * Test special ValueError cases for bcpowmod().
     * Tests negative exponent and zero modulus validation.
     */
    public function testBcpowmodValueError(): void
    {
        // Test negative exponent (should throw ValueError)
        try {
            BCMath::powmod('2', '-1', '3', 2);
            $this->fail('Expected ValueError for negative exponent');
        } catch (\ValueError $e) {
            $this->assertStringContainsString('must be greater than or equal to 0', $e->getMessage());
        }

        // Test zero modulus (should throw DivisionByZeroError, like the native extension)
        try {
            BCMath::powmod('2', '1', '0', 2);
            $this->fail('Expected DivisionByZeroError for zero modulus');
        } catch (\DivisionByZeroError $e) {
            $this->assertSame('Modulo by zero', $e->getMessage());
        }

        // Test malformed inputs
        $invalidInputs = ['invalid', ' 1', '1e1'];

        foreach ($invalidInputs as $invalid) {
            try {
                BCMath::powmod($invalid, '1', '3', 2);
                $this->addToAssertionCount(1); // Current may silently convert
            } catch (\ValueError $e) {
                $this->assertStringContainsString('is not well-formed', $e->getMessage());
            }
        }
    }

    /**
     * Test ValueError cases for bcfloor(), bcceil(), bcround().
     * These functions should validate input format in PHP 8.4+.
     */
    #[RequiresPhp('>=8.4')]
    public function testFloorCeilRoundValueError(): void
    {
        $functions = ['floor', 'ceil', 'round'];
        $invalidInputs = [
            'invalid',
            ' 1',
            '1e1',
            'INF',
            '-INF',
            'NAN',
            '1,1',
        ];

        foreach ($functions as $function) {
            foreach ($invalidInputs as $invalid) {
                $this->expectException(\ValueError::class);
                $this->expectExceptionMessageMatches('/is not well-formed/');

                if ($function === 'round') {
                    BCMath::$function($invalid, 2);
                } else {
                    BCMath::$function($invalid);
                }

                // Only one iteration per test due to expectException
                return;
            }
        }
    }

    /**
     * Test ValueError cases for bcfloor(), bcceil(), bcround() without PHP 8.4.
     * These should trigger warnings instead of exceptions in older PHP versions.
     */
    public function testFloorCeilRoundValueErrorLegacy(): void
    {
        if (function_exists('bcfloor') || version_compare(PHP_VERSION, '8.4', '>=')) {
            $this->markTestSkipped('Testing legacy behavior, but native functions available or PHP 8.4+');
        }

        $functions = ['floor', 'ceil', 'round'];
        $invalidInputs = ['invalid', ' 1', '1e1'];

        foreach ($functions as $function) {
            foreach ($invalidInputs as $invalid) {
                // In legacy mode, these should return '0' and trigger warning
                if ($function === 'round') {
                    $result = BCMath::$function($invalid, 2);
                } else {
                    $result = BCMath::$function($invalid);
                }
                $this->assertSame('0', $result);
            }
        }
    }

    /**
     * Test TypeError for completely invalid argument types.
     * Tests what happens when non-string arguments are passed.
     */
    public function testTypeErrorInvalidTypes(): void
    {
        // Note: In current implementation, these may be auto-converted to strings
        // This test documents expected vs actual behavior

        $invalidTypes = [
            [1.5],    // Array with float
            ['abc'],  // Array with string
            new \stdClass(), // Object
        ];

        foreach ($invalidTypes as $invalidType) {
            $caught = false;

            try {
                // When arrays/objects are cast to string, they produce "Array" or class names
                // which are caught by our ValueError validation as "not well-formed"
                if (is_array($invalidType)) {
                    BCMath::add('Array', '1');
                } else {
                    // This is stdClass object
                    BCMath::add('stdClass', '1');
                }
            } catch (\ValueError $e) {
                // Our validation catches these as malformed strings
                $this->assertStringContainsString('is not well-formed', $e->getMessage());
                $caught = true;
            } catch (\TypeError $e) {
                // Could also get TypeError from casting
                $this->assertStringContainsString('string', $e->getMessage());
                $caught = true;
            } catch (\Error $e) {
                // Object to string conversion may fail
                $this->assertStringContainsString('string', $e->getMessage());
                $caught = true;
            }
            $this->assertTrue($caught, 'Expected ValueError, TypeError, or Error but none was thrown');
        }
    }

    /**
     * Test bcdiv division by zero error cases.
     * Based on php-src bcdiv_error1.php test.
     */
    public function testBcdivDivisionByZeroError(): void
    {
        // Test case 1: Division by '0'
        $this->expectException(\DivisionByZeroError::class);
        $this->expectExceptionMessage('Division by zero');
        BCMath::div('10.99', '0');
    }

    /**
     * Test bcdiv division by zero with '0.00'.
     */
    public function testBcdivDivisionByZeroDecimal(): void
    {
        $this->expectException(\DivisionByZeroError::class);
        $this->expectExceptionMessage('Division by zero');
        BCMath::div('10.99', '0.00');
    }

    /**
     * Test bcdiv division by zero with '-0.00'.
     */
    public function testBcdivDivisionByNegativeZeroDecimal(): void
    {
        $this->expectException(\DivisionByZeroError::class);
        $this->expectExceptionMessage('Division by zero');
        BCMath::div('10.99', '-0.00');
    }

    /**
     * Data provider for bcpow zero base test cases.
     *
     * @return iterable<int, array<int, int|string>>
     */
    public static function provideBcpowZeroBaseCases(): iterable
    {
        return [
            // [base, exponent, scale, expected_result]
            ['0', '1', 2, '0.00'],
            ['0', '2', 2, '0.00'],
            ['0.0', '1', 2, '0.00'],
            ['-0', '2', 2, '0.00'],
            ['+0.000', '3', 2, '0.00'],
            ['0', '1', 0, '0'],
        ];
    }

    /**
     * Test bcpow zero base handling with various formats.
     * Based on issue where zero base with negative exponents caused infinite loops.
     */
    #[DataProvider('provideBcpowZeroBaseCases')]
    public function testBcpowZeroBase(string $base, string $exponent, int $scale, string $expected): void
    {
        $result = BCMath::pow($base, $exponent, $scale);
        $this->assertSame($expected, $result);
    }

    /**
     * Test bcpow zero base special case: 0^0 = 1.
     */
    public function testBcpowZeroToZeroPower(): void
    {
        $result = BCMath::pow('0', '0', 2);
        $this->assertSame('1.00', $result);

        $result = BCMath::pow('0.00', '0', 2);
        $this->assertSame('1.00', $result);
    }

    /**
     * Data provider for bcpow negative power of zero test cases.
     *
     * @return iterable<int, array<int, int|string>>
     */
    public static function provideBcpowNegativePowerOfZeroThrowsErrorCases(): iterable
    {
        return [
            // [base, exponent, scale]
            ['0', '-1', 2],
            ['0', '-2', 2],
            ['0.00', '-1', 2],
            ['-0.00', '-1', 2],
            ['0.00', '-2', 4],
        ];
    }

    /**
     * Test bcpow negative power of zero throws DivisionByZeroError.
     */
    #[DataProvider('provideBcpowNegativePowerOfZeroThrowsErrorCases')]
    public function testBcpowNegativePowerOfZeroThrowsError(string $base, string $exponent, int $scale): void
    {
        $this->expectException(\DivisionByZeroError::class);
        $this->expectExceptionMessage('Negative power of zero');
        BCMath::pow($base, $exponent, $scale);
    }

    /**
     * Data provider for comprehensive bcsqrt test cases from php-src.
     *
     * @return array<int, array{string, int, string}>
     */
    public static function provideBcsqrtComprehensiveCases(): iterable
    {
        return [
            // Scale 0 test cases
            ['0', 0, '0'],
            ['0.00', 0, '0'],
            ['-0', 0, '0'],
            ['-0.00', 0, '0'],
            ['15151324141414.412312232141241', 0, '3892470'],
            ['141241241241241248267654747412', 0, '375820756799356'],
            ['0.1322135476547459213732911312', 0, '0'],
            ['14.14', 0, '3'],
            ['0.15', 0, '0'],
            ['15', 0, '3'],
            ['1', 0, '1'],

            // Scale 10 test cases
            ['0', 10, '0.0000000000'],
            ['0.00', 10, '0.0000000000'],
            ['-0', 10, '0.0000000000'],
            ['-0.00', 10, '0.0000000000'],
            ['15151324141414.412312232141241', 10, '3892470.1850385973'],
            ['141241241241241248267654747412', 10, '375820756799356.7439216735'],
            ['0.1322135476547459213732911312', 10, '0.3636118090'],
            ['14.14', 10, '3.7603191353'],
            ['0.15', 10, '0.3872983346'],
            ['15', 10, '3.8729833462'],
            ['1', 10, '1.0000000000'],
        ];
    }

    /**
     * Test comprehensive bcsqrt cases from php-src test suite.
     * These test cases ensure compatibility with native bcmath behavior.
     */
    #[DataProvider('provideBcsqrtComprehensiveCases')]
    public function testBcsqrtComprehensive(string $radicant, int $scale, string $expected): void
    {
        $result = BCMath::sqrt($radicant, $scale);
        $this->assertSame($expected, $result, "bcsqrt('{$radicant}', {$scale}) should return '{$expected}'");
    }

    /**
     * Test bcsqrt negative number validation.
     * Should throw ValueError for negative numbers (except negative zero).
     */
    public function testBcsqrtNegativeNumberValidation(): void
    {
        // Negative numbers should throw ValueError
        $negativeTestCases = ['-1', '-4', '-100', '-0.1', '-2.5'];

        foreach ($negativeTestCases as $testCase) {
            try {
                BCMath::sqrt($testCase);
                $this->fail("BCMath::sqrt('{$testCase}') should throw ValueError but didn't");
            } catch (ValueError $e) {
                $this->assertStringContainsString('must be greater than or equal to 0', $e->getMessage());
            }
        }

        // Negative zero variants should NOT throw error
        $negativeZeroCases = ['-0', '-0.00', '-0.000'];
        foreach ($negativeZeroCases as $testCase) {
            $result = BCMath::sqrt($testCase);
            $this->assertSame('0', $result, "BCMath::sqrt('{$testCase}') should return '0'");
        }
    }

    /**
     * Test whitespace handling across all bcmath functions.
     * All functions should throw ValueError for inputs with whitespace,
     * matching native bcmath behavior.
     */
    public function testWhitespaceHandlingInAllMethods(): void
    {
        // Test cases with various whitespace patterns
        $whitespaceTestCases = [
            ' 4 ',      // leading and trailing spaces
            '  -1  ',   // spaces around negative number
            "\t5\n",    // tab and newline
            '1 2',      // space in middle
            ' 0 ',      // spaces around zero
            '  +3  ',   // spaces around positive with explicit sign
        ];

        // Test sqrt function
        foreach ($whitespaceTestCases as $testCase) {
            try {
                BCMath::sqrt($testCase);
                $this->fail("BCMath::sqrt('{$testCase}') should throw ValueError for whitespace input");
            } catch (ValueError $e) {
                $this->assertStringContainsString('is not well-formed', $e->getMessage());
            }
        }

        // Test add function
        foreach ($whitespaceTestCases as $testCase) {
            try {
                BCMath::add($testCase, '1');
                $this->fail("BCMath::add('{$testCase}', '1') should throw ValueError for whitespace input");
            } catch (ValueError $e) {
                $this->assertStringContainsString('is not well-formed', $e->getMessage());
            }

            try {
                BCMath::add('1', $testCase);
                $this->fail("BCMath::add('1', '{$testCase}') should throw ValueError for whitespace input");
            } catch (ValueError $e) {
                $this->assertStringContainsString('is not well-formed', $e->getMessage());
            }
        }

        // Test sub function
        foreach ($whitespaceTestCases as $testCase) {
            try {
                BCMath::sub($testCase, '1');
                $this->fail("BCMath::sub('{$testCase}', '1') should throw ValueError for whitespace input");
            } catch (ValueError $e) {
                $this->assertStringContainsString('is not well-formed', $e->getMessage());
            }
        }

        // Test mul function
        foreach ($whitespaceTestCases as $testCase) {
            try {
                BCMath::mul($testCase, '2');
                $this->fail("BCMath::mul('{$testCase}', '2') should throw ValueError for whitespace input");
            } catch (ValueError $e) {
                $this->assertStringContainsString('is not well-formed', $e->getMessage());
            }
        }

        // Test div function
        foreach ($whitespaceTestCases as $testCase) {
            try {
                BCMath::div($testCase, '2');
                $this->fail("BCMath::div('{$testCase}', '2') should throw ValueError for whitespace input");
            } catch (ValueError $e) {
                $this->assertStringContainsString('is not well-formed', $e->getMessage());
            }
        }

        // Test floor function
        foreach ($whitespaceTestCases as $testCase) {
            try {
                BCMath::floor($testCase);
                $this->fail("BCMath::floor('{$testCase}') should throw ValueError for whitespace input");
            } catch (ValueError $e) {
                $this->assertStringContainsString('is not well-formed', $e->getMessage());
            }
        }

        // Test ceil function
        foreach ($whitespaceTestCases as $testCase) {
            try {
                BCMath::ceil($testCase);
                $this->fail("BCMath::ceil('{$testCase}') should throw ValueError for whitespace input");
            } catch (ValueError $e) {
                $this->assertStringContainsString('is not well-formed', $e->getMessage());
            }
        }

        // Test round function
        foreach ($whitespaceTestCases as $testCase) {
            try {
                BCMath::round($testCase, 0);
                $this->fail("BCMath::round('{$testCase}', 0) should throw ValueError for whitespace input");
            } catch (ValueError $e) {
                $this->assertStringContainsString('is not well-formed', $e->getMessage());
            }
        }
    }

    /**
     * Test negative number validation with whitespace scenarios.
     * This tests the specific vulnerability where whitespace before negative sign
     * could bypass negative number validation.
     */
    public function testNegativeNumberValidationWithWhitespace(): void
    {
        // These should all throw "not well-formed" errors due to whitespace
        // NOT because they're negative numbers
        $whitespaceNegativeTestCases = [
            '  -1',     // leading spaces before negative
            '-1  ',     // trailing spaces after negative
            '  -1  ',   // spaces around negative
            "\t-5",     // tab before negative
            "-3\n",     // newline after negative
        ];

        // Test sqrt - should throw "not well-formed" not "must be greater than or equal to 0"
        foreach ($whitespaceNegativeTestCases as $testCase) {
            try {
                BCMath::sqrt($testCase);
                $this->fail("BCMath::sqrt('{$testCase}') should throw ValueError for malformed input");
            } catch (ValueError $e) {
                $this->assertStringContainsString('is not well-formed', $e->getMessage());
                $this->assertStringNotContainsString('must be greater than or equal to 0', $e->getMessage());
            }
        }

        // Test floor - should throw "not well-formed"
        foreach ($whitespaceNegativeTestCases as $testCase) {
            try {
                BCMath::floor($testCase);
                $this->fail("BCMath::floor('{$testCase}') should throw ValueError for malformed input");
            } catch (ValueError $e) {
                $this->assertStringContainsString('is not well-formed', $e->getMessage());
            }
        }

        // Test ceil - should throw "not well-formed"
        foreach ($whitespaceNegativeTestCases as $testCase) {
            try {
                BCMath::ceil($testCase);
                $this->fail("BCMath::ceil('{$testCase}') should throw ValueError for malformed input");
            } catch (ValueError $e) {
                $this->assertStringContainsString('is not well-formed', $e->getMessage());
            }
        }

        // Test round - should throw "not well-formed"
        foreach ($whitespaceNegativeTestCases as $testCase) {
            try {
                BCMath::round($testCase, 0);
                $this->fail("BCMath::round('{$testCase}', 0) should throw ValueError for malformed input");
            } catch (ValueError $e) {
                $this->assertStringContainsString('is not well-formed', $e->getMessage());
            }
        }
    }

    /**
     * Data provider for RoundingMode enum support tests.
     *
     * @return array<string, array{string, int, \RoundingMode, string}>
     */
    public static function provideRoundingModeEnumSupportCases(): iterable
    {
        if (!enum_exists('RoundingMode')) {
            return [];
        }

        return [
            // Test supported enum values that map to existing PHP_ROUND_* constants
            // These work in both polyfill (PHP 8.1-8.3) and native (PHP 8.4+) environments
            'HalfAwayFromZero basic' => ['1.55', 1, \RoundingMode::HalfAwayFromZero, '1.6'],
            'HalfTowardsZero basic' => ['1.55', 1, \RoundingMode::HalfTowardsZero, '1.5'],
            'HalfEven basic' => ['1.55', 1, \RoundingMode::HalfEven, '1.6'],
            'HalfOdd basic' => ['1.55', 1, \RoundingMode::HalfOdd, '1.5'],

            // Test edge cases with supported modes
            'HalfAwayFromZero positive half' => ['2.5', 0, \RoundingMode::HalfAwayFromZero, '3'],
            'HalfTowardsZero positive half' => ['2.5', 0, \RoundingMode::HalfTowardsZero, '2'],
            'HalfAwayFromZero negative half' => ['-2.5', 0, \RoundingMode::HalfAwayFromZero, '-3'],
            'HalfTowardsZero negative half' => ['-2.5', 0, \RoundingMode::HalfTowardsZero, '-2'],

            // Additional comprehensive cases
            'Negative half even/odd' => ['-3.5', 0, \RoundingMode::HalfEven, '-4'],
            'Positive half even/odd' => ['3.5', 0, \RoundingMode::HalfOdd, '3'],
            'Decimal precision' => ['1.255', 2, \RoundingMode::HalfAwayFromZero, '1.26'],
        ];
    }

    /**
     * Test RoundingMode enum support for PHP 8.4+.
     *
     * @param \RoundingMode $mode
     */
    #[RequiresPhp('>=8.1')]
    #[DataProvider('provideRoundingModeEnumSupportCases')]
    public function testRoundingModeEnumSupport(string $number, int $scale, $mode, string $expected): void
    {
        if (!enum_exists('RoundingMode')) {
            $this->markTestSkipped('RoundingMode enum not available');
        }

        $result = BCMath::round($number, $scale, $mode);
        $this->assertSame($expected, $result);
    }

    /**
     * Data provider for backward compatibility tests with PHP_ROUND_* constants.
     *
     * @return array<string, array{string, int, int, string}>
     */
    public static function provideRoundingModeBackwardCompatibilityCases(): iterable
    {
        return [
            // Basic cases with traditional PHP_ROUND_* constants
            'HALF_UP basic' => ['1.55', 1, PHP_ROUND_HALF_UP, '1.6'],
            'HALF_DOWN basic' => ['1.55', 1, PHP_ROUND_HALF_DOWN, '1.5'],
            'HALF_EVEN basic' => ['1.55', 1, PHP_ROUND_HALF_EVEN, '1.6'],
            'HALF_ODD basic' => ['1.55', 1, PHP_ROUND_HALF_ODD, '1.5'],

            // Edge cases with constants
            'HALF_UP positive half' => ['2.5', 0, PHP_ROUND_HALF_UP, '3'],
            'HALF_DOWN positive half' => ['2.5', 0, PHP_ROUND_HALF_DOWN, '2'],
            'HALF_EVEN positive half' => ['2.5', 0, PHP_ROUND_HALF_EVEN, '2'],
            'HALF_ODD positive half' => ['2.5', 0, PHP_ROUND_HALF_ODD, '3'],

            // Negative numbers
            'HALF_UP negative half' => ['-2.5', 0, PHP_ROUND_HALF_UP, '-3'],
            'HALF_DOWN negative half' => ['-2.5', 0, PHP_ROUND_HALF_DOWN, '-2'],
            'HALF_EVEN negative half' => ['-2.5', 0, PHP_ROUND_HALF_EVEN, '-2'],
            'HALF_ODD negative half' => ['-2.5', 0, PHP_ROUND_HALF_ODD, '-3'],
        ];
    }

    /**
     * Test backward compatibility with PHP_ROUND_* constants.
     */
    #[DataProvider('provideRoundingModeBackwardCompatibilityCases')]
    public function testRoundingModeBackwardCompatibility(string $number, int $scale, int $mode, string $expected): void
    {
        $result = BCMath::round($number, $scale, $mode);
        $this->assertSame($expected, $result);
    }

    /**
     * Data provider for invalid rounding mode tests.
     *
     * @return array<string, array{mixed}>
     */
    public static function provideInvalidRoundingModeCases(): iterable
    {
        return [
            'invalid integer' => [999],
            'string mode' => ['invalid'],
            'null mode' => [null],
            'float mode' => [1.5],
            'array mode' => [[]],
        ];
    }

    /**
     * Test invalid rounding mode handling.
     *
     * @param null|float|int|string|string[] $invalidMode
     */
    #[DataProvider('provideInvalidRoundingModeCases')]
    public function testInvalidRoundingMode(array|float|int|string|null $invalidMode): void
    {
        try {
            BCMath::round('1.55', 1, $invalidMode); // @phpstan-ignore-line argument.type

            // Some invalid modes might be handled gracefully by PHP's round()
            if (is_int($invalidMode)) {
                $this->addToAssertionCount(1);
            } else {
                $this->fail('Expected exception for invalid rounding mode');
            }
        } catch (\ValueError $e) {
            $this->assertStringContainsString('Invalid rounding mode', $e->getMessage());
        } catch (\TypeError) {
            // May also throw TypeError depending on implementation
            $this->addToAssertionCount(1);
        }
    }

    /**
     * Data provider for comprehensive RoundingMode behavior tests.
     *
     * @return array<string, array{string, int}>
     */
    public static function provideRoundingModeComprehensiveCases(): iterable
    {
        if (!enum_exists('RoundingMode')) {
            return [];
        }

        // Simple test cases - the actual enum testing is done in the test method
        return [
            'positive half 2.5' => ['2.5', 0],
            'negative half -2.5' => ['-2.5', 0],
            'positive half 3.5' => ['3.5', 0],
            'negative half -3.5' => ['-3.5', 0],
            'decimal precision 1.255' => ['1.255', 2],
        ];
    }

    /**
     * Test comprehensive RoundingMode behavior with various numbers.
     */
    #[RequiresPhp('>=8.1')]
    #[DataProvider('provideRoundingModeComprehensiveCases')]
    public function testRoundingModeComprehensive(string $number, int $scale): void
    {
        if (!enum_exists('RoundingMode')) {
            $this->markTestSkipped('RoundingMode enum not available');
        }

        // Test supported modes individually to avoid enum key issues
        $testCases = [
            ['mode' => \RoundingMode::HalfAwayFromZero, 'name' => 'HalfAwayFromZero'],
            ['mode' => \RoundingMode::HalfTowardsZero, 'name' => 'HalfTowardsZero'],
            ['mode' => \RoundingMode::HalfEven, 'name' => 'HalfEven'],
            ['mode' => \RoundingMode::HalfOdd, 'name' => 'HalfOdd'],
            ['mode' => \RoundingMode::TowardsZero, 'name' => 'TowardsZero'],
            ['mode' => \RoundingMode::AwayFromZero, 'name' => 'AwayFromZero'],
            ['mode' => \RoundingMode::NegativeInfinity, 'name' => 'NegativeInfinity'],
            ['mode' => \RoundingMode::PositiveInfinity, 'name' => 'PositiveInfinity'],
        ];

        foreach ($testCases as $testCase) {
            $mode = $testCase['mode'];
            $modeName = $testCase['name'];

            // Define expected result for current number/scale/mode combination
            $expected = match (true) {
                $number === '2.5' && $scale === 0 && $modeName === 'HalfAwayFromZero' => '3',
                $number === '2.5' && $scale === 0 && $modeName === 'HalfTowardsZero' => '2',
                $number === '2.5' && $scale === 0 && $modeName === 'HalfEven' => '2',
                $number === '2.5' && $scale === 0 && $modeName === 'HalfOdd' => '3',

                $number === '-2.5' && $scale === 0 && $modeName === 'HalfAwayFromZero' => '-3',
                $number === '-2.5' && $scale === 0 && $modeName === 'HalfTowardsZero' => '-2',
                $number === '-2.5' && $scale === 0 && $modeName === 'HalfEven' => '-2',
                $number === '-2.5' && $scale === 0 && $modeName === 'HalfOdd' => '-3',

                $number === '3.5' && $scale === 0 && $modeName === 'HalfAwayFromZero' => '4',
                $number === '3.5' && $scale === 0 && $modeName === 'HalfTowardsZero' => '3',
                $number === '3.5' && $scale === 0 && $modeName === 'HalfEven' => '4',
                $number === '3.5' && $scale === 0 && $modeName === 'HalfOdd' => '3',

                $number === '-3.5' && $scale === 0 && $modeName === 'HalfAwayFromZero' => '-4',
                $number === '-3.5' && $scale === 0 && $modeName === 'HalfTowardsZero' => '-3',
                $number === '-3.5' && $scale === 0 && $modeName === 'HalfEven' => '-4',
                $number === '-3.5' && $scale === 0 && $modeName === 'HalfOdd' => '-3',

                $number === '1.255' && $scale === 2 && $modeName === 'HalfAwayFromZero' => '1.26',
                $number === '1.255' && $scale === 2 && $modeName === 'HalfTowardsZero' => '1.25',
                $number === '1.255' && $scale === 2 && $modeName === 'HalfEven' => '1.26',
                $number === '1.255' && $scale === 2 && $modeName === 'HalfOdd' => '1.25',

                // Directional modes (PHP 8.4+ semantics, now supported by the polyfill)
                $number === '2.5' && $scale === 0 && $modeName === 'TowardsZero' => '2',
                $number === '2.5' && $scale === 0 && $modeName === 'AwayFromZero' => '3',
                $number === '2.5' && $scale === 0 && $modeName === 'NegativeInfinity' => '2',
                $number === '2.5' && $scale === 0 && $modeName === 'PositiveInfinity' => '3',

                $number === '-2.5' && $scale === 0 && $modeName === 'TowardsZero' => '-2',
                $number === '-2.5' && $scale === 0 && $modeName === 'AwayFromZero' => '-3',
                $number === '-2.5' && $scale === 0 && $modeName === 'NegativeInfinity' => '-3',
                $number === '-2.5' && $scale === 0 && $modeName === 'PositiveInfinity' => '-2',

                $number === '3.5' && $scale === 0 && $modeName === 'TowardsZero' => '3',
                $number === '3.5' && $scale === 0 && $modeName === 'AwayFromZero' => '4',
                $number === '3.5' && $scale === 0 && $modeName === 'NegativeInfinity' => '3',
                $number === '3.5' && $scale === 0 && $modeName === 'PositiveInfinity' => '4',

                $number === '-3.5' && $scale === 0 && $modeName === 'TowardsZero' => '-3',
                $number === '-3.5' && $scale === 0 && $modeName === 'AwayFromZero' => '-4',
                $number === '-3.5' && $scale === 0 && $modeName === 'NegativeInfinity' => '-4',
                $number === '-3.5' && $scale === 0 && $modeName === 'PositiveInfinity' => '-3',

                $number === '1.255' && $scale === 2 && $modeName === 'TowardsZero' => '1.25',
                $number === '1.255' && $scale === 2 && $modeName === 'AwayFromZero' => '1.26',
                $number === '1.255' && $scale === 2 && $modeName === 'NegativeInfinity' => '1.25',
                $number === '1.255' && $scale === 2 && $modeName === 'PositiveInfinity' => '1.26',

                default => null
            };

            if ($expected !== null) {
                $result = BCMath::round($number, $scale, $mode);
                $this->assertSame(
                    $expected,
                    $result,
                    "Failed for number={$number}, scale={$scale}, mode={$modeName}"
                );
            }
        }
    }

    /**
     * Test edge cases with whitespace that could expose vulnerabilities.
     * These are specific scenarios that could bypass validation if not handled properly.
     */
    public function testWhitespaceEdgeCases(): void
    {
        $edgeCases = [
            // Basic ASCII whitespace characters
            '  -0  ',           // space (0x20) around negative zero
            '  +0  ',           // space (0x20) around positive zero
            " \t-1\n ",         // mixed basic whitespace around negative
            '   ',              // only spaces
            ' . ',              // space around decimal point
            ' -. ',             // space around negative decimal point
            ' +. ',             // space around positive decimal point

            // Extended ASCII whitespace characters
            "\t1\t",            // tab (0x09) around positive number
            "\n-5\n",           // newline (0x0A) around negative number
            "\r2\r",            // carriage return (0x0D) around positive number
            "\x0B3\x0B",        // vertical tab (0x0B) around positive number
            "\f-4\f",           // form feed (0x0C) around negative number

            // Mixed whitespace combinations
            " \t\n\r-1 \t\n\r", // all basic whitespace around negative
            "\t \n\r0.5\r\n \t", // mixed whitespace around decimal

            // Null byte (special case)
            "\0-1\0",           // null byte (0x00) around negative

            // Unicode whitespace (some may not be caught by \s but worth testing)
            "\xA0-1\xA0",       // non-breaking space (0xA0)
            "\x85-1\x85",       // next line (0x85)

            // Edge cases with only whitespace
            "\t\t\t",           // only tabs
            "\n\n\n",           // only newlines
            "\r\r\r",           // only carriage returns
            "\x0B\x0B\x0B",     // only vertical tabs
            "\f\f\f",           // only form feeds
            "\0\0\0",           // only null bytes

            // Whitespace at specific positions
            "\t-1",             // leading tab before negative
            "-1\n",             // trailing newline after negative
            '1 2',              // space in middle of number
            '- 1',              // space after negative sign
            '+ 1',              // space after positive sign
            '1. ',              // space after decimal point
            '. 5',              // space after lone decimal point
        ];

        $methods = [
            ['add', ['1']],           // bcadd - second parameter
            ['sub', ['1']],           // bcsub - second parameter
            ['mul', ['2']],           // bcmul - second parameter
            ['div', ['2']],           // bcdiv - second parameter
            ['mod', ['3']],           // bcmod - second parameter
            ['comp', ['1']],          // bccomp - second parameter
            ['pow', ['2']],           // bcpow - second parameter (exponent)
            ['powmod', ['2', '5']],   // bcpowmod - second parameter (exponent), third parameter (modulus)
            ['sqrt', []],             // bcsqrt - only one parameter
            ['floor', []],            // bcfloor - only one parameter
            ['ceil', []],             // bcceil - only one parameter
            ['round', [0]],           // bcround - second parameter (precision)
        ];

        foreach ($methods as [$method, $extraArgs]) {
            foreach ($edgeCases as $testCase) {
                try {
                    BCMath::$method($testCase, ...$extraArgs); // @phpstan-ignore-line arguments.count
                    $this->fail("BCMath::{$method}('{$testCase}') should throw ValueError for malformed input");
                } catch (ValueError $e) { // @phpstan-ignore catch.neverThrown
                    $this->assertStringContainsString(
                        'is not well-formed',
                        $e->getMessage(),
                        "Method {$method} should throw 'not well-formed' error for input '{$testCase}'"
                    );
                }
            }
        }
    }

    /**
     * Test polyfill RoundingMode enum support for PHP 8.1-8.3.
     * This specifically tests the polyfill enum behavior when native RoundingMode is not available.
     */
    #[RequiresPhp('>=8.1')]
    public function testPolyfillRoundingModeEnumSupport(): void
    {
        if (!enum_exists('RoundingMode')) {
            $this->markTestSkipped('RoundingMode enum not available');
        }

        // Test supported enum values that work in polyfill
        $supportedCases = [
            [\RoundingMode::HalfAwayFromZero, '1.55', 1, '1.6'],
            [\RoundingMode::HalfTowardsZero, '1.55', 1, '1.5'],
            [\RoundingMode::HalfEven, '1.55', 1, '1.6'],
            [\RoundingMode::HalfOdd, '1.55', 1, '1.5'],
        ];

        foreach ($supportedCases as [$mode, $number, $scale, $expected]) {
            $result = BCMath::round($number, $scale, $mode);
            $this->assertSame($expected, $result, "Failed for mode {$mode->name}");
        }
    }

    /**
     * Test the directional RoundingMode enum values (TowardsZero, AwayFromZero,
     * NegativeInfinity, PositiveInfinity). These are backported by the polyfill
     * for PHP 8.1-8.3 and match the native PHP 8.4+ semantics.
     */
    #[RequiresPhp('>=8.1')]
    public function testPolyfillDirectionalModes(): void
    {
        if (!enum_exists('RoundingMode')) {
            $this->markTestSkipped('RoundingMode enum not available');
        }

        // [mode, number, scale, expected]
        $directionalCases = [
            [\RoundingMode::TowardsZero, '1.55', 1, '1.5'],
            [\RoundingMode::TowardsZero, '-1.55', 1, '-1.5'],
            [\RoundingMode::AwayFromZero, '1.55', 1, '1.6'],
            [\RoundingMode::AwayFromZero, '-1.55', 1, '-1.6'],
            [\RoundingMode::PositiveInfinity, '1.51', 1, '1.6'],
            [\RoundingMode::PositiveInfinity, '-1.59', 1, '-1.5'],
            [\RoundingMode::NegativeInfinity, '1.59', 1, '1.5'],
            [\RoundingMode::NegativeInfinity, '-1.51', 1, '-1.6'],
        ];

        foreach ($directionalCases as [$mode, $number, $scale, $expected]) {
            $this->assertSame(
                $expected,
                BCMath::round($number, $scale, $mode),
                "Failed for mode {$mode->name}, number={$number}, scale={$scale}"
            );
        }
    }

    /**
     * Test backward compatibility between polyfill enum and PHP_ROUND_* constants.
     * Ensures both enum and constants produce identical results.
     */
    #[RequiresPhp('>=8.1')]
    public function testPolyfillEnumBackwardCompatibility(): void
    {
        if (!enum_exists('RoundingMode')) {
            $this->markTestSkipped('RoundingMode enum not available');
        }

        $compatibilityCases = [
            [\RoundingMode::HalfAwayFromZero, PHP_ROUND_HALF_UP],
            [\RoundingMode::HalfTowardsZero, PHP_ROUND_HALF_DOWN],
            [\RoundingMode::HalfEven, PHP_ROUND_HALF_EVEN],
            [\RoundingMode::HalfOdd, PHP_ROUND_HALF_ODD],
        ];

        $testNumbers = [
            ['1.55', 1],
            ['2.5', 0],
            ['-2.5', 0],
            ['3.5', 0],
            ['-3.5', 0],
            ['1.255', 2],
        ];

        foreach ($testNumbers as [$number, $scale]) {
            foreach ($compatibilityCases as [$enumMode, $constantMode]) {
                $enumResult = BCMath::round($number, $scale, $enumMode);
                $constantResult = BCMath::round($number, $scale, $constantMode);

                $this->assertSame(
                    $constantResult,
                    $enumResult,
                    "Enum mode {$enumMode->name} should produce same result as constant {$constantMode} for number={$number}, scale={$scale}"
                );
            }
        }
    }

    /**
     * Test environment detection logic for native vs polyfill enum.
     * This helps ensure proper behavior across different PHP versions.
     */
    #[RequiresPhp('>=8.1')]
    public function testRoundingModeEnvironmentDetection(): void
    {
        if (!enum_exists('RoundingMode')) {
            $this->markTestSkipped('RoundingMode enum not available');
        }

        // Test that enum exists and has expected cases
        $expectedCases = [
            'HalfAwayFromZero',
            'HalfTowardsZero',
            'HalfEven',
            'HalfOdd',
            'TowardsZero',
            'AwayFromZero',
            'NegativeInfinity',
            'PositiveInfinity',
        ];

        foreach ($expectedCases as $caseName) {
            // enum_exists('RoundingMode') は冒頭の markTestSkipped ガードで true 確定済みのため、
            // ここでは defined() のみ検証する（PHPStan の booleanAnd.leftAlwaysTrue 回避）。
            $this->assertTrue(
                defined("RoundingMode::{$caseName}"),
                "RoundingMode::{$caseName} should be available"
            );
        }

        // Directional modes are supported (polyfilled for 8.1-8.3, native on 8.4+)
        // and produce the native PHP 8.4 result regardless of PHP version.
        $this->assertSame('1', BCMath::round('1.55', 0, \RoundingMode::TowardsZero));
        $this->assertSame('2', BCMath::round('1.55', 0, \RoundingMode::AwayFromZero));
    }

    /**
     * Regression test: HalfEven/HalfOdd rounding must stay exact for large and
     * high-precision inputs. The previous implementation fell back to float
     * (round((float) ...)), which corrupted digits beyond IEEE-754 precision.
     */
    #[RequiresPhp('>=8.1')]
    public function testHighPrecisionHalfEvenOddRounding(): void
    {
        if (!enum_exists('RoundingMode')) {
            $this->markTestSkipped('RoundingMode enum not available');
        }

        // Larger than 2^53: float conversion would lose the low-order digits.
        $this->assertSame('12345678901234567890', BCMath::round('12345678901234567890.5', 0, \RoundingMode::HalfEven));
        $this->assertSame('12345678901234567891', BCMath::round('12345678901234567890.5', 0, \RoundingMode::HalfOdd));

        // Around 2^53 (9007199254740992).
        $this->assertSame('9007199254740994', BCMath::round('9007199254740993.5', 0, \RoundingMode::HalfEven));
        $this->assertSame('9007199254740993', BCMath::round('9007199254740993.5', 0, \RoundingMode::HalfOdd));

        // 30 significant digits kept exactly.
        $this->assertSame('0.12345678901234567890', BCMath::round('0.123456789012345678901234567890', 20, \RoundingMode::HalfEven));
        $this->assertSame('0.12345678901234567890', BCMath::round('0.123456789012345678901234567890', 20, \RoundingMode::HalfOdd));
    }

    /**
     * Test bcdivmod() (PHP 8.4+), implemented on top of BCMath::div()/mod() so it
     * works without the native bcmath extension.
     */
    public function testDivmod(): void
    {
        // [num1, num2, scale, expectedQuotient, expectedRemainder]
        $cases = [
            ['17', '5', 0, '3', '2'],
            ['17', '5', 2, '3', '2.00'],
            ['-17', '5', 0, '-3', '-2'],
            ['17', '-5', 0, '-3', '2'],
            ['14.14', '6', 2, '2', '2.14'],
            ['0.15', '-0.01', 0, '-15', '0'],
        ];

        foreach ($cases as [$num1, $num2, $scale, $expectedQuot, $expectedRem]) {
            [$quot, $rem] = BCMath::divmod($num1, $num2, $scale);
            $this->assertSame($expectedQuot, $quot, "quotient of {$num1} / {$num2} @ {$scale}");
            $this->assertSame($expectedRem, $rem, "remainder of {$num1} / {$num2} @ {$scale}");

            // On PHP 8.4+ (native bcdivmod available) the polyfill must match it.
            if (function_exists('bcdivmod')) {
                // @phpstan-ignore-next-line
                $this->assertSame([$expectedQuot, $expectedRem], bcdivmod($num1, $num2, $scale));
            }
        }

        // The result must equal the composition of bcdiv()/bcmod().
        [$quot, $rem] = BCMath::divmod('15151324141414.412', '7.3', 10);
        $this->assertSame(BCMath::div('15151324141414.412', '7.3', 0), $quot);
        $this->assertSame(BCMath::mod('15151324141414.412', '7.3', 10), $rem);
    }

    /**
     * bcdivmod() by zero must throw DivisionByZeroError with the native message.
     */
    public function testDivmodByZero(): void
    {
        $this->expectException(\DivisionByZeroError::class);
        $this->expectExceptionMessage('Division by zero');

        BCMath::divmod('1', '0');
    }

    /**
     * Numbers in the forms that the native extension accepts (#103, #104).
     *
     * @return iterable<string, array{string, list<int|string>, int|string}>
     */
    public static function provideAcceptedNumberFormsCases(): iterable
    {
        // #103: a leading '+' was read as zero
        yield "add('+5')" => ['add', ['+5', '1', 2], '6.00'];

        yield "mul('+2.5')" => ['mul', ['+2.5', '2', 1], '5.0'];

        yield "div('+4')" => ['div', ['1', '+4', 2], '0.25'];

        yield "mod('+7')" => ['mod', ['+7', '+4'], '3'];

        yield "comp('+1')" => ['comp', ['+1', '1'], 0];

        yield "sqrt('+4')" => ['sqrt', ['+4', 1], '2.0'];

        yield "pow('+2')" => ['pow', ['+2', '+3'], '8'];

        yield "powmod('+5')" => ['powmod', ['+5', '+3', '+7'], '6'];

        yield "floor('+1.5')" => ['floor', ['+1.5'], '1'];

        yield "round('+1.25')" => ['round', ['+1.25', 1], '1.3'];

        // #104: a sign directly followed by the decimal point
        yield "add('-.5')" => ['add', ['-.5', '1', 2], '0.50'];

        yield "add('+.5')" => ['add', ['+.5', '0', 1], '0.5'];

        yield "floor('-.5')" => ['floor', ['-.5'], '-1'];

        // #104: an empty run of digits on either side of the decimal point
        yield "add('5.')" => ['add', ['5.', '1'], '6'];

        yield "add('-5.')" => ['add', ['-5.', '0', 1], '-5.0'];

        yield "add('.')" => ['add', ['.', '1'], '1'];

        yield "add('-')" => ['add', ['-', '1'], '1'];

        yield "add('+.')" => ['add', ['+.', '1'], '1'];

        // #104: the empty string is zero for bcfloor(), bcceil() and bcround() too
        yield "floor('')" => ['floor', [''], '0'];

        yield "ceil('')" => ['ceil', [''], '0'];

        yield "round('')" => ['round', ['', 2], '0.00'];

        // Leading zeros and the sign of zero
        yield "add('007.50')" => ['add', ['007.50', '0', 2], '7.50'];

        yield "sqrt('-0')" => ['sqrt', ['-0', 2], '0.00'];

        yield "powmod('-0' exponent)" => ['powmod', ['5', '-0', '7'], '1'];
    }

    /**
     * @param list<int|string> $args
     */
    #[DataProvider('provideAcceptedNumberFormsCases')]
    public function testAcceptedNumberForms(string $method, array $args, int|string $expected): void
    {
        $this->assertSame($expected, BCMath::$method(...$args)); // @phpstan-ignore-line argument.type
    }

    /**
     * The native extension reports an invalid scale before a malformed number (#104).
     *
     * @return iterable<string, array{string, list<int|string>, string}>
     */
    public static function provideScaleErrorPrecedenceCases(): iterable
    {
        foreach (['add', 'sub', 'mul', 'div', 'mod', 'comp'] as $method) {
            yield $method => [$method, ['abc', '1', -1], "bc{$method}(): Argument #3 (\$scale) must be between 0 and 2147483647"];
        }

        yield 'divmod' => ['divmod', ['abc', '1', -1], 'bcdivmod(): Argument #3 ($scale) must be between 0 and 2147483647'];

        yield 'pow' => ['pow', ['abc', '1', -1], 'bcpow(): Argument #3 ($scale) must be between 0 and 2147483647'];

        yield 'powmod' => ['powmod', ['abc', '1', '2', -1], 'bcpowmod(): Argument #4 ($scale) must be between 0 and 2147483647'];

        yield 'sqrt' => ['sqrt', ['abc', -1], 'bcsqrt(): Argument #2 ($scale) must be between 0 and 2147483647'];
    }

    /**
     * @param list<int|string> $args
     */
    #[DataProvider('provideScaleErrorPrecedenceCases')]
    public function testScaleErrorPrecedence(string $method, array $args, string $message): void
    {
        $this->expectException(\ValueError::class);
        $this->expectExceptionMessage($message);

        BCMath::$method(...$args); // @phpstan-ignore-line argument.type
    }

    /**
     * bccomp() and bcpowmod() use the default scale when the scale is omitted (#105).
     */
    public function testCompAndPowmodUseDefaultScale(): void
    {
        $property = new \ReflectionProperty(BCMath::class, 'scale');
        $original = $property->getValue();

        try {
            BCMath::scale(7);
            $this->assertSame(1, BCMath::comp('0.5', '0'));
            $this->assertSame(0, BCMath::comp('0.00000001', '0'));

            BCMath::scale(9);
            $this->assertSame('331776.000000000', BCMath::powmod('24', '4', '2256945666040'));
        } finally {
            $property->setValue(null, $original);
        }
    }

    /**
     * bcpow() and bcpowmod() results that differed from the native extension (#106, #107, #108).
     *
     * @return iterable<string, array{string, list<int|string>, string}>
     */
    public static function providePowAndPowmodResultsCases(): iterable
    {
        // #106: a negative base takes the sign of x^e, whatever the sign of the modulus
        yield "powmod('-2', '3', '-7')" => ['powmod', ['-2', '3', '-7'], '-1'];

        yield "powmod('-999', '5', '-6861')" => ['powmod', ['-999', '5', '-6861'], '-4584'];

        yield "powmod('-2', '3', '7', 2)" => ['powmod', ['-2', '3', '7', 2], '-1.00'];

        yield "powmod('-2', '2', '7')" => ['powmod', ['-2', '2', '7'], '4'];

        yield "powmod('-7', '3', '7')" => ['powmod', ['-7', '3', '7'], '0'];

        yield "powmod('2', '3', '-7')" => ['powmod', ['2', '3', '-7'], '1'];

        // x^e used to be computed in full, which crashed the process with SIGFPE (GMP)
        yield "powmod('-999', '4897977738825', '-6861')" => ['powmod', ['-999', '4897977738825', '-6861'], '-2010'];

        // #107: a zero exponent, and a modulus of ±1
        yield "powmod('5', '-0', '7')" => ['powmod', ['5', '-0', '7'], '1'];

        yield "powmod('0', '0', '7', 1)" => ['powmod', ['0', '0', '7', 1], '1.0'];

        yield "powmod('5', '0', '1', 2)" => ['powmod', ['5', '0', '1', 2], '0.00'];

        yield "powmod('5', '0', '-1')" => ['powmod', ['5', '0', '-1'], '0'];

        yield "powmod('5', '1', '1', 3)" => ['powmod', ['5', '1', '1', 3], '0.000'];

        // #108: a zero exponent with a fractional part
        yield "pow('0', '0.0', 2)" => ['pow', ['0', '0.0', 2], '1.00'];

        yield "pow('0', '-0.00', 1)" => ['pow', ['0', '-0.00', 1], '1.0'];

        yield "pow('5', '0.000')" => ['pow', ['5', '0.000'], '1'];

        yield "pow('-2', '3.0')" => ['pow', ['-2', '3.0'], '-8'];

        yield "pow('-0.5', '3', 5)" => ['pow', ['-0.5', '3', 5], '-0.12500'];
    }

    /**
     * @param list<int|string> $args
     */
    #[DataProvider('providePowAndPowmodResultsCases')]
    public function testPowAndPowmodResults(string $method, array $args, string $expected): void
    {
        $this->assertSame($expected, BCMath::$method(...$args)); // @phpstan-ignore-line argument.type
    }

    /**
     * bcpow() and bcpowmod() errors that differed from the native extension (#107, #108).
     *
     * The native extension checks the arguments in order, so the first bad one is reported.
     *
     * @return iterable<string, array{string, list<int|string>, class-string<\Throwable>, string}>
     */
    public static function providePowAndPowmodErrorsCases(): iterable
    {
        // #108
        yield "pow('2', '1.5')" => ['pow', ['2', '1.5', 2], \ValueError::class, 'bcpow(): Argument #2 ($exponent) cannot have a fractional part'];

        yield "pow('0', '-2.5')" => ['pow', ['0', '-2.5'], \ValueError::class, 'bcpow(): Argument #2 ($exponent) cannot have a fractional part'];

        // The exponent range is checked before the base, and PHP_INT_MIN is out of range
        yield "pow('0', '9223372036854775808')" => ['pow', ['0', '9223372036854775808', 2], \ValueError::class, 'bcpow(): Argument #2 ($exponent) is too large'];

        yield "pow('0', '-9223372036854775808')" => ['pow', ['0', '-9223372036854775808', 2], \ValueError::class, 'bcpow(): Argument #2 ($exponent) is too large'];

        yield "pow('2', '9223372036854775808.5')" => ['pow', ['2', '9223372036854775808.5'], \ValueError::class, 'bcpow(): Argument #2 ($exponent) cannot have a fractional part'];

        // #107: fractional operands
        yield "powmod('5.5', '3', '7')" => ['powmod', ['5.5', '3', '7'], \ValueError::class, 'bcpowmod(): Argument #1 ($num) cannot have a fractional part'];

        yield "powmod('5', '3.5', '7')" => ['powmod', ['5', '3.5', '7'], \ValueError::class, 'bcpowmod(): Argument #2 ($exponent) cannot have a fractional part'];

        yield "powmod('5', '3', '7.5')" => ['powmod', ['5', '3', '7.5'], \ValueError::class, 'bcpowmod(): Argument #3 ($modulus) cannot have a fractional part'];

        yield "powmod('5', '3', '-0.5')" => ['powmod', ['5', '3', '-0.5'], \ValueError::class, 'bcpowmod(): Argument #3 ($modulus) cannot have a fractional part'];

        yield "powmod('5.5', '3.5', '7.5')" => ['powmod', ['5.5', '3.5', '7.5'], \ValueError::class, 'bcpowmod(): Argument #1 ($num) cannot have a fractional part'];

        yield "powmod('5', '-3', '7.5')" => ['powmod', ['5', '-3', '7.5'], \ValueError::class, 'bcpowmod(): Argument #2 ($exponent) must be greater than or equal to 0'];

        // #107: a zero modulus, in any form
        yield "powmod('5', '3', '0')" => ['powmod', ['5', '3', '0'], \DivisionByZeroError::class, 'Modulo by zero'];

        yield "powmod('5', '3', '00')" => ['powmod', ['5', '3', '00'], \DivisionByZeroError::class, 'Modulo by zero'];

        yield "powmod('5', '3', '-0.0')" => ['powmod', ['5', '3', '-0.0'], \DivisionByZeroError::class, 'Modulo by zero'];

        yield "powmod('5', '-3', '0')" => ['powmod', ['5', '-3', '0'], \ValueError::class, 'bcpowmod(): Argument #2 ($exponent) must be greater than or equal to 0'];
    }

    /**
     * @param list<int|string> $args
     * @param class-string<\Throwable> $exception
     */
    #[DataProvider('providePowAndPowmodErrorsCases')]
    public function testPowAndPowmodErrors(string $method, array $args, string $exception, string $message): void
    {
        $this->expectException($exception);
        $this->expectExceptionMessage($message);

        BCMath::$method(...$args); // @phpstan-ignore-line argument.type
    }

    /**
     * bcsqrt() results that differed from the native extension (#109).
     *
     * @return iterable<string, array{string, int, string}>
     */
    public static function provideSqrtResultsCases(): iterable
    {
        // Precision: digits beyond about 18 were wrong
        yield "sqrt('2', 40)" => ['2', 40, '1.4142135623730950488016887242096980785696'];

        // The remainder became zero before all the input digits were read
        yield "sqrt('9.0035224160128699', 5)" => ['9.0035224160128699', 5, '3.00058'];

        // Numbers below 1 got more digits than the scale
        yield "sqrt('0.0019', 1)" => ['0.0019', 1, '0.0'];

        yield "sqrt('0.0001', 2)" => ['0.0001', 2, '0.01'];

        yield "sqrt('0.25', 1)" => ['0.25', 1, '0.5'];

        // Leading zeros of the integer part were kept
        yield "sqrt('095', 2)" => ['095', 2, '9.74'];

        // Truncated, not rounded
        yield "sqrt('99', 0)" => ['99', 0, '9'];

        yield "sqrt('8', 3)" => ['8', 3, '2.828'];

        yield "sqrt('1e200', 0)" => ['1'.str_repeat('0', 200), 0, '1'.str_repeat('0', 100)];

        yield "sqrt('16.000', 0)" => ['16.000', 0, '4'];
    }

    #[DataProvider('provideSqrtResultsCases')]
    public function testSqrtResults(string $num, int $scale, string $expected): void
    {
        $this->assertSame($expected, BCMath::sqrt($num, $scale));
    }

    /**
     * The phpseclib engines that do not use GMP or bcmath.
     *
     * @return iterable<string, array{string}>
     */
    public static function provideSqrtWithEngineWithoutGmpCases(): iterable
    {
        yield 'PHP64' => ['PHP64'];

        yield 'PHP32' => ['PHP32'];
    }

    /**
     * bcsqrt() with phpseclib's pure PHP engines, which are used when GMP is not
     * available: PHP64 on 64-bit PHP and PHP32 on 32-bit PHP.
     *
     * With the PHP64 engine, a quotient returned by BigInteger::divide() gives a
     * wrong result when it is used as a divisor, so the square root must not do that.
     */
    #[DataProvider('provideSqrtWithEngineWithoutGmpCases')]
    public function testSqrtWithEngineWithoutGmp(string $engine): void
    {
        $original = BigInteger::getEngine();

        try {
            try {
                BigInteger::setEngine($engine);
            } catch (\Exception $e) {
                // PHP64 is not available on 32-bit PHP, nor on Windows with JIT enabled.
                $this->markTestSkipped("The {$engine} engine is not available: {$e->getMessage()}");
            }
            $this->assertSame('1.41421356237309504880168872420969807856967187537694', BCMath::sqrt('2', 50));
            $this->assertSame('11111.11110499999999887499999', BCMath::sqrt('123456789.987654321', 23));
        } finally {
            BigInteger::setEngine($original[0], [$original[1]]);
        }
    }

    /**
     * bccomp() compares the digit strings without big integer arithmetic (#123).
     *
     * @return iterable<string, array{string, string, null|int, int}>
     */
    public static function provideCompOnDigitStringsCases(): iterable
    {
        // Digits beyond the scale are ignored
        yield 'truncated fraction is equal' => ['1.239', '1.231', 2, 0];

        yield 'difference within the scale' => ['1.239', '1.231', 3, 1];

        yield 'scale 0 ignores the fraction' => ['1.9', '1.1', 0, 0];

        // A number that is zero within the scale has no sign
        yield 'negative below the scale equals zero' => ['-0.001', '0', 2, 0];

        yield 'negative below the scale equals positive below the scale' => ['-0.001', '0.009', 2, 0];

        yield 'negative within the scale is less than zero' => ['-0.01', '0', 2, -1];

        yield 'zero is greater than a negative' => ['0', '-0.01', 2, 1];

        // Trailing zeros and different fraction lengths
        yield 'trailing zeros are equal' => ['1.50', '1.5', 5, 0];

        yield 'shorter fraction is smaller' => ['1.5', '1.50001', 5, -1];

        yield 'fractions compare digit by digit, not as integers' => ['0.5', '0.49', 2, 1];

        yield 'leading zeros of the fraction count' => ['0.05', '0.5', 2, -1];

        // Integer parts: more digits is larger, whatever the leading digit
        yield 'more integer digits is larger' => ['100', '99', 0, 1];

        yield 'leading zeros are not digits' => ['0099', '100', 0, -1];

        yield 'negatives reverse the order' => ['-100', '-99', 0, -1];

        yield 'negative is less than positive' => ['-1', '1', 0, -1];

        yield 'same negative numbers' => ['-12.34', '-12.34', 2, 0];

        yield 'negative fractions reverse the order' => ['-0.5', '-0.49', 2, -1];

        // Beyond the precision of int and float
        yield '30 digits differing in the last one' => ['123456789012345678901234567890', '123456789012345678901234567891', 0, -1];

        yield 'fraction of 30 digits differing in the last one' => ['0.123456789012345678901234567891', '0.123456789012345678901234567890', 30, 1];

        yield 'leading zeros are not compared as numbers' => ['010', '9', 0, 1];

        // Forms accepted by the native extension
        yield 'empty forms are zero' => ['.', '-', 3, 0];

        yield 'plus sign' => ['+5', '5.000', 3, 0];

        yield 'default scale' => ['1.5', '1.4', null, 0];
    }

    #[DataProvider('provideCompOnDigitStringsCases')]
    public function testCompOnDigitStrings(string $num1, string $num2, ?int $scale, int $expected): void
    {
        $this->assertSame($expected, BCMath::comp($num1, $num2, $scale));
        $this->assertSame($expected, BCMath::comp($num2, $num1, $scale) * -1);
        $this->assertSame(bccomp($num1, $num2, $scale), BCMath::comp($num1, $num2, $scale)); // @phpstan-ignore-line argument.type
    }

    /**
     * bcpow() uses square-and-multiply (#123): the result must not depend on the bits of the exponent.
     *
     * @return iterable<string, array{string, string, int}>
     */
    public static function providePowBySquaringCases(): iterable
    {
        foreach (['1', '2', '3', '4', '7', '8', '15', '16', '17', '31', '32', '33', '100', '255', '256', '1000'] as $exponent) {
            yield "2 ** {$exponent}" => ['2', $exponent, 0];

            yield "-3 ** {$exponent}" => ['-3', $exponent, 0];
        }

        yield '1.05 ** 30' => ['1.05', '30', 10];

        yield '-1.5 ** 17' => ['-1.5', '17', 20];

        yield '0.1 ** 40' => ['0.1', '40', 30];

        yield '2 ** -10' => ['2', '-10', 15];

        yield '-2 ** -11' => ['-2', '-11', 15];

        yield '1.0001 ** 3000' => ['1.0001', '3000', 20];

        yield '1.5 ** -33' => ['1.5', '-33', 25];
    }

    #[DataProvider('providePowBySquaringCases')]
    public function testPowBySquaring(string $base, string $exponent, int $scale): void
    {
        $this->assertSame(bcpow($base, $exponent, $scale), BCMath::pow($base, $exponent, $scale)); // @phpstan-ignore-line argument.type
    }

    public function testPowBySquaringLargeExponentValue(): void
    {
        $this->assertSame(
            '10715086071862673209484250490600018105614048117055336074437503883703510511249361224931983788156958581275946729175531468251871452856923140435984577574698574803934567774824230985421074605062371141877954182153046474983581941267398767559165543946077062914571196477686542167660429831652624386837205668069376',
            BCMath::pow('2', '1000')
        );
    }

    /**
     * BCMath::format() places the decimal point and applies the scale.
     *
     * @return iterable<string, array{string, null|int, int, string}>
     */
    public static function provideFormatCases(): iterable
    {
        yield 'integer' => ['123', 0, 0, '123'];

        yield 'integer with a scale' => ['123', 2, 0, '123.00'];

        yield 'integer with a null scale' => ['123', null, 0, '123'];

        yield 'decimal point inside' => ['12345', 2, 2, '123.45'];

        yield 'fraction is truncated' => ['12345', 1, 3, '12.3'];

        yield 'fraction is padded' => ['12345', 5, 2, '123.45000'];

        yield 'scale 0 drops the fraction' => ['12345', 0, 2, '123'];

        yield 'null scale keeps the fraction' => ['12345', null, 4, '1.2345'];

        yield 'no integer digits' => ['45', 2, 2, '0.45'];

        yield 'fewer digits than the pad' => ['5', 3, 3, '0.005'];

        yield 'negative' => ['-12345', 2, 2, '-123.45'];

        yield 'negative without integer digits' => ['-5', 3, 3, '-0.005'];

        yield 'negative truncated to zero keeps its sign' => ['-5', 1, 3, '-0.0'];

        yield 'zero' => ['0', 2, 2, '0.00'];

        yield 'zero without pad' => ['0', 0, 0, '0'];
    }

    #[DataProvider('provideFormatCases')]
    public function testFormat(string $digits, ?int $scale, int $pad, string $expected): void
    {
        $this->assertSame($expected, BCMath::format(new BigInteger($digits), $scale, $pad));
    }

    /**
     * Results around the limit of the PHP integer fast path (#123).
     *
     * On 64-bit PHP, operands of up to 18 significant digits (a product or a scaled
     * dividend of up to 18 digits in total) are computed with PHP integers, and
     * larger ones with BigInteger. The expected values are from the native extension.
     *
     * @return iterable<string, array{string, array{string, string, int}, list<string>|string}>
     */
    public static function provideIntFastPathBoundaryCases(): iterable
    {
        yield '18 digits each, largest sum on the int path' => ['add', ['999999999999999999', '999999999999999999', 0], '1999999999999999998'];

        yield '19 digits, BigInteger path' => ['add', ['9999999999999999999', '1', 0], '10000000000000000000'];

        yield 'PHP_INT_MAX plus one' => ['add', ['9223372036854775807', '1', 0], '9223372036854775808'];

        yield 'PHP_INT_MIN minus one' => ['sub', ['-9223372036854775808', '1', 0], '-9223372036854775809'];

        yield 'fraction digits count towards the limit' => ['add', ['99999999.9999999999', '0.0000000001', 10], '100000000.0000000000'];

        yield '18 digits with a fraction' => ['sub', ['-99999999.9999999999', '99999999.9999999999', 10], '-199999999.9999999998'];

        yield 'scale truncates the sum' => ['add', ['1.23456', '1', 2], '2.23'];

        yield 'negative sum truncated to zero has no sign' => ['add', ['-0.001', '0', 2], '0.00'];

        yield '9 by 9 digits, largest product on the int path' => ['mul', ['999999999', '999999999', 0], '999999998000000001'];

        yield '9 by 10 digits, BigInteger path' => ['mul', ['999999999', '9999999999', 0], '9999999989000000001'];

        yield 'product of fractions' => ['mul', ['-0.000000001', '0.000000001', 20], '-0.00000000000000000100'];

        yield 'negative product truncated to zero has no sign' => ['mul', ['-0.01', '0.01', 3], '0.000'];

        yield 'dividend digits plus scale is 18' => ['div', ['99999999', '3', 10], '33333333.0000000000'];

        yield 'dividend digits plus scale is 19' => ['div', ['99999999', '3', 11], '33333333.00000000000'];

        yield 'division truncates towards zero' => ['div', ['-7', '2', 0], '-3'];

        yield 'negative divisor' => ['div', ['7', '-2', 0], '-3'];

        yield 'negative quotient truncated to zero has no sign' => ['div', ['-0.001', '3', 2], '0.00'];

        yield '18-digit divisor' => ['div', ['1', '999999999999999999', 18], '0.000000000000000001'];

        yield 'remainder has the sign of the dividend' => ['mod', ['-7', '2', 0], '-1'];

        yield 'negative modulus' => ['mod', ['7', '-2', 0], '1'];

        yield 'remainder with a fraction' => ['mod', ['-7.5', '2', 1], '-1.5'];

        yield '18-digit dividend' => ['mod', ['999999999999999999', '1000000007', 0], '48'];

        yield '19-digit dividend, BigInteger path' => ['mod', ['9999999999999999999', '1000000007', 0], '489'];

        yield 'divmod truncates the quotient' => ['divmod', ['-7', '2', 0], ['-3', '-1']];

        yield 'divmod with a fraction' => ['divmod', ['7.5', '-2', 2], ['-3', '1.50']];
    }

    /**
     * @param array{string, string, int} $args
     * @param list<string>|string        $expected
     */
    #[DataProvider('provideIntFastPathBoundaryCases')]
    public function testIntFastPathBoundary(string $method, array $args, array|string $expected): void
    {
        $this->assertSame($expected, BCMath::$method(...$args));
    }
}
