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

use Symfony\Polyfill\Intl\Icu\Exception\MethodNotImplementedException;

/**
 * Replacement for PHP's native {@link \Locale} class.
 *
 * The only methods supported in this class are `getDefault` and `canonicalize`.
 * All other methods will throw an exception when used.
 *
 * @author Eriksen Costa <eriksen.costa@infranology.com.br>
 * @author Bernhard Schussek <bschussek@gmail.com>
 *
 * @internal
 */
abstract class Locale
{
    public const DEFAULT_LOCALE = null;

    /* Locale method constants */
    public const ACTUAL_LOCALE = 0;
    public const VALID_LOCALE = 1;

    /* Language tags constants */
    public const LANG_TAG = 'language';
    public const EXTLANG_TAG = 'extlang';
    public const SCRIPT_TAG = 'script';
    public const REGION_TAG = 'region';
    public const VARIANT_TAG = 'variant';
    public const GRANDFATHERED_LANG_TAG = 'grandfathered';
    public const PRIVATE_TAG = 'private';

    /**
     * Not supported. Returns the best available locale based on HTTP "Accept-Language" header according to RFC 2616.
     *
     * @return string The corresponding locale code
     *
     * @see https://php.net/locale.acceptfromhttp
     *
     * @throws MethodNotImplementedException
     */
    public static function acceptFromHttp(string $header)
    {
        throw new MethodNotImplementedException(__METHOD__);
    }

    /**
     * Returns a canonicalized locale string.
     *
     * This polyfill doesn't implement the full-spec algorithm. It only
     * canonicalizes locale strings handled by the `LocaleBundle` class.
     *
     * @return string
     */
    public static function canonicalize(string $locale)
    {
        if ('' === $locale || '.' === $locale[0]) {
            return self::getDefault();
        }

        if (!preg_match('/^([a-z]{2})[-_]([a-z]{2})(?:([a-z]{2})(?:[-_]([a-z]{2}))?)?(?:\..*)?$/i', $locale, $m)) {
            return $locale;
        }

        if (!empty($m[4])) {
            return strtolower($m[1]).'_'.ucfirst(strtolower($m[2].$m[3])).'_'.strtoupper($m[4]);
        }

        if (!empty($m[3])) {
            return strtolower($m[1]).'_'.ucfirst(strtolower($m[2].$m[3]));
        }

        return strtolower($m[1]).'_'.strtoupper($m[2]);
    }

    /**
     * Not supported. Returns a correctly ordered and delimited locale code.
     *
     * @return string The corresponding locale code
     *
     * @see https://php.net/locale.composelocale
     *
     * @throws MethodNotImplementedException
     */
    public static function composeLocale(array $subtags)
    {
        throw new MethodNotImplementedException(__METHOD__);
    }

    /**
     * Not supported. Checks if a language tag filter matches with locale.
     *
     * @return string The corresponding locale code
     *
     * @see https://php.net/locale.filtermatches
     *
     * @throws MethodNotImplementedException
     */
    public static function filterMatches(string $languageTag, string $locale, bool $canonicalize = false)
    {
        throw new MethodNotImplementedException(__METHOD__);
    }

    /**
     * Not supported. Returns the variants for the input locale.
     *
     * @return array The locale variants
     *
     * @see https://php.net/locale.getallvariants
     *
     * @throws MethodNotImplementedException
     */
    public static function getAllVariants(string $locale)
    {
        throw new MethodNotImplementedException(__METHOD__);
    }

    /**
     * Returns the default locale.
     *
     * @return string The default locale code. Always returns 'en'
     *
     * @see https://php.net/locale.getdefault
     */
    public static function getDefault()
    {
        return 'en';
    }

    /**
     * Not supported. Returns the localized display name for the locale language.
     *
     * @return string The localized language display name
     *
     * @see https://php.net/locale.getdisplaylanguage
     *
     * @throws MethodNotImplementedException
     */
    public static function getDisplayLanguage(string $locale, string $displayLocale = null)
    {
        throw new MethodNotImplementedException(__METHOD__);
    }

    /**
     * Not supported. Returns the localized display name for the locale.
     *
     * @return string The localized locale display name
     *
     * @see https://php.net/locale.getdisplayname
     *
     * @throws MethodNotImplementedException
     */
    public static function getDisplayName(string $locale, string $displayLocale = null)
    {
        throw new MethodNotImplementedException(__METHOD__);
    }

    /**
     * Not supported. Returns the localized display name for the locale region.
     *
     * @return string The localized region display name
     *
     * @see https://php.net/locale.getdisplayregion
     *
     * @throws MethodNotImplementedException
     */
    public static function getDisplayRegion(string $locale, string $displayLocale = null)
    {
        throw new MethodNotImplementedException(__METHOD__);
    }

    /**
     * Not supported. Returns the localized display name for the locale script.
     *
     * @return string The localized script display name
     *
     * @see https://php.net/locale.getdisplayscript
     *
     * @throws MethodNotImplementedException
     */
    public static function getDisplayScript(string $locale, string $displayLocale = null)
    {
        throw new MethodNotImplementedException(__METHOD__);
    }

    /**
     * Not supported. Returns the localized display name for the locale variant.
     *
     * @return string The localized variant display name
     *
     * @see https://php.net/locale.getdisplayvariant
     *
     * @throws MethodNotImplementedException
     */
    public static function getDisplayVariant(string $locale, string $displayLocale = null)
    {
        throw new MethodNotImplementedException(__METHOD__);
    }

    /**
     * Not supported. Returns the keywords for the locale.
     *
     * @return array Associative array with the extracted variants
     *
     * @see https://php.net/locale.getkeywords
     *
     * @throws MethodNotImplementedException
     */
    public static function getKeywords(string $locale)
    {
        throw new MethodNotImplementedException(__METHOD__);
    }

    /**
     * Not supported. Returns the primary language for the locale.
     *
     * @return string|null The extracted language code or null in case of error
     *
     * @see https://php.net/locale.getprimarylanguage
     *
     * @throws MethodNotImplementedException
     */
    public static function getPrimaryLanguage(string $locale)
    {
        throw new MethodNotImplementedException(__METHOD__);
    }

    /**
     * Not supported. Returns the region for the locale.
     *
     * @return string|null The extracted region code or null if not present
     *
     * @see https://php.net/locale.getregion
     *
     * @throws MethodNotImplementedException
     */
    public static function getRegion(string $locale)
    {
        throw new MethodNotImplementedException(__METHOD__);
    }

    /**
     * Not supported. Returns the script for the locale.
     *
     * @return string|null The extracted script code or null if not present
     *
     * @see https://php.net/locale.getscript
     *
     * @throws MethodNotImplementedException
     */
    public static function getScript(string $locale)
    {
        throw new MethodNotImplementedException(__METHOD__);
    }

    /**
     * Not supported. Returns the closest language tag for the locale.
     *
     * @see https://php.net/locale.lookup
     *
     * @throws MethodNotImplementedException
     */
    public static function lookup(array $languageTag, string $locale, bool $canonicalize = false, string $defaultLocale = null)
    {
        throw new MethodNotImplementedException(__METHOD__);
    }

    /**
     * Not supported. Returns an associative array of locale identifier subtags.
     *
     * @return array Associative array with the extracted subtags
     *
     * @see https://php.net/locale.parselocale
     *
     * @throws MethodNotImplementedException
     */
    public static function parseLocale(string $locale)
    {
        throw new MethodNotImplementedException(__METHOD__);
    }

    /**
     * Not supported. Sets the default runtime locale.
     *
     * @return bool true on success or false on failure
     *
     * @see https://php.net/locale.setdefault
     *
     * @throws MethodNotImplementedException
     */
    public static function setDefault(string $locale)
    {
        if ('en' !== $locale) {
            throw new MethodNotImplementedException(__METHOD__);
        }

        return true;
    }
}
