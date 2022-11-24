<?php

namespace MathPHP\Tests\Util;

use MathPHP\Util\Iter;

class IterZipIteratorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test         zip with two iterators of the same size
     * @dataProvider dataProviderForZipTwoIteratorsSameSize
     * @param        \Iterator $iter1
     * @param        \Iterator $iter2
     * @param        array     $expected
     */
    public function testZipTwoIteratorSameSize(\Iterator $iter1, \Iterator $iter2, array $expected)
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
    public function dataProviderForZipTwoIteratorsSameSize(): array
    {
        return [
            [
                new ArrayIteratorFixture([]),
                new ArrayIteratorFixture([]),
                [],
            ],
            [
                new ArrayIteratorFixture([1]),
                new ArrayIteratorFixture([2]),
                [[1, 2]],
            ],
            [
                new ArrayIteratorFixture([1, 2]),
                new ArrayIteratorFixture([4, 5]),
                [[1, 4], [2, 5]],
            ],
            [
                new ArrayIteratorFixture([1, 2, 3]),
                new ArrayIteratorFixture([4, 5, 6]),
                [[1, 4], [2, 5], [3, 6]],
            ],
            [
                new ArrayIteratorFixture([1, 2, 3, 4, 5, 6, 7, 8, 9]),
                new ArrayIteratorFixture([4, 5, 6, 7, 8, 9, 1, 2, 3]),
                [[1, 4], [2, 5], [3, 6], [4, 7], [5, 8], [6, 9], [7, 1], [8, 2], [9, 3]],
            ],
        ];
    }

    /**
     * @test         zip with two iterators of the different sizes
     * @dataProvider dataProviderForZipTwoIteratorsDifferentSize
     * @param        \Iterator $iter1
     * @param        \Iterator $iter2
     * @param        array     $expected
     */
    public function testZipTwoIteatorsDifferentSize(\Iterator $iter1, \Iterator $iter2, array $expected)
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
    public function dataProviderForZipTwoIteratorsDifferentSize(): array
    {
        return [
            [
                new ArrayIteratorFixture([1]),
                    new ArrayIteratorFixture([]),
                [],
            ],
            [
                new ArrayIteratorFixture([]),
                    new ArrayIteratorFixture([2]),
                [],
            ],
            [
                new ArrayIteratorFixture([1, 2]),
                new ArrayIteratorFixture([4]),
                [[1, 4]],
            ],
            [
                new ArrayIteratorFixture([1]),
                new ArrayIteratorFixture([4, 5]),
                [[1, 4]],
            ],
            [
                new ArrayIteratorFixture([1, 2, 3]),
                new ArrayIteratorFixture([4, 5]),
                [[1, 4], [2, 5]],
            ],
            [
                new ArrayIteratorFixture([1, 2]),
                new ArrayIteratorFixture([4, 5, 6]),
                [[1, 4], [2, 5]],
            ],
            [
                new ArrayIteratorFixture([1, 2, 3]),
                new ArrayIteratorFixture([4]),
                [[1, 4]],
            ],
            [
                new ArrayIteratorFixture([1]),
                new ArrayIteratorFixture([4, 5, 6]),
                [[1, 4]],
            ],
        ];
    }
}
