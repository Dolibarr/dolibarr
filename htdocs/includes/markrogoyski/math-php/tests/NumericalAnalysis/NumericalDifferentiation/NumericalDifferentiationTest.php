<?php

namespace MathPHP\Tests\NumericalAnalysis\NumericalDifferentiation;

use MathPHP\NumericalAnalysis\NumericalDifferentiation\NumericalDifferentiation;
use MathPHP\Exception;

class NumericalDifferentiationTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test   getPoints data is not a callback nor set of arrays
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
        NumericalDifferentiation::getPoints($incorrectFunction, [0,4,5]);
    }

    /**
     * @test   validate an array doesn't have precisely two numbers (coordinates)
     * @throws \Exception
     */
    public function testNotCoordinatesException()
    {
        // Given
        $points = [[0,0], [1,2,3], [2,2]];
        $degree = 3;

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        NumericalDifferentiation::validate($points, $degree);
    }

    /**
     * @test   validate there are not enough arrays in the input
     * @throws \Exception
     */
    public function testNotEnoughArraysException()
    {
        // Given
        $points = [[0,0]];
        $degree = 3;

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        NumericalDifferentiation::validate($points, $degree);
    }

    /**
     * @test   validate two arrays share the same first number (x-component)
     * @throws \Exception
     */
    public function testNotAFunctionException()
    {
        // Given
        $points = [[0,0], [0,5], [1,1]];
        $degree = 3;

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        NumericalDifferentiation::validate($points, $degree);
    }

    /**
     * @test   isSpacingConstant when there is not constant spacing between points
     * @throws \Exception
     */
    public function testSpacingNonConstant()
    {
        // Given
        $sortedPoints = [[0,0], [3,3], [2,2]];

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        NumericalDifferentiation::isSpacingConstant($sortedPoints);
    }

    /**
     * @test   isTargetInPoints target is not the x-component of one of the points
     * @throws \Exception
     */
    public function testTargetNotInPoints()
    {
        // Given
        $target       = 1;
        $sortedPoints = [[0,0], [3,3], [2,2]];

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        NumericalDifferentiation::isTargetInPoints($target, $sortedPoints);
    }
}
