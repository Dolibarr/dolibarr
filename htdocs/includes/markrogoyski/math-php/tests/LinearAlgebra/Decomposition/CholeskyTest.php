<?php

namespace MathPHP\Tests\LinearAlgebra\Decomposition;

use MathPHP\LinearAlgebra\MatrixFactory;
use MathPHP\Exception;

class CholeskyTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @testCase     choleskyDecomposition computes the expected lower triangular matrix
     * @dataProvider dataProviderForCholeskyDecomposition
     * @param        array $A
     * @param        array $expected_L
     * @throws       \Exception
     */
    public function testCholeskyDecomposition(array $A, array $expected_L)
    {
        // Given
        $A           = MatrixFactory::create($A);
        $expected_L  = MatrixFactory::create($expected_L);
        $expected_Lᵀ = $expected_L->transpose();

        // When
        $cholesky = $A->choleskyDecomposition();
        $L        = $cholesky->L;
        $Lᵀ       = $cholesky->LT;

        // Then
        $this->assertEqualsWithDelta($expected_L->getMatrix(), $L->getMatrix(), 0.00001);
        $this->assertEqualsWithDelta($expected_Lᵀ->getMatrix(), $Lᵀ->getMatrix(), 0.00001);

        // And LLᵀ = A
        $LLᵀ = $L->multiply($Lᵀ);
        $this->assertEquals($A->getMatrix(), $LLᵀ->getMatrix());
    }

    /**
     * @return array
     */
    public function dataProviderForCholeskyDecomposition(): array
    {
        return [
            // Example data from Wikipedia
            [
                [
                    [4, 12, -16],
                    [12, 37, -43],
                    [-16, -43, 98],
                ],
                [
                    [2, 0, 0],
                    [6, 1, 0],
                    [-8, 5, 3],
                ],
            ],
            // Example data from rosettacode.org
            [
                [
                    [25, 15, -5],
                    [15, 18,  0],
                    [-5,  0, 11],
                ],
                [
                    [5, 0, 0],
                    [3, 3, 0],
                    [-1, 1, 3],
                ],
            ],
            [
                [
                    [18, 22,  54,  42],
                    [22, 70,  86,  62],
                    [54, 86, 174, 134],
                    [42, 62, 134, 106],
                ],
                [
                    [4.24264,  0.00000, 0.00000, 0.00000],
                    [5.18545,  6.56591, 0.00000, 0.00000],
                    [12.72792, 3.04604, 1.64974, 0.00000],
                    [9.89949,  1.62455, 1.84971, 1.39262],
                ],
            ],
            // Example data from https://ece.uwaterloo.ca/~dwharder/NumericalAnalysis/04LinearAlgebra/cholesky/
            [
                [
                    [5, 1.2, 0.3, -0.6],
                    [1.2, 6, -0.4, 0.9],
                    [0.3, -0.4, 8, 1.7],
                    [-0.6, 0.9, 1.7, 10],
                ],
                [
                    [2.236067977499790, 0, 0, 0],
                    [0.536656314599949,   2.389979079406345, 0, 0],
                    [0.134164078649987,  -0.197491268466351,   2.818332343581848, 0],
                    [-0.268328157299975,   0.436823907370487,   0.646577012719190,  3.052723872310221],
                ],
            ],
            [
                [
                    [9.0000,  0.6000,  -0.3000,   1.5000],
                    [0.6000,  16.0400,  1.1800,  -1.5000],
                    [-0.3000, 1.1800,   4.1000,  -0.5700],
                    [1.5000, -1.5000,  -0.5700,  25.4500],
                ],
                [
                    [3, 0, 0, 0],
                    [0.2, 4, 0, 0],
                    [-0.1, 0.3, 2, 0],
                    [0.5, -0.4, -0.2, 5],
                ],
            ],
            // Example data created with http://calculator.vhex.net/post/calculator-result/cholesky-decomposition
            [
                [
                    [2, -1],
                    [-1, 2],
                ],
                [
                    [1.414214, 0],
                    [-0.707107, 1.224745],
                ],
            ],
            [
                [
                    [1, -1],
                    [-1, 4],
                ],
                [
                    [1, 0],
                    [-1, 1.732051],
                ],
            ],
            [
                [
                    [6, 4],
                    [4, 5],
                ],
                [
                    [2.44949, 0],
                    [1.632993, 1.527525],
                ],
            ],
            [
                [
                    [4, 1, -1],
                    [1, 2, 1],
                    [-1, 1, 2],
                ],
                [
                    [2, 0, 0],
                    [0.5, 1.322876, 0],
                    [-0.5, 0.944911, 0.92582],
                ],
            ],
            [
                [
                    [9, -3, 3, 9],
                    [-3, 17, -1, -7],
                    [3, -1, 17, 15],
                    [9, -7, 15, 44],
                ],
                [
                    [3, 0, 0, 0],
                    [-1, 4, 0, 0],
                    [1, 0, 4, 0],
                    [3, -1, 3, 5],
                ],
            ],
        ];
    }

    /**
     * @test   choleskyDecomposition throws a MatrixException if the matrix is not positive definite
     * @throws \Exception
     */
    public function testCholeskyDecompositionException()
    {
        // Given
        $A = [
            [1, 2, 3],
            [2, 3, 4],
            [3, 4, 5],
        ];
        $A = MatrixFactory::create($A);

        // Then
        $this->expectException(Exception\MatrixException::class);

        // When
        $L = $A->choleskyDecomposition();
    }

    /**
     * @test   Cholesky Decomposition invalid property
     * @throws \Exception
     */
    public function testCholeskyDecompositionInvalidProperty()
    {
        // Given
        $A = MatrixFactory::create([
            [4, 1, -1],
            [1, 2, 1],
            [-1, 1, 2],
        ]);
        $cholesky = $A->choleskyDecomposition();

        // Then
        $this->expectException(Exception\MathException::class);

        // When
        $doesNotExist = $cholesky->doesNotExist;
    }
}
