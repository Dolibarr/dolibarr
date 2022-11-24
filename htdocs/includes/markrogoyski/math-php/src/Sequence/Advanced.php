<?php

namespace MathPHP\Sequence;

use MathPHP\Exception\OutOfBoundsException;
use MathPHP\NumberTheory\Integer;

/**
 * Advanced integer sequences
 *  - Fibonacci
 *  - Lucas numbers
 *  - Pell numbers
 *  - Triangular numbers
 *  - Pentagonal numbers
 *  - Hexagonal numbers
 *  - Heptagonal numbers
 *  - Look-and-say sequence
 *  - Lazy caterer's sequence
 *  - Magic squares sequence
 *  - Perfect powers
 *  - Not perfect powers
 *
 * All sequences return an array of numbers in the sequence.
 * The array index starting point depends on the sequence type.
 */
class Advanced
{
    /**
     * Fibonacci numbers
     * Every number is the sum of the two preceding ones.
     * https://en.wikipedia.org/wiki/Fibonacci_number
     *
     * F₀ = 0
     * F₁ = 1
     * Fᵢ = Fᵢ₋₁ + Fᵢ₋₂
     *
     * Example:
     *  n = 6
     *  Sequence:    0, 1, 1, 2, 3, 5
     *  Array index: 0, 1, 2, 3, 4, 5
     *
     * @param  int $n How many numbers in the sequence
     *
     * @return array Indexed from 0
     */
    public static function fibonacci(int $n): array
    {
        $fibonacci = [];

        // Bad input; return empty list
        if ($n <= 0) {
            return $fibonacci;
        }

        // Base case (n = 1): F₀ = 0
        $fibonacci[] = 0;
        if ($n === 1) {
            return $fibonacci;
        }

        // Base case (n = 2): F₀ = 0, F₁ = 1
        $fibonacci[] = 1;
        if ($n === 2) {
            return $fibonacci;
        }

        // Standard iterative case (n > 1): Fᵢ = Fᵢ₋₁ + Fᵢ₋₂
        for ($i = 2; $i < $n; $i++) {
            $fibonacci[] = $fibonacci[$i - 1] + $fibonacci[$i - 2];
        }

        return $fibonacci;
    }

    /**
     * Lucas numbers
     * Every number is the sum of its two immediate previous terms.
     * Similar to Fibonacci numbers except the base cases differ.
     * https://en.wikipedia.org/wiki/Lucas_number
     *
     * L₀ = 2
     * L₁ = 1
     * Lᵢ = Lᵢ₋₁ + Lᵢ₋₂
     *
     * Example:
     *  n = 6
     *  Sequence:    2, 1, 3, 4, 7, 11
     *  Array index: 0, 1, 2, 3, 4, 5
     *
     * @param  int $n How many numbers in the sequence
     *
     * @return array Indexed from 0
     */
    public static function lucasNumber(int $n): array
    {
        $lucas = [];

        // Bad input; return empty list
        if ($n <= 0) {
            return $lucas;
        }

        // Base case (n = 1): L₀ = 2
        $lucas[] = 2;
        if ($n === 1) {
            return $lucas;
        }

        // Base case (n = 2): , L₀ = 2L₁ = 1
        $lucas[] = 1;
        if ($n === 2) {
            return $lucas;
        }

        // Standard iterative case: Lᵢ = Lᵢ₋₁ + Lᵢ₋₂
        for ($i = 2; $i < $n; $i++) {
            $lucas[$i] = $lucas[$i - 1] + $lucas[$i - 2];
        }

        return $lucas;
    }

    /**
     * Pell numbers
     * The denominators of the closest rational approximations to the square root of 2.
     * https://en.wikipedia.org/wiki/Pell_number
     *
     * P₀ = 0
     * P₁ = 1
     * Pᵢ = 2Pᵢ₋₁ + Pᵢ₋₂
     *
     * Example:
     *  n = 6
     *  Sequence:    0, 1, 2, 5, 12, 29
     *  Array index: 0, 1, 2, 3, 4,  5
     *
     * @param  int $n How many numbers in the sequence
     *
     * @return array Indexed from 0
     */
    public static function pellNumber(int $n): array
    {
        $pell = [];

        // Bad input; return empty list
        if ($n <= 0) {
            return $pell;
        }

        // Base case (n = 1): P₀ = 0
        $pell[] = 0;
        if ($n === 1) {
            return $pell;
        }

        // Base case (n = 2): P₀ = 0, P₁ = 1
        $pell[] = 1;
        if ($n === 2) {
            return $pell;
        }

        // Standard iterative case: Pᵢ = 2Pᵢ₋₁ + Pᵢ₋₂
        for ($i = 2; $i < $n; $i++) {
            $pell[$i] = 2 * $pell[$i - 1] + $pell[$i - 2];
        }

        return $pell;
    }

    /**
     * Triangular numbers
     * Figurate numbers that represent equilateral triangles.
     * https://en.wikipedia.org/wiki/Triangular_number
     *
     *      n(n + 1)
     * Tn = --------
     *         2
     *
     * Example:
     *  n = 6
     *  Sequence:    1, 3, 6, 10, 15, 21
     *  Array index: 1, 2, 3,  4,  5,  6
     *
     * @param  int $n How many numbers in the sequence
     *
     * @return array Indexed from 1
     */
    public static function triangularNumber(int $n): array
    {
        $triangular = [];
        // Bad input; return empty list
        if ($n <= 0) {
            return $triangular;
        }

        // Standard case for pn: n(n + 1) / 2
        for ($i = 1; $i <= $n; $i++) {
            $triangular[$i] = ($i * ($i + 1)) / 2;
        }

        return $triangular;
    }

    /**
     * Pentagonal numbers
     * Figurate numbers that represent pentagons.
     * https://en.wikipedia.org/wiki/Pentagonal_number
     *
     *      3n² - n
     * pn = -------
     *         2
     *
     * Example:
     *  n = 6
     *  Sequence:    1, 5, 12, 22, 35, 51
     *  Array index: 1, 2, 3,  4,  5,  6
     *
     * @param  int $n How many numbers in the sequence
     *
     * @return array Indexed from 1
     */
    public static function pentagonalNumber(int $n): array
    {
        $pentagonal = [];
        // Bad input; return empty list
        if ($n <= 0) {
            return $pentagonal;
        }

        // Standard case for pn: (3n² - n) / 2
        for ($i = 1; $i <= $n; $i++) {
            $pentagonal[$i] = (3 * ($i ** 2) - $i) / 2;
        }

        return $pentagonal;
    }

    /**
     * Hexagonal numbers
     * Figurate numbers that represent hexagons.
     * https://en.wikipedia.org/wiki/Hexagonal_number
     *
     *      2n × (2n - 1)
     * hn = -------------
     *           2
     *
     * Example:
     *  n = 6
     *  Sequence:    1, 6, 15, 28, 45, 66
     *  Array index: 1, 2, 3,  4,  5,  6
     *
     * @param  int $n How many numbers in the sequence
     *
     * @return array Indexed from 1
     */
    public static function hexagonalNumber(int $n): array
    {
        $hexagonal = [];

        // Bad input; return empty list
        if ($n <= 0) {
            return $hexagonal;
        }

        // Standard case for hn: (2n × (2n - 1)) / 2
        for ($i = 1; $i <= $n; $i++) {
            $hexagonal[$i] = ((2 * $i) * (2 * $i - 1)) / 2;
        }

        return $hexagonal;
    }

    /**
     * Heptagonal numbers
     * Figurate numbers that represent heptagons.
     * https://en.wikipedia.org/wiki/Heptagonal_number
     *
     *      5n² - 3n
     * Hn = --------
     *         2
     *
     * Example:
     *  n = 6
     *  Sequence:    1, 4, 7, 13, 18, 27
     *  Array index: 1, 2, 3, 4,  5,  6
     *
     * @param  int $n How many numbers in the sequence
     *
     * @return array Indexed from 1
     */
    public static function heptagonalNumber(int $n): array
    {
        $heptagonal = [];

        // Bad input; return empty list
        if ($n <= 0) {
            return $heptagonal;
        }

        // Standard case for Hn: (5n² - 3n) / 2
        for ($i = 1; $i <= $n; $i++) {
            $heptagonal[$i] = ((5 * $i ** 2) - (3 * $i)) / 2;
        }

        return $heptagonal;
    }

    /**
     * Look-and-say sequence (describe the previous term!)
     * (Sequence A005150 in the OEIS)
     *
     * 1, 11, 21, 1211, 111221, 312211, 13112221, 1113213211, ...
     *
     * To generate a member of the sequence from the previous member,
     * read off the digits of the previous member, counting the number of
     * digits in groups of the same digit.
     *
     * 1 is read off as "one 1" or 11.
     * 11 is read off as "two 1s" or 21.
     * 21 is read off as "one 2, then one 1" or 1211.
     * 1211 is read off as "one 1, one 2, then two 1s" or 111221.
     * 111221 is read off as "three 1s, two 2s, then one 1" or 312211.
     *
     * https://en.wikipedia.org/wiki/Look-and-say_sequence
     * https://oeis.org/A005150
     *
     * Example:
     *  n = 6
     *  Sequence:    1, 11, 21, 1211, 111221, 312211
     *  Array index: 1, 2,  3,  4,    5,      6
     *
     * @param  int $n How many numbers in the sequence
     *
     * @return array of strings indexed from 1
     */
    public static function lookAndSay(int $n): array
    {
        if ($n <= 0) {
            return [];
        }

        // Initialize
        $list     = [1 => '1'];
        $previous = '1';

        // Base case
        if ($n === 1) {
            return $list;
        }

        for ($i = 2; $i <= $n; $i++) {
            $sequence = "";
            $count    = 1;
            $len      = \strlen($previous);

            for ($j = 1; $j < $len; $j++) {
                if (\substr($previous, $j, 1) === \substr($previous, $j - 1, 1)) {
                    $count++;
                } else {
                    $sequence .= $count . \substr($previous, $j - 1, 1);
                    $count = 1;
                }
            }

            $sequence .= $count . \substr($previous, $j - 1, 1);
            $previous = $sequence;
            $list[$i] = $sequence;
        }

        return $list;
    }

    /**
     * Lazy caterer's sequence (central polygonal numbers)
     * Describes the maximum number of pieces of a circle that can be made with
     * a given number of straight cuts.
     *
     * https://en.wikipedia.org/wiki/Lazy_caterer%27s_sequence
     * https://oeis.org/A000124
     *
     *     n² + n + 2
     * p = ----------
     *          2
     *
     * Using binomial coefficients:
     *
     *         (n + 1)   (n)   (n)   (n)
     * p = 1 + (     ) = ( ) + ( ) + ( )
     *         (  2 )    (0)   (1)   (2)
     *
     * Example:
     *  n = 6
     *  Sequence:    1, 2, 4, 7, 11, 16, 22
     *  Array index: 0, 1, 2, 3, 4,  5,  6
     *
     * @param int $n How many numbers in the sequence
     *
     * @return array
     */
    public static function lazyCaterers(int $n): array
    {
        if ($n < 0) {
            return [];
        }

        $p = [];

        for ($i = 0; $i < $n; $i++) {
            $p[] = ($i ** 2 + $i + 2) / 2;
        }

        return $p;
    }

    /**
     * Magic squares series
     * The constant sum in every row, column and diagonal of a magic square is
     * called the magic constant or magic sum, M.
     *
     * https://oeis.org/A006003
     * https://edublognss.wordpress.com/2013/04/16/famous-mathematical-sequences-and-series/
     *
     *     n(n² + 1)
     * M = ---------
     *         2
     *
     * Example:
     *  n = 6
     *  Sequence:    0, 1, 5, 15, 34, 65
     *  Array index: 0, 1, 2, 3,  4,  5,
     *
     * @param int $n How many numbers in the sequence
     *
     * @return array
     */
    public static function magicSquares(int $n): array
    {
        if ($n < 0) {
            return [];
        }

        $M = [];

        for ($i = 0; $i < $n; $i++) {
            $M[] = ($i * ($i ** 2 + 1)) / 2;
        }

        return $M;
    }

    private const PERFECT_NUMBERS = [
        6, 28, 496, 8128, 33550336, 8589869056, 137438691328, 2305843008139952128, 2658455991569831744654692615953842176, 191561942608236107294793378084303638130997321548169216
    ];

    /**
     * Perfect numbers
     * @see https://oeis.org/A000396
     *
     * Example
     *  n = 5
     *  Sequence:    6, 28, 496, 8128, 33550336
     *  Array index: 0, 1,  2,   3,    4
     *
     * @param  int $n
     *
     * @return array
     *
     * @throws OutOfBoundsException
     */
    public static function perfectNumbers(int $n): array
    {
        if ($n <= 0) {
            return [];
        }

        if ($n <= 10) {
            return \array_slice(self::PERFECT_NUMBERS, 0, $n);
        }

        throw new OutOfBoundsException("Perfect numbers beyond the tenth are too large to compute");
    }

    /**
     * Perfect powers
     * https://en.wikipedia.org/wiki/Perfect_power
     *
     * mᵏ = n where m > 1 and k >= 2.
     * Without duplication.
     * https://oeis.org/A001597 (similar)
     *
     * Example:
     *  n = 6
     *  Sequence:    4, 8, 9, 16, 25, 27
     *  Array index: 0, 1, 2, 3,  4,  5
     *
     * @param  int $n How many numbers in the sequence
     *
     * @return array
     */
    public static function perfectPowers(int $n): array
    {
        $pp = [];

        if ($n <= 0) {
            return $pp;
        }

        $i = 2;
        while ($n > 0) {
            if (Integer::isPerfectPower($i)) {
                $pp[] = $i;
                $n--;
            }
            $i++;
        }

        return $pp;
    }

    /**
     * Numbers that are not perfect powers
     * https://en.wikipedia.org/wiki/Perfect_power
     *
     * https://oeis.org/A007916
     *
     * Example:
     *  n = 6
     *  Sequence:    2, 3, 5, 6, 7, 10
     *  Array index: 0, 1, 2, 3, 4, 5
     *
     * @param  int $n How many numbers in the sequence
     *
     * @return array
     */
    public static function notPerfectPowers(int $n): array
    {
        $npp = [];

        if ($n <= 0) {
            return $npp;
        }

        $i = 2;
        while ($n > 0) {
            if (!Integer::isPerfectPower($i)) {
                $npp[] = $i;
                $n--;
            }
            $i++;
        }

        return $npp;
    }

    /**
     * Prime numbers up to n.
     * https://oeis.org/A000040
     *
     * Algorithm: Sieve of Eratosthenes
     * Let A be an array of boolean values, indexed by integers 2 to n, initially all set to true.
     * for i = 2, 3, 4, ..., not exceeding √n:
     *   if A[i] is true:
     *      for j = i², i²+i, i²+2i, i²+3i, ..., not exceeding n:
     *         A[j] := false.
     *
     * Output: all i such that A[i] is true.
     *
     * https://en.wikipedia.org/wiki/Sieve_of_Eratosthenes
     *
     * Example:
     *  n = 20
     *  Sequence:    2, 3, 5, 7, 11, 13, 17, 19
     *  Array index: 0, 1, 2, 3, 4,  5,  6,  7
     *
     * @param  int   $n Prime numbers up to this n
     *
     * @return array
     */
    public static function primesUpTo(int $n): array
    {
        if ($n < 2) {
            return [];
        }

        $primes = \array_fill_keys(\range(2, $n), true);
        $√n     = \ceil(\sqrt($n));

        for ($i = 2; $i <= $√n; $i++) {
            if ($primes[$i] === true) {
                $i² = $i ** 2;
                for ($j = $i²; $j <= $n; $j += $i) {
                    $primes[$j] = false;
                }
            }
        }

        return \array_keys(\array_filter($primes));
    }
}
