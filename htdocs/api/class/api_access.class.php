<?php

use \Luracast\Restler\iAuthenticate;
use \Luracast\Restler\Resources;
use \Luracast\Restler\Defaults;
use Luracast\Restler\RestException;


/**
 * Description of DolibarrApiAccess
 *
 * @author jfefe
 */
class DolibarrApiAccess implements iAuthenticate
{
	const REALM = 'Restricted Dolibarr API';
	
	/**
	 * @var array $requires	role required by API method		user / external / admin	
	 */
	public static $requires = array('user','external','admin');
	
	/**
	 * @var string $role		user role
	 */
    public static $role = 'user';
	
	/**
	 * @var array	$user_perms	Permission of loggued user 
	 @todo
	public static $user_perms = array();
	
	public static $required_perms = '';
	 * *
	 */
	
	
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
		
		
		if (isset($_GET['api_key'])) {
			// @todo : check from database
			$sql = "SELECT u.login, u.datec, u.api_key, ";
			$sql.= " u.tms as date_modification, u.entity";
			$sql.= " FROM ".MAIN_DB_PREFIX."user as u";
			$sql.= " WHERE u.api_key = '".$db->escape($_GET['api_key'])."'";

			
			if ($db->query($sql))
			{
				if ($db->num_rows($result))
				{
					$obj = $db->fetch_object($result);
					$login = $obj->login;
					$stored_key = $obj->api_key;
				}
			}
			else {
				throw new RestException(503, 'Error when fetching user api_key :'.$db->error_msg);
			}

			if ( $stored_key != $_GET['api_key']) {
				$userClass::setCacheIdentifier($_GET['api_key']);
				return false;
			}
			
			$fuser = new User($db);
			if(! $fuser->fetch('',$login)) {
				throw new RestException(503, 'Error when fetching user :'.$fuser->error);
			}
			$fuser->getrights();
			static::$user_perms = $fuser->rights;
			
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
        return in_array(static::$role, (array) static::$requires) || static::$role == 'admin';
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
        $requires = isset($m['class']['DolibarrApiAccess']['properties']['requires'])
                ? $m['class']['DolibarrApiAccess']['properties']['requires']
                : false;
		
		
        return $requires
            ? static::$role == 'admin' || in_array(static::$role, (array) $requires)
            : true;
		
    }
}
