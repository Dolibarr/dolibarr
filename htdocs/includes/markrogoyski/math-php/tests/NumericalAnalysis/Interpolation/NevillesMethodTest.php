<?php

namespace MathPHP\Tests\NumericalAnalysis\Interpolation;

use MathPHP\NumericalAnalysis\Interpolation\NevillesMethod;

class NevillesMethodTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test         Interpolate
     * @dataProvider dataProviderForPolynomialAgrees
     * @param        int $x
     * @param        int $expected
     * @throws       \Exception
     */
    public function testPolynomialAgrees(int $x, int $expected)
    {
        // Given
        $points   = [[0, 0], [1, 5], [3, 2], [7, 10], [10, -4]];
        $roundoff = 0.0000001; // round off error

        // When
        $interpolated = NevillesMethod::interpolate($x, $points);

        // Then
        $this->assertEqualsWithDelta($expected, $interpolated, $roundoff);
    }

    /**
     * @return array (x, expected)
     */
    public function dataProviderForPolynomialAgrees(): array
    {
        return [
            [0, 0],    // p(0) = 0
            [1, 5],    // p(1) = 5
            [3, 2],    // p(3) = 2
            [7, 10],   // p(7) = 10
            [10, -4],  // p(10) = -4
        ];
    }

    /**
     * @test         Solve
     * @dataProvider dataProviderForSolve
     * @param        float $x
     * @throws       \Exception
     *
     * f(x) = x⁴ + 8x³ -13x² -92x + 96
     *
     * Given n points, the error in Nevilles Method (Lagrange polynomials) is proportional
     * to the max value of the nth derivative. Thus, if we if interpolate n at
     * 6 points, the 5th derivative of our original function f(x) = 0, and so
     * our resulting polynomial will have no error.
     *
     * p(x) agrees with f(x) at x = $_
     */
    public function testSolve($x)
    {
        // f(x) = x⁴ + 8x³ -13x² -92x + 96
        $f = function ($x) {
            return $x ** 4 + 8 * $x ** 3 - 13 * $x ** 2 - 92 * $x + 96;
        };

        // And
        $a        = 0;
        $b        = 10;
        $n        = 5;
        $roundoff = 0.000001; // round off error

        // And
        $expected = $f($x);

        // When
        $actual = NevillesMethod::interpolate($x, $f, $a, $b, $n);

        // Then
        $this->assertEqualsWithDelta($expected, $actual, $roundoff);
    }

    /**
     * @test         Solve
     * @dataProvider dataProviderForSolve
     * @param        float $x
     * @throws       \Exception
     *
     * f(x) = x⁴ + 8x³ -13x² -92x + 96
     *
     * The error is bounded by:
     * |f(x)-p(x)| = tol <= (max f⁽ⁿ⁺¹⁾(x))*(x-x₀)*...*(x-xn)/(n+1)!
     *
     * f'(x)  = 4x³ +24x² -26x - 92
     * f''(x) = 12x² - 48x - 26
     * f'''(x) = 24x - 48
     * f⁽⁴⁾(x) = 24
     *
     *  o, tol <= 24*(x-x₀)*...*(x-xn)/(4!) = (x-x₀)*...*(x-xn) where
     *
     * p(x) agrees with f(x) at x = $_
     */
    public function testSolveNonZeroError($x)
    {
        // f(x) = x⁴ + 8x³ -13x² -92x + 96
        $f = function ($x) {
            return $x ** 4 + 8 * $x ** 3 - 13 * $x ** 2 - 92 * $x + 96;
        };

        // And
        $a = 0;
        $b = 9;
        $n = 4;

        $x₀  = 0;
        $x₁  = 3;
        $x₂  = 6;
        $x₃  = 9;
        $tol = \abs(($x - $x₀) * ($x - $x₁) * ($x - $x₂) * ($x - $x₃));

        $roundoff = 0.0000001; // round off error

        // And
        $expected = $f($x);

        // When
        $actual = NevillesMethod::interpolate($x, $f, $a, $b, $n);

        // Then
        $this->assertEqualsWithDelta($expected, $actual, $tol + $roundoff);
    }

    /**
     * @return array p(x) agrees with f(x) at x = $_
     */
    public function dataProviderForSolve(): array
    {
        return [
            [0],
            [2],
            [4],
            [6],
            [8],
            [10],
            [-90],
            [-99],
        ];
    }
}
