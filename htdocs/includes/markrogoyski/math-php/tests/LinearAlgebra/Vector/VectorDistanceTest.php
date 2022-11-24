<?php

namespace MathPHP\Tests\LinearAlgebra\Vector;

use MathPHP\Exception\BadDataException;
use MathPHP\LinearAlgebra\Vector;

class VectorDistanceTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test         minkowskiDistance exception when vectors don't have the same size
     * @dataProvider dataProviderForDifferentVectors
     * @param        array $A
     * @param        array $B
     */
    public function testMinkowskiDistanceExceptionDifferentVectors(array $A, array $B)
    {
        // Given
        $A = new Vector($A);
        $B = new Vector($B);

        // Then
        $this->expectException(BadDataException::class);

        //When
        $A->minkowskiDistance($B, 2);
    }

    /**
     * @return array
     */
    public function dataProviderForDifferentVectors(): array
    {
        return [
            [ [1, 2, 3], [0, 0]],
            [ [0, 0, 0], [3, 2]],
            [ [0, 0], [0, 0, 0]],
            [ [3, 4], [4, 3, 2]],
            [ [1, 1], [1, 1, 1]],
        ];
    }

    /**
     * @test         l1Distance
     * @dataProvider dataProviderForL1Distance
     * @param        array $A
     * @param        array $B
     * @param        float $expected
     */
    public function testL1Distance(array $A, array $B, float $expected)
    {
        // Given
        $A = new Vector($A);
        $B = new Vector($B);

        // When
        $distance1 = $A->l1Distance($B);
        $distance2 = $B->l1Distance($A);

        // Then
        $this->assertEqualsWithDelta($expected, $distance1, 0.0000000001);
        $this->assertEqualsWithDelta($expected, $distance2, 0.0000000001);
    }

    /**
     * @return array
     */
    public function dataProviderForL1Distance(): array
    {
        return [
            [
                [1, 0, 0],
                [0, 1, 0],
                2,
            ],
            [
                [1, 1, 0],
                [0, 1, 0],
                1,
            ],
            [
                [1, 2, 3],
                [0, 0, 0],
                6,
            ],
            [
                [0, 0, 0],
                [0, 0, 0],
                0,
            ],
            [
                [1, 1, 1],
                [1, 1, 1],
                0,
            ],
            [
                [56, 26, 83],
                [11, 82, 95],
                113,
            ],
        ];
    }

    /**
     * @test         euclidean distance
     * @dataProvider dataProviderForL2Distance
     * @param        array $A
     * @param        array $B
     * @param        float $expected
     */
    public function testL2Distance(array $A, array $B, float $expected)
    {
        // Given
        $A = new Vector($A);
        $B = new Vector($B);

        // When
        $distance1 = $A->l2Distance($B);
        $distance2 = $B->l2Distance($A);

        // Then
        $this->assertEqualsWithDelta($expected, $distance1, 0.0000000001);
        $this->assertEqualsWithDelta($expected, $distance2, 0.0000000001);
    }

    /**
     * @return array
     */
    public function dataProviderForL2Distance(): array
    {
        return [
            [
                [1, 0, 0],
                [0, 1, 0],
                1.4142135623730951,
            ],
            [
                [1, 1, 0],
                [0, 1, 0],
                1,
            ],
            [
                [1, 2, 3],
                [0, 0, 0],
                3.7416573867739413,
            ],
            [
                [0, 0, 0],
                [0, 0, 0],
                0,
            ],
            [
                [1, 1, 1],
                [1, 1, 1],
                0,
            ],
            [
                [56, 26, 83],
                [11, 82, 95],
                72.83543093852057,
            ],
        ];
    }

    /**
     * @test         minkowski distance
     * @dataProvider dataProviderForMinkowskiDistance
     * @param        array $A
     * @param        array $B
     * @param        int   $p
     * @param        float $expected
     */
    public function testMinkowskiDistance(array $A, array $B, int $p, float $expected)
    {
        // Given
        $A = new Vector($A);
        $B = new Vector($B);

        // When
        $distance1 = $A->minkowskiDistance($B, $p);
        $distance2 = $B->minkowskiDistance($A, $p);

        // Then
        $this->assertEqualsWithDelta($expected, $distance1, 0.0000000001);
        $this->assertEqualsWithDelta($expected, $distance2, 0.0000000001);
    }

    /**
     * @return array
     */
    public function dataProviderForMinkowskiDistance(): array
    {
        return [
            [
                [1, 0, 0],
                [0, 1, 0],
                1,
                2,
            ],
            [
                [1, 0, 0],
                [0, 1, 0],
                2,
                1.4142135623730951,
            ],
            [
                [1, 0, 0],
                [0, 1, 0],
                3,
                1.2599210498948732,
            ],
            [
                [1, 1, 0],
                [0, 1, 0],
                1,
                1,
            ],
            [
                [1, 1, 0],
                [0, 1, 0],
                2,
                1,
            ],
            [
                [1, 1, 0],
                [0, 1, 0],
                3,
                1,
            ],
            [
                [1, 2, 3],
                [0, 0, 0],
                1,
                6,
            ],
            [
                [1, 2, 3],
                [0, 0, 0],
                2,
                3.7416573867739413,
            ],
            [
                [1, 2, 3],
                [0, 0, 0],
                3,
                3.3019272488946263,
            ],
            [
                [0, 0, 0],
                [0, 0, 0],
                1,
                0,
            ],
            [
                [0, 0, 0],
                [0, 0, 0],
                2,
                0,
            ],
            [
                [0, 0, 0],
                [0, 0, 0],
                3,
                0,
            ],
            [
                [1, 1, 1],
                [1, 1, 1],
                1,
                0,
            ],
            [
                [1, 1, 1],
                [1, 1, 1],
                2,
                0,
            ],
            [
                [1, 1, 1],
                [1, 1, 1],
                3,
                0,
            ],
            [
                [56, 26, 83],
                [11, 82, 95],
                1,
                113,
            ],
            [
                [56, 26, 83],
                [11, 82, 95],
                2,
                72.83543093852057,
            ],
            [
                [56, 26, 83],
                [11, 82, 95],
                3,
                64.51064463863402,
            ],
        ];
    }
}
