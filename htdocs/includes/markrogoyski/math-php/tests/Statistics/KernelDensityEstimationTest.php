<?php

namespace MathPHP\Tests\Statistics;

use MathPHP\Exception;
use MathPHP\Statistics\KernelDensityEstimation;

class KernelDensityEstimationTest extends \PHPUnit\Framework\TestCase
{
    /** @var array 100 Random normally distributed data points */
    private $data = [
            -2.76, -2.59, -2.57, -2.4, -1.9, -1.77, -1.33, -1.27, -1.26, -1.19, -1.13, -1.1, -1.09, -1.05, -1.03,
            -0.9, -0.87, -0.87, -0.85, -0.83, -0.78, -0.71, -0.62, -0.61, -0.6, -0.57, -0.55, -0.51, -0.5, -0.5,
            -0.47, -0.46, -0.46, -0.45, -0.44, -0.32, -0.28, -0.27, -0.25, -0.23, -0.21, -0.21, -0.2, -0.16, -0.15,
            -0.11, -0.094, -0.07, -0.06, -0.04, 0, 0.03, 0.04, 0.06, 0.06, 0.09, 0.1, 0.12, 0.15, 0.15, 0.18, 0.22,
            0.22, 0.23, 0.24, 0.3, 0.31, 0.34, 0.34, 0.38, 0.38, 0.44, 0.52, 0.53, 0.56, 0.57, 0.61, 0.61, 0.69,
            0.7, 0.75, 0.79, 0.79, 0.79, 0.8, 0.83, 0.85, 0.99, 1.08, 1.23, 1.23, 1.23, 1.25, 1.29, 1.32, 1.34,
            1.43, 1.49, 1.62, 1.75
        ];

    /**
     * @test         evaluate using default standard normal kernel function
     * @dataProvider dataProviderForKernelDensity
     * @param        array $data
     * @param        float $x
     * @param        float $expected
     */
    public function testDefaultKernelDensity(array $data, float $x, float $expected)
    {
        // Given
        $KDE = new KernelDensityEstimation($data);

        // When
        $estimate = $KDE->evaluate($x);

        // Then
        $this->assertEqualsWithDelta($expected, $estimate, 0.0001);
    }

    /**
     * @return array [data, x, expected]
     */
    public function dataProviderForKernelDensity(): array
    {
        return [
            [ $this->data, 1, 0.236055582 ],
            [ $this->data, .1, 0.421356196 ],
            [ $this->data, -1, 0.232277743 ],
        ];
    }

    /**
     * @test         evaluate when setting custom h using default kernel function
     * @dataProvider dataProviderForKernelDensityCustomH
     * @param        array $data
     * @param        float $h
     * @param        float $x
     * @param        float $expected
     */
    public function testDefaultKernelDensityCustomH(array $data, float $h, float $x, float $expected)
    {
        // Given
        $KDE = new KernelDensityEstimation($data);
        $KDE->setBandwidth($h);

        // When
        $estimate = $KDE->evaluate($x);

        // Then
        $this->assertEqualsWithDelta($expected, $estimate, 0.0001);
    }

    /**
     * @return array [data, h, x, expected]
     */
    public function dataProviderForKernelDensityCustomH(): array
    {
        $h = count($this->data) ** (-1 / 6);
        return [
            [ $this->data, $h, 1, 0.237991168 ],
            [ $this->data, $h, .1, 0.40525027 ],
            [ $this->data, $h, -1, 0.232226496 ],
        ];
    }

    /**
     * @test         Custom kernel function and custom h
     * @dataProvider dataProviderForKernelDensityCustomBoth
     * @param        float $h
     * @param        callable $kernel
     * @param        float $x
     * @param        float $expected
     */
    public function testDefaultKernelDensityCustomBoth(float $h, callable $kernel, float $x, float $expected)
    {
        // Given
        $KDE     = new KernelDensityEstimation($this->data, $h, $kernel);
        $kernel2 = KernelDensityEstimation::TRICUBE;
        $KDE2    = new KernelDensityEstimation($this->data, $h, $kernel2);

        // When
        $estimate1 = $KDE->evaluate($x);
        $estimate2 = $KDE2->evaluate($x);

        // Then
        $this->assertEqualsWithDelta($expected, $estimate1, 0.0001);
        $this->assertEqualsWithDelta($expected, $estimate2, 0.0001);
    }

    /**
     * @return array [h, kernel, x, expected]
     */
    public function dataProviderForKernelDensityCustomBoth(): array
    {
        $h = 1;

        // Tricube
        $kernel = function ($x) {
            if (\abs($x) > 1) {
                return 0;
            } else {
                return 70 / 81 * ((1 - \abs($x) ** 3) ** 3);
            }
        };

        return [
            [ $h, $kernel, 1, 0.238712304 ],
            [ $h, $kernel, .1, 0.420794741 ],
            [ $h, $kernel, -1, 0.229056709 ],
        ];
    }

    /**
     * @test         evaluate using optional kernel functions
     * @dataProvider dataProviderForTestKernels
     * @param        string $kernel
     * @param        float $x
     * @param        float $expected
     */
    public function testKernels(string $kernel, float $x, float $expected)
    {
        // Given
        $KDE = new KernelDensityEstimation($this->data, 1, $kernel);

        // When
        $estimate = $KDE->evaluate($x);

        // Then
        $this->assertEqualsWithDelta($expected, $estimate, 0.0001);
    }

    /**
     * @return array [kernel, x, expected]
     */
    public function dataProviderForTestKernels(): array
    {
        return [
            [KernelDensityEstimation::UNIFORM, 1, .25],
            [KernelDensityEstimation::TRIANGULAR, 1, .235],
            [KernelDensityEstimation::EPANECHNIKOV, 1, .2401905],
        ];
    }

    /**
     * @test Kernel function NORMAL
     * Test data made with scipy.stats.gaussian_kde
     * @dataProvider dataProviderForNormalKde
     * @param float $x
     * @param float $expected
     */
    public function testNormal(float $x, float $expected)
    {
        // Given
        $KDE = new KernelDensityEstimation($this->data);
        $h   = 0.3932;
        $KDE->setBandwidth($h);
        $KDE->setKernelFunction(KernelDensityEstimation::NORMAL);

        // When
        $estimate = $KDE->evaluate($x);

        // Then
        $this->assertEqualsWithDelta($expected, $estimate, 0.00001, "Evaluate x = $x");
    }

    /**
     * Test data made with scipy.stats.gaussian_kde
     * @return array (x, expected)
     */
    public function dataProviderForNormalKde(): array
    {
        return [
            [-3.939, 8.60829883e-05],
            [-2.222, 0.04138222],
            [-0.505, 0.36562621],
            [0, 0.43200043],
            [1.212, 0.19113825],
            [2.929, 9.09492454e-05],
        ];
    }

    /**
     * @test Invalid kernel throws an Exception
     */
    public function testBadKernel()
    {
        // Then
        $this->expectException(Exception\BadParameterException::class);

        // When
        $KDE = new KernelDensityEstimation($this->data, 1, 1.0);
    }

    /**
     * @test Unknown built-in kernel throws an Exception
     */
    public function testUnknownBuildInKernel()
    {
        // Given
        $KDE = new KernelDensityEstimation($this->data);

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        $KDE->setKernelFunction('DoesNotExist');
    }

    /**
     * @test Invalid bandwidth throws an Exception
     */
    public function testBadSetBandwidth()
    {
        // Given
        $KDE = new KernelDensityEstimation($this->data);

        // Then
        $this->expectException(Exception\OutOfBoundsException::class);

        // When
        $KDE->setBandwidth(-1);
    }

    /**
     * @test Empty data throws an Exception
     */
    public function testEmptyData()
    {
        // Given
        $data = [];

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        $KDE = new KernelDensityEstimation([]);
    }
}
