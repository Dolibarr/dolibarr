<?php

namespace MathPHP\Statistics;

use MathPHP\Exception;

/**
 * Effect size is a quantitative measure of the strength of a phenomenon.
 * https://en.wikipedia.org/wiki/Effect_size
 *
 * - η² (Eta-squared)
 * - η²p (Partial eta-squared)
 * - ω² (omega-squared)
 * - Cohen's ƒ²
 * - Cohen's q
 * - Cohen's d
 * - Hedges' g
 * - Glass' Δ (glass' delta)
 */
class EffectSize
{
    /**
     * η² (Eta-squared)
     *
     * Eta-squared describes the ratio of variance explained in the dependent
     * variable by a predictor while controlling for other predictors, making
     * it analogous to the r².
     * https://en.wikipedia.org/wiki/Effect_size#Eta-squared_.28.CE.B72.29
     *
     *      SSt
     * η² = ---
     *      SST
     *
     * where:
     *  SSt = sum of squares treatment
     *  SST = sum of squares total
     *
     * @param  float $SSt Sum of squares treatment
     * @param  float $SST Sum of squares total
     *
     * @return float
     */
    public static function etaSquared(float $SSt, float $SST): float
    {
        return $SSt / $SST;
    }

    /**
     * η²p (Partial eta-squared)
     *
     * https://en.wikipedia.org/wiki/Effect_size#Eta-squared_.28.CE.B72.29
     *
     *          SSt
     * η²p = ---------
     *       SSt + SSE
     *
     * where:
     *  SSt = sum of squares treatment
     *  SSE = sum of squares error
     *
     * @param  float $SSt Sum of squares treatment
     * @param  float $SSE Sum of squares error
     *
     * @return float
     */
    public static function partialEtaSquared(float $SSt, float $SSE): float
    {
        return $SSt / ($SSt + $SSE);
    }

    /**
     * ω² (omega-squared)
     *
     * A less biased estimator of the variance explained in the population.
     * https://en.wikipedia.org/wiki/Effect_size#Omega-squared_.28.CF.892.29
     *
     *      SSt - dft * MSE
     * ω² = ---------------
     *         SST + MSE
     *
     * where:
     *  SSt = sum of squares treatment
     *  SST = sum of squares total
     *  dft = degrees of freedom treatment
     *  MSE = Mean squares error
     *
     * @param float $SSt Sum of squares treatment
     * @param int   $dft Degrees of freedom treatment
     * @param float $SST Sum of squares total
     * @param float $MSE Mean squares error
     *
     * @return float
     */
    public static function omegaSquared(float $SSt, int $dft, float $SST, float $MSE): float
    {
        return ($SSt - $dft * $MSE) / ($SST + $MSE);
    }

    /**
     * Cohen's ƒ²
     *
     * One of several effect size measures to use in the context of an F-test
     * for ANOVA or multiple regression. Its amount of bias (overestimation of
     * the effect size for the ANOVA) depends on the bias of its underlying
     * measurement of variance explained (R², η², ω²)
     * https://en.wikipedia.org/wiki/Effect_size#Cohen.27s_.C6.922
     *
     *        R²
     * ƒ² = ------
     *      1 - R²
     *
     *        η²
     * ƒ² = ------
     *      1 - η²
     *
     *        ω²
     * ƒ² = ------
     *      1 - ω²
     *
     * @param float $measure_of_variance_explained (R², η², ω²)
     *
     * @return float
     */
    public static function cohensF(float $measure_of_variance_explained): float
    {
        return $measure_of_variance_explained / (1 - $measure_of_variance_explained);
    }

    /**
     * Cohen's q
     *
     * The difference between two Fisher transformed Pearson regression coefficients.
     * hhttps://en.wikipedia.org/wiki/Effect_size#Cohen.27s_q
     *
     *     1     1 + r₁   1     1 + r₂
     * q = - log ------ - - log ------
     *     2     1 - r₁   2     1 - r₂
     *
     * where r₁ and r₂ are the regressions being compared
     *
     * @param float $r₁
     * @param float $r₂
     *
     * @return float
     *
     * @throws Exception\OutOfBoundsException if an r is ≤ 0
     */
    public static function cohensQ(float $r₁, float $r₂): float
    {
        if ($r₁ >= 1 || $r₂ >= 1) {
            throw new Exception\OutOfBoundsException('r must be greater than or equal to 1');
        }

        $½ = 0.5;

        return \abs(($½ * \log((1 + $r₁) / (1 - $r₁))) - ($½ * \log((1 + $r₂) / (1 - $r₂))));
    }

    /**
     * Cohen's d
     *
     * The difference between two means divided by a standard deviation for the data.
     * https://en.wikipedia.org/wiki/Effect_size#Cohen.27s_d
     *
     *     μ₁ - μ₂
     * d = -------
     *        s
     *
     *        _________
     *       /s₁² + s₂²
     * s =  / ---------
     *     √      2
     *
     * where
     *  μ₁  = mean of sample population 1
     *  μ₂  = mean of sample population 2
     *  s₁² = variance of sample population 1
     *  s₂² = variance of sample population 1
     *  s   = pooled standard deviation
     *
     * This formula uses the common simplified version of the pooled standard deviation.
     *
     * @param float $μ₁ Mean of sample population 1
     * @param float $μ₂ Mean of sample population 2
     * @param float $s₁ Standard deviation of sample population 1
     * @param float $s₂ Standard deviation of sample population 2
     *
     * @return float
     */
    public static function cohensD(float $μ₁, float $μ₂, float $s₁, float $s₂): float
    {
        // Variance of each data set
        $s₁² = $s₁ * $s₁;
        $s₂² = $s₂ * $s₂;

        // Pooled standard deviation
        $s = \sqrt(($s₁² + $s₂²) / 2);

        // d
        return ($μ₁ - $μ₂) / $s;
    }

    /**
     * Hedges' g
     *
     * The difference between two means divided by a standard deviation for the data.
     * https://en.wikipedia.org/wiki/Effect_size#Hedges.27_g
     * http://www.polyu.edu.hk/mm/effectsizefaqs/effect_size_equations2.html
     *
     *     μ₁ - μ₂
     * g = -------
     *        s*
     *
     *         _________________________
     *        /(n₁ - 1)s₁² + (n₂ - 1)s₂²
     * s* =  / -------------------------
     *      √         n₁ + n₂ - 2
     *
     *
     * Then, to remove bias
     *
     *       /          3        \
     * g* ≈ | 1 - --------------  | g
     *       \    4(n₁ + n₂) - 9 /
     *
     * where
     *  μ₁  = mean of sample population 1
     *  μ₂  = mean of sample population 2
     *  s₁² = variance of sample population 1
     *  s₂² = variance of sample population 1
     *  n₁  = sample size of sample population 1
     *  n₂  = sample size of sample population 2
     *  s*  = pooled standard deviation
     *
     * @param float $μ₁ Mean of sample population 1
     * @param float $μ₂ Mean of sample population 2
     * @param float $s₁ Standard deviation of sample population 1
     * @param float $s₂ Standard deviation of sample population 2
     * @param int   $n₁ Sample size of sample popluation 1
     * @param int   $n₂ Sample size of sample popluation 2
     *
     * @return float
     */
    public static function hedgesG(float $μ₁, float $μ₂, float $s₁, float $s₂, int $n₁, int $n₂): float
    {
        // Variance of each data set
        $s₁² = $s₁ * $s₁;
        $s₂² = $s₂ * $s₂;

        // Pooled standard deviation
        $⟮n₁ − 1⟯s₁² ＋ ⟮n₂ − 1⟯s₂²   = (($n₁ - 1) * $s₁²) + (($n₂ - 1) * $s₂²);
        $⟮n₁ ＋ n₂ − 2⟯              = $n₁ + $n₂ - 2;
        $s＊                        = \sqrt($⟮n₁ − 1⟯s₁² ＋ ⟮n₂ − 1⟯s₂² / $⟮n₁ ＋ n₂ − 2⟯);

        // g
        $g = ($μ₁ - $μ₂) / $s＊;

        // Unbiased g
        return (1 - (3 / (4 * ($n₁ + $n₂) - 9))) * $g;
    }

    /**
     * Glass' Δ (glass' delta)
     *
     * An estimator of the effect size that uses only the standard deviation of
     * the second group.
     * https://en.wikipedia.org/wiki/Effect_size#Glass.27_.CE.94
     *
     *     μ₁ - μ₂
     * Δ = -------
     *        s₂
     *
     * where
     *  μ₁ = mean of sample population 1
     *  μ₂ = mean of sample population 2
     *  s₂ = standard deviation of sample population 2
     *
     * @param float $μ₁ Mean of sample population 1
     * @param float $μ₂ Mean of sample population 2
     * @param float $s₂ Standard deviation of sample population 2
     *
     * @return float
     */
    public static function glassDelta(float $μ₁, float $μ₂, float $s₂): float
    {
        return ($μ₁ - $μ₂) / $s₂;
    }
}
