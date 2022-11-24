<?php

namespace MathPHP\Tests\Probability\Distribution\Continuous;

use MathPHP\Probability\Distribution\Continuous\LogNormal;

class LogNormalTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test         pdf
     * @dataProvider dataProviderForPdf
     * @param        float $x
     * @param        float $μ
     * @param        float $σ
     * @param        float $expected_pdf
     */
    public function testPdf(float $x, float $μ, float $σ, float $expected_pdf)
    {
        // Given
        $log_normal = new LogNormal($μ, $σ);

        // When
        $pdf = $log_normal->pdf($x);

        // Then
        $this->assertEqualsWithDelta($expected_pdf, $pdf, 0.000001);
    }

    /**
     * @return array [x, μ, σ, pdf]
     * Generated with R (stats) dlnorm(q, meanlog sdlog)
     */
    public function dataProviderForPdf(): array
    {
        return [
            [4.3, 6, 2, 0.003522012],
            [4.3, 6, 1, 3.082892e-06],
            [4.3, 1, 1, 0.08351597],
            [1, 6, 2, 0.002215924],
            [2, 6, 2, 0.002951125],
            [2, 3, 2, 0.0512813],

            [0.1, -2, 1, 3.810909],
            [1, -2, 1, 0.05399097],
            [2, -2, 1, 0.005307647],
            [5, -2, 1, 0.0001182869],
        ];
    }

    /**
     * @test         cdf
     * @dataProvider dataProviderForCdf
     * @param        float $x
     * @param        float $μ
     * @param        float $σ
     * @param        float $expected_pdf
     */
    public function testCdf(float $x, float $μ, float $σ, float $expected_pdf)
    {
        // Given
        $log_normal = new LogNormal($μ, $σ);

        // When
        $cdf = $log_normal->cdf($x);

        // Then
        $this->assertEqualsWithDelta($expected_pdf, $cdf, 0.000001);
    }

    /**
     * @return array [x, μ, σ, cdf]
     * Generated with R (stats) plnorm(q, meanlog sdlog)
     */
    public function dataProviderForCdf(): array
    {
        return [
            [4.3, 6, 2, 0.0115828],
            [4.3, 6, 1, 2.794294e-06],
            [4.3, 1, 1, 0.6767447],
            [1, 6, 2, 0.001349898],
            [2, 6, 2, 0.003983957],
            [2, 3, 2, 0.1243677],

            [0.1, -2, 1, 0.381103],
            [1, -2, 1, 0.9772499],
            [2, -2, 1, 0.9964609],
            [5, -2, 1, 0.9998466],
        ];
    }

    /**
     * @test         mean
     * @dataProvider dataProviderForMean
     * @param        float $μ
     * @param        float $σ
     * @param        float $expected_mean
     */
    public function testMean(float $μ, float $σ, float $expected_mean)
    {
        // Given
        $log_normal = new LogNormal($μ, $σ);

        // When
        $mean = $log_normal->mean();

        // Then
        $this->assertEqualsWithDelta($expected_mean, $mean, 0.000001);
    }

    /**
     * @return array
     */
    public function dataProviderForMean(): array
    {
        return [
            [1, 1, 4.48168907034],
            [2, 2, 54.5981500331],
            [1.3, 1.6, 13.1971381597],
            [2.6, 3.16, 1983.86055382],
        ];
    }

    /**
     * @test         median
     * @dataProvider dataProviderForMedian
     * @param        float $μ
     * @param        float $σ
     * @param        float $expected_median
     */
    public function testMedian(float $μ, float $σ, float $expected_median)
    {
        // Given
        $log_normal = new LogNormal($μ, $σ);

        // When
        $median = $log_normal->median();

        // Then
        $this->assertEqualsWithDelta($expected_median, $median, 0.000001);
    }

    /**
     * @return array
     */
    public function dataProviderForMedian(): array
    {
        return [
            [1, 1, 2.718281828459045],
            [2, 2, 7.38905609893065],
            [1.3, 1.6, 3.669296667619244],
            [2.6, 3.16, 13.46373803500169],
        ];
    }

    /**
     * @test         mode
     * @dataProvider dataProviderForMode
     * @param        float $μ
     * @param        float $σ
     * @param        float $expected
     */
    public function testMode(float $μ, float $σ, float $expected)
    {
        // Given
        $log_normal = new LogNormal($μ, $σ);

        // When
        $mode = $log_normal->mode();

        // Then
        $this->assertEqualsWithDelta($expected, $mode, 0.000001);
    }

    /**
     * @return array
     */
    public function dataProviderForMode(): array
    {
        return [
            [1, 1, 1],
            [1, 2, 0.049787068367864],
            [2, 1, 2.718281828459045],
            [2, 2, 0.135335283236613],
            [1.3, 1.6, 0.28365402649977],
            [2.6, 3.16, 0.000620118480873],
        ];
    }

    /**
     * @test         variance
     * @dataProvider dataProviderForVariance
     * @param        float $μ
     * @param        float $σ
     * @param        float $expected
     */
    public function testVariance(float $μ, float $σ, float $expected)
    {
        // Given
        $log_normal = new LogNormal($μ, $σ);

        // When
        $variance = $log_normal->variance();

        // Then
        $this->assertEqualsWithDelta($expected, $variance, 0.001);
    }

    /**
     * @return array
     */
    public function dataProviderForVariance(): array
    {
        return [
            [1, 1, 34.51261310995665],
            [1, 2, 21623.03700131397116],
            [2, 1, 255.01563439015922],
            [2, 2, 159773.83343196209715],
            [1.3, 1.6, 2078.79512496361378],
            [2.6, 3.16, 85446299583.51734035309427],
        ];
    }


    /**
     * @test         inverse
     * @dataProvider dataProviderForInverse
     * @param        float $p
     * @param        float $μ
     * @param        float $σ
     * @param        float $expected_inverse
     */
    public function testInverse(float $p, float $μ, float $σ, float $expected_inverse)
    {
        // Given
        $log_normal = new LogNormal($μ, $σ);

        // When
        $inverse = $log_normal->inverse($p);

        // Then
        $this->assertEqualsWithDelta($expected_inverse, $inverse, 0.001);
    }

    /**
     * @return array [p, μ, σ, inverse]
     * Generated with R (stats) qlnorm(p, meanlog, sdlog)
     */
    public function dataProviderForInverse(): array
    {
        return [
            [0, -1, 1, 0],
            [0.1, -1, 1, 0.1021256],
            [0.2, -1, 1, 0.1585602],
            [0.3, -1, 1, 0.2177516],
            [0.5, -1, 1, 0.3678794],
            [0.7, -1, 1, 0.6215124],
            [0.9, -1, 1, 1.325184],
            [1, -1, 1, \INF],

            [0, 1, 1, 0],
            [0.1, 1, 1, 0.754612],
            [0.3, 1, 1, 1.608978],
            [0.5, 1, 1, 2.718282],
            [0.7, 1, 1, 4.59239],
            [0.9, 1, 1, 9.791861],
            [1, 1, 1, \INF],

            [0, 2, 3,0 ],
            [0.1, 2, 3, 0.1580799],
            [0.3, 2, 3, 1.532344],
            [0.5, 2, 3, 7.389056],
            [0.7, 2, 3, 35.63048],
            [0.9, 2, 3, 345.3833],
            [1, 2, 3, \INF],

            [0, 5, 2, 0],
            [0.1, 5, 2, 11.43749],
            [0.3, 5, 2, 51.99767],
            [0.5, 5, 2, 148.4132],
            [0.7, 5, 2, 423.6048],
            [0.8, 5, 2, 798.9053],
            [1, 5, 2, \INF],
        ];
    }

    /**
     * @test         inverse of CDF is original x
     * @dataProvider dataProviderForCdf
     * @param        float $x
     * @param        float $μ
     * @param        float $σ
     */
    public function testInverseOfCdf(float $x, float $μ, float $σ)
    {
        // Given
        $log_normal = new LogNormal($μ, $σ);
        $cdf        = $log_normal->cdf($x);

        // When
        $inverse_of_cdf = $log_normal->inverse($cdf);

        // Then
        $this->assertEqualsWithDelta($x, $inverse_of_cdf, 0.001);
    }

    /**
     * @test rand
     */
    public function testRand()
    {
        foreach (\range(-3, 3) as $μ) {
            foreach (\range(1, 3) as $σ) {
                // Given
                $log_normal = new LogNormal($μ, $σ);

                // When
                $random = $log_normal->rand();

                // Then
                $this->assertTrue(\is_numeric($random));
            }
        }
    }
}
