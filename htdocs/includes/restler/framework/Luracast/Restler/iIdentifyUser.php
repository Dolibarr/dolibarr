<?php
namespace Luracast\Restler;

/**
 * Interface to identify the user
 *
 * When the user is known we will be able to monitor, rate limit and do more
 *
 * @category   Framework
 * @package    restler
 * @author     R.Arul Kumaran <arul@luracast.com>
 * @copyright  2010 Luracast
 * @license    http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link       http://luracast.com/products/restler/
 *
 */
interface iIdentifyUser
{
    /**
     * A way to uniquely identify the current api consumer
     *
     * When his user id is known it should be used otherwise ip address
     * can be used
     *
     * @param bool $includePlatform Should we consider user alone or should
     *                              consider the application/platform/device
     *                              as well for generating unique id
     *
     * @return string
     */
    public static function getUniqueIdentifier($includePlatform = false);

    /**
     * User identity to be used for caching purpose
     *
     * When the dynamic cache service places an object in the cache, it needs to
     * label it with a unique identifying string known as a cache ID. This
     * method gives that identifier
     *
     * @return string
     */
    public static function getCacheIdentifier();

    /**
     * Authentication classes should call this method
     *
     * @param string $id user id as identified by the authentication classes
     *
     * @return void
     */
    public static function setUniqueIdentifier($id);

    /**
     * User identity for caching purpose
     *
     * In a role based access control system this will be based on role
     *
     * @param $id
     *
     * @return void
     */
    public static function setCacheIdentifier($id);
}