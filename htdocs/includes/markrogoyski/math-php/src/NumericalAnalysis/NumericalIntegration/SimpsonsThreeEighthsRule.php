<?php

namespace MathPHP\NumericalAnalysis\NumericalIntegration;

use MathPHP\Exception;
use MathPHP\NumericalAnalysis\Interpolation\LagrangePolynomial;

/**
 * Simpsons 3/8 Rule
 *
 * In numerical analysis, Simpson's 3/8 rule is a technique for approximating
 * the definite integral of a function.
 *
 * Simpson's 3/8 rule belongs to the closed Newton-Cotes formulas, a group of methods
 * for numerical integration which approximate the integral of a function. We
 * can either directly supply a set of inputs and their corresponding outputs for
 * said function, or if we explicitly know the function, we can define it as a
 * callback function and then generate a set of points by evaluating that function
 * at n points between a start and end point. We then use these values to
 * interpolate a Lagrange polynomial. Finally, we integrate the Lagrange
 * polynomial to approximate the integral of our original function.
 *
 * Simpson's 3/8 rule is produced by integrating the third Lagrange polynomial.
 *
 * https://en.wikipedia.org/wiki/Simpson%27s_rule#Simpson.27s_3.2F8_rule
 * http://mathworld.wolfram.com/Simpsons38Rule.html
 * http://www.efunda.com/math/num_integration/num_int_newton.cfm
 */
class SimpsonsThreeEighthsRule extends NumericalIntegration
{
    /**
     * Use Simpson's 3/8 Rule to aproximate the definite integral of a
     * function f(x). Our input can support either a set of arrays, or a callback
     * function with arguments (to produce a set of arrays). Each array in our
     * input contains two numbers which correspond to coordinates (x, y) or
     * equivalently, (x, f(x)), of the function f(x) whose definite integral we
     * are approximating.
     *
     * Note: Simpson's 3/8 rule requires that our number of subintervals is a factor
     * of three (we must supply an n points such that n-1 is a multiple of three)
     * and also that the size of each subinterval is equal (spacing between each
     * point is equal).
     *
     * The bounds of the definite integral to which we are approximating is
     * determined by the our inputs.
     *
     * Example: approximate([0, 10], [2, 5], [4, 7], [6,3]) will approximate the
     * definite integral of the function that produces these coordinates with a
     * lower bound of 0, and an upper bound of 6.
     *
     * Example: approximate(function($x) {return $x**2;}, [0, 3 ,4]) will produce
     * a set of arrays by evaluating the callback at 4 evenly spaced points
     * between 0 and 3. Then, this array will be used in our approximation.
     *
     * Simpson's 3/8 Rule:
     *
     * xn        ⁿ⁻¹ xᵢ₊₁
     * ∫ f(x)dx = ∑   ∫ f(x)dx
     * x₁        ⁱ⁼¹  xᵢ
     *
     *         ⁽ⁿ⁻¹⁾/³ 3h
     *          = ∑    - [f⟮x₂ᵢ₋₁⟯ + 3f⟮x₂ᵢ⟯ + 3f⟮x₂ᵢ₊₁⟯ + f⟮x₂ᵢ₊₂⟯] + O(h⁵f⁗(x))
     *           ⁱ⁼¹   8
     * where h = (xn - x₁) / (n - 1)
     *
     * @param callable|array $source   The source of our approximation. Should be either
     *                                 a callback function or a set of arrays. Each array
     *                                 (point) contains precisely two numbers, an x and y.
     *                                 Example array: [[1,2], [2,3], [3,4], [4,5]].
     *                                 Example callback: function($x) {return $x**2;}
     * @param number          ...$args The arguments of our callback function: start,
     *                                 end, and n. Example: approximate($source, 0, 8, 4).
     *                                 If $source is a set of points, do not input any
     *                                 $args. Example: approximate($source).
     *
     * @return float                   The approximation to the integral of f(x)
     *
     * @throws Exception\BadDataException
     * @throws Exception\IncorrectTypeException
     */
    public static function approximate($source, ...$args): float
    {
        // Get an array of points from our $source argument
        $points = self::getPoints($source, $args);

        // Validate input and sort points
        self::validate($points, $degree = 4);
        Validation::isSubintervalsMultiple($points, $m = 3);
        $sorted = self::sort($points);
        Validation::isSpacingConstant($sorted);

        // Descriptive constants
        $x = self::X;
        $y = self::Y;

        // Initialize
        $n             = \count($sorted);
        $subintervals  = $n - 1;
        $a             = $sorted[0][$x];
        $b             = $sorted[$n - 1][$x];
        $h             = ($b - $a) / $subintervals;
        $approximation = 0;

        /*
         * ⁽ⁿ⁻¹⁾/³ 3h
         *    ∑    -- [f⟮x₂ᵢ₋₁⟯ + 3f⟮x₂ᵢ⟯ + 3f⟮x₂ᵢ₊₁⟯ + f⟮x₂ᵢ₊₂⟯] + O(h⁵f⁗(x))
         *   ⁱ⁼¹   8
         *  where h = (xn - x₁) / (n - 1)
         */
        for ($i = 1; $i < ($subintervals / 3) + 1; $i++) {
            $x₂ᵢ₋₁          = $sorted[(2 * $i) - 2][$x];
            $x₂ᵢ            = $sorted[(2 * $i) - 1][$x];
            $x₂ᵢ₊₁          = $sorted[(2 * $i)][$x];
            $x₂ᵢ₊₂          = $sorted[(2 * $i) + 1][$x];
            $f⟮x₂ᵢ₋₁⟯        = $sorted[(2 * $i) - 2][$y]; // y₂₋₁
            $f⟮x₂ᵢ⟯          = $sorted[(2 * $i) - 1][$y]; // y₂ᵢ
            $f⟮x₂ᵢ₊₁⟯        = $sorted[(2 * $i)][$y];   // y₂ᵢ₊₁
            $f⟮x₂ᵢ₊₂⟯        = $sorted[(2 * $i) + 1][$y]; // y₂ᵢ₊₂
            $lagrange       = LagrangePolynomial::interpolate([[$x₂ᵢ₋₁, $f⟮x₂ᵢ₋₁⟯], [$x₂ᵢ, $f⟮x₂ᵢ⟯], [$x₂ᵢ₊₁, $f⟮x₂ᵢ₊₁⟯], [$x₂ᵢ₊₂, $f⟮x₂ᵢ₊₂⟯]]);
            $integral       = $lagrange->integrate();
            $approximation += $integral($x₂ᵢ₊₂) - $integral($x₂ᵢ₋₁); // definite integral of lagrange polynomial
        }

        return $approximation;
    }
}
