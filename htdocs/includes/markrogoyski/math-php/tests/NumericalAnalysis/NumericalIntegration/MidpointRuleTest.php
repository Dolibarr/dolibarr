<?php

namespace MathPHP\Tests\NumericalAnalysis\NumericalIntegration;

use MathPHP\Expression\Polynomial;
use MathPHP\NumericalAnalysis\NumericalIntegration\MidpointRule;

class MidpointRuleTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test   approximate with endpoints: (0, 1) and (3, 16)
     * @throws \Exception
     *
     * f(x)                            = -x² + 2x + 1
     * Antiderivative F(x)             = -(1/3)x³ + x² + x
     * Indefinite integral over [0, 3] = F(3) - F(0) = 3
     *
     * h₁, h₂, ... denotes the size on interval 1, 2, ...
     * ζ₁, ζ₂, ... denotes the max of the second derivative of f(x) on
     *            interval 1, 2, ...
     * f'(x)  = -2x + 2
     * f''(x) = -2
     * ζ      = |f''(x)| = 2
     * Error  = Sum(ζ₁h₁³ + ζ₂h₂³ + ...) = 2 * Sum(h₁³ + h₂³ + ...)
     *
     * Approximate with endpoints: (0, 1) and (3, 16)
     * Error = 2 * ((3 - 0)²) = 18
     */
    public function testApproximateEndpoints()
    {

        // Given
        $endpoints = [[0, 1], [3, -2]];
        $expected  = 3;
        $tol       = 18;

        // When
        $x = MidpointRule::approximate($endpoints);

        // Then
        $this->assertEqualsWithDelta($expected, $x, $tol);
    }

    /**
     * @test   approximate with endpoints: endpoints and one interior point: (0, 1), (1, 4), and (3, 16)
     * @throws \Exception
     *
     * f(x)                            = -x² + 2x + 1
     * Antiderivative F(x)             = -(1/3)x³ + x² + x
     * Indefinite integral over [0, 3] = F(3) - F(0) = 3
     *
     * h₁, h₂, ... denotes the size on interval 1, 2, ...
     * ζ₁, ζ₂, ... denotes the max of the second derivative of f(x) on
     *            interval 1, 2, ...
     * f'(x)  = -2x + 2
     * f''(x) = -2
     * ζ      = |f''(x)| = 2
     * Error  = Sum(ζ₁h₁³ + ζ₂h₂³ + ...) = 2 * Sum(h₁³ + h₂³ + ...)
     *
     * Approximate endpoints and one interior point: (0, 1), (1, 4), and (3, 16)
     * Error = 2 * ((3 - 0)²) = 18
     */
    public function testApproximateEndpointsOneInteriorPoint()
    {

        // Given
        $points    = [[0, 1], [1, 2], [3, -2]];
        $expected  = 3;
        $tol       = 10;

        // When
        $x = MidpointRule::approximate($points);

        // Then
        $this->assertEqualsWithDelta($expected, $x, $tol);
    }

    /**
     * @test   approximate with endpoints: endpoints and two interior points: (0, 1), (1, 4), (2, 9), and (3, 16)
     * @throws \Exception
     *
     * f(x)                            = -x² + 2x + 1
     * Antiderivative F(x)             = -(1/3)x³ + x² + x
     * Indefinite integral over [0, 3] = F(3) - F(0) = 3
     *
     * h₁, h₂, ... denotes the size on interval 1, 2, ...
     * ζ₁, ζ₂, ... denotes the max of the second derivative of f(x) on
     *            interval 1, 2, ...
     * f'(x)  = -2x + 2
     * f''(x) = -2
     * ζ      = |f''(x)| = 2
     * Error  = Sum(ζ₁h₁³ + ζ₂h₂³ + ...) = 2 * Sum(h₁³ + h₂³ + ...)
     *
     * Approximate endpoints and two interior points: (0, 1), (1, 4), (2, 9), and (3, 16)
     * Error = 2 * ((1 - 0)² + (2 - 1)² + (3 - 2)²) = 6
     */
    public function testApproximateEndpointsTwoInteriorPoints()
    {

        // Given
        $points    = [[0, 1], [1, 2], [2, 1], [3, -2]];
        $expected  = 3;
        $tol       = 6;

        // When
        $x = MidpointRule::approximate($points);

        // Then
        $this->assertEqualsWithDelta($expected, $x, $tol);
    }

    /**
     * @test   approximate with endpoints: endpoints and two interior points: (0, 1), (1, 4), (2, 9), and (3, 16)
     * @throws \Exception
     *
     * f(x)                            = -x² + 2x + 1
     * Antiderivative F(x)             = -(1/3)x³ + x² + x
     * Indefinite integral over [0, 3] = F(3) - F(0) = 3
     *
     * h₁, h₂, ... denotes the size on interval 1, 2, ...
     * ζ₁, ζ₂, ... denotes the max of the second derivative of f(x) on
     *            interval 1, 2, ...
     * f'(x)  = -2x + 2
     * f''(x) = -2
     * ζ      = |f''(x)| = 2
     * Error  = Sum(ζ₁h₁³ + ζ₂h₂³ + ...) = 2 * Sum(h₁³ + h₂³ + ...)
     *
     * Approximate endpoints and two interior points: (0, 1), (1, 4), (2, 9), and (3, 16)
     * Error = 2 * ((1 - 0)² + (2 - 1)² + (3 - 2)²) = 6
     */
    public function testApproximateEndpointsTwoInteriorPointsNotSorted()
    {

        // Given
        $points    = [[1, 2], [3, -2], [0, 1], [2, 1]];
        $expected  = 3;
        $tol       = 6;

        // When
        $x = MidpointRule::approximate($points);

        // Then
        $this->assertEqualsWithDelta($expected, $x, $tol);
    }

    /**
     * @test   approximate with callback
     * @throws \Exception
     *
     * f(x)                            = -x² + 2x + 1
     * Antiderivative F(x)             = -(1/3)x³ + x² + x
     * Indefinite integral over [0, 3] = F(3) - F(0) = 3
     *
     * h₁, h₂, ... denotes the size on interval 1, 2, ...
     * ζ₁, ζ₂, ... denotes the max of the second derivative of f(x) on
     *            interval 1, 2, ...
     * f'(x)  = -2x + 2
     * f''(x) = -2
     * ζ      = |f''(x)| = 2
     * Error  = Sum(ζ₁h₁³ + ζ₂h₂³ + ...) = 2 * Sum(h₁³ + h₂³ + ...)
     *
     * Approximate with endpoints: (0, 1) and (3, 16)
     * Error = 2 * ((3 - 0)²) = 18
     */
    public function testApproximateUsingCallback()
    {
        // Given -x² + 2x + 1
        $func = function ($x) {
            return -$x ** 2 + 2 * $x + 1;
        };
        $start    = 0;
        $end      = 3;
        $n        = 4;
        $tol      = 6;
        $expected = 3;

        // When
        $x = MidpointRule::approximate($func, $start, $end, $n);

        // Then
        $this->assertEqualsWithDelta($expected, $x, $tol);
    }

    /**
     * @test   approximate with polynomial
     * @throws \Exception
     *
     * f(x)                            = -x² + 2x + 1
     * Antiderivative F(x)             = -(1/3)x³ + x² + x
     * Indefinite integral over [0, 3] = F(3) - F(0) = 3
     *
     * h₁, h₂, ... denotes the size on interval 1, 2, ...
     * ζ₁, ζ₂, ... denotes the max of the second derivative of f(x) on
     *            interval 1, 2, ...
     * f'(x)  = -2x + 2
     * f''(x) = -2
     * ζ      = |f''(x)| = 2
     * Error  = Sum(ζ₁h₁³ + ζ₂h₂³ + ...) = 2 * Sum(h₁³ + h₂³ + ...)
     *
     * Approximate with endpoints: (0, 1) and (3, 16)
     * Error = 2 * ((3 - 0)²) = 18
     */
    public function testApproximateUsingPolynomial()
    {
        // Given -x² + 2x + 1
        $polynomial = new Polynomial([-1, 2, 1]);
        $start      = 0;
        $end        = 3;
        $n          = 4;
        $tol        = 6;
        $expected   = 3;

        // When
        $x = MidpointRule::approximate($polynomial, $start, $end, $n);

        // Then
        $this->assertEqualsWithDelta($expected, $x, $tol);
    }
}
