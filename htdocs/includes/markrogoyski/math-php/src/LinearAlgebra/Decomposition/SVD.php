<?php

namespace MathPHP\LinearAlgebra\Decomposition;

use MathPHP\Exception;
use MathPHP\LinearAlgebra\NumericMatrix;
use MathPHP\LinearAlgebra\MatrixFactory;
use MathPHP\LinearAlgebra\Vector;

/**
 * Singular value decomposition
 *
 * The generalization of the eigendecomposition of a square matrix to an m x n matrix
 * https://en.wikipedia.org/wiki/Singular_value_decomposition
 *
 * @property-read NumericMatrix  $S m x n diagonal matrix
 * @property-read NumericMatrix  $V n x n orthogonal matrix
 * @property-read NumericMatrix  $U m x m orthogonal matrix
 * @property-read Vector<number> $D diagonal elements from S
 */
class SVD extends Decomposition
{
    /** @var NumericMatrix m x m orthogonal matrix  */
    private $U;

    /** @var NumericMatrix n x n orthogonal matrix  */
    private $V;

    /** @var NumericMatrix m x n diagonal matrix containing the singular values  */
    private $S;

    /** @var Vector<number> diagonal elements from S that are the singular values  */
    private $D;

    /**
     * @param NumericMatrix $U Orthogonal matrix
     * @param NumericMatrix $S Rectangular Diagonal matrix
     * @param NumericMatrix $V Orthogonal matrix
     */
    private function __construct(NumericMatrix $U, NumericMatrix $S, NumericMatrix $V)
    {
        $this->U = $U;
        $this->S = $S;
        $this->V = $V;
        $this->D = new Vector($S->getDiagonalElements());
    }

    /**
     * Get U
     *
     * @return NumericMatrix
     */
    public function getU(): NumericMatrix
    {
        return $this->U;
    }

    /**
     * Get S
     *
     * @return NumericMatrix
     */
    public function getS(): NumericMatrix
    {
        return $this->S;
    }

    /**
     * Get V
     *
     * @return NumericMatrix
     */
    public function getV(): NumericMatrix
    {
        return $this->V;
    }

    /**
     * Get D
     *
     * @return Vector<number>
     */
    public function getD(): Vector
    {
        return $this->D;
    }

    /**
     * Generate the Singlue Value Decomposition of the matrix
     *
     * @param NumericMatrix $M
     *
     * @return SVD
     */
    public static function decompose(NumericMatrix $M): SVD
    {
        $Mᵀ  = $M->transpose();
        $MMᵀ = $M->multiply($Mᵀ);
        $MᵀM = $Mᵀ->multiply($M);

        // m x m orthoganol matrix
        $U = $MMᵀ->eigenvectors();

        // n x n orthoganol matrix
        $V = $MᵀM->eigenvectors();

        // A rectangular diagonal matrix
        $S = $U->transpose()->multiply($M)->multiply($V);

        $diag = $S->getDiagonalElements();

        // If there is a negative singular value, we need to adjust the signs of columns in U
        if (min($diag) < 0) {
            $sig = MatrixFactory::identity($U->getN())->getMatrix();
            foreach ($diag as $key => $value) {
                $sig[$key][$key] = $value >= 0 ? 1 : -1;
            }
            $signature = MatrixFactory::createNumeric($sig);
            $U = $U->multiply($signature);
            $S = $signature->multiply($S);
        }

        return new SVD($U, $S, $V);
    }

    /**
     * Get U, S, or V matrix, or D vector
     *
     * @param string $name
     *
     * @return NumericMatrix|Vector<number>
     */
    public function __get(string $name)
    {
        switch ($name) {
            case 'U':
            case 'S':
            case 'V':
            case 'D':
                return $this->$name;
            default:
                throw new Exception\MatrixException("SVD class does not have a gettable property: $name");
        }
    }
}
