<?php

namespace MathPHP\Tests\LinearAlgebra\Matrix\Base;

use MathPHP\LinearAlgebra\MatrixFactory;
use MathPHP\LinearAlgebra\NumericMatrix;
use MathPHP\LinearAlgebra\Vector;
use MathPHP\Exception;

class MatrixGettersTest extends \PHPUnit\Framework\TestCase
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
        $this->matrix = MatrixFactory::create($this->A);
    }

    /**
     * @test    getMatrix returns the expected array representation of the matrix
     * @throws \Exception
     */
    public function testGetMatrix()
    {
        $this->assertEquals($this->A, $this->matrix->getMatrix());
    }

    /**
     * @test         getM returns the number of rows
     * @dataProvider dataProviderForGetM
     * @param        array $A
     * @param        int   $m
     * @throws       \Exception
     */
    public function testGetM(array $A, int $m)
    {
        // Given
        $matrix = MatrixFactory::create($A);

        // Then
        $this->assertEquals($m, $matrix->getM());
    }

    public function dataProviderForGetM(): array
    {
        return [
            [
                [[1]], 1
            ],
            [
                [
                    [1, 2],
                    [2, 3],
                ], 2
            ],
            [
                [
                    [1, 2],
                    [2, 3],
                    [3, 4],
                ], 3
            ],
            [
                [
                    [1, 2],
                    [2, 3],
                    [3, 4],
                    [4, 5],
                ], 4
            ],
            [
                [
                    [1, 2, 0],
                    [2, 3, 0],
                    [3, 4, 0],
                    [4, 5, 0],
                ], 4
            ],
        ];
    }

    /**
     * @test         getN returns the number of columns
     * @dataProvider dataProviderForGetN
     * @param        array $A
     * @param        int   $n
     * @throws       \Exception
     */
    public function testGetN(array $A, int $n)
    {
        // Given
        $matrix = MatrixFactory::create($A);

        // Then
        $this->assertEquals($n, $matrix->getN());
    }

    public function dataProviderForGetN(): array
    {
        return [
            [
                [[1]], 1
            ],
            [
                [
                    [1, 2],
                    [2, 3],
                ], 2
            ],
            [
                [
                    [1, 2],
                    [2, 3],
                    [3, 4],
                ], 2
            ],
            [
                [
                    [1, 2],
                    [2, 3],
                    [3, 4],
                    [4, 5],
                ], 2
            ],
            [
                [
                    [1, 2, 0],
                    [2, 3, 0],
                    [3, 4, 0],
                    [4, 5, 0],
                ], 3
            ],
        ];
    }

    /**
     * @test   getRow returns the expected row as an array
     * @throws \Exception
     */
    public function testGetRow()
    {
        // Given
        $A = [
            [1, 2, 3],
            [2, 3, 4],
            [4, 5, 6],
        ];
        $matrix = MatrixFactory::create($A);

        // Then
        $this->assertEquals([1, 2, 3], $matrix->getRow(0));
        $this->assertEquals([2, 3, 4], $matrix->getRow(1));
        $this->assertEquals([4, 5, 6], $matrix->getRow(2));
    }

    /**
     * @test   getRow throws Exception\MatrixException if the row does not exist
     * @throws \Exception
     */
    public function testGetRowException()
    {
        // Then
        $this->expectException(Exception\MatrixException::class);

        // When
        $this->matrix->getRow(8);
    }

    /**
     * @test   getColumn returns the expected column as an array
     * @throws \Exception
     */
    public function testGetColumn()
    {
        // Given
        $A = [
            [1, 2, 3],
            [2, 3, 4],
            [4, 5, 6],
        ];
        $matrix = MatrixFactory::create($A);

        // Then
        $this->assertEquals([1, 2, 4], $matrix->getColumn(0));
        $this->assertEquals([2, 3, 5], $matrix->getColumn(1));
        $this->assertEquals([3, 4, 6], $matrix->getColumn(2));
    }

    /**
     * @test   getColumn throws Exception\MatrixException if the column does not exist
     * @throws \Exception
     */
    public function testGetColumnException()
    {
        // Then
        $this->expectException(Exception\MatrixException::class);

        // When
        $this->matrix->getColumn(8);
    }

    /**
     * @test   get returns the expected element as a scalar
     * @throws \Exception
     */
    public function testGet()
    {
        // Given
        $A = [
            [1, 2, 3],
            [2, 3, 4],
            [4, 5, 6],
        ];
        $matrix = MatrixFactory::create($A);

        // Then
        $this->assertEquals(1, $matrix->get(0, 0));
        $this->assertEquals(2, $matrix->get(0, 1));
        $this->assertEquals(3, $matrix->get(0, 2));

        $this->assertEquals(2, $matrix->get(1, 0));
        $this->assertEquals(3, $matrix->get(1, 1));
        $this->assertEquals(4, $matrix->get(1, 2));

        $this->assertEquals(4, $matrix->get(2, 0));
        $this->assertEquals(5, $matrix->get(2, 1));
        $this->assertEquals(6, $matrix->get(2, 2));
    }

    /**
     * @test   get throws Exception\MatrixException if the row does not exist
     * @throws \Exception
     */
    public function testGetExceptionRow()
    {
        // Then
        $this->expectException(Exception\MatrixException::class);

        // When
        $this->matrix->get(8, 1);
    }

    /**
     * @test   get throws Exception\MatrixException if the column does not exist
     * @throws \Exception
     */
    public function testGetExceptionColumn()
    {
        // Then
        $this->expectException(Exception\MatrixException::class);

        // When
        $this->matrix->get(1, 8);
    }

    /**
     * @test   asVectors returns the matrix represented as an array of Vector objects
     * @throws \Exception
     */
    public function testAsVectors()
    {
        // Given
        $A = new NumericMatrix([
            [1, 2, 3],
            [4, 5, 6],
            [7, 8, 9],
        ]);

        $expected = [
            new Vector([1, 4, 7]),
            new Vector([2, 5, 8]),
            new Vector([3, 6, 9]),
        ];

        // Then
        $this->assertEquals($expected, $A->asVectors());
    }

    /**
     * @test   asRowVectors returns the matrix represented as an array of Vector objects
     * @throws \Exception
     */
    public function testAsRowVectors()
    {
        // Given
        $A = new NumericMatrix([
            [1, 2, 3],
            [4, 5, 6],
            [7, 8, 9],
        ]);

        $expected = [
            new Vector([1, 2, 3]),
            new Vector([4, 5, 6]),
            new Vector([7, 8, 9]),
        ];

        // Then
        $this->assertEquals($expected, $A->asRowVectors());
    }

    /**
     * @test         getDiagonalElements
     * @dataProvider dataProviderForGetDiagonalElements
     * @param        array $A
     * @param        array $R
     * @throws       \Exception
     */
    public function testGetDiagonalElements(array $A, array $R)
    {
        // Given
        $A = MatrixFactory::create($A);

        // Then
        $this->assertEquals($R, $A->getDiagonalElements());
    }

    public function dataProviderForGetDiagonalElements(): array
    {
        return [
            [
                [
                    [1, 2]
                ],
                [1],
            ],
            [
                [
                    [1],
                    [2],
                ],
                [1],
            ],
            [
                [[1]],
                [1],
            ],
            [
                [
                    [1, 2],
                    [2, 3],
                ],
                [1, 3],
            ],
            [
                [
                    [1, 2, 3],
                    [2, 3, 4],
                    [3, 4, 5],
                ],
                [1, 3, 5],
            ],
            [
                [
                    [1, 2, 3, 4],
                    [2, 3, 4, 5],
                    [3, 4, 5, 6],
                    [4, 5, 6, 7],
                ],
                [1, 3, 5, 7],
            ],
            [
                [
                    [1, 2, 3, 4],
                    [2, 3, 4, 5],
                    [3, 4, 5, 6],
                ],
                [1, 3, 5],
            ],
            [
                [
                    [1, 2, 3],
                    [2, 3, 4],
                    [3, 4, 5],
                    [4, 5, 6],
                ],
                [1, 3, 5],
            ],
        ];
    }

    /**
     * @test         getSuperdiagonalElements
     * @dataProvider dataProviderForGetSuperdiagonalElements
     * @param        array $A
     * @param        array $R
     * @throws       \Exception
     */
    public function testGetSuperdiagonalElements(array $A, array $R)
    {
        // Given
        $A = MatrixFactory::create($A);

        // Then
        $this->assertEquals($R, $A->getSuperdiagonalElements());
    }

    public function dataProviderForGetSuperdiagonalElements(): array
    {
        return [
            [
                [
                    [1, 2]
                ],
                [],
            ],
            [
                [
                    [1],
                    [2],
                ],
                [],
            ],
            [
                [[1]],
                [],
            ],
            [
                [
                    [1, 2],
                    [4, 3],
                ],
                [2],
            ],
            [
                [
                    [1, 2, 3],
                    [2, 3, 4],
                    [4, 5, 6],
                ],
                [2, 4],
            ],
            [
                [
                    [1, 2, 3, 4],
                    [2, 3, 4, 5],
                    [4, 5, 6, 7],
                    [3, 4, 5, 6],
                ],
                [2, 4, 7],
            ],
        ];
    }

    /**
     * @test         getSubdiagonalElements
     * @dataProvider dataProviderForGetSubdiagonalElements
     * @param        array $A
     * @param        array $R
     * @throws       \Exception
     */
    public function testGetSubdiagonalElements(array $A, array $R)
    {
        // Given
        $A = MatrixFactory::create($A);

        // Then
        $this->assertEquals($R, $A->getSubdiagonalElements());
    }

    public function dataProviderForGetSubdiagonalElements(): array
    {
        return [
            [
                [
                    [1, 2]
                ],
                [],
            ],
            [
                [
                    [1],
                    [2],
                ],
                [],
            ],
            [
                [[1]],
                [],
            ],
            [
                [
                    [1, 2],
                    [4, 3],
                ],
                [4],
            ],
            [
                [
                    [1, 2, 3],
                    [2, 3, 4],
                    [4, 5, 6],
                ],
                [2, 5],
            ],
            [
                [
                    [1, 2, 3, 4],
                    [2, 3, 4, 5],
                    [4, 5, 6, 7],
                    [3, 4, 5, 6],
                ],
                [2, 5, 5],
            ],
        ];
    }
}
