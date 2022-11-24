<?php

namespace MathPHP\Statistics;

use MathPHP\Exception;

/**
 * Statistical experiments (Epidemiology methods, etc.)
 *  - Risk ratio
 *  - Odds ratio
 *  - Likelihood ratio
 */
class Experiment
{
    /**
     * Z score for 95% confidence interval
     * @var float
     */
    private const Z = 1.96;

    /**
     * Normal lower tail probability for calculating P value
     * @var float
     */
    private const NORMAL_LOWER_TAIL_PROBABILITY = -0.717;

    /**
     * Normal upper tail probability for calculating P value
     * @var float
     */
    private const NORMAL_UPPER_TAIL_PROBABILITY = 0.416;

    /**
     * Risk ratio (relative risk) - RR
     * Computes risk ratio and 95% confidence interval.
     *
     * The ratio of the probability of an event occurring in an exposed group
     * to the probability of the event occurring in a comparison, non-exposed group.
     * https://en.wikipedia.org/wiki/Relative_risk
     * http://www.bmj.com/content/343/bmj.d2304
     *
     *        P(event when exposed)     a / (a + b)
     * RR = ------------------------- = -----------
     *      P(event when non-exposed)   c / (c + d)
     *
     * Standard error of the log relative risk:
     *                ______________________
     *               / 1   1     1       1
     * SS{ln(RR)} = /  - + - - ----- - -----
     *             √   a   c   a + b   c + d
     *
     * CI Range(95%) = exp( ln(RR) - z × SS{ln(RR)} ) to exp( ln(RR) + z × SS{ln(RR)} )
     *
     * P = exp((-0.717 * z) - (0.416 * z²))
     *
     * @param  int   $a Exposed and event present
     * @param  int   $b Exposed and event absent
     * @param  int   $c Non-exposed and event present
     * @param  int   $d Non-exposed and event absent
     *
     * @return array [ RR, ci_lower_bound, ci_upper_bound, p ]
     */
    public static function riskRatio(int $a, int $b, int $c, int $d): array
    {
        // Risk ratio
        $RR = ($a / ($a + $b)) / ($c / ($c + $d));

        // Standard error of the log relative risk
        $ln⟮RR⟯     = \log($RR);
        $SS｛ln⟮RR⟯｝ = \sqrt((1 / $a) + (1 / $c) - (1 / ($a + $b)) - (1 / ($c + $d)));

        // Z score for 95% confidence interval
        $z = 1.96;

        // Confidence interval
        $ci_lower_bound = \exp($ln⟮RR⟯ - ($z * $SS｛ln⟮RR⟯｝));
        $ci_upper_bound = \exp($ln⟮RR⟯ + ($z * $SS｛ln⟮RR⟯｝));

        // P-value (significance level)
        $est = \log($RR);                   // estimate of effect
        $l   = \log($ci_lower_bound);       // ln CI lower bound
        $u   = \log($ci_upper_bound);       // ln CI upper bound
        $SE  = ($u - $l) / (2 * self::Z);  // standard error
        $z   = \abs($est / $SE);            // test statistic z
        $p   = \exp((self::NORMAL_LOWER_TAIL_PROBABILITY * $z) - (self::NORMAL_UPPER_TAIL_PROBABILITY * $z ** 2));

        return [
            'RR'             => $RR,
            'ci_lower_bound' => $ci_lower_bound,
            'ci_upper_bound' => $ci_upper_bound,
            'p'              => $p,
        ];
    }

    /**
     * Odds ratio (OR)
     * Computes odds ratio and 95% confidence ratio.
     *
     * Ratio which quantitatively describes the association between the presence/absence
     * of "A" and the presence/absence of "B" for individuals in the population.
     * https://en.wikipedia.org/wiki/Odds_ratio
     * http://www.bmj.com/content/343/bmj.d2304
     *
     *      a / b
     * OR = -----
     *      c / d
     *
     * Standard error of the log odds ratio:
     *                ______________
     *               / 1   1   1   1
     * SS{ln(OR)} = /  - + - - - + -
     *             √   a   b   c   d
     *
     * CI Range(95%) = exp( ln(OR) - z × SS{ln(OR)} ) to exp( ln(OR) + z × SS{ln(OR)} )
     *
     * P = exp((-0.717 * z) - (0.416 * z²))
     *
     * @param  int   $a Exposed and event present
     * @param  int   $b Exposed and event absent
     * @param  int   $c Non-exposed and event present
     * @param  int   $d Non-exposed and event absent
     *
     * @return array [ OR, ci_lower_bound, ci_upper_bound, p ]
     */
    public static function oddsRatio(int $a, int $b, int $c, int $d): array
    {
        // Odds ratio
        $OR = ($a / $b) / ($c / $d);

        // Standard error of the log odds ratio
        $ln⟮OR⟯     = \log($OR);
        $SS｛ln⟮OR⟯｝ = \sqrt((1 / $a) + (1 / $b) + (1 / $c) + (1 / $d));

        // Confidence interval
        $ci_lower_bound = \exp($ln⟮OR⟯ - (self::Z * $SS｛ln⟮OR⟯｝));
        $ci_upper_bound = \exp($ln⟮OR⟯ + (self::Z * $SS｛ln⟮OR⟯｝));

        // P-value (significance level)
        $est = \log($OR);                   // estimate of effect
        $l   = \log($ci_lower_bound);       // ln CI lower bound
        $u   = \log($ci_upper_bound);       // ln CI upper bound
        $SE  = ($u - $l) / (2 * self::Z);  // standard error
        $z   = \abs($est / $SE);            // test statistic z
        $p   = \exp((self::NORMAL_LOWER_TAIL_PROBABILITY * $z) - (self::NORMAL_UPPER_TAIL_PROBABILITY * $z ** 2));

        return [
            'OR'             => $OR,
            'ci_lower_bound' => $ci_lower_bound,
            'ci_upper_bound' => $ci_upper_bound,
            'p'              => $p,
        ];
    }

    /**
     * Likelihood ratio
     * Computes positive and negative likelihood ratios from a, b, c, d variables.
     *
     * Used to analyze the goodness of a diagnostic tests.
     * https://en.wikipedia.org/wiki/Likelihood_ratios_in_diagnostic_testing
     *
     *       a / (a + c)
     * LL+ = -----------
     *       b / (b + d)
     *
     *       c / (a + c)
     * LL- = -----------
     *       d / (b + d)
     *
     * @param  int   $a Exposed and event present
     * @param  int   $b Exposed and event absent
     * @param  int   $c Non-exposed and event present
     * @param  int   $d Non-exposed and event absent
     *
     * @return array [ LL+, LL- ]
     */
    public static function likelihoodRatio(int $a, int $b, int $c, int $d): array
    {
        // LL+ Positive likelihood ratio
        $LL＋ = ($a / ($a + $c)) / ($b / ($b + $d));

        // LL- Negative likelihood ratio
        $LL− = ($c / ($a + $c)) / ($d / ($b + $d));

        return [
            'LL+' => $LL＋,
            'LL-' => $LL−,
        ];
    }

    /**
     * Likelihood ratio
     * Computes positive and negative likelihood ratios from sensitivity and specificity.
     *
     * Used to analyze the goodness of a diagnostic tests.
     * https://en.wikipedia.org/wiki/Likelihood_ratios_in_diagnostic_testing
     *
     *         sensitivity
     * LL+ = ---------------
     *       1 - specificity
     *
     *       1 - sensitivity
     * LL- = ---------------
     *         specificity
     *
     * @param  float $sensitivity
     * @param  float $specificity
     *
     * @return array [ LL+, LL- ]
     *
     * @throws Exception\OutOfBoundsException if sensitivity or specificity are > 1.0
     */
    public static function likelihoodRatioSS(float $sensitivity, float $specificity): array
    {
        if ($sensitivity > 1.0 || $specificity > 1.0) {
            throw new Exception\OutOfBoundsException('Sensitivity and specificity must be <= 1.0');
        }

        // LL+ Positive likelihood ratio
        $LL＋ = $sensitivity / (1 - $specificity);

        // LL- Negative likelihood ratio
        $LL− = (1 - $sensitivity) / $specificity;

        return [
            'LL+' => $LL＋,
            'LL-' => $LL−,
        ];
    }
}
