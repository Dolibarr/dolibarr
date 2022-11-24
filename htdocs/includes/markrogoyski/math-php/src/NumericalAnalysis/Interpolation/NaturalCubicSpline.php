<?php

namespace MathPHP\NumericalAnalysis\Interpolation;

use MathPHP\Exception;
use MathPHP\Expression\Polynomial;
use MathPHP\Expression\Piecewise;

/**
 * Natural Cubic Spline Interpolating Polynomial
 *
 * In numerical analysis, cubic splines are used for polynomial
 * interpolation.
 *
 * A cubic spline is a spline constructed of piecewise third-order polynomials
 * which pass through a set of m control points." In the case of the natural
 * cubic spline, the second derivative of each polynomial is set to zero at the
 * endpoints of each interval of the piecewise function.
 *
 * Cubic spline interpolation belongs to a collection of techniques that
 * interpolate a function or a set of values, producing a continuous polynomial.
 * In the case of the cubic spline, a piecewise function (polynomial) is produced.
 * We can either directly supply a set of inputs and their corresponding outputs
 * for said function, or if we explicitly know the function, we can define it as
 * a callback function and then generate a set of points by evaluating that
 * function at n points between a start and end point. We then use these values
 * to interpolate our piecewise polynomial.
 *
 * https://en.wikipedia.org/wiki/Spline_interpolation
 * http://mathworld.wolfram.com/CubicSpline.html
 */
class NaturalCubicSpline extends Interpolation
{
    /**
     * Interpolate
     *
     * @param callable|array $source The source of our approximation. Should be either
     *                           a callback function or a set of arrays. Each array
     *                           (point) contains precisely two numbers, an x and y.
     *                           Example array: [[1,2], [2,3], [3,4]].
     *                           Example callback: function($x) {return $x**2;}
     * @param number   ...$args  The arguments of our callback function: start,
     *                           end, and n. Example: approximate($source, 0, 8, 5).
     *                           If $source is a set of points, do not input any
     *                           $args. Example: approximate($source).
     *
     * @return Piecewise         The interpolating (piecewise) polynomial, as an
     *                           instance of Piecewise.
     *
     * @throws Exception\BadDataException
     */
    public static function interpolate($source, ...$args): Piecewise
    {
        // Get an array of points from our $source argument
        $points = self::getPoints($source, $args);

        // Validate input and sort points
        self::validate($points, $degree = 1);
        $sorted = self::sort($points);

        // Descriptive constants
        $x = self::X;
        $y = self::Y;

        // Initialize
        $n     = \count($sorted);
        $k     = $n - 1;
        $h     = [];
        $a     = [];
        $μ     = [0];
        $z     = [0];
        $z[$k] = 0;
        $c     = [];
        $c[$k] = 0;
        $poly  = [];
        $int   = [];

        for ($i = 0; $i < $k; $i++) {
            $xᵢ     = $sorted[$i][$x];
            $xᵢ₊₁   = $sorted[$i + 1][$x];
            $a[$i]  = $sorted[$i][$y];
            $h[$i]  = $xᵢ₊₁ - $xᵢ;

            if ($i == 0) {
                continue;
            }

            $xᵢ₋₁   = $sorted[$i - 1][$x];
            $f⟮xᵢ⟯   = $sorted[$i][$y];    // yᵢ
            $f⟮xᵢ₊₁⟯ = $sorted[$i + 1][$y];  // yᵢ₊₁
            $f⟮xᵢ₋₁⟯ = $sorted[$i - 1][$y];  // yᵢ₋₁

            $α      = (3 / $h[$i]) * ($f⟮xᵢ₊₁⟯ - $f⟮xᵢ⟯) - (3 / $h[$i - 1]) * ($f⟮xᵢ⟯ - $f⟮xᵢ₋₁⟯);
            $l      = 2 * ($xᵢ₊₁ - $xᵢ₋₁) - $h[$i - 1] * $μ[$i - 1];
            $μ[$i]  = $h[$i] / $l;
            $z[$i]  = ($α - $h[$i - 1] * $z[$i - 1]) / $l;
        }

        for ($i = $k - 1; $i >= 0; $i--) {
            $xᵢ       = $sorted[$i][$x];
            $xᵢ₊₁     = $sorted[$i + 1][$x];
            $f⟮xᵢ⟯     = $sorted[$i][$y];    // yᵢ
            $f⟮xᵢ₊₁⟯   = $sorted[$i + 1][$y];  // yᵢ₊₁

            $c[$i]    = $z[$i] - $μ[$i] * $c[$i + 1];
            $b[$i]    = ($f⟮xᵢ₊₁⟯ - $f⟮xᵢ⟯) / $h[$i] - $h[$i] * ($c[$i + 1] + 2 * $c[$i]) / 3;
            $d[$i]    = ($c[$i + 1] - $c[$i]) / (3 * $h[$i]);

            $poly[$i] = new Polynomial([
                $d[$i],
                $c[$i] - 3 * $d[$i] * $xᵢ,
                $b[$i] - 2 * $c[$i] * $xᵢ + 3 * $d[$i] * ($xᵢ ** 2),
                $a[$i] - $b[$i] * $xᵢ + $c[$i] * ($xᵢ ** 2) - $d[$i] * ($xᵢ ** 3)
            ]);

            if ($i == 0) {
                $int[$i] = [$xᵢ, $xᵢ₊₁];
            } else {
                $int[$i] = [$xᵢ, $xᵢ₊₁, true, false];
            }
        }

        $piecewise = new Piecewise($int, $poly);

        return $piecewise;
    }
}
