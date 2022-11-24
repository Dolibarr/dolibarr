<?php

namespace MathPHP\Tests\Number;

use MathPHP\Number\Rational;
use MathPHP\Exception;

class RationalTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test createZeroValue
     */
    public function testCreateZeroValue()
    {
        // Given
        $zero = Rational::createZeroValue();

        // Then
        $this->assertEquals(0, $zero->getWholePart());
        $this->assertEquals(0, $zero->getNumerator());
        $this->assertEquals(1, $zero->getDenominator());
    }

    /**
     * @test         getWholePart
     * @dataProvider dataProviderForData
     * @param        int $w
     * @param        int $n
     * @param        int $d
     * @param        int $expectedWholePart
     */
    public function testGetWholePart($w, $n, $d, $expectedWholePart)
    {
        // Given
        $number = new Rational($w, $n, $d);

        // When
        $wholePart = $number->getWholePart();

        // Then
        $this->assertSame($expectedWholePart, $wholePart);
    }

    /**
     * @test         getNumerator
     * @dataProvider dataProviderForData
     * @param        int $w
     * @param        int $n
     * @param        int $d
     * @param        int $_
     * @param        int $expectedNumerator
     */
    public function testGetNumerator($w, $n, $d, $_, $expectedNumerator)
    {
        // Given
        $number = new Rational($w, $n, $d);

        // When
        $numerator = $number->getNumerator();

        // Then
        $this->assertSame($expectedNumerator, $numerator);
    }

    /**
     * @test         getDenominator
     * @dataProvider dataProviderForData
     * @param        int $w
     * @param        int $n
     * @param        int $d
     * @param        int $_
     * @param        int $__
     * @param        int $expectedDenominator
     */
    public function testGetDenominator($w, $n, $d, $_, $__, $expectedDenominator)
    {
        // Given
        $number = new Rational($w, $n, $d);

        // When
        $denominator = $number->getDenominator();

        // Then
        $this->assertSame($expectedDenominator, $denominator);
    }

    /**
     * @return array
     */
    public function dataProviderForData(): array
    {
        return [
            [0, 0, 1, 0, 0, 1],
            [1, 0, 1, 1, 0, 1],
            [-1, 0, 1, -1, 0, 1],
            [0, 1, 1, 1, 0, 1],
            [0, -1, 1, -1, 0, 1],
            [0, 1, -1, -1, 0, 1],
            [-5, -1, 2, -5, -1, 2],
            [-5, 1, 2, -4, -1, 2],
            [0, 1, 2, 0, 1, 2],
            [0, -1, 2, 0, -1, 2],
            [0, 2, 3, 0, 2, 3],
            [0, 3, 4, 0, 3, 4],
            [0, 4, 5, 0, 4, 5],
            [0, 5, 6, 0, 5, 6],
            [0, 6, 7, 0, 6, 7],
            [0, 7, 8, 0, 7, 8],
            [0, 8, 9, 0, 8, 9],
            [0, 9, 10, 0, 9, 10],
            [0, 10, 21, 0, 10, 21],
            [0, 3, 2, 1, 1, 2],
            [0, 4, 2, 2, 0, 1],
            [0, 5, 2, 2, 1, 2],
            [0, 10, 2, 5, 0, 1],
            [0, 4, 3, 1, 1, 3],
        ];
    }

    /**
     * @test         __toString returns the proper string representation of a rational number
     * @dataProvider dataProviderForToString
     * @param        number $w
     * @param        number $n
     * @param        number $d
     * @param        string $string
     */
    public function testToString($w, $n, $d, string $string)
    {
        // Given
        $number = new Rational($w, $n, $d);

        // When
        $stringRepresentation = (string) $number;

        // Then
        $this->assertSame($string, $stringRepresentation);
    }

    /**
     * @return array
     */
    public function dataProviderForToString(): array
    {
        return [
            [0, 0, 1, '0'],
            [1, 0, 1, '1'],
            [-1, 0, 1, '-1'],
            [0, 1, 1, '1'],
            [0, -1, 1, '-1'],
            [0, 1, -1, '-1'],
            [-5, -1, 2, '-5 ¹/₂'],
            [-5, 1, 2, '-4 ¹/₂'],
            [0, 1, 2, '¹/₂'],
            [0, -1, 2, '-¹/₂'],
            [0, 2, 3, '²/₃'],
            [0, 3, 4, '³/₄'],
            [0, 4, 5, '⁴/₅'],
            [0, 5, 6, '⁵/₆'],
            [0, 6, 7, '⁶/₇'],
            [0, 7, 8, '⁷/₈'],
            [0, 8, 9, '⁸/₉'],
            [0, 9, 10, '⁹/₁₀'],
            [0, 10, 21, '¹⁰/₂₁'],
            [0, 3, 2, '1 ¹/₂'],
            [0, 4, 2, '2'],
            [0, 5, 2, '2 ¹/₂'],
            [0, 10, 2, '5'],
            [0, 4, 3, '1 ¹/₃'],
        ];
    }

    /**
     * @test         toFloat returns the correct floating point number
     * @dataProvider dataProviderForToFloat
     * @param        number $w
     * @param        number $n
     * @param        number $d
     * @param        float $float
     */
    public function testToFloat($w, $n, $d, float $float)
    {
        // Given
        $number = new Rational($w, $n, $d);

        // When
        $computedFloat = $number->toFloat();

        // Then
        $this->assertEquals($float, $computedFloat);
    }

    /**
     * @return array
     */
    public function dataProviderForToFloat(): array
    {
        return [
            [0, 0, 1, 0],
            [1, 0, 1, 1],
            [-1, 0, 1, -1],
            [0, 1, 1, 1],
            [0, -1, 1, -1],
            [0, 1, -1, -1],
            [-5, -1, 2, -5.5],
            [-5, 1, 2, -4.5],
            [0, 1, 2, .5],
            [0, -1, 2, -.5],
        ];
    }

    /**
     * @test normalization throws an Exception\BadDataException if the denominator is zero
     */
    public function testNormalizeException()
    {
        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        $number = new Rational(1, 1, 0);
    }

    /**
     * @test         abs returns the correct number
     * @dataProvider dataProviderForAbs
     * @param        number $w
     * @param        number $n
     * @param        number $d
     * @param        array $result
     */
    public function testAbs($w, $n, $d, array $result)
    {
        // Given
        $number = new Rational($w, $n, $d);

        // When
        $result_rn = new Rational(...$result);

        // Then
        $this->assertTrue($number->abs()->equals($result_rn));
    }

    /**
     * @return array
     */
    public function dataProviderForAbs(): array
    {
        return [
            [0, 0, 1, [0, 0, 1]],
            [1, 0, 1, [1, 0, 1]],
            [-1, 0, 1, [1, 0, 1]],
            [0, 1, 1, [1, 0, 1]],
            [0, -1, 1, [1, 0, 1]],
            [0, 1, -1, [1, 0, 1]],
            [-5, -1, 2, [5, 1, 2]],
            [-5, 1, 2, [4, 1, 2]],
            [0, 1, 2, [0, 1, 2]],
            [0, -1, 2, [0, 1, 2]],
        ];
    }

    /**
     * @test         inverse returns the correct number
     * @dataProvider dataProviderForInverse
     * @param        number $w
     * @param        number $n
     * @param        number $d
     * @param        array $result
     */
    public function testInverse($w, $n, $d, array $result)
    {
        // Given
        $number = new Rational($w, $n, $d);

        // When
        $result_rn = new Rational(...$result);

        // Then
        $this->assertTrue($number->inverse()->equals($result_rn));
    }

    /**
     * @return array
     */
    public function dataProviderForInverse(): array
    {
        return [
            [1, 0, 1, [1, 0, 1]],
            [-1, 0, 1, [-1, 0, 1]],
            [0, 1, 1, [1, 0, 1]],
            [0, -1, 1, [-1, 0, 1]],
            [0, 1, -1, [-1, 0, 1]],
            [-5, -1, 2, [0, -2, 11]],
            [-5, 1, 2, [0, -2, 9]],
            [0, 1, 2, [2, 0, 1]],
            [0, -1, 2, [-2, 0, 1]],
        ];
    }

    /**
     * @test         add returns the correct number
     * @dataProvider dataProviderForAdd
     * @param        array $rn1
     * @param        array $rn2
     * @param        array $expected
     * @throws       \Exception
     */
    public function testAdd(array $rn1, array $rn2, array $expected)
    {
        // Given
        $rational_number_1 = new Rational($rn1[0], $rn1[1], $rn1[2]);
        $rational_number_2 = new Rational($rn2[0], $rn2[1], $rn2[2]);
        $expected_rn       = new Rational(...$expected);

        // When
        $addition_result   = $rational_number_1->add($rational_number_2);

        // Then
        $this->assertTrue($addition_result->equals($expected_rn));
        $this->assertSame($expected_rn->__toString(), $addition_result->__toString());
        $this->assertSame($expected_rn->toFloat(), $addition_result->toFloat());
    }

    /**
     * @return array
     */
    public function dataProviderForAdd(): array
    {
        return [
            [[0, 0, 1], [0, 0, 1], [0, 0, 1]],
            [[1, 0, 1], [1, 0, 1], [2, 0, 1]],
            [[1, 0, 1], [-1, 0, 1], [0, 0, 1]],
            [[-5, -1, 2], [0, 1, 2], [-5, 0, 1]],
            [[15, 0, 1], [0, 3, 4], [15, 3, 4]],
        ];
    }

    /**
     * @test         subtract returns the correct number
     * @dataProvider dataProviderForSubtract
     * @param        array $rn1
     * @param        array $rn2
     * @param        array $expected
     * @throws       \Exception
     */
    public function testSubtract(array $rn1, array $rn2, array $expected)
    {
        // Given
        $rational_number_1 = new Rational($rn1[0], $rn1[1], $rn1[2]);
        $rational_number_2 = new Rational($rn2[0], $rn2[1], $rn2[2]);
        $expected_rn       = new Rational(...$expected);

        // When
        $subtraction_result = $rational_number_1->subtract($rational_number_2);

        // Then
        $this->assertTrue($subtraction_result->equals($expected_rn));
        $this->assertSame($expected_rn->__toString(), $subtraction_result->__toString());
        $this->assertSame($expected_rn->toFloat(), $subtraction_result->toFloat());
    }

    /**
     * @return array
     */
    public function dataProviderForSubtract(): array
    {
        return [
            [[0, 0, 1], [0, 0, 1], [0, 0, 1]],
            [[1, 0, 1], [1, 0, 1], [0, 0, 1]],
            [[1, 0, 1], [-1, 0, 1], [2, 0, 1]],
            [[-5, -1, 2], [0, 1, 2], [-6, 0, 1]],
            [[15, 0, 1], [0, 3, 4], [14, 1, 4]],
        ];
    }

    /**
     * @test         multiply returns the correct number
     * @dataProvider dataProviderForMultiply
     * @param        array $rn1
     * @param        array $rn2
     * @param        array $expected
     * @throws       \Exception
     */
    public function testMultiply(array $rn1, array $rn2, array $expected)
    {
        // Given
        $rational_number_1 = new Rational($rn1[0], $rn1[1], $rn1[2]);
        $rational_number_2 = new Rational($rn2[0], $rn2[1], $rn2[2]);
        $expected_rn      = new Rational(...$expected);

        // When
        $multiplication_result = $rational_number_1->multiply($rational_number_2);

        // Then
        $this->assertTrue($multiplication_result->equals($expected_rn));
        $this->assertSame($expected_rn->__toString(), $multiplication_result->__toString());
        $this->assertSame($expected_rn->toFloat(), $multiplication_result->toFloat());
    }

    /**
     * @return array
     */
    public function dataProviderForMultiply(): array
    {
        return [
            [[0, 0, 1], [0, 0, 1], [0, 0, 1]],
            [[1, 0, 1], [1, 0, 1], [1, 0, 1]],
            [[1, 0, 1], [-1, 0, 1], [-1, 0, 1]],
            [[-5, -1, 2], [0, 1, 2], [-2, -3, 4]],
            [[15, 0, 1], [0, 3, 4], [11, 1, 4]],
        ];
    }

    /**
     * @test         divide returns the correct number
     * @dataProvider dataProviderForDivide
     * @param        array $rn1
     * @param        array $rn2
     * @param        array $expected
     * @throws       \Exception
     */
    public function testDivide(array $rn1, array $rn2, array $expected)
    {
        // Given
        $rational_number_1 = new Rational($rn1[0], $rn1[1], $rn1[2]);
        $rational_number_2 = new Rational($rn2[0], $rn2[1], $rn2[2]);
        $expected_rn       = new Rational(...$expected);

        // When
        $division_result   = $rational_number_1->divide($rational_number_2);

        // Then
        $this->assertTrue($division_result->equals($expected_rn));
        $this->assertSame($expected_rn->__toString(), $division_result->__toString());
        $this->assertSame($expected_rn->toFloat(), $division_result->toFloat());
    }

    /**
     * @return array
     */
    public function dataProviderForDivide(): array
    {
        return [
            [[1, 0, 1], [1, 0, 1], [1, 0, 1]],
            [[1, 0, 1], [-1, 0, 1], [-1, 0, 1]],
            [[3, 4, 2], [3, 5, 2], [0, 10, 11]],
            [[-5, -1, 2], [0, 1, 2], [-11, 0, 1]],
            [[15, 0, 1], [0, 3, 4], [20, 0, 1]],
        ];
    }

    /**
     * @test         add int returns the correct number
     * @dataProvider dataProviderForAddInt
     * @param        array $rn
     * @param        int   $int
     * @param        array $result
     * @throws       \Exception
     */
    public function testAddInt(array $rn, int $int, array $result)
    {
        // Given
        $rational_number = new Rational(...$rn);
        $result_rn       = new Rational(...$result);

        // When
        $additionResult = $rational_number->add($int);

        // Then
        $this->assertTrue($additionResult->equals($result_rn));
    }

    /**
     * @return array
     */
    public function dataProviderForAddInt(): array
    {
        return [
            [[1, 0, 1], 0, [1, 0, 1]],
            [[1, 0, 1], -1, [0, 0, 1]],
            [[3, 5, 2], 10, [15, 1, 2]],
            [[-5, -1, 2], -4, [-9, -1, 2]],
            [[15, 6, 13], -15, [0, 6, 13]],
        ];
    }

    /**
     * @test         subtract int returns the correct number
     * @dataProvider dataProviderForSubtractInt
     * @param        array $rn
     * @param        int   $int
     * @param        array $result
     * @throws       \Exception
     */
    public function testSubtractInt(array $rn, int $int, array $result)
    {
        // Given
        $rational_number = new Rational(...$rn);
        $result_rn       = new Rational(...$result);

        // When
        $subtractionResult = $rational_number->subtract($int);

        // Then
        $this->assertTrue($subtractionResult->equals($result_rn));
    }

    /**
     * @return array
     */
    public function dataProviderForSubtractInt(): array
    {
        return [
            [[1, 0, 1], 0, [1, 0, 1]],
            [[1, 0, 1], -1, [2, 0, 1]],
            [[3, 5, 2], 10, [-4, -1, 2]],
            [[-5, -1, 2], -4, [-1, -1, 2]],
            [[15, 6, 13], -15, [30, 6, 13]],
        ];
    }

    /**
     * @test         multiply int returns the correct number
     * @dataProvider dataProviderForMultiplyInt
     * @param        array $rn
     * @param        int   $int
     * @param        array $expected
     * @throws       \Exception
     */
    public function testMultiplyInt(array $rn, int $int, array $expected)
    {
        // Given
        $rational_number       = new Rational(...$rn);
        $expected_rn           = new Rational(...$expected);

        // When
        $multiplication_result = $rational_number->multiply($int);

        // Then
        $this->assertTrue($multiplication_result->equals($expected_rn));
    }

    /**
     * @return array
     */
    public function dataProviderForMultiplyInt(): array
    {
        return [
            [[1, 0, 1], 0, [0, 0, 1]],
            [[1, 0, 1], -1, [-1, 0, 1]],
            [[3, 5, 2], 10, [55, 0, 1]],
            [[-5, -1, 2], -4, [22, 0, 1]],
            [[15, 6, 13], 2, [30, 12, 13]],
        ];
    }

    /**
     * @test         divide int returns the correct number
     * @dataProvider dataProviderForDivideInt
     * @param        array $rn
     * @param        int   $int
     * @param        array $result
     * @throws       \Exception
     */
    public function testDivideInt(array $rn, int $int, array $result)
    {
        // Given
        $rational_number = new Rational(...$rn);
        $result_rn       = new Rational(...$result);

        // When
        $divisionResult = $rational_number->divide($int);

        // Then
        $this->assertTrue($divisionResult->equals($result_rn));
    }

    /**
     * @return array
     */
    public function dataProviderForDivideInt(): array
    {
        return [
            [[1, 0, 1], 1, [1, 0, 1]],
            [[1, 0, 1], -1, [-1, 0, 1]],
            [[3, 5, 2], 10, [0, 11, 20]],
            [[-5, -1, 2], -4, [1, 3, 8]],
            [[15, 6, 13], -15, [-1, -2, 65]],
        ];
    }

    /**
     * @test         pow returns the correct number
     * @dataProvider dataProviderForPow
     * @param        array $rn
     * @param        int   $int
     * @param        array $result
     * @throws       \Exception
     */
    public function testPow(array $rn, int $int, array $result)
    {
        // Given
        $rational_number = new Rational(...$rn);
        $result_rn       = new Rational(...$result);

        // When
        $powResult = $rational_number->pow($int);

        // Then
        $this->assertTrue($powResult->equals($result_rn));
    }

    /**
     * @return array
     */
    public function dataProviderForPow(): array
    {
        return [
            [[1, 0, 1], -1, [1, 0, 1]],
            [[1, 0, 1], 0, [1, 0, 1]],
            [[1, 0, 1], 1, [1, 0, 1]],
            [[1, 0, 1], 2, [1, 0, 1]],
            [[0, 0, 1], 0, [1, 0, 1]],
            [[0, 0, 1], 10, [0, 0, 1]],
            [[0, 1, 2], 0, [0, 1, 1]],
            [[1, 0, 1], 0, [1, 0, 1]],
            [[0, 1, 1], 0, [0, 1, 1]],
            [[0, 1, 2], -1, [0, 2, 1]],
            [[0, 1, 2], -2, [0, 4, 1]],
            [[4, 5, 2], -2, [0, 4, 169]],
            [[3, 5, 2], 5, [5032, 27, 32]],
        ];
    }

    /**
     * @test     The inverse of zero throws an exception.
     * @throws   \Exception
     */
    public function testInverseException()
    {
        // Given
        $number = new Rational(0, 0, 1);

        // Then
        $this->expectException(Exception\DivisionByZeroException::class);

        // When
        $number->inverse();
    }

    /**
     * @test     Adding a float throws an exception
     * @throws   \Exception
     */
    public function testAddException()
    {
        $this->expectException(Exception\IncorrectTypeException::class);
        $number = new Rational(1, 0, 1);
        $number->add(1.5);
    }

    /**
     * @test     Subtracting a float throws an exception
     * @throws   \Exception
     */
    public function testSubtractException()
    {
        // Given
        $number = new Rational(1, 0, 1);

        // Then
        $this->expectException(Exception\IncorrectTypeException::class);

        // When
        $number->subtract(1.5);
    }

    /**
     * @test   Multiplying a float throws an exception
     * @throws \Exception
     */
    public function testMultiplyException()
    {
        // Given
        $number = new Rational(1, 0, 1);

        // Then
        $this->expectException(Exception\IncorrectTypeException::class);

        // When
        $number->multiply(1.5);
    }

    /**
     * @test     Dividing a float throws an exception
     * @throws   \Exception
     */
    public function testDivideException()
    {
        // Given
        $number = new Rational(1, 0, 1);

        // Then
        $this->expectException(Exception\IncorrectTypeException::class);

        // When
        $number->divide(1.5);
    }

    /**
     * @test     Raising zero to a negative exponent throws an exception.
     * @throws   \Exception
     */
    public function testPowException()
    {
        // Given
        $number = new Rational(0, 0, 1);

        // Then
        $this->expectException(Exception\DivisionByZeroException::class);

        // When
        $number->pow(-2);
    }
}
