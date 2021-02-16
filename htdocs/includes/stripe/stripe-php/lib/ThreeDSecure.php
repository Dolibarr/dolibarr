<?php

// File generated from our OpenAPI spec

namespace Stripe;

/**
 * Cardholder authentication via 3D Secure is initiated by creating a <code>3D
 * Secure</code> object. Once the object has been created, you can use it to
 * authenticate the cardholder and create a charge.
 *
 * @property string $id Unique identifier for the object.
 * @property string $object String representing the object's type. Objects of the same type share the same value.
 * @property int $amount Amount of the charge that you will create when authentication completes.
 * @property bool $authenticated True if the cardholder went through the authentication flow and their bank indicated that authentication succeeded.
 * @property \Stripe\Card $card <p>You can store multiple cards on a customer in order to charge the customer later. You can also store multiple debit cards on a recipient in order to transfer to those cards later.</p><p>Related guide: <a href="https://stripe.com/docs/sources/cards">Card Payments with Sources</a>.</p>
 * @property int $created Time at which the object was created. Measured in seconds since the Unix epoch.
 * @property string $currency Three-letter <a href="https://www.iso.org/iso-4217-currency-codes.html">ISO currency code</a>, in lowercase. Must be a <a href="https://stripe.com/docs/currencies">supported currency</a>.
 * @property bool $livemode Has the value <code>true</code> if the object exists in live mode or the value <code>false</code> if the object exists in test mode.
 * @property null|string $redirect_url If present, this is the URL that you should send the cardholder to for authentication. If you are going to use Stripe.js to display the authentication page in an iframe, you should use the value &quot;_callback&quot;.
 * @property string $status Possible values are <code>redirect_pending</code>, <code>succeeded</code>, or <code>failed</code>. When the cardholder can be authenticated, the object starts with status <code>redirect_pending</code>. When liability will be shifted to the cardholder's bank (either because the cardholder was successfully authenticated, or because the bank has not implemented 3D Secure, the object wlil be in status <code>succeeded</code>. <code>failed</code> indicates that authentication was attempted unsuccessfully.
 */
class ThreeDSecure extends ApiResource
{
    const OBJECT_NAME = 'three_d_secure';

    use ApiOperations\Create;
    use ApiOperations\Retrieve;

    /**
     * @return string the endpoint URL for the given class
     */
    public static function classUrl()
    {
        return '/v1/3d_secure';
    }
}
