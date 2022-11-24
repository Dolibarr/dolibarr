<?php

namespace MathPHP\Tests\Probability\Distribution\Discrete;

use MathPHP\Probability\Distribution\Discrete\Hypergeometric;

class HypergeometricTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test         pmf returns expected probability
     * @dataProvider dataProviderForPmf
     * @param  int   $N population size
     * @param  int   $K number of success states in the population
     * @param  int   $n number of draws
     * @param  int   $k number of observed successes
     * @param  float $expectedPmf
     */
    public function testPmf(int $N, int $K, int $n, int $k, float $expectedPmf)
    {
        // Given
        $hypergeometric = new Hypergeometric($N, $K, $n);

        // When
        $pmf = $hypergeometric->pmf($k);

        // Then
        $this->assertEqualsWithDelta($expectedPmf, $pmf, 0.0000001);
    }

    /**
     * Test data made with: http://stattrek.com/m/online-calculator/hypergeometric.aspx
     * @return array
     */
    public function dataProviderForPmf(): array
    {
        return [
            [50, 5, 10, 4, 0.00396458305801507],
            [50, 5, 10, 5, 0.000118937491740452],
            [100, 80, 50, 40, 0.196871217706549],
            [100, 80, 50, 35, 0.00889760379503624],
            [48, 6, 15, 2, 0.350128003786331],
            [48, 6, 15, 0, 0.0902552187538097],
            [48, 6, 15, 6, 0.000407855201543217],
            [100, 30, 20, 5, 0.19182559242904654583],
        ];
    }

    /**
     * @test         cdf returns expected probability
     * @dataProvider dataProviderForCdf
     * @param  int   $N population size
     * @param  int   $K number of success states in the population
     * @param  int   $n number of draws
     * @param  int   $k number of observed successes
     * @param  float $expectedCdf
     */
    public function testCdf(int $N, int $K, int $n, int $k, float $expectedCdf)
    {
        // Given
        $hypergeometric = new Hypergeometric($N, $K, $n);

        // When
        $cdf = $hypergeometric->cdf($k);

        // Then
        $this->assertEqualsWithDelta($expectedCdf, $cdf, 0.0000001);
    }

    /**
     * Test data made with: http://stattrek.com/m/online-calculator/hypergeometric.aspx
     * @return array
     */
    public function dataProviderForCdf(): array
    {
        return [
            [50, 5, 10, 4, 0.000118937],
            [100, 80, 50, 40, 0.401564391],
            [100, 80, 50, 35, 0.988582509],
            [48, 6, 15, 2, 0.269510717],
            [48, 6, 15, 0, 0.909744781],
            [100, 30, 20, 5, 0.599011207],
        ];
    }

    /**
     * @test         mean returns expected average
     * @dataProvider dataProviderForMean
     * @param  int   $N population size
     * @param  int   $K number of success states in the population
     * @param  int   $n number of draws
     * @param  float $μ
     */
    public function testMean(int $N, int $K, int $n, float $μ)
    {
        // Given
        $hypergeometric = new Hypergeometric($N, $K, $n);

        // When
        $mean = $hypergeometric->mean();

        // Then
        $this->assertEqualsWithDelta($μ, $mean, 0.0000001);
    }

    /**
     * Test data made with: http://keisan.casio.com/exec/system/1180573201
     * @return array
     */
    public function dataProviderForMean(): array
    {
        return [
            [50, 5, 10, 1],
            [100, 80, 50, 40],
            [48, 6, 15, 1.875],
            [100, 30, 20, 6],
        ];
    }

    /**
     * @test         mode
     * @dataProvider dataProviderForMode
     * @param  int   $N population size
     * @param  int   $K number of success states in the population
     * @param  int   $n number of draws
     * @param  array $expectedMode
     */
    public function testMode(int $N, int $K, int $n, array $expectedMode)
    {
        // Given
        $hypergeometric = new Hypergeometric($N, $K, $n);

        // When
        $mode = $hypergeometric->mode();

        // Then
        $this->assertEqualsWithDelta($expectedMode, $mode, 0.0000001);
    }

    /**
     * @return array [N, K, n, mode]
     */
    public function dataProviderForMode(): array
    {
        return [
            [50, 5, 10, [1, 1]],
            [100, 80, 50, [40, 40]],
            [48, 6, 15, [2, 2]],
            [100, 30, 20, [6, 6]],
        ];
    }

    /**
     * @test         variance
     * @dataProvider dataProviderForVariance
     * @param  int   $N population size
     * @param  int   $K number of success states in the population
     * @param  int   $n number of draws
     * @param  float $σ²
     */
    public function testVariance(int $N, int $K, int $n, float $σ²)
    {
        // Given
        $hypergeometric = new Hypergeometric($N, $K, $n);

        // When
        $variance = $hypergeometric->variance();

        // Then
        $this->assertEqualsWithDelta($σ², $variance, 0.0000001);
    }

    /**
     * @return array [N, K, n, σ²]
     */
    public function dataProviderForVariance(): array
    {
        return [
            [50, 5, 10, 0.73469387755102],
            [100, 80, 50, 4.040404040404],
        ];
    }
}
