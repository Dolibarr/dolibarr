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
            \Stripe\Collection::OBJECT_NAME => 'Stripe\\Collection',

            // business objects
            \Stripe\Account::OBJECT_NAME => 'Stripe\\Account',
            \Stripe\AccountLink::OBJECT_NAME => 'Stripe\\AccountLink',
            \Stripe\AlipayAccount::OBJECT_NAME => 'Stripe\\AlipayAccount',
            \Stripe\ApplePayDomain::OBJECT_NAME => 'Stripe\\ApplePayDomain',
            \Stripe\ApplicationFee::OBJECT_NAME => 'Stripe\\ApplicationFee',
            \Stripe\Balance::OBJECT_NAME => 'Stripe\\Balance',
            \Stripe\BalanceTransaction::OBJECT_NAME => 'Stripe\\BalanceTransaction',
            \Stripe\BankAccount::OBJECT_NAME => 'Stripe\\BankAccount',
            \Stripe\BitcoinReceiver::OBJECT_NAME => 'Stripe\\BitcoinReceiver',
            \Stripe\BitcoinTransaction::OBJECT_NAME => 'Stripe\\BitcoinTransaction',
            \Stripe\Card::OBJECT_NAME => 'Stripe\\Card',
            \Stripe\Charge::OBJECT_NAME => 'Stripe\\Charge',
            \Stripe\Checkout\Session::OBJECT_NAME => 'Stripe\\Checkout\\Session',
            \Stripe\CountrySpec::OBJECT_NAME => 'Stripe\\CountrySpec',
            \Stripe\Coupon::OBJECT_NAME => 'Stripe\\Coupon',
            \Stripe\Customer::OBJECT_NAME => 'Stripe\\Customer',
            \Stripe\Discount::OBJECT_NAME => 'Stripe\\Discount',
            \Stripe\Dispute::OBJECT_NAME => 'Stripe\\Dispute',
            \Stripe\EphemeralKey::OBJECT_NAME => 'Stripe\\EphemeralKey',
            \Stripe\Event::OBJECT_NAME => 'Stripe\\Event',
            \Stripe\ExchangeRate::OBJECT_NAME => 'Stripe\\ExchangeRate',
            \Stripe\ApplicationFeeRefund::OBJECT_NAME => 'Stripe\\ApplicationFeeRefund',
            \Stripe\File::OBJECT_NAME => 'Stripe\\File',
            \Stripe\File::OBJECT_NAME_ALT => 'Stripe\\File',
            \Stripe\FileLink::OBJECT_NAME => 'Stripe\\FileLink',
            \Stripe\Invoice::OBJECT_NAME => 'Stripe\\Invoice',
            \Stripe\InvoiceItem::OBJECT_NAME => 'Stripe\\InvoiceItem',
            \Stripe\InvoiceLineItem::OBJECT_NAME => 'Stripe\\InvoiceLineItem',
            \Stripe\IssuerFraudRecord::OBJECT_NAME => 'Stripe\\IssuerFraudRecord',
            \Stripe\Issuing\Authorization::OBJECT_NAME => 'Stripe\\Issuing\\Authorization',
            \Stripe\Issuing\Card::OBJECT_NAME => 'Stripe\\Issuing\\Card',
            \Stripe\Issuing\CardDetails::OBJECT_NAME => 'Stripe\\Issuing\\CardDetails',
            \Stripe\Issuing\Cardholder::OBJECT_NAME => 'Stripe\\Issuing\\Cardholder',
            \Stripe\Issuing\Dispute::OBJECT_NAME => 'Stripe\\Issuing\\Dispute',
            \Stripe\Issuing\Transaction::OBJECT_NAME => 'Stripe\\Issuing\\Transaction',
            \Stripe\LoginLink::OBJECT_NAME => 'Stripe\\LoginLink',
            \Stripe\Order::OBJECT_NAME => 'Stripe\\Order',
            \Stripe\OrderItem::OBJECT_NAME => 'Stripe\\OrderItem',
            \Stripe\OrderReturn::OBJECT_NAME => 'Stripe\\OrderReturn',
            \Stripe\PaymentIntent::OBJECT_NAME => 'Stripe\\PaymentIntent',
            \Stripe\Payout::OBJECT_NAME => 'Stripe\\Payout',
            \Stripe\Person::OBJECT_NAME => 'Stripe\\Person',
            \Stripe\Plan::OBJECT_NAME => 'Stripe\\Plan',
            \Stripe\Product::OBJECT_NAME => 'Stripe\\Product',
            \Stripe\Radar\ValueList::OBJECT_NAME => 'Stripe\\Radar\\ValueList',
            \Stripe\Radar\ValueListItem::OBJECT_NAME => 'Stripe\\Radar\\ValueListItem',
            \Stripe\Recipient::OBJECT_NAME => 'Stripe\\Recipient',
            \Stripe\RecipientTransfer::OBJECT_NAME => 'Stripe\\RecipientTransfer',
            \Stripe\Refund::OBJECT_NAME => 'Stripe\\Refund',
            \Stripe\Reporting\ReportRun::OBJECT_NAME => 'Stripe\\Reporting\\ReportRun',
            \Stripe\Reporting\ReportType::OBJECT_NAME => 'Stripe\\Reporting\\ReportType',
            \Stripe\Review::OBJECT_NAME => 'Stripe\\Review',
            \Stripe\SKU::OBJECT_NAME => 'Stripe\\SKU',
            \Stripe\Sigma\ScheduledQueryRun::OBJECT_NAME => 'Stripe\\Sigma\\ScheduledQueryRun',
            \Stripe\Source::OBJECT_NAME => 'Stripe\\Source',
            \Stripe\SourceTransaction::OBJECT_NAME => 'Stripe\\SourceTransaction',
            \Stripe\Subscription::OBJECT_NAME => 'Stripe\\Subscription',
            \Stripe\SubscriptionItem::OBJECT_NAME => 'Stripe\\SubscriptionItem',
            \Stripe\SubscriptionSchedule::OBJECT_NAME => 'Stripe\\SubscriptionSchedule',
            \Stripe\SubscriptionScheduleRevision::OBJECT_NAME => 'Stripe\\SubscriptionScheduleRevision',
            \Stripe\ThreeDSecure::OBJECT_NAME => 'Stripe\\ThreeDSecure',
            \Stripe\Terminal\ConnectionToken::OBJECT_NAME => 'Stripe\\Terminal\\ConnectionToken',
            \Stripe\Terminal\Location::OBJECT_NAME => 'Stripe\\Terminal\\Location',
            \Stripe\Terminal\Reader::OBJECT_NAME => 'Stripe\\Terminal\\Reader',
            \Stripe\Token::OBJECT_NAME => 'Stripe\\Token',
            \Stripe\Topup::OBJECT_NAME => 'Stripe\\Topup',
            \Stripe\Transfer::OBJECT_NAME => 'Stripe\\Transfer',
            \Stripe\TransferReversal::OBJECT_NAME => 'Stripe\\TransferReversal',
            \Stripe\UsageRecord::OBJECT_NAME => 'Stripe\\UsageRecord',
            \Stripe\UsageRecordSummary::OBJECT_NAME => 'Stripe\\UsageRecordSummary',
            \Stripe\WebhookEndpoint::OBJECT_NAME => 'Stripe\\WebhookEndpoint',
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
     * Recursively goes through an array of parameters. If a parameter is an instance of
     * ApiResource, then it is replaced by the resource's ID.
     * Also clears out null values.
     *
     * @param mixed $h
     * @return mixed
     */
    public static function objectsToIds($h)
    {
        if ($h instanceof \Stripe\ApiResource) {
            return $h->id;
        } elseif (static::isList($h)) {
            $results = [];
            foreach ($h as $v) {
                array_push($results, static::objectsToIds($v));
            }
            return $results;
        } elseif (is_array($h)) {
            $results = [];
            foreach ($h as $k => $v) {
                if (is_null($v)) {
                    continue;
                }
                $results[$k] = static::objectsToIds($v);
            }
            return $results;
        } else {
            return $h;
        }
    }

    /**
     * @param array $params
     *
     * @return string
     */
    public static function encodeParameters($params)
    {
        $flattenedParams = self::flattenParams($params);
        $pieces = [];
        foreach ($flattenedParams as $param) {
            list($k, $v) = $param;
            array_push($pieces, self::urlEncode($k) . '=' . self::urlEncode($v));
        }
        return implode('&', $pieces);
    }

    /**
     * @param array $params
     * @param string|null $parentKey
     *
     * @return array
     */
    public static function flattenParams($params, $parentKey = null)
    {
        $result = [];

        foreach ($params as $key => $value) {
            $calculatedKey = $parentKey ? "{$parentKey}[{$key}]" : $key;

            if (self::isList($value)) {
                $result = array_merge($result, self::flattenParamsList($value, $calculatedKey));
            } elseif (is_array($value)) {
                $result = array_merge($result, self::flattenParams($value, $calculatedKey));
            } else {
                array_push($result, [$calculatedKey, $value]);
            }
        }

        return $result;
    }

    /**
     * @param array $value
     * @param string $calculatedKey
     *
     * @return array
     */
    public static function flattenParamsList($value, $calculatedKey)
    {
        $result = [];

        foreach ($value as $i => $elem) {
            if (self::isList($elem)) {
                $result = array_merge($result, self::flattenParamsList($elem, $calculatedKey));
            } elseif (is_array($elem)) {
                $result = array_merge($result, self::flattenParams($elem, "{$calculatedKey}[{$i}]"));
            } else {
                array_push($result, ["{$calculatedKey}[{$i}]", $elem]);
            }
        }

        return $result;
    }

    /**
     * @param string $key A string to URL-encode.
     *
     * @return string The URL-encoded string.
     */
    public static function urlEncode($key)
    {
        $s = urlencode($key);

        // Don't use strict form encoding by changing the square bracket control
        // characters back to their literals. This is fine by the server, and
        // makes these parameter strings easier to read.
        $s = str_replace('%5B', '[', $s);
        $s = str_replace('%5D', ']', $s);

        return $s;
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

    /**
     * Returns UNIX timestamp in milliseconds
     *
     * @return integer current time in millis
     */
    public static function currentTimeMillis()
    {
        return (int) round(microtime(true) * 1000);
    }
}
