<?php

namespace MathPHP\Tests\LinearAlgebra\Matrix\Numeric;

use MathPHP\LinearAlgebra\MatrixFactory;
use MathPHP\Exception;

class MatrixColumnOperationsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test         columnMultiply
     * @dataProvider dataProviderForColumnMultiply
     * @param        array $A
     * @param        int   $nᵢ
     * @param        float $k
     * @param        array $expectedMatrix
     * @throws       \Exception
     */
    public function testColumnMultiply(array $A, int $nᵢ, float $k, array $expectedMatrix)
    {
        // Given
        $A = MatrixFactory::create($A);
        $expectedMatrix = MatrixFactory::create($expectedMatrix);

        // When
        $R = $A->columnMultiply($nᵢ, $k);

        // Then
        $this->assertEquals($expectedMatrix, $R);
    }

    /**
     * @return array
     */
    public function dataProviderForColumnMultiply(): array
    {
        return [
            [
                [
                    [1, 2, 3],
                    [2, 3, 4],
                    [3, 4, 5],
                ], 0, 5,
                [
                    [5, 2, 3],
                    [10, 3, 4],
                    [15, 4, 5],
                ]
            ],
            [
                [
                    [1, 2, 3],
                    [2, 3, 4],
                    [3, 4, 5],
                ], 1, 4,
                [
                    [1, 8, 3],
                    [2, 12, 4],
                    [3, 16, 5],
                ]
            ],
            [
                [
                    [1, 2, 3],
                    [2, 3, 4],
                    [3, 4, 5],
                ], 2, 8,
                [
                    [1, 2, 24],
                    [2, 3, 32],
                    [3, 4, 40],
                ]
            ],
            [
                [
                    [1, 2, 3],
                    [2, 3, 4],
                    [3, 4, 5],
                ], 0, 5.1,
                [
                    [5.1, 2, 3],
                    [10.2, 3, 4],
                    [15.3, 4, 5],
                ]
            ],
            [
                [
                    [1, 2, 3],
                    [2, 3, 4],
                    [3, 4, 5],
                ], 0, 0,
                [
                    [0, 2, 3],
                    [0, 3, 4],
                    [0, 4, 5],
                ]
            ],
        ];
    }

    /**
     * @test  columnMultiply column greater than n
     * @throws \Exception
     */
    public function testColumnMultiplyExceptionColumnGreaterThanN()
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
        $A->columnMultiply(4, 5);
    }

    /**
     * @test         columnAdd
     * @dataProvider dataProviderForColumnAdd
     * @param        array $A
     * @param        int   $nᵢ
     * @param        int   $nⱼ
     * @param        float $k
     * @param        array $expectedMatrix
     * @throws      \Exception
     */
    public function testColumnAdd(array $A, int $nᵢ, int $nⱼ, float $k, array $expectedMatrix)
    {
        // Given
        $A = MatrixFactory::create($A);
        $expectedMatrix = MatrixFactory::create($expectedMatrix);

        // When
        $R = $A->columnAdd($nᵢ, $nⱼ, $k);

        // Then
        $this->assertEquals($expectedMatrix, $R);
    }

    /**
     * @return array
     */
    public function dataProviderForColumnAdd(): array
    {
        return [
            [
                [
                    [1, 2, 3],
                    [2, 3, 4],
                    [3, 4, 5],
                ], 0, 1, 2,
                [
                    [1, 4, 3],
                    [2, 7, 4],
                    [3, 10, 5],
                ]
            ],
            [
                [
                    [1, 2, 3],
                    [2, 3, 4],
                    [3, 4, 5],
                ], 1, 2, 3,
                [
                    [1, 2, 9],
                    [2, 3, 13],
                    [3, 4, 17],
                ]
            ],
            [
                [
                    [1, 2, 3],
                    [2, 3, 4],
                    [3, 4, 5],
                ], 0, 2, 4,
                [
                    [1, 2, 7],
                    [2, 3, 12],
                    [3, 4, 17],
                ]
            ],
            [
                [
                    [1, 2, 3],
                    [2, 3, 4],
                    [3, 4, 5],
                ], 0, 1, 2.2,
                [
                    [1, 4.2, 3],
                    [2, 7.4, 4],
                    [3, 10.6, 5],
                ]
            ],
        ];
    }

    /**
     * @test   columnAdd row greater than n
     * @throws \Exception
     */
    public function testColumnAddExceptionRowGreaterThanN()
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
        $A->columnAdd(4, 5, 2);
    }

    /**
     * @test   columnAdd k is zero
     * @throws \Exception
     */
    public function testColumnAddExceptionKIsZero()
    {
        // Given
        $A = MatrixFactory::create([
            [1, 2, 3],
            [2, 3, 4],
            [3, 4, 5],
        ]);

        // Then
        $this->expectException(Exception\BadParameterException::class);

        // When
        $A->columnAdd(1, 2, 0);
    }
}
