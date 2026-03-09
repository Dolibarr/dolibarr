<?php
/*
* File:     Attribute.php
* Category: -
* Author:   M. Goldenbaum
* Created:  01.01.21 20:17
* Updated:  -
*
* Description:
*  -
*/

namespace Webklex\PHPIMAP;

use ArrayAccess;
use Carbon\Carbon;

/**
 * Class Attribute
 *
 * @package Webklex\PHPIMAP
 */
class Attribute implements ArrayAccess {

    /** @var string $name */
    protected $name;

    /**
     * Value holder
     *
     * @var array $values
     */
    protected $values = [];

    /**
     * Attribute constructor.
     * @param string   $name
     * @param array|mixed      $value
     */
    public function __construct($name, $value = null) {
        $this->setName($name);
        $this->add($value);
    }


    /**
     * Return the stringified attribute
     *
     * @return string
     */
    public function __toString() {
        return implode(", ", $this->values);
    }

    /**
     * Return the stringified attribute
     *
     * @return string
     */
    public function toString(){
        return $this->__toString();
    }

    /**
     * Return the serialized attribute
     *
     * @return array
     */
    public function __serialize(){
        return $this->values;
    }

    /**
     * Convert instance to array
     *
     * @return array
     */
    public function toArray(){
        return $this->__serialize();
    }

    /**
     * Convert first value to a date object
     *
     * @return Carbon|null
     */
    public function toDate(){
        $date = $this->first();
        if ($date instanceof Carbon) return $date;

        return Carbon::parse($date);
    }

    /**
     * Determine if a value exists at an offset.
     *
     * @param  mixed  $key
     * @return bool
     */
    public function offsetExists($key) {
        return array_key_exists($key, $this->values);
    }

    /**
     * Get a value at a given offset.
     *
     * @param  mixed  $key
     * @return mixed
     */
    public function offsetGet($key) {
        return $this->values[$key];
    }

    /**
     * Set the value at a given offset.
     *
     * @param  mixed  $key
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet($key, $value) {
        if (is_null($key)) {
            $this->values[] = $value;
        } else {
            $this->values[$key] = $value;
        }
    }

    /**
     * Unset the value at a given offset.
     *
     * @param  string  $key
     * @return void
     */
    public function offsetUnset($key) {
        unset($this->values[$key]);
    }

    /**
     * Add one or more values to the attribute
     * @param array|mixed $value
     * @param boolean $strict
     *
     * @return Attribute
     */
    public function add($value, $strict = false) {
        if (is_array($value)) {
            return $this->merge($value, $strict);
        }elseif ($value !== null) {
            $this->attach($value, $strict);
        }

        return $this;
    }

    /**
     * Merge a given array of values with the current values array
     * @param array $values
     * @param boolean $strict
     *
     * @return Attribute
     */
    public function merge($values, $strict = false) {
        if (is_array($values)) {
            foreach ($values as $value) {
                $this->attach($value, $strict);
            }
        }

        return $this;
    }

    /**
     * Check if the attribute contains the given value
     * @param mixed $value
     *
     * @return bool
     */
    public function contains($value) {
        foreach ($this->values as $v) {
            if ($v === $value) {
                return true;
            }
        }
        return false;
    }

    /**
     * Attach a given value to the current value array
     * @param $value
     * @param bool $strict
     */
    public function attach($value, $strict = false) {
        if ($strict === true) {
            if ($this->contains($value) === false) {
                $this->values[] = $value;
            }
        }else{
            $this->values[] = $value;
        }
    }

    /**
     * Set the attribute name
     * @param $name
     *
     * @return Attribute
     */
    public function setName($name){
        $this->name = $name;

        return $this;
    }

    /**
     * Get the attribute name
     *
     * @return string
     */
    public function getName(){
        return $this->name;
    }

    /**
     * Get all values
     *
     * @return array
     */
    public function get(){
        return $this->values;
    }

    /**
     * Alias method for self::get()
     *
     * @return array
     */
    public function all(){
        return $this->get();
    }

    /**
     * Get the first value if possible
     *
     * @return mixed|null
     */
    public function first(){
        if ($this->offsetExists(0)) {
            return $this->values[0];
        }
        return null;
    }

    /**
     * Get the last value if possible
     *
     * @return mixed|null
     */
    public function last(){
        if (($cnt = $this->count()) > 0) {
            return $this->values[$cnt - 1];
        }
        return null;
    }

    /**
     * Get the number of values
     *
     * @return int
     */
    public function count(){
        return count($this->values);
    }
}