<?php
/* Copyright (C) 2006-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *       \file       htdocs/webservices/client.php
 *       \brief      Demo page to make a client call to Dolibarr WebServices "server_invoice"
 *       \version    $Id$
 */

// This is to make Dolibarr working with Plesk
set_include_path($_SERVER['DOCUMENT_ROOT'].'/htdocs');

require_once("../master.inc.php");
require_once(NUSOAP_PATH.'/nusoap.php');		// Include SOAP

$WS_DOL_URL = $dolibarr_main_url_root.'/webservices/server_invoice.php';
$WS_METHOD1  = 'getInvoice';
$WS_METHOD2  = 'getInvoicesForThirdParty';


// Set the WebService URL
dol_syslog("Create soapclient_nusoap for URL=".$WS_DOL_URL);
$soapclient1 = new soapclient_nusoap($WS_DOL_URL);
if ($soapclient1)
{
	$soapclient1->soap_defencoding='UTF-8';
}
$soapclient2 = new soapclient_nusoap($WS_DOL_URL);
if ($soapclient2)
{
    $soapclient2->soap_defencoding='UTF-8';
}

// Call the WebService method and store its result in $result.
$authentication=array(
    'dolibarrkey'=>$conf->global->WEBSERVICES_KEY,
    'sourceapplication'=>'DEMO',
    'login'=>'admin',
    'password'=>'changeme',
    'entity'=>'');

$parameters = array('authentication'=>$authentication,'id'=>1,'ref'=>'');
dol_syslog("Call method ".$WS_METHOD1);
$result1 = $soapclient1->call($WS_METHOD1,$parameters);
if (! $result1)
{
	print $soapclient1->error_str;
	exit;
}

$parameters = array('authentication'=>$authentication,'idthirdparty'=>'1');
dol_syslog("Call method ".$WS_METHOD2);
$result2 = $soapclient2->call($WS_METHOD2,$parameters);
if (! $result2)
{
    print $soapclient2->error_str;
    exit;
}


/*
 * View
 */

header("Content-type: text/html; charset=utf8");
print '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">'."\n";
echo '<html>'."\n";
echo '<head>';
echo '<title>WebService Test: '.$WS_METHOD1.'</title>';
echo '</head>'."\n";

echo '<body>'."\n";

echo "<h2>Request:</h2>";
echo '<h4>Function</h4>';
echo $WS_METHOD1;
echo '<h4>SOAP Message</h4>';
echo '<pre>' . htmlspecialchars($soapclient1->request, ENT_QUOTES) . '</pre>';
echo '<hr>';
echo "<h2>Response:</h2>";
echo '<h4>Result</h4>';
echo '<pre>';
print_r($result1);
echo '</pre>';
echo '<h4>SOAP Message</h4>';
echo '<pre>' . htmlspecialchars($soapclient1->response, ENT_QUOTES) . '</pre>';

print '<hr>';

echo "<h2>Request:</h2>";
echo '<h4>Function</h4>';
echo $WS_METHOD2;
echo '<h4>SOAP Message</h4>';
echo '<pre>' . htmlspecialchars($soapclient2->request, ENT_QUOTES) . '</pre>';
echo '<hr>';
echo "<h2>Response:</h2>";
echo '<h4>Result</h4>';
echo '<pre>';
print_r($result2);
echo '</pre>';
echo '<h4>SOAP Message</h4>';
echo '<pre>' . htmlspecialchars($soapclient2->response, ENT_QUOTES) . '</pre>';

echo '</body>'."\n";;
echo '</html>'."\n";;
?>
