<?php

namespace MathPHP\Probability\Distribution\Continuous;

use MathPHP\Functions\Support;

/**
 * Log-logistic distribution
 * Also known as the Fisk distribution.
 * https://en.wikipedia.org/wiki/Log-logistic_distribution
 */
class LogLogistic extends Continuous
{
    /**
     * Distribution parameter bounds limits
     * α ∈ (0,∞)
     * β ∈ (0,∞)
     * @var array
     */
    public const PARAMETER_LIMITS = [
        'α' => '(0,∞)',
        'β' => '(0,∞)',
    ];

    /**
     * Distribution support bounds limits
     * x ∈ [0,∞)
     * @var array
     */
    public const SUPPORT_LIMITS = [
        'x' => '[0,∞)',
    ];

     /** @var float Scale Parameter */
    protected $α;

    /** @var float Shape Parameter */
    protected $β;

    /**
     * Constructor
     *
     * @param float $α scale parameter α > 0
     * @param float $β shape parameter β > 0
     */
    public function __construct(float $α, float $β)
    {
        parent::__construct($α, $β);
    }

    /**
     * Probability density function
     *
     *              (β/α)(x/α)ᵝ⁻¹
     * f(x; α, β) = -------------
     *              (1 + (x/α)ᵝ)²
     *
     * @param float $x (x > 0)
     *
     * @return float
     */

    public function pdf(float $x): float
    {
        Support::checkLimits(self::SUPPORT_LIMITS, ['x' => $x]);

        $α = $this->α;
        $β = $this->β;

        $⟮β／α⟯⟮x／α⟯ᵝ⁻¹  = ($β / $α) * \pow($x / $α, $β - 1);
        $⟮1 ＋ ⟮x／α⟯ᵝ⟯² = \pow(1 + ($x / $α) ** $β, 2);
        return $⟮β／α⟯⟮x／α⟯ᵝ⁻¹ / $⟮1 ＋ ⟮x／α⟯ᵝ⟯²;
    }

    /**
     * Cumulative distribution function
     *
     *                   1
     * F(x; α, β) = -----------
     *              1 + (x/α)⁻ᵝ
     *
     * @param float $x (x > 0)
     *
     * @return float
     */
    public function cdf(float $x): float
    {
        Support::checkLimits(self::SUPPORT_LIMITS, ['x' => $x]);

        $α = $this->α;
        $β = $this->β;

        $⟮x／α⟯⁻ᵝ = \pow($x / $α, -$β);
        return 1 / (1 + $⟮x／α⟯⁻ᵝ);
    }

    /**
     * Inverse CDF (Quantile function)
     *
     *                 /   p   \ 1/β
     * F⁻¹(p;α,β) = α |  -----  |
     *                 \ 1 - p /
     *
     * @param float $p
     *
     * @return float
     */
    public function inverse(float $p): float
    {
        Support::checkLimits(['p' => '[0,1]'], ['p' => $p]);

        $α = $this->α;
        $β = $this->β;

        return $α * ($p / (1 - $p)) ** (1 / $β);
    }

    /**
     * Mean of the distribution
     *
     *      απ / β
     * μ = --------  if β > 1, else undefined
     *     sin(π/β)
     *
     * @return float
     */
    public function mean(): float
    {
        $α = $this->α;
        $β = $this->β;
        $π = \M_PI;

        if ($β > 1) {
            return (($α * $π) / $β) / \sin($π / $β);
        }

        return \NAN;
    }

    /**
     * Median of the distribution
     *
     * median = α
     *
     * @return float
     */
    public function median(): float
    {
        return $this->α;
    }

    /**
     * Mode of the distribution
     *
     * mode = 0                 β ≤ 1
     *
     *           / β - 1 \ 1/β
     * mode = α |  -----  |     β > 1
     *           \ β + 1 /
     *
     * @return float
     */
    public function mode(): float
    {
        $α = $this->α;
        $β = $this->β;

        if ($β <= 1) {
            return 0;
        }

        return $α * \pow(($β - 1) / ($β + 1), 1 / $β);
    }

    /**
     * Variance of the distribution
     *
     *              /   2β       β²  \
     * var[X] = α² |  ------ - -----  |    β > 2
     *              \ sin 2β   sin²β  /
     *
     * @return float
     */
    public function variance(): float
    {
        $α = $this->α;
        $β = $this->β;

        if ($β <= 2) {
            return \NAN;
        }

        $α²    = $α ** 2;
        $β²    = $β ** 2;
        $２β   = 2 * $β;
        $sin2β = \sin($２β);
        $sin²β = \sin($β) ** 2;

        return $α² * (($２β / $sin2β) - ($β² / $sin²β));
    }
}
