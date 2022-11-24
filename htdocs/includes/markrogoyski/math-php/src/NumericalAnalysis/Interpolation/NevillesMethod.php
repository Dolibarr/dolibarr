<?php

namespace MathPHP\NumericalAnalysis\Interpolation;

use MathPHP\Exception;
use MathPHP\Expression\Polynomial;

/**
 * Nevilles Method
 *
 * In numerical analysis, Nevilles Method is an interpolation technique that
 * uses Lagrange polynomials of lower powers recursively in order to compute
 * Lagrange polynomials of higher powers.
 *
 * Nevilles Method belongs to a collection of techniques that interpolate a
 * function or a set of values to approximate a function at a target point.
 * We can either directly supply a set of inputs and their corresponding outputs
 * for said function, or if we explicitly know the function, we can define it as
 * a callback function and then generate a set of points by evaluating that
 * function at n points between a start and end point. We then use these values
 * to interpolate Lagrange polynomials recursively at our target point.
 *
 * http://www2.math.ou.edu/~npetrov/neville.pdf
 */
class NevillesMethod extends Interpolation
{
    /**
     * Interpolate
     *
     * @param float          $target  The point at which we are interpolation
     * @param callable|array $source  The source of our approximation. Should be either
     *                                a callback function or a set of arrays. Each array
     *                                (point) contains precisely two numbers, an x and y.
     *                                Example array: [[1,2], [2,3], [3,4]].
     *                                Example callback: function($x) {return $x**2;}
     * @param float[]        ...$args The arguments of our callback function: start,
     *                                end, and n. Example: approximate($source, 0, 8, 5).
     *                                If $source is a set of points, do not input any
     *                                $args. Example: approximate($source).
     *
     * @return float                  The interpolated value at our target
     *
     * @throws Exception\BadDataException
     * @throws Exception\IncorrectTypeException
     */
    public static function interpolate(float $target, $source, ...$args): float
    {
        // Get an array of points from our $source argument
        $points = self::getPoints($source, $args);

        // Validate input and sort points
        self::validate($points, $degree = 2);
        $sorted = self::sort($points);

        // Descriptive constants
        $x = self::X;
        $y = self::Y;

        // Initialize
        $n = \count($sorted);
        $Q = [];

        // Build our 0th-degree Lagrange polynomials: Q₍ᵢ₎₍₀₎ = yᵢ for all i < n
        for ($i = 0; $i < $n; $i++) {
            $Q[$i][0] = new Polynomial([$sorted[$i][$y]]); // yᵢ
        }

        // Recursively generate our (n-1)th-degree Lagrange polynomial at $target
        for ($i = 1; $i < $n; $i++) {
            for ($j = 1; $j <= $i; $j++) {
                $xᵢ₋ⱼ        = $sorted[$i - $j][$x];
                $xᵢ          = $sorted[$i][$x];
                $Q₍ᵢ₎₍ⱼ₋₁₎   = $Q[$i][$j - 1]($target);
                $Q₍ᵢ₋₁₎₍ⱼ₋₁₎ = $Q[$i - 1][$j - 1]($target);
                $Q[$i][$j]   = LagrangePolynomial::interpolate([[$xᵢ₋ⱼ,$Q₍ᵢ₋₁₎₍ⱼ₋₁₎],[$xᵢ,$Q₍ᵢ₎₍ⱼ₋₁₎]]);
            }
        }

        // Return our (n-1)th-degree Lagrange polynomial evaluated at $target
        return $Q[$n - 1][$n - 1]($target);
    }
}
