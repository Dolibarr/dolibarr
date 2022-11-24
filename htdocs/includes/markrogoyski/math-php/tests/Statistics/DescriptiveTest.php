<?php

namespace MathPHP\Tests\Statistics;

use MathPHP\Statistics\Descriptive;
use MathPHP\Exception;

class DescriptiveTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test         range
     * @dataProvider dataProviderForRange
     * @param        array $numbers
     * @param        float $expectedRange
     * @throws       \Exception
     */
    public function testRange(array $numbers, float $expectedRange)
    {
        // When
        $range = Descriptive::range($numbers);

        // Then
        $this->assertEqualsWithDelta($expectedRange, $range, 0.01);
    }

    /**
     * Data provider for range test
     * @return array [ [ numbers ], range ]
     */
    public function dataProviderForRange(): array
    {
        return [
            [ [ 1, 1, 1 ], 0 ],
            [ [ 1, 1, 2 ], 1 ],
            [ [ 1, 2, 1 ], 1 ],
            [ [ 8, 4, 3 ], 5 ],
            [ [ 9, 7, 8 ], 2 ],
            [ [ 13, 18, 13, 14, 13, 16, 14, 21, 13 ], 8 ],
            [ [ 1, 2, 4, 7 ], 6 ],
            [ [ 8, 9, 10, 10, 10, 11, 11, 11, 12, 13 ], 5 ],
            [ [ 6, 7, 8, 10, 12, 14, 14, 15, 16, 20 ], 14 ],
            [ [ 9, 10, 11, 13, 15, 17, 17, 18, 19, 23 ], 14 ],
            [ [ 12, 14, 16, 20, 24, 28, 28, 30, 32, 40 ], 28 ],
        ];
    }

    /**
     * @test   range when array is empty
     * @throws \Exception
     */
    public function testRangeExceptionWhenEmptyArray()
    {
        // Given
        $numbers = [];

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        Descriptive::range($numbers);
    }

    /**
     * @test         midrange
     * @dataProvider dataProviderForMidrange
     * @param        array $numbers
     * @param        float $expectedMidrange
     * @throws       \Exception
     */
    public function testMidrange(array $numbers, float $expectedMidrange)
    {
        // When
        $midrange = Descriptive::midrange($numbers);

        // Then
        $this->assertEqualsWithDelta($expectedMidrange, $midrange, 0.01);
    }

    /**
     * Data provider for midrange test
     * @return array [ [ numbers ], range ]
     */
    public function dataProviderForMidrange(): array
    {
        return [
            [ [ 1, 1, 1 ], 1 ],
            [ [ 1, 1, 2 ], 1.5 ],
            [ [ 1, 2, 1 ], 1.5 ],
            [ [ 8, 4, 3 ], 5.5 ],
            [ [ 9, 7, 8 ], 8 ],
            [ [ 13, 18, 13, 14, 13, 16, 14, 21, 13 ], 17 ],
            [ [ 1, 2, 4, 7 ], 4 ],
            [ [ 8, 9, 10, 10, 10, 11, 11, 11, 12, 13 ], 10.5 ],
            [ [ 6, 7, 8, 10, 12, 14, 14, 15, 16, 20 ], 13 ],
            [ [ 9, 10, 11, 13, 15, 17, 17, 18, 19, 23 ], 16 ],
            [ [ 12, 14, 16, 20, 24, 28, 28, 30, 32, 40 ], 26 ],
        ];
    }

    /**
     * @test   midrange when the array is empty
     * @throws \Exception
     */
    public function testMidrangeExceptionWhenEmptyArray()
    {
        // Given
        $numbers = [];

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        Descriptive::midrange($numbers);
    }

    /**
     * @test         populationVariance
     * @dataProvider dataProviderForPopulationVariance
     * @param        array $numbers
     * @param        float $expectedVariance
     * @throws       \Exception
     */
    public function testPopulationVariance(array $numbers, float $expectedVariance)
    {
        // When
        $variance = Descriptive::populationVariance($numbers);

        // Then
        $this->assertEqualsWithDelta($expectedVariance, $variance, 0.01);
    }

    /**
     * Data provider for population variance test
     * @return array [ [ numbers ], variance ]
     */
    public function dataProviderForPopulationVariance(): array
    {
        return [
            [ [ -10, 0, 10, 20, 30 ], 200 ],
            [ [ 8, 9, 10, 11, 12 ], 2 ],
            [ [ 600, 470, 170, 430, 300 ], 21704 ],
            [ [ -5, 1, 8, 7, 2], 21.84 ],
            [ [ 3, 7, 34, 25, 46, 7754, 3, 6 ], 6546331.937 ],
            [ [ 4, 6, 2, 2, 2, 2, 3, 4, 1, 3 ], 1.89 ],
            [ [ -3432, 5, 23, 9948, -74 ], 20475035.6 ],
        ];
    }

    /**
     * @test   populationVariance when the array is empty
     * @throws \Exception
     */
    public function testPopulationVarianceExceptionWhenEmptyArray()
    {
        // Given
        $numbers = [];

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        Descriptive::populationVariance($numbers);
    }

    /**
     * @test         sampleVariance
     * @dataProvider dataProviderForSampleVariance
     * @param        array $numbers
     * @param        float $expectedVariance
     * @throws       \Exception
     */
    public function testSampleVariance(array $numbers, float $expectedVariance)
    {
        // When
        $variance = Descriptive::sampleVariance($numbers);

        // Then
        $this->assertEqualsWithDelta($expectedVariance, $variance, 0.01);
    }

    /**
     * Data provider for sample variance test
     * @return array [ [ numbers ], variance ]
     */
    public function dataProviderForSampleVariance(): array
    {
        return [
            [ [ -10, 0, 10, 20, 30 ], 250 ],
            [ [ 8, 9, 10, 11, 12 ], 2.5 ],
            [ [ 600, 470, 170, 430, 300 ], 27130 ],
            [ [ -5, 1, 8, 7, 2 ], 27.3 ],
            [ [ 3, 7, 34, 25, 46, 7754, 3, 6 ], 7481522.21429 ],
            [ [ 4, 6, 2, 2, 2, 2, 3, 4, 1, 3 ], 2.1 ],
            [ [ -3432, 5, 23, 9948, -74 ], 25593794.5 ],
            [ [ 3, 21, 98, 203, 17, 9 ],  6219.9 ],
            [ [ 170, 300, 430, 470, 600 ], 27130 ],
            [ [ 1550, 1700, 900, 850, 1000, 950 ], 135416.66668 ],
            [ [ 1245, 1255, 1654, 1547, 1787, 1989, 1878, 2011, 2145, 2545, 2656 ], 210804.29090909063 ],
        ];
    }

    /**
     * @test sampleVariance when the array is empty
     * @throws   \Exception
     */
    public function testSampleVarianceExceptionWhenEmptyArray()
    {
        // Given
        $numbers = [];

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        Descriptive::sampleVariance($numbers);
    }

    /**
     * @test sampleVariance when the array only contains one item
     * @throws   \Exception
     */
    public function testSampleVarianceZeroWhenListContainsOnlyOneItem()
    {
        // When
        $variance = Descriptive::sampleVariance([5]);

        // Then
        $this->assertEquals(0, $variance);
    }

    /**
     * @test   variance when the degrees of freedom is less than zero
     * @throws \Exception
     */
    public function testVarianceExceptionDFLessThanZero()
    {
        // Given
        $numbers = [1, 2, 3];
        $ν       = -1;

        // Then
        $this->expectException(Exception\OutOfBoundsException::class);

        // When
        Descriptive::variance($numbers, $ν);
    }

    /**
     * @test         weightedSampleVariance unbiased
     * @dataProvider dataProviderForWeightedSampleVarianceUnbiased
     * @param        array $numbers
     * @param        array $weights
     * @param        float $expectedVariance
     * @throws       \Exception
     */
    public function testWeightedSampleVarianceUnbiased(array $numbers, array $weights, float $expectedVariance)
    {
        // When
        $variance = Descriptive::weightedSampleVariance($numbers, $weights);

        // Then
        $this->assertEqualsWithDelta($expectedVariance, $variance, 0.00001);
    }

     /**
     * Data provider for weighted sample variance test
     * @return array [ [ numbers, weights ], variance ]
     */
    public function dataProviderForWeightedSampleVarianceUnbiased(): array
    {
        return [
            [ [ -10, 0, 10, 20, 30 ], [1, 1, 1, 1, 1], 250 ],
            [ [ 8, 9, 10, 11, 12 ], [1, 1, 1, 1, 1], 2.5 ],
            [ [ 600, 470, 170, 430, 300 ], [1, 1, 1, 1, 1], 27130 ],
            [ [ -5, 1, 8, 7, 2 ], [1, 1, 1, 1, 1], 27.3 ],
            [ [ 3, 7, 34, 25, 46, 7754, 3, 6 ], [1, 1, 1, 1, 1, 1, 1, 1], 7481522.21429 ],
        ];
    }

    /**
     * @test         weightedSampleVariance biased
     * @dataProvider dataProviderForWeightedSampleVarianceBiased
     * @param        array $numbers
     * @param        array $weights
     * @param        float $expectedVariance
     * @throws       \Exception
     */
    public function testWeightedSampleVarianceBiased(array $numbers, array $weights, float $expectedVariance)
    {
        // Given
        $biased = true;

        // When
        $variance = Descriptive::weightedSampleVariance($numbers, $weights, $biased);

        // Then
        $this->assertEqualsWithDelta($expectedVariance, $variance, 0.1);
    }

    /**
     * Data provider for weighted sample variance test
     * Test data created with R package Weighted.Desc.Stat: w.var(x, w)
     * @return array [ [ numbers, weights ], variance ]
     */
    public function dataProviderForWeightedSampleVarianceBiased(): array
    {
        return [
            [ [ -10, 0, 10, 20, 30 ], [1, 1, 1, 1, 1], 200 ],
            [ [ 8, 9, 10, 11, 12 ], [1, 1, 1, 1, 1], 2 ],
            [ [ 8, 9, 10, 11, 12 ], [0.3, 0.3, 0.2, 0.2, 0.1], 1.702479 ],
            [ [ 600, 470, 170, 430, 300 ], [1, 1, 1, 1, 1], 21704 ],
            [ [ -5, 1, 8, 7, 2 ], [1, 1, 1, 1, 1], 21.84 ],
            [ [ 3, 7, 34, 25, 46, 7754, 3, 6 ], [1, 1, 1, 1, 1, 1, 1, 1], 6546332 ],
        ];
    }

    /**
     * @test   weightedSampleVariance is zero when there is only one number.
     * @throws Exception\BadDataException
     */
    public function testWeightedSampleVarianceSetOfOne()
    {
        // Given
        $numbers = [4];
        $weights = [1];

        // When
        $variance = Descriptive::weightedSampleVariance($numbers, $weights);

        // Then
        $this->assertEquals(0, $variance);
    }

    /**
     * @test   weightedSampleVariance throws a BadDataException if the weights and numbers have different counts
     * @throws Exception\BadDataException
     */
    public function testWeightedSampleVarianceException()
    {
        // Given
        $numbers = [1, 2, 3];
        $weights = [1, 1];

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        Descriptive::weightedSampleVariance($numbers, $weights);
    }

    /**
     * @test         standardDeviation using population variance
     * @dataProvider dataProviderForStandardDeviationUsingPopulationVariance
     * @param        array $numbers
     * @param        float $expectedStandardDeviation
     * @throws       \Exception
     */
    public function testStandardDeviationUsingPopulationVariance(array $numbers, float $expectedStandardDeviation)
    {
        // When
        $sd = Descriptive::standardDeviation($numbers, true);

        // Then
        $this->assertEqualsWithDelta($expectedStandardDeviation, $sd, 0.01);
    }

    /**
     * @test         sd using population variance
     * @dataProvider dataProviderForStandardDeviationUsingPopulationVariance
     * @param        array $numbers
     * @param        float $expectedStandardDeviation
     * @throws       \Exception
     */
    public function testSdUsingPopulationVariance(array $numbers, float $expectedStandardDeviation)
    {
        // When
        $sd = Descriptive::sd($numbers, true);

        // Then
        $this->assertEqualsWithDelta($expectedStandardDeviation, $sd, 0.01);
    }

    /**
     * Data provider for standard deviation test
     * @return array [ [ numbers ], mean ]
     */
    public function dataProviderForStandardDeviationUsingPopulationVariance(): array
    {
        return [
            [ [ -10, 0, 10, 20, 30 ], 10 * \sqrt(2) ],
            [ [ 8, 9, 10, 11, 12 ], \sqrt(2) ],
            [ [ 600, 470, 170, 430, 300], 147.32 ],
            [ [ -5, 1, 8, 7, 2], 4.67 ],
            [ [ 3, 7, 34, 25, 46, 7754, 3, 6 ], 2558.580063 ],
            [ [ 4, 6, 2, 2, 2, 2, 3, 4, 1, 3 ], 1.374772708 ],
            [ [ -3432, 5, 23, 9948, -74 ], 4524.934872 ],
        ];
    }

    /**
     * @test         standardDeviation using sample variance
     * @dataProvider dataProviderForStandardDeviationUsingSampleVariance
     * @param        array $numbers
     * @param        float $expectedStandardDeviation
     * @throws       \Exception
     */
    public function testStandardDeviationUsingSampleVariance(array $numbers, float $expectedStandardDeviation)
    {
        // When
        $sd = Descriptive::standardDeviation($numbers);

        // Then
        $this->assertEqualsWithDelta($expectedStandardDeviation, $sd, 0.01);
    }

    /**
     * @test         sd using sample variance
     * @dataProvider dataProviderForStandardDeviationUsingSampleVariance
     * @param        array $numbers
     * @param        float $expectedStandardDeviation
     * @throws       \Exception
     */
    public function testSDeviationUsingSampleVariance(array $numbers, float $expectedStandardDeviation)
    {
        // When
        $sd = Descriptive::sd($numbers);

        // Then
        $this->assertEqualsWithDelta($expectedStandardDeviation, $sd, 0.01);
    }

    /**
     * Data provider for standard deviation using sample variance test
     * @return array [ [ numbers ], mean ]
     */
    public function dataProviderForStandardDeviationUsingSampleVariance(): array
    {
        return [
            [ [ 3, 21, 98, 203, 17, 9 ],  78.86634 ],
            [ [ 170, 300, 430, 470, 600 ], 164.7118696390761 ],
            [ [ 1550, 1700, 900, 850, 1000, 950 ], 367.99 ],
            [ [ 1245, 1255, 1654, 1547, 1787, 1989, 1878, 2011, 2145, 2545, 2656 ], 459.13 ],
        ];
    }

    /**
     * @test   standardDeviation when the array is empty
     * @throws \Exception
     */
    public function testStandardDeviationExceptionWhenEmptyArray()
    {
        // Given
        $numbers = [];

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        Descriptive::standardDeviation($numbers);
    }

    /**
     * @test     sd when the array is empty
     * @throws   \Exception
     */
    public function testSDExceptionWhenEmptyArray()
    {
        // Given
        $numbers = [];

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        Descriptive::sd($numbers);
    }

    /**
     * @test         meanAbsoluteDeviation
     * @dataProvider dataProviderForMeanAbsoluteDeviation
     * @param        array $numbers
     * @param        float $expectedMad
     * @throws       \Exception
     */
    public function testMeanAbsoluteDeviation(array $numbers, float $expectedMad)
    {
        // When
        $mad = Descriptive::meanAbsoluteDeviation($numbers);

        // Then
        $this->assertEqualsWithDelta($expectedMad, $mad, 0.01);
    }

    /**
     * Data provider for MAD (mean) test
     * @return array [ [ numbers ], mad ]
     */
    public function dataProviderForMeanAbsoluteDeviation(): array
    {
        return [
            [ [ 92, 83, 88, 94, 91, 85, 89, 90 ], 2.75 ],
            [ [ 2, 2, 3, 4, 14 ], 3.6 ],
        ];
    }

    /**
     * @test   meanAbsoluteDeviation when the array is empty
     * @throws \Exception
     */
    public function testMeanAbsoluteDeviationExceptionWhenEmptyArray()
    {
        // Given
        $numbers = [];

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        Descriptive::meanAbsoluteDeviation($numbers);
    }

    /**
     * @test         medianAbsoluteDeviation
     * @dataProvider dataProviderForMedianAbsoluteDeviation
     * @param        array $numbers
     * @param        float $expectedMad
     * @throws       \Exception
     */
    public function testMedianAbsoluteDeviation(array $numbers, float $expectedMad)
    {
        // When
        $mad = Descriptive::medianAbsoluteDeviation($numbers);

        // Then
        $this->assertEqualsWithDelta($expectedMad, $mad, 0.01);
    }

    /**
     * Data provider for MAD (median) test
     * @return array [ [ numbers ], mad ]
     */
    public function dataProviderForMedianAbsoluteDeviation(): array
    {
        return [
            [ [ 1, 1, 2, 2, 4, 6, 9 ], 1 ],
            [ [ 92, 83, 88, 94, 91, 85, 89, 90 ], 2 ],
            [ [ 2, 2, 3, 4, 14 ], 1 ],
        ];
    }

    /**
     * @test   medianAbsoluteDeviation when array is empty
     * @throws \Exception
     */
    public function testMedianAbsoluteDeviationExceptionWhenEmptyArray()
    {
        // Given
        $numbers = [];

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        Descriptive::medianAbsoluteDeviation($numbers);
    }

    /**
     * @test         quartilesExclusive
     * @dataProvider dataProviderForQuartilesExclusive
     * @param        array $numbers
     * @param        array $expectedQuartiles
     * @throws       \Exception
     */
    public function testQuartilesExclusive(array $numbers, array $expectedQuartiles)
    {
        // When
        $quartiles = Descriptive::quartilesExclusive($numbers);

        // Then
        $this->assertEquals($expectedQuartiles, $quartiles);
    }

    /**
     * @return array [numbers, quartiles]
     */
    public function dataProviderForQuartilesExclusive(): array
    {
        return [
            [
                [ 6, 7, 15, 36, 39, 40, 41, 42, 43, 47, 49],
                [ '0%' => 6, 'Q1' => 15, 'Q2' => 40, 'Q3' => 43, '100%' => 49, 'IQR' => 28 ],
            ],
            [
                [ 7, 15, 36, 39, 40, 41 ],
                [ '0%' => 7, 'Q1' => 15, 'Q2' => 37.5, 'Q3' => 40, '100%' => 41, 'IQR' => 25 ],
            ],
            [
                [ 0, 2, 2, 4, 5, 6, 7, 7, 8, 9, 34, 34, 43, 54, 54, 76, 234 ],
                [ '0%' => 0, 'Q1' => 4.5, 'Q2' => 8, 'Q3' => 48.5, '100%' => 234, 'IQR' => 44 ],
            ],
            [
                [0],
                [ '0%' => 0, 'Q1' => 0, 'Q2' => 0, 'Q3' => 0, '100%' => 0, 'IQR' => 0 ],
            ]
        ];
    }

    /**
     * @test   quartilesExclusive when the array is empty
     * @throws \Exception
     */
    public function testQuartilesExclusiveExceptionWhenEmptyArray()
    {
        // Given
        $numbers = [];

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        Descriptive::quartilesExclusive($numbers);
    }

    /**
     * @test         quartilesInclusive
     * @dataProvider dataProviderForQuartilesInclusive
     * @param        array $numbers
     * @param        array $expectedQuartiles
     * @throws       \Exception
     */
    public function testQuartilesInclusive(array $numbers, array $expectedQuartiles)
    {
        // When
        $quartiles = Descriptive::quartilesInclusive($numbers);

        // Then
        $this->assertEquals($expectedQuartiles, $quartiles);
    }

    /**
     * @return array [numbers, quartiles]
     */
    public function dataProviderForQuartilesInclusive(): array
    {
        return [
            [
                [ 6, 7, 15, 36, 39, 40, 41, 42, 43, 47, 49],
                [ '0%' => 6, 'Q1' => 25.5, 'Q2' => 40, 'Q3' => 42.5, '100%' => 49, 'IQR' => 17 ],
            ],
            [
                [ 7, 15, 36, 39, 40, 41 ],
                [ '0%' => 7, 'Q1' => 15, 'Q2' => 37.5, 'Q3' => 40, '100%' => 41, 'IQR' => 25 ],
            ],
            [
                [ 0, 2, 2, 4, 5, 6, 7, 7, 8, 9, 34, 34, 43, 54, 54, 76, 234 ],
                [ '0%' => 0, 'Q1' => 5, 'Q2' => 8, 'Q3' => 43, '100%' => 234, 'IQR' => 38 ],
            ]
        ];
    }

    /**
     * @test   quartilesInclusive when the array is empty
     * @throws \Exception
     */
    public function testQuartilesInclusiveExceptionWhenEmptyArray()
    {
        // Given
        $numbers = [];

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        Descriptive::quartilesInclusive($numbers);
    }

    /**
     * @test         quartiles
     * @dataProvider dataProviderForQuartiles
     * @param        array  $numbers
     * @param        string $method
     * @param        array  $expectedQuartiles
     * @throws       \Exception
     */
    public function testQuartiles(array $numbers, string $method, array $expectedQuartiles)
    {
        // When
        $quartiles = Descriptive::quartiles($numbers, $method);

        // Then
        $this->assertEquals($expectedQuartiles, $quartiles);
    }

    /**
     * @return array [numbers, method, quartiles]
     */
    public function dataProviderForQuartiles(): array
    {
        return [
            [
                [ 6, 7, 15, 36, 39, 40, 41, 42, 43, 47, 49 ], 'Exclusive',
                [ '0%' => 6, 'Q1' => 15, 'Q2' => 40, 'Q3' => 43, '100%' => 49, 'IQR' => 28 ],
            ],
            [
                [ 7, 15, 36, 39, 40, 41 ], 'Exclusive',
                [ '0%' => 7, 'Q1' => 15, 'Q2' => 37.5, 'Q3' => 40, '100%' => 41, 'IQR' => 25 ],
            ],
            [
                [ 0, 2, 2, 4, 5, 6, 7, 7, 8, 9, 34, 34, 43, 54, 54, 76, 234 ], 'Exclusive',
                [ '0%' => 0, 'Q1' => 4.5, 'Q2' => 8, 'Q3' => 48.5, '100%' => 234, 'IQR' => 44 ],
            ],
            [
                [ 6, 7, 15, 36, 39, 40, 41, 42, 43, 47, 49 ], 'Inclusive',
                [ '0%' => 6, 'Q1' => 25.5, 'Q2' => 40, 'Q3' => 42.5, '100%' => 49, 'IQR' => 17 ],
            ],
            [
                [ 7, 15, 36, 39, 40, 41 ], 'Inclusive',
                [ '0%' => 7, 'Q1' => 15, 'Q2' => 37.5, 'Q3' => 40, '100%' => 41, 'IQR' => 25 ],
            ],
            [
                [ 0, 2, 2, 4, 5, 6, 7, 7, 8, 9, 34, 34, 43, 54, 54, 76, 234 ], 'Inclusive',
                [ '0%' => 0, 'Q1' => 5, 'Q2' => 8, 'Q3' => 43, '100%' => 234, 'IQR' => 38 ],
            ],
            [
                [ 6, 7, 15, 36, 39, 40, 41, 42, 43, 47, 49 ], 'Not_A_Real_Method_So_Default_Is_Used_Which_Is_Exclusive',
                [ '0%' => 6, 'Q1' => 15, 'Q2' => 40, 'Q3' => 43, '100%' => 49, 'IQR' => 28 ],
            ],
            [
                [ 7, 15, 36, 39, 40, 41 ], 'Not_A_Real_Method_So_Default_Is_Used_Which_Is_Exclusive',
                [ '0%' => 7, 'Q1' => 15, 'Q2' => 37.5, 'Q3' => 40, '100%' => 41, 'IQR' => 25 ],
            ],
            [
                [ 0, 2, 2, 4, 5, 6, 7, 7, 8, 9, 34, 34, 43, 54, 54, 76, 234 ], 'Not_A_Real_Method_So_Default_Is_Used_Which_Is_Exclusive',
                [ '0%' => 0, 'Q1' => 4.5, 'Q2' => 8, 'Q3' => 48.5, '100%' => 234, 'IQR' => 44 ],
            ],
        ];
    }

    /**
     * @test         interquartileRange
     * @dataProvider dataProviderForIQR
     * @param        array $numbers
     * @param        float $expectedIqr
     * @throws       \Exception
     */
    public function testInterquartileRange(array $numbers, float $expectedIqr)
    {
        // When
        $iqr = Descriptive::interquartileRange($numbers);

        // Then
        $this->assertEquals($expectedIqr, $iqr);
    }

    /**
     * @test         iqr
     * @dataProvider dataProviderForIQR
     * @param        array $numbers
     * @param        float $expectedIqr
     * @throws       \Exception
     */
    public function testIQR(array $numbers, float $expectedIqr)
    {
        // When
        $iqr = Descriptive::iqr($numbers);

        // Then
        $this->assertEquals($expectedIqr, $iqr);
    }

    /**
     * @return array [numbers, iqr]
     */
    public function dataProviderForIQR(): array
    {
        return [
            [ [6, 7, 15, 36, 39, 40, 41, 42, 43, 47, 49], 28 ],
            [ [7, 15, 36, 39, 40, 41 ], 25 ],
            [ [0], 0 ],
            [ [1], 0 ],
            [ [9], 0 ],
        ];
    }

    /**
     * @test         percentile
     * @dataProvider dataProviderForPercentile
     * @param        array $numbers
     * @param        float $percentile
     * @param        float $expectedValue
     * @throws       \Exception
     */
    public function testPercentile(array $numbers, float $percentile, float $expectedValue)
    {
        // When
        $value = Descriptive::percentile($numbers, $percentile);

        // Then
        $this->assertEqualsWithDelta($expectedValue, $value, 0.0000001);
    }

    /**
     * @return array [numbers, percentile, value]
     */
    public function dataProviderForPercentile(): array
    {
        return [
            // Wikipedia
            [[15, 20, 35, 40, 50], 40, 29],
            [[1, 2, 3, 4], 75, 3.25],

            // numpy.percentile / Excel 2015 Mac
            // All int percentiles 0 - 100
            [[15, 20, 35, 40, 50], 0, 15],
            [[15, 20, 35, 40, 50], 1, 15.2],
            [[15, 20, 35, 40, 50], 2, 15.4],
            [[15, 20, 35, 40, 50], 3, 15.6],
            [[15, 20, 35, 40, 50], 4, 15.8],
            [[15, 20, 35, 40, 50], 5, 16.0],
            [[15, 20, 35, 40, 50], 6, 16.2],
            [[15, 20, 35, 40, 50], 7, 16.4],
            [[15, 20, 35, 40, 50], 8, 16.6],
            [[15, 20, 35, 40, 50], 9, 16.8],
            [[15, 20, 35, 40, 50], 10, 17.0],
            [[15, 20, 35, 40, 50], 11, 17.2],
            [[15, 20, 35, 40, 50], 12, 17.4],
            [[15, 20, 35, 40, 50], 13, 17.6],
            [[15, 20, 35, 40, 50], 14, 17.8],
            [[15, 20, 35, 40, 50], 15, 18.0],
            [[15, 20, 35, 40, 50], 16, 18.2],
            [[15, 20, 35, 40, 50], 17, 18.4],
            [[15, 20, 35, 40, 50], 18, 18.6],
            [[15, 20, 35, 40, 50], 19, 18.8],
            [[15, 20, 35, 40, 50], 20, 19.0],
            [[15, 20, 35, 40, 50], 21, 19.2],
            [[15, 20, 35, 40, 50], 22, 19.4],
            [[15, 20, 35, 40, 50], 23, 19.6],
            [[15, 20, 35, 40, 50], 24, 19.8],
            [[15, 20, 35, 40, 50], 25, 20.0],
            [[15, 20, 35, 40, 50], 26, 20.6],
            [[15, 20, 35, 40, 50], 27, 21.2],
            [[15, 20, 35, 40, 50], 28, 21.8],
            [[15, 20, 35, 40, 50], 29, 22.4],
            [[15, 20, 35, 40, 50], 30, 23.0],
            [[15, 20, 35, 40, 50], 31, 23.6],
            [[15, 20, 35, 40, 50], 32, 24.2],
            [[15, 20, 35, 40, 50], 33, 24.8],
            [[15, 20, 35, 40, 50], 34, 25.4],
            [[15, 20, 35, 40, 50], 35, 26.0],
            [[15, 20, 35, 40, 50], 36, 26.6],
            [[15, 20, 35, 40, 50], 37, 27.2],
            [[15, 20, 35, 40, 50], 38, 27.8],
            [[15, 20, 35, 40, 50], 39, 28.4],
            [[15, 20, 35, 40, 50], 40, 29.0],
            [[15, 20, 35, 40, 50], 41, 29.6],
            [[15, 20, 35, 40, 50], 42, 30.2],
            [[15, 20, 35, 40, 50], 43, 30.8],
            [[15, 20, 35, 40, 50], 44, 31.4],
            [[15, 20, 35, 40, 50], 45, 32.0],
            [[15, 20, 35, 40, 50], 46, 32.6],
            [[15, 20, 35, 40, 50], 47, 33.2],
            [[15, 20, 35, 40, 50], 48, 33.8],
            [[15, 20, 35, 40, 50], 49, 34.4],
            [[15, 20, 35, 40, 50], 50, 35.0],
            [[15, 20, 35, 40, 50], 51, 35.2],
            [[15, 20, 35, 40, 50], 52, 35.4],
            [[15, 20, 35, 40, 50], 53, 35.6],
            [[15, 20, 35, 40, 50], 54, 35.8],
            [[15, 20, 35, 40, 50], 55, 36.0],
            [[15, 20, 35, 40, 50], 56, 36.2],
            [[15, 20, 35, 40, 50], 57, 36.4],
            [[15, 20, 35, 40, 50], 58, 36.6],
            [[15, 20, 35, 40, 50], 59, 36.8],
            [[15, 20, 35, 40, 50], 60, 37.0],
            [[15, 20, 35, 40, 50], 61, 37.2],
            [[15, 20, 35, 40, 50], 62, 37.4],
            [[15, 20, 35, 40, 50], 63, 37.6],
            [[15, 20, 35, 40, 50], 64, 37.8],
            [[15, 20, 35, 40, 50], 65, 38.0],
            [[15, 20, 35, 40, 50], 66, 38.2],
            [[15, 20, 35, 40, 50], 67, 38.4],
            [[15, 20, 35, 40, 50], 68, 38.6],
            [[15, 20, 35, 40, 50], 69, 38.8],
            [[15, 20, 35, 40, 50], 70, 39.0],
            [[15, 20, 35, 40, 50], 71, 39.2],
            [[15, 20, 35, 40, 50], 72, 39.4],
            [[15, 20, 35, 40, 50], 73, 39.6],
            [[15, 20, 35, 40, 50], 74, 39.8],
            [[15, 20, 35, 40, 50], 75, 40.0],
            [[15, 20, 35, 40, 50], 76, 40.4],
            [[15, 20, 35, 40, 50], 77, 40.8],
            [[15, 20, 35, 40, 50], 78, 41.2],
            [[15, 20, 35, 40, 50], 79, 41.6],
            [[15, 20, 35, 40, 50], 80, 42.0],
            [[15, 20, 35, 40, 50], 81, 42.4],
            [[15, 20, 35, 40, 50], 82, 42.8],
            [[15, 20, 35, 40, 50], 83, 43.2],
            [[15, 20, 35, 40, 50], 84, 43.6],
            [[15, 20, 35, 40, 50], 85, 44.0],
            [[15, 20, 35, 40, 50], 86, 44.4],
            [[15, 20, 35, 40, 50], 87, 44.8],
            [[15, 20, 35, 40, 50], 88, 45.2],
            [[15, 20, 35, 40, 50], 89, 45.6],
            [[15, 20, 35, 40, 50], 90, 46.0],
            [[15, 20, 35, 40, 50], 91, 46.4],
            [[15, 20, 35, 40, 50], 92, 46.8],
            [[15, 20, 35, 40, 50], 93, 47.2],
            [[15, 20, 35, 40, 50], 94, 47.6],
            [[15, 20, 35, 40, 50], 95, 48.0],
            [[15, 20, 35, 40, 50], 96, 48.4],
            [[15, 20, 35, 40, 50], 97, 48.8],
            [[15, 20, 35, 40, 50], 98, 49.2],
            [[15, 20, 35, 40, 50], 99, 49.6],
            [[15, 20, 35, 40, 50], 100, 50.0],

            // Float percentiles
            [[15, 20, 35, 40, 50], 0.5, 15.1],
            [[15, 20, 35, 40, 50], 1.5, 15.299999999999999],
            [[15, 20, 35, 40, 50], 5.5, 16.100000000000001],
            [[15, 20, 35, 40, 50], 50.5, 35.099999999999994],
            [[15, 20, 35, 40, 50], 60.2, 37.039999999999999],
            [[15, 20, 35, 40, 50], 91.8, 46.719999999999999],
            [[15, 20, 35, 40, 50], 99.9, 49.960000000000008],

            // Edge case: one-element list
            [[5], 0, 5],
            [[5], 1, 5],
            [[5], 50.5, 5],
            [[5], 99, 5],
            [[5], 100, 5],

            // Two-element list
            [[2, 3], 0, 2],
            [[2, 3], 1, 2.01],
            [[2, 3], 50.5, 2.505],
            [[2, 3], 99, 2.9899999999999998],
            [[2, 3], 100, 3],

            // Big list
            [[1,2,3,4,5,6,7,8,9,9,8,7,6,5,4,3,2,1,2,3,4,5,6,7,8,9,1,2,3,4,5,6,7,8,9,9,9,9,9,9,9,9,8,9,8,9,8,9,8,7,6,5,4,3,2,1,2,3,4,3,4,3,4,5,6,7,8,7,8,7,8,9,0,0,9,0,9,8,7,6,5,4,3,2,1], 45.3, 5],

            // More random test cases
            [[3, 6, 7, 8, 8, 10, 13, 15, 16, 20], 0, 3.0 ],
            [[3, 6, 7, 8, 8, 10, 13, 15, 16, 20], 25, 7.25 ],
            [[3, 6, 7, 8, 8, 10, 13, 15, 16, 20], 50, 9.0 ],
            [[3, 6, 7, 8, 8, 10, 13, 15, 16, 20], 75, 14.5 ],
            [[3, 6, 7, 8, 8, 10, 13, 15, 16, 20], 100, 20.0 ],
            [[3, 6, 7, 8, 8, 9, 10, 13, 15, 16, 20], 0, 3.0 ],
            [[3, 6, 7, 8, 8, 9, 10, 13, 15, 16, 20], 25, 7.5 ],
            [[3, 6, 7, 8, 8, 9, 10, 13, 15, 16, 20], 50, 9.0 ],
            [[3, 6, 7, 8, 8, 9, 10, 13, 15, 16, 20], 75, 14.0 ],
            [[3, 6, 7, 8, 8, 9, 10, 13, 15, 16, 20], 100, 20.0 ],
        ];
    }

    /**
     * @test   percentile throws an Exception\BadDataException if numbers is empty
     * @throws \Exception
     */
    public function testPercentileEmptyList()
    {
        // Given
        $numbers = [];
        $P       = 5;

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        Descriptive::percentile($numbers, $P);
    }

    /**
     * @test   percentile throws an Exception\OutOfBoundsException if P is < 0
     * @throws \Exception
     */
    public function testPercentileOutOfLowerBoundsP()
    {
        // Given
        $numbers = [1, 2, 3];
        $P       = -4;

        // Then
        $this->expectException(Exception\OutOfBoundsException::class);

        // When
        Descriptive::percentile($numbers, $P);
    }

    /**
     * @test   percentile throws an Exception\OutOfBoundsException if P is > 100
     * @throws \Exception
     */
    public function testPercentileOutOfUpperBoundsP()
    {
        // Given
        $numbers = [1, 2, 3];
        $P       = 101;

        // Then
        $this->expectException(Exception\OutOfBoundsException::class);

        // When
        Descriptive::percentile($numbers, $P);
    }

    /**
     * @test         midhinge
     * @dataProvider dataProviderForMidhinge
     * @param        array $numbers
     * @param        float $expectedMidhinge
     * @throws       \Exception
     */
    public function testMidhinge(array $numbers, float $expectedMidhinge)
    {
        // When
        $midhinge = Descriptive::midhinge($numbers);

        // Then
        $this->assertEqualsWithDelta($expectedMidhinge, $midhinge, 0.01);
    }

    /**
     * @return array [numbers, midhinge]
     */
    public function dataProviderForMidhinge(): array
    {
        return [
            [ [1, 2, 3, 4, 5, 6], 3.5 ],
            [ [5, 5, 7, 8, 8, 11, 12, 12, 14, 15, 16, 19, 21, 22, 22, 26, 26, 26, 28, 29, 29, 32, 33, 34, 34, 34, 34, 35, 35, 37, 38, 38], 23.5],
            [ [36, 34, 21, 10, 20, 24, 31, 30, 30, 30, 30, 24, 30, 24, 39, 6, 32, 33, 33, 25, 26, 35, 8, 5, 30, 40, 9, 32, 25, 40, 24, 38], 28.5],
            [ [8, 10, 11, 12, 12, 13, 17, 18, 19, 19, 21, 23, 24, 24, 25, 25, 27, 27, 28, 28, 29, 29, 30, 30, 32, 33, 34, 35, 36, 37, 37, 40], 24.75 ],
        ];
    }

    /**
     * @test   midhinge throws an Exception\BadDataException if numbers is empty
     * @throws \Exception
     */
    public function testMidhingeEmptyList()
    {
        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        Descriptive::midhinge([]);
    }

    /**
     * @test         coefficientOfVariation
     * @dataProvider dataProviderForCoefficientOfVariation
     * @param        array $numbers
     * @param        float $expectedCv
     * @throws       \Exception
     */
    public function testsCoefficientOfVariation(array $numbers, float $expectedCv)
    {
        // When
        $cv = Descriptive::coefficientOfVariation($numbers);

        // Then
        $this->assertEqualsWithDelta($expectedCv, $cv, 0.0001);
    }

    /**
     * @return array [numbers, cv]
     */
    public function dataProviderForCoefficientOfVariation(): array
    {
        return [
            [ [1, 2, 3, 4, 5, 6 ,7, 8], 0.54433 ],
            [ [4, 7, 43, 12, 23, 76, 45, 3, 62, 23, 34, 44, 41], 0.70673 ],
            [ [3, 3, 3, 6, 6, 5, 9], 0.44721 ],
            [ [100, 100, 100], 0 ],
            [ [0, 10, 20, 30, 40], 0.7905 ],
            [ [32, 50, 68, 86, 104], 0.41852941176471 ],
        ];
    }

    /**
     * @test   describe - population
     * @throws \Exception
     */
    public function testDescribePopulation()
    {
        // Given
        $population = true;

        // When
        $stats = Descriptive::describe([ 13, 18, 13, 14, 13, 16, 14, 21, 13 ], $population);

        // Then
        $this->assertTrue(\is_array($stats));
        $this->assertArrayHasKey('n', $stats);
        $this->assertArrayHasKey('min', $stats);
        $this->assertArrayHasKey('max', $stats);
        $this->assertArrayHasKey('mean', $stats);
        $this->assertArrayHasKey('median', $stats);
        $this->assertArrayHasKey('mode', $stats);
        $this->assertArrayHasKey('range', $stats);
        $this->assertArrayHasKey('midrange', $stats);
        $this->assertArrayHasKey('variance', $stats);
        $this->assertArrayHasKey('sd', $stats);
        $this->assertArrayHasKey('cv', $stats);
        $this->assertArrayHasKey('mean_mad', $stats);
        $this->assertArrayHasKey('median_mad', $stats);
        $this->assertArrayHasKey('quartiles', $stats);
        $this->assertArrayHasKey('midhinge', $stats);
        $this->assertArrayHasKey('skewness', $stats);
        $this->assertArrayHasKey('ses', $stats);
        $this->assertArrayHasKey('kurtosis', $stats);
        $this->assertArrayHasKey('sek', $stats);
        $this->assertArrayHasKey('sem', $stats);
        $this->assertArrayHasKey('ci_95', $stats);
        $this->assertArrayHasKey('ci_99', $stats);
        $this->assertTrue(\is_int($stats['n']));
        $this->assertTrue(\is_numeric($stats['min']));
        $this->assertTrue(\is_numeric($stats['max']));
        $this->assertTrue(\is_numeric($stats['mean']));
        $this->assertTrue(\is_numeric($stats['median']));
        $this->assertTrue(\is_array($stats['mode']));
        $this->assertTrue(\is_numeric($stats['range']));
        $this->assertTrue(\is_numeric($stats['midrange']));
        $this->assertTrue(\is_numeric($stats['variance']));
        $this->assertTrue(\is_numeric($stats['sd']));
        $this->assertTrue(\is_numeric($stats['cv']));
        $this->assertTrue(\is_numeric($stats['mean_mad']));
        $this->assertTrue(\is_numeric($stats['median_mad']));
        $this->assertTrue(\is_array($stats['quartiles']));
        $this->assertTrue(\is_numeric($stats['midhinge']));
        $this->assertTrue(\is_numeric($stats['skewness']));
        $this->assertTrue(\is_numeric($stats['ses']));
        $this->assertTrue(\is_numeric($stats['kurtosis']));
        $this->assertTrue(\is_numeric($stats['sek']));
        $this->assertTrue(\is_numeric($stats['sem']));
        $this->assertTrue(\is_array($stats['ci_95']));
        $this->assertTrue(\is_array($stats['ci_99']));
    }

    /**
     * @test   describe - sample
     * @throws \Exception
     */
    public function testDescribeSample()
    {
        // Given
        $population = false;

        // When
        $stats = Descriptive::describe([ 13, 18, 13, 14, 13, 16, 14, 21, 13 ], $population);

        // Then
        $this->assertTrue(\is_array($stats));
        $this->assertArrayHasKey('n', $stats);
        $this->assertArrayHasKey('min', $stats);
        $this->assertArrayHasKey('max', $stats);
        $this->assertArrayHasKey('mean', $stats);
        $this->assertArrayHasKey('median', $stats);
        $this->assertArrayHasKey('mode', $stats);
        $this->assertArrayHasKey('range', $stats);
        $this->assertArrayHasKey('midrange', $stats);
        $this->assertArrayHasKey('variance', $stats);
        $this->assertArrayHasKey('sd', $stats);
        $this->assertArrayHasKey('cv', $stats);
        $this->assertArrayHasKey('quartiles', $stats);
        $this->assertArrayHasKey('midhinge', $stats);
        $this->assertArrayHasKey('skewness', $stats);
        $this->assertArrayHasKey('ses', $stats);
        $this->assertArrayHasKey('kurtosis', $stats);
        $this->assertArrayHasKey('sek', $stats);
        $this->assertArrayHasKey('sem', $stats);
        $this->assertArrayHasKey('ci_95', $stats);
        $this->assertArrayHasKey('ci_99', $stats);
        $this->assertTrue(\is_int($stats['n']));
        $this->assertTrue(\is_numeric($stats['min']));
        $this->assertTrue(\is_numeric($stats['max']));
        $this->assertTrue(\is_numeric($stats['mean']));
        $this->assertTrue(\is_numeric($stats['median']));
        $this->assertTrue(\is_array($stats['mode']));
        $this->assertTrue(\is_numeric($stats['range']));
        $this->assertTrue(\is_numeric($stats['midrange']));
        $this->assertTrue(\is_numeric($stats['variance']));
        $this->assertTrue(\is_numeric($stats['sd']));
        $this->assertTrue(\is_numeric($stats['cv']));
        $this->assertTrue(\is_array($stats['quartiles']));
        $this->assertTrue(\is_numeric($stats['midhinge']));
        $this->assertTrue(\is_numeric($stats['skewness']));
        $this->assertTrue(\is_numeric($stats['ses']));
        $this->assertTrue(\is_numeric($stats['kurtosis']));
        $this->assertTrue(\is_numeric($stats['sek']));
        $this->assertTrue(\is_numeric($stats['sem']));
        $this->assertTrue(\is_array($stats['ci_95']));
        $this->assertTrue(\is_array($stats['ci_99']));
    }

    /**
     * @test         describe will return null ses for values of n < 3
     * @dataProvider dataProviderForDescribeNullSes
     * @param        array $numbers
     * @throws       \Exception
     */
    public function testDescribeSesNullForSmallN(array $numbers)
    {
        // When
        $stats = Descriptive::describe($numbers);

        // Then
        $this->assertNull($stats['ses']);
    }

    /**
     * @return array [numbers]
     */
    public function dataProviderForDescribeNullSes(): array
    {
        return [
            [[-1]],
            [[0]],
            [[1]],
            [[2]],
            [[3]],
            [[4]],
            [[5]],
            [[10]],
            [[100]],
            [[999999]],
            [[-1, -1]],
            [[-1, 0]],
            [[0, -1]],
            [[0, 0]],
            [[0, 1]],
            [[1, 0]],
            [[1, 1]],
            [[1, 2]],
            [[5, 5]],
            [[10, 10]],
            [[9293, 85732]],
        ];
    }

    /**
     * @test         describe will return null sek for values of n < 4
     * @dataProvider dataProviderForDescribeNullSek
     * @param        array $numbers
     * @throws       \Exception
     */
    public function testDescribeSekNullForSmallN(array $numbers)
    {
        // When
        $stats = Descriptive::describe($numbers);

        // Then
        $this->assertNull($stats['sek']);
    }

    /**
     * @return array [numbers]
     */
    public function dataProviderForDescribeNullSek(): array
    {
        return [
            [[-1]],
            [[0]],
            [[1]],
            [[2]],
            [[3]],
            [[4]],
            [[5]],
            [[10]],
            [[100]],
            [[999999]],
            [[-1, -1]],
            [[-1, 0]],
            [[0, -1]],
            [[0, 0]],
            [[0, 1]],
            [[1, 0]],
            [[1, 1]],
            [[1, 2]],
            [[5, 5]],
            [[10, 10]],
            [[9293, 85732]],
            [[-1, -1, -1]],
            [[-1, 0, 0]],
            [[-1, 0, -1]],
            [[0, -1, 0]],
            [[0, -1, -1]],
            [[0, 0, -1]],
            [[-1, 0, -1]],
            [[0, 0, 0]],
            [[0, 1], 0],
            [[1, 0], 0],
            [[1, 1], 1],
            [[1, 2, 3]],
            [[5, 5], 5],
            [[10, 10, 10]],
            [[9293, 85732, 44837475]],
        ];
    }

    /**
     * @test         fiveNumberSummary
     * @dataProvider dataProviderForFiveNumberSummary
     * @param        array $numbers
     * @param        array $expectedSummary
     * @throws       \Exception
     */
    public function testFiveNumberSummary(array $numbers, array $expectedSummary)
    {
        // When
        $summary = Descriptive::fiveNumberSummary($numbers);

        // Then
        $this->assertEqualsWithDelta($expectedSummary, $summary, 0.0001);
    }

    /**
     * @return array
     */
    public function dataProviderForFiveNumberSummary(): array
    {
        return [
            [
                [0, 0, 1, 2, 63, 61, 27, 13],
                ['min' => 0, 'Q1' => 0.5, 'median' => 7.5, 'Q3' => 44.0, 'max' => 63],
            ],
        ];
    }
}
