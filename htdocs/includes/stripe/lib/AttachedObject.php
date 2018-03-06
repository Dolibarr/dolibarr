<?php

namespace Stripe;

use Countable;

/**
 * Class AttachedObject
 *
 * e.g. metadata on Stripe objects.
 *
 * @package Stripe
 */
class AttachedObject extends StripeObject implements Countable
{
    /**
     * Updates this object.
     *
     * @param array $properties A mapping of properties to update on this object.
     */
    public function replaceWith($properties)
    {
        $removed = array_diff(array_keys($this->_values), array_keys($properties));
        // Don't unset, but rather set to null so we send up '' for deletion.
        foreach ($removed as $k) {
            $this->$k = null;
        }

        foreach ($properties as $k => $v) {
            $this->$k = $v;
        }
    }

    /**
     * Counts the number of elements in the AttachedObject instance.
     *
     * @return int the number of elements
     */
    public function count()
    {
        return count($this->_values);
    }
}
