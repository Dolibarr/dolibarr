<?php

namespace MathPHP\Tests\Probability\Distribution\Discrete;

use MathPHP\Probability\Distribution\Discrete\ShiftedGeometric;

class ShiftedGeometricTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test         pmf
     * @dataProvider dataProviderForPmf
     * @param        int $k
     * @param        float $p
     * @param        float $expectedPmf
     */
    public function testPmf(int $k, float $p, float $expectedPmf)
    {
        // Given
        $shiftedGeometric = new ShiftedGeometric($p);

        // When
        $pmf = $shiftedGeometric->pmf($k);

        // Then
        $this->assertEqualsWithDelta($expectedPmf, $pmf, 0.001);
    }

    /**
     * @return array
     */
    public function dataProviderForPMF(): array
    {
        return [
            [5, 0.1, 0.065610],
            [5, 0.2, 0.081920],
            [1, 0.4, 0.400000],
            [2, 0.4, 0.240000],
            [3, 0.4, 0.144],
            [5, 0.5, 0.031512],
            [5, 0.09, 0.061717],
            [1, 1, 1],
            [2, 1, 0],
        ];
    }

    /**
     * @test         cdf
     * @dataProvider dataProviderForCdf
     * @param        int $k
     * @param        float $p
     * @param        float $expectedCdf
     */
    public function testCdf(int $k, float $p, float $expectedCdf)
    {
        // Given
        $shiftedGeometric = new ShiftedGeometric($p);

        // When
        $cdf = $shiftedGeometric->cdf($k);

        // Then
        $this->assertEqualsWithDelta($expectedCdf, $cdf, 0.001);
    }

    /**
     * @return array
     */
    public function dataProviderForCDF(): array
    {
        return [
            [5, 0.1, 0.40951],
            [5, 0.2, 0.67232],
            [1, 0.4, 0.4],
            [2, 0.4, 0.64],
            [3, 0.4, 0.784],
            [5, 0.5, 0.9688],
            [5, 0.09, 0.3759678549],
            [1, 1, 1],
            [2, 1, 1],
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
        $shiftedGeometric = new ShiftedGeometric($p);

        // When
        $mean = $shiftedGeometric->mean();

        // Then
        $this->assertEqualsWithDelta($μ, $mean, 0.000001);
    }

    /**
     * @return array [p, μ]
     */
    public function dataProviderForMean(): array
    {
        return [
            [0.1, 10],
            [0.2, 5],
            [0.5, 2],
            [0.8, 1.25],
            [0.9, 1.11111111111111],
            [1, 1],
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
        $shiftedGeometric = new ShiftedGeometric($p);

        // When
        $median = $shiftedGeometric->median();

        // Then
        $this->assertEqualsWithDelta($expected, $median, 0.000001);
    }

    /**
     * @return array [p, median]
     */
    public function dataProviderForMedian(): array
    {
        return [
            [0.1, 7],
            [0.2, 4],
            [0.5, 1],
            [0.8, 1],
            [0.9, 1],
            [1, 0],
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
        $shiftedGeometric = new ShiftedGeometric($p);

        // When
        $mode = $shiftedGeometric->mode();

        // Then
        $this->assertEqualsWithDelta($expected, $mode, 0.000001);
    }

    /**
     * @return array [p, mode]
     */
    public function dataProviderForMode(): array
    {
        return [
            [0.1, 1],
            [0.2, 1],
            [0.5, 1],
            [0.8, 1],
            [0.9, 1],
            [1, 1],
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
        $shiftedGeometric = new ShiftedGeometric($p);

        // When
        $mode = $shiftedGeometric->variance();

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
