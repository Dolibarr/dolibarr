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

/**
 *      \file       htdocs/core/modules/oauth/github_oauthcallback.php
 *      \ingroup    oauth
 *      \brief      Page to get oauth callback
 */

require '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/includes/OAuth/bootstrap.php';
use OAuth\Common\Storage\DoliStorage;
use OAuth\Common\Consumer\Credentials;
use OAuth\OAuth2\Service\GitHub;

// Define $urlwithroot
$urlwithouturlroot=preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
$urlwithroot=$urlwithouturlroot.DOL_URL_ROOT;		// This is to use external domain name found into config file
//$urlwithroot=DOL_MAIN_URL_ROOT;					// This is to use same domain name than current



$action = GETPOST('action', 'alpha');
$backtourl = GETPOST('backtourl', 'alpha');


/**
 * Create a new instance of the URI class with the current URI, stripping the query string
 */
$uriFactory = new \OAuth\Common\Http\Uri\UriFactory();
//$currentUri = $uriFactory->createFromSuperGlobalArray($_SERVER);
//$currentUri->setQuery('');
$currentUri = $uriFactory->createFromAbsolute($urlwithroot.'/core/modules/oauth/github_oauthcallback.php');


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
    $conf->global->OAUTH_GITHUB_ID,
    $conf->global->OAUTH_GITHUB_SECRET,
    $currentUri->getAbsoluteUri()
);

$requestedpermissionsarray=array();
if (GETPOST('state')) $requestedpermissionsarray=explode(',', GETPOST('state'));       // Example: 'userinfo_email,userinfo_profile,cloud_print'. 'state' parameter is standard to retrieve some parameters back
if ($action != 'delete' && empty($requestedpermissionsarray))
{
    print 'Error, parameter state is not defined';
    exit;
}
//var_dump($requestedpermissionsarray);exit;

// Instantiate the Api service using the credentials, http client and storage mechanism for the token
/** @var $apiService Service */
$apiService = $serviceFactory->createService('GitHub', $credentials, $storage, $requestedpermissionsarray);

// access type needed to have oauth provider refreshing token
//$apiService->setAccessType('offline');

$langs->load("oauth");


/*
 * Actions
 */


if ($action == 'delete')
{
    $storage->clearToken('GitHub');

    setEventMessages($langs->trans('TokenDeleted'), null, 'mesgs');

    header('Location: ' . $backtourl);
    exit();
}

if (! empty($_GET['code']))     // We are coming from oauth provider page
{
	// We should have
	//$_GET=array('code' => string 'aaaaaaaaaaaaaa' (length=20), 'state' => string 'user,public_repo' (length=16))

	dol_syslog("We are coming from the oauth provider page");
    //llxHeader('',$langs->trans("OAuthSetup"));

    //$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
    //print load_fiche_titre($langs->trans("OAuthSetup"),$linkback,'title_setup');

    //dol_fiche_head();
    // retrieve the CSRF state parameter
    $state = isset($_GET['state']) ? $_GET['state'] : null;
    //print '<table>';

    // This was a callback request from service, get the token
    try {
        //var_dump($_GET['code']);
        //var_dump($state);
        //var_dump($apiService);      // OAuth\OAuth2\Service\GitHub

        //$token = $apiService->requestAccessToken($_GET['code'], $state);
        $token = $apiService->requestAccessToken($_GET['code']);
        // Github is a service that does not need state to be stored.
        // Into constructor of GitHub, the call
        // parent::__construct($credentials, $httpClient, $storage, $scopes, $baseApiUri)
        // has not the ending parameter to true like the Google class constructor.

        setEventMessages($langs->trans('NewTokenStored'), null, 'mesgs');   // Stored into object managed by class DoliStorage so into table oauth_token

        $backtourl = $_SESSION["backtourlsavedbeforeoauthjump"];
        unset($_SESSION["backtourlsavedbeforeoauthjump"]);

        header('Location: ' . $backtourl);
        exit();
    } catch (Exception $e) {
        print $e->getMessage();
    }
}
else // If entry on page with no parameter, we arrive here
{
    $_SESSION["backtourlsavedbeforeoauthjump"]=$backtourl;

    // This may create record into oauth_state before the header redirect.
    // Creation of record with state in this tables depend on the Provider used (see its constructor).
    if (GETPOST('state'))
    {
        $url = $apiService->getAuthorizationUri(array('state'=>GETPOST('state')));
    }
    else
    {
        $url = $apiService->getAuthorizationUri();      // Parameter state will be randomly generated
    }

    // we go on oauth provider authorization page
    header('Location: ' . $url);
    exit();
}


/*
 * View
 */

// No view at all, just actions

$db->close();
