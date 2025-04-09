<?php
/* Copyright (C) 2004-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2016 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2010-2021 Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2014      Cedric Gross         <c.gross@kreiz-it.fr>
 * Copyright (C) 2016      Florian Henry        <florian.henry@atm-consulting.fr>
 * Copyright (C) 2017-2022 Ferran Marcet        <fmarcet@2byte.es>
 * Copyright (C) 2018-2024  Frédéric France      <frederic.france@free.fr>
 * Copyright (C) 2019-2020 Christophe Battarel	<christophe@altairis.fr>
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
 * \file 	htdocs/reception/dispatch.php
 * \ingroup commande
 * \brief 	Page to dispatch receptions.
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/supplier_order/modules_commandefournisseur.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/fourn.lib.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.dispatch.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/reception.lib.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langs->loadLangs(array("bills", "orders", "sendings", "companies", "deliveries", "products", "stocks", "receptions"));

if (isModEnabled('productbatch')) {
	$langs->load('productbatch');
}

// Security check
$id = GETPOSTINT("id");
$ref = GETPOST('ref');
$lineid = GETPOSTINT('lineid');
$action = GETPOST('action', 'aZ09');
$fk_default_warehouse = GETPOSTINT('fk_default_warehouse');
$cancel = GETPOST('cancel', 'alpha');
$confirm = GETPOST('confirm', 'alpha');

$error = 0;
$errors = array();

if ($user->socid) {
	$socid = $user->socid;
}

$hookmanager->initHooks(array('ordersupplierdispatch'));

// Recuperation de l'id de projet
$projectid = 0;
if (GETPOSTISSET("projectid")) {
	$projectid = GETPOSTINT("projectid");
}

$object = new Reception($db);

if ($id > 0 || !empty($ref)) {
	$result = $object->fetch($id, $ref);
	if ($result < 0) {
		setEventMessages($object->error, $object->errors, 'errors');
	}
	$result = $object->fetch_thirdparty();
	if ($result < 0) {
		setEventMessages($object->error, $object->errors, 'errors');
	}
	if (!empty($object->origin)) {
		$origin = $object->origin;
		$typeobject = $object->origin;

		$object->fetch_origin();
	}
	if ($origin == 'order_supplier' && $object->origin_object->id && (isModEnabled("fournisseur") && !getDolGlobalString('MAIN_USE_NEW_SUPPLIERMOD') || isModEnabled("supplier_order"))) {
		$origin_id = $object->origin_object->id;
		$objectsrc = new CommandeFournisseur($db);
		$objectsrc->fetch($origin_id);
	}
}

if (empty($conf->reception->enabled)) {
	$permissiontoreceive = $user->hasRight('fournisseur', 'commande', 'receptionner');
	$permissiontocontrol = ((!getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('fournisseur', 'commande', 'receptionner')) || (getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('fournisseur', 'commande_advance', 'check')));
} else {
	$permissiontoreceive = $user->hasRight('reception', 'creer');
	$permissiontocontrol = ((!getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('reception', 'creer')) || (getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('reception', 'reception_advance', 'validate')));
}

// $id is id of a reception
$result = restrictedArea($user, 'reception', $object->id);

if (!isModEnabled('stock')) {
	accessforbidden('Module stock disabled');
}

$usercancreate = $user->hasRight('reception', 'creer');
$permissiontoadd = $usercancreate; // Used by the include of actions_addupdatedelete.inc.php


/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

// Update a dispatched line
if ($action == 'updatelines' && $permissiontoreceive) {
	$db->begin();
	$error = 0;

	$supplierorderdispatch = new CommandeFournisseurDispatch($db);
	$pos = 0;

	foreach ($_POST as $key => $value) {
		// without batch module enabled
		$reg = array();
		if (preg_match('/^product_.*([0-9]+)_([0-9]+)$/i', $key, $reg)) {
			$pos++;
			if (preg_match('/^product_([0-9]+)_([0-9]+)$/i', $key, $reg)) {
				$modebatch = "barcode";
			} elseif (preg_match('/^product_batch_([0-9]+)_([0-9]+)$/i', $key, $reg)) { // With batchmode enabled
				$modebatch = "batch";
			}

			$numline = $pos;
			if ($modebatch == "barcode") {
				$prod = "product_".$reg[1].'_'.$reg[2];
			} else {
				$prod = 'product_batch_'.$reg[1].'_'.$reg[2];
			}
			$qty = "qty_".$reg[1].'_'.$reg[2];
			$ent = "entrepot_".$reg[1].'_'.$reg[2];
			$pu = "pu_".$reg[1].'_'.$reg[2]; // This is unit price including discount
			$fk_commandefourndet = "fk_commandefourndet_".$reg[1].'_'.$reg[2];
			$idline = GETPOST("idline_".$reg[1].'_'.$reg[2]);
			$lot = '';
			$dDLUO = '';
			$dDLC = '';
			if ($modebatch == "batch") {
				$lot = GETPOST('lot_number_'.$reg[1].'_'.$reg[2]);
				$dDLUO = dol_mktime(12, 0, 0, GETPOSTINT('dluo_'.$reg[1].'_'.$reg[2].'month'), GETPOSTINT('dluo_'.$reg[1].'_'.$reg[2].'day'), GETPOSTINT('dluo_'.$reg[1].'_'.$reg[2].'year'));
				$dDLC = dol_mktime(12, 0, 0, GETPOSTINT('dlc_'.$reg[1].'_'.$reg[2].'month'), GETPOSTINT('dlc_'.$reg[1].'_'.$reg[2].'day'), GETPOSTINT('dlc_'.$reg[1].'_'.$reg[2].'year'));
			}

			if (getDolGlobalString('SUPPLIER_ORDER_CAN_UPDATE_BUYINGPRICE_DURING_RECEIPT')) {
				if (!isModEnabled("multicurrency") && empty($conf->dynamicprices->enabled)) {
					$dto = GETPOSTINT("dto_".$reg[1].'_'.$reg[2]);
					if (!empty($dto)) {
						$unit_price = (float) price2num(GETPOSTFLOAT("pu_".$reg[1]) * (100 - $dto) / 100, 'MU');
					}
					$saveprice = "saveprice_".$reg[1].'_'.$reg[2];
				}
			}

			// We ask to move a qty
			if (($modebatch == "batch" && GETPOST($qty) > 0) || ($modebatch == "barcode" && GETPOST($qty) != 0)) {
				if (!(GETPOSTINT($ent) > 0)) {
					dol_syslog('No dispatch for line '.$key.' as no warehouse was chosen.');
					$text = $langs->transnoentities('Warehouse').', '.$langs->transnoentities('Line').' '.($numline);
					setEventMessages($langs->trans('ErrorFieldRequired', $text), null, 'errors');
					$error++;
				}

				if (!$error) {
					if ($idline > 0) {
						$result = $supplierorderdispatch->fetch($idline);
						if ($result < 0) {
							setEventMessages($supplierorderdispatch->error, $supplierorderdispatch->errors, 'errors');
							$error++;
						} else {
							$qtystart = $supplierorderdispatch->qty;
							$supplierorderdispatch->qty = (float) price2num(GETPOST($qty));
							$supplierorderdispatch->fk_entrepot = GETPOSTINT($ent);
							if ($modebatch == "batch") {
								$supplierorderdispatch->eatby = $dDLUO;
								$supplierorderdispatch->sellby = $dDLC;
							}

							$result = $supplierorderdispatch->update($user);
							if ($result < 0) {
								setEventMessages($supplierorderdispatch->error, $supplierorderdispatch->errors, 'errors');
								$error++;
							}

							// If module stock is enabled and the stock decrease is done on edition of this page
							/*
							if (!$error && GETPOST($ent, 'int') > 0 && isModEnabled('stock') && !empty($conf->global->STOCK_CALCULATE_ON_SUPPLIER_DISPATCH_ORDER)) {
								$mouv = new MouvementStock($db);
								$product = GETPOST($prod, 'int');
								$entrepot = GETPOST($ent, 'int');
								$qtymouv = GETPOST($qty) - $qtystart;
								$price = GETPOST($pu);
								$comment = GETPOST('comment');
								$inventorycode = dol_print_date(dol_now(), 'dayhourlog');
								$now = dol_now();
								$eatby = '';
								$sellby = '';
								$batch = '';
								if ($modebatch == "batch") {
									$eatby = $dDLUO;
									$sellby = $dDLC;
									$batch = $supplierorderdispatch->batch;
								}
								if ($product > 0) {
									// $price should take into account discount (except if option STOCK_EXCLUDE_DISCOUNT_FOR_PMP is on)
									$mouv->origin = $objectsrc;
									$mouv->setOrigin($objectsrc->element, $objectsrc->id);

									// Method change if qty < 0
									if (getDolGlobalString('SUPPLIER_ORDER_ALLOW_NEGATIVE_QTY_FOR_SUPPLIER_ORDER_RETURN') && $qtymouv < 0) {
										$result = $mouv->livraison($user, $product, $entrepot, $qtymouv*(-1), $price, $comment, $now, $eatby, $sellby, $batch, 0, $inventorycode);
									} else {
										$result = $mouv->reception($user, $product, $entrepot, $qtymouv, $price, $comment, $eatby, $sellby, $batch, '', 0, $inventorycode);
									}

									if ($result < 0) {
										setEventMessages($mouv->error, $mouv->errors, 'errors');
										$error++;
									}
								}
							}
							*/
						}
					} else {
						$result = $objectsrc->dispatchProduct($user, GETPOSTINT($prod), GETPOST($qty), GETPOSTINT($ent), GETPOST($pu), GETPOST('comment'), $dDLUO, $dDLC, $lot, GETPOSTINT($fk_commandefourndet), 0, $object->id);
						if ($result < 0) {
							setEventMessages($objectsrc->error, $objectsrc->errors, 'errors');
							$error++;
						}
					}

					if (!$error && getDolGlobalString('SUPPLIER_ORDER_CAN_UPDATE_BUYINGPRICE_DURING_RECEIPT')) {
						if (!isModEnabled("multicurrency") && empty($conf->dynamicprices->enabled)) {
							$dto = price2num(GETPOSTINT("dto_".$reg[1].'_'.$reg[2]), '');
							if (empty($dto)) {
								$dto = 0;
							}

							//update supplier price
							if (GETPOSTISSET($saveprice)) {
								// TODO Use class
								$sql = "UPDATE ".MAIN_DB_PREFIX."product_fournisseur_price";
								$sql .= " SET unitprice='".price2num(GETPOST($pu), 'MU')."'";
								$sql .= ", price=".price2num(GETPOST($pu), 'MU')."*quantity";
								$sql .= ", remise_percent = ".((float) $dto);
								$sql .= " WHERE fk_soc=".((int) $object->socid);
								$sql .= " AND fk_product=".(GETPOSTINT($prod));

								$resql = $db->query($sql);
							}
						}
					}
				}
			}
		}
	}
	if ($error > 0) {
		$db->rollback();
		setEventMessages($langs->trans("Error"), $errors, 'errors');
	} else {
		$db->commit();
		setEventMessages($langs->trans("ReceptionUpdated"), null);

		header("Location: ".DOL_URL_ROOT.'/reception/dispatch.php?id='.$object->id);
		exit;
	}
}


/*
 * View
 */

$now = dol_now();

$form = new Form($db);
$formproduct = new FormProduct($db);
$warehouse_static = new Entrepot($db);
$supplierorderdispatch = new CommandeFournisseurDispatch($db);

$title = $object->ref." - ".$langs->trans('ReceptionDistribution');
$help_url = 'EN:Module_Suppliers_Orders|FR:CommandeFournisseur|ES:Módulo_Pedidos_a_proveedores';
$morejs = array('/fourn/js/lib_dispatch.js.php');
$numline = 0;

llxHeader('', $title, $help_url, '', 0, 0, $morejs, '', '', 'mod-reception page-card_dispatch');

if ($id > 0 || !empty($ref)) {
	if (!empty($object->origin) && $object->origin_id > 0) {
		$object->origin = 'CommandeFournisseur';
		$typeobject = $object->origin;
		$origin = $object->origin;
		$origin_id = $object->origin_id;
		$object->fetch_origin(); // Load property $object->origin_object, $object->commande, $object->propal, ...
	}
	$soc = new Societe($db);
	$soc->fetch($object->socid);

	$author = new User($db);
	$author->fetch($object->user_author_id);

	$head = reception_prepare_head($object);

	$title = $langs->trans("SupplierOrder");
	print dol_get_fiche_head($head, 'dispatch', $langs->trans("Reception"), -1, 'dollyrevert');


	$formconfirm = '';

	// Confirmation to delete line
	if ($action == 'ask_deleteline') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&lineid='.$lineid, $langs->trans('DeleteLine'), $langs->trans('ConfirmDeleteLine'), 'confirm_deleteline', '', 0, 1);
	}

	// Call Hook formConfirm
	$parameters = array('lineid' => $lineid);
	// Note that $action and $object may be modified by hook
	$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action);
	if (empty($reshook)) {
		$formconfirm .= $hookmanager->resPrint;
	} elseif ($reshook > 0) {
		$formconfirm = $hookmanager->resPrint;
	}

	// Print form confirm
	print $formconfirm;

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
	print '<div class="underbanner clearboth"></div>';

	print '<table class="border tableforfield" width="100%">';

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
	print '</tr></table>';
	print '</td><td colspan="2">';
	print $object->date_delivery ? dol_print_date($object->date_delivery, 'dayhour') : '&nbsp;';
	print '</td>';
	print '</tr>';
	print '</table>';

	print '<br><br><center>';
	print '<a href="#" id="resetalltoexpected" class="marginrightonly paddingright marginleftonly paddingleft">'.img_picto("", 'autofill', 'class="pictofixedwidth"').$langs->trans("RestoreWithCurrentQtySaved").'</a></td>';
	// Link to clear qty
	print '<a href="#" id="autoreset" class="marginrightonly paddingright marginleftonly paddingleft">'.img_picto("", 'eraser', 'class="pictofixedwidth"').$langs->trans("ClearQtys").'</a></td>';
	print '<center>';

	print '<br>';
	$disabled = 0;	// This is used to disable or not the bulk selection of target warehouse. No reason to have it disabled so forced to 0.

	if ($object->statut == Reception::STATUS_DRAFT || ($object->statut == Reception::STATUS_VALIDATED && !getDolGlobalString('STOCK_CALCULATE_ON_RECEPTION'))) {
		require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
		$formproduct = new FormProduct($db);
		$formproduct->loadWarehouses();
		$entrepot = new Entrepot($db);
		$listwarehouses = $entrepot->list_array(1);

		print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';

		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="action" value="updatelines">';
		print '<input type="hidden" name="id" value="'.$object->id.'">';

		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder centpercent">';

		// Get list of lines from the original Order into $products_dispatched with qty dispatched for each product id
		$products_dispatched = array();
		$sql = "SELECT l.rowid, cfd.fk_product, sum(cfd.qty) as qty";
		$sql .= " FROM ".MAIN_DB_PREFIX."receptiondet_batch as cfd";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."receptiondet_batch as l on l.rowid = cfd.fk_elementdet";
		$sql .= " WHERE cfd.fk_reception = ".((int) $object->id);
		$sql .= " GROUP BY l.rowid, cfd.fk_product";

		$resql = $db->query($sql);
		if ($resql) {
			$num = $db->num_rows($resql);
			$i = 0;

			if ($num) {
				while ($i < $num) {
					$objd = $db->fetch_object($resql);
					$products_dispatched[$objd->rowid] = price2num($objd->qty, 'MS');
					$i++;
				}
			}
			$db->free($resql);
		}


		//$sql = "SELECT l.rowid, l.fk_product, l.subprice, l.remise_percent, l.ref AS sref, SUM(l.qty) as qty,";
		$sql = "SELECT l.rowid, l.fk_product, l.subprice, l.remise_percent, l.ref AS sref, l.qty as qty,";
		$sql .= " p.ref, p.label, p.tobatch, p.fk_default_warehouse";
		// Enable hooks to alter the SQL query (SELECT)
		$parameters = array();
		$reshook = $hookmanager->executeHooks(
			'printFieldListSelect',
			$parameters,
			$object,
			$action
		);
		if ($reshook < 0) {
			setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
		}
		$sql .= $hookmanager->resPrint;
		$sql .= " FROM ".MAIN_DB_PREFIX."commande_fournisseurdet as l";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product as p ON l.fk_product=p.rowid";
		$sql .= " WHERE l.fk_commande = ".((int) $objectsrc->id);
		if (!getDolGlobalString('STOCK_SUPPORTS_SERVICES')) {
			$sql .= " AND l.product_type = 0";
		}
		// Enable hooks to alter the SQL query (WHERE)
		$parameters = array();
		$reshook = $hookmanager->executeHooks(
			'printFieldListWhere',
			$parameters,
			$object,
			$action
		);
		if ($reshook < 0) {
			setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
		}
		$sql .= $hookmanager->resPrint;

		//$sql .= " GROUP BY p.ref, p.label, p.tobatch, p.fk_default_warehouse, l.rowid, l.fk_product, l.subprice, l.remise_percent, l.ref"; // Calculation of amount dispatched is done per fk_product so we must group by fk_product
		$sql .= " ORDER BY l.rang, p.ref, p.label";

		$resql = $db->query($sql);
		if ($resql) {
			$num = $db->num_rows($resql);
			$i = 0;

			if ($num) {
				print '<tr class="liste_titre">';

				print '<td>'.$langs->trans("Description").'</td>';
				if (isModEnabled('productbatch')) {
					print '<td class="dispatch_batch_number_title">'.$langs->trans("batch_number").'</td>';
					if (!getDolGlobalString('PRODUCT_DISABLE_SELLBY')) {
						print '<td class="dispatch_dlc_title">'.$langs->trans("SellByDate").'</td>';
					}
					if (!getDolGlobalString('PRODUCT_DISABLE_EATBY')) {
						print '<td class="dispatch_dluo_title">'.$langs->trans("EatByDate").'</td>';
					}
				} else {
					print '<td></td>';
					print '<td></td>';
					print '<td></td>';
				}
				print '<td class="right">'.$langs->trans("SupplierRef").'</td>';
				print '<td class="right">'.$langs->trans("QtyOrdered").'</td>';
				if ($object->status == Reception::STATUS_DRAFT) {
					print '<td class="right">'.$langs->trans("QtyToReceive");	// Qty to dispatch (sum for all lines of batch detail if there is)
				} else {
					print '<td class="right">'.$langs->trans("QtyDispatchedShort").'</td>';
				}
				print '<td class="right">'.$langs->trans("Details");
				print '<td width="32"></td>';

				if (getDolGlobalString('SUPPLIER_ORDER_CAN_UPDATE_BUYINGPRICE_DURING_RECEIPT')) {
					if (!isModEnabled("multicurrency") && empty($conf->dynamicprices->enabled)) {
						print '<td class="right">'.$langs->trans("Price").'</td>';
						print '<td class="right">'.$langs->trans("ReductionShort").' (%)</td>';
						print '<td class="right">'.$langs->trans("UpdatePrice").'</td>';
					}
				}

				print '<td align="right">'.$langs->trans("Warehouse");

				// Select warehouse to force it everywhere
				if (count($listwarehouses) > 1) {
					print '<br><span class="opacitymedium">'.$langs->trans("ForceTo").'</span> '.$form->selectarray('fk_default_warehouse', $listwarehouses, $fk_default_warehouse, 1, 0, 0, '', 0, 0, $disabled, '', 'minwidth100 maxwidth300', 1);
				} elseif (count($listwarehouses) == 1) {
					print '<br><span class="opacitymedium">'.$langs->trans("ForceTo").'</span> '.$form->selectarray('fk_default_warehouse', $listwarehouses, $fk_default_warehouse, 0, 0, 0, '', 0, 0, $disabled, '', 'minwidth100 maxwidth300', 1);
				}

				print '</td>';

				// Enable hooks to append additional columns
				$parameters = array();
				$reshook = $hookmanager->executeHooks(
					'printFieldListTitle',
					$parameters,
					$object,
					$action
				);
				if ($reshook < 0) {
					setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
				}
				print $hookmanager->resPrint;

				print "</tr>\n";
			}

			$nbfreeproduct = 0; // Nb of lines of free products/services
			$nbproduct = 0; // Nb of predefined product lines to dispatch (already done or not) if SUPPLIER_ORDER_DISABLE_STOCK_DISPATCH_WHEN_TOTAL_REACHED is off (default)
			// or nb of line that remain to dispatch if SUPPLIER_ORDER_DISABLE_STOCK_DISPATCH_WHEN_TOTAL_REACHED is on.

			$conf->cache['product'] = array();

			// Loop on each source order line (may be more or less than current number of lines in llx_commande_fournisseurdet)
			while ($i < $num) {
				$objp = $db->fetch_object($resql);

				// On n'affiche pas les produits libres
				if (!$objp->fk_product > 0) {
					$nbfreeproduct++;
				} else {
					$alreadydispatched = isset($products_dispatched[$objp->rowid]) ? $products_dispatched[$objp->rowid] : 0;
					$remaintodispatch = price2num($objp->qty, 5); // Calculation of dispatched
					if ($remaintodispatch < 0 && !getDolGlobalString('SUPPLIER_ORDER_ALLOW_NEGATIVE_QTY_FOR_SUPPLIER_ORDER_RETURN')) {
						$remaintodispatch = 0;
					}

					if ($remaintodispatch || !getDolGlobalString('SUPPLIER_ORDER_DISABLE_STOCK_DISPATCH_WHEN_TOTAL_REACHED')) {
						$nbproduct++;

						// To show detail cref and description value, we must make calculation by cref
						// print ($objp->cref?' ('.$objp->cref.')':'');
						// if ($objp->description) print '<br>'.nl2br($objp->description);
						$suffix = '_0_'.$i;

						print "\n";
						print '<!-- Line to dispatch '.$suffix.' -->'."\n";
						// hidden fields for js function
						print '<input id="qty_ordered'.$suffix.'" type="hidden" value="'.$objp->qty.'">';
						print '<input id="qty_dispatched'.$suffix.'" type="hidden" data-dispatched="'.((float) $alreadydispatched).'" value="'.(float) $alreadydispatched.'">';
						print '<tr class="oddeven">';

						if (empty($conf->cache['product'][$objp->fk_product])) {
							$tmpproduct = new Product($db);
							$tmpproduct->fetch($objp->fk_product);
							$conf->cache['product'][$objp->fk_product] = $tmpproduct;
						} else {
							$tmpproduct = $conf->cache['product'][$objp->fk_product];
						}

						$linktoprod = $tmpproduct->getNomUrl(1);
						$linktoprod .= ' - '.$objp->label."\n";

						if (isModEnabled('productbatch')) {
							if ($objp->tobatch) {
								// Product
								print '<td>';
								print $linktoprod;
								print "</td>";
								print '<td class="dispatch_batch_number"></td>';
								if (!getDolGlobalString('PRODUCT_DISABLE_SELLBY')) {
									print '<td class="dispatch_dlc"></td>';
								}
								if (!getDolGlobalString('PRODUCT_DISABLE_EATBY')) {
									print '<td class="dispatch_dluo"></td>';
								}
							} else {
								// Product
								print '<td>';
								print $linktoprod;
								print "</td>";
								print '<td class="dispatch_batch_number">';
								print '<span class="opacitymedium small">'.$langs->trans("ProductDoesNotUseBatchSerial").'</small>';
								print '</td>';
								if (!getDolGlobalString('PRODUCT_DISABLE_SELLBY')) {
									print '<td class="dispatch_dlc"></td>';
								}
								if (!getDolGlobalString('PRODUCT_DISABLE_EATBY')) {
									print '<td class="dispatch_dluo"></td>';
								}
							}
						} else {
							print '<td colspan="4">';
							print $linktoprod;
							print "</td>";
						}

						// Define unit price for PMP calculation
						$up_ht_disc = $objp->subprice;
						if (!empty($objp->remise_percent) && !getDolGlobalString('STOCK_EXCLUDE_DISCOUNT_FOR_PMP')) {
							$up_ht_disc = price2num($up_ht_disc * (100 - $objp->remise_percent) / 100, 'MU');
						}

						// Supplier ref
						print '<td class="right">'.$objp->sref.'</td>';

						// Qty ordered
						print '<td class="right">'.$objp->qty.'</td>';

						// Already dispatched
						print '<td class="right">'.$alreadydispatched.'</td>';

						print '<td class="right">';
						print '</td>'; // Qty to dispatch
						print '<td>';
						print '</td>'; // Dispatch column
						print '<td></td>'; // Warehouse column

						$sql = "SELECT cfd.rowid, cfd.qty, cfd.fk_entrepot, cfd.batch, cfd.eatby, cfd.sellby, cfd.fk_product";
						$sql .= " FROM ".MAIN_DB_PREFIX."receptiondet_batch as cfd";
						$sql .= " WHERE cfd.fk_reception = ".((int) $object->id);
						$sql .= " AND cfd.fk_element = ".((int) $objectsrc->id);
						$sql .= " AND cfd.fk_elementdet = ".(int) $objp->rowid;

						//print $sql;
						$resultsql = $db->query($sql);
						$j = 0;
						if ($resultsql) {
							$numd = $db->num_rows($resultsql);

							while ($j < $numd) {
								$suffix = "_".$j."_".$i;
								$objd = $db->fetch_object($resultsql);

								if (isModEnabled('productbatch') && (!empty($objd->batch) || (is_null($objd->batch) && $tmpproduct->status_batch > 0))) {
									$type = 'batch';

									// Enable hooks to append additional columns
									$parameters = array(
										// allows hook to distinguish between the rows with information and the rows with dispatch form input
										'is_information_row' => true,
										'j' => $j,
										'suffix' => $suffix,
										'objd' => $objd,
									);
									$reshook = $hookmanager->executeHooks(
										'printFieldListValue',
										$parameters,
										$object,
										$action
									);
									if ($reshook < 0) {
										setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
									}
									print $hookmanager->resPrint;

									print '</tr>';

									print '<!-- line for batch '.$numline.' -->';
									print '<tr class="oddeven autoresettr" name="'.$type.$suffix.'" data-remove="clear">';
									print '<td>';
									print '<input id="fk_commandefourndet'.$suffix.'" name="fk_commandefourndet'.$suffix.'" type="hidden" value="'.$objp->rowid.'">';
									print '<input id="idline'.$suffix.'" name="idline'.$suffix.'" type="hidden" value="'.$objd->rowid.'">';
									print '<input name="product_batch'.$suffix.'" type="hidden" value="'.$objd->fk_product.'">';

									print '<!-- This is a U.P. (may include discount or not depending on STOCK_EXCLUDE_DISCOUNT_FOR_PMP. will be used for PMP calculation) -->';
									if (getDolGlobalString('SUPPLIER_ORDER_EDIT_BUYINGPRICE_DURING_RECEIPT')) { // Not tested !
										print $langs->trans("BuyingPrice").': <input class="maxwidth75" name="pu'.$suffix.'" type="text" value="'.price2num($up_ht_disc, 'MU').'">';
									} else {
										print '<input class="maxwidth75" name="pu'.$suffix.'" type="hidden" value="'.price2num($up_ht_disc, 'MU').'">';
									}

									print '</td>';

									print '<td>';
									print '<input disabled="" type="text" class="inputlotnumber quatrevingtquinzepercent" id="lot_number'.$suffix.'" name="lot_number'.$suffix.'" value="'.(GETPOSTISSET('lot_number'.$suffix) ? GETPOST('lot_number'.$suffix) : $objd->batch).'">';
									print '</td>';
									if (!getDolGlobalString('PRODUCT_DISABLE_SELLBY')) {
										print '<td class="nowraponall">';
										$dlcdatesuffix = !empty($objd->sellby) ? dol_stringtotime($objd->sellby) : dol_mktime(0, 0, 0, GETPOST('dlc'.$suffix.'month'), GETPOST('dlc'.$suffix.'day'), GETPOST('dlc'.$suffix.'year'));
										print $form->selectDate($dlcdatesuffix, 'dlc'.$suffix, 0, 0, 1, '');
										print '</td>';
									}
									if (!getDolGlobalString('PRODUCT_DISABLE_EATBY')) {
										print '<td class="nowraponall">';
										$dluodatesuffix = !empty($objd->eatby) ? dol_stringtotime($objd->eatby) : dol_mktime(0, 0, 0, GETPOST('dluo'.$suffix.'month'), GETPOST('dluo'.$suffix.'day'), GETPOST('dluo'.$suffix.'year'));
										print $form->selectDate($dluodatesuffix, 'dluo'.$suffix, 0, 0, 1, '');
										print '</td>';
									}
									print '<td colspan="3">&nbsp;</td>'; // Supplier ref + Qty ordered + qty already dispatched
								} else {
									$type = 'dispatch';
									$colspan = 7;
									$colspan = (getDolGlobalString('PRODUCT_DISABLE_SELLBY')) ? --$colspan : $colspan;
									$colspan = (getDolGlobalString('PRODUCT_DISABLE_EATBY')) ? --$colspan : $colspan;

									// Enable hooks to append additional columns
									$parameters = array(
										// allows hook to distinguish between the rows with information and the rows with dispatch form input
										'is_information_row' => true,
										'j' => $j,
										'suffix' => $suffix,
										'objd' => $objd,
									);
									$reshook = $hookmanager->executeHooks(
										'printFieldListValue',
										$parameters,
										$object,
										$action
									);
									if ($reshook < 0) {
										setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
									}
									print $hookmanager->resPrint;

									print '</tr>';

									print '<!-- line no batch '.$numline.' -->';
									print '<tr class="oddeven autoresettr" name="'.$type.$suffix.'" data-remove="clear">';
									print '<td colspan="'.$colspan.'">';
									print '<input id="fk_commandefourndet'.$suffix.'" name="fk_commandefourndet'.$suffix.'" type="hidden" value="'.$objp->rowid.'">';
									print '<input id="idline'.$suffix.'" name="idline'.$suffix.'" type="hidden" value="'.$objd->rowid.'">';
									print '<input name="product'.$suffix.'" type="hidden" value="'.$objd->fk_product.'">';

									print '<!-- This is a up (may include discount or not depending on STOCK_EXCLUDE_DISCOUNT_FOR_PMP. will be used for PMP calculation) -->';
									if (getDolGlobalString('SUPPLIER_ORDER_EDIT_BUYINGPRICE_DURING_RECEIPT')) { // Not tested !
										print $langs->trans("BuyingPrice").': <input class="maxwidth75" name="pu'.$suffix.'" type="text" value="'.price2num($up_ht_disc, 'MU').'">';
									} else {
										print '<input class="maxwidth75" name="pu'.$suffix.'" type="hidden" value="'.price2num($up_ht_disc, 'MU').'">';
									}

									print '</td>';
								}
								// Qty to dispatch
								print '<td class="right">';
								print '<a href="#" id="reset'.$suffix.'" class="resetline">'.img_picto($langs->trans("Reset"), 'eraser', 'class="pictofixedwidth opacitymedium"').'</a>';
								print '<input id="qty'.$suffix.'" onchange="onChangeDispatchLineQty($(this))" name="qty'.$suffix.'" data-type="'.$type.'" data-index="'.$i.'" class="width50 right qtydispatchinput" value="'.(GETPOSTISSET('qty'.$suffix) ? GETPOSTINT('qty'.$suffix) : $objd->qty).'" data-expected="'.$objd->qty.'">';
								print '</td>';
								print '<td>';
								if (isModEnabled('productbatch') && $objp->tobatch > 0) {
									$type = 'batch';
									print img_picto($langs->trans('AddStockLocationLine'), 'split.png', 'class="splitbutton" '.($numd != $j + 1 ? 'style="display:none"' : '').' onClick="addDispatchLine('.$i.', \''.$type.'\')"');
								} else {
									$type = 'dispatch';
									print img_picto($langs->trans('AddStockLocationLine'), 'split.png', 'class="splitbutton" '.($numd != $j + 1 ? 'style="display:none"' : '').' onClick="addDispatchLine('.$i.', \''.$type.'\')"');
								}

								print '</td>';

								if (getDolGlobalString('SUPPLIER_ORDER_CAN_UPDATE_BUYINGPRICE_DURING_RECEIPT')) {
									if (!isModEnabled("multicurrency") && empty($conf->dynamicprices->enabled)) {
										// Price
										print '<td class="right">';
										print '<input id="pu'.$suffix.'" name="pu'.$suffix.'" type="text" size="8" value="'.price((GETPOST('pu'.$suffix) != '' ? price2num(GETPOST('pu'.$suffix)) : $up_ht_disc)).'">';
										print '</td>';

										// Discount
										print '<td class="right">';
										print '<input id="dto'.$suffix.'" name="dto'.$suffix.'" type="text" size="8" value="'.(GETPOST('dto'.$suffix) != '' ? GETPOST('dto'.$suffix) : '').'">';
										print '</td>';

										// Save price
										print '<td class="center">';
										print '<input class="flat checkformerge" type="checkbox" name="saveprice'.$suffix.'" value="'.(GETPOST('saveprice'.$suffix) != '' ? GETPOST('saveprice'.$suffix) : '').'">';
										print '</td>';
									}
								}

								// Warehouse
								print '<td class="right">';
								if (count($listwarehouses) > 1) {
									print $formproduct->selectWarehouses(GETPOST("entrepot".$suffix) ? GETPOST("entrepot".$suffix) : $objd->fk_entrepot, "entrepot".$suffix, '', 1, 0, $objp->fk_product, '', 1, 0, array(), 'csswarehouse'.$suffix);
								} elseif (count($listwarehouses) == 1) {
									print $formproduct->selectWarehouses(GETPOST("entrepot".$suffix) ? GETPOST("entrepot".$suffix) : $objd->fk_entrepot, "entrepot".$suffix, '', 0, 0, $objp->fk_product, '', 1, 0, array(), 'csswarehouse'.$suffix);
								} else {
									$langs->load("errors");
									print $langs->trans("ErrorNoWarehouseDefined");
								}
								print "</td>\n";

								// Enable hooks to append additional columns
								$parameters = array(
									'is_information_row' => false, // this is a dispatch form row
									'i' => $i,
									'suffix' => $suffix,
									'objp' => $objp,
								);
								$reshook = $hookmanager->executeHooks(
									'printFieldListValue',
									$parameters,
									$object,
									$action
								);
								if ($reshook < 0) {
									setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
								}
								print $hookmanager->resPrint;

								print "</tr>\n";
								$j++;

								$numline++;
							}
							$suffix = "_".$j."_".$i;
						}

						if ($j == 0) {
							if (isModEnabled('productbatch') && !empty($objp->tobatch)) {
								$type = 'batch';

								// Enable hooks to append additional columns
								$parameters = array(
									// allows hook to distinguish between the rows with information and the rows with dispatch form input
									'is_information_row' => true,
									'j' => $j,
									'suffix' => $suffix,
									'objp' => $objp,
								);
								$reshook = $hookmanager->executeHooks(
									'printFieldListValue',
									$parameters,
									$object,
									$action
								);
								if ($reshook < 0) {
									setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
								}
								print $hookmanager->resPrint;

								print '</tr>';

								print '<!-- line for batch '.$numline.' (not dispatched line yet for this order line) -->';
								print '<tr class="oddeven autoresettr" name="'.$type.$suffix.'">';
								print '<td>';
								print '<input id="fk_commandefourndet'.$suffix.'" name="fk_commandefourndet'.$suffix.'" type="hidden" value="'.$objp->rowid.'">';
								print '<input id="idline'.$suffix.'" name="idline'.$suffix.'" type="hidden" value="-1">';
								print '<input name="product_batch'.$suffix.'" type="hidden" value="'.$objp->fk_product.'">';

								print '<!-- This is a up (may include discount or not depending on STOCK_EXCLUDE_DISCOUNT_FOR_PMP. will be used for PMP calculation) -->';
								if (getDolGlobalString('SUPPLIER_ORDER_EDIT_BUYINGPRICE_DURING_RECEIPT')) { // Not tested !
									print $langs->trans("BuyingPrice").': <input class="maxwidth75" name="pu'.$suffix.'" type="text" value="'.price2num($up_ht_disc, 'MU').'">';
								} else {
									print '<input class="maxwidth75" name="pu'.$suffix.'" type="hidden" value="'.price2num($up_ht_disc, 'MU').'">';
								}

								print '</td>';

								print '<td>';
								print '<input type="text" class="inputlotnumber quatrevingtquinzepercent" id="lot_number'.$suffix.'" name="lot_number'.$suffix.'" value="'.GETPOST('lot_number'.$suffix).'">';
								print '</td>';
								if (!getDolGlobalString('PRODUCT_DISABLE_SELLBY')) {
									print '<td class="nowraponall">';
									$dlcdatesuffix = dol_mktime(0, 0, 0, GETPOST('dlc'.$suffix.'month'), GETPOST('dlc'.$suffix.'day'), GETPOST('dlc'.$suffix.'year'));
									print $form->selectDate($dlcdatesuffix, 'dlc'.$suffix, 0, 0, 1, '');
									print '</td>';
								}
								if (!getDolGlobalString('PRODUCT_DISABLE_EATBY')) {
									print '<td class="nowraponall">';
									$dluodatesuffix = dol_mktime(0, 0, 0, GETPOST('dluo'.$suffix.'month'), GETPOST('dluo'.$suffix.'day'), GETPOST('dluo'.$suffix.'year'));
									print $form->selectDate($dluodatesuffix, 'dluo'.$suffix, 0, 0, 1, '');
									print '</td>';
								}
								print '<td colspan="3">&nbsp;</td>'; // Supplier ref + Qty ordered + qty already dispatched
							} else {
								$type = 'dispatch';
								$colspan = 7;
								$colspan = (getDolGlobalString('PRODUCT_DISABLE_SELLBY')) ? --$colspan : $colspan;
								$colspan = (getDolGlobalString('PRODUCT_DISABLE_EATBY')) ? --$colspan : $colspan;

								// Enable hooks to append additional columns
								$parameters = array(
									// allows hook to distinguish between the rows with information and the rows with dispatch form input
									'is_information_row' => true,
									'j' => $j,
									'suffix' => $suffix,
									'objp' => $objp,
								);
								$reshook = $hookmanager->executeHooks(
									'printFieldListValue',
									$parameters,
									$object,
									$action
								);
								if ($reshook < 0) {
									setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
								}
								print $hookmanager->resPrint;

								print '</tr>';

								print '<!-- line no batch '.$numline.' (not dispatched line yet for this order line) -->';
								print '<tr class="oddeven autoresettr" name="'.$type.$suffix.'" data-remove="clear">';
								print '<td colspan="'.$colspan.'">';
								print '<input id="fk_commandefourndet'.$suffix.'" name="fk_commandefourndet'.$suffix.'" type="hidden" value="'.$objp->rowid.'">';
								print '<input id="idline'.$suffix.'" name="idline'.$suffix.'" type="hidden" value="-1">';
								print '<input name="product'.$suffix.'" type="hidden" value="'.$objp->fk_product.'">';

								print '<!-- This is a up (may include discount or not depending on STOCK_EXCLUDE_DISCOUNT_FOR_PMP. will be used for PMP calculation) -->';
								if (getDolGlobalString('SUPPLIER_ORDER_EDIT_BUYINGPRICE_DURING_RECEIPT')) { // Not tested !
									print $langs->trans("BuyingPrice").': <input class="maxwidth75" name="pu'.$suffix.'" data-type="text" value="'.price2num($up_ht_disc, 'MU').'">';
								} else {
									print '<input class="maxwidth75" name="pu'.$suffix.'" type="hidden" value="'.price2num($up_ht_disc, 'MU').'">';
								}

								print '</td>';
							}
							// Qty to dispatch
							print '<td class="right">';
							print '<a href="#" id="reset'.$suffix.'" class="resetline">'.img_picto($langs->trans("Reset"), 'eraser', 'class="pictofixedwidth opacitymedium"').'</a>';
							print '<input id="qty'.$suffix.'" onchange="onChangeDispatchLineQty($(this))" name="qty'.$suffix.'" data-index="'.$i.'" data-type="text" class="width50 right qtydispatchinput" value="'.(GETPOSTISSET('qty'.$suffix) ? GETPOSTINT('qty'.$suffix) : (!getDolGlobalString('SUPPLIER_ORDER_DISPATCH_FORCE_QTY_INPUT_TO_ZERO') ? $remaintodispatch : 0)).'" data-expected="'.$remaintodispatch.'">';
							print '</td>';
							print '<td>';
							if (isModEnabled('productbatch') && $objp->tobatch > 0) {
								$type = 'batch';
								print img_picto($langs->trans('AddStockLocationLine'), 'split.png', 'class="splitbutton" onClick="addDispatchLine('.$i.', \''.$type.'\')"');
							} else {
								$type = 'dispatch';
								print img_picto($langs->trans('AddStockLocationLine'), 'split.png', 'class="splitbutton" onClick="addDispatchLine('.$i.', \''.$type.'\')"');
							}

							print '</td>';

							if (getDolGlobalString('SUPPLIER_ORDER_CAN_UPDATE_BUYINGPRICE_DURING_RECEIPT')) {
								if (!isModEnabled("multicurrency") && empty($conf->dynamicprices->enabled)) {
									// Price
									print '<td class="right">';
									print '<input id="pu'.$suffix.'" name="pu'.$suffix.'" type="text" size="8" value="'.price((GETPOST('pu'.$suffix) != '' ? price2num(GETPOST('pu'.$suffix)) : $up_ht_disc)).'">';
									print '</td>';

									// Discount
									print '<td class="right">';
									print '<input id="dto'.$suffix.'" name="dto'.$suffix.'" type="text" size="8" value="'.(GETPOST('dto'.$suffix) != '' ? GETPOST('dto'.$suffix) : '').'">';
									print '</td>';

									// Save price
									print '<td class="center">';
									print '<input class="flat checkformerge" type="checkbox" name="saveprice'.$suffix.'" value="'.(GETPOST('saveprice'.$suffix) != '' ? GETPOST('saveprice'.$suffix) : '').'">';
									print '</td>';
								}
							}

							// Warehouse
							print '<td class="right">';
							if (count($listwarehouses) > 1) {
								print $formproduct->selectWarehouses(GETPOST("entrepot".$suffix) ? GETPOST("entrepot".$suffix) : ($objp->fk_default_warehouse ? $objp->fk_default_warehouse : ''), "entrepot".$suffix, '', 1, 0, $objp->fk_product, '', 1, 0, array(), 'csswarehouse'.$suffix);
							} elseif (count($listwarehouses) == 1) {
								print $formproduct->selectWarehouses(GETPOST("entrepot".$suffix) ? GETPOST("entrepot".$suffix) : ($objp->fk_default_warehouse ? $objp->fk_default_warehouse : ''), "entrepot".$suffix, '', 0, 0, $objp->fk_product, '', 1, 0, array(), 'csswarehouse'.$suffix);
							} else {
								$langs->load("errors");
								print $langs->trans("ErrorNoWarehouseDefined");
							}
							print "</td>\n";

							// Enable hooks to append additional columns
							$parameters = array(
								'is_information_row' => false, // this is a dispatch form row
								'i' => $i,
								'suffix' => $suffix,
								'objp' => $objp,
							);
							$reshook = $hookmanager->executeHooks(
								'printFieldListValue',
								$parameters,
								$object,
								$action
							);
							if ($reshook < 0) {
								setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
							}
							print $hookmanager->resPrint;
							print "</tr>\n";
						}
					}
				}
				$i++;
			}
			$db->free($resql);
		} else {
			dol_print_error($db);
		}

		print "</table>\n";
		print '</div>';

		if ($nbproduct) {
			print '<div class="center">';
			$parameters = array();
			$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been
			// modified by hook
			if (empty($reshook)) {
				/*$checkboxlabel = $langs->trans("CloseReceivedSupplierOrdersAutomatically", $langs->transnoentitiesnoconv('StatusOrderReceivedAll'));

				if (empty($conf->reception->enabled)) {
					print $langs->trans("Comment").' : ';
					print '<input type="text" class="minwidth400" maxlength="128" name="comment" value="';
					print GETPOSTISSET("comment") ? GETPOST("comment") : $langs->trans("DispatchSupplierOrder", $object->ref);
					// print ' / '.$object->ref_supplier; // Not yet available
					print '" class="flat"><br>';

					print '<input type="checkbox" checked="checked" name="closeopenorder"> '.$checkboxlabel;
				}

				$dispatchBt = empty($conf->reception->enabled) ? $langs->trans("Receive") : $langs->trans("CreateReception");

				print '<br>';
				*/

				print '<input type="submit" id="submitform" class="button" name="dispatch" value="'.$langs->trans("Save").'"';
				$disabled = 0;
				if (!$permissiontoreceive) {
					$disabled = 1;
				}
				if (count($listwarehouses) <= 0) {
					$disabled = 1;
				}
				if ($disabled) {
					print ' disabled';
				}

				print '>';
			}
			print '</div>';
		}

		// Message if nothing to dispatch
		if (!$nbproduct) {
			print "<br>\n";
			if (!getDolGlobalString('SUPPLIER_ORDER_DISABLE_STOCK_DISPATCH_WHEN_TOTAL_REACHED')) {
				print '<div class="opacitymedium">'.$langs->trans("NoPredefinedProductToDispatch").'</div>'; // No predefined line at all
			} else {
				print '<div class="opacitymedium">'.$langs->trans("NoMorePredefinedProductToDispatch").'</div>'; // No predefined line that remain to be dispatched.
			}
		}

		print '</form>';
	}

	print dol_get_fiche_end();

	// traitement entrepot par défaut
	print '<script type="text/javascript">
		$(document).ready(function () {
			$("select[name=fk_default_warehouse]").change(function() {
				var fk_default_warehouse = $("option:selected", this).val();
				$("select[name^=entrepot_]").val(fk_default_warehouse).change();
			});

			$("#autoreset").click(function() {
				$(".autoresettr").each(function(){
					id = $(this).attr("name");
					idtab = id.split("_");
					if ($(this).data("remove") == "clear"){
						console.log("We clear the object to expected value")
						$("#qty_"+idtab[1]+"_"+idtab[2]).val("");
						/*
						qtyexpected = $("#qty_"+idtab[1]+"_"+idtab[2]).data("expected")
						console.log(qtyexpected);
						$("#qty_"+idtab[1]+"_"+idtab[2]).val(qtyexpected);
						qtydispatched = $("#qty_dispatched_0_"+idtab[2]).data("dispatched")
						$("#qty_dispatched_0_"+idtab[2]).val(qtydispatched);
						*/
					} else {
						console.log("We remove the object")
						$(this).remove();
						$("tr[name^=\'"+idtab[0]+"_\'][name$=\'_"+idtab[2]+"\']:last .splitbutton").show();
					}
				});
				return false;
			});

			$("#resetalltoexpected").click(function(){
				$(".qtydispatchinput").each(function(){
					console.log("We reset to expected "+$(this).attr("id")+" qty to dispatch");
					$(this).val($(this).data("expected"));
				});
				return false;
			});

			$(".resetline").click(function(e){
				e.preventDefault();
				id = $(this).attr("id");
				id = id.split("reset_");
				console.log("Reset trigger for id = qty_"+id[1]);
				$("#qty_"+id[1]).val("");
				return false;
			});
		});
	</script>';
}

// End of page
llxFooter();
$db->close();
