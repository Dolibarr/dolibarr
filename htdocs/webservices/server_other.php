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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *       \file       htdocs/webservices/server_other.php
 *       \brief      File that is entry point to call Dolibarr WebServices
 */

if (! defined("NOCSRFCHECK"))    define("NOCSRFCHECK", '1');

require '../master.inc.php';
require_once NUSOAP_PATH.'/nusoap.php';        // Include SOAP
require_once DOL_DOCUMENT_ROOT.'/core/lib/ws.lib.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';


dol_syslog("Call Dolibarr webservices interfaces");

$langs->load("main");

// Enable and test if module web services is enabled
if (empty($conf->global->MAIN_MODULE_WEBSERVICES))
{
	$langs->load("admin");
	dol_syslog("Call Dolibarr webservices interfaces with module webservices disabled");
	print $langs->trans("WarningModuleNotActive", 'WebServices').'.<br><br>';
	print $langs->trans("ToActivateModule");
	exit;
}

// Create the soap Object
$server = new nusoap_server();
$server->soap_defencoding='UTF-8';
$server->decode_utf8=false;
$ns='http://www.dolibarr.org/ns/';
$server->configureWSDL('WebServicesDolibarrOther', $ns);
$server->wsdl->schemaTargetNamespace=$ns;


// Define WSDL Authentication object
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
    )
);
// Define WSDL Return object
$server->wsdl->addComplexType(
    'result',
    'complexType',
    'struct',
    'all',
    '',
    array(
        'result_code' => array('name'=>'result_code','type'=>'xsd:string'),
        'result_label' => array('name'=>'result_label','type'=>'xsd:string'),
    )
);

// Define WSDL Return object for document
$server->wsdl->addComplexType(
	'document',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'filename' => array('name'=>'filename','type'=>'xsd:string'),
		'mimetype' => array('name'=>'mimetype','type'=>'xsd:string'),
		'content' => array('name'=>'content','type'=>'xsd:string'),
		'length' => array('name'=>'length','type'=>'xsd:string')
	)
);

// Define other specific objects
// None


// 5 styles: RPC/encoded, RPC/literal, Document/encoded (not WS-I compliant), Document/literal, Document/literal wrapped
// Style merely dictates how to translate a WSDL binding to a SOAP message. Nothing more. You can use either style with any programming model.
// http://www.ibm.com/developerworks/webservices/library/ws-whichwsdl/
$styledoc='rpc';       // rpc/document (document is an extend into SOAP 1.0 to support unstructured messages)
$styleuse='encoded';   // encoded/literal/literal wrapped
// Better choice is document/literal wrapped but literal wrapped not supported by nusoap.

// Register WSDL
$server->register(
    'getVersions',
    // Entry values
    array('authentication'=>'tns:authentication'),
    // Exit values
    array('result'=>'tns:result','dolibarr'=>'xsd:string','os'=>'xsd:string','php'=>'xsd:string','webserver'=>'xsd:string'),
    $ns,
    $ns.'#getVersions',
    $styledoc,
    $styleuse,
    'WS to get Versions'
);

// Register WSDL
$server->register(
	'getDocument',
	// Entry values
	array('authentication'=>'tns:authentication', 'modulepart'=>'xsd:string', 'file'=>'xsd:string' ),
	// Exit values
	array('result'=>'tns:result','document'=>'tns:document'),
	$ns,
	$ns.'#getDocument',
	$styledoc,
	$styleuse,
	'WS to get document'
);



// Full methods code
function getVersions($authentication)
{
	global $db,$conf,$langs;

	dol_syslog("Function: getVersions login=".$authentication['login']);

	if ($authentication['entity']) $conf->entity=$authentication['entity'];

    // Init and check authentication
    $objectresp=array();
    $errorcode='';$errorlabel='';
    $error=0;
    $fuser=check_authentication($authentication, $error, $errorcode, $errorlabel);
    // Check parameters


    if (! $error)
	{
		$objectresp['result']=array('result_code'=>'OK', 'result_label'=>'');
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


/**
 * Method to get a document by webservice
 *
 * @param 	array	$authentication		Array with permissions
 * @param 	string	$modulepart		 	Properties of document
 * @param	string	$file				Relative path
 * @param	string	$refname			Ref of object to check permission for external users (autodetect if not provided)
 * @return	void
 */
function getDocument($authentication, $modulepart, $file, $refname = '')
{
	global $db,$conf,$langs,$mysoc;

	dol_syslog("Function: getDocument login=".$authentication['login'].' - modulepart='.$modulepart.' - file='.$file);

	if ($authentication['entity']) $conf->entity=$authentication['entity'];

	$objectresp=array();
	$errorcode='';$errorlabel='';
	$error=0;

	// Properties of doc
	$original_file = $file;
	$type=dol_mimetype($original_file);
	//$relativefilepath = $ref . "/";
	//$relativepath = $relativefilepath . $ref.'.pdf';

	$accessallowed=0;

	$fuser=check_authentication($authentication, $error, $errorcode, $errorlabel);

	if ($fuser->societe_id) $socid=$fuser->societe_id;

	// Check parameters
	if (! $error && ( ! $file || ! $modulepart ) )
	{
		$error++;
		$errorcode='BAD_PARAMETERS'; $errorlabel="Parameter file and modulepart must be both provided.";
	}

	if (! $error)
	{
		$fuser->getrights();

		// Suppression de la chaine de caractere ../ dans $original_file
		$original_file = str_replace("../", "/", $original_file);

		// find the subdirectory name as the reference
		if (empty($refname)) $refname=basename(dirname($original_file)."/");

		// Security check
		$check_access = dol_check_secure_access_document($modulepart, $original_file, $conf->entity, $fuser, $refname);
		$accessallowed              = $check_access['accessallowed'];
		$sqlprotectagainstexternals = $check_access['sqlprotectagainstexternals'];
		$original_file              = $check_access['original_file'];

		// Basic protection (against external users only)
		if ($fuser->societe_id > 0)
		{
			if ($sqlprotectagainstexternals)
			{
				$resql = $db->query($sqlprotectagainstexternals);
				if ($resql)
				{
					$num=$db->num_rows($resql);
					$i=0;
					while ($i < $num)
					{
						$obj = $db->fetch_object($resql);
						if ($fuser->societe_id != $obj->fk_soc)
						{
							$accessallowed=0;
							break;
						}
						$i++;
					}
				}
			}
		}

		// Security:
		// Limite acces si droits non corrects
		if (! $accessallowed)
		{
			$errorcode='NOT_PERMITTED';
			$errorlabel='Access not allowed';
			$error++;
		}

		// Security:
		// On interdit les remontees de repertoire ainsi que les pipe dans
		// les noms de fichiers.
		if (preg_match('/\.\./', $original_file) || preg_match('/[<>|]/', $original_file))
		{
			dol_syslog("Refused to deliver file ".$original_file);
			$errorcode='REFUSED';
			$errorlabel='';
			$error++;
		}

		clearstatcache();

		if(!$error)
		{
			if(file_exists($original_file))
			{
				dol_syslog("Function: getDocument $original_file $filename content-type=$type");

				$file=$fileparams['fullname'];
				$filename = basename($file);

				$f = fopen($original_file, 'r');
				$content_file = fread($f, filesize($original_file));

				$objectret = array(
					'filename' => basename($original_file),
					'mimetype' => dol_mimetype($original_file),
					'content' => base64_encode($content_file),
					'length' => filesize($original_file)
				);

				// Create return object
				$objectresp = array(
					'result'=>array('result_code'=>'OK', 'result_label'=>''),
					'document'=>$objectret
				);
			}
			else
			{
				dol_syslog("File doesn't exist ".$original_file);
				$errorcode='NOT_FOUND';
				$errorlabel='';
				$error++;
			}
		}
	}

	if ($error)
	{
		$objectresp = array(
		'result'=>array('result_code' => $errorcode, 'result_label' => $errorlabel)
		);
	}

	return $objectresp;
}

// Return the results.
$server->service(file_get_contents("php://input"));
