<?php

namespace MathPHP\Tests\NumericalAnalysis\Interpolation;

use MathPHP\NumericalAnalysis\Interpolation\Interpolation;
use MathPHP\Exception;

class InterpolationTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test   getPoints with incorrect type - source is neither a callback nor a set of arrays
     * @throws \Exception
     */
    public function testIncorrectInput()
    {
        // Given
        $x                 = 10;
        $incorrectFunction = $x ** 2 + 2 * $x + 1;

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        Interpolation::getPoints($incorrectFunction, [0,4,5]);
    }

    /**
     * @test   validate array doesn't have precisely two numbers (coordinates)
     * @throws \Exception
     */
    public function testNotCoordinatesException()
    {
        // Given
        $points = [[0,0], [1,2,3], [2,2]];

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        Interpolation::validate($points);
    }

    /**
     * @test   validate - not enough arrays in the input
     * @throws \Exception
     */
    public function testNotEnoughArraysException()
    {
        // Given
        $points = [[0,0]];

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        Interpolation::validate($points);
    }

    /**
     * @test   validate - two arrays share the same first number (x component)
     * @throws \Exception
     */
    public function testNotAFunctionException()
    {
        // Given
        $points = [[0,0], [0,5], [1,1]];

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        Interpolation::validate($points);
    }
}
