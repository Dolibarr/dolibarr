<?php

namespace Stripe;

/**
 * Class Refund
 *
 * @property string $id
 * @property string $object
 * @property int $amount
 * @property string $balance_transaction
 * @property string $charge
 * @property int $created
 * @property string $currency
 * @property string $failure_balance_transaction
 * @property string failure_reason
 * @property StripeObject $metadata
 * @property mixed $reason
 * @property mixed $receipt_number
 * @property string $status
 *
 * @package Stripe
 */
class Refund extends ApiResource
{
    use ApiOperations\All;
    use ApiOperations\Create;
    use ApiOperations\Retrieve;
    use ApiOperations\Update;
}
