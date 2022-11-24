<?php

namespace MathPHP\Probability\Distribution\Discrete;

use MathPHP\Exception;

/**
 * Discrete uniform distribution
 * https://en.wikipedia.org/wiki/Discrete_uniform_distribution
 */
class Uniform extends Discrete
{
    /**
     * Distribution parameter bounds limits
     * a ∈ (-∞,∞)
     * b ∈ (-∞,∞)  b > a
     * @var array
     */
    public const PARAMETER_LIMITS = [
        'a' => '(-∞,∞)',
        'b' => '(-∞,∞)',
    ];

    /**
     * Distribution support bounds limits
     * k ∈ (-∞,∞)
     * @var array
     */
    public const SUPPORT_LIMITS = [
        'k' => '(-∞,∞)',
    ];

    /** @var int number of events */
    protected $a;

    /** @var float probability of success */
    protected $b;

    /**
     * Constructor
     *
     * @param int $a lower boundary of the distribution
     * @param int $b upper boundary of the distribution
     *
     * @throws Exception\BadDataException if b is ≤ a
     */
    public function __construct(int $a, int $b)
    {
        if ($b <= $a) {
            throw new Exception\BadDataException("b must be > a (b:$b a:$a)");
        }

        parent::__construct($a, $b);
    }

    /**
     * Probability mass function
     *
     *       1
     * pmf = -
     *       n
     *
     * Percentile n = b - a + 1
     *
     * @return float
     */
    public function pmf(): float
    {
        $a = $this->a;
        $b = $this->b;

        $n = $b - $a + 1;

        return 1 / $n;
    }

    /**
     * Cumulative distribution function
     *
     *       k - a + 1
     * pmf = ---------
     *           n
     *
     * Percentile n = b - a + 1
     *
     * @param int $k percentile
     *
     * @return float
     */
    public function cdf(int $k): float
    {
        $a = $this->a;
        $b = $this->b;

        if ($k < $a) {
            return 0;
        }
        if ($k > $b) {
            return 1;
        }

        $n = $b - $a + 1;

        return ($k - $a + 1) / $n;
    }

    /**
     * Mean of the distribution
     *
     *     a + b
     * μ = -----
     *       2
     *
     * @return float
     */
    public function mean(): float
    {
        $a = $this->a;
        $b = $this->b;

        return ($a + $b) / 2;
    }

    /**
     * Median of the distribution
     *
     *     a + b
     * μ = -----
     *       2
     *
     * @return float
     */
    public function median(): float
    {
        $a = $this->a;
        $b = $this->b;

        return ($a + $b) / 2;
    }

    /**
     * Variance of the distribution
     *
     *      (b - a + 1)² - 1
     * σ² = ----------------
     *             12
     *
     * @return float
     */
    public function variance(): float
    {
        $a = $this->a;
        $b = $this->b;

        return (($b - $a + 1) ** 2 - 1) / 12;
    }
}
