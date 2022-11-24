<?php

namespace MathPHP\Tests\Probability\Distribution\Continuous;

use MathPHP\Exception\OutOfBoundsException;
use MathPHP\Probability\Distribution\Continuous\Uniform;

class UniformTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test         constructor exception b < a
     * @dataProvider dataProviderForOutOfBoundsParameters
     * @param        float $a
     * @param        float $b
     * @throws       \Exception
     */
    public function testConstructorExceptionBLessThanA(float $a, float $b)
    {
        // Then
        $this->expectException(OutOfBoundsException::class);

        // When
        $uniform = new Uniform($a, $b);
    }

    /**
     * @return array [a, b]
     */
    public function dataProviderForOutOfBoundsParameters(): array
    {
        return [
            [1, 0],
            [2, 1],
            [4, 1],
            [94, 35],
        ];
    }

    /**
     * @test         pdf
     * @dataProvider dataProviderForPdf
     * @param        float $a
     * @param        float $b
     * @param        float $x
     * @param        float $expected
     * @throws       \Exception
     */
    public function testPdf(float $a, float $b, float $x, float $expected)
    {
        // Given
        $uniform = new Uniform($a, $b);

        // When
        $pdf = $uniform->pdf($x);

        // Then
        $this->assertEqualsWithDelta($expected, $pdf, 0.001);
    }

    /**
     * @return array [a, b, x, pdf]
     * Generated with R dunif(x, min, max)
     */
    public function dataProviderForPdf(): array
    {
        return [
            [0, 1, -1, 0],
            [0, 1, 0, 1],
            [0, 1, 0.5, 1],
            [0, 1, 1, 1],
            [0, 1, 2, 0],

            [0, 2, -1, 0],
            [0, 2, 0, 0.5],
            [0, 2, 0.5, 0.5],
            [0, 2, 1, 0.5],
            [0, 2, 2, 0.5],
            [0, 2, 3, 0],

            [1, 4, 2, 0.3333333],
            [1, 4, 3.4, 0.3333333],
            [1, 5.4, 3, 0.2272727],
            [1, 5.4, 0.3, 0],
            [1, 5.4, 6, 0],
        ];
    }

    /**
     * @test         cdf
     * @dataProvider dataProviderForCdf
     * @param        float $a
     * @param        float $b
     * @param        float $x
     * @param        float $expected
     * @throws       \Exception
     */
    public function testCdf(float $a, float $b, float $x, float $expected)
    {
        // Given
        $uniform = new Uniform($a, $b);

        // When
        $cdf = $uniform->cdf($x);

        // Then
        $this->assertEqualsWithDelta($expected, $cdf, 0.001);
    }

    /**
     * @return array [a, b, x, cdf]
     * Generated with R punif(x, min, max)
     */
    public function dataProviderForCdf(): array
    {
        return [
            [0, 1, -1, 0],
            [0, 1, 0, 0],
            [0, 1, 0.5, 0.5],
            [0, 1, 1, 1],
            [0, 1, 2, 1],

            [0, 2, -1, 0],
            [0, 2, 0, 0],
            [0, 2, 0.5, 0.25],
            [0, 2, 1, 0.5],
            [0, 2, 2, 1],
            [0, 2, 3, 1],

            [1, 4, 2, 0.3333333],
            [1, 4, 3.4, 0.8],
            [1, 5.4, 3, 0.4545455],
            [1, 5.4, 0.3, 0],
            [1, 5.4, 6, 1],
        ];
    }

    /**
     * @test         mean
     * @dataProvider dataProviderForMean
     * @param        float $a
     * @param        float $b
     * @param        float $μ
     * @throws       \Exception
     */
    public function testMean(float $a, float $b, float $μ)
    {
        // Given
        $uniform = new Uniform($a, $b);

        // When
        $mean = $uniform->mean();

        // Then
        $this->assertEqualsWithDelta($μ, $mean, 0.00001);
    }

    /**
     * @test         median
     * @dataProvider dataProviderForMean
     * @param        float $a
     * @param        float $b
     * @param        float $μ
     * @throws       \Exception
     */
    public function testMedian(float $a, float $b, float $μ)
    {
        // Given
        $uniform = new Uniform($a, $b);

        // When
        $median = $uniform->median();

        // Then
        $this->assertEqualsWithDelta($μ, $median, 0.00001);
    }

    /**
     * @test         mode
     * @dataProvider dataProviderForMean
     * @param        float $a
     * @param        float $b
     * @throws       \Exception
     */
    public function testMode(float $a, float $b)
    {
        // Given
        $uniform = new Uniform($a, $b);

        // When
        $mode = $uniform->mode();

        // Then
        $this->assertGreaterThanOrEqual($a, $mode);
        $this->assertLessThanOrEqual($b, $mode);
    }

    /**
     * @return array [a, b, μ]
     */
    public function dataProviderForMean(): array
    {
        return [
            [0, 1, 0.5],
            [0, 2, 1],
            [1, 2, 1.5],
            [2, 3, 5 / 2],
            [2, 4, 3],
            [5, 11, 8],
        ];
    }

    /**
     * @test         variance
     * @dataProvider dataProviderForVariance
     * @param        float $a
     * @param        float $b
     * @param        float $expected
     * @throws       \Exception
     */
    public function testVariance(float $a, float $b, float $expected)
    {
        // Given
        $uniform = new Uniform($a, $b);

        // When
        $variance = $uniform->variance();

        // Then
        $this->assertEqualsWithDelta($expected, $variance, 0.00001);
    }

    /**
     * @return array [a, b, var]
     */
    public function dataProviderForVariance(): array
    {
        return [
            [0, 1, 1 / 12],
            [0, 2, 4 / 12],
            [1, 2, 1 / 12],
            [2, 3, 1 / 12],
            [2, 4, 4 / 12],
            [5, 11, 3],
        ];
    }
}
