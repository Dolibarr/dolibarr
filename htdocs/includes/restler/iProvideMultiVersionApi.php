<?php
namespace Luracast\Restler;


interface iProvideMultiVersionApi {
    /**
     * Maximum api version supported by the api class
     * @return int
     */
    public static function __getMaximumSupportedVersion();
}