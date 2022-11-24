<?php

namespace MathPHP\Tests\Number;

use MathPHP\Number\Complex;

/**
 * Tests of complex number axioms
 * These tests don't test specific functions,
 * but rather complex number axioms which in term make use of multiple functions.
 * If all the complex number math is implemented properly, these tests should
 * all work out according to the axioms.
 *
 * Axioms tested:
 *  - Commutativity
 *    - z + w = w + z
 *    - zw = wz
 *  - Associativity
 *    - z + (u + v) = (z + u) + v
 *    - z(uv) = (zu)v
 *  - Distributed Law
 *    - z(u + v) = zu + zv
 *  - Identity
 *    - z + 0 = z
 *    - z * 1 = z
 *  - Inverse
 *    - (∀a)(∃b) a + b = 0
 */
class ComplexAxiomsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test Axiom: z + w = w + z
     * Commutativity of addition.
     * @dataProvider dataProviderForTwoComplexNumbers
     * @param        int $r₁
     * @param        int $i₁
     * @param        int $r₂
     * @param        int $i₂
     * @throws       \Exception
     */
    public function testCommutativityOfAddition(int $r₁, int $i₁, int $r₂, int $i₂)
    {
        // Given
        $z = new Complex($r₁, $i₁);
        $w = new Complex($r₂, $i₂);

        // When
        $z＋w = $z->add($w);
        $w＋z = $w->add($z);

        $this->assertTrue($z＋w->equals($w＋z));
        $this->assertTrue($w＋z->equals($z＋w));
        $this->assertEquals($z＋w->r, $w＋z->r);
        $this->assertEquals($z＋w->i, $w＋z->i);
    }

    /**
     * @test Axiom: zw = wz
     * Commutativity of multiplication.
     * @dataProvider dataProviderForTwoComplexNumbers
     * @param        int $r₁
     * @param        int $i₁
     * @param        int $r₂
     * @param        int $i₂
     * @throws       \Exception
     */
    public function testCommutativityOfMultiplication(int $r₁, int $i₁, int $r₂, int $i₂)
    {
        // Given
        $z = new Complex($r₁, $i₁);
        $w = new Complex($r₂, $i₂);

        // When
        $zw = $z->multiply($w);
        $wz = $w->multiply($z);

        $this->assertTrue($zw->equals($wz));
        $this->assertTrue($wz->equals($zw));
        $this->assertEquals($zw->r, $wz->r);
        $this->assertEquals($zw->i, $wz->i);
    }

    /**
     * @test Axiom: z + (u + v) = (z + u) + v
     * Associativity of Addition.
     * @dataProvider dataProviderForThreeComplexNumbers
     * @param        int $r₁
     * @param        int $i₁
     * @param        int $r₂
     * @param        int $i₂
     * @param        int $r₃
     * @param        int $i₃
     * @throws       \Exception
     */
    public function testAssociativityOfAddition(int $r₁, int $i₁, int $r₂, int $i₂, int $r₃, int $i₃)
    {
        // Given
        $z = new Complex($r₁, $i₁);
        $u = new Complex($r₂, $i₂);
        $v = new Complex($r₃, $i₃);

        // When
        $z⟮u ＋ v⟯ = $z->add($u->add($v));
        $⟮z ＋ u⟯v = $z->add($u)->add($v);

        $this->assertTrue($z⟮u ＋ v⟯->equals($⟮z ＋ u⟯v));
        $this->assertTrue($⟮z ＋ u⟯v->equals($z⟮u ＋ v⟯));
        $this->assertEquals($z⟮u ＋ v⟯->r, $⟮z ＋ u⟯v->r);
        $this->assertEquals($z⟮u ＋ v⟯->i, $⟮z ＋ u⟯v->i);
    }

    /**
     * @test Axiom: z(uv) = (zu)v
     * Associativity of Multiplication.
     * @dataProvider dataProviderForThreeComplexNumbers
     * @param        int $r₁
     * @param        int $i₁
     * @param        int $r₂
     * @param        int $i₂
     * @param        int $r₃
     * @param        int $i₃
     * @throws       \Exception
     */
    public function testAssociativityOfMultiplication(int $r₁, int $i₁, int $r₂, int $i₂, int $r₃, int $i₃)
    {
        // Given
        $z = new Complex($r₁, $i₁);
        $u = new Complex($r₂, $i₂);
        $v = new Complex($r₃, $i₃);

        // When
        $z⟮uv⟯ = $z->multiply($u->multiply($v));
        $⟮zu⟯v = $z->multiply($u)->multiply($v);

        $this->assertTrue($z⟮uv⟯->equals($⟮zu⟯v));
        $this->assertTrue($⟮zu⟯v->equals($z⟮uv⟯));
        $this->assertEquals($z⟮uv⟯->r, $⟮zu⟯v->r);
        $this->assertEquals($z⟮uv⟯->i, $⟮zu⟯v->i);
    }

    /**
     * @test Axiom: z(u + v) = zu + zv
     * Distributed Law.
     * @dataProvider dataProviderForThreeComplexNumbers
     * @param        int $r₁
     * @param        int $i₁
     * @param        int $r₂
     * @param        int $i₂
     * @param        int $r₃
     * @param        int $i₃
     * @throws       \Exception
     */
    public function testDistributedLaw(int $r₁, int $i₁, int $r₂, int $i₂, int $r₃, int $i₃)
    {
        // Given
        $z = new Complex($r₁, $i₁);
        $u = new Complex($r₂, $i₂);
        $v = new Complex($r₃, $i₃);

        // When
        $z⟮u ＋ v⟯ = $z->multiply($u->add($v));
        $zu ＋ zv = $z->multiply($u)->add($z->multiply($v));

        $this->assertTrue($z⟮u ＋ v⟯->equals($zu ＋ zv));
        $this->assertTrue($zu ＋ zv->equals($z⟮u ＋ v⟯));
        $this->assertEquals($z⟮u ＋ v⟯->r, $zu ＋ zv->r);
        $this->assertEquals($z⟮u ＋ v⟯->i, $zu ＋ zv->i);
    }

    /**
     * @test Axiom: z + 0 = z
     * Additive identity
     * @dataProvider dataProviderForOneComplexNumber
     * @param        int $r
     * @param        int $i
     * @throws       \Exception
     */
    public function testAdditiveIdentity(int $r, int $i)
    {
        // Given
        $z = new Complex($r, $i);

        // When
        $z＋0 = $z->add(0);

        $this->assertTrue($z＋0->equals($z));
        $this->assertTrue($z->equals($z＋0));
        $this->assertEquals($z->r, $z＋0->r);
        $this->assertEquals($z->i, $z＋0->i);
    }

    /**
     * @test Axiom: z * 1 = z
     * Multiplicative identity
     * @dataProvider dataProviderForOneComplexNumber
     * @param        int $r
     * @param        int $i
     * @throws       \Exception
     */
    public function testMultiplicativeIdentity(int $r, int $i)
    {
        // Given
        $z = new Complex($r, $i);

        // When
        $z1 = $z->multiply(1);

        $this->assertTrue($z1->equals($z));
        $this->assertTrue($z->equals($z1));
        $this->assertEquals($z->r, $z1->r);
        $this->assertEquals($z->i, $z1->i);
    }

    /**
     * @test Axiom: (∀a)(∃b) a + b = 0
     * Additive inverse.
     * @dataProvider dataProviderForOneComplexNumber
     * @param        int $r
     * @param        int $i
     * @throws       \Exception
     */
    public function testAdditiveInverse(int $r, int $i)
    {
        // Given
        $a = new Complex($r, $i);
        $b = new Complex(-$r, -$i);

        // When
        $a＋b = $a->add($b);

        $this->assertEquals(0, $a＋b->r);
        $this->assertEquals(0, $a＋b->i);
    }

    public function dataProviderForOneComplexNumber(): array
    {
        return [
            [0, 0],
            [0, 0],
            [0, 0],
            [0, 1],
            [1, 0],
            [1, 0],
            [1, 0],
            [1, 1],
            [1, 1],
            [1, 1],
            [1, 1],
            [2, 3],
            [4, 5],
            [7, 4],
            [-5, 2],
            [3, -6],
            [-3, -5],
            [4, 5],
            [3, 6],
            [12, 65],
            [54, -4],
            [-3, 34],
        ];
    }

    public function dataProviderForTwoComplexNumbers(): array
    {
        return [
            [0, 0, 0, 0],
            [0, 0, 0, 1],
            [0, 0, 1, 0],
            [0, 1, 0, 0],
            [1, 0, 0, 0],
            [1, 0, 0, 1],
            [1, 0, 1, 0],
            [1, 1, 0, 0],
            [1, 1, 0, 1],
            [1, 1, 1, 0],
            [1, 1, 1, 1],
            [2, 3, 4, 5],
            [4, 5, 3, 7],
            [7, 4, 5, 1],
            [-5, 2, 7, 2],
            [3, -6, -5, 3],
            [-3, -5, -2, -7],
            [4, 5, -6, -3],
            [3, 6, -4, 43],
            [12, 65, 32, -32],
            [54, -4, 43, -96],
            [-3, 34, 12, -4],
        ];
    }

    public function dataProviderForThreeComplexNumbers(): array
    {
        return [
            [0, 0, 0, 0, 0, 0],
            [0, 0, 0, 1, 0, 0],
            [0, 0, 1, 0, 0, 0],
            [0, 1, 0, 0, 0, 0],
            [1, 0, 0, 0, 1, 0],
            [1, 0, 0, 1, 0, 1],
            [1, 0, 1, 0, 0, 0],
            [1, 1, 0, 0, 0, 0],
            [1, 1, 0, 1, 1, 1],
            [1, 1, 1, 0, 0, 0],
            [1, 1, 1, 1, 0, 0],
            [2, 3, 4, 5, 0, 0],
            [4, 5, 3, 7, 0, 1],
            [7, 4, 5, 1, 1, 0],
            [-5, 2, 7, 2, 5, 3],
            [3, -6, -5, 3, 5, 3],
            [-3, -5, -2, -7, 5, 3],
            [4, 5, -6, -3, 5, 3],
            [3, 6, -4, 43, 5, 3],
            [12, 65, 32, -32, 5, 3],
            [54, -4, 43, -96, 5, 3],
            [-3, 34, 12, -4, 5, 3],
            [1, 2, 3, 4, 5, 6],
            [6, 5, 4, 3, 2, 1],
            [1, -2, 3, -4, 5, -6],
            [-6, 5, -4, 3, -2, 1],
            [345, 765, 235, 123, 765, 456],
        ];
    }
}
