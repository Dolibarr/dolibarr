<?php
/* Copyright (C) 2022       Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2015       Frederic France      <frederic.france@free.fr>
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
 *      \file       htdocs/core/modules/oauth/generic_oauthcallback.php
 *      \ingroup    oauth
 *      \brief      Page to get oauth callback
 */

// Force keyforprovider
$forlogin = 0;
if (!empty($_GET['state']) && preg_match('/^forlogin-/', $_GET['state'])) {
	$forlogin = 1;
	$_GET['keyforprovider'] = 'Login';
}

if (!defined('NOLOGIN') && $forlogin) {
	define("NOLOGIN", 1); // This means this output page does not require to be logged.
}

// Load Dolibarr environment
require '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/includes/OAuth/bootstrap.php';
use OAuth\Common\Storage\DoliStorage;
use OAuth\Common\Consumer\Credentials;

// Define $urlwithroot
global $dolibarr_main_url_root;
$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
//$urlwithroot=DOL_MAIN_URL_ROOT;					// This is to use same domain name than current

$langs->load("oauth");

$action = GETPOST('action', 'aZ09');
$backtourl = GETPOST('backtourl', 'alpha');
$keyforprovider = GETPOST('keyforprovider', 'aZ09');
if (!GETPOSTISSET('keyforprovider') && !empty($_SESSION["oauthkeyforproviderbeforeoauthjump"]) && (GETPOST('code') || $action == 'delete')) {
	// If we are coming from the Oauth page
	$keyforprovider = $_SESSION["oauthkeyforproviderbeforeoauthjump"];
}
$genericstring = 'GENERIC';


/**
 * Create a new instance of the URI class with the current URI, stripping the query string
 */
$uriFactory = new \OAuth\Common\Http\Uri\UriFactory();
//$currentUri = $uriFactory->createFromSuperGlobalArray($_SERVER);
//$currentUri->setQuery('');
$currentUri = $uriFactory->createFromAbsolute($urlwithroot.'/core/modules/oauth/generic_oauthcallback.php');


/**
 * Load the credential for the service
 */

/** @var \OAuth\ServiceFactory $serviceFactory An OAuth service factory. */
$serviceFactory = new \OAuth\ServiceFactory();
$httpClient = new \OAuth\Common\Http\Client\CurlClient();
// TODO Set options for proxy and timeout
// $params=array('CURLXXX'=>value, ...)
//$httpClient->setCurlParameters($params);
$serviceFactory->setHttpClient($httpClient);

// Setup the credentials for the requests
$keyforparamid = 'OAUTH_'.$genericstring.($keyforprovider ? '-'.$keyforprovider : '').'_ID';
$keyforparamsecret = 'OAUTH_'.$genericstring.($keyforprovider ? '-'.$keyforprovider : '').'_SECRET';
$credentials = new Credentials(
	getDolGlobalString($keyforparamid),
	getDolGlobalString($keyforparamsecret),
	$currentUri->getAbsoluteUri()
);

$state = GETPOST('state');
$statewithscopeonly = '';
$statewithanticsrfonly = '';

$requestedpermissionsarray = array();
if ($state) {
	// 'state' parameter is standard to store a hash value and can be used to retrieve some parameters back
	$statewithscopeonly = preg_replace('/\-.*$/', '', preg_replace('/^forlogin-/', '', $state));
	$requestedpermissionsarray = explode(',', $statewithscopeonly); // Example: 'userinfo_email,userinfo_profile,openid,email,profile,cloud_print'.
	$statewithanticsrfonly = preg_replace('/^.*\-/', '', $state);
}

// Add a test to check that the state parameter is provided into URL when we make the first call to ask the redirect or when we receive the callback
// but not when callback was ok and we recall the page
if ($action != 'delete' && !GETPOSTINT('afteroauthloginreturn') && (empty($statewithscopeonly) || empty($requestedpermissionsarray))) {
	dol_syslog("state or statewithscopeonly and/or requestedpermissionsarray are empty");
	setEventMessages($langs->trans('ScopeUndefined'), null, 'errors');
	if (empty($backtourl)) {
		$backtourl = DOL_URL_ROOT.'/';
	}
	header('Location: '.$backtourl);
	exit();
}

//var_dump($requestedpermissionsarray);exit;


// Dolibarr storage
$storage = new DoliStorage($db, $conf, $keyforprovider);

// Instantiate the Api service using the credentials, http client and storage mechanism for the token
// ucfirst(strtolower($genericstring)) must be the name of a class into OAuth/OAuth2/Services/Xxxx
$apiService = $serviceFactory->createService(ucfirst(strtolower($genericstring)), $credentials, $storage, $requestedpermissionsarray);

/*
var_dump($genericstring.($keyforprovider ? '-'.$keyforprovider : ''));
var_dump($credentials);
var_dump($storage);
var_dump($requestedpermissionsarray);
*/

if (empty($apiService) || !$apiService instanceof OAuth\OAuth2\Service\Generic) {
	print 'Error, failed to create Generic serviceFactory';
	exit;
}
if (!$apiService->getBaseApiUri()) {
	print 'Error, setup of OAuth entry is not complete (missing base url)';
	exit;
}

// access type needed to have oauth provider refreshing token
// also note that a refresh token is sent only after a prompt
if (method_exists($apiService, 'setAccessType')) {
	$apiService->setAccessType('offline');		// Most generic OAUTH provider does not provide AccessType online/offline. They are mostly offline.  // @phan-suppress-current-line PhanUndeclaredMethod
}

if (!getDolGlobalString($keyforparamid)) {
	accessforbidden('Setup of service '.$keyforparamid.' is not complete. Customer ID is missing');
}
if (!getDolGlobalString($keyforparamsecret)) {
	accessforbidden('Setup of service '.$keyforparamid.' is not complete. Secret key is missing');
}


/*
 * Actions
 */

if ($action == 'delete') {
	$storage->clearToken($genericstring);

	setEventMessages($langs->trans('TokenDeleted'), null, 'mesgs');

	if (empty($backtourl)) {
		$backtourl = DOL_URL_ROOT.'/';
	}

	header('Location: '.$backtourl);
	exit();
}

if (!GETPOST('code') && !GETPOST('error')) {
	dol_syslog("Page is called without the 'code' parameter defined");

	// If we enter this page without 'code' parameter, it means we click on the link from login page ($forlogin is set) or from setup page and we want to get the redirect
	// to the OAuth provider login page.
	$_SESSION["backtourlsavedbeforeoauthjump"] = $backtourl;
	$_SESSION["oauthkeyforproviderbeforeoauthjump"] = $keyforprovider;
	$_SESSION['oauthstateanticsrf'] = $state;

	// Save more data into session
	// No need to save more data in sessions. We have several info into $_SESSION['datafromloginform'], saved when form is posted with a click
	// on "Login with Generic" with param actionlogin=login and beforeoauthloginredirect=generic, by the functions_genericoauth.php.

	// Set approval_prompt. Note: A refresh token will be provided only if prompt is done.
	if ($forlogin) {
		$approval_prompt = getDolGlobalString('OAUTH_'.$genericstring.'_FORCE_PROMPT_ON_LOGIN', 'auto');	// Can be 'force'
		if (method_exists($apiService, 'setApprouvalPrompt')) {
			$apiService->setApprouvalPrompt($approval_prompt);  // @phan-suppress-current-line PhanUndeclaredMethod
		}
	} else {
		if (method_exists($apiService, 'setApprouvalPrompt')) {
			$apiService->setApprouvalPrompt('force');  // @phan-suppress-current-line PhanUndeclaredMethod
		}
	}

	// This may create record into oauth_state before the header redirect.
	// Creation of record with state, create record or just update column state of table llx_oauth_token (and create/update entry in llx_oauth_state) depending on the Provider used (see its constructor).
	if ($state) {
		$url = $apiService->getAuthorizationUri(array('client_id' => getDolGlobalString($keyforparamid), 'response_type' => 'code', 'state' => $state));
	} else {
		$url = $apiService->getAuthorizationUri(array('client_id' => getDolGlobalString($keyforparamid), 'response_type' => 'code')); // Parameter state will be randomly generated
	}
	// The redirect_uri is included into this $url

	// Add scopes
	$url .= '&scope='.str_replace(',', '+', $statewithscopeonly);

	// Add more param
	$url .= '&nonce='.bin2hex(random_bytes(64 / 8));

	if ($forlogin) {
		// TODO Add param hd. What is it for ?
		//$url .= 'hd=xxx';

		if (GETPOST('username')) {
			$url .= '&login_hint='.urlencode(GETPOST('username'));
		}

		// Check that the redirect_uri that will be used is same than url of current domain

		// Define $urlwithroot
		global $dolibarr_main_url_root;
		$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
		$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
		//$urlwithroot = DOL_MAIN_URL_ROOT;				// This is to use same domain name than current

		include DOL_DOCUMENT_ROOT.'/core/lib/geturl.lib.php';
		$currentrooturl = getRootURLFromURL(DOL_MAIN_URL_ROOT);
		$externalrooturl = getRootURLFromURL($urlwithroot);

		if ($currentrooturl != $externalrooturl) {
			$langs->load("errors");
			setEventMessages($langs->trans("ErrorTheUrlOfYourDolInstanceDoesNotMatchURLIntoOAuthSetup", $currentrooturl, $externalrooturl), null, 'errors');
			$url = DOL_URL_ROOT;
		}
	}

	//var_dump($url);exit;

	// we go on oauth provider authorization page, we will then go back on this page but into the other branch of the if (!GETPOST('code'))
	header('Location: '.$url);
	exit();
} else {
	// We are coming from the return of an OAuth2 provider page.
	dol_syslog("We are coming from the oauth provider page keyforprovider=".$keyforprovider." code=".dol_trunc(GETPOST('code'), 5));

	// We must validate that the $state is the same than the one into $_SESSION['oauthstateanticsrf'], return error if not.
	if (isset($_SESSION['oauthstateanticsrf']) && $state != $_SESSION['oauthstateanticsrf']) {
		//var_dump($_SESSION['oauthstateanticsrf']);exit;
		print 'Value for state='.dol_escape_htmltag($state).' differs from value in $_SESSION["oauthstateanticsrf"]. Code is refused.';
		unset($_SESSION['oauthstateanticsrf']);
	} else {
		// This was a callback request from service, get the token
		try {
			//var_dump($apiService);      // OAuth\OAuth2\Service\Generic
			//dol_syslog("_GET=".var_export($_GET, true));

			$errorincheck = 0;

			$db->begin();

			// This requests the token from the received OAuth code (call of the endpoint)
			// Result is stored into object managed by class DoliStorage into includes/OAuth/Common/Storage/DoliStorage.php and into database table llx_oauth_token
			$token = $apiService->requestAccessToken(GETPOST('code'), $state);

			'@phan-var-force OAuth\Common\Token\AbstractToken $token';

			// The refresh token is inside the object token if the prompt was forced only.
			//$refreshtoken = $token->getRefreshToken();
			//var_dump($refreshtoken);

			// Note: The extraparams has the 'id_token' than contains a lot of information about the user.
			$extraparams = $token->getExtraParams();

			$username = '';
			$useremail = '';

			// Extract the middle part, base64 decode, then json_decode it
			/*
			$jwt = explode('.', $extraparams['id_token']);

			if (!empty($jwt[1])) {
				$userinfo = json_decode(base64_decode($jwt[1]), true);

				dol_syslog("userinfo=".var_export($userinfo, true));

				$useremail = $userinfo['email'];

				// We should make the steps of validation of id_token

				// Verify that the state is the one expected
				// TODO

				// Verify that the ID token is properly signed by the issuer.
				// TODO

				// Verify that the value of the iss claim in the ID token is equal to https://accounts.google.com or accounts.google.com.
				if ($userinfo['iss'] != 'accounts.google.com' && $userinfo['iss'] != 'https://accounts.google.com') {
					setEventMessages($langs->trans('Bad value for returned userinfo[iss]'), null, 'errors');
					$errorincheck++;
				}

				// Verify that the value of the aud claim in the ID token is equal to your app's client ID.
				if ($userinfo['aud'] != getDolGlobalString($keyforparamid)) {
					setEventMessages($langs->trans('Bad value for returned userinfo[aud]'), null, 'errors');
					$errorincheck++;
				}

				// Verify that the expiry time (exp claim) of the ID token has not passed.
				if ($userinfo['exp'] <= dol_now()) {
					setEventMessages($langs->trans('Bad value for returned userinfo[exp]. Token expired.'), null, 'errors');
					$errorincheck++;
				}

				// If you specified a hd parameter value in the request, verify that the ID token has a hd claim that matches an accepted G Suite hosted domain.
				// $userinfo['hd'] is the domain name of Gmail account.
				// TODO
			}
			*/

			if (!$errorincheck) {
				// If call back to url for a OAUTH2 login
				if ($forlogin) {
					dol_syslog("we received the login/email to log to, it is ".$useremail);

					$tmparray = (empty($_SESSION['datafromloginform']) ? array() : $_SESSION['datafromloginform']);
					$entitytosearchuser = (isset($tmparray['entity']) ? $tmparray['entity'] : -1);

					// Delete the old token
					$storage->clearToken($genericstring);	// Delete the token called ("Generic-".$storage->keyforprovider)

					$tmpuser = new User($db);
					$res = $tmpuser->fetch(0, '', '', 0, $entitytosearchuser, $useremail, 0, 1);	// Load user. Can load with email_oauth2.

					if ($res > 0) {
						$username = $tmpuser->login;

						$_SESSION['genericoauth_receivedlogin'] = dol_hash($conf->file->instance_unique_id.$username, '0');
						dol_syslog('We set $_SESSION[\'genericoauth_receivedlogin\']='.$_SESSION['genericoauth_receivedlogin']);
					} else {
						$errormessage = "Failed to login using '.$genericstring.'. User with the Email '".$useremail."' was not found";
						if ($entitytosearchuser > 0) {
							$errormessage .= ' ('.$langs->trans("Entity").' '.$entitytosearchuser.')';
						}
						$_SESSION["dol_loginmesg"] = $errormessage;
						$errorincheck++;

						dol_syslog($errormessage);
					}
				}
			} else {
				// If call back to url for a OAUTH2 login
				if ($forlogin) {
					$_SESSION["dol_loginmesg"] = "Failed to login using '.$genericstring.'. OAuth callback URL retrieves a token with non valid data";
					$errorincheck++;
				}
			}

			if (!$errorincheck) {
				$db->commit();
			} else {
				$db->rollback();
			}

			$backtourl = $_SESSION["backtourlsavedbeforeoauthjump"];
			unset($_SESSION["backtourlsavedbeforeoauthjump"]);

			if (empty($backtourl)) {
				$backtourl = DOL_URL_ROOT.'/';
			}

			// If call back to this url was for a OAUTH2 login
			if ($forlogin) {
				// _SESSION['genericoauth_receivedlogin'] has been set to the key to validate the next test by function_genericoauth(), so we can make the redirect
				$backtourl .= '?actionlogin=login&afteroauthloginreturn=1&mainmenu=home'.($username ? '&username='.urlencode($username) : '').'&token='.newToken();
				if (!empty($tmparray['entity'])) {
					$backtourl .= '&entity='.$tmparray['entity'];
				}
			}

			dol_syslog("Redirect now on backtourl=".$backtourl);

			header('Location: '.$backtourl);
			exit();
		} catch (Exception $e) {
			print $e->getMessage();
		}
	}
}


/*
 * View
 */

// No view at all, just actions

$db->close();
