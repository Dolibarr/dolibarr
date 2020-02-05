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
include_once DOL_DOCUMENT_ROOT.'/product/inventory/class/inventory.class.php';
include_once DOL_DOCUMENT_ROOT.'/product/inventory/lib/inventory.lib.php';

// Load translation files required by the page
$langs->loadLangs(array("stocks", "other"));

// Get parameters
$id = GETPOST('id', 'int');
$ref        = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$confirm    = GETPOST('confirm', 'alpha');
$cancel     = GETPOST('cancel', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'myobjectcard'; // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha');

if (empty($conf->global->MAIN_USE_ADVANCED_PERMS))
{
	$result = restrictedArea($user, 'stock', $id);
}
else
{
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
$search_all = trim(GETPOST("search_all", 'alpha'));
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
}
else
{
	$permissiontoadd = $user->rights->stock->inventory_advance->write;
	$permissiontodelete = $user->rights->stock->inventory_advance->write;
}


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
	$trackid='myobject'.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';*/
}




/*
 * View
 */

$form = new Form($db);

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
	dol_fiche_head($head, 'inventory', $langs->trans("Inventory"), -1, 'stock');

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

	dol_fiche_end();


	// Buttons for actions
	if ($action == 'edit') {
		print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="action" value="update">';
		print '<input type="hidden" name="id" value="'.$object->id.'">';
		if ($backtopage) print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';

		print '<div class="center">';
		print '<span class="opacitymedium">'.$langs->trans("InventoryDesc").'</span><br>';
		print '<input type="submit" class="button" name="save" value="'.$langs->trans("Save").'">';
		print ' &nbsp; ';
		print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
		print '</div>';
		print '<br>';
	}
	else {
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
    				print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=edit">'.$langs->trans("Edit").'</a>'."\n";
    			}
    			else
    			{
    				print '<a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans('Edit').'</a>'."\n";
    			}
    		}

    		if ($object->status == Inventory::STATUS_DRAFT)
    		{
	        	if ($permissiontoadd)
	    		{
	    			print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=validate">'.$langs->trans("Validate").'</a>'."\n";
	    		}
	    		else
	    		{
	    			print '<a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans('Validate').'</a>'."\n";
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
	print '<td class="right">'.$langs->trans("RecordedQty").'</td>';
	print '<td class="right">'.$langs->trans("RealQty").'</td>';
	print '<td>';
	print '</td>';
	print '</tr>';


	$sql = 'SELECT ps.rowid, ps.fk_entrepot as fk_warehouse, ps.fk_product';
	$sql .= ' FROM '.MAIN_DB_PREFIX.'product_stock as ps, '.MAIN_DB_PREFIX.'product as p, '.MAIN_DB_PREFIX.'entrepot as e';
	$sql .= ' WHERE p.entity IN ('.getEntity('product').')';
	$sql .= ' AND ps.fk_product = p.rowid AND ps.fk_entrepot = e.rowid';
	if (empty($conf->global->STOCK_SUPPORTS_SERVICES)) $sql .= " AND p.fk_product_type = 0";
	if ($object->fk_product > 0) $sql .= ' AND ps.fk_product = '.$object->fk_product;
	if ($object->fk_warehouse > 0) $sql .= ' AND ps.fk_entrepot = '.$object->fk_warehouse;

	$cacheOfProducts = array();
	$cacheOfWarehouses = array();

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
				print '';
				print '</td>';
			}

			print '<td>';
			print '';
			print '</td>';
			print '<td>';
			print '';
			print '</td>';
			print '<td>';
			print '';
			print '</td>';

			print '</tr>';

			$i++;
		}
	} else {
		dol_print_error($db);
	}

	print '</table>';
	print '</div>';

	//print '</div>';
	print '</div>';

	if ($action == 'edit') {
		print '</form>';
	}
}

// End of page
llxFooter();
$db->close();
