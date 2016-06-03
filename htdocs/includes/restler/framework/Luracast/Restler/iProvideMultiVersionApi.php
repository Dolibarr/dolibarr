<?php
namespace Luracast\Restler;

/**
 * Interface iProvideMultiVersionApi
 * @package Luracast\Restler
 *
 * @version    3.0.0rc6
 */
interface iProvideMultiVersionApi
{
    /**
     * Maximum api version supported by the api class
     * @return int
     */
    public static function __getMaximumSupportedVersion();
}