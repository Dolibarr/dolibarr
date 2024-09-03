<?php
/* Copyright (C) 2017-2023  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2019       Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2023       Charlene Benke          <charlene@patas-monkey.com>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 *    \file       htdocs/bom/bom_card.php
 *    \ingroup    bom
 *    \brief      Page to create/edit/view Bill Of Material
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/bom/class/bom.class.php';
require_once DOL_DOCUMENT_ROOT.'/bom/lib/bom.lib.php';
require_once DOL_DOCUMENT_ROOT.'/mrp/lib/mrp.lib.php';


// Load translation files required by the page
$langs->loadLangs(array('mrp', 'other'));

// Get parameters
$id      = GETPOSTINT('id');
$lineid  = GETPOSTINT('lineid');
$ref     = GETPOST('ref', 'alpha');
$action  = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$cancel  = GETPOST('cancel', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'bomcard'; // To manage different context of search
$backtopage  = GETPOST('backtopage', 'alpha');


// PDF
$hidedetails = (GETPOSTINT('hidedetails') ? GETPOSTINT('hidedetails') : (getDolGlobalString('MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS') ? 1 : 0));
$hidedesc = (GETPOSTINT('hidedesc') ? GETPOSTINT('hidedesc') : (getDolGlobalString('MAIN_GENERATE_DOCUMENTS_HIDE_DESC') ? 1 : 0));
$hideref = (GETPOSTINT('hideref') ? GETPOSTINT('hideref') : (getDolGlobalString('MAIN_GENERATE_DOCUMENTS_HIDE_REF') ? 1 : 0));

// Initialize a technical objects
$object = new BOM($db);
$extrafields = new ExtraFields($db);
$diroutputmassaction = $conf->bom->dir_output.'/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array('bomcard', 'globalcard')); // Note that conf->hooks_modules contains array

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

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be 'include', not 'include_once'.
if ($object->id > 0) {
	$object->calculateCosts();
}


// Security check - Protection if external user
//if ($user->socid > 0) accessforbidden();
//if ($user->socid > 0) $socid = $user->socid;
$isdraft = (($object->status == $object::STATUS_DRAFT) ? 1 : 0);
$result = restrictedArea($user, 'bom', $object->id, $object->table_element, '', '', 'rowid', $isdraft);

// Permissions
$permissionnote = $user->hasRight('bom', 'write'); // Used by the include of actions_setnotes.inc.php
$permissiondellink = $user->hasRight('bom', 'write'); // Used by the include of actions_dellink.inc.php
$permissiontoadd = $user->hasRight('bom', 'write'); // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
$permissiontodelete = $user->hasRight('bom', 'delete') || ($permissiontoadd && isset($object->status) && $object->status == $object::STATUS_DRAFT);
$upload_dir = $conf->bom->multidir_output[isset($object->entity) ? $object->entity : 1];


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

	$backurlforlist = DOL_URL_ROOT.'/bom/bom_list.php';

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) {
				$backtopage = $backurlforlist;
			} else {
				$backtopage = DOL_URL_ROOT.'/bom/bom_card.php?id='.($id > 0 ? $id : '__ID__');
			}
		}
	}

	$triggermodname = 'BOM_MODIFY'; // Name of trigger action code to execute when we modify record


	// Actions cancel, add, update, delete or clone
	include DOL_DOCUMENT_ROOT.'/core/actions_addupdatedelete.inc.php';
	// The fetch/fetch_lines was redone into the inc.php so we must recall the calculateCosts()
	if ($action == 'confirm_validate' && $object->id > 0) {
		$object->calculateCosts();
	}

	// Actions when linking object each other
	include DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php';

	// Actions when printing a doc from card
	include DOL_DOCUMENT_ROOT.'/core/actions_printing.inc.php';

	// Action to move up and down lines of object
	//include DOL_DOCUMENT_ROOT.'/core/actions_lineupdown.inc.php';

	// Action to build doc
	include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';

	// Actions to send emails
	$triggersendname = 'BOM_SENTBYMAIL';
	$autocopy = 'MAIN_MAIL_AUTOCOPY_BOM_TO';
	$trackid = 'bom'.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';

	// Add line
	if ($action == 'addline' && $user->hasRight('bom', 'write')) {
		$langs->load('errors');
		$error = 0;
		$predef = '';

		// Set if we used free entry or predefined product
		$bom_child_id = GETPOSTINT('bom_id');
		if ($bom_child_id > 0) {
			$bom_child = new BOM($db);
			$res = $bom_child->fetch($bom_child_id);
			if ($res) {
				$idprod = $bom_child->fk_product;
			}
		} else {
			$idprod = (GETPOSTINT('idprodservice') ? GETPOSTINT('idprodservice') : GETPOSTINT('idprod'));
		}

		$qty = price2num(GETPOST('qty', 'alpha'), 'MS');
		$qty_frozen = price2num(GETPOST('qty_frozen', 'alpha'), 'MS');
		$disable_stock_change = GETPOSTINT('disable_stock_change');
		$fk_workstation = GETPOSTINT('idworkstations');
		$efficiency = price2num(GETPOST('efficiency', 'alpha'));
		$fk_unit = GETPOST('fk_unit', 'alphanohtml');

		$fk_default_workstation = 0;
		if (!empty($idprod) && isModEnabled('workstation')) {
			$product = new Product($db);
			$res = $product->fetch($idprod);
			if ($res > 0 && $product->type == Product::TYPE_SERVICE) {
				if ($fk_workstation > 0) {
					$fk_default_workstation = $fk_workstation;
				} else {
					$fk_default_workstation = $product->fk_default_workstation;
				}
			}
			if (empty($fk_unit)) {
				$fk_unit = $product->fk_unit;
			}
		}

		if ($qty == '') {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Qty')), null, 'errors');
			$error++;
		}
		if (!($idprod > 0)) {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Product')), null, 'errors');
			$error++;
		}

		if ($object->fk_product == $idprod) {
			setEventMessages($langs->trans('TheProductXIsAlreadyTheProductToProduce'), null, 'errors');
			$error++;
		}

		// We check if we're allowed to add this bom
		$TParentBom = array();
		$object->getParentBomTreeRecursive($TParentBom);
		if ($bom_child_id > 0 && !empty($TParentBom) && in_array($bom_child_id, $TParentBom)) {
			$n_child = new BOM($db);
			$n_child->fetch($bom_child_id);
			setEventMessages($langs->transnoentities('BomCantAddChildBom', $n_child->getNomUrl(1), $object->getNomUrl(1)), null, 'errors');
			$error++;
		}

		if (!$error) {
			// Extrafields
			$extralabelsline = $extrafields->fetch_name_optionals_label($object->table_element_line);
			$array_options = $extrafields->getOptionalsFromPost($object->table_element_line, $predef);
			// Unset extrafield
			if (is_array($extralabelsline)) {
				// Get extra fields
				foreach ($extralabelsline as $key => $value) {
					unset($_POST["options_".$key]);
				}
			}

			$result = $object->addLine($idprod, $qty, $qty_frozen, $disable_stock_change, $efficiency, -1, $bom_child_id, null, $fk_unit, $array_options, $fk_default_workstation);

			if ($result <= 0) {
				setEventMessages($object->error, $object->errors, 'errors');
				$action = '';
			} else {
				unset($_POST['idprod']);
				unset($_POST['idprodservice']);
				unset($_POST['qty']);
				unset($_POST['qty_frozen']);
				unset($_POST['disable_stock_change']);
			}

			$object->fetchLines();

			$object->calculateCosts();
		}
	}

	// Update line
	if ($action == 'updateline' && $user->hasRight('bom', 'write')) {
		$langs->load('errors');
		$error = 0;

		// Set if we used free entry or predefined product
		$qty = price2num(GETPOST('qty', 'alpha'), 'MS');
		$qty_frozen = price2num(GETPOST('qty_frozen', 'alpha'), 'MS');
		$disable_stock_change = GETPOSTINT('disable_stock_change');
		$efficiency = price2num(GETPOST('efficiency', 'alpha'));
		$fk_unit = GETPOST('fk_unit', 'alphanohtml');

		if ($qty == '') {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Qty')), null, 'errors');
			$error++;
		}

		if (!$error) {
			// Extrafields
			$extralabelsline = $extrafields->fetch_name_optionals_label($object->table_element_line);
			$array_options = $extrafields->getOptionalsFromPost($object->table_element_line);
			// Unset extrafield
			if (is_array($extralabelsline)) {
				// Get extra fields
				foreach ($extralabelsline as $key => $value) {
					unset($_POST["options_".$key]);
				}
			}

			$bomline = new BOMLine($db);
			$bomline->fetch($lineid);

			$fk_default_workstation = $bomline->fk_default_workstation;
			if (isModEnabled('workstation') &&  GETPOSTISSET('idworkstations')) {
				$fk_default_workstation = GETPOSTINT('idworkstations');
			}

			$result = $object->updateLine($lineid, $qty, (int) $qty_frozen, (int) $disable_stock_change, $efficiency, $bomline->position, $bomline->import_key, $fk_unit, $array_options, $fk_default_workstation);

			if ($result <= 0) {
				setEventMessages($object->error, $object->errors, 'errors');
				$action = '';
			} else {
				unset($_POST['idprod']);
				unset($_POST['idprodservice']);
				unset($_POST['qty']);
				unset($_POST['qty_frozen']);
				unset($_POST['disable_stock_change']);
			}

			$object->fetchLines();

			$object->calculateCosts();
		}
	}
}


/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);

if ($object->id > 0) {
	$title = $object->ref;
} else {
	$title = $langs->trans('BOM');
}
$help_url = 'EN:Module_BOM';
llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-bom page-card');

// Part to create
if ($action == 'create') {
	print load_fiche_titre($langs->trans("NewBOM"), '', 'bom');

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="add">';
	print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';

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
}

// Part to edit record
if (($id || $ref) && $action == 'edit') {
	print load_fiche_titre($langs->trans("BillOfMaterials"), '', 'cubes');

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="update">';
	print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	print '<input type="hidden" name="id" value="'.$object->id.'">';

	print dol_get_fiche_head();

	//$object->fields['keyfield']['disabled'] = 1;

	print '<table class="border centpercent tableforfieldedit">'."\n";

	// Common attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_edit.tpl.php';

	// Other attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_edit.tpl.php';

	print '</table>';

	print dol_get_fiche_end();

	print $form->buttonsSaveCancel("Create");

	print '</form>';
}

// Part to show record
if ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create'))) {
	$head = bomPrepareHead($object);
	print dol_get_fiche_head($head, 'card', $langs->trans("BillOfMaterials"), -1, 'bom');

	$formconfirm = '';

	// Confirmation to delete
	if ($action == 'delete') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('DeleteBillOfMaterials'), $langs->trans('ConfirmDeleteBillOfMaterials'), 'confirm_delete', '', 0, 1);
	}
	// Confirmation to delete line
	if ($action == 'deleteline') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&lineid='.$lineid, $langs->trans('DeleteLine'), $langs->trans('ConfirmDeleteLine'), 'confirm_deleteline', '', 0, 1);
	}

	// Confirmation of validation
	if ($action == 'validate') {
		// We check that object has a temporary ref
		$ref = substr($object->ref, 1, 4);
		if ($ref == 'PROV') {
			$object->fetch_product();
			$numref = $object->getNextNumRef($object->product);
		} else {
			$numref = $object->ref;
		}

		$text = $langs->trans('ConfirmValidateBom', $numref);
		/*if (isModEnabled('notification'))
		 {
		 require_once DOL_DOCUMENT_ROOT . '/core/class/notify.class.php';
		 $notify = new Notify($db);
		 $text .= '<br>';
		 $text .= $notify->confirmMessage('BOM_VALIDATE', $object->socid, $object);
		 }*/

		$formquestion = array();
		if (isModEnabled('bom')) {
			$langs->load("mrp");
			$forcecombo = 0;
			if ($conf->browser->name == 'ie') {
				$forcecombo = 1; // There is a bug in IE10 that make combo inside popup crazy
			}
			$formquestion = array(
				// 'text' => $langs->trans("ConfirmClone"),
				// array('type' => 'checkbox', 'name' => 'clone_content', 'label' => $langs->trans("CloneMainAttributes"), 'value' => 1),
				// array('type' => 'checkbox', 'name' => 'update_prices', 'label' => $langs->trans("PuttingPricesUpToDate"), 'value' => 1),
			);
		}

		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('Validate'), $text, 'confirm_validate', $formquestion, 0, 1, 220);
	}

	// Confirmation of closing
	if ($action == 'close') {
		$text = $langs->trans('ConfirmCloseBom', $object->ref);
		/*if (isModEnabled('notification'))
		 {
		 require_once DOL_DOCUMENT_ROOT . '/core/class/notify.class.php';
		 $notify = new Notify($db);
		 $text .= '<br>';
		 $text .= $notify->confirmMessage('BOM_CLOSE', $object->socid, $object);
		 }*/

		$formquestion = array();
		if (isModEnabled('bom')) {
			$langs->load("mrp");
			$forcecombo = 0;
			if ($conf->browser->name == 'ie') {
				$forcecombo = 1; // There is a bug in IE10 that make combo inside popup crazy
			}
			$formquestion = array(
				// 'text' => $langs->trans("ConfirmClone"),
				// array('type' => 'checkbox', 'name' => 'clone_content', 'label' => $langs->trans("CloneMainAttributes"), 'value' => 1),
				// array('type' => 'checkbox', 'name' => 'update_prices', 'label' => $langs->trans("PuttingPricesUpToDate"), 'value' => 1),
			);
		}

		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('Close'), $text, 'confirm_close', $formquestion, 0, 1, 220);
	}

	// Confirmation of reopen
	if ($action == 'reopen') {
		$text = $langs->trans('ConfirmReopenBom', $object->ref);
		/*if (isModEnabled('notification'))
		 {
		 require_once DOL_DOCUMENT_ROOT . '/core/class/notify.class.php';
		 $notify = new Notify($db);
		 $text .= '<br>';
		 $text .= $notify->confirmMessage('BOM_CLOSE', $object->socid, $object);
		 }*/

		$formquestion = array();
		if (isModEnabled('bom')) {
			$langs->load("mrp");
			require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
			$forcecombo = 0;
			if ($conf->browser->name == 'ie') {
				$forcecombo = 1; // There is a bug in IE10 that make combo inside popup crazy
			}
			$formquestion = array(
				// 'text' => $langs->trans("ConfirmClone"),
				// array('type' => 'checkbox', 'name' => 'clone_content', 'label' => $langs->trans("CloneMainAttributes"), 'value' => 1),
				// array('type' => 'checkbox', 'name' => 'update_prices', 'label' => $langs->trans("PuttingPricesUpToDate"), 'value' => 1),
			);
		}

		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ReOpen'), $text, 'confirm_reopen', $formquestion, 0, 1, 220);
	}

	// Clone confirmation
	if ($action == 'clone') {
		// Create an array for form
		$formquestion = array();
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ToClone'), $langs->trans('ConfirmCloneBillOfMaterials', $object->ref), 'confirm_clone', $formquestion, 'yes', 1);
	}

	// Confirmation of action xxxx
	if ($action == 'setdraft') {
		$text = $langs->trans('ConfirmSetToDraft', $object->ref);

		$formquestion = array();
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('SetToDraft'), $text, 'confirm_setdraft', $formquestion, 0, 1, 220);
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
	$linkback = '<a href="'.DOL_URL_ROOT.'/bom/bom_list.php?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

	$morehtmlref = '<div class="refidno">';
	/*
	 // Ref bis
	 $morehtmlref.=$form->editfieldkey("RefBis", 'ref_client', $object->ref_client, $object, $user->hasRight('bom', 'creer'), 'string', '', 0, 1);
	 $morehtmlref.=$form->editfieldval("RefBis", 'ref_client', $object->ref_client, $object, $user->hasRight('bom', 'creer'), 'string', '', null, null, '', 1);
	 // Thirdparty
	 $morehtmlref.='<br>'.$langs->trans('ThirdParty') . ' : ' . $soc->getNomUrl(1);
	 // Project
	 if (isModEnabled('project'))
	 {
	 $langs->load("projects");
	 $morehtmlref.='<br>'.$langs->trans('Project') . ' ';
	 if ($permissiontoadd)
	 {
	 if ($action != 'classify')
	 $morehtmlref.='<a class="editfielda" href="' . $_SERVER['PHP_SELF'] . '?action=classify&token='.newToken().'&id=' . $object->id . '">' . img_edit($langs->transnoentitiesnoconv('SetProject')) . '</a> : ';
	 if ($action == 'classify') {
	 //$morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'projectid', 0, 0, 1, 1);
	 $morehtmlref.='<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
	 $morehtmlref.='<input type="hidden" name="action" value="classin">';
	 $morehtmlref.='<input type="hidden" name="token" value="'.newToken().'">';
	 $morehtmlref.=$formproject->select_projects($object->socid, $object->fk_project, 'projectid', 0, 0, 1, 0, 1, 0, 0, '', 1);
	 $morehtmlref.='<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
	 $morehtmlref.='</form>';
	 } else {
	 $morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'none', 0, 0, 0, 1);
	 }
	 } else {
	 if (! empty($object->fk_project)) {
	 $proj = new Project($db);
	 $proj->fetch($object->fk_project);
	 $morehtmlref.=$proj->getNomUrl();
	 } else {
	 $morehtmlref.='';
	 }
	 }
	 }
	 */
	$morehtmlref .= '</div>';


	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);


	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent tableforfield">'."\n";

	// Common attributes
	$keyforbreak = 'duration';
	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_view.tpl.php';
	$object->calculateCosts();
	print '<tr><td>'.$form->textwithpicto($langs->trans("TotalCost"), $langs->trans("BOMTotalCost")).'</td><td><span class="amount">'.price($object->total_cost).'</span></td></tr>';
	print '<tr><td>'.$langs->trans("UnitCost").'</td><td>'.price($object->unit_cost).'</td></tr>';

	// Other attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

	print '</table>';
	print '</div>';
	print '</div>';

	print '<div class="clearboth"></div>';

	print dol_get_fiche_end();



	/*
	 * Lines
	 */

	if (!empty($object->table_element_line)) {
		// Products

		$res = $object->fetchLinesbytypeproduct(0);		// Load all lines products into ->lines
		$object->calculateCosts();

		print ($res == 0 && $object->status >= $object::STATUS_VALIDATED) ? '' : load_fiche_titre($langs->trans('BOMProductsList'), '', 'product');

		print '	<form name="addproduct" id="listbomproducts" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="POST">
    	<input type="hidden" name="token" value="' . newToken() . '">
    	<input type="hidden" name="action" value="' . (($action != 'editline') ? 'addline' : 'updateline') . '">
    	<input type="hidden" name="mode" value="">
		<input type="hidden" name="page_y" value="">
    	<input type="hidden" name="id" value="' . $object->id . '">
    	';

		if (!empty($conf->use_javascript_ajax) && $object->status == 0) {
			include DOL_DOCUMENT_ROOT.'/core/tpl/ajaxrow.tpl.php';
		}

		print '<div class="div-table-responsive-no-min">';
		if (!empty($object->lines) || ($object->status == $object::STATUS_DRAFT && $permissiontoadd && $action != 'selectlines' && $action != 'editline')) {
			print '<table id="tablelines" class="noborder noshadow centpercent">';
		}

		if (!empty($object->lines)) {
			$object->printObjectLines($action, $mysoc, null, GETPOSTINT('lineid'), 1, '/bom/tpl');
		}

		// Form to add new line
		if ($object->status == 0 && $permissiontoadd && $action != 'selectlines') {
			if ($action != 'editline') {
				// Add products/services form


				$parameters = array();
				$reshook = $hookmanager->executeHooks('formAddObjectLine', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
				if ($reshook < 0) {
					setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
				}
				if (empty($reshook)) {
					$object->formAddObjectLine(1, $mysoc, null, '/bom/tpl');
				}
			}
		}

		if (!empty($object->lines) || ($object->status == $object::STATUS_DRAFT && $permissiontoadd && $action != 'selectlines' && $action != 'editline')) {
			print '</table>';
		}
		print '</div>';

		print "</form>\n";


		// Services

		$filtertype = 1;
		$res = $object->fetchLinesbytypeproduct(1);		// Load all lines services into ->lines
		$object->calculateCosts();

		print ($res == 0 && $object->status >= $object::STATUS_VALIDATED) ? '' : load_fiche_titre($langs->trans('BOMServicesList'), '', 'service');

		print '	<form name="addservice" id="listbomservices" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="POST">
    	<input type="hidden" name="token" value="' . newToken() . '">
    	<input type="hidden" name="action" value="' . (($action != 'editline') ? 'addline' : 'updateline') . '">
    	<input type="hidden" name="mode" value="">
		<input type="hidden" name="page_y" value="">    		<input type="hidden" name="id" value="' . $object->id . '">
    	';

		if (!empty($conf->use_javascript_ajax) && $object->status == 0) {
			$tagidfortablednd = 'tablelinesservice';
			include DOL_DOCUMENT_ROOT . '/core/tpl/ajaxrow.tpl.php';
		}

		print '<div class="div-table-responsive-no-min">';
		if (!empty($object->lines) || ($object->status == $object::STATUS_DRAFT && $permissiontoadd && $action != 'selectlines' && $action != 'editline')) {
			print '<table id="tablelinesservice" class="noborder noshadow centpercent">';
		}

		if (!empty($object->lines)) {
			$object->printObjectLines($action, $mysoc, null, GETPOSTINT('lineid'), 1, '/bom/tpl');
		}

		// Form to add new line
		if ($object->status == 0 && $permissiontoadd && $action != 'selectlines') {
			if ($action != 'editline') {
				// Add services form
				$parameters = array();
				$reshook = $hookmanager->executeHooks('formAddObjectServiceLine', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
				if ($reshook < 0) {
					setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
				}
				if (empty($reshook)) {
					$object->formAddObjectLine(1, $mysoc, null, '/bom/tpl');
				}
			}
		}
	}

	if (!empty($object->lines) || ($object->status == $object::STATUS_DRAFT && $permissiontoadd && $action != 'selectlines' && $action != 'editline')) {
		print '</table>';
	}
	print '</div>';

	print "</form>\n";


	mrpCollapseBomManagement();


	$res = $object->fetchLines();

	// Buttons for actions

	if ($action != 'presend' && $action != 'editline') {
		print '<div class="tabsAction">'."\n";
		$parameters = array();
		$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		if ($reshook < 0) {
			setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
		}

		if (empty($reshook)) {
			// Send
			//if (empty($user->socid)) {
			//	print '<a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=presend&mode=init#formmailbeforetitle">' . $langs->trans('SendMail') . '</a>'."\n";
			//}

			// Back to draft
			if ($object->status == $object::STATUS_VALIDATED) {
				if ($permissiontoadd) {
					print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=setdraft&token='.newToken().'">'.$langs->trans("SetToDraft").'</a>'."\n";
				}
			}

			// Modify
			if ($object->status == $object::STATUS_DRAFT) {
				if ($permissiontoadd) {
					print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=edit&token='.newToken().'">'.$langs->trans("Modify").'</a>'."\n";
				} else {
					print '<a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans('Modify').'</a>'."\n";
				}
			}

			// Validate
			if ($object->status == $object::STATUS_DRAFT) {
				if ($permissiontoadd) {
					if (is_array($object->lines) && count($object->lines) > 0) {
						print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;action=validate&amp;token='.newToken().'">'.$langs->trans("Validate").'</a>'."\n";
					} else {
						$langs->load("errors");
						print '<a class="butActionRefused classfortooltip" href="" title="'.$langs->trans("ErrorAddAtLeastOneLineFirst").'">'.$langs->trans("Validate").'</a>'."\n";
					}
				}
			}

			// Re-open
			if ($permissiontoadd && $object->status == $object::STATUS_CANCELED) {
				print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=reopen&token='.newToken().'">'.$langs->trans("ReOpen").'</a>'."\n";
			}

			// Create MO
			if (isModEnabled('mrp')) {
				if ($object->status == $object::STATUS_VALIDATED && $user->hasRight('mrp', 'write')) {
					print '<a class="butAction" href="'.DOL_URL_ROOT.'/mrp/mo_card.php?action=create&fk_bom='.$object->id.'&token='.newToken().'&backtopageforcancel='.urlencode($_SERVER["PHP_SELF"].'?id='.$object->id).'">'.$langs->trans("CreateMO").'</a>'."\n";
				}
			}

			// Clone
			if ($permissiontoadd) {
				print dolGetButtonAction($langs->trans("ToClone"), '', 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.(!empty($object->socid) ? '&socid='.$object->socid : "").'&action=clone&object=bom', 'clone', $permissiontoadd);
			}

			// Close / Cancel
			if ($permissiontoadd && $object->status == $object::STATUS_VALIDATED) {
				print '<a class="butActionDelete" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=close&token='.newToken().'">'.$langs->trans("Disable").'</a>'."\n";
			}

			/*
			  if ($user->hasRight('bom', 'write')) {
				  if ($object->status == 1) {
					  print '<a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=disable&token='.newToken().'">'.$langs->trans("Disable").'</a>'."\n";
				  } else {
					  print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=enable&token='.newToken().'">'.$langs->trans("Enable").'</a>'."\n";
				  }
			  }
			*/

			// Delete
			print dolGetButtonAction($langs->trans("Delete"), '', 'delete', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=delete&token='.newToken(), 'delete', $permissiontodelete);
		}
		print '</div>'."\n";
	}


	// Select mail models is same action as presend
	if (GETPOST('modelselected')) {
		$action = 'presend';
	}

	if ($action != 'presend') {
		print '<div class="fichecenter"><div class="fichehalfleft">';
		print '<a name="builddoc"></a>'; // ancre

		// Documents
		$objref = dol_sanitizeFileName($object->ref);
		$relativepath = $objref.'/'.$objref.'.pdf';
		$filedir = $conf->bom->dir_output.'/'.$objref;
		$urlsource = $_SERVER["PHP_SELF"]."?id=".$object->id;
		$genallowed = $user->hasRight('bom', 'read'); // If you can read, you can build the PDF to read content
		$delallowed = $user->hasRight('bom', 'write'); // If you can create/edit, you can remove a file on card
		print $formfile->showdocuments('bom', $objref, $filedir, $urlsource, $genallowed, $delallowed, $object->model_pdf, 1, 0, 0, 28, 0, '', '', '', $langs->defaultlang);

		// Show links to link elements
		$linktoelem = $form->showLinkToObjectBlock($object, null, array('bom'));
		$somethingshown = $form->showLinkedObjectBlock($object, $linktoelem);


		print '</div><div class="fichehalfright">';

		$MAXEVENT = 10;

		$morehtmlcenter = dolGetButtonTitle($langs->trans('SeeAll'), '', 'fa fa-bars imgforviewmode', DOL_URL_ROOT.'/bom/bom_agenda.php?id='.$object->id);

		// List of actions on element
		include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
		$formactions = new FormActions($db);
		$somethingshown = $formactions->showactions($object, $object->element, 0, 1, '', $MAXEVENT, '', $morehtmlcenter);

		print '</div></div>';
	}

	//Select mail models is same action as presend
	if (GETPOST('modelselected')) {
		$action = 'presend';
	}

	// Presend form
	$modelmail = 'bom';
	$defaulttopic = 'InformationMessage';
	$diroutput = $conf->bom->dir_output;
	$trackid = 'bom'.$object->id;

	include DOL_DOCUMENT_ROOT.'/core/tpl/card_presend.tpl.php';
}


// End of page
llxFooter();
$db->close();
