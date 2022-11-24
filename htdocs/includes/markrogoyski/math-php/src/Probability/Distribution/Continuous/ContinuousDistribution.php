<?php

namespace MathPHP\Probability\Distribution\Continuous;

/**
 * Interface ContinuousDistribution
 */
interface ContinuousDistribution
{
    /**
     * Probability density function
     *
     * @param float $x
     *
     * @return mixed
     */
    public function pdf(float $x);

    /**
     * Cumulative distribution function
     *
     * @param float $x
     *
     * @return mixed
     */
    public function cdf(float $x);

    /**
     * Mean average
     *
     * @return mixed
     */
    public function mean();
}
