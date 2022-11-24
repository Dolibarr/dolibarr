<?php

namespace MathPHP\Tests\Arithmetic;

use MathPHP\Arithmetic;
use MathPHP\Exception;

class ArithmeticTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test         copySign
     * @dataProvider dataProviderForCopySign
     * @param        float $x
     * @param        float $y
     * @param        float $x_with_y_sign
     */
    public function testCopySign(float $x, float $y, float $x_with_y_sign)
    {
        // When
        $copy_sign = Arithmetic::copySign($x, $y);

        // Then
        $this->assertSame($x_with_y_sign, $copy_sign);
    }

    /**
     * @return array [x, y, x with y sign]
     */
    public function dataProviderForCopySign(): array
    {
        return [
            [1, 1, 1],
            [1, -1, -1],
            [-1, 1, 1],
            [-1, -1, -1],
            [2, 1, 2],
            [2, -1, -2],
            [-2, 1, 2],
            [-2, -1, -2],
            [3, 0, 3],
            [3, -0, 3],
            [-3, 0, 3],
            [-3, -0, 3],
            [2.3, 1, 2.3],
            [2.3, -1, -2.3],
            [-2.3, 1, 2.3],
            [-2.3, -1, -2.3],
            [INF, 1, INF],
            [INF, -1, -INF],
            [-INF, 1, INF],
            [-INF, -1, -INF],
        ];
    }

    /**
     * @test         root returns the expected value.
     * @dataProvider dataProviderForRoot
     * @param        float $x
     * @param        int   $n
     * @param        float $expected_root
     */
    public function testRoot(float $x, int $n, float $expected_root)
    {
        // When
        $root = Arithmetic::root($x, $n);

        // Then
        $this->assertEqualsWithDelta($expected_root, $root, 0.000000001);
    }

    /**
     * @return array
     */
    public function dataProviderForRoot(): array
    {
        return [
            [1, 6, 1],
            [-1, 5, -1],
            [3125, 5, 5],
            [0, 3, 0],
            [4, 2, 2],
            [9, 2, 3],
            [16, 2, 4],
            [1, 3, 1],
            [-1, 3, -1],
            [2, 3, 1.259921049894873],
            [-2, 3, -1.259921049894873],
            [3, 3, 1.442249570307408],
            [-3, 3, -1.442249570307408],
            [8, 3, 2],
            [-8, 3, -2],
            [27, 3, 3],
            [-27, 3, -3],
            [64, 3, 4],
            [-64, 3, -4],
            [125, 3, 5],
            [-125, 3, -5],
            [245.362, 3, 6.260405067916984],
            [-245.362, 3, -6.260405067916984],
            [0.0548, 3, 0.379833722265818],
            [-0.0548, 3, -0.379833722265818],
            [81, 4, 3],
            [100, 4, 3.1622776602],
        ];
    }

    /**
     * @test         cubeRoot returns the expected value.
     * @dataProvider dataProviderForCubeRoot
     * @param        float $x
     * @param        float $expected_cube_root
     */
    public function testCubeRoot(float $x, float $expected_cube_root)
    {
        // When
        $cube_root = Arithmetic::cubeRoot($x);

        // Then
        $this->assertEqualsWithDelta($expected_cube_root, $cube_root, 0.000000001);
    }

    /**
     * @return array
     */
    public function dataProviderForCubeRoot(): array
    {
        return [
            [0, 0],
            [1, 1],
            [-1, -1],
            [2, 1.259921049894873],
            [-2, -1.259921049894873],
            [3, 1.442249570307408],
            [-3, -1.442249570307408],
            [8, 2],
            [-8, -2],
            [27, 3],
            [-27, -3],
            [64, 4],
            [-64, -4],
            [125, 5],
            [-125, -5],
            [245.362, 6.260405067916984],
            [-245.362, -6.260405067916984],
            [0.0548, 0.379833722265818],
            [-0.0548, -0.379833722265818],
        ];
    }

    /**
     * @test         digitSum returns the expected sum of digits for base 10
     * @dataProvider dataProviderForDigitSumBaseTen
     * @param        int $x
     * @param        int $expected
     */
    public function testDigitSum(int $x, int $expected)
    {
        // Given
        $base = 10;

        // When
        $digital_sum = Arithmetic::digitSum($x, $base);

        // Then
        $this->assertEquals($expected, $digital_sum);
    }

    /**
     * @return array
     */
    public function dataProviderForDigitSumBaseTen(): array
    {
        return [
            [0, 0],
            [1, 1],
            [2, 2],
            [3, 3],
            [4, 4],
            [5, 5],
            [6, 6],
            [7, 7],
            [8, 8],
            [9, 9],
            [10, 1],
            [11, 2],
            [12, 3],
            [13, 4],
            [14, 5],
            [15, 6],
            [16, 7],
            [17, 8],
            [18, 9],
            [19, 10],
            [20, 2],
            [21, 3],
            [22, 4],
            [23, 5],
            [24, 6],
            [25, 7],
            [26, 8],
            [27, 9],
            [28, 10],
            [29, 11],
            [30, 3],
            [31, 4],
            [32, 5],
            [33, 6],
            [34, 7],
            [111, 3],
            [222, 6],
            [123, 6],
            [999, 27],
            [152, 8],
            [84001, 13],
            [18, 9],
            [27, 9],
            [36, 9],
            [45, 9],
            [54, 9],
            [63, 9],
            [72, 9],
            [81, 9],
            [90, 9],
            [99, 18],
        ];
    }

    /**
     * @test         digitSum returns the expected sum of digits for base 2
     * @dataProvider dataProviderForDigitSumBaseTwo
     * @param        int $x
     * @param        int $expected
     */
    public function testDigitSumBaseTwo(int $x, int $expected)
    {
        // Given
        $base = 2;

        // When
        $digital_sum = Arithmetic::digitSum($x, $base);

        // Then
        $this->assertEquals($expected, $digital_sum);
    }

    /**
     * @return array
     */
    public function dataProviderForDigitSumBaseTwo(): array
    {
        return [
            [0b0, 0],
            [0b1, 1],
            [0b10, 1],
            [0b11, 2],
            [0b100, 1],
            [0b101, 2],
            [0b110, 2],
            [0b111, 3],
            [0b1000, 1],
            [0b1001, 2],
            [0b1010, 2],
            [0b1011, 3],
            [0b1100, 2],
            [0b1101, 3],
            [0b111, 3],
        ];
    }

    /**
     * @test         digitalRoot returns the expected root
     * @dataProvider dataProviderForDigitalRoot
     * @param        int $x
     * @param        int $expected_root
     */
    public function testDigitalRoot(int $x, int $expected_root)
    {
        // When
        $digital_root = Arithmetic::digitalRoot($x);

        // Then
        $this->assertEquals($expected_root, $digital_root);
    }

    /**
     * @return array
     */
    public function dataProviderForDigitalRoot(): array
    {
        return [
            [0, 0],
            [1, 1],
            [2, 2],
            [3, 3],
            [4, 4],
            [5, 5],
            [6, 6],
            [7, 7],
            [8, 8],
            [9, 9],
            [10, 1],
            [11, 2],
            [12, 3],
            [13, 4],
            [14, 5],
            [15, 6],
            [16, 7],
            [17, 8],
            [18, 9],
            [19, 1],
            [20, 2],
            [21, 3],
            [22, 4],
            [23, 5],
            [24, 6],
            [25, 7],
            [26, 8],
            [27, 9],
            [28, 1],
            [29, 2],
            [30, 3],
            [31, 4],
            [32, 5],
            [33, 6],
            [34, 7],
            [111, 3],
            [222, 6],
            [123, 6],
            [999, 9],
            [152, 8],
            [84001, 4],
            [65536, 7],
            [18, 9],
            [27, 9],
            [36, 9],
            [45, 9],
            [54, 9],
            [63, 9],
            [72, 9],
            [81, 9],
            [90, 9],
            [99, 9],
            [108, 9],
        ];
    }

    /**
     * @test         almostEqual
     * @dataProvider dataProviderForAlmostEqual
     * @param        float $x
     * @param        float $y
     * @param        float $ε
     * @param        bool  $expected
     */
    public function testAlmostEqual(float $x, float $y, float $ε, bool $expected)
    {
        // When
        $equal = Arithmetic::almostEqual($x, $y, $ε);

        // Then
        $this->assertSame($expected, $equal);
    }

    /**
     * @return array [x, y, ε, expected]   .00000000000035
     */
    public function dataProviderForAlmostEqual(): array
    {
        return [
            [0, 0, 0, true],
            [0, 0, 0.0, true],
            [0, 0, 0.0000000001, true],
            [0, 1, 0.0000000001, false],
            [-0, -0, 0, true],
            [-0, -0, 0.0, true],
            [-0, -0, 0.0000000001, true],
            [-0, -1, 0.0000000001, false],
            [1.2345678, 1.23456789, 0.1, true],
            [1.2345678, 1.23456789, 0.01, true],
            [1.2345678, 1.23456789, 0.001, true],
            [1.2345678, 1.23456789, 0.0001, true],
            [1.2345678, 1.23456789, 0.00001, true],
            [1.2345678, 1.23456789, 0.000001, true],
            [1.2345678, 1.23456789, 0.0000001, true],
            [1.2345678, 1.23456789, 0.00000001, false],
            [1.2345678, 1.23456789, 0.0000000001, false],
            [1.2345678, 1.23456789, 0.00000000001, false],
            [-1.2345678, -1.23456789, 0.1, true],
            [-1.2345678, -1.23456789, 0.01, true],
            [-1.2345678, -1.23456789, 0.001, true],
            [-1.2345678, -1.23456789, 0.0001, true],
            [-1.2345678, -1.23456789, 0.00001, true],
            [-1.2345678, -1.23456789, 0.000001, true],
            [-1.2345678, -1.23456789, 0.0000001, true],
            [-1.2345678, -1.23456789, 0.00000001, false],
            [-1.2345678, -1.23456789, 0.0000000001, false],
            [-1.2345678, -1.23456789, 0.00000000001, false],
            [0.00000003458, 0.0000000345599999, 0.00000001, true],
            [0.00000003458, 0.0000000345599999, 0.000000001, true],
            [0.00000003458, 0.0000000345599999, 0.0000000001, true],
            [0.00000003458, 0.0000000345599999, 0.00000000001, false],
            [0.00000003458, 0.0000000345599999, 0.000000000001, false],
            [0.00000003458, 0.0000000345599999, 0.0000000000001, false],
            [0.00000003458, 0.0000000345764999, 0.00000001, true],
            [0.00000003458, 0.0000000345764999, 0.000000001, true],
            [0.00000003458, 0.0000000345764999, 0.0000000001, true],
            [0.00000003458, 0.0000000345764999, 0.00000000001, true],
            [0.00000003458, 0.0000000345764999, 0.000000000001, false],
            [0.00000003458, 0.0000000345764999, 0.0000000000001, false],
            [-0.00000003458, -0.0000000345599999, 0.00000001, true],
            [-0.00000003458, -0.0000000345599999, 0.000000001, true],
            [-0.00000003458, -0.0000000345599999, 0.0000000001, true],
            [-0.00000003458, -0.0000000345599999, 0.00000000001, false],
            [-0.00000003458, -0.0000000345599999, 0.000000000001, false],
            [-0.00000003458, -0.0000000345599999, 0.0000000000001, false],
            [-0.00000003458, -0.0000000345764999, 0.00000001, true],
            [-0.00000003458, -0.0000000345764999, 0.000000001, true],
            [-0.00000003458, -0.0000000345764999, 0.0000000001, true],
            [-0.00000003458, -0.0000000345764999, 0.00000000001, true],
            [-0.00000003458, -0.0000000345764999, 0.000000000001, false],
            [-0.00000003458, -0.0000000345764999, 0.0000000000001, false],
            [0.00000003458, 0.00000003455, 0.00000001, true],
            [0.00000003458, 0.00000003455, 0.000000001, true],
            [0.00000003458, 0.00000003455, 0.0000000001, true],
            [0.00000003458, 0.00000003455, 0.00000000001, false],
            [0.00000003458, 0.00000003455, 0.000000000001, false],
            [0.00000003458, 0.00000003455, 0.0000000000001, false],
            [-0.00000003458, -0.00000003455, 0.00000001, true],
            [-0.00000003458, -0.00000003455, 0.000000001, true],
            [-0.00000003458, -0.00000003455, 0.0000000001, true],
            [-0.00000003458, -0.00000003455, 0.00000000001, false],
            [-0.00000003458, -0.00000003455, 0.000000000001, false],
            [-0.00000003458, -0.00000003455, 0.0000000000001, false],
            [0.0000000000000000044746732, 0.0000000000000000044746639325, 0.00000001, true],
            [0.0000000000000000044746732, 0.0000000000000000044746639325, 0.000000001, true],
            [0.0000000000000000044746732, 0.0000000000000000044746639325, 0.0000000001, true],
            [0.0000000000000000044746732, 0.0000000000000000044746639325, 0.00000000001, true],
            [0.0000000000000000044746732, 0.0000000000000000044746639325, 0.000000000001, true],
            [0.0000000000000000044746732, 0.0000000000000000044746639325, 0.0000000000001, true],
            [0.0000000000000000044746732, 0.0000000000000000044746639325, 0.00000000000001, true],
            [0.0000000000000000044746732, 0.0000000000000000044746639325, 0.000000000000001, true],
            [0.0000000000000000044746732, 0.0000000000000000044746639325, 0.0000000000000001, true],
            [0.0000000000000000044746732, 0.0000000000000000044746639325, 0.00000000000000001, true],
            [0.0000000000000000044746732, 0.0000000000000000044746639325, 0.000000000000000001, true],
            [0.0000000000000000044746732, 0.0000000000000000044746639325, 0.0000000000000000001, true],
            [0.0000000000000000044746732, 0.0000000000000000044746639325, 0.00000000000000000001, true],
            [0.0000000000000000044746732, 0.0000000000000000044746639325, 0.000000000000000000001, true],
            [0.0000000000000000044746732, 0.0000000000000000044746639325, 0.0000000000000000000001, true],
            [0.0000000000000000044746732, 0.0000000000000000044746639325, 0.00000000000000000000001, true],
            [0.0000000000000000044746732, 0.0000000000000000044746639325, 0.000000000000000000000001, false],
            [0.0000000000000000044746732, 0.0000000000000000044746639325, 0.0000000000000000000000001, false],
            [0.0000000000000000044746732, 0.0000000000000000044746639325, 0.00000000000000000000000001, false],
            [0.0000000000000000044746732, 0.0000000000000000044746639325, 0.000000000000000000000000001, false],
            [-0.0000000000000000044746732, -0.0000000000000000044746639325, 0.00000001, true],
            [-0.0000000000000000044746732, -0.0000000000000000044746639325, 0.000000001, true],
            [-0.0000000000000000044746732, -0.0000000000000000044746639325, 0.0000000001, true],
            [-0.0000000000000000044746732, -0.0000000000000000044746639325, 0.00000000001, true],
            [-0.0000000000000000044746732, -0.0000000000000000044746639325, 0.000000000001, true],
            [-0.0000000000000000044746732, -0.0000000000000000044746639325, 0.0000000000001, true],
            [-0.0000000000000000044746732, -0.0000000000000000044746639325, 0.00000000000001, true],
            [-0.0000000000000000044746732, -0.0000000000000000044746639325, 0.000000000000001, true],
            [-0.0000000000000000044746732, -0.0000000000000000044746639325, 0.0000000000000001, true],
            [-0.0000000000000000044746732, -0.0000000000000000044746639325, 0.00000000000000001, true],
            [-0.0000000000000000044746732, -0.0000000000000000044746639325, 0.000000000000000001, true],
            [-0.0000000000000000044746732, -0.0000000000000000044746639325, 0.0000000000000000001, true],
            [-0.0000000000000000044746732, -0.0000000000000000044746639325, 0.00000000000000000001, true],
            [-0.0000000000000000044746732, -0.0000000000000000044746639325, 0.000000000000000000001, true],
            [-0.0000000000000000044746732, -0.0000000000000000044746639325, 0.0000000000000000000001, true],
            [-0.0000000000000000044746732, -0.0000000000000000044746639325, 0.00000000000000000000001, true],
            [-0.0000000000000000044746732, -0.0000000000000000044746639325, 0.000000000000000000000001, false],
            [-0.0000000000000000044746732, -0.0000000000000000044746639325, 0.0000000000000000000000001, false],
            [-0.0000000000000000044746732, -0.0000000000000000044746639325, 0.00000000000000000000000001, false],
            [-0.0000000000000000044746732, -0.0000000000000000044746639325, 0.000000000000000000000000001, false],
        ];
    }

    /**
     * @test         modulo of positive dividend and divisor
     * @dataProvider dataProviderForModuloPositiveDividendAndDivisor
     * @param        int $a dividend
     * @param        int $n divisor
     * @param        int $expected
     */
    public function testModuloPositiveDividendAndDivisor(int $a, int $n, int $expected)
    {
        // When
        $modulo = Arithmetic::modulo($a, $n);

        // Then
        $this->assertEquals($expected, $modulo);
    }

    /**
     * Test data generated with R: a %% n
     * @return array (dividend, divisor, expected)
     */
    public function dataProviderForModuloPositiveDividendAndDivisor(): array
    {
        return [
            [0, 1, 0],
            [0, 2, 0],
            [1, 1, 0],
            [1, 2, 1],
            [2, 1, 0],
            [2, 2, 0],
            [2, 3, 2],
            [3, 2, 1],
            [5, 3, 2],
            [10, 1, 0],
            [10, 2, 0],
            [10, 3, 1],
            [10, 4, 2],
            [10, 5, 0],
            [10, 6, 4],
            [10, 7, 3],
            [10, 8, 2],
            [10, 9, 1],
            [10, 10, 0],
            [12, 5, 2],
            [18, 3, 0],
            [100, 3, 1],
            [100, 7, 2],
            [340, 60, 40],
        ];
    }

    /**
     * @test Modulo is the same as the built-in remainder (%) operator when the dividend and divisor are positive
     */
    public function testModuloPositiveDividendAndDivisorIsSameAsBuiltInRemainderOperator()
    {
        // Given
        foreach (\range(0, 20) as $a) {
            foreach (\range(1, 20) as $n) {
                // When
                $remainder = $a % $n;
                $modulo    = Arithmetic::modulo($a, $n);

                // Then
                $this->assertEquals($remainder, $modulo);
            }
        }
    }

    /**
     * @test         modulo of negative dividend
     * @dataProvider dataProviderForModuloNegativeDividend
     * @param        int $a dividend
     * @param        int $n divisor
     * @param        int $expected
     */
    public function testModuloNegativeDividend(int $a, int $n, int $expected)
    {
        // When
        $modulo = Arithmetic::modulo($a, $n);

        // Then
        $this->assertEquals($expected, $modulo);
    }

    /**
     * Test data generated with R: a %% n
     * @return array (dividend, divisor, expected)
     */
    public function dataProviderForModuloNegativeDividend(): array
    {
        return [
            [-0, 1, 0],
            [-0, 2, 0],
            [-1, 1, 0],
            [-1, 2, 1],
            [-2, 1, 0],
            [-2, 2, 0],
            [-2, 3, 1],
            [-3, 2, 1],
            [-5, 3, 1],
            [-10, 1, 0],
            [-10, 2, 0],
            [-10, 3, 2],
            [-10, 4, 2],
            [-10, 5, 0],
            [-10, 6, 2],
            [-10, 7, 4],
            [-10, 8, 6],
            [-10, 9, 8],
            [-10, 10, 0],
            [-12, 5, 3],
            [-18, 3, 0],
            [-100, 3, 2],
            [-100, 7, 5],
            [-340, 60, 20],
        ];
    }

    /**
     * @test         modulo of negative divisor
     * @dataProvider dataProviderForModuloNegativeDivisor
     * @param        int $a dividend
     * @param        int $n divisor
     * @param        int $expected
     */
    public function testModuloNegativeDivisor(int $a, int $n, int $expected)
    {
        // When
        $modulo = Arithmetic::modulo($a, $n);

        // Then
        $this->assertEquals($expected, $modulo);
    }

    /**
     * Test data generated with R: a %% n
     * @return array (dividend, divisor, expected)
     */
    public function dataProviderForModuloNegativeDivisor(): array
    {
        return [
            [0, -1, 0],
            [0, -2, 0],
            [1, -1, 0],
            [1, -2, -1],
            [2, -1, 0],
            [2, -2, 0],
            [2, -3, -1],
            [3, -2, -1],
            [5, -3, -1],
            [10, -1, 0],
            [10, -2, 0],
            [10, -3,- 2],
            [10, -4,- 2],
            [10, -5, 0],
            [10, -6, -2],
            [10, -7, -4],
            [10, -8, -6],
            [10, -9, -8],
            [10, -10, 0],
            [12, -5, -3],
            [18, -3, 0],
            [100, -3, -2],
            [100, -7, -5],
            [340, -60, -20],
        ];
    }

    /**
     * @test         modulo of negative dividend and divisor
     * @dataProvider dataProviderForModuloNegativeDividendAndDivisor
     * @param        int $a dividend
     * @param        int $n divisor
     * @param        int $expected
     */
    public function testModuloNegativeDividendAndDivisor(int $a, int $n, int $expected)
    {
        // When
        $modulo = Arithmetic::modulo($a, $n);

        // Then
        $this->assertEquals($expected, $modulo);
    }

    /**
     * Test data generated with R: a %% n
     * @return array (dividend, divisor, expected)
     */
    public function dataProviderForModuloNegativeDividendAndDivisor(): array
    {
        return [
            [-0, -1, 0],
            [-0, -2, 0],
            [-1, -1, 0],
            [-1, -2, -1],
            [-2, -1, 0],
            [-2, -2, 0],
            [-2, -3, -2],
            [-3, -2, -1],
            [-5, -3, -2],
            [-10, -1, 0],
            [-10, -2, 0],
            [-10, -3, -1],
            [-10, -4, -2],
            [-10, -5, 0],
            [-10, -6, -4],
            [-10, -7, -3],
            [-10, -8, -2],
            [-10, -9, -1],
            [-10, -10, 0],
            [-12, -5, -2],
            [-18, -3, 0],
            [-100, -3, -1],
            [-100, -7, -2],
            [-340, -60, -40],
        ];
    }

    /**
     * @test         modulo of of zero divisor is just the dividend
     * @dataProvider dataProviderForModuloZeroDivisor
     * @param        int $a dividend
     * @param        int $n divisor
     */
    public function testModuloZeroDivisorIsDividend(int $a, int $n)
    {
        // When
        $modulo = Arithmetic::modulo($a, $n);

        // Then
        $this->assertEquals($a, $modulo);
    }

    /**
     * @return array (dividend, divisor, expected)
     */
    public function dataProviderForModuloZeroDivisor(): array
    {
        return [
            [-5, 0],
            [-4, 0],
            [-3, 0],
            [-2, 0],
            [-1, 0],
            [0, 0],
            [1, 0],
            [2, 0],
            [3, 0],
            [4, 0],
            [5, 0],
        ];
    }

    /**
     * @test         isqrt
     * @dataProvider dataProviderForIsqrt
     * @param        float $x
     * @param        int   $expected
     */
    public function testIsqrt(float $x, int $expected)
    {
        // When
        $isqrt = Arithmetic::isqrt($x);

        // Then
        $this->assertEquals($expected, $isqrt);
    }

    public function dataProviderForIsqrt(): array
    {
        return [
            [0, 0],
            [0.5, 0],
            [1, 1],
            [2, 1],
            [3, 1],
            [3.99, 1],
            [4, 2],
            [5, 2],
            [8, 2],
            [8.9939, 2],
            [9, 3],
            [25, 5],
            [27, 5],
        ];
    }

    /**
     * @test isqrt error when value is negative
     */
    public function testIsqrtNegativeNumberIsBadParameterError()
    {
        // Given
        $x = -1;

        // Then
        $this->expectException(Exception\BadParameterException::class);

        // When
        Arithmetic::isqrt($x);
    }
}
