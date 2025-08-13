<?php
namespace Luracast\Restler;

use Luracast\Restler\Format\HtmlFormat;
use ArrayAccess;

/**
 * Storing and retrieving a message or array of key value pairs for one time use using $_SESSION
 *
 * They are typically used in view templates when using HtmlFormat
 *
 * @category   Framework
 * @package    Restler
 * @author     R.Arul Kumaran <arul@luracast.com>
 * @copyright  2010 Luracast
 * @license    http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link       http://luracast.com/products/restler/
 * @version    3.0.0rc6
 */
class Flash implements ArrayAccess
{
    const SUCCESS = 'success';
    const INFO = 'info';
    const WARNING = 'warning';
    const DANGER = 'danger';

    /**
     * @var Flash
     */
    private static $instance;
    private $usedOnce = false;

    /**
     * Flash a success message to user
     *
     * @param string $message
     * @param string $header
     *
     * @return Flash
     */
    public static function success($message, $header = '')
    {
        return static::message($message, $header, Flash::SUCCESS);
    }

    /**
     * Flash a info message to user
     *
     * @param string $message
     * @param string $header
     *
     * @return Flash
     */
    public static function info($message, $header = '')
    {
        return static::message($message, $header, Flash::INFO);
    }

    /**
     * Flash a warning message to user
     *
     * @param string $message
     * @param string $header
     *
     * @return Flash
     */
    public static function warning($message, $header = '')
    {
        return static::message($message, $header, Flash::WARNING);
    }

    /**
     * Flash a error message to user
     *
     * @param string $message
     * @param string $header
     *
     * @return Flash
     */
    public static function danger($message, $header = '')
    {
        return static::message($message, $header, Flash::DANGER);
    }

    /**
     * Flash a message to user
     *
     * @param string $text message text
     * @param string $header
     * @param string $type
     *
     * @return Flash
     */
    public static function message($text, $header = '', $type = Flash::WARNING)
    {
        return static::set(array('message' => $text, 'header' => $header, 'type' => $type));
    }

    /**
     * Set some data for one time use
     *
     * @param array $data array of key value pairs {@type associative}
     *
     * @return Flash
     */
    public static function set(array $data)
    {
        if (!static::$instance) {
            static::$instance = new Flash();
        }
        if (!isset($_SESSION['flash'])) {
            $_SESSION['flash'] = array();
        }
        $_SESSION['flash'] += $data;
        HtmlFormat::$data['flash'] = static::$instance;

        return static::$instance;
    }

    public function __get($name)
    {
        $this->usedOnce = true;

        return Util::nestedValue($_SESSION, 'flash', $name);
    }

    public function __isset($name)
    {
        return !is_null(Util::nestedValue($_SESSION, 'flash', $name));
    }

    public function __destruct()
    {
        if ($this->usedOnce) {
            unset($_SESSION['flash']);
        }
    }

    /**
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     */
    public function jsonSerialize()
    {
        $this->usedOnce = true;

        return isset($_SESSION['flash'])
            ? $_SESSION['flash']
            : array();
    }


    public function offsetExists($offset)
    {
        return $this->__isset($offset);
    }

    public function offsetGet($offset)
    {
        return $this->__get($offset);
    }

    public function offsetSet($offset, $value)
    {
        //not implemented
    }

    public function offsetUnset($offset)
    {
        //not implemented
    }
}