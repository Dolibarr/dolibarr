<?php

namespace MathPHP\Tests\Expression;

use MathPHP\Expression\Polynomial;

/**
 * Tests of polynomial axioms
 * These tests don't test specific functions,
 * but rather polynomial axioms which in term make use of multiple functions.
 * If all the polynomial math is implemented properly, these tests should
 * all work out according to the axioms.
 *
 * Axioms tested:
 *  - Commutativity
 *    - a + b = b + a
 *    - ab = bc
 *  - Associativity
 *    - a + (b + c) = (a + b) + c
 *    - a(bc) = (ab)c
 *  - Distributed Law
 *    - a ✕ (b + c) = a ✕ b + a ✕ c
 *    - a + (b ✕ c) = a ✕ c + b ✕ c
 *  - Identity
 *    - a + 0 = 0 + a = a
 *    - a ✕ 0 = 0 ✕ a = 0
 *  - Negate
 *    - -a = a * -1
 *  - Arithmetic
 *    - Sum of two polynomials is a polynomial
 *    - Product of two polynomials is a polynomial
 *    - Derivative of a polynomial is a polynomial
 *    - Integral of a polynomial is a polynomial
 */
class PolynomialAxiomsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test Axiom: a + b = b + a
     * Commutativity of addition.
     * @dataProvider dataProviderForTwoPolynomials
     * @param        array $a
     * @param        array $b
     * @throws       \Exception
     */
    public function testCommutativityOfAddition(array $a, array $b)
    {
        // Given
        $a = new Polynomial($a);
        $b = new Polynomial($b);

        // When
        $a＋b = $a->add($b);
        $b＋a = $b->add($a);

        // Then
        $this->assertEquals($a＋b->getDegree(), $b＋a->getDegree());
        $this->assertEquals($a＋b->getCoefficients(), $b＋a->getCoefficients());
    }

    /**
     * @test Axiom: ab = bc
     * Commutativity of multiplication.
     * @dataProvider dataProviderForTwoPolynomials
     * @param        array $a
     * @param        array $b
     * @throws       \Exception
     */
    public function testCommutativityOfMultiplication(array $a, array $b)
    {
        // Given
        $a = new Polynomial($a);
        $b = new Polynomial($b);

        // When
        $ab = $a->multiply($b);
        $ba = $b->multiply($a);

        // Then
        $this->assertEquals($ab->getDegree(), $ba->getDegree());
        $this->assertEquals($ab->getCoefficients(), $ba->getCoefficients());
    }

    /**
     * @test Axiom: a + (b + c) = (a + b) + c
     * Associativity of addition.
     * @dataProvider dataProviderForThreePolynomials
     * @param        array $a
     * @param        array $b
     * @param        array $c
     * @throws       \Exception
     */
    public function testAssociativityOfAddition(array $a, array $b, array $c)
    {
        // Given
        $a = new Polynomial($a);
        $b = new Polynomial($b);
        $c = new Polynomial($c);

        // When
        $a ＋ ⟮b ＋ c⟯ = $a->add($b->add($c));
        $⟮a ＋ b⟯ ＋ c = ($a->add($b))->add($c);

        // Then
        $this->assertEquals($a ＋ ⟮b ＋ c⟯->getDegree(), $⟮a ＋ b⟯ ＋ c->getDegree());
        $this->assertEquals($a ＋ ⟮b ＋ c⟯->getCoefficients(), $⟮a ＋ b⟯ ＋ c->getCoefficients());
    }

    /**
     * @test Axiom: a(bc) = (ab)c
     * Associativity of multiplication.
     * @dataProvider dataProviderForThreePolynomials
     * @param        array $a
     * @param        array $b
     * @param        array $c
     * @throws       \Exception
     */
    public function testAssociativityOfMultiplication(array $a, array $b, array $c)
    {
        // Given
        $a = new Polynomial($a);
        $b = new Polynomial($b);
        $c = new Polynomial($c);

        // When
        $a⟮bc⟯ = $a->multiply($b->multiply($c));
        $⟮ab⟯c = ($a->multiply($b))->multiply($c);

        // Then
        $this->assertEquals($a⟮bc⟯->getDegree(), $⟮ab⟯c->getDegree());
        $this->assertEquals($a⟮bc⟯->getCoefficients(), $⟮ab⟯c->getCoefficients());
    }

    /**
     * @test Axiom: a ✕ (b + c) = a ✕ b + a ✕ c
     * Distributive law.
     * @dataProvider dataProviderForThreePolynomials
     * @param        array $a
     * @param        array $b
     * @param        array $c
     * @throws       \Exception
     */
    public function testDistributiveLaw1(array $a, array $b, array $c)
    {
        // Given
        $a = new Polynomial($a);
        $b = new Polynomial($b);
        $c = new Polynomial($c);

        // When
        $a⟮b ＋ c⟯   = $a->multiply($b->add($c));
        $⟮ab⟯ ＋ ⟮ac⟯ = ($a->multiply($b))->add($a->multiply($c));

        // Then
        $this->assertEquals($a⟮b ＋ c⟯->getDegree(), $⟮ab⟯ ＋ ⟮ac⟯->getDegree());
        $this->assertEquals($a⟮b ＋ c⟯->getCoefficients(), $⟮ab⟯ ＋ ⟮ac⟯->getCoefficients());
    }

    /**
     * @test Axiom: (a + b) ✕ c = a ✕ c + b ✕ c
     * Distributive law.
     * @dataProvider dataProviderForThreePolynomials
     * @param        array $a
     * @param        array $b
     * @param        array $c
     * @throws       \Exception
     */
    public function testDistributiveLaw2(array $a, array $b, array $c)
    {
        // Given
        $a = new Polynomial($a);
        $b = new Polynomial($b);
        $c = new Polynomial($c);

        // When
        $⟮a ＋ b⟯c   = ($a->add($b))->multiply($c);
        $⟮ac⟯ ＋ ⟮bc⟯ = ($a->multiply($c))->add($b->multiply($c));

        // Then
        $this->assertEquals($⟮a ＋ b⟯c->getDegree(), $⟮ac⟯ ＋ ⟮bc⟯->getDegree());
        $this->assertEquals($⟮a ＋ b⟯c->getCoefficients(), $⟮ac⟯ ＋ ⟮bc⟯->getCoefficients());
    }

    /**
     * @test Axiom: a + 0 = 0 + a = a
     * Identity of addition.
     * @dataProvider dataProviderForOnePolynomial
     * @param        array $a
     * @throws       \Exception
     */
    public function testIdentityOfAddition(array $a)
    {
        // Given
        $a    = new Polynomial($a);
        $zero = new Polynomial([0]);

        // When
        $a＋0    = $a->add($zero);
        $zero＋a = $zero->add($a);

        // Then
        $this->assertEquals($a->getDegree(), $a＋0->getDegree());
        $this->assertEquals($a->getDegree(), $zero＋a->getDegree());

        // And
        $this->assertEquals($a->getCoefficients(), $a＋0->getCoefficients());
        $this->assertEquals($a->getCoefficients(), $zero＋a->getCoefficients());
    }

    /**
     * @test Axiom: a ✕ 0 = 0 ✕ a = 0
     * Identity of multiplication.
     * @dataProvider dataProviderForOnePolynomial
     * @param        array $a
     * @throws       \Exception
     */
    public function testIdentityOfMultiplication(array $a)
    {
        // Given
        $a    = new Polynomial($a);
        $zero = new Polynomial([0]);

        // When
        $a✕0    = $a->multiply($zero);
        $zero✕a = $zero->multiply($a);

        // Then
        $this->assertEquals($zero->getDegree(), $a✕0->getDegree());
        $this->assertEquals($zero->getDegree(), $zero✕a->getDegree());

        // And
        $this->assertEquals($zero->getCoefficients(), $a✕0->getCoefficients());
        $this->assertEquals($zero->getCoefficients(), $zero✕a->getCoefficients());
    }

    /**
     * @test Axiom: -a = a * -1
     * Negation is the same as multiplying by -1
     * @dataProvider dataProviderForOnePolynomial
     * @param        array $a
     * @throws       \Exception
     */
    public function testNegateSameAsMultiplyingByNegativeOne(array $a)
    {
        // Given
        $a = new Polynomial($a);

        // When
        $−a = $a->negate();
        $a⟮−1⟯ = $a->multiply(-1);

        // Then
        $this->assertEquals($−a->getDegree(), $a⟮−1⟯->getDegree());
        $this->assertEquals($−a->getCoefficients(), $a⟮−1⟯->getCoefficients());
    }

    /**
     * @test Axiom: Sum of two polynomials is a polynomial
     * @dataProvider dataProviderForTwoPolynomials
     * @param        array $a
     * @param        array $b
     * @throws       \Exception
     */
    public function testArithmeticAdditionProperty(array $a, array $b)
    {
        // Given
        $a = new Polynomial($a);
        $b = new Polynomial($b);

        // When
        $a＋b = $a->add($b);

        // Then
        $this->assertInstanceOf(Polynomial::class, $a＋b);
    }

    /**
     * @test Axiom: Product of two polynomials is a polynomial
     * @dataProvider dataProviderForTwoPolynomials
     * @param        array $a
     * @param        array $b
     * @throws       \Exception
     */
    public function testArithmeticMultiplicationProperty(array $a, array $b)
    {
        // Given
        $a = new Polynomial($a);
        $b = new Polynomial($b);

        // When
        $ab = $a->multiply($b);

        // Then
        $this->assertInstanceOf(Polynomial::class, $ab);
    }

    /**
     * @test Axiom: Derivative of a polynomials is a polynomial
     * @dataProvider dataProviderForOnePolynomial
     * @param        array $a
     * @throws       \Exception
     */
    public function testArithmeticDerivativeProperty(array $a)
    {
        // Given
        $a = new Polynomial($a);

        // When
        $derivative = $a->differentiate();

        // Then
        $this->assertInstanceOf(Polynomial::class, $derivative);
    }

    /**
     * @test Axiom: Integral of a polynomials is a polynomial
     * @dataProvider dataProviderForOnePolynomial
     * @param        array $a
     * @throws       \Exception
     */
    public function testArithmeteicIntegrationProperty(array $a)
    {
        // Given
        $a = new Polynomial($a);

        // When
        $derivative = $a->integrate();

        // Then
        $this->assertInstanceOf(Polynomial::class, $derivative);
    }

    public function dataProviderForOnePolynomial(): array
    {
        return [
            [
                [0],
            ],
            [
                [1],
            ],
            [
                [2],
            ],
            [
                [8],
            ],
            [
                [1, 5],
            ],
            [
                [4, 0],
            ],
            [
                [0, 3],
            ],
            [
                [12, 4],
            ],
            [
                [1, 2, 3],
            ],
            [
                [2, 3, 4],
            ],
            [
                [1, 1, 1],
            ],
            [
                [5, 3, 6],
            ],
            [
                [2, 7, 4],
            ],
            [
                [6, 0, 3],
            ],
            [
                [4, 5, 2, 6],
            ],
            [
                [3, 5, 2, 10],
            ],
            [
                [-4, 6, 7, -1],
            ],
            [
                [-2, -1, -4, -3],
            ],
            [
                [5, 3, 6],
            ],
            [
                [7, 6, 6],
            ],
            [
                [-6, -1],
            ],
            [
                [-5, -5, -1, 2, 4, 6, 5],
            ],
            [
                [10, 20, 30, 40],
            ],
            [
                [-5, 10, -15, 20, -55],
            ],
            [
                [0, 0, 0, 0, 5],
            ],
            [
                [2, 0, 0, 0, 4],
            ],
            [
                [-1, -2, -3, -4, -5, -6, -7, -8, -9],
            ],
            [
                [1, 2, 3, 4, 5, 6, 7, 8, 9],
            ],
            [
                [4, 54, 23, -34, 12, 73, -34, 2],
            ],
        ];
    }

    public function dataProviderForTwoPolynomials(): array
    {
        return [
            [
                [0],
                [0],
            ],
            [
                [1],
                [1],
            ],
            [
                [0],
                [1],
            ],
            [
                [1],
                [0],
            ],
            [
                [2],
                [2],
            ],
            [
                [1],
                [2],
            ],
            [
                [4],
                [8],
            ],
            [
                [1, 5],
                [5, 4],
            ],
            [
                [4, 0],
                [5, 6],
            ],
            [
                [0, 3],
                [5, 5],
            ],
            [
                [12, 4],
                [5, 10],
            ],
            [
                [1, 2, 3],
                [1, 2, 3],
            ],
            [
                [1, 2, 3],
                [2, 3, 4],
            ],
            [
                [1, 1, 1],
                [2, 2, 2],
            ],
            [
                [5, 3, 6],
                [8, 7, 3],
            ],
            [
                [2, 7, 4],
                [5, 4, 7],
            ],
            [
                [6, 0, 3],
                [1, 1, 2],
            ],
            [
                [4, 5, 2, 6],
                [6, 5, 5, 4],
            ],
            [
                [3, 5, 2, 10],
                [2, -2, 5, 3],
            ],
            [
                [-4, 6, 7, -1],
                [5, 5, -5, -1],
            ],
            [
                [-2, -1, -4, -3],
                [-5, 5, -4, -3],
            ],
            [
                [1],
                [5, 3, 6],
            ],
            [
                [7, 6, 6],
                [3, 2],
            ],
            [
                [-3, 4, 5, 6],
                [-6, -1],
            ],
            [
                [5, 6, 7, 6, 5, 6],
                [-5, -5, -1, 2, 4, 6, 5],
            ],
            [
                [10, 20, 30, 40],
                [-4, 5, 6, -4, 3],
            ],
            [
                [4, 8, 12, 16, 20],
                [-5, 10, -15, 20, -55],
            ],
            [
                [0, 0, 0, 0, 5],
                [4, 3, 6, 7],
            ],
            [
                [2, 0, 0, 0, 4],
                [1, 1, 1, 1, 1],
            ],
            [
                [1, 2, 3, 4, 5, 6, 7, 8, 9],
                [-1, -2, -3, -4, -5, -6, -7, -8, -9],
            ],
            [
                [1, 2, 3, 4, 5, 6, 7, 8, 9],
                [2, 3, 4, 5, 6, 7, 8, 9, 10],
            ],
            [
                [34, 65, 34, 23, 62, 87, 34, 65],
                [4, 54, 23, -34, 12, 73, -34, 2],
            ],
        ];
    }

    public function dataProviderForThreePolynomials(): array
    {
        return [
            [
                [0],
                [0],
                [0],
            ],
            [
                [1],
                [1],
                [1],
            ],
            [
                [0],
                [1],
                [0],
            ],
            [
                [1],
                [0],
                [1],
            ],
            [
                [2],
                [2],
                [2],
            ],
            [
                [1],
                [2],
                [3],
            ],
            [
                [4],
                [8],
                [2],
            ],
            [
                [1, 5],
                [5, 4],
                [4, 3],
            ],
            [
                [4, 0],
                [5, 6],
                [6, 5],
            ],
            [
                [0, 3],
                [5, 5],
                [0, 0],
            ],
            [
                [12, 4],
                [5, 10],
                [2, 10],
            ],
            [
                [1, 2, 3],
                [1, 2, 3],
                [1, 2, 3],
            ],
            [
                [1, 2, 3],
                [2, 3, 4],
                [3, 4, 5],
            ],
            [
                [1, 1, 1],
                [2, 2, 2],
                [3, 3, 3],
            ],
            [
                [5, 3, 6],
                [8, 7, 3],
                [3, 2, 7],
            ],
            [
                [2, 7, 4],
                [5, 4, 7],
                [6, 5, 4],
            ],
            [
                [6, 0, 3],
                [1, 1, 2],
                [2, 3, 0],
            ],
            [
                [4, 5, 2, 6],
                [6, 5, 5, 4],
                [2, 2, 3, 3],
            ],
            [
                [3, 5, 2, 10],
                [2, -2, 5, 3],
                [-1, 3, 4, -1],
            ],
            [
                [-4, 6, 7, -1],
                [5, 5, -5, -1],
                [6, 5, -4, -3],
            ],
            [
                [-2, -1, -4, -3],
                [-5, 5, -4, -3],
                [1, -1, 1, -2],
            ],
            [
                [1],
                [5, 3, 6],
                [3, -2],
            ],
            [
                [7, 6, 6],
                [3, 2],
                [4],
            ],
            [
                [-3, 4, 5, 6],
                [-6, -1],
                [5, 6, 4],
            ],
            [
                [5, 6, 7, 6, 5, 6],
                [-5, -5, -1, 2, 4, 6, 5],
                [5, 5, 5, -6, -6, -4, 3],
            ],
            [
                [10, 20, 30, 40],
                [-4, 5, 6, -4, 3],
                [-3, -3, -2, 1, 5],
            ],
            [
                [4, 8, 12, 16, 20],
                [-5, 10, -15, 20, -55],
                [3, 6, 9, -12, -15],
            ],
            [
                [0, 0, 0, 0, 5],
                [4, 3, 6, 7],
                [6, 0, 0, 0, 0],
            ],
            [
                [2, 0, 0, 0, 4],
                [1, 1, 1, 1, 1],
                [2, 2, 2, -3, -2]
            ],
            [
                [1, 2, 3, 4, 5, 6, 7, 8, 9],
                [-1, -2, -3, -4, -5, -6, -7, -8, -9],
                [4, 3, 5, 6],
            ],
            [
                [1, 2, 3, 4, 5, 6, 7, 8, 9],
                [2, 3, 4, 5, 6, 7, 8, 9, 10],
                [3, 4, 5, 6, 7, 8, 9, 10, 11],
            ],
            [
                [34, 65, 34, 23, 62, 87, 34, 65],
                [4, 54, 23, -34, 12, 73, -34, 2],
                [34, 23, 12, 63, 24, -42, 12, 4],
            ],
            [
                [1, 2, 3, 4, 5, 6],
                [-1, -2, -3, -4, -6],
                [0, 0, 0, 0, 0, 0],
            ],
        ];
    }
}
