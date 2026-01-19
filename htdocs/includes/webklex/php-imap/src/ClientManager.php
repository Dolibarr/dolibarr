<?php
/*
* File:     ClientManager.php
* Category: -
* Author:   M. Goldenbaum
* Created:  19.01.17 22:21
* Updated:  -
*
* Description:
*  -
*/

namespace Webklex\PHPIMAP;

/**
 * Class ClientManager
 *
 * @package Webklex\IMAP
 */
class ClientManager {

    /**
     * All library config
     *
     * @var Config $config
     */
    public Config $config;

    /**
     * @var array $accounts
     */
    protected array $accounts = [];

    /**
     * ClientManager constructor.
     * @param array|string|Config $config
     */
    public function __construct(array|string|Config $config = []) {
        $this->setConfig($config);
    }

    /**
     * Dynamically pass calls to the default account.
     * @param string $method
     * @param array $parameters
     *
     * @return mixed
     * @throws Exceptions\MaskNotFoundException
     */
    public function __call(string $method, array $parameters) {
        $callable = [$this->account(), $method];

        return call_user_func_array($callable, $parameters);
    }

    /**
     * Safely create a new client instance which is not listed in accounts
     * @param array $config
     *
     * @return Client
     * @throws Exceptions\MaskNotFoundException
     */
    public function make(array $config): Client {
        $name = $this->config->getDefaultAccount();
        $clientConfig = $this->config->all();
        $clientConfig["accounts"] = [$name => $config];
        return new Client(Config::make($clientConfig));
    }

    /**
     * Resolve a account instance.
     * @param string|null $name
     *
     * @return Client
     * @throws Exceptions\MaskNotFoundException
     */
    public function account(?string $name = null): Client {
        $name = $name ?: $this->config->getDefaultAccount();

        // If the connection has not been resolved we will resolve it now as all
        // the connections are resolved when they are actually needed, so we do
        // not make any unnecessary connection to the various queue end-points.
        if (!isset($this->accounts[$name])) {
            $this->accounts[$name] = $this->resolve($name);
        }

        return $this->accounts[$name];
    }

    /**
     * Resolve an account.
     * @param string $name
     *
     * @return Client
     * @throws Exceptions\MaskNotFoundException
     */
    protected function resolve(string $name): Client {
        $config = $this->config->getClientConfig($name);

        return new Client($config);
    }


    /**
     * Merge the vendor settings with the local config
     *
     * The default account identifier will be used as default for any missing account parameters.
     * If however the default account is missing a parameter the package default account parameter will be used.
     * This can be disabled by setting imap.default in your config file to 'false'
     *
     * @param array|string|Config $config
     *
     * @return $this
     */
    public function setConfig(array|string|Config $config): ClientManager {
        if (!$config instanceof Config) {
            $config = Config::make($config);
        }
        $this->config = $config;

        return $this;
    }

    /**
     * Get the config instance
     * @return Config
     */
    public function getConfig(): Config {
        return $this->config;
    }
}