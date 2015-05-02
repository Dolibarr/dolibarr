<?php

use \Luracast\Restler\iAuthenticate;
use \Luracast\Restler\Resources;
use \Luracast\Restler\Defaults;

require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';

/**
 * Description of DolibarrApiAccess
 *
 * @author jfefe
 */
class DolibarrApiAccess implements iAuthenticate
{
	const REALM = 'Restricted Dolibarr API';
	const TEST_KEY = 'changeme';
	
	/**
	 *
	 * @var string $role		user / external / admin
	 * @var string $requires	
	 */
	public static $requires = 'user';
    public static $role = 'user';
	
	public function __isAllowed()
    {
		
		//@todo hardcoded api_key=>role for brevity
		//
        $roles = array('123' => 'user', '456' => 'external', '789' => 'admin');
		
		$userClass = Defaults::$userIdentifierClass;
		
		// for dev @todo : remove this!
		static::$role = 'user';
		
		if( isset($_GET['test_key'])) {
			if( ! $_GET['test_key'] == DolibarrApiAccess::TEST_KEY) {
				$userClass::setCacheIdentifier($_GET['test_key']);
				return false;
			}
		}
		elseif (isset($_GET['api_key'])) {
			// @todo : check from database
			if (!array_key_exists($_GET['api_key'], $roles)) {
				$userClass::setCacheIdentifier($_GET['api_key']);
				return false;
			}
			static::$role = $roles[$_GET['api_key']];
        }
		else
		{
			return false;
		}
		
		
		
        $userClass::setCacheIdentifier(static::$role);
        Resources::$accessControlFunction = 'DolibarrApiAccess::verifyAccess';
        return static::$requires == static::$role || static::$role == 'admin';
	}
	
	public function __getWWWAuthenticateString()
    {
        return 'Query name="api_key"';
    }
	
	/**
     * @access private
     */
    public static function verifyAccess(array $m)
    {
        $requires =
            isset($m['class']['DolibarrApiAccess']['properties']['requires'])
                ? $m['class']['DolibarrApiAccess']['properties']['requires']
                : false;
        return $requires
            ? static::$role == 'admin' || static::$role == $requires
            : true;
    }
}
