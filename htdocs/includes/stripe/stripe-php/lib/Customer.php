<?php

// File generated from our OpenAPI spec

namespace Stripe;

/**
 * <code>Customer</code> objects allow you to perform recurring charges, and to
 * track multiple charges, that are associated with the same customer. The API
 * allows you to create, delete, and update your customers. You can retrieve
 * individual customers as well as a list of all your customers.
 *
 * Related guide: <a
 * href="https://stripe.com/docs/payments/save-during-payment">Save a card during
 * payment</a>.
 *
 * @property string $id Unique identifier for the object.
 * @property string $object String representing the object's type. Objects of the same type share the same value.
 * @property null|\Stripe\StripeObject $address The customer's address.
 * @property int $balance Current balance, if any, being stored on the customer. If negative, the customer has credit to apply to their next invoice. If positive, the customer has an amount owed that will be added to their next invoice. The balance does not refer to any unpaid invoices; it solely takes into account amounts that have yet to be successfully applied to any invoice. This balance is only taken into account as invoices are finalized.
 * @property int $created Time at which the object was created. Measured in seconds since the Unix epoch.
 * @property null|string $currency Three-letter <a href="https://stripe.com/docs/currencies">ISO code for the currency</a> the customer can be charged in for recurring billing purposes.
 * @property null|string|\Stripe\Account|\Stripe\AlipayAccount|\Stripe\BankAccount|\Stripe\BitcoinReceiver|\Stripe\Card|\Stripe\Source $default_source <p>ID of the default payment source for the customer.</p><p>If you are using payment methods created via the PaymentMethods API, see the <a href="https://stripe.com/docs/api/customers/object#customer_object-invoice_settings-default_payment_method">invoice_settings.default_payment_method</a> field instead.</p>
 * @property null|bool $delinquent <p>When the customer's latest invoice is billed by charging automatically, <code>delinquent</code> is <code>true</code> if the invoice's latest charge failed. When the customer's latest invoice is billed by sending an invoice, <code>delinquent</code> is <code>true</code> if the invoice isn't paid by its due date.</p><p>If an invoice is marked uncollectible by <a href="https://stripe.com/docs/billing/automatic-collection">dunning</a>, <code>delinquent</code> doesn't get reset to <code>false</code>.</p>
 * @property null|string $description An arbitrary string attached to the object. Often useful for displaying to users.
 * @property null|\Stripe\Discount $discount Describes the current discount active on the customer, if there is one.
 * @property null|string $email The customer's email address.
 * @property null|string $invoice_prefix The prefix for the customer used to generate unique invoice numbers.
 * @property \Stripe\StripeObject $invoice_settings
 * @property bool $livemode Has the value <code>true</code> if the object exists in live mode or the value <code>false</code> if the object exists in test mode.
 * @property \Stripe\StripeObject $metadata Set of <a href="https://stripe.com/docs/api/metadata">key-value pairs</a> that you can attach to an object. This can be useful for storing additional information about the object in a structured format.
 * @property null|string $name The customer's full name or business name.
 * @property int $next_invoice_sequence The suffix of the customer's next invoice number, e.g., 0001.
 * @property null|string $phone The customer's phone number.
 * @property null|string[] $preferred_locales The customer's preferred locales (languages), ordered by preference.
 * @property null|\Stripe\StripeObject $shipping Mailing and shipping address for the customer. Appears on invoices emailed to this customer.
 * @property \Stripe\Collection $sources The customer's payment sources, if any.
 * @property \Stripe\Collection $subscriptions The customer's current subscriptions, if any.
 * @property null|string $tax_exempt Describes the customer's tax exemption status. One of <code>none</code>, <code>exempt</code>, or <code>reverse</code>. When set to <code>reverse</code>, invoice and receipt PDFs include the text <strong>&quot;Reverse charge&quot;</strong>.
 * @property \Stripe\Collection $tax_ids The customer's tax IDs.
 */
class Customer extends ApiResource
{
    const OBJECT_NAME = 'customer';

    use ApiOperations\All;
    use ApiOperations\Create;
    use ApiOperations\Delete;
    use ApiOperations\NestedResource;
    use ApiOperations\Retrieve;
    use ApiOperations\Update;

    const TAX_EXEMPT_EXEMPT = 'exempt';
    const TAX_EXEMPT_NONE = 'none';
    const TAX_EXEMPT_REVERSE = 'reverse';

    public static function getSavedNestedResources()
    {
        static $savedNestedResources = null;
        if (null === $savedNestedResources) {
            $savedNestedResources = new Util\Set([
                'source',
            ]);
        }

        return $savedNestedResources;
    }

    /**
     * @param null|array $params
     * @param null|array|string $opts
     *
     * @return \Stripe\Customer the updated customer
     */
    public function deleteDiscount($params = null, $opts = null)
    {
        $url = $this->instanceUrl() . '/discount';
        list($response, $opts) = $this->_request('delete', $url, $params, $opts);
        $this->refreshFrom(['discount' => null], $opts, true);
    }

    const PATH_BALANCE_TRANSACTIONS = '/balance_transactions';

    /**
     * @param string $id the ID of the customer on which to retrieve the customer balance transactions
     * @param null|array $params
     * @param null|array|string $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\Collection the list of customer balance transactions
     */
    public static function allBalanceTransactions($id, $params = null, $opts = null)
    {
        return self::_allNestedResources($id, static::PATH_BALANCE_TRANSACTIONS, $params, $opts);
    }

    /**
     * @param string $id the ID of the customer on which to create the customer balance transaction
     * @param null|array $params
     * @param null|array|string $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\CustomerBalanceTransaction
     */
    public static function createBalanceTransaction($id, $params = null, $opts = null)
    {
        return self::_createNestedResource($id, static::PATH_BALANCE_TRANSACTIONS, $params, $opts);
    }

    /**
     * @param string $id the ID of the customer to which the customer balance transaction belongs
     * @param string $balanceTransactionId the ID of the customer balance transaction to retrieve
     * @param null|array $params
     * @param null|array|string $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\CustomerBalanceTransaction
     */
    public static function retrieveBalanceTransaction($id, $balanceTransactionId, $params = null, $opts = null)
    {
        return self::_retrieveNestedResource($id, static::PATH_BALANCE_TRANSACTIONS, $balanceTransactionId, $params, $opts);
    }

    /**
     * @param string $id the ID of the customer to which the customer balance transaction belongs
     * @param string $balanceTransactionId the ID of the customer balance transaction to update
     * @param null|array $params
     * @param null|array|string $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\CustomerBalanceTransaction
     */
    public static function updateBalanceTransaction($id, $balanceTransactionId, $params = null, $opts = null)
    {
        return self::_updateNestedResource($id, static::PATH_BALANCE_TRANSACTIONS, $balanceTransactionId, $params, $opts);
    }

    const PATH_SOURCES = '/sources';

    /**
     * @param string $id the ID of the customer on which to retrieve the payment sources
     * @param null|array $params
     * @param null|array|string $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\Collection the list of payment sources (AlipayAccount, BankAccount, BitcoinReceiver, Card or Source)
     */
    public static function allSources($id, $params = null, $opts = null)
    {
        return self::_allNestedResources($id, static::PATH_SOURCES, $params, $opts);
    }

    /**
     * @param string $id the ID of the customer on which to create the payment source
     * @param null|array $params
     * @param null|array|string $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\AlipayAccount|\Stripe\BankAccount|\Stripe\BitcoinReceiver|\Stripe\Card|\Stripe\Source
     */
    public static function createSource($id, $params = null, $opts = null)
    {
        return self::_createNestedResource($id, static::PATH_SOURCES, $params, $opts);
    }

    /**
     * @param string $id the ID of the customer to which the payment source belongs
     * @param string $sourceId the ID of the payment source to delete
     * @param null|array $params
     * @param null|array|string $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\AlipayAccount|\Stripe\BankAccount|\Stripe\BitcoinReceiver|\Stripe\Card|\Stripe\Source
     */
    public static function deleteSource($id, $sourceId, $params = null, $opts = null)
    {
        return self::_deleteNestedResource($id, static::PATH_SOURCES, $sourceId, $params, $opts);
    }

    /**
     * @param string $id the ID of the customer to which the payment source belongs
     * @param string $sourceId the ID of the payment source to retrieve
     * @param null|array $params
     * @param null|array|string $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\AlipayAccount|\Stripe\BankAccount|\Stripe\BitcoinReceiver|\Stripe\Card|\Stripe\Source
     */
    public static function retrieveSource($id, $sourceId, $params = null, $opts = null)
    {
        return self::_retrieveNestedResource($id, static::PATH_SOURCES, $sourceId, $params, $opts);
    }

    /**
     * @param string $id the ID of the customer to which the payment source belongs
     * @param string $sourceId the ID of the payment source to update
     * @param null|array $params
     * @param null|array|string $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\AlipayAccount|\Stripe\BankAccount|\Stripe\BitcoinReceiver|\Stripe\Card|\Stripe\Source
     */
    public static function updateSource($id, $sourceId, $params = null, $opts = null)
    {
        return self::_updateNestedResource($id, static::PATH_SOURCES, $sourceId, $params, $opts);
    }

    const PATH_TAX_IDS = '/tax_ids';

    /**
     * @param string $id the ID of the customer on which to retrieve the tax ids
     * @param null|array $params
     * @param null|array|string $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\Collection the list of tax ids
     */
    public static function allTaxIds($id, $params = null, $opts = null)
    {
        return self::_allNestedResources($id, static::PATH_TAX_IDS, $params, $opts);
    }

    /**
     * @param string $id the ID of the customer on which to create the tax id
     * @param null|array $params
     * @param null|array|string $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\TaxId
     */
    public static function createTaxId($id, $params = null, $opts = null)
    {
        return self::_createNestedResource($id, static::PATH_TAX_IDS, $params, $opts);
    }

    /**
     * @param string $id the ID of the customer to which the tax id belongs
     * @param string $taxIdId the ID of the tax id to delete
     * @param null|array $params
     * @param null|array|string $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\TaxId
     */
    public static function deleteTaxId($id, $taxIdId, $params = null, $opts = null)
    {
        return self::_deleteNestedResource($id, static::PATH_TAX_IDS, $taxIdId, $params, $opts);
    }

    /**
     * @param string $id the ID of the customer to which the tax id belongs
     * @param string $taxIdId the ID of the tax id to retrieve
     * @param null|array $params
     * @param null|array|string $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\TaxId
     */
    public static function retrieveTaxId($id, $taxIdId, $params = null, $opts = null)
    {
        return self::_retrieveNestedResource($id, static::PATH_TAX_IDS, $taxIdId, $params, $opts);
    }
}
