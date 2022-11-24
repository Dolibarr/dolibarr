<?php

namespace MathPHP\Tests\NumericalAnalysis\NumericalDifferentiation;

use MathPHP\NumericalAnalysis\NumericalDifferentiation\FivePointFormula;

class FivePointFormulaTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test         differentiate zero error using callback - Check that the endpoint formula agrees with f'(x) at x = $_
     * @dataProvider dataProviderForTestDifferentiateZeroError
     * @param        int $x
     * @throws       \Exception
     *
     * f(x) = 13x² -92x + 96
     * f’(x) = 26x - 92
     *
     *                                      h⁴
     * Error term for the Midpoint Formula: - f⁽⁵⁾(ζ₁)
     *                                      30
     *
     * where ζ₁ lies between x₀ - 2h and x₀ + 2h
     *
     *                                      h⁴
     * Error term for the Endpoint Formula: - f⁽⁵⁾(ζ₀)
     *                                      5
     *
     * where ζ₀ lies between x₀ and x₀ + 4h
     *
     * f'(x)   = 26x - 92
     * f''(x)  = 26
     * f⁽³⁾(x) = 0
     * ...
     * f⁽⁵⁾(x) = 0
     * Thus, our error is zero in both formulas for our function $f
     */
    public function testDifferentiateZeroError(int $x)
    {
        // Given f(x) = 13x² -92x + 96
        $f = function ($x) {
            return 13 * $x ** 2 - 92 * $x + 96;
        };

        // And f’(x) = 26x - 92
        $f’ = function ($x) {
            return 26 * $x - 92;
        };
        $expected = $f’($x);

        // And
        $n = 5;
        $a = 0;
        $b = 4;

        // When
        $actual = FivePointFormula::differentiate($x, $f, $a, $b, $n);

        // Then
        $this->assertEquals($expected, $actual);
    }

    /**
     * @return array (x)
     */
    public function dataProviderForTestDifferentiateZeroError(): array
    {
        return [
            [0],
            [2],
            [4],
        ];
    }

    /**
     * @test         differentiate non-zero error using callback - Check that the endpoint formula agrees with f'(x) at x = $_
     * @dataProvider dataProviderForTestDifferentiateNonZeroError
     * @param        int $x
     * @param        int $tol
     * @throws       \Exception
     *
     * f(x) = x⁵ - 13x² -92x + 96
     * f’(x) = 5x⁴ -26x - 92
     *
     *                                      h⁴
     * Error term for the Midpoint Formula: - f⁽⁵⁾(ζ₁)
     *                                      30
     *
     * where ζ₁ lies between x₀ - 2h and x₀ + 2h
     *
     *                                      h⁴
     * Error term for the Endpoint Formula: - f⁽⁵⁾(ζ₀)
     *                                      5
     *
     * where ζ₀ lies between x₀ and x₀ + 4h
     *
     * f'(x)   = 5x⁴ - 26x - 92
     * f''(x)  = 20x³ - 26
     * f⁽³⁾(x) = 60x²
     * f⁽⁴⁾(x) = 120x
     * f⁽⁵⁾(x) = 12 *
     * Error in Midpoint Formula on [0,4] (where h=1) < 4
     * Error in Endpoint Formula on [0,4] (where h=1) < 24
     */
    public function testDifferentiateNonZeroError(int $x, int $tol)
    {
        // Given f(x) = x⁵ - 13x² -92x + 96
        $f = function ($x) {
            return $x ** 5 - 13 * $x ** 2 - 92 * $x + 96;
        };

        // And f’(x) = 5x⁴ -26x - 92
        $f’ = function ($x) {
            return 5 * $x ** 4 - 26 * $x - 92;
        };
        $expected = $f’($x);

        // And
        $n = 5;
        $a = 0;
        $b = 4;

        // When
        $actual = FivePointFormula::differentiate($x, $f, $a, $b, $n);

        // Then
        $this->assertEqualsWithDelta($expected, $actual, $tol);
    }

    /**
     * @return array (x, tol)
     */
    public function dataProviderForTestDifferentiateNonZeroError(): array
    {
        return [
            [0, 24],
            [2, 4],
            [4, 24],
        ];
    }

    /**
     * @test         differentiate zero error using points - Check that the endpoint formula agrees with f'(x) at x = $_
     * @dataProvider dataProviderForTestDifferentiateZeroError
     * @param        int $x
     * @throws       \Exception
     *
     * f(x) = 13x² -92x + 96
     * f’(x) = 26x - 92
     *
     *                                      h⁴
     * Error term for the Midpoint Formula: - f⁽⁵⁾(ζ₁)
     *                                      30
     *
     * where ζ₁ lies between x₀ - 2h and x₀ + 2h
     *
     *                                      h⁴
     * Error term for the Endpoint Formula: - f⁽⁵⁾(ζ₀)
     *                                      5
     *
     * where ζ₀ lies between x₀ and x₀ + 4h
     *
     * f'(x)   = 26x - 92
     * f''(x)  = 26
     * f⁽³⁾(x) = 0
     * ...
     * f⁽⁵⁾(x) = 0
     * Thus, our error is zero in both formulas for our function $f
     */
    public function testDifferentiateZeroErrorUsingPoints(int $x)
    {
        // Given f(x) = 13x² -92x + 96
        $f = function ($x) {
            return 13 * $x ** 2 - 92 * $x + 96;
        };
        $points = [[0, $f(0)], [1, $f(1)], [2, $f(2)], [3, $f(3)], [4, $f(4)]];

        // And f’(x) = 26x - 92
        $f’ = function ($x) {
            return 26 * $x - 92;
        };
        $expected = $f’($x);

        // When
        $actual = FivePointFormula::differentiate($x, $points);

        // Then
        $this->assertEquals($expected, $actual);
    }
}
