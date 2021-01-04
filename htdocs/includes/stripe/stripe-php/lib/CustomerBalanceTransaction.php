<?php

namespace Stripe;

/**
 * Class CustomerBalanceTransaction
 *
 * @package Stripe
 *
 * @property string $id
 * @property string $object
 * @property int $amount
 * @property string $credit_note
 * @property int $created
 * @property string $currency
 * @property string $customer
 * @property string $description
 * @property int $ending_balance
 * @property string $invoice
 * @property bool $livemode
 * @property StripeObject $metadata
 * @property string $type
 */
class CustomerBalanceTransaction extends ApiResource
{
    const OBJECT_NAME = "customer_balance_transaction";

    /**
     * Possible string representations of a balance transaction's type.
     * @link https://stripe.com/docs/api/customers/customer_balance_transaction_object#customer_balance_transaction_object-type
     */
    const TYPE_ADJUSTEMENT             = 'adjustment';
    const TYPE_APPLIED_TO_INVOICE      = 'applied_to_invoice';
    const TYPE_CREDIT_NOTE             = 'credit_note';
    const TYPE_INITIAL                 = 'initial';
    const TYPE_INVOICE_TOO_LARGE       = 'invoice_too_large';
    const TYPE_INVOICE_TOO_SMALL       = 'invoice_too_small';
    const TYPE_UNSPENT_RECEIVER_CREDIT = 'unspent_receiver_credit';

    /**
     * @return string The API URL for this balance transaction.
     */
    public function instanceUrl()
    {
        $id = $this['id'];
        $customer = $this['customer'];
        if (!$id) {
            throw new Error\InvalidRequest(
                "Could not determine which URL to request: class instance has invalid ID: $id",
                null
            );
        }
        $id = Util\Util::utf8($id);
        $customer = Util\Util::utf8($customer);

        $base = Customer::classUrl();
        $customerExtn = urlencode($customer);
        $extn = urlencode($id);
        return "$base/$customerExtn/balance_transactions/$extn";
    }

    /**
     * @param array|string $_id
     * @param array|string|null $_opts
     *
     * @throws \Stripe\Error\InvalidRequest
     */
    public static function retrieve($_id, $_opts = null)
    {
        $msg = "Customer Balance Transactions cannot be accessed without a customer ID. " .
               "Retrieve a balance transaction using Customer::retrieveBalanceTransaction('cus_123', 'cbtxn_123') instead.";
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
        $msg = "Customer Balance Transactions cannot be accessed without a customer ID. " .
               "Update a balance transaction using Customer::updateBalanceTransaction('cus_123', 'cbtxn_123', \$params) instead.";
        throw new Error\InvalidRequest($msg, null);
    }
}
