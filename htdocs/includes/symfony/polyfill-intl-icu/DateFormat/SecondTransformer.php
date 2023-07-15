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
 * Parser and formatter for the second format.
 *
 * @author Igor Wiedler <igor@wiedler.ch>
 *
 * @internal
 */
class SecondTransformer extends Transformer
{
    public function format(\DateTime $dateTime, int $length): string
    {
        $secondOfMinute = (int) $dateTime->format('s');

        return $this->padLeft($secondOfMinute, $length);
    }

    public function getReverseMatchingRegExp(int $length): string
    {
        return 1 === $length ? '\d{1,2}' : '\d{'.$length.'}';
    }

    public function extractDateOptions(string $matched, int $length): array
    {
        return [
            'second' => (int) $matched,
        ];
    }
}
