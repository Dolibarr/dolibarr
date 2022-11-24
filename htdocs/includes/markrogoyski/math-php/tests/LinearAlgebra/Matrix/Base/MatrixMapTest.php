<?php

namespace MathPHP\Tests\LinearAlgebra\Matrix\Base;

use MathPHP\LinearAlgebra\MatrixFactory;
use MathPHP\LinearAlgebra\Vector;

class MatrixMapTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test         map with a callable
     * @dataProvider dataProviderForMapCallable
     * @param        array    $A
     * @param        callable $func
     * @param        array    $expected
     * @throws       \Exception
     */
    public function testMapWithCallable(array $A, callable $func, array $expected)
    {
        // Given
        $A        = MatrixFactory::create($A);
        $expected = MatrixFactory::create($expected);

        // When
        $R = $A->map($func);

        // Then
        $this->assertEquals($expected, $R);
    }

    /**
     * @return array (input, func, output)
     */
    public function dataProviderForMapCallable(): array
    {
        return [
            'abs' => [
                [
                    [1, -2, 3],
                    [-4, 5, -6],
                    [-7, -8, 9],
                ],
                'abs',
                [
                    [1, 2, 3],
                    [4, 5, 6],
                    [7, 8, 9],
                ]
            ],
            'round' => [
                [
                    [1.1, 2.2, 3.3],
                    [4.4, 5.5, 6.6],
                    [7.7, 8.8, 9.9],
                ],
                'round',
                [
                    [1, 2, 3],
                    [4, 6, 7],
                    [8, 9, 10],
                ]
            ],
            'sqrt' => [
                [
                    [1, 4, 9],
                    [16, 25, 36],
                    [49, 64, 81],
                ],
                'sqrt',
                [
                    [1, 2, 3],
                    [4, 5, 6],
                    [7, 8, 9],
                ]
            ],
        ];
    }

    /**
     * @test         map with a closure
     * @dataProvider dataProviderForMapClosure
     * @param        array    $A
     * @param        \Closure $func
     * @param        array    $expected
     * @throws       \Exception
     */
    public function testMapWithClosure(array $A, \Closure $func, array $expected)
    {
        // Given
        $A        = MatrixFactory::create($A);
        $expected = MatrixFactory::create($expected);

        // When
        $R = $A->map($func);

        // Then
        $this->assertEquals($expected, $R);
    }

    /**
     * @return array (input, func, output)
     */
    public function dataProviderForMapClosure(): array
    {
        return [
            'doubler' => [
                [
                    [1, 2, 3],
                    [4, 5, 6],
                    [7, 8, 9],
                ],
                function ($x) {
                    return $x * 2;
                },
                [
                    [2, 4, 6],
                    [8, 10, 12],
                    [14, 16, 18],
                ]
            ],
            'add one' => [
                [
                    [1, 2, 3],
                    [4, 5, 6],
                    [7, 8, 9],
                ],
                function ($x) {
                    return $x + 1;
                },
                [
                    [2, 3, 4],
                    [5, 6, 7],
                    [8, 9, 10],
                ]
            ],
        ];
    }

    /**
     * @test         applyRows with a callable
     * @dataProvider dataProviderForApplyRowsCallable
     * @param        array    $A
     * @param        callable $func
     * @param        array    $expected
     * @throws       \Exception
     */
    public function testApplyRowsWithCallable(array $A, callable $func, array $expected)
    {
        // Given
        $A = MatrixFactory::create($A);

        // When
        $R = $A->mapRows($func);

        // Then
        $this->assertEquals($expected, $R);
    }

    /**
     * @return array (input, func, output)
     */
    public function dataProviderForApplyRowsCallable(): array
    {
        return [
            [
                [
                    [1, 2, 3],
                    [4, 5, 6],
                    [7, 8, 9],
                ],
                'array_reverse',
                [
                    [3, 2, 1],
                    [6, 5, 4],
                    [9, 8, 7],
                ]
            ],
            [
                [
                    [1, 2, 3],
                    [4, 5, 6],
                    [7, 8, 9],
                ],
                'array_sum',
                [
                    6,
                    15,
                    24,
                ]
            ],
            [
                [
                    [1, 2, 3],
                    [4, 5, 6],
                    [7, 8, 9],
                ],
                'array_product',
                [
                    6,
                    120,
                    504,
                ]
            ],
            [
                [
                    [1, 2, 3],
                    [4, 0, 6],
                    [0, 0, 9],
                ],
                'array_filter',
                [
                    [1, 2, 3],
                    [0 => 4, 2 => 6],
                    [2 => 9],
                ]
            ],
            [
                [
                    [1, 2, 3],
                    [4, 5, 6],
                    [7, 8, 9],
                ],
                'array_flip',
                [
                    [1 => 0, 2 => 1, 3 => 2],
                    [4 => 0, 5 => 1, 6 => 2],
                    [7 => 0, 8 => 1, 9 => 2],
                ]
            ],
            [
                [
                    [1, 2, 3],
                    [4, 5, 6],
                    [7, 8, 9],
                ],
                'array_keys',
                [
                    [0, 1, 2],
                    [0, 1, 2],
                    [0, 1, 2],
                ]
            ],
            [
                [
                    [1, 1, 3],
                    [4, 6, 6],
                    [7, 7, 7],
                ],
                'array_unique',
                [
                    [1, 2 => 3],
                    [4, 1 => 6],
                    [0 => 7],
                ]
            ],
            [
                [
                    [3, 2, 1],
                    [6, 5, 4],
                    [9, 8, 7],

                ],
                'count',
                [
                    3,
                    3,
                    3,
                ]
            ],
            [
                [
                    [1, 2, 3],
                    [4, 5, 6],
                    [7, 8, 9],
                ],
                'min',
                [
                    1,
                    4,
                    7,
                ]
            ],
            [
                [
                    [1, 2, 3],
                    [4, 5, 6],
                    [7, 8, 9],
                ],
                'max',
                [
                    3,
                    6,
                    9,
                ]
            ],
            [
                [
                    [1, 2, 3, 1, 2, 3],
                    [4, 5, 6, 6, 6, 6],
                    [7, 8, 8, 9, 9, 9],
                ],
                'array_count_values',
                [
                    [1 => 2, 2 => 2, 3 => 2],
                    [4 => 1, 5 => 1, 6 => 4],
                    [7 => 1, 8 => 2, 9 => 3],
                ]
            ],
        ];
    }

    /**
     * @test         applyRows with a closure
     * @dataProvider dataProviderForApplyRowsClosure
     * @param        array    $A
     * @param        \Closure $func
     * @param        array    $expected
     * @throws       \Exception
     */
    public function testApplyRowsWithClosure(array $A, \Closure $func, array $expected)
    {
        // Given
        $A = MatrixFactory::create($A);

        // When
        $R = $A->mapRows($func);

        // Then
        $this->assertEquals($expected, $R);
    }

    /**
     * @return array (input, func, output)
     * @throws \Exception
     */
    public function dataProviderForApplyRowsClosure(): array
    {
        return [
            'add one' => [
                [
                    [1, 2, 3],
                    [4, 5, 6],
                    [7, 8, 9],
                ],
                function (array $row) {
                    return \array_sum($row) + 1;
                },
                [
                    7,
                    16,
                    25,
                ]
            ],
            'sort' => [
                [
                    [3, 1, 2],
                    [4, 6, 5],
                    [7, 8, 9],
                ],
                function (array $row) {
                    sort($row);
                    return $row;
                },
                [
                    [1, 2, 3],
                    [4, 5, 6],
                    [7, 8, 9],
                ],
            ],
            'remove first and last' => [
                [
                    [1, 2, 3],
                    [4, 5, 6],
                    [7, 8, 9],
                ],
                function (array $row) {
                    \array_shift($row);
                    \array_pop($row);
                    return $row;
                },
                [
                    [2],
                    [5],
                    [8],
                ],
            ],
            'something strange with reduce' => [
                [
                    [1, 2, 3],
                    [4, 5, 6],
                    [7, 8, 9],
                ],
                function (array $row) {
                    return \array_reduce(
                        $row,
                        function ($carry, $item) {
                            return $carry * $carry + $item;
                        },
                        1
                    );
                },
                [
                    39,
                    906,
                    5193,
                ]
            ],
            'merge' => [
                [
                    [1, 2, 3],
                    [4, 5, 6],
                    [7, 8, 9],
                ],
                function (array $row) {
                    return \array_merge($row, [9, 9, 9]);
                },
                [
                    [1, 2, 3, 9, 9, 9],
                    [4, 5, 6, 9, 9, 9],
                    [7, 8, 9, 9, 9, 9],
                ],
            ],
            'chunk' => [
                [
                    [1, 2, 3],
                    [4, 5, 6],
                    [7, 8, 9],
                ],
                function (array $row) {
                    return array_chunk($row, 1);
                },
                [
                    [[1], [2], [3]],
                    [[4], [5], [6]],
                    [[7], [8], [9]],
                ],
            ],
            'vectors' => [
                [
                    [1, 2, 3],
                    [4, 5, 6],
                    [7, 8, 9],
                ],
                function (array $row) {
                    return new Vector($row);
                },
                [
                    new Vector([1, 2, 3]),
                    new Vector([4, 5, 6]),
                    new Vector([7, 8, 9]),
                ],
            ],
        ];
    }

    /**
     * @test   applyRows with shuffle closure
     * @throws \Exception
     */
    public function testApplyRowsClosureShuffle()
    {
        // Given
        $A = MatrixFactory::create([
            [1, 2, 3],
            [4, 5, 6],
            [7, 8, 9],
        ]);
        $func = function (array $row) {
            shuffle($row);
            return $row;
        };

        // When
        $R = $A->mapRows($func);

        // Then
        $this->assertTrue(\in_array(1, $R[0]));
        $this->assertTrue(\in_array(2, $R[0]));
        $this->assertTrue(\in_array(3, $R[0]));
        $this->assertTrue(\in_array(4, $R[1]));
        $this->assertTrue(\in_array(5, $R[1]));
        $this->assertTrue(\in_array(6, $R[1]));
        $this->assertTrue(\in_array(7, $R[2]));
        $this->assertTrue(\in_array(8, $R[2]));
        $this->assertTrue(\in_array(9, $R[2]));
    }

    /**
     * @test walk
     */
    public function testWalk()
    {
        // Given
        $A = MatrixFactory::create([
            [1, 2, 3],
            [4, 5, 6],
            [7, 8, 9],
        ]);

        // Then
        $func = function ($item) {
            $this->assertTrue(\is_int($item));
            $this->assertFalse(\is_float($item));
        };

        // When
        $A->walk($func);
    }
}
