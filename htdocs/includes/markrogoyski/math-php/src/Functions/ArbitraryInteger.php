<?php

namespace MathPHP\Functions;

use MathPHP\Exception;
use MathPHP\Number;

/**
 * Functions that operate with ArbitraryInteger objects
 */
class ArbitraryInteger
{
    /**
     * Ackermann Function
     * A well known highly recursive function which produces very large numbers
     *
     * https://en.wikipedia.org/wiki/Ackermann_function
     *
     * @param int|string|Number\ArbitraryInteger $m
     * @param int|string|Number\ArbitraryInteger $n
     *
     * @return Number\ArbitraryInteger
     *
     * @throws Exception\BadParameterException
     * @throws Exception\IncorrectTypeException
     */
    public static function ackermann($m, $n): Number\ArbitraryInteger
    {
        $m = Number\ArbitraryInteger::create($m);
        $n = Number\ArbitraryInteger::create($n);

        if ($m->equals(0)) {
            return $n->add(1);
        } elseif ($m->equals(1)) {
            return $n->add(2);
        } elseif ($m->equals(2)) {
            return $n->leftShift(1)->add(3);
        } elseif ($m->equals(3)) {
            $one = new Number\ArbitraryInteger(1);
            return $one->leftShift($n->add(3))->subtract(3);  // 2^(n+3) - 3
        } elseif ($n->equals(0)) {
            return self::ackermann($m->subtract(1), 1);
        } else {
            return self::ackermann($m->subtract(1), self::ackermann($m, $n->subtract(1)));
        }
    }

    /**
     * Create a random ArbitraryInteger
     *
     * @param int $bytes
     *
     * @return Number\ArbitraryInteger
     *
     * @throws Exception\BadParameterException
     * @throws Exception\IncorrectTypeException
     */
    public static function rand(int $bytes): Number\ArbitraryInteger
    {
        if ($bytes <= 0) {
            throw new Exception\BadParameterException('Cannot produce a random number with zero or negative bytes.');
        }

        return Number\ArbitraryInteger::fromBinary(\random_bytes($bytes), \mt_rand(0, 1) === 0);
    }
}
