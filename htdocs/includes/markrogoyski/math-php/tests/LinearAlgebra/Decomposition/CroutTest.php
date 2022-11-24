<?php

namespace MathPHP\Tests\LinearAlgebra\Decomposition;

use MathPHP\LinearAlgebra\MatrixFactory;
use MathPHP\Exception;

class CroutTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test         croutDecomposition returns the expected array of L and U factorized matrices
     * @dataProvider dataProviderForCroutDecomposition
     * @param        array $A
     * @param        array $expected
     * @throws       \Exception
     */
    public function testCroutDecomposition(array $A, array $expected)
    {
        // Given
        $A = MatrixFactory::create($A);
        $L = MatrixFactory::create($expected['L']);
        $U = MatrixFactory::create($expected['U']);

        // When
        $lu = $A->croutDecomposition();

        // Then
        $this->assertEqualsWithDelta($L->getMatrix(), $lu->L->getMatrix(), 0.00001);
        $this->assertEqualsWithDelta($U->getMatrix(), $lu->U->getMatrix(), 0.00001);
    }

    /**
     * @return array
     */
    public function dataProviderForCroutDecomposition(): array
    {
        return [
            [
                [
                    [4, 0, 1],
                    [2, 1, 0],
                    [2, 2, 3],
                ],
                [
                    'L' => [
                        [4, 0, 0],
                        [2, 1, 0],
                        [2, 2, 7 / 2],
                    ],
                    'U' => [
                        [1, 0, 1 / 4],
                        [0, 1, -1 / 2],
                        [0, 0, 1],
                    ],
                ],
            ],
            [
                [
                    [5, 4, 1],
                    [10, 9, 4],
                    [10, 13, 15],
                ],
                [
                    'L' => [
                        [5, 0, 0],
                        [10, 1, 0],
                        [10, 5, 3],
                    ],
                    'U' => [
                        [1, 4 / 5, 1 / 5],
                        [0, 1, 2],
                        [0, 0, 1],
                    ],
                ],
            ],
            [
                [
                    [2, -4, 1],
                    [6, 2, -1],
                    [-2, 6, -2],
                ],
                [
                    'L' => [
                        [2, 0, 0],
                        [6, 14, 0],
                        [-2, 2, -0.428571],
                    ],
                    'U' => [
                        [1, -2, 0.5],
                        [0, 1, -0.285714],
                        [0, 0, 1],
                    ],
                ],
            ],
            [
                [
                    [1, 2, 3],
                    [2, 20, 26],
                    [3, 26, 70],
                ],
                [
                    'L' => [
                        [1, 0, 0],
                        [2, 16, 0],
                        [3, 20, 36],
                    ],
                    'U' => [
                        [1, 2, 3],
                        [0, 1, 5 / 4],
                        [0, 0, 1],
                    ],
                ],
            ],
            [
                [
                    [2, -1, 3],
                    [1, 3, -1],
                    [2, -2, 5],
                ],
                [
                    'L' => [
                        [2, 0, 0],
                        [1, 7 / 2, 0],
                        [2, -1, 9 / 7],
                    ],
                    'U' => [
                        [1, -1 / 2, 3 / 2],
                        [0, 1, -5 / 7],
                        [0, 0, 1],
                    ],
                ],
            ],
        ];
    }

    /**
     * @test    croutDecomposition throws a MatrixException if the det(L) is close to zero
     * @throws \Exception
     */
    public function testCroutDecompositionException()
    {
        // Given
        $A = MatrixFactory::create([
            [3, 4],
            [6, 8],
        ]);

        // Then
        $this->expectException(Exception\MatrixException::class);

        // When
        $lu = $A->croutDecomposition();
    }

    /**
     * @test   Crout Decomposition invalid property
     * @throws \Exception
     */
    public function testCountDecompositionInvalidProperty()
    {
        // Given
        $A = MatrixFactory::create([
            [4, 1, -1],
            [1, 2, 1],
            [-1, 1, 2],
        ]);
        $crout = $A->croutDecomposition();

        // Then
        $this->expectException(Exception\MathException::class);

        // When
        $doesNotExist = $crout->doesNotExist;
    }
}
