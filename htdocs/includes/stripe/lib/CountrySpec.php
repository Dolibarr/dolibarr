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
 * @property mixed $verification_fields
 *
 * @package Stripe
 */
class CountrySpec extends ApiResource
{
    use ApiOperations\All;
    use ApiOperations\Retrieve;

    /**
     * This is a special case because the country specs endpoint has an
     *    underscore in it. The parent `className` function strips underscores.
     *
     * @return string The name of the class.
     */
    public static function className()
    {
        return 'country_spec';
    }
}
