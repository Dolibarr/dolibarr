<?php

namespace MathPHP\Tests\Statistics;

use MathPHP\Statistics\Average;
use MathPHP\Exception;

class AverageTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test         mean
     * @dataProvider dataProviderForMean
     * @param        array $numbers
     * @param        float $expectedMean
     * @throws       \Exception
     */
    public function testMean(array $numbers, float $expectedMean)
    {
        // When
        $mean = Average::mean($numbers);

        // Then
        $this->assertEqualsWithDelta($expectedMean, $mean, 0.01);
    }

    /**
     * @return array [numbers, mean]
     */
    public function dataProviderForMean(): array
    {
        return [
            [ [ 1, 1, 1 ], 1 ],
            [ [ 1, 2, 3 ], 2 ],
            [ [ 2, 3, 4 ], 3 ],
            [ [ 5, 5, 6 ], 5.33 ],
            [ [ 13, 18, 13, 14, 13, 16, 14, 21, 13 ], 15 ],
            [ [ 1, 2, 4, 7 ], 3.5 ],
            [ [ 8, 9, 10, 10, 10, 11, 11, 11, 12, 13 ], 10.5 ],
            [ [ 6, 7, 8, 10, 12, 14, 14, 15, 16, 20 ], 12.2 ],
            [ [ 9, 10, 11, 13, 15, 17, 17, 18, 19, 23 ], 15.2 ],
            [ [ 12, 14, 16, 20, 24, 28, 28, 30, 32, 40 ], 24.4 ],
            [ [1.1, 1.2, 1.3, 1.3, 1.4, 1.5 ], 1.3 ],
        ];
    }

    /**
     * @test   mean when the input array is empty
     * @throws \Exception
     */
    public function testMeanExceptionWhenEmptyArray()
    {
        // Given
        $numbers = [];

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        Average::mean($numbers);
    }

    /**
     * @test         weightedMean
     * @dataProvider dataProviderForWeightedMean
     * @param        array $numbers
     * @param        array $weights
     * @param        float $expectedMean
     * @throws       \Exception
     */
    public function testWeightedMean(array $numbers, array $weights, float $expectedMean)
    {
        // When
        $mean = Average::weightedMean($numbers, $weights);

        // Then
        $this->assertEqualsWithDelta($expectedMean, $mean, 0.0001);
    }

    /**
     * @return array [numbers, weights, mean]
     */
    public function dataProviderForWeightedMean(): array
    {
        return [
            // Weights add up to 1
            [ [1, 3, 5, 7, 10], [1 / 5, 1 / 5, 1 / 5, 1 / 5, 1 / 5], 5.2],
            [ [1, 2, 3, 4], [1 / 4, 1 / 4, 1 / 4, 1 / 4], 2.5],
            [ [1, 2,3 , 4], [0.1, 0.1, 0.7, 0.1], 2.8],
            [ [8, 6, 7], [0.5, 0.3, 0.2], 7.2],
            [ [9, 4, 6], [0.5, 0.3, 0.2], 6.9],

            // Weights do not add at up 1
            [ [26, 3, 3, 20, 21, 14, 4, 16, 13, 14], [10, 29, 26, 18, 9, 20, 9, 14, 27, 9], 11.6433 ],
            [ [ 1, 2, 3 ], [ 1, 1, 1 ], 2 ],
            [ [ 2, 0.8, 2.9, 2.4, 2.8, 1.3, 2.7, 0.7, 0, 1.9 ], [2.1, 1.9, 0.5, 2.7, 1.9, 0.1, 1.5, 1.7, 2, 0.5], 1.69732 ],
            [ [70, 80, 90], [2, 3, 1], 78.3333],
            [ [1, 2, 5, 7], [2, 14, 8, 32], 5.25],
        ];
    }

    /**
     * @test   weighted mean when the input array is empty
     * @throws \Exception
     */
    public function testWeightedMeanExceptionWhenEmptyArray()
    {
        // Given
        $numbers = [];
        $weights = [];

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        Average::weightedMean($numbers, $weights);
    }

    /**
     * @test         mean when the input array is empty
     * @dataProvider dataProviderForMean
     * @param        array $numbers
     * @param        float $expectedMean
     * @throws       \Exception
     */
    public function testWeightedMeanIsJustMeanWhenEmptyWeights(array $numbers, float $expectedMean)
    {
        // When
        $mean = Average::weightedMean($numbers, []);

        // Then
        $this->assertEqualsWithDelta($expectedMean, $mean, 0.01);
    }

    /**
     * @test   weightedMean throws a BadDataException when the numbers and weights don't have the same number of elements
     * @throws Exception\BadDataException
     */
    public function testWeightedMeanBadDataExceptionWhenCountsDoNotMatch()
    {
        // Given
        $numbers = [1, 2, 3];
        $weights = [1, 1];

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        Average::weightedMean($numbers, $weights);
    }

    /**
     * @test         median
     * @dataProvider dataProviderForMedian
     * @param        array $numbers
     * @param        float $expectedMedian
     * @throws       \Exception
     */
    public function testMedian(array $numbers, float $expectedMedian)
    {
        // When
        $median = Average::median($numbers);

        // Then
        $this->assertEqualsWithDelta($expectedMedian, $median, 0.01);
    }

    /**
     * @test   median when the input array is empty
     * @throws \Exception
     */
    public function testMedianExceptionWhenEmptyArray()
    {
        // Given
        $numbers = [];

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        Average::median($numbers);
    }

    /**
     * @return array [numbers, median]
     */
    public function dataProviderForMedian(): array
    {
        return [
            [ [0], 0],
            [ [1], 1],
            [ [9], 9],
            [ [1, 1, 1 ], 1],
            [ [1, 2, 3 ], 2],
            [ [2, 3, 4 ], 3],
            [ [5, 5, 6 ], 5],
            [ [1, 2, 3, 4, 5], 3 ],
            [ [1, 2, 3, 4, 5, 6], 3.5 ],
            [ [13, 18, 13, 14, 13, 16, 14, 21, 13], 14 ],
            [ [1, 2, 4, 7], 3 ],
            [ [8, 9, 10, 10, 10, 11, 11, 11, 12, 13], 10.5 ],
            [ [6, 7, 8, 10, 12, 14, 14, 15, 16, 20], 13 ],
            [ [9, 10, 11, 13, 15, 17, 17, 18, 19, 23], 16 ],
            [ [12, 14, 16, 20, 24, 28, 28, 30, 32, 40], 26 ],
            [ [1.1, 1.2, 1.3, 1.4, 1.5], 1.3 ],
            [ [1.1, 1.2, 1.3, 1.3, 1.4, 1.5], 1.3 ],
            [ [1.1, 1.2, 1.3, 1.4], 1.25 ],
        ];
    }

    /**
     * @test         kthSmallest
     * @dataProvider dataProviderForKthSmallest
     * @param        array $numbers
     * @param        int $k
     * @param        float $expectedSmallest
     */
    public function testKthSmallest(array $numbers, int $k, float $expectedSmallest)
    {
        // When
        $smallest = Average::kthSmallest($numbers, $k);

        // Then
        $this->assertEquals($expectedSmallest, $smallest);
    }

    /**
     * @return array [numbers, k, smalest]
     */
    public function dataProviderForKthSmallest(): array
    {
        return [
            [ [ 1, 1, 1 ], 2, 1 ],
            [ [ 1, 2, 3 ], 1, 2 ],
            [ [ 2, 3, 4 ], 1, 3 ],
            [ [ 5, 5, 6 ], 0, 5 ],
            [ [ 1, 2, 3, 4, 5 ], 3, 4 ],
            [ [ 1, 2, 3, 4, 5, 6 ], 2, 3 ],
            [ [ 13, 18, 13, 14, 13, 16, 14, 21, 13 ], 7, 18 ],
            [ [ 1, 2, 4, 7 ], 2, 4 ],
            [ [ 8, 9, 10, 10, 10, 11, 11, 11, 12, 13 ], 5, 11 ],
            [ [ 6, 7, 8, 10, 12, 14, 14, 15, 16, 20 ], 7, 15 ],
            [ [ 9, 10, 11, 13, 15, 17, 17, 18, 19, 23 ], 9, 23 ],
            [ [ 12, 14, 16, 20, 24, 28, 28, 30, 32, 40 ], 1, 14 ],
            [ [1.1, 1.2, 1.3, 1.4, 1.5], 0, 1.1 ],
            [ [1.1, 1.2, 1.3, 1.4, 1.5], 1, 1.2 ],
            [ [1.1, 1.2, 1.3, 1.4, 1.5], 2, 1.3 ],
            [ [1.1, 1.2, 1.3, 1.4, 1.5], 3, 1.4 ],
            [ [1.1, 1.2, 1.3, 1.4, 1.5], 4, 1.5 ],
        ];
    }

    /**
     * @test   kthSmallest when the input array is empty
     * @throws \Exception
     */
    public function testKthSmallestExceptionWhenEmptyArray()
    {
        // Given
        $numbers = [];
        $k       = 1;

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        Average::kthSmallest($numbers, $k);
    }

    /**
     * @test   kthSmallest when k is larger than n
     * @throws \Exception
     */
    public function testKthSmallestExceptionWhenKIsLargerThanN()
    {
        // Given
        $numbers = [1, 2, 3];
        $k       = 4;

        // Then
        $this->expectException(Exception\OutOfBoundsException::class);

        // When
        Average::kthSmallest($numbers, $k);
    }

    /**
     * @test         mode
     * @dataProvider dataProviderForMode
     * @param        array $numbers
     * @param        array $modes
     * @throws       \Exception
     */
    public function testMode(array $numbers, array $modes)
    {
        // When
        $computed_modes = Average::mode($numbers);
        sort($modes);
        sort($computed_modes);

        // Then
        $this->assertEquals($modes, $computed_modes);
    }

    /**
     * @array [numbers, modes]
     */
    public function dataProviderForMode(): array
    {
        return [
            [ [ 1, 1, 1 ], [1] ],
            [ [ 1, 1, 2 ], [1] ],
            [ [ 1, 2, 1 ], [1] ],
            [ [ 2, 1, 1 ], [1] ],
            [ [ 1, 2, 2 ], [2] ],
            [ [ 1, 1, 1, 1 ], [1] ],
            [ [ 1, 1, 1, 2 ], [1] ],
            [ [ 1, 1, 2, 1 ], [1] ],
            [ [ 1, 2, 1, 1 ], [1] ],
            [ [ 2, 1, 1, 1 ], [1] ],
            [ [ 1, 1, 2, 2 ], [ 1, 2 ] ],
            [ [ 1, 2, 2, 1 ], [ 1, 2 ] ],
            [ [ 2, 2, 1, 1 ], [ 1, 2 ] ],
            [ [ 2, 1, 2, 1 ], [ 1, 2 ] ],
            [ [ 2, 1, 1, 2 ], [ 1, 2 ] ],
            [ [ 1, 1, 2, 2, 3, 3 ], [ 1, 2, 3 ] ],
            [ [ 1, 2, 1, 2, 3, 3 ], [ 1, 2, 3 ] ],
            [ [ 1, 2, 3, 1, 2, 3 ], [ 1, 2, 3 ] ],
            [ [ 3, 1, 2, 3, 2, 1 ], [ 1, 2, 3 ] ],
            [ [ 3, 3, 2, 2, 1, 1 ], [ 1, 2, 3 ] ],
            [ [ 1, 1, 1, 2, 2, 3 ], [1] ],
            [ [ 1, 2, 2, 2, 2, 3 ], [2] ],
            [ [ 1, 2, 2, 3, 3, 4 ], [ 2, 3 ] ],
            [ [ 13, 18, 13, 14, 13, 16, 14, 21, 13 ], [13] ],
            [ [ 1, 2, 4, 7 ], [ 1, 2, 4, 7 ] ],
            [ [ 8, 9, 10, 10, 10, 11, 11, 11, 12, 13 ], [ 10, 11 ] ],
            [ [ 6, 7, 8, 10, 12, 14, 14, 15, 16, 20 ], [14] ],
            [ [ 9, 10, 11, 13, 15, 17, 17, 18, 19, 23 ], [17] ],
            [ [ 12, 14, 16, 20, 24, 28, 28, 30, 32, 40 ], [28] ],
            [ [ 1, 1.5, 2, 2 ], [2 ]],
            [ [ 1, 1.1, 1.2, 1.3, 1.3, 1.4, 1.4, 1.5, 1.6, 1.7, 2, 2.5 ], [1.3, 1.4] ],
            [ [ 1.2345678, 1.2345678, 1.23456, 1.23456789 ], [1.2345678] ],
            [ [ 1.2345678, 1.2345678, 1.23456, 1.23456789, 1.2232323, 1.4323432, 1.234432 ], [1.2345678] ],
            [ [ 231.424, 231.424, 333.2342, 34.23423, 354345345.23 ], [231.424] ],
            [ [ 1, 2, 2, 2, 3, 4.4, 4.4, 4.4, 5.6, 10], [2, 4.4] ],
            [ [ 1, 2.2, 2.20, 2.200, 3 ], [2.2] ],
            [ [ 1, 2.34354, 2.34354000, 4, 4, 5, 6 ], [2.34354, 4] ],
            [ [ 1, 2.458474748, 2.4584747480, 2.458474748000, 4, 4, 4, 5, 6 ], [2.45847474800, 4] ],
        ];
    }


    /**
     * @test   mode when the input array is empty
     * @throws \Exception
     */
    public function testModeEmptyArrayWhenEmptyArray()
    {
        // Given
        $numbers = [];

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        Average::mode($numbers);
    }

    /**
     * @test         geometricMean
     * @dataProvider dataProviderForGeometricMean
     * @param        array $numbers
     * @param        float $expectedMean
     * @throws       \Exception
     */
    public function testGeometricMean(array $numbers, float $expectedMean)
    {
        // When
        $mean = Average::geometricMean($numbers);

        // Then
        $this->assertEqualsWithDelta($expectedMean, $mean, 0.01);
    }

    /**
     * @return array [numbers, mean]
     */
    public function dataProviderForGeometricMean(): array
    {
        return [
            [ [ 1, 1, 1 ], 1 ],
            [ [ 1, 2, 3 ], 1.81712 ],
            [ [ 2, 3, 4 ], 2.8845 ],
            [ [ 5, 5, 6 ], 5.31329 ],
            [ [ 13, 18, 13, 14, 13, 16, 14, 21, 13 ], 14.78973 ],
            [ [ 1, 2, 4, 7 ], 2.73556 ],
            [ [ 8, 9, 10, 10, 10, 11, 11, 11, 12, 13 ], 10.41031 ],
            [ [ 6, 7, 8, 10, 12, 14, 14, 15, 16, 20 ], 11.4262 ],
            [ [ 9, 10, 11, 13, 15, 17, 17, 18, 19, 23 ], 14.59594 ],
            [ [ 12, 14, 16, 20, 24, 28, 28, 30, 32, 40 ], 22.8524 ],
            [ [ 1, 3, 5, 7, 10 ], 4.02011 ],
        ];
    }

    /**
     * @test geometricMean when the input array is empty
     */
    public function testGeometricMeanExceptionWhenEmptyArray()
    {
        // Given
        $numbers = [];

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        Average::geometricMean($numbers);
    }

    /**
     * @test         harmonicMean
     * @dataProvider dataProviderForHarmonicMean
     * @param        array $numbers
     * @param        float $expectedMean
     * @throws       \Exception
     */
    public function testHarmonicMean(array $numbers, float $expectedMean)
    {
        // When
        $mean = Average::harmonicMean($numbers);

        // Then
        $this->assertEqualsWithDelta($expectedMean, $mean, 0.01);
    }

    /**
     * @return array [numbers, float]
     */
    public function dataProviderForHarmonicMean(): array
    {
        return [
            [ [ 1, 2, 4, ], 1.71429 ],
            [ [ 1, 1, 1 ], 1 ],
            [ [ 1, 2, 3 ], 1.63636 ],
            [ [ 2, 3, 4 ], 2.76923 ],
            [ [ 5, 5, 6 ], 5.29412 ],
            [ [ 13, 18, 13, 14, 13, 16, 14, 21, 13 ], 14.60508 ],
            [ [ 1, 2, 4, 7 ], 2.11321 ],
            [ [ 8, 9, 10, 10, 10, 11, 11, 11, 12, 13 ], 10.31891 ],
            [ [ 6, 7, 8, 10, 12, 14, 14, 15, 16, 20 ], 10.63965 ],
            [ [ 9, 10, 11, 13, 15, 17, 17, 18, 19, 23 ], 13.98753 ],
            [ [ 12, 14, 16, 20, 24, 28, 28, 30, 32, 40 ], 21.27929 ],
            [ [ 1, 3, 5, 7, 10 ], 2.81501 ],
        ];
    }

    /**
     * @test   harmonicMean when the input array is empty
     * @throws \Exception
     */
    public function testHarmonicMeanNullWhenEmptyArray()
    {
        // Given
        $numbers = [];

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        Average::harmonicMean($numbers);
    }

    /**
     * @test   harmonicMean with negative values
     * @throws \Exception
     */
    public function testHarmonicMeanExceptionNegativeValues()
    {
        // Given
        $numbers = [ 1, 2, 3, -4, 5, -6, 7 ];

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        Average::harmonicMean($numbers);
    }

    /**
     * @test         rootMeanSquare
     * @dataProvider dataProviderForRootMeanSquare
     * @param        array $numbers
     * @param        float $expectedRms
     */
    public function testRootMeanSquare(array $numbers, float $expectedRms)
    {
        // When
        $rms = Average::rootMeanSquare($numbers);

        // Then
        $this->assertEqualsWithDelta($expectedRms, $rms, 0.01);
    }

    /**
     * @test         quadradicMean
     * @dataProvider dataProviderForRootMeanSquare
     * @param        array $numbers
     * @param        float $expectedRms
     */
    public function testQuadradicMean(array $numbers, float $expectedRms)
    {
        // When
        $rms = Average::quadraticMean($numbers);

        // Then
        $this->assertEqualsWithDelta($expectedRms, $rms, 0.01);
    }

    /**
     * @return array [numbers, rms]
     */
    public function dataProviderForRootMeanSquare(): array
    {
        return [
            [ [0, 0, 0], 0 ],
            [ [1, 2, 3, 4, 5, 6], 3.89444 ],
            [ [0.001, 0.039, 0.133, 0.228, 0.374], 0.20546 ],
            [ [3, 5, 6, 3, 3535, 234, 0, 643, 2], 1200.209 ],
        ];
    }

    /**
     * @test   rootMeanSquare with empty list of numbers
     * @throws \Exception
     */
    public function testRootMeanSquareExceptionWhenEmptyList()
    {
        // Given
        $numbers = [];

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        Average::rootMeanSquare($numbers);
    }

    /**
     * @test   quadraticMean with empty list of numbers
     * @throws \Exception
     */
    public function testQuadraticMeanExceptionWhenEmptyList()
    {
        // Given
        $numbers = [];

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        Average::quadraticMean($numbers);
    }

    /**
     * @test         trimean
     * @dataProvider dataProviderForTrimean
     * @param        array $numbers
     * @param        float $expectedTrimean
     * @throws       \Exception
     */
    public function testTrimean(array $numbers, float $expectedTrimean)
    {
        // When
        $trimean = Average::trimean($numbers);

        // Then
        $this->assertEqualsWithDelta($expectedTrimean, $trimean, 0.1);
    }

    /**
     * @return array [numbers, trimean]
     */
    public function dataProviderForTrimean(): array
    {
        return [
            [ [ 155, 158, 161, 162, 166, 170, 171, 174, 179 ], 166 ],
            [ [ 162, 162, 163, 165, 166, 175, 181, 186, 192 ], 169.5 ],
            [ [ 1, 3, 4, 4, 6, 6, 6, 6, 7, 7, 7, 8, 8, 9, 9, 10, 11, 12, 13 ], 7.25 ],
            [ [ 1, 3, 4, 4, 6, 6, 6, 6, 7, 7, 7, 8, 8, 9, 9, 10, 11, 12, 1000 ], 7.25 ],
        ];
    }

    /**
     * @test         truncatedMean
     * @dataProvider dataProviderForTruncatedMean
     * @param        array $numbers
     * @param        int   $trim_percent
     * @param        float $expectedMean
     * @throws       \Exception
     */
    public function testTruncatedMean(array $numbers, int $trim_percent, float $expectedMean)
    {
        // When
        $mean = Average::truncatedMean($numbers, $trim_percent);

        // Then
        $this->assertEqualsWithDelta($expectedMean, $mean, 0.00001);
    }

    /**
     * Test data generated with R: mean(nums, trim=0.05)
     * @return array [numbers, trim_percent, mean]
     */
    public function dataProviderForTruncatedMean(): array
    {
        return [
            [[92, 19, 101, 58, 1053, 91, 26, 78, 10, 13, -40, 101, 86, 85, 15, 89, 89, 28, -5, 41], 0, 101.5],
            [[92, 19, 101, 58, 1053, 91, 26, 78, 10, 13, -40, 101, 86, 85, 15, 89, 89, 28, -5, 41], 5, 56.5],
            [[92, 19, 101, 58, 1053, 91, 26, 78, 10, 13, -40, 101, 86, 85, 15, 89, 89, 28, -5, 41], 15, 57.85714],
            [[92, 19, 101, 58, 1053, 91, 26, 78, 10, 13, -40, 101, 86, 85, 15, 89, 89, 28, -5, 41], 40, 65.5],
            [[92, 19, 101, 58, 1053, 91, 26, 78, 10, 13, -40, 101, 86, 85, 15, 89, 89, 28, -5, 41], 50, 68],
            [[4,3,6,8,4,2,4,8,12,53,23,12,21], 0, 12.30769],
            [[4,3,6,8,4,2,4,8,12,53,23,12,21], 5, 12.30769],
            [[4,3,6,8,4,2,4,8,12,53,23,12,21], 10, 9.545455],
            [[4,3,6,8,4,2,4,8,12,53,23,12,21], 20, 8.777778],
            [[4,3,6,8,4,2,4,8,12,53,23,12,21], 25, 7.714286],
            [[4,3,6,8,4,2,4,8,12,53,23,12,21], 30, 7.714286],
            [[4,3,6,8,4,2,4,8,12,53,23,12,21], 35, 7.6],
            [[4,3,6,8,4,2,4,8,12,53,23,12,21], 40, 7.333333],
            [[4,3,6,8,4,2,4,8,12,53,23,12,21], 45, 7.333333],
            [[4,3,6,8,4,2,4,8,12,53,23,12,21], 50, 8],
            [[8, 3, 7, 1, 3, 9], 0, 5.16666667],
            [[8, 3, 7, 1, 3, 9], 20, 5.25],
            [[8, 3, 7, 1, 3, 9], 50, 5],
            [[6,4,2,4,3,7,6,33,77,22,3,5,6,5,0,2,3,4,6], 25, 4.727273],
            [[6,4,2,4,3,7,6,33,77,22,3,5,6,5,0,2,3,4,6], 50, 5],
            [[2, 3, 4, 5, 1, 9, 6, 7, 10, 8], 1, 5.5],
            [[2, 3, 4, 5, 1, 9, 6, 7, 10, 8], 10, 5.5],
            [[2, 3, 4, 5, 1, 9, 6, 7, 10, 8], 40, 5.5],
            [[2, 3, 4, 5, 1, 9, 6, 7, 10, 8], 50, 5.5],
            [[3, 5, 6, 7, 6, 5, 6, 4, 2, 1, 0, 9, 8, 2, 4, 16, 4, 3, 3, 2, 12], 1, 5.142857],
            [[3, 5, 6, 7, 6, 5, 6, 4, 2, 1, 0, 9, 8, 2, 4, 16, 4, 3, 3, 2, 12], 1, 5.142857],
            [[3, 5, 6, 7, 6, 5, 6, 4, 2, 1, 0, 9, 8, 2, 4, 16, 4, 3, 3, 2, 12], 10, 4.647059],
            [[3, 5, 6, 7, 6, 5, 6, 4, 2, 1, 0, 9, 8, 2, 4, 16, 4, 3, 3, 2, 12], 20, 4.461538],
            [[3, 5, 6, 7, 6, 5, 6, 4, 2, 1, 0, 9, 8, 2, 4, 16, 4, 3, 3, 2, 12], 25, 4.454545],
            [[3, 5, 6, 7, 6, 5, 6, 4, 2, 1, 0, 9, 8, 2, 4, 16, 4, 3, 3, 2, 12], 40, 4.4],
            [[3, 5, 6, 7, 6, 5, 6, 4, 2, 1, 0, 9, 8, 2, 4, 16, 4, 3, 3, 2, 12], 50, 4],
            [[1, 2, 3], 50, 2],
            [[1, 2, 3, 4], 50, 2.5],
            [[1, 2, 3, 4, 5], 50, 3],
            [[1, 2, 3, 4, 5, 6], 50, 3.5],
        ];
    }

    /**
     * @test   truncatedMean of an empty list
     * @throws \Exception
     */
    public function testTruncatedMeanExceptionEmptyList()
    {
        // Given
        $numbers      = [];
        $trim_percent = 5;

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        Average::truncatedMean($numbers, $trim_percent);
    }

    /**
     * @test   truncatedMean trim percent is less than zero
     * @throws \Exception
     */
    public function testTruncatedMeanExceptionLessThanZeroTrimPercent()
    {
        // Given
        $numbers      = [1, 2, 3];
        $trim_percent = -1;

        // Then
        $this->expectException(Exception\OutOfBoundsException::class);

        // When
        Average::truncatedMean($numbers, $trim_percent);
    }

    /**
     * @test         truncatedMean trim percent greater than 50
     * @dataProvider dataProviderForTruncatedMeanGreaterThan50TrimPercent
     * @param        int $trim_percent
     */
    public function testTruncatedMeanExceptionGreaterThan50TrimPercent(int $trim_percent)
    {
        // Given
        $numbers = [1, 2, 3, 6, 5, 4, 7];

        // Then
        $this->expectException(Exception\OutOfBoundsException::class);

        // When
        Average::truncatedMean([1, 2, 3], $trim_percent);
    }

    public function dataProviderForTruncatedMeanGreaterThan50TrimPercent(): array
    {
        return [
            [51],
            [75],
            [99],
            [100],
            [101],
        ];
    }

    /**
     * @test         interquartileMean
     * @dataProvider dataProviderForInterquartileMean
     * @param        array $numbers
     * @param        float $expectedIqm
     * @throws       \Exception
     */
    public function testInterquartileMean(array $numbers, float $expectedIqm)
    {
        // When
        $iqm = Average::interquartileMean($numbers);

        // Then
        $this->assertEqualsWithDelta($expectedIqm, $iqm, 0.01);
    }

    /**
     * @test         iqm
     * @dataProvider dataProviderForInterquartileMean
     * @param        array $numbers
     * @param        float $expectedIqm
     * @throws       \Exception
     */
    public function testIqm(array $numbers, float $expectedIqm)
    {
        // When
        $iqm = Average::iqm($numbers);

        // Then
        $this->assertEqualsWithDelta($expectedIqm, $iqm, 0.01);
    }

    /**
     * @return array [numbers, iqm]
     */
    public function dataProviderForInterquartileMean(): array
    {
        return [
            [ [5, 8, 4, 38, 8, 6, 9, 7, 7, 3, 1, 6], 6.5 ],
            [ [1, 3, 5, 7, 9, 11, 13, 15, 17], 9 ]
        ];
    }

    /**
     * @test   cubicMean
     * @dataProvider dataProviderForCubicMean
     * @throws \Exception
     */
    public function testCubicMean(array $numbers, float $expectedMean)
    {
        // When
        $mean = Average::cubicMean($numbers);

        // Then
        $this->assertEqualsWithDelta($expectedMean, $mean, 0.001);
    }

    /**
     * @return array
     */
    public function dataProviderForCubicMean(): array
    {
        return [
            [[1, 2, 3], 2.289428485106664],
            [[0, 5, 9, 4], 6.122482652876022],
        ];
    }

    /**
     * @test   cubic mean with empty list of numbers
     * @throws \Exception
     */
    public function testCubicMeanExceptionWhenEmptyList()
    {
        // Given
        $numbers = [];

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        Average::cubicMean($numbers);
    }

    /**
     * @test         lehmerMean
     * @dataProvider dataProviderForLehmerMean
     * @param        array $numbers
     * @param        float $p
     * @param        float $expectedMean
     */
    public function testLehmerMean(array $numbers, float $p, float $expectedMean)
    {
        // When
        $mean = Average::lehmerMean($numbers, $p);

        // Then
        $this->assertEqualsWithDelta($expectedMean, $mean, 0.01);
    }

    /**
     * @return array [numbers, p, mean]
     */
    public function dataProviderForLehmerMean(): array
    {
        return [
            [ [ 3, 6, 2, 9, 1, 7, 2 ], -2, 1.290 ],
            [ [ 3, 6, 2, 9, 1, 7, 2 ], -1, 1.647 ],
            [ [ 3, 6, 2, 9, 1, 7, 2 ], -0.5, 1.997 ],
            [ [ 3, 6, 2, 9, 1, 7, 2 ], 0.5, 3.322 ],
            [ [ 3, 6, 2, 9, 1, 7, 2 ], 1, 4.286 ],
            [ [ 3, 6, 2, 9, 1, 7, 2 ], 2, 6.133 ],
            [ [ 3, 6, 2, 9, 1, 7, 2 ], 3, 7.239 ],
        ];
    }

    /**
     * @test   lehmerMean with empty list of numbers
     * @throws \Exception
     */
    public function testLehmerMeanExceptionWhenEmptyList()
    {
        // Given
        $numbers = [];
        $p       = 1;

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        Average::lehmerMean($numbers, $p);
    }

    /**
     * @test lehmerMean p is negative infinity
     */
    public function testLehmerMeanPEqualsNegativeInfinityIsMin()
    {
        // Given
        $numbers = [ 3, 6, 2, 9, 1, 7, 2];
        $p       = -\INF;

        // When
        $mean = Average::lehmerMean($numbers, $p);

        // Then
        $this->assertEquals(\min($numbers), $mean);
    }

    /**
     * @test lehmerMean p is infinity
     */
    public function testLehmerMeanPEqualsInfinityIsMax()
    {
        // Given
        $numbers = [ 3, 6, 2, 9, 1, 7, 2];
        $p       = \INF;

        // When
        $mean = Average::lehmerMean($numbers, $p);

        // Then
        $this->assertEquals(\max($numbers), $mean);
    }

    /**
     * @test   lehmerMean with a p of zero is the harmonic mean
     * @throws \Exception
     */
    public function testLehmerMeanPEqualsZeroIsHarmonicMean()
    {
        // Given
        $numbers = [ 3, 6, 2, 9, 1, 7, 2];
        $p       = 0;

        // When
        $mean = Average::lehmerMean($numbers, $p);

        // Then
        $this->assertEquals(Average::harmonicMean($numbers), $mean);
    }

    /**
     * @test   lehmerMean with a p of one half is the geometric mean
     * @throws \Exception
     */
    public function testLehmerMeanPEqualsOneHalfIsGeometricMean()
    {
        // Given
        $numbers = [3, 6];
        $p       = 1 / 2;

        // When
        $mean = Average::lehmerMean($numbers, $p);

        // Then
        $this->assertEquals(Average::geometricMean($numbers), $mean);
    }

    /**
     * @test   lehmerMean with a p of one is the arithmetic mean
     * @throws \Exception
     */
    public function testLehmerMeanPEqualsOneIsArithmeticMean()
    {
        // Given
        $numbers = [ 3, 6, 2, 9, 1, 7, 2];
        $p       = 1;

        // When
        $mean = Average::lehmerMean($numbers, $p);

        // Then
        $this->assertEquals(Average::mean($numbers), $mean);
    }

    /**
     * @test         generalizedMean
     * @dataProvider dataProviderForGeneralizedMean
     * @param        array $numbers
     * @param        float $p
     * @param        float $expectedMean
     * @throws       \Exception
     */
    public function testGeneralizedMean(array $numbers, float $p, float $expectedMean)
    {
        // When
        $mean = Average::generalizedMean($numbers, $p);

        // Then
        $this->assertEqualsWithDelta($expectedMean, $mean, 0.001);
    }

    /**
     * @test   generalizedMean with empty list of numbers
     * @throws \Exception
     */
    public function testGeneralizedMeanExceptionWhenEmptyList()
    {
        // Given
        $numbers = [];
        $p       = 1;

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        Average::generalizedMean($numbers, $p);
    }

    /**
     * @test     powerMean
     * @dataProvider dataProviderForGeneralizedMean
     * @param        array $numbers
     * @param        float $p
     * @param        float $expectedMean
     */
    public function testPowerMean(array $numbers, float $p, float $expectedMean)
    {
        // When
        $mean = Average::powerMean($numbers, $p);

        // Then
        $this->assertEqualsWithDelta($expectedMean, $mean, 0.001);
    }

    /**
     * @return array [numbers, float, mean]
     */
    public function dataProviderForGeneralizedMean(): array
    {
        return [
            [ [1, 2, 3, 4, 5], -2, 1.84829867963 ],
            [ [1, 2, 3, 4, 5], -1, 2.1897810219 ],
            [ [1, 2, 3, 4, 5], -0.5, 2.3937887509 ],
            [ [1, 2, 3, 4, 5], 0.5, 2.81053982332 ],
            [ [1, 2, 3, 4, 5], 1, 3 ],
            [ [1, 2, 3, 4, 5], 2, 3.31662479036 ],
            [ [1, 2, 3, 4, 5], 3, 3.55689330449 ],
        ];
    }

    /**
     * @test   generalizedMean with a p of negative infinity
     * @throws \Exception
     */
    public function testGeneralizedMeanPEqualsNegativeInfinityIsMin()
    {
        // Given
        $numbers = [3, 6, 2, 9, 1, 7, 2];
        $p       = -\INF;

        // When
        $mean = Average::generalizedMean($numbers, $p);

        // Then
        $this->assertEquals(\min($numbers), $mean);
    }

    /**
     * @test   generalizedMean with a p of infinity
     * @throws \Exception
     */
    public function testGeneralizedMeanPEqualsInfinityIsMax()
    {
        // Given
        $numbers = [3, 6, 2, 9, 1, 7, 2];
        $p       = \INF;

        // When
        $mean = Average::generalizedMean($numbers, $p);

        // Then
        $this->assertEquals(\max($numbers), $mean);
    }

    /**
     * @test   generalizedMean with a p of negative one is the harmonic mean
     * @throws \Exception
     */
    public function testGeneralizedMeanPEqualsNegativeOneIsHarmonicMean()
    {
        // Given
        $numbers = [3, 6, 2, 9, 1, 7, 2];
        $p       = -1;

        // When
        $mean = Average::generalizedMean($numbers, $p);

        // Then
        $this->assertEquals(Average::harmonicMean($numbers), $mean);
    }

    /**
     * @test   generalizedMean with a p of zero is the geometric mean
     * @throws \Exception
     */
    public function testGeneralizedMeanPEqualsZeroIsGeometricMean()
    {
        $numbers = [ 3, 6, 2, 9, 1, 7, 2];
        $p       = 0;
        $this->assertEquals(Average::geometricMean($numbers), Average::generalizedMean($numbers, $p));
    }

    /**
     * @test   generalizedMean with a p of one is the arithmetic mean
     * @throws \Exception
     */
    public function testGeneralizedMeanPEqualsOneIsArithmeticMean()
    {
        // Given
        $numbers = [3, 6, 2, 9, 1, 7, 2];
        $p       = 1;

        // When
        $mean = Average::generalizedMean($numbers, $p);

        // Then
        $this->assertEquals(Average::mean($numbers), $mean);
    }

    /**
     * @test   generalizedMean with a p of two is the quadratic mean
     * @throws \Exception
     */
    public function testGeneralizedMeanPEqualsTwoIsQuadraticMean()
    {
        // Given
        $numbers = [3, 6, 2, 9, 1, 7, 2];
        $p       = 2;

        // When
        $mean = Average::generalizedMean($numbers, $p);

        // Then
        $this->assertEquals(Average::quadraticMean($numbers), $mean);
    }

    /**
     * @test   generalizedMean with a p of three is the cubic mean
     * @throws \Exception
     */
    public function testGeneralizedMeanPEqualsThreeIsCubicMean()
    {
        // Given
        $numbers = [3, 6, 2, 9, 1, 7, 2];
        $p       = 3;

        // When
        $mean = Average::generalizedMean($numbers, $p);

        // Then
        $this->assertEquals(Average::cubicMean($numbers), $mean);
    }

    /**
     * @test contraharmonicMean
     */
    public function testContraharmonicMean()
    {
        // Given
        $numbers = [3, 6, 2, 9, 1, 7, 2];

        // When
        $mean = Average::contraharmonicMean($numbers);

        // Then
        $this->assertEqualsWithDelta(6.133, $mean, 0.01);
    }

    /**
     * @test         simpleMovingAverage
     * @dataProvider dataProviderForSimpleMovingAverage
     * @param        array $numbers
     * @param        int   $n
     * @param        array $expectedSma
     */
    public function testSimpleMovingAverage(array $numbers, int $n, array $expectedSma)
    {
        // When
        $sma = Average::simpleMovingAverage($numbers, $n);

        // Then
        $this->assertEqualsWithDelta($expectedSma, $sma, 0.0001);
    }

    /**
     * @return array [numbers, int, SMA]
     */
    public function dataProviderForSimpleMovingAverage(): array
    {
        return [
            [
                [1, 1, 2, 2, 3, 3], 2,
                [1, 1.5, 2, 2.5, 3],
            ],
            [
                [10, 11, 11, 15, 13, 14, 12, 10, 11], 4,
                [11.75, 12.5, 13.25, 13.5, 12.25, 11.75],
            ],
            [
                [11, 12, 13, 14, 15, 16, 17], 5,
                [13, 14, 15],
            ],
            [
                [4, 6, 5, 8, 9, 5, 4, 3, 7, 8], 5,
                [6.4, 6.6, 6.2, 5.8, 5.6, 5.4],
            ],
            [
                [43, 67, 57, 67, 4, 32, 34, 54, 93, 94, 38, 45], 6,
                [45, 43.5, 41.3333, 47.3333, 51.8333, 57.5, 59.6667],
            ],
            [
                [5, 6, 7, 8, 9], 3,
                [6, 7, 8]
            ],
        ];
    }

    /**
     * @test         cumulativeMovingAverage
     * @dataProvider dataProviderForCumulativeMovingAverage
     * @param        array $numbers
     * @param        array $expectredCma
     */
    public function testCumulativeMovingAverage(array $numbers, array $expectredCma)
    {
        // When
        $cma = Average::cumulativeMovingAverage($numbers);

        // Then
        $this->assertEqualsWithDelta($expectredCma, $cma, 0.001);
    }

    /**
     * @return array [numbers, CMA]
     */
    public function dataProviderForCumulativeMovingAverage(): array
    {
        return [
            [
                [1, 2, 3, 4, 5],
                [1, 1.5, 2, 2.5, 3],
            ],
            [
                [1, 1, 2, 2, 3, 3],
                [1, 1, 1.333, 1.5, 1.8, 2],
            ],
            [
                [1, 3, 8, 12, 10, 8, 7, 15],
                [1, 2, 4, 6, 6.8, 7, 7, 8],
            ],
        ];
    }

    /**
     * @test         weightedMovingAverage
     * @dataProvider dataProviderForWeightedMovingAverage
     * @param        array $numbers
     * @param        int   $n
     * @param        array $weights
     * @param        array $expectedWma
     * @throws       \Exception
     */
    public function testWeightedMovingAverage(array $numbers, int $n, array $weights, array $expectedWma)
    {
        // When
        $wma = Average::weightedMovingAverage($numbers, $n, $weights);

        // Then
        $this->assertEqualsWithDelta($expectedWma, $wma, 0.001);
    }

    /**
     * @return array [numbers, n, weights, WMA]
     */
    public function dataProviderForWeightedMovingAverage(): array
    {
        return [
            [
                [10, 11, 15, 16, 14, 12, 10, 11],
                3,
                [1, 2, 5],
                [13.375, 15.125, 14.625, 13, 11, 10.875],
            ],
            [
                [5, 4, 8],
                3,
                [1, 2, 3],
                [6.16666667]
            ],
            [
                [5, 6, 7, 8, 9],
                5,
                [1, 2, 3, 4, 5],
                [7.6667],
            ],
        ];
    }

    /**
     * @test   weightedMovingAverage weights differ from n
     * @throws \Exception
     */
    public function testWeightedMovingAverageExceptionWeightsDifferFromN()
    {
        // Given
        $numbers = [1, 2, 3, 4, 5, 6];
        $n       = 3;
        $weights = [1, 2];

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        Average::weightedMovingAverage($numbers, $n, $weights);
    }

    /**
     * @test         exponentialMovingAverage
     * @dataProvider dataProviderForExponentialMovingAverage
     * @param        array $numbers
     * @param        int   $n
     * @param        array $expectedEma
     */
    public function testExponentialMovingAverage(array $numbers, int $n, array $expectedEma)
    {
        // When
        $ema = Average::exponentialMovingAverage($numbers, $n);

        // Then
        $this->assertEqualsWithDelta($expectedEma, $ema, 0.01);
    }

    /**
     * @return array [numbers, n, EMA]
     */
    public function dataProviderForExponentialMovingAverage(): array
    {
        return [
            [
                [1, 1, 2, 2, 3, 3], 2,
                [1, 1, 1.667, 1.889, 2.63, 2.877],
            ],
            [
                [5, 6, 7, 8, 7, 8, 9, 8, 7], 2,
                [5, 5.667, 6.556, 7.519, 7.173, 7.724, 8.575, 8.192, 7.397],
            ],
            [
                [5, 6, 7, 8, 7, 8, 9, 8, 7], 3,
                [5, 5.5, 6.25, 7.125, 7.063, 7.531, 8.266, 8.133, 7.566],
            ],
            [
                [22, 25, 27, 29, 34, 46, 43, 39, 37, 36, 36, 35, 34, 40, 43, 44, 49, 50, 52, 47, 35, 32, 29, 15, 17, 18, 19], 3,
                [22, 23.5, 25.25, 27.125, 30.563, 38.281, 40.641, 39.82, 38.41, 37.205, 36.603, 35.801, 34.901, 37.45, 40.225, 42.113, 45.556, 47.778, 49.889, 48.445, 41.722, 36.861, 32.931, 23.965, 20.483, 19.241, 19.121]
            ],
            [
                [22.81, 23.09, 22.91, 23.23, 22.83, 23.05, 23.02, 23.29, 23.41, 23.49, 24.60, 24.63, 24.51, 23.73, 23.31, 23.53, 23.06, 23.25, 23.12, 22.80, 22.84], 9,
                [22.81, 22.87, 22.87, 22.95, 22.92, 22.95, 22.96, 23.03, 23.10, 23.18, 23.47, 23.70, 23.86, 23.83, 23.73, 23.69, 23.56, 23.50, 23.42, 23.30, 23.21]
            ],
            [
                [10, 15, 17, 20, 22, 20, 25, 27, 30, 35, 37, 40], 3,
                [10, 12.5, 14.75, 17.375, 19.688, 19.844, 22.422, 24.711, 27.355, 31.178, 34.089, 37.044]
            ],
        ];
    }

    /**
     * @test         arithmeticGeometricMean
     * @dataProvider dataProviderForArithmeticGeometricMean
     * @param        float $x
     * @param        float $y
     * @param        float $expectedMean
     */
    public function testArithmeticGeometricMean(float $x, float $y, float $expectedMean)
    {
        // When
        $mean = Average::arithmeticGeometricMean($x, $y);

        // Then
        $this->assertEqualsWithDelta($expectedMean, $mean, 0.00001);
    }

    /**
     * @test         agm
     * @dataProvider dataProviderForArithmeticGeometricMean
     * @param        float $x
     * @param        float $y
     * @param        float $expectedMean
     */
    public function testAGM(float $x, float $y, float $expectedMean)
    {
        // When
        $mean = Average::agm($x, $y);

        // Then
        $this->assertEqualsWithDelta($expectedMean, $mean, 0.00001);
    }

    /**
     * @return array [x, y, mean]
     */
    public function dataProviderForArithmeticGeometricMean(): array
    {
        return [
            [ 24, 6, 13.4581714817256154207668131569743992430538388544 ],
            [ 2, 4, 2.913582062093814 ],
            [ 1, 1, 1 ],
            [ 43.6, 7765.332, 1856.949564100313 ],
            [ 0, 3434, 0 ],
            [ 3432, 0, 0 ],
        ];
    }

    /**
     * @test arithmeticGeometricMean negative
     */
    public function testArithmeticGeometricMeanNegativeNAN()
    {
        $this->assertNan(Average::arithmeticGeometricMean(-32, 45));
        $this->assertNan(Average::arithmeticGeometricMean(32, -45));
        $this->assertNan(Average::agm(-32, 45));
        $this->assertNan(Average::agm(32, -45));
    }

    /**
     * @test         logarithmicMean
     * @dataProvider dataProviderForArithmeticLogarithmicMean
     * @param        float $x
     * @param        float $y
     * @param        float $expectedMean
     */
    public function testLogarithmicMean(float $x, float $y, float $expectedMean)
    {
        // When
        $mean = Average::logarithmicMean($x, $y);

        // Then
        $this->assertEqualsWithDelta($expectedMean, $mean, 0.01);
    }

    /**
     * @return array [x, y, mean]
     */
    public function dataProviderForArithmeticLogarithmicMean(): array
    {
        return [
            [ 0, 0, 0 ],
            [ 5, 5, 5 ],
            [ 45, 55, 49.83 ],
            [ 70, 30, 47.21 ],
            [ 339.78, 41.03, 141.32 ],
            [ 349.76, 31.05, 131.61 ],
        ];
    }

    /**
     * @test         heronianMean
     * @dataProvider dataProviderForHeronianMean
     * @param        float $A
     * @param        float $B
     * @param        float $expected
     */
    public function testHeronianMean(float $A, float $B, float $expected)
    {
        // When
        $H = Average::heronianMean($A, $B);

        // Then
        $this->assertEquals($expected, $H);
    }

    /**
     * @return array [A, B, H]
     */
    public function dataProviderForHeronianMean(): array
    {
        return [
            [ 4, 5, 4.490711985 ],
            [ 12, 50, 28.8316324759 ],
        ];
    }

    /**
     * @test         identricMean
     * @dataProvider dataProviderForIdentricMean
     * @param        float $x
     * @param        float $y
     * @param        float $expectedMean
     * @throws       \Exception
     */
    public function testIdentricMean(float $x, float $y, float $expectedMean)
    {
        // When
        $mean = Average::identricMean($x, $y);

        // Then
        $this->assertEqualsWithDelta($expectedMean, $mean, 0.001);
    }

    /**
     * @return array [x, y, mean]
     */
    public function dataProviderForIdentricMean(): array
    {
        return [
            [ 5, 5, 5 ],
            [ 5, 6, 5.49241062633 ],
            [ 6, 5, 5.49241062633 ],
            [ 12, 3, 7.00766654296 ],
            [ 3, 12, 7.00766654296 ],
        ];
    }

    /**
     * @test   identricMean throws an \Exception for a negative value
     * @throws \Exception
     */
    public function testIdentricMeanExceptionNegativeValue()
    {
        // Given
        $x = -2;
        $y = 5;

        // Then
        $this->expectException(Exception\OutOfBoundsException::class);

        // When
        Average::identricMean($x, $y);
    }

    /**
     * @test   describe
     * @throws \Exception
     */
    public function testDescribe()
    {
        // Given
        $numbers = [13, 18, 13, 14, 13, 16, 14, 21, 13];

        // When
        $averages = Average::describe($numbers);

        // Then
        $this->assertTrue(\is_array($averages));
        $this->assertArrayHasKey('mean', $averages);
        $this->assertArrayHasKey('median', $averages);
        $this->assertArrayHasKey('mode', $averages);
        $this->assertArrayHasKey('geometric_mean', $averages);
        $this->assertArrayHasKey('harmonic_mean', $averages);
        $this->assertArrayHasKey('contraharmonic_mean', $averages);
        $this->assertArrayHasKey('quadratic_mean', $averages);
        $this->assertArrayHasKey('trimean', $averages);
        $this->assertArrayHasKey('iqm', $averages);
        $this->assertArrayHasKey('cubic_mean', $averages);
        $this->assertTrue(\is_numeric($averages['mean']));
        $this->assertTrue(\is_numeric($averages['median']));
        $this->assertTrue(\is_array($averages['mode']));
        $this->assertTrue(\is_numeric($averages['geometric_mean']));
        $this->assertTrue(\is_numeric($averages['harmonic_mean']));
        $this->assertTrue(\is_numeric($averages['contraharmonic_mean']));
        $this->assertTrue(\is_numeric($averages['quadratic_mean']));
        $this->assertTrue(\is_numeric($averages['trimean']));
        $this->assertTrue(\is_numeric($averages['iqm']));
        $this->assertTrue(\is_numeric($averages['cubic_mean']));
    }
}
