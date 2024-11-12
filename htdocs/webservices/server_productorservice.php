<?php
/* Copyright (C) 2006-2016 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2012      JF FERRY             <jfefe@aternatik.fr>
 * Copyright (C) 2020-2024 Frédéric France		<frederic.france@free.fr>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
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
 *
 * Path to WSDL is: http://localhost/dolibarr/webservices/server_productorservice.php?wsdl
 */

/**
 *       \file       htdocs/webservices/server_productorservice.php
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
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';

require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT."/categories/class/categorie.class.php";
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';

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
$server->configureWSDL('WebServicesDolibarrProductOrService', $ns);
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
		'result_label' => array('name' => 'result_label', 'type' => 'xsd:string')
	)
);

$productorservice_fields = array(
	'id' => array('name' => 'id', 'type' => 'xsd:string'),
	'ref' => array('name' => 'ref', 'type' => 'xsd:string'),
	'ref_ext' => array('name' => 'ref_ext', 'type' => 'xsd:string'),
	'type' => array('name' => 'type', 'type' => 'xsd:string'),
	'label' => array('name' => 'label', 'type' => 'xsd:string'),
	'description' => array('name' => 'description', 'type' => 'xsd:string'),
	'date_creation' => array('name' => 'date_creation', 'type' => 'xsd:dateTime'),
	'date_modification' => array('name' => 'date_modification', 'type' => 'xsd:dateTime'),
	'note' => array('name' => 'note', 'type' => 'xsd:string'),
	'status_tobuy' => array('name' => 'status_tobuy', 'type' => 'xsd:string'),
	'status_tosell' => array('name' => 'status_tosell', 'type' => 'xsd:string'),
	'barcode' => array('name' => 'barcode', 'type' => 'xsd:string'),
	'barcode_type' => array('name' => 'barcode_type', 'type' => 'xsd:string'),
	'country_id' => array('name' => 'country_id', 'type' => 'xsd:string'),
	'country_code' => array('name' => 'country_code', 'type' => 'xsd:string'),
	'customcode' => array('name' => 'customcode', 'type' => 'xsd:string'),

	'price_net' => array('name' => 'price_net', 'type' => 'xsd:string'),
	'price' => array('name' => 'price', 'type' => 'xsd:string'),
	'price_min_net' => array('name' => 'price_min_net', 'type' => 'xsd:string'),
	'price_min' => array('name' => 'price_min', 'type' => 'xsd:string'),

	'price_base_type' => array('name' => 'price_base_type', 'type' => 'xsd:string'),

	'vat_rate' => array('name' => 'vat_rate', 'type' => 'xsd:string'),
	'vat_npr' => array('name' => 'vat_npr', 'type' => 'xsd:string'),
	'localtax1_tx' => array('name' => 'localtax1_tx', 'type' => 'xsd:string'),
	'localtax2_tx' => array('name' => 'localtax2_tx', 'type' => 'xsd:string'),

	'stock_alert' => array('name' => 'stock_alert', 'type' => 'xsd:string'),
	'stock_real' => array('name' => 'stock_real', 'type' => 'xsd:string'),
	'stock_pmp' => array('name' => 'stock_pmp', 'type' => 'xsd:string'),
	'warehouse_ref' => array('name' => 'warehouse_ref', 'type' => 'xsd:string'), // Used only for create or update to set which warehouse to use for stock correction if stock_real differs from database

	'canvas' => array('name' => 'canvas', 'type' => 'xsd:string'),
	'import_key' => array('name' => 'import_key', 'type' => 'xsd:string'),

	'dir' => array('name' => 'dir', 'type' => 'xsd:string'),
	'images' => array('name' => 'images', 'type' => 'tns:ImagesArray')
);


$elementtype = 'product';


//Retrieve all extrafield for product
// fetch optionals attributes and labels
$extrafields = new ExtraFields($db);
$extrafields->fetch_name_optionals_label($elementtype, true);
$extrafield_array = null;
if (is_array($extrafields->attributes) && $extrafields->attributes[$elementtype]['count'] > 0) {
	$extrafield_array = array();
}
if (isset($extrafields->attributes[$elementtype]['label']) && is_array($extrafields->attributes[$elementtype]['label']) && count($extrafields->attributes[$elementtype]['label'])) {
	foreach ($extrafields->attributes[$elementtype]['label'] as $key => $label) {
		$type = $extrafields->attributes[$elementtype]['type'][$key];
		if ($type == 'date' || $type == 'datetime') {
			$type = 'xsd:dateTime';
		} else {
			$type = 'xsd:string';
		}

		$extrafield_array['options_'.$key] = array('name' => 'options_'.$key, 'type' => $type);
	}
}

if (!empty($extrafield_array) && is_array($extrafield_array)) {
	$productorservice_fields = array_merge($productorservice_fields, $extrafield_array);
}

// Define other specific objects
$server->wsdl->addComplexType(
	'product',
	'complexType',
	'struct',
	'all',
	'',
	$productorservice_fields
);


/*
 * Image of product
 */
$server->wsdl->addComplexType(
	'ImagesArray',
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
		'photo' => array('name' => 'photo', 'type' => 'xsd:string'),
		'photo_vignette' => array('name' => 'photo_vignette', 'type' => 'xsd:string'),
		'imgWidth' => array('name' => 'imgWidth', 'type' => 'xsd:string'),
		'imgHeight' => array('name' => 'imgHeight', 'type' => 'xsd:string')
	)
);


// Define other specific objects
$server->wsdl->addComplexType(
	'filterproduct',
	'complexType',
	'struct',
	'all',
	'',
	array(
		//'limit' => array('name'=>'limit','type'=>'xsd:string'),
		'type' => array('name' => 'type', 'type' => 'xsd:string'),
		'status_tobuy' => array('name' => 'status_tobuy', 'type' => 'xsd:string'),
		'status_tosell' => array('name' => 'status_tosell', 'type' => 'xsd:string'),
	)
);

$server->wsdl->addComplexType(
	'ProductsArray2',
	'complexType',
	'array',
	'sequence',
	'',
	array(
		'product' => array(
			'name' => 'product',
			'type' => 'tns:product',
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
	'getProductOrService',
	// Entry values
	array('authentication' => 'tns:authentication', 'id' => 'xsd:string', 'ref' => 'xsd:string', 'ref_ext' => 'xsd:string', 'lang' => 'xsd:string'),
	// Exit values
	array('result' => 'tns:result', 'product' => 'tns:product'),
	$ns,
	$ns.'#getProductOrService',
	$styledoc,
	$styleuse,
	'WS to get product or service'
);

// Register WSDL
$server->register(
	'createProductOrService',
	// Entry values
	array('authentication' => 'tns:authentication', 'product' => 'tns:product'),
	// Exit values
	array('result' => 'tns:result', 'id' => 'xsd:string'),
	$ns,
	$ns.'#createProductOrService',
	$styledoc,
	$styleuse,
	'WS to create a product or service'
);

// Register WSDL
$server->register(
	'updateProductOrService',
	// Entry values
	array('authentication' => 'tns:authentication', 'product' => 'tns:product'),
	// Exit values
	array('result' => 'tns:result', 'id' => 'xsd:string'),
	$ns,
	$ns.'#updateProductOrService',
	$styledoc,
	$styleuse,
	'WS to update a product or service'
);

// Register WSDL
$server->register(
	'deleteProductOrService',
	// Entry values
	array('authentication' => 'tns:authentication', 'listofid' => 'xsd:string'),
	// Exit values
	array('result' => 'tns:result', 'nbdeleted' => 'xsd:int'),
	$ns,
	$ns.'#deleteProductOrService',
	$styledoc,
	$styleuse,
	'WS to delete a product or service'
);

// Register WSDL
$server->register(
	'getListOfProductsOrServices',
	// Entry values
	array('authentication' => 'tns:authentication', 'filterproduct' => 'tns:filterproduct'),
	// Exit values
	array('result' => 'tns:result', 'products' => 'tns:ProductsArray2'),
	$ns,
	$ns.'#getListOfProductsOrServices',
	$styledoc,
	$styleuse,
	'WS to get list of all products or services id and ref'
);

// Register WSDL
$server->register(
	'getProductsForCategory',
	// Entry values
	array('authentication' => 'tns:authentication', 'id' => 'xsd:string', 'lang' => 'xsd:string'),
	// Exit values
	array('result' => 'tns:result', 'products' => 'tns:ProductsArray2'),
	$ns,
	$ns.'#getProductsForCategory',
	$styledoc,
	$styleuse,
	'WS to get list of all products or services for a category'
);


/**
 * Get produt or service
 *
 * @param	array{login:string,password:string,entity:?int,dolibarrkey:string}		$authentication		Array of authentication information
 * @param	int			$id					Id of object
 * @param	string		$ref				Ref of object
 * @param	string		$ref_ext			Ref external of object
 * @param   string      $lang               Lang to force
 * @return array{product?:mixed[],result:array{result_code:string,result_label:string}} Array result
 */
function getProductOrService($authentication, $id = 0, $ref = '', $ref_ext = '', $lang = '')
{
	global $db, $conf, $langs;

	dol_syslog("Function: getProductOrService login=".$authentication['login']." id=".$id." ref=".$ref." ref_ext=".$ref_ext);

	$langcode = ($lang ? $lang : (!getDolGlobalString('MAIN_LANG_DEFAULT') ? 'auto' : $conf->global->MAIN_LANG_DEFAULT));
	$langs->setDefaultLang($langcode);

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
		$langcode = ($lang ? $lang : (!getDolGlobalString('MAIN_LANG_DEFAULT') ? 'auto' : $conf->global->MAIN_LANG_DEFAULT));
		$langs->setDefaultLang($langcode);

		$fuser->loadRights();

		$nbmax = 10;
		if ($fuser->hasRight('produit', 'lire') || $fuser->hasRight('service', 'lire')) {
			$product = new Product($db);
			$result = $product->fetch($id, $ref, $ref_ext);

			if ($result > 0) {
				$product->load_stock();

				$dir = (!empty($conf->product->dir_output) ? $conf->product->dir_output : $conf->service->dir_output);
				$pdir = get_exdir($product->id, 2, 0, 0, $product, 'product').$product->ref."/";
				$dir = $dir.'/'.$pdir;

				if (!empty($product->multilangs[$langs->defaultlang]["label"])) {
					$product->label = $product->multilangs[$langs->defaultlang]["label"];
				}
				if (!empty($product->multilangs[$langs->defaultlang]["description"])) {
					$product->description = $product->multilangs[$langs->defaultlang]["description"];
				}
				if (!empty($product->multilangs[$langs->defaultlang]["note"])) {
					$product->note = $product->multilangs[$langs->defaultlang]["note"];
				}

				$productorservice_result_fields = array(
					'id' => $product->id,
					'ref' => $product->ref,
					'ref_ext' => $product->ref_ext,
					'label' => $product->label,
					'description' => $product->description,
					'date_creation' => dol_print_date($product->date_creation, 'dayhourrfc'),
					'date_modification' => dol_print_date($product->date_modification, 'dayhourrfc'),
					'note' => $product->note,
					'status_tosell' => $product->status,
					'status_tobuy' => $product->status_buy,
					'type' => $product->type,
					'barcode' => $product->barcode,
					'barcode_type' => $product->barcode_type,
					'country_id' => $product->country_id > 0 ? $product->country_id : '',
					'country_code' => $product->country_code,
					'custom_code' => $product->customcode,

					'price_net' => $product->price,
					'price' => $product->price_ttc,
					'price_min_net' => $product->price_min,
					'price_min' => $product->price_min_ttc,
					'price_base_type' => $product->price_base_type,
					'vat_rate' => $product->tva_tx,
					//! French VAT NPR
					'vat_npr' => $product->tva_npr,
					//! Spanish local taxes
					'localtax1_tx' => $product->localtax1_tx,
					'localtax2_tx' => $product->localtax2_tx,

					'stock_real' => $product->stock_reel,
					'stock_virtual' => $product->stock_theorique,
					'stock_alert' => $product->seuil_stock_alerte,
					'pmp' => $product->pmp,
					'import_key' => $product->import_key,
					'dir' => $pdir,
					'images' => $product->liste_photos($dir, $nbmax)
				);

				$elementtype = 'product';

				//Retrieve all extrafield for thirdsparty
				// fetch optionals attributes and labels
				$extrafields = new ExtraFields($db);
				$extrafields->fetch_name_optionals_label($elementtype, true);
				//Get extrafield values
				$product->fetch_optionals();

				if (isset($extrafields->attributes[$elementtype]['label']) && is_array($extrafields->attributes[$elementtype]['label']) && count($extrafields->attributes[$elementtype]['label'])) {
					foreach ($extrafields->attributes[$elementtype]['label'] as $key => $label) {
						$productorservice_result_fields = array_merge($productorservice_result_fields, array('options_'.$key => $product->array_options['options_'.$key]));
					}
				}

				// Create
				$objectresp = array(
					'result' => array('result_code' => 'OK', 'result_label' => ''),
					'product' => $productorservice_result_fields
				);
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
	//var_dump($objectresp);exit;
	return $objectresp;
}


/**
 * Create an invoice
 *
 * @param	array{login:string,password:string,entity:?int,dolibarrkey:string}		$authentication		Array of authentication information
 * @param	array{id:string,ref:string,ref_ext:string,type:string,label:string,description:string,date_creation:string,date_modification:string,note:string,status_tobuy:string,status_tosell:string,barcode:string,barcode_type:string,country_id:string,country_code:string,customcode:string,price_net:string,price:string,price_min_net:string,price_min:string,price_base_type:string,vat_rate:string,vat_npr:string,localtax1_tx:string,localtax2_tx:string,stock_alert:string,stock_real:string,stock_pmp:string,warehouse_ref:string,canvas:string,import_key:string,dir:string,images:array<array{photo:string,photo_vignette:string,imgWidth:string,imgHeight:string}>}		$product			Product
 * @return array{result:array{result_code:string,result_label:string}} Array result
 */
function createProductOrService($authentication, $product)
{
	global $db, $conf;

	$now = dol_now();

	dol_syslog("Function: createProductOrService login=".$authentication['login']);

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
	if (empty($product['price_base_type'])) {
		if (isset($product['price_net']) && $product['price_net'] > 0) {
			$product['price_base_type'] = 'HT';
		}
		if (isset($product['price']) && $product['price'] > 0) {
			$product['price_base_type'] = 'TTC';
		}
	}

	if (isset($product['price_net']) && $product['price_net'] > 0 && isset($product['price']) && $product['price'] > 0) {
		$error++;
		$errorcode = 'KO';
		$errorlabel = "You must choose between price or price_net to provide price.";
	}

	if (!empty($product['barcode']) && empty($product['barcode_type'])) {
		$error++;
		$errorcode = 'KO';
		$errorlabel = "You must set a barcode type when setting a barcode.";
	}

	if (!$error) {
		include_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';

		$newobject = new Product($db);
		$newobject->ref = $product['ref'];
		$newobject->ref_ext = empty($product['ref_ext']) ? '' : $product['ref_ext'];
		$newobject->type = empty($product['type']) ? 0 : $product['type'];
		$newobject->label = empty($product['label']) ? '' : $product['label'];
		$newobject->description = empty($product['description']) ? '' : $product['description'];
		$newobject->note_public = empty($product['note_public']) ? '' : $product['note_public'];
		$newobject->note_private = empty($product['note_private']) ? '' : $product['note_private'];
		$newobject->status = empty($product['status_tosell']) ? 0 : $product['status_tosell'];
		$newobject->status_buy = empty($product['status_tobuy']) ? 0 : $product['status_tobuy'];
		$newobject->price = isset($product['price_net']) ? (float) $product['price_net'] : 0;
		$newobject->price_ttc = isset($product['price']) ? (float) $product['price'] : 0;
		$newobject->tva_tx = empty($product['vat_rate']) ? 0 : $product['vat_rate'];
		$newobject->price_base_type = $product['price_base_type'];
		$newobject->date_creation = $now;

		if (!empty($product['barcode'])) {
			$newobject->barcode = $product['barcode'];
			$newobject->barcode_type = (int) $product['barcode_type'];
		}

		$newobject->stock_reel = isset($product['stock_real']) ? (int) $product['stock_real'] : null;
		$newobject->pmp = isset($product['pmp']) ? (float) $product['pmp'] : 0;
		$newobject->seuil_stock_alerte = isset($product['stock_alert']) ? (float) $product['stock_alert'] : null;

		$newobject->country_id = isset($product['country_id']) ? (int) $product['country_id'] : 0;
		if (!empty($product['country_code'])) {
			$newobject->country_id = getCountry($product['country_code'], '3');
		}
		$newobject->customcode = isset($product['customcode']) ? $product['customcode'] : '';

		$newobject->canvas = isset($product['canvas']) ? $product['canvas'] : '';
		/*foreach($product['lines'] as $line)
		{
			$newline=new FactureLigne($db);
			$newline->type=$line['type'];
			$newline->desc=$line['desc'];
			$newline->fk_product=$line['fk_product'];
			$newline->total_ht=$line['total_net'];
			$newline->total_vat=$line['total_vat'];
			$newline->total_ttc=$line['total'];
			$newline->vat=$line['vat_rate'];
			$newline->qty=$line['qty'];
			$newline->fk_product=$line['product_id'];
		}*/
		//var_dump($product['ref_ext']);
		//var_dump($product['lines'][0]['type']);

		$elementtype = 'product';

		$extrafields = new ExtraFields($db);
		$extrafields->fetch_name_optionals_label($elementtype, true);
		if (isset($extrafields->attributes[$elementtype]['label']) && is_array($extrafields->attributes[$elementtype]['label']) && count($extrafields->attributes[$elementtype]['label'])) {
			foreach ($extrafields->attributes[$elementtype]['label'] as $key => $label) {
				$key = 'options_'.$key;
				$newobject->array_options[$key] = $product[$key];
			}
		}

		$db->begin();

		$result = $newobject->create($fuser, 0);
		if ($result <= 0) {
			$error++;
		}

		if (!$error) {
			// Update stock if stock count is provided and differs from database after creation or update
			if (isset($product['stock_real']) && $product['stock_real'] != '' && isModEnabled('stock')) {
				include_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';

				$savstockreal = $newobject->stock_reel;
				$newobject->load_stock('novirtual,nobatch'); // This overwrite ->stock_reel, surely 0 because we have just created product
				$getstockreal = $newobject->stock_reel;

				if ($savstockreal != $getstockreal) {
					$warehouse = new Entrepot($db);
					$warehouse->fetch(0, $product['warehouse_ref']);
					if ($warehouse->id > 0) {
						if (($savstockreal - $getstockreal) > 0) {
							$result = $newobject->correct_stock($fuser, $warehouse->id, ($savstockreal - $getstockreal), 0, 'Correction from external call (Web Service)', 0, 'WS'.dol_print_date($now, 'dayhourlog'));
						}
						if (($savstockreal - $getstockreal) > 0) {
							$result = $newobject->correct_stock($fuser, $warehouse->id, ($savstockreal - $getstockreal), 1, 'Correction from external call (Web Service)', 0, 'WS'.dol_print_date($now, 'dayhourlog'));
						}
						if ($result <= 0) {
							$error++;
							$newobject->error = 'You set a different value for stock, but correction of stock count (before='.$getstockreal.', after='.$savstockreal.') fails with error '.$newobject->error;
						}
					} else {
						$error++;
						$newobject->error = 'You set a different value for stock but we failed to find warehouse '.$product['warehouse_ref'].' to make correction.';
					}
				}
			}
		}

		if (!$error) {
			$db->commit();
			$objectresp = array('result' => array('result_code' => 'OK', 'result_label' => ''), 'id' => $newobject->id, 'ref' => $newobject->ref);
		} else {
			$db->rollback();
			$error++;
			$errorcode = 'KO';
			$errorlabel = $newobject->error;
		}
	}

	if ($error) {
		$objectresp = array('result' => array('result_code' => $errorcode, 'result_label' => $errorlabel));
	}

	return $objectresp;
}


/**
 * Update a product or service
 *
 * @param	array{login:string,password:string,entity:?int,dolibarrkey:string}		$authentication		Array of authentication information
 * @param	array{id:string,ref:string,ref_ext:string,type:string,label:string,description:string,date_creation:string,date_modification:string,note:string,status_tobuy:string,status_tosell:string,barcode:string,barcode_type:string,country_id:string,country_code:string,customcode:string,price_net:string,price:string,price_min_net:string,price_min:string,price_base_type:string,vat_rate:string,vat_npr:string,localtax1_tx:string,localtax2_tx:string,stock_alert:string,stock_real:string,stock_pmp:string,warehouse_ref:string,canvas:string,import_key:string,dir:string,images:array<array{photo:string,photo_vignette:string,imgWidth:string,imgHeight:string}>}		$product			Product
 * @return array{result:array{result_code:string,result_label:string}} Array result
 */
function updateProductOrService($authentication, $product)
{
	global $db, $conf;

	$now = dol_now();

	dol_syslog("Function: updateProductOrService login=".$authentication['login']);

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
	if ($product['price_net'] > 0) {
		$product['price_base_type'] = 'HT';
	}
	if ($product['price'] > 0) {
		$product['price_base_type'] = 'TTC';
	}

	if ($product['price_net'] > 0 && $product['price'] > 0) {
		$error++;
		$errorcode = 'KO';
		$errorlabel = "You must choose between price or price_net to provide price.";
	}


	if ($product['barcode'] && !$product['barcode_type']) {
		$error++;
		$errorcode = 'KO';
		$errorlabel = "You must set a barcode type when setting a barcode.";
	}

	if (!$error) {
		include_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';

		$newobject = new Product($db);
		$newobject->fetch($product['id']);

		if (isset($product['ref'])) {
			$newobject->ref = $product['ref'];
		}
		if (isset($product['ref_ext'])) {
			$newobject->ref_ext = $product['ref_ext'];
		}
		$newobject->type = (int) $product['type'];
		$newobject->label = $product['label'];
		$newobject->description = $product['description'];
		$newobject->note = $product['note'];
		$newobject->status = (int) $product['status_tosell'];
		$newobject->status_buy = (int) $product['status_tobuy'];
		$newobject->price = (float) $product['price_net'];
		$newobject->price_ttc = (float) $product['price'];
		$newobject->tva_tx = $product['vat_rate'];
		$newobject->price_base_type = $product['price_base_type'];
		$newobject->date_creation = $now;

		if ($product['barcode']) {
			$newobject->barcode = $product['barcode'];
			$newobject->barcode_type = (int) $product['barcode_type'];
		}

		$newobject->stock_reel = isset($product['stock_real']) ? (int) $product['stock_real'] : null;
		$newobject->pmp = isset($product['pmp']) ? (float) $product['pmp'] : 0;
		$newobject->seuil_stock_alerte = isset($product['stock_alert']) ? (float) $product['stock_alert'] : null;

		$newobject->country_id = isset($product['country_id']) ? (int) $product['country_id'] : 0;
		if (!empty($product['country_code'])) {
			$newobject->country_id = getCountry($product['country_code'], '3');
		}
		$newobject->customcode = isset($product['customcode']) ? $product['customcode'] : '';

		$newobject->canvas = isset($product['canvas']) ? $product['canvas'] : '';

		$elementtype = 'product';

		$extrafields = new ExtraFields($db);
		$extrafields->fetch_name_optionals_label($elementtype, true);
		if (isset($extrafields->attributes[$elementtype]['label']) && is_array($extrafields->attributes[$elementtype]['label']) && count($extrafields->attributes[$elementtype]['label'])) {
			foreach ($extrafields->attributes[$elementtype]['label'] as $key => $label) {
				$key = 'options_'.$key;
				$newobject->array_options[$key] = $product[$key];
			}
		}

		$db->begin();

		$result = $newobject->update($newobject->id, $fuser);
		if ($result <= 0) {
			$error++;
		} else {
			// Update stock if stock count is provided and differs from database after creation or update
			if (isset($product['stock_real']) && $product['stock_real'] != '' && isModEnabled('stock')) {
				include_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';

				$savstockreal = $newobject->stock_reel;
				$newobject->load_stock('novirtual,nobatch'); // This overwrite ->stock_reel
				$getstockreal = $newobject->stock_reel;

				if ($savstockreal != $getstockreal) {
					$warehouse = new Entrepot($db);
					$warehouse->fetch(0, $product['warehouse_ref']);
					if ($warehouse->id > 0) {
						if (($savstockreal - $getstockreal) > 0) {
							$result = $newobject->correct_stock($fuser, $warehouse->id, ($savstockreal - $getstockreal), 0, 'Correction from external call (Web Service)', 0, 'WS'.dol_print_date($now, 'dayhourlog'));
						}
						if (($savstockreal - $getstockreal) > 0) {
							$result = $newobject->correct_stock($fuser, $warehouse->id, ($savstockreal - $getstockreal), 1, 'Correction from external call (Web Service)', 0, 'WS'.dol_print_date($now, 'dayhourlog'));
						}
						if ($result <= 0) {
							$error++;
							$newobject->error = 'You set a different value for stock, but correction of stock count (before='.$getstockreal.', after='.$savstockreal.') fails with error '.$newobject->error;
						}
					} else {
						$error++;
						$newobject->error = 'You set a different value for stock but we failed to find warehouse '.$product['warehouse_ref'].' to make correction.';
					}
				}
			}
		}

		if (!$error) {
			if ($newobject->price_base_type == 'HT') {
				$result = $newobject->updatePrice($newobject->price, $newobject->price_base_type, $fuser);
				if ($result <= 0) {
					$error++;
				}
			} elseif ($newobject->price_base_type == 'TTC') {
				$result = $newobject->updatePrice($newobject->price_ttc, $newobject->price_base_type, $fuser);
				if ($result <= 0) {
					$error++;
				}
			}
		}

		if (!$error) {
			$db->commit();
			$objectresp = array('result' => array('result_code' => 'OK', 'result_label' => ''), 'id' => $newobject->id, 'ref' => $newobject->ref);
		} else {
			$db->rollback();
			$error++;
			$errorcode = 'KO';
			$errorlabel = $newobject->error;
		}
	}

	if ($error) {
		$objectresp = array('result' => array('result_code' => $errorcode, 'result_label' => $errorlabel));
	}

	return $objectresp;
}


/**
 * Delete a product or service
 *
 * @param	array{login:string,password:string,entity:?int,dolibarrkey:string}		$authentication		Array of authentication information
 * @param	string		$listofidstring		List of id with comma
 * @return array{result:array{result_code:string,result_label:string}} Array result
 */
function deleteProductOrService($authentication, $listofidstring)
{
	global $db, $conf;

	dol_syslog("Function: deleteProductOrService login=".$authentication['login']);

	if ($authentication['entity']) {
		$conf->entity = $authentication['entity'];
	}

	// Init and check authentication
	$objectresp = array();
	$errorcode = '';
	$errorlabel = '';
	$error = 0;
	$fuser = check_authentication($authentication, $error, $errorcode, $errorlabel);

	// User must be defined to user authenticated
	global $user;
	$user = $fuser;

	$listofid = explode(',', trim($listofidstring));
	$listofiddeleted = array();

	// Check parameters
	if (count($listofid) == 0 || empty($listofid[0])) {
		$error++;
		$errorcode = 'KO';
		$errorlabel = "List of Id of products or services to delete are required.";
	}

	if (!$error) {
		$firsterror = '';

		$db->begin();

		foreach ($listofid as $id) {
			$newobject = new Product($db);
			$result = $newobject->fetch($id);

			if ($result == 0) {
				$error++;
				$firsterror = 'Product or service with id '.$id.' not found';
				break;
			} else {
				$result = $newobject->delete($user);
				if ($result <= 0) {
					$error++;
					$firsterror = $newobject->error;
					break;
				}

				$listofiddeleted[] = $id;
			}
		}

		if (!$error) {
			$db->commit();
			//$objectresp=array('result'=>array('result_code'=>'OK', 'result_label'=>''), 'listofid'=>$listofiddeleted);
			$objectresp = array('result' => array('result_code' => 'OK', 'result_label' => ''), 'nbdeleted' => count($listofiddeleted));
		} else {
			$db->rollback();
			$error++;
			$errorcode = 'KO';
			$errorlabel = $firsterror;
		}
	}

	if ($error) {
		//$objectresp = array('result'=>array('result_code' => $errorcode, 'result_label' => $errorlabel), 'listofid'=>$listofiddeleted);
		$objectresp = array('result' => array('result_code' => $errorcode, 'result_label' => $errorlabel), 'nbdeleted' => 0);
	} elseif (count($listofiddeleted) == 0) {
		//$objectresp=array('result'=>array('result_code'=>'NOT_FOUND', 'result_label'=>'No product or service with id '.join(',',$listofid).' found'), 'listofid'=>$listofiddeleted);
		$objectresp = array('result' => array('result_code' => 'NOT_FOUND', 'result_label' => 'No product or service with id '.implode(',', $listofid).' found'), 'nbdeleted' => 0);
	}

	return $objectresp;
}


/**
 * getListOfProductsOrServices
 *
 * @param	array{login:string,password:string,entity:?int,dolibarrkey:string}		$authentication		Array of authentication information
 * @param	array<string,mixed>		$filterproduct		Filter fields
 * @return array{result:array{result_code:string,result_label:string}} Array result
 */
function getListOfProductsOrServices($authentication, $filterproduct)
{
	global $db, $conf;

	dol_syslog("Function: getListOfProductsOrServices login=".$authentication['login']);

	if ($authentication['entity']) {
		$conf->entity = $authentication['entity'];
	}

	// Init and check authentication
	$objectresp = array();
	$arrayproducts = array();
	$errorcode = '';
	$errorlabel = '';
	$error = 0;
	$fuser = check_authentication($authentication, $error, $errorcode, $errorlabel);
	// Check parameters

	if (!$error) {
		$sql = "SELECT rowid, ref, ref_ext";
		$sql .= " FROM ".MAIN_DB_PREFIX."product";
		$sql .= " WHERE entity=".$conf->entity;
		foreach ($filterproduct as $key => $val) {
			if ($key == 'type' && $val >= 0) {
				$sql .= " AND fk_product_type = ".((int) $val);
			}
			if ($key == 'status_tosell') {
				$sql .= " AND tosell = ".((int) $val);
			}
			if ($key == 'status_tobuy') {
				$sql .= " AND tobuy = ".((int) $val);
			}
		}
		$resql = $db->query($sql);
		if ($resql) {
			$num = $db->num_rows($resql);

			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				$arrayproducts[] = array('id' => $obj->rowid, 'ref' => $obj->ref, 'ref_ext' => $obj->ref_ext);
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
			'result' => array('result_code' => $errorcode, 'result_label' => $errorlabel),
			'products' => $arrayproducts
		);
	} else {
		$objectresp = array(
			'result' => array('result_code' => 'OK', 'result_label' => ''),
			'products' => $arrayproducts
		);
	}

	return $objectresp;
}


/**
 * Get list of products for a category
 *
 * @param	array{login:string,password:string,entity:?int,dolibarrkey:string}		$authentication		Array of authentication information
 * @param	int			$id					Category id
 * @param	string		$lang				Force lang
 * @return array{result:array{result_code:string,result_label:string}} Array result
 */
function getProductsForCategory($authentication, $id, $lang = '')
{
	global $db, $conf, $langs;

	$langcode = ($lang ? $lang : (!getDolGlobalString('MAIN_LANG_DEFAULT') ? 'auto' : $conf->global->MAIN_LANG_DEFAULT));
	$langs->setDefaultLang($langcode);

	dol_syslog("Function: getProductsForCategory login=".$authentication['login']." id=".$id);

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
		$langcode = ($lang ? $lang : (!getDolGlobalString('MAIN_LANG_DEFAULT') ? 'auto' : $conf->global->MAIN_LANG_DEFAULT));
		$langs->setDefaultLang($langcode);

		$fuser->loadRights();

		$nbmax = 10;
		if ($fuser->hasRight('produit', 'lire')) {
			$categorie = new Categorie($db);
			$result = $categorie->fetch($id);
			if ($result > 0) {
				$table = "product";
				$field = "product";
				$sql  = "SELECT fk_".$field." FROM ".MAIN_DB_PREFIX."categorie_".$table;
				$sql .= " WHERE fk_categorie = ".((int) $id);
				$sql .= " ORDER BY fk_".$field." ASC";


				dol_syslog("getProductsForCategory get id of product into category", LOG_DEBUG);
				$res = $db->query($sql);
				if ($res) {
					$iProduct = 0;
					$tmpproduct = new Product($db);
					$products = array();
					while ($rec = $db->fetch_array($res)) {
						$tmpproduct->fetch($rec['fk_'.$field]);
						if ($tmpproduct->status > 0) {
							$dir = (!empty($conf->product->dir_output) ? $conf->product->dir_output : $conf->service->dir_output);
							$pdir = get_exdir($tmpproduct->id, 2, 0, 0, $tmpproduct, 'product').$tmpproduct->id."/photos/";
							$dir = $dir.'/'.$pdir;

							$products[] = array(
								'id' => $tmpproduct->id,
								'ref' => $tmpproduct->ref,
								'ref_ext' => $tmpproduct->ref_ext,
								'label' => !empty($tmpproduct->multilangs[$langs->defaultlang]["label"]) ? $tmpproduct->multilangs[$langs->defaultlang]["label"] : $tmpproduct->label,
								'description' => !empty($tmpproduct->multilangs[$langs->defaultlang]["description"]) ? $tmpproduct->multilangs[$langs->defaultlang]["description"] : $tmpproduct->description,
								'date_creation' => dol_print_date($tmpproduct->date_creation, 'dayhourrfc'),
								'date_modification' => dol_print_date($tmpproduct->date_modification, 'dayhourrfc'),
								'note' => !empty($tmpproduct->multilangs[$langs->defaultlang]["note"]) ? $tmpproduct->multilangs[$langs->defaultlang]["note"] : $tmpproduct->note,
								'status_tosell' => $tmpproduct->status,
								'status_tobuy' => $tmpproduct->status_buy,
								'type' => $tmpproduct->type,
								'barcode' => $tmpproduct->barcode,
								'barcode_type' => $tmpproduct->barcode_type,
								'country_id' => $tmpproduct->country_id > 0 ? $tmpproduct->country_id : '',
								'country_code' => $tmpproduct->country_code,
								'custom_code' => $tmpproduct->customcode,

								'price_net' => $tmpproduct->price,
								'price' => $tmpproduct->price_ttc,
								'vat_rate' => $tmpproduct->tva_tx,

								'price_base_type' => $tmpproduct->price_base_type,

								'stock_real' => $tmpproduct->stock_reel,
								'stock_alert' => $tmpproduct->seuil_stock_alerte,
								'pmp' => $tmpproduct->pmp,
								'import_key' => $tmpproduct->import_key,
								'dir' => $pdir,
								'images' => $tmpproduct->liste_photos($dir, $nbmax)
							);

							$elementtype = 'product';

							//Retrieve all extrafield for thirdsparty
							// fetch optionals attributes and labels
							$extrafields = new ExtraFields($db);
							$extrafields->fetch_name_optionals_label($elementtype, true);
							//Get extrafield values
							$tmpproduct->fetch_optionals();

							if (isset($extrafields->attributes[$elementtype]['label']) && is_array($extrafields->attributes[$elementtype]['label']) && count($extrafields->attributes[$elementtype]['label'])) {
								foreach ($extrafields->attributes[$elementtype]['label'] as $key => $label) {
									$products[$iProduct] = array_merge($products[$iProduct], array('options_'.$key => $tmpproduct->array_options['options_'.$key]));
								}
							}

							$iProduct++;
						}
					}

					// Retour
					$objectresp = array(
					'result' => array('result_code' => 'OK', 'result_label' => ''),
					'products' => $products
					);
				} else {
					$errorcode = 'NORECORDS_FOR_ASSOCIATION';
					$errorlabel = 'No products associated'.$sql;
					$objectresp = array('result' => array('result_code' => $errorcode, 'result_label' => $errorlabel));
					dol_syslog("getProductsForCategory:: ".$errorcode, LOG_DEBUG);
				}
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
		$objectresp = array('result' => array('result_code' => $errorcode, 'result_label' => $errorlabel));
	}

	return $objectresp;
}

// Return the results.
$server->service(file_get_contents("php://input"));
