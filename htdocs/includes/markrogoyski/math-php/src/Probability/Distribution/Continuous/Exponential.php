<?php

namespace MathPHP\Probability\Distribution\Continuous;

use MathPHP\Exception\OutOfBoundsException;

/**
 * Exponential distribution
 * https://en.wikipedia.org/wiki/Exponential_distribution
 */
class Exponential extends Continuous
{
    /**
     * Distribution parameter bounds limits
     * λ ∈ (0,∞)
     * @var array
     */
    public const PARAMETER_LIMITS = [
        'λ' => '(0,∞)',
    ];

    /**
     * Distribution support bounds limits
     * x ∈ [0,∞)
     * @var array
     */
    public const SUPPORT_LIMITS = [
        'x' => '[0,∞)',
    ];

     /** @var float rate parameter */
    protected $λ;

    /**
     * Constructor
     *
     * @param float $λ often called the rate parameter
     */
    public function __construct(float $λ)
    {
        parent::__construct($λ);
    }

    /**
     * Probability density function
     *
     * f(x;λ) = λℯ^⁻λx  x ≥ 0
     *        = 0       x < 0
     *
     * @param float $x the random variable
     *
     * @return float
     */
    public function pdf(float $x): float
    {
        if ($x < 0) {
            return 0;
        }

        $λ = $this->λ;

        return $λ * \exp(-$λ * $x);
    }
    /**
     * Cumulative distribution function
     *
     * f(x;λ) = 1 − ℯ^⁻λx  x ≥ 0
     *        = 0          x < 0
     *
     * @param float $x the random variable
     *
     * @return float
     */
    public function cdf(float $x): float
    {
        if ($x < 0) {
            return 0;
        }

        $λ = $this->λ;

        return 1 - \exp(-$λ * $x);
    }

    /**
     * Inverse cumulative distribution function (quantile function)
     *
     *            −ln(1 − p)
     * F⁻¹(p;λ) = ----------    0 ≤ p < 1
     *                λ
     *
     * @param float $p
     *
     * @return float
     *
     * @throws OutOfBoundsException
     */
    public function inverse(float $p): float
    {
        if ($p < 0 || $p > 1) {
            throw new OutOfBoundsException("p must be between 0 and 1; given a p of $p");
        }
        if ($p == 1) {
            return \INF;
        }

        return -\log(1 - $p) / $this->λ;
    }

    /**
     * Mean of the distribution
     *
     * μ = λ⁻¹
     *
     * @return float
     */
    public function mean(): float
    {
        return 1 / $this->λ;
    }

    /**
     * Median of the distribution
     *
     *          ln(2)
     * median = -----
     *            λ
     *
     * @return float
     */
    public function median(): float
    {
        return \log(2) / $this->λ;
    }

    /**
     * Mode of the distribution
     *
     * mode = 0
     *
     * @return float
     */
    public function mode(): float
    {
        return 0;
    }

    /**
     * Variance of the distribution
     *
     *           1
     * var[X] = --
     *          λ²
     *
     * @return float
     */
    public function variance(): float
    {
        return 1 / ($this->λ ** 2);
    }
}
