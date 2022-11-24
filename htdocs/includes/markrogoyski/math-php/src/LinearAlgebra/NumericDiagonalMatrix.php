<?php

namespace MathPHP\LinearAlgebra;

use MathPHP\Functions\Map\Single;
use MathPHP\Exception\MatrixException;

/**
 * Diagonal matrix
 * Elements along the main diagonal are the only non-zero elements (may also be zero).
 * The off-diagonal elements are all zero
 */
class NumericDiagonalMatrix extends NumericSquareMatrix
{
    /**
     * Constructor
     *
     * @param array $A
     */
    public function __construct(array $A)
    {
        parent::__construct($A);

        if (!parent::isLowerTriangular() || !parent::isUpperTriangular()) {
            throw new MatrixException('Trying to construct DiagonalMatrix with non-diagonal elements: ' . \print_r($this->A, true));
        }
    }

    /**
     * Diagonal matrix must be symmetric
     * @inheritDoc
     */
    public function isSymmetric(): bool
    {
        return true;
    }

    /**
     * Diagonal matrix must be lower triangular
     * @inheritDoc
     */
    public function isLowerTriangular(): bool
    {
        return true;
    }

    /**
     * Diagonal matrix must be upper triangular
     * @inheritDoc
     */
    public function isUpperTriangular(): bool
    {
        return true;
    }

    /**
     * Diagonal matrix must be triangular
     * @inheritDoc
     */
    public function isTriangular(): bool
    {
        return true;
    }

    /**
     * Diagonal matrix must be diagonal
     * @inheritDoc
     */
    public function isDiagonal(): bool
    {
        return true;
    }

    /**
     * Inverse of a diagonal matrix is the reciprocals of the diagonal elements
     *
     * @return NumericMatrix
     */
    public function inverse(): NumericMatrix
    {
        return MatrixFactory::diagonal(Single::reciprocal($this->getDiagonalElements()));
    }
}
