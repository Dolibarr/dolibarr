<?php

namespace MathPHP\Probability\Distribution\Continuous;

use MathPHP\Functions\Special;
use MathPHP\Functions\Support;

class LogNormal extends Continuous
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
     * x ∈ (0,∞)
     * @var array
     */
    public const SUPPORT_LIMITS = [
        'x' => '(0,∞)',
    ];

     /** @var float location parameter */
    protected $μ;

     /** @var float scale parameter > 0 */
    protected $σ;

    /**
     * Constructor
     *
     * @param float $μ location parameter
     * @param float $λ scale parameter > 0
     */
    public function __construct(float $μ, float $λ)
    {
        parent::__construct($μ, $λ);
    }

    /**
     * Log normal distribution - probability density function
     *
     * https://en.wikipedia.org/wiki/Log-normal_distribution
     *
     *                 (ln x - μ)²
     *         1     - ----------
     * pdf = ----- ℯ       2σ²
     *       xσ√2π
     *
     * @param  float $x > 0
     *
     * @return float
     */
    public function pdf(float $x): float
    {
        Support::checkLimits(self::SUPPORT_LIMITS, ['x' => $x]);

        $μ = $this->μ;
        $σ = $this->σ;
        $π = \M_PI;

        $xσ√2π      = $x * $σ * \sqrt(2 * $π);
        $⟮ln x − μ⟯² = \pow(\log($x) - $μ, 2);
        $σ²         = $σ ** 2;

        return (1 / $xσ√2π) * \exp(-($⟮ln x − μ⟯² / (2 * $σ²)));
    }
    /**
     * Log normal distribution - cumulative distribution function
     *
     * https://en.wikipedia.org/wiki/Log-normal_distribution
     *
     *       1   1      / ln x - μ \
     * cdf = - + - erf |  --------  |
     *       2   2      \   √2σ     /
     *
     * @param  float $x > 0
     *
     * @return float
     */
    public function cdf(float $x): float
    {
        Support::checkLimits(self::SUPPORT_LIMITS, ['x' => $x]);

        $μ = $this->μ;
        $σ = $this->σ;

        $⟮ln x − μ⟯ = \log($x) - $μ;
        $√2σ       = \sqrt(2) * $σ;

        return 1 / 2 + 1 / 2 * Special::erf($⟮ln x − μ⟯ / $√2σ);
    }

    /**
     * Inverse of CDF (quantile)
     *
     * exp(μ + σ * normal-inverse(p))
     *
     * @param float $p
     *
     * @return float
     */
    public function inverse(float $p): float
    {
        if ($p == 0) {
            return 0;
        }
        if ($p == 1) {
            return \INF;
        }

        $μ = $this->μ;
        $σ = $this->σ;
        $standard_normal = new StandardNormal();

        return \exp($μ + $σ * $standard_normal->inverse($p));
    }

    /**
     * Mean of the distribution
     *
     * μ = exp(μ + σ²/2)
     *
     * @return float
     */
    public function mean(): float
    {
        $μ = $this->μ;
        $σ = $this->σ;

        return \exp($μ + ($σ ** 2 / 2));
    }

    /**
     * Median of the distribution
     *
     * median = exp(μ)
     *
     * @return float
     */
    public function median(): float
    {
        return \exp($this->μ);
    }

    /**
     * Mode of the distribution
     *
     * mode = exp(μ - σ²)
     *
     * @return float
     */
    public function mode(): float
    {
        return \exp($this->μ - $this->σ ** 2);
    }

    /**
     * Variance of the distribution
     *
     * var[X] = [exp(σ²) - 1][exp(2μ + σ²)]
     *
     * @return float
     */
    public function variance(): float
    {
        $μ = $this->μ;
        $σ = $this->σ;

        $σ²  = $σ ** 2;
        $２μ = 2 * $μ;

        return (\exp($σ²) - 1) * \exp($２μ + $σ²);
    }
}
