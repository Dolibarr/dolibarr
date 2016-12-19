<?php
namespace Luracast\Restler\Format;

/**
 * Javascript Object Notation Packaged in a method (JSONP)
 *
 * @category   Framework
 * @package    Restler
 * @subpackage format
 * @author     R.Arul Kumaran <arul@luracast.com>
 * @copyright  2010 Luracast
 * @license    http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link       http://luracast.com/products/restler/
 * @version    3.0.0rc6
 */
class JsFormat extends JsonFormat
{
    const MIME = 'text/javascript';
    const EXTENSION = 'js';

    public static $callbackMethodName = 'parseResponse';
    public static $callbackOverrideQueryString = 'callback';
    public static $includeHeaders = true;

    public function encode($data, $human_readable = false)
    {
        $r = array();
        if (static::$includeHeaders) {
            $r['meta'] = array();
            foreach (headers_list() as $header) {
                list($h, $v) = explode(': ', $header, 2);
                $r['meta'][$h] = $v;
            }
        }
        $r['data'] = $data;
        if (isset($_GET[static::$callbackOverrideQueryString])) {
            static::$callbackMethodName
                = (string) $_GET[static::$callbackOverrideQueryString];
        }
        return static::$callbackMethodName . '('
            . parent::encode($r, $human_readable) . ');';
    }

    public function isReadable()
    {
        return false;
    }
}
