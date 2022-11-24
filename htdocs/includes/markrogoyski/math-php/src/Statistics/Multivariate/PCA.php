<?php

namespace MathPHP\Statistics\Multivariate;

use MathPHP\Exception;
use MathPHP\Functions\Map\Single;
use MathPHP\LinearAlgebra\Eigenvalue;
use MathPHP\LinearAlgebra\NumericMatrix;
use MathPHP\LinearAlgebra\MatrixFactory;
use MathPHP\LinearAlgebra\Vector;
use MathPHP\Probability\Distribution\Continuous\F;
use MathPHP\Probability\Distribution\Continuous\StandardNormal;
use MathPHP\Statistics\Descriptive;

/**
 * Principal component analysis
 *
 * PCA uses the correlation between data vectors to find a transformation that minimizes variability.
 *
 * https://en.wikipedia.org/wiki/Principal_component_analysis
 */
class PCA
{
    /** @var NumericMatrix Dataset */
    private $data;

    /** @var Vector Means */
    private $center;

    /** @var Vector Scale */
    private $scale;

    /** @var Vector $EVal Eigenvalues of the correlation Matrix - Also the Loading Matrix for the PCA */
    private $EVal = null;

    /** @var NumericMatrix $EVec Eigenvectors of the correlation matrix */
    private $EVec = null;

    /**
     * Constructor
     *
     * @param NumericMatrix $M      each row is a sample, each column is a variable
     * @param bool          $center - Sets if the columns are to be centered to μ = 0
     * @param bool          $scale  - Sets if the columns are to be scaled to σ  = 1
     *
     * @throws Exception\BadDataException if any rows have a different column count
     * @throws Exception\MathException
     */
    public function __construct(NumericMatrix $M, bool $center = true, bool $scale = true)
    {
        // Check that there is enough data: at least two columns and rows
        if (!($M->getM() > 1) || !($M->getN() > 1)) {
            throw new Exception\BadDataException('Data matrix must be at least 2x2.');
        }

        $this->center = $center === true
            ? $this->center = $M->columnMeans()
            : $this->center = new Vector(\array_fill(0, $M->getN(), 0));

        if ($scale === true) {
            $scaleArray = [];
            for ($i = 0; $i < $M->getN(); $i++) {
                $scaleArray[] = Descriptive::standardDeviation($M->getColumn($i));
            }
            $this->scale = new Vector($scaleArray);
        } else {
            $this->scale = new Vector(\array_fill(0, $M->getN(), 1));
        }

        // Save the source data to the class
        $this->data = $M;

        // Center and scale the data as needed
        $this->data = $this->standardizeData();

        // Create the correlation / variance-covarience Matrix
        $samples       = $M->getM();
        $corrCovMatrix = $this->data->transpose()->multiply($this->data)->scalarDivide($samples - 1);

        // Eigenvalues and vectors
        $this->EVal = new Vector($corrCovMatrix->eigenvalues(Eigenvalue::JACOBI_METHOD));
        $this->EVec = $corrCovMatrix->eigenvectors(Eigenvalue::JACOBI_METHOD);
    }

    /**
     * Verify that the matrix has the same number of columns as the original data
     *
     * @param NumericMatrix $newData
     *
     * @throws Exception\BadDataException if the matrix is not square
     */
    private function checkNewData(NumericMatrix $newData): void
    {
        if ($newData->getN() !== $this->data->getN()) {
            throw new Exception\BadDataException('Data does not have the same number of columns');
        }
    }

    /**
     * Standardize the data
     * Use the object $center and $scale Vectors to transform the provided data
     *
     * @param NumericMatrix $new_data - An optional Matrix of new data which is standardized against the original data
     *
     * @return NumericMatrix
     *
     * @throws Exception\MathException
     */
    public function standardizeData(NumericMatrix $new_data = null): NumericMatrix
    {
        if ($new_data === null) {
            $X = $this->data;
        } else {
            $this->checkNewData($new_data);
            $X = $new_data;
        }
        $ones_column = MatrixFactory::one($X->getM(), 1);

        // Create a matrix the same dimensions as $new_data, each element is the average of that column in the original data.
        $center_matrix = $ones_column->multiply(MatrixFactory::create([$this->center->getVector()]));
        $scale_matrix  = MatrixFactory::diagonal($this->scale->getVector())->inverse();

        // scaled data: ($X - μ) / σ
        return $X->subtract($center_matrix)->multiply($scale_matrix);
    }

    /**
     * The loadings are the unit eigenvectors of the correlation matrix
     *
     * @return NumericMatrix
     */
    public function getLoadings(): NumericMatrix
    {
        return $this->EVec;
    }

    /**
     * The eigenvalues of the correlation matrix
     *
     * @return Vector
     *
     * @throws Exception\MathException
     */
    public function getEigenvalues(): Vector
    {
        $EV = [];
        for ($i = 0; $i < $this->data->getN(); $i++) {
            $EV[] = Descriptive::standardDeviation($this->getScores()->getColumn($i)) ** 2;
        }

        return new Vector($EV);
    }

    /**
     * Get Scores
     *
     * Transform the standardized data with the loadings matrix
     *
     * @param NumericMatrix|null $new_data
     *
     * @return NumericMatrix
     *
     * @throws Exception\MathException
     */
    public function getScores(NumericMatrix $new_data = null): NumericMatrix
    {
        if ($new_data === null) {
            $scaled_data = $this->data;
        } else {
            $this->checkNewData($new_data);
            $scaled_data = $this->standardizeData($new_data);
        }

        return $scaled_data->multiply($this->EVec);
    }

    /**
     * Get R² Values
     *
     * R² for each component is eigenvalue divided by the sum of all eigenvalues
     *
     * @return float[]
     */
    public function getR2(): array
    {
        $total_variance = $this->EVal->sum();
        return $this->EVal->scalarDivide($total_variance)->getVector();
    }

    /**
     * Get the cumulative R²
     *
     * @return float[]
     */
    public function getCumR2(): array
    {
        $result = [];
        $sum    = 0;

        foreach ($this->getR2() as $R²value) {
            $sum += $R²value;
            $result[] = $sum;
        }

        return $result;
    }

    /**
     * Get the Q Residuals
     *
     * The Q residual is the error in the model at a given model complexity.
     * For each row (i) in the data Matrix x, and retained components (j):
     * Qᵢ = eᵢ'eᵢ = xᵢ(I-PⱼPⱼ')xᵢ'
     *
     * @param NumericMatrix $new_data - An optional Matrix of new data which is standardized against the original data
     *
     * @return NumericMatrix of Q residuals
     *
     * @throws Exception\MathException
     */
    public function getQResiduals(NumericMatrix $new_data = null): NumericMatrix
    {
        $vars = $this->data->getN();

        if ($new_data === null) {
            $X = $this->data;
        } else {
            $this->checkNewData($new_data);
            $X = $this->standardizeData($new_data);
        }

        $X′ = $X->transpose();
        $I  = MatrixFactory::identity($vars);

        // Initial element with initialization of result matrix
        $P  = $this->EVec->submatrix(0, 0, $vars - 1, 0);  // Get the first column of the loading matrix
        $P′ = $P->transpose();
        $Q  = MatrixFactory::create([$X->multiply($I->subtract($P->multiply($P′)))->multiply($X′)->getDiagonalElements()])->transpose();

        for ($i = 1; $i < $vars; $i++) {
            // Get the first $i+1 columns of the loading matrix
            $P  = $this->EVec->submatrix(0, 0, $vars - 1, $i);
            $P′ = $P->transpose();
            $Qᵢ = MatrixFactory::create([$X->multiply($I->subtract($P->multiply($P′)))->multiply($X′)->getDiagonalElements()])->transpose();
            $Q  = $Q->augment($Qᵢ);
        }

        return $Q;
    }

    /**
     * Get the T² Distance
     *
     * Get the distance from the score to the center of the model plane.
     * For each row (i) in the data matrix, and retained componenets (j)
     * Tᵢ² = XᵢPⱼΛⱼ⁻¹Pⱼ'Xᵢ'
     *
     * @param NumericMatrix $new_data - An optional Matrix of new data which is standardized against the original data
     *
     * @return NumericMatrix
     *
     * @throws Exception\MathException
     */
    public function getT2Distances(NumericMatrix $new_data = null): NumericMatrix
    {
        $vars = $this->data->getN();

        if ($new_data === null) {
            $X = $this->data;
        } else {
            $this->checkNewData($new_data);
            $X = $this->standardizeData($new_data);
        }

        $X′ = $X->transpose();

        // Initial element with initialization of result matrix
        $P    = $this->EVec->submatrix(0, 0, $vars - 1, 0); // // Get the first column of the loading matrix
        $P′   = $P->transpose();
        $Λⱼ⁻¹ = MatrixFactory::diagonal(\array_slice($this->EVal->getVector(), 0, 0 + 1))->inverse();
        $T²   = MatrixFactory::create([$X->multiply($P)->multiply($Λⱼ⁻¹)->multiply($P′)->multiply($X′)->getDiagonalElements()])->transpose();

        for ($i = 1; $i < $this->data->getN(); $i++) {
            // Get the first $i+1 columns of the loading matrix
            $P    = $this->EVec->submatrix(0, 0, $vars - 1, $i);
            $P′   = $P->transpose();
            $Λⱼ⁻¹ = MatrixFactory::diagonal(\array_slice($this->EVal->getVector(), 0, $i + 1))->inverse();
            $Tᵢ²  = MatrixFactory::create([$X->multiply($P)->multiply($Λⱼ⁻¹)->multiply($P′)->multiply($X′)->getDiagonalElements()])->transpose();
            $T²   = $T²->augment($Tᵢ²);
        }

        return $T²;
    }

    /**
     * Calculate the critical limits of T²
     *
     * @param float $alpha the probability limit of the critical value
     *
     * @return float[] Critical values for each model complexity
     */
    public function getCriticalT2(float $alpha = .05): array
    {
        $samp = $this->data->getM();
        $vars = $this->data->getN();

        $T²Critical = [];
        for ($i = 1; $i <= $vars; $i++) {
            $F = new F($i, $samp - $i);
            $T = $i * ($samp - 1) * $F->inverse(1 - $alpha) / ($samp - $i);
            $T²Critical[] = $T;
        }

        return $T²Critical;
    }

    /**
     * Calculate the critical limits of Q
     *
     * @param float $alpha the probability limit of the critical value
     *
     * @return float[] Critical values for each model complexity
     *
     * @throws Exception\MathException
     */
    public function getCriticalQ(float $alpha = .05): array
    {
        $vars  = $this->data->getN();
        $QCritical = [];

        for ($i = 0; $i < $vars - 1; $i++) {
            $evals = \array_slice($this->getEigenvalues()->getVector(), $i + 1);

            $t1 = \array_sum($evals);
            $t2 = \array_sum(Single::square($evals));
            $t3 = \array_sum(Single::pow($evals, 3));

            $h0 = 1 - 2 * $t1 * $t3 / 3 / $t2 ** 2;
            if ($h0 < .001) {
                $h0 = .001;
            }

            $normal = new StandardNormal();
            $ca     = $normal->inverse(1 - $alpha);

            $h1 = $ca * \sqrt(2 * $t2 * $h0 ** 2) / $t1;
            $h2 = $t2 * $h0 * ($h0 - 1) / $t1 ** 2;

            $QCritical[] = $t1 * (1 + $h1 + $h2) ** (1 / $h0);
        }

        // The final value is always zero since the model is perfectly fit.
        $QCritical[] = 0;

        return $QCritical;
    }
}
