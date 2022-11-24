<?php

namespace MathPHP\Probability\Distribution\Continuous;

use MathPHP\Functions\Support;

/**
 * Cauchy distribution
 * https://en.wikipedia.org/wiki/Cauchy_distribution
 */
class Cauchy extends Continuous
{
    /**
     * Distribution parameter bounds limits
     * x₀ ∈ (-∞,∞)
     * γ  ∈ (0,∞)
     * @var array
     */
    public const PARAMETER_LIMITS = [
        'x₀' => '(-∞,∞)',
        'γ'  => '(0,∞)',
    ];

    /**
     * Distribution support bounds limits
     * x  ∈ (-∞,∞)
     * @var array
     */
    public const SUPPORT_LIMITS = [
        'x'  => '(-∞,∞)',
    ];

    /** @var number Location Parameter */
    protected $x₀;

    /** @var number Scale Parameter */
    protected $γ;

    /**
     * Constructor
     *
     * @param float $x₀ location parameter
     * @param float $γ  scale parameter γ > 0
     */
    public function __construct(float $x₀, float $γ)
    {
        parent::__construct($x₀, $γ);
    }

    /**
     * Probability density function
     *
     *                1
     *    --------------------------
     *       ┌        / x - x₀ \ ² ┐
     *    πγ | 1  +  | ---------|  |
     *       └        \    γ   /   ┘
     *
     * @param float $x
     *
     * @return float
     */
    public function pdf(float $x): float
    {
        Support::checkLimits(self::SUPPORT_LIMITS, ['x' => $x]);

        $x₀ = $this->x₀;
        $γ  = $this->γ;
        $π  = \M_PI;

        return 1 / ($π * $γ * (1 + (($x - $x₀) / $γ) ** 2));
    }

    /**
     * Cumulative distribution function
     * Calculate the cumulative value value up to a point, left tail.
     *
     * @param float $x
     *
     * @return float
     */
    public function cdf(float $x): float
    {
        Support::checkLimits(self::SUPPORT_LIMITS, ['x' => $x]);

        $x₀ = $this->x₀;
        $γ  = $this->γ;
        $π  = \M_PI;

        return 1 / $π * \atan(($x - $x₀) / $γ) + .5;
    }

    /**
     * Inverse CDF (Quantile function)
     *
     * Q(p;x₀,γ) = x₀ + γ tan[π(p - ½)]
     *
     * @param float $p
     *
     * @return float
     */
    public function inverse(float $p): float
    {
        Support::checkLimits(['p' => '[0,1]'], ['p' => $p]);

        $x₀ = $this->x₀;
        $γ  = $this->γ;

        $π = \M_PI;

        return $x₀ + $γ * \tan($π * ($p - .5));
    }

    /**
     * Mean of the distribution (undefined)
     *
     * μ is undefined
     *
     * @return float \NAN
     */
    public function mean(): float
    {
        return \NAN;
    }

    /**
     * Median of the distribution
     *
     * @return float x₀
     */
    public function median(): float
    {
        return $this->x₀;
    }

    /**
     * Mode of the distribution
     *
     * @return float x₀
     */
    public function mode(): float
    {
        return $this->x₀;
    }

    /**
     * Variance of the distribution (undefined)
     *
     * @return float
     */
    public function variance(): float
    {
        return \NAN;
    }
}
