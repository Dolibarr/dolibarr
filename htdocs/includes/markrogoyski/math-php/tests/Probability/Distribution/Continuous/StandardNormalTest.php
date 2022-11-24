<?php

namespace MathPHP\Tests\Probability\Distribution\Continuous;

use MathPHP\Probability\Distribution\Continuous\StandardNormal;
use MathPHP\Probability\Distribution\Continuous\Normal;

class StandardNormalTest extends \PHPUnit\Framework\TestCase
{
    /** @var StandardNormal */
    private $standardNormal;

    private const μ = 0;
    private const σ = 1;

    public function setUp(): void
    {
        // Given
        $this->standardNormal = new StandardNormal();
    }

    /**
     * @test         pdf
     * @dataProvider dataProviderForPdf
     * @param        float $z
     * @param        float $expected_pdf
     */
    public function testPdf(float $z, float $expected_pdf)
    {
        // When
        $pdf = $this->standardNormal->pdf($z);

        // Then
        $this->assertEqualsWithDelta($expected_pdf, $pdf, 0.0000001);
    }

    /**
     * @test         pdf is the same as normal pdf with μ = 0 and σ = 1
     * @dataProvider dataProviderForPdf
     * @param        float $z
     */
    public function testPdfEqualsNormalWithMeanZeroAndStandardDeviationOne(float $z)
    {
        // Given
        $normal     = new Normal(self::μ, self::σ);
        $normal_pdf = $normal->pdf($z);

        // When
        $pdf = $this->standardNormal->pdf($z);

        // Then
        $this->assertEqualsWithDelta($normal_pdf, $pdf, 0.0000001);
    }

    /**
     * @return array [z, pdf]
     */
    public function dataProviderForPdf(): array
    {
        return [
            [10, 0],
            [6, 1e-8],
            [5, 0.00000149],
            [4, 0.00013383],
            [3, 0.00443185],
            [2, 0.05399097],
            [1.96, 0.05844094],
            [1.5, 0.1295176],
            [1.1, 0.21785218],
            [1, 0.24197072],
            [0.9, 0.26608525],
            [0.8, 0.28969155],
            [0.7, 0.31225393],
            [0.6, 0.3332246],
            [0.5, 0.35206533],
            [0.4, 0.36827014],
            [0.3, 0.38138782],
            [0.2, 0.39104269],
            [0.1, 0.39695255],
            [0, 0.39894228],
            [-0.1, 0.39695255],
            [-0.5, 0.35206533],
            [-1, 0.24197072],
            [-1.96, 0.05844094],
            [-2, 0.05399097],
            [-3, 0.00443185],
            [-4, 0.00013383],
            [-5, 0.00000149],
            [-6, 1e-8],
            [-10, 0],
        ];
    }

    /**
     * @test         cdf
     * @dataProvider dataProviderForCdf
     * @param        float $z
     * @param        float $expected_cdf
     */
    public function testCdf(float $z, float $expected_cdf)
    {
        // When
        $cdf = $this->standardNormal->cdf($z);

        // Then
        $this->assertEqualsWithDelta($expected_cdf, $cdf, 0.0000001);
    }

    /**
     * @test         cdf is the same as normal cdf with μ = 0 and σ = 1
     * @dataProvider dataProviderForCdf
     * @param        float $z
     */
    public function testCdfEqualsNormalWithMeanZeroAndStandardDeviationOne(float $z)
    {
        // Given
        $normal = new Normal(0, 1);
        $normal_cdf = $normal->cdf($z);

        // When
        $cdf = $this->standardNormal->cdf($z);

        // Then
        $this->assertEqualsWithDelta($normal_cdf, $cdf, 0.0000001);
    }

    /**
     * @return array [z, cdf]
     */
    public function dataProviderForCdf(): array
    {
        return [
            [10, 1],
            [6, 1],
            [5, 0.99999971],
            [4, 0.99996833],
            [3, 0.9986501],
            [2, 0.97724987],
            [1.96, 0.9750021],
            [1.5, 0.9331928],
            [1.1, 0.86433394],
            [1, 0.84134475],
            [0.9, 0.81593987],
            [0.8, 0.7881446],
            [0.7, 0.75803635],
            [0.6, 0.72574688],
            [0.5, 0.69146246],
            [0.4, 0.65542174],
            [0.31, 0.62171952],
            [0.3, 0.61791142],
            [0.2, 0.57925971],
            [0.1, 0.53982784],
            [0.01, 0.50398936],
            [0.02, 0.50797831],
            [0, 0.5],
            [-0.1, 0.46017216],
            [-0.31, 0.37828048],
            [-0.39, 0.34826827],
            [-0.5, 0.30853754],
            [-1, 0.15865525],
            [-1.96, 0.0249979],
            [-2, 0.02275013],
            [-2.90, 0.00186581],
            [-2.96, 0.0015382],
            [-3, 0.0013499],
            [-3.09, 0.00100078],
            [-4, 0.00003167],
            [-5, 2.9e-7],
            [-6, 0],
            [-10, 0],
        ];
    }

    /**
     * @test mean
     */
    public function testMean()
    {
        // When
        $mean = $this->standardNormal->mean();

        // Then
        $this->assertEquals(0, $mean);
    }

    /**
     * @test mode
     */
    public function testMode()
    {
        // When
        $mode = $this->standardNormal->mode();

        // Then
        $this->assertEquals(0, $mode);
    }

    /**
     * @test variance
     */
    public function testVariance()
    {
        // When
        $variance = $this->standardNormal->variance();

        // Then
        $this->assertEquals(1, $variance);
    }

    /**
     * @test         inverse
     * @dataProvider dataProviderForInverse
     * @param        float $target
     * @param        float $expected_inverse
     */
    public function testInverse(float $target, float $expected_inverse)
    {
        // When
        $inverse = $this->standardNormal->inverse($target);

        // Then
        $this->assertEqualsWithDelta($expected_inverse, $inverse, 0.000001);
    }

    /**
     * @return array [z, inverse]
     * Generated with calculator https://captaincalculator.com/math/statistics/normal-distribution-calculator/
     */
    public function dataProviderForInverse(): array
    {
        return [
            [0.99, 2.32634787],
            [0.9, 1.28155157],
            [0.8, 0.84162123],
            [0.7, 0.52440051],
            [0.6, 0.2533471],
            [0.51, 0.02506891],
            [0.501, 0.00250663],
            [0.5005, 0.00125331],
            [0.50005, 0.00012533],
            [0.500005, 0.00001253],
            [0.5000005, 0.00000125],
            [0.50000005, 1.3e-7],
            [0.500000005, 1e-8],
            [0.5000000005, 0],
            [0.50000000005, 0],
            [0.500000000005, 0],
            [0.5000000000005, 0],
            [0.50000000000005, 0],
            [0.50000000000005, 0],
            [0.5, 0],
            [0.499999999999995, 0],
            [0.49999999999995, 0],
            [0.4999999999995, 0],
            [0.499999999995, 0],
            [0.49999999995, 0],
            [0.4999999995, 0],
            [0.499999995, -1e-8],
            [0.49999995, -1.3e-7],
            [0.4999995, -0.00000125],
            [0.499995, -0.00001253],
            [0.49995, -0.00012533],
            [0.4995, -0.00125331],
            [0.495, -0.01253347],
            [0.499, -0.00250663],
            [0.49, -0.02506891],
            [0.4, -0.2533471],
            [0.3, -0.52440051],
            [0.2, -0.84162123],
            [0.1, -1.28155157],
            [0.01, -2.32634787],
        ];
    }
}
