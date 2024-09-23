<?php
/* Copyright (C) 2004-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Eric	Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2016 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2010-2015 Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2011-2022 Philippe Grand       <philippe.grand@atoo-net.com>
 * Copyright (C) 2012-2016 Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2013      Florian Henry        <florian.henry@open-concept.pro>
 * Copyright (C) 2014      Ion Agorria          <ion@agorria.com>
 * Copyright (C) 2018-2024	Frédéric France      <frederic.france@free.fr>
 * Copyright (C) 2022      Gauthier VERDOL      <gauthier.verdol@atm-consulting.fr>
 * Copyright (C) 2022      Charlene Benke       <charlene@patas-monkey.com>
 * Copyright (C) 2023 	   Joachim Kueter       <git-jk@bloxera.com>
 * Copyright (C) 2024      MDW                  <mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024      Nick Fragoulis
 *
 * This	program	is free	software; you can redistribute it and/or modify
 * it under	the	terms of the GNU General Public	License	as published by
 * the Free	Software Foundation; either	version	2 of the License, or
 * (at your	option)	any	later version.
 *
 * This	program	is distributed in the hope that	it will	be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A	PARTICULAR PURPOSE.	 See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 * or see https://www.gnu.org/
 */

/**
 *    \file       htdocs/fourn/commande/card.php
 *    \ingroup    supplier, order
 *    \brief      Card supplier order
 */


// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formorder.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/fourn.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/supplier_order/modules_commandefournisseur.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';

if (isModEnabled('supplier_proposal')) {
	require_once DOL_DOCUMENT_ROOT.'/supplier_proposal/class/supplier_proposal.class.php';
}
if (isModEnabled("product")) {
	require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
}
if (isModEnabled('project')) {
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
}
require_once NUSOAP_PATH.'/nusoap.php'; // Include SOAP

if (isModEnabled('variants')) {
	require_once DOL_DOCUMENT_ROOT.'/variants/class/ProductCombination.class.php';
}


// Load translation files required by the page
$langs->loadLangs(array('admin', 'orders', 'sendings', 'companies', 'bills', 'propal', 'receptions', 'supplier_proposal', 'deliveries', 'products', 'stocks', 'productbatch'));
if (isModEnabled('incoterm')) {
	$langs->load('incoterm');
}


// Get Parameters
$id = GETPOSTINT('id');
$ref = GETPOST('ref', 'alpha');
$action      = GETPOST('action', 'alpha');
$confirm     = GETPOST('confirm', 'alpha');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'purchaseordercard'; // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');

$socid     = GETPOSTINT('socid');
$projectid = GETPOSTINT('projectid');
$cancel    = GETPOST('cancel', 'alpha');
$lineid    = GETPOSTINT('lineid');
$origin    = GETPOST('origin', 'alpha');
$originid  = (GETPOSTINT('originid') ? GETPOSTINT('originid') : GETPOSTINT('origin_id')); // For backward compatibility
$rank      = (GETPOSTINT('rank') > 0) ? GETPOSTINT('rank') : -1;

// PDF
$hidedetails = (GETPOSTINT('hidedetails') ? GETPOSTINT('hidedetails') : (getDolGlobalString('MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS') ? 1 : 0));
$hidedesc = (GETPOSTINT('hidedesc') ? GETPOSTINT('hidedesc') : (getDolGlobalString('MAIN_GENERATE_DOCUMENTS_HIDE_DESC') ? 1 : 0));
$hideref = (GETPOSTINT('hideref') ? GETPOSTINT('hideref') : (getDolGlobalString('MAIN_GENERATE_DOCUMENTS_HIDE_REF') ? 1 : 0));

$datelivraison = dol_mktime(GETPOSTINT('liv_hour'), GETPOSTINT('liv_min'), GETPOSTINT('liv_sec'), GETPOSTINT('liv_month'), GETPOSTINT('liv_day'), GETPOSTINT('liv_year'));


// Security check
if (!empty($user->socid)) {
	$socid = $user->socid;
}

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('ordersuppliercard', 'globalcard'));

$object = new CommandeFournisseur($db);
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

if ($user->socid) {
	$socid = $user->socid;
}

// Load object
if ($id > 0 || !empty($ref)) {
	$ret = $object->fetch($id, $ref);
	if ($ret < 0) {
		dol_print_error($db, $object->error);
	}
	$ret = $object->fetch_thirdparty();
	if ($ret < 0) {
		dol_print_error($db, $object->error);
	}
} elseif (!empty($socid) && $socid > 0) {
	$object->socid = $socid;
	$ret = $object->fetch_thirdparty();
	if ($ret < 0) {
		dol_print_error($db, $object->error);
	}
}

// Security check
$isdraft = (isset($object->statut) && ($object->statut == $object::STATUS_DRAFT) ? 1 : 0);
$result = restrictedArea($user, 'fournisseur', $object, 'commande_fournisseur', 'commande', 'fk_soc', 'rowid', $isdraft);

// Common permissions
$usercanread	= ($user->hasRight("fournisseur", "commande", "lire") || $user->hasRight("supplier_order", "lire"));
$usercancreate	= ($user->hasRight("fournisseur", "commande", "creer") || $user->hasRight("supplier_order", "creer"));
$usercandelete	= (($user->hasRight("fournisseur", "commande", "supprimer") || $user->hasRight("supplier_order", "supprimer")) || ($usercancreate && isset($object->statut) && $object->statut == $object::STATUS_DRAFT));

// Advanced permissions
$usercanvalidate = ((!getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && !empty($usercancreate)) || (getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && $user->hasRight("fournisseur", "supplier_order_advance", "validate")));

// Additional area permissions
$usercanapprove			= $user->hasRight("fournisseur", "commande", "approuver");
$usercanapprovesecond	= $user->hasRight("fournisseur", "commande", "approve2");
$usercanorder			= $user->hasRight("fournisseur", "commande", "commander");
if (!isModEnabled('reception')) {
	$usercanreceive = $user->hasRight("fournisseur", "commande", "receptionner");
} else {
	$usercanreceive = $user->hasRight("reception", "creer");
}

// Permissions for includes
$permissionnote		= $usercancreate; // Used by the include of actions_setnotes.inc.php
$permissiondellink	= $usercancreate; // Used by the include of actions_dellink.inc.php
$permissiontoedit	= $usercancreate; // Used by the include of actions_lineupdown.inc.php
$permissiontoadd	= $usercancreate; // Used by the include of actions_addupdatedelete.inc.php

// Project permission
$caneditproject = false;
if (isModEnabled('project')) {
	$caneditproject = !getDolGlobalString('SUPPLIER_ORDER_FORBID_EDIT_PROJECT') || ($object->statut == CommandeFournisseur::STATUS_DRAFT && preg_match('/^[\(]?PROV/i', $object->ref));
}

$error = 0;


/*
 * Actions
 */

$parameters = array('socid' => $socid);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	$backurlforlist = DOL_URL_ROOT.'/fourn/commande/list.php'.($socid > 0 ? '?socid='.((int) $socid) : '');

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) {
				$backtopage = $backurlforlist;
			} else {
				$backtopage = DOL_URL_ROOT.'/fourn/commande/card.php?id='.((!empty($id) && $id > 0) ? $id : '__ID__');
			}
		}
	}

	if ($cancel) {
		if (!empty($backtopageforcancel)) {
			header("Location: ".$backtopageforcancel);
			exit;
		} elseif (!empty($backtopage)) {
			header("Location: ".$backtopage);
			exit;
		}
		$action = '';
	}

	include DOL_DOCUMENT_ROOT.'/core/actions_setnotes.inc.php'; // Must be 'include', not 'include_once'

	include DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php'; // Must be 'include', not 'include_once'

	include DOL_DOCUMENT_ROOT.'/core/actions_lineupdown.inc.php'; // Must be 'include', not 'include_once'

	if ($action == 'setref_supplier' && $usercancreate) {
		$result = $object->setValueFrom('ref_supplier', GETPOST('ref_supplier', 'alpha'), '', null, 'text', '', $user, 'ORDER_SUPPLIER_MODIFY');
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	// Set incoterm
	if ($action == 'set_incoterms' && $usercancreate) {
		$result = $object->setIncoterms(GETPOSTINT('incoterm_id'), GETPOSTINT('location_incoterms'));
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	// payment conditions
	if ($action == 'setconditions' && $usercancreate) {
		$result = $object->setPaymentTerms(GETPOSTINT('cond_reglement_id'));
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	// payment mode
	if ($action == 'setmode' && $usercancreate) {
		$result = $object->setPaymentMethods(GETPOSTINT('mode_reglement_id'));
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	} elseif ($action == 'setmulticurrencycode' && $usercancreate) {
		// Multicurrency Code
		$result = $object->setMulticurrencyCode(GETPOST('multicurrency_code', 'alpha'));
	} elseif ($action == 'setmulticurrencyrate' && $usercancreate) {
		// Multicurrency rate
		$result = $object->setMulticurrencyRate(price2num(GETPOST('multicurrency_tx')), GETPOSTINT('calculation_mode'));
	}

	// bank account
	if ($action == 'setbankaccount' && $usercancreate) {
		$result = $object->setBankAccount(GETPOSTINT('fk_account'));
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	// date of delivery
	if ($action == 'setdate_livraison' && $usercancreate) {
		$result = $object->setDeliveryDate($user, $datelivraison);
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	// Set project
	if ($action == 'classin' && $usercancreate && $caneditproject) {
		$result = $object->setProject($projectid);
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	// Edit Thirdparty
	if (getDolGlobalString('MAIN_CAN_EDIT_SUPPLIER_ON_SUPPLIER_ORDER') && $action == 'set_thirdparty' && $usercancreate && $object->statut == CommandeFournisseur::STATUS_DRAFT) {
		$new_socid = GETPOSTINT('new_socid');
		if (!empty($new_socid) && $new_socid != $object->thirdparty->id) {
			$db->begin();

			// Update supplier
			$sql = 'UPDATE '.MAIN_DB_PREFIX.'commande_fournisseur';
			$sql .= ' SET fk_soc = '.((int) $new_socid);
			$sql .= ' WHERE fk_soc = '.((int) $object->thirdparty->id);
			$sql .= ' AND rowid = '.((int) $object->id);

			$res = $db->query($sql);

			if (!$res) {
				$db->rollback();
			} else {
				$db->commit();

				// Replace prices for each lines by new supplier prices
				foreach ($object->lines as $l) {
					$sql = 'SELECT price, unitprice, tva_tx, ref_fourn';
					$sql .= ' FROM '.MAIN_DB_PREFIX.'product_fournisseur_price';
					$sql .= ' WHERE fk_product = '.((int) $l->fk_product);
					$sql .= ' AND fk_soc = '.((int) $new_socid);
					$sql .= ' ORDER BY unitprice ASC';

					$resql = $db->query($sql);
					if ($resql) {
						$num_row = $db->num_rows($resql);
						if (empty($num_row)) {
							// No product price for this supplier !
							$l->subprice = 0;
							$l->total_ht = 0;
							$l->total_tva = 0;
							$l->total_ttc = 0;
							$l->ref_supplier = '';
							$l->update();
						} else {
							// No need for loop to keep best supplier price
							$obj = $db->fetch_object($resql);
							$l->subprice = $obj->unitprice;
							$l->total_ht = $obj->price;
							$l->tva_tx = $obj->tva_tx;
							$l->total_tva = $l->total_ht * ($obj->tva_tx / 100);
							$l->total_ttc = $l->total_ht + $l->total_tva;
							$l->ref_supplier = $obj->ref_fourn;
							$l->update();
						}
					} else {
						dol_print_error($db);
					}
					$db->free($resql);
				}
				$object->update_price();
			}
		}
		header('Location: '.$_SERVER['PHP_SELF'].'?id='.$object->id);
		exit;
	}

	if ($action == 'setremisepercent' && $usercancreate) {
		$result = $object->set_remise($user, price2num(GETPOST('remise_percent')));
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	if ($action == 'reopen' && $permissiontoadd) {	// no test on permission here, permission to use will depends on status
		if (in_array($object->statut, array(1, 2, 3, 4, 5, 6, 7, 9))) {
			if ($object->statut == 1) {
				$newstatus = 0; // Validated->Draft
			} elseif ($object->statut == 2) {
				$newstatus = 0; // Approved->Draft
			} elseif ($object->statut == 3) {
				$newstatus = 2; // Ordered->Approved
			} elseif ($object->statut == 4) {
				$newstatus = 3;
			} elseif ($object->statut == 5) {
				//$newstatus=2;    // Ordered
				// TODO Can we set it to submitted ?
				//$newstatus=3;  // Submitted
				// TODO If there is at least one reception, we can set to Received->Received partially
				$newstatus = 4; // Received partially
			} elseif ($object->statut == 6) {
				$newstatus = 2; // Canceled->Approved
			} elseif ($object->statut == 7) {
				$newstatus = 3; // Canceled->Process running
			} elseif ($object->statut == 9) {
				$newstatus = 1; // Refused->Validated
			} else {
				$newstatus = 2;
			}

			//print "old status = ".$object->statut.' new status = '.$newstatus;
			$db->begin();

			$result = $object->setStatus($user, $newstatus);
			if ($result > 0) {
				if ($newstatus == 0) {
					$sql = 'UPDATE '.MAIN_DB_PREFIX.'commande_fournisseur';
					$sql .= ' SET fk_user_approve = null, fk_user_approve2 = null, date_approve = null, date_approve2 = null';
					$sql .= ' WHERE rowid = '.((int) $object->id);

					$resql = $db->query($sql);
				}

				$db->commit();

				header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
				exit;
			} else {
				$db->rollback();

				setEventMessages($object->error, $object->errors, 'errors');
			}
		}
	}

	/*
	 * Classify supplier order as billed
	 */
	if ($action == 'classifybilled' && $usercancreate) {
		$ret = $object->classifyBilled($user);
		if ($ret < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	if ($action == 'classifyunbilled' && $usercancreate) {
		$ret = $object->classifyUnBilled($user);
		if ($ret < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	// Add a product line
	if ($action == 'addline' && GETPOST('submitforalllines', 'aZ09') && (GETPOST('alldate_start', 'alpha') || GETPOST('alldate_end', 'alpha')) && $usercancreate) {
		// Define date start and date end for all line
		$alldate_start = dol_mktime(GETPOST('alldate_starthour'), GETPOST('alldate_startmin'), 0, GETPOST('alldate_startmonth'), GETPOST('alldate_startday'), GETPOST('alldate_startyear'));
		$alldate_end = dol_mktime(GETPOST('alldate_endhour'), GETPOST('alldate_endmin'), 0, GETPOST('alldate_endmonth'), GETPOST('alldate_endday'), GETPOST('alldate_endyear'));
		foreach ($object->lines as $line) {
			if ($line->product_type == 1) { // only service line
				$result = $object->updateline($line->id, $line->desc, $line->subprice, $line->qty, $line->remise_percent, $line->tva_tx, $line->localtax1_tx, $line->localtax2_tx, 'HT', $line->info_bits, $line->product_type, 0, $alldate_start, $alldate_end, $line->array_options, $line->fk_unit, $line->multicurrency_subprice, $line->ref_supplier);
			}
		}
	} elseif ($action == 'addline' && GETPOST('submitforalllines', 'aZ09') && GETPOST('vatforalllines', 'alpha') !== '' && $usercancreate) {
		// Define new vat_rate for all lines
		$vat_rate = (GETPOST('vatforalllines') ? GETPOST('vatforalllines') : 0);
		$vat_rate = str_replace('*', '', $vat_rate);
		$localtax1_rate = get_localtax($vat_rate, 1, $object->thirdparty, $mysoc);
		$localtax2_rate = get_localtax($vat_rate, 2, $object->thirdparty, $mysoc);
		foreach ($object->lines as $line) {
			$result = $object->updateline($line->id, $line->desc, $line->subprice, $line->qty, $line->remise_percent, $vat_rate, $localtax1_rate, $localtax2_rate, 'HT', $line->info_bits, $line->product_type, 0, $line->date_start, $line->date_end, $line->array_options, $line->fk_unit, $line->multicurrency_subprice, $line->ref_supplier);
		}
	} elseif ($action == 'addline' && $usercancreate) {
		$db->begin();

		$langs->load('errors');
		$error = 0;

		// Set if we used free entry or predefined product
		$predef = '';
		$product_desc = (GETPOSTISSET('dp_desc') ? GETPOST('dp_desc', 'restricthtml') : '');
		$date_start = dol_mktime(GETPOST('date_start'.$predef.'hour'), GETPOST('date_start'.$predef.'min'), GETPOST('date_start'.$predef.'sec'), GETPOST('date_start'.$predef.'month'), GETPOST('date_start'.$predef.'day'), GETPOST('date_start'.$predef.'year'));
		$date_end = dol_mktime(GETPOST('date_end'.$predef.'hour'), GETPOST('date_end'.$predef.'min'), GETPOST('date_end'.$predef.'sec'), GETPOST('date_end'.$predef.'month'), GETPOST('date_end'.$predef.'day'), GETPOST('date_end'.$predef.'year'));

		$prod_entry_mode = GETPOST('prod_entry_mode');
		if ($prod_entry_mode == 'free') {
			$idprod = 0;
		} else {
			$idprod = GETPOSTINT('idprod');
		}

		$tva_tx = (GETPOST('tva_tx') ? GETPOST('tva_tx') : 0);		// Can be '1.2' or '1.2 (CODE)'

		$price_ht = price2num(GETPOST('price_ht'), 'MU', 2);
		$price_ht_devise = price2num(GETPOST('multicurrency_price_ht'), 'CU', 2);
		$price_ttc = price2num(GETPOST('price_ttc'), 'MU', 2);
		$price_ttc_devise = price2num(GETPOST('multicurrency_price_ttc'), 'CU', 2);
		$qty = price2num(GETPOST('qty'.$predef, 'alpha'), 'MS');

		$remise_percent = (GETPOSTISSET('remise_percent'.$predef) ? price2num(GETPOST('remise_percent'.$predef, 'alpha'), '', 2) : 0);
		if (empty($remise_percent)) {
			$remise_percent = 0;
		}

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

		if ($prod_entry_mode == 'free' && GETPOST('price_ht') < 0 && $qty < 0) {
			setEventMessages($langs->trans('ErrorBothFieldCantBeNegative', $langs->transnoentitiesnoconv('UnitPrice'), $langs->transnoentitiesnoconv('Qty')), null, 'errors');
			$error++;
		}
		if ($prod_entry_mode == 'free' && !GETPOST('idprodfournprice') && GETPOST('type') < 0) {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Type')), null, 'errors');
			$error++;
		}
		if ($prod_entry_mode == 'free' && GETPOST('price_ht') === '' && GETPOST('price_ttc') === '' && $price_ht_devise === '') { // Unit price can be 0 but not ''
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('UnitPrice')), null, 'errors');
			$error++;
		}
		if ($prod_entry_mode == 'free' && !GETPOST('dp_desc')) {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Description')), null, 'errors');
			$error++;
		}
		if (GETPOST('qty', 'alpha') == '') {	// 0 is allowed for order
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Qty')), null, 'errors');
			$error++;
		}

		if (!$error && isModEnabled('variants') && $prod_entry_mode != 'free') {
			if ($combinations = GETPOST('combinations', 'array')) {
				//Check if there is a product with the given combination
				$prodcomb = new ProductCombination($db);

				if ($res = $prodcomb->fetchByProductCombination2ValuePairs($idprod, $combinations)) {
					$idprod = $res->fk_product_child;
				} else {
					setEventMessages($langs->trans('ErrorProductCombinationNotFound'), null, 'errors');
					$error++;
				}
			}
		}

		if ($prod_entry_mode != 'free' && empty($error)) {	// With combolist mode idprodfournprice is > 0 or -1. With autocomplete, idprodfournprice is > 0 or ''
			$productsupplier = new ProductFournisseur($db);

			$idprod = 0;
			if (GETPOST('idprodfournprice', 'alpha') == -1 || GETPOST('idprodfournprice', 'alpha') == '') {
				$idprod = -99; // Same behaviour than with combolist. When not select idprodfournprice is now -99 (to avoid conflict with next action that may return -1, -2, ...)
			}

			$reg = array();
			if (preg_match('/^idprod_([0-9]+)$/', GETPOST('idprodfournprice', 'alpha'), $reg)) {
				$idprod = $reg[1];
				$res = $productsupplier->fetch($idprod); // Load product from its id
				// Call to init some price properties of $productsupplier
				// So if a supplier price already exists for another thirdparty (first one found), we use it as reference price
				if (getDolGlobalString('SUPPLIER_TAKE_FIRST_PRICE_IF_NO_PRICE_FOR_CURRENT_SUPPLIER')) {
					$fksoctosearch = 0;
					$productsupplier->get_buyprice(0, -1, $idprod, 'none', $fksoctosearch); // We force qty to -1 to be sure to find if a supplier price exist
					if ($productsupplier->fourn_socid != $socid) {	// The price we found is for another supplier, so we clear supplier price
						$productsupplier->ref_supplier = '';
					}
				} else {
					$fksoctosearch = $object->thirdparty->id;
					$productsupplier->get_buyprice(0, -1, $idprod, 'none', $fksoctosearch); // We force qty to -1 to be sure to find if a supplier price exist
				}
			} elseif (GETPOST('idprodfournprice', 'alpha') > 0) {
				$qtytosearch = $qty; // Just to see if a price exists for the quantity. Not used to found vat.
				//$qtytosearch = -1;	 // We force qty to -1 to be sure to find if a supplier price exist
				$idprod = $productsupplier->get_buyprice(GETPOST('idprodfournprice', 'alpha'), $qtytosearch);
				$res = $productsupplier->fetch($idprod);
			}

			if ($idprod > 0) {
				$label = $productsupplier->label;

				// Define output language
				if (getDolGlobalInt('MAIN_MULTILANGS') && getDolGlobalString('PRODUIT_TEXTS_IN_THIRDPARTY_LANGUAGE')) {
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
					$desc = (!empty($productsupplier->multilangs[$outputlangs->defaultlang]["description"])) ? $productsupplier->multilangs[$outputlangs->defaultlang]["description"] : $productsupplier->description;
				} else {
					$desc = $productsupplier->description;
				}
				// if we use supplier description of the products
				if (!empty($productsupplier->desc_supplier) && getDolGlobalString('PRODUIT_FOURN_TEXTS')) {
					$desc = $productsupplier->desc_supplier;
				}

				//If text set in desc is the same as product descpription (as now it's preloaded) we add it only one time
				if (trim($product_desc) == trim($desc) && getDolGlobalString('PRODUIT_AUTOFILL_DESC')) {
					$product_desc = '';
				}

				if (!empty($product_desc) && getDolGlobalString('MAIN_NO_CONCAT_DESCRIPTION')) {
					$desc = $product_desc;
				}
				if (!empty($product_desc) && trim($product_desc) != trim($desc)) {
					$desc = dol_concatdesc($desc, $product_desc, false, getDolGlobalString('MAIN_CHANGE_ORDER_CONCAT_DESCRIPTION') ? true : false);
				}

				$ref_supplier = $productsupplier->ref_supplier;

				// Get vat rate
				$tva_npr = 0;
				if (!GETPOSTISSET('tva_tx')) {	// If vat rate not provided from the form (the form has the priority)
					$tva_tx = get_default_tva($object->thirdparty, $mysoc, $productsupplier->id, GETPOST('idprodfournprice', 'alpha'));
					$tva_npr = get_default_npr($object->thirdparty, $mysoc, $productsupplier->id, GETPOST('idprodfournprice', 'alpha'));
					if (empty($tva_tx)) {
						$tva_npr = 0;
					}
				}

				$localtax1_tx = get_localtax($tva_tx, 1, $mysoc, $object->thirdparty, $tva_npr);
				$localtax2_tx = get_localtax($tva_tx, 2, $mysoc, $object->thirdparty, $tva_npr);

				$type = $productsupplier->type;
				if (GETPOST('price_ht') != '' || GETPOST('multicurrency_price_ht') != '') {
					$price_base_type = 'HT';
					$pu = price2num($price_ht, 'MU');
					$pu_devise = price2num($price_ht_devise, 'CU');
				} elseif (GETPOST('price_ttc') != '' || GETPOST('multicurrency_price_ttc') != '') {
					$price_base_type = 'TTC';
					$pu = price2num($price_ttc, 'MU');
					$pu_devise = price2num($price_ttc_devise, 'CU');
				} else {
					$price_base_type = ($productsupplier->fourn_price_base_type ? $productsupplier->fourn_price_base_type : 'HT');
					if (empty($object->multicurrency_code) || ($productsupplier->fourn_multicurrency_code != $object->multicurrency_code)) {	// If object is in a different currency and price not in this currency
						$pu = $productsupplier->fourn_pu;
						$pu_devise = 0;
					} else {
						$pu = $productsupplier->fourn_pu;
						$pu_devise = $productsupplier->fourn_multicurrency_unitprice;
					}
				}

				if (empty($pu)) {
					$pu = 0; // If pu is '' or null, we force to have a numeric value
				}

				$result = $object->addline(
					$desc,
					($price_base_type == 'HT' ? $pu : 0),
					$qty,
					$tva_tx,
					$localtax1_tx,
					$localtax2_tx,
					$idprod,
					$productsupplier->product_fourn_price_id,
					$ref_supplier,
					$remise_percent,
					$price_base_type,
					($price_base_type == 'TTC' ? $pu : 0),
					$type,
					$tva_npr,
					0,
					$date_start,
					$date_end,
					$array_options,
					$productsupplier->fk_unit,
					$pu_devise,
					'',
					0,
					min($rank, count($object->lines) + 1)
				);
			}
			if ($idprod == -99 || $idprod == 0) {
				// Product not selected
				$error++;
				$langs->load("errors");
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("ProductOrService")), null, 'errors');
			}
			if ($idprod == -1) {
				// Quantity too low
				$error++;
				$langs->load("errors");
				setEventMessages($langs->trans("ErrorQtyTooLowForThisSupplier"), null, 'errors');
			}
		} elseif (empty($error)) { // $price_ht is already set
			$tva_npr = (preg_match('/\*/', $tva_tx) ? 1 : 0);
			$tva_tx = str_replace('*', '', $tva_tx);
			$label = (GETPOST('product_label') ? GETPOST('product_label') : '');
			$desc = $product_desc;
			$type = GETPOST('type');
			$ref_supplier = GETPOST('fourn_ref', 'alpha');

			$fk_unit = GETPOST('units', 'alpha');

			if (!preg_match('/\((.*)\)/', $tva_tx)) {
				$tva_tx = price2num($tva_tx); // $txtva can have format '5,1' or '5.1' or '5.1(XXX)', we must clean only if '5,1'
			}

			// Local Taxes
			$localtax1_tx = get_localtax($tva_tx, 1, $mysoc, $object->thirdparty);
			$localtax2_tx = get_localtax($tva_tx, 2, $mysoc, $object->thirdparty);

			if (GETPOST('price_ht') != '' || GETPOST('multicurrency_price_ht') != '') {
				$pu_ht = price2num($price_ht, 'MU'); // $pu_ht must be rounded according to settings
				$pu_ttc = '';
			} else {
				$pu_ttc = price2num(GETPOST('price_ttc'), 'MU');
				$pu_ht = price2num((float) $pu_ttc / (1 + ((float) $tva_tx / 100)), 'MU'); // $pu_ht must be rounded according to settings
			}
			$price_base_type = 'HT';
			$pu_ht_devise = price2num($price_ht_devise, 'CU');

			$result = $object->addline($desc, $pu_ht, $qty, $tva_tx, $localtax1_tx, $localtax2_tx, 0, 0, $ref_supplier, $remise_percent, $price_base_type, $pu_ttc, $type, 0, 0, $date_start, $date_end, $array_options, $fk_unit, $pu_ht_devise);
		}

		//print "xx".$tva_tx; exit;
		if (!$error && $result > 0) {
			$db->commit();

			$ret = $object->fetch($object->id); // Reload to get new records

			// Define output language
			if (!getDolGlobalString('MAIN_DISABLE_PDF_AUTOUPDATE')) {
				$outputlangs = $langs;
				$newlang = '';
				if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang)) {
					$newlang = $object->thirdparty->default_lang;
					if (GETPOST('lang_id', 'aZ09')) {
						$newlang = GETPOST('lang_id', 'aZ09');
					}
				}
				if (!empty($newlang)) {
					$outputlangs = new Translate("", $conf);
					$outputlangs->setDefaultLang($newlang);
				}
				$model = $object->model_pdf;
				$ret = $object->fetch($id); // Reload to get new records

				$result = $object->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
				if ($result < 0) {
					setEventMessages($object->error, $object->errors, 'errors');
				}
			}

			unset($_POST ['prod_entry_mode']);

			unset($_POST['qty']);
			unset($_POST['type']);
			unset($_POST['remise_percent']);
			unset($_POST['pu']);
			unset($_POST['price_ht']);
			unset($_POST['multicurrency_price_ht']);
			unset($_POST['price_ttc']);
			unset($_POST['fourn_ref']);
			unset($_POST['tva_tx']);
			unset($_POST['label']);
			unset($localtax1_tx);
			unset($localtax2_tx);
			unset($_POST['np_marginRate']);
			unset($_POST['np_markRate']);
			unset($_POST['dp_desc']);
			unset($_POST['idprodfournprice']);
			unset($_POST['units']);

			unset($_POST['date_starthour']);
			unset($_POST['date_startmin']);
			unset($_POST['date_startsec']);
			unset($_POST['date_startday']);
			unset($_POST['date_startmonth']);
			unset($_POST['date_startyear']);
			unset($_POST['date_endhour']);
			unset($_POST['date_endmin']);
			unset($_POST['date_endsec']);
			unset($_POST['date_endday']);
			unset($_POST['date_endmonth']);
			unset($_POST['date_endyear']);
		} else {
			$db->rollback();
			setEventMessages($object->error, $object->errors, 'errors');
		}

		$action = '';
	}

	/*
	 *	Updating a line in the order
	 */
	if ($action == 'updateline' && $usercancreate && !GETPOST('cancel', 'alpha')) {
		$db->begin();

		$vat_rate = (GETPOST('tva_tx') ? GETPOST('tva_tx') : 0);

		if ($lineid) {
			$line = new CommandeFournisseurLigne($db);
			$res = $line->fetch($lineid);
			if (!$res) {
				dol_print_error($db);
			}
		}

		$productsupplier = new ProductFournisseur($db);
		if (getDolGlobalString('SUPPLIER_ORDER_WITH_PREDEFINED_PRICES_ONLY')) {
			if ($line->fk_product > 0 && $productsupplier->get_buyprice(0, price2num(GETPOSTINT('qty')), $line->fk_product, 'none', GETPOSTINT('socid')) < 0) {
				setEventMessages($langs->trans("ErrorQtyTooLowForThisSupplier"), null, 'warnings');
			}
		}

		$date_start = dol_mktime(GETPOST('date_starthour'), GETPOST('date_startmin'), GETPOST('date_startsec'), GETPOST('date_startmonth'), GETPOST('date_startday'), GETPOST('date_startyear'));
		$date_end = dol_mktime(GETPOST('date_endhour'), GETPOST('date_endmin'), GETPOST('date_endsec'), GETPOST('date_endmonth'), GETPOST('date_endday'), GETPOST('date_endyear'));

		// Define info_bits
		$info_bits = 0;
		if (preg_match('/\*/', $vat_rate)) {
			$info_bits |= 0x01;
		}

		// Define vat_rate
		$vat_rate = str_replace('*', '', $vat_rate);
		$localtax1_rate = get_localtax($vat_rate, 1, $mysoc, $object->thirdparty);
		$localtax2_rate = get_localtax($vat_rate, 2, $mysoc, $object->thirdparty);

		if (GETPOST('price_ht') != '') {
			$price_base_type = 'HT';
			$ht = price2num(GETPOST('price_ht'), '', 2);
		} else {
			$reg = array();
			$vatratecleaned = $vat_rate;
			if (preg_match('/^(.*)\s*\((.*)\)$/', $vat_rate, $reg)) {      // If vat is "xx (yy)"
				$vatratecleaned = trim($reg[1]);
				$vatratecode = $reg[2];
			}

			$ttc = price2num(GETPOST('price_ttc'), '', 2);
			$ht = (float) $ttc / (1 + ((float) $vatratecleaned / 100));
			$price_base_type = 'HT';
		}

		$pu_ht_devise = price2num(GETPOST('multicurrency_subprice'), 'CU', 2);

		// Extrafields Lines
		$extralabelsline = $extrafields->fetch_name_optionals_label($object->table_element_line);
		$array_options = $extrafields->getOptionalsFromPost($object->table_element_line);
		// Unset extrafield POST Data
		if (is_array($extralabelsline)) {
			foreach ($extralabelsline as $key => $value) {
				unset($_POST["options_".$key]);
			}
		}

		$result = $object->updateline(
			$lineid,
			GETPOST('product_desc', 'restricthtml'),
			$ht,
			price2num(GETPOST('qty'), 'MS'),
			price2num(GETPOST('remise_percent'), '', 2),
			$vat_rate,
			$localtax1_rate,
			$localtax2_rate,
			$price_base_type,
			0,
			GETPOSTISSET("type") ? GETPOST("type") : $line->product_type,
			false,
			$date_start,
			$date_end,
			$array_options,
			GETPOST('units'),
			$pu_ht_devise,
			GETPOST('fourn_ref', 'alpha')
		);
		unset($_POST['qty']);
		unset($_POST['type']);
		unset($_POST['idprodfournprice']);
		unset($_POST['remmise_percent']);
		unset($_POST['dp_desc']);
		unset($_POST['np_desc']);
		unset($_POST['pu']);
		unset($_POST['fourn_ref']);
		unset($_POST['tva_tx']);
		unset($_POST['date_start']);
		unset($_POST['date_end']);
		unset($_POST['units']);
		unset($localtax1_tx);
		unset($localtax2_tx);

		unset($_POST['date_starthour']);
		unset($_POST['date_startmin']);
		unset($_POST['date_startsec']);
		unset($_POST['date_startday']);
		unset($_POST['date_startmonth']);
		unset($_POST['date_startyear']);
		unset($_POST['date_endhour']);
		unset($_POST['date_endmin']);
		unset($_POST['date_endsec']);
		unset($_POST['date_endday']);
		unset($_POST['date_endmonth']);
		unset($_POST['date_endyear']);

		if ($result >= 0) {
			// Define output language
			if (!getDolGlobalString('MAIN_DISABLE_PDF_AUTOUPDATE')) {
				$outputlangs = $langs;
				$newlang = '';
				if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
					$newlang = GETPOST('lang_id', 'aZ09');
				}
				if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang)) {
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
					dol_print_error($db, $object->error, $object->errors);
				}
			}

			$db->commit();
		} else {
			$db->rollback();

			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	// Remove a product line
	if ($action == 'confirm_deleteline' && $confirm == 'yes' && $usercancreate) {
		$db->begin();

		$result = $object->deleteLine($lineid);
		if ($result > 0) {
			// reorder lines
			$object->line_order(true);
			// Define output language
			$outputlangs = $langs;
			$newlang = '';
			if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
				$newlang = GETPOST('lang_id', 'aZ09');
			}
			if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang)) {
				$newlang = $object->thirdparty->default_lang;
			}
			if (!empty($newlang)) {
				$outputlangs = new Translate("", $conf);
				$outputlangs->setDefaultLang($newlang);
			}
			if (!getDolGlobalString('MAIN_DISABLE_PDF_AUTOUPDATE')) {
				$ret = $object->fetch($object->id); // Reload to get new records
				$object->generateDocument($object->model_pdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
			}
		} else {
			$error++;
			setEventMessages($object->error, $object->errors, 'errors');
			// Reset action to avoid asking again confirmation on failure
			$action = '';
		}

		if (!$error) {
			// reopen order if necessary
			if ($object->status == CommandeFournisseur::STATUS_RECEIVED_COMPLETELY) {
				if ($object->setStatus($user, CommandeFournisseur::STATUS_RECEIVED_PARTIALLY) < 0) {
					setEventMessages($object->error, $object->errors, 'errors');
					$error++;
					$action = '';
				}
			}
		}

		if (!$error) {
			$db->commit();
			header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
			exit;
		} else {
			$db->rollback();
		}
	}

	// Validate
	if ($action == 'confirm_valid' && $confirm == 'yes' && $usercanvalidate) {
		$db->begin();

		$object->date_commande = dol_now();
		$result = $object->valid($user);
		if ($result >= 0) {
			// Define output language
			if (!getDolGlobalString('MAIN_DISABLE_PDF_AUTOUPDATE')) {
				$outputlangs = $langs;
				$newlang = '';
				if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
					$newlang = GETPOST('lang_id', 'aZ09');
				}
				if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang)) {
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
					$error++;
					dol_print_error($db, $object->error, $object->errors);
				}
			}
		} else {
			$error++;
			setEventMessages($object->error, $object->errors, 'errors');
		}

		// If we have permission, and if we don't need to provide the idwarehouse, we go directly on approved step
		if (!$error && !getDolGlobalString('SUPPLIER_ORDER_NO_DIRECT_APPROVE') && $usercanapprove && !(getDolGlobalString('STOCK_CALCULATE_ON_SUPPLIER_VALIDATE_ORDER') && $object->hasProductsOrServices(1))) {
			$action = 'confirm_approve'; // can make standard or first level approval also if permission is set
		}

		if (!$error) {
			$db->commit();
		} else {
			$db->rollback();
		}
	}

	if (($action == 'confirm_approve' || $action == 'confirm_approve2') && $confirm == 'yes' && $usercanapprove) {
		$db->begin();

		$idwarehouse = GETPOSTINT('idwarehouse');

		$qualified_for_stock_change = 0;
		if (!getDolGlobalString('STOCK_SUPPORTS_SERVICES')) {
			$qualified_for_stock_change = $object->hasProductsOrServices(2);
		} else {
			$qualified_for_stock_change = $object->hasProductsOrServices(1);
		}

		// Check parameters
		if (isModEnabled('stock') && getDolGlobalString('STOCK_CALCULATE_ON_SUPPLIER_VALIDATE_ORDER') && $qualified_for_stock_change) {	// warning name of option should be STOCK_CALCULATE_ON_SUPPLIER_APPROVE_ORDER
			if (!$idwarehouse || $idwarehouse == -1) {
				$error++;
				setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("Warehouse")), null, 'errors');
				$action = '';
			}
		}

		if (!$error) {
			$result = $object->approve($user, $idwarehouse, ($action == 'confirm_approve2' ? 1 : 0));
			if ($result > 0) {
				if (!getDolGlobalString('MAIN_DISABLE_PDF_AUTOUPDATE')) {
					$outputlangs = $langs;
					$newlang = '';
					if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
						$newlang = GETPOST('lang_id', 'aZ09');
					}
					if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang)) {
						$newlang = $object->thirdparty->default_lang;
					}
					if (!empty($newlang)) {
						$outputlangs = new Translate("", $conf);
						$outputlangs->setDefaultLang($newlang);
					}
					$object->generateDocument($object->model_pdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
				}
			} else {
				$error++;
				setEventMessages($object->error, $object->errors, 'errors');
			}
		}

		if (!$error) {
			$db->commit();

			header("Location: ".$_SERVER["PHP_SELF"]."?id=".$object->id);
			exit;
		} else {
			$db->rollback();
		}
	}

	if ($action == 'confirm_refuse' && $confirm == 'yes' && $usercanapprove) {
		if (GETPOST('refuse_note')) {
			$object->refuse_note = GETPOST('refuse_note');
		}
		$result = $object->refuse($user);
		if ($result > 0) {
			header("Location: ".$_SERVER["PHP_SELF"]."?id=".$object->id);
			exit;
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	// Force mandatory order method
	if ($action == 'commande') {	// Test on permission not required here
		$methodecommande = GETPOSTINT('methodecommande');

		if ($cancel) {
			$action = '';
		} elseif ($methodecommande <= 0 && !getDolGlobalInt('SUPPLIER_ORDER_MODE_OPTIONAL')) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("OrderMode")), null, 'errors');
			$action = 'createorder';
		}
	}

	if ($action == 'confirm_commande' && $confirm == 'yes' && $usercanorder) {
		$db->begin();

		$result = $object->commande($user, GETPOST("datecommande"), GETPOSTINT("methode"), GETPOSTINT('comment'));
		if ($result > 0) {
			if (!getDolGlobalString('MAIN_DISABLE_PDF_AUTOUPDATE')) {
				$outputlangs = $langs;
				$newlang = '';
				if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
					$newlang = GETPOST('lang_id', 'aZ09');
				}
				if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang)) {
					$newlang = $object->thirdparty->default_lang;
				}
				if (!empty($newlang)) {
					$outputlangs = new Translate("", $conf);
					$outputlangs->setDefaultLang($newlang);
				}
				$object->generateDocument($object->model_pdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
			}
			$action = '';
		} else {
			$error++;
			setEventMessages($object->error, $object->errors, 'errors');
		}

		if (!$error) {
			$db->commit();

			header("Location: ".$_SERVER["PHP_SELF"]."?id=".$object->id);
			exit;
		} else {
			$db->rollback();
		}
	}


	if ($action == 'confirm_delete' && $confirm == 'yes' && $usercandelete) {
		$result = $object->delete($user);
		if ($result > 0) {
			header("Location: ".DOL_URL_ROOT.'/fourn/commande/list.php?restore_lastsearch_values=1');
			exit;
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	// Action clone object
	if ($action == 'confirm_clone' && $confirm == 'yes' && $usercancreate) {
		// @phan-suppress-next-line PhanPluginBothLiteralsBinaryOp
		if (1 == 0 && !GETPOST('clone_content') && !GETPOST('clone_receivers')) {
			setEventMessages($langs->trans("NoCloneOptionsSpecified"), null, 'errors');
		} else {
			if ($object->id > 0) {
				$orig = clone $object;

				$result = $object->createFromClone($user, $socid);
				if ($result > 0) {
					header("Location: ".$_SERVER['PHP_SELF'].'?id='.$result);
					exit;
				} else {
					setEventMessages($object->error, $object->errors, 'errors');
					$object = $orig;
					$action = '';
				}
			}
		}
	}

	// Set status of reception (complete, partial, ...)
	if ($action == 'livraison' && $usercanreceive) {
		if ($cancel) {
			$action = '';
		} else {
			$db->begin();

			if (GETPOST("type") != '') {
				$date_liv = dol_mktime(GETPOST('rehour'), GETPOST('remin'), GETPOST('resec'), GETPOST("remonth"), GETPOST("reday"), GETPOST("reyear"));

				$result = $object->Livraison($user, $date_liv, GETPOST("type"), GETPOST("comment")); // GETPOST("type") is 'tot', 'par', 'nev', 'can'
				if ($result > 0) {
					$langs->load("deliveries");
					setEventMessages($langs->trans("DeliveryStateSaved"), null);
					$action = '';
				} else {
					//if ($result == -3) {}
					$error++;
					setEventMessages($object->error, $object->errors, 'errors');
				}
			} else {
				$error++;
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Delivery")), null, 'errors');
			}

			if (!$error) {
				$db->commit();
			} else {
				$db->rollback();
			}
		}
	}

	if ($action == 'confirm_cancel' && $confirm == 'yes' && $usercanorder) {
		if (GETPOST('cancel_note')) {
			$object->cancel_note = GETPOST('cancel_note');
		}
		$result = $object->cancel($user);
		if ($result > 0) {
			header("Location: ".$_SERVER["PHP_SELF"]."?id=".$object->id);
			exit;
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	// Actions when printing a doc from card
	include DOL_DOCUMENT_ROOT.'/core/actions_printing.inc.php';

	// Actions to send emails
	$triggersendname = 'ORDER_SUPPLIER_SENTBYMAIL';
	$autocopy = 'MAIN_MAIL_AUTOCOPY_SUPPLIER_ORDER_TO';
	$trackid = 'sord'.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';

	// Actions to build doc
	$upload_dir = $conf->fournisseur->commande->dir_output;
	include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';


	if ($action == 'update_extras' && $permissiontoadd) {
		$object->oldcopy = dol_clone($object, 2);

		// Fill array 'array_options' with data from add form
		$ret = $extrafields->setOptionalsFromPost(null, $object, GETPOST('attribute', 'restricthtml'));
		if ($ret < 0) {
			$error++;
		}

		if (!$error) {
			// Actions on extra fields
			if (!$error) {
				$result = $object->insertExtraFields('ORDER_SUPPLIER_MODIFY');
				if ($result < 0) {
					$error++;
					setEventMessages($object->error, $object->errors, 'errors');
				}
			}
		}

		if ($error) {
			$action = 'edit_extras';
		}
	}

	/*
	 * Create an order
	 */
	if ($action == 'add' && $permissiontoadd) {
		$error = 0;
		$selectedLines = GETPOST('toselect', 'array');
		if ($socid < 1) {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentities('Supplier')), null, 'errors');
			$action = 'create';
			$error++;
		}

		if (!$error) {
			$db->begin();

			// Creation commande
			$object->ref_supplier  	= GETPOST('refsupplier');
			$object->socid         	= $socid;
			$object->cond_reglement_id = GETPOSTINT('cond_reglement_id');
			$object->mode_reglement_id = GETPOSTINT('mode_reglement_id');
			$object->fk_account        = GETPOSTINT('fk_account');
			$object->note_private = GETPOST('note_private', 'restricthtml');
			$object->note_public   	= GETPOST('note_public', 'restricthtml');
			$object->delivery_date = $datelivraison;
			$object->fk_incoterms = GETPOSTINT('incoterm_id');
			$object->location_incoterms = GETPOST('location_incoterms', 'alpha');
			$object->multicurrency_code = GETPOST('multicurrency_code', 'alpha');
			$object->multicurrency_tx = price2num(GETPOST('originmulticurrency_tx', 'alpha'));
			$object->fk_project       = GETPOSTINT('projectid');

			// Fill array 'array_options' with data from add form
			if (!$error) {
				$ret = $extrafields->setOptionalsFromPost(null, $object);
				if ($ret < 0) {
					$error++;
				}
			}

			if (!$error) {
				// If creation from another object of another module (Example: origin=propal, originid=1)
				if (!empty($origin) && !empty($originid)) {
					$element = $subelement = $origin;
					$classname = ucfirst($subelement);
					if ($origin == 'propal' || $origin == 'proposal') {
						$element = 'comm/propal';
						$subelement = 'propal';
						$classname = 'Propal';
					}
					if ($origin == 'order' || $origin == 'commande') {
						$element = $subelement = 'commande';
						$classname = 'Commande';
					}
					if ($origin == 'supplier_proposal') {
						$classname = 'SupplierProposal';
						$element = 'supplier_proposal';
						$subelement = 'supplier_proposal';
					}

					$object->origin = $origin;
					$object->origin_id = $originid;

					// Possibility to add external linked objects with hooks
					$object->linked_objects [$object->origin] = $object->origin_id;
					$other_linked_objects = GETPOST('other_linked_objects', 'array');
					if (!empty($other_linked_objects)) {
						$object->linked_objects = array_merge($object->linked_objects, $other_linked_objects);
					}

					$id = $object->create($user);
					if ($id > 0) {
						dol_include_once('/'.$element.'/class/'.$subelement.'.class.php');

						$srcobject = new $classname($db);

						dol_syslog("Try to find source object origin=".$object->origin." originid=".$object->origin_id." to add lines");
						$result = $srcobject->fetch($object->origin_id);
						if ($result > 0) {
							$tmpdate = $srcobject->delivery_date;
							$object->setDeliveryDate($user, $tmpdate);
							$object->set_id_projet($user, $srcobject->fk_project);

							$lines = $srcobject->lines;
							if (empty($lines) && method_exists($srcobject, 'fetch_lines')) {
								$srcobject->fetch_lines();
								$lines = $srcobject->lines;
							}

							$fk_parent_line = 0;
							$num = count($lines);

							for ($i = 0; $i < $num; $i++) {
								if (empty($lines[$i]->subprice) || $lines[$i]->qty <= 0 || !in_array($lines[$i]->id, $selectedLines)) {
									continue;
								}

								$label = (!empty($lines[$i]->label) ? $lines[$i]->label : '');
								$desc = (!empty($lines[$i]->desc) ? $lines[$i]->desc : '');
								$product_type = (!empty($lines[$i]->product_type) ? $lines[$i]->product_type : 0);

								// Reset fk_parent_line for no child products and special product
								if (($lines[$i]->product_type != 9 && empty($lines[$i]->fk_parent_line)) || $lines[$i]->product_type == 9) {
									$fk_parent_line = 0;
								}

								// Extrafields
								if (method_exists($lines[$i], 'fetch_optionals')) { 							// For avoid conflicts if
									$lines[$i]->fetch_optionals();
									$array_option = $lines[$i]->array_options;
								}

								$ref_supplier = '';
								$product_fourn_price_id = 0;
								if ($origin == "commande") {
									$productsupplier = new ProductFournisseur($db);
									$result = $productsupplier->find_min_price_product_fournisseur($lines[$i]->fk_product, $lines[$i]->qty, $object->socid);
									$lines[$i]->subprice = 0;
									if ($result > 0) {
										$ref_supplier = $productsupplier->ref_supplier;
										$product_fourn_price_id = $productsupplier->product_fourn_price_id;
										// we need supplier subprice
										foreach ($srcobject->lines as $li) {
											$sql = 'SELECT price, unitprice, tva_tx, remise_percent, entity, ref_fourn';
											$sql .= ' FROM '.MAIN_DB_PREFIX.'product_fournisseur_price';
											$sql .= ' WHERE fk_product = '.((int) $li->fk_product);
											$sql .= ' AND entity IN ('.getEntity('product_fournisseur_price').')';
											$sql .= ' AND fk_soc = '.((int) $object->socid);
											$sql .= ' ORDER BY unitprice ASC';

											$resql = $db->query($sql);
											if ($resql) {
												$num_row = $db->num_rows($resql);
												if (empty($num_row)) {
													$li->remise_percent = 0;
												} else {
													$obj = $db->fetch_object($resql);
													$li->subprice = $obj->unitprice;
													$li->remise_percent = $obj->remise_percent;
												}
											} else {
												dol_print_error($db);
											}
											$db->free($resql);
										}
									}
								} else {
									$ref_supplier = $lines[$i]->ref_fourn;
									$product_fourn_price_id = 0;
								}

								$tva_tx = $lines[$i]->tva_tx;

								if ($origin == "commande") {
									$soc = new Societe($db);
									$soc->fetch($socid);
									$tva_tx = get_default_tva($soc, $mysoc, $lines[$i]->fk_product, $product_fourn_price_id);
								}

								$result = $object->addline(
									$desc,
									$lines[$i]->subprice,
									$lines[$i]->qty,
									$tva_tx,
									$lines[$i]->localtax1_tx,
									$lines[$i]->localtax2_tx,
									$lines[$i]->fk_product > 0 ? $lines[$i]->fk_product : 0,
									$product_fourn_price_id,
									$ref_supplier,
									$lines[$i]->remise_percent,
									'HT',
									0,
									$lines[$i]->product_type,
									0,
									0,
									null,
									null,
									$array_option,
									$lines[$i]->fk_unit,
									0,
									$element,
									!empty($lines[$i]->id) ? $lines[$i]->id : $lines[$i]->rowid,
									-1,
									$lines[$i]->special_code
								);

								if ($result < 0) {
									setEventMessages($object->error, $object->errors, 'errors');
									$error++;
									break;
								}

								// Defined the new fk_parent_line
								if ($result > 0 && $lines[$i]->product_type == 9) {
									$fk_parent_line = $result;
								}
							}

							// Add link between elements


							// Hooks
							$parameters = array('objFrom' => $srcobject);
							$reshook = $hookmanager->executeHooks('createFrom', $parameters, $object, $action); // Note that $action and $object may have been

							if ($reshook < 0) {
								setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
								$error++;
							}
						} else {
							setEventMessages($srcobject->error, $srcobject->errors, 'errors');
							$error++;
						}
					} else {
						setEventMessages($object->error, $object->errors, 'errors');
						$error++;
					}
				} else {
					$id = $object->create($user);
					if ($id < 0) {
						$error++;
						setEventMessages($object->error, $object->errors, 'errors');
					}
				}
			}

			if ($error) {
				$langs->load("errors");
				$db->rollback();
				$action = 'create';
			} else {
				$db->commit();
				header("Location: ".$_SERVER['PHP_SELF']."?id=".urlencode((string) ($id)));
				exit;
			}
		}
	}

	if (getDolGlobalString('MAIN_DISABLE_CONTACTS_TAB')) {
		if ($action == 'addcontact' && $permissiontoadd) {
			if ($object->id > 0) {
				$contactid = (GETPOST('userid') ? GETPOST('userid') : GETPOST('contactid'));
				$typeid = (GETPOST('typecontact') ? GETPOST('typecontact') : GETPOST('type'));
				$result = $object->add_contact($contactid, $typeid, GETPOST("source", 'aZ09'));
			}

			if ($result >= 0) {
				header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
				exit;
			} else {
				if ($object->error == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
					$langs->load("errors");
					setEventMessages($langs->trans("ErrorThisContactIsAlreadyDefinedAsThisType"), null, 'errors');
				} else {
					setEventMessages($object->error, $object->errors, 'errors');
				}
			}
		} elseif ($action == 'swapstatut' && $object->id > 0 && $permissiontoadd) {
			// bascule du statut d'un contact
			$result = $object->swapContactStatus(GETPOSTINT('ligne'));
		} elseif ($action == 'deletecontact' && $object->id > 0 && $permissiontoadd) {
			// Efface un contact
			$result = $object->delete_contact(GETPOSTINT("lineid"));

			if ($result >= 0) {
				header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
				exit;
			} else {
				dol_print_error($db);
			}
		}
	}
}


/*
 * View
 */

$form = new	Form($db);
$formfile = new FormFile($db);
$formorder = new FormOrder($db);
$productstatic = new Product($db);
if (isModEnabled('project')) {
	$formproject = new FormProjets($db);
}

$title = $object->ref." - ".$langs->trans('Card');
if ($action == 'create') {
	$title = $langs->trans("NewOrderSupplier");
}
$help_url = 'EN:Module_Suppliers_Orders|FR:CommandeFournisseur|ES:Módulo_Pedidos_a_proveedores';
llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-supplier-order page-card');

$now = dol_now();

if ($action == 'create') {
	print load_fiche_titre($langs->trans('NewOrderSupplier'), '', 'supplier_order');

	dol_htmloutput_events();

	$currency_code = $conf->currency;

	$societe = '';
	$objectsrc = null;

	if ($socid > 0) {
		$societe = new Societe($db);
		$societe->fetch($socid);
	}

	if (!empty($origin) && !empty($originid)) {
		// Parse element/subelement (ex: project_task)
		$element = $subelement = $origin;
		$classname = ucfirst($subelement);
		$regs = array();
		if (preg_match('/^([^_]+)_([^_]+)/i', $origin, $regs)) {
			$element = $regs[1];
			$subelement = $regs[2];
		}

		if ($origin == 'propal' || $origin == 'proposal') {
			$classname = 'Propal';
			$element = 'comm/propal';
			$subelement = 'propal';
		}
		if ($origin == 'order' || $origin == 'commande') {
			$classname = 'Commande';
			$element = $subelement = 'commande';
		}
		if ($origin == 'supplier_proposal') {
			$classname = 'SupplierProposal';
			$element = 'supplier_proposal';
			$subelement = 'supplier_proposal';
		}


		dol_include_once('/'.$element.'/class/'.$subelement.'.class.php');

		$objectsrc = new $classname($db);
		$objectsrc->fetch($originid);
		if (empty($objectsrc->lines) && method_exists($objectsrc, 'fetch_lines')) {
			$objectsrc->fetch_lines();
		}
		$objectsrc->fetch_thirdparty();

		// Replicate extrafields
		$objectsrc->fetch_optionals();
		$object->array_options = $objectsrc->array_options;

		$projectid = (!empty($objectsrc->fk_project) ? $objectsrc->fk_project : '');
		$ref_client = (!empty($objectsrc->ref_client) ? $objectsrc->ref_client : '');
		$fk_account = 0;
		if ($origin == "commande") {
			$cond_reglement_id = 0;
			$mode_reglement_id = 0;
			$delivery_date = '';
			$objectsrc->note_private = '';
			$objectsrc->note_public = '';
			if ($societe = $object->thirdparty) {
				$cond_reglement_id = $societe->cond_reglement_supplier_id;
				$mode_reglement_id = $societe->mode_reglement_supplier_id;
				if (isModEnabled("multicurrency")) {
					$currency_code = $societe->multicurrency_code;
					if (getDolGlobalString('MULTICURRENCY_USE_ORIGIN_TX')) {
						$currency_tx = $societe->multicurrency_tx;
					}
				}
			}
		} else {
			$soc = $objectsrc->thirdparty;

			$cond_reglement_id	= (!empty($objectsrc->cond_reglement_id) ? $objectsrc->cond_reglement_id : (!empty($soc->cond_reglement_id) ? $soc->cond_reglement_id : 0));
			$mode_reglement_id	= (!empty($objectsrc->mode_reglement_id) ? $objectsrc->mode_reglement_id : (!empty($soc->mode_reglement_id) ? $soc->mode_reglement_id : 0));
			$fk_account         = (!empty($objectsrc->fk_account) ? $objectsrc->fk_account : (!empty($soc->fk_account) ? $soc->fk_account : 0));
			$availability_id	= (!empty($objectsrc->availability_id) ? $objectsrc->availability_id : (!empty($soc->availability_id) ? $soc->availability_id : 0));
			$shipping_method_id = (!empty($objectsrc->shipping_method_id) ? $objectsrc->shipping_method_id : (!empty($soc->shipping_method_id) ? $soc->shipping_method_id : 0));
			$demand_reason_id = (!empty($objectsrc->demand_reason_id) ? $objectsrc->demand_reason_id : (!empty($soc->demand_reason_id) ? $soc->demand_reason_id : 0));
			//$remise_percent		= (!empty($objectsrc->remise_percent) ? $objectsrc->remise_percent : (!empty($soc->remise_supplier_percent) ? $soc->remise_supplier_percent : 0));
			//$remise_absolue		= (!empty($objectsrc->remise_absolue) ? $objectsrc->remise_absolue : (!empty($soc->remise_absolue) ? $soc->remise_absolue : 0));
			$dateinvoice		= !getDolGlobalString('MAIN_AUTOFILL_DATE') ? -1 : '';

			$datedelivery = (!empty($objectsrc->delivery_date) ? $objectsrc->delivery_date : '');

			if (isModEnabled("multicurrency")) {
				if (!empty($objectsrc->multicurrency_code)) {
					$currency_code = $objectsrc->multicurrency_code;
				}
				if (getDolGlobalString('MULTICURRENCY_USE_ORIGIN_TX') && !empty($objectsrc->multicurrency_tx)) {
					$currency_tx = $objectsrc->multicurrency_tx;
				}
			}

			$note_private = $object->getDefaultCreateValueFor('note_private', (!empty($objectsrc->note_private) ? $objectsrc->note_private : null));
			$note_public = $object->getDefaultCreateValueFor('note_public', (!empty($objectsrc->note_public) ? $objectsrc->note_public : null));

			// Object source contacts list
			$srccontactslist = $objectsrc->liste_contact(-1, 'external', 1);
		}
	} else {
		$cond_reglement_id 	= !empty($societe->cond_reglement_supplier_id) ? $societe->cond_reglement_supplier_id : 0;
		$mode_reglement_id 	= !empty($societe->mode_reglement_supplier_id) ? $societe->mode_reglement_supplier_id : 0;

		if (isModEnabled("multicurrency") && !empty($societe->multicurrency_code)) {
			$currency_code = $societe->multicurrency_code;
		}

		$note_private = $object->getDefaultCreateValueFor('note_private');
		$note_public = $object->getDefaultCreateValueFor('note_public');
	}

	// If not defined, set default value from constant
	if (empty($cond_reglement_id) && getDolGlobalString('SUPPLIER_ORDER_DEFAULT_PAYMENT_TERM_ID')) {
		$cond_reglement_id = getDolGlobalString('SUPPLIER_ORDER_DEFAULT_PAYMENT_TERM_ID');
	}
	if (empty($mode_reglement_id) && getDolGlobalString('SUPPLIER_ORDER_DEFAULT_PAYMENT_MODE_ID')) {
		$mode_reglement_id = getDolGlobalString('SUPPLIER_ORDER_DEFAULT_PAYMENT_MODE_ID');
	}

	print '<form name="add" action="'.$_SERVER["PHP_SELF"].'" method="post">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="add">';
	print '<input type="hidden" name="remise_percent" value="'.(empty($soc->remise_supplier_percent) ? '' : $soc->remise_supplier_percent).'">';
	print '<input type="hidden" name="origin" value="'.$origin.'">';
	print '<input type="hidden" name="originid" value="'.$originid.'">';
	if ($backtopage) {
		print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	}
	if ($backtopageforcancel) {
		print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';
	}

	if (!empty($currency_tx)) {
		print '<input type="hidden" name="originmulticurrency_tx" value="'.$currency_tx.'">';
	}

	print dol_get_fiche_head(array());

	// Call Hook tabContentCreateSupplierOrder
	$parameters = array();
	// Note that $action and $object may be modified by hook
	$reshook = $hookmanager->executeHooks('tabContentCreateSupplierOrder', $parameters, $object, $action);
	if (empty($reshook)) {
		print '<table class="border centpercent">';

		// Ref
		print '<tr><td class="titlefieldcreate">'.$langs->trans('Ref').'</td><td>'.$langs->trans('Draft').'</td></tr>';

		// Third party
		print '<tr><td class="fieldrequired">'.$langs->trans('Supplier').'</td>';
		print '<td>';

		if (!empty($societe->id) && $societe->id > 0) {
			print $societe->getNomUrl(1, 'supplier');
			print '<input type="hidden" name="socid" value="'.$societe->id.'">';
		} else {
			$filter = '((s.fournisseur:=:1) AND (s.status:=:1))';
			print img_picto('', 'company', 'class="pictofixedwidth"').$form->select_company((empty($socid) ? '' : $socid), 'socid', $filter, 'SelectThirdParty', 1, 0, array(), 0, 'minwidth175 maxwidth500 widthcentpercentminusxx');
			// reload page to retrieve customer information
			if (!getDolGlobalString('RELOAD_PAGE_ON_SUPPLIER_CHANGE_DISABLED')) {
				print '<script>
				$(document).ready(function() {
					$("#socid").change(function() {
						console.log("We have changed the company - Reload page");
						// reload page
						$("input[name=action]").val("create");
						$("form[name=add]").submit();
					});
				});
				</script>';
			}
			print ' <a href="'.DOL_URL_ROOT.'/societe/card.php?action=create&client=0&fournisseur=1&backtopage='.urlencode($_SERVER["PHP_SELF"].'?action=create').'"><span class="fa fa-plus-circle valignmiddle paddingleft" title="'.$langs->trans("AddThirdParty").'"></span></a>';
		}
		print '</td>';

		if (!empty($societe->id) && $societe->id > 0) {
			// Discounts for third party
			print '<tr><td>'.$langs->trans('Discounts').'</td><td>';

			$absolute_discount = $societe->getAvailableDiscounts(null, '', 0, 1);

			$thirdparty = $societe;
			$discount_type = 1;
			$backtopage = urlencode($_SERVER["PHP_SELF"].'?socid='.$thirdparty->id.'&action='.$action.'&origin='.GETPOST('origin').'&originid='.GETPOST('originid'));
			include DOL_DOCUMENT_ROOT.'/core/tpl/object_discounts.tpl.php';

			print '</td></tr>';
		}

		// Ref supplier
		print '<tr><td>'.$langs->trans('RefSupplier').'</td><td><input name="refsupplier" type="text"></td>';
		print '</tr>';

		// Payment term
		print '<tr><td class="nowrap">'.$langs->trans('PaymentConditionsShort').'</td><td>';
		print img_picto('', 'payment', 'class="pictofixedwidth"');
		print $form->getSelectConditionsPaiements((GETPOSTISSET('cond_reglement_id') &&  GETPOST('cond_reglement_id') != 0) ? GETPOST('cond_reglement_id') : $cond_reglement_id, 'cond_reglement_id', -1, 1);
		print '</td></tr>';

		// Payment mode
		print '<tr><td>'.$langs->trans('PaymentMode').'</td><td>';
		print img_picto('', 'bank', 'class="pictofixedwidth"');
		$form->select_types_paiements((GETPOSTISSET('mode_reglement_id') && GETPOSTINT('mode_reglement_id') != 0) ? GETPOST('mode_reglement_id') : $mode_reglement_id, 'mode_reglement_id');
		print '</td></tr>';

		// Planned delivery date
		print '<tr><td>';
		print $langs->trans('DateDeliveryPlanned');
		print '</td>';
		print '<td>';
		$usehourmin = 0;
		if (getDolGlobalString('SUPPLIER_ORDER_USE_HOUR_FOR_DELIVERY_DATE')) {
			$usehourmin = 1;
		}
		print img_picto('', 'action', 'class="pictofixedwidth"');
		print $form->selectDate($datelivraison ? $datelivraison : -1, 'liv_', $usehourmin, $usehourmin, 0, "set");
		print '</td></tr>';

		// Bank Account
		if (getDolGlobalString('BANK_ASK_PAYMENT_BANK_DURING_SUPPLIER_ORDER') && isModEnabled("bank")) {
			$langs->load("bank");
			print '<tr><td>'.$langs->trans('BankAccount').'</td><td>';
			print img_picto('', 'bank_account', 'class="pictofixedwidth"');
			$form->select_comptes($fk_account, 'fk_account', 0, '', 1);
			print '</td></tr>';
		}

		// Project
		if (isModEnabled('project')) {
			$formproject = new FormProjets($db);

			$langs->load('projects');
			print '<tr><td>'.$langs->trans('Project').'</td><td>';
			print img_picto('', 'project', 'class="pictofixedwidth"').$formproject->select_projects((!getDolGlobalString('PROJECT_CAN_ALWAYS_LINK_TO_ALL_SUPPLIERS') ? $societe->id : -1), $projectid, 'projectid', 0, 0, 1, 1, 0, 0, 0, '', 1, 0, 'maxwidth500');
			print ' &nbsp; <a href="'.DOL_URL_ROOT.'/projet/card.php?action=create&status=1'.(!empty($societe->id) ? '&socid='.$societe->id : "").'&backtopage='.urlencode($_SERVER["PHP_SELF"].'?action=create'.(!empty($societe->id) ? '&socid='.$societe->id : "")).'"><span class="fa fa-plus-circle valignmiddle" title="'.$langs->trans("AddProject").'"></span></a>';
			print '</td></tr>';
		}

		// Incoterms
		if (isModEnabled('incoterm')) {
			$fkincoterms = (!empty($object->fk_incoterms) ? $object->fk_incoterms : ($socid > 0 ? $societe->fk_incoterms : ''));
			$locincoterms = (!empty($object->location_incoterms) ? $object->location_incoterms : ($socid > 0 ? $societe->location_incoterms : ''));
			print '<tr>';
			print '<td><label for="incoterm_id">'.$form->textwithpicto($langs->trans("IncotermLabel"), $object->label_incoterms, 1).'</label></td>';
			print '<td class="maxwidthonsmartphone">';
			print img_picto('', 'incoterm', 'class="pictofixedwidth"');
			print $form->select_incoterms($fkincoterms, $locincoterms);
			print '</td></tr>';
		}

		// Multicurrency
		if (isModEnabled("multicurrency")) {
			print '<tr>';
			print '<td>'.$form->editfieldkey('Currency', 'multicurrency_code', '', $object, 0).'</td>';
			print '<td class="maxwidthonsmartphone">';
			print img_picto('', 'currency', 'class="pictofixedwidth"');
			print $form->selectMultiCurrency($currency_code, 'multicurrency_code');
			print '</td></tr>';
		}

		print '<tr><td>'.$langs->trans('NotePublic').'</td>';
		print '<td>';
		$doleditor = new DolEditor('note_public', isset($note_public) ? $note_public : GETPOST('note_public', 'restricthtml'), '', 80, 'dolibarr_notes', 'In', 0, false, !getDolGlobalString('FCKEDITOR_ENABLE_NOTE_PUBLIC') ? 0 : 1, ROWS_3, '90%');
		print $doleditor->Create(1);
		print '</td>';
		//print '<textarea name="note_public" wrap="soft" cols="60" rows="'.ROWS_5.'"></textarea>';
		print '</tr>';

		print '<tr><td>'.$langs->trans('NotePrivate').'</td>';
		print '<td>';
		$doleditor = new DolEditor('note_private', isset($note_private) ? $note_private : GETPOST('note_private', 'restricthtml'), '', 80, 'dolibarr_notes', 'In', 0, false, !getDolGlobalString('FCKEDITOR_ENABLE_NOTE_PRIVATE') ? 0 : 1, ROWS_3, '90%');
		print $doleditor->Create(1);
		print '</td>';
		//print '<td><textarea name="note_private" wrap="soft" cols="60" rows="'.ROWS_5.'"></textarea></td>';
		print '</tr>';

		if (!empty($origin) && !empty($originid) && is_object($objectsrc)) {
			print "\n<!-- ".$classname." info -->";
			print "\n";
			print '<input type="hidden" name="amount"         value="'.$objectsrc->total_ht.'">'."\n";
			print '<input type="hidden" name="total"          value="'.$objectsrc->total_ttc.'">'."\n";
			print '<input type="hidden" name="tva"            value="'.$objectsrc->total_tva.'">'."\n";
			print '<input type="hidden" name="origin"         value="'.$objectsrc->element.'">';
			print '<input type="hidden" name="originid"       value="'.$objectsrc->id.'">';

			$newclassname = $classname;
			print '<tr><td>'.$langs->trans($newclassname).'</td><td>'.$objectsrc->getNomUrl(1, 'supplier').'</td></tr>';
			print '<tr><td>'.$langs->trans('AmountHT').'</td><td>'.price($objectsrc->total_ht).'</td></tr>';
			print '<tr><td>'.$langs->trans('AmountVAT').'</td><td>'.price($objectsrc->total_tva)."</td></tr>";
			if ($mysoc->localtax1_assuj == "1" || $objectsrc->total_localtax1 != 0) { 		// Localtax1 RE
				print '<tr><td>'.$langs->transcountry("AmountLT1", $mysoc->country_code).'</td><td>'.price($objectsrc->total_localtax1)."</td></tr>";
			}

			if ($mysoc->localtax2_assuj == "1" || $objectsrc->total_localtax2 != 0) { 		// Localtax2 IRPF
				print '<tr><td>'.$langs->transcountry("AmountLT2", $mysoc->country_code).'</td><td>'.price($objectsrc->total_localtax2)."</td></tr>";
			}

			print '<tr><td>'.$langs->trans('AmountTTC').'</td><td>'.price($objectsrc->total_ttc)."</td></tr>";

			if (isModEnabled("multicurrency")) {
				print '<tr><td>'.$langs->trans('MulticurrencyAmountHT').'</td><td>'.price($objectsrc->multicurrency_total_ht).'</td></tr>';
				print '<tr><td>'.$langs->trans('MulticurrencyAmountVAT').'</td><td>'.price($objectsrc->multicurrency_total_tva).'</td></tr>';
				print '<tr><td>'.$langs->trans('MulticurrencyAmountTTC').'</td><td>'.price($objectsrc->multicurrency_total_ttc).'</td></tr>';
			}
		}

		// Other options
		$parameters = array();
		$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		print $hookmanager->resPrint;

		if (empty($reshook)) {
			print $object->showOptionals($extrafields, 'create');
		}

		// Bouton "Create Draft"
		print "</table>\n";
	}
	print dol_get_fiche_end();

	print $form->buttonsSaveCancel("CreateDraft");

	// Show origin lines
	if (!empty($origin) && !empty($originid) && is_object($objectsrc)) {
		$title = $langs->trans('ProductsAndServices');
		print load_fiche_titre($title);

		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder centpercent">';

		$selectedLines = array();

		$objectsrc->printOriginLinesList('', $selectedLines);

		print '</table>';
		print '</div>';
	}
	print "</form>\n";
} elseif (!empty($object->id)) {
	// view
	$result = $object->fetch($id, $ref);
	$object->fetch_thirdparty();

	$societe = $object->thirdparty;

	$author = new User($db);
	$author->fetch($object->user_author_id);

	$head = ordersupplier_prepare_head($object);

	$title = $langs->trans("SupplierOrder");
	print dol_get_fiche_head($head, 'card', $title, -1, 'order');


	$formconfirm = '';

	// Confirmation de la suppression de la commande
	if ($action == 'delete') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('DeleteOrder'), $langs->trans('ConfirmDeleteOrder'), 'confirm_delete', '', 0, 2);
	}

	// Clone confirmation
	if ($action == 'clone') {
		$filter = '(s.fournisseur:=:1)';
		// Create an array for form
		$formquestion = array(
			array('type' => 'other', 'name' => 'socid', 'label' => $langs->trans("SelectThirdParty"), 'value' => $form->select_company(GETPOSTINT('socid'), 'socid', $filter))
		);
		// Paiement incomplet. On demande si motif = escompte ou autre
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ToClone'), $langs->trans('ConfirmCloneOrder', $object->ref), 'confirm_clone', $formquestion, 'yes', 1);
	}

	// Confirmation de la validation
	if ($action == 'valid') {
		$object->date_commande = dol_now();

		// We check if number is temporary number
		if (preg_match('/^[\(]?PROV/i', $object->ref) || empty($object->ref)) { // empty should not happened, but when it occurs, the test save life
			$newref = $object->getNextNumRef($object->thirdparty);
		} else {
			$newref = $object->ref;
		}

		if ($newref < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
			$action = '';
		} else {
			$text = $langs->trans('ConfirmValidateOrder', $newref);
			if (isModEnabled('notification')) {
				require_once DOL_DOCUMENT_ROOT.'/core/class/notify.class.php';
				$notify = new	Notify($db);
				$text .= '<br>';
				$text .= $notify->confirmMessage('ORDER_SUPPLIER_VALIDATE', $object->socid, $object);
			}

			$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ValidateOrder'), $text, 'confirm_valid', '', 0, 1);
		}
	}

	// Confirm approval
	if ($action == 'approve' || $action == 'approve2') {
		$qualified_for_stock_change = 0;
		if (!getDolGlobalString('STOCK_SUPPORTS_SERVICES')) {
			$qualified_for_stock_change = $object->hasProductsOrServices(2);
		} else {
			$qualified_for_stock_change = $object->hasProductsOrServices(1);
		}

		$formquestion = array();
		if (isModEnabled('stock') && getDolGlobalString('STOCK_CALCULATE_ON_SUPPLIER_VALIDATE_ORDER') && $qualified_for_stock_change) {
			$langs->load("stocks");
			require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
			$formproduct = new FormProduct($db);
			$forcecombo = 0;
			if ($conf->browser->name == 'ie') {
				$forcecombo = 1; // There is a bug in IE10 that make combo inside popup crazy
			}
			$formquestion = array(
				//'text' => $langs->trans("ConfirmClone"),
				//array('type' => 'checkbox', 'name' => 'clone_content',   'label' => $langs->trans("CloneMainAttributes"),   'value' => 1),
				//array('type' => 'checkbox', 'name' => 'update_prices',   'label' => $langs->trans("PuttingPricesUpToDate"),   'value' => 1),
				array('type' => 'other', 'name' => 'idwarehouse', 'label' => $langs->trans("SelectWarehouseForStockIncrease"), 'value' => $formproduct->selectWarehouses(GETPOSTINT('idwarehouse'), 'idwarehouse', '', 1, 0, 0, '', 0, $forcecombo))
			);
		}
		$text = $langs->trans("ConfirmApproveThisOrder", $object->ref);
		if (isModEnabled('notification')) {
			require_once DOL_DOCUMENT_ROOT.'/core/class/notify.class.php';
			$notify = new	Notify($db);
			$text .= '<br>';
			$text .= $notify->confirmMessage('ORDER_SUPPLIER_APPROVE', $object->socid, $object);
		}

		$formconfirm = $form->formconfirm($_SERVER['PHP_SELF']."?id=".$object->id, $langs->trans("ApproveThisOrder"), $text, "confirm_".$action, $formquestion, 1, 1, 240);
	}

	// Confirmation of disapproval
	if ($action == 'refuse') {
		$formquestion = array(
			array(
				'type' => 'text',
				'name' => 'refuse_note',
				'label' => $langs->trans("Reason"),
				'value' => '',
				'morecss' => 'minwidth300'
			)
		);
		$formconfirm  = $form->formconfirm($_SERVER['PHP_SELF']."?id=$object->id", $langs->trans("DenyingThisOrder"), $langs->trans("ConfirmDenyingThisOrder", $object->ref), "confirm_refuse", $formquestion, 0, 1);
	}

	// Confirmation of cancellation
	if ($action == 'cancel') {
		$formquestion = array(
			array(
				'type' => 'text',
				'name' => 'cancel_note',
				'label' => $langs->trans("Reason"),
				'value' => '',
				'morecss' => 'minwidth300'
			)
		);
		if (!empty($conf->notification->enabled)) {
			require_once DOL_DOCUMENT_ROOT.'/core/class/notify.class.php';
			$notify = new	Notify($db);
			$text .= '<br>';
			$text .= $notify->confirmMessage('ORDER_SUPPLIER_CANCEL', $object->socid, $object);
		}
		$formconfirm = $form->formconfirm($_SERVER['PHP_SELF']."?id=$object->id", $langs->trans("Cancel"), $langs->trans("ConfirmCancelThisOrder", $object->ref), "confirm_cancel", $formquestion, 0, 1);
	}

	// Confirmation de l'envoi de la commande
	if ($action == 'commande') {
		$date_com = dol_mktime(GETPOST('rehour'), GETPOST('remin'), GETPOST('resec'), GETPOST("remonth"), GETPOST("reday"), GETPOST("reyear"));
		if (!empty($conf->notification->enabled)) {
			require_once DOL_DOCUMENT_ROOT.'/core/class/notify.class.php';
			$notify = new	Notify($db);
			$text .= '<br>';
			$text .= $notify->confirmMessage('ORDER_SUPPLIER_SUBMIT', $object->socid, $object);
		}
		$formconfirm = $form->formconfirm($_SERVER['PHP_SELF']."?id=".$object->id."&datecommande=".$date_com."&methode=".GETPOST("methodecommande")."&comment=".urlencode(GETPOST("comment")), $langs->trans("MakeOrder"), $langs->trans("ConfirmMakeOrder", dol_print_date($date_com, 'day')), "confirm_commande", '', 0, 2);
	}

	// Confirmation to delete line
	if ($action == 'ask_deleteline') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&lineid='.$lineid, $langs->trans('DeleteProductLine'), $langs->trans('ConfirmDeleteProductLine'), 'confirm_deleteline', '', 0, 1);
	}

	$parameters = array('formConfirm' => $formconfirm, 'lineid' => $lineid);
	$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	if (empty($reshook)) {
		$formconfirm .= $hookmanager->resPrint;
	} elseif ($reshook > 0) {
		$formconfirm = $hookmanager->resPrint;
	}

	// Print form confirm
	print $formconfirm;


	// Supplier order card

	$linkback = '<a href="'.DOL_URL_ROOT.'/fourn/commande/list.php?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

	$morehtmlref = '<div class="refidno">';
	// Ref supplier
	$morehtmlref .= $form->editfieldkey("RefSupplier", 'ref_supplier', $object->ref_supplier, $object, $usercancreate, 'string', '', 0, 1);
	$morehtmlref .= $form->editfieldval("RefSupplier", 'ref_supplier', $object->ref_supplier, $object, $usercancreate, 'string'.(isset($conf->global->THIRDPARTY_REF_INPUT_SIZE) ? ':' . getDolGlobalString('THIRDPARTY_REF_INPUT_SIZE') : ''), '', null, null, '', 1);
	// Thirdparty
	$morehtmlref .= '<br>';
	if (getDolGlobalString('MAIN_CAN_EDIT_SUPPLIER_ON_SUPPLIER_ORDER') && !empty($usercancreate) && $action == 'edit_thirdparty') {
		$morehtmlref .= '<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
		$morehtmlref .= '<input type="hidden" name="action" value="set_thirdparty">';
		$morehtmlref .= '<input type="hidden" name="token" value="'.newToken().'">';
		$filter = '(s.fournisseur:=:1)';
		$morehtmlref .= $form->select_company($object->thirdparty->id, 'new_socid', $filter, '', 0, 0, array(), 0, 'minwidth300');
		$morehtmlref .= '<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
		$morehtmlref .= '</form>';
	}
	if (!getDolGlobalString('MAIN_CAN_EDIT_SUPPLIER_ON_SUPPLIER_ORDER') || $action != 'edit_thirdparty') {
		if (getDolGlobalString('MAIN_CAN_EDIT_SUPPLIER_ON_SUPPLIER_ORDER') && $object->statut == CommandeFournisseur::STATUS_DRAFT) {
			$morehtmlref .= '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?action=edit_thirdparty&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetThirdParty')).'</a>';
		}
		$morehtmlref .= $object->thirdparty->getNomUrl(1, 'supplier');
		if (!getDolGlobalString('MAIN_DISABLE_OTHER_LINK') && $object->thirdparty->id > 0) {
			$morehtmlref .= ' (<a href="'.DOL_URL_ROOT.'/fourn/commande/list.php?socid='.$object->thirdparty->id.'&search_company='.urlencode($object->thirdparty->name).'">'.$langs->trans("OtherOrders").'</a>)';
		}
	}

	// Project
	if (isModEnabled('project')) {
		$langs->load("projects");
		$morehtmlref .= '<br>';
		if ($permissiontoadd) {
			$morehtmlref .= img_picto($langs->trans("Project"), 'project', 'class="pictofixedwidth"');
			if ($action != 'classify' && $caneditproject) {
				$morehtmlref .= '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?action=classify&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetProject')).'</a> ';
			}
			$morehtmlref .= $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, (!getDolGlobalString('PROJECT_CAN_ALWAYS_LINK_TO_ALL_SUPPLIERS') ? $object->socid : -1), $object->fk_project, ($action == 'classify' ? 'projectid' : 'none'), 1, 0, 0, 1, '', 'maxwidth300');
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

	// Call Hook tabContentViewSupplierOrder
	$parameters = array();
	// Note that $action and $object may be modified by hook
	$reshook = $hookmanager->executeHooks('tabContentViewSupplierOrder', $parameters, $object, $action);
	if (empty($reshook)) {
		print '<div class="fichecenter">';
		print '<div class="fichehalfleft">';
		print '<div class="underbanner clearboth"></div>';

		print '<table class="border tableforfield centpercent">';

		// Date
		if ($object->methode_commande_id > 0) {
			$usehourmin = 0;
			if (getDolGlobalString('SUPPLIER_ORDER_USE_HOUR_FOR_DELIVERY_DATE')) {
				$usehourmin = 1;
			}
			print '<tr><td class="titlefield">'.$langs->trans("Date").'</td><td>';
			print $object->date_commande ? dol_print_date($object->date_commande, $usehourmin ? 'dayhour' : 'day') : '';
			if ($object->hasDelay() && !empty($object->delivery_date) && !empty($object->date_commande)) {
				print ' '.img_picto($langs->trans("Late").' : '.$object->showDelay(), "warning");
			}
			print "</td></tr>";

			if ($object->methode_commande) {
				print '<tr><td>'.$langs->trans("Method").'</td><td>'.$object->getInputMethod().'</td></tr>';
			}
		}

		// Author
		print '<tr><td class="titlefield">'.$langs->trans("AuthorRequest").'</td>';
		print '<td>'.$author->getNomUrl(-1, '', 0, 0, 0).'</td>';
		print '</tr>';

		// Relative and absolute discounts
		if (getDolGlobalString('FACTURE_SUPPLIER_DEPOSITS_ARE_JUST_PAYMENTS')) {
			$filterabsolutediscount = "fk_invoice_supplier_source IS NULL"; // If we want deposit to be subtracted to payments only and not to total of final invoice
			$filtercreditnote = "fk_invoice_supplier_source IS NOT NULL"; // If we want deposit to be subtracted to payments only and not to total of final invoice
		} else {
			$filterabsolutediscount = "fk_invoice_supplier_source IS NULL OR (description LIKE '(DEPOSIT)%' AND description NOT LIKE '(EXCESS PAID)%')";
			$filtercreditnote = "fk_invoice_supplier_source IS NOT NULL AND (description NOT LIKE '(DEPOSIT)%' OR description LIKE '(EXCESS PAID)%')";
		}

		$absolute_discount = $societe->getAvailableDiscounts(null, $filterabsolutediscount, 0, 1);
		$absolute_creditnote = $societe->getAvailableDiscounts(null, $filtercreditnote, 0, 1);
		$absolute_discount = price2num($absolute_discount, 'MT');
		$absolute_creditnote = price2num($absolute_creditnote, 'MT');

		print '<tr><td class="titlefield">'.$langs->trans('Discounts').'</td><td>';

		$thirdparty = $societe;
		$discount_type = 1;
		$backtopage = urlencode($_SERVER["PHP_SELF"].'?id='.$object->id);
		include DOL_DOCUMENT_ROOT.'/core/tpl/object_discounts.tpl.php';

		print '</td></tr>';

		// Default terms of the settlement
		$langs->load('bills');
		print '<tr><td class="nowrap">';
		print '<table class="nobordernopadding centpercent"><tr><td class="nowrap">';
		print $langs->trans('PaymentConditions');
		print '<td>';
		if ($action != 'editconditions' && $permissiontoadd) {
			print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editconditions&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->trans('SetConditions'), 1).'</a></td>';
		}
		print '</tr></table>';
		print '</td><td>';
		if ($action == 'editconditions') {
			$form->form_conditions_reglement($_SERVER['PHP_SELF'].'?id='.$object->id, $object->cond_reglement_id, 'cond_reglement_id');
		} else {
			$form->form_conditions_reglement($_SERVER['PHP_SELF'].'?id='.$object->id, $object->cond_reglement_id, 'none');
		}
		print "</td>";
		print '</tr>';

		// Mode of payment
		$langs->load('bills');
		print '<tr><td class="nowrap">';
		print '<table class="nobordernopadding centpercent"><tr><td class="nowrap">';
		print $langs->trans('PaymentMode');
		print '</td>';
		if ($action != 'editmode' && $permissiontoadd) {
			print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editmode&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->trans('SetMode'), 1).'</a></td>';
		}
		print '</tr></table>';
		print '</td><td>';
		if ($action == 'editmode') {
			$form->form_modes_reglement($_SERVER['PHP_SELF'].'?id='.$object->id, $object->mode_reglement_id, 'mode_reglement_id', 'DBIT', 1, 1);
		} else {
			$form->form_modes_reglement($_SERVER['PHP_SELF'].'?id='.$object->id, $object->mode_reglement_id, 'none');
		}
		print '</td></tr>';

		// Multicurrency
		if (isModEnabled("multicurrency")) {
			// Multicurrency code
			print '<tr>';
			print '<td>';
			print '<table class="nobordernopadding centpercent"><tr><td>';
			print $form->editfieldkey('Currency', 'multicurrency_code', '', $object, 0);
			print '</td>';
			if ($action != 'editmulticurrencycode' && $object->statut == $object::STATUS_DRAFT && $permissiontoadd) {
				print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editmulticurrencycode&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetMultiCurrencyCode'), 1).'</a></td>';
			}
			print '</tr></table>';
			print '</td><td>';
			if ($action == 'editmulticurrencycode') {
				$form->form_multicurrency_code($_SERVER['PHP_SELF'].'?id='.$object->id, $object->multicurrency_code, 'multicurrency_code');
			} else {
				$form->form_multicurrency_code($_SERVER['PHP_SELF'].'?id='.$object->id, $object->multicurrency_code, 'none');
			}
			print '</td></tr>';

			// Multicurrency rate
			if ($object->multicurrency_code != $conf->currency || $object->multicurrency_tx != 1) {
				print '<tr>';
				print '<td>';
				print '<table class="nobordernopadding centpercent"><tr>';
				print '<td>';
				print $form->editfieldkey('CurrencyRate', 'multicurrency_tx', '', $object, 0);
				print '</td>';
				if ($action != 'editmulticurrencyrate' && $object->statut == $object::STATUS_DRAFT && $object->multicurrency_code && $object->multicurrency_code != $conf->currency) {
					print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editmulticurrencyrate&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetMultiCurrencyCode'), 1).'</a></td>';
				}
				print '</tr></table>';
				print '</td><td>';
				if ($action == 'editmulticurrencyrate' || $action == 'actualizemulticurrencyrate') {
					if ($action == 'actualizemulticurrencyrate') {
						list($object->fk_multicurrency, $object->multicurrency_tx) = MultiCurrency::getIdAndTxFromCode($object->db, $object->multicurrency_code);
					}
					$form->form_multicurrency_rate($_SERVER['PHP_SELF'].'?id='.$object->id, $object->multicurrency_tx, 'multicurrency_tx', $object->multicurrency_code);
				} else {
					$form->form_multicurrency_rate($_SERVER['PHP_SELF'].'?id='.$object->id, $object->multicurrency_tx, 'none', $object->multicurrency_code);
					if ($object->statut == $object::STATUS_DRAFT && $object->multicurrency_code && $object->multicurrency_code != $conf->currency) {
						print '<div class="inline-block"> &nbsp; &nbsp; &nbsp; &nbsp; ';
						print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=actualizemulticurrencyrate">'.$langs->trans("ActualizeCurrency").'</a>';
						print '</div>';
					}
				}
				print '</td></tr>';
			}
		}

		// Bank Account
		if (getDolGlobalString('BANK_ASK_PAYMENT_BANK_DURING_SUPPLIER_ORDER') && isModEnabled("bank")) {
			print '<tr><td class="nowrap">';
			print '<table class="nobordernopadding centpercent"><tr><td class="nowrap">';
			print $langs->trans('BankAccount');
			print '<td>';
			if ($action != 'editbankaccount' && $permissiontoadd) {
				print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editbankaccount&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->trans('SetBankAccount'), 1).'</a></td>';
			}
			print '</tr></table>';
			print '</td><td>';
			if ($action == 'editbankaccount') {
				$form->formSelectAccount($_SERVER['PHP_SELF'].'?id='.$object->id, $object->fk_account, 'fk_account', 1);
			} else {
				$form->formSelectAccount($_SERVER['PHP_SELF'].'?id='.$object->id, $object->fk_account, 'none');
			}
			print '</td>';
			print '</tr>';
		}

		// Delivery delay (in days)
		print '<tr>';
		print '<td>'.$langs->trans('NbDaysToDelivery').'&nbsp;'.img_picto($langs->trans('DescNbDaysToDelivery'), 'info', 'style="cursor:help"').'</td>';
		print '<td>'.$object->getMaxDeliveryTimeDay($langs).'</td>';
		print '</tr>';

		// Delivery date planned
		print '<tr><td>';
		print '<table class="nobordernopadding centpercent"><tr><td>';
		print $langs->trans('DateDeliveryPlanned');
		print '</td>';
		if ($action != 'editdate_livraison') {
			print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editdate_livraison&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->trans('SetDeliveryDate'), 1).'</a></td>';
		}
		print '</tr></table>';
		print '</td><td>';
		if ($action == 'editdate_livraison') {
			print '<form name="setdate_livraison" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="post">';
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<input type="hidden" name="action" value="setdate_livraison">';
			$usehourmin = 0;
			if (getDolGlobalString('SUPPLIER_ORDER_USE_HOUR_FOR_DELIVERY_DATE')) {
				$usehourmin = 1;
			}
			print $form->selectDate($object->delivery_date ? $object->delivery_date : -1, 'liv_', $usehourmin, $usehourmin, 0, "setdate_livraison");
			print '<input type="submit" class="button button-edit smallpaddingimp valign middle" value="'.$langs->trans('Modify').'">';
			print '</form>';
		} else {
			$usehourmin = 'day';
			if (getDolGlobalString('SUPPLIER_ORDER_USE_HOUR_FOR_DELIVERY_DATE')) {
				$usehourmin = 'dayhour';
			}
			print $object->delivery_date ? dol_print_date($object->delivery_date, $usehourmin) : '&nbsp;';
			if ($object->hasDelay() && !empty($object->delivery_date) && ($object->statut == $object::STATUS_ORDERSENT || $object->statut == $object::STATUS_RECEIVED_PARTIALLY)) {
				print ' '.img_picto($langs->trans("Late").' : '.$object->showDelay(), "warning");
			}
		}
		print '</td></tr>';

		// Incoterms
		if (isModEnabled('incoterm')) {
			print '<tr><td>';
			print '<table class="nobordernopadding centpercent"><tr><td>';
			print $langs->trans('IncotermLabel');
			print '<td><td class="right">';
			if ($usercancreate) {
				print '<a class="editfielda" href="'.DOL_URL_ROOT.'/fourn/commande/card.php?id='.$object->id.'&action=editincoterm&token='.newToken().'">'.img_edit().'</a>';
			} else {
				print '&nbsp;';
			}
			print '</td></tr></table>';
			print '</td>';
			print '<td>';
			if ($action != 'editincoterm') {
				print $form->textwithpicto(dol_escape_htmltag($object->display_incoterms()), $object->label_incoterms, 1);
			} else {
				print $form->select_incoterms((!empty($object->fk_incoterms) ? $object->fk_incoterms : ''), (!empty($object->location_incoterms) ? $object->location_incoterms : ''), $_SERVER['PHP_SELF'].'?id='.$object->id);
			}
			print '</td></tr>';
		}

		// Other attributes
		include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

		print '</table>';

		print '</div>';
		print '<div class="fichehalfright">';
		print '<div class="underbanner clearboth"></div>';

		print '<table class="border tableforfield centpercent">';

		print '<tr>';
		// Amount HT
		print '<td class="titlefieldmiddle">' . $langs->trans('AmountHT') . '</td>';
		print '<td class="nowrap amountcard right">' . price($object->total_ht, 0, $langs, 0, -1, -1, $conf->currency) . '</td>';
		if (isModEnabled("multicurrency") && ($object->multicurrency_code && $object->multicurrency_code != $conf->currency)) {
			// Multicurrency Amount HT
			print '<td class="nowrap amountcard right">' . price($object->multicurrency_total_ht, 0, $langs, 0, -1, -1, $object->multicurrency_code) . '</td>';
		}
		print '</tr>';

		print '<tr>';
		// Amount VAT
		print '<td class="titlefieldmiddle">' . $langs->trans('AmountVAT') . '</td>';
		print '<td class="nowrap amountcard right">' . price($object->total_tva, 0, $langs, 0, -1, -1, $conf->currency) . '</td>';
		if (isModEnabled("multicurrency") && ($object->multicurrency_code && $object->multicurrency_code != $conf->currency)) {
			// Multicurrency Amount VAT
			print '<td class="nowrap amountcard right">' . price($object->multicurrency_total_tva, 0, $langs, 0, -1, -1, $object->multicurrency_code) . '</td>';
		}
		print '</tr>';

		// Amount Local Taxes
		if ($mysoc->localtax1_assuj == "1" || $object->total_localtax1 != 0) {
			print '<tr>';
			print '<td class="titlefieldmiddle">' . $langs->transcountry("AmountLT1", $mysoc->country_code) . '</td>';
			print '<td class="nowrap amountcard right">' . price($object->total_localtax1, 0, $langs, 0, -1, -1, $conf->currency) . '</td>';
			if (isModEnabled("multicurrency") && ($object->multicurrency_code && $object->multicurrency_code != $conf->currency)) {
				print '<td class="nowrap amountcard right">' . price($object->total_localtax1, 0, $langs, 0, -1, -1, $object->multicurrency_code) . '</td>';
			}
			print '</tr>';

			if ($mysoc->localtax2_assuj == "1" || $object->total_localtax2 != 0) {
				print '<tr>';
				print '<td>' . $langs->transcountry("AmountLT2", $mysoc->country_code) . '</td>';
				print '<td class="nowrap amountcard right">' . price($object->total_localtax2, 0, $langs, 0, -1, -1, $conf->currency) . '</td>';
				if (isModEnabled("multicurrency") && ($object->multicurrency_code && $object->multicurrency_code != $conf->currency)) {
					print '<td class="nowrap amountcard right">' . price($object->total_localtax2, 0, $langs, 0, -1, -1, $object->multicurrency_code) . '</td>';
				}
				print '</tr>';
			}
		}

		$alert = '';
		if (getDolGlobalString('ORDER_MANAGE_MIN_AMOUNT') && $object->total_ht < $object->thirdparty->supplier_order_min_amount) {
			$alert = ' ' . img_warning($langs->trans('OrderMinAmount') . ': ' . price($object->thirdparty->supplier_order_min_amount));
		}

		print '<tr>';
		// Amount TTC
		print '<td>' . $langs->trans('AmountTTC') . '</td>';
		print '<td class="nowrap amountcard right">' . price($object->total_ttc, 0, $langs, 0, -1, -1, $conf->currency) . $alert . '</td>';
		if (isModEnabled("multicurrency") && ($object->multicurrency_code && $object->multicurrency_code != $conf->currency)) {
			// Multicurrency Amount TTC
			print '<td class="nowrap amountcard right">' . price($object->multicurrency_total_ttc, 0, $langs, 0, -1, -1, $object->multicurrency_code) . '</td>';
		}
		print '</tr>';

		print '</table>';

		// Margin Infos
		/*if (isModEnabled('margin')) {
			$formmargin->displayMarginInfos($object);
		}*/


		print '</div>';
		print '</div>';

		print '<div class="clearboth"></div><br>';

		if (getDolGlobalString('MAIN_DISABLE_CONTACTS_TAB')) {
			$blocname = 'contacts';
			$title = $langs->trans('ContactsAddresses');
			include DOL_DOCUMENT_ROOT.'/core/tpl/bloc_showhide.tpl.php';
		}

		if (getDolGlobalString('MAIN_DISABLE_NOTES_TAB')) {
			$blocname = 'notes';
			$title = $langs->trans('Notes');
			include DOL_DOCUMENT_ROOT.'/core/tpl/bloc_showhide.tpl.php';
		}

		/*
		 * Lines
		 */
		//$result = $object->getLinesArray();


		print '	<form name="addproduct" id="addproduct" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.(($action != 'editline') ? '' : '#line_'.GETPOSTINT('lineid')).'" method="POST">
		<input type="hidden" name="token" value="'.newToken().'">
		<input type="hidden" name="action" value="' . (($action != 'editline') ? 'addline' : 'updateline').'">
		<input type="hidden" name="mode" value="">
		<input type="hidden" name="page_y" value="">
		<input type="hidden" name="id" value="'.$object->id.'">
		<input type="hidden" name="socid" value="'.$societe->id.'">
		';

		if (!empty($conf->use_javascript_ajax) && $object->statut == 0) {
			include DOL_DOCUMENT_ROOT.'/core/tpl/ajaxrow.tpl.php';
		}

		print '<div class="div-table-responsive-no-min">';
		print '<table id="tablelines" class="noborder noshadow centpercent">';

		// Add free products/services form
		global $forceall, $senderissupplier, $dateSelector, $inputalsopricewithtax;
		$forceall = 1;
		$dateSelector = 0;
		$inputalsopricewithtax = 1;
		$senderissupplier = 2; // $senderissupplier=2 is same than 1 but disable test on minimum qty and disable autofill qty with minimum.
		if (getDolGlobalString('SUPPLIER_ORDER_WITH_PREDEFINED_PRICES_ONLY')) {
			$senderissupplier = 1;
		}

		// Show object lines
		if (!empty($object->lines)) {
			$object->printObjectLines($action, $object->thirdparty, $mysoc, $lineid, 1);
		}

		$num = count($object->lines);

		// Form to add new line
		if ($object->statut == CommandeFournisseur::STATUS_DRAFT && $usercancreate) {
			if ($action != 'editline') {
				// Add free products/services

				$parameters = array();
				$reshook = $hookmanager->executeHooks('formAddObjectLine', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
				if ($reshook < 0) {
					setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
				}
				if (empty($reshook)) {
					$object->formAddObjectLine(1, $societe, $mysoc);
				}
			}
		}
		print '</table>';
		print '</div>';
		print '</form>';
	}

	print dol_get_fiche_end();

	/**
	 * Buttons for actions
	 */

	if ($user->socid == 0 && $action != 'delete') {
		if ($action != 'createorder' && $action != 'presend' && $action != 'editline') {
			print '<div class="tabsAction">';

			$parameters = array();
			$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been
			// modified by hook
			if (empty($reshook)) {
				$object->fetchObjectLinked(); // Links are used to show or not button, so we load them now.

				// Validate
				if ($object->statut == 0 && $num > 0) {
					if ($usercanvalidate) {
						$tmpbuttonlabel = $langs->trans('Validate');
						if ($usercanapprove && !getDolGlobalString('SUPPLIER_ORDER_NO_DIRECT_APPROVE')) {
							$tmpbuttonlabel = $langs->trans("ValidateAndApprove");
						}

						print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=valid&token='.newToken().'">';
						print $tmpbuttonlabel;
						print '</a>';
					}
				}
				// Create event
				/*if (isModEnabled('agenda') && getDolGlobalString('MAIN_ADD_EVENT_ON_ELEMENT_CARD')) 	// Add hidden condition because this is not a "workflow" action so should appears somewhere else on page.
				{
					print '<div class="inline-block divButAction"><a class="butAction" href="' . DOL_URL_ROOT . '/comm/action/card.php?action=create&amp;origin=' . $object->element . '&amp;originid=' . $object->id . '&amp;socid=' . $object->socid . '">' . $langs->trans("AddAction") . '</a></div>';
				}*/

				// Modify
				if ($object->statut == CommandeFournisseur::STATUS_VALIDATED) {
					if ($usercanorder) {
						print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=reopen&token='.newToken().'">'.$langs->trans("Modify").'</a>';
					}
				}

				// Approve
				if ($object->statut == CommandeFournisseur::STATUS_VALIDATED) {
					if ($usercanapprove) {
						if (getDolGlobalString('SUPPLIER_ORDER_3_STEPS_TO_BE_APPROVED') && $object->total_ht >= $conf->global->SUPPLIER_ORDER_3_STEPS_TO_BE_APPROVED && !empty($object->user_approve_id)) {
							print '<a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("FirstApprovalAlreadyDone")).'">'.$langs->trans("ApproveOrder").'</a>';
						} else {
							print '<a class="butAction"	href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=approve">'.$langs->trans("ApproveOrder").'</a>';
						}
					} else {
						print '<a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("NotAllowed")).'">'.$langs->trans("ApproveOrder").'</a>';
					}
				}

				// Second approval (if option SUPPLIER_ORDER_3_STEPS_TO_BE_APPROVED is set)
				if (getDolGlobalString('SUPPLIER_ORDER_3_STEPS_TO_BE_APPROVED') && $object->total_ht >= $conf->global->SUPPLIER_ORDER_3_STEPS_TO_BE_APPROVED) {
					if ($object->statut == CommandeFournisseur::STATUS_VALIDATED) {
						if ($usercanapprovesecond) {
							if (!empty($object->user_approve_id2)) {
								print '<a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("SecondApprovalAlreadyDone")).'">'.$langs->trans("Approve2Order").'</a>';
							} else {
								print '<a class="butAction"	href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=approve2">'.$langs->trans("Approve2Order").'</a>';
							}
						} else {
							print '<a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("NotAllowed")).'">'.$langs->trans("Approve2Order").'</a>';
						}
					}
				}

				// Refuse
				if ($object->statut == CommandeFournisseur::STATUS_VALIDATED) {
					if ($usercanapprove || $usercanapprovesecond) {
						print '<a class="butAction"	href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=refuse">'.$langs->trans("RefuseOrder").'</a>';
					} else {
						print '<a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("NotAllowed")).'">'.$langs->trans("RefuseOrder").'</a>';
					}
				}

				// Send
				if (empty($user->socid)) {
					if (in_array($object->statut, array(CommandeFournisseur::STATUS_ACCEPTED, 3, 4, 5)) || getDolGlobalString('SUPPLIER_ORDER_SENDBYEMAIL_FOR_ALL_STATUS')) {
						if ($usercanorder) {
							print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=presend&mode=init#formmailbeforetitle">'.$langs->trans('SendMail').'</a>';
						}
					}
				}

				// Reopen
				if (in_array($object->statut, array(CommandeFournisseur::STATUS_ACCEPTED))) {
					$buttonshown = 0;
					if (!$buttonshown && $usercanapprove) {
						if (!getDolGlobalString('SUPPLIER_ORDER_REOPEN_BY_APPROVER_ONLY')
							|| (getDolGlobalString('SUPPLIER_ORDER_REOPEN_BY_APPROVER_ONLY') && $user->id == $object->user_approve_id)) {
							print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=reopen&token='.newToken().'">'.$langs->trans("Disapprove").'</a>';
							$buttonshown++;
						}
					}
					if (!$buttonshown && $usercanapprovesecond && getDolGlobalString('SUPPLIER_ORDER_3_STEPS_TO_BE_APPROVED')) {
						if (!getDolGlobalString('SUPPLIER_ORDER_REOPEN_BY_APPROVER2_ONLY')
							|| (getDolGlobalString('SUPPLIER_ORDER_REOPEN_BY_APPROVER2_ONLY') && $user->id == $object->user_approve_id2)) {
							print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=reopen&token='.newToken().'">'.$langs->trans("Disapprove").'</a>';
						}
					}
				}
				if (in_array($object->statut, array(3, 4, 5, 6, 7, 9))) {
					if ($usercanorder) {
						print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=reopen&token='.newToken().'">'.$langs->trans("ReOpen").'</a>';
					}
				}

				// Ship
				$hasreception = 0;
				if (isModEnabled('stock') && (getDolGlobalString('STOCK_CALCULATE_ON_SUPPLIER_DISPATCH_ORDER') || getDolGlobalString('STOCK_CALCULATE_ON_RECEPTION') || getDolGlobalString('STOCK_CALCULATE_ON_RECEPTION_CLOSE'))) {
					$labelofbutton = $langs->trans('ReceiveProducts');
					if (isModEnabled('reception')) {
						$labelofbutton = $langs->trans("CreateReception");
						if (!empty($object->linkedObjects['reception'])) {
							foreach ($object->linkedObjects['reception'] as $element) {
								if ($element->statut >= 0) {
									$hasreception = 1;
									break;
								}
							}
						}
					}

					if (in_array($object->statut, array(3, 4, 5))) {
						if (isModEnabled("supplier_order") && $usercanreceive) {
							print '<div class="inline-block divButAction"><a class="butAction" href="'.DOL_URL_ROOT.'/fourn/commande/dispatch.php?id='.$object->id.'">'.$labelofbutton.'</a></div>';
						} else {
							print '<div class="inline-block divButAction"><a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("NotAllowed")).'">'.$labelofbutton.'</a></div>';
						}
					}
				}

				if ($object->statut == CommandeFournisseur::STATUS_ACCEPTED) {
					if ($usercanorder) {
						print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=createorder&token='.newToken().'#makeorder">'.$langs->trans("MakeOrder").'</a></div>';
					} else {
						print '<div class="inline-block divButAction"><a class="butActionRefused classfortooltip" href="#">'.$langs->trans("MakeOrder").'</a></div>';
					}
				}

				// Classify received (this does not record reception)
				if ($object->statut == CommandeFournisseur::STATUS_ORDERSENT || $object->statut == CommandeFournisseur::STATUS_RECEIVED_PARTIALLY) {
					if ($usercanreceive) {
						print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&token='.newToken().'&action=classifyreception#classifyreception">'.$langs->trans("ClassifyReception").'</a></div>';
					}
				}

				// Create bill
				//if (isModEnabled('facture'))
				//{
				if (isModEnabled("supplier_invoice") && ($object->statut >= 2 && $object->statut != 7 && $object->billed != 1)) {  // statut 2 means approved, 7 means canceled
					if ($user->hasRight('fournisseur', 'facture', 'creer') || $user->hasRight("supplier_invoice", "creer")) {
						print '<a class="butAction" href="'.DOL_URL_ROOT.'/fourn/facture/card.php?action=create&amp;origin='.$object->element.'&amp;originid='.$object->id.'&amp;socid='.$object->socid.'">'.$langs->trans("SupplierOrderCreateBill").'</a>';
					}
				}
				//}

				// Classify billed manually (need one invoice if module invoice is on, no condition on invoice if not)
				if ($usercancreate && $object->statut >= 2 && $object->statut != 7 && $object->billed != 1) {  // statut 2 means approved
					if (!isModEnabled('invoice')) {
						print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=classifybilled&token='.newToken().'">'.$langs->trans("ClassifyBilled").'</a>';
					} else {
						if (!empty($object->linkedObjectsIds['invoice_supplier']) || (empty($object->linkedObjectsIds['invoice_supplier']) && !getDolGlobalInt('SUPPLIER_ORDER_DISABLE_CLASSIFY_BILLED_FROM_SUPPLIER_ORDER'))) {
							if ($user->hasRight('fournisseur', 'facture', 'creer') || $user->hasRight("supplier_invoice", "creer")) {
								print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=classifybilled&token='.newToken().'">'.$langs->trans("ClassifyBilled").'</a>';
							}
						} else {
							print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NeedAtLeastOneInvoice")).'">'.$langs->trans("ClassifyBilled").'</a>';
						}
					}
				}

				// Classify unbilled manually
				if ($usercancreate && $object->billed > 0 && $object->statut > $object::STATUS_DRAFT) {
					print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=classifyunbilled&token='.newToken().'">'.$langs->trans("ClassifyUnbilled").'</a>';
				}

				// Clone
				if ($usercancreate) {
					print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;socid='.$object->socid.'&amp;action=clone&amp;token='.newToken().'&amp;object=order">'.$langs->trans("ToClone").'</a>';
				}

				// Cancel
				if ($object->statut == CommandeFournisseur::STATUS_ACCEPTED) {
					if ($usercanorder) {
						print '<a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=cancel&amp;token='.newToken().'">'.$langs->trans("CancelOrder").'</a>';
					}
				}

				// Delete
				if (!empty($usercandelete)) {
					if ($hasreception) {
						print '<a class="butActionRefused classfortooltip" href="#" title="'.$langs->trans("ReceptionExist").'">'.$langs->trans("Delete").'</a>';
					} else {
						print '<a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=delete&token='.newToken().'">'.$langs->trans("Delete").'</a>';
					}
				}
			}

			print "</div>";
		}

		if ($usercanorder && $object->statut == CommandeFournisseur::STATUS_ACCEPTED && $action == 'createorder') {
			// Set status to ordered (action=commande)
			print '<!-- form to record supplier order -->'."\n";
			print '<form name="commande" id="makeorder" action="card.php?id='.$object->id.'&amp;action=commande" method="POST">';

			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<input type="hidden"	name="action" value="commande">';
			print load_fiche_titre($langs->trans("ToOrder"), '', '');
			print '<table class="noborder centpercent">';
			//print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("ToOrder").'</td></tr>';
			print '<tr><td class="fieldrequired">'.$langs->trans("OrderDate").'</td><td>';
			$date_com = dol_mktime(GETPOSTINT('rehour'), GETPOSTINT('remin'), GETPOSTINT('resec'), GETPOSTINT('remonth'), GETPOSTINT('reday'), GETPOSTINT('reyear'));
			print $form->selectDate($date_com ?: '', '', 0, 0, 0, "commande", 1, 1);
			print '</td></tr>';

			// Force mandatory order method
			print '<tr><td class="fieldrequired">'.$langs->trans("OrderMode").'</td><td>';
			$formorder->selectInputMethod(GETPOST('methodecommande'), "methodecommande", 1);
			print '</td></tr>';

			print '<tr><td>'.$langs->trans("Comment").'</td><td><input class="quatrevingtpercent" type="text" name="comment" value="'.GETPOST('comment').'"></td></tr>';

			print '<tr><td class="center" colspan="2">';
			print '<input type="submit" name="makeorder" class="button" value="'.$langs->trans("ToOrder").'">';
			print ' &nbsp; &nbsp; ';
			print '<input type="submit" name="cancel" class="button button-cancel" value="'.$langs->trans("Cancel").'">';
			print '</td></tr>';
			print '</table>';

			print '</form>';
			print "<br>";
		}

		// Select mail models is same action as presend
		if (GETPOST('modelselected')) {
			$action = 'presend';
		}

		if ($action != 'createorder' && $action != 'presend') {
			print '<div class="fichecenter"><div class="fichehalfleft">';

			// Generated documents
			$objref = dol_sanitizeFileName($object->ref);
			$file = $conf->fournisseur->dir_output.'/commande/'.$objref.'/'.$objref.'.pdf';
			$relativepath = $objref.'/'.$objref.'.pdf';
			$filedir = $conf->fournisseur->dir_output.'/commande/'.$objref;
			$urlsource = $_SERVER["PHP_SELF"]."?id=".$object->id;
			$genallowed = $usercanread;
			$delallowed = $usercancreate;
			$modelpdf = (!empty($object->model_pdf) ? $object->model_pdf : (!getDolGlobalString('COMMANDE_SUPPLIER_ADDON_PDF') ? '' : $conf->global->COMMANDE_SUPPLIER_ADDON_PDF));

			print $formfile->showdocuments('commande_fournisseur', $objref, $filedir, $urlsource, $genallowed, $delallowed, $modelpdf, 1, 0, 0, 0, 0, '', '', '', $object->thirdparty->default_lang, '', $object);
			$somethingshown = $formfile->numoffiles;

			// Show links to link elements
			$linktoelem = $form->showLinkToObjectBlock($object, array(), array('supplier_order', 'order_supplier'));
			$somethingshown = $form->showLinkedObjectBlock($object, $linktoelem);

			print '</div><div class="fichehalfright">';

			if ($action == 'classifyreception') {
				if ($usercanreceive && ($object->statut == CommandeFournisseur::STATUS_ORDERSENT || $object->statut == CommandeFournisseur::STATUS_RECEIVED_PARTIALLY)) {
					// Set status to received (action=livraison)
					print '<!-- form to record purchase order received -->'."\n";
					print '<form id="classifyreception" action="card.php?id='.$object->id.'" method="post">';
					print '<input type="hidden" name="token" value="'.newToken().'">';
					print '<input type="hidden"	name="action" value="livraison">';
					print load_fiche_titre($langs->trans("Receive"), '', '');

					print '<table class="noborder centpercent">';
					//print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("Receive").'</td></tr>';
					print '<tr><td>'.$langs->trans("DeliveryDate").'</td><td>';
					$datepreselected = dol_now();
					print $form->selectDate($datepreselected, '', 1, 1, 0, "commande", 1, 1);
					print "</td></tr>\n";

					print '<tr><td class="fieldrequired">'.$langs->trans("Delivery")."</td><td>\n";
					$liv = array();
					$liv[''] = '&nbsp;';
					$liv['tot']	= $langs->trans("CompleteOrNoMoreReceptionExpected");
					$liv['par']	= $langs->trans("PartialWoman");
					$liv['nev']	= $langs->trans("NeverReceived");
					$liv['can']	= $langs->trans("Canceled");

					print $form->selectarray("type", $liv);

					print '</td></tr>';
					print '<tr><td>'.$langs->trans("Comment").'</td><td><input class="quatrevingtpercent" type="text" name="comment"></td></tr>';
					print '<tr><td class="center" colspan="2">';
					print '<input type="submit" name="receive" class="button" value="'.$langs->trans("Receive").'">';
					print ' &nbsp; &nbsp; ';
					print '<input type="submit" name="cancel" class="button button-cancel" value="'.$langs->trans("Cancel").'">';
					print '</td></tr>';
					print "</table>\n";
					print "</form>\n";
					print "<br>";
				}
			}

			// List of actions on element
			include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
			$formactions = new FormActions($db);
			$somethingshown = $formactions->showactions($object, 'order_supplier', $socid, 1, 'listaction'.($genallowed ? 'largetitle' : ''));

			print '</div></div>';
		}

		/*
		 * Action webservice
		 */
		if ($action == 'webservice' && GETPOST('mode', 'alpha') != "send" && !GETPOST('cancel', 'alpha')) {
			$mode        = GETPOST('mode', 'alpha');
			$ws_url      = $object->thirdparty->webservices_url;
			$ws_key      = $object->thirdparty->webservices_key;
			$ws_user     = GETPOST('ws_user', 'alpha');
			$ws_password = GETPOST('ws_password', 'alpha');
			$error_occurred = false;

			// NS and Authentication parameters
			$ws_ns = 'http://www.dolibarr.org/ns/';
			$ws_authentication = array(
				'dolibarrkey' => $ws_key,
				'sourceapplication' => 'DolibarrWebServiceClient',
				'login' => $ws_user,
				'password' => $ws_password,
				'entity' => ''
			);

			print load_fiche_titre($langs->trans('CreateRemoteOrder'), '');

			//Is everything filled?
			if (empty($ws_url) || empty($ws_key)) {
				setEventMessages($langs->trans("ErrorWebServicesFieldsRequired"), null, 'errors');
				$mode = "init";
				$error_occurred = true; //Don't allow to set the user/pass if thirdparty fields are not filled
			} elseif ($mode != "init" && (empty($ws_user) || empty($ws_password))) {
				setEventMessages($langs->trans("ErrorFieldsRequired"), null, 'errors');
				$mode = "init";
			}

			if ($mode == "init") {
				//Table/form header
				print '<table class="border centpercent">';
				print '<form action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="post">';
				print '<input type="hidden" name="token" value="'.newToken().'">';
				print '<input type="hidden" name="action" value="webservice">';
				print '<input type="hidden" name="mode" value="check">';

				if ($error_occurred) {
					print "<br>".$langs->trans("ErrorOccurredReviseAndRetry")."<br>";
					print '<input class="button button-cancel" type="submit" id="cancel" name="cancel" value="'.$langs->trans("Cancel").'">';
				} else {
					// Webservice url
					print '<tr><td>'.$langs->trans("WebServiceURL").'</td><td colspan="3">'.dol_print_url($ws_url).'</td></tr>';
					//Remote User
					print '<tr><td>'.$langs->trans("User").'</td><td><input class="width100" type="text" name="ws_user"></td></tr>';
					//Remote Password
					print '<tr><td>'.$langs->trans("Password").'</td><td><input class="width100" type="text" name="ws_password"></td></tr>';
					//Submit button
					print '<tr><td class="center" colspan="2">';
					print '<input type="submit" class="button" id="ws_submit" name="ws_submit" value="'.$langs->trans("CreateRemoteOrder").'">';
					print ' &nbsp; &nbsp; ';
					//Cancel button
					print '<input class="button button-cancel" type="submit" id="cancel" name="cancel" value="'.$langs->trans("Cancel").'">';
					print '</td></tr>';
				}

				//End table/form
				print '</form>';
				print '</table>';
			} elseif ($mode == "check") {
				$ws_entity = '';
				$ws_thirdparty = '';
				$error_occurred = false;

				//Create SOAP client and connect it to user
				$soapclient_user = new nusoap_client($ws_url."/webservices/server_user.php");
				$soapclient_user->soap_defencoding = 'UTF-8';
				$soapclient_user->decodeUTF8(false);

				//Get the thirdparty associated to user
				$ws_parameters = array('authentication' => $ws_authentication, 'id' => '', 'ref' => $ws_user);
				$result_user = $soapclient_user->call("getUser", $ws_parameters, $ws_ns, '');
				$user_status_code = $result_user["result"]["result_code"];

				if ($user_status_code == "OK") {
					//Fill the variables
					$ws_entity = $result_user["user"]["entity"];
					$ws_authentication['entity'] = $ws_entity;
					$ws_thirdparty = $result_user["user"]["fk_thirdparty"];
					if (empty($ws_thirdparty)) {
						setEventMessages($langs->trans("RemoteUserMissingAssociatedSoc"), null, 'errors');
						$error_occurred = true;
					} else {
						//Create SOAP client and connect it to product/service
						$soapclient_product = new nusoap_client($ws_url."/webservices/server_productorservice.php");
						$soapclient_product->soap_defencoding = 'UTF-8';
						$soapclient_product->decodeUTF8(false);

						// Iterate each line and get the reference that uses the supplier of that product/service
						$i = 0;
						foreach ($object->lines as $line) {
							$i += 1;
							$ref_supplier = $line->ref_supplier;
							$line_id = $i."º) ".$line->product_ref.": ";
							if (empty($ref_supplier)) {
								continue;
							}
							$ws_parameters = array('authentication' => $ws_authentication, 'id' => '', 'ref' => $ref_supplier);
							$result_product = $soapclient_product->call("getProductOrService", $ws_parameters, $ws_ns, '');
							if (!$result_product) {
								setEventMessages($line_id.$langs->trans("Error")." SOAP ".$soapclient_product->error_str." - ".$soapclient_product->response, null, 'errors');
								$error_occurred = true;
								break;
							}

							// Check the result code
							$status_code = $result_product["result"]["result_code"];
							if (empty($status_code)) { //No result, check error str
								setEventMessages($langs->trans("Error")." SOAP '".$soapclient_product->error_str."'", null, 'errors');
							} elseif ($status_code != "OK") { //Something went wrong
								if ($status_code == "NOT_FOUND") {
									setEventMessages($line_id.$langs->trans("SupplierMissingRef")." '".$ref_supplier."'", null, 'warnings');
								} else {
									setEventMessages($line_id.$langs->trans("ResponseNonOK")." '".$status_code."' - '".$result_product["result"]["result_label"]."'", null, 'errors');
									$error_occurred = true;
									break;
								}
							}


							// Ensure that price is equal and warn user if it's not
							$supplier_price = price($result_product["product"]["price_net"]); //Price of client tab in supplier dolibarr
							$local_price = null; //Price of supplier as stated in product suppliers tab on this dolibarr, NULL if not found

							$product_fourn = new ProductFournisseur($db);
							$product_fourn_list = $product_fourn->list_product_fournisseur_price($line->fk_product);
							if (count($product_fourn_list) > 0) {
								foreach ($product_fourn_list as $product_fourn_line) {
									//Only accept the line where the supplier is the same at this order and has the same ref
									if ($product_fourn_line->fourn_id == $object->socid && $product_fourn_line->fourn_ref == $ref_supplier) {
										$local_price = price($product_fourn_line->fourn_price);
									}
								}
							}

							if ($local_price != null && $local_price != $supplier_price) {
								setEventMessages($line_id.$langs->trans("RemotePriceMismatch")." ".$supplier_price." - ".$local_price, null, 'warnings');
							}

							// Check if is in sale
							if (empty($result_product["product"]["status_tosell"])) {
								setEventMessages($line_id.$langs->trans("ProductStatusNotOnSellShort")." '".$ref_supplier."'", null, 'warnings');
							}
						}
					}
				} elseif ($user_status_code == "PERMISSION_DENIED") {
					setEventMessages($langs->trans("RemoteUserNotPermission"), null, 'errors');
					$error_occurred = true;
				} elseif ($user_status_code == "BAD_CREDENTIALS") {
					setEventMessages($langs->trans("RemoteUserBadCredentials"), null, 'errors');
					$error_occurred = true;
				} else {
					setEventMessages($langs->trans("ResponseNonOK")." '".$user_status_code."'", null, 'errors');
					$error_occurred = true;
				}

				//Form
				print '<form action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="post">';
				print '<input type="hidden" name="token" value="'.newToken().'">';
				print '<input type="hidden" name="action" value="webservice">';
				print '<input type="hidden" name="mode" value="send">';
				print '<input type="hidden" name="ws_user" value="'.$ws_user.'">';
				print '<input type="hidden" name="ws_password" value="'.$ws_password.'">';
				print '<input type="hidden" name="ws_entity" value="'.$ws_entity.'">';
				print '<input type="hidden" name="ws_thirdparty" value="'.$ws_thirdparty.'">';
				if ($error_occurred) {
					print "<br>".$langs->trans("ErrorOccurredReviseAndRetry")."<br>";
				} else {
					print '<input type="submit" class="button" id="ws_submit" name="ws_submit" value="'.$langs->trans("Confirm").'">';
					print ' &nbsp; &nbsp; ';
				}
				print '<input class="button button-cancel" type="submit" id="cancel" name="cancel" value="'.$langs->trans("Cancel").'">';
				print '</form>';
			}
		}

		// Presend form
		$modelmail = 'order_supplier_send';
		$defaulttopic = 'SendOrderRef';
		$diroutput = $conf->fournisseur->commande->dir_output;
		$autocopy = 'MAIN_MAIL_AUTOCOPY_SUPPLIER_ORDER_TO';
		$trackid = 'sord'.$object->id;

		include DOL_DOCUMENT_ROOT.'/core/tpl/card_presend.tpl.php';
	}
}

// End of page
llxFooter();
$db->close();
