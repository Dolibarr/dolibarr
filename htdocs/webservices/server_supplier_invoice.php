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
 *       \file       htdocs/webservices/server_supplier_invoice.php
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
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';


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
$server->configureWSDL('WebServicesDolibarrSupplierInvoice', $ns);
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

// Define other specific objects
$server->wsdl->addComplexType(
	'line',
	'element',
	'struct',
	'all',
	'',
	array(
		'id' => array('name'=>'id', 'type'=>'xsd:string'),
		'type' => array('name'=>'type', 'type'=>'xsd:int'),
		'desc' => array('name'=>'desc', 'type'=>'xsd:string'),
		'fk_product' => array('name'=>'fk_product', 'type'=>'xsd:int'),
		'total_net' => array('name'=>'total_net', 'type'=>'xsd:double'),
		'total_vat' => array('name'=>'total_vat', 'type'=>'xsd:double'),
		'total' => array('name'=>'total', 'type'=>'xsd:double'),
		'vat_rate' => array('name'=>'vat_rate', 'type'=>'xsd:double'),
		'qty' => array('name'=>'qty', 'type'=>'xsd:double'),
		'date_start' => array('name'=>'date_start', 'type'=>'xsd:date'),
		'date_end' => array('name'=>'date_end', 'type'=>'xsd:date'),
		// From product
		'product_ref' => array('name'=>'product_ref', 'type'=>'xsd:string'),
		'product_label' => array('name'=>'product_label', 'type'=>'xsd:string'),
		'product_desc' => array('name'=>'product_desc', 'type'=>'xsd:string')
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
		array('ref'=>'SOAP-ENC:arrayType', 'wsdl:arrayType'=>'tns:line[]')
	),
	'tns:line'
);

$server->wsdl->addComplexType(
	'invoice',
	'element', // If we put element here instead of complexType to have tag called invoice in getInvoicesForThirdParty we brek getInvoice
	'struct',
	'all',
	'',
	array(
		'id' => array('name'=>'id', 'type'=>'xsd:string'),
		'ref' => array('name'=>'ref', 'type'=>'xsd:string'),
		'ref_ext' => array('name'=>'ref_ext', 'type'=>'xsd:string'),
		'ref_supplier' => array('name'=>'ref_supplier', 'type'=>'xsd:string'),
		'fk_user_author' => array('name'=>'fk_user_author', 'type'=>'xsd:int'),
		'fk_user_valid' => array('name'=>'fk_user_valid', 'type'=>'xsd:int'),
		'fk_thirdparty' => array('name'=>'fk_thirdparty', 'type'=>'xsd:int'),
		'date_creation' => array('name'=>'date_creation', 'type'=>'xsd:dateTime'),
		'date_validation' => array('name'=>'date_validation', 'type'=>'xsd:dateTime'),
		'date_modification' => array('name'=>'date_modification', 'type'=>'xsd:dateTime'),
		'date_invoice' => array('name'=>'date_invoice', 'type'=>'xsd:date'),
		'date_term' => array('name'=>'date_modification', 'type'=>'xsd:date'),
		'label' => array('name'=>'label', 'type'=>'xsd:date'),
		'type' => array('name'=>'type', 'type'=>'xsd:int'),
		'total_net' => array('name'=>'type', 'type'=>'xsd:double'),
		'total_vat' => array('name'=>'type', 'type'=>'xsd:double'),
		'total' => array('name'=>'type', 'type'=>'xsd:double'),
		'note_private' => array('name'=>'note_private', 'type'=>'xsd:string'),
		'note_public' => array('name'=>'note_public', 'type'=>'xsd:string'),
		'status' => array('name'=>'status', 'type'=>'xsd:int'),
		'close_code' => array('name'=>'close_code', 'type'=>'xsd:string'),
		'close_note' => array('name'=>'close_note', 'type'=>'xsd:string'),
		'lines' => array('name'=>'lines', 'type'=>'tns:LinesArray')
	)
);

$server->wsdl->addComplexType(
	'InvoicesArray',
	'complexType',
	'array',
	'',
	'SOAP-ENC:Array',
	array(),
	array(
		array('ref'=>'SOAP-ENC:arrayType', 'wsdl:arrayType'=>'tns:invoice[]')
	),
	'tns:invoice'
);

$server->wsdl->addComplexType(
	'invoices',
	'complexType',
	'array',
	'',
	'SOAP-ENC:Array',
	array(),
	array(
		array('ref'=>'SOAP-ENC:arrayType', 'wsdl:arrayType'=>'tns:invoice[]')
	),
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
	'getSupplierInvoice',
	// Entry values
	array('authentication'=>'tns:authentication', 'id'=>'xsd:string', 'ref'=>'xsd:string', 'ref_ext'=>'xsd:string'),
	// Exit values
	array('result'=>'tns:result', 'invoice'=>'tns:invoice'),
	$ns,
	$ns.'#getSupplierInvoice',
	$styledoc,
	$styleuse,
	'WS to get SupplierInvoice'
);
$server->register(
	'getSupplierInvoicesForThirdParty',
	// Entry values
	array('authentication'=>'tns:authentication', 'idthirdparty'=>'xsd:string'),
	// Exit values
	array('result'=>'tns:result', 'invoices'=>'tns:invoices'),
	$ns,
	$ns.'#getSupplierInvoicesForThirdParty',
	$styledoc,
	$styleuse,
	'WS to get SupplierInvoicesForThirdParty'
);


/**
 * Get invoice from id, ref or ref_ext
 *
 * @param	array		$authentication		Array of authentication information
 * @param	int			$id					Id
 * @param	string		$ref				Ref
 * @param	string		$ref_ext			Ref_ext
 * @return	array							Array result
 */
function getSupplierInvoice($authentication, $id = 0, $ref = '', $ref_ext = '')
{
	global $db, $conf;

	dol_syslog("Function: getSupplierInvoice login=".$authentication['login']." id=".$id." ref=".$ref." ref_ext=".$ref_ext);

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

		if ($fuser->hasRight('fournisseur', 'facture', 'lire')) {
			$invoice = new FactureFournisseur($db);
			$result = $invoice->fetch($id, $ref, $ref_ext);
			if ($result > 0) {
				$linesresp = array();
				$i = 0;
				foreach ($invoice->lines as $line) {
					//var_dump($line); exit;
					$linesresp[] = array(
						'id'=>$line->rowid,
						'type'=>$line->product_type,
						'total_net'=>$line->total_ht,
						'total_vat'=>$line->total_tva,
						'total'=>$line->total_ttc,
						'vat_rate'=>$line->tva_tx,
						'qty'=>$line->qty
					);
					$i++;
				}

				// Create invoice
				$objectresp = array(
					'result'=>array('result_code'=>'OK', 'result_label'=>''),
					'invoice'=>array(
					'id' => $invoice->id,
					'ref' => $invoice->ref,
					'ref_supplier'=>$invoice->ref_supplier,
					'ref_ext' => $invoice->ref_ext,
					'fk_user_author' => $invoice->fk_user_author,
					'fk_user_valid' => $invoice->fk_user_valid,
					'fk_thirdparty' => $invoice->fk_soc,
					'type'=>$invoice->type,
					'status'=>$invoice->statut,
					'total_net'=>$invoice->total_ht,
					'total_vat'=>$invoice->total_tva,
					'total'=>$invoice->total_ttc,
					'date_creation'=>dol_print_date($invoice->datec, 'dayhourrfc'),
					'date_modification'=>dol_print_date($invoice->tms, 'dayhourrfc'),
					'date_invoice'=>dol_print_date($invoice->date, 'dayhourrfc'),
					'date_term'=>dol_print_date($invoice->date_echeance, 'dayhourrfc'),
					'label'=>$invoice->label,
					'paid'=>$invoice->paid,
					'note_private'=>$invoice->note_private,
					'note_public'=>$invoice->note_public,
					'close_code'=>$invoice->close_code,
					'close_note'=>$invoice->close_note,

					'lines' => $linesresp,
					// 'lines' => array('0'=>array('id'=>222,'type'=>1),
					// '1'=>array('id'=>333,'type'=>1)),

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
		$objectresp = array('result'=>array('result_code' => $errorcode, 'result_label' => $errorlabel));
	}

	return $objectresp;
}


/**
 * Get list of invoices for third party
 *
 * @param	array		$authentication		Array of authentication information
 * @param	int			$idthirdparty		Id thirdparty
 * @return	array							Array result
 */
function getSupplierInvoicesForThirdParty($authentication, $idthirdparty)
{
	global $db, $conf;

	dol_syslog("Function: getSupplierInvoicesForThirdParty login=".$authentication['login']." idthirdparty=".$idthirdparty);

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
	if (!$error && empty($idthirdparty)) {
		$error++;
		$errorcode = 'BAD_PARAMETERS';
		$errorlabel = 'Parameter id is not provided';
	}

	if (!$error) {
		$linesinvoice = array();

		$sql = "SELECT f.rowid as facid";
		$sql .= " FROM ".MAIN_DB_PREFIX."facture_fourn as f";
		$sql .= " WHERE f.entity = ".((int) $conf->entity);
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

				$invoice = new FactureFournisseur($db);
				$result = $invoice->fetch($obj->facid);
				if ($result < 0) {
					$error++;
					$errorcode = $result;
					$errorlabel = $invoice->error;
					break;
				}

				// Define lines of invoice
				$linesresp = array();
				foreach ($invoice->lines as $line) {
					$linesresp[] = array(
						'id'=>$line->rowid,
						'type'=>$line->product_type,
						'desc'=>dol_htmlcleanlastbr($line->description),
						'total_net'=>$line->total_ht,
						'total_vat'=>$line->total_tva,
						'total'=>$line->total_ttc,
						'vat_rate'=>$line->tva_tx,
						'qty'=>$line->qty,
						'product_ref'=>$line->product_ref,
						'product_label'=>$line->product_label,
						'product_desc'=>$line->product_desc,
					);
				}

				// Now define invoice
				$linesinvoice[] = array(
					'id'=>$invoice->id,
					'ref'=>$invoice->ref,
					'ref_supplier'=>$invoice->ref_supplier,
					'ref_ext'=>$invoice->ref_ext,
					'fk_user_author' => $invoice->user_creation_id,
					'fk_user_valid' => $invoice->user_validation_id,
					'fk_thirdparty' => $invoice->socid,
					'type'=>$invoice->type,
					'status'=>$invoice->status,
					'total_net'=>$invoice->total_ht,
					'total_vat'=>$invoice->total_tva,
					'total'=>$invoice->total_ttc,
					'date_creation'=>dol_print_date($invoice->datec, 'dayhourrfc'),
					'date_modification'=>dol_print_date($invoice->tms, 'dayhourrfc'),
					'date_invoice'=>dol_print_date($invoice->date, 'dayhourrfc'),
					'date_term'=>dol_print_date($invoice->date_echeance, 'dayhourrfc'),
					'label'=>$invoice->label,
					'paid'=>$invoice->paid,
					'note_private'=>$invoice->note_private,
					'note_public'=>$invoice->note_public,
					'close_code'=>$invoice->close_code,
					'close_note'=>$invoice->close_note,

					'lines' => $linesresp
				);

				$i++;
			}

			$objectresp = array(
				'result'=>array('result_code'=>'OK', 'result_label'=>''),
				'invoices'=>$linesinvoice

			);
		} else {
			$error++;
			$errorcode = $db->lasterrno();
			$errorlabel = $db->lasterror();
		}
	}

	if ($error) {
		$objectresp = array('result'=>array('result_code' => $errorcode, 'result_label' => $errorlabel));
	}

	return $objectresp;
}

// Return the results.
$server->service(file_get_contents("php://input"));
