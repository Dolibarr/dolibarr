<?php

namespace MathPHP\Probability\Distribution\Continuous;

use MathPHP\Functions\Support;

/**
 * Pareto distribution
 * https://en.wikipedia.org/wiki/Pareto_distribution
 */
class Pareto extends Continuous
{
    /**
     * Distribution parameter bounds limits
     * a ∈ (0,∞)
     * b ∈ (0,∞)
     * @var array
     */
    public const PARAMETER_LIMITS = [
        'a' => '(0,∞)',
        'b' => '(0,∞)',
    ];

    /**
     * Distribution support bounds limits
     * x ∈ (0,∞)
     * @var array
     */
    public const SUPPORT_LIMITS = [
        'x' => '(0,∞)',
        'a' => '(0,∞)',
        'b' => '(0,∞)',
    ];

    /** @var float Shape Parameter */
    protected $a;

    /** @var float Scale Parameter */
    protected $b;

    /**
     * Constructor
     *
     * @param float $a shape parameter
     * @param float $b scale parameter
     */
    public function __construct(float $a, float $b)
    {
        parent::__construct($a, $b);
    }

    /**
     * Probability density function
     *
     *          abᵃ
     * P(x) =  ----  for x ≥ b
     *         xᵃ⁺¹
     *
     * P(x) = 0      for x < b
     *
     * @param  float $x
     *
     * @return float
     */
    public function pdf(float $x): float
    {
        Support::checkLimits(self::SUPPORT_LIMITS, ['x' => $x]);

        $a = $this->a;
        $b = $this->b;
        if ($x < $b) {
            return 0;
        }

        $abᵃ  = $a * $b ** $a;
        $xᵃ⁺¹ = \pow($x, $a + 1);
        return $abᵃ / $xᵃ⁺¹;
    }
    /**
     * Cumulative distribution function
     *
     *             / b \ᵃ
     * D(x) = 1 - |  -  | for x ≥ b
     *             \ x /
     *
     * D(x) = 0           for x < b
     *
     * @param  float $x
     *
     * @return float
     */
    public function cdf(float $x): float
    {
        Support::checkLimits(self::SUPPORT_LIMITS, ['x' => $x]);

        $a = $this->a;
        $b = $this->b;
        if ($x < $b) {
            return 0;
        }
        return 1 - \pow($b / $x, $a);
    }

    /**
     * Inverse CDF (quantile)
     *
     *             b
     * F⁻¹(P) = -------
     *          (1 - P)¹/ᵃ
     *
     * @param float $p
     *
     * @return float
     */
    public function inverse(float $p): float
    {
        $a = $this->a;
        $b = $this->b;

        if ($p == 0) {
            return -\INF;
        }
        if ($p == 1) {
            return \INF;
        }

        return $b / ((1 - $p) ** (1 / $a));
    }

    /**
     * Mean of the distribution
     *
     * μ = ∞ for a ≤ 1
     *
     *      ab
     * μ = ----- for a > 1
     *     a - 1
     *
     * @return float
     */
    public function mean(): float
    {
        $a = $this->a;
        $b = $this->b;

        if ($a <= 1) {
            return \INF;
        }

        return $a * $b / ($a - 1);
    }

    /**
     * Median of the distribution
     *
     * median = a ᵇ√2
     *
     * @return float
     */
    public function median(): float
    {
        $a = $this->a;
        $b = $this->b;

        return $a * (2 ** (1 / $b));
    }

    /**
     * Mode of the distribution
     *
     * mode = a
     *
     * @return float
     */
    public function mode(): float
    {
        return $this->a;
    }

    /**
     * Variance of the distribution
     *
     * σ² = ∞                 a ≤ 2
     *
     *            ab²
     * σ² = ---------------   a > 2
     *      (a - 1)²(a - 2)
     *
     * @return float
     */
    public function variance(): float
    {
        $a = $this->a;
        $b = $this->b;

        if ($a <= 2) {
            return \INF;
        }

        return ($a * $b ** 2) / (($a - 1) ** 2 * ($a - 2));
    }
}
