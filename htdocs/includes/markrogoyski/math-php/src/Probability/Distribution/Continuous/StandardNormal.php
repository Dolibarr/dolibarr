<?php

namespace MathPHP\Probability\Distribution\Continuous;

use MathPHP\Functions\Support;

/**
 * Standard normal distribution
 * The simplest case of a normal distribution.
 * This is a special case when μ = 0 and σ = 1,
 */
class StandardNormal extends Normal
{
    /**
     * Mean is always 0
     * @var int
     */
    protected const μ = 0;

    /**
     * Standard deviation is always 1
     * @var int
     */
    protected const σ = 1;

    /**
     * Distribution parameter bounds limits
     * μ ∈ [0,0]
     * σ ∈ [1,1]
     * @var array
     */
    public const PARAMETER_LIMITS = [
        'μ' => '[-0,0]',
        'σ' => '[1,1]',
    ];

    /**
     * Distribution support bounds limits
     * z ∈ (-∞,∞)
     * @var array
     */
    public const SUPPORT_LIMITS = [
        'z' => '(-∞,∞)',
    ];

    /**
     * StandardNormal constructor
     */
    public function __construct()
    {
        parent::__construct(self::μ, self::σ);
    }
}
