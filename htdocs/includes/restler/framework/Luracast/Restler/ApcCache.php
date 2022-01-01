<?php

namespace Luracast\Restler;

use Luracast\Restler\iCache;

/**
 * Class ApcCache provides an APC based cache for Restler
 *
 * @category   Framework
 * @package    Restler
 * @author     Joel R. Simpson <joel.simpson@gmail.com>
 * @copyright  2013 Luracast
 * @license    http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link       http://luracast.com/products/restler/
 * @version    3.0.0rc6
 */
class ApcCache implements iCache
{
    /**
     * The namespace that all of the cached entries will be stored under.  This allows multiple APIs to run concurrently.
     *
     * @var string
     */
    static public $namespace = 'restler';

    /**
     * store data in the cache
     *
     *
     * @param string $name
     * @param mixed $data
     *
     * @return boolean true if successful
     */
    public function set($name, $data)
    {
        function_exists('apc_store') || $this->apcNotAvailable();

        try {
            return apc_store(self::$namespace . "-" . $name, $data);
        } catch
        (\Exception $exception) {
            return false;
        }
    }

    private function apcNotAvailable()
    {
        throw new \Exception('APC is not available for use as Restler Cache. Please make sure the module is installed. http://php.net/manual/en/apc.installation.php');
    }

    /**
     * retrieve data from the cache
     *
     *
     * @param string $name
     * @param bool $ignoreErrors
     *
     * @throws \Exception
     * @return mixed
     */
    public function get($name, $ignoreErrors = false)
    {
        function_exists('apc_fetch') || $this->apcNotAvailable();

        try {
            return apc_fetch(self::$namespace . "-" . $name);
        } catch (\Exception $exception) {
            if (!$ignoreErrors) {
                throw $exception;
            }
            return null;
        }
    }

    /**
     * delete data from the cache
     *
     *
     * @param string $name
     * @param bool $ignoreErrors
     *
     * @throws \Exception
     * @return boolean true if successful
     */
    public function clear($name, $ignoreErrors = false)
    {
        function_exists('apc_delete') || $this->apcNotAvailable();

        try {
            apc_delete(self::$namespace . "-" . $name);
        } catch (\Exception $exception) {
            if (!$ignoreErrors) {
                throw $exception;
            }
        }
    }

    /**
     * check if the given name is cached
     *
     *
     * @param string $name
     *
     * @return boolean true if cached
     */
    public function isCached($name)
    {
        function_exists('apc_exists') || $this->apcNotAvailable();
        return apc_exists(self::$namespace . "-" . $name);
    }

}