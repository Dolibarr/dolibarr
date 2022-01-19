<?php
/*
 * Copyright (C) 2015       Frederic France      <frederic.france@free.fr>
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

// This page should make the process to login and get token as described here:
// https://developers.google.com/identity/protocols/oauth2/openid-connect#server-flow

/**
 *      \file       htdocs/core/modules/oauth/google_oauthcallback.php
 *      \ingroup    oauth
 *      \brief      Page to get oauth callback
 */

require '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/includes/OAuth/bootstrap.php';
use OAuth\Common\Storage\DoliStorage;
use OAuth\Common\Consumer\Credentials;
use OAuth\OAuth2\Service\Google;

// Define $urlwithroot
$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
//$urlwithroot=DOL_MAIN_URL_ROOT;					// This is to use same domain name than current



$action = GETPOST('action', 'aZ09');
$backtourl = GETPOST('backtourl', 'alpha');


/**
 * Create a new instance of the URI class with the current URI, stripping the query string
 */
$uriFactory = new \OAuth\Common\Http\Uri\UriFactory();
//$currentUri = $uriFactory->createFromSuperGlobalArray($_SERVER);
//$currentUri->setQuery('');
$currentUri = $uriFactory->createFromAbsolute($urlwithroot.'/core/modules/oauth/google_oauthcallback.php');


/**
 * Load the credential for the service
 */

/** @var $serviceFactory \OAuth\ServiceFactory An OAuth service factory. */
$serviceFactory = new \OAuth\ServiceFactory();
$httpClient = new \OAuth\Common\Http\Client\CurlClient();
// TODO Set options for proxy and timeout
// $params=array('CURLXXX'=>value, ...)
//$httpClient->setCurlParameters($params);
$serviceFactory->setHttpClient($httpClient);

// Dolibarr storage
$storage = new DoliStorage($db, $conf);

// Setup the credentials for the requests
$credentials = new Credentials(
	$conf->global->OAUTH_GOOGLE_ID,
	$conf->global->OAUTH_GOOGLE_SECRET,
	$currentUri->getAbsoluteUri()
);

$state = GETPOST('state');

$requestedpermissionsarray = array();
if ($state) {
	// 'state' parameter is standard to store a hash value and can be used to retrieve some parameters back
	$statewithscopeonly = preg_replace('/\-.*$/', '', $state);
	$requestedpermissionsarray = explode(',', $statewithscopeonly); // Example: 'userinfo_email,userinfo_profile,openid,email,profile,cloud_print'.
}
if ($action != 'delete' && empty($requestedpermissionsarray)) {
	print 'Error, parameter state is not defined';
	exit;
}
//var_dump($requestedpermissionsarray);exit;



// Instantiate the Api service using the credentials, http client and storage mechanism for the token
// $requestedpermissionsarray contains list of scopes.
// Conversion into URL is done by Reflection on constant with name SCOPE_scope_in_uppercase
$apiService = $serviceFactory->createService('Google', $credentials, $storage, $requestedpermissionsarray);

// access type needed to have oauth provider refreshing token
// also note that a refresh token is sent only after a prompt
$apiService->setAccessType('offline');

$apiService->setApprouvalPrompt('force');

//$apiService->setLoginHint(email); // If we know the email of Google account, we can set it to have it correctly selected on login prompt on multiaccount

$langs->load("oauth");


/*
 * Actions
 */


if ($action == 'delete') {
	$storage->clearToken('Google');

	setEventMessages($langs->trans('TokenDeleted'), null, 'mesgs');

	header('Location: '.$backtourl);
	exit();
}

if (GETPOST('code')) {     // We are coming from oauth provider page.
	dol_syslog("We are coming from the oauth provider page");

	// TODO
	// We should validate that the $sate is same than the one into $_SESSION['oauthstateanticsrf'], return error if not.


	// This was a callback request from service, get the token
	try {
		//var_dump($_GET['code']);
		//var_dump($state);
		//var_dump($apiService);      // OAuth\OAuth2\Service\Google

		// This request the token
		// Result is stored into object managed by class DoliStorage into includes/OAuth/Common/Storage/DoliStorage.php, so into table llx_oauth_token
		$token = $apiService->requestAccessToken(GETPOST('code'), $state);

		// Note: The token contains a lot of information about the user.

		setEventMessages($langs->trans('NewTokenStored'), null, 'mesgs');

		$backtourl = $_SESSION["backtourlsavedbeforeoauthjump"];
		unset($_SESSION["backtourlsavedbeforeoauthjump"]);

		header('Location: '.$backtourl);
		exit();
	} catch (Exception $e) {
		print $e->getMessage();
	}
} else {
	// If we enter this page without 'code' parameter, we arrive here. this is the case when we want to get the redirect
	// to the OAuth provider login page
	$_SESSION["backtourlsavedbeforeoauthjump"] = $backtourl;

	// This may create record into oauth_state before the header redirect.
	// Creation of record with state in this tables depend on the Provider used (see its constructor).
	if (GETPOST('state')) {
		$url = $apiService->getAuthorizationUri(array('state'=>GETPOST('state')));
	} else {
		$url = $apiService->getAuthorizationUri(); // Parameter state will be randomly generated
	}

	// we go on oauth provider authorization page
	header('Location: '.$url);
	exit();
}


/*
 * View
 */

// No view at all, just actions, so we never reach this line, except on error.

$db->close();
