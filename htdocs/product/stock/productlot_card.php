<?php
/* Copyright (C) 2007-2018 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2018      All-3kcis       		 <contact@all-3kcis.fr>
 * Copyright (C) 2021      Noé Cendrier         <noe.cendrier@altairis.fr>
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
 *   	\file       product/stock/productlot_card.php
 *		\ingroup    stock
 *		\brief      This file is an example of a php page
 *					Initially built by build_class_from_table on 2016-05-17 12:22
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/productlot.class.php';

global $conf, $db, $langs, $user;

// Load translation files required by the page
$langs->loadLangs(array('stocks', 'other', 'productbatch'));

// Get parameters
$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$cancel = GETPOST('cancel', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'myobjectcard'; // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');

$id = GETPOSTINT('id');
$lineid = GETPOSTINT('lineid');
$batch = GETPOST('batch', 'alpha');
$productid = GETPOSTINT('productid');
$ref = GETPOST('ref', 'alpha'); // ref is productid_batch

// Initialize a technical objects
$object = new Productlot($db);
$extrafields = new ExtraFields($db);
$hookmanager->initHooks(array('productlotcard', 'globalcard')); // Note that conf->hooks_modules contains array

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// Initialize array of search criteria
$search_all = GETPOST("search_all", 'alpha');
$search = array();
foreach ($object->fields as $key => $val) {
	if (GETPOST('search_'.$key, 'alpha')) {
		$search[$key] = GETPOST('search_'.$key, 'alpha');
	}
}

if (empty($action) && empty($id) && empty($ref)) {
	$action = 'view';
}

$search_entity = GETPOSTINT('search_entity');
$search_fk_product = GETPOSTINT('search_fk_product');
$search_batch = GETPOST('search_batch', 'alpha');
$search_fk_user_creat = GETPOSTINT('search_fk_user_creat');
$search_fk_user_modif = GETPOSTINT('search_fk_user_modif');
$search_import_key = GETPOSTINT('search_import_key');

if (empty($action) && empty($id) && empty($ref)) {
	$action = 'list';
}

// Load object
//include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php';  // Must be 'include', not 'include_once'. Include fetch and fetch_thirdparty but not fetch_optionals
if ($id || $ref) {
	if ($ref) {
		$tmp = explode('_', $ref);
		$productid = $tmp[0];
		$batch = $tmp[1];
	}
	$object->fetch($id, $productid, $batch);
	$object->ref = $object->batch; // Old system for document management ( it uses $object->ref)
	$upload_dir = $conf->productbatch->multidir_output[$object->entity].'/'.get_exdir(0, 0, 0, 1, $object, $modulepart);
	$filearray = dol_dir_list($upload_dir, "files");
	if (empty($filearray)) {
		// If no files linked yet, use new system on lot id. (Batch is not unique and can be same on different product)
		$object->fetch($id, $productid, $batch);
	}
}

// Initialize a technical object to manage hooks of modules. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('productlotcard', 'globalcard'));


$permissionnote = $user->hasRight('stock', 'creer'); // Used by the include of actions_setnotes.inc.php
$permissiondellink = $user->hasRight('stock', 'creer'); // Used by the include of actions_dellink.inc.php
$permissiontoadd = $user->hasRight('stock', 'creer'); // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php

$usercanread = $user->hasRight('produit', 'lire');
$usercancreate = $user->hasRight('produit', 'creer');
$usercandelete = $user->hasRight('produit', 'supprimer');

$upload_dir = $conf->productbatch->multidir_output[$conf->entity];

$permissiontoread = $usercanread;
$permissiontoadd = $usercancreate;
$permissiontodelete = $usercandelete;

// Security check
if (!isModEnabled('productbatch')) {
	accessforbidden('Module not enabled');
}
$socid = 0;
if ($user->socid > 0) { // Protection if external user
	//$socid = $user->socid;
	accessforbidden();
}
//$result = restrictedArea($user, 'productbatch');
if (!$permissiontoread) {
	accessforbidden();
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
	$error = 0;

	$backurlforlist = dol_buildpath('/product/stock/productlot_list.php', 1);

	if ($action == 'seteatby' && $user->hasRight('stock', 'creer') && ! GETPOST('cancel', 'alpha')) {
		$newvalue = dol_mktime(12, 0, 0, GETPOSTINT('eatbymonth'), GETPOSTINT('eatbyday'), GETPOSTINT('eatbyyear'));

		// check parameters
		$object->eatby = $newvalue;
		$res = $object->checkSellOrEatByMandatory('eatby');
		if ($res < 0) {
			$error++;
		}

		if (!$error) {
			$result = $object->setValueFrom('eatby', $newvalue, '', null, 'date', '', $user, 'PRODUCTLOT_MODIFY');
			if ($result < 0) {
				$error++;
			}
		}

		if ($error) {
			setEventMessages($object->error, $object->errors, 'errors');
			$action = 'editeatby';
		} else {
			$action = 'view';
		}
	}

	if ($action == 'setsellby' && $user->hasRight('stock', 'creer') && ! GETPOST('cancel', 'alpha')) {
		$newvalue = dol_mktime(12, 0, 0, GETPOSTINT('sellbymonth'), GETPOSTINT('sellbyday'), GETPOSTINT('sellbyyear'));

		// check parameters
		$object->sellby = $newvalue;
		$res = $object->checkSellOrEatByMandatory('sellby');
		if ($res < 0) {
			$error++;
		}

		if (!$error) {
			$result = $object->setValueFrom('sellby', $newvalue, '', null, 'date', '', $user, 'PRODUCTLOT_MODIFY');
			if ($result < 0) {
				$error++;
			}
		}

		if ($error) {
			setEventMessages($object->error, $object->errors, 'errors');
			$action = 'editsellby';
		} else {
			$action = 'view';
		}
	}

	if ($action == 'seteol_date' && $user->hasRight('stock', 'creer') && ! GETPOST('cancel', 'alpha')) {
		$newvalue = dol_mktime(12, 0, 0, GETPOSTINT('eol_datemonth'), GETPOSTINT('eol_dateday'), GETPOSTINT('eol_dateyear'));
		$result = $object->setValueFrom('eol_date', $newvalue, '', null, 'date', '', $user, 'PRODUCTLOT_MODIFY');
		if ($result < 0) {
			setEventMessages($object->error, null, 'errors');
			$action = 'editeol_date';
		} else {
			$action = 'view';
		}
	}

	if ($action == 'setmanufacturing_date' && $user->hasRight('stock', 'creer') && ! GETPOST('cancel', 'alpha')) {
		$newvalue = dol_mktime(12, 0, 0, GETPOSTINT('manufacturing_datemonth'), GETPOSTINT('manufacturing_dateday'), GETPOSTINT('manufacturing_dateyear'));
		$result = $object->setValueFrom('manufacturing_date', $newvalue, '', null, 'date', '', $user, 'PRODUCTLOT_MODIFY');
		if ($result < 0) {
			setEventMessages($object->error, null, 'errors');
			$action = 'editmanufacturing_date';
		} else {
			$action = 'view';
		}
	}

	if ($action == 'setscrapping_date' && $user->hasRight('stock', 'creer') && ! GETPOST('cancel', 'alpha')) {
		$newvalue = dol_mktime(12, 0, 0, GETPOSTINT('scrapping_datemonth'), GETPOSTINT('scrapping_dateday'), GETPOSTINT('scrapping_dateyear'));
		$result = $object->setValueFrom('scrapping_date', $newvalue, '', null, 'date', '', $user, 'PRODUCTLOT_MODIFY');
		if ($result < 0) {
			setEventMessages($object->error, null, 'errors');
			$action = 'editscrapping_date';
		} else {
			$action = 'view';
		}
	}

	/* if ($action == 'setcommissionning_date' && $user->hasRight('stock', 'creer') && ! GETPOST('cancel', 'alpha')) {
		$newvalue = dol_mktime(12, 0, 0, GETPOST('commissionning_datemonth', 'int'), GETPOST('commissionning_dateday', 'int'), GETPOST('commissionning_dateyear', 'int'));
		$result = $object->setValueFrom('commissionning_date', $newvalue, '', null, 'date', '', $user, 'PRODUCTLOT_MODIFY');
		if ($result < 0) {
			setEventMessages($object->error, null, 'errors');
			$action == 'editcommissionning_date';
		} else {
			$action = 'view';
		}
	} */

	if ($action == 'setqc_frequency' && $user->hasRight('stock', 'creer') && ! GETPOST('cancel', 'alpha')) {
		$result = $object->setValueFrom('qc_frequency', GETPOST('qc_frequency'), '', null, 'int', '', $user, 'PRODUCT_MODIFY');
		if ($result < 0) { // Prévoir un test de format de durée
			setEventMessages($object->error, null, 'errors');
			$action = 'editqc_frequency';
		} else {
			$action = 'view';
		}
	}

	$triggermodname = 'PRODUCT_LOT_MODIFY'; // Name of trigger action code to execute when we modify record

	// Actions cancel, add, update, update_extras, confirm_validate, confirm_delete, confirm_deleteline, confirm_clone, confirm_close, confirm_setdraft, confirm_reopen
	include DOL_DOCUMENT_ROOT.'/core/actions_addupdatedelete.inc.php';
	/*
	if ($action == 'update_extras') {
		$object->oldcopy = dol_clone($object, 2);

		// Fill array 'array_options' with data from update form
		$ret = $extrafields->setOptionalsFromPost(null, $object, GETPOST('attribute', 'restricthtml'));
		if ($ret < 0) $error++;

		if (!$error) {
			// Actions on extra fields
			$result = $object->insertExtraFields('PRODUCT_LOT_MODIFY');
			if ($result < 0) {
				setEventMessages($object->error, $object->errors, 'errors');
				$error++;
			}
		}

		if ($error) {
			$action = 'edit_extras';
		}
	}

	// Action to add record
	if ($action == 'add') {
		if (GETPOST('cancel', 'alpha')) {
			$urltogo = $backtopage ? $backtopage : dol_buildpath('/stock/list.php', 1);
			header("Location: ".$urltogo);
			exit;
		}

		$error = 0;

		$object->entity = GETPOST('entity', 'int');
		$object->fk_product = GETPOST('fk_product', 'int');
		$object->batch = GETPOST('batch', 'alpha');
		$object->fk_user_creat = GETPOST('fk_user_creat', 'int');
		$object->fk_user_modif = GETPOST('fk_user_modif', 'int');
		$object->import_key = GETPOST('import_key', 'int');

		if (empty($object->ref)) {
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Ref")), null, 'errors');
		}

		if (!$error) {
			$result = $object->create($user);
			if ($result > 0) {
				// Creation OK
				$urltogo = $backtopage ? $backtopage : dol_buildpath('/stock/list.php', 1);
				header("Location: ".$urltogo);
				exit;
			}
			{
				// Creation KO
				if (!empty($object->errors)) setEventMessages(null, $object->errors, 'errors');
			else setEventMessages($object->error, null, 'errors');
				$action = 'create';
			}
		} else {
			$action = 'create';
		}
	}

	// Cancel
	if ($action == 'update' && GETPOST('cancel', 'alpha')) $action = 'view';

	// Action to update record
	if ($action == 'update' && !GETPOST('cancel', 'alpha')) {
		$error = 0;

		$object->entity = GETPOST('entity', 'int');
		$object->fk_product = GETPOST('fk_product', 'int');
		$object->batch = GETPOST('batch', 'alpha');
		$object->fk_user_creat = GETPOST('fk_user_creat', 'int');
		$object->fk_user_modif = GETPOST('fk_user_modif', 'int');
		$object->import_key = GETPOST('import_key', 'int');

		if (empty($object->ref)) {
			$error++;
			setEventMessages($langs->transnoentitiesnoconv("ErrorFieldRequired", $langs->transnoentitiesnoconv("Ref")), null, 'errors');
		}

		if (!$error) {
			$result = $object->update($user);
			if ($result > 0) {
				$action = 'view';
			} else {
				// Creation KO
				if (!empty($object->errors)) setEventMessages(null, $object->errors, 'errors');
				else setEventMessages($object->error, null, 'errors');
				$action = 'edit';
			}
		} else {
			$action = 'edit';
		}
	}

	// Action to delete
	if ($action == 'confirm_delete') {
		$result = $object->delete($user);
		if ($result > 0) {
			// Delete OK
			setEventMessages("RecordDeleted", null, 'mesgs');
			header("Location: ".dol_buildpath('/stock/list.php', 1));
			exit;
		} else {
			if (!empty($object->errors)) setEventMessages(null, $object->errors, 'errors');
			else setEventMessages($object->error, null, 'errors');
		}
	}
	*/
	// Action to build doc
	include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';

	// Actions to send emails
	$triggersendname = 'PRODUCT_LOT_SENTBYMAIL';
	$autocopy = 'MAIN_MAIL_AUTOCOPY_PRODUCT_LOT_TO';
	$trackid = 'productlot'.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';
}




/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);

$title = $langs->trans("ProductLot");
$help_url = '';

llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-product page-stock_productlot_card');

$res = $object->fetch_product();
if ($res > 0 && $object->product) {
	if ($object->product->sell_or_eat_by_mandatory == Product::SELL_OR_EAT_BY_MANDATORY_ID_SELL_BY) {
		$object->fields['sellby']['notnull'] = 1;
	} elseif ($object->product->sell_or_eat_by_mandatory == Product::SELL_OR_EAT_BY_MANDATORY_ID_EAT_BY) {
		$object->fields['eatby']['notnull'] = 1;
	} elseif ($object->product->sell_or_eat_by_mandatory == Product::SELL_OR_EAT_BY_MANDATORY_ID_SELL_AND_EAT) {
		$object->fields['sellby']['notnull'] = 1;
		$object->fields['eatby']['notnull'] = 1;
	}
}
// Part to create
if ($action == 'create') {
	print load_fiche_titre($langs->trans("Batch"), '', 'object_'.$object->picto);

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="add">';
	if ($backtopage) {
		print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	}
	if ($backtopageforcancel) {
		print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';
	}

	print dol_get_fiche_head(array(), '');

	print '<table class="border centpercent tableforfieldcreate">'."\n";

	// Common attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_add.tpl.php';

	// Other attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_add.tpl.php';

	print '</table>'."\n";

	print dol_get_fiche_end();

	print $form->buttonsSaveCancel("Create");

	print '</form>';

	//dol_set_focus('input[name="ref"]');
}


// Part to show record
if ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create'))) {
	$res = $object->fetch_optionals();

	$head = productlot_prepare_head($object);
	print dol_get_fiche_head($head, 'card', $langs->trans("Batch"), -1, $object->picto);

	$formconfirm = '';

	// Confirmation to delete
	if ($action == 'delete') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('DeleteBatch'), $langs->trans('ConfirmDeleteBatch'), 'confirm_delete', '', 0, 1);
	}

	// Call Hook formConfirm
	$parameters = array('formConfirm' => $formconfirm, 'lineid' => $lineid);
	$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	if (empty($reshook)) {
		$formconfirm .= $hookmanager->resPrint;
	} elseif ($reshook > 0) {
		$formconfirm = $hookmanager->resPrint;
	}

	// Print form confirm
	print $formconfirm;

	// Object card
	// ------------------------------------------------------------
	$linkback = '<a href="'.DOL_URL_ROOT.'/product/stock/productlot_list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

	$shownav = 1;
	if ($user->socid && !in_array('batch', explode(',', getDolGlobalString('MAIN_MODULES_FOR_EXTERNAL')))) {
		$shownav = 0;
	}

	$morehtmlref = '';

	dol_banner_tab($object, 'id', $linkback, $shownav, 'rowid', 'batch', $morehtmlref);

	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent tableforfield">'."\n";

	// Product
	print '<tr><td class="titlefield">'.$langs->trans("Product").'</td><td>';
	$producttmp = new Product($db);
	$producttmp->fetch($object->fk_product);
	print $producttmp->getNomUrl(1, 'stock')." - ".$producttmp->label;
	print '</td></tr>';

	// Sell by
	if (!getDolGlobalString('PRODUCT_DISABLE_SELLBY')) {
		print '<tr><td>';
		print $form->editfieldkey($langs->trans('SellByDate'), 'sellby', $object->sellby, $object, $user->hasRight('stock', 'creer'), 'datepicker', '', $object->fields['sellby']['notnull']);
		print '</td><td>';
		print $form->editfieldval($langs->trans('SellByDate'), 'sellby', $object->sellby, $object, $user->hasRight('stock', 'creer'), 'datepicker', '', null, null, '', 1, '', 'id', 'auto', array(), $action);
		print '</td>';
		print '</tr>';
	}

	// Eat by
	if (!getDolGlobalString('PRODUCT_DISABLE_EATBY')) {
		print '<tr><td>';
		print $form->editfieldkey($langs->trans('EatByDate'), 'eatby', $object->eatby, $object, $user->hasRight('stock', 'creer'), 'datepicker', '', $object->fields['eatby']['notnull']);
		print '</td><td>';
		print $form->editfieldval($langs->trans('EatByDate'), 'eatby', $object->eatby, $object, $user->hasRight('stock', 'creer'), 'datepicker', '', null, null, '', 1, '', 'id', 'auto', array(), $action);
		print '</td>';
		print '</tr>';
	}

	if (getDolGlobalString('PRODUCT_LOT_ENABLE_TRACEABILITY')) {
		print '<tr><td>'.$form->editfieldkey($langs->trans('ManufacturingDate'), 'manufacturing_date', $object->manufacturing_date, $object, $user->hasRight('stock', 'creer')).'</td>';
		print '<td>'.$form->editfieldval($langs->trans('ManufacturingDate'), 'manufacturing_date', $object->manufacturing_date, $object, $user->hasRight('stock', 'creer'), 'datepicker').'</td>';
		print '</tr>';
		// print '<tr><td>'.$form->editfieldkey($langs->trans('FirstUseDate'), 'commissionning_date', $object->commissionning_date, $object, $user->hasRight('stock', 'creer')).'</td>';
		// print '<td>'.$form->editfieldval($langs->trans('FirstUseDate'), 'commissionning_date', $object->commissionning_date, $object, $user->hasRight('stock', 'creer'), 'datepicker').'</td>';
		// print '</tr>';
		print '<tr><td>'.$form->editfieldkey($langs->trans('DestructionDate'), 'scrapping_date', $object->scrapping_date, $object, $user->hasRight('stock', 'creer')).'</td>';
		print '<td>'.$form->editfieldval($langs->trans('DestructionDate'), 'scrapping_date', $object->scrapping_date, $object, $user->hasRight('stock', 'creer'), 'datepicker').'</td>';
		print '</tr>';
	}

	// Quality control
	if (getDolGlobalString('PRODUCT_LOT_ENABLE_QUALITY_CONTROL')) {
		print '<tr><td>'.$form->editfieldkey($langs->trans('EndOfLife'), 'eol_date', $object->eol_date, $object, $user->hasRight('stock', 'creer')).'</td>';
		print '<td>'.$form->editfieldval($langs->trans('EndOfLife'), 'eol_date', $object->eol_date, $object, $user->hasRight('stock', 'creer'), 'datepicker').'</td>';
		print '</tr>';
		print '<tr><td>'.$form->editfieldkey($langs->trans('QCFrequency'), 'qc_frequency', $object->qc_frequency, $object, $user->hasRight('stock', 'creer')).'</td>';
		print '<td>'.$form->editfieldval($langs->trans('QCFrequency'), 'qc_frequency', $object->qc_frequency, $object, $user->hasRight('stock', 'creer'), 'string').'</td>';
		print '</tr>';
		print '<tr><td>'.$form->editfieldkey($langs->trans('Lifetime'), 'lifetime', $object->lifetime, $object, $user->hasRight('stock', 'creer')).'</td>';
		print '<td>'.$form->editfieldval($langs->trans('Lifetime'), 'lifetime', $object->lifetime, $object, $user->hasRight('stock', 'creer'), 'string').'</td>';
		print '</tr>';
	}

	// Other attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

	print '</table>';
	print '</div>';
	print '</div>';

	print '<div class="clearboth"></div>';

	print dol_get_fiche_end();

	// Link to other lists
	print '<a href="'.DOL_URL_ROOT.'/product/reassortlot.php?sref='.urlencode($producttmp->ref).'&search_batch='.urlencode($object->batch).'">'.img_object('', 'stock', 'class="pictofixedwidth"').$langs->trans("ShowCurrentStockOfLot").'</a><br>';
	print '<br>';
	print '<a href="'.DOL_URL_ROOT.'/product/stock/movement_list.php?search_product_ref='.urlencode($producttmp->ref).'&search_batch='.urlencode($object->batch).'">'.img_object('', 'movement', 'class="pictofixedwidth"').$langs->trans("ShowLogOfMovementIfLot").'</a><br>';

	print '<br>';


	// Buttons for actions
	if ($action != 'presend' && $action != 'editline') {
		print '<div class="tabsAction">'."\n";
		$parameters = array();
		$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		if ($reshook < 0) {
			setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
		}

		if (empty($reshook)) {
			/*TODO
			if ($user->hasRight('stock', 'lire')) {
				print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=edit&token='.newToken().'">'.$langs->trans("Modify").'</a></div>'."\n";
			}
			*/
			print dolGetButtonAction($langs->trans("Delete"), '', 'delete', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=delete&token='.newToken(), 'delete', $permissiontodelete);
		}

		print '</div>'."\n";
	}
}



/*
 * Generated documents
 */

if ($action != 'presend') {
	print '<div class="fichecenter"><div class="fichehalfleft">';
	print '<a name="builddoc"></a>'; // ancre

	$includedocgeneration = 1;

	// Documents
	if ($includedocgeneration) {
		$objref = dol_sanitizeFileName($object->ref);
		$relativepath = $objref.'/'.$objref.'.pdf';
		$filedir = $conf->productbatch->multidir_output[$object->entity].'/'.get_exdir(0, 0, 0, 1, $object, 'product_batch');
		$urlsource = $_SERVER["PHP_SELF"]."?id=".$object->id;
		$genallowed = $usercanread; // If you can read, you can build the PDF to read content
		$delallowed = $usercancreate; // If you can create/edit, you can remove a file on card
		print $formfile->showdocuments('product_batch', $objref, $filedir, $urlsource, $genallowed, $delallowed, $object->model_pdf, 0, 0, 0, 28, 0, '', 0, '', (empty($object->default_lang) ? '' : $object->default_lang), '', $object);
	}

	print '</div><div class="fichehalfright">';

	$MAXEVENT = 10;

	include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
	$formactions = new FormActions($db);
	$somethingshown = $formactions->showactions($object, 'productlot', 0, 1, '', $MAXEVENT);

	print '</div></div>';
}

// End of page
llxFooter();
$db->close();
