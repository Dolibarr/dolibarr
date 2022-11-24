<?php

namespace MathPHP\Tests\Functions;

use MathPHP\Exception;
use MathPHP\Functions\BaseEncoderDecoder;
use MathPHP\Number\ArbitraryInteger;

class BaseEncoderDecoderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test         toBase
     * @dataProvider dataProviderForTestToBase
     * @param        string $int
     * @param        int    $base
     * @param        string $expected
     * @throws       \Exception
     */
    public function testToBase(string $int, int $base, string $expected)
    {
        // Given
        $int = new ArbitraryInteger($int);

        // When
        $toBase = BaseEncoderDecoder::toBase($int, $base);

        // Theb
        $this->assertEquals($expected, $toBase);
    }

    public function dataProviderForTestToBase(): array
    {
        return [
           ['0xf', 16, 'f'],
           ['100', 256, \chr(100)],
        ];
    }

    /**
     * @test         creation with string representation
     * @dataProvider dataProviderForTestCreateArbitrary
     */
    public function testCreateArbitrary(string $int, int $base, string $expected)
    {
        // Given
        $int = BaseEncoderDecoder::createArbitraryInteger($int, $base);

        // When
        $stringRepresentation = (string) $int;

        // Then
        $this->assertEquals($expected, $stringRepresentation);
    }

    public function dataProviderForTestCreateArbitrary(): array
    {
        return [
            ['123', 10, '123'],
            ['7b', 16, '123'],
            [\chr(123), 256, '123'],
        ];
    }

    /**
     * @test     toBase throws an exception when base>256
     * @throws   \Exception
     */
    public function testInvalidToBaseException()
    {
        // Given
        $base = 300;
        $int  = new ArbitraryInteger('123456');

        // Then
        $this->expectException(Exception\BadParameterException::class);

        // When
        $string = BaseEncoderDecoder::toBase($int, $base);
    }

    /**
     * @test   Function throws an exception when given an empty string
     * @throws \Exception
     */
    public function testEmptyStringException()
    {
        // Given
        $number = "";

        // Then
        $this->expectException(Exception\BadParameterException::class);

        // When
        $int = BaseEncoderDecoder::createArbitraryInteger($number, 10);
    }

    /**
     * @test   Function throws an exception when base>256
     * @throws \Exception
     */
    public function testInvalidBaseException()
    {
        // Given
        $base = 300;

        // Then
        $this->expectException(Exception\BadParameterException::class);

        // When
        $int = BaseEncoderDecoder::createArbitraryInteger('123456', $base);
    }

    /**
     * @test         Function throws an exception when base>256
     * @dataProvider dataProviderForTestInvalidCharInStringException
     * @param        string $value
     * @param        int    $base
     * @throws       \Exception
     */
    public function testInvalidCharInStringException(string $value, int $base)
    {
        // Then
        $this->expectException(Exception\BadParameterException::class);

        // When
        $int = BaseEncoderDecoder::createArbitraryInteger($value, $base);
    }

    public function dataProviderForTestInvalidCharInStringException(): array
    {
        return [
            ['12a', 10],
            ['0x12afg', 16],
        ];
    }
}
