<?php
/* Copyright (C) 2001-2007	Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2016	Laurent Destailleur	 <eldy@users.sourceforge.net>
 * Copyright (C) 2005		Eric Seigne		     <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2015	Regis Houssin		 <regis.houssin@capnetworks.com>
 * Copyright (C) 2006		Andre Cianfarani	 <acianfa@free.fr>
 * Copyright (C) 2006		Auguria SARL		 <info@auguria.org>
 * Copyright (C) 2010-2015	Juanjo Menent		 <jmenent@2byte.es>
 * Copyright (C) 2013-2016	Marcos García		 <marcosgdf@gmail.com>
 * Copyright (C) 2012-2013	Cédric Salvador		 <csalvador@gpcsolutions.fr>
 * Copyright (C) 2011-2023	Alexandre Spangaro	 <aspangaro@open-dsi.fr>
 * Copyright (C) 2014		Cédric Gross		 <c.gross@kreiz-it.fr>
 * Copyright (C) 2014-2015	Ferran Marcet		 <fmarcet@2byte.es>
 * Copyright (C) 2015		Jean-François Ferry	 <jfefe@aternatik.fr>
 * Copyright (C) 2015		Raphaël Doursenaud	 <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2016-2022	Charlene Benke		 <charlene@patas-monkey.com>
 * Copyright (C) 2016		Meziane Sof		     <virtualsof@yahoo.fr>
 * Copyright (C) 2017		Josep Lluís Amador	 <joseplluis@lliuretic.cat>
 * Copyright (C) 2019-2022  Frédéric France      <frederic.france@netlogic.fr>
 * Copyright (C) 2019-2020  Thibault FOUCART     <support@ptibogxiv.net>
 * Copyright (C) 2020  		Pierre Ardoin     	 <mapiolca@me.com>
 * Copyright (C) 2022  		Vincent de Grandpré  <vincent@de-grandpre.quebec>
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
 *  \file       htdocs/product/card.php
 *  \ingroup    product
 *  \brief      Page to show product
 */


// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/canvas.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/genericobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/product/modules_product.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';


if (isModEnabled('propal')) {
	require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
}
if (isModEnabled('facture')) {
	require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
}
if (isModEnabled('commande')) {
	require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
}
if (isModEnabled('accounting')) {
	require_once DOL_DOCUMENT_ROOT.'/core/lib/accounting.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formaccounting.class.php';
	require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingaccount.class.php';
}
if (isModEnabled('bom')) {
	require_once DOL_DOCUMENT_ROOT.'/bom/class/bom.class.php';
}
if (isModEnabled('workstation')) {
	require_once DOL_DOCUMENT_ROOT.'/workstation/class/workstation.class.php';
}

// Load translation files required by the page
$langs->loadLangs(array('products', 'other'));
if (isModEnabled('stock')) {
	$langs->load("stocks");
}
if (isModEnabled('facture')) {
	$langs->load("bills");
}
if (isModEnabled('productbatch')) {
	$langs->load("productbatch");
}

$mesg = ''; $error = 0; $errors = array();

$refalreadyexists = 0;

// Get parameters
$id  = GETPOST('id', 'int');
if (getDolGlobalString('MAIN_SECURITY_ALLOW_UNSECURED_REF_LABELS')) {
	$ref = (GETPOSTISSET('ref') ? GETPOST('ref', 'nohtml') : null);
} else {
	$ref = (GETPOSTISSET('ref') ? GETPOST('ref', 'alpha') : null);
}
$type = (GETPOSTISSET('type') ? GETPOST('type', 'int') : Product::TYPE_PRODUCT);
$action = (GETPOST('action', 'alpha') ? GETPOST('action', 'alpha') : 'view');
$cancel = GETPOST('cancel', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');
$confirm = GETPOST('confirm', 'alpha');
$socid = GETPOST('socid', 'int');
$duration_value = GETPOST('duration_value', 'int');
$duration_unit = GETPOST('duration_unit', 'alpha');

$accountancy_code_sell = GETPOST('accountancy_code_sell', 'alpha');
$accountancy_code_sell_intra = GETPOST('accountancy_code_sell_intra', 'alpha');
$accountancy_code_sell_export = GETPOST('accountancy_code_sell_export', 'alpha');
$accountancy_code_buy = GETPOST('accountancy_code_buy', 'alpha');
$accountancy_code_buy_intra = GETPOST('accountancy_code_buy_intra', 'alpha');
$accountancy_code_buy_export = GETPOST('accountancy_code_buy_export', 'alpha');

$checkmandatory = GETPOST('accountancy_code_buy_export', 'alpha');

// by default 'alphanohtml' (better security); hidden conf MAIN_SECURITY_ALLOW_UNSECURED_LABELS_WITH_HTML allows basic html
if (getDolGlobalString('MAIN_SECURITY_ALLOW_UNSECURED_REF_LABELS')) {
	$label_security_check = 'nohtml';
} else {
	$label_security_check = !getDolGlobalString('MAIN_SECURITY_ALLOW_UNSECURED_LABELS_WITH_HTML') ? 'alphanohtml' : 'restricthtml';
}

if (!empty($user->socid)) {
	$socid = $user->socid;
}

// Load object modCodeProduct
$module = (getDolGlobalString('PRODUCT_CODEPRODUCT_ADDON') ? $conf->global->PRODUCT_CODEPRODUCT_ADDON : 'mod_codeproduct_leopard');
if (substr($module, 0, 16) == 'mod_codeproduct_' && substr($module, -3) == 'php') {
	$module = substr($module, 0, dol_strlen($module) - 4);
}
$result = dol_include_once('/core/modules/product/'.$module.'.php');
if ($result > 0) {
	$modCodeProduct = new $module();
}

$object = new Product($db);
$object->type = $type; // so test later to fill $usercancxxx is correct
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

if ($id > 0 || !empty($ref)) {
	$result = $object->fetch($id, $ref);
	if ($result < 0) {
		dol_print_error($db, $object->error, $object->errors);
	}
	$entity = (empty($object->entity) ? $conf->entity : $object->entity);
	if (isModEnabled("product")) {
		$upload_dir = $conf->product->multidir_output[$entity].'/'.get_exdir(0, 0, 0, 0, $object, 'product').dol_sanitizeFileName($object->ref);
	} elseif (isModEnabled("service")) {
		$upload_dir = $conf->service->multidir_output[$entity].'/'.get_exdir(0, 0, 0, 0, $object, 'product').dol_sanitizeFileName($object->ref);
	}

	if (getDolGlobalInt('PRODUCT_USE_OLD_PATH_FOR_PHOTO')) {    // For backward compatiblity, we scan also old dirs
		if (isModEnabled("product")) {
			$upload_dirold = $conf->product->multidir_output[$entity].'/'.substr(substr("000".$object->id, -2), 1, 1).'/'.substr(substr("000".$object->id, -2), 0, 1).'/'.$object->id."/photos";
		} else {
			$upload_dirold = $conf->service->multidir_output[$entity].'/'.substr(substr("000".$object->id, -2), 1, 1).'/'.substr(substr("000".$object->id, -2), 0, 1).'/'.$object->id."/photos";
		}
	}
}

$modulepart = 'product';

// Get object canvas (By default, this is not defined, so standard usage of dolibarr)
$canvas = !empty($object->canvas) ? $object->canvas : GETPOST("canvas");
$objcanvas = null;
if (!empty($canvas)) {
	require_once DOL_DOCUMENT_ROOT.'/core/class/canvas.class.php';
	$objcanvas = new Canvas($db, $action);
	$objcanvas->getCanvas('product', 'card', $canvas);
}

// Security check
$fieldvalue = (!empty($id) ? $id : (!empty($ref) ? $ref : ''));
$fieldtype = (!empty($id) ? 'rowid' : 'ref');

if ($object->id > 0) {
	if ($object->type == $object::TYPE_PRODUCT) {
		restrictedArea($user, 'produit', $object->id, 'product&product', '', '');
	}
	if ($object->type == $object::TYPE_SERVICE) {
		restrictedArea($user, 'service', $object->id, 'product&product', '', '');
	}
} else {
	restrictedArea($user, 'produit|service', 0, 'product&product', '', '', $fieldtype);
}

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('productcard', 'globalcard'));

// Permissions
$usercanread   = (($object->type == Product::TYPE_PRODUCT && $user->hasRight('produit', 'read')) || ($object->type == Product::TYPE_SERVICE && $user->hasRight('service', 'lire')));
$usercancreate = (($object->type == Product::TYPE_PRODUCT && $user->hasRight('produit', 'creer')) || ($object->type == Product::TYPE_SERVICE && $user->hasRight('service', 'creer')));
$usercandelete = (($object->type == Product::TYPE_PRODUCT && $user->hasRight('produit', 'supprimer')) || ($object->type == Product::TYPE_SERVICE && $user->hasRight('service', 'supprimer')));


/*
 * Actions
 */

if ($cancel) {
	$action = '';
}

$createbarcode = isModEnabled('barcode');
if (getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && !$user->hasRight('barcode', 'creer_advance')) {
	$createbarcode = 0;
}

$parameters = array('id'=>$id, 'ref'=>$ref, 'objcanvas'=>$objcanvas);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	$backurlforlist = DOL_URL_ROOT.'/product/list.php?type='.$type;

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) {
				$backtopage = $backurlforlist;
			} else {
				$backtopage = DOL_URL_ROOT.'/product/card.php?id='.((!empty($id) && $id > 0) ? $id : '__ID__');
			}
		}
	}

	if ($cancel) {
		if (!empty($backtopageforcancel)) {
			header("Location: ".$backtopageforcancel);
			exit;
		} elseif (!empty($backtopage)) {
			header("Location: ".$backtopage);
			exit;
		}
		$action = '';
	}
	// merge products
	if ($action == 'confirm_merge' && $confirm == 'yes' && $user->hasRight('societe', 'creer')) {
		$error = 0;
		$productOriginId = GETPOST('product_origin', 'int');
		$productOrigin = new Product($db);

		if ($productOriginId <= 0) {
			$langs->load('errors');
			setEventMessages($langs->trans('ErrorProductIdIsMandatory', $langs->transnoentitiesnoconv('MergeOriginProduct')), null, 'errors');
		} else {
			if (!$error && $productOrigin->fetch($productOriginId) < 1) {
				setEventMessages($langs->trans('ErrorRecordNotFound'), null, 'errors');
				$error++;
			}

			if (!$error) {
				// TODO Move the merge function into class of object.
				$db->begin();

				// Recopy some data
				$listofproperties = array(
					'ref',
					'ref_ext',
					'label',
					'description',
					'url',
					'barcode',
					'fk_barcode_type',
					'import_key',
					'mandatory_period',
					'accountancy_code_buy',
					'accountancy_code_buy_intra',
					'accountancy_code_buy_export',
					'accountancy_code_sell',
					'accountancy_code_sell_intra',
					'accountancy_code_sell_export'
				);
				foreach ($listofproperties as $property) {
					if (empty($object->$property)) {
						$object->$property = $productOrigin->$property;
					}
				}
				// Concat some data
				$listofproperties = array(
					'note_public', 'note_private'
				);
				foreach ($listofproperties as $property) {
					$object->$property = dol_concatdesc($object->$property, $productOrigin->$property);
				}

				// Merge extrafields
				if (is_array($productOrigin->array_options)) {
					foreach ($productOrigin->array_options as $key => $val) {
						if (empty($object->array_options[$key])) {
							$object->array_options[$key] = $val;
						}
					}
				}

				// Merge categories
				$static_cat = new Categorie($db);
				$custcats_ori = $static_cat->containing($productOrigin->id, 'product', 'id');
				$custcats = $static_cat->containing($object->id, 'product', 'id');
				$custcats = array_merge($custcats, $custcats_ori);
				$object->setCategories($custcats);

				// If product has a new code that is same than origin, we clean origin code to avoid duplicate key from database unique keys.
				if ($productOrigin->barcode == $object->barcode) {
					dol_syslog("We clean customer and supplier code so we will be able to make the update of target");
					$productOrigin->barcode = '';
					//$productOrigin->update($productOrigin->id, $user, 0, 'merge');
				}

				// Update
				$result = $object->update($object->id, $user, 0, 'merge');
				if ($result <= 0) {
					setEventMessages($object->error, $object->errors, 'errors');
					$error++;
				}

				// Move links
				if (!$error) {
					// TODO add this functionality into the api_products.class.php
					// TODO Mutualise the list into object product.class.php
					$objects = array(
						'ActionComm' => '/comm/action/class/actioncomm.class.php',
						'Bom' => '/bom/class/bom.class.php',
						// do not use Categorie, it cause foreign key error, merge is done before
						//'Categorie' => '/categories/class/categorie.class.php',
						'Commande' => '/commande/class/commande.class.php',
						'CommandeFournisseur' => '/fourn/class/fournisseur.commande.class.php',
						'Contrat' => '/contrat/class/contrat.class.php',
						'Delivery' => '/delivery/class/delivery.class.php',
						'Facture' => '/compta/facture/class/facture.class.php',
						'FactureFournisseur' => '/fourn/class/fournisseur.facture.class.php',
						'FactureRec' => '/compta/facture/class/facture-rec.class.php',
						'FichinterRec' => '/fichinter/class/fichinterrec.class.php',
						'ProductFournisseur' => '/fourn/class/fournisseur.product.class.php',
						'Propal' => '/comm/propal/class/propal.class.php',
						'Reception' => '/reception/class/reception.class.php',
						'SupplierProposal' => '/supplier_proposal/class/supplier_proposal.class.php',
					);

					//First, all core objects must update their tables
					foreach ($objects as $object_name => $object_file) {
						require_once DOL_DOCUMENT_ROOT.$object_file;

						if (!$error && !$object_name::replaceProduct($db, $productOrigin->id, $object->id)) {
							$error++;
							setEventMessages($db->lasterror(), null, 'errors');
							break;
						}
					}
				}

				// External modules should update their ones too
				if (!$error) {
					$parameters = array('soc_origin' => $productOrigin->id, 'soc_dest' => $object->id);
					$reshook = $hookmanager->executeHooks(
						'replaceProduct',
						$parameters,
						$object,
						$action
					);

					if ($reshook < 0) {
						setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
						$error++;
					}
				}


				if (!$error) {
					$object->context = array(
						'merge' => 1,
						'mergefromid' => $productOrigin->id,
					);

					// Call trigger
					$result = $object->call_trigger('PRODUCT_MODIFY', $user);
					if ($result < 0) {
						setEventMessages($object->error, $object->errors, 'errors');
						$error++;
					}
					// End call triggers
				}

				if (!$error) {
					// We finally remove the old product
					// TODO merge attached files from old product into new one before delete
					if ($productOrigin->delete($user) < 1) {
						$error++;
					}
				}

				if (!$error) {
					setEventMessages($langs->trans('ProductsMergeSuccess'), null, 'mesgs');
					$db->commit();
				} else {
					$langs->load("errors");
					setEventMessages($langs->trans('ErrorsProductsMerge'), null, 'errors');
					$db->rollback();
				}
			}
		}
	}

	// Type
	if ($action == 'setfk_product_type' && $usercancreate) {
		$result = $object->setValueFrom('fk_product_type', GETPOST('fk_product_type'), '', null, 'text', '', $user, 'PRODUCT_MODIFY');
		header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
		exit;
	}

	// Actions to build doc
	$upload_dir = $conf->product->dir_output;
	$permissiontoadd = $usercancreate;
	include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';

	include DOL_DOCUMENT_ROOT.'/core/actions_printing.inc.php';

	// Barcode type
	if ($action == 'setfk_barcode_type' && $createbarcode) {
		$result = $object->setValueFrom('fk_barcode_type', GETPOST('fk_barcode_type'), '', null, 'text', '', $user, 'PRODUCT_MODIFY');
		header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
		exit;
	}

	// Barcode value
	if ($action == 'setbarcode' && $createbarcode) {
		$result = $object->check_barcode(GETPOST('barcode'), GETPOST('barcode_type_code'));

		if ($result >= 0) {
			$result = $object->setValueFrom('barcode', GETPOST('barcode'), '', null, 'text', '', $user, 'PRODUCT_MODIFY');
			header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
			exit;
		} else {
			$langs->load("errors");
			if ($result == -1) {
				$errors[] = 'ErrorBadBarCodeSyntax';
			} elseif ($result == -2) {
				$errors[] = 'ErrorBarCodeRequired';
			} elseif ($result == -3) {
				$errors[] = 'ErrorBarCodeAlreadyUsed';
			} else {
				$errors[] = 'FailedToValidateBarCode';
			}

			$error++;
			setEventMessages('', $errors, 'errors');
		}
	}

	// Quick edit for extrafields
	if ($action == 'update_extras') {
		$object->oldcopy = dol_clone($object, 2);

		// Fill array 'array_options' with data from update form
		$ret = $extrafields->setOptionalsFromPost(null, $object, GETPOST('attribute', 'restricthtml'));
		if ($ret < 0) {
			$error++;
		}

		if (!$error) {
			// Actions on extra fields
			$result = $object->insertExtraFields('PRODUCT_MODIFY');
			if ($result < 0) {
				setEventMessages($object->error, $object->errors, 'errors');
				$error++;
			}
		}

		if ($error) {
			$action = 'edit_extras';
		}
	}

	// Add a product or service
	if ($action == 'add' && $usercancreate) {
		$error = 0;

		if (!GETPOST('label', $label_security_check)) {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentities('Label')), null, 'errors');
			$action = "create";
			$error++;
		}
		if (empty($ref)) {
			if (!getDolGlobalString('PRODUCT_GENERATE_REF_AFTER_FORM')) {
				setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentities('ProductRef')), null, 'errors');
				$action = "create";
				$error++;
			}
		}
		if (!empty($duration_value) && empty($duration_unit)) {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentities('Unit')), null, 'errors');
			$action = "create";
			$error++;
		}

		if (!$error) {
			$units = GETPOST('units', 'int');

			$object->entity				= $conf->entity;
			$object->ref				= $ref;
			$object->label				= GETPOST('label', $label_security_check);
			$object->price_base_type	= GETPOST('price_base_type', 'aZ09');
			$object->mandatory_period	= !empty(GETPOST("mandatoryperiod", 'alpha')) ? 1 : 0;
			if ($object->price_base_type == 'TTC') {
				$object->price_ttc = GETPOST('price');
			} else {
				$object->price = GETPOST('price');
			}
			if ($object->price_base_type == 'TTC') {
				$object->price_min_ttc = GETPOST('price_min');
			} else {
				$object->price_min = GETPOST('price_min');
			}

			$tva_tx_txt = GETPOST('tva_tx', 'alpha'); // tva_tx can be '8.5'  or  '8.5*'  or  '8.5 (XXX)' or '8.5* (XXX)'

			// We must define tva_tx, npr and local taxes
			$vatratecode = '';
			$tva_tx = preg_replace('/[^0-9\.].*$/', '', $tva_tx_txt); // keep remove all after the numbers and dot
			$npr = preg_match('/\*/', $tva_tx_txt) ? 1 : 0;
			$localtax1 = 0;
			$localtax2 = 0;
			$localtax1_type = '0';
			$localtax2_type = '0';
			// If value contains the unique code of vat line (new recommanded method), we use it to find npr and local taxes
			$reg = array();
			if (preg_match('/\((.*)\)/', $tva_tx_txt, $reg)) {
				// We look into database using code (we can't use get_localtax() because it depends on buyer that is not known). Same in update price.
				$vatratecode = $reg[1];
				// Get record from code
				$sql = "SELECT t.rowid, t.code, t.recuperableonly, t.localtax1, t.localtax2, t.localtax1_type, t.localtax2_type";
				$sql .= " FROM ".MAIN_DB_PREFIX."c_tva as t, ".MAIN_DB_PREFIX."c_country as c";
				$sql .= " WHERE t.fk_pays = c.rowid AND c.code = '".$db->escape($mysoc->country_code)."'";
				$sql .= " AND t.taux = ".((float) $tva_tx)." AND t.active = 1";
				$sql .= " AND t.code = '".$db->escape($vatratecode)."'";
				$sql .= " AND t.entity IN (".getEntity('c_tva').")";
				$resql = $db->query($sql);
				if ($resql) {
					$obj = $db->fetch_object($resql);
					$npr = $obj->recuperableonly;
					$localtax1 = $obj->localtax1;
					$localtax2 = $obj->localtax2;
					$localtax1_type = $obj->localtax1_type;
					$localtax2_type = $obj->localtax2_type;
				}
			}

			$object->default_vat_code = $vatratecode;
			$object->tva_tx = $tva_tx;
			$object->tva_npr = $npr;
			$object->localtax1_tx = $localtax1;
			$object->localtax2_tx = $localtax2;
			$object->localtax1_type = $localtax1_type;
			$object->localtax2_type = $localtax2_type;

			$object->type               	 = $type;
			$object->status             	 = GETPOST('statut');
			$object->status_buy = GETPOST('statut_buy');
			$object->status_batch = GETPOST('status_batch');
			$object->batch_mask = GETPOST('batch_mask');

			$object->barcode_type = GETPOST('fk_barcode_type');
			$object->barcode = GETPOST('barcode');
			// Set barcode_type_xxx from barcode_type id
			$stdobject = new GenericObject($db);
			$stdobject->element = 'product';
			$stdobject->barcode_type = GETPOST('fk_barcode_type');
			$result = $stdobject->fetch_barcode();
			if ($result < 0) {
				$error++;
				$mesg = 'Failed to get bar code type information ';
				setEventMessages($mesg.$stdobject->error, $stdobject->errors, 'errors');
			}
			$object->barcode_type_code      = $stdobject->barcode_type_code;
			$object->barcode_type_coder     = $stdobject->barcode_type_coder;
			$object->barcode_type_label     = $stdobject->barcode_type_label;

			$object->description        	 = dol_htmlcleanlastbr(GETPOST('desc', 'restricthtml'));
			$object->url = GETPOST('url');
			$object->note_private          	 = dol_htmlcleanlastbr(GETPOST('note_private', 'restricthtml'));
			$object->note               	 = $object->note_private; // deprecated
			$object->customcode              = GETPOST('customcode', 'alphanohtml');
			$object->country_id = GETPOST('country_id', 'int');
			$object->state_id = GETPOST('state_id', 'int');
			$object->lifetime               = GETPOST('lifetime', 'int');
			$object->qc_frequency           = GETPOST('qc_frequency', 'int');
			$object->duration_value     	 = $duration_value;
			$object->duration_unit      	 = $duration_unit;
			$object->fk_default_warehouse	 = GETPOST('fk_default_warehouse', 'int');
			$object->fk_default_workstation	 = GETPOST('fk_default_workstation', 'int');
			$object->seuil_stock_alerte 	 = GETPOST('seuil_stock_alerte') ? GETPOST('seuil_stock_alerte') : 0;
			$object->desiredstock          = GETPOST('desiredstock') ? GETPOST('desiredstock') : 0;
			$object->canvas             	 = GETPOST('canvas');
			$object->net_measure           = GETPOST('net_measure');
			$object->net_measure_units     = GETPOST('net_measure_units'); // This is not the fk_unit but the power of unit
			$object->weight             	 = GETPOST('weight');
			$object->weight_units       	 = GETPOST('weight_units'); // This is not the fk_unit but the power of unit
			$object->length             	 = GETPOST('size');
			$object->length_units       	 = GETPOST('size_units'); // This is not the fk_unit but the power of unit
			$object->width = GETPOST('sizewidth');
			$object->height             	 = GETPOST('sizeheight');
			$object->surface            	 = GETPOST('surface');
			$object->surface_units      	 = GETPOST('surface_units'); // This is not the fk_unit but the power of unit
			$object->volume             	 = GETPOST('volume');
			$object->volume_units       	 = GETPOST('volume_units'); // This is not the fk_unit but the power of unit
			$finished = GETPOST('finished', 'int');
			if ($finished >= 0) {
				$object->finished = $finished;
			} else {
				$object->finished = null;
			}

			$units = GETPOST('units', 'int');
			if ($units > 0) {
				$object->fk_unit = $units;
			} else {
				$object->fk_unit = null;
			}

			$accountancy_code_sell = GETPOST('accountancy_code_sell', 'alpha');
			$accountancy_code_sell_intra = GETPOST('accountancy_code_sell_intra', 'alpha');
			$accountancy_code_sell_export = GETPOST('accountancy_code_sell_export', 'alpha');
			$accountancy_code_buy = GETPOST('accountancy_code_buy', 'alpha');
			$accountancy_code_buy_intra = GETPOST('accountancy_code_buy_intra', 'alpha');
			$accountancy_code_buy_export = GETPOST('accountancy_code_buy_export', 'alpha');

			if (empty($accountancy_code_sell) || $accountancy_code_sell == '-1') {
				$object->accountancy_code_sell = '';
			} else {
				$object->accountancy_code_sell = $accountancy_code_sell;
			}
			if (empty($accountancy_code_sell_intra) || $accountancy_code_sell_intra == '-1') {
				$object->accountancy_code_sell_intra = '';
			} else {
				$object->accountancy_code_sell_intra = $accountancy_code_sell_intra;
			}
			if (empty($accountancy_code_sell_export) || $accountancy_code_sell_export == '-1') {
				$object->accountancy_code_sell_export = '';
			} else {
				$object->accountancy_code_sell_export = $accountancy_code_sell_export;
			}
			if (empty($accountancy_code_buy) || $accountancy_code_buy == '-1') {
				$object->accountancy_code_buy = '';
			} else {
				$object->accountancy_code_buy = $accountancy_code_buy;
			}
			if (empty($accountancy_code_buy_intra) || $accountancy_code_buy_intra == '-1') {
				$object->accountancy_code_buy_intra = '';
			} else {
				$object->accountancy_code_buy_intra = $accountancy_code_buy_intra;
			}
			if (empty($accountancy_code_buy_export) || $accountancy_code_buy_export == '-1') {
				$object->accountancy_code_buy_export = '';
			} else {
				$object->accountancy_code_buy_export = $accountancy_code_buy_export;
			}

			// MultiPrix
			if (getDolGlobalString('PRODUIT_MULTIPRICES')) {
				for ($i = 2; $i <= $conf->global->PRODUIT_MULTIPRICES_LIMIT; $i++) {
					if (GETPOSTISSET("price_".$i)) {
						$object->multiprices["$i"] = price2num(GETPOST("price_".$i), 'MU');
						$object->multiprices_base_type["$i"] = GETPOST("multiprices_base_type_".$i);
					} else {
						$object->multiprices["$i"] = "";
					}
				}
			}

			// Fill array 'array_options' with data from add form
			$ret = $extrafields->setOptionalsFromPost(null, $object);
			if ($ret < 0) {
				$error++;
			}

			if (!$ref && getDolGlobalString('PRODUCT_GENERATE_REF_AFTER_FORM')) {
				// Generate ref...
				$ref = $modCodeProduct->getNextValue($object, $type);
			}

			if (!$error) {
				$id = $object->create($user);
			}

			if ($id > 0) {
				// Category association
				$categories = GETPOST('categories', 'array');
				$object->setCategories($categories);

				if (!empty($backtopage)) {
					$backtopage = preg_replace('/__ID__/', $object->id, $backtopage); // New method to autoselect parent project after a New on another form object creation
					$backtopage = preg_replace('/--IDFORBACKTOPAGE--/', $object->id, $backtopage); // New method to autoselect parent after a New on another form object creation
					if (preg_match('/\?/', $backtopage)) {
						$backtopage .= '&productid='.$object->id; // Old method
					}

					header("Location: ".$backtopage);
					exit;
				} else {
					header("Location: ".$_SERVER['PHP_SELF']."?id=".$id);
					exit;
				}
			} else {
				if (count($object->errors)) {
					setEventMessages($object->error, $object->errors, 'errors');
				} else {
					if ($object->error == 'ErrorProductAlreadyExists') {
						// allow to hook on ErrorProductAlreadyExists in any module
						$reshook = $hookmanager->executeHooks('onProductAlreadyExists', $parameters, $object, $action);
						if ($reshook < 0) {
							setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
						}
						if ($object->error) {
							// check again to prevent translation issue,
							// as error may have been cleared in hook function
							setEventMessages($langs->trans($object->error), null, 'errors');
						}
					} else {
						setEventMessages($langs->trans($object->error), null, 'errors');
					}
				}
				$action = "create";
			}
		}
	}

	// Update a product or service
	if ($action == 'update' && $usercancreate) {
		if (GETPOST('cancel', 'alpha')) {
			$action = '';
		} else {
			if ($object->id > 0) {
				//Need dol_clone methode 1 (same object class) because update product use hasbatch() method on oldcopy
				$object->oldcopy = dol_clone($object, 1);

				if (!getDolGlobalString('PRODUCT_GENERATE_REF_AFTER_FORM')) {
					$object->ref                = $ref;
				}
				$object->label                  = GETPOST('label', $label_security_check);

				$desc = dol_htmlcleanlastbr(preg_replace('/&nbsp;$/', '', GETPOST('desc', 'restricthtml')));
				$object->description            = $desc;

				$object->url = GETPOST('url');
				if (getDolGlobalString('MAIN_DISABLE_NOTES_TAB')) {
					$object->note_private = dol_htmlcleanlastbr(GETPOST('note_private', 'restricthtml'));
					$object->note = $object->note_private;
				}
				$object->customcode             = GETPOST('customcode', 'alpha');
				$object->country_id = GETPOST('country_id', 'int');
				$object->state_id = GETPOST('state_id', 'int');
				$object->lifetime               = GETPOST('lifetime', 'int');
				$object->qc_frequency           = GETPOST('qc_frequency', 'int');
				$object->status                 = GETPOST('statut', 'int');
				$object->status_buy             = GETPOST('statut_buy', 'int');
				$object->status_batch = GETPOST('status_batch', 'aZ09');
				$object->batch_mask = GETPOST('batch_mask', 'alpha');
				$object->fk_default_warehouse   = GETPOST('fk_default_warehouse', 'int');
				$object->fk_default_workstation   = GETPOST('fk_default_workstation', 'int');
				// removed from update view so GETPOST always empty
				/*
				$object->seuil_stock_alerte     = GETPOST('seuil_stock_alerte');
				$object->desiredstock           = GETPOST('desiredstock');
				*/
				$object->duration_value         = GETPOST('duration_value', 'int');
				$object->duration_unit          = GETPOST('duration_unit', 'alpha');

				$object->canvas                 = GETPOST('canvas');
				$object->net_measure            = GETPOST('net_measure');
				$object->net_measure_units      = GETPOST('net_measure_units'); // This is not the fk_unit but the power of unit
				$object->weight                 = GETPOST('weight');
				$object->weight_units           = GETPOST('weight_units'); // This is not the fk_unit but the power of unit
				$object->length                 = GETPOST('size');
				$object->length_units           = GETPOST('size_units'); // This is not the fk_unit but the power of unit
				$object->width = GETPOST('sizewidth');
				$object->height = GETPOST('sizeheight');

				$object->surface                = GETPOST('surface');
				$object->surface_units          = GETPOST('surface_units'); // This is not the fk_unit but the power of unit
				$object->volume                 = GETPOST('volume');
				$object->volume_units           = GETPOST('volume_units'); // This is not the fk_unit but the power of unit

				$finished = GETPOST('finished', 'int');
				if ($finished >= 0) {
					$object->finished = $finished;
				} else {
					$object->finished = null;
				}

				$fk_default_bom = GETPOST('fk_default_bom', 'int');
				if ($fk_default_bom >= 0) {
					$object->fk_default_bom = $fk_default_bom;
				} else {
					$object->fk_default_bom = null;
				}

				$units = GETPOST('units', 'int');
				if ($units > 0) {
					$object->fk_unit = $units;
				} else {
					$object->fk_unit = null;
				}

				$object->barcode_type = GETPOST('fk_barcode_type');
				$object->barcode = GETPOST('barcode');
				// Set barcode_type_xxx from barcode_type id
				$stdobject = new GenericObject($db);
				$stdobject->element = 'product';
				$stdobject->barcode_type = GETPOST('fk_barcode_type');
				$result = $stdobject->fetch_barcode();
				if ($result < 0) {
					$error++;
					$mesg = 'Failed to get bar code type information ';
					setEventMessages($mesg.$stdobject->error, $stdobject->errors, 'errors');
				}
				$object->barcode_type_code      = $stdobject->barcode_type_code;
				$object->barcode_type_coder     = $stdobject->barcode_type_coder;
				$object->barcode_type_label     = $stdobject->barcode_type_label;

				$accountancy_code_sell = GETPOST('accountancy_code_sell', 'alpha');
				$accountancy_code_sell_intra = GETPOST('accountancy_code_sell_intra', 'alpha');
				$accountancy_code_sell_export = GETPOST('accountancy_code_sell_export', 'alpha');
				$accountancy_code_buy = GETPOST('accountancy_code_buy', 'alpha');
				$accountancy_code_buy_intra = GETPOST('accountancy_code_buy_intra', 'alpha');
				$accountancy_code_buy_export = GETPOST('accountancy_code_buy_export', 'alpha');
				$checkmandatory = GETPOST('mandatoryperiod', 'alpha');
				if (empty($accountancy_code_sell) || $accountancy_code_sell == '-1') {
					$object->accountancy_code_sell = '';
				} else {
					$object->accountancy_code_sell = $accountancy_code_sell;
				}
				if (empty($accountancy_code_sell_intra) || $accountancy_code_sell_intra == '-1') {
					$object->accountancy_code_sell_intra = '';
				} else {
					$object->accountancy_code_sell_intra = $accountancy_code_sell_intra;
				}
				if (empty($accountancy_code_sell_export) || $accountancy_code_sell_export == '-1') {
					$object->accountancy_code_sell_export = '';
				} else {
					$object->accountancy_code_sell_export = $accountancy_code_sell_export;
				}
				if (empty($accountancy_code_buy) || $accountancy_code_buy == '-1') {
					$object->accountancy_code_buy = '';
				} else {
					$object->accountancy_code_buy = $accountancy_code_buy;
				}
				if (empty($accountancy_code_buy_intra) || $accountancy_code_buy_intra == '-1') {
					$object->accountancy_code_buy_intra = '';
				} else {
					$object->accountancy_code_buy_intra = $accountancy_code_buy_intra;
				}
				if (empty($accountancy_code_buy_export) || $accountancy_code_buy_export == '-1') {
					$object->accountancy_code_buy_export = '';
				} else {
					$object->accountancy_code_buy_export = $accountancy_code_buy_export;
				}
				if ($object->isService()) {
					$object->mandatory_period =  (!empty($checkmandatory)) ? 1 : 0 ;
				}



				// Fill array 'array_options' with data from add form
				$ret = $extrafields->setOptionalsFromPost(null, $object, '@GETPOSTISSET');
				if ($ret < 0) {
					$error++;
				}

				if (!$error && $object->check()) {
					if ($object->update($object->id, $user) > 0) {
						// Category association
						$categories = GETPOST('categories', 'array');
						$object->setCategories($categories);

						$action = 'view';
					} else {
						if (count($object->errors)) {
							setEventMessages($object->error, $object->errors, 'errors');
						} else {
							setEventMessages($langs->trans($object->error), null, 'errors');
						}
						$action = 'edit';
					}
				} else {
					if (count($object->errors)) {
						setEventMessages($object->error, $object->errors, 'errors');
					} else {
						setEventMessages($langs->trans("ErrorProductBadRefOrLabel"), null, 'errors');
					}
					$action = 'edit';
				}
			}
		}
	}

	// Action clone object
	if ($action == 'confirm_clone' && $confirm != 'yes') {
		$action = '';
	}
	if ($action == 'confirm_clone' && $confirm == 'yes' && $usercancreate) {
		if (!GETPOST('clone_content') && !GETPOST('clone_prices')) {
			setEventMessages($langs->trans("NoCloneOptionsSpecified"), null, 'errors');
		} else {
			if ($object->id > 0) {
				$error = 0;
				// We clone object to avoid to denaturate loaded object when setting some properties for clone or if createFromClone modifies the object.
				// We use native clone to keep this->db valid and allow to use later all the methods of object.
				$clone = dol_clone($object, 1);

				$clone->id = null;
				$clone->ref = GETPOST('clone_ref', 'alphanohtml');
				$clone->status = 0;
				$clone->status_buy = 0;
				$clone->barcode = -1;

				if ($clone->check()) {
					$db->begin();

					$clone->context['createfromclone'] = 'createfromclone';
					$id = $clone->create($user);
					if ($id > 0) {
						if (GETPOST('clone_composition')) {
							$result = $clone->clone_associations($object->id, $id);
							if ($result < 1) {
								setEventMessages($langs->trans('ErrorProductClone'), null, 'errors');
								setEventMessages($clone->error, $clone->errors, 'errors');
								$error++;
							}
						}

						if (!$error && GETPOST('clone_categories')) {
							$result = $clone->cloneCategories($object->id, $id);
							if ($result < 1) {
								setEventMessages($langs->trans('ErrorProductClone'), null, 'errors');
								setEventMessages($clone->error, $clone->errors, 'errors');
								$error++;
							}
						}

						if (!$error && GETPOST('clone_prices')) {
							$result = $clone->clone_price($object->id, $id);
							if ($result < 1) {
								setEventMessages($langs->trans('ErrorProductClone'), null, 'errors');
								setEventMessages($clone->error, $clone->errors, 'errors');
								$error++;
							}
						}

						// $clone->clone_fournisseurs($object->id, $id);
					} else {
						if ($clone->error == 'ErrorProductAlreadyExists') {
							$refalreadyexists++;
							$action = "";

							$mesg = $langs->trans("ErrorProductAlreadyExists", $clone->ref);
							$mesg .= ' <a href="' . $_SERVER["PHP_SELF"] . '?ref=' . $clone->ref . '">' . $langs->trans("ShowCardHere") . '</a>.';
							setEventMessages($mesg, null, 'errors');
						} else {
							if (count($clone->errors)) {
								setEventMessages($clone->error, $clone->errors, 'errors');
								dol_print_error($db, $clone->errors);
							} else {
								setEventMessages($langs->trans($clone->error), null, 'errors');
								dol_print_error($db, $clone->error);
							}
						}
						$error++;
					}

					unset($clone->context['createfromclone']);

					if ($error) {
						$db->rollback();
					} else {
						$db->commit();
						$db->close();
						header("Location: " . $_SERVER["PHP_SELF"] . "?id=" . $id);
						exit;
					}
				} else {
					setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("NewRefForClone")), null, 'errors');
				}
			} else {
				dol_print_error($db, $object->error);
			}
		}
		$action = 'clone';
	}

	// Delete a product
	if ($action == 'confirm_delete' && $confirm != 'yes') {
		$action = '';
	}
	if ($action == 'confirm_delete' && $confirm == 'yes' && $usercandelete) {
		$result = $object->delete($user);

		if ($result > 0) {
			header('Location: '.DOL_URL_ROOT.'/product/list.php?type='.$object->type.'&delprod='.urlencode($object->ref));
			exit;
		} else {
			setEventMessages($langs->trans($object->error), null, 'errors');
			$reload = 0;
			$action = '';
		}
	}


	// Add product into object
	if ($object->id > 0 && $action == 'addin') {
		$thirpdartyid = 0;
		if (GETPOST('propalid') > 0) {
			$propal = new Propal($db);
			$result = $propal->fetch(GETPOST('propalid'));
			if ($result <= 0) {
				dol_print_error($db, $propal->error);
				exit;
			}
			$thirpdartyid = $propal->socid;
		} elseif (GETPOST('commandeid') > 0) {
			$commande = new Commande($db);
			$result = $commande->fetch(GETPOST('commandeid'));
			if ($result <= 0) {
				dol_print_error($db, $commande->error);
				exit;
			}
			$thirpdartyid = $commande->socid;
		} elseif (GETPOST('factureid') > 0) {
			$facture = new Facture($db);
			$result = $facture->fetch(GETPOST('factureid'));
			if ($result <= 0) {
				dol_print_error($db, $facture->error);
				exit;
			}
			$thirpdartyid = $facture->socid;
		}

		if ($thirpdartyid > 0) {
			$soc = new Societe($db);
			$result = $soc->fetch($thirpdartyid);
			if ($result <= 0) {
				dol_print_error($db, $soc->error);
				exit;
			}

			$desc = $object->description;

			$tva_tx = get_default_tva($mysoc, $soc, $object->id);
			$tva_npr = get_default_npr($mysoc, $soc, $object->id);
			if (empty($tva_tx)) {
				$tva_npr = 0;
			}
			$localtax1_tx = get_localtax($tva_tx, 1, $soc, $mysoc, $tva_npr);
			$localtax2_tx = get_localtax($tva_tx, 2, $soc, $mysoc, $tva_npr);

			$pu_ht = $object->price;
			$pu_ttc = $object->price_ttc;
			$price_base_type = $object->price_base_type;

			// If multiprice
			if ($conf->global->PRODUIT_MULTIPRICES && $soc->price_level) {
				$pu_ht = $object->multiprices[$soc->price_level];
				$pu_ttc = $object->multiprices_ttc[$soc->price_level];
				$price_base_type = $object->multiprices_base_type[$soc->price_level];
			} elseif (getDolGlobalString('PRODUIT_CUSTOMER_PRICES')) {
				require_once DOL_DOCUMENT_ROOT.'/product/class/productcustomerprice.class.php';

				$prodcustprice = new ProductCustomerPrice($db);

				$filter = array('t.fk_product' => $object->id, 't.fk_soc' => $soc->id);

				$result = $prodcustprice->fetchAll('', '', 0, 0, $filter);
				if ($result) {
					if (count($prodcustprice->lines) > 0) {
						$pu_ht = price($prodcustprice->lines [0]->price);
						$pu_ttc = price($prodcustprice->lines [0]->price_ttc);
						$price_base_type = $prodcustprice->lines [0]->price_base_type;
						$tva_tx = $prodcustprice->lines [0]->tva_tx;
					}
				}
			}

			$tmpvat = price2num(preg_replace('/\s*\(.*\)/', '', $tva_tx));
			$tmpprodvat = price2num(preg_replace('/\s*\(.*\)/', '', $prod->tva_tx));

			// On reevalue prix selon taux tva car taux tva transaction peut etre different
			// de ceux du produit par defaut (par exemple si pays different entre vendeur et acheteur).
			if ($tmpvat != $tmpprodvat) {
				if ($price_base_type != 'HT') {
					$pu_ht = price2num($pu_ttc / (1 + ($tmpvat / 100)), 'MU');
				} else {
					$pu_ttc = price2num($pu_ht * (1 + ($tmpvat / 100)), 'MU');
				}
			}

			if (GETPOST('propalid') > 0) {
				// Define cost price for margin calculation
				$buyprice = 0;
				if (($result = $propal->defineBuyPrice($pu_ht, price2num(GETPOST('remise_percent'), '', 2), $object->id)) < 0) {
					dol_syslog($langs->trans('FailedToGetCostPrice'));
					setEventMessages($langs->trans('FailedToGetCostPrice'), null, 'errors');
				} else {
					$buyprice = $result;
				}

				$result = $propal->addline(
					$desc,
					$pu_ht,
					price2num(GETPOST('qty'), 'MS'),
					$tva_tx,
					$localtax1_tx, // localtax1
					$localtax2_tx, // localtax2
					$object->id,
					price2num(GETPOST('remise_percent'), '', 2),
					$price_base_type,
					$pu_ttc,
					0,
					0,
					-1,
					0,
					0,
					0,
					$buyprice,
					'',
					'',
					'',
					0,
					$object->fk_unit
				);
				if ($result > 0) {
					header("Location: ".DOL_URL_ROOT."/comm/propal/card.php?id=".$propal->id);
					return;
				}

				setEventMessages($langs->trans("ErrorUnknown").": $result", null, 'errors');
			} elseif (GETPOST('commandeid') > 0) {
				// Define cost price for margin calculation
				$buyprice = 0;
				if (($result = $commande->defineBuyPrice($pu_ht, price2num(GETPOST('remise_percent'), '', 2), $object->id)) < 0) {
					dol_syslog($langs->trans('FailedToGetCostPrice'));
					setEventMessages($langs->trans('FailedToGetCostPrice'), null, 'errors');
				} else {
					$buyprice = $result;
				}

				$result = $commande->addline(
					$desc,
					$pu_ht,
					price2num(GETPOST('qty'), 'MS'),
					$tva_tx,
					$localtax1_tx, // localtax1
					$localtax2_tx, // localtax2
					$object->id,
					price2num(GETPOST('remise_percent'), '', 2),
					'',
					'',
					$price_base_type,
					$pu_ttc,
					'',
					'',
					0,
					-1,
					0,
					0,
					null,
					$buyprice,
					'',
					0,
					$object->fk_unit
				);

				if ($result > 0) {
					header("Location: ".DOL_URL_ROOT."/commande/card.php?id=".urlencode($commande->id));
					exit;
				}
			} elseif (GETPOST('factureid') > 0) {
				// Define cost price for margin calculation
				$buyprice = 0;
				if (($result = $facture->defineBuyPrice($pu_ht, price2num(GETPOST('remise_percent'), '', 2), $object->id)) < 0) {
					dol_syslog($langs->trans('FailedToGetCostPrice'));
					setEventMessages($langs->trans('FailedToGetCostPrice'), null, 'errors');
				} else {
					$buyprice = $result;
				}

				$result = $facture->addline(
					$desc,
					$pu_ht,
					price2num(GETPOST('qty'), 'MS'),
					$tva_tx,
					$localtax1_tx,
					$localtax2_tx,
					$object->id,
					price2num(GETPOST('remise_percent'), '', 2),
					'',
					'',
					'',
					'',
					'',
					$price_base_type,
					$pu_ttc,
					Facture::TYPE_STANDARD,
					-1,
					0,
					'',
					0,
					0,
					null,
					$buyprice,
					'',
					0,
					100,
					'',
					$object->fk_unit
				);

				if ($result > 0) {
					header("Location: ".DOL_URL_ROOT."/compta/facture/card.php?facid=".$facture->id);
					exit;
				}
			}
		} else {
			$action = "";
			setEventMessages($langs->trans("WarningSelectOneDocument"), null, 'warnings');
		}
	}
}



/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);
$formproduct = new FormProduct($db);
$formcompany = new FormCompany($db);
if (isModEnabled('accounting')) {
	$formaccounting = new FormAccounting($db);
}


$title = $langs->trans('ProductServiceCard');

$help_url = '';
$shortlabel = dol_trunc($object->label, 16);
if (GETPOST("type") == '0' || ($object->type == Product::TYPE_PRODUCT)) {
	if ($action == 'create') {
		$title = $langs->trans("NewProduct");
	} else {
		$title = $langs->trans('Product')." ".$shortlabel." - ".$langs->trans('Card');
		$help_url = 'EN:Module_Products|FR:Module_Produits|ES:M&oacute;dulo_Productos|DE:Modul_Produkte';
	}
}
if (GETPOST("type") == '1' || ($object->type == Product::TYPE_SERVICE)) {
	if ($action == 'create') {
		$title = $langs->trans("NewService");
	} else {
		$title = $langs->trans('Service')." ".$shortlabel." - ".$langs->trans('Card');
		$help_url = 'EN:Module_Services_En|FR:Module_Services|ES:M&oacute;dulo_Servicios|DE:Modul_Leistungen';
	}
}

llxHeader('', $title, $help_url);

// Load object modBarCodeProduct
$res = 0;
if (isModEnabled('barcode') && getDolGlobalString('BARCODE_PRODUCT_ADDON_NUM')) {
	$module = strtolower($conf->global->BARCODE_PRODUCT_ADDON_NUM);
	$dirbarcode = array_merge(array('/core/modules/barcode/'), $conf->modules_parts['barcode']);
	foreach ($dirbarcode as $dirroot) {
		$res = dol_include_once($dirroot.$module.'.php');
		if ($res) {
			break;
		}
	}
	if ($res > 0) {
		$modBarCodeProduct = new $module();
	}
}

$canvasdisplayaction = $action;
if (in_array($canvasdisplayaction, array('merge', 'confirm_merge'))) {
	$canvasdisplayaction = 'view';
}

if (is_object($objcanvas) && $objcanvas->displayCanvasExists($canvasdisplayaction)) {
	// -----------------------------------------
	// When used with CANVAS
	// -----------------------------------------
	$objcanvas->assign_values($canvasdisplayaction, $object->id, $object->ref); // Set value for templates
	$objcanvas->display_canvas($canvasdisplayaction); // Show template
} else {
	// -----------------------------------------
	// When used in standard mode
	// -----------------------------------------
	if ($action == 'create' && $usercancreate) {
		//WYSIWYG Editor
		require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';

		if (!empty($conf->use_javascript_ajax)) {
			print '<script type="text/javascript">';
			print '$(document).ready(function () {
                        $("#selectcountry_id").change(function() {
                        	document.formprod.action.value="create";
                        	document.formprod.submit();
                        });
                     });';
			print '</script>'."\n";
		}

		// Load object modCodeProduct
		$module = (getDolGlobalString('PRODUCT_CODEPRODUCT_ADDON') ? $conf->global->PRODUCT_CODEPRODUCT_ADDON : 'mod_codeproduct_leopard');
		if (substr($module, 0, 16) == 'mod_codeproduct_' && substr($module, -3) == 'php') {
			$module = substr($module, 0, dol_strlen($module) - 4);
		}
		$result = dol_include_once('/core/modules/product/'.$module.'.php');
		if ($result > 0) {
			$modCodeProduct = new $module();
		}

		dol_set_focus('input[name="ref"]');

		print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST" name="formprod">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="action" value="add">';
		print '<input type="hidden" name="type" value="'.$type.'">'."\n";
		if (!empty($modCodeProduct->code_auto)) {
			print '<input type="hidden" name="code_auto" value="1">';
		}
		if (!empty($modBarCodeProduct->code_auto)) {
			print '<input type="hidden" name="barcode_auto" value="1">';
		}
		print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';

		if ($type == 1) {
			$picto = 'service';
			$title = $langs->trans("NewService");
		} else {
			$picto = 'product';
			$title = $langs->trans("NewProduct");
		}
		$linkback = "";
		print load_fiche_titre($title, $linkback, $picto);

		// We set country_id, country_code and country for the selected country
		$object->country_id = GETPOSTISSET('country_id') ? GETPOST('country_id', 'int') : null;
		if ($object->country_id > 0) {
			$tmparray = getCountry($object->country_id, 'all');
			$object->country_code = $tmparray['code'];
			$object->country = $tmparray['label'];
		}

		print dol_get_fiche_head('');

		// Call Hook tabContentCreateProduct
		$parameters = array();
		// Note that $action and $object may be modified by hook
		$reshook = $hookmanager->executeHooks('tabContentCreateProduct', $parameters, $object, $action);
		if (empty($reshook)) {
			print '<table class="border centpercent">';

			if (!getDolGlobalString('PRODUCT_GENERATE_REF_AFTER_FORM')) {
				print '<tr>';
				$tmpcode = '';
				if (!empty($modCodeProduct->code_auto)) {
					$tmpcode = $modCodeProduct->getNextValue($object, $type);
				}
				print '<td class="titlefieldcreate fieldrequired">'.$langs->trans("ProductRef").'</td><td><input id="ref" name="ref" class="maxwidth200" maxlength="128" value="'.dol_escape_htmltag(GETPOSTISSET('ref') ? GETPOST('ref', 'alphanohtml') : $tmpcode).'">';
				if ($refalreadyexists) {
					print $langs->trans("RefAlreadyExists");
				}
				print '</td></tr>';
			}

			// Label
			print '<tr><td class="fieldrequired">'.$langs->trans("Label").'</td><td><input name="label" class="minwidth300 maxwidth400onsmartphone" maxlength="255" value="'.dol_escape_htmltag(GETPOST('label', $label_security_check)).'"></td></tr>';

			// On sell
			print '<tr><td class="fieldrequired">'.$langs->trans("Status").' ('.$langs->trans("Sell").')</td><td>';
			$statutarray = array('1' => $langs->trans("OnSell"), '0' => $langs->trans("NotOnSell"));
			print $form->selectarray('statut', $statutarray, GETPOST('statut'));
			print '</td></tr>';

			// To buy
			print '<tr><td class="fieldrequired">'.$langs->trans("Status").' ('.$langs->trans("Buy").')</td><td>';
			$statutarray = array('1' => $langs->trans("ProductStatusOnBuy"), '0' => $langs->trans("ProductStatusNotOnBuy"));
			print $form->selectarray('statut_buy', $statutarray, GETPOST('statut_buy'));
			print '</td></tr>';

			// Batch number management
			if (isModEnabled('productbatch')) {
				print '<tr><td>'.$langs->trans("ManageLotSerial").'</td><td>';
				$statutarray = array('0' => $langs->trans("ProductStatusNotOnBatch"), '1' => $langs->trans("ProductStatusOnBatch"), '2' => $langs->trans("ProductStatusOnSerial"));
				print $form->selectarray('status_batch', $statutarray, GETPOST('status_batch'));
				print '</td></tr>';
				// Product specific batch number management
				$status_batch = GETPOST('status_batch');
				if ($status_batch !== '0') {
					$langs->load("admin");
					$tooltip = $langs->trans("GenericMaskCodes", $langs->transnoentities("Batch"), $langs->transnoentities("Batch"));
					$tooltip .= '<br>'.$langs->trans("GenericMaskCodes2");
					$tooltip .= '<br>'.$langs->trans("GenericMaskCodes3");
					$tooltip .= '<br>'.$langs->trans("GenericMaskCodes4a", $langs->transnoentities("Batch"), $langs->transnoentities("Batch"));
					$tooltip .= '<br>'.$langs->trans("GenericMaskCodes5");
					if ((getDolGlobalString('PRODUCTBATCH_LOT_ADDON') == 'mod_lot_advanced')
						|| (getDolGlobalString('PRODUCTBATCH_SN_ADDON') == 'mod_sn_advanced')) {
						print '<tr><td id="mask_option">'.$langs->trans("ManageLotMask").'</td>';
						$inherited_mask_lot = getDolGlobalString('LOT_ADVANCED_MASK');
						$inherited_mask_sn = getDolGlobalString('SN_ADVANCED_MASK');
						print '<td id="field_mask">';
						print $form->textwithpicto('<input type="text" class="flat minwidth175" name="batch_mask" id="batch_mask_input">', $tooltip, 1, 1);
						print '<script type="text/javascript">
									$(document).ready(function() {
										$("#field_mask, #mask_option").addClass("hideobject");
										$("#status_batch").on("change", function () {
											console.log("We change batch status");
											var optionSelected = $("option:selected", this);
											var valueSelected = this.value;
											$("#field_mask, #mask_option").addClass("hideobject");
						';
						if (getDolGlobalString('PRODUCTBATCH_LOT_USE_PRODUCT_MASKS') && getDolGlobalString('PRODUCTBATCH_LOT_ADDON') == 'mod_lot_advanced') {
							print '
											if (this.value == 1) {
												$("#field_mask, #mask_option").toggleClass("hideobject");
												$("#batch_mask_input").val("'.$inherited_mask_lot.'");
											}
							';
						}
						if (isset($conf->global->PRODUCTBATCH_SN_USE_PRODUCT_MASKS) && getDolGlobalString('PRODUCTBATCH_SN_ADDON') == 'mod_sn_advanced') {
							print '
											if (this.value == 2) {
												$("#field_mask, #mask_option").toggleClass("hideobject");
												$("#batch_mask_input").val("'.$inherited_mask_sn.'");
											}
							';
						}
						print '
										})
									})
								</script>';
						print '</td></tr>';
					}
				}
			}

			$showbarcode = isModEnabled('barcode');
			if (getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && !$user->hasRight('barcode', 'lire_advance')) {
				$showbarcode = 0;
			}

			if ($showbarcode) {
				print '<tr><td>'.$langs->trans('BarcodeType').'</td><td>';
				if (GETPOSTISSET('fk_barcode_type')) {
					$fk_barcode_type = GETPOST('fk_barcode_type') ? GETPOST('fk_barcode_type') : 0;
				} else {
					if (empty($fk_barcode_type) && getDolGlobalString('PRODUIT_DEFAULT_BARCODE_TYPE')) {
						$fk_barcode_type = getDolGlobalInt("PRODUIT_DEFAULT_BARCODE_TYPE");
					} else {
						$fk_barcode_type=0;
					}
				}
				require_once DOL_DOCUMENT_ROOT.'/core/class/html.formbarcode.class.php';
				$formbarcode = new FormBarCode($db);
				print $formbarcode->selectBarcodeType($fk_barcode_type, 'fk_barcode_type', 1);
				print '</td>';
				print '</tr><tr>';
				print '<td>'.$langs->trans("BarcodeValue").'</td><td>';
				$tmpcode = GETPOSTISSET('barcode') ? GETPOST('barcode') : $object->barcode;
				if (empty($tmpcode) && !empty($modBarCodeProduct->code_auto)) {
					$tmpcode = $modBarCodeProduct->getNextValue($object, $fk_barcode_type);
				}
				print img_picto('', 'barcode', 'class="pictofixedwidth"');
				print '<input class="maxwidth100" type="text" name="barcode" value="'.dol_escape_htmltag($tmpcode).'">';
				print '</td></tr>';
			}

			// Description (used in invoice, propal...)
			print '<tr><td class="tdtop">'.$langs->trans("Description").'</td><td>';
			$doleditor = new DolEditor('desc', GETPOST('desc', 'restricthtml'), '', 160, 'dolibarr_details', '', false, true, getDolGlobalString('FCKEDITOR_ENABLE_DETAILS'), ROWS_4, '90%');
			$doleditor->Create();
			print "</td></tr>";

			if (!getDolGlobalString('PRODUCT_DISABLE_PUBLIC_URL')) {
				// Public URL
				print '<tr><td>'.$langs->trans("PublicUrl").'</td><td>';
				print img_picto('', 'globe', 'class="pictofixedwidth"');
				print '<input type="text" name="url" class="quatrevingtpercent" value="'.GETPOST('url').'">';
				print '</td></tr>';
			}

			if (($type != 1 || getDolGlobalInt('STOCK_SUPPORTS_SERVICES')) && isModEnabled('stock')) {
				// Default warehouse
				print '<tr><td>'.$langs->trans("DefaultWarehouse").'</td><td>';
				print img_picto($langs->trans("DefaultWarehouse"), 'stock', 'class="pictofixedwidth"');
				print $formproduct->selectWarehouses(GETPOST('fk_default_warehouse', 'int'), 'fk_default_warehouse', 'warehouseopen', 1, 0, 0, '', 0, 0, array(), 'minwidth300 widthcentpercentminusxx maxwidth500');
				print ' <a href="'.DOL_URL_ROOT.'/product/stock/card.php?action=create&token='.newToken().'&backtopage='.urlencode($_SERVER['PHP_SELF'].'?&action=create&type='.GETPOST('type', 'int')).'">';
				print '<span class="fa fa-plus-circle valignmiddle paddingleft" title="'.$langs->trans("AddWarehouse").'"></span>';
				print '</a>';

				print '</td>';
				print '</tr>';

				if (!getDolGlobalString('PRODUCT_DISABLE_STOCK_LEVELS')) {
					// Stock min level
					print '<tr><td>'.$form->textwithpicto($langs->trans("StockLimit"), $langs->trans("StockLimitDesc"), 1).'</td><td>';
					print '<input name="seuil_stock_alerte" class="maxwidth50" value="'.GETPOST('seuil_stock_alerte').'">';
					print '</td>';
					print '</tr>';

					// Stock desired level
					print '<tr><td>'.$form->textwithpicto($langs->trans("DesiredStock"), $langs->trans("DesiredStockDesc"), 1).'</td><td>';
					print '<input name="desiredstock" class="maxwidth50" value="'.GETPOST('desiredstock').'">';
					print '</td></tr>';
				}
			} else {
				if (!getDolGlobalString('PRODUCT_DISABLE_STOCK_LEVELS')) {
					print '<input name="seuil_stock_alerte" type="hidden" value="0">';
					print '<input name="desiredstock" type="hidden" value="0">';
				}
			}

			if ($type == $object::TYPE_SERVICE && isModEnabled("workstation")) {
				// Default workstation
				print '<tr><td>'.$langs->trans("DefaultWorkstation").'</td><td>';
				print img_picto($langs->trans("DefaultWorkstation"), 'workstation', 'class="pictofixedwidth"');
				print $formproduct->selectWorkstations($object->fk_default_workstation, 'fk_default_workstation', 1);
				print '</td></tr>';
			}

			// Duration
			if ($type == 1) {
				print '<tr><td>'.$langs->trans("Duration").'</td><td>';
				print img_picto('', 'clock', 'class="pictofixedwidth"');
				print '<input name="duration_value" size="4" value="'.GETPOST('duration_value', 'int').'">';
				print $formproduct->selectMeasuringUnits("duration_unit", "time", (GETPOSTISSET('duration_unit') ? GETPOST('duration_unit', 'alpha') : 'h'), 0, 1);

				// Mandatory period
				print ' &nbsp; &nbsp; &nbsp; ';
				print '<input type="checkbox" id="mandatoryperiod" name="mandatoryperiod"'.($object->mandatory_period == 1 ? ' checked="checked"' : '').'>';
				print '<label for="mandatoryperiod">';
				$htmltooltip = $langs->trans("mandatoryHelper");
				print $form->textwithpicto($langs->trans("mandatoryperiod"), $htmltooltip, 1, 0);
				print '</label>';

				print '</td></tr>';
			}

			if ($type != 1) {	// Nature, Weight and volume only applies to products and not to services
				if (!getDolGlobalString('PRODUCT_DISABLE_NATURE')) {
					// Nature
					print '<tr><td>'.$form->textwithpicto($langs->trans("NatureOfProductShort"), $langs->trans("NatureOfProductDesc")).'</td><td>';
					print $formproduct->selectProductNature('finished', $object->finished);
					print '</td></tr>';
				}
			}

			if ($type != 1) {
				if (!getDolGlobalString('PRODUCT_DISABLE_WEIGHT')) {
					// Brut Weight
					print '<tr><td>'.$langs->trans("Weight").'</td><td>';
					print img_picto('', 'fa-balance-scale', 'class="pictofixedwidth"');
					print '<input name="weight" size="4" value="'.GETPOST('weight').'">';
					print $formproduct->selectMeasuringUnits("weight_units", "weight", GETPOSTISSET('weight_units') ? GETPOST('weight_units', 'alpha') : (!getDolGlobalString('MAIN_WEIGHT_DEFAULT_UNIT') ? 0 : $conf->global->MAIN_WEIGHT_DEFAULT_UNIT), 0, 2);
					print '</td></tr>';
				}

				// Brut Length
				if (!getDolGlobalString('PRODUCT_DISABLE_SIZE')) {
					print '<tr><td>'.$langs->trans("Length").' x '.$langs->trans("Width").' x '.$langs->trans("Height").'</td><td>';
					print img_picto('', 'fa-ruler', 'class="pictofixedwidth"');
					print '<input name="size" class="width50" value="'.GETPOST('size').'"> x ';
					print '<input name="sizewidth" class="width50" value="'.GETPOST('sizewidth').'"> x ';
					print '<input name="sizeheight" class="width50" value="'.GETPOST('sizeheight').'">';
					print $formproduct->selectMeasuringUnits("size_units", "size", GETPOSTISSET('size_units') ? GETPOST('size_units', 'alpha') : '0', 0, 2);
					print '</td></tr>';
				}
				if (!getDolGlobalString('PRODUCT_DISABLE_SURFACE')) {
					// Brut Surface
					print '<tr><td>'.$langs->trans("Surface").'</td><td>';
					print '<input name="surface" size="4" value="'.GETPOST('surface').'">';
					print $formproduct->selectMeasuringUnits("surface_units", "surface", GETPOSTISSET('surface_units') ? GETPOST('surface_units', 'alpha') : '0', 0, 2);
					print '</td></tr>';
				}
				if (!getDolGlobalString('PRODUCT_DISABLE_VOLUME')) {
					// Brut Volume
					print '<tr><td>'.$langs->trans("Volume").'</td><td>';
					print '<input name="volume" size="4" value="'.GETPOST('volume').'">';
					print $formproduct->selectMeasuringUnits("volume_units", "volume", GETPOSTISSET('volume_units') ? GETPOST('volume_units', 'alpha') : '0', 0, 2);
					print '</td></tr>';
				}

				if (getDolGlobalString('PRODUCT_ADD_NET_MEASURE')) {
					// Net Measure
					print '<tr><td>'.$langs->trans("NetMeasure").'</td><td>';
					print '<input name="net_measure" size="4" value="'.GETPOST('net_measure').'">';
					print $formproduct->selectMeasuringUnits("net_measure_units", '', GETPOSTISSET('net_measure_units') ? GETPOST('net_measure_units', 'alpha') : (!getDolGlobalString('MAIN_WEIGHT_DEFAULT_UNIT') ? 0 : $conf->global->MAIN_WEIGHT_DEFAULT_UNIT), 0, 0);
					print '</td></tr>';
				}
			}

			// Units
			if (getDolGlobalString('PRODUCT_USE_UNITS')) {
				print '<tr><td>'.$langs->trans('DefaultUnitToShow').'</td>';
				print '<td>';
				print $form->selectUnits(empty($line->fk_unit) ? $conf->global->PRODUCT_USE_UNITS : $line->fk_unit, 'units');
				print '</td></tr>';
			}

			// Custom code
			if (!getDolGlobalString('PRODUCT_DISABLE_CUSTOM_INFO') && empty($type)) {
				print '<tr><td class="wordbreak">'.$langs->trans("CustomCode").'</td><td><input name="customcode" class="maxwidth100onsmartphone" value="'.GETPOST('customcode').'"></td></tr>';

				// Origin country
				print '<tr><td>'.$langs->trans("CountryOrigin").'</td>';
				print '<td>';
				print img_picto('', 'globe-americas', 'class="pictofixedwidth"');
				print $form->select_country((GETPOSTISSET('country_id') ? GETPOST('country_id') : $object->country_id), 'country_id', '', 0, 'minwidth300 widthcentpercentminusx maxwidth500');
				if ($user->admin) {
					print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
				}
				print '</td></tr>';

				// State
				if (!getDolGlobalString('PRODUCT_DISABLE_STATE')) {
					print '<tr>';
					if (getDolGlobalString('MAIN_SHOW_REGION_IN_STATE_SELECT') && (getDolGlobalInt('MAIN_SHOW_REGION_IN_STATE_SELECT') == 1 || getDolGlobalInt('MAIN_SHOW_REGION_IN_STATE_SELECT') == 2)) {
						print '<td>'.$form->editfieldkey('RegionStateOrigin', 'state_id', '', $object, 0).'</td><td>';
					} else {
						print '<td>'.$form->editfieldkey('StateOrigin', 'state_id', '', $object, 0).'</td><td>';
					}

					print img_picto('', 'state', 'class="pictofixedwidth"');
					print $formcompany->select_state($object->state_id, $object->country_code);
					print '</tr>';
				}
			}

			// Quality control
			if (getDolGlobalString('PRODUCT_LOT_ENABLE_QUALITY_CONTROL')) {
				print '<tr><td>'.$langs->trans("LifeTime").'</td><td><input name="lifetime" class="maxwidth50" value="'.GETPOST('lifetime').'"></td></tr>';
				print '<tr><td>'.$langs->trans("QCFrequency").'</td><td><input name="qc_frequency" class="maxwidth50" value="'.GETPOST('qc_frequency').'"></td></tr>';
			}

			// Other attributes
			$parameters = array('colspan' => ' colspan="2"', 'cols'=>2);
			$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
			print $hookmanager->resPrint;
			if (empty($reshook)) {
				print $object->showOptionals($extrafields, 'create', $parameters);
			}

			// Note (private, no output on invoices, propales...)
			//if (!empty($conf->global->MAIN_DISABLE_NOTES_TAB))       available in create mode
			//{
			print '<tr><td class="tdtop">'.$langs->trans("NoteNotVisibleOnBill").'</td><td>';

			// We use dolibarr_details as type of DolEditor here, because we must not accept images as description is included into PDF and not accepted by TCPDF.
			$doleditor = new DolEditor('note_private', GETPOST('note_private', 'restricthtml'), '', 140, 'dolibarr_details', '', false, true, getDolGlobalString('FCKEDITOR_ENABLE_NOTE_PRIVATE'), ROWS_8, '90%');
			$doleditor->Create();

			print "</td></tr>";
			//}

			if (isModEnabled('categorie')) {
				// Categories
				print '<tr><td>'.$langs->trans("Categories").'</td><td>';
				$cate_arbo = $form->select_all_categories(Categorie::TYPE_PRODUCT, '', 'parent', 64, 0, 1);
				print img_picto('', 'category', 'class="pictofixedwidth"').$form->multiselectarray('categories', $cate_arbo, GETPOST('categories', 'array'), '', 0, 'quatrevingtpercent widthcentpercentminusx', 0, 0);
				print "</td></tr>";
			}

			print '</table>';

			print '<hr>';

			if (!getDolGlobalString('PRODUCT_DISABLE_PRICES')) {
				if (getDolGlobalString('PRODUIT_MULTIPRICES')) {
					// We do no show price array on create when multiprices enabled.
					// We must set them on prices tab.
					print '<table class="border centpercent">';
					// VAT
					print '<tr><td class="titlefieldcreate">'.$langs->trans("VATRate").'</td><td>';
					$defaultva = get_default_tva($mysoc, $mysoc);
					print $form->load_tva("tva_tx", $defaultva, $mysoc, $mysoc, 0, 0, '', false, 1);
					print '</td></tr>';

					print '</table>';

					print '<br>';
				} else {
					print '<table class="border centpercent">';

					// Price
					print '<tr><td class="titlefieldcreate">'.$langs->trans("SellingPrice").'</td>';
					print '<td><input name="price" class="maxwidth50" value="'.$object->price.'">';
					print $form->selectPriceBaseType($conf->global->PRODUCT_PRICE_BASE_TYPE, "price_base_type");
					print '</td></tr>';

					// Min price
					print '<tr><td>'.$langs->trans("MinPrice").'</td>';
					print '<td><input name="price_min" class="maxwidth50" value="'.$object->price_min.'">';
					print '</td></tr>';

					// VAT
					print '<tr><td>'.$langs->trans("VATRate").'</td><td>';
					$defaultva = get_default_tva($mysoc, $mysoc);
					print $form->load_tva("tva_tx", $defaultva, $mysoc, $mysoc, 0, 0, '', false, 1);
					print '</td></tr>';

					print '</table>';

					print '<br>';
				}
			}

			// Accountancy codes
			print '<!-- accountancy codes -->'."\n";
			print '<table class="border centpercent">';

			if (!getDolGlobalString('PRODUCT_DISABLE_ACCOUNTING')) {
				if (isModEnabled('accounting')) {
					// Accountancy_code_sell
					print '<tr><td class="titlefieldcreate">'.$langs->trans("ProductAccountancySellCode").'</td>';
					print '<td>';
					if ($type == 0) {
						$accountancy_code_sell = (GETPOSTISSET('accountancy_code_sell') ? GETPOST('accountancy_code_sell', 'alpha') : getDolGlobalString("ACCOUNTING_PRODUCT_SOLD_ACCOUNT"));
					} else {
						$accountancy_code_sell = (GETPOSTISSET('accountancy_code_sell') ? GETPOST('accountancy_code_sell', 'alpha') : getDolGlobalString("ACCOUNTING_SERVICE_SOLD_ACCOUNT"));
					}
					print $formaccounting->select_account($accountancy_code_sell, 'accountancy_code_sell', 1, null, 1, 1, 'minwidth150 maxwidth300', 1);
					print '</td></tr>';

					// Accountancy_code_sell_intra
					if ($mysoc->isInEEC()) {
						print '<tr><td class="titlefieldcreate">'.$langs->trans("ProductAccountancySellIntraCode").'</td>';
						print '<td>';
						if ($type == 0) {
							$accountancy_code_sell_intra = (GETPOSTISSET('accountancy_code_sell_intra') ? GETPOST('accountancy_code_sell_intra', 'alpha') : getDolGlobalString("ACCOUNTING_PRODUCT_SOLD_INTRA_ACCOUNT"));
						} else {
							$accountancy_code_sell_intra = (GETPOSTISSET('accountancy_code_sell_intra') ? GETPOST('accountancy_code_sell_intra', 'alpha') : getDolGlobalString("ACCOUNTING_SERVICE_SOLD_INTRA_ACCOUNT"));
						}
						print $formaccounting->select_account($accountancy_code_sell_intra, 'accountancy_code_sell_intra', 1, null, 1, 1, 'minwidth150 maxwidth300', 1);
						print '</td></tr>';
					}

					// Accountancy_code_sell_export
					print '<tr><td class="titlefieldcreate">'.$langs->trans("ProductAccountancySellExportCode").'</td>';
					print '<td>';
					if ($type == 0) {
						$accountancy_code_sell_export = (GETPOST('accountancy_code_sell_export') ? GETPOST('accountancy_code_sell_export', 'alpha') : getDolGlobalString("ACCOUNTING_PRODUCT_SOLD_EXPORT_ACCOUNT"));
					} else {
						$accountancy_code_sell_export = (GETPOST('accountancy_code_sell_export') ? GETPOST('accountancy_code_sell_export', 'alpha') : getDolGlobalString("ACCOUNTING_SERVICE_SOLD_EXPORT_ACCOUNT"));
					}
					print $formaccounting->select_account($accountancy_code_sell_export, 'accountancy_code_sell_export', 1, null, 1, 1, 'minwidth150 maxwidth300', 1);
					print '</td></tr>';

					// Accountancy_code_buy
					print '<tr><td>'.$langs->trans("ProductAccountancyBuyCode").'</td>';
					print '<td>';
					if ($type == 0) {
						$accountancy_code_buy = (GETPOST('accountancy_code_buy', 'alpha') ? (GETPOST('accountancy_code_buy', 'alpha')) : getDolGlobalString("ACCOUNTING_PRODUCT_BUY_ACCOUNT"));
					} else {
						$accountancy_code_buy = (GETPOST('accountancy_code_buy', 'alpha') ? (GETPOST('accountancy_code_buy', 'alpha')) : getDolGlobalString("ACCOUNTING_SERVICE_BUY_ACCOUNT"));
					}
					print $formaccounting->select_account($accountancy_code_buy, 'accountancy_code_buy', 1, null, 1, 1, 'minwidth150 maxwidth300', 1);
					print '</td></tr>';

					// Accountancy_code_buy_intra
					if ($mysoc->isInEEC()) {
						print '<tr><td class="titlefieldcreate">'.$langs->trans("ProductAccountancyBuyIntraCode").'</td>';
						print '<td>';
						if ($type == 0) {
							$accountancy_code_buy_intra = (GETPOSTISSET('accountancy_code_buy_intra') ? GETPOST('accountancy_code_buy_intra', 'alpha') : getDolGlobalString("ACCOUNTING_PRODUCT_BUY_INTRA_ACCOUNT"));
						} else {
							$accountancy_code_buy_intra = (GETPOSTISSET('accountancy_code_buy_intra') ? GETPOST('accountancy_code_buy_intra', 'alpha') : getDolGlobalString("ACCOUNTING_SERVICE_BUY_INTRA_ACCOUNT"));
						}
						print $formaccounting->select_account($accountancy_code_buy_intra, 'accountancy_code_buy_intra', 1, null, 1, 1, 'minwidth150 maxwidth300', 1);
						print '</td></tr>';
					}

					// Accountancy_code_buy_export
					print '<tr><td class="titlefieldcreate">'.$langs->trans("ProductAccountancyBuyExportCode").'</td>';
					print '<td>';
					if ($type == 0) {
						$accountancy_code_buy_export = (GETPOST('accountancy_code_buy_export') ? GETPOST('accountancy_code_buy_export', 'alpha') : getDolGlobalString("ACCOUNTING_PRODUCT_BUY_EXPORT_ACCOUNT"));
					} else {
						$accountancy_code_buy_export = (GETPOST('accountancy_code_buy_export') ? GETPOST('accountancy_code_buy_export', 'alpha') : getDolGlobalString("ACCOUNTING_SERVICE_BUY_EXPORT_ACCOUNT"));
					}
					print $formaccounting->select_account($accountancy_code_buy_export, 'accountancy_code_buy_export', 1, null, 1, 1, 'minwidth150 maxwidth300', 1);
					print '</td></tr>';
				} else {// For external software
					if (!empty($accountancy_code_sell)) {
						$object->accountancy_code_sell = $accountancy_code_sell;
					}
					if (!empty($accountancy_code_sell_intra)) {
						$object->accountancy_code_sell_intra = $accountancy_code_sell_intra;
					}
					if (!empty($accountancy_code_sell_export)) {
						$object->accountancy_code_sell_export = $accountancy_code_sell_export;
					}
					if (!empty($accountancy_code_buy)) {
						$object->accountancy_code_buy = $accountancy_code_buy;
					}
					if (!empty($accountancy_code_buy_intra)) {
						$object->accountancy_code_buy_intra = $accountancy_code_buy_intra;
					}
					if (!empty($accountancy_code_buy_export)) {
						$object->accountancy_code_buy_export = $accountancy_code_buy_export;
					}

					// Accountancy_code_sell
					print '<tr><td class="titlefieldcreate">'.$langs->trans("ProductAccountancySellCode").'</td>';
					print '<td class="maxwidthonsmartphone"><input class="minwidth150" name="accountancy_code_sell" value="'.$object->accountancy_code_sell.'">';
					print '</td></tr>';

					// Accountancy_code_sell_intra
					if ($mysoc->isInEEC()) {
						print '<tr><td class="titlefieldcreate">'.$langs->trans("ProductAccountancySellIntraCode").'</td>';
						print '<td class="maxwidthonsmartphone"><input class="minwidth150" name="accountancy_code_sell_intra" value="'.$object->accountancy_code_sell_intra.'">';
						print '</td></tr>';
					}

					// Accountancy_code_sell_export
					print '<tr><td class="titlefieldcreate">'.$langs->trans("ProductAccountancySellExportCode").'</td>';
					print '<td class="maxwidthonsmartphone"><input class="minwidth150" name="accountancy_code_sell_export" value="'.$object->accountancy_code_sell_export.'">';
					print '</td></tr>';

					// Accountancy_code_buy
					print '<tr><td>'.$langs->trans("ProductAccountancyBuyCode").'</td>';
					print '<td class="maxwidthonsmartphone"><input class="minwidth150" name="accountancy_code_buy" value="'.$object->accountancy_code_buy.'">';
					print '</td></tr>';

					// Accountancy_code_buy_intra
					if ($mysoc->isInEEC()) {
						print '<tr><td class="titlefieldcreate">'.$langs->trans("ProductAccountancyBuyIntraCode").'</td>';
						print '<td class="maxwidthonsmartphone"><input class="minwidth150" name="accountancy_code_buy_intra" value="'.$object->accountancy_code_buy_intra.'">';
						print '</td></tr>';
					}

					// Accountancy_code_buy_export
					print '<tr><td class="titlefieldcreate">'.$langs->trans("ProductAccountancyBuyExportCode").'</td>';
					print '<td class="maxwidthonsmartphone"><input class="minwidth150" name="accountancy_code_buy_export" value="'.$object->accountancy_code_buy_export.'">';
					print '</td></tr>';
				}
			}
			print '</table>';
		}

		print dol_get_fiche_end();

		print $form->buttonsSaveCancel("Create");

		print '</form>';
	} elseif ($object->id > 0) {
		/*
		 * Product card
		 */

		// Fiche en mode edition
		if ($action == 'edit' && $usercancreate) {
			//WYSIWYG Editor
			require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';

			if (!empty($conf->use_javascript_ajax)) {
				print '<script type="text/javascript">';
				print '$(document).ready(function () {
                        $("#selectcountry_id").change(function () {
                        	document.formprod.action.value="edit";
                        	document.formprod.submit();
                        });
				});';
				print '</script>'."\n";
			}

			// We set country_id, country_code and country for the selected country
			$object->country_id = GETPOST('country_id') ? GETPOST('country_id') : $object->country_id;
			if ($object->country_id) {
				$tmparray = getCountry($object->country_id, 'all');
				$object->country_code = $tmparray['code'];
				$object->country = $tmparray['label'];
			}

			$type = $langs->trans('Product');
			if ($object->isService()) {
				$type = $langs->trans('Service');
			}
			// print load_fiche_titre($langs->trans('Modify').' '.$type.' : '.(is_object($object->oldcopy)?$object->oldcopy->ref:$object->ref), "");

			// Main official, simple, and not duplicated code
			print '<form action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'" method="POST" name="formprod">'."\n";
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<input type="hidden" name="action" value="update">';
			print '<input type="hidden" name="id" value="'.$object->id.'">';
			print '<input type="hidden" name="canvas" value="'.$object->canvas.'">';

			$head = product_prepare_head($object);
			$titre = $langs->trans("CardProduct".$object->type);
			$picto = ($object->type == Product::TYPE_SERVICE ? 'service' : 'product');
			print dol_get_fiche_head($head, 'card', $titre, 0, $picto);

			// Call Hook tabContentEditProduct
			$parameters = array();
			// Note that $action and $object may be modified by hook
			$reshook = $hookmanager->executeHooks('tabContentEditProduct', $parameters, $object, $action);

			if (empty($reshook)) {
				print '<table class="border allwidth">';

				// Ref
				if (!getDolGlobalString('MAIN_PRODUCT_REF_NOT_EDITABLE')) {
					print '<tr><td class="titlefieldcreate fieldrequired">'.$langs->trans("Ref").'</td><td colspan="3"><input name="ref" class="maxwidth200" maxlength="128" value="'.dol_escape_htmltag(GETPOSTISSET('ref') ? GETPOST('ref') : $object->ref).'"></td></tr>';
				} else {
					print '<tr><td class="titlefieldcreate fieldrequired">'.$langs->trans("Ref").'</td><td colspan="3"><input name="ref" class="maxwidth200" maxlength="128" value="'.dol_escape_htmltag($object->ref).'" readonly="true"></td></tr>';
				}

				// Label
				print '<tr><td class="fieldrequired">'.$langs->trans("Label").'</td><td colspan="3"><input name="label" class="minwidth300 maxwidth400onsmartphone" maxlength="255" value="'.dol_escape_htmltag(GETPOSTISSET('label') ? GETPOST('label') : $object->label).'"></td></tr>';

				// Status To sell
				print '<tr><td class="fieldrequired">'.$langs->trans("Status").' ('.$langs->trans("Sell").')</td><td colspan="3">';
				print '<select class="flat" name="statut">';
				if ((GETPOSTISSET('statut') && GETPOST('statut')) || (!GETPOSTISSET('statut') && $object->status)) {
					print '<option value="1" selected>'.$langs->trans("OnSell").'</option>';
					print '<option value="0">'.$langs->trans("NotOnSell").'</option>';
				} else {
					print '<option value="1">'.$langs->trans("OnSell").'</option>';
					print '<option value="0" selected>'.$langs->trans("NotOnSell").'</option>';
				}
				print '</select>';
				print '</td></tr>';

				// Status To Buy
				print '<tr><td class="fieldrequired">'.$langs->trans("Status").' ('.$langs->trans("Buy").')</td><td colspan="3">';
				print '<select class="flat" name="statut_buy">';
				if ((GETPOSTISSET('statut_buy') && GETPOST('statut_buy')) || (!GETPOSTISSET('statut_buy') && $object->status_buy)) {
					print '<option value="1" selected>'.$langs->trans("ProductStatusOnBuy").'</option>';
					print '<option value="0">'.$langs->trans("ProductStatusNotOnBuy").'</option>';
				} else {
					print '<option value="1">'.$langs->trans("ProductStatusOnBuy").'</option>';
					print '<option value="0" selected>'.$langs->trans("ProductStatusNotOnBuy").'</option>';
				}
				print '</select>';
				print '</td></tr>';

				// Batch number managment
				if (isModEnabled('productbatch')) {
					if ($object->isProduct() || getDolGlobalString('STOCK_SUPPORTS_SERVICES')) {
						print '<tr><td>'.$langs->trans("ManageLotSerial").'</td><td>';
						$statutarray = array('0' => $langs->trans("ProductStatusNotOnBatch"), '1' => $langs->trans("ProductStatusOnBatch"), '2' => $langs->trans("ProductStatusOnSerial"));

						print $form->selectarray('status_batch', $statutarray, GETPOSTISSET('status_batch') ? GETPOST('status_batch') : $object->status_batch);

						print '<span id="statusBatchWarning" class="warning" style="display: none;">';
						print img_warning().'&nbsp;'.$langs->trans("WarningConvertFromBatchToSerial").'</span>';

						print '<span id="statusBatchMouvToGlobal" class="warning" style="display: none;">';
						print img_warning().'&nbsp;'.$langs->trans("WarningTransferBatchStockMouvToGlobal").'</span>';

						if ($object->status_batch) {
							// Display message to make user know that all batch will be move into global stock
							print '<script type="text/javascript">
								$(document).ready(function() {
									console.log($("#statusBatchWarning"))
									$("#status_batch").on("change", function() {
										if ($("#status_batch")[0].value == 0){
											$("#statusBatchMouvToGlobal").show()
										} else {
											$("#statusBatchMouvToGlobal").hide()
										}
									})
								})</script>';

							// Display message to explain that if the product currently have a quantity higher or equal to 2, switching to this choice means we will still have a product with different objects of the same batch (while we want a unique serial number)
							if ($object->status_batch == 1) {
								print '<script type="text/javascript">
								$(document).ready(function() {
									console.log($("#statusBatchWarning"))
									$("#status_batch").on("change", function() {
										if ($("#status_batch")[0].value == 2){
											$("#statusBatchWarning").show()
										} else {
											$("#statusBatchWarning").hide()
										}
									})
								})</script>';
							}
						}

						print '</td></tr>';
						if (!empty($object->status_batch) || !empty($conf->use_javascript_ajax)) {
							$langs->load("admin");
							$tooltip = $langs->trans("GenericMaskCodes", $langs->transnoentities("Batch"), $langs->transnoentities("Batch"));
							$tooltip .= '<br>'.$langs->trans("GenericMaskCodes2");
							$tooltip .= '<br>'.$langs->trans("GenericMaskCodes3");
							$tooltip .= '<br>'.$langs->trans("GenericMaskCodes4a", $langs->transnoentities("Batch"), $langs->transnoentities("Batch"));
							$tooltip .= '<br>'.$langs->trans("GenericMaskCodes5");
							print '<tr><td id="mask_option">'.$langs->trans("ManageLotMask").'</td>';
							$mask = '';
							if ($object->status_batch == '1' && getDolGlobalString('PRODUCTBATCH_LOT_USE_PRODUCT_MASKS') && getDolGlobalString('PRODUCTBATCH_LOT_ADDON') == 'mod_lot_advanced') {
								$mask = !empty($object->batch_mask) ? $object->batch_mask : getDolGlobalString('LOT_ADVANCED_MASK');
							}
							if ($object->status_batch == '2' && getDolGlobalString('PRODUCTBATCH_SN_USE_PRODUCT_MASKS') && getDolGlobalString('PRODUCTBATCH_SN_ADDON') == 'mod_sn_advanced') {
								$mask = !empty($object->batch_mask) ? $object->batch_mask : getDolGlobalString('SN_ADVANCED_MASK');
							}
							$inherited_mask_lot = getDolGlobalString('LOT_ADVANCED_MASK');
							$inherited_mask_sn = getDolGlobalString('SN_ADVANCED_MASK');
							print '<td id="field_mask">';
							print $form->textwithpicto('<input type="text" class="flat minwidth175" name="batch_mask" id="batch_mask_input" value="'.$mask.'">', $tooltip, 1, 1);
							// Add javascript to sho/hide field for custom mask
							if (!empty($conf->use_javascript_ajax)) {
								print '<script type="text/javascript">
								$(document).ready(function() {
									$("#field_mask").parent().addClass("hideobject");
									var preselect = document.getElementById("status_batch");';
								if (getDolGlobalString('PRODUCTBATCH_SN_USE_PRODUCT_MASKS')) {
									print 'if (preselect.value == "2") {
											$("#field_mask").parent().removeClass("hideobject");
										}';
								}
								if (getDolGlobalString('PRODUCTBATCH_LOT_USE_PRODUCT_MASKS')) {
									print 'if (preselect.value == "1") {
											$("#field_mask").parent().removeClass("hideobject");
										}';
								}
								print '$("#status_batch").on("change", function () {
										var optionSelected = $("option:selected", this);
										var valueSelected = this.value;
										$("#field_mask").parent().addClass("hideobject");
								';
								if (getDolGlobalString('PRODUCTBATCH_LOT_USE_PRODUCT_MASKS') && getDolGlobalString('PRODUCTBATCH_LOT_ADDON') == 'mod_lot_advanced') {
									print '
										if (this.value == 1) {
											$("#field_mask").parent().removeClass("hideobject");
											$("#batch_mask_input").val("'.$inherited_mask_lot.'");
										}
									';
								}
								if (getDolGlobalString('PRODUCTBATCH_SN_USE_PRODUCT_MASKS') && getDolGlobalString('PRODUCTBATCH_SN_ADDON') == 'mod_sn_advanced') {
									print '
										if (this.value == 2) {
											$("#field_mask").parent().removeClass("hideobject");
											$("#batch_mask_input").val("'.$inherited_mask_sn.'");
										}
									';
								}
								print '
									})
								})
							</script>';
							}
							print '</td></tr>';
						}
					}
				}

				// Barcode
				$showbarcode = isModEnabled('barcode');
				if (getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && !$user->hasRight('barcode', 'lire_advance')) {
					$showbarcode = 0;
				}

				if ($showbarcode) {
					print '<tr><td>'.$langs->trans('BarcodeType').'</td><td>';
					if (GETPOSTISSET('fk_barcode_type')) {
						$fk_barcode_type = GETPOST('fk_barcode_type');
					} else {
						$fk_barcode_type = $object->barcode_type;
						if (empty($fk_barcode_type) && getDolGlobalString('PRODUIT_DEFAULT_BARCODE_TYPE')) {
							$fk_barcode_type = $conf->global->PRODUIT_DEFAULT_BARCODE_TYPE;
						}
					}
					require_once DOL_DOCUMENT_ROOT.'/core/class/html.formbarcode.class.php';
					$formbarcode = new FormBarCode($db);
					print $formbarcode->selectBarcodeType($fk_barcode_type, 'fk_barcode_type', 1);
					print '</td></tr>';
					print '<tr><td>'.$langs->trans("BarcodeValue").'</td><td>';
					$tmpcode = GETPOSTISSET('barcode') ? GETPOST('barcode') : $object->barcode;
					if (empty($tmpcode) && !empty($modBarCodeProduct->code_auto)) {
						$tmpcode = $modBarCodeProduct->getNextValue($object, $fk_barcode_type);
					}
					print '<input class="maxwidth150 maxwidthonsmartphone" type="text" name="barcode" value="'.dol_escape_htmltag($tmpcode).'">';
					print '</td></tr>';
				}

				// Description (used in invoice, propal...)
				print '<tr><td class="tdtop">'.$langs->trans("Description").'</td><td>';

				// We use dolibarr_details as type of DolEditor here, because we must not accept images as description is included into PDF and not accepted by TCPDF.
				$doleditor = new DolEditor('desc', GETPOSTISSET('desc') ? GETPOST('desc', 'restricthtml') : $object->description, '', 160, 'dolibarr_details', '', false, true, getDolGlobalInt('FCKEDITOR_ENABLE_DETAILS'), ROWS_4, '90%');
				$doleditor->Create();

				print "</td></tr>";
				print "\n";

				// Public Url
				if (!getDolGlobalString('PRODUCT_DISABLE_PUBLIC_URL')) {
					print '<tr><td>'.$langs->trans("PublicUrl").'</td><td>';
					print img_picto('', 'globe', 'class="pictofixedwidth"');
					print '<input type="text" name="url" class="maxwidth500 widthcentpercentminusx" value="'.(GETPOSTISSET('url') ? GETPOST('url') : $object->url).'">';
					print '</td></tr>';
				}

				// Stock
				if (($object->isProduct() || getDolGlobalInt('STOCK_SUPPORTS_SERVICES')) && isModEnabled('stock')) {
					// Default warehouse
					print '<tr><td>'.$langs->trans("DefaultWarehouse").'</td><td>';
					print img_picto($langs->trans("DefaultWarehouse"), 'stock', 'class="pictofixedwidth"');
					print $formproduct->selectWarehouses((GETPOSTISSET('fk_default_warehouse') ? GETPOST('fk_default_warehouse') : $object->fk_default_warehouse), 'fk_default_warehouse', 'warehouseopen', 1, 0, 0, '', 0, 0, array(), 'maxwidth500 widthcentpercentminusxx');
					print ' <a href="'.DOL_URL_ROOT.'/product/stock/card.php?action=create&amp;backtopage='.urlencode($_SERVER['PHP_SELF'].'?action=edit&id='.((int) $object->id)).'">';
					print '<span class="fa fa-plus-circle valignmiddle paddingleft" title="'.$langs->trans("AddWarehouse").'"></span></a>';
					print '</td></tr>';
					/*
					print "<tr>".'<td>'.$langs->trans("StockLimit").'</td><td>';
					print '<input name="seuil_stock_alerte" size="4" value="'.$object->seuil_stock_alerte.'">';
					print '</td>';

					print '<td>'.$langs->trans("DesiredStock").'</td><td>';
					print '<input name="desiredstock" size="4" value="'.$object->desiredstock.'">';
					print '</td></tr>';
					*/
				}

				if ($object->isService() && isModEnabled('workstation')) {
					// Default workstation
					print '<tr><td>'.$langs->trans("DefaultWorkstation").'</td><td>';
					print img_picto($langs->trans("DefaultWorkstation"), 'workstation', 'class="pictofixedwidth"');
					print $formproduct->selectWorkstations($object->fk_default_workstation, 'fk_default_workstation', 1);
					print '</td></tr>';
				}

				/*
				else
				{
					print '<input name="seuil_stock_alerte" type="hidden" value="'.$object->seuil_stock_alerte.'">';
					print '<input name="desiredstock" type="hidden" value="'.$object->desiredstock.'">';
				}*/

				if ($object->isService()) {
					// Duration
					print '<tr><td>'.$langs->trans("Duration").'</td><td>';
					print '<input name="duration_value" size="5" value="'.$object->duration_value.'"> ';
					print $formproduct->selectMeasuringUnits("duration_unit", "time", $object->duration_unit, 0, 1);

					// Mandatory period
					print ' &nbsp; &nbsp; &nbsp; ';
					print '<input type="checkbox" id="mandatoryperiod" name="mandatoryperiod"'.($object->mandatory_period == 1 ? ' checked="checked"' : '').'>';
					print '<label for="mandatoryperiod">';
					$htmltooltip = $langs->trans("mandatoryHelper");
					print $form->textwithpicto($langs->trans("mandatoryperiod"), $htmltooltip, 1, 0);
					print '</label>';

					print '</td></tr>';
				} else {
					if (!getDolGlobalString('PRODUCT_DISABLE_NATURE')) {
						// Nature
						print '<tr><td>'.$form->textwithpicto($langs->trans("NatureOfProductShort"), $langs->trans("NatureOfProductDesc")).'</td><td>';
						print $formproduct->selectProductNature('finished', (GETPOSTISSET('finished') ? GETPOST('finished') : $object->finished));
						print '</td></tr>';
					}
				}

				if (!$object->isService() && isModEnabled('bom')) {
					print '<tr><td>'.$form->textwithpicto($langs->trans("DefaultBOM"), $langs->trans("DefaultBOMDesc", $langs->transnoentitiesnoconv("Finished"))).'</td><td>';
					$bomkey = "Bom:bom/class/bom.class.php:0:(t.status:=:1) AND (t.fk_product:=:".((int) $object->id).')';
					print $form->selectForForms($bomkey, 'fk_default_bom', (GETPOSTISSET('fk_default_bom') ? GETPOST('fk_default_bom') : $object->fk_default_bom), 1);
					print '</td></tr>';
				}

				if (!$object->isService()) {
					if (!getDolGlobalString('PRODUCT_DISABLE_WEIGHT')) {
						// Brut Weight
						print '<tr><td>'.$langs->trans("Weight").'</td><td>';
						print '<input name="weight" size="5" value="'.(GETPOSTISSET('weight') ? GETPOST('weight') : $object->weight).'"> ';
						print $formproduct->selectMeasuringUnits("weight_units", "weight", GETPOSTISSET('weight_units') ? GETPOST('weight_units') : $object->weight_units, 0, 2);
						print '</td></tr>';
					}

					if (!getDolGlobalString('PRODUCT_DISABLE_SIZE')) {
						// Brut Length
						print '<tr><td>'.$langs->trans("Length").' x '.$langs->trans("Width").' x '.$langs->trans("Height").'</td><td>';
						print '<input name="size" size="5" value="'.(GETPOSTISSET('size') ? GETPOST('size') : $object->length).'">x';
						print '<input name="sizewidth" size="5" value="'.(GETPOSTISSET('sizewidth') ? GETPOST('sizewidth') : $object->width).'">x';
						print '<input name="sizeheight" size="5" value="'.(GETPOSTISSET('sizeheight') ? GETPOST('sizeheight') : $object->height).'"> ';
						print $formproduct->selectMeasuringUnits("size_units", "size", GETPOSTISSET('size_units') ? GETPOST('size_units') : $object->length_units, 0, 2);
						print '</td></tr>';
					}
					if (!getDolGlobalString('PRODUCT_DISABLE_SURFACE')) {
						// Brut Surface
						print '<tr><td>'.$langs->trans("Surface").'</td><td>';
						print '<input name="surface" size="5" value="'.(GETPOSTISSET('surface') ? GETPOST('surface') : $object->surface).'"> ';
						print $formproduct->selectMeasuringUnits("surface_units", "surface", GETPOSTISSET('surface_units') ? GETPOST('surface_units') : $object->surface_units, 0, 2);
						print '</td></tr>';
					}
					if (!getDolGlobalString('PRODUCT_DISABLE_VOLUME')) {
						// Brut Volume
						print '<tr><td>'.$langs->trans("Volume").'</td><td>';
						print '<input name="volume" size="5" value="'.(GETPOSTISSET('volume') ? GETPOST('volume') : $object->volume).'"> ';
						print $formproduct->selectMeasuringUnits("volume_units", "volume", GETPOSTISSET('volume_units') ? GETPOST('volume_units') : $object->volume_units, 0, 2);
						print '</td></tr>';
					}

					if (getDolGlobalString('PRODUCT_ADD_NET_MEASURE')) {
						// Net Measure
						print '<tr><td>'.$langs->trans("NetMeasure").'</td><td>';
						print '<input name="net_measure" size="5" value="'.(GETPOSTISSET('net_measure') ? GETPOST('net_measure') : $object->net_measure).'"> ';
						print $formproduct->selectMeasuringUnits("net_measure_units", "", GETPOSTISSET('net_measure_units') ? GETPOST('net_measure_units') : $object->net_measure_units, 0, 0);
						print '</td></tr>';
					}
				}
				// Units
				if (getDolGlobalString('PRODUCT_USE_UNITS')) {
					print '<tr><td>'.$langs->trans('DefaultUnitToShow').'</td>';
					print '<td>';
					print $form->selectUnits($object->fk_unit, 'units');
					print '</td></tr>';
				}

				// Custom code
				if (!$object->isService() && !getDolGlobalString('PRODUCT_DISABLE_CUSTOM_INFO')) {
					print '<tr><td class="wordbreak">'.$langs->trans("CustomCode").'</td><td><input name="customcode" class="maxwidth100onsmartphone" value="'.(GETPOSTISSET('customcode') ? GETPOST('customcode') : $object->customcode).'"></td></tr>';
					// Origin country
					print '<tr><td>'.$langs->trans("CountryOrigin").'</td>';
					print '<td>';
					print img_picto('', 'globe-americas', 'class="paddingrightonly"');
					print $form->select_country(GETPOSTISSET('country_id') ? GETPOST('country_id', 'int') : $object->country_id, 'country_id', '', 0, 'minwidth100 maxwidthonsmartphone');
					if ($user->admin) {
						print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
					}
					print '</td></tr>';

					// State
					if (!getDolGlobalString('PRODUCT_DISABLE_STATE')) {
						print '<tr>';
						if (getDolGlobalString('MAIN_SHOW_REGION_IN_STATE_SELECT') && (getDolGlobalInt('MAIN_SHOW_REGION_IN_STATE_SELECT') == 1 || getDolGlobalInt('MAIN_SHOW_REGION_IN_STATE_SELECT') == 2)) {
							print '<td>'.$form->editfieldkey('RegionStateOrigin', 'state_id', '', $object, 0).'</td><td>';
						} else {
							print '<td>'.$form->editfieldkey('StateOrigin', 'state_id', '', $object, 0).'</td><td>';
						}

						print img_picto('', 'state', 'class="pictofixedwidth"');
						print $formcompany->select_state(GETPOSTISSET('state_id') ? GETPOST('state_id', 'int') : $object->state_id, $object->country_code);
						print '</td>';
						print '</tr>';
					}
				}

				// Quality control
				if (getDolGlobalString('PRODUCT_LOT_ENABLE_QUALITY_CONTROL')) {
					print '<tr><td>'.$langs->trans("LifeTime").'</td><td><input name="lifetime" class="maxwidth100onsmartphone" value="'.$object->lifetime.'"></td></tr>';
					print '<tr><td>'.$langs->trans("QCFrequency").'</td><td><input name="qc_frequency" class="maxwidth100onsmartphone" value="'.$object->qc_frequency.'"></td></tr>';
				}

				// Other attributes
				$parameters = array('colspan' => ' colspan="2"', 'cols' => 2);
				$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
				print $hookmanager->resPrint;
				if (empty($reshook)) {
					print $object->showOptionals($extrafields, 'edit', $parameters);
				}

				// Tags-Categories
				if (isModEnabled('categorie')) {
					print '<tr><td>'.$langs->trans("Categories").'</td><td>';
					$cate_arbo = $form->select_all_categories(Categorie::TYPE_PRODUCT, '', 'parent', 64, 0, 1);
					$c = new Categorie($db);
					$cats = $c->containing($object->id, Categorie::TYPE_PRODUCT);
					$arrayselected = array();
					if (is_array($cats)) {
						foreach ($cats as $cat) {
							$arrayselected[] = $cat->id;
						}
					}
					if (GETPOSTISARRAY('categories')) {
						foreach (GETPOST('categories', 'array') as $cat) {
							$arrayselected[] = $cat;
						}
					}
					print img_picto('', 'category', 'class="pictofixedwidth"').$form->multiselectarray('categories', $cate_arbo, $arrayselected, '', 0, 'quatrevingtpercent widthcentpercentminusx', 0, 0);
					print "</td></tr>";
				}

				// Note private
				if (getDolGlobalString('MAIN_DISABLE_NOTES_TAB')) {
					print '<tr><td class="tdtop">'.$langs->trans("NoteNotVisibleOnBill").'</td><td>';

					$doleditor = new DolEditor('note_private', $object->note_private, '', 140, 'dolibarr_notes', '', false, true, getDolGlobalInt('FCKEDITOR_ENABLE_NOTE_PRIVATE'), ROWS_4, '90%');
					$doleditor->Create();

					print "</td></tr>";
				}

				print '</table>';

				print '<br>';

				print '<table class="border centpercent">';

				if (!getDolGlobalString('PRODUCT_DISABLE_ACCOUNTING')) {
					if (isModEnabled('accounting')) {
						// Accountancy_code_sell
						print '<tr><td class="titlefieldcreate">'.$langs->trans("ProductAccountancySellCode").'</td>';
						print '<td>';
						print $formaccounting->select_account((GETPOSTISSET('accountancy_code_sell') ? GETPOST('accountancy_code_sell') : $object->accountancy_code_sell), 'accountancy_code_sell', 1, '', 1, 1, 'minwidth150 maxwidth300');
						print '</td></tr>';

						// Accountancy_code_sell_intra
						if ($mysoc->isInEEC()) {
							print '<tr><td class="titlefieldcreate">'.$langs->trans("ProductAccountancySellIntraCode").'</td>';
							print '<td>';
							print $formaccounting->select_account((GETPOSTISSET('accountancy_code_sell_intra') ? GETPOST('accountancy_code_sell_intra') : $object->accountancy_code_sell_intra), 'accountancy_code_sell_intra', 1, '', 1, 1, 'minwidth150 maxwidth300');
							print '</td></tr>';
						}

						// Accountancy_code_sell_export
						print '<tr><td class="titlefieldcreate">'.$langs->trans("ProductAccountancySellExportCode").'</td>';
						print '<td>';
						print $formaccounting->select_account((GETPOSTISSET('accountancy_code_sell_export') ? GETPOST('accountancy_code_sell_export') : $object->accountancy_code_sell_export), 'accountancy_code_sell_export', 1, '', 1, 1, 'minwidth150 maxwidth300');
						print '</td></tr>';

						// Accountancy_code_buy
						print '<tr><td>'.$langs->trans("ProductAccountancyBuyCode").'</td>';
						print '<td>';
						print $formaccounting->select_account((GETPOSTISSET('accountancy_code_buy') ? GETPOST('accountancy_code_buy') : $object->accountancy_code_buy), 'accountancy_code_buy', 1, '', 1, 1, 'minwidth150 maxwidth300');
						print '</td></tr>';

						// Accountancy_code_buy_intra
						if ($mysoc->isInEEC()) {
							print '<tr><td class="titlefieldcreate">'.$langs->trans("ProductAccountancyBuyIntraCode").'</td>';
							print '<td>';
							print $formaccounting->select_account((GETPOSTISSET('accountancy_code_buy_intra') ? GETPOST('accountancy_code_buy_intra') : $object->accountancy_code_buy_intra), 'accountancy_code_buy_intra', 1, '', 1, 1, 'minwidth150 maxwidth300');
							print '</td></tr>';
						}

						// Accountancy_code_buy_export
						print '<tr><td class="titlefieldcreate">'.$langs->trans("ProductAccountancyBuyExportCode").'</td>';
						print '<td>';
						print $formaccounting->select_account((GETPOSTISSET('accountancy_code_buy_export') ? GETPOST('accountancy_code_buy_export') : $object->accountancy_code_buy_export), 'accountancy_code_buy_export', 1, '', 1, 1, 'minwidth150 maxwidth300');
						print '</td></tr>';
					} else {
						// For external software
						// Accountancy_code_sell
						print '<tr><td class="titlefieldcreate">'.$langs->trans("ProductAccountancySellCode").'</td>';
						print '<td><input name="accountancy_code_sell" class="maxwidth200" value="'.(GETPOSTISSET('accountancy_code_sell') ? GETPOST('accountancy_code_sell') : $object->accountancy_code_sell).'">';
						print '</td></tr>';

						// Accountancy_code_sell_intra
						if ($mysoc->isInEEC()) {
							print '<tr><td class="titlefieldcreate">'.$langs->trans("ProductAccountancySellIntraCode").'</td>';
							print '<td><input name="accountancy_code_sell_intra" class="maxwidth200" value="'.(GETPOSTISSET('accountancy_code_sell_intra') ? GETPOST('accountancy_code_sell_intra') : $object->accountancy_code_sell_intra).'">';
							print '</td></tr>';
						}

						// Accountancy_code_sell_export
						print '<tr><td class="titlefieldcreate">'.$langs->trans("ProductAccountancySellExportCode").'</td>';
						print '<td><input name="accountancy_code_sell_export" class="maxwidth200" value="'.(GETPOSTISSET('accountancy_code_sell_export') ? GETPOST('accountancy_code_sell_export') : $object->accountancy_code_sell_export).'">';
						print '</td></tr>';

						// Accountancy_code_buy
						print '<tr><td>'.$langs->trans("ProductAccountancyBuyCode").'</td>';
						print '<td><input name="accountancy_code_buy" class="maxwidth200" value="'.(GETPOSTISSET('accountancy_code_buy') ? GETPOST('accountancy_code_buy') : $object->accountancy_code_buy).'">';
						print '</td></tr>';

						// Accountancy_code_buy_intra
						if ($mysoc->isInEEC()) {
							print '<tr><td class="titlefieldcreate">'.$langs->trans("ProductAccountancyBuyIntraCode").'</td>';
							print '<td><input name="accountancy_code_buy_intra" class="maxwidth200" value="'.(GETPOSTISSET('accountancy_code_buy_intra') ? GETPOST('accountancy_code_buy_intra') : $object->accountancy_code_buy_intra).'">';
							print '</td></tr>';
						}

						// Accountancy_code_buy_export
						print '<tr><td class="titlefieldcreate">'.$langs->trans("ProductAccountancyBuyExportCode").'</td>';
						print '<td><input name="accountancy_code_buy_export" class="maxwidth200" value="'.(GETPOSTISSET('accountancy_code_buy_export') ? GETPOST('accountancy_code_buy_export') : $object->accountancy_code_buy_export).'">';
						print '</td></tr>';
					}
				}
				print '</table>';
			}

			print dol_get_fiche_end();

			print $form->buttonsSaveCancel();

			print '</form>';
		} else {
			// Fiche en mode visu

			$showbarcode = isModEnabled('barcode');
			if (getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && !$user->hasRight('barcode', 'lire_advance')) {
				$showbarcode = 0;
			}

			$head = product_prepare_head($object);
			$titre = $langs->trans("CardProduct".$object->type);
			$picto = ($object->type == Product::TYPE_SERVICE ? 'service' : 'product');

			print dol_get_fiche_head($head, 'card', $titre, -1, $picto);

			$linkback = '<a href="'.DOL_URL_ROOT.'/product/list.php?restore_lastsearch_values=1&type='.$object->type.'">'.$langs->trans("BackToList").'</a>';
			$object->next_prev_filter = "fk_product_type = ".((int) $object->type);

			$shownav = 1;
			if ($user->socid && !in_array('product', explode(',', getDolGlobalString('MAIN_MODULES_FOR_EXTERNAL')))) {
				$shownav = 0;
			}

			dol_banner_tab($object, 'ref', $linkback, $shownav, 'ref');

			// Call Hook tabContentViewProduct
			$parameters = array();
			// Note that $action and $object may be modified by hook
			$reshook = $hookmanager->executeHooks('tabContentViewProduct', $parameters, $object, $action);
			if (empty($reshook)) {
				print '<div class="fichecenter">';
				print '<div class="fichehalfleft">';

				print '<div class="underbanner clearboth"></div>';
				print '<table class="border tableforfield centpercent">';

				// Type
				if (isModEnabled("product") && isModEnabled("service")) {
					$typeformat = 'select;0:'.$langs->trans("Product").',1:'.$langs->trans("Service");
					print '<tr><td class="titlefield">';
					print (!getDolGlobalString('PRODUCT_DENY_CHANGE_PRODUCT_TYPE')) ? $form->editfieldkey("Type", 'fk_product_type', $object->type, $object, $usercancreate, $typeformat) : $langs->trans('Type');
					print '</td><td>';
					print $form->editfieldval("Type", 'fk_product_type', $object->type, $object, $usercancreate, $typeformat);
					print '</td></tr>';
				}

				if ($showbarcode) {
					// Barcode type
					print '<tr><td class="nowrap">';
					print '<table width="100%" class="nobordernopadding"><tr><td class="nowrap">';
					print $langs->trans("BarcodeType");
					print '</td>';
					if (($action != 'editbarcodetype') && $usercancreate && $createbarcode) {
						print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editbarcodetype&id='.$object->id.'&token='.newToken().'">'.img_edit($langs->trans('Edit'), 1).'</a></td>';
					}
					print '</tr></table>';
					print '</td><td>';
					if ($action == 'editbarcodetype' || $action == 'editbarcode') {
						require_once DOL_DOCUMENT_ROOT.'/core/class/html.formbarcode.class.php';
						$formbarcode = new FormBarCode($db);
					}

					$fk_barcode_type = '';
					if ($action == 'editbarcodetype') {
						print $formbarcode->formBarcodeType($_SERVER['PHP_SELF'].'?id='.$object->id, $object->barcode_type, 'fk_barcode_type');
						$fk_barcode_type = $object->barcode_type;
					} else {
						$object->fetch_barcode();
						$fk_barcode_type = $object->barcode_type;
						print $object->barcode_type_label ? $object->barcode_type_label : ($object->barcode ? '<div class="warning">'.$langs->trans("SetDefaultBarcodeType").'<div>' : '');
					}
					print '</td></tr>'."\n";

					// Barcode value
					print '<tr><td class="nowrap">';
					print '<table width="100%" class="nobordernopadding"><tr><td class="nowrap">';
					print $langs->trans("BarcodeValue");
					print '</td>';
					if (($action != 'editbarcode') && $usercancreate && $createbarcode) {
						print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editbarcode&id='.$object->id.'&token='.newToken().'">'.img_edit($langs->trans('Edit'), 1).'</a></td>';
					}
					print '</tr></table>';
					print '</td><td>';
					if ($action == 'editbarcode') {
						$tmpcode = GETPOSTISSET('barcode') ? GETPOST('barcode') : $object->barcode;
						if (empty($tmpcode) && !empty($modBarCodeProduct->code_auto)) {
							$tmpcode = $modBarCodeProduct->getNextValue($object, $fk_barcode_type);
						}

						print '<form method="post" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'">';
						print '<input type="hidden" name="token" value="'.newToken().'">';
						print '<input type="hidden" name="action" value="setbarcode">';
						print '<input type="hidden" name="barcode_type_code" value="'.$object->barcode_type_code.'">';
						print '<input class="width300" class="maxwidthonsmartphone" type="text" name="barcode" value="'.$tmpcode.'">';
						print '&nbsp;<input type="submit" class="button smallpaddingimp" value="'.$langs->trans("Modify").'">';
						print '</form>';
					} else {
						print showValueWithClipboardCPButton($object->barcode);
					}
					print '</td></tr>'."\n";
				}

				// Batch number management (to batch)
				if (isModEnabled('productbatch')) {
					if ($object->isProduct() || getDolGlobalString('STOCK_SUPPORTS_SERVICES')) {
						print '<tr><td>'.$langs->trans("ManageLotSerial").'</td><td>';
						print $object->getLibStatut(0, 2);
						print '</td></tr>';
						if ((($object->status_batch == '1' && getDolGlobalString('PRODUCTBATCH_LOT_USE_PRODUCT_MASKS') && getDolGlobalString('PRODUCTBATCH_LOT_ADDON') == 'mod_lot_advanced')
							|| ($object->status_batch == '2' && getDolGlobalString('PRODUCTBATCH_SN_ADDON') == 'mod_sn_advanced' && getDolGlobalString('PRODUCTBATCH_SN_USE_PRODUCT_MASKS')))) {
							print '<tr><td>'.$langs->trans("ManageLotMask").'</td><td>';
							print $object->batch_mask;
							print '</td></tr>';
						}
					}
				}

				if (!getDolGlobalString('PRODUCT_DISABLE_ACCOUNTING')) {
					// Accountancy sell code
					print '<tr><td class="nowrap">';
					print $langs->trans("ProductAccountancySellCode");
					print '</td><td>';
					if (isModEnabled('accounting')) {
						if (!empty($object->accountancy_code_sell)) {
							$accountingaccount = new AccountingAccount($db);
							$accountingaccount->fetch('', $object->accountancy_code_sell, 1);

							print $accountingaccount->getNomUrl(0, 1, 1, '', 1);
						}
					} else {
						print $object->accountancy_code_sell;
					}
					print '</td></tr>';

					// Accountancy sell code intra-community
					if ($mysoc->isInEEC()) {
						print '<tr><td class="nowrap">';
						print $langs->trans("ProductAccountancySellIntraCode");
						print '</td><td>';
						if (isModEnabled('accounting')) {
							if (!empty($object->accountancy_code_sell_intra)) {
								$accountingaccount2 = new AccountingAccount($db);
								$accountingaccount2->fetch('', $object->accountancy_code_sell_intra, 1);

								print $accountingaccount2->getNomUrl(0, 1, 1, '', 1);
							}
						} else {
							print $object->accountancy_code_sell_intra;
						}
						print '</td></tr>';
					}

					// Accountancy sell code export
					print '<tr><td class="nowrap">';
					print $langs->trans("ProductAccountancySellExportCode");
					print '</td><td>';
					if (isModEnabled('accounting')) {
						if (!empty($object->accountancy_code_sell_export)) {
							$accountingaccount3 = new AccountingAccount($db);
							$accountingaccount3->fetch('', $object->accountancy_code_sell_export, 1);

							print $accountingaccount3->getNomUrl(0, 1, 1, '', 1);
						}
					} else {
						print $object->accountancy_code_sell_export;
					}
					print '</td></tr>';

					// Accountancy buy code
					print '<tr><td class="nowrap">';
					print $langs->trans("ProductAccountancyBuyCode");
					print '</td><td>';
					if (isModEnabled('accounting')) {
						if (!empty($object->accountancy_code_buy)) {
							$accountingaccount4 = new AccountingAccount($db);
							$accountingaccount4->fetch('', $object->accountancy_code_buy, 1);

							print $accountingaccount4->getNomUrl(0, 1, 1, '', 1);
						}
					} else {
						print $object->accountancy_code_buy;
					}
					print '</td></tr>';

					// Accountancy buy code intra-community
					if ($mysoc->isInEEC()) {
						print '<tr><td class="nowrap">';
						print $langs->trans("ProductAccountancyBuyIntraCode");
						print '</td><td>';
						if (isModEnabled('accounting')) {
							if (!empty($object->accountancy_code_buy_intra)) {
								$accountingaccount5 = new AccountingAccount($db);
								$accountingaccount5->fetch('', $object->accountancy_code_buy_intra, 1);

								print $accountingaccount5->getNomUrl(0, 1, 1, '', 1);
							}
						} else {
							print $object->accountancy_code_buy_intra;
						}
						print '</td></tr>';
					}

					// Accountancy buy code export
					print '<tr><td class="nowrap">';
					print $langs->trans("ProductAccountancyBuyExportCode");
					print '</td><td>';
					if (isModEnabled('accounting')) {
						if (!empty($object->accountancy_code_buy_export)) {
							$accountingaccount6 = new AccountingAccount($db);
							$accountingaccount6->fetch('', $object->accountancy_code_buy_export, 1);

							print $accountingaccount6->getNomUrl(0, 1, 1, '', 1);
						}
					} else {
						print $object->accountancy_code_buy_export;
					}
					print '</td></tr>';
				}

				// Description
				print '<tr><td class="tdtop">'.$langs->trans("Description").'</td><td>'.(dol_textishtml($object->description) ? $object->description : dol_nl2br($object->description, 1, true)).'</td></tr>';

				// Public URL
				if (!getDolGlobalString('PRODUCT_DISABLE_PUBLIC_URL')) {
					print '<tr><td>'.$langs->trans("PublicUrl").'</td><td>';
					print dol_print_url($object->url, '_blank', 128);
					print '</td></tr>';
				}

				// Default warehouse
				if (($object->isProduct() || getDolGlobalInt('STOCK_SUPPORTS_SERVICES')) && isModEnabled('stock')) {
					$warehouse = new Entrepot($db);
					$warehouse->fetch($object->fk_default_warehouse);

					print '<tr><td>'.$langs->trans("DefaultWarehouse").'</td><td>';
					print(!empty($warehouse->id) ? $warehouse->getNomUrl(1) : '');
					print '</td>';
				}

				if ($object->isService() && isModEnabled('workstation')) {
					$workstation = new Workstation($db);
					$res = $workstation->fetch($object->fk_default_workstation);

					print '<tr><td>'.$langs->trans("DefaultWorkstation").'</td><td>';
					print(!empty($workstation->id) ? $workstation->getNomUrl(1) : '');
					print '</td>';
				}

				// Parent product.
				if (isModEnabled('variants') && ($object->isProduct() || $object->isService())) {
					$combination = new ProductCombination($db);

					if ($combination->fetchByFkProductChild($object->id) > 0) {
						$prodstatic = new Product($db);
						$prodstatic->fetch($combination->fk_product_parent);

						// Parent product
						print '<tr><td>'.$langs->trans("ParentProduct").'</td><td>';
						print $prodstatic->getNomUrl(1);
						print '</td></tr>';
					}
				}

				print '</table>';
				print '</div>';
				print '<div class="fichehalfright">';

				print '<div class="underbanner clearboth"></div>';
				print '<table class="border tableforfield centpercent">';

				if ($object->isService()) {
					// Duration
					print '<tr><td class="titlefield">'.$langs->trans("Duration").'</td><td>';
					print $object->duration_value;
					if ($object->duration_value > 1) {
						$dur = array("i"=>$langs->trans("Minute"), "h"=>$langs->trans("Hours"), "d"=>$langs->trans("Days"), "w"=>$langs->trans("Weeks"), "m"=>$langs->trans("Months"), "y"=>$langs->trans("Years"));
					} elseif ($object->duration_value > 0) {
						$dur = array("i"=>$langs->trans("Minute"), "h"=>$langs->trans("Hour"), "d"=>$langs->trans("Day"), "w"=>$langs->trans("Week"), "m"=>$langs->trans("Month"), "y"=>$langs->trans("Year"));
					}
					print(!empty($object->duration_unit) && isset($dur[$object->duration_unit]) ? "&nbsp;".$langs->trans($dur[$object->duration_unit])."&nbsp;" : '');

					// Mandatory period
					if ($object->duration_value > 0) {
						print ' &nbsp; &nbsp; &nbsp; ';
					}
					$htmltooltip = $langs->trans("mandatoryHelper");
					print '<input type="checkbox" class="" name="mandatoryperiod"'.($object->mandatory_period == 1 ? ' checked="checked"' : '').' disabled>';
					print $form->textwithpicto($langs->trans("mandatoryperiod"), $htmltooltip, 1, 0);

					print '</td></tr>';
				} else {
					if (!getDolGlobalString('PRODUCT_DISABLE_NATURE')) {
						// Nature
						print '<tr><td class="titlefield">'.$form->textwithpicto($langs->trans("NatureOfProductShort"), $langs->trans("NatureOfProductDesc")).'</td><td>';
						print $object->getLibFinished();
						print '</td></tr>';
					}
				}

				if (!$object->isService() && isModEnabled('bom') && $object->finished) {
					print '<tr><td class="titlefield">'.$form->textwithpicto($langs->trans("DefaultBOM"), $langs->trans("DefaultBOMDesc", $langs->transnoentitiesnoconv("Finished"))).'</td><td>';
					if ($object->fk_default_bom) {
						$bom_static = new BOM($db);
						$bom_static->fetch($object->fk_default_bom);
						print $bom_static->getNomUrl(1);
					}
					print '</td></tr>';
				}

				if (!$object->isService()) {
					// Brut Weight
					if (!getDolGlobalString('PRODUCT_DISABLE_WEIGHT')) {
						print '<tr><td class="titlefield">'.$langs->trans("Weight").'</td><td>';
						if ($object->weight != '') {
							print $object->weight." ".measuringUnitString(0, "weight", $object->weight_units);
						} else {
							print '&nbsp;';
						}
						print "</td></tr>\n";
					}

					if (!getDolGlobalString('PRODUCT_DISABLE_SIZE')) {
						// Brut Length
						print '<tr><td>'.$langs->trans("Length").' x '.$langs->trans("Width").' x '.$langs->trans("Height").'</td><td>';
						if ($object->length != '' || $object->width != '' || $object->height != '') {
							print $object->length;
							if ($object->width) {
								print " x ".$object->width;
							}
							if ($object->height) {
								print " x ".$object->height;
							}
							print ' '.measuringUnitString(0, "size", $object->length_units);
						} else {
							print '&nbsp;';
						}
						print "</td></tr>\n";
					}
					if (!getDolGlobalString('PRODUCT_DISABLE_SURFACE')) {
						// Brut Surface
						print '<tr><td>'.$langs->trans("Surface").'</td><td>';
						if ($object->surface != '') {
							print $object->surface." ".measuringUnitString(0, "surface", $object->surface_units);
						} else {
							print '&nbsp;';
						}
						print "</td></tr>\n";
					}
					if (!getDolGlobalString('PRODUCT_DISABLE_VOLUME')) {
						// Brut Volume
						print '<tr><td>'.$langs->trans("Volume").'</td><td>';
						if ($object->volume != '') {
							print $object->volume." ".measuringUnitString(0, "volume", $object->volume_units);
						} else {
							print '&nbsp;';
						}
						print "</td></tr>\n";
					}

					if (getDolGlobalString('PRODUCT_ADD_NET_MEASURE')) {
						// Net Measure
						print '<tr><td class="titlefield">'.$langs->trans("NetMeasure").'</td><td>';
						if ($object->net_measure != '') {
							print $object->net_measure." ".measuringUnitString($object->net_measure_units);
						} else {
							print '&nbsp;';
						}
						print '</td></tr>';
					}
				}

				// Unit
				if (getDolGlobalString('PRODUCT_USE_UNITS')) {
					$unit = $object->getLabelOfUnit();

					print '<tr><td>'.$langs->trans('DefaultUnitToShow').'</td><td>';
					if ($unit !== '') {
						print $langs->trans($unit);
					}
					print '</td></tr>';
				}

				// Custom code
				if (!$object->isService() && !getDolGlobalString('PRODUCT_DISABLE_CUSTOM_INFO')) {
					print '<tr><td>'.$langs->trans("CustomCode").'</td><td>'.$object->customcode.'</td></tr>';

					// Origin country code
					print '<tr><td>'.$langs->trans("Origin").'</td><td>'.getCountry($object->country_id, 0, $db);
					if (!empty($object->state_id)) {
						print ' - '.getState($object->state_id, 0, $db);
					}
					print '</td></tr>';
				}

				// Quality Control
				if (getDolGlobalString('PRODUCT_LOT_ENABLE_QUALITY_CONTROL')) {
					print '<tr><td>'.$langs->trans("LifeTime").'</td><td>'.$object->lifetime.'</td></tr>';
					print '<tr><td>'.$langs->trans("QCFrequency").'</td><td>'.$object->qc_frequency.'</td></tr>';
				}

				// Other attributes
				$parameters = array();
				include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

				// Categories
				if (isModEnabled('categorie')) {
					print '<tr><td class="valignmiddle">'.$langs->trans("Categories").'</td><td>';
					print $form->showCategories($object->id, Categorie::TYPE_PRODUCT, 1);
					print "</td></tr>";
				}

				// Note private
				if (getDolGlobalString('MAIN_DISABLE_NOTES_TAB')) {
					print '<!-- show Note --> '."\n";
					print '<tr><td class="tdtop">'.$langs->trans("NotePrivate").'</td><td>'.(dol_textishtml($object->note_private) ? $object->note_private : dol_nl2br($object->note_private, 1, true)).'</td></tr>'."\n";
					print '<!-- End show Note --> '."\n";
				}

				print "</table>\n";
				print '</div>';

				print '</div>';
				print '<div class="clearboth"></div>';
			}

			print dol_get_fiche_end();
		}
	} elseif ($action != 'create') {
		exit;
	}
}

$tmpcode = '';
if (!empty($modCodeProduct->code_auto)) {
	$tmpcode = $modCodeProduct->getNextValue($object, $object->type);
}

$formconfirm = '';

// Confirm delete product
if (($action == 'delete' && (empty($conf->use_javascript_ajax) || !empty($conf->dol_use_jmobile)))	// Output when action = clone if jmobile or no js
	|| (!empty($conf->use_javascript_ajax) && empty($conf->dol_use_jmobile))) {							// Always output when not jmobile nor js
	$formconfirm = $form->formconfirm("card.php?id=".$object->id, $langs->trans("DeleteProduct"), $langs->trans("ConfirmDeleteProduct"), "confirm_delete", '', 0, "action-delete");
}
if ($action == 'merge') {
	$formquestion = array(
		array(
			'name' => 'product_origin',
			'label' => $langs->trans('MergeOriginProduct'),
			'type' => 'other',
			'value' => $form->select_produits('', 'product_origin', '', 0, 0, 1, 2, '', 1, array(), 0, 1, 0, 'minwidth200', 0, '', null, 1),
		)
	);
	$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$object->id, $langs->trans("MergeProducts"), $langs->trans("ConfirmMergeProducts"), "confirm_merge", $formquestion, 'no', 1, 250);
}

// Clone confirmation
if (($action == 'clone' && (empty($conf->use_javascript_ajax) || !empty($conf->dol_use_jmobile)))		// Output when action = clone if jmobile or no js
	|| (!empty($conf->use_javascript_ajax) && empty($conf->dol_use_jmobile))) {							// Always output when not jmobile nor js
	// Define confirmation messages
	$formquestionclone = array(
		'text' => $langs->trans("ConfirmClone"),
		array('type' => 'text', 'name' => 'clone_ref', 'label' => $langs->trans("NewRefForClone"), 'value' => empty($tmpcode) ? $langs->trans("CopyOf").' '.$object->ref : $tmpcode, 'morecss'=>'width150'),
		array('type' => 'checkbox', 'name' => 'clone_content', 'label' => $langs->trans("CloneContentProduct"), 'value' => 1),
		array('type' => 'checkbox', 'name' => 'clone_categories', 'label' => $langs->trans("CloneCategoriesProduct"), 'value' => 1),
	);
	if (getDolGlobalString('PRODUIT_MULTIPRICES')) {
		$formquestionclone[] = array('type' => 'checkbox', 'name' => 'clone_prices', 'label' => $langs->trans("ClonePricesProduct").' ('.$langs->trans("CustomerPrices").')', 'value' => 0);
	}
	if (getDolGlobalString('PRODUIT_SOUSPRODUITS')) {
		$formquestionclone[] = array('type' => 'checkbox', 'name' => 'clone_composition', 'label' => $langs->trans('CloneCompositionProduct'), 'value' => 1);
	}

	$formconfirm .= $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ToClone'), $langs->trans('ConfirmCloneProduct', $object->ref), 'confirm_clone', $formquestionclone, 'yes', 'action-clone', 350, 600);
}

// Call Hook formConfirm
$parameters = array('formConfirm' => $formconfirm, 'object' => $object);
$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
if (empty($reshook)) {
	$formconfirm .= $hookmanager->resPrint;
} elseif ($reshook > 0) {
	$formconfirm = $hookmanager->resPrint;
}

// Print form confirm
print $formconfirm;

/*
 * Action bar
 */
if ($action != 'create' && $action != 'edit') {
	$cloneProductUrl = $_SERVER["PHP_SELF"].'?action=clone&token='.newToken();
	$cloneButtonId = 'action-clone-no-ajax';

	print "\n".'<div class="tabsAction">'."\n";

	$parameters = array();
	$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	if (empty($reshook)) {
		if ($usercancreate) {
			if (!isset($object->no_button_edit) || $object->no_button_edit != 1) {
				print dolGetButtonAction('', $langs->trans('Modify'), 'default', $_SERVER["PHP_SELF"].'?action=edit&token='.newToken().'&id='.$object->id, '', $usercancreate);
			}

			if (!isset($object->no_button_copy) || $object->no_button_copy != 1) {
				if (!empty($conf->use_javascript_ajax) && empty($conf->dol_use_jmobile)) {
					$cloneProductUrl = '';
					$cloneButtonId = 'action-clone';
				}
				print dolGetButtonAction($langs->trans('ToClone'), '', 'default', $cloneProductUrl, $cloneButtonId, $usercancreate);
			}
		}
		$object_is_used = $object->isObjectUsed($object->id);

		if ($usercandelete) {
			if (empty($object_is_used) && (!isset($object->no_button_delete) || $object->no_button_delete != 1)) {
				if (!empty($conf->use_javascript_ajax) && empty($conf->dol_use_jmobile)) {
					print dolGetButtonAction($langs->trans('Delete'), '', 'delete', '#', 'action-delete', true);
				} else {
					print dolGetButtonAction('', $langs->trans('Delete'), 'delete', $_SERVER["PHP_SELF"].'?action=delete&token='.newToken().'&id='.$object->id, '');
				}
			} else {
				print dolGetButtonAction($langs->trans("ProductIsUsed"), $langs->trans('Delete'), 'delete', '#', '', false);
			}
			if (getDolGlobalInt('MAIN_FEATURES_LEVEL') > 1) {
				print '<a class="butActionDelete" href="card.php?action=merge&id='.$object->id.'" title="'.dol_escape_htmltag($langs->trans("MergeProducts")).'">'.$langs->trans('Merge').'</a>'."\n";
			}
		} else {
			print dolGetButtonAction($langs->trans("NotEnoughPermissions"), $langs->trans('Delete'), 'delete', '#', '', false);
		}
	}

	print "\n</div>\n";
}


/*
 * All the "Add to" areas if PRODUCT_ADD_FORM_ADD_TO is set
 */

if (getDolGlobalString('PRODUCT_ADD_FORM_ADD_TO') && $object->id && ($action == '' || $action == 'view') && $object->status) {
	//Variable used to check if any text is going to be printed
	$html = '';
	//print '<div class="fichecenter"><div class="fichehalfleft">';

	// Propals
	if (isModEnabled("propal") && $user->hasRight('propal', 'creer')) {
		$propal = new Propal($db);

		$langs->load("propal");

		$otherprop = $propal->liste_array(2, 1, 0);

		if (is_array($otherprop) && count($otherprop)) {
			$html .= '<tr><td style="width: 200px;">';
			$html .= $langs->trans("AddToDraftProposals").'</td><td>';
			$html .= $form->selectarray("propalid", $otherprop, 0, 1);
			$html .= '</td></tr>';
		} else {
			$html .= '<tr><td style="width: 200px;">';
			$html .= $langs->trans("AddToDraftProposals").'</td><td>';
			$html .= $langs->trans("NoDraftProposals");
			$html .= '</td></tr>';
		}
	}

	// Commande
	if (isModEnabled('commande') && $user->hasRight('commande', 'creer')) {
		$commande = new Commande($db);

		$langs->load("orders");

		$othercom = $commande->liste_array(2, 1, null);
		if (is_array($othercom) && count($othercom)) {
			$html .= '<tr><td style="width: 200px;">';
			$html .= $langs->trans("AddToDraftOrders").'</td><td>';
			$html .= $form->selectarray("commandeid", $othercom, 0, 1);
			$html .= '</td></tr>';
		} else {
			$html .= '<tr><td style="width: 200px;">';
			$html .= $langs->trans("AddToDraftOrders").'</td><td>';
			$html .= $langs->trans("NoDraftOrders");
			$html .= '</td></tr>';
		}
	}

	// Factures
	if (isModEnabled('facture') && $user->hasRight('facture', 'creer')) {
		$invoice = new Facture($db);

		$langs->load("bills");

		$otherinvoice = $invoice->liste_array(2, 1, null);
		if (is_array($otherinvoice) && count($otherinvoice)) {
			$html .= '<tr><td style="width: 200px;">';
			$html .= $langs->trans("AddToDraftInvoices").'</td><td>';
			$html .= $form->selectarray("factureid", $otherinvoice, 0, 1);
			$html .= '</td></tr>';
		} else {
			$html .= '<tr><td style="width: 200px;">';
			$html .= $langs->trans("AddToDraftInvoices").'</td><td>';
			$html .= $langs->trans("NoDraftInvoices");
			$html .= '</td></tr>';
		}
	}

	//If any text is going to be printed, then we show the table
	if (!empty($html)) {
		print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="action" value="addin">';

		print load_fiche_titre($langs->trans("AddToDraft"), '', '');

		print dol_get_fiche_head('');

		$html .= '<tr><td class="nowrap">'.$langs->trans("Quantity").' ';
		$html .= '<input type="text" class="flat" name="qty" size="1" value="1"></td>';
		$html .= '<td class="nowrap">'.$langs->trans("ReductionShort").'(%) ';
		$html .= '<input type="text" class="flat" name="remise_percent" size="1" value="0">';
		$html .= '</td></tr>';

		print '<table width="100%" class="border">';
		print $html;
		print '</table>';

		print '<div class="center">';
		print '<input type="submit" class="button button-add" value="'.$langs->trans("Add").'">';
		print '</div>';

		print dol_get_fiche_end();

		print '</form>';
	}
}


/*
 * Generated documents
 */

if ($action != 'create' && $action != 'edit' && $action != 'delete') {
	print '<div class="fichecenter"><div class="fichehalfleft">';
	print '<a name="builddoc"></a>'; // ancre

	// Documents
	$objectref = dol_sanitizeFileName($object->ref);
	if (!empty($conf->product->multidir_output[$object->entity])) {
		$filedir = $conf->product->multidir_output[$object->entity].'/'.$objectref; //Check repertories of current entities
	} else {
		$filedir = $conf->product->dir_output.'/'.$objectref;
	}
	$urlsource = $_SERVER["PHP_SELF"]."?id=".$object->id;
	$genallowed = $usercanread;
	$delallowed = $usercancreate;

	print $formfile->showdocuments($modulepart, $object->ref, $filedir, $urlsource, $genallowed, $delallowed, '', 0, 0, 0, 28, 0, '', 0, '', $langs->getDefaultLang(), '', $object);
	$somethingshown = $formfile->numoffiles;

	print '</div><div class="fichehalfright">';

	$MAXEVENT = 10;

	$morehtmlcenter = dolGetButtonTitle($langs->trans('SeeAll'), '', 'fa fa-bars imgforviewmode', DOL_URL_ROOT.'/product/agenda.php?id='.$object->id);

	// List of actions on element
	include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
	$formactions = new FormActions($db);
	$somethingshown = $formactions->showactions($object, 'product', 0, 1, '', $MAXEVENT, '', $morehtmlcenter); // Show all action for product

	print '</div></div>';
}

// End of page
llxFooter();
$db->close();
