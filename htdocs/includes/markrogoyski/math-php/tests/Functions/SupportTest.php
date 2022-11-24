<?php

namespace MathPHP\Tests\Functions;

use MathPHP\Functions\Support;
use MathPHP\Exception;

class SupportTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test         checkLimits on lower limit
     * @dataProvider dataProviderForCheckLimitsLowerLimit
     * @param        array $limits
     * @param        array $params
     * @throws       \Exception
     */
    public function testCheckLimitsLowerLimit(array $limits, array $params)
    {
        // When
        $withinLimits = Support::checkLimits($limits, $params);

        // Then
        $this->assertTrue($withinLimits);
    }

    /**
     * @return array
     */
    public function dataProviderForCheckLimitsLowerLimit(): array
    {
        return [
            [
                ['x' => '[0,∞]'],
                ['x' => 0],
            ],
            [
                ['x' => '[0,∞]'],
                ['x' => 0.1],
            ],
            [
                ['x' => '[0,∞]'],
                ['x' => 1],
            ],
            [
                ['x' => '[0,∞]'],
                ['x' => 4934],
            ],
            [
                ['x' => '(0,∞]'],
                ['x' => 0.1],
            ],
            [
                ['x' => '(0,∞]'],
                ['x' => 1],
            ],
            [
                ['x' => '(0,∞]'],
                ['x' => 4934],
            ],
            [
                ['x' => '[-50,∞]'],
                ['x' => -50],
            ],
            [
                ['x' => '(-50,∞]'],
                ['x' => -49],
            ],
            [
                ['x' => '[-∞,10]'],
                ['x' => -89379837],
            ],
            [
                ['x' => '(-∞,10]'],
                ['x' => -95893223452],
            ],
            [
                ['x' => '[0,∞]', 'y' => '[0,∞]'],
                ['x' => 0, 'y' => 5],
            ],
            [
                ['x' => '[0,∞]', 'y' => '[0,∞]', 'z' => '[0,1]'],
                ['x' => 0, 'y' => 5],
            ],
        ];
    }

    /**
     * @test         checkLimits out of bounds
     * @dataProvider dataProviderForCheckLimitsLowerLimitException
     * @param        array $limits
     * @param        array $params
     * @throws       \Exception
     */
    public function testCheckLimitsLowerLimitException(array $limits, array $params)
    {
        // Then
        $this->expectException(Exception\OutOfBoundsException::class);

        // When
        Support::checkLimits($limits, $params);
    }

    /**
     * @return array
     */
    public function dataProviderForCheckLimitsLowerLimitException(): array
    {
        return [
            [
                ['x' => '[0,∞]'],
                ['x' => -1],
            ],
            [
                ['x' => '[0,∞]'],
                ['x' => -4],
            ],
            [
                ['x' => '[5,∞]'],
                ['x' => 4],
            ],
            [
                ['x' => '(0,∞]'],
                ['x' => -1],
            ],
            [
                ['x' => '(0,∞]'],
                ['x' => -4],
            ],
            [
                ['x' => '(5,∞]'],
                ['x' => 4],
            ],
        ];
    }

    /**
     * @test         checkLimits on upper limit
     * @dataProvider dataProviderForCheckLimitsUpperLimit
     * @param        array $limits
     * @param        array $params
     * @throws       \Exception
     */
    public function testCheckLimitsUpperLimit(array $limits, array $params)
    {
        // When
        $withinLimits = Support::checkLimits($limits, $params);

        // Then
        $this->assertTrue($withinLimits);
    }

    /**
     * @return array
     */
    public function dataProviderForCheckLimitsUpperLimit(): array
    {
        return [
            [
                ['x' => '[0,5]'],
                ['x' => 0],
            ],
            [
                ['x' => '[0,5]'],
                ['x' => 3],
            ],
            [
                ['x' => '[0,5]'],
                ['x' => 5],
            ],
            [
                ['x' => '[0,5)'],
                ['x' => 0],
            ],
            [
                ['x' => '[0,5)'],
                ['x' => 3],
            ],
            [
                ['x' => '[0,5)'],
                ['x' => 4.999],
            ],
            [
                ['x' => '[0,∞]'],
                ['x' => 9489859893],
            ],
            [
                ['x' => '[0,∞)'],
                ['x' => 9489859893],
            ],
            [
                ['x' => '[0,5]', 'y' => '[0,5]'],
                ['x' => 0],
            ],
            [
                ['x' => '[0,5]', 'y' => '[0,5]'],
                ['x' => 0, 'y' => 3],
            ],
            [
                ['x' => '[0,5]', 'y' => '[0,5]', 'z' => '[0,5]'],
                ['x' => 0, 'y' => 3],
            ],
        ];
    }

    /**
     * @test         checkLimits out of bounds
     * @dataProvider dataProviderForCheckLimitsUpperLimitException
     * @param        array $limits
     * @param        array $params
     * @throws       \Exception
     */
    public function testCheckLimitsUpperLimitException(array $limits, array $params)
    {
        // Then
        $this->expectException(Exception\OutOfBoundsException::class);

        // When
        Support::checkLimits($limits, $params);
    }

    /**
     * @return array
     */
    public function dataProviderForCheckLimitsUpperLimitException(): array
    {
        return [
            [
                ['x' => '[0,5]'],
                ['x' => 5.001],
            ],
            [
                ['x' => '[0,5]'],
                ['x' => 6],
            ],
            [
                ['x' => '[0,5]'],
                ['x' => 98349389],
            ],
            [
                ['x' => '[0,5)'],
                ['x' => 5],
            ],
            [
                ['x' => '[0,5)'],
                ['x' => 5.1],
            ],
            [
                ['x' => '[0,5)'],
                ['x' => 857385738],
            ],
        ];
    }

    /**
     * @test   checkLimits bad data
     * @throws \Exception
     */
    public function testCheckLimitsLowerLimitEndpointException()
    {
        // Given
        $limits = ['x' => '{0,1)'];
        $params = ['x' => 0.5];

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        Support::checkLimits($limits, $params);
    }

    /**
     * @test   checkLimits bad data
     * @throws \Exception
     */
    public function testCheckLimitsUpperLimitEndpointException()
    {
        // Given
        $limits = ['x' => '(0,1}'];
        $params = ['x' => 0.5];

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        Support::checkLimits($limits, $params);
    }

    /**
     * @test         checkLimits bad parameter
     * @dataProvider dataProviderForCheckLimitsUndefinedParameterException
     * @param        array $limits
     * @param        array $params
     * @throws       \Exception
     */
    public function testCheckLimitsUndefinedParameterException(array $limits, array $params)
    {
        // Then
        $this->expectException(Exception\BadParameterException::class);

        // When
        Support::checkLimits($limits, $params);
    }

    /**
     * @return array
     */
    public function dataProviderForCheckLimitsUndefinedParameterException(): array
    {
        return [
            [
                ['x' => '[0,1]'],
                ['y' => 0.5],
            ],
            [
                ['x' => '[0,1]', 'a' => '[0,10]'],
                ['y' => 0.5],
            ],
            [
                ['x' => '[0,1]', 'a' => '[0,10]'],
                ['x' => 0.5, 'b' => 4],
            ],
            [
                ['x' => '[0,1]', 'a' => '[0,10]'],
                ['x' => 0.5, 'a' => 4, 'z' => 9],
            ],
        ];
    }

    /**
     * @test         isZero returns true for infinitesimal quantities less than the defined epsilon
     * @dataProvider dataProviderForZero
     * @param        float $x
     */
    public function testIsZeroTrue(float $x)
    {
        // When
        $isZero = Support::isZero($x);

        // Then
        $this->assertTrue($isZero);
    }

    /**
     * @test         isZero returns false for infinitesimal quantities greater than the defined epsilon
     * @dataProvider dataProviderForNotZero
     * @param        float $x
     */
    public function testIsZeroFalse(float $x)
    {
        // When
        $isZero = Support::isZero($x);

        // Then
        $this->assertFalse($isZero);
    }

    /**
     * @test         isNotZero returns true for infinitesimal quantities greater than the defined epsilon
     * @dataProvider dataProviderForNotZero
     * @param        float $x
     */
    public function testIsNotZeroTrue(float $x)
    {
        // When
        $isNotZero = Support::isNotZero($x);

        // Then
        $this->assertTrue($isNotZero);
    }

    /**
     * @test         isNotZero returns false for infinitesimal quantities less than the defined epsilon
     * @dataProvider dataProviderForZero
     * @param        float $x
     */
    public function testIsNotZeroFalse(float $x)
    {
        // When
        $isNotZero = Support::isNotZero($x);

        // Then
        $this->assertFalse($isNotZero);
    }

    /**
     * @test isZero is true when setting a specific tolerance
     */
    public function testIsZeroWithinTolerance()
    {
        // Given
        $x = 0.00000001;
        $ε = 0.00000001;

        // When
        $isZero = Support::isZero($x, $ε);

        // Then
        $this->assertTrue($isZero);
    }

    /**
     * @test isZero is false when setting a specific tolerance
     */
    public function testIsZeroOutsideOfTolerance()
    {
        // Given
        $x = 0.00000002;
        $ε = 0.00000001;

        // When
        $isZero = Support::isZero($x, $ε);

        // Then
        $this->assertFalse($isZero);
    }

    /**
     * @test isNotZero is true when setting a specific tolerance
     */
    public function testIsNotZeroWithinTolerance()
    {
        // Given
        $x = 0.00000002;
        $ε = 0.00000001;

        // When
        $isZero = Support::isNotZero($x, $ε);

        // Then
        $this->assertTrue($isZero);
    }

    /**
     * @test isNotZero is false when setting a specific tolerance
     */
    public function testIsNotZeroOutsideOfTolerance()
    {
        // Given
        $x = 0.00000001;
        $ε = 0.00000001;

        // When
        $isZero = Support::isNotZero($x, $ε);

        // Then
        $this->assertFalse($isZero);
    }

    /**
     * @return array
     */
    public function dataProviderForZero(): array
    {
        return [
            [0],
            [0.0],
            [0.00],
            [0.000000000000000000000000000000],
            [0.000000000000001],
            [0.0000000000000001],
            [0.00000000000000001],
            [0.000000000000000001],
            [0.0000000000000000001],
            [0.00000000000000000001],
            [0.000000000000000000001],
            [0.0000000000000000000001],
            [0.00000000000000000000001],
            [0.000000000000000000000001],
            [-0],
            [-0.0],
            [-0.00],
            [-0.000000000000000000000000000000],
            [-0.000000000000001],
            [-0.0000000000000001],
            [-0.00000000000000001],
            [-0.000000000000000001],
            [-0.0000000000000000001],
            [-0.00000000000000000001],
            [-0.000000000000000000001],
            [-0.0000000000000000000001],
            [-0.00000000000000000000001],
            [-0.000000000000000000000001],
        ];
    }

    public function dataProviderForNotZero(): array
    {
        return [
            [1],
            [1.0],
            [1.00],
            [1.000000000000000000000000000000],
            [0.00000000002],
            [0.0000000001],
            [0.000000001],
            [0.00000001],
            [0.0000001],
            [0.000001],
            [-1],
            [-1.0],
            [-1.00],
            [-1.000000000000000000000000000000],
            [-0.00000000002],
            [-0.0000000001],
            [-0.000000001],
            [-0.00000001],
            [-0.0000001],
            [-0.000001],
        ];
    }

    /**
     * @test         isEqual returns true for equal values
     * @dataProvider dataProviderForEqualValues
     * @param        int|float $x
     * @param        int|float $y
     */
    public function testIsEqual($x, $y)
    {
        // When
        $isEqual = Support::isEqual($x, $y);

        // Then
        $this->assertTrue($isEqual);
    }

    /**
     * @test         isEqual returns false for unequal values
     * @dataProvider dataProviderForUnequalValues
     * @param        int|float $x
     * @param        int|float $y
     */
    public function testIsEqualWhenNotEqual($x, $y)
    {
        // When
        $isEqual = Support::isEqual($x, $y);

        // Then
        $this->assertFalse($isEqual);
    }

    /**
     * @test         isNotEqual returns true for unequal values
     * @dataProvider dataProviderForUnequalValues
     * @param        int|float $x
     * @param        int|float $y
     */
    public function testIsNotEqual($x, $y)
    {
        // When
        $isNotEqual = Support::isNotEqual($x, $y);

        // Then
        $this->assertTrue($isNotEqual);
    }

    /**
     * @test         isNotEqual returns false for equal values
     * @dataProvider dataProviderForEqualValues
     * @param        int|float $x
     * @param        int|float $y
     */
    public function testIsNotEqualWhenEqual($x, $y)
    {
        // When
        $isNotEqual = Support::isNotEqual($x, $y);

        // Then
        $this->assertFalse($isNotEqual);
    }

    /**
     * @test isEqual is true when setting a specific tolerance
     */
    public function testIsEqualWithinTolerance()
    {
        // Given
        $x = 1.000001;
        $y = 1.000002;
        $ε = 0.000002;

        // When
        $isEqual = Support::isEqual($x, $y, $ε);

        // Then
        $this->assertTrue($isEqual);
    }

    /**
     * @test isEqual is false when setting a specific tolerance
     */
    public function testIsEqualOutsideOfTolerance()
    {
        // Given
        $x = 1.000001;
        $y = 1.000002;
        $ε = 0.0000009;

        // When
        $isEqual = Support::isEqual($x, $y, $ε);

        // Then
        $this->assertFalse($isEqual);
    }

    /**
     * @test isNotEqual is true when setting a specific tolerance
     */
    public function testIsNotEqualWithinTolerance()
    {
        // Given
        $x = 1.000001;
        $y = 1.000002;
        $ε = 0.000001;

        // When
        $isEqual = Support::isNotEqual($x, $y, $ε);

        // Then
        $this->assertTrue($isEqual);
    }

    /**
     * @test isNotEqual is false when setting a specific tolerance
     */
    public function testIsNotEqualOutsideOfTolerance()
    {
        // Given
        $x = 1.000001;
        $y = 1.000002;
        $ε = 0.000002;

        // When
        $isEqual = Support::isNotEqual($x, $y, $ε);

        // Then
        $this->assertFalse($isEqual);
    }

    /**
     * @return array
     */
    public function dataProviderForEqualValues(): array
    {
        return [
            [0, 0],
            [1, 1],
            [2, 2],
            [489837, 489837],
            [-1, -1],
            [-2, -2],
            [-489837, -489837],
            [1.1, 1.1],
            [4.86, 4.86],
            [4.4948739874, 4.4948739874],
            [-1.1, -1.1],
            [-4.86, -4.86],
            [-4.4948739874, -4.4948739874],
            [0.01, 0.01],
            [0.001, 0.001],
            [0.0001, 0.0001],
            [0.00001, 0.00001],
            [0.000001, 0.000001],
            [0.0000001, 0.0000001],
            [0.00000001, 0.00000001],
            [0.000000001, 0.000000001],
            [0.0000000001, 0.0000000001],
            [0.00000000001, 0.00000000001],
            [0.000000000001, 0.000000000001],
            [-0.01, -0.01],
            [-0.001, -0.001],
            [-0.0001, -0.0001],
            [-0.00001, -0.00001],
            [-0.000001, -0.000001],
            [-0.0000001, -0.0000001],
            [-0.00000001, -0.00000001],
            [-0.000000001, -0.000000001],
            [-0.0000000001, -0.0000000001],
            [-0.00000000001, -0.00000000001],
            [-0.000000000001, -0.000000000001],
        ];
    }

    /**
     * @return array
     */
    public function dataProviderForUnequalValues(): array
    {
        return [
            [0, 1],
            [1, 2],
            [2, 3],
            [489838, 489837],
            [-1, -2],
            [-2, -3],
            [-489838, -489837],
            [1.1, 1.2],
            [4.86, 4.87],
            [4.4948739876, 4.4948739874],
            [-1.1, -1.2],
            [-4.86, -4.87],
            [-4.4948739873, -4.4948739874],
            [0.01, 0.02],
            [0.001, 0.002],
            [0.0001, 0.0002],
            [0.00001, 0.00002],
            [0.000001, 0.000002],
            [0.0000001, 0.0000002],
            [0.00000001, 0.00000002],
            [0.000000001, 0.000000002],
            [0.0000000001, 0.0000000002],
            [0.00000000001, 0.00000000002],
            [0.00000000002, 0.00000000003],
            [-0.01, -0.02],
            [-0.001, -0.002],
            [-0.0001, -0.0002],
            [-0.00001, -0.00002],
            [-0.000001, -0.000002],
            [-0.0000001, -0.0000002],
            [-0.00000001, -0.00000002],
            [-0.000000001, -0.000000002],
            [-0.0000000001, -0.0000000002],
            [-0.00000000001, -0.00000000002],
            [-0.00000000002, -0.00000000003],
        ];
    }
}
