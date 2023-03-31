<?php
/* Copyright (C) 2006-2016 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *		\file       htdocs/societe/checkvat/checkVatPopup.php
 *		\ingroup    societe
 *		\brief      Popup screen to validate VAT
 */

if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', '1');		// Do not roll the Anti CSRF token (used if MAIN_SECURITY_CSRF_WITH_TOKEN is on)

require "../../main.inc.php";
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once NUSOAP_PATH.'/nusoap.php';

$langs->load("companies");

//http://ec.europa.eu/taxation_customs/vies/checkVatService.wsdl
$WS_DOL_URL = 'https://ec.europa.eu/taxation_customs/vies/services/checkVatService';
//$WS_DOL_URL_WSDL=$WS_DOL_URL.'?wsdl';
$WS_DOL_URL_WSDL = 'https://ec.europa.eu/taxation_customs/vies/checkVatService.wsdl';
$WS_METHOD = 'checkVat';


$conf->dol_hide_topmenu = 1;
$conf->dol_hide_leftmenu = 1;

llxHeader('', $langs->trans("VATIntraCheckableOnEUSite"));

print '<div class="vatcheckarea margintoponly marginbottomonly">';

print load_fiche_titre($langs->trans("VATIntraCheckableOnEUSite"), '', 'title_setup');

$vatNumber = GETPOST("vatNumber", 'alpha');

if (!$vatNumber) {
	print '<br>';
	print '<span class="error">'.$langs->transnoentities("ErrorFieldRequired", $langs->trans("VATIntraShort")).'</span><br>';
} else {
	$vatNumber = preg_replace('/\^\w/', '', $vatNumber);
	$vatNumber = str_replace(array(' ', '.'), '', $vatNumber);
	$countryCode = substr($vatNumber, 0, 2);
	$vatNumber = substr($vatNumber, 2);

	print '<b>'.$langs->trans("Country").'</b>: '.$countryCode.'<br>';
	print '<b>'.$langs->trans("VATIntraShort").'</b>: '.$vatNumber.'<br>';
	print '<br>';

	// Set the parameters to send to the WebService
	$parameters = array("countryCode" => $countryCode,
						"vatNumber" => $vatNumber);

	// Set the WebService URL
	dol_syslog("Create nusoap_client for URL=".$WS_DOL_URL." WSDL=".$WS_DOL_URL_WSDL);
	require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
	$params = getSoapParams();
	//ini_set('default_socket_timeout', $params['response_timeout']);
	//$soapclient = new SoapClient($WS_DOL_URL_WSDL,$params);
	$soapclient = new nusoap_client($WS_DOL_URL_WSDL, true, $params['proxy_host'], $params['proxy_port'], $params['proxy_login'], $params['proxy_password'], $params['connection_timeout'], $params['response_timeout']);
	$soapclient->soap_defencoding = 'utf-8';
	$soapclient->xml_encoding = 'utf-8';
	$soapclient->decode_utf8 = false;

	// Check for an error
	$err = $soapclient->getError();
	if ($err) {
		dol_syslog("Constructor error ".$WS_DOL_URL, LOG_ERR);
	}

	// Call the WebService and store its result in $result.
	dol_syslog("Call method ".$WS_METHOD);
	$result = $soapclient->call($WS_METHOD, $parameters);

	//var_dump($parameters);
	//var_dump($soapclient);
	//print "x".is_array($result)."i";
	//var_dump($result);
	//print $soapclient->request.'<br>';
	//print $soapclient->response.'<br>';

	$messagetoshow = '';
	print '<b>'.$langs->trans("Response").'</b>:<br>';

	// Service indisponible
	if (!is_array($result) || preg_match('/SERVICE_UNAVAILABLE/i', $result['faultstring'])) {
		print '<span class="error">'.$langs->trans("ErrorServiceUnavailableTryLater").'</span><br>';
		$messagetoshow = $soapclient->response;
	} elseif (preg_match('/TIMEOUT/i', $result['faultstring'])) {
		print '<span class="error">'.$langs->trans("ErrorServiceUnavailableTryLater").'</span><br>';
		$messagetoshow = $soapclient->response;
	} elseif (preg_match('/SERVER_BUSY/i', $result['faultstring'])) {
		print '<span class="error">'.$langs->trans("ErrorServiceUnavailableTryLater").'</span><br>';
		$messagetoshow = $soapclient->response;
	} elseif ($result['faultstring']) {
		print '<span class="error">'.$langs->trans("Error").'</span><br>';
		$messagetoshow = $result['faultstring'];
	} elseif (preg_match('/INVALID_INPUT/i', $result['faultstring'])
	|| ($result['requestDate'] && !$result['valid'])) {
		// Syntaxe ko
		if ($result['requestDate']) {
			print $langs->trans("Date").': '.$result['requestDate'].'<br>';
		}
		print $langs->trans("VATIntraSyntaxIsValid").': <span class="error">'.$langs->trans("No").'</span> (Might be a non europeen VAT)<br>';
		print $langs->trans("ValueIsValid").': <span class="error">'.$langs->trans("No").'</span> (Might be a non europeen VAT)<br>';
		//$messagetoshow=$soapclient->response;
	} else {
		// Syntaxe ok
		if ($result['requestDate']) {
			print $langs->trans("Date").': '.$result['requestDate'].'<br>';
		}
		print $langs->trans("VATIntraSyntaxIsValid").': <span class="ok">'.$langs->trans("Yes").'</span><br>';
		print $langs->trans("ValueIsValid").': ';
		if (preg_match('/MS_UNAVAILABLE/i', $result['faultstring'])) {
			print '<span class="error">'.$langs->trans("ErrorVATCheckMS_UNAVAILABLE", $countryCode).'</span><br>';
		} else {
			if (!empty($result['valid']) && ($result['valid'] == 1 || $result['valid'] == 'true')) {
				print '<span	 class="ok">'.$langs->trans("Yes").'</span>';
				print '<br>';
				print $langs->trans("Name").': '.$result['name'].'<br>';
				print $langs->trans("Address").': '.$result['address'].'<br>';
			} else {
				print '<span	 class="error">'.$langs->trans("No").'</span>';
				print '<br>'."\n";
			}
		}
	}

	// Show log data into page
	// print "\n";
	// print '<!-- ';
	// var_dump($result);
	// print '-->';
}

print '<br>';
print $langs->trans("VATIntraManualCheck", $langs->trans("VATIntraCheckURL"), $langs->transnoentitiesnoconv("VATIntraCheckURL")).'<br>';
print '<br>';
print '<div class="center"><input type="button" class="button" value="'.$langs->trans("CloseWindow").'" onclick="javascript: window.close()"></div>';

if ($messagetoshow) {
	print '<br><br>';
	print "\n".'Error returned:<br>';
	print nl2br($messagetoshow);
}

print '</div>';

// End of page
llxFooter();
$db->close();
