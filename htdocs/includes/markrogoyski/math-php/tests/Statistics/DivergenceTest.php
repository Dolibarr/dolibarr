<?php

namespace MathPHP\Tests\Statistics;

use MathPHP\Statistics\Divergence;
use MathPHP\Exception;

class DivergenceTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test         kullbackLeibler
     * @dataProvider dataProviderForKullbackLeibler
     * @param        array $p
     * @param        array $q
     * @param        float $expected
     */
    public function testKullbackLeibler(array $p, array $q, float $expected)
    {
        // When
        $BD = Divergence::kullbackLeibler($p, $q);

        // Then
        $this->assertEqualsWithDelta($expected, $BD, 0.0001);
    }

    /**
     * Test data created using Python's scipi.stats.Distance
     * @return array [p, q, distance]
     */
    public function dataProviderForKullbackLeibler(): array
    {
        return [
            [
                [0.5, 0.5],
                [0.75, 0.25],
                0.14384103622589045,
            ],
            [
                [0.75, 0.25],
                [0.5, 0.5],
                0.13081203594113694,
            ],
            [
                [0.2, 0.5, 0.3],
                [0.1, 0.4, 0.5],
                0.096953524639296684,
            ],
            [
                [0.4, 0.6],
                [0.3, 0.7],
                0.022582421084357374
            ],
            [
                [0.9, 0.1],
                [0.1, 0.9],
                1.7577796618689758
            ],
        ];
    }

    /**
     * @test kullbackLeibler when arrays are different lengths
     */
    public function testKullbackLeiblerExceptionArraysDifferentLength()
    {
        // Given
        $p = [0.4, 0.5, 0.1];
        $q = [0.2, 0.8];

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        Divergence::kullbackLeibler($p, $q);
    }

    /**
     * @test kullbackLeibler when probabilities do not add up to one
     */
    public function testKullbackLeiblerExceptionNotProbabilityDistributionThatAddsUpToOne()
    {
        // Given
        $p = [0.2, 0.2, 0.1];
        $q = [0.2, 0.4, 0.6];

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        Divergence::kullbackLeibler($p, $q);
    }

    /**
     * @test         jensenShannon
     * @dataProvider dataProviderForJensenShannonDivergence
     * @param        array $p
     * @param        array $q
     * @param        float $expected
     */
    public function testJensenShannonDivergence(array $p, array $q, float $expected)
    {
        // When
        $BD = Divergence::jensenShannon($p, $q);

        // Then
        $this->assertEqualsWithDelta($expected, $BD, 0.0001);
    }

    /**
     * Test data created with Python's numpy/scipi where p and q are numpy.arrays:
     * def jsd(p, q):
     *     M = (p + q) / 2
     *     return (scipy.stats.Distance(p, M) + scipy.stats.Distance(q, M)) / 2
     * @return array [p, q, distance]
     */
    public function dataProviderForJensenShannonDivergence(): array
    {
        return [
            [
                [0.4, 0.6],
                [0.5, 0.5],
                0.0050593899289876343,
            ],
            [
                [0.1, 0.2, 0.2, 0.2, 0.2, 0.1],
                [0.0, 0.1, 0.4, 0.4, 0.1, 0.0],
                0.12028442909461383
            ],
            [
                [0.25, 0.5, 0.25],
                [0.5, 0.3, 0.2],
                0.035262717451799902,
            ],
            [
                [0.5, 0.3, 0.2],
                [0.25, 0.5, 0.25],
                0.035262717451799902,
            ],
        ];
    }

    /**
     * @test jensenShannon when the arrays are different lengths
     */
    public function testJensenShannonDivergenceExceptionArraysDifferentLength()
    {
        // Given
        $p = [0.4, 0.5, 0.1];
        $q = [0.2, 0.8];

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        Divergence::jensenShannon($p, $q);
    }

    /**
     * @test jensenShannon when the probabilities do not add up to one
     */
    public function testJensenShannonDivergenceExceptionNotProbabilityDistributionThatAddsUpToOne()
    {
        // Given
        $p = [0.2, 0.2, 0.1];
        $q = [0.2, 0.4, 0.6];

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        Divergence::jensenShannon($p, $q);
    }
}
