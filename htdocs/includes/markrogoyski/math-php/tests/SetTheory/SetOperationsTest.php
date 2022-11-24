<?php

namespace MathPHP\Tests\SetTheory;

use MathPHP\SetTheory\Set;
use MathPHP\LinearAlgebra\Vector;
use MathPHP\LinearAlgebra\NumericMatrix;

class SetOperationsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test
     * @dataProvider dataProviderForAdd
     */
    public function testAdd(array $A, $x, array $R)
    {
        // Given
        $setA = new Set($A);
        $setR = new Set($R);

        // When
        $setA->add($x);

        // Then
        $this->assertEquals($setR, $setA);
        $this->assertEquals($setR->asArray(), $setA->asArray());
    }

    /**
     * @test
     * @dataProvider dataProviderForAdd
     */
    public function testAddTwiceDoesNothing(array $A, $x, array $R)
    {
        // Given
        $setA = new Set($A);
        $setR = new Set($R);

        // When
        $setA->add($x);
        $setA->add($x);

        // Then
        $this->assertEquals($setR, $setA);
    }

    public function dataProviderForAdd(): array
    {
        $vector = new Vector([1, 2, 3]);

        return [
            [
                [],
                null,
                [null],
            ],
            [
                [],
                new Set(),
                ['Ø' => new Set()],
            ],
            [
                [],
                1,
                [1 => 1],
            ],
            [
                [1, 2, 3],
                4,
                [1, 2, 3, 4],
            ],
            [
                [1, 2, 3],
                1,
                [1, 2, 3],
            ],
            [
                [1, 2, 3],
                'new',
                [1, 2, 3, 'new'],
            ],
            [
                [1, 2, 3],
                3.1,
                [1, 2, 3, 3.1],
            ],
            [
                [1, 2, 3],
                new Set(),
                [1, 2, 3, 'Ø'],
            ],
            [
                [1, 2, 3],
                new Set([4, 5]),
                [1, 2, 3, 'Set{4, 5}'],
            ],
            [
                [1, 2, 3],
                new Set([1, 2]),
                [1, 2, 3, 'Set{1, 2}'],
            ],
            [
                [1, 2, 3],
                -3,
                [1, 2, 3, -3],
            ],
            [
                [1, 2, 3],
                $vector,
                [1, 2, 3, $vector],
            ],
        ];
    }

    /**
     * When adding objects to a set, the key becomes to the objects hash.
     * The object is stored as is as the value.
     */
    public function testAddWithObjects()
    {
        // Given
        $set    = new Set([1, 2, 3]);
        $vector = new Vector([1, 2, 3]);
        $matrix = new NumericMatrix([[1,2,3],[2,3,4]]);

        // When
        $set->add($vector);
        $set->add($matrix);

        // Then
        $this->assertEquals(5, count($set));
        $this->assertEquals(5, count($set->asArray()));

        $objects = 0;
        foreach ($set as $key => $value) {
            if ($value instanceof \MathPHP\LinearAlgebra\Vector) {
                $objects++;
                $vector_key = \get_class($value) . '(' . spl_object_hash($vector) . ')';
                $this->assertEquals($vector_key, $key);
                $this->assertEquals($vector, $value);
            }
            if ($value instanceof \MathPHP\LinearAlgebra\NumericMatrix) {
                $objects++;
                $matrix_key = \get_class($value) . '(' . spl_object_hash($matrix) . ')';
                $this->assertEquals($matrix_key, $key);
                $this->assertEquals($matrix, $value);
            }
        }

        // There should have been two objects (vector and matrix)
        $this->assertEquals(2, $objects);
    }

    public function testAddWithMultipleObjects()
    {
        // Given
        $set     = new Set([1, 2, 3]);
        $vector1 = new Vector([1, 2, 3]);
        $vector2 = new Vector([1, 2, 3]);
        $vector3 = new Vector([4, 5, 6]);
        $matrix  = new NumericMatrix([[1,2,3],[2,3,4]]);
        $std1    = new \StdClass();
        $std2    = new \StdClass();
        $std3    = $std2; // Same object so this wont get added

        // When
        $set->add($vector1);
        $set->add($vector2);
        $set->add($vector3);
        $set->add($matrix);
        $set->add($std1);
        $set->add($std2);
        $set->add($std3);

        // Then
        $this->assertEquals(9, count($set));
        $this->assertEquals(9, count($set->asArray()));

        $objects = 0;
        foreach ($set as $key => $value) {
            if ($value instanceof \MathPHP\LinearAlgebra\Vector) {
                $objects++;
                $this->assertInstanceOf(\MathPHP\LinearAlgebra\Vector::class, $value);
            }
            if ($value instanceof \MathPHP\LinearAlgebra\NumericMatrix) {
                $objects++;
                $this->assertInstanceOf(\MathPHP\LinearAlgebra\NumericMatrix::class, $value);
            }
            if ($value instanceof \StdClass) {
                $objects++;
                $this->assertInstanceOf(\StdClass::class, $value);
            }
        }

        // There should have been four objects (3 vectors and 1 matrix)
        $this->assertEquals(6, $objects);
    }

    public function testAddWithDuplicateObjects()
    {
        // Given
        $set    = new Set([1, 2, 3]);
        $vector = new Vector([1, 2, 3]);

        // When adding the same object twice.
        $set->add($vector);
        $set->add($vector);

        // Then
        $this->assertEquals(4, count($set));
        $this->assertEquals(4, count($set->asArray()));

        $objects = 0;
        foreach ($set as $key => $value) {
            if ($value instanceof \MathPHP\LinearAlgebra\Vector) {
                $objects++;
                $vector_key = \get_class($value) . '(' . spl_object_hash($vector) . ')';
                $this->assertEquals($vector_key, $key);
                $this->assertEquals($vector, $value);
            }
        }

        // There should have only been one vector object.
        $this->assertEquals(1, $objects);
    }

    /**
     * In this case, we add an array that contains arrays.
     * So each array element will be added, but with the implementation
     * detail that they will be converted into ArrayObjects.
     */
    public function testAddMultiWithArrayOfArrays()
    {
        // Given
        $set   = new Set([1, 2, 3]);
        $array = [4, 5, [1, 2, 3]];

        // When
        $set->addMulti($array);

        // Then
        $this->assertEquals(6, count($set));
        $this->assertEquals(6, count($set->asArray()));

        $arrays = 0;
        foreach ($set as $key => $value) {
            if (\is_array($value)) {
                $arrays++;
                $this->assertEquals([1, 2, 3], $value);
                $this->assertEquals(3, count($value));
                $this->assertEquals(1, $value[0]);
                $this->assertEquals(1, $value[0]);
                $this->assertEquals(1, $value[0]);
            }
        }

        // There should have only been one array.
        $this->assertEquals(1, $arrays);
    }

    /**
     * In this case, we add an array that contains arrays.
     * So each array element will be added, but with the implementation
     * detail that they will be converted into ArrayObjects.
     */
    public function testAddMultiWithArrayOfArraysMultipleArraysAndDuplicates()
    {
        // Given
        $set   = new Set([1, 2, 3]);
        $array = [4, 5, [1, 2, 3], [1, 2, 3], [5, 5, 5]];

        // When
        $set->addMulti($array);

        // Then, only 7, because [1, 2, 3] was in there twice.
        $this->assertEquals(7, count($set));
        $this->assertEquals(7, count($set->asArray()));

        $arrays = 0;
        foreach ($set as $key => $value) {
            if (\is_array($value)) {
                $arrays++;
                $this->assertEquals(3, count($value));
            }
        }

        // There should have been 2 arrays.
        $this->assertEquals(2, $arrays);
    }

    /**
     * When adding resources to a set, the key becomes to the resource ID.
     * The resource is stored as is as the value.
     */
    public function testAddWithResources()
    {
        // Given
        $set = new Set();
        $fh  = fopen(__FILE__, 'r');

        // When
        $set->add($fh);
        $set->add($fh); // Should only get added once

        // Then
        $this->assertEquals(1, count($set));
        $this->assertEquals(1, count($set->asArray()));

        $resources = 0;
        foreach ($set as $key => $value) {
            if (\is_resource($value)) {
                $resources++;
                $vector_key = 'Resource(' . \strval($value) . ')';
                $this->assertEquals($vector_key, $key);
                $this->assertEquals($fh, $value);
            }
        }

        // There should have been one resource
        $this->assertEquals(1, $resources);
    }

    /**
     * @test
     * @dataProvider dataProviderForAddMulti
     */
    public function testAddMulti(array $A, array $x, array $R)
    {
        // Given
        $setA = new Set($A);
        $setR = new Set($R);

        // When
        $setA->addMulti($x);

        // Then
        $this->assertEquals($setR, $setA);
        $this->assertEquals($setR->asArray(), $setA->asArray());
    }

    public function dataProviderForAddMulti(): array
    {
        $vector = new Vector([1, 2, 3]);

        return [
            [
                [],
                [1],
                [1],
            ],
            [
                [],
                [1, 2],
                [1, 2],
            ],
            [
                [1, 2, 3],
                [],
                [1, 2, 3],
            ],
            [
                [1, 2, 3],
                [4],
                [1, 2, 3, 4],
            ],
            [
                [1, 2, 3],
                [4, 5],
                [1, 2, 3, 4, 5],
            ],
            [
                [1, 2, 3],
                [4, 5, 6],
                [1, 2, 3, 4, 5, 6],
            ],
            [
                [1, 2, 3],
                [1, 2, 3],
                [1, 2, 3],
            ],
            [
                [1, 2, 3],
                [1, 2, 3, 4],
                [1, 2, 3, 4],
            ],
            [
                [1, 2, 3],
                ['new', 4],
                [1, 2, 3, 'new', 4],
            ],
            [
                [1, 2, 3],
                [3.1, 4],
                [1, 2, 3, 3.1, 4],
            ],
            [
                [1, 2, 3],
                [new Set()],
                [1, 2, 3, 'Ø'],
            ],
            [
                [1, 2, 3],
                [new Set([4, 5])],
                [1, 2, 3, 'Set{4, 5}'],
            ],
            [
                [1, 2, 3],
                [new Set([1, 2]), 4],
                [1, 2, 3, 'Set{1, 2}', 4],
            ],
            [
                [1, 2, 3],
                [new Set([1, 2]), 6, 7, new Set([1, 2]), new Set([3, 4])],
                [1, 2, 3, 'Set{1, 2}', 6, 7, 'Set{3, 4}'],
            ],
            [
                [1, 2, 3],
                [-3],
                [1, 2, 3, -3],
            ],
            [
                [1, 2, 3],
                [4, $vector],
                [1, 2, 3, 4, $vector],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dataProviderForRemove
     */
    public function testRemove(array $A, $x, array $R)
    {
        // Given
        $setA = new Set($A);
        $setR = new Set($R);

        // When
        $setA->remove($x);

        // Then
        $this->assertEquals($setR, $setA);
    }

    public function dataProviderForRemove(): array
    {
        $vector = new Vector([1, 2, 3]);
        $fh     = fopen(__FILE__, 'r');

        return [
            [
                [],
                null,
                [],
            ],
            [
                [null],
                null,
                [],
            ],
            [
                [1],
                1,
                [],
            ],
            [
                [1, 2, 3],
                1,
                [2, 3],
            ],
            [
                [1, 2, 3],
                2,
                [1, 3],
            ],
            [
                [1, 2, 3],
                3,
                [1, 2],
            ],
            [
                [1, 2, 3],
                5,
                [1, 2, 3],
            ],
            [
                [1, 2, 3],
                -1,
                [1, 2, 3],
            ],
            [
                [1, 2, 3, -3],
                -3,
                [1, 2, 3],
            ],
            [
                [1, 2, 3, 4.5, 6.7],
                4.5,
                [1, 2, 3, 6.7],
            ],
            [
                [1, 2, 3, 'a', 'b', 'see'],
                'b',
                [1, 2, 3, 'a', 'see'],
            ],
            [
                [1, 2, 3, new Set([1, 2])],
                1,
                [2, 3, 'Set{1, 2}'],
            ],
            [
                [1, 2, 3, new Set([1, 2])],
                new Set([1, 2]),
                [1, 2, 3],
            ],
            [
                [1, 2, 3, new Set([1, 2])],
                'Set{1, 2}',
                [1, 2, 3],
            ],
            [
                [1, 2, 3, [1, 2, 3]],
                [1, 2, 3],
                [1, 2, 3],
            ],
            [
                [1, 2, 3, [1, 2, 3], [2, 3], [4, 5, 6]],
                [2, 3],
                [1, 2, 3, [1, 2, 3], [4, 5, 6]],
            ],
            [
                [1, 2, 3, [1, 2, 3], [2, 3], [4, 5, 6]],
                [4, 5, 6],
                [1, 2, 3, [1, 2, 3], [2, 3]],
            ],
            [
                [1, 2, 3, [1, 2, 3]],
                [6, 7, 3],
                [1, 2, 3, [1, 2, 3]],
            ],
            [
                [1, 2, 3, $vector],
                $vector,
                [1, 2, 3],
            ],
            [
                [1, 2, 3, $vector],
                [$vector], // Array containing vector
                [1, 2, 3, $vector],
            ],
            [
                [1, 2, 3, $vector],
                [1, $vector], // array containing 1 and vector
                [1, 2, 3, $vector],
            ],
            [
                [1, 2, 3, $fh],
                $fh,
                [1, 2, 3],
            ],
            [
                [1, 2, 3, $fh],
                [1, $fh], // array containing 1 and f1
                [1, 2, 3, $fh],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dataProviderForRemoveMulti
     */
    public function testRemoveMulti(array $A, array $x, array $R)
    {
        // Given
        $setA = new Set($A);
        $setR = new Set($R);

        // When
        $setA->removeMulti($x);

        // Then
        $this->assertEquals($setR, $setA);
    }

    public function dataProviderForRemoveMulti(): array
    {
        $vector = new Vector([1, 2, 3]);
        $fh     = fopen(__FILE__, 'r');

        return [
            [
                [],
                [],
                [],
            ],
            [
                [],
                [null],
                [],
            ],
            [
                [null],
                [null],
                [],
            ],
            [
                [1],
                [1],
                [],
            ],
            [
                [1],
                [1],
                [],
            ],
            [
                [1, 2, 3],
                [1],
                [2, 3],
            ],
            [
                [1, 2, 3],
                [2],
                [1, 3],
            ],
            [
                [1, 2, 3],
                [3],
                [1, 2],
            ],
            [
                [1, 2, 3],
                [4],
                [1, 2, 3],
            ],
            [
                [1, 2, 3],
                [5, 6],
                [1, 2, 3],
            ],
            [
                [1, 2, 3],
                [3, 4, 5],
                [1, 2],
            ],
            [
                [1, 2, 3],
                [1, 2],
                [3],
            ],
            [
                [1, 2, 3],
                [2, 3],
                [1],
            ],
            [
                [1, 2, 3],
                [1, 3],
                [2],
            ],
            [
                [1, 2, 3],
                [5, 'a'],
                [1, 2, 3],
            ],
            [
                [1, 2, 3],
                [-1],
                [1, 2, 3],
            ],
            [
                [1, 2, 3, -3],
                [-3],
                [1, 2, 3],
            ],
            [
                [1, 2, 3, 4.5, 6.7],
                [4.5, 10],
                [1, 2, 3, 6.7],
            ],
            [
                [1, 2, 3, 'a', 'b', 'see'],
                ['b', 'z'],
                [1, 2, 3, 'a', 'see'],
            ],
            [
                [1, 2, 3, 'a', 'b', 'see'],
                ['b', 1, 'see', 5555],
                [ 2, 3, 'a'],
            ],
            [
                [1, 2, 3, new Set([1, 2])],
                [1],
                [2, 3, 'Set{1, 2}'],
            ],
            [
                [1, 2, 3, new Set([1, 2])],
                [new Set([1, 2])],
                [1, 2, 3],
            ],
            [
                [1, 2, 3, new Set([1, 2])],
                ['Set{1, 2}'],
                [1, 2, 3],
            ],
            [
                [1, 2, 3, [1, 2, 3]],
                [1, 2, 3],
                [[1, 2, 3]],
            ],
            [
                [1, 2, 3, [1, 2, 3], [2, 3], [4, 5, 6]],
                [2, 3],
                [1, [1, 2, 3], [2, 3], [4, 5, 6]],
            ],
            [
                [1, 2, 3, [1, 2, 3], [2, 3], [4, 5, 6]],
                [4, 5, 6],
                [1, 2, 3, [1, 2, 3], [2, 3], [4, 5, 6]],
            ],
            [
                [1, 2, 3, [1, 2, 3]],
                [[1, 2, 3]],
                [1, 2, 3],
            ],
            [
                [1, 2, 3, [1, 2, 3], [2, 3], [4, 5, 6]],
                [[2, 3]],
                [1, 2, 3, [1, 2, 3], [4, 5, 6]],
            ],
            [
                [1, 2, 3, [1, 2, 3], [2, 3], [4, 5, 6]],
                [[4, 5, 6]],
                [1, 2, 3, [1, 2, 3], [2, 3]],
            ],
            [
                [1, 2, 3, [1, 2, 3], [2, 3], [4, 5, 6]],
                [[1, 2, 3], [4, 5, 6]],
                [1, 2, 3, [2, 3]],
            ],
            [
                [1, 2, 3, [1, 2, 3], [2, 3], [4, 5, 6]],
                [1, [4, 5, 6], 3],
                [2, [1, 2, 3], [2, 3]],
            ],
            [
                [1, 2, 3, $vector],
                [$vector, 9],
                [1, 2, 3],
            ],
            [
                [1, 2, 3, $vector],
                [$vector],
                [1, 2, 3],
            ],
            [
                [1, 2, 3, $vector],
                [1, $vector],
                [2, 3],
            ],
            [
                [1, 2, 3, $fh],
                [1, $fh],
                [2, 3],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dataProviderForIsDisjoint
     */
    public function testIsDisjoint(array $A, array $B)
    {
        // Given
        $setA = new Set($A);
        $setB = new Set($B);

        // Then
        $this->assertTrue($setA->isDisjoint($setB));
    }

    public function dataProviderForIsDisjoint(): array
    {
        return [
            [
                [],
                [2],
            ],
            [
                [1],
                [],
            ],
            [
                [1],
                [2],
            ],
            [
                [1, 2, 3],
                [4, 5, 6],
            ],
            [
                [1, 2, 3,],
                [1.1, 2.2, -3],
            ],
            [
                [1, 2, 3, 'a', 'b'],
                [4, 5, 6, 'c', 'd'],
            ],
            [
                [1, 2, 3, new Set([1, 2])],
                [4, 5, 6],
            ],
            [
                [1, 2, 3, new Set([1, 2])],
                [4, 5, 6, new Set([2, 3])],
            ],
            [
                [new Set([1, 2])],
                [new Set([2, 3])],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dataProviderForNotDisjoint
     */
    public function testNotDisjoint(array $A, array $B)
    {
        // Given
        $setA = new Set($A);
        $setB = new Set($B);

        // Then
        $this->assertFalse($setA->isDisjoint($setB));
    }

    public function dataProviderForNotDisjoint(): array
    {
        return [
            [
                [1],
                [1],
            ],
            [
                [new Set()],
                [new Set()],
            ],
            [
                [new Set([1, 2])],
                [new Set([1, 2])],
            ],
            [
                [1, 2, 3],
                [3, 4, 5],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dataProviderForIsSubsetSuperset
     */
    public function testIsSubset(array $A, array $B)
    {
        // Given
        $setA = new Set($A);
        $setB = new Set($B);

        // Then
        $this->assertTrue($setA->isSubset($setB));
    }

    public function dataProviderForIsSubsetSuperset(): array
    {
        return [
            [
                [],
                [1],
            ],
            [
                [1],
                [1],
            ],
            [
                [1, 2],
                [1, 2],
            ],
            [
                [1, 2],
                [1, 2, 3],
            ],
            [
                [1, 2, 'a'],
                [1, 2, 3, 'a', 4.5, new Set([1, 2])],
            ],
            [
                [1, 2, 'a', new Set([1, 2])],
                [1, 2, 3, 'a', 4.5, new Set([1, 2])],
            ],
            [
                [1, 2, 'a', new Set([1, 2]), -1, 2.4],
                [1, 2, 3, 'a', 4.5, new Set([1, 2]), -1, -2, 2.4, 3.5],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dataProviderForIsNotSubset
     */
    public function testIsNotSubset(array $A, array $B)
    {
        // Given
        $setA = new Set($A);
        $setB = new Set($B);

        // Then
        $this->assertFalse($setA->isSubset($setB));
    }

    public function dataProviderForIsNotSubset(): array
    {
        return [
            [
                [1],
                [],
            ],
            [
                [1, 2],
                [1],
            ],
            [
                [1, 2, 3],
                [1, 2],
            ],
            [
                [1, 2, 3, 4],
                [1, 2, 3],
            ],
            [
                [1, 2, 'b'],
                [1, 2, 3, 'a', 4.5, new Set([1, 2])],
            ],
            [
                [1, 2, 3, 'a', 4.5, new Set([1, 2])],
                [1, 2, 'a', new Set([1, 2])],
            ],
            [
                [1, 2, 3, 'a', 4.5, new Set([1, 2]), -1, -2, 2.4, 3.5],
                [1, 2, 'a', new Set([1, 2]), -1, 2.4],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dataProviderForIsProperSet
     */
    public function testIsProperSubset(array $A, array $B)
    {
        // Given
        $setA = new Set($A);
        $setB = new Set($B);

        // Then
        $this->assertTrue($setA->isProperSubset($setB));
    }

    /**
     * @test
     * @dataProvider dataProviderForIsProperSet
     */
    public function testIsProperSuperset(array $A, array $B)
    {
        // Given
        $setA = new Set($B);
        $setB = new Set($A);

        // Then
        $this->assertFalse($setA->isProperSuperset($setB));
    }

    public function dataProviderForIsProperSet(): array
    {
        return [
            [
                [],
                [],
            ],
            [
                [1],
                [1],
            ],
            [
                [1, 2],
                [1, 2],
            ],
            [
                [1, 3, 2],
                [1, 2, 3],
            ],
            [
                [1, 2,'a', 3, 4.5, new Set([1, 2])],
                [1, 2, 3, 'a', 4.5, new Set([1, 2])],
            ],
            [
                [1, 2, 3, 'a', 4.5, new Set([1, 2])],
                [1, 2, 3, 'a', 4.5, new Set([1, 2])],
            ],
            [
                [1, 2, 3, 'a', 4.5, new Set([1, 2]), -1, -2, 2.4, 3.5],
                [1, 2, 3, 'a', 4.5, new Set([1, 2]), -1, -2, 2.4, 3.5],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dataProviderForIsSubsetSuperset
     */
    public function testIsSuperset(array $A, array $B)
    {
        // Given
        $setA = new Set($B);
        $setB = new Set($A);

        // Then
        $this->assertTrue($setA->isSuperset($setB));
    }

    /**
     * @test
     * @dataProvider dataProviderForUnion
     */
    public function testUnion(array $A, array $B, array $A∪B, Set $R)
    {
        // Given
        $setA        = new Set($A);
        $setB        = new Set($B);
        $expected    = new Set($A∪B);

        // When
        $union       = $setA->union($setB);
        $union_array = $union->asArray();

        // Then
        $this->assertEquals($R, $union);
        $this->assertEquals($expected, $union);
        $this->assertEquals(count($A∪B), count($union));
        foreach ($A∪B as $member) {
            $this->assertArrayHasKey("$member", $union_array);
        }
        foreach ($A∪B as $_ => $value) {
            if ($value instanceof Set) {
                $this->assertEquals($value, $union_array["$value"]);
            } else {
                $this->assertArrayHasKey((string) $value, $union_array);
            }
        }
    }

    public function dataProviderForUnion(): array
    {
        $setOneTwo = new Set([1, 2]);

        return [
            [
                [],
                [],
                [],
                new Set(),
            ],
            [
                [1],
                [],
                [1],
                new Set([1]),
            ],
            [
                [],
                [1],
                [1],
                new Set([1]),
            ],
            [
                [1],
                [1],
                [1],
                new Set([1]),
            ],
            [
                [1],
                [2],
                [1, 2],
                new Set([1, 2]),
            ],
            [
                [2],
                [1],
                [1, 2],
                new Set([1, 2]),
            ],
            [
                [1],
                [2],
                [2, 1],
                new Set([1, 2]),
            ],
            [
                [2],
                [1],
                [2, 1],
                new Set([1, 2]),
            ],
            [
                [1, 2, 3, 'a', 'b'],
                [1, 'a', 'k'],
                [1, 2, 3, 'a', 'b', 'k'],
                new Set([1, 2, 3, 'a', 'b', 'k']),
            ],
            [
                [1, 2, 3, 'a', 'b', $setOneTwo],
                [1, 'a', 'k'],
                [1, 2, 3, 'a', 'b', 'k', $setOneTwo],
                new Set([1, 2, 3, 'a', 'b', 'k', $setOneTwo]),
            ],
            [
                [1, 2, 3, 'a', 'b'],
                [1, 'a', 'k', $setOneTwo],
                [1, 2, 3, 'a', 'b', 'k', $setOneTwo],
                new Set([1, 2, 3, 'a', 'b', 'k', $setOneTwo]),
            ],
            [
                [1, 2, 3, 'a', 'b', new Set()],
                [1, 'a', 'k', $setOneTwo],
                [1, 2, 3, 'a', 'b', 'k', $setOneTwo, new Set()],
                new Set([1, 2, 3, 'a', 'b', 'k', $setOneTwo, new Set()]),
            ],
            [
                [1, 2, 3, 'a', 'b', $setOneTwo],
                [1, 'a', 'k', -2, '2.4', 3.5, $setOneTwo],
                [1, 2, 3, 'a', 'b', 'k', -2, '2.4', 3.5, $setOneTwo],
                new Set([1, 2, 3, 'a', 'b', 'k', -2, '2.4', 3.5, $setOneTwo]),
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dataProviderForUnionMultipleSets
     */
    public function testUnionMultipleSets(array $A, array $B, array $C, array $A∪B∪C, Set $R)
    {
        // Given
        $setA        = new Set($A);
        $setB        = new Set($B);
        $setC        = new Set($C);
        $expected    = new Set($A∪B∪C);

        // When
        $union       = $setA->union($setB, $setC);
        $union_array = $union->asArray();

        // Then
        $this->assertEquals($R, $union);
        $this->assertEquals($expected, $union);
        $this->assertEquals(count($A∪B∪C), count($union));
        foreach ($A∪B∪C as $member) {
            $this->assertArrayHasKey("$member", $union_array);
        }
        foreach ($A∪B∪C as $_ => $value) {
            if ($value instanceof Set) {
                $this->assertEquals($value, $union_array["$value"]);
            } else {
                $this->assertArrayHasKey((string) $value, $union_array);
            }
        }
    }

    public function dataProviderForUnionMultipleSets(): array
    {
        return [
            [
                [1, 2, 3],
                [2, 3, 4],
                [3, 4, 5],
                [1, 2, 3, 4, 5],
                new Set([1, 2, 3, 4, 5]),
            ],
            [
                [1, 2, 3, -3, 3.4],
                [2, 3, 4, new Set()],
                [3, 4, 5, new Set([1, 2])],
                [1, 2, 3, 4, 5, -3, 3.4, new Set(), new Set([1, 2])],
                new Set([1, 2, 3, 4, 5, -3, 3.4, new Set(), new Set([1, 2])]),
            ],
        ];
    }

    public function testUnionWithArrays()
    {
        // Given
        $A        = new Set([1, 2, [1, 2, 3]]);
        $B        = new Set([2, 3, [2, 3, 4]]);
        $expected = new Set([1, 2, [1, 2, 3], 3, [2, 3, 4]]);

        // When
        $A∪B = $A->union($B);

        // Then
        $this->assertEquals($expected, $A∪B);
        $this->assertEquals($expected->asArray(), $A∪B->asArray());
    }

    public function testUnionWithArrays2()
    {
        // Given
        $A        = new Set([1, 2, [1, 2, 3]]);
        $B        = new Set([2, 3, [2, 3, 4], [1, 2, 3]]);
        $expected = new Set([1, 2, [1, 2, 3], 3, [2, 3, 4]]);

        // When
        $A∪B = $A->union($B);

        // Then
        $this->assertEquals($expected, $A∪B);
        $this->assertEquals($expected->asArray(), $A∪B->asArray());
    }

    public function testUnionWithObjects()
    {
        // Given
        $vector1  = new Vector([1, 2, 3]);
        $vector2  = new Vector([1, 2, 3]);
        $A        = new Set([1, 2, $vector1]);
        $B        = new Set([2, 3, $vector2]);
        $expected = new Set([1, 2, $vector1, 3, $vector2]);

        // When
        $A∪B = $A->union($B);

        // Then
        $this->assertEquals($expected, $A∪B);
        $this->assertEquals($expected->asArray(), $A∪B->asArray());
    }

    public function testUnionWithObjects2()
    {
        // Given
        $vector1  = new Vector([1, 2, 3]);
        $vector2  = new Vector([1, 2, 3]);
        $A        = new Set([1, 2, $vector1]);
        $B        = new Set([2, 3, $vector2, $vector1]);
        $expected = new Set([1, 2, $vector1, 3, $vector2]);

        // When
        $A∪B = $A->union($B);

        // Then
        $this->assertEquals($expected, $A∪B);
        $this->assertEquals($expected->asArray(), $A∪B->asArray());
    }

    /**
     * @test
     * @dataProvider dataProviderForIntersect
     */
    public function testIntersect(array $A, array $B, array $A∩B, Set $R)
    {
        // Given
        $setA               = new Set($A);
        $setB               = new Set($B);
        $expected           = new Set($A∩B);

        // When
        $intersection       = $setA->intersect($setB);
        $intersection_array = $intersection->asArray();

        // Then
        $this->assertEquals($R, $intersection);
        $this->assertEquals($expected, $intersection);
        $this->assertEquals(count($A∩B), count($intersection));
        foreach ($A∩B as $member) {
            $this->assertArrayHasKey("$member", $intersection_array);
            $this->assertArrayHasKey("$member", $setA->asArray());
            $this->assertArrayHasKey("$member", $setB->asArray());
            $this->assertContains($member, $A);
            $this->assertContains($member, $B);
        }
        foreach ($A∩B as $_ => $value) {
            if ($value instanceof Set) {
                $this->assertEquals($value, $intersection_array["$value"]);
            } else {
                $this->assertContains($value, $intersection_array);
            }
        }
    }

    public function dataProviderForIntersect(): array
    {
        $setOneTwo = new Set([1, 2]);

        return [
            [
                [],
                [],
                [],
                new Set(),
            ],
            [
                [1],
                [],
                [],
                new Set(),
            ],
            [
                [],
                [1],
                [],
                new Set(),
            ],
            [
                [1],
                [1],
                [1],
                new Set([1]),
            ],
            [
                [1],
                [2],
                [],
                new Set(),
            ],
            [
                [2],
                [1],
                [],
                new Set(),
            ],
            [
                [2],
                [2],
                [2],
                new Set([2]),
            ],
            [
                [1, 2],
                [1, 2],
                [1, 2],
                new Set([1, 2]),
            ],
            [
                [1, 2],
                [2, 1],
                [1, 2],
                new Set([1, 2]),
            ],
            [
                [1, 2, 3, 'a', 'b'],
                [1, 'a', 'k'],
                [1, 'a'],
                new Set([1, 'a']),
            ],
            [
                [1, 2, 3, 'a', 'b', new Set([1, 2])],
                [1, 'a', 'k'],
                [1, 'a'],
                new Set([1, 'a']),
            ],
            [
                [1, 2, 3, 'a', 'b'],
                [1, 'a', 'k', new Set([1, 2])],
                [1, 'a'],
                new Set([1, 'a']),
            ],
            [
                [1, 2, 3, 'a', 'b', $setOneTwo],
                [1, 'a', 'k', $setOneTwo],
                [1, 'a', $setOneTwo],
                new Set([1, 'a', $setOneTwo]),
            ],
            [
                [1, 2, 3, 'a', 'b', new Set()],
                [1, 'a', 'k', $setOneTwo],
                [1, 'a'],
                new Set([1, 'a']),
            ],
            [
                [1, 2, 3, 'a', 'b', $setOneTwo],
                [1, 'a', 'k', -2, '2.4', 3.5, $setOneTwo],
                [1, 'a', $setOneTwo],
                new Set([1, 'a', $setOneTwo]),
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dataProviderForIntersectMultipleSets
     */
    public function testIntersectMultipleSets(array $A, array $B, array $C, array $A∩B∩C, Set $R)
    {
        // Given
        $setA               = new Set($A);
        $setB               = new Set($B);
        $setC               = new Set($C);
        $expected           = new Set($A∩B∩C);

        // When
        $intersection       = $setA->intersect($setB, $setC);
        $intersection_array = $intersection->asArray();

        // Then
        $this->assertEquals($R, $intersection);
        $this->assertEquals($expected, $intersection);
        $this->assertEquals(count($A∩B∩C), count($intersection));
        foreach ($A∩B∩C as $member) {
            $this->assertArrayHasKey("$member", $intersection_array);
            $this->assertArrayHasKey("$member", $setA->asArray());
            $this->assertArrayHasKey("$member", $setB->asArray());
            $this->assertArrayHasKey("$member", $setC->asArray());
            $this->assertContains($member, $A);
            $this->assertContains($member, $B);
            $this->assertContains($member, $C);
        }
        foreach ($A∩B∩C as $_ => $value) {
            if ($value instanceof Set) {
                $this->assertEquals($value, $intersection_array["$value"]);
            } else {
                $this->assertContains($value, $intersection_array);
            }
        }
    }

    public function dataProviderForIntersectMultipleSets(): array
    {
        $setOneTwo = new Set([1, 2]);

        return [
            [
                [1, 2, 3, 4],
                [2, 3, 4, 5],
                [3, 4, 5, 6],
                [3, 4],
                new Set([3, 4]),
            ],
            [
                [1, 2, 3, 4, $setOneTwo],
                [2, 3, 4, 5, $setOneTwo],
                [3, 4, 5, 6, $setOneTwo],
                [3, 4, $setOneTwo],
                new Set([3, 4, $setOneTwo]),
            ],
        ];
    }

    public function testIntersectWithArrays()
    {
        // Given
        $A        = new Set([1, 2, [1, 2, 3]]);
        $B        = new Set([2, 3, [2, 3, 4]]);
        $expected = new Set([2]);

        // When
        $A∩B = $A->intersect($B);

        // Then
        $this->assertEquals($expected, $A∩B);
        $this->assertEquals($expected->asArray(), $A∩B->asArray());
    }

    public function testIntersectWithArrays2()
    {
        // Given
        $A        = new Set([1, 2, [1, 2, 3]]);
        $B        = new Set([2, 3, [2, 3, 4], [1, 2, 3]]);
        $expected = new Set([2, [1, 2, 3]]);

        // When
        $A∩B = $A->intersect($B);

        // Then
        $this->assertEquals($expected, $A∩B);
        $this->assertEquals($expected->asArray(), $A∩B->asArray());
    }

    public function testIntersectWithObjects()
    {
        // Given
        $vector1 = new Vector([1, 2, 3]);
        $vector2 = new Vector([1, 2, 3]);

        $A        = new Set([1, 2, $vector1]);
        $B        = new Set([2, 3, $vector2]);
        $expected = new Set([2]);

        // When
        $A∩B = $A->intersect($B);

        // Then
        $this->assertEquals($expected, $A∩B);
        $this->assertEquals($expected->asArray(), $A∩B->asArray());
    }

    public function testIntersectWithObjects2()
    {
        // Given
        $vector1 = new Vector([1, 2, 3]);
        $vector2 = new Vector([1, 2, 3]);

        $A        = new Set([1, 2, $vector1]);
        $B        = new Set([2, 3, $vector2, $vector1]);
        $expected = new Set([2, $vector1]);

        // When
        $A∩B = $A->intersect($B);

        // Then
        $this->assertEquals($expected, $A∩B);
        $this->assertEquals($expected->asArray(), $A∩B->asArray());
    }

    /**
     * @test
     * @dataProvider dataProviderForDifference
     */
    public function testDifference(array $A, array $B, array $diff, Set $R)
    {
        // Given
        $setA             = new Set($A);
        $setB             = new Set($B);
        $expected         = new Set($diff);

        // When
        $difference       = $setA->difference($setB);
        $difference_array = $difference->asArray();

        // Then
        $this->assertEquals($R, $difference);
        $this->assertEquals($expected, $difference);
        $this->assertEquals(count($diff), count($difference));
        foreach ($diff as $member) {
            $this->assertArrayHasKey("$member", $difference_array);
            $this->assertArrayHasKey("$member", $setA->asArray());
            $this->assertArrayNotHasKey("$member", $setB->asArray());
            $this->assertContains($member, $A);
            $this->assertNotContains("$member", $B);
        }
        foreach ($diff as $_ => $value) {
            if ($value instanceof Set) {
                $this->assertEquals($value, $difference_array["$value"]);
            } else {
                $this->assertContains($value, $difference_array);
            }
        }
    }

    public function dataProviderForDifference(): array
    {
        $emptySet  = new Set();
        $setOneTwo = new Set([1, 2]);

        return [
            [
                [],
                [],
                [],
                new Set(),
            ],
            [
                [1],
                [1],
                [],
                new Set(),
            ],
            [
                [1, 2],
                [1],
                [2],
                new Set([2]),
            ],
            [
                [1],
                [1, 2],
                [],
                new Set(),
            ],
            [
                [1, 2, 3, 4],
                [2, 3, 4, 5],
                [1],
                new Set([1]),
            ],
            [
                [1, 2, 3, 'a', 'b'],
                [1, 'a', 'k'],
                [2, 3, 'b'],
                new Set([2, 3, 'b']),
            ],
            [
                [1, 2, 3, 'a', 'b', $setOneTwo],
                [1, 'a', 'k'],
                [2, 3, 'b',$setOneTwo],
                new Set([2, 3, 'b', $setOneTwo]),
            ],
            [
                [1, 2, 3, 'a', 'b'],
                [1, 'a', 'k', new Set([1, 2])],
                [2, 3, 'b'],
                new Set([2, 3, 'b']),
            ],
            [
                [1, 2, 3, 'a', 'b', $emptySet],
                [1, 'a', 'k', new Set([1, 2])],
                [2, 3, 'b', $emptySet],
                new Set([2, 3, 'b', $emptySet]),
            ],
            [
                [1, 2, 3, 'a', 'b', new Set([1, 2])],
                [1, 'a', 'k', -2, '2.4', 3.5, new Set([1, 2])],
                [2, 3, 'b'],
                new Set([2, 3, 'b']),
            ],
            [
                [1, 2,'a', 3, 4.5, new Set([1, 2])],
                [1, 2, 3, 'a', 4.5, new Set([1, 2])],
                [],
                new Set(),
            ],
            [
                [1, 2, 3, 'a', 4.5, new Set([1, 2])],
                [1, 2, 3, 'a', 4.5, new Set([1, 2])],
                [],
                new Set(),
            ],
            [
                [1, 2, 3, 'a', 4.5, new Set([1, 2]), -1, -2, 2.4, 3.5],
                [1, 2, 3, 'a', 4.5, new Set([1, 2]), -1, -2, 2.4, 3.5],
                [],
                new Set(),
            ],

        ];
    }

    /**
     * @test
     * @dataProvider dataProviderForDifferenceMultiSet
     */
    public function testDifferenceMultiSet(array $A, array $B, array $C, array $diff, Set $R)
    {
        // Given
        $setA     = new Set($A);
        $setB     = new Set($B);
        $setC     = new Set($C);
        $expected = new Set($diff);

        // When
        $difference       = $setA->difference($setB, $setC);
        $difference_array = $difference->asArray();

        // Then
        $this->assertEquals($R, $difference);
        $this->assertEquals($expected, $difference);
        $this->assertEquals(count($diff), count($difference));
        foreach ($diff as $member) {
            $this->assertArrayHasKey("$member", $difference_array);
            $this->assertArrayHasKey("$member", $setA->asArray());
            $this->assertArrayNotHasKey("$member", $setB->asArray());
            $this->assertArrayNotHasKey("$member", $setC->asArray());
            $this->assertContains($member, $A);
            $this->assertNotContains("$member", $B);
            $this->assertNotContains("$member", $C);
        }
        foreach ($diff as $_ => $value) {
            if ($value instanceof Set) {
                $this->assertEquals($value, $difference_array["$value"]);
            } else {
                $this->assertContains($value, $difference_array);
            }
        }
    }

    public function dataProviderForDifferenceMultiSet(): array
    {
        $setOneTwo = new Set([1, 2]);
        return [
            [
                ['1', '2', '3', '4'],
                ['2', '3', '4', '5'],
                ['3', '4', '5', '6'],
                ['1'],
                new Set([1]),
            ],
            [
                ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10'],
                ['2', '4', '6', '8', '10'],
                ['5', '10'],
                ['1', '3', '7', '9'],
                new Set([1, 3, 7, 9]),
            ],
            [
                ['1', '2', '3', '4', $setOneTwo],
                ['2', '3', '4', '5'],
                ['3', '4', '5', '6'],
                ['1', $setOneTwo],
                new Set([1, $setOneTwo]),
            ],
            [
                ['1', '2', '3', '4', new Set([1, 2])],
                ['2', '3', '4', '5'],
                ['3', '4', '5', '6', new Set([1, 2])],
                ['1'],
                new Set([1]),
            ],
        ];
    }

    public function testDifferenceWithArrays()
    {
        // Given
        $A        = new Set([1, 2, [1, 2, 3]]);
        $B        = new Set([2, 3, [2, 3, 4]]);
        $expected = new Set([1, [1, 2, 3]]);

        // When
        $A∖B = $A->difference($B);

        // Then
        $this->assertEquals($expected, $A∖B);
        $this->assertEquals($expected->asArray(), $A∖B->asArray());
    }

    public function testDifferenceWithArrays2()
    {
        // Given
        $A        = new Set([1, 2, [1, 2, 3]]);
        $B        = new Set([2, 3, [2, 3, 4], [1, 2, 3]]);
        $expected = new Set([1]);

        // When
        $A∖B = $A->difference($B);

        // Then
        $this->assertEquals($expected, $A∖B);
        $this->assertEquals($expected->asArray(), $A∖B->asArray());
    }

    public function testDifferenceWithObjects()
    {
        // Given
        $vector1 = new Vector([1, 2, 3]);
        $vector2 = new Vector([1, 2, 3]);

        $A        = new Set([1, 2, $vector1]);
        $B        = new Set([2, 3, $vector2]);
        $expected = new Set([1, $vector1]);

        // When
        $A∖B = $A->difference($B);

        // Then
        $this->assertEquals($expected, $A∖B);
        $this->assertEquals($expected->asArray(), $A∖B->asArray());
    }

    public function testDifferenceWithObjects2()
    {
        // Given
        $vector1 = new Vector([1, 2, 3]);
        $vector2 = new Vector([1, 2, 3]);

        $A        = new Set([1, 2, $vector1]);
        $B        = new Set([2, 3, $vector2, $vector1]);
        $expected = new Set([1]);

        // When
        $A∖B = $A->difference($B);

        // Then
        $this->assertEquals($expected, $A∖B);
        $this->assertEquals($expected->asArray(), $A∖B->asArray());
    }

    /**
     * @test
     * @dataProvider dataProviderForSymmetricDifference
     */
    public function testSymmetricDifference(array $A, array $B, array $diff, Set $R)
    {
        // Given
        $setA             = new Set($A);
        $setB             = new Set($B);
        $expected         = new Set($diff);

        // When
        $difference       = $setA->symmetricDifference($setB);
        $difference_array = $difference->asArray();

        // Then
        $this->assertEquals($R, $difference);
        $this->assertEquals($expected, $difference);
        $this->assertEquals(count($diff), count($difference));
        foreach ($diff as $member) {
            $this->assertArrayHasKey("$member", $difference_array);
        }
        foreach ($diff as $_ => $value) {
            if ($value instanceof Set) {
                $this->assertEquals($value, $difference_array["$value"]);
            } else {
                $this->assertArrayHasKey((string) $value, $difference_array);
            }
        }
    }

    public function dataProviderForSymmetricDifference(): array
    {
        return [
            [
                [1, 2, 3],
                [2, 3, 4],
                [1, 4],
                new Set([1, 4]),
            ],
            [
                [1, 2, 3, new Set()],
                [2, 3, 4],
                [1, 4, new Set()],
                new Set([1, 4, new Set()]),
            ],
            [
                [1, 2, 3],
                [2, 3, 4, new Set()],
                [1, 4, new Set()],
                new Set([1, 4, new Set()]),
            ],
            [
                [1, 2, 3, new Set()],
                [2, 3, 4, new Set()],
                [1, 4],
                new Set([1, 4]),
            ],
            [
                [1, 3, 5, 7, 9],
                [2, 4, 6, 8, 10],
                [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
                new Set([1, 2, 3, 4, 5, 6, 7, 8, 9, 10]),
            ],
        ];
    }

    public function testSymmetricDifferenceWithArrays()
    {
        // Given
        $A        = new Set([1, 2, [1, 2, 3]]);
        $B        = new Set([2, 3, [2, 3, 4]]);
        $expected = new Set([1, 3, [1, 2, 3], [2, 3, 4]]);

        // When
        $AΔB  = $A->symmetricDifference($B);

        // Then
        $this->assertEquals($expected, $AΔB);
        $this->assertEquals($expected->asArray(), $AΔB->asArray());
    }

    public function testSymmetricDifferenceWithArrays2()
    {
        // Given
        $A        = new Set([1, 2, [1, 2, 3]]);
        $B        = new Set([2, 3, [2, 3, 4], [1, 2, 3]]);
        $expected = new Set([1, 3, [2, 3, 4]]);

        // When
        $AΔB  = $A->symmetricDifference($B);

        // Then
        $this->assertEquals($expected, $AΔB);
        $this->assertEquals($expected->asArray(), $AΔB->asArray());
    }

    public function testSymmetricDifferenceWithObjects()
    {
        // Given
        $vector1 = new Vector([1, 2, 3]);
        $vector2 = new Vector([1, 2, 3]);

        $A        = new Set([1, 2, $vector1]);
        $B        = new Set([2, 3, $vector2]);
        $expected = new Set([1, 3, $vector1, $vector2]);

        // When
        $AΔB = $A->symmetricDifference($B);

        // Then
        $this->assertEquals($expected, $AΔB);
        $this->assertEquals($expected->asArray(), $AΔB->asArray());
    }

    public function testSymmetricDifferenceWithObjects2()
    {
        // Given
        $vector1 = new Vector([1, 2, 3]);
        $vector2 = new Vector([1, 2, 3]);

        $A        = new Set([1, 2, $vector1]);
        $B        = new Set([2, 3, $vector2, $vector1]);
        $expected = new Set([1, 3, $vector2]);

        // When
        $AΔB = $A->symmetricDifference($B);

        // Then
        $this->assertEquals($expected, $AΔB);
        $this->assertEquals($expected->asArray(), $AΔB->asArray());
    }

    /**
     * @test
     * @dataProvider dataProviderForSingleSet
     */
    public function testCopy(array $members)
    {
        // Given
        $set  = new Set($members);
        $copy = $set->copy();

        // When
        $set_array  = $set->asArray();
        $copy_array = $copy->asArray();

        // Then
        $this->assertEquals($set, $copy);
        $this->assertEquals($set_array, $copy_array);
        $this->assertEquals(count($set), count($copy));
    }

    /**
     * @test
     * @dataProvider dataProviderForSingleSet
     */
    public function testClear(array $members)
    {
        // Given
        $set  = new Set($members);

        // When
        $set->clear();

        // Then
        $this->assertTrue($set->isEmpty());
        $this->assertEmpty($set->asArray());
        $this->assertEquals($set, new Set());
    }

    /**
     * @test
     * @dataProvider dataProviderForCartesianProduct
     */
    public function testCartesianProduct(array $A, array $B, array $A×B, Set $R)
    {
        // Given
        $setA      = new Set($A);
        $setB      = new Set($B);

        // When
        $setA×B    = $setA->cartesianProduct($setB);
        $A×B_array = $setA×B->asArray();

        // Then
        $this->assertEquals($R, $setA×B);
        $this->assertEquals($A×B, $A×B_array);
        $this->assertEquals(count($setA×B), count($A×B));

        foreach ($setA×B as $key => $value) {
            $this->assertInstanceOf(Set::class, $value);
            $this->assertEquals(2, count($value));
        }
        foreach ($A×B_array as $key => $value) {
            $this->assertInstanceOf(Set::class, $value);
            $this->assertEquals(2, count($value));
        }
    }

    public function dataProviderForCartesianProduct(): array
    {
        return [
            [
                [1, 2],
                [3, 4],
                ['Set{1, 3}' => new Set([1, 3]), 'Set{1, 4}' => new Set([1, 4]), 'Set{2, 3}' => new Set([2, 3]), 'Set{2, 4}' => new Set([2, 4])],
                new Set([new Set([1, 3]), new Set([1, 4]), new Set([2, 3]), new Set([2, 4])]),
            ],
            [
                [1, 2],
                ['red', 'white'],
                ['Set{1, red}' => new Set([1, 'red']), 'Set{1, white}' => new Set([1, 'white']), 'Set{2, red}' => new Set([2, 'red']), 'Set{2, white}' => new Set([2, 'white'])],
                new Set([new Set([1, 'red']), new Set([1, 'white']), new Set([2, 'red']), new Set([2, 'white'])]),
            ],
            [
                [1, 2],
                [],
                [],
                new Set(),
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dataProviderForNaryCartesianProduct
     */
    public function testNaryCartesianProduct(array $A, array $B, array $C, array $A×B×C, Set $R)
    {
        // Given
        $setA        = new Set($A);
        $setB        = new Set($B);
        $setC        = new Set($C);

        // When
        $setA×B×C    = $setA->cartesianProduct($setB, $setC);
        $A×B×C_array = $setA×B×C->asArray();

        // Then
        $this->assertEquals($R, $setA×B×C);
        $this->assertEquals($A×B×C, $A×B×C_array);
        $this->assertEquals(count($setA×B×C), count($A×B×C));
        $this->assertEquals(count($setA×B×C), count($setA) * count($setB) * count($setC));

        foreach ($setA×B×C as $key => $value) {
            $this->assertInstanceOf(Set::class, $value);
            $this->assertEquals(3, count($value));
        }
        foreach ($A×B×C_array as $key => $value) {
            $this->assertInstanceOf(Set::class, $value);
            $this->assertEquals(3, count($value));
        }
    }

    public function dataProviderForNaryCartesianProduct(): array
    {
        return [
            [
                [1, 2],
                [3, 4],
                [5, 6],
                [
                    'Set{1, 3, 5}' => new Set([1, 3, 5]),
                    'Set{1, 3, 6}' => new Set([1, 3, 6]),
                    'Set{1, 4, 5}' => new Set([1, 4, 5]),
                    'Set{1, 4, 6}' => new Set([1, 4, 6]),
                    'Set{2, 3, 5}' => new Set([2, 3, 5]),
                    'Set{2, 3, 6}' => new Set([2, 3, 6]),
                    'Set{2, 4, 5}' => new Set([2, 4, 5]),
                    'Set{2, 4, 6}' => new Set([2, 4, 6]),
                ],
                new Set([
                    new Set([1, 3, 5]),
                    new Set([1, 3, 6]),
                    new Set([1, 4, 5]),
                    new Set([1, 4, 6]),
                    new Set([2, 3, 5]),
                    new Set([2, 3, 6]),
                    new Set([2, 4, 5]),
                    new Set([2, 4, 6]),
                ]),
            ],
            [
                [1, 2],
                ['red', 'white'],
                ['A', 'B'],
                [
                    'Set{1, red, A}' => new Set([1, 'red', 'A']),
                    'Set{1, red, B}' => new Set([1, 'red', 'B']),
                    'Set{1, white, A}' => new Set([1, 'white', 'A']),
                    'Set{1, white, B}' => new Set([1, 'white', 'B']),
                    'Set{2, red, A}' => new Set([2, 'red', 'A']),
                    'Set{2, red, B}' => new Set([2, 'red', 'B']),
                    'Set{2, white, A}' => new Set([2, 'white', 'A']),
                    'Set{2, white, B}' => new Set([2, 'white', 'B']),
                ],
                new Set([
                    new Set([1, 'red', 'A']),
                    new Set([1, 'red', 'B']),
                    new Set([1, 'white', 'A']),
                    new Set([1, 'white', 'B']),
                    new Set([2, 'red', 'A']),
                    new Set([2, 'red', 'B']),
                    new Set([2, 'white', 'A']),
                    new Set([2, 'white', 'B']),
                ]),
            ],
            [
                [1, 2],
                [3],
                [],
                [],
                new Set(),
            ],
        ];
    }


    /**
     * @test
     * @dataProvider dataProviderForPowerSet
     */
    public function testPowerSet(Set $A, Set $expected)
    {
        // When
        $P⟮S⟯ = $A->powerSet();

        // Then
        $this->assertEquals($expected, $P⟮S⟯);
        $this->assertEquals($expected->asArray(), $P⟮S⟯->asArray());
        $this->assertEquals(count($expected), count($P⟮S⟯));
    }

    public function dataProviderForPowerSet(): array
    {
        return [
            // P({}) = {Ø}
            [
                new Set(),
                new Set([
                    new Set(),
                ]),
            ],
            // P({1}) = {{Ø}, {1}}
            [
                new Set([1]),
                new Set([
                    new Set(),
                    new Set([1]),
                ]),
            ],
            // P({1, 2, 3}) = {Ø, {1}, {2}, {3}, {1,2}, {1,3}, {2,3}, {1,2,3}}
            [
                new Set([1, 2, 3]),
                new Set([
                    new Set(),
                    new Set([1]),
                    new Set([2]),
                    new Set([3]),
                    new Set([1, 2]),
                    new Set([1, 3]),
                    new Set([2, 3]),
                    new Set([1, 2, 3]),
                ]),
            ],
            // P({x, y, z}) = {Ø, {x}, {y}, {z}, {x,y}, {x,z}, {y,z}, {x,y,z}}
            [
                new Set(['x', 'y', 'z']),
                new Set([
                    new Set(),
                    new Set(['x']),
                    new Set(['y']),
                    new Set(['z']),
                    new Set(['x', 'y']),
                    new Set(['x', 'z']),
                    new Set(['y', 'z']),
                    new Set(['x', 'y', 'z']),
                ]),
            ],
            // P({1, [1, 2]}) = {Ø, {1}, {[1, 2]}, {1, [1, 2]}}
            [
                new Set([1, [1, 2]]),
                new Set([
                    new Set(),
                    new Set([1]),
                    new Set([[1, 2]]),
                    new Set([1, [1, 2]]),
                ]),
            ],
        ];
    }

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
}
