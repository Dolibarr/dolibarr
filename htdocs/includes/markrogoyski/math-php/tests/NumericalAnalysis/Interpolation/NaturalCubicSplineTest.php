<?php

namespace MathPHP\Tests\NumericalAnalysis\Interpolation;

use MathPHP\NumericalAnalysis\Interpolation\NaturalCubicSpline;
use MathPHP\Expression\Polynomial;

class NaturalCubicSplineTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test         Interpolated piecewise function computes expected values: p(x) = expected
     * @dataProvider dataProviderForPiecewiseForPolynomialAgrees
     * @param        int $x
     * @param        int $expected
     * @throws       \Exception
     */
    public function testPolynomialAgrees(int $x, int $expected)
    {
        // Given
        $points = [[0, 0], [1, 5], [3, 2], [7, 10], [10, -4]];

        // And
        $p = NaturalCubicSpline::interpolate($points);

        // When
        $evaluated = $p($x);

        // Then
        $this->assertEquals($expected, $evaluated);
    }

    /**
     * @return array (x, expected)
     */
    public function dataProviderForPiecewiseForPolynomialAgrees(): array
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
     * @test         Solve zero error
     * @dataProvider dataProviderForSolve
     * @param        float $x
     * @throws       \Exception
     *
     * f(x) = 8x³ -13x² -92x + 96
     *
     * The error in the Cubic Spline Interpolating Polynomial is proportional
     * to the max value of the 4th derivative. Thus, if our input Function
     * is a 3rd-degree polynomial, the fourth derivative will be zero, and
     * thus we will have zero error.
     *
     * p(x) agrees with f(x) at x = $_
     */
    public function testSolveZeroError($x)
    {
        // Given f(x) = 8x³ -13x² -92x + 96
        $f  = new Polynomial([8, -13, -92, 96]);

        // And
        $a        = 0;
        $b        = 10;
        $n        = 50;
        $tol      = 0;
        $roundoff = 0.0001; // round off error

        // And
        $p        = NaturalCubicSpline::interpolate($f, $a, $b, $n);
        $expected = $f($x);

        // When
        $evaluated = $p($x);

        // Then
        $this->assertEqualsWithDelta($expected, $evaluated, $tol + $roundoff);
    }

    /**
     * @test         Solve non-zero error
     * @dataProvider dataProviderForSolve
     * @param        float $x
     * @throws       \Exception
     *
     * f(x) = x⁴ + 8x³ -13x² -92x + 96
     *
     * The error is bounded by:
     * |f(x)-p(x)| = tol <= (1/4!) * h⁴ * max f⁽⁴⁾(x)
     * where h = max hᵢ
     *
     * f'(x)  = 4x³ +24x² -26x - 92
     * f''(x) = 12x² - 48x - 26
     * f'''(x) = 24x - 48
     * f⁽⁴⁾(x) = 24
     *
     * So, tol <= (1/24) * (1/5)⁴ * 24 = (1/5)⁴
     *
     * p(x) agrees with f(x) at x = $_
     */
    public function testSolveNonZeroError($x)
    {
        // Given f(x) = x⁴ + 8x³ -13x² -92x + 96
        $f = new Polynomial([1, 8, -13, -92, 96]);

        // And
        $a = 0;
        $b = 10;
        $n = 51;

        // And
        $tol      = $tol = 0.2 ** 4;  // So, tol <= (1/24) * (1/5)⁴ * 24 = (1/5)⁴
        $roundoff = 0.000001;         // round off error

        // And
        $p        = NaturalCubicSpline::interpolate($f, $a, $b, $n);
        $expected = $f($x);

        // When
        $evaluated = $p($x);

        // Then
        $this->assertEqualsWithDelta($expected, $evaluated, $tol + $roundoff);
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
            [7.32],  // not a node
        ];
    }
}
