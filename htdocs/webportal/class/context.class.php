<?php
/* Copyright (C) 2023-2024 	Laurent Destailleur         <eldy@users.sourceforge.net>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
* \file       htdocs/webportal/class/context.class.php
* \ingroup    webportal
* \brief      File of context class for WebPortal
*/

require_once __DIR__ . '/controller.class.php';
require_once __DIR__ . '/webPortalTheme.class.php';

/**
 * Class Context
 */
class Context
{
	/**
	 * @var ?Context Singleton
	 * @access private
	 * @static
	 */
	private static $_instance = null;

	/**
	 * @var	DoliDB	$db		Database handler
	 */
	public $db;

	/**
	 * @var string
	 */
	public $title;

	/**
	 * @var string
	 */
	public $desc;

	/**
	 * @var string
	 */
	public $meta_title;

	/**
	 * @var string
	 */
	public $meta_desc;

	/**
	 * The application name
	 * @var string $appliName
	 */
	public $appliName;

	/**
	 * @var string
	 */
	public $controller;

	/**
	 * @var boolean
	 */
	public $controller_found = false;

	/**
	 * @var stdClass[]
	 */
	private $controllers = array();

	/**
	 * @var Controller $controllerInstance
	 */
	public $controllerInstance;

	/**
	 * for internal error msg
	 * @var string error
	 */
	public $error;

	/**
	 * @var string[] errors
	 */
	public $errors = array();

	/**
	 * @var string Action
	 */
	public $action;

	/**
	 * @var string tpl directory
	 */
	public $tplDir;

	/**
	 * @var string tpl path
	 */
	public $tplPath;

	/**
	 * @var stdClass
	 */
	public $topMenu;

	/**
	 * @var string root url
	 */
	public $rootUrl;

	/**
	 * @var string[]
	 */
	public $menu_active = array();

	/**
	 * @var array{mesgs:string[],warnings:string[],errors:string[]}|array{} event messages
	 */
	public $eventMessages = array();

	/**
	 * @var string token key
	 */
	public $tokenKey = 'token';

	/**
	 * Current object of page
	 * @var object $object
	 */
	public $object;

	/**
	 * @var CommonObject Logged user
	 */
	public $logged_user = null;

	/**
	 * @var CommonObject Logged third-party
	 */
	public $logged_thirdparty = null;

	/**
	 * @var CommonObject Logged member
	 */
	public $logged_member = null;

	/**
	 * @var CommonObject Logged partnership
	 */
	public $logged_partnership = null;

	/**
	 * @var WebPortalTheme Theme data
	 */
	public $theme;


	/**
	 * Constructor
	 *
	 * @return  void
	 */
	private function __construct()
	{
		global $conf, $db;

		$this->db = $db;

		$this->tplDir = __DIR__ . '/../';

		$this->getControllerUrl();

		$this->topMenu = new stdClass();

		$this->tplPath = realpath(__DIR__ . '/../../public/webportal/tpl');

		$this->controller = GETPOST('controller', 'aZ09'); // for security, limited to 'aZ09'
		$this->action = GETPOST('action', 'aZ09');// for security, limited to 'aZ09'

		if (empty($this->controller)) {
			$this->controller = 'default';
		}

		$this->appliName = getDolGlobalString('WEBPORTAL_TITLE', getDolGlobalString('MAIN_INFO_SOCIETE_NOM'));

		//$this->generateNewToken();

		$this->initController();

		// Init de l'url de base
		$this->rootUrl = self::getRootConfigUrl();


		$this->theme = new WebPortalTheme();
	}

	/**
	 * Singleton method to create one instance of this object
	 *
	 * @return	Context	Instance
	 */
	public static function getInstance()
	{
		if (is_null(self::$_instance)) {
			self::$_instance = new Context();
		}

		return self::$_instance;
	}

	/**
	 * Init controller
	 *
	 * @return  void
	 */
	public function initController()
	{
		global $db;

		$defaultControllersPath = __DIR__ . '/../controllers/';

		// define controllers definition
		$this->addControllerDefinition('login', $defaultControllersPath . 'login.controller.class.php', 'LoginController');
		$this->addControllerDefinition('default', $defaultControllersPath . 'default.controller.class.php', 'DefaultController');
		$this->addControllerDefinition('document', $defaultControllersPath . 'document.controller.class.php', 'DocumentController');
		$this->addControllerDefinition('propallist', $defaultControllersPath . 'propallist.controller.class.php', 'PropalListController');
		$this->addControllerDefinition('orderlist', $defaultControllersPath . 'orderlist.controller.class.php', 'OrderListController');
		$this->addControllerDefinition('invoicelist', $defaultControllersPath . 'invoicelist.controller.class.php', 'InvoiceListController');
		$this->addControllerDefinition('membercard', $defaultControllersPath . 'membercard.controller.class.php', 'MemberCardController');
		$this->addControllerDefinition('partnershipcard', $defaultControllersPath . 'partnershipcard.controller.class.php', 'PartnershipCardController');

		// call triggers
		//include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
		//$interface=new Interfaces($db);
		//$interface->run_triggers('WebPortalInitController', $this, $logged_user, $langs, $conf);

		// search for controller
		$this->controllerInstance = new Controller();
		if (isset($this->controllers[$this->controller]) && file_exists($this->controllers[$this->controller]->path)) {
			require_once $this->controllers[$this->controller]->path;

			if (class_exists($this->controllers[$this->controller]->class)) {
				$this->controllerInstance = new $this->controllers[$this->controller]->class();
				$this->setControllerFound();
			}
		}
	}

	/**
	 * Add controller definition
	 *
	 * @param	string	$controller		Name
	 * @param	string	$path			Path
	 * @param	string	$className		Class name
	 * @return  bool
	 */
	public function addControllerDefinition($controller, $path, $className)
	{
		$fileName = basename($path);
		$needle = '.controller.class.php';
		$length = strlen($needle);
		$isControllerFile = $length > 0 ? substr($fileName, -$length) === $needle : true;
		if (!$isControllerFile) {
			$this->setError('Error: controller definition ' . $fileName);
			return false;
		}

		$this->controllers[$controller] = new stdClass();
		$this->controllers[$controller]->path = $path;
		$this->controllers[$controller]->class = $className;

		return true;
	}

	/**
	 * Set controller found
	 *
	 * @return  void
	 */
	public function setControllerFound()
	{
		$this->controller_found = true;
	}

	/**
	 * Get WebPortal root url
	 *
	 * @return  string  Web Portal root url
	 */
	public static function getRootConfigUrl()
	{
		global $conf;

		// Init de l'url de base
		if (getDolGlobalString('WEBPORTAL_ROOT_URL')) {
			$rootUrl = getDolGlobalString('WEBPORTAL_ROOT_URL');
			if (substr($rootUrl, -1) !== '/') {
				$rootUrl .= '/';
			}
		} else {
			$rootUrl = dol_buildpath('/public/webportal/', 2);
		}

		return $rootUrl;
	}

	/**
	 * Get root url
	 *
	 * @param	string			$controller		Controller name
	 * @param	string|array<string,mixed>	$moreParams		More parameters
	 * @param	bool			$addToken		Add token hash only if $controller is set
	 * @return	string
	 * @deprecated see getControllerUrl()
	 */
	public function getRootUrl($controller = '', $moreParams = '', $addToken = true)
	{
		return self::getControllerUrl($controller, $moreParams, $addToken);
	}

	/**
	 * Get controller url according to context
	 *
	 * @param	string			$controller		Controller name
	 * @param	string|array<string,mixed>	$moreParams		More parameters
	 * @param	bool			$addToken		Add token hash only if controller is set
	 * @return	string
	 */
	public function getControllerUrl($controller = '', $moreParams = '', $addToken = true)
	{
		// TODO : addToken parameter on auto to detect (create or edit) action and add token on url
		$url = $this->rootUrl;

		if (empty($controller)) {
			// because can be called without params to get only rootUrl
			return $url;
		}

		$Tparams = array();

		$Tparams['controller'] = $controller;

		if (!empty($addToken)) {
			$Tparams[$this->tokenKey] = $this->newToken();
		}

		return self::getPublicControllerUrl($controller, $moreParams, $Tparams);
	}

	/**
	 * Generate public controller URL
	 * Used for external link (like email or web page)
	 * so remove token and contextual behavior associate with current user
	 *
	 * @param 	string						$controller		Controller
	 * @param 	string|array<string,mixed>	$moreParams		More parameters
	 * @param	array<string,mixed>			$Tparams		Parameters
	 * @return	string
	 */
	public static function getPublicControllerUrl($controller = '', $moreParams = '', $Tparams = array())
	{
		$url = self::getRootConfigUrl();

		if (empty($controller)) {
			// because can be called without params to get only rootUrl
			return $url;
		}

		$Tparams['controller'] = $controller;

		// if $moreParams is an array
		if (!empty($moreParams) && is_array($moreParams)) {
			if (isset($moreParams['controller'])) {
				unset($moreParams['controller']);
			}
			if (!empty($moreParams)) {
				foreach ($moreParams as $paramKey => $paramVal) {
					$Tparams[$paramKey] = $paramVal;
				}
			}
		}

		if (!empty($Tparams)) {
			$TCompiledAttr = array();
			foreach ($Tparams as $key => $value) {
				$TCompiledAttr[] = $key . '=' . $value;
			}
			$url .= '?' . implode("&", $TCompiledAttr);
		}

		// if $moreParams is a string
		if (!empty($moreParams) && !is_array($moreParams)) {
			if (empty($Tparams)) {
				if ($moreParams[0] !== '?') {
					$url .= '?';
				}
				if ($moreParams[0] === '&') {
					$moreParams = substr($moreParams, 1);
				}
			}
			$url .= $moreParams;
		}

		return $url;
	}

	/**
	 * Url origin
	 *
	 * @param	bool	$withRequestUri			With request URI
	 * @param	bool	$use_forwarded_host		Use formatted host
	 * @return 	string
	 */
	public static function urlOrigin($withRequestUri = true, $use_forwarded_host = false)
	{
		$s = $_SERVER;

		$ssl = (!empty($s['HTTPS']) && $s['HTTPS'] == 'on');
		$sp = strtolower($s['SERVER_PROTOCOL']);
		$protocol = substr($sp, 0, strpos($sp, '/')) . (($ssl) ? 's' : '');
		$port = $s['SERVER_PORT'];
		$port = ((!$ssl && $port == '80') || ($ssl && $port == '443')) ? '' : ':' . $port;
		$host = ($use_forwarded_host && isset($s['HTTP_X_FORWARDED_HOST'])) ? $s['HTTP_X_FORWARDED_HOST'] : (isset($s['HTTP_HOST']) ? $s['HTTP_HOST'] : null);
		$host = isset($host) ? $host : $s['SERVER_NAME'] . $port;

		$url = $protocol . '://' . $host;

		if ($withRequestUri) {
			$url .= $s['REQUEST_URI'];
		}

		return $url;
	}

	/**
	 * Check if user is logged
	 *
	 * @return	bool
	 */
	public function userIsLog()
	{
		if (!empty($_SESSION["webportal_logged_thirdparty_account_id"])) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Is menu enabled ?
	 *
	 * @param   string	$menuName	Menu name
	 * @return  bool
	 */
	public function menuIsActive($menuName)
	{
		return in_array($menuName, $this->menu_active);
	}

	/**
	 * Set errors
	 *
	 * @param 	string|string[]	$errors		Errors
	 * @return	void
	 */
	public function setError($errors)
	{
		if (!is_array($errors)) {
			$errors = array($errors);
		}
		if (!isset($_SESSION['webportal_errors'])) {
			$_SESSION['webportal_errors'] = array();
		}
		foreach ($errors as $msg) {
			if (!in_array($msg, $_SESSION['webportal_errors'])) {
				$_SESSION['webportal_errors'][] = $msg;
			}
		}
	}

	/**
	 * Get errors
	 *
	 * @return  int
	 */
	public function getErrors()
	{
		if (!empty($_SESSION['webportal_errors'])) {
			$this->errors = array_values($_SESSION['webportal_errors']);
			return count($this->errors);
		}

		return 0;
	}

	/**
	 * Clear errors
	 *
	 * @return  void
	 */
	public function clearErrors()
	{
		unset($_SESSION['webportal_errors']);
		$this->errors = array();
	}

	/**
	 * Set event messages in dol_events session object. Will be output by calling dol_htmloutput_events.
	 * Note: Calling dol_htmloutput_events is done into pages by standard llxFooter() function.
	 *
	 * @param	string|string[]	$mesgs	Message string or array
	 * @param	string			$style	Which style to use ('mesgs' by default, 'warnings', 'errors')
	 * @return	void
	 */
	public function setEventMessage($mesgs, $style = 'mesgs')
	{
		$TAcceptedStyle = array('mesgs', 'warnings', 'errors');

		if (!in_array($style, $TAcceptedStyle)) {
			$style = 'mesgs';
		}

		if (!is_array($mesgs)) {
			$mesgs = array($mesgs);
		}
		if (!isset($_SESSION['webportal_events'])) {
			$_SESSION['webportal_events'] = array(
				'mesgs' => array(), 'warnings' => array(), 'errors' => array()
			);
		}

		foreach ($mesgs as $msg) {
			if (!in_array($msg, $_SESSION['webportal_events'][$style])) {
				$_SESSION['webportal_events'][$style][] = $msg;
			}
		}
	}

	/**
	 * Set event messages in dol_events session object. Will be output by calling dol_htmloutput_events.
	 * Note: Calling dol_htmloutput_events is done into pages by standard llxFooter() function.
	 *
	 * @param	string			$mesg	Message string
	 * @param	string[]|null	$mesgs	Message array
	 * @param	string			$style	Which style to use ('mesgs' by default, 'warnings', 'errors')
	 * @return	void
	 */
	public function setEventMessages($mesg, $mesgs, $style = 'mesgs')
	{
		if (empty($mesg) && empty($mesgs)) {
			dol_syslog(__METHOD__ . ' Try to add a message in stack, but value to add is empty message', LOG_WARNING);
		} else {
			if (!in_array((string) $style, array('mesgs', 'warnings', 'errors'))) {
				dol_print_error(null, 'Bad parameter style=' . $style . ' for setEventMessages');
			}
			if (empty($mesgs)) {
				$this->setEventMessage($mesg, $style);
			} else {
				if (!empty($mesg) && !in_array($mesg, $mesgs)) {
					$this->setEventMessage($mesg, $style); // Add message string if not already into array
				}
				$this->setEventMessage($mesgs, $style);
			}
		}
	}

	/**
	 * Load event messages
	 *
	 * @return  int
	 */
	public function loadEventMessages()
	{
		if (!empty($_SESSION['webportal_events'])) {
			$this->eventMessages = $_SESSION['webportal_events'];
			return 1;
		}

		return 0;
	}

	/**
	 * Clear event messages
	 *
	 * @return  void
	 */
	public function clearEventMessages()
	{
		unset($_SESSION['webportal_events']);
		$this->eventMessages = array();
	}

	/**
	 * Return the value of token currently saved into session with name 'newToken'.
	 * This token must be sent by any POST as it will be used by next page for comparison with value in session.
	 * This token depends on controller
	 *
	 * @return  string
	 */
	public function newToken()
	{
		return newToken();
	}

	/**
	 * Generate new token.
	 * @deprecated see main
	 * @return	string
	 */
	protected function generateNewToken()
	{
		$currentToken = $this->newToken();
		// Creation of a token against CSRF vulnerabilities
		if (!defined('NOTOKENRENEWAL') || empty($currentToken)) {
			// Rolling token at each call ($_SESSION['token'] contains token of previous page)
			if (isset($_SESSION['newtoken'])) {
				$_SESSION['token'] = $_SESSION['newtoken'];
			}

			// Save what will be next token. Into forms, we will add param $context->newToken();
			$token = dol_hash(uniqid((string) mt_rand(), true)); // Generate
			$_SESSION['newtoken'] = $token;

			return $token;
		} else {
			return $this->newToken();
		}
	}

	/**
	 * Get token url
	 *
	 * @return	string|null
	 */
	public function getUrlToken()
	{
		$token = $this->newToken();
		if ($token) {
			return '&' . $this->tokenKey . '=' . $this->newToken();
		}

		return null;
	}

	/**
	 * Get token input for form
	 *
	 * @return  string|null
	 */
	public function getFormToken()
	{
		$token = $this->newToken();
		if ($token) {
			return '<input type="hidden" name="' . $this->tokenKey . '" value="' . $this->newToken() . '" />';
		}

		return null;
	}

	/**
	 * Try to find the third-party account id from
	 *
	 * @param	string	$login		Login
	 * @param	string	$pass		Password
	 * @return  int		Third-party account id || <0 if error
	 */
	public function getThirdPartyAccountFromLogin($login, $pass)
	{
		$id = 0;

		$sql = "SELECT sa.rowid as id, sa.pass_crypted";
		$sql .= " FROM " . $this->db->prefix() . "societe_account as sa";
		$sql .= " WHERE sa.login = '" . $this->db->escape($login) . "'";
		//$sql .= " AND BINARY sa.pass_crypted = '" . $this->db->escape($pass) . "'"; // case sensitive
		$sql .= " AND sa.site = 'dolibarr_portal'";
		$sql .= " AND sa.status = 1";
		$sql .= " AND sa.entity IN (" . getEntity('societe') . ")";

		dol_syslog(__METHOD__ . ' Try to find the third-party account id for login"' . $login . '" and site="dolibarr_portal"', LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			if ($this->db->num_rows($result) == 1) {
				$passok = false;
				$obj = $this->db->fetch_object($result);
				if ($obj) {
					$passcrypted = $obj->pass_crypted;

					// Check crypted password
					$cryptType = '';
					if (getDolGlobalString('DATABASE_PWD_ENCRYPTED')) {
						$cryptType = getDolGlobalString('DATABASE_PWD_ENCRYPTED');
					}

					// By default, we use default setup for encryption rule
					if (!in_array($cryptType, array('auto'))) {
						$cryptType = 'auto';
					}

					// Check crypted password according to crypt algorithm
					if ($cryptType == 'auto') {
						if ($passcrypted && dol_verifyHash($pass, $passcrypted, '0')) {
							$passok = true;
						}
					}

					// Password ok ?
					if ($passok) {
						$id = $obj->id;
					} else {
						dol_syslog(__METHOD__ .' Authentication KO bad password for ' . $login . ', cryptType=' . $cryptType, LOG_NOTICE);
						sleep(1); // Brut force protection. Must be same delay when login is not valid
						return -3;
					}
				}
			} else {
				dol_syslog(__METHOD__ . ' Many third-party account found for login"' . $login . '" and site="dolibarr_portal"', LOG_ERR);
				return -2;
			}
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}

		return $id;
	}
}
