<?php

namespace MathPHP\Tests\Probability\Distribution\Continuous;

use MathPHP\Probability\Distribution\Continuous\Logistic;

class LogisticTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test         pdf
     * @dataProvider dataProviderForPdf
     * @param        float $x
     * @param        float $μ
     * @param        float $s
     * @param        float $expected_pdf
     */
    public function testPdf(float $x, float $μ, float $s, float $expected_pdf)
    {
        // Given
        $logistic = new Logistic($μ, $s);

        // When
        $pdf = $logistic->pdf($x);

        // Then
        $this->assertEqualsWithDelta($expected_pdf, $pdf, 0.000001);
    }

    /**
     * @return array [x, μ, s, pdf]
     * Generated with R (stats) dlogis(x, μ, s)
     */
    public function dataProviderForPdf(): array
    {
        return [
            [0, 0, 1, 0.25],
            [1, 0, 1, 0.1966119],
            [2, 0, 1, 0.1049936],
            [3, 0, 1, 0.04517666],
            [4, 0, 1, 0.01766271],
            [5, 0, 1, 0.006648057],
            [10, 0, 1, 4.539581e-05],

            [0, 1, 1, 0.1966119],
            [1, 1, 1, 0.25],
            [2, 1, 1, 0.1966119],
            [3, 1, 1, 0.1049936],
            [4, 1, 1, 0.04517666],
            [5, 1, 1, 0.01766271],
            [10, 1, 1, 0.0001233793],

            [-5, 0, 0.7, 0.001127488648],
            [-4.2, 0, 0.7, 0.003523584702],
            [-3.5, 0, 0.7, 0.009497223815],
            [-3.0, 0, 0.7, 0.01913226324],
            [-2.0, 0, 0.7, 0.07337619322],
            [-0.1, 0, 0.7, 0.3553268797],
            [0, 0, 0.7, 0.3571428571],
            [0.1, 0, 0.7, 0.3553268797],
            [3.5, 0, 0.7, 0.009497223815],
            [4.2, 0, 0.7, 0.003523584702],
            [5, 0, 0.7, 0.001127488648],

            [-5, 2, 1.5, 0.006152781498],
            [-3.7, 2, 1.5, 0.01426832061],
            [0, 2, 1.5, 0.1100606731],
            [3.7, 2, 1.5, 0.1228210582],
            [5, 2, 1.5, 0.06999572],
        ];
    }

    /**
     * @test         cdf
     * @dataProvider dataProviderForCdf
     * @param        float $x
     * @param        float $μ
     * @param        float $s
     * @param        float $expected_cdf
     */
    public function testCdf(float $x, float $μ, float $s, float $expected_cdf)
    {
        // Given
        $logistic = new Logistic($μ, $s);

        // When
        $cdf = $logistic->cdf($x);

        // Then
        $this->assertEqualsWithDelta($expected_cdf, $cdf, 0.000001);
    }

    /**
     * @test         inverse of cdf is x
     * @dataProvider dataProviderForCdf
     * @param        float $x
     * @param        float $μ
     * @param        float $s
     */
    public function testInverseOfCdf(float $x, float $μ, float $s)
    {
        // Given
        $logistic = new Logistic($μ, $s);
        $cdf      = $logistic->cdf($x);

        // When
        $inverse_of_cdf = $logistic->inverse($cdf);

        // Then
        $this->assertEqualsWithDelta($x, $inverse_of_cdf, 0.000001);
    }

    /**
     * @return array [x, μ, s, cdf]
     * Generated with R (stats) plogis(x, μ, s)
     */
    public function dataProviderForCdf(): array
    {
        return [
            [0, 0, 1, 0.5],
            [1, 0, 1, 0.7310586],
            [2, 0, 1, 0.8807971],
            [3, 0, 1, 0.9525741],
            [4, 0, 1, 0.9820138],
            [5, 0, 1, 0.9933071],
            [10, 0, 1, 0.9999546],

            [0, 1, 1, 0.2689414],
            [1, 1, 1, 0.5],
            [2, 1, 1, 0.7310586],
            [3, 1, 1, 0.8807971],
            [4, 1, 1, 0.9525741],
            [5, 1, 1, 0.9820138],
            [10, 1, 1, 0.9998766],

            [-4.8, 0, 0.7, 0.001050809752],
            [-3.5, 0, 0.7, 0.006692850924],
            [-3.0, 0, 0.7, 0.01357691694],
            [-2.0, 0, 0.7, 0.05431326613],
            [-0.1, 0, 0.7, 0.4643463292],
            [0, 0, 0.7, 0.5],
            [0.1, 0, 0.7, 0.5356536708],
            [3.5, 0, 0.7, 0.9933071491],
            [4.2, 0, 0.7, 0.9975273768],
            [5, 0, 0.7, 0.9992101341],

            [-5, 2, 1.5, 0.009315959345],
            [-3.7, 2, 1.5, 0.02188127094],
            [0, 2, 1.5, 0.2086085273],
            [3.7, 2, 1.5, 0.7564535292],
            [5, 2, 1.5, 0.880797078],
        ];
    }

    /**
     * @test     mean
     */
    public function testMean()
    {
        foreach (\range(-3, 3) as $μ) {
            foreach (\range(1, 3) as $s) {
                // Given
                $logistic = new Logistic($μ, $s);

                // When
                $mean = $logistic->mean();

                // Then
                $this->assertEquals($μ, $mean);
            }
        }
    }

    /**
     * @test     median
     */
    public function testMedian()
    {
        foreach (\range(-3, 3) as $μ) {
            foreach (\range(1, 3) as $s) {
                // Given
                $logistic = new Logistic($μ, $s);

                // When
                $median = $logistic->median();

                // Then
                $this->assertEquals($μ, $median);
            }
        }
    }

    /**
     * @test     mode
     */
    public function testMode()
    {
        foreach (\range(-3, 3) as $μ) {
            foreach (\range(1, 3) as $s) {
                // Given
                $logistic = new Logistic($μ, $s);

                // When
                $mode = $logistic->mode();

                // Then
                $this->assertEquals($μ, $mode);
            }
        }
    }

    /**
     * @test         variance
     * @dataProvider dataProviderForVariance
     * @param        float $μ
     * @param        float $s
     * @param        float $expected
     */
    public function testVariance(float $μ, float $s, float $expected)
    {
        // Given
        $logistic = new Logistic($μ, $s);

        // When
        $variance = $logistic->variance();

        // Then
        $this->assertEqualsWithDelta($expected, $variance, 0.000001);
    }

    /**
     * @return array
     */
    public function dataProviderForVariance(): array
    {
        return [
            [0, 1, 3.28986813369645],
            [0, 2, 13.15947253478581],
            [0, 3, 29.60881320326808],
            [5, 4, 52.63789013914325],
        ];
    }

    /**
     * @test         inverse
     * @dataProvider dataProviderForInverse
     * @param        float $p
     * @param        float $μ
     * @param        float $s
     * @param        $expected_inverse
     */
    public function testInverse(float $p, float $μ, float $s, $expected_inverse)
    {
        // Given
        $logistic = new Logistic($μ, $s);

        // When
        $inverse = $logistic->inverse($p);

        // Then
        $this->assertEqualsWithDelta($expected_inverse, $inverse, 0.00001);
    }

    /**
     * @return array [p, μ, s, inverse]
     * Generated with R (stats) qlogis(p, location, scale)
     */
    public function dataProviderForInverse(): array
    {
        return [
            [0, -1, 1, -\INF],
            [0.1, -1, 1, -3.197225],
            [0.3, -1, 1, -1.847298],
            [0.5, -1, 1, -1],
            [0.7, -1, 1, -0.1527021],
            [0.9, -1, 1, 1.197225],
            [1, -1, 1, \INF],

            [0, 0, 1, -\INF],
            [0.1, 0, 1, -2.197225],
            [0.3, 0, 1, -0.8472979],
            [0.5, 0, 1, 0],
            [0.7, 0, 1, 0.8472979],
            [0.9, 0, 1, 2.197225],
            [1, 0, 1, \INF],

            [0, 1, 1, -\INF],
            [0.1, 1, 1, -1.197225],
            [0.3, 1, 1, 0.1527021],
            [0.5, 1, 1, 1],
            [0.7, 1, 1, 1.847298],
            [0.9, 1, 1, 3.197225],
            [1, 1, 1, \INF],

            [0, 2, 5, -\INF],
            [0.1, 2, 5, -8.986123],
            [0.3, 2, 5, -2.236489],
            [0.5, 2, 5, 2],
            [0.7, 2, 5, 6.236489],
            [0.9, 2, 5, 12.98612],
            [1, 2, 5, \INF],
        ];
    }

    /**
     * @test rand
     */
    public function testRand()
    {
        foreach (\range(-3, 3) as $μ) {
            foreach (\range(1, 3) as $s) {
                // Given
                $logistic = new Logistic($μ, $s);

                // When
                $rand = $logistic->rand();

                // Then
                $this->assertTrue(\is_numeric($rand));
            }
        }
    }
}
