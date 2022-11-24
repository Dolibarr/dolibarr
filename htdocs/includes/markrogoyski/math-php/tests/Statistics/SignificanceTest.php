<?php

namespace MathPHP\Tests\Statistics;

use MathPHP\Statistics\Average;
use MathPHP\Statistics\Descriptive;
use MathPHP\Statistics\Significance;
use MathPHP\Exception;

class SignificanceTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test         zScore table value
     * @dataProvider dataProviderForZScore
     * @param        float $μ
     * @param        float $σ
     * @param        float $M
     * @param        float $expected
     */
    public function testZScore(float $μ, float $σ, float $M, float $expected)
    {
        // When
        $z = Significance::zScore($M, $μ, $σ, Significance::Z_TABLE_VALUE);

        // Then
        $this->assertEqualsWithDelta($expected, $z, 0.001);
    }

    /**
     * @return array [μ, σ, M, z]
     */
    public function dataProviderForZScore(): array
    {
        return [
            [1, 1, 1, 0],
            [1, 1, 2, 1],
            [4, 0.5, 5.5, 3.0],
            [4, 0.5, 3, -2.0],
            [3.6, 0.4, 3.3, -0.75],
            [943, 36.8, 1032.44, 2.43],
            [943, 36.8, 803.2, -3.80],
        ];
    }

    /**
     * @test         zScore raw value
     * @dataProvider dataProviderForZScoreRaw
     * @param        float $μ
     * @param        float $σ
     * @param        float $M
     * @param        float $expected
     */
    public function testZScoreRaw(float $μ, float $σ, float $M, float $expected)
    {
        // When
        $z = Significance::zScore($M, $μ, $σ, Significance::Z_RAW_VALUE);

        // Then
        $this->assertEqualsWithDelta($expected, $z, 0.01);
    }

    /**
     * @return array [μ, σ, M, z]
     */
    public function dataProviderForZScoreRaw(): array
    {
        return [
            [1, 1, 1, 0],
            [1, 1, 2, 1],
            [4, 0.5, 5.5, 3.0],
            [4, 0.5, 3, -2.0],
            [3.6, 0.4, 3.3, -0.75],
            [943, 36.8, 1032.44, 2.43],
            [943, 36.8, 803.2, -3.80434783],
        ];
    }

    /**
     * @test         sem
     * @dataProvider dataProviderForSem
     * @param        float $σ
     * @param        int   $n
     * @param        float $expected
     */
    public function testSem(float $σ, int $n, float $expected)
    {
        // When
        $sem = Significance::sem($σ, $n);

        // Then
        $this->assertEqualsWithDelta($expected, $sem, 0.0001);
    }

    /**
     * @return array [σ, n, sem]
     */
    public function dataProviderForSem(): array
    {
        return [
            [5, 100, 0.5],
            [6, 200, 0.4242640687119],
            [5, 35, 0.8451542547285],
        ];
    }

    /**
     * @test         zTestOneSample
     * @dataProvider dataProviderForZTestOneSample
     * @param        float $Hₐ
     * @param        int   $n
     * @param        float $H₀
     * @param        float $σ
     * @param        array $expected
     */
    public function testZTestOneSample(float $Hₐ, int $n, float $H₀, float $σ, array $expected)
    {
        // When
        $zTest = Significance::zTest($Hₐ, $n, $H₀, $σ);

        // Then
        $this->assertEqualsWithDelta($expected, $zTest, 0.001);
    }

    /**
     * @return array [Hₐ, n, H₀, σ, ztest]
     * Test data created from these sites:
     *   - http://www.socscistatistics.com/tests/ztest_sample_mean/Default2.aspx
     *   - https://www.easycalculation.com/statistics/p-value-for-z-score.php
     */
    public function dataProviderForZTestOneSample(): array
    {
        return [
            [96, 55, 100, 12, ['z' => -2.4720661623652, 'p1' => 0.00676, 'p2' => 0.013436]],
            [83, 40, 80, 5, ['z' => 3.79473, 'p1' => 0.0001, 'p2' => 0.0001]],
            [20, 200, 19.2, 6, ['z' => 1.88562, 'p1' => 0.02938, 'p2' => 0.0593]],
            [22.875, 35, 19.5, 5, ['z' => 3.99335, 'p1' => 0.0001, 'p2' => 0.0001]],
            [112, 30, 100, 15, ['z' => 4.38178, 'p1' => 0.0000, 'p2' => 0.0000]],
            [18.9, 200, 21, 5, ['z' => -5.9397, 'p1' => 0.0000, 'p2' => 0.0000]],
            [6.7, 29, 5, 7.1, ['z' => 1.28941, 'p1' => 0.0986, 'p2' => 0.1973]],
            [80.94, 25, 85, 11.6, ['z' => -1.75, 'p1' => 0.0401, 'p2' => 0.080118]],
        ];
    }

    /**
     * @test         zTestTwoSample
     * @dataProvider dataProviderForZTestTwoSample
     * @param        float $μ₁
     * @param        float $μ₂
     * @param        int   $n₁
     * @param        int   $n₂
     * @param        float $σ₁
     * @param        float $σ₂
     * @param        float $Δ
     * @param        array $expected
     */
    public function testZTestTwoSample(float $μ₁, float $μ₂, int $n₁, int $n₂, float $σ₁, float $σ₂, float $Δ, array $expected)
    {
        // When
        $zTest = Significance::zTestTwoSample($μ₁, $μ₂, $n₁, $n₂, $σ₁, $σ₂, $Δ);

        // Then
        $this->assertEqualsWithDelta($expected, $zTest, 0.001);
    }

    /**
     * @return array [μ₁, μ₂, n₁, n₂, σ₁, σ₂, Δ, ztest]
     * Test data created using this online calculator: http://www.mathcracker.com/z-test-for-two-means.php
     */
    public function dataProviderForZTestTwoSample(): array
    {
        return [
            [28, 33, 75, 50, 14.1, 9.5, 0, ['z' => -2.36868418147285, 'p1' => 0.00893, 'p2' => 0.0179]],
            [9.78, 15.10, 900, 1000, 4.05, 4.28, 0, ['z' => -27.83, 'p1' => 0.0, 'p2' => 0.0]],  // Test data: http://www.stat.ucla.edu/~cochran/stat10/winter/lectures/lect21.html
            [150.0648965, 126.7361145, 68, 292, 233.8012747, 199.3031358, 0, ['z' => 0.761, 'p1' => 0.22335, 'p2' => 0.4467]],  // Github issue 175 test data
            [3100, 2750, 75, 75, 420, 425, 0, ['z' => 5.073, 'p1' => 0.0000, 'p2' => 0.0000]],
            [34.5, 34.9, 80, 90, 0.4, 0.8, 0, ['z' => -4.191, 'p1' => 0.0, 'p2' => 0.0]],
            [12.2, 9.2, 1000, 1000, 10.2, 9.9, 0, ['z' => 6.674, 'p1' => 0.0, 'p2' => 0.0]],
        ];
    }

    /**
     * @test         tScore
     * @dataProvider dataProviderForTScore
     * @param        float $Hₐ
     * @param        float $s
     * @param        int   $n
     * @param        float $H₀
     * @param        float $expected
     */
    public function testTScore(float $Hₐ, float $s, int $n, float $H₀, float $expected)
    {
        // When
        $t = Significance::tScore($Hₐ, $s, $n, $H₀);

        // Then
        $this->assertEqualsWithDelta($expected, $t, 0.001);
    }

    /**
     * @return array [Hₐ, s, n, H₀, t]
     */
    public function dataProviderForTScore(): array
    {
        return [
            [130.1, 21.21, 100, 120, 4.762],
            [280, 50, 15, 300, -1.549],
        ];
    }

    /**
     * @test         tTest one sample
     * @dataProvider dataProviderForTTestOneSample
     * @param        array $a
     * @param        float $H₀
     * @param        array $expected
     * @throws       Exception\BadParameterException
     */
    public function testTTestWithOneSampleData(array $a, float $H₀, array $expected)
    {
        // When
        $tTest = Significance::tTest($a, $H₀);

        // Then
        $this->assertEqualsWithDelta($expected['t'], $tTest['t'], 0.00001);
        $this->assertEqualsWithDelta($expected['df'], $tTest['df'], 0.00001);
        $this->assertEqualsWithDelta($expected['p1'], $tTest['p1'], 0.0001);
        $this->assertEqualsWithDelta($expected['p2'], $tTest['p2'], 0.00001);
        $this->assertEqualsWithDelta($expected['mean'], $tTest['mean'], 0.00001);
        $this->assertEqualsWithDelta($expected['sd'], $tTest['sd'], 0.00001);
    }

    /**
     * @test         tTestOneSample
     * @dataProvider dataProviderForTTestOneSample
     * @param        array $a
     * @param        float $H₀
     * @param        array $expected
     */
    public function testTTestOneSample(array $a, float $H₀, array $expected)
    {
        // When
        $tTest = Significance::tTestOneSample($a, $H₀);

        // Then
        $this->assertEqualsWithDelta($expected['t'], $tTest['t'], 0.00001);
        $this->assertEqualsWithDelta($expected['df'], $tTest['df'], 0.00001);
        $this->assertEqualsWithDelta($expected['p1'], $tTest['p1'], 0.0001);
        $this->assertEqualsWithDelta($expected['p2'], $tTest['p2'], 0.00001);
        $this->assertEqualsWithDelta($expected['mean'], $tTest['mean'], 0.00001);
        $this->assertEqualsWithDelta($expected['sd'], $tTest['sd'], 0.00001);
    }

    /**
     * @test         tTestOneSampleFromSummaryData
     * @dataProvider dataProviderForTTestOneSample
     * @param        array $a
     * @param        float $H₀
     * @param        array $expected
     */
    public function testTTestOneSampleFromSummaryData(array $a, float $H₀, array $expected)
    {
        // Given
        $Hₐ    = Average::mean($a);
        $s     = Descriptive::standardDeviation($a, Descriptive::SAMPLE);
        $n     = count($a);

        // When
        $tTest = Significance::tTestOneSampleFromSummaryData($Hₐ, $s, $n, $H₀);

        // Then
        $this->assertEqualsWithDelta($expected['t'], $tTest['t'], 0.00001);
        $this->assertEqualsWithDelta($expected['df'], $tTest['df'], 0.00001);
        $this->assertEqualsWithDelta($expected['p1'], $tTest['p1'], 0.0001);
        $this->assertEqualsWithDelta($expected['p2'], $tTest['p2'], 0.00001);
        $this->assertEqualsWithDelta($expected['mean'], $tTest['mean'], 0.00001);
        $this->assertEqualsWithDelta($expected['sd'], $tTest['sd'], 0.00001);
    }

    /**
     * @return array [a, H₀, expected]
     * t, df, p2 generated with R t.test(x, mu=H₀)
     * p1 generated with calculators http://www.socscistatistics.com/pvalues/tdistribution.aspx and https://www.danielsoper.com/statcalc/calculator.aspx?id=8
     */
    public function dataProviderForTTestOneSample(): array
    {
        return [
            [
                [65, 78, 88, 55, 48, 95, 66, 57, 79, 81],
                75,
                ['t' => -0.7830291, 'df' => 9, 'p1' => 0.226868, 'p2' => 0.4537205, 'mean' => 71.2, 'sd' => 15.34637],
            ],
            [
                [1, 2, 3, 3, 4, 4, 4, 5, 5, 5, 5, 5, 5, 5, 6, 6, 6, 6, 7, 7, 7, 8, 9, 9, 11],
                4,
                ['t' => 3.367538, 'df' => 24, 'p1' => 0.001277, 'p2' => 0.002553184, 'mean' => 5.52, 'sd' => 2.256841],
            ],
            [
                [1, 2, 3, 3, 4, 4, 4, 5, 5, 5, 5, 5, 5, 5, 6, 6, 6, 6, 7, 7, 7, 8, 9, 9, 11],
                5,
                ['t' => 1.152053, 'df' => 24, 'p1' => 0.130314, 'p2' => 0.2606466, 'mean' => 5.52, 'sd' => 2.256841],
            ],
            [
                [1, 2, 3, 3, 4, 4, 4, 5, 5, 5, 5, 5, 5, 5, 6, 6, 6, 6, 7, 7, 7, 8, 9, 9, 11],
                5.5,
                ['t' => 0.04430971, 'df' => 24, 'p1' => 0.482516, 'p2' => 0.9650241, 'mean' => 5.52, 'sd' => 2.256841],
            ],
            [
                [1, 2, 3, 3, 4, 4, 4, 5, 5, 5, 5, 5, 5, 5, 6, 6, 6, 6, 7, 7, 7, 8, 9, 9, 11],
                5.52,
                ['t' => 0, 'df' => 24, 'p1' => 0.5, 'p2' => 1, 'mean' => 5.52, 'sd' => 2.256841],
            ],
            [
                [1, 2, 3, 3, 4, 4, 4, 5, 5, 5, 5, 5, 5, 5, 6, 6, 6, 6, 7, 7, 7, 8, 9, 9, 11],
                6,
                ['t' => -1.063433, 'df' => 24, 'p1' => 0.149084, 'p2' => 0.298169, 'mean' => 5.52, 'sd' => 2.256841],
            ],
            [
                [5, 5.5, 4.5, 5, 5, 6, 5, 5, 4.5, 5, 5, 4.5, 4.5, 5.5, 4, 5, 5, 5.5, 4.5, 5.5, 5, 5.5],
                4.7,
                ['t' => 3.039737, 'df' => 21, 'p1' => 0.003115, 'p2' => 0.006228674, 'mean' => 5, 'sd' => 0.46291],
            ],
            [
                [5, 5.5, 4.5, 5, 5, 6, 5, 5, 4.5, 5, 5, 4.5, 4.5, 5.5, 4, 5, 5, 5.5, 4.5, 5.5, 5, 5.5],
                4.8,
                ['t' => 2.026491, 'df' => 21, 'p1' => 0.027806, 'p2' => 0.05560202, 'mean' => 5, 'sd' => 0.46291],
            ],
            [
                [5, 5.5, 4.5, 5, 5, 6, 5, 5, 4.5, 5, 5, 4.5, 4.5, 5.5, 4, 5, 5, 5.5, 4.5, 5.5, 5, 5.5],
                4.9,
                ['t' => 1.013246, 'df' => 21, 'p1' => 0.161248, 'p2' => 0.3224757, 'mean' => 5, 'sd' => 0.46291],
            ],
            [
                [5, 5.5, 4.5, 5, 5, 6, 5, 5, 4.5, 5, 5, 4.5, 4.5, 5.5, 4, 5, 5, 5.5, 4.5, 5.5, 5, 5.5],
                4.99,
                ['t' => 0.1013246, 'df' => 21, 'p1' => 0.460137, 'p2' => 0.920254, 'mean' => 5, 'sd' => 0.46291],
            ],
            [
                [5, 5.5, 4.5, 5, 5, 6, 5, 5, 4.5, 5, 5, 4.5, 4.5, 5.5, 4, 5, 5, 5.5, 4.5, 5.5, 5, 5.5],
                5,
                ['t' => 0, 'df' => 21, 'p1' => 0.5, 'p2' => 1, 'mean' => 5, 'sd' => 0.46291],
            ],
            [
                [5, 5.5, 4.5, 5, 5, 6, 5, 5, 4.5, 5, 5, 4.5, 4.5, 5.5, 4, 5, 5, 5.5, 4.5, 5.5, 5, 5.5],
                5.01,
                ['t' => -0.1013246, 'df' => 21, 'p1' => 0.46013665, 'p2' => 0.920254, 'mean' => 5, 'sd' => 0.46291],
            ],
            [
                [5, 5.5, 4.5, 5, 5, 6, 5, 5, 4.5, 5, 5, 4.5, 4.5, 5.5, 4, 5, 5, 5.5, 4.5, 5.5, 5, 5.5],
                5.1,
                ['t' => -1.013246, 'df' => 21, 'p1' => 0.16124847, 'p2' => 0.3224757, 'mean' => 5, 'sd' => 0.46291],
            ],
            [
                [128, 118, 144, 133, 132, 111, 149, 139, 136, 126, 127, 115, 142, 140, 131, 132, 122, 119, 129, 128],
                120,
                ['t' => 4.512404, 'df' => 19, 'p1' => 0.00011919, 'p2' => 0.0002383806, 'mean' => 130.05, 'sd' => 9.960316],
            ],
            [
                [128, 118, 144, 133, 132, 111, 149, 139, 136, 126, 127, 115, 142, 140, 143, 132, 122, 119, 129, 128],
                120,
                ['t' => 4.591373, 'df' => 19, 'p1' => 0.00009966, 'p2' => 0.0001993183, 'mean' => 130.65, 'sd' => 10.37342],
            ],
        ];
    }

    /**
     * @test         testTestFromSummaryData more tests
     * @dataProvider dataProviderForTTestOneSampleFromSummaryData
     * @param        float $Hₐ
     * @param        float $s
     * @param        int   $n
     * @param        float $H₀
     * @param        array $expected
     */
    public function testTTestOneSampleFromSummaryData2(float $Hₐ, float $s, int $n, float $H₀, array $expected)
    {
        // When
        $tTest = Significance::tTestOneSampleFromSummaryData($Hₐ, $s, $n, $H₀);

        // Then
        $this->assertEqualsWithDelta($expected, $tTest, 0.001);
    }

    /**
     * @return array [Hₐ, s, n, H₀, ttest]
     */
    public function dataProviderForTTestOneSampleFromSummaryData(): array
    {
        return [
            [130.1, 21.21, 100, 120, ['t' => 4.762, 'df' => 99, 'p1' => 0, 'p2' => 0, 'mean' => 130.1, 'sd' => 21.21]],
            [280, 50, 15, 300, ['t' => -1.549, 'df' => 14, 'p1' => 0.0718, 'p2' => 0.1437, 'mean' => 280, 'sd' => 50]],
            [130.5, 32.4, 30, 142.1, ['t' => -1.9609820, 'df' => 29, 'p1' => 0.02977385, 'p2' => 0.05954770, 'mean' => 130.5, 'sd' => 32.4]],
            [25.12, 2.91, 18, 24.64, ['t' => 0.69981702, 'df' => 17, 'p1' => 0.24675380, 'p2' => 0.4935, 'mean' => 25.12, 'sd' => 2.91]]
        ];
    }

    /**
     * @test         tTest for two samples
     * @dataProvider dataProviderFortTestTwoSampleDataSet
     * @param        array $x₁
     * @param        array $x₂
     * @param        float $μ₁ Sample mean of population 1
     * @param        float $μ₂ Sample mean of population 2
     * @param        int   $n₁ Sample size of population 1
     * @param        int   $n₂ Sample size of population 1
     * @param        float $σ₁ Standard deviation of sample mean 1
     * @param        float $σ₂ Standard deviation of sample mean 2
     * @param        array  $expected
     * @throws       Exception\BadParameterException
     */
    public function testtTestWithTwoSamples(array $x₁, array $x₂, float $μ₁, float $μ₂, int $n₁, int $n₂, float $σ₁, float $σ₂, array $expected)
    {
        // When
        $tTest = Significance::tTest($x₁, $x₂);

        // Then
        $this->assertEqualsWithDelta($expected['t'], $tTest['t'], 0.00001);
        $this->assertEqualsWithDelta($expected['df'], $tTest['df'], 0.00001);
        $this->assertEqualsWithDelta($expected['p1'], $tTest['p1'], 0.0001);
        $this->assertEqualsWithDelta($expected['p2'], $tTest['p2'], 0.00001);
        $this->assertEqualsWithDelta($μ₁, $tTest['mean1'], 0.00001);
        $this->assertEqualsWithDelta($μ₂, $tTest['mean2'], 0.00001);
        $this->assertEqualsWithDelta($σ₁, $tTest['sd1'], 0.00001);
        $this->assertEqualsWithDelta($σ₂, $tTest['sd2'], 0.00001);
    }

    /**
     * @test         tTestTwoSample
     * @dataProvider dataProviderFortTestTwoSampleDataSet
     * @param        array $x₁
     * @param        array $x₂
     * @param        float $μ₁ Sample mean of population 1
     * @param        float $μ₂ Sample mean of population 2
     * @param        int   $n₁ Sample size of population 1
     * @param        int   $n₂ Sample size of population 1
     * @param        float $σ₁ Standard deviation of sample mean 1
     * @param        float $σ₂ Standard deviation of sample mean 2
     * @param        array  $expected
     */
    public function testtTestTwoSample(array $x₁, array $x₂, float $μ₁, float $μ₂, int $n₁, int $n₂, float $σ₁, float $σ₂, array $expected)
    {
        // When
        $tTest = Significance::tTestTwoSample($x₁, $x₂);

        // Then
        $this->assertEqualsWithDelta($expected['t'], $tTest['t'], 0.00001);
        $this->assertEqualsWithDelta($expected['df'], $tTest['df'], 0.00001);
        $this->assertEqualsWithDelta($expected['p1'], $tTest['p1'], 0.0001);
        $this->assertEqualsWithDelta($expected['p2'], $tTest['p2'], 0.00001);
        $this->assertEqualsWithDelta($μ₁, $tTest['mean1'], 0.00001);
        $this->assertEqualsWithDelta($μ₂, $tTest['mean2'], 0.00001);
        $this->assertEqualsWithDelta($σ₁, $tTest['sd1'], 0.00001);
        $this->assertEqualsWithDelta($σ₂, $tTest['sd2'], 0.00001);
    }

    /**
     * @test         tTestTwoSampleFromSummaryData
     * @dataProvider dataProviderFortTestTwoSampleDataSet
     * @param        array $x₁
     * @param        array $x₂
     * @param        float $μ₁ Sample mean of population 1
     * @param        float $μ₂ Sample mean of population 2
     * @param        int   $n₁ Sample size of population 1
     * @param        int   $n₂ Sample size of population 1
     * @param        float $σ₁ Standard deviation of sample mean 1
     * @param        float $σ₂ Standard deviation of sample mean 2
     * @param        array  $expected
     */
    public function testtTestTwoSampleFromSummaryData(array $x₁, array $x₂, float $μ₁, float $μ₂, int $n₁, int $n₂, float $σ₁, float $σ₂, array $expected)
    {
        // When
        $tTest = Significance::tTestTwoSampleFromSummaryData($μ₁, $μ₂, $n₁, $n₂, $σ₁, $σ₂);

        // Then
        $this->assertEqualsWithDelta($expected['t'], $tTest['t'], 0.00001);
        $this->assertEqualsWithDelta($expected['df'], $tTest['df'], 0.00001);
        $this->assertEqualsWithDelta($expected['p1'], $tTest['p1'], 0.0001);
        $this->assertEqualsWithDelta($expected['p2'], $tTest['p2'], 0.00001);
        $this->assertEqualsWithDelta($μ₁, $tTest['mean1'], 0.00001);
        $this->assertEqualsWithDelta($μ₂, $tTest['mean2'], 0.00001);
        $this->assertEqualsWithDelta($σ₁, $tTest['sd1'], 0.00001);
        $this->assertEqualsWithDelta($σ₂, $tTest['sd2'], 0.00001);
    }

    /**
     * @return array [x₁, x₂, μ₁, μ₂, n₁, n₂, σ₁, σ₂, expected]
     * t, df, p2 generated with R t.test(x, y)
     * p1 generated with calculator http://www.socscistatistics.com/pvalues/tdistribution.aspx
     */
    public function dataProviderFortTestTwoSampleDataSet(): array
    {
        return [
            [
                [42.1, 41.3, 42.4, 43.2, 41.8, 41.0, 41.8, 42.8, 42.3, 42.7],
                [42.7, 43.8, 42.5, 43.1, 44.0, 43.6, 43.3, 43.5, 41.7, 44.1],
                42.14, 43.23, 10, 10, 0.6834553, 0.7498889,
                ['t' => -3.397231, 'df' => 17.8473, 'p1' => 0.001622, 'p2' => 0.003242249],
            ],
            [
                [30, 31, 32],
                [29, 30, 31],
                31, 30, 3, 3, 1, 1,
                ['t' => 1.224745, 'df' => 4, 'p1' => 0.14394, 'p2' => 0.2878641],
            ],
            [
                [30.02, 29.99, 30.11, 29.97, 30.01, 29.99],
                [29.89, 29.93, 29.72, 29.98, 30.02, 29.98],
                30.015, 29.92, 6, 6, 0.04969909, 0.1078888,
                ['t' => 1.959006, 'df' => 7.03056, 'p1' => 0.045387, 'p2' => 0.09077332],
            ],
            [
                [4, 5, 5, 6, 6, 6, 7, 7, 7, 7, 7, 7, 8, 8, 9, 9, 10, 12],
                [4, 4, 5, 6, 6, 6, 7, 7, 7, 7, 8, 8, 8, 9, 9, 11, 13],
                7.222222, 7.352941, 18, 17, 1.926764, 2.316818,
                ['t' => -0.1809288, 'df' => 31.20011, 'p1' => 0.428769, 'p2' => 0.8575937],
            ],
            // Equal variances; equal sizes - Wikipedia https://en.wikipedia.org/wiki/Welch%27s_t-test
            [
                [27.5, 21.0, 19.0, 23.6, 17.0, 17.9, 16.9, 20.1, 21.9, 22.6, 23.1, 19.6, 19.0, 21.7, 21.4],
                [27.1, 22.0, 20.8, 23.4, 23.4, 23.5, 25.8, 22.0, 24.8, 20.2, 21.9, 22.1, 22.9, 20.5, 24.4],
                20.82, 22.98667, 15, 15, 2.804894, 1.952605,
                ['t' => -2.455356, 'df' => 24.98853, 'p1' => 0.010698, 'p2' => 0.021378],
            ],
            // Unequal variances; unequal sizes - Wikipedia https://en.wikipedia.org/wiki/Welch%27s_t-test
            [
                [17.2, 20.9, 22.6, 18.1, 21.7, 21.4, 23.5, 24.2, 14.7, 21.8],
                [21.5, 22.8, 21.0, 23.0, 21.6, 23.6, 22.5, 20.7, 23.4, 21.8, 20.7, 21.7, 21.5, 22.5, 23.6, 21.5, 22.5, 23.5, 21.5, 21.8],
                20.61, 22.135, 10, 20, 3.006826, 0.9477258,
                ['t' => -1.565434, 'df' => 9.904741, 'p1' => 0.074471, 'p2' => 0.1488417],
            ],
            // Unequal variances; unequal sizes - Wikipedia https://en.wikipedia.org/wiki/Welch%27s_t-test
            [
                [19.8, 20.4, 19.6, 17.8, 18.5, 18.9, 18.3, 18.9, 19.5, 22.0],
                [28.2, 26.6, 20.1, 23.3, 25.2, 22.1, 17.7, 27.6, 20.6, 13.7, 23.2, 17.5, 20.6, 18.0, 23.9, 21.6, 24.3, 20.4, 24.0, 13.2],
                19.37, 21.59, 10, 20, 1.203744, 4.137111,
                ['t' => -2.219241, 'df' => 24.49622, 'p1' => 0.017995, 'p2' => 0.03597227],
            ],
            [
                [35, 40, 12, 15, 21, 14, 46, 10, 28, 48, 16, 30, 32, 48, 31, 22, 12, 39, 19, 25],
                [2, 27, 38, 31, 1, 19, 1, 34, 3, 1, 2, 3, 2, 1, 2, 1, 3, 29, 37, 2],
                27.15, 11.95, 20, 20, 12.508, 14.61245,
                ['t' => 3.534054, 'df' => 37.11672, 'p1' => 0.000558, 'p2' => 0.0011156],
            ],
            // Large unequal sample sizes
            [
                [17.2, 20.9, 22.6, 18.1, 21.7, 21.4, 23.5, 24.2, 14.7, 21.8, 17.2, 20.9, 22.6, 18.1, 21.7, 21.4, 23.5, 24.2, 14.7, 21.8, 17.2, 20.9, 22.6, 18.1, 21.7, 21.4, 23.5, 24.2, 14.7, 21.8, 17.2, 20.9, 22.6, 18.1, 21.7, 21.4, 23.5, 24.2, 14.7, 21.8, 17.2, 20.9, 22.6, 18.1, 21.7, 21.4, 23.5, 24.2, 14.7, 21.8, 17.2, 20.9, 22.6, 18.1, 21.7, 21.4, 23.5, 24.2, 14.7, 21.8, 17.2, 20.9, 22.6, 18.1, 21.7, 21.4, 23.5, 24.2],
                [21.5, 22.8, 21.0, 23.0, 21.6, 23.6, 22.5, 20.7, 23.4, 21.8, 20.7, 21.7, 21.5, 22.5, 23.6, 21.5, 22.5, 23.5, 21.5, 21.8, 21.5, 22.8, 21.0, 23.0, 21.6, 23.6, 22.5, 20.7, 23.4, 21.8, 20.7, 21.7, 21.5, 22.5, 23.6, 21.5, 22.5, 23.5, 21.5, 21.8, 21.5, 22.8, 21.0, 23.0, 21.6, 23.6, 22.5, 20.7, 23.4, 21.8, 20.7, 21.7, 21.5, 22.5, 23.6, 21.5, 22.5, 23.5, 21.5, 21.8, 21.5, 22.8, 21.0, 23.0, 21.6, 23.6, 22.5, 20.7, 23.4, 21.8, 20.7, 21.7, 21.5, 22.5, 23.6, 21.5, 22.5, 23.5, 21.5, 21.8, 21.5, 22.8, 21.0, 23.0, 21.6, 23.6, 22.5, 20.7, 23.4, 21.8, 20.7, 21.7, 21.5, 22.5, 23.6, 21.5, 22.5, 23.5, 21.5, 21.8, 21.5, 22.8, 21.0, 23.0, 21.6, 23.6, 22.5, 20.7, 23.4, 21.8, 20.7, 21.7, 21.5, 22.5, 23.6, 21.5, 22.5, 23.5, 21.5, 21.8, 21.5, 22.8, 21.0, 23.0, 21.6, 23.6, 22.5, 20.7, 23.4, 21.8, 20.7, 21.7, 21.5, 22.5, 23.6, 21.5, 22.5, 23.5, 21.5, 21.8, 21.5, 22.8, 21.0, 23.0, 21.6, 23.6, 22.5, 20.7, 23.4, 21.8, 20.7, 21.7, 21.5, 22.5, 23.6, 21.5, 22.5, 23.5, 21.5, 21.8, 21.5, 22.8, 21.0, 23.0, 21.6, 23.6, 22.5, 20.7, 23.4, 21.8, 20.7, 21.7, 21.5, 22.5, 23.6, 21.5, 22.5, 23.5, 21.5, 21.8, 21.5, 22.8, 21.0, 23.0, 21.6, 23.6, 22.5, 20.7, 23.4, 21.8, 20.7, 21.7, 21.5, 22.5, 23.6, 21.5, 22.5, 23.5, 21.5, 21.8, 21.5, 22.8, 21.0, 23.0, 21.6, 23.6, 22.5, 20.7, 23.4, 21.8, 20.7, 21.7, 21.5, 22.5, 23.6, 21.5, 22.5, 23.5, 21.5, 21.8, 21.5, 22.8, 21.0, 23.0, 21.6, 23.6, 22.5, 20.7, 23.4, 21.8, 20.7, 21.7, 21.5, 22.5, 23.6, 21.5, 22.5, 23.5, 21.5, 21.8, 21.5, 22.8, 21.0, 23.0, 21.6, 23.6, 22.5, 20.7, 23.4, 21.8, 20.7, 21.7, 21.5, 22.5, 23.6, 21.5, 22.5, 23.5, 21.5, 21.8, 21.5, 22.8, 21.0, 23.0, 21.6, 23.6, 22.5, 20.7, 23.4, 21.8, 20.7, 21.7, 21.5, 22.5, 23.6, 21.5, 22.5, 23.5, 21.5, 21.8, 21.5, 22.8, 21.0, 23.0, 21.6, 23.6, 22.5, 20.7, 23.4, 21.8, 20.7, 21.7, 21.5, 22.5, 23.6, 21.5, 22.5, 23.5, 21.5, 21.8],
                20.67941, 22.135, 68, 300, 2.820266, 0.9252723,
                ['t' => -4.205026, 'df' => 70.29978, 'p1' => 0.000076, 'p2' => 7.568864e-05],
            ],
        ];
    }

    /**
     * @test         tTestTwoSampleFromSummaryData regressions
     * @dataProvider dataProviderForTTestTwoSampleFromSummaryData
     * @param        float $μ₁ Sample mean of population 1
     * @param        float $μ₂ Sample mean of population 2
     * @param        int   $n₁ Sample size of population 1
     * @param        int   $n₂ Sample size of population 1
     * @param        float $σ₁ Standard deviation of sample mean 1
     * @param        float $σ₂ Standard deviation of sample mean 2
     * @param        array  $expected
     */
    public function testTTestTwoSampleFromSummaryDataRegression(float $μ₁, float $μ₂, int $n₁, int $n₂, float $σ₁, float $σ₂, array $expected)
    {
        // When
        $tTest = Significance::tTestTwoSampleFromSummaryData($μ₁, $μ₂, $n₁, $n₂, $σ₁, $σ₂);

        // Then
        $this->assertEqualsWithDelta($expected['t'], $tTest['t'], 0.0001);
        $this->assertEqualsWithDelta($expected['df'], $tTest['df'], 0.00001);
        $this->assertEqualsWithDelta($expected['p1'], $tTest['p1'], 0.0001);
        $this->assertEqualsWithDelta($expected['p2'], $tTest['p2'], 0.0001);
        $this->assertEqualsWithDelta($μ₁, $tTest['mean1'], 0.00001);
        $this->assertEqualsWithDelta($μ₂, $tTest['mean2'], 0.00001);
        $this->assertEqualsWithDelta($σ₁, $tTest['sd1'], 0.00001);
        $this->assertEqualsWithDelta($σ₂, $tTest['sd2'], 0.00001);
    }

    /**
     * @return array [μ₁, μ₂, n₁, n₂, σ₁, σ₂, expected]
     */
    public function dataProviderForTTestTwoSampleFromSummaryData(): array
    {
        return [
            // Regression issue 265 - data generated with http://www.usablestats.com/calcs/2samplet&summary=1
            [
                32, 30, 134, 210, 21, 18,
                ['t' => 0.9097, 'df' => 251.72638768068, 'p1' => 0.1819, 'p2' => 0.3638 ],
            ],
        ];
    }

    /**
     * @test tTest throws an Exception\BadParameterException if the second argument is neither a number nor a string
     */
    public function testTTestBadParameterException()
    {
        // Given
        $a = [1, 2, 3];
        $b = 'string';

        // Then
        $this->expectException(Exception\BadParameterException::class);

        // When
        $tTest = Significance::tTest($a, $b);
    }

    /**
     * @test         chiSquaredTest
     * @dataProvider dataProviderForChiSquaredTest
     * @param        array $observed
     * @param        array $expected
     * @param        float $χ²
     * @param        float $p
     * @throws       \Exception
     */
    public function testChiSquaredTest(array $observed, array $expected, float $χ², float $p)
    {
        // When
        $chi = Significance::chiSquaredTest($observed, $expected);

        // Then
        $this->assertEqualsWithDelta($χ², $chi['chi-square'], 0.0001);
        $this->assertEqualsWithDelta($p, $chi['p'], 0.0001);
    }

    /**
     * @return array [observed, expected, χ², p]
     */
    public function dataProviderForChiSquaredTest(): array
    {
        return [
            // Example data from Statistics (Freedman, Pisani, Purves)
            [
                [4, 6, 17, 16, 8, 9],
                [10, 10, 10, 10, 10, 10],
                14.2, 0.014388,
            ],
            [
                [5, 7, 17, 16, 8, 7],
                [10, 10, 10, 10, 10, 10],
                13.2, 0.0216,
            ],
            [
                [9, 11, 10, 8, 12, 10],
                [10, 10, 10, 10, 10, 10],
                1.0, 0.962566,
            ],
            [
                [90, 110, 100, 80, 120, 100],
                [100, 100, 100, 100, 100, 100],
                10.0, 0.075235,
            ],
            [
                [10287, 10056, 9708, 10080, 9935, 9934],
                [10000, 10000, 10000, 10000, 10000, 10000],
                18.575, 0.0023,
            ],
        ];
    }

    /**
     * @test     chiSquaredTest BadDataException
     */
    public function testChiSquaredTestExceptionCountsDiffer()
    {
        // Given
        $observed = [1, 2, 3, 4];
        $expected = [1, 2, 3];

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        Significance::chiSquaredTest($observed, $expected);
    }
}
