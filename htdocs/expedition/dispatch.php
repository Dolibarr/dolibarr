<?php
/* Copyright (C) 2004-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2016 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2010-2021 Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2014      Cedric Gross         <c.gross@kreiz-it.fr>
 * Copyright (C) 2016      Florian Henry        <florian.henry@atm-consulting.fr>
 * Copyright (C) 2017-2022 Ferran Marcet        <fmarcet@2byte.es>
 * Copyright (C) 2018-2022 Frédéric France      <frederic.france@netlogic.fr>
 * Copyright (C) 2019-2020 Christophe Battarel	<christophe@altairis.fr>
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
 * \file 	htdocs/expedition/dispatch.php
 * \ingroup expedition
 * \brief 	Page to dispatch shipments
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
require_once DOL_DOCUMENT_ROOT.'/core/lib/sendings.lib.php';
if (isModEnabled('project')) {
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
}

// Load translation files required by the page
$langs->loadLangs(array("sendings", "companies", "bills", 'deliveries', 'orders', 'stocks', 'other', 'propal', 'receptions'));

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

$hookmanager->initHooks(array('expeditiondispatch'));

// Recuperation de l'id de projet
$projectid = 0;
if (GETPOSTISSET("projectid")) {
	$projectid = GETPOSTINT("projectid");
}

$object = new Expedition($db);
$objectorder = new Commande($db);


if ($id > 0 || !empty($ref)) {
	$result = $object->fetch($id, $ref);
	if ($result <= 0) {
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
}

// $id is id of a purchase order.
$result = restrictedArea($user, 'expedition', $object, '');

if (!isModEnabled('stock')) {
	accessforbidden('Module stock disabled');
}

$usercancreate = $user->hasRight('expedition', 'creer');
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
if ($action == 'updatelines' && $usercancreate) {
	$db->begin();
	$error = 0;

	$expeditiondispatch = new ExpeditionLigne($db);
	$expeditionlinebatch = new ExpeditionLineBatch($db);

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
			$fk_commandedet = "fk_commandedet_".$reg[1].'_'.$reg[2];
			$idline = GETPOST("idline_".$reg[1].'_'.$reg[2]);
			$warehouse_id = GETPOSTINT($ent);
			$prod_id = GETPOSTINT($prod);
			//$pu = "pu_".$reg[1].'_'.$reg[2]; // This is unit price including discount
			$lot = '';
			$dDLUO = '';
			$dDLC = '';
			if ($modebatch == "batch") { //TODO: Make impossible to input non existing batch code
				$lot = GETPOST('lot_number_'.$reg[1].'_'.$reg[2]);
				$dDLUO = dol_mktime(12, 0, 0, GETPOSTINT('dluo_'.$reg[1].'_'.$reg[2].'month'), GETPOSTINT('dluo_'.$reg[1].'_'.$reg[2].'day'), GETPOSTINT('dluo_'.$reg[1].'_'.$reg[2].'year'));
				$dDLC = dol_mktime(12, 0, 0, GETPOSTINT('dlc_'.$reg[1].'_'.$reg[2].'month'), GETPOSTINT('dlc_'.$reg[1].'_'.$reg[2].'day'), GETPOSTINT('dlc_'.$reg[1].'_'.$reg[2].'year'));
			}

			$newqty = GETPOSTFLOAT($qty, 'MS');
			//var_dump("modebatch=".$modebatch." newqty=".$newqty." ent=".$ent." idline=".$idline);

			// We ask to move a qty
			if (($modebatch == "batch" && $newqty >= 0) || ($modebatch == "barcode" && $newqty != 0)) {
				if ($newqty > 0) {	// If we want a qty, we make test on input data
					if (!($warehouse_id > 0)) {
						dol_syslog('No dispatch for line '.$key.' as no warehouse was chosen.');
						$text = $langs->transnoentities('Warehouse').', '.$langs->transnoentities('Line').' '.($numline);
						setEventMessages($langs->trans('ErrorFieldRequired', $text), null, 'errors');
						$error++;
					}
					if (!$error && $modebatch == "batch") {
						$sql = "SELECT pb.rowid ";
						$sql .= " FROM ".MAIN_DB_PREFIX."product_batch as pb";
						$sql .= " JOIN ".MAIN_DB_PREFIX."product_stock as ps";
						$sql .= " ON ps.rowid = pb.fk_product_stock";
						$sql .= " WHERE pb.batch = '".$db->escape($lot)."'";
						$sql .= " AND ps.fk_product = ".((int) $prod_id) ;
						$sql .= " AND ps.fk_entrepot = ".((int) $warehouse_id) ;

						$resql = $db->query($sql);
						if ($resql) {
							$num = $db->num_rows($resql);
							if ($num > 1) {
								dol_syslog('No dispatch for line '.$key.' as too many combination warehouse, product, batch code was found ('.$num.').');
								setEventMessages($langs->trans('ErrorTooManyCombinationBatchcode', $numline, $num), null, 'errors');
								$error++;
							} elseif ($num < 1) {
								$tmpwarehouse = new Entrepot($db);
								$tmpwarehouse->fetch($warehouse_id);
								$tmpprod = new Product($db);
								$tmpprod->fetch($prod_id);
								dol_syslog('No dispatch for line '.$key.' as no combination warehouse, product, batch code was found.');
								setEventMessages($langs->trans('ErrorNoCombinationBatchcode', $numline, $tmpwarehouse->ref, $tmpprod->ref, $lot), null, 'errors');
								$error++;
							}
							$db->free($resql);
						}
					}
				}
				//var_dump($key.' '.$newqty.' '.$idline.' '.$error);

				if (!$error) {
					$qtystart = 0;

					if ($idline > 0) {
						$result = $expeditiondispatch->fetch($idline);	// get line from llx_expeditiondet
						if ($result < 0) {
							setEventMessages($expeditiondispatch->error, $expeditiondispatch->errors, 'errors');
							$error++;
						} else {
							$qtystart = $expeditiondispatch->qty;
							$expeditiondispatch->qty = $newqty;
							$expeditiondispatch->entrepot_id = GETPOSTINT($ent);

							if ($newqty > 0) {
								$result = $expeditiondispatch->update($user);
							} else {
								$result = $expeditiondispatch->delete($user);
							}
							if ($result < 0) {
								setEventMessages($expeditiondispatch->error, $expeditiondispatch->errors, 'errors');
								$error++;
							}

							if (!$error && $modebatch == "batch") {
								if ($newqty > 0) {
									$suffixkeyfordate = preg_replace('/^product_batch/', '', $key);
									$sellby = dol_mktime(0, 0, 0, GETPOST('dlc'.$suffixkeyfordate.'month'), GETPOST('dlc'.$suffixkeyfordate.'day'), GETPOST('dlc'.$suffixkeyfordate.'year'), '');
									$eatby = dol_mktime(0, 0, 0, GETPOST('dluo'.$suffixkeyfordate.'month'), GETPOST('dluo'.$suffixkeyfordate.'day'), GETPOST('dluo'.$suffixkeyfordate.'year'));

									$sqlsearchdet = "SELECT rowid FROM ".MAIN_DB_PREFIX.$expeditionlinebatch->table_element;
									$sqlsearchdet .= " WHERE fk_expeditiondet = ".((int) $idline);
									$sqlsearchdet .= " AND batch = '".$db->escape($lot)."'";
									$resqlsearchdet = $db->query($sqlsearchdet);

									if ($resqlsearchdet) {
										$objsearchdet = $db->fetch_object($resqlsearchdet);
									} else {
										dol_print_error($db);
									}

									if ($objsearchdet) {
										$sql = "UPDATE ".MAIN_DB_PREFIX.$expeditionlinebatch->table_element." SET";
										$sql .= " eatby = ".($eatby ? "'".$db->idate($eatby)."'" : "null");
										$sql .= " , sellby = ".($sellby ? "'".$db->idate($sellby)."'" : "null");
										$sql .= " , qty = ".((float) $newqty);
										$sql .= " , fk_warehouse = ".((int) $warehouse_id);
										$sql .= " WHERE rowid = ".((int) $objsearchdet->rowid);
									} else {
										$sql = "INSERT INTO ".MAIN_DB_PREFIX.$expeditionlinebatch->table_element." (";
										$sql .= "fk_expeditiondet, eatby, sellby, batch, qty, fk_origin_stock, fk_warehouse)";
										$sql .= " VALUES (".((int) $idline).", ".($eatby ? "'".$db->idate($eatby)."'" : "null").", ".($sellby ? "'".$db->idate($sellby)."'" : "null").", ";
										$sql .= " '".$db->escape($lot)."', ".((float) $newqty).", 0, ".((int) $warehouse_id).")";
									}
								} else {
									$sql = " DELETE FROM ".MAIN_DB_PREFIX.$expeditionlinebatch->table_element;
									$sql .= " WHERE fk_expeditiondet = ".((int) $idline);
									$sql .= " AND batch = '".$db->escape($lot)."'";
								}

								$resql = $db->query($sql);
								if (!$resql) {
									dol_print_error($db);
									$error++;
								}
							}
						}
					} else {
						$expeditiondispatch->fk_expedition = $object->id;
						$expeditiondispatch->entrepot_id = GETPOSTINT($ent);
						$expeditiondispatch->fk_elementdet = GETPOSTINT($fk_commandedet);
						$expeditiondispatch->qty = $newqty;

						if ($newqty > 0) {
							$idline = $expeditiondispatch->insert($user);
							if ($idline < 0) {
								setEventMessages($expeditiondispatch->error, $expeditiondispatch->errors, 'errors');
								$error++;
							}

							if ($modebatch == "batch" && !$error) {
								$expeditionlinebatch->sellby = $dDLUO;
								$expeditionlinebatch->eatby = $dDLC;
								$expeditionlinebatch->batch = $lot;
								$expeditionlinebatch->qty = $newqty;
								$expeditionlinebatch->fk_origin_stock = 0;
								$expeditionlinebatch->fk_warehouse = GETPOSTINT($ent);

								$result = $expeditionlinebatch->create($idline);
								if ($result < 0) {
									setEventMessages($expeditionlinebatch->error, $expeditionlinebatch->errors, 'errors');
									$error++;
								}
							}
						}
					}

					// If module stock is enabled and the stock decrease is done on edition of this page
					/*
					if (!$error && GETPOST($ent, 'int') > 0 && isModEnabled('stock') && !empty($conf->global->STOCK_CALCULATE_ON_SHIPMENT_DISPATCH_ORDER)) {
						$mouv = new MouvementStock($db);
						$product = GETPOST($prod, 'int');
						$entrepot = GETPOST($ent, 'int');
						$qtymouv = price2num(GETPOST($qty, 'alpha'), 'MS') - $qtystart;
						$price = price2num(GETPOST($pu), 'MU');
						$comment = GETPOST('comment');
						$inventorycode = dol_print_date(dol_now(), 'dayhourlog');
						$now = dol_now();
						$eatby = '';
						$sellby = '';
						$batch = '';
						if ($modebatch == "batch") {
							$eatby = $dDLUO;
							$sellby = $dDLC;
							$batch = $lot ;
						}
						if ($product > 0 && $qtymouv != 0) {
							// $price should take into account discount (except if option STOCK_EXCLUDE_DISCOUNT_FOR_PMP is on)
							$mouv->origin = $objectorder;
							$mouv->setOrigin($objectorder->element, $objectorder->id);

							// Method change if qty < 0
							if (!empty($conf->global->SUPPLIER_ORDER_ALLOW_NEGATIVE_QTY_FOR_SUPPLIER_ORDER_RETURN) && $qtymouv < 0) {
								$result = $mouv->reception($user, $product, $entrepot, $qtymouv*(-1), $price, $comment, $eatby, $sellby, $batch, '', 0, $inventorycode);
							} else {
								$result = $mouv->livraison($user, $product, $entrepot, $qtymouv, $price, $comment, $now, $eatby, $sellby, $batch, 0, $inventorycode);
							}

							if ($result < 0) {
								setEventMessages($mouv->error, $mouv->errors, 'errors');
								$error++;
							}
						}
					}
					*/
				}
			}
		}
	}

	if ($error > 0) {
		$db->rollback();
		setEventMessages($error, $errors, 'errors');
	} else {
		$db->commit();
		setEventMessages($langs->trans("ReceptionUpdated"), null);

		header("Location: ".DOL_URL_ROOT.'/expedition/dispatch.php?id='.$object->id);
		exit;
	}
} elseif ($action == 'setdate_livraison' && $usercancreate) {
	$datedelivery = dol_mktime(GETPOSTINT('liv_hour'), GETPOSTINT('liv_min'), 0, GETPOSTINT('liv_month'), GETPOSTINT('liv_day'), GETPOSTINT('liv_year'));

	$object->fetch($id);
	$result = $object->setDeliveryDate($user, $datedelivery);
	if ($result < 0) {
		setEventMessages($object->error, $object->errors, 'errors');
	}
}


/*
 * View
 */

$now = dol_now();

$form = new Form($db);
$formproduct = new FormProduct($db);
$warehouse_static = new Entrepot($db);

$title = $object->ref." - ".$langs->trans('ShipmentDistribution');
$help_url = 'EN:Module_Shipments|FR:Module_Expéditions|ES:M&oacute;dulo_Expediciones|DE:Modul_Lieferungen';
$morejs = array('/expedition/js/lib_dispatch.js.php');

llxHeader('', $title, $help_url, '', 0, 0, $morejs, '', '', 'mod-expedition page-card_dispatch');

if ($object->id > 0 || !empty($object->ref)) {
	$lines = $object->lines;	// This is an array of detail of line, on line per source order line found intolines[]->fk_elementdet, then each line may have sub data
	//var_dump($lines[0]->fk_elementdet); exit;

	$num_prod = count($lines);

	if (!empty($object->origin) && $object->origin_id > 0) {
		$object->origin = 'commande';
		$typeobject = $object->origin;
		$origin = $object->origin;

		$object->fetch_origin(); // Load property $object->origin_object, $object->commande, $object->propal, ...
	}
	$soc = new Societe($db);
	$soc->fetch($object->socid);

	$author = new User($db);
	$author->fetch($object->user_author_id);

	$head = shipping_prepare_head($object);

	print dol_get_fiche_head($head, 'dispatch', $langs->trans("Shipment"), -1, $object->picto);


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

	if ($typeobject == 'commande' && $object->origin_object->id && isModEnabled('order')) {
		$objectsrc = new Commande($db);
		$objectsrc->fetch($object->origin_object->id);
	}
	if ($typeobject == 'propal' && $object->origin_object->id && isModEnabled("propal")) {
		$objectsrc = new Propal($db);
		$objectsrc->fetch($object->origin_object->id);
	}

	// Shipment card
	$linkback = '<a href="'.DOL_URL_ROOT.'/expedition/list.php?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';
	$morehtmlref = '<div class="refidno">';

	// Ref customer shipment
	$morehtmlref .= $form->editfieldkey("RefCustomer", 'ref_customer', $object->ref_customer, $object, $user->hasRight('expedition', 'creer'), 'string', '', 0, 1);
	$morehtmlref .= $form->editfieldval("RefCustomer", 'ref_customer', $object->ref_customer, $object, $user->hasRight('expedition', 'creer'), 'string'.(isset($conf->global->THIRDPARTY_REF_INPUT_SIZE) ? ':' . getDolGlobalString('THIRDPARTY_REF_INPUT_SIZE') : ''), '', null, null, '', 1);

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

	print '<table class="border tableforfield centpercent">';

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
		print $objectsrc->getNomUrl(1, 'expedition');
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
		print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editdate_livraison&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->trans('SetDeliveryDate'), 1).'</a></td>';
	}
	print '</tr></table>';
	print '</td><td colspan="2">';
	if ($action == 'editdate_livraison') {
		print '<form name="setdate_livraison" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="post">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="action" value="setdate_livraison">';
		print $form->selectDate($object->date_delivery ? $object->date_delivery : -1, 'liv_', 1, 1, 0, "setdate_livraison", 1, 0);
		print '<input type="submit" class="button button-edit smallpaddingimp" value="'.$langs->trans('Modify').'">';
		print '</form>';
	} else {
		print $object->date_delivery ? dol_print_date($object->date_delivery, 'dayhour') : '&nbsp;';
	}
	print '</td>';
	print '</tr></table>';

	print '<br><center>';
	if (isModEnabled('barcode') || isModEnabled('productbatch')) {
		print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=updatebyscaning&token='.currentToken().'" class="marginrightonly paddingright marginleftonly paddingleft">'.img_picto('', 'barcode', 'class="paddingrightonly"').$langs->trans("UpdateByScaning").'</a>';
	}
	print '<a href="#" id="resetalltoexpected" class="marginrightonly paddingright marginleftonly paddingleft">'.img_picto("", 'autofill', 'class="pictofixedwidth"').$langs->trans("RestoreWithCurrentQtySaved").'</a></td>';
	// Link to clear qty
	print '<a href="#" id="autoreset" class="marginrightonly paddingright marginleftonly paddingleft">'.img_picto("", 'eraser', 'class="pictofixedwidth"').$langs->trans("ClearQtys").'</a></td>';
	print '<center>';

	print '<br>';
	$disabled = 0;	// This is used to disable or not the bulk selection of target warehouse. No reason to have it disabled so forced to 0.

	if ($object->statut == Expedition::STATUS_DRAFT) {
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

		// Get list of lines of the shipment $products_dispatched, with qty dispatched for each product id
		$products_dispatched = array();
		$sql = "SELECT ed.fk_elementdet as rowid, sum(ed.qty) as qty";
		$sql .= " FROM ".MAIN_DB_PREFIX."expeditiondet as ed";
		$sql .= " WHERE ed.fk_expedition = ".((int) $object->id);
		$sql .= " GROUP BY ed.fk_elementdet";

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
		$sql = "SELECT l.rowid, l.fk_product, l.subprice, l.remise_percent, '' AS sref, l.qty as qty,";
		$sql .= " p.ref, p.label, p.tobatch, p.fk_default_warehouse, p.barcode, p.stockable_product";
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

		$sql .= " FROM ".MAIN_DB_PREFIX."commandedet as l";
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
			$numline = 1;

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
				print '<td class="right">'.$langs->trans("QtyOrdered").'</td>';
				if ($object->status == Expedition::STATUS_DRAFT) {
					print '<td class="right">'.$langs->trans("QtyToShip");	// Qty to dispatch (sum for all lines of batch detail if there is)
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

			$nbfreeproduct = 0; // Nb of lins of free products/services
			$nbproduct = 0; // Nb of predefined product lines to dispatch (already done or not) if SUPPLIER_ORDER_DISABLE_STOCK_DISPATCH_WHEN_TOTAL_REACHED is off (default)
			// or nb of line that remain to dispatch if SUPPLIER_ORDER_DISABLE_STOCK_DISPATCH_WHEN_TOTAL_REACHED is on.

			$conf->cache['product'] = array();

			// Loop on each line of origin order
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
								print '<td id="product_'.$i.'" data-idproduct="'.$objp->fk_product.'" data-barcode="'.$objp->barcode.'">';
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
								print '<td id="product_'.$i.'" data-idproduct="'.$objp->fk_product.'" data-barcode="'.$objp->barcode.'">';
								print $linktoprod;
								print "</td>";
								print '<td class="dispatch_batch_number">';
								print '<span class="opacitymedium small">'.$langs->trans("ProductDoesNotUseBatchSerial").'</span>';
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

						// Qty ordered
						print '<td class="right">'.$objp->qty.'</td>';

						// Already dispatched
						print '<td class="right">'.$alreadydispatched.'</td>';

						print '<td class="right">';
						print '</td>'; // Qty to dispatch
						print '<td>';
						print '</td>'; // Dispatch column
						print '<td></td>'; // Warehouse column

						$sql = "SELECT ed.rowid, ed.qty, ed.fk_entrepot,";
						$sql .= " eb.batch, eb.eatby, eb.sellby, cd.fk_product";
						$sql .= " FROM ".MAIN_DB_PREFIX."expeditiondet as ed";
						$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."expeditiondet_batch as eb on ed.rowid = eb.fk_expeditiondet";
						$sql .= " JOIN ".MAIN_DB_PREFIX."commandedet as cd on ed.fk_elementdet = cd.rowid";
						$sql .= " WHERE ed.fk_elementdet =".(int) $objp->rowid;
						$sql .= " AND ed.fk_expedition =".(int) $object->id;
						$sql .= " ORDER BY ed.rowid, ed.fk_elementdet";

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
									print '<input id="fk_commandedet'.$suffix.'" name="fk_commandedet'.$suffix.'" type="hidden" value="'.$objp->rowid.'">';
									print '<input id="idline'.$suffix.'" name="idline'.$suffix.'" type="hidden" value="'.$objd->rowid.'">';
									print '<input name="product_batch'.$suffix.'" type="hidden" value="'.$objd->fk_product.'">';

									print '<!-- This is a U.P. (may include discount or not depending on STOCK_EXCLUDE_DISCOUNT_FOR_PMP. will be used for PMP calculation) -->';
									print '<input class="maxwidth75" name="pu'.$suffix.'" type="hidden" value="'.price2num($up_ht_disc, 'MU').'">';

									print '</td>';

									print '<td>';
									print '<input type="text" class="inputlotnumber quatrevingtquinzepercent" id="lot_number'.$suffix.'" name="lot_number'.$suffix.'" value="'.(GETPOSTISSET('lot_number'.$suffix) ? GETPOST('lot_number'.$suffix) : $objd->batch).'">';
									//print '<input type="hidden" id="lot_number'.$suffix.'" name="lot_number'.$suffix.'" value="'.$objd->batch.'">';
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
									print '<td colspan="2">&nbsp;</td>'; // Supplier ref + Qty ordered + qty already dispatched
								} else {
									$type = 'dispatch';
									$colspan = 6;
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
									print '<input id="fk_commandedet'.$suffix.'" name="fk_commandedet'.$suffix.'" type="hidden" value="'.$objp->rowid.'">';
									print '<input id="idline'.$suffix.'" name="idline'.$suffix.'" type="hidden" value="'.$objd->rowid.'">';
									print '<input name="product'.$suffix.'" type="hidden" value="'.$objd->fk_product.'">';
									print '<!-- This is a up (may include discount or not depending on STOCK_EXCLUDE_DISCOUNT_FOR_PMP. will be used for PMP calculation) -->';
									print '<input class="maxwidth75" name="pu'.$suffix.'" type="hidden" value="'.price2num($up_ht_disc, 'MU').'">';
									print '</td>';
								}
								// Qty to dispatch
								print '<td class="right nowraponall">';
								print '<a href="" id="reset'.$suffix.'" class="resetline">'.img_picto($langs->trans("Reset"), 'eraser', 'class="pictofixedwidth opacitymedium"').'</a>';
								$suggestedvalue = (GETPOSTISSET('qty'.$suffix) ? GETPOSTINT('qty'.$suffix) : $objd->qty);
								//var_dump($suggestedvalue);exit;
								print '<input id="qty'.$suffix.'" onchange="onChangeDispatchLineQty($(this))" name="qty'.$suffix.'" data-type="'.$type.'" data-index="'.$i.'" class="width50 right qtydispatchinput" value="'.$suggestedvalue.'" data-expected="'.$objd->qty.'">';
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

								// Warehouse
								print '<td class="right">';
								if ($objp->stockable_product == Product::ENABLED_STOCK){
									if (count($listwarehouses) > 1) {
										print $formproduct->selectWarehouses(GETPOST("entrepot".$suffix) ? GETPOST("entrepot".$suffix) : $objd->fk_entrepot, "entrepot".$suffix, '', 1, 0, $objp->fk_product, '', 1, 0, null, 'csswarehouse'.$suffix);
									} elseif (count($listwarehouses) == 1) {
										print $formproduct->selectWarehouses(GETPOST("entrepot".$suffix) ? GETPOST("entrepot".$suffix) : $objd->fk_entrepot, "entrepot".$suffix, '', 0, 0, $objp->fk_product, '', 1, 0, null, 'csswarehouse'.$suffix);
									} else {
										$langs->load("errors");
										print $langs->trans("ErrorNoWarehouseDefined");
									}
								} else {
									print '<input id="entrepot'.$suffix.'" name="entrepot'.$suffix.'" type="hidden" value="'.$objd->fk_entrepot.'">';
									print img_warning().' '.$langs->trans('StockDisabled') ;
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
								print '<tr class="oddeven autoresettr" name="'.$type.$suffix.'" data-remove="clear">';
								print '<td>';
								print '<input id="fk_commandedet'.$suffix.'" name="fk_commandedet'.$suffix.'" type="hidden" value="'.$objp->rowid.'">';
								print '<input id="idline'.$suffix.'" name="idline'.$suffix.'" type="hidden" value="-1">';
								print '<input name="product_batch'.$suffix.'" type="hidden" value="'.$objp->fk_product.'">';

								print '<!-- This is a up (may include discount or not depending on STOCK_EXCLUDE_DISCOUNT_FOR_PMP. will be used for PMP calculation) -->';
								print '<input class="maxwidth75" name="pu'.$suffix.'" type="hidden" value="'.price2num($up_ht_disc, 'MU').'">';
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
								print '<td colspan="2">&nbsp;</td>'; // Supplier ref + Qty ordered + qty already dispatched
							} else {
								$type = 'dispatch';
								$colspan = 6;
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
								print '<input id="fk_commandedet'.$suffix.'" name="fk_commandedet'.$suffix.'" type="hidden" value="'.$objp->rowid.'">';
								print '<input id="idline'.$suffix.'" name="idline'.$suffix.'" type="hidden" value="-1">';
								print '<input name="product'.$suffix.'" type="hidden" value="'.$objp->fk_product.'">';

								print '<!-- This is a up (may include discount or not depending on STOCK_EXCLUDE_DISCOUNT_FOR_PMP. will be used for PMP calculation) -->';
								print '<input class="maxwidth75" name="pu'.$suffix.'" type="hidden" value="'.price2num($up_ht_disc, 'MU').'">';
								print '</td>';
							}
							// Qty to dispatch
							print '<td class="right">';
							print '<a href="" id="reset'.$suffix.'" class="resetline">'.img_picto($langs->trans("Reset"), 'eraser', 'class="pictofixedwidth opacitymedium"').'</a>';
							$amounttosuggest = (GETPOSTISSET('qty'.$suffix) ? GETPOSTINT('qty'.$suffix) : (!getDolGlobalString('SUPPLIER_ORDER_DISPATCH_FORCE_QTY_INPUT_TO_ZERO') ? $remaintodispatch : 0));
							if (count($products_dispatched)) {
								// There is already existing lines into llx_expeditiondet, this means a plan for the shipment has already been started.
								// In such a case, we do not suggest new values, we suggest the value known.
								$amounttosuggest = (GETPOSTISSET('qty'.$suffix) ? GETPOSTINT('qty'.$suffix) : (isset($products_dispatched[$objp->rowid]) ? $products_dispatched[$objp->rowid] : ''));
							}
							print '<input id="qty'.$suffix.'" onchange="onChangeDispatchLineQty($(this))" name="qty'.$suffix.'" data-index="'.$i.'" data-type="text" class="width50 right qtydispatchinput" value="'.$amounttosuggest.'" data-expected="'.$amounttosuggest.'">';
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

							// Warehouse
							print '<td class="right">';
							if (count($listwarehouses) > 1) {
								print $formproduct->selectWarehouses(GETPOST("entrepot".$suffix) ? GETPOST("entrepot".$suffix) : ($objp->fk_default_warehouse ? $objp->fk_default_warehouse : ''), "entrepot".$suffix, '', 1, 0, $objp->fk_product, '', 1, 0, null, 'csswarehouse'.$suffix);
							} elseif (count($listwarehouses) == 1) {
								print $formproduct->selectWarehouses(GETPOST("entrepot".$suffix) ? GETPOST("entrepot".$suffix) : ($objp->fk_default_warehouse ? $objp->fk_default_warehouse : ''), "entrepot".$suffix, '', 0, 0, $objp->fk_product, '', 1, 0, null, 'csswarehouse'.$suffix);
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
			//$checkboxlabel = $langs->trans("CloseReceivedSupplierOrdersAutomatically", $langs->transnoentitiesnoconv('StatusOrderReceivedAll'));

			print '<div class="center">';
			$parameters = array();
			$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been
			// modified by hook
			if (empty($reshook)) {
				/*if (empty($conf->reception->enabled)) {
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
				if (!$usercancreate) {
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
				var warehousetouse = $("select[name=warehousenew]").val();
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
					$(".qtydispatchinput").each(function(){
						id = $(this).attr(\'id\');
						idarray = id.split(\'_\');
						idproduct = idarray[2];
						id = idarray[1] + \'_\' + idarray[2];
						console.log("Analyze the line "+id+" in inventory, barcodemode="+barcodemode);
						warehouse = $("#entrepot_"+id).val();
						console.log(warehouse);
						productbarcode = $("#product_"+idproduct).attr(\'data-barcode\');
						console.log(productbarcode);
						productbatchcode = $("#lot_number_"+id).val();
						if(productbatchcode == undefined){
							productbatchcode = "";
						}
						console.log(productbatchcode);

						if (barcodemode != "barcodeforproduct") {
							tabproduct.forEach(product=>{
								console.log("product.Batch="+product.Batch+" productbatchcode="+productbatchcode);
								if(product.Batch != "" && product.Batch == productbatchcode){
									console.log("duplicate batch code found for batch code "+productbatchcode);
									duplicatedbatchcode.push(productbatchcode);
								}
							})
						}
						productinput = $("#qty_"+id).val();
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
								verify_barcode = barcodeserialforproduct(tabproduct,index,element,barcodeproductqty,warehousetouse,selectaddorreplace,"barcode",true);
								verify_batch = barcodeserialforproduct(tabproduct,index,element,barcodeproductqty,warehousetouse,selectaddorreplace,"lotserial",true);
								break;
							case "barcodeforproduct":
								verify_barcode = barcodeserialforproduct(tabproduct,index,element,barcodeproductqty,warehousetouse,selectaddorreplace,"barcode");
								break;
							case "barcodeforlotserial":
								verify_batch = barcodeserialforproduct(tabproduct,index,element,barcodeproductqty,warehousetouse,selectaddorreplace,"lotserial");
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
								if(product.hasOwnProperty("reelqty")){
									idprod = $("td[data-idproduct=\'"+product.fk_product+"\']").attr("id");
									idproduct = idprod.split("_")[1];
									console.log("We create a new line for product_"+idproduct);
									if(product.Barcode != null){
										modedispatch = "dispatch";
									} else {
										modedispatch = "batch";
									}
									addDispatchLine(idproduct,modedispatch);
									console.log($("tr[name^=\'"+modedispatch+"_\'][name$=\'_"+idproduct+"\']"));
									nbrTrs = $("tr[name^=\'"+modedispatch+"_\'][name$=\'_"+idproduct+"\']").length;

									$("#qty_"+(nbrTrs-1)+"_"+idproduct).val(product.Qty);
									$("#entrepot_"+(nbrTrs-1)+"_"+idproduct).val(product.Warehouse);

									if(modedispatch == "batch"){
										$("#lot_number_"+(nbrTrs-1)+"_"+idproduct).val(product.Batch);
									}

								} else {
									console.log("We change #qty_"+product.Id +" to match input in scanner box");
									$("#qty_"+product.Id).val(product.Qty);
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
			function barcodeserialforproduct(tabproduct,index,element,barcodeproductqty,warehousetouse,selectaddorreplace,mode,autodetect=false){
				BarcodeIsInProduct=0;
				newproductrow=0
				result=false;
				tabproduct.forEach(product => {
					$.ajax({ url: \''.DOL_URL_ROOT.'/expedition/ajax/searchfrombarcode.php\',
						data: { "token":"'.newToken().'", "action":"existbarcode","fk_entrepot": warehousetouse, "barcode":element, "mode":mode},
						type: \'POST\',
						async: false,
						success: function(response) {
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
					testonwarehouse = product.Warehouse;
					if(testonproduct == element && testonwarehouse == warehousetouse){
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
		print $formother->getHTMLScannerForm("barcodescannerjs", 'all', 1);
	}

	// traitement entrepot par défaut
	print '<script type="text/javascript">
		$(document).ready(function () {
			$("select[name=fk_default_warehouse]").change(function() {
				console.log("warehouse is modified");
				var fk_default_warehouse = $("option:selected", this).val();
				$("select[name^=entrepot_]").val(fk_default_warehouse).change();
			});

			$("#autoreset").click(function() {
				console.log("we click on autoreset");
				$(".autoresettr").each(function(){
					id = $(this).attr("name");
					idtab = id.split("_");
					console.log("we process line "+id+" "+idtab);
					if ($(this).data("remove") == "clear") {	/* data-remove=clear means that line qty must be cleared but line must not be removed */
						console.log("We clear the object to expected value")
						$("#qty_"+idtab[1]+"_"+idtab[2]).val("");
						/*
						qtyexpected = $("#qty_"+idtab[1]+"_"+idtab[2]).data("expected")
						console.log(qtyexpected);
						$("#qty_"+idtab[1]+"_"+idtab[2]).val(qtyexpected);
						qtydispatched = $("#qty_dispatched_0_"+idtab[2]).data("dispatched")
						$("#qty_dispatched_0_"+idtab[2]).val(qtydispatched);
						*/
					} else {									/* data-remove=remove means that line must be removed */
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

			$(".resetline").on("click", function(event) {
				event.preventDefault();
				id = $(this).attr("id");
				id = id.split("reset_");
				console.log("Reset trigger for id = qty_"+id[1]);
				$("#qty_"+id[1]).val("");
			});
		});
	</script>';
}

// End of page
llxFooter();
$db->close();
