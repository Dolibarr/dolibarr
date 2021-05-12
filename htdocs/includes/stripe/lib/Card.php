<?php

namespace Stripe;

/**
 * Class Card
 *
 * @property string $id
 * @property string $object
<<<<<<< HEAD
=======
 * @property string $account
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
 * @property string $address_city
 * @property string $address_country
 * @property string $address_line1
 * @property string $address_line1_check
 * @property string $address_line2
 * @property string $address_state
 * @property string $address_zip
 * @property string $address_zip_check
<<<<<<< HEAD
 * @property string $brand
 * @property string $country
 * @property string $customer
 * @property string $cvc_check
=======
 * @property string[] $available_payout_methods
 * @property string $brand
 * @property string $country
 * @property string $currency
 * @property string $customer
 * @property string $cvc_check
 * @property bool $default_for_currency
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
 * @property string $dynamic_last4
 * @property int $exp_month
 * @property int $exp_year
 * @property string $fingerprint
 * @property string $funding
 * @property string $last4
 * @property StripeObject $metadata
 * @property string $name
<<<<<<< HEAD
=======
 * @property string $recipient
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
 * @property string $tokenization_method
 *
 * @package Stripe
 */
class Card extends ApiResource
{
<<<<<<< HEAD
=======

    const OBJECT_NAME = "card";

>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    use ApiOperations\Delete;
    use ApiOperations\Update;

    /**
<<<<<<< HEAD
=======
     * Possible string representations of the CVC check status.
     * @link https://stripe.com/docs/api/cards/object#card_object-cvc_check
     */
    const CVC_CHECK_FAIL        = 'fail';
    const CVC_CHECK_PASS        = 'pass';
    const CVC_CHECK_UNAVAILABLE = 'unavailable';
    const CVC_CHECK_UNCHECKED   = 'unchecked';

    /**
     * Possible string representations of the funding of the card.
     * @link https://stripe.com/docs/api/cards/object#card_object-funding
     */
    const FUNDING_CREDIT  = 'credit';
    const FUNDING_DEBIT   = 'debit';
    const FUNDING_PREPAID = 'prepaid';
    const FUNDING_UNKNOWN = 'unknown';

    /**
     * Possible string representations of the tokenization method when using Apple Pay or Google Pay.
     * @link https://stripe.com/docs/api/cards/object#card_object-tokenization_method
     */
    const TOKENIZATION_METHOD_APPLE_PAY  = 'apple_pay';
    const TOKENIZATION_METHOD_GOOGLE_PAY = 'google_pay';

    /**
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
     * @return string The instance URL for this resource. It needs to be special
     *    cased because cards are nested resources that may belong to different
     *    top-level resources.
     */
    public function instanceUrl()
    {
        if ($this['customer']) {
            $base = Customer::classUrl();
            $parent = $this['customer'];
            $path = 'sources';
        } elseif ($this['account']) {
            $base = Account::classUrl();
            $parent = $this['account'];
            $path = 'external_accounts';
        } elseif ($this['recipient']) {
            $base = Recipient::classUrl();
            $parent = $this['recipient'];
            $path = 'cards';
        } else {
            $msg = "Cards cannot be accessed without a customer ID, account ID or recipient ID.";
            throw new Error\InvalidRequest($msg, null);
        }
        $parentExtn = urlencode(Util\Util::utf8($parent));
        $extn = urlencode(Util\Util::utf8($this['id']));
        return "$base/$parentExtn/$path/$extn";
    }

    /**
     * @param array|string $_id
     * @param array|string|null $_opts
     *
     * @throws \Stripe\Error\InvalidRequest
     */
    public static function retrieve($_id, $_opts = null)
    {
        $msg = "Cards cannot be accessed without a customer, recipient or account ID. " .
               "Retrieve a card using \$customer->sources->retrieve('card_id'), " .
<<<<<<< HEAD
               "\$recipient->cards->retrieve('card_id'), or";
=======
               "\$recipient->cards->retrieve('card_id'), or " .
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
               "\$account->external_accounts->retrieve('card_id') instead.";
        throw new Error\InvalidRequest($msg, null);
    }

    /**
     * @param string $_id
     * @param array|null $_params
     * @param array|string|null $_options
     *
     * @throws \Stripe\Error\InvalidRequest
     */
    public static function update($_id, $_params = null, $_options = null)
    {
        $msg = "Cards cannot be accessed without a customer, recipient or account ID. " .
               "Call save() on \$customer->sources->retrieve('card_id'), " .
<<<<<<< HEAD
               "\$recipient->cards->retrieve('card_id'), or";
=======
               "\$recipient->cards->retrieve('card_id'), or " .
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
               "\$account->external_accounts->retrieve('card_id') instead.";
        throw new Error\InvalidRequest($msg, null);
    }
}
