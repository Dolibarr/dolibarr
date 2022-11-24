<?php

namespace MathPHP\Tests;

use MathPHP\Trigonometry;

class TrigonometryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test         unitCircle returns points on a unit circle.
     * @dataProvider dataProviderForUnitCircle
     * @param        int   $points
     * @param        array $expected
     */
    public function testUnitCircle(int $points, array $expected)
    {
        // When
        $unitCircle = Trigonometry::unitCircle($points);

        // Then
        $this->assertEquals($expected, $unitCircle);
    }

    /**
     * @return array
     */
    public function dataProviderForUnitCircle(): array
    {
        return [
            [5, [[1, 0], [0, 1], [-1, 0], [0, -1], [1, 0]]],
            [9, [[1, 0], [\M_SQRT1_2, \M_SQRT1_2], [0, 1], [-\M_SQRT1_2, \M_SQRT1_2], [-1, 0], [-\M_SQRT1_2, -\M_SQRT1_2], [0, -1], [\M_SQRT1_2, -\M_SQRT1_2], [1, 0]]],
        ];
    }
}
