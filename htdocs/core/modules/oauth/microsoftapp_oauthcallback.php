<?php
/* Copyright (C) 2026		Florian Hoedl		<trainingsmagnet@gmail.com>
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
 *      \file       htdocs/core/modules/oauth/microsoftapp_oauthcallback.php
 *      \ingroup    oauth
 *      \brief      Page to get an app-only token for Microsoft Exchange Online (client credentials grant flow)
 *
 *      Unlike the other oauth callback pages, there is no redirect to the OAuth provider here:
 *      the token is requested server to server with the application credentials (application
 *      permissions with admin consent granted on https://portal.azure.com/), then stored.
 */

// Load Dolibarr environment
require '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/includes/OAuth/bootstrap.php';
/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var Translate $langs
 * @var User $user
 *
 * @var string $dolibarr_main_url_root
 */

use OAuth\Common\Storage\DoliStorage;
use OAuth\Common\Consumer\Credentials;

// Define $urlwithroot
$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file


$action = GETPOST('action', 'aZ09');
$backtourl = GETPOST('backtourl', 'alpha');
$keyforprovider = GETPOST('keyforprovider', 'aZ09');
$genericstring = 'MICROSOFTAPP';


/**
 * Create a new instance of the URI class with the current URI, stripping the query string
 */
$uriFactory = new \OAuth\Common\Http\Uri\UriFactory();


$currentUri = $uriFactory->createFromAbsolute($urlwithroot.'/core/modules/oauth/microsoftapp_oauthcallback.php');


/**
 * Load the credential for the service
 */

/** @var \OAuth\ServiceFactory $serviceFactory An OAuth service factory. */
$serviceFactory = new \OAuth\ServiceFactory();
$httpClient = new \OAuth\Common\Http\Client\CurlClient();

$serviceFactory->setHttpClient($httpClient);

// Setup the credentials for the requests
$keyforparamid = 'OAUTH_'.$genericstring.($keyforprovider ? '-'.$keyforprovider : '').'_ID';
$keyforparamsecret = 'OAUTH_'.$genericstring.($keyforprovider ? '-'.$keyforprovider : '').'_SECRET';
$keyforparamtenant = 'OAUTH_'.$genericstring.($keyforprovider ? '-'.$keyforprovider : '').'_TENANT';
$keyforparamscope = 'OAUTH_'.$genericstring.($keyforprovider ? '-'.$keyforprovider : '').'_SCOPE';

// Dolibarr storage
$storage = new DoliStorage($db, $conf, $keyforprovider, getDolGlobalString($keyforparamtenant));

$credentials = new Credentials(
	getDolGlobalString($keyforparamid),
	getDolGlobalString($keyforparamsecret),
	$currentUri->getAbsoluteUri()
);

$langs->load("oauth");

if (!getDolGlobalString($keyforparamid)) {
	accessforbidden('Setup of service is not complete. Customer ID is missing');
}
if (!getDolGlobalString($keyforparamsecret)) {
	accessforbidden('Setup of service is not complete. Secret key is missing');
}
if (!getDolGlobalString($keyforparamtenant)) {
	accessforbidden('Setup of service is not complete. Tenant/Annuary ID key is missing');
}

/*
 * Actions
 */

if ($action == 'delete' && (!empty($user->admin) || $user->id == GETPOSTINT('userid'))) {
	$storage->userid = GETPOSTINT('userid');
	$storage->clearToken('Microsoftapp');

	setEventMessages($langs->trans('TokenDeleted'), null, 'mesgs');

	if (empty($backtourl)) {
		$backtourl = DOL_URL_ROOT.'/';
	}

	header('Location: '.$backtourl);
	exit();
}

// Default action: request a new app-only access token, server to server, and store it.
// Only an admin can do this: the token is generated from the application credentials
// without any consent screen on the Microsoft side (admin consent was granted into Azure).
if (empty($user->admin)) {
	accessforbidden('Only an admin user can request an application token');
}

if (empty($backtourl)) {
	$backtourl = DOL_URL_ROOT.'/admin/oauthlogintokens.php';
}

// Scopes are read from the setup. If none is defined, the service fallbacks to the
// Exchange Online ".default" scope required by the client credentials grant flow.
$requestedpermissionsarray = array();
if (getDolGlobalString($keyforparamscope)) {
	$requestedpermissionsarray = explode(',', getDolGlobalString($keyforparamscope));
}

try {
	$nameofservice = ucfirst(strtolower($genericstring));
	$apiService = $serviceFactory->createService($nameofservice, $credentials, $storage, $requestedpermissionsarray);

	if (!($apiService instanceof OAuth\OAuth2\Service\Microsoftapp)) {
		print 'Error, failed to create serviceFactory';
		exit;
	}

	dol_syslog(basename(__FILE__)." Request app-only access token (client credentials grant flow) for service ".$nameofservice.($keyforprovider ? '-'.$keyforprovider : ''));

	$apiService->requestNewAccessToken();

	setEventMessages($langs->trans('NewTokenStored'), null, 'mesgs'); // Stored into object managed by class DoliStorage so into table oauth_token
} catch (Exception $e) {
	setEventMessages($e->getMessage(), null, 'errors');
	dol_syslog(basename(__FILE__)." Failed to get app-only access token: ".$e->getMessage(), LOG_ERR);
}

header('Location: '.$backtourl);
exit();

/*
 * View
 */

// No view at all, just actions
