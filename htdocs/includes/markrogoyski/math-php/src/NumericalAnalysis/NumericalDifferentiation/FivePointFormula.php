<?php

namespace MathPHP\NumericalAnalysis\NumericalDifferentiation;

use MathPHP\Exception;

/**
 * Five Point Formula
 *
 * In numerical analysis, the five point formula is used for approximating
 * the derivative of a function at a point in its domain.
 *
 * We can either directly supply a set of inputs and their corresponding outputs
 * for said function, or if we explicitly know the function, we can define it as a
 * callback function and then generate a set of points by evaluating that function
 * at 5 points between a start and end point.
 */
class FivePointFormula extends NumericalDifferentiation
{
    /**
     * Use the Five Point Formula to approximate the derivative of a function at
     * our $target. Our input can support either a set of arrays, or a callback
     * function with arguments (to produce a set of arrays). Each array in our
     * input contains two numbers which correspond to coordinates (x, y) or
     * equivalently, (x, f(x)), of the function f(x) whose derivative we are
     * approximating.
     *
     * The Five Point Formula requires we supply 5 points that are evenly spaced
     * apart, and that our target equals the x-components of one of our 5 points.
     *
     * Example: differentiation(2, function($x) {return $x**2;}, 0, 4 ,5) will produce
     * a set of arrays by evaluating the callback at 5 evenly spaced points
     * between 0 and 4. Then, this array will be used in our approximation.
     *
     * Five Point Formula:
     *
     *   - If the 3rd point is our $target, use the Midpoint Formula:
     *
     *              1                                         h⁴
     *     f′(x₀) = - [f(x₀-2h)-8f(x₀-h)+8f(x₀+h)-f(x₀+2h)] - - f⁽⁵⁾(ζ₁)
     *             12h                                        30
     *
     *         where ζ₁ lies between x₀ - 2h and x₀ + 2h
     *
     *   - If the 1st or 5th point is our $target, use the Endpoint Formula:
     *   - Note that when the 3rd point is our $target, we use a negative h.
     *
     *              1                                                        h⁴
     *     f′(x₀) = - [-25f(x₀)+48f(x₀+h)-36f(x₀+2h)+16f(x₀+3h)-3f(x₀+4h)] + - f⁽⁵⁾(ζ₀)
     *             12h                                                       5
     *
     *         where ζ₀ lies between x₀ and x₀ + 4h
     *
     * @param float          $target  The value at which we are approximating the derivative
     * @param callable|array $source  The source of our approximation. Should be either
     *                                a callback function or a set of arrays. Each array
     *                                (point) contains precisely two numbers, an x and y.
     *                                Example array: [[1,2], [2,3], [3,4], [4,5], [5,6]].
     *                                Example callback: function($x) {return $x**2;}
     * @param number         ...$args The arguments of our callback function: start,
     *                                end, and n. Example: approximate($number, $source, 0, 8, 5).
     *                                If $source is a set of points, do not input any
     *                               $args. Example: approximate($source).
     *
     * @return float                 The approximation of f'($target), i.e. the derivative
     *                               of our input at our target point
     *
     * @throws Exception\BadDataException
     */
    public static function differentiate(float $target, $source, ...$args): float
    {
        // Get an array of points from our $source argument
        $points = self::getPoints($source, $args);

        // Validate input, sort points, make sure spacing is constant, and make
        // sure our target is contained in an interval supplied by our $source
        self::validate($points, $degree = 5);
        $sorted = self::sort($points);
        self::isSpacingConstant($sorted);
        self::isTargetInPoints($target, $sorted);

        // Descriptive constants
        $x = self::X;
        $y = self::Y;

        // Initialize
        $h = ($sorted[4][$x] - $sorted[0][$x]) / 4;

        /*
         * If the 3rd point is our $target, use the Midpoint Formula:
         *
         *              1                                         h⁴
         *     f′(x₀) = - [f(x₀-2h)-8f(x₀-h)+8f(x₀+h)-f(x₀+2h)] - - f⁽⁵⁾(ζ₁)
         *             12h                                        30
         *
         *         where ζ₁ lies between x₀ - 2h and x₀ + 2h
         */
        if ($sorted[2][$x] == $target) {
            $f⟮x₀⧿2h⟯    = $sorted[0][$y];
            $f⟮x₀⧿h⟯     = $sorted[1][$y];
            $f⟮x₀⧾h⟯     = $sorted[3][$y];
            $f⟮x₀⧾2h⟯    = $sorted[4][$y];

            $derivative = ($f⟮x₀⧿2h⟯ - 8 * $f⟮x₀⧿h⟯ + 8 * $f⟮x₀⧾h⟯ - $f⟮x₀⧾2h⟯) / (12 * $h);

            return $derivative;
        }

        /*
         * If the 1st or 5th point is our $target, use the Endpoint Formula:
         * Note that when the 3rd point is our $target, we use a negative h.
         *
         *              1                                                        h⁴
         *     f′(x₀) = - [-25f(x₀)+48f(x₀+h)-36f(x₀+2h)+16f(x₀+3h)-3f(x₀+4h)] + - f⁽⁵⁾(ζ₀)
         *             12h                                                       5
         *
         *         where ζ₀ lies between x₀ and x₀ + 4h
         */
        if ($sorted[0][$x] == $target) {  // The 1st point is our $target
            $f⟮x₀⟯    = $sorted[0][$y];
            $f⟮x₀⧾h⟯  = $sorted[1][$y];
            $f⟮x₀⧾2h⟯ = $sorted[2][$y];
            $f⟮x₀⧾3h⟯ = $sorted[3][$y];
            $f⟮x₀⧾4h⟯ = $sorted[4][$y];
        } else {                          // The 5th point is our $target, use negative h
            $h = -$h;
            $f⟮x₀⟯    = $sorted[4][$y];
            $f⟮x₀⧾h⟯  = $sorted[3][$y];
            $f⟮x₀⧾2h⟯ = $sorted[2][$y];
            $f⟮x₀⧾3h⟯ = $sorted[1][$y];
            $f⟮x₀⧾4h⟯ = $sorted[0][$y];
        }

        $derivative = (-25 * $f⟮x₀⟯ + 48 * $f⟮x₀⧾h⟯ - 36 * $f⟮x₀⧾2h⟯ + 16 * $f⟮x₀⧾3h⟯ - 3 * $f⟮x₀⧾4h⟯) / (12 * $h);

        return $derivative;
    }
}
