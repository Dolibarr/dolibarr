<?php
/* Copyright (C) 2004-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2023 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2010-2021 Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2014      Cedric Gross         <c.gross@kreiz-it.fr>
 * Copyright (C) 2016      Florian Henry        <florian.henry@atm-consulting.fr>
 * Copyright (C) 2017-2022 Ferran Marcet        <fmarcet@2byte.es>
 * Copyright (C) 2018-2022 Frédéric France      <frederic.france@netlogic.fr>
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
 * \file htdocs/fourn/commande/dispatch.php
 * \ingroup commande
 * \brief Page to dispatch receiving
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/supplier_order/modules_commandefournisseur.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/fourn.lib.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.dispatch.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/productlot.class.php';

if (isModEnabled('project')) {
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
}

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

if ($user->socid) {
	$socid = $user->socid;
}

$hookmanager->initHooks(array('ordersupplierdispatch'));

// Recuperation de l'id de projet
$projectid = 0;
if (GETPOSTISSET("projectid")) {
	$projectid = GETPOSTINT("projectid");
}

$object = new CommandeFournisseur($db);

if ($id > 0 || !empty($ref)) {
	$result = $object->fetch($id, $ref);
	if ($result < 0) {
		setEventMessages($object->error, $object->errors, 'errors');
	}
	$result = $object->fetch_thirdparty();
	if ($result < 0) {
		setEventMessages($object->error, $object->errors, 'errors');
	}
}

if (empty($conf->reception->enabled)) {
	$permissiontoreceive = $user->hasRight("fournisseur", "commande", "receptionner");
	$permissiontocontrol = ((!getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && $user->hasRight("fournisseur", "commande", "receptionner")) || (getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && $user->hasRight("fournisseur", "commande_advance", "check")));
} else {
	$permissiontoreceive = $user->hasRight("reception", "creer");
	$permissiontocontrol = ((!getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && $user->hasRight("reception", "creer")) || (getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && $user->hasRight("reception", "reception_advance", "validate")));
}

// $id is id of a purchase order.
$result = restrictedArea($user, 'fournisseur', $object, 'commande_fournisseur', 'commande');

if (!isModEnabled('stock')) {
	accessforbidden();
}

$usercancreate	= ($user->hasRight("fournisseur", "commande", "creer") || $user->hasRight("supplier_order", "creer"));
$permissiontoadd	= $usercancreate; // Used by the include of actions_addupdatedelete.inc.php


/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if ($action == 'checkdispatchline' && $permissiontocontrol) {
	$error = 0;
	$supplierorderdispatch = new CommandeFournisseurDispatch($db);

	$db->begin();

	$result = $supplierorderdispatch->fetch($lineid);
	if (!$result) {
		$error++;
		setEventMessages($supplierorderdispatch->error, $supplierorderdispatch->errors, 'errors');
		$action = '';
	}

	if (!$error) {
		$result = $supplierorderdispatch->setStatut(1);
		if ($result < 0) {
			setEventMessages($supplierorderdispatch->error, $supplierorderdispatch->errors, 'errors');
			$error++;
			$action = '';
		}
	}

	if (!$error) {
		$result = $object->calcAndSetStatusDispatch($user);
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
			$error++;
			$action = '';
		}
	}
	if (!$error) {
		$db->commit();
	} else {
		$db->rollback();
	}
}

if ($action == 'uncheckdispatchline' && $permissiontocontrol) {
	$error = 0;
	$supplierorderdispatch = new CommandeFournisseurDispatch($db);

	$db->begin();

	$result = $supplierorderdispatch->fetch($lineid);
	if (!$result) {
		$error++;
		setEventMessages($supplierorderdispatch->error, $supplierorderdispatch->errors, 'errors');
		$action = '';
	}

	if (!$error) {
		$result = $supplierorderdispatch->setStatut(0);
		if ($result < 0) {
			setEventMessages($supplierorderdispatch->error, $supplierorderdispatch->errors, 'errors');
			$error++;
			$action = '';
		}
	}
	if (!$error) {
		$result = $object->calcAndSetStatusDispatch($user);
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
			$error++;
			$action = '';
		}
	}
	if (!$error) {
		$db->commit();
	} else {
		$db->rollback();
	}
}

if ($action == 'denydispatchline' && $permissiontocontrol) {
	$error = 0;
	$supplierorderdispatch = new CommandeFournisseurDispatch($db);

	$db->begin();

	$result = $supplierorderdispatch->fetch($lineid);
	if (!$result) {
		$error++;
		setEventMessages($supplierorderdispatch->error, $supplierorderdispatch->errors, 'errors');
		$action = '';
	}

	if (!$error) {
		$result = $supplierorderdispatch->setStatut(2);
		if ($result < 0) {
			setEventMessages($supplierorderdispatch->error, $supplierorderdispatch->errors, 'errors');
			$error++;
			$action = '';
		}
	}
	if (!$error) {
		$result = $object->calcAndSetStatusDispatch($user);
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
			$error++;
			$action = '';
		}
	}
	if (!$error) {
		$db->commit();
	} else {
		$db->rollback();
	}
}

if ($action == 'dispatch' && $permissiontoreceive) {
	$error = 0;
	$notrigger = 0;

	$db->begin();

	$pos = 0;
	foreach ($_POST as $key => $value) {
		// without batch module enabled
		$reg = array();
		if (preg_match('/^product_([0-9]+)_([0-9]+)$/i', $key, $reg)) {
			$pos++;

			// $numline=$reg[2] + 1; // line of product
			$numline = $pos;
			$prod = "product_".$reg[1].'_'.$reg[2];
			$qty = "qty_".$reg[1].'_'.$reg[2];
			$ent = "entrepot_".$reg[1].'_'.$reg[2];
			if (empty(GETPOST($ent))) {
				$ent = $fk_default_warehouse;
			}
			$pu = "pu_".$reg[1].'_'.$reg[2]; // This is unit price including discount
			$fk_commandefourndet = "fk_commandefourndet_".$reg[1].'_'.$reg[2];

			if (getDolGlobalString('SUPPLIER_ORDER_CAN_UPDATE_BUYINGPRICE_DURING_RECEIPT')) {
				if (!isModEnabled("multicurrency") && empty($conf->dynamicprices->enabled)) {
					$dto = GETPOSTINT("dto_".$reg[1].'_'.$reg[2]);
					if (!empty($dto)) {
						$unit_price = price2num(GETPOST("pu_".$reg[1]) * (100 - $dto) / 100, 'MU');
					}
					$saveprice = "saveprice_".$reg[1].'_'.$reg[2];
				}
			}

			// We ask to move a qty
			$qtytomove = GETPOSTFLOAT($qty);
			$puformove = GETPOSTFLOAT($pu);
			if ($qtytomove != 0) {
				if (!(GETPOSTINT($ent) > 0)) {
					dol_syslog('No dispatch for line '.$key.' as no warehouse was chosen.');
					$text = $langs->transnoentities('Warehouse').', '.$langs->transnoentities('Line').' '.($numline);
					setEventMessages($langs->trans('ErrorFieldRequired', $text), null, 'errors');
					$error++;
				}

				if (!$error) {
					$result = $object->dispatchProduct($user, GETPOSTINT($prod), $qtytomove, GETPOSTINT($ent), $puformove, GETPOST('comment'), '', '', '', GETPOSTINT($fk_commandefourndet), $notrigger);
					if ($result < 0) {
						setEventMessages($object->error, $object->errors, 'errors');
						$error++;
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
		// with batch module enabled
		if (preg_match('/^product_batch_([0-9]+)_([0-9]+)$/i', $key, $reg)) {
			$pos++;

			// eat-by date dispatch
			// $numline=$reg[2] + 1; // line of product
			$numline = $pos;
			$prod = 'product_batch_'.$reg[1].'_'.$reg[2];
			$qty = 'qty_'.$reg[1].'_'.$reg[2];
			$ent = 'entrepot_'.$reg[1].'_'.$reg[2];
			$pu = 'pu_'.$reg[1].'_'.$reg[2];
			$fk_commandefourndet = 'fk_commandefourndet_'.$reg[1].'_'.$reg[2];
			$lot = 'lot_number_'.$reg[1].'_'.$reg[2];
			$dDLUO = dol_mktime(12, 0, 0, GETPOSTINT('dluo_'.$reg[1].'_'.$reg[2].'month'), GETPOSTINT('dluo_'.$reg[1].'_'.$reg[2].'day'), GETPOSTINT('dluo_'.$reg[1].'_'.$reg[2].'year'));
			$dDLC = dol_mktime(12, 0, 0, GETPOSTINT('dlc_'.$reg[1].'_'.$reg[2].'month'), GETPOSTINT('dlc_'.$reg[1].'_'.$reg[2].'day'), GETPOSTINT('dlc_'.$reg[1].'_'.$reg[2].'year'));

			$fk_commandefourndet = 'fk_commandefourndet_'.$reg[1].'_'.$reg[2];

			if (getDolGlobalString('SUPPLIER_ORDER_CAN_UPDATE_BUYINGPRICE_DURING_RECEIPT')) {
				if (!isModEnabled("multicurrency") && empty($conf->dynamicprices->enabled)) {
					$dto = GETPOSTINT("dto_".$reg[1].'_'.$reg[2]);
					if (!empty($dto)) {
						$unit_price = price2num(GETPOST("pu_".$reg[1]) * (100 - $dto) / 100, 'MU');
					}
					$saveprice = "saveprice_".$reg[1].'_'.$reg[2];
				}
			}

			// We ask to move a qty
			$qtytomove = GETPOSTFLOAT($qty);
			$puformove = GETPOSTFLOAT($pu);
			if ($qtytomove > 0) {
				$productId = GETPOSTINT($prod);

				if (!(GETPOSTINT($ent) > 0)) {
					dol_syslog('No dispatch for line '.$key.' as no warehouse was chosen.');
					$text = $langs->transnoentities('Warehouse').', '.$langs->transnoentities('Line').' '.($numline).'-'.((int) $reg[1] + 1);
					setEventMessages($langs->trans('ErrorFieldRequired', $text), null, 'errors');
					$error++;
				}

				// check sell-by / eat-by date is mandatory
				/* Not required. Mandatory is checked when we insert the lot. Once lot has been recorded and is known, user can just enter the lot/serial
				$errorMsgArr = Productlot::checkSellOrEatByMandatoryFromProductIdAndDates($productId, $dDLC, $dDLUO);
				if (!(GETPOST($lot, 'alpha')) || !empty($errorMsgArr)) {
					dol_syslog('No dispatch for line '.$key.' as serial/eat-by/sellby date are not set');
					$text = $langs->transnoentities('atleast1batchfield').', '.$langs->transnoentities('Line').' '.($numline).'-'.($reg[1] + 1);
					setEventMessages($langs->trans('ErrorFieldRequired', $text), null, 'errors');
					$error++;
				}*/
				if (!GETPOST($lot, 'alpha') && !$dDLUO && !$dDLC) {
					dol_syslog('No dispatch for line '.$key.' as serial/eat-by/sellby date are not set');
					$text = $langs->transnoentities('atleast1batchfield').', '.$langs->transnoentities('Line').' '.($numline).'-'.((int) $reg[1] + 1);
					setEventMessages($langs->trans('ErrorFieldRequired', $text), null, 'errors');
					$error++;
				}

				if (!$error) {
					$result = $object->dispatchProduct($user, $productId, $qtytomove, GETPOSTINT($ent), $puformove, GETPOST('comment'), $dDLUO, $dDLC, GETPOST($lot, 'alpha'), GETPOSTINT($fk_commandefourndet), $notrigger);
					if ($result < 0) {
						setEventMessages($object->error, $object->errors, 'errors');
						$error++;
					}

					if (!$error && getDolGlobalString('SUPPLIER_ORDER_CAN_UPDATE_BUYINGPRICE_DURING_RECEIPT')) {
						if (!isModEnabled("multicurrency") && empty($conf->dynamicprices->enabled)) {
							$dto = GETPOSTINT("dto_".$reg[1].'_'.$reg[2]);
							//update supplier price
							if (GETPOSTISSET($saveprice)) {
								// TODO Use class
								$sql = "UPDATE ".MAIN_DB_PREFIX."product_fournisseur_price";
								$sql .= " SET unitprice = ".price2num(GETPOST($pu), 'MU', 2);
								$sql .= ", price = ".price2num(GETPOST($pu), 'MU', 2)." * quantity";
								$sql .= ", remise_percent = ".price2num((empty($dto) ? 0 : $dto), 3, 2)."'";
								$sql .= " WHERE fk_soc = ".((int) $object->socid);
								$sql .= " AND fk_product=".((int) $productId);

								$resql = $db->query($sql);
							}
						}
					}
				}
			}
		}
	}

	if (!$error) {
		$result = $object->calcAndSetStatusDispatch($user, GETPOST('closeopenorder') ? 1 : 0, GETPOST('comment'));
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
			$error++;
		}
	}

	if ($result >= 0 && !$error) {
		$db->commit();

		setEventMessages($langs->trans("ReceptionsRecorded"), null, 'mesgs');

		header("Location: dispatch.php?id=".$id);
		exit();
	} else {
		$db->rollback();
	}
}

// Remove a dispatched line
if ($action == 'confirm_deleteline' && $confirm == 'yes' && $permissiontoreceive) {
	$db->begin();

	$supplierorderdispatch = new CommandeFournisseurDispatch($db);
	$result = $supplierorderdispatch->fetch($lineid);
	if ($result > 0) {
		$qty = $supplierorderdispatch->qty;
		$entrepot = $supplierorderdispatch->fk_entrepot;
		$product = $supplierorderdispatch->fk_product;
		$price = price2num(GETPOST('price', 'alpha'), 'MU');
		$comment = $supplierorderdispatch->comment;
		$eatby = $supplierorderdispatch->eatby;
		$sellby = $supplierorderdispatch->sellby;
		$batch = $supplierorderdispatch->batch;

		$result = $supplierorderdispatch->delete($user);
	}
	if ($result < 0) {
		$errors = $object->errors;
		$error++;
	} else {
		// If module stock is enabled and the stock increase is done on purchase order dispatching
		if ($entrepot > 0 && isModEnabled('stock') && getDolGlobalString('STOCK_CALCULATE_ON_SUPPLIER_DISPATCH_ORDER') && empty($supplierorderdispatch->fk_reception)) {
			$mouv = new MouvementStock($db);
			if ($product > 0) {
				$mouv->origin = &$object;
				$mouv->setOrigin($object->element, $object->id);
				$result = $mouv->livraison($user, $product, $entrepot, $qty, $price, $comment, '', $eatby, $sellby, $batch);
				if ($result < 0) {
					$errors = $mouv->errors;
					$error++;
				}
			}
		}
	}
	if ($error > 0) {
		$db->rollback();
		setEventMessages($error, $errors, 'errors');
	} else {
		$db->commit();
	}
}

// Update a dispatched line
if ($action == 'updateline' && $permissiontoreceive && empty($cancel)) {
	$db->begin();
	$error = 0;

	$supplierorderdispatch = new CommandeFournisseurDispatch($db);
	$result = $supplierorderdispatch->fetch($lineid);
	if ($result > 0) {
		$qty = $supplierorderdispatch->qty;
		$entrepot = $supplierorderdispatch->fk_entrepot;
		$product = $supplierorderdispatch->fk_product;
		$price = GETPOSTFLOAT('price');
		$comment = $supplierorderdispatch->comment;
		$eatby = $supplierorderdispatch->eatby;
		$sellby = $supplierorderdispatch->sellby;
		$batch = $supplierorderdispatch->batch;

		$supplierorderdispatch->qty = GETPOSTFLOAT('qty', 'MS');
		$supplierorderdispatch->fk_entrepot = GETPOSTINT('fk_entrepot');
		$result = $supplierorderdispatch->update($user);
	}
	if ($result < 0) {
		$error++;
		$errors = $supplierorderdispatch->errors;
	} else {
		// If module stock is enabled and the stock increase is done on purchase order dispatching
		if ($entrepot > 0 && isModEnabled('stock') && getDolGlobalString('STOCK_CALCULATE_ON_SUPPLIER_DISPATCH_ORDER')) {
			$mouv = new MouvementStock($db);
			if ($product > 0) {
				$mouv->origin = &$object;
				$mouv->setOrigin($object->element, $object->id);
				$result = $mouv->livraison($user, $product, $entrepot, $qty, $price, $comment, '', $eatby, $sellby, $batch);
				if ($result < 0) {
					$errors = $mouv->errors;
					$error++;
				} else {
					$mouv->origin = &$object;
					$result = $mouv->reception($user, $product, $supplierorderdispatch->fk_entrepot, $supplierorderdispatch->qty, $price, $comment, $eatby, $sellby, $batch);
					if ($result < 0) {
						$errors = $mouv->errors;
						$error++;
					}
				}
			}
		}
	}
	if ($error > 0) {
		$db->rollback();
		setEventMessages($error, $errors, 'errors');
	} else {
		$db->commit();
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

$title = $object->ref." - ".$langs->trans('OrderDispatch');
$help_url = 'EN:Module_Suppliers_Orders|FR:CommandeFournisseur|ES:Módulo_Pedidos_a_proveedores';
$morejs = array('/fourn/js/lib_dispatch.js.php');

llxHeader('', $title, $help_url, '', 0, 0, $morejs, '', '', 'mod-supplier-order page-card_dispatch');

if ($id > 0 || !empty($ref)) {
	$soc = new Societe($db);
	$soc->fetch($object->socid);

	$author = new User($db);
	$author->fetch($object->user_author_id);

	$head = ordersupplier_prepare_head($object);

	$title = $langs->trans("SupplierOrder");
	print dol_get_fiche_head($head, 'dispatch', $title, -1, 'order');

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

	// Supplier order card

	$linkback = '<a href="'.DOL_URL_ROOT.'/fourn/commande/list.php'.(!empty($socid) ? '?socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

	$morehtmlref = '<div class="refidno">';
	// Ref supplier
	$morehtmlref .= $form->editfieldkey("RefSupplier", 'ref_supplier', $object->ref_supplier, $object, 0, 'string', '', 0, 1);
	$morehtmlref .= $form->editfieldval("RefSupplier", 'ref_supplier', $object->ref_supplier, $object, 0, 'string', '', null, null, '', 1);
	// Thirdparty
	$morehtmlref .= '<br>'.$object->thirdparty->getNomUrl(1);
	// Project
	if (isModEnabled('project')) {
		$langs->load("projects");
		$morehtmlref .= '<br>';
		if (0) {
			$morehtmlref .= img_picto($langs->trans("Project"), 'project', 'class="pictofixedwidth"');
			if ($action != 'classify' && $caneditproject) {
				$morehtmlref .= '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?action=classify&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetProject')).'</a> ';
			}
			$morehtmlref .= $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, (!getDolGlobalString('PROJECT_CAN_ALWAYS_LINK_TO_ALL_SUPPLIERS') ? $object->socid : -1), $object->fk_project, ($action == 'classify' ? 'projectid' : 'none'), 0, 0, 0, 1, '', 'maxwidth300');
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
	print '<div class="underbanner clearboth"></div>';

	print '<table class="border tableforfield" width="100%">';

	// Date
	if ($object->methode_commande_id > 0) {
		print '<tr><td class="titlefield">'.$langs->trans("Date").'</td><td>';
		if ($object->date_commande) {
			print dol_print_date($object->date_commande, "dayhour")."\n";
		}
		print "</td></tr>";

		if ($object->methode_commande) {
			print '<tr><td>'.$langs->trans("Method").'</td><td>'.$object->getInputMethod().'</td></tr>';
		}
	}

	// Author
	print '<tr><td class="titlefield">'.$langs->trans("AuthorRequest").'</td>';
	print '<td>'.$author->getNomUrl(1, '', 0, 0, 0).'</td>';
	print '</tr>';

	$parameters = array();
	$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); // Note that $action and $object may have been modified by hook

	print "</table>";

	print '</div>';

	// if ($mesg) print $mesg;
	print '<br>';

	/*$disabled = 1;
	if (!empty($conf->global->STOCK_CALCULATE_ON_SUPPLIER_DISPATCH_ORDER)) {
		$disabled = 0;
	}*/
	$disabled = 0;	// This is used to disable or not the bulk selection of target warehouse. No reason to have it disabled so forced to 0.

	// Line of orders
	if ($object->statut <= CommandeFournisseur::STATUS_ACCEPTED || $object->statut >= CommandeFournisseur::STATUS_CANCELED) {
		print '<br><span class="opacitymedium">'.$langs->trans("OrderStatusNotReadyToDispatch").'</span>';
	}


	print '<br>';


	if ($object->statut == CommandeFournisseur::STATUS_ORDERSENT
		|| $object->statut == CommandeFournisseur::STATUS_RECEIVED_PARTIALLY
		|| $object->statut == CommandeFournisseur::STATUS_RECEIVED_COMPLETELY) {
		require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
		$formproduct = new FormProduct($db);
		$formproduct->loadWarehouses();
		$entrepot = new Entrepot($db);
		$listwarehouses = $entrepot->list_array(1);


		if (empty($conf->reception->enabled)) {
			print '<form method="POST" action="dispatch.php?id='.$object->id.'">';
		} else {
			print '<form method="post" action="'.dol_buildpath('/reception/card.php', 1).'?originid='.$object->id.'&origin=supplierorder">';
		}

		print '<input type="hidden" name="token" value="'.newToken().'">';
		if (empty($conf->reception->enabled)) {
			print '<input type="hidden" name="action" value="dispatch">';
		} else {
			print '<input type="hidden" name="action" value="create">';
		}

		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder centpercent">';

		// Set $products_dispatched with qty dispatched for each product id
		$products_dispatched = array();
		$sql = "SELECT l.rowid, cfd.fk_product, sum(cfd.qty) as qty";
		$sql .= " FROM ".MAIN_DB_PREFIX."receptiondet_batch as cfd";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."commande_fournisseurdet as l on l.rowid = cfd.fk_elementdet";
		$sql .= " WHERE cfd.fk_element = ".((int) $object->id);
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
		$sql .= " WHERE l.fk_commande = ".((int) $object->id);
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
				print '<td class="right">'.$langs->trans("QtyDispatchedShort").'</td>';
				print ' <td class="right">'.$langs->trans("QtyToDispatchShort");
				print '<br><a href="#" id="autoreset">'.img_picto($langs->trans("Reset"), 'eraser', 'class="pictofixedwidth opacitymedium"').$langs->trans("Reset").'</a></td>';
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
					print '<br>'.$form->selectarray('fk_default_warehouse', $listwarehouses, $fk_default_warehouse, $langs->trans("ForceTo"), 0, 0, '', 0, 0, $disabled, '', 'minwidth100 maxwidth300', 1);
				} elseif (count($listwarehouses) == 1) {
					print '<br>'.$form->selectarray('fk_default_warehouse', $listwarehouses, $fk_default_warehouse, 0, 0, 0, '', 0, 0, $disabled, '', 'minwidth100 maxwidth300', 1);
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

			// Loop on each source order line (may be more or less than current number of lines in llx_commande_fournisseurdet)
			while ($i < $num) {
				$objp = $db->fetch_object($resql);

				// On n'affiche pas les produits libres
				if (!$objp->fk_product > 0) {
					$nbfreeproduct++;
				} else {
					$alreadydispatched = isset($products_dispatched[$objp->rowid]) ? $products_dispatched[$objp->rowid] : 0;
					$remaintodispatch = price2num($objp->qty - ((float) $alreadydispatched), 5); // Calculation of dispatched
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
						print '<input id="qty_dispatched'.$suffix.'" type="hidden" value="'.(float) $alreadydispatched.'">';
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

						if (isModEnabled('productbatch') && $objp->tobatch > 0) {
							$type = 'batch';
							print '<td class="right">';
							print '</td>'; // Qty to dispatch
							print '<td>';
							//print img_picto($langs->trans('AddDispatchBatchLine'), 'split.png', 'class="splitbutton" onClick="addDispatchLine(' . $i . ',\'' . $type . '\')"');
							print '</td>'; // Dispatch column
							print '<td></td>'; // Warehouse column

							// Enable hooks to append additional columns
							$parameters = array(
								// allows hook to distinguish between the rows with information and the rows with dispatch form input
								'is_information_row' => true,
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

							print '</tr>';

							print '<tr class="oddeven" name="'.$type.$suffix.'">';
							print '<td>';
							print '<input name="fk_commandefourndet'.$suffix.'" type="hidden" value="'.$objp->rowid.'">';
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
							print '<td class="right">';
							print '</td>'; // Qty to dispatch
							print '<td>';
							//print img_picto($langs->trans('AddStockLocationLine'), 'split.png', 'class="splitbutton" onClick="addDispatchLine(' . $i . ',\'' . $type . '\')"');
							print '</td>'; // Dispatch column
							print '<td></td>'; // Warehouse column

							// Enable hooks to append additional columns
							$parameters = array(
								// allows hook to distinguish between the rows with information and the rows with dispatch form input
								'is_information_row' => true,
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

							print '</tr>';

							print '<tr class="oddeven" name="'.$type.$suffix.'">';
							print '<td colspan="'.$colspan.'">';
							print '<input name="fk_commandefourndet'.$suffix.'" type="hidden" value="'.$objp->rowid.'">';
							print '<input name="product'.$suffix.'" type="hidden" value="'.$objp->fk_product.'">';

							print '<!-- This is a up (may include discount or not depending on STOCK_EXCLUDE_DISCOUNT_FOR_PMP. will be used for PMP calculation) -->';
							if (getDolGlobalString('SUPPLIER_ORDER_EDIT_BUYINGPRICE_DURING_RECEIPT')) { // Not tested !
								print $langs->trans("BuyingPrice").': <input class="maxwidth75" name="pu'.$suffix.'" type="text" value="'.price2num($up_ht_disc, 'MU').'">';
							} else {
								print '<input class="maxwidth75" name="pu'.$suffix.'" type="hidden" value="'.price2num($up_ht_disc, 'MU').'">';
							}

							print '</td>';
						}

						// Qty to dispatch
						print '<td class="right nowrap">';
						if ($remaintodispatch>0) {
							$btnLabel = $langs->trans("Fill").' : '.$remaintodispatch;
							print '<button class="auto-fill-qty btn-low-emphasis --btn-icon" data-rowname="qty'.$suffix.'" data-value="'.$remaintodispatch.'" title="'.dol_escape_htmltag($btnLabel).'" aria-label="'.dol_escape_htmltag($btnLabel).'" >'.img_picto($btnLabel, 'fa-arrow-right', 'aria-hidden="true"', 0, 0, 1).'</button>';
						}
						print '<input id="qty'.$suffix.'" name="qty'.$suffix.'" type="number" step="any" class="width50 right qtydispatchinput" value="'.(GETPOSTISSET('qty'.$suffix) ? GETPOSTINT('qty'.$suffix) : (!getDolGlobalString('SUPPLIER_ORDER_DISPATCH_FORCE_QTY_INPUT_TO_ZERO') ? $remaintodispatch : 0)).'">';
						print '<button class="resetline btn-low-emphasis --btn-icon" id="reset'.$suffix.'" title="'.dol_escape_htmltag($langs->trans("Reset")).'" >'.img_picto($langs->trans("Reset"), 'eraser', 'aria-hidden="true"', 0, 0, 1).'</button>';
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
				$i++;
			}
			$db->free($resql);
		} else {
			dol_print_error($db);
		}

		print "</table>\n";
		print '</div>';

		if ($nbproduct) {
			$checkboxlabel = $langs->trans("CloseReceivedSupplierOrdersAutomatically", $langs->transnoentitiesnoconv('StatusOrderReceivedAll'));

			print '<div class="center">';
			$parameters = array();
			$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been
			// modified by hook
			if (empty($reshook)) {
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
				print '<input type="hidden" name="backtopageforcancel" value="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'">';
				print '<input type="submit" class="button" name="dispatch" value="'.dol_escape_htmltag($dispatchBt).'"';
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

				$(".auto-fill-qty").on("click touchstart", function(e){
					e.preventDefault();
					$("input[name="+$(this).data("rowname")+"]").val($(this).data("value")).trigger("change");
				});

	            $("#autoreset").click(function() {
					$(".qtydispatchinput").each(function(){
						id = $(this).attr("id");
						idtab = id.split("_");
						if(idtab[1] == 0){
							console.log(idtab);
							$(this).val("");
							$("#qty_dispatched_0_"+idtab[2]).val("0");
						} else {
							obj = $(this).parent().parent();
							nameobj = obj.attr("name");
							nametab = nameobj.split("_");
							obj.remove();
							$("tr[name^=\'"+nametab[0]+"_\'][name$=\'_"+nametab[2]+"\']:last .splitbutton").show();
						}
					});
                });

				$(".resetline").click(function(e){
					e.preventDefault();
					id = $(this).attr("id");
					id = id.split("reset_");
					console.log("Reset trigger for id = qty_"+id[1]);
					$("#qty_"+id[1]).val("");
				});
			});
		</script>';

	// List of lines already dispatched
	$sql = "SELECT p.rowid as pid, p.ref, p.label,";
	$sql .= " e.rowid as warehouse_id, e.ref as entrepot,";
	$sql .= " cfd.rowid as dispatchlineid, cfd.fk_product, cfd.qty, cfd.eatby, cfd.sellby, cfd.batch, cfd.comment, cfd.status, cfd.datec";
	$sql .= " ,cd.rowid, cd.subprice";
	if (isModEnabled('reception')) {
		$sql .= " ,cfd.fk_reception, r.date_delivery";
	}
	$sql .= " FROM ".MAIN_DB_PREFIX."product as p,";
	$sql .= " ".MAIN_DB_PREFIX."receptiondet_batch as cfd";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."commande_fournisseurdet as cd ON cd.rowid = cfd.fk_elementdet";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."entrepot as e ON cfd.fk_entrepot = e.rowid";
	if (isModEnabled('reception')) {
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."reception as r ON cfd.fk_reception = r.rowid";
	}
	$sql .= " WHERE cfd.fk_element = ".((int) $object->id);
	$sql .= " AND cfd.fk_product = p.rowid";
	$sql .= " ORDER BY cfd.rowid ASC";

	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		$i = 0;

		if ($num > 0) {
			print "<br>\n";

			print load_fiche_titre($langs->trans("ReceivingForSameOrder"));

			print '<div class="div-table-responsive">';
			print '<table id="dispatch_received_products" class="noborder centpercent">';

			print '<tr class="liste_titre">';
			// Reception ref
			if ($conf->reception->enabled) {
				print '<td>'.$langs->trans("Reception").'</td>';
			}
			// Product
			print '<td>'.$langs->trans("Product").'</td>';
			print '<td class="center">'.$langs->trans("DateCreation").'</td>';
			print '<td class="center">'.$langs->trans("DateDeliveryPlanned").'</td>';
			if (isModEnabled('productbatch')) {
				print '<td class="dispatch_batch_number_title">'.$langs->trans("batch_number").'</td>';
				if (!getDolGlobalString('PRODUCT_DISABLE_SELLBY')) {
					print '<td class="dispatch_dlc_title">'.$langs->trans("SellByDate").'</td>';
				}
				if (!getDolGlobalString('PRODUCT_DISABLE_EATBY')) {
					print '<td class="dispatch_dluo_title">'.$langs->trans("EatByDate").'</td>';
				}
			}
			print '<td class="right">'.$langs->trans("QtyDispatched").'</td>';
			print '<td>'.$langs->trans("Warehouse").'</td>';
			print '<td>'.$langs->trans("Comment").'</td>';

			// Status
			if (getDolGlobalString('SUPPLIER_ORDER_USE_DISPATCH_STATUS') && empty($reception->rowid)) {
				print '<td class="center" colspan="2">'.$langs->trans("Status").'</td>';
			} elseif (isModEnabled("reception")) {
				print '<td class="center"></td>';
			}

			print '<td class="center" colspan="2"></td>';

			print "</tr>\n";


			while ($i < $num) {
				$objp = $db->fetch_object($resql);

				if ($action == 'editline' && $lineid == $objp->dispatchlineid) {
					print '<form name="editdispatchedlines" id="editdispatchedlines" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'#line_'.GETPOSTINT('lineid').'" method="POST">
					<input type="hidden" name="token" value="'.newToken().'">
					<input type="hidden" name="action" value="updateline">
					<input type="hidden" name="mode" value="">
					<input type="hidden" name="lineid" value="'.$objp->dispatchlineid.'">';
				}

				print '<tr class="oddeven" id="line_'.$objp->dispatchlineid.'" >';

				// Reception ref
				if (isModEnabled("reception")) {
					print '<td class="nowraponall">';
					if (!empty($objp->fk_reception)) {
						$reception = new Reception($db);
						$reception->fetch($objp->fk_reception);
						print $reception->getNomUrl(1);
					}

					print "</td>";
				}

				// Product
				print '<td class="tdoverflowmax150">';
				if (empty($conf->cache['product'][$objp->fk_product])) {
					$tmpproduct = new Product($db);
					$tmpproduct->fetch($objp->fk_product);
					$conf->cache['product'][$objp->fk_product] = $tmpproduct;
				} else {
					$tmpproduct = $conf->cache['product'][$objp->fk_product];
				}
				print $tmpproduct->getNomUrl(1);
				print ' - '.$objp->label;
				print "</td>\n";

				// Date creation
				print '<td class="center">'.dol_print_date($db->jdate($objp->datec), 'day').'</td>';

				// Date delivery
				print '<td class="center">'.dol_print_date($db->jdate($objp->date_delivery), 'day').'</td>';

				// Batch / Eat by / Sell by
				if (isModEnabled('productbatch')) {
					if ($objp->batch) {
						include_once DOL_DOCUMENT_ROOT.'/product/stock/class/productlot.class.php';
						$lot = new Productlot($db);
						$lot->fetch(0, $objp->pid, $objp->batch);
						print '<td class="dispatch_batch_number">'.$lot->getNomUrl(1).'</td>';
						if (!getDolGlobalString('PRODUCT_DISABLE_SELLBY')) {
							print '<td class="dispatch_dlc">'.dol_print_date($lot->sellby, 'day').'</td>';
						}
						if (!getDolGlobalString('PRODUCT_DISABLE_EATBY')) {
							print '<td class="dispatch_dluo">'.dol_print_date($lot->eatby, 'day').'</td>';
						}
					} else {
						print '<td class="dispatch_batch_number"></td>';
						if (!getDolGlobalString('PRODUCT_DISABLE_SELLBY')) {
							print '<td class="dispatch_dlc"></td>';
						}
						if (!getDolGlobalString('PRODUCT_DISABLE_EATBY')) {
							print '<td class="dispatch_dluo"></td>';
						}
					}
				}

				// Qty
				print '<td class="right">';
				if ($action == 'editline' && $lineid == $objp->dispatchlineid) {
					print '<input style="width: 50px;" type="text" min="1" name="qty" value="'.$objp->qty.'" />';
				} else {
					print $objp->qty;
				}
				print '<input type="hidden" name="price" value="'.$objp->subprice.'" />';
				print '</td>';

				// Warehouse
				print '<td class="tdoverflowmax150">';
				if ($action == 'editline' && $lineid == $objp->dispatchlineid) {
					if (count($listwarehouses) > 1) {
						print $formproduct->selectWarehouses(GETPOST("fk_entrepot") ? GETPOST("fk_entrepot") : ($objp->warehouse_id ? $objp->warehouse_id : ''), "fk_entrepot", '', 1, 0, $objp->fk_product, '', 1, 1, null, 'csswarehouse');
					} elseif (count($listwarehouses) == 1) {
						print $formproduct->selectWarehouses(GETPOST("fk_entrepot") ? GETPOST("fk_entrepot") : ($objp->warehouse_id ? $objp->warehouse_id : ''), "fk_entrepot", '', 0, 0, $objp->fk_product, '', 1, 1, null, 'csswarehouse');
					} else {
						$langs->load("errors");
						print $langs->trans("ErrorNoWarehouseDefined");
					}
				} else {
					$warehouse_static->id = $objp->warehouse_id;
					$warehouse_static->label = $objp->entrepot;
					print $warehouse_static->getNomUrl(1);
				}
				print '</td>';

				// Comment
				print '<td class="tdoverflowmax300" style="white-space: pre;">'.$objp->comment.'</td>';

				// Status
				if (getDolGlobalString('SUPPLIER_ORDER_USE_DISPATCH_STATUS') && empty($reception->rowid)) {
					print '<td class="right">';
					$supplierorderdispatch->status = (empty($objp->status) ? 0 : $objp->status);
					// print $supplierorderdispatch->status;
					print $supplierorderdispatch->getLibStatut(5);
					print '</td>';

					// Add button to check/uncheck disaptching
					print '<td class="center">';
					if (!$permissiontocontrol) {
						if (empty($objp->status)) {
							print '<a class="button buttonRefused" href="#">'.$langs->trans("Approve").'</a>';
							print '<a class="button buttonRefused" href="#">'.$langs->trans("Deny").'</a>';
						} else {
							print '<a class="button buttonRefused" href="#">'.$langs->trans("Disapprove").'</a>';
							print '<a class="button buttonRefused" href="#">'.$langs->trans("Deny").'</a>';
						}
					} else {
						$disabled = '';
						if ($object->statut == 5) {
							$disabled = 1;
						}
						if (empty($objp->status)) {
							print '<a class="button'.($disabled ? ' buttonRefused' : '').'" href="'.$_SERVER["PHP_SELF"]."?id=".$id."&action=checkdispatchline&lineid=".$objp->dispatchlineid.'">'.$langs->trans("Approve").'</a>';
							print '<a class="button'.($disabled ? ' buttonRefused' : '').'" href="'.$_SERVER["PHP_SELF"]."?id=".$id."&action=denydispatchline&lineid=".$objp->dispatchlineid.'">'.$langs->trans("Deny").'</a>';
						}
						if ($objp->status == 1) {
							print '<a class="button'.($disabled ? ' buttonRefused' : '').'" href="'.$_SERVER["PHP_SELF"]."?id=".$id."&action=uncheckdispatchline&lineid=".$objp->dispatchlineid.'">'.$langs->trans("Reinit").'</a>';
							print '<a class="button'.($disabled ? ' buttonRefused' : '').'" href="'.$_SERVER["PHP_SELF"]."?id=".$id."&action=denydispatchline&lineid=".$objp->dispatchlineid.'">'.$langs->trans("Deny").'</a>';
						}
						if ($objp->status == 2) {
							print '<a class="button'.($disabled ? ' buttonRefused' : '').'" href="'.$_SERVER["PHP_SELF"]."?id=".$id."&action=uncheckdispatchline&lineid=".$objp->dispatchlineid.'">'.$langs->trans("Reinit").'</a>';
							print '<a class="button'.($disabled ? ' buttonRefused' : '').'" href="'.$_SERVER["PHP_SELF"]."?id=".$id."&action=checkdispatchline&lineid=".$objp->dispatchlineid.'">'.$langs->trans("Approve").'</a>';
						}
					}
					print '</td>';
				} elseif (isModEnabled("reception")) {
					print '<td class="right">';
					if (!empty($reception->id)) {
						print $reception->getLibStatut(5);
					}
					print '</td>';
				}

				// Action
				if ($action != 'editline' || $lineid != $objp->dispatchlineid) {
					if (empty($reception->id) || ($reception->statut == Reception::STATUS_DRAFT)) { // only allow edit on draft reception
						print '<td class="linecoledit center">';
						print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=editline&token='.newToken().'&lineid='.$objp->dispatchlineid.'#line_'.$objp->dispatchlineid.'">';
						print img_edit();
						print '</a>';
						print '</td>';

						print '<td class="linecoldelete center">';
						print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=ask_deleteline&token='.newToken().'&lineid='.$objp->dispatchlineid.'#dispatch_received_products">';
						print img_delete();
						print '</a>';
						print '</td>';
					} else {
						print '<td></td><td></td>';
					}
				} else {
					print '<td class="center valignmiddle">';
					print '<input type="submit" class="button button-save" id="savelinebutton" name="save" value="'.$langs->trans("Save").'" />';
					print '</td>';
					print '<td class="center valignmiddle">';
					print '<input type="submit" class="button button-cancel" id="cancellinebutton" name="cancel" value="'.$langs->trans("Cancel").'" />';
					print '</td>';
				}


				print "</tr>\n";
				if ($action == 'editline' && $lineid == $objp->dispatchlineid) {
					print '</form>';
				}

				$i++;
			}
			$db->free($resql);

			print "</table>\n";
			print '</div>';
		}
	} else {
		dol_print_error($db);
	}
}

// End of page
llxFooter();
$db->close();
