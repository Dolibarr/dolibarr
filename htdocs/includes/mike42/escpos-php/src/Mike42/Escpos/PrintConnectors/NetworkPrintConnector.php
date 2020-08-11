<?php
/**
 * This file is part of escpos-php: PHP receipt printer library for use with
 * ESC/POS-compatible thermal and impact printers.
 *
 * Copyright (c) 2014-18 Michael Billington < michael.billington@gmail.com >,
 * incorporating modifications by others. See CONTRIBUTORS.md for a full list.
 *
 * This software is distributed under the terms of the MIT license. See LICENSE.md
 * for details.
 */

namespace Mike42\Escpos\PrintConnectors;

use Exception;

/**
 * PrintConnector for directly opening a network socket to a printer to send it commands.
 */
class NetworkPrintConnector extends FilePrintConnector
{
    /**
     * Construct a new NetworkPrintConnector
     *
     * @param string $ip IP address or hostname to use.
     * @param string $port The port number to connect on.
     * @param string $timeout The connection timeout, in seconds.
     * @throws Exception Where the socket cannot be opened.
     */
    public function __construct($ip, $port = "9100", $timeout = false)
    {
        // Default to 60 if default_socket_timeout isn't defined in the ini
        $defaultSocketTimeout = ini_get("default_socket_timeout") ?: 60;
        $timeout = $timeout ?: $defaultSocketTimeout;

        $this -> fp = @fsockopen($ip, $port, $errno, $errstr, $timeout);
        if ($this -> fp === false) {
            throw new Exception("Cannot initialise NetworkPrintConnector: " . $errstr);
        }
    }
}
