<?php

namespace MathPHP\Tests\LinearAlgebra\Matrix\Base;

use MathPHP\Expression\Polynomial;
use MathPHP\LinearAlgebra\MatrixFactory;
use MathPHP\LinearAlgebra\NumericMatrix;
use MathPHP\Exception;

class MatrixOperationsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test         transpose
     * @dataProvider dataProviderForTranspose
     * @param        array $A
     * @param        array $R
     * @throws       \Exception
     */
    public function testTranspose(array $A, array $R)
    {
        // Given
        $A = MatrixFactory::create($A);
        $R = MatrixFactory::create($R);

        // When
        $Aᵀ = $A->transpose();

        // Then
        $this->assertEquals($R->getMatrix(), $Aᵀ->getMatrix());
    }

    /**
     * @test         transpose of transpose is the original matrix
     * @dataProvider dataProviderForTranspose
     * @param        array $A
     * @throws       \Exception
     */
    public function testTransposeOfTransposeIsOriginalMatrix(array $A)
    {
        // Given
        $A = MatrixFactory::create($A);

        // When
        $Aᵀ  = $A->transpose();
        $Aᵀᵀ = $Aᵀ->transpose();

        // Then
        $this->assertEquals($A->getMatrix(), $Aᵀᵀ->getMatrix());
    }

    /**
     * @return array
     */
    public function dataProviderForTranspose(): array
    {
        return [
            [
                [
                    [1, 2],
                    [3, 4],
                    [5, 6],
                ],
                [
                    [1, 3, 5],
                    [2, 4, 6],
                ]
            ],
            [
                [
                    [5, 4, 3],
                    [4, 0, 4],
                    [7, 10, 3],
                ],
                [
                    [5, 4, 7],
                    [4, 0, 10],
                    [3, 4, 3],
                ]
            ],
            [
                [
                    [5, 4],
                    [4, 0],
                    [7, 10],
                    [-1, 8],
                ],
                [
                    [5, 4, 7, -1],
                    [4, 0, 10, 8],
                ]
            ]
        ];
    }

    /**
     * @test         minorMatrix
     * @dataProvider dataProviderForMinorMatrix
     */
    public function testMinorMatrix(array $A, int $mᵢ, int $nⱼ, array $Mᵢⱼ)
    {
        // Given
        $A   = MatrixFactory::create($A);
        $Mᵢⱼ = MatrixFactory::create($Mᵢⱼ);

        // When
        $minor = $A->minorMatrix($mᵢ, $nⱼ);

        // Then
        $this->assertEquals($Mᵢⱼ, $minor);
    }

    public function dataProviderForMinorMatrix(): array
    {
        return [
            [
                [
                    [1, 4, 7],
                    [3, 0, 5],
                    [-1, 9, 11],
                ],
                0, 0,
                [
                    [0, 5],
                    [9, 11],
                ],
            ],
            [
                [
                    [1, 4, 7],
                    [3, 0, 5],
                    [-1, 9, 11],
                ],
                0, 1,
                [
                    [3, 5],
                    [-1, 11],
                ],
            ],
            [
                [
                    [1, 4, 7],
                    [3, 0, 5],
                    [-1, 9, 11],
                ],
                0, 2,
                [
                    [3, 0],
                    [-1, 9],
                ],
            ],
            [
                [
                    [1, 4, 7],
                    [3, 0, 5],
                    [-1, 9, 11],
                ],
                1, 0,
                [
                    [4, 7],
                    [9, 11],
                ],
            ],
            [
                [
                    [1, 4, 7],
                    [3, 0, 5],
                    [-1, 9, 11],
                ],
                1, 1,
                [
                    [1, 7],
                    [-1, 11],
                ],
            ],
            [
                [
                    [1, 4, 7],
                    [3, 0, 5],
                    [-1, 9, 11],
                ],
                1, 2,
                [
                    [1, 4],
                    [-1, 9],
                ],
            ],
            [
                [
                    [1, 4, 7],
                    [3, 0, 5],
                    [-1, 9, 11],
                ],
                2, 0,
                [
                    [4, 7],
                    [0, 5],
                ],
            ],
            [
                [
                    [1, 4, 7],
                    [3, 0, 5],
                    [-1, 9, 11],
                ],
                2, 1,
                [
                    [1, 7],
                    [3, 5],
                ],
            ],
            [
                [
                    [1, 4, 7],
                    [3, 0, 5],
                    [-1, 9, 11],
                ],
                2, 2,
                [
                    [1, 4],
                    [3, 0],
                ],
            ],
        ];
    }

    /**
     * @test minorMatrix exception - bad row
     */
    public function testMinorMatrixExceptionBadRow()
    {
        // Given
        $A = MatrixFactory::create([
            [1, 2, 3],
            [2, 3, 4],
            [3, 4, 5],
        ]);

        // Then
        $this->expectException(Exception\MatrixException::class);

        // When
        $A->minorMatrix(4, 1);
    }

    /**
     * @test minorMatrix exception - bad column
     */
    public function testMinorMatrixExceptionBadColumn()
    {
        // Given
        $A = MatrixFactory::create([
            [1, 2, 3],
            [2, 3, 4],
            [3, 4, 5],
        ]);

        // Then
        $this->expectException(Exception\MatrixException::class);

        // When
        $A->minorMatrix(1, 4);
    }
    /**
     * @test minorMatrix exception - not square
     */
    public function testMinorMatrixExceptionNotSquare()
    {
        // Given
        $A = MatrixFactory::create([
            [1, 2, 3, 4],
            [2, 3, 4, 4],
            [3, 4, 5, 4],
        ]);

        // Then
        $this->expectException(Exception\MatrixException::class);

        // When
        $A->minorMatrix(1, 1);
    }

    /**
     * @test         leadingPrincipalMinor returns the expected SquareMatrix
     * @dataProvider dataProviderForLeadingPrincipalMinor
     * @param        array $A
     * @param        int $k
     * @param        array $R
     */
    public function testLeadingPrincipalMinor(array $A, int $k, array $R)
    {
        // Given
        $A = MatrixFactory::create($A);
        $R = MatrixFactory::create($R);

        // When
        $minor = $A->leadingPrincipalMinor($k);

        // Then
        $this->assertEquals($R, $minor);
    }

    public function dataProviderForLeadingPrincipalMinor(): array
    {
        return [
            [
                [
                    [1],
                ],
                1,
                [
                    [1],
                ],
            ],
            [
                [
                    [1, 2],
                    [4, 5],
                ],
                1,
                [
                    [1],
                ],
            ],
            [
                [
                    [1, 2],
                    [4, 5],
                ],
                2,
                [
                    [1, 2],
                    [4, 5],
                ],
            ],
            [
                [
                    [1, 2, 3],
                    [4, 5, 6],
                    [7, 8, 9],
                ],
                1,
                [
                    [1],
                ],
            ],
            [
                [
                    [1, 2, 3],
                    [4, 5, 6],
                    [7, 8, 9],
                ],
                2,
                [
                    [1, 2],
                    [4, 5],
                ],
            ],
            [
                [
                    [1, 2, 3],
                    [4, 5, 6],
                    [7, 8, 9],
                ],
                3,
                [
                    [1, 2, 3],
                    [4, 5, 6],
                    [7, 8, 9],
                ],
            ],
            [
                [
                    [1, 2, 3, 4],
                    [5, 6, 7, 8],
                    [9, 0, 1, 2],
                    [3, 4, 5, 6],
                ],
                1,
                [
                    [1],
                ],
            ],
            [
                [
                    [1, 2, 3, 4],
                    [5, 6, 7, 8],
                    [9, 0, 1, 2],
                    [3, 4, 5, 6],
                ],
                2,
                [
                    [1, 2],
                    [5, 6],
                ],
            ],
            [
                [
                    [1, 2, 3, 4],
                    [5, 6, 7, 8],
                    [9, 0, 1, 2],
                    [3, 4, 5, 6],
                ],
                3,
                [
                    [1, 2, 3],
                    [5, 6, 7],
                    [9, 0, 1],
                ],
            ],
            [
                [
                    [1, 2, 3, 4],
                    [5, 6, 7, 8],
                    [9, 0, 1, 2],
                    [3, 4, 5, 6],
                ],
                4,
                [
                    [1, 2, 3, 4],
                    [5, 6, 7, 8],
                    [9, 0, 1, 2],
                    [3, 4, 5, 6],
                ],
            ],
        ];
    }

    /**
     * @test leadingPrincipalMinor throws an OutOfBoundsException when k is < 0.
     */
    public function testLeadingPrincipalMinorExceptionKLessThanZero()
    {
        // Given
        $A = MatrixFactory::create([
            [1, 2, 3],
            [2, 3, 4],
            [3, 4, 5],
        ]);

        // Then
        $this->expectException(Exception\OutOfBoundsException::class);

        // When
        $R = $A->leadingPrincipalMinor(-1);
    }

    /**
     * @test leadingPrincipalMinor throws an OutOfBoundsException when k is > n.
     */
    public function testLeadingPrincipalMinorExceptionKGreaterThanN()
    {
        // Given
        $A = MatrixFactory::create([
            [1, 2, 3],
            [2, 3, 4],
            [3, 4, 5],
        ]);

        // Then
        $this->expectException(Exception\OutOfBoundsException::class);

        // When
        $R = $A->leadingPrincipalMinor($A->getN() + 1);
    }

    /**
     * @test leadingPrincipalMinor throws a MatrixException if the Matrix is not square.
     */
    public function testLeadingPrincipalMinorExceptionMatrixNotSquare()
    {
        // Given
        $A = MatrixFactory::create([
            [1, 2, 3],
            [2, 3, 4],
            [3, 4, 5],
            [4, 5, 6],
        ]);

        // Then
        $this->expectException(Exception\MatrixException::class);

        // When
        $R = $A->leadingPrincipalMinor(2);
    }

    /**
     * @test         minor
     * @dataProvider dataProviderForMinor
     */
    public function testMinor(array $A, int $mᵢ, int $nⱼ, $Mᵢⱼ)
    {
        // Given
        $A = MatrixFactory::create($A);

        // When
        $minor = $A->minor($mᵢ, $nⱼ);

        // Then
        $this->assertEquals($Mᵢⱼ, $minor);
    }

    public function dataProviderForMinor(): array
    {
        return [
            [
                [
                    [1, 4, 7],
                    [3, 0, 5],
                    [-1, 9, 11],
                ],
                0, 0, -45
            ],
            [
                [
                    [1, 4, 7],
                    [3, 0, 5],
                    [-1, 9, 11],
                ],
                0, 1, 38
            ],
            [
                [
                    [1, 4, 7],
                    [3, 0, 5],
                    [-1, 9, 11],
                ],
                1, 2, 13
            ],
            [
                [
                    [1, 2, 1],
                    [6, -1, 0],
                    [-1, -2, -1],
                ], 0, 0, 1
            ],
            [
                [
                    [1, 2, 1],
                    [6, -1, 0],
                    [-1, -2, -1],
                ], 0, 1, -6
            ],
            [
                [
                    [1, 2, 1],
                    [6, -1, 0],
                    [-1, -2, -1],
                ], 0, 2, -13
            ],
            [
                [
                    [1, 2, 1],
                    [6, -1, 0],
                    [-1, -2, -1],
                ], 1, 0, 0
            ],
            [
                [
                    [1, 2, 1],
                    [6, -1, 0],
                    [-1, -2, -1],
                ], 1, 1, 0
            ],
            [
                [
                    [1, 2, 1],
                    [6, -1, 0],
                    [-1, -2, -1],
                ], 1, 2, 0
            ],
            [
                [
                    [1, 2, 1],
                    [6, -1, 0],
                    [-1, -2, -1],
                ], 2, 0, 1
            ],
            [
                [
                    [1, 2, 1],
                    [6, -1, 0],
                    [-1, -2, -1],
                ], 2, 1, -6
            ],
            [
                [
                    [1, 2, 1],
                    [6, -1, 0],
                    [-1, -2, -1],
                ], 2, 2, -13
            ],
        ];
    }

    /**
     * @test minor exception - bad row
     */
    public function testMinorExceptionBadRow()
    {
        // Given
        $A = MatrixFactory::create([
            [1, 2, 3],
            [2, 3, 4],
            [3, 4, 5],
        ]);

        // Then
        $this->expectException(Exception\MatrixException::class);

        // When
        $A->minor(4, 1);
    }

    /**
     * @test minor exception - bad column
     */
    public function testMinorExceptionBadColumn()
    {
        // Given
        $A = MatrixFactory::create([
            [1, 2, 3],
            [2, 3, 4],
            [3, 4, 5],
        ]);

        // Then
        $this->expectException(Exception\MatrixException::class);

        // When
        $A->minor(1, 4);
    }

    /**
     * @test minor exception - not square
     */
    public function testMinorExceptionNotSquare()
    {
        // Given
        $A = MatrixFactory::create([
            [1, 2, 3, 4],
            [2, 3, 4, 4],
            [3, 4, 5, 4],
        ]);

        // Then
        $this->expectException(Exception\MatrixException::class);

        // When
        $A->minor(1, 1);
    }

    /**
     * @test         submatrix
     * @dataProvider dataProviderForSubmatrix
     * @param        array $data
     * @param        array $params
     * @param        array $result
     * @throws       \Exception
     */
    public function testSubmatrix(array $data, array $params, array $result)
    {
        // Given
        $M = new NumericMatrix($data);
        $expectedMatrix = new NumericMatrix($result);

        // When
        $R = $M->submatrix(...$params);

        // Then
        $this->assertEquals($expectedMatrix->getMatrix(), $R->getMatrix());
    }

    /**
     * @return array
     */
    public function dataProviderForSubMatrix(): array
    {
        return [
            [
                [
                    [1, 4, 7],
                    [3, 0, 5],
                    [-1, 9, 11],
                ],
                [1, 1, 2, 2],
                [
                    [0, 5],
                    [9, 11],
                ],
            ],
            [
                [
                    [1, 4, 7],
                    [3, 0, 5],
                    [-1, 9, 11],
                ],
                [0, 0, 1, 0],
                [
                    [1],
                    [3],
                ],
            ],
            [
                [
                    [1, 4, 7, 30],
                    [3, 0, 5, 4],
                    [-1, 9, 11, 10],
                ],
                [0, 1, 1, 3],
                [
                    [4, 7, 30],
                    [0, 5, 4],
                ],
            ],
        ];
    }

    /**
     * @test   submatrix exception - bad row
     * @throws \Exception
     */
    public function testSubmatrixExceptionBadRow()
    {
        // Given
        $A = MatrixFactory::create([
            [1, 2, 3],
            [2, 3, 4],
            [3, 4, 5],
        ]);

        // Then
        $this->expectException(Exception\MatrixException::class);
        $this->expectExceptionMessage('Specified Matrix row does not exist');

        // When
        $A->submatrix(0, 0, 4, 1);
    }

    /**
     * @test   submatrix exception - bad column
     * @throws \Exception
     */
    public function testSubMatrixExceptionBadColumn()
    {
        // Given
        $A = MatrixFactory::create([
            [1, 2, 3],
            [2, 3, 4],
            [3, 4, 5],
        ]);

        // Then
        $this->expectException(Exception\MatrixException::class);
        $this->expectExceptionMessage('Specified Matrix column does not exist');

        // When
        $A->submatrix(0, 0, 1, 4);
    }

    /**
     * @test   submatrix exception - wrong row order
     * @throws \Exception
     */
    public function testSubMatrixWrongRowOrder()
    {
        // Given
        $A = MatrixFactory::create([
            [1, 2, 3],
            [2, 3, 4],
            [3, 4, 5],
        ]);

        // Then
        $this->expectException(Exception\MatrixException::class);
        $this->expectExceptionMessage('Ending row must be greater than beginning row');

        // When
        $A->submatrix(2, 0, 1, 2);
    }

    /**
     * @test   submatrix exception - wrong column order
     * @throws \Exception
     */
    public function testSubMatrixWrongColumnOrder()
    {
        // Given
        $A = MatrixFactory::create([
            [1, 2, 3],
            [2, 3, 4],
            [3, 4, 5],
        ]);

        // Then
        $this->expectException(Exception\MatrixException::class);
        $this->expectExceptionMessage('Ending column must be greater than the beginning column');

        // When
        $A->submatrix(0, 2, 1, 0);
    }

    /**
     * @test         insert returns the expected value
     * @dataProvider dataProviderForInsert
     */
    public function testInsert(array $A, array $B, int $m, int $n, $expected)
    {
        // Given
        $A = MatrixFactory::create($A);
        $B = MatrixFactory::create($B);

        // When
        $matrixWithInsertion = $A->insert($B, $m, $n);

        // Then
        $this->assertEquals($expected, $matrixWithInsertion->getMatrix());
    }

    public function dataProviderForInsert(): array
    {
        return [
            [
                [
                    [1, 1, 1],
                    [1, 1, 1],
                    [1, 1, 1],
                ],
                [
                    [0, 0],
                    [0, 0],
                ],
                1,
                1,
                [
                    [1, 1, 1],
                    [1, 0, 0],
                    [1, 0, 0],
                ],
            ],
            [
                [
                    [1, 1, 1],
                    [1, 1, 1],
                    [1, 1, 1],
                ],
                [
                    [0, 0],
                ],
                1,
                1,
                [
                    [1, 1, 1],
                    [1, 0, 0],
                    [1, 1, 1],
                ],
            ],
            [
                [
                    [1, 1, 1],
                    [1, 1, 1],
                    [1, 1, 1],
                ],
                [
                    [0, 0],
                ],
                2,
                1,
                [
                    [1, 1, 1],
                    [1, 1, 1],
                    [1, 0, 0],
                ],
            ],
        ];
    }

    /**
     * @test   insert exception - Inner matrix exceeds bounds
     * @throws \Exception
     */
    public function testInsertMatrixExceedsBounds()
    {
        // Given
        $A = MatrixFactory::create([
            [1, 1, 1],
            [1, 1, 1],
            [1, 1, 1],
        ]);
        // And
        $B = MatrixFactory::create([
            [0, 0, 0],
        ]);
        // Then
        $this->expectException(Exception\MatrixException::class);
        // When
        $A->insert($B, 1, 1);
    }

    /**
     * @test   insert exception - matrix is not the same type.
     * @throws \Exception
     */
    public function testInsertExceptionTypeMismatch()
    {
        // Given
        $A    = MatrixFactory::create([[1]]);
        $B    = MatrixFactory::create([[new Polynomial([1,1])]]);

        // Then
        $this->expectException(Exception\MatrixException::class);

        // When
        $A->insert($B, 0, 0);
    }
}
