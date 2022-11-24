<?php

namespace MathPHP\NumericalAnalysis\NumericalDifferentiation;

use MathPHP\Exception;

/**
 * Base class for numerical differentiation techniques.
 *
 * Numerical differentiation techniques are used to approximate the derivative
 * of a function at an input value.
 *
 * This class gives each technique a set of common tools, and requires each
 * technique to define a differentiate() method to approximate the derivative
 * of a function at an input value.
 */
abstract class NumericalDifferentiation
{
    /** @var int Index of x */
    protected const X = 0;

    /** @var int Index of y */
    protected const Y = 1;

    abstract public static function differentiate(float $target, $source, ...$args);

    /**
     * Determine where the input $source argument is a callback function, a set
     * of arrays, or neither. If $source is a callback function, run it through
     * the functionToPoints() method with the input $args, and set $points to
     * output array. If $source is a set of arrays, simply set $points to
     * $source. If $source is neither, throw an Exception.
     *
     * @todo  Add method to verify function is continuous on our interval.
     * @todo  Add method to verify input arguments are valid.
     *        Verify $start and $end are numbers, $end > $start, and $points is an integer > 1
     *
     * @param  callable|array  $source The source of our approximation. Should be either
     *                         a callback function or a set of arrays.
     * @param  array   $args   The arguments of our callback function: start,
     *                         end, and n. Example: [0, 8, 5]. If $source is a
     *                         set of arrays, $args will default to [].
     *
     * @return array
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
     * Evaluate our callback function at n evenly spaced points on the interval
     * between start and end
     *
     * @param  callable $function f(x) callback function
     * @param  number   $start    the start of the interval
     * @param  number   $end      the end of the interval
     * @param  number   $n        the number of function evaluations
     *
     * @return array[]
     */
    protected static function functionToPoints(callable $function, $start, $end, $n): array
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
     * Validate that there are a set number of input arrays (points), that each point array
     * has precisely two numbers, and that no two points share the same first number
     * (x-component)
     *
     * @param  array $points Array of arrays (points)
     * @param  int   $degree The number of input arrays
     *
     * @throws Exception\BadDataException if there are not enough input arrays
     * @throws Exception\BadDataException if any point does not contain two numbers
     * @throws Exception\BadDataException if two points share the same first number (x-component)
     */
    public static function validate(array $points, int $degree)
    {
        if (\count($points) != $degree) {
            throw new Exception\BadDataException("You need to have $degree sets of coordinates (arrays) for this technique");
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
     * @param array[] $points
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

    /**
     * Ensures that the length of each subinterval is equal, or equivalently,
     * that the spacing between each point is equal
     *
     * @param  array[] $sorted Points sorted by (increasing) x-component
     *
     * @throws Exception\BadDataException if the spacing between any two points is not equal
     *         to the average spacing between every point
     */
    public static function isSpacingConstant(array $sorted)
    {
        $x       = self::X;
        $length  = \count($sorted);
        $spacing = ($sorted[$length - 1][$x] - $sorted[0][$x]) / ($length - 1);

        for ($i = 1; $i < $length - 1; $i++) {
            if ($sorted[$i + 1][$x] - $sorted[$i][$x] !== $spacing) {
                throw new Exception\BadDataException('The size of each subinterval must be the same. Provide points with constant spacing.');
            }
        }
    }

    /**
     * Ensures that our target is the x-component of one of the points we supply
     *
     * @param  number $target The value at which we are approximating the derivative
     * @param  array  $sorted Points sorted by (increasing) x-component
     *
     * @throws Exception\BadDataException if $target is not contained in the array of our x-components
     */
    public static function isTargetInPoints($target, array $sorted)
    {
        $xComponents = \array_map(
            function (array $point) {
                return $point[self::X];
            },
            $sorted
        );

        if (!\in_array($target, $xComponents)) {
            throw new Exception\BadDataException('Your target point must be the x-component of one of the points you supplied.');
        }
    }
}
