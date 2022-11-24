<?php

namespace MathPHP\Tests\Probability\Distribution\Continuous;

use MathPHP\Probability\Distribution\Continuous\Pareto;

class ParetoTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test         pdf
     * @dataProvider dataProviderForPdf
     * @param        float $x
     * @param        float $a
     * @param        float $b
     * @param        float $expected_pdf
     */
    public function testPdf(float $x, float $a, float $b, float $expected_pdf)
    {
        // Given
        $pareto = new Pareto($a, $b);

        // When
        $pdf = $pareto->pdf($x);

        // Then
        $this->assertEqualsWithDelta($expected_pdf, $pdf, 0.00001);
    }

    /**
     * @return array
     * Generated with R dpareto(x, b a) from package EnvStats
     */
    public function dataProviderForPdf(): array
    {
        return [
            [0.1, 1, 1, 0],
            [1, 1, 1, 1],
            [2, 1, 1, 0.25],
            [3, 1, 1, 0.1111111],
            [4, 1, 1, 0.0625],
            [5, 1, 1, 0.04],
            [10, 1, 1, 0.01],

            [0.1, 2, 1, 0],
            [1, 2, 1, 2],
            [2, 2, 1, 0.25],
            [3, 2, 1, 0.07407407],
            [4, 2, 1, 0.03125],
            [5, 2, 1, 0.016],
            [10, 2, 1, 0.002],

            [0.1, 2, 1, 0],
            [1, 1, 2, 0],
            [2, 1, 2, 0.5],
            [3, 1, 2, 0.2222222],
            [4, 1, 2, 0.125],
            [5, 1, 2, 0.08],
            [10, 1, 2, 0.02],

            [0.1, 2, 2, 0],
            [1, 2, 2, 0],
            [2, 2, 2, 1],
            [3, 2, 2, 0.2962963],
            [4, 2, 2, 0.125],
            [5, 2, 2, 0.064],
            [10, 2, 2, 0.008],

            [4, 8, 2, 0.0078125],
            [5, 8, 2, 0.001048576],
            [9, 4, 5, 0.04233772],
        ];
    }

    /**
     * @test         cdf
     * @dataProvider dataProviderForCdf
     * @param        float $x
     * @param        float $a
     * @param        float $b
     * @param        float $expected_cdf
     */
    public function testCdf(float $x, float $a, float $b, float $expected_cdf)
    {
        // Given
        $pareto = new Pareto($a, $b);

        // When
        $cdf = $pareto->cdf($x);

        // Then
        $this->assertEqualsWithDelta($expected_cdf, $cdf, 0.00001);
    }

    /**
     * @return array
     * Generated with R ppareto(x, b a) from package EnvStats
     */
    public function dataProviderForCdf(): array
    {
        return [
            [0.1, 1, 1, 0],
            [1, 1, 1, 0],
            [2, 1, 1, 0.5],
            [3, 1, 1, 0.6666667],
            [4, 1, 1, 0.75],
            [5, 1, 1, 0.8],
            [10, 1, 1, 0.9],

            [0.1, 2, 1, 0],
            [1, 2, 1, 0],
            [2, 2, 1, 0.75],
            [3, 2, 1, 0.8888889],
            [4, 2, 1, 0.9375],
            [5, 2, 1, 0.96],
            [10, 2, 1, 0.99],

            [0.1, 2, 1, 0],
            [1, 1, 2, 0],
            [2, 1, 2, 0],
            [3, 1, 2, 0.3333333],
            [4, 1, 2, 0.5],
            [5, 1, 2, 0.6],
            [10, 1, 2, 0.8],

            [0.1, 2, 2, 0],
            [1, 2, 2, 0],
            [2, 2, 2, 0],
            [3, 2, 2, 0.5555556],
            [4, 2, 2, 0.75],
            [5, 2, 2, 0.84],
            [10, 2, 2, 0.96],

            [4, 8, 2, 0.9960938],
            [5, 8, 2, 0.9993446],
            [9, 4, 5, 0.9047401],
        ];
    }

    /**
     * @test         inverse
     * @dataProvider dataProviderForInverse
     * @param        float $p
     * @param        float $a
     * @param        float $b
     * @param        float $expected_inverse
     */
    public function testInverse(float $p, float $a, float $b, float $expected_inverse)
    {
        // Given
        $pareto = new Pareto($a, $b);

        // When
        $inverse = $pareto->inverse($p);

        // Then
        $this->assertEqualsWithDelta($expected_inverse, $inverse, 0.00001);
    }

    /**
     * @return array
     * Generated with https://solvemymath.com/online_math_calculator/statistics/continuous_distributions/pareto/quantile_pareto.php
     */
    public function dataProviderForInverse(): array
    {
        return [
            [0, 1, 1, -\INF],
            [0.1, 1, 1, 1.1111111],
            [0.2, 1, 1, 1.25],
            [0.3, 1, 1, 1.42857],
            [0.4, 1, 1, 1.666666667],
            [0.5, 1, 1, 2],
            [0.6, 1, 1, 2.5],
            [0.7, 1, 1, 3.3333333],
            [0.8, 1, 1, 5],
            [0.9, 1, 1, 10],
            [1, 1, 1, \INF],

            [0, 2, 2, -\INF],
            [0.1, 2, 2, 2.108185],
            [0.2, 2, 2, 2.2360679],
            [0.3, 2, 2, 2.390457218],
            [0.4, 2, 2, 2.5819888974],
            [0.5, 2, 2, 2.8284271247],
            [0.6, 2, 2, 3.1622776601],
            [0.7, 2, 2, 3.6514837167],
            [0.8, 2, 2, 4.4721359549],
            [0.9, 2, 2, 6.3245553203],
            [1, 2, 2, \INF],

            [0, 4, 6, -\INF],
            [0.1, 4, 6, 6.1601405764],
            [0.2, 4, 6, 6.3442275806],
            [0.5, 4, 6, 7.1352426900],
            [0.9, 4, 6, 10.669676460],
            [1, 4, 6, \INF],
        ];
    }

    /**
     * @test         inverse of CDF is x
     * @dataProvider dataProviderForInverseOfCdf
     * @param        float $x
     * @param        float $a
     * @param        float $b
     */
    public function testInverseOfCdf(float $x, float $a, float $b)
    {
        // Given
        $pareto = new Pareto($a, $b);
        $cdf     = $pareto->cdf($x);

        // When
        $inverse_of_cdf = $pareto->inverse($cdf);

        // Then
        $this->assertEqualsWithDelta($x, $inverse_of_cdf, 0.000001);
    }

    /**
     * @return array
     */
    public function dataProviderForInverseOfCdf(): array
    {
        return [
            [2, 1, 1, 0.5],
            [3, 1, 1, 0.6666667],
            [4, 1, 1, 0.75],
            [5, 1, 1, 0.8],
            [10, 1, 1, 0.9],

            [2, 2, 1, 0.75],
            [3, 2, 1, 0.8888889],
            [4, 2, 1, 0.9375],
            [5, 2, 1, 0.96],
            [10, 2, 1, 0.99],

            [3, 1, 2, 0.3333333],
            [4, 1, 2, 0.5],
            [5, 1, 2, 0.6],
            [10, 1, 2, 0.8],

            [3, 2, 2, 0.5555556],
            [4, 2, 2, 0.75],
            [5, 2, 2, 0.84],
            [10, 2, 2, 0.96],

            [4, 8, 2, 0.9960938],
            [5, 8, 2, 0.9993446],
            [9, 4, 5, 0.9047401],
        ];
    }

    /**
     * @test         mean
     * @dataProvider dataProviderForMean
     * @param        float $a
     * @param        float $b
     * @param        float $μ
     */
    public function testMean(float $a, float $b, float $μ)
    {
        // Given
        $pareto = new Pareto($a, $b);

        // When
        $mean = $pareto->mean();

        // Then
        $this->assertEqualsWithDelta($μ, $mean, 0.0001);
    }

    /**
     * @return array [a, b, μ]
     */
    public function dataProviderForMean(): array
    {
        return [
            [1, 2, \INF],
            [0.4, 2, \INF],
            [0.001, 2, \INF],
            [2, 1, 2],
            [3, 1, 1.5],
            [3, 2, 3],
        ];
    }

    /**
     * @test         median
     * @dataProvider dataProviderForMedian
     * @param        float $a
     * @param        float $b
     * @param        float $expected_median
     */
    public function testMedian(float $a, float $b, float $expected_median)
    {
        // Given
        $pareto = new Pareto($a, $b);

        // When
        $median = $pareto->median();

        // Then
        $this->assertEqualsWithDelta($expected_median, $median, 0.0000001);
    }

    /**
     * @return array [a, b, median]
     */
    public function dataProviderForMedian(): array
    {
        return [
            [1, 1, 2],
            [1, 2, 1.414213562373095],
            [2, 1, 4],
            [4, 5, 4.59479341998814],
        ];
    }

    /**
     * @test         mode
     * @dataProvider dataProviderForMode
     * @param        float $a
     * @param        float $b
     * @param        float $expected
     */
    public function testMode(float $a, float $b, float $expected)
    {
        // Given
        $pareto = new Pareto($a, $b);

        // When
        $mode = $pareto->mode();

        // Then
        $this->assertEqualsWithDelta($expected, $mode, 0.0000001);
    }

    /**
     * @return array [a, b, mode]
     */
    public function dataProviderForMode(): array
    {
        return [
            [1, 1, 1],
            [2, 2, 2],
            [2, 1, 2],
            [4, 5, 4],
        ];
    }

    /**
     * @test         variance
     * @dataProvider dataProviderForVariance
     * @param        float $a
     * @param        float $b
     * @param        float $expected
     */
    public function testVariance(float $a, float $b, float $expected)
    {
        // Given
        $pareto = new Pareto($a, $b);

        // When
        $variance = $pareto->variance();

        // Then
        $this->assertEqualsWithDelta($expected, $variance, 0.0000001);
    }

    /**
     * @return array [a, b, σ²]
     */
    public function dataProviderForVariance(): array
    {
        return [
            [1, 1, \INF],
            [2, 2, \INF],
            [2, 1, \INF],
            [3, 1, 0.75],
            [3, 2, 3],
            [4, 3, 2],
        ];
    }
}
