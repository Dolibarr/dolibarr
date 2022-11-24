<?php

namespace MathPHP\Tests\LinearAlgebra\Reduction;

use MathPHP\LinearAlgebra\MatrixFactory;
use MathPHP\LinearAlgebra\NumericMatrix;
use MathPHP\LinearAlgebra\Reduction;
use MathPHP\Tests;

class RowEchelonFormTest extends \PHPUnit\Framework\TestCase
{
    use Tests\LinearAlgebra\Fixture\MatrixDataProvider;

    /**
     * @test         isRef on ref matrix should return true
     * @dataProvider dataProviderForNonsingularMatrix
     * @param        $A
     * @throws       \Exception
     */
    public function testRefIsRef(array $A)
    {
        // Given
        $A = MatrixFactory::create($A);

        // When
        $ref = $A->ref();

        // Then
        $this->assertTrue($ref->isRef());
    }

    /**
     * @test   ref lazy load is the same as the computed and returned value.
     * @throws \Exception
     */
    public function testRefAlreadyComputed()
    {
        // Given
        $A = new NumericMatrix([
            [ 4,  1,  2,  -3],
            [-3,  3, -1,   4],
            [-1,  2,  5,   1],
            [ 5,  4,  3,  -1],
        ]);

        // When
        $ref1 = $A->ref(); // computes ref
        $ref2 = $A->ref(); // simply gets already-computed ref

        // Then
        $this->assertEquals($ref1, $ref2);
    }

    /**
     * @test         rowReductionToEchelonForm method of ref
     * @dataProvider dataProviderForRowReductionToEchelonForm
     * @param        array $A
     * @param        array $R
     * @throws       \Exception
     */
    public function testRowReductionToEchelonForm(array $A, array $R)
    {
        // Given
        $A   = MatrixFactory::create($A);
        $R   = MatrixFactory::create($R);

        // When
        [$ref, $swaps] = Reduction\RowEchelonForm::rowReductionToEchelonForm($A);
        $ref = MatrixFactory::create($ref);

        // Then
        $this->assertEqualsWithDelta($R->getMatrix(), $ref->getMatrix(), 0.000001);
        $this->assertTrue($ref->isRef());
    }

    /**
     * @return array
     */
    public function dataProviderForRowReductionToEchelonForm(): array
    {
        return [
            [
                [
                    [1, 2, 0],
                    [-1, 1, 1],
                    [1, 2, 3],
                ],
                [
                    [1, 2, 0],
                    [0, 1, 1 / 3],
                    [0, 0, 1],
                ],
            ],
            [
                [
                    [0, 2, 0],
                    [-1, 1, 1],
                    [1, 2, 3],
                ],
                [
                    [1, -1, -1],
                    [0, 1, 0],
                    [0, 0, 1],
                ],
            ],
            [
                [
                    [0, 2, 0],
                    [0, 1, 1],
                    [1, 2, 3],
                ],
                [
                    [1, 2, 3],
                    [0, 1, 1],
                    [0, 0, 1],
                ],
            ],
            [
                [
                    [1, 2, 0],
                    [0, 1, 1],
                    [0, 2, 3],
                ],
                [
                    [1, 2, 0],
                    [0, 1, 1],
                    [0, 0, 1],
                ],
            ],
            [
                [
                    [2, 5, 4],
                    [2, 4, 6],
                    [8, 7, 5],
                    [6, 4, 5],
                    [6, 2, 3],
                ],
                [
                    [1, 5 / 2, 2],
                    [0, 1, -2],
                    [0, 0, 1],
                    [0, 0, 0],
                    [0, 0, 0],
                ],
            ],
            [
                [
                    [1, 0, -2, 1, 0],
                    [0, -1, -3, 1, 3],
                    [-2, -1, 1, -1, 3],
                    [0, 3, 9, 0, -12],
                ],
                [
                    [1, 0, -2, 1, 0],
                    [0, 1, 3, -1, -3],
                    [0, 0, 0, 1, -1],
                    [0, 0, 0, 0, 0],
                ],
            ],
            [
                [
                    [5, 4, 8],
                    [7, 7, 5],
                    [6, 2, 4],
                ],
                [
                    [1, 4 / 5, 8 / 5],
                    [0, 1, -31 / 7],
                    [0, 0, 1],
                ],
            ],
            [
                [
                    [2, 0, -1, 0, 0],
                    [1, 0, 0, -1, 0],
                    [3, 0, 0, -2, -1],
                    [0, 1, 0, 0, -2],
                    [0, 1, -1, 0, 0]
                ],
                [
                    [1, 0, -1 / 2, 0, 0],
                    [0, 1, 0, 0, -2],
                    [0, 0, 1, -4 / 3, -2 / 3],
                    [0, 0, 0, 1, -1],
                    [0, 0, 0, 0, 0],
                ],
            ],
            [
                [
                    [2, -1, 4, 3, 2, 3, 4, 4],
                    [-1, 2, 3, 2, 1, 2, 3, 3],
                    [4, 3, 2, 1, 2, 3, 4, 4],
                    [2, 1, 2, 1, 2, 1, 2, 2],
                    [3, 2, 3, 2, 1, 2, 3, 3],
                    [3, 2, 3, 2, 1, 2, 1, 2],
                    [4, 3, 4, 3, 2, 1, 2, 2],
                    [4, 3, 4, 3, 2, 2, 2, 2],
                ],
                [
                    [1, -1 / 2, 2, 3 / 2, 1, 3 / 2, 2, 2],
                    [0, 1, 10 / 3, 7 / 3, 4 / 3, 7 / 3, 10 / 3, 10 / 3],
                    [0, 0, 1, 25 / 34, 13 / 34, 11 / 17, 31 / 34, 31 / 34],
                    [0, 0, 0, 1, -11 / 5, 18 / 5, 13 / 5, 13 / 5],
                    [0, 0, 0, 0, 1, 2, 2, 2],
                    [0, 0, 0, 0, 0, 1, 1, 1],
                    [0, 0, 0, 0, 0, 0, 1, 1 / 2],
                    [0, 0, 0, 0, 0, 0, 0, 1],
                ],
            ],
            [
                [
                    [0]
                ],
                [
                    [0],
                ],
            ],
            [
                [
                    [1]
                ],
                [
                    [1],
                ],
            ],
            [
                [
                    [5]
                ],
                [
                    [1],
                ],
            ],
            [
                [
                    [0, 0],
                    [0, 0]
                ],
                [
                    [0, 0],
                    [0, 0],
                ],
            ],
            [
                [
                    [0, 0],
                    [0, 1]
                ],
                [
                    [0, 1],
                    [0, 0],
                ],
            ],
            [
                [
                    [1, 0],
                    [0, 0]
                ],
                [
                    [1, 0],
                    [0, 0],
                ],
            ],
            [
                [
                    [0, 0],
                    [1, 0]
                ],
                [
                    [1, 0],
                    [0, 0],
                ],
            ],
            [
                [
                    [0, 0],
                    [1, 1]
                ],
                [
                    [1, 1],
                    [0, 0],
                ],
            ],
            [
                [
                    [0, 1],
                    [0, 1]
                ],
                [
                    [0, 1],
                    [0, 0],
                ],
            ],
            [
                [
                    [1, 0],
                    [1, 0]
                ],
                [
                    [1, 0],
                    [0, 0],
                ],
            ],
            [
                [
                    [1, 1],
                    [1, 1]
                ],
                [
                    [1, 1],
                    [0, 0],
                ],
            ],
            [
                [
                    [2, 6],
                    [1, 3]
                ],
                [
                    [1, 3],
                    [0, 0],
                ],
            ],
            [
                [
                    [3, 6],
                    [1, 2]
                ],
                [
                    [1, 2],
                    [0, 0],
                ],
            ],
            [
                [
                    [1, 2],
                    [1, 2]
                ],
                [
                    [1, 2],
                    [0, 0],
                ],
            ],
            [
                [
                    [1, 2, 3],
                    [2, 3, 4],
                    [3, 4, 5],
                ],
                [
                    [1, 2, 3],
                    [0, 1, 2],
                    [0, 0, 0],
                ],
            ],
            [
                [
                    [1, 2, 1],
                    [-2, -3, 1],
                    [3, 5, 0],
                ],
                [
                    [1, 2, 1],
                    [0, 1, 3],
                    [0, 0, 0],
                ],
            ],
            [
                [
                    [1, -1, 2],
                    [2, 1, 1],
                    [1, 1, 0],
                ],
                [
                    [1, -1, 2],
                    [0, 1, -1],
                    [0, 0, 0],
                ],
            ],
            [
                [
                    [1, 0, 1],
                    [0, 1, -1],
                    [0, 0, 0],
                ],
                [
                    [1, 0, 1],
                    [0, 1, -1],
                    [0, 0, 0],
                ],
            ],
            [
                [
                    [1, 2, 3],
                    [1, 3, 1],
                    [3, 4, 7],
                ],
                [
                    [1, 2, 3],
                    [0, 1, -2],
                    [0, 0, 1],
                ],
            ],
            [
                [
                    [1, 0, 0],
                    [-2, 0, 0],
                    [4, 6, 1],
                ],
                [
                    [1, 0, 0],
                    [0, 1, 1 / 6],
                    [0, 0, 0],
                ],
            ],
            [
                [
                    [1, 1, 4, 1, 2],
                    [0, 1, 2, 1, 1],
                    [0, 0, 0, 1, 2],
                    [1, -1, 0, 0, 2],
                    [2, 1, 6, 0, 1],
                ],
                [
                    [1, 1, 4, 1, 2],
                    [0, 1, 2, 1, 1],
                    [0, 0, 0, 1, 2],
                    [0, 0, 0, 0, 0],
                    [0, 0, 0, 0, 0],
                ],
            ],
            // This is an interesting case because of the minuscule values that are for all intents and purposes zero.
            // If the minuscule zero-like values are not handled properly, the zero value will be used as a pivot,
            // instead of being interchanged with a non-zero row.
            [
                [
                    [0, 1, 4, 2, 3, 3, 4, 4],
                    [1, 0, 3, 1, 2, 2, 3, 3],
                    [4, 3, 0, 2, 3, 3, 4, 4],
                    [3, 2, 1, 1, 2, 2, 3, 3],
                    [2, 1, 2, 0, 1, 1, 2, 2],
                    [3, 2, 3, 1, 2, 0, 1, 2],
                    [4, 3, 4, 2, 3, 1, 0, 2],
                    [4, 3, 4, 2, 3, 2, 2, 0],
                ],
                [
                    [1, 0, 3, 1, 2, 2, 3, 3],
                    [0, 1, 4, 2, 3, 3, 4, 4],
                    [0, 0, 1, 1 / 3, 7 / 12, 7 / 12, 5 / 6, 5 / 6],
                    [0, 0, 0, 1, 1, 1, 1, 1],
                    [0, 0, 0, 0, 1, 5, 6, 4],
                    [0, 0, 0, 0, 0, 1, 0, 0],
                    [0, 0, 0, 0, 0, 0, 1, -1],
                    [0, 0, 0, 0, 0, 0, 0, 0],
                ],
            ],
        ];
    }
}
