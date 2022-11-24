<?php

namespace MathPHP\Tests\LinearAlgebra\Matrix\Object;

use MathPHP\Expression\Polynomial;
use MathPHP\LinearAlgebra\MatrixFactory;
use MathPHP\LinearAlgebra\ObjectMatrix;
use MathPHP\LinearAlgebra\Vector;
use MathPHP\Number\ArbitraryInteger;
use MathPHP\Number\Complex;
use MathPHP\Exception;
use MathPHP\Number\ObjectArithmetic;

class ObjectMatrixTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test         The constructor throws the proper exceptions
     * @dataProvider dataProviderConstructorException
     * @param array  $A
     * @param string $exception
     */
    public function testMatrixConstructorException(array $A, string $exception)
    {
        // Then
        $this->expectException($exception);

        // When
        $A = new ObjectMatrix($A);
    }

    public function dataProviderConstructorException(): array
    {
        return [
            'object does not implement ObjectArithmetic' => [
                [[new \stdClass()]],
                Exception\IncorrectTypeException::class,
            ],
            'multiple objects do not implement ObjectArithmetic' => [
                [
                    [new \stdClass(), new Polynomial([1, 2, 3])],
                    [new \stdClass(), new Polynomial([1, 2, 3])]
                ],
                Exception\IncorrectTypeException::class,
            ],
            'objects are not the same type' => [
                [
                    [new ArbitraryInteger(5), new Polynomial([1, 2, 3])],
                    [new ArbitraryInteger(5), new Polynomial([1, 2, 3])]
                ],
                Exception\IncorrectTypeException::class
            ],
            'different row counts' => [
                [
                    [new Polynomial([1, 2, 3]), new Polynomial([1, 2, 3])],
                    [new Polynomial([1, 2, 3])]
                ],
                Exception\BadDataException::class
            ],
        ];
    }

    /**
     * @test         Addition throws the proper exceptions
     * @dataProvider dataProviderForArithmeticExceptions
     * @param array            $A
     * @param ObjectArithmetic $B
     * @param string           $exception
     */
    public function testMatrixAddException(array $A, ObjectArithmetic $B, string $exception)
    {
        // Given
        $A = new ObjectMatrix($A);

        // Then
        $this->expectException($exception);

        // When
        $C = $A->add($B);
    }

    /**
     * @test         Subtraction throws the proper exceptions
     * @dataProvider dataProviderForArithmeticExceptions
     * @param array            $A
     * @param ObjectArithmetic $B
     * @param string           $exception
     */
    public function testMatrixSubtractException(array $A, ObjectArithmetic $B, string $exception)
    {
        // Given
        $A = new ObjectMatrix($A);

        // Then
        $this->expectException($exception);

        // When
        $C = $A->subtract($B);
    }

    /**
     * @test         Multiplication throws the proper exceptions
     * @dataProvider dataProviderForArithmeticExceptions
     * @param array            $A
     * @param ObjectArithmetic $B
     * @param string           $exception
     */
    public function testMatrixMultiplyException(array $A, ObjectArithmetic $B, string $exception)
    {
        // Given
        $A = new ObjectMatrix($A);

        // Then
        $this->expectException($exception);

        // When
        $C = $A->multiply($B);
    }

    public function dataProviderForArithmeticExceptions(): array
    {
        return[
            [ // Different Sizes
                [[new Polynomial([1, 2, 3]), new Polynomial([1, 2, 3])],
                    [new Polynomial([1, 2, 3]), new Polynomial([1, 2, 3])]],
                MatrixFactory::create([[new Polynomial([1, 2, 3])]]),
                Exception\MatrixException::class,
            ],
            [ // Different Types
                [[new Polynomial([1, 2, 3])]],
                new ObjectMatrix([[new Complex(1, 2)]]),
                Exception\IncorrectTypeException::class,
            ],
            [ // Not a Matrix
                [[new Polynomial([1, 2, 3])]],
                new Complex(1, 2),
                Exception\IncorrectTypeException::class,
            ],
        ];
    }

    /**
     * @test         Cannot compute the determinant of a non-square matrix
     * @dataProvider dataProviderDetException
     * @param        array $A
     */
    public function testMatrixDetException(array $A)
    {
        // Given
        $A = new ObjectMatrix($A);

        // Then
        $this->expectException(Exception\MatrixException::class);

        // When
        $det = $A->det();
    }

    /**
     * @return array
     */
    public function dataProviderDetException(): array
    {
        return [
            [
                [
                    [new Polynomial([1, 2]), new Polynomial([2, 1])],
                ],
            ],
        ];
    }

    /**
     * @test         isEqual
     * @dataProvider dataProviderisEqual
     * @param        array $A
     * @param        array $B
     * @param        bool $expected
     * @throws       \Exception
     */
    public function testIsEqual(array $A, array $B, bool $expected)
    {
        // Given
        $A = MatrixFactory::create($A);
        $B = MatrixFactory::create($B);

        // When
        $comparison = $A->isEqual($B);

        // Then
        $this->assertEquals($expected, $comparison);
    }

    /**
     * @return array
     */
    public function dataProviderisEqual()
    {
        return [
            'same' => [
                [[new Polynomial([1, 0])]],
                [[new Polynomial([1, 0])]],
                true,
            ],
            'different types' => [
                [[new Polynomial([1, 0])]],
                [[1]],
                false,
            ],
            'different contents' => [
                [[new Polynomial([1, 0])]],
                [[new Polynomial([1, 1])]],
                false,
            ],
            'different shapes' => [
                [[new Polynomial([1, 0]), new Polynomial([1, 0])]],
                [[new Polynomial([1, 0])], [new Polynomial([1, 0])]],
                false,
            ],
        ];
    }

    /**
     * @test         add
     * @dataProvider dataProviderAdd
     * @param        array $A
     * @param        array $B
     * @param        array $expected
     * @throws       \Exception
     */
    public function testAdd(array $A, array $B, array $expected)
    {
        // Given
        $A = new ObjectMatrix($A);
        $B = new ObjectMatrix($B);

        // And
        $expected = matrixFactory::create($expected);

        // When
        $sum = $A->add($B);

        // Then
        $this->assertEquals($expected, $sum);
    }

    /**
     * @return array
     */
    public function dataProviderAdd(): array
    {
        return [
            [
                [
                    [new Polynomial([1, 0]), new Polynomial([0, 0])],
                    [new Polynomial([0, 0]), new Polynomial([1, 0])],
                ],
                [
                    [new Polynomial([1, 0]), new Polynomial([1, 1])],
                    [new Polynomial([1, 1]), new Polynomial([1, 0])],
                ],
                [
                    [new Polynomial([2, 0]), new Polynomial([1, 1])],
                    [new Polynomial([1, 1]), new Polynomial([2, 0])],
                ],
            ],
            [
                [
                    [new Polynomial([1, 0]), new Polynomial([1, 0])],
                    [new Polynomial([1, 0]), new Polynomial([1, 0])],
                ],
                [
                    [new Polynomial([1, 0]), new Polynomial([1, 1])],
                    [new Polynomial([1, 1]), new Polynomial([1, 0])],
                ],
                [
                    [new Polynomial([2, 0]), new Polynomial([2, 1])],
                    [new Polynomial([2, 1]), new Polynomial([2, 0])],
                ],
            ],
        ];
    }

    /**
     * @test         subtract
     * @dataProvider dataProviderSubtract
     * @param        array $A
     * @param        array $B
     * @param        array $expected
     * @throws       \Exception
     */
    public function testSubtract(array $A, array $B, array $expected)
    {
        // Given
        $A        = new ObjectMatrix($A);
        $B        = new ObjectMatrix($B);
        $expected = new ObjectMatrix($expected);

        // When
        $difference = $A->subtract($B);

        // Then
        $this->assertEquals($expected->getMatrix(), $difference->getMatrix());
    }

    /**
     * @return array
     */
    public function dataProviderSubtract(): array
    {
        return [
            [
                [
                    [new Polynomial([1, 0]), new Polynomial([0, 0])],
                    [new Polynomial([0, 0]), new Polynomial([1, 0])],
                ],
                [
                    [new Polynomial([2, 1]), new Polynomial([2, 1])],
                    [new Polynomial([1, -1]), new Polynomial([-1, 0])],
                ],
                [
                    [new Polynomial([-1, -1]), new Polynomial([-2, -1])],
                    [new Polynomial([-1, 1]), new Polynomial([2, 0])],
                ],
            ],
            [
                [
                    [new Polynomial([1, 0]), new Polynomial([1, 0])],
                    [new Polynomial([1, 0]), new Polynomial([1, 0])],
                ],
                [
                    [new Polynomial([-2, 0]), new Polynomial([1, -1])],
                    [new Polynomial([-2, 2]), new Polynomial([4, 4])],
                ],
                [
                    [new Polynomial([3, 0]), new Polynomial([0, 1])],
                    [new Polynomial([3, -2]), new Polynomial([-3, -4])],
                ],
            ],
        ];
    }

    /**
     * @test         multiply
     * @dataProvider dataProviderMul
     * @param        array $A
     * @param        array $B
     * @param        array $expected
     */
    public function testMul(array $A, array $B, array $expected)
    {
        // Given
        $A = new ObjectMatrix($A);
        $B = new ObjectMatrix($B);

        // And
        $expected = matrixFactory::create($expected);

        // When
        $sum = $A->multiply($B);

        // Then
        $this->assertEquals($expected, $sum);
    }

    /**
     * @return array
     */
    public function dataProviderMul(): array
    {
        return [
            [
                [
                    [new Polynomial([1, 0]), new Polynomial([0, 0])],
                    [new Polynomial([0, 0]), new Polynomial([1, 0])],
                ],
                [
                    [new Polynomial([1, 0]), new Polynomial([1, 1])],
                    [new Polynomial([1, 1]), new Polynomial([1, 0])],
                ],
                [
                    [new Polynomial([1, 0, 0]), new Polynomial([1, 1, 0])],
                    [new Polynomial([1, 1, 0]), new Polynomial([1, 0, 0])],
                ],
            ],
            [
                [
                    [new Polynomial([1, 0]), new Polynomial([1, 0])],
                    [new Polynomial([1, 0]), new Polynomial([1, 0])],
                ],
                [
                    [new Polynomial([1, 0]), new Polynomial([1, 1])],
                    [new Polynomial([1, 1]), new Polynomial([1, 0])],
                ],
                [
                    [new Polynomial([2, 1, 0]), new Polynomial([2, 1, 0])],
                    [new Polynomial([2, 1, 0]), new Polynomial([2, 1, 0])],
                ],
            ],
        ];
    }

    /**
     * @test         Matrix can be multiplied by a vector
     * @dataProvider dataProviderMultiplyVector
     * @param        array $A
     * @param        array $B
     * @param        array $expected
     */
    public function testMultiplyVector(array $A, array $B, array $expected)
    {
        // Given
        $A = new ObjectMatrix($A);
        $B = new Vector($B);

        // When
        $sum = $A->multiply($B);

        // Then
        $expected = MatrixFactory::create($expected);
        $this->assertEquals($expected, $sum);
    }

    public function dataProviderMultiplyVector(): array
    {
        return [
            [
                [
                    [new Polynomial([1, 0]), new Polynomial([0, 0])],
                    [new Polynomial([0, 0]), new Polynomial([1, 0])],
                ],
                [new Polynomial([1, 0]), new Polynomial([1, 1])],
                [
                    [new Polynomial([1, 0, 0])],
                    [new Polynomial([1, 1, 0])],
                ],
            ],
        ];
    }

    /**
     * @test         det
     * @dataProvider dataProviderDet
     * @param        array $A
     * @param        Polynomial $expected
     */
    public function testDet(array $A, Polynomial $expected)
    {
        // Given
        $A = new ObjectMatrix($A);

        // When
        $det = $A->det();

        // Then
        $this->assertEquals($det, $expected);

        // And when
        $det = $A->det();

        // Then
        $this->assertEquals($expected, $det);
    }

    /**
     * @return array
     */
    public function dataProviderDet(): array
    {
        return [
            [
                [
                    [new Polynomial([1, 0])],
                ],
                new Polynomial([1, 0]),
            ],
            [
                [
                    [new Polynomial([1, 0]), new Polynomial([1, 0])],
                    [new Polynomial([1, 0]), new Polynomial([0, 4])],
                ],
                new Polynomial([-1, 4, 0]),
            ],
        ];
    }

    /**
     * @test         cofactor
     * @dataProvider dataProviderForCofactor
     * @param        array            $A
     * @param        int              $mᵢ
     * @param        int              $nⱼ
     * @param        ArbitraryInteger $Cᵢⱼ
     */
    public function testCofactor(array $A, int $mᵢ, int $nⱼ, ArbitraryInteger $Cᵢⱼ)
    {
        // Given
        $A = new ObjectMatrix($A);

        // When
        $cofactor = $A->cofactor($mᵢ, $nⱼ);

        // Then
        $this->assertEquals($Cᵢⱼ, $cofactor);
        $this->assertEquals($Cᵢⱼ->toInt(), $cofactor->toInt());
    }

    public function dataProviderForCofactor(): array
    {
        return [
            [
                [
                    [new ArbitraryInteger(1), new ArbitraryInteger(4), new ArbitraryInteger(7)],
                    [new ArbitraryInteger(3), new ArbitraryInteger(0), new ArbitraryInteger(5)],
                    [new ArbitraryInteger(-1), new ArbitraryInteger(9), new ArbitraryInteger(11)],
                ],
                0, 0, new ArbitraryInteger(-45)
            ],
        ];
    }

    /**
     * @test transpose
     */
    public function testTranspose()
    {
        // Given
        $A = [
            [new ArbitraryInteger(1), new ArbitraryInteger(4)],
            [new ArbitraryInteger(3), new ArbitraryInteger(0)],
        ];
        $A = new ObjectMatrix($A);

        // And
        $expected = [
            [new ArbitraryInteger(1), new ArbitraryInteger(3)],
            [new ArbitraryInteger(4), new ArbitraryInteger(0)],
        ];

        // When
        $Aᵀ = $A->transpose();

        // Then
        $this->assertEquals($expected, $Aᵀ->getMatrix());
    }

    /**
     * @test scalarMultiply
     */
    public function testScalarMultiply()
    {
        // Given
        $A = [
            [new ArbitraryInteger(1), new ArbitraryInteger(4)],
            [new ArbitraryInteger(-3), new ArbitraryInteger(0)],
        ];
        $A = new ObjectMatrix($A);

        // And
        $λ = 2;

        // When
        $λA = $A->scalarMultiply($λ);

        // Then
        $expected = new ObjectMatrix([
            [new ArbitraryInteger(2), new ArbitraryInteger(8)],
            [new ArbitraryInteger(-6), new ArbitraryInteger(0)],
        ]);
        $this->assertEquals($expected->getMatrix(), $λA->getMatrix());
    }

    /**
     * @test scalarMultiply by an object
     */
    public function testScalarMultiplyByObject()
    {
        // Given
        $A = [
            [new ArbitraryInteger(1), new ArbitraryInteger(4)],
            [new ArbitraryInteger(-3), new ArbitraryInteger(0)],
        ];
        $A = new ObjectMatrix($A);

        // And
        $λ = new ArbitraryInteger(2);

        // When
        $λA = $A->scalarMultiply($λ);

        // Then
        $expected = new ObjectMatrix([
            [new ArbitraryInteger(2), new ArbitraryInteger(8)],
            [new ArbitraryInteger(-6), new ArbitraryInteger(0)],
        ]);
        $this->assertEquals($expected->getMatrix(), $λA->getMatrix());
    }

    /**
     * @test createZeroValue
     */
    public function testCreateZeroValue()
    {
        // Given
        $zeroMatrix = ObjectMatrix::createZeroValue();

        // And
        $expected = [
            [new ArbitraryInteger(0)]
        ];

        // Then
        $this->assertEquals($expected, $zeroMatrix->getMatrix());
    }

    /**
     * @test         trace
     * @dataProvider dataProviderForTrace
     * @param        array            $A
     * @param        ObjectArithmetic $tr
     */
    public function testTrace(array $A, ObjectArithmetic $tr)
    {
        // Given
        $A = new ObjectMatrix($A);

        // When
        $trace = $A->trace();

        // Then
        $this->assertEquals($tr, $trace);
    }

    public function dataProviderForTrace(): array
    {
        return [
            [
                [
                    [new ArbitraryInteger(1)]
                ],
                new ArbitraryInteger(1)
            ],
            [
                [
                    [new ArbitraryInteger(1), new ArbitraryInteger(2)],
                    [new ArbitraryInteger(2), new ArbitraryInteger(3)],
                ],
                new ArbitraryInteger(4)
            ],
            [
                [
                    [new ArbitraryInteger(1), new ArbitraryInteger(2), new ArbitraryInteger(3)],
                    [new ArbitraryInteger(4), new ArbitraryInteger(5), new ArbitraryInteger(6)],
                    [new ArbitraryInteger(7), new ArbitraryInteger(8), new ArbitraryInteger(9)],
                ],
                new ArbitraryInteger(15)
            ],
        ];
    }

    /**
     * @test trace error when matrix not square
     */
    public function testTraceNotSquare()
    {
        // Given
        $A = new ObjectMatrix([
            [new ArbitraryInteger(1), new ArbitraryInteger(2)]
        ]);

        // Then
        $this->expectException(Exception\MatrixException::class);

        // When
        $tr = $A->trace();
    }
}
