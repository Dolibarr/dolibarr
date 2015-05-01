<?php
namespace Luracast\Restler\Data;

/**
 * Convenience class for String manipulation
 *
 * @category   Framework
 * @package    Restler
 * @author     R.Arul Kumaran <arul@luracast.com>
 * @copyright  2010 Luracast
 * @license    http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link       http://luracast.com/products/restler/
 */
class String
{
    /**
     * Given haystack contains the needle or not?
     *
     * @param string $haystack
     * @param string $needle
     * @param bool   $caseSensitive
     *
     * @return bool
     */
    public static function contains($haystack, $needle, $caseSensitive = true)
    {
        if (empty($needle))
            return true;
        return $caseSensitive
            ? strpos($haystack, $needle) !== false
            : stripos($haystack, $needle) !== false;
    }

    /**
     * Given haystack begins with the needle or not?
     *
     * @param string $haystack
     * @param string $needle
     *
     * @return bool
     */
    public static function beginsWith($haystack, $needle)
    {
        $length = strlen($needle);
        return (substr($haystack, 0, $length) === $needle);
    }

    /**
     * Given haystack ends with the needle or not?
     *
     * @param string $haystack
     * @param string $needle
     *
     * @return bool
     */
    public static function endsWith($haystack, $needle)
    {
        $length = strlen($needle);
        if ($length == 0) {
            return true;
        }
        return (substr($haystack, -$length) === $needle);
    }


    /**
     * Convert camelCased or underscored string in to a title
     *
     * @param string $name
     *
     * @return string
     */
    public static function title($name)
    {
        return
            ucwords(
                preg_replace(
                    array('/(?<=[^A-Z])([A-Z])/', '/(?<=[^0-9])([0-9])/', '/(_)/'),
                    array(' $0', ' $0', ' '),
                    $name
                )
            );
    }
} 