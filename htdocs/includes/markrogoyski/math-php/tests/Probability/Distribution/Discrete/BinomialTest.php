<?php

namespace MathPHP\Tests\Probability\Distribution\Discrete;

use MathPHP\Probability\Distribution\Discrete\Binomial;

class BinomialTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test         pmf
     * @dataProvider dataProviderForPmf
     * @param        int $n
     * @param        int $r
     * @param        float $p
     * @param        float $expectedPmf
     */
    public function testPmf(int $n, int $r, float $p, float $expectedPmf)
    {
        // Given
        $binomial = new Binomial($n, $p);

        // When
        $pdf = $binomial->pmf($r);

        // Then
        $this->assertEqualsWithDelta($expectedPmf, $pdf, 0.0000001);
    }

    /**
     * @return array [n, r, p, pmf]
     * Data created with R dbinom(x, r, p)
     */
    public function dataProviderForPmf(): array
    {
        return [
            [2, 1, 0.5, 0.5],
            [2, 1, 0.4, 0.48],
            [6, 2, 0.7, 0.059535],
            [8, 7, 0.83, 0.3690503],
            [10, 5, 0.85, 0.008490856],
            [50, 48, 0.97, 0.2555182],
            [5, 4, 1, 0.0],
            [12, 4, 0.2, 0.1328756],
            [1000, 600, 0.65, 0.000117808],
            [700, 500, 0.75, 0.003307635],
            [600, 400, 0.75, 1.026813e-06],
            [400, 200, 0.4, 1.134711e-05],
            [300, 100, 0.4, 0.002852069],
            [200, 30, 0.14, 0.07261467],
            [190, 95, 0.56, 0.0145733],
            [175, 165, 0.97, 0.02215201],
            [150, 65, 0.36, 0.01190476],
            [145, 129, 0.87, 0.0811693],
            [100, 35, 0.36, 0.08160685],
        ];
    }

    /**
     * @test         cdf
     * @dataProvider dataProviderForCdf
     * @param        int $n
     * @param        int $r
     * @param        float $p
     * @param        float $expectedCdf
     */
    public function testCdf(int $n, int $r, float $p, float $expectedCdf)
    {
        // Given
        $binomial = new Binomial($n, $p);

        // When
        $cdf = $binomial->cdf($r);

        // Then
        $this->assertEqualsWithDelta($expectedCdf, $cdf, 0.0000001);
    }

    /**
     * @return array [n, r, p, cdf]
     * Data created with R pbinom(x, n, p)
     */
    public function dataProviderForCdf(): array
    {
        return [
            [2, 1, 0.5, 0.75],
            [2, 1, 0.4, 0.84],
            [6, 2, 0.7, 0.07047],
            [8, 7, 0.83, 0.7747708],
            [10, 5, 0.85, 0.009874091],
            [50, 48, 0.97, 0.4447201],
            [5, 4, 1, 0.0],
            [12, 4, 0.2, 0.9274445],
            [1000, 600, 0.65, 0.0005712963],
            [700, 500, 0.75, 0.01723671],
            [600, 400, 0.75, 2.971822e-06],
            [400, 200, 0.4, 0.9999788],
            [300, 100, 0.4, 0.01021691],
            [200, 30, 0.14, 0.701047],
            [190, 95, 0.56, 0.05595475],
            [175, 165, 0.97, 0.03934616],
            [150, 65, 0.36, 0.9736608],
            [145, 129, 0.87, 0.7932707],
            [100, 35, 0.36, 0.4623937],
        ];
    }

    /**
     * @test         mean
     * @dataProvider dataProviderForMean
     * @param        int   $n
     * @param        float $p
     * @param        float $μ
     */
    public function testMean(int $n, float $p, float $μ)
    {
        // Given
        $binomial = new Binomial($n, $p);

        // When
        $mean = $binomial->mean();

        // Then
        $this->assertEqualsWithDelta($μ, $mean, 0.000001);
    }

    /**
     * @return array
     */
    public function dataProviderForMean(): array
    {
        return [
            [0, 0, 0],
            [0, 1, 0],
            [5, 0, 0],
            [1, 1, 1],
            [1, 0.5, 0.5],
            [10, 0.2, 2],
        ];
    }

    /**
     * @test         variance
     * @dataProvider dataProviderForVariance
     * @param        int   $n
     * @param        float $p
     * @param        float $σ²
     */
    public function testVariance(int $n, float $p, float $σ²)
    {
        // Given
        $binomial = new Binomial($n, $p);

        // When
        $variance = $binomial->variance();

        // Then
        $this->assertEqualsWithDelta($σ², $variance, 0.000001);
    }

    /**
     * @return array
     */
    public function dataProviderForVariance(): array
    {
        return [
            [0, 0, 0],
            [0, 1, 0],
            [5, 0, 0],
            [1, 1, 0],
            [1, 0.5, .25],
            [5, 0.4, 1.2],
            [10, 0.2, 1.6],
        ];
    }
}
