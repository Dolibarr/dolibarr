<?php
define('WEBPORTAL', 1);
define('NOSESSION', 1);
define('NOLOGIN', 1);
define('NOREQUIREUSER', 1); // $user
define('NOREQUIREMENU', 1);
define('NOREQUIRESOC', 1); // $mysoc
define('EVEN_IF_ONLY_LOGIN_ALLOWED', 1);

// Change this following line to use the correct relative path (../, ../../, etc)
$res = 0;
if (!$res && file_exists('../../main.inc.php')) $res = @include '../../main.inc.php';                // to work if your module directory is into dolibarr root htdocs directory
if (!$res && file_exists('../../../main.inc.php')) $res = @include '../../../main.inc.php';            // to work if your module directory is into a subdir of root htdocs directory
if (!$res && file_exists('../../../../main.inc.php')) $res = @include '../../../../main.inc.php';            // to work if your module directory is into a subdir of root htdocs directory
if (!$res) die('Include of main fails');
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societeaccount.class.php';
dol_include_once('/webportal/public/lib/webportal.lib.php');
//dol_include_once('/webportal/public/lib/login.lib.php');
dol_include_once('/webportal/public/class/context.class.php');
dol_include_once('/webportal/class/webportalmember.class.php');
dol_include_once('/webportal/class/webportalpartnership.class.php');

// Init session. Name of session is specific to WEBPORTAL instance.
// Must be done after the include of filefunc.inc.php so global variables of conf file are defined (like $dolibarr_main_instance_unique_id or $dolibarr_main_force_https).
// Note: the function dol_getprefix is defined into functions.lib.php but may have been defined to return a different key to manage another area to protect.
$prefix = dol_getprefix('');
$sessionname = 'WEBPORTAL_SESSID_' . $prefix;
$sessiontimeout = 'WEBPORTAL_SESSTIMEOUT_' . $prefix;
if (!empty($_COOKIE[$sessiontimeout])) {
	ini_set('session.gc_maxlifetime', $_COOKIE[$sessiontimeout]);
}

// This create lock, released by session_write_close() or end of page.
// We need this lock as long as we read/write $_SESSION ['vars']. We can remove lock when finished.
session_set_cookie_params(0, '/', null, (empty($dolibarr_main_force_https) ? false : true), true); // Add tag secure and httponly on session cookie (same as setting session.cookie_httponly into php.ini). Must be called before the session_start.
session_name($sessionname);
session_start();

$context = Context::getInstance();

$hookmanager->initHooks(array('main'));

$logged_user = new User($db);
$anti_spam_session_key = 'dol_antispam_value';

if (!defined('NOREQUIREDB') && empty($conf->webportal->enabled)) {
	accessforbidden('Module not activated');
}

if (!defined('WEBPORTAL_NOREQUIRETRAN') || (!defined('WEBPORTAL_NOLOGIN') && !empty($context->controllerInstance->accessNeedLoggedUser))) {
	if (!is_object($langs)) { // This can occurs when calling page with NOREQUIRETRAN defined, however we need langs for error messages.
		include_once DOL_DOCUMENT_ROOT . '/core/class/translate.class.php';
		$langs = new Translate("", $conf);
		$langcode = (GETPOST('lang', 'aZ09', 1) ? GETPOST('lang', 'aZ09', 1) : (empty($logged_user->conf->MAIN_LANG_DEFAULT) ? (empty($conf->global->MAIN_LANG_DEFAULT) ? 'auto' : $conf->global->MAIN_LANG_DEFAULT) : $logged_user->conf->MAIN_LANG_DEFAULT));
		if (defined('MAIN_LANG_DEFAULT')) {
			$langcode = constant('MAIN_LANG_DEFAULT');
		}
		$langs->setDefaultLang($langcode);
	}
	$langs->loadLangs(array('webportal@webportal', 'main'));
}

/*
 * Phase authentication / login
 */
if (!defined('WEBPORTAL_NOLOGIN') && !empty($context->controllerInstance->accessNeedLoggedUser)) {
	$admin_error_messages = array();
	$webportal_logged_thirdparty_account_id = isset($_SESSION["webportal_logged_thirdparty_account_id"]) && $_SESSION["webportal_logged_thirdparty_account_id"] > 0 ? $_SESSION["webportal_logged_thirdparty_account_id"] : 0;
	if (empty($webportal_logged_thirdparty_account_id)) {
		// It is not already authenticated and it requests the login / password
		$langs->loadLangs(array("other", "help", "admin"));

		$error = 0;
		$action = GETPOST('action_login', 'alphanohtml');

		if ($action == 'login') {
			$login = GETPOST('login', 'alphanohtml');
			$password = GETPOST('password', 'none');
//			$security_code = GETPOST('security_code', 'alphanohtml');

			if (empty($login)) {
				$context->setEventMessage($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Login")), 'errors');
				$focus_element = 'login';
				$error++;
			}
			if (empty($password)) {
				$context->setEventMessage($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Password")), 'errors');
				if (empty($focus_element)) $focus_element = 'password';
				$error++;
			}
			// Verification security graphic code
//			if (!$error && (array_key_exists($anti_spam_session_key, $_SESSION) === false ||
//					(strtolower($_SESSION[$anti_spam_session_key]) !== strtolower($security_code)))
//			) {
//				$context->setEventMessage($langs->trans("ErrorBadValueForCode"), 'errors');
//				if (empty($focus_element)) $focus_element = 'security_code';
//				$error++;s
//			}

			if (!$error) {
				// fetch third-party account from login and account type
				$thirdparty_account_id = $context->getThirdPartyAccountFromLogin($login, $password);
				if ($thirdparty_account_id <= 0) {
					$error++;
					dol_syslog($langs->transnoentitiesnoconv('WebPortalErrorFetchThirdPartyAccountFromLogin', $login), LOG_WARNING);
					$context->setEventMessage($langs->transnoentitiesnoconv('WebPortalErrorAuthentication'), 'errors');
				} else {
					$_SESSION["webportal_logged_thirdparty_account_id"] = $thirdparty_account_id;
					$webportal_logged_thirdparty_account_id = $thirdparty_account_id;
					$context->controller = 'default';
					$context->initController();
				}
			}
		}

		if (empty($webportal_logged_thirdparty_account_id)) {
			// Set cookie for timeout management
			if (!empty($conf->global->MAIN_SESSION_TIMEOUT)) {
				setcookie($sessiontimeout, $conf->global->MAIN_SESSION_TIMEOUT, 0, "/", null, (empty($dolibarr_main_force_https) ? false : true), true);
			}

			$context->controller = 'login';
			$context->initController();
		}
	}

	if ($webportal_logged_thirdparty_account_id > 0) {
		$error = 0;

		// We are already into an authenticated session
		$websiteaccount = new SocieteAccount($db);
		$result = $websiteaccount->fetch($webportal_logged_thirdparty_account_id);
		if ($result <= 0) {
			$error++;

			// Account has been removed after login
			dol_syslog("Can't load third-party account (ID: $webportal_logged_thirdparty_account_id) even if session logged.", LOG_WARNING);
			session_destroy();
			session_set_cookie_params(0, '/', null, (empty($dolibarr_main_force_https) ? false : true), true); // Add tag secure and httponly on session cookie
			session_name($sessionname);
			session_start();

			$context->setEventMessage($langs->transnoentitiesnoconv('WebPortalErrorFetchLoggedThirdPartyAccount', $webportal_logged_thirdparty_account_id), 'errors');
		}

		if (!$error) {
			$user_id = getDolGlobalInt('WEBPORTAL_USER_LOGGED');
			$result = $logged_user->fetch($user_id);
			if ($result <= 0) {
				$error++;
				$error_msg = $langs->transnoentitiesnoconv('WebPortalErrorFetchLoggedUser', $user_id);
				dol_syslog($error_msg, LOG_ERR);
				$context->setEventMessage($error_msg, 'errors');
			}

			if (!$error) {
				// get third-party
				$logged_thirdparty = $websiteaccount->thirdparty;
				if (!$logged_thirdparty || !($logged_thirdparty->id > 0)) {
					$result = $websiteaccount->fetch_thirdparty();
					if ($result < 0) {
						$error_msg = $langs->transnoentitiesnoconv('WebPortalErrorFetchLoggedThirdParty', $websiteaccount->fk_soc);
						//dol_syslog("Can't load third-party (ID: ".$websiteaccount->fk_soc.") even if session logged.", LOG_ERR);
						dol_syslog($error_msg, LOG_ERR);
						$context->setEventMessage($error_msg, 'errors');
						$error++;
					}
				}

				if (!$error) {
					$logged_thirdparty = $websiteaccount->thirdparty;

					// get member
					$logged_member = new WebPortalMember($db);
					$result = $logged_member->fetch(0, '', $websiteaccount->thirdparty->id);
					if ($result < 0) {
						$error++;
						$error_msg = $langs->transnoentitiesnoconv('WebPortalErrorFetchLoggedMember', $websiteaccount->thirdparty->id);
						dol_syslog($error_msg, LOG_ERR);
						$context->setEventMessage($error_msg, 'errors');
					}

					if (!$error) {
						// get partnership
						$logged_partnership = new WebPortalPartnership($db);
						$result = $logged_partnership->fetch(0, '', $logged_member->id, $websiteaccount->thirdparty->id);
						if ($result < 0) {
							$error++;
							$error_msg = $langs->transnoentitiesnoconv('WebPortalErrorFetchLoggedPartnership', $websiteaccount->thirdparty->id, $logged_member->id);
							dol_syslog($error_msg, LOG_ERR);
							$context->setEventMessage($error_msg, 'errors');
						}
					}

					if (!$error) {
						if ($logged_thirdparty->default_lang != $langs->defaultlang && !defined('WEBPORTAL_NOREQUIRETRAN')) {
							if (!is_object($langs)) { // This can occurs when calling page with NOREQUIRETRAN defined, however we need langs for error messages.
								include_once DOL_DOCUMENT_ROOT . '/core/class/translate.class.php';
								$langs = new Translate("", $conf);
								$langs->setDefaultLang($logged_thirdparty->default_lang);
							}
							$langs->loadLangs(array('webportal@webportal', 'main'));
						}

						$context->logged_user = $logged_user;
						$context->logged_thirdparty = $logged_thirdparty;
						$context->logged_member = $logged_member;
						$context->logged_partnership = $logged_partnership;
					}
				}
			}
		}
	}
}
