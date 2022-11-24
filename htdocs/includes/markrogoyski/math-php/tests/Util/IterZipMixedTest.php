<?php

namespace MathPHP\Tests\Util;

use MathPHP\Util\Iter;

class IterZipMixedTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test         zip with three different iterables of the same size
     * @dataProvider dataProviderForZipThreeIterablesSameSize
     * @param        array        $array
     * @param        \Iterator    $iter
     * @param        \Traversable $traversable
     * @param        array        $expected
     */
    public function testZipThreeIterablesSameSize(array $array, \Iterator $iter, \Traversable $traversable, array $expected)
    {
        // Given
        $result = [];

        // When
        foreach (Iter::zip($array, $iter, $traversable) as [$value1, $value2, $value3]) {
            $result[] = [$value1, $value2, $value3];
        }

        // Then
        $this->assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function dataProviderForZipThreeIterablesSameSize(): array
    {
        return [
            [
                [],
                new ArrayIteratorFixture([]),
                new IteratorAggregateFixture([]),
                [],
            ],
            [
                [1],
                new ArrayIteratorFixture([2]),
                new IteratorAggregateFixture([3]),
                [[1, 2, 3]],
            ],
            [
                [1, 2],
                new ArrayIteratorFixture([3, 4]),
                new IteratorAggregateFixture([5, 6]),
                [[1, 3, 5], [2, 4, 6]],
            ],
            [
                [1, 2, 3],
                new ArrayIteratorFixture([4, 5, 6]),
                new IteratorAggregateFixture([7, 8, 9]),
                [[1, 4, 7], [2, 5, 8], [3, 6, 9]],
            ],
            [
                [1, 2, 3, 4, 5, 6, 7, 8, 9],
                new ArrayIteratorFixture([4, 5, 6, 7, 8, 9, 1, 2, 3]),
                new IteratorAggregateFixture([0, 9, 8, 7, 6, 5, 4, 3, 2]),
                [[1, 4, 0], [2, 5, 9], [3, 6, 8], [4, 7, 7], [5, 8, 6], [6, 9, 5], [7, 1, 4], [8, 2, 3], [9, 3, 2]],
            ],
        ];
    }

    /**
     * @test         zip with three different iterables of differentSizes
     * @dataProvider dataProviderForZipThreeIterablesDifferentSize
     * @param        array        $array
     * @param        \Iterator    $iter
     * @param        \Traversable $traversable
     * @param        array        $expected
     */
    public function testZipThreeIterablesDifferentSize(array $array, \Iterator $iter, \Traversable $traversable, array $expected)
    {
        // Given
        $result = [];

        // When
        foreach (Iter::zip($array, $iter, $traversable) as [$value1, $value2, $value3]) {
            $result[] = [$value1, $value2, $value3];
        }

        // Then
        $this->assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function dataProviderForZipThreeIterablesDifferentSize(): array
    {
        return [
            [
                [],
                new ArrayIteratorFixture([1]),
                new IteratorAggregateFixture([1, 2]),
                [],
            ],
            [
                [1],
                new ArrayIteratorFixture([2, 2]),
                new IteratorAggregateFixture([3, 3, 3]),
                [[1, 2, 3]],
            ],
            [
                [1, 2, 3],
                new ArrayIteratorFixture([3, 4]),
                new IteratorAggregateFixture([5, 6, 7]),
                [[1, 3, 5], [2, 4, 6]],
            ],
            [
                [1, 2, 3],
                new ArrayIteratorFixture([4, 5, 6]),
                new IteratorAggregateFixture([7]),
                [[1, 4, 7]],
            ],
            [
                [1, 2, 3, 4, 5, 6, 7, 8, 9],
                new ArrayIteratorFixture([4, 5, 6, 7, 8, 9, 1, 2]),
                new IteratorAggregateFixture([0, 9, 8, 7, 6, 5, 4, 3, 2]),
                [[1, 4, 0], [2, 5, 9], [3, 6, 8], [4, 7, 7], [5, 8, 6], [6, 9, 5], [7, 1, 4], [8, 2, 3]],
            ],
        ];
    }
}
