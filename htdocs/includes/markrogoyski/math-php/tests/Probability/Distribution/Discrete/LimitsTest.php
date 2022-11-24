<?php

namespace MathPHP\Tests\Probability\Distribution\Discrete;

use MathPHP\Probability\Distribution\Discrete;

class LimitsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Limits should look like:
     *  (a,b)
     *  [a,b)
     *  (a,b]
     *  [a,b]
     */
    private function limitTest($limits)
    {
        foreach ($limits as $parameter => $limit) {
            $this->assertRegExp('/^ ([[(]) (.+) , (.+?) ([])]) $/x', $limit);
        }
    }

    /**
     * @test Limits constant is correct format
     */
    public function testBernoulliParameterLimits()
    {
        $this->limitTest(Discrete\Bernoulli::PARAMETER_LIMITS);
    }

    /**
     * @test Limits constant is correct format
     */
    public function testBernoulliSupportLimits()
    {
        $this->limitTest(Discrete\Bernoulli::SUPPORT_LIMITS);
    }

    /**
     * @test Limits constant is correct format
     */
    public function testBinomialParameterLimits()
    {
        $this->limitTest(Discrete\Binomial::PARAMETER_LIMITS);
    }

    /**
     * @test Limits constant is correct format
     */
    public function testBinomialSupportLimits()
    {
        $this->limitTest(Discrete\Binomial::SUPPORT_LIMITS);
    }

    /**
     * @test Limits constant is correct format
     */
    public function testGeometricParameterLimits()
    {
        $this->limitTest(Discrete\Geometric::PARAMETER_LIMITS);
    }

    /**
     * @test Limits constant is correct format
     */
    public function testGeometricSupportLimits()
    {
        $this->limitTest(Discrete\Geometric::SUPPORT_LIMITS);
    }

    /**
     * @test Limits constant is correct format
     */
    public function testShiftedGeometricLimits()
    {
        $this->limitTest(Discrete\ShiftedGeometric::PARAMETER_LIMITS);
        $this->limitTest(Discrete\ShiftedGeometric::SUPPORT_LIMITS);
    }

    /**
     * @test Limits constant is correct format
     */
    public function testNegativeBinomialLimits()
    {
        $this->limitTest(Discrete\NegativeBinomial::PARAMETER_LIMITS);
        $this->limitTest(Discrete\NegativeBinomial::SUPPORT_LIMITS);
    }

    /**
     * @test Limits constant is correct format
     */
    public function testPoissonLimits()
    {
        $this->limitTest(Discrete\Poisson::PARAMETER_LIMITS);
        $this->limitTest(Discrete\Poisson::SUPPORT_LIMITS);
    }
}
