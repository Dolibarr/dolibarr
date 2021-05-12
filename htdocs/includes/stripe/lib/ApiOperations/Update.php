<?php

namespace Stripe\ApiOperations;

/**
 * Trait for updatable resources. Adds an `update()` static method and a
 * `save()` method to the class.
 *
 * This trait should only be applied to classes that derive from StripeObject.
 */
trait Update
{
    /**
     * @param string $id The ID of the resource to update.
     * @param array|null $params
     * @param array|string|null $opts
     *
<<<<<<< HEAD
     * @return \Stripe\ApiResource The updated resource.
=======
     * @return static The updated resource.
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
     */
    public static function update($id, $params = null, $opts = null)
    {
        self::_validateParams($params);
        $url = static::resourceUrl($id);

        list($response, $opts) = static::_staticRequest('post', $url, $params, $opts);
        $obj = \Stripe\Util\Util::convertToStripeObject($response->json, $opts);
        $obj->setLastResponse($response);
        return $obj;
    }

    /**
     * @param array|string|null $opts
     *
<<<<<<< HEAD
     * @return \Stripe\ApiResource The saved resource.
=======
     * @return static The saved resource.
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
     */
    public function save($opts = null)
    {
        $params = $this->serializeParameters();
        if (count($params) > 0) {
            $url = $this->instanceUrl();
            list($response, $opts) = $this->_request('post', $url, $params, $opts);
            $this->refreshFrom($response, $opts);
        }
        return $this;
    }
}
