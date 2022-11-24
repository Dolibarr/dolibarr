<?php

namespace MathPHP\Tests\Statistics\Regression;

use MathPHP\Statistics\Regression\Linear;

class RegressionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test         correlationCoefficient
     * @dataProvider dataProviderForR
     * @param        array $points
     * @param        float $r
     */
    public function testCorrelationCoefficient(array $points, float $r)
    {
        // Given
        $regression = new Linear($points);

        // Then
        $this->assertEqualsWithDelta($r, $regression->correlationCoefficient(), 0.001);
    }

    /**
     * @test         r
     * @dataProvider dataProviderForR
     * @param        array $points
     * @param        float $r
     */
    public function testR(array $points, float $r)
    {
        $regression = new Linear($points);
        $this->assertEqualsWithDelta($r, $regression->r($points), 0.001);
    }

    /**
     * @return array [points, r]
     */
    public function dataProviderForR(): array
    {
        return [
            [
                [ [1,2], [2,3], [4,5], [5,7], [6,8] ],
                0.993
            ],
            [
                [ [4,390], [9,580], [10,650], [14,730], [4,410], [7,530], [12,600], [22,790], [1,350], [3,400], [8,590], [11,640], [5,450], [6,520], [10,690], [11,690], [16,770], [13,700], [13,730], [10,640] ],
                0.9336
            ],
        ];
    }

    /**
     * @test         coefficientOfDetermination
     * @dataProvider dataProviderForR2
     * @param        array $points
     * @param        float $r2
     */
    public function testCoefficientOfDetermination(array $points, float $r2)
    {
        // Given
        $regression = new Linear($points);

        // Then
        $this->assertEqualsWithDelta($r2, $regression->coefficientOfDetermination($points), 0.001);
    }

    /**
     * @test         r2
     * @dataProvider dataProviderForR2
     * @param        array $points
     * @param        float $r2
     */
    public function testR2(array $points, float $r2)
    {
        // Given
        $regression = new Linear($points);

        // Then
        $this->assertEqualsWithDelta($r2, $regression->r2($points), 0.001);
    }

    /**
     * @return array [points, r2]
     */
    public function dataProviderForR2(): array
    {
        return [
            [
                [ [1,2], [2,3], [4,5], [5,7], [6,8] ],
                0.986049
            ],
            [
                [ [4,390], [9,580], [10,650], [14,730], [4,410], [7,530], [12,600], [22,790], [1,350], [3,400], [8,590], [11,640], [5,450], [6,520], [10,690], [11,690], [16,770], [13,700], [13,730], [10,640] ],
                0.87160896
            ],
        ];
    }

    /**
     * @test toString
     */
    public function testToString()
    {
        // Given
        $regression = new Linear([[1,2],[3,3],[3,4],[4,6]]);

        // Then
        $this->assertTrue(\is_string($regression->__toString()));
    }

    /**
     * @test         sumOfSquaresTotal
     * @dataProvider dataProviderForSumOfSquaresTotal
     * @param        array $points
     * @param        float $SUStot
     */
    public function testSumOfSquaresTotal(array $points, float $SUStot)
    {
        // Given
        $regression = new Linear($points);

        // Then
        $this->assertEqualsWithDelta($SUStot, $regression->sumOfSquaresTotal(), 0.0001);
    }

    /**
     * @return array [points, SUStot]
     */
    public function dataProviderForSumOfSquaresTotal(): array
    {
        return [
            [ [[1,3], [2,6], [3,7], [4,11], [5,12], [6,13], [7,17]], 136.8571],
            [ [[1,2], [2,3], [4,5], [5,7], [6,8]], 26],
        ];
    }

    /**
     * @test         yHat
     * @dataProvider dataProviderForYHat()
     * @param        array $points
     * @param        array $yhat
     */
    public function testYHat(array $points, array $yhat)
    {
        // Given
        $regression = new Linear($points);

        // Then
        $this->assertEqualsWithDelta($yhat, $regression->yHat(), 0.01);
    }

    /**
     * @return array [points, yHat]
     */
    public function dataProviderForYHat(): array
    {
        return [
            [
                [ [1,2], [2,3], [4,5], [5,7], [6,8] ], // m = 1.2209302325581, b = 0.60465116279069
                [ 1.82558139534879, 3.04651162790689, 5.48837209302309, 6.70930232558119, 7.93023255813929] // evaluate y = mx + b
            ],
            // Example data from http://faculty.cas.usf.edu/mbrannick/regression/regbas.html
            [
                [ [61,105], [62,120], [63,120], [65,160], [65,120], [68,145], [69,175], [70,160], [72,185], [75,210] ],
                [ 108.19, 115.16, 122.13, 136.06, 136.06, 156.97, 163.94, 170.91, 184.84, 205.75 ],
            ],
        ];
    }

    /**
     * @test         sumOfSquaresRegression
     * @dataProvider dataProviderForSumOfSquaresRegression
     * @param        array $points
     * @param        float $SSreg
     */
    public function testSumOfSquaresRegression(array $points, float $SSreg)
    {
        // Given
        $regression = new Linear($points);

        // Then
        $this->assertEqualsWithDelta($SSreg, $regression->sumOfSquaresRegression(), 0.00001);
    }

    /**
     * @return array [points, SSreg]
     */
    public function dataProviderForSumOfSquaresRegression(): array
    {
        return [
            [
                [ [1,2], [2,3], [4,5], [5,7], [6,8] ], // y mean = 5; yhat = [1.82558139534879, 3.04651162790689, 5.48837209302309, 6.70930232558119, 7.93023255813929]
                25.63953488371927                      // 10.07693347755574 + 3.81611681990299 + 0.23850730124375 + 2.92171444023726 + 8.58626284477953
            ],
            [
                [ [61,105], [62,120], [63,120], [65,160], [65,120], [68,145], [69,175], [70,160], [72,185], [75,210] ], // y mean = 150; yhat = [108.1914893617, 115.15957446809, 122.12765957447, 136.06382978723, 136.06382978723, 156.96808510638, 163.93617021277, 170.90425531915, 184.84042553191, 205.74468085106]
                9128.19148936100534 // 1747.95156179284427 + 1213.85525124456621 + 776.86736079663386 + 194.21684019929783 + 194.21684019929783 + 48.55421004975478 + 194.21684019929783 + 436.98789044821107 + 1213.85525124456621 + 3107.46944318653545
            ],
        ];
    }

    /**
     * @test         sumOfSquaresResidual
     * @dataProvider dataProviderForSumOfSquaresResidual
     * @param        array $points
     * @param        float $SSres
     */
    public function testSumOfSquareResidual(array $points, float $SSres)
    {
        // Given
        $regression = new Linear($points);

        // Then
        $this->assertEqualsWithDelta($SSres, $regression->sumOfSquaresResidual(), 0.00001);
    }

    /**
     * @return array [points, SSres]
     */
    public function dataProviderForSumOfSquaresResidual()
    {
        return [
            [
                [ [1,2], [2,3], [4,5], [5,7], [6,8] ], // yhat = [1.82558139534879, 3.04651162790689, 5.48837209302309, 6.70930232558119, 7.93023255813929]
                0.36046511627907 // 0.03042184964848 + 0.00216333153055 + 0.23850730124375 + 0.0845051379125 + 0.00486749594379
            ],
            [
                [ [61,105], [62,120], [63,120], [65,160], [65,120], [68,145], [69,175], [70,160], [72,185], [75,210] ], // yhat = [108.1914893617, 115.15957446809, 122.12765957447, 136.06382978723, 136.06382978723, 156.96808510638, 163.93617021277, 170.90425531915, 184.84042553191, 205.74468085106]
                1271.80851063820534 // 10.18560434584427 + 23.42971932996621 + 4.52693526483386 + 572.94024445469783 + 258.04662743309783 + 143.23506111355478 + 122.40832956079783 + 118.90278406521107 + 0.02546401086621 + 18.10774105933545
            ],
        ];
    }

    /**
     * @test         The sum of squares of Y equals the sum of squares regression plus the sum of squares of error (residual)
     *               SStotal = SSreg + SSres
     * @dataProvider dataProviderForSumOfSquaresEqualsSumOfSQuaresRegressionPlusSumOfSquaresResidual
     * @param        array $points
     */
    public function testSumOfSquaresEqualsSumOfSQuaresRegressionPlusSumOfSquaresResidual(array $points)
    {
        // Given
        $regression = new Linear($points);

        // Wheb
        $SStot      = $regression->sumOfSquaresTotal();
        $SSreg      = $regression->sumOfSquaresRegression();
        $SSres      = $regression->sumOfSquaresResidual();

        // Then
        $this->assertEqualsWithDelta($SStot, $SSreg + $SSres, 0.001);
    }

    /**
     * @return array [points]
     */
    public function dataProviderForSumOfSquaresEqualsSumOfSQuaresRegressionPlusSumOfSquaresResidual(): array
    {
        return [
            [ [[1,2], [2,3], [4,5], [5,7], [6,8]] ],
            [ [[1,3], [2,6], [3,7], [4,11], [5,12], [6,13], [7,17]] ],
            [ [[61,105], [62,120], [63,120], [65,160], [65,120], [68,145], [69,175], [70,160], [72,185], [75,210]] ],
        ];
    }
}
