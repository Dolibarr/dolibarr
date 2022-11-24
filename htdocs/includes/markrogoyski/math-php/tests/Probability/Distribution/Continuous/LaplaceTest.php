<?php

namespace MathPHP\Tests\Probability\Distribution\Continuous;

use MathPHP\Probability\Distribution\Continuous\Laplace;

class LaplaceTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test         pdf
     * @dataProvider dataProviderForPdf
     * @param        float $x
     * @param        float $μ
     * @param        float $b
     * @param        float $expected_pdf
     */
    public function testPdf(float $x, float $μ, float $b, float $expected_pdf)
    {
        // Given
        $laplace = new Laplace($μ, $b);

        // When
        $pdf = $laplace->pdf($x);

        // Then
        $this->assertEqualsWithDelta($expected_pdf, $pdf, 0.000001);
    }

    /**
     * @return array [x, μ, b, pdf]
     */
    public function dataProviderForPdf(): array
    {
        return [
            [1, 0, 1, 0.1839397206],
            [1.1, 0, 1, 0.1664355418],
            [1.2, 0, 1, 0.150597106],
            [5, 0, 1, 0.0033689735],
            [1, 2, 1.4, 0.174836307],
            [1.1, 2, 1.4, 0.1877814373],
            [2.9, 2, 1.4, 0.1877814373],
        ];
    }

    /**
     * @test         cdf
     * @dataProvider dataProviderForCdf
     * @param        float $x
     * @param        float $μ
     * @param        float $b
     * @param        float $expected_cdf
     */
    public function testCdf(float $x, float $μ, float $b, float $expected_cdf)
    {
        // Given
        $laplace = new Laplace($μ, $b);

        // When
        $cdf = $laplace->cdf($x);

        // Then
        $this->assertEqualsWithDelta($expected_cdf, $cdf, 0.000001);
    }

    /**
     * @return array [x, μ, b, cdf]
     */
    public function dataProviderForCdf(): array
    {
        return [
            [1, 0, 1, 0.8160602794],
            [1.1, 0, 1, 0.8335644582],
            [1.2, 0, 1, 0.849402894],
            [5, 0, 1, 0.9966310265],
            [1, 2, 1.4, 0.2447708298],
            [1.1, 2, 1.4, 0.2628940122],
            [2.9, 2, 1.4, 0.7371059878],
        ];
    }

    /**
     * @test     mean is always μ
     */
    public function testMean()
    {
        foreach (\range(-5, 5) as $μ) {
            foreach (\range(1, 3) as $b) {
                // Given
                $laplace = new Laplace($μ, $b);

                // When
                $mean = $laplace->mean();

                // Then
                $this->assertEquals($μ, $mean);
            }
        }
    }

    /**
     * @test     median is always μ
     */
    public function testMedian()
    {
        foreach (\range(-5, 5) as $μ) {
            foreach (\range(1, 3) as $b) {
                // Given
                $laplace = new Laplace($μ, $b);

                // When
                $median = $laplace->median();

                // Then
                $this->assertEquals($μ, $median);
            }
        }
    }

    /**
     * @test     mode is always μ
     */
    public function testMode()
    {
        foreach (\range(-5, 5) as $μ) {
            foreach (\range(1, 3) as $b) {
                // Given
                $laplace = new Laplace($μ, $b);

                // When
                $mode = $laplace->mode();

                // Then
                $this->assertEquals($μ, $mode);
            }
        }
    }

    /**
     * @test         variance
     * @dataProvider dataProviderForVariance
     * @param        float $μ
     * @param        float $b
     * @param        float $expected
     */
    public function testVariance(float $μ, float $b, float $expected)
    {
        // Given
        $laplace = new Laplace($μ, $b);

        // When
        $variance = $laplace->variance();

        // Then
        $this->assertEqualsWithDelta($expected, $variance, 0.000001);
    }

    /**
     * @return array [μ, b, variance]
     */
    public function dataProviderForVariance(): array
    {
        return [
            [1, 1, 2],
            [2, 1, 2],
            [3, 1, 2],
            [1, 2, 8],
            [2, 2, 8],
            [4, 3, 18],
        ];
    }

    /**
     * @test         inverse
     * @dataProvider dataProviderForInverse
     * @param        float $p
     * @param        float $μ
     * @param        float $b
     * @param        $expected_inverse
     */
    public function testInverse(float $p, float $μ, float $b, $expected_inverse)
    {
        // Given
        $laplace = new Laplace($μ, $b);

        // When
        $inverse = $laplace->inverse($p);

        // Then
        $this->assertEqualsWithDelta($expected_inverse, $inverse, 0.00001);
    }

    /**
     * @return array [p, μ, b, inverse]
     * Generated with R (rmutil) qlaplace(p, location, dispersion)
     */
    public function dataProviderForInverse(): array
    {
        return [
            [0, 0, 1, -\INF],
            [0.1, 0, 1, -1.609438],
            [0.3, 0, 1, -0.5108256],
            [0.5, 0, 1, 0],
            [0.7, 0, 1, 0.5108256],
            [0.9, 0, 1, 1.609438],
            [1, 0, 1, \INF],

            [0, 1, 1, -\INF],
            [0.1, 1, 1, -0.6094379],
            [0.2, 1, 1, 0.08370927],
            [0.3, 1, 1, 0.4891744],
            [0.5, 1, 1, 1],
            [0.7, 1, 1, 1.510826],
            [0.9, 1, 1, 2.609438],
            [1, 1, 1, \INF],

            [0, -1, 1, -\INF],
            [0.1, -1, 1, -2.609438],
            [0.3, -1, 1, -1.510826],
            [0.5, -1, 1, -1],
            [0.7, -1, 1, -0.4891744],
            [0.9, -1, 1, 0.6094379],
            [1, -1, 1, \INF],

            [0, 2, 4, -\INF],
            [0.1, 2, 4, -4.437752],
            [0.3, 2, 4, -0.0433025],
            [0.5, 2, 4, 2],
            [0.7, 2, 4, 4.043302],
            [0.9, 2, 4, 8.437752],
            [1, 2, 4, \INF],

            [0, 13, 9, -\INF],
            [0.1, 13, 9, -1.484941],
            [0.3, 13, 9, 8.402569],
            [0.5, 13, 9, 13],
            [0.7, 13, 9, 17.59743],
            [0.9, 13, 9, 27.48494],
            [1, 13, 9, \INF],
        ];
    }

    /**
     * @test rand
     */
    public function testRand()
    {
        foreach (\range(-3, 3) as $μ) {
            foreach (\range(1, 3) as $b) {
                // Given
                $laplace = new Laplace($μ, $b);

                // When
                $random = $laplace->rand();

                // Then
                $this->assertTrue(\is_numeric($random));
            }
        }
    }
}
