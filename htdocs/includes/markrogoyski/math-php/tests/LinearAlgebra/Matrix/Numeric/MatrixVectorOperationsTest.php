<?php

namespace MathPHP\Tests\LinearAlgebra\Matrix\Numeric;

use MathPHP\LinearAlgebra\MatrixFactory;
use MathPHP\LinearAlgebra\Vector;
use MathPHP\Exception;

class MatrixVectorOperationsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider dataProviderForVectorMultiply
     */
    public function testVectorMultiply(array $A, array $B, array $R)
    {
        // Given
        $A  = MatrixFactory::create($A);
        $B  = new Vector($B);
        $R  = new Vector($R);

        // When
        $R2 = $A->vectorMultiply($B);

        // Then
        $this->assertEquals($R, $R2);
    }

    public function dataProviderForVectorMultiply(): array
    {
        return [
            [
                [
                    [1],
                ],
                [1],
                [1],
            ],
            [
                [
                    [2],
                ],
                [3],
                [6],
            ],
            [
                [
                    [1, 2],
                    [2, 3],
                ],
                [4, 5],
                [14, 23]
            ],
            [
                [
                    [1, 2, 3],
                    [2, 3, 4],
                    [3, 4, 5],
                ],
                [1, 2, 3],
                [14, 20, 26],
            ],
            [
                [
                    [3, 6, 5],
                    [1, 7, 5],
                    [2, 3, 2],
                ],
                [1, 5, 4],
                [53, 56, 25],
            ],
            [
                [
                    [1, 1, 1],
                    [2, 2, 2],
                ],
                [1, 2, 3],
                [6, 12],
            ],
            [
                [
                    [1, 1, 1],
                    [2, 2, 2],
                    [3, 3, 3],
                    [4, 4, 4]
                ],
                [1, 2, 3],
                [6, 12, 18, 24],
            ],
        ];
    }

    public function testVectorMultiplyExceptionDimensionsDoNotMatch()
    {
        // Given
        $A = MatrixFactory::create([
            [1, 2, 3],
            [2, 3, 4],
        ]);
        $B = new Vector([1, 2, 3, 4, 5]);

        // Then
        $this->expectException(Exception\MatrixException::class);

        // When
        $A->vectorMultiply($B);
    }

    /**
     * @test         rowSums
     * @dataProvider dataProviderForRowSums
     * @param        array $A
     * @param        array $expected
     * @throws       \Exception
     */
    public function testRowSums(array $A, array $expected)
    {
        // Given
        $A        = MatrixFactory::create($A);
        $expected = new Vector($expected);

        // When
        $R = $A->rowSums();

        // Then
        $this->assertEquals($expected, $R);
    }

    /**
     * Computed using R rowSums(A)
     * @return array
     */
    public function dataProviderForRowSums(): array
    {
        return [
            [
                [
                    [4, -1, 3],
                    [1, 3, 5],
                ],
                [6, 9],
            ],
            [
                [
                    [1, 4, 7, 8],
                    [2, 2, 8, 4],
                    [1, 13, 1, 5],
                ],
                [20, 16, 20],
            ],
            [
                [
                    [19, 22, 6, 3, 2, 20],
                    [12, 6, 9, 15, 13, 5],
                ],
                [72, 60],
            ],
            [
                [
                    [1, 5, 2, 6, 7, 3],
                    [3, 11, 6, 8, 15, 11],
                ],
                [24, 54],
            ],
            [
                [
                    [4, 4.2, 3.9, 4.3, 4.1],
                    [2, 2.1, 2, 2.1, 2.2],
                    [.6, .59, .58, .62, .63]
                ],
                [20.50, 10.40, 3.02],
            ],
        ];
    }

    /**
     * @test         rowMeans
     * @dataProvider dataProviderForRowMeans
     * @param        array $A
     * @param        array $expected
     * @throws       \Exception
     */
    public function testRowMeans(array $A, array $expected)
    {
        // Given
        $A        = MatrixFactory::create($A);
        $expected = new Vector($expected);

        // When
        $R = $A->rowMeans();

        // Then
        $this->assertEquals($expected, $R);
    }

    /**
     * @return array
     */
    public function dataProviderForRowMeans(): array
    {
        return [
            // Test data from: http://www.maths.manchester.ac.uk/~mkt/MT3732%20(MVA)/Intro.pdf
            [
                [
                    [4, -1, 3],
                    [1, 3, 5],
                ],
                [2, 3],
            ],
            // Test data from Linear Algebra and Its Aplications (Lay)
            [
                [
                    [1, 4, 7, 8],
                    [2, 2, 8, 4],
                    [1, 13, 1, 5],
                ],
                [5, 4, 5],
            ],
            [
                [
                    [19, 22, 6, 3, 2, 20],
                    [12, 6, 9, 15, 13, 5],
                ],
                [12, 10],
            ],
            [
                [
                    [1, 5, 2, 6, 7, 3],
                    [3, 11, 6, 8, 15, 11],
                ],
                [4, 9],
            ],
            // Test data from: http://www.itl.nist.gov/div898/handbook/pmc/section5/pmc541.htm
            [
                [
                    [4, 4.2, 3.9, 4.3, 4.1],
                    [2, 2.1, 2, 2.1, 2.2],
                    [.6, .59, .58, .62, .63]
                ],
                [4.10, 2.08, 0.604],
            ],
        ];
    }

    /**
     * @test         columnSums
     * @dataProvider dataProviderForColumnSums
     * @param        array $A
     * @param        array $expected
     * @throws       \Exception
     */
    public function testColumnSums(array $A, array $expected)
    {
        // Given
        $A        = MatrixFactory::create($A);
        $expected = new Vector($expected);

        // When
        $R = $A->columnSums();

        // Then
        $this->assertEqualsWithDelta($expected, $R, 0.000001);
    }

    /**
     * Computed using R colSums(A)
     * @return array
     */
    public function dataProviderForColumnSums(): array
    {
        return [
            [
                [
                    [4, -1, 3],
                    [1, 3, 5],
                ],
                [5, 2, 8],
            ],
            [
                [
                    [1, 4, 7, 8],
                    [2, 2, 8, 4],
                    [1, 13, 1, 5],
                ],
                [4, 19, 16, 17],
            ],
            [
                [
                    [19, 22, 6, 3, 2, 20],
                    [12, 6, 9, 15, 13, 5],
                ],
                [31, 28, 15, 18, 15, 25],
            ],
            [
                [
                    [1, 5, 2, 6, 7, 3],
                    [3, 11, 6, 8, 15, 11],
                ],
                [4, 16, 8, 14, 22, 14],
            ],
            [
                [
                    [4, 4.2, 3.9, 4.3, 4.1],
                    [2, 2.1, 2, 2.1, 2.2],
                    [.6, .59, .58, .62, .63]
                ],
                [6.60, 6.89, 6.48, 7.02, 6.93],
            ]
        ];
    }

    /**
     * @test         columnMeans
     * @dataProvider dataProviderForColumnMeans
     * @param        array $A
     * @param        array $expected
     * @throws       \Exception
     */
    public function testColumnMeans(array $A, array $expected)
    {
        // Given
        $A        = MatrixFactory::create($A);
        $expected = new Vector($expected);

        // When
        $R = $A->columnMeans();

        // Then
        $this->assertEqualsWithDelta($expected, $R, 0.000001);
    }

    /**
     * Computed using R colMeans(A)
     * @return array
     */
    public function dataProviderForColumnMeans(): array
    {
        return [
            [
                [
                    [4, -1, 3],
                    [1, 3, 5],
                ],
                [2.5, 1.0, 4.0],
            ],
            [
                [
                    [1, 4, 7, 8],
                    [2, 2, 8, 4],
                    [1, 13, 1, 5],
                ],
                [1.333333, 6.333333, 5.333333, 5.666667],
            ],
            [
                [
                    [19, 22, 6, 3, 2, 20],
                    [12, 6, 9, 15, 13, 5],
                ],
                [15.5, 14.0,  7.5,  9.0,  7.5, 12.5],
            ],
            [
                [
                    [1, 5, 2, 6, 7, 3],
                    [3, 11, 6, 8, 15, 11],
                ],
                [2, 8, 4, 7, 11, 7],
            ],
            [
                [
                    [4, 4.2, 3.9, 4.3, 4.1],
                    [2, 2.1, 2, 2.1, 2.2],
                    [.6, .59, .58, .62, .63]
                ],
                [2.200000, 2.296667, 2.160000, 2.340000, 2.310000],
            ]
        ];
    }
}
