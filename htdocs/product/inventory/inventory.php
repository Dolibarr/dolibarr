<?php
/* Copyright (C) 2019 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *   	\file       htdocs/product/inventory/inventory.php
 *		\ingroup    inventory
 *		\brief      Tabe to enter counting
 */

require '../../main.inc.php';
include_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
include_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
include_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
include_once DOL_DOCUMENT_ROOT.'/product/inventory/class/inventory.class.php';
include_once DOL_DOCUMENT_ROOT.'/product/inventory/lib/inventory.lib.php';

// Load translation files required by the page
$langs->loadLangs(array("stocks", "other", "productbatch"));

// Get parameters
$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$cancel = GETPOST('cancel', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'inventorycard'; // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha');

$fk_warehouse = GETPOST('fk_warehouse', 'int');
$fk_product = GETPOST('fk_product', 'int');
$lineid = GETPOST('lineid', 'int');
$batch = GETPOST('batch', 'alphanohtml');

if (empty($conf->global->MAIN_USE_ADVANCED_PERMS))
{
	$result = restrictedArea($user, 'stock', $id);
} else {
	$result = restrictedArea($user, 'stock', $id, '', 'inventory_advance');
}

// Initialize technical objects
$object = new Inventory($db);
$extrafields = new ExtraFields($db);
$diroutputmassaction = $conf->stock->dir_output.'/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array('inventorycard')); // Note that conf->hooks_modules contains array

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// Initialize array of search criterias
$search_all = GETPOST("search_all", 'alpha');
$search = array();
foreach ($object->fields as $key => $val)
{
	if (GETPOST('search_'.$key, 'alpha')) $search[$key] = GETPOST('search_'.$key, 'alpha');
}

if (empty($action) && empty($id) && empty($ref)) $action = 'view';

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be include, not include_once.

// Security check - Protection if external user
//if ($user->socid > 0) accessforbidden();
//if ($user->socid > 0) $socid = $user->socid;
//$result = restrictedArea($user, 'mymodule', $id);

if (empty($conf->global->MAIN_USE_ADVANCED_PERMS))
{
	$permissiontoadd = $user->rights->stock->creer;
	$permissiontodelete = $user->rights->stock->supprimer;
} else {
	$permissiontoadd = $user->rights->stock->inventory_advance->write;
	$permissiontodelete = $user->rights->stock->inventory_advance->write;
}

$now = dol_now();


/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
	$error = 0;

	$backurlforlist = DOL_URL_ROOT.'/product/inventory/list.php';
	$backtopage = DOL_URL_ROOT.'/product/inventory/inventory.php?id='.$object->id;

	// Actions cancel, add, update, delete or clone
	include DOL_DOCUMENT_ROOT.'/core/actions_addupdatedelete.inc.php';

	// Actions when linking object each other
	include DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php';

	// Actions when printing a doc from card
	include DOL_DOCUMENT_ROOT.'/core/actions_printing.inc.php';

	// Actions to send emails
	/*$triggersendname = 'MYOBJECT_SENTBYMAIL';
	$autocopy='MAIN_MAIL_AUTOCOPY_MYOBJECT_TO';
	$trackid='stockinv'.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';*/

	if (GETPOST('addline', 'alpha')) {
		if ($fk_warehouse <= 0) {
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Warehouse")), null, 'errors');
		}
		if ($fk_product <= 0) {
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Product")), null, 'errors');
		}

		if (!$error && !empty($conf->productbatch->enabled)) {
			$tmpproduct = new Product($db);
			$result = $tmpproduct->fetch($fk_product);

			if (!$error && $tmpproduct->status_batch && !$batch) {
				$error++;
				$langs->load("errors");
				setEventMessages($langs->trans("ErrorProductNeedBatchNumber", $tmpproduct->ref), null, 'errors');
			}
			if (!$error && !$tmpproduct->status_batch && $batch) {
				$error++;
				$langs->load("errors");
				setEventMessages($langs->trans("ErrorProductDoesNotNeedBatchNumber", $tmpproduct->ref), null, 'errors');
			}
		}
		if (!$error) {
			$tmp = new InventoryLine($db);
			$tmp->fk_inventory = $object->id;
			$tmp->fk_warehouse = $fk_warehouse;
			$tmp->fk_product = $fk_product;
			$tmp->batch = $batch;
			$tmp->datec = $now;

			$result = $tmp->create($user);
			if ($result < 0) {
				if ($db->lasterrno() == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
					setEventMessages($langs->trans("DuplicateRecord"), null, 'errors');
				} else {
					dol_print_error($db, $tmp->error, $tmp->errors);
				}
			}
		}
	}
}




/*
 * View
 */

$form = new Form($db);
$formproduct = new FormProduct($db);

llxHeader('', $langs->trans('Inventory'), '');

// Example : Adding jquery code
print '<script type="text/javascript" language="javascript">
jQuery(document).ready(function() {
	function init_myfunc()
	{
		jQuery("#myid").removeAttr(\'disabled\');
		jQuery("#myid").attr(\'disabled\',\'disabled\');
	}
	init_myfunc();
	jQuery("#mybutton").click(function() {
		init_myfunc();
	});
});
</script>';


// Part to show record
if ($object->id > 0)
{
	$res = $object->fetch_optionals();

	$head = inventoryPrepareHead($object);
	print dol_get_fiche_head($head, 'inventory', $langs->trans("Inventory"), -1, 'stock');

	$formconfirm = '';

	// Confirmation to delete
	if ($action == 'delete') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('DeleteInventory'), $langs->trans('ConfirmDeleteOrder'), 'confirm_delete', '', 0, 1);
	}
	// Confirmation to delete line
	if ($action == 'deleteline')
	{
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&lineid='.$lineid, $langs->trans('DeleteLine'), $langs->trans('ConfirmDeleteLine'), 'confirm_deleteline', '', 0, 1);
	}

	// Clone confirmation
	if ($action == 'clone') {
		// Create an array for form
		$formquestion = array();
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ToClone'), $langs->trans('ConfirmCloneMyObject', $object->ref), 'confirm_clone', $formquestion, 'yes', 1);
	}

	// Call Hook formConfirm
	$parameters = array('formConfirm' => $formconfirm, 'lineid' => $lineid);
	$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	if (empty($reshook)) $formconfirm .= $hookmanager->resPrint;
	elseif ($reshook > 0) $formconfirm = $hookmanager->resPrint;

	// Print form confirm
	print $formconfirm;


	// Object card
	// ------------------------------------------------------------
	$linkback = '<a href="'.DOL_URL_ROOT.'/product/inventory/list.php'.(!empty($socid) ? '?socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

	$morehtmlref = '<div class="refidno">';
	/*
	// Ref bis
	$morehtmlref.=$form->editfieldkey("RefBis", 'ref_client', $object->ref_client, $object, $user->rights->inventory->creer, 'string', '', 0, 1);
	$morehtmlref.=$form->editfieldval("RefBis", 'ref_client', $object->ref_client, $object, $user->rights->inventory->creer, 'string', '', null, null, '', 1);
	// Thirdparty
	$morehtmlref.='<br>'.$langs->trans('ThirdParty') . ' : ' . $soc->getNomUrl(1);
	// Project
	if (! empty($conf->projet->enabled))
	{
	    $langs->load("projects");
	    $morehtmlref.='<br>'.$langs->trans('Project') . ' ';
	    if ($user->rights->inventory->creer)
	    {
	        if ($action != 'classify')
	        {
	            $morehtmlref.='<a class="editfielda" href="' . $_SERVER['PHP_SELF'] . '?action=classify&amp;id=' . $object->id . '">' . img_edit($langs->transnoentitiesnoconv('SetProject')) . '</a> : ';
	            if ($action == 'classify') {
	                //$morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'projectid', 0, 0, 1, 1);
	                $morehtmlref.='<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
	                $morehtmlref.='<input type="hidden" name="action" value="classin">';
	                $morehtmlref.='<input type="hidden" name="token" value="'.newToken().'">';
	                $morehtmlref.=$formproject->select_projects($object->socid, $object->fk_project, 'projectid', $maxlength, 0, 1, 0, 1, 0, 0, '', 1);
	                $morehtmlref.='<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
	                $morehtmlref.='</form>';
	            } else {
	                $morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'none', 0, 0, 0, 1);
	            }
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
	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_view.tpl.php';

	// Other attributes. Fields from hook formObjectOptions and Extrafields.
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

	print '</table>';
	print '</div>';
	print '</div>';

	print '<div class="clearboth"></div>';

	print dol_get_fiche_end();


	// Buttons for actions
	if ($action == 'record') {
		print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="action" value="update">';
		print '<input type="hidden" name="id" value="'.$object->id.'">';
		if ($backtopage) print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';

		print '<div class="center">';
		print '<span class="opacitymedium">'.$langs->trans("InventoryDesc").'</span><br>';
		print '<input type="submit" class="button button-save" name="save" value="'.$langs->trans("Save").'">';
		print ' &nbsp; ';
		print '<input type="submit" class="button button-cancel" name="cancel" value="'.$langs->trans("Cancel").'">';
		print '</div>';
		print '<br>';
		print '</form>';
	} else {
		print '<div class="tabsAction">'."\n";
		$parameters = array();
		$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

		if (empty($reshook))
		{
			if ($object->status == Inventory::STATUS_DRAFT)
			{
				if ($permissiontoadd)
				{
					print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=confirm_validate&confirm=yes">'.$langs->trans("Validate").' ('.$langs->trans("Start").')</a>'."\n";
				} else {
					print '<a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans('Validate').' ('.$langs->trans("Start").')</a>'."\n";
				}
			}

			if ($object->status == Inventory::STATUS_VALIDATED)
			{
				if ($permissiontoadd)
				{
					/*
    				if (!empty($conf->barcode->enabled)) {
    					print '<a href="#" class="butAction">'.$langs->trans("UpdateByScaningProductBarcode").'</a>';
    				}
    				if (!empty($conf->productbatch->enabled)) {
    					print '<a href="#" class="butAction">'.$langs->trans('UpdateByScaningLot').'</a>';
    				}*/
					if (!empty($conf->barcode->enabled) || !empty($conf->productbatch->enabled)) {
						print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=updatebyscaning" class="butAction">'.$langs->trans("UpdateByScaning").'</a>';
					}
				} else {
					print '<a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans("Save").'</a>'."\n";
				}
			}

			if ($object->status == Inventory::STATUS_VALIDATED)
			{
				if ($permissiontoadd)
				{
					print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=record">'.$langs->trans("Finish").'</a>'."\n";
				} else {
					print '<a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans('Finish').'</a>'."\n";
				}
			}

			/*if ($object->status == Inventory::STATUS_VALIDATED)
    		{
	    		if ($permissiontoadd)
	    		{
	    			print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=edit">'.$langs->trans("RecordVerb").'</a>'."\n";
	    		}
	    		else
	    		{
	    			print '<a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans('RecordVerb').'</a>'."\n";
	    		}
    		}*/
		}
		print '</div>'."\n";
	}

	if ($action == 'updatebyscaning') {
		print '<div class="div-for-modal">';

		print 'TODO';

		print '</div>';
	}


	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="updateinventorylines">';
	print '<input type="hidden" name="id" value="'.$object->id.'">';
	if ($backtopage) print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';

	print '<div class="fichecenter">';
	//print '<div class="fichehalfleft">';
	print '<div class="clearboth"></div>';

	//print load_fiche_titre($langs->trans('Consumption'), '', '');

	print '<div class="div-table-responsive-no-min">';
	print '<table id="tablelines" class="noborder noshadow centpercent">';

	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("Warehouse").'</td>';
	print '<td>'.$langs->trans("Product").'</td>';
	if ($conf->productbatch->enabled) {
		print '<td>';
		print $langs->trans("Batch");
		print '</td>';
	}
	print '<td class="right">'.$langs->trans("ExpectedQty").'</td>';
	print '<td class="center">';
	print $form->textwithpicto($langs->trans("RealQty"), $langs->trans("InventoryRealQtyHelp"));
	print '</td>';
	// Actions
	print '<td class="center">';
	print '</td>';
	print '</tr>';

	// Line to add a new line in inventory
	//if ($action == 'addline') {
	if ($object->status == $object::STATUS_VALIDATED) {
		print '<tr>';
		print '<td>';
		print $formproduct->selectWarehouses((GETPOSTISSET('fk_warehouse') ? GETPOST('fk_warehouse', 'int') : $object->fk_warehouse), 'fk_warehouse', 'warehouseopen', 1, 0, 0, '', 0, 0, array(), 'maxwidth300');
		print '</td>';
		print '<td>';
		print $form->select_produits((GETPOSTISSET('fk_product') ? GETPOST('fk_product', 'int') : $object->fk_product), 'fk_product', '', 0, 0, 1, 2, '', 0, null, 0, '1', 0, 'maxwidth300');
		print '</td>';
		if ($conf->productbatch->enabled) {
			print '<td>';
			print '<input type="text" name="batch" class="maxwidth100" value="'.(GETPOSTISSET('batch') ? GETPOST('batch') : '').'">';
			print '</td>';
		}
		print '<td class="right"></td>';
		print '<td class="center">';
		print '<input type="submit" class="button paddingrightonly" name="addline" value="'.$langs->trans("Add").'">';
		//print '<input type="submit" class="button paddingrightonly button-cancel" name="canceladdline" value="'.$langs->trans("Cancel").'">';
		print '</td>';
		// Actions
		print '<td class="center">';
		print '</td>';
		print '</tr>';
	}

	// Request to show lines of inventory (prefilled during creation)
	$sql = 'SELECT id.rowid, id.datec as date_creation, id.tms as date_modification, id.fk_inventory, id.fk_warehouse,';
	$sql .= ' id.fk_product, id.batch, id.qty_stock, id.qty_view, id.qty_regulated';
	$sql .= ' FROM '.MAIN_DB_PREFIX.'inventorydet as id';
	$sql .= ' WHERE id.fk_inventory = '.$object->id;

	$cacheOfProducts = array();
	$cacheOfWarehouses = array();

	//$sql = '';
	$resql = $db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);

		$i = 0;
		$totalarray = array();
		while ($i < $num)
		{
			$obj = $db->fetch_object($resql);

			if (is_object($cacheOfWarehouses[$obj->fk_warehouse])) {
				$warehouse_static = $cacheOfWarehouses[$obj->fk_warehouse];
			} else {
				$warehouse_static = new Entrepot($db);
				$warehouse_static->fetch($obj->fk_warehouse);

				$cacheOfWarehouses[$warehouse_static->id] = $warehouse_static;
			}

			if (is_object($cacheOfProducts[$obj->fk_product])) {
				$product_static = $cacheOfProducts[$obj->fk_product];
			} else {
				$product_static = new Product($db);
				$product_static->fetch($obj->fk_product);

				$option = 'nobatch';
				$option .= ',novirtual';
				$product_static->load_stock($option); // Load stock_reel + stock_warehouse. This can also call load_virtual_stock()

				$cacheOfProducts[$product_static->id] = $product_static;
			}

			print '<tr class="oddeven">';
			print '<td>';
			print $warehouse_static->getNomUrl(1);
			print '</td>';
			print '<td>';
			print $product_static->getNomUrl(1);
			print '</td>';

			if ($conf->productbatch->enabled) {
				print '<td>';
				print $obj->batch;
				print '</td>';
			}

			print '<td class="right">';
			print 'TODO';
			print '</td>';
			print '<td class="center">';
			print '<input type="text" class="maxwidth75" name="id_'.$obj->rowid.' value="'.GETPOST("id_".$obj->rowid).'">';
			print '</td>';
			print '<td class="right">';
			print '<a class="reposition" href="'.DOL_URL_ROOT.'/product/inventory/inventory.php?id='.$object->id.'&lineid='.$obj->rowid.'&action=deleteline&token='.newToken().'">'.img_delete().'</a>';
			print '</td>';

			print '</tr>';

			$i++;
		}
	} else {
		dol_print_error($db);
	}

	print '</table>';

	// Save
	if ($object->status == $object::STATUS_VALIDATED) {
		print '<div class="center">';
		print '<input type="submit" class="button button-save" name="save" value="'.$langs->trans("Save").'">';
		print '</div>';
	}

	print '</div>';

	print '</div>';

	print '</form>';
}

// End of page
llxFooter();
$db->close();
