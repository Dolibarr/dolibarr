<?php

namespace Stripe\ApiOperations;

/**
 * Trait for deletable resources. Adds a `delete()` method to the class.
 *
 * This trait should only be applied to classes that derive from StripeObject.
 */
trait Delete
{
    /**
     * @param array|null $params
     * @param array|string|null $opts
     *
<<<<<<< HEAD
     * @return \Stripe\ApiResource The deleted resource.
=======
     * @return static The deleted resource.
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
     */
    public function delete($params = null, $opts = null)
    {
        self::_validateParams($params);

        $url = $this->instanceUrl();
        list($response, $opts) = $this->_request('delete', $url, $params, $opts);
        $this->refreshFrom($response, $opts);
        return $this;
    }
}
