<?php
namespace Luracast\Restler\Data;

/**
 * Convenience class for Array manipulation
 *
 * @category   Framework
 * @package    Restler
 * @author     R.Arul Kumaran <arul@luracast.com>
 * @copyright  2010 Luracast
 * @license    http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link       http://luracast.com/products/restler/
 * @version    3.0.0rc6
 */
class Arr
{
    /**
     * Deep copy given array
     *
     * @param array $arr
     *
     * @return array
     */
    public static function copy(array $arr)
    {
        $copy = array();
        foreach ($arr as $key => $value) {
            if (is_array($value)) $copy[$key] = static::copy($value);
            else if (is_object($value)) $copy[$key] = clone $value;
            else $copy[$key] = $value;
        }
        return $copy;
    }
} 