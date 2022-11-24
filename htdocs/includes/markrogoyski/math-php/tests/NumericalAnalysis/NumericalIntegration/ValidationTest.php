<?php

namespace MathPHP\Tests\NumericalAnalysis\NumericalIntegration;

use MathPHP\Exception;
use MathPHP\NumericalAnalysis\NumericalIntegration\Validation;

class ValidationTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test         isSpacingConstant
     * @dataProvider dataProviderForConstantSpacedPoints
     * @param        array $points
     * @throws       \Exception
     */
    public function testIsSpacingConstant(array $points)
    {
        // When
        Validation::isSpacingConstant($points);

        // Then
        $this->assertTrue(true);
    }

    /**
     * @return array
     */
    public function dataProviderForConstantSpacedPoints(): array
    {
        return [
            [
                []
            ],
            [
                [[0,0]]
            ],
            [
                [[0,0], [1,1]]
            ],
            [
                [[0,0], [1,1], [2,2], [3,3], [4,4], [5,5]]
            ],
            [
                [[0,2], [2,4], [4,6], [6,8], [8,10], [10,12]]
            ],
            [
                [[1,0], [4,1], [7,2], [10,3], [13,4], [16,5]]
            ],
        ];
    }

    /**
     * @test         isSpacingConstant when not constant
     * @dataProvider dataProviderForNonConstantSpacedPoints
     * @param        array $points
     * @throws       \Exception
     */
    public function testIsSpacingConstantWhenNotConstant(array $points)
    {
        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        Validation::isSpacingConstant($points);
    }

    /**
     * @return array
     */
    public function dataProviderForNonConstantSpacedPoints(): array
    {
        return [
            [
                [[0,0], [1,1], [2,2], [5,3], [6,4], [7,5]]
            ],
            [
                [[0,2], [2,4], [4,6], [6,8], [8,10], [9,12]]
            ],
            [
                [[1,0], [4,1], [5,2], [10,3], [13,4], [15,5]]
            ],
        ];
    }

    /**
     * @test         isSubintervalsMultiple
     * @dataProvider dataProviderForSubintervalsMultiple
     * @param        array $points
     * @param        int   $m
     * @throws       \Exception
     */
    public function testIsSubintervalsMultiple(array $points, int $m)
    {
        // When
        Validation::isSubintervalsMultiple($points, $m);

        // Then
        $this->assertTrue(true);
    }

    /**
     * @return array
     */
    public function dataProviderForSubintervalsMultiple(): array
    {
        return [
            [
                [[0,0], [1,1]],
                1
            ],
            [
                [[0,0], [1,1], [2,2]],
                2
            ],
            [
                [[0,2], [2,4], [4,6], [6,8]],
                3
            ],
            [
                [[1,0], [4,1], [7,2], [10,3], [13,4], [16,5]],
                5
            ],
        ];
    }

    /**
     * @test         isSubintervalsMultiple when not a multiple of m
     * @dataProvider dataProviderForSubintervalsNotMultiple
     * @param        array $points
     * @param        int   $m
     * @throws       \Exception
     */
    public function testIsSubintervalsMultipleNotMultiple(array $points, int $m)
    {
        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        Validation::isSubintervalsMultiple($points, $m);
    }

    /**
     * @return array
     */
    public function dataProviderForSubintervalsNotMultiple(): array
    {
        return [
            [
                [[0,0], [1,1]],
                2
            ],
            [
                [[0,0], [1,1], [2,2]],
                3
            ],
            [
                [[0,2], [2,4], [4,6], [6,8]],
                2
            ],
            [
                [[1,0], [4,1], [7,2], [10,3], [13,4], [16,5]],
                9
            ],
        ];
    }
}
