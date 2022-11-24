<?php

namespace MathPHP\Tests\LinearAlgebra\Matrix\Other;

use MathPHP\Exception;
use MathPHP\LinearAlgebra\FunctionMatrix;

class FunctionMatrixTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test   evaluate
     * @throws \Exception
     */
    public function testEvaluate()
    {
        // Given
        $A = [
            [
                function ($params) {
                    $x = $params['x'];
                    $y = $params['y'];
                    return $x ** 2 * $y;
                }
            ],
            [
                function ($params) {
                    $x = $params['x'];
                    $y = $params['y'];
                    return 5 * $x +  \sin($y);
                }
            ],
        ];
        $M  = new FunctionMatrix($A);

        // When
        $ME = $M->evaluate(['x' => 1, 'y' => 2]);

        // Then
        $this->assertEqualsWithDelta(2, $ME->get(0, 0), 0.000001);
        $this->assertEqualsWithDelta(5.90929742683, $ME->get(1, 0), 0.000001);
    }

    /**
     * @test   evaluate
     * @throws \Exception
     */
    public function testEvaluateSquare()
    {
        // Given
        $A = [
            [
                function ($params) {
                    $x = $params['x'];
                    $y = $params['y'];
                    return $x + $y;
                },
                function ($params) {
                    $x = $params['x'];
                    $y = $params['y'];
                    return $x - $y;
                }
            ],
            [
                function ($params) {
                    $x = $params['x'];
                    $y = $params['y'];
                    return $x * $y;
                },
                function ($params) {
                    $x = $params['x'];
                    $y = $params['y'];
                    return $x / $y;
                }
            ],
        ];
        $M  = new FunctionMatrix($A);

        // When
        $ME = $M->evaluate(['x' => 1, 'y' => 2]);

        // Then
        $this->assertEqualsWithDelta(3, $ME[0][0], 0.000001);
        $this->assertEqualsWithDelta(-1, $ME[0][1], 0.000001);
        $this->assertEqualsWithDelta(2, $ME[1][0], 0.000001);
        $this->assertEqualsWithDelta(1 / 2, $ME[1][1], 0.000001);
    }

    /**
     * @test   evaluate
     * @throws \Exception
     */
    public function testConstructionExceptionDifferenceDimensions()
    {
        // Given
        $A = [
            [
                function ($params) {
                    $x = $params['x'];
                    $y = $params['y'];
                    return $x ** 2 * $y;
                },
                function ($params) {
                    $x = $params['x'];
                    $y = $params['y'];
                    return $x ** 2 * $y;
                },
            ],
            [
                function ($params) {
                    $x = $params['x'];
                    $y = $params['y'];
                    return 5 * $x +  \sin($y);
                }
            ],
        ];

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        $M  = new FunctionMatrix($A);
    }
}
