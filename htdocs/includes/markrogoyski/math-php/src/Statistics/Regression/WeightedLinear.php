<?php

namespace MathPHP\Statistics\Regression;

use MathPHP\Exception;

/**
 * Weighted linear regression - least squares method
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
 */
class WeightedLinear extends ParametricRegression
{
    use Models\LinearModel;
    use Methods\WeightedLeastSquares;

    /**
     * Array of weights
     * @var array
     */
    private $ws;

    /**
     * @param array $points
     * @param array $ws     Weights
     */
    public function __construct(array $points, array $ws)
    {
        $this->ws = $ws;
        parent::__construct($points);
    }

    /**
     * Calculates the regression parameters.
     *
     * @throws Exception\MatrixException
     * @throws Exception\IncorrectTypeException
     * @throws Exception\MathException
     */
    public function calculate(): void
    {
        $this->parameters = $this->leastSquares($this->ys, $this->xs, $this->ws)->getColumn(0);
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
