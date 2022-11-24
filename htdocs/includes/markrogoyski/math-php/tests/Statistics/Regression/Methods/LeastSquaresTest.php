<?php

namespace MathPHP\Tests\Statistics\Regression;

use MathPHP\Statistics\Regression\Linear;
use MathPHP\Exception;

class LeastSquaresTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test         LeastSquares trait leastSquares method throws a BadDataException if degrees of freedom is ≤ 0
     *               That will happen if there are only one or two points being used to fit a regression line.
     * @dataProvider dataProviderForLeastSquaresDegreesOfFreedomBadDataException
     * @param        array  $points
     */
    public function testLeastSquaresDegreesOfFreedomBadDataException(array $points)
    {
        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        $regression = new Linear($points);
    }

    /**
     * @return array [points]
     */
    public function dataProviderForLeastSquaresDegreesOfFreedomBadDataException(): array
    {
        return [
            'zero_points' => [
                [],
            ],
            'one_point' => [
                [[1, 2]],
            ],
            'two_points' => [
                [[1, 2], [2, 3]],
            ]
        ];
    }
}
