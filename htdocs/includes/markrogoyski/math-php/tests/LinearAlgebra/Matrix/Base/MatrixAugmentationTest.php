<?php

namespace MathPHP\Tests\LinearAlgebra\Matrix;

use MathPHP\Expression\Polynomial;
use MathPHP\LinearAlgebra\MatrixFactory;
use MathPHP\LinearAlgebra\NumericMatrix;
use MathPHP\Exception;

class MatrixAugmentationTest extends \PHPUnit\Framework\TestCase
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
     * @test         augment
     * @dataProvider dataProviderForAugment
     * @param        array $A
     * @param        array $B
     * @param        array $⟮A∣B⟯
     * @throws       \Exception
     */
    public function testAugment(array $A, array $B, array $⟮A∣B⟯)
    {
        // Given
        $A    = MatrixFactory::create($A);
        $B    = MatrixFactory::create($B);
        $⟮A∣B⟯ = MatrixFactory::create($⟮A∣B⟯);

        // When
        $augmented = $A->augment($B);

        // Then
        $this->assertEquals($⟮A∣B⟯, $augmented);
    }

    /**
     * @return array
     */
    public function dataProviderForAugment(): array
    {
        return [
            [
                [
                    [1, 2, 3],
                    [2, 3, 4],
                    [3, 4, 5],
                ],
                [
                    [4],
                    [5],
                    [6],
                ],
                [
                    [1, 2, 3, 4],
                    [2, 3, 4, 5],
                    [3, 4, 5, 6],
                ]
            ],
            [
                [
                    [1, 2, 3],
                    [2, 3, 4],
                    [3, 4, 5],
                ],
                [
                    [4, 7, 8],
                    [5, 7, 8],
                    [6, 7, 8],
                ],
                [
                    [1, 2, 3, 4, 7, 8],
                    [2, 3, 4, 5, 7, 8],
                    [3, 4, 5, 6, 7, 8],
                ]
            ],
            [
                [
                    [1, 2, 3],

                ],
                [
                    [4],

                ],
                [
                    [1, 2, 3, 4],
                ]
            ],
            [
                [
                    [1],

                ],
                [
                    [4],
                ],
                [
                    [1, 4],
                ]
            ],
            [
                [
                    [1, 2, 3],
                    [2, 3, 4],
                    [3, 4, 5],
                ],
                [
                    [4, 7, 8, 9],
                    [5, 7, 8, 9],
                    [6, 7, 8, 9],
                ],
                [
                    [1, 2, 3, 4, 7, 8, 9],
                    [2, 3, 4, 5, 7, 8, 9],
                    [3, 4, 5, 6, 7, 8, 9],
                ]
            ],
        ];
    }

    /**
     * @test     augment matrix with matrix that does not match dimensions
     * @throws   \Exception
     */
    public function testAugmentExceptionRowsDoNotMatch()
    {
        // Given
        $A = MatrixFactory::create([
            [1, 2, 3],
            [2, 3, 4],
            [3, 4, 5],
        ]);
        $B = MatrixFactory::create([
            [4, 5],
            [5, 6],
        ]);

        // Then
        $this->expectException(Exception\MatrixException::class);

        // When
        $A->augment($B);
    }

    /**
     * @test         Augment with identity
     * @dataProvider dataProviderForAugmentIdentity
     * @throws       \Exception
     */
    public function testAugmentIdentity(array $C, array $⟮C∣I⟯)
    {
        // Given
        $C    = MatrixFactory::create($C);
        $⟮C∣I⟯ = MatrixFactory::create($⟮C∣I⟯);

        // Then
        $this->assertEquals($⟮C∣I⟯, $C->augmentIdentity());
    }

    public function dataProviderForAugmentIdentity(): array
    {
        return [
            [
                [
                    [1, 2, 3],
                    [2, 3, 4],
                    [3, 4, 5],
                ],
                [
                    [1, 2, 3, 1, 0, 0],
                    [2, 3, 4, 0, 1, 0],
                    [3, 4, 5, 0, 0, 1],
                ]
            ],
            [
                [
                    [1, 2],
                    [2, 3],
                ],
                [
                    [1, 2, 1, 0],
                    [2, 3, 0, 1],
                ]
            ],
            [
                [
                    [1]
                ],
                [
                    [1, 1],
                ]
            ],
        ];
    }

    /**
     * @test   Augment with identity exception when not square
     * @throws \Exception
     */
    public function testAugmentIdentityExceptionNotSquare()
    {
        // Given
        $A = MatrixFactory::create([
            [1, 2],
            [2, 3],
            [3, 4],
        ]);

        // Then
        $this->expectException(Exception\MatrixException::class);

        // When
        $A->augmentIdentity();
    }

    /**
     * @test         augmentBelow
     * @dataProvider dataProviderForAugmentBelow
     * @param        array $A
     * @param        array $B
     * @param        array $⟮A∣B⟯
     * @throws       \Exception
     */
    public function testAugmentBelow(array $A, array $B, array $⟮A∣B⟯)
    {
        // Given
        $A    = MatrixFactory::create($A);
        $B    = MatrixFactory::create($B);
        $⟮A∣B⟯ = MatrixFactory::create($⟮A∣B⟯);

        // When
        $augmented = $A->augmentBelow($B);

        // Then
        $this->assertEquals($⟮A∣B⟯, $augmented);
    }

    /**
     * @return array
     */
    public function dataProviderForAugmentBelow(): array
    {
        return [
            [
                [
                    [1],
                ],
                [
                    [2],
                ],
                [
                    [1],
                    [2],
                ],
            ],
            [
                [
                    [1],
                    [2],
                ],
                [
                    [3],
                ],
                [
                    [1],
                    [2],
                    [3],
                ],
            ],
            [
                [
                    [1, 2],
                    [2, 3],
                ],
                [
                    [3, 4],
                ],
                [
                    [1, 2],
                    [2, 3],
                    [3, 4],
                ],
            ],
            [
                [
                    [1, 2, 3],
                    [2, 3, 4],
                ],
                [
                    [3, 4, 5],
                ],
                [
                    [1, 2, 3],
                    [2, 3, 4],
                    [3, 4, 5],
                ],
            ],
            [
                [
                    [1, 2, 3],
                    [2, 3, 4],
                ],
                [
                    [3, 4, 5],
                    [4, 5, 6]
                ],
                [
                    [1, 2, 3],
                    [2, 3, 4],
                    [3, 4, 5],
                    [4, 5, 6],
                ],
            ],
        ];
    }

    /**
     * @test   It is an error to augment a matrix from below if the column count does not match
     * @throws \Exception
     */
    public function testAugmentBelowExceptionColumnsDoNotMatch()
    {
        // Given
        $A = MatrixFactory::create([
            [1, 2, 3],
            [2, 3, 4],
            [3, 4, 5],
        ]);
        $B = MatrixFactory::create([
            [4, 5],
            [5, 6],
        ]);

        // Then
        $this->expectException(Exception\MatrixException::class);

        // When
        $A->augmentBelow($B);
    }

    /**
     * @test         augmentAbove
     * @dataProvider dataProviderForAugmentAbove
     * @param        array $A
     * @param        array $B
     * @param        array $⟮A∣B⟯
     * @throws       \Exception
     */
    public function testAugmentAbove(array $A, array $B, array $⟮A∣B⟯)
    {
        // Given
        $A    = MatrixFactory::create($A);
        $B    = MatrixFactory::create($B);
        $⟮A∣B⟯ = MatrixFactory::create($⟮A∣B⟯);

        // When
        $augmented = $A->augmentAbove($B);

        // Then
        $this->assertEquals($⟮A∣B⟯, $augmented);
    }

    /**
     * @return array
     */
    public function dataProviderForAugmentAbove(): array
    {
        return [
            [
                [
                    [1],
                ],
                [
                    [2],
                ],
                [
                    [2],
                    [1],
                ],
            ],
            [
                [
                    [1],
                    [2],
                ],
                [
                    [3],
                ],
                [
                    [3],
                    [1],
                    [2],
                ],
            ],
            [
                [
                    [1, 2],
                    [2, 3],
                ],
                [
                    [3, 4],
                ],
                [
                    [3, 4],
                    [1, 2],
                    [2, 3],
                ],
            ],
            [
                [
                    [1, 2, 3],
                    [2, 3, 4],
                ],
                [
                    [3, 4, 5],
                ],
                [
                    [3, 4, 5],
                    [1, 2, 3],
                    [2, 3, 4],
                ],
            ],
            [
                [
                    [1, 2, 3],
                    [2, 3, 4],
                ],
                [
                    [3, 4, 5],
                    [4, 5, 6]
                ],
                [
                    [3, 4, 5],
                    [4, 5, 6],
                    [1, 2, 3],
                    [2, 3, 4],
                ],
            ],
        ];
    }

    /**
     * @test   It is an error to augment a matrix from above if the column count does not match
     * @throws \Exception
     */
    public function testAugmentAboveExceptionColumnsDoNotMatch()
    {
        // Given
        $A = MatrixFactory::create([
            [1, 2, 3],
            [2, 3, 4],
            [3, 4, 5],
        ]);
        $B = MatrixFactory::create([
            [4, 5],
            [5, 6],
        ]);

        // Then
        $this->expectException(Exception\MatrixException::class);

        // When
        $A->augmentAbove($B);
    }

    /**
     * @test         augmentLeft
     * @dataProvider dataProviderForAugmentLeft
     * @param        array $A
     * @param        array $B
     * @param        array $⟮B∣A⟯
     * @throws       \Exception
     */
    public function testAugmentLeft(array $A, array $B, array $⟮B∣A⟯)
    {
        // Given
        $A    = MatrixFactory::create($A);
        $B    = MatrixFactory::create($B);
        $⟮B∣A⟯ = MatrixFactory::create($⟮B∣A⟯);

        // When
        $augmented = $A->augmentLeft($B);

        // Then
        $this->assertEquals($⟮B∣A⟯, $augmented);
    }

    /**
     * @return array
     */
    public function dataProviderForAugmentLeft(): array
    {
        return [
            [
                [
                    [1, 2, 3],
                    [2, 3, 4],
                    [3, 4, 5],
                ],
                [
                    [4],
                    [5],
                    [6],
                ],
                [
                    [4, 1, 2, 3],
                    [5, 2, 3, 4],
                    [6, 3, 4, 5],
                ]
            ],
            [
                [
                    [1, 2, 3],
                    [2, 3, 4],
                    [3, 4, 5],
                ],
                [
                    [4, 7, 8],
                    [5, 7, 8],
                    [6, 7, 8],
                ],
                [
                    [4, 7, 8, 1, 2, 3],
                    [5, 7, 8, 2, 3, 4],
                    [6, 7, 8, 3, 4, 5],
                ]
            ],
            [
                [
                    [1, 2, 3],

                ],
                [
                    [4],

                ],
                [
                    [4, 1, 2, 3],
                ]
            ],
            [
                [
                    [1],

                ],
                [
                    [4],
                ],
                [
                    [4, 1],
                ]
            ],
            [
                [
                    [1, 2, 3],
                    [2, 3, 4],
                    [3, 4, 5],
                ],
                [
                    [4, 7, 8, 9],
                    [5, 7, 8, 9],
                    [6, 7, 8, 9],
                ],
                [
                    [4, 7, 8, 9, 1, 2, 3],
                    [5, 7, 8, 9, 2, 3, 4],
                    [6, 7, 8, 9, 3, 4, 5],
                ]
            ],
        ];
    }

    /**
     * @test   augmentLeft matrix with matrix that does not match dimensions
     * @throws \Exception
     */
    public function testAugmentLeftExceptionRowsDoNotMatch()
    {
        $A = MatrixFactory::create([
            [1, 2, 3],
            [2, 3, 4],
            [3, 4, 5],
        ]);
        $B = MatrixFactory::create([
            [4, 5],
            [5, 6],
        ]);

        $this->expectException(Exception\MatrixException::class);
        $A->augmentLeft($B);
    }

    /**
     * @test         augment
     * @throws       \Exception
     */
    public function testAugmentExceptionTypeMismatch()
    {
        // Given
        $A    = MatrixFactory::create([[1]]);
        $B    = MatrixFactory::create([[new Polynomial([1,1])]]);

        // Then
        $this->expectException(Exception\MatrixException::class);

        // When
        $augmented = $A->augment($B);
    }

    /**
     * @test         augmentLeft
     * @throws       \Exception
     */
    public function testAugmentLeftExceptionTypeMismatch()
    {
        // Given
        $A    = MatrixFactory::create([[1]]);
        $B    = MatrixFactory::create([[new Polynomial([1,1])]]);

        // Then
        $this->expectException(Exception\MatrixException::class);

        // When
        $augmented = $A->augmentLeft($B);
    }

    /**
     * @test         augmentAbove
     * @throws       \Exception
     */
    public function testAugmentAboveExceptionTypeMismatch()
    {
        // Given
        $A    = MatrixFactory::create([[1]]);
        $B    = MatrixFactory::create([[new Polynomial([1,1])]]);

        // Then
        $this->expectException(Exception\MatrixException::class);

        // When
        $augmented = $A->augmentAbove($B);
    }

    /**
     * @test         augmentBelow
     * @throws       \Exception
     */
    public function testAugmentBelowExceptionTypeMismatch()
    {
        // Given
        $A    = MatrixFactory::create([[1]]);
        $B    = MatrixFactory::create([[new Polynomial([1,1])]]);

        // Then
        $this->expectException(Exception\MatrixException::class);

        // When
        $augmented = $A->augmentBelow($B);
    }
}
