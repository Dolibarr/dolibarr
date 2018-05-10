<?php

namespace Stripe\Util;

use Stripe\StripeObject;

abstract class Util
{
    private static $isMbstringAvailable = null;
    private static $isHashEqualsAvailable = null;

    /**
     * Whether the provided array (or other) is a list rather than a dictionary.
     * A list is defined as an array for which all the keys are consecutive
     * integers starting at 0. Empty arrays are considered to be lists.
     *
     * @param array|mixed $array
     * @return boolean true if the given object is a list.
     */
    public static function isList($array)
    {
        if (!is_array($array)) {
            return false;
        }
        if ($array === []) {
            return true;
        }
        if (array_keys($array) !== range(0, count($array) - 1)) {
            return false;
        }
        return true;
    }

    /**
     * Recursively converts the PHP Stripe object to an array.
     *
     * @param array $values The PHP Stripe object to convert.
     * @return array
     */
    public static function convertStripeObjectToArray($values)
    {
        $results = [];
        foreach ($values as $k => $v) {
            // FIXME: this is an encapsulation violation
            if ($k[0] == '_') {
                continue;
            }
            if ($v instanceof StripeObject) {
                $results[$k] = $v->__toArray(true);
            } elseif (is_array($v)) {
                $results[$k] = self::convertStripeObjectToArray($v);
            } else {
                $results[$k] = $v;
            }
        }
        return $results;
    }

    /**
     * Converts a response from the Stripe API to the corresponding PHP object.
     *
     * @param array $resp The response from the Stripe API.
     * @param array $opts
     * @return StripeObject|array
     */
    public static function convertToStripeObject($resp, $opts)
    {
        $types = [
            // data structures
            'list' => 'Stripe\\Collection',

            // business objects
            'account' => 'Stripe\\Account',
            'alipay_account' => 'Stripe\\AlipayAccount',
            'apple_pay_domain' => 'Stripe\\ApplePayDomain',
            'application_fee' => 'Stripe\\ApplicationFee',
            'balance' => 'Stripe\\Balance',
            'balance_transaction' => 'Stripe\\BalanceTransaction',
            'bank_account' => 'Stripe\\BankAccount',
            'bitcoin_receiver' => 'Stripe\\BitcoinReceiver',
            'bitcoin_transaction' => 'Stripe\\BitcoinTransaction',
            'card' => 'Stripe\\Card',
            'charge' => 'Stripe\\Charge',
            'country_spec' => 'Stripe\\CountrySpec',
            'coupon' => 'Stripe\\Coupon',
            'customer' => 'Stripe\\Customer',
            'dispute' => 'Stripe\\Dispute',
            'ephemeral_key' => 'Stripe\\EphemeralKey',
            'event' => 'Stripe\\Event',
            'exchange_rate' => 'Stripe\\ExchangeRate',
            'fee_refund' => 'Stripe\\ApplicationFeeRefund',
            'file_upload' => 'Stripe\\FileUpload',
            'invoice' => 'Stripe\\Invoice',
            'invoiceitem' => 'Stripe\\InvoiceItem',
            'login_link' => 'Stripe\\LoginLink',
            'order' => 'Stripe\\Order',
            'order_return' => 'Stripe\\OrderReturn',
            'payout' => 'Stripe\\Payout',
            'plan' => 'Stripe\\Plan',
            'product' => 'Stripe\\Product',
            'recipient' => 'Stripe\\Recipient',
            'recipient_transfer' => 'Stripe\\RecipientTransfer',
            'refund' => 'Stripe\\Refund',
            'sku' => 'Stripe\\SKU',
            'source' => 'Stripe\\Source',
            'source_transaction' => 'Stripe\\SourceTransaction',
            'subscription' => 'Stripe\\Subscription',
            'subscription_item' => 'Stripe\\SubscriptionItem',
            'three_d_secure' => 'Stripe\\ThreeDSecure',
            'token' => 'Stripe\\Token',
            'topup' => 'Stripe\\Topup',
            'transfer' => 'Stripe\\Transfer',
            'transfer_reversal' => 'Stripe\\TransferReversal',
        ];
        if (self::isList($resp)) {
            $mapped = [];
            foreach ($resp as $i) {
                array_push($mapped, self::convertToStripeObject($i, $opts));
            }
            return $mapped;
        } elseif (is_array($resp)) {
            if (isset($resp['object']) && is_string($resp['object']) && isset($types[$resp['object']])) {
                $class = $types[$resp['object']];
            } else {
                $class = 'Stripe\\StripeObject';
            }
            return $class::constructFrom($resp, $opts);
        } else {
            return $resp;
        }
    }

    /**
     * @param string|mixed $value A string to UTF8-encode.
     *
     * @return string|mixed The UTF8-encoded string, or the object passed in if
     *    it wasn't a string.
     */
    public static function utf8($value)
    {
        if (self::$isMbstringAvailable === null) {
            self::$isMbstringAvailable = function_exists('mb_detect_encoding');

            if (!self::$isMbstringAvailable) {
                trigger_error("It looks like the mbstring extension is not enabled. " .
                    "UTF-8 strings will not properly be encoded. Ask your system " .
                    "administrator to enable the mbstring extension, or write to " .
                    "support@stripe.com if you have any questions.", E_USER_WARNING);
            }
        }

        if (is_string($value) && self::$isMbstringAvailable && mb_detect_encoding($value, "UTF-8", true) != "UTF-8") {
            return utf8_encode($value);
        } else {
            return $value;
        }
    }

    /**
     * Compares two strings for equality. The time taken is independent of the
     * number of characters that match.
     *
     * @param string $a one of the strings to compare.
     * @param string $b the other string to compare.
     * @return bool true if the strings are equal, false otherwise.
     */
    public static function secureCompare($a, $b)
    {
        if (self::$isHashEqualsAvailable === null) {
            self::$isHashEqualsAvailable = function_exists('hash_equals');
        }

        if (self::$isHashEqualsAvailable) {
            return hash_equals($a, $b);
        } else {
            if (strlen($a) != strlen($b)) {
                return false;
            }

            $result = 0;
            for ($i = 0; $i < strlen($a); $i++) {
                $result |= ord($a[$i]) ^ ord($b[$i]);
            }
            return ($result == 0);
        }
    }

    /**
     * @param array $arr A map of param keys to values.
     * @param string|null $prefix
     *
     * @return string A querystring, essentially.
     */
    public static function urlEncode($arr, $prefix = null)
    {
        if (!is_array($arr)) {
            return $arr;
        }

        $r = [];
        foreach ($arr as $k => $v) {
            if (is_null($v)) {
                continue;
            }

            if ($prefix) {
                if ($k !== null && (!is_int($k) || is_array($v))) {
                    $k = $prefix."[".$k."]";
                } else {
                    $k = $prefix."[]";
                }
            }

            if (is_array($v)) {
                $enc = self::urlEncode($v, $k);
                if ($enc) {
                    $r[] = $enc;
                }
            } else {
                $r[] = urlencode($k)."=".urlencode($v);
            }
        }

        return implode("&", $r);
    }

    public static function normalizeId($id)
    {
        if (is_array($id)) {
            $params = $id;
            $id = $params['id'];
            unset($params['id']);
        } else {
            $params = [];
        }
        return [$id, $params];
    }
}
