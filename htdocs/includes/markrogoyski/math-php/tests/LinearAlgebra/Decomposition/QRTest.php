<?php

namespace MathPHP\Tests\LinearAlgebra\Decomposition;

use MathPHP\LinearAlgebra\MatrixFactory;
use MathPHP\Exception;
use MathPHP\LinearAlgebra\Vector;
use MathPHP\Tests\LinearAlgebra\Fixture\MatrixDataProvider;

class QRTest extends \PHPUnit\Framework\TestCase
{
    use MatrixDataProvider;

    /**
     * @test         qrDecomposition property A = QR
     * @dataProvider dataProviderForQrDecompositionSquareMatricesWithSpecificResults
     * @dataProvider dataProviderForQrDecompositionNonSquareMatricesWithSpecificResults
     * @dataProvider dataProviderForSingularMatrix
     * @dataProvider dataProviderForNonsingularMatrix
     * @param        array $A
     * @throws       \Exception
     */
    public function testQrDecompositionPropertyAEqualsQR(array $A)
    {
        // Given
        $A = MatrixFactory::create($A);

        // And
        $qrDecomposition = $A->qrDecomposition();

        // When
        $QR = $qrDecomposition->Q->multiply($qrDecomposition->R);

        // Then A = QR
        $this->assertEqualsWithDelta($A->getMatrix(), $QR->getMatrix(), 0.00001);
    }

    /**
     * @test         qrDecomposition properties Q is orthogonal and R is upper triangular
     * @dataProvider dataProviderForQrDecompositionSquareMatricesWithSpecificResults
     * @dataProvider dataProviderForSingularMatrix
     * @dataProvider dataProviderForNonsingularMatrix
     * @param        array $A
     * @throws       \Exception
     */
    public function testQrDecompositionPropertiesOfQR(array $A)
    {
        // Given
        $A = MatrixFactory::create($A);

        // When
        $qr  = $A->qrDecomposition();

        // Then Q is orthogonal and R is upper triangular
        $this->assertTrue($qr->Q->isOrthogonal());
        $this->assertTrue($qr->R->isUpperTriangular());
    }

    /**
     * @test         qrDecomposition returns the expected array of Q and R factorized matrices
     *               This test could be removed. It is testing a specific implementation of QR decomposition.
     * @dataProvider dataProviderForQrDecompositionSquareMatricesWithSpecificResults
     * @dataProvider dataProviderForQrDecompositionNonSquareMatricesWithSpecificResults
     * @param        array $A
     * @param        array $Q
     * @param        array $R
     * @throws       \Exception
     */
    public function testQrDecompositionResultMatrices(array $A, array $Q, array $R)
    {
        // Given
        $A = MatrixFactory::create($A);
        $Q = MatrixFactory::create($Q);
        $R = MatrixFactory::create($R);

        // When
        $qr  = $A->qrDecomposition();
        $qrQ = $qr->Q;
        $qrR = $qr->R;

        // And Q and R are expected solution to QR decomposition
        $this->assertEqualsWithDelta($R->getMatrix(), $qrR->getMatrix(), 0.00001);
        $this->assertEqualsWithDelta($Q->getMatrix(), $qrQ->getMatrix(), 0.00001);
    }

    /**
     * @test         Orthonormal matrix Q has the property QᵀQ = I
     * @dataProvider dataProviderForQrDecompositionSquareMatricesWithSpecificResults
     * @dataProvider dataProviderForQrDecompositionNonSquareMatricesWithSpecificResults
     * @dataProvider dataProviderForSingularMatrix
     * @dataProvider dataProviderForNonsingularMatrix
     * @param        array $A
     * @throws       \Exception
     */
    public function testQrDecompositionOrthonormalMatrixQPropertyQTransposeQIsIdentity(array $A)
    {
        // Given
        $A = MatrixFactory::create($A);
        $I = MatrixFactory::identity(min($A->getM(), $A->getN()));

        // And
        $qr = $A->qrDecomposition();

        // When
        $QᵀQ = $qr->Q->transpose()->multiply($qr->Q);

        // Then QᵀQ = I
        $this->assertEqualsWithDelta($I->getMatrix(), $QᵀQ->getMatrix(), 0.000001);
    }

    /**
     * @test         qrDecomposition property R = QᵀA
     * @dataProvider dataProviderForQrDecompositionSquareMatricesWithSpecificResults
     * @dataProvider dataProviderForQrDecompositionNonSquareMatricesWithSpecificResults
     * @dataProvider dataProviderForSingularMatrix
     * @dataProvider dataProviderForNonsingularMatrix
     * @param        array $A
     * @throws       \Exception
     */
    public function testQrDecompositionPropertyREqualsQTransposeA(array $A)
    {
        // Given
        $A = MatrixFactory::create($A);

        // And
        $qrDecomposition = $A->qrDecomposition();

        // When
        $QᵀA = $qrDecomposition->Q->transpose()->multiply($A);

        // Then R = QᵀA
        $this->assertEqualsWithDelta($qrDecomposition->R->getMatrix(), $QᵀA->getMatrix(), 0.00001);
    }

    /**
     * @test         qrDecomposition property Qᵀ = Q⁻¹
     * @dataProvider dataProviderForQrDecompositionSquareMatricesWithSpecificResults
     * @dataProvider dataProviderForSingularMatrix
     * @dataProvider dataProviderForNonsingularMatrix
     * @param        array $A
     * @throws       \Exception
     */
    public function testQrDecompositionPropertyQTransposeEqualsQInverse(array $A)
    {
        // Given
        $A = MatrixFactory::create($A);

        // And
        $Q = $A->qrDecomposition()->Q;

        // When
        $Qᵀ  = $Q->transpose();
        $Q⁻¹ = $Q->inverse();

        // Then Qᵀ = Q⁻¹
        $this->assertEqualsWithDelta($Qᵀ->getMatrix(), $Q⁻¹->getMatrix(), 0.00001);
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
        $QR = $A->qrDecomposition();

        // And
        $expected = new Vector($expected);

        // When
        $x = $QR->solve($b);

        // Then
        $this->assertEqualsWithDelta($expected, $x, 0.00001);
    }

    /**
     * @return array (A, Q, R)
     */
    public function dataProviderForQrDecompositionSquareMatricesWithSpecificResults(): array
    {
        return [
            [
                'A' => [
                    [0],
                ],
                'Q' => [
                    [1],
                ],
                'R' => [
                    [0],
                ],
            ],
            [
                'A' => [
                    [1],
                ],
                'Q' => [
                    [1],
                ],
                'R' => [
                    [1],
                ],

            ],
            [
                'A' => [
                    [0, 0],
                    [0, 0],
                ],
                'Q' => [
                    [1, 0],
                    [0, 1]
                ],
                'R' => [
                    [0, 0],
                    [0, 0],
                ],
            ],
            [
                'A' => [
                    [1, 1],
                    [1, 1],
                ],
                'Q' => [
                    [-0.7071068, -0.7071068],
                    [-0.7071068, 0.7071068]
                ],
                'R' => [
                    [-1.414214, -1.414214],
                    [0, 0],
                ],
            ],
            [
                'A' => [
                    [6, 3],
                    [8, 4],
                ],
                'Q' => [
                    [-0.6, -0.8],
                    [-0.8, 0.6]
                ],
                'R' => [
                    [-10, -5],
                    [0, 0],
                ],
            ],
            [
                'A' => [
                    [2, -2, 18],
                    [2, 1, 0],
                    [1, 2, 0],
                ],
                'Q' => [
                    [-2 / 3,  2 / 3, 1 / 3],
                    [-2 / 3, -1 / 3, -2 / 3],
                    [-1 / 3, -2 / 3, 2 / 3],
                ],
                'R' => [
                    [-3,  0, -12],
                    [ 0, -3,  12],
                    [ 0,  0,  6],
                ],
            ],
            [
                'A' => [
                    [12, -51,    4],
                    [ 6,  167, -68],
                    [-4,  24,  -41],
                ],
                'Q' => [
                    [ -0.85714286,  0.39428571,  0.33142857],
                    [ -0.42857143, -0.90285714, -0.03428571],
                    [0.28571429, -0.17142857,  0.94285714],
                ],
                'R' => [
                    [-14,  -21, 14],
                    [ 0, -175, 70],
                    [ 0,   0,  -35],
                ],
            ],
            [
                'A' => [
                    [4, 3, 7],
                    [1, 3, 6],
                    [8, 5, 7],
                ],
                'Q' => [
                    [-0.4444444, -0.1194133, -0.8878117],
                    [-0.1111111, -0.9760737,  0.1869077],
                    [-0.8888889,  0.1817158,  0.4205424],
                ],
                'R' => [
                    [-9, -6.111111, -10.000000],
                    [0,  -2.377882, -5.420324],
                    [0,  0.000000,  -2.149439],
                ],
            ],
            [
                'A' => [
                    [1, 2, 3, 2],
                    [4, 5, 6, 2],
                    [7, 8, 9, 2],
                    [4, 5, 5, 6],
                ],
                'Q' => [
                    [-0.1104315,  0.8589557,  0.2886751, -4.082483e-01],
                    [-0.4417261,  0.2342606,  0.2886751,  8.164966e-01],
                    [-0.7730207, -0.3904344,  0.2886751, -4.082483e-01],
                    [-0.4417261,  0.2342606, -0.8660254, -9.159340e-16],
                ],
                'R' => [
                    [-9.055385, -10.8222896, -12.1474679, -5.300713e+00],
                    [ 0.000000,   0.9370426,   1.6398245,  2.811128e+00],
                    [ 0.000000,   0.0000000,   0.8660254, -3.464102e+00],
                    [ 0.000000,   0.0000000,   0.0000000, -5.329071e-15],
                ],
            ],
            [
                'A' => [
                    [7, 8, 9, 2],
                    [1, 2, 3, 2],
                    [4, -3, 2, 12],
                    [4, 1, -6, 6],
                ],
                'Q' => [
                    [-0.7730207, -0.5413835, -0.2274010, -0.24006603],
                    [-0.1104315, -0.2016919, -0.1581711,  0.96026411],
                    [-0.4417261,  0.7890753, -0.4245139,  0.04501238],
                    [-0.4417261,  0.2087688,  0.8620085,  0.13503714],
                ],
                'R' => [
                    [-9.055385, -5.521576, -5.521576, -9.7179743],
                    [ 0.000000, -6.892909, -5.151990,  9.2353658],
                    [ 0.000000,  0.000000, -8.542201, -0.6932606],
                    [ 0.000000,  0.000000,  0.000000,  2.7907676],
                ],
            ],
            [
                'A' => [
                    [3, 7, 6, 4, 5],
                    [2, 3, 6, 5, 8],
                    [2, 3, 4, 1, 0],
                    [3, 7, 6, 7, 7],
                    [1, 3, 4, 9, 4],
                ],
                'Q' => [
                    [-0.5773503,  0.3086067, -0.2229113,  0.7003493,  0.1767767],
                    [-0.3849002, -0.5657789,  0.5387023,  0.2150195, -0.4419417],
                    [-0.3849002, -0.5657789, -0.2414872, -0.3010273,  0.6187184],
                    [-0.5773503,  0.3086067, -0.2229113, -0.5713376, -0.4419417],
                    [-0.1924501,  0.4114756,  0.7430376, -0.2150195,  0.4419417],
                ],
                'R' => [
                    [-5.196152, -10.969655, -11.5470054, -10.392305, -10.7772050],
                    [ 0.000000,   2.160247,  -0.3086067,   3.703280,   0.8229512],
                    [ 0.000000,   0.000000,   2.5634798,   6.687339,   4.6068332],
                    [ 0.000000,   0.000000,   0.0000000,  -2.359071,   0.3624615],
                    [ 0.000000,   0.000000,   0.0000000,   0.000000,  -3.9774756],
                ],
            ],
            [
                'A' => [
                    [1,  0, 0,  0,  0],
                    [0,  0, 1,  0,  0],
                    [1, -7, 0,  4,  2],
                    [0,  4, 2, -7,  1],
                    [0,  2, 0,  1, -7],
                ],
                'Q' => [
                    [-0.7071068, -0.5246722,  0.3333983, -0.01889766, -0.3364633],
                    [ 0.0000000,  0.0000000, -0.5298652,  0.63622126, -0.5607722],
                    [-0.7071068,  0.5246722, -0.3333983,  0.01889766,  0.3364633],
                    [ 0.0000000, -0.5996254, -0.6787037, -0.31811063,  0.2803861],
                    [ 0.0000000, -0.2998127,  0.1905133,  0.70236308,  0.6168494],
                ],
                'R' => [
                    [-1.414214,  4.949747,  0.000000, -2.828427, -1.414214],
                    [ 0.000000, -6.670832, -1.199251,  5.996254,  2.548408],
                    [ 0.000000,  0.000000, -1.887273,  3.607846, -2.679094],
                    [ 0.000000,  0.000000,  0.000000,  3.004728, -5.196857],
                    [ 0.000000,  0.000000,  0.000000,  0.000000, -3.364633],
                ],
            ],
        ];
    }

    /**
     * @return array (A, Q, R)
     */
    public function dataProviderForQrDecompositionNonSquareMatricesWithSpecificResults(): array
    {
        return [
            [
                'A' => [
                    [0],
                    [0],
                ],
                'Q' => [
                    [1],
                    [0],
                ],
                'R' => [
                    [0],
                ],
            ],
            [
                'A' => [
                    [1],
                    [1],
                ],
                'Q' => [
                    [-0.7071068],
                    [-0.7071068],
                ],
                'R' => [
                    [-1.414214],
                ],
            ],
            [
                'A' => [
                    [2, -2, -3],
                    [0, -6, -1],
                    [0, 0, 1],
                    [0, 0, 4],
                ],
                'Q' => [
                    [-1.0, 0.0, 0.0],
                    [0.0, -1.0, 0.0],
                    [0.0, 0.0, -1 / \sqrt(17)],
                    [0.0, 0.0, -4 / \sqrt(17)],
                ],
                'R' => [
                    [-2.0, 2.0, 3.0],
                    [0.0, 6.0, 1.0],
                    [0.0, 0.0, -1 * \sqrt(17)],
                ],
            ],
            [
                'A' => [
                    [1,0,0],
                    [0,0,0],
                    [0,0,0],
                    [0,0,0],
                ],
                'Q' => [
                    [-1,0,0],
                    [0,1,0],
                    [0,0,1],
                    [0,0,0],
                ],
                'R' => [
                    [-1,0,0],
                    [0,0,0],
                    [0,0,0],
                ],
            ],
            [
                'A' => [
                    [3, 7, 6, 4, 5, 8],
                    [2, 3, 6, 5, 8, 9],
                    [2, 3, 4, 1, 0, 9],
                    [3, 7, 6, 7, 7, 3],
                    [1, 3, 4, 9, 4, 8],
                ],
                'Q' => [
                    [-0.5773503,  0.3086067, -0.2229113,  0.7003493,  0.1767767],
                    [-0.3849002, -0.5657789,  0.5387023,  0.2150195, -0.4419417],
                    [-0.3849002, -0.5657789, -0.2414872, -0.3010273,  0.6187184],
                    [-0.5773503,  0.3086067, -0.2229113, -0.5713376, -0.4419417],
                    [-0.1924501,  0.4114756,  0.7430376, -0.2150195,  0.4419417],
                ],
                'R' => [
                    [-5.196152, -10.969655, -11.5470054, -10.392305, -10.7772050, -14.818657],
                    [ 0.000000,   2.160247,  -0.3086067,   3.703280,   0.8229512,  -3.497543],
                    [ 0.000000,   0.000000,   2.5634798,   6.687339,   4.6068332,   6.167212],
                    [ 0.000000,   0.000000,   0.0000000,  -2.359071,   0.3624615,   1.394555],
                    [ 0.000000,   0.000000,   0.0000000,   0.000000,  -3.9774756,   5.214913],
                ],
            ],
        ];
    }

    /**
     * @test   QR Decomposition invalid property
     * @throws \Exception
     */
    public function testQRDecompositionInvalidProperty()
    {
        // Given
        $A = MatrixFactory::create([
            [4, 1, -1],
            [1, 2, 1],
            [-1, 1, 2],
        ]);
        $qr = $A->qrDecomposition();

        // Then
        $this->expectException(Exception\MathException::class);

        // When
        $doesNotExist = $qr->doesNotExist;
    }

    /**
     * @test   QR Decomposition solve incorrect type exception
     * @throws \Exception
     */
    public function testQRDecompositionSolveIncorrectTypeException()
    {
        // Given
        $A = MatrixFactory::create([
            [4, 1, -1],
            [1, 2, 1],
            [-1, 1, 2],
        ]);
        $qr = $A->qrDecomposition();

        // And
        $b = new \stdClass();

        // Then
        $this->expectException(Exception\IncorrectTypeException::class);

        // When
        $qr->solve($b);
    }
}
