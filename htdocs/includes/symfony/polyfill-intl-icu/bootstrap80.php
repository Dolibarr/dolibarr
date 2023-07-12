<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\Polyfill\Intl\Icu as p;

if (!function_exists('intl_is_failure')) {
    function intl_is_failure(?int $errorCode): bool { return p\Icu::isFailure((int) $errorCode); }
}
if (!function_exists('intl_get_error_code')) {
    function intl_get_error_code(): int { return p\Icu::getErrorCode(); }
}
if (!function_exists('intl_get_error_message')) {
    function intl_get_error_message(): string { return p\Icu::getErrorMessage(); }
}
if (!function_exists('intl_error_name')) {
    function intl_error_name(?int $errorCode): string { return p\Icu::getErrorName((int) $errorCode); }
}
