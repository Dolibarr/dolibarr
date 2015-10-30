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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *      \file       htdocs/core/modules/oauth/getoauthcallback.php
 *      \ingroup    oauth
 *      \brief      Page to get oauth callback
 */

require '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/includes/OAuth/bootstrap.php';
use OAuth\Common\Storage\Session;
use OAuth\Common\Storage\DoliStorage;
use OAuth\Common\Consumer\Credentials;
use OAuth\Common\Token\TokenInterface;
use OAuth\OAuth2\Service\Google;

/**
 * Create a new instance of the URI class with the current URI, stripping the query string
 */
$uriFactory = new \OAuth\Common\Http\Uri\UriFactory();
$currentUri = $uriFactory->createFromSuperGlobalArray($_SERVER);
$currentUri->setQuery('');

/**
 * Load the credential for the service
 */

/** @var $serviceFactory \OAuth\ServiceFactory An OAuth service factory. */
$serviceFactory = new \OAuth\ServiceFactory();
// Dolibarr storage
$storage = new DoliStorage($db, $conf);
// Setup the credentials for the requests
$credentials = new Credentials(
    $conf->global->OAUTH_GOOGLE_ID,
    $conf->global->OAUTH_GOOGLE_SECRET,
    $currentUri->getAbsoluteUri()
);

// Instantiate the Api service using the credentials, http client and storage mechanism for the token
/** @var $apiService Service */
// TODO remove hardcoded array
$apiService = $serviceFactory->createService('Google', $credentials, $storage, array('userinfo_email', 'userinfo_profile', 'cloud_print'));

// access type needed for google refresh token
$apiService->setAccessType('offline');
//print '<pre>'.print_r($apiService,true).'</pre>';
//print 'Has access Token: '.($storage->hasAccessToken('Google')?'Yes':'No').'</ br>';
//print 'Has Author State: '.($storage->hasAuthorizationState('Google')?'Yes':'No').'</ br>';
//print 'Authorization State: '.$storage->retrieveAuthorizationState('Google').'</ br>';
//print '<td><pre>'.print_r($token,true).'</pre></td>';
if (! empty($_GET['code'])) {
    llxHeader('',$langs->trans("OAuthSetup"));

    $linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
    print load_fiche_titre($langs->trans("OAuthSetup"),$linkback,'title_setup');
    // retrieve the CSRF state parameter
    $state = isset($_GET['state']) ? $_GET['state'] : null;
    try {
        $token = $storage->retrieveAccessToken('Google');
    } catch (Exception $e) {
        print $e->getMessage();
    }
    //print '<pre>'.print_r($token->getRefreshToken(),true).'</pre>';
    //$refreshtoken = $token->getRefreshToken();
    // This was a callback request from service, get the token
    $apiService->requestAccessToken($_GET['code'], $state);
    //print '<pre>'.print_r($apiService,true).'</pre>';

    try {
        $token = $storage->retrieveAccessToken('Google');
    } catch (Exception $e) {
        print $e->getMessage();
    }
    $newrefreshtoken = $token->getRefreshToken();
    if (empty($newrefreshtoken) && ! empty($refreshtoken)) {
        $token->setRefreshToken($refreshtoken);
        $storage->storeAccessToken('Google', $token);
    }
    print '<td><pre>'.print_r($token,true).'</pre></td>';
    //$apiService->refreshAccessToken($token);
    //print '<pre>'.print_r($apiService,true).'</pre>';
    //$token = $storage->retrieveAccessToken('Google');
    //print '<td><pre>'.print_r($token,true).'</pre></td>';

} else {
    $url = $apiService->getAuthorizationUri();
    // we go on google authorization page
    header('Location: ' . $url);
    exit();
}

llxFooter();

$db->close();
