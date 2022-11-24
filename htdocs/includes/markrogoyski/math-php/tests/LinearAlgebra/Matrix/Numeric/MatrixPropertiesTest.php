<?php

namespace MathPHP\Tests\LinearAlgebra\Matrix\Numeric;

use MathPHP\LinearAlgebra\MatrixFactory;
use MathPHP\LinearAlgebra\NumericMatrix;

class MatrixPropertiesTest extends \PHPUnit\Framework\TestCase
{
    use \MathPHP\Tests\LinearAlgebra\Fixture\MatrixDataProvider;

    /**
     * @test         isSymmetric returns true for symmetric matrices.
     * @dataProvider dataProviderForSymmetricMatrix
     * @param        array $A
     * @throws       \Exception
     */
    public function testIsSymmetric(array $A)
    {
        // Given
        $A = MatrixFactory::create($A);

        // Then
        $this->assertTrue($A->isSymmetric());
    }

    /**
     * @test         isSymmetric returns false for nonsymmetric matrices.
     * @dataProvider dataProviderForNotSymmetricMatrix
     * @dataProvider dataProviderForNotSquareMatrix
     * @param        array $A
     * @throws       \Exception
     */
    public function testIsNotSymmetric(array $A)
    {
        // Given
        $A = MatrixFactory::create($A);

        // Then
        $this->assertFalse($A->isSymmetric());
    }

    /**
     * @test         isSkewSymmetric returns true for skew-symmetric matrices.
     * @dataProvider dataProviderForSkewSymmetricMatrix
     * @param        array $A
     * @throws       \Exception
     */
    public function testIsSkewSymmetric(array $A)
    {
        // Given
        $A = MatrixFactory::create($A);

        // Then
        $this->assertTrue($A->isSkewSymmetric());
    }

    /**
     * @test         isSkewSymmetric returns false for non skew-symmetric matrices.
     * @dataProvider dataProviderForNotSkewSymmetricMatrix
     * @dataProvider dataProviderForNotSymmetricMatrix
     * @dataProvider dataProviderForNotSquareMatrix
     * @param        array $A
     * @throws       \Exception
     */
    public function testIsNotSkewSymmetric(array $A)
    {
        // Given
        $A = MatrixFactory::create($A);

        // Then
        $this->assertFalse($A->isSkewSymmetric());
    }


    /**
     * @test         isSingular returns true for a singular matrix.
     * @dataProvider dataProviderForSingularMatrix
     * @param        array $A
     * @throws       \Exception
     */
    public function testIsSingular(array $A)
    {
        // Given
        $A = MatrixFactory::create($A);

        // Then
        $this->assertTrue($A->isSingular());
    }

    /**
     * @test         isSingular returns false for a nonsingular matrix.
     * @dataProvider dataProviderForNonsingularMatrix
     * @param        array $A
     * @throws       \Exception
     */
    public function testIsSingularFalseForNonsingularMatrix(array $A)
    {
        // Given
        $A = MatrixFactory::create($A);

        // Then
        $this->assertFalse($A->isSingular());
    }

    /**
     * @test         isNonsingular returns true for a nonsingular matrix.
     * @dataProvider dataProviderForNonsingularMatrix
     * @param        array $A
     * @throws       \Exception
     */
    public function testIsNonsingular(array $A)
    {
        // Given
        $A = MatrixFactory::create($A);

        // Then
        $this->assertTrue($A->isNonsingular());
    }

    /**
     * @test         isInvertible returns true for a invertible matrix.
     * @dataProvider dataProviderForNonsingularMatrix
     * @param        array $A
     * @throws       \Exception
     */
    public function testIsInvertible(array $A)
    {
        // Given
        $A = MatrixFactory::create($A);

        // Then
        $this->assertTrue($A->isInvertible());
    }

    /**
     * @test         isNonsingular returns false for a singular matrix.
     * @dataProvider dataProviderForSingularMatrix
     * @param        array $A
     * @throws       \Exception
     */
    public function testIsNonsingularFalseForSingularMatrix(array $A)
    {
        // Given
        $A = MatrixFactory::create($A);

        $this->assertFalse($A->isNonsingular());
    }

    /**
     * @test         isInvertible returns false for a non-invertible matrix.
     * @dataProvider dataProviderForSingularMatrix
     * @param        array $A
     * @throws       \Exception
     */
    public function testIsInvertibleFalseForNonInvertibleMatrix(array $A)
    {
        // Given
        $A = MatrixFactory::create($A);

        // Then
        $this->assertFalse($A->isInvertible());
    }

    /**
     * @test         isPositiveDefinite returns true for a positive definite square matrix.
     * @dataProvider dataProviderForPositiveDefiniteMatrix
     * @param        array $A
     * @throws       \Exception
     */
    public function testIsPositiveDefinite(array $A)
    {
        // Given
        $A = MatrixFactory::create($A);

        // Then
        $this->assertTrue($A->isPositiveDefinite());
    }

    /**
     * @test         isPositiveDefinite returns false for a non positive definite square matrix.
     * @dataProvider dataProviderForNotPositiveDefiniteMatrix
     * @param        array $A
     * @throws       \Exception
     */
    public function testIsNotPositiveDefinite(array $A)
    {
        // Given
        $A = MatrixFactory::create($A);

        // Then
        $this->assertFalse($A->isPositiveDefinite());
    }

    /**
     * @test         isPositiveSemidefinite returns true for a positive definite square matrix.
     * @dataProvider dataProviderForPositiveSemidefiniteMatrix
     * @param        array $A
     * @throws       \Exception
     */
    public function testIsPositiveSemidefinite(array $A)
    {
        // Given
        $A = MatrixFactory::create($A);

        // Then
        $this->assertTrue($A->isPositiveSemidefinite());
    }

    /**
     * @test         isPositiveSemidefinite returns false for a non positive semidefinite square matrix.
     * @dataProvider dataProviderForNotPositiveSemidefiniteMatrix
     * @param        array $A
     * @throws       \Exception
     */
    public function testIsNotPositiveSemiDefinite(array $A)
    {
        // Given
        $A = MatrixFactory::create($A);

        // Then
        $this->assertFalse($A->isPositiveSemidefinite());
    }

    /**
     * @test         isNegativeDefinite returns true for a negative definite square matrix.
     * @dataProvider dataProviderForNegativeDefiniteMatrix
     * @param        array $A
     * @throws       \Exception
     */
    public function testIsNegativeDefinite(array $A)
    {
        // Given
        $A = MatrixFactory::create($A);

        // Then
        $this->assertTrue($A->isNegativeDefinite());
    }

    /**
     * @test         isNegativeDefinite returns false for a non negative definite square matrix.
     * @dataProvider dataProviderForNotNegativeDefiniteMatrix
     * @param        array $A
     * @throws       \Exception
     */
    public function testIsNotNegativeDefinite(array $A)
    {
        // Given
        $A = MatrixFactory::create($A);

        // Then
        $this->assertFalse($A->isNegativeDefinite());
    }

    /**
     * @test         isNegativeSemidefinite returns true for a negative semidefinite square matrix.
     * @dataProvider dataProviderForNegativeSemidefiniteMatrix
     * @param        array $A
     * @throws       \Exception
     */
    public function testIsNegativeSemidefinite(array $A)
    {
        // Given
        $A = MatrixFactory::create($A);

        // Then
        $this->assertTrue($A->isNegativeSemidefinite());
    }

    /**
     * @test         isNegativeSemidefinite returns false for a non negative semidefinite square matrix.
     * @dataProvider dataProviderForNotNegativeSemidefiniteMatrix
     * @param        array $A
     * @throws       \Exception
     */
    public function testIsNotNegativeSemidefinite(array $A)
    {
        // Given
        $A = MatrixFactory::create($A);

        // Then
        $this->assertFalse($A->isNegativeSemidefinite());
    }

    /**
     * @test     Non square matrix is not any definite.
     * @throws       \Exception
     */
    public function testNonSquareMatrixIsNotAnyDefinite()
    {
        // Given
        $A = new NumericMatrix([
            [1, 2, 3],
            [2, 3, 4],
        ]);

        // Then
        $this->assertFalse($A->isPositiveDefinite());
        $this->assertFalse($A->isPositiveSemidefinite());
        $this->assertFalse($A->isNegativeDefinite());
        $this->assertFalse($A->isNegativeSemidefinite());
    }

    /**
     * @test     Non symmetric square matrix is not any definite.
     * @throws       \Exception
     */
    public function testNonSymmetricSquareMatrixIsNotAnyDefinite()
    {
        // Given
        $A = new NumericMatrix([
            [1, 2, 3],
            [9, 8, 4],
            [6, 2, 5],
        ]);

        // Then
        $this->assertFalse($A->isPositiveDefinite());
        $this->assertFalse($A->isPositiveSemidefinite());
        $this->assertFalse($A->isNegativeDefinite());
        $this->assertFalse($A->isNegativeSemidefinite());
    }

    /**
     * @test         isUpperTriangular returns true for an upper triangular matrix
     * @dataProvider dataProviderForUpperTriangularMatrix
     * @param        array $U
     * @throws       \Exception
     */
    public function testIsUpperTriangular(array $U)
    {
        // Given
        $U = MatrixFactory::create($U);

        // Then
        $this->assertTrue($U->isUpperTriangular());
    }

    /**
     * @test         isUpperTriangular returns false for a non upper triangular matrix
     * @dataProvider dataProviderForNotTriangularMatrix
     * @param        array $A
     * @throws       \Exception
     */
    public function testIsNotUpperTriangular(array $A)
    {
        // Given
        $A = MatrixFactory::create($A);

        // Then
        $this->assertFalse($A->isUpperTriangular());
    }

    /**
     * @test         isLowerTriangular returns true for an upper triangular matrix
     * @dataProvider dataProviderForLowerTriangularMatrix
     * @param        array $L
     * @throws       \Exception
     */
    public function testIsLowerTriangular(array $L)
    {
        // Given
        $L = MatrixFactory::create($L);

        // Then
        $this->assertTrue($L->isLowerTriangular());
    }

    /**
     * @test         isLowerTriangular returns false for a non upper triangular matrix
     * @dataProvider dataProviderForNotTriangularMatrix
     * @param        array $A
     * @throws       \Exception
     */
    public function testIsNotLowerTriangular(array $A)
    {
        // Given
        $A = MatrixFactory::create($A);

        // Then
        $this->assertFalse($A->isLowerTriangular());
    }

    /**
     * @test         isTriangular returns true for a lower triangular matrix
     * @dataProvider dataProviderForLowerTriangularMatrix
     * @param        array $L
     * @throws       \Exception
     */
    public function testIsTriangularForLowerTriangular(array $L)
    {
        // Given
        $L = MatrixFactory::create($L);

        $this->assertTrue($L->isTriangular());
    }

    /**
     * @test         isTriangular returns true for an upper triangular matrix
     * @dataProvider dataProviderForUpperTriangularMatrix
     * @param        array $U
     * @throws       \Exception
     */
    public function testIsTriangularForUpperTriangular(array $U)
    {
        // Given
        $U = MatrixFactory::create($U);

        // Then
        $this->assertTrue($U->isTriangular());
    }

    /**
     * @test         isTriangular returns false for a non triangular matrix
     * @dataProvider dataProviderForNotTriangularMatrix
     * @param        array $A
     * @throws       \Exception
     */
    public function testIsNotTriangular(array $A)
    {
        // Given
        $A = MatrixFactory::create($A);

        // Then
        $this->assertFalse($A->isTriangular());
    }

    /**
     * @test         isDiagonal returns true for a diagonal matrix
     * @dataProvider dataProviderForDiagonalMatrix
     * @param        array $D
     * @throws       \Exception
     */
    public function testIsDiagonal(array $D)
    {
        // Given
        $D = MatrixFactory::create($D);

        // Then
        $this->assertTrue($D->isDiagonal());
    }

    /**
     * @test         isDiagonal returns false for a non diagonal matrix
     * @dataProvider dataProviderForNotDiagonalMatrix
     * @param        array $A
     * @throws       \Exception
     */
    public function testIsDiagonalForLowerTriangular(array $A)
    {
        // Given
        $A = MatrixFactory::create($A);

        // Then
        $this->assertFalse($A->isDiagonal());
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

    /**
     * @test         isRectangularDiagonal returns false appropriately
     * @dataProvider dataProviderForNotRectangularDiagonalMatrix
     * @param        array $D
     * @throws       \Exception
     */
    public function testIsNotRectangularDiagonal(array $D)
    {
        // Given
        $D = MatrixFactory::create($D);

        // Then
        $this->assertFalse($D->isRectangularDiagonal());
    }

    /**
     * @test         isRef returns true for a matrix in row echelon form
     * @dataProvider dataProviderForRefMatrix
     * @param        array $A
     * @throws       \Exception
     */
    public function testIsRef(array $A)
    {
        // Given
        $A = MatrixFactory::create($A);

        // Then
        $this->assertTrue($A->isRef());
    }

    /**
     * @test         isRef returns false for a matrix not in row echelon form
     * @dataProvider dataProviderForNotRefMatrix
     * @param        array $A
     * @throws       \Exception
     */
    public function testIsNotRef(array $A)
    {
        // Given
        $A = MatrixFactory::create($A);

        // Then
        $this->assertFalse($A->isRef());
    }

    /**
     * @test         isRef returns true for a matrix in row echelon form
     * @dataProvider dataProviderForRrefMatrix
     * @param        array $A
     * @throws       \Exception
     */
    public function testIsRref(array $A)
    {
        // Given
        $A = MatrixFactory::create($A);

        $this->assertTrue($A->isRref());
    }

    /**
     * @test         isRef returns false for a matrix not in row echelon form
     * @dataProvider dataProviderForNotRefMatrix
     * @param        array $A
     * @throws       \Exception
     */
    public function testIsNotRref(array $A)
    {
        // Given
        $A = MatrixFactory::create($A);

        // Then
        $this->assertFalse($A->isRref());
    }

    /**
     * @test         isRef returns false for a ref matrix
     * @dataProvider dataProviderForNotRrefMatrix
     * @param        array $A
     * @throws       \Exception
     */
    public function testIsNotRrefForRefMatrix(array $A)
    {
        // Given
        $A = MatrixFactory::create($A);

        // Then
        $this->assertFalse($A->isRref());
    }

/**
     * @test         isIdempotent returns true for an Idempotent matrix
     * @dataProvider dataProviderForIdempotentMatrix
     * @param        array $A
     * @throws       \Exception
     */
    public function testIsIdempotent(array $A)
    {
        // Given
        $A = MatrixFactory::create($A);

        // Then
        $this->assertTrue($A->isIdempotent());
    }

    /**
     * @test         isIdempotent returns false for a non-Idempotent matrix
     * @dataProvider dataProviderForNotIdempotentMatrix
     * @param        array $A
     * @throws       \Exception
     */
    public function testIsNotIdempotent(array $A)
    {
        // Given
        $A = MatrixFactory::create($A);

        // Then
        $this->assertFalse($A->isIdempotent());
    }

    /**
     * @test         isNilpotent returns true for a nilpotent matrix
     * @dataProvider dataProviderForNilpotentMatrix
     * @param        array $A
     * @throws       \Exception
     */
    public function testIsNilpotent(array $A)
    {
        // Given
        $A = MatrixFactory::create($A);

        // Then
        $this->assertTrue($A->isNilpotent());
    }

    /**
     * @test         isNilpotent returns false for a non-nilpotent matrix
     * @dataProvider dataProviderForNotNilpotentMatrix
     * @param        array $A
     * @throws       \Exception
     */
    public function testIsNotNilpotent(array $A)
    {
        // Given
        $A = MatrixFactory::create($A);

        // Then
        $this->assertFalse($A->isNilpotent());
    }

    /**
     * @test         isInvolutory returns true for a Involutory matrix
     * @dataProvider dataProviderForInvolutoryMatrix
     * @param        array $A
     * @throws       \Exception
     */
    public function testIsInvolutory(array $A)
    {
        // Given
        $A = MatrixFactory::create($A);

        // Then
        $this->assertTrue($A->isInvolutory());
    }

    /**
     * @test         isInvolutory returns false for a non-Involutory matrix
     * @dataProvider dataProviderForNotInvolutoryMatrix
     * @param        array $A
     * @throws       \Exception
     */
    public function testIsNotInvolutory(array $A)
    {
        // Given
        $A = MatrixFactory::create($A);

        // Then
        $this->assertFalse($A->isInvolutory());
    }

    /**
     * @test         isSignature returns true for a Signature matrix
     * @dataProvider dataProviderForSignatureMatrix
     * @param        array $A
     * @throws       \Exception
     */
    public function testIsSignature(array $A)
    {
        // Given
        $A = MatrixFactory::create($A);

        // Then
        $this->assertTrue($A->isSignature());
    }

    /**
     * @test         isSignature returns false for a non-Signature matrix
     * @dataProvider dataProviderForNotSignatureMatrix
     * @param        array $A
     * @throws       \Exception
     */
    public function testIsNotSignature(array $A)
    {
        // Given
        $A = MatrixFactory::create($A);

        $this->assertFalse($A->isSignature());
    }

    /**
     * @test         isUpperBidiagonal returns true for an upper bidiagonal matrix
     * @dataProvider dataProviderForUpperBidiagonalMatrix
     * @param        array $A
     * @throws       \Exception
     */
    public function testIsUpperBidiagonal(array $A)
    {
        // Given
        $A = MatrixFactory::create($A);

        // Then
        $this->assertTrue($A->isUpperBidiagonal());
    }

    /**
     * @test         isUpperBidiagonal returns false for a non upper bidiagonal matrix
     * @dataProvider dataProviderForNotUpperBidiagonalMatrix
     * @param        array $A
     * @throws       \Exception
     */
    public function testIsNotUpperBidiagonal(array $A)
    {
        // Given
        $A = MatrixFactory::create($A);

        // Then
        $this->assertFalse($A->isUpperBidiagonal());
    }

    /**
     * @test         isLowerBidiagonal returns true for a lower bidiagonal matrix
     * @dataProvider dataProviderForLowerBidiagonalMatrix
     * @param        array $A
     * @throws       \Exception
     */
    public function testIsLowerBidiagonal(array $A)
    {
        // Given
        $A = MatrixFactory::create($A);

        $this->assertTrue($A->isLowerBidiagonal());
    }

    /**
     * @test         isLowerBidiagonal returns false for a non lower bidiagonal matrix
     * @dataProvider dataProviderForNotLowerBidiagonalMatrix
     * @param        array $A
     * @throws       \Exception
     */
    public function testIsNotLowerBidiagonal(array $A)
    {
        $A = MatrixFactory::create($A);

        // Then
        $this->assertFalse($A->isLowerBidiagonal());
    }

    /**
     * @test         isBidiagonal returns true for a lower bidiagonal matrix
     * @dataProvider dataProviderForLowerBidiagonalMatrix
     * @param        array $A
     * @throws       \Exception
     */
    public function testLowerBidiagonalIsBidiagonal(array $A)
    {
        // Given
        $A = MatrixFactory::create($A);

        // Then
        $this->assertTrue($A->isBidiagonal());
    }

    /**
     * @test         isBidiagonal returns true for an upper bidiagonal matrix
     * @dataProvider dataProviderForUpperBidiagonalMatrix
     * @param        array $A
     * @throws       \Exception
     */
    public function testUpperBidiagonalIsBidiagonal(array $A)
    {
        // Given
        $A = MatrixFactory::create($A);

        // Then
        $this->assertTrue($A->isBidiagonal());
    }

    /**
     * @test         isBidiagonal returns false for a non bidiagonal matrix
     * @dataProvider dataProviderForNotBidiagonalMatrix
     * @param        array $A
     * @throws       \Exception
     */
    public function testIsNotBidiagonal(array $A)
    {
        // Given
        $A = MatrixFactory::create($A);

        $this->assertFalse($A->isBidiagonal());
    }

    /**
     * @test         isTridiagonal returns true for a tridiagonal matrix
     * @dataProvider dataProviderForTridiagonalMatrix
     * @param        array $A
     * @throws       \Exception
     */
    public function testIsTridiagonal(array $A)
    {
        // Given
        $A = MatrixFactory::create($A);

        // Then
        $this->assertTrue($A->isTridiagonal());
    }

    /**
     * @test         isTridiagonal returns false for a non tridiagonal matrix
     * @dataProvider dataProviderForNotTridiagonalMatrix
     * @param        array $A
     * @throws       \Exception
     */
    public function testIsNotTridiagonal(array $A)
    {
        // Given
        $A = MatrixFactory::create($A);

        // Then
        $this->assertFalse($A->isTridiagonal());
    }

    /**
     * @test         isUpperHessenberg returns true for an upper Hessenberg matrix
     * @dataProvider dataProviderForUpperHessenbergMatrix
     * @param        array $A
     * @throws       \Exception
     */
    public function testIsUpperHessenberg(array $A)
    {
        // Given
        $A = MatrixFactory::create($A);

        $this->assertTrue($A->isUpperHessenberg());
    }

    /**
     * @test         isUpperHessenberg returns false for a non upper Hessenberg matrix
     * @dataProvider dataProviderForNotUpperHessenbergMatrix
     * @param        array $A
     * @throws       \Exception
     */
    public function testIsNotUpperHessenberg(array $A)
    {
        // Given
        $A = MatrixFactory::create($A);

        // Then
        $this->assertFalse($A->isUpperHessenberg());
    }

    /**
     * @test         isLowerHessenberg returns true for an lower Hessenberg matrix
     * @dataProvider dataProviderForLowerHessenbergMatrix
     * @param        array $A
     * @throws       \Exception
     */
    public function testIsLowerHessenberg(array $A)
    {
        // Given
        $A = MatrixFactory::create($A);

        // Then
        $this->assertTrue($A->isLowerHessenberg());
    }

    /**
     * @test         isLowerHessenberg returns false for a non lower Hessenberg matrix
     * @dataProvider dataProviderForNotLowerHessenbergMatrix
     * @param        array $A
     * @throws       \Exception
     */
    public function testIsNotLowerHessenberg(array $A)
    {
        // Given
        $A = MatrixFactory::create($A);

        // Then
        $this->assertFalse($A->isLowerHessenberg());
    }

    /**
     * @test         isOrthogonal
     * @dataProvider dataProviderForOrthogonalMatrix
     * @param        array $A
     * @throws       \Exception
     */
    public function testIsOrthogonal2(array $A)
    {
        // Given
        $A = MatrixFactory::create($A);

        // Then
        $this->assertTrue($A->isOrthogonal());
    }

    /**
     * @test         isOrthogonal when not orthogonal
     * @dataProvider dataProviderForNonOrthogonalMatrix
     * @param        array $A
     * @throws       \Exception
     */
    public function testIsOrthogonalWhenNotOrthogonal(array $A)
    {
        // Given
        $A = MatrixFactory::create($A);

        // Then
        $this->assertFalse($A->isOrthogonal());
    }

    /**
     * @test         isNormal
     * @dataProvider dataProviderForOrthogonalMatrix
     * @dataProvider dataProviderForSkewSymmetricMatrix
     * @dataProvider dataProviderForDiagonalMatrix
     * @param        array $A
     * @throws       \Exception
     */
    public function testisNormal(array $A)
    {
        // Given
        $A = MatrixFactory::create($A);

        // Then
        $this->assertTrue($A->isNormal());
    }

    /**
     * @test         isNormal when not normal
     * @dataProvider dataProviderForNonNormalMatrix
     * @param        array $A
     * @throws       \Exception
     */
    public function testIsNormalWhenNotNormal(array $A)
    {
        // Given
        $A = MatrixFactory::create($A);

        // Then
        $this->assertFalse($A->isNormal());
    }

    /**
     * @test         isUnitary
     * @dataProvider dataProviderForOrthogonalMatrix
     * @param        array $A
     * @throws       \Exception
     */
    public function testIsUnitary(array $A)
    {
        // Given
        $A = MatrixFactory::create($A);

        // Then
        $this->assertTrue($A->isUnitary());
    }

    /**
     * @test         isUnitary when not unitary
     * @dataProvider dataProviderForNonOrthogonalMatrix
     * @param        array $A
     * @throws       \Exception
     */
    public function testIsUnitaryWhenNotUnitary(array $A)
    {
        // Given
        $A = MatrixFactory::create($A);

        // Then
        $this->assertFalse($A->isUnitary());
    }

    /**
     * @test         isHermitian returns true for hermitian matrices.
     * @dataProvider dataProviderForSymmetricMatrix
     * @param        array $A
     * @throws       \Exception
     */
    public function testIsHermitian(array $A)
    {
        // Given
        $A = MatrixFactory::create($A);

        // Then
        $this->assertTrue($A->isHermitian());
    }

    /**
     * @test         isHermitian returns false for nonhermitian matrices.
     * @dataProvider dataProviderForNotSymmetricMatrix
     * @dataProvider dataProviderForNotSquareMatrix
     * @param        array $A
     * @throws       \Exception
     */
    public function testIsHermitianWhenNotHermitian(array $A)
    {
        // Given
        $A = MatrixFactory::create($A);

        // Then
        $this->assertFalse($A->isHermitian());
    }
}
