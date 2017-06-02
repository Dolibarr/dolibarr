<?php
namespace Luracast\Restler;
/**
 * Interface for the cache system that manages caching of given data
 *
 * @category   Framework
 * @package    Restler
 * @author     R.Arul Kumaran <arul@luracast.com>
 * @copyright  2010 Luracast
 * @license    http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link       http://luracast.com/products/restler/
 * @version    3.0.0rc6
 */
interface iCache
{
    /**
     * store data in the cache
     *
     * @abstract
     *
     * @param string $name
     * @param mixed  $data
     *
     * @return boolean true if successful
     */
    public function set($name, $data);

    /**
     * retrieve data from the cache
     *
     * @abstract
     *
     * @param string     $name
     * @param bool       $ignoreErrors
     *
     * @return mixed
     */
    public function get($name, $ignoreErrors = false);

    /**
     * delete data from the cache
     *
     * @abstract
     *
     * @param string     $name
     * @param bool       $ignoreErrors
     *
     * @return boolean true if successful
     */
    public function clear($name, $ignoreErrors = false);

    /**
     * check if the given name is cached
     *
     * @abstract
     *
     * @param string $name
     *
     * @return boolean true if cached
     */
    public function isCached($name);
}

