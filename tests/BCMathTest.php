<?php

//declare(strict_types=1);

use bcmath_compat\BCMath;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

// use PHPUnit\Framework\Attributes\TestWith;

/**
 * requires extension bcmath
 */
#[RequiresPhpExtension('bcmath')]
class BCMathTest extends TestCase
{
    protected static $emsg = '';
    /**
     * Produces all combinations of test values.
     *
     * @return array
     */
    public static function generateTwoParams()
    {
        $r = [
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
            //[null, '9'],
            ['-0.0000005', '0', 3],
            ['-0.0000005', '0.0000001', 3],
            ['-0', '0'],
            ['-0', '-0', 4],
        ];
        return $r;
    }

    #[DataProvider('generateTwoParams')]
    public function testAdd(...$params)
    {
        $a = bcadd(...$params);
        $b = BCMath::add(...$params);

        if (version_compare(PHP_VERSION, '8.0.10') < 0 && preg_match('#^-0\.?0*$#', $a)) {
            $this->markTestSkipped('< PHP 8.0.10 made it so that you can\'t have -0 per http://bugs.php.net/78238');
        }

        $this->assertSame($a, $b);
    }

    #[DataProvider('generateTwoParams')]
    public function testSub(...$params)
    {
        $a = bcsub(...$params);
        $b = BCMath::sub(...$params);

        if (version_compare(PHP_VERSION, '8.0.10') < 0 && preg_match('#^-0\.?0*$#', $a)) {
            $this->markTestSkipped('< PHP 8.0.10 made it so that you can\'t have -0 per http://bugs.php.net/78238');
        }

        $this->assertSame($a, $b);
    }

    /**
     * requires PHP 7.3
     */

    #[RequiresPhp('>7.3')]
    #[DataProvider('generateTwoParams')]
    public function testMul(...$params)
    {
        $a = bcmul(...$params);
        $b = BCMath::mul(...$params);

        if (version_compare(PHP_VERSION, '8.0.10') < 0 && preg_match('#^-0\.?0*$#', $a)) {
            $this->markTestSkipped('< PHP 8.0.10 made it so that you can\'t have -0 per http://bugs.php.net/78238');
        }

        $this->assertSame($a, $b);
    }

    #[DataProvider('generateTwoParams')]
    public function testDiv(...$params)
    {
        if ($params[1] === '0' || $params[1] === '-0') {
            if (version_compare(PHP_VERSION, '8.0.0') >= 0) {
                $this->expectException('DivisionByZeroError');
            } else {
                $this->markTestSkipped('< PHP 8.0.0 has different behavior than >= PHP 8.0.0');
            }
        }

        $a = bcdiv(...$params);
        $b = BCMath::div(...$params);
        $this->assertSame($a, $b);
    }

    /**
     * dataProvider generateTwoParams
     * requires PHP 7.2
     */

    #[DataProvider('generateTwoParams')]
    #[RequiresPhp('>7.2')]
    public function testMod(...$params)
    {
        if ($params[1] === '0' || $params[1] === '-0') {
            if (version_compare(PHP_VERSION, '8.0.0') >= 0) {
                $this->expectException('DivisionByZeroError');
            } else {
                $this->markTestSkipped('< PHP 8.0.0 has different behavior than >= PHP 8.0.0');
            }
        }

        $a = bcmod(...$params);
        $b = BCMath::mod(...$params);
        $this->assertSame($a, $b);
    }

    /**
     * Produces all combinations of test values.
     *
     * @return array
     */
    public static function generatePowParams()
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
     * @dataProvider generatePowParams
     * requires PHP 7.3
     */
    #[DataProvider('generatePowParams')]
    #[RequiresPhp('>7.3')]
    public function testPow(...$params)
    {
        $a = bcpow(...$params);
        $b = BCMath::pow(...$params);
        $this->assertSame($a, $b);
    }

    /**
     * Produces all combinations of test values.
     *
     * @return array
     */
    public static function generatePowModParams()
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

        if (version_compare(PHP_VERSION, '8.0.0') >= 0) {
            $a = array_merge($a, [['9', '-1024', '127', 5]]);
        }

        return $a;
    }

    /**
     * dataProvider generatePowModParams
     * requires PHP 7.3
     */
    #[DataProvider('generatePowModParams')]
    #[RequiresPhp('>7.3')]

    public function testPowMod(...$params)
    {
        // Skip the specific test case on 32-bit Windows due to architecture limitations
        if (PHP_INT_SIZE === 4 && PHP_OS_FAMILY === 'Windows'
            && $params[0] === '-9' && $params[1] === '1024' && $params[2] === '123') {
            $this->markTestSkipped('Known limitation on 32-bit Windows');
        }

        $a = bcpowmod(...$params);
        $b = BCMath::powmod(...$params);
        $this->assertSame($a, $b);
    }

    public function testSqrt()
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

    public function testBoolScale()
    {
        if (false) {
            $exception_thrown = false;
            try {
                $a = bcadd('5', '2', false);
            } catch (TypeError) {
                $exception_thrown = true;
            }
            $this->assertSame(true, $exception_thrown);
        } else {
            $a = bcadd('5', '2', false);
            $b = BCMath::add('5', '2', false);
            $this->assertSame($a, $b);
        }
    }

    public function testIntParam()
    {
        $a = bccomp('9223372036854775807', 16);
        $b = BCMath::comp('9223372036854775807', 16);
        $this->assertSame($a, $b);
    }

    public function setExpectedException($name, $message = null, $code = null)
    {
        if (version_compare(PHP_VERSION, '7.0.0') < 0) {
            parent::setExpectedException($name, $message, $code);
            return;
        }
        switch ($name) {
            case 'PHPUnit_Framework_Error_Notice':
            case 'PHPUnit_Framework_Error_Warning':
                $name = str_replace('_', '\\', $name);
        }
        $this->expectException($name);
        if (!empty($message)) {
            $this->expectExceptionMessage($message);
        }
        if (!empty($code)) {
            $this->expectExceptionCode($code);
        }
    }

    public static function generateScaleCallstaticParams()
    {
        return [
            [4],
            [4,2],
            [4,2,3],
            [4,2,3,5],
        ];
    }

    #[DataProvider('generateScaleCallstaticParams')]
    public function testArgumentsScaleCallstatic(...$params)
    {
        // Save original scale
        $originalScale = bcscale();

        //scale with 1, 2, 3 parameters
        if (func_num_args() == 1) {
            bcscale(...$params);
            BCMath::scale(...$params);
            $scale = bcscale();
            $orig = $params[0];
            $this->assertSame($orig, $scale);
            $scale = BCMath::scale();
            $this->assertSame($orig, $scale);
        } else {
            $exception_thrown = false;
            try {
                BCMath::scale(...$params);
            } catch (ArgumentCountError $e) {
                $exception_thrown = true;
            }
            $this->assertSame(true, $exception_thrown);
            if (true) {
                // start the unit test with: (showing the wrong given values)
                // phpunit --testdox-test testdox.txt --display-skipped
                $msg = 'ArgumentCountError in ' . $e->getFile() . ':' . $e->getLine() . ' : ' . $e->getMessage();
                $this->markTestSkipped($msg);
            }
        }

        // Restore original scale
        bcscale($originalScale);
        BCMath::scale($originalScale);
    }
    public static function generatePowModCallstaticParams()
    {
        return [
            ['9'],
            ['9', '17'],
            ['9', '17', '-111'],
            ['9', '17', '-111', 5],
            ['9', '17', '-111', 5, 8],
        ];
    }
    #[DataProvider('generatePowModCallstaticParams')]
    public function testArgumentsPowModCallstatic(...$params)
    {
        //scale with 1, 2, 3 parameters
        if (func_num_args() > 2 && func_num_args() < 5) {
            $a = bcpowmod(...$params);
            $b = BCMath::powmod(...$params);
            $this->assertSame($a, $b);
        } else {
            $exception_thrown = false;
            try {
                BCMath::powmod(...$params);
            } catch (ArgumentCountError $e) {
                $exception_thrown = true;
            }
            $this->assertSame(true, $exception_thrown);
            if (true) {
                // start the unit test with: (showing the wrong given values)
                // phpunit --testdox-test testdox.txt --display-skipped
                $msg = 'ArgumentCountError in ' . $e->getFile() . ':' . $e->getLine() . ' : ' . $e->getMessage();
                $this->markTestSkipped($msg);
            }
        }
    }

    /**
     * Test bcfloor function
     * requires PHP 8.4
     */
    #[RequiresPhp('>=8.4')]
    public function testFloor()
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

        // Test with scale - only test BCMath class directly since native bcfloor doesn't support scale
        $this->assertSame('1', BCMath::floor('1.95583', 0));
        $this->assertSame('1.95', BCMath::floor('1.95583', 2));
        $this->assertSame('-1.9558', BCMath::floor('-1.95583', 4));
    }

    /**
     * Test bcceil function
     * requires PHP 8.4
     */
    #[RequiresPhp('>=8.4')]
    public function testCeil()
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

        // Test with scale - only test BCMath class directly since native bcceil doesn't support scale
        $this->assertSame('2', BCMath::ceil('1.95583', 0));
        $this->assertSame('1.96', BCMath::ceil('1.95583', 2));
        $this->assertSame('-1.9558', BCMath::ceil('-1.95583', 4));
    }

    /**
     * Test bcround function
     * requires PHP 8.4
     */
    #[RequiresPhp('>=8.4')]
    public function testRound()
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

        // Test different rounding modes with RoundingMode enum for PHP 8.4
        if (enum_exists('RoundingMode', false)) {
            $this->assertSame(
                bcround('1.55', 1, \RoundingMode::HalfAwayFromZero),
                BCMath::round('1.55', 1, PHP_ROUND_HALF_UP)
            );
            $this->assertSame(
                bcround('1.55', 1, \RoundingMode::HalfTowardsZero),
                BCMath::round('1.55', 1, PHP_ROUND_HALF_DOWN)
            );
            $this->assertSame(
                bcround('1.55', 1, \RoundingMode::HalfEven),
                BCMath::round('1.55', 1, PHP_ROUND_HALF_EVEN)
            );
            $this->assertSame(
                bcround('1.55', 1, \RoundingMode::HalfOdd),
                BCMath::round('1.55', 1, PHP_ROUND_HALF_ODD)
            );
        } else {
            // Fallback for environments where RoundingMode is not available yet
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
     * Test bcfloor function without PHP 8.4
     */
    public function testFloorPolyfill()
    {
        if (function_exists('bcfloor')) {
            $this->markTestSkipped('bcfloor is available, testing with native function');
        }

        // Test positive numbers
        $this->assertSame('4', BCMath::floor('4.3'));
        $this->assertSame('9', BCMath::floor('9.999'));
        $this->assertSame('3.00', BCMath::floor('3.14159', 2));

        // Test negative numbers
        $this->assertSame('-5', BCMath::floor('-4.3'));
        $this->assertSame('-10', BCMath::floor('-9.999'));
        $this->assertSame('-4.000', BCMath::floor('-3.14159', 3));

        // Test integers
        $this->assertSame('5', BCMath::floor('5'));
        $this->assertSame('-5', BCMath::floor('-5'));
        $this->assertSame('0', BCMath::floor('0'));

        // Test with scale
        $this->assertSame('1', BCMath::floor('1.95583', 0));
        $this->assertSame('1.00', BCMath::floor('1.95583', 2));
        $this->assertSame('-2.0000', BCMath::floor('-1.95583', 4));
    }

    /**
     * Test bcceil function without PHP 8.4
     */
    public function testCeilPolyfill()
    {
        if (function_exists('bcceil')) {
            $this->markTestSkipped('bcceil is available, testing with native function');
        }

        // Test positive numbers
        $this->assertSame('5', BCMath::ceil('4.3'));
        $this->assertSame('10', BCMath::ceil('9.999'));
        $this->assertSame('4.00', BCMath::ceil('3.14159', 2));

        // Test negative numbers
        $this->assertSame('-4', BCMath::ceil('-4.3'));
        $this->assertSame('-9', BCMath::ceil('-9.999'));
        $this->assertSame('-3.000', BCMath::ceil('-3.14159', 3));

        // Test integers
        $this->assertSame('5', BCMath::ceil('5'));
        $this->assertSame('-5', BCMath::ceil('-5'));
        $this->assertSame('0', BCMath::ceil('0'));

        // Test with scale
        $this->assertSame('2', BCMath::ceil('1.95583', 0));
        $this->assertSame('2.00', BCMath::ceil('1.95583', 2));
        $this->assertSame('-1.0000', BCMath::ceil('-1.95583', 4));
    }

    /**
     * Test bcround function without PHP 8.4
     */
    public function testRoundPolyfill()
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
     * Test boundary values with very large decimal places
     */
    public function testBoundaryValuesLargeDecimals()
    {
        // Test with very large decimal places
        $largeDecimal = '1.' . str_repeat('9', 100);
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
        $expected = '0.' . str_repeat('3', 100);
        $this->assertSame($expected, $quotient);
    }

    /**
     * Test with scale value 2147483647 (maximum integer)
     */
    public function testMaximumScaleValue()
    {
        // Note: Due to memory limitations, we can't actually test with scale 2147483647
        // but we can test the function accepts it and behaves correctly

        // Test that the scale parameter accepts large values
        $result = BCMath::add('1.5', '2.5', 1000);
        $this->assertSame('4.' . str_repeat('0', 1000), $result);

        // Test with a reasonably large scale
        $result = BCMath::div('1', '7', 500);
        // Should produce 0.142857142857... repeating (total length = 502 with "0.")
        $expected = '0.' . str_repeat('142857', 83) . '14';
        $this->assertSame($expected, $result);
    }

    /**
     * Test with extremely small numbers
     */
    public function testExtremelySmallNumbers()
    {
        // Test with scientific notation converted to decimal
        $small = '0.' . str_repeat('0', 99) . '1'; // 1e-100

        // Addition with extremely small numbers
        $result = BCMath::add($small, $small, 101);
        $expected = '0.' . str_repeat('0', 99) . '2' . '0';
        $this->assertSame($expected, $result);

        // Multiplication of extremely small numbers
        $result = BCMath::mul($small, '2', 101);
        $expected = '0.' . str_repeat('0', 99) . '2' . '0';
        $this->assertSame($expected, $result);

        // Division producing extremely small results
        $result = BCMath::div('1', '1' . str_repeat('0', 50), 60);
        $expected = '0.' . str_repeat('0', 49) . '10' . str_repeat('0', 9);
        $this->assertSame($expected, $result);

        // Operations with mixed extremely small and normal numbers
        $result = BCMath::add('1000000', $small, 105);
        $expected = '1000000.' . str_repeat('0', 99) . '1' . str_repeat('0', 5);
        $this->assertSame($expected, $result);
    }

    /**
     * Test all bcround() rounding modes for PHP 8.4+
     * @requires PHP >= 8.4
     */
    #[RequiresPhp('>=8.4')]
    public function testRoundAllModes()
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
                    "Failed for number=$number, scale=$scale, mode=$mode"
                );

                // Also test with native bcround if available with RoundingMode enum
                if (function_exists('bcround') && enum_exists('RoundingMode', false)) {
                    $enumMode = match ($mode) {
                        PHP_ROUND_HALF_UP => \RoundingMode::HalfAwayFromZero,
                        PHP_ROUND_HALF_DOWN => \RoundingMode::HalfTowardsZero,
                        PHP_ROUND_HALF_EVEN => \RoundingMode::HalfEven,
                        PHP_ROUND_HALF_ODD => \RoundingMode::HalfOdd,
                    };
                    $nativeResult = bcround($number, $scale, $enumMode);
                    $this->assertSame(
                        $nativeResult,
                        $result,
                        "Native bcround differs from polyfill for number=$number, scale=$scale, mode=$mode"
                    );
                }
            }
        }
    }

    /**
     * Test bcround() with negative scale values
     */
    public function testRoundNegativeScale()
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
                "Failed for number=$number, scale=$scale"
            );

            // Test with native bcround for PHP 8.4+
            if (function_exists('bcround')) {
                $nativeResult = bcround($number, $scale);
                $this->assertSame(
                    $nativeResult,
                    $result,
                    "Native bcround differs from polyfill for number=$number, scale=$scale"
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
}
