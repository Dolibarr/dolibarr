<?php

namespace MathPHP\Tests\NumericalAnalysis\RootFinding;

use MathPHP\Expression\Polynomial;
use MathPHP\NumericalAnalysis\RootFinding\FixedPointIteration;
use MathPHP\Exception;

class FixedPointIterationTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test   Solve f(x) = x⁴ + 8x³ -13x² -92x + 96
     *         Polynomial has 4 roots: 3, 1, -8 and -4
     *         Uses \Closure object
     * @throws \Exception
     */
    public function testSolvePolynomialWithFourRootsUsingClosure()
    {
        // Given f(x) = x⁴ + 8x³ -13x² -92x + 96 = 0
        // Note that f(x) has a root at 1
        // Rewrite f(x) = 0 as (x⁴ + 8x³ -13x² + 96)/92 = x
        // Thus, g(x) = (x⁴ + 8x³ -13x² + 96)/92
        $func = function ($x) {
            return ($x ** 4 + 8 * $x ** 3 - 13 * $x ** 2 + 96) / 92;
        };
        $tol = 0.00001;

        // g(0)  = 96/92, where 0 < 96/92 < 2
        // g(2)  = 124/92, where 0 < 124/92 < 2
        // g'(x) = (4x³ + 24x² - 26x)/92 is continuous
        // g'(x) has no root on [0, 2]. Thus, the derivative of g(x) does not
        // change direction on [0, 2]. So, if g(2) > g(0), then 0 < g(x) < 2
        // for all x in [0, 2]. So, there is a root in [0, 2]

        // And
        $a        = 0;
        $b        = 2;
        $p        = 0;
        $expected = 1;

        // When solving for f(x) = 0 where x is 1
        // And switching a and b and test that they get reversed properly
        $x1 = FixedPointIteration::solve($func, $a, $b, $p, $tol);
        $x2 = FixedPointIteration::solve($func, $b, $a, $p, $tol);

        // Then
        $this->assertEqualsWithDelta($expected, $x1, $tol);
        $this->assertEqualsWithDelta($expected, $x2, $tol);
    }

    /**
     * @test   Solve f(x) = x⁴ + 8x³ -13x² -92x + 96
     *         Polynomial has 4 roots: 3, 1, -8 and -4
     *         Uses Polynomial object
     * @throws \Exception
     */
    public function testSolvePolynomialWithFourRootsUsingPolynomial()
    {
        // Given f(x) = x⁴ + 8x³ -13x² -92x + 96 = 0
        // Note that f(x) has a root at 1
        // Rewrite f(x) = 0 as (x⁴ + 8x³ -13x² + 96)/92 = x
        // Thus, g(x) = (x⁴ + 8x³ -13x² + 96)/92
        $polynomial = new Polynomial([1 / 92, 8 / 92, -13 / 92, 96 / 92]);
        $tol        = 0.00001;

        // g(0)  = 96/92, where 0 < 96/92 < 2
        // g(2)  = 124/92, where 0 < 124/92 < 2
        // g'(x) = (4x³ + 24x² - 26x)/92 is continuous
        // g'(x) has no root on [0, 2]. Thus, the derivative of g(x) does not
        // change direction on [0, 2]. So, if g(2) > g(0), then 0 < g(x) < 2
        // for all x in [0, 2]. So, there is a root in [0, 2]

        // And
        $a        = 0;
        $b        = 2;
        $p        = 0;
        $expected = 1;

        // When solving for f(x) = 0 where x is 1
        // And switching a and b and test that they get reversed properly
        $x1 = FixedPointIteration::solve($polynomial, $a, $b, $p, $tol);
        $x2 = FixedPointIteration::solve($polynomial, $b, $a, $p, $tol);

        // Then
        $this->assertEqualsWithDelta($expected, $x1, $tol);
        $this->assertEqualsWithDelta($expected, $x2, $tol);
    }

    /**
     * @test   Solve negative tolerance
     * @throws \Exception
     */
    public function testFixedPointIterationExceptionNegativeTolerance()
    {
        // Given
        $func = function ($x) {
            return ($x ** 4 + 8 * $x ** 3 - 13 * $x ** 2 + 96) / 92;
        };
        $tol = -0.00001;
        $a   = 0;
        $b   = 3;
        $p   = 0;

        // Then
        $this->expectException(Exception\OutOfBoundsException::class);

        // When
        $x = FixedPointIteration::solve($func, $a, $b, $p, $tol);
    }

    /**
     * @test   Solve zero interval
     * @throws \Exception
     */
    public function testFixedPointIterationExceptionZeroInterval()
    {
        // Given
        $func = function ($x) {
            return ($x ** 4 + 8 * $x ** 3 - 13 * $x ** 2 + 96) / 92;
        };
        $tol = 0.00001;
        $a   = 3;
        $b   = 3;
        $p   = 3;

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        $x = FixedPointIteration::solve($func, $a, $b, $p, $tol);
    }

    /**
     * @test   Solve guess not in interval
     * @throws \Exception
     */
    public function testFixedPointIterationExceptionGuessNotInInterval()
    {
        // Given
        $func = function ($x) {
            return ($x ** 4 + 8 * $x ** 3 - 13 * $x ** 2 + 96) / 92;
        };
        $tol = 0.00001;
        $a   = 0;
        $b   = 3;
        $p   = -1;

        // Then
        $this->expectException(Exception\OutOfBoundsException::class);

        // When
        $x = FixedPointIteration::solve($func, $a, $b, $p, $tol);
    }
}
