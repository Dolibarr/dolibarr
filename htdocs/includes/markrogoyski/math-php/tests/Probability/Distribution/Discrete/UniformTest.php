<?php

namespace MathPHP\Tests\Probability\Distribution\Discrete;

use MathPHP\Probability\Distribution\Discrete\Uniform;
use MathPHP\Exception;

class UniformTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test         pmf returns the expected probability
     * @dataProvider dataProviderForPmf
     * @param        int   $a
     * @param        int   $b
     * @param        float $expectedPmf
     * @throws       \Exception
     */
    public function testPmf(int $a, int $b, float $expectedPmf)
    {
        // Given
        $uniform = new Uniform($a, $b);

        // When
        $pmf = $uniform->pmf();

        // Then
        $this->assertEqualsWithDelta($expectedPmf, $pmf, 0.001);
    }

    /**
     * @return array
     */
    public function dataProviderForPmf(): array
    {
        return [
            [1, 2, 0.5],
            [1, 3, 0.33333],
            [1, 4, 0.25],
            [1, 5, 0.2],
        ];
    }

    /**
     * @test     constructor throws a BadDataException if b is < a
     */
    public function testConstructorException()
    {
        // Given
        $a = 4;
        $b = 1;

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        $uniform = new Uniform($a, $b);
    }

    /**
     * @test         cdf returns the expected cumulative probability
     * @dataProvider dataProviderForCdf
     * @param        int   $a
     * @param        int   $b
     * @param        float $expectedCdf
     * @throws       \Exception
     */
    public function testCdf(int $a, int $b, $k, float $expectedCdf)
    {
        // Given
        $uniform = new Uniform($a, $b);

        // When
        $cdf = $uniform->cdf($k);

        // Then
        $this->assertEqualsWithDelta($expectedCdf, $cdf, 0.001);
    }

    /**
     * @return array
     */
    public function dataProviderForCdf(): array
    {
        return [
            [1, 4, 0, 0],
            [1, 4, 1, 1 / 4],
            [1, 4, 2, 2 / 4],
            [1, 4, 3, 3 / 4],
            [1, 4, 4, 4 / 4],
            [1, 4, 5, 1],
        ];
    }

    /**
     * @test         mean
     * @dataProvider dataProviderForAverage
     * @param        int   $a
     * @param        int   $b
     * @param        float $expectedMean
     * @throws       \Exception
     */
    public function testMean(int $a, int $b, float $expectedMean)
    {
        // Given
        $uniform = new Uniform($a, $b);

        // When
        $mean = $uniform->mean();

        // Then
        $this->assertEqualsWithDelta($expectedMean, $mean, 0.0001);
    }

    /**
     * @test         median
     * @dataProvider dataProviderForAverage
     * @param        int   $a
     * @param        int   $b
     * @param        float $expectedMedian
     * @throws       \Exception
     */
    public function testMedian(int $a, int $b, float $expectedMedian)
    {
        // Given
        $uniform = new Uniform($a, $b);

        // When
        $median = $uniform->median();

        // Then
        $this->assertEqualsWithDelta($expectedMedian, $median, 0.0001);
    }

    /**
     * @return array
     */
    public function dataProviderForAverage(): array
    {
        return [
            [1, 2, 3 / 2],
            [1, 3, 4 / 2],
            [1, 4, 5 / 2],
        ];
    }

    /**
     * @test         variance
     * @dataProvider dataProviderForVariance
     * @param        int   $a
     * @param        int   $b
     * @param        float $expectedVariance
     * @throws       \Exception
     */
    public function testVariance(int $a, int $b, float $expectedVariance)
    {
        // Given
        $uniform = new Uniform($a, $b);

        // When
        $variance = $uniform->variance();

        // Then
        $this->assertEqualsWithDelta($expectedVariance, $variance, 0.0001);
    }

    /**
     * @return array
     */
    public function dataProviderForVariance(): array
    {
        return [
            [1, 2, 0.25],
            [1, 3, 0.66666666666667],
            [1, 4, 1.25],
            [2, 4, 0.66666666666667],
        ];
    }
}
