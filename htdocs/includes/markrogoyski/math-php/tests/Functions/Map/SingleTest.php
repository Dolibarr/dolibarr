<?php

namespace MathPHP\Tests\Functions\Map;

use MathPHP\Functions\Map\Single;
use MathPHP\Exception;

class SingleTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test         square
     * @dataProvider dataProviderForSquare
     * @param        array $xs
     * @param        array $expected
     */
    public function testSquare(array $xs, array $expected)
    {
        // When
        $squares = Single::square($xs);

        // Then
        $this->assertEquals($expected, $squares);
    }

    public function dataProviderForSquare(): array
    {
        return [
            [
                [1, 2, 3, 4],
                [1, 4, 9, 16],
            ],
            [
                [7, 8, 9, 10],
                [49, 64, 81, 100],
            ],
        ];
    }

    /**
     * @test         cube
     * @dataProvider dataProviderForCube
     * @param        array $xs
     * @param        array $expected
     */
    public function testCube(array $xs, array $expected)
    {
        // When
        $cubes = Single::cube($xs);

        // Then
        $this->assertEquals($expected, $cubes);
    }

    public function dataProviderForCube(): array
    {
        return [
            [
                [1, 2, 3, 4],
                [1, 8, 27, 64],
            ],
            [
                [7, 8, 9, 10],
                [343, 512, 729, 1000],
            ],
        ];
    }

    /**
     * @test         pow
     * @dataProvider dataProviderForPow
     * @param        array $xs
     * @param        int   $n
     * @param        array $expected
     */
    public function testPow(array $xs, int $n, array $expected)
    {
        // When
        $pows = Single::pow($xs, $n);

        // Then
        $this->assertEquals($expected, $pows);
    }

    public function dataProviderForPow(): array
    {
        return [
            [
                [1, 2, 3, 4], 5,
                [1, 32, 243, 1024],
            ],
            [
                [7, 8, 9, 10], 4,
                [2401, 4096, 6561, 10000],
            ],
        ];
    }

    /**
     * @test         equals
     * @dataProvider dataProviderForSqrt
     * @param        array $xs
     * @param        array $expected
     */
    public function testSqrt(array $xs, array $expected)
    {
        // When
        $sqrts = Single::sqrt($xs);

        // Then
        $this->assertEquals($expected, $sqrts);
    }

    public function dataProviderForSqrt(): array
    {
        return [
            [
                [4, 9, 16, 25],
                [2, 3, 4, 5],
            ],
            [
                [64, 81, 100, 144],
                [8, 9, 10, 12],
            ],
        ];
    }

    /**
     * @test         equals
     * @dataProvider dataProviderForAbs
     * @param        array $xs
     * @param        array $expected
     */
    public function testAbs(array $xs, array $expected)
    {
        // When
        $abs = Single::abs($xs);

        // Then
        $this->assertEquals($expected, $abs);
    }

    public function dataProviderForAbs(): array
    {
        return [
            [
                [1, 2, 3, 4],
                [1, 2, 3, 4],
            ],
            [
                [1, -2, 3, -4],
                [1, 2, 3, 4],
            ],
            [
                [-1, -2, -3, -4],
                [1, 2, 3, 4],
            ],
        ];
    }

    /**
     * @test         add
     * @dataProvider dataProviderForAdd
     * @param        array $xs
     * @param        mixed $k
     * @param        array $expected
     */
    public function testAdd(array $xs, $k, array $expected)
    {
        // When
        $sums = Single::add($xs, $k);

        // Then
        $this->assertEquals($expected, $sums);
    }

    public function dataProviderForAdd(): array
    {
        return [
            [ [1, 2, 3, 4, 5], 4, [5, 6, 7, 8, 9] ],
            [ [5, 7, 23, 5, 2], 9.1, [14.1, 16.1, 32.1, 14.1, 11.1] ],
        ];
    }

    /**
     * @test         subtract
     * @dataProvider dataProviderForSubtract
     * @param        array $xs
     * @param        int   $k
     * @param        array $expected
     */
    public function testSubtract(array $xs, int $k, array $expected)
    {
        // When
        $differences = Single::subtract($xs, $k);

        // Then
        $this->assertEquals($expected, $differences);
    }

    public function dataProviderForSubtract(): array
    {
        return [
            [ [1, 2, 3, 4, 5], 1, [0, 1, 2, 3, 4] ],
            [ [5, 7, 23, 5, 2], 3, [2, 4, 20, 2, -1] ],
        ];
    }

    /**
     * @test         multiply
     * @dataProvider dataProviderForMultiply
     * @param        array $xs
     * @param        int   $k
     * @param        array $expected
     */
    public function testMultiply(array $xs, int $k, array $expected)
    {
        // When
        $products = Single::multiply($xs, $k);

        // Then
        $this->assertEquals($expected, $products);
    }

    public function dataProviderForMultiply(): array
    {
        return [
            [ [1, 2, 3, 4, 5], 4, [4, 8, 12, 16, 20] ],
            [ [5, 7, 23, 5, 2], 3, [15, 21, 69, 15, 6] ],
        ];
    }

    /**
     * @test         multiply
     * @dataProvider dataProviderForDivide
     * @param        array $xs
     * @param        int   $k
     * @param        array $expected
     */
    public function testDivide(array $xs, int $k, array $expected)
    {
        // When
        $quotients = Single::divide($xs, $k);

        // Then
        $this->assertEquals($expected, $quotients);
    }

    public function dataProviderForDivide(): array
    {
        return [
            [ [1, 2, 3, 4, 5], 2, [0.5, 1, 1.5, 2, 2.5] ],
            [ [5, 10, 15, 20, 25], 5, [1, 2, 3, 4, 5] ],
        ];
    }

    /**
     * @test         max
     * @dataProvider dataProviderForMax
     * @param        array $xs
     * @param        mixed $value
     * @param        array $expected
     */
    public function testMax(array $xs, $value, array $expected)
    {
        // When
        $maxes = Single::max($xs, $value);

        // Then
        $this->assertEquals($expected, $maxes);
    }

    public function dataProviderForMax(): array
    {
        return [
            [[1, 2, 3, 4, 5], 0, [1, 2, 3, 4, 5]],
            [[1, 2, 3, 4, 5], 1, [1, 2, 3, 4, 5]],
            [[1, 2, 3, 4, 5], 3, [3, 3, 3, 4, 5]],
            [[1, 2, 3, 4, 5], 6, [6, 6, 6, 6, 6]],
            [[1, 2, 3, 4, 5], 9, [9, 9, 9, 9, 9]],
            [[1, 2, 3, 4, 5], 3.4, [3.4, 3.4, 3.4, 4, 5]],
            [[1, 2, 3, 4, 5], 6.7, [6.7, 6.7, 6.7, 6.7, 6.7]],
        ];
    }

    /**
     * @test         min
     * @dataProvider dataProviderForMin
     * @param        array $xs
     * @param        mixed $value
     * @param        array $expected
     */
    public function testMin(array $xs, $value, array $expected)
    {
        // When
        $mins = Single::min($xs, $value);

        // Then
        $this->assertEquals($expected, $mins);
    }

    public function dataProviderForMin(): array
    {
        return [
            [[1, 2, 3, 4, 5], 0, [0, 0, 0, 0, 0]],
            [[1, 2, 3, 4, 5], 1, [1, 1, 1, 1, 1]],
            [[1, 2, 3, 4, 5], 3, [1, 2, 3, 3, 3]],
            [[1, 2, 3, 4, 5], 6, [1, 2, 3, 4, 5]],
            [[1, 2, 3, 4, 5], 9, [1, 2, 3, 4, 5]],
            [[1, 2, 3, 4, 5], 3.4, [1, 2, 3, 3.4, 3.4]],
            [[1, 2, 3, 4, 5], 6.7, [1, 2, 3, 4, 5]],
        ];
    }

    /**
     * @test         reciprocal
     * @dataProvider dataProviderForReciprocal
     * @param        array $xs
     * @param        array $expectedReciprocals
     * @throws       \Exception
     */
    public function testReciprocal(array $xs, array $expectedReciprocals)
    {
        // When
        $reciprocals = Single::reciprocal($xs);

        // Then
        $this->assertEquals($expectedReciprocals, $reciprocals);
    }

    /**
     * @return array
     */
    public function dataProviderForReciprocal(): array
    {
        return [
            [
                [1, 2, 3, 4],
                [1 / 1, 1 / 2, 1 / 3, 1 / 4],
            ],
            [
                [7, 8, 9, 10],
                [1 / 7, 1 / 8, 1 / 9, 1 / 10],
            ],
            [
                [-2, -1, 1.1, 2.5, 6.73],
                [-1 / 2, -1 / 1, 1 / 1.1, 1 / 2.5, 1 / 6.73],
            ]
        ];
    }

    /**
     * @test   reciprocal when there are zeros
     * @throws Exception\BadDataException
     */
    public function testReciprocalWithZeros()
    {
        // Given
        $xs = [1, 2, 0, 3, 0];

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        Single::reciprocal($xs);
    }
}
