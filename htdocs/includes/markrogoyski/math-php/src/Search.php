<?php

namespace MathPHP;

use MathPHP\Exception;

/**
 * Search
 * Various functions to find specific indices in an array.
 */
class Search
{
    /**
     * Search Sorted
     * Find the array indices where items should be inserted to maintain sorted order.
     *
     * Inspired by and similar to Python NumPy's searchsorted
     *
     * @param float[]|int[] $haystack Sorted array with standard increasing numerical array keys
     * @param float         $needle   Item wanting to insert
     *
     * @return int Index of where you would insert the needle and maintain sorted order
     */
    public static function sorted(array $haystack, float $needle): int
    {
        if (empty($haystack)) {
            return 0;
        }

        $index = 0;
        foreach ($haystack as $i => $val) {
            if ($needle > $val) {
                $index++;
            } else {
                return $index;
            }
        }

        return $index;
    }

    /**
     * ArgMax
     * Find the array index of the maximum value.
     *
     * In case of the maximum value appearing multiple times, the index of the first occurrence is returned.
     * In the case NAN is present, the index of the first NAN is returned.
     *
     * Inspired by and similar to Python NumPy's argmax
     *
     * @param float[]|int[] $values
     *
     * @return int Index of the first occurrence of the maximum value
     *
     * @throws Exception\BadDataException if the array of values is empty
     */
    public static function argMax(array $values): int
    {
        if (empty($values)) {
            throw new Exception\BadDataException('Cannot find the argMax of an empty array');
        }

        // Special case: NAN wins if present
        $nanPresent = \array_filter(
            $values,
            function ($value) {
                return \is_float($value) && \is_nan($value);
            }
        );
        if (\count($nanPresent) > 0) {
            foreach ($values as $i => $v) {
                if (\is_nan($v)) {
                    return $i;
                }
            }
        }

        // Standard case: Find max and return index
        return self::baseArgMax($values);
    }

    /**
     * NanArgMax
     * Find the array index of the maximum value, ignoring NANs
     *
     * In case of the maximum value appearing multiple times, the index of the first occurrence is returned.
     *
     * Inspired by and similar to Python NumPy's nanargmax
     *
     * @param float[]|int[] $values
     *
     * @return int Index of the first occurrence of the maximum value
     *
     * @throws Exception\BadDataException if the array of values is empty
     * @throws Exception\BadDataException if the array only contains NANs
     */
    public static function nanArgMax(array $values): int
    {
        if (empty($values)) {
            throw new Exception\BadDataException('Cannot find the argMax of an empty array');
        }

        $valuesWithoutNans = \array_filter(
            $values,
            function ($value) {
                return !\is_nan($value);
            }
        );
        if (\count($valuesWithoutNans) === 0) {
            throw new Exception\BadDataException('Array of all NANs has no nanArgMax');
        }

        return self::baseArgMax($valuesWithoutNans);
    }

    /**
     * Base argMax calculation
     * Find the array index of the maximum value.
     *
     * In case of the maximum value appearing multiple times, the index of the first occurrence is returned.
     *
     * @param float[]|int[] $values
     *
     * @return int Index of the first occurrence of the maximum value
     */
    private static function baseArgMax(array $values): int
    {
        $max = \max($values);
        foreach ($values as $i => $v) {
            if ($v === $max) {
                return $i;
            }
        }
    }

    /**
     * ArgMin
     * Find the array index of the minimum value.
     *
     * In case of the minimum value appearing multiple times, the index of the first occurrence is returned.
     * In the case NAN is present, the index of the first NAN is returned.
     *
     * Inspired by and similar to Python NumPy's argmin
     *
     * @param float[]|int[] $values
     *
     * @return int Index of the first occurrence of the minimum value
     *
     * @throws Exception\BadDataException if the array of values is empty
     */
    public static function argMin(array $values): int
    {
        if (empty($values)) {
            throw new Exception\BadDataException('Cannot find the argMin of an empty array');
        }

        // Special case: NAN wins if present
        $nanPresent = \array_filter(
            $values,
            function ($value) {
                return \is_float($value) && \is_nan($value);
            }
        );
        if (\count($nanPresent) > 0) {
            foreach ($values as $i => $v) {
                if (\is_nan($v)) {
                    return $i;
                }
            }
        }

        // Standard case: Find max and return index
        return self::baseArgMin($values);
    }

    /**
     * NanArgMin
     * Find the array index of the minimum value, ignoring NANs
     *
     * In case of the minimum value appearing multiple times, the index of the first occurrence is returned.
     *
     * Inspired by and similar to Python NumPy's nanargin
     *
     * @param float[]|int[] $values
     *
     * @return int Index of the first occurrence of the minimum value
     *
     * @throws Exception\BadDataException if the array of values is empty
     * @throws Exception\BadDataException if the array only contains NANs
     */
    public static function nanArgMin(array $values): int
    {
        if (empty($values)) {
            throw new Exception\BadDataException('Cannot find the nanArgMin of an empty array');
        }

        $valuesWithoutNans = \array_filter(
            $values,
            function ($value) {
                return !\is_nan($value);
            }
        );
        if (\count($valuesWithoutNans) === 0) {
            throw new Exception\BadDataException('Array of all NANs has no nanArgMax');
        }

        return self::baseArgMin($valuesWithoutNans);
    }

    /**
     * Base argMin calculation
     * Find the array index of the minimum value.
     *
     * In case of the maximum value appearing multiple times, the index of the first occurrence is returned.
     *
     * @param float[]|int[] $values
     *
     * @return int Index of the first occurrence of the minimum value
     */
    private static function baseArgMin(array $values): int
    {
        $max = \min($values);
        foreach ($values as $i => $v) {
            if ($v === $max) {
                return $i;
            }
        }
    }

    /**
     * NonZero
     * Find the array indices of the scalar values that are non-zero.
     *
     * Considered 0:
     *  int 0, -0
     *  float 0.0, -0.0
     *  string 0, -0, 0.0, -0.0
     *  bool false
     *
     * Inspired by Python NumPy's nonzero
     *
     * @param float[]|int[] $values
     *
     * @return int[]
     */
    public static function nonZero(array $values): array
    {
        $indices = [];
        foreach ($values as $i => $v) {
            if (!\is_scalar($v)) {
                continue;
            }
            if ($v != 0) {
                $indices[] = $i;
            }
        }

        return $indices;
    }
}
