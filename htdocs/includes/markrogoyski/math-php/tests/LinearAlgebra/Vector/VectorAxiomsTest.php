<?php

namespace MathPHP\Tests\LinearAlgebra\Vector;

use MathPHP\LinearAlgebra\Vector;
use MathPHP\LinearAlgebra\NumericMatrix;

/**
 * Tests of Vector axioms
 * These tests don't test specific functions,
 * but rather matrix axioms which in term make use of multiple functions.
 * If all the Vector math is implemented properly, these tests should
 * all work out according to the axioms.
 *
 * Axioms tested:
 *  - Norms
 *    - |x|₂ ≤ |x|₁ ≤ √n |x|₂
 *    - |x|∞ ≤ |x|₂ ≤ √n |x|∞
 *    - |x|∞ ≤ |x|₁ ≤ √n |x|∞
 *  - Dot product
 *    - A⋅B = B⋅A
 *    - 0⋅A = A⋅0 = 0
 *  - Cross product
 *    - A x B = -(B x A)
 *    - A x 0 = 0
 *    - A x (B + C) = (A x B) + (A x C)
 *  - Cross product / dot product
 *    - (A x B) ⋅ A = 0
 *    - (A x B) ⋅ B = 0
 *    - A ⋅ (B x C) = (A x B) ⋅ C
 *    - A x (B x C) = (A ⋅ C)B - (A ⋅ B)C
 *  - Outer product
 *    - A⨂B = ABᵀ
 *    - A⨂B = AB (direct product)
 *  - Scalar multiplication
 *    - (c + d)A = cA + dA
 *    - c(A + B) = cA + cB
 *    - 1A = A
 *    - 0A = 0
 *    - -1A = -A
 *  - Perpendicular / Perp dot product
 *    - A⋅A⊥ = 0
 *    - A⊥⋅A = 0
 *    - A⋅A⊥ = -A⊥⋅A
 *  - Projections / Perps
 *    - projᵇA + perpᵇA = A
 *    - |projᵇA|² + |perpᵇA|² = |A|²
 *    - projᵇA ⋅ perpᵇA = 0
 *    - |projᵇA⊥ ⋅ perpᵇA| = |projᵇA| |perpᵇA|
 */
class VectorAxiomsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test Axiom: |x|₂ ≤ |x|₁ ≤ √n |x|₂
     * l²-norm is less than equal to l₁-norm which is less than equal to sqrt n * l²-norm.
     *
     * @dataProvider dataProviderForSingleVector
     */
    public function testL2NormLessThanL1NormLessThanSqrtNL2Norm(array $V)
    {
        // Given
        $V = new Vector($V);
        $n = $V->getN();

        // When
        $l₁norm   = $V->l1Norm();
        $l²norm   = $V->l2Norm();
        $√nl²norm = $n * $l²norm;

        // Then
        $this->assertLessThanOrEqual($l₁norm, $l²norm);
        $this->assertLessThanOrEqual($√nl²norm, $l₁norm);
        $this->assertLessThanOrEqual($√nl²norm, $l²norm);
    }

    /**
     * @test Axiom: |x|∞ ≤ |x|₂ ≤ √n |x|∞
     * Max norm is less than equal to l₂-norm which is less than equal to sqrt n * max norm.
     *
     * @dataProvider dataProviderForSingleVector
     */
    public function testMaxNormLessThtanEQualL2NormLessThanEqualSqrtNMaxNorm(array $V)
    {
        // Given
        $V = new Vector($V);
        $n = $V->getN();

        // When
        $max_norm    = $V->maxNorm();
        $l²norm      = $V->l2Norm();
        $√n_max_norm = $n * $max_norm;

        // Then
        $this->assertLessThanOrEqual($l²norm, $max_norm);
        $this->assertLessThanOrEqual($√n_max_norm, $l²norm);
        $this->assertLessThanOrEqual($√n_max_norm, $max_norm);
    }

    /**
     * @test Axiom: |x|∞ ≤ |x|₁ ≤ √n |x|∞
     * Max norm is less than equal to l₁-norm which is less than equal to sqrt n * max norm.
     *
     * @dataProvider dataProviderForSingleVector
     */
    public function testMaxNormLessThanEqualL1NormLessThanEqualSqrtNMaxNorm(array $V)
    {
        // Given
        $V = new Vector($V);
        $n = $V->getN();

        // When
        $max_norm    = $V->maxNorm();
        $l₁norm      = $V->l1Norm();
        $√n_max_norm = $n * $max_norm;

        // Then
        $this->assertLessThanOrEqual($l₁norm, $max_norm);
        $this->assertLessThanOrEqual($√n_max_norm, $l₁norm);
        $this->assertLessThanOrEqual($√n_max_norm, $max_norm);
    }

    public function dataProviderForSingleVector(): array
    {
        return [
            [ [0] ],
            [ [1] ],
            [ [1, 2] ],
            [ [1, 2, 3, 4, 5] ],
            [ [5, 2, 7, 4, 2, 7, 4] ],
            [ [-4, 6, 3, 7, -4, 5, -8, -11, 5, 0, 5, -2] ],
            [ [1, 0, 3, 5, 3, 0, 0, 9, 0] ],
            [ [34, 100, 4, 532, 6, 43, 78, 32, 853, 23, 532, 327 ] ],
        ];
    }

    /**
     * @test Axiom: A⋅B = B⋅A
     * Dot product is commutative
     * @dataProvider dataProviderForTwoVectors
     */
    public function testDotProductCommutative(array $A, array $B)
    {
        // Given
        $A = new Vector($A);
        $B = new Vector($B);

        // When
        $A⋅B = $A->dotProduct($B);
        $B⋅A = $B->dotProduct($A);

        // Then
        $this->assertEquals($A⋅B, $B⋅A);
    }

    public function dataProviderForTwoVectors(): array
    {
        return [
            [
                [1],
                [1],
            ],
            [
                [1, 2],
                [2, 3],
            ],
            [
                [1, 2, 3, 4, 5],
                [4, 5, 6, 7, 8],
            ],
            [
                [3, -5, 2, -12, 4, 9, -4],
                [-9, 4, 5, 6, -11, 2, -4],
            ],
            [
                [1, 0, 3],
                [0, 1 ,9],
            ],
        ];
    }

    /**
     * @test Axiom: 0⋅A = A⋅0 = 0
     * Dot product of a vector and zero is zero.
     * @dataProvider dataProviderForDotProductZero
     */
    public function testDotProductZero(array $A, array $zero)
    {
        // Given
        $A    = new Vector($A);
        $zero = new Vector($zero);

        // When
        $A⋅zero = $A->dotProduct($zero);
        $zero⋅A = $zero->dotProduct($A);

        // Then
        $this->assertEquals(0, $A⋅zero);
        $this->assertEquals(0, $zero⋅A);
        $this->assertEquals($A⋅zero, $zero⋅A);
    }

    public function dataProviderForDotProductZero(): array
    {
        return [
            [
                [1],
                [0],
            ],
            [
                [1, 2],
                [0, 0],
            ],
            [
                [1, 2, 3],
                [0, 0, 0],
            ],
            [
                [5, 6, 7, 3, 4, 5, 6, 7, 8, 6, 5],
                [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
            ],
        ];
    }

    /**
     * @test Axiom: A x B = -(B x A)
     * Anticommutivity: Reverse order cross product results in a negative cross product
     * @dataProvider dataProviderForCrossProduct
     */
    public function testReverseCrossProduct(array $A, array $B)
    {
        // Given
        $A = new Vector($A);
        $B = new Vector($B);

        // When
        $AxB = $A->crossProduct($B);
        $BxA = $B->crossProduct($A);

        // Then
        $this->assertEquals($AxB[0], -$BxA[0]);
        $this->assertEquals($AxB[1], -$BxA[1]);
        $this->assertEquals($AxB[2], -$BxA[2]);
    }

    /**
     * @test Axiom: A x 0 = 0
     * Cross product property of 0
     * @dataProvider dataProviderForCrossProduct
     */
    public function testCrossProductPropertyOfZero(array $A, array $_)
    {
        // Given
        $A    = new Vector($A);
        $zero = new Vector(\array_fill(0, $A->getN(), 0));

        // When
        $Ax0 = $A->crossProduct($zero);

        // Then
        $this->assertEquals($zero, $Ax0);
        $this->assertEquals($zero->getVector(), $Ax0->getVector());
    }

    /**
     * @test Axiom: A x (B + C) = (A x B) + (A x C)
     * Cross product distributivity
     * @dataProvider dataProviderForCrossProductThreeVectors
     */
    public function testCrossProductDistributivity(array $A, array $B, array $C)
    {
        // Given
        $A = new Vector($A);
        $B = new Vector($B);
        $C = new Vector($C);

        // When
        $Ax⟮B＋C⟯    = $A->crossProduct($B->add($C));
        $⟮AxB⟯＋⟮AxC⟯ = $A->crossProduct($B)->add($A->crossProduct($C));

        // Then
        $this->assertEquals($Ax⟮B＋C⟯, $⟮AxB⟯＋⟮AxC⟯);
        $this->assertEquals($Ax⟮B＋C⟯->getVector(), $⟮AxB⟯＋⟮AxC⟯->getVector());
    }

    public function dataProviderForCrossProductThreeVectors(): array
    {
        return [
            [
                [1, 2, 3],
                [4, 5, 6],
                [5, 6, 7],
            ],
            [
                [1, 2, 3],
                [4, -5, 6],
                [5, 6, 7],
            ],
            [
                [-1, 2, -3],
                [4,-5,6],
                [5, 6, 7],
            ],
            [
                [0,0,0],
                [0,0,0],
                [0, 0, 0],
            ],
            [
                [4, 5, 6],
                [7, 8, 9],
                [5, 6, 7],
            ],
            [
                [4, 9, 3],
                [12, 11, 4],
                [9, 6, 5],
            ],
            [
                [-4, 9, 3],
                [12, 11, 4],
                [2, 6, 7],
            ],
            [
                [4, -9, 3],
                [12, 11, 4],
                [5, 3, 7],
            ],
            [
                [4, 9, -3],
                [12, 11, 4],
                [1, 6, 7],
            ],
            [
                [4, 9, 3],
                [-12, 11, 4],
                [6, 6, 0],
            ],
            [
                [4, 9, 3],
                [12, -11, 4],
                [5, 6, 7],
            ],
            [
                [4, 9, 3],
                [12, 11, -4],
                [1, 2, -7],
            ],
        ];
    }

    /**
     * @test Axiom: (A x B) ⋅ A = 0
     * @test Axiom: (A x B) ⋅ B = 0
     * Dot product of either vector with the cross product is always zero.
     * @dataProvider dataProviderForCrossProduct
     */
    public function testCrossProductInnerProductWithEitherVectorIsZero(array $A, array $B)
    {
        // Given
        $A = new Vector($A);
        $B = new Vector($B);

        // When
        $AxB = $A->crossProduct($B);

        // Then
        $this->assertEquals(0, $AxB->innerProduct($A));
        $this->assertEquals(0, $AxB->innerProduct($B));
    }

    public function dataProviderForCrossProduct(): array
    {
        return [
            [
                [1, 2, 3],
                [4, 5, 6],
            ],
            [
                [1, 2, 3],
                [4, -5, 6],
            ],
            [
                [-1, 2, -3],
                [4,-5,6],
            ],
            [
                [0,0,0],
                [0,0,0],
            ],
            [
                [4, 5, 6],
                [7, 8, 9],
            ],
            [
                [4, 9, 3],
                [12, 11, 4],
            ],
            [
                [-4, 9, 3],
                [12, 11, 4],
            ],
            [
                [4, -9, 3],
                [12, 11, 4],
            ],
            [
                [4, 9, -3],
                [12, 11, 4],
            ],
            [
                [4, 9, 3],
                [-12, 11, 4],
            ],
            [
                [4, 9, 3],
                [12, -11, 4],
            ],
            [
                [4, 9, 3],
                [12, 11, -4],
            ],
        ];
    }

    /**
     * @test Axiom: A ⋅ (B x C) = (A x B) ⋅ C
     * Cross product volumn property
     * @dataProvider dataProviderForCrossProductThreeVectors
     */
    public function testCrossProductVolumeProperty(array $A, array $B, array $C)
    {
        // Given
        $A = new Vector($A);
        $B = new Vector($B);
        $C = new Vector($C);

        // When
        $A⋅⟮BxC⟯ = $A->dotProduct($B->crossProduct($C));
        $⟮AxB⟯⋅C = $A->crossProduct($B)->dotProduct($C);

        // Then
        $this->assertEquals($A⋅⟮BxC⟯, $⟮AxB⟯⋅C);
    }

    /**
     * @test Axiom: A x (B x C) = (A ⋅ C)B - (A ⋅ B)C
     * Lagrange's formula
     * @dataProvider dataProviderForCrossProductThreeVectors
     */
    public function testCrossProductLagrangeFormula(array $A, array $B, array $C)
    {
        // Given
        $A = new Vector($A);
        $B = new Vector($B);
        $C = new Vector($C);

        // When
        $Ax⟮BxC⟯ = $A->crossProduct($B->crossProduct($C));
        $⟮A⋅C⟯B = $B->scalarMultiply($A->dotProduct($C));
        $⟮A⋅B⟯C = $C->scalarMultiply($A->dotProduct($B));
        $⟮A⋅C⟯B−⟮A⋅B⟯C = $⟮A⋅C⟯B->subtract($⟮A⋅B⟯C);

        // Then
        $this->assertEquals($Ax⟮BxC⟯, $⟮A⋅C⟯B−⟮A⋅B⟯C);
        $this->assertEquals($Ax⟮BxC⟯->getVector(), $⟮A⋅C⟯B−⟮A⋅B⟯C->getVector());
    }

    /**
     * @test Axiom: A⨂B = ABᵀ
     * Outer product is the same as matrix multiplication of A and transpose of B
     * @dataProvider dataProviderForOuterProduct
     */
    public function testOuterProductIsMatrixMultiplicationOfAAndBTranspose(array $A, array $B)
    {
        // Given Vector A⨂B
        $Av   = new Vector($A);
        $Bv   = new Vector($B);
        $A⨂B = $Av->outerProduct($Bv);

        // When Matrix multiplication ABᵀ
        $Am = $Av->asColumnMatrix();
        $Bᵀ  = new NumericMatrix([
            $Bv->getVector()
        ]);
        $ABᵀ = $Am->multiply($Bᵀ);

        // Then
        $this->assertEquals($A⨂B, $ABᵀ);
    }

    public function dataProviderForOuterProduct(): array
    {
        return [
            [
                [2],
                [6],
            ],
            [
                [2, 5, 8],
                [6, 4, 9],
            ],
            [
                [3, 6, 3, 5, 8, 21],
                [12, 4, 5, 3, 21, 4],
            ],
        ];
    }

    /**
     * @test Axiom: A⨂B = AB (direct product)
     * Outer product is the same as the direct product
     * @dataProvider dataProviderForTwoVectors
     */
    public function testOuterProductIsDirectProduct(array $A, array $B)
    {
        // Given Outer product
        $Av   = new Vector($A);
        $Bv   = new Vector($B);
        $A⨂B = $Av->outerProduct($Bv);

        // When Direct product
        $AB = $Av->directProduct($Bv);

        // Then
        $this->assertEquals($A⨂B->getMatrix(), $AB->getMatrix());
    }

    /**
     * @test Axiom: (c + d)A = cA + dA
     * Additivity in the scalar
     * @dataProvider dataProviderForSingleVector
     */
    public function testAdditivityInTheScalarForScalarMultiplication(array $A)
    {
        // Given
        $A = new Vector($A);
        $c = 2;
        $d = 9;

        // When
        $⟮c＋d⟯A  = $A->scalarMultiply($c + $d);
        $⟮cA＋dA⟯ = $A->scalarMultiply($c)->add($A->scalarMultiply($d));

        // Then
        $this->assertEquals($⟮c＋d⟯A, $⟮cA＋dA⟯);
        $this->assertEquals($⟮c＋d⟯A->getVector(), $⟮cA＋dA⟯->getVector());
    }

    /**
     * @test Axiom: c(A + B) = cA + cB
     * Additivity in the vector
     * @dataProvider dataProviderForTwoVectors
     */
    public function testAdditivityInTheVectorForScalarMultiplication(array $A, array $B)
    {
        // Given
        $A = new Vector($A);
        $B = new Vector($B);
        $c = 4;

        // When
        $c⟮A＋B⟯ = $A->add($B)->scalarMultiply($c);
        $⟮cA＋cB⟯ = $A->scalarMultiply($c)->add($B->scalarMultiply($c));

        // Then
        $this->assertEquals($c⟮A＋B⟯, $⟮cA＋cB⟯);
        $this->assertEquals($c⟮A＋B⟯->getVector(), $⟮cA＋cB⟯->getVector());
    }

    /**
     * @test Axiom: 1A = A
     * Multiplying (scaling) by 1 does not change the vector
     * @dataProvider dataProviderForSingleVector
     */
    public function testScalarMultiplyOneIdentity(array $A)
    {
        // Given
        $A = new Vector($A);

        // When
        $１A = $A->scalarMultiply(1);

        // Then
        $this->assertEquals($A, $１A);
        $this->assertEquals($A->getVector(), $１A->getVector());
    }

    /**
     * @test Axiom: 0A = 0
     * Multiplying (scaling) by 0 gives the zero vector
     * @dataProvider dataProviderForSingleVector
     */
    public function testScalarMultiplyZeroIdentity(array $A)
    {
        // Given
        $A = new Vector($A);

        // When
        $０A  = $A->scalarMultiply(0);
        $zero = new Vector(\array_fill(0, $A->getN(), 0));

        // Then
        $this->assertEquals($zero, $０A);
        $this->assertEquals($zero->getVector(), $０A->getVector());
    }

    /**
     * @test Axiom: -1A = -A
     * Additive inverse
     * @dataProvider dataProviderForAdditiveInverse
     */
    public function testScalarMultiplyNegativeOneIdentity(array $A, array $R)
    {
        // Given
        $A = new Vector($A);

        // When
        $ーA  = $A->scalarMultiply(-1);
        $R    = new Vector($R);

        // Then
        $this->assertEquals($R, $ーA);
        $this->assertEquals($R->getVector(), $ーA->getVector());
    }

    public function dataProviderForAdditiveInverse(): array
    {
        return [
            [
                [2],
                [-2],
            ],
            [
                [0, 1, 2, 3, 4, 5, -6, -7, 8],
                [0, -1, -2, -3, -4, -5, 6, 7, -8],
            ],
        ];
    }

    /**
     * @test Axiom: A⋅A⊥ = 0
     * Vector dot product with a vector perpendicular to it will be zero.
     * @dataProvider dataProviderForPerpendicularIdentity
     */
    public function testPerpendicularDotProduct(array $A)
    {
        // Given
        $A = new Vector($A);

        // When
        $A⊥   = $A->perpendicular();
        $A⋅A⊥ = $A->dotProduct($A⊥);

        // Then
        $this->assertEquals(0, $A⋅A⊥);
    }

    /**
     * @test Axiom: A⊥⋅A = 0
     * Perp dot product with itself will be zero.
     * @dataProvider dataProviderForPerpendicularIdentity
     */
    public function testPerpDotProductZero(array $A)
    {
        // Given
        $A = new Vector($A);

        // When
        $A⊥⋅A = $A->perpDotProduct($A);

        // Then
        $this->assertEquals(0, $A⊥⋅A);
    }

    public function dataProviderForPerpendicularIdentity(): array
    {
        return [
            [[0, 0]],
            [[0, 1]],
            [[1, 0]],
            [[1, 1]],
            [[1, 2]],
            [[1, 2]],
            [[1, 3]],
            [[1, 4]],
            [[2, 0]],
            [[2, 1]],
            [[2, 2]],
            [[2, 3]],
            [[2, 4]],
            [[4, 7]],
            [[5, 3]],
            [[-2, 1]],
            [[2, -1]],
            [[-2, -1]],
            [[6, 9]],
            [[-9, 12]],
        ];
    }

    /**
     * @test Axiom: A⋅A⊥ = -A⊥⋅A
     * Swapping operands changes the sign of the perp dot product
     * @dataProvider dataProviderForPerpendicularIdentity
     */
    public function testPerpDotProdcutSwapOperandsChangeSign(array $A)
    {
        // Given
        $A  = new Vector($A);
        $A⊥ = $A->perpendicular();

        // When
        $A⋅A⊥ = $A->dotProduct($A⊥);
        $A⊥⋅A = $A⊥->dotProduct($A);

        // Then
        $this->assertEquals($A⋅A⊥, -$A⊥⋅A);
    }

    /**
     * @test Axiom: projᵇA + perpᵇA = A
     * Sum of the proj and perp of A on B equals A
     * @dataProvider dataProviderForProjPerp
     */
    public function testProjPerpSumEqualsA(array $A, array $B)
    {
        // Given
        $A = new Vector($A);
        $B = new Vector($B);

        // When
        $projᵇA = $A->projection($B);
        $perpᵇA = $A->perp($B);

        $projᵇA＋perpᵇA = $projᵇA->add($perpᵇA);

        // Then
        $this->assertEquals($A, $projᵇA＋perpᵇA);
        $this->assertEquals($A->getVector(), $projᵇA＋perpᵇA->getVector());
    }

    /**
     * @test Axiom: |projᵇA|² + |perpᵇA|² = |A|²
     * Sum of squared lengths of proj and perp equals squared length of A
     * @dataProvider dataProviderForProjPerp
     */
    public function testProjPerpSumOfSquares(array $A, array $B)
    {
        // Given
        $A = new Vector($A);
        $B = new Vector($B);

        // When
        $│A│²      = ($A->length()) ** 2;
        $│projᵇA│² = ($A->projection($B)->length()) ** 2;
        $│perpᵇA│² = ($A->perp($B)->length()) ** 2;

        // Then
        $this->assertEquals($│A│², $│projᵇA│² + $│perpᵇA│²);
    }

    /**
     * @test Axiom: projᵇA ⋅ perpᵇA = 0
     * Dot product of proj and perp of A on B is 0
     * @dataProvider dataProviderForProjPerp
     */
    public function testProjPerpDotProductEqualsZero(array $A, array $B)
    {
        // Given
        $A = new Vector($A);
        $B = new Vector($B);

        // When
        $projᵇA = $A->projection($B);
        $perpᵇA = $A->perp($B);

        $projᵇA⋅perpᵇA = $projᵇA->dotProduct($perpᵇA);

        // Then
        $this->assertEquals(0, $projᵇA⋅perpᵇA);
    }

    /**
     * @test Axiom: |projᵇA⊥ ⋅ perpᵇA| = |projᵇA| |perpᵇA|
     * Absolute value of proj and perp dot product equals product of their lengths.
     * @dataProvider dataProviderForProjPerp
     */
    public function testProjPerpPerpDotProductEqualsProductOfLengths(array $A, array $B)
    {
        // Given
        $A = new Vector($A);
        $B = new Vector($B);

        // When
        $projᵇA  = $A->projection($B);
        $projᵇA⊥ = $A->projection($B)->perpendicular();
        $perpᵇA  = $A->perp($B);

        $projᵇA⊥⋅perpᵇA = \abs($projᵇA⊥->dotProduct($perpᵇA));
        $│projᵇA│       = $projᵇA->length();
        $│perpᵇA│       = $perpᵇA->length();

        // Then
        $this->assertEquals($projᵇA⊥⋅perpᵇA, $│projᵇA│ * $│perpᵇA│);
    }

    public function dataProviderForProjPerp(): array
    {
        return [
            [
                [1, 2],
                [2, 3],
            ],
            [
                [2, 2],
                [2, 7],
            ],
            [
                [1, 1],
                [2, 2],
            ],
            [
                [2, 2],
                [1, 1],
            ],
            [
                [5, 9],
                [12, 8],
            ],
            [
                [5, 2],
                [3, 3],
            ],
        ];
    }
}
