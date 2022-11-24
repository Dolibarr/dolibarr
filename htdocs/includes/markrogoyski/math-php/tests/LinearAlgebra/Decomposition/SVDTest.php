<?php

namespace MathPHP\Tests\LinearAlgebra\Decomposition;

use MathPHP\Functions\Support;
use MathPHP\LinearAlgebra\MatrixFactory;
use MathPHP\Exception;
use MathPHP\LinearAlgebra\NumericMatrix;
use MathPHP\LinearAlgebra\Vector;
use MathPHP\Tests\LinearAlgebra\Fixture\MatrixDataProvider;

class SVDTest extends \PHPUnit\Framework\TestCase
{
    use MatrixDataProvider;

    /**
     * @test         SVD returns the expected array of U, S, and Vt factorized matrices
     * @dataProvider dataProviderForSVD
     * @dataProvider dataProviderForLesserRankSVD
     * @param        array $A
     * @param        array $expected
     * @throws       \Exception
     */
    public function testSVD(array $A, array $expected)
    {
        // Given
        $A = MatrixFactory::createNumeric($A);
        $expected_S = MatrixFactory::createNumeric($expected['S']);

        // When
        $svd = $A->svd();

        // And
        $U = $svd->U;
        $S = $svd->S;
        $V = $svd->V;

        // Then A = USVᵀ
        $this->assertEqualsWithDelta($A->getMatrix(), $U->multiply($S)->multiply($V->transpose())->getMatrix(), 0.00001);

        // And S is expected solution to SVD
        $this->assertEqualsWithDelta($expected_S->getMatrix(), $S->getMatrix(), 0.00001);
    }

    /**
     * Test data created with:
     *  R: svd(A)
     *  Python: scipy.linalg.svd(A)
     * @return array
     */
    public function dataProviderForSVD(): array
    {
        return [
            [
                [
                    [1, 0, 0, 0, 2],
                    [0, 0, 3, 0, 0],
                    [0, 0, 0, 0, 0],
                    [0, 2, 0, 0, 0],
                ],
                [ // Technically, the order of the diagonal elements can be in any order
                    'S' => [
                        [3, 0, 0, 0, 0],
                        [0, sqrt(5), 0, 0, 0],
                        [0, 0, 2, 0, 0],
                        [0, 0, 0, 0, 0],
                    ],
                ],
            ],
            [
                [
                    [8, -6, 2],
                    [-6, 7, -4],
                    [2, -4, -3],
                ],
                [
                    'S' => [
                        [14.528807, 0, 0],
                        [0, 4.404176, 0],
                        [0, 0, 1.875369],
                    ],
                ],
            ],
            [
                [
                    [1, 2],
                    [3, 4],
                    [5, 6],
                ],
                [
                    'S' => [
                        [9.52551809, 0],
                        [0, 0.51430058],
                        [0, 0],
                    ],
                ],
            ],
            [
                [[3]],
                [
                    'S' => [[3]],
                ],
            ],
            [
                [[0]],
                [
                    'S' => [[0]],
                ],
            ],
            [
                [[1]],
                [
                    'S' => [[1]],
                ],
            ],
            [
                [
                    [1, 2, 3],
                    [4, 5, 6],
                    [7, 8, 9],
                ],
                [
                    'S' => [
                        [1.684810e+01, 0, 0],
                        [0, 1.068370e+00, 0],
                        [0, 0, 4.418425e-16],
                    ],
                ],
            ],
            [
                [
                    [2, 2, 2],
                    [2, 2, 2],
                    [2, 2, 2],
                ],
                [
                    'S' => [
                        [6, 0, 0],
                        [0, 0, 0],
                        [0, 0, 0],
                    ],
                ],
            ],
            [
                [
                    [-2, -2, -2],
                    [-2, -2, -2],
                    [-2, -2, -2],
                ],
                [
                    'S' => [
                        [6, 0, 0],
                        [0, 0, 0],
                        [0, 0, 0],
                    ],
                ],
            ],
            [
                [
                    [1, 2, 3],
                    [0, 4, 5],
                    [0, 0, 6],
                ],
                [
                    'S' => [
                        [9.0125424, 0, 0],
                        [0, 2.9974695, 0],
                        [0, 0, 0.8884012],
                    ],
                ],
            ],
            [
                [
                    [1, 0, 0],
                    [2, 3, 0],
                    [4, 5, 6],
                ],
                [
                    'S' => [
                        [9.2000960, 0, 0],
                        [0, 2.3843001, 0],
                        [0, 0, 0.8205768],
                    ],
                ],
            ],
            // Singular
            [
                [
                    [1, 0],
                    [0, 0],
                ],
                [
                    'S' => [
                        [1, 0],
                        [0, 0],
                    ],
                ],
            ],
            // Singular
            [
                [
                    [1, 0, 1],
                    [0, 1, -1],
                    [0, 0, 0],
                ],
                [
                    'S' => [
                        [1.732051, 0, 0],
                        [0, 1.000000, 0],
                        [0, 0, 0.0],
                    ],
                ],
            ],
            // Idempotent
            [
                [
                    [1, 0, 0],
                    [0, 0, 0],
                    [0, 0, 1],
                ],
                [
                    'S' => [
                        [1, 0, 0],
                        [0, 1, 0],
                        [0, 0, 0],
                    ],
                ],
            ],
            // Idempotent
            [
                [
                    [2, -2, -4],
                    [-1, 3, 4],
                    [1, -2, -3],
                ],
                [
                    'S' => [
                        [7.937254, 0, 0],
                        [0, 1, 0],
                        [0, 0, 2.198569e-17],
                    ],
                ],
            ],
            // Floating point
            [
                [
                    [2.5, 6.3, 9.1],
                    [-1.4, 3.0, 4.45],
                    [1.01, 8.5, -3.334],
                ],
                [
                    'S' => [
                        [12.786005, 0, 0],
                        [0, 8.663327, 0],
                        [0, 0, 2.315812],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function dataProviderForLesserRankSVD(): array
    {
        return [
            [
                [
                    [1, 1, 1, 1, 1],
                    [1, 1, 1, 1, 1],
                    [1, 1, 1, 1, 1],
                ],
                [
                    'S' => [
                        [3.872983, 0, 0, 0, 0],
                        [0, 1.812987e-16, 0, 0, 0],
                        [0, 0, 1.509615e-32, 0, 0],
                    ],
                ],
            ]
        ];
    }

    /**
     * @test         SVD properties
     * @dataProvider dataProviderForSVD
     * @param        array $A
     * @throws       \Exception
     */
    public function testSVDProperties(array $A)
    {
        // Given
        $A = MatrixFactory::createNumeric($A);

        // When
        $svd = $A->svd();

        // And
        $U = $svd->U;
        $S = $svd->S;
        $V = $svd->V;
        $D = $svd->D;

        // Then U and V are orthogonal
        $this->assertTrue($svd->getU()->isOrthogonal());
        $this->assertTrue($svd->getV()->isOrthogonal());

        // And S is rectangular diagonal with non-negative real numbers on the diagonal
        $this->assertTrue($S->isRectangularDiagonal());
        foreach ($S->getDiagonalElements() as $diagonalElement) {
            $this->assertTrue($diagonalElement >= 0);
        }

        // And D contains the diagonal elements of S
        $this->assertEqualsWithDelta($D->getVector(), $S->getDiagonalElements(), 0.00001, '');

        // And the number of non-zero singular values is equal to the rank of M
        $nonZeroSingularValues = array_filter(
            $D->getVector(),
            function ($singularValue) {
                return Support::isNotZero($singularValue);
            }
        );
        $this->assertEquals($A->rank(), count($nonZeroSingularValues));

        // And UUᵀ = I
        $this->assertEqualsWithDelta(MatrixFactory::identity($U->getM())->getMatrix(), $U->multiply($U->transpose())->getMatrix(), 0.00001);

        // And VVᵀ = I
        $this->assertEqualsWithDelta(MatrixFactory::identity($V->getM())->getMatrix(), $V->multiply($V->transpose())->getMatrix(), 0.00001);
    }

    /**
     * @test         SVD properties of less than full rank matrices
     * @dataProvider dataProviderForLesserRankSVD
     * @param        array $A
     * @throws       \Exception
     */
    public function testLesserRankSVDProperties(array $A)
    {
        // Given
        $A = MatrixFactory::createNumeric($A);

        // When
        $svd = $A->svd();

        // Then
        $this->assertTrue($svd->getU()->isOrthogonal());
        $this->assertTrue($svd->getS()->isRectangularDiagonal());
        $this->assertEqualsWithDelta($svd->D->getVector(), $svd->getS()->getDiagonalElements(), 0.00001, '');
    }

    /**
     * @test SVD get properties
     */
    public function testSVDGetProperties()
    {
        // Given
        $A = MatrixFactory::createNumeric([
            [4, 1, -1],
            [1, 2, 1],
            [-1, 1, 2],
        ]);
        $svd = $A->svd();

        // When
        $S = $svd->S;
        $V = $svd->V;
        $D = $svd->D;
        $U = $svd->U;

        // Then
        $this->assertInstanceOf(NumericMatrix::class, $S);
        $this->assertInstanceOf(NumericMatrix::class, $V);
        $this->assertInstanceOf(NumericMatrix::class, $U);
        $this->assertInstanceOf(Vector::class, $D);

        // And
        $this->assertEquals($svd->getS(), $S);
        $this->assertEquals($svd->getV(), $V);
        $this->assertEquals($svd->getD(), $D);
        $this->assertEquals($svd->getU(), $U);
    }

    /**
     * @test SVD invalid property
     */
    public function testSVDInvalidProperty()
    {
        // Given
        $A = MatrixFactory::createNumeric([
            [4, 1, -1],
            [1, 2, 1],
            [-1, 1, 2],
        ]);
        $svd = $A->svd();

        // Then
        $this->expectException(Exception\MathException::class);

        // When
        $doesNotExist = $svd->doesNotExist;
    }
}
