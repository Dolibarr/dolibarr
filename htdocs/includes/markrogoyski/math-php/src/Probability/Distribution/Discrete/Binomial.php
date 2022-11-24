<?php

namespace MathPHP\Probability\Distribution\Discrete;

use MathPHP\Arithmetic;
use MathPHP\Probability\Combinatorics;
use MathPHP\Functions\Support;

/**
 * Binomial distribution - probability mass function
 *
 * https://en.wikipedia.org/wiki/Binomial_distribution
 */
class Binomial extends Discrete
{
    /**
     * Distribution parameter bounds limits
     * n ∈ [0,∞)
     * p ∈ [0,1]
     * @var array
     */
    public const PARAMETER_LIMITS = [
        'n' => '[0,∞)',
        'p' => '[0,1]',
    ];

    /**
     * Distribution support bounds limits
     * r ∈ [0,∞)
     * @var array
     */
    public const SUPPORT_LIMITS = [
        'r' => '[0,∞)',
    ];

    /** @var int number of events */
    protected $n;

    /** @var float probability of success */
    protected $p;

    /**
     * Constructor
     *
     * @param int   $n number of events n >= 0
     * @param float $p probability of success 0 <= p <= 1
     */
    public function __construct(int $n, float $p)
    {
        parent::__construct($n, $p);
    }

    /**
     * Probability mass function
     *
     * P(X = r) = nCr pʳ (1 - p)ⁿ⁻ʳ
     *
     * If n is large, combinatorial factorial blows up,
     * so use the multiplication method instead.
     *
     * @param  int $r number of successful events
     *
     * @return float
     */
    public function pmf(int $r): float
    {
        Support::checkLimits(self::SUPPORT_LIMITS, ['r' => $r]);

        return $this->n < 150
            ? $this->combinatorialMethod($r)
            : $this->multiplicationMethod($r, $this->n, $this->p);
    }

    /**
     * PMF combinatorial method
     *
     * P(X = r) = nCr pʳ (1 - p)ⁿ⁻ʳ
     *
     * @param int $r number of successful events
     *
     * @return float
     *
     * @throws \MathPHP\Exception\OutOfBoundsException
     */
    private function combinatorialMethod(int $r): float
    {
        $n = $this->n;
        $p = $this->p;
        $nCr       = Combinatorics::combinations($n, $r);
        $pʳ        = \pow($p, $r);
        $⟮1 − p⟯ⁿ⁻ʳ = \pow(1 - $p, $n - $r);

        return $nCr * $pʳ * $⟮1 − p⟯ⁿ⁻ʳ;
    }

    /**
     * PMF multiplication method
     *
     * Evaluate binomial probabilities using a method that avoids unnecessary overflow and underflow
     * Catherine Loader: http://octave.1599824.n4.nabble.com/attachment/3829107/0/loader2000Fast.pdf
     *
     *               x             x   n-x
     *              __  n - x + i __   __
     * p(x; n, p) = ||  --------- || p ||  (1 - p)
     *              ⁱ⁼¹     i     ⁱ⁼¹  ⁱ⁼¹
     *
     * @param int   $r number of successful events
     * @param int   $n number of events
     * @param float $p probability of success
     *
     * @return float
     */
    private function multiplicationMethod(int $r, int $n, float $p): float
    {
        if (2 * $r > $n) {
            return $this->multiplicationMethod($n - $r, $n, 1 - $p);
        }

        [$j₀, $j₁, $j₂] = [0, 0, 0];
        $f = 1;

        while (($j₀ < $r) | ($j₁ < $r) | ($j₂ < $n - $r)) {
            if (($j₀ < $r) && ($f < 1)) {
                $j₀++;
                $f *= ($n - $r + $j₀) / $j₀;
            } elseif ($j₁ < $r) {
                $j₁++;
                $f *= $p;
            } else {
                $j₂++;
                $f *= 1 - $p;
            }
        }

        return $f;
    }

    /**
     * Cumulative distribution function
     * Computes and sums the binomial distribution at each of the values in r.
     *
     * @param  int $r number of successful events
     *
     * @return float
     */
    public function cdf(int $r): float
    {
        Support::checkLimits(self::SUPPORT_LIMITS, ['r' => $r]);

        $cdf = 0;
        for ($i = $r; $i >= 0; $i--) {
            $cdf += $this->pmf($i);
        }
        return $cdf;
    }

    /**
     * Mean of the distribution
     *
     * μ = np
     *
     * @return float
     */
    public function mean(): float
    {
        return $this->n * $this->p;
    }

    /**
     * Variance of the distribution
     *
     * σ² = np(1 - p)
     *
     * @return float
     */
    public function variance(): float
    {
        $n = $this->n;
        $p = $this->p;

        return $n * $p * (1 - $p);
    }
}
