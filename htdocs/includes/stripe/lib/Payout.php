<?php

namespace Stripe;

/**
 * Class Payout
 *
 * @property string $id
 * @property string $object
 * @property int $amount
 * @property string $balance_transaction
 * @property string $cancellation_balance_transaction
 * @property int $created
 * @property string $currency
 * @property int $arrival_date
 * @property string $destination
 * @property string $failure_code
 * @property string $failure_message
 * @property bool $livemode
 * @property mixed $metadata
 * @property string $method
 * @property string $recipient
 * @property string $source_type
 * @property string $statement_descriptor
 * @property string $status
 * @property string $type
 *
 * @package Stripe
 */
class Payout extends ApiResource
{
    /**
     * @param string $id The ID of the payout to retrieve.
     * @param array|string|null $opts
     *
     * @return Payout
     */
    public static function retrieve($id, $opts = null)
    {
        return self::_retrieve($id, $opts);
    }

    /**
     * @param array|null $params
     * @param array|string|null $opts
     *
     * @return Collection of Payouts
     */
    public static function all($params = null, $opts = null)
    {
        return self::_all($params, $opts);
    }

    /**
     * @param array|null $params
     * @param array|string|null $opts
     *
     * @return Payout The created payout.
     */
    public static function create($params = null, $opts = null)
    {
        return self::_create($params, $opts);
    }

    /**
     * @param string $id The ID of the payout to update.
     * @param array|null $params
     * @param array|string|null $options
     *
     * @return Payout The updated payout.
     */
    public static function update($id, $params = null, $options = null)
    {
        return self::_update($id, $params, $options);
    }

    /**
     * @return Payout The canceled payout.
     */
    public function cancel()
    {
        $url = $this->instanceUrl() . '/cancel';
        list($response, $opts) = $this->_request('post', $url);
        $this->refreshFrom($response, $opts);
        return $this;
    }

    /**
     * @param array|string|null $opts
     *
     * @return Payout The saved payout.
     */
    public function save($opts = null)
    {
        return $this->_save($opts);
    }
}
