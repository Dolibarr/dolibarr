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
 * Parser and formatter for minute format.
 *
 * @author Igor Wiedler <igor@wiedler.ch>
 *
 * @internal
 */
class MinuteTransformer extends Transformer
{
    public function format(\DateTime $dateTime, int $length): string
    {
        $minuteOfHour = (int) $dateTime->format('i');

        return $this->padLeft($minuteOfHour, $length);
    }

    public function getReverseMatchingRegExp(int $length): string
    {
        return 1 === $length ? '\d{1,2}' : '\d{'.$length.'}';
    }

    public function extractDateOptions(string $matched, int $length): array
    {
        return [
            'minute' => (int) $matched,
        ];
    }
}
