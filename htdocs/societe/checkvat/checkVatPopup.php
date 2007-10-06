<?php
/* Copyright (C) 2006 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *
 * $Id$
 * $Source$
 */

/**
*		\file       htdocs/societe/checkvat/checkVatPopup.php
*		\ingroup    societe
*		\brief      Onglet societe d'une societe
*		\version    $Revision$
*/

require ("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/company.lib.php");
require_once(DOL_DOCUMENT_ROOT."/includes/nusoap/lib/nusoap.php");

$langs->load("companies");

$WS_DOL_URL='http://ec.europa.eu/taxation_customs/vies/vieshome.do';
$WS_METHOD  = 'checkVat';


top_htmlhead("", $langs->trans("VATIntraCheckableOnEUSite"));
print '<body style="margin: 10px">';
print '<div>';
print '<div>';

print_fiche_titre($langs->trans("VATIntraCheckableOnEUSite"),'','setup');


if (! $_REQUEST["countryCode"])
{
	print '<br>';
	print '<font class="error">'.$langs->transnoentities("ErrorFieldRequired",$langs->trans("Country")).'</font><br>';
}
elseif (! $_REQUEST["vatNumber"])
{
	print '<br>';
	print '<font class="error">'.$langs->transnoentities("ErrorFieldRequired",$langs->trans("VATIntraShort")).'</font><br>';
}
else
{
	print '<b>'.$langs->trans("Country").'</b>: '.$_REQUEST["countryCode"].'<br>';
	print '<b>'.$langs->trans("VATIntraShort").'</b>: '.$_REQUEST["vatNumber"].'<br>';
	print '<br>';
	
	// Set the parameters to send to the WebService
	$parameters = array("countryCode" => $_REQUEST["countryCode"],
						"vatNumber" => $_REQUEST["vatNumber"]);
	
	// Set the WebService URL
	dolibarr_syslog("Create soapclient_nusoap for URL=".$WS_DOL_URL);
	$soapclient = new soapclient_nusoap($WS_DOL_URL);
	
	// Call the WebService and store its result in $result.
	dolibarr_syslog("Call method ".$WS_METHOD);
	$result = $soapclient->call($WS_METHOD,$parameters);

//	print "x".$result['valid']."i";
//	print_r($result);
//	print $soapclient->request.'<br>';
//	print $soapclient->response.'<br>';
	
	print '<b>'.$langs->trans("Response").'</b>:<br>';

	// Service indisponible
	if (eregi('SERVICE_UNAVAILABLE',$result['faultstring']))
	{
		print '<font class="error">'.$langs->trans("ErrorServiceUnavailableTryLater").'</font><br>';
	}
	elseif (eregi('TIMEOUT',$result['faultstring']))
	{
		print '<font class="error">'.$langs->trans("ErrorServiceUnavailableTryLater").'</font><br>';	
	}
	elseif (eregi('SERVER_BUSY',$result['faultstring']))
	{
		print '<font class="error">'.$langs->trans("ErrorServiceUnavailableTryLater").'</font><br>';	
	}
	// Syntaxe ko
	elseif (eregi('INVALID_INPUT',$result['faultstring']) 
			|| ($result['requestDate'] && ! $result['valid']))
	{
		if ($result['requestDate']) print $langs->trans("Date").': '.$result['requestDate'].'<br>';
		print $langs->trans("VATIntraSyntaxIsValid").': <font class="error">'.$langs->trans("No").'</font><br>';
		print $langs->trans("VATIntraValueIsValid").': <font class="error">'.$langs->trans("No").'</font><br>';
	}
	else
	{
		// Syntaxe ok
		if ($result['requestDate']) print $langs->trans("Date").': '.$result['requestDate'].'<br>';
		print $langs->trans("VATIntraSyntaxIsValid").': <font class="ok">'.$langs->trans("Yes").'</font><br>';
		print $langs->trans("VATIntraValueIsValid").': ';
		if (eregi('MS_UNAVAILABLE',$result['faultstring']))
		{
			print '<font class="error">'.$langs->trans("ErrorVATCheckMS_UNAVAILABLE",$_REQUEST["countryCode"]).'</font><br>';	
		}
		else
		{
			if ($result['valid']) 
			{
				print '<font class="ok">'.$langs->trans("Yes").'</font>';
				print '<br>';
				print $langs->trans("Name").': '.$result['name'].'<br>';
				print $langs->trans("Address").': '.$result['address'].'<br>';
			}
			else
			{
				print '<font class="error">'.$langs->trans("No").'</font>';
				print '<br>';
			}
		}
	}
}

print '<br>';
print $langs->trans("VATIntraManualCheck",$langs->trans("VATIntraCheckURL"),$langs->trans("VATIntraCheckURL")).'<br>';
print '<br>';
print '<center><input type="button" class="button" value="'.$langs->trans("CloseWindow").'" onclick="javascript: window.close()"></center>';



llxFooter('$Date$ - $Revision$',0);
?>
