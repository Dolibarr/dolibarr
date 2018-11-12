<?php

namespace Stripe;

/**
 * Class Token
 *
 * @property string $id
 * @property string $object
 * @property mixed $bank_account
 * @property mixed $card
 * @property string $client_ip
 * @property int $created
 * @property bool $livemode
 * @property string $type
 * @property bool $used
 *
 * @package Stripe
 */
class Token extends ApiResource
{
    use ApiOperations\Create;
    use ApiOperations\Retrieve;
}
