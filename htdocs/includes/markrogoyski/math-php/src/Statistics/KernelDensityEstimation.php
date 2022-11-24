<?php

namespace MathPHP\Statistics;

use MathPHP\Exception;
use MathPHP\Probability\Distribution\Continuous;

/**
 * Kernel Density Estimate
 * https://en.wikipedia.org/wiki/Kernel_density_estimation
 *
 *                       ____
 *            1          \         / (x - xᵢ) \
 * KDE(x) = -----   *     >     K |  -------   |
 *          n * h        /         \    h     /
 *                       ‾‾‾‾
 * The kernel function K, must be a non-negative function with a mean of 0 and integrates to 1
 */
class KernelDensityEstimation
{
    /** @var array Data used for the esimtation */
    protected $data;

    /** @var int number of data points */
    protected $n;

    /** @var float bandwidth */
    protected $h;

    /** @var callable kernel function */
    protected $kernel;

    // Available built-in kernel functions
    public const STANDARD_NORMAL = 'StandardNormal';
    public const NORMAL          = 'Normal';
    public const UNIFORM         = 'Uniform';
    public const TRIANGULAR      = 'Triangular';
    public const EPANECHNIKOV    = 'Epanechnikov';
    public const TRICUBE         = 'Tricube';

    /**
     * Constructor
     *
     * @param array $data data used for the estimation
     * @param float|null $h the bandwidth
     * @param callable|string|null $kernel a function used to generate the KDE
     *
     * @throws Exception\BadDataException     if data set is empty
     * @throws Exception\OutOfBoundsException h ≤ 0
     * @throws Exception\BadParameterException
     */
    public function __construct(array $data, float $h = null, $kernel = null)
    {
        $this->n = \count($data);
        if ($this->n === 0) {
            throw new Exception\BadDataException('Dataset cannot be empty.');
        }
        $this->data = $data;

        $this->setBandwidth($h);
        $this->setKernelFunction($kernel);
    }

    /**
     * Set Bandwidth
     *
     * @param float|null $h the bandwidth
     *
     * @throws Exception\OutOfBoundsException if h ≤ 0
     */
    public function setBandwidth(float $h = null): void
    {
        if ($h === null) {
            $this->h = $this->getDefaultBandwidth();
            return;
        }

        if ($h <= 0) {
            throw new Exception\OutOfBoundsException("Bandwidth must be > 0. h = $h");
        }

        $this->h = $h;
    }

    /**
     * Default bandwidth for when one is not provided.
     * Uses the normal distribution approximation bandwidth estimator.
     * https://en.wikipedia.org/wiki/Kernel_density_estimation#A_rule-of-thumb_bandwidth_estimator
     *
     *             ⅕
     *      / 4σ⁵ \
     * h = |  ---  |
     *      \ 3n  /
     *
     *
     * @return float
     *
     * @throws Exception\OutOfBoundsException
     */
    private function getDefaultBandwidth(): float
    {
        $４σ⁵ = 4 * Descriptive::standardDeviation($this->data) ** 5;
        $３n  = 3 * $this->n;
        $⅕    = 0.2;

        return ($４σ⁵ / $３n) ** $⅕;
    }

    /**
     * Set The Kernel Function
     *
     * If the parameter is a string, check that there is a function with that name
     * in the "library". If it's a callable, use that function.
     *
     * @param callable|string|null $kernel
     *
     * @throws Exception\BadParameterException if $kernel is not a string or callable
     * @throws Exception\BadDataException
     * @throws Exception\OutOfBoundsException
     */
    public function setKernelFunction($kernel = null): void
    {
        if ($kernel === null) {
            $this->kernel = $this->getKernelFunctionFromLibrary(self::STANDARD_NORMAL);
        } elseif (\is_string($kernel)) {
            $this->kernel = $this->getKernelFunctionFromLibrary($kernel);
        } elseif (\is_callable($kernel)) {
            $this->kernel = $kernel;
        } else {
            throw new Exception\BadParameterException('Kernel must be a callable or a string. Type is: ' . \gettype($kernel));
        }
    }

    /**
     * Select the kernel function from one of the built-in provided functions.
     *
     * @param  string $kernel Name of built-in kernel function
     *
     * @return callable kernel function
     *
     * @throws Exception\BadDataException if the name of the kernel function is not one of the built-in functions
     * @throws Exception\OutOfBoundsException
     */
    private function getKernelFunctionFromLibrary(string $kernel): callable
    {
        switch ($kernel) {
            case self::STANDARD_NORMAL:
                return function ($x) {
                    $standardNormal = new Continuous\StandardNormal();
                    return $standardNormal->pdf($x);
                };

            case self::NORMAL:
                $μ = 0;
                $σ = Descriptive::standardDeviation($this->data);
                return function ($x) use ($μ, $σ) {
                    $normal = new Continuous\Normal($μ, $σ);
                    return $normal->pdf($x);
                };

            case self::UNIFORM:
                return function ($x) {
                    if (\abs($x) > 1) {
                        return 0;
                    } else {
                        return .5;
                    }
                };

            case self::TRIANGULAR:
                return function ($x) {
                    if (\abs($x) > 1) {
                        return 0;
                    } else {
                        return 1 - \abs($x);
                    }
                };

            case self::EPANECHNIKOV:
                return function ($x) {
                    if (\abs($x) > 1) {
                        return 0;
                    } else {
                        return .75 * (1 - $x ** 2);
                    }
                };

            case self::TRICUBE:
                return function ($x) {
                    if (\abs($x) > 1) {
                        return 0;
                    } else {
                        return 70 / 81 * ((1 - \abs($x) ** 3) ** 3);
                    }
                };

            default:
                throw new Exception\BadDataException("Unknown kernel function: $kernel");
        }
    }

    /**
     * Evaluate the kernel density estimation at $x
     *
     *                       ____
     *            1          \         / (x - xᵢ) \
     * KDE(x) = -----   *     >     K |  -------   |
     *          n * h        /         \    h     /
     *                       ‾‾‾‾
     * @param float $x the value to evaluate
     *
     * @return float the kernel density estimate at $x
     */
    public function evaluate(float $x): float
    {
        $h = $this->h;
        $n = $this->n;

        $scale = \array_map(
            function ($xᵢ) use ($x, $h) {
                return ($x - $xᵢ) / $h;
            },
            $this->data
        );
        $K       = \array_map($this->kernel, $scale);
        $density = \array_sum($K) / ($n * $h);

        return $density;
    }
}
