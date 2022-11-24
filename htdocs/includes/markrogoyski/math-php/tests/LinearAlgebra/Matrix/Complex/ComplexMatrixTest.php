<?php

namespace MathPHP\Tests\LinearAlgebra\Matrix\Complex;

use MathPHP\Exception;
use MathPHP\LinearAlgebra\ComplexMatrix;
use MathPHP\Number\ArbitraryInteger;
use MathPHP\Number\Complex;
use MathPHP\Tests\LinearAlgebra\Fixture\MatrixDataProvider;

class ComplexMatrixTest extends \PHPUnit\Framework\TestCase
{
    use MatrixDataProvider;

    /**
     * @test         Construction
     * @dataProvider dataProviderForComplexObjectMatrix
     * @param        Complex[][]
     */
    public function testConstruction(array $A)
    {
        // When
        $A = new ComplexMatrix($A);

        // Then
        $this->assertInstanceOf(ComplexMatrix::class, $A);
    }

    /**
     * @test Constructor exception when non complex number provided
     */
    public function testConstructorException()
    {
        // Given
        $A = [
            [new Complex(2, 1), new Complex(2, 1)],
            [new Complex(2, 1), new ArbitraryInteger(4)],
        ];

        // Then
        $this->expectException(Exception\IncorrectTypeException::class);

        // When
        $A = new ComplexMatrix($A);
    }

    /**
     * @test createZeroValue
     */
    public function testCreateZeroValue()
    {
        // Given
        $zeroMatrix = ComplexMatrix::createZeroValue();

        // And
        $expected = [
            [new Complex(0, 0)]
        ];

        // Then
        $this->assertEquals($expected, $zeroMatrix->getMatrix());
    }

    /**
     * @test conjugateTranspose
     */
    public function testConjugateTranspose()
    {
        // Given
        $A = [
            [new Complex(1, 0), new Complex(-2, -1), new Complex(5, 0)],
            [new Complex(1, 1), new Complex(0, 1), new Complex(4, -2)],
        ];
        $A = new ComplexMatrix($A);

        // And
        $expected = [
            [new Complex(1, 0), new Complex(1, -1)],
            [new Complex(-2, 1), new Complex(0, -1)],
            [new Complex(5, 0), new Complex(4, 2)],
        ];

        // When
        $Aᴴ = $A->conjugateTranspose();

        // Then
        $this->assertEquals($expected, $Aᴴ->getMatrix());
    }
}
