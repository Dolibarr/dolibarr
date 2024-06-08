<?php
/* Copyright (C) 2022       Laurent Destailleur  <eldy@users.sourceforge.net>
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

/**
 *      \file       htdocs/core/modules/oauth/microsoft_oauthcallback.php
 *      \ingroup    oauth
 *      \brief      Page to get oauth callback
 */

// Load Dolibarr environment
require '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/includes/OAuth/bootstrap.php';
use OAuth\Common\Storage\DoliStorage;
use OAuth\Common\Consumer\Credentials;

// Define $urlwithroot
$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
//$urlwithroot=DOL_MAIN_URL_ROOT;					// This is to use same domain name than current


$action = GETPOST('action', 'aZ09');
$backtourl = GETPOST('backtourl', 'alpha');
$keyforprovider = GETPOST('keyforprovider', 'aZ09');
if (empty($keyforprovider) && !empty($_SESSION["oauthkeyforproviderbeforeoauthjump"]) && (GETPOST('code') || $action == 'delete')) {
	$keyforprovider = $_SESSION["oauthkeyforproviderbeforeoauthjump"];
}
$genericstring = 'MICROSOFT2';


/**
 * Create a new instance of the URI class with the current URI, stripping the query string
 */
$uriFactory = new \OAuth\Common\Http\Uri\UriFactory();
//$currentUri = $uriFactory->createFromSuperGlobalArray($_SERVER);
//$currentUri->setQuery('');
$currentUri = $uriFactory->createFromAbsolute($urlwithroot.'/core/modules/oauth/microsoft_oauthcallback.php');


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

// Dolibarr storage
$storage = new DoliStorage($db, $conf, $keyforprovider);

// Setup the credentials for the requests
$keyforparamid = 'OAUTH_'.$genericstring.($keyforprovider ? '-'.$keyforprovider : '').'_ID';
$keyforparamsecret = 'OAUTH_'.$genericstring.($keyforprovider ? '-'.$keyforprovider : '').'_SECRET';
$keyforparamtenant = 'OAUTH_'.$genericstring.($keyforprovider ? '-'.$keyforprovider : '').'_TENANT';
$credentials = new Credentials(
	getDolGlobalString($keyforparamid),
	getDolGlobalString($keyforparamsecret),
	$currentUri->getAbsoluteUri()
);

$state = GETPOST('state');

$requestedpermissionsarray = array();
if ($state) {
	$requestedpermissionsarray = explode(',', $state); // Example: 'user'. 'state' parameter is standard to retrieve some parameters back
}
if ($action != 'delete' && empty($requestedpermissionsarray)) {
	print 'Error, parameter state is not defined';
	exit;
}
//var_dump($requestedpermissionsarray);exit;

// Instantiate the Api service using the credentials, http client and storage mechanism for the token
// ucfirst(strtolower($genericstring)) must be the name of a class into OAuth/OAuth2/Services/Xxxx
// $requestedpermissionsarray contains list of scopes.
// Conversion into URL is done by Reflection on constant with name SCOPE_scope_in_uppercase
try {
	$apiService = $serviceFactory->createService(ucfirst(strtolower($genericstring)), $credentials, $storage, $requestedpermissionsarray);
} catch (Exception $e) {
	print $e->getMessage();
	exit;
}
/*
var_dump($genericstring.($keyforprovider ? '-'.$keyforprovider : ''));
var_dump($credentials);
var_dump($storage);
var_dump($requestedpermissionsarray);
*/

if (empty($apiService)) {
	print 'Error, failed to create serviceFactory';
	exit;
}

// access type needed to have oauth provider refreshing token
//$apiService->setAccessType('offline');

$langs->load("oauth");

if (!getDolGlobalString($keyforparamid)) {
	accessforbidden('Setup of service is not complete. Customer ID is missing');
}
if (!getDolGlobalString($keyforparamsecret)) {
	accessforbidden('Setup of service is not complete. Secret key is missing');
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

//dol_syslog("GET=".join(',', $_GET));


if (GETPOST('code') || GETPOST('error')) {     // We are coming from oauth provider page
	// We should have
	//$_GET=array('code' => string 'aaaaaaaaaaaaaa' (length=20), 'state' => string 'user,public_repo' (length=16))

	dol_syslog("We are coming from the oauth provider page code=".dol_trunc(GETPOST('code'), 5)." error=".GETPOST('error'));

	// This was a callback request from service, get the token
	try {
		//var_dump($state);
		//var_dump($apiService);      // OAuth\OAuth2\Service\Microsoft

		if (GETPOST('error')) {
			setEventMessages(GETPOST('error').' '.GETPOST('error_description'), null, 'errors');
		} else {
			//print GETPOST('code');exit;

			//$token = $apiService->requestAccessToken(GETPOST('code'), $state);
			$token = $apiService->requestAccessToken(GETPOST('code'));
			// Microsoft is a service that does not need state to be stored as second parameter of requestAccessToken

			//print $token->getAccessToken().'<br><br>';
			//print $token->getExtraParams()['id_token'].'<br>';
			//print $token->getRefreshToken().'<br>';exit;

			setEventMessages($langs->trans('NewTokenStored'), null, 'mesgs'); // Stored into object managed by class DoliStorage so into table oauth_token
		}

		$backtourl = $_SESSION["backtourlsavedbeforeoauthjump"];
		unset($_SESSION["backtourlsavedbeforeoauthjump"]);

		header('Location: '.$backtourl);
		exit();
	} catch (Exception $e) {
		print $e->getMessage();
	}
} else {
	// If we enter this page without 'code' parameter, we arrive here. This is the case when we want to get the redirect
	// to the OAuth provider login page.
	$_SESSION["backtourlsavedbeforeoauthjump"] = $backtourl;
	$_SESSION["oauthkeyforproviderbeforeoauthjump"] = $keyforprovider;
	$_SESSION['oauthstateanticsrf'] = $state;

	//if (!preg_match('/^forlogin/', $state)) {
	//	$apiService->setApprouvalPrompt('auto');
	//}

	// This may create record into oauth_state before the header redirect.
	// Creation of record with state in this tables depend on the Provider used (see its constructor).
	if ($state) {
		$url = $apiService->getAuthorizationUri(array('state' => $state));
	} else {
		$url = $apiService->getAuthorizationUri(); // Parameter state will be randomly generated
	}

	// Show url to get authorization
	//var_dump((string) $url);exit;
	dol_syslog("Redirect to url=".$url);

	// we go on oauth provider authorization page
	header('Location: '.$url);
	exit();
}


/*
 * View
 */

// No view at all, just actions

$db->close();
