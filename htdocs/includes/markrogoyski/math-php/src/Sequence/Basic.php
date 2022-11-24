<?php

namespace MathPHP\Sequence;

use MathPHP\Arithmetic;
use MathPHP\Exception;

/**
 * Basic integer sequences
 *  - Arithmetic progression
 *  - Geometric progression
 *  - Square numbers
 *  - Cubic numbers
 *  - Powers of 2
 *  - Powers of 10
 *  - Factorial
 *
 * All sequences return an array of numbers in the sequence.
 * The array index starting point depends on the sequence type.
 */
class Basic
{
    /**
     * Arithmetic progression
     * A sequence of numbers such that the difference between the consecutive terms is constant.
     * https://en.wikipedia.org/wiki/Arithmetic_progression
     *
     * Example:
     *  n  = 10
     *  d  = 2
     *  a₁ = 1
     *  Sequence:    1, 3, 5, 7, 9, 11, 13, 15, 17, 19
     *  Array index: 1, 2, 3, 4, 5, 6,  7,  8,  9,  10
     *
     * @param int $n  How many numbers in the sequence
     * @param int $d  Difference between the elements of the sequence
     * @param int $a₁ Starting number for the sequence
     *
     * @return array Indexed from 1
     */
    public static function arithmeticProgression(int $n, int $d, int $a₁): array
    {
        if ($n <= 0) {
            return [];
        }

        $progression[1] = $a₁;
        for ($i = 1; $i < $n; $i++) {
            $progression[$i + 1] = $progression[$i] + $d;
        }

        return $progression;
    }

    /**
     * Geometric progression
     * A sequence of numbers where each term after the first is found by multiplying
     * the previous one by a fixed, non-zero number called the common ratio.
     * https://en.wikipedia.org/wiki/Geometric_progression
     *
     * an = arⁿ⁻¹
     *
     * Example:
     *  n = 4
     *  a = 2
     *  r = 3
     *  Sequence:    2(3)⁰, 2(3)¹, 2(3)², 2(3)³
     *  Sequence:    2,     6,     18,    54
     *  Array index: 0      1      2      3
     *
     * @param  int    $n How many numbers in the sequence
     * @param  number $a Scalar value
     * @param  number $r Common ratio
     *
     * @return array Indexed from 0 (indexes are powers of common ratio)
     *
     * @throws Exception\BadParameterException
     */
    public static function geometricProgression(int $n, $a, $r): array
    {
        if ($r === 0) {
            throw new Exception\BadParameterException('Common ratio r cannot be 0');
        }

        $progression = [];
        if ($n < 0) {
            return $progression;
        }

        for ($i = 0; $i < $n; $i++) {
            $progression[] = $a * $r ** $i;
        }

        return $progression;
    }

    /**
     * Square numbers
     * https://en.wikipedia.org/wiki/Square_number
     *
     * n²
     *
     * Example:
     *  n = 5
     *  Sequence:    0², 1², 2², 3², 4²
     *  Sequence:    0,  1,  4,  9,  16
     *  Array index: 0,  1,  2,  3,  4
     *
     * @param  int $n How many numbers in the sequence
     *
     * @return array Indexed from 0 (indexes are the base number which is raised to the power of 2)
     */
    public static function squareNumber(int $n): array
    {
        $squares = [];
        if ($n <= 0) {
            return $squares;
        }

        for ($i = 0; $i < $n; $i++) {
            $squares[] = $i ** 2;
        }

        return $squares;
    }

    /**
     * Cubic numbers
     * https://en.wikipedia.org/wiki/Cube_(algebra)
     *
     * n³
     *
     * Example:
     *  n = 5
     *  Sequence:    0³, 1³, 2³, 3³, 4³
     *  Sequence:    0,  1,  8,  27, 64
     *  Array index: 0,  1,  2,  3,  4
     *
     * @param  int $n How many numbers in the sequence
     *
     * @return array Indexed from 0 (indexes are the base number which is raised to the power of 3)
     */
    public static function cubicNumber(int $n): array
    {
        $cubes = [];
        if ($n <= 0) {
            return $cubes;
        }

        for ($i = 0; $i < $n; $i++) {
            $cubes[] = $i ** 3;
        }

        return $cubes;
    }

    /**
     * Powers of two
     * https://en.wikipedia.org/wiki/Power_of_two
     *
     * 2ⁿ
     *
     * Example:
     *  n = 5
     *  Sequence:    2⁰, 2¹, 2², 2³, 2⁴
     *  Sequence:    1,  2,  4,  8,  16
     *  Array index: 0,  1,  2,  3,  4
     *
     * @param  int $n How many numbers in the sequence
     *
     * @return array Indexed from 0 (indexes are the power 2 is raised to)
     */
    public static function powersOfTwo(int $n): array
    {
        $powers_of_2 = [];
        if ($n <= 0) {
            return $powers_of_2;
        }

        for ($i = 0; $i < $n; $i++) {
            $powers_of_2[] = 2 ** $i;
        }

        return $powers_of_2;
    }

    /**
     * Powers of ten
     * https://en.wikipedia.org/wiki/Power_of_10
     *
     * Example:
     *  n = 5
     *  Sequence:    10⁰, 10¹, 10², 10³,  10⁴
     *  Sequence:    1,   10,  100, 1000, 10000
     *  Array index: 0,   1,   2,   3,    4
     *
     * @param  int $n How many numbers in the sequence
     *
     * @return array Indexed from 0 (indexes are the power 10 is raised to)
     */
    public static function powersOfTen(int $n): array
    {
        $powers_of_10 = [];
        if ($n <= 0) {
            return $powers_of_10;
        }

        for ($i = 0; $i < $n; $i++) {
            $powers_of_10[] = 10 ** $i;
        }

        return $powers_of_10;
    }

    /**
     * Factorial
     * https://en.wikipedia.org/wiki/Factorial
     *
     * Example:
     *  n = 5
     *  Sequence:    0!, 1!, 2!, 3!, 4!
     *  Sequence:    1,  1,  2,  6,  24
     *  Array index: 0,  1,  2,  3,  4
     *
     * @param  int $n How many numbers in the sequence
     *
     * @return array Indexed from 0 (indexes are the n!)
     */
    public static function factorial(int $n): array
    {
        if ($n <= 0) {
            return [];
        }

        $factorial = [1];
        if ($n === 1) {
            return $factorial;
        }

        for ($i = 1; $i < $n; $i++) {
            $factorial[] = $i * $factorial[$i - 1];
        }

        return $factorial;
    }

    /**
     * Digit sum (sum of digits)
     * https://en.wikipedia.org/wiki/Digit_sum
     * https://oeis.org/A007953
     *
     * Example
     *  n = 11
     *  Sequence:    0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 1
     *  Array index: 0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 1
     *
     * @param  int $n How many numbers in the sequence
     *
     * @return array Indexed from 0 (indexes are the n in the digitSum(n))
     */
    public static function digitSum(int $n): array
    {
        if ($n <= 0) {
            return [];
        }

        $digit_sums = [];
        for ($i = 0; $i < $n; $i++) {
            $digit_sums[] = Arithmetic::digitSum($i);
        }

        return $digit_sums;
    }

    /**
     * Digital root (iterated digit sum, repeated digital sum)
     * https://en.wikipedia.org/wiki/Digital_root
     * http://oeis.org/A010888
     *
     * Example
     *  n = 11
     *  Sequence:    0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 1
     *  Array index: 0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 1
     *
     * @param  int $n How many numbers in the sequence
     *
     * @return array Indexed from 0 (indexes are the n in the digitSum(n))
     */
    public static function digitalRoot(int $n): array
    {
        if ($n <= 0) {
            return [];
        }

        $digital_roots = [];
        for ($i = 0; $i < $n; $i++) {
            $digital_roots[] = Arithmetic::digitalRoot($i);
        }

        return $digital_roots;
    }
}
