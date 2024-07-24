<?php
/* Copyright (C) 2006-2016 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2012      JF FERRY             <jfefe@aternatik.fr>
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
 *       \file       htdocs/webservices/server_category.php
 *       \brief      File that is entry point to call Dolibarr WebServices
 */

if (!defined('NOCSRFCHECK')) {
	define('NOCSRFCHECK', '1'); // Do not check anti CSRF attack test
}
if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', '1'); // Do not check anti POST attack test
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1'); // If there is no need to load and show top and left menu
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', '1'); // If we don't need to load the html.form.class.php
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1'); // Do not load ajax.lib.php library
}
if (!defined("NOLOGIN")) {
	define("NOLOGIN", '1'); // If this page is public (can be called outside logged session)
}
if (!defined("NOSESSION")) {
	define("NOSESSION", '1');
}

require '../main.inc.php';
require_once NUSOAP_PATH.'/nusoap.php'; // Include SOAP
require_once DOL_DOCUMENT_ROOT.'/core/lib/ws.lib.php';
require_once DOL_DOCUMENT_ROOT."/categories/class/categorie.class.php";


dol_syslog("Call Dolibarr webservices interfaces");

// Enable and test if module web services is enabled
if (!getDolGlobalString('MAIN_MODULE_WEBSERVICES')) {
	$langs->load("admin");
	dol_syslog("Call Dolibarr webservices interfaces with module webservices disabled");
	print $langs->trans("WarningModuleNotActive", 'WebServices').'.<br><br>';
	print $langs->trans("ToActivateModule");
	exit;
}

// Create the soap Object
$server = new nusoap_server();
$server->soap_defencoding = 'UTF-8';
$server->decode_utf8 = false;
$ns = 'http://www.dolibarr.org/ns/';
$server->configureWSDL('WebServicesDolibarrCategorie', $ns);
$server->wsdl->schemaTargetNamespace = $ns;


// Define WSDL content
$server->wsdl->addComplexType(
	'authentication',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'dolibarrkey' => array('name'=>'dolibarrkey', 'type'=>'xsd:string'),
		'sourceapplication' => array('name'=>'sourceapplication', 'type'=>'xsd:string'),
		'login' => array('name'=>'login', 'type'=>'xsd:string'),
		'password' => array('name'=>'password', 'type'=>'xsd:string'),
		'entity' => array('name'=>'entity', 'type'=>'xsd:string'),
	)
);

/*
 * Une catégorie
 */
$server->wsdl->addComplexType(
	'categorie',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'id' => array('name'=>'id', 'type'=>'xsd:string'),
		'id_mere' => array('name'=>'id_mere', 'type'=>'xsd:string'),
		'label' => array('name'=>'label', 'type'=>'xsd:string'),
		'description' => array('name'=>'description', 'type'=>'xsd:string'),
		'socid' => array('name'=>'socid', 'type'=>'xsd:string'),
		'type' => array('name'=>'type', 'type'=>'xsd:string'),
		'visible' => array('name'=>'visible', 'type'=>'xsd:string'),
		'dir'=> array('name'=>'dir', 'type'=>'xsd:string'),
		'photos' => array('name'=>'photos', 'type'=>'tns:PhotosArray'),
		'filles' => array('name'=>'filles', 'type'=>'tns:FillesArray')
	)
);

/*
 * Les catégories filles, sous tableau dez la catégorie
 */
$server->wsdl->addComplexType(
	'FillesArray',
	'complexType',
	'array',
	'',
	'SOAP-ENC:Array',
	array(),
	array(
		array('ref'=>'SOAP-ENC:arrayType', 'wsdl:arrayType'=>'tns:categorie[]')
	),
	'tns:categorie'
);

 /*
  * Image of product
 */
$server->wsdl->addComplexType(
	'PhotosArray',
	'complexType',
	'array',
	'sequence',
	'',
	array(
				'image' => array(
						'name' => 'image',
						'type' => 'tns:image',
						'minOccurs' => '0',
						'maxOccurs' => 'unbounded'
				)
		)
);

 /*
  * An image
 */
$server->wsdl->addComplexType(
	'image',
	'complexType',
	'struct',
	'all',
	'',
	array(
				'photo' => array('name'=>'photo', 'type'=>'xsd:string'),
				'photo_vignette' => array('name'=>'photo_vignette', 'type'=>'xsd:string'),
				'imgWidth' => array('name'=>'imgWidth', 'type'=>'xsd:string'),
				'imgHeight' => array('name'=>'imgHeight', 'type'=>'xsd:string')
		)
);

/*
 * Retour
 */
$server->wsdl->addComplexType(
	'result',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'result_code' => array('name'=>'result_code', 'type'=>'xsd:string'),
		'result_label' => array('name'=>'result_label', 'type'=>'xsd:string'),
	)
);

// 5 styles: RPC/encoded, RPC/literal, Document/encoded (not WS-I compliant), Document/literal, Document/literal wrapped
// Style merely dictates how to translate a WSDL binding to a SOAP message. Nothing more. You can use either style with any programming model.
// http://www.ibm.com/developerworks/webservices/library/ws-whichwsdl/
$styledoc = 'rpc'; // rpc/document (document is an extend into SOAP 1.0 to support unstructured messages)
$styleuse = 'encoded'; // encoded/literal/literal wrapped
// Better choice is document/literal wrapped but literal wrapped not supported by nusoap.


// Register WSDL
$server->register(
	'getCategory',
	// Entry values
	array('authentication'=>'tns:authentication', 'id'=>'xsd:string'),
	// Exit values
	array('result'=>'tns:result', 'categorie'=>'tns:categorie'),
	$ns,
	$ns.'#getCategory',
	$styledoc,
	$styleuse,
	'WS to get category'
);


/**
 * Get category infos and children
 *
 * @param	array		$authentication		Array of authentication information
 * @param	int			$id					Id of object
 * @return	mixed
 */
function getCategory($authentication, $id)
{
	global $db, $conf, $langs;

	$nbmax = 10;

	dol_syslog("Function: getCategory login=".$authentication['login']." id=".$id);

	if ($authentication['entity']) {
		$conf->entity = $authentication['entity'];
	}

	$objectresp = array();
	$errorcode = '';
	$errorlabel = '';
	$error = 0;
	$fuser = check_authentication($authentication, $error, $errorcode, $errorlabel);

	if (!$error && !$id) {
		$error++;
		$errorcode = 'BAD_PARAMETERS';
		$errorlabel = "Parameter id must be provided.";
	}

	if (!$error) {
		$fuser->getrights();

		$nbmax = 10;
		if ($fuser->hasRight('categorie', 'lire')) {
			$categorie = new Categorie($db);
			$result = $categorie->fetch($id);
			if ($result > 0) {
				$dir = (!empty($conf->categorie->dir_output) ? $conf->categorie->dir_output : $conf->service->dir_output);
				$pdir = get_exdir($categorie->id, 2, 0, 0, $categorie, 'category').$categorie->id."/photos/";
				$dir = $dir.'/'.$pdir;

				$cat = array(
					'id' => $categorie->id,
					'id_mere' => $categorie->id_mere,
					'label' => $categorie->label,
					'description' => $categorie->description,
					'socid' => $categorie->socid,
					//'visible'=>$categorie->visible,
					'type' => $categorie->type,
					'dir' => $pdir,
					'photos' => $categorie->liste_photos($dir, $nbmax)
				);

				$cats = $categorie->get_filles();
				if (count($cats) > 0) {
					foreach ($cats as $fille) {
						$dir = (!empty($conf->categorie->dir_output) ? $conf->categorie->dir_output : $conf->service->dir_output);
						$pdir = get_exdir($fille->id, 2, 0, 0, $categorie, 'category').$fille->id."/photos/";
						$dir = $dir.'/'.$pdir;
						$cat['filles'][] = array(
							'id'=>$fille->id,
							'id_mere' => $categorie->id_mere,
							'label'=>$fille->label,
							'description'=>$fille->description,
							'socid'=>$fille->socid,
							//'visible'=>$fille->visible,
							'type'=>$fille->type,
							'dir' => $pdir,
							'photos' => $fille->liste_photos($dir, $nbmax)
						);
					}
				}

				// Create
				$objectresp = array(
					'result'=>array('result_code'=>'OK', 'result_label'=>''),
					'categorie'=> $cat
				);
			} else {
				$error++;
				$errorcode = 'NOT_FOUND';
				$errorlabel = 'Object not found for id='.$id;
			}
		} else {
			$error++;
			$errorcode = 'PERMISSION_DENIED';
			$errorlabel = 'User does not have permission for this request';
		}
	}

	if ($error) {
		$objectresp = array('result'=>array('result_code' => $errorcode, 'result_label' => $errorlabel));
	}

	return $objectresp;
}

// Return the results.
$server->service(file_get_contents("php://input"));
