<?php

namespace Stripe;

/**
 * Class AlipayAccount
 *
 * @package Stripe
 *
 * @deprecated Alipay accounts are deprecated. Please use the sources API instead.
 * @link https://stripe.com/docs/sources/alipay
 */
class AlipayAccount extends ApiResource
{

    const OBJECT_NAME = "alipay_account";

    use ApiOperations\Delete;
    use ApiOperations\Update;

    /**
     * @return string The instance URL for this resource. It needs to be special
     *    cased because it doesn't fit into the standard resource pattern.
     */
    public function instanceUrl()
    {
        if ($this['customer']) {
            $base = Customer::classUrl();
            $parent = $this['customer'];
            $path = 'sources';
        } else {
            $msg = "Alipay accounts cannot be accessed without a customer ID.";
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
     *
     * @deprecated Alipay accounts are deprecated. Please use the sources API instead.
     * @link https://stripe.com/docs/sources/alipay
     */
    public static function retrieve($_id, $_opts = null)
    {
        $msg = "Alipay accounts cannot be accessed without a customer ID. " .
               "Retrieve an Alipay account using \$customer->sources->retrieve('alipay_account_id') instead.";
        throw new Error\InvalidRequest($msg, null);
    }

    /**
     * @param string $_id
     * @param array|null $_params
     * @param array|string|null $_options
     *
     * @throws \Stripe\Error\InvalidRequest
     *
     * @deprecated Alipay accounts are deprecated. Please use the sources API instead.
     * @link https://stripe.com/docs/sources/alipay
     */
    public static function update($_id, $_params = null, $_options = null)
    {
        $msg = "Alipay accounts cannot be accessed without a customer ID. " .
               "Call save() on \$customer->sources->retrieve('alipay_account_id') instead.";
        throw new Error\InvalidRequest($msg, null);
    }
}
