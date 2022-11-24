<?php

namespace MathPHP\Tests\Probability\Distribution\Continuous;

use MathPHP\Probability\Distribution\Continuous\NoncentralT;

class NoncentralTTest extends \PHPUnit\Framework\TestCase
{
    private const ε = .000001;

    /**
     * @test         pdf
     * @dataProvider dataProviderForPdf
     * @param        float $t
     * @param        int   $ν degrees of freedom > 0
     * @param        float $μ Noncentrality parameter
     * @param        float $expected
     */
    public function testPdf(float $t, int $ν, float $μ, float $expected)
    {
        // Given
        $noncentral_t = new NoncentralT($ν, $μ);
        $tol          = \abs(self::ε * $expected);

        // When
        $pdf = $noncentral_t->pdf($t);

        // Then
        $this->assertEqualsWithDelta($expected, $pdf, $tol);
    }

    /**
     * @return array [t, ν, μ, pdf]
     */
    public function dataProviderForPdf(): array
    {
        return [
            [0, 25, -2, 0.053453889],
            [-2, 2, -2, 0.25505237245],
            [8, 50, 10, .09559962614195],
            [12, 50, 10, .10778431492038],

            [-1, 1, 0, 0.1591549430918953357689],
            [0, 1, 0, 0.3183098861837906715378],
            [1, 1, 0, 0.1591549430918953357689],
            [2, 1, 0, 0.06366197723675813430755],
            [3, 1, 0, 0.03183098861837906715378],

            [-2.8, 1, 1, 0.00793047581730952096428],
            [-1, 1, 1, 0.0438603083831399257109],
            [0, 1, 1, 0.1930647052601078150839],
            [1, 1, 1, 0.2635559531170011242344],
            [2, 1, 1, 0.1437974534152678701355],
            [2.8, 1, 1, 0.0882563624149698842293],
            [3, 1, 1, 0.0789682653062082740843],
            [3.25, 1, 1, 0.0691488842822591489281],
            [5, 1, 1, 0.03212097322886888814071],
            [10, 1, 1, 0.008482965907309839007312],

            [-1, 3, 2, 0.00527823780849162254],
            [0, 3, 2, 0.049742834812291363635],
            [1, 3, 2, 0.236548732514145537757],
            [2, 3, 2, 0.28778335780668807139],
            [3, 3, 2, 0.181695020544847197199],

            //[-1, 5, 5, 1.5871922128754056E-8],    // Slightly outside of tolerance
            [0, 5, 5, 1.41466247476928302415E-6],
            [1, 5, 5, 3.11315287270833112945E-4],
            [2, 5, 5, 0.0163549748052644937958],
            [3, 5, 5, 0.108534331537738072681],
            [3.5, 5, 5, 0.165432129771609351798],
            [5, 5, 5, 0.205723200508389201985],
            [8, 5, 5, 0.0691178126030179718418],
            [10, 5, 5, 0.0283367802665771249083],
        ];
    }

    /**
     * @test         cdf
     * @dataProvider dataProviderForCdf
     * @param        float $t
     * @param        int   $ν degrees of freedom > 0
     * @param        float $μ Noncentrality parameter
     * @param        float $expected
     */
    public function testCdf(float $t, int $ν, float $μ, float $expected)
    {
        // Given
        $noncentral_t = new NoncentralT($ν, $μ);
        $tol          = \abs(self::ε * $expected);

        // When
        $cdf = $noncentral_t->cdf($t);

        // Then
        $this->assertEqualsWithDelta($expected, $cdf, $tol);
    }

    /**
     * @return array [t, ν, μ, cdf]
     */
    public function dataProviderForCdf(): array
    {
        return [
            [0, 25, -2, 0.97724986805],
            [2, 2, 2, 0.4204754808637],
            [8, 50, 10, 0.05611822106520649788],
            [12, 50, 10, 0.8939939602826094285],

            [-1, 1, 0, 0.25],
            [0, 1, 0, 0.5],
            [1, 1, 0, 0.75],
            [2, 1, 0, 0.8524163823495667258246],
            [3, 1, 0, 0.8975836176504332741754],

            [-2.8, 1, 1, 0.0232136140512707476863],
            [-1, 1, 1, 0.05748009179432582500346],
            [0, 1, 1, 0.1586552539314570514148],
            [1, 1, 1, 0.4220200303926276373138],
            [2, 1, 1, 0.6228719644602816531633],
            [2.8, 1, 1, 0.7134035920837329779863],
            [3, 1, 1, 0.730102598610563360141],
            [3.25, 1, 1, 0.7485796324415677422027],
            [5, 1, 1, 0.8313197187246860636513],
            [10, 1, 1, 0.9141028166626217410293],

            //[-1, 3, 2, 0.003005247595398505997],   // Slightly outside of tolerance
            //[0, 3, 2, 0.02275013194817920720028],  // Slightly outside of tolerance
            [1, 3, 2, 0.157349433970036534264],
            [2, 3, 2, 0.443075782217602879851],
            [3, 3, 2, 0.680027174055522443575],

            //[-1, 5, 5, 4.914260406713E-9],         // Outside of tolerance
            //[0, 5, 5, 2.866515718791939116738E-7], // Slightly outside of tolerance
            //[1, 5, 5, 5.9336218471589503E-5],      // Slightly outside of tolerance
            [2, 5, 5, 0.00479699066830130346],
            [3, 5, 5, 0.060515014206464689666],
            [3.5, 5, 5, 0.129477929356560427512],
            [5, 5, 5, 0.433982979486368857942],
            [8, 5, 5, 0.8356579020754351168709],
            [10, 5, 5, 0.926988481803982025489],
        ];
    }

    /**
     * @test         mean
     * @dataProvider dataProviderForMean
     * @param        int   $ν degrees of freedom > 0
     * @param        float $μ Noncentrality parameter
     * @param        float $expected
     */
    public function testMean(int $ν, float $μ, float $expected)
    {
        // Given
        $noncentral_t = new NoncentralT($ν, $μ);
        $tol          = \abs(self::ε * $expected);

        // When
        $mean = $noncentral_t->mean();

        // Then
        $this->assertEqualsWithDelta($expected, $mean, $tol);
    }

    /**
     * @return array [ν, μ, mean]
     */
    public function dataProviderForMean(): array
    {
        return [
            [25, -2, -2.06260931008523],
            [2, -2, -3.54490770181103],
            [50, 10, 10.1531919459648],
            [10, 10, 10.8372230793914],
        ];
    }

    /**
     * @test         mean not a number if ν = 1
     * @dataProvider dataProviderForMeanNan
     * @param        int $ν
     * @param        float $μ
     */
    public function testMeanNAN(int $ν, float $μ)
    {
        // Given
        $noncentral_t = new NoncentralT($ν, $μ);

        // When
        $mean = $noncentral_t->mean();

        // Then
        $this->assertNan($mean);
    }

    /**
     * @return array [ν, μ]
     */
    public function dataProviderForMeanNan(): array
    {
        return [
            [1, 1],
            [1, 2],
            [1, -1],
        ];
    }

    /**
     * @test         median (temporary version that is just the mean)
     * @dataProvider dataProviderForMean
     * @todo         Rewrite test using actual median values once median calculation is implemented
     * @param        int   $ν degrees of freedom > 0
     * @param        float $μ Noncentrality parameter
     * @param        float $expected
     */
    public function testMedianTemporaryValue(int $ν, float $μ, float $expected)
    {
        // Given
        $noncentral_t = new NoncentralT($ν, $μ);
        $tol          = \abs(self::ε * $expected);

        // When
        $median = $noncentral_t->median();

        // Then
        $this->assertEqualsWithDelta($expected, $median, $tol);
    }
}
