<?php

namespace Stripe;

/**
 * Class StripeObject
 *
 * @package Stripe
 */
class StripeObject implements \ArrayAccess, \Countable, \JsonSerializable
{
    protected $_opts;
    protected $_originalValues;
    protected $_values;
    protected $_unsavedValues;
    protected $_transientValues;
    protected $_retrieveOptions;
    protected $_lastResponse;

    /**
     * @return Util\Set Attributes that should not be sent to the API because
     *    they're not updatable (e.g. ID).
     */
    public static function getPermanentAttributes()
    {
        static $permanentAttributes = null;
        if ($permanentAttributes === null) {
            $permanentAttributes = new Util\Set([
                'id',
            ]);
        }
        return $permanentAttributes;
    }

    /**
     * Additive objects are subobjects in the API that don't have the same
     * semantics as most subobjects, which are fully replaced when they're set.
     * This is best illustrated by example. The `source` parameter sent when
     * updating a subscription is *not* additive; if we set it:
     *
     *     source[object]=card&source[number]=123
     *
     * We expect the old `source` object to have been overwritten completely. If
     * the previous source had an `address_state` key associated with it and we
     * didn't send one this time, that value of `address_state` is gone.
     *
     * By contrast, additive objects are those that will have new data added to
     * them while keeping any existing data in place. The only known case of its
     * use is for `metadata`, but it could in theory be more general. As an
     * example, say we have a `metadata` object that looks like this on the
     * server side:
     *
     *     metadata = ["old" => "old_value"]
     *
     * If we update the object with `metadata[new]=new_value`, the server side
     * object now has *both* fields:
     *
     *     metadata = ["old" => "old_value", "new" => "new_value"]
     *
     * This is okay in itself because usually users will want to treat it as
     * additive:
     *
     *     $obj->metadata["new"] = "new_value";
     *     $obj->save();
     *
     * However, in other cases, they may want to replace the entire existing
     * contents:
     *
     *     $obj->metadata = ["new" => "new_value"];
     *     $obj->save();
     *
     * This is where things get a little bit tricky because in order to clear
     * any old keys that may have existed, we actually have to send an explicit
     * empty string to the server. So the operation above would have to send
     * this form to get the intended behavior:
     *
     *     metadata[old]=&metadata[new]=new_value
     *
     * This method allows us to track which parameters are considered additive,
     * and lets us behave correctly where appropriate when serializing
     * parameters to be sent.
     *
     * @return Util\Set Set of additive parameters
     */
    public static function getAdditiveParams()
    {
        static $additiveParams = null;
        if ($additiveParams === null) {
            // Set `metadata` as additive so that when it's set directly we remember
            // to clear keys that may have been previously set by sending empty
            // values for them.
            //
            // It's possible that not every object has `metadata`, but having this
            // option set when there is no `metadata` field is not harmful.
            $additiveParams = new Util\Set([
                'metadata',
            ]);
        }
        return $additiveParams;
    }

    public function __construct($id = null, $opts = null)
    {
        list($id, $this->_retrieveOptions) = Util\Util::normalizeId($id);
        $this->_opts = Util\RequestOptions::parse($opts);
        $this->_originalValues = [];
        $this->_values = [];
        $this->_unsavedValues = new Util\Set();
        $this->_transientValues = new Util\Set();
        if ($id !== null) {
            $this->_values['id'] = $id;
        }
    }

    // Standard accessor magic methods
    public function __set($k, $v)
    {
        if (static::getPermanentAttributes()->includes($k)) {
            throw new \InvalidArgumentException(
                "Cannot set $k on this object. HINT: you can't set: " .
                join(', ', static::getPermanentAttributes()->toArray())
            );
        }

        if ($v === "") {
            throw new \InvalidArgumentException(
                'You cannot set \''.$k.'\'to an empty string. '
                .'We interpret empty strings as NULL in requests. '
                .'You may set obj->'.$k.' = NULL to delete the property'
            );
        }

        $this->_values[$k] = Util\Util::convertToStripeObject($v, $this->_opts);
        $this->dirtyValue($this->_values[$k]);
        $this->_unsavedValues->add($k);
    }

    public function __isset($k)
    {
        return isset($this->_values[$k]);
    }

    public function __unset($k)
    {
        unset($this->_values[$k]);
        $this->_transientValues->add($k);
        $this->_unsavedValues->discard($k);
    }

    public function &__get($k)
    {
        // function should return a reference, using $nullval to return a reference to null
        $nullval = null;
        if (!empty($this->_values) && array_key_exists($k, $this->_values)) {
            return $this->_values[$k];
        } else if (!empty($this->_transientValues) && $this->_transientValues->includes($k)) {
            $class = get_class($this);
            $attrs = join(', ', array_keys($this->_values));
            $message = "Stripe Notice: Undefined property of $class instance: $k. "
                    . "HINT: The $k attribute was set in the past, however. "
                    . "It was then wiped when refreshing the object "
                    . "with the result returned by Stripe's API, "
                    . "probably as a result of a save(). The attributes currently "
                    . "available on this object are: $attrs";
            Stripe::getLogger()->error($message);
            return $nullval;
        } else {
            $class = get_class($this);
            Stripe::getLogger()->error("Stripe Notice: Undefined property of $class instance: $k");
            return $nullval;
        }
    }

    // Magic method for var_dump output. Only works with PHP >= 5.6
    public function __debugInfo()
    {
        return $this->_values;
    }

    // ArrayAccess methods
    public function offsetSet($k, $v)
    {
        $this->$k = $v;
    }

    public function offsetExists($k)
    {
        return array_key_exists($k, $this->_values);
    }

    public function offsetUnset($k)
    {
        unset($this->$k);
    }

    public function offsetGet($k)
    {
        return array_key_exists($k, $this->_values) ? $this->_values[$k] : null;
    }

    // Countable method
    public function count()
    {
        return count($this->_values);
    }

    public function keys()
    {
        return array_keys($this->_values);
    }

    public function values()
    {
        return array_values($this->_values);
    }

    /**
     * This unfortunately needs to be public to be used in Util\Util
     *
     * @param array $values
     * @param null|string|array|Util\RequestOptions $opts
     *
     * @return static The object constructed from the given values.
     */
    public static function constructFrom($values, $opts = null)
    {
        $obj = new static(isset($values['id']) ? $values['id'] : null);
        $obj->refreshFrom($values, $opts);
        return $obj;
    }

    /**
     * Refreshes this object using the provided values.
     *
     * @param array $values
     * @param null|string|array|Util\RequestOptions $opts
     * @param boolean $partial Defaults to false.
     */
    public function refreshFrom($values, $opts, $partial = false)
    {
        $this->_opts = Util\RequestOptions::parse($opts);

        $this->_originalValues = self::deepCopy($values);

        if ($values instanceof StripeObject) {
            $values = $values->__toArray(true);
        }

        // Wipe old state before setting new.  This is useful for e.g. updating a
        // customer, where there is no persistent card parameter.  Mark those values
        // which don't persist as transient
        if ($partial) {
            $removed = new Util\Set();
        } else {
            $removed = new Util\Set(array_diff(array_keys($this->_values), array_keys($values)));
        }

        foreach ($removed->toArray() as $k) {
            unset($this->$k);
        }

        $this->updateAttributes($values, $opts, false);
        foreach ($values as $k => $v) {
            $this->_transientValues->discard($k);
            $this->_unsavedValues->discard($k);
        }
    }

    /**
     * Mass assigns attributes on the model.
     *
     * @param array $values
     * @param null|string|array|Util\RequestOptions $opts
     * @param boolean $dirty Defaults to true.
     */
    public function updateAttributes($values, $opts = null, $dirty = true)
    {
        foreach ($values as $k => $v) {
            // Special-case metadata to always be cast as a StripeObject
            // This is necessary in case metadata is empty, as PHP arrays do
            // not differentiate between lists and hashes, and we consider
            // empty arrays to be lists.
            if (($k === "metadata") && (is_array($v))) {
                $this->_values[$k] = StripeObject::constructFrom($v, $opts);
            } else {
                $this->_values[$k] = Util\Util::convertToStripeObject($v, $opts);
            }
            if ($dirty) {
                $this->dirtyValue($this->_values[$k]);
            }
            $this->_unsavedValues->add($k);
        }
    }

    /**
     * @return array A recursive mapping of attributes to values for this object,
     *    including the proper value for deleted attributes.
     */
    public function serializeParameters($force = false)
    {
        $updateParams = [];

        foreach ($this->_values as $k => $v) {
            // There are a few reasons that we may want to add in a parameter for
            // update:
            //
            //   1. The `$force` option has been set.
            //   2. We know that it was modified.
            //   3. Its value is a StripeObject. A StripeObject may contain modified
            //      values within in that its parent StripeObject doesn't know about.
            //
            $original = array_key_exists($k, $this->_originalValues) ? $this->_originalValues[$k] : null;
            $unsaved = $this->_unsavedValues->includes($k);
            if ($force || $unsaved || $v instanceof StripeObject) {
                $updateParams[$k] = $this->serializeParamsValue(
                    $this->_values[$k],
                    $original,
                    $unsaved,
                    $force,
                    $k
                );
            }
        }

        // a `null` that makes it out of `serializeParamsValue` signals an empty
        // value that we shouldn't appear in the serialized form of the object
        $updateParams = array_filter(
            $updateParams,
            function ($v) {
                return $v !== null;
            }
        );

        return $updateParams;
    }


    public function serializeParamsValue($value, $original, $unsaved, $force, $key = null)
    {
        // The logic here is that essentially any object embedded in another
        // object that had a `type` is actually an API resource of a different
        // type that's been included in the response. These other resources must
        // be updated from their proper endpoints, and therefore they are not
        // included when serializing even if they've been modified.
        //
        // There are _some_ known exceptions though.
        //
        // For example, if the value is unsaved (meaning the user has set it), and
        // it looks like the API resource is persisted with an ID, then we include
        // the object so that parameters are serialized with a reference to its
        // ID.
        //
        // Another example is that on save API calls it's sometimes desirable to
        // update a customer's default source by setting a new card (or other)
        // object with `->source=` and then saving the customer. The
        // `saveWithParent` flag to override the default behavior allows us to
        // handle these exceptions.
        //
        // We throw an error if a property was set explicitly but we can't do
        // anything with it because the integration is probably not working as the
        // user intended it to.
        if ($value === null) {
            return "";
        } elseif (($value instanceof APIResource) && (!$value->saveWithParent)) {
            if (!$unsaved) {
                return null;
            } elseif (isset($value->id)) {
                return $value;
            } else {
                throw new \InvalidArgumentException(
                    "Cannot save property `$key` containing an API resource of type " .
                    get_class($value) . ". It doesn't appear to be persisted and is " .
                    "not marked as `saveWithParent`."
                );
            }
        } elseif (is_array($value)) {
            if (Util\Util::isList($value)) {
                // Sequential array, i.e. a list
                $update = [];
                foreach ($value as $v) {
                    array_push($update, $this->serializeParamsValue($v, null, true, $force));
                }
                // This prevents an array that's unchanged from being resent.
                if ($update !== $this->serializeParamsValue($original, null, true, $force, $key)) {
                    return $update;
                }
            } else {
                // Associative array, i.e. a map
                return Util\Util::convertToStripeObject($value, $this->_opts)->serializeParameters();
            }
        } elseif ($value instanceof StripeObject) {
            $update = $value->serializeParameters($force);
            if ($original && $unsaved && $key && static::getAdditiveParams()->includes($key)) {
                $update = array_merge(self::emptyValues($original), $update);
            }
            return $update;
        } else {
            return $value;
        }
    }

    public function jsonSerialize()
    {
        return $this->__toArray(true);
    }

    public function __toJSON()
    {
        return json_encode($this->__toArray(true), JSON_PRETTY_PRINT);
    }

    public function __toString()
    {
        $class = get_class($this);
        return $class . ' JSON: ' . $this->__toJSON();
    }

    public function __toArray($recursive = false)
    {
        if ($recursive) {
            return Util\Util::convertStripeObjectToArray($this->_values);
        } else {
            return $this->_values;
        }
    }

    /**
     * Sets all keys within the StripeObject as unsaved so that they will be
     * included with an update when `serializeParameters` is called. This
     * method is also recursive, so any StripeObjects contained as values or
     * which are values in a tenant array are also marked as dirty.
     */
    public function dirty()
    {
        $this->_unsavedValues = new Util\Set(array_keys($this->_values));
        foreach ($this->_values as $k => $v) {
            $this->dirtyValue($v);
        }
    }

    protected function dirtyValue($value)
    {
        if (is_array($value)) {
            foreach ($value as $v) {
                $this->dirtyValue($v);
            }
        } elseif ($value instanceof StripeObject) {
            $value->dirty();
        }
    }

    /**
     * Produces a deep copy of the given object including support for arrays
     * and StripeObjects.
     */
    protected static function deepCopy($obj)
    {
        if (is_array($obj)) {
            $copy = [];
            foreach ($obj as $k => $v) {
                $copy[$k] = self::deepCopy($v);
            }
            return $copy;
        } elseif ($obj instanceof StripeObject) {
            return $obj::constructFrom(
                self::deepCopy($obj->_values),
                clone $obj->_opts
            );
        } else {
            return $obj;
        }
    }

    /**
     * Returns a hash of empty values for all the values that are in the given
     * StripeObject.
     */
    public static function emptyValues($obj)
    {
        if (is_array($obj)) {
            $values = $obj;
        } elseif ($obj instanceof StripeObject) {
            $values = $obj->_values;
        } else {
            throw new \InvalidArgumentException(
                "empty_values got got unexpected object type: " . get_class($obj)
            );
        }
        $update = array_fill_keys(array_keys($values), "");
        return $update;
    }

    /**
     * @return object The last response from the Stripe API
     */
    public function getLastResponse()
    {
        return $this->_lastResponse;
    }

    /**
     * Sets the last response from the Stripe API
     *
     * @param ApiResponse $resp
     * @return void
     */
    public function setLastResponse($resp)
    {
        $this->_lastResponse = $resp;
    }

    /**
     * Indicates whether or not the resource has been deleted on the server.
     * Note that some, but not all, resources can indicate whether they have
     * been deleted.
     *
     * @return bool Whether the resource is deleted.
     */
    public function isDeleted()
    {
        return isset($this->_values['deleted']) ? $this->_values['deleted'] : false;
    }
}
