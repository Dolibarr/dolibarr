<?php

namespace Stripe;

/**
 * Class Plan
 *
 * @package Stripe
 *
 * @property $id
 * @property $object
 * @property $amount
 * @property $created
 * @property $currency
 * @property $interval
 * @property $interval_count
 * @property $livemode
 * @property AttachedObject $metadata
 * @property $name
 * @property $statement_descriptor
 * @property $trial_period_days
 */
class Plan extends ApiResource
{
    /**
     * @param string $id The ID of the plan to retrieve.
     * @param array|string|null $opts
     *
     * @return Plan
     */
    public static function retrieve($id, $opts = null)
    {
        return self::_retrieve($id, $opts);
    }

    /**
     * @param array|null $params
     * @param array|string|null $opts
     *
     * @return Plan The created plan.
     */
    public static function create($params = null, $opts = null)
    {
        return self::_create($params, $opts);
    }

    /**
     * @param string $id The ID of the plan to update.
     * @param array|null $params
     * @param array|string|null $options
     *
     * @return Plan The updated plan.
     */
    public static function update($id, $params = null, $options = null)
    {
        return self::_update($id, $params, $options);
    }

    /**
     * @param array|null $params
     * @param array|string|null $opts
     *
     * @return Plan The deleted plan.
     */
    public function delete($params = null, $opts = null)
    {
        return $this->_delete($params, $opts);
    }

    /**
     * @param array|string|null $opts
     *
     * @return Plan The saved plan.
     */
    public function save($opts = null)
    {
        return $this->_save($opts);
    }

    /**
     * @param array|null $params
     * @param array|string|null $opts
     *
     * @return Collection of Plans
     */
    public static function all($params = null, $opts = null)
    {
        return self::_all($params, $opts);
    }
}
