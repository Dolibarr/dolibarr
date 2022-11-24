<?php

namespace MathPHP\Tests\NumericalAnalysis\NumericalIntegration;

use MathPHP\NumericalAnalysis\NumericalIntegration\NumericalIntegration;
use MathPHP\Exception;

class NumericalIntegrationTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test   The input $source is neither a callback or a set of arrays
     * @throws Exception\BadDataException
     */
    public function testIncorrectInput()
    {
        // Given
        $x                 = 10;
        $incorrectFunction = $x ** 2 + 2 * $x + 1;

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        NumericalIntegration::getPoints($incorrectFunction, [0,4,5]);
    }

    /**
     * @test   An array doesn't have precisely two numbers (coordinates)
     * @throws Exception\BadDataException
     */
    public function testNotCoordinatesException()
    {
        // Given
        $points = [[0,0], [1,2,3], [2,2]];

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        NumericalIntegration::validate($points);
    }

    /**
     * @test   There are not enough arrays in the input
     * @throws Exception\BadDataException
     */
    public function testNotEnoughArraysException()
    {
        // Given
        $points = [[0,0]];

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        NumericalIntegration::validate($points);
    }

    /**
     * @test   Two arrays share the same first number (x-component)
     * @throws Exception\BadDataException
     */
    public function testNotAFunctionException()
    {
        // Given
        $points = [[0,0], [0,5], [1,1]];

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        NumericalIntegration::validate($points);
    }
}
