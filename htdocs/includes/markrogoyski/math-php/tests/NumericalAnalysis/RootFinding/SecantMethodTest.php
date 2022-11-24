<?php

namespace MathPHP\Tests\NumericalAnalysis\RootFinding;

use MathPHP\Expression\Polynomial;
use MathPHP\NumericalAnalysis\RootFinding\SecantMethod;

class SecantMethodTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test   Solve f(x) = x⁴ + 8x³ -13x² -92x + 96
     *         Polynomial has 4 roots: 3, 1, -8 and -4
     *         Uses \Closure object
     * @dataProvider dataProviderForPolynomial
     * @param        int $p₀
     * @param        int $p₁
     * @param        int $expected
     * @throws       \Exception
     */
    public function testSolvePolynomialWithFourRootsUsingClosure(int $p₀, int $p₁, int $expected)
    {
        // Given
        $func = function ($x) {
            return $x ** 4 + 8 * $x ** 3 - 13 * $x ** 2 - 92 * $x + 96;
        };
        $tol = 0.00001;

        // When solving for f(x) = 0 where x $expected
        $x = SecantMethod::solve($func, $p₀, $p₁, $tol);

        // Then
        $this->assertEqualsWithDelta($expected, $x, $tol);
    }

    /**
     * @test   Solve f(x) = x⁴ + 8x³ -13x² -92x + 96
     *         Polynomial has 4 roots: 3, 1, -8 and -4
     *         Uses Polynomial object
     * @dataProvider dataProviderForPolynomial
     * @param        int $p₀
     * @param        int $p₁
     * @param        int $expected
     * @throws       \Exception
     */
    public function testSolvePolynomialWithFourRootsUsingPolynomial(int $p₀, int $p₁, int $expected)
    {
        // Given
        $polynomial = new Polynomial([1, 8, -13, -92, 96]);
        $tol        = 0.00001;

        // When solving for f(x) = 0 where x is $expected
        $x = SecantMethod::solve($polynomial, $p₀, $p₁, $tol);

        // Then
        $this->assertEqualsWithDelta($expected, $x, $tol);
    }

    /**
     * @return array (p₀, p₁, expected)
     */
    public function dataProviderForPolynomial(): array
    {
        return [
            'solving for f(x) = 0 where x is -4' => [-5, -2, -4],
            'solving for f(x) = 0 where x is -8' => [-10, -7, -8],
            'solving for f(x) = 0 where x is 3'  => [2, 5, 3],
            'solving for f(x) = 0 where x is 1'  => [-1, 2, 1],
            'Solve for f(x) = 0 where x is 1: Switch p₀ and p₁ and test that they get reversed properly' => [-1, 2, 1],
        ];
    }

    /**
     * @test   Solve f(x) = x³ - x + 1
     *         Polynomial has a root of approximately -1.324717
     * @throws \Exception
     */
    public function testXCubedSubtractXPlusOne()
    {
        // Given
        $func = function ($x) {
            return $x ** 3 - $x + 1;
        };

        // And
        $expected = -1.324717;
        $p₁       = -3;
        $p₀       = 1;
        $tol      = 0.00001;

        // When
        $root = SecantMethod::solve($func, $p₀, $p₁, $tol);

        // Then
        $this->assertEqualsWithDelta($expected, $root, $tol);
    }

    /**
     * @test   Solve f(x) = x² - 5
     *         Polynomial has a root of √5
     * @throws \Exception
     */
    public function testXSquaredSubtractFive()
    {
        // Given
        $func = function ($x) {
            return $x ** 2 - 5;
        };

        // And
        $expected = \sqrt(5);
        $p₁       = 1;
        $p₀       = 5;
        $tol      = 0.00001;

        // When
        $root = SecantMethod::solve($func, $p₀, $p₁, $tol);

        // Then
        $this->assertEqualsWithDelta($expected, $root, $tol);
    }

    /**
     * @test   Solve \cos(x) - 2x
     *         Has a root of approximately 0.450183
     * @throws \Exception
     */
    public function testCosXSubtractTwoX()
    {
        // Given
        $func = function ($x) {
            return \cos($x) - 2 * $x;
        };

        // And
        $expected = 0.450183;
        $p₁       = 0;
        $p₀       = 3;
        $tol      = 0.00001;

        // When
        $root = SecantMethod::solve($func, $p₀, $p₁, $tol);

        // Then
        $this->assertEqualsWithDelta($expected, $root, $tol);
    }

    /**
     * @test   Solve with negative tolerance
     * @throws \Exception
     */
    public function testExceptionNegativeTolerance()
    {
        // Given
        $func = function ($x) {
            return $x ** 4 + 8 * $x ** 3 - 13 * $x ** 2 - 92 * $x + 96;
        };

        // And
        $tol      = -0.00001;
        $p₀       = -1;
        $p₁       = 2;

        // Then
        $this->expectException('\Exception');

        // When
        $x = SecantMethod::solve($func, $p₀, $p₁, $tol);
    }

    /**
     * @test   Solve with zero interval
     * @throws \Exception
     */
    public function testExceptionZeroInterval()
    {
        // Given
        $func = function ($x) {
            return $x ** 4 + 8 * $x ** 3 - 13 * $x ** 2 - 92 * $x + 96;
        };

        // And
        $tol = 0.00001;
        $p₀  = 1;
        $p₁  = 1;

        // Then
        $this->expectException('\Exception');

        // When
        $x = SecantMethod::solve($func, $p₀, $p₁, $tol);
    }
}
