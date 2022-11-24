<?php

namespace MathPHP\Probability\Distribution\Continuous;

use MathPHP\Functions\Special;
use MathPHP\Functions\Support;

/**
 * χ²-distribution (Chi-squared)
 * https://en.wikipedia.org/wiki/Chi-squared_distribution
 */
class ChiSquared extends Continuous
{
    /**
     * Distribution parameter bounds limits
     * k ∈ [1,∞)
     * @var array
     */
    public const PARAMETER_LIMITS = [
        'k' => '[1,∞)',
    ];

    /**
     * Distribution support bounds limits
     * x ∈ [0,∞)
     * @var array
     */
    public const SUPPORT_LIMITS = [
        'x' => '[0,∞)',
    ];

    /** @var float Degrees of Freedom Parameter */
    protected $k;

    /**
     * Constructor
     *
     * @param float $k degrees of freedom parameter k >= 1
     */
    public function __construct(float $k)
    {
        parent::__construct($k);
    }

    /**
     * Probability density function
     *
     *                 1
     *           -------------- x⁽ᵏ/²⁾⁻¹ ℯ⁻⁽ˣ/²⁾
     *  χ²(k) =          / k \
     *           2ᵏ/² Γ |  -  |
     *                   \ 2 /
     *
     * @param float $x point at which to evaluate > 0
     *
     * @return float probability
     */
    public function pdf(float $x): float
    {
        Support::checkLimits(self::SUPPORT_LIMITS, ['x' => $x]);

        $k = $this->k;

        // Numerator
        $x⁽ᵏ／²⁾⁻¹ = $x ** (($k / 2) - 1);
        $ℯ⁻⁽ˣ／²⁾  = \exp(-($x / 2));

        // Denominator
        $２ᵏ／²  = 2 ** ($k / 2);
        $Γ⟮k／2⟯ = Special::Γ($k / 2);

        return ($x⁽ᵏ／²⁾⁻¹ * $ℯ⁻⁽ˣ／²⁾) / ($２ᵏ／² * $Γ⟮k／2⟯);
    }

    /**
     * Cumulative distribution function
     *
     * Cumulative t value up to a point, left tail.
     *
     *          / k   x  \
     *       γ |  - , -  |
     *          \ 2   2 /
     * CDF = -------------
     *            / k \
     *         Γ |  -  |
     *            \ 2 /
     *
     * @param float $x Chi-square critical value (CV) > 0
     *
     * @return float cumulative probability
     */
    public function cdf(float $x): float
    {
        Support::checkLimits(self::SUPPORT_LIMITS, ['x' => $x]);

        $k = $this->k;

        // Numerator
        $γ⟮k／2、x／2⟯ = Special::γ($k / 2, $x / 2);

        // Denominator
        $Γ⟮k／2⟯ = Special::Γ($k / 2);

        return $γ⟮k／2、x／2⟯ / $Γ⟮k／2⟯;
    }

    /**
     * Mean of the distribution
     *
     * μ = k
     *
     * @return float k
     */
    public function mean(): float
    {
        return $this->k;
    }

    /**
     * Median - closed form approximation
     *
     *             /    2 \³
     * median ≈ k | 1 - -  |
     *             \    k /
     *
     * @return float
     */
    public function median(): float
    {
        $k          = $this->k;
        $⟮1 − 2／9k⟯ = 1 - (2 / (9 * $k));

        return $k * $⟮1 − 2／9k⟯ ** 3;
    }

    /**
     * Mode of the distribution
     *
     * \max(k - 2, 0)
     *
     * @return float
     */
    public function mode(): float
    {
        return \max($this->k - 2, 0);
    }

    /**
     * Variance of the distribution
     *
     * var[X] = 2k
     *
     * @return float
     */
    public function variance(): float
    {
        return 2 * $this->k;
    }
}
