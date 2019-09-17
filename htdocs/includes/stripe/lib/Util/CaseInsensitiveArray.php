<?php

namespace Stripe\Util;

use ArrayAccess;

/**
 * CaseInsensitiveArray is an array-like class that ignores case for keys.
 *
 * It is used to store HTTP headers. Per RFC 2616, section 4.2:
 * Each header field consists of a name followed by a colon (":") and the field value. Field names
 * are case-insensitive.
 *
 * In the context of stripe-php, this is useful because the API will return headers with different
 * case depending on whether HTTP/2 is used or not (with HTTP/2, headers are always in lowercase).
 */
class CaseInsensitiveArray implements ArrayAccess
{
    private $container = array();

    public function __construct($initial_array = array())
    {
        $this->container = array_map("strtolower", $initial_array);
    }

    public function offsetSet($offset, $value)
    {
        $offset = static::maybeLowercase($offset);
        if (is_null($offset)) {
            $this->container[] = $value;
        } else {
            $this->container[$offset] = $value;
        }
    }

    public function offsetExists($offset)
    {
        $offset = static::maybeLowercase($offset);
        return isset($this->container[$offset]);
    }

    public function offsetUnset($offset)
    {
        $offset = static::maybeLowercase($offset);
        unset($this->container[$offset]);
    }

    public function offsetGet($offset)
    {
        $offset = static::maybeLowercase($offset);
        return isset($this->container[$offset]) ? $this->container[$offset] : null;
    }

    private static function maybeLowercase($v)
    {
        if (is_string($v)) {
            return strtolower($v);
        } else {
            return $v;
        }
    }
}
