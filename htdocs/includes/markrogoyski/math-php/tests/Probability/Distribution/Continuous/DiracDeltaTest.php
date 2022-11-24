<?php

namespace MathPHP\Tests\Probability\Distribution\Continuous;

use MathPHP\Probability\Distribution\Continuous\DiracDelta;

class DiracDeltaTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test         pdf
     * @dataProvider dataProviderForPdf
     * @param        float $x
     * @param        float $expectedPdf
     */
    public function testPdf(float $x, float $expectedPdf)
    {
        // Given
        $dirac = new DiracDelta();

        // When
        $pdf = $dirac->pdf($x);

        // Then
        $this->assertEquals($expectedPdf, $pdf);
    }

    /**
     * @return array [x, pdf]
     */
    public function dataProviderForPdf(): array
    {
        return [
            [-100, 0],
            [-12, 0],
            [-2, 0],
            [-1, 0],
            [-0.5, 0],
            [0, \INF],
            [0.5, 0],
            [1, 0],
            [2, 0],
            [12, 0],
            [100, 0],
        ];
    }
    /**
     * @testCase     cdf
     * @dataProvider dataProviderForCdf
     * @param        float $x
     * @param        int   $expectedCdf
     */
    public function testCdf(float $x, int $expectedCdf)
    {
        // Given
        $dirac = new DiracDelta();

        // When
        $cdf = $dirac->cdf($x);

        // Then
        $this->assertSame($expectedCdf, $cdf);
    }

    /**
     * @return array [x, cdf]
     */
    public function dataProviderForCdf(): array
    {
        return [
            [-100, 0],
            [-12, 0],
            [-2, 0],
            [-1, 0],
            [-0.5, 0],
            [0, 1],
            [0.5, 1],
            [1, 1],
            [2, 1],
            [12, 1],
            [100, 1],
        ];
    }

    /**
     * @testCase inverse is always 0
     */
    public function testInverse()
    {
        // Given
        $diracDelta = new DiracDelta();

        foreach (\range(-10, 10, 0.5) as $p) {
            // When
            $inverse = $diracDelta->inverse($p);

            // Then
            $this->assertEquals(0, $inverse);
        }
    }

    /**
     * @testCase rand is always 0
     */
    public function testRand()
    {
        // Given
        $diracDelta = new DiracDelta();

        foreach (\range(-10, 10, 0.5) as $_) {
            // When
            $rand = $diracDelta->rand();

            // Then
            $this->assertEquals(0, $rand);
        }
    }

    /**
     * @testCase mean is always 0
     */
    public function testMean()
    {
        // Given
        $diracDelta = new DiracDelta();

        foreach (\range(-10, 10, 0.5) as $_) {
            // When
            $mean = $diracDelta->mean();

            // Then
            $this->assertEquals(0, $mean);
        }
    }

    /**
     * @testCase median is always 0
     */
    public function testMedian()
    {
        // Given
        $diracDelta = new DiracDelta();

        foreach (\range(-10, 10, 0.5) as $_) {
            // When
            $median = $diracDelta->median();

            // Then
            $this->assertEquals(0, $median);
        }
    }

    /**
     * @testCase mode is always 0
     */
    public function testMode()
    {
        // Given
        $diracDelta = new DiracDelta();

        foreach (\range(-10, 10, 0.5) as $_) {
            // When
            $mode = $diracDelta->mode();

            // Then
            $this->assertEquals(0, $mode);
        }
    }
}
