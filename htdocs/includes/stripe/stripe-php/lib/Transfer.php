<?php

// File generated from our OpenAPI spec

namespace Stripe;

/**
 * A <code>Transfer</code> object is created when you move funds between Stripe
 * accounts as part of Connect.
 *
 * Before April 6, 2017, transfers also represented movement of funds from a Stripe
 * account to a card or bank account. This behavior has since been split out into a
 * <a href="https://stripe.com/docs/api#payout_object">Payout</a> object, with
 * corresponding payout endpoints. For more information, read about the <a
 * href="https://stripe.com/docs/transfer-payout-split">transfer/payout split</a>.
 *
 * Related guide: <a
 * href="https://stripe.com/docs/connect/charges-transfers">Creating Separate
 * Charges and Transfers</a>.
 *
 * @property string $id Unique identifier for the object.
 * @property string $object String representing the object's type. Objects of the same type share the same value.
 * @property int $amount Amount in %s to be transferred.
 * @property int $amount_reversed Amount in %s reversed (can be less than the amount attribute on the transfer if a partial reversal was issued).
 * @property null|string|\Stripe\BalanceTransaction $balance_transaction Balance transaction that describes the impact of this transfer on your account balance.
 * @property int $created Time that this record of the transfer was first created.
 * @property string $currency Three-letter <a href="https://www.iso.org/iso-4217-currency-codes.html">ISO currency code</a>, in lowercase. Must be a <a href="https://stripe.com/docs/currencies">supported currency</a>.
 * @property null|string $description An arbitrary string attached to the object. Often useful for displaying to users.
 * @property null|string|\Stripe\Account $destination ID of the Stripe account the transfer was sent to.
 * @property string|\Stripe\Charge $destination_payment If the destination is a Stripe account, this will be the ID of the payment that the destination account received for the transfer.
 * @property bool $livemode Has the value <code>true</code> if the object exists in live mode or the value <code>false</code> if the object exists in test mode.
 * @property \Stripe\StripeObject $metadata Set of <a href="https://stripe.com/docs/api/metadata">key-value pairs</a> that you can attach to an object. This can be useful for storing additional information about the object in a structured format.
 * @property \Stripe\Collection $reversals A list of reversals that have been applied to the transfer.
 * @property bool $reversed Whether the transfer has been fully reversed. If the transfer is only partially reversed, this attribute will still be false.
 * @property null|string|\Stripe\Charge $source_transaction ID of the charge or payment that was used to fund the transfer. If null, the transfer was funded from the available balance.
 * @property null|string $source_type The source balance this transfer came from. One of <code>card</code>, <code>fpx</code>, or <code>bank_account</code>.
 * @property null|string $transfer_group A string that identifies this transaction as part of a group. See the <a href="https://stripe.com/docs/connect/charges-transfers#transfer-options">Connect documentation</a> for details.
 */
class Transfer extends ApiResource
{
    const OBJECT_NAME = 'transfer';

    use ApiOperations\All;
    use ApiOperations\Create;
    use ApiOperations\NestedResource;
    use ApiOperations\Retrieve;
    use ApiOperations\Update;

    const SOURCE_TYPE_ALIPAY_ACCOUNT = 'alipay_account';
    const SOURCE_TYPE_BANK_ACCOUNT = 'bank_account';
    const SOURCE_TYPE_CARD = 'card';
    const SOURCE_TYPE_FINANCING = 'financing';

    /**
     * @param null|array $params
     * @param null|array|string $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return Transfer the canceled transfer
     */
    public function cancel($params = null, $opts = null)
    {
        $url = $this->instanceUrl() . '/cancel';
        list($response, $opts) = $this->_request('post', $url, $params, $opts);
        $this->refreshFrom($response, $opts);

        return $this;
    }

    const PATH_REVERSALS = '/reversals';

    /**
     * @param string $id the ID of the transfer on which to retrieve the transfer reversals
     * @param null|array $params
     * @param null|array|string $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\Collection the list of transfer reversals
     */
    public static function allReversals($id, $params = null, $opts = null)
    {
        return self::_allNestedResources($id, static::PATH_REVERSALS, $params, $opts);
    }

    /**
     * @param string $id the ID of the transfer on which to create the transfer reversal
     * @param null|array $params
     * @param null|array|string $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\TransferReversal
     */
    public static function createReversal($id, $params = null, $opts = null)
    {
        return self::_createNestedResource($id, static::PATH_REVERSALS, $params, $opts);
    }

    /**
     * @param string $id the ID of the transfer to which the transfer reversal belongs
     * @param string $reversalId the ID of the transfer reversal to retrieve
     * @param null|array $params
     * @param null|array|string $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\TransferReversal
     */
    public static function retrieveReversal($id, $reversalId, $params = null, $opts = null)
    {
        return self::_retrieveNestedResource($id, static::PATH_REVERSALS, $reversalId, $params, $opts);
    }

    /**
     * @param string $id the ID of the transfer to which the transfer reversal belongs
     * @param string $reversalId the ID of the transfer reversal to update
     * @param null|array $params
     * @param null|array|string $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\TransferReversal
     */
    public static function updateReversal($id, $reversalId, $params = null, $opts = null)
    {
        return self::_updateNestedResource($id, static::PATH_REVERSALS, $reversalId, $params, $opts);
    }
}
