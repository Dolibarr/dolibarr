<?php

namespace MathPHP\Tests\SetTheory;

use MathPHP\SetTheory\Set;
use MathPHP\LinearAlgebra\Vector;
use MathPHP\LinearAlgebra\NumericMatrix;

class SetTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test interfaces
     */
    public function testInterfaces()
    {
        // When
        $interfaces = class_implements(Set::class);

        // Then
        $this->assertContains('Countable', $interfaces);
        $this->assertContains('Iterator', $interfaces);
    }

    /**
     * @test         asArray
     * @dataProvider dataProviderForAsArray
     * @param        array $members
     * @param        array $expected
     */
    public function testAsArray(array $members, array $expected)
    {
        // Given
        $set = new Set($members);

        // When
        $array = $set->asArray();

        // Then
        $this->assertEquals($expected, $array);
    }

    public function dataProviderForAsArray(): array
    {
        return [
            [
                [],
                [],
            ],
            [
                [0],
                [0 => 0],
            ],
            [
                [1],
                [1 => 1],
            ],
            [
                [5],
                [5 => 5],
            ],
            [
                [-5],
                ['-5' => -5],
            ],
            [
                [1, 2],
                [1 => 1, 2 => 2],
            ],
            [
                [1, 2, 3],
                [1 => 1, 2 => 2, 3 => 3],
            ],
            [
                [1, -2, 3],
                [1 => 1, '-2' => -2, 3 => 3],
            ],
            [
                [1, 2, 3, 4, 5, 6],
                [1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5, 6 => 6],
            ],
            [
                [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
                [1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5, 6 => 6, 7 => 7, 8 => 8, 9 => 9, 10 => 10],
            ],
            [
                [1, 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 2, 2.01, 2.001, 2.15],
                [1 => 1, '1.1' => 1.1, '1.2' => 1.2, '1.3' => 1.3, '1.4' => 1.4, '1.5' => 1.5, '1.6' => 1.6, 2 => 2, '2.01' => 2.01, '2.001' => 2.001, '2.15' => 2.15],
            ],
            [
                ['a'],
                ['a' => 'a'],
            ],
            [
                ['a', 'b'],
                ['a' => 'a', 'b' => 'b'],
            ],
            [
                ['a', 'b', 'c', 'd', 'e'],
                ['a' => 'a', 'b' => 'b', 'c' => 'c', 'd' => 'd', 'e' => 'e'],
            ],
            [
                [1, 2, 'a', 'b', 3.14, 'hello', 'goodbye'],
                [1 => 1, 2 => 2, 'a' => 'a', 'b' => 'b', '3.14' => 3.14, 'hello' => 'hello', 'goodbye' => 'goodbye'],
            ],
            [
                [1, 2, 3, new Set([1, 2]), 'a', 'b'],
                [1 => 1, 2 => 2, 3 => 3, 'Set{1, 2}' => new Set([1, 2]), 'a' => 'a', 'b' => 'b'],
            ],
            [
                ['a', 1, 'b', new Set([1, 'b']), new Set([3, 4, 5,]), '4', 5],
                ['a' => 'a', 1 => 1, 'b' => 'b', 'Set{1, b}' => new Set([1, 'b']), 'Set{3, 4, 5}' => new Set([3, 4, 5,]), '4' => '4', 5 => 5],
            ],
        ];
    }

    /**
     * @test         asArray
     * @dataProvider dataProviderForSingleSet
     * @param        array $members
     */
    public function testAsArrayAnotherWay(array $members)
    {
        // Given
        $set   = new Set($members);

        // When
        $array = $set->asArray();
        $new_set = new Set($array);

        // Then
        $this->assertEquals($new_set, $set);
    }

    /**
     * @test         length
     * @dataProvider dataProviderForSingleSet
     * @param array $members
     */
    public function testLength(array $members)
    {
        // Given
        $set           = new Set($members);
        $expectedCount = count($members);

        // When
        $length = $set->length();

        // Then
        $this->assertEquals($expectedCount, $length);
    }

    /**
     * @test         isEmpty
     * @dataProvider dataProviderForSingleSet
     * @param        array $members
     */
    public function testIsEmpty(array $members)
    {
        // Given
        $set               = new Set($members);
        $expectedEmptiness = empty($members);

        // when
        $isEmpty = $set->isEmpty();

        // Then
        $this->assertEquals($expectedEmptiness, $isEmpty);
    }

    /**
     * @test         isMember
     * @dataProvider dataProviderForSingleSetAtLeastOneMember
     * @param        array $members
     */
    public function testIsMember(array $members)
    {
        // Given
        $set = new Set($members);

        foreach ($members as $member) {
            // When
            $isMember = $set->isMember($member);

            // Then
            $this->assertTrue($isMember);
        }
    }

    /**
     * @test         isNotMember
     * @dataProvider dataProviderForSingleSet
     * @param        array $members
     */
    public function testIsNotMember(array $members)
    {
        // Given
        $set = new Set($members);

        // Then
        $this->assertTrue($set->isNotMember('TotallNotAMember'));
        $this->assertTrue($set->isNotMember('99999123'));
        $this->assertTrue($set->isNotMember(99999123));
    }

    /**
     * @return array
     */
    public function dataProviderForSingleSet(): array
    {
        $fh     = fopen(__FILE__, 'r');
        $vector = new Vector([1, 2, 3]);
        $func   = function ($x) {
            return $x * 2;
        };

        return [
            [[]],
            [[0]],
            [[1]],
            [[5]],
            [[-5]],
            [[1, 2]],
            [[1, 2, 3]],
            [[1, -2, 3]],
            [[1, 2, 3, 4, 5, 6]],
            [[1, 2, 3, 4, 5, 6, 7, 8, 9, 10]],
            [[1, 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 2, 2.01, 2.001, 2.15]],
            [['a']],
            [['a', 'b']],
            [['a', 'b', 'c', 'd', 'e']],
            [[1, 2, 'a', 'b', 3.14, 'hello', 'goodbye']],
            [[1, 2, 3, new Set([1, 2]), 'a', 'b']],
            [['a', 1, 'b', new Set([1, 'b'])]],
            [['a', 1, 'b', new Set([1, 'b']), '4', 5]],
            [['a', 1, 'b', new Set([1, 'b']), new Set([3, 4, 5]), '4', 5]],
            [[1, 2, 3, [1, 2], [2, 3, 4]]],
            [[1, 2, $fh, $vector, [4, 5], 6, 'a', $func, 12, new Set([4, 6, 7]), new Set(), 'sets']],
        ];
    }

    /**
     * @return array
     */
    public function dataProviderForSingleSetAtLeastOneMember(): array
    {
        $fh     = fopen(__FILE__, 'r');
        $vector = new Vector([1, 2, 3]);
        $func   = function ($x) {
            return $x * 2;
        };

        return [
            [[0]],
            [[1]],
            [[5]],
            [[-5]],
            [[1, 2]],
            [[1, 2, 3]],
            [[1, -2, 3]],
            [[1, 2, 3, 4, 5, 6]],
            [[1, 2, 3, 4, 5, 6, 7, 8, 9, 10]],
            [[1, 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 2, 2.01, 2.001, 2.15]],
            [['a']],
            [['a', 'b']],
            [['a', 'b', 'c', 'd', 'e']],
            [[1, 2, 'a', 'b', 3.14, 'hello', 'goodbye']],
            [[1, 2, 3, new Set([1, 2]), 'a', 'b']],
            [['a', 1, 'b', new Set([1, 'b'])]],
            [['a', 1, 'b', new Set([1, 'b']), '4', 5]],
            [['a', 1, 'b', new Set([1, 'b']), new Set([3, 4, 5]), '4', 5]],
            [[1, 2, 3, [1, 2], [2, 3, 4]]],
            [[1, 2, $fh, $vector, [4, 5], 6, 'a', $func, 12, new Set([4, 6, 7]), new Set(), 'sets']],
        ];
    }

    /**
     * @test         count
     * @dataProvider dataProviderForSingleSet
     * @param        array $A
     */
    public function testCount(array $A)
    {
        // Given
        $set = new Set($A);
        $expectedCount = count($A);

        // When
        $count = count($set);

        // Then
        $this->assertEquals($expectedCount, $count);
        $this->assertEquals($set->length(), $count);
    }

    /**
     * @test         String representation
     * @dataProvider dataProviderForToString
     * @param        array  $members
     * @param        string $expected
     */
    public function testToString(array $members, string $expected)
    {
        // Given
        $set = new Set($members);

        // When
        $stringRepresentation = (string) $set;

        // Then
        $this->assertSame($expected, $stringRepresentation);
    }

    public function dataProviderForToString(): array
    {
        $vector      = new Vector([1, 2, 3]);
        $vector_hash = spl_object_hash($vector);

        return [
            [
                [],
                'Ø',
            ],
            [
                [new Set()],
                'Set{Ø}',
            ],
            [
                [0],
                'Set{0}',
            ],
            [
                [1],
                'Set{1}',
            ],
            [
                [5],
                'Set{5}',
            ],
            [
                [-5],
                'Set{-5}',
            ],
            [
                [1, 2],
                'Set{1, 2}',
            ],
            [
                [1, 2, 3],
                'Set{1, 2, 3}',
            ],
            [
                [1, 2, 3, new Set()],
                'Set{1, 2, 3, Ø}',
            ],
            [
                [1, -2, 3],
                'Set{1, -2, 3}',
            ],
            [
                [1, 2, 3, 4, 5, 6],
                'Set{1, 2, 3, 4, 5, 6}',
            ],
            [
                [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
                'Set{1, 2, 3, 4, 5, 6, 7, 8, 9, 10}',
            ],
            [
                [1, 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 2, 2.01, 2.001, 2.15],
                'Set{1, 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 2, 2.01, 2.001, 2.15}',
            ],
            [
                ['a'],
                'Set{a}',
            ],
            [
                ['a', 'b'],
                'Set{a, b}',
            ],
            [
                ['a', 'b', 'c', 'd', 'e'],
                'Set{a, b, c, d, e}',
            ],
            [
                [1, 2, 'a', 'b', 3.14, 'hello', 'goodbye'],
                'Set{1, 2, a, b, 3.14, hello, goodbye}',
            ],
            [
                [1, 2, 3, new Set([1, 2]), 'a', 'b'],
                'Set{1, 2, 3, Set{1, 2}, a, b}',
            ],
            [
                ['a', 1, 'b', new Set([1, 'b']), new Set([3, 4, 5]), '4', 5],
                'Set{a, 1, b, Set{1, b}, Set{3, 4, 5}, 4, 5}',
            ],
            [
                [1, 2, new Set([1, 2, new Set([1, 2])])],
                'Set{1, 2, Set{1, 2, Set{1, 2}}}',
            ],
            [
                [1, 2, [1, 2, 3]],
                'Set{1, 2, Array(a:3:{i:0;i:1;i:1;i:2;i:2;i:3;})}',
            ],
            [
                [1, 2, [1, 2, 3], [1, 2, 3]],
                'Set{1, 2, Array(a:3:{i:0;i:1;i:1;i:2;i:2;i:3;})}',
            ],
            [
                [1, 2, $vector],
                "Set{1, 2, MathPHP\LinearAlgebra\Vector($vector_hash)}",
            ],
            [
                [1, 2, $vector, $vector],
                "Set{1, 2, MathPHP\LinearAlgebra\Vector($vector_hash)}",
            ],
        ];
    }

    /**
     * @test iterator interface
     */
    public function testIteratorInterface()
    {
        // Given
        $set = new Set([1, 2, 3, 4, 5]);

        $i = 1;
        foreach ($set as $key => $value) {
            // Then
            $this->assertEquals($i, $key);
            $this->assertEquals($i, $value);
            $i++;
        }
    }

    /**
     * @test iterator interface
     */
    public function testIteratorInterface2()
    {
        // Given
        $set = new Set([new Set([1, 2]), new Set([3, 4])]);

        $i = 1;
        foreach ($set as $key => $value) {
            // Then
            if ($i === 1) {
                $this->assertEquals(('Set{1, 2}'), $key);
                $this->assertEquals(new Set([1, 2]), $value);
            }
            if ($i === 2) {
                $this->assertEquals(('Set{3, 4}'), $key);
                $this->assertEquals(new Set([3, 4]), $value);
            }
            $i++;
        }
    }

    /**
     * @test Fluent interface
     */
    public function testFluentInterface()
    {
        // Given
        $A = new Set();
        $B = new Set([3, 4, 7, new Set([1, 2, 3])]);

        // When
        $A->add(1)
          ->add(2)
          ->add(3)
          ->remove(2)
          ->add(4)
          ->remove(1)
          ->addMulti([5, 6, 7])
          ->add(new Set([1, 2, 3]))
          ->removeMulti([5, 6]);

        // Then
        $this->assertEquals($B, $A);
    }
}
