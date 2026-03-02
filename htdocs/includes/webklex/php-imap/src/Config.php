<?php
/*
* File: Config.php
* Category: -
* Author: M.Goldenbaum
* Created: 10.04.24 15:42
* Updated: -
*
* Description:
*  -
*/

namespace Webklex\PHPIMAP;

use Webklex\PHPIMAP\Decoder\DecoderInterface;
use Webklex\PHPIMAP\Exceptions\DecoderNotFoundException;

/**
 * Class Config
 *
 * @package Webklex\PHPIMAP
 */
class Config {

    /**
     * Configuration array
     * @var array $config
     */
    protected array $config = [];

    /**
     * Config constructor.
     * @param array $config
     */
    public function __construct(array $config = []) {
        $this->config = $config;
    }

    /**
     * Get a dotted config parameter
     * @param string $key
     * @param null $default
     *
     * @return mixed|null
     */
    public function get(string $key, $default = null): mixed {
        $parts = explode('.', $key);
        $value = null;
        foreach ($parts as $part) {
            if ($value === null) {
                if (isset($this->config[$part])) {
                    $value = $this->config[$part];
                } else {
                    break;
                }
            } else {
                if (isset($value[$part])) {
                    $value = $value[$part];
                } else {
                    break;
                }
            }
        }

        return $value === null ? $default : $value;
    }

    /**
     * Set a dotted config parameter
     * @param string $key
     * @param string|array|mixed$value
     *
     * @return void
     */
    public function set(string $key, mixed $value): void {
        $parts = explode('.', $key);
        $config = &$this->config;

        foreach ($parts as $part) {
            if (!isset($config[$part])) {
                $config[$part] = [];
            }
            $config = &$config[$part];
        }

        if(is_array($config) && is_array($value)){
            $config = array_merge($config, $value);
        }else{
            $config = $value;
        }
    }

    /**
     * Get the decoder for a given name
     * @param $name string Decoder name
     *
     * @return DecoderInterface
     * @throws DecoderNotFoundException
     */
    public function getDecoder(string $name): DecoderInterface {
        $default_decoders = $this->get('decoding.decoder', [
            'header' => \Webklex\PHPIMAP\Decoder\HeaderDecoder::class,
            'message' => \Webklex\PHPIMAP\Decoder\MessageDecoder::class,
            'attachment' => \Webklex\PHPIMAP\Decoder\AttachmentDecoder::class
        ]);
        $options = $this->get('decoding.options', [
            'header' => 'utf-8',
            'message' => 'utf-8',
            'attachment' => 'utf-8',
        ]);
        if (isset($default_decoders[$name])) {
            if (class_exists($default_decoders[$name])) {
                return new $default_decoders[$name]($options);
            }
        }
        throw new DecoderNotFoundException();
    }

    /**
     * Get the mask for a given section
     * @param string $section section name such as "message" or "attachment"
     *
     * @return string|null
     */
    public function getMask(string $section): ?string {
        $default_masks = $this->get('masks', []);
        if (isset($default_masks[$section])) {
            if (class_exists($default_masks[$section])) {
                return $default_masks[$section];
            }
        }
        return null;
    }

    /**
     * Get the account configuration.
     * @param string|null $name
     *
     * @return self
     */
    public function getClientConfig(?string $name): self {
        $config = $this->all();
        $defaultName = $this->getDefaultAccount();
        $defaultAccount = $this->get('accounts.'.$defaultName, []);

        if ($name === null || $name === 'null' || $name === "") {
            $account = $defaultAccount;
            $name = $defaultName;
        }else{
            $account = $this->get('accounts.'.$name, $defaultAccount);
        }

        $config["default"] = $name;
        $config["accounts"] = [
            $name => $account
        ];

        return new self($config);
    }

    /**
     * Get the name of the default account.
     *
     * @return string
     */
    public function getDefaultAccount(): string {
        return $this->get('default', 'default');
    }

    /**
     * Set the name of the default account.
     * @param string $name
     *
     * @return void
     */
    public function setDefaultAccount(string $name): void {
        $this->set('default', $name);
    }

    /**
     * Create a new instance of the Config class
     * @param array|string $config
     * @return Config
     */
    public static function make(array|string $config = []): Config {
        if (is_array($config) === false) {
            $config = require $config;
        }

        $config_key = 'imap';
        $path = __DIR__ . '/config/' . $config_key . '.php';

        $vendor_config = require $path;
        $config = self::array_merge_recursive_distinct($vendor_config, $config);

        if (isset($config['default'])) {
            if (isset($config['accounts']) && $config['default']) {

                $default_config = $vendor_config['accounts']['default'];
                if (isset($config['accounts'][$config['default']])) {
                    $default_config = array_merge($default_config, $config['accounts'][$config['default']]);
                }

                if (is_array($config['accounts'])) {
                    foreach ($config['accounts'] as $account_key => $account) {
                        $config['accounts'][$account_key] = array_merge($default_config, $account);
                    }
                }
            }
        }

        return new self($config);
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
     * @return array
     *
     * @link   http://www.php.net/manual/en/function.array-merge-recursive.php#96201
     * @author Mark Roduner <mark.roduner@gmail.com>
     */
    private static function array_merge_recursive_distinct(): array {
        $arrays = func_get_args();
        $base = array_shift($arrays);

        // From https://stackoverflow.com/a/173479
        $isAssoc = function(array $arr) {
            if (array() === $arr) return false;
            return array_keys($arr) !== range(0, count($arr) - 1);
        };

        if (!is_array($base)) $base = empty($base) ? array() : array($base);

        foreach ($arrays as $append) {
            if (!is_array($append)) $append = array($append);

            foreach ($append as $key => $value) {

                if (!array_key_exists($key, $base) and !is_numeric($key)) {
                    $base[$key] = $value;
                    continue;
                }

                if ((is_array($value) && $isAssoc($value)) || (is_array($base[$key]) && $isAssoc($base[$key]))) {
                    // If the arrays are not associates we don't want to array_merge_recursive_distinct
                    // else merging $baseConfig['dispositions'] = ['attachment', 'inline'] with $customConfig['dispositions'] = ['attachment']
                    // results in $resultConfig['dispositions'] = ['attachment', 'inline']
                    $base[$key] = self::array_merge_recursive_distinct($base[$key], $value);
                } else if (is_numeric($key)) {
                    if (!in_array($value, $base)) $base[] = $value;
                } else {
                    $base[$key] = $value;
                }

            }

        }

        return $base;
    }

    /**
     * Get all configuration values
     * @return array
     */
    public function all(): array {
        return $this->config;
    }

    /**
     * Check if a configuration value exists
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool {
        return $this->get($key) !== null;
    }

    /**
     * Remove all configuration values
     * @return $this
     */
    public function clear(): static {
        $this->config = [];
        return $this;
    }
}