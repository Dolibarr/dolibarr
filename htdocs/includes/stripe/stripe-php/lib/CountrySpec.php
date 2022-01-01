<?php

namespace Stripe;

/**
 * Class CountrySpec
 *
 * @property string $id
 * @property string $object
 * @property string $default_currency
 * @property mixed $supported_bank_account_currencies
 * @property string[] $supported_payment_currencies
 * @property string[] $supported_payment_methods
 * @property string[] $supported_transfer_countries
 * @property mixed $verification_fields
 *
 * @package Stripe
 */
class CountrySpec extends ApiResource
{
    const OBJECT_NAME = "country_spec";

    use ApiOperations\All;
    use ApiOperations\Retrieve;
}
