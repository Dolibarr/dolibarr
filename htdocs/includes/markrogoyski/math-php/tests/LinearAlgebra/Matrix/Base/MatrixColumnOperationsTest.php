<?php

namespace MathPHP\Tests\LinearAlgebra\Matrix\Base;

use MathPHP\LinearAlgebra\MatrixFactory;
use MathPHP\Exception;

class MatrixColumnOperationsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test         columnInterchange
     * @dataProvider dataProviderForColumnInterchange
     * @param        array $A
     * @param        int   $nᵢ
     * @param        int   $nⱼ
     * @param        array $expectedMatrix
     * @throws      \Exception
     */
    public function testColumnInterchange(array $A, int $nᵢ, int $nⱼ, array $expectedMatrix)
    {
        // Given
        $A = MatrixFactory::create($A);
        $expectedMatrix = MatrixFactory::create($expectedMatrix);

        // When
        $R = $A->columnInterchange($nᵢ, $nⱼ);

        // Then
        $this->assertEquals($expectedMatrix, $R);
    }

    /**
     * @return array
     */
    public function dataProviderForColumnInterchange(): array
    {
        return [
            [
                [
                    [1, 2, 3],
                    [2, 3, 4],
                    [3, 4, 5],
                ], 0, 1,
                [
                    [2, 1, 3],
                    [3, 2, 4],
                    [4, 3, 5],
                ]
            ],
            [
                [
                    [1, 2, 3],
                    [2, 3, 4],
                    [3, 4, 5],
                ], 1, 2,
                [
                    [1, 3, 2],
                    [2, 4, 3],
                    [3, 5, 4],
                ]
            ],
            [
                [
                    [5, 5],
                    [4, 4],
                    [2, 7],
                    [9, 0],
                ], 0, 1,
                [
                    [5, 5],
                    [4, 4],
                    [7, 2],
                    [0, 9],
                ]
            ],
        ];
    }

    /**
     * @test   columnInterchange column greater than n
     * @throws \Exception
     */
    public function testColumnInterchangeExceptionColumnGreaterThanN()
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
        $A->columnInterchange(4, 5);
    }

    /**
     * @test         columnExclude
     * @dataProvider dataProviderForColumnExclude
     * @param        array $A
     * @param        int   $nᵢ
     * @param        array $expectedMatrix
     * @throws       \Exception
     */
    public function testColumnExclude(array $A, int $nᵢ, array $expectedMatrix)
    {
        // Given
        $A = MatrixFactory::create($A);
        $expectedMatrix = MatrixFactory::create($expectedMatrix);

        // When
        $R = $A->columnExclude($nᵢ);

        // Then
        $this->assertEquals($expectedMatrix, $R);
    }

    /**
     * @return array
     */
    public function dataProviderForColumnExclude(): array
    {
        return [
            [
                [
                    [1, 2, 3],
                    [2, 3, 4],
                    [3, 4, 5],
                ], 0,
                [
                    [2, 3],
                    [3, 4],
                    [4, 5],
                ]
            ],
            [
                [
                    [1, 2, 3],
                    [2, 3, 4],
                    [3, 4, 5],
                ], 1,
                [
                    [1, 3],
                    [2, 4],
                    [3, 5],
                ]
            ],
            [
                [
                    [1, 2, 3],
                    [2, 3, 4],
                    [3, 4, 5],
                ], 2,
                [
                    [1, 2],
                    [2, 3],
                    [3, 4],
                ]
            ],
        ];
    }

    /**
     * @test   columnExclude column does not exist
     * @throws \Exception
     */
    public function testColumnExcludeExceptionColumnDoesNotExist()
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
        $A->columnExclude(-5);
    }
}
