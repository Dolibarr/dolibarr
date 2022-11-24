<?php

namespace MathPHP\Tests\Functions\Map;

use MathPHP\Functions\Map\Multi;
use MathPHP\Exception;

class MultiTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test         add two arrays
     * @dataProvider dataProviderForAddTwoArrays
     * @param        array $xs
     * @param        array $ys
     * @param        array $expected
     * @throws       \Exception
     */
    public function testAddTwoArrays(array $xs, array $ys, array $expected)
    {
        // When
        $sums = Multi::add($xs, $ys);

        // Then
        $this->assertEquals($expected, $sums);
    }

    public function dataProviderForAddTwoArrays(): array
    {
        return [
            [
                [1, 2, 3, 4],
                [2, 3, 4, 5],
                [3, 5, 7, 9],
            ],
            [
                [1, 2, 3, 4],
                [6, 6, 6, 6],
                [7, 8, 9, 10],
            ],
        ];
    }

    /**
     * @test         add multiple arrays
     * @dataProvider dataProviderForAddMulti
     * @param        array $expected
     * @param        array ...$arrays
     * @throws       \Exception
     */
    public function testAddMulti(array $expected, array ...$arrays)
    {
        // When
        $sums = Multi::add(...$arrays);

        // Then
        $this->assertEquals($expected, $sums);
    }

    public function dataProviderForAddMulti(): array
    {
        return [
            [
                [3, 5, 7, 9],
                [1, 2, 3, 4],
                [2, 3, 4, 5],
            ],
            [
                [7, 8, 9, 10],
                [1, 2, 3, 4],
                [6, 6, 6, 6],
            ],
            [
                [6, 7, 9, 10],
                [1, 2, 3, 4],
                [2, 2, 2, 2],
                [3, 3, 4, 4],
            ]
        ];
    }

    /**
     * @test         subtract two arrays
     * @dataProvider dataProviderForSubtractTwoArrays
     * @param        array $xs
     * @param        array $ys
     * @param        array $expected
     * @throws       \Exception
     */
    public function testSubtractTwoArrays(array $xs, array $ys, array $expected)
    {
        // When
        $differences = Multi::subtract($xs, $ys);

        // Then
        $this->assertEquals($expected, $differences);
    }

    public function dataProviderForSubtractTwoArrays(): array
    {
        return [
            [
                [1, 2, 3, 4],
                [2, 3, 4, 5],
                [-1, -1, -1, -1],
            ],
            [
                [1, 2, 3, 4],
                [6, 6, 6, 6],
                [-5, -4, -3, -2],
            ],
        ];
    }

    /**
     * @test         subtract multiple arrays
     * @dataProvider dataProviderForSubtractMulti
     * @param        array $expected
     * @param        array[] $arrays
     * @throws       \Exception
     */
    public function testSubtractMulti(array $expected, array ...$arrays)
    {
        // When
        $differences = Multi::subtract(...$arrays);

        // Then
        $this->assertEquals($expected, $differences);
    }

    public function dataProviderForSubtractMulti(): array
    {
        return [
            [
                [-1, -1, -1, -1],
                [1, 2, 3, 4],
                [2, 3, 4, 5],
            ],
            [
                [-5, -4, -3, -2],
                [1, 2, 3, 4],
                [6, 6, 6, 6],
            ],
            [
                [3, 3, 4, 4],
                [6, 7, 9, 10],
                [1, 2, 3, 4],
                [2, 2, 2, 2],
            ]
        ];
    }

    /**
     * @test         multiply two arrays
     * @dataProvider dataProviderForMultiplyTwoArrays
     */
    public function testMultiplyTwoArrays(array $xs, array $ys, array $expected)
    {
        // When
        $products = Multi::multiply($xs, $ys);

        // Then
        $this->assertEquals($expected, $products);
    }

    public function dataProviderForMultiplyTwoArrays(): array
    {
        return [
            [
                [1, 2, 3, 4],
                [2, 3, 4, 5],
                [2, 6, 12, 20],
            ],
            [
                [1, 2, 3, 4],
                [6, 6, 6, 6],
                [6, 12, 18, 24],
            ],
        ];
    }

    /**
     * @test         multiply multiple arrays
     * @dataProvider dataProviderForMultiplyMulti
     */
    public function testMultiplyMulti(array $expected, array ...$arrays)
    {
        // When
        $products = Multi::multiply(...$arrays);

        // Then
        $this->assertEquals($expected, $products);
    }

    public function dataProviderForMultiplyMulti(): array
    {
        return [
            [
                [2, 6, 12, 20],
                [1, 2, 3, 4],
                [2, 3, 4, 5],
            ],
            [
                [6, 12, 18, 24],
                [1, 2, 3, 4],
                [6, 6, 6, 6],
            ],
            [
                [12, 28, 54, 80],
                [6, 7, 9, 10],
                [1, 2, 3, 4],
                [2, 2, 2, 2],
            ]
        ];
    }

    /**
     * @test         divide two arrays
     * @dataProvider dataProviderForDivideTwoArrays
     * @param        array $xs
     * @param        array $ys
     * @param        array $expected
     * @throws       \Exception
     */
    public function testDivideTwoArrays(array $xs, array $ys, array $expected)
    {
        // When
        $quotients = Multi::divide($xs, $ys);

        // Then
        $this->assertEquals($expected, $quotients);
    }

    public function dataProviderForDivideTwoArrays(): array
    {
        return [
            [
                [5, 10, 15, 20],
                [5, 5, 5, 5],
                [1, 2, 3, 4],
            ],
            [
                [5, 10, 15, 20],
                [2.5, 20, 3, 4],
                [2, 0.5, 5, 5],
            ],
        ];
    }

    /**
     * @test         divide multiple arrays
     * @dataProvider dataProviderForDivideMulti
     * @param        array $expected
     * @param        array[] $arrays
     * @throws       \Exception
     */
    public function testDivideMulti(array $expected, array ...$arrays)
    {
        // When
        $quotients = Multi::divide(...$arrays);

        // Then
        $this->assertEquals($expected, $quotients);
    }

    public function dataProviderForDivideMulti(): array
    {
        return [
            [
                [1, 2, 3, 4],
                [5, 10, 15, 20],
                [5, 5, 5, 5],
            ],
            [
                [2, 0.5, 5, 5],
                [5, 10, 15, 20],
                [2.5, 20, 3, 4],
            ],
            [
                [5, 20, 1, 8],
                [100, 80, 25, 64],
                [10, 2, 5, 2],
                [2, 2, 5, 4],
            ]
        ];
    }

    /**
     * @test         max two arrays
     * @dataProvider dataProviderForMaxTwoArrays
     * @param        array $xs
     * @param        array $ys
     * @param        array $expected
     * @throws       \Exception
     */
    public function testMaxTwoArrays(array $xs, array $ys, array $expected)
    {
        // When
        $maxes = Multi::max($xs, $ys);

        // Then
        $this->assertEquals($expected, $maxes);
    }

    public function dataProviderForMaxTwoArrays(): array
    {
        return [
            [
                [1, 5, 3, 6],
                [5, 5, 5, 5],
                [5, 5, 5, 6],
            ],
            [
                [5, 10, 15, 20],
                [2.5, 20, 3, 4],
                [5, 20, 15, 20],
            ],
        ];
    }

    /**
     * @test         max multiple arrays
     * @dataProvider dataProviderForMaxMulti
     * @param        array $expected
     * @param        array[] $arrays
     * @throws       \Exception
     */
    public function testMaxMulti(array $expected, array ...$arrays)
    {
        // When
        $maxes = Multi::max(...$arrays);

        // Then
        $this->assertEquals($expected, $maxes);
    }

    public function dataProviderForMaxMulti(): array
    {
        return [
            [
                [5, 10, 15, 20],
                [5, 10, 15, 20],
                [5, 5, 5, 5],
            ],
            [
                [5, 20, 15, 20],
                [5, 10, 15, 20],
                [2.5, 20, 3, 4],
            ],
            [
                [100, 80, 55, 664],
                [100, 80, 25, 64],
                [10, 2, 55, 2],
                [2, 2, 5, 664],
            ]
        ];
    }

    /**
     * @test         min
     * @dataProvider dataProviderForMin
     * @param        array $xs
     * @param        array $ys
     * @param        array $expected
     * @throws       \Exception
     */
    public function testMin(array $xs, array $ys, array $expected)
    {
        // When
        $mins = Multi::min($xs, $ys);

        // Then
        $this->assertEquals($expected, $mins);
    }

    public function dataProviderForMin(): array
    {
        return [
            [
                [1, 5, 3, 6],
                [5, 5, 5, 5],
                [1, 5, 3, 5],
            ],
            [
                [5, 10, 15, 20],
                [2.5, 20, 3, 4],
                [2.5, 10, 3, 4],
            ],
        ];
    }

    /**
     * @test         min multiple arrays
     * @dataProvider dataProviderForMinMulti
     * @param        array $expected
     * @param        array[] $arrays
     * @throws       \Exception
     */
    public function testMinMulti(array $expected, array ...$arrays)
    {
        // When
        $mins = Multi::min(...$arrays);

        // Then
        $this->assertEquals($expected, $mins);
    }

    public function dataProviderForMinMulti(): array
    {
        return [
            [
                [5, 5, 5, 5],
                [5, 10, 15, 20],
                [5, 5, 5, 5],
            ],
            [
                [2.5, 10, 3, 4],
                [5, 10, 15, 20],
                [2.5, 20, 3, 4],
            ],
            [
                [2, 2, 5, 2],
                [100, 80, 25, 64],
                [10, 2, 55, 2],
                [2, 2, 5, 664],
            ]
        ];
    }

    /**
     * @test   array lengths are not equal exception
     * @throws \Exception
     */
    public function testCheckArrayLengthsException()
    {
        // Given
        $xs = [1, 2, 3];
        $ys = [1, 2];

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        Multi::add($xs, $ys);
    }

    /**
     * @test   Only one array exception
     * @throws \Exception
     */
    public function testCheckArrayLengthsExceptionOnlyOneArray()
    {
        // Given
        $xs = [1, 2];

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        Multi::add($xs);
    }
}
