<?php

namespace MathPHP\Probability;

use MathPHP\Exception;

/**
 * Combinatorics
 *  - Factorials
 *    - Factorial
 *    - Double factorial
 *    - Rising factorial
 *    - Falling factorial
 *    - Subfactorial
 *  - Permutations and Combinations
 *    - Permutations nPn
 *    - Permutations nPk
 *    - Combinations without repetition nCk
 *    - Combinations with repetition nC′k
 *    - Central binomial coefficient
 *  - Other Combinatorics
 *    - Catalan number
 *    - Lah number
 *    - Multinomial coefficient
 */
class Combinatorics
{
    /** @var bool Combinations with repetition */
    public const REPETITION = true;

    /**************************************************************************
     * Factorials
     *************************************************************************/

    /**
     * Factorial (iterative)
     * Represents the number of ways to arrange n things (permutations)
     * n! = n(n - 1)(n - 2) ・・・ (n - (n - 1))
     *
     * @param  int $n
     *
     * @return float number of permutations of n
     *
     * @throws Exception\OutOfBoundsException if n < 0
     */
    public static function factorial(int $n): float
    {
        if ($n < 0) {
            throw new Exception\OutOfBoundsException('Cannot compute factorial of a negative number.');
        }
        $factorial = 1;
        while ($n > 1) {
            $factorial *= $n;
            --$n;
        }
        return $factorial;
    }

    /**
     * Double factorial (iterative)
     * Also known as semifactorial
     *
     * The product of all the integers from 1 up to some non-negative integer n
     * that have the same parity as n. Denoted by n!!
     *
     * n‼︎ = n(n - 2)(n - 4) ・・・
     *
     * For even n:
     *       n/2
     * n‼︎ =  ∏ (2k) = n(n - 2) ・・・ 2
     *       k=1
     *
     * For odd n:
     *     (n+1)/2
     * n‼︎ =  ∏ (2k - 1) = n(n - 2) ・・・ 1
     *       k=1
     *
     * 0‼︎ = 1
     *
     * @param  int $n
     *
     * @return float
     *
     * @throws Exception\OutOfBoundsException if n < 0
     */
    public static function doubleFactorial(int $n): float
    {
        if ($n < 0) {
            throw new Exception\OutOfBoundsException('Cannot compute double factorial of a negative number.');
        }

        // Zero base case
        if ($n === 0) {
            return 1;
        }

        // Even and odd initialization base cases: odd = 1, even = 2
        if ($n % 2 == 0) {
            $n‼︎ = 2;
        } else {
            $n‼︎ = 1;
        }

        while ($n > 2) {
            $n‼︎ *= $n;
            $n  -= 2;
        }

        return $n‼︎;
    }

    /**
     * Rising Factorial
     * Also known as Pochhammer function, Pochhammer polynomial, ascending factorial,
     * rising sequential product, upper factorial.
     * https://en.wikipedia.org/wiki/Falling_and_rising_factorials
     *
     * x⁽ⁿ⁾ = x * (x + 1) * (x + 2) ... (x + n - 1)
     *
     * @param  float $x
     * @param  int   $n
     *
     * @return float
     *
     * @throws Exception\OutOfBoundsException if n < 0
     */
    public static function risingFactorial(float $x, int $n): float
    {
        if ($n < 0) {
            throw new Exception\OutOfBoundsException('Cannot compute rising factorial of a negative number.');
        }

        $fact = 1;
        while ($n > 0) {
            $fact *= $x + $n - 1;
            $n--;
        }

        return $fact;
    }

    /**
     * Falling Factorial
     * Also known as descending factorial, falling sequential product, lower factorial.
     * https://en.wikipedia.org/wiki/Falling_and_rising_factorials
     *
     * x₍ᵢ₎ = x * (x - 1) * (x - 2) ... (x - i + 1)
     *
     * @param  float $x
     * @param  int   $n
     *
     * @return float
     *
     * @throws Exception\OutOfBoundsException if n < 0
     */
    public static function fallingFactorial(float $x, int $n): float
    {
        if ($n < 0) {
            throw new Exception\OutOfBoundsException('Cannot compute falling factorial of a negative number.');
        }

        if ($n > $x) {
            return 0;
        }

        $fact = 1;
        while ($n > 0) {
            $fact *= $x - $n + 1;
            $n--;
        }

        return $fact;
    }

    /**
     * Subfactorial - Derangement number (iterative)
     * The number of permutations of n objects in which no object appears in its natural place.
     *
     *         n  (-1)ⁱ 
     * !n = n! ∑  -----
     *        ᵢ₌₀  i!
     *
     * https://en.wikipedia.org/wiki/Derangement
     * http://mathworld.wolfram.com/Subfactorial.html
     *
     * @param  int $n
     *
     * @return float number of permutations of n
     *
     * @throws Exception\OutOfBoundsException if n < 0
     */
    public static function subfactorial(int $n): float
    {
        if ($n < 0) {
            throw new Exception\OutOfBoundsException('Cannot compute subfactorial of a negative number.');
        }

        $n！ = self::factorial($n);
        $∑  = 0;

        for ($i = 0; $i <= $n; $i++) {
            $i！ = self::factorial($i);
            $∑  += ((-1) ** $i) / $i！;
        }
        return $n！ * $∑;
    }

    /**************************************************************************
     * Permutations and combinations
     *************************************************************************/

    /**
     * Permutations (ordered arrangements)
     *
     * nPn - number of permutations of n things, taken n at a time.
     * P(n) = nPn = (N)n = n(n - 1)(n - 2) ・・・ (n - (n - 1)) = n!
     *
     *
     * nPk: number of permutations of n things, taking only k of them.
     *                    n!
     * P(n,k) = nPk =  --------
     *                 (n - k)!
     *
     * @param int $n
     * @param int $k (Optional) for nPk permutations
     *
     * @return float number of permutations of n
     *
     * @throws Exception\OutOfBoundsException if n is negative or k is larger than n
     */
    public static function permutations(int $n, int $k = null): float
    {
        if ($n < 0) {
            throw new Exception\OutOfBoundsException('Cannot compute negative permutations.');
        }
        if (!\is_null($k) && $k > $n) {
            throw new Exception\OutOfBoundsException('k cannot be larger than n.');
        }

        // nPn: permutations of n things, taken n at a time
        if (\is_null($k)) {
            return self::factorial($n);
        }

        // nPk: Permutations of n things taking only k of them
        $falling_factorial = 1;
        for ($i = $n - $k + 1; $i <= $n; $i++) {
            $falling_factorial *= $i;
        }
        return $falling_factorial;
    }

    /**
     * Combinations - Binomial Coefficient
     * Number of ways of picking k unordered outcomes from n possibilities
     * n choose k: number of possible combinations of n objects taken k at a time.
     *
     * Without repetition:
     *        (n)       n!
     *  nCk = ( ) = ----------
     *        (k)   (n - k)!k!
     *
     * With repetition:
     *         (n)   (n + k - 1)!
     *  nC'k = ( ) = ------------
     *         (k)    (n - 1)!k!
     *
     * http://mathworld.wolfram.com/BinomialCoefficient.html
     * The above formulas are inefficient and can quickly result in floating point overflow.
     * Instead, we use the multiplicative formula.
     *
     *        (n)   nᵏ    n(n - 1)(n - 2)⋯(n - (k - 1)     _ᵏ_  n + 1 - i
     *  nCk = ( ) = -- =  ----------------------------   = | |  ---------
     *        (k)   k!        k(k - 1)(k - 2)⋯1            ⁱ⁼¹      i
     *
     * Where the numerator nᵏ is expressed as a falling factorial.
     * The numerator gives the number of ways to select a sequence of k distinct objects, retaining the order of selection, from a set of n objects.
     * The denominator counts the number of distinct sequences that define the same k-combination when order is disregarded.
     * Due to the symmetry of the binomial coefficient with regard to k and n − k,
     * calculation may be optimised by setting the upper limit of the product above to the smaller of k and n − k.
     * https://en.wikipedia.org/wiki/Binomial_coefficient#Multiplicative_formula
     *
     * @param  int  $n
     * @param  int  $k
     * @param  bool $repetition Whether to do n choose k with or without repetitions
     *
     * @return float number of possible combinations of n objects taken k at a time
     *
     * @throws Exception\OutOfBoundsException if n is negative; if k is larger than n
     */
    public static function combinations(int $n, int $k, bool $repetition = false): float
    {
        if ($n < 0) {
            throw new Exception\OutOfBoundsException('Cannot compute negative combinations.');
        }
        if (!$repetition && $k > $n) {
            throw new Exception\OutOfBoundsException('k cannot be larger than n.');
        }

        if ($repetition) { // nC'k with repetition
            $denominator = $n - 1;
            $numerator   = $n + $k - 1;
        } else { // nCk without repetition
            $denominator = $n - $k;
            $numerator   = $n;
        }

        // The internal self::fallingFactorial() implementation always returns a float.
        // Here we maintain int precision as much as possible.
        $max = \max($denominator, $k);
        $min = \min($denominator, $k);
        $falling_factorial = 1;
        for ($i = $max + 1; $i <= $numerator; $i++) {
            $falling_factorial *= $i;
        }
        return $falling_factorial / self::factorial($min);
    }

    /**
     * Central Binomial Coefficient
     *
     * (2n)   (2n)!
     * (  ) = ----- for n ≥ 0
     * (n )   (n!)²
     *
     * https://en.wikipedia.org/wiki/Central_binomial_coefficient
     *
     * @param  int $n
     *
     * @return float number
     *
     * @throws Exception\OutOfBoundsException if n < 0
     */
    public static function centralBinomialCoefficient(int $n): float
    {
        if ($n < 0) {
            throw new Exception\OutOfBoundsException('Cannot compute negative central binomial coefficient.');
        }

        $⟮2n⟯！ = self::factorial(2 * $n);
        $⟮n！⟯² = (self::factorial($n)) ** 2;

        return $⟮2n⟯！ / $⟮n！⟯²;
    }

    /**************************************************************************
     * Other Combinatorics
     *************************************************************************/

    /**
     * Catalan number
     *
     *        1   (2n)
     * Cn = ----- (  ) for n ≥ 0
     *      n + 1 (n )
     *
     * https://en.wikipedia.org/wiki/Catalan_number
     *
     * @param  int $n
     *
     * @return float number
     *
     * @throws Exception\OutOfBoundsException if n < 0
     */
    public static function catalanNumber(int $n): float
    {
        if ($n < 0) {
            throw new Exception\OutOfBoundsException('Cannot compute negative catalan number.');
        }

        return (1 / ($n + 1)) * self::centralBinomialCoefficient($n);
    }

    /**
     * Lah number
     * Coefficients expressing rising factorials in terms of falling factorials.
     * https://en.wikipedia.org/wiki/Lah_number
     *
     *           / n - 1 \  n!
     * L(n,k) = |         | --
     *           \ k - 1 /  k!
     *
     * @param int $n
     * @param int $k
     *
     * @return float
     *
     * @throws Exception\OutOfBoundsException if n or k < 1 or n < k
     */
    public static function lahNumber(int $n, int $k): float
    {
        if ($n < 1 || $k < 1) {
            throw new Exception\OutOfBoundsException("n and k must be < 1 for Lah Numbers");
        }
        if ($n < $k) {
            throw new Exception\OutOfBoundsException("n must be >= k for Lah Numbers");
        }

        $nCk = self::combinations($n - 1, $k - 1);
        $n！ = self::factorial($n);
        $k！ = self::factorial($k);

        return $nCk * ($n！ / $k！);
    }

    /**
     * Multinomial coefficient (Multinomial Theorem)
     * Finds the number of divisions of n items into r distinct nonoverlapping subgroups of sizes k₁, k₂, etc.
     *
     *       n!       (n₁ + n₂ + ⋯ + nk)!
     *   ---------- = -------------------
     *   k₁!k₂!⋯km!       k₁!k₂!⋯km!
     *
     * http://mathworld.wolfram.com/MultinomialCoefficient.html
     * https://en.wikipedia.org/wiki/Multinomial_theorem
     *
     * @param  int[] $groups Sizes of each subgroup
     *
     * @return float Number of divisions of n items into r distinct nonoverlapping subgroups
     *
     * @throws Exception\OutOfBoundsException
     */
    public static function multinomial(array $groups): float
    {
        /** @var int $n */
        $n            = \array_sum($groups);
        $n！          = self::factorial($n);
        $k₁！k₂！⋯km！ = \array_product(\array_map([Combinatorics::class, 'factorial'], $groups));

        return $n！ / $k₁！k₂！⋯km！;
    }
}
