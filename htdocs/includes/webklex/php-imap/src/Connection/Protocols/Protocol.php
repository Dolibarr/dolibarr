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

use Webklex\PHPIMAP\Config;
use Webklex\PHPIMAP\Exceptions\ConnectionFailedException;
use Webklex\PHPIMAP\IMAP;

/**
 * Class Protocol
 *
 * @package Webklex\PHPIMAP\Connection\Protocols
 */
abstract class Protocol implements ProtocolInterface {

    /**
     * Default connection timeout in seconds
     */
    protected int $connection_timeout = 30;

    /**
     * @var boolean
     */
    protected bool $debug = false;

    /**
     * @var boolean
     */
    protected bool $enable_uid_cache = true;

    /**
     * @var resource|mixed|boolean|null $stream
     */
    public $stream = false;

    /**
     * @var Config $config
     */
    protected Config $config;

    /**
     * Connection encryption method
     * @var string $encryption
     */
    protected string $encryption = "";

    /**
     * Set to false to ignore SSL certificate validation
     * @var bool
     */
    protected bool $cert_validation = true;

    /**
     * Proxy settings
     * @var array
     */
    protected array $proxy = [
        'socket' => null,
        'request_fulluri' => false,
        'username' => null,
        'password' => null,
    ];

    /**
     * SSL stream context options
     *
     * @see https://www.php.net/manual/en/context.ssl.php for possible options
     *
     * @var array
     */
    protected array $ssl_options = [];

    /**
     * Cache for uid of active folder.
     *
     * @var array
     */
    protected array $uid_cache = [];

    /**
     * Get an available cryptographic method
     *
     * @return int
     */
    public function getCryptoMethod(): int {
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
     * @return Protocol
     */
    public function enableCertValidation(): Protocol {
        $this->cert_validation = true;
        return $this;
    }

    /**
     * Disable SSL certificate validation
     * @return Protocol
     */
    public function disableCertValidation(): Protocol {
        $this->cert_validation = false;
        return $this;
    }

    /**
     * Set SSL certificate validation
     * @var int $cert_validation
     *
     * @return Protocol
     */
    public function setCertValidation(int $cert_validation): Protocol {
        $this->cert_validation = $cert_validation;
        return $this;
    }

    /**
     * Should we validate SSL certificate?
     *
     * @return bool
     */
    public function getCertValidation(): bool {
        return $this->cert_validation;
    }

    /**
     * Set connection proxy settings
     * @var array $options
     *
     * @return Protocol
     */
    public function setProxy(array $options): Protocol {
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
    public function getProxy(): array {
        return $this->proxy;
    }

    /**
     * Set SSL context options settings
     * @var array $options
     *
     * @return Protocol
     */
    public function setSslOptions(array $options): Protocol
    {
        $this->ssl_options = $options;

        return $this;
    }

    /**
     * Get the current SSL context options settings
     *
     * @return array
     */
    public function getSslOptions(): array {
        return $this->ssl_options;
    }

    /**
     * Prepare socket options
     * @return array
     *@var string $transport
     *
     */
    private function defaultSocketOptions(string $transport): array {
        $options = [];
        if ($this->encryption) {
            $options["ssl"] = [
                'verify_peer_name' => $this->getCertValidation(),
                'verify_peer'      => $this->getCertValidation(),
            ];

            if (count($this->ssl_options)) {
                /* Get the ssl context options from the config, but prioritize the 'validate_cert' config over the ssl context options */
                $options["ssl"] = array_replace($this->ssl_options, $options["ssl"]);
            }
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
     * @return resource The socket created.
     * @throws ConnectionFailedException
     */
    public function createStream($transport, string $host, int $port, int $timeout) {
        $socket = "$transport://$host:$port";
        $stream = stream_socket_client($socket, $errno, $errstr, $timeout,
            STREAM_CLIENT_CONNECT,
            stream_context_create($this->defaultSocketOptions($transport))
        );

        if (!$stream) {
            throw new ConnectionFailedException($errstr, $errno);
        }

        if (false === stream_set_timeout($stream, $timeout)) {
            throw new ConnectionFailedException('Failed to set stream timeout');
        }

        return $stream;
    }

    /**
     * Get the current connection timeout
     *
     * @return int
     */
    public function getConnectionTimeout(): int {
        return $this->connection_timeout;
    }

    /**
     * Set the connection timeout
     * @param int $connection_timeout
     *
     * @return Protocol
     */
    public function setConnectionTimeout(int $connection_timeout): Protocol {
        $this->connection_timeout = $connection_timeout;
        return $this;
    }

    /**
     * Get the UID key string
     * @param int|string $uid
     *
     * @return string
     */
    public function getUIDKey(int|string $uid): string {
        if ($uid == IMAP::ST_UID || $uid == IMAP::FT_UID) {
            return "UID";
        }
        if (strlen($uid) > 0 && !is_numeric($uid)) {
            return (string)$uid;
        }

        return "";
    }

    /**
     * Build a UID / MSGN command
     * @param string $command
     * @param int|string $uid
     *
     * @return string
     */
    public function buildUIDCommand(string $command, int|string $uid): string {
        return trim($this->getUIDKey($uid)." ".$command);
    }

    /**
     * Set the uid cache of current active folder
     *
     * @param array|null $uids
     */
    public function setUidCache(?array $uids): void {
        if (is_null($uids)) {
            $this->uid_cache = [];
            return;
        }

        $messageNumber = 1;

        $uid_cache = [];
        foreach ($uids as $uid) {
            $uid_cache[$messageNumber++] = (int)$uid;
        }

        $this->uid_cache = $uid_cache;
    }

    /**
     * Enable the uid cache
     *
     * @return void
     */
    public function enableUidCache(): void {
        $this->enable_uid_cache = true;
    }

    /**
     * Disable the uid cache
     *
     * @return void
     */
    public function disableUidCache(): void {
        $this->enable_uid_cache = false;
    }

    /**
     * Set the encryption method
     * @param string $encryption
     *
     * @return void
     */
    public function setEncryption(string $encryption): void {
        $this->encryption = $encryption;
    }

    /**
     * Get the encryption method
     * @return string
     */
    public function getEncryption(): string {
        return $this->encryption;
    }

    /**
     * Check if the current session is connected
     *
     * @return bool
     */
    public function connected(): bool {
        return (bool)$this->stream;
    }

    /**
     * Retrieves header/metadata from the resource stream
     *
     * @return array
     */
    public function meta(): array {
        if (!$this->stream) {
            return [
                "crypto"       => [
                    "protocol"       => "",
                    "cipher_name"    => "",
                    "cipher_bits"    => 0,
                    "cipher_version" => "",
                ],
                "timed_out"    => true,
                "blocked"      => true,
                "eof"          => true,
                "stream_type"  => "tcp_socket/unknown",
                "mode"         => "c",
                "unread_bytes" => 0,
                "seekable"     => false,
            ];
        }
        return stream_get_meta_data($this->stream);
    }

    /**
     * Get the resource stream
     *
     * @return mixed
     */
    public function getStream(): mixed {
        return $this->stream;
    }

    /**
     * Set the Config instance
     *
     * @return Config
     */
    public function getConfig(): Config {
        return $this->config;
    }
}
