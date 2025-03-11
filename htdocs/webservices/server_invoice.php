<?php
/* Copyright (C) 2006-2016 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2016       Juanjo Menent       <jmenent@2byte.es>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
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
 *       \file       htdocs/webservices/server_invoice.php
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
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/ws.lib.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';

require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';

/**
 * @var DoliDB $db
 * @var Translate $langs
 */

dol_syslog("Call Dolibarr webservices interfaces");

$langs->load("main");

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
$server->configureWSDL('WebServicesDolibarrInvoice', $ns);
$server->wsdl->schemaTargetNamespace = $ns;


// Define WSDL Authentication object
$server->wsdl->addComplexType(
	'authentication',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'dolibarrkey' => array('name' => 'dolibarrkey', 'type' => 'xsd:string'),
		'sourceapplication' => array('name' => 'sourceapplication', 'type' => 'xsd:string'),
		'login' => array('name' => 'login', 'type' => 'xsd:string'),
		'password' => array('name' => 'password', 'type' => 'xsd:string'),
		'entity' => array('name' => 'entity', 'type' => 'xsd:string')
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
		'result_code' => array('name' => 'result_code', 'type' => 'xsd:string'),
		'result_label' => array('name' => 'result_label', 'type' => 'xsd:string'),
	)
);

// Define other specific objects
$server->wsdl->addComplexType(
	'line',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'id' => array('name' => 'id', 'type' => 'xsd:string'),
		'type' => array('name' => 'type', 'type' => 'xsd:int'),
		'desc' => array('name' => 'desc', 'type' => 'xsd:string'),
		'vat_rate' => array('name' => 'vat_rate', 'type' => 'xsd:double'),
		'qty' => array('name' => 'qty', 'type' => 'xsd:double'),
		'unitprice' => array('name' => 'unitprice', 'type' => 'xsd:double'),
		'total_net' => array('name' => 'total_net', 'type' => 'xsd:double'),
		'total_vat' => array('name' => 'total_vat', 'type' => 'xsd:double'),
		'total' => array('name' => 'total', 'type' => 'xsd:double'),
		'date_start' => array('name' => 'date_start', 'type' => 'xsd:date'),
		'date_end' => array('name' => 'date_end', 'type' => 'xsd:date'),
		// From product
		'product_id' => array('name' => 'product_id', 'type' => 'xsd:int'),
		'product_ref' => array('name' => 'product_ref', 'type' => 'xsd:string'),
		'product_label' => array('name' => 'product_label', 'type' => 'xsd:string'),
		'product_desc' => array('name' => 'product_desc', 'type' => 'xsd:string')
	)
);

/*$server->wsdl->addComplexType(
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
);*/
$server->wsdl->addComplexType(
	'LinesArray2',
	'complexType',
	'array',
	'sequence',
	'',
	array(
		'line' => array(
			'name' => 'line',
			'type' => 'tns:line',
			'minOccurs' => '0',
			'maxOccurs' => 'unbounded'
		)
	),
	array(),
	'tns:line'
);


$server->wsdl->addComplexType(
	'invoice',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'id' => array('name' => 'id', 'type' => 'xsd:string'),
		'ref' => array('name' => 'ref', 'type' => 'xsd:string'),
		'ref_ext' => array('name' => 'ref_ext', 'type' => 'xsd:string'),
		'thirdparty_id' => array('name' => 'thirdparty_id', 'type' => 'xsd:int'),
		'fk_user_author' => array('name' => 'fk_user_author', 'type' => 'xsd:string'),
		'fk_user_valid' => array('name' => 'fk_user_valid', 'type' => 'xsd:string'),
		'date' => array('name' => 'date', 'type' => 'xsd:date'),
		'date_due' => array('name' => 'date_due', 'type' => 'xsd:date'),
		'date_creation' => array('name' => 'date_creation', 'type' => 'xsd:dateTime'),
		'date_validation' => array('name' => 'date_validation', 'type' => 'xsd:dateTime'),
		'date_modification' => array('name' => 'date_modification', 'type' => 'xsd:dateTime'),
		'payment_mode_id' => array('name' => 'payment_mode_id', 'type' => 'xsd:string'),
		'type' => array('name' => 'type', 'type' => 'xsd:int'),
		'total_net' => array('name' => 'type', 'type' => 'xsd:double'),
		'total_vat' => array('name' => 'type', 'type' => 'xsd:double'),
		'total' => array('name' => 'type', 'type' => 'xsd:double'),
		'note_private' => array('name' => 'note_private', 'type' => 'xsd:string'),
		'note_public' => array('name' => 'note_public', 'type' => 'xsd:string'),
		'status' => array('name' => 'status', 'type' => 'xsd:int'),
		'close_code' => array('name' => 'close_code', 'type' => 'xsd:string'),
		'close_note' => array('name' => 'close_note', 'type' => 'xsd:string'),
		'project_id' => array('name' => 'project_id', 'type' => 'xsd:string'),
		'lines' => array('name' => 'lines', 'type' => 'tns:LinesArray2')
	)
);
/*
$server->wsdl->addComplexType(
	'InvoicesArray',
	'complexType',
	'array',
	'',
	'SOAP-ENC:Array',
	array(),
	array(
		array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'tns:invoice[]')
	),
	'tns:invoice'
);*/
$server->wsdl->addComplexType(
	'InvoicesArray2',
	'complexType',
	'array',
	'sequence',
	'',
	array(
		'invoice' => array(
			'name' => 'invoice',
			'type' => 'tns:invoice',
			'minOccurs' => '0',
			'maxOccurs' => 'unbounded'
		)
	),
	array(),
	'tns:invoice'
);



// 5 styles: RPC/encoded, RPC/literal, Document/encoded (not WS-I compliant), Document/literal, Document/literal wrapped
// Style merely dictates how to translate a WSDL binding to a SOAP message. Nothing more. You can use either style with any programming model.
// http://www.ibm.com/developerworks/webservices/library/ws-whichwsdl/
$styledoc = 'rpc'; // rpc/document (document is an extend into SOAP 1.0 to support unstructured messages)
$styleuse = 'encoded'; // encoded/literal/literal wrapped
// Better choice is document/literal wrapped but literal wrapped not supported by nusoap.

// Register WSDL
$server->register(
	'getInvoice',
	// Entry values
	array('authentication' => 'tns:authentication', 'id' => 'xsd:string', 'ref' => 'xsd:string', 'ref_ext' => 'xsd:string'),
	// Exit values
	array('result' => 'tns:result', 'invoice' => 'tns:invoice'),
	$ns,
	$ns.'#getInvoice',
	$styledoc,
	$styleuse,
	'WS to get a particular invoice'
);
$server->register(
	'getInvoicesForThirdParty',
	// Entry values
	array('authentication' => 'tns:authentication', 'idthirdparty' => 'xsd:string'),
	// Exit values
	array('result' => 'tns:result', 'invoices' => 'tns:InvoicesArray2'),
	$ns,
	$ns.'#getInvoicesForThirdParty',
	$styledoc,
	$styleuse,
	'WS to get all invoices of a third party'
);
$server->register(
	'createInvoice',
	// Entry values
	array('authentication' => 'tns:authentication', 'invoice' => 'tns:invoice'),
	// Exit values
	array('result' => 'tns:result', 'id' => 'xsd:string', 'ref' => 'xsd:string', 'ref_ext' => 'xsd:string'),
	$ns,
	$ns.'#createInvoice',
	$styledoc,
	$styleuse,
	'WS to create an invoice'
);
$server->register(
	'createInvoiceFromOrder',
	// Entry values
	array('authentication' => 'tns:authentication', 'id_order' => 'xsd:string', 'ref_order' => 'xsd:string', 'ref_ext_order' => 'xsd:string'),
	// Exit values
	array('result' => 'tns:result', 'id' => 'xsd:string', 'ref' => 'xsd:string', 'ref_ext' => 'xsd:string'),
	$ns,
	$ns.'#createInvoiceFromOrder',
	$styledoc,
	$styleuse,
	'WS to create an invoice from an order'
);
$server->register(
	'updateInvoice',
	// Entry values
	array('authentication' => 'tns:authentication', 'invoice' => 'tns:invoice'),
	// Exit values
	array('result' => 'tns:result', 'id' => 'xsd:string', 'ref' => 'xsd:string', 'ref_ext' => 'xsd:string'),
	$ns,
	$ns.'#updateInvoice',
	$styledoc,
	$styleuse,
	'WS to update an invoice'
);


/**
 * Get invoice from id, ref or ref_ext.
 *
 * @param	array{login:string,password:string,entity:?int,dolibarrkey:string}		$authentication		Array of authentication information
 * @param	int			$id					Id
 * @param	string		$ref				Ref
 * @param	string		$ref_ext			Ref_ext
 * @return array{result:array{result_code:string,result_label:string}} Array result
 */
function getInvoice($authentication, $id = 0, $ref = '', $ref_ext = '')
{
	global $db, $conf;

	dol_syslog("Function: getInvoice login=".$authentication['login']." id=".$id." ref=".$ref." ref_ext=".$ref_ext);

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
		$fuser->loadRights();

		if ($fuser->hasRight('facture', 'lire')) {
			$invoice = new Facture($db);
			$result = $invoice->fetch($id, $ref, $ref_ext);
			if ($result > 0) {
				$linesresp = array();
				$i = 0;
				foreach ($invoice->lines as $line) {
					//var_dump($line); exit;
					$linesresp[] = array(
						'id' => $line->id,
						'type' => $line->product_type,
												'desc' => dol_htmlcleanlastbr($line->desc),
												'total_net' => $line->total_ht,
												'total_vat' => $line->total_tva,
												'total' => $line->total_ttc,
												'vat_rate' => $line->tva_tx,
												'qty' => $line->qty,
												'unitprice' => $line->subprice,
												'date_start' => $line->date_start ? dol_print_date($line->date_start, 'dayrfc') : '',
												'date_end' => $line->date_end ? dol_print_date($line->date_end, 'dayrfc') : '',
												'product_id' => $line->fk_product,
												'product_ref' => $line->product_ref,
												'product_label' => $line->product_label,
												'product_desc' => $line->product_desc,
					);
					$i++;
				}

				// Create invoice
				$objectresp = array(
					'result' => array('result_code' => 'OK', 'result_label' => ''),
					'invoice' => array(
						'id' => $invoice->id,
						'ref' => $invoice->ref,
						'ref_ext' => $invoice->ref_ext ? $invoice->ref_ext : '', // If not defined, field is not added into soap
						'thirdparty_id' => $invoice->socid,
						'fk_user_author' => $invoice->fk_user_author ? $invoice->fk_user_author : '',
						'fk_user_valid' => $invoice->user_validation_id ? $invoice->user_validation_id : '',
						'date' => $invoice->date ? dol_print_date($invoice->date, 'dayrfc') : '',
						'date_due' => $invoice->date_lim_reglement ? dol_print_date($invoice->date_lim_reglement, 'dayrfc') : '',
						'date_creation' => $invoice->date_creation ? dol_print_date($invoice->date_creation, 'dayhourrfc') : '',
						'date_validation' => $invoice->date_validation ? dol_print_date($invoice->date_creation, 'dayhourrfc') : '',
						'date_modification' => $invoice->datem ? dol_print_date($invoice->datem, 'dayhourrfc') : '',
						'type' => $invoice->type,
						'total_net' => $invoice->total_ht,
						'total_vat' => $invoice->total_tva,
						'total' => $invoice->total_ttc,
						'note_private' => $invoice->note_private ? $invoice->note_private : '',
						'note_public' => $invoice->note_public ? $invoice->note_public : '',
						'status' => $invoice->statut,
						'project_id' => $invoice->fk_project,
						'close_code' => $invoice->close_code ? $invoice->close_code : '',
						'close_note' => $invoice->close_note ? $invoice->close_note : '',
						'payment_mode_id' => $invoice->mode_reglement_id ? $invoice->mode_reglement_id : '',
						'lines' => $linesresp
					));
			} else {
				$error++;
				$errorcode = 'NOT_FOUND';
				$errorlabel = 'Object not found for id='.$id.' nor ref='.$ref.' nor ref_ext='.$ref_ext;
			}
		} else {
			$error++;
			$errorcode = 'PERMISSION_DENIED';
			$errorlabel = 'User does not have permission for this request';
		}
	}

	if ($error) {
		$objectresp = array('result' => array('result_code' => $errorcode, 'result_label' => $errorlabel));
	}

	return $objectresp;
}


/**
 * Get list of invoices for third party
 *
 * @param	array{login:string,password:string,entity:?int,dolibarrkey:string}		$authentication		Array of authentication information
 * @param	int			$idthirdparty		Id thirdparty
 * @return array{result:array{result_code:string,result_label:string}} Array result
 */
function getInvoicesForThirdParty($authentication, $idthirdparty)
{
	global $db, $conf;

	dol_syslog("Function: getInvoicesForThirdParty login=".$authentication['login']." idthirdparty=".$idthirdparty);

	if ($authentication['entity']) {
		$conf->entity = $authentication['entity'];
	}

	// Init and check authentication
	$objectresp = array();
	$errorcode = '';
	$errorlabel = '';
	$error = 0;
	$socid = 0;
	$fuser = check_authentication($authentication, $error, $errorcode, $errorlabel);

	if ($fuser->socid) {
		$socid = $fuser->socid;
	}

	// Check parameters
	if (!$error && empty($idthirdparty)) {
		$error++;
		$errorcode = 'BAD_PARAMETERS';
		$errorlabel = 'Parameter idthirdparty is not provided';
	}

	if (!$error) {
		$linesinvoice = array();

		$sql = 'SELECT f.rowid as facid, ref as ref, ref_ext, type, fk_statut as status, total_ttc, total, tva';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'facture as f';
		$sql .= " WHERE f.entity IN (".getEntity('invoice').")";
		if ($idthirdparty != 'all') {
			$sql .= " AND f.fk_soc = ".((int) $idthirdparty);
		}

		$resql = $db->query($sql);
		if ($resql) {
			$num = $db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				// En attendant remplissage par boucle
				$obj = $db->fetch_object($resql);

				$invoice = new Facture($db);
				$invoice->fetch($obj->facid);

				// Sécurité pour utilisateur externe
				if ($socid && ($socid != $invoice->socid)) {
					$error++;
					$errorcode = 'PERMISSION_DENIED';
					$errorlabel = $invoice->socid.' User does not have permission for this request';
				}

				if (!$error) {
					// Define lines of invoice
					$linesresp = array();
					foreach ($invoice->lines as $line) {
						$linesresp[] = array(
							'id' => $line->id,
							'type' => $line->product_type,
							'total_net' => $line->total_ht,
							'total_vat' => $line->total_tva,
							'total' => $line->total_ttc,
							'vat_rate' => $line->tva_tx,
							'qty' => $line->qty,
							'unitprice' => $line->subprice,
							'date_start' => $line->date_start ? dol_print_date($line->date_start, 'dayrfc') : '',
							'date_end' => $line->date_end ? dol_print_date($line->date_end, 'dayrfc') : '',
							'product_id' => $line->fk_product,
							'product_ref' => $line->product_ref,
							'product_label' => $line->product_label,
							'product_desc' => $line->product_desc,
						);
					}

					// Now define invoice
					$linesinvoice[] = array(
						'id' => $invoice->id,
						'ref' => $invoice->ref,
						'ref_ext' => $invoice->ref_ext ? $invoice->ref_ext : '', // If not defined, field is not added into soap
						'fk_user_author' => $invoice->fk_user_author ? $invoice->fk_user_author : '',
						'fk_user_valid' => $invoice->user_validation_id ? $invoice->user_validation_id : '',
						'date' => $invoice->date ? dol_print_date($invoice->date, 'dayrfc') : '',
						'date_due' => $invoice->date_lim_reglement ? dol_print_date($invoice->date_lim_reglement, 'dayrfc') : '',
						'date_creation' => $invoice->date_creation ? dol_print_date($invoice->date_creation, 'dayhourrfc') : '',
						'date_validation' => $invoice->date_validation ? dol_print_date($invoice->date_creation, 'dayhourrfc') : '',
						'date_modification' => $invoice->datem ? dol_print_date($invoice->datem, 'dayhourrfc') : '',
						'type' => $invoice->type,
						'total_net' => $invoice->total_ht,
						'total_vat' => $invoice->total_tva,
						'total' => $invoice->total_ttc,
						'note_private' => $invoice->note_private ? $invoice->note_private : '',
						'note_public' => $invoice->note_public ? $invoice->note_public : '',
						'status' => $invoice->statut,
						'project_id' => $invoice->fk_project,
						'close_code' => $invoice->close_code ? $invoice->close_code : '',
						'close_note' => $invoice->close_note ? $invoice->close_note : '',
						'payment_mode_id' => $invoice->mode_reglement_id ? $invoice->mode_reglement_id : '',
						'lines' => $linesresp
					);
				}

				$i++;
			}

			$objectresp = array(
				'result' => array('result_code' => 'OK', 'result_label' => ''),
				'invoices' => $linesinvoice

			);
		} else {
			$error++;
			$errorcode = $db->lasterrno();
			$errorlabel = $db->lasterror();
		}
	}

	if ($error) {
		$objectresp = array('result' => array('result_code' => $errorcode, 'result_label' => $errorlabel));
	}

	return $objectresp;
}


/**
 * Create an invoice
 *
 * @param	array{login:string,password:string,entity:?int,dolibarrkey:string}		$authentication		Array of authentication information
 * @param	array{id:string,ref:string,ref_ext:string,thirdparty_id:int,fk_user_author:string,fk_user_valid:string,date:string,date_due:string,date_creation:string,date_validation:string,date_modification:string,payment_mode_id:string,type:int,total_net:float,total_vat:float,total:float,note_private:string,note_public:string,status:int,close_code:string,close_note:string,project_id:string,lines?:array{id:string,type:int,desc:string,vat_rate:float,qty:float,unitprice:float,total_net:float,total_vat:float,total:float,date_start:string,date_end:string,product_id:int,product_ref:string,product_label:string,product_desc:string}}		$invoice			Invoice
 * @return array{result:array{result_code:string,result_label:string}} Array result
 */
function createInvoice($authentication, $invoice)
{
	global $db, $conf;

	$now = dol_now();

	dol_syslog("Function: createInvoice login=".$authentication['login']." id=".$invoice['id'].", ref=".$invoice['ref'].", ref_ext=".$invoice['ref_ext']);
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
	if (empty($invoice['id']) && empty($invoice['ref']) && empty($invoice['ref_ext'])) {
		$error++;
		$errorcode = 'KO';
		$errorlabel = "Invoice id or ref or ref_ext is mandatory.";
	}

	if (!$error) {
		$new_invoice = new Facture($db);
		$new_invoice->socid = $invoice['thirdparty_id'];
		$new_invoice->type = $invoice['type'];
		$new_invoice->ref_ext = $invoice['ref_ext'];
		$new_invoice->date = dol_stringtotime($invoice['date'], 'dayrfc');
		$new_invoice->note_private = $invoice['note_private'];
		$new_invoice->note_public = $invoice['note_public'];
		$new_invoice->statut = Facture::STATUS_DRAFT; // We start with status draft
		$new_invoice->fk_project = (int) $invoice['project_id'];
		$new_invoice->date_creation = $now;

		//take mode_reglement and cond_reglement from thirdparty
		$soc = new Societe($db);
		$res = $soc->fetch($new_invoice->socid);
		if ($res > 0) {
			$new_invoice->mode_reglement_id = !empty($invoice['payment_mode_id']) ? $invoice['payment_mode_id'] : $soc->mode_reglement_id;
			$new_invoice->cond_reglement_id = $soc->cond_reglement_id;
		} else {
			$new_invoice->mode_reglement_id = (int) $invoice['payment_mode_id'];
		}

		// Trick because nusoap does not store data with same structure if there is one or several lines
		$arrayoflines = array();
		if (isset($invoice['lines']['line'][0])) {
			$arrayoflines = $invoice['lines']['line']; // @phan-suppress-current-line PhanTypeInvalidDimOffset
		} else {
			$arrayoflines = $invoice['lines'];
		}
		if (!is_array($arrayoflines)) {
			$arrayoflines = array();
		}

		foreach ($arrayoflines as $line) {
			// $key can be 'line' or '0','1',...
			$newline = new FactureLigne($db);
			$newline->product_type = $line['type'];
			$newline->desc = $line['desc'];
			$newline->fk_product = $line['product_id'];
			$newline->tva_tx = isset($line['vat_rate']) ? $line['vat_rate'] : 0;
			$newline->qty = $line['qty'];
			$newline->subprice = isset($line['unitprice']) ? $line['unitprice'] : null;
			$newline->total_ht = $line['total_net'];
			$newline->total_tva = $line['total_vat'];
			$newline->total_ttc = $line['total'];
			$newline->date_start = dol_stringtotime($line['date_start']);
			$newline->date_end = dol_stringtotime($line['date_end']);

			$new_invoice->lines[] = $newline;
		}
		//var_dump($newobject->date_lim_reglement); exit;
		//var_dump($invoice['lines'][0]['type']);

		$db->begin();

		$result = $new_invoice->create($fuser, 0, dol_stringtotime($invoice['date_due'], 'dayrfc'));
		if ($result < 0) {
			$error++;
		}

		if (!$error && $invoice['status'] == Facture::STATUS_VALIDATED) {   // We want invoice to have status validated
			$result = $new_invoice->validate($fuser);
			if ($result < 0) {
				$error++;
			}
		}

		if (!$error) {
			$db->commit();
			$objectresp = array('result' => array('result_code' => 'OK', 'result_label' => ''), 'id' => $new_invoice->id,
					'ref' => $new_invoice->ref, 'ref_ext' => $new_invoice->ref_ext);
		} else {
			$db->rollback();
			$error++;
			$errorcode = 'KO';
			$errorlabel = $new_invoice->error;
			dol_syslog("Function: createInvoice error while creating".$errorlabel);
		}
	}

	if ($error) {
		$objectresp = array('result' => array('result_code' => $errorcode, 'result_label' => $errorlabel));
	}

	return $objectresp;
}

/**
 * Create an invoice from an order
 *
 * @param	array{login:string,password:string,entity:?int,dolibarrkey:string}	$authentication		Array of authentication information
 * @param	string      $id_order			id of order to copy invoice from
 * @param	string      $ref_order			ref of order to copy invoice from
 * @param	string      $ref_ext_order		ref_ext of order to copy invoice from
 * @return	array{result:array{result_code:string,result_label:string},id?:int,ref?:string,ref_ext?:string}	Array result
 */
function createInvoiceFromOrder($authentication, $id_order = '', $ref_order = '', $ref_ext_order = '')
{
	global $db, $conf;

	dol_syslog("Function: createInvoiceFromOrder login=".$authentication['login']." id=".$id_order.", ref=".$ref_order.", ref_ext=".$ref_ext_order);

	if ($authentication['entity']) {
		$conf->entity = $authentication['entity'];
	}

	// Init and check authentication
	$objectresp = array();
	$errorcode = '';
	$errorlabel = '';
	$error = 0;
	$newobject = null;
	$socid = 0;
	$fuser = check_authentication($authentication, $error, $errorcode, $errorlabel);
	if ($fuser->socid) {
		$socid = $fuser->socid;
	}

	// Check parameters
	if (empty($id_order) && empty($ref_order) && empty($ref_ext_order)) {
		$error++;
		$errorcode = 'KO';
		$errorlabel = "order id or ref or ref_ext is mandatory.";
	}

	//////////////////////
	if (!$error) {
		$fuser->loadRights();

		if ($fuser->hasRight('commande', 'lire')) {
			$order = new Commande($db);
			$result = $order->fetch($id_order, $ref_order, $ref_ext_order);
			if ($result > 0) {
				// Security for external user
				if ($socid && ($socid != $order->socid)) {
					$error++;
					$errorcode = 'PERMISSION_DENIED';
					$errorlabel = $order->socid.'User does not have permission for this request';
				}

				if (!$error) {
					$newobject = new Facture($db);
					$result = $newobject->createFromOrder($order, $fuser);

					if ($result < 0) {
						$error++;
						dol_syslog("Webservice server_invoice:: invoice creation from order failed", LOG_ERR);
					}
				}
			} else {
				$error++;
				$errorcode = 'NOT_FOUND';
				$errorlabel = 'Object not found for id='.$id_order.' nor ref='.$ref_order.' nor ref_ext='.$ref_ext_order;
			}
		} else {
			$error++;
			$errorcode = 'PERMISSION_DENIED';
			$errorlabel = 'User does not have permission for this request';
		}
	}

	if ($error || $newobject === null) {
		$objectresp = array('result' => array('result_code' => $errorcode, 'result_label' => $errorlabel));
	} else {
		$objectresp = array('result' => array('result_code' => 'OK', 'result_label' => ''), 'id' => $newobject->id, 'ref' => $newobject->ref, 'ref_ext' => $newobject->ref_ext);
	}

	return $objectresp;
}

/**
 * Update an invoice, only change the state of an invoice
 *
 * @param	array{login:string,password:string,entity:?int,dolibarrkey:string}	$authentication		Array of authentication information
 * @param	array{id:string,ref:string,ref_ext:string,status?:string,close_code?:int,close_note?:int}	$invoice			Invoice
 * @return	array{result:array{result_code:string,result_label:string},id?:int,ref?:string,ref_ext?:string}	Array result
 */
function updateInvoice($authentication, $invoice)
{
	global $db, $conf, $langs;

	dol_syslog("Function: updateInvoice login=".$authentication['login']." id=".$invoice['id'].
			", ref=".$invoice['ref'].", ref_ext=".$invoice['ref_ext']);

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
	if (empty($invoice['id']) && empty($invoice['ref']) && empty($invoice['ref_ext'])) {
		$error++;
		$errorcode = 'KO';
		$errorlabel = "Invoice id or ref or ref_ext is mandatory.";
	}

	if (!$error) {
		$objectfound = false;

		$object = new Facture($db);
		$result = $object->fetch($invoice['id'], $invoice['ref'], $invoice['ref_ext'], 0);

		if (!empty($object->id)) {
			$objectfound = true;

			$db->begin();

			if (isset($invoice['status'])) {
				if ($invoice['status'] == Facture::STATUS_DRAFT) {
					$result = $object->setDraft($fuser);
				}
				if ($invoice['status'] == Facture::STATUS_VALIDATED) {
					$result = $object->validate($fuser);

					if ($result >= 0) {
						// Define output language
						$outputlangs = $langs;
						$object->generateDocument($object->model_pdf, $outputlangs);
					}
				}
				if ($invoice['status'] == Facture::STATUS_CLOSED) {
					$result = $object->setPaid($fuser, $invoice['close_code'], $invoice['close_note']);
				}
				if ($invoice['status'] == Facture::STATUS_ABANDONED) {
					$result = $object->setCanceled($fuser, $invoice['close_code'], $invoice['close_note']);
				}
			}
		}

		if ((!$error) && ($objectfound)) {
			$db->commit();
			$objectresp = array(
					'result' => array('result_code' => 'OK', 'result_label' => ''),
					'id' => $object->id,
					'ref' => $object->ref,
					'ref_ext' => $object->ref_ext
			);
		} elseif ($objectfound) {
			$db->rollback();
			$error++;
			$errorcode = 'KO';
			$errorlabel = $object->error;
		} else {
			$error++;
			$errorcode = 'NOT_FOUND';
			$errorlabel = 'Invoice id='.$invoice['id'].' ref='.$invoice['ref'].' ref_ext='.$invoice['ref_ext'].' cannot be found';
		}
	}

	if ($error) {
		$objectresp = array('result' => array('result_code' => $errorcode, 'result_label' => $errorlabel));
	}

	return $objectresp;
}

// Return the results.
$server->service(file_get_contents("php://input"));
