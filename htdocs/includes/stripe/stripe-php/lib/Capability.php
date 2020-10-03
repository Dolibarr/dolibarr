<?php

namespace Stripe;

/**
 * Class Capability
 *
 * @package Stripe
 *
 * @property string $id
 * @property string $object
 * @property string $account
 * @property bool $requested
 * @property int $requested_at
 * @property mixed $requirements
 * @property string $status
 */
class Capability extends ApiResource
{
    const OBJECT_NAME = "capability";

    use ApiOperations\Update;

    /**
     * Possible string representations of a capability's status.
     * @link https://stripe.com/docs/api/capabilities/object#capability_object-status
     */
    const STATUS_ACTIVE      = 'active';
    const STATUS_INACTIVE    = 'inactive';
    const STATUS_PENDING     = 'pending';
    const STATUS_UNREQUESTED = 'unrequested';

    /**
     * @return string The API URL for this Stripe account reversal.
     */
    public function instanceUrl()
    {
        $id = $this['id'];
        $account = $this['account'];
        if (!$id) {
            throw new Error\InvalidRequest(
                "Could not determine which URL to request: " .
                "class instance has invalid ID: $id",
                null
            );
        }
        $id = Util\Util::utf8($id);
        $account = Util\Util::utf8($account);

        $base = Account::classUrl();
        $accountExtn = urlencode($account);
        $extn = urlencode($id);
        return "$base/$accountExtn/capabilities/$extn";
    }

    /**
     * @param array|string $_id
     * @param array|string|null $_opts
     *
     * @throws \Stripe\Error\InvalidRequest
     */
    public static function retrieve($_id, $_opts = null)
    {
        $msg = "Capabilities cannot be accessed without an account ID. " .
               "Retrieve a Capability using \$account->retrieveCapability('acap_123') instead.";
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
        $msg = "Capabilities cannot be accessed without an account ID. " .
               "Update a Capability using \$account->updateCapability('acap_123') instead.";
        throw new Error\InvalidRequest($msg, null);
    }
}
