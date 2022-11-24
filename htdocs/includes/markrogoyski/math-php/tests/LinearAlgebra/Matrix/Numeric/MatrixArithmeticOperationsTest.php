<?php

namespace MathPHP\Tests\LinearAlgebra\MatrixNumeric;

use MathPHP\LinearAlgebra\MatrixFactory;
use MathPHP\LinearAlgebra\NumericMatrix;
use MathPHP\LinearAlgebra\NumericSquareMatrix;
use MathPHP\LinearAlgebra\Vector;
use MathPHP\Exception;

class MatrixArithmeticOperationsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test         add
     * @dataProvider dataProviderForAdd
     */
    public function testAdd(array $A, array $B, array $R)
    {
        // Given
        $A  = MatrixFactory::create($A);
        $B  = MatrixFactory::create($B);
        $R  = MatrixFactory::create($R);

        // When
        $R2 = $A->add($B);

        // Then
        $this->assertEquals($R, $R2);
    }

    public function dataProviderForAdd(): array
    {
        return [
            [
                [
                    [1, 2, 3],
                    [2, 3, 4],
                    [3, 4, 5],
                ],
                [
                    [1, 1, 1],
                    [1, 1, 1],
                    [1, 1, 1],
                ],
                [
                    [2, 3, 4],
                    [3, 4, 5],
                    [4, 5, 6],
                ],
            ],
            [
                [
                    [0, 1, 2],
                    [9, 8, 7],
                ],
                [
                    [6, 5, 4],
                    [3, 4, 5],
                ],
                [
                    [ 6,  6,  6],
                    [12, 12, 12],
                ],
            ],
        ];
    }

    /**
     * @test   add exception for rows
     * @throws \Exception
     */
    public function testAddExceptionRows()
    {
        // Given
        $A = MatrixFactory::create([
            [1, 2],
            [2, 3],
        ]);
        $B = MatrixFactory::create([
            [1, 2]
        ]);

        // Then
        $this->expectException(Exception\MatrixException::class);

        //  WHen
        $A->add($B);
    }

    /**
     * @test   add exception for columns
     * @throws \Exception
     */
    public function testAddExceptionColumns()
    {
        // Given
        $A = MatrixFactory::create([
            [1, 2, 3],
            [2, 3, 4],
        ]);
        $B = MatrixFactory::create([
            [1, 2],
            [2, 3],
        ]);

        // Then
        $this->expectException(Exception\MatrixException::class);

        // When
        $A->add($B);
    }

    /**
     * @test         directSum
     * @dataProvider dataProviderForDirectSum
     */
    public function testDirectSum(array $A, array $B, array $R)
    {
        // Given
        $A  = MatrixFactory::create($A);
        $B  = MatrixFactory::create($B);
        $R  = MatrixFactory::create($R);

        // When
        $R2 = $A->directSum($B);

        // Then
        $this->assertEquals($R, $R2);
    }

    public function dataProviderForDirectSum(): array
    {
        return [
            [
                [
                    [1, 3, 2],
                    [2, 3, 1],
                ],
                [
                    [1, 6],
                    [0, 1],
                ],
                [
                    [1, 3, 2, 0, 0],
                    [2, 3, 1, 0, 0],
                    [0, 0, 0, 1, 6],
                    [0, 0, 0, 0, 1],
                ],
            ],
        ];
    }

    /**
     * @test         kroneckerSum returns the expected SquareMatrix
     * @dataProvider dataProviderKroneckerSum
     * @param        array A
     * @param        array B
     * @param        array $expected
     */
    public function testKroneckerSum(array $A, array $B, array $expected)
    {
        // Given
        $A   = new NumericSquareMatrix($A);
        $B   = new NumericSquareMatrix($B);
        $R   = new NumericSquareMatrix($expected);

        // When
        $A⊕B = $A->kroneckerSum($B);

        // Then
        $this->assertEquals($R, $A⊕B);
        $this->assertEquals($R->getMatrix(), $A⊕B->getMatrix());
        $this->assertInstanceOf(NumericSquareMatrix::class, $A⊕B);
    }

    public function dataProviderKroneckerSum(): array
    {
        return [
            [
                [
                    [1, 2],
                    [3, 4],
                ],
                [
                    [1, 2, 3],
                    [4, 5, 6],
                    [7, 8, 9],
                ],
                [
                    [2, 2, 3, 2, 0, 0],
                    [4, 6, 6, 0, 2, 0],
                    [7, 8,10, 0, 0, 2],
                    [3, 0, 0, 5, 2, 3],
                    [0, 3, 0, 4, 9, 6],
                    [0, 0, 3, 7, 8,13],
                ],
            ],
            [
                [
                    [1, 1],
                    [1, 1],
                ],
                [
                    [1, 1],
                    [1, 1],
                ],
                [
                    [2, 1, 1, 0],
                    [1, 2, 0, 1],
                    [1, 0, 2, 1],
                    [0, 1, 1, 2],
                ],
            ],
            [
                [
                    [1, 1],
                    [1, 1],
                ],
                [
                    [2, 3],
                    [4, 5],
                ],
                [
                    [3, 3, 1, 0],
                    [4, 6, 0, 1],
                    [1, 0, 3, 3],
                    [0, 1, 4, 6],
                ],
            ],
            [
                [
                    [2, 3],
                    [4, 5],
                ],
                [
                    [1, 1],
                    [1, 1],
                ],
                [
                    [3, 1, 3, 0],
                    [1, 3, 0, 3],
                    [4, 0, 6, 1],
                    [0, 4, 1, 6],
                ],
            ],
        ];
    }

    /**
     * @test         kronecerSum throws a MatrixException if one of the matrices is not square
     * @dataProvider dataProviderForKroneckerSumSquareMatrixException
     * @param        array A
     * @param        array B
     */
    public function testKroneckerSumSquareMatrixException($A, $B)
    {
        // Given
        $A   = new NumericMatrix($A);
        $B   = new NumericMatrix($B);

        // Then
        $this->expectException(Exception\MatrixException::class);

        // When
        $A⊕B = $A->kroneckerSum($B);
    }

    public function dataProviderForKroneckerSumSquareMatrixException(): array
    {
        return [
            [
                [
                    [1, 2, 3],
                    [2, 3, 4],
                ],
                [
                    [1, 2],
                    [2, 3],
                ]
            ],
            [
                [
                    [1, 2],
                    [2, 3],
                ],
                [
                    [1, 2, 3],
                    [2, 3, 4],
                ],
            ],
            [
                [
                    [1, 2],
                    [2, 3],
                    [4, 5],
                ],
                [
                    [1, 2, 3],
                    [2, 3, 4],
                ],
            ],
        ];
    }

    /**
     * @test         subtract
     * @dataProvider dataProviderForSubtract
     */
    public function testSubtract(array $A, array $B, array $R)
    {
        // Given
        $A  = MatrixFactory::create($A);
        $B  = MatrixFactory::create($B);
        $R  = MatrixFactory::create($R);

        // When
        $R2 = $A->subtract($B);

        // Then
        $this->assertEquals($R, $R2);
    }

    public function dataProviderForSubtract(): array
    {
        return [
            [
                [
                    [1, 2, 3],
                    [2, 3, 4],
                    [3, 4, 5],
                ],
                [
                    [1, 1, 1],
                    [1, 1, 1],
                    [1, 1, 1],
                ],
                [
                    [ 0, 1, 2 ],
                    [ 1, 2, 3 ],
                    [ 2, 3, 4 ],
                ],
            ],
            [
                [
                    [0, 1, 2],
                    [9, 8, 7],
                ],
                [
                    [6, 5, 4],
                    [3, 4, 5],
                ],
                [
                    [ -6, -4, -2 ],
                    [  6,  4,  2 ],
                ],
            ],
        ];
    }

    /**
     * @test   subtract exception for rows
     * @throws \Exception
     */
    public function testSubtractExceptionRows()
    {
        // Given
        $A = MatrixFactory::create([
            [1, 2],
            [2, 3],
        ]);
        $B = MatrixFactory::create([
            [1, 2]
        ]);

        // Then
        $this->expectException(Exception\MatrixException::class);

        // When
        $A->subtract($B);
    }

    /**
     * @test   subtract exception for columns
     * @throws \Exception
     */
    public function testSubtractExceptionColumns()
    {
        // Given
        $A = MatrixFactory::create([
            [1, 2, 3],
            [2, 3, 4],
        ]);
        $B = MatrixFactory::create([
            [1, 2],
            [2, 3],
        ]);

        // Then
        $this->expectException(Exception\MatrixException::class);

        // When
        $A->subtract($B);
    }

    /**
     * @test         multiplication
     * @dataProvider dataProviderForMultiply
     * @param        array $A
     * @param        array $B
     * @param        array $expected
     * @throws       \Exception
     */
    public function testMultiply(array $A, array $B, array $expected)
    {
        // Given
        $A        = MatrixFactory::create($A);
        $B        = MatrixFactory::create($B);
        $expected = MatrixFactory::create($expected);

        // When
        $R = $A->multiply($B);

        // Then
        $this->assertEquals($expected, $R);
        $this->assertTrue($R->isEqual($expected));
    }

    public function dataProviderForMultiply(): array
    {
        return [
            [
                [
                    [0]
                ],
                [
                    [0]
                ],
                [
                    [0]
                ],
            ],
            [
                [
                    [0]
                ],
                [
                    [1]
                ],
                [
                    [0]
                ],
            ],
            [
                [
                    [1]
                ],
                [
                    [0]
                ],
                [
                    [0]
                ],
            ],
            [
                [
                    [1]
                ],
                [
                    [2]
                ],
                [
                    [2]
                ],
            ],
            [
                [
                    [2]
                ],
                [
                    [1]
                ],
                [
                    [2]
                ],
            ],
            [
                [
                    [2]
                ],
                [
                    [3]
                ],
                [
                    [6]
                ],
            ],
            [
                [
                    [3]
                ],
                [
                    [2]
                ],
                [
                    [6]
                ],
            ],
            [
                [
                    [1]
                ],
                [
                    [1, 2, 3]
                ],
                [
                    [1, 2, 3]
                ],
            ],
            [
                [
                    [0]
                ],
                [
                    [1, 2, 3]
                ],
                [
                    [0, 0, 0]
                ],
            ],
            [
                [
                    [4]
                ],
                [
                    [1, 2, 3]
                ],
                [
                    [4, 8, 12]
                ],
            ],
            [
                [
                    [4]
                ],
                [
                    [1, -3, 2]
                ],
                [
                    [4, -12, 8]
                ],
            ],
            [
                [
                    [1, -3, 2]
                ],
                [
                    [2],
                    [-1],
                    [0],
                ],
                [
                    [5],
                ],
            ],
            [
                [
                    [1],
                    [-3],
                    [2],
                ],
                [
                    [-1, -2],
                ],
                [
                    [-1, -2],
                    [3, 6],
                    [-2, -4],
                ],
            ],
            [
                [
                    [0, 1, 0]
                ],
                [
                    [1, -2, 2],
                    [4, 2, 0],
                    [1, 2, 3],
                ],
                [
                    [4, 2, 0],
                ],
            ],
            [
                [
                    [1, 2],
                    [3, 4],
                ],
                [
                    [0, 0],
                    [0, 0],
                ],
                [
                    [0, 0],
                    [0, 0],
                ],
            ],
            [
                [
                    [0, 0],
                    [0, 0],
                ],
                [
                    [1, 2],
                    [3, 4],
                ],
                [
                    [0, 0],
                    [0, 0],
                ],
            ],
            [
                [
                    [0, 1],
                    [0, 0],
                ],
                [
                    [0, 0],
                    [1, 0],
                ],
                [
                    [1, 0],
                    [0, 0],
                ],
            ],
            [
                [
                    [0, 0],
                    [1, 0],
                ],
                [
                    [0, 1],
                    [0, 0],
                ],
                [
                    [0, 0],
                    [0, 1],
                ],
            ],
            [
                [
                    [1, 2],
                    [3, 4],
                ],
                [
                    [5, 6],
                    [7, 8],
                ],
                [
                    [19, 22],
                    [43, 50],
                ],
            ],
            [
                [
                    [1, 2],
                    [3, 4],
                ],
                [
                    [2, 0],
                    [1, 2],
                ],
                [
                    [4, 4],
                    [10, 8],
                ],
            ],
            [
                [
                    [2, 0],
                    [1, 2],
                ],
                [
                    [1, 2],
                    [3, 4],
                ],
                [
                    [2, 4],
                    [7, 10],
                ],
            ],
            [
                [
                    [ 1, 0, -2 ],
                    [ 0, 3, -1 ],
                ],
                [
                    [  0,  3 ],
                    [ -2, -1 ],
                    [  0,  4 ],
                ],
                [
                    [  0, -5 ],
                    [ -6, -7 ],
                ],
            ],
            [
                [
                    [ 2,  3 ],
                    [ 1, -5 ],
                ],
                [
                    [ 4,  3, 6 ],
                    [ 1, -2, 3 ],
                ],
                [
                    [ 11,  0, 21 ],
                    [ -1, 13, -9 ],
                ],
            ],
            [
                [
                    [1, 2, 3],
                    [4, 5, 6],
                ],
                [
                    [7, 8],
                    [9, 10],
                    [11, 12],
                ],
                [
                    [58, 64],
                    [139, 154],
                ],
            ],
            [
                [
                    [3, 4, 2],
                ],
                [
                    [13, 9, 7, 15],
                    [8, 7, 4, 6],
                    [6, 4, 0, 3],
                ],
                [
                    [83, 63, 37, 75],
                ],
            ],
            [
                [
                    [0, 1, 2],
                    [3, 4, 5],
                ],
                [
                    [6, 7],
                    [8, 9],
                    [10, 11],
                ],
                [
                    [28, 31],
                    [100, 112],
                ],
            ],
            [
                [
                    [1, 2, 3],
                    [4, 5, 6],
                    [7, 8, 9],
                ],
                [
                    [1, 2, 3],
                    [4, 5, 6],
                    [7, 8, 9],
                ],
                [
                    [30, 36, 42],
                    [66, 81, 96],
                    [102, 126, 150],
                ],
            ],
            [
                [
                    [1, 2, 3, 4, 5, 6, 7, 8],
                    [2, 3, 4, 5, 6, 7, 8, 9],
                    [3, 4, 5, 6, 7, 8, 9, 1],
                    [4, 5, 6, 7, 8, 9, 1, 2],
                    [5, 6, 7, 8, 9, 1, 2, 3],
                    [6, 7, 8, 9, 1, 2, 3, 4],
                    [7, 8, 9, 1, 2, 3, 4, 5],
                    [8, 9, 1, 2, 3, 4, 5, 6],
                    [9, 1, 2, 3, 4, 5, 6, 7],
                ],
                [
                    [7, 8, 9, 1, 2, 3, 4, 5, 6, 7, 8],
                    [8, 9, 1, 2, 3, 4, 5, 6, 7, 8, 9],
                    [9, 1, 2, 3, 4, 5, 6, 7, 8, 9, 1],
                    [1, 2, 3, 4, 5, 6, 7, 8, 9, 1, 2],
                    [2, 3, 4, 5, 6, 7, 8, 9, 1, 2, 3],
                    [3, 4, 5, 6, 7, 8, 9, 1, 2, 3, 4],
                    [4, 5, 6, 7, 8, 9, 1, 2, 3, 4, 5],
                    [5, 6, 7, 8, 9, 1, 2, 3, 4, 5, 6],
                ],
                [
                    [150, 159, 177, 204, 240, 204, 177, 159, 150, 150 ,159],
                    [189, 197, 214, 240, 284, 247, 219, 200, 190, 189, 197],
                    [183, 181, 188, 204, 247, 281, 243, 214, 194, 183, 181],
                    [186, 174, 171, 177, 219, 243, 276, 237, 207, 186, 174],
                    [198, 176, 163, 159, 200, 214, 237, 269, 229, 198, 176],
                    [219, 187, 164, 150, 190, 194, 207, 229, 260, 219, 187],
                    [249, 207, 174, 150, 189, 183, 186, 198, 219, 249, 207],
                    [207, 236, 193, 159, 197, 181, 174, 176, 187, 207, 236],
                    [174, 193, 221, 177, 214, 188, 171, 163, 164, 174, 193],
                ],
            ],
            [
                [
                    [1.4, 5.3, 4.8],
                    [3.2, 2.3, 9.05],
                    [9.54, 0.2, 1.85],
                ],
                [
                    [3.5, 5.6, 6.7],
                    [6.5, 4.2, 9.05],
                    [0.6, 0.236, 4.5],
                ],
                [
                    [42.23, 31.2328, 78.945],
                    [31.58, 29.7158, 82.980],
                    [35.80, 54.7006, 74.053],
                ],
            ],
        ];
    }

    /**
     * @test         multiply vector
     * @dataProvider dataProviderForMultiplyVector
     */
    public function testMultiplyVector(array $A, array $B, array $R)
    {
        // Given
        $A  = MatrixFactory::create($A);
        $B  = new Vector($B);
        $R  = MatrixFactory::create($R);

        // When
        $R2 = $A->multiply($B);

        // Then
        $this->assertEquals($R, $R2);
    }

    public function dataProviderForMultiplyVector(): array
    {
        return [
            [
                [
                    [1],
                ],
                [1],
                [
                    [1],
                ],
            ],
            [
                [
                    [2],
                ],
                [3],
                [
                    [6],
                ],
            ],
            [
                [
                    [1, 2],
                    [2, 3],
                ],
                [4, 5],
                [
                    [14],
                    [23],
                ],
            ],
            [
                [
                    [1, 2, 3],
                    [2, 3, 4],
                    [3, 4, 5],
                ],
                [1, 2, 3],
                [
                    [14],
                    [20],
                    [26],
                ],
            ],
            [
                [
                    [3, 6, 5],
                    [1, 7, 5],
                    [2, 3, 2],
                ],
                [1, 5, 4],
                [
                    [53],
                    [56],
                    [25],
                ],
            ],
            [
                [
                    [1, 1, 1],
                    [2, 2, 2],
                ],
                [1, 2, 3],
                [
                    [6],
                    [12],
                ],
            ],
            [
                [
                    [1, 1, 1],
                    [2, 2, 2],
                    [3, 3, 3],
                    [4, 4, 4]
                ],
                [1, 2, 3],
                [
                    [6],
                    [12],
                    [18],
                    [24],
                ],
            ],
        ];
    }

    /**
     * @test   multiple exception
     * @throws \Exception
     */
    public function testMultiplyExceptionDimensionsDoNotMatch()
    {
        // Given
        $A = MatrixFactory::create([
            [1, 2, 3],
            [2, 3, 4],
        ]);
        $B = MatrixFactory::create([
            [1, 2, 3],
            [2, 3, 4],
        ]);

        // Then
        $this->expectException(Exception\MatrixException::class);

        // When
        $A->multiply($B);
    }

    /**
     * @test   multiple exception
     * @throws \Exception
     */
    public function testMultiplyExceptionNotMatrixOrVector()
    {
        // Given
        $A = MatrixFactory::create([
            [1, 2, 3],
            [2, 3, 4],
            [3, 4, 5],
        ]);
        $B = [
            [1, 2, 3],
            [2, 3, 4],
            [3, 4, 5],
        ];

        // Then
        $this->expectException(Exception\IncorrectTypeException::class);

        // When
        $A->multiply($B);
    }

    /**
     * @test         scalarMultiply
     * @dataProvider dataProviderForScalarMultiply
     */
    public function testScalarMultiply(array $A, $k, array $R)
    {
        // Given
        $A = MatrixFactory::create($A);
        $R = MatrixFactory::create($R);

        // When
        $kA = $A->scalarMultiply($k);

        // Then
        $this->assertEquals($R, $kA);
    }

    public function dataProviderForScalarMultiply(): array
    {
        return [
            [
                [
                    [1, 2, 3],
                    [2, 3, 4],
                    [3, 4, 5],
                ], 3,
                [
                    [3, 6, 9],
                    [6, 9, 12],
                    [9, 12, 15],
                ],
            ],
            [
                [
                    [1, 2, 3],
                ], 3,
                [
                    [3, 6, 9],
                ],
            ],
            [
                [
                    [1],
                    [2],
                    [3],
                ], 3,
                [
                    [3],
                    [6],
                    [9],
                ],
            ],
            [
                [
                    [1],
                ], 3,
                [
                    [3],
                ],
            ],
        ];
    }

    /**
     * @test         negate
     * @dataProvider dataProviderForNegate
     * @param        array $A
     * @param        array $expected
     * @throws       \Exception
     */
    public function testNegate(array $A, array $expected)
    {
        // Given
        $A        = MatrixFactory::create($A);
        $expected = MatrixFactory::create($expected);

        // When
        $−A = $A->negate();

        // Then
        $this->assertEquals($expected, $−A);
    }

    /**
     * @return array [A, −A]
     */
    public function dataProviderForNegate(): array
    {
        return [
            [
                [
                    [0]
                ],
                [
                    [0]
                ],
            ],
            [
                [
                    [1]
                ],
                [
                    [-1]
                ],
            ],
            [
                [
                    [-1]
                ],
                [
                    [1]
                ],
            ],
            [
                [
                    [1, 2],
                    [3, 4],
                ],
                [
                    [-1, -2],
                    [-3, -4],
                ],
            ],
            [
                [
                    [1, -2, 3],
                    [-4, 5, -6],
                    [7, -8, 9],
                ],
                [
                    [-1, 2, -3],
                    [4, -5, 6],
                    [-7, 8, -9],
                ]
            ],
        ];
    }

    /**
     * @test         scalarDivide
     * @dataProvider dataProviderForScalarDivide
     */
    public function testScalarDivide(array $A, $k, array $R)
    {
        // Given
        $A = MatrixFactory::create($A);
        $R = MatrixFactory::create($R);

        // When
        $divided = $A->scalarDivide($k);

        // Then
        $this->assertEquals($R, $divided);
    }

    public function dataProviderForScalarDivide(): array
    {
        return [
            [
                [
                    [1, 2, 3],
                    [2, 3, 4],
                    [3, 4, 5],
                ], 3,
                [
                    [1 / 3, 2 / 3, 1],
                    [2 / 3, 1, 4 / 3],
                    [1, 4 / 3, 5 / 3],
                ],
            ],
            [
                [
                    [3, 6, 9],
                ], 3,
                [
                    [1, 2, 3],
                ],
            ],
            [
                [
                    [1],
                    [2],
                    [3],
                ], 3,
                [
                    [1 / 3],
                    [2 / 3],
                    [1],
                ],
            ],
            [
                [
                    [1],
                ], 3,
                [
                    [1 / 3],
                ],
            ],
        ];
    }

    /**
     * @test   scalarDivide by zero
     * @throws \Exception
     */
    public function testScalarDivideByZero()
    {
        // Given
        $A = MatrixFactory::create([
            [1, 2, 3],
            [2, 3, 4],
        ]);

        // Then
        $this->expectException(Exception\BadParameterException::class);

        // When
        $A->scalarDivide(0);
    }

    /**
     * @test         hadamardProduct
     * @dataProvider dataProviderForHadamardProduct
     */
    public function testHadamardProduct(array $A, array $B, array $expected)
    {
        // Given
        $A        = MatrixFactory::create($A);
        $B        = MatrixFactory::create($B);
        $expected = MatrixFactory::create($expected);

        // When
        $A∘B = $A->hadamardProduct($B);

        // Then
        $this->assertEquals($expected, $A∘B);
    }

    public function dataProviderForHadamardProduct(): array
    {
        return [
            [
                [
                    [1, 2, 3],
                    [2, 3, 4],
                    [3, 4, 5],
                ],
                [
                    [1, 2, 3],
                    [2, 3, 4],
                    [3, 4, 5],
                ],
                [
                    [1, 4, 9],
                    [4, 9, 16],
                    [9, 16, 25],
                ]
            ],
            [
                [
                    [1, 2, 3],
                    [2, 3, 4],
                    [3, 4, 5],
                ],
                [
                    [6, 6, 4],
                    [8, 7, 8],
                    [3, 1, 7],
                ],
                [
                    [6, 12, 12],
                    [16, 21, 32],
                    [9, 4, 35],
                ]
            ],
        ];
    }

    /**
     * @test   hadamardProduct dimensions don't match
     * @throws \Exception
     */
    public function testHadamardProductDimensionsDoNotMatch()
    {
        // Given
        $A = MatrixFactory::create([
            [1, 2, 3],
            [2, 3, 4],
        ]);
        $B = MatrixFactory::create([
            [1, 2, 3, 4],
            [2, 3, 4, 5],
        ]);

        // Then
        $this->expectException(Exception\MatrixException::class);

        // When
        $A->hadamardProduct($B);
    }

    /**
     * @test         kroneckerProduct
     * @dataProvider dataProviderForKroneckerProduct
     */
    public function testKroneckerProduct(array $A, array $B, array $expected)
    {
        // Given
        $A        = new NumericMatrix($A);
        $B        = new NumericMatrix($B);
        $expected = new NumericMatrix($expected);

        // When
        $A⊗B = $A->kroneckerProduct($B);

        // Then
        $this->assertEquals($expected->getMatrix(), $A⊗B->getMatrix());
    }

    public function dataProviderForKroneckerProduct(): array
    {
        return [
            [
                [
                    [1, 2],
                    [3, 4],
                ],
                [
                    [0, 5],
                    [6, 7],
                ],
                [
                    [0, 5, 0, 10],
                    [6, 7, 12, 14],
                    [0, 15, 0, 20],
                    [18, 21, 24, 28],
                ],
            ],
            [
                [
                    [1, 1],
                    [1, -1],
                ],
                [
                    [1, 1],
                    [1, -1],
                ],
                [
                    [1, 1, 1, 1],
                    [1, -1, 1, -1],
                    [1, 1, -1, -1],
                    [1, -1, -1, 1],
                ],
            ],
            [
                [
                    [1, 2, 3],
                    [4, 5, 6],
                ],
                [
                    [7, 8],
                    [9, 10],
                ],
                [
                    [7, 8, 14, 16, 21, 24],
                    [9, 10, 18, 20, 27, 30],
                    [28, 32, 35, 40, 42, 48],
                    [36, 40, 45, 50, 54, 60],
                ],
            ],
            [
                [
                    [2, 3],
                    [5, 4],
                ],
                [
                    [5, 5],
                    [4, 4],
                    [2, 9]
                ],
                [
                    [10, 10, 15, 15],
                    [8, 8, 12, 12],
                    [4, 18, 6, 27],
                    [25, 25, 20, 20],
                    [20, 20, 16, 16],
                    [10, 45, 8, 36],
                ],
            ],
            [
                [
                    [2, 3],
                    [5, 4],
                ],
                [
                    [5, 4, 2],
                    [5, 4, 9],
                ],
                [
                    [10, 8, 4, 15, 12, 6],
                    [10, 8, 18, 15, 12, 27],
                    [25, 20, 10, 20, 16, 8],
                    [25, 20, 45, 20, 16, 36],
                ],
            ],

        ];
    }
}
