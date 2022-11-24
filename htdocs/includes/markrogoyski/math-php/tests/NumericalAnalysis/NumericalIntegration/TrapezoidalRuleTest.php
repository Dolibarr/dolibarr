<?php

namespace MathPHP\Tests\NumericalAnalysis\NumericalIntegration;

use MathPHP\Expression\Polynomial;
use MathPHP\NumericalAnalysis\NumericalIntegration\TrapezoidalRule;

class TrapezoidalRuleTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test   approximate with endpoints (0, 1) and (3, 16)
     * @throws \Exception
     *
     * f(x)                            = x² + 2x + 1
     * Antiderivative F(x)             = (1/3)x³ + x² + x
     * Indefinite integral over [0, 3] = F(3) - F(0) = 21
     *
     *  h₁, h₂, ... denotes the size on interval 1, 2, ...
     *  ζ₁, ζ₂, ... denotes the max of the second derivative of f(x) on
     *              interval 1, 2, ...
     *  f'(x)  = 2x + 2
     *  f''(x) = 2
     *  ζ      = f''(x) = 2
     *  Error  = Sum(ζ₁h₁³ + ζ₂h₂³ + ...) = 2 * Sum(h₁³ + h₂³ + ...)
     *
     *  Approximate with endpoints: (0, 1) and (3, 16)
     *  Error = 2 * ((3 - 0)²) = 18
     */
    public function testApproximateWithEndpoints()
    {
        // Given
        $points   = [[0, 1], [3, 16]];
        $tol      = 18;
        $expected = 21;

        // When
        $x = TrapezoidalRule::approximate($points);

        // Then
        $this->assertEqualsWithDelta($expected, $x, $tol);
    }

    /**
     * @test   approximate with endpoints and one interior point: (0, 1), (1, 4) and (3, 16)
     * @throws \Exception
     *
     * f(x)                            = x² + 2x + 1
     * Antiderivative F(x)             = (1/3)x³ + x² + x
     * Indefinite integral over [0, 3] = F(3) - F(0) = 21
     *
     *  h₁, h₂, ... denotes the size on interval 1, 2, ...
     *  ζ₁, ζ₂, ... denotes the max of the second derivative of f(x) on
     *              interval 1, 2, ...
     *  f'(x)  = 2x + 2
     *  f''(x) = 2
     *  ζ      = f''(x) = 2
     *  Error  = Sum(ζ₁h₁³ + ζ₂h₂³ + ...) = 2 * Sum(h₁³ + h₂³ + ...)
     *
     *  Approximate with endpoints and interior point: (0, 1), (1, 4) and (3, 16)
     *  Error = 2 * ((1 - 0)² + (3 - 1)²) = 10
     */
    public function testApproximateWithEndpointsAndOneInteriorPoint()
    {
        // Given
        $points   = [[0, 1], [1, 4], [3, 16]];
        $tol      = 10;
        $expected = 21;

        // When
        $x = TrapezoidalRule::approximate($points);

        // Then
        $this->assertEqualsWithDelta($expected, $x, $tol);
    }

    /**
     * @test   approximate with endpoints and two interior points: (0, 1), (1, 4), (2, 9) and (3, 16)
     * @throws \Exception
     *
     * f(x)                            = x² + 2x + 1
     * Antiderivative F(x)             = (1/3)x³ + x² + x
     * Indefinite integral over [0, 3] = F(3) - F(0) = 21
     *
     *  h₁, h₂, ... denotes the size on interval 1, 2, ...
     *  ζ₁, ζ₂, ... denotes the max of the second derivative of f(x) on
     *              interval 1, 2, ...
     *  f'(x)  = 2x + 2
     *  f''(x) = 2
     *  ζ      = f''(x) = 2
     *  Error  = Sum(ζ₁h₁³ + ζ₂h₂³ + ...) = 2 * Sum(h₁³ + h₂³ + ...)
     *
     *  Approximate with endpoints and interior point: (0, 1), (1, 4), (2, 9) and (3, 16)
     *  Error = 2 * ((1 - 0)² + (2 - 1)² + (3 - 2)²) = 6
     */
    public function testApproximateWithEndpointsAndTwoInteriorPoints()
    {
        // Given
        $points   = [[0, 1], [1, 4], [2, 9], [3, 16]];
        $tol      = 6;
        $expected = 21;

        // When
        $x = TrapezoidalRule::approximate($points);

        // Then
        $this->assertEqualsWithDelta($expected, $x, $tol);
    }

    /**
     * @test   approximate with endpoints and two interior points not sorted: (0, 1), (1, 4), (2, 9) and (3, 16)
     * @throws \Exception
     *
     * f(x)                            = x² + 2x + 1
     * Antiderivative F(x)             = (1/3)x³ + x² + x
     * Indefinite integral over [0, 3] = F(3) - F(0) = 21
     *
     *  h₁, h₂, ... denotes the size on interval 1, 2, ...
     *  ζ₁, ζ₂, ... denotes the max of the second derivative of f(x) on
     *              interval 1, 2, ...
     *  f'(x)  = 2x + 2
     *  f''(x) = 2
     *  ζ      = f''(x) = 2
     *  Error  = Sum(ζ₁h₁³ + ζ₂h₂³ + ...) = 2 * Sum(h₁³ + h₂³ + ...)
     *
     *  Approximate with endpoints and interior point: (0, 1), (1, 4), (2, 9) and (3, 16)
     *  Error = 2 * ((1 - 0)² + (2 - 1)² + (3 - 2)²) = 6
     */
    public function testApproximateWithEndpointsAndTwoInteriorPointsNotSorted()
    {
        // Given
        $points   = [[1, 4], [3, 16], [0, 1], [2, 9]];
        $tol      = 6;
        $expected = 21;

        // When
        $x = TrapezoidalRule::approximate($points);

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
     *  h₁, h₂, ... denotes the size on interval 1, 2, ...
     *  ζ₁, ζ₂, ... denotes the max of the second derivative of f(x) on
     *              interval 1, 2, ...
     *  f'(x)  = 2x + 2
     *  f''(x) = 2
     *  ζ      = f''(x) = 2
     *  Error  = Sum(ζ₁h₁³ + ζ₂h₂³ + ...) = 2 * Sum(h₁³ + h₂³ + ...)
     */
    public function testApproximateUsingCallback()
    {
        // Given x² + 2x + 1
        $func = $func = function ($x) {
            return $x ** 2 + 2 * $x + 1;
        };
        $start    = 0;
        $end      = 3;
        $n        = 4;
        $tol      = 6;
        $expected = 21;

        // When
        $x = TrapezoidalRule::approximate($func, $start, $end, $n);

        // Then
        $this->assertEqualsWithDelta($expected, $x, $tol);
    }

    /**
     * @test         approximate e^x² (http://tutorial.math.lamar.edu/Classes/CalcII/ApproximatingDefIntegrals.aspx)
     * @dataProvider dataProviderForEXSquared
     * @param        int   $n
     * @param        float $expected
     * @param        float $tol
     * @throws       \Exception
     */
    public function testApproximateUsingCallback2(int $n, float $expected, float $tol)
    {
        // Given e^x²
        $func = function ($x) {
            return M_E ** ($x ** 2);
        };
        $start    = 0;
        $end      = 2;

        // When
        $x = TrapezoidalRule::approximate($func, $start, $end, $n);

        // Then
        $this->assertEqualsWithDelta($expected, $x, $tol);
    }

    /**
     * http://tutorial.math.lamar.edu/Classes/CalcII/ApproximatingDefIntegrals.aspx
     * @return array (n, expected, tol)
     */
    public function dataProviderForEXSquared(): array
    {
        return [
            [4, 20.64455905, 4.19193129],
            [8, 17.5650858, 1.1124580],
            [16, 16.7353812, 0.2827535],
            [32, 16.5236176, 0.0709898],
            [64, 16.4703942, 0.0177665],
            [128, 16.4570706, 0.0044428],
        ];
    }

    /**
     * @test   approximate using polynomial
     * @throws \Exception
     *
     * f(x)                            = x² + 2x + 1
     * Antiderivative F(x)             = (1/3)x³ + x² + x
     * Indefinite integral over [0, 3] = F(3) - F(0) = 21
     *
     *  h₁, h₂, ... denotes the size on interval 1, 2, ...
     *  ζ₁, ζ₂, ... denotes the max of the second derivative of f(x) on
     *              interval 1, 2, ...
     *  f'(x)  = 2x + 2
     *  f''(x) = 2
     *  ζ      = f''(x) = 2
     *  Error  = Sum(ζ₁h₁³ + ζ₂h₂³ + ...) = 2 * Sum(h₁³ + h₂³ + ...)
     */
    public function testApproximateUsingPolynomial()
    {
        // Given x² + 2x + 1
        $polynomial = new Polynomial([1, 2, 1]);
        $start      = 0;
        $end        = 3;
        $n          = 4;
        $tol        = 6;
        $expected   = 21;

        // When
        $x = TrapezoidalRule::approximate($polynomial, $start, $end, $n);

        // Then
        $this->assertEqualsWithDelta($expected, $x, $tol);
    }
}
