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
 *       \file       htdocs/webservices/server_thirdparty.php
 *       \brief      File that is entry point to call Dolibarr WebServices
 */

if (!defined("NOCSRFCHECK")) {
	define("NOCSRFCHECK", '1');
}

require_once '../master.inc.php';
require_once NUSOAP_PATH.'/nusoap.php'; // Include SOAP
require_once DOL_DOCUMENT_ROOT.'/core/lib/ws.lib.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';

require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';


dol_syslog("Call Dolibarr webservices interfaces");

$langs->load("main");

// Enable and test if module web services is enabled
if (empty($conf->global->MAIN_MODULE_WEBSERVICES)) {
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
$server->configureWSDL('WebServicesDolibarrThirdParty', $ns);
$server->wsdl->schemaTargetNamespace = $ns;


// Define WSDL Authentication object
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
// Define WSDL Return object
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


$thirdparty_fields = array(
		'id' => array('name'=>'id', 'type'=>'xsd:string'),
		'ref' => array('name'=>'name', 'type'=>'xsd:string'),
		'ref_ext' => array('name'=>'ref_ext', 'type'=>'xsd:string'),
		'fk_user_author' => array('name'=>'fk_user_author', 'type'=>'xsd:string'),
		'status' => array('name'=>'status', 'type'=>'xsd:string'),
		'client' => array('name'=>'client', 'type'=>'xsd:string'),
		'supplier' => array('name'=>'supplier', 'type'=>'xsd:string'),
		'customer_code' => array('name'=>'customer_code', 'type'=>'xsd:string'),
		'supplier_code' => array('name'=>'supplier_code', 'type'=>'xsd:string'),
		'customer_code_accountancy' => array('name'=>'customer_code_accountancy', 'type'=>'xsd:string'),
		'supplier_code_accountancy' => array('name'=>'supplier_code_accountancy', 'type'=>'xsd:string'),
		'date_creation' => array('name'=>'date_creation', 'type'=>'xsd:dateTime'),
		'date_modification' => array('name'=>'date_modification', 'type'=>'xsd:dateTime'),
		'note_private' => array('name'=>'note_private', 'type'=>'xsd:string'),
		'note_public' => array('name'=>'note_public', 'type'=>'xsd:string'),
		'address' => array('name'=>'address', 'type'=>'xsd:string'),
		'zip' => array('name'=>'zip', 'type'=>'xsd:string'),
		'town' => array('name'=>'town', 'type'=>'xsd:string'),
		'region_code' => array('name'=>'region_code', 'type'=>'xsd:string'),
		'country_id' => array('name'=>'country_id', 'type'=>'xsd:string'),
		'country_code' => array('name'=>'country_code', 'type'=>'xsd:string'),
		'country' => array('name'=>'country', 'type'=>'xsd:string'),
		'phone' => array('name'=>'phone', 'type'=>'xsd:string'),
		'fax' => array('name'=>'fax', 'type'=>'xsd:string'),
		'email' => array('name'=>'email', 'type'=>'xsd:string'),
		'url' => array('name'=>'url', 'type'=>'xsd:string'),
		'profid1' => array('name'=>'profid1', 'type'=>'xsd:string'),
		'profid2' => array('name'=>'profid2', 'type'=>'xsd:string'),
		'profid3' => array('name'=>'profid3', 'type'=>'xsd:string'),
		'profid4' => array('name'=>'profid4', 'type'=>'xsd:string'),
		'profid5' => array('name'=>'profid5', 'type'=>'xsd:string'),
		'profid6' => array('name'=>'profid6', 'type'=>'xsd:string'),
		'capital' => array('name'=>'capital', 'type'=>'xsd:string'),
		'vat_used' => array('name'=>'vat_used', 'type'=>'xsd:string'),
		'vat_number' => array('name'=>'vat_number', 'type'=>'xsd:string'));

$elementtype = 'societe';

// Retrieve all extrafields for thirdsparty
// fetch optionals attributes and labels
$extrafields = new ExtraFields($db);
$extrafields->fetch_name_optionals_label($elementtype, true);
$extrafield_array = null;
if (is_array($extrafields) && count($extrafields) > 0) {
	$extrafield_array = array();
}
if (isset($extrafields->attributes[$elementtype]['label']) && is_array($extrafields->attributes[$elementtype]['label']) && count($extrafields->attributes[$elementtype]['label'])) {
	foreach ($extrafields->attributes[$elementtype]['label'] as $key => $label) {
		//$value=$object->array_options["options_".$key];
		$type = $extrafields->attributes[$elementtype]['type'][$key];
		if ($type == 'date' || $type == 'datetime') {
			$type = 'xsd:dateTime';
		} else {
			$type = 'xsd:string';
		}

		$extrafield_array['options_'.$key] = array('name'=>'options_'.$key, 'type'=>$type);
	}
}

if (is_array($extrafield_array)) {
	$thirdparty_fields = array_merge($thirdparty_fields, $extrafield_array);
}

// Define other specific objects
$server->wsdl->addComplexType(
	'thirdparty',
	'complexType',
	'struct',
	'all',
	'',
	$thirdparty_fields
);

// Define other specific objects
$server->wsdl->addComplexType(
	'filterthirdparty',
	'complexType',
	'struct',
	'all',
	'',
	array(
		//'limit' => array('name'=>'limit','type'=>'xsd:string'),
		'client' => array('name'=>'client', 'type'=>'xsd:string'),
		'supplier' => array('name'=>'supplier', 'type'=>'xsd:string'),
		'category' => array('name'=>'category', 'type'=>'xsd:string')
	)
);

$server->wsdl->addComplexType(
	'ThirdPartiesArray',
	'complexType',
	'array',
	'',
	'SOAP-ENC:Array',
	array(),
	array(
		array('ref'=>'SOAP-ENC:arrayType', 'wsdl:arrayType'=>'tns:thirdparty[]')
	),
	'tns:thirdparty'
);
$server->wsdl->addComplexType(
	'ThirdPartiesArray2',
	'complexType',
	'array',
	'sequence',
	'',
	array(
		'thirdparty' => array(
			'name' => 'thirdparty',
			'type' => 'tns:thirdparty',
			'minOccurs' => '0',
			'maxOccurs' => 'unbounded'
		)
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
	'getThirdParty',
	// Entry values
	array('authentication'=>'tns:authentication', 'id'=>'xsd:string', 'ref'=>'xsd:string', 'ref_ext'=>'xsd:string', 'barcode'=>'xsd:string', 'profid1'=>'xsd:string', 'profid2'=>'xsd:string'),
	// Exit values
	array('result'=>'tns:result', 'thirdparty'=>'tns:thirdparty'),
	$ns,
	$ns.'#getThirdParty',
	$styledoc,
	$styleuse,
	'WS to get a thirdparty from its id, ref or ref_ext'
);

// Register WSDL
$server->register(
	'createThirdParty',
	// Entry values
	array('authentication'=>'tns:authentication', 'thirdparty'=>'tns:thirdparty'),
	// Exit values
	array('result'=>'tns:result', 'id'=>'xsd:string', 'ref'=>'xsd:string'),
	$ns,
	$ns.'#createThirdParty',
	$styledoc,
	$styleuse,
	'WS to create a thirdparty'
);

// Register WSDL
$server->register(
	'updateThirdParty',
	// Entry values
	array('authentication'=>'tns:authentication', 'thirdparty'=>'tns:thirdparty'),
	// Exit values
	array('result'=>'tns:result', 'id'=>'xsd:string'),
	$ns,
	$ns.'#updateThirdParty',
	$styledoc,
	$styleuse,
	'WS to update a thirdparty'
);


// Register WSDL
$server->register(
	'getListOfThirdParties',
	// Entry values
	array('authentication'=>'tns:authentication', 'filterthirdparty'=>'tns:filterthirdparty'),
	// Exit values
	array('result'=>'tns:result', 'thirdparties'=>'tns:ThirdPartiesArray2'),
	$ns,
	$ns.'#getListOfThirdParties',
	$styledoc,
	$styleuse,
	'WS to get list of thirdparties id and ref'
);

// Register WSDL
$server->register(
	'deleteThirdParty',
	// Entry values
		array('authentication'=>'tns:authentication', 'id'=>'xsd:string', 'ref'=>'xsd:string', 'ref_ext'=>'xsd:string'),
	// Exit values
		array('result'=>'tns:result', 'id'=>'xsd:string'),
	$ns,
	$ns.'#deleteThirdParty',
	$styledoc,
	$styleuse,
	'WS to delete a thirdparty from its id, ref or ref_ext'
);


// Full methods code
/**
 * Get a thirdparty
 *
 * @param	array		$authentication		Array of authentication information
 * @param	string		$id		    		internal id
 * @param	string		$ref		    	internal reference
 * @param	string		$ref_ext	   		external reference
 * @param	string		$barcode	   		barcode
 * @param	string		$profid1	   		profid1
 * @param	string		$profid2	   		profid2
 * @return	array							Array result
 */
function getThirdParty($authentication, $id = '', $ref = '', $ref_ext = '', $barcode = '', $profid1 = '', $profid2 = '')
{
	global $db, $conf;

	dol_syslog("Function: getThirdParty login=".$authentication['login']." id=".$id." ref=".$ref." ref_ext=".$ref_ext." barcode=".$barcode." profid1=".$profid1." profid2=".$profid2);

	if ($authentication['entity']) {
		$conf->entity = $authentication['entity'];
	}

	// Init and check authentication
	$objectresp = array();
	$errorcode = '';
	$errorlabel = '';
	$error = 0;
	$fuser = check_authentication($authentication, $error, $errorcode, $errorlabel);
	// Check parameters
	if (!$error && (($id && $ref) || ($id && $ref_ext) || ($ref && $ref_ext))) {
		$error++;
		$errorcode = 'BAD_PARAMETERS';
		$errorlabel = "Parameter id, ref and ref_ext can't be both provided. You must choose one or other but not both.";
	}

	if (!$error) {
		$fuser->getrights();

		if ($fuser->rights->societe->lire) {
			$thirdparty = new Societe($db);
			$result = $thirdparty->fetch($id, $ref, $ref_ext, $barcode, $profid1, $profid2);
			if ($result > 0) {
				$thirdparty_result_fields = array(
						'id' => $thirdparty->id,
						'ref' => $thirdparty->name,
						'ref_ext' => $thirdparty->ref_ext,
						'status' => $thirdparty->status,
						'client' => $thirdparty->client,
						'supplier' => $thirdparty->fournisseur,
						'customer_code' => $thirdparty->code_client,
						'supplier_code' => $thirdparty->code_fournisseur,
						'customer_code_accountancy' => $thirdparty->code_compta,
						'supplier_code_accountancy' => $thirdparty->code_compta_fournisseur,
						'user_creation' => $thirdparty->user_creation,
						'date_creation' => dol_print_date($thirdparty->date_creation, 'dayhourrfc'),
						'user_modification' => $thirdparty->user_modification,
						'date_modification' => dol_print_date($thirdparty->date_modification, 'dayhourrfc'),
						'address' => $thirdparty->address,
						'zip' => $thirdparty->zip,
						'town' => $thirdparty->town,
						'region_code' => $thirdparty->region_code,
						'country_id' => $thirdparty->country_id,
						'country_code' => $thirdparty->country_code,
						'country' => $thirdparty->country,
						'phone' => $thirdparty->phone,
						'fax' => $thirdparty->fax,
						'email' => $thirdparty->email,
						'url' => $thirdparty->url,
						'profid1' => $thirdparty->idprof1,
						'profid2' => $thirdparty->idprof2,
						'profid3' => $thirdparty->idprof3,
						'profid4' => $thirdparty->idprof4,
						'profid5' => $thirdparty->idprof5,
						'profid6' => $thirdparty->idprof6,
						'capital' => $thirdparty->capital,
						'barcode' => $thirdparty->barcode,
						'vat_used' => $thirdparty->tva_assuj,
						'vat_number' => $thirdparty->tva_intra,
						'note_private' => $thirdparty->note_private,
						'note_public' => $thirdparty->note_public);

				$elementtype = 'societe';

				// Retrieve all extrafields for thirdsparty
				// fetch optionals attributes and labels
				$extrafields = new ExtraFields($db);
				$extrafields->fetch_name_optionals_label($elementtype, true);
				//Get extrafield values
				$thirdparty->fetch_optionals();

				if (isset($extrafields->attributes[$elementtype]['label']) && is_array($extrafields->attributes[$elementtype]['label']) && count($extrafields->attributes[$elementtype]['label'])) {
					foreach ($extrafields->attributes[$elementtype]['label'] as $key => $label) {
						if (isset($thirdparty->array_options['options_'.$key])) {
							$thirdparty_result_fields = array_merge($thirdparty_result_fields, array('options_'.$key => $thirdparty->array_options['options_'.$key]));
						}
					}
				}

				// Create
				$objectresp = array(
					'result'=>array('result_code'=>'OK', 'result_label'=>''),
					'thirdparty'=>$thirdparty_result_fields);
			} elseif ($result == -2) {
				$error++;
				$errorcode = 'DUPLICATE_FOUND'; $errorlabel = 'Object found several times for id='.$id.' or ref='.$ref.' or ref_ext='.$ref_ext;
			} else {
				$error++;
				$errorcode = 'NOT_FOUND'; $errorlabel = 'Object not found for id='.$id.' nor ref='.$ref.' nor ref_ext='.$ref_ext;
			}
		} else {
			$error++;
			$errorcode = 'PERMISSION_DENIED'; $errorlabel = 'User does not have permission for this request';
		}
	}

	if ($error) {
		$objectresp = array('result'=>array('result_code' => $errorcode, 'result_label' => $errorlabel));
	}

	return $objectresp;
}



/**
 * Create a thirdparty
 *
 * @param	array		$authentication		Array of authentication information
 * @param	Societe		$thirdparty		    Thirdparty
 * @return	array							Array result
 */
function createThirdParty($authentication, $thirdparty)
{
	global $db, $conf;

	$now = dol_now();

	dol_syslog("Function: createThirdParty login=".$authentication['login']);

	if ($authentication['entity']) {
		$conf->entity = $authentication['entity'];
	}

	// Init and check authentication
	$objectresp = array();
	$errorcode = ''; $errorlabel = '';
	$error = 0;
	$fuser = check_authentication($authentication, $error, $errorcode, $errorlabel);
	// Check parameters
	if (empty($thirdparty['ref'])) {
		$error++; $errorcode = 'KO'; $errorlabel = "Name is mandatory.";
	}


	if (!$error) {
		include_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';

		$newobject = new Societe($db);
		$newobject->ref = $thirdparty['ref'];
		$newobject->name = $thirdparty['ref'];
		$newobject->ref_ext = $thirdparty['ref_ext'];
		$newobject->status = $thirdparty['status'];
		$newobject->client = $thirdparty['client'];
		$newobject->fournisseur = $thirdparty['supplier'];
		$newobject->code_client = $thirdparty['customer_code'];
		$newobject->code_fournisseur = $thirdparty['supplier_code'];
		$newobject->code_compta = $thirdparty['customer_code_accountancy'];
		$newobject->code_compta_fournisseur = $thirdparty['supplier_code_accountancy'];
		$newobject->date_creation = $now;
		$newobject->note_private = $thirdparty['note_private'];
		$newobject->note_public = $thirdparty['note_public'];
		$newobject->address = $thirdparty['address'];
		$newobject->zip = $thirdparty['zip'];
		$newobject->town = $thirdparty['town'];

		$newobject->country_id = $thirdparty['country_id'];
		if ($thirdparty['country_code']) {
			$newobject->country_id = getCountry($thirdparty['country_code'], 3);
		}
		$newobject->region_code = empty($thirdparty['region_code']) ? '' : $thirdparty['region_code'];
		//if ($thirdparty['province_code']) $newobject->province_code=getCountry($thirdparty['province_code'],3);

		$newobject->phone = $thirdparty['phone'];
		$newobject->fax = $thirdparty['fax'];
		$newobject->email = $thirdparty['email'];
		$newobject->url = $thirdparty['url'];
		$newobject->idprof1 = $thirdparty['profid1'];
		$newobject->idprof2 = $thirdparty['profid2'];
		$newobject->idprof3 = $thirdparty['profid3'];
		$newobject->idprof4 = $thirdparty['profid4'];
		$newobject->idprof5 = $thirdparty['profid5'];
		$newobject->idprof6 = $thirdparty['profid6'];

		$newobject->capital = $thirdparty['capital'];

		$newobject->barcode = empty($thirdparty['barcode']) ? '' : $thirdparty['barcode'];
		$newobject->tva_assuj = empty($thirdparty['vat_used']) ? 0 : $thirdparty['vat_used'];
		$newobject->tva_intra = empty($thirdparty['vat_number']) ? '' : $thirdparty['vat_number'];

		$newobject->canvas = empty($thirdparty['canvas']) ? '' : $thirdparty['canvas'];
		$newobject->particulier = empty($thirdparty['individual']) ? 0 : $thirdparty['individual'];

		$elementtype = 'societe';

		// Retrieve all extrafields for thirdsparty
		// fetch optionals attributes and labels
		$extrafields = new ExtraFields($db);
		$extrafields->fetch_name_optionals_label($elementtype, true);
		if (isset($extrafields->attributes[$elementtype]['label']) && is_array($extrafields->attributes[$elementtype]['label']) && count($extrafields->attributes[$elementtype]['label'])) {
			foreach ($extrafields->attributes[$elementtype]['label'] as $key => $label) {
				$key = 'options_'.$key;
				if (isset($thirdparty[$key])) {
					$newobject->array_options[$key] = $thirdparty[$key];
				}
			}
		}

		$db->begin();

		$result = $newobject->create($fuser);
		if ($newobject->particulier && $result > 0) {
			$newobject->firstname = $thirdparty['firstname'];
			$newobject->name_bis = $thirdparty['lastname'];
			$result = $newobject->create_individual($fuser);
		}
		if ($result <= 0) {
			$error++;
		}

		if (!$error) {
			$db->commit();

			// Patch to add capability to associate (one) sale representative
			if (!empty($thirdparty['commid']) && $thirdparty['commid'] > 0) {
				$newobject->add_commercial($fuser, $thirdparty["commid"]);
			}

			$objectresp = array('result'=>array('result_code'=>'OK', 'result_label'=>''), 'id'=>$newobject->id, 'ref'=>$newobject->ref);
		} else {
			$db->rollback();
			$error++;
			$errorcode = 'KO';
			$errorlabel = $newobject->error;
		}
	}

	if ($error) {
		$objectresp = array('result'=>array('result_code' => $errorcode, 'result_label' => $errorlabel));
	}

	return $objectresp;
}

/**
 * Update a thirdparty
 *
 * @param	array		$authentication		Array of authentication information
 * @param	Societe		$thirdparty		    Thirdparty
 * @return	array							Array result
 */
function updateThirdParty($authentication, $thirdparty)
{
	global $db, $conf;

	$now = dol_now();

	dol_syslog("Function: updateThirdParty login=".$authentication['login']);

	if ($authentication['entity']) {
		$conf->entity = $authentication['entity'];
	}

	// Init and check authentication
	$objectresp = array();
	$errorcode = ''; $errorlabel = '';
	$error = 0;
	$fuser = check_authentication($authentication, $error, $errorcode, $errorlabel);
	// Check parameters
	if (empty($thirdparty['id'])) {
		$error++; $errorcode = 'KO'; $errorlabel = "Thirdparty id is mandatory.";
	}

	if (!$error) {
		$objectfound = false;

		include_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';

		$object = new Societe($db);
		$result = $object->fetch($thirdparty['id']);

		if (!empty($object->id)) {
			$objectfound = true;

			$object->ref = $thirdparty['ref'];
			$object->name = $thirdparty['ref'];
			$object->ref_ext = $thirdparty['ref_ext'];
			$object->status = $thirdparty['status'];
			$object->client = $thirdparty['client'];
			$object->fournisseur = $thirdparty['supplier'];
			$object->code_client = $thirdparty['customer_code'];
			$object->code_fournisseur = $thirdparty['supplier_code'];
			$object->code_compta = $thirdparty['customer_code_accountancy'];
			$object->code_compta_fournisseur = $thirdparty['supplier_code_accountancy'];
			$object->date_creation = $now;
			$object->note_private = $thirdparty['note_private'];
			$object->note_public = $thirdparty['note_public'];
			$object->address = $thirdparty['address'];
			$object->zip = $thirdparty['zip'];
			$object->town = $thirdparty['town'];

			$object->country_id = $thirdparty['country_id'];
			if ($thirdparty['country_code']) {
				$object->country_id = getCountry($thirdparty['country_code'], 3);
			}
			$object->region_code = $thirdparty['region_code'];
			//if ($thirdparty['province_code']) $newobject->province_code=getCountry($thirdparty['province_code'],3);

			$object->phone = $thirdparty['phone'];
			$object->fax = $thirdparty['fax'];
			$object->email = $thirdparty['email'];
			$object->url = $thirdparty['url'];
			$object->idprof1 = $thirdparty['profid1'];
			$object->idprof2 = $thirdparty['profid2'];
			$object->idprof3 = $thirdparty['profid3'];
			$object->idprof4 = $thirdparty['profid4'];
			$object->idprof5 = $thirdparty['profid5'];
			$object->idprof6 = $thirdparty['profid6'];

			$object->capital = $thirdparty['capital'];

			$object->barcode = $thirdparty['barcode'];
			$object->tva_assuj = $thirdparty['vat_used'];
			$object->tva_intra = $thirdparty['vat_number'];

			$object->canvas = $thirdparty['canvas'];

			$elementtype = 'societe';

			// Retrieve all extrafields for thirdsparty
			// fetch optionals attributes and labels
			$extrafields = new ExtraFields($db);
			$extrafields->fetch_name_optionals_label($elementtype, true);
			if (isset($extrafields->attributes[$elementtype]['label']) && is_array($extrafields->attributes[$elementtype]['label']) && count($extrafields->attributes[$elementtype]['label'])) {
				foreach ($extrafields->attributes[$elementtype]['label'] as $key => $label) {
					$key = 'options_'.$key;
					if (isset($thirdparty[$key])) {
						$object->array_options[$key] = $thirdparty[$key];
					}
				}
			}

			$db->begin();

			$result = $object->update($thirdparty['id'], $fuser);
			if ($result <= 0) {
				$error++;
			}
		}

		if ((!$error) && ($objectfound)) {
			$db->commit();
			$objectresp = array(
					'result'=>array('result_code'=>'OK', 'result_label'=>''),
					'id'=>$object->id
			);
		} elseif ($objectfound) {
			$db->rollback();
			$error++;
			$errorcode = 'KO';
			$errorlabel = $object->error;
		} else {
			$error++;
			$errorcode = 'NOT_FOUND';
			$errorlabel = 'Thirdparty id='.$thirdparty['id'].' cannot be found';
		}
	}

	if ($error) {
		$objectresp = array('result'=>array('result_code' => $errorcode, 'result_label' => $errorlabel));
	}

	return $objectresp;
}



/**
 * getListOfThirdParties
 *
 * @param	array		$authentication		Array of authentication information
 * @param	array		$filterthirdparty	Filter fields (key=>value to filer on. For example 'client'=>2, 'supplier'=>1, 'category'=>idcateg, 'name'=>'searchstring', ...)
 * @return	array							Array result
 */
function getListOfThirdParties($authentication, $filterthirdparty)
{
	global $db, $conf;

	dol_syslog("Function: getListOfThirdParties login=".$authentication['login']);

	if ($authentication['entity']) {
		$conf->entity = $authentication['entity'];
	}

	// Init and check authentication
	$objectresp = array();
	$arraythirdparties = array();

	$errorcode = ''; $errorlabel = '';
	$error = 0;
	$fuser = check_authentication($authentication, $error, $errorcode, $errorlabel);
	// Check parameters

	if (!$error) {
		$sql  = "SELECT s.rowid as socRowid, s.nom as ref, s.ref_ext, s.address, s.zip, s.town, c.label as country, s.phone, s.fax, s.url, extra.*";
		$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_country as c ON s.fk_pays = c.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe_extrafields as extra ON s.rowid=fk_object";

		$sql .= " WHERE entity=".$conf->entity;
		foreach ($filterthirdparty as $key => $val) {
			if ($key == 'name' && $val != '') {
				$sql .= " AND s.name LIKE '%".$db->escape($val)."%'";
			}
			if ($key == 'client' && (int) $val > 0) {
				$sql .= " AND s.client = ".((int) $val);
			}
			if ($key == 'supplier' && (int) $val > 0) {
				$sql .= " AND s.fournisseur = ".((int) $val);
			}
			if ($key == 'category' && (int) $val > 0) {
				$sql .= " AND s.rowid IN (SELECT fk_soc FROM ".MAIN_DB_PREFIX."categorie_societe WHERE fk_categorie = ".((int) $val).") ";
			}
		}
		dol_syslog("Function: getListOfThirdParties", LOG_DEBUG);

		$elementtype = 'societe';

		$extrafields = new ExtraFields($db);
		$extrafields->fetch_name_optionals_label($elementtype, true);


		$resql = $db->query($sql);
		if ($resql) {
			$num = $db->num_rows($resql);

			$i = 0;
			while ($i < $num) {
				$extrafieldsOptions = array();
				$obj = $db->fetch_object($resql);

				if (isset($extrafields->attributes[$elementtype]['label']) && is_array($extrafields->attributes[$elementtype]['label']) && count($extrafields->attributes[$elementtype]['label'])) {
					foreach ($extrafields->attributes[$elementtype]['label'] as $key => $label) {
						if (isset($obj->{$key})) {
							$extrafieldsOptions['options_'.$key] = $obj->{$key};
						}
					}
				}

				$arraythirdparties[] = array('id'=>$obj->socRowid,
					'ref'=>$obj->ref,
					'ref_ext'=>$obj->ref_ext,
					'adress'=>$obj->adress,
					'zip'=>$obj->zip,
					'town'=>$obj->town,
					'country'=>$obj->country,
					'phone'=>$obj->phone,
					'fax'=>$obj->fax,
					'url'=>$obj->url
				);
				$arraythirdparties[$i] = array_merge($arraythirdparties[$i], $extrafieldsOptions);

				$i++;
			}
		} else {
			$error++;
			$errorcode = $db->lasterrno();
			$errorlabel = $db->lasterror();
		}
	}

	if ($error) {
		$objectresp = array(
			'result'=>array('result_code' => $errorcode, 'result_label' => $errorlabel),
			'thirdparties'=>$arraythirdparties
		);
	} else {
		$objectresp = array(
			'result'=>array('result_code' => 'OK', 'result_label' => ''),
			'thirdparties'=>$arraythirdparties
		);
	}

	return $objectresp;
}

/**
 * Delete a thirdparty
 *
 * @param	array		$authentication		Array of authentication information
 * @param	string		$id		    		internal id
 * @param	string		$ref		    	internal reference
 * @param	string		$ref_ext	   		external reference
 * @return	array							Array result
 */
function deleteThirdParty($authentication, $id = '', $ref = '', $ref_ext = '')
{
	global $db, $conf;

	dol_syslog("Function: deleteThirdParty login=".$authentication['login']." id=".$id." ref=".$ref." ref_ext=".$ref_ext);

	if ($authentication['entity']) {
		$conf->entity = $authentication['entity'];
	}

	// Init and check authentication
	$objectresp = array();
	$errorcode = ''; $errorlabel = '';
	$error = 0;
	$fuser = check_authentication($authentication, $error, $errorcode, $errorlabel);
	// Check parameters
	if (!$error && (($id && $ref) || ($id && $ref_ext) || ($ref && $ref_ext))) {
		dol_syslog("Function: deleteThirdParty checkparam");
		$error++;
		$errorcode = 'BAD_PARAMETERS'; $errorlabel = "Parameter id, ref and ref_ext can't be both provided. You must choose one or other but not both.";
	}
	dol_syslog("Function: deleteThirdParty 1");

	if (!$error) {
		$fuser->getrights();

		if ($fuser->rights->societe->lire && $fuser->rights->societe->supprimer) {
			$thirdparty = new Societe($db);
			$result = $thirdparty->fetch($id, $ref, $ref_ext);

			if ($result > 0) {
				$db->begin();

				$result = $thirdparty->delete($thirdparty->id, $fuser);

				if ($result > 0) {
					$db->commit();

					$objectresp = array('result'=>array('result_code'=>'OK', 'result_label'=>''));
				} else {
					$db->rollback();
					$error++;
					$errorcode = 'KO';
					$errorlabel = $thirdparty->error;
					dol_syslog("Function: deleteThirdParty cant delete");
				}
			} else {
				$error++;
				$errorcode = 'NOT_FOUND'; $errorlabel = 'Object not found for id='.$id.' nor ref='.$ref.' nor ref_ext='.$ref_ext;
			}
		} else {
			$error++;
			$errorcode = 'PERMISSION_DENIED'; $errorlabel = 'User does not have permission for this request';
		}
	}

	if ($error) {
		$objectresp = array('result'=>array('result_code' => $errorcode, 'result_label' => $errorlabel));
	}

	return $objectresp;
}

// Return the results.
$server->service(file_get_contents("php://input"));
