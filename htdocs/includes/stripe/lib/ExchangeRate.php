<?php

namespace Stripe;

/**
 * Class ExchangeRate
 *
 * @package Stripe
 */
class ExchangeRate extends ApiResource
{

    const OBJECT_NAME = "exchange_rate";

    use ApiOperations\All;
    use ApiOperations\Retrieve;
}
