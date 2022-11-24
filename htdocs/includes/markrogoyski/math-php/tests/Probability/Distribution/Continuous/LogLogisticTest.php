<?php

namespace MathPHP\Tests\Probability\Distribution\Continuous;

use MathPHP\Probability\Distribution\Continuous\LogLogistic;

class LogLogisticTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test         pdf
     * @dataProvider dataProviderForPdf
     * @param        float $x
     * @param        float $α
     * @param        float $β
     * @param        float $expectedPdf
     */
    public function testPdf(float $x, float $α, float $β, float $expectedPdf)
    {
        // Given
        $logLogistic = new LogLogistic($α, $β);

        // When
        $pdf = $logLogistic->pdf($x);

        // Then
        $this->assertEqualsWithDelta($expectedPdf, $pdf, 0.000001);
    }

    /**
     * @return array [x, α, β, pdf]
     * Generated with R dllogis(x, scale = α, shape = β) [From eha package]
     */
    public function dataProviderForPdf(): array
    {
        return [
            [0, 1, 1, 1],
            [1, 1, 1, 0.25],
            [2, 1, 1, 0.1111111],
            [3, 1, 1, 0.0625],
            [4, 1, 1, 0.04],
            [5, 1, 1, 0.02777778],
            [10, 1, 1, 0.008264463],

            [0, 1, 2, 0],
            [1, 1, 2, 0.5],
            [2, 1, 2, 0.16],
            [3, 1, 2, 0.06],
            [4, 1, 2, 0.02768166],
            [5, 1, 2, 0.0147929],
            [10, 1, 2, 0.001960592],

            [0, 2, 2, 0],
            [1, 2, 2, 0.32],
            [2, 2, 2, 0.25],
            [3, 2, 2, 0.1420118],
            [4, 2, 2, 0.08],
            [5, 2, 2, 0.04756243],
            [10, 2, 2, 0.00739645],

            [4, 2, 3, 0.07407407],
        ];
    }

    /**
     * @test         cdf
     * @dataProvider dataProviderForCdf
     * @param        float $x
     * @param        float $α
     * @param        float $β
     * @param        float $expectedPdf
     */
    public function testCdf(float $x, float $α, float $β, float $expectedPdf)
    {
        // Given
        $logLogistic = new LogLogistic($α, $β);

        // When
        $cdf = $logLogistic->cdf($x);

        // Then
        $this->assertEqualsWithDelta($expectedPdf, $cdf, 0.000001);
    }

    /**
     * @test         inverse of cdf is x
     * @dataProvider dataProviderForCdf
     * @param        float $x
     * @param        float $α
     * @param        float $β
     */
    public function testInverse(float $x, float $α, float $β)
    {
        // Given
        $logLogistic = new LogLogistic($α, $β);
        $cdf         = $logLogistic->cdf($x);

        // When
        $inverseOfCdf = $logLogistic->inverse($cdf);

        // Then
        $this->assertEqualsWithDelta($x, $inverseOfCdf, 0.000001);
    }

    /**
     * @return array [x, α, β, cdf]
     * Generated with R pllogis(x, scale = α, shape = β) [From eha package]
     */
    public function dataProviderForCdf(): array
    {
        return [
            [0, 1, 1, 0],
            [1, 1, 1, 0.5],
            [2, 1, 1, 0.6666667],
            [3, 1, 1, 0.75],
            [4, 1, 1, 0.8],
            [5, 1, 1, 0.8333333],
            [10, 1, 1, 0.9090909],

            [0, 1, 2, 0],
            [1, 1, 2, 0.5],
            [2, 1, 2, 0.8],
            [3, 1, 2, 0.9],
            [4, 1, 2, 0.9411765],
            [5, 1, 2, 0.9615385],
            [10, 1, 2, 0.990099],

            [0, 2, 2, 0],
            [1, 2, 2, 0.2],
            [2, 2, 2, 0.5],
            [3, 2, 2, 0.6923077],
            [4, 2, 2, 0.8],
            [5, 2, 2, 0.862069],
            [10, 2, 2, 0.9615385],

            [4, 2, 3, 0.8888889],
        ];
    }

    /**
     * @test         mean
     * @dataProvider dataProviderForMean
     * @param        float $α
     * @param        float $β
     * @param        float $μ
     */
    public function testMean(float $α, float $β, float $μ)
    {
        // Given
        $logLogistic = new LogLogistic($α, $β);

        // When
        $mean = $logLogistic->mean();

        // Then
        $this->assertEqualsWithDelta($μ, $mean, 0.00001);
    }

    /**
     * @return array [α, β, μ]
     */
    public function dataProviderForMean(): array
    {
        return [
            [1, 2, 1.570795],
            [2, 2, 3.14159],
            [3, 3, 3.62759751692],
            [5, 4, 5.55360266602],
        ];
    }

    /**
     * @test         mean is not a number when shape is not greater than 1
     * @dataProvider dataProviderForMeanNan
     * @param        float $α
     * @param        float $β
     */
    public function testMeanNan(float $α, float $β)
    {
        $logLogistic = new LogLogistic($α, $β);
        $this->assertNan($logLogistic->mean());
    }

    /**
     * @return array [α, β]
     */
    public function dataProviderForMeanNan(): array
    {
        return [
            [1, 1],
            [2, 1],
            [3, 1],
            [5, 1],
        ];
    }

    /**
     * @test         median
     * @dataProvider dataProviderForMean
     * @param        float $α
     * @param        float $β
     */
    public function testMedian(float $α, float $β)
    {
        // Given
        $logLogistic = new LogLogistic($α, $β);

        // When
        $median = $logLogistic->median();

        // Then
        $this->assertEqualsWithDelta($α, $median, 0.00001);
    }

    /**
     * @test         mode
     * @dataProvider dataProviderForMode
     * @param        float $α
     * @param        float $β
     * @param        float $expected
     */
    public function testMode(float $α, float $β, float $expected)
    {
        // Given
        $logLogistic = new LogLogistic($α, $β);

        // When
        $mode = $logLogistic->mode();

        // Then
        $this->assertEqualsWithDelta($expected, $mode, 0.00001);
    }

    /**
     * @return array
     */
    public function dataProviderForMode(): array
    {
        return [
            [1, 0.2, 0],
            [2, 0.9, 0],
            [3, 1, 0],
            [1, 2, 0.577350269189623],
            [1, 3, 0.793700525984102],
            [2, 3, 1.5874010519682],
        ];
    }

    /**
     * @test         variance
     * @dataProvider dataProviderForVariance
     * @param        float $α
     * @param        float $β
     * @param        float $expected
     */
    public function testVariance(float $α, float $β, float $expected)
    {
        // Given
        $logLogistic = new LogLogistic($α, $β);

        // When
        $variance = $logLogistic->variance();

        // Then
        $this->assertEqualsWithDelta($expected, $variance, 0.00001);
    }

    /**
     * @return array
     */
    public function dataProviderForVariance(): array
    {
        return [
            [1, 3, -473.39731252713666],
            [2, 4, -79.39739526887552],
        ];
    }

    /**
     * @test         variance is not a number when β ≤ 2
     * @dataProvider dataProviderForVarianceNan
     * @param        float $α
     * @param        float $β
     */
    public function testVarianceNan(float $α, float $β)
    {
        // Given
        $logLogistic = new LogLogistic($α, $β);

        // When
        $variance = $logLogistic->variance();

        // Then
        $this->assertNan($variance);
    }

    /**
     * @return array
     */
    public function dataProviderForVarianceNan(): array
    {
        return [
            [1, 1],
            [2, 2],
        ];
    }
}
