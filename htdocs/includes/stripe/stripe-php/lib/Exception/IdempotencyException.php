<?php

namespace Stripe\Exception;

/**
 * IdempotencyException is thrown in cases where an idempotency key was used
 * improperly.
 */
class IdempotencyException extends ApiErrorException
{
}
