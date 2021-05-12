<?php

namespace Stripe;

/**
 * Class Token
 *
 * @property string $id
 * @property string $object
<<<<<<< HEAD
 * @property mixed $bank_account
 * @property mixed $card
=======
 * @property BankAccount $bank_account
 * @property Card $card
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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
<<<<<<< HEAD
    use ApiOperations\Create;
    use ApiOperations\Retrieve;
=======

    const OBJECT_NAME = "token";

    use ApiOperations\Create;
    use ApiOperations\Retrieve;

    /**
     * Possible string representations of the token type.
     * @link https://stripe.com/docs/api/tokens/object#token_object-type
     */
    const TYPE_ACCOUNT      = 'account';
    const TYPE_BANK_ACCOUNT = 'bank_account';
    const TYPE_CARD         = 'card';
    const TYPE_PII          = 'pii';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
}
