<?php

namespace MathPHP\Tests\Statistics\Regression;

use MathPHP\Statistics\Regression\WeightedLinear;

class WeightedLinearTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test         getParameters
     * @dataProvider dataProviderForParameters
     * @param        array $points
     * @param        array $weights
     * @param        float $m
     * @param        float $b
     */
    public function testGetParameters(array $points, array $weights, float $m, float $b)
    {
        // Given
        $regression = new WeightedLinear($points, $weights);

        // When
        $parameters = $regression->getParameters();

        // Then
        $this->assertEqualsWithDelta($m, $parameters['m'], 0.0001);
        $this->assertEqualsWithDelta($b, $parameters['b'], 0.0001);
    }

    /**
     * @return array [points, weights, m, b]
     */
    public function dataProviderForParameters(): array
    {
        return [
            [
                [[1,2], [2,3], [4,5], [5,7], [6,8]],
                [1, 1, 1, 1, 1],
                1.2209302325581, 0.60465116279069
            ],
            [
                [[1,2], [2,3], [4,5], [5,7], [6,8]],
                [1, 2, 3, 4, 5],
                1.259717314, 0.439929329
            ],
        ];
    }

    /**
     * @test         evaluate
     * @dataProvider dataProviderForEvaluate
     * @param        array $points
     * @param        array $weights
     * @param        float $x
     * @param        float $expected_y
     */
    public function testEvaluate(array $points, array $weights, float $x, float $expected_y)
    {
        // Given
        $regression = new WeightedLinear($points, $weights);

        // When
        $y = $regression->evaluate($x);

        // Then
        $this->assertEqualsWithDelta($expected_y, $y, 0.0001);
    }

    /**
     * @return array [points, weights, x, expected y]
     */
    public function dataProviderForEvaluate(): array
    {
        return [
            [
                [[1,2], [2,3], [4,5], [5,7], [6,8]],
                [1, 1, 1, 1, 1],
                5,
                6.709302,
            ],
            [
                [[1,2], [2,3], [4,5], [5,7], [6,8]],
                [1, 2, 3, 4, 5],
                5,
                6.738516,
            ],
        ];
    }
}
