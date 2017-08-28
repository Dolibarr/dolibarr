<?php

namespace Stripe;

/**
 * Class SubscriptionItem
 *
 * @package Stripe
 */
class SubscriptionItem extends ApiResource
{
    /**
     * This is a special case because the subscription items endpoint has an
     *    underscore in it. The parent `className` function strips underscores.
     *
     * @return string The name of the class.
     */
    public static function className()
    {
        return 'subscription_item';
    }

    /**
     * @param string $id The ID of the subscription item to retrieve.
     * @param array|string|null $opts
     *
     * @return SubscriptionItem
     */
    public static function retrieve($id, $opts = null)
    {
        return self::_retrieve($id, $opts);
    }

    /**
     * @param array|null $params
     * @param array|string|null $opts
     *
     * @return Collection of SubscriptionItems
     */
    public static function all($params = null, $opts = null)
    {
        return self::_all($params, $opts);
    }

    /**
     * @param array|null $params
     * @param array|string|null $opts
     *
     * @return SubscriptionItem The created subscription item.
     */
    public static function create($params = null, $opts = null)
    {
        return self::_create($params, $opts);
    }

    /**
     * @param string $id The ID of the subscription item to update.
     * @param array|null $params
     * @param array|string|null $options
     *
     * @return SubscriptionItem The updated subscription item.
     */
    public static function update($id, $params = null, $options = null)
    {
        return self::_update($id, $params, $options);
    }

    /**
     * @param array|string|null $opts
     *
     * @return SubscriptionItem The saved subscription item.
     */
    public function save($opts = null)
    {
        return $this->_save($opts);
    }

    /**
     * @param array|null $params
     * @param array|string|null $opts
     *
     * @return SubscriptionItem The deleted subscription item.
     */
    public function delete($params = null, $opts = null)
    {
        return $this->_delete($params, $opts);
    }
}
