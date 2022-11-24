<?php

namespace MathPHP\Tests\Statistics\Regression;

use MathPHP\Statistics\Regression\LineweaverBurk;

class LineweaverBurkTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test         getEquation - Equation matches pattern y = V * X / (K + x)
     * @dataProvider dataProviderForEquation
     * @param        array $points
     */
    public function testGetEquation(array $points)
    {
        // Given
        $regression = new LineweaverBurk($points);

        // Then
        $this->assertRegExp('/^y = \d+[.]\d+x\/\(\d+[.]\d+\+x\)$/', $regression->getEquation());
    }

    /**
     * @return array [points]
     */
    public function dataProviderForEquation(): array
    {
        return [
            [
                [ [.038, .05], [.194, .127], [.425, .094], [.626, .2122], [1.253, .2729], [2.5, .2665], [3.740, .3317] ]
            ]
        ];
    }

    /**
     * @test         getParameters
     * @dataProvider dataProviderForParameters
     * @param        array $points
     * @param        float $V
     * @param        float $K
     */
    public function testGetParameters(array $points, float $V, float $K)
    {
        // Given
        $regression = new LineweaverBurk($points);

        // When
        $parameters = $regression->getParameters();

        // Then
        $this->assertEqualsWithDelta($V, $parameters['V'], 0.0001);
        $this->assertEqualsWithDelta($K, $parameters['K'], 0.0001);
    }

    /**
     * @return array [points, V, K]
     */
    public function dataProviderForParameters(): array
    {
        return [
            [
                [ [.038, .05], [.194, .127], [.425, .094], [.626, .2122], [1.253, .2729], [2.5, .2665], [3.740, .3317] ],
                0.222903511, 0.13447224,
            ],
        ];
    }

    /**
     * @test         evaluate
     * @dataProvider dataProviderForEvaluate
     * @param        array $points
     * @param        float $x
     * @param        float $y
     */
    public function testEvaluate(array $points, float $x, float $y)
    {
        // Given
        $regression = new LineweaverBurk($points);

        // Then
        $this->assertEqualsWithDelta($y, $regression->evaluate($x), 0.0001);
    }

    /**
     * @return array [points, x, y]
     */
    public function dataProviderForEvaluate(): array
    {
        return [
            [
                [ [.038, .05], [.194, .127], [.425, .094], [.626, .2122], [1.253, .2729], [2.5, .2665], [3.740, .3317] ],
                0.038, 0.049111286,
            ],
        ];
    }
}
