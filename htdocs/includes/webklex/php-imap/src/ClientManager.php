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
 *
 * @mixin Client
 */
class ClientManager {

    /**
     * All library config
     *
     * @var array $config
     */
    public static $config = [];

    /**
     * @var array $accounts
     */
    protected $accounts = [];

    /**
     * ClientManager constructor.
     * @param array|string $config
     */
    public function __construct($config = []) {
        $this->setConfig($config);
    }

    /**
     * Dynamically pass calls to the default account.
     * @param  string  $method
     * @param  array   $parameters
     *
     * @return mixed
     * @throws Exceptions\MaskNotFoundException
     */
    public function __call($method, $parameters) {
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
    public function make($config) {
        return new Client($config);
    }

    /**
     * Get a dotted config parameter
     * @param string $key
     * @param null   $default
     *
     * @return mixed|null
     */
    public static function get($key, $default = null) {
        $parts = explode('.', $key);
        $value = null;
        foreach($parts as $part) {
            if($value === null) {
                if(isset(self::$config[$part])) {
                    $value = self::$config[$part];
                }else{
                    break;
                }
            }else{
                if(isset($value[$part])) {
                    $value = $value[$part];
                }else{
                    break;
                }
            }
        }

        return $value === null ? $default : $value;
    }

    /**
     * Resolve a account instance.
     * @param  string  $name
     *
     * @return Client
     * @throws Exceptions\MaskNotFoundException
     */
    public function account($name = null) {
        $name = $name ?: $this->getDefaultAccount();

        // If the connection has not been resolved yet we will resolve it now as all
        // of the connections are resolved when they are actually needed so we do
        // not make any unnecessary connection to the various queue end-points.
        if (!isset($this->accounts[$name])) {
            $this->accounts[$name] = $this->resolve($name);
        }

        return $this->accounts[$name];
    }

    /**
     * Resolve a account.
     *
     * @param  string  $name
     *
     * @return Client
     * @throws Exceptions\MaskNotFoundException
     */
    protected function resolve($name) {
        $config = $this->getClientConfig($name);

        return new Client($config);
    }

    /**
     * Get the account configuration.
     * @param  string  $name
     *
     * @return array
     */
    protected function getClientConfig($name) {
        if ($name === null || $name === 'null') {
            return ['driver' => 'null'];
        }

        return self::$config["accounts"][$name];
    }

    /**
     * Get the name of the default account.
     *
     * @return string
     */
    public function getDefaultAccount() {
        return self::$config['default'];
    }

    /**
     * Set the name of the default account.
     * @param  string  $name
     *
     * @return void
     */
    public function setDefaultAccount($name) {
        self::$config['default'] = $name;
    }


    /**
     * Merge the vendor settings with the local config
     *
     * The default account identifier will be used as default for any missing account parameters.
     * If however the default account is missing a parameter the package default account parameter will be used.
     * This can be disabled by setting imap.default in your config file to 'false'
     *
     * @param array|string $config
     *
     * @return $this
     */
    public function setConfig($config) {

        if(is_array($config) === false) {
            $config = require $config;
        }

        $config_key = 'imap';
        $path = __DIR__.'/config/'.$config_key.'.php';

        $vendor_config = require $path;
        $config = $this->array_merge_recursive_distinct($vendor_config, $config);

        if(is_array($config)){
            if(isset($config['default'])){
                if(isset($config['accounts']) && $config['default'] != false){

                    $default_config = $vendor_config['accounts']['default'];
                    if(isset($config['accounts'][$config['default']])){
                        $default_config = array_merge($default_config, $config['accounts'][$config['default']]);
                    }

                    if(is_array($config['accounts'])){
                        foreach($config['accounts'] as $account_key => $account){
                            $config['accounts'][$account_key] = array_merge($default_config, $account);
                        }
                    }
                }
            }
        }

        self::$config = $config;

        return $this;
    }

    /**
     * Marge arrays recursively and distinct
     *
     * Merges any number of arrays / parameters recursively, replacing
     * entries with string keys with values from latter arrays.
     * If the entry or the next value to be assigned is an array, then it
     * automatically treats both arguments as an array.
     * Numeric entries are appended, not replaced, but only if they are
     * unique
     *
     * @param  array $array1 Initial array to merge.
     * @param  array ...     Variable list of arrays to recursively merge.
     *
     * @return array|mixed
     *
     * @link   http://www.php.net/manual/en/function.array-merge-recursive.php#96201
     * @author Mark Roduner <mark.roduner@gmail.com>
     */
    private function array_merge_recursive_distinct() {

        $arrays = func_get_args();
        $base = array_shift($arrays);

        if(!is_array($base)) $base = empty($base) ? array() : array($base);

        foreach($arrays as $append) {

            if(!is_array($append)) $append = array($append);

            foreach($append as $key => $value) {

                if(!array_key_exists($key, $base) and !is_numeric($key)) {
                    $base[$key] = $append[$key];
                    continue;
                }

                if(is_array($value) or is_array($base[$key])) {
                    $base[$key] = $this->array_merge_recursive_distinct($base[$key], $append[$key]);
                } else if(is_numeric($key)) {
                    if(!in_array($value, $base)) $base[] = $value;
                } else {
                    $base[$key] = $value;
                }

            }

        }

        return $base;
    }
}