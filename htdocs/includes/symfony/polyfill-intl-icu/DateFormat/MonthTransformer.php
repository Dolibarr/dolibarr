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
 * Parser and formatter for month format.
 *
 * @author Igor Wiedler <igor@wiedler.ch>
 *
 * @internal
 */
class MonthTransformer extends Transformer
{
    protected static $months = [
        'January',
        'February',
        'March',
        'April',
        'May',
        'June',
        'July',
        'August',
        'September',
        'October',
        'November',
        'December',
    ];

    /**
     * Short months names (first 3 letters).
     */
    protected static $shortMonths = [];

    /**
     * Flipped $months array, $name => $index.
     */
    protected static $flippedMonths = [];

    /**
     * Flipped $shortMonths array, $name => $index.
     */
    protected static $flippedShortMonths = [];

    public function __construct()
    {
        if (0 === \count(self::$shortMonths)) {
            self::$shortMonths = array_map(function ($month) {
                return substr($month, 0, 3);
            }, self::$months);

            self::$flippedMonths = array_flip(self::$months);
            self::$flippedShortMonths = array_flip(self::$shortMonths);
        }
    }

    public function format(\DateTime $dateTime, int $length): string
    {
        $matchLengthMap = [
            1 => 'n',
            2 => 'm',
            3 => 'M',
            4 => 'F',
        ];

        if (isset($matchLengthMap[$length])) {
            return $dateTime->format($matchLengthMap[$length]);
        }

        if (5 === $length) {
            return substr($dateTime->format('M'), 0, 1);
        }

        return $this->padLeft($dateTime->format('m'), $length);
    }

    public function getReverseMatchingRegExp(int $length): string
    {
        switch ($length) {
            case 1:
                $regExp = '\d{1,2}';
                break;
            case 3:
                $regExp = implode('|', self::$shortMonths);
                break;
            case 4:
                $regExp = implode('|', self::$months);
                break;
            case 5:
                $regExp = '[JFMASOND]';
                break;
            default:
                $regExp = '\d{1,'.$length.'}';
                break;
        }

        return $regExp;
    }

    public function extractDateOptions(string $matched, int $length): array
    {
        if (!is_numeric($matched)) {
            if (3 === $length) {
                $matched = self::$flippedShortMonths[$matched] + 1;
            } elseif (4 === $length) {
                $matched = self::$flippedMonths[$matched] + 1;
            } elseif (5 === $length) {
                // IntlDateFormatter::parse() always returns false for MMMMM or LLLLL
                $matched = false;
            }
        } else {
            $matched = (int) $matched;
        }

        return [
            'month' => $matched,
        ];
    }
}
