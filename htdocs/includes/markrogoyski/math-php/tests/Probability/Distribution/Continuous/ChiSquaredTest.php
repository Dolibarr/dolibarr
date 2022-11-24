<?php

namespace MathPHP\Tests\Probability\Distribution\Continuous;

use MathPHP\Probability\Distribution\Continuous\ChiSquared;

class ChiSquaredTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test         pdf
     * @dataProvider dataProviderForPdf
     * @param        float $x
     * @param        int $k
     * @param        float $pdf
     */
    public function testPdf(float $x, int $k, float $expectedPdf)
    {
        // Given
        $chiSquared = new ChiSquared($k);

        // When
        $pdf = $chiSquared->pdf($x);

        // Then
        $this->assertEqualsWithDelta($expectedPdf, $pdf, 0.00000001);
    }

    /**
     * @return array [x, k, pdf]
     * Generated with http://keisan.casio.com/exec/system/1180573196
     */
    public function dataProviderForPdf(): array
    {
        return [
            [0, 1, \INF],
            [1, 1, 0.2419707245191433497978],
            [2, 1, 0.1037768743551486758351],
            [3, 1, 0.05139344326792309227041],
            [4, 1, 0.02699548325659402597528],
            [5, 1, 0.0146449825619264871132],
            [6, 1, 0.008108695554940243370932],
            [7, 1, 0.004553342921640173367469],
            [8, 1, 0.002583373169261506732134],

            [0, 2, 0.5],
            [1, 2, 0.3032653298563167118019],
            [2, 2, 0.1839397205857211607978],
            [3, 2, 0.1115650800742149144666],
            [4, 2, 0.067667641618306345947],
            [5, 2, 0.04104249931194939758476],
            [6, 2, 0.02489353418393197148967],
            [7, 2, 0.01509869171115925036989],
            [8, 2, 0.009157819444367090146859],


            [0, 3, 0],
            [1, 3, 0.2419707245191433497978],
            [2, 3, 0.2075537487102973516701],
            [3, 3, 0.1541803298037692768112],
            [4, 3, 0.1079819330263761039011],
            [5, 3, 0.073224912809632435566],
            [6, 3, 0.04865217332964146022559],
            [7, 3, 0.03187340045148121357228],
            [8, 3, 0.02066698535409205385707],

            [0, 4, 0],
            [0, 5, 0],
            [1, 4, 0.151632664928158355901],
            [1, 5, 0.08065690817304778326594],
            [2, 3, 0.2075537487102973516701],

            [2.5, 1, 0.07228896],
            [2.5, 10, 0.01457239],
            [2.5, 15, 0.00032650],
            [6, 5, 0.09730435],
            [6, 20, 0.00135025],
            [17.66, 6, 0.00285129],
            [0.09, 6, 0.00048397],
        ];
    }

    /**
     * @test         cdf
     * @dataProvider dataProviderForCdf
     * @param        float $x
     * @param        int $k
     * @param        float $expectedCdf
     */
    public function testCdf(float $x, int $k, float $expectedCdf)
    {
        // Given
        $chiSquared = new ChiSquared($k);

        // When
        $cdf = $chiSquared->cdf($x);

        // Then
        $this->assertEqualsWithDelta($expectedCdf, $cdf, 0.000001);
    }

    /**
     * @return array [x, k, cdf]
     * Generated with http://keisan.casio.com/exec/system/1180573196
     */
    public function dataProviderForCdf(): array
    {
        return [
            [0, 1, 0],
            [1, 1, 0.6826894921370858971705],
            [2, 1, 0.8427007929497148693412],
            [3, 1, 0.9167354833364495981451],
            [4, 1, 0.9544997361036415855994],
            [5, 1, 0.9746526813225317360684],
            [6, 1, 0.9856941215645703604742],
            [7, 1, 0.991849028406497299687],
            [8, 1, 0.9953222650189527341621],

            [0, 2, 0],
            [1, 2, 0.3934693402873665763962],
            [2, 2, 0.6321205588285576784045],
            [3, 2, 0.7768698398515701710667],
            [4, 2, 0.864664716763387308106],
            [5, 2, 0.9179150013761012048305],
            [6, 2, 0.9502129316321360570207],
            [7, 2, 0.9698026165776814992602],
            [8, 2, 0.9816843611112658197063],

            [0, 3, 0],
            [1, 3, 0.1987480430987991975748],
            [2, 3, 0.427593295529120166001],
            [3, 3, 0.6083748237289110445226],
            [4, 3, 0.7385358700508893777972],
            [5, 3, 0.8282028557032668649364],
            [6, 3, 0.888389774905287440023],
            [7, 3, 0.9281022275035348725425],
            [8, 3, 0.9539882943107686264479],

            [0, 3, 0],
            [0, 4, 0],
            [0, 5, 0],
            [1, 4, 0.0902040104310498645943],
            [1, 5, 0.03743422675270363104292],

            [7.26, 15, 0.04997084177886489436453],
            [7.26, 12, 0.1600358165499511869596],
            [7.26, 1, 0.9929492708895772574712],
            [1, 30, 0],
            [1, 7, 0.005171463483484517736541],
            [4.6, 1, 0.9680280438223512895985],
            [4.6, 2, 0.8997411562771962662701],
            [4.6, 6, 0.4039611740679318029755],
            [4.6, 10, 0.0837507192794016330488],
        ];
    }

    /**
     * @test     mean is k
     */
    public function testMean()
    {
        // Given
        $k = 5;
        $chiSquared = new ChiSquared($k);

        // When
        $mean = $chiSquared->mean();

        // Then
        $this->assertEquals($k, $mean);
    }

    /**
     * @test         median
     * @dataProvider dataProviderForMedian
     * @param        float $k
     * @param        float $expected
     */
    public function testMedian(float $k, float $expected)
    {
        // Given
        $chiSquared = new ChiSquared($k);

        // When
        $median = $chiSquared->median();

        // Then
        $this->assertEqualsWithDelta($expected, $median, 0.00000001);
    }

    /**
     * @return array
     */
    public function dataProviderForMedian(): array
    {
        return [
            [1, 0.47050754458162],
            [2, 1.40466392318244],
            [3, 2.38149672306054],
            [4, 3.36968449931408],
            [5, 4.36252400548703],
            [20, 19.3407133058986],
        ];
    }

    /**
     * @test         mode
     * @dataProvider dataProviderForMode
     * @param        float $k
     * @param        float $expected
     */
    public function testMode(float $k, float $expected)
    {
        // Given
        $chiSquared = new ChiSquared($k);

        // When
        $mode = $chiSquared->mode();

        // Then
        $this->assertEqualsWithDelta($expected, $mode, 0.00000001);
    }

    /**
     * @return array
     */
    public function dataProviderForMode(): array
    {
        return [
            [1, 0],
            [2, 0],
            [3, 1],
            [4, 2],
            [5, 3],
            [20, 18],
        ];
    }

    /**
     * @test         variance
     * @dataProvider dataProviderForVariance
     * @param        float $k
     * @param        float $expected
     */
    public function testVariance(float $k, float $expected)
    {
        // Given
        $chiSquared = new ChiSquared($k);

        // When
        $variance = $chiSquared->variance();

        // Then
        $this->assertEqualsWithDelta($expected, $variance, 0.00000001);
    }

    /**
     * @return array
     */
    public function dataProviderForVariance(): array
    {
        return [
            [1, 2],
            [2, 4],
            [3, 6],
            [4, 8],
            [5, 10],
            [20, 40],
        ];
    }
}
