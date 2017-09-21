<?php

namespace Stripe;

/**
 * Class BalanceTransaction
 *
 * @property string $id
 * @property string $object
 * @property int $amount
 * @property int $available_on
 * @property int $created
 * @property string $currency
 * @property string $description
 * @property int $fee
 * @property mixed $fee_details
 * @property int $net
 * @property string $source
 * @property mixed $sourced_transfers
 * @property string $status
 * @property string $type
 *
 * @package Stripe
 */
class BalanceTransaction extends ApiResource
{
    /**
     * @return string The class URL for this resource. It needs to be special
     *    cased because it doesn't fit into the standard resource pattern.
     */
    public static function classUrl()
    {
        return "/v1/balance/history";
    }

    /**
     * @param string $id The ID of the balance transaction to retrieve.
     * @param array|string|null $opts
     *
     * @return BalanceTransaction
     */
    public static function retrieve($id, $opts = null)
    {
        return self::_retrieve($id, $opts);
    }

    /**
     * @param array|null $params
     * @param array|string|null $opts
     *
     * @return Collection of BalanceTransactions
     */
    public static function all($params = null, $opts = null)
    {
        return self::_all($params, $opts);
    }
}
