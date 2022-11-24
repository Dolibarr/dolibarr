<?php

namespace MathPHP\Tests\Probability\Distribution\Table;

use MathPHP\Probability\Distribution\Table\StandardNormal;
use MathPHP\Exception;

class StandardNormalTableTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test         z score
     * @dataProvider dataProviderForZScores
     * @param        float $Z
     * @param        float $Φ
     * @throws       \Exception
     */
    public function testGetZScoreProbability(float $Z, float $Φ)
    {
        // When
        $score = StandardNormal::getZScoreProbability($Z);

        // Then
        $this->assertEqualsWithDelta($Φ, $score, 0.0001);
    }

    public function dataProviderForZScores(): array
    {
        return [
            [ 0, 0.5000 ], [ 0.01, 0.5040 ], [ 0.02, 0.5080 ],
            [ 0.30, 0.6179 ], [ 0.31, 0.6217 ], [ 0.39, 0.6517 ],
            [ 2.90, 0.9981 ], [ 2.96, 0.9985 ], [ 3.09, 0.9990 ],
            [ -0, 0.5000 ], [ -0.01, 0.4960 ], [ -0.02, 0.4920 ],
            [ -0.30, 0.3821 ], [ -0.31, 0.3783 ], [ -0.39, 0.3483 ],
            [ -2.90, 0.0019 ], [ -2.96, 0.0015 ], [ -3.09, 0.0010 ],
        ];
    }

    /**
     * @test   bad format exception
     * @throws \Exception
     */
    public function testGetZScoreProbabilityExceptionZBadFormat()
    {
        // Then
        $this->expectException(Exception\BadParameterException::class);

        // When
        StandardNormal::getZScoreProbability('12.34');
    }

    /**
     * @test         confidence interval score
     * @dataProvider dataProviderForZScoresForConfidenceInterval
     * @param        mixed $cl
     * @param        float  $Z
     * @throws       \Exception
     */
    public function testGetZScoreForConfidenceInterval($cl, float $Z)
    {
        // When
        $score = StandardNormal::getZScoreForConfidenceInterval($cl);

        // Then
        $this->assertEqualsWithDelta($Z, $score, 0.01);
    }

    public function dataProviderForZScoresForConfidenceInterval(): array
    {
        return [
            [50, 0.67449],
            [95, 1.95996],
            [99, 2.57583],
            ['99.5', 2.81],
            ['99.9', 3.29053],
        ];
    }

    /**
     * @test   confidence interval invalid exception
     * @throws \Exception
     */
    public function testGetZScoreForConfidenceIntervalInvalidConfidenceLevel()
    {
        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        StandardNormal::getZScoreForConfidenceInterval(12);
    }
}
