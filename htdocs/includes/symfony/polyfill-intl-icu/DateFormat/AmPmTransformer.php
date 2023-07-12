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
 * Parser and formatter for AM/PM markers format.
 *
 * @author Igor Wiedler <igor@wiedler.ch>
 *
 * @internal
 */
class AmPmTransformer extends Transformer
{
    public function format(\DateTime $dateTime, int $length): string
    {
        return $dateTime->format('A');
    }

    public function getReverseMatchingRegExp(int $length): string
    {
        return 'AM|PM';
    }

    public function extractDateOptions(string $matched, int $length): array
    {
        return [
            'marker' => $matched,
        ];
    }
}
