<?php

namespace Stripe;

/**
 * Class Topup
 *
 * @property string $id
 * @property string $object
 * @property int $amount
 * @property string $balance_transaction
 * @property int $created
 * @property string $currency
 * @property string $description
 * @property int $expected_availability_date
 * @property string $failure_code
 * @property string $failure_message
 * @property bool $livemode
 * @property StripeObject $metadata
 * @property mixed $source
 * @property string $statement_descriptor
 * @property string $status
 * @property string $transfer_group
 *
 * @package Stripe
 */
class Topup extends ApiResource
{

    const OBJECT_NAME = "topup";

    use ApiOperations\All;
    use ApiOperations\Create;
    use ApiOperations\Retrieve;
    use ApiOperations\Update;

    /**
     * Possible string representations of the status of the top-up.
     * @link https://stripe.com/docs/api/topups/object#topup_object-status
     */
    const STATUS_CANCELED  = 'canceled';
    const STATUS_FAILED    = 'failed';
    const STATUS_PENDING   = 'pending';
    const STATUS_REVERSED  = 'reversed';
    const STATUS_SUCCEEDED = 'succeeded';

    /**
     * @param array|null $params
     * @param array|string|null $options
     *
     * @return Topup The canceled topup.
     */
    public function cancel($params = null, $options = null)
    {
        $url = $this->instanceUrl() . '/cancel';
        list($response, $opts) = $this->_request('post', $url, $params, $options);
        $this->refreshFrom($response, $opts);
        return $this;
    }
}
