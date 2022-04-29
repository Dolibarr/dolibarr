<?php
namespace Luracast\Restler;

/**
 * Api classes or filter classes can implement this interface to know about
 * authentication status
 *
 * @category   Framework
 * @package    Restler
 * @author     R.Arul Kumaran <arul@luracast.com>
 * @copyright  2010 Luracast
 * @license    http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link       http://luracast.com/products/restler/
 *
 */
interface iUseAuthentication
{
    /**
     * This method will be called first for filter classes and api classes so
     * that they can respond accordingly for filer method call and api method
     * calls
     *
     * @abstract
     *
     * @param bool $isAuthenticated passes true when the authentication is
     * done false otherwise
     *
     * @return mixed
     */
    public function __setAuthenticationStatus($isAuthenticated=false);
}

