<?php

namespace MathPHP\Tests\Number;

use MathPHP\Number\Quaternion;

/**
 * Tests of quaternion number axioms
 * These tests don't test specific functions,
 * but rather quaternion number axioms which in term make use of multiple functions.
 * If all the quaternion number math is implemented properly, these tests should
 * all work out according to the axioms.
 *
 * Axioms tested:
 *  - Identity
 *    - z + 0 = z
 *    - z * 1 = z
 *  - Inverse
 *    - (∀a)(∃b) a + b = 0
 */
class QuaternionAxiomsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test Axiom: z + 0 = z
     * Additive identity
     * @dataProvider dataProviderForOneQuaternionNumber
     * @param        int $r
     * @param        int $i
     * @throws       \Exception
     */
    public function testAdditiveIdentity(int $r, int $i, int $j, int $k)
    {
        // Given
        $z = new Quaternion($r, $i, $j, $k);

        // When
        $z＋0 = $z->add(0);

        $this->assertTrue($z＋0->equals($z));
        $this->assertTrue($z->equals($z＋0));
        $this->assertEquals($z->r, $z＋0->r);
        $this->assertEquals($z->i, $z＋0->i);
        $this->assertEquals($z->j, $z＋0->j);
        $this->assertEquals($z->k, $z＋0->k);
    }

    /**
     * @test Axiom: z * 1 = z
     * Multiplicative identity
     * @dataProvider dataProviderForOneQuaternionNumber
     * @param        int $r
     * @param        int $i
     * @throws       \Exception
     */
    public function testMultiplicativeIdentity(int $r, int $i, int $j, int $k)
    {
        // Given
        $z = new Quaternion($r, $i, $j, $k);

        // When
        $z1 = $z->multiply(1);

        $this->assertTrue($z1->equals($z));
        $this->assertTrue($z->equals($z1));
        $this->assertEquals($z->r, $z1->r);
        $this->assertEquals($z->i, $z1->i);
        $this->assertEquals($z->j, $z1->j);
        $this->assertEquals($z->k, $z1->k);
    }

    /**
     * @test Axiom: (∀a)(∃b) a + b = 0
     * Additive inverse.
     * @dataProvider dataProviderForOneQuaternionNumber
     * @param        int $r
     * @param        int $i
     * @throws       \Exception
     */
    public function testAdditiveInverse(int $r, int $i, int $j, int $k)
    {
        // Given
        $a = new Quaternion($r, $i, $j, $k);
        $b = new Quaternion(-$r, -$i, -$j, -$k);

        // When
        $a＋b = $a->add($b);

        $this->assertEquals(0, $a＋b->r);
        $this->assertEquals(0, $a＋b->i);
        $this->assertEquals(0, $a＋b->j);
        $this->assertEquals(0, $a＋b->k);
    }

    public function dataProviderForOneQuaternionNumber(): array
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
}
