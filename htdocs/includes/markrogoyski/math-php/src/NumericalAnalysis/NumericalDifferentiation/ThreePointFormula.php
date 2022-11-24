<?php

namespace MathPHP\NumericalAnalysis\NumericalDifferentiation;

use MathPHP\Exception;

/**
 * Three Point Formula
 *
 * In numerical analysis, the three point formula is used for approximating
 * the derivative of a function at a point in its domain.
 *
 * We can either directly supply a set of inputs and their corresponding outputs
 * for said function, or if we explicitly know the function, we can define it as a
 * callback function and then generate a set of points by evaluating that function
 * at 3 points between a start and end point.
 */
class ThreePointFormula extends NumericalDifferentiation
{
    /**
     * Use the Three Point Formula to approximate the derivative of a function at
     * our $target. Our input can support either a set of arrays, or a callback
     * function with arguments (to produce a set of arrays). Each array in our
     * input contains two numbers which correspond to coordinates (x, y) or
     * equivalently, (x, f(x)), of the function f(x) whose derivative we are
     * approximating.
     *
     * The Three Point Formula requires we supply 3 points that are evenly spaced
     * apart, and that our target equals the x-components of one of our 3 points.
     *
     * Example: differentiation(2, function($x) {return $x**2;}, 0, 4 ,3) will produce
     * a set of arrays by evaluating the callback at 3 evenly spaced points
     * between 0 and 4. Then, this array will be used in our approximation.
     *
     * Three Point Formula:
     *
     *   - If the 2nd point is our $target, use the Midpoint Formula:
     *
     *              1                     h²
     *     f′(x₀) = - [f(x₀+h)-f(x₀-h)] - - f⁽³⁾(ζ₁)
     *              2h                    6
     *
     *         where ζ₁ lies between x₀ - h and x₀ + h
     *
     *   - If the 1st or 3rd point is our $target, use the Endpoint Formula:
     *   - Note that when the 3rd point is our $target, we use a negative h.
     *
     *              1                               h²
     *     f′(x₀) = - [-3f(x₀)+4f(x₀+h)-f(x₀+2h)] + - f⁽³⁾(ζ₀)
     *              2h                              3
     *
     *         where ζ₀ lies between x₀ and x₀ + 2h
     *
     * @param float          $target  The value at which we are approximating the derivative
     * @param callable|array $source  The source of our approximation. Should be either
     *                                a callback function or a set of arrays. Each array
     *                                (point) contains precisely two numbers, an x and y.
     *                                Example array: [[1,2], [2,3], [3,4]].
     *                                Example callback: function($x) {return $x**2;}
     * @param number         ...$args The arguments of our callback function: start,
     *                                end, and n. Example: differentiate($target, $source, 0, 8, 3).
     *                                If $source is a set of points, do not input any
     *                                $args. Example: approximate($source).
     *
     * @return float                  The approximation of f'($target), i.e. the derivative
     *                                of our input at our target point
     *
     * @throws Exception\BadDataException
     */
    public static function differentiate(float $target, $source, ...$args): float
    {
        // Get an array of points from our $source argument
        $points = self::getPoints($source, $args);

        // Validate input, sort points, make sure spacing is constant, and make
        // sure our target is contained in an interval supplied by our $source
        self::validate($points, $degree = 3);
        $sorted = self::sort($points);
        self::isSpacingConstant($sorted);
        self::isTargetInPoints($target, $sorted);

        // Descriptive constants
        $x = self::X;
        $y = self::Y;

        // Initialize
        $h = ($sorted[2][$x] - $sorted[0][$x]) / 2;

        /*
         * If the 2nd point is our $target, use the Midpoint Formula:
         *
         *          1                     h²
         * f′(x₀) = - [f(x₀+h)-f(x₀-h)] - - f⁽³⁾(ζ₁)
         *          2h                    6
         *
         *     where ζ₁ lies between x₀ - h and x₀ + h
         */
        if ($sorted[1][$x] == $target) {
            $f⟮x₀⧿h⟯     = $sorted[0][$y];
            $f⟮x₀⧾h⟯     = $sorted[2][$y];
            $derivative = ($f⟮x₀⧾h⟯ - $f⟮x₀⧿h⟯) / (2 * $h);

            return $derivative;
        }

        /*
         * If the 1st or 3rd point is our $target, use the Endpoint Formula:
         * Note that when the 3rd point is our $target, we use a negative h.
         *
         *          1                               h²
         * f′(x₀) = - [-3f(x₀)+4f(x₀+h)-f(x₀+2h)] + - f⁽³⁾(ζ₀)
         *          2h                              3
         *
         *     where ζ₀ lies between x₀ and x₀ + 2h
         */
        if ($sorted[0][$x] == $target) {  // The 1st point is our $target
            $f⟮x₀⟯    = $sorted[0][$y];
            $f⟮x₀⧾h⟯  = $sorted[1][$y];
            $f⟮x₀⧾2h⟯ = $sorted[2][$y];
        } else {                          // The 3rd point is our $target, use negative h
            $h       = -$h;
            $f⟮x₀⟯    = $sorted[2][$y];
            $f⟮x₀⧾h⟯  = $sorted[1][$y];
            $f⟮x₀⧾2h⟯ = $sorted[0][$y];
        }

        $derivative = (-3 * $f⟮x₀⟯ + 4 * $f⟮x₀⧾h⟯ - $f⟮x₀⧾2h⟯) / (2 * $h);

        return $derivative;
    }
}
