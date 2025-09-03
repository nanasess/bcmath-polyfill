<?php

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Group('without-bcmath')]
#[CoversNothing]
class BCMathWithoutExtensionTest extends TestCase
{
    protected function setUp(): void
    {
        if (extension_loaded('bcmath')) {
            $this->markTestSkipped('BCMath extension is loaded');
        }
    }

    public function testBcaddWithoutExtension(): void
    {
        $this->assertSame('3.14', bcadd('1.1', '2.04', 2));
        $this->assertSame('100', bcadd('99', '1'));
        $this->assertSame('18.98', bcadd('9.99', '8.99', 2));
        $this->assertSame('2.99', bcadd('9.99', '-7', 2));
        $this->assertSame('-6.99', bcadd('-9.99', '3', 2));
        $this->assertSame('34', bcadd('0', '34'));
        $this->assertSame('0.30', bcadd('0.15', '0.15', 2));
        $this->assertSame('0.1', bcadd('0.15', '-0.1', 1));
        $this->assertSame('0', bcadd('-0.0000005', '0', 3));
        $this->assertSame('0', bcadd('-0', '0'));
    }

    public function testBcsubWithoutExtension(): void
    {
        $this->assertSame('-0.94', bcsub('1.1', '2.04', 2));
        $this->assertSame('98', bcsub('99', '1'));
        $this->assertSame('1.00', bcsub('9.99', '8.99', 2));
        $this->assertSame('16.99', bcsub('9.99', '-7', 2));
        $this->assertSame('-12.99', bcsub('-9.99', '3', 2));
        $this->assertSame('-34', bcsub('0', '34'));
        $this->assertSame('0.00', bcsub('0.15', '0.15', 2));
        $this->assertSame('0.3', bcsub('0.15', '-0.1', 1));
        $this->assertSame('0', bcsub('-0.0000005', '0', 3));
        $this->assertSame('0', bcsub('-0', '0'));
    }

    public function testBcmulWithoutExtension(): void
    {
        $this->assertSame('2.244', bcmul('1.1', '2.04', 3));
        $this->assertSame('99', bcmul('99', '1'));
        $this->assertSame('89.8001', bcmul('9.99', '8.99', 4));
        $this->assertSame('-69.93', bcmul('9.99', '-7', 2));
        $this->assertSame('-29.97', bcmul('-9.99', '3', 2));
        $this->assertSame('0', bcmul('0', '34'));
        $this->assertSame('0.02', bcmul('0.15', '0.15', 2));
        $this->assertSame('-0.015', bcmul('0.15', '-0.1', 3));
    }

    public function testBcdivWithoutExtension(): void
    {
        $this->assertSame('0.53', bcdiv('1.1', '2.04', 2));
        $this->assertSame('99', bcdiv('99', '1'));
        $this->assertSame('1.1112', bcdiv('9.99', '8.99', 4));
        $this->assertSame('-1.42', bcdiv('9.99', '-7', 2));
        $this->assertSame('-3.33', bcdiv('-9.99', '3', 2));
        $this->assertSame('0', bcdiv('0', '34'));
        $this->assertSame('1.00', bcdiv('0.15', '0.15', 2));
        $this->assertSame('-1.500', bcdiv('0.15', '-0.1', 3));
    }

    public function testBcdivByZeroError(): void
    {
        $this->expectException(\DivisionByZeroError::class);
        bcdiv('1', '0');
    }

    public function testBcmodWithoutExtension(): void
    {
        $this->assertSame('1.1', bcmod('1.1', '2.04', 2));
        $this->assertSame('0', bcmod('99', '1'));
        $this->assertSame('1.00', bcmod('9.99', '8.99', 2));
        $this->assertSame('2.99', bcmod('9.99', '-7', 2));
        $this->assertSame('-0.99', bcmod('-9.99', '3', 2));
        $this->assertSame('0', bcmod('0', '34'));
        $this->assertSame('0.00', bcmod('0.15', '0.15', 2));
    }

    public function testBcmodByZeroError(): void
    {
        $this->expectException(\DivisionByZeroError::class);
        bcmod('1', '0');
    }

    public function testBcpowWithoutExtension(): void
    {
        $this->assertSame('387420489', bcpow('9', '9'));
        $this->assertSame('-387420489', bcpow('-9', '9'));
        $this->assertSame('995', bcpow('9.99', '2', 0));
        $this->assertSame('99.8001', bcpow('9.99', '2', 4));
        $this->assertSame('0.0000010', bcpow('9.99', '-7', 7));
        $this->assertSame('0', bcpow('0', '34'));
        $this->assertSame('0.0000000000000000001', bcpow('0.15', '15', 19));
        $this->assertSame('6.6666666667', bcpow('0.15', '-1', 10));
        $this->assertSame('1', bcpow('5', '0', 0));
        $this->assertSame('1.0000', bcpow('5', '0', 4));
    }

    public function testBcsqrtWithoutExtension(): void
    {
        $this->assertSame('12.3407', bcsqrt('152.2756', 4));
        $this->assertSame('200', bcsqrt('40000'));
        $this->assertSame('1.4142', bcsqrt('2', 4));
        $this->assertSame('3.1623', bcsqrt('10', 4));
        $this->assertSame('1', bcsqrt('1'));
        $this->assertSame('0', bcsqrt('0'));
    }

    public function testBcscaleWithoutExtension(): void
    {
        // Save original scale
        $originalScale = bcscale();

        // Test setting scale
        bcscale(4);
        $this->assertSame(4, bcscale());

        bcscale(2);
        $this->assertSame(2, bcscale());

        bcscale(0);
        $this->assertSame(0, bcscale());

        // Restore original scale
        bcscale($originalScale);
    }

    public function testBccompWithoutExtension(): void
    {
        $this->assertSame(0, bccomp('1.1', '1.1'));
        $this->assertSame(-1, bccomp('1.1', '2.04'));
        $this->assertSame(1, bccomp('2.04', '1.1'));
        $this->assertSame(0, bccomp('9.99', '9.99', 2));
        $this->assertSame(-1, bccomp('9.99', '9.991', 3));
        $this->assertSame(1, bccomp('9.991', '9.99', 3));
        $this->assertSame(1, bccomp('9.99', '-7'));
        $this->assertSame(-1, bccomp('-9.99', '3'));
        $this->assertSame(-1, bccomp('0', '34'));
        $this->assertSame(0, bccomp('0', '0'));
    }

    public function testBcfloorWithoutExtension(): void
    {
        $this->assertSame('3', bcfloor('3.14'));
        $this->assertSame('-4', bcfloor('-3.14'));
        $this->assertSame('9', bcfloor('9.999'));
        $this->assertSame('-10', bcfloor('-9.999'));
        $this->assertSame('5', bcfloor('5'));
        $this->assertSame('-5', bcfloor('-5'));
        $this->assertSame('0', bcfloor('0'));
        $this->assertSame('1', bcfloor('1.95583', 0));
        $this->assertSame('1.95', bcfloor('1.95583', 2));
        $this->assertSame('-2.0000', bcfloor('-1.95583', 4));
    }

    public function testBcceilWithoutExtension(): void
    {
        $this->assertSame('4', bcceil('3.14'));
        $this->assertSame('-3', bcceil('-3.14'));
        $this->assertSame('10', bcceil('9.999'));
        $this->assertSame('-9', bcceil('-9.999'));
        $this->assertSame('5', bcceil('5'));
        $this->assertSame('-5', bcceil('-5'));
        $this->assertSame('0', bcceil('0'));
        $this->assertSame('2', bcceil('1.95583', 0));
        $this->assertSame('2.00', bcceil('1.95583', 2));
        $this->assertSame('-1.0000', bcceil('-1.95583', 4));
    }

    public function testBcroundWithoutExtension(): void
    {
        // Test basic rounding
        $this->assertSame('3', bcround('3.4'));
        $this->assertSame('4', bcround('3.5'));
        $this->assertSame('4', bcround('3.6'));
        $this->assertSame('-3', bcround('-3.4'));
        $this->assertSame('-4', bcround('-3.5'));
        $this->assertSame('-4', bcround('-3.6'));

        // Test with scale
        $this->assertSame('1.96', bcround('1.95583', 2));
        $this->assertSame('1.956', bcround('1.95583', 3));
        $this->assertSame('1.2', bcround('1.2345', 1));

        // Test different rounding modes
        $this->assertSame('1.6', bcround('1.55', 1, PHP_ROUND_HALF_UP));
        $this->assertSame('1.5', bcround('1.55', 1, PHP_ROUND_HALF_DOWN));
        $this->assertSame('1.6', bcround('1.55', 1, PHP_ROUND_HALF_EVEN));
        $this->assertSame('1.5', bcround('1.55', 1, PHP_ROUND_HALF_ODD));

        // Test negative scale
        $this->assertSame('140', bcround('135', -1));
        $this->assertSame('100', bcround('135', -2));
        $this->assertSame('1200', bcround('1234.5678', -2));
    }

    public function testPowmodWithoutExtension(): void
    {
        $this->assertSame('9', bcpowmod('9', '9', '17'));
        $this->assertSame('999', bcpowmod('999', '999', '111', 0));
        $this->assertSame('8', bcpowmod('-9', '1024', '123'));
        $this->assertSame('1', bcpowmod('3', '0', '13'));
    }

    public function testComplexCalculations(): void
    {
        // Test chained operations
        $result = bcadd(bcmul('5', '3', 2), bcdiv('10', '4', 2), 2);
        $this->assertSame('17.50', $result);

        // Test with different scales
        $result = bcadd('1.234567890123456789', '2.345678901234567890', 15);
        $this->assertSame('3.579246791358024679', $result);

        // Test large numbers
        $result = bcadd('999999999999999999999999999999', '1');
        $this->assertSame('1000000000000000000000000000000', $result);

        // Test precision preservation
        $result = bcdiv('1', '3', 20);
        $this->assertSame('0.33333333333333333333', $result);
    }

    public function testScaleDefault(): void
    {
        // Test that default scale is respected
        bcscale(3);
        $this->assertSame('3.000', bcadd('1', '2'));
        $this->assertSame('-1.000', bcsub('1', '2'));
        $this->assertSame('2.000', bcmul('1', '2'));
        $this->assertSame('0.500', bcdiv('1', '2'));

        // Reset scale
        bcscale(0);
    }

    public function testEdgeCases(): void
    {
        // Test zero operations
        $this->assertSame('0', bcadd('0', '0'));
        $this->assertSame('0', bcsub('0', '0'));
        $this->assertSame('0', bcmul('0', '100'));
        $this->assertSame('0', bccomp('0', '0'));

        // Test negative zero handling
        $this->assertSame('0', bcadd('-0', '0'));
        $this->assertSame('0', bcsub('-0', '0'));

        // Test very small numbers
        $this->assertSame('0.000000000000000001', bcadd('0.000000000000000001', '0', 18));
        $this->assertSame('0.000000000000000002', bcadd('0.000000000000000001', '0.000000000000000001', 18));
    }
}
