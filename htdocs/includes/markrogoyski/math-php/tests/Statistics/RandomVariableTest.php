<?php

namespace MathPHP\Tests\Statistics;

use MathPHP\Exception;
use MathPHP\Statistics\RandomVariable;

class RandomVariableTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test         centralMoment
     * @dataProvider dataProviderForCentralMoment
     * @param        array $X
     * @param        int   $n
     * @param        float $expected
     * @throws       \Exception
     */
    public function testCentralMoment(array $X, int $n, float $expected)
    {
        // When
        $centralMoment = RandomVariable::centralMoment($X, $n);

        // Then
        $this->assertEqualsWithDelta($expected, $centralMoment, 0.0001);
    }

    /**
     * @return array [X, n, moment]
     */
    public function dataProviderForCentralMoment(): array
    {
        return [
            [ [ 600, 470, 170, 430, 300 ], 1, 0 ],
            [ [ 600, 470, 170, 430, 300 ], 2, 21704 ],
            [ [ 600, 470, 170, 430, 300 ], 3, -568512 ],
        ];
    }

    /**
     * @test   centralMoment error if X is empty
     * @throws \Exception
     */
    public function testCentralMomentNullIfXEmpty()
    {
        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        RandomVariable::centralMoment(array(), 3);
    }

    /**
     * @test         populationSkewness
     * @dataProvider dataProviderForPopulationSkewness
     * @param        array $X
     * @param        float $expected
     * @throws       \Exception
     */
    public function testPopulationSkewness(array $X, float $expected)
    {
        // When
        $populationSkewness = RandomVariable::populationSkewness($X);

        // Then
        $this->assertEqualsWithDelta($expected, $populationSkewness, 0.000001);
    }

    /**
     * Generated with R (e1071) skewness(data, type=1)
     * @return array [X, skewness]
     */
    public function dataProviderForPopulationSkewness(): array
    {
        return [
            [ [61,61,61,61,61,64,64,64,64,64,64,64,64,64,64,64,64,64,64,64,64,64,64,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,70,70,70,70,70,70,70,70,70,70,70,70,70,70,70,70,70,70,70,70,70,70,70,70,70,70,70,73,73,73,73,73,73,73,73], -0.1081544 ],
            [ [2, 3, -1, 3, 4, 5, 0, 2], -0.3677454 ],
            [ [1, 2, 3, 4, 5, 6, 8, 8], 0.079248 ],
            [ [1, 1, 3, 4, 5, 6, 7, 8], -0.079248 ],
            [ [3, 4, 5, 2, 3, 4, 5, 6, 4, 7], 0.3031933 ],
            [ [1, 1, 2, 2, 2, 2, 3, 3, 3, 4, 4, 5, 6, 7, 8], 0.7745239 ],
            [ [1,2,3,4,5,6,7,8], 0 ],
            [ [1, 2, 3, 4, 5, 9, 23, 32, 69], 1.605855 ],
            [ [3, 4, 5, 2, 3, 4, 5, 6, 4, 7 ], 0.3031933 ],
            [ [1, 1, 2, 2, 2, 2, 3, 3, 3, 4, 4, 5, 6, 7, 8 ], 0.7745239 ],
            [ [2, 3, -1, 3, 4, 5, 0, 2], -0.3677454 ],
            [ [-2.83, -0.95, -0.88, 1.21, -1.67, 0.83, -0.27, 1.36, -0.34, 0.48, -2.83, -0.95, -0.88, 1.21, -1.67], -0.1561309 ],
            [ [13, 18, 13, 14, 13, 16, 14, 21, 13], 1.230469],
        ];
    }

    /**
     * @test   populationSkewness error if array is empty
     * @throws \Exception
     */
    public function testPopulationSkewnessNullWhenEmptyArray()
    {
        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        RandomVariable::populationSkewness(array());
    }

    /**
     * @test         sampleSkewness
     * @dataProvider dataProviderForSampleSkewness
     * @param        array $X
     * @param        float $expected
     * @throws       \Exception
     */
    public function testSampleSkewness(array $X, float $expected)
    {
        // When
        $skewness = RandomVariable::sampleSkewness($X);

        // Then
        $this->assertEqualsWithDelta($expected, $skewness, 0.000001);
    }

    /**
     * Generated with R (e1071) skewness(data, type=2)
     * @return array [X, skewness]
     */
    public function dataProviderForSampleSkewness(): array
    {
        return [
            [ [61,61,61,61,61,64,64,64,64,64,64,64,64,64,64,64,64,64,64,64,64,64,64,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,70,70,70,70,70,70,70,70,70,70,70,70,70,70,70,70,70,70,70,70,70,70,70,70,70,70,70,73,73,73,73,73,73,73,73], -0.1098084 ],
            [ [2, 3, -1, 3, 4, 5, 0, 2], -0.4586591 ],
            [ [1, 2, 3, 4, 5, 6, 8, 8], 0.09883962 ],
            [ [1, 1, 3, 4, 5, 6, 7, 8], -0.09883962 ],
            [ [3, 4, 5, 2, 3, 4, 5, 6, 4, 7], 0.3595431 ],
            [ [1, 1, 2, 2, 2, 2, 3, 3, 3, 4, 4, 5, 6, 7, 8], 0.8633783 ],
            [ [1,2,3,4,5,6,7,8], 0 ],
            [ [1, 2, 3, 4, 5, 9, 23, 32, 69], 1.946591 ],
            [ [3, 4, 5, 2, 3, 4, 5, 6, 4, 7 ], 0.3595431 ],
            [ [1, 1, 2, 2, 2, 2, 3, 3, 3, 4, 4, 5, 6, 7, 8 ], 0.8633783 ],
            [ [2, 3, -1, 3, 4, 5, 0, 2], -0.4586591 ],
            [ [-2.83, -0.95, -0.88, 1.21, -1.67, 0.83, -0.27, 1.36, -0.34, 0.48, -2.83, -0.95, -0.88, 1.21, -1.67], -0.1740425 ],
            [ [13, 18, 13, 14, 13, 16, 14, 21, 13], 1.491553],
        ];
    }

    /**
     * @test   sampleSkewness errpr when array is empty
     * @throws \Exception
     */
    public function testSampleSkewnessNullWhenEmptyArray()
    {
        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        RandomVariable::sampleSkewness(array());
    }

    /**
     * @test   sampleSkewness error when array has fewer than 3 elements
     * @throws \Exception
     */
    public function testSampleSkewnessNullWhenSmallArray()
    {
        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        RandomVariable::sampleSkewness([1, 2]);
    }

    /**
     * @test         populationSkewness Nan
     * @dataProvider dataProviderForSkewnessNan
     * @param        array $X
     * @throws       \Exception
     */
    public function testPopulationSkewnessNan(array $X)
    {
        // When
        $skewness = RandomVariable::populationSkewness($X);

        // Then
        $this->assertNan($skewness);
    }

    /**
     * @test         sampleSkewness Nan
     * @dataProvider dataProviderForSkewnessNan
     * @param        array $X
     * @throws       \Exception
     */
    public function testSampleSkewnessNan(array $X)
    {
        // When
        $skewness = RandomVariable::sampleSkewness($X);

        // Then
        $this->assertNan($skewness);
    }

    /**
     * @test         alternativeSkewness Nan
     * @dataProvider dataProviderForSkewnessNan
     * @param        array $X
     * @throws       \Exception
     */
    public function testAlternativeSkewnessNan(array $X)
    {
        // When
        $skewness = RandomVariable::alternativeSkewness($X);

        // Then
        $this->assertNan($skewness);
    }

    /**
     * Generated with R (e1071) skewness(data, type=2)
     * @return array [X, skewness]
     */
    public function dataProviderForSkewnessNan(): array
    {
        return [
            [[-1, -1, -1]],
            [[10, 10, 10]],
            [[0, 0, 0]],
            [[10, 10, 10, 10]],
        ];
    }

    /**
     * @test         alternativeSkewness
     * @dataProvider dataProviderForAlternativeSkewness
     * @param        array $X
     * @param        float $expected
     * @throws       \Exception
     */
    public function testAlternativeSkewness(array $X, float $expected)
    {
        // When
        $skewness = RandomVariable::alternativeSkewness($X);

        // Then
        $this->assertEqualsWithDelta($expected, $skewness, 0.000001);
    }

    /**
     * Generated with online calculator: http://www.endmemo.com/statistics/skewness.php
     * @return array [X, skewness]
     */
    public function dataProviderForAlternativeSkewness(): array
    {
        return [
            [ [61,61,61,61,61,64,64,64,64,64,64,64,64,64,64,64,64,64,64,64,64,64,64,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,70,70,70,70,70,70,70,70,70,70,70,70,70,70,70,70,70,70,70,70,70,70,70,70,70,70,70,73,73,73,73,73,73,73,73], -0.107612 ],
            [ [1, 2, 3, 4, 5, 9, 23, 32, 69], 1.514015 ],
            [ [5,20,40,80,100], 0.202721 ],
            [ [3, 4, 5, 2, 3, 4, 5, 6, 4, 7], 0.287634 ],
            [ [1, 1, 3, 4, 5, 6, 7, 8], -0.07413 ],
            [ [1,2,3,4,5,6,7,8], 0 ],
            [ [13, 18, 13, 14, 13, 16, 14, 21, 13], 1.160097],
        ];
    }

    /**
     * @test   alternativeSkewness error when array is empty
     * @throws \Exception
     */
    public function testAlternativeSkewnessErrorWhenEmptyArray()
    {
        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        RandomVariable::alternativeSkewness(array());
    }

    /**
     * @test         skewness
     * @dataProvider dataProviderForSkewness
     * @param        array $X
     * @param        string $type
     * @param        float $expected
     * @throws       \Exception
     */
    public function testSkewness(array $X, string $type, float $expected)
    {
        // When
        $skewness = RandomVariable::skewness($X, $type);

        // Then
        $this->assertEqualsWithDelta($expected, $skewness, 0.000001);
    }

    /**
     * @return array [X, algorithm, skewness]
     */
    public function dataProviderForSkewness(): array
    {
        return [
            // Population skewness
            [ [61,61,61,61,61,64,64,64,64,64,64,64,64,64,64,64,64,64,64,64,64,64,64,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,70,70,70,70,70,70,70,70,70,70,70,70,70,70,70,70,70,70,70,70,70,70,70,70,70,70,70,73,73,73,73,73,73,73,73], RandomVariable::POPULATION_SKEWNESS, -0.1081544 ],
            [ [2, 3, -1, 3, 4, 5, 0, 2], RandomVariable::POPULATION_SKEWNESS, -0.3677454 ],
            [ [1, 2, 3, 4, 5, 6, 8, 8], RandomVariable::POPULATION_SKEWNESS, 0.079248 ],
            [ [1, 1, 3, 4, 5, 6, 7, 8], RandomVariable::POPULATION_SKEWNESS, -0.079248 ],
            [ [3, 4, 5, 2, 3, 4, 5, 6, 4, 7], RandomVariable::POPULATION_SKEWNESS, 0.3031933 ],
            [ [1, 1, 2, 2, 2, 2, 3, 3, 3, 4, 4, 5, 6, 7, 8], RandomVariable::POPULATION_SKEWNESS, 0.7745239 ],
            [ [1,2,3,4,5,6,7,8], RandomVariable::POPULATION_SKEWNESS, 0 ],
            [ [1, 2, 3, 4, 5, 9, 23, 32, 69], RandomVariable::POPULATION_SKEWNESS, 1.605855 ],
            [ [3, 4, 5, 2, 3, 4, 5, 6, 4, 7 ], RandomVariable::POPULATION_SKEWNESS, 0.3031933 ],
            [ [1, 1, 2, 2, 2, 2, 3, 3, 3, 4, 4, 5, 6, 7, 8 ], RandomVariable::POPULATION_SKEWNESS, 0.7745239 ],
            [ [2, 3, -1, 3, 4, 5, 0, 2], RandomVariable::POPULATION_SKEWNESS, -0.3677454 ],
            [ [-2.83, -0.95, -0.88, 1.21, -1.67, 0.83, -0.27, 1.36, -0.34, 0.48, -2.83, -0.95, -0.88, 1.21, -1.67], RandomVariable::POPULATION_SKEWNESS, -0.1561309 ],

            // Sample skewness
            [ [61,61,61,61,61,64,64,64,64,64,64,64,64,64,64,64,64,64,64,64,64,64,64,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,70,70,70,70,70,70,70,70,70,70,70,70,70,70,70,70,70,70,70,70,70,70,70,70,70,70,70,73,73,73,73,73,73,73,73], RandomVariable::SAMPLE_SKEWNESS, -0.1098084 ],
            [ [2, 3, -1, 3, 4, 5, 0, 2], RandomVariable::SAMPLE_SKEWNESS, -0.4586591 ],
            [ [1, 2, 3, 4, 5, 6, 8, 8], RandomVariable::SAMPLE_SKEWNESS, 0.09883962 ],
            [ [1, 1, 3, 4, 5, 6, 7, 8], RandomVariable::SAMPLE_SKEWNESS, -0.09883962 ],
            [ [3, 4, 5, 2, 3, 4, 5, 6, 4, 7], RandomVariable::SAMPLE_SKEWNESS, 0.3595431 ],
            [ [1, 1, 2, 2, 2, 2, 3, 3, 3, 4, 4, 5, 6, 7, 8], RandomVariable::SAMPLE_SKEWNESS, 0.8633783 ],
            [ [1,2,3,4,5,6,7,8], RandomVariable::SAMPLE_SKEWNESS, 0 ],
            [ [1, 2, 3, 4, 5, 9, 23, 32, 69], RandomVariable::SAMPLE_SKEWNESS, 1.946591 ],
            [ [3, 4, 5, 2, 3, 4, 5, 6, 4, 7 ], RandomVariable::SAMPLE_SKEWNESS, 0.3595431 ],
            [ [1, 1, 2, 2, 2, 2, 3, 3, 3, 4, 4, 5, 6, 7, 8 ], RandomVariable::SAMPLE_SKEWNESS, 0.8633783 ],
            [ [2, 3, -1, 3, 4, 5, 0, 2], RandomVariable::SAMPLE_SKEWNESS, -0.4586591 ],
            [ [-2.83, -0.95, -0.88, 1.21, -1.67, 0.83, -0.27, 1.36, -0.34, 0.48, -2.83, -0.95, -0.88, 1.21, -1.67], RandomVariable::SAMPLE_SKEWNESS, -0.1740425 ],

            // Alternative skewness
            [ [61,61,61,61,61,64,64,64,64,64,64,64,64,64,64,64,64,64,64,64,64,64,64,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,67,70,70,70,70,70,70,70,70,70,70,70,70,70,70,70,70,70,70,70,70,70,70,70,70,70,70,70,73,73,73,73,73,73,73,73], RandomVariable::ALTERNATIVE_SKEWNESS, -0.107612 ],
            [ [1, 2, 3, 4, 5, 9, 23, 32, 69], RandomVariable::ALTERNATIVE_SKEWNESS, 1.514015 ],
            [ [5,20,40,80,100], RandomVariable::ALTERNATIVE_SKEWNESS, 0.202721 ],
            [ [3, 4, 5, 2, 3, 4, 5, 6, 4, 7], RandomVariable::ALTERNATIVE_SKEWNESS, 0.287634 ],
            [ [1, 1, 3, 4, 5, 6, 7, 8], RandomVariable::ALTERNATIVE_SKEWNESS, -0.07413 ],
            [ [1,2,3,4,5,6,7,8], RandomVariable::ALTERNATIVE_SKEWNESS, 0 ],
            [ [13, 18, 13, 14, 13, 16, 14, 21, 13], RandomVariable::ALTERNATIVE_SKEWNESS, 1.160097],
        ];
    }

    /**
     * @test         skewness default type is sample skewness
     * @dataProvider dataProviderForSampleSkewness
     * @param        array $X
     * @param        float $expected
     * @throws       \Exception
     */
    public function testSkewnessDefaultTypeIsSampleSkewness(array $X, float $expected)
    {
        // When
        $skewness = RandomVariable::skewness($X);

        // Then
        $this->assertEqualsWithDelta($expected, $skewness, 0.000001);
    }

    /**
     * @test   skewness error when array is empty
     * @throws \Exception
     */
    public function testSkewnessErrorWhenEmptyArray()
    {
        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        RandomVariable::skewness(array());
    }

    /**
     * @test   skewness error - unknown type
     * @throws \Exception
     */
    public function testSkewnessTypeError()
    {
        // Given
        $X    = [1, 2, 3, 2, 3, 1, 1, 2];
        $type = 'unknownType';

        // Then
        $this->expectException(Exception\IncorrectTypeException::class);

        // When
        RandomVariable::skewness($X, $type);
    }

    /**
     * @test         ses
     * @dataProvider dataProviderForSes
     * @param        int   $n
     * @param        float $expected
     * @throws       \Exception
     */
    public function testSes(int $n, float $expected)
    {
        // When
        $ses = RandomVariable::ses($n);

        // Then
        $this->assertEqualsWithDelta($expected, $ses, 0.001);
    }

    /**
     * @return array [n, ses]
     */
    public function dataProviderForSes(): array
    {
        return [
            [5, 0.913],
            [10, 0.687],
            [20, 0.512],
            [100, 0.241],
            [1000, 0.077],
            [10000, 0.024],
        ];
    }

    /**
     * @test         ses throws a BadDataException if n is < 3
     * @dataProvider dataProviderForSesException
     * @param        int $n
     * @throws       \Exception
     */
    public function testSesException(int $n)
    {
        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        RandomVariable::ses($n);
    }

    /**
     * @return array [n]
     */
    public function dataProviderForSesException(): array
    {
        return [
            [-1],
            [0],
            [1],
            [2],
        ];
    }

    /**
     * @test         sampleKurtosis
     * @dataProvider dataProviderForSampleKurtosis
     * @param        array $X
     * @param        float $expected
     * @throws       \Exception
     */
    public function testSampleKurtosis(array $X, float $expected)
    {
        // When
        $kurtosis = RandomVariable::sampleKurtosis($X);

        // Then
        $this->assertEqualsWithDelta($expected, $kurtosis, 0.000001);
    }

    /**
     * Generated with R (e1071) kurtosis(data, type=1)
     * @return array [X, kurtosis]
     */
    public function dataProviderForSampleKurtosis(): array
    {
        return [
            [ [ 1987, 1987, 1991, 1992, 1992, 1992, 1992, 1993, 1994, 1994, 1995 ], -0.2320107 ],
            [ [ 0, 7, 7, 6, 6, 6, 5, 5, 4, 1 ], -0.273157 ],
            [ [ 2, 2, 4, 6, 8, 10, 10 ], -1.574074 ],
            [ [ 1242, 1353, 1142 ], -1.5 ],
            [ [1, 2, 3, 4, 5, 9, 23, 32, 69], 1.389416 ],
            [ [5,20,40,80,100], -1.525992 ],
            [ [4, 5, 5, 5, 5, 6], 0 ],
        ];
    }

    /**
     * @test   sampleKurtosis error when array is empty
     * @throws \Exception
     */
    public function testSampleKurtosisErrorWhenEmptyArray()
    {
        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        RandomVariable::sampleKurtosis(array());
    }

    /**
     * @test         sampleKurtosis Nan
     * @dataProvider dataProviderForSkewnessNan
     * @param        array $X
     * @throws       \Exception
     */
    public function testSampleKurtosisNan(array $X)
    {
        // When
        $kurtosis = RandomVariable::sampleKurtosis($X);

        // Then
        $this->assertNan($kurtosis);
    }

    /**
     * @test         populationKurtosis
     * @dataProvider dataProviderForPopulationKurtosis
     * @param        array $X
     * @param        float $expected
     * @throws       \Exception
     */
    public function testPopulationKurtosis(array $X, float $expected)
    {
        // When
        $kurtosis = RandomVariable::populationKurtosis($X);

        // Then
        $this->assertEqualsWithDelta($expected, $kurtosis, 0.000001);
    }

    /**
     * Generated with R (e1071) kurtosis(data, type=2)
     * @return array [X, kurtosis]
     */
    public function dataProviderForPopulationKurtosis(): array
    {
        return [
            [ [ 1987, 1987, 1991, 1992, 1992, 1992, 1992, 1993, 1994, 1994, 1995 ],0.4466489 ],
            [ [ 0, 7, 7, 6, 6, 6, 5, 5, 4, 1 ], 0.4813832 ],
            [ [ 2, 2, 4, 6, 8, 10, 10 ], -1.977778 ],
            [ [ 1242, 1353, 1142, 1222 ], 1.397048 ],
            [ [1, 2, 3, 4, 5, 9, 23, 32, 69], 3.789364 ],
            [ [5,20,40,80,100], -2.103968 ],
            [ [4, 5, 5, 5, 5, 6], 2.5 ],
        ];
    }

    /**
     * @test         populationKurtosis Nan
     * @dataProvider dataProviderForPopulationKurtosisNan
     * @param        array $X
     * @throws       \Exception
     */
    public function testPopulationKurtosisNan(array $X)
    {
        // When
        $kurtosis = RandomVariable::populationKurtosis($X);

        // Then
        $this->assertNan($kurtosis);
    }

    /**
     * Generated with R (e1071) kurtosis(data, type=2)
     * @return array [X, skewness]
     */
    public function dataProviderForPopulationKurtosisNan(): array
    {
        return [
            [[-1, -1, -1, -1]],
            [[1, 1, 1, 1]],
            [[0, 0, 0, 0]],
            [[10, 10, 10, 10]],
        ];
    }

    /**
     * @test         populationKurtosis error when fewer than four numbers
     * @dataProvider dataProviderForPopulationKurtosisErrorFewerThanFourNumbers
     * @param        array $X
     * @throws       \Exception
     */
    public function testPopulationKurtosisErrorFewerThanFourNumbers(array $X)
    {
        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        $kurtosis = RandomVariable::populationKurtosis($X);
    }

    /**
     * Generated with R (e1071) kurtosis(data, type=2)
     * @return array [X, skewness]
     */
    public function dataProviderForPopulationKurtosisErrorFewerThanFourNumbers(): array
    {
        return [
            [[1]],
            [[2, 2]],
            [[3, 3, 3]],
            [[0, 0, 0]],
            [[10, 10, 10]],
        ];
    }

    /**
     * @test         kurtosis
     * @dataProvider dataProviderForKurtosis
     * @param        array $X
     * @param        string $type
     * @param        float $expected
     * @throws       \Exception
     */
    public function testKurtosis(array $X, string $type, float $expected)
    {
        // When
        $kurtosis = RandomVariable::kurtosis($X, $type);

        // Then
        $this->assertEqualsWithDelta($expected, $kurtosis, 0.000001);
    }

    /**
     * @return array [X, algorithm, kurtosis]
     */
    public function dataProviderForKurtosis(): array
    {
        return [
            // Population kurtosis
            [ [ 1987, 1987, 1991, 1992, 1992, 1992, 1992, 1993, 1994, 1994, 1995 ], RandomVariable::POPULATION_KURTOSIS, 0.4466489 ],
            [ [ 0, 7, 7, 6, 6, 6, 5, 5, 4, 1 ], RandomVariable::POPULATION_KURTOSIS, 0.4813832 ],
            [ [ 2, 2, 4, 6, 8, 10, 10 ], RandomVariable::POPULATION_KURTOSIS, -1.977778 ],
            [ [ 1242, 1353, 1142, 1222 ], RandomVariable::POPULATION_KURTOSIS, 1.397048 ],
            [ [1, 2, 3, 4, 5, 9, 23, 32, 69], RandomVariable::POPULATION_KURTOSIS, 3.789364 ],
            [ [5,20,40,80,100], RandomVariable::POPULATION_KURTOSIS, -2.103968 ],
            [ [4, 5, 5, 5, 5, 6], RandomVariable::POPULATION_KURTOSIS, 2.5 ],

            // Sample kurtosis
            [ [ 1987, 1987, 1991, 1992, 1992, 1992, 1992, 1993, 1994, 1994, 1995 ], RandomVariable::SAMPLE_KURTOSIS, -0.2320107 ],
            [ [ 0, 7, 7, 6, 6, 6, 5, 5, 4, 1 ], RandomVariable::SAMPLE_KURTOSIS, -0.273157 ],
            [ [ 2, 2, 4, 6, 8, 10, 10 ], RandomVariable::SAMPLE_KURTOSIS, -1.574074 ],
            [ [ 1242, 1353, 1142 ], RandomVariable::SAMPLE_KURTOSIS, -1.5 ],
            [ [1, 2, 3, 4, 5, 9, 23, 32, 69], RandomVariable::SAMPLE_KURTOSIS, 1.389416 ],
            [ [5,20,40,80,100], RandomVariable::SAMPLE_KURTOSIS, -1.525992 ],
            [ [4, 5, 5, 5, 5, 6], RandomVariable::SAMPLE_KURTOSIS, 0 ],
        ];
    }

    /**
     * @test         kurtosis default type is poluation skewness
     * @dataProvider dataProviderForPopulationKurtosis
     * @param        array $X
     * @param        float $expected
     * @throws       \Exception
     */
    public function testSkewnessDefaultTypeIsPopulationKurtosis(array $X, float $expected)
    {
        // When
        $kurtosis = RandomVariable::kurtosis($X);

        // Then
        $this->assertEqualsWithDelta($expected, $kurtosis, 0.000001);
    }

    /**
     * @test   kurtosis error when array is empty
     * @throws \Exception
     */
    public function testKurtosisErrorWhenEmptyArray()
    {
        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        RandomVariable::kurtosis(array());
    }

    /**
     * @test   kurtosis error - unknown type
     * @throws \Exception
     */
    public function testKurtosisTypeError()
    {
        // Given
        $X    = [1, 2, 3, 2, 3, 1, 1, 2];
        $type = 'unknownType';

        // Then
        $this->expectException(Exception\IncorrectTypeException::class);

        // When
        RandomVariable::kurtosis($X, $type);
    }

    /**
     * @test         isPlatykurtic
     * @dataProvider dataProviderForPlatykurtic
     * @param        array $data
     * @throws       \Exception
     */
    public function testIsPlatykurtic(array $data)
    {
        $this->assertTrue(RandomVariable::isPlatykurtic($data, RandomVariable::SAMPLE_KURTOSIS));
    }

    /**
     * @test         isLeptokurtic
     * @dataProvider dataProviderForLeptokurtic
     * @param        array $data
     * @throws       \Exception
     */
    public function testIsLeptokurtic(array $data)
    {
        $this->assertTrue(RandomVariable::isLeptokurtic($data, RandomVariable::SAMPLE_KURTOSIS));
    }

    /**
     * @test         isMesokurtic
     * @dataProvider dataProviderForMesokurtic
     * @param        array $data
     * @throws       \Exception
     */
    public function testIsMesokurtic(array $data)
    {
        $this->assertTrue(RandomVariable::isMesokurtic($data, RandomVariable::SAMPLE_KURTOSIS));
    }

    /**
     * @test         isPlatykurtic returns false for a leptokurtic data set
     * @dataProvider dataProviderForLeptokurtic
     * @param        array $data
     * @throws       \Exception
     */
    public function testIsNotPlatykurtic(array $data)
    {
        $this->assertFalse(RandomVariable::isPlatykurtic($data));
    }

    /**
     * @test         isLeptokurtic returns false for a platykurtic data set
     * @dataProvider dataProviderForPlatykurtic
     * @param        array $data
     * @throws       \Exception
     */
    public function testIsNotLeptokurtic(array $data)
    {
        $this->assertFalse(RandomVariable::isLeptokurtic($data));
    }

    /**
     * @return array [data]
     */
    public function dataProviderForPlatykurtic(): array
    {
        return [
            [[2, 2, 4, 6, 8, 10, 10]],
        ];
    }

    /**
     * @return array [data]
     */
    public function dataProviderForLeptokurtic(): array
    {
        return [
            [[1, 2, 3, 4, 5, 9, 23, 32, 69]],
        ];
    }

    /**
     * @return array [data]
     */
    public function dataProviderForMesokurtic(): array
    {
        return [
            [[4, 5, 5, 5, 5, 6]],
        ];
    }

    /**
     * @test         sek
     * @dataProvider dataProviderForSek
     * @param        int   $n
     * @param        float $expected
     * @throws       \Exception
     */
    public function testSek(int $n, float $expected)
    {
        // When
        $sek = RandomVariable::sek($n);

        // Then
        $this->assertEqualsWithDelta($expected, $sek, 0.001);
    }

    /**
     * @return array [n, sek]
     */
    public function dataProviderForSek(): array
    {
        return [
            [5, 2],
            [10, 1.334],
            [20, 0.992],
            [100, 0.478],
            [1000, 0.154],
            [10000, 0.048],
        ];
    }

    /**
     * @test         sek throws a BadDataException if n is < 4
     * @dataProvider dataProviderForSekException
     * @param        int $n
     * @throws       \Exception
     */
    public function testSekException(int $n)
    {
        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        RandomVariable::sek($n);
    }

    /**
     * @return array [n]
     */
    public function dataProviderForSekException(): array
    {
        return [
            [-1],
            [0],
            [1],
            [2],
            [3],
        ];
    }

    /**
     * @test         standardErrorOfTheMean
     * @dataProvider dataProviderForStandardErrorOfTheMean
     * @param        array $X
     * @param        float $expected
     * @throws       \Exception
     */
    public function testStandardErrorOfTheMean(array $X, float $expected)
    {
        // When
        $sem = RandomVariable::standardErrorOfTheMean($X);

        // Then
        $this->assertEqualsWithDelta($expected, $sem, 0.0001);
    }

    /**
     * @test         standardErrorOfTheMean
     * @dataProvider dataProviderForStandardErrorOfTheMean
     * @param        array $X
     * @param        float $expected
     * @throws       \Exception
     */
    public function testSem(array $X, float $expected)
    {
        // When
        $sem = RandomVariable::sem($X);

        // Then
        $this->assertEqualsWithDelta($expected, $sem, 0.0001);
    }

    /**
     * @return array [X, sem]
     */
    public function dataProviderForStandardErrorOfTheMean(): array
    {
        return [
            [ [1,2,3,4,5,5,6,7], 0.7180703308172536 ],
            [ [34,6,23,12,25,64,32,75], 8.509317372319423 ],
            [ [1.5,1.3,2.532,0.43,0.042,5.9,0.9942,1.549], 0.645903079859 ],
            [ [453543,235235,656,342,2235,6436,234,9239,3535,8392,3492,5933,244], 37584.225394 ],
        ];
    }

    /**
     * @test   standardErrorOfTheMean error when array is empty
     * @throws \Exception
     */
    public function testStandardErrorOfTheMeanNullWhenEmptyArray()
    {
        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        RandomVariable::standardErrorOfTheMean(array());
    }

    /**
     * @test         confidenceInterval
     * @dataProvider dataProviderForConfidenceInterval
     * @param        number $μ
     * @param        number $n
     * @param        float  $σ
     * @param        float  $cl
     * @param        array  $expected
     * @throws       \Exception
     */
    public function testConfidenceInterval($μ, $n, float $σ, float $cl, array $expected)
    {
        // When
        $ci = RandomVariable::confidenceInterval($μ, $n, $σ, $cl);

        // Then
        $this->assertEqualsWithDelta($expected, $ci, 0.1);
    }

    /**
     * @return array [μ, n, σ, cl, ci]
     */
    public function dataProviderForConfidenceInterval(): array
    {
        return [
            [90, 9, 36, 80, ['ci' => 15.38, 'lower_bound' => 74.62, 'upper_bound' => 105.38]],
            [90, 9, 36, 85, ['ci' => 17.27, 'lower_bound' => 72.73, 'upper_bound' => 107.27]],
            [90, 9, 36, 90, ['ci' => 19.74, 'lower_bound' => 70.26, 'upper_bound' => 109.74]],
            [90, 9, 36, 95, ['ci' => 23.52, 'lower_bound' => 66.48, 'upper_bound' => 113.52]],
            [90, 9, 36, 99, ['ci' => 30.91, 'lower_bound' => 59.09, 'upper_bound' => 120.91]],
            [90, 9, 36, 99.5, ['ci' => 33.68, 'lower_bound' => 56.32, 'upper_bound' => 123.68]],
            [90, 9, 36, 99.9, ['ci' => 39.49, 'lower_bound' => 50.51, 'upper_bound' => 129.49]],
            [90, 0, 36, 99.9, ['ci' => null, 'lower_bound' => null, 'upper_bound' => null]],
        ];
    }

    /**
     * @test         sumOfSquares
     * @dataProvider dataProviderForSumOfSquares
     * @param        array  $numbers
     * @param        number $expected
     * @throws       \Exception
     */
    public function testSumOfSquares(array $numbers, $expected)
    {
        // When
        $sos = RandomVariable::sumOfSquares($numbers);

        // Then
        $this->assertEqualsWithDelta($expected, $sos, 0.001);
    }

    /**
     * @return array
     */
    public function dataProviderForSumOfSquares(): array
    {
        return [
            [ [3, 6, 7, 11, 12, 13, 17], 817],
            [ [6, 11, 12, 14, 15, 20, 21], 1563],
            [ [1, 2, 3, 6, 7, 11, 12], 364],
            [ [1, 2, 3, 4, 5, 6, 7, 8, 9, 0], 285],
            [ [34, 253, 754, 2342, 75, 23, 876, 4, 1, -34, -345, 754, -377, 3, 0], 7723027],
        ];
    }

    /**
     * @test   sumOfSquares error when the array is empty
     * @throws \Exception
     */
    public function testSumOfSquaresNullWhenEmptyArray()
    {
        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        RandomVariable::sumOfSquares(array());
    }

    /**
     * @test         sumOfSquaresDeviations
     * @dataProvider dataProviderForSumOfSquaresDeviations
     * @param        array $numbers
     * @param        float $expected
     * @throws       \Exception
     */
    public function testSumOfSquaresDeviations(array $numbers, float $expected)
    {
        // When
        $sosDeviations = RandomVariable::sumOfSquaresDeviations($numbers);

        // Then
        $this->assertEqualsWithDelta($expected, $sosDeviations, 0.001);
    }

    /**
     * @return array [numbers, sos]
     */
    public function dataProviderForSumOfSquaresDeviations(): array
    {
        return [
            [ [3, 6, 7, 11, 12, 13, 17], 136.8571],
            [ [6, 11, 12, 14, 15, 20, 21], 162.8571],
            [ [1, 2, 3, 6, 7, 11, 12], 112],
            [ [1, 2, 3, 4, 5, 6, 7, 8, 9, 0], 82.5],
            [ [34, 253, 754, 2342, 75, 23, 876, 4, 1, -34, -345, 754, -377, 3, 0], 6453975.7333],
        ];
    }

    /**
     * @test   sumOfSquaresDeviations is null when the array is empty
     * @throws \Exception
     */
    public function testSumOfSquaresDeviationsNullWhenEmptyArray()
    {
        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        RandomVariable::sumOfSquaresDeviations(array());
    }
}
