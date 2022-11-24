<?php

namespace MathPHP\Tests\LinearAlgebra\Matrix\Base;

use MathPHP\LinearAlgebra\MatrixFactory;
use MathPHP\Exception;

class MatrixRowOperationsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test         rowInterchange
     * @dataProvider dataProviderForRowInterchange
     * @param        array $A
     * @param        int   $mᵢ
     * @param        int   $mⱼ
     * @param        array $expectedMatrix
     * @throws       \Exception
     */
    public function testRowInterchange(array $A, int $mᵢ, int $mⱼ, array $expectedMatrix)
    {
        // Given
        $A = MatrixFactory::create($A);
        $expectedMatrix = MatrixFactory::create($expectedMatrix);

        // When
        $R = $A->rowInterchange($mᵢ, $mⱼ);

        // Then
        $this->assertEquals($expectedMatrix, $R);
    }

    /**
     * @return array
     */
    public function dataProviderForRowInterchange(): array
    {
        return [
            [
                [
                    [1, 2, 3],
                    [2, 3, 4],
                    [3, 4, 5],
                ], 0, 1,
                [
                    [2, 3, 4],
                    [1, 2, 3],
                    [3, 4, 5],
                ]
            ],
            [
                [
                    [5, 5],
                    [4, 4],
                    [2, 7],
                    [9, 0],
                ], 2, 3,
                [
                    [5, 5],
                    [4, 4],
                    [9, 0],
                    [2, 7],
                ]
            ],
            [
                [
                    [5, 5],
                    [4, 4],
                    [2, 7],
                    [9, 0],
                ], 1, 2,
                [
                    [5, 5],
                    [2, 7],
                    [4, 4],
                    [9, 0],
                ]
            ]
        ];
    }

    /**
     * @test   rowInterchange on a row greater than m
     * @throws \Exception
     */
    public function testRowInterchangeExceptionRowGreaterThanM()
    {
        // Given
        $A = MatrixFactory::create([
            [1, 2, 3],
            [2, 3, 4],
            [3, 4, 5],
        ]);

        // Then
        $this->expectException(Exception\MatrixException::class);

        // When
        $A->rowInterchange(4, 5);
    }

    /**
     * @test         rowExclude
     * @dataProvider dataProviderForRowExclude
     * @param        array $A
     * @param        int   $mᵢ
     * @param        array $expectedMatrix
     * @throws       \Exception
     */
    public function testRowExclude(array $A, int $mᵢ, array $expectedMatrix)
    {
        // Given
        $A = MatrixFactory::create($A);
        $expectedMatrix = MatrixFactory::create($expectedMatrix);

        // When
        $R = $A->rowExclude($mᵢ);

        // Then
        $this->assertEquals($expectedMatrix, $R);
    }

    /**
     * @return array
     */
    public function dataProviderForRowExclude(): array
    {
        return [
            [
                [
                    [1, 2, 3],
                    [2, 3, 4],
                    [3, 4, 5],
                ], 0,
                [
                    [2, 3, 4],
                    [3, 4, 5],
                ]
            ],
            [
                [
                    [1, 2, 3],
                    [2, 3, 4],
                    [3, 4, 5],
                ], 1,
                [
                    [1, 2, 3],
                    [3, 4, 5],
                ]
            ],
            [
                [
                    [1, 2, 3],
                    [2, 3, 4],
                    [3, 4, 5],
                ], 2,
                [
                    [1, 2, 3],
                    [2, 3, 4],
                ]
            ],
        ];
    }

    /**
     * @test  rowExclude on a row that does not exist
     * @throws \Exception
     */
    public function testRowExcludeExceptionRowDoesNotExist()
    {
        // Given
        $A = MatrixFactory::create([
            [1, 2, 3],
            [2, 3, 4],
            [4, 5, 6],
        ]);

        // Then
        $this->expectException(Exception\MatrixException::class);

        // When
        $A->rowExclude(-5);
    }
}
