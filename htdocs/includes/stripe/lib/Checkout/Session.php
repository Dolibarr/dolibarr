<?php

namespace Stripe\Checkout;

/**
 * Class Session
 *
 * @property string $id
 * @property string $object
 * @property bool $livemode
 *
 * @package Stripe
 */
class Session extends \Stripe\ApiResource
{

    const OBJECT_NAME = "checkout.session";

    use \Stripe\ApiOperations\Create;
}
