<?php

namespace MathPHP\LinearAlgebra\Reduction;

use MathPHP\Exception;
use MathPHP\Functions\Support;
use MathPHP\LinearAlgebra\NumericMatrix;

/**
 * Matrix reduced to row echelon form - REF
 * https://en.wikipedia.org/wiki/Row_echelon_form
 *
 * A matrix is in echelon form if it has the shape resulting from a Gaussian elimination.
 * Specifically, a matrix is in row echelon form if:
 *  - all nonzero rows (rows with at least one nonzero element) are above any rows of all zeroes
 *    (all zero rows, if any, belong at the bottom of the matrix), and
 *  - the leading coefficient (the first nonzero number from the left, also called the pivot)
 *    of a nonzero row is always strictly to the right of the leading coefficient of the row above it
 */
class RowEchelonForm extends NumericMatrix
{
    /** @var int Number of row swaps when computing REF */
    protected $swaps;

    /**
     * RowEchelonForm constructor
     * @param array $A
     * @param int   $swaps Number of row swaps when computing REF
     *
     * @throws Exception\BadDataException
     */
    public function __construct(array $A, int $swaps)
    {
        parent::__construct($A);

        $this->swaps = $swaps;
    }

    /**
     * Get number of row swaps when computing REF
     *
     * @return int
     */
    public function getRowSwaps(): int
    {
        return $this->swaps;
    }

    /**
     * Reduced row echelon form
     *
     * @return ReducedRowEchelonForm
     *
     * @throws Exception\BadDataException
     * @throws Exception\BadParameterException
     * @throws Exception\IncorrectTypeException
     * @throws Exception\MatrixException
     */
    public function rref(): ReducedRowEchelonForm
    {
        return ReducedRowEchelonForm::reduceFromRowEchelonForm($this);
    }

    /**
     * Reduce a matrix to row echelon form
     * Factory method to create a RowEchelonForm matrix
     *
     * First tries Guassian elimination.
     * If that fails (singular matrix), uses custom row reduction algorithm
     *
     * @param NumericMatrix $A
     *
     * @return RowEchelonForm
     *
     * @throws Exception\BadDataException
     * @throws Exception\BadParameterException
     * @throws Exception\IncorrectTypeException
     * @throws Exception\MatrixException
     */
    public static function reduce(NumericMatrix $A): RowEchelonForm
    {
        try {
            [$R, $ref_swaps] = self::gaussianElimination($A);
        } catch (Exception\SingularMatrixException $e) {
            [$R, $ref_swaps] = self::rowReductionToEchelonForm($A);
        }

        $ref = new RowEchelonForm($R, $ref_swaps);
        $ref->setError($A->getError());

        return $ref;
    }

    /**
     * Gaussian elimination - row echelon form
     *
     * Algorithm
     *  for k = 1 ... min(m,n):
     *    Find the k-th pivot:
     *    i_max  := argmax (i = k ... m, abs(A[i, k]))
     *    if A[i_max, k] = 0
     *      error "Matrix is singular!"
     *    swap rows(k, i_max)
     *    Do for all rows below pivot:
     *    for i = k + 1 ... m:
     *      f := A[i, k] / A[k, k]
     *      Do for all remaining elements in current row:
     *      for j = k + 1 ... n:
     *        A[i, j]  := A[i, j] - A[k, j] * f
     *      Fill lower triangular matrix with zeros:
     *      A[i, k]  := 0
     *
     * https://en.wikipedia.org/wiki/Gaussian_elimination
     *
     * @param NumericMatrix $A
     *
     * @return array - matrix in row echelon form and number of row swaps
     *
     * @throws Exception\SingularMatrixException if the matrix is singular
     */
    public static function gaussianElimination(NumericMatrix $A): array
    {
        $m     = $A->getM();
        $n     = $A->getN();
        $size  = \min($m, $n);
        $R     = $A->getMatrix();
        $swaps = 0;
        $ε     = $A->getError();

        for ($k = 0; $k < $size; $k++) {
            // Find column max
            $i_max = $k;
            for ($i = $k; $i < $m; $i++) {
                if (\abs($R[$i][$k]) > \abs($R[$i_max][$k])) {
                    $i_max = $i;
                }
            }

            if (Support::isZero($R[$i_max][$k], $ε)) {
                throw new Exception\SingularMatrixException('Guassian elimination fails for singular matrices');
            }

            // Swap rows k and i_max (column max)
            if ($k != $i_max) {
                [$R[$k], $R[$i_max]] = [$R[$i_max], $R[$k]];
                $swaps++;
            }

            // Row operations
            for ($i = $k + 1; $i < $m; $i++) {
                $f = (Support::isNotZero($R[$k][$k], $ε)) ? $R[$i][$k] / $R[$k][$k] : 1;
                for ($j = $k + 1; $j < $n; $j++) {
                    $R[$i][$j] = $R[$i][$j] - ($R[$k][$j] * $f);
                    if (Support::isZero($R[$i][$j], $ε)) {
                        $R[$i][$j] = 0;
                    }
                }
                $R[$i][$k] = 0;
            }
        }

        return [$R, $swaps];
    }

    /**
     * Reduce a matrix to row echelon form using basic row operations
     * Custom MathPHP algorithm for classic row reduction using basic matrix operations.
     *
     * Algorithm:
     *   (1) Find pivot
     *     (a) If pivot column is 0, look down the column to find a non-zero pivot and swap rows
     *     (b) If no non-zero pivot in the column, go to the next column of the same row and repeat (1)
     *   (2) Scale pivot row so pivot is 1 by using row division
     *   (3) Eliminate elements below pivot (make 0 using row addition of the pivot row * a scaling factor)
     *       so there are no non-zero elements in the pivot column in rows below the pivot
     *   (4) Repeat from 1 from the next row and column
     *
     *   (Extra) Keep track of number of row swaps (used for computing determinant)
     *
     * @param NumericMatrix $A
     *
     * @return array - matrix in row echelon form and number of row swaps
     *
     * @throws Exception\IncorrectTypeException
     * @throws Exception\MatrixException
     * @throws Exception\BadParameterException
     */
    public static function rowReductionToEchelonForm(NumericMatrix $A): array
    {
        $m    = $A->m;
        $n    = $A->n;
        $R    = $A;
        $ε    = $A->getError();

        // Starting conditions
        $row   = 0;
        $col   = 0;
        $swaps = 0;
        $ref   = false;

        while (!$ref) {
            // If pivot is 0, try to find a non-zero pivot in the column and swap rows
            if (Support::isZero($R[$row][$col], $ε)) {
                for ($j = $row + 1; $j < $m; $j++) {
                    if (Support::isNotZero($R[$j][$col], $ε)) {
                        $R = $R->rowInterchange($row, $j);
                        $swaps++;
                        break;
                    }
                }
            }

            // No non-zero pivot, go to next column of the same row
            if (Support::isZero($R[$row][$col], $ε)) {
                $col++;
                if ($row >= $m || $col >= $n) {
                    $ref = true;
                }
                continue;
            }

            // Scale pivot to 1
            $divisor = $R[$row][$col];
            $R = $R->rowDivide($row, $divisor);

            // Eliminate elements below pivot
            for ($j = $row + 1; $j < $m; $j++) {
                $factor = $R[$j][$col];
                if (Support::isNotZero($factor, $ε)) {
                    $R = $R->rowAdd($row, $j, -$factor);
                    for ($k = 0; $k < $n; $k++) {
                        if (Support::isZero($R[$j][$k], $ε)) {
                            $R->A[$j][$k] = 0;
                        }
                    }
                }
            }

            // Move on to next row and column
            $row++;
            $col++;

            // If no more rows or columns, ref achieved
            if ($row >= $m || $col >= $n) {
                $ref = true;
            }
        }

        $R = $R->getMatrix();

        // Floating point adjustment for zero values
        for ($i = 0; $i < $m; $i++) {
            for ($j = 0; $j < $n; $j++) {
                if (Support::isZero($R[$i][$j], $ε)) {
                    $R[$i][$j] = 0;
                }
            }
        }

        return [$R, $swaps];
    }
}
