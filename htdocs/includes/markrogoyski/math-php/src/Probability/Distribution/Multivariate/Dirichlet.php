<?php

namespace MathPHP\Probability\Distribution\Multivariate;

use MathPHP\Functions\Map;
use MathPHP\Functions\Special;
use MathPHP\Functions\Support;
use MathPHP\Exception;

/**
 * Dirichlet distribution
 * https://en.wikipedia.org/wiki/Dirichlet_distribution
 */
class Dirichlet
{
    /**
     * Distribution parameter bounds limits
     * α ∈ (0,∞)
     * @var array
     */
    public const PARAMETER_LIMITS = [
        'α' => '(0,∞)',
    ];

    /**
     * Distribution parameter bounds limits
     * x ∈ (0,1)
     * @var array
     */
    public const SUPPORT_LIMITS = [
        'x' => '(0,1)',
    ];

    /** @var float[] $αs */
    protected $αs;

    /**
     * Constructor
     *
     * @param float[] $αs
     */
    public function __construct(array $αs)
    {
        $n = \count($αs);
        for ($i = 0; $i < $n; $i++) {
            Support::checkLimits(self::PARAMETER_LIMITS, ['α' => $αs[$i]]);
        }
        $this->αs = $αs;
    }

    /**
     * Probability density function
     *
     *        1    K   αᵢ-1
     * pdf = ----  ∏ xᵢ
     *       B(α) ⁱ⁼ⁱ
     *
     * where B(α) is the multivariate Beta function
     *
     * @param float[] $xs
     *
     * @return float
     *
     * @throws Exception\BadDataException if xs and αs don't have the same number of elements
     */
    public function pdf(array $xs): float
    {
        if (\count($xs) !== \count($this->αs)) {
            throw new Exception\BadDataException('xs and αs must have the same number of elements');
        }

        $n = \count($xs);
        for ($i = 0; $i < $n; $i++) {
            Support::checkLimits(self::SUPPORT_LIMITS, ['x' => $xs[$i]]);
        }

        /*
         *  K   αᵢ-1
         *  ∏ xᵢ
         * ⁱ⁼ⁱ
         */
        $∏xᵢ = \array_product(
            \array_map(
                function ($xᵢ, $αᵢ) {
                    return $xᵢ ** ($αᵢ - 1);
                },
                $xs,
                $this->αs
            )
        );

        $B⟮α⟯ = Special::multivariateBeta($this->αs);

        return $∏xᵢ / $B⟮α⟯;
    }
}
