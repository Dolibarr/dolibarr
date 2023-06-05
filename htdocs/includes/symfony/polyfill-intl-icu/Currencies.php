<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Polyfill\Intl\Icu;

/**
 * @author Nicolas Grekas <p@tchwork.com>
 *
 * @internal
 */
class Currencies
{
    private static $data;

    public static function getSymbol(string $currency): ?string
    {
        $data = self::$data ?? self::$data = require __DIR__.'/Resources/currencies.php';

        return $data[$currency][0] ?? $data[strtoupper($currency)][0] ?? null;
    }

    public static function getFractionDigits(string $currency): int
    {
        $data = self::$data ?? self::$data = require __DIR__.'/Resources/currencies.php';

        return $data[$currency][1] ?? $data[strtoupper($currency)][1] ?? $data['DEFAULT'][1];
    }

    public static function getRoundingIncrement(string $currency): int
    {
        $data = self::$data ?? self::$data = require __DIR__.'/Resources/currencies.php';

        return $data[$currency][2] ?? $data[strtoupper($currency)][2] ?? $data['DEFAULT'][2];
    }
}
