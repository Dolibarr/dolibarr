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
	
	/**
	 * @var string $requires	role required by API method		user / external / admin	
	 */
	public static $requires = 'user';
	
	/**
	 * @var string $role		user role
	 */
    public static $role = 'user';
	
	/**
	 * Check access
	 * 
	 * @return boolean
	 */
	public function __isAllowed()
    {
		global $db;
		
		//@todo hardcoded api_key=>role for brevity
		//
		$stored_key = '';
		
		$userClass = Defaults::$userIdentifierClass;
		
		// for dev @todo : remove this!
		static::$role = 'user';
		
		if (isset($_GET['api_key'])) {
			// @todo : check from database
			$sql = "SELECT u.login, u.datec, u.api_key, ";
			$sql.= " u.tms as date_modification, u.entity";
			$sql.= " FROM ".MAIN_DB_PREFIX."user as u";
			$sql.= " WHERE u.api_key = '".$db->escape($_GET['api_key'])."'";

			$result=$db->query($sql);
			
			if ($result)
			{
				if ($db->num_rows($result))
				{
					$obj = $db->fetch_object($result);
					$login = $obj->login;
					$stored_key = $obj->api_key;
				}
			}

			if ( $stored_key != $_GET['api_key']) {
				$userClass::setCacheIdentifier($_GET['api_key']);
				return false;
			}
			
			$fuser = new User($db);
			$result = $fuser->fetch('',$login);
			
			if($fuser->societe_id)
				static::$role = 'external';
			
			if($fuser->admin)
				static::$role = 'admin';
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
        return '';
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
