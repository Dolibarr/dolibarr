<?php

namespace MathPHP\Tests\NumericalAnalysis\NumericalIntegration;

use MathPHP\Expression\Polynomial;
use MathPHP\NumericalAnalysis\NumericalIntegration\SimpsonsRule;

class SimpsonsRuleTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test   approximate with points (0, 1), (1.5, 6.25) and (3, 16)
     * @throws \Exception
     *
     * f(x)                            = x² + 2x + 1
     * Antiderivative F(x)             = (1/3)x³ + x² + x
     * Indefinite integral over [0, 3] = F(3) - F(0) = 21
     *
     * h           denotes the size of subintervals, or equivalently, the distance between two points
     * ζ₁, ζ₂, ... denotes the max of the fourth derivative of f(x) on interval 1, 2, ...
     * f'(x)    = 2x + 2
     * f''(x)   = 2
     * f'''(x)  = 0
     * f''''(x) = 0
     * ζ        = f''''(x) = 0
     * Error    = O(h^5 * ζ) = 0
     *
     * Approximate with points (0, 1), (1.5, 6.25) and (3, 16)
     * Error = 0
     */
    public function testApproximateWithPoints()
    {
        // Given
        $points   = [[0, 1], [1.5, 6.25], [3, 16]];
        $tol      = 0;
        $expected = 21;

        // When
        $x = SimpsonsRule::approximate($points);

        // Then
        $this->assertEqualsWithDelta($expected, $x, $tol);
    }

    /**
     * @test   approximate with points not sorted: (0, 1), (1.5, 6.25) and (3, 16)
     * @throws \Exception
     *
     * f(x)                            = x² + 2x + 1
     * Antiderivative F(x)             = (1/3)x³ + x² + x
     * Indefinite integral over [0, 3] = F(3) - F(0) = 21
     *
     * h           denotes the size of subintervals, or equivalently, the distance between two points
     * ζ₁, ζ₂, ... denotes the max of the fourth derivative of f(x) on interval 1, 2, ...
     * f'(x)    = 2x + 2
     * f''(x)   = 2
     * f'''(x)  = 0
     * f''''(x) = 0
     * ζ        = f''''(x) = 0
     * Error    = O(h^5 * ζ) = 0
     *
     * Approximate with points (0, 1), (1.5, 6.25) and (3, 16)
     * Error = 0
     */
    public function testApproximateWithPointsNotSorted()
    {
        // Given
        $points   = [[1.5, 6.25], [3, 16], [0, 1]];
        $tol      = 0;
        $expected = 21;

        // When
        $x = SimpsonsRule::approximate($points);

        // Then
        $this->assertEqualsWithDelta($expected, $x, $tol);
    }

    /**
     * @test   approximate using callback
     * @throws \Exception
     *
     * f(x)                            = x² + 2x + 1
     * Antiderivative F(x)             = (1/3)x³ + x² + x
     * Indefinite integral over [0, 3] = F(3) - F(0) = 21
     *
     * h           denotes the size of subintervals, or equivalently, the distance between two points
     * ζ₁, ζ₂, ... denotes the max of the fourth derivative of f(x) on interval 1, 2, ...
     * f'(x)    = 2x + 2
     * f''(x)   = 2
     * f'''(x)  = 0
     * f''''(x) = 0
     * ζ        = f''''(x) = 0
     * Error    = O(h^5 * ζ) = 0
     */
    public function testApproximateUsingCallback()
    {
        // Given x² + 2x + 1
        $func = $func = function ($x) {
            return $x ** 2 + 2 * $x + 1;
        };
        $start    = 0;
        $end      = 3;
        $n        = 3;
        $tol      = 0;
        $expected = 21;

        // When
        $x = SimpsonsRule::approximate($func, $start, $end, $n);

        // Then
        $this->assertEqualsWithDelta($expected, $x, $tol);
    }

    /**
     * @test   approximate using polynomial
     * @throws \Exception
     *
     * f(x)                            = x² + 2x + 1
     * Antiderivative F(x)             = (1/3)x³ + x² + x
     * Indefinite integral over [0, 3] = F(3) - F(0) = 21
     *
     * h           denotes the size of subintervals, or equivalently, the distance between two points
     * ζ₁, ζ₂, ... denotes the max of the fourth derivative of f(x) on interval 1, 2, ...
     * f'(x)    = 2x + 2
     * f''(x)   = 2
     * f'''(x)  = 0
     * f''''(x) = 0
     * ζ        = f''''(x) = 0
     * Error    = O(h^5 * ζ) = 0
     */
    public function testApproximateUsingPolynomial()
    {
        // Given x² + 2x + 1
        $polynomial = new Polynomial([1, 2, 1]);
        $start      = 0;
        $end        = 3;
        $n          = 3;
        $tol        = 0;
        $expected   = 21;

        // When
        $x = SimpsonsRule::approximate($polynomial, $start, $end, $n);

        // Then
        $this->assertEqualsWithDelta($expected, $x, $tol);
    }

    /**
     * @test   approximate error when there are not even even number of subintervals, or equivalently, there are not an add number of points
     * @throws \Exception
     */
    public function testApproximateErrorSubintervalsNotEven()
    {
        // Given
        $points = [[0,0], [4,4], [2,2], [6,6]];

        // Then
        $this->expectException(\Exception::class);

        // When
        SimpsonsRule::approximate($points);
    }

    /**
     * @test   approximate error when there is not constant spacing between points
     * @throws \Exception
     */
    public function testNonConstantSpacingException()
    {
        // Given
        $points = [[0,0], [3,3], [2,2]];

        // Then
        $this->expectException(\Exception::class);

        // When
        SimpsonsRule::approximate($points);
    }
}
