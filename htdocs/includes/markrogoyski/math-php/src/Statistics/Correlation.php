<?php

namespace MathPHP\Statistics;

use MathPHP\Exception;
use MathPHP\Functions\Map;
use MathPHP\LinearAlgebra\Eigenvalue;
use MathPHP\LinearAlgebra\Eigenvector;
use MathPHP\LinearAlgebra\NumericMatrix;
use MathPHP\LinearAlgebra\MatrixFactory;
use MathPHP\Probability\Distribution\Continuous\ChiSquared;
use MathPHP\Probability\Distribution\Continuous\StandardNormal;
use MathPHP\Trigonometry;

/**
 * Statistical correlation
 *  - covariance
 *  - correlation coefficient (r)
 *  - coefficient of determination (R²)
 *  - Kendall's tau (τ)
 *  - Spearman's rho (ρ)
 *  - confidence ellipse
 */
class Correlation
{
    private const X = 0;
    private const Y = 1;

    /**
     * Covariance
     * Convenience method to access population and sample covariance.
     *
     * A measure of how much two random variables change together.
     * Average product of their deviations from their respective means.
     * The population covariance is defined in terms of the sample means x, y
     * https://en.wikipedia.org/wiki/Covariance
     *
     * @param array $X values for random variable X
     * @param array $Y values for random variable Y
     * @param bool $population Optional flag for population or sample covariance
     *
     * @return float
     *
     * @throws Exception\BadDataException
     */
    public static function covariance(array $X, array $Y, bool $population = false): float
    {
        return $population
            ? self::populationCovariance($X, $Y)
            : self::sampleCovariance($X, $Y);
    }

    /**
     * Population Covariance
     * A measure of how much two random variables change together.
     * Average product of their deviations from their respective means.
     * The population covariance is defined in terms of the population means μx, μy
     * https://en.wikipedia.org/wiki/Covariance
     *
     * cov(X, Y) = σxy = E[⟮X - μx⟯⟮Y - μy⟯]
     *
     *                   ∑⟮xᵢ - μₓ⟯⟮yᵢ - μy⟯
     * cov(X, Y) = σxy = -----------------
     *                           N
     *
     * @param array $X values for random variable X
     * @param array $Y values for random variable Y
     *
     * @return float
     *
     * @throws Exception\BadDataException if X and Y do not have the same number of elements
     */
    public static function populationCovariance(array $X, array $Y): float
    {
        if (\count($X) !== \count($Y)) {
            throw new Exception\BadDataException('X and Y must have the same number of elements.');
        }
        $μₓ = Average::mean($X);
        $μy = Average::mean($Y);

        $∑⟮xᵢ − μₓ⟯⟮yᵢ − μy⟯ = \array_sum(\array_map(
            function ($xᵢ, $yᵢ) use ($μₓ, $μy) {
                return ( $xᵢ - $μₓ ) * ( $yᵢ - $μy );
            },
            $X,
            $Y
        ));
        $N = \count($X);

        return $∑⟮xᵢ − μₓ⟯⟮yᵢ − μy⟯ / $N;
    }

    /**
     * Sample covariance
     * A measure of how much two random variables change together.
     * Average product of their deviations from their respective means.
     * The population covariance is defined in terms of the sample means x, y
     * https://en.wikipedia.org/wiki/Covariance
     *
     * cov(X, Y) = Sxy = E[⟮X - x⟯⟮Y - y⟯]
     *
     *                   ∑⟮xᵢ - x⟯⟮yᵢ - y⟯
     * cov(X, Y) = Sxy = ---------------
     *                         n - 1
     *
     * @param array $X values for random variable X
     * @param array $Y values for random variable Y
     *
     * @return float
     *
     * @throws Exception\BadDataException if X and Y do not have the same number of elements
     */
    public static function sampleCovariance(array $X, array $Y): float
    {
        if (\count($X) !== \count($Y)) {
            throw new Exception\BadDataException('X and Y must have the same number of elements.');
        }
        $x = Average::mean($X);
        $y = Average::mean($Y);

        $∑⟮xᵢ − x⟯⟮yᵢ − y⟯ = \array_sum(\array_map(
            function ($xᵢ, $yᵢ) use ($x, $y) {
                return ( $xᵢ - $x ) * ( $yᵢ - $y );
            },
            $X,
            $Y
        ));
        $n = \count($X);

        return $∑⟮xᵢ − x⟯⟮yᵢ − y⟯ / ($n - 1);
    }

    /**
     * Weighted covariance
     * A measure of how much two random variables change together with weights.
     * https://en.wikipedia.org/wiki/Pearson_correlation_coefficient#Weighted_correlation_coefficient
     *
     *                       ∑wᵢ⟮xᵢ - μₓ⟯⟮yᵢ - μy⟯
     * cov(X, Y, w) = sxyw = --------------------
     *                              ∑wᵢ
     *
     * @param array $X values for random variable X
     * @param array $Y values for random variable Y
     * @param array $w values for weights
     *
     * @return float
     *
     * @throws Exception\BadDataException if X and Y do not have the same number of elements
     */
    public static function weightedCovariance(array $X, array $Y, array $w): float
    {
        if (\count($X) !== \count($Y) || \count($X) !== \count($w)) {
            throw new Exception\BadDataException('X, Y and w must have the same number of elements.');
        }

        $μₓ = Average::weightedMean($X, $w);
        $μy = Average::weightedMean($Y, $w);

        $∑wᵢ⟮xᵢ − μₓ⟯⟮yᵢ − μy⟯ = \array_sum(\array_map(
            function ($xᵢ, $yᵢ, $wᵢ) use ($μₓ, $μy) {
                return $wᵢ * ( $xᵢ - $μₓ ) * ( $yᵢ - $μy );
            },
            $X,
            $Y,
            $w
        ));

        $∑wᵢ = \array_sum($w);

        return $∑wᵢ⟮xᵢ − μₓ⟯⟮yᵢ − μy⟯ / $∑wᵢ;
    }

    /**
     * r - correlation coefficient
     * Pearson product-moment correlation coefficient (PPMCC or PCC or Pearson's r)
     *
     * Convenience method for population and sample correlationCoefficient
     *
     * @param array $X values for random variable X
     * @param array $Y values for random variable Y
     * @param bool $population Optional flag for population or sample covariance
     *
     * @return float
     *
     * @throws Exception\BadDataException
     * @throws Exception\OutOfBoundsException
     */
    public static function r(array $X, array $Y, bool $population = false): float
    {
        return $population
            ? self::populationCorrelationCoefficient($X, $Y)
            : self::sampleCorrelationCoefficient($X, $Y);
    }

    /**
     * Population correlation coefficient
     * Pearson product-moment correlation coefficient (PPMCC or PCC or Pearson's r)
     *
     * A normalized measure of the linear correlation between two variables X and Y,
     * giving a value between +1 and −1 inclusive, where 1 is total positive correlation,
     * 0 is no correlation, and −1 is total negative correlation.
     * It is widely used in the sciences as a measure of the degree of linear dependence
     * between two variables.
     * https://en.wikipedia.org/wiki/Pearson_product-moment_correlation_coefficient
     *
     * The correlation coefficient of two variables in a data sample is their covariance
     * divided by the product of their individual standard deviations.
     *
     *        cov(X,Y)
     * ρxy = ----------
     *         σx σy
     *
     *  conv(X,Y) is the population covariance
     *  σx is the population standard deviation of X
     *  σy is the population standard deviation of Y
     *
     * @param array $X values for random variable X
     * @param array $Y values for random variable Y
     *
     * @return float
     *
     * @throws Exception\BadDataException
     * @throws Exception\OutOfBoundsException
     */
    public static function populationCorrelationCoefficient(array $X, array $Y): float
    {
        $cov⟮X，Y⟯ = self::populationCovariance($X, $Y);
        $σx      = Descriptive::standardDeviation($X, true);
        $σy      = Descriptive::standardDeviation($Y, true);

        return $cov⟮X，Y⟯ / ( $σx * $σy );
    }

    /**
     * Sample correlation coefficient
     * Pearson product-moment correlation coefficient (PPMCC or PCC or Pearson's r)
     *
     * A normalized measure of the linear correlation between two variables X and Y,
     * giving a value between +1 and −1 inclusive, where 1 is total positive correlation,
     * 0 is no correlation, and −1 is total negative correlation.
     * It is widely used in the sciences as a measure of the degree of linear dependence
     * between two variables.
     * https://en.wikipedia.org/wiki/Pearson_product-moment_correlation_coefficient
     *
     * The correlation coefficient of two variables in a data sample is their covariance
     * divided by the product of their individual standard deviations.
     *
     *          Sxy
     * rxy = ----------
     *         sx sy
     *
     *  Sxy is the sample covariance
     *  σx is the sample standard deviation of X
     *  σy is the sample standard deviation of Y
     *
     * @param array $X values for random variable X
     * @param array $Y values for random variable Y
     *
     * @return float
     *
     * @throws Exception\BadDataException
     * @throws Exception\OutOfBoundsException
     */
    public static function sampleCorrelationCoefficient(array $X, array $Y): float
    {
        $Sxy = self::sampleCovariance($X, $Y);
        $sx  = Descriptive::standardDeviation($X, Descriptive::SAMPLE);
        $sy  = Descriptive::standardDeviation($Y, Descriptive::SAMPLE);

        return $Sxy / ( $sx * $sy );
    }

    /**
     * R² - coefficient of determination
     * Convenience wrapper for coefficientOfDetermination
     *
     * @param array $X values for random variable X
     * @param array $Y values for random variable Y
     * @param bool $popluation
     *
     * @return float
     *
     * @throws Exception\BadDataException
     * @throws Exception\OutOfBoundsException
     */
    public static function r2(array $X, array $Y, bool $popluation = false): float
    {
        return \pow(self::r($X, $Y, $popluation), 2);
    }

    /**
     * R² - coefficient of determination
     *
     * Indicates the proportion of the variance in the dependent variable
     * that is predictable from the independent variable.
     * Range of 0 - 1. Close to 1 means the regression line is a good fit
     * https://en.wikipedia.org/wiki/Coefficient_of_determination
     *
     * @param array $X values for random variable X
     * @param array $Y values for random variable Y
     * @param bool $popluation
     *
     * @return float
     *
     * @throws Exception\BadDataException
     * @throws Exception\OutOfBoundsException
     */
    public static function coefficientOfDetermination(array $X, array $Y, bool $popluation = false): float
    {
        return \pow(self::r($X, $Y, $popluation), 2);
    }

    /**
     * Weighted correlation coefficient
     * Pearson product-moment correlation coefficient (PPMCC or PCC or Pearson's r) width weighted values
     *
     * A normalized measure of the linear correlation between two variables X and Y,
     * giving a value between +1 and −1 inclusive, where 1 is total positive correlation,
     * 0 is no correlation, and −1 is total negative correlation.
     * It is widely used in the sciences as a measure of the degree of linear dependence between two variables.
     * https://en.wikipedia.org/wiki/Pearson_correlation_coefficient#Weighted_correlation_coefficient
     *
     * The weighted correlation coefficient of two variables in a data sample is their covariance
     * divided by the product of their individual standard deviations.
     *
     *          cov(X,Y,w)
     * ρxyw = -------------
     *          √(sxw syw)
     *
     *  conv(X,Y, w) is the weighted covariance
     *  sxw is the weighted variance of X
     *  syw is the weighted variance of Y
     *
     * @param array $X values for random variable X
     * @param array $Y values for random variable Y
     * @param array $w values for weights
     *
     * @return float
     *
     * @throws Exception\BadDataException
     */
    public static function weightedCorrelationCoefficient(array $X, array $Y, array $w): float
    {
        $cov⟮X，Y，w⟯ = self::weightedCovariance($X, $Y, $w);
        $sxw         = Descriptive::weightedSampleVariance($X, $w, true);
        $syw         = Descriptive::weightedSampleVariance($Y, $w, true);

        return $cov⟮X，Y，w⟯ / \sqrt($sxw * $syw);
    }

    /**
     * τ - Kendall rank correlation coefficient (Kendall's tau)
     *
     * A statistic used to measure the ordinal association between two
     * measured quantities. It is a measure of rank correlation:
     * the similarity of the orderings of the data when ranked by each
     * of the quantities.
     * https://en.wikipedia.org/wiki/Kendall_rank_correlation_coefficient
     * https://onlinecourses.science.psu.edu/stat509/node/158
     *
     * tau-a (no rank ties):
     *
     *        nc - nd
     *   τ = ----------
     *       n(n - 1)/2
     *
     *   Where
     *     nc: number of concordant pairs
     *     nd: number of discordant pairs
     *
     * tau-b (rank ties exist):
     *
     *                 nc - nd
     *   τ = -----------------------------
     *       √(nc + nd + X₀)(nc + nd + Y₀)
     *
     *   Where
     *     X₀: number of pairs tied only on the X variable
     *     Y₀: number of pairs tied only on the Y variable
     *
     * @param array $X values for random variable X
     * @param array $Y values for random variable Y
     *
     * @todo Implement with algorithm faster than O(n²)
     *
     * @return float
     *
     * @throws Exception\BadDataException if both random variables do not have the same number of elements
     */
    public static function kendallsTau(array $X, array $Y): float
    {
        if (\count($X) !== \count($Y)) {
            throw new Exception\BadDataException('Both random variables must have the same number of elements');
        }

        $n = \count($X);

        // Match X and Y pairs and sort by X rank
        $xy = \array_map(
            function ($x, $y) {
                return [$x, $y];
            },
            $X,
            $Y
        );
        \usort($xy, function ($a, $b) {
            return $a[0] <=> $b[0];
        });

        // Initialize counters
        $nc      = 0;  // concordant pairs
        $nd      = 0;  // discordant pairs
        $ties_x  = 0;  // ties xᵢ = xⱼ
        $ties_y  = 0;  // ties yᵢ = yⱼ
        $ties_xy = 0;  // ties xᵢ = xⱼ and yᵢ = yⱼ

        // Tally concordant, discordant, and tied pairs
        for ($i = 0; $i < $n; $i++) {
            for ($j = $i + 1; $j < $n; $j++) {
                // xᵢ = xⱼ and yᵢ = yⱼ -- neither concordant or discordant
                if ($xy[$i][self::X] == $xy[$j][self::X] && $xy[$i][self::Y] == $xy[$j][self::Y]) {
                    $ties_xy++;
                // xᵢ = xⱼ -- neither concordant or discordant
                } elseif ($xy[$i][self::X] == $xy[$j][self::X]) {
                    $ties_x++;
                // yᵢ = yⱼ -- neither concordant or discordant
                } elseif ($xy[$i][self::Y] == $xy[$j][self::Y]) {
                    $ties_y++;
                // xᵢ < xⱼ and yᵢ < yⱼ -- concordant
                } elseif ($xy[$i][self::X] < $xy[$j][self::X] && $xy[$i][self::Y] < $xy[$j][self::Y]) {
                    $nc++;
                // xᵢ > xⱼ and yᵢ < yⱼ or  xᵢ < xⱼ and yᵢ > yⱼ -- discordant
                } else {
                    $nd++;
                }
            }
        }

        // Numerator: (number of concordant pairs) - (number of discordant pairs)
        $⟮nc − nd⟯ = $nc - $nd;

        /* tau-a (no rank ties):
         *
         *        nc - nd
         *   τ = ----------
         *       n(n - 1)/2
         */
        if ($ties_x == 0 && $ties_y == 0) {
            return $⟮nc − nd⟯ / (($n * ($n - 1)) / 2);
        }

        /* tau-b (rank ties exist):
         *
         *                 nc - nd
         *   τ = -----------------------------
         *       √(nc + nd + X₀)(nc + nd + Y₀)
         */
        return $⟮nc − nd⟯ / \sqrt(($nc + $nd + $ties_x) * ($nc + $nd + $ties_y));
    }

    /**
     * ρ - Spearman's rank correlation coefficient (Spearman's rho)
     *
     * https://en.wikipedia.org/wiki/Spearman%27s_rank_correlation_coefficient
     *
     *     cov(rgᵪ, rgᵧ)
     * ρ = ------------
     *        σᵣᵪσᵣᵧ
     *
     *   Where
     *    cov(rgᵪ, rgᵧ): covariance of the rank variables
     *    σᵣᵪ and σᵣᵧ:   standard deviations of the rank variables
     *
     * @param array $X values for random variable X
     * @param array $Y values for random variable Y
     *
     * @return float
     *
     * @throws Exception\BadDataException if both random variables do not have the same number of elements
     * @throws Exception\OutOfBoundsException if one of the random variables is empty
     */
    public static function spearmansRho(array $X, array $Y): float
    {
        if (\count($X) !== \count($Y)) {
            throw new Exception\BadDataException('Both random variables for spearmansRho must have the same number of elements');
        }

        $rgᵪ         = Distribution::fractionalRanking($X);
        $rgᵧ         = Distribution::fractionalRanking($Y);
        $cov⟮rgᵪ、rgᵧ⟯ = Correlation::covariance($rgᵪ, $rgᵧ);
        $σᵣᵪ         = Descriptive::sd($rgᵪ);
        $σᵣᵧ         = Descriptive::sd($rgᵧ);

        return $cov⟮rgᵪ、rgᵧ⟯  / ($σᵣᵪ * $σᵣᵧ);
    }

    /**
     * Descriptive correlation report about two random variables
     *
     * @param  array $X values for random variable X
     * @param  array $Y values for random variable Y
     * @param  bool $population Optional flag if all samples of a population are present
     *
     * @return array [cov, r, R2, tau, rho]
     *
     * @throws Exception\BadDataException
     * @throws Exception\OutOfBoundsException
     */
    public static function describe(array $X, array $Y, bool $population = false): array
    {
        return [
            'cov' => self::covariance($X, $Y, $population),
            'r'   => self::r($X, $Y, $population),
            'r2'  => self::r2($X, $Y, $population),
            'tau' => self::kendallsTau($X, $Y),
            'rho' => self::spearmansRho($X, $Y),
        ];
    }

    /**
     * Confidence ellipse (error ellipse)
     * Given the data in $X and $Y, create an ellipse
     * surrounding the data at $z standard deviations.
     *
     * The function will return $num_points pairs of X,Y data
     * http://stackoverflow.com/questions/3417028/ellipse-around-the-data-in-matlab
     *
     * @param array $X an array of independent data
     * @param array $Y an array of dependent data
     * @param float $z the number of standard deviations to encompass
     * @param int $num_points the number of points to include around the ellipse. The actual array
     *                          will be one larger because the first point and last will be repeated
     *                          to ease display.
     *
     * @return array paired x and y points on an ellipse aligned with the data provided
     *
     * @throws Exception\BadDataException
     * @throws Exception\BadParameterException
     * @throws Exception\IncorrectTypeException
     * @throws Exception\MatrixException
     * @throws Exception\OutOfBoundsException
     */
    public static function confidenceEllipse(array $X, array $Y, float $z, int $num_points = 11): array
    {
        $standardNormal = new StandardNormal();
        $p  = 2 * $standardNormal->cdf($z) - 1;
        $chiSquared = new ChiSquared(2);
        $χ² = $chiSquared->inverse($p);

        $data_array[] = $X;
        $data_array[] = $Y;
        $data_matrix  = new NumericMatrix($data_array);

        $covariance_matrix = $data_matrix->covarianceMatrix();

        // Scale the data by the confidence interval
        $cov         = $covariance_matrix->scalarMultiply($χ²);
        $eigenvalues = Eigenvalue::closedFormPolynomialRootMethod($cov);

        // Sort the eigenvalues from highest to lowest
        \rsort($eigenvalues);
        $V = Eigenvector::eigenvectors($cov, $eigenvalues);

        // Make ia diagonal matrix of the eigenvalues
        $D = MatrixFactory::diagonal($eigenvalues);
        $D = $D->map('\sqrt');
        $transformation_matrix = $V->multiply($D);

        $x_bar = Average::mean($X);
        $y_bar = Average::mean($Y);
        $translation_matrix = new NumericMatrix([[$x_bar],[$y_bar]]);

        // We add a row to allow the transformation matrix to also traslate the ellipse to a different location
        $transformation_matrix = $transformation_matrix->augment($translation_matrix);

        $unit_circle = new NumericMatrix(Trigonometry::unitCircle($num_points));

        // We add a column of ones to allow us to translate the ellipse
        $unit_circle_with_ones = $unit_circle->augment(MatrixFactory::one($num_points, 1));

        // The unit circle is rotated, stretched, and translated to the appropriate ellipse by the translation matrix.
        $ellipse = $transformation_matrix->multiply($unit_circle_with_ones->transpose())->transpose();

        return $ellipse->getMatrix();
    }
}
