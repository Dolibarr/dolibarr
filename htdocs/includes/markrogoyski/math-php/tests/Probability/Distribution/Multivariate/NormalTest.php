<?php

namespace MathPHP\Tests\Probability\Distribution\Multivariate;

use MathPHP\Probability\Distribution\Multivariate\Normal;
use MathPHP\LinearAlgebra\MatrixFactory;
use MathPHP\Exception;
use MathPHP\Tests;

class NormalTest extends \PHPUnit\Framework\TestCase
{
    use Tests\LinearAlgebra\Fixture\MatrixDataProvider;

    /**
     * @test         pdf returns the expected density
     * @dataProvider dataProviderForPdf
     * @param        array $x
     * @param        array $μ
     * @param        array $∑
     * @param        float $expected
     * @throws       \Exception
     */
    public function testPdf(array $x, array $μ, array $∑, float $expected)
    {
        // Given
        $∑      = MatrixFactory::create($∑);
        $normal = new Normal($μ, $∑);

        // When
        $pdf = $normal->pdf($x);

        // Then
        $this->assertEqualsWithDelta($expected, $pdf, 0.00000000000001);
    }

    /**
     * Test data created with scipy.stats.multivariate_normal.pdf
     * @return array
     */
    public function dataProviderForPdf(): array
    {
        return [
            [
                [0, 0],
                [0, 0],
                [
                    [1, 0],
                    [0, 1],
                ],
                0.15915494309189535,
            ],
            [
                [1, 1],
                [0, 0],
                [
                    [1, 0],
                    [0, 1],
                ],
                0.058549831524319168,
            ],
            [
                [1, 1],
                [1, 1],
                [
                    [1, 0],
                    [0, 1],
                ],
                0.15915494309189535,
            ],
            [
                [0.7, 1.4],
                [1, 1.1],
                [
                    [1, 0],
                    [0, 1],
                ],
                0.14545666578175082,
            ],
            [
                [0.7, 1.4],
                [1, 1.1],
                [
                    [1, 0],
                    [0, 2],
                ],
                0.10519382725436884,
            ],
            [
                [4.5, 7.6],
                [3.2, 6.7],
                [
                    [1, 0],
                    [0, 1],
                ],
                0.045598654639838636,
            ],
            [
                [20.3, 12.6],
                [20, 15],
                [
                    [25, 10],
                    [10, 16],
                ],
                0.0070398507893074313,
            ],
            [
                [7, 12],
                [4.8, 8.4],
                [
                    [1.7, 2.6],
                    [2.6, 6.3],
                ],
                0.019059723382617431,
            ],
            [
                [4, 9],
                [4.8, 8.4],
                [
                    [1.7, 2.6],
                    [2.6, 6.3],
                ],
                0.032434509200433989,
            ],
            [
                [4, 5],
                [4.8, 8.4],
                [
                    [1.7, 2.6],
                    [2.6, 6.3],
                ],
                0.023937002571148978,
            ],
            [
                [5, 8],
                [4.8, 8.4],
                [
                    [1.7, 2.6],
                    [2.6, 6.3],
                ],
                0.07109614254107853,
            ],
            [
                [4, 8],
                [4.8, 8.4],
                [
                    [1.7, 2.6],
                    [2.6, 6.3],
                ],
                0.057331098511004673,
            ],
            [
                [30, 50],
                [26.95, 24.8],
                [
                    [88.57632, 67.51579],
                    [67.51579, 64.27368],
                ],
                6.0531136999164446e-12,
            ],
            [
                [4.5, 7.6, 9.3],
                [3.2, 6.7, 8.0],
                [
                    [1, 0, 0],
                    [0, 1, 0],
                    [0, 0, 1],
                ],
                0.0078141772449033566,
            ],
            [
                [4.5, 7.6, 9.3],
                [3.2, 6.7, 8.0],
                [
                    [1, 0, 0],
                    [0, 1, 0],
                    [0, 0, 2],
                ],
                0.0084305843631899829,
            ],
            [
                [2, 11, 3],
                [1, 12, 2],
                [
                    [1, 2, 0],
                    [2, 5, 0.5],
                    [0, 0.5, 3],
                ],
                8.2808512671378126e-05,
            ],
        ];
    }

    /**
     * @test         pdf throws an Exception\BadDataException if the covariance matrix is not positive definite
     * @dataProvider dataProviderForNotPositiveDefiniteMatrix
     * @param        array $M Non-positive definite matrix
     * @throws       \Exception
     */
    public function testPdfCovarianceMatrixNotPositiveDefiniteException(array $M)
    {
        // Given
        $μ = [0, 0];
        $∑ = MatrixFactory::create($M);

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        $normal = new Normal($μ, $∑);
    }

    /**
     * @test    pdf throws an Exception\BadDataException if x and μ don't have the same number of elements
     * @throws \Exception
     */
    public function testPdfXAndMuDifferentNumberOfElementsException()
    {
        // Given
        $μ = [0, 0];
        $∑ = MatrixFactory::create([
            [1, 0],
            [0, 1],
        ]);
        $x      = [0, 0, 0];
        $normal = new Normal($μ, $∑);

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        $pdf = $normal->pdf($x);
    }

    /**
     * @test    pdf throws an Exception\BadDataException if the covariance matrix has a different number of elements.
     * @throws \Exception
     */
    public function testPdfCovarianceMatrixDifferentNumberOfElementsException()
    {
        // Given
        $μ = [0, 0];
        $∑ = MatrixFactory::create([
            [1, 0, 0],
            [0, 1, 0],
        ]);

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        $normal = new Normal($μ, $∑);
    }
}
