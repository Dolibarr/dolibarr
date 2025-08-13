<?php

// File generated from our OpenAPI spec

namespace Stripe\Service\Identity;

/**
 * Service factory class for API resources in the Identity namespace.
 *
 * @property VerificationReportService $verificationReports
 * @property VerificationSessionService $verificationSessions
 */
class IdentityServiceFactory extends \Stripe\Service\AbstractServiceFactory
{
    /**
     * @var array<string, string>
     */
    private static $classMap = [
        'verificationReports' => VerificationReportService::class,
        'verificationSessions' => VerificationSessionService::class,
    ];

    protected function getServiceClass($name)
    {
        return \array_key_exists($name, self::$classMap) ? self::$classMap[$name] : null;
    }
}
