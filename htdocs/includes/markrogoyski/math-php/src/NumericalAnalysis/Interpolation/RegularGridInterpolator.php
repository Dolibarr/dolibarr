<?php

namespace MathPHP\NumericalAnalysis\Interpolation;

use MathPHP\Exception;
use MathPHP\Search;
use MathPHP\Util\Iter;

/**
 * Interpolation on a regular grid in arbitrary dimensions
 *
 * https://en.wikipedia.org/wiki/Multivariate_interpolation#Regular_grid
 *
 * Data is defined on a regular grid. Grid spacing is predetermined, but not necessarily uniform.
 * Methods:
 *  - Linear
 *  - Nearest neighbor
 *
 * Implementation inspired by SciPy
 * https://docs.scipy.org/doc/scipy/reference/generated/scipy.interpolate.RegularGridInterpolator.html
 *
 * Example usage:
 * // Points defining the regular grid
 * $xs = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9];
 * $ys = [10, 11, 12, 13, 14, 15, 16, 17, 18, 19];
 * $zs = [110, 111, 112, 113, 114, 115, 116, 117, 118, 119];
 *
 * // Data on the regular grid in n dimensions
 * $data = [];
 * $func = function ($x, $y, $z) {
 *     return 2 * $x + 3 * $y - $z;
 * };
 * foreach ($xs as $i => $x) {
 *     foreach ($ys as $j => $y) {
 *         foreach ($zs as $k => $z) {
 *             $data[$i][$j][$k] = $func($x, $y, $z);
 *         }
 *     }
 * }
 *
 * // Constructing a RegularGridInterpolator
 * $rgi = new RegularGridInterpolator([$xs, $ys, $zs], $data, 'linear');
 *
 * // Interpolating coordinates on the regular grid
 * $coordinates   = [2.21, 12.1, 115.9];
 * $interpolation = $rgi($coordinates);  // -75.18
 */
class RegularGridInterpolator
{
    public const METHOD_LINEAR  = 'linear';
    public const METHOD_NEAREST = 'nearest';

    /** @var string Interpolation method (linear or nearest) */
    private $method;

    /** @var array[] Points defining the regular grid in n dimensions */
    private $grid;

    /** @var array Data on the regular grid in n dimensions */
    private $values;

    /**
     * @param array  $points Points defining the regular grid in n dimensions
     * @param array  $values Data on the regular grid in n dimensions
     * @param string $method (optional - default: linear) Interpolation method (linear or nearest)
     *
     * @throws Exception\BadDataException the points and value dimensions do not align, or if an unknown method is used
     */
    public function __construct(array $points, array $values, string $method = self::METHOD_LINEAR)
    {
        if (!\in_array($method, [self::METHOD_LINEAR, self::METHOD_NEAREST])) {
            throw new Exception\BadDataException("Method '{$method}' is not defined");
        }
        $this->method    = $method;
        $valuesDimension = $this->countDimensions($values);
        $pointsCount     = \count($points);

        if ($pointsCount > $valuesDimension) {
            throw new Exception\BadDataException(\sprintf('There are %d point arrays, but values has %d dimensions', $pointsCount, $valuesDimension));
        }

        $this->grid   = $points;
        $this->values = $values;
    }

    /**
     * Count dimensions of a multi-dimensional array
     *
     * @param  array $array
     *
     * @return int
     */
    private function countDimensions(array $array): int
    {
        if (\is_array(\reset($array))) {
            $return = $this->countDimensions(\reset($array)) + 1;
        } else {
            $return = 1;
        }

        return $return;
    }

    /**
     * Interpolation of the grid at some coordinates
     *
     * @param  array $xi n-dimensional array containing the coordinates to sample the gridded data at
     *
     * @return float
     *
     * @throws Exception\BadDataException if dimensions do not match
     */
    public function __invoke(array $xi): float
    {
        $gridDimension  = \count($this->grid);
        $pointDimension = \count($xi);
        if (\count($xi) != $gridDimension) {
            throw new Exception\BadDataException('The requested sample points xi have dimension ' . "{$pointDimension}, but this RegularGridInterpolator has " . "dimension {$gridDimension}");
        }

        [$indices, $normDistances] = $this->findIndices($xi);

        return $this->method === self::METHOD_LINEAR
            ? $this->evaluateLinear($indices, $normDistances)
            : $this->evaluateNearest($indices, $normDistances);
    }

    /**
     * @param array $indices
     * @param array $normDistances
     *
     * @return float|int
     */
    private function evaluateLinear(array $indices, array $normDistances)
    {
        $edges = [];
        foreach ($indices as $i) {
            $edges[] = [$i, $i + 1];
        }
        $edges[] = 1; // pass last argument (repeat)
        $edges   = $this->product(...$edges); // create many to many links

        $values = 0;
        foreach ($edges as $edge_indices) {
            $weight = 1;
            foreach (Iter::zip($edge_indices, $indices, $normDistances) as [$ei, $i, $yi]) {
                $weight *= ($ei == $i)
                    ? 1 - $yi
                    : $yi;
            }
            $values += ($this->flatCall($this->values, $edge_indices) * $weight);
        }

        return $values;
    }

    /**
     * @param array $indices
     * @param array $normDistances
     *
     * @return float|int
     */
    private function evaluateNearest(array $indices, array $normDistances)
    {
        $idxRes = [];
        foreach (Iter::zip($indices, $normDistances) as [$i, $yi]) {
            $idxRes[] = $yi <= 0.5
                ? $i
                : $i + 1;
        }

        return $this->flatCall($this->values, $idxRes);
    }

    /**
     * Find the indicies and norm distances for search point
     *
     * @param float[] $xi 1-dimensional array ( search point = [x,y,z ....] )
     *
     * @return array[] (indices in grid for search point, normDistances for search point)
     */
    private function findIndices($xi): array
    {
        $indices       = [];  // Find relevant edges between which xi are situated
        $normDistances = [];  // Compute distance to lower edge in unity units

        // Iterate through dimensions x-y-z-...>
        // $grid - 1nd array, example all x values (or all y..)
        // $x float, search point: x or y or z...
        foreach (Iter::zip($xi, $this->grid) as [$x, $grid]) {
            $gridSize = \count($grid);                       // Column count
            $i        = Search::sorted($grid, $x) - 1;  // Min match index
            if ($i < 0) {
                $i = 0;
            }
            if ($i > $gridSize - 2) {
                $i = $gridSize - 2;
            }

            $indices[]       = $i;
            $lessValue       = $grid[$i];
            $greaterValue    = $grid[$i + 1];
            $normDistances[] = ($x - $lessValue) / ($greaterValue - $lessValue);
        }

        return [$indices, $normDistances];
    }

    /**
     * Dynamically accessing multidimensional array value.
     *
     * @param array $data
     * @param array $keys
     *
     * @return array|mixed
     */
    private function flatCall(array $data, array $keys)
    {
        $current = $data;
        foreach ($keys as $key) {
            $current = $current[$key];
        }

        return $current;
    }

    /**
     * Find the cartesian product from the given iterator.
     * Output is lexicographic ordered
     *
     * @param mixed ...$args ...$iterables[, $repeat]
*
     * @return \Generator
     */
    private function product(...$args): \Generator
    {
        $repeat = \array_pop($args);
        $pools  = \array_merge(...\array_fill(0, $repeat, $args));
        $result = [[]];

        foreach ($pools as $pool) {
            $result_inner = [];
            foreach ($result as $x) {
                foreach ($pool as $y) {
                    $result_inner[] = \array_merge($x, [$y]);
                }
            }
            $result = $result_inner;
        }

        foreach ($result as $prod) {
            yield $prod;
        }
    }
}
