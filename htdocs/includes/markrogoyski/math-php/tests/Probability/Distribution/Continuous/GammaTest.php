<?php

namespace MathPHP\Tests\Probability\Distribution\Continuous;

use MathPHP\Probability\Distribution\Continuous\Gamma;

class GammaTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test         pdf
     * @dataProvider dataProviderForPdf
     * @param        float $x   x ∈ (0,1)
     * @param        float $k   shape parameter α > 0
     * @param        float $θ   scale parameter θ > 0
     * @param        float $expectedPdf
     */
    public function testPdf(float $x, float $k, float $θ, float $expectedPdf)
    {
        // Given
        $gamma = new Gamma($k, $θ);

        // When
        $pdf = $gamma->pdf($x);

        // Then
        $this->assertEqualsWithDelta($expectedPdf, $pdf, 0.00000001);
    }

    /**
     * Data provider for PDF
     * Test data created with calculator http://keisan.casio.com/exec/system/1180573217
     * Additional data generated with R dgamma(x, shape = k, scale = θ)
     * @return array [x, k, θ, pdf]
     */
    public function dataProviderForPdf(): array
    {
        return [
            [1, 1, 1, 0.3678794411714423215955],
            [1, 2, 1, 0.3678794411714423215955],
            [1, 1, 2, 0.3032653298563167118019],
            [2, 2, 2, 0.1839397205857211607978],
            [2, 4, 1, 0.180447044315483589192],
            [4, 2, 5, 0.07189263425875545462882],
            [18, 2, 5, 0.01967308016205064377713],
            [75, 2, 5, 9.177069615054773651144E-7],
            [0.1, 0.1, 0.1, 0.386691694403023771966],
            [15, 0.1, 0.1, 8.2986014463775253874E-68],
            [4, 0.5, 6, 0.05912753695472959648351],

            [1, 4, 5, 0.0002183282],
            [2, 4, 5, 0.001430016],
            [3, 4, 5, 0.003951444],
            [5, 4, 5, 0.01226265],
            [15, 4, 5, 0.04480836],
            [115, 4, 5, 4.161876e-08],
        ];
    }

    /**
     * @test         cdf
     * @dataProvider dataProviderForCdf
     * @param        float $x   x ∈ (0,1)
     * @param        float $k   shape parameter α > 0
     * @param        float $θ   scale parameter θ > 0
     * @param        float $expectedCdf
     */
    public function testCdf(float $x, float $k, float $θ, float $expectedCdf)
    {
        // Given
        $gamma = new Gamma($k, $θ);

        // When
        $cdf = $gamma->cdf($x);

        // Then
        $this->assertEqualsWithDelta($expectedCdf, $cdf, 0.000001);
    }

    /**
     * Data provider for CDF
     * Test data created with calculator http://keisan.casio.com/exec/system/1180573217
     * Additional data generated with R pgamma(x, shape = k, scale = θ)
     * @return array [x, k, θ, cdf]
     */
    public function dataProviderForCdf(): array
    {
        return [
            [1, 1, 1, 0.6321205588285576784045],
            [1, 2, 1, 0.264241117657115356809],
            [1, 1, 2, 0.3934693402873665763962],
            [2, 2, 2, 0.264241117657115356809],
            [2, 4, 1, 0.142876539501452951338],
            [4, 2, 5, 0.1912078645890011354258],
            [18, 2, 5, 0.8743108767424542203128],
            [75, 2, 5, 0.9999951055628719707874],
            [0.1, 0.1, 0.1, 0.975872656273672222617],
            [15, 0.1, 0.1, 1],
            [4, 0.5, 6, 0.7517869210100764165283],

            [1, 4, 5, 5.684024e-05],
            [2, 4, 5, 0.0007762514],
            [3, 4, 5, 0.003358069],
            [5, 4, 5, 0.01898816],
            [15, 4, 5, 0.3527681],
            [115, 4, 5, 0.9999998],
        ];
    }

    /**
     * @test         mean returns the expected average
     * @dataProvider dataProviderForMean
     * @param        float $k
     * @param        float $θ
     * @param        float $μ
     */
    public function testMean(float $k, float $θ, float $μ)
    {
        // Given
        $gamma = new Gamma($k, $θ);

        // When
        $mean = $gamma->mean();

        // Then
        $this->assertEqualsWithDelta($μ, $mean, 0.0001);
    }

    /**
     * Data provider for mean
     * @return array [k, θ, μ]
     */
    public function dataProviderForMean(): array
    {
        return [
            [1, 1, 1.0],
            [1, 2, 2.0],
            [2, 1, 2.0],
            [9, 0.5, 4.5],
        ];
    }

    /**
     * @test         median returns the expected approximation of the average
     * @dataProvider dataProviderForMedian
     * @param        float $k
     * @param        float $θ
     * @param        float $expectedApproximation
     */
    public function testMedian(float $k, float $θ, float $expectedApproximation)
    {
        // Given
        $gamma = new Gamma($k, $θ);

        // When
        $median = $gamma->median();

        // Then
        $this->assertEqualsWithDelta($expectedApproximation, $median, 0.000001);
    }

    /**
     * Data provider for median
     * @return array [k, θ, μ]
     */
    public function dataProviderForMedian(): array
    {
        return [
            [1, 1, 0.6875],
            [1, 2, 1.375],
            [2, 1, 1.6774193548387],
            [9, 0.5, 4.33455882352943],
        ];
    }

    /**
     * @test         mode
     * @dataProvider dataProviderForMode
     * @param        float $k
     * @param        float $θ
     * @param        float $expected
     */
    public function testMode(float $k, float $θ, float $expected)
    {
        // Given
        $gamma = new Gamma($k, $θ);

        // When
        $mode = $gamma->mode();

        // Then
        $this->assertEqualsWithDelta($expected, $mode, 0.000001);
    }

    /**
     * Data provider for mode
     * @return array [k, θ, μ]
     */
    public function dataProviderForMode(): array
    {
        return [
            [1, 1, 0],
            [1, 2, 0],
            [2, 1, 1],
            [2, 2, 2],
            [2, 3, 3],
            [3, 1, 2],
            [3, 2, 4],
            [3, 3, 6],
        ];
    }

    /**
     * @test         mode is not a number if k < 1
     * @dataProvider dataProviderForModeNan
     * @param        float $k
     * @param        float $θ
     */
    public function testModeNan(float $k, float $θ)
    {
        // Given
        $gamma = new Gamma($k, $θ);

        // When
        $mode = $gamma->mode();

        // Then
        $this->assertNan($mode);
    }

    /**
     * Data provider for mode NAN
     * @return array [k, θ]
     */
    public function dataProviderForModeNan(): array
    {
        return [
            [0.1, 1],
            [0.5, 3],
            [0.9, 6],
        ];
    }

    /**
     * @test         variance
     * @dataProvider dataProviderForVariance
     * @param        float $k
     * @param        float $θ
     * @param        float $expected
     */
    public function testVariance(float $k, float $θ, float $expected)
    {
        // Given
        $gamma = new Gamma($k, $θ);

        // When
        $variance = $gamma->variance();

        // Then
        $this->assertEqualsWithDelta($expected, $variance, 0.000001);
    }

    /**
     * Data provider for variance
     * @return array [k, θ, variance]
     */
    public function dataProviderForVariance(): array
    {
        return [
            [1, 1, 1],
            [1, 2, 4],
            [2, 1, 2],
            [2, 2, 8],
            [2, 3, 18],
            [3, 1, 3],
            [3, 2, 12],
            [3, 3, 27],
        ];
    }
}
