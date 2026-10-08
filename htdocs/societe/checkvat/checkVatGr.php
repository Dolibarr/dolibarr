<?php
/* Copyright (C) 2016       Spiros Ioannou
 * Copyright (C) 2017       Marios Kaintatzis
 * Copyright (C) 2023       Nick Fragoulis
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
 *  \file       htdocs/societe/checkvat/checkVatGr.php
 *  \ingroup    societe
 *  \brief      Request VAT details from the Greek Ministry of Finance GSIS SOAP web service
 */

require "../../main.inc.php";

$username = getDolGlobalString('AADE_WEBSERVICE_USER'); // Get username from request
$password = getDolGlobalString('AADE_WEBSERVICE_KEY'); // Get password from request
$myafm = preg_replace('/\D/', '', getDolGlobalString('MAIN_INFO_TVAINTRA')); // Get Vat from request after removing non-digit characters
$afm = GETPOST('afm'); // Get client Vat from request

// Make call to check VAT for Greek client
$result = checkVATGR($username, $password, $myafm, $afm);

top_httphead('application/json');
echo json_encode($result); // Encode the result as JSON and output

/**
* Request VAT details
* @param 	string 	$username 			Company AADE username
* @param 	string 	$password 			Company AADE password
* @param 	string 	$AFMcalledby 		Company vat number
* @param 	string 	$AFMcalledfor 		Client vat number
* @return   string
*/
function checkVATGR($username, $password, $AFMcalledby, $AFMcalledfor)
{
	/*
	$WS_DOL_URL_WSDL = "https://www1.gsis.gr/webtax2/wsgsis/RgWsPublic/RgWsPublicPort?WSDL";

	require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
	$params = getSoapParams();
	//ini_set('default_socket_timeout', $params['response_timeout']);
	$soapclient = new nusoap_client($WS_DOL_URL_WSDL, true, $params['proxy_host'], $params['proxy_port'], $params['proxy_login'], $params['proxy_password'], $params['connection_timeout'], $params['response_timeout']);

	$soapclient->soap_defencoding = 'utf-8';
	$soapclient->xml_encoding = 'utf-8';
	$soapclient->decode_utf8 = false;

	// Check for an error
	$err = $soapclient->getError();
	if ($err) {
		dol_syslog("Constructor error ".$WS_DOL_URL, LOG_ERR);
	}

	...


	*/

	// TODO Replace this with code using nusoap_client(), see previous commented code, and remove phpstan tag
	// @phpstan-ignore-next-line
	$client = new SoapClient("https://www1.gsis.gr/webtax2/wsgsis/RgWsPublic/RgWsPublicPort?WSDL", array('trace' => true));
	$authHeader = new stdClass();
	$authHeader->UsernameToken = new stdClass();
	$authHeader->UsernameToken->Username = "$username";
	$authHeader->UsernameToken->Password = "$password";
	$Headers = array();
	$Headers[] = new SoapHeader('https://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd', 'Security', $authHeader, true);
	$client->__setSoapHeaders($Headers);
	$result = $client->rgWsPublicAfmMethod(
		array(
			'afmCalledBy' => "$AFMcalledby",
			'afmCalledFor' => "$AFMcalledfor",
		)
	);

	return $result;
}
