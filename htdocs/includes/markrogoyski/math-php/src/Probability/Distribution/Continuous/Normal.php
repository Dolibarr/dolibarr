<?php

namespace MathPHP\Probability\Distribution\Continuous;

use MathPHP\Functions\Special;
use MathPHP\Functions\Support;

/**
 * Normal distribution
 * https://en.wikipedia.org/wiki/Normal_distribution
 */
class Normal extends Continuous
{
    /**
     * Distribution parameter bounds limits
     * μ ∈ (-∞,∞)
     * σ ∈ (0,∞)
     * @var array
     */
    public const PARAMETER_LIMITS = [
        'μ' => '(-∞,∞)',
        'σ' => '(0,∞)',
    ];

    /**
     * Distribution support bounds limits
     * x ∈ (-∞,∞)
     * @var array
     */
    public const SUPPORT_LIMITS = [
        'x' => '(-∞,∞)',
    ];

    /** @var float Mean Parameter */
    protected $μ;

    /** @var float Standard Deviation Parameter */
    protected $σ;

    /**
     * Normal constructor
     *
     * @param float $μ
     * @param float $σ
     */
    public function __construct(float $μ, float $σ)
    {
        parent::__construct($μ, $σ);
    }

    /**
     * Probability density function
     *
     *              1
     * f(x|μ,σ) = ----- ℯ^−⟮x − μ⟯²∕2σ²
     *            σ√⟮2π⟯
     *
     * @param float $x random variable
     *
     * @return float f(x|μ,σ)
     */
    public function pdf(float $x): float
    {
        Support::checkLimits(self::SUPPORT_LIMITS, ['x' => $x]);

        $μ     = $this->μ;
        $σ     = $this->σ;
        $π     = \M_PI;
        $σ√⟮2π⟯ = $σ * \sqrt(2 * $π);

        $⟮x − μ⟯²∕2σ² = \pow(($x - $μ), 2) / (2 * $σ ** 2);

        $ℯ＾−⟮x − μ⟯²∕2σ² = \exp(-$⟮x − μ⟯²∕2σ²);

        return ( 1 / $σ√⟮2π⟯ ) * $ℯ＾−⟮x − μ⟯²∕2σ²;
    }

    /**
     * Cumulative distribution function
     * Probability of being below X.
     * Area under the normal distribution from -∞ to X.
     *             _                  _
     *          1 |         / x - μ \  |
     * cdf(x) = - | 1 + erf|  ----- |  |
     *          2 |_        \  σ√2  / _|
     *
     * @param float $x upper bound
     *
     * @return float cdf(x) below
     */
    public function cdf(float $x): float
    {
        Support::checkLimits(self::SUPPORT_LIMITS, ['x' => $x]);

        $μ = $this->μ;
        $σ = $this->σ;

        return 1 / 2 * ( 1 + Special::erf(($x - $μ) / ($σ * \sqrt(2))) );
    }

    /**
     * Inverse CDF (quantile)
     *
     * @param float $p
     *
     * @return float
     */
    public function inverse(float $p): float
    {
        if ($p == 0) {
            return -\INF;
        }
        if ($p == 1) {
            return \INF;
        }

        return parent::inverse($p);
    }

    /**
     * Mean of the distribution
     *
     * μ = μ
     *
     * @return float
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
     * @return float
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
     * @return float
     */
    public function mode(): float
    {
        return $this->μ;
    }

    /**
     * Variance of the distribution
     *
     * var[X] = σ²
     *
     * @return float
     */
    public function variance(): float
    {
        return $this->σ ** 2;
    }

    /**
     * Random number - Box–Muller transform
     *
     * https://en.wikipedia.org/wiki/Box%E2%80%93Muller_transform
     */
    public function rand()
    {
        $rand1 = \random_int(0, \PHP_INT_MAX) / \PHP_INT_MAX;
        $rand2 = \random_int(0, \PHP_INT_MAX) / \PHP_INT_MAX;
        return \sqrt(-2 * \log($rand1)) * \cos(2 * pi() * $rand2) * $this->σ + $this->μ;
    }
}
