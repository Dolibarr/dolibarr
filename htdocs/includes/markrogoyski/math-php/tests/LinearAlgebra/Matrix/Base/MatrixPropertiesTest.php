<?php

namespace MathPHP\Tests\LinearAlgebra\Matrix\Base;

use MathPHP\LinearAlgebra\MatrixFactory;
use MathPHP\LinearAlgebra\NumericMatrix;

class MatrixPropertiesTest extends \PHPUnit\Framework\TestCase
{
    use \MathPHP\Tests\LinearAlgebra\Fixture\MatrixDataProvider;

    /**
     * @test         isSquare returns true for square matrices.
     * @dataProvider dataProviderForSquareMatrix
     * @param        array $A
     * @throws       \Exception
     */
    public function testIsSquare(array $A)
    {
        // Given
        $A = MatrixFactory::create($A);

        // Then
        $this->assertTrue($A->isSquare());
    }

    /**
     * @test         isSquare returns false for nonsquare matrices.
     * @dataProvider dataProviderForNotSquareMatrix
     * @param        array $A
     * @throws       \Exception
     */
    public function testIsSquareFalseNonSquareMatrix(array $A)
    {
        // Given
        $A = MatrixFactory::create($A);

        // Then
        $this->assertFalse($A->isSquare());
    }

    /**
     * @test         isNotSquare returns true for nonsquare matrices.
     * @dataProvider dataProviderForNotSquareMatrix
     * @param        array $A
     * @throws       \Exception
     */
    public function testIsNotSquare(array $A)
    {
        // Given
        $A = MatrixFactory::create($A);

        // Then
        $this->assertFalse($A->isSquare());
    }

    /**
     * @test         isRectangularDiagonal returns true appropriately
     * @dataProvider dataProviderForRectangularDiagonalMatrix
     * @param        array $D
     * @throws       \Exception
     */
    public function testIsRectangularDiagonal(array $D)
    {
        // Given
        $D = MatrixFactory::create($D);

        // Then
        $this->assertTrue($D->isRectangularDiagonal());
    }
}
