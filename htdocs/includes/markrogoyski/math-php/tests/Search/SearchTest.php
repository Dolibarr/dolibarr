<?php

namespace MathPHP\Tests\Search;

use MathPHP\Exception;
use MathPHP\Search;

class SearchTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test         sorted
     * @dataProvider dataProviderForSearchSorted
     * @param        array $haystack
     * @param        float $needle
     * @param        int   $expected
     */
    public function testSearchSorted(array $haystack, float $needle, int $expected)
    {
        // When
        $insertionPoint = Search::sorted($haystack, $needle);

        // Then
        $this->assertSame($expected, $insertionPoint);
    }

    /**
     * Test data created with Python NumPy searchsorted
     * @return array (haystack, needle, expected)
     */
    public function dataProviderForSearchSorted(): array
    {
        return [
            [[1, 2, 3, 4, 5], -100, 0],
            [[1, 2, 3, 4, 5], -1, 0],
            [[1, 2, 3, 4, 5], 0, 0],
            [[1, 2, 3, 4, 5], 1, 0],
            [[1, 2, 3, 4, 5], 2, 1],
            [[1, 2, 3, 4, 5], 3, 2],
            [[1, 2, 3, 4, 5], 4, 3],
            [[1, 2, 3, 4, 5], 5, 4],
            [[1, 2, 3, 4, 5], 6, 5],
            [[1, 2, 3, 4, 5], 7, 5],
            [[1, 2, 3, 4, 5], 100, 5],

            [[-8, -5, -1, 3, 6, 10], -10, 0],
            [[-8, -5, -1, 3, 6, 10], -9, 0],
            [[-8, -5, -1, 3, 6, 10], -8, 0],
            [[-8, -5, -1, 3, 6, 10], -7, 1],
            [[-8, -5, -1, 3, 6, 10], -6, 1],
            [[-8, -5, -1, 3, 6, 10], -5, 1],
            [[-8, -5, -1, 3, 6, 10], -4, 2],
            [[-8, -5, -1, 3, 6, 10], -3, 2],
            [[-8, -5, -1, 3, 6, 10], -2, 2],
            [[-8, -5, -1, 3, 6, 10], -1, 2],
            [[-8, -5, -1, 3, 6, 10], 0, 3],
            [[-8, -5, -1, 3, 6, 10], 1, 3],
            [[-8, -5, -1, 3, 6, 10], 2, 3],
            [[-8, -5, -1, 3, 6, 10], 3, 3],
            [[-8, -5, -1, 3, 6, 10], 4, 4],
            [[-8, -5, -1, 3, 6, 10], 5, 4],
            [[-8, -5, -1, 3, 6, 10], 6, 4],
            [[-8, -5, -1, 3, 6, 10], 7, 5],
            [[-8, -5, -1, 3, 6, 10], 8, 5],
            [[-8, -5, -1, 3, 6, 10], 9, 5],
            [[-8, -5, -1, 3, 6, 10], 10, 5],
            [[-8, -5, -1, 3, 6, 10], 11, 6],
            [[-8, -5, -1, 3, 6, 10], 12, 6],
            [[-8, -5, -1, 3, 6, 10], 100, 6],

            [[1.1, 2.2, 3.3, 4.4, 5.5], -1.4, 0],
            [[1.1, 2.2, 3.3, 4.4, 5.5], -0.1, 0],
            [[1.1, 2.2, 3.3, 4.4, 5.5], 0, 0],
            [[1.1, 2.2, 3.3, 4.4, 5.5], 1.045, 0],
            [[1.1, 2.2, 3.3, 4.4, 5.5], 1.2, 1],
            [[1.1, 2.2, 3.3, 4.4, 5.5], 2, 1],
            [[1.1, 2.2, 3.3, 4.4, 5.5], 2.5, 2],
            [[1.1, 2.2, 3.3, 4.4, 5.5], 3.2, 2],
            [[1.1, 2.2, 3.3, 4.4, 5.5], 3.8, 3],
            [[1.1, 2.2, 3.3, 4.4, 5.5], 4.3, 3],
            [[1.1, 2.2, 3.3, 4.4, 5.5], 4.5, 4],
            [[1.1, 2.2, 3.3, 4.4, 5.5], 5.4, 4],
            [[1.1, 2.2, 3.3, 4.4, 5.5], 5.55, 5],
            [[1.1, 2.2, 3.3, 4.4, 5.5], 20.9, 5],

            [[4, 4.14285714, 4.28571429, 4.42857143, 4.57142857, 4.71428571, 4.85714286, 5, 5.14285714, 5.28571429, 5.42857143, 5.57142857, 5.71428571, 5.85714286, 6, 6.14285714, 6.28571429, 6.42857143, 6.57142857, 6.71428571, 6.85714286, 7], 6.2, 16],
        ];
    }

    /**
     * @test sorted on empty list
     */
    public function testSearchSortedEmptyList()
    {
        // Given
        $haystack = [];
        $needle   = 5;

        // When
        $insertionPoint = Search::sorted($haystack, $needle);

        // Then
        $this->assertSame(0, $insertionPoint);
    }

    /**
     * @test         argMax
     * @dataProvider dataProviderForArgMax
     * @dataProvider dataProviderForArgMaxWithNans
     * @param        array $values
     * @param        int   $expected
     */
    public function testArgMax(array $values, int $expected)
    {
        // When
        $indexOfMax = Search::argMax($values);

        // Then
        $this->assertSame($expected, $indexOfMax);
    }

    /**
     * Test data created with Python NumPy argmax
     * @return array
     */
    public function dataProviderForArgMax(): array
    {
        return [
            [[-100], 0],
            [[-1], 0],
            [[0], 0],
            [[1], 0],
            [[2], 0],
            [[100], 0],

            [[-100.43], 0],
            [[-1.3], 0],
            [[0.0], 0],
            [[0.0000003829], 0],
            [[1.5], 0],
            [[2.2], 0],
            [[100.4089], 0],

            [[0, 1], 1],
            [[1, 2], 1],
            [[3, 6], 1],
            [[94, 95], 1],
            [[9384935, 900980398049], 1],

            [[0.0, 0.1], 1],
            [[0.00004, 0.00005], 1],
            [[39.34, 39.35], 1],
            [[-4, -3], 1],
            [[-0.00001, 0], 1],

            [[-1, -1], 0],
            [[0, 0], 0],
            [[1, 1], 0],
            [[1.7, 1.7], 0],
            [[34535, 34535], 0],

            [[5, 1, 2, 3, 4], 0],
            [[1, 5, 2, 3, 4], 1],
            [[1, 2, 5, 3, 4], 2],
            [[1, 2, 3, 5, 4], 3],
            [[1, 2, 3, 4, 5], 4],

            [[5, 1, 2, 3, 5], 0],
            [[1, 5, 2, 5, 4], 1],
            [[1, 5, 5, 3, 4], 1],
            [[1, 2, 5, 5, 4], 2],
            [[1, 2, 3, 5, 5], 3],

            [[1.1, 1.2, 1.3, 1.4, 1.5], 4],
            [[92830482039, 980983209480923, 823094802943, \INF], 3],
        ];
    }

    /**
     * Test data created with Python NumPy argmax
     * @return array
     */
    public function dataProviderForArgMaxWithNans(): array
    {
        return [
            [[0, 1, 2, 3, \NAN], 4],
            [[0, 1, 2, \NAN, 3], 3],
            [[0, 1, \NAN, 2, 3], 2],
            [[0, \NAN, 1, 2, 3], 1],
            [[\NAN, 0, 1, 2, 3], 0],
            [[\NAN, 0, \NAN, 1, 2, 3], 0],

            [[\NAN, \INF], 0],
            [[\INF, \NAN], 1],
        ];
    }

    /**
     * @test argMax error when the input array is empty
     */
    public function testArgMaxErrorOnEmptyArray()
    {
        // Given
        $values = [];

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        $index = Search::argMax($values);
    }

    /**
     * @test         nanArgMax
     * @dataProvider dataProviderForArgMax
     * @dataProvider dataProviderForNanArgMaxWithNans
     * @param        array $values
     * @param        int   $expected
     */
    public function testNanArgMax(array $values, int $expected)
    {
        // When
        $indexOfMax = Search::nanArgMax($values);

        // Then
        $this->assertSame($expected, $indexOfMax);
    }

    /**
     * Test data created with Python NumPy nanargmax
     * @return array
     */
    public function dataProviderForNanArgMaxWithNans(): array
    {
        return [
            [[0, 1, 2, 3, \NAN], 3],
            [[0, 1, 2, \NAN, 3], 4],
            [[0, 1, \NAN, 2, 3], 4],
            [[0, \NAN, 1, 2, 3], 4],
            [[\NAN, 0, 1, 2, 3], 4],
            [[\NAN, 0, \NAN, 1, 2, 3], 5],

            [[\NAN, \INF], 1],
            [[\INF, \NAN], 0],
        ];
    }

    /**
     * @test nanArgMax error when the input array is empty
     */
    public function testNanArgMaxErrorOnEmptyArray()
    {
        // Given
        $values = [];

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        $index = Search::nanArgMax($values);
    }

    /**
     * @test nanArgMax error when the input array is empty
     */
    public function testNanArgMaxErrorOnArrayOfAllNans()
    {
        // Given
        $values = [\NAN, \NAN, \NAN];

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        $index = Search::nanArgMax($values);
    }

    /**
     * @test         argMin
     * @dataProvider dataProviderForArgMin
     * @dataProvider dataProviderForArgMinWithNans
     * @param        array $values
     * @param        int   $expected
     */
    public function testArgMin(array $values, int $expected)
    {
        // When
        $indexOfMax = Search::argMin($values);

        // Then
        $this->assertSame($expected, $indexOfMax);
    }

    /**
     * Test data created with Python NumPy argmax
     * @return array
     */
    public function dataProviderForArgMin(): array
    {
        return [
            [[-100], 0],
            [[-1], 0],
            [[0], 0],
            [[1], 0],
            [[2], 0],
            [[100], 0],

            [[-100.43], 0],
            [[-1.3], 0],
            [[0.0], 0],
            [[0.0000003829], 0],
            [[1.5], 0],
            [[2.2], 0],
            [[100.4089], 0],

            [[0, 1], 0],
            [[1, 2], 0],
            [[3, 6], 0],
            [[94, 95], 0],
            [[9384935, 900980398049], 0],

            [[0.0, 0.1], 0],
            [[0.00004, 0.00005], 0],
            [[39.34, 39.35], 0],
            [[-4, -3], 0],
            [[-0.00001, 0], 0],

            [[-1, -1], 0],
            [[0, 0], 0],
            [[1, 1], 0],
            [[1.7, 1.7], 0],
            [[34535, 34535], 0],

            [[1, 2, 3, 4, 5], 0],
            [[2, 1, 3, 4, 5], 1],
            [[3, 4, 2, 5, 6], 2],
            [[2, 3, 4, 1, 5], 3],
            [[2, 3, 4, 5, 1], 4],

            [[1, 2, 3, 4, 1], 0],
            [[2, 1, 3, 4, 1], 1],
            [[3, 4, 2, 1, 5], 3],
            [[2, 3, 4, 1, 1], 3],
            [[2, 3, 4, 5, 1], 4],

            [[1.1, 1.2, 1.3, 1.4, 1.5], 0],
            [[-92830482039, -980983209480923, -823094802943, -\INF], 3],
        ];
    }

    /**
     * Test data created with Python NumPy argmax
     * @return array
     */
    public function dataProviderForArgMinWithNans(): array
    {
        return [
            [[0, 1, 2, 3, \NAN], 4],
            [[0, 1, 2, \NAN, 3], 3],
            [[0, 1, \NAN, 2, 3], 2],
            [[0, \NAN, 1, 2, 3], 1],
            [[\NAN, 0, 1, 2, 3], 0],
            [[\NAN, 0, \NAN, 1, 2, 3], 0],

            [[\NAN, -\INF], 0],
            [[-\INF, \NAN], 1],
        ];
    }

    /**
     * @test argMin error when the input array is empty
     */
    public function testArgMinErrorOnEmptyArray()
    {
        // Given
        $values = [];

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        $index = Search::argMin($values);
    }

    /**
     * @test         nanArgMin
     * @dataProvider dataProviderForArgMin
     * @dataProvider dataProviderForNanArgMinWithNans
     * @param        array $values
     * @param        int   $expected
     */
    public function testNanArgMin(array $values, int $expected)
    {
        // When
        $indexOfMax = Search::nanArgMin($values);

        // Then
        $this->assertSame($expected, $indexOfMax);
    }

    /**
     * Test data created with Python NumPy nanargmin
     * @return array
     */
    public function dataProviderForNanArgMinWithNans(): array
    {
        return [
            [[0, 1, 2, 3, \NAN], 0],
            [[0, 1, 2, \NAN, 3], 0],
            [[0, 1, \NAN, 2, 3], 0],
            [[0, \NAN, 1, 2, 3], 0],
            [[\NAN, 0, 1, 2, 3], 1],
            [[\NAN, 0, \NAN, 1, 2, 3], 1],

            [[\NAN, -\INF], 1],
            [[-\INF, \NAN], 0],
        ];
    }

    /**
     * @test nanArgMin error when the input array is empty
     */
    public function testNanArgMinErrorOnEmptyArray()
    {
        // Given
        $values = [];

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        $index = Search::nanArgMin($values);
    }

    /**
     * @test nanArgMin error when the input array is empty
     */
    public function testNanArgMinErrorOnArrayOfAllNans()
    {
        // Given
        $values = [\NAN, \NAN, \NAN];

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        $index = Search::nanArgMin($values);
    }

    /**
     * @test         nonZero
     * @dataProvider dataProviderForNonZero
     * @param        array $values
     * @param        array $expected
     */
    public function testNonZero(array $values, array $expected)
    {
        // When
        $indices = Search::nonZero($values);

        // Then
        $this->assertEquals($expected, $indices);
    }

    /**
     * @return array (values, expected)
     */
    public function dataProviderForNonZero(): array
    {
        return [
            [
                [],
                [],
            ],
            [
                [0],
                [],
            ],
            [
                [0, 0, 0],
                [],
            ],
            [
                [0.0],
                [],
            ],
            [
                [0.0],
                [],
            ],
            [
                [0, -0, 0.0, -0.0, '0', '-0', '0.0', '-0.0', false],
                [],
            ],
            [
                [1],
                [0],
            ],
            [
                [-1],
                [0],
            ],
            [
                [1],
                [0],
            ],
            [
                [1.1],
                [0],
            ],
            [
                [-1.1],
                [0],
            ],
            [
                [98273492837],
                [0],
            ],
            [
                [-90273402738049],
                [0],
            ],
            [
                [0.0000005],
                [0],
            ],
            [
                [-0.0000005],
                [0],
            ],
            [
                [\INF],
                [0],
            ],
            [
                [-\INF],
                [0],
            ],
            [
                [\NAN],
                [0],
            ],
            [
                [1, 2, 3],
                [0, 1, 2],
            ],
            [
                [1, -1, 0.1, -0.1, 83928, -8939823, \INF, \NAN],
                [0, 1, 2, 3, 4, 5, 6, 7],
            ],
            [
                [0, 1, 0, 1, 0],
                [1, 3],
            ],
            [
                [0, 0, 0, 0, 1, 1, 1, 1],
                [4, 5, 6, 7],
            ],
            [
                [1, 1, 1, 1, 0, 0, 0, 0],
                [0, 1, 2, 3],
            ],
            [
                [0.0, -0.0, 0.000000000000001, -0.000000000000001],
                [2, 3],
            ],
            [
                ['0', '1', '0.0'],
                [1],
            ],
            [
                [true, false, true, false],
                [0, 2],
            ],
            [
                [1, 2, [], [], new \stdClass(), new \stdClass(), 0, 1, 2],
                [0, 1, 7, 8],
            ],
        ];
    }
}
