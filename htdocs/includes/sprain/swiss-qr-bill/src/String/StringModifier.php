<?php declare(strict_types=1);

namespace Sprain\SwissQrBill\String;

/**
 * @internal
 */
final class StringModifier
{
    public static function replaceLineBreaksAndTabsWithSpaces(?string $string): string
    {
        return is_null($string)
            ? ''
            : str_replace(["\r", "\n", "\t"], ' ', $string);
    }

    public static function replaceMultipleSpacesWithOne(?string $string): string
    {
        return is_null($string)
            ? ''
            :  preg_replace('/ +/', ' ', $string);
    }

    public static function stripWhitespace(?string $string): string
    {
        return is_null($string)
            ? ''
            : preg_replace('/\s+/', '', $string);
    }
}
