<?php

namespace MathPHP\Tests\Probability;

use MathPHP\Probability\Combinatorics;

/**
 * Tests of Combinatorics axioms
 * These tests don't test specific functions,
 * but rather combinatorics axioms which in term make use of multiple functions.
 * If all the combinatorics math is implemented properly, these tests should
 * all work out according to the axioms.
 *
 * Axioms tested:
 *  - Lah numbers, rising and falling factorials
 *   - x⁽ⁿ⁾ = ∑ L⟮n,k⟯ x₍k₎
 *   - x₍n₎ = ∑ (-1)ⁿ⁻ᵏ L(n,k) x⁽ᵏ⁾
 *   - L(n,1) = n!
 *   - L(n,2) = (n - 1)n! / 2
 *   - L(n,n) = 1
 */
class CombinatoricsAxiomsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @testCase Axiom: x⁽ⁿ⁾ = L⟮n,k⟯ x₍k₎
     * Rising factorial can be represented as the summation of Lah numbers and falling factorials
     *
     * @dataProvider dataProivderForLahNumbers
     * @param        int $x
     * @param        int $n
     * @throws       \Exception
     */
    public function testRisingFactorialAsLahNumberAndFallingFactorial(int $x, int $n)
    {
        $x⁽ⁿ⁾  = Combinatorics::risingFactorial($x, $n);

        $∑L⟮n、k⟯x₍k₎ = 0;
        for ($k = 1; $k <= $n; $k++) {
            $x₍k₎        = Combinatorics::fallingFactorial($x, $k);
            $L⟮n、k⟯       = Combinatorics::lahNumber($n, $k);
            $∑L⟮n、k⟯x₍k₎ += $L⟮n、k⟯ * $x₍k₎;
        }

        $this->assertEquals($x⁽ⁿ⁾, $∑L⟮n、k⟯x₍k₎);
    }

    /**
     * @testCase Axiom: x₍n₎ = ∑ (-1)ⁿ⁻ᵏ L(n,k) x⁽ᵏ⁾
     * Falling factorial can be represented as the summation of Lah numbers and rising factorials
     *
     * @dataProvider dataProivderForLahNumbers
     * @param        int $x
     * @param        int $n
     * @throws       \Exception
     */
    public function testFallingFactorialAsLahNumberAndRisingFactorial(int $x, int $n)
    {
        $x₍n₎ = Combinatorics::fallingFactorial($x, $n);

        $∑⟮−1⟯ⁿ⁻ᵏL⟮n、k⟯x₍k₎ = 0;
        for ($k = 1; $k <= $n; $k++) {
            $⟮−1⟯ⁿ⁻ᵏ            = (-1) ** ($n - $k);
            $L⟮n、k⟯             = Combinatorics::lahNumber($n, $k);
            $x⁽ᵏ⁾              = Combinatorics::risingFactorial($x, $k);
            $∑⟮−1⟯ⁿ⁻ᵏL⟮n、k⟯x₍k₎ += $⟮−1⟯ⁿ⁻ᵏ * $L⟮n、k⟯ * $x⁽ᵏ⁾;
        }

        $this->assertEquals($x₍n₎, $∑⟮−1⟯ⁿ⁻ᵏL⟮n、k⟯x₍k₎);
    }

    /**
     * @testCase Axiom: L(n,1) = n!
     * Lah number identity when k is 1
     *
     * @dataProvider dataProivderForLahNumberIdentities
     * @param        int $n
     * @throws       \Exception
     */
    public function testLahNumberIdentityKEqualsOne(int $n)
    {
        $L⟮n、1⟯ = Combinatorics::lahNumber($n, 1);
        $n！    = Combinatorics::factorial($n);

        $this->assertEquals($L⟮n、1⟯, $n！);
    }

    /**
     * @testCase Axiom: L(n,2) = (n - 1)n! / 2
     * Lah number identity when k is 2
     *
     * @dataProvider dataProivderForLahNumberIdentitiesGreaterThanOne
     * @param        int $n
     * @throws       \Exception
     */
    public function testLahNumberIdentityKEqualsTwo(int $n)
    {
        $L⟮n、1⟯     = Combinatorics::lahNumber($n, 2);
        $⟮n−1⟯n！／2 = (($n - 1) * Combinatorics::factorial($n)) / 2;

        $this->assertEquals($L⟮n、1⟯, $⟮n−1⟯n！／2);
    }

    /**
     * @testCase Axiom: L(n,n) = 1
     * Lah number identity when n = n
     *
     * @dataProvider dataProivderForLahNumberIdentities
     * @param        int $n
     * @throws       \Exception
     */
    public function testLahNumberIdentityNNEqualsOne(int $n)
    {
        $L⟮n、n⟯ = Combinatorics::lahNumber($n, $n);

        $this->assertEquals(1, $L⟮n、n⟯);
    }

    /**
     * @return array [n, k]
     */
    public function dataProivderForLahNumbers(): array
    {
        return [
            [1, 1],
            [5, 1],
            [5, 2],
            [5, 3],
            [4, 4],
            [3, 5],
            [2, 6],
            [1, 1],
            [2, 1],
            [2, 2],
            [3, 1],
            [3, 2],
            [3, 3],
            [4, 1],
            [4, 2],
            [4, 3],
            [4, 4],
            [5, 1],
            [5, 2],
            [5, 3],
            [5, 4],
            [5, 5],
            [6, 1],
            [6, 2],
            [6, 3],
            [6, 4],
            [6, 5],
            [6, 6],
            [12, 1],
            [12, 2],
            [12, 3],
            [12, 4],
            [12, 5],
            [12, 6],
            [12, 7],
            [12, 8],
            [12, 9],
            [12, 10],
            [12, 11],
            [12, 12],
        ];
    }

    /**
     * @return array [n]
     */
    public function dataProivderForLahNumberIdentities(): array
    {
        return [
            [1],
            [2],
            [3],
            [4],
            [5],
            [6],
            [7],
            [8],
            [9],
            [12],
        ];
    }

    /**
     * @return array [n]
     */
    public function dataProivderForLahNumberIdentitiesGreaterThanOne(): array
    {
        return [
            [2],
            [3],
            [4],
            [5],
            [6],
            [7],
            [8],
            [9],
            [12],
        ];
    }
}
