<?php

namespace MathPHP\Tests\Statistics;

use MathPHP\Statistics\Distribution;

class DistributionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test         frequency
     * @dataProvider dataProviderForFrequency
     * @param        array $values
     * @param        array $expected
     */
    public function testFrequency(array $values, array $expected)
    {
        // When
        $frequencies = Distribution::frequency($values);

        // Then
        $this->assertEquals($expected, $frequencies);
    }

    /**
     * @return array [values, frequencies]
     */
    public function dataProviderForFrequency(): array
    {
        return [
            [
                [ 'A', 'A', 'B', 'B', 'B', 'B', 'C', 'C', 'D', 'F' ],
                [ 'A' => 2, 'B' => 4, 'C' => 2, 'D' => 1, 'F' => 1 ],
            ],
            [
                [ 1, 1, 1, 1, 1, 2, 2, 2, 3, 3, 3, 3, 3, 3, 3, 3, 3, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4],
                [ 1 => 5, 2 => 3, 3 => 9, 4 => 14 ],
            ],
            [
                [ 'yes', 'yes', 'no', 'yes', 'no', 'no', 'yes', 'yes', 'yes', 'no' ],
                [ 'yes' => 6, 'no' => 4 ],
            ],
            [
                [ 'agree', 'disagree', 'agree', 'agree', 'no opinion', 'agree', 'disagree' ],
                [ 'agree' => 4, 'disagree' => 2, 'no opinion' => 1 ],
            ],
        ];
    }

    /**
     * @test         relativeFrequency
     * @dataProvider dataProviderForRelativeFrequency
     * @param        array $values
     * @param        array $expected
     */
    public function testRelativeFrequency(array $values, array $expected)
    {
        // When
        $frequencies = Distribution::relativeFrequency($values);

        // Then
        $this->assertEqualsWithDelta($expected, $frequencies, 0.0001);
    }

    /**
     * @return array [values, frequencies]
     */
    public function dataProviderForRelativeFrequency(): array
    {
        return [
            [
                [ 'A', 'A', 'B', 'B', 'B', 'B', 'C', 'C', 'D', 'F' ],
                [ 'A' => 0.2, 'B' => 0.4, 'C' => 0.2, 'D' => 0.1, 'F' => 0.1 ],
            ],
            [
                [ 1, 1, 1, 1, 1, 2, 2, 2, 3, 3, 3, 3, 3, 3, 3, 3, 3, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4],
                [ 1 => 0.16129032, 2 => 0.09677419, 3 => 0.29032258, 4 => 0.45161290 ],
            ],
            [
                [ 'yes', 'yes', 'no', 'yes', 'no', 'no', 'yes', 'yes', 'yes', 'no' ],
                [ 'yes' => 0.6, 'no' => 0.4 ],
            ],
            [
                [ 'agree', 'disagree', 'agree', 'agree', 'no opinion', 'agree', 'disagree' ],
                [ 'agree' => 0.57142857, 'disagree' => 0.28571429, 'no opinion' => 0.14285714 ],
            ],
        ];
    }

    /**
     * @test         cumulativeFrequency
     * @dataProvider dataProviderForCumulativeFrequency
     * @param        array $values
     * @param        array $expected
     */
    public function testCumulativeFrequency(array $values, array $expected)
    {
        // When
        $frequencies = Distribution::cumulativeFrequency($values);

        // Then
        $this->assertEqualsWithDelta($expected, $frequencies, 0.0001);
    }

    /**
     * @return array [values, frequencies]
     */
    public function dataProviderForCumulativeFrequency(): array
    {
        return [
            [
                [ 'A', 'A', 'B', 'B', 'B', 'B', 'C', 'C', 'D', 'F' ],
                [ 'A' => 2, 'B' => 6, 'C' => 8, 'D' => 9, 'F' => 10 ],
            ],
            [
                [ 1, 1, 1, 1, 1, 2, 2, 2, 3, 3, 3, 3, 3, 3, 3, 3, 3, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4],
                [ 1 => 5, 2 => 8, 3 => 17, 4 => 31 ],
            ],
            [
                [ 'yes', 'yes', 'no', 'yes', 'no', 'no', 'yes', 'yes', 'yes', 'no' ],
                [ 'yes' => 6, 'no' => 10 ],
            ],
            [
                [ 'agree', 'disagree', 'agree', 'agree', 'no opinion', 'agree', 'disagree' ],
                [ 'agree' => 4, 'disagree' => 6, 'no opinion' => 7 ],
            ],
        ];
    }

    /**
     * @test         cumulativeRelativeFrequency
     * @dataProvider dataProviderForCumulativeRelativeFrequency
     * @param        array $values
     * @param        array $expected
     */
    public function testCumulativeRelativeFrequency(array $values, array $expected)
    {
        // When
        $frequencies = Distribution::cumulativeRelativeFrequency($values);

        // Then
        $this->assertEqualsWithDelta($expected, $frequencies, 0.0001);
    }

    /**
     * @return array [values, frequencies]
     */
    public function dataProviderForCumulativeRelativeFrequency(): array
    {
        return [
            [
                [ 'A', 'A', 'B', 'B', 'B', 'B', 'C', 'C', 'D', 'F' ],
                [ 'A' => 0.2, 'B' => 0.6, 'C' => 0.8, 'D' => 0.9, 'F' => 1 ],
            ],
            [
                [ 1, 1, 1, 1, 1, 2, 2, 2, 3, 3, 3, 3, 3, 3, 3, 3, 3, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4],
                [ 1 => 0.16129032, 2 => 0.25806452, 3 => 0.5483871, 4 => 1 ],
            ],
            [
                [ 'yes', 'yes', 'no', 'yes', 'no', 'no', 'yes', 'yes', 'yes', 'no' ],
                [ 'yes' => 0.6, 'no' => 1 ],
            ],
            [
                [ 'agree', 'disagree', 'agree', 'agree', 'no opinion', 'agree', 'disagree' ],
                [ 'agree' => 0.57142857, 'disagree' => 0.85714286, 'no opinion' => 1 ],
            ],
        ];
    }

    /**
     * @test         fractionalRanking
     * @dataProvider dataProviderForRankingWithoutTies
     * @dataProvider dataProviderForFractionalRank
     * @param        array $values
     * @param        array $expected
     */
    public function testFractionalRanking(array $values, array $expected)
    {
        // When
        $sampleRank = Distribution::fractionalRanking($values);

        // Then
        $this->assertEquals($expected, $sampleRank);
    }

    /**
     * @test         fractionalRanking: Sum of all assigned ranks is ½n(n + 1)
     * @dataProvider dataProviderForRankingWithoutTies
     * @dataProvider dataProviderForFractionalRank
     * @param        array $values
     */
    public function testFractionalRankingDistributionSumOfAllRanks(array $values)
    {
        // Given
        $n = count($values);
        $expectedSumOfAssignedRanks = ($n * ($n + 1)) / 2;

        // When
        $sampleRank = Distribution::fractionalRanking($values);

        // Then
        $sumOfAssignedRanks = \array_sum($sampleRank);
        $this->assertEquals($expectedSumOfAssignedRanks, $sumOfAssignedRanks);
    }

    /**
     * Data generated with R: rank(c(1, 2, 3, 4, 5), ties.method='average')
     * @return array
     */
    public function dataProviderForRankingWithoutTies(): array
    {
        return [
            [
                [0],
                [1],
            ],
            [
                [1],
                [1],
            ],
            [
                [-1],
                [1],
            ],
            [
                [5],
                [1],
            ],
            [
                [1, 5],
                [1, 2],
            ],
            [
                [2, 5],
                [1, 2],
            ],
            [
                [1, 2, 3, 4, 5],
                [1, 2, 3, 4, 5],
            ],
            [
                [5, 2],
                [2, 1],
            ],
            [
                [5, 4, 3, 2, 1],
                [5, 4, 3, 2, 1],
            ],
            [
                [5, 3, 1, 2, 4],
                [5, 3, 1, 2, 4],
            ],
            [
                [1, 3, 5, 7, 9],
                [1, 2, 3, 4, 5],
            ],
            [
                [9, 7, 5, 3, 1],
                [5, 4, 3, 2, 1],
            ],
            [
                [3, 1, 4, 15, 92],
                [2, 1, 3, 4, 5],
            ],
            [
                [8, 4, 10, 3, 5, 32, 1, 98, 43],
                [5, 3, 6, 2, 4, 7, 1, 9, 8],
            ],
            [
                [1, 2, 4, 5],
                [1, 2, 3, 4],
            ],
            [
                [-3, -2, -1, 0, 1, 2, 3],
                [1, 2, 3, 4, 5, 6, 7],
            ],
        ];
    }

    /**
     * Data generated with R: rank(c(1, 2, 3, 4, 5), ties.method='average')
     * @return array
     */
    public function dataProviderForFractionalRank(): array
    {
        return [
            [
                [1, 2, 2, 3],
                [1, 2.5, 2.5, 4],
            ],
            [
                [3, 2, 2, 1],
                [4, 2.5, 2.5, 1],
            ],
            [
                [1, 2, 3, 3, 4, 5],
                [1, 2, 3.5, 3.5, 5, 6],
            ],
            [
                [1, 2, 3, 3, 3, 4, 5],
                [1, 2, 4, 4, 4, 6, 7],

            ],
            [
                [1, 1],
                [1.5, 1.5],
            ],
            [
                [0, 0],
                [1.5, 1.5],
            ],
            [
                [-1, -1],
                [1.5, 1.5],
            ],
            [
                [3, 1, 4, 1, 5, 9, 2, 6, 5, 3, 5],
                [4.5, 1.5, 6.0, 1.5, 8.0, 11.0, 3.0, 10.0, 8.0, 4.5, 8.0],
            ],
            [
                [1.0, 1.0, 2.0, 3.0, 3.0, 4.0, 5.0, 5.0, 5.0],
                [1.5, 1.5, 3, 4.5, 4.5, 6, 8, 8, 8],
            ],
            [
                [-3, -2, -2, -1, -1, 0, 1, 2, 3],
                [1, 2.5, 2.5, 4.5, 4.5, 6, 7, 8, 9],
            ],
            [
                [-1, 5, 7, -1],
                [1.5, 3, 4, 1.5],
            ],
            [
                [2.5, 2.5, 2.5, 3, 3, 2.5, 2.25, 2.75, 2, 2.75],
                [4.5, 4.5, 4.5, 9.5, 9.5, 4.5, 2.0, 7.5, 1.0, 7.5],
            ],
            [
                [2.25, 2.75, 2.75, 2.25, 2.25, 3.25, 2, 2, 2.75, 1.25],
                [5.0, 8.0, 8.0, 5.0, 5.0, 10.0, 2.5, 2.5, 8.0, 1.0],
            ],
            [
                [2.534, 2.512, 2.4634, 2.512, 2.543, 2.5, 2.51, 2.49, 2.49, 2.53, 2.5],
                [10.0, 7.5, 1.0, 7.5, 11.0, 4.5, 6.0, 2.5, 2.5, 9.0, 4.5],
            ],
        ];
    }

    /**
     * @test         standardCompetitionRanking
     * @dataProvider dataProviderForRankingWithoutTies
     * @dataProvider dataProviderForStandardCompetitionRanking
     * @param        array $values
     * @param        array $expected
     */
    public function testStandardCompetitionRanking(array $values, array $expected)
    {
        // When
        $ranking = Distribution::standardCompetitionRanking($values);

        // Then
        $this->assertEquals($expected, $ranking);
    }

    /**
     * Data generated with R: rank(c(1, 2, 3, 4, 5), ties.method='min')
     * @return array
     */
    public function dataProviderForStandardCompetitionRanking(): array
    {
        return [
            [
                [1, 2, 2, 3],
                [1, 2, 2, 4],
            ],
            [
                [3, 2, 2, 1],
                [4, 2, 2, 1],
            ],
            [
                [1, 2, 3, 3, 4, 5],
                [1, 2, 3, 3, 5, 6],
            ],
            [
                [1, 2, 3, 3, 3, 4, 5],
                [1, 2, 3, 3, 3, 6, 7],

            ],
            [
                [1, 1],
                [1, 1],
            ],
            [
                [0, 0],
                [1, 1],
            ],
            [
                [-1, -1],
                [1, 1],
            ],
            [
                [3, 1, 4, 1, 5, 9, 2, 6, 5, 3, 5],
                [4, 1, 6, 1, 7, 11, 3, 10, 7, 4, 7],
            ],
            [
                [1.0, 1.0, 2.0, 3.0, 3.0, 4.0, 5.0, 5.0, 5.0],
                [1, 1, 3, 4, 4, 6, 7, 7, 7],
            ],
            [
                [-3, -2, -2, -1, -1, 0, 1, 2, 3],
                [1, 2, 2, 4, 4, 6, 7, 8, 9],
            ],
            [
                [-1, 5, 7, -1],
                [1, 3, 4, 1],
            ],
            [
                [2.5, 2.5, 2.5, 3, 3, 2.5, 2.25, 2.75, 2, 2.75],
                [3, 3, 3, 9, 9, 3, 2, 7, 1, 7],
            ],
            [
                [2.25, 2.75, 2.75, 2.25, 2.25, 3.25, 2, 2, 2.75, 1.25],
                [4, 7, 7, 4, 4, 10, 2, 2, 7, 1],
            ],
            [
                [2.534, 2.512, 2.4634, 2.512, 2.543, 2.5, 2.51, 2.49, 2.49, 2.53, 2.5],
                [10, 7, 1, 7, 11, 4, 6, 2, 2, 9, 4],
            ],
        ];
    }

    /**
     * @test         modifiedCompetitionRanking
     * @dataProvider dataProviderForRankingWithoutTies
     * @dataProvider dataProviderForModifiedCompetitionRanking
     * @param        array $values
     * @param        array $expected
     */
    public function testModifiedCompetitionRanking(array $values, array $expected)
    {
        // When
        $ranking = Distribution::modifiedCompetitionRanking($values);

        // Then
        $this->assertEquals($expected, $ranking);
    }

    /**
     * Data generated with R: rank(c(1, 2, 3, 4, 5), ties.method='max')
     * @return array
     */
    public function dataProviderForModifiedCompetitionRanking(): array
    {
        return [
            [
                [1, 2, 2, 3],
                [1, 3, 3, 4],
            ],
            [
                [3, 2, 2, 1],
                [4, 3, 3, 1],
            ],
            [
                [1, 2, 3, 3, 4, 5],
                [1, 2, 4, 4, 5, 6],
            ],
            [
                [1, 2, 3, 3, 3, 4, 5],
                [1, 2, 5, 5, 5, 6, 7],

            ],
            [
                [1, 1],
                [2, 2],
            ],
            [
                [0, 0],
                [2, 2],
            ],
            [
                [-1, -1],
                [2, 2],
            ],
            [
                [3, 1, 4, 1, 5, 9, 2, 6, 5, 3, 5],
                [5, 2, 6, 2, 9, 11, 3, 10, 9, 5, 9],
            ],
            [
                [1.0, 1.0, 2.0, 3.0, 3.0, 4.0, 5.0, 5.0, 5.0],
                [2, 2, 3, 5, 5, 6, 9, 9, 9],
            ],
            [
                [-3, -2, -2, -1, -1, 0, 1, 2, 3],
                [1, 3, 3, 5, 5, 6, 7, 8, 9],
            ],
            [
                [-1, 5, 7, -1],
                [2, 3, 4, 2],
            ],
            [
                [2.5, 2.5, 2.5, 3, 3, 2.5, 2.25, 2.75, 2, 2.75],
                [6, 6, 6, 10, 10, 6, 2, 8, 1, 8],
            ],
            [
                [2.25, 2.75, 2.75, 2.25, 2.25, 3.25, 2, 2, 2.75, 1.25],
                [6, 9, 9, 6, 6, 10, 3, 3, 9, 1],
            ],
            [
                [2.534, 2.512, 2.4634, 2.512, 2.543, 2.5, 2.51, 2.49, 2.49, 2.53, 2.5],
                [10, 8, 1, 8, 11, 5, 6, 3, 3, 9, 5],
            ],
        ];
    }

    /**
     * @test         ordinalRanking
     * @dataProvider dataProviderForRankingWithoutTies
     * @dataProvider dataProviderForOrdinalRanking
     * @param        array $values
     * @param        array $expected
     */
    public function testOrdinalRanking(array $values, array $expected)
    {
        // When
        $ranking = Distribution::ordinalRanking($values);

        // Then
        $this->assertEquals($expected, $ranking);
    }

    /**
     * Data generated with R: rank(c(1, 2, 3, 4, 5), ties.method='first')
     * @return array
     */
    public function dataProviderForOrdinalRanking(): array
    {
        return [
            [
                [1, 2, 2, 3],
                [1, 2, 3, 4],
            ],
            [
                [3, 2, 2, 1],
                [4, 2, 3, 1],
            ],
            [
                [1, 2, 3, 3, 4, 5],
                [1, 2, 3, 4, 5, 6],
            ],
            [
                [1, 2, 3, 3, 3, 4, 5],
                [1, 2, 3, 4, 5, 6, 7],

            ],
            [
                [1, 1],
                [1, 2],
            ],
            [
                [0, 0],
                [1, 2],
            ],
            [
                [-1, -1],
                [1, 2],
            ],
            [
                [3, 1, 4, 1, 5, 9, 2, 6, 5, 3, 5],
                [4, 1, 6, 2, 7, 11, 3, 10, 8, 5, 9],
            ],
            [
                [1.0, 1.0, 2.0, 3.0, 3.0, 4.0, 5.0, 5.0, 5.0],
                [1, 2, 3, 4, 5, 6, 7, 8, 9],
            ],
            [
                [-3, -2, -2, -1, -1, 0, 1, 2, 3],
                [1, 2, 3, 4, 5, 6, 7, 8, 9],
            ],
            [
                [-1, 5, 7, -1],
                [1, 3, 4, 2],
            ],
            [
                [2.5, 2.5, 2.5, 3, 3, 2.5, 2.25, 2.75, 2, 2.75],
                [3, 4, 5, 9, 10, 6, 2, 7, 1, 8],
            ],
            [
                [2.25, 2.75, 2.75, 2.25, 2.25, 3.25, 2, 2, 2.75, 1.25],
                [4, 7, 8, 5, 6, 10, 2, 3, 9, 1],
            ],
            [
                [2.534, 2.512, 2.4634, 2.512, 2.543, 2.5, 2.51, 2.49, 2.49, 2.53, 2.5],
                [10, 7, 1, 8, 11, 4, 6, 2, 3, 9, 5],
            ],
        ];
    }

    /**
     * @test         stemAndLeafPlot
     * @dataProvider dataProviderForStemAndLeafPlot
     * @param        array $values
     * @param        array $expected
     */
    public function testStemAndLeafPlot(array $values, array $expected)
    {
        // When
        $plot = Distribution::stemAndLeafPlot($values);

        // Then
        $this->assertEquals($expected, $plot);
    }

    /**
     * @return array [values, plot]
     */
    public function dataProviderForStemAndLeafPlot(): array
    {
        return [
            [
                [44, 46, 47, 49, 63, 64, 66, 68, 68, 72, 72, 75, 76, 81, 84, 88, 106, ],
                [ 4 => [4, 6, 7, 9], 5 => [], 6 => [3, 4, 6, 8, 8], 7 => [2, 2, 5, 6], 8 => [1, 4, 8], 9 => [], 10 => [6] ],
            ],
        ];
    }

    /**
     * @test stemAndLeafPlot printed to standard output
     */
    public function testStemAndLeafPlotPrint()
    {
        // Given
        $print = true;

        // Then
        $this->expectOutputString('0 | 1 2 3' . \PHP_EOL);

        // When
        Distribution::stemAndLeafPlot([1, 2, 3], $print);
    }
}
