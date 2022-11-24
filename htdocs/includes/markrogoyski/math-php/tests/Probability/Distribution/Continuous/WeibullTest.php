<?php

namespace MathPHP\Tests\Probability\Distribution\Continuous;

use MathPHP\Probability\Distribution\Continuous\Weibull;

class WeibullTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test         pdf
     * @dataProvider dataProviderForPdf
     * @param        float $x
     * @param        float $k
     * @param        float $λ
     * @param        float $expected_pdf
     */
    public function testPdf(float $x, float $k, float $λ, float $expected_pdf)
    {
        // Given
        $weibull = new Weibull($k, $λ);

        // When
        $pdf = $weibull->pdf($x);

        // Then
        $this->assertEqualsWithDelta($expected_pdf, $pdf, 0.0000001);
    }

    /**
     * @return array [x, k, λ. pdf]
     * Generated with R dweibull(x, shape, scale)
     */
    public function dataProviderForPdf(): array
    {
        return [
            [-1, 1, 1, 0],
            [0, 1, 1, 1],
            [1, 1, 1, 0.3678794],
            [2, 1, 1, 0.1353353],
            [3, 1, 1, 0.04978707],
            [4, 1, 1, 0.01831564],
            [5, 1, 1, 0.006737947],
            [10, 1, 1, 4.539993e-05],

            [-1, 1, 2, 0],
            [0, 1, 2, 0.5],
            [1, 1, 2, 0.3032653],
            [2, 1, 2, 0.1839397],
            [3, 1, 2, 0.1115651],
            [4, 1, 2, 0.06766764],
            [5, 1, 2, 0.0410425],
            [10, 1, 2, 0.003368973],

            [-1, 2, 1, 0],
            [0, 2, 1, 0],
            [1, 2, 1, 0.7357589],
            [2, 2, 1, 0.07326256],
            [3, 2, 1, 0.0007404588],
            [4, 2, 1, 9.002814e-07],
            [5, 2, 1, 1.388794e-10],
            [10, 2, 1, 7.440152e-43],

            [3, 4, 5, 0.1517956],
            [3, 5, 5, 0.1199042],
            [33, 34, 45, 2.711453e-05],
        ];
    }

    /**
     * @test         cdf
     * @dataProvider dataProviderForCdf
     * @param        float $x
     * @param        float $k
     * @param        float $λ
     * @param        float $expected_cdf
     */
    public function testCdf(float $x, float $k, float $λ, float $expected_cdf)
    {
        // Given
        $weibull = new Weibull($k, $λ);

        // When
        $cdf = $weibull->cdf($x);

        // Then
        $this->assertEqualsWithDelta($expected_cdf, $cdf, 0.0000001);
    }

    /**
     * @return array [x, k, λ, cdf]
     * Generated with R pweibull(x, shape, scale)
     */
    public function dataProviderForCdf(): array
    {
        return [
            [-1, 1, 1, 0],
            [0, 1, 1, 0],
            [1, 1, 1, 0.6321206],
            [2, 1, 1, 0.8646647],
            [3, 1, 1, 0.9502129],
            [4, 1, 1, 0.9816844],
            [5, 1, 1, 0.9932621],
            [10, 1, 1, 0.9999546],

            [-1, 1, 2, 0],
            [0, 1, 2, 0],
            [1, 1, 2, 0.3934693],
            [2, 1, 2, 0.6321206],
            [3, 1, 2, 0.7768698],
            [4, 1, 2, 0.8646647],
            [5, 1, 2, 0.917915],
            [10, 1, 2, 0.9932621],

            [-1, 2, 1, 0],
            [0, 2, 1, 0],
            [1, 2, 1, 0.6321206],
            [2, 2, 1, 0.9816844],
            [3, 2, 1, 0.9998766],
            [4, 2, 1, 0.9999999],
            [5, 2, 1, 1],

            [3, 4, 5, 0.1215533],
            [3, 5, 5, 0.07481356],
            [33, 34, 45, 2.631739e-05],
        ];
    }

    /**
     * @dataProvider dataProviderForInverse
     * @param float $p
     * @param float $k
     * @param float $λ
     * @param float $expected_inverse
     */
    public function testInverse(float $p, float $k, float $λ, float $expected_inverse)
    {
        // Given
        $weibull =  new Weibull($k, $λ);

        // When
        $inverse = $weibull->inverse($p);

        // Then
        $this->assertEqualsWithDelta($expected_inverse, $inverse, 0.000001);
    }

    /**
     * @return array [x, k, λ, inverse]
     * Generated with R (stats) qweibull(p, shape, scale)
     */
    public function dataProviderForInverse(): array
    {
        return [
            [0, 1, 1, 0],
            [0.1, 1, 1, 0.1053605],
            [0.3, 1, 1, 0.3566749],
            [0.5, 1, 1, 0.6931472],
            [0.7, 1, 1, 1.203973],
            [0.9, 1, 1, 2.302585],
            [1, 1, 1, \INF],

            [0, 2, 3, 0],
            [0.1, 2, 3, 0.9737785],
            [0.3, 2, 3, 1.791668],
            [0.5, 2, 3, 2.497664],
            [0.7, 2, 3, 3.291771],
            [0.9, 2, 3, 4.552281],
            [1, 2, 3, \INF],
        ];
    }

    /**
     * @test         inverse of cdf is x
     * @dataProvider dataProviderForInverseOfCdf
     * @param        float $x
     * @param        float $k
     * @param        float $λ
     */
    public function testInverseOfCdf(float $x, float $k, float $λ)
    {
        // Given
        $weibull = new Weibull($k, $λ);
        $cdf = $weibull->cdf($x);

        // When
        $inverse_of_cdf = $weibull->inverse($cdf);

        // Then
        $this->assertEqualsWithDelta($x, $inverse_of_cdf, 0.000001);
    }

    /**
     * @return array [x, k, λ]
     */
    public function dataProviderForInverseOfCdf(): array
    {
        return [
            [1, 1, 1],
            [2, 1, 1],
            [3, 1, 1],
            [4, 1, 1],
            [5, 1, 1],
            [10, 1, 1],

            [1, 1, 2],
            [2, 1, 2],
            [3, 1, 2],
            [4, 1, 2],
            [5, 1, 2],
            [10, 1, 2],

            [1, 2, 1],
            [2, 2, 1],
            [3, 2, 1],
            [4, 2, 1],
            [5, 2, 1],

            [3, 4, 5],
            [3, 5, 5],
            [33, 34, 45],
        ];
    }

    /**
     * @test         mean
     * @dataProvider dataProviderForMean
     * @param        float $k
     * @param        float $λ
     * @param        float $μ
     */
    public function testMean(float $k, float $λ, float $μ)
    {
        // Given
        $weibull = new Weibull($k, $λ);

        // When
        $mean = $weibull->mean();

        // Then
        $this->assertEqualsWithDelta($μ, $mean, 0.0001);
    }

    /**
     * @return array [k, λ, μ]
     */
    public function dataProviderForMean(): array
    {
        return [
            [1, 1, 1],
            [1, 2, 2],
            [2, 1, 0.88622692545275801365],
            [2, 2, 1.77245386],
            [22, 27, 26.34458072],
        ];
    }

    /**
     * @test         median
     * @dataProvider dataProviderForMedian
     * @param        float $k
     * @param        float $λ
     * @param        float $μ
     */
    public function testMedian(float $k, float $λ, float $μ)
    {
        // Given
        $weibull = new Weibull($k, $λ);

        // When
        $median = $weibull->median();

        // Then
        $this->assertEqualsWithDelta($μ, $median, 0.0001);
    }

    /**
     * Data generated with https://captaincalculator.com/math/statistics/weibull-distribution-calculator/
     * @return array [k, λ, μ]
     */
    public function dataProviderForMedian(): array
    {
        return [
            [1, 1, 0.69314718055995],
            [2, 1, 0.83255461],
            [1, 2, 1.38629436],
            [2, 2, 1.66510922],
            [22, 27, 26.55391482],
        ];
    }

    /**
     * @test         mode
     * @dataProvider dataProviderForMode
     * @param        float $k
     * @param        float $λ
     * @param        float $μ
     */
    public function testMode(float $k, float $λ, float $μ)
    {
        // Given
        $weibull = new Weibull($k, $λ);

        // When
        $mode = $weibull->mode();

        // Then
        $this->assertEqualsWithDelta($μ, $mode, 0.0001);
    }

    /**
     * Data generated with https://captaincalculator.com/math/statistics/weibull-distribution-calculator/
     * @return array [k, λ, μ]
     */
    public function dataProviderForMode(): array
    {
        return [
            [0.5, 1, 0],
            [1, 1, 0],
            [2, 1, 0.70710678],
            [1, 2, 0],
            [2, 2, 1.41421356],
            [22, 27, 26.94296757],
        ];
    }

    /**
     * @test rand
     */
    public function testRand()
    {
        foreach (\range(1, 10) as $k) {
            foreach (\range(1, 10) as $λ) {
                // Given
                $weibull = new Weibull($k, $λ);
                foreach (\range(1, 3) as $_) {
                    // When
                    $random = $weibull->rand();

                    // Then
                    $this->assertTrue(\is_numeric($random));
                }
            }
        }
    }
}
