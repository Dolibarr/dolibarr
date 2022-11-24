<?php

namespace MathPHP\Tests\LinearAlgebra\Matrix\Numeric;

use MathPHP\LinearAlgebra\MatrixFactory;
use MathPHP\Exception;

class MatrixRowOperationsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test         rowMultiply
     * @dataProvider dataProviderForRowMultiply
     * @param        array $A
     * @param        int   $mᵢ
     * @param        float $k
     * @param        array $expectedMatrix
     * @throws       \Exception
     */
    public function testRowMultiply(array $A, int $mᵢ, float $k, array $expectedMatrix)
    {
        // Given
        $A = MatrixFactory::create($A);
        $expectedMatrix = MatrixFactory::create($expectedMatrix);

        // When
        $R = $A->rowMultiply($mᵢ, $k);

        // Then
        $this->assertEquals($expectedMatrix, $R);
    }

    /**
     * @return array
     */
    public function dataProviderForRowMultiply(): array
    {
        return [
            [
                [
                    [1, 2, 3],
                    [2, 3, 4],
                    [3, 4, 5],
                ], 0, 5,
                [
                    [5, 10, 15],
                    [2, 3, 4],
                    [3, 4, 5],
                ]
            ],
            [
                [
                    [1, 2, 3],
                    [2, 3, 4],
                    [3, 4, 5],
                ], 1, 4,
                [
                    [1, 2, 3],
                    [8, 12, 16],
                    [3, 4, 5],
                ]
            ],
            [
                [
                    [1, 2, 3],
                    [2, 3, 4],
                    [3, 4, 5],
                ], 2, 8,
                [
                    [1, 2, 3],
                    [2, 3, 4],
                    [24, 32, 40],
                ]
            ],
            [
                [
                    [1, 2, 3],
                    [2, 3, 4],
                    [3, 4, 5],
                ], 0, 2.3,
                [
                    [2.3, 4.6, 6.9],
                    [2, 3, 4],
                    [3, 4, 5],
                ]
            ],
            [
                [
                    [1, 2, 3],
                    [2, 3, 4],
                    [3, 4, 5],
                ], 0, 0,
                [
                    [0, 0, 0],
                    [2, 3, 4],
                    [3, 4, 5],
                ]
            ],
        ];
    }

    /**
     * @test  rowMultiply on a row greater than m
     * @throws \Exception
     */
    public function testRowMultiplyExceptionRowGreaterThanM()
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
        $A->rowMultiply(4, 5);
    }

    /**
     * @test         rowDivide
     * @dataProvider dataProviderForRowDivide
     * @param        array $A
     * @param        int   $mᵢ
     * @param        float $k
     * @param        array $expectedMatrix
     * @throws       \Exception
     */
    public function testRowDivide(array $A, int $mᵢ, float $k, array $expectedMatrix)
    {
        // Given
        $A = MatrixFactory::create($A);
        $expectedMatrix = MatrixFactory::create($expectedMatrix);

        // When
        $R = $A->rowDivide($mᵢ, $k);

        // Then
        $this->assertEquals($expectedMatrix, $R);
    }

    /**
     * @return array
     */
    public function dataProviderForRowDivide(): array
    {
        return [
            [
                [
                    [2, 4, 8],
                    [2, 3, 4],
                    [3, 4, 5],
                ], 0, 2,
                [
                    [1, 2, 4],
                    [2, 3, 4],
                    [3, 4, 5],
                ]
            ],
            [
                [
                    [2, 4, 8],
                    [2, 3, 4],
                    [3, 4, 5],
                ], 0, 2.1,
                [
                    [0.952380952380952, 1.904761904761905, 3.80952380952381],
                    [2, 3, 4],
                    [3, 4, 5],
                ]
            ],
        ];
    }

    /**
     * @test   rowDivide row greater than M
     * @throws \Exception
     */
    public function testRowDivideExceptionRowGreaterThanM()
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
        $A->rowDivide(4, 5);
    }

    /**
     * @test   rowDivide K is zero
     * @throws \Exception
     */
    public function testRowDivideExceptionKIsZero()
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
        $A->rowDivide(2, 0);
    }

    /**
     * @test         rowAdd
     * @dataProvider dataProviderForRowAdd
     * @param        array $A
     * @param        int   $mᵢ
     * @param        int   $mⱼ
     * @param        float $k
     * @param        array $expectedMatrix
     * @throws      \Exception
     */
    public function testRowAdd(array $A, int $mᵢ, int $mⱼ, float $k, array $expectedMatrix)
    {
        // Given
        $A = MatrixFactory::create($A);
        $expectedMatrix = MatrixFactory::create($expectedMatrix);

        // When
        $R = $A->rowAdd($mᵢ, $mⱼ, $k);

        // Then
        $this->assertEquals($expectedMatrix, $R);
    }

    /**
     * @return array
     */
    public function dataProviderForRowAdd(): array
    {
        return [
            [
                [
                    [1, 2, 3],
                    [2, 3, 4],
                    [3, 4, 5],
                ], 0, 1, 2,
                [
                    [1, 2, 3],
                    [4, 7, 10],
                    [3, 4, 5],
                ]
            ],
            [
                [
                    [1, 2, 3],
                    [2, 3, 4],
                    [3, 4, 5],
                ], 1, 2, 3,
                [
                    [1, 2, 3],
                    [2, 3, 4],
                    [9, 13, 17],
                ]
            ],
            [
                [
                    [1, 2, 3],
                    [2, 3, 4],
                    [3, 4, 5],
                ], 0, 2, 4,
                [
                    [1, 2, 3],
                    [2, 3, 4],
                    [7, 12, 17],
                ]
            ],
            [
                [
                    [1, 2, 3],
                    [2, 3, 4],
                    [3, 4, 5],
                ], 0, 1, 2.1,
                [
                    [1, 2, 3],
                    [4.1, 7.2, 10.3],
                    [3, 4, 5],
                ]
            ],
        ];
    }

    /**
     * @test   rowAdd row greater than m
     * @throws \Exception
     */
    public function testRowAddExceptionRowGreaterThanM()
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
        $A->rowAdd(4, 5, 2);
    }

    /**
     * @test   rowAdd k is zero
     * @throws \Exception
     */
    public function testRowAddExceptionKIsZero()
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
        $A->rowAdd(1, 2, 0);
    }

    /**
     * @test         rowAddScalar
     * @dataProvider dataProviderForRowAddScalar
     * @param        array $A
     * @param        int   $mᵢ
     * @param        float $k
     * @param        array $expectedMatrix
     * @throws      \Exception
     */
    public function testRowAddScalar(array $A, int $mᵢ, float $k, array $expectedMatrix)
    {
        // Given
        $A = MatrixFactory::create($A);
        $expectedMatrix = MatrixFactory::create($expectedMatrix);

        // When
        $R = $A->rowAddScalar($mᵢ, $k);

        // Then
        $this->assertEquals($expectedMatrix, $R);
    }

    /**
     * @return array
     */
    public function dataProviderForRowAddScalar(): array
    {
        return [
            [
                [
                    [1, 2, 3],
                    [2, 3, 4],
                    [3, 4, 5],
                ], 0, 5,
                [
                    [6, 7, 8],
                    [2, 3, 4],
                    [3, 4, 5],
                ],
            ],
            [
                [
                    [1, 2, 3],
                    [2, 3, 4],
                    [3, 4, 5],
                ], 0, 5.3,
                [
                    [6.3, 7.3, 8.3],
                    [2, 3, 4],
                    [3, 4, 5],
                ],
            ],
        ];
    }

    /**
     * @test  rowAddScalar row greater than m
     * @throws \Exception
     */
    public function testRowAddScalarExceptionRowGreaterThanM()
    {
        // Given
        $A = MatrixFactory::create([
            [1, 2, 3],
            [2, 3, 4],
            [3, 4, 5],
        ]);

        // Then
        $this->expectException(Exception\MatrixException::class);

        // Then
        $A->rowAddScalar(4, 5);
    }

    /**
     * @test         rowSubtract
     * @dataProvider dataProviderForRowSubtract
     * @param        array $A
     * @param        int   $mᵢ
     * @param        int   $mⱼ
     * @param        float $k
     * @param        array $expectedMatrix
     * @throws       \Exception
     */
    public function testRowSubtract(array $A, int $mᵢ, int $mⱼ, float $k, array $expectedMatrix)
    {
        // Given
        $A = MatrixFactory::create($A);
        $expectedMatrix = MatrixFactory::create($expectedMatrix);

        // When
        $R = $A->rowSubtract($mᵢ, $mⱼ, $k);

        // Then
        $this->assertEquals($expectedMatrix, $R);
    }

    /**
     * @return array
     */
    public function dataProviderForRowSubtract(): array
    {
        return [
            [
                [
                    [1, 2, 3],
                    [2, 3, 4],
                    [3, 4, 5],
                ], 0, 1, 2,
                [
                    [1, 2, 3],
                    [0, -1, -2],
                    [3, 4, 5],
                ]
            ],
            [
                [
                    [1, 2, 3],
                    [2, 3, 4],
                    [3, 4, 5],
                ], 1, 2, 3,
                [
                    [1, 2, 3],
                    [2, 3, 4],
                    [-3, -5, -7],
                ]
            ],
            [
                [
                    [1, 2, 3],
                    [2, 3, 4],
                    [3, 4, 5],
                ], 0, 2, 4,
                [
                    [1, 2, 3],
                    [2, 3, 4],
                    [-1, -4, -7],
                ]
            ],
            [
                [
                    [1, 2, 3],
                    [2, 3, 4],
                    [3, 4, 5],
                ], 0, 1, 2.6,
                [
                    [1, 2, 3],
                    [-0.6, -2.2, -3.8],
                    [3, 4, 5],
                ]
            ],
        ];
    }

    /**
     * @test   rowSubtract row greater than m
     * @throws \Exception
     */
    public function testRowSubtractExceptionRowGreaterThanM()
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
        $A->rowSubtract(4, 5, 2);
    }

    /**
     * @test         rowSubtractScalar
     * @dataProvider dataProviderForRowSubtractScalar
     * @param        array $A
     * @param        int   $mᵢ
     * @param        float $k
     * @param        array $expectedMatrix
     * @throws      \Exception
     */
    public function testRowSubtractScalar(array $A, int $mᵢ, float $k, array $expectedMatrix)
    {
        // Given
        $A = MatrixFactory::create($A);
        $expectedMatrix = MatrixFactory::create($expectedMatrix);

        // When
        $R = $A->rowSubtractScalar($mᵢ, $k);

        // Then
        $this->assertEquals($expectedMatrix, $R);
    }

    /**
     * @return array
     */
    public function dataProviderForRowSubtractScalar(): array
    {
        return [
            [
                [
                    [6, 7, 8],
                    [2, 3, 4],
                    [3, 4, 5],
                ], 0, 5,
                [
                    [1, 2, 3],
                    [2, 3, 4],
                    [3, 4, 5],
                ],
            ],
            [
                [
                    [6, 7, 8],
                    [2, 3, 4],
                    [3, 4, 5],
                ], 0, 5.2,
                [
                    [0.8, 1.8, 2.8],
                    [2, 3, 4],
                    [3, 4, 5],
                ],
            ],
        ];
    }

    /**
     * @test   rowSubtractScalar row greater than m
     * @throws \Exception
     */
    public function testRowSubtractScalarExceptionRowGreaterThanM()
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
        $A->rowSubtractScalar(4, 5);
    }
}
