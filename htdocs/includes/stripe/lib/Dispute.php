<?php

namespace Stripe;

/**
 * Class Dispute
 *
 * @property string $id
 * @property string $object
 * @property int $amount
 * @property mixed $balance_transactions
 * @property string $charge
 * @property int $created
 * @property string $currency
 * @property mixed $evidence
 * @property mixed $evidence_details
 * @property bool $is_charge_refundable
 * @property bool $livemode
 * @property mixed $metadata
 * @property string $reason
 * @property string $status
 *
 * @package Stripe
 */
class Dispute extends ApiResource
{
    /**
     * @param string $id The ID of the dispute to retrieve.
     * @param array|string|null $options
     *
     * @return Dispute
     */
    public static function retrieve($id, $options = null)
    {
        return self::_retrieve($id, $options);
    }

    /**
     * @param array|null $params
     * @param array|string|null $options
     *
     * @return array An array of Disputes.
     */
    public static function all($params = null, $options = null)
    {
        return self::_all($params, $options);
    }

    /**
     * @param string $id The ID of the dispute to update.
     * @param array|null $params
     * @param array|string|null $options
     *
     * @return Dispute The updated dispute.
     */
    public static function update($id, $params = null, $options = null)
    {
        return self::_update($id, $params, $options);
    }

    /**
     * @param array|string|null $options
     *
     * @return Dispute The saved charge.
     */
    public function save($options = null)
    {
        return $this->_save($options);
    }

    /**
     * @param array|string|null $options
     *
     * @return Dispute The closed dispute.
     */
    public function close($options = null)
    {
        $url = $this->instanceUrl() . '/close';
        list($response, $opts) = $this->_request('post', $url, null, $options);
        $this->refreshFrom($response, $opts);
        return $this;
    }
}
