<?php

namespace MathPHP\Tests\NumericalAnalysis\RootFinding;

use MathPHP\Expression\Polynomial;
use MathPHP\NumericalAnalysis\RootFinding\BisectionMethod;
use MathPHP\Exception;

class BisectionMethodTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test Solve f(x) = x⁴ + 8x³ -13x² -92x + 96
     *       Polynomial has 4 roots: 3, 1, -8 and -4
     *       Uses \Closure object
     * @dataProvider dataProviderForPolynomial
     * @param        int $a
     * @param        int $b
     * @param        int $expected
     * @throws       \Exception
     */
    public function testSolvePolynomialWithFourRootsUsingClosure(int $a, int $b, int $expected)
    {
        // Given f(x) = x⁴ + 8x³ -13x² -92x + 96
        // This polynomial has 4 roots: 3, 1 ,-8 and -4
        $func = function ($x) {
            return $x ** 4 + 8 * $x ** 3 - 13 * $x ** 2 - 92 * $x + 96;
        };
        $tol = 0.00001;

        // When
        $x = BisectionMethod::solve($func, $a, $b, $tol);

        // The
        $this->assertEqualsWithDelta($expected, $x, $tol);
    }

    /**
     * @test Solve f(x) = x⁴ + 8x³ -13x² -92x + 96
     *       Polynomial has 4 roots: 3, 1, -8 and -4
     *       Uses Polynomial object
     * @dataProvider dataProviderForPolynomial
     * @param        int $a
     * @param        int $b
     * @param        int $expected
     * @throws       \Exception
     */
    public function testSolvePolynomialWithFourRootsUsingPolynomial(int $a, int $b, int $expected)
    {
        // Given f(x) = x⁴ + 8x³ -13x² -92x + 96
        // This polynomial has 4 roots: 3, 1 ,-8 and -4
        $polynomial = new Polynomial([1, 8, -13, -92, 96]);
        $tol        = 0.00001;

        // When
        $x = BisectionMethod::solve($polynomial, $a, $b, $tol);

        // The
        $this->assertEqualsWithDelta($expected, $x, $tol);
    }

    /**
     * @return array (a, b, expected)
     */
    public function dataProviderForPolynomial(): array
    {
        return [
            'f(x) = 0 where x is -4' => [-7, 0, -4],
            'f(x) = 0 where x is -8' => [-10, -5, -8],
            'f(x) = 0 where x is 3'  => [2, 5, 3],
            'f(x) = 0 where x is 1'  => [0, 2, 1],
            'f(x) = 0 where x is 1 (Switch a and b and test that they get reversed properly)' => [2, 0, 1],
        ];
    }

    /**
     * @test   Solve more polynomials
     * @throws \Exception
     * Example from https://en.wikipedia.org/wiki/Bisection_method
     */
    public function testSolveXCubedSubtractXSubtractTwo()
    {
        // Given f(x) = x³ - x - 2
        $func = function ($x) {
            return $x ** 3 - $x - 2;
        };
        $tol = 0.001;

        // And solving for f(x) = 0 where x is about 1.521 (Find the root 1.521)
        $a        = 1;
        $b        = 2;
        $expected = 1.521;

        // When
        $x = BisectionMethod::solve($func, $a, $b, $tol);

        // Then
        $this->assertEqualsWithDelta($expected, $x, $tol);
    }

    /**
     * @test   Solve more polynomials
     * @throws \Exception
     * Example from https://ece.uwaterloo.ca/~dwharder/NumericalAnalysis/10RootFinding/bisection/examples.html
     */
    public function testSolveXSquaredSubtractThree()
    {
        // Given f(x) = x² - 3
        $func = function ($x) {
            return $x ** 2 - 3;
        };
        $tol = 0.01;

        // And solving for f(x) = 0 where x is about 1.7344 (Find the root 1.7344)
        $a        = 1;
        $b        = 2;
        $expected = 1.7344;

        // When
        $x = BisectionMethod::solve($func, $a, $b, $tol);

        // Then
        $this->assertEqualsWithDelta($expected, $x, $tol);
    }

    /**
     * @test   Solve more polynomials
     * @throws \Exception
     * Example from https://ece.uwaterloo.ca/~dwharder/NumericalAnalysis/10RootFinding/bisection/examples.html
     */
    public function testSolveEToNegativeXTimesSomeStuff()
    {
        // Given f(x) = e⁻ˣ (3.2 sin(x) - 0.5\cos(x))
        $func = function ($x) {
            return \exp(-$x) * ((3.2 *  \sin($x)) - (0.5 * \cos($x)));
        };
        $tol = 0.0001;

        // And solving for f(x) = 0 where x is about 3.2968 (Find the root 3.2968)
        $a        = 3;
        $b        = 4;
        $expected = 3.2968;

        // When
        $x = BisectionMethod::solve($func, $a, $b, $tol);

        // Then
        $this->assertEqualsWithDelta($expected, $x, $tol);
    }

    /**
     * @test   Solve with negative tolerance
     * @throws \Exception
     */
    public function testBisectionMethodExceptionNegativeTolerance()
    {
        // Given
        $func = function ($x) {
            return $x ** 4 + 8 * $x ** 3 - 13 * $x ** 2 - 92 * $x + 96;
        };

        // And
        $tol = -0.00001;
        $a   = 0;
        $b   = 2;

        // Then
        $this->expectException(Exception\OutOfBoundsException::class);

        // When
        $x = BisectionMethod::solve($func, $a, $b, $tol);
    }

    /**
     * @test   Solve with zero interval
     * @throws \Exception
     */
    public function testBisectionMethodExceptionZeroInterval()
    {
        // Given
        $func = function ($x) {
            return $x ** 4 + 8 * $x ** 3 - 13 * $x ** 2 - 92 * $x + 96;
        };

        // And
        $tol = 0.00001;
        $a   = 2;
        $b   = 2;

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        $x = BisectionMethod::solve($func, $a, $b, $tol);
    }

    /**
     * @test   Solve with same signs
     * @throws \Exception
     */
    public function testBisectionMethodExceptionSameSigns()
    {
        // Given
        $func = function ($x) {
            return $x + 96;
        };

        // And
        $tol = 0.00001;
        $a   = 0;
        $b   = 1;

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        $x = BisectionMethod::solve($func, $a, $b, $tol);
    }
}
