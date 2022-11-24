<?php

namespace MathPHP\LinearAlgebra;

use MathPHP\Exception;
use MathPHP\Number\ArbitraryInteger;
use MathPHP\Number\ObjectArithmetic;

/**
 * ObjectMatrix
 *
 * The ObjectMatrix extends Matrix functions to a matrix of objects.
 * The object must implement the MatrixArithmetic interface to prove
 * compatibility. It extends the SquareMatrix in order to use Matrix::minor().
 */
class ObjectMatrix extends Matrix implements ObjectArithmetic
{
    /**
     * The type of object that is being stored in this Matrix
     * @var string
     */
    protected $object_type;

    /**
     * The constructor follows performs all the same checks as the parent, but also checks that
     * all of the elements in the array are of the same data type.
     *
     * @param ObjectArithmetic[][] $A m x n matrix of objects
     *
     * @throws Exception\BadDataException if any rows have a different column count
     * @throws Exception\IncorrectTypeException if all elements are not the same class
     * @throws Exception\IncorrectTypeException if The class does not implement the ObjectArithmetic interface
     * @throws Exception\MathException
     */
    public function __construct(array $A)
    {
        $this->A       = $A;
        $this->m       = \count($A);
        $this->n       = $this->m > 0 ? \count($A[0]) : 0;
        $this->catalog = new MatrixCatalog();

        $this->validateMatrixData();
    }

    /**
     * Validate the matrix is entirely m x n
     *
     * @throws Exception\BadDataException if any rows have a different column count
     * @throws Exception\IncorrectTypeException if all elements are not the same class
     * @throws Exception\IncorrectTypeException if The class does not implement the ObjectArithmetic interface
     * @throws Exception\MathException
     */
    protected function validateMatrixData()
    {
        if ($this->A[0][0] instanceof ObjectArithmetic) {
            $this->object_type = \get_class($this->A[0][0]);
        } else {
            throw new Exception\IncorrectTypeException("The object must implement the interface.");
        }
        foreach ($this->A as $i => $row) {
            foreach ($row as $object) {
                if (\get_class($object) != $this->object_type) {
                    throw new Exception\IncorrectTypeException("All elements in the matrix must be of the same type.");
                }
            }
        }
        foreach ($this->A as $i => $row) {
            if (\count($row) !== $this->n) {
                throw new Exception\BadDataException("Row $i has a different column count: " . \count($row) . "; was expecting {$this->n}.");
            }
        }
    }

    /**
     * Get the type of objects that are stored in the matrix
     *
     * @return string The class of the objects
     */
    public function getObjectType(): string
    {
        return $this->object_type;
    }

    /**
     * Zero value: [[0]]
     *
     * @return ObjectMatrix
     */
    public static function createZeroValue(): ObjectArithmetic
    {
        return new ObjectMatrix([[new ArbitraryInteger(0)]]);
    }

    /***************************************************************************
     * MATRIX COMPARISONS
     *  - isEqual
     ***************************************************************************/

    /**
     * Is this matrix equal to some other matrix?
     *
     * @param Matrix $B
     *
     * @return bool
     */
    public function isEqual(Matrix $B): bool
    {
        if (!$this->isEqualSizeAndType($B)) {
            return false;
        }

        $m = $this->m;
        $n = $this->n;
        // All elements are the same
        for ($i = 0; $i < $m; $i++) {
            for ($j = 0; $j < $n; $j++) {
                if ($this->A[$i][$j] != $B[$i][$j]) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Check that the matrices are the same size and of the same type
     *
     * @throws Exception\MatrixException if matrices have a different number of rows or columns
     * @throws Exception\IncorrectTypeException if the two matricies are not the same class
     */
    private function checkEqualSizes(Matrix $B)
    {
        if ($B->getM() !== $this->m || $B->getN() !== $this->n) {
            throw new Exception\MatrixException('Matrices are different sizes');
        }
        if ($B->getObjectType() !== $this->object_type) {
            throw new Exception\IncorrectTypeException('Matrices must contain the same object types');
        }
    }

    /**************************************************************************
     * MATRIX ARITHMETIC OPERATIONS - Return a Matrix
     *  - add
     *  - subtract
     *  - multiply
     *  - scalarMultiply
     **************************************************************************/

    /**
     * {@inheritDoc}
     */
    public function add($B): Matrix
    {
        if (!$B instanceof Matrix) {
            throw new Exception\IncorrectTypeException('Can only do matrix addition with a Matrix');
        }
        $this->checkEqualSizes($B);
        $R = [];
        for ($i = 0; $i < $this->m; $i++) {
            for ($j = 0; $j < $this->n; $j++) {
                $R[$i][$j] = $this->A[$i][$j]->add($B[$i][$j]);
            }
        }
        return MatrixFactory::create($R);
    }

    /**
     * {@inheritDoc}
     */
    public function subtract($B): Matrix
    {
        if (!$B instanceof Matrix) {
            throw new Exception\IncorrectTypeException('Can only do matrix subtraction with a Matrix');
        }
        $this->checkEqualSizes($B);
        $R = [];
        for ($i = 0; $i < $this->m; $i++) {
            for ($j = 0; $j < $this->n; $j++) {
                $R[$i][$j] = $this->A[$i][$j]->subtract($B[$i][$j]);
            }
        }
        return MatrixFactory::create($R);
    }

    /**
     * {@inheritDoc}
     */
    public function multiply($B): Matrix
    {
        if ((!$B instanceof Matrix) && (!$B instanceof Vector)) {
            throw new Exception\IncorrectTypeException('Can only do matrix multiplication with a Matrix or Vector');
        }
        if ($B instanceof Vector) {
            $B = $B->asColumnMatrix();
        }
        if ($B->getM() !== $this->n) {
            throw new Exception\MatrixException("Matrix dimensions do not match");
        }
        $n = $B->getN();
        $m = $this->m;
        $R = [];
        for ($i = 0; $i < $m; $i++) {
            for ($j = 0; $j < $n; $j++) {
                $VA        = $this->getRow($i);
                $VB        = $B->getColumn($j);
                $R[$i][$j] = \array_reduce(
                    \array_map(
                        function (ObjectArithmetic $a, ObjectArithmetic $b) {
                            return $a->multiply($b);
                        },
                        $VA,
                        $VB
                    ),
                    function ($sum, $item) {
                        return $sum
                            ? $sum->add($item)
                            : $item;
                    }
                );
            }
        }
        return MatrixFactory::create($R);
    }

    /**
     * Scalar matrix multiplication
     * https://en.wikipedia.org/wiki/Matrix_multiplication#Scalar_multiplication
     *
     * @param  float $λ
     *
     * @return Matrix
     *
     * @throws Exception\BadParameterException if λ is not a number
     * @throws Exception\IncorrectTypeException
     */
    public function scalarMultiply($λ): Matrix
    {
        $R = [];

        for ($i = 0; $i < $this->m; $i++) {
            for ($j = 0; $j < $this->n; $j++) {
                $R[$i][$j] = $this->A[$i][$j]->multiply($λ);
            }
        }

        return MatrixFactory::create($R);
    }

    /**************************************************************************
     * MATRIX OPERATIONS - Return a value
     *  - trace
     *  - det
     *  - cofactor
     **************************************************************************/

    /**
     * {@inheritDoc}
     *
     * @return number
     *
     * @throws Exception\MatrixException if the matrix is not a square matrix
     */
    public function trace()
    {
        if (!$this->isSquare()) {
            throw new Exception\MatrixException('trace only works on a square matrix');
        }

        $m    = $this->m;
        $tr⟮A⟯ = $this->getObjectType()::createZeroValue();

        for ($i = 0; $i < $m; $i++) {
            $tr⟮A⟯ = $tr⟮A⟯->add($this->A[$i][$i]);
        }

        return $tr⟮A⟯;
    }

    /**
     * Determinant
     *
     * This implementation is simpler than that of the parent. Instead of
     * reducing the matrix, which requires division, we calculate the cofactors
     * for the first row of the matrix, perform element-wise multiplication, and
     * add the results of that row.
     *
     * This implementation also uses the same algorithm for 2x2 matrices. Adding
     * a special case may quicken code execution.
     *
     * @return ObjectArithmetic
     */
    public function det()
    {
        if ($this->catalog->hasDeterminant()) {
            return $this->catalog->getDeterminant();
        }

        if (!$this->isSquare()) {
            throw new Exception\MatrixException('Not a square matrix (required for determinant)');
        }

        $m = $this->m;
        $R = MatrixFactory::create($this->A);

        /*
         * 1x1 matrix
         *  A = [a]
         *
         * |A| = a
         */
        if ($m === 1) {
            $det = $R[0][0];
        } else {
            // Calculate the cofactors of the top row of the matrix
            $row_of_cofactors = [];
            for ($i = 0; $i < $m; $i++) {
                $row_of_cofactors[$i] = $R->cofactor(0, $i);
            }

            // Since we don't know what the data type is, we can't initialze $det
            // to zero without a special initialize() or zero() method.
            $initialize = true;
            $det = $R[0][0]->multiply($row_of_cofactors[0]);
            foreach ($row_of_cofactors as $key => $value) {
                if ($initialize) {
                    // We skip the first element since it was used to initialize.
                    $initialize = false;
                } else {
                    // $det += element * cofactor
                    $det = $det->add($R[0][$key]->multiply($value));
                }
            }
        }

        $this->catalog->addDeterminant($det);
        return $det;
    }


    /**
     * {@inheritDoc}
     */
    public function cofactor(int $mᵢ, int $nⱼ)
    {
        /** @var ObjectArithmetic $Mᵢⱼ */
        $Mᵢⱼ    = $this->minor($mᵢ, $nⱼ);
        $⟮−1⟯ⁱ⁺ʲ = (-1) ** ($mᵢ + $nⱼ);

        return $Mᵢⱼ->multiply($⟮−1⟯ⁱ⁺ʲ);
    }
}
