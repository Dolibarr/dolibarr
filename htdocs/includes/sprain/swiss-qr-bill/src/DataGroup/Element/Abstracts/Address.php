<?php

namespace Sprain\SwissQrBill\DataGroup\Element\Abstracts;

use Sprain\SwissQrBill\String\StringAnalyzer;
use Sprain\SwissQrBill\String\StringModifier;

/**
 * @internal
 */
abstract class Address
{
    private const MAX_CHARS_PER_LINE_ON_RECEIPT = 40;

    protected static function normalizeString(?string $string): ?string
    {
        if (is_null($string)) {
            return null;
        }

        $string = trim($string);
        $string = StringModifier::replaceLineBreaksAndTabsWithSpaces($string);
        $string = StringModifier::replaceMultipleSpacesWithOne($string);

        return $string;
    }

    protected static function clearMultilines(array $lines): array
    {
        $noOfLongLines = 0;

        foreach ($lines as $line) {
            if (self::willBeMoreThanOneLineOnReceipt($line)) {
                $noOfLongLines++;
            }
        }

        if ($noOfLongLines > 0) {
            if (isset($lines[2])) {
                unset($lines[2]);
            }
        }

        if ($noOfLongLines > 1) {
            unset($lines[3]);
        }

        return $lines;
    }

    private static function willBeMoreThanOneLineOnReceipt(string $string): bool
    {
        return mb_strlen($string) > self::MAX_CHARS_PER_LINE_ON_RECEIPT;
    }
}
