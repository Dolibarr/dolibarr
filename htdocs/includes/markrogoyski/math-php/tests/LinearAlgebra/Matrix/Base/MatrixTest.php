<?php

namespace MathPHP\Tests\LinearAlgebra\Matrix\Base;

use MathPHP\LinearAlgebra\MatrixFactory;
use MathPHP\LinearAlgebra\NumericMatrix;
use MathPHP\Exception;

class MatrixTest extends \PHPUnit\Framework\TestCase
{
    /** @var array */
    private $A;

    /** @var NumericMatrix */
    private $matrix;

    /**
     * @throws \Exception
     */
    public function setUp(): void
    {
        $this->A = [
            [1, 2, 3],
            [2, 3, 4],
            [4, 5, 6],
        ];
        $this->matrix = new NumericMatrix($this->A);
    }

    /**
     * @test   Implemented interfaces
     * @throws \Exception
     */
    public function testInterfaces()
    {
        $this->assertInstanceOf(\ArrayAccess::class, $this->matrix);
        $this->assertInstanceOf(\JsonSerializable::class, $this->matrix);
    }

    /**
     * @test   constructor throws Exception\MatrixException if the number of columns is not consistent
     * @throws \Exception
     */
    public function testConstructorExceptionNCountDiffers()
    {
        // Given
        $A = [
            [1, 2, 3],
            [2, 3, 4, 5],
            [3, 4, 5],
        ];

        // Then
        $this->expectException(Exception\MatrixException::class);

        // When
        $matrix = MatrixFactory::create($A);
    }

    /**
     * @test   constructor throws Exception\BadDataException if the number of columns is not consistent
     * @throws \Exception
     */
    public function testRawConstructorExceptionNCountDiffers()
    {
        // Given
        $A = [
            [1, 2, 3],
            [2, 3, 4, 5],
            [3, 4, 5],
        ];

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        $matrix = new NumericMatrix($A);
    }

    /**
     * @test   Matrix implements \ArrayAccess
     * @throws \Exception
     */
    public function testArrayAccessInterfaceOffsetGet()
    {
        // Given
        $A = [
            [1, 2, 3],
            [2, 3, 4],
            [4, 5, 6],
        ];
        $matrix = MatrixFactory::create($A);

        // Then
        $this->assertInstanceOf(\ArrayAccess::class, $matrix);

        $this->assertEquals([1, 2, 3], $matrix[0]);
        $this->assertEquals([2, 3, 4], $matrix[1]);
        $this->assertEquals([4, 5, 6], $matrix[2]);

        $this->assertEquals(1, $matrix[0][0]);
        $this->assertEquals(2, $matrix[0][1]);
        $this->assertEquals(3, $matrix[0][2]);

        $this->assertEquals(2, $matrix[1][0]);
        $this->assertEquals(3, $matrix[1][1]);
        $this->assertEquals(4, $matrix[1][2]);

        $this->assertEquals(4, $matrix[2][0]);
        $this->assertEquals(5, $matrix[2][1]);
        $this->assertEquals(6, $matrix[2][2]);
    }

    /**
     * @test Matrix implements \ArrayAccess
     */
    public function testArrayAccessInterfaceOffsetSet()
    {
        // Given
        $this->assertTrue($this->matrix->offsetExists(0));

        // Then
        $this->expectException(Exception\MatrixException::class);

        // When
        $this->matrix[0] = [4, 3, 5];
    }

    /**
     * @test   Matrix implements \ArrayAccess
     * @throws \Exception
     */
    public function testArrayAccessInterfaceOffExists()
    {
        $this->assertTrue($this->matrix->offsetExists(0));
    }

    /**
     * @test   Matrix implements \ArrayAccess
     * @throws \Exception
     */
    public function testArrayAccessOffsetUnsetException()
    {
        // Then
        $this->expectException(Exception\MatrixException::class);

        // When
        unset($this->matrix[0]);
    }

    /**
     * @test   __toString returns the expected string representation of the matrix
     * @throws \Exception
     */
    public function testToString()
    {
        // When
        $string = $this->matrix->__toString();

        // Then
        $this->assertTrue(\is_string($string));
        $this->assertEquals(
            "[1, 2, 3]\n[2, 3, 4]\n[4, 5, 6]",
            $string
        );
    }

    /**
     * @test         Matrix implements \JsonSerializable
     * @dataProvider dataProviderForJsonSerialize
     * @param        array $A
     * @param        string $json
     * @throws      \Exception
     */
    public function testJsonSerialize(array $A, string $json)
    {
        // Given
        $A = MatrixFactory::create($A);

        // Then
        $this->assertEquals($json, json_encode($A));
    }

    public function dataProviderForJsonSerialize(): array
    {
        return [
            [
                [
                    [1, 2, 3],
                    [4, 5, 6],
                    [7, 8, 9],
                ],
                '[[1,2,3],[4,5,6],[7,8,9]]',
            ],
            [
                [
                    [1],
                ],
                '[[1]]',
            ],
        ];
    }
}
