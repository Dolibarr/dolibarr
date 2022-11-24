<?php

namespace MathPHP\Tests\Statistics;

use MathPHP\Statistics\EffectSize;
use MathPHP\Exception;

class EffectSizeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test         etaSquared
     * @dataProvider dataProviderForEtaSquared
     * @param        float $SSt
     * @param        float $SST
     * @param        float $expected
     */
    public function testEtaSquared(float $SSt, float $SST, float $expected)
    {
        // When
        $η² = EffectSize::etaSquared($SSt, $SST);

        // Then
        $this->assertEqualsWithDelta($expected, $η², 0.0000000001);
    }

    /**
     * @return array [SSt, SST, expected]
     */
    public function dataProviderForEtaSquared(): array
    {
        return [
            // Test data: http://www.statisticshowto.com/eta-squared/
            [4.08, 62.29, 0.06550008026971],
            [9.2, 62.29, 0.14769625943169],
            [19.54, 62.29, 0.31369401187992],
            // Test data: http://wilderdom.com/research/ExampleCalculationOfEta-SquaredFromSPSSOutput.pdf
            [4.412, 301.70, 0.01462379847531],
            [26.196, 301.70, 0.08682797480941],
            [0.090, 301.70, 0.00029830957905],
            [271.2, 301.70, 0.89890619821014],
            // Test data: http://www.uccs.edu/lbecker/glm_effectsize.html
            [24, 610, 0.03934426229508],
            [112, 610, 0.18360655737705],
            [144, 610, 0.23606557377049],
        ];
    }

    /**
     * @test         partialEtaSquared
     * @dataProvider dataProviderForPartialEtaSquared
     * @param        float $SSt
     * @param        float $SSE
     * @param        float $expected
     */
    public function testPartialEtaSquared(float $SSt, float $SSE, float $expected)
    {
        // When
        $η²p = EffectSize::partialEtaSquared($SSt, $SSE);

        // Then
        $this->assertEqualsWithDelta($expected, $η²p, 0.000000001);
    }

    /**
     * @return array [SSt, SSE, expected]
     */
    public function dataProviderForPartialEtaSquared(): array
    {
        return [
            // Test data: http://jalt.org/test/bro_28.htm
            [158.372, 3068.553, 0.049078302],
            [0.344, 3003.548, 0.000114518],
            [137.572, 3003.548, 0.043797116],
            // Test data: http://www.uccs.edu/lbecker/glm_effectsize.html
            [24, 330, 0.06779661016949],
            [112, 330, 0.25339366515837],
            [144, 330, 0.30379746835443],
        ];
    }

    /**
     * @test         omegaSquared
     * @dataProvider dataProviderForOmegaSquared
     * @param        float $SSt
     * @param        int   $dft
     * @param        float $SST
     * @param        float $MSE
     * @param        float $expected
     */
    public function testOmegaSquared(float $SSt, int $dft, float $SST, float $MSE, float $expected)
    {
        // When
        $ω² = EffectSize::omegaSquared($SSt, $dft, $SST, $MSE);

        // Then
        $this->assertEqualsWithDelta($expected, $ω², 0.000001);
    }

    /**
     * @return array [SSt, dft, SST, MSE, expected]
     */
    public function dataProviderForOmegaSquared(): array
    {
        return [
            // Test data: http://www.uccs.edu/lbecker/glm_effectsize.html
            [24, 1, 610, 18.333, 0.00901910292791],
            [112, 2, 610, 18.333, 0.11989502381699],
            [144, 2, 610, 18.333, 0.17082343279758],
        ];
    }

    /**
     * @test         cohensF
     * @dataProvider dataProviderForCohensF
     * @param        float $measure_of_variance_explained
     * @param        float $expected
     */
    public function testCohensF(float $measure_of_variance_explained, float $expected)
    {
        // When
        $ƒ² = EffectSize::cohensF($measure_of_variance_explained);

        // Then
        $this->assertEqualsWithDelta($expected, $ƒ², 0.0000001);
    }

    /**
     * @return array [measure of variance explained, expected]
     */
    public function dataProviderForCohensF(): array
    {
        return [
            [0.06550008026971, 0.07009104964783],
            [0.01462379847531, 0.01484082774953],
            [0.18360655737705, 0.22489959839358],
            [0.00901910292791, 0.00910118747451],
            [0.25, 0.33333333],
            [0.00001, 0.000010],
            [0.99999, 99999.00000046],
        ];
    }

    /**
     * @test         cohensQ
     * @dataProvider dataProviderForCohensQ
     * @param        float $r₁
     * @param        float $r₂
     * @param        float $expected
     */
    public function testCohensQ(float $r₁, float $r₂, float $expected)
    {
        // When
        $q = EffectSize::cohensQ($r₁, $r₂);

        // Then
        $this->assertEqualsWithDelta($expected, $q, 0.001);
    }

    /**
     * @return array  [r₁, r₂, expected]
     */
    public function dataProviderForCohensQ(): array
    {
        return [
            [0.1, 0.1, 0],
            [0.5, 0.5, 0],
            [0.1, 0.2, 0.102],
            [0.2, 0.1, 0.102],
            [0.1, 0.5, 0.449],
            [0.1, 0.9, 1.372],
            [0.1, 0, 0.1],
            [0.1, -0.1, 0.201],
        ];
    }

    /**
     * @test     cohensQ R out of bounds
     */
    public function testCohensQExceptionROutOfBounds()
    {
        // Then
        $this->expectException(Exception\OutOfBoundsException::class);

        // When
        EffectSize::cohensQ(0.1, 2);
    }

    /**
     * @test         cohensD
     * @dataProvider dataProviderForCohensD
     * @param        float $μ₁
     * @param        float $μ₂
     * @param        float $s₁
     * @param        float $s₂
     * @param        float $expected
     */
    public function testCohensD(float $μ₁, float $μ₂, float $s₁, float $s₂, float $expected)
    {
        // When
        $d = EffectSize::cohensD($μ₁, $μ₂, $s₁, $s₂);

        // Then
        $this->assertEqualsWithDelta($expected, $d, 0.00001);
    }

    /**
     * @return array [μ₁, μ₂, s₁, s₂, expected]
     */
    public function dataProviderForCohensD(): array
    {
        return [
            // Test data: http://www.uccs.edu/~lbecker/
            [3, 3, 1.5811388300842, 1.5811388300842, 0],
            [3, 4, 1.5811388300842, 1.5811388300842, -0.6324555320336718],
            [6, 4.9166666666667, 1.5954480704349, 2.5030284687058, 0.5161479565960618],
            [40, 57.727272727273, 21.275964529644, 30.763910379179, -0.6702470286592815],
            [6.7, 6, 1.2, 1, 0.6337502222976299],
            [9, 3.5, 1.2, 1.5, 4.049155956077707],
            [108, 118, 15, 14.83239697419133, -0.6704015],
        ];
    }

    /**
     * @test         hedgesG
     * @dataProvider dataProviderForHedgesG
     * @param        float $μ₁
     * @param        float $μ₂
     * @param        float $s₁
     * @param        float $s₂
     * @param        int   $n₁
     * @param        int   $n₂
     * @param        float $expected
     */
    public function testHedgesG(float $μ₁, float $μ₂, float $s₁, float $s₂, int $n₁, int $n₂, float $expected)
    {
        // When
        $g = EffectSize::hedgesG($μ₁, $μ₂, $s₁, $s₂, $n₁, $n₂);

        // Then
        $this->assertEqualsWithDelta($expected, $g, 0.00001);
    }

    /**
     * @return array [μ₁, μ₂, s₁, s₂, n₁, n₂, expected]
     */
    public function dataProviderForHedgesG(): array
    {
        return [
            // Test data: http://www.polyu.edu.hk/mm/effectsizefaqs/calculator/calculator.html
            [3, 3, 1.5811388300842, 1.5811388300842, 5, 5, 0],
            [3, 4, 1.5811388300842, 1.5811388300842, 5, 5, -0.57125016],
            [6, 4.9166666666667, 1.5954480704349, 2.5030284687058, 12, 12, 0.49834975],
            [40, 57.727272727273, 21.275964529644, 30.763910379179, 7, 11, -0.61190744],
            [6.7, 6, 1.2, 1, 15, 15, 0.61662184],
            [6.7, 6, 1.2, 1, 16, 15, 0.61530752],
            [6.7, 6, 1.2, 1, 45, 15, 0.59824169],
            [9, 3.5, 1.2, 1.5, 13, 15, 3.89844347],
            [108, 118, 15, 14.83239697419133, 21, 18, -0.65642092],
        ];
    }

    /**
     * @test         glassDelta
     * @dataProvider dataProviderForGlassDelta
     * @param        float $μ₁
     * @param        float $μ₂
     * @param        float $s₂
     * @param        float $expected
     */
    public function testGlassDelta(float $μ₁, float $μ₂, float $s₂, float $expected)
    {
        // When
        $Δ = EffectSize::glassDelta($μ₁, $μ₂, $s₂);

        // Then
        $this->assertEqualsWithDelta($expected, $Δ, 0.00001);
    }

    /**
     * @return array [μ₁, μ₂, s₂, expected]
     */
    public function dataProviderForGlassDelta(): array
    {
        return [
            [40, 57.727272727273, 30.763910379179, -0.57623600],
            [3, 4, 1.5811388300842, -0.63245553],
            [3, 3, 1.5, 0],
        ];
    }
}
