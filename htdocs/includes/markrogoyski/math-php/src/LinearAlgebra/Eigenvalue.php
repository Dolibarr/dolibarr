<?php

namespace MathPHP\LinearAlgebra;

use MathPHP\Exception;
use MathPHP\Expression\Polynomial;
use MathPHP\Functions\Support;

class Eigenvalue
{
    public const CLOSED_FORM_POLYNOMIAL_ROOT_METHOD = 'closedFormPolynomialRootMethod';
    public const POWER_ITERATION                    = 'powerIteration';
    public const JACOBI_METHOD                      = 'jacobiMethod';

    private const METHODS = [
        self::CLOSED_FORM_POLYNOMIAL_ROOT_METHOD,
        self::POWER_ITERATION,
        self::JACOBI_METHOD,
    ];

    /**
     * Is the provided algorithm a valid eigenvalue method?
     *
     * @param  string  $method
     *
     * @return boolean true if a valid method; false otherwise
     */
    public static function isAvailableMethod(string $method): bool
    {
        return \in_array($method, self::METHODS);
    }

    /**
     * Verify that the matrix can have eigenvalues
     *
     * @param NumericMatrix $A
     *
     * @throws Exception\BadDataException if the matrix is not square
     */
    private static function checkMatrix(NumericMatrix $A)
    {
        if (!$A->isSquare()) {
            throw new Exception\BadDataException('Matrix must be square');
        }
    }

    /**
     * Produces the Eigenvalues for square 2x2 - 4x4 matricies
     *
     * Given a matrix
     *      [a b]
     * A =  [c d]
     *
     * Find all λ such that:
     *      |A-Iλ| = 0
     *
     * This is accomplished by finding the roots of the polyniomial that
     * is produced when computing the determinant of the matrix. The determinant
     * polynomial is calculated using polynomial arithmetic.
     *
     * @param NumericMatrix $A
     *
     * @return float[] of eigenvalues
     *
     * @throws Exception\BadDataException if the matrix is not square
     * @throws Exception\BadDataException if the matrix is not 2x2, 3x3, or 4x4
     * @throws Exception\MathException
     */
    public static function closedFormPolynomialRootMethod(NumericMatrix $A): array
    {
        self::checkMatrix($A);

        $m = $A->getM();
        if ($m < 2 || $m > 4) {
            throw new Exception\BadDataException("Matrix must be 2x2, 3x3, or 4x4. $m x $m given");
        }

        // Convert the numerical matrix into an ObjectMatrix
        $B_array = [];
        for ($i = 0; $i < $m; $i++) {
            for ($j = 0; $j < $m; $j++) {
                $B_array[$i][$j] = new Polynomial([$A[$i][$j]], 'λ');
            }
        }
        $B = MatrixFactory::create($B_array);

        // Create a diagonal Matrix of lambda (Iλ)
        $λ_poly    = new Polynomial([1, 0], 'λ');
        $zero_poly = new Polynomial([0], 'λ');
        $λ_array   = [];
        for ($i = 0; $i < $m; $i++) {
            for ($j = 0; $j < $m; $j++) {
                $λ_array[$i][$j] = ($i == $j)
                    ? $λ_poly
                    : $zero_poly;
            }
        }

        /** @var ObjectSquareMatrix $λ */
        $λ = MatrixFactory::create($λ_array);

        /** @var ObjectSquareMatrix $⟮B − λ⟯ Subtract Iλ from B */
        $⟮B − λ⟯ = $B->subtract($λ);

        /** @var Polynomial $det The Eigenvalues are the roots of the determinant of this matrix */
        $det = $⟮B − λ⟯->det();

        // Calculate the roots of the determinant.
        $eigenvalues = $det->roots();
        \usort($eigenvalues, function ($a, $b) {
            return \abs($b) <=> \abs($a);
        });
        return $eigenvalues;
    }

    /**
     * Find eigenvalues by the Jacobi method
     *
     * https://en.wikipedia.org/wiki/Jacobi_eigenvalue_algorithm
     *
     * @param NumericMatrix $A
     *
     * @return float[] of eigenvalues
     *
     * @throws Exception\BadDataException if the matrix is not symmetric
     * @throws Exception\BadDataException if the matrix is 1x1
     * @throws Exception\MathException
     */
    public static function jacobiMethod(NumericMatrix $A): array
    {
        if (!$A->isSymmetric()) {
            throw new Exception\BadDataException('Matrix must be symmetric');
        }

        $m = $A->getM();
        if ($m < 2) {
            throw new Exception\BadDataException("Matrix must be 2x2 or larger");
        }

        $D = $A;
        $S = MatrixFactory::identity($m);

        $iterations = 0; // For infinitely oscillating edge cases between very small positive and negative numbers that don't converge
        while (!$D->isDiagonal()) {
            // Find the largest off-diagonal element in $D
            $pivot = ['value' => 0, 'i' => 0, 'j' => 0];
            for ($i = 0; $i < $m - 1; $i++) {
                for ($j = $i + 1; $j < $m; $j++) {
                    if (\abs($D[$i][$j]) > \abs($pivot['value'])) {
                        $pivot['value'] = $D[$i][$j];
                        $pivot['i']     = $i;
                        $pivot['j']     = $j;
                    }
                }
            }

            $i     = $pivot['i'];
            $j     = $pivot['j'];
            $angle = ($D[$i][$i] == $D[$j][$j])
                ? ($D[$i][$i] > 0 ? 1 : -1) * \M_PI / 4
                : \atan(2 * $D[$i][$j] / ($D[$i][$i] - $D[$j][$j])) / 2;

            $G = MatrixFactory::givens($i, $j, $angle, $m);
            $D = $G->transpose()->multiply($D)->multiply($G);
            $S = $S->multiply($G);

            // To prevent infinite looping when zero-like oscillations don't converge
            $iterations++;
            if ($iterations > 200) {
                break;
            }
        }

        $eigenvalues = $D->getDiagonalElements();
        \usort($eigenvalues, function ($a, $b) {
            return \abs($b) <=> \abs($a);
        });
        return $eigenvalues;
    }

    /**
     * Power Iteration
     *
     * The recurrence relation:
     *         Abₖ
     * bₖ₊₁ = ------
     *        ‖Abₖ‖
     *
     * will converge to the dominant eigenvector,
     *
     * The corresponding eigenvalue is calculated as:
     *
     *      bₖᐪAbₖ
     * μₖ = -------
     *       bₖᐪbₖ
     *
     * https://en.wikipedia.org/wiki/Power_iteration
     *
     * @param NumericMatrix $A
     * @param int           $iterations max number of iterations to perform
     *
     * @return float[] most extreme eigenvalue
     *
     * @throws Exception\BadDataException if the matrix is not square
     * @throws Exception\MathException
     */
    public static function powerIteration(NumericMatrix $A, int $iterations = 1000): array
    {
        self::checkMatrix($A);

        $initial_iter = $iterations;
        do {
            $b = MatrixFactory::random($A->getM(), 1);
        } while ($b->frobeniusNorm() == 0);
        $b = $b->scalarDivide($b->frobeniusNorm());  // Scale to a unit vector

        $newμ      = 0;
        $μ         = -1;
        $max_rerun = 2;
        $rerun     = 0;
        $max_ev    = 0;

        while ($rerun < $max_rerun) {
            while (!Support::isEqual($μ, $newμ)) {
                if ($iterations <= 0) {
                    throw new Exception\FunctionFailedToConvergeException("Maximum number of iterations exceeded.");
                }

                $μ  = $newμ;
                $Ab = $A->multiply($b);
                while ($Ab->frobeniusNorm() == 0) {
                    $Ab = MatrixFactory::random($A->getM(), 1);
                }

                $b    = $Ab->scalarDivide($Ab->frobeniusNorm());
                $newμ = $b->transpose()->multiply($A)->multiply($b)->get(0, 0);
                $iterations--;
            }

            $max_ev = \abs($max_ev) > \abs($newμ) ? $max_ev : $newμ;

            // Perturb the eigenvector and run again to make sure the same solution is found
            $newb = $b->getMatrix();
            for ($i = 0; $i < \count($newb); $i++) {
                $newb[$i][0] = $newb[1][0] + \rand() / 10;
            }
            $b    = MatrixFactory::create($newb);
            $b    = $b->scalarDivide($b->frobeniusNorm());  // Scale to a unit vector
            $newμ = 0;
            $μ    = -1;

            $rerun++;
            $iterations = $initial_iter;
        }

        return [$max_ev];
    }
}
