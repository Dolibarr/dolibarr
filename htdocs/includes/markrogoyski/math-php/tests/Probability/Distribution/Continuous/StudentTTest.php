<?php

namespace MathPHP\Tests\Probability\Distribution\Continuous;

use MathPHP\Probability\Distribution\Continuous\StudentT;

class StudentTTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test         pdf
     * @dataProvider dataProviderForPdf
     * @param        float $t
     * @param        float $ν
     * @param        float $expected
     */
    public function testPdf(float $t, float $ν, float $expected)
    {
        // Given
        $studentT = new StudentT($ν);

        // When
        $pdf = $studentT->pdf($t);

        // Then
        $this->assertEqualsWithDelta($expected, $pdf, 0.0000001);
    }

    /**
     * @return array [t, ν, pdf]
     * Generated with R dt(t, ν) from package stats
     */
    public function dataProviderForPdf(): array
    {
        return [
            [-4, 1, 0.01872411],
            [-3, 1, 0.03183099],
            [-2, 1, 0.06366198],
            [-1, 1, 0.1591549],
            [0, 1, 0.3183099],
            [1, 1, 0.1591549],
            [2, 1, 0.06366198],
            [3, 1, 0.03183099],
            [4, 1, 0.01872411],
            [5, 1, 0.01224269],
            [10, 1, 0.003151583],

            [-4, 2, 0.01309457],
            [-3, 2, 0.02741012],
            [-2, 2, 0.06804138],
            [-1, 2, 0.1924501],
            [0, 2, 0.3535534],
            [1, 2, 0.1924501],
            [2, 2, 0.06804138],
            [3, 2, 0.02741012],
            [4, 2, 0.01309457],
            [5, 2, 0.007127781],
            [10, 2, 0.0009707329],

            [-4, 6, 0.004054578],
            [-3, 6, 0.01549193],
            [-2, 6, 0.06403612],
            [-1, 6, 0.2231423],
            [0, 6, 0.3827328],
            [1, 6, 0.2231423],
            [2, 6, 0.06403612],
            [3, 6, 0.01549193],
            [4, 6, 0.004054578],
            [5, 6, 0.001220841],
            [10, 6, 1.651408e-05],

            [-10, 10, 7.284686e-07],
            [-5, 10, 0.0003960011],
            [-2, 10, 0.06114577],
            [-1, 10, 0.230362],
            [0, 10, 0.3891084],
            [1, 10, 0.230362],
            [2, 10, 0.06114577],
            [5, 10, 0.0003960011],
            [10, 10, 7.284686e-07],

            [-10, 20, 2.660085e-09],
            [-5, 20, 7.898911e-05],
            [-2, 20, 0.05808722],
            [-1, 20, 0.2360456],
            [0, 20, 0.3939886],
            [1, 20, 0.2360456],
            [2, 20, 0.05808722],
            [5, 20, 7.898911e-05],
            [-10, 20, 2.660085e-09],

            [0, 50, 0.3969527],
            [1, 50, 0.2395711],
            [5, 50, 1.283547e-05],

            [0, 100, 0.3979462],
            [1, 100, 0.2407659],
            [5, 100, 5.080058e-06],

            [0, 200, 0.3984439],
            [1, 200, 0.2413671],
            [5, 200, 2.88097e-06],

            [0, 250, 0.3985435],
            [1, 250, 0.2414876],
            [5, 250, 2.545035e-06],

            [0, 300, 0.39861],
            [1, 300, 0.241568],
            [5, 300, 2.338036e-06],

            [0, 301, 0.3986111],

            [0, 302, 0.3986122],
            [0, 303, 0.3986133],
            [0, 305, 0.3986154],
            [0, 310, 0.3986207],

            [0, 320, 0.3986307],
            [1, 320, 0.2415931],
            [5, 320, 2.276028e-06],

            [5, 320, 2.276028e-06],
            [5, 325, 2.261896e-06],
            [5, 330, 2.248256e-06],
            [5, 331, 2.245584e-06],
            [5, 332, 2.242931e-06],
            [5, 333, 2.240297e-06],
            [5, 334, 2.23768e-06],
            [5, 335, 2.235082e-06],

            [0, 341, 0.3986499],
            [0, 351, 0.3986582],
            [0, 361, 0.3986661],
            [0, 371, 0.3986735],
            [0, 381, 0.3986806],
            [0, 400, 0.398693],

            [0, 500, 0.3987429],
            [1, 500, 0.241729],
            [2, 500, 0.05417883],
            [3, 500, 0.004569562],
            [5, 500, 1.962337e-06],

            [0, 1000, 0.3988426],
            [1, 1000, 0.2418498],
            [2, 1000, 0.05408517],
            [3, 1000, 0.004500625],
            [5, 1000, 1.712012e-06],

            [0, 1200, 0.3988592],
            [1, 1200, 0.2418699],
            [2, 1200, 0.05406951],
            [3, 1200, 0.004489151],
            [5, 1200, 1.672771e-06],

            [0, 1500, 0.3988758],
            [1, 1500, 0.2418901],
            [2, 1500, 0.05405383],
            [3, 1500, 0.004477681],
            [5, 1500, 1.634218e-06],

            [5, 1000, 1.712012233e-06],
            [1E9, 2, 1e-27],
        ];
    }

    /**
     * @test Github issue 429 - pdf - produced incorrect value of 0
     *
     * R Reference:
     *  > library(stats)
     *  > v <- 341
     *  > t <- 0
     *  > dt(t, v)
     *  [1] 0.3986499
     */
    public function testBugIssue429StudentTPdf()
    {
        // Given
        $v = 341;
        $studentT = new StudentT($v);

        // When
        $t = 0;
        $pdf = $studentT->pdf($t);

        // Then
        $expected = 0.3986499;
        $this->assertEqualsWithDelta($expected, $pdf, 0.001);
    }

    /**
     * @test         cdf
     * @dataProvider dataProviderForCdf
     * @param        float $t
     * @param        float $ν
     * @param        float $expected
     */
    public function testCdf(float $t, float $ν, float $expected)
    {
        // Given
        $studentT = new StudentT($ν);

        // When
        $cdf = $studentT->cdf($t);

        // Then
        $this->assertEqualsWithDelta($expected, $cdf, 0.0000001);
    }

    /**
     * @return array [t, ν, cdf]
     * Generated with R pt(t, ν) from package stats
     */
    public function dataProviderForCdf(): array
    {
        return [
            [-4, 1, 0.07797913],
            [-3, 1, 0.1024164],
            [-2, 1, 0.1475836],
            [-1, 1, 0.25],
            [0, 1, 0.5],
            [1, 1, 0.75],
            [2, 1, 0.8524164],
            [3, 1, 0.8975836],
            [4, 1, 0.9220209],
            [5, 1, 0.937167],
            [10, 1, 0.9682745],

            [-4, 2, 0.02859548],
            [-3, 2, 0.04773298],
            [-2, 2, 0.09175171],
            [-1, 2, 0.2113249],
            [0, 2, 0.5],
            [1, 2, 0.7886751],
            [2, 2, 0.9082483],
            [3, 2, 0.952267],
            [4, 2, 0.9714045],
            [5, 2, 0.9811252],
            [10, 2, 0.9950738],

            [-4, 6, 0.003559489],
            [-3, 6, 0.0120041],
            [-2, 6, 0.04621316],
            [-1, 6, 0.1779588],
            [0, 6, 0.5],
            [1, 6, 0.8220412],
            [2, 6, 0.9537868],
            [3, 6, 0.9879959],
            [4, 6, 0.9964405],
            [5, 6, 0.9987738],
            [10, 6, 0.999971],

            [-2, 3, 0.06966298],
            [0.1, 2, 0.5352673],
            [2.9, 2, 0.9494099],
            [3.9, 6, 0.996008],
            [\INF, 6, 1],
            [-\INF, 6, 0],
            [0, \INF, 0.5],
            [1, \INF, 0.841344746],
            [1, 5E5, 0.841344504097939],
            [1.5E50, 1, 1],
        ];
    }

    /**
     * @test         mean
     * @dataProvider dataProviderForMean
     * @param        float $ν
     * @param        float $μ
     */
    public function testMean(float $ν, float $μ)
    {
        // Given
        $studentT = new StudentT($ν);

        // When
        $mean = $studentT->mean();

        // Then
        $this->assertEquals($μ, $mean);
    }

    /**
     * @return array [ν, μ]
     */
    public function dataProviderForMean(): array
    {
        return [
            [2, 0],
            [3, 0],
        ];
    }

    /**
     * @test     mean is not a number when ν is less than or equal to 1
     */
    public function testMeanNan()
    {
        // Given
        $ν        = 1;
        $studentT = new StudentT($ν);

        // When
        $mean = $studentT->mean();

        // Then
        $this->assertNan($mean);
    }

    /**
     * @test         median
     * @dataProvider dataProviderForMedianAndMode
     * @param        float $ν
     * @param        float $expected
     */
    public function testMedian(float $ν, float $expected)
    {
        // Given
        $studentT = new StudentT($ν);

        // When
        $median = $studentT->median();

        // Then
        $this->assertEquals($expected, $median);
    }

    /**
     * @test         mode
     * @dataProvider dataProviderForMedianAndMode
     * @param        float $ν
     * @param        float $expected
     */
    public function testMode(float $ν, float $expected)
    {
        // Given
        $studentT = new StudentT($ν);

        // When
        $mode = $studentT->mode();

        // Then
        $this->assertEquals($expected, $mode);
    }

    /**
     * @return array [ν, μ]
     */
    public function dataProviderForMedianAndMode(): array
    {
        return [
            [1, 0],
            [2, 0],
            [3, 0],
            [4, 0],
        ];
    }

    /**
     * @test         variance
     * @dataProvider dataProviderForVariance
     * @param        float $ν
     * @param        float $expected
     */
    public function testVariance(float $ν, float $expected)
    {
        // Given
        $studentT = new StudentT($ν);

        // When
        $variance = $studentT->variance();

        // Then
        $this->assertEquals($expected, $variance);
    }

    /**
     * @return array [ν, μ]
     */
    public function dataProviderForVariance(): array
    {
        return [
            [1.1, \INF],
            [1.5, \INF],
            [2, \INF],
            [3, 3],
            [4, 2],
            [5, 5 / 3],
        ];
    }

    /**
     * @test         variance is not a number when ν ≤ 1
     * @dataProvider dataProviderForVarianceNan
     * @param        float $ν
     */
    public function testVarianceNan(float $ν)
    {
        // Given
        $studentT = new StudentT($ν);

        // When
        $variance = $studentT->variance();

        // Then
        $this->assertNan($variance);
    }

    /**
     * @return array [ν, μ]
     */
    public function dataProviderForVarianceNan(): array
    {
        return [
            [0.1],
            [0.5],
            [0.9],
            [1],
        ];
    }

    /**
     * @test         inverse
     * @dataProvider dataProviderForInverse
     * @param        float $p
     * @param        float $ν
     * @param        float $x
     */
    public function testInverse(float $p, float $ν, float $x)
    {
        // Given
        $studentT = new StudentT($ν);

        // When
        $inverse = $studentT->inverse($p);

        // Then
        $this->assertEqualsWithDelta($x, $inverse, 0.00001);
    }

    /**
     * Generated with R qt(c(p), ν)
     * @return array [p, ν, x]
     */
    public function dataProviderForInverse(): array
    {
        return [
            [0.90, 1, 3.077684],
            [0.90, 2, 1.885618],
            [0.90, 3, 1.637744],
            [0.90, 4, 1.533206],
            [0.90, 5, 1.475884],
            [0.90, 10, 1.372184],
            [0.90, 20, 1.325341],
            [0.90, 50, 1.298714],
            [0.90, 100, 1.290075],
            [0.95, 1, 6.313752],
            [0.95, 2, 2.919986],
            [0.95, 3, 2.353363],
            [0.95, 4, 2.131847],
            [0.95, 5, 2.015048],
            [0.95, 10, 1.812461],
            [0.95, 20, 1.724718],
            [0.95, 50, 1.675905],
            [0.95, 100, 1.660234],
            [0.99, 1, 31.82052],
            [0.99, 2, 6.964557],
            [0.99, 3, 4.540703],
            [0.99, 4, 3.746947],
            [0.99, 5, 3.36493],
            [0.99, 10, 2.763769],
            [0.99, 20, 2.527977],
            [0.99, 50, 2.403272],
            [0.99, 100, 2.364217],
            [0.6, 1, 0.3249197],
            [0.6, 2, 0.2886751],
        ];
    }
}
