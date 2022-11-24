<?php

namespace MathPHP\Probability\Distribution\Multivariate;

use MathPHP\Probability\Combinatorics;
use MathPHP\Exception;

/**
 * Multinomial distribution (multivariate)
 *
 * https://en.wikipedia.org/wiki/Multinomial_distribution
 */
class Multinomial
{
    /** @var array */
    protected $probabilities;

    /**
     * Multinomial constructor
     *
     * @param   array $probabilities
     *
     * @throws Exception\BadDataException if the probabilities do not add up to 1
     */
    public function __construct(array $probabilities)
    {
        // Probabilities must add up to 1
        if (\round(\array_sum($probabilities), 1) != 1) {
            throw new Exception\BadDataException('Probabilities do not add up to 1.');
        }

        $this->probabilities = $probabilities;
    }

    /**
     * Probability mass function
     *
     *          n!
     * pmf = ------- p₁ˣ¹⋯pkˣᵏ
     *       x₁!⋯xk!
     *
     * n = number of trials (sum of the frequencies) = x₁ + x₂ + ⋯ xk
     *
     * @param  array $frequencies
     *
     * @return float
     *
     * @throws Exception\BadDataException if the number of frequencies does not match the number of probabilities
     */
    public function pmf(array $frequencies): float
    {
        // Must have a probability for each frequency
        if (\count($frequencies) !== \count($this->probabilities)) {
            throw new Exception\BadDataException('Number of frequencies does not match number of probabilities.');
        }
        foreach ($frequencies as $frequency) {
            if (!\is_int($frequency)) {
                throw new Exception\BadDataException("Frequencies must be integers. $frequency is not an int.");
            }
        }

        /** @var int $n */
        $n   = \array_sum($frequencies);
        $n！ = Combinatorics::factorial($n);

        $x₁！⋯xk！ = \array_product(\array_map(
            'MathPHP\Probability\Combinatorics::factorial',
            $frequencies
        ));

        $p₁ˣ¹⋯pkˣᵏ = \array_product(\array_map(
            function ($x, $p) {
                return $p ** $x;
            },
            $frequencies,
            $this->probabilities
        ));

        return ($n！ / $x₁！⋯xk！) * $p₁ˣ¹⋯pkˣᵏ;
    }
}
