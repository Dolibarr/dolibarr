<?php

namespace MathPHP\LinearAlgebra;

class MatrixCatalog
{
    /** @var NumericMatrix transpose */
    private $Aᵀ;

    /** @var NumericMatrix inverse */
    private $A⁻¹;

    /** @var Reduction\RowEchelonForm */
    private $REF;

    /** @var Reduction\ReducedRowEchelonForm */
    private $RREF;

    /** @var Decomposition\LU */
    private $LU;

    /** @var Decomposition\QR */
    private $QR;

    /** @var Decomposition\Cholesky */
    private $cholesky;

    /** @var Decomposition\Crout */
    private $crout;

    /** @var Decomposition\SVD */
    private $SVD;

    /** @var float determinant */
    private $det;

    /**************************************************************************
     * DERIVED MATRICES
     *  - transpose
     *  - inverse
     **************************************************************************/

    // TRANSPOSE

    /**
     * @param Matrix $Aᵀ
     */
    public function addTranspose(Matrix $Aᵀ): void
    {
        $this->Aᵀ = $Aᵀ;
    }

    /**
     * @return bool
     */
    public function hasTranspose(): bool
    {
        return isset($this->Aᵀ);
    }

    /**
     * @return Matrix
     */
    public function getTranspose(): Matrix
    {
        return $this->Aᵀ;
    }

    // INVERSE

    /**
     * @param Matrix $A⁻¹
     */
    public function addInverse(Matrix $A⁻¹): void
    {
        $this->A⁻¹ = $A⁻¹;
    }

    /**
     * @return bool
     */
    public function hasInverse(): bool
    {
        return isset($this->A⁻¹);
    }

    /**
     * @return Matrix
     */
    public function getInverse(): Matrix
    {
        return $this->A⁻¹;
    }

    /**************************************************************************
     * MATRIX REDUCTIONS
     *  - ref (row echelon form)
     *  - rref (reduced row echelon form)
     **************************************************************************/

    // ROW ECHELON FORM

    /**
     * @param Reduction\RowEchelonForm $REF
     */
    public function addRowEchelonForm(Reduction\RowEchelonForm $REF): void
    {
        $this->REF = $REF;
    }

    /**
     * @return bool
     */
    public function hasRowEchelonForm(): bool
    {
        return isset($this->REF);
    }

    /**
     * @return Reduction\RowEchelonForm
     */
    public function getRowEchelonForm(): Reduction\RowEchelonForm
    {
        return $this->REF;
    }

    // REDUCED ROW ECHELON FORM

    /**
     * @param Reduction\ReducedRowEchelonForm $RREF
     */
    public function addReducedRowEchelonForm(Reduction\ReducedRowEchelonForm $RREF): void
    {
        $this->RREF = $RREF;
    }

    /**
     * @return bool
     */
    public function hasReducedRowEchelonForm(): bool
    {
        return isset($this->RREF);
    }

    /**
     * @return Reduction\ReducedRowEchelonForm
     */
    public function getReducedRowEchelonForm(): Reduction\ReducedRowEchelonForm
    {
        return $this->RREF;
    }

    /**************************************************************************
     * MATRIX DECOMPOSITIONS
     *  - LU decomposition
     *  - QR decomposition
     *  - Cholesky decomposition
     *  - Crout decomposition
     *  - SVD
     **************************************************************************/


    // LU DECOMPOSITION

    /**
     * @param Decomposition\LU $LU
     */
    public function addLuDecomposition(Decomposition\LU $LU): void
    {
        $this->LU = $LU;
    }

    /**
     * @return bool
     */
    public function hasLuDecomposition(): bool
    {
        return isset($this->LU);
    }

    /**
     * @return Decomposition\LU
     */
    public function getLuDecomposition(): Decomposition\LU
    {
        return $this->LU;
    }

    // QR DECOMPOSITION

    /**
     * @param Decomposition\QR $QR
     */
    public function addQrDecomposition(Decomposition\QR $QR): void
    {
        $this->QR = $QR;
    }

    /**
     * @return bool
     */
    public function hasQrDecomposition(): bool
    {
        return isset($this->QR);
    }

    /**
     * @return Decomposition\QR
     */
    public function getQrDecomposition(): Decomposition\QR
    {
        return $this->QR;
    }

    // CHOLESKY DECOMPOSITION

    /**
     * @param Decomposition\Cholesky $cholesky
     */
    public function addCholeskyDecomposition(Decomposition\Cholesky $cholesky): void
    {
        $this->cholesky = $cholesky;
    }

    /**
     * @return bool
     */
    public function hasCholeskyDecomposition(): bool
    {
        return isset($this->cholesky);
    }

    /**
     * @return Decomposition\Cholesky
     */
    public function getCholeskyDecomposition(): Decomposition\Cholesky
    {
        return $this->cholesky;
    }

    // CROUT DECOMPOSITION

    /**
     * @param Decomposition\Crout $crout
     */
    public function addCroutDecomposition(Decomposition\Crout $crout): void
    {
        $this->crout = $crout;
    }

    /**
     * @return bool
     */
    public function hasCroutDecomposition(): bool
    {
        return isset($this->crout);
    }

    /**
     * @return Decomposition\Crout
     */
    public function getCroutDecomposition(): Decomposition\Crout
    {
        return $this->crout;
    }

    // SVD

    /**
     * @param Decomposition\SVD $SVD
     */
    public function addSVD(Decomposition\SVD $SVD)
    {
        $this->SVD = $SVD;
    }

    /**
     * @return bool
     */
    public function hasSVD()
    {
        return isset($this->SVD);
    }

    /**
     * @return Decomposition\SVD
     */
    public function getSVD(): Decomposition\SVD
    {
        return $this->SVD;
    }
    /**************************************************************************
     * DERIVED DATA
     *  - determinant
     **************************************************************************/

    // DETERMINANT

    /**
     * @param number $det
     */
    public function addDeterminant($det): void
    {
        $this->det = $det;
    }

    /**
     * @return bool
     */
    public function hasDeterminant(): bool
    {
        return isset($this->det);
    }

    /**
     * @return number
     */
    public function getDeterminant()
    {
        return $this->det;
    }
}
