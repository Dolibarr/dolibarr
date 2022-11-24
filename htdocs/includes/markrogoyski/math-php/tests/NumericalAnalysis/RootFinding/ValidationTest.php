<?php

namespace MathPHP\Tests\NumericalAnalysis\RootFinding;

use MathPHP\Exception;
use MathPHP\NumericalAnalysis\RootFinding\Validation;

class ValidationTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test         Tolerance is not negative
     * @dataProvider dataProviderForPositiveTolerance
     * @param        float $tolerance
     * @throws       \Exception
     */
    public function testToleranceNotNegative(float $tolerance)
    {
        // Given
        Validation::tolerance($tolerance);

        // Then no exception is thrown
        $this->assertTrue(true);
    }

    /**
     * @return array
     */
    public function dataProviderForPositiveTolerance(): array
    {
        return [
            [0],
            [0.0000001],
            [0.1],
            [0.5],
            [1],
            [3837],
        ];
    }

    /**
     * @test   Tolerance is negative
     * @throws \Exception
     */
    public function testToleranceNegative()
    {
        // Given
        $tolerance = -0.1;

        // Then
        $this->expectException(Exception\OutOfBoundsException::class);

        // When
        Validation::tolerance($tolerance);
    }

    /**
     * @test         Interval has different points
     * @dataProvider dataProviderForValidInterval
     * @param        float $a
     * @param        float$b
     * @throws       Exception\BadDataException
     */
    public function testIntervalNotTheSame(float $a, float $b)
    {
        // When
        Validation::interval($a, $b);

        // Then no exception is thrown
        $this->assertTrue(true);
    }

    /**
     * @return array
     */
    public function dataProviderForValidInterval(): array
    {
        return [
            [1, 2],
            [-2, -1],
            [-2, 1],
            [0, 1],
            [-1, 0],
            [1.2, 1.3],
            [-1.3, -1.2],
        ];
    }

    /**
     * @test   Interval start and end points are the same
     * @throws \Exception
     */
    public function testIntervalSamePoints()
    {
        // Given
        $a = 3.5;
        $b = 3.5;

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        Validation::interval($a, $b);
    }
}
