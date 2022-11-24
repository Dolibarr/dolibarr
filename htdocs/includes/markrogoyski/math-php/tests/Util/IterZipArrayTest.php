<?php

namespace MathPHP\Tests\Util;

use MathPHP\Util\Iter;

class IterZipArrayTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test         zip with two arrays of the same size
     * @dataProvider dataProviderForZipTwoArraysSameSize
     * @param        array $array1
     * @param        array $array2
     * @param        array $expected
     */
    public function testZipTwoArraysSameSize(array $array1, array $array2, array $expected)
    {
        // Given
        $result = [];

        // When
        foreach (Iter::zip($array1, $array2) as [$value1, $value2]) {
            $result[] = [$value1, $value2];
        }

        // Then
        $this->assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function dataProviderForZipTwoArraysSameSize(): array
    {
        return [
            [
                [],
                [],
                [],
            ],
            [
                [1],
                [2],
                [[1, 2]],
            ],
            [
                [1, 2],
                [4, 5],
                [[1, 4], [2, 5]],
            ],
            [
                [1, 2, 3],
                [4, 5, 6],
                [[1, 4], [2, 5], [3, 6]],
            ],
            [
                [1, 2, 3, 4, 5, 6, 7, 8, 9],
                [4, 5, 6, 7, 8, 9, 1, 2, 3],
                [[1, 4], [2, 5], [3, 6], [4, 7], [5, 8], [6, 9], [7, 1], [8, 2], [9, 3]],
            ],
        ];
    }

    /**
     * @test         zip with two arrays of the different sizes
     * @dataProvider dataProviderForZipTwoArraysDifferentSize
     * @param        array $array1
     * @param        array $array2
     * @param        array $expected
     */
    public function testZipTwoArraysDifferentSize(array $array1, array $array2, array $expected)
    {
        // Given
        $result = [];

        foreach (Iter::zip($array1, $array2) as [$value1, $value2]) {
            $result[] = [$value1, $value2];
        }

        // Then
        $this->assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function dataProviderForZipTwoArraysDifferentSize(): array
    {
        return [
            [
                [1],
                [],
                [],
            ],
            [
                [],
                [2],
                [],
            ],
            [
                [1, 2],
                [4],
                [[1, 4]],
            ],
            [
                [1],
                [4, 5],
                [[1, 4]],
            ],
            [
                [1, 2, 3],
                [4, 5],
                [[1, 4], [2, 5]],
            ],
            [
                [1, 2],
                [4, 5, 6],
                [[1, 4], [2, 5]],
            ],
            [
                [1, 2, 3],
                [4],
                [[1, 4]],
            ],
            [
                [1],
                [4, 5, 6],
                [[1, 4]],
            ],
        ];
    }

    /**
     * @test         zip with three arrays of the same size
     * @dataProvider dataProviderForZipThreeArraysSameSize
     * @param        array $array1
     * @param        array $array2
     * @param        array $array3
     * @param        array $expected
     */
    public function testZipThreeArraysSameSize(array $array1, array $array2, array $array3, array $expected)
    {
        // Given
        $result = [];

        // When
        foreach (Iter::zip($array1, $array2, $array3) as [$value1, $value2, $value3]) {
            $result[] = [$value1, $value2, $value3];
        }

        // Then
        $this->assertEquals($expected, $result);
    }

    /**
     * @test         zip with three arrays of the same size - unpacking
     * @dataProvider dataProviderForZipThreeArraysSameSize
     * @param        array $array1
     * @param        array $array2
     * @param        array $array3
     * @param        array $expected
     */
    public function testZipThreeArraysSameSizeUsingUnpacking(array $array1, array $array2, array $array3, array $expected)
    {
        // Given
        $result = [];

        // When
        foreach (Iter::zip(...[$array1, $array2, $array3]) as [$value1, $value2, $value3]) {
            $result[] = [$value1, $value2, $value3];
        }

        // Then
        $this->assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function dataProviderForZipThreeArraysSameSize(): array
    {
        return [
            [
                [],
                [],
                [],
                [],
            ],
            [
                [1],
                [2],
                [3],
                [[1, 2, 3]],
            ],
            [
                [1, 2],
                [4, 5],
                [7, 8],
                [[1, 4, 7], [2, 5, 8]],
            ],
            [
                [1, 2, 3],
                [4, 5, 6],
                [7, 8, 9],
                [[1, 4, 7], [2, 5, 8], [3, 6, 9]],
            ],
            [
                [1, 2, 3, 4, 5, 6, 7, 8, 9],
                [4, 5, 6, 7, 8, 9, 1, 2, 3],
                [7, 8, 9, 1, 2, 3, 4, 5, 6],
                [[1, 4, 7], [2, 5, 8], [3, 6, 9], [4, 7, 1], [5, 8, 2], [6, 9, 3], [7, 1, 4], [8, 2, 5], [9, 3, 6]],
            ],
        ];
    }

    /**
     * @test         zip with three arrays of different size
     * @dataProvider dataProviderForZipThreeArraysDifferentSize
     * @param        array $array1
     * @param        array $array2
     * @param        array $array3
     * @param        array $expected
     */
    public function testZipThreeArraysDifferentSize(array $array1, array $array2, array $array3, array $expected)
    {
        // Given
        $result = [];

        // When
        foreach (Iter::zip($array1, $array2, $array3) as [$value1, $value2, $value3]) {
            $result[] = [$value1, $value2, $value3];
        }

        // Then
        $this->assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function dataProviderForZipThreeArraysDifferentSize(): array
    {
        return [
            [
                [],
                [1],
                [2, 3],
                [],
            ],
            [
                [1],
                [2, 3],
                [4, 5, 6],
                [[1, 2, 4]],
            ],
            [
                [1, 2],
                [4, 5, 7, 8],
                [9, 1, 2, 3, 4, 5, 6],
                [[1, 4, 9], [2, 5, 1]],
            ],
            [
                [1, 2, 3, 4],
                [4, 5, 6],
                [7, 8, 9, 0],
                [[1, 4, 7], [2, 5, 8], [3, 6, 9]],
            ],
        ];
    }
}
