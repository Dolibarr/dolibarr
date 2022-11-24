<?php

namespace MathPHP\Tests\Statistics;

use MathPHP\Statistics\Experiment;
use MathPHP\Exception;

class ExperimentTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test         riskRatio
     * @dataProvider dataProviderForRiskRatio
     * @param        int   $a
     * @param        int   $b
     * @param        int   $c
     * @param        int   $d
     * @param        array $expected
     */
    public function testRiskRatio(int $a, int $b, int $c, int $d, array $expected)
    {
        // When
        $riskRation = Experiment::riskRatio($a, $b, $c, $d);

        // Then
        $this->assertEqualsWithDelta($expected['RR'], $riskRation['RR'], 0.001);
        $this->assertEqualsWithDelta($expected['ci_lower_bound'], $riskRation['ci_lower_bound'], 0.001);
        $this->assertEqualsWithDelta($expected['ci_upper_bound'], $riskRation['ci_upper_bound'], 0.001);
        $this->assertEqualsWithDelta($expected['p'], $riskRation['p'], 0.0001);
    }

    /**
     * @return array [a, b, c, d, rr]
     */
    public function dataProviderForRiskRatio(): array
    {
        return [
            [20, 80, 1, 99, ['RR' => 20, 'ci_lower_bound' => 2.7361, 'ci_upper_bound' => 146.1912, 'p' => 0.0032]],
            [100, 200, 20, 400, ['RR' => 7, 'ci_lower_bound' => 4.4337, 'ci_upper_bound' => 11.0516, 'p' => 0.0001]],
            [43, 26, 54, 654, ['RR' => 8.1707, 'ci_lower_bound' => 5.9614, 'ci_upper_bound' => 11.1987, 'p' => 0.0001]],
            [53, 58, 11, 40, ['RR' => 2.2138, 'ci_lower_bound' => 1.2666, 'ci_upper_bound' => 3.8693, 'p' => 0.0053]],
            [59, 33, 17, 44, ['RR' => 2.3012, 'ci_lower_bound' => 1.4944, 'ci_upper_bound' => 3.5434, 'p' => 0.0002]],
            [28, 129, 4, 133, ['RR' => 6.1083, 'ci_lower_bound' => 2.1976, 'ci_upper_bound' => 16.9784, 'p' => 0.0005]],
            [1000, 49000, 100, 49900, ['RR' => 10, 'ci_lower_bound' => 8.1449, 'ci_upper_bound' => 12.2776, 'p' => 0.0001]],
        ];
    }

    /**
     * @test         oddsRatio
     * @dataProvider dataProviderForOddsRatio
     * @param        int   $a
     * @param        int   $b
     * @param        int   $c
     * @param        int   $d
     * @param        array $expected
     */
    public function testOddsRatio(int $a, int $b, int $c, int $d, array $expected)
    {
        // When
        $oddsRatio = Experiment::oddsRatio($a, $b, $c, $d);

        // Then
        $this->assertEqualsWithDelta($expected['OR'], $oddsRatio['OR'], 0.001);
        $this->assertEqualsWithDelta($expected['ci_lower_bound'], $oddsRatio['ci_lower_bound'], 0.001);
        $this->assertEqualsWithDelta($expected['ci_upper_bound'], $oddsRatio['ci_upper_bound'], 0.001);
        $this->assertEqualsWithDelta($expected['p'], $oddsRatio['p'], 0.0001);
    }

    /**
     * @return array [a, b, c, d, or]
     */
    public function dataProviderForOddsRatio(): array
    {
        return [
            [20, 80, 1, 99, ['OR' => 24.7500, 'ci_lower_bound' => 3.2509, 'ci_upper_bound' => 188.4303, 'p' => 0.0019]],
            [100, 200, 20, 400, ['OR' => 10.0000, 'ci_lower_bound' => 6.0096, 'ci_upper_bound' => 16.6400, 'p' => 0.0001]],
            [43, 26, 54, 654, ['OR' => 20.0299, 'ci_lower_bound' => 11.4361, 'ci_upper_bound' => 35.0817, 'p' => 0.0001]],
            [53, 58, 11, 40, ['OR' => 3.3229, 'ci_lower_bound' => 1.5475, 'ci_upper_bound' => 7.1351, 'p' => 0.0021]],
            [59, 33, 17, 44, ['OR' => 4.6275, 'ci_lower_bound' => 2.2901, 'ci_upper_bound' => 9.3505, 'p' => 0.0001]],
            [28, 129, 4, 133, ['OR' => 7.2171, 'ci_lower_bound' => 2.4624, 'ci_upper_bound' => 21.1522, 'p' => 0.0003]],
            [1000, 49000, 100, 49900, ['OR' => 10.1837, 'ci_lower_bound' => 8.2883, 'ci_upper_bound' => 12.5125, 'p' => 0.0001]],
        ];
    }

    /**
     * @test         likelihoodRatio
     * @dataProvider dataProviderForLikelihoodRatio
     * @param        int   $a
     * @param        int   $b
     * @param        int   $c
     * @param        int   $d
     * @param        array $expected
     */
    public function testLikelihoodRatio(int $a, int $b, int $c, int $d, array $expected)
    {
        // When
        $likelihoodRatio = Experiment::likelihoodRatio($a, $b, $c, $d);

        // Then
        $this->assertEqualsWithDelta($expected['LL+'], $likelihoodRatio['LL+'], 0.001);
        $this->assertEqualsWithDelta($expected['LL-'], $likelihoodRatio['LL-'], 0.001);
    }

    /**
     * @return array [a, b, c, d, ll]
     */
    public function dataProviderForLikelihoodRatio(): array
    {
        return [
            [20, 180, 10, 1820, ['LL+' => 7.4074, 'LL-' => 0.3663]],
            [20, 80, 1, 99, ['LL+' => 2.131, 'LL-' => 0.0861]],
            [100, 200, 20, 400, ['LL+' => 2.5, 'LL-' => 0.25]],
            [43, 26, 54, 654, ['LL+' => 11.594, 'LL-' => 0.5788]],
            [53, 58, 11, 40, ['LL+' => 1.3992, 'LL-' => 0.4211]],
            [59, 33, 17, 44, ['LL+' => 1.8114, 'LL-' => 0.3914]],
            [28, 129, 4, 133, ['LL+' => 1.7771, 'LL-' => 0.2462]],
            [1000, 49000, 100, 49900, ['LL+' => 1.8349, 'LL-' => 0.1802]],
        ];
    }

    /**
     * @test         likelihoodRatioSS
     * @dataProvider dataProviderForLikelihoodRatioSS
     * @param        float $sensitivity
     * @param        float $specificity
     * @param        array $expected
     */
    public function testLikelihoodRatioSS(float $sensitivity, float $specificity, array $expected)
    {
        // When
        $likelihoodRatio = Experiment::likelihoodRatioSS($sensitivity, $specificity);

        // Then
        $this->assertEqualsWithDelta($expected['LL+'], $likelihoodRatio['LL+'], 0.001);
        $this->assertEqualsWithDelta($expected['LL-'], $likelihoodRatio['LL-'], 0.001);
    }

    /**
     * @return array [sensitivity, specificity]
     */
    public function dataProviderForLikelihoodRatioSS(): array
    {
        return [
            [0.67, 0.91, ['LL+' => 7.4444, 'LL-' => 0.3626]],
            [0.90, 0.85, ['LL+' => 6, 'LL-' => 0.1176]]
        ];
    }

    /**
     * @test likelihoodRatioSS exception if sensitivity or specificity are > 1.0
     */
    public function testLikelihoodRatioSSException()
    {
        // Given
        $sensitivity = 1.2;
        $specificity = 1.5;

        // Then
        $this->expectException(Exception\OutOfBoundsException::class);

        // When
        Experiment::likelihoodRatioSS($sensitivity, $specificity);
    }
}
