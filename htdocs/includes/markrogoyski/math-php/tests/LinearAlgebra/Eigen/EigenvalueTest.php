<?php

namespace MathPHP\Tests\LinearAlgebra\Eigen;

use MathPHP\Exception;
use MathPHP\LinearAlgebra\MatrixFactory;
use MathPHP\LinearAlgebra\Eigenvalue;

class EigenvalueTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test         closedFormPolynomialRootMethod returns the expected eigenvalues
     * @dataProvider dataProviderForEigenvalues
     * @param        array $A
     * @param        array $S
     * @throws       \Exception
     */
    public function testClosedFormPolynomialRootMethod(array $A, array $S)
    {
        // Given
        $A = MatrixFactory::create($A);

        // When
        $eigenvalues = Eigenvalue::closedFormPolynomialRootMethod($A);

        // Then
        $this->assertEqualsWithDelta($S, $eigenvalues, 0.0001);
        $this->assertEqualsWithDelta($S, $A->eigenvalues(Eigenvalue::CLOSED_FORM_POLYNOMIAL_ROOT_METHOD), 0.0001);
    }

    /**
     * @test         Matrix eigenvalues using closedFormPolynomialRootMethod returns the expected eigenvalues
     * @dataProvider dataProviderForEigenvalues
     * @param        array $A
     * @param        array $S
     * @throws       \Exception
     */
    public function testClosedFormPolynomialRootMethodViaMatrix(array $A, array $S)
    {
        // Given
        $A = MatrixFactory::create($A);

        // When
        $eigenvalues = $A->eigenvalues(Eigenvalue::CLOSED_FORM_POLYNOMIAL_ROOT_METHOD);

        // Then
        $this->assertEqualsWithDelta($S, $eigenvalues, 0.0001);
    }

    /**
     * @test         jacobiMethod returns the expected eigenvalues
     * @dataProvider dataProviderForSymmetricEigenvalues
     * @dataProvider dataProviderForSymmetricEigenvalueEdgeCases
     * @param        array $A
     * @param        array $S
     * @throws       \Exception
     */
    public function testJacobiMethod(array $A, array $S)
    {
        // Given
        $A = MatrixFactory::create($A);

        // When
        $eigenvalues = Eigenvalue::jacobiMethod($A);

        // Then
        $this->assertEqualsWithDelta($S, $eigenvalues, 0.0001);
    }

    /**
     * @test         Matrix eigenvalues using jacobiMethod returns the expected eigenvalues
     * @dataProvider dataProviderForSymmetricEigenvalues
     * @dataProvider dataProviderForSymmetricEigenvalueEdgeCases
     * @param        array $A
     * @param        array $S
     * @throws       \Exception
     */
    public function testJacobiMethodViaMatrix(array $A, array $S)
    {
        // Given
        $A = MatrixFactory::create($A);

        // When
        $eigenvalues = $A->eigenvalues(Eigenvalue::JACOBI_METHOD);

        // Then
        $this->assertEqualsWithDelta($S, $eigenvalues, 0.0001);
    }

    /**
     * @test         powerIterationMethod returns the expected eigenvalues
     * @dataProvider dataProviderForEigenvalues
     * @dataProvider dataProviderForLargeMatrixEigenvalues
     * @dataProvider dataProviderForSymmetricEigenvalues
     * @param        array $A
     * @param        array $S
     * @param        float $max_abs_eigenvalue maximum absolute eigenvalue
     * @throws       \Exception
     */
    public function testPowerIteration(array $A, array $S, float $max_abs_eigenvalue)
    {
        // Given
        $A = MatrixFactory::create($A);

        // When
        $eigenvalues = Eigenvalue::powerIteration($A);

        // Then
        $this->assertEqualsWithDelta([$max_abs_eigenvalue], $eigenvalues, 0.0001);
    }

    /**
     * @test         Matrix eigenvalues using powerIterationMethod returns the expected eigenvalues
     * @dataProvider dataProviderForEigenvalues
     * @dataProvider dataProviderForLargeMatrixEigenvalues
     * @dataProvider dataProviderForSymmetricEigenvalues
     * @param        array $A
     * @param        array $S
     * @param        float $max_abs_eigenvalue maximum absolute eigenvalue
     * @throws       \Exception
     */
    public function testPowerIterationViaMatrix(array $A, array $S, float $max_abs_eigenvalue)
    {
        // Given
        $A = MatrixFactory::create($A);

        // When
        $eigenvalues = $A->eigenvalues(Eigenvalue::POWER_ITERATION);

        // Then
        $this->assertEqualsWithDelta([$max_abs_eigenvalue], $eigenvalues, 0.0001);
    }

    /**
     * @return array (matrix, eigenvalues, dominant eigenvalue)
     */
    public function dataProviderForEigenvalues(): array
    {
        return [
            [
                [
                    [0, 0],
                    [0, 0],
                ],
                [0, 0],
                0,
            ],
            [
                [
                    [0, 1],
                    [-2, -3],
                ],
                [-2, -1],
                -2,
            ],
            [
                [
                    [6, -1],
                    [2, 3],
                ],
                [5, 4],
                5,
            ],
            [
                [
                    [1, -2],
                    [-2, 0],
                ],
                [(1 + \sqrt(17)) / 2, (1 - \sqrt(17)) / 2],
                (1 + \sqrt(17)) / 2,
            ],
            [
                [
                    [2, -12],
                    [1, -5],
                ],
                [-2, -1],
                -2,
            ],
            [
                [
                    [-2, -4, 2],
                    [-2, 1, 2],
                    [4, 2, 5],
                ],
                [6, -5, 3],
                6,
            ],
            [
                [
                    [2, 0, 0],
                    [1, 2, 1],
                    [-1, 0, 1],
                ],
                [2, 2, 1],
                2,
            ],
            [
                [
                    [1, 2, 1],
                    [6, -1, 0],
                    [-1, -2, -1],
                ],
                [-4, 3, 0],
                -4,
            ],
            [
                [
                    [1, 2, 3],
                    [4, 5, 6],
                    [7, 8, 9],
                ],
                [(3 * (5 + \sqrt(33))) / 2, (-3 * (sqrt(33) - 5)) / 2, 0],
                3 * (5 + \sqrt(33)) / 2,
            ],
            [
                [
                    [8, -6, 2],
                    [-6, 7, -4],
                    [2, -4, -3],
                ],
                [14.528807, -4.404176, 1.875369],
                14.528807,
            ],
            [
                [
                    [0, 11, -5],
                    [-2, 17, -7],
                    [-4, 26, -10],
                ],
                [4, 2, 1],
                4,
            ],
        ];
    }

    /**
     * @return array (matrix, eigenvalues, dominant eiganvalue)
     */
    public function dataProviderForLargeMatrixEigenvalues(): array
    {
        return [
            [
                [
                    [ 87,  270, -12, -49, -276,  40],
                    [-14,  -45,   6,  10,   46,  -4],
                    [-50, -156,   4,  25,  162, -25],
                    [ 94,  294,  -5, -47, -306,  49],
                    [  1,    1,   3,   1,    0,   2],
                    [ 16,   48,   1,  -6,  -48,   8],
                ],
                [4, 3, 2, -2, 1, -1],
                4,
            ]
        ];
    }

    /**
     * @test         closedFormPolynomialRootMethod throws a BadDataException if the matrix is not the correct size (2x2 or 3x3)
     * @dataProvider dataProviderForEigenvalueException
     * @param        array $A
     * @throws       \Exception
     */
    public function testClosedFormPolynomialRootMethodExceptionMatrixNotCorrectSize(array $A)
    {
        // Given
        $A = MatrixFactory::create($A);

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        Eigenvalue::closedFormPolynomialRootMethod($A);
    }

    /**
     * @return array
     */
    public function dataProviderForEigenvalueException(): array
    {
        return [
            '1x1' => [
                [
                    [1],
                ],
            ],
            '5x5' => [
                [
                    [1, 2, 3, 4, 5],
                    [2, 3, 4, 5, 6],
                    [3, 4, 5, 6, 7],
                    [4, 5, 6, 7, 8],
                    [5, 6, 7, 8, 9],
                ]
            ],
            'not_square' => [
                [
                    [1, 2, 3],
                    [2, 3, 4],
                ],
            ],
        ];
    }

    /**
     * @return array (matrix, eigenvalues, dominant eigenvalue)
     */
    public function dataProviderForSymmetricEigenvalues(): array
    {
        return [
            [
                [
                    [1, 4],
                    [4, 1],
                ],
                [5.000, -3.000],
                5,
            ],
            [
                [
                    [1, 1, 1],
                    [1, 2, 1],
                    [1, 1, 1],
                ],
                [2 + M_SQRT2, 2 - M_SQRT2, 0.00],
                2 + M_SQRT2,
            ],
            [
                [
                    [1, 1, 1],
                    [1, 1, 1],
                    [1, 1, 1],
                ],
                [3, 8.881784e-16, 0],
                3,
            ],
            [
                [
                    [4, -30, 60, -35],
                    [-30, 300, -675, 420],
                    [60, -675, 1620, -1050],
                    [-35, 420, -1050, 700],
                ],
                [2585.2538, 37.10149, 1.47805, .166642],
                2585.2538,
            ],
            [
                [
                    [1, 2],
                    [2, 3],
                ],
                [4.236068, -0.236068],
                4.236068,
            ],
            [
                [
                    [1, 2],
                    [2, 1],
                ],
                [3, -1],
                3,
            ],
            [
                [
                    [4, 1],
                    [1, -2],
                ],
                [4.162278, -2.162278],
                4.162278,
            ],
            [
                [
                    [4, -1],
                    [-1, 9],
                ],
                [9.192582, 3.807418],
                9.192582,
            ],
            [
                [
                    [1, 2, 3],
                    [2, 6, 4],
                    [3, 4, 5],
                ],
                [10.784062, 1.825499, -0.609561],
                10.784062,
            ],
            [
                [
                    [1, 7, 3],
                    [7, 4, -5],
                    [3, -5, 6],
                ],
                [10.971983, 7.035946, -7.007929],
                10.971983,
            ],
            [
                [
                    [5, 6, 7],
                    [6, 3, 2],
                    [7, 2, 1],
                ],
                [13.7082039, -5.0000000, 0.2917961],
                13.7082039,
            ],
            [
                [
                    [4, -1, -1, -1],
                    [-1, 4, -1, -1],
                    [-1, -1, 4, -1],
                    [-1, -1, -1, 4],
                ],
                [5, 5, 5, 1],
                5,
            ],
            [
                [
                    [2, 7, 3],
                    [7, 9, 4],
                    [3, 4, 7],
                ],
                [16.065129, 4.287057, -2.352186],
                16.065129,
            ],
            [
                [
                    [1, 5, 6, 8],
                    [5, 2, 7, 9],
                    [6, 7, 3, 10],
                    [8, 9, 10, 4],
                ],
                [25.527715, -7.381045, -4.652925, -3.493745],
                25.527715,
            ],
            [
                [
                    [1, 7, 3, 6],
                    [7, 4, -5, 3],
                    [3, -5, 6, 2],
                    [6, 3, 2, 4],
                ],
                [13.6856756, 9.5813577, -7.2742130, -0.9928203],
                13.6856756,
            ],
            [
                [
                    [-12, -16, -18, 11, 11, 13, -20, 1],
                    [-16, -18, 10, -1, -18, 18, -16, 6],
                    [-18, 10, 4, -17, 2, -14, -11, -16],
                    [11, -1, -17, -1, -19, 5, 8, -20],
                    [11, -18, 2, -19, -13, 8, 5, -4],
                    [13, 18, -14, 5, 8, 10, -19, 19],
                    [-20, -16, -11, 8, 5, -19, 1, -3],
                    [1, 6, -16, -20, -4, 19, -3, 15],
                ],
                [53.85777, -49.65359, -48.21567, 35.73664, -33.18637, 22.86811, 15.47171, -10.87861],
                53.85777,
            ],
            [
                [
                    [3, -2, 4],
                    [-2, 6, 2],
                    [4, 2, 3],
                ],
                [7, 7, -2],
                7,
            ],
            [
                [
                    [1 / 2, 0, 0],
                    [0, 1 / 3, 0],
                    [0, 0, 1 / 4],
                ],
                [1 / 2, 1 / 3, 1 / 4],
                1 / 2,
            ],
            [
                [
                    [1, 4, 5],
                    [4, -3, 0],
                    [5, 0, 7],
                ],
                [10.150897, -6.089238, 0.938341],
                10.150897,
            ],
            [
                [
                    [2, 4, 5],
                    [4, 5, 1],
                    [5, 1, 3],
                ],
                [10.055486, -3.259280, 3.203794],
                10.055486,
            ],
            [
                [
                    [1, -1, 5],
                    [-1, 2, 1],
                    [5, 1, 3],
                ],
                [7.102976, -3.461768, 2.358792],
                7.102976,
            ],
            [
                [
                    [3, 0, 0, 0],
                    [0, 1, 0, 1],
                    [0, 0, 2, 0],
                    [0, 1, 0, 1],
                ],
                [3, 2, 2, 0],
                3,
            ],
            [
                [
                    [4, -14, -12],
                    [-14, 10, 13],
                    [-12, 13, 1],
                ],
                [31.535690, -9.643665, -6.892025],
                31.535690,
            ],
            [
                [
                    [9, 13, 3, 6],
                    [13, 11, 7, 6],
                    [3, 7, 4, 7],
                    [6, 6, 7, 10],
                ],
                [30.6854034, 7.1478692, -4.5592669, 0.7259942],
                30.6854034,
            ],
            [
                [
                    [1, 3, 8],
                    [3, 8, -4],
                    [8, -4, 6],
                ],
                [12.531500, 8.945111, -6.476611],
                12.531500,
            ],
            [
                [
                    [1, 0, 0],
                    [0, 2, 0],
                    [0, 0, 4],
                ],
                [4, 2, 1],
                4,
            ],
            [
                [
                    [8, -6, 2],
                    [-6, 7, -4],
                    [2, -4, -3],
                ],
                [14.528807, -4.404176, 1.875369],
                14.528807,
            ],
            [
                [
                    [1, 0, 0],
                    [0, 1, 0],
                    [0, 0, 1],
                ],
                [1, 1, 1],
                1,
            ],
            [
                [
                    [1, 1, 1],
                    [1, 2, 2],
                    [1, 2, 3],
                ],
                [5.0489173, 0.6431041, 0.3079785],
                5.0489173,
            ],
            [
                [
                    [0, 3, 4],
                    [3, 0, 5],
                    [4, 5, 0],
                ],
                [8.055810, -5.180268, -2.875543],
                8.055810,
            ],
            [
                [
                    [4, 0, 2, -2],
                    [0, 9, -6, 3],
                    [2, -6, 5, -3],
                    [-2, 3, -3, 2],
                ],
                [15, 5, 0, 0],
                15,
            ],
            [
                [
                    [2, -3 / 2, -3 / 2],
                    [-3 / 2, 3, 1],
                    [-3 / 2, 1, -3],
                ],
                [4.468627, -3.468627, 1.000000],
                4.468627,
            ],
        ];
    }

    /**
     * @return array (matrix, eigenvalues, dominant eigenvalue)
     */
    public function dataProviderForSymmetricEigenvalueEdgeCases(): array
    {
        return [
            [
                [
                    [0, 0, 0],
                    [0, 0, 0],
                    [0, 0, 0],
                ],
                [0, 0, 0],
                0,
            ],
        ];
    }

    /**
     * @test   Matrix eigenvalues throws a MatrixException if the eigenvalue method is not valid
     * @throws \Exception
     */
    public function testMatrixEigenvalueInvalidMethodException()
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
        $A->eigenvalues($invalidMethod);
    }

    /**
     * @test         JacobiMethod throws a BadDataException if the matrix is not the correct size.
     * @dataProvider dataProviderForSymmetricException
     * @param        array $A
     * @throws       \Exception
     */
    public function testJacobiExceptionMatrixNotCorrectSize(array $A)
    {
        // Given
        $A = MatrixFactory::create($A);

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        Eigenvalue::jacobiMethod($A);
    }

    /**
     * @return array
     */
    public function dataProviderForSymmetricException(): array
    {
        return [
            '1x1' => [
                [
                    [1],
                ],
            ],
            'not_symetric' => [
                [
                    [1, 2, 3, 4, 6],
                    [2, 3, 4, 5, 6],
                    [3, 4, 5, 6, 7],
                    [4, 5, 6, 7, 8],
                    [5, 6, 7, 8, 9],
                ]
            ],
            'not_square' => [
                [
                    [1, 2, 3],
                    [2, 3, 4],
                ]
            ],
        ];
    }

    /**
     * @test         Power Iteration throws exception if number of iterations is exceeded
     * @dataProvider dataProviderForIterationFailure
     * @param        array $A
     * @throws       \Exception
     */
    public function testPowerIterationFail(array $A)
    {
        // Given
        $A = MatrixFactory::create($A);

        // Then
        $this->expectException(Exception\FunctionFailedToConvergeException::class);

        // When
        Eigenvalue::powerIteration($A, 0);
    }

    /**
     * @return array
     */
    public function dataProviderForIterationFailure(): array
    {
        return [
            [
                [
                    [4, -30, 60, -35],
                    [-30, 300, -675, 420],
                    [60, -675, 1620, -1050],
                    [-35, 420, -1050, 700],
                ],
            ],
        ];
    }

    /**
     * @test         that a variety of matrix types can have eigenvalues calulated
     * @dataProvider dataProviderForSymmetricEigenvalues
     * @dataProvider dataProviderForEigenvalues
     * @dataProvider dataProviderForTriangularEigenvalues
     * @throws       \Exception
     */
    public function testSmartEigenvalues(array $A, array $S)
    {
        // Given
        $A = MatrixFactory::create($A);

        // When
        $eigenvalues = $A->eigenvalues();

        // Then
        $this->assertEqualsWithDelta($S, $eigenvalues, 0.0001);
    }

    /**
     * @return array
     */
    public function dataProviderForTriangularEigenvalues(): array
    {
        return [
            [
                [
                    [2, 0, 0, 0, 0, 0],
                    [4, 3, 0, 0, 0, 0],
                    [8, 2, 3, 0, 0, 0],
                    [1, 7, 3, 9, 0, 0],
                    [5, 4, 3, 2, 1, 0],
                    [1, 6, 2, 9, 3, 6],
                ],
                [9, 6, 3, 3, 2, 1],
            ],
            [
                [
                    [1, 0, 0, 1, 0, 0],
                    [0, 2, 0, 0, 1, 0],
                    [0, 0, 3, 0, 0, 1],
                    [0, 0, 0, 4, 0, 0],
                    [0, 0, 0, 0, 5, 0],
                    [0, 0, 0, 0, 0, 6],
                ],
                [6, 5, 4, 3, 2, 1],
            ],
            [
                [[6]],
                [6],
            ],
        ];
    }

    /**
     * @test         the function fails appropriately
     * @dataProvider dataProviderForEigenvalueFailure
     * @throws       \Exception
     */
    public function testSmartEigenvalueFailure(array $A)
    {
        // Given
        $A = MatrixFactory::create($A);

        // Then
        $this->expectException(Exception\MatrixException::class);

        // When
        $A->eigenvalues();
    }

    /**
     * @return array
     */
    public function dataProviderForEigenvalueFailure(): array
    {
        return [
            [ // Not Square
                [[1, 2]],
            ],
            [ // Can Not Solve (yet)
                [
                    [1, 2, 3, 4, 5],
                    [2, 3, 4, 5, 1],
                    [3, 4, 5, 1, 2],
                    [4, 5, 1, 2, 3],
                    [6, 1, 2, 3, 4],
                ],
            ],
        ];
    }

    /**
     * @test Bug Issue 414 - Jacobi method does not converge on highly correlated data and goes into infinite loop
     *       Test for eigenvalues
     * @link https://github.com/markrogoyski/math-php/issues/414
     *
     * Test data from Python Numpy
     * > import numpy
     * > A = [[11090.868109438, 2292930.5298083], [2292930.5298083, 474044636.63249]]
     * > eig = numpy.linalg.eig(A)
     * > eig
     *  (array([7.62112141e-02, 4.74055727e+08]),
     *   array([[-0.9999883 , -0.00483689],
     *          [ 0.00483689, -0.9999883 ]]))
     *
     * For reference, R result:
     * > A = rbind(c(11090.868109438, 2292930.5298083), c(2292930.5298083, 474044636.63249))
     * > ev <- eigen(A)
     * > ev
     * eigen() decomposition
     * $values
     * [1] 4.740557e+08 7.621119e-02
     *
     * $vectors
     * [,1]         [,2]
     * [1,] 0.004836894 -0.999988302
     * [2,] 0.999988302  0.004836894
     */
    public function testJocobiMethodBugIssue414Eigenvalues()
    {
        // Given
        $A = MatrixFactory::createNumeric([
            [11090.868109438, 2292930.5298083],
            [2292930.5298083, 474044636.63249],
        ]);

        // And
        $expected = [4.74055727e+08, 7.62112141e-02];

        // When
        $eigenvalues = Eigenvalue::jacobiMethod($A);

        // Then
        $this->assertEqualsWithDelta($expected[0], $eigenvalues[0], 0.5);
        $this->assertEqualsWithDelta($expected[1], $eigenvalues[1], 0.0000001);
    }
}
