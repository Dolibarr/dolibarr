<?php

namespace MathPHP\Tests\LinearAlgebra\Vector;

use MathPHP\LinearAlgebra\Vector;
use MathPHP\LinearAlgebra\NumericMatrix;
use MathPHP\Exception;

class VectorTest extends \PHPUnit\Framework\TestCase
{
    /** @var array */
    private $A;

    /** @var Vector */
    private $V;

    public function setUp(): void
    {
        // Given
        $this->A = [1, 2, 3, 4, 5];
        $this->V = new Vector($this->A);
    }

    /**
     * @test Get vector values as array
     */
    public function testGetVector()
    {
        // When
        $values = $this->V->getVector();

        // Then
        $this->assertEquals($this->A, $values);
    }

    /**
     * @test         Get N
     * @dataProvider dataProviderForGetN
     * @param        array $A
     * @param        int $expected
     */
    public function testGetN(array $A, int $expected)
    {
        // Given
        $V = new Vector($A);

        // When
        $n = $V->getN();

        // Then
        $this->assertEquals($expected, $n);
    }

    public function dataProviderForGetN(): array
    {
        return [
            [[1], 1],
            [[1,2], 2],
            [[1,2,3], 3],
            [[1,2,3,4], 4],
        ];
    }

    /**
     * @test   Get a value
     * @throws Exception\VectorException
     */
    public function testGet()
    {
        // Then
        $this->assertEquals(1, $this->V->get(0));
        $this->assertEquals(2, $this->V->get(1));
        $this->assertEquals(3, $this->V->get(2));
        $this->assertEquals(4, $this->V->get(3));
        $this->assertEquals(5, $this->V->get(4));
    }

    /**
     * @test   Get exception
     * @throws Exception\VectorException
     */
    public function testGetException()
    {
        // Then
        $this->expectException(Exception\VectorException::class);

        // When
        $this->V->get(100);
    }

    /**
     * @test toString
     */
    public function testToString()
    {
        // Given
        $A = new Vector([1, 2, 3]);

        // When
        $string = $A->__toString();

        // Then
        $this->assertTrue(\is_string($string));
        $this->assertEquals('[1, 2, 3]', $string);
    }

    /**
     * @test         As column matrix
     * @dataProvider dataProviderForAsColumnMatrix
     * @param        array $A
     * @param        array $R
     * @throws       Exception\MathException
     */
    public function testAsColumnMatrix(array $A, array $R)
    {
        // Given
        $A = new Vector($A);
        $R = new NumericMatrix($R);

        // When
        $M = $A->asColumnMatrix();

        // Then
        $this->assertEquals($R->getMatrix(), $M->getMatrix());
    }

    public function dataProviderForAsColumnMatrix(): array
    {
        return [
            [
                [1],
                [
                    [1],
                ],
            ],
            [
                [1, 2],
                [
                    [1],
                    [2],
                ],
            ],
            [
                [1, 2, 3],
                [
                    [1],
                    [2],
                    [3],
                ],
            ],
            [
                [1, 2, 3, 4],
                [
                    [1],
                    [2],
                    [3],
                    [4],
                ],
            ],
        ];
    }

    /**
     * @test         As row matrix
     * @dataProvider dataProviderForAsRowMatrix
     * @param        array $A
     * @param        array $R
     * @throws       Exception\MathException
     */
    public function testAsRowMatrix(array $A, array $R)
    {
        // Given
        $A = new Vector($A);
        $R = new NumericMatrix($R);

        // When
        $M = $A->asRowMatrix();

        // Then
        $this->assertEquals($R->getMatrix(), $M->getMatrix());
    }

    public function dataProviderForAsRowMatrix(): array
    {
        return [
            [
                [1],
                [
                    [1],
                ],
            ],
            [
                [1, 2],
                [
                    [1, 2],
                ],
            ],
            [
                [1, 2, 3],
                [
                    [1, 2, 3],
                ],
            ],
            [
                [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
                [
                    [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
                ],
            ],
        ];
    }

    /**
     * @test   Empty Vector is not allowed
     * @throws \Exception
     */
    public function testEmptyVectorException()
    {
        // Given
        $values = [];

        // Then
        $this->expectException(Exception\MathException::class);

        // When
        $V = new Vector($values);
    }
}
