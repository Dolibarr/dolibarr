<?php

namespace MathPHP\Tests\LinearAlgebra\Matrix\Other;

use MathPHP\Exception\OutOfBoundsException;
use MathPHP\LinearAlgebra\MatrixFactory;

class GivensMatrixTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test    Test that the construction fails when parameters are out of bounds
     * @throws \Exception
     */
    public function testException()
    {
        // Given
        $m     = 2;
        $n     = 3;
        $angle = \M_PI;
        $size  = 2;

        // Then
        $this->expectException(OutOfBoundsException::class);

        // When
        $matrix = MatrixFactory::givens($m, $n, $angle, $size);
    }

    /**
     * @test         Test that the function returns a properly formatted Matrix
     * @dataProvider dataProviderForTestGivensMatrix
     * @param        int $m
     * @param        int $n
     * @param        float $angle
     * @param        int $size
     * @param        array $expected
     * @throws       \Exception
     */
    public function testGivensMatrix(int $m, int $n, float $angle, int $size, array $expected)
    {
        // When
        $G = MatrixFactory::givens($m, $n, $angle, $size);

        // Then
        $this->assertEquals($expected, $G->getMatrix());
    }

    /**
     * @return array
     */
    public function dataProviderForTestGivensMatrix(): array
    {
        return [
            [
                0, 1, \M_PI, 2,
                [[-1, 0],
                 [0, -1]]
            ],
            [
                0, 2, \M_PI / 4, 3,
                [[\M_SQRT1_2, 0,  -1 * \M_SQRT1_2],
                 [0,          1,  0],
                 [\M_SQRT1_2, 0, \M_SQRT1_2]],
            ],
        ];
    }
}
