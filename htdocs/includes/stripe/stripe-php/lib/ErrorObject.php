<?php

namespace Stripe;

/**
 * Class ErrorObject.
 *
 * @property string $charge For card errors, the ID of the failed charge.
 * @property string $code For some errors that could be handled
 *    programmatically, a short string indicating the error code reported.
 * @property string $decline_code For card errors resulting from a card issuer
 *    decline, a short string indicating the card issuer's reason for the
 *    decline if they provide one.
 * @property string $doc_url A URL to more information about the error code
 *    reported.
 * @property string $message A human-readable message providing more details
 *    about the error. For card errors, these messages can be shown to your
 *    users.
 * @property string $param If the error is parameter-specific, the parameter
 *    related to the error. For example, you can use this to display a message
 *    near the correct form field.
 * @property PaymentIntent $payment_intent The PaymentIntent object for errors
 *    returned on a request involving a PaymentIntent.
 * @property PaymentMethod $payment_method The PaymentMethod object for errors
 *    returned on a request involving a PaymentMethod.
 * @property string $payment_method_type If the error is specific to the type
 *    of payment method, the payment method type that had a problem. This
 *    field is only populated for invoice-related errors.
 * @property SetupIntent $setup_intent The SetupIntent object for errors
 *    returned on a request involving a SetupIntent.
 * @property StripeObject $source The source object for errors returned on a
 *    request involving a source.
 * @property string $type The type of error returned. One of
 *    `api_connection_error`, `api_error`, `authentication_error`,
 *    `card_error`, `idempotency_error`, `invalid_request_error`, or
 *    `rate_limit_error`.
 */
class ErrorObject extends StripeObject
{
    /**
     * Possible string representations of an error's code.
     *
     * @see https://stripe.com/docs/error-codes
     */
    const CODE_ACCOUNT_ALREADY_EXISTS = 'account_already_exists';
    const CODE_ACCOUNT_COUNTRY_INVALID_ADDRESS = 'account_country_invalid_address';
    const CODE_ACCOUNT_INVALID = 'account_invalid';
    const CODE_ACCOUNT_NUMBER_INVALID = 'account_number_invalid';
    const CODE_ALIPAY_UPGRADE_REQUIRED = 'alipay_upgrade_required';
    const CODE_AMOUNT_TOO_LARGE = 'amount_too_large';
    const CODE_AMOUNT_TOO_SMALL = 'amount_too_small';
    const CODE_API_KEY_EXPIRED = 'api_key_expired';
    const CODE_BALANCE_INSUFFICIENT = 'balance_insufficient';
    const CODE_BANK_ACCOUNT_EXISTS = 'bank_account_exists';
    const CODE_BANK_ACCOUNT_UNUSABLE = 'bank_account_unusable';
    const CODE_BANK_ACCOUNT_UNVERIFIED = 'bank_account_unverified';
    const CODE_BITCOIN_UPGRADE_REQUIRED = 'bitcoin_upgrade_required';
    const CODE_CARD_DECLINED = 'card_declined';
    const CODE_CHARGE_ALREADY_CAPTURED = 'charge_already_captured';
    const CODE_CHARGE_ALREADY_REFUNDED = 'charge_already_refunded';
    const CODE_CHARGE_DISPUTED = 'charge_disputed';
    const CODE_CHARGE_EXCEEDS_SOURCE_LIMIT = 'charge_exceeds_source_limit';
    const CODE_CHARGE_EXPIRED_FOR_CAPTURE = 'charge_expired_for_capture';
    const CODE_COUNTRY_UNSUPPORTED = 'country_unsupported';
    const CODE_COUPON_EXPIRED = 'coupon_expired';
    const CODE_CUSTOMER_MAX_SUBSCRIPTIONS = 'customer_max_subscriptions';
    const CODE_EMAIL_INVALID = 'email_invalid';
    const CODE_EXPIRED_CARD = 'expired_card';
    const CODE_IDEMPOTENCY_KEY_IN_USE = 'idempotency_key_in_use';
    const CODE_INCORRECT_ADDRESS = 'incorrect_address';
    const CODE_INCORRECT_CVC = 'incorrect_cvc';
    const CODE_INCORRECT_NUMBER = 'incorrect_number';
    const CODE_INCORRECT_ZIP = 'incorrect_zip';
    const CODE_INSTANT_PAYOUTS_UNSUPPORTED = 'instant_payouts_unsupported';
    const CODE_INVALID_CARD_TYPE = 'invalid_card_type';
    const CODE_INVALID_CHARGE_AMOUNT = 'invalid_charge_amount';
    const CODE_INVALID_CVC = 'invalid_cvc';
    const CODE_INVALID_EXPIRY_MONTH = 'invalid_expiry_month';
    const CODE_INVALID_EXPIRY_YEAR = 'invalid_expiry_year';
    const CODE_INVALID_NUMBER = 'invalid_number';
    const CODE_INVALID_SOURCE_USAGE = 'invalid_source_usage';
    const CODE_INVOICE_NO_CUSTOMER_LINE_ITEMS = 'invoice_no_customer_line_items';
    const CODE_INVOICE_NO_SUBSCRIPTION_LINE_ITEMS = 'invoice_no_subscription_line_items';
    const CODE_INVOICE_NOT_EDITABLE = 'invoice_not_editable';
    const CODE_INVOICE_PAYMENT_INTENT_REQUIRES_ACTION = 'invoice_payment_intent_requires_action';
    const CODE_INVOICE_UPCOMING_NONE = 'invoice_upcoming_none';
    const CODE_LIVEMODE_MISMATCH = 'livemode_mismatch';
    const CODE_LOCK_TIMEOUT = 'lock_timeout';
    const CODE_MISSING = 'missing';
    const CODE_NOT_ALLOWED_ON_STANDARD_ACCOUNT = 'not_allowed_on_standard_account';
    const CODE_ORDER_CREATION_FAILED = 'order_creation_failed';
    const CODE_ORDER_REQUIRED_SETTINGS = 'order_required_settings';
    const CODE_ORDER_STATUS_INVALID = 'order_status_invalid';
    const CODE_ORDER_UPSTREAM_TIMEOUT = 'order_upstream_timeout';
    const CODE_OUT_OF_INVENTORY = 'out_of_inventory';
    const CODE_PARAMETER_INVALID_EMPTY = 'parameter_invalid_empty';
    const CODE_PARAMETER_INVALID_INTEGER = 'parameter_invalid_integer';
    const CODE_PARAMETER_INVALID_STRING_BLANK = 'parameter_invalid_string_blank';
    const CODE_PARAMETER_INVALID_STRING_EMPTY = 'parameter_invalid_string_empty';
    const CODE_PARAMETER_MISSING = 'parameter_missing';
    const CODE_PARAMETER_UNKNOWN = 'parameter_unknown';
    const CODE_PARAMETERS_EXCLUSIVE = 'parameters_exclusive';
    const CODE_PAYMENT_INTENT_AUTHENTICATION_FAILURE = 'payment_intent_authentication_failure';
    const CODE_PAYMENT_INTENT_INCOMPATIBLE_PAYMENT_METHOD = 'payment_intent_incompatible_payment_method';
    const CODE_PAYMENT_INTENT_INVALID_PARAMETER = 'payment_intent_invalid_parameter';
    const CODE_PAYMENT_INTENT_PAYMENT_ATTEMPT_FAILED = 'payment_intent_payment_attempt_failed';
    const CODE_PAYMENT_INTENT_UNEXPECTED_STATE = 'payment_intent_unexpected_state';
    const CODE_PAYMENT_METHOD_UNACTIVATED = 'payment_method_unactivated';
    const CODE_PAYMENT_METHOD_UNEXPECTED_STATE = 'payment_method_unexpected_state';
    const CODE_PAYOUTS_NOT_ALLOWED = 'payouts_not_allowed';
    const CODE_PLATFORM_API_KEY_EXPIRED = 'platform_api_key_expired';
    const CODE_POSTAL_CODE_INVALID = 'postal_code_invalid';
    const CODE_PROCESSING_ERROR = 'processing_error';
    const CODE_PRODUCT_INACTIVE = 'product_inactive';
    const CODE_RATE_LIMIT = 'rate_limit';
    const CODE_RESOURCE_ALREADY_EXISTS = 'resource_already_exists';
    const CODE_RESOURCE_MISSING = 'resource_missing';
    const CODE_ROUTING_NUMBER_INVALID = 'routing_number_invalid';
    const CODE_SECRET_KEY_REQUIRED = 'secret_key_required';
    const CODE_SEPA_UNSUPPORTED_ACCOUNT = 'sepa_unsupported_account';
    const CODE_SETUP_ATTEMPT_FAILED = 'setup_attempt_failed';
    const CODE_SETUP_INTENT_AUTHENTICATION_FAILURE = 'setup_intent_authentication_failure';
    const CODE_SETUP_INTENT_UNEXPECTED_STATE = 'setup_intent_unexpected_state';
    const CODE_SHIPPING_CALCULATION_FAILED = 'shipping_calculation_failed';
    const CODE_SKU_INACTIVE = 'sku_inactive';
    const CODE_STATE_UNSUPPORTED = 'state_unsupported';
    const CODE_TAX_ID_INVALID = 'tax_id_invalid';
    const CODE_TAXES_CALCULATION_FAILED = 'taxes_calculation_failed';
    const CODE_TESTMODE_CHARGES_ONLY = 'testmode_charges_only';
    const CODE_TLS_VERSION_UNSUPPORTED = 'tls_version_unsupported';
    const CODE_TOKEN_ALREADY_USED = 'token_already_used';
    const CODE_TOKEN_IN_USE = 'token_in_use';
    const CODE_TRANSFERS_NOT_ALLOWED = 'transfers_not_allowed';
    const CODE_UPSTREAM_ORDER_CREATION_FAILED = 'upstream_order_creation_failed';
    const CODE_URL_INVALID = 'url_invalid';

    /**
     * Refreshes this object using the provided values.
     *
     * @param array $values
     * @param null|array|string|Util\RequestOptions $opts
     * @param bool $partial defaults to false
     */
    public function refreshFrom($values, $opts, $partial = false)
    {
        // Unlike most other API resources, the API will omit attributes in
        // error objects when they have a null value. We manually set default
        // values here to facilitate generic error handling.
        $values = \array_merge([
            'charge' => null,
            'code' => null,
            'decline_code' => null,
            'doc_url' => null,
            'message' => null,
            'param' => null,
            'payment_intent' => null,
            'payment_method' => null,
            'setup_intent' => null,
            'source' => null,
            'type' => null,
        ], $values);
        parent::refreshFrom($values, $opts, $partial);
    }
}
