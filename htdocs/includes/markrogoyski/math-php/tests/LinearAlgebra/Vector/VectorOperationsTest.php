<?php

namespace MathPHP\Tests\LinearAlgebra\Vector;

use MathPHP\LinearAlgebra\Vector;
use MathPHP\LinearAlgebra\NumericMatrix;
use MathPHP\Exception;

class VectorOperationsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test         dot product
     * @dataProvider dataProviderForDotProduct
     */
    public function testDotProduct(array $A, array $B, $expected)
    {
        // Given
        $A  = new Vector($A);
        $B  = new Vector($B);

        // When
        $dotProduct = $A->dotProduct($B);

        // Then
        $this->assertEquals($expected, $dotProduct);
    }

    /**
     * @test         inner product
     * @dataProvider dataProviderForDotProduct
     */
    public function testInnerProduct(array $A, array $B, $expected)
    {
        // Given
        $A  = new Vector($A);
        $B  = new Vector($B);

        // When
        $innerProduct = $A->innerProduct($B);

        // Then
        $this->assertEquals($expected, $innerProduct);
    }

    public function dataProviderForDotProduct(): array
    {
        return [
            [ [ 1, 2, 3 ],  [ 4, -5, 6 ],  12 ],
            [ [ -4, -9],    [ -1, 2],     -14 ],
            [ [ 6, -1, 3 ], [ 4, 18, -2 ],  0 ],
        ];
    }

    /**
     * @test   dot product exception
     * @throws \Exception
     */
    public function testDotProductExceptionSizeDifference()
    {
        // Given
        $A = new Vector([1, 2]);
        $B = new Vector([1, 2, 3]);

        // Then
        $this->expectException(Exception\VectorException::class);

        // When
        $A->dotProduct($B);
    }

    /**
     * @test         cross product
     * @dataProvider dataProviderForCrossProduct
     */
    public function testCrossProduct(array $A, array $B, array $R)
    {
        // Given
        $A = new Vector($A);
        $B = new Vector($B);
        $R = new Vector($R);

        // When
        $crossProduct = $A->crossProduct($B);

        // Then
        $this->assertEquals($R, $crossProduct);
    }

    public function dataProviderForCrossProduct(): array
    {
        return [
            [
                [1, 2, 3],
                [4, -5, 6],
                [27,6,-13],
            ],
            [
                [-1, 2, -3],
                [4,-5,6],
                [-3,-6,-3],
            ],
            [
                [0,0,0],
                [0,0,0],
                [0,0,0],
            ],
            [
                [4, 5, 6],
                [7, 8, 9],
                [-3, 6, -3],
            ],
            [
                [4, 9, 3],
                [12, 11, 4],
                [3, 20, -64],
            ],
            [
                [-4, 9, 3],
                [12, 11, 4],
                [3, 52, -152],
            ],
            [
                [4, -9, 3],
                [12, 11, 4],
                [-69, 20, 152],
            ],
            [
                [4, 9, -3],
                [12, 11, 4],
                [69, -52, -64],
            ],
            [
                [4, 9, 3],
                [-12, 11, 4],
                [3, -52, 152],
            ],
            [
                [4, 9, 3],
                [12, -11, 4],
                [69, 20, -152],
            ],
            [
                [4, 9, 3],
                [12, 11, -4],
                [-69, 52, -64],
            ],
        ];
    }

    /**
     * @test         cross product exception - wrong size
     * @dataProvider dataProviderForCrossProductExceptionWrongSize
     */
    public function testCrossProductExceptionWrongSize(array $A, array $B)
    {
        // Given
        $A = new Vector($A);
        $B = new Vector($B);

        // Then
        $this->expectException(Exception\VectorException::class);

        // When
        $A->crossProduct($B);
    }

    public function dataProviderForCrossProductExceptionWrongSize(): array
    {
        return [
            [
                [1, 2],
                [1, 2, 3],
            ],
            [
                [1, 2, 3],
                [1],
            ],
        ];
    }

    /**
     * @test         outer product
     * @dataProvider dataProviderForOuterProduct
     */
    public function testOuterProduct(array $A, array $B, array $R)
    {
        // Given
        $A = new Vector($A);
        $B = new Vector($B);
        $R = new NumericMatrix($R);

        // When
        $outerProduct = $A->outerProduct($B)->getMatrix();

        // Then
        $this->assertEquals($R->getMatrix(), $outerProduct);
    }

    public function dataProviderForOuterProduct(): array
    {
        return [
            [
                [1, 2],
                [3, 4, 5],
                [
                    [3, 4, 5],
                    [6, 8, 10],
                ],
            ],
            [
                [3, 4, 5],
                [1, 2],
                [
                    [3, 6],
                    [4, 8],
                    [5, 10],
                ],
            ],
            [
                [1],
                [2],
                [
                    [2],
                ],
            ],
            [
                [1, 2],
                [2, 3],
                [
                    [2, 3],
                    [4, 6],
                ],
            ],
            [
                [1, 2, 3],
                [2, 3, 4],
                [
                    [2, 3, 4],
                    [4, 6, 8],
                    [6, 9, 12],
                ],
            ],
            [
                [1, 2, 3, 4],
                [2, 3, 4, 5],
                [
                    [2, 3, 4, 5],
                    [4, 6, 8, 10],
                    [6, 9, 12, 15],
                    [8, 12, 16, 20],
                ],
            ],
            [
                [3, 2, 6, 4],
                [4, 5, 1, 7],
                [
                    [12, 15, 3, 21],
                    [8, 10, 2, 14],
                    [24, 30, 6, 42],
                    [16, 20, 4, 28],
                ],
            ],
        ];
    }

    /**
     * @test         sum
     * @dataProvider dataProviderForSum
     */
    public function testSum(array $A, $expected)
    {
        // Given
        $A = new Vector($A);

        // When
        $sum = $A->sum();

        // Then
        $this->assertEqualsWithDelta($expected, $sum, 0.00001);
    }

    public function dataProviderForSum(): array
    {
        return [
            [ [1, 2, 3], 6 ],
            [ [2, 3, 4, 8, 8, 9], 34 ],
        ];
    }

    /**
     * @test         scalar multiply
     * @dataProvider dataProviderForScalarMultiply
     */
    public function testScalarMultiply(array $A, $k, array $R)
    {
        // Given
        $A  = new Vector($A);
        $R  = new Vector($R);

        // When
        $kA = $A->scalarMultiply($k);

        // Then
        $this->assertEquals($R, $kA);
        $this->assertEquals($R->getVector(), $kA->getVector());
    }

    public function dataProviderForScalarMultiply(): array
    {
        return [
            [
                [1],
                2,
                [2],
            ],
            [
                [2, 3],
                2,
                [4, 6],
            ],
            [
                [1, 2, 3],
                2,
                [2, 4, 6],
            ],
            [
                [1, 2, 3, 4, 5],
                5,
                [5, 10, 15, 20, 25],
            ],
            [
                [1, 2, 3, 4, 5],
                0,
                [0, 0, 0, 0, 0],
            ],
            [
                [1, 2, 3, 4, 5],
                -2,
                [-2, -4, -6, -8, -10],
            ],
            [
                [1, 2, 3, 4, 5],
                0.2,
                [0.2, 0.4, 0.6, 0.8, 1],
            ],
        ];
    }

    /**
     * @test         scalar divide
     * @dataProvider dataProviderForScalarDivide
     */
    public function testScalarDivide(array $A, $k, array $R)
    {
        // Given
        $A    = new Vector($A);
        $R    = new Vector($R);

        // When
        $A／k = $A->scalarDivide($k);

        // Then
        $this->assertEquals($R, $A／k);
        $this->assertEquals($R->getVector(), $A／k->getVector());
    }

    public function dataProviderForScalarDivide(): array
    {
        return [
            [
                [1],
                2,
                [1 / 2],
            ],
            [
                [2, 4],
                2,
                [1, 2],
            ],
            [
                [1, 2, 3],
                2,
                [1 / 2, 1, 3 / 2],
            ],
            [
                [5, 10, 15, 20, 25],
                5,
                [1, 2, 3, 4, 5],
            ],
            [
                [0, 0, 0, 0, 0],
                47,
                [0, 0, 0, 0, 0],
            ],
            [
                [-2, -4, -6, -8, -10],
                -2,
                [1, 2, 3, 4, 5],
            ],
            [
                [1, 2, 3, 4, 5],
                0.2,
                [5, 10, 15, 20, 25],
            ],
        ];
    }

    /**
     * @test         add
     * @dataProvider dataProviderForAdd
     */
    public function testAdd(array $A, array $B, array $R)
    {
        // Given
        $A    = new Vector($A);
        $B    = new Vector($B);
        $R    = new Vector($R);

        // When
        $A＋B = $A->add($B);

        // Then
        $this->assertEquals($R, $A＋B);
        $this->assertEquals($R->getVector(), $A＋B->getVector());
    }

    public function dataProviderForAdd(): array
    {
        return [
            [
                [1],
                [2],
                [3],
            ],
            [
                [1, 2, 3],
                [1, 2, 3],
                [2, 4, 6],
            ],
            [
                [1, 2, 3],
                [-2, -2, -4],
                [-1, 0, -1],
            ],
        ];
    }

    /**
     * @test   add exception - size mismatch
     * @throws \Exception
     */
    public function testAddExceptionSizeMismatch()
    {
        // Given
        $A = new Vector([1, 2, 3]);
        $B = new Vector([1, 2]);

        // Then
        $this->expectException(Exception\VectorException::class);

        // When
        $A->add($B);
    }

    /**
     * @test         subtract
     * @dataProvider dataProviderForSubtract
     */
    public function testSubtract(array $A, array $B, array $R)
    {
        // Given
        $A    = new Vector($A);
        $B    = new Vector($B);
        $R    = new Vector($R);

        // When
        $A−B = $A->subtract($B);

        // Then
        $this->assertEquals($R, $A−B);
        $this->assertEquals($R->getVector(), $A−B->getVector());
    }

    public function dataProviderForSubtract(): array
    {
        return [
            [
                [3],
                [2],
                [1],
            ],
            [
                [2, 2, 2],
                [1, 2, 3],
                [1, 0, -1],
            ],
            [
                [2, 2, 2],
                [-1, -2, -3],
                [3, 4, 5],
            ],
        ];
    }

    /**
     * @test   subtract exception - size mismatch
     * @throws \Exception
     */
    public function testSubtractExceptionSizeMismatch()
    {
        // Given
        $A = new Vector([1, 2, 3]);
        $B = new Vector([1, 2]);

        // Then
        $this->expectException(Exception\VectorException::class);

        // When
        $A->subtract($B);
    }

    /**
     * @test         multiply
     * @dataProvider dataProviderForMultiply
     * @param        array $A
     * @param        array $B
     * @param        array $R
     * @throws       \Exception
     */
    public function testMultiply(array $A, array $B, array $R)
    {
        // Given
        $A    = new Vector($A);
        $B    = new Vector($B);
        $R    = new Vector($R);

        // When
        $A×B = $A->multiply($B);

        // Then
        $this->assertEquals($R, $A×B);
        $this->assertEquals($R->getVector(), $A×B->getVector());
    }

    public function dataProviderForMultiply(): array
    {
        return [
            [
                [1],
                [2],
                [2],
            ],
            [
                [1, 2, 3],
                [1, 2, 3],
                [1, 4, 9],
            ],
            [
                [1, 2, 3],
                [-2, -2, -4],
                [-2, -4, -12],
            ],
        ];
    }

    /**
     * @test   Multiply size mismatch
     * @throws \Exception
     */
    public function testMultiplyExceptionSizeMismatch()
    {
        // Given
        $A = new Vector([1, 2, 3]);
        $B = new Vector([1, 2]);

        // Then
        $this->expectException(Exception\VectorException::class);

        // Then
        $A->multiply($B);
    }

    /**
     * @test         divide
     * @dataProvider dataProviderForDivide
     * @param        array $A
     * @param        array $B
     * @param        array $R
     * @throws       \Exception
     */
    public function testDivide(array $A, array $B, array $R)
    {
        // Given
        $A    = new Vector($A);
        $B    = new Vector($B);
        $R    = new Vector($R);

        // When
        $A／B = $A->divide($B);

        // Then
        $this->assertEquals($R, $A／B);
        $this->assertEquals($R->getVector(), $A／B->getVector());
    }

    public function dataProviderForDivide(): array
    {
        return [
            [
                [1],
                [2],
                [1 / 2],
            ],
            [
                [1, 2, 3],
                [1, 2, 3],
                [1, 1, 1],
            ],
            [
                [1, 2, 3],
                [-2, -2, -4],
                [-1 / 2, -1, -3 / 4],
            ],
        ];
    }

    /**
     * @test   Divide size mismatch
     * @throws \Exception
     */
    public function testDivideExceptionSizeMismatch()
    {
        // Given
        $A = new Vector([1, 2, 3]);
        $B = new Vector([1, 2]);

        // Then
        $this->expectException(Exception\VectorException::class);

        // When
        $A->divide($B);
    }

    /**
     * @test         length
     * @dataProvider dataProviderForLength
     */
    public function testLength(array $A, $l²norm)
    {
        // Given
        $A = new Vector($A);

        // When
        $length = $A->length();

        // Then
        $this->assertEqualsWithDelta($l²norm, $length, 0.0001);
    }

    public function dataProviderForLength(): array
    {
        return [
            [ [1, 2, 3], 3.7416573867739413 ],
            [ [7, 5, 5], 9.9498743710662 ],
            [ [3, 3, 3], 5.196152422706632 ],
            [ [2, 2, 2], 3.4641016151377544 ],
            [ [1, 1, 1], 1.7320508075688772 ],
            [ [0, 0, 0], 0 ],
            [ [1, 0, 0], 1 ],
            [ [1, 1, 0], 1.4142135623730951 ],
            [ [-1, 1, 0], 1.4142135623730951 ],
        ];
    }

    /**
     * @test         normalize
     * @dataProvider dataProviderForNormalize
     */
    public function testNormalize(array $A, array $expected)
    {
        // Given
        $A        = new Vector($A);
        $expected = new Vector($expected);

        // When
        $Â = $A->normalize();

        // Then
        $this->assertEqualsWithDelta($expected, $Â, 0.00000001);
        $this->assertEqualsWithDelta($expected->getVector(), $Â->getVector(), 0.00000001);
    }

    public function dataProviderForNormalize(): array
    {
        return [
            [
                [3, 5],
                [0.51449575542753, 0.85749292571254],
            ],
            [
                [3, 1, 2],
                [0.80178372573727, 0.26726124191242, 0.53452248382485],
            ],
        ];
    }

    /**
     * @test         perpendicular
     * @dataProvider dataProviderForPerpendicular
     */
    public function testPerpendicular(array $A, array $expected)
    {
        // Given
        $A        = new Vector($A);
        $expected = new Vector($expected);

        // When
        $A⊥ = $A->perpendicular();

        // Then
        $this->assertEquals($expected, $A⊥);
        $this->assertEquals($expected->getVector(), $A⊥->getVector());
    }

    public function dataProviderForPerpendicular(): array
    {
        return [
            [
                [3, 5],
                [-5, 3],
            ],
            [
                [2, 3],
                [-3, 2],
            ],
        ];
    }

    /**
     * @test   perpendicular exception - n greater than two
     * @throws \Exception
     */
    public function testPerpendicularExceptionNGreaterThanTwo()
    {
        // Given
        $A = new Vector([1, 2, 3]);

        // Then
        $this->expectException(Exception\VectorException::class);

        // When
        $A->perpendicular();
    }

    /**
     * @test         perp dot product
     * @dataProvider dataProviderForPerpDotProduct
     */
    public function testPerpDotProduct(array $A, array $B, $expected)
    {
        // Given
        $A = new Vector($A);
        $B = new Vector($B);

        // When
        $A⊥⋅B = $A->perpDotProduct($B);

        // Then
        $this->assertEquals($expected, $A⊥⋅B);
    }

    public function dataProviderForPerpDotProduct(): array
    {
        return [
            [
                [3, -2],
                [1, 2],
                8,
            ],
            [
                [2, 0],
                [-1, 3],
                6
            ],
        ];
    }

    /**
     * @test   perp dot product exception - n not both two
     * @throws \Exception
     */
    public function testPerpDotProductExceptionNNotBothTwo()
    {
        // Given
        $A = new Vector([1, 2, 3]);
        $B = new Vector([1, 2, 3]);

        // Then
        $this->expectException(Exception\VectorException::class);

        // When
        $A->perpDotProduct($B);
    }

    /**
     * @test         projection
     * @dataProvider dataProviderForProjection
     */
    public function testProjection(array $A, array $B, array $expected)
    {
        // Given
        $A        = new Vector($A);
        $B        = new Vector($B);
        $expected = new Vector($expected);

        // When
        $projₐb   = $A->projection($B);

        // Then
        $this->assertEquals($expected, $projₐb);
        $this->assertEquals($expected->getVector(), $projₐb->getVector());
    }

    public function dataProviderForProjection(): array
    {
        return [
            [
                [2, 4],
                [5, 3],
                [3.2352941176468, 1.94117647058808],
            ],
            [
                [1, 2, 3],
                [4, 5, 6],
                [128 / 77, 160 / 77, 192 / 77],
            ],
            [
                [4, 5, 6],
                [1, 2, 3],
                [16 / 7, 32 / 7, 48 / 7],
            ],
            [
                [2, 9, -4],
                [-1, 5, 5],
                [-23 / 51, 115 / 51, 115 / 51],
            ],
            [
                [1, 1, 1],
                [1, 1, 1],
                [1, 1, 1],
            ],
            [
                [1, 1, 1],
                [2, 2, 2],
                [1, 1, 1],
            ],
            [
                [2, 2, 2],
                [1, 1, 1],
                [2, 2, 2],
            ],
            [
                [1, 2, 1],
                [2, 1, 2],
                [4 / 3, 2 / 3, 4 / 3],
            ],
        ];
    }

    /**
     * @test         perp
     * @dataProvider dataProviderForPerp
     */
    public function testPerp(array $A, array $B, array $expected)
    {
        // Given
        $A        = new Vector($A);
        $B        = new Vector($B);
        $expected = new Vector($expected);

        // When
        $perpₐb = $A->perp($B);

        // Then
        $this->assertEquals($expected, $perpₐb);
        $this->assertEquals($expected->getVector(), $perpₐb->getVector());
    }

    public function dataProviderForPerp(): array
    {
        return [
            [
                [2, 4],
                [5, 3],
                [-1.23529411764, 2.0588235294],
            ],
        ];
    }

    /**
     * @test         direct product
     * @dataProvider dataProviderForDirectProduct
     */
    public function testDirectProduct(array $A, array $B, array $expected)
    {
        // Given
        $A        = new Vector($A);
        $B        = new Vector($B);
        $expected = new NumericMatrix($expected);

        // When
        $AB = $A->directProduct($B);

        // Then
        $this->assertEquals($expected->getMatrix(), $AB->getMatrix());
    }

    public function dataProviderForDirectProduct(): array
    {
        return [
            [
                [1],
                [2],
                [
                    [2],
                ],
            ],
            [
                [1, 2],
                [2, 3],
                [
                    [2, 3],
                    [4, 6],
                ],
            ],
            [
                [1, 2, 3],
                [2, 3, 4],
                [
                    [2, 3, 4],
                    [4, 6, 8],
                    [6, 9, 12],
                ],
            ],
            [
                [1, 2, 3, 4],
                [2, 3, 4, 5],
                [
                    [2, 3, 4, 5],
                    [4, 6, 8, 10],
                    [6, 9, 12, 15],
                    [8, 12, 16, 20],
                ],
            ],
            [
                [3, 2, 6, 4],
                [4, 5, 1, 7],
                [
                    [12, 15, 3, 21],
                    [8, 10, 2, 14],
                    [24, 30, 6, 42],
                    [16, 20, 4, 28],
                ],
            ],
            [
                [1, 2],
                [3, 4, 5],
                [
                    [3, 4, 5],
                    [6, 8, 10],
                ],
            ],
            [
                [3, 4, 5],
                [1, 2],
                [
                    [3, 6],
                    [4, 8],
                    [5, 10],
                ],
            ],
        ];
    }

    /**
     * @test         kroneckerProduct returns the expected Vector
     * @dataProvider dataProviderForKroneckerProduct
     * @param        array $A
     * @param        array $B
     * @param        array $expected
     */
    public function testKroneckerProduct(array $A, array $B, array $expected)
    {
        // Given
        $A = new Vector($A);
        $B = new Vector($B);
        $R = new Vector($expected);

        // When
        $A⨂B = $A->kroneckerProduct($B);

        // Then
        $this->assertEquals($R, $A⨂B);
    }

    public function dataProviderForKroneckerProduct(): array
    {
        return [
            [
                [1],
                [1],
                [1],
            ],
            [
                [2],
                [3],
                [6],
            ],
            [
                [1, 2],
                [3, 4],
                [3, 4, 6, 8],
            ],
            [
                [4, 6],
                [3, 9],
                [12, 36, 18, 54],
            ],
            [
                [1, 2, 3],
                [4, 5, 6],
                [4, 5, 6, 8, 10, 12, 12, 15, 18],
            ],
            [
                [5, 3, 9, 8],
                [1, 6, 5, 12],
                [5, 30, 25, 60, 3, 18, 15, 36, 9, 54, 45, 108, 8, 48, 40, 96],
            ],
        ];
    }

    /**
     * @test         max
     * @dataProvider dataProviderForMax
     * @param array  $A
     * @param number $expected
     */
    public function testMax(array $A, $expected)
    {
        // Given
        $A = new Vector($A);

        // When
        $max = $A->max();

        // Then
        $this->assertEquals($expected, $max);
    }

    /**
     * @return array
     */
    public function dataProviderForMax(): array
    {
        return [
            [
                [0],
                0,
            ],
            [
                [1],
                1,
            ],
            [
                [-1],
                -1,
            ],
            [
                [0, 1, 2, 3],
                3,
            ],
            [
                [3, 2, 1, 0],
                3,
            ],
            [
                [0, 1, 2, 3, -4, 55, -66],
                55,
            ],
            [
                [0.0, 1.1, 2.2, 3.3],
                3.3,
            ],
        ];
    }

    /**
     * @test         min
     * @dataProvider dataProviderForMin
     * @param array  $A
     * @param number $expected
     */
    public function testMin(array $A, $expected)
    {
        // Given
        $A = new Vector($A);

        // When
        $min = $A->min();

        // Then
        $this->assertEquals($expected, $min);
    }

    /**
     * @return array
     */
    public function dataProviderForMin(): array
    {
        return [
            [
                [0],
                0,
            ],
            [
                [1],
                1,
            ],
            [
                [-1],
                -1,
            ],
            [
                [0, 1, 2, 3],
                0,
            ],
            [
                [3, 2, 1, 0],
                0,
            ],
            [
                [0, 1, 2, 3, -4, 55, -66],
                -66,
            ],
            [
                [1.1, 2.2, 3.3],
                1.1,
            ],
        ];
    }
}
