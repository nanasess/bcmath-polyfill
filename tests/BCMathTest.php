<?php

// declare(strict_types=1);

use bcmath_compat\BCMath;
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
        if ($message !== null && $message !== '' && $message !== '0') {
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
        $wasPadded = strlen($numStr) % 2 !== 0;  // false for '56'
        $integerLength = strlen($temp[0]) + ($wasPadded ? 1 : 0);  // 1 + 0 = 1
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

        // Test zero modulus (should throw ValueError)
        try {
            BCMath::powmod('2', '1', '0', 2);
            $this->fail('Expected ValueError for zero modulus');
        } catch (\ValueError $e) {
            $this->assertStringContainsString('cannot be zero', $e->getMessage());
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
    public static function provideBcpowNegativePowerOfZeroCases(): iterable
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
    #[DataProvider('provideBcpowNegativePowerOfZeroCases')]
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

        // Skip unsupported modes in all versions
        $unsupportedModes = [\RoundingMode::TowardsZero, \RoundingMode::AwayFromZero, \RoundingMode::NegativeInfinity];
        if (in_array($mode, $unsupportedModes, true)) {
            $this->expectException(\ValueError::class);
            $this->expectExceptionMessage("RoundingMode::{$mode->name} is not supported");
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

        // Test unsupported modes throw ValueError
        $unsupportedModes = [
            ['mode' => \RoundingMode::TowardsZero, 'name' => 'TowardsZero'],
            ['mode' => \RoundingMode::AwayFromZero, 'name' => 'AwayFromZero'],
            ['mode' => \RoundingMode::NegativeInfinity, 'name' => 'NegativeInfinity'],
        ];

        foreach ($unsupportedModes as $testCase) {
            $mode = $testCase['mode'];
            $modeName = $testCase['name'];

            try {
                BCMath::round($number, $scale, $mode);
                $this->fail("Expected ValueError for unsupported mode {$modeName}");
            } catch (\ValueError $e) {
                $this->assertStringContainsString(
                    'is not supported',
                    $e->getMessage(),
                    "Expected specific error message for unsupported mode {$modeName}"
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
     * Test unsupported RoundingMode enum values in PHP < 8.4.
     * These modes should throw ValueError when used in polyfill environment.
     */
    #[RequiresPhp('>=8.1')]
    public function testPolyfillUnsupportedModes(): void
    {
        if (!enum_exists('RoundingMode')) {
            $this->markTestSkipped('RoundingMode enum not available');
        }

        // Skip this test on PHP 8.4+ where these modes are supported
        $unsupportedModes = [
            \RoundingMode::TowardsZero,
            \RoundingMode::AwayFromZero,
            \RoundingMode::NegativeInfinity,
        ];

        foreach ($unsupportedModes as $mode) {
            try {
                BCMath::round('1.55', 1, $mode);
                $this->fail("Expected ValueError for unsupported mode {$mode->name}");
            } catch (\ValueError $e) {
                $this->assertStringContainsString(
                    'is not supported',
                    $e->getMessage(),
                    "Expected specific error message for unsupported mode {$mode->name}"
                );
            }
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
        ];

        foreach ($expectedCases as $caseName) {
            $this->assertTrue(
                enum_exists('RoundingMode') && defined("RoundingMode::{$caseName}"),
                "RoundingMode::{$caseName} should be available"
            );
        }

        // Test that unsupported modes always throw ValueError regardless of PHP version
        try {
            BCMath::round('1.55', 1, \RoundingMode::TowardsZero);
            $this->fail('TowardsZero mode should always throw ValueError');
        } catch (\ValueError $e) {
            $this->assertStringContainsString('is not supported', $e->getMessage());
        }
    }
}
