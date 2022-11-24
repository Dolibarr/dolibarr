<?php

namespace MathPHP\Tests\Util;

use MathPHP\Util\Iter;

class IterZipTraversableTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test         zip with two traversable objects of the same size
     * @dataProvider dataProviderForZipTwoTraversableSameSize
     * @param        \Traversable $iter1
     * @param        \Traversable $iter2
     * @param        array     $expected
     */
    public function testZipTwoIteratorSameSize(\Traversable $iter1, \Traversable $iter2, array $expected)
    {
        // Given
        $result = [];

        // When
        foreach (Iter::zip($iter1, $iter2) as [$value1, $value2]) {
            $result[] = [$value1, $value2];
        }

        // Then
        $this->assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function dataProviderForZipTwoTraversableSameSize(): array
    {
        return [
            [
                new IteratorAggregateFixture([]),
                new IteratorAggregateFixture([]),
                [],
            ],
            [
                new IteratorAggregateFixture([1]),
                new IteratorAggregateFixture([2]),
                [[1, 2]],
            ],
            [
                new IteratorAggregateFixture([1, 2]),
                new IteratorAggregateFixture([4, 5]),
                [[1, 4], [2, 5]],
            ],
            [
                new IteratorAggregateFixture([1, 2, 3]),
                new IteratorAggregateFixture([4, 5, 6]),
                [[1, 4], [2, 5], [3, 6]],
            ],
            [
                new IteratorAggregateFixture([1, 2, 3, 4, 5, 6, 7, 8, 9]),
                new IteratorAggregateFixture([4, 5, 6, 7, 8, 9, 1, 2, 3]),
                [[1, 4], [2, 5], [3, 6], [4, 7], [5, 8], [6, 9], [7, 1], [8, 2], [9, 3]],
            ],
        ];
    }

    /**
     * @test         zip with two traversable objects of the different sizes
     * @dataProvider dataProviderForZipTwoTraversableDifferentSize
     * @param        \Traversable $iter1
     * @param        \Traversable $iter2
     * @param        array     $expected
     */
    public function testZipTwoTraversableDifferentSize(\Traversable $iter1, \Traversable $iter2, array $expected)
    {
        // Given
        $result = [];

        foreach (Iter::zip($iter1, $iter2) as [$value1, $value2]) {
            $result[] = [$value1, $value2];
        }

        // Then
        $this->assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function dataProviderForZipTwoTraversableDifferentSize(): array
    {
        return [
            [
                new IteratorAggregateFixture([1]),
                new IteratorAggregateFixture([]),
                [],
            ],
            [
                new IteratorAggregateFixture([]),
                new IteratorAggregateFixture([2]),
                [],
            ],
            [
                new IteratorAggregateFixture([1, 2]),
                new IteratorAggregateFixture([4]),
                [[1, 4]],
            ],
            [
                new IteratorAggregateFixture([1]),
                new IteratorAggregateFixture([4, 5]),
                [[1, 4]],
            ],
            [
                new IteratorAggregateFixture([1, 2, 3]),
                new IteratorAggregateFixture([4, 5]),
                [[1, 4], [2, 5]],
            ],
            [
                new IteratorAggregateFixture([1, 2]),
                new IteratorAggregateFixture([4, 5, 6]),
                [[1, 4], [2, 5]],
            ],
            [
                new IteratorAggregateFixture([1, 2, 3]),
                new IteratorAggregateFixture([4]),
                [[1, 4]],
            ],
            [
                new IteratorAggregateFixture([1]),
                new IteratorAggregateFixture([4, 5, 6]),
                [[1, 4]],
            ],
        ];
    }
}
