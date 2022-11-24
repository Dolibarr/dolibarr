<?php

namespace MathPHP\Tests\NumericalAnalysis\NumericalDifferentiation;

use MathPHP\NumericalAnalysis\NumericalDifferentiation\ThreePointFormula;

class ThreePointFormulaTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test         differentiate zero error using callback - Check that the endpoint/midpoint/backward endpoint formula agrees with f'(x) at x = $_
     * @dataProvider dataProviderForTestDifferentiateZeroError
     * @param        int $x
     * @throws       \Exception
     *
     * f(x)  = 13x² -92x + 96
     * f’(x) = 26x - 92
     *
     *                                      h²
     * Error term for the Midpoint Formula: - f⁽³⁾(ζ₁)
     *                                      6
     *
     *     where ζ₁ lies between x₀ - h and x₀ + h
     *
     *                                      h²
     * Error term for the Endpoint Formula: - f⁽³⁾(ζ₀)
     *                                      3
     *
     *     where ζ₀ lies between x₀ and x₀ + 2h
     *
     * f'(x)   = 26x - 92
     * f''(x)  = 26
     * f⁽³⁾(x) = 0
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
        $n = 3;
        $a = 0;
        $b = 4;

        // When
        $actual = ThreePointFormula::differentiate($x, $f, $a, $b, $n);

        // Then
        $this->assertEquals($expected, $actual);
    }

    /**
     * @return array (x)
     */
    public function dataProviderForTestDifferentiateZeroError(): array
    {
        return [
            [0],  // Check that the endpoint formula agrees with f'(x) at x = 0
            [2],  // Check that the midpoint formula agrees with f'(x) at x = 2
            [4],  // Check that the (backward) endpoint formula agrees with f'(x) at x = 4
        ];
    }

    /**
     * @test         differentiate non-zero error using callback - Check that the endpoint/midpoint/backward endpoint formula agrees with f'(x) at x = $_
     * @dataProvider dataProviderForTestDifferentiateNonZeroError
     * @param        int $x
     * @param        int $tol
     * @throws       \Exception
     *
     * f(x)  = x³ - 13x² -92x + 96
     * f'(x) = 3x² - 26x - 92
     *
     *                                      h²
     * Error term for the Midpoint Formula: - f⁽³⁾(ζ₁)
     *                                      6
     *
     *     where ζ₁ lies between x₀ - h and x₀ + h
     *
     *                                      h²
     * Error term for the Endpoint Formula: - f⁽³⁾(ζ₀)
     *                                      3
     *
     *     where ζ₀ lies between x₀ and x₀ + 2h
     *
     * f(x)    = x³ - 13x² -92x + 96
     * f'(x)   = 3x² - 26x - 92
     * f⁽³⁾(x) = 6
     * Error in Midpoint Formula on [0,2] (where h=1) < 1
     * Error in Endpoint Formula on [0,2] (where h=1) < 2
     */
    public function testDifferentiateNonZeroError(int $x, int $tol)
    {
        // Given f(x) = x³ - 13x² -92x + 96
        $f = function ($x) {
            return $x ** 3 - 13 * $x ** 2 - 92 * $x + 96;
        };

        // And
        $f’ = function ($x) {
            return 3 * $x ** 2 - 26 * $x - 92;
        };
        $expected = $f’($x);

        // And
        $n = 3;
        $a = 0;
        $b = 2;

        // When
        $actual = ThreePointFormula::differentiate($x, $f, $a, $b, $n);

        // Then
        $this->assertEqualsWithDelta($expected, $actual, $tol);
    }

    /**
     * @return array (x, tol)
     */
    public function dataProviderForTestDifferentiateNonZeroError(): array
    {
        return [
            [0, 2],
            [1, 1],
            [2, 2],
        ];
    }

    /**
     * @test         differentiate zero error using array of points - Check that the endpoint/midpoint/backward endpoint formula agrees with f'(x) at x = $_
     * @dataProvider dataProviderForTestDifferentiateZeroError
     * @param        int $x
     * @throws       \Exception
     *
     * f(x)  = 13x² -92x + 96
     * f’(x) = 26x - 92
     *
     *                                      h²
     * Error term for the Midpoint Formula: - f⁽³⁾(ζ₁)
     *                                      6
     *
     *     where ζ₁ lies between x₀ - h and x₀ + h
     *
     *                                      h²
     * Error term for the Endpoint Formula: - f⁽³⁾(ζ₀)
     *                                      3
     *
     *     where ζ₀ lies between x₀ and x₀ + 2h
     *
     * f'(x)   = 26x - 92
     * f''(x)  = 26
     * f⁽³⁾(x) = 0
     * Thus, our error is zero in both formulas for our function $f
     */
    public function testDifferentiateZeroErrorUsingPoints(int $x)
    {
        // Given f(x) = 13x² -92x + 96
        $f = function ($x) {
            return 13 * $x ** 2 - 92 * $x + 96;
        };
        $points = [[0, $f(0)], [2, $f(2)], [4, $f(4)]];

        // And f’(x) = 26x - 92
        $f’ = function ($x) {
            return 26 * $x - 92;
        };
        $expected = $f’($x);

        // When
        $actual = ThreePointFormula::differentiate($x, $points);

        // Then
        $this->assertEquals($expected, $actual);
    }
}
