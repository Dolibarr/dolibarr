<?php

namespace MathPHP\Tests\LinearAlgebra\Decomposition;

use MathPHP\LinearAlgebra\MatrixFactory;
use MathPHP\Exception;
use MathPHP\LinearAlgebra\Vector;
use MathPHP\Tests\LinearAlgebra\Fixture\MatrixDataProvider;

class LUTest extends \PHPUnit\Framework\TestCase
{
    use MatrixDataProvider;

    /**
     * @test         LU decomposition - expected values for L, U, and P
     * @dataProvider dataProviderForLUDecomposition
     * @param        array $A
     * @param        array $L
     * @param        array $U
     * @param        array $P
     * @throws       \Exception
     */
    public function testLUDecomposition(array $A, array $L, array $U, array $P)
    {
        // Given
        $A = MatrixFactory::create($A);
        $L = MatrixFactory::create($L);
        $U = MatrixFactory::create($U);
        $P = MatrixFactory::create($P);

        // When
        $LU = $A->luDecomposition();

        // Then
        $this->assertEqualsWithDelta($L, $LU->L, 0.001);
        $this->assertEqualsWithDelta($U, $LU->U, 0.001);
        $this->assertEqualsWithDelta($P, $LU->P, 0.001);
    }

    /**
     * @test         LU decomposition - PA = LU
     * @dataProvider dataProviderForLUDecomposition
     * @param        array $A
     * @throws       \Exception
     */
    public function testLUDecompositionPaEqualsLu(array $A)
    {
        // Given
        $A = MatrixFactory::create($A);

        // When
        $LU = $A->luDecomposition();

        // Then PA = LU;
        $PA = $LU->P->multiply($A);
        $LU = $LU->L->multiply($LU->U);
        $this->assertEqualsWithDelta($PA->getMatrix(), $LU->getMatrix(), 0.01);
    }

    /**
     * @test         LU decomposition - L and U properties
     * @dataProvider dataProviderForLUDecomposition
     * @param        array $A
     * @throws       \Exception
     */
    public function testLUDecompositionLAndUProperties(array $A)
    {
        // Given
        $A = MatrixFactory::create($A);

        // When
        $LU = $A->luDecomposition();

        // Then
        $this->assertTrue($LU->L->isLowerTriangular());
        $this->assertTrue($LU->U->isUpperTriangular());
    }

    /**
     * @test         Solve
     * @dataProvider dataProviderForSolve
     * @param        array $A
     * @param        array $b
     * @param        array $expected
     * @throws       \Exception
     */
    public function testSolve(array $A, array $b, array $expected)
    {
        // Given
        $A  = MatrixFactory::create($A);
        $LU = $A->luDecomposition();

        // And
        $expected = new Vector($expected);

        // When
        $x = $LU->solve($b);

        // Then
        $this->assertEqualsWithDelta($expected, $x, 0.00001);
    }

    /**
     * @test LU decomposition with small pivots
     *       (http://buzzard.ups.edu/courses/2014spring/420projects/math420-UPS-spring-2014-reid-LU-pivoting.pdf)
     *       Results computed with SciPy scipy.linalg.lu(A)
     * @throws \Exception
     */
    public function testLuDecompositionSmallPivots()
    {
        // Given
        $A = MatrixFactory::create([
            [10e-20, 1],
            [1, 2],
        ]);

        // And
        $L = MatrixFactory::create([
            [1, 0],
            [1e-19, 1],
        ]);
        $U = MatrixFactory::create([
            [1, 2],
            [0, 1],
        ]);
        $P = MatrixFactory::create([
            [0, 1],
            [1, 0],
        ]);

        // When
        $LU = $A->luDecomposition();

        // Then
        $this->assertEqualsWithDelta($L, $LU->L, 1e-20);
        $this->assertEqualsWithDelta($U, $LU->U, 1e-20);
        $this->assertEqualsWithDelta($P, $LU->P, 1e-20);

        // And
        $this->assertTrue($LU->L->isLowerTriangular());
        $this->assertTrue($LU->U->isUpperTriangular());

        // And PA = LU;
        $PA = $LU->P->multiply($A);
        $LU = $LU->L->multiply($LU->U);
        $this->assertEqualsWithDelta($PA->getMatrix(), $LU->getMatrix(), 0.01);
    }

    /**
     * Test data from various sources:
     *   SciPy scipy.linalg.lu(A)
     *   Online calculator: https://www.easycalculation.com/matrix/lu-decomposition-matrix.php
     *   Various other sources.
     * @return array (A, L, U, P)
     */
    public function dataProviderForLuDecomposition(): array
    {
        return [
            [
                [
                    [4, 3],
                    [6, 3],
                ],
                [
                    [1, 0],
                    [0.667, 1],
                ],
                [
                    [6, 3],
                    [0, 1],
                ],
                [
                    [0, 1],
                    [1, 0],
                ],
            ],
            // Matrix Computations 3.4 Pivoting example - pivoting prevents large entries in the triangular factors L and U
            [
                [
                    [.0001, 1],
                    [1, 1],
                ],
                [
                    [1, 0],
                    [0.0001, 1],
                ],
                [
                    [1, 1],
                    [0, 0.999],
                ],
                [
                    [0, 1],
                    [1, 0],
                ],
            ],
            // Zero at first pivot element would cause a divide by zero error without pivoting (http://buzzard.ups.edu/courses/2014spring/420projects/math420-UPS-spring-2014-reid-LU-pivoting.pdf)
            [
                [
                    [0, 1],
                    [1, 2],
                ],
                [
                    [1, 0],
                    [0, 1],
                ],
                [
                    [1, 2],
                    [0, 1],
                ],
                [
                    [0, 1],
                    [1, 0],
                ],
            ],
            // Small pivots
            [
                [
                    [10e-20, 1],
                    [1, 2],
                ],
                [
                    [1, 0],
                    [1e-19, 1],
                ],
                [
                    [1, 2],
                    [0, 1],
                ],
                [
                    [0, 1],
                    [1, 0],
                ],
            ],
            [
                [
                    [1, 3, 5],
                    [2, 4, 7],
                    [1, 1, 0],
                ],
                [
                    [1, 0, 0],
                    [.5, 1, 0],
                    [.5, -1, 1],
                ],
                [
                    [2, 4, 7],
                    [0, 1, 1.5],
                    [0, 0, -2],
                ],
                [
                    [0, 1, 0],
                    [1, 0, 0],
                    [0, 0, 1],
                ]
            ],
            [
                [
                    [1, -2, 3],
                    [2, -5, 12],
                    [0, 2, -10],
                ],
                [
                    [1, 0, 0],
                    [0, 1, 0],
                    [0.5, 0.25, 1],
                ],
                [
                    [2, -5, 12],
                    [0, 2, -10],
                    [0, 0, -0.5],
                ],
                [
                    [0, 1, 0],
                    [0, 0, 1],
                    [1, 0, 0],
                ],
            ],
            [
                [
                    [4, 2, 3],
                    [-3, 1, 4],
                    [2, 4, 5],
                ],
                [
                    [1, 0, 0],
                    [0.5, 1, 0],
                    [-0.75, 0.833, 1],
                ],
                [
                    [4, 2, 3],
                    [0, 3, 3.5],
                    [0, 0, 3.333]
                ],
                [
                    [1, 0, 0],
                    [0, 0, 1],
                    [0, 1, 0],
                ],
            ],
            // Partial pivoting example - (http://buzzard.ups.edu/courses/2014spring/420projects/math420-UPS-spring-2014-reid-LU-pivoting.pdf)
            [
                [
                    [1, 2, 4],
                    [2, 1, 3],
                    [3, 2, 4],
                ],
                [
                    [1, 0, 0],
                    [1 / 3, 1, 0],
                    [2 / 3, -1 / 4, 1],
                ],
                [
                    [3, 2, 4],
                    [0, 4 / 3, 8 / 3],
                    [0, 0, 1]
                ],
                [
                    [0, 0, 1],
                    [1, 0, 0],
                    [0, 1, 0],
                ],
            ],
            [
                [
                    [2, 3, 4],
                    [4, 7, 5],
                    [4, 9, 5],
                ],
                [
                    [1, 0, 0],
                    [1, 1, 0],
                    [0.5, -0.25, 1],
                ],
                [
                    [4, 7, 5],
                    [0, 2, 0],
                    [0, 0, 1.5]
                ],
                [
                    [0, 1, 0],
                    [0, 0, 1],
                    [1, 0, 0],
                ],
            ],
            [
                [
                    [5, 4, 8, 9],
                    [9, 9, 9, 9],
                    [4, 5, 5, 7],
                    [1, 9, 8, 7],
                ],
                [
                    [1, 0, 0, 0],
                    [.556, 1, 0, 0],
                    [.111, -8, 1, 0],
                    [.444, -1, .129, 1],
                ],
                [
                    [9, 9, 9, 9],
                    [0, -1, 3, 4],
                    [0, 0, 31, 38],
                    [0, 0, 0, 2.097],
                ],
                [
                    [0, 1, 0, 0],
                    [1, 0, 0, 0],
                    [0, 0, 0, 1],
                    [0, 0, 1, 0],
                ],
            ],
            [
                [
                    [2, 1, 1, 0],
                    [4, 3, 3, 1],
                    [8, 7, 9, 5],
                    [6, 7, 9, 8],
                ],
                [
                    [1, 0, 0, 0],
                    [0.25, 1, 0, 0],
                    [0.5, 0.667, 1, 0],
                    [0.75, -2.333, 1, 1],
                ],
                [
                    [8, 7, 9, 5],
                    [0, -0.75, -1.25, -1.25],
                    [0, 0, -0.667, -0.667],
                    [0, 0, 0, 2],
                ],
                [
                    [0, 0, 1, 0],
                    [1, 0, 0, 0],
                    [0, 1, 0, 0],
                    [0, 0, 0, 1],
                ],
            ],
            [
                [
                    [11, 9, 24, 2],
                    [1, 5, 2, 6],
                    [3, 17, 18, 1],
                    [2, 5, 7, 1],
                ],
                [
                    [1, 0, 0, 0],
                    [.27273, 1, 0, 0],
                    [.09091, .28750, 1, 0],
                    [.18182, .23125, .00360, 1],
                ],
                [
                    [11, 9, 24, 2],
                    [0, 14.54545, 11.45455, 0.45455],
                    [0, 0, -3.47500, 5.68750],
                    [0, 0, 0, 0.51079],
                ],
                [
                    [1, 0, 0, 0],
                    [0, 0, 1, 0],
                    [0, 1, 0, 0],
                    [0, 0, 0, 1],
                ],
            ],
            [
                [
                    [5, 3, 8],
                    [6, 4, 5],
                    [1, 8, 9],
                ],
                [
                    [1, 0, 0],
                    [0.167, 1, 0],
                    [.833, -0.045, 1],
                ],
                [
                    [6, 4, 5],
                    [0, 7.333, 8.167],
                    [0, 0, 4.205]
                ],
                [
                    [0, 1, 0],
                    [0, 0, 1],
                    [1, 0, 0],
                ],
            ],
            [
                [
                    [3, 2, 6, 7],
                    [4, 3, -6, 2],
                    [12, 14, 14, -6],
                    [4, 6, 4, -42],
                ],
                [
                    [1, 0, 0, 0],
                    [0.25, 1, 0, 0],
                    [0.333, 1.111, 1, 0],
                    [0.333, -0.889, -0.116, 1],
                ],
                [
                    [12, 14, 14, -6],
                    [0, -1.5, 2.5, 8.5],
                    [0, 0, -13.444, -5.444],
                    [0, 0, 0, -33.074],
                ],
                [
                    [0, 0, 1, 0],
                    [1, 0, 0, 0],
                    [0, 1, 0, 0],
                    [0, 0, 0, 1],
                ],
            ],
            [
                [
                    [5, 3, 4, 1],
                    [5, 6, 4, 3],
                    [7, 6, 5, 3],
                    [2, 7, 4, 7],
                ],
                [
                    [1, 0, 0, 0],
                    [0.286, 1, 0, 0],
                    [0.714, -0.243, 1, 0],
                    [0.714, 0.324, -0.385, 1],
                ],
                [
                    [7, 6, 5, 3],
                    [0, 5.286, 2.571, 6.143],
                    [0, 0, 1.054, 0.351],
                    [0, 0, 0, -1],
                ],
                [
                    [0, 0, 1, 0],
                    [0, 0, 0, 1],
                    [1, 0, 0, 0],
                    [0, 1, 0, 0],
                ],
            ],
        ];
    }

    /**
     * @test   LU decomposition exception for matrix not being square
     * @throws \Exception
     */
    public function testLUDecompositionExceptionNotSquare()
    {
        // Given
        $A = MatrixFactory::create([
            [1, 2, 3],
            [2, 3, 4],
        ]);

        // Then
        $this->expectException(Exception\MatrixException::class);

        // When
        $A->luDecomposition();
    }

    /**
     * @test   LU Decomposition invalid property
     * @throws \Exception
     */
    public function testLUDecompositionInvalidProperty()
    {
        // Given
        $A = MatrixFactory::create([
            [5, 3, 4, 1],
            [5, 6, 4, 3],
            [7, 6, 5, 3],
            [2, 7, 4, 7],
        ]);
        $LU = $A->luDecomposition();

        // Then
        $this->expectException(Exception\MathException::class);

        // When
        $doesNotExist = $LU->doesNotExist;
    }

    /**
     * @test   LU Decomposition solve incorrect type on b
     * @throws \Exception
     */
    public function testLUDecompositionSolveIncorrectTypeError()
    {
        // Given
        $A = MatrixFactory::create([
            [5, 3, 4, 1],
            [5, 6, 4, 3],
            [7, 6, 5, 3],
            [2, 7, 4, 7],
        ]);
        $LU = $A->luDecomposition();

        // And
        $b = new \stdClass();

        // Then
        $this->expectException(Exception\IncorrectTypeException::class);

        // When
        $LU->solve($b);
    }
}
