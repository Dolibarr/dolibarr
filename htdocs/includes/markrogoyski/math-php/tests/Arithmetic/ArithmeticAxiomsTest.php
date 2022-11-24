<?php

namespace MathPHP\Tests\Arithmetic;

use MathPHP\Arithmetic;

/**
 * Tests of arithmetic axioms
 * These tests don't test specific functions,
 * but rather arithmetic axioms which in term make use of multiple functions.
 * If all the arithmetic math is implemented properly, these tests should
 * all work out according to the axioms.
 *
 * Axioms tested:
 *  - Digital root
 *    - dr(n) = n ⇔ n ∈ {0, 1, 2, 3, 4, 5, 6, 7, 8, 9}
 *    - dr(n) < n ⇔ n ≥ 10
 *    - dr(a+b) = dr(dr(a) + dr(b))
 *    - dr(a×b) = dr(dr(a) × dr(b))
 *    - dr(n) = 0 ⇔ n = 9m for m = 1, 2, 3 ⋯
 *  - Modulo
 *    - Identity: (a mod n) mod n = a mod n
 *    - Identity: nˣ mod n = 0 for all positive integer values of x
 *    - Inverse: [(−a mod n) + (a mod n)] mod n = 0
 *    - Distributive: (a + b) mod n = [(a mod n) + (b mod n)] mod n
 *    - Distributive: ab mod n = [(a mod n)(b mod n)] mod n
 *    - Distributive: c(x mod y) = (cx) mod (cy)
 */
class ArithmeticAxiomsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test Axiom: dr(n) = n ⇔ n ∈ {0, 1, 2, 3, 4, 5, 6, 7, 8, 9}
     * The digital root of n is n itself if and only if the number has exactly one digit.
     */
    public function testDigitalRootEqualsN()
    {
        // Given
        for ($n = 0; $n < 10; $n++) {
            // When
            $digitalRoot = Arithmetic::digitalRoot($n);

            // Then
            $this->assertEquals($n, $digitalRoot);
        }
    }

    /**
     * @test Axiom: dr(n) < n ⇔ n ≥ 10
     * The digital root of n is less than n if and only if the number is greater than or equal to 10.
     */
    public function testDigitalRootLessThanN()
    {
        // Given
        for ($n = 10; $n <= 100; $n++) {
            // When
            $digitalRoot = Arithmetic::digitalRoot($n);

            // Then
            $this->assertLessThan($n, $digitalRoot);
        }
    }

    /**
     * @test Axiom: dr(a+b) = dr(dr(a) + dr(b))
     * The digital root of a + b is digital root of the sum of the digital root of a and the digital root of b.
     * @dataProvider dataProviderDigitalRootArithmetic
     * @param        int $a
     * @param        int $b
     */
    public function testDigitalRootAddition(int $a, int $b)
    {
        // When
        $dr⟮a＋b⟯       = Arithmetic::digitalRoot($a + $b);
        $dr⟮dr⟮a⟯＋dr⟮b⟯⟯ = Arithmetic::digitalRoot(Arithmetic::digitalRoot($a) + Arithmetic::digitalRoot($b));

        // Then
        $this->assertEquals($dr⟮a＋b⟯, $dr⟮dr⟮a⟯＋dr⟮b⟯⟯);
    }

    /**
     * @test Axiom: dr(a×b) = dr(dr(a) × dr(b))
     * The digital root of a × b is digital root of the product of the digital root of a and the digital root of b.
     * @dataProvider dataProviderDigitalRootArithmetic
     * @param        int $a
     * @param        int $b
     */
    public function testDigitalRootProduct(int $a, int $b)
    {
        // When
        $dr⟮ab⟯        = Arithmetic::digitalRoot($a * $b);
        $dr⟮dr⟮a⟯×dr⟮b⟯⟯ = Arithmetic::digitalRoot(Arithmetic::digitalRoot($a) * Arithmetic::digitalRoot($b));

        // Then
        $this->assertEquals($dr⟮ab⟯, $dr⟮dr⟮a⟯×dr⟮b⟯⟯);
    }

    /**
     * @return array
     */
    public function dataProviderDigitalRootArithmetic(): array
    {
        return [
            [0, 0],
            [1, 0],
            [0, 1],
            [1, 1],
            [1, 2],
            [2, 2],
            [5, 4],
            [16, 42],
            [10, 10],
            [8041, 2301],
            [241, 325],
            [48, 332],
            [89, 404804],
            [12345, 67890],
            [405, 3],
            [0, 34434],
            [398792873, 2059872903],
        ];
    }

    /**
     * @test Axiom: dr(n) = 0 ⇔ n = 9m for m = 1, 2, 3 ⋯
     * The digital root of a nonzero number is 9 if and only if the number is itself a multiple of 9.
     */
    public function testDigitalRootMultipleOfNine()
    {
        // Given
        for ($n = 9; $n <= 900; $n += 9) {
            // When
            $digitalRoot = Arithmetic::digitalRoot($n);

            // Then
            $this->assertEquals(9, $digitalRoot);
        }
    }

    /**
     * @test Axiom Identity: (a mod n) mod n = a mod n
     * https://en.wikipedia.org/wiki/Modulo_operation#Properties_(identities)
     */
    public function testModuloIdentity()
    {
        // Given
        foreach (\range(-20, 20) as $a) {
            foreach (\range(-20, 20) as $n) {
                // When
                $⟮a mod n⟯ mod n = Arithmetic::modulo(Arithmetic::modulo($a, $n), $n);
                $a mod n        = Arithmetic::modulo($a, $n);

                // Then
                $this->assertEquals($⟮a mod n⟯ mod n, $a mod n);
            }
        }
    }

    /**
     * @test Axiom Identity: nˣ mod n = 0 for all positive integer values of x
     * https://en.wikipedia.org/wiki/Modulo_operation#Properties_(identities)
     */
    public function testModuloIdentityOfPowers()
    {
        foreach (\range(-20, 20) as $n) {
            foreach (\range(1, 5) as $ˣ) {
                // Given
                $nˣ = $n ** $ˣ;

                // When
                $nˣ mod n = Arithmetic::modulo($nˣ, $n);

                // Then
                $this->assertEquals(0, $nˣ mod n);
            }
        }
    }

    /**
     * @test Axiom Inverse: [(−a mod n) + (a mod n)] mod n = 0
     * https://en.wikipedia.org/wiki/Modulo_operation#Properties_(identities)
     */
    public function testModuloInverse()
    {
        // Given
        foreach (\range(-20, 20) as $a) {
            foreach (\range(-20, 20) as $n) {
                // When
                $⟦⟮−a mod n⟯ ＋ ⟮a mod n⟯⟧ mod n = Arithmetic::modulo(
                    Arithmetic::modulo(-$a, $n) + Arithmetic::modulo($a, $n),
                    $n
                );

                // Then
                $this->assertEquals(0, $⟦⟮−a mod n⟯ ＋ ⟮a mod n⟯⟧ mod n);
            }
        }
    }

    /**
     * @test Axiom Distributive: (a + b) mod n = [(a mod n) + (b mod n)] mod n
     * https://en.wikipedia.org/wiki/Modulo_operation#Properties_(identities)
     */
    public function testModuloDistributiveAdditionProperty()
    {
        // Given
        foreach (\range(-5, 5) as $a) {
            foreach (\range(-5, 5) as $b) {
                foreach (\range(-6, 6) as $n) {
                    // When
                    $⟮a ＋ b⟯ mod n = Arithmetic::modulo($a + $b, $n);
                    $⟦⟮a mod n⟯ ＋ ⟮b mod n⟧⟯ mod n = Arithmetic::modulo(
                        Arithmetic::modulo($a, $n) + Arithmetic::modulo($b, $n),
                        $n
                    );

                    // Then
                    $this->assertEquals($⟮a ＋ b⟯ mod n, $⟦⟮a mod n⟯ ＋ ⟮b mod n⟧⟯ mod n);
                }
            }
        }
    }

    /**
     * @test Axiom Distributive: ab mod n = [(a mod n)(b mod n)] mod n
     * https://en.wikipedia.org/wiki/Modulo_operation#Properties_(identities)
     */
    public function testModuloDistributiveMultiplicationProperty()
    {
        // Given
        foreach (\range(-5, 5) as $a) {
            foreach (\range(-5, 5) as $b) {
                foreach (\range(-6, 6) as $n) {
                    // When
                    $ab mod n = Arithmetic::modulo($a * $b, $n);
                    $⟦⟮a mod n⟯⟮b mod n⟧⟯ mod n = Arithmetic::modulo(
                        Arithmetic::modulo($a, $n) * Arithmetic::modulo($b, $n),
                        $n
                    );

                    // Then
                    $this->assertEquals($ab mod n, $⟦⟮a mod n⟯⟮b mod n⟧⟯ mod n);
                }
            }
        }
    }

    /**
     * @test Axiom Distributive: c(x mod y) = (cx) mod (cy)
     * Graham, Knuth, Patashnik (1994). Concrete Mathematics, A Foundation For Computer Science. Addison-Wesley.
     */
    public function testModuloDistributiveLaw()
    {
        // Given
        foreach (\range(-5, 5) as $x) {
            foreach (\range(-5, 5) as $y) {
                foreach (\range(-6, 6) as $c) {
                    // When
                    $c⟮x mod y⟯   = $c * Arithmetic::modulo($x, $y);
                    $⟮cx⟯ mod ⟮cy⟯ = Arithmetic::modulo($c * $x, $c * $y);

                    // Then
                    $this->assertEquals($c⟮x mod y⟯, $⟮cx⟯ mod ⟮cy⟯);
                }
            }
        }
    }
}
