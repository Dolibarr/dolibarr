<?php

namespace MathPHP\Tests\Probability\Distribution\Table;

use MathPHP\Probability\Distribution\Table\TDistribution;
use MathPHP\Exception;

class TDistributionTableTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test         one-sided T value from confidence level
     * @dataProvider dataProviderForOneSidedCL
     * @param        mixed $ν
     * @param        mixed $cl
     * @param        float  $t
     * @throws       \Exception
     */
    public function testGetOneSidedTValueFromConfidenceLevel($ν, $cl, float $t)
    {
        // When
        $value = TDistribution::getOneSidedTValueFromConfidenceLevel($ν, $cl);

        // Then
        $this->assertEquals($t, $value);
    }

    public function dataProviderForOneSidedCL(): array
    {
        return [
            [ 1, 0, 0 ],
            [ 1, 75, 1.000 ],
            [ 1, 80, 1.376 ],
            [ 1, 97.5, 12.71 ],
            [ 1, 99.9, 318.3 ],
            [ 1, 99.95, 636.6 ],
            [ 10, 0, 0 ],
            [ 10, 75, 0.700 ],
            [ 10, 80, 0.879 ],
            [ 10, 95, 1.812 ],
            [ 10, 99.5, 3.169 ],
            [ 10, 99.95, 4.587 ],
            [ 'infinity', 0, 0 ],
            [ 'infinity', 75, 0.674 ],
            [ 'infinity', 97.5, 1.960 ],
            [ 'infinity', 99.95, 3.291 ],
        ];
    }

    /**
     * @test         two-sided T value from confidence level
     * @dataProvider dataProviderForTwoSidedCL
     * @param        mixed $ν
     * @param        mixed $cl
     * @param        float  $t
     * @throws       \Exception
     */
    public function testGetTwoSidedTValueFromConfidenceLevel($ν, $cl, float $t)
    {
        // When
        $value = TDistribution::getTwoSidedTValueFromConfidenceLevel($ν, $cl);

        // Then
        $this->assertEquals($t, $value);
    }

    public function dataProviderForTwoSidedCL(): array
    {
        return [
            [ 1, 0, 0 ],
            [ 1, 50, 1.000 ],
            [ 1, 60, 1.376 ],
            [ 1, 95, 12.71 ],
            [ 1, 99.8, 318.3 ],
            [ 1, 99.9, 636.6 ],
            [ 10, 0, 0 ],
            [ 10, 50, 0.700 ],
            [ 10, 60, 0.879 ],
            [ 10, 90, 1.812 ],
            [ 10, 99, 3.169 ],
            [ 10, 99.9, 4.587 ],
            [ 'infinity', 0, 0 ],
            [ 'infinity', 50, 0.674 ],
            [ 'infinity', 95, 1.960 ],
            [ 'infinity', 99.9, 3.291 ],
        ];
    }

    /**
     * @test          one-sided value from alpha
     * @dataProvider  dataProviderForOneSidedAlpha
     * @param         mixed $ν
     * @param         mixed $α
     * @param         float  $t
     * @throws        \Exception
     */
    public function testGetOneSidedTValueFromAlpha($ν, $α, float $t)
    {
        // When
        $value = TDistribution::getOneSidedTValueFromAlpha($ν, $α);

        // Then
        $this->assertEquals($t, $value);
    }

    public function dataProviderForOneSidedAlpha(): array
    {
        return [
            [ 1, '0.50', 0 ],
            [ 1, 0.25, 1.000 ],
            [ 1, '0.20', 1.376 ],
            [ 1, 0.025, 12.71 ],
            [ 1, 0.001, 318.3 ],
            [ 1, 0.0005, 636.6 ],
            [ 10, '0.50', 0 ],
            [ 10, 0.25, 0.700 ],
            [ 10, '0.20', 0.879 ],
            [ 10, 0.05, 1.812 ],
            [ 10, 0.005, 3.169 ],
            [ 10, 0.0005, 4.587 ],
            [ 'infinity', '0.50', 0 ],
            [ 'infinity', 0.25, 0.674 ],
            [ 'infinity', 0.025, 1.960 ],
            [ 'infinity', 0.0005, 3.291 ],
        ];
    }

    /**
     * @test         two-sided value from alpha
     * @dataProvider dataProviderForTwoSidedAlpha
     * @param        mixed $ν
     * @param        mixed $α
     * @param        float  $t
     * @throws       \Exception
     */
    public function testGetTwoSidedTValueFromAlpha($ν, $α, float $t)
    {
        // When
        $value = TDistribution::getTwoSidedTValueFromAlpha($ν, $α);

        // Then
        $this->assertEquals($t, $value);
    }

    public function dataProviderForTwoSidedAlpha(): array
    {
        return [
            [ 1, '1.00', 0 ],
            [ 1, '0.50', 1.000 ],
            [ 1, '0.40', 1.376 ],
            [ 1, 0.05, 12.71 ],
            [ 1, 0.002, 318.3 ],
            [ 1, 0.001, 636.6 ],
            [ 10, '1.00', 0 ],
            [ 10, '0.50', 0.700 ],
            [ 10, '0.40', 0.879 ],
            [ 10, '0.10', 1.812 ],
            [ 10, 0.01, 3.169 ],
            [ 10, 0.001, 4.587 ],
            [ 'infinity', '1.00', 0 ],
            [ 'infinity', '0.50', 0.674 ],
            [ 'infinity', 0.05, 1.960 ],
            [ 'infinity', 0.001, 3.291 ],
        ];
    }

    /**
     * @test
     * @throws \Exception
     */
    public function testGetOneSidedTValueFromConfidenceLevelExceptionBadDF()
    {
        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        TDistribution::getOneSidedTValueFromConfidenceLevel(1234, 99);
    }

    /**
     * @test
     * @throws \Exception
     */
    public function testGetTwoSidedTValueFromConfidenceLevelExceptionBadDF()
    {
        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        TDistribution::getTwoSidedTValueFromConfidenceLevel(1234, 99);
    }

    /**
     * @test
     * @throws \Exception
     */
    public function testGetOneSidedTValueFromAlphaExceptionBadDF()
    {
        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        TDistribution::getOneSidedTValueFromAlpha(1234, 0.05);
    }

    /**
     * @test
     * @throws \Exception
     */
    public function testGetTwoSidedTValueFromAlphaExceptionBadDF()
    {
        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        TDistribution::getTwoSidedTValueFromAlpha(1234, 0.05);
    }

    /**
     * @test
     * @throws \Exception
     */
    public function testGetOneSidedTValueFromConfidenceLevelExceptionBadCL()
    {
        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        TDistribution::getOneSidedTValueFromConfidenceLevel(1, 155);
    }

    /**
     * @test
     * @throws \Exception
     */
    public function testGetTwoSidedTValueFromConfidenceLevelExceptionBadCL()
    {
        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        TDistribution::getTwoSidedTValueFromConfidenceLevel(1, 155);
    }

    /**
     * @test
     * @throws \Exception
     */
    public function testGetOneSidedTValueFromAlphaExceptionBadAlpha()
    {
        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        TDistribution::getOneSidedTValueFromAlpha(1, 999);
    }

    /**
     * @test
     * @throws \Exception
     */
    public function testGetTwoSidedTValueFromAlphaExceptionBadAlpha()
    {
        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        TDistribution::getTwoSidedTValueFromAlpha(1, 999);
    }
}
