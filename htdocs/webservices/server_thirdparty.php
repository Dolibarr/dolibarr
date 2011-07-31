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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *       \file       htdocs/webservices/server_thirdparty.php
 *       \brief      File that is entry point to call Dolibarr WebServices
 *       \version    $Id: server_thirdparty.php,v 1.13 2011/07/31 23:21:08 eldy Exp $
 */

// This is to make Dolibarr working with Plesk
set_include_path($_SERVER['DOCUMENT_ROOT'].'/htdocs');

require_once("../master.inc.php");
require_once(NUSOAP_PATH.'/nusoap.php');        // Include SOAP
require_once(DOL_DOCUMENT_ROOT."/lib/ws.lib.php");
require_once(DOL_DOCUMENT_ROOT."/user/class/user.class.php");

require_once(DOL_DOCUMENT_ROOT."/societe/class/societe.class.php");


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
$server = new nusoap_server();
$server->soap_defencoding='UTF-8';
$server->decode_utf8=false;
$ns='http://www.dolibarr.org/ns/';
$server->configureWSDL('WebServicesDolibarrThirdParty',$ns);
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
        'thirdparty',
 	    'complexType',
	    'struct',
	    'all',
	    '',
	    array(
	    	'id' => array('name'=>'id','type'=>'xsd:string'),
	        'ref' => array('name'=>'name','type'=>'xsd:string'),
	        'ref_ext' => array('name'=>'ref_ext','type'=>'xsd:string'),
	        'fk_user_author' => array('name'=>'fk_user_author','type'=>'xsd:string'),
	        'date' => array('name'=>'date','type'=>'xsd:date'),
	        'date_creation' => array('name'=>'date_creation','type'=>'xsd:dateTime'),
	        'date_modification' => array('name'=>'date_modification','type'=>'xsd:dateTime'),
	        'note' => array('name'=>'note','type'=>'xsd:string'),
	    	'address' => array('name'=>'address','type'=>'xsd:string'),
	    	'zip' => array('name'=>'zip','type'=>'xsd:string'),
	    	'town' => array('name'=>'town','type'=>'xsd:string'),
	    	'province_id' => array('name'=>'province_id','type'=>'xsd:string'),
	    	'country_id' => array('name'=>'country_id','type'=>'xsd:string'),
	    	'country_code' => array('name'=>'country_code','type'=>'xsd:string'),
	    	'country' => array('name'=>'country','type'=>'xsd:string'),
	        'phone' => array('name'=>'country_id','type'=>'xsd:string'),
	    	'fax' => array('name'=>'country_id','type'=>'xsd:string'),
	    	'email' => array('name'=>'country_id','type'=>'xsd:string'),
	    	'url' => array('name'=>'country_id','type'=>'xsd:string'),
	    	'profid1' => array('name'=>'profid1','type'=>'xsd:string'),
	    	'profid2' => array('name'=>'profid2','type'=>'xsd:string'),
	    	'profid3' => array('name'=>'profid3','type'=>'xsd:string'),
	    	'profid4' => array('name'=>'profid4','type'=>'xsd:string'),
	    	'prefix' => array('name'=>'prefix','type'=>'xsd:string'),
	    	'vat_used' => array('name'=>'vat_used','type'=>'xsd:string'),
	    	'vat_number' => array('name'=>'vat_number','type'=>'xsd:string')
	    )
    );

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


// 5 styles: RPC/encoded, RPC/literal, Document/encoded (not WS-I compliant), Document/literal, Document/literal wrapped
// Style merely dictates how to translate a WSDL binding to a SOAP message. Nothing more. You can use either style with any programming model.
// http://www.ibm.com/developerworks/webservices/library/ws-whichwsdl/
$styledoc='rpc';       // rpc/document (document is an extend into SOAP 1.0 to support unstructured messages)
$styleuse='encoded';   // encoded/literal/literal wrapped
// Better choice is document/literal wrapped but literal wrapped not supported by nusoap.

// Register WSDL
$server->register('getThirdParty',
// Entry values
array('authentication'=>'tns:authentication','id'=>'xsd:string','ref'=>'xsd:string','ref_ext'=>'xsd:string'),
// Exit values
array('result'=>'tns:result','thirdparty'=>'tns:thirdparty'),
$ns,
$ns.'#getVersions',
$styledoc,
$styleuse,
'WS to get Versions'
);



// Full methods code
function getThirdParty($authentication,$id='',$ref='',$ref_ext='')
{
	global $db,$conf,$langs;

	dol_syslog("Function: getThirdParty login=".$authentication['login']." id=".$id." ref=".$ref." ref_ext=".$ref_ext);

	if ($authentication['entity']) $conf->entity=$authentication['entity'];

    // Init and check authentication
    $objectresp=array();
    $errorcode='';$errorlabel='';
    $error=0;
    $fuser=check_authentication($authentication,$error,$errorcode,$errorlabel);
    // Check parameters
	if (! $error && (($id && $ref) || ($id && $ref_ext) || ($ref && $ref_ext)))
	{
		$error++;
		$errorcode='BAD_PARAMETERS'; $errorlabel="Parameter id, ref and ref_ext can't be both provided. You must choose one or other but not both.";
	}

	if (! $error)
	{
		$fuser->getrights();

		if ($fuser->rights->societe->lire)
		{
			$thirdparty=new Societe($db);
			$result=$thirdparty->fetch($id,$ref,$ref_ext);
			if ($result > 0)
			{
			    // Create
			    $objectresp = array(
			    	'result'=>array('result_code'=>'OK', 'result_label'=>''),
			        'thirdparty'=>array(
				    	'id' => $thirdparty->id,
			   			'ref' => $thirdparty->name,
			   			'ref_ext' => $thirdparty->ref_ext,
			    		'fk_user_author' => $thirdparty->fk_user_author,
//			    		'date_creation' => $thirdparty->
//			    		'date_modification' => $thirdparty->
			            'address' => $thirdparty->address,
				        'zip' => $thirdparty->cp,
				        'town' => $thirdparty->ville,
				        'province_id' => $thirdparty->departement_id,
				        'country_id' => $thirdparty->pays_id,
				        'country_code' => $thirdparty->pays_code,
				        'country' => $thirdparty->country,
			            'phone' => $thirdparty->tel,
				        'fax' => $thirdparty->fax,
				        'email' => $thirdparty->email,
				        'url' => $thirdparty->url,
				        'profid1' => $thirdparty->siren,
				        'profid2' => $thirdparty->siret,
				        'profid3' => $thirdparty->ape,
				        'profid4' => $thirdparty->idprof4,
				        'prefix' => $thirdparty->prefix_comm,
				        'vat_used' => $thirdparty->tva_assuj,
				        'vat_number' => $thirdparty->tva_intra
			    ));
			}
			else
			{
				$error++;
				$errorcode='NOT_FOUND'; $errorlabel='Object not found for id='.$id.' nor ref='.$ref.' nor ref_ext='.$ref_ext;
			}
		}
		else
		{
			$error++;
			$errorcode='PERMISSION_DENIED'; $errorlabel='User does not have permission for this request';
		}
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
