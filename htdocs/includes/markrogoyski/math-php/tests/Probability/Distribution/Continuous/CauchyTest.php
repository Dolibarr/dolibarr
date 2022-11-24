<?php

namespace MathPHP\Tests\Probability\Distribution\Continuous;

use MathPHP\Probability\Distribution\Continuous\Cauchy;

class CauchyTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test         pdf
     * @dataProvider dataProviderForPdf
     * @param        float $x
     * @param        float $x₀
     * @param        float $γ
     * @param        float $expected_pdf
     */
    public function testPdf(float $x, float $x₀, float $γ, float $expected_pdf)
    {
        // Given
        $cauchy = new Cauchy($x₀, $γ);

        // When
        $pdf = $cauchy->pdf($x);

        // Then
        $this->assertEqualsWithDelta($expected_pdf, $pdf, 0.000000001);
    }

    /**
     * @return array [x, x₀, γ, pdf]
     * Generated with http://keisan.casio.com/exec/system/1180573169
     */
    public function dataProviderForPdf(): array
    {
        return [
            [1, 0, 1, 0.1591549430918953357689],
            [1, 0, 2, 0.1273239544735162686151],
            [1, 0, 3, 0.09549296585513720146133],
            [1, 0, 4, 0.07489644380795074624418],
            [1, 0, 5, 0.06121343965072897529573],
            [1, 0, 6, 0.05161781938115524403315],

            [0, 1, 1, 0.1591549430918953357689],
            [0, 1, 2, 0.1273239544735162686151],
            [0, 1, 3, 0.09549296585513720146133],
            [0, 1, 4, 0.07489644380795074624418],
            [0, 1, 5, 0.06121343965072897529573],
            [0, 1, 6, 0.05161781938115524403315],

            [1, 1, 1, 0.3183098861837906715378],
            [2, 3, 4, 0.07489644380795074624418],
            [4, 3, 2, 0.1273239544735162686151],
            [5, 5, 5, 0.06366197723675813430755],

            [-20, 7.3, 4.3, 0.001792050735277566691472],
            [-3, 7.3, 4.3, 0.01098677565090945486926],
            [-2, 7.3, 4.3, 0.01303803115441322049545],
            [-1, 7.3, 4.3, 0.01566413951236323973006],
            [0, 7.3, 4.3, 0.01906843843118277915314],
            [1, 7.3, 4.3, 0.02352582520780852333469],
            [2, 7.3, 4.3, 0.0293845536837762964279],
            [3, 7.3, 4.3, 0.03701277746323147343462],
            [20, 7.3, 4.3, 0.007613374739071642494229],
        ];
    }

    /**
     * @test         cdf
     * @dataProvider dataProviderForCdf
     * @param        float $x
     * @param        float $x₀
     * @param        float $γ
     * @param        float $expected_cdf
     */
    public function testCdf(float $x, float $x₀, float $γ, float $expected_cdf)
    {
        // Given
        $cauchy = new Cauchy($x₀, $γ);

        // When
        $cdf = $cauchy->cdf($x);

        // Then
        $this->assertEqualsWithDelta($expected_cdf, $cdf, 0.000000001);
    }

    /**
     * @test         inverse of CDF is original support x
     * @dataProvider dataProviderForCdf
     * @param        float $x
     * @param        float $x₀
     * @param        float $γ
     */
    public function testInverseOfCdf(float $x, float $x₀, float $γ)
    {
        // Given
        $cauchy = new Cauchy($x₀, $γ);
        $cdf = $cauchy->cdf($x);

        // When
        $inverse_of_cdf = $cauchy->inverse($cdf);

        // Then
        $this->assertEqualsWithDelta($x, $inverse_of_cdf, 0.000000001);
    }

    /**
     * @return array [x, x₀, γ, cdf]
     * Generated with http://keisan.casio.com/exec/system/1180573169
     */
    public function dataProviderForCdf(): array
    {
        return [
            [1, 0, 1, 0.75],
            [1, 0, 2, 0.6475836176504332741754],
            [1, 0, 3, 0.6024163823495667258246],
            [1, 0, 4, 0.5779791303773693254605],
            [1, 0, 5, 0.5628329581890011838138],
            [1, 0, 6, 0.5525684567112534299508],

            [0, 1, 1, 0.25],
            [0, 1, 2, 0.3524163823495667258246],
            [0, 1, 3, 0.3975836176504332741754],
            [0, 1, 4, 0.4220208696226306745395],
            [0, 1, 5, 0.4371670418109988161863],
            [0, 1, 6, 0.4474315432887465700492],

            [1, 1, 1, 0.5],
            [2, 3, 4, 0.4220208696226306745395],
            [4, 3, 2, 0.6475836176504332741754],
            [5, 5, 5, 0.5],

            [-20, 7.3, 4.3, 0.04972817023155424541129],
            [-3, 7.3, 4.3, 0.1258852891111436766445],
            [-2, 7.3, 4.3, 0.1378566499474175095298],
            [-1, 7.3, 4.3, 0.1521523453170898354801],
            [0, 7.3, 4.3, 0.1694435179635968563959],
            [1, 7.3, 4.3, 0.1906393755555404651458],
            [2, 7.3, 4.3, 0.2169618719223694455636],
            [3, 7.3, 4.3, 0.25],
            [20, 7.3, 4.3, 0.8960821670587991836005],
        ];
    }

    /**
     * @dataProvider dataProviderForInverse
     * @param float $p
     * @param float $x₀
     * @param float $γ
     * @param float $expected_inverse
     */
    public function testInverse(float $p, float $x₀, float $γ, float $expected_inverse)
    {
        // Given
        $cauchy = new Cauchy($x₀, $γ);

        // When
        $inverse = $cauchy->inverse($p);

        // Then
        $this->assertEqualsWithDelta($expected_inverse, $inverse, 0.000001);
    }

    /**
     * @return array [$p, $x₀, $γ, expected_inverse]
     * Generated with R (stats) qcauchy(p, location, scale)
     */
    public function dataProviderForInverse(): array
    {
        return [
            [0.1, 1, 1, -2.077684],
            [0.3, 1, 1, 0.2734575],
            [0.5, 1, 1, 1],
            [0.7, 1, 1, 1.726543],
            [0.9, 1, 1, 4.077684],

            [0.1, 2, 3, -7.233051],
            [0.3, 2, 3, -0.1796276],
            [0.5, 2, 3, 2],
            [0.7, 2, 3, 4.179628],
            [0.9, 2, 3, 11.23305],
        ];
    }

    /**
     * @test         mean is not a number
     * @dataProvider dataProviderForAverages
     * @param        float $x₀
     * @param        float $γ
     */
    public function testMean(float $x₀, float $γ)
    {
        // Given
        $cauchy = new Cauchy($x₀, $γ);

        // When
        $mean = $cauchy->mean();

        // Then
        $this->assertNan($mean);
    }

    /**
     * @test         median is $x₀
     * @dataProvider dataProviderForAverages
     * @param        float $x₀
     * @param        float $γ
     */
    public function testMedian(float $x₀, float $γ)
    {
        // Given
        $cauchy = new Cauchy($x₀, $γ);

        // When
        $median = $cauchy->median();

        // Then
        $this->assertEquals($x₀, $median);
    }

    /**
     * @test         mode is $x₀
     * @dataProvider dataProviderForAverages
     * @param        float $x₀
     * @param        float $γ
     */
    public function testMode(float $x₀, float $γ)
    {
        // Given
        $cauchy = new Cauchy($x₀, $γ);

        // When
        $mode = $cauchy->mode();

        // Then
        $this->assertEquals($x₀, $mode);
    }

    /**
     * @test         variance is not a number
     * @dataProvider dataProviderForAverages
     * @param        float $x₀
     * @param        float $γ
     */
    public function testVariance(float $x₀, float $γ)
    {
        // Given
        $cauchy = new Cauchy($x₀, $γ);

        // When
        $variance = $cauchy->variance();

        // Then
        $this->assertNan($variance);
    }

    /**
     * @return array [x₀, γ]
     */
    public function dataProviderForAverages(): array
    {
        return [
            [-1, 0.1],
            [-1, 1],
            [-1, 2],
            [0, 0.1],
            [0, 1],
            [0, 2],
            [1, 0.1],
            [1, 1],
            [1, 2],
            [2, 3],
            [5, 3],
        ];
    }

    /**
     * @test rand
     */
    public function testRand()
    {
        foreach (\range(-5, 5) as $x₀) {
            foreach (\range(1, 10) as $γ) {
                // Given
                $cauchy = new Cauchy($x₀, $γ);
                foreach (\range(1, 3) as $_) {
                    // When
                    $random = $cauchy->rand();

                    // Then
                    $this->assertTrue(\is_numeric($random));
                }
            }
        }
    }
}
