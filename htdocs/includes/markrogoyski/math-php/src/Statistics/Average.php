<?php

namespace MathPHP\Statistics;

use MathPHP\Functions\Map;
use MathPHP\Exception;

/**
 * Statistical averages
 */
class Average
{
    /**************************************************************************
     * Averages of a list of numbers
     **************************************************************************/

    /**
     * Calculate the mean average of a list of numbers
     *
     *     ∑⟮xᵢ⟯
     * x̄ = -----
     *       n
     *
     * @param float[] $numbers
     *
     * @return float
     *
     * @throws Exception\BadDataException if the input array of numbers is empty
     */
    public static function mean(array $numbers): float
    {
        if (empty($numbers)) {
            throw new Exception\BadDataException('Cannot find the average of an empty list of numbers');
        }
        return \array_sum($numbers) / \count($numbers);
    }

    /**
     * Calculate the weighted mean average of a list of numbers
     * https://en.wikipedia.org/wiki/Weighted_arithmetic_mean
     *
     *     ∑⟮xᵢwᵢ⟯
     * x̄ = -----
     *      ∑⟮wᵢ⟯
     *
     * @param float[] $numbers
     * @param float[] $weights
     *
     * @return float
     *
     * @throws Exception\BadDataException if the input array of numbers is empty
     * @throws Exception\BadDataException if the number of numbers and weights are not equal
     */
    public static function weightedMean(array $numbers, array $weights): float
    {
        if (empty($numbers)) {
            throw new Exception\BadDataException('Cannot find the weightedMean of an empty list of numbers');
        }
        if (empty($weights)) {
            return Average::mean($numbers);
        }
        if (\count($numbers) !== \count($weights)) {
            throw new Exception\BadDataException('Numbers and weights must have the same number of elements.');
        }

        $∑⟮xᵢwᵢ⟯ = \array_sum(\array_map(
            function ($xᵢ, $wᵢ) {
                return $xᵢ * $wᵢ;
            },
            $numbers,
            $weights
        ));
        $∑⟮wᵢ⟯ = \array_sum($weights);

        return $∑⟮xᵢwᵢ⟯ / $∑⟮wᵢ⟯;
    }

    /**
     * Calculate the median average of a list of numbers
     *
     * @param float[] $numbers
     *
     * @return float
     *
     * @throws Exception\BadDataException if the input array of numbers is empty
     * @throws Exception\OutOfBoundsException if kth-smallest k is out of bounds
     */
    public static function median(array $numbers): float
    {
        if (empty($numbers)) {
            throw new Exception\BadDataException('Cannot find the median of an empty list of numbers');
        }
        if (\count($numbers) === 1) {
            return \array_pop($numbers);
        }

        // Reset the array key indexes because we don't know what might be passed in
        $numbers = \array_values($numbers);

        // For odd number of numbers, take the middle indexed number
        if (\count($numbers) % 2 == 1) {
            $middle_index = \intdiv(\count($numbers), 2);
            return self::kthSmallest($numbers, $middle_index);
        }

        // For even number of items, take the mean of the middle two indexed numbers
        $left_middle_index  = \intdiv(\count($numbers), 2) - 1;
        $left_median        = self::kthSmallest($numbers, $left_middle_index);
        $right_middle_index = $left_middle_index + 1;
        $right_median       = self::kthSmallest($numbers, $right_middle_index);

        return self::mean([ $left_median, $right_median ]);
    }

    /**
     * Return the kth smallest value in an array
     * Uses a linear-time algorithm: O(n) time in worst case.
     *
     * if $a = [1,2,3,4,6,7]
     *
     * kthSmallest($a, 4) = 6
     *
     * Algorithm:
     *  1) If n is small, just sort and return
     *  2) Otherwise, group into 5-element subsets and mind the median
     *  3) Find the median of the medians
     *  4) Find L and U sets
     *     - L is numbers lower than the median of medians
     *     - U is numbers higher than the median of medians
     *  5) Recursive step
     *     - if k is the median of medians, return that
     *     - Otherwise, recursively search in smaller group.
     *
     * @param float[] $numbers
     * @param int    $k zero indexed - must be less than n (count of $numbers)
     *
     * @return float
     *
     * @throws Exception\BadDataException if the input array of numbers is empty
     * @throws Exception\OutOfBoundsException if k ≥ n
     */
    public static function kthSmallest(array $numbers, int $k): float
    {
        $n = \count($numbers);
        if ($n === 0) {
            throw new Exception\BadDataException('Cannot find the k-th smallest of an empty list of numbers');
        }
        if ($k >= $n) {
            throw new Exception\OutOfBoundsException('k cannot be greater than or equal to the count of numbers');
        }

        // Reset the array key indexes because we don't know what might be passed in
        $numbers = \array_values($numbers);

        // If the array is 5 elements or smaller, use quicksort and return the element of interest.
        if ($n <= 5) {
            \sort($numbers);
            return $numbers[$k];
        }

        // Otherwise, we are going to slice $numbers into 5-element slices and find the median of each.
        $num_slices = \ceil($n / 5);
        $median_array = [];
        for ($i = 0; $i < $num_slices; $i++) {
            $median_array[] = self::median(\array_slice($numbers, 5 * $i, 5));
        }

        // Then we find the median of the medians.
        $median_of_medians = self::median($median_array);

        // Next we walk the array and separate it into values that are greater than or less than this "median of medians".
        $lower_upper   = self::splitAtValue($numbers, $median_of_medians);
        $lower_number = \count($lower_upper['lower']);
        $equal_number = $lower_upper['equal'];

        // Lastly, we find which group of values our value of interest is in, and find it in the smaller array.
        if ($k < $lower_number) {
            return self::kthSmallest($lower_upper['lower'], $k);
        } elseif ($k < ($lower_number + $equal_number)) {
            return $median_of_medians;
        } else {
            return self::kthSmallest($lower_upper['upper'], $k - $lower_number - $equal_number);
        }
    }

    /**
     * Given an array and a value, separate the array into two groups,
     * those values which are greater than the value, and those that are less
     * than the value. Also, tell how many times the value appears in the array.
     *
     * @param float[] $numbers
     * @param float   $value
     *
     * @return array
     */
    private static function splitAtValue(array $numbers, float $value): array
    {
        $lower        = [];
        $upper        = [];
        $number_equal = 0;

        foreach ($numbers as $number) {
            if ($number < $value) {
                $lower[] = $number;
            } elseif ($number > $value) {
                $upper[] = $number;
            } else {
                $number_equal++;
            }
        }

        return [
            'lower' => $lower,
            'upper' => $upper,
            'equal' => $number_equal,
        ];
    }

    /**
     * Calculate the mode average of a list of numbers
     * If multiple modes (bimodal, trimodal, etc.), all modes will be returned.
     * Always returns an array, even if only one mode.
     *
     * @param float[] $numbers
     *
     * @return float[] of mode(s)
     *
     * @throws Exception\BadDataException if the input array of numbers is empty
     */
    public static function mode(array $numbers): array
    {
        if (empty($numbers)) {
            throw new Exception\BadDataException('Cannot find the mode of an empty list of numbers');
        }

        // Count how many times each number occurs.
        // Determine the max any number occurs.
        // Find all numbers that occur max times.
        $number_strings = \array_map('\strval', $numbers);
        $number_counts  = \array_count_values($number_strings);
        $max            = \max($number_counts);
        $modes          = array();
        foreach ($number_counts as $number => $count) {
            if ($count === $max) {
                $modes[] = $number;
            }
        }

        // Cast back to numbers
        return \array_map('\floatval', $modes);
    }

    /**
     * Geometric mean
     * A type of mean which indicates the central tendency or typical value of a set of numbers
     * by using the product of their values (as opposed to the arithmetic mean which uses their sum).
     * https://en.wikipedia.org/wiki/Geometric_mean
     *                    __________
     * Geometric mean = ⁿ√a₀a₁a₂ ⋯
     *
     * @param  float[] $numbers
     *
     * @return float
     *
     * @throws Exception\BadDataException if the input array of numbers is empty
     */
    public static function geometricMean(array $numbers): float
    {
        if (empty($numbers)) {
            throw new Exception\BadDataException('Cannot find the geometric mean of an empty list of numbers');
        }

        $n       = \count($numbers);
        $a₀a₁a₂⋯ = \array_reduce(
            $numbers,
            function ($carry, $a) {
                return $carry * $a;
            },
            1
        );
        $ⁿ√a₀a₁a₂⋯ = \pow($a₀a₁a₂⋯, 1 / $n);

        return $ⁿ√a₀a₁a₂⋯;
    }

    /**
     * Harmonic mean (subcontrary mean)
     * The harmonic mean can be expressed as the reciprocal of the arithmetic mean of the reciprocals.
     * Appropriate for situations when the average of rates is desired.
     * https://en.wikipedia.org/wiki/Harmonic_mean
     *
     *
     *        n
     * H = ------
     *      n  1
     *      ∑  -
     *     ⁱ⁼¹ xᵢ
     *
     * @param  float[] $numbers
     *
     * @return float
     *
     * @throws Exception\BadDataException if the input array of numbers is empty
     * @throws Exception\BadDataException if there are negative numbers
     */
    public static function harmonicMean(array $numbers): float
    {
        if (empty($numbers)) {
            throw new Exception\BadDataException('Cannot find the harmonic mean of an empty list of numbers');
        }

        $negativeValues = \array_filter(
            $numbers,
            function ($x) {
                return $x < 0;
            }
        );
        if (!empty($negativeValues)) {
            throw new Exception\BadDataException('Harmonic mean cannot be computed for negative values.');
        }

        $n      = \count($numbers);
        $∑1／xᵢ = \array_sum(Map\Single::reciprocal($numbers));

        return $n / $∑1／xᵢ;
    }

    /**
     * Contraharmonic mean
     * A function complementary to the harmonic mean.
     * A special case of the Lehmer mean, L₂(x), where p = 2.
     * https://en.wikipedia.org/wiki/Contraharmonic_mean
     *
     * @param  float[] $numbers
     *
     * @return float
     */
    public static function contraharmonicMean(array $numbers): float
    {
        $p = 2;
        return self::lehmerMean($numbers, $p);
    }

    /**
     * Root mean square (quadratic mean)
     * The square root of the arithmetic mean of the squares of a set of numbers.
     * https://en.wikipedia.org/wiki/Root_mean_square
     *           ___________
     *          /x₁+²x₂²+ ⋯
     * x rms = / -----------
     *        √       n
     *
     * @param  float[] $numbers
     *
     * @return float
     *
     * @throws Exception\BadDataException if the input array of numbers is empty
     */
    public static function rootMeanSquare(array $numbers): float
    {
        if (empty($numbers)) {
            throw new Exception\BadDataException('Cannot find the root mean square of an empty list of numbers');
        }

        $n = \count($numbers);
        $x₁²＋x₂²＋⋯ = \array_sum(\array_map(
            function ($x) {
                return $x ** 2;
            },
            $numbers
        ));

        return \sqrt($x₁²＋x₂²＋⋯ / $n);
    }

    /**
     * Quadradic mean (root mean square)
     * Convenience function for rootMeanSquare
     *
     * @param  float[] $numbers
     *
     * @return float
     *
     * @throws Exception\BadDataException if the input array of numbers is empty
     */
    public static function quadraticMean(array $numbers): float
    {
        return self::rootMeanSquare($numbers);
    }

    /**
     * Trimean (TM, or Tukey's trimean)
     * A measure of a probability distribution's location defined as
     * a weighted average of the distribution's median and its two quartiles.
     * https://en.wikipedia.org/wiki/Trimean
     *
     *      Q₁ + 2Q₂ + Q₃
     * TM = -------------
     *            4
     *
     * @param float[] $numbers
     *
     * @return float
     *
     * @throws Exception\BadDataException if the input array of numbers is empty
     */
    public static function trimean(array $numbers): float
    {
        $quartiles = Descriptive::quartiles($numbers);
        $Q₁        = $quartiles['Q1'];
        $Q₂        = $quartiles['Q2'];
        $Q₃        = $quartiles['Q3'];

        return ($Q₁ + 2 * $Q₂ + $Q₃) / 4;
    }

    /**
     * Interquartile mean (IQM)
     * A measure of central tendency based on the truncated mean of the interquartile range.
     * Only the data in the second and third quartiles is used (as in the interquartile range),
     * and the lowest 25% and the highest 25% of the scores are discarded.
     * https://en.wikipedia.org/wiki/Interquartile_mean
     *
     * @param  float[] $numbers
     *
     * @return float
     *
     * @throws Exception\OutOfBoundsException
     */
    public static function interquartileMean(array $numbers): float
    {
        return self::truncatedMean($numbers, 25);
    }

    /**
     * IQM (Interquartile mean)
     * Convenience function for interquartileMean
     *
     * @param  float[] $numbers
     *
     * @return float
     *
     * @throws Exception\OutOfBoundsException
     */
    public static function iqm(array $numbers): float
    {
        return self::truncatedMean($numbers, 25);
    }

    /**
     * Cubic mean
     * https://en.wikipedia.org/wiki/Cubic_mean
     *              _________
     *             / 1  n
     * x cubic = ³/  -  ∑ xᵢ³
     *           √   n ⁱ⁼¹
     *
     * @param array $numbers
     *
     * @return float
     *
     * @throws Exception\BadDataException if the input array of numbers is empty
     */
    public static function cubicMean(array $numbers): float
    {
        if (empty($numbers)) {
            throw new Exception\BadDataException('Cannot find the cubic mean of an empty list of numbers');
        }

        $n    = \count($numbers);
        $∑xᵢ³ = \array_sum(Map\Single::cube($numbers));

        return \pow($∑xᵢ³ / $n, 1 / 3);
    }

    /**
     * Truncated mean (trimmed mean)
     * The mean after discarding given parts of a probability distribution or sample
     * at the high and low end, and typically discarding an equal amount of both.
     * This number of points to be discarded is given as a percentage of the total number of points.
     * https://en.wikipedia.org/wiki/Truncated_mean
     *
     * Trim count = floor((trim percent / 100) * sample size)
     *
     * For example: [8, 3, 7, 1, 3, 9] with a trim of 20%
     * First sort the list: [1, 3, 3, 7, 8, 9]
     * Sample size = 6
     * Then determine trim count: floor(20/100 * 6) = 1
     * Trim the list by removing 1 from each end: [3, 3, 7, 8]
     * Finally, find the mean: 5.2
     *
     * @param float[] $numbers
     * @param int     $trim_percent Percent between 0-50 indicating percent of observations trimmed from each end of distribution
     *
     * @return float
     *
     * @throws Exception\OutOfBoundsException if trim percent is not between 0 and 50
     * @throws Exception\BadDataException if the input array of numbers is empty
     */
    public static function truncatedMean(array $numbers, int $trim_percent): float
    {
        if (empty($numbers)) {
            throw new Exception\BadDataException('Cannot find the truncated mean of an empty list of numbers');
        }
        if ($trim_percent < 0 || $trim_percent > 50) {
            throw new Exception\OutOfBoundsException('Trim percent must be between 0 and 50.');
        }

        $n          = \count($numbers);
        $trim_count = \floor($n * ($trim_percent / 100));

        \sort($numbers);
        if ($trim_percent == 50) {
            return self::median($numbers);
        }

        for ($i = 1; $i <= $trim_count; $i++) {
            \array_shift($numbers);
            \array_pop($numbers);
        }
        return self::mean($numbers);
    }

    /**
     * Lehmer mean
     * https://en.wikipedia.org/wiki/Lehmer_mean
     *
     *          ∑xᵢᵖ
     * Lp(x) = ------
     *         ∑xᵢᵖ⁻¹
     *
     * Special cases:
     *  L-∞(x) is the min(x)
     *  L₀(x) is the harmonic mean
     *  L½(x₀, x₁) is the geometric mean if computed against two numbers
     *  L₁(x) is the arithmetic mean
     *  L₂(x) is the contraharmonic mean
     *  L∞(x) is the max(x)
     *
     * @param  float[] $numbers
     * @param  float   $p
     *
     * @return float
     *
     * @throws Exception\BadDataException if the input array of numbers is empty
     */
    public static function lehmerMean(array $numbers, $p): float
    {
        if (empty($numbers)) {
            throw new Exception\BadDataException('Cannot find the lehmer mean of an empty list of numbers');
        }

        // Special cases for infinite p
        if ($p == -\INF) {
            return \min($numbers);
        }
        if ($p == \INF) {
            return \max($numbers);
        }

        // Standard case for non-infinite p
        $∑xᵢᵖ   = \array_sum(Map\Single::pow($numbers, $p));
        $∑xᵢᵖ⁻¹ = \array_sum(Map\Single::pow($numbers, $p - 1));

        return $∑xᵢᵖ / $∑xᵢᵖ⁻¹;
    }

    /**
     * Generalized mean (power mean, Hölder mean)
     * https://en.wikipedia.org/wiki/Generalized_mean
     *
     *          / 1  n    \ 1/p
     * Mp(x) = |  -  ∑ xᵢᵖ|
     *          \ n ⁱ⁼¹   /
     *
     * Special cases:
     *  M-∞(x) is \min(x)
     *  M₋₁(x) is the harmonic mean
     *  M₀(x) is the geometric mean
     *  M₁(x) is the arithmetic mean
     *  M₂(x) is the quadratic mean
     *  M₃(x) is the cubic mean
     *  M∞(x) is max(X)
     *
     * @param  float[] $numbers
     * @param  float   $p
     *
     * @return float
     *
     * @throws Exception\BadDataException if the input array of numbers is empty
     */
    public static function generalizedMean(array $numbers, float $p): float
    {
        if (empty($numbers)) {
            throw new Exception\BadDataException('Cannot find the generalized mean of an empty list of numbers');
        }

        // Special cases for infinite p
        if ($p == -\INF) {
            return \min($numbers);
        }
        if ($p == \INF) {
            return \max($numbers);
        }

        // Special case for p = 0 (geometric mean)
        if ($p == 0) {
            return self::geometricMean($numbers);
        }

        // Standard case for non-infinite p
        $n    = \count($numbers);
        $∑xᵢᵖ = \array_sum(Map\Single::pow($numbers, $p));

        return \pow($∑xᵢᵖ / $n, 1 / $p);
    }

    /**
     * Power mean (generalized mean)
     * Convenience method for generalizedMean
     *
     * @param  float[] $numbers
     * @param  float $p
     *
     * @return float
     *
     * @throws Exception\BadDataException if the input array of numbers is empty
     */
    public static function powerMean(array $numbers, float $p): float
    {
        return self::generalizedMean($numbers, $p);
    }

    /**************************************************************************
     * Moving averages (list of numbers)
     **************************************************************************/

    /**
     * Simple n-point moving average SMA
     * The unweighted mean of the previous n data.
     *
     * First calculate initial average:
     *  ⁿ⁻¹
     *   ∑ xᵢ
     *  ᵢ₌₀
     *
     * To calculating successive values, a new value comes into the sum and an old value drops out:
     *  SMAtoday = SMAyesterday + NewNumber/N - DropNumber/N
     *
     * @param  float[] $numbers
     * @param  int     $n       n-point moving average
     *
     * @return float[] of averages for each n-point time period
     */
    public static function simpleMovingAverage(array $numbers, int $n): array
    {
        $m   = \count($numbers);
        $SMA = [];

        // Counters
        $new       = $n; // New value comes into the sum
        $drop      = 0;  // Old value drops out
        $yesterday = 0;  // Yesterday's SMA

        // Base case: initial average
        $SMA[] = \array_sum(\array_slice($numbers, 0, $n)) / $n;

        // Calculating successive values: New value comes in; old value drops out
        while ($new < $m) {
            $SMA[] = $SMA[$yesterday] + ($numbers[$new] / $n) - ($numbers[$drop] / $n);
            $drop++;
            $yesterday++;
            $new++;
        }

        return $SMA;
    }

    /**
     * Cumulative moving average (CMA)
     *
     * Base case for initial average:
     *         x₀
     *  CMA₀ = --
     *         1
     *
     * Standard case:
     *         xᵢ + (i * CMAᵢ₋₁)
     *  CMAᵢ = -----------------
     *              i + 1
     *
     * @param  float[] $numbers
     *
     * @return float[] of cumulative averages
     */
    public static function cumulativeMovingAverage(array $numbers): array
    {
        $m   = \count($numbers);
        $CMA = [];

        // Base case: first average is just itself
        $CMA[] = $numbers[0];

        for ($i = 1; $i < $m; $i++) {
            $CMA[] = (($numbers[$i]) + ($CMA[$i - 1] * $i)) / ($i + 1);
        }

        return $CMA;
    }

    /**
     * Weighted n-point moving average (WMA)
     *
     * Similar to simple n-point moving average,
     * however, each n-point has a weight associated with it,
     * and instead of dividing by n, we divide by the sum of the weights.
     *
     * Each weighted average = ∑(weighted values) / ∑(weights)
     *
     * @param  array  $numbers
     * @param  int    $n       n-point moving average
     * @param  array  $weights Weights for each n points
     *
     * @return array of averages
     *
     * @throws Exception\BadDataException if number of weights is not equal to number of n-points
     */
    public static function weightedMovingAverage(array $numbers, int $n, array $weights): array
    {
        if (\count($weights) !== $n) {
            throw new Exception\BadDataException('Number of weights must equal number of n-points');
        }

        $m   = \count($numbers);
        $∑w  = \array_sum($weights);
        $WMA = [];

        for ($i = 0; $i <= $m - $n; $i++) {
            $∑wp   = \array_sum(Map\Multi::multiply(\array_slice($numbers, $i, $n), $weights));
            $WMA[] = $∑wp / $∑w;
        }

        return $WMA;
    }

    /**
     * Exponential moving average (EMA)
     *
     * The start of the EPA is seeded with the first data point.
     * Then each day after that:
     *  EMAtoday = α⋅xtoday + (1-α)EMAyesterday
     *
     *   where
     *    α: coefficient that represents the degree of weighting decrease, a constant smoothing factor between 0 and 1.
     *
     * @param array  $numbers
     * @param int    $n       Length of the EPA
     *
     * @return array of exponential moving averages
     */
    public static function exponentialMovingAverage(array $numbers, int $n): array
    {
        $m   = \count($numbers);
        $α   = 2 / ($n + 1);
        $EMA = [];

        // Start off by seeding with the first data point
        $EMA[] = $numbers[0];

        // Each day after: EMAtoday = α⋅xtoday + (1-α)EMAyesterday
        for ($i = 1; $i < $m; $i++) {
            $EMA[] = ($α * $numbers[$i]) + ((1 - $α) * $EMA[$i - 1]);
        }

        return $EMA;
    }

    /**************************************************************************
     * Averages of two numbers
     **************************************************************************/

    /**
     * Arithmetic-Geometric mean
     *
     * First, compute the arithmetic and geometric means of x and y, calling them a₁ and g₁ respectively.
     * Then, use iteration, with a₁ taking the place of x and g₁ taking the place of y.
     * Both a and g will converge to the same mean.
     * https://en.wikipedia.org/wiki/Arithmetic%E2%80%93geometric_mean
     *
     * x and y ≥ 0
     * If x or y = 0, then agm = 0
     * If x or y < 0, then NaN
     *
     * @param  float $x
     * @param  float $y
     *
     * @return float
     */
    public static function arithmeticGeometricMean(float $x, float $y): float
    {
        // x or y < 0 = NaN
        if ($x < 0 || $y < 0) {
            return \NAN;
        }

        // x or y zero = 0
        if ($x == 0 || $y == 0) {
            return 0;
        }

        // Standard case x and y > 0
        [$a, $g] = [$x, $y];
        for ($i = 0; $i <= 10; $i++) {
            [$a, $g] = [self::mean([$a, $g]), self::geometricMean([$a, $g])];
        }
        return $a;
    }

    /**
     * Convenience method for arithmeticGeometricMean
     *
     * @param  float $x
     * @param  float $y
     *
     * @return float
     */
    public static function agm(float $x, float $y): float
    {
        return self::arithmeticGeometricMean($x, $y);
    }

    /**
     * Logarithmic mean
     * A function of two non-negative numbers which is equal to their
     * difference divided by the logarithm of their quotient.
     *
     * https://en.wikipedia.org/wiki/Logarithmic_mean
     *
     *  Mlm(x, y) = 0 if x = 0 or y = 0
     *              x if x = y
     *  otherwise:
     *                y - x
     *             -----------
     *             ln y - ln x
     *
     * @param  float $x
     * @param  float $y
     *
     * @return float
     */
    public static function logarithmicMean(float $x, float $y): float
    {
        if ($x == 0 || $y == 0) {
            return 0;
        }
        if ($x == $y) {
            return $x;
        }

        return ($y - $x) / (\log($y) - \log($x));
    }

    /**
     * Heronian mean
     * https://en.wikipedia.org/wiki/Heronian_mean
     *            __
     * H = ⅓(A + √AB + B)
     *
     * @param  float $A
     * @param  float $B
     *
     * @return float
     */
    public static function heronianMean(float $A, float $B): float
    {
        return 1 / 3 * ($A + \sqrt($A * $B) + $B);
    }

    /**
     * Identric mean
     * https://en.wikipedia.org/wiki/Identric_mean
     *                 ____
     *          1     / xˣ
     * I(x,y) = - ˣ⁻ʸ/  --
     *          ℯ   √   yʸ
     *
     * @param  float $x
     * @param  float $y
     *
     * @return float
     *
     * @throws Exception\OutOfBoundsException if x or y is ≤ 0
     */
    public static function identricMean(float $x, float $y): float
    {
        // x and y must be positive
        if ($x <= 0 || $y <= 0) {
            throw new Exception\OutOfBoundsException('x and y must be positive real numbers.');
        }

        // Special case: x if x = y
        if ($x == $y) {
            return $x;
        }

        // Standard case
        $ℯ  = \M_E;
        $xˣ = $x ** $x;
        $yʸ = $y ** $y;

        return 1 / $ℯ * \pow($xˣ / $yʸ, 1 / ($x - $y));
    }

    /**
     * Get a report of all the averages over a list of numbers
     * Includes mean, median mode, geometric mean, harmonic mean, quardratic mean
     *
     * @param array $numbers
     *
     * @return array [ mean, median, mode, geometric_mean, harmonic_mean,
     *                 contraharmonic_mean, quadratic_mean, trimean, iqm, cubic_mean ]
     *
     * @throws Exception\BadDataException
     * @throws Exception\OutOfBoundsException
     */
    public static function describe(array $numbers): array
    {
        return [
            'mean'                => self::mean($numbers),
            'median'              => self::median($numbers),
            'mode'                => self::mode($numbers),
            'geometric_mean'      => self::geometricMean($numbers),
            'harmonic_mean'       => self::harmonicMean($numbers),
            'contraharmonic_mean' => self::contraharmonicMean($numbers),
            'quadratic_mean'      => self::quadraticMean($numbers),
            'trimean'             => self::trimean($numbers),
            'iqm'                 => self::iqm($numbers),
            'cubic_mean'          => self::cubicMean($numbers),
        ];
    }
}
