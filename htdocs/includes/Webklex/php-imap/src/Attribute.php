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
use ReturnTypeWillChange;

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
     * @param string $name
     * @param array|mixed      $value
     */
    public function __construct(string $name, $value = null) {
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
    public function toString(): string {
        return $this->__toString();
    }

    /**
     * Convert instance to array
     *
     * @return array
     */
    public function toArray(): array {
        return $this->values;
    }

    /**
     * Convert first value to a date object
     *
     * @return Carbon
     */
    public function toDate(): Carbon {
        $date = $this->first();
        if ($date instanceof Carbon) return $date;

        return Carbon::parse($date);
    }

    /**
     * Determine if a value exists at an offset.
     *
     * @param  mixed  $offset
     * @return bool
     */
    public function offsetExists($offset): bool {
        return array_key_exists($offset, $this->values);
    }

    /**
     * Get a value at a given offset.
     *
     * @param  mixed  $offset
     * @return mixed
     */
    #[ReturnTypeWillChange]
    public function offsetGet($offset) {
        return $this->values[$offset];
    }

    /**
     * Set the value at a given offset.
     *
     * @param  mixed  $offset
     * @param  mixed  $value
     * @return void
     */
    #[ReturnTypeWillChange]
    public function offsetSet($offset, $value) {
        if (is_null($offset)) {
            $this->values[] = $value;
        } else {
            $this->values[$offset] = $value;
        }
    }

    /**
     * Unset the value at a given offset.
     *
     * @param  string  $offset
     * @return void
     */
    #[ReturnTypeWillChange]
    public function offsetUnset($offset) {
        unset($this->values[$offset]);
    }

    /**
     * Add one or more values to the attribute
     * @param array|mixed $value
     * @param boolean $strict
     *
     * @return Attribute
     */
    public function add($value, bool $strict = false): Attribute {
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
    public function merge(array $values, bool $strict = false): Attribute {
        foreach ($values as $value) {
            $this->attach($value, $strict);
        }

        return $this;
    }

    /**
     * Check if the attribute contains the given value
     * @param mixed $value
     *
     * @return bool
     */
    public function contains($value): bool {
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
    public function attach($value, bool $strict = false) {
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
    public function setName($name): Attribute {
        $this->name = $name;

        return $this;
    }

    /**
     * Get the attribute name
     *
     * @return string
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * Get all values
     *
     * @return array
     */
    public function get(): array {
        return $this->values;
    }

    /**
     * Alias method for self::get()
     *
     * @return array
     */
    public function all(): array {
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
    public function count(): int {
        return count($this->values);
    }
}