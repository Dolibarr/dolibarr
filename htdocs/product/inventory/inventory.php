<?php
/* Copyright (C) 2019 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
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
 *		\brief      Tab to enter counting
 */

// Load Dolibarr environment
require '../../main.inc.php';
include_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
include_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
include_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
include_once DOL_DOCUMENT_ROOT.'/product/inventory/class/inventory.class.php';
include_once DOL_DOCUMENT_ROOT.'/product/inventory/lib/inventory.lib.php';
include_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';
include_once DOL_DOCUMENT_ROOT.'/product/stock/class/productlot.class.php';

// Load translation files required by the page
$langs->loadLangs(array("stocks", "other", "productbatch"));

// Get parameters
$id = GETPOSTINT('id');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$cancel = GETPOST('cancel', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'inventorycard'; // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha');
$listoffset = GETPOST('listoffset', 'alpha');
$limit = GETPOSTINT('limit') > 0 ? GETPOSTINT('limit') : $conf->liste_limit;
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
if (empty($page) || $page == -1) {
	$page = 0;
}
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

$fk_warehouse = GETPOSTINT('fk_warehouse');
$fk_product = GETPOSTINT('fk_product');
$lineid = GETPOSTINT('lineid');
$batch = GETPOST('batch', 'alphanohtml');
$totalExpectedValuation = 0;
$totalRealValuation = 0;
$hookmanager->initHooks(array('inventorycard')); // Note that conf->hooks_modules contains array
if (!getDolGlobalString('MAIN_USE_ADVANCED_PERMS')) {
	$result = restrictedArea($user, 'stock', $id);
} else {
	$result = restrictedArea($user, 'stock', $id, '', 'inventory_advance');
}

// Initialize a technical objects
$object = new Inventory($db);
$extrafields = new ExtraFields($db);
$diroutputmassaction = $conf->stock->dir_output.'/temp/massgeneration/'.$user->id;


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

// Security check - Protection if external user
//if ($user->socid > 0) accessforbidden();
//if ($user->socid > 0) $socid = $user->socid;
//$result = restrictedArea($user, 'mymodule', $id);

//Parameters Page
$paramwithsearch = '';
if ($limit > 0 && $limit != $conf->liste_limit) {
	$paramwithsearch .= '&limit='.((int) $limit);
}


if (!getDolGlobalString('MAIN_USE_ADVANCED_PERMS')) {
	$permissiontoadd = $user->hasRight('stock', 'creer');
	$permissiontodelete = $user->hasRight('stock', 'supprimer');
} else {
	$permissiontoadd = $user->hasRight('stock', 'inventory_advance', 'write');
	$permissiontodelete = $user->hasRight('stock', 'inventory_advance', 'write');
}

$now = dol_now();



/*
 * Actions
 */

if ($cancel) {
	$action = '';
}


$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	$error = 0;

	if ($action == 'cancel_record' && $permissiontoadd) {
		$object->setCanceled($user);
	}

	// Close inventory by recording the stock movements
	if ($action == 'update' && $user->hasRight('stock', 'mouvement', 'creer') && $object->status == $object::STATUS_VALIDATED) {
		$stockmovment = new MouvementStock($db);
		$stockmovment->setOrigin($object->element, $object->id);

		$cacheOfProducts = array();

		$db->begin();

		$sql = 'SELECT id.rowid, id.datec as date_creation, id.tms as date_modification, id.fk_inventory, id.fk_warehouse,';
		$sql .= ' id.fk_product, id.batch, id.qty_stock, id.qty_view, id.qty_regulated, id.pmp_real';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'inventorydet as id';
		$sql .= ' WHERE id.fk_inventory = '.((int) $object->id);
		$sql .= ' ORDER BY id.rowid';

		$resql = $db->query($sql);
		if ($resql) {
			$num = $db->num_rows($resql);
			$i = 0;
			$totalarray = array();
			$option = '';

			while ($i < $num) {
				$line = $db->fetch_object($resql);

				$qty_stock = $line->qty_stock;
				$qty_view = $line->qty_view;		// The quantity viewed by inventorier, the qty we target


				// Load real stock we have now.
				if (isset($cacheOfProducts[$line->fk_product])) {
					$product_static = $cacheOfProducts[$line->fk_product];
				} else {
					$product_static = new Product($db);
					$result = $product_static->fetch($line->fk_product, '', '', '', 1, 1, 1);

					//$option = 'nobatch';
					$option .= ',novirtual';
					$product_static->load_stock($option); // Load stock_reel + stock_warehouse.

					$cacheOfProducts[$product_static->id] = $product_static;
				}

				// Get the real quantity in stock now, but before the stock move for inventory.
				$realqtynow = $product_static->stock_warehouse[$line->fk_warehouse]->real;
				if (isModEnabled('productbatch') && $product_static->hasbatch()) {
					$realqtynow = $product_static->stock_warehouse[$line->fk_warehouse]->detail_batch[$line->batch]->qty;
				}

				if (!is_null($qty_view)) {
					$stock_movement_qty = price2num($qty_view - $realqtynow, 'MS');
					//print "Process inventory line ".$line->rowid." product=".$product_static->id." realqty=".$realqtynow." qty_stock=".$qty_stock." qty_view=".$qty_view." warehouse=".$line->fk_warehouse." qty to move=".$stock_movement_qty."<br>\n";

					if ($stock_movement_qty != 0) {
						if ($stock_movement_qty < 0) {
							$movement_type = 1;
						} else {
							$movement_type = 0;
						}

						$datemovement = '';
						//$inventorycode = 'INV'.$object->id;
						$inventorycode = 'INV-'.$object->ref;
						$price = 0;
						if (!empty($line->pmp_real) && getDolGlobalString('INVENTORY_MANAGE_REAL_PMP')) {
							$price = $line->pmp_real;
						}

						$idstockmove = $stockmovment->_create($user, $line->fk_product, $line->fk_warehouse, $stock_movement_qty, $movement_type, $price, $langs->trans('LabelOfInventoryMovemement', $object->ref), $inventorycode, $datemovement, '', '', $line->batch);
						if ($idstockmove < 0) {
							$error++;
							setEventMessages($stockmovment->error, $stockmovment->errors, 'errors');
							break;
						}

						// Update line with id of stock movement (and the start quantity if it has changed this last recording)
						$sqlupdate = "UPDATE ".MAIN_DB_PREFIX."inventorydet";
						$sqlupdate .= " SET fk_movement = ".((int) $idstockmove);
						if ($qty_stock != $realqtynow) {
							$sqlupdate .= ", qty_stock = ".((float) $realqtynow);
						}
						$sqlupdate .= " WHERE rowid = ".((int) $line->rowid);
						$resqlupdate = $db->query($sqlupdate);
						if (! $resqlupdate) {
							$error++;
							setEventMessages($db->lasterror(), null, 'errors');
							break;
						}
					}

					if (!empty($line->pmp_real) && getDolGlobalString('INVENTORY_MANAGE_REAL_PMP')) {
						$sqlpmp = 'UPDATE '.MAIN_DB_PREFIX.'product SET pmp = '.((float) $line->pmp_real).' WHERE rowid = '.((int) $line->fk_product);
						$resqlpmp = $db->query($sqlpmp);
						if (! $resqlpmp) {
							$error++;
							setEventMessages($db->lasterror(), null, 'errors');
							break;
						}
						if (getDolGlobalString('MAIN_PRODUCT_PERENTITY_SHARED')) {
							$sqlpmp = 'UPDATE '.MAIN_DB_PREFIX.'product_perentity SET pmp = '.((float) $line->pmp_real).' WHERE fk_product = '.((int) $line->fk_product).' AND entity='.$conf->entity;
							$resqlpmp = $db->query($sqlpmp);
							if (! $resqlpmp) {
								$error++;
								setEventMessages($db->lasterror(), null, 'errors');
								break;
							}
						}
					}
				}
				$i++;
			}

			if (!$error) {
				$object->setRecorded($user);
			}
		} else {
			setEventMessages($db->lasterror, null, 'errors');
			$error++;
		}

		if (! $error) {
			$db->commit();
		} else {
			$db->rollback();
		}
	}

	// Save quantity found during inventory (when we click on Save button on inventory page)
	if ($action == 'updateinventorylines' && $permissiontoadd) {
		$sql = 'SELECT id.rowid, id.datec as date_creation, id.tms as date_modification, id.fk_inventory, id.fk_warehouse,';
		$sql .= ' id.fk_product, id.batch, id.qty_stock, id.qty_view, id.qty_regulated';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'inventorydet as id';
		$sql .= ' WHERE id.fk_inventory = '.((int) $object->id);
		$sql .= $db->order('id.rowid', 'ASC');
		$sql .= $db->plimit($limit, $offset);

		$db->begin();

		$resql = $db->query($sql);
		if ($resql) {
			$num = $db->num_rows($resql);
			$i = 0;
			$totalarray = array();
			$inventoryline = new InventoryLine($db);

			while ($i < $num) {
				$line = $db->fetch_object($resql);
				$lineid = $line->rowid;

				$result = 0;
				$resultupdate = 0;

				if (GETPOST("id_".$lineid, 'alpha') != '') {		// If a value was set ('0' or something else)
					$qtytoupdate = (float) price2num(GETPOST("id_".$lineid, 'alpha'), 'MS');
					$result = $inventoryline->fetch($lineid);
					if ($qtytoupdate < 0) {
						$result = -1;
						setEventMessages($langs->trans("FieldCannotBeNegative", $langs->transnoentitiesnoconv("RealQty")), null, 'errors');
					}
					if ($result > 0) {
						$inventoryline->qty_stock = (float) price2num(GETPOST('stock_qty_'.$lineid, 'alpha'), 'MS');	// The new value that was set in as hidden field
						$inventoryline->qty_view = $qtytoupdate;	// The new value we want
						$inventoryline->pmp_real = price2num(GETPOST('realpmp_'.$lineid, 'alpha'), 'MS');
						$inventoryline->pmp_expected = price2num(GETPOST('expectedpmp_'.$lineid, 'alpha'), 'MS');
						$resultupdate = $inventoryline->update($user);
					}
				} elseif (GETPOSTISSET('id_' . $lineid)) {
					// Delete record
					$result = $inventoryline->fetch($lineid);
					if ($result > 0) {
						$inventoryline->qty_view = null;			// The new value we want
						$inventoryline->pmp_real = price2num(GETPOST('realpmp_'.$lineid, 'alpha'), 'MS');
						$inventoryline->pmp_expected = price2num(GETPOST('expectedpmp_'.$lineid, 'alpha'), 'MS');
						$resultupdate = $inventoryline->update($user);
					}
				}

				if ($result < 0 || $resultupdate < 0) {
					$error++;
				}

				$i++;
			}
		}

		// Update line with id of stock movement (and the start quantity if it has changed this last recording)
		if (! $error) {
			$sqlupdate = "UPDATE ".MAIN_DB_PREFIX."inventory";
			$sqlupdate .= " SET fk_user_modif = ".((int) $user->id);
			$sqlupdate .= " WHERE rowid = ".((int) $object->id);
			$resqlupdate = $db->query($sqlupdate);
			if (! $resqlupdate) {
				$error++;
				setEventMessages($db->lasterror(), null, 'errors');
			}
		}

		if (!$error) {
			$db->commit();
		} else {
			$db->rollback();
		}
	}

	$backurlforlist = DOL_URL_ROOT.'/product/inventory/list.php';
	$backtopage = DOL_URL_ROOT.'/product/inventory/inventory.php?id='.$object->id.'&page='.$page.$paramwithsearch;

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
		$qty = (GETPOST('qtytoadd') != '' ? ((float) price2num(GETPOST('qtytoadd'), 'MS')) : null);
		if ($fk_warehouse <= 0) {
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Warehouse")), null, 'errors');
		}
		if ($fk_product <= 0) {
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Product")), null, 'errors');
		}
		if (price2num(GETPOST('qtytoadd'), 'MS') < 0) {
			$error++;
			setEventMessages($langs->trans("FieldCannotBeNegative", $langs->transnoentitiesnoconv("RealQty")), null, 'errors');
		}
		if (!$error && isModEnabled('productbatch')) {
			$tmpproduct = new Product($db);
			$result = $tmpproduct->fetch($fk_product);

			if (empty($error) && $tmpproduct->status_batch > 0 && empty($batch)) {
				$error++;
				$langs->load("errors");
				setEventMessages($langs->trans("ErrorProductNeedBatchNumber", $tmpproduct->ref), null, 'errors');
			}
			if (empty($error) && $tmpproduct->status_batch == 2 && !empty($batch) && $qty > 1) {
				$error++;
				$langs->load("errors");
				setEventMessages($langs->trans("TooManyQtyForSerialNumber", $tmpproduct->ref, $batch), null, 'errors');
			}
			if (empty($error) && empty($tmpproduct->status_batch) && !empty($batch)) {
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
			$tmp->qty_view = $qty;

			$result = $tmp->create($user);
			if ($result < 0) {
				if ($db->lasterrno() == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
					$langs->load("errors");
					setEventMessages($langs->trans("ErrorRecordAlreadyExists"), null, 'errors');
				} else {
					dol_print_error($db, $tmp->error, $tmp->errors);
				}
			} else {
				// Clear var
				$_POST['batch'] = '';		// TODO Replace this with a var
				$_POST['qtytoadd'] = '';
			}
		}
	}
}



/*
 * View
 */

$form = new Form($db);
$formproduct = new FormProduct($db);

$help_url = '';

llxHeader('', $langs->trans('Inventory'), $help_url, '', 0, 0, '', '', '', 'mod-product page-inventory_inventory');

// Part to show record
if ($object->id <= 0) {
	dol_print_error(null, 'Bad value for object id');
	exit;
}


$res = $object->fetch_optionals();

$head = inventoryPrepareHead($object);
print dol_get_fiche_head($head, 'inventory', $langs->trans("Inventory"), -1, 'stock');

$formconfirm = '';

// Confirmation to delete
if ($action == 'delete') {
	$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('DeleteInventory'), $langs->trans('ConfirmDeleteOrder'), 'confirm_delete', '', 0, 1);
}
// Confirmation to delete line
if ($action == 'deleteline') {
	$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&lineid='.$lineid.'&page='.$page.$paramwithsearch, $langs->trans('DeleteLine'), $langs->trans('ConfirmDeleteLine'), 'confirm_deleteline', '', 0, 1);
}

// Clone confirmation
if ($action == 'clone') {
	// Create an array for form
	$formquestion = array();
	$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ToClone'), $langs->trans('ConfirmCloneMyObject', $object->ref), 'confirm_clone', $formquestion, 'yes', 1);
}

// Confirmation to close
if ($action == 'record') {
	$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&page='.$page.$paramwithsearch, $langs->trans('Close'), $langs->trans('ConfirmFinish'), 'update', '', 0, 1);
	$action = 'view';
}

// Confirmation to close
if ($action == 'confirm_cancel') {
	$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('Cancel'), $langs->trans('ConfirmCancel'), 'cancel_record', '', 0, 1);
	$action = 'view';
}

if ($action == 'validate') {
	$form = new Form($db);
	$formquestion = '';
	if (getDolGlobalInt('INVENTORY_INCLUDE_SUB_WAREHOUSE') && !empty($object->fk_warehouse)) {
		$formquestion = array(
			array('type' => 'checkbox', 'name' => 'include_sub_warehouse', 'label' => $langs->trans("IncludeSubWarehouse"), 'value' => 1, 'size' => '10'),
		);
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ValidateInventory'), $langs->trans('IncludeSubWarehouseExplanation'), 'confirm_validate', $formquestion, '', 1);
	}
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
$linkback = '<a href="'.DOL_URL_ROOT.'/product/inventory/list.php">'.$langs->trans("BackToList").'</a>';

$morehtmlref = '<div class="refidno">';
/*
// Ref bis
$morehtmlref.=$form->editfieldkey("RefBis", 'ref_client', $object->ref_client, $object, $user->rights->inventory->creer, 'string', '', 0, 1);
$morehtmlref.=$form->editfieldval("RefBis", 'ref_client', $object->ref_client, $object, $user->rights->inventory->creer, 'string', '', null, null, '', 1);
// Thirdparty
$morehtmlref.='<br>'.$langs->trans('ThirdParty') . ' : ' . $soc->getNomUrl(1);
// Project
if (isModEnabled('project'))
{
	$langs->load("projects");
	$morehtmlref.='<br>'.$langs->trans('Project') . ' ';
	if ($user->rights->inventory->creer)
	{
		if ($action != 'classify')
		{
			$morehtmlref.='<a class="editfielda" href="' . $_SERVER['PHP_SELF'] . '?action=classify&token='.newToken().'&id=' . $object->id . '">' . img_edit($langs->transnoentitiesnoconv('SetProject')) . '</a> : ';
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
		if (!empty($object->fk_project)) {
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

//print '<tr><td class="titlefield fieldname_invcode">'.$langs->trans("InventoryCode").'</td><td>INV'.$object->id.'</td></tr>';

print '</table>';
print '</div>';
print '</div>';

print '<div class="clearboth"></div>';

print dol_get_fiche_end();

print '<form id="formrecord" name="formrecord" method="POST" action="'.$_SERVER["PHP_SELF"].'?page='.$page.'&id='.$object->id.'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="updateinventorylines">';
print '<input type="hidden" name="id" value="'.$object->id.'">';
if ($backtopage) {
	print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
}


// Buttons for actions
if ($action != 'record') {
	print '<div class="tabsAction">'."\n";
	$parameters = array();
	$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	if ($reshook < 0) {
		setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
	}

	if (empty($reshook)) {
		if ($object->status == Inventory::STATUS_DRAFT) {
			if ($permissiontoadd) {
				if (getDolGlobalInt('INVENTORY_INCLUDE_SUB_WAREHOUSE') && !empty($object->fk_warehouse)) {
					print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=validate&token='.newToken().'">'.$langs->trans("Validate").' ('.$langs->trans("Start").')</a>';
				} else {
					print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=confirm_validate&confirm=yes&token='.newToken().'">'.$langs->trans("Validate").' ('.$langs->trans("Start").')</a>';
				}
			} else {
				print '<a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans('Validate').' ('.$langs->trans("Start").')</a>'."\n";
			}
		}

		// Save
		if ($object->status == $object::STATUS_VALIDATED) {
			if ($permissiontoadd) {
				print '<a class="butAction classfortooltip" id="idbuttonmakemovementandclose" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=record&page='.$page.$paramwithsearch.'&token='.newToken().'" title="'.dol_escape_htmltag($langs->trans("MakeMovementsAndClose")).'">'.$langs->trans("MakeMovementsAndClose").'</a>'."\n";
			} else {
				print '<a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans('MakeMovementsAndClose').'</a>'."\n";
			}

			if ($permissiontoadd) {
				print '<a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=confirm_cancel&page='.$page.$paramwithsearch.'&token='.newToken().'">'.$langs->trans("Cancel").'</a>'."\n";
			}
		}
	}
	print '</div>'."\n";

	if ($object->status != Inventory::STATUS_DRAFT && $object->status != Inventory::STATUS_VALIDATED) {
		print '<br><br>';
	}
}



if ($object->status == Inventory::STATUS_VALIDATED) {
	print '<center>';
	if (!empty($conf->use_javascript_ajax)) {
		if ($permissiontoadd) {
			// Link to launch scan tool
			if (isModEnabled('barcode') || isModEnabled('productbatch')) {
				print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=updatebyscaning&token='.currentToken().'" class="marginrightonly paddingright marginleftonly paddingleft">'.img_picto('', 'barcode', 'class="paddingrightonly"').$langs->trans("UpdateByScaning").'</a>';
			}

			// Link to autofill
			print '<a id="fillwithexpected" class="marginrightonly paddingright marginleftonly paddingleft" href="#">'.img_picto('', 'autofill', 'class="paddingrightonly"').$langs->trans('AutofillWithExpected').'</a>';
			print '<script>';
			print '$( document ).ready(function() {';
			print '	$("#fillwithexpected").on("click",function fillWithExpected(){
						$(".expectedqty").each(function(){
							var object = $(this)[0];
							var objecttofill = $("#"+object.id+"_input")[0];
							objecttofill.value = object.innerText;
							jQuery(".realqty").trigger("change");
						})
						console.log("Values filled (after click on fillwithexpected)");
						/* disablebuttonmakemovementandclose(); */
						return false;
			        });';
			print '});';
			print '</script>';

			// Link to reset qty
			print '<a href="#" id="clearqty" class="marginrightonly paddingright marginleftonly paddingleft">'.img_picto('', 'eraser', 'class="paddingrightonly"').$langs->trans("ClearQtys").'</a>';
		} else {
			print '<a class="classfortooltip marginrightonly paddingright marginleftonly paddingleft" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans("Save").'</a>'."\n";
		}
	}
	print '<br>';
	print '<br>';
	print '</center>';
}


// Popup for mass barcode scanning
if ($action == 'updatebyscaning') {
	if ($permissiontoadd) {
		// Output the javascript to manage the scanner tool.
		print '<script>';

		print '
		var duplicatedbatchcode = [];
		var errortab1 = [];
		var errortab2 = [];
		var errortab3 = [];
		var errortab4 = [];

		function barcodescannerjs(){
			console.log("We catch inputs in scanner box");
			jQuery("#scantoolmessage").text();

			var selectaddorreplace = $("select[name=selectaddorreplace]").val();
			var barcodemode = $("input[name=barcodemode]:checked").val();
			var barcodeproductqty = $("input[name=barcodeproductqty]").val();
			var textarea = $("textarea[name=barcodelist]").val();
			var textarray = textarea.split(/[\s,;]+/);
			var tabproduct = [];
			duplicatedbatchcode = [];
			errortab1 = [];
			errortab2 = [];
			errortab3 = [];
			errortab4 = [];

			textarray = textarray.filter(function(value){
				return value != "";
			});
			if(textarray.some((element) => element != "")){
				$(".expectedqty").each(function(){
					id = this.id;
					console.log("Analyze the line "+id+" in inventory, barcodemode="+barcodemode);
					warehouse = $("#"+id+"_warehouse").attr(\'data-ref\');
					//console.log(warehouse);
					productbarcode = $("#"+id+"_product").attr(\'data-barcode\');
					//console.log(productbarcode);
					productbatchcode = $("#"+id+"_batch").attr(\'data-batch\');
					//console.log(productbatchcode);

					if (barcodemode != "barcodeforproduct") {
						tabproduct.forEach(product=>{
							console.log("product.Batch="+product.Batch+" productbatchcode="+productbatchcode);
							if(product.Batch != "" && product.Batch == productbatchcode){
								console.log("duplicate batch code found for batch code "+productbatchcode);
								duplicatedbatchcode.push(productbatchcode);
							}
						})
					}
					productinput = $("#"+id+"_input").val();
					if(productinput == ""){
						productinput = 0
					}
					tabproduct.push({\'Id\':id,\'Warehouse\':warehouse,\'Barcode\':productbarcode,\'Batch\':productbatchcode,\'Qty\':productinput,\'fetched\':false});
				});

				console.log("Loop on each record entered in the textarea");
				textarray.forEach(function(element,index){
					console.log("Process record element="+element+" id="+id);
					var verify_batch = false;
					var verify_barcode = false;
					switch(barcodemode){
						case "barcodeforautodetect":
							verify_barcode = barcodeserialforproduct(tabproduct,index,element,barcodeproductqty,selectaddorreplace,"barcode",true);
							verify_batch = barcodeserialforproduct(tabproduct,index,element,barcodeproductqty,selectaddorreplace,"lotserial",true);
							break;
						case "barcodeforproduct":
							verify_barcode = barcodeserialforproduct(tabproduct,index,element,barcodeproductqty,selectaddorreplace,"barcode");
							break;
						case "barcodeforlotserial":
							verify_batch = barcodeserialforproduct(tabproduct,index,element,barcodeproductqty,selectaddorreplace,"lotserial");
							break;
						default:
							alert(\''.dol_escape_js($langs->trans("ErrorWrongBarcodemode")).' "\'+barcodemode+\'"\');
							throw \''.dol_escape_js($langs->trans('ErrorWrongBarcodemode')).' "\'+barcodemode+\'"\';
					}

					if (verify_batch == false && verify_barcode == false) {		/* If the 2 flags are false, not found error */
						errortab2.push(element);
					} else if (verify_batch == true && verify_barcode == true) {		/* If the 2 flags are true, error: we don t know which one to take */
						errortab3.push(element);
					} else if (verify_batch == true) {
						console.log("element="+element);
						console.log(duplicatedbatchcode);
						if (duplicatedbatchcode.includes(element)) {
							errortab1.push(element);
						}
					}
				});

				if (Object.keys(errortab1).length < 1 && Object.keys(errortab2).length < 1 && Object.keys(errortab3).length < 1) {
					tabproduct.forEach(product => {
						if(product.Qty!=0){
							console.log("We change #"+product.Id+"_input to match input in scanner box");
							if(product.hasOwnProperty("reelqty")){
								$.ajax({ url: \''.DOL_URL_ROOT.'/product/inventory/ajax/searchfrombarcode.php\',
									data: { "token":"'.newToken().'", "action":"addnewlineproduct", "fk_entrepot":product.Warehouse, "batch":product.Batch, "fk_inventory":'.dol_escape_js($object->id).', "fk_product":product.fk_product, "reelqty":product.reelqty},
									type: \'POST\',
									async: false,
									success: function(response) {
										response = JSON.parse(response);
										if(response.status == "success"){
											console.log(response.message);
											$("<input type=\'text\' value=\'"+product.Qty+"\' />")
											.attr("id", "id_"+response.id_line+"_input")
											.attr("name", "id_"+response.id_line)
											.appendTo("#formrecord");
										}else{
											console.error(response.message);
										}
									},
									error : function(output) {
										console.error("Error on line creation function");
									},
								});
							} else {
								$("#"+product.Id+"_input").val(product.Qty);
							}
						}
					});
					jQuery("#scantoolmessage").text("'.dol_escape_js($langs->transnoentities("QtyWasAddedToTheScannedBarcode")).'\n");
					/* document.forms["formrecord"].submit(); */
				} else {
					let stringerror = "";
					if (Object.keys(errortab1).length > 0) {
						stringerror += "<br>'.dol_escape_js($langs->transnoentities('ErrorSameBatchNumber')).': ";
						errortab1.forEach(element => {
							stringerror += (element + ", ")
						});
						stringerror = stringerror.slice(0, -2);	/* Remove last ", " */
					}
					if (Object.keys(errortab2).length > 0) {
						stringerror += "<br>'.dol_escape_js($langs->transnoentities('ErrorCantFindCodeInInventory')).': ";
						errortab2.forEach(element => {
							stringerror += (element + ", ")
						});
						stringerror = stringerror.slice(0, -2);	/* Remove last ", " */
					}
					if (Object.keys(errortab3).length > 0) {
						stringerror += "<br>'.dol_escape_js($langs->transnoentities('ErrorCodeScannedIsBothProductAndSerial')).': ";
						errortab3.forEach(element => {
							stringerror += (element + ", ")
						});
						stringerror = stringerror.slice(0, -2);	/* Remove last ", " */
					}
					if (Object.keys(errortab4).length > 0) {
						stringerror += "<br>'.dol_escape_js($langs->transnoentities('ErrorBarcodeNotFoundForProductWarehouse')).': ";
						errortab4.forEach(element => {
							stringerror += (element + ", ")
						});
						stringerror = stringerror.slice(0, -2);	/* Remove last ", " */
					}

					jQuery("#scantoolmessage").html(\''.dol_escape_js($langs->transnoentities("ErrorOnElementsInventory")).'\' + stringerror);
					//alert("'.dol_escape_js($langs->trans("ErrorOnElementsInventory")).' :\n" + stringerror);
				}
			}

		}

		/* This methode is called by parent barcodescannerjs() */
		function barcodeserialforproduct(tabproduct,index,element,barcodeproductqty,selectaddorreplace,mode,autodetect=false){
			BarcodeIsInProduct=0;
			newproductrow=0
			result=false;
			tabproduct.forEach(product => {
				$.ajax({ url: \''.DOL_URL_ROOT.'/product/inventory/ajax/searchfrombarcode.php\',
					data: { "token":"'.newToken().'", "action":"existbarcode", '.(!empty($object->fk_warehouse) ? '"fk_entrepot":'.$object->fk_warehouse.', ' : '').(!empty($object->fk_product) ? '"fk_product":'.$object->fk_product.', ' : '').'"barcode":element, "product":product, "mode":mode},
					type: \'POST\',
					async: false,
					success: function(response) {
						response = JSON.parse(response);
						if (response.status == "success"){
							console.log(response.message);
							if(!newproductrow){
								newproductrow = response.object;
							}
						}else{
							if (mode!="lotserial" && autodetect==false && !errortab4.includes(element)){
								errortab4.push(element);
								console.error(response.message);
							}
						}
					},
					error : function(output) {
					   console.error("Error on barcodeserialforproduct function");
					},
			    });
				console.log("Product "+(index+=1)+": "+element);
				if(mode == "barcode"){
					testonproduct = product.Barcode
				}else if (mode == "lotserial"){
					testonproduct = product.Batch
				}
				if(testonproduct == element){
					if(selectaddorreplace == "add"){
						productqty = parseInt(product.Qty,10);
						product.Qty = productqty + parseInt(barcodeproductqty,10);
					}else if(selectaddorreplace == "replace"){
						if(product.fetched == false){
							product.Qty = barcodeproductqty
							product.fetched=true
						}else{
							productqty = parseInt(product.Qty,10);
							product.Qty = productqty + parseInt(barcodeproductqty,10);
						}
					}
					BarcodeIsInProduct+=1;
				}
			})
			if(BarcodeIsInProduct==0 && newproductrow!=0){
				tabproduct.push({\'Id\':tabproduct.length-1,\'Warehouse\':newproductrow.fk_warehouse,\'Barcode\':mode=="barcode"?element:null,\'Batch\':mode=="lotserial"?element:null,\'Qty\':barcodeproductqty,\'fetched\':true,\'reelqty\':newproductrow.reelqty,\'fk_product\':newproductrow.fk_product,\'mode\':mode});
				result = true;
			}
			if(BarcodeIsInProduct > 0){
				result = true;
			}
			return result;
		}
		';
		print '</script>';
	}
	include DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
	$formother = new FormOther($db);
	print $formother->getHTMLScannerForm("barcodescannerjs", 'all');
}

//Call method to undo changes in real qty
print '<script>';
print 'jQuery(document).ready(function() {
	$("#clearqty").on("click", function() {
		console.log("Clear all values");
		/* disablebuttonmakemovementandclose(); */
		jQuery(".realqty").val("");
		jQuery(".realqty").trigger("change");
		return false;	/* disable submit */
	});
	$(".undochangesqty").on("click", function undochangesqty() {
		console.log("Clear value of inventory line");
		id = this.id;
		id = id.split("_")[1];
		tmpvalue = $("#id_"+id+"_input_tmp").val()
		$("#id_"+id+"_input")[0].value = tmpvalue;
		/* disablebuttonmakemovementandclose(); */
		return false;	/* disable submit */
	});
});';
print '</script>';

print '<div class="fichecenter">';
//print '<div class="fichehalfleft">';
print '<div class="clearboth"></div>';

//print load_fiche_titre($langs->trans('Consumption'), '', '');

print '<div class="div-table-responsive-no-min">';
print '<table id="tablelines" class="noborder noshadow centpercent">';

print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Warehouse").'</td>';
print '<td>'.$langs->trans("Product").'</td>';
if (isModEnabled('productbatch')) {
	print '<td>';
	print $langs->trans("Batch");
	print '</td>';
}
if ($object->status == $object::STATUS_DRAFT || $object->status == $object::STATUS_VALIDATED) {
	// Expected quantity = If inventory is open: Quantity currently in stock (may change if stock movement are done during the inventory)
	print '<td class="right">'.$form->textwithpicto($langs->trans("ExpectedQty"), $langs->trans("QtyCurrentlyKnownInStock")).'</td>';
} else {
	// Expected quantity = If inventory is closed: Quantity we had in stock when we start the inventory.
	print '<td class="right">'.$form->textwithpicto($langs->trans("ExpectedQty"), $langs->trans("QtyInStockWhenInventoryWasValidated")).'</td>';
}
if (getDolGlobalString('INVENTORY_MANAGE_REAL_PMP')) {
	print '<td class="right">'.$langs->trans('PMPExpected').'</td>';
	print '<td class="right">'.$langs->trans('ExpectedValuation').'</td>';
	print '<td class="right">'.$form->textwithpicto($langs->trans("RealQty"), $langs->trans("InventoryRealQtyHelp")).'</td>';
	print '<td class="right">'.$langs->trans('PMPReal').'</td>';
	print '<td class="right">'.$langs->trans('RealValuation').'</td>';
} else {
	print '<td class="right">';
	print $form->textwithpicto($langs->trans("RealQty"), $langs->trans("InventoryRealQtyHelp"));
	print '</td>';
}
if ($object->status == $object::STATUS_DRAFT || $object->status == $object::STATUS_VALIDATED) {
	// Actions or link to stock movement
	print '<td class="center">';
	print '</td>';
} else {
	// Actions or link to stock movement
	print '<td class="right">';
	//print $langs->trans("StockMovement");
	print '</td>';
}
print '</tr>';

// Line to add a new line in inventory
if ($object->status == $object::STATUS_DRAFT || $object->status == $object::STATUS_VALIDATED) {
	print '<tr>';
	print '<td>';
	print $formproduct->selectWarehouses((GETPOSTISSET('fk_warehouse') ? GETPOSTINT('fk_warehouse') : $object->fk_warehouse), 'fk_warehouse', 'warehouseopen', 1, 0, 0, '', 0, 0, array(), 'maxwidth300');
	print '</td>';
	print '<td>';
	print $form->select_produits((GETPOSTISSET('fk_product') ? GETPOSTINT('fk_product') : $object->fk_product), 'fk_product', '', 0, 0, -1, 2, '', 0, null, 0, '1', 0, 'maxwidth300');
	print '</td>';
	if (isModEnabled('productbatch')) {
		print '<td>';
		print '<input type="text" name="batch" class="maxwidth100" value="'.(GETPOSTISSET('batch') ? GETPOST('batch') : '').'">';
		print '</td>';
	}
	print '<td class="right"></td>';
	if (getDolGlobalString('INVENTORY_MANAGE_REAL_PMP')) {
		print '<td class="right">';
		print '</td>';
		print '<td class="right">';
		print '</td>';
		print '<td class="right">';
		print '<input type="text" name="qtytoadd" class="maxwidth75" value="">';
		print '</td>';
		print '<td class="right">';
		print '</td>';
		print '<td class="right">';
		print '</td>';
	} else {
		print '<td class="right">';
		print '<input type="text" name="qtytoadd" class="maxwidth75" value="">';
		print '</td>';
	}
	// Actions
	print '<td class="center">';
	print '<input type="submit" class="button paddingright" name="addline" value="'.$langs->trans("Add").'">';
	print '</td>';
	print '</tr>';
}

// Request to show lines of inventory (prefilled after start/validate step)
$sql = 'SELECT id.rowid, id.datec as date_creation, id.tms as date_modification, id.fk_inventory, id.fk_warehouse,';
$sql .= ' id.fk_product, id.batch, id.qty_stock, id.qty_view, id.qty_regulated, id.fk_movement, id.pmp_real, id.pmp_expected';
$sql .= ' FROM '.MAIN_DB_PREFIX.'inventorydet as id';
$sql .= ' WHERE id.fk_inventory = '.((int) $object->id);
$sql .= $db->order('id.rowid', 'ASC');
$sql .= $db->plimit($limit, $offset);

$cacheOfProducts = array();
$cacheOfWarehouses = array();

//$sql = '';
$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);

	if (!empty($limit != 0) || $num > $limit || $page) {
		print_fleche_navigation($page, $_SERVER["PHP_SELF"], '&id='.$object->id.$paramwithsearch, ($num >= $limit ? 1 : 0), '<li class="pagination"><span>' . $langs->trans("Page") . ' ' . ($page + 1) . '</span></li>', '', $limit);
	}

	$i = 0;
	$hasinput = false;
	$totalarray = array();
	while ($i < $num) {
		$obj = $db->fetch_object($resql);

		if (isset($cacheOfWarehouses[$obj->fk_warehouse])) {
			$warehouse_static = $cacheOfWarehouses[$obj->fk_warehouse];
		} else {
			$warehouse_static = new Entrepot($db);
			$warehouse_static->fetch($obj->fk_warehouse);

			$cacheOfWarehouses[$warehouse_static->id] = $warehouse_static;
		}

		// Load real stock we have now
		$option = '';
		if (isset($cacheOfProducts[$obj->fk_product])) {
			$product_static = $cacheOfProducts[$obj->fk_product];
		} else {
			$product_static = new Product($db);
			$result = $product_static->fetch($obj->fk_product, '', '', '', 1, 1, 1);

			//$option = 'nobatch';
			$option .= ',novirtual';
			$product_static->load_stock($option); // Load stock_reel + stock_warehouse.

			$cacheOfProducts[$product_static->id] = $product_static;
		}

		print '<tr class="oddeven">';
		print '<td id="id_'.$obj->rowid.'_warehouse" data-ref="'.dol_escape_htmltag($warehouse_static->ref).'">';
		print $warehouse_static->getNomUrl(1);
		print '</td>';
		print '<td id="id_'.$obj->rowid.'_product" data-ref="'.dol_escape_htmltag($product_static->ref).'" data-barcode="'.dol_escape_htmltag($product_static->barcode).'">';
		print $product_static->getNomUrl(1).' - '.$product_static->label;
		print '</td>';

		if (isModEnabled('productbatch')) {
			print '<td id="id_'.$obj->rowid.'_batch" data-batch="'.dol_escape_htmltag($obj->batch).'">';
			$batch_static = new Productlot($db);
			// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
			$res = $batch_static->fetch(0, $product_static->id, $obj->batch);
			if ($res) {
				print $batch_static->getNomUrl(1);
			} else {
				print dol_escape_htmltag($obj->batch);
			}
			print '</td>';
		}

		// Expected quantity = If inventory is open: Quantity currently in stock (may change if stock movement are done during the inventory)
		// Expected quantity = If inventory is closed: Quantity we had in stock when we start the inventory.
		print '<td class="right expectedqty" id="id_'.$obj->rowid.'" title="Stock viewed at last update: '.$obj->qty_stock.'">';
		$valuetoshow = $obj->qty_stock;
		// For inventory not yet close, we overwrite with the real value in stock now
		if ($object->status == $object::STATUS_DRAFT || $object->status == $object::STATUS_VALIDATED) {
			if (isModEnabled('productbatch') && $product_static->hasbatch()) {
				$valuetoshow = $product_static->stock_warehouse[$obj->fk_warehouse]->detail_batch[$obj->batch]->qty ?? 0;
			} else {
				$valuetoshow = $product_static->stock_warehouse[$obj->fk_warehouse]->real ?? 0;
			}
		}
		print price2num($valuetoshow, 'MS');
		print '<input type="hidden" name="stock_qty_'.$obj->rowid.'" value="'.$valuetoshow.'">';
		print '</td>';

		// Real quantity
		if ($object->status == $object::STATUS_DRAFT || $object->status == $object::STATUS_VALIDATED) {
			$qty_view = GETPOST("id_".$obj->rowid) && price2num(GETPOST("id_".$obj->rowid), 'MS') >= 0 ? GETPOST("id_".$obj->rowid) : $obj->qty_view;

			//if (!$hasinput && $qty_view !== null && $obj->qty_stock != $qty_view) {
			if ($qty_view != '') {
				$hasinput = true;
			}

			if (getDolGlobalString('INVENTORY_MANAGE_REAL_PMP')) {
				//PMP Expected
				if (!empty($obj->pmp_expected)) {
					$pmp_expected = $obj->pmp_expected;
				} else {
					$pmp_expected = $product_static->pmp;
				}
				$pmp_valuation = $pmp_expected * $valuetoshow;
				print '<td class="right">';
				print price($pmp_expected);
				print '<input type="hidden" name="expectedpmp_'.$obj->rowid.'" value="'.$pmp_expected.'"/>';
				print '</td>';
				print '<td class="right">';
				print price($pmp_valuation);
				print '</td>';

				print '<td class="right">';
				print '<a id="undochangesqty_'.$obj->rowid.'" href="#" class="undochangesqty reposition marginrightonly" title="'.dol_escape_htmltag($langs->trans("Clear")).'">';
				print img_picto('', 'eraser', 'class="opacitymedium"');
				print '</a>';
				print '<input type="text" class="maxwidth50 right realqty" name="id_'.$obj->rowid.'" id="id_'.$obj->rowid.'_input" value="'.$qty_view.'">';
				print '</td>';

				//PMP Real
				print '<td class="right">';
				if (!empty($obj->pmp_real) || (string) $obj->pmp_real === '0') {
					$pmp_real = $obj->pmp_real;
				} else {
					$pmp_real = $product_static->pmp;
				}
				$pmp_valuation_real = $pmp_real * $qty_view;
				print '<input type="text" class="maxwidth75 right realpmp'.$obj->fk_product.'" name="realpmp_'.$obj->rowid.'" id="id_'.$obj->rowid.'_input_pmp" value="'.price2num($pmp_real).'">';
				print '</td>';
				print '<td class="right">';
				print '<input type="text" class="maxwidth75 right realvaluation'.$obj->fk_product.'" name="realvaluation_'.$obj->rowid.'" id="id_'.$obj->rowid.'_input_real_valuation" value="'.$pmp_valuation_real.'">';
				print '</td>';

				$totalExpectedValuation += $pmp_valuation;
				$totalRealValuation += $pmp_valuation_real;
			} else {
				print '<td class="right">';
				print '<a id="undochangesqty_'.$obj->rowid.'" href="#" class="undochangesqty reposition marginrightonly" title="'.dol_escape_htmltag($langs->trans("Clear")).'">';
				print img_picto('', 'eraser', 'class="opacitymedium"');
				print '</a>';
				print '<input type="text" class="maxwidth50 right realqty" name="id_'.$obj->rowid.'" id="id_'.$obj->rowid.'_input" value="'.$qty_view.'">';
				print '</td>';
			}

			// Picto delete line
			print '<td class="right">';
			print '<a class="reposition" href="'.DOL_URL_ROOT.'/product/inventory/inventory.php?id='.$object->id.'&lineid='.$obj->rowid.'&action=deleteline&page='.$page.$paramwithsearch.'&token='.newToken().'">'.img_delete().'</a>';
			$qty_tmp = price2num(GETPOST("id_".$obj->rowid."_input_tmp", 'MS')) >= 0 ? GETPOST("id_".$obj->rowid."_input_tmp") : $qty_view;
			print '<input type="hidden" class="maxwidth50 right realqty" name="id_'.$obj->rowid.'_input_tmp" id="id_'.$obj->rowid.'_input_tmp" value="'.$qty_tmp.'">';
			print '</td>';
		} else {
			if (getDolGlobalString('INVENTORY_MANAGE_REAL_PMP')) {
				//PMP Expected
				if (!empty($obj->pmp_expected)) {
					$pmp_expected = $obj->pmp_expected;
				} else {
					$pmp_expected = $product_static->pmp;
				}
				$pmp_valuation = $pmp_expected * $valuetoshow;
				print '<td class="right">';
				print price($pmp_expected);
				print '</td>';
				print '<td class="right">';
				print price($pmp_valuation);
				print '</td>';

				print '<td class="right nowraponall">';
				print $obj->qty_view;	// qty found
				print '</td>';

				//PMP Real
				print '<td class="right">';
				if (!empty($obj->pmp_real)) {
					$pmp_real = $obj->pmp_real;
				} else {
					$pmp_real = $product_static->pmp;
				}
				$pmp_valuation_real = $pmp_real * $obj->qty_view;
				print price($pmp_real);
				print '</td>';
				print '<td class="right">';
				print price($pmp_valuation_real);
				print '</td>';
				print '<td class="nowraponall right">';

				$totalExpectedValuation += $pmp_valuation;
				$totalRealValuation += $pmp_valuation_real;
			} else {
				print '<td class="right nowraponall">';
				print $obj->qty_view;	// qty found
				print '</td>';
			}
			print '<td>';
			if ($obj->fk_movement > 0) {
				$stockmovment = new MouvementStock($db);
				$stockmovment->fetch($obj->fk_movement);
				print $stockmovment->getNomUrl(1, 'movements');
			}
			print '</td>';
		}
		print '</tr>';

		$i++;
	}
} else {
	dol_print_error($db);
}
if (getDolGlobalString('INVENTORY_MANAGE_REAL_PMP')) {
	print '<tr class="liste_total">';
	print '<td colspan="4">'.$langs->trans("Total").'</td>';
	print '<td class="right" colspan="2">'.price($totalExpectedValuation).'</td>';
	print '<td class="right" id="totalRealValuation" colspan="3">'.price($totalRealValuation).'</td>';
	print '<td></td>';
	print '</tr>';
}
print '</table>';

print '</div>';

if ($object->status == $object::STATUS_VALIDATED) {
	print '<center><input id="submitrecord" type="submit" class="button button-save" name="save" value="'.$langs->trans("Save").'"></center>';
}

print '</div>';


// Call method to disable the button if no qty entered yet for inventory
/*
if ($object->status != $object::STATUS_VALIDATED || !$hasinput) {
	print '<script type="text/javascript">
				jQuery(document).ready(function() {
					console.log("Call disablebuttonmakemovementandclose because status = '.((int) $object->status).' or $hasinput = '.((int) $hasinput).'");
					disablebuttonmakemovementandclose();
				});
			</script>';
}
*/

print '</form>';

print '<script type="text/javascript">
					$(document).ready(function() {

                        $(".paginationnext:last").click(function(e){
                            var form = $("#formrecord");
   							var actionURL = "'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&page='.($page).$paramwithsearch.'";
   							$.ajax({
      					 	url: actionURL,
        					data: form.serialize(),
        					cache: false,
        					success: function(result){
           				 	window.location.href = "'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&page='.($page + 1).$paramwithsearch.'";
    						}});
    					});


                         $(".paginationprevious:last").click(function(e){
                            var form = $("#formrecord");
   							var actionURL = "'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&page='.($page).$paramwithsearch.'";
   							$.ajax({
      					 	url: actionURL,
        					data: form.serialize(),
        					cache: false,
        					success: function(result){
           				 	window.location.href = "'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&page='.($page - 1).$paramwithsearch.'";
       					 	}});
						 });

                          $("#idbuttonmakemovementandclose").click(function(e){
                            var form = $("#formrecord");
   							var actionURL = "'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&page='.($page).$paramwithsearch.'";
   							$.ajax({
      					 	url: actionURL,
        					data: form.serialize(),
        					cache: false,
        					success: function(result){
           				 	window.location.href = "'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&page='.($page - 1).$paramwithsearch.'&action=record";
       					 	}});
						 });
					});
</script>';


if (getDolGlobalString('INVENTORY_MANAGE_REAL_PMP')) {
	?>
<script type="text/javascript">
$('.realqty').on('change', function () {
	let realqty = $(this).closest('tr').find('.realqty').val();
	let inputPmp = $(this).closest('tr').find('input[class*=realpmp]');
	let realpmp = $(inputPmp).val();
	if (!isNaN(realqty) && !isNaN(realpmp)) {
		let realval = realqty * realpmp;
		$(this).closest('tr').find('input[name^=realvaluation]').val(realval.toFixed(2));
	}
	updateTotalValuation();
});

$('input[class*=realpmp]').on('change', function () {
	let inputQtyReal = $(this).closest('tr').find('.realqty');
	let realqty = $(inputQtyReal).val();
	let inputPmp = $(this).closest('tr').find('input[class*=realpmp]');
	console.log(inputPmp);
	let realPmpClassname = $(inputPmp).attr('class').match(/[\w-]*realpmp[\w-]*/g)[0];
	let realpmp = $(inputPmp).val();
	if (!isNaN(realpmp)) {
		$('.'+realPmpClassname).val(realpmp); //For batch case if pmp is changed we change it everywhere it's same product and calc back everything

		if (!isNaN(realqty)) {
			let realval = realqty * realpmp;
			$(this).closest('tr').find('input[name^=realvaluation]').val(realval.toFixed(2));
		}
		$('.realqty').trigger('change');
		updateTotalValuation();
	}
});

$('input[name^=realvaluation]').on('change', function () {
	let inputQtyReal = $(this).closest('tr').find('.realqty');
	let realqty = $(inputQtyReal).val();
	let inputPmp = $(this).closest('tr').find('input[class*=realpmp]');
	let inputRealValuation = $(this).closest('tr').find('input[name^=realvaluation]');
	let realPmpClassname = $(inputPmp).attr('class').match(/[\w-]*realpmp[\w-]*/g)[0];
	let realvaluation = $(inputRealValuation).val();
	if (!isNaN(realvaluation) && !isNaN(realqty) && realvaluation !== '' && realqty !== '' && realqty !== 0) {
		let realpmp = realvaluation / realqty
		$('.'+realPmpClassname).val(realpmp); //For batch case if pmp is changed we change it everywhere it's same product and calc back everything
		$('.realqty').trigger('change');
		updateTotalValuation();
	}
});

function updateTotalValuation() {
	let total = 0;
	$('input[name^=realvaluation]').each(function( index ) {
		let val = $(this).val();
		if(!isNaN(val)) total += parseFloat($(this).val());
	});
	let currencyFractionDigits = new Intl.NumberFormat('fr-FR', {
		style: 'currency',
		currency: 'EUR',
	}).resolvedOptions().maximumFractionDigits;
	$('#totalRealValuation').html(total.toLocaleString('fr-FR', {
		maximumFractionDigits: currencyFractionDigits
	}));
}


</script>
	<?php
}

// End of page
llxFooter();
$db->close();
