<?php

namespace MathPHP\LinearAlgebra\Reduction;

use MathPHP\Exception;
use MathPHP\Functions\Support;
use MathPHP\LinearAlgebra\NumericMatrix;

/**
 * Matrix reduced to reduced row echelon form (row canonical form) - REF
 * https://en.wikipedia.org/wiki/Row_echelon_form
 *
 * A matrix is in reduced row echelon form if:
 *  - It is in row echelon form.
 *  - The leading entry in each nonzero row is a 1 (called a leading 1).
 *  - Each column containing a leading 1 has zeros everywhere else.
 *
 * Algorithm:
 *   (1) Reduce to REF
 *   (2) Find pivot
 *     (b) If no non-zero pivot in the column, go to the next column of the same row and repeat (2)
 *   (3) Scale pivot row so pivot is 1 by using row division
 *   (4) Eliminate elements above pivot (make 0 using row addition of the pivot row * a scaling factor)
 *       so there are no non-zero elements in the pivot column in rows above the pivot
 *   (5) Repeat from 2 from the next row and column
 */
class ReducedRowEchelonForm extends NumericMatrix
{
    /**
     * ReducedRowEchelonForm constructor
     *
     * @param array $A
     *
     * @throws Exception\BadDataException
     */
    public function __construct(array $A)
    {
        parent::__construct($A);
    }

    /**
     * Reduce a matrix to reduced row echelon form (row canonical form) - RREF
     * Factory method to create ReducedRowEchelonForm from any Matrix.
     *
     * @param NumericMatrix $A
     *
     * @return ReducedRowEchelonForm
     *
     * @throws Exception\BadDataException
     * @throws Exception\BadParameterException
     * @throws Exception\IncorrectTypeException
     * @throws Exception\MatrixException
     */
    public static function reduce(NumericMatrix $A): ReducedRowEchelonForm
    {
        return self::reduceFromRowEchelonForm($A->ref());
    }

    /**
     * Reduce a matrix from REF to reduced row echelon form (row canonical form) - RREF
     * Factory method to create ReducedRowEchelonForm from a RowEchelonForm Matrix.
     *
     * @param RowEchelonForm $A
     *
     * @return ReducedRowEchelonForm
     *
     * @throws Exception\BadDataException
     * @throws Exception\BadParameterException
     * @throws Exception\IncorrectTypeException
     * @throws Exception\MatrixException
     */
    public static function reduceFromRowEchelonForm(RowEchelonForm $A): ReducedRowEchelonForm
    {
        $m = $A->m;
        $n = $A->n;
        $R = $A;
        $ε = $A->getError();

        // Starting conditions
        $row   = 0;
        $col   = 0;
        $rref = false;

        while (!$rref) {
            // No non-zero pivot, go to next column of the same row
            if (Support::isZero($R[$row][$col], $ε)) {
                $col++;
                if ($row >= $m || $col >= $n) {
                    $rref = true;
                }
                continue;
            }

            // Scale pivot to 1
            if ($R[$row][$col] != 1) {
                $divisor = $R[$row][$col];
                $R = $R->rowDivide($row, $divisor);
            }

            // Eliminate elements above pivot
            for ($j = $row - 1; $j >= 0; $j--) {
                $factor = $R[$j][$col];
                if (Support::isNotZero($factor, $ε)) {
                    $R = $R->rowAdd($row, $j, -$factor);
                }
            }

            // Move on to next row and column
            $row++;
            $col++;

            // If no more rows or columns, rref achieved
            if ($row >= $m || $col >= $n) {
                $rref = true;
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

        $rref = new ReducedRowEchelonForm($R);
        $rref->setError($ε);

        return $rref;
    }
}
