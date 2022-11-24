<?php

namespace MathPHP\LinearAlgebra;

use MathPHP\Exception;
use MathPHP\Number\ObjectArithmetic;

/**
 * ObjectSquareMatrix
 *
 * The objectSquareMatrix extends Matrix functions to a matrix of objects.
 * The object must implement the MatrixArithmetic interface to prove
 * compatibility. It extends the SquareMatrix in order to use Matrix::minor().
 */
class ObjectSquareMatrix extends ObjectMatrix
{
    /**
     * @param ObjectArithmetic[][] $A n x n matrix of objects
     *
     * @throws Exception\BadDataException if any rows have a different column count
     * @throws Exception\IncorrectTypeException if all elements are not the same class
     * @throws Exception\IncorrectTypeException if The class does not implement the ObjectArithmetic interface
     * @throws Exception\MatrixException if not square
     * @throws Exception\MathException
     */
    public function __construct(array $A)
    {
        parent::__construct($A);

        if ($this->m !== $this->n) {
            throw new Exception\MatrixException("Not a square matrix; row count and column count differ: {$this->m}x{$this->n}");
        }
    }

    /**
     * Square matrix must be square
     *
     * @return bool
     */
    public function isSquare(): bool
    {
        return true;
    }
}
