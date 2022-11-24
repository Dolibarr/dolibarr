<?php

namespace MathPHP\Probability\Distribution\Discrete;

use MathPHP\Functions\Support;

/**
 * Shifted geometric distribution
 *
 * The probability distribution of the number X of Bernoulli trials needed
 * to get one success, supported on the set { 1, 2, 3, ...}
 *
 * https://en.wikipedia.org/wiki/Geometric_distribution
 */
class ShiftedGeometric extends Discrete
{
    /**
     * Distribution parameter bounds limits
     * p ∈ (0,1]
     * @var array
     */
    public const PARAMETER_LIMITS = [
        'p' => '(0,1]',
    ];

    /**
     * Distribution support bounds limits
     * k ∈ [1,∞)
     * @var array
     */
    public const SUPPORT_LIMITS = [
        'k' => '[1,∞)',
    ];

    /** @var float success probability */
    protected $p;

    /**
     * Constructor
     *
     * @param  float $p success probability  0 < p ≤ 1
     */
    public function __construct(float $p)
    {
        parent::__construct($p);
    }


    /**
     * Probability mass function
     *
     * k trials where k ∈ {1, 2, 3, ...}
     *
     * pmf = (1 - p)ᵏ⁻¹p
     *
     * @param  int   $k number of trials     k ≥ 1
     *
     * @return float
     */
    public function pmf(int $k): float
    {
        Support::checkLimits(self::SUPPORT_LIMITS, ['k' => $k]);

        $p = $this->p;

        $⟮1 − p⟯ᵏ⁻¹ = \pow(1 - $p, $k - 1);
        return $⟮1 − p⟯ᵏ⁻¹ * $p;
    }

    /**
     * Cumulative distribution function
     *
     * k trials where k ∈ {0, 1, 2, 3, ...}
     *
     * pmf = 1 - (1 - p)ᵏ
     *
     * @param  int   $k number of trials     k ≥ 0
     *
     * @return float
     */
    public function cdf(int $k): float
    {
        Support::checkLimits(self::SUPPORT_LIMITS, ['k' => $k]);

        $p = $this->p;

        $⟮1 − p⟯ᵏ = \pow(1 - $p, $k);
        return 1 - $⟮1 − p⟯ᵏ;
    }

    /**
     * Mean of the distribution
     *
     *     1
     * μ = -
     *     p
     *
     * @return float
     */
    public function mean(): float
    {
        return 1 / $this->p;
    }

    /**
     * Median of the distribution
     *
     *           _           _
     *          |     -1      |
     * median = | ----------- |
     *          | log₂(1 - p) |
     *
     * @return float
     */
    public function median(): float
    {
        $log₂⟮1 − p⟯ = \log(1 - $this->p, 2);

        return \ceil(-1 / $log₂⟮1 − p⟯);
    }

    /**
     * Mode of the distribution
     *
     * mode = 1
     *
     * @return int
     */
    public function mode(): int
    {
        return 1;
    }

    /**
     * Variance of the distribution
     *
     *      1 - p
     * σ² = -----
     *        p²
     *
     * @return float
     */
    public function variance(): float
    {
        return (1 - $this->p) / $this->p ** 2;
    }
}
