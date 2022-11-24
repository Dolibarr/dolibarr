<?php

namespace MathPHP\Statistics\Regression;

use MathPHP\Exception;

/**
 * Simple linear regression - least squares method
 *
 * A model with a single explanatory variable.
 * Fits a straight line through the set of n points in such a way that makes
 * the sum of squared residuals of the model (that is, vertical distances
 * between the points of the data set and the fitted line) as small as possible.
 * https://en.wikipedia.org/wiki/Simple_linear_regression
 *
 * Having data points {(xᵢ, yᵢ), i = 1 ..., n }
 * Find the equation y = mx + b
 *
 *      _ _   __
 *      x y - xy
 * m = _________
 *      _     __
 *     (x)² - x²
 *
 *     _    _
 * b = y - mx
 */
class Linear extends ParametricRegression
{
    use Methods\LeastSquares;
    use Models\LinearModel;

    /**
     * Average of x
     * @var number
     */
    private $xbar;

    /**
     * Average of y
     * @var number
     */
    private $ybar;

    /**
     * Sum of squared deviations of x
     * @var number
     */
    private $SSx;

    /**
     * Sum of squares residuals
     * @var number
     */
    private $SSres;

    /**
     * Calculates the regression parameters.
     *
     * @throws Exception\BadDataException
     * @throws Exception\IncorrectTypeException
     * @throws Exception\MatrixException
     * @throws Exception\MathException
     */
    public function calculate(): void
    {
        $this->parameters = $this->leastSquares($this->ys, $this->xs)->getColumn(0);
    }

    /**
     * Evaluate the regression equation at x
     * Uses the instance model's evaluateModel method.
     *
     * @param  float $x
     *
     * @return float
     */
    public function evaluate(float $x): float
    {
        return $this->evaluateModel($x, $this->parameters);
    }
}
