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
 * Parser and formatter for quarter format.
 *
 * @author Igor Wiedler <igor@wiedler.ch>
 *
 * @internal
 */
class QuarterTransformer extends Transformer
{
    public function format(\DateTime $dateTime, int $length): string
    {
        $month = (int) $dateTime->format('n');
        $quarter = (int) floor(($month - 1) / 3) + 1;
        switch ($length) {
            case 1:
            case 2:
                return $this->padLeft($quarter, $length);
            case 3:
                return 'Q'.$quarter;
            case 4:
                $map = [1 => '1st quarter', 2 => '2nd quarter', 3 => '3rd quarter', 4 => '4th quarter'];

                return $map[$quarter];
            default:
                if (\defined('INTL_ICU_VERSION') && version_compare(\INTL_ICU_VERSION, '70.1', '<')) {
                    $map = [1 => '1st quarter', 2 => '2nd quarter', 3 => '3rd quarter', 4 => '4th quarter'];

                    return $map[$quarter];
                } else {
                    return $quarter;
                }
        }
    }

    public function getReverseMatchingRegExp(int $length): string
    {
        switch ($length) {
            case 1:
            case 2:
                return '\d{'.$length.'}';
            case 3:
                return 'Q\d';
            default:
                return '(?:1st|2nd|3rd|4th) quarter';
        }
    }

    public function extractDateOptions(string $matched, int $length): array
    {
        return [];
    }
}
