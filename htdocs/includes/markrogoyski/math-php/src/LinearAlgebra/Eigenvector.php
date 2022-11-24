<?php

namespace MathPHP\LinearAlgebra;

use MathPHP\Exception;
use MathPHP\Functions\Map\Single;
use MathPHP\Functions\Special;

class Eigenvector
{
    /**
     * Calculate the Eigenvectors for a matrix
     *
     * Eigenvectors are vectors whos direction is unchaged after
     * the application of a transformation matrix.
     *
     * The results from this function are column unit vectors with the first
     * element being positive.
     *
     * If a eigenvalue appears multiple times, the eigenvectors in this space
     * will be orthoganal.
     *
     * @param NumericMatrix $A           a square matrix.
     * @param float[]       $eigenvalues an array of eigenvalues for this matrix
     *
     * @return NumericMatrix of eigenvectors
     *
     * @throws Exception\BadDataException if the matrix is not square; improper number of eigenvalues;
     *                                    eigenvalue is not a number; eigenvalue is not an eigenvalue of the matrix
     */
    public static function eigenvectors(NumericMatrix $A, array $eigenvalues = []): NumericMatrix
    {
        if (empty($eigenvalues)) {
            $eigenvalues = Eigenvalue::closedFormPolynomialRootMethod($A);
        }
        if (!$A->isSquare()) {
            throw new Exception\BadDataException('Matrix must be square');
        }
        // Scale the whole matrix by the max absolute value
        // to ensure computability.
        $max_abs = 0;
        $matrix = $A->getMatrix();
        for ($i = 0; $i < $A->getM(); $i++) {
            for ($j = 0; $j < $A->getN(); $j++) {
                $max_abs = $matrix[$i][$j] > $max_abs ? $matrix[$i][$j] : $max_abs;
            }
        }
        // Prevent divide by zero errors
        $max_abs = $max_abs === 0 ? 1 : $max_abs;
        $A = $A->scalarDivide($max_abs);
        $eig = new Vector($eigenvalues);
        $eigenvalues = $eig->scalarDivide($max_abs)->getVector();
        $number = \count($eigenvalues);

        // There cannot be more eigenvalues than the size of A, nor can there be zero.
        if ($number > $A->getM()) {
            throw new Exception\BadDataException('Improper number of eigenvalues provided');
        }
        $M = [];

        // We will store all our solutions here first because, in the case where there are duplicate
        // eigenvalues, we will find all the solutions for that value at once. At the end we will
        // pull them out in the same order as the eigenvalues array.
        $solution_array = [];
        foreach ($eigenvalues as $eigenvalue) {
            // If this is a duplicate eigenvalue, and this is the second instance, the first
            // pass already found all the vectors.
            $key = \array_search($eigenvalue, \array_column($solution_array, 'eigenvalue'));
            if (!$key) {
                $Iλ = MatrixFactory::identity($number)->scalarMultiply($eigenvalue);
                $T = $A->subtract($Iλ);

                $rref = $T->rref();

                $number_of_solutions = self::countSolutions($rref);
                if ($number_of_solutions === 0) {
                    throw new Exception\BadDataException($eigenvalue . ' is not an eigenvalue of this matrix');
                }
                if ($number_of_solutions == $number) {
                    return MatrixFactory::identity($number);
                }

                // Remove the zero rows from $rref
                for ($i = 0; $i < $number_of_solutions; $i++) {
                    if ($rref->getM() > 1) {
                        $rref = $rref->rowExclude($rref->getM() - 1);
                    }
                }

                $zero_columns = self::findZeroColumns($rref);

                 // A column of all zeroes means that a vector in that direction is a solution.
                foreach ($zero_columns as $column) {
                    $solution          = \array_fill(0, $number, 0);
                    $solution[$column] = 1;
                    $solution_array[]  = ['eigenvalue' => $eigenvalue, 'vector' => $solution];
                    // Add the solution to rref.
                    $rref = $rref->augmentBelow(MatrixFactory::create([$solution]))->rref();
                    $number_of_solutions--;
                }

                $vectors_found = 0;
                // Any remaining vectors must be found by solving an underdefined set of linear equations.
                while ($number_of_solutions > $vectors_found) {
                    // We will force the value of one or more of the variables
                    // to be one, and solve for the remaining variables.
                    $number_to_force  = $number_of_solutions - $vectors_found;
                    $forced_variables = [];
                    $n                = $rref->getN();
                    // The solution vector is a column vector.
                    $solution = new Vector(\array_fill(0, $n - $number_to_force, 0));
                    $matrix   = $rref;
                    for ($i = 0; $i < $n && \count($forced_variables) < $number_to_force; $i++) {
                        // Make sure that removing column $i does not leave behind a row of zeros
                        $column_can_be_used = true;
                        for ($j = 0; $j <= $i && $j < $rref->getM() && $column_can_be_used; $j++) {
                            if ($matrix->columnExclude($i - \count($forced_variables))->getRow($j) == \array_fill(0, $matrix->getN() - 1, 0)) {
                                $column_can_be_used = false;
                            }
                        }
                        if ($column_can_be_used) {
                            $matrix             = $matrix->columnExclude($i - \count($forced_variables));
                            $forced_variables[] = $i;
                            $new_column         = new Vector($rref->getColumn($i));
                            $solution           = $solution->subtract($new_column);
                        }
                    }

                    $eigenvector = $matrix->solve($solution)->getVector();

                    // Set all the forced variables to 1.
                    foreach ($forced_variables as $column) {
                        \array_splice($eigenvector, $column, 0, 1);
                    }

                    $eigenvector_scaled = $eigenvector;

                    // Scale it to be a unit vector.
                    $sign               = (Special::sgn($eigenvector_scaled[0]) == 1) ? 1 : -1;
                    $scale_factor       = $sign / \sqrt(\array_sum(Single::square($eigenvector_scaled)));
                    $eigenvector_scaled = Single::multiply($eigenvector_scaled, $scale_factor);
                    $solution_array[]   = ['eigenvalue' => $eigenvalue, 'vector' => $eigenvector_scaled];
                    $vectors_found++;

                    // If there are more solutions to be found, we will append this solution to the bottom
                    // of $rref. Doing this will set the constraint that the dot product between the next
                    // solution and this solution be zero, or that they are orthoganol.
                    if ($vectors_found < $number_of_solutions) {
                        $rref = $rref->augmentBelow(MatrixFactory::create([$eigenvector]))->rref();
                    }
                }
                $key = \array_search($eigenvalue, \array_column($solution_array, 'eigenvalue'));
            }
            $M[] = $solution_array[$key]['vector'];
            unset($solution_array[$key]);
            // Reset the array keys.
            $solution_array = \array_values($solution_array);
        }
        $matrix = MatrixFactory::create($M);
        return $matrix->transpose();
    }

    /**
     * Count the number of rows that contain all zeroes, starting at the bottom.
     * In reduced row echelon form, all the rows of zero will be on the bottom.
     *
     * @param NumericMatrix $M
     *
     * @return int
     */
    private static function countSolutions(NumericMatrix $M): int
    {
        $number_of_solutions = 0;
        // There are solutions to be found.
        $more_solutions = true;
        $m = $M->getM();
        // We will count the number of rows with all zeros, starting at the bottom.
        for ($i = $m - 1; $i >= 0 && $more_solutions; $i--) {
            // Every row of zeros is a degree of freedom (a solution) with that eigenvalue
            if ($M->getRow($i) == \array_fill(0, $m, 0)) {
                $number_of_solutions++;
            } else {
                 // Once we find a row with nonzero values, there are no more.
                 $more_solutions = false;
            }
        }
        return $number_of_solutions;
    }

    /**
     * Find the zero columns
     *
     * @param  NumericMatrix $M
     *
     * @return array
     */
    private static function findZeroColumns(NumericMatrix $M): array
    {
        $m = $M->getM();
        $zero_columns = [];
        for ($i = 0; $i < $M->getN(); $i++) {
            if ($M->getColumn($i) == \array_fill(0, $m, 0)) {
                $zero_columns[] = $i;
            }
        }
        return $zero_columns;
    }
}
