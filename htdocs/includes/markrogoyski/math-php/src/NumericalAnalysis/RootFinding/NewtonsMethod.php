<?php

namespace MathPHP\NumericalAnalysis\RootFinding;

use MathPHP\Exception;

/**
 * Newton's Method (also known as the Newton–Raphson method)
 *
 * In numerical analysis, Newton's method is a method for finding successively better
 * approximations to the roots (or zeroes) of a real-valued function.
 */
class NewtonsMethod
{
    /**
     * Use Newton's Method to find the x which produces $target = $function(x) value
     * $args is an array of parameters to pass to $function, but having the element that
     * will be changed and serve as the initial guess in position $position.
     *
     * @param callable     $function     f(x) callback function
     * @param array<mixed> $args         Parameters to pass to callback function. The initial value for the
     *                                   parameter of interest must be in this array.
     * @param int|float    $target       Value of f(x) we a trying to solve for
     * @param float        $tol          Tolerance; How close to the actual solution we would like.
     * @param int          $position     Which element in the $args array will be changed; also serves as initial guess
     * @param int          $iterations
     *
     * @return int|float
     *
     * @throws Exception\OutOfBoundsException if the tolerance is not valid
     */
    public static function solve(callable $function, array $args, $target, float $tol, int $position = 0, int $iterations = 100)
    {
        Validation::tolerance($tol);

        // Initialize
        $args1 = $args;
        $guess = $args[$position];
        $i     = 0;

        do {
            $args1[$position] = $guess + $tol; // load the initial guess into the arguments
            $args[$position]  = $guess;        // load the initial guess into the arguments
            $y                = $function(...$args);
            $y_at_xplusdelx   = $function(...$args1);
            $slope            = ($y_at_xplusdelx - $y) / $tol;
            $del_y            = $target - $y;
            if (\abs($slope) < $tol) {
                return \NAN;
            }
            $guess            = $del_y / $slope + $guess;
            $dif              = \abs($del_y);
            $i++;
        } while ($dif > $tol && $i < $iterations);

        if ($dif > $tol) {
            return \NAN;
        }
        return $guess;
    }
}
