<?php

namespace MathPHP\Tests\Expression;

use MathPHP\Expression\Polynomial;
use MathPHP\Exception;

class PolynomialTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test createZeroValue
     */
    public function testCreateZeroValue()
    {
        // Given
        $zero = Polynomial::createZeroValue();

        // Then
        $this->assertEquals([0], $zero->getCoefficients());
    }

    /**
     * @test         String representation
     * @dataProvider dataProviderForString
     * @param        array $coefficients
     * @param        string $expected
     */
    public function testString(array $coefficients, string $expected)
    {
        // Given
        $polynomial = new Polynomial($coefficients);

        // When
        $string = \strval($polynomial);

        // Then
        $this->assertEquals($expected, $string);
    }

    /**
     * @return array (coefficients, string representation)
     */
    public function dataProviderForString(): array
    {
        return [
            [
                [1, 2, 3],       // p(x) = x² + 2x + 3
                'x² + 2x + 3',
            ],
            [
                [2, 3, 4],       // p(x) = 2x² + 3x + 4
                '2x² + 3x + 4',
            ],
            [
                [-1, -2, -3],       // p(x) = -x² - 2x - 3
                '-x² - 2x - 3',
            ],
            [
                [-2, -3, -4],       // p(x) = -2x² - 3x - 4
                '-2x² - 3x - 4',
            ],
            [
                [0, 2, 3],       // p(x) = 2x + 3
                '2x + 3',
            ],
            [
                [1, 0, 3],       // p(x) = x² + 3
                'x² + 3',
            ],
            [
                [1, 2, 0],       // p(x) = x² + 2x
                'x² + 2x',
            ],
            [
                [0, 0, 3],       // p(x) = 3
                '3',
            ],
            [
                [1, 0, 0],       // p(x) = x²
                'x²',
            ],
            [
                [0, 2, 0],       // p(x) = 2x
                '2x',
            ],
            [
                [0, -2, 3],       // p(x) = -2x + 3
                '-2x + 3',
            ],
            [
                [-1, 0, 3],       // p(x) = -x² + 3
                '-x² + 3',
            ],
            [
                [1, -2, 0],       // p(x) = x² - 2x
                'x² - 2x',
            ],
            [
                [0, 0, -3],       // p(x) = -3
                '-3',
            ],
            [
                [-1, 0, 0],       // p(x) = -x²
                '-x²',
            ],
            [
                [0, -2, 0],       // p(x) = -2x
                '-2x',
            ],
            [
                [0, 0, 0],       // p(x) = 0
                '0',
            ],
            [
                [0, 0, 1],       // p(x) = 1
                '1',
            ],
            [
                [0, 0, 5],       // p(x) = 5
                '5',
            ],
            [
                [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12],       // p(x) = x¹¹ + 2x¹⁰ + 3x⁹ + 4x⁸ + 5x⁷ + 6x⁶ + 7x⁵ + 8x⁴ + 9x³ + 10x² + 11x + 12
                'x¹¹ + 2x¹⁰ + 3x⁹ + 4x⁸ + 5x⁷ + 6x⁶ + 7x⁵ + 8x⁴ + 9x³ + 10x² + 11x + 12',
            ],
        ];
    }

    /**
     * @test         Custom variable for string representation
     * @dataProvider dataProviderForVariable
     * @param        array $args
     * @param        string $expected
     */
    public function testVariable(array $args, string $expected)
    {
        // Given
        $coefficients = $args[0];
        $variable     = $args[1] ?? "x";
        $polynomial   = new Polynomial($coefficients, $variable);

        // When
        $string = \strval($polynomial);

        // Then
        $this->assertEquals($expected, $string);
    }

    /**
     * @return array (coefficients, string representation)
     */
    public function dataProviderForVariable(): array
    {
        return [
            [
                [[1, 2, 3]],       // p(x) = x² + 2x + 3
                'x² + 2x + 3',
            ],
            [
                [[2, 3, 4], "p"],       // p(p) = 2p² + 3p + 4
                '2p² + 3p + 4',
            ],
            [
                [[-1, -2, -3], "q"],       // p(q) = -q² - 2q - 3
                '-q² - 2q - 3',
            ],
            [
                [[-2, -3, -4], "a"],       // p(a) = -2a² - 3a - 4
                '-2a² - 3a - 4',
            ],
            [
                [[0, 2, 3], "a"],       // p(a) = 2a + 3
                '2a + 3',
            ],
            [
                [[1, 0, 3], "a"],       // p(a) = a² + 3
                'a² + 3',
            ],
            [
                [[1, 2, 0], "a"],       // p(a) = a² + 2a
                'a² + 2a',
            ],
            [
                [[0, 0, 3], "a"],       // p(a) = 3
                '3',
            ],
            [
                [[1, 0, 0], "a"],       // p(a) = a²
                'a²',
            ],
            [
                [[0, 2, 0], "a"],       // p(a) = 2a
                '2a',
            ],
            [
                [[0, -2, 3], "a"],       // p(a) = -2a + 3
                '-2a + 3',
            ],
            [
                [[-1, 0, 3], "a"],       // p(a) = -a² + 3
                '-a² + 3',
            ],
            [
                [[1, -2, 0], "a"],       // p(a) = a² - 2a
                'a² - 2a',
            ],
            [
                [[0, 0, -3], "a"],       // p(a) = -3
                '-3',
            ],
            [
                [[-1, 0, 0], "a"],       // p(a) = -a²
                '-a²',
            ],
            [
                [[0, -2, 0], "a"],       // p(a) = -2a
                '-2a',
            ],
            [
                [[0, 0, 0], "a"],       // p(a) = 0
                '0',
            ],
            [
                [[0, 0, 1], "a"],       // p(a) = 1
                '1',
            ],
            [
                [[0, 0, 5], "a"],       // p(a) = 5
                '5',
            ],
            [
                [[1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12], "a"],       // p(a) = a¹¹ + 2a¹⁰ + 3a⁹ + 4a⁸ + 5a⁷ + 6a⁶ + 7a⁵ + 8a⁴ + 9a³ + 10a² + 11a + 12
                'a¹¹ + 2a¹⁰ + 3a⁹ + 4a⁸ + 5a⁷ + 6a⁶ + 7a⁵ + 8a⁴ + 9a³ + 10a² + 11a + 12',
            ],
        ];
    }

    /**
     * @test         Evaluate the polynomial at some x
     * @dataProvider dataProviderForEval
     * @param        array $coefficients
     * @param        $x
     * @param        $expected
     */
    public function testEval(array $coefficients, $x, $expected)
    {
        // Given
        $polynomial = new Polynomial($coefficients);

        // When
        $evaluated = $polynomial($x);

        // Then
        $this->assertEquals($expected, $evaluated);
    }

    /**
     * @return array (coefficients, x, y)
     */
    public function dataProviderForEval(): array
    {
        return [
            [
                [1, 2, 3], // p(x) = x² + 2x + 3
                0, 3       // p(0) = 3
            ],
            [
                [1, 2, 3], // p(x) = x² + 2x + 3
                1, 6       // p(1) = 6
            ],
            [
                [1, 2, 3], // p(x) = x² + 2x + 3
                2, 11      // p(2) = 11
            ],
            [
                [1, 2, 3], // p(x) = x² + 2x + 3
                3, 18      // p(3) = 18
            ],
            [
                [1, 2, 3], // p(x) = x² + 2x + 3
                4, 27      // p(4) = 27
            ],
            [
                [1, 2, 3], // p(x) = x² + 2x + 3
                -1, 2      // p(-1) = 2
            ],
            [
                [0, 0, 0], // p(x) = 0
                5, 0       // p(5) = 0
            ],
        ];
    }

    /**
     * @test         Degree
     * @dataProvider dataProviderForGetDegree
     * @param        array $coefficients
     * @param        int $expected
     */
    public function testGetDegree(array $coefficients, int $expected)
    {
        // Given
        $polynomial = new Polynomial($coefficients);

        // When
        $degree = $polynomial->getDegree();

        // Then
        $this->assertEquals($expected, $degree);
    }

    /**
     * @return array (coefficients, degree)
     */
    public function dataProviderForGetDegree(): array
    {
        return [
            [
                [1, 2, 3],       // p(x) = x² + 2x + 3
                2,
            ],
            [
                [2, 3, 4],       // p(x) = 2x² + 3x + 4
                2
            ],
            [
                [-1, -2, -3],       // p(x) = -x² - 2x - 3
                2
            ],
            [
                [-2, -3, -4],       // p(x) = -2x² - 3x - 4
                2
            ],
            [
                [0, 2, 3],       // p(x) = 2x + 3
                1
            ],
            [
                [1, 0, 3],       // p(x) = x² + 3
                2
            ],
            [
                [1, 2, 0],       // p(x) = x² + 2x
                2
            ],
            [
                [0, 0, 3],       // p(x) = 3
                0
            ],
            [
                [1, 0, 0],       // p(x) = x²
                2
            ],
            [
                [0, 2, 0],       // p(x) = 2x
                1
            ],
            [
                [0, -2, 3],       // p(x) = -2x + 3
                1
            ],
            [
                [-1, 0, 3],       // p(x) = -x² + 3
                2
            ],
            [
                [1, -2, 0],       // p(x) = x² - 2x
                2
            ],
            [
                [0, 0, -3],       // p(x) = -3
                0
            ],
            [
                [-1, 0, 0],       // p(x) = -x²
                2
            ],
            [
                [0, -2, 0],       // p(x) = -2x
                1
            ],
            [
                [0, 0, 0],       // p(x) = 0
                0
            ],
            [
                [0, 0, 1],       // p(x) = 1
                0
            ],
            [
                [0, 0, 5],       // p(x) = 5
                0
            ],
            [
                [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12],       // p(x) = x¹¹ + 2x¹⁰ + 3x⁹ + 4x⁸ + 5x⁷ + 6x⁶ + 7x⁵ + 8x⁴ + 9x³ + 10x² + 11x + 12
                11
            ],
        ];
    }

    /**
     * @test         coefficients
     * @dataProvider dataProviderForGetCoefficients
     * @param        array $coefficients
     * @param        array $expected
     */
    public function testGetCoefficients(array $coefficients, array $expected)
    {
        // Given
        $polynomial   = new Polynomial($coefficients);

        // When
        $coefficients = $polynomial->getCoefficients();

        // Then
        $this->assertEquals($expected, $coefficients);
    }

    /**
     * @return array (coefficients, expected coefficients)
     */
    public function dataProviderForGetCoefficients(): array
    {
        return [
            [
                [1, 2, 3],       // p(x) = x² + 2x + 3
                [1, 2, 3]
            ],
            [
                [2, 3, 4],       // p(x) = 2x² + 3x + 4
                [2, 3, 4]
            ],
            [
                [-1, -2, -3],       // p(x) = -x² - 2x - 3
                [-1, -2, -3]
            ],
            [
                [-2, -3, -4],       // p(x) = -2x² - 3x - 4
                [-2, -3, -4]
            ],
            [
                [0, 2, 3],       // p(x) = 2x + 3
                [2, 3]
            ],
            [
                [1, 0, 3],       // p(x) = x² + 3
                [1, 0, 3]
            ],
            [
                [1, 2, 0],       // p(x) = x² + 2x
                [1, 2, 0]
            ],
            [
                [0, 0, 3],       // p(x) = 3
                [3]
            ],
            [
                [1, 0, 0],       // p(x) = x²
                [1, 0, 0]
            ],
            [
                [0, 2, 0],       // p(x) = 2x
                [2, 0]
            ],
            [
                [0, -2, 3],       // p(x) = -2x + 3
                [-2, 3]
            ],
            [
                [-1, 0, 3],       // p(x) = -x² + 3
                [-1, 0, 3]
            ],
            [
                [1, -2, 0],       // p(x) = x² - 2x
                [1, -2, 0]
            ],
            [
                [0, 0, -3],       // p(x) = -3
                [-3]
            ],
            [
                [-1, 0, 0],       // p(x) = -x²
                [-1, 0, 0]
            ],
            [
                [0, -2, 0],       // p(x) = -2x
                [-2, 0]
            ],
            [
                [0, 0, 0],       // p(x) = 0
                [0]
            ],
            [
                [0, 0, 1],       // p(x) = 1
                [1]
            ],
            [
                [0, 0, 5],       // p(x) = 5
                [5]
            ],
            [
                [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12],       // p(x) = x¹¹ + 2x¹⁰ + 3x⁹ + 4x⁸ + 5x⁷ + 6x⁶ + 7x⁵ + 8x⁴ + 9x³ + 10x² + 11x + 12
                [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12]
            ],
        ];
    }

    /**
     * @test         Get variable
     * @dataProvider dataProviderForGetVariable
     * @param array $args
     * @param string $expected
     */
    public function testGetVariable(array $args, string $expected)
    {
        // Given
        $coefficients = $args[0];
        $variable     = $args[1] ?? "x";
        $polynomial   = new Polynomial($coefficients, $variable);

        // When
        $result = $polynomial->getVariable();

        // Then
        $this->assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function dataProviderForGetVariable(): array
    {
        return [
            [
                [[1, 2, 3]],       // p(x) = x² + 2x + 3
                'x',
            ],
            [
                [[2, 3, 4], "p"],       // p(p) = 2p² + 3p + 4
                'p',
            ],
            [
                [[-1, -2, -3], "m"],       // p(m) = -m² - 2m - 3
                'm',
            ],
            [
                [[-2, -3, -4], "a"],       // p(a) = -2a² - 3a - 4
                'a',
            ],
            [
                [[0, 2, 3], "Δ"],       // p(Δ) = 2Δ + 3
                'Δ',
            ],
            [
                [[1, 0, 3], "Γ"],       // p(Γ) = Γ² + 3
                'Γ',
            ],
            [
                [[1, 2, 0], "Ψ"],       // p(a) = Ψ² + 2Ψ
                'Ψ',
            ],
            [
                [[0, 0, 3], "μ"],       // p(μ) = 3
                'μ',
            ],
            [
                [[1, 0, 0], "ξ"],       // p(ξ) = ξ²
                'ξ',
            ],
            [
                [[0, 2, 0], "aₙ"],       // p(aₙ) = 2aₙ
                'aₙ',
            ],
            [
                [[0, -2, 3], "aⁿ"],       // p(aⁿ) = -2aⁿ + 3
                'aⁿ',
            ],
            [
                [[-1, 0, 3], "a₍ₘ₎₍ₙ₎"],       // p(a) = -a₍ₘ₎₍ₙ₎² + 3
                'a₍ₘ₎₍ₙ₎',
            ],
        ];
    }

    /**
     * @test Set variable
     */
    public function testSetVariable()
    {
        // Given default variable: x
        $polynomial = new Polynomial([1, 1, 1, 1]);
        $expected   = "x";
        $result   = $polynomial->getVariable();
        $this->assertEquals($expected, $result);

        $expected = "x³ + x² + x + 1";
        $result   = \strval($polynomial);
        $this->assertEquals($expected, $result);

        // Given we switch variable to Φ
        $polynomial->setVariable("Φ");
        $expected = "Φ";
        $result   = $polynomial->getVariable();
        $this->assertEquals($expected, $result);

        $expected = "Φ³ + Φ² + Φ + 1";
        $result   = \strval($polynomial);
        $this->assertEquals($expected, $result);

        // Given we switch variable back to x
        $polynomial->setVariable("x");
        $expected = "x";
        $result   = $polynomial->getVariable();
        $this->assertEquals($expected, $result);

        $expected = "x³ + x² + x + 1";
        $result   = \strval($polynomial);
        $this->assertEquals($expected, $result);
    }

    /**
     * @test         Differentiate
     * @dataProvider dataProviderForDifferentiate
     * @param        array $polynomial
     * @param        array $expected
     */
    public function testDifferentiation(array $polynomial, array $expected)
    {
        // Given
        $polynomial = new Polynomial($polynomial);
        $expected   = new Polynomial($expected);

        // When
        $derivative = $polynomial->differentiate();

        // Then
        $this->assertEquals($expected, $derivative);
    }

    /**
     * @return array (coefficients, derivative)
     */
    public function dataProviderForDifferentiate(): array
    {
        return [
            [
                [1, 2, 3], // p(x)  = x² + 2x + 3
                [2, 2]     // p'(x) = 2x + 2
            ],
            [
                [2, 3, 4], // p(x)  = 2x² + 3x + 4
                [4, 3]     // p'(x) = 4x + 3
            ],
            [
                [1, 0], // p(x)  = x
                [1]     // p'(x) = 1
            ],
            [
                [5, 0], // p(x)  = 5x
                [5]     // p'(x) = 5
            ],
            [
                [1, 0, 0], // p(x)  = x²
                [2, 0]     // p'(x) = 2x
            ],
            [
                [5],    // p(x)  = 5
                [0]     // p'(x) = 0
            ],
            [
                [1],    // p(x)  = 1
                [0]     // p'(x) = 0
            ],
            [
                [0],    // p(x)  = 0
                [0]     // p'(x) = 0
            ],
        ];
    }

    /**
     * @test         Integration
     * @dataProvider dataProviderForIntegrate
     * @param        array $polynomial
     * @param        array $expected_integral
     */
    public function testIntegration(array $polynomial, array $expected_integral)
    {
        // Given
        $polynomial = new Polynomial($polynomial);
        $expected   = new Polynomial($expected_integral);

        // When
        $integral = $polynomial->integrate();

        // Then
        $this->assertEquals($expected, $integral);
    }

    /**
     * @return array (coefficients, integral)
     */
    public function dataProviderForIntegrate(): array
    {
        return [
            [
                [1, 2, 3],      // f(x)  = x² + 2x + 3
                [1 / 3, 1, 3, 0], // ∫f(x) = (1/3)x³ + x² + 3x
            ],
            [
                [5],    // f(x)  = 5
                [5, 0], // ∫f(x) = 5x
            ],
            [
                [0],    // f(x)  = 0
                [0, 0], // ∫f(x) = 0x
            ],
            [
                [1, 0],      // f(x)  = x
                [1 / 2, 0, 0], // ∫f(x) = (1/2)²
            ],
        ];
    }

    /**
     * @test Fundamental theorem of calculus
     */
    public function testFundamentalTheoremOfCalculus()
    {
        // Given p(x) = x² + 2x + 3
        $polynomial = new Polynomial([1, 2, 3]);
        $expected   = $polynomial;

        // When
        $integral   = $polynomial->integrate();
        $actual     = $integral->differentiate();

        // Then
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test         Addition
     * @dataProvider dataProviderForAddition
     * @param        array $polynomialA
     * @param        array $polynomialB
     * @param        array $expected_sum
     * @throws       \Exception
     */
    public function testAddition(array $polynomialA, array $polynomialB, array $expected_sum)
    {
        // Given
        $polynomialA  = new Polynomial($polynomialA);
        $polynomialB  = new Polynomial($polynomialB);
        $expected     = new Polynomial($expected_sum);

        // When
        $sum = $polynomialA->add($polynomialB);

        // Then
        $this->assertEquals($expected, $sum);
    }

    /**
     * @return array (p1, p2, sum)
     */
    public function dataProviderForAddition(): array
    {
        return [
            [
                [1, 2, 3],      // f(x)      = x² + 2x + 3
                [1, 2, 3],      // g(x)      = x² + 2x + 3
                [2, 4, 6],      // f(x)+g(x) = 2x² + 4x + 6
            ],
            [
                [1, 2, 3],      // f(x)      = x² + 2x + 3
                [2, 3, 1],      // g(x)      = 2x² + 3x + 1
                [3, 5, 4],      // f(x)+g(x) = 3x² + 5x + 4
            ],
            [
                [1, 2, 3, 4, 4], // f(x)      = x⁴ + 2x³ + 3x² + 4x + 4
                [2, 3, 1],       // g(x)      = 2x² + 3x + 1
                [1, 2, 5, 7, 5], // f(x)+g(x) = x⁴ + 2x³ + 5x² + 7x + 5
            ],
            [
                [2, 3, 1],       // f(x)      = 2x² + 3x + 1
                [1, 2, 3, 4, 4], // g(x)      = x⁴ + 2x³ + 3x² + 4x + 4
                [1, 2, 5, 7, 5], // f(x)+g(x) = x⁴ + 2x³ + 5x² + 7x + 5
            ],
            [
                [1, -8, 12, 3],  // f(x)      = x³ - 8x² + 12x + 3
                [1, -8, 12, 3],  // g(x)      = f(x)
                [2, -16, 24, 6], // f(x)+g(x) = 2x³ - 16x² + 24x + 6
            ],
        ];
    }

    /**
     * @test         Subtraction
     * @dataProvider dataProviderForSubtraction
     * @param        array $polynomialA
     * @param        array $polynomialB
     * @param        array $expected_sum
     * @throws       \Exception
     */
    public function testSubtraction(array $polynomialA, array $polynomialB, array $expected_sum)
    {
        // Given
        $polynomialA = new Polynomial($polynomialA);
        $polynomialB = new Polynomial($polynomialB);
        $expected    = new Polynomial($expected_sum);

        // When
        $difference = $polynomialA->subtract($polynomialB);

        // Then
        $this->assertEquals($expected, $difference);
    }

    /**
     * @return array (p1, p2, difference)
     */
    public function dataProviderForSubtraction(): array
    {
        return [
            [
                [1, 2, 3],      // f(x)      = x² + 2x + 3
                [1, 2, 3],      // g(x)      = x² + 2x + 3
                [0, 0, 0],      // f(x)-g(x) = 0
            ],
            [
                [1, 2, 3],      // f(x)      = x² + 2x + 3
                [2, 3, 1],      // g(x)      = 2x² + 3x + 1
                [-1, -1, 2],    // f(x)-g(x) = -x² - x + 2
            ],
            [
                [1, 2, 3, 4, 4], // f(x)      = x⁴ + 2x³ + 3x² + 4x + 4
                [2, 3, 1],       // g(x)      = 2x² + 3x + 1
                [1, 2, 1, 1, 3], // f(x)-g(x) = x⁴ + 2x³ + x² + x + 3
            ],
            [
                [1, -8, 12, 3],  // f(x)      = x³ - 8x² + 12x + 3
                [1, -8, 12, 3],  // g(x)      = f(x)
                [0, 0, 0, 0],    // f(x)-g(x) = 0
            ],
        ];
    }

    /**
     * @test         Multiplication
     * @dataProvider dataProviderForMultiplication
     * @param        array $polynomialA
     * @param        array $polynomialB
     * @param        array $expected_product
     * @throws       \Exception
     */
    public function testMultiplication(array $polynomialA, array $polynomialB, array $expected_product)
    {
        // Given
        $polynomialA = new Polynomial($polynomialA);
        $polynomialB = new Polynomial($polynomialB);
        $expected    = new Polynomial($expected_product);

        // When
        $product = $polynomialA->multiply($polynomialB);

        // Then
        $this->assertEquals($expected, $product);
    }

    /**
     * @return array (p1, p2, product)
     */
    public function dataProviderForMultiplication(): array
    {
        return [
            [
                [1, 2, 3],         // f(x)      = x² + 2x + 3
                [1, 2, 3],         // g(x)      = x² + 2x + 3
                [1, 4, 10, 12, 9], // f(x)*g(x) = x⁴ + 4x³ + 10x² + 12x + 9
            ],
            [
                [1, 2, 3],         // f(x)      = x² + 2x + 3
                [2, 3, 1],         // g(x)      = 2x² + 3x + 1
                [2, 7, 13, 11, 3], // f(x)*g(x) = 2x⁴ + 7x³ + 13x² + 11x + 3
            ],
            [
                [1, 2, 3, 4, 4],           // f(x)      = x⁴ + 2x³ + 3x² + 4x + 4
                [2, 3, 1],                 // g(x)      = 2x² + 3x + 1
                [2, 7, 13, 19, 23, 16, 4], // f(x)*g(x) = 2x⁶ + 7x⁵ + 13x⁴ + 19x³ + 23x² + 16x + 4
            ],
            [
                [1, -8, 12, 3],                // f(x)      = x³ - 8x² + 12x + 3
                [1, -8, 12, 3],                // g(x)      = f(x)
                [1, -16, 88, -186, 96, 72, 9], // f(x)+g(x) = x⁶ - 16x⁵ + 88x⁴ - 186x³ + 96x² + 72x + 9
            ],
        ];
    }

    /**
     * @test         Scalar addition
     * @dataProvider dataProviderForScalarAddition
     * @param        array $polynomialA
     * @param        int   $scaler
     * @param        array $expected_product
     * @throws       \Exception
     */
    public function testScalarAddition(array $polynomialA, int $scaler, array $expected_product)
    {
        // Given
        $polynomialA = new Polynomial($polynomialA);
        $expected    = new Polynomial($expected_product);

        // When
        $sum = $polynomialA->add($scaler);

        // Then
        $this->assertEquals($expected, $sum);
    }

    /**
     * @return array (p1, scalar, sum)
     */
    public function dataProviderForScalarAddition(): array
    {
        return [
            [
                [1, 2, 3],         // f(x)      = x² + 2x + 3
                2,
                [1, 2, 5],         // f(x)*c    = x² + 2x + 5
            ],
            [
                [1, 2, 3, 4, 4],      // f(x)      = x⁴ + 2x³ + 3x² + 4x + 4
                -2,
                [1, 2, 3, 4, 2],      // f(x)*c    = 1x⁴ + 2x³ + 3x² + 4x + 2
            ],
        ];
    }

    /**
     * @test         Scalar subtraction
     * @dataProvider dataProviderForScalarSubtraction
     * @param        array $polynomialA
     * @param        int   $scaler
     * @param        array $expected_product
     * @throws       \Exception
     */
    public function testScalarSubtraction(array $polynomialA, int $scaler, array $expected_product)
    {
        // Given
        $polynomialA = new Polynomial($polynomialA);
        $expected    = new Polynomial($expected_product);

        // When
        $difference = $polynomialA->subtract($scaler);

        // Then
        $this->assertEquals($expected, $difference);
    }

    /**
     * @return array (p1, scalar, difference)
     */
    public function dataProviderForScalarSubtraction(): array
    {
        return [
            [
                [1, 2, 3],         // f(x)      = x² + 2x + 3
                2,
                [1, 2, 1],         // f(x)*c    = x² + 2x + 1
            ],
            [
                [1, 2, 3, 4, 4],      // f(x)      = x⁴ + 2x³ + 3x² + 4x + 4
                -2,
                [1, 2, 3, 4, 6],      // f(x)*c    = 1x⁴ + 2x³ + 3x² + 4x + 6
            ],
        ];
    }

    /**
     * @test         Scalar multiplication
     * @dataProvider dataProviderForScalarMultiplication
     * @param        array $polynomialA
     * @param        int $scaler
     * @param        array $expected_product
     * @throws       \Exception
     */
    public function testScalarMultiplication(array $polynomialA, int $scaler, array $expected_product)
    {
        // Given
        $polynomialA = new Polynomial($polynomialA);
        $expected    = new Polynomial($expected_product);

        // When
        $product = $polynomialA->multiply($scaler);

        // Then
        $this->assertEquals($expected, $product);
    }

    /**
     * @return array (p1, scalar, product)
     */
    public function dataProviderForScalarMultiplication(): array
    {
        return [
            [
                [1, 2, 3],         // f(x)      = x² + 2x + 3
                2,
                [2, 4, 6],         // f(x)*c    = 2x² + 4x + 6
            ],
            [
                [1, 2, 3, 4, 4],           // f(x)      = x⁴ + 2x³ + 3x² + 4x + 4
                -2,
                [-2, -4, -6, -8, -8],      // f(x)*c    = -2x⁴ - 4x³ - 6x² - 8x - 8
            ],
        ];
    }

    /**
     * @test         roots
     * @dataProvider dataProviderForRoots
     * @param        array $polynomialA
     * @param        array $expected_roots
     * @throws       \Exception
     */
    public function testRoots(array $polynomialA, array $expected_roots)
    {
        // Given
        $polynomialA = new Polynomial($polynomialA);

        // When
        $roots = $polynomialA->roots();

        // Then
        $this->assertEquals($expected_roots, $roots);
    }

    /**
     * @return array
     */
    public function dataProviderForRoots(): array
    {
        return [
            // Degree 0
            [
                [0],
                [null],
            ],
            [
                [3],
                [null],
            ],
            [
                [0, -3],
                [null],
            ],
            // Degree 1
            [
                [1, -3],
                [3],
            ],
            [
                [2, 0],
                [0],
            ],
            // Degree 2
            [
                [1, -3, -4],
                [-1, 4],
            ],
            // Degree 3
            [
                [1, -6, 11, -6],
                [3, 1, 2],
            ],
            // Degree 4
            [
                [1, -10, 35, -50, 24],
                [4, 1, 3, 2],
            ],
        ];
    }

    /**
     * @test         roots NAN - Closed form solutions don't exist for degrees 5 or higher - no implementation
     * @dataProvider dataProviderForRootsNAN
     * @param        array $polynomialA
     * @throws       \Exception
     */
    public function testRootsNAN(array $polynomialA)
    {
        // Given
        $polynomialA = new Polynomial($polynomialA);

        // When
        $roots = $polynomialA->roots();

        // Then
        $this->assertCount(1, $roots);
        $this->assertNan($roots[0]);
    }

    /**
     * @return array
     */
    public function dataProviderForRootsNAN(): array
    {
        return [
            'degree 5' => [
                [1, -3, -4, 5, 5, 5],
            ],
            'degree 6' => [
                [1, 2, 3, 4, 5, 6, 7],
            ],
        ];
    }

    /**
     * @test   add - IncorrectTypeException if the argument is not numeric or a Polynomial
     * @throws \Exception
     */
    public function testException()
    {
        // Given
        $string = 'This is a string!';
        $poly   = new Polynomial([1, 2]);

        // Then
        $this->expectException(Exception\IncorrectTypeException::class);

        // When
        $sum = $poly->add($string);
    }

    /**
     * @test         checkNumericOrPolynomial returns a Polynomial for numeric and Polynomial inputs
     * @dataProvider dataProviderForCheckNumericOrPolynomial
     */
    public function testCheckNumericOrPolynomialNumericInput($input)
    {
        // Given
        $method = new \ReflectionMethod(Polynomial::class, 'checkNumericOrPolynomial');
        $method->setAccessible(true);

        // When
        $polynomial = $method->invokeArgs(new Polynomial([1]), [$input]);

        // Then
        $this->assertInstanceOf(Polynomial::class, $polynomial);
    }

    public function dataProviderForCheckNumericOrPolynomial(): array
    {
        return [
            [-1],
            [0],
            [1],
            [10],
            [2.45],
            ['3'],
            ['5.4'],
            [new Polynomial([4])],
            [new Polynomial([2, 3, 4])],

        ];
    }

    /**
     * @test checkNumericOrPolynomial throws an IncorrectTypeException if the input is not numeric or a Polynomial
     */
    public function testCheckNumericOrPolynomialException()
    {
        // Given
        $method = new \ReflectionMethod(Polynomial::class, 'checkNumericOrPolynomial');
        $method->setAccessible(true);

        // Then
        $this->expectException(Exception\IncorrectTypeException::class);

        // When
        $polynomial = $method->invokeArgs(new Polynomial([1]), ['not a number']);
    }

    /**
     * @test         negate returns a Polynomial with every coefficient negated
     * @dataProvider dataProviderForNegate
     * @param        array $polynomial
     * @param        array $expected_negated_polynomial
     */
    public function testNegate(array $polynomial, array $expected_negated_polynomial)
    {
        // Given
        $polynomial = new Polynomial($polynomial);
        $expected   = new Polynomial($expected_negated_polynomial);

        // When
        $negated = $polynomial->negate();

        // Then
        $this->assertEquals($expected, $negated);
    }

    /**
     * @return array
     */
    public function dataProviderForNegate(): array
    {
        return [
            [
                [],
                [],
            ],
            [
                [0],
                [0],
            ],
            [
                [1],
                [-1],
            ],
            [
                [-1],
                [1],
            ],
            [
                [1, 1],
                [-1, -1],
            ],
            [
                [-1, -1],
                [1, 1],
            ],
            [
                [1, -2, 3],
                [-1, 2, -3],
            ],
            [
                [5, 5, 5, -5, -5],
                [-5, -5, -5, 5, 5],
            ],
            [
                [23, 5, 65, 0, -4],
                [-23, -5, -65, 0, 4],
            ],
            [
                [-4, -3, 0, 0, 0],
                [4, 3, 0, 0, 0],
            ],
            [
                [-3, -4, 2, 1, 5, 5, 4, -3, 2],
                [3, 4, -2, -1, -5, -5, -4, 3, -2],
            ],
            [
                [1, 2, 3],
                [-1, -2, -3],
            ],
        ];
    }

    /**
     * @test         Test that the proper companion matrix is calulated from a polynomial
     * @dataProvider dataProviderForTestCompanionMatrix
     * @param array  $poly the polynomial
     * @param array  $companion_matrix the expected companion matrix
     */
    public function testCompanionMatrix(array $poly, array $expected_matrix)
    {
        // Create a polynomial
        $poly = new Polynomial($poly);

        $companion = $poly->companionMatrix();
        $this->assertEqualsWithDelta($expected_matrix, $companion->getMatrix(), .0000001);
    }

    /**
     * Data cross referenced with numpy.polynomial.polynomial.polycompanion(c)
     * @return array[]
     */
    public function dataProviderForTestCompanionMatrix(): array
    {
        return [
            [
                [1, -21, 175, -735, 1624, -1764, 720],
                [
                    [0, 0, 0, 0, 0, -720],
                    [1, 0, 0, 0, 0, 1764],
                    [0, 1, 0, 0, 0, -1624],
                    [0, 0, 1, 0, 0, 735],
                    [0, 0, 0, 1, 0, -175],
                    [0, 0, 0, 0, 1, 21],
                ],
            ],
            [
                [2, -42, 350, -1470, 3248, -3528, 1440],
                [
                    [0, 0, 0, 0, 0, -720],
                    [1, 0, 0, 0, 0, 1764],
                    [0, 1, 0, 0, 0, -1624],
                    [0, 0, 1, 0, 0, 735],
                    [0, 0, 0, 1, 0, -175],
                    [0, 0, 0, 0, 1, 21],
                ],
            ],
            [
                [1, -1, -30],
                [
                    [0, 30],
                    [1, 1],
                ],
            ],
            [
                [1, 5, 0],
                [
                    [0, 0],
                    [1, -5],
                ],
            ],
        ];
    }

    /**
     * @test   companionMatrix - OutOfBoundsException if the polynomial is degree 0.
     * @throws \Exception
     */
    public function testCompanionException()
    {
        // Given
        $poly   = new Polynomial([2]);

        // Then
        $this->expectException(Exception\OutOfBoundsException::class);

        // When
        $matrix = $poly->companionMatrix();
    }
}
