<?php

namespace MathPHP\Tests\LinearAlgebra\Eigen;

use MathPHP\Exception;
use MathPHP\LinearAlgebra\MatrixFactory;
use MathPHP\LinearAlgebra\Eigenvector;

class EigenvectorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test         eigenvector using closedFormPolynomialRootMethod returns the expected eigenvalues
     * @dataProvider dataProviderForEigenvector
     * @param        array $A
     * @param        array $S
     */
    public function testEigenvectorsUsingClosedFormPolynomialRootMethod(array $A, array $S)
    {
        // Given
        $A = MatrixFactory::create($A);

        // When
        $eigenvectors = Eigenvector::eigenvectors($A);

        // Then
        $this->assertEqualsWithDelta($S, $eigenvectors->getMatrix(), 0.0001);
        $this->assertEqualsWithDelta($S, $A->eigenvectors()->getMatrix(), 0.0001);
    }

    /**
     * @test         eigenvector using closedFormPolynomialRootMethod returns the expected eigenvalues
     * @dataProvider dataProviderForEigenvector
     * @param        array $A
     * @param        array $S
     */
    public function testEigenvectorsUsingClosedFormPolynomialRootMethodFromMatrix(array $A, array $S)
    {
        // Given
        $A = MatrixFactory::create($A);

        // When
        $eigenvectors = $A->eigenvectors();

        // Then
        $this->assertEqualsWithDelta($S, $eigenvectors->getMatrix(), 0.0001);
    }

    public function dataProviderForEigenvector(): array
    {
        return [
            [
                [
                    [0, 1],
                    [-2, -3],
                ],
                [
                    [1 / \sqrt(5), \M_SQRT1_2],
                    [-2 / \sqrt(5), -\M_SQRT1_2],
                ]
            ],
            [
                [
                    [6, -1],
                    [2, 3],
                ],
                [
                    [\M_SQRT1_2, 1 / \sqrt(5)],
                    [\M_SQRT1_2, 2 / \sqrt(5)],
                ]
            ],
            [
                [
                    [-2, -4, 2],
                    [-2, 1, 2],
                    [4, 2, 5],
                ],
                [
                    [1 / \sqrt(293), 2 / \sqrt(6), 2 / \sqrt(14)],
                    [6 / \sqrt(293), 1 / \sqrt(6), -3 / \sqrt(14)],
                    [16 / \sqrt(293), -1 / \sqrt(6), -1 / \sqrt(14)],
                ]
            ],
            [ // RREF is a zero matrix
                [
                    [1, 0, 0],
                    [0, 1, 0],
                    [0, 0, 1],
                ],
                [
                    [1, 0, 0],
                    [0, 1, 0],
                    [0, 0, 1],
                ]
            ],
            [ // Matrix has duplicate eigenvalues. One vector is on an axis.
                [
                    [2, 0, 1],
                    [2, 1, 2],
                    [3, 0, 4],
                ],
                [
                    [1 / \sqrt(14), 0, \M_SQRT1_2],
                    [2 / \sqrt(14), 1, 0],
                    [3 / \sqrt(14), 0, -1 * \M_SQRT1_2],
                ]
            ],
            [ // Matrix has duplicate eigenvalues. no solution on the axis
                [
                    [2, 2, -3],
                    [2, 5, -6],
                    [3, 6, -8],
                ],
                [
                    [1 / \sqrt(14), 1 / \M_SQRT3, 5 / \sqrt(42)],
                    [2 / \sqrt(14), 1 / \M_SQRT3, -4 / \sqrt(42)],
                    [3 / \sqrt(14), 1 / \M_SQRT3, -1 / \sqrt(42)],
                ]
            ],
            [ // The top row of the rref has a solitary 1 in position 0,0
                [
                    [4, 1, 2],
                    [0, 0, -2],
                    [2, 2, 5],
                ],
                [
                    [ 5 / \sqrt(65), 1 / 3, 0],
                    [-2 / \sqrt(65), 2 / 3, -2 / \sqrt(5)],
                    [6 / \sqrt(65), -2 / 3, 1 / \sqrt(5),],
                ]
            ],
        ];
    }

    /**
     * @test eigenvectors throws a BadDataException when the matrix is not square
     */
    public function testEigenvectorMatrixNotCorrectSize()
    {
        // Given
        $A = MatrixFactory::create([[1,2]]);

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        Eigenvector::eigenvectors($A, [0]);
    }

    /**
     * @test         eigenvectors throws a BadDataException when the array of eigenvales is too long or short
     * @dataProvider dataProviderForIncorrectNumberOfEigenvectors
     * @param        array $A
     * @param        array $B
     */
    public function testIncorrectNumberOfEigenvectors(array $A, array $B)
    {
        // Given
        $A = MatrixFactory::create($A);

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        Eigenvector::eigenvectors($A, $B);
    }

    public function dataProviderForIncorrectNumberOfEigenvectors(): array
    {
        return [
            [
                [
                    [0, 1],
                    [-2, -3],
                ],
                [1,2,3],
            ],
        ];
    }

    /**
     * @test         eigenvectors throws a BadDataException when there is an incorrect eigenvalue provided
     * @dataProvider dataProviderForEigenvectorNotAnEigenvector
     * @param        array $A
     * @param        array $B
     */
    public function testEigenvectorNotAnEigenvector(array $A, array $B)
    {
        // Given
        $A = MatrixFactory::create($A);

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        Eigenvector::eigenvectors($A, $B);
    }

    public function dataProviderForEigenvectorNotAnEigenvector(): array
    {
        return [
            [
                [
                    [0, 1],
                    [-2, -3],
                ],
                [-2, 0],
            ],
            [
                [
                    [0, 1],
                    [-2, -3],
                ],
                [0, -3],
            ],
        ];
    }

    /**
     * @test Matrix eigenvectors throws a MatrixException if the eigenvalue method is not valid
     */
    public function testMatrixEigenvectorInvalidMethodException()
    {
        // Given
        $A = MatrixFactory::create([
            [1, 2, 3],
            [2, 3, 4],
            [3, 4, 5],
        ]);
        $invalidMethod = 'SecretMethod';

        // Then
        $this->expectException(Exception\MatrixException::class);

        // When
        $A->eigenvectors($invalidMethod);
    }
}
