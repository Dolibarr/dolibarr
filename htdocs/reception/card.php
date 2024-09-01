<?php
/* Copyright (C) 2003-2008	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2005-2016	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005		Simon TOSSER			<simon@kornog-computing.com>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@capnetworks.com>
 * Copyright (C) 2011-2017	Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2013       Florian Henry		  	<florian.henry@open-concept.pro>
 * Copyright (C) 2013       Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2014		Cedric GROSS			<c.gross@kreiz-it.fr>
 * Copyright (C) 2014-2017	Francis Appels			<francis.appels@yahoo.com>
 * Copyright (C) 2015		Claudio Aschieri		<c.aschieri@19.coop>
 * Copyright (C) 2016		Ferran Marcet			<fmarcet@2byte.es>
 * Copyright (C) 2016		Yasser Carreón			<yacasia@gmail.com>
 * Copyright (C) 2018	    Quentin Vial-Gouteyron  <quentin.vial-gouteyron@atm-consulting.fr>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/reception/card.php
 *	\ingroup    reception
 *	\brief      Card of a reception
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/reception/class/reception.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/reception.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/reception/modules_reception.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/productlot.class.php';
if (isModEnabled("product") || isModEnabled("service")) {
	require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
}
if (isModEnabled("propal")) {
	require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
}
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.dispatch.class.php';
if (isModEnabled('productbatch')) {
	require_once DOL_DOCUMENT_ROOT.'/product/class/productbatch.class.php';
}
if (isModEnabled('project')) {
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
}

$langs->loadLangs(array("receptions", "companies", "bills", 'deliveries', 'orders', 'stocks', 'other', 'propal', 'sendings'));

if (isModEnabled('incoterm')) {
	$langs->load('incoterm');
}
if (isModEnabled('productbatch')) {
	$langs->load('productbatch');
}

$origin = GETPOST('origin', 'alpha') ? GETPOST('origin', 'alpha') : 'reception'; // Example: commande, propal
$origin_id = GETPOSTINT('id') ? GETPOSTINT('id') : '';
$id = $origin_id;
if (empty($origin_id)) {
	$origin_id  = GETPOSTINT('origin_id'); // Id of order or propal
}
if (empty($origin_id)) {
	$origin_id  = GETPOSTINT('object_id'); // Id of order or propal
}
if (empty($origin_id)) {
	$origin_id  = GETPOSTINT('originid'); // Id of order or propal
}
$ref = GETPOST('ref', 'alpha');
$line_id = GETPOSTINT('lineid') ? GETPOSTINT('lineid') : 0;
$facid = GETPOSTINT('facid');

$action	= GETPOST('action', 'alpha');
//Select mail models is same action as presend
if (GETPOST('modelselected')) {
	$action = 'presend';
}
$confirm = GETPOST('confirm', 'alpha');
$cancel = GETPOST('cancel', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');

//PDF
$hidedetails = (GETPOSTINT('hidedetails') ? GETPOSTINT('hidedetails') : (getDolGlobalString('MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS') ? 1 : 0));
$hidedesc = (GETPOSTINT('hidedesc') ? GETPOSTINT('hidedesc') : (getDolGlobalString('MAIN_GENERATE_DOCUMENTS_HIDE_DESC') ? 1 : 0));
$hideref = (GETPOSTINT('hideref') ? GETPOSTINT('hideref') : (getDolGlobalString('MAIN_GENERATE_DOCUMENTS_HIDE_REF') ? 1 : 0));

$object = new Reception($db);
$objectorder = new CommandeFournisseur($db);
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);
$extrafields->fetch_name_optionals_label($object->table_element_line);
$extrafields->fetch_name_optionals_label($objectorder->table_element_line);

// Load object. Make an object->fetch
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be 'include', not 'include_once'

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('receptioncard', 'globalcard'));

$date_delivery = dol_mktime(GETPOSTINT('date_deliveryhour'), GETPOSTINT('date_deliverymin'), 0, GETPOSTINT('date_deliverymonth'), GETPOSTINT('date_deliveryday'), GETPOSTINT('date_deliveryyear'));

if ($id > 0 || !empty($ref)) {
	$object->fetch($id, $ref);
	$object->fetch_thirdparty();

	$typeobject = '';
	if (!empty($object->origin)) {
		$origin = $object->origin;
		$typeobject = $object->origin;

		$object->fetch_origin();
	}

	// Set $origin_id and $objectsrc
	if (($origin == 'order_supplier' || $origin == 'supplier_order') && is_object($object->origin_object) && isModEnabled("supplier_order")) {
		$origin_id = $object->origin_object->id;
		$objectsrc = $object->origin_object;
	}
}

// Security check
$socid = '';
if ($user->socid) {
	$socid = $user->socid;
}

// TODO Test on reception module on only
if (isModEnabled("reception") || $origin == 'reception' || empty($origin)) {
	$result = restrictedArea($user, 'reception', $object->id);
} else {
	// We do not use the reception module, so we test permission on the supplier orders
	if ($origin == 'supplierorder' || $origin == 'order_supplier') {
		$result = restrictedArea($user, 'fournisseur', $origin_id, 'commande_fournisseur', 'commande');
	} elseif (!$user->hasRight($origin, 'lire') && !$user->hasRight($origin, 'read')) {
		accessforbidden();
	}
}

if (isModEnabled("reception")) {
	$permissiontoread = $user->hasRight('reception', 'lire');
	$permissiontoadd = $user->hasRight('reception', 'creer');
	$permissiondellink = $user->hasRight('reception', 'creer'); // Used by the include of actions_dellink.inc.php
	$permissiontovalidate = ((!getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('reception', 'creer')) || (getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('reception', 'reception_advance', 'validate')));
	$permissiontodelete = $user->hasRight('reception', 'supprimer');
} else {
	$permissiontoread = $user->hasRight('fournisseur', 'commande', 'receptionner');
	$permissiontoadd = $user->hasRight('fournisseur', 'commande', 'receptionner');
	$permissiondellink = $user->hasRight('fournisseur', 'commande', 'receptionner'); // Used by the include of actions_dellink.inc.php
	$permissiontovalidate = ((!getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('fournisseur', 'commande', 'receptionner')) || (getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('fournisseur', 'commande_advance', 'check')));
	$permissiontodelete = $user->hasRight('fournisseur', 'commande', 'receptionner');
}

$error = 0;


/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	/*
	$backurlforlist = DOL_URL_ROOT.'/reception/list.php';

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			 if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) {
				 $backtopage = $backurlforlist;
			 } else {
				 $backtopage = dol_buildpath('/mymodule/myobject_card.php', 1).'?id='.((!empty($id) && $id > 0) ? $id : '__ID__');
			 }
		}
	}
	*/

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

	include DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php'; // Must be 'include', not 'include_once'

	// Reopen
	if ($action == 'reopen' && $permissiontoadd) {	// Test on permissions not required here
		$result = $object->reOpen();
	}

	// Confirm back to draft status
	if ($action == 'modif' && $permissiontoadd) {
		$result = $object->setDraft($user);
		if ($result >= 0) {
			// Define output language
			if (!getDolGlobalString('MAIN_DISABLE_PDF_AUTOUPDATE')) {
				$outputlangs = $langs;
				$newlang = '';
				if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
					$newlang = GETPOST('lang_id', 'aZ09');
				}
				if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang)) {
					$newlang = $object->thirdparty->default_lang;
				}
				if (!empty($newlang)) {
					$outputlangs = new Translate("", $conf);
					$outputlangs->setDefaultLang($newlang);
				}
				$model = $object->model_pdf;
				$ret = $object->fetch($id); // Reload to get new records
				$object->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
			}
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	// Set incoterm
	if ($action == 'set_incoterms' && isModEnabled('incoterm') && $permissiontoadd) {
		$result = $object->setIncoterms(GETPOSTINT('incoterm_id'), GETPOSTINT('location_incoterms'));
	}

	if ($action == 'setref_supplier' && $permissiontoadd) {
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}

		$result = $object->setValueFrom('ref_supplier', GETPOST('ref_supplier', 'alpha'), '', null, 'text', '', $user, 'RECEPTION_MODIFY');
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
			$action = 'editref_supplier';
		} else {
			header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
			exit;
		}
	}

	if ($action == 'update_extras' && $permissiontoadd) {
		$object->oldcopy = dol_clone($object, 2);

		// Fill array 'array_options' with data from update form
		$ret = $extrafields->setOptionalsFromPost(null, $object, GETPOST('attribute', 'restricthtml'));
		if ($ret < 0) {
			$error++;
		}

		if (!$error) {
			// Actions on extra fields
			$result = $object->insertExtraFields('RECEPTION_MODIFY');
			if ($result < 0) {
				setEventMessages($object->error, $object->errors, 'errors');
				$error++;
			}
		}

		if ($error) {
			$action = 'edit_extras';
		}
	}

	// Create reception
	if ($action == 'add' && $permissiontoadd) {
		$error = 0;

		$db->begin();

		$object->note = GETPOST('note', 'alpha');
		$object->note_private = GETPOST('note', 'alpha');
		$object->origin = $origin;
		$object->origin_id = $origin_id;
		$object->fk_project = GETPOSTINT('projectid');
		$object->weight = GETPOSTINT('weight') == '' ? null : GETPOSTINT('weight');
		$object->trueHeight = GETPOSTINT('trueHeight') == '' ? null : GETPOSTINT('trueHeight');
		$object->trueWidth = GETPOSTINT('trueWidth') == '' ? null : GETPOSTINT('trueWidth');
		$object->trueDepth = GETPOSTINT('trueDepth') == '' ? null : GETPOSTINT('trueDepth');
		$object->size_units = GETPOSTINT('size_units');
		$object->weight_units = GETPOSTINT('weight_units');

		// On va boucler sur chaque ligne du document d'origine pour completer object reception
		// avec info diverses + qte a livrer

		if ($object->origin == "supplierorder") {
			$object->origin = 'order_supplier';
			$classname = 'CommandeFournisseur';
		} else {
			$classname = ucfirst($object->origin);
		}
		$objectsrc = new $classname($db);
		$objectsrc->fetch($object->origin_id);

		$object->socid = $objectsrc->socid;
		$object->ref_supplier = GETPOST('ref_supplier', 'alpha');
		$object->model_pdf = GETPOST('model');
		$object->date_delivery = $date_delivery; // Date delivery planned
		$object->fk_delivery_address = $objectsrc->fk_delivery_address;
		$object->shipping_method_id = GETPOSTINT('shipping_method_id');
		$object->tracking_number = GETPOST('tracking_number', 'alpha');
		$object->note_private = GETPOST('note_private', 'restricthtml');
		$object->note_public = GETPOST('note_public', 'restricthtml');
		$object->fk_incoterms = GETPOSTINT('incoterm_id');
		$object->location_incoterms = GETPOST('location_incoterms', 'alpha');

		$batch_line = array();
		$stockLine = array();
		$array_options = array();

		$totalqty = 0;

		$num = 0;
		foreach ($_POST as $key => $value) {
			// without batch module enabled

			if (strpos($key, 'qtyasked') !== false) {
				$num++;
			}
		}

		// Loop lines to calculate $totalqty
		for ($i = 1; $i <= $num; $i++) {
			$idl = "idl".$i;	// id line source

			//$sub_qty = array();
			//$subtotalqty = 0;

			//$j = 0;
			//$batch = "batchl".$i."_0";
			//$stockLocation = "ent1".$i."_0";
			$qty = "qtyl".$i;	// qty

			//reception line for product with no batch management and no multiple stock location
			if (GETPOST($qty, 'alpha') > 0) {
				$totalqty += price2num(GETPOST($qty, 'alpha'), 'MS');
			}

			// Extrafields
			$array_options[$i] = $extrafields->getOptionalsFromPost($object->table_element_line, $i);
		}


		if ($totalqty > 0) {  // There is at least one thing to ship
			for ($i = 1; $i <= $num; $i++) {
				$idl = "idl".$i;	// id line source
				$lineToTest = '';
				$lineId = GETPOSTINT($idl);
				foreach ($objectsrc->lines as $linesrc) {
					if ($linesrc->id == $lineId) {
						$lineToTest = $linesrc;
						break;
					}
				}
				if (empty($lineToTest)) {
					continue;
				}
				$qty = "qtyl".$i;
				$comment = "comment".$i;
				// EATBY <-> DLUO and SELLBY <-> DLC, see productbatch.class.php
				$eatby = "dluo".$i;
				$sellby = "dlc".$i;
				$batch = "batch".$i;
				$cost_price = "cost_price".$i;

				//var_dump(GETPOST("productl".$i, 'int').' '.GETPOST('entl'.$i, 'int').' '.GETPOST($idl, 'int').' '.GETPOST($qty, 'int').' '.GETPOST($batch, 'alpha'));

				//if (GETPOST($qty, 'int') > 0 || (GETPOST($qty, 'int') == 0 && getDolGlobalString('RECEPTION_GETS_ALL_ORDER_PRODUCTS')) || (GETPOST($qty, 'int') < 0 && getDolGlobalString('RECEPTION_ALLOW_NEGATIVE_QTY'))) {
				if (GETPOSTINT($qty) > 0 || (GETPOSTINT($qty) == 0 && getDolGlobalString('RECEPTION_GETS_ALL_ORDER_PRODUCTS'))) {
					$ent = "entl".$i;
					$idl = "idl".$i;

					$entrepot_id = is_numeric(GETPOST($ent)) ? GETPOSTINT($ent) : GETPOSTINT('entrepot_id');

					/*
					if (!empty($lineToTest)) {
						$fk_product = $lineToTest->fk_product;
					} else {
						$fk_product = $linesrc->fk_product;
					}*/
					$fk_product = GETPOSTINT("productl".$i);

					if ($entrepot_id < 0) {
						$entrepot_id = '';
					}
					if (!($fk_product > 0) && !getDolGlobalString('STOCK_SUPPORTS_SERVICES')) {
						$entrepot_id = 0;
					}

					$eatby = GETPOST($eatby, 'alpha');
					$sellby = GETPOST($sellby, 'alpha');
					$eatbydate = str_replace('/', '-', $eatby);
					$sellbydate = str_replace('/', '-', $sellby);

					if (getDolGlobalString('STOCK_CALCULATE_ON_RECEPTION') || getDolGlobalString('STOCK_CALCULATE_ON_RECEPTION_CLOSE')) {
						$ret = $object->addline($entrepot_id, GETPOSTINT($idl), price2num(GETPOST($qty), 'MS'), $array_options[$i], GETPOST($comment), strtotime($eatbydate), strtotime($sellbydate), GETPOST($batch), GETPOSTFLOAT($cost_price, 'MU'));
					} else {
						$ret = $object->addline($entrepot_id, GETPOSTINT($idl), price2num(GETPOST($qty), 'MS'), $array_options[$i], GETPOST($comment), strtotime($eatbydate), strtotime($sellbydate), GETPOST($batch));
					}
					if ($ret < 0) {
						setEventMessages($object->error, $object->errors, 'errors');
						$error++;
					}
				}
			}

			// Fill array 'array_options' with data from add form
			$ret = $extrafields->setOptionalsFromPost(null, $object);
			if ($ret < 0) {
				$error++;
			}
			if (!$error) {
				$ret = $object->create($user); // This create reception (like Odoo picking) and line of receptions. Stock movement will when validating reception.

				if ($ret <= 0) {
					setEventMessages($object->error, $object->errors, 'errors');
					$error++;
				} else {
					// Define output language
					if (!getDolGlobalString('MAIN_DISABLE_PDF_AUTOUPDATE')) {
						$object->fetch_thirdparty();
						$outputlangs = $langs;
						$newlang = '';
						if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
							$newlang = GETPOST('lang_id', 'aZ09');
						}
						if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang)) {
							$newlang = $object->thirdparty->default_lang;
						}
						if (!empty($newlang)) {
							$outputlangs = new Translate("", $conf);
							$outputlangs->setDefaultLang($newlang);
						}
						$model = $object->model_pdf;
						$ret = $object->fetch($object->id); // Reload to get new records

						$result = $object->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
						if ($result < 0) {
							dol_print_error($db, $result);
						}
					}
				}
			}
		} else {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("QtyToReceive").'/'.$langs->transnoentitiesnoconv("Warehouse")), null, 'errors');
			$error++;
		}

		if (!$error) {
			$db->commit();
			header("Location: card.php?id=".$object->id);
			exit;
		} else {
			$db->rollback();
			//$_GET["commande_id"] = GETPOSTINT('commande_id');
			$action = 'create';
		}
	} elseif ($action == 'confirm_valid' && $confirm == 'yes' && $permissiontovalidate) {
		$object->fetch_thirdparty();

		$result = $object->valid($user);

		if ($result < 0) {
			$langs->load("errors");
			setEventMessages($langs->trans($object->error), null, 'errors');
		} else {
			// Define output language
			if (!getDolGlobalString('MAIN_DISABLE_PDF_AUTOUPDATE')) {
				$outputlangs = $langs;
				$newlang = '';
				if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
					$newlang = GETPOST('lang_id', 'aZ09');
				}
				if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang)) {
					$newlang = $object->thirdparty->default_lang;
				}
				if (!empty($newlang)) {
					$outputlangs = new Translate("", $conf);
					$outputlangs->setDefaultLang($newlang);
				}
				$model = $object->model_pdf;
				$ret = $object->fetch($id); // Reload to get new records

				$result = $object->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
				if ($result < 0) {
					dol_print_error($db, $result);
				}
			}
		}
	} elseif ($action == 'confirm_delete' && $confirm == 'yes' && $permissiontodelete) {
		$result = $object->delete($user);
		if ($result > 0) {
			header("Location: ".DOL_URL_ROOT.'/reception/index.php');
			exit;
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
		}

		// TODO add alternative status
		/*} elseif ($action == 'reopen' && ($user->hasRights('reception', 'creer') || $user->hasRights('reception', 'reception_advance', 'validate'))) {
			$result = $object->setStatut(0);
			if ($result < 0) {
				setEventMessages($object->error, $object->errors, 'errors');
		}*/
	} elseif ($action == 'setdate_livraison' && $permissiontoadd) {
		$datedelivery = dol_mktime(GETPOSTINT('liv_hour'), GETPOSTINT('liv_min'), 0, GETPOSTINT('liv_month'), GETPOSTINT('liv_day'), GETPOSTINT('liv_year'));

		$object->fetch($id);
		$result = $object->setDeliveryDate($user, $datedelivery);
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	} elseif (($action == 'settracking_number' || $action == 'settracking_url'
	|| $action == 'settrueWeight'
	|| $action == 'settrueWidth'
	|| $action == 'settrueHeight'
	|| $action == 'settrueDepth'
		|| $action == 'setshipping_method_id') && $permissiontoadd) {
		// Action update
		$error = 0;

		if ($action == 'settracking_number') {	// Test on permission to add
			$object->tracking_number = trim(GETPOST('tracking_number', 'alpha'));
		}
		if ($action == 'settracking_url') {		// Test on permission to add
			$object->tracking_url = trim(GETPOST('tracking_url', 'restricthtml'));
		}
		if ($action == 'settrueWeight') {		// Test on permission to add
			$object->trueWeight = GETPOSTINT('trueWeight');
			$object->weight_units = GETPOSTINT('weight_units');
		}
		if ($action == 'settrueWidth') {		// Test on permission to add
			$object->trueWidth = GETPOSTINT('trueWidth');
		}
		if ($action == 'settrueHeight') {		// Test on permission to add
			$object->trueHeight = GETPOSTINT('trueHeight');
			$object->size_units = GETPOSTINT('size_units');
		}
		if ($action == 'settrueDepth') {		// Test on permission to add
			$object->trueDepth = GETPOSTINT('trueDepth');
		}
		if ($action == 'setshipping_method_id') {	// Test on permission to add
			$object->shipping_method_id = GETPOSTINT('shipping_method_id');
		}

		if (!$error) {
			if ($object->update($user) >= 0) {
				header("Location: card.php?id=".$object->id);
				exit;
			}
			setEventMessages($object->error, $object->errors, 'errors');
		}

		$action = "";
	} elseif ($action == 'builddoc' && $permissiontoread) {
		// Build document
		// En get ou en post
		// Save last template used to generate document
		if (GETPOST('model')) {
			$object->setDocModel($user, GETPOST('model', 'alpha'));
		}

		// Define output language
		$outputlangs = $langs;
		$newlang = '';
		if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
			$newlang = GETPOST('lang_id', 'aZ09');
		}
		if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang)) {
			$newlang = $reception->thirdparty->default_lang;
		}
		if (!empty($newlang)) {
			$outputlangs = new Translate("", $conf);
			$outputlangs->setDefaultLang($newlang);
		}
		$result = $object->generateDocument($object->model_pdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
		if ($result <= 0) {
			setEventMessages($object->error, $object->errors, 'errors');
			$action = '';
		}
	} elseif ($action == 'remove_file' && $permissiontoadd) {
		// Delete file in doc form
		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$upload_dir = $conf->reception->dir_output;
		$file = $upload_dir.'/'.GETPOST('file');
		$ret = dol_delete_file($file, 0, 0, 0, $object);
		if ($ret) {
			setEventMessages($langs->trans("FileWasRemoved", GETPOST('urlfile')), null, 'mesgs');
		} else {
			setEventMessages($langs->trans("ErrorFailToDeleteFile", GETPOST('urlfile')), null, 'errors');
		}
	} elseif ($action == 'classifybilled' && $permissiontoadd) {
		$result = $object->setBilled();
		if ($result >= 0) {
			header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
			exit();
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
			$action = '';
		}
	} elseif ($action == 'classifyclosed' && $permissiontoread) {
		$result = $object->setClosed();
		if ($result >= 0) {
			header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
			exit();
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
			$action = '';
		}
	} elseif ($action == 'deleteline' && !empty($line_id) && $permissiontoread) {
		// delete a line
		$lines = $object->lines;
		$line = new CommandeFournisseurDispatch($db);

		$num_prod = count($lines);
		for ($i = 0; $i < $num_prod; $i++) {
			if ($lines[$i]->id == $line_id) {
				// delete single warehouse line
				$line->id = $line_id;
				if (!$error && $line->delete($user) < 0) {
					$error++;
				}
			}
			unset($_POST["lineid"]);
		}

		if (!$error) {
			header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
			exit();
		} else {
			setEventMessages($line->error, $line->errors, 'errors');
		}
	} elseif ($action == 'updateline' && GETPOST('save') && $permissiontoadd) {
		// Update a line
		// Clean parameters
		$qty = 0;
		$entrepot_id = 0;
		$batch_id = 0;

		$lines = $object->lines;
		$num_prod = count($lines);
		for ($i = 0; $i < $num_prod; $i++) {
			if ($lines[$i]->id == $line_id) {  // we have found line to update
				$line = new CommandeFournisseurDispatch($db);
				$line->fetch($line_id);
				// Extrafields Lines
				$extrafields->fetch_name_optionals_label($object->table_element_line);
				$line->array_options = $extrafields->getOptionalsFromPost($object->table_element_line);


				$line->fk_product = $lines[$i]->fk_product;


				if ($lines[$i]->fk_product > 0) {
					// single warehouse reception line
					$stockLocation = "entl".$line_id;
					$qty = "qtyl".$line_id;
					$comment = "comment".$line_id;


					$line->id = $line_id;
					$line->fk_entrepot = GETPOSTINT($stockLocation);
					$line->qty = GETPOSTINT($qty);
					$line->comment = GETPOST($comment, 'alpha');

					if (isModEnabled('productbatch')) {
						$batch = "batch".$line_id;
						$dlc = "dlc".$line_id;
						$dluo = "dluo".$line_id;
						// EATBY <-> DLUO
						$eatby = GETPOST($dluo, 'alpha');
						$eatbydate = str_replace('/', '-', $eatby);
						// SELLBY <-> DLC
						$sellby = GETPOST($dlc, 'alpha');
						$sellbydate = str_replace('/', '-', $sellby);
						$line->batch = GETPOST($batch, 'alpha');
						$line->eatby = strtotime($eatbydate);
						$line->sellby = strtotime($sellbydate);
					}

					if ($line->update($user) < 0) {
						setEventMessages($line->error, $line->errors, 'errors');
						$error++;
					}
				} else { // Product no predefined
					$qty = "qtyl".$line_id;
					$line->id = $line_id;
					$line->qty = GETPOSTINT($qty);
					$line->fk_entrepot = 0;
					if ($line->update($user) < 0) {
						setEventMessages($line->error, $line->errors, 'errors');
						$error++;
					}
					unset($_POST[$qty]);
				}
			}
		}

		unset($_POST["lineid"]);

		if (!$error) {
			if (!getDolGlobalString('MAIN_DISABLE_PDF_AUTOUPDATE')) {
				// Define output language
				$outputlangs = $langs;
				$newlang = '';
				if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
					$newlang = GETPOST('lang_id', 'aZ09');
				}
				if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang)) {
					$newlang = $object->thirdparty->default_lang;
				}
				if (!empty($newlang)) {
					$outputlangs = new Translate("", $conf);
					$outputlangs->setDefaultLang($newlang);
				}

				$ret = $object->fetch($object->id); // Reload to get new records
				$object->generateDocument($object->model_pdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
			}
		} else {
			header('Location: '.$_SERVER['PHP_SELF'].'?id='.$object->id); // To reshow the record we edit
			exit();
		}
	} elseif ($action == 'updateline' && $permissiontoadd && GETPOST('cancel', 'alpha') == $langs->trans("Cancel")) {
		header('Location: '.$_SERVER['PHP_SELF'].'?id='.$object->id); // To reshow the record we edit
		exit();
	}

	include DOL_DOCUMENT_ROOT.'/core/actions_printing.inc.php';

	// Actions to send emails
	if (empty($id)) {
		$id = $facid;
	}
	$triggersendname = 'RECEPTION_SENTBYMAIL';
	$paramname = 'id';
	$mode = 'emailfromreception';
	$autocopy = 'MAIN_MAIL_AUTOCOPY_RECEPTION_TO';
	$trackid = 'rec'.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';
}


/*
 * View
 */

$title = $object->ref.' - '.$langs->trans('Reception');

llxHeader('', $title, 'Reception', '', 0, 0, '', '', '', 'mod-reception page-card');

$form = new Form($db);
$formfile = new FormFile($db);
$formproduct = new FormProduct($db);
if (isModEnabled('project')) {
	$formproject = new FormProjets($db);
}

$product_static = new Product($db);
$reception_static = new Reception($db);
$warehousestatic = new Entrepot($db);

if ($action == 'create2') {
	print load_fiche_titre($langs->trans("CreateReception"), '', 'dollyrevert');

	print '<br>'.$langs->trans("ReceptionCreationIsDoneFromOrder");
	$action = '';
	$id = '';
	$ref = '';
}

// Mode creation.
if ($action == 'create') {
	$recept = new Reception($db);

	print load_fiche_titre($langs->trans("CreateReception"));
	if (!$origin) {
		setEventMessages($langs->trans("ErrorBadParameters"), null, 'errors');
	}

	if ($origin) {
		if ($origin == 'supplierorder') {
			$classname = 'CommandeFournisseur';
		} else {
			$classname = ucfirst($origin);
		}

		$objectsrc = new $classname($db);
		if ($objectsrc->fetch($origin_id)) {	// This include the fetch_lines
			$soc = new Societe($db);
			$soc->fetch($objectsrc->socid);

			$author = new User($db);
			$author->fetch($objectsrc->user_author_id);

			if (isModEnabled('stock')) {
				$entrepot = new Entrepot($db);
			}

			print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<input type="hidden" name="action" value="add">';
			print '<input type="hidden" name="origin" value="'.$origin.'">';
			print '<input type="hidden" name="origin_id" value="'.$objectsrc->id.'">';
			print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';
			print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
			if (GETPOSTINT('entrepot_id')) {
				print '<input type="hidden" name="entrepot_id" value="'.GETPOSTINT('entrepot_id').'">';
			}

			print dol_get_fiche_head();

			print '<table class="border centpercent">';

			// Ref
			print '<tr><td class="titlefieldcreate fieldrequired">';
			if ($origin == 'supplierorder' && isModEnabled("supplier_order")) {
				print $langs->trans("RefOrder").'</td><td colspan="3"><a href="'.DOL_URL_ROOT.'/fourn/commande/card.php?id='.$objectsrc->id.'">'.img_object($langs->trans("ShowOrder"), 'order').' '.$objectsrc->ref;
			}
			if ($origin == 'propal' && isModEnabled("propal")) {
				print $langs->trans("RefProposal").'</td><td colspan="3"><a href="'.DOL_URL_ROOT.'/comm/card.php?id='.$objectsrc->id.'">'.img_object($langs->trans("ShowProposal"), 'propal').' '.$objectsrc->ref;
			}
			print '</a></td>';
			print "</tr>\n";

			// Ref client
			print '<tr><td>';
			if ($origin == 'supplier_order') {
				print $langs->trans('SupplierOrder');
			} else {
				print $langs->trans('RefSupplier');
			}
			print '</td><td colspan="3">';
			print '<input type="text" name="ref_supplier" value="'.$objectsrc->ref_supplier.'" />';
			print '</td>';
			print '</tr>';

			// Tiers
			print '<tr><td class="titlefieldcreate fieldrequired">'.$langs->trans('Company').'</td>';
			print '<td colspan="3">'.$soc->getNomUrl(1).'</td>';
			print '</tr>';

			// Project
			if (isModEnabled('project')) {
				$projectid = GETPOSTINT('projectid') ? GETPOSTINT('projectid') : 0;
				if (empty($projectid) && !empty($objectsrc->fk_project)) {
					$projectid = $objectsrc->fk_project;
				}
				if ($origin == 'project') {
					$projectid = ($originid ? $originid : 0);
				}

				$langs->load("projects");
				print '<tr>';
				print '<td>'.$langs->trans("Project").'</td><td colspan="2">';
				print img_picto('', 'project', 'class="paddingright"');
				print $formproject->select_projects((!getDolGlobalString('PROJECT_CAN_ALWAYS_LINK_TO_ALL_SUPPLIERS') ? $soc->id : -1), $projectid, 'projectid', 0, 0, 1, 0, 1, 0, 0, '', 1, 0, 'maxwidth500');
				print ' &nbsp; <a href="'.DOL_URL_ROOT.'/projet/card.php?socid='.$soc->id.'&action=create&status=1&backtopage='.urlencode($_SERVER["PHP_SELF"].'?action=create&socid='.$soc->id).'"><span class="fa fa-plus-circle valignmiddle" title="'.$langs->trans("AddProject").'"></span></a>';
				print '</td>';
				print '</tr>';
			}

			// Date delivery planned
			print '<tr><td>'.$langs->trans("DateDeliveryPlanned").'</td>';
			print '<td colspan="3">';
			$date_delivery = ($date_delivery ? $date_delivery : $objectsrc->delivery_date); // $date_delivery comes from GETPOST
			print $form->selectDate($date_delivery ? $date_delivery : -1, 'date_delivery', 1, 1, 1);
			print "</td>\n";
			print '</tr>';

			// Note Public
			print '<tr><td>'.$langs->trans("NotePublic").'</td>';
			print '<td colspan="3">';
			$doleditor = new DolEditor('note_public', $objectsrc->note_public, '', 60, 'dolibarr_notes', 'In', 0, false, !getDolGlobalString('FCKEDITOR_ENABLE_NOTE_PUBLIC') ? 0 : 1, ROWS_3, '90%');
			print $doleditor->Create(1);
			print "</td></tr>";

			// Note Private
			if ($objectsrc->note_private && !$user->socid) {
				print '<tr><td>'.$langs->trans("NotePrivate").'</td>';
				print '<td colspan="3">';
				$doleditor = new DolEditor('note_private', $objectsrc->note_private, '', 60, 'dolibarr_notes', 'In', 0, false, !getDolGlobalString('FCKEDITOR_ENABLE_NOTE_PRIVATE') ? 0 : 1, ROWS_3, '90%');
				print $doleditor->Create(1);
				print "</td></tr>";
			}

			// Weight
			print '<tr><td>';
			print $langs->trans("Weight");
			print '</td><td colspan="3"><input name="weight" size="4" value="'.GETPOSTINT('weight').'"> ';
			$text = $formproduct->selectMeasuringUnits("weight_units", "weight", GETPOSTINT('weight_units'), 0, 2);
			$htmltext = $langs->trans("KeepEmptyForAutoCalculation");
			print $form->textwithpicto($text, $htmltext);
			print '</td></tr>';
			// Dim
			print '<tr><td>';
			print $langs->trans("Width").' x '.$langs->trans("Height").' x '.$langs->trans("Depth");
			print ' </td><td colspan="3"><input name="trueWidth" size="4" value="'.GETPOSTINT('trueWidth').'">';
			print ' x <input name="trueHeight" size="4" value="'.GETPOSTINT('trueHeight').'">';
			print ' x <input name="trueDepth" size="4" value="'.GETPOSTINT('trueDepth').'">';
			print ' ';
			$text = $formproduct->selectMeasuringUnits("size_units", "size", GETPOSTINT('size_units'), 0, 2);
			$htmltext = $langs->trans("KeepEmptyForAutoCalculation");
			print $form->textwithpicto($text, $htmltext);
			print '</td></tr>';

			// Delivery method
			print "<tr><td>".$langs->trans("ReceptionMethod")."</td>";
			print '<td colspan="3">';
			$recept->fetch_delivery_methods();
			print $form->selectarray("shipping_method_id", $recept->meths, GETPOSTINT('shipping_method_id'), 1, 0, 0, "", 1);
			if ($user->admin) {
				print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
			}
			print "</td></tr>\n";

			// Tracking number
			print "<tr><td>".$langs->trans("TrackingNumber")."</td>";
			print '<td colspan="3">';
			print '<input name="tracking_number" size="20" value="'.GETPOST('tracking_number', 'alpha').'">';
			print "</td></tr>\n";

			// Other attributes
			$parameters = array('objectsrc' => $objectsrc, 'colspan' => ' colspan="3"', 'cols' => '3', 'socid' => $socid);
			$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $recept, $action); // Note that $action and $objectsrc may have been modified by hook
			print $hookmanager->resPrint;

			// Here $object can be of an object Reception
			$extrafields->fetch_name_optionals_label($object->table_element);
			if (empty($reshook) && !empty($extrafields->attributes[$object->table_element]['label'])) {
				// copy from order
				if ($objectsrc->fetch_optionals() > 0) {
					$recept->array_options = array_merge($recept->array_options, $objectsrc->array_options);
				}
				print $recept->showOptionals($extrafields, 'create', $parameters);
			}

			// Incoterms
			if (isModEnabled('incoterm')) {
				print '<tr>';
				print '<td><label for="incoterm_id">'.$form->textwithpicto($langs->trans("IncotermLabel"), $objectsrc->label_incoterms, 1).'</label></td>';
				print '<td colspan="3" class="maxwidthonsmartphone">';
				print $form->select_incoterms((!empty($objectsrc->fk_incoterms) ? $objectsrc->fk_incoterms : ''), (!empty($objectsrc->location_incoterms) ? $objectsrc->location_incoterms : ''));
				print '</td></tr>';
			}

			// Document model
			include_once DOL_DOCUMENT_ROOT.'/core/modules/reception/modules_reception.php';
			$list = ModelePdfReception::liste_modeles($db);

			if (count($list) > 1) {
				print "<tr><td>".$langs->trans("DefaultModel")."</td>";
				print '<td colspan="3">';
				print $form->selectarray('model', $list, $conf->global->RECEPTION_ADDON_PDF);
				print "</td></tr>\n";
			}

			print "</table>";

			print dol_get_fiche_end();

			// Number of lines show on the reception card
			$numAsked = 0;

			/**
			 * @var array $suffix2numAsked map HTTP query parameter suffixes (like '1_0') to line indices so that
			 *                             extrafields from HTTP query can be assigned to the correct dispatch line
			*/
			$suffix2numAsked = array();
			$dispatchLines = array();

			foreach ($_POST as $key => $value) {
				// If create form is coming from the button "Create Reception" of previous page

				// without batch module enabled or product with no lot/serial
				$reg = array();
				if (preg_match('/^product_([0-9]+)_([0-9]+)$/i', $key, $reg)) {
					$numAsked++;
					$paramSuffix = $reg[1] . '_' . $reg[2];
					$suffix2numAsked[$paramSuffix] = $numAsked;

					// $numline=$reg[2] + 1; // line of product
					$numline = $numAsked;

					$prod = "product_" . $paramSuffix;
					$qty = "qty_" . $paramSuffix;
					$ent = "entrepot_" . $paramSuffix;
					$pu = "pu_" . $paramSuffix; // This is unit price including discount
					$fk_commandefourndet = "fk_commandefourndet_" . $paramSuffix;
					$dispatchLines[$numAsked] = array('paramSuffix' => $paramSuffix, 'prod' => GETPOSTINT($prod), 'qty' => price2num(GETPOST($qty), 'MS'), 'ent' => GETPOSTINT($ent), 'pu' => price2num(GETPOST($pu), 'MU'), 'comment' => GETPOST('comment'), 'fk_commandefourndet' => GETPOSTINT($fk_commandefourndet));
				}

				// with batch module enabled and product with lot/serial
				if (preg_match('/^product_batch_([0-9]+)_([0-9]+)$/i', $key, $reg)) {
					$numAsked++;
					$paramSuffix = $reg[1] . '_' . $reg[2];
					$suffix2numAsked[$paramSuffix] = $numAsked;

					// eat-by date dispatch
					// $numline=$reg[2] + 1; // line of product
					$numline = $numAsked;

					$prod = 'product_batch_' . $paramSuffix;
					$qty = 'qty_' . $paramSuffix;
					$ent = 'entrepot_' . $paramSuffix;
					$pu = 'pu_' . $paramSuffix;
					$lot = 'lot_number_' . $paramSuffix;
					$dDLUO = dol_mktime(12, 0, 0, GETPOSTINT('dluo_'.$paramSuffix.'month'), GETPOSTINT('dluo_'.$paramSuffix.'day'), GETPOSTINT('dluo_'.$paramSuffix.'year'));
					$dDLC = dol_mktime(12, 0, 0, GETPOSTINT('dlc_'.$paramSuffix.'month'), GETPOSTINT('dlc_'.$paramSuffix.'day'), GETPOSTINT('dlc_'.$paramSuffix.'year'));
					$fk_commandefourndet = 'fk_commandefourndet_'.$paramSuffix;
					$dispatchLines[$numAsked] = array('paramSuffix' => $paramSuffix, 'prod' => GETPOSTINT($prod), 'qty' => price2num(GETPOST($qty), 'MS'), 'ent' => GETPOSTINT($ent), 'pu' => price2num(GETPOST($pu), 'MU'), 'comment' => GETPOST('comment'), 'fk_commandefourndet' => GETPOSTINT($fk_commandefourndet), 'DLC' => $dDLC, 'DLUO' => $dDLUO, 'lot' => GETPOST($lot));
				}

				// If create form is coming from same page, it means that post was sent but an error occurred
				if (preg_match('/^productl([0-9]+)$/i', $key, $reg)) {
					$numAsked++;
					$paramSuffix = $reg[1];
					$suffix2numAsked[$paramSuffix] = $numAsked;

					// eat-by date dispatch
					// $numline=$reg[2] + 1; // line of product
					$numline = $numAsked;

					$prod = 'productid'.$paramSuffix;
					$comment = 'comment'.$paramSuffix;
					$qty = 'qtyl'.$paramSuffix;
					$ent = 'entl'.$paramSuffix;
					$pu = 'pul'.$paramSuffix;
					$lot = 'batch'.$paramSuffix;
					$dDLUO = dol_mktime(12, 0, 0, GETPOSTINT('dluo'.$paramSuffix.'month'), GETPOSTINT('dluo'.$paramSuffix.'day'), GETPOSTINT('dluo'.$paramSuffix.'year'));
					$dDLC = dol_mktime(12, 0, 0, GETPOSTINT('dlc'.$paramSuffix.'month'), GETPOSTINT('dlc'.$paramSuffix.'day'), GETPOSTINT('dlc'.$paramSuffix.'year'));
					$fk_commandefourndet = 'fk_commandefournisseurdet'.$paramSuffix;
					$dispatchLines[$numAsked] = array('prod' => GETPOSTINT($prod), 'qty' => price2num(GETPOST($qty), 'MS'), 'ent' => GETPOSTINT($ent), 'pu' => price2num(GETPOST($pu), 'MU'), 'comment' => GETPOST($comment), 'fk_commandefourndet' => GETPOSTINT($fk_commandefourndet), 'DLC' => $dDLC, 'DLUO' => $dDLUO, 'lot' => GETPOSTINT($lot));
				}
			}

			// If extrafield values are passed in the HTTP query, assign them to the correct dispatch line
			// Note that if an extrafield with the same name exists in the origin supplier order line, the value
			// from the HTTP query will be ignored
			foreach ($suffix2numAsked as $suffix => $n) {
				$dispatchLines[$n]['array_options'] = $extrafields->getOptionalsFromPost('receptiondet_batch', '_' . $suffix, '');
			}

			print '<script type="text/javascript">
            jQuery(document).ready(function() {
	            jQuery("#autofill").click(function(event) {
					event.preventDefault();';
			$i = 1;
			while ($i <= $numAsked) {
				print 'jQuery("#qtyl'.$i.'").val(jQuery("#qtyasked'.$i.'").val() - jQuery("#qtydelivered'.$i.'").val());'."\n";
				$i++;
			}
			print '});
	            jQuery("#autoreset").click(function(event) {
					event.preventDefault();';
			$i = 1;
			while ($i <= $numAsked) {
				print 'jQuery("#qtyl'.$i.'").val(0);'."\n";
				$i++;
			}
			print '});
        	});
            </script>';

			print '<br>';

			print '<table class="noborder centpercent">';

			// Load receptions already done for same order
			$objectsrc->loadReceptions();

			if ($numAsked) {
				print '<tr class="liste_titre">';
				print '<td>'.$langs->trans("Description").'</td>';
				print '<td>'.$langs->trans("Comment").'</td>';
				print '<td class="center">'.$langs->trans("QtyOrdered").'</td>';
				print '<td class="center">'.$langs->trans("QtyReceived").'</td>';
				print '<td class="center">'.$langs->trans("QtyToReceive");
				if (getDolGlobalInt('STOCK_CALCULATE_ON_RECEPTION') || getDolGlobalInt('STOCK_CALCULATE_ON_RECEPTION_CLOSE')) {
					print '<td>'.$langs->trans("BuyingPrice").'</td>';
				}
				if (!isModEnabled('productbatch')) {
					print ' <br><center><a href="#" id="autofill"><span class="fas fa-fill pictofixedwidth" style=""></span> '.$langs->trans("Fill").'</a>';
					print ' &nbsp; &nbsp; <a href="#" id="autoreset"><span class="fas fa-eraser pictofixedwidth" style=""></span>'.$langs->trans("Reset").'</a></center><br>';
				}
				print '</td>';
				if (isModEnabled('stock')) {
					print '<td class="left">'.$langs->trans("Warehouse").' ('.$langs->trans("Stock").')</td>';
				}
				if (isModEnabled('productbatch')) {
					print '<td class="left">'.$langs->trans("batch_number").'</td>';
					if (!getDolGlobalInt('PRODUCT_DISABLE_SELLBY')) {
						print '<td class="left">'.$langs->trans("SellByDate").'</td>';
					}
					if (!getDolGlobalInt('PRODUCT_DISABLE_EATBY')) {
						print '<td class="left">'.$langs->trans("EatByDate").'</td>';
					}
				}
				print "</tr>\n";
			}

			// $objectsrc->lines contains the line of the purchase order
			// $dispatchLines is list of lines with dispatching detail (with product, qty and warehouse). One purchase order line may have n of this dispatch lines.

			$arrayofpurchaselinealreadyoutput = array();

			// $_POST contains fk_commandefourndet_X_Y    where Y is num of product line and X is number of split lines
			$indiceAsked = 1;
			while ($indiceAsked <= $numAsked) {	// Loop on $dispatchLines. Warning: $dispatchLines must be sorted by fk_commandefourndet (it is a regroupment key on output)
				$product = new Product($db);

				// We search the purchase order line that is linked to the dispatchLines
				foreach ($objectsrc->lines as $supplierLine) {
					if ($dispatchLines[$indiceAsked]['fk_commandefourndet'] == $supplierLine->id) {
						$line = $supplierLine;
						break;
					}
				}

				// Show product and description
				$type = $line->product_type ? $line->product_type : $line->fk_product_type;
				// Try to enhance type detection using date_start and date_end for free lines where type
				// was not saved.
				if (!empty($line->date_start)) {
					$type = 1;
				}
				if (!empty($line->date_end)) {
					$type = 1;
				}

				print '<!-- line fk_commandefourndet='.$line->id.' for product='.$line->fk_product.' -->'."\n";
				print '<tr class="oddeven">'."\n";

				// Product label
				if ($line->fk_product > 0) {  // If predefined product
					$product->fetch($line->fk_product);
					$product->load_stock('warehouseopen'); // Load all $product->stock_warehouse[idwarehouse]->detail_batch
					//var_dump($product->stock_warehouse[1]);
					//var_dump($dispatchLines[$indiceAsked]);

					print '<td>';
					print '<a name="'.$line->id.'"></a>'; // ancre pour retourner sur la ligne

					print '<input type="hidden" name="productl'.$indiceAsked.'" value="'.$line->fk_product.'">';

					if (! array_key_exists($line->id, $arrayofpurchaselinealreadyoutput)) {	// Add test to avoid to show qty twice
						print '<input type="hidden" name="productid'.$indiceAsked.'" value="'.$line->fk_product.'">';

						// Show product and description
						$product_static = $product;

						$text = $product_static->getNomUrl(1);
						$text .= ' - '.(!empty($line->label) ? $line->label : $line->product_label);
						$description = (getDolGlobalInt('PRODUIT_DESC_IN_FORM_ACCORDING_TO_DEVICE') ? '' : dol_htmlentitiesbr($line->desc));
						print $form->textwithtooltip($text, $description, 3, '', '', $i);

						// Show range
						print_date_range($db->jdate($line->date_start), $db->jdate($line->date_end));

						// Add description in form
						if (getDolGlobalInt('PRODUIT_DESC_IN_FORM_ACCORDING_TO_DEVICE')) {
							print ($line->desc && $line->desc != $line->product_label) ? '<br>'.dol_htmlentitiesbr($line->desc) : '';
						}
					}
					print '</td>';
				} else {
					print "<td>";
					if (! array_key_exists($line->id, $arrayofpurchaselinealreadyoutput)) {	// Add test to avoid to show qty twice
						if ($type == 1) {
							$text = img_object($langs->trans('Service'), 'service');
						} else {
							$text = img_object($langs->trans('Product'), 'product');
						}

						if (!empty($line->label)) {
							$text .= ' <strong>'.$line->label.'</strong>';
							print $form->textwithtooltip($text, $line->desc, 3, '', '', $i);
						} else {
							print $text.' '.nl2br($line->desc);
						}

						// Show range
						print_date_range($db->jdate($line->date_start), $db->jdate($line->date_end));
					}
					print "</td>\n";
				}

				// Comment
				//$defaultcomment = 'Line create from order line id '.$line->id;
				$defaultcomment = $dispatchLines[$indiceAsked]['comment'];
				print '<td>';
				print '<input type="text" class="maxwidth100" name="comment'.$indiceAsked.'" value="'.$defaultcomment.'">';
				print '</td>';

				// Qty in source purchase order line
				print '<td class="center">';
				if (! array_key_exists($line->id, $arrayofpurchaselinealreadyoutput)) {	// Add test to avoid to show qty twice
					print $line->qty;
				}
				print '<input type="hidden" name="fk_commandefournisseurdet'.$indiceAsked.'" value="'.$line->id.'">';
				print '<input type="hidden" name="pul'.$indiceAsked.'" value="'.$line->pu_ht.'">';
				print '<input name="qtyasked'.$indiceAsked.'" id="qtyasked'.$indiceAsked.'" type="hidden" value="'.$line->qty.'">';
				print '</td>';
				$qtyProdCom = $line->qty;

				// Qty already received
				print '<td class="center">';
				$quantityDelivered = $objectsrc->receptions[$line->id];
				if (! array_key_exists($line->id, $arrayofpurchaselinealreadyoutput)) {	// Add test to avoid to show qty twice
					print $quantityDelivered;
				}
				print '<input name="qtydelivered'.$indiceAsked.'" id="qtydelivered'.$indiceAsked.'" type="hidden" value="'.$quantityDelivered.'">';
				print '</td>';


				if ($line->product_type == 1 && !getDolGlobalString('STOCK_SUPPORTS_SERVICES')) {
					$quantityToBeDelivered = 0;
				} else {
					$quantityToBeDelivered = $dispatchLines[$indiceAsked]['qty'];
				}
				$warehouse_id = $dispatchLines[$indiceAsked]['ent'];


				$warehouseObject = null;
				if (isModEnabled('stock')) {
					// If warehouse was already selected or if product is not a predefined, we go into this part with no multiwarehouse selection
					print '<!-- Case warehouse already known or product not a predefined product -->';
					if (array_key_exists($dispatchLines[$indiceAsked]['ent'], $product->stock_warehouse)) {
						$stock = +$product->stock_warehouse[$dispatchLines[$indiceAsked]['ent']]->real; // Convert to number
					}
					$deliverableQty = $dispatchLines[$indiceAsked]['qty'];
					$cost_price = $dispatchLines[$indiceAsked]['pu'];

					// Quantity to send
					print '<td class="center">';
					if ($line->product_type == Product::TYPE_PRODUCT || getDolGlobalString('STOCK_SUPPORTS_SERVICES')) {
						if (GETPOSTINT('qtyl'.$indiceAsked)) {
							$defaultqty = GETPOSTINT('qtyl'.$indiceAsked);
						}
						print '<input name="idl'.$indiceAsked.'" type="hidden" value="'.$line->id.'">';
						print '<input class="right" name="qtyl'.$indiceAsked.'" id="qtyl'.$indiceAsked.'" type="text" size="4" value="'.$deliverableQty.'">';
					} else {
						print $langs->trans("NA");
					}
					print '</td>';

					if (getDolGlobalString('STOCK_CALCULATE_ON_RECEPTION') || getDolGlobalString('STOCK_CALCULATE_ON_RECEPTION_CLOSE')) {
						print '<td>';
						print '<input class="width75 right" name="cost_price'.$indiceAsked.'" id="cost_price'.$indiceAsked.'" value="'.$cost_price.'">';
						print '</td>';
					}

					// Stock
					if (isModEnabled('stock')) {
						print '<td class="left">';
						if ($line->product_type == Product::TYPE_PRODUCT || getDolGlobalString('STOCK_SUPPORTS_SERVICES')) {   // Type of product need stock change ?
							// Show warehouse combo list
							$ent = "entl".$indiceAsked;
							$idl = "idl".$indiceAsked;
							$tmpentrepot_id = is_numeric(GETPOST($ent)) ? GETPOSTINT($ent) : $warehouse_id;
							if ($line->fk_product > 0) {
								print '<!-- Show warehouse selection -->';
								print $formproduct->selectWarehouses($tmpentrepot_id, 'entl'.$indiceAsked, '', 0, 0, $line->fk_product, '', 1);
							}
						} else {
							print $langs->trans("Service");
						}
						print '</td>';
					}

					if (isModEnabled('productbatch')) {
						if (!empty($product->status_batch)) {
							print '<td><input name="batch'.$indiceAsked.'" value="'.$dispatchLines[$indiceAsked]['lot'].'"></td>';
							if (!getDolGlobalString('PRODUCT_DISABLE_SELLBY')) {
								print '<td class="nowraponall">';
								print $form->selectDate($dispatchLines[$indiceAsked]['DLC'], 'dlc'.$indiceAsked, 0, 0, 1, "");
								print '</td>';
							}
							if (!getDolGlobalString('PRODUCT_DISABLE_EATBY')) {
								print '<td class="nowraponall">';
								print $form->selectDate($dispatchLines[$indiceAsked]['DLUO'], 'dluo'.$indiceAsked, 0, 0, 1, "");
								print '</td>';
							}
						} else {
							print '<td colspan="3"></td>';
						}
					}
				}

				$arrayofpurchaselinealreadyoutput[$line->id] = $line->id;

				print "</tr>\n";

				// Display lines for extrafields of the Reception line
				// $line is a 'CommandeFournisseurLigne', $dispatchLines contains values of Reception lines so properties of CommandeFournisseurDispatch
				if (!empty($extrafields)) {
					$colspan = 5;
					if (isModEnabled('productbatch')) {
						$colspan += 2;
						if (!getDolGlobalString('PRODUCT_DISABLE_SELLBY')) {
							$colspan += 1;
						}
						if (!getDolGlobalString('PRODUCT_DISABLE_EATBY')) {
							$colspan += 1;
						}
					}
					$recLine = new CommandeFournisseurDispatch($db);

					$srcLine = new CommandeFournisseurLigne($db);
					$srcLine->id = $line->id;
					$srcLine->fetch_optionals(); // fetch extrafields also available in orderline

					if (empty($recLine->array_options) && !empty($dispatchLines[$indiceAsked]['array_options'])) {
						$recLine->array_options = $dispatchLines[$indiceAsked]['array_options'];
					}
					$recLine->array_options = array_merge($recLine->array_options, $srcLine->array_options);

					print $recLine->showOptionals($extrafields, 'edit', array('style' => 'class="oddeven"', 'colspan' => $colspan), $indiceAsked, '', 1);
				}

				$indiceAsked++;
			}

			print "</table>";

			print '<br>';

			print $form->buttonsSaveCancel("Create");

			print '</form>';

			print '<br>';
		} else {
			dol_print_error($db);
		}
	}
} elseif ($id || $ref) {
	/* *************************************************************************** */
	/*                                                                             */
	/* Edit and view mode                                                          */
	/*                                                                             */
	/* *************************************************************************** */
	$lines = $object->lines;

	$num_prod = count($lines);
	$indiceAsked = 0;

	if ($object->id <= 0) {
		print $langs->trans("NoRecordFound");
		llxFooter();
		exit;
	}

	if (!empty($object->origin) && $object->origin_id > 0) {
		$object->origin = 'CommandeFournisseur';
		$typeobject = $object->origin;
		$origin = $object->origin;
		$origin_id = $object->origin_id;
		$object->fetch_origin(); // Load property $object->origin_object, $object->commande, $object->propal, ...
	}

	$soc = new Societe($db);
	$soc->fetch($object->socid);

	$res = $object->fetch_optionals();

	$head = reception_prepare_head($object);
	print dol_get_fiche_head($head, 'reception', $langs->trans("Reception"), -1, 'dollyrevert');

	$formconfirm = '';

	// Confirm deletion
	if ($action == 'delete') {
		$formconfirm = $form->formconfirm($_SERVER['PHP_SELF'].'?id='.$object->id, $langs->trans('DeleteReception'), $langs->trans("ConfirmDeleteReception", $object->ref), 'confirm_delete', '', 0, 1);
	}

	// Confirmation validation
	if ($action == 'valid') {
		$objectref = substr($object->ref, 1, 4);
		if ($objectref == 'PROV') {
			$numref = $object->getNextNumRef($soc);
		} else {
			$numref = $object->ref;
		}

		$text = $langs->trans("ConfirmValidateReception", $numref);
		if (getDolGlobalString('STOCK_CALCULATE_ON_RECEPTION')) {
			$text .= '<br>'.img_picto('', 'movement', 'class="pictofixedwidth"').$langs->trans("StockMovementWillBeRecorded").'.';
		} elseif (getDolGlobalString('STOCK_CALCULATE_ON_RECEPTION_CLOSE')) {
			$text .= '<br>'.img_picto('', 'movement', 'class="pictofixedwidth"').$langs->trans("StockMovementNotYetRecorded").'.';
		}

		if (isModEnabled('notification')) {
			require_once DOL_DOCUMENT_ROOT.'/core/class/notify.class.php';
			$notify = new Notify($db);
			$text .= '<br>';
			$text .= $notify->confirmMessage('RECEPTION_VALIDATE', $object->socid, $object);
		}

		$formconfirm = $form->formconfirm($_SERVER['PHP_SELF'].'?id='.$object->id, $langs->trans('ValidateReception'), $text, 'confirm_valid', '', 0, 1, 250);
	}

	// Confirm cancellation
	if ($action == 'annuler') {
		$formconfirm = $form->formconfirm($_SERVER['PHP_SELF'].'?id='.$object->id, $langs->trans('CancelReception'), $langs->trans("ConfirmCancelReception", $object->ref), 'confirm_cancel', '', 0, 1);
	}

	if (!$formconfirm) {
		$parameters = array('formConfirm' => $formconfirm);
		$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		if (empty($reshook)) {
			$formconfirm .= $hookmanager->resPrint;
		} elseif ($reshook > 0) {
			$formconfirm = $hookmanager->resPrint;
		}
	}

	// Print form confirm
	print $formconfirm;


	// Calculate totalWeight and totalVolume for all products
	// by adding weight and volume of each product line.
	$tmparray = $object->getTotalWeightVolume();
	$totalWeight = $tmparray['weight'];
	$totalVolume = $tmparray['volume'];


	if ($typeobject == 'commande' && $object->origin_object->id && isModEnabled('order')) {
		$objectsrc = new Commande($db);
		$objectsrc->fetch($object->origin_object->id);
	}
	if ($typeobject == 'propal' && $object->origin_object->id && isModEnabled("propal")) {
		$objectsrc = new Propal($db);
		$objectsrc->fetch($object->origin_object->id);
	}
	if ($typeobject == 'CommandeFournisseur' && $object->origin_object->id && isModEnabled("supplier_order")) {
		$objectsrc = new CommandeFournisseur($db);
		$objectsrc->fetch($object->origin_object->id);
	}
	// Reception card
	$linkback = '<a href="'.DOL_URL_ROOT.'/reception/list.php?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';
	$morehtmlref = '<div class="refidno">';
	// Ref customer reception

	$morehtmlref .= $form->editfieldkey("RefSupplier", 'ref_supplier', $object->ref_supplier, $object, $user->hasRight('reception', 'creer'), 'string', '', 0, 1);
	$morehtmlref .= $form->editfieldval("RefSupplier", 'ref_supplier', $object->ref_supplier, $object, $user->hasRight('reception', 'creer'), 'string', '', null, null, '', 1);

	// Thirdparty
	$morehtmlref .= '<br>'.$object->thirdparty->getNomUrl(1);
	// Project
	if (isModEnabled('project')) {
		$langs->load("projects");
		$morehtmlref .= '<br>';
		if (0) {    // Do not change on reception
			$morehtmlref .= img_picto($langs->trans("Project"), 'project', 'class="pictofixedwidth"');
			if ($action != 'classify' && $permissiontoadd) {
				$morehtmlref .= '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?action=classify&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetProject')).'</a> ';
			}
			$morehtmlref .= $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, (!getDolGlobalString('PROJECT_CAN_ALWAYS_LINK_TO_ALL_SUPPLIERS') ? $object->socid : -1), $object->fk_project, ($action == 'classify' ? 'projectid' : 'none'), 0, 0, 0, 1, '', 'maxwidth300');
		} else {
			if (!empty($objectsrc) && !empty($objectsrc->fk_project)) {
				$proj = new Project($db);
				$proj->fetch($objectsrc->fk_project);
				$morehtmlref .= $proj->getNomUrl(1);
				if ($proj->title) {
					$morehtmlref .= '<span class="opacitymedium"> - '.dol_escape_htmltag($proj->title).'</span>';
				}
			}
		}
	}
	$morehtmlref .= '</div>';

	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);


	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';

	print '<table class="border centpercent tableforfield">';

	// Linked documents
	if ($typeobject == 'commande' && $object->origin_object->id && isModEnabled('order')) {
		print '<tr><td>';
		print $langs->trans("RefOrder").'</td>';
		print '<td colspan="3">';
		print $objectsrc->getNomUrl(1, 'commande');
		print "</td>\n";
		print '</tr>';
	}
	if ($typeobject == 'propal' && $object->origin_object->id && isModEnabled("propal")) {
		print '<tr><td>';
		print $langs->trans("RefProposal").'</td>';
		print '<td colspan="3">';
		print $objectsrc->getNomUrl(1, 'reception');
		print "</td>\n";
		print '</tr>';
	}
	if ($typeobject == 'CommandeFournisseur' && $object->origin_object->id && isModEnabled("propal")) {
		print '<tr><td>';
		print $langs->trans("SupplierOrder").'</td>';
		print '<td colspan="3">';
		print $objectsrc->getNomUrl(1, 'reception');
		print "</td>\n";
		print '</tr>';
	}

	// Date creation
	print '<tr><td class="titlefield">'.$langs->trans("DateCreation").'</td>';
	print '<td colspan="3">'.dol_print_date($object->date_creation, "dayhour", "tzuserrel")."</td>\n";
	print '</tr>';

	// Delivery date planned
	print '<tr><td height="10">';
	print '<table class="nobordernopadding" width="100%"><tr><td>';
	print $langs->trans('DateDeliveryPlanned');
	print '</td>';

	if ($action != 'editdate_livraison' && $permissiontoadd) {
		print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editdate_livraison&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->trans('SetDeliveryDate'), 1).'</a></td>';
	}
	print '</tr></table>';
	print '</td><td colspan="2">';
	if ($action == 'editdate_livraison') {
		print '<form name="setdate_livraison" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="post">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="action" value="setdate_livraison">';
		print $form->selectDate($object->date_delivery ? $object->date_delivery : -1, 'liv_', 1, 1, 0, "setdate_livraison", 1, 0);
		print '<input type="submit" class="button button-edit" value="'.$langs->trans('Modify').'">';
		print '</form>';
	} else {
		print $object->date_delivery ? dol_print_date($object->date_delivery, 'dayhour') : '&nbsp;';
	}
	print '</td>';
	print '</tr>';

	// Weight
	print '<tr><td>';
	print $form->editfieldkey("Weight", 'trueWeight', $object->trueWeight, $object, $user->hasRight('reception', 'creer'));
	print '</td><td colspan="3">';

	if ($action == 'edittrueWeight') {
		print '<form name="settrueweight" action="'.$_SERVER["PHP_SELF"].'" method="post">';
		print '<input name="action" value="settrueWeight" type="hidden">';
		print '<input name="id" value="'.$object->id.'" type="hidden">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input id="trueWeight" name="trueWeight" value="'.$object->trueWeight.'" type="text">';
		print $formproduct->selectMeasuringUnits("weight_units", "weight", $object->weight_units, 0, 2);
		print ' <input class="button" name="modify" value="'.$langs->trans("Modify").'" type="submit">';
		print ' <input class="button button-cancel" name="cancel" value="'.$langs->trans("Cancel").'" type="submit">';
		print '</form>';
	} else {
		print $object->trueWeight;
		print ($object->trueWeight && $object->weight_units != '') ? ' '.measuringUnitString(0, "weight", $object->weight_units) : '';
	}

	// Calculated
	if ($totalWeight > 0) {
		if (!empty($object->trueWeight)) {
			print ' ('.$langs->trans("SumOfProductWeights").': ';
		}
		print showDimensionInBestUnit($totalWeight, 0, "weight", $langs, isset($conf->global->MAIN_WEIGHT_DEFAULT_ROUND) ? $conf->global->MAIN_WEIGHT_DEFAULT_ROUND : -1, isset($conf->global->MAIN_WEIGHT_DEFAULT_UNIT) ? $conf->global->MAIN_WEIGHT_DEFAULT_UNIT : 'no');
		if (!empty($object->trueWeight)) {
			print ')';
		}
	}
	print '</td></tr>';

	// Width
	print '<tr><td>'.$form->editfieldkey("Width", 'trueWidth', $object->trueWidth, $object, $user->hasRight('reception', 'creer')).'</td><td colspan="3">';
	print $form->editfieldval("Width", 'trueWidth', $object->trueWidth, $object, $user->hasRight('reception', 'creer'));
	print ($object->trueWidth && $object->width_units != '') ? ' '.measuringUnitString(0, "size", $object->width_units) : '';
	print '</td></tr>';

	// Height
	print '<tr><td>'.$form->editfieldkey("Height", 'trueHeight', $object->trueHeight, $object, $user->hasRight('reception', 'creer')).'</td><td colspan="3">';
	if ($action == 'edittrueHeight') {
		print '<form name="settrueHeight" action="'.$_SERVER["PHP_SELF"].'" method="post">';
		print '<input name="action" value="settrueHeight" type="hidden">';
		print '<input name="id" value="'.$object->id.'" type="hidden">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input id="trueHeight" name="trueHeight" value="'.$object->trueHeight.'" type="text">';
		print $formproduct->selectMeasuringUnits("size_units", "size", $object->size_units, 0, 2);
		print ' <input class="button" name="modify" value="'.$langs->trans("Modify").'" type="submit">';
		print ' <input class="button button-cancel" name="cancel" value="'.$langs->trans("Cancel").'" type="submit">';
		print '</form>';
	} else {
		print $object->trueHeight;
		print ($object->trueHeight && $object->height_units != '') ? ' '.measuringUnitString(0, "size", $object->height_units) : '';
	}

	print '</td></tr>';

	// Depth
	print '<tr><td>'.$form->editfieldkey("Depth", 'trueDepth', $object->trueDepth, $object, $user->hasRight('reception', 'creer')).'</td><td colspan="3">';
	print $form->editfieldval("Depth", 'trueDepth', $object->trueDepth, $object, $user->hasRight('reception', 'creer'));
	print ($object->trueDepth && $object->depth_units != '') ? ' '.measuringUnitString(0, "size", $object->depth_units) : '';
	print '</td></tr>';

	// Volume
	print '<tr><td>';
	print $langs->trans("Volume");
	print '</td>';
	print '<td colspan="3">';
	$calculatedVolume = 0;
	$volumeUnit = 0;
	if ($object->trueWidth && $object->trueHeight && $object->trueDepth) {
		$calculatedVolume = ($object->trueWidth * $object->trueHeight * $object->trueDepth);
		$volumeUnit = $object->size_units * 3;
	}
	// If reception volume not defined we use sum of products
	if ($calculatedVolume > 0) {
		if ($volumeUnit < 50) {
			print showDimensionInBestUnit($calculatedVolume, $volumeUnit, "volume", $langs, isset($conf->global->MAIN_VOLUME_DEFAULT_ROUND) ? $conf->global->MAIN_VOLUME_DEFAULT_ROUND : -1, isset($conf->global->MAIN_VOLUME_DEFAULT_UNIT) ? $conf->global->MAIN_VOLUME_DEFAULT_UNIT : 'no');
		} else {
			print $calculatedVolume.' '.measuringUnitString(0, "volume", (string) $volumeUnit);
		}
	}
	if ($totalVolume > 0) {
		if ($calculatedVolume) {
			print ' ('.$langs->trans("SumOfProductVolumes").': ';
		}
		print showDimensionInBestUnit($totalVolume, 0, "volume", $langs, isset($conf->global->MAIN_VOLUME_DEFAULT_ROUND) ? $conf->global->MAIN_VOLUME_DEFAULT_ROUND : -1, isset($conf->global->MAIN_VOLUME_DEFAULT_UNIT) ? $conf->global->MAIN_VOLUME_DEFAULT_UNIT : 'no');
		//if (empty($calculatedVolume)) print ' ('.$langs->trans("Calculated").')';
		if ($calculatedVolume) {
			print ')';
		}
	}
	print "</td>\n";
	print '</tr>';

	// Other attributes
	$cols = 2;

	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

	print '</table>';

	print '</div>';
	print '<div class="fichehalfright">';
	print '<div class="underbanner clearboth"></div>';

	print '<table class="border centpercent tableforfield">';

	// Reception method
	print '<tr><td height="10">';
	print '<table class="nobordernopadding centpercent"><tr><td>';
	print $langs->trans('ReceptionMethod');
	print '</td>';

	if ($action != 'editshipping_method_id' && $permissiontoadd) {
		print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editshipping_method_id&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->trans('SetReceptionMethod'), 1).'</a></td>';
	}
	print '</tr></table>';
	print '</td><td colspan="2">';
	if ($action == 'editshipping_method_id') {
		print '<form name="setshipping_method_id" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="post">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="action" value="setshipping_method_id">';
		$object->fetch_delivery_methods();
		print $form->selectarray("shipping_method_id", $object->meths, $object->shipping_method_id, 1, 0, 0, "", 1);
		if ($user->admin) {
			print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
		}
		print '<input type="submit" class="button button-edit" value="'.$langs->trans('Modify').'">';
		print '</form>';
	} else {
		if ($object->shipping_method_id > 0) {
			// Get code using getLabelFromKey
			$code = $langs->getLabelFromKey($db, $object->shipping_method_id, 'c_shipment_mode', 'rowid', 'code');
			print $langs->trans("SendingMethod".strtoupper($code));
		}
	}
	print '</td>';
	print '</tr>';

	// Tracking Number
	print '<tr><td class="titlefield">'.$form->editfieldkey("TrackingNumber", 'tracking_number', $object->tracking_number, $object, $user->hasRight('reception', 'creer')).'</td><td colspan="3">';
	print $form->editfieldval("TrackingNumber", 'tracking_number', $object->tracking_url, $object, $user->hasRight('reception', 'creer'), 'safehtmlstring', $object->tracking_number);
	print '</td></tr>';

	// Incoterms
	if (isModEnabled('incoterm')) {
		print '<tr><td>';
		print '<table width="100%" class="nobordernopadding"><tr><td>';
		print $langs->trans('IncotermLabel');
		print '<td><td class="right">';
		if ($user->hasRight('reception', 'creer')) {
			print '<a class="editfielda" href="'.DOL_URL_ROOT.'/reception/card.php?id='.$object->id.'&action=editincoterm&token='.newToken().'">'.img_edit().'</a>';
		} else {
			print '&nbsp;';
		}
		print '</td></tr></table>';
		print '</td>';
		print '<td colspan="3">';
		if ($action != 'editincoterm') {
			print $form->textwithpicto($object->display_incoterms(), $object->label_incoterms, 1);
		} else {
			print $form->select_incoterms((!empty($object->fk_incoterms) ? $object->fk_incoterms : ''), (!empty($object->location_incoterms) ? $object->location_incoterms : ''), $_SERVER['PHP_SELF'].'?id='.$object->id);
		}
		print '</td></tr>';
	}

	print "</table>";

	print '</div>';
	print '</div>';

	print '<div class="clearboth"></div>';


	// Lines of products
	if ($action == 'editline') {
		print '<form name="updateline" id="updateline" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;lineid='.$line_id.'" method="POST">
		<input type="hidden" name="token" value="' . newToken().'">
		<input type="hidden" name="action" value="updateline">
		<input type="hidden" name="mode" value="">
		<input type="hidden" name="id" value="' . $object->id.'">';
	}
	print '<br><br>';

	print '<div class="div-table-responsive-no-min">';
	print '<table id="tablelines" class="noborder centpercent">';
	print '<thead>';
	print '<tr class="liste_titre">';
	// #
	if (getDolGlobalString('MAIN_VIEW_LINE_NUMBER')) {
		print '<td width="5" class="center">&nbsp;</td>';
	}
	// Product/Service
	print '<td>'.$langs->trans("Products").'</td>';
	// Comment
	print '<td>'.$langs->trans("Comment").'</td>';
	// Qty
	print '<td class="center">'.$langs->trans("QtyOrdered").'</td>';
	if ($origin && $origin_id > 0) {
		print '<td class="center">'.$langs->trans("QtyInOtherReceptions").'</td>';
	}
	if ($action == 'editline') {
		$editColspan = 3;
		if (!isModEnabled('stock')) {
			$editColspan--;
		}
		if (empty($conf->productbatch->enabled)) {
			$editColspan--;
		}
		print '<td class="center" colspan="'.$editColspan.'">';
		if ($object->statut <= 1) {
			print $langs->trans("QtyToReceive").' - ';
		} else {
			print $langs->trans("QtyReceived").' - ';
		}
		if (isModEnabled('stock')) {
			print $langs->trans("WarehouseTarget").' - ';
		}
		if (isModEnabled('productbatch')) {
			print $langs->trans("Batch");
		}
		print '</td>';
	} else {
		$statusreceived = $object::STATUS_CLOSED;
		if (getDolGlobalInt("STOCK_CALCULATE_ON_RECEPTION")) {
			$statusreceived = $object::STATUS_VALIDATED;
		}
		if (getDolGlobalInt("STOCK_CALCULATE_ON_RECEPTION_CLOSE")) {
			$statusreceived = $object::STATUS_CLOSED;
		}
		if ($object->statut < $statusreceived) {
			print '<td class="center">'.$langs->trans("QtyToReceive").'</td>';
		} else {
			print '<td class="center">'.$langs->trans("QtyReceived").'</td>';
		}
		if (isModEnabled('stock')) {
			print '<td class="left">'.$langs->trans("WarehouseTarget").'</td>';
		}

		if (isModEnabled('productbatch')) {
			print '<td class="left">'.$langs->trans("Batch").'</td>';
		}
	}
	print '<td class="center">'.$langs->trans("CalculatedWeight").'</td>';
	print '<td class="center">'.$langs->trans("CalculatedVolume").'</td>';
	//print '<td class="center">'.$langs->trans("Size").'</td>';
	if ($object->statut == 0) {
		print '<td class="linecoledit"></td>';
		print '<td class="linecoldelete" width="10"></td>';
	}
	print "</tr>\n";
	print '</thead>';

	$var = false;

	if (getDolGlobalInt('MAIN_MULTILANGS') && getDolGlobalString('PRODUIT_TEXTS_IN_THIRDPARTY_LANGUAGE')) {
		$object->fetch_thirdparty();
		$outputlangs = $langs;
		$newlang = '';
		if (empty($newlang) && GETPOST('lang_id', 'aZ09')) {
			$newlang = GETPOST('lang_id', 'aZ09');
		}
		if (empty($newlang)) {
			$newlang = $object->thirdparty->default_lang;
		}
		if (!empty($newlang)) {
			$outputlangs = new Translate("", $conf);
			$outputlangs->setDefaultLang($newlang);
		}
	}

	// Get list of products already sent for same source object into $alreadysent
	$alreadysent = array();

	$origin = 'commande_fournisseur';

	if ($origin && $origin_id > 0) {
		$sql = "SELECT obj.rowid, obj.fk_product, obj.label, obj.description, obj.product_type as fk_product_type, obj.qty as qty_asked, obj.date_start, obj.date_end";
		$sql .= ", ed.rowid as receptionline_id, ed.qty, ed.fk_reception as reception_id,  ed.fk_entrepot";
		$sql .= ", e.rowid as reception_id, e.ref as reception_ref, e.date_creation, e.date_valid, e.date_delivery, e.date_reception";
		//if (getDolGlobalInt('MAIN_SUBMODULE_DELIVERY')) $sql .= ", l.rowid as livraison_id, l.ref as livraison_ref, l.date_delivery, ld.qty as qty_received";
		$sql .= ', p.label as product_label, p.ref, p.fk_product_type, p.rowid as prodid, p.tobatch as product_tobatch';
		$sql .= ', p.description as product_desc';
		$sql .= " FROM ".MAIN_DB_PREFIX."receptiondet_batch as ed";
		$sql .= ", ".MAIN_DB_PREFIX."reception as e";
		$sql .= ", ".MAIN_DB_PREFIX.$origin."det as obj";
		//if (getDolGlobalInt('MAIN_SUBMODULE_DELIVERY')) $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."delivery as l ON l.fk_reception = e.rowid LEFT JOIN ".MAIN_DB_PREFIX."deliverydet as ld ON ld.fk_delivery = l.rowid  AND obj.rowid = ld.fk_origin_line";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product as p ON obj.fk_product = p.rowid";
		$sql .= " WHERE e.entity IN (".getEntity('reception').")";
		$sql .= " AND obj.fk_commande = ".((int) $origin_id);
		$sql .= " AND obj.rowid = ed.fk_elementdet";
		$sql .= " AND ed.fk_reception = e.rowid";
		$sql .= " AND ed.fk_reception !=".((int) $object->id);
		//if ($filter) $sql.= $filter;
		$sql .= " ORDER BY obj.fk_product";

		dol_syslog("get list of reception lines", LOG_DEBUG);
		$resql = $db->query($sql);
		if ($resql) {
			$num = $db->num_rows($resql);
			$i = 0;

			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				if ($obj) {
					// $obj->rowid is rowid in $origin."det" table
					$alreadysent[$obj->rowid][$obj->receptionline_id] = array('reception_ref' => $obj->reception_ref, 'reception_id' => $obj->reception_id, 'warehouse' => $obj->fk_entrepot, 'qty' => $obj->qty, 'date_valid' => $obj->date_valid, 'date_delivery' => $obj->date_delivery);
				}
				$i++;
			}
		}
		//var_dump($alreadysent);
	}

	$arrayofpurchaselinealreadyoutput = array();

	// Loop on each product to send/sent. Warning: $lines must be sorted by ->fk_commandefourndet (it is a regroupment key on output)
	print '<tbody>';
	for ($i = 0; $i < $num_prod; $i++) {
		print '<!-- origin line id = '.(!empty($lines[$i]->origin_line_id) ? $lines[$i]->origin_line_id : 0).' -->'; // id of order line
		print '<tr class="oddeven" id="row-'.$lines[$i]->id.'" data-id="'.$lines[$i]->id.'" data-element="'.$lines[$i]->element.'">';

		// #
		if (getDolGlobalString('MAIN_VIEW_LINE_NUMBER')) {
			print '<td class="center">'.($i + 1).'</td>';
		}

		// Predefined product or service
		if ($lines[$i]->fk_product > 0) {
			// Define output language
			if (getDolGlobalInt('MAIN_MULTILANGS') && getDolGlobalString('PRODUIT_TEXTS_IN_THIRDPARTY_LANGUAGE')) {
				$prod = new Product($db);
				$prod->fetch($lines[$i]->fk_product);
				$label = (!empty($prod->multilangs[$outputlangs->defaultlang]["label"])) ? $prod->multilangs[$outputlangs->defaultlang]["label"] : $lines[$i]->product->label;
			} else {
				$label = (!empty($lines[$i]->product->label) ? $lines[$i]->product->label : $lines[$i]->product->product_label);
			}

			print '<td class="linecoldescription">';
			if (!array_key_exists($lines[$i]->fk_commandefourndet, $arrayofpurchaselinealreadyoutput)) {
				$text = $lines[$i]->product->getNomUrl(1);
				$text .= ' - '.$label;
				$description = (getDolGlobalInt('PRODUIT_DESC_IN_FORM_ACCORDING_TO_DEVICE') ? '' : dol_htmlentitiesbr($lines[$i]->product->description));
				print $form->textwithtooltip($text, $description, 3, '', '', $i);
				print_date_range(!empty($lines[$i]->date_start) ? $lines[$i]->date_start : 0, !empty($lines[$i]->date_end) ? $lines[$i]->date_end : 0);
				if (getDolGlobalInt('PRODUIT_DESC_IN_FORM_ACCORDING_TO_DEVICE')) {
					print (!empty($lines[$i]->product->description) && $lines[$i]->description != $lines[$i]->product->description) ? '<br>'.dol_htmlentitiesbr($lines[$i]->description) : '';
				}
			}
			print "</td>\n";
		} else {
			print '<td class="linecoldescription">';
			if (!array_key_exists($lines[$i]->fk_commandefourndet, $arrayofpurchaselinealreadyoutput)) {
				if ($lines[$i]->product_type == Product::TYPE_SERVICE) {
					$text = img_object($langs->trans('Service'), 'service');
				} else {
					$text = img_object($langs->trans('Product'), 'product');
				}

				if (!empty($lines[$i]->label)) {
					$text .= ' <strong>'.$lines[$i]->label.'</strong>';
					print $form->textwithtooltip($text, $lines[$i]->description, 3, '', '', $i);
				} else {
					print $text.' '.nl2br($lines[$i]->description);
				}

				print_date_range($lines[$i]->date_start, $lines[$i]->date_end);
			}
			print "</td>\n";
		}

		if ($action == 'editline' && $lines[$i]->id == $line_id) {
			print '<td><input name="comment'.$line_id.'" id="comment'.$line_id.'" value="'.dol_escape_htmltag($lines[$i]->comment).'"></td>';
		} else {
			print '<td style="white-space: pre-wrap; max-width: 200px;">'.dol_escape_htmltag($lines[$i]->comment).'</td>';
		}


		// Qty ordered
		print '<td class="center linecolqty">';
		if (!array_key_exists($lines[$i]->fk_commandefourndet, $arrayofpurchaselinealreadyoutput)) {
			print $lines[$i]->qty_asked;
		}
		print '</td>';

		// Qty in other receptions (with reception and warehouse used)
		if ($origin && $origin_id > 0) {
			print '<td class="center nowrap linecolqtyinotherreceptions">';
			$htmltooltip = '';
			$qtyalreadyreceived = 0;
			if (!array_key_exists($lines[$i]->fk_commandefourndet, $arrayofpurchaselinealreadyoutput)) {
				foreach ($alreadysent as $key => $val) {
					if ($lines[$i]->fk_commandefourndet == $key) {
						$j = 0;
						foreach ($val as $receptionline_id => $receptionline_var) {
							if ($receptionline_var['reception_id'] == $lines[$i]->fk_reception) {
								continue; // We want to show only "other receptions"
							}

							$j++;
							if ($j > 1) {
								$htmltooltip .= '<br>';
							}
							$reception_static->fetch($receptionline_var['reception_id']);
							$htmltooltip .= $reception_static->getNomUrl(1, 0, 0, 0, 1);
							$htmltooltip .= ' - '.$receptionline_var['qty'];

							$htmltext = $langs->trans("DateValidation").' : '.(empty($receptionline_var['date_valid']) ? $langs->trans("Draft") : dol_print_date($receptionline_var['date_valid'], 'dayhour'));
							if (isModEnabled('stock') && $receptionline_var['warehouse'] > 0) {
								$warehousestatic->fetch($receptionline_var['warehouse']);
								$htmltext .= '<br>'.$langs->trans("From").' : '.$warehousestatic->getNomUrl(1, '', 0, 1);
							}
							$htmltooltip .= ' '.$form->textwithpicto('', $htmltext, 1);

							$qtyalreadyreceived += $receptionline_var['qty'];
						}
						if ($j) {
							$htmltooltip = $langs->trans("QtyInOtherReceptions").'...<br><br>'.$htmltooltip.'<br><input type="submit" name="dummyhiddenbuttontogetfocus" style="display:none" autofocus>';
						}
					}
				}
			}
			print $form->textwithpicto($qtyalreadyreceived, $htmltooltip, 1, 'info', '', 0, 3, 'tooltip'.$lines[$i]->id);
			print '</td>';
		}

		if ($action == 'editline' && $lines[$i]->id == $line_id) {
			// edit mode
			print '<td colspan="'.$editColspan.'" class="center"><table class="nobordernopadding">';
			if (isModEnabled('stock')) {
				if ($lines[$i]->fk_product > 0) {
					print '<!-- case edit 1 -->';
					print '<tr>';
					// Qty to receive or received
					print '<td><input name="qtyl'.$line_id.'" id="qtyl'.$line_id.'" type="text" size="4" value="'.$lines[$i]->qty.'"></td>';
					// Warehouse source
					print '<td>'.$formproduct->selectWarehouses($lines[$i]->fk_entrepot, 'entl'.$line_id, '', 1, 0, $lines[$i]->fk_product, '', 1).'</td>';
					// Batch number management
					if ($conf->productbatch->enabled && !empty($lines[$i]->product->status_batch)) {
						print '<td class="nowraponall left"><input name="batch'.$line_id.'" id="batch'.$line_id.'" type="text" value="'.$lines[$i]->batch.'"><br>';
						if (!getDolGlobalString('PRODUCT_DISABLE_SELLBY')) {
							print $langs->trans('SellByDate').' : ';
							print $form->selectDate($lines[$i]->sellby, 'dlc'.$line_id, 0, 0, 1, "").'</br>';
						}
						if (!getDolGlobalString('PRODUCT_DISABLE_EATBY')) {
							print $langs->trans('EatByDate').' : ';
							print $form->selectDate($lines[$i]->eatby, 'dluo'.$line_id, 0, 0, 1, "");
						}
						print '</td>';
					}
					print '</tr>';
				} else {
					print '<!-- case edit 2 -->';
					print '<tr>';
					// Qty to receive or received
					print '<td><input name="qtyl'.$line_id.'" id="qtyl'.$line_id.'" type="text" size="4" value="'.$lines[$i]->qty.'"></td>';
					// Warehouse source
					print '<td></td>';
					// Batch number management
					print '<td></td>';
					print '</tr>';
				}
			}
			print '</table></td>';
		} else {
			// Qty to receive or received
			print '<td class="center linecolqtytoreceive">'.$lines[$i]->qty.'</td>';

			// Warehouse source
			if (isModEnabled('stock')) {
				if ($lines[$i]->fk_entrepot > 0) {
					$entrepot = new Entrepot($db);
					$entrepot->fetch($lines[$i]->fk_entrepot);

					print '<td class="left tdoverflowmax150" title="'.dol_escape_htmltag($entrepot->label).'">';
					print $entrepot->getNomUrl(1);
					print '</td>';
				} else {
					print '<td></td>';
				}
			}

			// Batch number management
			if (isModEnabled('productbatch')) {
				if (isset($lines[$i]->batch)) {
					print '<!-- Detail of lot -->';
					print '<td class="linecolbatch nowrap">';
					$detail = $langs->trans("NA");
					if ($lines[$i]->product->status_batch > 0 && $lines[$i]->fk_product > 0) {
						require_once DOL_DOCUMENT_ROOT.'/product/stock/class/productlot.class.php';
						$productlot = new Productlot($db);
						$reslot = $productlot->fetch(0, $lines[$i]->fk_product, $lines[$i]->batch);
						if ($reslot > 0) {
							$detail = $productlot->getNomUrl(1);
						} else {
							// lot is not created and info is only in reception lines
							$batchinfo = $langs->trans("Batch").': '.$lines[$i]->batch;
							if (!getDolGlobalString('PRODUCT_DISABLE_SELLBY')) {
								$batchinfo .= ' - '.$langs->trans("SellByDate").': '.dol_print_date($lines[$i]->sellby, "day");
							}
							if (!getDolGlobalString('PRODUCT_DISABLE_EATBY')) {
								$batchinfo .= ' - '.$langs->trans("EatByDate").': '.dol_print_date($lines[$i]->eatby, "day");
							}
							$detail = $form->textwithtooltip(img_picto('', 'object_barcode').' '.$langs->trans("DetailBatchNumber"), $batchinfo);
						}
					}
					print $detail . '</td>';
				} else {
					print '<td></td>';
				}
			}
		}

		// Weight
		print '<td class="center linecolweight">';
		if (!empty($lines[$i]->fk_product_type) && $lines[$i]->fk_product_type == Product::TYPE_PRODUCT) {
			print $lines[$i]->product->weight * $lines[$i]->qty.' '.measuringUnitString(0, "weight", $lines[$i]->product->weight_units);
		} else {
			print '&nbsp;';
		}
		print '</td>';

		// Volume
		print '<td class="center linecolvolume">';
		if (!empty($lines[$i]->fk_product_type) && $lines[$i]->fk_product_type == Product::TYPE_PRODUCT) {
			print $lines[$i]->product->volume * $lines[$i]->qty.' '.measuringUnitString(0, "volume", $lines[$i]->product->volume_units);
		} else {
			print '&nbsp;';
		}
		print '</td>';


		if ($action == 'editline' && $lines[$i]->id == $line_id) {
			print '<td class="center valignmiddle" colspan="2">';
			print '<input type="submit" class="button small button-save" id="savelinebutton marginbottomonly" name="save" value="'.$langs->trans("Save").'"><br>';
			print '<input type="submit" class="button small button-cancel" id="cancellinebutton" name="cancel" value="'.$langs->trans("Cancel").'"><br>';
			print '</td>';
		} elseif ($object->statut == Reception::STATUS_DRAFT) {
			// edit-delete buttons
			print '<td class="linecoledit center">';
			print '<a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=editline&token='.newToken().'&lineid='.$lines[$i]->id.'">'.img_edit().'</a>';
			print '</td>';
			print '<td class="linecoldelete" width="10">';
			print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=deleteline&token='.newToken().'&lineid='.$lines[$i]->id.'">'.img_delete().'</a>';
			print '</td>';

			// Display lines extrafields
			if (!empty($rowExtrafieldsStart)) {
				print $rowExtrafieldsStart;
				print $rowExtrafieldsView;
				print $rowEnd;
			}
		}
		print "</tr>";

		$arrayofpurchaselinealreadyoutput[$lines[$i]->fk_commandefourndet] = $lines[$i]->fk_commandefourndet;

		// Display lines extrafields
		$extralabelslines = $extrafields->attributes[$lines[$i]->table_element];
		if (!empty($extralabelslines) && is_array($extralabelslines) && count($extralabelslines) > 0) {
			$colspan = 8;
			if (isModEnabled('stock')) {
				$colspan++;
			}
			if (isModEnabled('productbatch')) {
				$colspan++;
			}

			$line = new CommandeFournisseurDispatch($db);
			$line->id = $lines[$i]->id;
			$line->fetch_optionals();

			if ($action == 'editline' && $lines[$i]->id == $line_id) {
				print $line->showOptionals($extrafields, 'edit', array('colspan' => $colspan), '');
			} else {
				print $line->showOptionals($extrafields, 'view', array('colspan' => $colspan), '');
			}
		}
	}
	print '</tbody>';

	// TODO Show also lines ordered but not delivered

	print "</table>\n";
	print '</div>';


	print dol_get_fiche_end();


	$object->fetchObjectLinked($object->id, $object->element);


	/*
	 *    Boutons actions
	 */

	if (($user->socid == 0) && ($action != 'presend')) {
		print '<div class="tabsAction">';

		$parameters = array();
		$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		if (empty($reshook)) {
			if ($object->statut == Reception::STATUS_DRAFT && $num_prod > 0) {
				if ((!getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('reception', 'creer'))
				 || (getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('reception', 'reception_advance', 'validate'))) {
					print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=valid&token='.newToken().'">'.$langs->trans("Validate").'</a>';
				} else {
					print '<a class="butActionRefused" href="#" title="'.$langs->trans("NotAllowed").'">'.$langs->trans("Validate").'</a>';
				}
			}
			// Back to draft
			if ($object->statut == Reception::STATUS_VALIDATED && $user->hasRight('reception', 'creer')) {
				print '<div class="inline-block divButAction"><a class="butAction" href="card.php?id='.$object->id.'&action=modif&token='.newToken().'">'.$langs->trans('SetToDraft').'</a></div>';
			}

			// TODO add alternative status
			// 0=draft, 1=validated, 2=billed, we miss a status "delivered" (only available on order)
			if ($object->statut == Reception::STATUS_CLOSED && $user->hasRight('reception', 'creer')) {
				print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=reopen&token='.newToken().'">'.$langs->trans("ReOpen").'</a>';
			}

			// Send
			if (empty($user->socid)) {
				if ($object->statut > 0) {
					if (!getDolGlobalString('MAIN_USE_ADVANCED_PERMS') || $user->hasRight('reception', 'reception_advance', 'send')) {
						print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=presend&mode=init#formmailbeforetitle">'.$langs->trans('SendByMail').'</a>';
					} else {
						print '<a class="butActionRefused" href="#">'.$langs->trans('SendByMail').'</a>';
					}
				}
			}

			// Create bill
			if (isModEnabled("supplier_invoice") && ($object->statut == Reception::STATUS_VALIDATED || $object->statut == Reception::STATUS_CLOSED)) {
				if ($user->hasRight('fournisseur', 'facture', 'creer') || $user->hasRight('supplier_invoice', 'creer')) {
					if (getDolGlobalString('WORKFLOW_BILL_ON_RECEPTION') !== '0') {
						print '<a class="butAction" href="'.DOL_URL_ROOT.'/fourn/facture/card.php?action=create&amp;origin='.$object->element.'&amp;originid='.$object->id.'&amp;socid='.$object->socid.'">'.$langs->trans("CreateBill").'</a>';
					}
				}
			}


			// Set Billed and Closed
			if ($object->statut == Reception::STATUS_VALIDATED) {
				if ($user->hasRight('reception', 'creer') && $object->statut > 0) {
					if (!$object->billed && getDolGlobalString('WORKFLOW_BILL_ON_RECEPTION') !== '0') {
						print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=classifybilled&token='.newToken().'">'.$langs->trans('ClassifyBilled').'</a>';
					}
					print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=classifyclosed&token='.newToken().'">'.$langs->trans("Close").'</a>';
				}
			}

			if ($user->hasRight('reception', 'supprimer')) {
				print '<a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=delete&token='.newToken().'">'.$langs->trans("Delete").'</a>';
			}
		}

		print '</div>';
	}


	/*
	 * Documents generated
	 */

	if ($action != 'presend' && $action != 'editline') {
		print '<div class="fichecenter"><div class="fichehalfleft">';

		$objectref = dol_sanitizeFileName($object->ref);
		$filedir = $conf->reception->dir_output."/".$objectref;

		$urlsource = $_SERVER["PHP_SELF"]."?id=".$object->id;

		$genallowed = $user->hasRight('reception', 'lire');
		$delallowed = $user->hasRight('reception', 'creer');

		print $formfile->showdocuments('reception', $objectref, $filedir, $urlsource, $genallowed, $delallowed, $object->model_pdf, 1, 0, 0, 28, 0, '', '', '', $soc->default_lang);

		// Show links to link elements
		//$linktoelem = $form->showLinkToObjectBlock($object, null, array('order'));
		$somethingshown = $form->showLinkedObjectBlock($object, '');

		print '</div><div class="fichehalfright">';

		print '</div></div>';
	}

	// Presend form
	$modelmail = 'shipping_send';
	$defaulttopic = 'SendReceptionRef';
	$diroutput = $conf->reception->dir_output;
	$trackid = 'rec'.$object->id;

	include DOL_DOCUMENT_ROOT.'/core/tpl/card_presend.tpl.php';
}


llxFooter();

$db->close();
