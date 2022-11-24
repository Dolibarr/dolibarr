<?php

namespace MathPHP\Tests\LinearAlgebra\Matrix\Numeric;

use MathPHP\LinearAlgebra\MatrixFactory;

class MatrixComparisonsTest extends \PHPUnit\Framework\TestCase
{
    use \MathPHP\Tests\LinearAlgebra\Fixture\MatrixDataProvider;

    /**
     * @test         isEqual finds two matrices to be equal
     * @dataProvider dataProviderForSquareMatrix
     * @dataProvider dataProviderForNotSquareMatrix
     * @dataProvider dataProviderForNonsingularMatrix
     * @dataProvider dataProviderForMatrixWithWeirdNumbers
     * @param        array $A
     * @throws       \Exception
     */
    public function testIsEqual(array $A)
    {
        // Given
        $A = MatrixFactory::create($A);

        // Then
        $this->assertTrue($A->isEqual($A));
    }

    /**
     * @test         isEqual finds to matrices to not be equal
     * @dataProvider dataProviderForTwoSquareMatrices
     * @dataProvider dataProviderForTwoNonsingularMatrices
     * @param        array $A
     * @param        array $B
     * @throws       \Exception
     */
    public function testIsNotEqual(array $A, array $B)
    {
        // Given
        $A = MatrixFactory::create($A);
        $B = MatrixFactory::create($B);

        // Then
        $this->assertFalse($A->isEqual($B));
        $this->assertFalse($B->isEqual($A));
    }

    /**
     * @test   isEqual finds matrices to be equal because the values are within the error tolerance
     * @throws \Exception
     */
    public function testIsEqualBecauseWithinErrorTolerance()
    {
        // Given
        $A = MatrixFactory::create([
            [1.0001, 2.0002],
            [3.0003, 4.0004],
        ]);
        $B = MatrixFactory::create([
            [1.0002, 2.0003],
            [3.0004, 4.0005],
        ]);

        // When
        $A->setError(0.001);

        // Then
        $this->assertTrue($A->isEqual($B));
    }

    /**
     * @test   isEqual finds matrices to be not equal because the values are within the error tolerance
     * @throws \Exception
     */
    public function testIsNotEqualBecauseOutsideOfErrorTolerance()
    {
        // Given
        $A = MatrixFactory::create([
            [1.0001, 2.0002],
            [3.0003, 4.0004],
        ]);
        $B = MatrixFactory::create([
            [1.0002, 2.0003],
            [3.0004, 4.0005],
        ]);

        // When
        $A->setError(0.00001);

        // Then
        $this->assertFalse($A->isEqual($B));
    }

    /**
     * @test   isEqual finds matrices to be not equal because the dimensions are not the same
     * @throws \Exception
     */
    public function testIsEqualNotEqualBecauseDifferentDimmensions()
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
        $this->assertFalse($A->isEqual($B));
        $this->assertFalse($B->isEqual($A));
    }
}
