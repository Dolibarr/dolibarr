<?php

// File generated from our OpenAPI spec

namespace Stripe\Service\TestHelpers\Issuing;

/**
 * Service factory class for API resources in the Issuing namespace.
 *
 * @property CardService $cards
 */
class IssuingServiceFactory extends \Stripe\Service\AbstractServiceFactory
{
    /**
     * @var array<string, string>
     */
    private static $classMap = [
        'cards' => CardService::class,
    ];

    protected function getServiceClass($name)
    {
        return \array_key_exists($name, self::$classMap) ? self::$classMap[$name] : null;
    }
}
