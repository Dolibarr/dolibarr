<?php

namespace MathPHP\Statistics;

use MathPHP\Probability\Distribution\Continuous\StandardNormal;
use MathPHP\Probability\Distribution\Continuous\StudentT;
use MathPHP\Probability\Distribution\Continuous\ChiSquared;
use MathPHP\Exception;

/**
 * Tests of statistical significance
 *  - Z-test (one sample)
 *  - Z-score
 *  - T-test (one and two samples)
 *  - T-score
 *  - χ² test
 *  - SEM (Standard Error of the Mean)
 */
class Significance
{
    public const Z_TABLE_VALUE = true;
    public const Z_RAW_VALUE   = false;

    /**
     * One-sample Z-test
     * Convenience method for zTestOneSample()
     *
     * @param float $Hₐ Alternate hypothesis (M Sample mean)
     * @param int   $n  Sample size
     * @param float $H₀ Null hypothesis (μ Population mean)
     * @param float $σ  SD of population (Standard error of the mean)
     *
     * @return array [
     *   z  => z score
     *   p1 => one-tailed p value (left or right tail depends on how Hₐ differs from H₀)
     *   p2 => two-tailed p value
     * ]
     */
    public static function zTest(float $Hₐ, int $n, float $H₀, float $σ): array
    {
        return self::zTestOneSample($Hₐ, $n, $H₀, $σ);
    }

    /**
     * One-sample Z-test
     * When the population mean and standard deviation are known.
     * https://en.wikipedia.org/wiki/Z-test
     *
     *     Hₐ - H₀   M - μ   M - μ   M - μ
     * z = ------- = ----- = ----- = -----
     *        σ        σ      SEM     σ/√n
     *
     * p1 = CDF below if left tailed
     *    = CDF above if right tailed
     * p2 = CDF outside
     *
     * @param float $Hₐ Alternate hypothesis (M Sample mean)
     * @param int   $n  Sample size
     * @param float $H₀ Null hypothesis (μ Population mean)
     * @param float $σ  SD of population (Standard error of the mean)
     *
     * @return array [
     *   z  => z score
     *   p1 => one-tailed p value (left or right tail depends on how Hₐ differs from H₀)
     *   p2 => two-tailed p value
     * ]
     */
    public static function zTestOneSample(float $Hₐ, int $n, float $H₀, float $σ): array
    {
        // Calculate z score (test statistic)
        $sem = self::sem($σ, $n);
        $z   = self::zScore($Hₐ, $H₀, $sem, self::Z_RAW_VALUE);

        // One- and two-tailed P values
        $standardNormal = new StandardNormal();
        if ($Hₐ < $H₀) {
            $p1 = $standardNormal->cdf($z);
        } else {
            $p1 = $standardNormal->above($z);
        }
        $p2 = $standardNormal->outside(-\abs($z), \abs($z));

        return [
            'z'  => $z,
            'p1' => $p1,
            'p2' => $p2,
        ];
    }

    /**
     * Two-sample z-test
     * Test the means of two samples.
     * https://en.wikipedia.org/wiki/Z-test
     * http://www.stat.ucla.edu/~cochran/stat10/winter/lectures/lect21.html
     *
     * The sample is from two independent treatment groups.
     * Conducts a z test for two means where the standard deviations are known.
     *
     *      μ₁ - μ₂ - Δ
     * z = --------------
     *        _________
     *       /σ₁²   σ₂²
     *      / --- + ---
     *     √   n₁    n₂
     *
     * where
     *  μ₁ is sample mean 1
     *  μ₂ is sample mean 2
     *  Δ  is the hypothesized difference between the population means (0 if testing for equal means)
     *  σ₁ is standard deviation of sample mean 1
     *  σ₂ is standard deviation of sample mean 2
     *  n₁ is sample size of mean 1
     *  n₂ is sample size of mean 2
     *
     * p1 = CDF above
     * p2 = CDF outside
     *
     * @param float $μ₁ Sample mean of population 1
     * @param float $μ₂ Sample mean of population 2
     * @param int   $n₁ Sample size of population 1
     * @param int   $n₂ Sample size of population 1
     * @param float $σ₁ Standard deviation of sample mean 1
     * @param float $σ₂ Standard deviation of sample mean 2
     * @param float $Δ  (Optional) hypothesized difference between the population means (0 if testing for equal means)
     *
     * @return array [
     *   z  => z score
     *   p1 => one-tailed p value
     *   p2 => two-tailed p value
     * ]
     */
    public static function zTestTwoSample(float $μ₁, float $μ₂, int $n₁, int $n₂, float $σ₁, float $σ₂, float $Δ = 0.0): array
    {
        // Calculate z score (test statistic)
        $z = ($μ₁ - $μ₂ - $Δ) / \sqrt((($σ₁ ** 2) / $n₁) + (($σ₂ ** 2) / $n₂));

        $standardNormal = new StandardNormal();
        // One- and two-tailed P values
        $p1 = $standardNormal->above(\abs($z));
        $p2 = $standardNormal->outside(-\abs($z), \abs($z));

        return [
            'z'  => $z,
            'p1' => $p1,
            'p2' => $p2,
        ];
    }

    /**
     * Z score - standard score
     * https://en.wikipedia.org/wiki/Standard_score
     *
     *     M - μ
     * z = -----
     *       σ
     *
     * @param float $M           Sample mean
     * @param float $μ           Population mean
     * @param float $σ           Population standard deviation
     * @param bool   $table_value Whether to return a rouned z score for looking up in a standard normal table, or the raw z score value
     *
     * @return float
     */
    public static function zScore(float $M, float $μ, float $σ, bool $table_value = false): float
    {
        $z = ($M - $μ) / $σ;

        return $table_value
            ? \round($z, 2)
            : $z;
    }

    /**
     * t-test - one sample or two sample tests
     * https://en.wikipedia.org/wiki/Student%27s_t-test
     *
     * @param array $a sample set 1
     * @param float|array $b population mean for one sample t test; sample set 2 for two sample t-test
     *
     * @return array
     *
     * @throws Exception\BadParameterException
     * @throws Exception\OutOfBoundsException
     */
    public static function tTest(array $a, $b): array
    {
        if (\is_numeric($b)) {
            return self::tTestOneSample($a, $b);
        }
        if (\is_array($b)) {
            return self::tTestTwoSample($a, $b);
        }

        throw new Exception\BadParameterException('Second parameter must be numeric for one-sample t-test, or an array for two-sample t-test');
    }

    /**
     * One-sample Student's t-test
     * Compares sample mean to the population mean.
     * https://en.wikipedia.org/wiki/Student%27s_t-test
     *
     *     Hₐ - H₀   M - μ   M - μ   M - μ
     * t = ------- = ----- = ----- = -----
     *        σ        σ      SEM     σ/√n
     *
     * p1 = CDF below if left tailed
     *    = CDF above if right tailed
     * p2 = CDF outside
     *
     * @param array $a Sample set
     * @param float $H₀ Null hypothesis (μ₀ Population mean)
     *
     * @return array [
     *   t    => t score
     *   df   => degrees of freedom
     *   p1   => one-tailed p value (left or right tail depends on how Hₐ differs from H₀)
     *   p2   => two-tailed p value
     *   mean => sample mean
     *   sd   => standard deviation
     * ]
     *
     * @throws Exception\OutOfBoundsException
     */
    public static function tTestOneSample(array $a, float $H₀): array
    {
        $n  = \count($a);
        $Hₐ = Average::mean($a);
        $σ  = Descriptive::standardDeviation($a, Descriptive::SAMPLE);

        return self::tTestOneSampleFromSummaryData($Hₐ, $σ, $n, $H₀);
    }

    /**
     * One-sample Student's t-test from summary data
     * Compares sample mean to the population mean.
     * https://en.wikipedia.org/wiki/Student%27s_t-test
     *
     *     Hₐ - H₀   M - μ   M - μ   M - μ
     * t = ------- = ----- = ----- = -----
     *        σ        σ      SEM     σ/√n
     *
     * p1 = CDF below if left tailed
     *    = CDF above if right tailed
     * p2 = CDF outside
     *
     * @param float $Hₐ Alternate hypothesis (M Sample mean)
     * @param float $s  SD of sample
     * @param int    $n  Sample size
     * @param float $H₀ Null hypothesis (μ₀ Population mean)
     *
     * @return array [
     *   t    => t score
     *   df   => degrees of freedom
     *   p1   => one-tailed p value (left or right tail depends on how Hₐ differs from H₀)
     *   p2   => two-tailed p value
     *   mean => sample mean
     *   sd   => standard deviation
     * ]
     */
    public static function tTestOneSampleFromSummaryData(float $Hₐ, float $s, int $n, float $H₀): array
    {
        // Calculate test statistic t
        $t = self::tScore($Hₐ, $s, $n, $H₀);

        // Degrees of freedom
        $ν = $n - 1;

        // One- and two-tailed P values
        $studentT = new StudentT($ν);
        if ($Hₐ < $H₀) {
            $p1 = $studentT->cdf($t);
        } else {
            $p1 = $studentT->above($t);
        }
        $p2 = $studentT->outside(-\abs($t), \abs($t));

        return [
            't'    => $t,
            'df'   => $ν,
            'p1'   => $p1,
            'p2'   => $p2,
            'mean' => $Hₐ,
            'sd'   => $s,
        ];
    }

    /**
     * Two-sample t-test (Welch's test)
     * Test the means of two samples.
     * https://en.wikipedia.org/wiki/Student%27s_t-test
     *
     *        μ₁ - μ₂
     * t = --------------
     *        _________
     *       /σ₁²   σ₂²
     *      / --- + ---
     *     √   n₁    n₂
     *
     *
     *         / σ₁²   σ₂² \²
     *        | --- + ---  |
     *         \ n₁    n₂  /
     * ν =  -------------------
     *      (σ₁²/n₁)²  (σ₂²/n₂)²
     *      -------- + --------
     *       n₁ - 1     n₂ - 1
     *
     * where
     *  μ₁ is sample mean 1
     *  μ₂ is sample mean 2
     *  σ₁ is standard deviation of sample mean 1
     *  σ₂ is standard deviation of sample mean 2
     *  n₁ is sample size of mean 1
     *  n₂ is sample size of mean 2
     *  t  is test statistic
     *  ν  is degrees of freedom
     *
     * p1 = CDF above
     * p2 = CDF outside
     *
     * @param array $x₁ sample set 1
     * @param array $x₂ sample set 2
     *
     * @return array [
     *   t     => t score
     *   df    => degrees of freedom
     *   p1    => one-tailed p value
     *   p2    => two-tailed p value
     *   mean1 => mean of sample set 1
     *   mean2 => mean of sample set 2
     *   sd1   => standard deviation of sample set 1
     *   sd2   => standard deviation of sample set 2
     * ]
     *
     * @throws Exception\OutOfBoundsException
     */
    public static function tTestTwoSample(array $x₁, array $x₂): array
    {
        $n₁ = \count($x₁);
        $n₂ = \count($x₂);

        $μ₁ = Average::mean($x₁);
        $μ₂ = Average::mean($x₂);

        $σ₁ = Descriptive::sd($x₁, Descriptive::SAMPLE);
        $σ₂ = Descriptive::sd($x₂, Descriptive::SAMPLE);

        return self::tTestTwoSampleFromSummaryData($μ₁, $μ₂, $n₁, $n₂, $σ₁, $σ₂);
    }

    /**
     * Two-sample t-test (Welch's test) from summary data
     * Test the means of two samples.
     * https://en.wikipedia.org/wiki/Student%27s_t-test
     *
     *        μ₁ - μ₂
     * t = --------------
     *        _________
     *       /σ₁²   σ₂²
     *      / --- + ---
     *     √   n₁    n₂
     *
     *
     *         / σ₁²   σ₂² \²
     *        | --- + ---  |
     *         \ n₁    n₂  /
     * ν =  -------------------
     *      (σ₁²/n₁)²  (σ₂²/n₂)²
     *      -------- + --------
     *       n₁ - 1     n₂ - 1
     *
     * where
     *  μ₁ is sample mean 1
     *  μ₂ is sample mean 2
     *  σ₁ is standard deviation of sample mean 1
     *  σ₂ is standard deviation of sample mean 2
     *  n₁ is sample size of mean 1
     *  n₂ is sample size of mean 2
     *  t  is test statistic
     *  ν  is degrees of freedom
     *
     * p1 = CDF above
     * p2 = CDF outside
     *
     * @param float $μ₁ Sample mean of population 1
     * @param float $μ₂ Sample mean of population 2
     * @param int   $n₁ Sample size of population 1
     * @param int   $n₂ Sample size of population 1
     * @param float $σ₁ Standard deviation of sample mean 1
     * @param float $σ₂ Standard deviation of sample mean 2
     *
     * @return array [
     *   t     => t score
     *   df    => degrees of freedom
     *   p1    => one-tailed p value
     *   p2    => two-tailed p value
     *   mean1 => mean of sample set 1
     *   mean2 => mean of sample set 2
     *   sd1   => standard deviation of sample set 1
     *   sd2   => standard deviation of sample set 2
     * ]
     */
    public static function tTestTwoSampleFromSummaryData(float $μ₁, float $μ₂, int $n₁, int $n₂, float $σ₁, float $σ₂): array
    {
        // Calculate t score (test statistic)
        $t = ($μ₁ - $μ₂) / \sqrt((($σ₁ ** 2) / $n₁) + (($σ₂ ** 2) / $n₂));

        // Degrees of freedom
        $ν = ((($σ₁ ** 2) / $n₁) + (($σ₂ ** 2) / $n₂)) ** 2
            /
            (((($σ₁ ** 2) / $n₁) ** 2 / ($n₁ - 1)) + ((($σ₂ ** 2) / $n₂) ** 2 / ($n₂ - 1)));

        // One- and two-tailed P values
        $studentT = new StudentT($ν);
        $p1 = $studentT->above(\abs($t));
        $p2 = $studentT->outside(-\abs($t), \abs($t));

        return [
            't'  => $t,
            'df' => $ν,
            'p1' => $p1,
            'p2' => $p2,
            'mean1' => $μ₁,
            'mean2' => $μ₂,
            'sd1'   => $σ₁,
            'sd2'   => $σ₂,
        ];
    }

    /**
     * T-score
     *
     *     Hₐ - H₀   X - μ
     * t = ------- = -----
     *      s/√n      s/√n
     *
     * @param float $Hₐ Alternate hypothesis (M Sample mean)
     * @param float $s  SD of sample
     * @param int    $n  Sample size
     * @param float $H₀ Null hypothesis (μ₀ Population mean)
     *
     * @return float
     */
    public static function tScore(float $Hₐ, float $s, int $n, float $H₀): float
    {
        return ($Hₐ - $H₀) / ($s / \sqrt($n));
    }

    /**
     * χ² test (chi-squared goodness of fit test)
     * Tests the hypothesis that data were generated according to a
     * particular chance model (Statistics [Freedman, Pisani, Purves]).
     * https://en.wikipedia.org/wiki/Chi-squared_test#Example_chi-squared_test_for_categorical_data
     *
     *        (Oᵢ - Eᵢ)²
     * χ² = ∑ ----------
     *            Eᵢ
     *  where:
     *   O = observed value
     *   E = expected value
     *
     * k (degrees of freedom) = number of terms - 1
     *
     * p = χ² distribution CDF(χ², k)
     *
     * @param  array  $observed
     * @param  array  $expected
     *
     * @return array [chi-square, p]
     *
     * @throws Exception\BadDataException if count of observed does not equal count of expected
     */
    public static function chiSquaredTest(array $observed, array $expected): array
    {
        // Arrays must have the same number of elements
        if (\count($observed) !== \count($expected)) {
            throw new Exception\BadDataException('Observed and expected must have the same number of elements');
        }

        // Reset array indexes and initialize
        $O  = \array_values($observed);
        $E  = \array_values($expected);
        $n  = \count($observed);        // number of terms
        $k  = $n - 1;                   // degrees of freedom
        $χ² = 0;

        /*
         *        (Oᵢ - Eᵢ)²
         * χ² = ∑ ----------
         *            Eᵢ
         */
        for ($i = 0; $i < $n; $i++) {
            $χ² += (($O[$i] - $E[$i]) ** 2) / $E[$i];
        }

        $chiSquared = new ChiSquared($k);
        $p = $chiSquared->above($χ²);

        return [
            'chi-square' => $χ²,
            'p'          => $p,
        ];
    }

    /**
     * Standard error of the mean (SEM)
     * Can be considered true standard deviation of the sample mean.
     * Used in the Z test.
     * https://en.wikipedia.org/wiki/Standard_error
     *
     *       σ
     * SEM = --
     *       √n
     *
     * @param float $σ Population standard deviation
     * @param int   $n Sample size (number of observations of the sample)
     *
     * @return float
     */
    public static function sem(float $σ, int $n): float
    {
        return $σ / \sqrt($n);
    }
}
