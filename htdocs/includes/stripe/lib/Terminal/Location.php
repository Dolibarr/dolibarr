<?php

namespace Stripe\Terminal;

/**
 * Class Location
 *
 * @property string $id
 * @property string $object
 * @property string $display_name
 * @property string $address_city
 * @property string $address_country
 * @property string $address_line1
 * @property string $address_line2
 * @property string $address_state
 * @property string $address_postal_code
 *
 * @package Stripe\Terminal
 */
class Location extends \Stripe\ApiResource
{
    const OBJECT_NAME = "terminal.location";

    use \Stripe\ApiOperations\All;
    use \Stripe\ApiOperations\Create;
    use \Stripe\ApiOperations\Retrieve;
    use \Stripe\ApiOperations\Update;
}
