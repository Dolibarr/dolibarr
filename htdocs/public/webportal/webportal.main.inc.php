<?php
/* Copyright (C) 2023-2024 	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2023-2024	Lionel Vessiller		<lvessiller@easya.solutions>
 * Copyright (C) 2024-2025	Frédéric France			<frederic.france@free.fr>
 * Copyright (C) 2024-2025	MDW						<mdeweerd@users.noreply.github.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    htdocs/public/webportal/webportal.main.inc.php
 * \ingroup webportal
 * \brief   Main include file for WebPortal
 */

if (!defined('WEBPORTAL')) {
	define('WEBPORTAL', 1);
}
if (!defined('NOLOGIN')) {
	define('NOLOGIN', 1);
}
if (!defined('NOREQUIREUSER')) {
	define('NOREQUIREUSER', 1);
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', 1);
}
if (!defined('NOREQUIRESOC')) {
	define('NOREQUIRESOC', 1);
}
if (!defined('EVEN_IF_ONLY_LOGIN_ALLOWED')) {
	define('EVEN_IF_ONLY_LOGIN_ALLOWED', 1);
}
if (!defined('NOIPCHECK')) {
	define('NOIPCHECK', 1);
}


if (!function_exists('dol_getprefix')) {
	/**
	 *  Return a prefix to use for this Dolibarr instance, for session/cookie names or email id.
	 *  The prefix is unique for instance and avoid conflict between multi-instances, even when having two instances with same root dir
	 *  or two instances in same virtual servers.
	 *  This function must not use dol_hash (that is used for password hash) and need to have all context $conf loaded when called.
	 *
	 *  @param  string  $mode                   '' (prefix for session name) or 'email' (prefix for email id)
	 *  @return	string                          A calculated prefix
	 */
	function dol_getprefix($mode = '')  // @phan-suppress-current-line PhanRedefineFunction
	{
		global $dolibarr_main_instance_unique_id,
		$dolibarr_main_cookie_cryptkey; // This is loaded by filefunc.inc.php

		$tmp_instance_unique_id = empty($dolibarr_main_instance_unique_id) ?
			(empty($dolibarr_main_cookie_cryptkey) ? '' :
				$dolibarr_main_cookie_cryptkey) : $dolibarr_main_instance_unique_id;
		// Unique id of instance

		// The recommended value (may be not defined for old versions)
		if (!empty($tmp_instance_unique_id)) {
			return sha1('webportal' . $tmp_instance_unique_id);
		} else {
			return sha1('webportal' . $_SERVER['SERVER_NAME'].$_SERVER['DOCUMENT_ROOT'].DOL_DOCUMENT_ROOT);
		}
	}
}

include __DIR__.'/../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societeaccount.class.php';
require_once DOL_DOCUMENT_ROOT . '/public/webportal/lib/webportal.lib.php';
require_once DOL_DOCUMENT_ROOT . '/webportal/class/context.class.php';
require_once DOL_DOCUMENT_ROOT . '/webportal/class/webportalmember.class.php';
require_once DOL_DOCUMENT_ROOT . '/webportal/class/webportalpartnership.class.php';
/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 *
 * @var string $sessionname
 * @var string $sessiontimeout
 */
'
@phan-var-force string $sessionname
@phan-var-force string $sessiontimeout
';
// Init session. Name of session is specific to WEBPORTAL instance.
// Must be done after the include of filefunc.inc.php so global variables of conf file are defined (like $dolibarr_main_instance_unique_id or $dolibarr_main_force_https).
// Note: the function dol_getprefix is defined into functions.lib.php but may have been defined to return a different key to manage another area to protect.
//$prefix = dol_getprefix('');
//$sessionname = 'WEBPORTAL_SESSID_' . $prefix;
//$sessiontimeout = 'WEBPORTAL_SESSTIMEOUT_' . $prefix;
//if (!empty($_COOKIE[$sessiontimeout]) && session_status() === PHP_SESSION_NONE) {
//	ini_set('session.gc_maxlifetime', $_COOKIE[$sessiontimeout]);
//}

$context = Context::getInstance();
$context->initController();

$hookmanager->initHooks(array('main', 'webportal'));

$logged_user = new User($db);
$anti_spam_session_key = 'dol_antispam_value';

if (!defined('NOREQUIREDB') && empty($conf->webportal->enabled)) {
	accessforbidden('Module not activated');
}

if (!defined('WEBPORTAL_NOREQUIRETRAN') || (!defined('WEBPORTAL_NOLOGIN') && !empty($context->controllerInstance->accessNeedLoggedUser))) {
	if (!is_object($langs)) { // This can occurs when calling page with NOREQUIRETRAN defined, however we need langs for error messages.
		include_once DOL_DOCUMENT_ROOT . '/core/class/translate.class.php';
		$langs = new Translate("", $conf);
		$langcode = (GETPOST('lang', 'aZ09', 1) ? GETPOST('lang', 'aZ09', 1) : (empty($logged_user->conf->MAIN_LANG_DEFAULT) ? getDolGlobalString('MAIN_LANG_DEFAULT', 'auto') : $logged_user->conf->MAIN_LANG_DEFAULT));
		if (defined('MAIN_LANG_DEFAULT')) {
			$langcode = constant('MAIN_LANG_DEFAULT');
		}
		$langs->setDefaultLang($langcode);
	}
	$langs->loadLangs(array('website', 'main'));
}

/*
 * Phase authentication / login
 */
if (getDolGlobalInt('WEBPORTAL_LOGIN_BY_MODULE') && !empty($conf->modules_parts['webportallogin']) && is_array($conf->modules_parts['webportallogin'])) {
	foreach ($conf->modules_parts['webportallogin'] as $module => $file) {
		$path=dol_buildpath("/$module/$file");
		include_once $path;
		break;
	}
} elseif (!defined('WEBPORTAL_NOLOGIN') && !empty($context->controllerInstance->accessNeedLoggedUser)) {
	$error = 0;

	// Hooks for security access
	$hookmanager->initHooks(array('login'));
	$parameters = array(
		"webportal_sessionname" => $sessionname,
		"webportal_anti_spam_session_key" => $anti_spam_session_key,
	);
	$reshook = $hookmanager->executeHooks('beforeLoginAuthentication', $parameters, $context);
	if ($reshook < 0) {
		$error++;
	} elseif (empty($reshook)) {
		$admin_error_messages = array();
		$webportal_logged_thirdparty_account_id = isset($_SESSION["webportal_logged_thirdparty_account_id"]) && $_SESSION["webportal_logged_thirdparty_account_id"] > 0 ? $_SESSION["webportal_logged_thirdparty_account_id"] : 0;
		$webportal_logged_member_account_id = isset($_SESSION["webportal_logged_member_account_id"]) && $_SESSION["webportal_logged_member_account_id"] > 0 ? $_SESSION["webportal_logged_member_account_id"] : 0;

		if (!$context->userIsLog()) {
			// It is not already authenticated and it requests the login / password
			$langs->loadLangs(array("other", "help", "admin"));

			$action = GETPOST('action_login', 'alphanohtml');

			if ($action == 'login') {
				$login = GETPOST('login', 'alphanohtml');
				$password = GETPOST('password', 'password');
				// $security_code = GETPOST('security_code', 'alphanohtml');

				if (empty($login)) {
					$context->setEventMessage($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Login")), 'errors');
					$focus_element = 'login';
					$error++;
				}
				if (empty($password)) {
					$context->setEventMessage($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Password")), 'errors');
					if (empty($focus_element)) {
						$focus_element = 'password';
					}
					$error++;
				}
				// check security graphic code
				//if (!$error && (array_key_exists($anti_spam_session_key, $_SESSION) === false ||
				//		(strtolower($_SESSION[$anti_spam_session_key]) !== strtolower($security_code)))
				//) {
				//	$context->setEventMessage($langs->trans("ErrorBadValueForCode"), 'errors');
				//	if (empty($focus_element)) $focus_element = 'security_code';
				//	$error++;
				//}

				if (!$error && (isModEnabled('societe') && !getDolGlobalInt('WEBPORTAL_LOGIN_BY_MEMBER_ACCOUNT'))) {
					// fetch third-party account from login and account type
					$thirdparty_account_id = $context->getThirdPartyAccountFromLogin($login, $password);
					if ($thirdparty_account_id <= 0) {
						$error++;
						dol_syslog($langs->transnoentitiesnoconv('WebPortalErrorFetchThirdPartyAccountFromLogin', $login), LOG_WARNING);
					} else {
						$_SESSION["webportal_logged_thirdparty_account_id"] = $thirdparty_account_id;
						$webportal_logged_thirdparty_account_id = $thirdparty_account_id;
						$context->controller = 'default';
						$context->initController();
					}
				} elseif (!$error && isModEnabled('member') && getDolGlobalInt('WEBPORTAL_LOGIN_BY_MEMBER_ACCOUNT') && !getDolGlobalString('ADHERENT_LOGIN_NOT_REQUIRED')) {
					// fetch member account from login
					$member_account_id = $context->getMemberAccountFromLogin($login, $password);
					if ($member_account_id <= 0) {
						$error++;
						dol_syslog($langs->transnoentitiesnoconv('WebPortalErrorFetchMemberAccountFromLogin', $login), LOG_WARNING);
					} else {
						$_SESSION["webportal_logged_member_account_id"] = $member_account_id;
						$webportal_logged_member_account_id = $member_account_id;
						$context->controller = 'default';
						$context->initController();
					}
				} else {
					$error++;
					dol_syslog($langs->transnoentitiesnoconv('WebPortalErrorNoFetchMethod'), LOG_WARNING);
				}

				if ($error) {
					$context->setEventMessage($langs->transnoentitiesnoconv('WebPortalErrorAuthentication'), 'errors');
				}
			}

			if (empty($webportal_logged_thirdparty_account_id) && empty($webportal_logged_member_account_id)) {
				// Set cookie for timeout management
				if (getDolGlobalString('MAIN_SESSION_TIMEOUT')) {
					dolSetCookie($sessiontimeout, getDolGlobalString('MAIN_SESSION_TIMEOUT'), 0);
				}

				$context->controller = 'login';
				$context->initController();
			}
		}

		if (!$error && $context->userIsLog()) {
			$logged_member = null;
			$websiteaccount = null;
			// We are already into an authenticated session
			if (isModEnabled('member') && getDolGlobalInt('WEBPORTAL_LOGIN_BY_MEMBER_ACCOUNT') && !getDolGlobalString('ADHERENT_LOGIN_NOT_REQUIRED')) {
				// get member
				$logged_member = new WebPortalMember($db);
				$result = $logged_member->fetch($webportal_logged_member_account_id);
				if ($result <= 0) {
					$error++;

					// Account has been removed after login
					dol_syslog("Can't load member account (ID: $webportal_logged_member_account_id) even if session logged.", LOG_WARNING);
					session_destroy();
					session_set_cookie_params(0, '/', null, !empty($dolibarr_main_force_https), true); // Add tag secure and httponly on session cookie
					session_name($sessionname);
					session_start();

					$error_msg = $langs->transnoentitiesnoconv('WebPortalErrorFetchLoggedMember', (string) $webportal_logged_member_account_id);
					dol_syslog($error_msg, LOG_ERR);
					$context->setEventMessage($error_msg, 'errors');
				}
			} else {
				$websiteaccount = new SocieteAccount($db);
				$result = $websiteaccount->fetch($webportal_logged_thirdparty_account_id);

				if ($result <= 0) {
					$error++;

					// Account has been removed after login
					dol_syslog("Can't load third-party account (ID: $webportal_logged_thirdparty_account_id) even if session logged.", LOG_WARNING);
					session_destroy();
					session_set_cookie_params(0, '/', null, !empty($dolibarr_main_force_https), true); // Add tag secure and httponly on session cookie
					session_name($sessionname);
					session_start();

					$context->setEventMessage($langs->transnoentitiesnoconv('WebPortalErrorFetchLoggedThirdPartyAccount', $webportal_logged_thirdparty_account_id), 'errors');
				}
			}

			if (!$error) {
				$user_id = getDolGlobalInt('WEBPORTAL_USER_LOGGED');

				if ($user_id <= 0) {
					$error++;
					$error_msg = $langs->transnoentitiesnoconv('WebPortalSetupNotComplete');
					dol_syslog($error_msg, LOG_WARNING);
					$context->setEventMessages($error_msg, null, 'errors');
				}

				if (!$error) {
					$result = $logged_user->fetch($user_id);
					if ($result <= 0) {
						$error++;
						$error_msg = $langs->transnoentitiesnoconv('WebPortalErrorFetchLoggedUser', (string) $user_id);
						dol_syslog($error_msg, LOG_ERR);
						$context->setEventMessages($error_msg, null, 'errors');
					}
				}

				if (!getDolGlobalInt('WEBPORTAL_LOGIN_BY_MEMBER_ACCOUNT')) {
					if (!$error) {
						// get third-party
						$logged_thirdparty = $websiteaccount->thirdparty;
						if (!$logged_thirdparty || !($logged_thirdparty->id > 0)) {
							$result = $websiteaccount->fetch_thirdparty();

							if ($result < 0) {
								$error_msg = $langs->transnoentitiesnoconv('WebPortalErrorFetchLoggedThirdParty', (string) $websiteaccount->fk_soc);
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
								$error_msg = $langs->transnoentitiesnoconv('WebPortalErrorFetchLoggedMember', (string) $websiteaccount->thirdparty->id);
								dol_syslog($error_msg, LOG_ERR);
								$context->setEventMessage($error_msg, 'errors');
							}

							if (isModEnabled('partnership') && !$error && $logged_member->id > 0) {
								// get partnership
								$logged_partnership = new WebPortalPartnership($db);
								// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
								$result = $logged_partnership->fetch(0, '', $logged_member->id, $websiteaccount->thirdparty->id);
								if ($result < 0) {
									$error++;
									$error_msg = $langs->transnoentitiesnoconv('WebPortalErrorFetchLoggedPartnership', (string) $websiteaccount->thirdparty->id, (string) $logged_member->id);
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
									$langs->loadLangs(array('website', 'main'));
								}

								$context->logged_thirdparty = $logged_thirdparty;
								if (!empty($logged_partnership)) {
									$context->logged_partnership = $logged_partnership;
								}
							}
						}
					}
				}

				if (!$error) {
					$context->logged_user = $logged_user;
					if (!empty($logged_member)) {
						$context->logged_member = $logged_member;
					}

					global $user; // set global user as logged user (used for hooks in external modules)
					$user = $context->logged_user;
				}
			}
		}
	}

	if ($error) {
		// Hooks on failed login
		$parameters = array(
			"webportal_sessionname" => $sessionname,
			"webportal_anti_spam_session_key" => $anti_spam_session_key,
		);
		$reshook = $hookmanager->executeHooks('afterLoginFailed', $parameters, $context);
		if ($reshook < 0) {
			$error++;
		}
	} else {
		// Hooks on after login
		$parameters = array(
			"webportal_sessionname" => $sessionname,
			"webportal_anti_spam_session_key" => $anti_spam_session_key,
		);
		$reshook = $hookmanager->executeHooks('afterLogin', $parameters, $context);
		if ($reshook < 0) {
			$error++;
		}
	}
}
