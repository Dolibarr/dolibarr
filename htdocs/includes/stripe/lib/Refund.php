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
 * @property string $description
 * @property string $failure_balance_transaction
 * @property string $failure_reason
 * @property StripeObject $metadata
 * @property string $reason
 * @property string $receipt_number
 * @property string $source_transfer_reversal
 * @property string $status
 * @property string $transfer_reversal
 *
 * @package Stripe
 */
class Refund extends ApiResource
{

    const OBJECT_NAME = "refund";

    use ApiOperations\All;
    use ApiOperations\Create;
    use ApiOperations\Retrieve;
    use ApiOperations\Update;
}
