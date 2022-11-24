<?php

namespace MathPHP\Functions;

use MathPHP\Exception;

class Support
{
    private const ε = 0.00000000001;

    /**
     * Checks that the values of the parameters passed
     * to a function fall within the defined bounds.
     * The parameter limits are defined using ISO 31-11 notation.
     * https://en.wikipedia.org/wiki/ISO_31-11
     *
     *  (a,b) = a <  x <  b
     *  [a,b) = a <= x <  b
     *  (a,b] = a <  x <= b
     *  [a,b] = a <= x <= b
     *
     * @param array $limits Boundary limit definitions for each parameter
     *                      ['var1' => limit, 'var2' => limit, ...]
     * @param array $params Parameters and their value to check against the defined limits
     *                      ['var1' => value, 'var2' => value, ...]
     *
     * @return bool True if all parameters are within defined limits
     *
     * @throws Exception\BadParameterException if a parameter without bounds limits is defined
     * @throws Exception\OutOfBoundsException if any parameter is outside the defined limits
     * @throws Exception\BadDataException if an unknown bounds character is used
     */
    public static function checkLimits(array $limits, array $params)
    {
        // All parameters should have limit bounds defined
        $undefined_limits = \array_diff_key($params, $limits);
        if (!empty($undefined_limits)) {
            throw new Exception\BadParameterException('Parameter without bounds limit defined: ' . \print_r($undefined_limits, true));
        }

        foreach ($params as $variable => $value) {
            // Remove the first character: ( or [
            $lower_endpoint = \substr($limits[$variable], 0, 1);

            // Remove the last character: ) or ]
            $upper_endpoint = \substr($limits[$variable], -1, 1);

            // Set the lower and upper limits: #,#
            [$lower_limit, $upper_limit] = \explode(',', \substr($limits[$variable], 1, -1));

            // If the lower limit is -∞, we are always in bounds.
            if ($lower_limit != "-∞") {
                switch ($lower_endpoint) {
                    case '(':
                        if ($value <= $lower_limit) {
                            throw new Exception\OutOfBoundsException("{$variable} must be > {$lower_limit} (lower bound), given {$value}");
                        }
                        break;
                    case '[':
                        if ($value < $lower_limit) {
                            throw new Exception\OutOfBoundsException("{$variable} must be >= {$lower_limit} (lower bound), given {$value}");
                        }
                        break;
                    default:
                        throw new Exception\BadDataException("Unknown lower endpoint character: {$lower_limit}");
                }
            }

            // If the upper limit is ∞, we are always in bounds.
            if ($upper_limit != "∞") {
                switch ($upper_endpoint) {
                    case ')':
                        if ($value >= $upper_limit) {
                            throw new Exception\OutOfBoundsException("{$variable} must be < {$upper_limit} (upper bound), given {$value}");
                        }
                        break;
                    case ']':
                        if ($value > $upper_limit) {
                            throw new Exception\OutOfBoundsException("{$variable} must be <= {$upper_limit} (upper bound), given {$value}");
                        }
                        break;
                    default:
                        throw new Exception\BadDataException("Unknown upper endpoint character: {$upper_endpoint}");
                }
            }
        }

        return true;
    }

    /**
     * Is the number equivalent to zero?
     * Due to floating-point arithmetic, zero might be represented as an infinitesimal quantity.
     *
     * @param  float $x
     *
     * @return boolean true if equivalent to zero; false otherwise
     */
    public static function isZero(float $x, float $ε = self::ε): bool
    {
        return ($x == 0 || \abs($x) <= $ε);
    }

    /**
     * Is the number equivalent to a non-zero value?
     * Due to floating-point arithmetic, zero might be represented as an infinitesimal quantity.
     *
     * @param  float $x
     *
     * @return boolean true if equivalent to a non-zero value; false otherwise
     */
    public static function isNotZero(float $x, float $ε = self::ε): bool
    {
        return ($x != 0 && \abs($x) > $ε);
    }

    /**
     * Are two numbers equivalent up to a tiny tolerance?
     *
     * @param float $x
     * @param float $y
     *
     * @return bool
     */
    public static function isEqual(float $x, float $y, float $ε = self::ε): bool
    {
        return $x == $y || \abs($x - $y) < $ε;
    }

    /**
     * Are two numbers not equal given a tiny tolerance?
     *
     * @param float $x
     * @param float $y
     *
     * @return bool
     */
    public static function isNotEqual(float $x, float $y, float $ε = self::ε): bool
    {
        return $x != $y && \abs($x - $y) >= $ε;
    }
}
