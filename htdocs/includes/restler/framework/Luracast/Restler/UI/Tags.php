<?php
namespace Luracast\Restler\UI;

use ArrayAccess;
use Countable;
use Luracast\Restler\Util;

/**
 * Utility class for generating html tags in an object oriented way
 *
 * @category   Framework
 * @package    Restler
 * @author     R.Arul Kumaran <arul@luracast.com>
 * @copyright  2010 Luracast
 * @license    http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link       http://luracast.com/products/restler/
 * @version    3.0.0rc6
 *
 * ============================ magic  properties ==============================
 * @property Tags parent parent tag
 * ============================== magic  methods ===============================
 * @method Tags name(string $value) name attribute
 * @method Tags action(string $value) action attribute
 * @method Tags placeholder(string $value) placeholder attribute
 * @method Tags value(string $value) value attribute
 * @method Tags required(boolean $value) required attribute
 * @method Tags class(string $value) required attribute
 *
 * =========================== static magic methods ============================
 * @method static Tags form() creates a html form
 * @method static Tags input() creates a html input element
 * @method static Tags button() creates a html button element
 *
 */
class Tags implements ArrayAccess, Countable
{
    public static $humanReadable = true;
    public static $initializer = null;
    protected static $instances = array();
    public $prefix = '';
    public $indent = '    ';
    public $tag;
    protected $attributes = array();
    protected $children = array();
    protected $_parent;

    public function __construct($name = null, array $children = array())
    {
        $this->tag = $name;
        $c = array();
        foreach ($children as $child) {
            is_array($child)
                ? $c = array_merge($c, $child)
                : $c [] = $child;
        }
        $this->markAsChildren($c);
        $this->children = $c;
        if (static::$initializer)
            call_user_func_array(static::$initializer, array(& $this));
    }

    /**
     * Get Tag by id
     *
     * Retrieve a tag by its id attribute
     *
     * @param string $id
     *
     * @return Tags|null
     */
    public static function byId($id)
    {
        return Util::nestedValue(static::$instances, $id);
    }

    /**
     * @param       $name
     * @param array $children
     *
     * @return Tags
     */
    public static function __callStatic($name, array $children)
    {
        return new static($name, $children);
    }

    public function toString($prefix = '', $indent = '    ')
    {
        $this->prefix = $prefix;
        $this->indent = $indent;
        return $this->__toString();
    }

    public function __toString()
    {
        $children = '';
        if (static::$humanReadable) {
            $lineBreak = false;
            foreach ($this->children as $key => $child) {
                $prefix = $this->prefix;
                if (!is_null($this->tag))
                    $prefix .= $this->indent;
                if ($child instanceof $this) {
                    $child->prefix = $prefix;
                    $child->indent = $this->indent;
                    $children .= PHP_EOL . $child;
                    $lineBreak = true;
                } else {
                    $children .= $child;
                }
            }
            if ($lineBreak)
                $children .= PHP_EOL . $this->prefix;
        } else {
            $children = implode('', $this->children);
        }
        if (is_null($this->tag))
            return $children;
        $attributes = '';
        foreach ($this->attributes as $attribute => &$value)
            $attributes .= " $attribute=\"$value\"";

        if (count($this->children))
            return static::$humanReadable
                ? "$this->prefix<{$this->tag}{$attributes}>"
                . "$children"
                . "</{$this->tag}>"
                : "<{$this->tag}{$attributes}>$children</{$this->tag}>";

        return "$this->prefix<{$this->tag}{$attributes}/>";
    }

    public function toArray()
    {
        $r = array();
        $r['attributes'] = $this->attributes;
        $r['tag'] = $this->tag;
        $children = array();
        foreach ($this->children as $key => $child) {
            $children[$key] = $child instanceof $this
                ? $child->toArray()
                : $child;
        }
        $r['children'] = $children;
        return $r;
    }

    /**
     * Set the id attribute of the current tag
     *
     * @param string $value
     *
     * @return string
     */
    public function id($value)
    {
        if (!empty($value) && is_string($value)) {
            $this->attributes['id'] = $value;
            static::$instances[$value] = $this;
        }
        return $this;
    }

    public function __get($name)
    {
        if ('parent' == $name)
            return $this->_parent;
        if (isset($this->attributes[$name]))
            return $this->attributes[$name];
        return;
    }

    public function __set($name, $value)
    {
        if ('parent' == $name) {
            if ($this->_parent) {
                unset($this->_parent[array_search($this, $this->_parent->children)]);
            }
            if (!empty($value)) {
                $value[] = $this;
            }
        }
    }

    public function __isset($name)
    {
        return isset($this->attributes[$name]);
    }

    /**
     * @param $attribute
     * @param $value
     *
     * @return Tags
     */
    public function __call($attribute, $value)
    {
        if (is_null($value)) {
            return isset($this->attributes[$attribute])
                ? $this->attributes[$attribute]
                : null;
        }
        $value = $value[0];
        if (is_null($value)) {
            unset($this->attributes[$attribute]);
            return $this;
        }
        $this->attributes[$attribute] = is_bool($value)
            ? ($value ? 'true' : 'false')
            : @(string)$value;
        return $this;
    }

    public function offsetGet($index)
    {
        if ($this->offsetExists($index)) {
            return $this->children[$index];
        }
        return false;
    }

    public function offsetExists($index)
    {
        return isset($this->children[$index]);
    }

    public function offsetSet($index, $value)
    {
        if ($index) {
            $this->children[$index] = $value;
        } elseif (is_array($value)) {
            $c = array();
            foreach ($value as $child) {
                is_array($child)
                    ? $c = array_merge($c, $child)
                    : $c [] = $child;
            }
            $this->markAsChildren($c);
            $this->children += $c;
        } else {
            $c = array($value);
            $this->markAsChildren($c);
            $this->children[] = $value;
        }
        return true;
    }

    public function offsetUnset($index)
    {
        $this->children[$index]->_parent = null;
        unset($this->children[$index]);
        return true;
    }

    public function getContents()
    {
        return $this->children;
    }

    public function count()
    {
        return count($this->children);
    }

    private function markAsChildren(& $children)
    {
        foreach ($children as $i => $child) {
            if (is_string($child))
                continue;
            if (!is_object($child)) {
                unset($children[$i]);
                continue;
            }
            //echo $child;
            if (isset($child->_parent) && $child->_parent != $this) {
                //remove from current parent
                unset($child->_parent[array_search($child, $child->_parent->children)]);
            }
            $child->_parent = $this;
        }
    }
}