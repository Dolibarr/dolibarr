<?php

namespace Stripe\Util;

/**
 * Describes a logger instance.
 *
 * This is a subset of the interface of the same name in the PSR-3 logger
 * interface. We guarantee to keep it compatible, but we'd redefined it here so
 * that we don't have to pull in the extra dependencies for users who don't want
 * it.
 *
 * See https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md
 * for the full interface specification.
 *
 * The message MUST be a string or object implementing __toString().
 *
 * The message MAY contain placeholders in the form: {foo} where foo
 * will be replaced by the context data in key "foo".
 *
 * The context array can contain arbitrary data, the only assumption that
 * can be made by implementors is that if an Exception instance is given
 * to produce a stack trace, it MUST be in a key named "exception".
 */
interface LoggerInterface
{
    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param string $message
     */
    public function error($message, array $context = []);
}
