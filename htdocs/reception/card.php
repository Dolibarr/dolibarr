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
if (!empty($conf->product->enabled) || !empty($conf->service->enabled)) {
	require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
}
if (!empty($conf->propal->enabled)) {
	require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
}
if ((!empty($conf->fournisseur->enabled) && empty($conf->global->MAIN_USE_NEW_SUPPLIERMOD)) || !empty($conf->supplier_order->enabled)) {
	require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
	require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.dispatch.class.php';
}
if (!empty($conf->productbatch->enabled)) {
	require_once DOL_DOCUMENT_ROOT.'/product/class/productbatch.class.php';
}
if (!empty($conf->projet->enabled)) {
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
}

$langs->loadLangs(array("receptions", "companies", "bills", 'deliveries', 'orders', 'stocks', 'other', 'propal', 'sendings'));

if (!empty($conf->incoterm->enabled)) {
	$langs->load('incoterm');
}
if (!empty($conf->productbatch->enabled)) {
	$langs->load('productbatch');
}

$origin = GETPOST('origin', 'alpha') ?GETPOST('origin', 'alpha') : 'reception'; // Example: commande, propal
$origin_id = GETPOST('id', 'int') ? GETPOST('id', 'int') : '';
$id = $origin_id;
if (empty($origin_id)) {
	$origin_id  = GETPOST('origin_id', 'int'); // Id of order or propal
}
if (empty($origin_id)) {
	$origin_id  = GETPOST('object_id', 'int'); // Id of order or propal
}
if (empty($origin_id)) {
	$origin_id  = GETPOST('originid', 'int'); // Id of order or propal
}
$ref = GETPOST('ref', 'alpha');
$line_id = GETPOST('lineid', 'int') ?GETPOST('lineid', 'int') : '';

$action	= GETPOST('action', 'alpha');
//Select mail models is same action as presend
if (GETPOST('modelselected')) {
	$action = 'presend';
}
$confirm = GETPOST('confirm', 'alpha');
$cancel = GETPOST('cancel', 'alpha');

//PDF
$hidedetails = (GETPOST('hidedetails', 'int') ? GETPOST('hidedetails', 'int') : (!empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS) ? 1 : 0));
$hidedesc = (GETPOST('hidedesc', 'int') ? GETPOST('hidedesc', 'int') : (!empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DESC) ? 1 : 0));
$hideref = (GETPOST('hideref', 'int') ? GETPOST('hideref', 'int') : (!empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_REF) ? 1 : 0));

$object = new Reception($db);
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);
$extrafields->fetch_name_optionals_label($object->table_element_line);

// Load object. Make an object->fetch
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be include, not include_once

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('receptioncard', 'globalcard'));

$permissiondellink = $user->rights->reception->creer; // Used by the include of actions_dellink.inc.php
//var_dump($object->lines[0]->detail_batch);

$date_delivery = dol_mktime(GETPOST('date_deliveryhour', 'int'), GETPOST('date_deliverymin', 'int'), 0, GETPOST('date_deliverymonth', 'int'), GETPOST('date_deliveryday', 'int'), GETPOST('date_deliveryyear', 'int'));

if ($id > 0 || !empty($ref)) {
	$object->fetch($id, $ref);
	$object->fetch_thirdparty();

	if (!empty($object->origin)) {
		$origin = $object->origin;

		$object->fetch_origin();
		$typeobject = $object->origin;
	}

	// Linked documents
	if ($origin == 'order_supplier' && $object->$typeobject->id && (!empty($conf->fournisseur->enabled) && empty($conf->global->MAIN_USE_NEW_SUPPLIERMOD) || !empty($conf->supplier_order->enabled))) {
		$origin_id = $object->$typeobject->id;
		$objectsrc = new CommandeFournisseur($db);
		$objectsrc->fetch($object->$typeobject->id);
	}
}

// Security check
$socid = '';
if ($user->socid) {
	$socid = $user->socid;
}

if ($origin == 'reception') {
	$result = restrictedArea($user, 'reception', $id);
} else {
	if ($origin == 'supplierorder' || $origin == 'order_supplier') {
		$result = restrictedArea($user, 'fournisseur', $origin_id, 'commande_fournisseur', 'commande');
	} elseif (empty($user->rights->{$origin}->lire) && empty($user->rights->{$origin}->read)) {
		accessforbidden();
	}
}


/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	if ($cancel) {
		$action = '';
	}

	include DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php'; // Must be include, not include_once

	// Reopen
	if ($action == 'reopen' && $user->rights->reception->creer) {
		$result = $object->reOpen();
	}

	// Confirm back to draft status
	if ($action == 'modif' && $user->rights->reception->creer) {
		$result = $object->setDraft($user);
		if ($result >= 0) {
			// Define output language
			if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
				$outputlangs = $langs;
				$newlang = '';
				if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
					$newlang = GETPOST('lang_id', 'aZ09');
				}
				if ($conf->global->MAIN_MULTILANGS && empty($newlang)) {
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
	if ($action == 'set_incoterms' && !empty($conf->incoterm->enabled)) {
		$result = $object->setIncoterms(GETPOST('incoterm_id', 'int'), GETPOST('location_incoterms', 'alpha'));
	}

	if ($action == 'setref_supplier') {
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

	if ($action == 'update_extras') {
		$object->oldcopy = dol_clone($object);

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
	if ($action == 'add' && $user->rights->reception->creer) {
		$error = 0;
		$predef = '';

		$db->begin();

		$object->note = GETPOST('note', 'alpha');
		$object->origin = $origin;
		$object->origin_id = $origin_id;
		$object->fk_project = GETPOST('projectid', 'int');
		$object->weight = GETPOST('weight', 'int') == '' ? null : GETPOST('weight', 'int');
		$object->sizeH = GETPOST('sizeH', 'int') == '' ? null : GETPOST('sizeH', 'int');
		$object->sizeW = GETPOST('sizeW', 'int') == '' ? null : GETPOST('sizeW', 'int');
		$object->sizeS = GETPOST('sizeS', 'int') == '' ? null : GETPOST('sizeS', 'int');
		$object->size_units = GETPOST('size_units', 'int');
		$object->weight_units = GETPOST('weight_units', 'int');

		// On va boucler sur chaque ligne du document d'origine pour completer objet reception
		// avec info diverses + qte a livrer

		if ($object->origin == "supplierorder") {
			$classname = 'CommandeFournisseur';
		} else {
			$classname = ucfirst($object->origin);
		}
		$objectsrc = new $classname($db);
		$objectsrc->fetch($object->origin_id);

		$object->socid = $objectsrc->socid;
		$object->ref_supplier = GETPOST('ref_supplier', 'alpha');
		$object->model_pdf = GETPOST('model');
		$object->date_delivery = $date_delivery; // Date delivery planed
		$object->fk_delivery_address = $objectsrc->fk_delivery_address;
		$object->shipping_method_id = GETPOST('shipping_method_id', 'int');
		$object->tracking_number = GETPOST('tracking_number', 'alpha');
		$object->note_private = GETPOST('note_private', 'restricthtml');
		$object->note_public = GETPOST('note_public', 'restricthtml');
		$object->fk_incoterms = GETPOST('incoterm_id', 'int');
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

		for ($i = 1; $i <= $num; $i++) {
			$idl = "idl".$i;

			$sub_qty = array();
			$subtotalqty = 0;

			$j = 0;
			$batch = "batchl".$i."_0";
			$stockLocation = "ent1".$i."_0";
			$qty = "qtyl".$i;

			//var_dump(GETPOST($qty,'int')); var_dump($_POST); var_dump($batch);exit;
			//reception line for product with no batch management and no multiple stock location
			if (GETPOST($qty, 'alpha') > 0) {
				$totalqty += price2num(GETPOST($qty, 'alpha'), 'MS');
			}

			// Extrafields
			$array_options[$i] = $extrafields->getOptionalsFromPost($object->table_element_line, $i);
		}


		if ($totalqty > 0) {  // There is at least one thing to ship
			//var_dump($_POST);exit;
			for ($i = 1; $i <= $num; $i++) {
				$lineToTest = '';
				$lineId = GETPOST($idl, 'int');
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
				// EATBY <-> DLUO see productbatch.class.php
				// SELLBY <-> DLC
				$eatby = "dluo".$i;
				$sellby = "dlc".$i;
				$batch = "batch".$i;

				if (GETPOST($qty, 'int') > 0 || (GETPOST($qty, 'int') == 0 && $conf->global->RECEPTION_GETS_ALL_ORDER_PRODUCTS)) {
					$ent = "entl".$i;

					$idl = "idl".$i;

					$entrepot_id = is_numeric(GETPOST($ent, 'int')) ? GETPOST($ent, 'int') : GETPOST('entrepot_id', 'int');

					if (!empty($lineToTest)) {
						$fk_product = $lineToTest->fk_product;
					} else {
						$fk_product = $linesrc->fk_product;
					}

					if ($entrepot_id < 0) {
						$entrepot_id = '';
					}
					if (!($fk_product > 0) && empty($conf->global->STOCK_SUPPORTS_SERVICES)) {
						$entrepot_id = 0;
					}
					$eatby = GETPOST($eatby, 'alpha');
					$sellby = GETPOST($sellby, 'alpha');
					$eatbydate = str_replace('/', '-', $eatby);
					$sellbydate = str_replace('/', '-', $sellby);

					$ret = $object->addline($entrepot_id, GETPOST($idl, 'int'), GETPOST($qty, 'int'), $array_options[$i], GETPOST($comment, 'alpha'), strtotime($eatbydate), strtotime($sellbydate), GETPOST($batch, 'alpha'));
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
			$_GET["commande_id"] = GETPOST('commande_id', 'int');
			$action = 'create';
		}
	} elseif ($action == 'confirm_valid' && $confirm == 'yes' &&
		((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && !empty($user->rights->reception->creer))
		|| (!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && !empty($user->rights->reception->reception_advance->validate)))
	) {
		$object->fetch_thirdparty();

		$result = $object->valid($user);

		if ($result < 0) {
			$langs->load("errors");
			setEventMessages($langs->trans($object->error), null, 'errors');
		} else {
			// Define output language
			if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
				$outputlangs = $langs;
				$newlang = '';
				if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
					$newlang = GETPOST('lang_id', 'aZ09');
				}
				if ($conf->global->MAIN_MULTILANGS && empty($newlang)) {
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
	} elseif ($action == 'confirm_delete' && $confirm == 'yes' && $user->rights->reception->supprimer) {
		$result = $object->delete($user);
		if ($result > 0) {
			header("Location: ".DOL_URL_ROOT.'/reception/index.php');
			exit;
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
		}

		// TODO add alternative status
		/*} elseif ($action == 'reopen' && (! empty($user->rights->reception->creer) || ! empty($user->rights->reception->reception_advance->validate))) {
			$result = $object->setStatut(0);
			if ($result < 0) {
				setEventMessages($object->error, $object->errors, 'errors');
		}*/
	} elseif ($action == 'setdate_livraison' && $user->rights->reception->creer) {
		//print "x ".$_POST['liv_month'].", ".$_POST['liv_day'].", ".$_POST['liv_year'];
		$datedelivery = dol_mktime(GETPOST('liv_hour', 'int'), GETPOST('liv_min', 'int'), 0, GETPOST('liv_month', 'int'), GETPOST('liv_day', 'int'), GETPOST('liv_year', 'int'));

		$object->fetch($id);
		$result = $object->setDeliveryDate($user, $datedelivery);
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	} elseif ($action == 'settracking_number' || $action == 'settracking_url'
	|| $action == 'settrueWeight'
	|| $action == 'settrueWidth'
	|| $action == 'settrueHeight'
	|| $action == 'settrueDepth'
	|| $action == 'setshipping_method_id') {
		// Action update
		$error = 0;

		if ($action == 'settracking_number') {
			$object->tracking_number = trim(GETPOST('tracking_number', 'alpha'));
		}
		if ($action == 'settracking_url') {
			$object->tracking_url = trim(GETPOST('tracking_url', 'int'));
		}
		if ($action == 'settrueWeight') {
			$object->trueWeight = trim(GETPOST('trueWeight', 'int'));
			$object->weight_units = GETPOST('weight_units', 'int');
		}
		if ($action == 'settrueWidth') {
			$object->trueWidth = trim(GETPOST('trueWidth', 'int'));
		}
		if ($action == 'settrueHeight') {
						$object->trueHeight = trim(GETPOST('trueHeight', 'int'));
						$object->size_units = GETPOST('size_units', 'int');
		}
		if ($action == 'settrueDepth') {
			$object->trueDepth = trim(GETPOST('trueDepth', 'int'));
		}
		if ($action == 'setshipping_method_id') {
			$object->shipping_method_id = trim(GETPOST('shipping_method_id', 'int'));
		}

		if (!$error) {
			if ($object->update($user) >= 0) {
				header("Location: card.php?id=".$object->id);
				exit;
			}
			setEventMessages($object->error, $object->errors, 'errors');
		}

		$action = "";
	} elseif ($action == 'builddoc') {
		// Build document
		// En get ou en post
		// Save last template used to generate document
		if (GETPOST('model')) {
			$object->setDocModel($user, GETPOST('model', 'alpha'));
		}

		// Define output language
		$outputlangs = $langs;
		$newlang = '';
		if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
			$newlang = GETPOST('lang_id', 'aZ09');
		}
		if ($conf->global->MAIN_MULTILANGS && empty($newlang)) {
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
	} elseif ($action == 'remove_file') {
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
	} elseif ($action == 'classifybilled') {
		$result = $object->setBilled();
		if ($result >= 0) {
			header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
			exit();
		}
	} elseif ($action == 'classifyclosed') {
		$result = $object->setClosed();
		if ($result >= 0) {
			header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
			exit();
		}
	} elseif ($action == 'deleteline' && !empty($line_id)) {
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
	} elseif ($action == 'updateline' && $user->rights->reception->creer && GETPOST('save')) {
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
					$line->fk_entrepot = GETPOST($stockLocation, 'int');
					$line->qty = GETPOST($qty, 'int');
					$line->comment = GETPOST($comment, 'alpha');

					if (!empty($conf->productbatch->enabled)) {
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
				} else // Product no predefined
				{
					$qty = "qtyl".$line_id;
					$line->id = $line_id;
					$line->qty = GETPOST($qty, 'int');
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
			if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
				// Define output language
				$outputlangs = $langs;
				$newlang = '';
				if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
					$newlang = GETPOST('lang_id', 'aZ09');
				}
				if ($conf->global->MAIN_MULTILANGS && empty($newlang)) {
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
			header('Location: '.$_SERVER['PHP_SELF'].'?id='.$object->id); // Pour reaffichage de la fiche en cours d'edition
			exit();
		}
	} elseif ($action == 'updateline' && $user->rights->reception->creer && GETPOST('cancel', 'alpha') == $langs->trans("Cancel")) {
		header('Location: '.$_SERVER['PHP_SELF'].'?id='.$object->id); // Pour reaffichage de la fiche en cours d'edition
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
	$trackid = 'rec'.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';
}


/*
 * View
 */

llxHeader('', $langs->trans('Reception'), 'Reception');

$form = new Form($db);
$formfile = new FormFile($db);
$formproduct = new FormProduct($db);
if (!empty($conf->projet->enabled)) {
	$formproject = new FormProjets($db);
}

$product_static = new Product($db);
$reception_static = new Reception($db);
$warehousestatic = new Entrepot($db);

if ($action == 'create2') {
	print load_fiche_titre($langs->trans("CreateReception"), '', 'dollyrevert');

	print '<br>'.$langs->trans("ReceptionCreationIsDoneFromOrder");
	$action = ''; $id = ''; $ref = '';
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

			if (!empty($conf->stock->enabled)) {
				$entrepot = new Entrepot($db);
			}

			print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<input type="hidden" name="action" value="add">';
			print '<input type="hidden" name="origin" value="'.$origin.'">';
			print '<input type="hidden" name="origin_id" value="'.$objectsrc->id.'">';
			if (GETPOST('entrepot_id', 'int')) {
				print '<input type="hidden" name="entrepot_id" value="'.GETPOST('entrepot_id', 'int').'">';
			}

			print dol_get_fiche_head('');

			print '<table class="border centpercent">';

			// Ref
			print '<tr><td class="titlefieldcreate fieldrequired">';
			if ($origin == 'supplierorder' && ((!empty($conf->fournisseur->enabled) && empty($conf->global->MAIN_USE_NEW_SUPPLIERMOD)) || !empty($conf->supplier_order->enabled))) {
				print $langs->trans("RefOrder").'</td><td colspan="3"><a href="'.DOL_URL_ROOT.'/fourn/commande/card.php?id='.$objectsrc->id.'">'.img_object($langs->trans("ShowOrder"), 'order').' '.$objectsrc->ref;
			}
			if ($origin == 'propal' && !empty($conf->propal->enabled)) {
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
			if (!empty($conf->projet->enabled)) {
				$projectid = GETPOST('projectid', 'int') ?GETPOST('projectid', 'int') : 0;
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
				print $formproject->select_projects((empty($conf->global->PROJECT_CAN_ALWAYS_LINK_TO_ALL_SUPPLIERS) ? $soc->id : -1), $projectid, 'projectid', 0, 0, 1, 0, 1, 0, 0, '', 1, 0, 'maxwidth500');
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
			$doleditor = new DolEditor('note_public', $objectsrc->note_public, '', 60, 'dolibarr_notes', 'In', 0, false, empty($conf->global->FCKEDITOR_ENABLE_NOTE_PUBLIC) ? 0 : 1, ROWS_3, '90%');
			print $doleditor->Create(1);
			print "</td></tr>";

			// Note Private
			if ($objectsrc->note_private && !$user->socid) {
				print '<tr><td>'.$langs->trans("NotePrivate").'</td>';
				print '<td colspan="3">';
				$doleditor = new DolEditor('note_private', $objectsrc->note_private, '', 60, 'dolibarr_notes', 'In', 0, false, empty($conf->global->FCKEDITOR_ENABLE_NOTE_PRIVATE) ? 0 : 1, ROWS_3, '90%');
				print $doleditor->Create(1);
				print "</td></tr>";
			}

			// Weight
			print '<tr><td>';
			print $langs->trans("Weight");
			print '</td><td colspan="3"><input name="weight" size="4" value="'.GETPOST('weight', 'int').'"> ';
			$text = $formproduct->selectMeasuringUnits("weight_units", "weight", GETPOST('weight_units', 'int'), 0, 2);
			$htmltext = $langs->trans("KeepEmptyForAutoCalculation");
			print $form->textwithpicto($text, $htmltext);
			print '</td></tr>';
			// Dim
			print '<tr><td>';
			print $langs->trans("Width").' x '.$langs->trans("Height").' x '.$langs->trans("Depth");
			print ' </td><td colspan="3"><input name="sizeW" size="4" value="'.GETPOST('sizeW', 'int').'">';
			print ' x <input name="sizeH" size="4" value="'.GETPOST('sizeH', 'int').'">';
			print ' x <input name="sizeS" size="4" value="'.GETPOST('sizeS', 'int').'">';
			print ' ';
			$text = $formproduct->selectMeasuringUnits("size_units", "size", GETPOST('size_units', 'int'), 0, 2);
			$htmltext = $langs->trans("KeepEmptyForAutoCalculation");
			print $form->textwithpicto($text, $htmltext);
			print '</td></tr>';

			// Delivery method
			print "<tr><td>".$langs->trans("ReceptionMethod")."</td>";
			print '<td colspan="3">';
			$recept->fetch_delivery_methods();
			print $form->selectarray("shipping_method_id", $recept->meths, GETPOST('shipping_method_id', 'int'), 1, 0, 0, "", 1);
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
			$parameters = array('objectsrc' => $objectsrc, 'colspan' => ' colspan="3"', 'cols' => '3', 'socid'=>$socid);
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
			if (!empty($conf->incoterm->enabled)) {
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

			// Reception lines
			$numAsked = 0;

			/**
			 * @var array $suffix2numAsked map HTTP query parameter suffixes (like '1_0') to line indices so that
			 *                             extrafields from HTTP query can be assigned to the correct dispatch line
			*/
			$suffix2numAsked = array();
			$dispatchLines = array();

			foreach ($_POST as $key => $value) {
				// If create form is coming from the button "Create Reception" of previous page

				// without batch module enabled
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
					$dispatchLines[$numAsked] = array('prod' => GETPOST($prod, 'int'), 'qty' => price2num(GETPOST($qty), 'MS'), 'ent' => GETPOST($ent, 'int'), 'pu' => price2num(GETPOST($pu), 'MU'), 'comment' => GETPOST('comment'), 'fk_commandefourndet' => GETPOST($fk_commandefourndet, 'int'));
				}

				// with batch module enabled
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
					$dDLUO = dol_mktime(12, 0, 0, $_POST['dluo_'.$paramSuffix.'month'], $_POST['dluo_'.$paramSuffix.'day'], $_POST['dluo_'.$paramSuffix.'year']);
					$dDLC = dol_mktime(12, 0, 0, $_POST['dlc_'.$paramSuffix.'month'], $_POST['dlc_'.$paramSuffix.'day'], $_POST['dlc_'.$paramSuffix.'year']);
					$fk_commandefourndet = 'fk_commandefourndet_'.$paramSuffix;
					$dispatchLines[$numAsked] = array('prod' => GETPOST($prod, 'int'), 'qty' => price2num(GETPOST($qty), 'MS'), 'ent' =>GETPOST($ent, 'int'), 'pu' => price2num(GETPOST($pu), 'MU'), 'comment' =>GETPOST('comment'), 'fk_commandefourndet' => GETPOST($fk_commandefourndet, 'int'), 'DLC'=> $dDLC, 'DLUO'=> $dDLUO, 'lot'=> GETPOST($lot, 'alpha'));
				}

				// If create form is coming from same page, it means that post was sent but an error occured
				if (preg_match('/^productid([0-9]+)$/i', $key, $reg)) {
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
					$dDLUO = dol_mktime(12, 0, 0, GETPOST('dluo'.$paramSuffix.'month', 'int'), GETPOST('dluo'.$paramSuffix.'day', 'int'), GETPOST('dluo'.$paramSuffix.'year', 'int'));
					$dDLC = dol_mktime(12, 0, 0, GETPOST('dlc'.$paramSuffix.'month', 'int'), GETPOST('dlc'.$paramSuffix.'day', 'int'), GETPOST('dlc'.$paramSuffix.'year', 'int'));
					$fk_commandefourndet = 'fk_commandefournisseurdet'.$paramSuffix;
					$dispatchLines[$numAsked] = array('prod' => GETPOST($prod, 'int'), 'qty' => price2num(GETPOST($qty), 'MS'), 'ent' =>GETPOST($ent, 'int'), 'pu' => price2num(GETPOST($pu), 'MU'), 'comment' =>GETPOST($comment), 'fk_commandefourndet' => GETPOST($fk_commandefourndet, 'int'), 'DLC'=> $dDLC, 'DLUO'=> $dDLUO, 'lot'=> GETPOST($lot, 'alpha'));
				}
			}

			// If extrafield values are passed in the HTTP query, assign them to the correct dispatch line
			// Note that if an extrafield with the same name exists in the origin supplier order line, the value
			// from the HTTP query will be ignored
			foreach ($suffix2numAsked as $suffix => $n) {
				$dispatchLines[$n]['array_options'] = $extrafields->getOptionalsFromPost('commande_fournisseur_dispatch', '_' . $suffix, '');
			}

			print '<script type="text/javascript" language="javascript">
            jQuery(document).ready(function() {
	            jQuery("#autofill").click(function() {';
			$i = 1;
			while ($i <= $numAsked) {
				print 'jQuery("#qtyl'.$i.'").val(jQuery("#qtyasked'.$i.'").val() - jQuery("#qtydelivered'.$i.'").val());'."\n";
				$i++;
			}
			print '});
	            jQuery("#autoreset").click(function() {';
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
				if (empty($conf->productbatch->enabled)) {
					print ' <br>(<a href="#" id="autofill">'.$langs->trans("Fill").'</a>';
					print ' / <a href="#" id="autoreset">'.$langs->trans("Reset").'</a>)';
				}
				print '</td>';
				if (!empty($conf->stock->enabled)) {
					print '<td class="left">'.$langs->trans("Warehouse").' ('.$langs->trans("Stock").')</td>';
				}
				if (!empty($conf->productbatch->enabled)) {
					print '<td class="left">'.$langs->trans("batch_number").'</td>';
					if (empty($conf->global->PRODUCT_DISABLE_SELLBY)) {
						print '<td class="left">'.$langs->trans("SellByDate").'</td>';
					}
					if (empty($conf->global->PRODUCT_DISABLE_EATBY)) {
						print '<td class="left">'.$langs->trans("EatByDate").'</td>';
					}
				}
				print "</tr>\n";
			}

			// $objectsrc->lines contains the line of the purchase order
			// $dispatchLines is list of lines with dispatching detail (with product, qty and warehouse). One purchase order line may have n of this dispatch lines.

			$arrayofpurchaselinealreadyoutput= array();

			// $_POST contains fk_commandefourndet_X_Y    where Y is num of product line and X is number of splitted line
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

					print '<td>';
					print '<a name="'.$line->id.'"></a>'; // ancre pour retourner sur la ligne
					if (! array_key_exists($line->id, $arrayofpurchaselinealreadyoutput)) {	// Add test to avoid to show qty twice
						print '<input type="hidden" name="productid'.$indiceAsked.'" value="'.$line->fk_product.'">';

						// Show product and description
						$product_static = $product;

						$text = $product_static->getNomUrl(1);
						$text .= ' - '.(!empty($line->label) ? $line->label : $line->product_label);
						$description = ($conf->global->PRODUIT_DESC_IN_FORM ? '' : dol_htmlentitiesbr($line->desc));
						print $form->textwithtooltip($text, $description, 3, '', '', $i);

						// Show range
						print_date_range($db->jdate($line->date_start), $db->jdate($line->date_end));

						// Add description in form
						if (!empty($conf->global->PRODUIT_DESC_IN_FORM)) {
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


				if ($line->product_type == 1 && empty($conf->global->STOCK_SUPPORTS_SERVICES)) {
					$quantityToBeDelivered = 0;
				} else {
					$quantityToBeDelivered = $dispatchLines[$indiceAsked]['qty'];
				}
				$warehouse_id = $dispatchLines[$indiceAsked]['ent'];


				$warehouseObject = null;
				if (!empty($conf->stock->enabled)) {     // If warehouse was already selected or if product is not a predefined, we go into this part with no multiwarehouse selection
					print '<!-- Case warehouse already known or product not a predefined product -->';

					$stock = + $product->stock_warehouse[$dispatchLines[$indiceAsked]['ent']]->real; // Convert to number
					$deliverableQty = $dispatchLines[$indiceAsked]['qty'];

					// Quantity to send
					print '<td class="center">';
					if ($line->product_type == Product::TYPE_PRODUCT || !empty($conf->global->STOCK_SUPPORTS_SERVICES)) {
						if (GETPOST('qtyl'.$indiceAsked, 'int')) {
							$defaultqty = GETPOST('qtyl'.$indiceAsked, 'int');
						}
						print '<input name="idl'.$indiceAsked.'" type="hidden" value="'.$line->id.'">';
						print '<input name="qtyl'.$indiceAsked.'" id="qtyl'.$indiceAsked.'" type="text" size="4" value="'.$deliverableQty.'">';
					} else {
						print $langs->trans("NA");
					}
					print '</td>';

					// Stock
					if (!empty($conf->stock->enabled)) {
						print '<td class="left">';
						if ($line->product_type == Product::TYPE_PRODUCT || !empty($conf->global->STOCK_SUPPORTS_SERVICES)) {   // Type of product need stock change ?
							// Show warehouse combo list
							$ent = "entl".$indiceAsked;
							$idl = "idl".$indiceAsked;
							$tmpentrepot_id = is_numeric(GETPOST($ent, 'int')) ?GETPOST($ent, 'int') : $warehouse_id;
							if ($line->fk_product > 0) {
								print '<!-- Show warehouse selection -->';
								print $formproduct->selectWarehouses($tmpentrepot_id, 'entl'.$indiceAsked, '', 0, 0, $line->fk_product, '', 1);
							}
						} else {
							print $langs->trans("Service");
						}
						print '</td>';
					}

					if (!empty($conf->productbatch->enabled)) {
						if (!empty($product->status_batch)) {
							print '<td><input name="batch'.$indiceAsked.'" value="'.$dispatchLines[$indiceAsked]['lot'].'"></td>';
							if (empty($conf->global->PRODUCT_DISABLE_SELLBY)) {
								print '<td class="nowraponall">';
								print $form->selectDate($dispatchLines[$indiceAsked]['DLC'], 'dlc'.$indiceAsked, '', '', 1, "");
								print '</td>';
							}
							if (empty($conf->global->PRODUCT_DISABLE_EATBY)) {
								print '<td class="nowraponall">';
								print $form->selectDate($dispatchLines[$indiceAsked]['DLUO'], 'dluo'.$indiceAsked, '', '', 1, "");
								print '</td>';
							}
						} else {
							print '<td colspan="3"></td>';
						}
					}
				}

				$arrayofpurchaselinealreadyoutput[$line->id] = $line->id;

				print "</tr>\n";

				$extralabelslines = $extrafields->attributes[$line->table_element];
				//Display lines extrafields
				if (is_array($extralabelslines) && count($extralabelslines) > 0) {
					$colspan = 5;
					if ($conf->productbatch->enabled) {
						$colspan += 3;
					}

					$srcLine = new CommandeFournisseurLigne($db);
					$line = new CommandeFournisseurDispatch($db);

					$extrafields->fetch_name_optionals_label($srcLine->table_element);
					$extrafields->fetch_name_optionals_label($line->table_element);

					$srcLine->id = $line->id;
					$srcLine->fetch_optionals(); // fetch extrafields also available in orderline
					$line->fetch_optionals();

					if (empty($line->array_options) && !empty($dispatchLines[$indiceAsked]['array_options'])) {
						$line->array_options = $dispatchLines[$indiceAsked]['array_options'];
					}
					$line->array_options = array_merge($line->array_options, $srcLine->array_options);

					print $line->showOptionals($extrafields, 'edit', array('style'=>'class="oddeven"', 'colspan'=>$colspan), $indiceAsked);
				}

				$indiceAsked++;
			}

			print "</table>";

			print '<br>';

			print '<div class="center">';
			print '<input type="submit" class="button" name="add" value="'.dol_escape_htmltag($langs->trans("Create")).'">';
			print '&nbsp; ';
			print '<input type="'.($backtopage ? "submit" : "button").'" class="button button-cancel" name="cancel" value="'.dol_escape_htmltag($langs->trans("Cancel")).'"'.($backtopage ? '' : ' onclick="javascript:history.go(-1)"').'>'; // Cancel for create does not post form if we don't know the backtopage
			print '</div>';

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

	if ($object->id > 0) {
		if (!empty($object->origin) && $object->origin_id > 0) {
			$object->origin = 'CommandeFournisseur';
			$typeobject = $object->origin;
			$origin = $object->origin;
			$origin_id = $object->origin_id;
			$object->fetch_origin(); // Load property $object->commande, $object->propal, ...
		}

		$soc = new Societe($db);
		$soc->fetch($object->socid);

		$res = $object->fetch_optionals();

		$head = reception_prepare_head($object);
		print dol_get_fiche_head($head, 'reception', $langs->trans("Reception"), -1, 'dollyrevert');

		$formconfirm = '';

		// Confirm deleteion
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

			if (!empty($conf->notification->enabled)) {
				require_once DOL_DOCUMENT_ROOT.'/core/class/notify.class.php';
				$notify = new Notify($db);
				$text .= '<br>';
				$text .= $notify->confirmMessage('RECEPTION_VALIDATE', $object->socid, $object);
			}

			$formconfirm = $form->formconfirm($_SERVER['PHP_SELF'].'?id='.$object->id, $langs->trans('ValidateReception'), $text, 'confirm_valid', '', 0, 1);
		}

		// Confirm cancelation
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


		if ($typeobject == 'commande' && $object->$typeobject->id && !empty($conf->commande->enabled)) {
			$objectsrc = new Commande($db);
			$objectsrc->fetch($object->$typeobject->id);
		}
		if ($typeobject == 'propal' && $object->$typeobject->id && !empty($conf->propal->enabled)) {
			$objectsrc = new Propal($db);
			$objectsrc->fetch($object->$typeobject->id);
		}
		if ($typeobject == 'CommandeFournisseur' && $object->$typeobject->id && ((!empty($conf->fournisseur->enabled) && empty($conf->global->MAIN_USE_NEW_SUPPLIERMOD)) || !empty($conf->supplier_order->enabled))) {
			$objectsrc = new CommandeFournisseur($db);
			$objectsrc->fetch($object->$typeobject->id);
		}
		// Reception card
		$linkback = '<a href="'.DOL_URL_ROOT.'/reception/list.php?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';
		$morehtmlref = '<div class="refidno">';
		// Ref customer reception

		$morehtmlref .= $form->editfieldkey("RefSupplier", 'ref_supplier', $object->ref_supplier, $object, $user->rights->reception->creer, 'string', '', 0, 1);
		$morehtmlref .= $form->editfieldval("RefSupplier", 'ref_supplier', $object->ref_supplier, $object, $user->rights->reception->creer, 'string', '', null, null, '', 1);

		// Thirdparty
		$morehtmlref .= '<br>'.$langs->trans('ThirdParty').' : '.$object->thirdparty->getNomUrl(1);
		// Project
		if (!empty($conf->projet->enabled)) {
			$langs->load("projects");
			$morehtmlref .= '<br>'.$langs->trans('Project').' ';
			if (0) {    // Do not change on reception
				if ($action != 'classify') {
					$morehtmlref .= '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?action=classify&amp;id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetProject')).'</a> : ';
				}
				if ($action == 'classify') {
					// $morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'projectid', 0, 0, 1, 1);
					$morehtmlref .= '<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
					$morehtmlref .= '<input type="hidden" name="action" value="classin">';
					$morehtmlref .= '<input type="hidden" name="token" value="'.newToken().'">';
					$morehtmlref .= $formproject->select_projects($object->socid, $object->fk_project, 'projectid', $maxlength, 0, 1, 0, 1, 0, 0, '', 1);
					$morehtmlref .= '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
					$morehtmlref .= '</form>';
				} else {
					$morehtmlref .= $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, $object->socid, $object->fk_project, 'none', 0, 0, 0, 1);
				}
			} else {
				// We don't have project on reception, so we will use the project or source object instead
				// TODO Add project on reception
				$morehtmlref .= ' : ';
				if (!empty($objectsrc->fk_project)) {
					$proj = new Project($db);
					$proj->fetch($objectsrc->fk_project);
					$morehtmlref .= '<a href="'.DOL_URL_ROOT.'/projet/card.php?id='.$objectsrc->fk_project.'" title="'.$langs->trans('ShowProject').'">';
					$morehtmlref .= $proj->ref;
					$morehtmlref .= '</a>';
				} else {
					$morehtmlref .= '';
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
		if ($typeobject == 'commande' && $object->$typeobject->id && !empty($conf->commande->enabled)) {
			print '<tr><td>';
			print $langs->trans("RefOrder").'</td>';
			print '<td colspan="3">';
			print $objectsrc->getNomUrl(1, 'commande');
			print "</td>\n";
			print '</tr>';
		}
		if ($typeobject == 'propal' && $object->$typeobject->id && !empty($conf->propal->enabled)) {
			print '<tr><td>';
			print $langs->trans("RefProposal").'</td>';
			print '<td colspan="3">';
			print $objectsrc->getNomUrl(1, 'reception');
			print "</td>\n";
			print '</tr>';
		}
		if ($typeobject == 'CommandeFournisseur' && $object->$typeobject->id && !empty($conf->propal->enabled)) {
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

		if ($action != 'editdate_livraison') {
			print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editdate_livraison&amp;id='.$object->id.'">'.img_edit($langs->trans('SetDeliveryDate'), 1).'</a></td>';
		}
		print '</tr></table>';
		print '</td><td colspan="2">';
		if ($action == 'editdate_livraison') {
			print '<form name="setdate_livraison" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="post">';
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<input type="hidden" name="action" value="setdate_livraison">';
			print $form->selectDate($object->date_delivery ? $object->date_delivery : -1, 'liv_', 1, 1, '', "setdate_livraison", 1, 0);
			print '<input type="submit" class="button" value="'.$langs->trans('Modify').'">';
			print '</form>';
		} else {
			print $object->date_delivery ? dol_print_date($object->date_delivery, 'dayhour') : '&nbsp;';
		}
		print '</td>';
		print '</tr>';

		// Weight
		print '<tr><td>';
		print $form->editfieldkey("Weight", 'trueWeight', $object->trueWeight, $object, $user->rights->reception->creer);
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
		print '<tr><td>'.$form->editfieldkey("Width", 'trueWidth', $object->trueWidth, $object, $user->rights->reception->creer).'</td><td colspan="3">';
		print $form->editfieldval("Width", 'trueWidth', $object->trueWidth, $object, $user->rights->reception->creer);
		print ($object->trueWidth && $object->width_units != '') ? ' '.measuringUnitString(0, "size", $object->width_units) : '';
		print '</td></tr>';

		// Height
		print '<tr><td>'.$form->editfieldkey("Height", 'trueHeight', $object->trueHeight, $object, $user->rights->reception->creer).'</td><td colspan="3">';
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
		print '<tr><td>'.$form->editfieldkey("Depth", 'trueDepth', $object->trueDepth, $object, $user->rights->reception->creer).'</td><td colspan="3">';
		print $form->editfieldval("Depth", 'trueDepth', $object->trueDepth, $object, $user->rights->reception->creer);
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
				print $calculatedVolume.' '.measuringUnitString(0, "volume", $volumeUnit);
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
		print '<div class="ficheaddleft">';
		print '<div class="underbanner clearboth"></div>';

		print '<table class="border centpercent tableforfield">';

		// Reception method
		print '<tr><td height="10">';
		print '<table class="nobordernopadding centpercent"><tr><td>';
		print $langs->trans('ReceptionMethod');
		print '</td>';

		if ($action != 'editshipping_method_id') {
			print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editshipping_method_id&amp;id='.$object->id.'">'.img_edit($langs->trans('SetReceptionMethod'), 1).'</a></td>';
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
			print '<input type="submit" class="button" value="'.$langs->trans('Modify').'">';
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
		print '<tr><td class="titlefield">'.$form->editfieldkey("TrackingNumber", 'tracking_number', $object->tracking_number, $object, $user->rights->reception->creer).'</td><td colspan="3">';
		print $form->editfieldval("TrackingNumber", 'tracking_number', $object->tracking_url, $object, $user->rights->reception->creer, 'safehtmlstring', $object->tracking_number);
		print '</td></tr>';

		// Incoterms
		if (!empty($conf->incoterm->enabled)) {
			print '<tr><td>';
			print '<table width="100%" class="nobordernopadding"><tr><td>';
			print $langs->trans('IncotermLabel');
			print '<td><td class="right">';
			if ($user->rights->reception->creer) {
				print '<a class="editfielda" href="'.DOL_URL_ROOT.'/reception/card.php?id='.$object->id.'&action=editincoterm">'.img_edit().'</a>';
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
		print '<br>';

		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		// #
		if (!empty($conf->global->MAIN_VIEW_LINE_NUMBER)) {
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
			if (empty($conf->stock->enabled)) {
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
			if (!empty($conf->stock->enabled)) {
				print $langs->trans("WarehouseSource").' - ';
			}
			if (!empty($conf->productbatch->enabled)) {
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
			if (!empty($conf->stock->enabled)) {
				print '<td class="left">'.$langs->trans("WarehouseSource").'</td>';
			}

			if (!empty($conf->productbatch->enabled)) {
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

		$var = false;

		if (!empty($conf->global->MAIN_MULTILANGS) && !empty($conf->global->PRODUIT_TEXTS_IN_THIRDPARTY_LANGUAGE)) {
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
			//if ($conf->delivery_note->enabled) $sql .= ", l.rowid as livraison_id, l.ref as livraison_ref, l.date_delivery, ld.qty as qty_received";
			$sql .= ', p.label as product_label, p.ref, p.fk_product_type, p.rowid as prodid, p.tobatch as product_tobatch';
			$sql .= ', p.description as product_desc';
			$sql .= " FROM ".MAIN_DB_PREFIX."commande_fournisseur_dispatch as ed";
			$sql .= ", ".MAIN_DB_PREFIX."reception as e";
			$sql .= ", ".MAIN_DB_PREFIX.$origin."det as obj";
			//if ($conf->delivery_note->enabled) $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."delivery as l ON l.fk_reception = e.rowid LEFT JOIN ".MAIN_DB_PREFIX."deliverydet as ld ON ld.fk_delivery = l.rowid  AND obj.rowid = ld.fk_origin_line";
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product as p ON obj.fk_product = p.rowid";
			$sql .= " WHERE e.entity IN (".getEntity('reception').")";
			$sql .= " AND obj.fk_commande = ".((int) $origin_id);
			$sql .= " AND obj.rowid = ed.fk_commandefourndet";
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
						$alreadysent[$obj->rowid][$obj->receptionline_id] = array('reception_ref'=>$obj->reception_ref, 'reception_id'=>$obj->reception_id, 'warehouse'=>$obj->fk_entrepot, 'qty'=>$obj->qty, 'date_valid'=>$obj->date_valid, 'date_delivery'=>$obj->date_delivery);
					}
					$i++;
				}
			}
			//var_dump($alreadysent);
		}

		$arrayofpurchaselinealreadyoutput = array();

		// Loop on each product to send/sent. Warning: $lines must be sorted by ->fk_commandefourndet (it is a regroupment key on output)
		for ($i = 0; $i < $num_prod; $i++) {
			print '<!-- origin line id = '.$lines[$i]->origin_line_id.' -->'; // id of order line
			print '<tr class="oddeven">';

			// #
			if (!empty($conf->global->MAIN_VIEW_LINE_NUMBER)) {
				print '<td class="center">'.($i + 1).'</td>';
			}

			// Predefined product or service
			if ($lines[$i]->fk_product > 0) {
				// Define output language
				if (!empty($conf->global->MAIN_MULTILANGS) && !empty($conf->global->PRODUIT_TEXTS_IN_THIRDPARTY_LANGUAGE)) {
					$prod = new Product($db);
					$prod->fetch($lines[$i]->fk_product);
					$label = (!empty($prod->multilangs[$outputlangs->defaultlang]["label"])) ? $prod->multilangs[$outputlangs->defaultlang]["label"] : $lines[$i]->product->label;
				} else {
					$label = (!empty($lines[$i]->product->label) ? $lines[$i]->product->label : $lines[$i]->product->product_label);
				}

				print '<td>';
				if (!array_key_exists($lines[$i]->fk_commandefourndet, $arrayofpurchaselinealreadyoutput)) {
					$text = $lines[$i]->product->getNomUrl(1);
					$text .= ' - '.$label;
					$description = (!empty($conf->global->PRODUIT_DESC_IN_FORM) ? '' : dol_htmlentitiesbr($lines[$i]->product->description));
					print $form->textwithtooltip($text, $description, 3, '', '', $i);
					print_date_range($lines[$i]->date_start, $lines[$i]->date_end);
					if (!empty($conf->global->PRODUIT_DESC_IN_FORM)) {
						print (!empty($lines[$i]->product->description) && $lines[$i]->description != $lines[$i]->product->description) ? '<br>'.dol_htmlentitiesbr($lines[$i]->description) : '';
					}
				}
				print "</td>\n";
			} else {
				print "<td>";
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
			print '<td class="center">';
			if (!array_key_exists($lines[$i]->fk_commandefourndet, $arrayofpurchaselinealreadyoutput)) {
				print $lines[$i]->qty_asked;
			}
			print '</td>';

			// Qty in other receptions (with reception and warehouse used)
			if ($origin && $origin_id > 0) {
				print '<td class="center nowrap">';
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
									print '<br>';
								}
								$reception_static->fetch($receptionline_var['reception_id']);
								print $reception_static->getNomUrl(1);
								print ' - '.$receptionline_var['qty'];

								$htmltext = $langs->trans("DateValidation").' : '.(empty($receptionline_var['date_valid']) ? $langs->trans("Draft") : dol_print_date($receptionline_var['date_valid'], 'dayhour'));
								if (!empty($conf->stock->enabled) && $receptionline_var['warehouse'] > 0) {
									$warehousestatic->fetch($receptionline_var['warehouse']);
									$htmltext .= '<br>'.$langs->trans("From").' : '.$warehousestatic->getNomUrl(1, '', 0, 1);
								}
								print ' '.$form->textwithpicto('', $htmltext, 1);
							}
						}
					}
				}
			}
			print '</td>';

			if ($action == 'editline' && $lines[$i]->id == $line_id) {
				// edit mode
				print '<td colspan="'.$editColspan.'" class="center"><table class="nobordernopadding">';
				if (!empty($conf->stock->enabled)) {
					if ($lines[$i]->fk_product > 0) {
						print '<!-- case edit 1 -->';
						print '<tr>';
						// Qty to receive or received
						print '<td><input name="qtyl'.$line_id.'" id="qtyl'.$line_id.'" type="text" size="4" value="'.$lines[$i]->qty.'"></td>';
						// Warehouse source
						print '<td>'.$formproduct->selectWarehouses($lines[$i]->fk_entrepot, 'entl'.$line_id, '', 1, 0, $lines[$i]->fk_product, '', 1).'</td>';
						// Batch number managment
						if ($conf->productbatch->enabled && !empty($lines[$i]->product->status_batch)) {
							print '<td class="nowraponall"><input name="batch'.$line_id.'" id="batch'.$line_id.'" type="text" value="'.$lines[$i]->batch.'"><br>';
							if (empty($conf->global->PRODUCT_DISABLE_SELLBY)) {
								print $langs->trans('SellByDate').' : ';
								print $form->selectDate($lines[$i]->sellby, 'dlc'.$line_id, '', '', 1, "").'</br>';
							}
							if (empty($conf->global->PRODUCT_DISABLE_EATBY)) {
								print $langs->trans('EatByDate').' : ';
								print $form->selectDate($lines[$i]->eatby, 'dluo'.$line_id, '', '', 1, "");
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
						// Batch number managment
						print '<td></td>';
						print '</tr>';
					}
				}
				print '</table></td>';
			} else {
				// Qty to receive or received
				print '<td class="center">'.$lines[$i]->qty.'</td>';

				// Warehouse source
				if (!empty($conf->stock->enabled)) {
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

				// Batch number managment
				if (!empty($conf->productbatch->enabled)) {
					if (isset($lines[$i]->batch)) {
						print '<!-- Detail of lot -->';
						print '<td>';
						$detail = '';
						if ($lines[$i]->product->status_batch) {
							$detail .= $langs->trans("Batch").': '.$lines[$i]->batch;
							if (empty($conf->global->PRODUCT_DISABLE_SELLBY)) {
								$detail .= ' - '.$langs->trans("SellByDate").': '.dol_print_date($lines[$i]->sellby, "day");
							}
							if (empty($conf->global->PRODUCT_DISABLE_EATBY)) {
								$detail .= ' - '.$langs->trans("EatByDate").': '.dol_print_date($lines[$i]->eatby, "day");
							}
							$detail .= '<br>';

							print $form->textwithtooltip(img_picto('', 'object_barcode').' '.$langs->trans("DetailBatchNumber"), $detail);
						} else {
							print $langs->trans("NA");
						}
						print '</td>';
					} else {
						print '<td></td>';
					}
				}
			}

			// Weight
			print '<td class="center">';
			if ($lines[$i]->fk_product_type == Product::TYPE_PRODUCT) {
				print $lines[$i]->product->weight * $lines[$i]->qty.' '.measuringUnitString(0, "weight", $lines[$i]->product->weight_units);
			} else {
				print '&nbsp;';
			}
			print '</td>';

			// Volume
			print '<td class="center">';
			if ($lines[$i]->fk_product_type == Product::TYPE_PRODUCT) {
				print $lines[$i]->product->volume * $lines[$i]->qty.' '.measuringUnitString(0, "volume", $lines[$i]->product->volume_units);
			} else {
				print '&nbsp;';
			}
			print '</td>';


			if ($action == 'editline' && $lines[$i]->id == $line_id) {
				print '<td class="center" colspan="2" valign="middle">';
				print '<input type="submit" class="button button-save" id="savelinebutton marginbottomonly" name="save" value="'.$langs->trans("Save").'"><br>';
				print '<input type="submit" class="button button-cancel" id="cancellinebutton" name="cancel" value="'.$langs->trans("Cancel").'"><br>';
			} elseif ($object->statut == Reception::STATUS_DRAFT) {
				// edit-delete buttons
				print '<td class="linecoledit center">';
				print '<a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=editline&amp;token='.newToken().'&amp;lineid='.$lines[$i]->id.'">'.img_edit().'</a>';
				print '</td>';
				print '<td class="linecoldelete" width="10">';
				print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=deleteline&amp;token='.newToken().'&amp;lineid='.$lines[$i]->id.'">'.img_delete().'</a>';
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
			if (is_array($extralabelslines) && count($extralabelslines) > 0) {
				$colspan = empty($conf->productbatch->enabled) ? 8 : 9;
				$line = new CommandeFournisseurDispatch($db);
				$line->id = $lines[$i]->id;
				$line->fetch_optionals();

				if ($action == 'editline' && $lines[$i]->id == $line_id) {
					print $line->showOptionals($extrafields, 'edit', array('colspan'=>$colspan), $indiceAsked);
				} else {
					print $line->showOptionals($extrafields, 'view', array('colspan'=>$colspan), $indiceAsked);
				}
			}
		}

		// TODO Show also lines ordered but not delivered

		print "</table>\n";
		print '</div>';
	}


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
				if ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && !empty($user->rights->reception->creer))
				 || (!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && !empty($user->rights->reception->reception_advance->validate))) {
					print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=valid&token='.newToken().'">'.$langs->trans("Validate").'</a>';
				} else {
					print '<a class="butActionRefused" href="#" title="'.$langs->trans("NotAllowed").'">'.$langs->trans("Validate").'</a>';
				}
			}
			// Back to draft
			if ($object->statut == Reception::STATUS_VALIDATED && $user->rights->reception->creer) {
				print '<div class="inline-block divButAction"><a class="butAction" href="card.php?id='.$object->id.'&action=modif&token='.newToken().'">'.$langs->trans('SetToDraft').'</a></div>';
			}

			// TODO add alternative status
			// 0=draft, 1=validated, 2=billed, we miss a status "delivered" (only available on order)
			if ($object->statut == Reception::STATUS_CLOSED && $user->rights->reception->creer) {
				if (!empty($conf->facture->enabled) && !empty($conf->global->WORKFLOW_BILL_ON_RECEPTION)) {  // Quand l'option est on, il faut avoir le bouton en plus et non en remplacement du Close ?
					print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=reopen">'.$langs->trans("ClassifyUnbilled").'</a>';
				} else {
					print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=reopen">'.$langs->trans("ReOpen").'</a>';
				}
			}

			// Send
			if (empty($user->socid)) {
				if ($object->statut > 0) {
					if (empty($conf->global->MAIN_USE_ADVANCED_PERMS) || $user->rights->reception->reception_advance->send) {
						print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=presend&mode=init#formmailbeforetitle">'.$langs->trans('SendByMail').'</a>';
					} else {
						print '<a class="butActionRefused" href="#">'.$langs->trans('SendByMail').'</a>';
					}
				}
			}

			// Create bill
			if (((!empty($conf->fournisseur->enabled) && empty($conf->global->MAIN_USE_NEW_SUPPLIERMOD)) || !empty($conf->supplier_invoice->enabled)) && ($object->statut == Reception::STATUS_VALIDATED || $object->statut == Reception::STATUS_CLOSED)) {
				if ($user->rights->fournisseur->facture->creer || $user->rights->supplier_invoice->creer) {
					// TODO show button only   if (! empty($conf->global->WORKFLOW_BILL_ON_RECEPTION))
					// If we do that, we must also make this option official.
					print '<a class="butAction" href="'.DOL_URL_ROOT.'/fourn/facture/card.php?action=create&amp;origin='.$object->element.'&amp;originid='.$object->id.'&amp;socid='.$object->socid.'">'.$langs->trans("CreateBill").'</a>';
				}
			}


			// Close
			if ($object->statut == Reception::STATUS_VALIDATED) {
				if ($user->rights->reception->creer && $object->statut > 0 && !$object->billed) {
					$label = "Close"; $paramaction = 'classifyclosed'; // = Transferred/Received
					// Label here should be "Close" or "ClassifyBilled" if we decided to make bill on receptions instead of orders
					if (((!empty($conf->fournisseur->enabled) && empty($conf->global->MAIN_USE_NEW_SUPPLIERMOD)) || !empty($conf->supplier_order->enabled)) && !empty($conf->global->WORKFLOW_BILL_ON_RECEPTION)) {  // Quand l'option est on, il faut avoir le bouton en plus et non en remplacement du Close ?
						$label = "ClassifyBilled";
						$paramaction = 'classifybilled';
					}
					print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action='.$paramaction.'">'.$langs->trans($label).'</a>';
				}
			}

			if ($user->rights->reception->supprimer) {
				print '<a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=delete&amp;token='.newToken().'">'.$langs->trans("Delete").'</a>';
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

		$genallowed = $user->rights->reception->lire;
		$delallowed = $user->rights->reception->creer;

		print $formfile->showdocuments('reception', $objectref, $filedir, $urlsource, $genallowed, $delallowed, $object->model_pdf, 1, 0, 0, 28, 0, '', '', '', $soc->default_lang);

		// Show links to link elements
		//$linktoelem = $form->showLinkToObjectBlock($object, null, array('order'));
		$somethingshown = $form->showLinkedObjectBlock($object, '');

		print '</div><div class="fichehalfright"><div class="ficheaddleft">';
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
