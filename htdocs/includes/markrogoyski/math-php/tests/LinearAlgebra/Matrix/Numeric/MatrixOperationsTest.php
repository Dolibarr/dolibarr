<?php

namespace MathPHP\Tests\LinearAlgebra\Matrix\Numeric;

use MathPHP\LinearAlgebra\MatrixFactory;
use MathPHP\LinearAlgebra\NumericMatrix;
use MathPHP\LinearAlgebra\NumericSquareMatrix;
use MathPHP\Exception;

class MatrixOperationsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test         trace
     * @dataProvider dataProviderForTrace
     */
    public function testTrace(array $A, $tr)
    {
        // Given
        $A = MatrixFactory::create($A);

        // When
        $trace = $A->trace();

        // Then
        $this->assertEquals($tr, $trace);
    }

    public function dataProviderForTrace(): array
    {
        return [
            [
                [[1]], 1
            ],
            [
                [
                    [1, 2],
                    [2, 3],
                ], 4
            ],
            [
                [
                    [1, 2, 3],
                    [4, 5, 6],
                    [7, 8, 9],
                ], 15
            ],
        ];
    }

    /**
     * @test trace exception - not square
     */
    public function testTraceExceptionNotSquareMatrix()
    {
        // Given
        $A = MatrixFactory::create([
            [1, 2],
            [2, 3],
            [3, 4],
        ]);

        // Then
        $this->expectException(Exception\MatrixException::class);

        // When
        $A->trace();
    }

    /**
     * @test         diagonal
     * @dataProvider dataProviderForDiagonal
     */
    public function testDiagonal(array $A, array $R)
    {
        // Given
        $A = MatrixFactory::create($A);
        $R = MatrixFactory::create($R);

        // When
        $diagonal = $A->diagonal();

        // Then
        $this->assertEquals($R, $diagonal);
    }

    public function dataProviderForDiagonal(): array
    {
        return [
            [
                [
                    [1, 2, 3],
                    [2, 3, 4],
                    [3, 4, 5],
                ],
                [
                    [1, 0, 0],
                    [0, 3, 0],
                    [0, 0, 5],
                ]
            ],
            [
                [
                    [1, 2, 3],
                    [2, 3, 4],
                    [3, 4, 5],
                    [4, 5, 6],
                ],
                [
                    [1, 0, 0],
                    [0, 3, 0],
                    [0, 0, 5],
                    [0, 0, 0],
                ]
            ],
            [
                [
                    [1, 2, 3, 4],
                    [2, 3, 4, 5],
                    [3, 4, 5, 6],
                ],
                [
                    [1, 0, 0, 0],
                    [0, 3, 0, 0],
                    [0, 0, 5, 0],
                ]
            ],
        ];
    }

    /**
     * @test         inverse
     * @dataProvider dataProviderForInverse
     * @param        array $A
     * @param        array $A⁻¹
     * @throws       \Exception
     */
    public function testInverse(array $A, array $A⁻¹)
    {
        // Given
        $A   = MatrixFactory::create($A);
        $A⁻¹ = MatrixFactory::create($A⁻¹);

        // When
        $inverse      = $A->inverse();
        $inverseAgain = $A->inverse();

        // Then
        $this->assertEqualsWithDelta($A⁻¹, $inverse, 0.001); // Test calculation
        $this->assertEqualsWithDelta($A⁻¹, $inverseAgain, 0.001); // Test class attribute
    }

    /**
     * @return array
     */
    public function dataProviderForInverse(): array
    {
        return [
            [
                [
                    [1]
                ],
                [
                    [1]
                ]
            ],
            [
                [
                    [2]
                ],
                [
                    [1 / 2]
                ]
            ],
            [
                [
                    [10]
                ],
                [
                    [1 / 10]
                ]
            ],
            [
                [
                    [-3]
                ],
                [
                    [-1 / 3]
                ]
            ],
            [
                [
                    [4, 7],
                    [2, 6],
                ],
                [
                    [0.6, -0.7],
                    [-0.2, 0.4],
                ],
            ],
            [
                [
                    [4, 3],
                    [3, 2],
                ],
                [
                    [-2, 3],
                    [3, -4],
                ],
            ],
            [
                [
                    [1, 2],
                    [3, 4],
                ],
                [
                    [-2, 1],
                    [3 / 2, -1 / 2],
                ],
            ],
            [
                [
                    [3, 3.5],
                    [3.2, 3.6],
                ],
                [
                    [-9, 8.75],
                    [8, -7.5],
                ],
            ],
            [
                [
                    [1, 2, 3],
                    [0, 4, 5],
                    [1, 0, 6],
                ],
                [
                    [12 / 11, -6 / 11, -1 / 11],
                    [5 / 22, 3 / 22, -5 / 22],
                    [-2 / 11, 1 / 11, 2 / 11],
                ],
            ],
            [
                [
                    [7, 2, 1],
                    [0, 3, -1],
                    [-3, 4, -2],
                ],
                [
                    [-2, 8, -5],
                    [3, -11, 7],
                    [9, -34, 21],
                ],
            ],
            [
                [
                    [2, 0, 0],
                    [0, 2, 0],
                    [0, 0, 2],
                ],
                [
                    [1/2, 0, 0],
                    [0, 1/2, 0],
                    [0, 0, 1/2],
                ],
            ],
            [
                [
                    [3, 6, 6, 8],
                    [4, 5, 3, 2],
                    [2, 2, 2, 3],
                    [6, 8, 4, 2],
                ],
                [
                    [-0.333, 0.667, 0.667, -0.333],
                    [0.167, -2.333, 0.167, 1.417],
                    [0.167, 4.667, -1.833, -2.583],
                    [0.000, -2.000, 1.000, 1.000],
                ],
            ],
            [
                [
                    [2, 0, 0, 0],
                    [0, 2, 0, 0],
                    [0, 0, 2, 0],
                    [0, 0, 0, 2],
                ],
                [
                    [1/2, 0, 0, 0],
                    [0, 1/2, 0, 0],
                    [0, 0, 1/2, 0],
                    [0, 0, 0, 1/2],
                ],
            ],
            [
                [
                    [4, 23, 6, 4, 7],
                    [3, 64, 23, 52, 2],
                    [65, 45, 3, 23, 1],
                    [2, 3, 4, 3, 9],
                    [53, 99, 54, 32, 105],
                ],
                [
                    [-0.142, 0.006, 0.003, -0.338, 0.038],
                    [0.172, -0.012, 0.010, 0.275, -0.035],
                    [-0.856, 0.082, -0.089, -2.344, 0.257],
                    [0.164, -0.001, 0.026, 0.683, -0.070],
                    [0.300, -0.033, 0.027, 0.909, -0.088],
                ],
            ],
        ];
    }

    /**
     * @test         inverse exception - not square
     * @dataProvider dataProviderForInverseExceptionNotSquare
     */
    public function testInverseExceptionNotSquare(array $A)
    {
        // Given
        $A = MatrixFactory::create($A);

        // Then
        $this->expectException(Exception\MatrixException::class);

        // When
        $A->inverse();
    }

    public function dataProviderForInverseExceptionNotSquare(): array
    {
        return [
            [
                [
                    [3, 4, 4],
                    [6, 8, 5],
                ]
            ],
        ];
    }

    /**
     * @test         inverse exception - det is zero
     * @dataProvider dataProviderForInverseExceptionDetIsZero
     */
    public function testInverseExceptionDetIsZero(array $A)
    {
        // Given
        $A = MatrixFactory::create($A);

        // Then
        $this->expectException(Exception\MatrixException::class);

        // When
        $A->inverse();
    }

    public function dataProviderForInverseExceptionDetIsZero(): array
    {
        return [
            [
                [
                    [3, 4],
                    [6, 8],
                ]

            ],
        ];
    }

    /**
     * @test         cofactor
     * @dataProvider dataProviderForCofactor
     */
    public function testCofactor(array $A, int $mᵢ, int $nⱼ, $Cᵢⱼ)
    {
        // Given
        $A = MatrixFactory::create($A);

        // When
        $cofactor = $A->cofactor($mᵢ, $nⱼ);

        // Then
        $this->assertEquals($Cᵢⱼ, $cofactor);
    }

    public function dataProviderForCofactor(): array
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
                0, 1, -38
            ],
            [
                [
                    [1, 4, 7],
                    [3, 0, 5],
                    [-1, 9, 11],
                ],
                1, 2, -13
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
                ], 0, 1, 6
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
                ], 0, 0, 1
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
     * @test cofactor exception - bad row
     */
    public function testCofactorExceptionBadRow()
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
        $A->cofactor(4, 1);
    }

    /**
     * @test cofactor exception - bad column
     */
    public function testCofactorExceptionBadColumn()
    {
        $A = MatrixFactory::create([
            [1, 2, 3],
            [2, 3, 4],
            [3, 4, 5],
        ]);

        $this->expectException(Exception\MatrixException::class);
        $A->cofactor(1, 4);
    }

    /**
     * @test cofactor exception - not square
     */
    public function testCofactorExceptionNotSquare()
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
        $A->cofactor(1, 1);
    }

    /**
     * @test         cofactorMatrix
     * @dataProvider dataProviderForCofactorMatrix
     */
    public function testCofactorMatrix(array $A, array $R)
    {
        // Given
        $A = MatrixFactory::create($A);
        $R = new NumericSquareMatrix($R);

        // When
        $cofactor = $A->cofactorMatrix();

        // Then
        $this->assertEqualsWithDelta($R, $cofactor, 0.00000001);
    }

    public function dataProviderForCofactorMatrix(): array
    {
        return [
            [
                [
                    [6, 4],
                    [3, 2],
                ],
                [
                    [2, -3],
                    [-4, 6],
                ],
            ],
            [
                [
                    [1, 2, 3],
                    [0, 4, 5],
                    [1, 0, 6],
                ],
                [
                    [24, 5, -4],
                    [-12, 3, 2],
                    [-2, -5, 4],
                ],
            ],
            [
                [
                    [-1, 2, 3],
                    [1, 5, 6],
                    [0, 4, 3],
                ],
                [
                    [-9, -3, 4],
                    [6, -3, 4],
                    [-3, 9, -7],
                ],
            ],
            [
                [
                    [3, 65, 23],
                    [98, 35, 86],
                    [5, 2, 10],
                ],
                [
                    [178, -550, 21],
                    [-604, -85, 319],
                    [4785, 1996, -6265],
                ],
            ],
            [
                [
                    [2, -1, 4, 3, 2, 3, 3, 4, 4],
                    [-1, 2, 3, 2, 1, 2, 2, 3, 3],
                    [4, 3, 2, 1, 2, 3, 3, 4, 4],
                    [2, 1, 2, 1, 2, 1, 1, 2, 2],
                    [3, 2, 1, 2, 1, 2, 2, 3, 3],
                    [3, 2, 3, 2, 1, 2, 2, 3, 3],
                    [3, 2, 3, 2, 1, 2, 2, 1, 2],
                    [4, 3, 4, 3, 2, 3, 1, 2, 2],
                    [4, 3, 4, 3, 2, 3, 2, 2, 2],
                ],
                [
                    [0,   128,     0,     0,     0,  -128,     0,     0,     0,],
                    [128,   -80,     0,   -32,   -32,   -16,     0,    32,   -64,],
                    [0,     0,     0,   256,     0,  -256,     0,     0,     0,],
                    [0,   -32,     0,   -64,  -320,   352,     0,    64,  -128,],
                    [0,   -32,   256,  -320,   -64,    96,     0,    64,  -128,],
                    [-128,   -16,  -256,    96,   352,   304,     0,  -352,   192,],
                    [0,     0,     0,     0,    -0,     0,     0,   512,  -512,],
                    [-0,    32,     0,    64,    64,  -352,   512,   192,  -384,],
                    [0,   -64,     0,  -128,  -128,   192,  -512,  -384,   768,],
                ],
            ],
            [
                [
                    [0, 1, 4, 3, 2, 3, 3, 4, 4],
                    [1, 0, 3, 2, 1, 2, 2, 3, 3],
                    [4, 3, 0, 1, 2, 3, 3, 4, 4],
                    [3, 2, 1, 0, 1, 2, 2, 3, 3],
                    [2, 1, 2, 1, 0, 1, 1, 2, 2],
                    [3, 2, 3, 2, 1, 0, 2, 3, 3],
                    [3, 2, 3, 2, 1, 2, 0, 1, 2],
                    [4, 3, 4, 3, 2, 3, 1, 0, 2],
                    [4, 3, 4, 3, 2, 3, 2, 2, 0],
                ],
                [
                    [-640.0000,   736.0000,   96.0000,     0.0000,  -224.0000,   96.0000,     0.0000,   64.0000,   64.0000],
                    [ 736.0000, -1472.0000,    0.0000,     0.0000,   736.0000,    0.0000,     0.0000,    0.0000,    0.0000],
                    [  96.0000,     0.0000, -640.0000,   736.0000,  -224.0000,   96.0000,     0.0000,   64.0000,   64.0000],
                    [  -0.0000,     0.0000,  736.0000, -1472.0000,   736.0000,    0.0000,     0.0000,    0.0000,    0.0000],
                    [-224.0000,   736.0000, -224.0000,   736.0000, -2544.0000,  512.0000,   736.0000, -272.0000,   96.0000],
                    [  96.0000,     0.0000,   96.0000,     0.0000,   512.0000, -640.0000,     0.0000,   64.0000,   64.0000],
                    [  -0.0000,     0.0000,    0.0000,     0.0000,   736.0000,    0.0000, -1472.0000,  736.0000,    0.0000],
                    [  64.0000,     0.0000,   64.0000,     0.0000,  -272.0000,   64.0000,   736.0000, -816.0000,  288.0000],
                    [  64.0000,     0.0000,   64.0000,     0.0000,    96.0000,   64.0000,     0.0000,  288.0000, -448.0000],
                ],
            ],
            [
                [
                    [-1, 2, 3],
                    [1, 5, 6],
                    [0, 4, 3],
                ],
                [
                    [-9, -3, 4],
                    [6, -3, 4],
                    [-3, 9, -7],
                ],
            ],
            [
                [
                    [0, 1, 4],
                    [1, 0, 3],
                    [4, 3, 0],
                ],
                [
                    [-9, 12, 3],
                    [12, -16, 4],
                    [3, 4, -1],
                ],
            ],
        ];
    }

    /**
     * @test cofactorMatrix exception - not square
     */
    public function testCofactorMatrixExceptionNotSquare()
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
        $A->cofactorMatrix();
    }

    /**
     * @test cofactorMatrix exception - too small
     */
    public function testCofactorMatrixExceptionTooSmall()
    {
        // Given
        $A = MatrixFactory::create([
            [1],
        ]);

        // Then
        $this->expectException(Exception\MatrixException::class);

        // When
        $A->cofactorMatrix();
    }

    /**
     * @test         meanDeviation
     * @dataProvider dataProviderForMeanDeviation
     */
    public function testMeanDeviation(array $A, array $B)
    {
        // Given
        $A = MatrixFactory::create($A);
        $B = MatrixFactory::create($B);

        // When
        $meanDeviation = $A->meanDeviation();

        // Then
        $this->assertEquals($B, $meanDeviation);
    }

    public function dataProviderForMeanDeviation(): array
    {
        return [
            // Test data from: http://www.maths.manchester.ac.uk/~mkt/MT3732%20(MVA)/Intro.pdf
            [
                [
                    [4, -1, 3],
                    [1, 3, 5],
                ],
                [
                    [2, -3, 1],
                    [-2, 0, 2],
                ],
            ],
            // Test data from Linear Algebra and Its Applications (Lay)
            [
                [
                    [1, 4, 7, 8],
                    [2, 2, 8, 4],
                    [1, 13, 1, 5],
                ],
                [
                    [-4, -1, 2, 3],
                    [-2, -2, 4, 0],
                    [-4, 8, -4, 0],
                ],
            ],
            [
                [
                    [19, 22, 6, 3, 2, 20],
                    [12, 6, 9, 15, 13, 5],
                ],
                [
                    [7, 10, -6, -9, -10, 8],
                    [2, -4, -1, 5, 3, -5],
                ],
            ],
        ];
    }

    /**
     * @test         meanDeviation column as variables
     * @dataProvider dataProviderForMeanDeviationColumnsAsVariables
     */
    public function testMeanDeviationColumnsAsVariables(array $A, array $B)
    {
        // Given
        $A = MatrixFactory::create($A);
        $B = MatrixFactory::create($B);

        // When
        $meanDeviation = $A->meanDeviation('columns');

        // Then
        $this->assertEquals($B, $meanDeviation);
    }

    public function dataProviderForMeanDeviationColumnsAsVariables(): array
    {
        return [
            [
                [
                    [3, 5, 1],
                    [9, 1, 4],
                ],
                [
                    [-3, 2, -1.5],
                    [3, -2, 1.5],
                ],
            ],
            [
                [
                    [4, -1, 3],
                    [1, 3, 5],
                ],
                [
                    [1.5, -2, -1],
                    [-1.5, 2, 1],
                ],
            ],
            [
                [
                    [1, 4, 7, 8],
                    [2, 2, 8, 4],
                    [1, 13, 1, 5],
                ],
                [
                    [-1 / 3, -7 / 3,  5 / 3,  7 / 3],
                    [ 2 / 3, -13 / 3,  8 / 3, -5 / 3],
                    [-1 / 3,  20 / 3, -13 / 3,  -2 / 3],
                ],
            ],
            [
                [
                    [90, 60, 90],
                    [90, 90, 30],
                    [60, 60, 60],
                    [60, 60, 90],
                    [30, 30, 30],
                ],
                [
                    [24, 0, 30],
                    [24, 30, -30],
                    [-6, 0, 0],
                    [-6, 0, 30],
                    [-36, -30, -30],
                ],
            ],
        ];
    }

    /**
     * @test   meanDeviation exception is direction is not rows or columns
     * @throws \Exception
     */
    public function testMeanDeviationDirectionException()
    {
        // Given
        $A = MatrixFactory::create([
            [1, 2, 3],
            [2, 3, 4],
        ]);

        // Then
        $this->expectException(Exception\BadParameterException::class);

        // When
        $A->meanDeviation('diagonal_direction');
    }

    /**
     * @test         covarianceMatrix
     * @dataProvider dataProviderForCovarianceMatrix
     */
    public function testCovarianceMatrix(array $A, array $S)
    {
        // Given
        $A = MatrixFactory::create($A);

        // When
        $covarianceMatrix = $A->covarianceMatrix();

        // Then
        $this->assertEqualsWithDelta($S, $covarianceMatrix->getMatrix(), 0.0001);
    }

    public function dataProviderForCovarianceMatrix(): array
    {
        return [
            // Test data from Linear Algebra and Its Applications (Lay)
            [
                [
                    [1, 4, 7, 8],
                    [2, 2, 8, 4],
                    [1, 13, 1, 5],
                ],
                [
                    [10, 6, 0],
                    [6, 8, -8],
                    [0, -8, 32],
                ],
            ],
            [
                [
                    [19, 22, 6, 3, 2, 20],
                    [12, 6, 9, 15, 13, 5],
                ],
                [
                    [86, -27],
                    [-27, 16],
                ],
            ],
            // Test data from: http://www.itl.nist.gov/div898/handbook/pmc/section5/pmc541.htm
            [
                [
                    [4, 4.2, 3.9, 4.3, 4.1],
                    [2, 2.1, 2, 2.1, 2.2],
                    [.6, .59, .58, .62, .63]
                ],
                [
                    [0.025, 0.0075, 0.00175],
                    [0.0075, 0.007, 0.00135],
                    [0.00175, 0.00135, 0.00043],
                ],
            ],
            [
                [
                    [2.5, 0.5, 2.2, 1.9, 3.1, 2.3, 2, 1, 1.5, 1.1],
                    [2.4, 0.7, 2.9, 2.2, 3.0, 2.7, 1.6, 1.1, 1.6, 0.9],
                ],
                [
                    [0.616555556, 0.615444444],
                    [0.615444444, 0.716555556],
                ],
            ],
            // Test data from: https://www.mathworks.com/help/matlab/ref/cov.html
            [
                [
                    [5, 1, 4],
                    [0, -5, 9],
                    [3, 7, 8],
                    [7, 3, 10],
                ],
                [
                    [4.3333, 8.8333, -3.0000, 5.6667],
                    [8.8333, 50.3333, 6.5000, 24.1667],
                    [-3.0000, 6.5000, 7.0000, 1.0000],
                    [5.6667, 24.1667, 1.0000, 12.3333],
                ],
            ],
            // Test data from: http://stats.seandolinar.com/making-a-covariance-matrix-in-r/
            [
                [
                    [1, 2, 3, 4, 5, 6],
                    [2, 3, 5, 6, 1, 9],
                    [3, 5, 5, 5, 10, 8],
                    [10, 20, 30,40, 50, 55],
                    [7, 8, 9, 4, 6, 10],
                ],
                [
                    [ 3.5,  3.000000,  4.0,  32.500000, 0.400000],
                    [ 3.0,  8.666667,  0.4,  25.333333, 2.466667],
                    [ 4.0,  0.400000,  6.4,  38.000000, 0.400000],
                    [32.5, 25.333333, 38.0, 304.166667, 1.333333],
                    [ 0.4,  2.466667,  0.4,   1.333333, 4.666667],
                ],
            ],

        ];
    }

    /**
     * @test         covarianceMatrix columns as variables
     * @dataProvider dataProviderForCovarianceMatrixColumnsAsVariables
     */
    public function testCovarianceMatrixColumnsAsVariables(array $A, array $S)
    {
        // Given
        $A         = MatrixFactory::create($A);
        $direction = NumericMatrix::COLUMNS;

        // When
        $covarianceMatrix = $A->covarianceMatrix($direction);

        // Then
        $this->assertEqualsWithDelta($S, $covarianceMatrix->getMatrix(), 0.0001);
    }

    /**
     * Data generated with R cov(A)
     * @return array
     */
    public function dataProviderForCovarianceMatrixColumnsAsVariables(): array
    {
        return [
            [
                [
                    [90, 60, 90],
                    [90, 90, 30],
                    [60, 60, 60],
                    [60, 60, 90],
                    [30, 30, 30],
                ],
                [
                    [630, 450, 225],
                    [450, 450, 0],
                    [225, 0, 900],
                ],
            ],
            [
                [
                    [2, -2],
                    [8, -1],
                    [6, 0],
                    [4, 1],
                    [10, 2],
                ],
                [
                    [10, 3],
                    [3, 2.5],
                ],
            ],
            [
                [
                    [1, 4, 7, 8],
                    [2, 2, 8, 4],
                    [1, 13, 1, 5],
                ],
                [
                    [0.3333333,  -2.166667,  1.333333,  -0.8333333],
                    [-2.1666667, 34.333333, -22.166667, -1.3333333],
                    [1.3333333,  -22.166667, 14.333333, 1.1666667],
                    [-0.8333333, -1.333333,  1.166667,  4.3333333],
                ],
            ],
            [
                [
                    [19, 22, 6, 3, 2, 20],
                    [12, 6, 9, 15, 13, 5],
                ],
                [
                    [ 24.5,  56, -10.5, -42, -38.5,  52.5],
                    [ 56.0, 128, -24.0, -96, -88.0, 120.0],
                    [-10.5, -24,   4.5,  18,  16.5, -22.5],
                    [-42.0, -96,  18.0,  72,  66.0, -90.0],
                    [-38.5, -88,  16.5,  66,  60.5, -82.5],
                    [ 52.5, 120, -22.5, -90, -82.5, 112.5],
                ],
            ],
        ];
    }

    /**
     * @test   covarianceMatrix exception is direction is not rows or columns
     * @throws \Exception
     */
    public function testCovarianceMatrixDirectionException()
    {
        // Given
        $A = MatrixFactory::create([
            [1, 2, 3],
            [2, 3, 4],
        ]);

        // Then
        $this->expectException(Exception\BadParameterException::class);

        // When
        $covarianceMatrix = $A->covarianceMatrix('invalid_direction');
    }

    /**
     * @test         adjugate returns the expected SquareMatrix
     * @dataProvider dataProviderForAdjugate
     * @param        array $A
     * @param        array $expected
     */
    public function testAdjugate(array $A, array $expected)
    {
        // Given
        $A        = MatrixFactory::create($A);
        $expected = MatrixFactory::create($expected);

        // When
        $adj⟮A⟯ = $A->adjugate();

        // Then
        $this->assertEquals($expected, $adj⟮A⟯);
        $this->assertEquals($expected->getMatrix(), $adj⟮A⟯->getMatrix());
    }

    public function dataProviderForAdjugate(): array
    {
        return [
            [
                [
                    [0],
                ],
                [
                    [1],
                ],
            ],
            [
                [
                    [1],
                ],
                [
                    [1],
                ],
            ],
            [
                [
                    [5],
                ],
                [
                    [1],
                ],
            ],
            // Data calculated using online calculator: http://www.dcode.fr/adjoint-matrix
            [
                [
                    [1, 2],
                    [3, 4],
                ],
                [
                    [4, -2],
                    [-3, 1],
                ],
            ],
            [
                [
                    [4, -2],
                    [-3, 1],
                ],
                [
                    [1, 2],
                    [3, 4],
                ],
            ],
            [
                [
                    [1, 1, 1],
                    [1, 1, 1],
                    [1, 1, 1],
                ],
                [
                    [0, 0, 0],
                    [0, 0, 0],
                    [0, 0, 0],
                ],
            ],
            [
                [
                    [0, 0, 0],
                    [0, 0, 0],
                    [0, 0, 0],
                ],
                [
                    [0, 0, 0],
                    [0, 0, 0],
                    [0, 0, 0],
                ],
            ],
            [
                [
                    [1, 2, 3],
                    [4, 5, 6],
                    [7, 8, 9],
                ],
                [
                    [-3, 6, -3],
                    [6, -12, 6],
                    [-3, 6, -3],
                ],
            ],
            [
                [
                    [1, 2, 3],
                    [4, 5, 6],
                    [7, 8, 9],
                ],
                [
                    [-3, 6, -3],
                    [6, -12, 6],
                    [-3, 6, -3],
                ],
            ],
            [
                [
                    [1, 3, 4],
                    [3, 4, 4],
                    [4, 3, 2],
                ],
                [
                    [-4, 6, -4],
                    [10, -14, 8],
                    [-7, 9, -5],
                ],
            ],
            [
                [
                    [4, 8, 5, 4],
                    [3, 5, 7, 9],
                    [0, 8, 12, 5],
                    [7, 9, 0, 3],
                ],
                [
                    [-645, 39, 246, 333],
                    [403, -17, -158, -223],
                    [-392, 28, 136, 212],
                    [296, -40, -100, -152],
                ],
            ],
            [
                [
                    [2, -1, 4, 3, 2, 3, 3, 4, 4],
                    [-1, 2, 3, 2, 1, 2, 2, 3, 3],
                    [4, 3, 2, 1, 2, 3, 3, 4, 4],
                    [2, 1, 2, 1, 2, 1, 1, 2, 2],
                    [3, 2, 1, 2, 1, 2, 2, 3, 3],
                    [3, 2, 3, 2, 1, 2, 2, 3, 3],
                    [3, 2, 3, 2, 1, 2, 2, 1, 2],
                    [4, 3, 4, 3, 2, 3, 1, 2, 2],
                    [4, 3, 4, 3, 2, 3, 2, 2, 2],
                ],
                [
                    [0, 128, 0, 0, 0, -128, 0, 0, -0],
                    [128, -80, 0, -32, -32, -16, 0, 32, -64],
                    [0, 0, 0, 0, 256, -256, 0, 0, -0],
                    [0, -32, 256, -64, -320, 96, 0, 64, -128],
                    [0, -32, 0, -320, -64, 352, 0, 64, -128],
                    [-128, -16, -256, 352, 96, 304, -0, -352, 192],
                    [-0, 0, -0, 2.8421709430404E-14, 0, -0, 0, 512, -512],
                    [-0, 32, -0, 64, 64, -352, 512, 192, -384],
                    [0, -64, 0, -128, -128, 192, -512, -384, 768],
                ]
            ],
            [
                [
                    [0, 1, 4, 3, 2, 3, 3, 4, 4],
                    [1, 0, 3, 2, 1, 2, 2, 3, 3],
                    [4, 3, 0, 1, 2, 3, 3, 4, 4],
                    [3, 2, 1, 0, 1, 2, 2, 3, 3],
                    [2, 1, 2, 1, 0, 1, 1, 2, 2],
                    [3, 2, 3, 2, 1, 0, 2, 3, 3],
                    [3, 2, 3, 2, 1, 2, 0, 1, 2],
                    [4, 3, 4, 3, 2, 3, 1, 0, 2],
                    [4, 3, 4, 3, 2, 3, 2, 2, 0],
                ],
                [
                    [-640, 736, 96, 0, -224, 96, 0, 64, 64],
                    [736, -1472, 0, 0, 736, 0, 0, 0, 0],
                    [96, 0, -640, 736, -224, 96, 0, 64, 64],
                    [0, 0, 736, -1472, 736, 0, 0, 0, 0, ],
                    [-224, 736, -224, 736, -2544, 512, 736, -272, 96],
                    [96, 0, 96, 0, 512, -640, 0, 64, 64],
                    [0, 0, 0, 0, 736, 0, -1472, 736, 0],
                    [64, 0, 64, 0, -272, 64, 736, -816, 288],
                    [64, 0, 64, 0, 96, 64, 0, 288, -448],
                ]
            ],
        ];
    }

    /**
     * @test adjugate throws an Exception\MatrixException if the matrix is not square
     */
    public function testAdjugateSquareMatrixException()
    {
        // Given
        $A = [
            [1, 2, 3],
            [2, 3, 4],
        ];
        $A = MatrixFactory::create($A);

        // Then
        $this->expectException(Exception\MatrixException::class);

        // When
        $adj⟮A⟯ = $A->adjugate();
    }

    /**
     * @test         rank returns the expected value
     * @dataProvider dataProviderForRank
     */
    public function testRank(array $A, $expected)
    {
        // Given
        $A = MatrixFactory::create($A);

        // When
        $rank = $A->rank();

        $this->assertEquals($expected, $rank);
    }

    public function dataProviderForRank(): array
    {
        return [
            [
                [
                    [0]
                ], 0
            ],
            [
                [
                    [1]
                ], 1
            ],
            [
                [
                    [2]
                ], 1
            ],
            [
                [
                    [-2]
                ], 1
            ],
            [
                [
                    [1, 2, 3],
                    [2, 3, 4],
                    [3, 4, 5],
                ], 2
            ],
            [
                [
                    [1, 3, -1],
                    [0, 1, 7],
                ], 2
            ],
            [
                [
                    [1, 2, 1],
                    [-2, -3, 1],
                    [3, 5, 0],
                ], 2
            ],
            [
                [
                    [0, 3, -6, 6, 4, -5],
                    [3, -7, 8, -5, 8, 9],
                    [3, -9, 12, -9, 6, 15],
                ], 3
            ],
            [
                [
                    [0, 2, 8, -7],
                    [2, -2, 4, 0],
                    [-3, 4, -2, -5],
                ], 3
            ],
            [
                [
                    [1, -2, 3, 9],
                    [-1, 3, 0, -4],
                    [2, -5, 5, 17],
                ], 3
            ],
            [
                [
                    [1, 0, -2, 1, 0],
                    [0, -1, -3, 1, 3],
                    [-2, -1, 1, -1, 3],
                    [0, 3, 9, 0, -12],
                ], 3
            ],
            [
                [
                    [1, 1, 4, 1, 2],
                    [0, 1, 2, 1, 1],
                    [0, 0, 0, 1, 2],
                    [1, -1, 0, 0, 2],
                    [2, 1, 6, 0, 1],
                ], 3
            ],
            [
                [
                    [1, 2, 0, -1, 1, -10],
                    [1, 3, 1, 1, -1, -9],
                    [2, 5, 1, 0, 0, -19],
                    [3, 6, 0, 0, -6, -27],
                    [1, 5, 3, 5, -5, -7],
                ], 3
            ],
            [
                [
                    [-4, 3, 1, 5, -8],
                    [6, 0, 9, 2, 6],
                    [-1, 4, 4, 0, 2],
                    [8, -1, 3, 4, 0],
                    [5, 9, -7, -7, 1],
                ], 5
            ],
            [
                [
                    [4, 7],
                    [2, 6],
                ], 2
            ],
            [
                [
                    [4, 3],
                    [3, 2],
                ], 2
            ],
            [
                [
                    [1, 2],
                    [3, 4],
                ], 2
            ],
            [
                [
                    [1, 2, 3],
                    [0, 4, 5],
                    [1, 0, 6],
                ], 3
            ],
            [
                [
                    [7, 2, 1],
                    [0, 3, -1],
                    [-3, 4, -2],
                ], 3
            ],
            [
                [
                    [3, 6, 6, 8],
                    [4, 5, 3, 2],
                    [2, 2, 2, 3],
                    [6, 8, 4, 2],
                ], 4
            ],
            [
                [
                    [0, 0],
                    [0, 1],
                ], 1
            ],
            [
                [
                    [1, 1, 1, 1, 1],
                    [0, 1, 1, 1, 1],
                    [0, 0, 0, 0, 1],
                ], 3
            ],
            [
                [
                    [0, 0],
                    [1, 1],
                    [-1, 0],
                    [0, -1],
                    [0, 0],
                    [0, 0],
                    [0, 0],
                    [0, 0],
                    [1, 1],
                ], 2
            ],
            [
                [
                    [1,  2,  3,  4,  3,  1],
                    [2,  4,  6,  2,  6,  2],
                    [3,  6, 18,  9,  9, -6],
                    [4,  8, 12, 10, 12,  4],
                    [5, 10, 24, 11, 15, -4],
                ], 3
            ],
            [
                [
                    [1, 2, 3, 4, 3, 1],
                    [2, 4, 6, 2, 6, 2],
                    [3, 6, 18, 9, 9, -6],
                    [4, 8, 12, 10, 12, 4],
                    [5, 10, 24, 11, 15, -4]
                ], 3
            ],
            [
                [
                    [0, 1],
                    [1, 2],
                    [0, 5],
                ], 2
            ],
            [
                [
                    [1, 0, 1, 0, 1, 0],
                    [1, 0, 1, 0, 0, 1],
                    [1, 0, 0, 1, 1, 0],
                    [1, 0, 0, 1, 0, 1],
                    [0, 1, 0, 1, 1, 0],
                    [0, 1, 0, 1, 0, 1],
                    [0, 1, 1, 0, 1, 0],
                    [0, 1, 1, 0, 0, 1],
                ], 4
            ],
        ];
    }
}
