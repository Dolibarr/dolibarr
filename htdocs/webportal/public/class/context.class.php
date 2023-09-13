<?php

require_once __DIR__ . '/controller.class.php';

/**
 * Class Context
 */
class Context
{
	/**
	 * @var Singleton
	 * @access private
	 * @static
	 */
	private static $_instance = null;

	/**
	 * @var	DoliDb	$db		Database handler
	 */
	public $db;

	public $title;
	public $desc;

	public $meta_title;
	public $meta_desc;

	/**
	 * The application name
	 * @var $appliName
	 */
	public $appliName;

	public $controller;
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

	public $action;

	public $tplDir;
	public $tplPath;
	public $topMenu;

	public $rootUrl;

	public $menu_active = array();

	public $eventMessages = array();

	public $tokenKey = 'ctoken';

	/**
	 * Curent object of page
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

		$this->tplPath = realpath(__DIR__ . '/../tpl');

		$this->controller = GETPOST('controller', 'aZ09'); // for security, limited to 'aZ09'
		$this->action = GETPOST('action', 'aZ09');// for security, limited to 'aZ09'

		if (empty($this->controller)) {
			$this->controller = 'default';
		}

		$this->appliName = !empty($conf->global->WEBPORTAL_TITLE) ? $conf->global->WEBPORTAL_TITLE : $conf->global->MAIN_INFO_SOCIETE_NOM;

		$this->generateNewToken();

		$this->initController();

		// Init de l'url de base
		$this->rootUrl = self::getRootConfigUrl();
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
	 * @return  bool
	 */
	public function initController()
	{
		global $db, $conf, $langs;

		$defaultControllersPath = __DIR__ . '/../controllers/';

		// define controllers definition
		$this->addControllerDefinition('login', $defaultControllersPath . 'login.controller.php', 'LoginController');
		$this->addControllerDefinition('default', $defaultControllersPath . 'default.controller.php', 'DefaultController');
		$this->addControllerDefinition('document', $defaultControllersPath . 'document.controller.php', 'DocumentController');
		$this->addControllerDefinition('propallist', $defaultControllersPath . 'propallist.controller.php', 'PropalListController');
		$this->addControllerDefinition('orderlist', $defaultControllersPath . 'orderlist.controller.php', 'OrderListController');
		$this->addControllerDefinition('invoicelist', $defaultControllersPath . 'invoicelist.controller.php', 'InvoiceListController');
		$this->addControllerDefinition('membercard', $defaultControllersPath . 'membercard.controller.php', 'MemberCardController');
		$this->addControllerDefinition('partnershipcard', $defaultControllersPath . 'partnershipcard.controller.php', 'PartnershipCardController');

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
		$needle = '.controller.php';
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
	static public function getRootConfigUrl()
	{
		global $conf;

		// Init de l'url de base
		if (!empty($conf->global->WEBPORTAL_ROOT_URL)) {
			$rootUrl = $conf->global->WEBPORTAL_ROOT_URL;
			if (substr($rootUrl, -1) !== '/') {
				$rootUrl .= '/';
			}
		} else {
			$rootUrl = dol_buildpath('/webportal/public/', 2);
		}

		return $rootUrl;
	}

	/**
	 * Get root url
	 *
	 * @param	string			$controller		Controller name
	 * @param	string|array	$moreParams		More parameters
	 * @param	bool			$addToken		Add token hash only if $controller is setted
	 * @return	string
	 * @deprecated see getControllerUrl()
	 */
	public function getRootUrl($controller = false, $moreParams = '', $addToken = true)
	{
		return self::getControllerUrl($controller, $moreParams, $addToken);
	}

	/**
	 * Get controller url according to context
	 *
	 * @param	string			$controller		Controller name
	 * @param	string|array	$moreParams		More parameters
	 * @param	bool			$addToken		Add token hash only if controller is set
	 * @return	string
	 */
	public function getControllerUrl($controller = false, $moreParams = '', $addToken = true)
	{
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
	 * @param 	bool			$controller				Controller
	 * @param 	string|array	$moreParams				More parameters
	 * @param	array			$Tparams				Parameters
	 * @return	string
	 */
	static public function getPublicControllerUrl($controller = false, $moreParams = '', $Tparams = array())
	{
		$url = self::getRootConfigUrl();

		if (empty($controller)) {
			// because can be called without params to get only rootUrl
			return $url;
		}

		$Tparams['controller'] = $controller;

		// if $moreParams is an array
		if (!empty($moreParams) && is_array($moreParams)) {
			if (isset($moreParams['controller'])) unset($moreParams['controller']);
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
				if ($moreParams[0] !== '?') $url .= '?';
				if ($moreParams[0] === '&') $moreParams = substr($moreParams, 1);
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
	static public function urlOrigin($withRequestUri = true, $use_forwarded_host = false)
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
	 * @param 	array	$errors		Errors
	 * @return	void
	 */
	public function setError($errors)
	{
		if (!is_array($errors)) $errors = array($errors);
		if (!isset($_SESSION['webportal_errors'])) $_SESSION['webportal_errors'] = array();
		foreach ($errors as $msg) {
			if (!in_array($msg, $_SESSION['webportal_errors'])) $_SESSION['webportal_errors'][] = $msg;
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
	 * @param	string		$mesg	Message string
	 * @param	array|null	$mesgs	Message array
	 * @param	string		$style	Which style to use ('mesgs' by default, 'warnings', 'errors')
	 * @return	void
	 */
	public function setEventMessages($mesg, $mesgs, $style = 'mesgs')
	{
		if (empty($mesg) && empty($mesgs)) {
			dol_syslog(__METHOD__ . ' Try to add a message in stack, but value to add is empty message', LOG_WARNING);
		} else {
			if (!in_array((string) $style, array('mesgs', 'warnings', 'errors'))) {
				dol_print_error('', 'Bad parameter style=' . $style . ' for setEventMessages');
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
	 * @param	false|string	$controller			Controller
	 * @param	bool			$generateIfNull		Generate if null
	 * @return  string
	 */
	public function newToken($controller = false, $generateIfNull = true)
	{
		if (empty($controller)) {
			$controller = !empty($this->controller) ? $this->controller : 'default';
		}

		if (
			!isset($_SESSION['controllers_tokens'][$controller]['newToken'])
			&& $generateIfNull
		) {
			$this->generateNewToken($controller);
		}

		return !empty($_SESSION['controllers_tokens'][$controller]['newToken']) ? $_SESSION['controllers_tokens'][$controller]['newToken'] : '';
	}

	/**
	 * Generate new token.
	 *
	 * @param	false|string		$controller		Controller
	 * @return	string
	 */
	protected function generateNewToken($controller = false)
	{
		if (empty($controller)) {
			$controller = !empty($this->controller) ? $this->controller : 'default';
		}

		if (empty($_SESSION['controllers_tokens'])) {
			$_SESSION['controllers_tokens'] = array();
		}
		if (empty($_SESSION['controllers_tokens'][$controller])) {
			$_SESSION['controllers_tokens'][$controller] = array();
		}

		// Creation of a token against CSRF vulnerabilities
		if (!defined('NOTOKENRENEWAL')) {
			// Rolling token at each call ($_SESSION['token'] contains token of previous page)
			if (isset($_SESSION['controllers_tokens'][$controller]['newToken'])) {
				$_SESSION['controllers_tokens'][$controller]['token'] = $_SESSION['controllers_tokens'][$controller]['newToken'];
			}

			// Save what will be next token. Into forms, we will add param $context->newToken();
			$token = dol_hash(uniqid(mt_rand(), true)); // Generat
			$_SESSION['controllers_tokens'][$controller]['newToken'] = $token;

			return $token;
		} else {
			return $this->newToken($controller, false);
		}
	}

	/**
	 * Return the value of token currently saved into session with name 'token'.
	 *
	 * @param	bool		$controller		Controller
	 * @return  string
	 */
	public function currentToken($controller = false)
	{
		if (empty($controller)) {
			$controller = !empty($this->controller) ? $this->controller : 'default';
		}

		return isset($_SESSION['controllers_tokens'][$controller]['token']) ? $_SESSION['controllers_tokens'][$controller]['token'] : false;
	}

	/**
	 * Validate token
	 *
	 * @param	false 	$controller	Controller
	 * @param	bool	$erase		Erase
	 * @return  bool
	 */
	public function validateToken($controller = false, $erase = true)
	{
		$token = GETPOST($this->tokenKey, 'aZ09');

		if (empty($controller)) {
			$controller = !empty($this->controller) ? $this->controller : 'default';
		}
		$currentToken = $this->currentToken($controller);

		if (empty($currentToken)) return false;
		if (empty($token)) return false;
		if ($currentToken === $token) {
			if ($erase) {
				unset($_SESSION['controllers_tokens'][$controller]['token']);
			}
			return true;
		}
	}

	/**
	 * Get token url
	 *
	 * @param	false|string	$controller		Controller
	 * @return	string|null
	 */
	public function getUrlToken($controller = false)
	{
		if (empty($controller)) {
			$controller = !empty($this->controller) ? $this->controller : 'default';
		}

		$token = $this->newToken($controller);
		if ($token) {
			return '&' . $this->tokenKey . '=' . $this->newToken($controller);
		}
	}

	/**
	 * Get token input for form
	 *
	 * @param	false|string	$controller		Controller
	 * @return  string|null
	 */
	public function getFormToken($controller = false)
	{
		if (empty($controller)) {
			$controller = !empty($this->controller) ? $this->controller : 'default';
		}

		$token = $this->newToken($controller);
		if ($token) {
			return '<input type="hidden" name="' . $this->tokenKey . '" value="' . $this->newToken($controller) . '" />';
		}
	}

	/**
	 * Try to find the third-party account id from
	 *
	 * @param	string	$login		Login
	 * @param	string	$pass		Password
	 * @return  int		Third-party account id
	 */
	public function getThirdPartyAccountFromLogin($login, $pass)
	{
		$id = 0;

		$sql = "SELECT sa.rowid as id";
		$sql .= " FROM " . $this->db->prefix() . "societe_account as sa";
		$sql .= " WHERE BINARY sa.login = '" . $this->db->escape($login) . "'"; // case sensitive
		$sql .= " AND BINARY sa.pass_crypted = '" . $this->db->escape($pass) . "'"; // case sensitive
		$sql .= " AND sa.site = 'dolibarr_portal'";
		$sql .= " AND sa.status = 1";
		$sql .= " AND sa.entity IN (" . getEntity('societe') . ")";

		dol_syslog(__METHOD__ . ' Try to find the third-party account id for login"' . $login . '" and site="dolibarr_portal"', LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			if ($this->db->num_rows($result) == 1) {
				$obj = $this->db->fetch_object($result);
				$id = $obj->id;
			}
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}

		return $id;
	}
}
