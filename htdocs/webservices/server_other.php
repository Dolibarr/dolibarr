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
 *       \file       htdocs/webservices/server_other.php
 *       \brief      File that is entry point to call Dolibarr WebServices
 *       \version    $Id$
 */

// This is to make Dolibarr working with Plesk
set_include_path($_SERVER['DOCUMENT_ROOT'].'/htdocs');

require_once("../master.inc.php");
require_once(DOL_DOCUMENT_ROOT."/user/class/user.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/functions2.lib.php");
require_once(NUSOAP_PATH.'/nusoap.php');		// Include SOAP


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

// Create the soap Object
$server = new soap_server();
$server->soap_defencoding='UTF-8';
$ns='http://www.dolibarr.org/ns';
$server->configureWSDL('WebServicesDolibarrOther',$ns);
$server->wsdl->schemaTargetNamespace=$ns;


// Define WSDL content
$server->wsdl->addComplexType(
        'authentication',
 	    'complexType',
	    'struct',
	    'all',
	    '',
	    array(
	        'dolibarrkey' => array('name'=>'dolibarrkey','type'=>'xsd:string'),
	    	'sourceapplication' => array('name'=>'sourceapplication','type'=>'xsd:string'),
	    	'login' => array('name'=>'login','type'=>'xsd:string'),
	        'password' => array('name'=>'password','type'=>'xsd:string'),
	        'entity' => array('name'=>'entity','type'=>'xsd:string'),
	    ));

$server->wsdl->addComplexType(
    'result',
    'complexType',
    'struct',
    'all',
    '',
    array(
        'result_code' => array('name'=>'result_code','type'=>'xsd:string'),
        'result_label' => array('name'=>'result_label','type'=>'xsd:string'),
    ));


// Register WSDL
$server->register('getVersions',
// Entry values
array('authentication'=>'tns:authentication'),
// Exit values
array('result'=>'tns:result','dolibarr'=>'xsd:string','os'=>'xsd:string','php'=>'xsd:string','webserver'=>'xsd:string'),
$ns);



// Full methods code
function getVersions($authentication)
{
	global $db,$conf,$langs;

	dol_syslog("Function: getVersions login=".$authentication['login']);

	if ($authentication['entity']) $conf->entity=$authentication['entity'];

	$objectresp=array();
	$errorcode='';$errorlabel='';
	$error=0;

	if (! $error && empty($conf->global->WEBSERVICES_KEY))
	{
        $error++;
        $errorcode='SETUP_NOT_COMPLETE'; $errorlabel='Value for dolibarr security key not yet defined into Webservice module setup';
	}
	if (! $error && $authentication['dolibarrkey'] != $conf->global->WEBSERVICES_KEY)
	{
		$error++;
		$errorcode='BAD_VALUE_FOR_SECURITY_KEY'; $errorlabel='Value provided into dolibarrkey entry field does not match security key defined in Webservice module setup';
	}

	if (! $error)
	{
		$objectresp['result']=array('result_code'=>'', 'result_label'=>'');
		$objectresp['dolibarr']=version_dolibarr();
		$objectresp['os']=version_os();
		$objectresp['php']=version_php();
		$objectresp['webserver']=version_webserver();
	}

	if ($error)
	{
		$objectresp = array('result'=>array('result_code' => $errorcode, 'result_label' => $errorlabel));
	}

	return $objectresp;
}


// Return the results.
$server->service($HTTP_RAW_POST_DATA);

?>
