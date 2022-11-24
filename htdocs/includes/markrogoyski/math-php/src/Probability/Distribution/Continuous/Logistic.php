<?php

namespace MathPHP\Probability\Distribution\Continuous;

use MathPHP\Functions\Support;

/**
 * Logistic distribution
 * https://en.wikipedia.org/wiki/Logistic_distribution
 */
class Logistic extends Continuous
{
    /**
     * Distribution parameter bounds limits
     * μ ∈ (-∞,∞)
     * s ∈ (0,∞)
     * @var array
     */
    public const PARAMETER_LIMITS = [
        'μ' => '(-∞,∞)',
        's' => '(0,∞)',
    ];

    /**
     * Distribution support bounds limits
     * x ∈ (-∞,∞)
     * @var array
     */
    public const SUPPORT_LIMITS = [
        'x' => '(-∞,∞)',
    ];

    /** @var float Location Parameter */
    protected $μ;

    /** @var float Scale Parameter */
    protected $s;

    /**
     * Constructor
     *
     * @param float $μ shape parameter
     * @param float $s shape parameter s > 0
     */
    public function __construct(float $μ, float $s)
    {
        parent::__construct($μ, $s);
    }

    /**
     * Probability density function
     *
     *                     /  x - μ \
     *                 exp| - -----  |
     *                     \    s   /
     * f(x; μ, s) = -----------------------
     *                /        /  x - μ \ \ ²
     *              s| 1 + exp| - -----  | |
     *                \        \    s   / /
     *
     * @param float $x
     *
     * @return float
     */
    public function pdf(float $x): float
    {
        Support::checkLimits(self::SUPPORT_LIMITS, ['x' => $x]);

        $μ = $this->μ;
        $s = $this->s;

        $ℯ＾⁻⁽x⁻μ⁾／s = \exp(-($x - $μ) / $s);
        return $ℯ＾⁻⁽x⁻μ⁾／s / ($s * \pow(1 + $ℯ＾⁻⁽x⁻μ⁾／s, 2));
    }
    /**
     * Cumulative distribution function
     * From -∞ to x (lower CDF)
     *
     *                      1
     * f(x; μ, s) = -------------------
     *                      /  x - μ \
     *              1 + exp| - -----  |
     *                      \    s   /
     *
     * @param float $x
     *
     * @return float
     */
    public function cdf(float $x): float
    {
        Support::checkLimits(self::SUPPORT_LIMITS, ['x' => $x]);

        $μ = $this->μ;
        $s = $this->s;

        $ℯ＾⁻⁽x⁻μ⁾／s = \exp(-($x - $μ) / $s);
        return 1 / (1 + $ℯ＾⁻⁽x⁻μ⁾／s);
    }

    /**
     * Inverse CDF (quantile function)
     *
     *                     /   p   \
     * Q(p;μ,s) = μ + s ln|  -----  |
     *                     \ 1 - p /
     *
     * @param float $p
     *
     * @return float
     */
    public function inverse(float $p): float
    {
        Support::checkLimits(['p' => '[0,1]'], ['p' => $p]);
        $μ = $this->μ;
        $s = $this->s;

        if ($p == 1) {
            return \INF;
        }

        return $μ + $s * \log($p / (1 - $p));
    }

    /**
     * Mean of the distribution
     *
     * μ = μ
     *
     * @return float μ
     */
    public function mean(): float
    {
        return $this->μ;
    }

    /**
     * Median of the distribution
     *
     * median = μ
     *
     * @return float μ
     */
    public function median(): float
    {
        return $this->μ;
    }

    /**
     * Mode of the distribution
     *
     * mode = μ
     *
     * @return float μ
     */
    public function mode(): float
    {
        return $this->μ;
    }

    /**
     * Variance of the distribution
     *
     *          s²π²
     * var[X] = ----
     *           3
     *
     * @return float
     */
    public function variance(): float
    {
        $s² = $this->s ** 2;
        $π² = \M_PI ** 2;

        return ($s² * $π²) / 3;
    }
}
