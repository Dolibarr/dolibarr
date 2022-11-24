<?php

namespace MathPHP\Probability\Distribution\Continuous;

use MathPHP\Functions\Support;

class Laplace extends Continuous
{
    /**
     * Distribution parameter bounds limits
     * μ ∈ (-∞,∞)
     * b ∈ (0,∞)
     * @var array
     */
    public const PARAMETER_LIMITS = [
        'μ' => '(-∞,∞)',
        'b' => '(0,∞)',
    ];

    /**
     * Distribution support bounds limits
     * x ∈ (-∞,∞)
     * @var array
     */
    public const SUPPORT_LIMITS = [
        'x' => '(-∞,∞)',
    ];

     /** @var float location parameter */
    protected $μ;

     /** @var float scale parameter */
    protected $b;

    /**
     * Constructor
     *
     * @param float $μ location parameter
     * @param float $b scale parameter (diversity)  b > 0
     */
    public function __construct(float $μ, float $b)
    {
        parent::__construct($μ, $b);
    }

    /**
     * Laplace distribution - probability density function
     *
     * https://en.wikipedia.org/wiki/Laplace_distribution
     *
     *            1      /  |x - μ| \
     * f(x|μ,b) = -- exp| - -------  |
     *            2b     \     b    /
     *
     * @param  float $x
     *
     * @return float
     */
    public function pdf(float $x): float
    {
        Support::checkLimits(self::SUPPORT_LIMITS, ['x' => $x]);

        $μ = $this->μ;
        $b = $this->b;

        return (1 / (2 * $b)) * \exp(-(\abs($x - $μ) / $b));
    }
    /**
     * Laplace distribution - cumulative distribution function
     * From -∞ to x (lower CDF)
     * https://en.wikipedia.org/wiki/Laplace_distribution
     *
     *        1     / x - μ \
     * F(x) = - exp|  ------ |       if x < μ
     *        2     \   b   /
     *
     *            1     /  x - μ \
     * F(x) = 1 - - exp| - ------ |  if x ≥ μ
     *            2     \    b   /
     *
     * @param  float $x
     *
     * @return float
     */
    public function cdf(float $x): float
    {
        Support::checkLimits(self::SUPPORT_LIMITS, ['x' => $x]);

        $μ = $this->μ;
        $b = $this->b;

        if ($x < $μ) {
            return (1 / 2) * \exp(($x - $μ) / $b);
        }
        return 1 - (1 / 2) * \exp(-($x - $μ) / $b);
    }

    /**
     * Inverse cumulative distribution function (quantile function)
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
     * var[X] = 2b²
     *
     * @return float
     */
    public function variance(): float
    {
        return 2 * $this->b ** 2;
    }
}
