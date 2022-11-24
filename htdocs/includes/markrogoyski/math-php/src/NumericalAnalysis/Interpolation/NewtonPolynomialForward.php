<?php

namespace MathPHP\NumericalAnalysis\Interpolation;

use MathPHP\Exception;
use MathPHP\Expression\Polynomial;

/**
 * Newton (Forward) Interpolating Polynomials
 *
 * Newton Polynomials are used for polynomial interpolation.
 *
 * Newton (Forward) Interpolating Polynomial belongs to a class of techniques called
 * Newton Polynomials. These techniques are used to generate an interpolating
 * polynomial for a given set of points (or a function). We can either directly
 * supply a set of inputs and their corresponding outputs for said function, or
 * if we explicitly know the function, we can define it as a callback function
 * and then generate a set of points by evaluating that function at n points
 * between a start and end point. We then use these values to interpolate a
 * Lagrange polynomial.
 *
 * https://en.wikipedia.org/wiki/Newton_polynomial
 */
class NewtonPolynomialForward extends Interpolation
{
    /**
     * Interpolate
     *
     * @param callable|array $source The source of our approximation. Should be either
     *                               a callback function or a set of arrays. Each array
     *                               (point) contains precisely two numbers, an x and y.
     *                               Example array: [[1,2], [2,3], [3,4]].
     *                               Example callback: function($x) {return $x**2;}
     * @param number        ...$args The arguments of our callback function: start,
     *                               end, and n. Example: approximate($source, 0, 8, 5).
     *                               If $source is a set of points, do not input any
     *                               $args. Example: approximate($source).
     *
     * @return callable              The interpolating polynomial p(x)
     *
     * @throws Exception\BadDataException
     * @throws Exception\IncorrectTypeException
     */
    public static function interpolate($source, ...$args): callable
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
        $n   = \count($sorted);
        $Q   = [];

        // Build first column of divided differences table
        for ($i = 0; $i < $n; $i++) {
            $Q[$i][0] = $sorted[$i][$y]; // yᵢ
        }

        // Recursively generate remaining columns of our divided difference table
        for ($i = 1; $i < $n; $i++) {
            for ($j = 1; $j <= $i; $j++) {
                $xᵢ₋ⱼ        = $sorted[$i - $j][$x];
                $xᵢ          = $sorted[$i][$x];
                $Q₍ᵢ₎₍ⱼ₋₁₎   = $Q[$i][$j - 1];
                $Q₍ᵢ₋₁₎₍ⱼ₋₁₎ = $Q[$i - 1][$j - 1];
                $Q[$i][$j]   = ($Q₍ᵢ₎₍ⱼ₋₁₎ - $Q₍ᵢ₋₁₎₍ⱼ₋₁₎) / ($xᵢ - $xᵢ₋ⱼ);
            }
        }

        // initialize empty polynomial
        $polynomial = new Polynomial([0]);

        for ($i = 0; $i < $n; $i++) {
            // start each product with the upper diagonal from our divided differences table
            $product = new Polynomial([$Q[$i][$i]]);

            for ($j = 1; $j <= $i; $j++) {
                // generate the (x - xⱼ₋₁) term for each j
                $term = new Polynomial([1, -$sorted[$j - 1][$x]]);
                // multiply the term and our cumulative product
                $product = $product->multiply($term);
            }
            // add the whole product to our polynomial for each i
            $polynomial = $polynomial->add($product);
        }

        return $polynomial;
    }
}
