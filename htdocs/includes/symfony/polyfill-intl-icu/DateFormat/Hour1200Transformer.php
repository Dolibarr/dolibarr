<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Polyfill\Intl\Icu\DateFormat;

/**
 * Parser and formatter for 12 hour format (0-11).
 *
 * @author Igor Wiedler <igor@wiedler.ch>
 *
 * @internal
 */
class Hour1200Transformer extends HourTransformer
{
    public function format(\DateTime $dateTime, int $length): string
    {
        $hourOfDay = $dateTime->format('g');
        $hourOfDay = '12' === $hourOfDay ? '0' : $hourOfDay;

        return $this->padLeft($hourOfDay, $length);
    }

    public function normalizeHour(int $hour, string $marker = null): int
    {
        if ('PM' === $marker) {
            $hour += 12;
        }

        return $hour;
    }

    public function getReverseMatchingRegExp(int $length): string
    {
        return '\d{1,2}';
    }

    public function extractDateOptions(string $matched, int $length): array
    {
        return [
            'hour' => (int) $matched,
            'hourInstance' => $this,
        ];
    }
}
