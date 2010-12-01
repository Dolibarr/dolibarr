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
 *       \file       htdocs/webservices/server_invoice.php
 *       \brief      File that is entry point to call Dolibarr WebServices
 *       \version    $Id$
 */

// This is to make Dolibarr working with Plesk
set_include_path($_SERVER['DOCUMENT_ROOT'].'/htdocs');

require_once("../master.inc.php");
require_once(NUSOAP_PATH.'/nusoap.php');		// Include SOAP
require_once(DOL_DOCUMENT_ROOT."/user/class/user.class.php");
require_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");


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
$server->configureWSDL('WebServicesDolibarrInvoice',$ns);
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
    'line',
    'element',
    'struct',
    'all',
    '',
    array(
        'id' => array('name'=>'id','type'=>'xsd:string'),
        'type' => array('name'=>'type','type'=>'xsd:string'),
    	'fk_product' => array('name'=>'fk_product','type'=>'xsd:int'),
    	'total_ht' => array('name'=>'total_ht','type'=>'xsd:int'),
    	'total_vat' => array('name'=>'total_vat','type'=>'xsd:int'),
    	'total_ttc' => array('name'=>'total_ttc','type'=>'xsd:int')
    )
);

$server->wsdl->addComplexType(
    'LinesArray',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    array(),
    array(
        array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'tns:line[]')
    ),
    'tns:line'
);

$server->wsdl->addComplexType(
        'invoice',
 	    'complexType',
	    'struct',
	    'all',
	    '',
	    array(
	    	'id' => array('name'=>'id','type'=>'xsd:string'),
	        'ref' => array('name'=>'ref','type'=>'xsd:string'),
	        'fk_user_author' => array('name'=>'fk_user_author','type'=>'xsd:string'),
	        'fk_user_valid' => array('name'=>'fk_user_valid','type'=>'xsd:string'),
	        'date' => array('name'=>'date','type'=>'xsd:int'),
	        'date_creation' => array('name'=>'date_creation','type'=>'xsd:int'),
	        'date_validation' => array('name'=>'date_validation','type'=>'xsd:int'),
	        'date_modification' => array('name'=>'date_modification','type'=>'xsd:int'),
	        'type' => array('name'=>'type','type'=>'xsd:int'),
	        'total' => array('name'=>'type','type'=>'xsd:int'),
	        'total_vat' => array('name'=>'type','type'=>'xsd:int'),
	        'total_vat' => array('name'=>'type','type'=>'xsd:int'),
	        'note' => array('name'=>'note','type'=>'xsd:int'),
	        'note_public' => array('name'=>'note_public','type'=>'xsd:int'),
	        'status' => array('name'=>'status','type'=>'xsd:int'),
	        'close_code' => array('name'=>'close_code','type'=>'xsd:int'),
	        'close_note' => array('name'=>'close_note','type'=>'xsd:int'),
	    	'lines' => array('name'=>'lines','type'=>'tns:LinesArray')
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


// Register WSDL
$server->register('getInvoice',
// Entry values
array('authentication'=>'tns:authentication','id'=>'xsd:string','ref'=>'xsd:string'),
// Exit values
array('result'=>'tns:result','invoice'=>'tns:invoice'),
$ns
);


// Full methods code
function getInvoice($authentication,$id,$ref)
{
	global $db,$conf,$langs;

	dol_syslog("Function: getInvoice login=".$authentication['login']." id=".$id." ref=".$ref);

	if ($authentication['entity']) $conf->entity=$authentication['entity'];

	$objectresp=array();
	$errorcode='';$errorlabel='';
	$error=0;

	if (! $error && ($authentication['dolibarrkey'] != $conf->global->WEBSERVICES_KEY))
	{
		$error++;
		$errorcode='BAD_VALUE_FOR_SECURITY_KEY'; $errorlabel='Value provided into dolibarrkey entry field does not match security key defined in Webservice module setup';
	}

	if (! $error && $id && $ref)
	{
		$error++;
		$errorcode='BAD_PARAMETERS'; $errorlabel='Parameter id and ref can\'t be both provided. You must choose one or other but not both.';
	}

	if (! $error)
	{
		$fuser=new User($db);
		$result=$fuser->fetch('',$authentication['login'],'',0);
		if ($result <= 0) $error++;

		// TODO Check password



		if ($error)
		{
			$errorcode='BAD_CREDENTIALS'; $errorlabel='Bad value for login or password';
		}
	}

	if (! $error)
	{
		$fuser->getrights();

		if ($fuser->rights->facture->lire)
		{
			$invoice=new Facture($db);
			$result=$invoice->fetch($id,$ref);
			if ($result > 0)
			{
				$linesresp=array();
				$i=0;
				foreach($invoice->lines as $line)
				{
					//var_dump($line);
					$linesresp[]=array(
						'id'=>$line->rowid,
						'type'=>$line->type,
						'total_ht'=>$line->total_ht,
						'total_vat'=>$line->total_tva,
						'total_ttc'=>$line->total_ttc,


					);
					$i++;
				}

			    // Create invoice
			    $objectresp = array(
			    	'result'=>array('result_code'=>'', 'result_label'=>''),
			        'invoice'=>array(
				    	'id' => $invoice->id,
			   			'ref' => $invoice->ref,
			            'fk_user_author' => $invoice->fk_user_author,
			            'fk_user_valid' => $invoice->fk_user_valid,
			    		'lines' => $linesresp
//					        'lines' => array('0'=>array('id'=>222,'type'=>1),
//				        				 '1'=>array('id'=>333,'type'=>1))

			    ));
			}
			else
			{
				$error++;
				$errorcode='FAILEDTOREAD'; $errorlabel='Object not found for id='.$id.' nor ref='.$ref;
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
