<?php

namespace MathPHP\LinearAlgebra\Decomposition;

use MathPHP\Exception;
use MathPHP\LinearAlgebra\NumericMatrix;
use MathPHP\LinearAlgebra\MatrixFactory;

/**
 * Cholesky decomposition
 * A decomposition of a square, positive definitive matrix
 * into the product of a lower triangular matrix and its transpose.
 *
 * https://en.wikipedia.org/wiki/Cholesky_decomposition
 *
 * A = LLᵀ
 *
 *     [a₁₁ a₁₂ a₁₃]
 * A = [a₂₁ a₂₂ a₂₃]
 *     [a₃₁ a₃₂ a₃₃]
 *
 *     [l₁₁  0   0 ] [l₁₁ l₁₂ l₁₃]
 * A = [l₂₁ l₂₂  0 ] [ 0  l₂₂ l₂₃] ≡ LLᵀ
 *     [l₃₁ l₃₂ l₃₃] [ 0   0  l₃₃]
 *
 * Diagonal elements
 *          ____________
 *         /     ᵢ₋₁
 * lᵢᵢ =  / aᵢᵢ - ∑l²ᵢₓ
 *       √       ˣ⁼¹
 *
 * Elements below diagonal
 *
 *        1   /      ᵢ₋₁     \
 * lⱼᵢ = --- |  aⱼᵢ - ∑lⱼₓlᵢₓ |
 *       lᵢᵢ  \      ˣ⁼¹     /
 *
 * @property-read NumericMatrix $L  Lower triangular matrix
 * @property-read NumericMatrix $LT Transpose of lower triangular matrix
 * @property-read NumericMatrix $Lᵀ Transpose of lower triangular matrix
 */
class Cholesky extends Decomposition
{
    /** @var NumericMatrix Lower triangular matrix L of A = LLᵀ */
    private $L;

    /** @var NumericMatrix Transpose of lower triangular matrix of A = LLᵀ */
    private $Lᵀ;

    /**
     * Cholesky constructor
     *
     * @param NumericMatrix $L  Lower triangular matrix
     * @param NumericMatrix $Lᵀ Transpose of lower triangular matrix
     */
    private function __construct(NumericMatrix $L, NumericMatrix $Lᵀ)
    {
        $this->L  = $L;
        $this->Lᵀ = $Lᵀ;
    }

    /**
     * Decompose a matrix into Cholesky decomposition
     * Factory method to create Cholesky decomposition.
     *
     * @param NumericMatrix $A
     *
     * @return Cholesky Lower triangular matrix L and transpose Lᵀ of A = LLᵀ
     *
     * @throws Exception\BadDataException
     * @throws Exception\BadParameterException
     * @throws Exception\IncorrectTypeException
     * @throws Exception\MathException
     * @throws Exception\MatrixException
     * @throws Exception\OutOfBoundsException
     */
    public static function decompose(NumericMatrix $A): Cholesky
    {
        if (!$A->isPositiveDefinite()) {
            throw new Exception\MatrixException('Matrix must be positive definite for Cholesky decomposition');
        }

        $m = $A->getM();
        $L = MatrixFactory::zero($m, $m)->getMatrix();

        for ($j = 0; $j < $m; $j++) {
            for ($i = 0; $i < ($j + 1); $i++) {
                $∑lⱼₓlᵢₓ = 0;
                for ($x = 0; $x < $i; $x++) {
                    $∑lⱼₓlᵢₓ += $L[$j][$x] * $L[$i][$x];
                }
                $L[$j][$i] = ($j === $i)
                    ? \sqrt($A[$j][$j] - $∑lⱼₓlᵢₓ)
                    : (1 / $L[$i][$i] * ($A[$j][$i] - $∑lⱼₓlᵢₓ));
            }
        }

        $L  = MatrixFactory::create($L);
        $Lᵀ = $L->transpose();

        return new Cholesky($L, $Lᵀ);
    }

    /**
     * Get L, or Lᵀ matrix
     *
     * @param string $name
     *
     * @return NumericMatrix
     *
     * @throws Exception\MatrixException
     */
    public function __get(string $name): NumericMatrix
    {
        switch ($name) {
            case 'L':
                return $this->L;

            case 'LT':
            case 'Lᵀ':
                return $this->Lᵀ;

            default:
                throw new Exception\MatrixException("Cholesky class does not have a gettable property: $name");
        }
    }
}
