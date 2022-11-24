<?php

namespace MathPHP\Tests\Probability\Distribution\Continuous;

use MathPHP\Probability\Distribution\Continuous\F;

class FTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test         pdf
     * @dataProvider dataProviderForPdf
     * @param        int   $x
     * @param        int   $d₁
     * @param        int   $d₂
     * @param        float $expectedPdf
     */
    public function testPdf(int $x, int $d₁, int $d₂, float $expectedPdf)
    {
        // Given
        $f = new F($d₁, $d₂);

        // When
        $pdf = $f->pdf($x);

        // Then
        $this->assertEqualsWithDelta($expectedPdf, $pdf, 0.00001);
    }

    /**
     * @return array [x, d₁, d₂, pdf]
     * Generated with R df(x, d₁, d₂)
     */
    public function dataProviderForPdf(): array
    {
        return [
            [1, 1, 1, 0.1591549],
            [2, 1, 1, 0.07502636],
            [3, 1, 1, 0.04594407],
            [4, 1, 1, 0.03183099],
            [5, 1, 1, 0.02372542],
            [10, 1, 1, 0.009150766],

            [1, 2, 1, 0.1924501],
            [2, 2, 1, 0.08944272],
            [3, 2, 1, 0.05399492],
            [4, 2, 1, 0.03703704],
            [5, 2, 1, 0.02741012],
            [10, 2, 1, 0.01039133],

            [1, 1, 2, 0.1924501],
            [2, 1, 2, 0.08838835],
            [3, 1, 2, 0.05163978],
            [4, 1, 2, 0.03402069],
            [5, 1, 2, 0.02414726],
            [10, 1, 2, 0.007607258],

            [1, 2, 2, 0.25],
            [2, 2, 2, 0.1111111],
            [3, 2, 2, 0.0625],
            [4, 2, 2, 0.04],
            [5, 2, 2, 0.02777778],
            [10, 2, 2, 0.008264463],

            [5, 3, 7, 0.01667196],
            [7, 6, 2, 0.016943],
            [7, 20, 14, 0.0002263343],
            [45, 2, 3, 0.0001868942],
        ];
    }

    /**
     * @test         cdf
     * @dataProvider dataProviderForCdf
     * @param        int   $x
     * @param        int   $d₁
     * @param        int   $d₂
     * @param        float $expectedCdf
     */
    public function testCdf(int $x, int $d₁, int $d₂, float $expectedCdf)
    {
        // Given
        $f = new F($d₁, $d₂);

        // When
        $cdf = $f->cdf($x);

        // Then
        $this->assertEqualsWithDelta($expectedCdf, $cdf, 0.00001);
    }

    /**
     * @return array [x, d₁, d₂, cdf]
     * Generated with R pf(x, d₁, d₂)
     */
    public function dataProviderForCdf(): array
    {
        return [
            [0, 1, 1, 0],
            [0, 1, 2, 0],
            [0, 2, 1, 0],
            [0, 2, 2, 0],
            [0, 2, 3, 0],

            [1, 1, 1, 0.5],
            [2, 1, 1, 0.6081734],
            [3, 1, 1, 0.6666667],
            [4, 1, 1, 0.7048328],
            [5, 1, 1, 0.7322795],
            [10, 1, 1, 0.8050178],

            [1, 2, 1, 0.4226497],
            [2, 2, 1, 0.5527864],
            [3, 2, 1, 0.6220355],
            [4, 2, 1, 0.6666667],
            [5, 2, 1, 0.6984887],
            [10, 2, 1, 0.7817821],

            [1, 1, 2, 0.5773503],
            [2, 1, 2, 0.7071068],
            [3, 1, 2, 0.7745967],
            [4, 1, 2, 0.8164966],
            [5, 1, 2, 0.8451543],
            [10, 1, 2, 0.9128709],

            [1, 2, 2, 0.5],
            [2, 2, 2, 0.6666667],
            [3, 2, 2, 0.75],
            [4, 2, 2, 0.8],
            [5, 2, 2, 0.8333333],
            [10, 2, 2, 0.9090909],

            [5, 3, 7, 0.9633266],
            [7, 6, 2, 0.8697408],
            [7, 20, 14, 0.9997203],
            [45, 2, 3, 0.9942063],
        ];
    }

    /**
     * @test         mean
     * @dataProvider dataProviderForMean
     * @param        int   $d₁
     * @param        int   $d₂
     * @param        float $μ
     */
    public function testMean(int $d₁, int $d₂, float $μ)
    {
        // Given
        $f = new F($d₁, $d₂);

        // When
        $mean = $f->mean();

        // Then
        $this->assertEqualsWithDelta($μ, $mean, 0.0001);
    }

    /**
     * @return array [d₁, d₂, $μ]
     */
    public function dataProviderForMean(): array
    {
        return [
            [1, 3, 3,],
            [1, 4, 2],
            [1, 5, 1.66666667],
            [1, 6, 1.5],
        ];
    }

    /**
     * @test         mean is not a number if d₂ ≤ 2
     * @dataProvider dataProviderForMeanNan
     * @param        int $d₁
     * @param        int $d₂
     */
    public function testMeanNAN(int $d₁, int $d₂)
    {
        // Given
        $f = new F($d₁, $d₂);

        // When
        $mean = $f->mean();

        // Then
        $this->assertNan($mean);
    }

    /**
     * @return array [d₁, d₂]
     */
    public function dataProviderForMeanNan(): array
    {
        return [
            [1, 1],
            [1, 2],
        ];
    }

    /**
     * @test         mode
     * @dataProvider dataProviderForMode
     * @param        int   $d₁
     * @param        int   $d₂
     * @param        float $μ
     */
    public function testMode(int $d₁, int $d₂, float $μ)
    {
        // Given
        $f = new F($d₁, $d₂);

        // When
        $mode = $f->mode();

        // Then
        $this->assertEqualsWithDelta($μ, $mode, 0.0001);
    }

    /**
     * @return array [d₁, d₂, μ]
     */
    public function dataProviderForMode(): array
    {
        return [
            [3, 1, 0.11111111],
            [3, 2, 0.16666667],
            [3, 3, 0.2],
            [3, 4, 0.22222222],
            [4, 1, 0.16666667],
            [4, 2, 0.25],
        ];
    }

    /**
     * @test         mode is not defined for d₁ <= 2
     * @dataProvider dataProviderForModeNan
     * @param        int   $d₁
     * @param        int   $d₂
     */
    public function testModeNan(int $d₁, int $d₂)
    {
        // Given
        $f = new F($d₁, $d₂);

        // When
        $mode = $f->mode();

        // Then
        $this->assertNan($mode);
    }

    /**
     * @return array [d₁, d]
     */
    public function dataProviderForModeNan(): array
    {
        return [
            [1, 1],
            [1, 5],
            [2, 2],
            [2, 4],
        ];
    }

    /**
     * @test         variance
     * @dataProvider dataProviderForVariance
     * @param        int   $d₁
     * @param        int   $d₂
     * @param        float $expected
     */
    public function testVariance(int $d₁, int $d₂, float $expected)
    {
        // Given
        $f = new F($d₁, $d₂);

        // When
        $variance = $f->variance();

        // Then
        $this->assertEqualsWithDelta($expected, $variance, 0.0001);
    }

    /**
     * @return array [d₁, d₂, variance]
     */
    public function dataProviderForVariance(): array
    {
        return [
            [1, 5, 22.22222222],
            [2, 5, 13.88888889],
            [3, 5, 11.11111111],
            [4, 5, 9.72222222],
            [5, 5, 8.88888889],
            [6, 5, 8.33333333],
            [5, 7, 2.61333333],
            [9, 8, 1.48148148],
        ];
    }

    /**
     * @test         variance is not defined for d₂ <= 4
     * @dataProvider dataProviderForVarianceNan
     * @param        int   $d₁
     * @param        int   $d₂
     */
    public function testVarianceNan(int $d₁, int $d₂)
    {
        // Given
        $f = new F($d₁, $d₂);

        // When
        $variance = $f->variance();

        // Then
        $this->assertNan($variance);
    }

    /**
     * @return array [d₁, d]
     */
    public function dataProviderForVarianceNan(): array
    {
        return [
            [1, 1],
            [1, 2],
            [2, 3],
            [5, 4],
        ];
    }

    /**
     * @test         median (temporary version that is just the mean)
     * @dataProvider dataProviderForMean
     * @todo         Rewrite test using actual median values once median calculation is implemented
     * @param        int   $d₁
     * @param        int   $d₂
     * @param        float $μ
     */
    public function testMedianTemporaryVersion(int $d₁, int $d₂, float $μ)
    {
        // Given
        $f = new F($d₁, $d₂);

        // When
        $mean = $f->median();

        // Then
        $this->assertEqualsWithDelta($μ, $mean, 0.0001);
    }
}
