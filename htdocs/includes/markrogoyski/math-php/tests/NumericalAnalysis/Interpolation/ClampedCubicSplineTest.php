<?php

namespace MathPHP\Tests\NumericalAnalysis\Interpolation;

use MathPHP\NumericalAnalysis\Interpolation\ClampedCubicSpline;
use MathPHP\Expression\Polynomial;
use MathPHP\Exception;

class ClampedCubicSplineTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test         Interpolated piecewise function computes expected values: p(x) = expected
     * @dataProvider dataProviderForPolynomialAgrees
     * @param        int $x
     * @param        int $expected
     * @throws       \Exception
     */
    public function testPolynomialAgrees(int $x, int $expected)
    {
        // Given
        $points = [[0, 0, 1], [1, 5, -2], [3, 2, 0], [7, 10, 3], [10, -4, 3]];

        // And
        $p = ClampedCubicSpline::interpolate($points);

        // When
        $evaluated = $p($x);

        // Then
        $this->assertEquals($expected, $evaluated);
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
        $f’ = $f->differentiate();

        // And
        $a        = 0;
        $b        = 10;
        $n        = 50;
        $tol      = 0;
        $roundoff = 0.0001; // round off error

        // And
        $p        = ClampedCubicSpline::interpolate($f, $f’, $a, $b, $n);
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
     * |f(x)-p(x)| = tol <= (5/384) * h⁴ * max f⁽⁴⁾(x)
     * where h = max hᵢ
     * and max f⁽⁴⁾(x) = f⁽⁴⁾(x) for all x given a 4th-degree polynomial f(x)
     *
     * So, tol <= (1/24) * (1/5)⁴ * 24 = (1/5)⁴
     *
     * p(x) agrees with f(x) at x = $_
     */
    public function testSolveNonZeroError($x)
    {
        // Given f(x) = x⁴ + 8x³ -13x² -92x + 96
        $f = new Polynomial([1, 8, -13, -92, 96]);
        $f’ = $f->differentiate();
        $f⁽⁴⁾ = $f’->differentiate()->differentiate()->differentiate();

        // And
        $a = 0;
        $b = 10;
        $n = 51;

        // And
        $h        = ($b - $a) / ($n - 1);
        $tol      = (5 / 384) * ($h ** 4) * $f⁽⁴⁾(0);
        $roundoff = 0.000001; // round off error

        // And
        $p        = ClampedCubicSpline::interpolate($f, $f’, $a, $b, $n);
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

    /**
     * @test   Incorrect input - The input $source is neither a callback or a set of arrays
     * @throws \Exception
     */
    public function testIncorrectInput()
    {
        // Given
        $x                 = 10;
        $incorrectFunction = $x ** 2 + 2 * $x + 1;

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        ClampedCubicSpline::getSplinePoints($incorrectFunction, [0,4,5]);
    }

    /**
     * @test   Not coordinates - array doesn't have precisely three numbers (coordinates)
     * @throws \Exception
     */
    public function testNotCoordinatesException()
    {
        // Given
        $points = [[0,0,1], [1,2,3], [2,2]];

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        ClampedCubicSpline::validateSpline($points);
    }

    /**
     * @test   Not enough arrays - There are not enough arrays in the input
     * @throws \Exception
     */
    public function testNotEnoughArraysException()
    {
        // Given
        $points = [[0,0,1]];

        // Then
        $this->expectException(Exception\BadDataException::class);

        //   When
        ClampedCubicSpline::validateSpline([[0,0,1]]);
    }

    /**
     * @test   Not a function - Two arrays share the same first number (x-component)
     * @throws \Exception
     */
    public function testNotAFunctionException()
    {
        // Given
        $points = [[0,0,1], [0,5,0], [1,1,3]];

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        ClampedCubicSpline::validateSpline($points);
    }
}
