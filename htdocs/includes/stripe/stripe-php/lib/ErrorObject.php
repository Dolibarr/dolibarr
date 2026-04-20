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
 * @property string $request_log_url A URL to the request log entry in your
 *    dashboard.
 * @property SetupIntent $setup_intent The SetupIntent object for errors
 *    returned on a request involving a SetupIntent.
 * @property StripeObject $source The source object for errors returned on a
 *    request involving a source.
 * @property string $type The type of error returned. One of `api_error`,
 *   `card_error`, `idempotency_error`, or `invalid_request_error`.
 */
class ErrorObject extends StripeObject
{
    /**
     * Possible string representations of an error's code.
     *
     * @see https://stripe.com/docs/error-codes
     */
    const CODE_ACCOUNT_COUNTRY_INVALID_ADDRESS = 'account_country_invalid_address';
    const CODE_ACCOUNT_ERROR_COUNTRY_CHANGE_REQUIRES_ADDITIONAL_STEPS = 'account_error_country_change_requires_additional_steps';
    const CODE_ACCOUNT_INFORMATION_MISMATCH = 'account_information_mismatch';
    const CODE_ACCOUNT_INVALID = 'account_invalid';
    const CODE_ACCOUNT_NUMBER_INVALID = 'account_number_invalid';
    const CODE_ACSS_DEBIT_SESSION_INCOMPLETE = 'acss_debit_session_incomplete';
    const CODE_ALIPAY_UPGRADE_REQUIRED = 'alipay_upgrade_required';
    const CODE_AMOUNT_TOO_LARGE = 'amount_too_large';
    const CODE_AMOUNT_TOO_SMALL = 'amount_too_small';
    const CODE_API_KEY_EXPIRED = 'api_key_expired';
    const CODE_AUTHENTICATION_REQUIRED = 'authentication_required';
    const CODE_BALANCE_INSUFFICIENT = 'balance_insufficient';
    const CODE_BANK_ACCOUNT_BAD_ROUTING_NUMBERS = 'bank_account_bad_routing_numbers';
    const CODE_BANK_ACCOUNT_DECLINED = 'bank_account_declined';
    const CODE_BANK_ACCOUNT_EXISTS = 'bank_account_exists';
    const CODE_BANK_ACCOUNT_UNUSABLE = 'bank_account_unusable';
    const CODE_BANK_ACCOUNT_UNVERIFIED = 'bank_account_unverified';
    const CODE_BANK_ACCOUNT_VERIFICATION_FAILED = 'bank_account_verification_failed';
    const CODE_BILLING_INVALID_MANDATE = 'billing_invalid_mandate';
    const CODE_BITCOIN_UPGRADE_REQUIRED = 'bitcoin_upgrade_required';
    const CODE_CARD_DECLINE_RATE_LIMIT_EXCEEDED = 'card_decline_rate_limit_exceeded';
    const CODE_CARD_DECLINED = 'card_declined';
    const CODE_CARDHOLDER_PHONE_NUMBER_REQUIRED = 'cardholder_phone_number_required';
    const CODE_CHARGE_ALREADY_CAPTURED = 'charge_already_captured';
    const CODE_CHARGE_ALREADY_REFUNDED = 'charge_already_refunded';
    const CODE_CHARGE_DISPUTED = 'charge_disputed';
    const CODE_CHARGE_EXCEEDS_SOURCE_LIMIT = 'charge_exceeds_source_limit';
    const CODE_CHARGE_EXPIRED_FOR_CAPTURE = 'charge_expired_for_capture';
    const CODE_CHARGE_INVALID_PARAMETER = 'charge_invalid_parameter';
    const CODE_CLEARING_CODE_UNSUPPORTED = 'clearing_code_unsupported';
    const CODE_COUNTRY_CODE_INVALID = 'country_code_invalid';
    const CODE_COUNTRY_UNSUPPORTED = 'country_unsupported';
    const CODE_COUPON_EXPIRED = 'coupon_expired';
    const CODE_CUSTOMER_MAX_PAYMENT_METHODS = 'customer_max_payment_methods';
    const CODE_CUSTOMER_MAX_SUBSCRIPTIONS = 'customer_max_subscriptions';
    const CODE_DEBIT_NOT_AUTHORIZED = 'debit_not_authorized';
    const CODE_EMAIL_INVALID = 'email_invalid';
    const CODE_EXPIRED_CARD = 'expired_card';
    const CODE_IDEMPOTENCY_KEY_IN_USE = 'idempotency_key_in_use';
    const CODE_INCORRECT_ADDRESS = 'incorrect_address';
    const CODE_INCORRECT_CVC = 'incorrect_cvc';
    const CODE_INCORRECT_NUMBER = 'incorrect_number';
    const CODE_INCORRECT_ZIP = 'incorrect_zip';
    const CODE_INSTANT_PAYOUTS_LIMIT_EXCEEDED = 'instant_payouts_limit_exceeded';
    const CODE_INSTANT_PAYOUTS_UNSUPPORTED = 'instant_payouts_unsupported';
    const CODE_INSUFFICIENT_FUNDS = 'insufficient_funds';
    const CODE_INTENT_INVALID_STATE = 'intent_invalid_state';
    const CODE_INTENT_VERIFICATION_METHOD_MISSING = 'intent_verification_method_missing';
    const CODE_INVALID_CARD_TYPE = 'invalid_card_type';
    const CODE_INVALID_CHARACTERS = 'invalid_characters';
    const CODE_INVALID_CHARGE_AMOUNT = 'invalid_charge_amount';
    const CODE_INVALID_CVC = 'invalid_cvc';
    const CODE_INVALID_EXPIRY_MONTH = 'invalid_expiry_month';
    const CODE_INVALID_EXPIRY_YEAR = 'invalid_expiry_year';
    const CODE_INVALID_NUMBER = 'invalid_number';
    const CODE_INVALID_SOURCE_USAGE = 'invalid_source_usage';
    const CODE_INVOICE_NO_CUSTOMER_LINE_ITEMS = 'invoice_no_customer_line_items';
    const CODE_INVOICE_NO_PAYMENT_METHOD_TYPES = 'invoice_no_payment_method_types';
    const CODE_INVOICE_NO_SUBSCRIPTION_LINE_ITEMS = 'invoice_no_subscription_line_items';
    const CODE_INVOICE_NOT_EDITABLE = 'invoice_not_editable';
    const CODE_INVOICE_ON_BEHALF_OF_NOT_EDITABLE = 'invoice_on_behalf_of_not_editable';
    const CODE_INVOICE_PAYMENT_INTENT_REQUIRES_ACTION = 'invoice_payment_intent_requires_action';
    const CODE_INVOICE_UPCOMING_NONE = 'invoice_upcoming_none';
    const CODE_LIVEMODE_MISMATCH = 'livemode_mismatch';
    const CODE_LOCK_TIMEOUT = 'lock_timeout';
    const CODE_MISSING = 'missing';
    const CODE_NO_ACCOUNT = 'no_account';
    const CODE_NOT_ALLOWED_ON_STANDARD_ACCOUNT = 'not_allowed_on_standard_account';
    const CODE_OUT_OF_INVENTORY = 'out_of_inventory';
    const CODE_PARAMETER_INVALID_EMPTY = 'parameter_invalid_empty';
    const CODE_PARAMETER_INVALID_INTEGER = 'parameter_invalid_integer';
    const CODE_PARAMETER_INVALID_STRING_BLANK = 'parameter_invalid_string_blank';
    const CODE_PARAMETER_INVALID_STRING_EMPTY = 'parameter_invalid_string_empty';
    const CODE_PARAMETER_MISSING = 'parameter_missing';
    const CODE_PARAMETER_UNKNOWN = 'parameter_unknown';
    const CODE_PARAMETERS_EXCLUSIVE = 'parameters_exclusive';
    const CODE_PAYMENT_INTENT_ACTION_REQUIRED = 'payment_intent_action_required';
    const CODE_PAYMENT_INTENT_AUTHENTICATION_FAILURE = 'payment_intent_authentication_failure';
    const CODE_PAYMENT_INTENT_INCOMPATIBLE_PAYMENT_METHOD = 'payment_intent_incompatible_payment_method';
    const CODE_PAYMENT_INTENT_INVALID_PARAMETER = 'payment_intent_invalid_parameter';
    const CODE_PAYMENT_INTENT_KONBINI_REJECTED_CONFIRMATION_NUMBER = 'payment_intent_konbini_rejected_confirmation_number';
    const CODE_PAYMENT_INTENT_MANDATE_INVALID = 'payment_intent_mandate_invalid';
    const CODE_PAYMENT_INTENT_PAYMENT_ATTEMPT_EXPIRED = 'payment_intent_payment_attempt_expired';
    const CODE_PAYMENT_INTENT_PAYMENT_ATTEMPT_FAILED = 'payment_intent_payment_attempt_failed';
    const CODE_PAYMENT_INTENT_UNEXPECTED_STATE = 'payment_intent_unexpected_state';
    const CODE_PAYMENT_METHOD_BANK_ACCOUNT_ALREADY_VERIFIED = 'payment_method_bank_account_already_verified';
    const CODE_PAYMENT_METHOD_BANK_ACCOUNT_BLOCKED = 'payment_method_bank_account_blocked';
    const CODE_PAYMENT_METHOD_BILLING_DETAILS_ADDRESS_MISSING = 'payment_method_billing_details_address_missing';
    const CODE_PAYMENT_METHOD_CURRENCY_MISMATCH = 'payment_method_currency_mismatch';
    const CODE_PAYMENT_METHOD_INVALID_PARAMETER = 'payment_method_invalid_parameter';
    const CODE_PAYMENT_METHOD_INVALID_PARAMETER_TESTMODE = 'payment_method_invalid_parameter_testmode';
    const CODE_PAYMENT_METHOD_MICRODEPOSIT_FAILED = 'payment_method_microdeposit_failed';
    const CODE_PAYMENT_METHOD_MICRODEPOSIT_VERIFICATION_AMOUNTS_INVALID = 'payment_method_microdeposit_verification_amounts_invalid';
    const CODE_PAYMENT_METHOD_MICRODEPOSIT_VERIFICATION_AMOUNTS_MISMATCH = 'payment_method_microdeposit_verification_amounts_mismatch';
    const CODE_PAYMENT_METHOD_MICRODEPOSIT_VERIFICATION_ATTEMPTS_EXCEEDED = 'payment_method_microdeposit_verification_attempts_exceeded';
    const CODE_PAYMENT_METHOD_MICRODEPOSIT_VERIFICATION_DESCRIPTOR_CODE_MISMATCH = 'payment_method_microdeposit_verification_descriptor_code_mismatch';
    const CODE_PAYMENT_METHOD_MICRODEPOSIT_VERIFICATION_TIMEOUT = 'payment_method_microdeposit_verification_timeout';
    const CODE_PAYMENT_METHOD_PROVIDER_DECLINE = 'payment_method_provider_decline';
    const CODE_PAYMENT_METHOD_PROVIDER_TIMEOUT = 'payment_method_provider_timeout';
    const CODE_PAYMENT_METHOD_UNACTIVATED = 'payment_method_unactivated';
    const CODE_PAYMENT_METHOD_UNEXPECTED_STATE = 'payment_method_unexpected_state';
    const CODE_PAYMENT_METHOD_UNSUPPORTED_TYPE = 'payment_method_unsupported_type';
    const CODE_PAYOUTS_NOT_ALLOWED = 'payouts_not_allowed';
    const CODE_PLATFORM_ACCOUNT_REQUIRED = 'platform_account_required';
    const CODE_PLATFORM_API_KEY_EXPIRED = 'platform_api_key_expired';
    const CODE_POSTAL_CODE_INVALID = 'postal_code_invalid';
    const CODE_PROCESSING_ERROR = 'processing_error';
    const CODE_PRODUCT_INACTIVE = 'product_inactive';
    const CODE_RATE_LIMIT = 'rate_limit';
    const CODE_REFER_TO_CUSTOMER = 'refer_to_customer';
    const CODE_REFUND_DISPUTED_PAYMENT = 'refund_disputed_payment';
    const CODE_RESOURCE_ALREADY_EXISTS = 'resource_already_exists';
    const CODE_RESOURCE_MISSING = 'resource_missing';
    const CODE_RETURN_INTENT_ALREADY_PROCESSED = 'return_intent_already_processed';
    const CODE_ROUTING_NUMBER_INVALID = 'routing_number_invalid';
    const CODE_SECRET_KEY_REQUIRED = 'secret_key_required';
    const CODE_SEPA_UNSUPPORTED_ACCOUNT = 'sepa_unsupported_account';
    const CODE_SETUP_ATTEMPT_FAILED = 'setup_attempt_failed';
    const CODE_SETUP_INTENT_AUTHENTICATION_FAILURE = 'setup_intent_authentication_failure';
    const CODE_SETUP_INTENT_INVALID_PARAMETER = 'setup_intent_invalid_parameter';
    const CODE_SETUP_INTENT_SETUP_ATTEMPT_EXPIRED = 'setup_intent_setup_attempt_expired';
    const CODE_SETUP_INTENT_UNEXPECTED_STATE = 'setup_intent_unexpected_state';
    const CODE_SHIPPING_CALCULATION_FAILED = 'shipping_calculation_failed';
    const CODE_SKU_INACTIVE = 'sku_inactive';
    const CODE_STATE_UNSUPPORTED = 'state_unsupported';
    const CODE_TAX_ID_INVALID = 'tax_id_invalid';
    const CODE_TAXES_CALCULATION_FAILED = 'taxes_calculation_failed';
    const CODE_TERMINAL_LOCATION_COUNTRY_UNSUPPORTED = 'terminal_location_country_unsupported';
    const CODE_TESTMODE_CHARGES_ONLY = 'testmode_charges_only';
    const CODE_TLS_VERSION_UNSUPPORTED = 'tls_version_unsupported';
    const CODE_TOKEN_ALREADY_USED = 'token_already_used';
    const CODE_TOKEN_IN_USE = 'token_in_use';
    const CODE_TRANSFER_SOURCE_BALANCE_PARAMETERS_MISMATCH = 'transfer_source_balance_parameters_mismatch';
    const CODE_TRANSFERS_NOT_ALLOWED = 'transfers_not_allowed';
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
