<?php

// File generated from our OpenAPI spec

namespace Stripe;

/**
 * You can store multiple cards on a customer in order to charge the customer
 * later. You can also store multiple debit cards on a recipient in order to
 * transfer to those cards later.
 *
 * Related guide: <a href="https://stripe.com/docs/sources/cards">Card Payments
 * with Sources</a>.
 *
 * @property string $id Unique identifier for the object.
 * @property string $object String representing the object's type. Objects of the same type share the same value.
 * @property null|string|\Stripe\Account $account The account this card belongs to. This attribute will not be in the card object if the card belongs to a customer or recipient instead.
 * @property null|string $address_city City/District/Suburb/Town/Village.
 * @property null|string $address_country Billing address country, if provided when creating card.
 * @property null|string $address_line1 Address line 1 (Street address/PO Box/Company name).
 * @property null|string $address_line1_check If <code>address_line1</code> was provided, results of the check: <code>pass</code>, <code>fail</code>, <code>unavailable</code>, or <code>unchecked</code>.
 * @property null|string $address_line2 Address line 2 (Apartment/Suite/Unit/Building).
 * @property null|string $address_state State/County/Province/Region.
 * @property null|string $address_zip ZIP or postal code.
 * @property null|string $address_zip_check If <code>address_zip</code> was provided, results of the check: <code>pass</code>, <code>fail</code>, <code>unavailable</code>, or <code>unchecked</code>.
 * @property null|string[] $available_payout_methods A set of available payout methods for this card. Only values from this set should be passed as the <code>method</code> when creating a payout.
 * @property string $brand Card brand. Can be <code>American Express</code>, <code>Diners Club</code>, <code>Discover</code>, <code>JCB</code>, <code>MasterCard</code>, <code>UnionPay</code>, <code>Visa</code>, or <code>Unknown</code>.
 * @property null|string $country Two-letter ISO code representing the country of the card. You could use this attribute to get a sense of the international breakdown of cards you've collected.
 * @property null|string $currency Three-letter <a href="https://stripe.com/docs/payouts">ISO code for currency</a>. Only applicable on accounts (not customers or recipients). The card can be used as a transfer destination for funds in this currency.
 * @property null|string|\Stripe\Customer $customer The customer that this card belongs to. This attribute will not be in the card object if the card belongs to an account or recipient instead.
 * @property null|string $cvc_check If a CVC was provided, results of the check: <code>pass</code>, <code>fail</code>, <code>unavailable</code>, or <code>unchecked</code>. A result of unchecked indicates that CVC was provided but hasn't been checked yet. Checks are typically performed when attaching a card to a Customer object, or when creating a charge. For more details, see <a href="https://support.stripe.com/questions/check-if-a-card-is-valid-without-a-charge">Check if a card is valid without a charge</a>.
 * @property null|bool $default_for_currency Whether this card is the default external account for its currency.
 * @property null|string $dynamic_last4 (For tokenized numbers only.) The last four digits of the device account number.
 * @property int $exp_month Two-digit number representing the card's expiration month.
 * @property int $exp_year Four-digit number representing the card's expiration year.
 * @property null|string $fingerprint Uniquely identifies this particular card number. You can use this attribute to check whether two customers who’ve signed up with you are using the same card number, for example. For payment methods that tokenize card information (Apple Pay, Google Pay), the tokenized number might be provided instead of the underlying card number.
 * @property string $funding Card funding type. Can be <code>credit</code>, <code>debit</code>, <code>prepaid</code>, or <code>unknown</code>.
 * @property string $last4 The last four digits of the card.
 * @property null|\Stripe\StripeObject $metadata Set of <a href="https://stripe.com/docs/api/metadata">key-value pairs</a> that you can attach to an object. This can be useful for storing additional information about the object in a structured format.
 * @property null|string $name Cardholder name.
 * @property null|string|\Stripe\Recipient $recipient The recipient that this card belongs to. This attribute will not be in the card object if the card belongs to a customer or account instead.
 * @property null|string $tokenization_method If the card number is tokenized, this is the method that was used. Can be <code>android_pay</code> (includes Google Pay), <code>apple_pay</code>, <code>masterpass</code>, <code>visa_checkout</code>, or null.
 */
class Card extends ApiResource
{
    const OBJECT_NAME = 'card';

    use ApiOperations\Delete;
    use ApiOperations\Update;

    /**
     * Possible string representations of the CVC check status.
     *
     * @see https://stripe.com/docs/api/cards/object#card_object-cvc_check
     */
    const CVC_CHECK_FAIL = 'fail';
    const CVC_CHECK_PASS = 'pass';
    const CVC_CHECK_UNAVAILABLE = 'unavailable';
    const CVC_CHECK_UNCHECKED = 'unchecked';

    /**
     * Possible string representations of the funding of the card.
     *
     * @see https://stripe.com/docs/api/cards/object#card_object-funding
     */
    const FUNDING_CREDIT = 'credit';
    const FUNDING_DEBIT = 'debit';
    const FUNDING_PREPAID = 'prepaid';
    const FUNDING_UNKNOWN = 'unknown';

    /**
     * Possible string representations of the tokenization method when using Apple Pay or Google Pay.
     *
     * @see https://stripe.com/docs/api/cards/object#card_object-tokenization_method
     */
    const TOKENIZATION_METHOD_APPLE_PAY = 'apple_pay';
    const TOKENIZATION_METHOD_GOOGLE_PAY = 'google_pay';

    /**
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
            $msg = 'Cards cannot be accessed without a customer ID, account ID or recipient ID.';

            throw new Exception\UnexpectedValueException($msg);
        }
        $parentExtn = \urlencode(Util\Util::utf8($parent));
        $extn = \urlencode(Util\Util::utf8($this['id']));

        return "{$base}/{$parentExtn}/{$path}/{$extn}";
    }

    /**
     * @param array|string $_id
     * @param null|array|string $_opts
     *
     * @throws \Stripe\Exception\BadMethodCallException
     */
    public static function retrieve($_id, $_opts = null)
    {
        $msg = 'Cards cannot be retrieved without a customer ID or an ' .
               'account ID. Retrieve a card using ' .
               "`Customer::retrieveSource('customer_id', 'card_id')` or " .
               "`Account::retrieveExternalAccount('account_id', 'card_id')`.";

        throw new Exception\BadMethodCallException($msg);
    }

    /**
     * @param string $_id
     * @param null|array $_params
     * @param null|array|string $_options
     *
     * @throws \Stripe\Exception\BadMethodCallException
     */
    public static function update($_id, $_params = null, $_options = null)
    {
        $msg = 'Cards cannot be updated without a customer ID or an ' .
               'account ID. Update a card using ' .
               "`Customer::updateSource('customer_id', 'card_id', " .
               '$updateParams)` or `Account::updateExternalAccount(' .
               "'account_id', 'card_id', \$updateParams)`.";

        throw new Exception\BadMethodCallException($msg);
    }
}
