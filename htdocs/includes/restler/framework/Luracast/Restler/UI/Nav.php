<?php
namespace Luracast\Restler\UI;

use Luracast\Restler\CommentParser;
use Luracast\Restler\Defaults;
use Luracast\Restler\Restler;
use Luracast\Restler\Routes;
use Luracast\Restler\Scope;
use Luracast\Restler\Util;


/**
 * Utility class for automatically creating data to build an navigation interface
 * based on available routes that are accessible by the current user
 *
 * @category   Framework
 * @package    Restler
 * @author     R.Arul Kumaran <arul@luracast.com>
 * @copyright  2010 Luracast
 * @license    http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link       http://luracast.com/products/restler/
 * @version    3.0.0rc5
 */
class Nav
{
    public static $root = 'home';
    /**
     * @var null|callable if the api methods are under access control mechanism
     * you can attach a function here that returns true or false to determine
     * visibility of a protected api method. this function will receive method
     * info as the only parameter.
     */
    public static $accessControlFunction = null;
    /**
     * @var array all paths beginning with any of the following will be excluded
     * from documentation. if an empty string is given it will exclude the root
     */
    public static $excludedPaths = array('');
    /**
     * @var array prefix additional menu items with one of the following syntax
     *            [$path => $text]
     *            [$path]
     *            [$path => ['text' => $text, 'url' => $url]]
     */
    public static $prepends = array();
    /**
     * @var array suffix additional menu items with one of the following syntax
     *            [$path => $text]
     *            [$path]
     *            [$path => ['text' => $text, 'url' => $url]]
     */
    public static $appends = array();

    public static $addExtension = true;

    protected static $extension = '';

    public static function get($for = '', $activeUrl = null)
    {
        if (!static::$accessControlFunction && Defaults::$accessControlFunction)
            static::$accessControlFunction = Defaults::$accessControlFunction;
        /** @var Restler $restler */
        $restler = Scope::get('Restler');
        if (static::$addExtension)
            static::$extension = '.' . $restler->responseFormat->getExtension();
        if (is_null($activeUrl))
            $activeUrl = $restler->url;

        $tree = array();
        foreach (static::$prepends as $path => $text) {
            $url = null;
            if (is_array($text)) {
                if (isset($text['url'])) {
                    $url = $text['url'];
                    $text = $text['text'];
                } else {
                    $url = current(array_keys($text));
                    $text = current($text);
                }
            }
            if (is_numeric($path)) {
                $path = $text;
                $text = null;
            }
            if (empty($for) || 0 === strpos($path, "$for/"))
                static::build($tree, $path, $url, $text, $activeUrl);
        }
        $routes = Routes::toArray();
        $routes = $routes['v' . $restler->getRequestedApiVersion()];
        foreach ($routes as $value) {
            foreach ($value as $httpMethod => $route) {
                if ($httpMethod != 'GET') {
                    continue;
                }
                $path = $route['url'];
                if (false !== strpos($path, '{'))
                    continue;
                if ($route['accessLevel'] > 1 && !Util::$restler->_authenticated)
                    continue;
                foreach (static::$excludedPaths as $exclude) {
                    if (empty($exclude)) {
                        if (empty($path))
                            continue 2;
                    } elseif (0 === strpos($path, $exclude)) {
                        continue 2;
                    }
                }
                if ($restler->_authenticated
                    && static::$accessControlFunction
                    && (!call_user_func(
                        static::$accessControlFunction, $route['metadata']))
                ) {
                    continue;
                }
                $text = Util::nestedValue(
                    $route,
                    'metadata',
                    CommentParser::$embeddedDataName,
                    'label'
                );
                if (empty($for) || 0 === strpos($path, "$for/"))
                    static::build($tree, $path, null, $text, $activeUrl);
            }
        }
        foreach (static::$appends as $path => $text) {
            $url = null;
            if (is_array($text)) {
                if (isset($text['url'])) {
                    $url = $text['url'];
                    $text = $text['text'];
                } else {
                    $url = current(array_keys($text));
                    $text = current($text);
                }
            }
            if (is_numeric($path)) {
                $path = $text;
                $text = null;
            }
            if (empty($for) || 0 === strpos($path, "$for/"))
                static::build($tree, $path, $url, $text, $activeUrl);
        }
        if (!empty($for)) {
            $for = explode('/', $for);
            $p = & $tree;
            foreach ($for as $f) {
                if (isset($p[$f]['children'])) {
                    $p =  & $p[$f]['children'];
                } else {
                    return array();
                }
            }
            return $p;
        }
        return $tree;
    }

    protected static function build(&$tree, $path,
                                    $url = null, $text = null, $activeUrl = null)
    {
        $parts = explode('/', $path);
        if (count($parts) == 1 && empty($parts[0]))
            $parts = array(static::$root);
        $p = & $tree;
        $end = end($parts);
        foreach ($parts as $part) {
            if (!isset($p[$part])) {
                $p[$part] = array(
                    'href' => '#',
                    'text' => static::title($part)
                );
                if ($part == $end) {
                    $p[$part]['class'] = $part;
                    if ($text)
                        $p[$part]['text'] = $text;
                    if (is_null($url)) {
                        if (empty($path) && !empty(static::$extension))
                            $path = 'index';
                        $p[$part]['href'] = Util::$restler->getBaseUrl()
                            . '/' . $path . static::$extension;
                    } else {
                        if (empty($url) && !empty(static::$extension))
                            $url = 'index';
                        $p[$part]['href'] = $url . static::$extension;
                    }
                    if ($path == $activeUrl) {
                        $p[$part]['active'] = true;
                    }
                }
                $p[$part]['children'] = array();

            }
            $p = & $p[$part]['children'];
        }

    }

    protected static function title($name)
    {
        if (empty($name)) {
            $name = static::$root;
        } else {
            $name = ltrim($name, '#');
        }
        return ucfirst(preg_replace(array('/(?<=[^A-Z])([A-Z])/', '/(?<=[^0-9])([0-9])/'), ' $0', $name));
    }

} 