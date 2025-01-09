<?php

namespace Stripe\Exception\OAuth;

/**
 * InvalidRequestException is thrown when a code, refresh token, or grant
 * type parameter is not provided, but was required.
 */
class InvalidRequestException extends OAuthErrorException
{
}
