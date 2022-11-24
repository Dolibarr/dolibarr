<?php

namespace MathPHP\Tests\LinearAlgebra\Matrix\Other;

use MathPHP\Exception\MatrixException;
use MathPHP\LinearAlgebra\NumericDiagonalMatrix;
use MathPHP\LinearAlgebra\NumericMatrix;
use MathPHP\LinearAlgebra\MatrixFactory;

class DiagonalMatrixTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test         constructor builds the expected DiagonalMatrix
     * @dataProvider dataProviderMulti
     * @param        array $A
     * @param        array $R
     * @throws       \Exception
     */
    public function testConstructor(array $A, array $R)
    {
        // Given
        $R = new NumericMatrix($R);

        // When
        $D = MatrixFactory::diagonal($A);

        // Then
        $this->assertInstanceOf(NumericDiagonalMatrix::class, $D);
        $this->assertTrue($R->isEqual($D));
        $this->assertTrue($D->isEqual($R));
    }

    /**
     * @test         getMatrix returns the expected array
     * @dataProvider dataProviderMulti
     * @param        array $A
     * @param        array $R
     * @throws       \Exception
     */
    public function testGetMatrix(array $A, array $R)
    {
        // Given
        $D = MatrixFactory::diagonal($A);

        // Then
        $this->assertEquals($R, $D->getMatrix());
    }

    /**
     * @test         isSquare returns true
     * @dataProvider dataProviderMulti
     * @param        array $A
     * @throws       \Exception
     */
    public function testIsSquare(array $A)
    {
        // Given
        $D = MatrixFactory::diagonal($A);

        // Then
        $this->assertTrue($D->isSquare());
    }

    /**
     * @test         isSymmetric returns true
     * @dataProvider dataProviderMulti
     * @param        array $A
     * @throws       \Exception
     */
    public function testIsSymmetric(array $A)
    {
        // Given
        $D = MatrixFactory::diagonal($A);

        // Then
        $this->assertTrue($D->isSymmetric());
    }

    /**
     * @test         isLowerTriangular returns true
     * @dataProvider dataProviderMulti
     * @param        array $A
     * @throws       \Exception
     */
    public function testIsLowerTriangular(array $A)
    {
        // Given
        $D = MatrixFactory::diagonal($A);

        // Then
        $this->assertTrue($D->isLowerTriangular());
    }

    /**
     * @test         isUpperTriangular returns true
     * @dataProvider dataProviderMulti
     * @param        array $A
     * @throws       \Exception
     */
    public function testIsUpperTriangular(array $A)
    {
        // Given
        $D = MatrixFactory::diagonal($A);

        // Then
        $this->assertTrue($D->isUpperTriangular());
    }

    /**
     * @test         isTriangular returns true
     * @dataProvider dataProviderMulti
     * @param        array $A
     * @throws       \Exception
     */
    public function testIsTriangular(array $A)
    {
        // Given
        $D = MatrixFactory::diagonal($A);

        // Then
        $this->assertTrue($D->isTriangular());
    }

    /**
     * @test         isDiagonal returns true
     * @dataProvider dataProviderMulti
     * @param        array $A
     * @throws       \Exception
     */
    public function testIsDiagonal(array $A)
    {
        // Given
        $D = MatrixFactory::diagonal($A);

        // Then
        $this->assertTrue($D->isDiagonal());
    }

    /**
     * @return array
     */
    public function dataProviderMulti(): array
    {
        return [
            [
                [1, 2, 3],
                [
                    [1, 0, 0],
                    [0, 2, 0],
                    [0, 0, 3],
                ],
            ],
            [
                [1],
                [
                    [1]
                ]
            ],
        ];
    }

    /**
     * @test   Construction error - square matrix
     * @throws MatrixException
     */
    public function testConstructionExceptionNotSquare()
    {
        // Given
        $A = [
            [1, 0, 0],
            [0, 2, 0],
        ];

        // Then
        $this->expectException(MatrixException::class);

        // When
        $matrix = new NumericDiagonalMatrix($A);
    }

    /**
     * @test   Construction error - not diagonal matrix
     * @throws MatrixException
     */
    public function testConstructionExceptionNotDiagonal()
    {
        // Given
        $A = [
            [1, 1],
            [3, 2],
        ];

        // Then
        $this->expectException(MatrixException::class);

        // When
        $matrix = new NumericDiagonalMatrix($A);
    }
}
