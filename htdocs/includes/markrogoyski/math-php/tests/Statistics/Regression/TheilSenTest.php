<?php

namespace MathPHP\Tests\Statistics\Regression;

use MathPHP\Statistics\Regression\TheilSen;

class TheilSenTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test constructor
     */
    public function testConstructor()
    {
        // Given
        $points = [ [1,2], [2,3], [4,5], [5,7], [6,8] ];

        // When
        $regression = new TheilSen($points);

        // Then
        $this->assertInstanceOf(\MathPHP\Statistics\Regression\Regression::class, $regression);
        $this->assertInstanceOf(\MathPHP\Statistics\Regression\TheilSen::class, $regression);
    }

    /**
     * @test getPoints
     */
    public function testGetPoints()
    {
        // Given
        $points = [ [1,2], [2,3], [4,5], [5,7], [6,8] ];

        // When
        $regression = new TheilSen($points);

        // Then
        $this->assertEquals($points, $regression->getPoints());
    }

    /**
     * @test getXs
     */
    public function testGetXs()
    {
        // Given
        $points = [ [1,2], [2,3], [4,5], [5,7], [6,8] ];

        // When
        $regression = new TheilSen($points);

        // Then
        $this->assertEquals([1,2,4,5,6], $regression->getXs());
    }

    /**
     * @test getYs
     */
    public function testGetYs()
    {
        // Given
        $points = [ [1,2], [2,3], [4,5], [5,7], [6,8] ];

        // When
        $regression = new TheilSen($points);

        // Then
        $this->assertEquals([2,3,5,7,8], $regression->getYs());
    }

    /**
     * @test     getEquation - Equation matches pattern y = mx + b
     * @dataProvider dataProviderForEquation
     * @param        array $points
     */
    public function testGetEquation(array $points)
    {
        // Given
        $regression = new TheilSen($points);

        // Then
        $this->assertRegExp('/^y = \d+[.]\d+x [+] \d+[.]\d+$/', $regression->getEquation());
    }

    /**
     * @return array [points]
     */
    public function dataProviderForEquation(): array
    {
        return [
            [ [ [0,0], [1,1], [2,2], [3,3], [4,4] ] ],
        ];
    }

    /**
     * @test         getParameters
     * @dataProvider dataProviderForParameters
     * @param        array $points
     * @param        float $m
     * @param        float $b
     */
    public function testGetParameters(array $points, float $m, float $b)
    {
        // Given
        $regression = new TheilSen($points);

        // When
        $parameters = $regression->getParameters();

        // Then
        $this->assertEqualsWithDelta($m, $parameters['m'], 0.0001);
        $this->assertEqualsWithDelta($b, $parameters['b'], 0.0001);
    }

    /**
     * @return array [points, m, b]
     */
    public function dataProviderForParameters(): array
    {
        return [
            [
                [ [1,2], [2,3], [4,5], [5,7], [6,8] ],
                1.225, 0.1
            ],
        ];
    }

    /**
     * @test         getSampleSize
     * @dataProvider dataProviderForSampleSize
     * @param        array $points
     * @param        int   $n
     */
    public function testGetSampleSize(array $points, int $n)
    {
        // Given
        $regression = new TheilSen($points);

        // Then
        $this->assertEquals($n, $regression->getSampleSize());
    }

    /**
     * @return array [points, n]
     */
    public function dataProviderForSampleSize()
    {
        return [
            [
                [ [1,2], [2,3], [4,5], [5,7], [6,8] ], 5
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
        $regression = new TheilSen($points);

        // Then
        $this->assertEquals($y, $regression->evaluate($x));
    }

    /**
     * @return array [points, x, y]
     */
    public function dataProviderForEvaluate(): array
    {
        return [
            [
                [ [0,0], [1,1], [2,2], [3,3], [4,4] ], // y = x + 0
                5, 5,
            ],
            [
                [ [0,0], [1,1], [2,2], [3,3], [4,4] ], // y = x + 0
                18, 18,
            ],
            [
                [ [0,0], [1,2], [2,4], [3,6] ], // y = 2x + 0
                4, 8,
            ],
            [
                [ [0,1], [1,3.5], [2,6] ], // y = 2.5x + 1
                5, 13.5
            ],
            [
                [ [0,2], [1,1], [2,0], [3,-1] ], // y = -x - 2
                4, -2
            ],
        ];
    }
}
