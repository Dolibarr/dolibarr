<?php

// File generated from our OpenAPI spec

namespace Stripe\FinancialConnections;

/**
 * @property string $id Unique identifier for the object.
 * @property string $object String representing the object's type. Objects of the same type share the same value.
 * @property null|string $email The email address of the owner.
 * @property string $name The full name of the owner.
 * @property string $ownership The ownership object that this owner belongs to.
 * @property null|string $phone The raw phone number of the owner.
 * @property null|string $raw_address The raw physical address of the owner.
 * @property null|int $refreshed_at The timestamp of the refresh that updated this owner.
 */
class AccountOwner extends \Stripe\ApiResource
{
    const OBJECT_NAME = 'financial_connections.account_owner';
}
