<?php

namespace MathPHP\Tests\Probability\Distribution\Discrete;

use MathPHP\Probability\Distribution\Discrete\Geometric;

class GeometricTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test         pmf
     * @dataProvider dataProviderForPmf
     * @param        int   $k
     * @param        float $p
     * @param        float $expectedPmf
     */
    public function testPmf(int $k, float $p, float $expectedPmf)
    {
        // Given
        $geometric = new Geometric($p);

        // When
        $pmf = $geometric->pmf($k);

        // Then
        $this->assertEqualsWithDelta($expectedPmf, $pmf, 0.001);
    }

    /**
     * @return array
     */
    public function dataProviderForPmf(): array
    {
        return [
            [ 5, 0.1, 0.059049 ],
            [ 5, 0.2, 0.065536 ],
            [ 1, 0.4, 0.24 ],
            [ 2, 0.4, 0.144 ],
            [ 3, 0.4, 0.0864 ],
            [ 5, 0.5, 0.015625 ],
            [ 5, 0.09, 0.056162893059 ],
            [ 1, 1, 0 ],
            [ 2, 1, 0 ],
        ];
    }

    /**
     * @test         cdf
     * @dataProvider dataProviderForCdf
     * @param        int   $k
     * @param        float $p
     * @param        float $expectedCdf
     */
    public function testCdf(int $k, float $p, float $expectedCdf)
    {
        // Given
        $geometric = new Geometric($p);

        // When
        $cdf = $geometric->cdf($k);

        // Then
        $this->assertEqualsWithDelta($expectedCdf, $cdf, 0.001);
    }

    /**
     * @return array
     */
    public function dataProviderForCdf(): array
    {
        return [
            [ 5, 0.1, 0.468559 ],
            [ 5, 0.2, 0.737856 ],
            [ 1, 0.4, 0.64  ],
            [ 2, 0.4, 0.784 ],
            [ 3, 0.4, 0.8704 ],
            [ 5, 0.5, 0.984375 ],
            [ 5, 0.09, 0.432130747959 ],
            [ 1, 1, 1 ],
            [ 2, 1, 1 ],
        ];
    }

    /**
     * @test         mean
     * @dataProvider dataProviderForMean
     * @param        float $p
     * @param        float $μ
     */
    public function testMean(float $p, float $μ)
    {
        // Given
        $geometric = new Geometric($p);

        // When
        $mean = $geometric->mean();

        // Then
        $this->assertEqualsWithDelta($μ, $mean, 0.000001);
    }

    /**
     * @return array [p, μ]
     */
    public function dataProviderForMean(): array
    {
        return [
            [0.1, 9],
            [0.2, 4],
            [0.5, 1],
            [0.8, 0.25],
            [0.9, 0.11111111111111],
            [1, 0],
        ];
    }

    /**
     * @test         median
     * @dataProvider dataProviderForMedian
     * @param        float $p
     * @param        float $expected
     */
    public function testMedian(float $p, float $expected)
    {
        // Given
        $geometric = new Geometric($p);

        // When
        $median = $geometric->median();

        // Then
        $this->assertEqualsWithDelta($expected, $median, 0.000001);
    }

    /**
     * @return array [p, median]
     */
    public function dataProviderForMedian(): array
    {
        return [
            [0.1, 6],
            [0.2, 3],
            [0.5, 0],
            [0.8, 0],
            [0.9, 0],
            [1, -1],
        ];
    }

    /**
     * @test         mode
     * @dataProvider dataProviderForMode
     * @param        float $p
     * @param        float $expected
     */
    public function testMode(float $p, float $expected)
    {
        // Given
        $geometric = new Geometric($p);

        // When
        $mode = $geometric->mode();

        // Then
        $this->assertEqualsWithDelta($expected, $mode, 0.000001);
    }

    /**
     * @return array [p, mode]
     */
    public function dataProviderForMode(): array
    {
        return [
            [0.1, 0],
            [0.2, 0],
            [0.5, 0],
            [0.8, 0],
            [0.9, 0],
            [1, 0],
        ];
    }

    /**
     * @test         variance
     * @dataProvider dataProviderForVariance
     * @param        float $p
     * @param        float $σ²
     */
    public function testVariance(float $p, float $σ²)
    {
        // Given
        $geometric = new Geometric($p);

        // When
        $mode = $geometric->variance();

        // Then
        $this->assertEqualsWithDelta($σ², $mode, 0.000001);
    }

    /**
     * @return array [p, variance]
     */
    public function dataProviderForVariance(): array
    {
        return [
            [0.1, 90],
            [0.2, 20],
            [0.5, 2],
            [0.8, 0.3125],
            [0.9, 0.12345679012346],
            [1, 0],
        ];
    }
}
