<?php //declare(strict_types=1);

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
    static $emsg = '';
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
            ['-0', '-0', 4]
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
                $this->setExpectedException('DivisionByZeroError');
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
                $this->setExpectedException('DivisionByZeroError');
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
            ['5', '0', 4]
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
            } catch (TypeError $e) {
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
    public function test_argumentsScaleCallstatic(...$params)
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
                $this->markTestSkipped('ArgumentCountError in ' . $e->getFile() . ':' . $e->getLine() . ' : ' . $e->getMessage());
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
    public function test_argumentsPowModCallstatic(...$params)
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
                $this->markTestSkipped('ArgumentCountError in ' . $e->getFile() . ':' . $e->getLine() . ' : ' . $e->getMessage());
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
            $this->assertSame(bcround('1.55', 1, \RoundingMode::HalfAwayFromZero), BCMath::round('1.55', 1, PHP_ROUND_HALF_UP));
            $this->assertSame(bcround('1.55', 1, \RoundingMode::HalfTowardsZero), BCMath::round('1.55', 1, PHP_ROUND_HALF_DOWN));
            $this->assertSame(bcround('1.55', 1, \RoundingMode::HalfEven), BCMath::round('1.55', 1, PHP_ROUND_HALF_EVEN));
            $this->assertSame(bcround('1.55', 1, \RoundingMode::HalfOdd), BCMath::round('1.55', 1, PHP_ROUND_HALF_ODD));
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
}
