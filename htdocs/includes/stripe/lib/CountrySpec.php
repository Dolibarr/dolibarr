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
<<<<<<< HEAD
=======
 * @property string[] $supported_transfer_countries
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
 * @property mixed $verification_fields
 *
 * @package Stripe
 */
class CountrySpec extends ApiResource
{
<<<<<<< HEAD
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
=======

    const OBJECT_NAME = "country_spec";

    use ApiOperations\All;
    use ApiOperations\Retrieve;
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
}
