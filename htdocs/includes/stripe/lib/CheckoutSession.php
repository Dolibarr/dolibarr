<?php

namespace Stripe;

/**
 * Class CheckoutSession
 *
 * @property string $id
 * @property string $object
 * @property bool $livemode
 *
 * @package Stripe
 */
class CheckoutSession extends ApiResource
{

    const OBJECT_NAME = "checkout_session";

    use ApiOperations\Create;
}
