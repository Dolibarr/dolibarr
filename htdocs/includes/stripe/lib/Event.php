<?php

namespace Stripe;

/**
 * Class Event
 *
 * @property string $id
 * @property string $object
 * @property string $api_version
 * @property int $created
 * @property mixed $data
 * @property bool $livemode
 * @property int $pending_webhooks
 * @property string $request
 * @property string $type
 *
 * @package Stripe
 */
class Event extends ApiResource
{
    /**
     * @param string $id The ID of the event to retrieve.
     * @param array|string|null $opts
     *
     * @return Event
     */
    public static function retrieve($id, $opts = null)
    {
        return self::_retrieve($id, $opts);
    }

    /**
     * @param array|null $params
     * @param array|string|null $opts
     *
     * @return Collection of Events
     */
    public static function all($params = null, $opts = null)
    {
        return self::_all($params, $opts);
    }
}
