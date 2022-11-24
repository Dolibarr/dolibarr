<?php

namespace MathPHP\Probability\Distribution\Continuous;

abstract class Continuous extends \MathPHP\Probability\Distribution\Distribution implements ContinuousDistribution
{
    protected const GUESS_THRESHOLD = 10;
    protected const GUESS_ALLOWANCE = 8;

    /**
     * The Inverse CDF of the distribution
     *
     * For example, if the calling class CDF definition is CDF($x, $d1, $d2)
     * than the inverse is called as inverse($target, $d1, $d2)
     *
     * @param float $target   The area for which we are trying to find the $x
     *
     * @todo check the parameter ranges.
     * @return number
     */
    public function inverse(float $target)
    {
        $initial = $this->mean();
        if (\is_nan($initial)) {
            $initial = $this->median();
        }

        $tolerance     = .0000000001;
        $dif           = $tolerance + 1;
        $guess         = $initial;
        $guess_history = [];

        while ($dif > $tolerance) {
            $y = $this->cdf($guess);

            // Since the CDF is the integral of the PDF, the PDF is the derivative of the CDF
            $slope = $this->pdf($guess);
            $del_y = $target - $y;
            $guess = $del_y / $slope + $guess;

            // Handle edge case of guesses flipping between two or more small numbers
            $guess_history["$guess"] = isset($guess_history["$guess"])
                ? $guess_history["$guess"] + 1
                : 0;
            if ($guess_history["$guess"] > self::GUESS_THRESHOLD) {
                $repeated_guesses = \array_filter(
                    $guess_history,
                    function ($repeated_guess) {
                        return $repeated_guess > self::GUESS_ALLOWANCE;
                    }
                );
                return \array_sum(\array_keys($repeated_guesses)) / \count($repeated_guesses);
            }

            $dif = \abs($del_y);
        }
        return $guess;
    }

    /**
     * CDF between - probability of being between two points, x₁ and x₂
     * The area under a continuous distribution, that lies between two specified points.
     *
     * P(between) = CDF($x₂) - CDF($x₁)
     *
     * @param float $x₁ Lower bound
     * @param float $x₂ Upper bound
     *
     * @return float
     */
    public function between(float $x₁, float $x₂): float
    {
        $upper_area = $this->cdf($x₂);
        $lower_area = $this->cdf($x₁);
        return $upper_area - $lower_area;
    }

    /**
     * CDF outside - Probability of being below x₁ and above x₂.
     * The area under a continuous distribution, that lies above and below two points.
     *
     * P(outside) = 1 - P(between) = 1 - (CDF($x₂) - CDF($x₁))
     *
     * @param float $x₁ Lower bound
     * @param float $x₂ Upper bound
     *
     * @return float
     */
    public function outside(float $x₁, float $x₂): float
    {
        return 1 - $this->between($x₁, $x₂);
    }

    /**
     * CDF above - Probability of being above x to ∞
     * Area under a continuous distribution, that lies above a specified point.
     *
     * P(above) = 1 - CDF(x)
     *
     * @param float $x
     *
     * @return float
     */
    public function above(float $x): float
    {
        return 1 - $this->cdf($x);
    }

    /**
     * Produce a random number with a particular distribution
     *
     * @return number
     *
     * @throws \Exception
     */
    public function rand()
    {
        return $this->inverse(\random_int(0, \PHP_INT_MAX) / \PHP_INT_MAX);
    }

    abstract public function median();
}
