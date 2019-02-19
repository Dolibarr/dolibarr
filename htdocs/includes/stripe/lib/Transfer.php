<?php

namespace Stripe;

/**
 * Class Transfer
 *
 * @property string $id
 * @property string $object
 * @property int $amount
 * @property int $amount_reversed
 * @property string $balance_transaction
 * @property int $created
 * @property string $currency
 * @property string $description
 * @property string $destination
 * @property string $destination_payment
 * @property bool $livemode
 * @property StripeObject $metadata
 * @property Collection $reversals
 * @property bool $reversed
 * @property string $source_transaction
 * @property string $source_type
 * @property string $transfer_group
 *
 * @package Stripe
 */
class Transfer extends ApiResource
{

    const OBJECT_NAME = "transfer";

    use ApiOperations\All;
    use ApiOperations\Create;
    use ApiOperations\NestedResource;
    use ApiOperations\Retrieve;
    use ApiOperations\Update;

    const PATH_REVERSALS = '/reversals';

    /**
     * @return TransferReversal The created transfer reversal.
     */
    public function reverse($params = null, $opts = null)
    {
        $url = $this->instanceUrl() . '/reversals';
        list($response, $opts) = $this->_request('post', $url, $params, $opts);
        $this->refreshFrom($response, $opts);
        return $this;
    }

    /**
     * @return Transfer The canceled transfer.
     */
    public function cancel()
    {
        $url = $this->instanceUrl() . '/cancel';
        list($response, $opts) = $this->_request('post', $url);
        $this->refreshFrom($response, $opts);
        return $this;
    }

    /**
     * @param string|null $id The ID of the transfer on which to create the reversal.
     * @param array|null $params
     * @param array|string|null $opts
     *
     * @return TransferReversal
     */
    public static function createReversal($id, $params = null, $opts = null)
    {
        return self::_createNestedResource($id, static::PATH_REVERSALS, $params, $opts);
    }

    /**
     * @param string|null $id The ID of the transfer to which the reversal belongs.
     * @param array|null $reversalId The ID of the reversal to retrieve.
     * @param array|null $params
     * @param array|string|null $opts
     *
     * @return TransferReversal
     */
    public static function retrieveReversal($id, $reversalId, $params = null, $opts = null)
    {
        return self::_retrieveNestedResource($id, static::PATH_REVERSALS, $reversalId, $params, $opts);
    }

    /**
     * @param string|null $id The ID of the transfer to which the reversal belongs.
     * @param array|null $reversalId The ID of the reversal to update.
     * @param array|null $params
     * @param array|string|null $opts
     *
     * @return TransferReversal
     */
    public static function updateReversal($id, $reversalId, $params = null, $opts = null)
    {
        return self::_updateNestedResource($id, static::PATH_REVERSALS, $reversalId, $params, $opts);
    }

    /**
     * @param string|null $id The ID of the transfer on which to retrieve the reversals.
     * @param array|null $params
     * @param array|string|null $opts
     *
     * @return TransferReversal
     */
    public static function allReversals($id, $params = null, $opts = null)
    {
        return self::_allNestedResources($id, static::PATH_REVERSALS, $params, $opts);
    }
}
