<?php
/* Copyright (C) 2017 		Laurent Destailleur  	<eldy@users.sourceforge.net>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
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
 *   	\file       stocktransfer_card.php
 *		\ingroup    stocktransfer
 *		\brief      Page to create/edit/view stocktransfer
 */

// Load Dolibarr environment
require '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/productlot.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/stocktransfer/class/stocktransfer.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/stocktransfer/class/stocktransferline.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/stocktransfer/lib/stocktransfer_stocktransfer.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/stocktransfer/modules_stocktransfer.php';

// Load translation files required by the page
$langs->loadLangs(array("stocks", "other", "productbatch", "companies"));
if (isModEnabled('incoterm')) {
	$langs->load('incoterm');
}


// Get parameters
$id = GETPOSTINT('id');
$ref        = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$confirm    = GETPOST('confirm', 'alpha');
$cancel     = GETPOST('cancel', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : str_replace('_', '', basename(dirname(__FILE__)).basename(__FILE__, '.php')); // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha');					// if not set, a default page will be used
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');	// if not set, $backtopage will be used
$qty = GETPOSTINT('qty');
$fk_product = GETPOSTINT('fk_product');
$fk_warehouse_source = GETPOSTINT('fk_warehouse_source');
$fk_warehouse_destination = GETPOSTINT('fk_warehouse_destination');
$lineid   = GETPOSTINT('lineid');
$label = GETPOST('label', 'alpha');
$batch = GETPOST('batch', 'alpha');
$code_inv = GETPOST('inventorycode', 'alphanohtml');

// Initialize a technical objects
$object = new StockTransfer($db);
$extrafields = new ExtraFields($db);
$diroutputmassaction = $conf->stocktransfer->dir_output.'/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array($object->element.'card', 'globalcard')); // Note that conf->hooks_modules contains array

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// Initialize array of search criteria
$search_all = trim(GETPOST("search_all", 'alpha'));
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


$permissiontoread = $user->hasRight('stocktransfer', 'stocktransfer', 'read');
$permissiontoadd = $user->hasRight('stocktransfer', 'stocktransfer', 'write'); // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
$permissionnote = $user->hasRight('stocktransfer', 'stocktransfer', 'write'); // Used by the include of actions_setnotes.inc.php
$permissiontodelete = $user->rights->stocktransfer->stocktransfer->delete || ($permissiontoadd && isset($object->status) && $object->status < $object::STATUS_TRANSFERED);
$permissiondellink = $user->hasRight('stocktransfer', 'stocktransfer', 'write'); // Used by the include of actions_dellink.inc.php
$upload_dir = $conf->stocktransfer->multidir_output[isset($object->entity) ? $object->entity : 1];

// Security check - Protection if external user
//if ($user->socid > 0) accessforbidden();
//if ($user->socid > 0) $socid = $user->socid;
//$isdraft = (($object->statut == $object::STATUS_DRAFT) ? 1 : 0);
//$result = restrictedArea($user, 'stocktransfer', $object->id, '', '', 'fk_soc', 'rowid', $isdraft);

if (!$permissiontoread || ($action === 'create' && !$permissiontoadd)) {
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

	$backurlforlist = dol_buildpath('/product/stock/stocktransfer/stocktransfer_list.php', 1);

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) {
				$backtopage = $backurlforlist;
			} else {
				$backtopage = dol_buildpath('/product/stock/stocktransfer/stocktransfer_card.php', 1).'?id='.($id > 0 ? $id : '__ID__');
			}
		}
	}

	$triggermodname = 'STOCKTRANSFER_STOCKTRANSFER_MODIFY'; // Name of trigger action code to execute when we modify record

	// Actions cancel, add, update, update_extras, confirm_validate, confirm_delete, confirm_deleteline, confirm_clone, confirm_close, confirm_setdraft, confirm_reopen
	include DOL_DOCUMENT_ROOT.'/core/actions_addupdatedelete.inc.php';

	// On remet cette lecture de permission ici car nécessaire d'avoir le nouveau statut de l'objet après toute action exécutée dessus (après incrémentation par example, le bouton supprimer doit disparaître)
	$permissiontodelete = $user->rights->stocktransfer->stocktransfer->delete || ($permissiontoadd && isset($object->status) && $object->status == $object::STATUS_DRAFT);

	// Actions when linking object each other
	include DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php';

	// Actions when printing a doc from card
	include DOL_DOCUMENT_ROOT.'/core/actions_printing.inc.php';

	// Action to move up and down lines of object
	include DOL_DOCUMENT_ROOT.'/core/actions_lineupdown.inc.php';

	// Action to build doc
	include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';

	if ($action == 'set_thirdparty' && $permissiontoadd) {
		$object->setValueFrom('fk_soc', GETPOSTINT('fk_soc'), '', '', 'date', '', $user, $triggermodname);
	}
	if ($action == 'classin' && $permissiontoadd) {
		$object->setProject(GETPOSTINT('projectid'));
	}

	if ($action == 'addline' && $permissiontoadd) {
		if ($qty <= 0) {
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Qty")), null, 'errors');
			$action = 'view';
		}

		if ($fk_warehouse_source <= 0) {
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("WarehouseSource")), null, 'errors');
			$action = 'view';
		}

		if ($fk_warehouse_destination <= 0) {
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("WarehouseTarget")), null, 'errors');
			$action = 'view';
		}

		$prod = new Product($db);
		$prod->fetch($fk_product);
		if ($prod->hasbatch()) {
			if (empty($batch)) {
				$error++;
				$langs->load("errors");
				setEventMessages($langs->transnoentities("ErrorTryToMakeMoveOnProductRequiringBatchData", $prod->ref), null, 'errors');
			}
		} else {
			if (!empty($batch)) {
				$error++;
				setEventMessages($langs->transnoentities('StockTransferNoBatchForProduct', $prod->getNomUrl()), null, 'errors');
			}
		}

		if ($prod->status_batch==2 && abs($qty)>1) {
			$error++;
			setEventMessages($langs->transnoentities('TooManyQtyForSerialNumber', $prod->ref), null, 'errors');
		}

		if (empty($error)) {
			$line = new StockTransferLine($db);
			$records = $line->fetchAll('', '', 0, 0, '(fk_stocktransfer:=:'.((int) $id).') AND (fk_product:=:'.((int) $fk_product).') AND (fk_warehouse_source:=:'.((int) $fk_warehouse_source).') AND (fk_warehouse_destination:=:'.((int) $fk_warehouse_destination).') AND ('.(empty($batch) ? "(batch:=:'') OR (batch:IS:NULL)" : "batch:=:'".$db->escape($batch)."'").')');
			if (!empty($records[key($records)])) {
				$line = $records[key($records)];
			}
			$line->fk_stocktransfer = $id;
			$line->qty += $qty;
			$line->fk_warehouse_source = $fk_warehouse_source;
			$line->fk_warehouse_destination = $fk_warehouse_destination;
			$line->fk_product = $fk_product;
			$line->batch = $batch;

			$line->pmp = $prod->pmp;
			if ($line->id > 0) {
				$line->update($user);
			} else {
				$line->rang = (is_array($object->lines) || $object->lines instanceof Countable) ? count($object->lines) + 1 : 1;
				$line->create($user);
			}
			$object->fetchLines();
		}
	} elseif ($action === 'updateline' && $permissiontoadd) {
		if ($qty <= 0) {
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Qty")), null, 'errors');
			$action = 'editline';
		}

		if ($fk_warehouse_source <= 0) {
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("WarehouseSource")), null, 'errors');
			$action = 'editline';
		}

		if ($fk_warehouse_destination <= 0) {
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("WarehouseTarget")), null, 'errors');
			$action = 'editline';
		}

		$prod = new Product($db);
		$prod->fetch($fk_product);
		if ($prod->hasbatch()) {
			if (empty($batch)) {
				$error++;
				$langs->load("errors");
				setEventMessages($langs->transnoentities("ErrorTryToMakeMoveOnProductRequiringBatchData", $prod->getNomUrl()), null, 'errors');
				$action = 'editline';
			}
		} else {
			if (!empty($batch)) {
				$error++;
				setEventMessages($langs->transnoentities('StockTransferNoBatchForProduct', $prod->getNomUrl()), '', 'errors');
				$action = 'editline';
			}
		}

		if ($prod->status_batch==2 && abs($qty)>1) {
			$error++;
			setEventMessages($langs->transnoentities('TooManyQtyForSerialNumber', $prod->ref), null, 'errors');
			$action = 'editline';
		}

		if (empty($error)) {
			$line = new StockTransferLine($db);
			$line->fetch($lineid);
			$line->qty = $qty;
			$line->fk_warehouse_source = $fk_warehouse_source;
			$line->fk_warehouse_destination = $fk_warehouse_destination;
			$line->fk_product = $fk_product;
			$line->batch = $batch;
			$line->pmp = $prod->pmp;
			$line->update($user);
		}
	}

	if ($permissiontoadd) {
		// Décrémentation
		if ($action == 'confirm_destock' && $confirm == 'yes' && $object->status == $object::STATUS_VALIDATED) {
			$lines = $object->getLinesArray();
			if (!empty($lines)) {
				$db->begin();
				foreach ($lines as $line) {
					$res = $line->doStockMovement($label, $code_inv, $line->fk_warehouse_source);
					if ($res < 0) {
						$error++;
						setEventMessages($line->error, $line->errors, 'errors');
					}
				}
				if (empty($error)) {
					$db->commit();
				} else {
					$db->rollback();
				}
			}
			if (empty($error)) {
				$object->setStatut($object::STATUS_TRANSFERED, $id);
				$object->status = $object::STATUS_TRANSFERED;
				$object->date_reelle_depart = dol_now();
				$object->update($user);
				setEventMessage('StockStransferDecremented');
			}
		}

		// Annulation décrémentation
		if ($action == 'confirm_destockcancel' && $confirm == 'yes' && $object->status == $object::STATUS_TRANSFERED) {
			$lines = $object->getLinesArray();
			if (!empty($lines)) {
				$db->begin();
				foreach ($lines as $line) {
					$res = $line->doStockMovement($label, $code_inv, $line->fk_warehouse_source, 0);
					if ($res <= 0) {
						$error++;
						setEventMessages($line->error, $line->errors, 'errors');
					}
				}
				if (empty($error)) {
					$db->commit();
				} else {
					$db->rollback();
				}
			}
			if (empty($error)) {
				$object->setStatut($object::STATUS_VALIDATED, $id);
				$object->status = $object::STATUS_VALIDATED;
				$object->date_reelle_depart = null;
				$object->update($user);
				setEventMessage('StockStransferDecrementedCancel', 'warnings');
			}
		}

		// Incrémentation
		if ($action == 'confirm_addstock' && $confirm == 'yes' && $object->status == $object::STATUS_TRANSFERED) {
			$lines = $object->getLinesArray();
			if (!empty($lines)) {
				$db->begin();
				foreach ($lines as $line) {
					$res = $line->doStockMovement($label, $code_inv, $line->fk_warehouse_destination, 0);
					if ($res <= 0) {
						$error++;
						setEventMessages($line->error, $line->errors, 'errors');
					}
				}
				if (empty($error)) {
					$db->commit();
				} else {
					$db->rollback();
				}
			}
			if (empty($error)) {
				$object->setStatut($object::STATUS_CLOSED, $id);
				$object->status = $object::STATUS_CLOSED;
				$object->date_reelle_arrivee = dol_now();
				$object->update($user);
				setEventMessage('StockStransferIncrementedShort');
			}
		}

		// Annulation incrémentation
		if ($action == 'confirm_addstockcancel' && $confirm == 'yes' && $object->status == $object::STATUS_CLOSED) {
			$lines = $object->getLinesArray();
			if (!empty($lines)) {
				$db->begin();
				foreach ($lines as $line) {
					$res = $line->doStockMovement($label, $code_inv, $line->fk_warehouse_destination);
					if ($res <= 0) {
						$error++;
						setEventMessages($line->error, $line->errors, 'errors');
					}
				}
				if (empty($error)) {
					$db->commit();
				} else {
					$db->rollback();
				}
			}
			if (empty($error)) {
				$object->setStatut($object::STATUS_TRANSFERED, $id);
				$object->status = $object::STATUS_TRANSFERED;
				$object->date_reelle_arrivee = null;
				$object->update($user);
				setEventMessage('StockStransferIncrementedShortCancel', 'warnings');
			}
		}
	}

	// Set incoterm
	if ($action == 'set_incoterms' && isModEnabled('incoterm') && $permissiontoadd) {
		$result = $object->setIncoterms(GETPOSTINT('incoterm_id'), GETPOSTINT('location_incoterms'));
	}
	// Actions to send emails
	$triggersendname = 'STOCKTRANSFER_SENTBYMAIL';
	$autocopy = 'MAIN_MAIL_AUTOCOPY_STOCKTRANSFER_TO';
	$trackid = 'stocktransfer'.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';
}




/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);
$formproject = new FormProjets($db);

$title = $langs->trans("StockTransfer");
$help_url = '';

llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-product page-stock-stocktransfer_stocktransfer_card');



// Example : Adding jquery code
print '<script type="text/javascript" language="javascript">
jQuery(document).ready(function() {';

// Show alert for planned departure date if the transfer is related
$date_prevue_depart = $object->date_prevue_depart;
$date_prevue_depart_plus_delai = $date_prevue_depart;
if ($object->lead_time_for_warning > 0) {
	$date_prevue_depart_plus_delai = strtotime(date('Y-m-d', $date_prevue_depart) . ' + '.$object->lead_time_for_warning.' day');
}
if (!empty($date_prevue_depart) && $date_prevue_depart_plus_delai < strtotime(date('Y-m-d'))) {
	print "$('.valuefield.fieldname_date_prevue_depart').append('";
	print img_warning($langs->trans('Alert').' - '.$langs->trans('Late'));
	print "');";
}

print '});
</script>';


// Part to create
if ($action == 'create') {
	if (empty($permissiontoadd)) {
		accessforbidden('NotEnoughPermissions', 0, 1);
	}

	print load_fiche_titre($title, '', 'object_'.$object->picto);

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

	if (isModEnabled('incoterm')) {
		$soc = new Societe($db);
		if (!empty($object->fk_soc)) {
			$soc->fetch($object->fk_soc);
		}
		print '<tr>';
		print '<td><label for="incoterm_id">'.$form->textwithpicto($langs->trans("IncotermLabel"), $soc->label_incoterms, 1).'</label></td>';
		print '<td class="maxwidthonsmartphone">';
		print $form->select_incoterms((!empty($soc->fk_incoterms) ? $soc->fk_incoterms : ''), (!empty($soc->location_incoterms) ? $soc->location_incoterms : ''), '', 'fk_incoterms');
		print '</td></tr>';
	}
	// Template to use by default
	print '<tr><td>'.$langs->trans('DefaultModel').'</td>';
	print '<td>';
	print img_picto('', 'pdf', 'class="pictofixedwidth"');
	include_once DOL_DOCUMENT_ROOT.'/core/modules/commande/modules_commande.php';
	$liste = ModelePDFStockTransfer::liste_modeles($db);
	$preselected = getDolGlobalString('STOCKTRANSFER_ADDON_PDF');
	print $form->selectarray('model', $liste, $preselected, 0, 0, 0, '', 0, 0, 0, '', 'maxwidth200 widthcentpercentminusx', 1);
	print "</td></tr>";

	// Other attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_add.tpl.php';

	print '</table>'."\n";

	print dol_get_fiche_end();

	print $form->buttonsSaveCancel("Create");

	print '</form>';

	//dol_set_focus('input[name="ref"]');
}

// Part to edit record
if (($id || $ref) && $action == 'edit') {
	print load_fiche_titre($langs->trans("StockTransfer"), '', 'object_' . $object->picto);

	print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
	print '<input type="hidden" name="token" value="' . newToken() . '">';
	print '<input type="hidden" name="action" value="update">';
	print '<input type="hidden" name="id" value="' . $object->id . '">';
	if ($backtopage) {
		print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';
	}
	if ($backtopageforcancel) {
		print '<input type="hidden" name="backtopageforcancel" value="' . $backtopageforcancel . '">';
	}

	print dol_get_fiche_head();

	print '<table class="border centpercent tableforfieldedit">' . "\n";

	// Common attributes
	include DOL_DOCUMENT_ROOT . '/core/tpl/commonfields_edit.tpl.php';

	// Other attributes
	include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_edit.tpl.php';

	print '</table>';

	print dol_get_fiche_end();

	print $form->buttonsSaveCancel();

	print '</form>';
}

// Part to show record
if ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create'))) {
	$res = $object->fetch_optionals();

	$head = stocktransferPrepareHead($object);

	print dol_get_fiche_head($head, 'card', $langs->trans("StockTransfer"), -1, $object->picto);

	$formconfirm = '';

	// Confirmation to delete (using preloaded confirm popup)
	if ($action == 'delete' || ($conf->use_javascript_ajax && empty($conf->dol_use_jmobile))) {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('DeleteMyObject'), $langs->trans('ConfirmDeleteObject'), 'confirm_delete', '', 0, 'action-delete');
	}
	// Confirmation to delete line
	if ($action == 'deleteline') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&lineid='.$lineid, $langs->trans('DeleteLine'), $langs->trans('ConfirmDeleteLine'), 'confirm_deleteline', '', 0, 1);
	}
	// Clone confirmation
	if ($action == 'clone') {
		// Create an array for form
		$formquestion = array();
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ToClone'), $langs->trans('ConfirmCloneAsk', $object->ref), 'confirm_clone', $formquestion, 'yes', 1);
	} elseif ($action == 'destock') { // Destock confirmation
		// Create an array for form
		$formquestion = array(	'text' => '',
			0 => array('type' => 'text', 'name' => 'label', 'label' => $langs->trans("Label"), 'value' => $langs->trans('ConfirmDestock', $object->ref), 'size' => 40),
			1 => array('type' => 'text', 'name' => 'inventorycode', 'label' => $langs->trans("InventoryCode"), 'value' => dol_print_date(dol_now(), '%y%m%d%H%M%S'), 'size' => 25)
		);
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('DestockAllProduct'), '', 'confirm_destock', $formquestion, 'yes', 1);
	} elseif ($action == 'destockcancel') { // Destock confirmation cancel
		// Create an array for form
		$formquestion = array(	'text' => '',
			0 => array('type' => 'text', 'name' => 'label', 'label' => $langs->trans("Label"), 'value' => $langs->trans('ConfirmDestockCancel', $object->ref), 'size' => 40),
			1 => array('type' => 'text', 'name' => 'inventorycode', 'label' => $langs->trans("InventoryCode"), 'value' => dol_print_date(dol_now(), '%y%m%d%H%M%S'), 'size' => 25)
		);
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('DestockAllProductCancel'), '', 'confirm_destockcancel', $formquestion, 'yes', 1);
	} elseif ($action == 'addstock') { // Addstock confirmation
		// Create an array for form
		$formquestion = array(	'text' => '',
			0 => array('type' => 'text', 'name' => 'label', 'label' => $langs->trans("Label").'&nbsp;:', 'value' => $langs->trans('ConfirmAddStock', $object->ref), 'size' => 40),
			1 => array('type' => 'text', 'name' => 'inventorycode', 'label' => $langs->trans("InventoryCode"), 'value' => dol_print_date(dol_now(), '%y%m%d%H%M%S'), 'size' => 25)
		);
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('AddStockAllProduct'), '', 'confirm_addstock', $formquestion, 'yes', 1);
	} elseif ($action == 'addstockcancel') { // Addstock confirmation cancel
		// Create an array for form
		$formquestion = array(	'text' => '',
			0 => array('type' => 'text', 'name' => 'label', 'label' => $langs->trans("Label").'&nbsp;:', 'value' => $langs->trans('ConfirmAddStockCancel', $object->ref), 'size' => 40),
			1 => array('type' => 'text', 'name' => 'inventorycode', 'label' => $langs->trans("InventoryCode"), 'value' => dol_print_date(dol_now(), '%y%m%d%H%M%S'), 'size' => 25)
		);
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('AddStockAllProductCancel'), '', 'confirm_addstockcancel', $formquestion, 'yes', 1);
	}

	// Confirmation of action xxxx (You can use it for xxx = 'close', xxx = 'reopen', ...)
	if ($action == 'xxx') {
		$formquestion = array();
		/*
		$forcecombo=0;
		if ($conf->browser->name == 'ie') $forcecombo = 1;	// There is a bug in IE10 that make combo inside popup crazy
		$formquestion = array(
			// 'text' => $langs->trans("ConfirmClone"),
			// array('type' => 'checkbox', 'name' => 'clone_content', 'label' => $langs->trans("CloneMainAttributes"), 'value' => 1),
			// array('type' => 'checkbox', 'name' => 'update_prices', 'label' => $langs->trans("PuttingPricesUpToDate"), 'value' => 1),
			// array('type' => 'other',    'name' => 'idwarehouse',   'label' => $langs->trans("SelectWarehouseForStockDecrease"), 'value' => $formproduct->selectWarehouses(GETPOST('idwarehouse')?GETPOST('idwarehouse'):'ifone', 'idwarehouse', '', 1, 0, 0, '', 0, $forcecombo))
		);
		*/
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('XXX'), $text, 'confirm_xxx', $formquestion, 0, 1, 220);
	}


	if ($action == 'valid' && $permissiontoadd) {
		$nextref = $object->getNextNumRef();
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('Validate'), $langs->transnoentities('ConfirmValidateStockTransfer', $nextref), 'confirm_validate', $formquestion, 0, 2);
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
	$linkback = '<a href="'.dol_buildpath('/product/stock/stocktransfer/stocktransfer_list.php', 1).'?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

	$morehtmlref = '<div class="refidno">';
	// Thirdparty
	$morehtmlref .= empty($object->thirdparty) ? '' : $object->thirdparty->getNomUrl(1, 'customer');
	if (!getDolGlobalInt('MAIN_DISABLE_OTHER_LINK') && !empty($object->thirdparty) && $object->thirdparty->id > 0) {
		$morehtmlref .= ' (<a href="'.DOL_URL_ROOT.'/commande/list.php?socid='.$object->thirdparty->id.'&search_societe='.urlencode($object->thirdparty->name).'">'.$langs->trans("OtherOrders").'</a>)';
	}
	// Project
	if (isModEnabled('project')) {
		$langs->load("projects");
		$morehtmlref .= '<br>';
		if ($permissiontoadd) {
			$morehtmlref .= img_picto($langs->trans("Project"), 'project', 'class="pictofixedwidth"');
			if ($action != 'classify') {
				$morehtmlref .= '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?action=classify&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetProject')).'</a> ';
			}
			$morehtmlref .= $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, $object->socid, $object->fk_project, ($action == 'classify' ? 'projectid' : 'none'), 0, 0, 0, 1, '', 'maxwidth300');
		} else {
			if (!empty($object->fk_project)) {
				$proj = new Project($db);
				$proj->fetch($object->fk_project);
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
	print '<table class="border centpercent tableforfield">'."\n";

	// Common attributes
	//$keyforbreak='fieldkeytoswitchonsecondcolumn';	// We change column just before this field
	//unset($object->fields['fk_project']);				// Hide field already shown in banner
	//unset($object->fields['fk_soc']);					// Hide field already shown in banner

	$object->fields['fk_soc']['visible'] = 0; // Already available in banner
	$object->fields['fk_project']['visible'] = 0; // Already available in banner
	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_view.tpl.php';

	// Incoterms
	if (isModEnabled('incoterm')) {
		print '<tr><td>';
		print '<table width="100%" class="nobordernopadding"><tr><td>';
		print $langs->trans('IncotermLabel');
		print '<td><td class="right">';
		if ($permissiontoadd && $action != 'editincoterm') {
			print '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=editincoterm">'.img_edit().'</a>';
		} else {
			print '&nbsp;';
		}
		print '</td></tr></table>';
		print '</td>';
		print '<td>';
		if ($action != 'editincoterm') {
			print $form->textwithpicto($object->display_incoterms(), $object->label_incoterms, 1);
		} else {
			print $form->select_incoterms((!empty($object->fk_incoterms) ? $object->fk_incoterms : ''), (!empty($object->location_incoterms) ? $object->location_incoterms : ''), $_SERVER['PHP_SELF'].'?id='.$object->id);
		}
		print '</td></tr>';
	}

	echo '<tr>';
	echo '<td>'.$langs->trans('EnhancedValue').'&nbsp;'.strtolower($langs->trans('TotalWoman'));
	echo '<td>'.price($object->getValorisationTotale(), 0, '', 1, -1, -1, $conf->currency).'</td>';
	echo '</tr>';

	// Other attributes. Fields from hook formObjectOptions and Extrafields.
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
		// Show object lines
		/*
		$result = $object->getLinesArray();

		print '	<form name="addproduct" id="addproduct" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.(($action != 'editline') ? '' : '#line_'.GETPOST('lineid', 'int')).'" method="POST">
		<input type="hidden" name="token" value="' . newToken().'">
		<input type="hidden" name="action" value="' . (($action != 'editline') ? 'addline' : 'updateline').'">
		<input type="hidden" name="mode" value="">
		<input type="hidden" name="page_y" value="">
		<input type="hidden" name="id" value="' . $object->id.'">
		';
		*/

		if (!empty($conf->use_javascript_ajax) && $object->status == 0) {
			$fk_element = $object->id;
			include DOL_DOCUMENT_ROOT.'/core/tpl/ajaxrow.tpl.php';
		}

		/*
		print '<div class="div-table-responsive-no-min">';
		if (!empty($object->lines) || ($object->status == $object::STATUS_DRAFT && $permissiontoadd && $action != 'selectlines' && $action != 'editline')) {
			print '<table id="tablelines" class="noborder noshadow" width="100%">';
		}

		if (!empty($object->lines)) {
			$object->printObjectLines($action, $mysoc, null, GETPOST('lineid', 'int'), 1);
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
					$object->formAddObjectLine(1, $mysoc, $soc);
				}
			}
		}

		if (!empty($object->lines) || ($object->status == $object::STATUS_DRAFT && $permissiontoadd && $action != 'selectlines' && $action != 'editline')) {
			print '</table>';
		}
		print '</div>';

		print "</form>\n";
		*/
	}


	$formproduct = new FormProduct($db);
	print '<div class="div-table-responsive-no-min">';
	print '	<form name="addproduct" id="addproduct" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.(($action != 'editline') ? '#addline' : '#line_'.GETPOSTINT('lineid')).'" method="POST">
		<input type="hidden" name="token" value="' . newToken().'">
		<input type="hidden" name="action" value="' . (($action != 'editline') ? 'addline' : 'updateline').'">
		<input type="hidden" name="mode" value="">
		<input type="hidden" name="id" value="' . $object->id.'">
		';
	if ($lineid > 0) {
		print '<input type="hidden" name="lineid" value="'.$lineid.'" />';
	}
	print '<table id="tablelines" class="liste centpercent">';
	//print '<div class="tagtable centpercent">';

	$param = '';

	$conf->global->MAIN_DISABLE_WRAPPING_ON_COLUMN_TITLE = true; // Full display needed to see all column title details

	print '<tr class="liste_titre">';
	print getTitleFieldOfList($langs->trans('ProductRef'), 0, $_SERVER["PHP_SELF"], '', $param, '', '', $sortfield, $sortorder, 'tagtd maxwidthonsmartphone ');
	if (isModEnabled('productbatch')) {
		print getTitleFieldOfList($langs->trans('Batch'), 0, $_SERVER["PHP_SELF"], '', $param, '', '', $sortfield, $sortorder, 'tagtd maxwidthonsmartphone ');
	}
	print getTitleFieldOfList($langs->trans('WarehouseSource'), 0, $_SERVER["PHP_SELF"], '', $param, '', '', $sortfield, $sortorder, 'tagtd maxwidthonsmartphone ');
	print getTitleFieldOfList($langs->trans('WarehouseTarget'), 0, $_SERVER["PHP_SELF"], '', $param, '', '', $sortfield, $sortorder, 'tagtd maxwidthonsmartphone ');
	print getTitleFieldOfList($langs->trans('Qty'), 0, $_SERVER["PHP_SELF"], '', $param, '', '', $sortfield, $sortorder, 'center tagtd maxwidthonsmartphone ');
	if (getDolGlobalInt('PRODUCT_USE_UNITS')) {
		print getTitleFieldOfList($langs->trans('Unit'), 0, $_SERVER["PHP_SELF"], '', $param, '', '', $sortfield, $sortorder, 'tagtd maxwidthonsmartphone ');
	}
	print getTitleFieldOfList($langs->trans('AverageUnitPricePMPShort'), 0, $_SERVER["PHP_SELF"], '', $param, '', '', $sortfield, $sortorder, 'center tagtd maxwidthonsmartphone ');
	print getTitleFieldOfList($langs->trans('EstimatedStockValueShort'), 0, $_SERVER["PHP_SELF"], '', $param, '', '', $sortfield, $sortorder, 'center tagtd maxwidthonsmartphone ');
	if (empty($object->status) && $permissiontoadd) {
		print getTitleFieldOfList('', 0);
		print getTitleFieldOfList('', 0);
		print getTitleFieldOfList('', 0);
	}

	print '</tr>';

	$listofdata = $object->getLinesArray();
	$productstatic = new Product($db);
	$warehousestatics = new Entrepot($db);
	$warehousestatict = new Entrepot($db);

	foreach ($listofdata as $key => $line) {
		$productstatic->fetch($line->fk_product);
		$warehousestatics->fetch($line->fk_warehouse_source);
		$warehousestatict->fetch($line->fk_warehouse_destination);

		// add html5 elements
		$domData  = ' data-element="'.$line->element.'"';
		$domData .= ' data-id="'.$line->id.'"';
		$domData .= ' data-qty="'.$line->qty.'"';
		//$domData .= ' data-product_type="'.$line->product_type.'"';

		print '<tr id="row-'.$line->id.'" class="drag drop oddeven" '.$domData.'>';
		print '<td class="titlefield">';
		if ($action === 'editline' && $line->id == $lineid) {
			$form->select_produits($line->fk_product, 'fk_product', '', 0, 0, -1, 2, '', 0, array(), 0, 0, 0, 'minwidth200imp maxwidth300', 1);
		} else {
			print $productstatic->getNomUrl(1).' - '.$productstatic->label;
		}
		print '</td>';
		if (isModEnabled('productbatch')) {
			print '<td>';
			if ($action === 'editline' && $line->id == $lineid) {
				print '<input type="text" value="'.$line->batch.'" name="batch" class="flat maxwidth50"/>';
			} else {
				$productlot = new Productlot($db);
				if ($productlot->fetch(0, $line->fk_product, $line->batch) > 0) {
					print $productlot->getNomUrl(1);
				} elseif (!empty($line->batch)) {
					print $line->batch.'&nbsp;'.img_warning($langs->trans('BatchNotFound'));
				}
			}
			print '</td>';
		}

		print '<td>';

		if ($action === 'editline' && $line->id == $lineid) {
			print $formproduct->selectWarehouses($line->fk_warehouse_source, 'fk_warehouse_source', 'warehouseopen,warehouseinternal', 1, 0, 0, '', 0, 0, array(), 'minwidth200imp maxwidth200');
		} else {
			print $warehousestatics->getNomUrl(1);
		}
		print '</td>';
		print '<td>';
		if ($action === 'editline' && $line->id == $lineid) {
			print $formproduct->selectWarehouses($line->fk_warehouse_destination, 'fk_warehouse_destination', 'warehouseopen,warehouseinternal', 1, 0, 0, '', 0, 0, array(), 'minwidth200imp maxwidth200');
		} else {
			print $warehousestatict->getNomUrl(1);
		}
		print '</td>';
		if ($action === 'editline' && $line->id == $lineid) {
			print '<td class="center"><input type="text" class="flat maxwidth50" name="qty" value="'.$line->qty.'"></td>';
		} else {
			print '<td class="center">'.$line->qty.'</td>';
		}

		if (getDolGlobalInt('PRODUCT_USE_UNITS')) {
			print '<td class="linecoluseunit nowrap left">';
			$label = $productstatic->getLabelOfUnit('short');
			if ($label !== '') {
				print $langs->trans($label);
			}
			print '</td>';
		}

		print '<td class="center">';
		print price($line->pmp, 0, '', 1, -1, -1, $conf->currency);
		print '</td>';
		print '<td class="center">';
		print price($line->pmp * $line->qty, 0, '', 1, -1, -1, $conf->currency);
		print '</td>';
		if (empty($object->status) && $permissiontoadd) {
			if ($action === 'editline' && $line->id == $lineid) {
				//print '<td class="right" colspan="2"><input type="submit" class="button" name="addline" value="' . dol_escape_htmltag($langs->trans('Save')) . '"></td>';
				print '<td class="center valignmiddle" colspan="2"><input type="submit" class="button buttongen marginbottomonly" id="savelinebutton marginbottomonly" name="save" value="'.$langs->trans("Save").'"><br>';
				print '<input type="submit" class="button buttongen marginbottomonly" id="cancellinebutton" name="cancel" value="'.$langs->trans("Cancel").'"></td>';
			} else {
				print '<td class="right">';
				print '<a class="editfielda reposition" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;action=editline&amp;lineid=' . $line->id . '#line_' . $line->id . '">';
				print img_edit() . '</a>';
				print '</td>';
				print '<td class="right">';
				print '<a href="' . $_SERVER["PHP_SELF"] . '?id=' . $id . '&action=deleteline&lineid=' . $line->id . '">' . img_delete($langs->trans("Remove")) . '</a>';
				print '</td>';
			}

			$num = count($object->lines);

			if ($num > 1 && $conf->browser->layout != 'phone' && empty($disablemove)) {
				print '<td class="linecolmove tdlineupdown center">';
				if ($i > 0) { ?>
					<a class="lineupdown" href="<?php print $_SERVER["PHP_SELF"].'?id='.$id.'&amp;action=up&amp;rowid='.$line->id; ?>">
						<?php print img_up('default', 0, 'imgupforline'); ?>
					</a>
				<?php }
				if ($i < $num - 1) { ?>
					<a class="lineupdown" href="<?php print $_SERVER["PHP_SELF"].'?id='.$id.'&amp;action=down&amp;rowid='.$line->id; ?>">
						<?php print img_down('default', 0, 'imgdownforline'); ?>
					</a>
				<?php }
				print '</td>';
			} else {
				print '<td '.(($conf->browser->layout != 'phone' && empty($disablemove)) ? ' class="linecolmove tdlineupdown center"' : ' class="linecolmove center"').'></td>';
			}
		}

		print '</tr>';
	}

	if (empty($object->status) && $action !== 'editline' && $permissiontoadd) {
		print '<tr class="oddeven">';
		// Product
		print '<td class="titlefield">';
		$filtertype = 0;
		if (getDolGlobalString('STOCK_SUPPORTS_SERVICES')) {
			$filtertype = '';
		}
		if (getDolGlobalInt('PRODUIT_LIMIT_SIZE') <= 0) {
			$limit = '';
		} else {
			$limit = getDolGlobalString('PRODUIT_LIMIT_SIZE');
		}

		$form->select_produits($fk_product, 'fk_product', $filtertype, $limit, 0, -1, 2, '', 0, array(), 0, 0, 0, 'minwidth200imp maxwidth300', 1);
		print '</td>';
		// Batch number
		if (isModEnabled('productbatch')) {
			print '<td>';
			print '<input type="text" name="batch" class="flat maxwidth50" '.(!empty($error) ? 'value="'.$batch.'"' : '').'>';
			print '</td>';
		}

		$formproduct->loadWarehouses(); // Pour charger la totalité des entrepôts

		// Define a list of warehouse to not show on the list
		$TExcludedWarehouseSource = array();
		if (!empty($object->fk_warehouse_source)) {
			$source_ent = new Entrepot($db);
			$source_ent->fetch($object->fk_warehouse_source);
			foreach ($formproduct->cache_warehouses as $TDataCacheWarehouse) {
				if (strpos($TDataCacheWarehouse['full_label'], $source_ent->label) === false) {
					$TExcludedWarehouseSource[] = $TDataCacheWarehouse['id'];
				}
			}
		}

		// Define a list of warehouse to not show on the list
		$TExcludedWarehouseDestination = array();
		if (!empty($object->fk_warehouse_destination)) {
			$dest_ent = new Entrepot($db);
			$dest_ent->fetch($object->fk_warehouse_destination);
			foreach ($formproduct->cache_warehouses as $TDataCacheWarehouse) {
				if (strpos($TDataCacheWarehouse['full_label'], $dest_ent->label) === false) {
					$TExcludedWarehouseDestination[] = $TDataCacheWarehouse['id'];
				}
			}
		}

		// On vide le tableau pour qu'il se charge tout seul lors de l'appel à la fonction select_warehouses
		$formproduct->cache_warehouses = array();
		// In warehouse
		print '<td>';
		print $formproduct->selectWarehouses(empty($fk_warehouse_source) ? $object->fk_warehouse_source : $fk_warehouse_source, 'fk_warehouse_source', 'warehouseopen,warehouseinternal', 1, 0, 0, '', 0, 0, array(), 'minwidth200imp maxwidth200', $TExcludedWarehouseSource);
		print '</td>';

		// On vide le tableau pour qu'il se charge tout seul lors de l'appel à la fonction select_warehouses
		$formproduct->cache_warehouses = array();
		// Out warehouse
		print '<td>';
		print $formproduct->selectWarehouses(empty($fk_warehouse_destination) ? $object->fk_warehouse_destination : $fk_warehouse_destination, 'fk_warehouse_destination', 'warehouseopen,warehouseinternal', 1, 0, 0, '', 0, 0, array(), 'minwidth200imp maxwidth200', $TExcludedWarehouseDestination);
		print '</td>';

		// Qty
		print '<td class="center"><input type="text" class="flat maxwidth50" name="qty" '.(!empty($error) ? 'value="'.$qty.'"' : '').'></td>';
		// PMP
		print '<td></td>';
		if (getDolGlobalInt('PRODUCT_USE_UNITS')) {
			// Unité
			print '<td></td>';
		}
		// PMP * Qty
		print '<td></td>';
		// Button to add line
		print '<td class="right" colspan="2"><input type="submit" class="button" name="addline" value="' . dol_escape_htmltag($langs->trans('Add')) . '"></td>';
		// Grad and drop lines
		print '<td></td>';
		print '</tr>';
	}

	print '</table>';
	print '</form>';
	print '</div>';

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
			if (empty($user->socid)) {
				print dolGetButtonAction('', $langs->trans('SendMail'), 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=presend&token='.newToken().'&mode=init#formmailbeforetitle');
			}

			// Back to draft
			if ($object->status == $object::STATUS_VALIDATED) {
				print dolGetButtonAction('', $langs->trans('SetToDraft'), 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=confirm_setdraft&confirm=yes&token='.newToken(), '', $permissiontoadd);
			}

			// Modify
			print dolGetButtonAction('', $langs->trans('Modify'), 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=edit&token='.newToken(), '', $permissiontoadd);

			// Validate
			if ($object->status == $object::STATUS_DRAFT) {
				if (empty($object->table_element_line) || (is_array($object->lines) && count($object->lines) > 0)) {
					print dolGetButtonAction('', $langs->trans('Validate'), 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=confirm_validate&confirm=yes&token='.newToken(), '', $permissiontoadd);
				} else {
					$langs->load("errors");
					print dolGetButtonAction($langs->trans("ErrorAddAtLeastOneLineFirst"), $langs->trans("Validate"), 'default', '#', '', 0);
				}
			} elseif ($object->status == $object::STATUS_VALIDATED && $permissiontoadd) {
				print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=destock&token='.newToken().'">'.$langs->trans("StockTransferDecrementation").'</a>';
			} elseif ($object->status == $object::STATUS_TRANSFERED && $permissiontoadd) {
				print '<a class="butActionDelete" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=destockcancel&token='.newToken().'">'.$langs->trans("StockTransferDecrementationCancel").'</a>';
				print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=addstock&token='.newToken().'">'.$langs->trans("StockTransferIncrementation").'</a>';
			} elseif ($object->status == $object::STATUS_CLOSED && $permissiontoadd) {
				print '<a class="butActionDelete" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=addstockcancel&token='.newToken().'">'.$langs->trans("StockTransferIncrementationCancel").'</a>';
			}

			// Clone
			if ($permissiontoadd) {
				print dolGetButtonAction('', $langs->trans('ToClone'), 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.(!empty($object->socid) ? '&socid='.$object->socid : '').'&action=clone&token='.newToken(), '', $permissiontoadd);
			}

			/*
			if ($permissiontoadd) {
				if ($object->status == $object::STATUS_ENABLED) {
					print dolGetButtonAction('', $langs->trans('Disable'), 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=disable&token='.newToken(), '', $permissiontoadd);
				} else {
					print dolGetButtonAction('', $langs->trans('Enable'), 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=enable&token='.newToken(), '', $permissiontoadd);
				}
			}
			if ($permissiontoadd) {
				if ($object->status == $object::STATUS_VALIDATED) {
					print dolGetButtonAction('', $langs->trans('Cancel'), 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=close&token='.newToken(), '', $permissiontoadd);
				} else {
					print dolGetButtonAction('', $langs->trans('Re-Open'), 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=reopen&token='.newToken(), '', $permissiontoadd);
				}
			}

			*/

			// Delete (with preloaded confirm popup)
			$deleteUrl = $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=delete&token='.newToken();
			$buttonId = 'action-delete-no-ajax';
			if ($conf->use_javascript_ajax && empty($conf->dol_use_jmobile)) {	// We can use preloaded confirm if not jmobile
				$deleteUrl = '';
				$buttonId = 'action-delete';
			}
			$params = array();
			print dolGetButtonAction('', $langs->trans("Delete"), 'delete', $deleteUrl, $buttonId, $permissiontodelete, $params);
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

		$includedocgeneration = 1;

		// Documents
		if ($includedocgeneration) {
			$objref = dol_sanitizeFileName($object->ref);
			$relativepath = $objref . '/' . $objref . '.pdf';
			$filedir = $conf->stocktransfer->dir_output.'/'.$object->element.'/'.$objref;
			$urlsource = $_SERVER["PHP_SELF"] . "?id=" . $object->id;
			$genallowed = $permissiontoread; // If you can read, you can build the PDF to read content
			$delallowed = $permissiontoadd; // If you can create/edit, you can remove a file on card
			print $formfile->showdocuments('stocktransfer:StockTransfer', $object->element.'/'.$objref, $filedir, $urlsource, $genallowed, $delallowed, $object->model_pdf, 1, 0, 0, 28, 0, '', '', '', $langs->defaultlang);
		}

		// Show links to link elements
		$linktoelem = $form->showLinkToObjectBlock($object, null, array('stocktransfer'));
		$somethingshown = $form->showLinkedObjectBlock($object, $linktoelem);


		print '</div><div class="fichehalfright">';

		$MAXEVENT = 10;

		$morehtmlcenter = dolGetButtonTitle($langs->trans('SeeAll'), '', 'fa fa-bars imgforviewmode', dol_buildpath('/mymodule/myobject_agenda.php', 1).'?id='.$object->id);

		// List of actions on element
		include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
		$formactions = new FormActions($db);
		$somethingshown = $formactions->showactions($object, 'stocktransfer', 0, 1, '', $MAXEVENT, '');

		print '</div></div>';
	}

	//Select mail models is same action as presend
	if (GETPOST('modelselected')) {
		$action = 'presend';
	}

	// Presend form
	$modelmail = 'stocktransfer';
	$defaulttopic = 'InformationMessage';
	$diroutput = $conf->stocktransfer->dir_output;
	$trackid = 'stocktransfer'.$object->id;

	include DOL_DOCUMENT_ROOT.'/core/tpl/card_presend.tpl.php';
}

// End of page
llxFooter();
$db->close();
