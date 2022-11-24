<?php

namespace MathPHP\Tests\LinearAlgebra\Matrix\Complex;

use MathPHP\LinearAlgebra\ComplexMatrix;
use MathPHP\Number\Complex;
use MathPHP\Tests\LinearAlgebra\Fixture\MatrixDataProvider;

/**
 * Complex Matrix Axioms
 *  - Conjugate Transpose
 *    - (A + B)ᴴ = Aᴴ + Bᴴ
 *    - (zA)ᴴ = z‾Aᴴ
 *    - (AB)ᴴ = BᴴAᴴ
 *    - (Aᴴ)ᴴ = A
 *    - det(Aᴴ) = ‾det(A)‾
 */
class ComplexMatrixAxiomsTest extends \PHPUnit\Framework\TestCase
{
    use MatrixDataProvider;

    /**
     * @test (A + B)ᴴ = Aᴴ + Bᴴ
     * A and B must be the same dimensions, where ᴴ is the conjugate transpose
     */
    public function testConjugateTransposeAddition()
    {
        // Given
        $A = new ComplexMatrix([
            [new Complex(1, 0), new Complex(-2, -1)],
            [new Complex(1, 1), new Complex(0, 1)],
        ]);
        $B = new ComplexMatrix([
            [new Complex(2, 2), new Complex(2, -1)],
            [new Complex(1, 4), new Complex(3, -2)],
        ]);

        // When
        $Aᴴ ＋ Bᴴ = $A->conjugateTranspose()->add($B->conjugateTranspose());
        $⟮A ＋ B⟯ᴴ = $A->add($B)->conjugateTranspose();

        // Then
        $this->assertEquals($⟮A ＋ B⟯ᴴ->getMatrix(), $Aᴴ ＋ Bᴴ->getMatrix());
    }

    /**
     * @test (zA)ᴴ = z‾Aᴴ
     * z is a complex number and z‾ is the conjugate
     */
    public function testConjugateTransposeScalarMultiplication()
    {
        // Given
        $A = new ComplexMatrix([
            [new Complex(1, 0), new Complex(-2, -1)],
            [new Complex(1, 1), new Complex(0, 1)],
        ]);
        $z = new Complex(3, -2);

        // When
        $⟮zA⟯ᴴ = $A->scalarMultiply($z)->conjugateTranspose();
        $z‾Aᴴ = $A->conjugateTranspose()->scalarMultiply($z->complexConjugate());

        // Then
        $this->assertEquals($⟮zA⟯ᴴ->getMatrix(), $z‾Aᴴ->getMatrix());
    }

    /**
     * @test (AB)ᴴ = BᴴAᴴ
     */
    public function testConjugateTransposeMultiplication()
    {
        // Given
        $A = new ComplexMatrix([
            [new Complex(1, 0), new Complex(-2, -1)],
            [new Complex(1, 1), new Complex(0, 1)],
        ]);
        $B = new ComplexMatrix([
            [new Complex(2, 2), new Complex(2, -1)],
            [new Complex(1, 4), new Complex(3, -2)],
        ]);

        // When
        $⟮AB⟯ᴴ = $A->multiply($B)->conjugateTranspose();
        $BᴴAᴴ = $B->conjugateTranspose()->multiply($A->conjugateTranspose());

        // Then
        $this->assertEquals($⟮AB⟯ᴴ->getMatrix(), $BᴴAᴴ->getMatrix());
    }

    /**
     * @test (Aᴴ)ᴴ = A
     * Hermitian transposition is an involution
     */
    public function testConjugateTransposeHermitianTranspositionIsanInvolution()
    {
        // Given
        $A = new ComplexMatrix([
            [new Complex(1, 0), new Complex(-2, -1)],
            [new Complex(1, 1), new Complex(0, 1)],
        ]);

        // When
        $⟮Aᴴ⟯ᴴ = $A->conjugateTranspose()->conjugateTranspose();

        // Then
        $this->assertEquals($⟮Aᴴ⟯ᴴ->getMatrix(), $A->getMatrix());
    }

    /**
     * @test det(Aᴴ) = ‾det(A)‾
     * If A is a square matrix, then determinant of conjugate transpose is the same as the complex conjugate of the determinant
     *
     * @dataProvider dataProviderForComplexSquareObjectMatrix
     * @array        $A
     */
    public function testConjugateTransposeDeterminant(array $A)
    {
        // Given
        $A = new ComplexMatrix($A);

        // When
        $det⟮Aᴴ⟯  = $A->conjugateTranspose()->det();
        $‾det⟮A⟯‾ = $A->det()->complexConjugate();

        // Then
        $this->assertEquals($‾det⟮A⟯‾, $det⟮Aᴴ⟯);
    }

    /**
     * @test tr(Aᴴ) = ‾tr(A)‾
     * If A is a square matrix, then trace of conjugate transpose is the same as the complex conjugate of the trace
     *
     * @dataProvider dataProviderForComplexSquareObjectMatrix
     * @array        $A
     */
    public function testConjugateTransposeTrace(array $A)
    {
        // Given
        $A = new ComplexMatrix($A);

        // When
        $tr⟮Aᴴ⟯  = $A->conjugateTranspose()->trace();
        $‾tr⟮A⟯‾ = $A->trace()->complexConjugate();

        // Then
        $this->assertEquals($‾tr⟮A⟯‾, $tr⟮Aᴴ⟯);
    }
}
