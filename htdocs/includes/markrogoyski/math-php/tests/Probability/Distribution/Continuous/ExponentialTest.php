<?php

namespace MathPHP\Tests\Probability\Distribution\Continuous;

use MathPHP\Probability\Distribution\Continuous\Exponential;
use MathPHP\Exception\OutOfBoundsException;

class ExponentialTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test         pdf
     * @dataProvider dataProviderForPdf
     * @param        float $λ
     * @param        float $x
     * @param        float $expected_pdf
     */
    public function testPdf(float $λ, float $x, float $expected_pdf)
    {
        // Given
        $exponential = new Exponential($λ);

        // When
        $pdf = $exponential->pdf($x);

        // Then
        $this->assertEqualsWithDelta($expected_pdf, $pdf, 0.000001);
    }

    /**
     * @return array [λ, x, pdf]
     * Generated with R dexp(x, λ)
     */
    public function dataProviderForPdf(): array
    {
        return [
            [0.1, -100, 0],
            [0.1, -2, 0],
            [0.1, -1, 0],
            [0.1, 0, 0.1],
            [0.1, 0.1, 0.09900498],
            [0.1, 0.5, 0.09512294],
            [0.1, 1, 0.09048374],
            [0.1, 2, 0.08187308],
            [0.1, 3, 0.07408182],
            [0.1, 10, 0.03678794],
            [0.1, 50, 0.0006737947],

            [1, -100, 0],
            [1, -2, 0],
            [1, -1, 0],
            [1, 0, 1],
            [1, 0.1, 0.9048374],
            [1, 0.5, 0.6065307],
            [1, 1, 0.3678794],
            [1, 2, 0.1353353],
            [1, 3, 0.04978707],
            [1, 4, 0.01831564],

            [2, -100, 0],
            [2, -2, 0],
            [2, -1, 0],
            [2, 0, 2],
            [2, 0.1, 1.637462],
            [2, 0.5, 0.7357589],
            [2, 1, 0.2706706],
            [2, 2, 0.03663128],
            [2, 3, 0.004957504],
        ];
    }

    /**
     * @test         cdf
     * @dataProvider dataProviderForCdf
     * @param number $λ
     * @param number $x
     * @param number $expected_cdf
     */
    public function testCdf($λ, $x, $expected_cdf)
    {
        // Given
        $exponential = new Exponential($λ);

        // When
        $cdf = $exponential->cdf($x);

        // Then
        $this->assertEqualsWithDelta($expected_cdf, $cdf, 0.0000001);
    }

    /**
     * @return array [λ, x, cdf]
     * Generated with R pexp(x, λ)
     */
    public function dataProviderForCdf(): array
    {
        return [
            [0.1, -100, 0],
            [0.1, -2, 0],
            [0.1, -1, 0],
            [0.1, 0, 0],
            [0.1, 0.1, 0.009950166],
            [0.1, 0.5, 0.04877058],
            [0.1, 1, 0.09516258],
            [0.1, 2, 0.1812692],
            [0.1, 3, 0.2591818],
            [0.1, 10, 0.6321206],
            [0.1, 50, 0.9932621],

            [1, -100, 0],
            [1, -2, 0],
            [1, -1, 0],
            [1, 0, 0],
            [1, 0.1, 0.09516258],
            [1, 0.5, 0.3934693],
            [1, 1, 0.6321206],
            [1, 2, 0.8646647],
            [1, 3, 0.9502129],
            [1, 4, 0.9816844],

            [2, -100, 0],
            [2, -2, 0],
            [2, -1, 0],
            [2, 0, 0],
            [2, 0.1, 0.1812692],
            [2, 0.5, 0.6321206],
            [2, 1, 0.8646647],
            [2, 2, 0.9816844],
            [2, 3, 0.9975212],

            [1 / 3, 2, 0.4865829],
            [1 / 3, 4, 0.7364029],
            [1 / 5, 4, 0.550671],
        ];
    }

    /**
     * @test         mean
     * @dataProvider dataProviderForMean
     * @param        number $λ
     * @param        number $μ
     */
    public function testMean($λ, $μ)
    {
        // Given
        $exponential = new Exponential($λ);

        // When
        $mean = $exponential->mean();

        // then
        $this->assertEqualsWithDelta($μ, $mean, 0.0001);
    }

    /**
     * @return array [λ, μ]
     */
    public function dataProviderForMean(): array
    {
        return [
            [1, 1],
            [2, 0.5],
            [3, 0.33333],
            [4, 0.25],
        ];
    }

    /**
     * @test         median
     * @dataProvider dataProviderForMedian
     * @param        number $λ
     * @param        number $expectedMedian
     */
    public function testMedian($λ, $expectedMedian)
    {
        // Given
        $exponential = new Exponential($λ);

        // When
        $median = $exponential->median();

        // then
        $this->assertEqualsWithDelta($expectedMedian, $median, 0.0000001);
    }

    /**
     * @return array [λ, median]
     */
    public function dataProviderForMedian(): array
    {
        return [
            [1, 0.69314718055995],
            [2, 0.34657359027997],
            [3, 0.23104906018665],
            [4, 0.17328679513999],
        ];
    }

    /**
     * @test         mode
     * @dataProvider dataProviderForMedian
     * @param        number $λ
     */
    public function testMode($λ)
    {
        // Given
        $exponential = new Exponential($λ);

        // When
        $mode = $exponential->mode();

        // then
        $this->assertEquals(0, $mode);
    }

    /**
     * @test         variance
     * @dataProvider dataProviderForVariance
     * @param        number $λ
     * @param        number $expectedVariance
     */
    public function testVariance($λ, $expectedVariance)
    {
        // Given
        $exponential = new Exponential($λ);

        // When
        $variance = $exponential->variance();

        // then
        $this->assertEqualsWithDelta($expectedVariance, $variance, 0.0000001);
    }

    /**
     * @return array [λ, variance]
     */
    public function dataProviderForVariance(): array
    {
        return [
            [1, 1],
            [2, 0.25],
            [3, 0.111111111111111],
            [4, 0.0625],
        ];
    }

    /**
     * @test         inverse of cdf is x
     * @dataProvider dataProviderForInverse
     * @param        float $λ
     * @param        float $p
     * @param        float $expectedInverse
     * @throws       \Exception
     */
    public function testInverse(float $λ, float $p, float $expectedInverse)
    {
        // Given
        $exponential = new Exponential($λ);

        // When
        $inverse = $exponential->inverse($p);

        // Then
        $this->assertEqualsWithDelta($expectedInverse, $inverse, 0.00001);
    }

    /**
     * @test         inverse of cdf is original p
     * @dataProvider dataProviderForInverse
     * @param        float $λ
     * @param        float $p
     * @throws       \Exception
     */
    public function testInverseOfCdf(float $λ, float $p)
    {
        // Given
        $exponential = new Exponential($λ);
        $cdf = $exponential->cdf($p);

        // When
        $inverse_of_cdf = $exponential->inverse($cdf);

        // Then
        $this->assertEqualsWithDelta($p, $inverse_of_cdf, 0.000001);
    }

    /**
     * @return array [λ, p, cdf]
     * Generated with R (stats) qexp(p, λ)
     */
    public function dataProviderForInverse(): array
    {
        return [
            [0.1, 0, 0],
            [0.1, 0.1, 1.053605],
            [0.1, 0.3, 3.566749],
            [0.1, 0.5, 6.931472],
            [0.1, 0.7, 12.03973],
            [0.1, 0.9, 23.02585],
            [0.1, 1, \INF],

            [1, 0, 0],
            [1, 0.1, 0.1053605],
            [1, 0.3, 0.3566749],
            [1, 0.5, 0.6931472],
            [1, 0.7, 1.203973],
            [1, 0.9, 2.302585],
            [1, 1, \INF],

            [2, 0, 0],
            [2, 0.1, 0.05268026],
            [2, 0.3, 0.1783375],
            [2, 0.5, 0.3465736],
            [2, 0.7, 0.6019864],
            [2, 0.9, 1.151293],
            [2, 1, \INF],

            [1 / 3, 0, 0],
            [1 / 3, 0.1, 0.3160815],
            [1 / 3, 0.3, 1.070025],
            [1 / 3, 0.5, 2.079442],
            [1 / 3, 0.7, 3.611918],
            [1 / 3, 0.9, 6.907755],
            [1 / 3, 1, \INF],


            [4, 0, 0],
            [4, 0.1, 0.02634013],
            [4, 0.3, 0.08916874],
            [4, 0.5, 0.1732868],
            [4, 0.7, 0.3009932],
            [4, 0.9, 0.5756463],
            [4, 1, \INF],
        ];
    }

    /**
     * @test         inverse throws OutOfBounds exceptions for bad p values
     * @dataProvider dataProviderForInverseOutOfBoundsP
     * @param        float $p
     * @throws       \Exception
     */
    public function testInverseOutOfBoundsException(float $p)
    {
        // Given
        $λ           = 1;
        $exponential = new Exponential($λ);

        // Then
        $this->expectException(OutOfBoundsException::class);

        // When
        $exponential->inverse($p);
    }

    /**
     * @return array [p]
     */
    public function dataProviderForInverseOutOfBoundsP(): array
    {
        return [
            [-1],
            [-0.01],
            [1.01],
            [2],
        ];
    }

    /**
     * @test rand
     */
    public function testRand()
    {
        foreach (\range(1, 4) as $λ) {
            foreach (\range(1, 20) as $_) {
                // Given
                $exponential = new Exponential($λ);

                // When
                $random = $exponential->rand();

                // Then
                $this->assertTrue(\is_numeric($random));
            }
        }
    }
}
