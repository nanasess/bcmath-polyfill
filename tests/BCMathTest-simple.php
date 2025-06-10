<?php

use bcmath_compat\BCMath;

/**
 * BCMath test class for simple test runner
 * Compatible with PHP 5.4+
 */
class BCMathTestSimple extends SimpleTestCase
{
    /**
     * Produces all combinations of test values.
     *
     * @return array
     */
    public static function generateTwoParams()
    {
        $r = array(
            array('9', '9'),
            array('9.99', '9.99'),
            array('9.99', '9.99', 2),
            array('9.99', '9.00009'),
            array('9.99', '9.00009', 4),
            array('9.99', '9.00009', 6),
            array('9.99', '-7', 6),
            array('9.99', '-7.2', 6),
            array('-9.99', '-3', 4),
            array('-9.99', '3.7', 4),
            array('-9.99', '-2.4', 5),
            array('0', '34'),
            array('0.15', '0.15', 1),
            array('0.15', '-0.1', 1),
            array('12', '19', 5),
            array('19', '12', 5),
            array('190', '2', 3),
            array('2', '190', 3),
            array('9', '0'),
            array('0', '9'),
            array('-0.0000005', '0', 3),
            array('-0.0000005', '0.0000001', 3),
            array('-0', '0'),
            array('-0', '-0', 4)
        );
        return $r;
    }

    /**
     * @dataProvider generateTwoParams
     */
    public function testAdd($params)
    {
        // Check if bcmath extension is loaded
        if (!extension_loaded('bcmath')) {
            $this->markTestSkipped('bcmath extension not loaded');
            return;
        }
        
        $a = call_user_func_array('bcadd', $params);
        $b = call_user_func_array(array('bcmath_compat\BCMath', 'add'), $params);

        if (version_compare(PHP_VERSION, '8.0.10') < 0 && preg_match('#^-0\.?0*$#', $a)) {
            $this->markTestSkipped('< PHP 8.0.10 made it so that you can\'t have -0 per http://bugs.php.net/78238');
            return;
        }

        $this->assertSame($a, $b);
    }

    /**
     * @dataProvider generateTwoParams
     */
    public function testSub($params)
    {
        if (!extension_loaded('bcmath')) {
            $this->markTestSkipped('bcmath extension not loaded');
            return;
        }
        
        $a = call_user_func_array('bcsub', $params);
        $b = call_user_func_array(array('bcmath_compat\BCMath', 'sub'), $params);

        if (version_compare(PHP_VERSION, '8.0.10') < 0 && preg_match('#^-0\.?0*$#', $a)) {
            $this->markTestSkipped('< PHP 8.0.10 made it so that you can\'t have -0 per http://bugs.php.net/78238');
            return;
        }

        $this->assertSame($a, $b);
    }

    /**
     * @dataProvider generateTwoParams
     */
    public function testMul($params)
    {
        if (!extension_loaded('bcmath')) {
            $this->markTestSkipped('bcmath extension not loaded');
            return;
        }
        
        // Skip for PHP < 7.3 due to bcmath behavior differences
        if (version_compare(PHP_VERSION, '7.3.0') < 0) {
            $this->markTestSkipped('Requires PHP 7.3+');
            return;
        }
        
        $a = call_user_func_array('bcmul', $params);
        $b = call_user_func_array(array('bcmath_compat\BCMath', 'mul'), $params);

        if (version_compare(PHP_VERSION, '8.0.10') < 0 && preg_match('#^-0\.?0*$#', $a)) {
            $this->markTestSkipped('< PHP 8.0.10 made it so that you can\'t have -0 per http://bugs.php.net/78238');
            return;
        }

        $this->assertSame($a, $b);
    }

    /**
     * @dataProvider generateTwoParams
     */
    public function testDiv($params)
    {
        if (!extension_loaded('bcmath')) {
            $this->markTestSkipped('bcmath extension not loaded');
            return;
        }
        
        if ($params[1] === '0' || $params[1] === '-0') {
            if (version_compare(PHP_VERSION, '8.0.0') >= 0) {
                $this->setExpectedException('DivisionByZeroError');
                
                try {
                    $a = call_user_func_array('bcdiv', $params);
                } catch (DivisionByZeroError $e) {
                    // Expected
                }
                
                $b = call_user_func_array(array('bcmath_compat\BCMath', 'div'), $params);
                // If we get here, no exception was thrown
                throw new Exception('Expected DivisionByZeroError was not thrown');
            } else {
                $this->markTestSkipped('< PHP 8.0.0 has different behavior than >= PHP 8.0.0');
                return;
            }
        } else {
            $a = call_user_func_array('bcdiv', $params);
            $b = call_user_func_array(array('bcmath_compat\BCMath', 'div'), $params);
            $this->assertSame($a, $b);
        }
    }

    /**
     * @dataProvider generateTwoParams
     */
    public function testMod($params)
    {
        if (!extension_loaded('bcmath')) {
            $this->markTestSkipped('bcmath extension not loaded');
            return;
        }
        
        // Skip for PHP < 7.2 due to bcmath behavior differences
        if (version_compare(PHP_VERSION, '7.2.0') < 0) {
            $this->markTestSkipped('Requires PHP 7.2+');
            return;
        }
        
        if ($params[1] === '0' || $params[1] === '-0') {
            if (version_compare(PHP_VERSION, '8.0.0') >= 0) {
                $this->setExpectedException('DivisionByZeroError');
                
                try {
                    $a = call_user_func_array('bcmod', $params);
                } catch (DivisionByZeroError $e) {
                    // Expected
                }
                
                $b = call_user_func_array(array('bcmath_compat\BCMath', 'mod'), $params);
                // If we get here, no exception was thrown
                throw new Exception('Expected DivisionByZeroError was not thrown');
            } else {
                $this->markTestSkipped('< PHP 8.0.0 has different behavior than >= PHP 8.0.0');
                return;
            }
        } else {
            $a = call_user_func_array('bcmod', $params);
            $b = call_user_func_array(array('bcmath_compat\BCMath', 'mod'), $params);
            $this->assertSame($a, $b);
        }
    }

    /**
     * Produces all combinations of test values for pow.
     *
     * @return array
     */
    public static function generatePowParams()
    {
        return array(
            array('9', '9'),
            array('-9', '9'),
            array('9.99', '9'),
            array('9.99', '9', 4),
            array('9.99', '9', 6),
            array('9.99', '-7', 6),
            array('0', '34'),
            array('12', '19', 5),
            array('10', '-2', 10),
            array('-9.99', '-3', 10),
            array('0.15', '15', 10),
            array('0.15', '-1', 10),
            array('5', '0', 4)
        );
    }

    /**
     * @dataProvider generatePowParams
     */
    public function testPow($params)
    {
        if (!extension_loaded('bcmath')) {
            $this->markTestSkipped('bcmath extension not loaded');
            return;
        }
        
        // Skip for PHP < 7.3 due to bcmath behavior differences
        if (version_compare(PHP_VERSION, '7.3.0') < 0) {
            $this->markTestSkipped('Requires PHP 7.3+');
            return;
        }
        
        $a = call_user_func_array('bcpow', $params);
        $b = call_user_func_array(array('bcmath_compat\BCMath', 'pow'), $params);
        $this->assertSame($a, $b);
    }

    /**
     * Produces all combinations of test values for powmod.
     *
     * @return array
     */
    public static function generatePowModParams()
    {
        $a = array(
            array('9', '9', '17'),
            array('999', '999', '111', 5),
            array('-9', '1024', '123'),
            array('3', '1024', '-149'),
            array('2', '12', '2', 5),
            array('3', '0', '13'),
            array('-3', '0', '13', 4),
        );

        return $a;
    }

    /**
     * @dataProvider generatePowModParams
     */
    public function testPowMod($params)
    {
        if (!extension_loaded('bcmath')) {
            $this->markTestSkipped('bcmath extension not loaded');
            return;
        }
        
        // Skip for PHP < 7.3 due to bcmath behavior differences
        if (version_compare(PHP_VERSION, '7.3.0') < 0) {
            $this->markTestSkipped('Requires PHP 7.3+');
            return;
        }
        
        $a = call_user_func_array('bcpowmod', $params);
        $b = call_user_func_array(array('bcmath_compat\BCMath', 'powmod'), $params);
        $this->assertSame($a, $b);
    }


    public function testSqrt()
    {
        if (!extension_loaded('bcmath')) {
            $this->markTestSkipped('bcmath extension not loaded');
            return;
        }
        
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
        if (!extension_loaded('bcmath')) {
            $this->markTestSkipped('bcmath extension not loaded');
            return;
        }
        
        $a = bcadd('5','2', false);
        $b = BCMath::add('5', '2', false);
        $this->assertSame($a, $b);
    }

    public function testIntParam()
    {
        if (!extension_loaded('bcmath')) {
            $this->markTestSkipped('bcmath extension not loaded');
            return;
        }
        
        $a = bccomp('9223372036854775807', 16);
        $b = BCMath::comp('9223372036854775807', 16);
        $this->assertSame($a, $b);
    }

    public static function generateScaleCallstaticParams()
    {
        return array(
            array(4),
            array(4,2),
            array(4,2,3),
            array(4,2,3,5),
        );
    }

    /**
     * @dataProvider generateScaleCallstaticParams
     */
    public function test_argumentsScaleCallstatic($params)
    {
        if (!extension_loaded('bcmath')) {
            $this->markTestSkipped('bcmath extension not loaded');
            return;
        }
        
        //scale with 1, 2, 3 parameters
        if (count($params) == 1) {
            call_user_func_array('bcscale', $params);
            call_user_func_array(array('bcmath_compat\BCMath', 'scale'), $params);
            $scale = bcscale();
            $orig = $params[0];
            $this->assertSame($orig, $scale);
            $scale = BCMath::scale();
            $this->assertSame($orig, $scale);
        } else {
            $exception_thrown = false;
            try {
                call_user_func_array(array('bcmath_compat\BCMath', 'scale'), $params);
            } catch (Exception $e) {
                $exception_thrown = true;
            } catch (Error $e) {
                // For PHP 7+ ArgumentCountError
                $exception_thrown = true;
            }
            $this->assertSame(true, $exception_thrown);
        }
    }

    public static function generatePowModCallstaticParams()
    {
        return array(
            array('9'),
            array('9', '17'),
            array('9', '17', '-111'),
            array('9', '17', '-111', 5),
            array('9', '17', '-111', 5, 8),
        );
    }

    /**
     * @dataProvider generatePowModCallstaticParams
     */
    public function test_argumentsPowModCallstatic($params)
    {
        if (!extension_loaded('bcmath')) {
            $this->markTestSkipped('bcmath extension not loaded');
            return;
        }
        
        //scale with 1, 2, 3 parameters
        if (count($params) > 2 && count($params) < 5) {
            $a = call_user_func_array('bcpowmod', $params);
            $b = call_user_func_array(array('bcmath_compat\BCMath', 'powmod'), $params);
            $this->assertSame($a, $b);
        } else {
            $exception_thrown = false;
            try {
                call_user_func_array(array('bcmath_compat\BCMath', 'powmod'), $params);
            } catch (Exception $e) {
                $exception_thrown = true;
            } catch (Error $e) {
                // For PHP 7+ ArgumentCountError
                $exception_thrown = true;
            }
            $this->assertSame(true, $exception_thrown);
        }
    }
}