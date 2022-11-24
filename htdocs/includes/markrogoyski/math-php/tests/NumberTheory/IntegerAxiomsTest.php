<?php

namespace MathPHP\Tests\NumberTheory;

use MathPHP\NumberTheory\Integer;
use MathPHP\Algebra;

/**
 * Tests of number theory axioms
 * These tests don't test specific functions,
 * but rather number theory axioms which in term make use of multiple functions.
 * If all the number theory math is implemented properly, these tests should
 * all work out according to the axioms.
 *
 * Axioms tested:
 *  - Coprime
 *    - lcm(a, b) = ab
 */
class IntegerAxiomsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Axiom: If a and b are coprime ⇒ lcm(a, b) = ab
     * If a and b are coprime, then the least common multiple of a and b is equal to their product ab.
     * @dataProvider dataProviderForCoprime
     * @param        int $a
     * @param        int $b
     */
    public function testCoprimeProductEqualsLcm(int $a, int $b)
    {
        // Given
        $ab = $a * $b;

        // When
        $lcm⟮a、b⟯ = Algebra::lcm($a, $b);

        // Then
        $this->assertEquals($lcm⟮a、b⟯, $ab);
    }

    public function dataProviderForCoprime(): array
    {
        return [
            [1, 0],
            [1, 2],
            [1, 3],
            [1, 4],
            [1, 5],
            [1, 6],
            [1, 7],
            [1, 8],
            [1, 9],
            [1, 10],
            [1, 20],
            [1, 30],
            [1, 100],
            [2, 3],
            [2, 5],
            [2, 7],
            [2, 9],
            [2, 11],
            [2, 13],
            [2, 15],
            [2, 17],
            [2, 19],
            [2, 21],
            [2, 23],
            [2, 25],
            [2, 27],
            [2, 29],
            [3, 4],
            [3, 5],
            [3, 7],
            [3, 8],
            [3, 10],
            [3, 11],
            [3, 13],
            [3, 14],
            [3, 16],
            [4, 3],
            [4, 5],
            [4, 7],
            [4, 17],
            [4, 21],
            [4, 35],
            [5, 6],
            [5, 7],
            [5, 8],
            [5, 9],
            [5, 11],
            [5, 12],
            [5, 13],
            [5, 14],
            [5, 16],
            [5, 27],
            [6, 7],
            [6, 11],
            [6, 13],
            [6, 17],
            [6, 29],
            [6, 23],
            [6, 25],
            [6, 29],
            [19, 20],
            [20, 21],
            [23, 24],
            [23, 25],
            [27, 16],
            [28, 29],
            [29, 30],
        ];
    }
}
