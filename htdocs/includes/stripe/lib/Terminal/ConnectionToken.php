<?php

namespace Stripe\Terminal;

/**
 * Class ConnectionToken
 *
 * @property string $secret
 *
 * @package Stripe\Terminal
 */
class ConnectionToken extends \Stripe\ApiResource
{
    const OBJECT_NAME = "terminal.connection_token";

    use \Stripe\ApiOperations\Create;
}
