<?php

namespace MathPHP\Statistics\Regression\Models;

trait PowerModel
{
    /** @var int b parameter index */
    protected static $B = 1;

    /** @var int a parameter index */
    protected static $A = 0;

   /**
    * Evaluate the power curve equation from power law regression parameters for a value of x
    * y = axᵇ
    *
    * @param float $x
    * @param array $params
    *
    * @return float y evaluated
    */
    public static function evaluateModel(float $x, array $params): float
    {
        $a = $params[self::$A];
        $b = $params[self::$B];

        return $a * $x ** $b;
    }

    /**
     * Get regression parameters (a and b)
     *
     * @param array $params
     *
     * @return array [ a => number, b => number ]
     */
    public function getModelParameters(array $params): array
    {
        return [
            'a' => $params[self::$A],
            'b' => $params[self::$B],
        ];
    }

    /**
     * Get regression equation (y = axᵇ) in format y = ax^b
     *
     * @param array $params
     *
     * @return string
     */
    public function getModelEquation(array $params): string
    {
        return \sprintf('y = %fx^%f', $params[self::$A], $params[self::$B]);
    }
}
