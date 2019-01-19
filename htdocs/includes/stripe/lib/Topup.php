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
 *
 * @package Stripe
 */
class Topup extends ApiResource
{
    use ApiOperations\All;
    use ApiOperations\Create;
    use ApiOperations\Retrieve;
    use ApiOperations\Update;
}
