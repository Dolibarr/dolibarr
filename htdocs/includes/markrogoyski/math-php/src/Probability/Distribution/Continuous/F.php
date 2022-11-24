<?php

namespace MathPHP\Probability\Distribution\Continuous;

use MathPHP\Functions\Special;
use MathPHP\Functions\Support;

/**
 * F-distribution
 * https://en.wikipedia.org/wiki/F-distribution
 */
class F extends Continuous
{
    /**
     * Distribution parameter bounds limits
     * d₁ ∈ (0,∞)
     * d₂ ∈ (0,∞)
     * @var array
     */
    public const PARAMETER_LIMITS = [
        'd₁' => '(0,∞)',
        'd₂' => '(0,∞)',
    ];

    /**
     * Distribution Support bounds limits
     * x  ∈ [0,∞)
     * @var array
     */
    public const SUPPORT_LIMITS = [
        'x'  => '[0,∞)',
    ];

    /** @var float Degree of Freedom Parameter */
    protected $d₁;

    /** @var float Degree of Freedom Parameter */
    protected $d₂;

    /**
     * Constructor
     *
     * @param float $d₁ degree of freedom parameter d₁ > 0
     * @param float $d₂ degree of freedom parameter d₂ > 0
     */
    public function __construct(float $d₁, float $d₂)
    {
        parent::__construct($d₁, $d₂);
    }

    /**
     * Probability density function
     *
     *      __________________
     *     / (d₁ x)ᵈ¹ d₂ᵈ²
     *    /  ----------------
     *   √   (d₁ x + d₂)ᵈ¹⁺ᵈ²
     *   ---------------------
     *           / d₁  d₂ \
     *      x B |  --, --  |
     *           \ 2   2  /
     *
     * @param float $x  percentile ≥ 0
     *
     * @todo how to handle x = 0
     *
     * @return float probability
     */
    public function pdf(float $x): float
    {
        Support::checkLimits(self::SUPPORT_LIMITS, ['x' => $x]);

        $d₁ = $this->d₁;
        $d₂ = $this->d₂;

        // Numerator
        $⟮d₁x⟯ᵈ¹d₂ᵈ²                = ($d₁ * $x) ** $d₁ * $d₂ ** $d₂;
        $⟮d₁x＋d₂⟯ᵈ¹⁺ᵈ²             = ($d₁ * $x + $d₂) ** ($d₁ + $d₂);
        $√⟮d₁x⟯ᵈ¹d₂ᵈ²／⟮d₁x＋d₂⟯ᵈ¹⁺ᵈ² = \sqrt($⟮d₁x⟯ᵈ¹d₂ᵈ² / $⟮d₁x＋d₂⟯ᵈ¹⁺ᵈ²);

        // Denominator
        $xB⟮d₁／2、d₂／2⟯ = $x * Special::beta($d₁ / 2, $d₂ / 2);

        return $√⟮d₁x⟯ᵈ¹d₂ᵈ²／⟮d₁x＋d₂⟯ᵈ¹⁺ᵈ² / $xB⟮d₁／2、d₂／2⟯;
    }

    /**
     * Cumulative distribution function
     *
     *          / d₁  d₂ \
     *  I      |  --, --  |
     *   ᵈ¹ˣ    \ 2   2  /
     *   ------
     *   ᵈ¹ˣ⁺ᵈ²
     *
     * Where I is the regularized incomplete beta function.
     *
     * @param float $x  percentile ≥ 0
     *
     * @return float
     */
    public function cdf(float $x): float
    {
        Support::checkLimits(self::SUPPORT_LIMITS, ['x' => $x]);

        $d₁ = $this->d₁;
        $d₂ = $this->d₂;

        $ᵈ¹ˣ／d₁x＋d₂ = ($d₁ * $x) / ($d₁ * $x + $d₂);

        return Special::regularizedIncompleteBeta($ᵈ¹ˣ／d₁x＋d₂, $d₁ / 2, $d₂ / 2);
    }

    /**
     * Mean of the distribution
     *
     *       d₂
     * μ = ------  for d₂ > 2
     *     d₂ - 2
     *
     * @return float
     */
    public function mean(): float
    {
        $d₂ = $this->d₂;

        if ($d₂ > 2) {
            return $d₂ / ($d₂ - 2);
        }

        return \NAN;
    }

    /**
     * Mode of the distribution
     *
     *        d₁ - 2   d₂
     * mode = ------ ------     d₁ > 2
     *          d₁   d₂ + 2
     *
     * @return float
     */
    public function mode(): float
    {
        $d₁ = $this->d₁;
        $d₂ = $this->d₂;

        if ($d₁ <= 2) {
            return \NAN;
        }

        return (($d₁ - 2) / $d₁) * ($d₂ / ($d₂ + 2));
    }

    /**
     * Variance of the distribution
     *
     *          2d₂²(d₁ + d₂ - 2)
     * var[X] = -------------------   d₂ > 4
     *          d₁(d₂ - 2)²(d₂ - 4)
     *
     * @return float
     */
    public function variance(): float
    {
        $d₁ = $this->d₁;
        $d₂ = $this->d₂;

        if ($d₂ <= 4) {
            return \NAN;
        }

        $２d₂²⟮d₁ ＋ d₂ − 2⟯ = (2 * $d₂ ** 2) * ($d₁ + $d₂ - 2);
        $d₁⟮d₂ − 2⟯²⟮d₂ − 4⟯  = ($d₁ * ($d₂ - 2) ** 2) * ($d₂ - 4);

        return $２d₂²⟮d₁ ＋ d₂ − 2⟯ / $d₁⟮d₂ − 2⟯²⟮d₂ − 4⟯;
    }

    /**
     * Median of the distribution
     * @note: This is probably not correct and should be updated.
     * @todo: Replace with actual median calculation.
     *
     * @return float
     */
    public function median(): float
    {
        return $this->mean();
    }
}
