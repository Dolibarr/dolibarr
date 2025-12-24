<?php
/* Copyright (C) 2007-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2007-2009 Regis Houssin        <regis.houssin@inodbox.com>
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
 *      \file       htdocs/core/login/functions_googleoauth.php
 *      \ingroup    core
 *      \brief      Authentication functions for Google OAuth mode using "Server flow"
 *                  Another method could be to use the "Implicit flow" using Google-Signin library.
 */



//include_once DOL_DOCUMENT_ROOT.'/core/class/openid.class.php';


/**
 * Check validity of user/password/entity
 * If test is ko, reason must be filled into $_SESSION["dol_loginmesg"]
 *
 * @param	string	$usertotest		Login
 * @param	string	$passwordtotest	Password
 * @param   int		$entitytotest   Number of instance (always 1 if module multicompany not enabled)
 * @return	string					Login if OK, '' if KO
 */
function check_user_password_googleoauth($usertotest, $passwordtotest, $entitytotest)
{
	global $conf;

	dol_syslog("functions_googleoauth::check_user_password_googleoauth usertotest=".$usertotest." GETPOST('actionlogin')=".GETPOST('actionlogin'));

	$login = '';

	// Get identity from user and redirect browser to Google OAuth Server
	if (GETPOST('actionlogin') == 'login') {
		if (GETPOST('beforeoauthloginredirect')) {
			// We post the form on the login page by clicking on the link to login using Google.
			dol_syslog("We post the form on the login page by clicking on the link to login using Google. We save _SESSION['datafromloginform']");

			// We save data of form into a variable
			$_SESSION['datafromloginform'] = array(
				'entity'=>GETPOST('entity', 'int'), // avoid to return 0 if entity var not exists
				'backtopage'=>GETPOST('backtopage'),
				'tz'=>GETPOST('tz'),
				'tz_string'=>GETPOST('tz_string'),
				'dst_observed'=>GETPOST('dst_observed'),
				'dst_first'=>GETPOST('dst_first'),
				'dst_second'=>GETPOST('dst_second'),
				'dol_screenwidth'=>GETPOST('screenwidth'),
				'dol_screenheight'=>GETPOST('screenheight'),
				'dol_hide_topmenu'=>GETPOST('dol_hide_topmenu'),
				'dol_hide_leftmenu'=>GETPOST('dol_hide_leftmenu'),
				'dol_optimize_smallscreen'=>GETPOST('dol_optimize_smallscreen'),
				'dol_no_mouse_hover'=>GETPOST('dol_no_mouse_hover'),
				'dol_use_jmobile'=>GETPOST('dol_use_jmobile')
			);

			// Make the redirect to the google_authcallback.php page to start the redirect to Google OAUTH.

			// Define $urlwithroot
			//global $dolibarr_main_url_root;
			//$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
			//$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
			$urlwithroot=DOL_MAIN_URL_ROOT;					// This is to use same domain name than current

			//$shortscope = 'userinfo_email,userinfo_profile';
			$shortscope = 'openid,email,profile';	// For openid connect

			$oauthstateanticsrf = bin2hex(random_bytes(128/8));
			$_SESSION['oauthstateanticsrf'] = $shortscope.'-'.$oauthstateanticsrf;

			$url = $urlwithroot.'/core/modules/oauth/google_oauthcallback.php?shortscope='.urlencode($shortscope).'&state='.urlencode('forlogin-'.$shortscope.'-'.$oauthstateanticsrf).'&username='.urlencode($usertotest);

			// we go on oauth provider authorization page
			header('Location: '.$url);
			exit();
		}

		if (GETPOST('afteroauthloginreturn')) {
			// We reach this code after a call of a redirect to the targeted page from the callback url page of Google OAUTH2
			dol_syslog("We reach the code after a call of a redirect to the targeted page from the callback url page of Google OAUTH2");

			$tmparray = (empty($_SESSION['datafromloginform']) ? array() : $_SESSION['datafromloginform']);

			if (!empty($tmparray)) {
				$_POST['entity'] = $tmparray['entity'];
				$_POST['backtopage'] = $tmparray['backtopage'];
				$_POST['tz'] = $tmparray['tz'];
				$_POST['tz_string'] = $tmparray['tz_string'];
				$_POST['dst_observed'] = $tmparray['dst_observed'];
				$_POST['dst_first'] = $tmparray['dst_first'];
				$_POST['dst_second'] = $tmparray['dst_second'];
				$_POST['screenwidth'] = $tmparray['dol_screenwidth'];
				$_POST['screenheight'] = $tmparray['dol_screenheight'];
				$_POST['dol_hide_topmenu'] = $tmparray['dol_hide_topmenu'];
				$_POST['dol_hide_leftmenu'] = $tmparray['dol_hide_leftmenu'];
				$_POST['dol_optimize_smallscreen'] = $tmparray['dol_optimize_smallscreen'];
				$_POST['dol_no_mouse_hover'] = $tmparray['dol_no_mouse_hover'];
				$_POST['dol_use_jmobile'] = $tmparray['dol_use_jmobile'];
			}

			// If googleoauth_login has been set (by google_oauthcallback after a successful OAUTH2 request on openid scope
			if (!empty($_SESSION['googleoauth_receivedlogin']) && dol_verifyHash($conf->file->instance_unique_id.$usertotest, $_SESSION['googleoauth_receivedlogin'], '0')) {
				dol_syslog("Login received by Google OAuth was validated by callback page and saved crypted into session. This login is ".$usertotest);
				unset($_SESSION['googleoauth_receivedlogin']);
				$login = $usertotest;
			}
		}
	}

	return $login;
}
