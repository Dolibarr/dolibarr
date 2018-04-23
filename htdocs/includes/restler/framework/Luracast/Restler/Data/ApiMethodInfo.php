<?php
namespace Luracast\Restler\Data;

/**
 * ValueObject for api method info. All needed information about a api method
 * is stored here
 *
 * @category   Framework
 * @package    Restler
 * @author     R.Arul Kumaran <arul@luracast.com>
 * @copyright  2010 Luracast
 * @license    http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link       http://luracast.com/products/restler/
 * @version    3.0.0rc6
 */
class ApiMethodInfo extends ValueObject
{
    /**
     * @var string target url
     */
    public $url;
    /**
     * @var string
     */
    public $className;
    /**
     * @var string
     */
    public $methodName;
    /**
     * @var array parameters to be passed to the api method
     */
    public $parameters = array();
    /**
     * @var array information on parameters in the form of array(name => index)
     */
    public $arguments = array();
    /**
     * @var array default values for parameters if any
     * in the form of array(index => value)
     */
    public $defaults = array();
    /**
     * @var array key => value pair of method meta information
     */
    public $metadata = array();
    /**
     * @var int access level
     * 0 - @public - available for all
     * 1 - @hybrid - both public and protected (enhanced info for authorized)
     * 2 - @protected comment - only for authenticated users
     * 3 - protected method - only for authenticated users
     */
    public $accessLevel = 0;
}