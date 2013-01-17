<?php
/* Copyright (C) 2006-2011	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2011		Regis Houssin		<regis.houssin@capnetworks.com>
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
 *       \file       htdocs/webservices/server_invoice.php
 *       \brief      File that is entry point to call Dolibarr WebServices
 */

// This is to make Dolibarr working with Plesk
set_include_path($_SERVER['DOCUMENT_ROOT'].'/htdocs');

require_once '../master.inc.php';
require_once NUSOAP_PATH.'/nusoap.php';		// Include SOAP
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';

$langs->load("admin");


/*
 * View
 */

dol_syslog("Call Dolibarr webservices interfaces");

// Enable and test if module web services is enabled
if (empty($conf->global->MAIN_MODULE_WEBSERVICES))
{
	$langs->load("admin");
	dol_syslog("Call Dolibarr webservices interfaces with module webservices disabled");
	print $langs->trans("WarningModuleNotActive",'WebServices').'.<br><br>';
	print $langs->trans("ToActivateModule");
	exit;
}



// WSDL
print '<u>'.$langs->trans("WSDLCanBeDownloadedHere").':</u><br>';
$url=DOL_MAIN_URL_ROOT.'/webservices/server_other.php?wsdl';
print img_picto('','object_globe.png') . ' <a href="' . $url . '" target="_blank">' . $url . "</a><br>\n";
if (! empty($conf->societe->enabled))
{
	$url=DOL_MAIN_URL_ROOT.'/webservices/server_thirdparty.php?wsdl';
	print img_picto('','object_globe.png') . ' <a href="' . $url . '" target="_blank">' . $url . "</a><br>\n";
}
if (! empty($conf->facture->enabled))
{
	$url=DOL_MAIN_URL_ROOT.'/webservices/server_invoice.php?wsdl';
	print img_picto('','object_globe.png') . ' <a href="' . $url . '" target="_blank">' . $url . "</a><br>\n";
}
if (! empty($conf->fournisseur->enabled))
{
    $url=DOL_MAIN_URL_ROOT.'/webservices/server_supplier_invoice.php?wsdl';
    print img_picto('','object_globe.png') . ' <a href="' . $url . '" target="_blank">' . $url . "</a><br>\n";
}
$url=DOL_MAIN_URL_ROOT.'/webservices/server_user.php?wsdl';
print img_picto('','object_globe.png') . ' <a href="' . $url . '" target="_blank">' . $url . "</a><br>\n";
print '<br>';


// Endpoint
print '<u>'.$langs->trans("EndPointIs").':</u><br>';
$url=DOL_MAIN_URL_ROOT.'/webservices/server_other.php';
print img_picto('','object_globe.png') . ' <a href="' . $url . '" target="_blank">' . $url . "</a><br>\n";
if (! empty($conf->societe->enabled))
{
	$url=DOL_MAIN_URL_ROOT.'/webservices/server_thirdparty.php';
	print img_picto('','object_globe.png') . ' <a href="' . $url . '" target="_blank">' . $url . "</a><br>\n";
}
if (! empty($conf->facture->enabled))
{
	$url=DOL_MAIN_URL_ROOT.'/webservices/server_invoice.php';
	print img_picto('','object_globe.png') . ' <a href="' . $url . '" target="_blank">' . $url . "</a><br>\n";
}
if (! empty($conf->fournisseur->enabled))
{
    $url=DOL_MAIN_URL_ROOT.'/webservices/server_supplier_invoice.php';
    print img_picto('','object_globe.png') . ' <a href="' . $url . '" target="_blank">' . $url . "</a><br>\n";
}
$url=DOL_MAIN_URL_ROOT.'/webservices/server_user.php';
print img_picto('','object_globe.png') . ' <a href="' . $url . '" target="_blank">' . $url . "</a><br>\n";
print '<br>';

print '<br>';
print 'NUSoap library path used by Dolibarr: ' . NUSOAP_PATH . '<br>';
print '<br>';

$db->close();

?>