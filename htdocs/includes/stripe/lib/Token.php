<?php

namespace Stripe;

/**
 * Class Token
 *
 * @property string $id
 * @property string $object
 * @property BankAccount $bank_account
 * @property Card $card
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
}
