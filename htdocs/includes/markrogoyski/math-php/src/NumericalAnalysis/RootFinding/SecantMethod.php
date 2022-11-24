<?php

namespace MathPHP\NumericalAnalysis\RootFinding;

use MathPHP\Exception;

/**
 * Secant Method (also known as the Newton–Raphson method)
 *
 * In numerical analysis, the Secant Method is a method for finding successively
 * better approximations to the roots (or zeroes) of a real-valued function. It is
 * a variation of Newton's Method that we can utilize when the derivative of our
 * function f'(x) is not explicity given or cannot be calculated.
 *
 * https://en.wikipedia.org/wiki/Secant_method
 */
class SecantMethod
{
    /**
     * Use the Secant Method to find the x which produces $f(x) = 0 by calculating
     * the average change between our initial approximations and moving our
     * approximations closer to the root.
     *
     * @param callable $function f(x) callback function
     * @param number   $p₀       First initial approximation
     * @param number   $p₁       Second initial approximation
     * @param number   $tol      Tolerance; How close to the actual solution we would like.
     *
     * @return number
     *
     * @throws Exception\OutOfBoundsException if $tol (the tolerance) is negative
     * @throws Exception\BadDataException if $p₀ = $p₁
     */
    public static function solve(callable $function, $p₀, $p₁, $tol)
    {
        Validation::tolerance($tol);
        Validation::interval($p₀, $p₁);

        do {
            $q₀    = $function($p₀);
            $q₁    = $function($p₁);
            $slope = ($q₁ - $q₀) / ($p₁ - $p₀);
            $p     = $p₁ - ($q₁ / $slope);
            $dif   = \abs($p - $p₁);
            $p₀    = $p₁;
            $p₁    = $p;
        } while ($dif > $tol);

        return $p;
    }
}
