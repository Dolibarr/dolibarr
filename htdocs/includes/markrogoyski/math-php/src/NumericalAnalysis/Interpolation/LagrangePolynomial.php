<?php

namespace MathPHP\NumericalAnalysis\Interpolation;

use MathPHP\Exception;
use MathPHP\Expression\Polynomial;

/**
 * Lagrange Interpolating Polynomial
 *
 * In numerical analysis, the Lagrange Polynomials are used for polynomial
 * interpolation.
 *
 * "Given a set of distinct points {xⱼ,yⱼ}, the Lagrange Polynomial is the
 * [unique] polynomial of least degree such that at each point xⱼ assumes the
 * corresponding value yⱼ (i.e. the functions coincide at each point)."
 *
 * The lagrange polynomials belong to a collection of techniques that
 * interpolate a function or a set of values, producing a continuous polynomial.
 * We can either directly supply a set of inputs and their corresponding outputs
 * for said function, or if we explicitly know the function, we can define it as
 * a callback function and then generate a set of points by evaluating that
 * function at n points between a start and end point. We then use these values
 * to interpolate a Lagrange polynomial.
 *
 * https://en.wikipedia.org/wiki/Lagrange_polynomial
 * http://mathworld.wolfram.com/LagrangeInterpolatingPolynomial.html
 */
class LagrangePolynomial extends Interpolation
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
     * @return Polynomial        The lagrange polynomial p(x)
     *
     * @throws Exception\BadDataException
     * @throws Exception\IncorrectTypeException
     */
    public static function interpolate($source, ...$args): Polynomial
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
        $n   = \count($sorted);
        $p⟮t⟯ = new Polynomial([0]);

        /*         n      n
         *   p⟮t⟯ = ∑ f⟮xᵢ⟯ Π (x - xᵢ) / (xⱼ - xᵢ)
         *        ⁱ⁼⁰    ʲ⁼⁰
         *              ʲꜝ⁼ⁱ
         */
        for ($i = 0; $i < $n; $i++) {
            $pᵢ⟮t⟯ = new Polynomial([$sorted[$i][$y]]); // yᵢ
            for ($j = 0; $j < $n; $j++) {
                if ($j == $i) {
                    continue;
                }
                $xᵢ   = $sorted[$i][$x];
                $xⱼ   = $sorted[$j][$x];
                $Lᵢ⟮t⟯ = new Polynomial([1 / ($xᵢ - $xⱼ), -$xⱼ / ($xᵢ - $xⱼ)]);
                $pᵢ⟮t⟯ = $pᵢ⟮t⟯->multiply($Lᵢ⟮t⟯);
            }
            $p⟮t⟯ = $p⟮t⟯->add($pᵢ⟮t⟯);
        }

        return $p⟮t⟯;
    }
}
