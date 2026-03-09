<?php
namespace Luracast\Restler\Format;

/**
 * URL Encoded String Format
 *
 * @category   Framework
 * @package    Restler
 * @subpackage format
 * @author     R.Arul Kumaran <arul@luracast.com>
 * @copyright  2010 Luracast
 * @license    http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link       http://luracast.com/products/restler/
 *
 */
class UrlEncodedFormat extends Format
{
    const MIME = 'application/x-www-form-urlencoded';
    const EXTENSION = 'post';

    public function encode($data, $humanReadable = false)
    {
        return http_build_query(static::encoderTypeFix($data));
    }

    public function decode($data)
    {
        parse_str($data, $r);
        return self::decoderTypeFix($r);
    }

    public static function encoderTypeFix(array $data)
    {
        foreach ($data as $k => $v) {
            if (is_bool($v)) {
                $data[$k] = $v = $v ? 'true' : 'false';
            } elseif (is_array($v)) {
                $data[$k] = $v = static::decoderTypeFix($v);
            }
        }
        return $data;
    }

    public static function decoderTypeFix(array $data)
    {
        foreach ($data as $k => $v) {
            if ($v === 'true' || $v === 'false') {
                $data[$k] = $v = $v === 'true';
            } elseif (is_array($v)) {
                $data[$k] = $v = static::decoderTypeFix($v);
            } elseif (empty($v) && $v != 0) {
                unset($data[$k]);
            }
        }
        return $data;
    }
}

