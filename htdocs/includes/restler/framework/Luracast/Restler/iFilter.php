<?php
namespace Luracast\Restler;

/**
 * Interface for creating classes that perform authentication/access
 * verification
 *
 * @category   Framework
 * @package    Restler
 * @subpackage auth
 * @author     R.Arul Kumaran <arul@luracast.com>
 * @copyright  2010 Luracast
 * @license    http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link       http://luracast.com/products/restler/
 *
 */
interface iFilter
{
    /**
     * Access verification method.
     *
     * API access will be denied when this method returns false
     *
     * @abstract
     * @return boolean true when api access is allowed false otherwise
     */
    public function __isAllowed();

}

