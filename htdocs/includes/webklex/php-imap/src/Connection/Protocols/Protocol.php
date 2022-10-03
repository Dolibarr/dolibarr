<?php
/*
* File: ImapProtocol.php
* Category: Protocol
* Author: M.Goldenbaum
* Created: 16.09.20 18:27
* Updated: -
*
* Description:
*  -
*/

namespace Webklex\PHPIMAP\Connection\Protocols;

use Webklex\PHPIMAP\Exceptions\ConnectionFailedException;

/**
 * Class Protocol
 *
 * @package Webklex\PHPIMAP\Connection\Protocols
 */
abstract class Protocol implements ProtocolInterface {

    /**
     * Default connection timeout in seconds
     */
    protected $connection_timeout = 30;

    /**
     * @var boolean
     */
    protected $debug = false;

    /**
     * @var false|resource
     */
    public $stream = false;

    /**
     * Connection encryption method
     * @var mixed $encryption
     */
    protected $encryption = false;

    /**
     * Set to false to ignore SSL certificate validation
     * @var bool
     */
    protected $cert_validation = true;

    /**
     * Proxy settings
     * @var array
     */
    protected $proxy = [
        'socket' => null,
        'request_fulluri' => false,
        'username' => null,
        'password' => null,
    ];

    /**
     * Get an available cryptographic method
     *
     * @return int
     */
    public function getCryptoMethod() {
        // Allow the best TLS version(s) we can
        $cryptoMethod = STREAM_CRYPTO_METHOD_TLS_CLIENT;

        // PHP 5.6.7 dropped inclusion of TLS 1.1 and 1.2 in STREAM_CRYPTO_METHOD_TLS_CLIENT
        // so add them back in manually if we can
        if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
            $cryptoMethod = STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
        }elseif (defined('STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT')) {
            $cryptoMethod = STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT;
        }

        return $cryptoMethod;
    }

    /**
     * Enable SSL certificate validation
     *
     * @return $this
     */
    public function enableCertValidation() {
        $this->cert_validation = true;
        return $this;
    }

    /**
     * Disable SSL certificate validation
     * @return $this
     */
    public function disableCertValidation() {
        $this->cert_validation = false;
        return $this;
    }

    /**
     * Set SSL certificate validation
     * @var int $cert_validation
     *
     * @return $this
     */
    public function setCertValidation($cert_validation) {
        $this->cert_validation = $cert_validation;
        return $this;
    }

    /**
     * Should we validate SSL certificate?
     *
     * @return bool
     */
    public function getCertValidation() {
        return $this->cert_validation;
    }

    /**
     * Set connection proxy settings
     * @var array $options
     *
     * @return $this
     */
    public function setProxy($options) {
        foreach ($this->proxy as $key => $val) {
            if (isset($options[$key])) {
                $this->proxy[$key] = $options[$key];
            }
        }

        return $this;
    }

    /**
     * Get the current proxy settings
     *
     * @return array
     */
    public function getProxy() {
        return $this->proxy;
    }

    /**
     * Prepare socket options
     * @var string $transport
     *
     * @return array
     */
    private function defaultSocketOptions($transport) {
        $options = [];
        if ($this->encryption != false) {
            $options["ssl"] = [
                'verify_peer_name' => $this->getCertValidation(),
                'verify_peer'      => $this->getCertValidation(),
            ];
        }

        if ($this->proxy["socket"] != null) {
            $options[$transport]["proxy"] = $this->proxy["socket"];
            $options[$transport]["request_fulluri"] = $this->proxy["request_fulluri"];

            if ($this->proxy["username"] != null) {
                $auth = base64_encode($this->proxy["username"].':'.$this->proxy["password"]);

                $options[$transport]["header"] = [
                    "Proxy-Authorization: Basic $auth"
                ];
            }
        }

        return $options;
    }

    /**
     * Create a new resource stream
     * @param $transport
     * @param string $host hostname or IP address of IMAP server
     * @param int $port of IMAP server, default is 143 (993 for ssl)
     * @param int $timeout timeout in seconds for initiating session
     *
     * @return resource|boolean The socket created.
     * @throws ConnectionFailedException
     */
    protected function createStream($transport, $host, $port, $timeout) {
        $socket = "$transport://$host:$port";
        $stream = stream_socket_client($socket, $errno, $errstr, $timeout,
            STREAM_CLIENT_CONNECT,
            stream_context_create($this->defaultSocketOptions($transport))
        );
        stream_set_timeout($stream, $timeout);

        if (!$stream) {
            throw new ConnectionFailedException($errstr, $errno);
        }

        if (false === stream_set_timeout($stream, $timeout)) {
            throw new ConnectionFailedException('Failed to set stream timeout');
        }

        return $stream;
    }

    /**
     * @return int
     */
    public function getConnectionTimeout() {
        return $this->connection_timeout;
    }

    /**
     * @param int $connection_timeout
     * @return Protocol
     */
    public function setConnectionTimeout($connection_timeout) {
        if ($connection_timeout !== null) {
            $this->connection_timeout = $connection_timeout;
        }
        return $this;
    }

}