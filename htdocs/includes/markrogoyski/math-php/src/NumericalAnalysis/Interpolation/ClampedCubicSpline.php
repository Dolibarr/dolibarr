<?php

namespace MathPHP\NumericalAnalysis\Interpolation;

use MathPHP\Expression\Polynomial;
use MathPHP\Expression\Piecewise;
use MathPHP\Exception;

/**
 * Clamped Cubic Spline Interpolating Polynomial
 *
 * In numerical analysis, cubic splines are used for polynomial
 * interpolation.
 *
 * A cubic spline is a spline constructed of piecewise third-order polynomials
 * which pass through a set of m control points. In the case of the Clamped
 * cubic spline, the first derivative of piecewise polynomial is set to equal the
 * first derivative of our input at the endpoints.
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
class ClampedCubicSpline extends Interpolation
{
    /** @var int Index of y’ (y-prime) */
    private const Y’ = 2;

    /**
     * Interpolate
     *
     * @param callable|array $source The source of our approximation. Should be either
     *                           a callback function or a set of arrays. Each array
     *                           (point) contains precisely three numbers: x, y, and y'
     *                           Example array: [[1,2,1], [2,3,0], [3,4,2]].
     *                           Example callback: function($x) {return $x**2;}
     * @param number ...$args   (Optional) An additional callback: our first derivative,
     *                           and arguments of our callback functions: start,
     *                           end, and n.
     *                           Example: approximate($source, $derivative, 0, 8, 5).
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
        $points = self::getSplinePoints($source, $args);

        // Validate input and sort points
        self::validateSpline($points, $degree = 1);
        $sorted = self::sort($points);

        // Descriptive constants
        $x  = self::X;
        $y  = self::Y;
        $y’ = self::Y’;

        // Initialize
        $n     = \count($sorted);
        $k     = $n - 1;
        $x₀    = $sorted[0][$x];
        $x₁    = $sorted[1][$x];
        $f⟮x₀⟯  = $sorted[0][$y];  // y₀
        $f⟮x₁⟯  = $sorted[1][$y];  // y₁
        $y’₀   = $sorted[0][$y’]; // y₀-prime
        $h     = [$x₁ - $x₀];
        $a     = [(3 / $h[0]) * ($f⟮x₁⟯ - $f⟮x₀⟯) - 3 * $y’₀];
        $μ     = [0.5];
        $z     = [$a[0] / (2 * $h[0])];
        $c[$k] = 0;
        $poly  = [];
        $int   = [];

        for ($i = 0; $i < $k; $i++) {
            $xᵢ    = $sorted[$i][$x];
            $xᵢ₊₁  = $sorted[$i + 1][$x];
            $a[$i] = $sorted[$i][$y];
            $h[$i] = $xᵢ₊₁ - $xᵢ;

            if ($i == 0) {
                continue;
            }

            $xᵢ₋₁   = $sorted[$i - 1][$x];
            $f⟮xᵢ⟯   = $sorted[$i][$y];   // yᵢ
            $f⟮xᵢ₊₁⟯ = $sorted[$i + 1][$y]; // yᵢ₊₁
            $f⟮xᵢ₋₁⟯ = $sorted[$i - 1][$y]; // yᵢ₋₁

            $α      = (3 / $h[$i]) * ($f⟮xᵢ₊₁⟯ - $f⟮xᵢ⟯) - (3 / $h[$i - 1]) * ($f⟮xᵢ⟯ - $f⟮xᵢ₋₁⟯);
            $l      = 2 * ($xᵢ₊₁ - $xᵢ₋₁) - $h[$i - 1] * $μ[$i - 1];
            $μ[$i]  = $h[$i] / $l;
            $z[$i]  = ($α - $h[$i - 1] * $z[$i - 1]) / $l;
        }

        $f⟮xₙ⟯   = $sorted[$k][$y];   // yₙ
        $f⟮xₙ₋₁⟯ = $sorted[$k - 1][$y]; // yₙ₋₁
        $y’ₙ    = $sorted[$k][$y’];  // yₙ-prime
        $a[$k]  = 3 * $y’ₙ - 3 * ($f⟮xₙ⟯ - $f⟮xₙ₋₁⟯) / $h[$k - 1];
        $l      = $h[$k - 1] * (2 - $μ[$k - 1]);
        $z[$k]  = ($a[$k] - $h[$k - 1] * $z[$k - 1]) / $l;
        $c[$n]  = $z[$k];

        for ($i = $k - 1; $i >= 0; $i--) {
            $xᵢ     = $sorted[$i][$x];
            $xᵢ₊₁   = $sorted[$i + 1][$x];
            $f⟮xᵢ⟯   = $sorted[$i][$y];    // yᵢ
            $f⟮xᵢ₊₁⟯ = $sorted[$i + 1][$y];  // yᵢ₊₁

            $c[$i]  = $z[$i] - $μ[$i] * $c[$i + 1];
            $b[$i]  = ($f⟮xᵢ₊₁⟯ - $f⟮xᵢ⟯) / $h[$i] - $h[$i] * ($c[$i + 1] + 2 * $c[$i]) / 3;
            $d[$i]  = ($c[$i + 1] - $c[$i]) / (3 * $h[$i]);

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
     * @param callable|array   $source The source of our approximation. Should be either
     *                         a callback function or a set of arrays.
     * @param  array   $args   The arguments of our callback function: derivative,
     *                         start, end, and n. Example: [$derivative, 0, 8, 5].
     *                         If $source is a set of arrays, $args will default to [].
     *
     * @return array
     * @throws Exception\BadDataException if $source is not callable or a set of arrays
     */
    public static function getSplinePoints($source, array $args = []): array
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
        $function   = $source;
        $derivative = $args[0];
        $start      = $args[1];
        $end        = $args[2];
        $n          = $args[3];

        return self::functionToSplinePoints($function, $derivative, $start, $end, $n);
    }

    /**
     * Evaluate our callback function and derivative at n evenly spaced points
     * on the interval between start and end
     *
     * @param  callable $function   f(x) callback function
     * @param  callable $derivative f'(x) callback function
     * @param  float    $start      the start of the interval
     * @param  float    $end        the end of the interval
     * @param  int      $n          the number of function evaluations
     *
     * @return array
     */
    protected static function functionToSplinePoints(callable $function, callable $derivative, float $start, float $end, int $n): array
    {
        $points = [];
        $h      = ($end - $start) / ($n - 1);

        for ($i = 0; $i < $n; $i++) {
            $xᵢ         = $start + $i * $h;
            $f⟮xᵢ⟯       = $function($xᵢ);
            $f’⟮xᵢ⟯      = $derivative($xᵢ);
            $points[$i] = [$xᵢ, $f⟮xᵢ⟯, $f’⟮xᵢ⟯];
        }

        return $points;
    }

    /**
     * Validate that there are enough input arrays (points), that each point array
     * has precisely three numbers, and that no two points share the same first number
     * (x-component)
     *
     * @param  array $points Array of arrays (points)
     * @param  int   $degree The minimum number of input arrays
     *
     * @throws Exception\BadDataException if there are less than two points
     * @throws Exception\BadDataException if any point does not contain three numbers
     * @throws Exception\BadDataException if two points share the same first number (x-component)
     */
    public static function validateSpline(array $points, int $degree = 2)
    {
        if (\count($points) < $degree) {
            throw new Exception\BadDataException('You need to have at least $degree sets of coordinates (arrays) for this technique');
        }

        $x_coordinates = [];
        foreach ($points as $point) {
            if (\count($point) !== 3) {
                throw new Exception\BadDataException('Each array needs to have have precisely three numbers, representing x, y, and y-prime');
            }

            $x_component = $point[self::X];
            if (\in_array($x_component, $x_coordinates)) {
                throw new Exception\BadDataException('Not a function. Your input array contains more than one coordinate with the same x-component.');
            }
            $x_coordinates[] = $x_component;
        }
    }
}
