<?php

namespace MathPHP\NumericalAnalysis\NumericalIntegration;

use MathPHP\Exception;

/**
 * Base class for numerical integration techniques.
 *
 * Numerical integration techniques are used to approximate the value of
 * an indefinite integral.
 *
 * This class gives each technique a set of common tools, and requires each
 * technique to define an approximate() method to approximate an indefinite
 * integral.
 */
abstract class NumericalIntegration
{
    /** @var int Index of x */
    protected const X = 0;

    /** @var int Index of y */
    protected const Y = 1;

    abstract public static function approximate($source, ...$args);

    /**
     * Determine where the input $source argument is a callback function, a set
     * of arrays, or neither. If $source is a callback function, run it through
     * the functionToPoints() method with the input $args, and set $points to
     * output array. If $source is a set of arrays, simply set $points to
     * $source. If $source is neither, throw an Exception.
     *
     * @todo  Add method to verify function is continuous on our interval
     * @todo  Add method to verify input arguments are valid.
     *        Verify $start and $end are numbers, $end > $start, and $points is an integer > 1
     *
     * @param  callable|array  $source The source of our approximation. Should be either
     *                                 a callback function or a set of arrays.
     * @param  array           $args   The arguments of our callback function: start,
     *                                 end, and n. Example: [0, 8, 5]. If $source is a
     *                                 set of arrays, $args will default to [].
     *
     * @return array
     *
     * @throws Exception\BadDataException if $source is not callable or a set of arrays
     */
    public static function getPoints($source, array $args = []): array
    {
        // Guard clause - source must be callable or array of points
        if (!(\is_callable($source) || \is_array($source))) {
            throw new Exception\BadDataException('Input source is incorrect. You need to input either a callback function or a set of arrays');
        }

        // Source is already an array: nothing to do
        if (\is_array($source)) {
            return $source;
        }

        // Construct points from callable function
        $function = $source;
        $start    = $args[0];
        $end      = $args[1];
        $n        = $args[2];

        return self::functionToPoints($function, $start, $end, $n);
    }

    /**
     * Evaluate our callback function at n evenly spaced points on the interval between start and end
     *
     * @param  callable $function f(x) callback function
     * @param  float    $start    the start of the interval
     * @param  float    $end      the end of the interval
     * @param  int      $n        the number of function evaluations
     *
     * @return array
     */
    protected static function functionToPoints(callable $function, float $start, float $end, int $n): array
    {
        $points = [];
        $h      = ($end - $start) / ($n - 1);

        for ($i = 0; $i < $n; $i++) {
            $xᵢ         = $start + $i * $h;
            $f⟮xᵢ⟯       = $function($xᵢ);
            $points[$i] = [$xᵢ, $f⟮xᵢ⟯];
        }
        return $points;
    }

    /**
     * Validate that there are enough input arrays (points), that each point array
     * has precisely two numbers, and that no two points share the same first number
     * (x-component)
     *
     * @param  array $points Array of arrays (points)
     * @param  int   $degree The minimum number of input arrays
     *
     * @throws Exception\BadDataException if there are less than two points
     * @throws Exception\BadDataException if any point does not contain two numbers
     * @throws Exception\BadDataException if two points share the same first number (x-component)
     */
    public static function validate(array $points, int $degree = 2)
    {
        if (\count($points) < $degree) {
            throw new Exception\BadDataException("You need to have at least $degree sets of coordinates (arrays) for this technique");
        }

        $x_coordinates = [];
        foreach ($points as $point) {
            if (\count($point) !== 2) {
                throw new Exception\BadDataException('Each array needs to have have precisely two numbers, an x- and y-component');
            }

            $x_component = $point[self::X];
            if (\in_array($x_component, $x_coordinates)) {
                throw new Exception\BadDataException('Not a function. Your input array contains more than one coordinate with the same x-component.');
            }
            $x_coordinates[] = $x_component;
        }
    }

    /**
     * Sorts our coordinates (arrays) by their x-component (first number) such
     * that consecutive coordinates have an increasing x-component.
     *
     * @param  array[] $points
     *
     * @return array[]
     */
    protected static function sort(array $points): array
    {
        \usort($points, function ($a, $b) {
            return $a[self::X] <=> $b[self::X];
        });

        return $points;
    }
}
