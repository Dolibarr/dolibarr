<?php
/* Copyright (C) 2002-2006  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004       Eric Seigne             <eric.seigne@ryxeo.com>
 * Copyright (C) 2004-2020  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005       Marc Barilley / Ocebo   <marc@ocebo.com>
 * Copyright (C) 2005-2015  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2006       Andre Cianfarani        <acianfa@free.fr>
 * Copyright (C) 2010-2015  Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2012-2023  Christophe Battarel     <christophe.battarel@altairis.fr>
 * Copyright (C) 2012-2013  Cédric Salvador         <csalvador@gpcsolutions.fr>
 * Copyright (C) 2012-2014  Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2013       Jean-Francois FERRY     <jfefe@aternatik.fr>
 * Copyright (C) 2013-2014  Florian Henry           <florian.henry@open-concept.pro>
 * Copyright (C) 2013       Cédric Salvador         <csalvador@gpcsolutions.fr>
 * Copyright (C) 2014-2024  Ferran Marcet           <fmarcet@2byte.es>
 * Copyright (C) 2015-2016  Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2018-2024  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2022       Gauthier VERDOL         <gauthier.verdol@atm-consulting.fr>
 * Copyright (C) 2023       Nick Fragoulis
 * Copyright (C) 2024       MDW                     <mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Alexandre Spangaro      <alexandre@inovea-conseil.com>
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
 * \file 	htdocs/compta/facture/card.php
 * \ingroup facture
 * \brief 	Page to create/see an invoice
 */

// Libraries
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture-rec.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/facture/modules_facture.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/discount.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formmargin.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/invoice.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
if (isModEnabled('order')) {
	require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
}
if (isModEnabled('project')) {
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
}
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';

if (isModEnabled('variants')) {
	require_once DOL_DOCUMENT_ROOT.'/variants/class/ProductCombination.class.php';
}
if (isModEnabled('accounting')) {
	require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingjournal.class.php';
}

// Load translation files required by the page
$langs->loadLangs(array('bills', 'companies', 'compta', 'products', 'banks', 'main', 'withdrawals'));
if (isModEnabled('incoterm')) {
	$langs->load('incoterm');
}
if (isModEnabled('margin')) {
	$langs->load('margins');
}

// General $Variables
$id = (GETPOSTINT('id') ? GETPOSTINT('id') : GETPOSTINT('facid'));    // For backward compatibility
$ref = GETPOST('ref', 'alpha');
$socid = GETPOSTINT('socid');
$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$cancel = GETPOST('cancel', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');					// if not set, a default page will be used
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');	// if not set, $backtopage will be used
$lineid = GETPOSTINT('lineid');
$userid = GETPOSTINT('userid');
$search_ref = GETPOST('sf_ref', 'alpha') ? GETPOST('sf_ref', 'alpha') : GETPOST('search_ref', 'alpha');
$search_societe = GETPOST('search_societe', 'alpha');
$search_montant_ht = GETPOST('search_montant_ht', 'alpha');
$search_montant_ttc = GETPOST('search_montant_ttc', 'alpha');
$origin = GETPOST('origin', 'alpha');
$originid = (GETPOSTINT('originid') ? GETPOSTINT('originid') : GETPOSTINT('origin_id')); // For backward compatibility
$fac_rec = GETPOSTINT('fac_rec');
$facid = GETPOSTINT('facid');
$ref_client = GETPOSTINT('ref_client');
$rank = (GETPOSTINT('rank') > 0) ? GETPOSTINT('rank') : -1;
$projectid = (GETPOSTINT('projectid') ? GETPOSTINT('projectid') : 0);
$selectedLines = GETPOST('toselect', 'array');

// PDF
$hidedetails = (GETPOSTINT('hidedetails') ? GETPOSTINT('hidedetails') : (getDolGlobalString('MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS') ? 1 : 0));
$hidedesc = (GETPOSTINT('hidedesc') ? GETPOSTINT('hidedesc') : (getDolGlobalString('MAIN_GENERATE_DOCUMENTS_HIDE_DESC') ? 1 : 0));
$hideref = (GETPOSTINT('hideref') ? GETPOSTINT('hideref') : (getDolGlobalString('MAIN_GENERATE_DOCUMENTS_HIDE_REF') ? 1 : 0));

// Number of lines for predefined product/service choices
$NBLINES = 4;

$usehm = getDolGlobalInt('MAIN_USE_HOURMIN_IN_DATE_RANGE');

$object = new Facture($db);
$extrafields = new ExtraFields($db);

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// Load object
if ($id > 0 || !empty($ref)) {
	if ($action != 'add') {
		if (!getDolGlobalString('INVOICE_USE_SITUATION')) {
			$fetch_situation = false;
		} else {
			$fetch_situation = true;
		}
		$ret = $object->fetch($id, $ref, '', 0, $fetch_situation);
		if ($ret > 0 && isset($object->fk_project)) {
			$ret = $object->fetch_project();
		}
	}
}

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('invoicecard', 'globalcard'));

// Permissions
$usercanread = $user->hasRight("facture", "lire");
$usercancreate = $user->hasRight("facture", "creer");
$usercanissuepayment = $user->hasRight("facture", "paiement");
$usercandelete = $user->hasRight("facture", "supprimer") || ($usercancreate && isset($object->status) && $object->status == $object::STATUS_DRAFT);
$usercancreatecontract = $user->hasRight("contrat", "creer");

// Advanced Permissions
$usercanvalidate = ((!getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && $usercancreate) || (getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('facture', 'invoice_advance', 'validate')));
$usercansend = (!getDolGlobalString('MAIN_USE_ADVANCED_PERMS') || (getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('facture', 'invoice_advance', 'send')));
$usercanreopen = ((!getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && $usercancreate) || (getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('facture', 'invoice_advance', 'reopen')));
if (getDolGlobalString('INVOICE_DISALLOW_REOPEN')) {
	$usercanreopen = false;
}
$usercanunvalidate = ((!getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && !empty($usercancreate)) || (getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('facture', 'invoice_advance', 'unvalidate')));
$usermustrespectpricemin = ((getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && !$user->hasRight('produit', 'ignore_price_min_advance')) || !getDolGlobalString('MAIN_USE_ADVANCED_PERMS'));

// Other permissions
$usercancreatemargin = $user->hasRight('margins', 'creer');
$usercanreadallmargin = $user->hasRight('margins', 'liretous');
$usercancreatewithdrarequest = $user->hasRight('prelevement', 'bons', 'creer');

$permissionnote = $usercancreate; // Used by the include of actions_setnotes.inc.php
$permissiondellink = $usercancreate; // Used by the include of actions_dellink.inc.php
$permissiontoedit = $usercancreate; // Used by the include of actions_lineupdonw.inc.php
$permissiontoadd = $usercancreate; // Used by the include of actions_addupdatedelete.inc.php

// retained warranty invoice available type
$retainedWarrantyInvoiceAvailableType = array();
if (getDolGlobalString('INVOICE_USE_RETAINED_WARRANTY')) {
	$retainedWarrantyInvoiceAvailableType = explode('+', getDolGlobalString('INVOICE_USE_RETAINED_WARRANTY'));
}

// Security check
if ($user->socid) {
	$socid = $user->socid;
}
$isdraft = (($object->status == Facture::STATUS_DRAFT) ? 1 : 0);

$result = restrictedArea($user, 'facture', $object->id, '', '', 'fk_soc', 'rowid', $isdraft);


/*
 * Actions
 */

$parameters = array('socid' => $socid);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	$backurlforlist = DOL_URL_ROOT.'/compta/facture/list.php';

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) {
				$backtopage = $backurlforlist;
			} else {
				$backtopage = DOL_URL_ROOT.'/compta/facture/card.php?id='.((!empty($id) && $id > 0) ? $id : '__ID__');
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

	// Action clone object
	if ($action == 'confirm_clone' && $confirm == 'yes' && $permissiontoadd) {
		if (!($socid > 0)) {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('IdThirdParty')), null, 'errors');
		} else {
			$objectutil = dol_clone($object, 1); // We use a clone to avoid to denaturate loaded object when setting some properties for clone. We use native clone to keep this->db valid.
			'@phan-var-force Facture $objectutil';

			$objectutil->date = dol_mktime(12, 0, 0, GETPOSTINT('newdatemonth'), GETPOSTINT('newdateday'), GETPOSTINT('newdateyear'));
			$objectutil->socid = $socid;
			$result = $objectutil->createFromClone($user, $id);
			if ($result > 0) {
				$warningMsgLineList = array();
				// check all product lines are to sell otherwise add a warning message for each product line is not to sell
				foreach ($objectutil->lines as $line) {
					if (!is_object($line->product)) {
						$line->fetch_product();
					}
					if (is_object($line->product) && $line->product->id > 0) {
						if (empty($line->product->status)) {
							$warningMsgLineList[$line->id] = $langs->trans('WarningLineProductNotToSell', $line->product->ref);
						}
					}
				}
				if (!empty($warningMsgLineList)) {
					setEventMessages('', $warningMsgLineList, 'warnings');
				}

				header("Location: " . $_SERVER['PHP_SELF'] . '?facid=' . $result);
				exit();
			} else {
				$langs->load("errors");
				setEventMessages($objectutil->error, $objectutil->errors, 'errors');
				$action = '';
			}
		}
	} elseif ($action == 'reopen' && $usercanreopen) {
		$result = $object->fetch($id);

		if ($object->status == Facture::STATUS_CLOSED || ($object->status == Facture::STATUS_ABANDONED && ($object->close_code != 'replaced' || $object->getIdReplacingInvoice() == 0)) || ($object->status == Facture::STATUS_VALIDATED && $object->paye == 1)) {    // ($object->status == 1 && $object->paye == 1) should not happened but can be found when data are corrupted
			$result = $object->setUnpaid($user);
			if ($result > 0) {
				header('Location: '.$_SERVER["PHP_SELF"].'?facid='.$id);
				exit();
			} else {
				setEventMessages($object->error, $object->errors, 'errors');
			}
		}
	} elseif ($action == 'confirm_delete' && $confirm == 'yes') {
		// Delete invoice
		$result = $object->fetch($id);
		$object->fetch_thirdparty();

		$idwarehouse = GETPOST('idwarehouse');

		$qualified_for_stock_change = 0;
		if (!getDolGlobalString('STOCK_SUPPORTS_SERVICES')) {
			$qualified_for_stock_change = $object->hasProductsOrServices(2);
		} else {
			$qualified_for_stock_change = $object->hasProductsOrServices(1);
		}

		$isErasable = $object->is_erasable();

		if (($usercandelete && $isErasable > 0)
			|| ($usercancreate && $isErasable == 1)) {
			$result = $object->delete($user, 0, $idwarehouse);
			if ($result > 0) {
				header('Location: '.DOL_URL_ROOT.'/compta/facture/list.php?restore_lastsearch_values=1');
				exit();
			} else {
				setEventMessages($object->error, $object->errors, 'errors');
				$action = '';
			}
		}
	} elseif ($action == 'confirm_deleteline' && $confirm == 'yes' && $usercancreate) {
		// Delete line
		$object->fetch($id);
		$object->fetch_thirdparty();

		$result = $object->deleteLine(GETPOSTINT('lineid'));
		if ($result > 0) {
			// reorder lines
			$object->line_order(true);
			// Define output language
			$outputlangs = $langs;
			$newlang = '';
			if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang) && GETPOST('lang_id')) {
				$newlang = GETPOST('lang_id');
			}
			if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang)) {
				$newlang = $object->thirdparty->default_lang;
			}
			if (!empty($newlang)) {
				$outputlangs = new Translate("", $conf);
				$outputlangs->setDefaultLang($newlang);
				$outputlangs->load('products');
			}
			if (!getDolGlobalString('MAIN_DISABLE_PDF_AUTOUPDATE')) {
				$ret = $object->fetch($id); // Reload to get new records
				$result = $object->generateDocument($object->model_pdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
			}
			if ($result >= 0) {
				header('Location: '.$_SERVER["PHP_SELF"].'?facid='.$id);
				exit();
			}
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
			$action = '';
		}
	} elseif ($action == 'unlinkdiscount' && $usercancreate) {
		// Delete link of credit note to invoice
		$discount = new DiscountAbsolute($db);
		$result = $discount->fetch(GETPOSTINT("discountid"));
		$discount->unlink_invoice();
	} elseif ($action == 'valid' && $usercancreate) {
		// Validation
		$object->fetch($id);

		if ((preg_match('/^[\(]?PROV/i', $object->ref) || empty($object->ref)) &&	// empty should not happened, but when it occurs, the test save life
			getDolGlobalString('FAC_FORCE_DATE_VALIDATION')								// If option enabled, we force invoice date
		) {
			$object->date = dol_now();
		}

		if (getDolGlobalString('INVOICE_CHECK_POSTERIOR_DATE')) {
			$last_of_type = $object->willBeLastOfSameType(true);
			if (empty($object->date_validation) && !$last_of_type[0]) {
				setEventMessages($langs->transnoentities("ErrorInvoiceIsNotLastOfSameType", $object->ref, dol_print_date($object->date, 'day'), dol_print_date($last_of_type[1], 'day')), null, 'errors');
				$action = '';
			}
		}

		// We check invoice sign
		if ($object->type == Facture::TYPE_CREDIT_NOTE) {
			// If a credit note, the sign must be negative
			if ($object->total_ht > 0) {
				setEventMessages($langs->trans("ErrorInvoiceAvoirMustBeNegative"), null, 'errors');
				$action = '';
			}
		} else {
			// If not a credit note, amount with tax must be positive or nul.
			// Note that amount excluding tax can be negative because you can have a invoice of 100 with vat of 20 that
			// consumes a credit note of 100 with vat 0 (total with tax is 0 but without tax is -20).
			// For some cases, credit notes can have a vat of 0 (for example when selling goods in France).
			if (!getDolGlobalString('FACTURE_ENABLE_NEGATIVE') && $object->total_ttc < 0) {
				setEventMessages($langs->trans("ErrorInvoiceOfThisTypeMustBePositive"), null, 'errors');
				$action = '';
			}

			// Also negative lines should not be allowed on 'non Credit notes' invoices. A test is done when adding or updating lines but we must
			// do it again in validation to avoid cases where invoice is created from another object that allow negative lines.
			// Note that we can accept the negative line if sum with other lines with same vat makes total positive: Because all the lines will be merged together
			// when converted into 'available credit' and we will get a positive available credit line.
			// Note: Other solution if you want to add a negative line on invoice, is to create a discount for customer and consumme it (but this is possible on standard invoice only).
			$array_of_total_ht_per_vat_rate = array();
			$array_of_total_ht_devise_per_vat_rate = array();
			foreach ($object->lines as $line) {
				//$vat_src_code_for_line = $line->vat_src_code;		// TODO We check sign of total per vat without taking into account the vat code because for the moment the vat code is lost/unknown when we add a down payment.
				$vat_src_code_for_line = '';
				if (empty($array_of_total_ht_per_vat_rate[$line->tva_tx.'_'.$vat_src_code_for_line])) {
					$array_of_total_ht_per_vat_rate[$line->tva_tx.'_'.$vat_src_code_for_line] = 0;
				}
				if (empty($array_of_total_ht_devise_per_vat_rate[$line->tva_tx.'_'.$vat_src_code_for_line])) {
					$array_of_total_ht_devise_per_vat_rate[$line->tva_tx.'_'.$vat_src_code_for_line] = 0;
				}
				$array_of_total_ht_per_vat_rate[$line->tva_tx.'_'.$vat_src_code_for_line] += $line->total_ht;
				$array_of_total_ht_devise_per_vat_rate[$line->tva_tx.'_'.$vat_src_code_for_line] += $line->multicurrency_total_ht;
			}

			//var_dump($array_of_total_ht_per_vat_rate);exit;
			foreach ($array_of_total_ht_per_vat_rate as $vatrate => $tmpvalue) {
				$tmp_total_ht = price2num($array_of_total_ht_per_vat_rate[$vatrate]);
				$tmp_total_ht_devise = price2num($array_of_total_ht_devise_per_vat_rate[$vatrate]);

				if (($tmp_total_ht < 0 || $tmp_total_ht_devise < 0) && !getDolGlobalString('FACTURE_ENABLE_NEGATIVE_LINES')) {
					if ($object->type == $object::TYPE_DEPOSIT) {
						$langs->load("errors");
						// Using negative lines on deposit lead to headach and blocking problems when you want to consume them.
						setEventMessages($langs->trans("ErrorLinesCantBeNegativeOnDeposits"), null, 'errors');
						$error++;
						$action = '';
					} else {
						$tmpvatratetoshow = explode('_', $vatrate);
						$tmpvatratetoshow[0] = round((float) $tmpvatratetoshow[0], 2);

						if ($tmpvatratetoshow[0] != 0) {
							$langs->load("errors");
							setEventMessages($langs->trans("ErrorLinesCantBeNegativeForOneVATRate", $tmpvatratetoshow[0]), null, 'errors');
							$error++;
							$action = '';
						}
					}
				}
			}
		}
	} elseif ($action == 'classin' && $usercancreate) {
		$object->fetch($id);
		$object->setProject(GETPOSTINT('projectid'));
	} elseif ($action == 'setmode' && $usercancreate) {
		$object->fetch($id);
		$result = $object->setPaymentMethods(GETPOSTINT('mode_reglement_id'));
		if ($result < 0) {
			dol_print_error($db, $object->error);
		}
	} elseif ($action == 'setretainedwarrantyconditions' && $usercancreate) {
		$object->fetch($id);
		$object->retained_warranty_fk_cond_reglement = 0; // To clean property
		$result = $object->setRetainedWarrantyPaymentTerms(GETPOSTINT('retained_warranty_fk_cond_reglement'));
		if ($result < 0) {
			dol_print_error($db, $object->error);
		}

		$old_rw_date_lim_reglement = $object->retained_warranty_date_limit;
		$new_rw_date_lim_reglement = $object->calculate_date_lim_reglement($object->retained_warranty_fk_cond_reglement);
		if ($new_rw_date_lim_reglement > $old_rw_date_lim_reglement) {
			$object->retained_warranty_date_limit = $new_rw_date_lim_reglement;
		}
		if ($object->retained_warranty_date_limit < $object->date) {
			$object->retained_warranty_date_limit = $object->date;
		}
		$result = $object->update($user);
		if ($result < 0) {
			dol_print_error($db, $object->error);
		}
	} elseif ($action == 'setretainedwarranty' && $usercancreate) {
		$object->fetch($id);
		$result = $object->setRetainedWarranty(GETPOSTFLOAT('retained_warranty'));
		if ($result < 0) {
			dol_print_error($db, $object->error);
		}
	} elseif ($action == 'setretainedwarrantydatelimit' && $usercancreate) {
		$object->fetch($id);
		$result = $object->setRetainedWarrantyDateLimit(GETPOSTFLOAT('retained_warranty_date_limit'));
		if ($result < 0) {
			dol_print_error($db, $object->error);
		}
	} elseif ($action == 'setmulticurrencycode' && $usercancreate) {	 // Multicurrency Code
		$result = $object->setMulticurrencyCode(GETPOST('multicurrency_code', 'alpha'));
	} elseif ($action == 'setmulticurrencyrate' && $usercancreate) {	// Multicurrency rate
		$result = $object->setMulticurrencyRate(price2num(GETPOST('multicurrency_tx')), GETPOSTINT('calculation_mode'));
	} elseif ($action == 'setinvoicedate' && $usercancreate) {
		$object->fetch($id);
		$old_date_lim_reglement = $object->date_lim_reglement;
		$newdate = dol_mktime(0, 0, 0, GETPOSTINT('invoicedatemonth'), GETPOSTINT('invoicedateday'), GETPOSTINT('invoicedateyear'), 'tzserver');
		if (empty($newdate)) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Date")), null, 'errors');
			header('Location: '.$_SERVER["PHP_SELF"].'?facid='.$id.'&action=editinvoicedate&token='.newToken());
			exit;
		}
		if ($newdate > (dol_now('tzuserrel') + getDolGlobalInt('INVOICE_MAX_FUTURE_DELAY'))) {
			if (!getDolGlobalString('INVOICE_MAX_FUTURE_DELAY')) {
				setEventMessages($langs->trans("WarningInvoiceDateInFuture"), null, 'warnings');
			} else {
				setEventMessages($langs->trans("WarningInvoiceDateTooFarInFuture"), null, 'warnings');
			}
		}

		$object->date = $newdate;
		$new_date_lim_reglement = $object->calculate_date_lim_reglement();
		if ($new_date_lim_reglement) {
			$object->date_lim_reglement = $new_date_lim_reglement;
		}
		if ($object->date_lim_reglement < $object->date) {
			$object->date_lim_reglement = $object->date;
		}
		$result = $object->update($user);
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
			$action = 'editinvoicedate';
		}
	} elseif ($action == 'setdate_pointoftax' && $usercancreate) {
		$object->fetch($id);

		$date_pointoftax = dol_mktime(0, 0, 0, GETPOSTINT('date_pointoftaxmonth'), GETPOSTINT('date_pointoftaxday'), GETPOSTINT('date_pointoftaxyear'), 'tzserver');

		$object->date_pointoftax = $date_pointoftax;
		$result = $object->update($user);
		if ($result < 0) {
			dol_print_error($db, $object->error);
		}
	} elseif ($action == 'setconditions' && $usercancreate) {
		$object->fetch($id);
		$object->cond_reglement_code = 0; // To clean property
		$object->cond_reglement_id = 0; // To clean property

		$error = 0;

		$db->begin();

		if (!$error) {
			$result = $object->setPaymentTerms(GETPOSTINT('cond_reglement_id'));
			if ($result < 0) {
				$error++;
				setEventMessages($object->error, $object->errors, 'errors');
			}
		}

		if (!$error) {
			$old_date_lim_reglement = $object->date_lim_reglement;
			$new_date_lim_reglement = $object->calculate_date_lim_reglement();
			if ($new_date_lim_reglement) {
				$object->date_lim_reglement = $new_date_lim_reglement;
			}
			if ($object->date_lim_reglement < $object->date) {
				$object->date_lim_reglement = $object->date;
			}
			$result = $object->update($user);
			if ($result < 0) {
				$error++;
				setEventMessages($object->error, $object->errors, 'errors');
			}
		}

		if ($error) {
			$db->rollback();
		} else {
			$db->commit();
		}
	} elseif ($action == 'setpaymentterm' && $usercancreate) {
		$object->fetch($id);
		$object->date_lim_reglement = dol_mktime(12, 0, 0, GETPOSTINT('paymenttermmonth'), GETPOSTINT('paymenttermday'), GETPOSTINT('paymenttermyear'));
		if ($object->date_lim_reglement < $object->date) {
			$object->date_lim_reglement = $object->calculate_date_lim_reglement();
			setEventMessages($langs->trans("DatePaymentTermCantBeLowerThanObjectDate"), null, 'warnings');
		}
		$result = $object->update($user);
		if ($result < 0) {
			dol_print_error($db, $object->error);
		}
	} elseif ($action == 'setrevenuestamp' && $usercancreate) {
		$object->fetch($id);
		$object->revenuestamp = (float) price2num(GETPOST('revenuestamp'));
		$result = $object->update($user);
		$object->update_price(1);
		if ($result < 0) {
			dol_print_error($db, $object->error);
		} else {
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
					$outputlangs->load('products');
				}
				$model = $object->model_pdf;
				$ret = $object->fetch($id); // Reload to get new records

				$result = $object->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
				if ($result < 0) {
					setEventMessages($object->error, $object->errors, 'errors');
				}
			}
		}
	} elseif ($action == 'set_incoterms' && isModEnabled('incoterm') && $usercancreate) {		// Set incoterm
		$result = $object->setIncoterms(GETPOSTINT('incoterm_id'), GETPOSTINT('location_incoterms'));
	} elseif ($action == 'setbankaccount' && $usercancreate) {	// bank account
		$result = $object->setBankAccount(GETPOSTINT('fk_account'));
	} elseif ($action == 'setremisepercent' && $usercancreate) {
		$object->fetch($id);
		$result = $object->setDiscount($user, price2num(GETPOST('remise_percent'), '', 2));
	} elseif ($action == "setabsolutediscount" && $usercancreate) {
		// We have POST[remise_id] or POST[remise_id_for_payment]
		$db->begin();

		// We use the credit to reduce amount of invoice
		if (GETPOSTINT("remise_id") > 0) {
			$ret = $object->fetch($id);
			if ($ret > 0) {
				$result = $object->insert_discount(GETPOSTINT("remise_id"));
				if ($result < 0) {
					setEventMessages($object->error, $object->errors, 'errors');
				}
			} else {
				$error++;
				setEventMessages($object->error, $object->errors, 'errors');
			}
		}
		// We use the credit to reduce remain to pay
		if (GETPOSTINT("remise_id_for_payment") > 0) {
			require_once DOL_DOCUMENT_ROOT.'/core/class/discount.class.php';
			$discount = new DiscountAbsolute($db);
			$discount->fetch(GETPOSTINT("remise_id_for_payment"));

			//var_dump($object->getRemainToPay(0));
			//var_dump($discount->amount_ttc);exit;
			$remaintopay = $object->getRemainToPay(0);
			if (price2num($discount->amount_ttc) > price2num($remaintopay)) {
				// TODO Split the discount in 2 automatically
				$error++;
				setEventMessages($langs->trans("ErrorDiscountLargerThanRemainToPaySplitItBefore"), null, 'errors');
			}

			if (!$error) {
				$result = $discount->link_to_invoice(0, $id);
				if ($result < 0) {
					$error++;
					setEventMessages($discount->error, $discount->errors, 'errors');
				}
			}

			if (!$error) {
				$newremaintopay = $object->getRemainToPay(0);
				if ($newremaintopay == 0) {
					$object->setPaid($user);
				}
			}
		}

		if (!$error) {
			$db->commit();
		} else {
			$db->rollback();
		}

		if (empty($error) && !getDolGlobalString('MAIN_DISABLE_PDF_AUTOUPDATE')) {
			$outputlangs = $langs;
			$newlang = '';
			if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
				$newlang = GETPOST('lang_id', 'aZ09');
			}
			if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang)) {
				$object->fetch_thirdparty();
				$newlang = $object->thirdparty->default_lang;
			}
			if (!empty($newlang)) {
				$outputlangs = new Translate("", $conf);
				$outputlangs->setDefaultLang($newlang);
			}
			$ret = $object->fetch($id); // Reload to get new records

			$result = $object->generateDocument($object->model_pdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
			if ($result < 0) {
				setEventMessages($object->error, $object->errors, 'errors');
			}
		}
	} elseif ($action == 'setref' && $usercancreate) {
		$object->fetch($id);
		$object->setValueFrom('ref', GETPOST('ref'), '', 0, '', '', $user, 'BILL_MODIFY');
	} elseif ($action == 'setref_client' && $usercancreate) {
		$object->fetch($id);
		$object->set_ref_client(GETPOST('ref_client'));
	} elseif ($action == 'confirm_valid' && $confirm == 'yes' && $usercanvalidate) {
		// Classify to validated
		$idwarehouse = GETPOSTINT('idwarehouse');

		$object->fetch($id);
		$object->fetch_thirdparty();

		// Check for warehouse
		if ($object->type != Facture::TYPE_DEPOSIT && getDolGlobalString('STOCK_CALCULATE_ON_BILL')) {
			$qualified_for_stock_change = 0;
			if (!getDolGlobalString('STOCK_SUPPORTS_SERVICES')) {
				$qualified_for_stock_change = $object->hasProductsOrServices(2);
			} else {
				$qualified_for_stock_change = $object->hasProductsOrServices(1);
			}

			if ($qualified_for_stock_change) {
				if (!$idwarehouse || $idwarehouse == - 1) {
					$error++;
					setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("Warehouse")), null, 'errors');
					$action = '';
				}
			}
		}

		if (!$error) {
			$result = $object->validate($user, '', $idwarehouse);
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
						$outputlangs->load('products');
					}
					$model = $object->model_pdf;

					$ret = $object->fetch($id); // Reload to get new records

					$result = $object->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
					if ($result < 0) {
						setEventMessages($object->error, $object->errors, 'errors');
					}
				}
			} else {
				if (count($object->errors)) {
					setEventMessages(null, $object->errors, 'errors');
				} else {
					setEventMessages($object->error, $object->errors, 'errors');
				}
			}
		}
	} elseif ($action == 'confirm_modif' && $usercanunvalidate) {
		// Go back to draft status (unvalidate)
		$idwarehouse = GETPOSTINT('idwarehouse');

		$object->fetch($id);
		$object->fetch_thirdparty();

		// Check parameters
		if ($object->type != Facture::TYPE_DEPOSIT && getDolGlobalString('STOCK_CALCULATE_ON_BILL')) {
			$qualified_for_stock_change = 0;
			if (!getDolGlobalString('STOCK_SUPPORTS_SERVICES')) {
				$qualified_for_stock_change = $object->hasProductsOrServices(2);
			} else {
				$qualified_for_stock_change = $object->hasProductsOrServices(1);
			}

			if ($qualified_for_stock_change) {
				if (!$idwarehouse || $idwarehouse == - 1) {
					$error++;
					setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("Warehouse")), null, 'errors');
					$action = '';
				}
			}
		}

		if (!$error) {
			// We check if invoice has payments
			$totalpaid = 0;
			$sql = 'SELECT pf.amount';
			$sql .= ' FROM '.MAIN_DB_PREFIX.'paiement_facture as pf';
			$sql .= ' WHERE pf.fk_facture = '.((int) $object->id);

			$result = $db->query($sql);
			if ($result) {
				$i = 0;
				$num = $db->num_rows($result);

				while ($i < $num) {
					$objp = $db->fetch_object($result);
					$totalpaid += $objp->amount;
					$i++;
				}
			} else {
				dol_print_error($db, '');
			}

			$resteapayer = $object->total_ttc - $totalpaid;

			// We check that invoice lines are transferred into accountancy
			$ventilExportCompta = $object->getVentilExportCompta();

			// We check if no payment has been made
			if ($ventilExportCompta == 0) {
				if (getDolGlobalString('INVOICE_CAN_BE_EDITED_EVEN_IF_PAYMENT_DONE') || ($resteapayer == $object->total_ttc && empty($object->paye))) {
					$result = $object->setDraft($user, $idwarehouse);
					if ($result < 0) {
						setEventMessages($object->error, $object->errors, 'errors');
					}

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
							$outputlangs->load('products');
						}
						$model = $object->model_pdf;
						$ret = $object->fetch($id); // Reload to get new records

						$object->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
					}
				}
			}
		}
	} elseif ($action == 'confirm_paid' && $confirm == 'yes' && $usercanissuepayment) {
		// Classify "paid"
		$object->fetch($id);
		$result = $object->setPaid($user);
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	} elseif ($action == 'confirm_paid_partially' && $confirm == 'yes' && $usercanissuepayment) {
		// Classif "paid partially"
		$object->fetch($id);
		$close_code = GETPOST("close_code", 'restricthtml');
		$close_note = GETPOST("close_note", 'restricthtml');
		if ($close_code) {
			$result = $object->setPaid($user, $close_code, $close_note);
			if ($result < 0) {
				setEventMessages($object->error, $object->errors, 'errors');
			}
		} else {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Reason")), null, 'errors');
		}
	} elseif ($action == 'confirm_canceled' && $confirm == 'yes' && $usercancreate) {
		// Classify "abandoned"
		$object->fetch($id);
		$close_code = GETPOST("close_code", 'restricthtml');
		$close_note = GETPOST("close_note", 'restricthtml');
		if ($close_code) {
			$result = $object->setCanceled($user, $close_code, $close_note);
			if ($result < 0) {
				setEventMessages($object->error, $object->errors, 'errors');
			}
		} else {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Reason")), null, 'errors');
		}
	} elseif ($action == 'confirm_converttoreduc' && $confirm == 'yes' && $usercancreate) {
		// Convert to reduce
		$object->fetch($id);
		$object->fetch_thirdparty();
		//$object->fetch_lines();	// Already done into fetch

		// Check if there is already a discount (protection to avoid duplicate creation when resubmit post)
		$discountcheck = new DiscountAbsolute($db);
		$result = $discountcheck->fetch(0, $object->id);

		$canconvert = 0;
		if ($object->type == Facture::TYPE_DEPOSIT && empty($discountcheck->id)) {
			$canconvert = 1; // we can convert deposit into discount if deposit is paid (completely, partially or not at all) and not already converted (see real condition into condition used to show button converttoreduc)
		}
		if (($object->type == Facture::TYPE_CREDIT_NOTE || $object->type == Facture::TYPE_STANDARD || $object->type == Facture::TYPE_SITUATION) && $object->paye == 0 && empty($discountcheck->id)) {
			$canconvert = 1; // we can convert credit note into discount if credit note is not paid back and not already converted and amount of payment is 0 (see real condition into condition used to show button converttoreduc)
		}

		if ($canconvert) {
			$db->begin();

			$amount_ht = $amount_tva = $amount_ttc = array();
			$multicurrency_amount_ht = $multicurrency_amount_tva = $multicurrency_amount_ttc = array();

			// Loop on each vat rate
			$i = 0;
			foreach ($object->lines as $line) {
				if ($line->product_type < 9 && $line->total_ht != 0) { // Remove lines with product_type greater than or equal to 9 and no need to create discount if amount is null
					$keyforvatrate = $line->tva_tx.($line->vat_src_code ? ' ('.$line->vat_src_code.')' : '');

					$amount_ht[$keyforvatrate] += $line->total_ht;
					$amount_tva[$keyforvatrate] += $line->total_tva;
					$amount_ttc[$keyforvatrate] += $line->total_ttc;
					$multicurrency_amount_ht[$keyforvatrate] += $line->multicurrency_total_ht;
					$multicurrency_amount_tva[$keyforvatrate] += $line->multicurrency_total_tva;
					$multicurrency_amount_ttc[$keyforvatrate] += $line->multicurrency_total_ttc;
					$i++;
				}
			}
			'@phan-var-force array<string,float> $amount_ht
			 @phan-var-force array<string,float> $amount_tva
			 @phan-var-force array<string,float> $amount_ttc
			 @phan-var-force array<string,float> $multicurrency_amount_ht
			 @phan-var-force array<string,float> $multicurrency_amount_tva
			 @phan-var-force array<string,float> $multicurrency_amount_ttc';

			// If some payments were already done, we change the amount to pay using same prorate
			if (getDolGlobalString('INVOICE_ALLOW_REUSE_OF_CREDIT_WHEN_PARTIALLY_REFUNDED') && $object->type == Facture::TYPE_CREDIT_NOTE) {
				$alreadypaid = $object->getSommePaiement(); // This can be not 0 if we allow to create credit to reuse from credit notes partially refunded.
				if ($alreadypaid && abs($alreadypaid) < abs($object->total_ttc)) {
					$ratio = abs(($object->total_ttc - $alreadypaid) / $object->total_ttc);
					foreach ($amount_ht as $vatrate => $val) {
						$amount_ht[$vatrate] = price2num($amount_ht[$vatrate] * $ratio, 'MU');
						$amount_tva[$vatrate] = price2num($amount_tva[$vatrate] * $ratio, 'MU');
						$amount_ttc[$vatrate] = price2num($amount_ttc[$vatrate] * $ratio, 'MU');
						$multicurrency_amount_ht[$vatrate] = price2num($multicurrency_amount_ht[$vatrate] * $ratio, 'MU');
						$multicurrency_amount_tva[$vatrate] = price2num($multicurrency_amount_tva[$vatrate] * $ratio, 'MU');
						$multicurrency_amount_ttc[$vatrate] = price2num($multicurrency_amount_ttc[$vatrate] * $ratio, 'MU');
					}
				}
			}
			//var_dump($amount_ht);var_dump($amount_tva);var_dump($amount_ttc);exit;

			// Insert one discount by VAT rate category
			$discount = new DiscountAbsolute($db);
			if ($object->type == Facture::TYPE_CREDIT_NOTE) {
				$discount->description = '(CREDIT_NOTE)';
			} elseif ($object->type == Facture::TYPE_DEPOSIT) {
				$discount->description = '(DEPOSIT)';
			} elseif ($object->type == Facture::TYPE_STANDARD || $object->type == Facture::TYPE_REPLACEMENT || $object->type == Facture::TYPE_SITUATION) {
				$discount->description = '(EXCESS RECEIVED)';
			} else {
				setEventMessages($langs->trans('CantConvertToReducAnInvoiceOfThisType'), null, 'errors');
			}
			$discount->fk_soc = $object->socid;
			$discount->socid = $object->socid;
			$discount->fk_facture_source = $object->id;

			$error = 0;

			if ($object->type == Facture::TYPE_STANDARD || $object->type == Facture::TYPE_REPLACEMENT || $object->type == Facture::TYPE_SITUATION) {
				// If we're on a standard invoice, we have to get excess received to create a discount in TTC without VAT

				// Total payments
				$sql = 'SELECT SUM(pf.amount) as total_paiements';
				$sql .= ' FROM '.MAIN_DB_PREFIX.'paiement_facture as pf, '.MAIN_DB_PREFIX.'paiement as p';
				$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_paiement as c ON p.fk_paiement = c.id';
				$sql .= ' WHERE pf.fk_facture = '.((int) $object->id);
				$sql .= ' AND pf.fk_paiement = p.rowid';
				$sql .= ' AND p.entity IN ('.getEntity('invoice').')';
				$resql = $db->query($sql);
				if (!$resql) {
					dol_print_error($db);
				}

				$res = $db->fetch_object($resql);
				$total_paiements = $res->total_paiements;

				// Total credit note and deposit
				$total_creditnote_and_deposit = 0;
				$sql = "SELECT re.rowid, re.amount_ht, re.amount_tva, re.amount_ttc,";
				$sql .= " re.description, re.fk_facture_source";
				$sql .= " FROM ".MAIN_DB_PREFIX."societe_remise_except as re";
				$sql .= " WHERE fk_facture = ".((int) $object->id);
				$resql = $db->query($sql);
				if (!empty($resql)) {
					while ($obj = $db->fetch_object($resql)) {
						$total_creditnote_and_deposit += $obj->amount_ttc;
					}
				} else {
					dol_print_error($db);
				}

				$discount->amount_ht = $discount->amount_ttc = $total_paiements + $total_creditnote_and_deposit - $object->total_ttc;
				$discount->amount_tva = 0;
				$discount->tva_tx = 0;
				$discount->vat_src_code = '';

				$result = $discount->create($user);
				if ($result < 0) {
					$error++;
				}
			}
			if ($object->type == Facture::TYPE_CREDIT_NOTE || $object->type == Facture::TYPE_DEPOSIT) {
				foreach ($amount_ht as $tva_tx => $xxx) {
					$discount->amount_ht = abs((float) $amount_ht[$tva_tx]);
					$discount->amount_tva = abs((float) $amount_tva[$tva_tx]);
					$discount->amount_ttc = abs((float) $amount_ttc[$tva_tx]);
					$discount->multicurrency_amount_ht = abs((float) $multicurrency_amount_ht[$tva_tx]);
					$discount->multicurrency_amount_tva = abs((float) $multicurrency_amount_tva[$tva_tx]);
					$discount->multicurrency_amount_ttc = abs((float) $multicurrency_amount_ttc[$tva_tx]);

					// Clean vat code
					$reg = array();
					$vat_src_code = '';
					if (preg_match('/\((.*)\)/', $tva_tx, $reg)) {
						$vat_src_code = $reg[1];
						$tva_tx = preg_replace('/\s*\(.*\)/', '', $tva_tx); // Remove code into vatrate.
					}

					$discount->tva_tx = abs((float) $tva_tx);
					$discount->vat_src_code = $vat_src_code;

					$result = $discount->create($user);
					if ($result < 0) {
						$error++;
						break;
					}
				}
			}

			if (empty($error)) {
				if ($object->type != Facture::TYPE_DEPOSIT) {
					// Set invoice as paid
					$result = $object->setPaid($user);
					if ($result >= 0) {
						$db->commit();
					} else {
						setEventMessages($object->error, $object->errors, 'errors');
						$db->rollback();
					}
				} else {
					$db->commit();
				}
			} else {
				setEventMessages($discount->error, $discount->errors, 'errors');
				$db->rollback();
			}
		}
	} elseif ($action == 'confirm_delete_paiement' && $confirm == 'yes' && $usercanissuepayment) {
		// Delete payment
		$object->fetch($id);
		if ($object->status == Facture::STATUS_VALIDATED && $object->paye == 0) {
			$paiement = new Paiement($db);
			$result = $paiement->fetch(GETPOSTINT('paiement_id'));
			if ($result > 0) {
				$result = $paiement->delete($user); // If fetch ok and found
				if ($result >= 0) {
					header("Location: ".$_SERVER['PHP_SELF']."?id=".$id);
					exit;
				}
			}
			if ($result < 0) {
				setEventMessages($paiement->error, $paiement->errors, 'errors');
			}
		}
	} elseif ($action == 'add' && $usercancreate) {
		// Insert new invoice in database
		if ($socid > 0) {
			$object->socid = GETPOSTINT('socid');
		}

		if (GETPOST('type') === '') {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Type")), null, 'errors');
		}

		$db->begin();

		$error = 0;
		$originentity = GETPOSTINT('originentity');
		// Fill array 'array_options' with data from add form
		$ret = $extrafields->setOptionalsFromPost(null, $object);
		if ($ret < 0) {
			$error++;
		}

		$dateinvoice = dol_mktime(0, 0, 0, GETPOSTINT('remonth'), GETPOSTINT('reday'), GETPOSTINT('reyear'), 'tzserver');	// If we enter the 02 january, we need to save the 02 january for server
		$date_pointoftax = dol_mktime(0, 0, 0, GETPOSTINT('date_pointoftaxmonth'), GETPOSTINT('date_pointoftaxday'), GETPOSTINT('date_pointoftaxyear'), 'tzserver');

		// Replacement invoice
		if (GETPOST('type') == Facture::TYPE_REPLACEMENT) {
			if (empty($dateinvoice)) {
				$error++;
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Date")), null, 'errors');
				$action = 'create';
			} elseif ($dateinvoice > (dol_get_last_hour(dol_now('tzuserrel')) + getDolGlobalInt('INVOICE_MAX_FUTURE_DELAY'))) {
				$error++;
				setEventMessages($langs->trans("ErrorDateIsInFuture"), null, 'errors');
				$action = 'create';
			}

			if (!(GETPOSTINT('fac_replacement') > 0)) {
				$error++;
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("ReplaceInvoice")), null, 'errors');
				$action = 'create';
			}

			if (!$error) {
				// This is a replacement invoice
				$result = $object->fetch(GETPOSTINT('fac_replacement'));
				$object->fetch_thirdparty();

				$object->date               = $dateinvoice;
				$object->date_pointoftax    = $date_pointoftax;
				$object->note_public		= trim(GETPOST('note_public', 'restricthtml'));
				$object->note_private		= trim(GETPOST('note_private', 'restricthtml'));
				$object->ref_client			= GETPOST('ref_client', 'alphanohtml');
				$object->ref_customer		= GETPOST('ref_client', 'alphanohtml');
				$object->model_pdf          = GETPOST('model', 'alphanohtml');
				$object->fk_project			= GETPOSTINT('projectid');
				$object->cond_reglement_id	= GETPOSTINT('cond_reglement_id');
				$object->mode_reglement_id	= GETPOSTINT('mode_reglement_id');
				$object->fk_account         = GETPOSTINT('fk_account');
				//$object->remise_absolue		= price2num(GETPOST('remise_absolue'), 'MU', 2);
				//$object->remise_percent		= price2num(GETPOST('remise_percent'), '', 2);
				$object->fk_incoterms       = GETPOSTINT('incoterm_id');
				$object->location_incoterms = GETPOST('location_incoterms', 'alpha');
				$object->multicurrency_code = GETPOST('multicurrency_code', 'alpha');
				$object->multicurrency_tx   = GETPOSTINT('originmulticurrency_tx');

				// Special properties of replacement invoice
				$object->fk_facture_source = GETPOSTINT('fac_replacement');
				$object->type              = Facture::TYPE_REPLACEMENT;

				$id = $object->createFromCurrent($user);
				if ($id <= 0) {
					setEventMessages($object->error, $object->errors, 'errors');
				}
			}
		}

		// Credit note invoice
		if (GETPOST('type') == Facture::TYPE_CREDIT_NOTE) {
			$sourceinvoice = GETPOSTINT('fac_avoir');
			if (!($sourceinvoice > 0) && !getDolGlobalString('INVOICE_CREDIT_NOTE_STANDALONE')) {
				$error++;
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("CorrectInvoice")), null, 'errors');
				$action = 'create';
			}

			if (empty($dateinvoice)) {
				$error++;
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Date")), null, 'errors');
				$action = 'create';
			} elseif ($dateinvoice > (dol_get_last_hour(dol_now('tzuserrel')) + getDolGlobalInt('INVOICE_MAX_FUTURE_DELAY'))) {
				$error++;
				setEventMessages($langs->trans("ErrorDateIsInFuture"), null, 'errors');
				$action = 'create';
			}

			if (getDolGlobalInt('INVOICE_SUBTYPE_ENABLED') && empty(GETPOST("subtype"))) {
				$error++;
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("InvoiceSubtype")), null, 'errors');
				$action = 'create';
			}

			if (!$error) {
				if (!empty($originentity)) {
					$object->entity = $originentity;
				}
				$object->socid              = GETPOSTINT('socid');
				$object->subtype            = GETPOSTINT('subtype');
				$object->ref                = GETPOST('ref');
				$object->date               = $dateinvoice;
				$object->date_pointoftax    = $date_pointoftax;
				$object->note_public		= trim(GETPOST('note_public', 'restricthtml'));
				$object->note_private		= trim(GETPOST('note_private', 'restricthtml'));
				$object->ref_client			= GETPOST('ref_client', 'alphanohtml');
				$object->ref_customer		= GETPOST('ref_client', 'alphanohtml');
				$object->model_pdf          = GETPOST('model');
				$object->fk_project			= GETPOSTINT('projectid');
				$object->cond_reglement_id	= 0;		// No payment term for a credit note
				$object->mode_reglement_id	= GETPOSTINT('mode_reglement_id');
				$object->fk_account         = GETPOSTINT('fk_account');
				//$object->remise_absolue		= price2num(GETPOST('remise_absolue'), 'MU');
				//$object->remise_percent		= price2num(GETPOST('remise_percent'), '', 2);
				$object->fk_incoterms       = GETPOSTINT('incoterm_id');
				$object->location_incoterms = GETPOST('location_incoterms', 'alpha');
				$object->multicurrency_code = GETPOST('multicurrency_code', 'alpha');
				$object->multicurrency_tx   = GETPOSTINT('originmulticurrency_tx');

				// Special properties of replacement invoice
				$object->fk_facture_source = $sourceinvoice > 0 ? $sourceinvoice : '';
				$object->type = Facture::TYPE_CREDIT_NOTE;

				$facture_source = new Facture($db); // fetch origin object
				if ($facture_source->fetch($object->fk_facture_source) > 0) {
					if ($facture_source->type == Facture::TYPE_SITUATION) {
						$object->situation_counter = $facture_source->situation_counter;
						$object->situation_cycle_ref = $facture_source->situation_cycle_ref;
						$facture_source->fetchPreviousNextSituationInvoice();
					}
				}


				$id = $object->create($user);
				if ($id < 0) {
					$error++;
				} else {
					// copy internal contacts
					if ($object->copy_linked_contact($facture_source, 'internal') < 0) {
						$error++;
					} elseif ($facture_source->socid == $object->socid) {
						// copy external contacts if same company
						if ($object->copy_linked_contact($facture_source, 'external') < 0) {
							$error++;
						}
					}
				}

				// NOTE: Pb with situation invoice
				// NOTE: fields total on situation invoice are stored as cumulative values on total of lines (bad) but delta on invoice total
				// NOTE: fields total on credit note are stored as delta both on total of lines and on invoice total (good)
				// NOTE: fields situation_percent on situation invoice are stored as cumulative values on lines (bad)
				// NOTE: fields situation_percent on credit note are stored as delta on lines (good)
				if (GETPOSTINT('invoiceAvoirWithLines') == 1 && $id > 0) {
					if (!empty($facture_source->lines)) {
						$fk_parent_line = 0;

						foreach ($facture_source->lines as $line) {
							// Extrafields
							if (method_exists($line, 'fetch_optionals')) {
								// load extrafields
								$line->fetch_optionals();
							}

							// Reset fk_parent_line for no child products and special product
							if (($line->product_type != 9 && empty($line->fk_parent_line)) || $line->product_type == 9) {
								$fk_parent_line = 0;
							}


							if ($facture_source->type == Facture::TYPE_SITUATION) {
								$source_fk_prev_id = $line->fk_prev_id; // temporary storing situation invoice fk_prev_id
								$line->fk_prev_id  = $line->id; // The new line of the new credit note we are creating must be linked to the situation invoice line it is created from

								if (!empty($facture_source->tab_previous_situation_invoice)) {
									// search the last standard invoice in cycle and the possible credit note between this last and facture_source
									// TODO Move this out of loop of $facture_source->lines
									$tab_jumped_credit_notes = array();
									$lineIndex = count($facture_source->tab_previous_situation_invoice) - 1;
									$searchPreviousInvoice = true;
									while ($searchPreviousInvoice) {
										if ($facture_source->tab_previous_situation_invoice[$lineIndex]->type == Facture::TYPE_SITUATION || $lineIndex < 1) {
											$searchPreviousInvoice = false; // find, exit;
											break;
										} else {
											if ($facture_source->tab_previous_situation_invoice[$lineIndex]->type == Facture::TYPE_CREDIT_NOTE) {
												$tab_jumped_credit_notes[$lineIndex] = $facture_source->tab_previous_situation_invoice[$lineIndex]->id;
											}
											$lineIndex--; // go to previous invoice in cycle
										}
									}

									$maxPrevSituationPercent = 0;
									foreach ($facture_source->tab_previous_situation_invoice[$lineIndex]->lines as $prevLine) {
										if ($prevLine->id == $source_fk_prev_id) {
											$maxPrevSituationPercent = max($maxPrevSituationPercent, $prevLine->situation_percent);

											//$line->subprice  = $line->subprice - $prevLine->subprice;
											$line->total_ht  -= $prevLine->total_ht;
											$line->total_tva -= $prevLine->total_tva;
											$line->total_ttc -= $prevLine->total_ttc;
											$line->total_localtax1 -= $prevLine->total_localtax1;
											$line->total_localtax2 -= $prevLine->total_localtax2;

											$line->multicurrency_subprice  -= $prevLine->multicurrency_subprice;
											$line->multicurrency_total_ht  -= $prevLine->multicurrency_total_ht;
											$line->multicurrency_total_tva -= $prevLine->multicurrency_total_tva;
											$line->multicurrency_total_ttc -= $prevLine->multicurrency_total_ttc;
										}
									}

									// prorata
									$line->situation_percent = $maxPrevSituationPercent - $line->situation_percent;

									//print 'New line based on invoice id '.$facture_source->tab_previous_situation_invoice[$lineIndex]->id.' fk_prev_id='.$source_fk_prev_id.' will be fk_prev_id='.$line->fk_prev_id.' '.$line->total_ht.' '.$line->situation_percent.'<br>';

									// If there is some credit note between last situation invoice and invoice used for credit note generation (note: credit notes are stored as delta)
									$maxPrevSituationPercent = 0;
									foreach ($tab_jumped_credit_notes as $index => $creditnoteid) {
										foreach ($facture_source->tab_previous_situation_invoice[$index]->lines as $prevLine) {
											if ($prevLine->fk_prev_id == $source_fk_prev_id) {
												$maxPrevSituationPercent = $prevLine->situation_percent;

												$line->total_ht  -= $prevLine->total_ht;
												$line->total_tva -= $prevLine->total_tva;
												$line->total_ttc -= $prevLine->total_ttc;
												$line->total_localtax1 -= $prevLine->total_localtax1;
												$line->total_localtax2 -= $prevLine->total_localtax2;

												$line->multicurrency_subprice  -= $prevLine->multicurrency_subprice;
												$line->multicurrency_total_ht  -= $prevLine->multicurrency_total_ht;
												$line->multicurrency_total_tva -= $prevLine->multicurrency_total_tva;
												$line->multicurrency_total_ttc -= $prevLine->multicurrency_total_ttc;
											}
										}
									}

									// prorata
									$line->situation_percent += $maxPrevSituationPercent;

									//print 'New line based on invoice id '.$facture_source->tab_previous_situation_invoice[$lineIndex]->id.' fk_prev_id='.$source_fk_prev_id.' will be fk_prev_id='.$line->fk_prev_id.' '.$line->total_ht.' '.$line->situation_percent.'<br>';
								}
							}

							$line->fk_facture = $object->id;
							$line->fk_parent_line = $fk_parent_line;

							$line->subprice = -$line->subprice; // invert price for object
							// $line->pa_ht = $line->pa_ht; // we chose to have buy/cost price always positive, so no revert of sign here
							$line->total_ht = -$line->total_ht;
							$line->total_tva = -$line->total_tva;
							$line->total_ttc = -$line->total_ttc;
							$line->total_localtax1 = -$line->total_localtax1;
							$line->total_localtax2 = -$line->total_localtax2;

							$line->multicurrency_subprice = -$line->multicurrency_subprice;
							$line->multicurrency_total_ht = -$line->multicurrency_total_ht;
							$line->multicurrency_total_tva = -$line->multicurrency_total_tva;
							$line->multicurrency_total_ttc = -$line->multicurrency_total_ttc;

							$line->context['createcreditnotefrominvoice'] = 1;
							$result = $line->insert(0, 1); // When creating credit note with same lines than source, we must ignore error if discount already linked

							$object->lines[] = $line; // insert new line in current object

							// Defined the new fk_parent_line
							if ($result > 0 && $line->product_type == 9) {
								$fk_parent_line = $result;
							}
						}

						$object->update_price(1);
					}
				}

				if (GETPOSTINT('invoiceAvoirWithPaymentRestAmount') == 1 && $id > 0) {
					if ($facture_source->fetch($object->fk_facture_source) > 0) {
						$totalpaid = $facture_source->getSommePaiement();
						$totalcreditnotes = $facture_source->getSumCreditNotesUsed();
						$totaldeposits = $facture_source->getSumDepositsUsed();
						$remain_to_pay = abs($facture_source->total_ttc - $totalpaid - $totalcreditnotes - $totaldeposits);

						if (getDolGlobalString('INVOICE_VAT_TO_USE_ON_CREDIT_NOTE_WHEN_GENERATED_FROM_REMAIN_TO_PAY') == 'default') {
							if ((empty($object->thirdparty) || !is_object($object->thirdparty) || get_class($object->thirdparty) != 'Societe')) {
								$object->fetch_thirdparty();
							}
							if (!empty($object->thirdparty) && is_object($object->thirdparty) && get_class($object->thirdparty) == 'Societe') {
								$tva_tx = get_default_tva($mysoc, $object->thirdparty);
							} else {
								$tva_tx = 0;
							}
						} elseif ((float) getDolGlobalString('INVOICE_VAT_TO_USE_ON_CREDIT_NOTE_WHEN_GENERATED_FROM_REMAIN_TO_PAY') > 0) {
							$tva_tx = (float) getDolGlobalString('INVOICE_VAT_TO_USE_ON_CREDIT_NOTE_WHEN_GENERATED_FROM_REMAIN_TO_PAY');
						} else {
							$tva_tx = 0;
						}

						$object->addline($langs->trans('invoiceAvoirLineWithPaymentRestAmount'), $remain_to_pay, 1, $tva_tx, 0, 0, 0, 0, '', '', 0, 0, 0, 'TTC');
					}
				}

				// Add link between credit note and origin
				if (!empty($object->fk_facture_source) && $id > 0) {
					$facture_source->fetch($object->fk_facture_source);
					$facture_source->fetchObjectLinked();

					if (!empty($facture_source->linkedObjectsIds)) {
						foreach ($facture_source->linkedObjectsIds as $sourcetype => $TIds) {
							$object->add_object_linked($sourcetype, current($TIds));
						}
					}
				}
			}
		}

		// Standard invoice or Deposit invoice, created from a Predefined template invoice
		if ((GETPOST('type') == Facture::TYPE_STANDARD || GETPOST('type') == Facture::TYPE_DEPOSIT) && GETPOSTINT('fac_rec') > 0) {
			if (empty($dateinvoice)) {
				$error++;
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Date")), null, 'errors');
				$action = 'create';
			} elseif ($dateinvoice > (dol_get_last_hour(dol_now('tzuserrel')) + getDolGlobalInt('INVOICE_MAX_FUTURE_DELAY'))) {
				$error++;
				setEventMessages($langs->trans("ErrorDateIsInFuture"), null, 'errors');
				$action = 'create';
			}


			if (getDolGlobalInt('INVOICE_SUBTYPE_ENABLED') && empty(GETPOST("subtype"))) {
				$error++;
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("InvoiceSubtype")), null, 'errors');
				$action = 'create';
			}

			if (!$error) {
				$object->socid              = GETPOSTINT('socid');
				$object->type               = GETPOSTINT('type');
				$object->subtype            = GETPOSTINT('subtype');
				$object->ref                = GETPOST('ref');
				$object->date               = $dateinvoice;
				$object->date_pointoftax    = $date_pointoftax;
				$object->note_public        = trim(GETPOST('note_public', 'restricthtml'));
				$object->note_private       = trim(GETPOST('note_private', 'restricthtml'));
				$object->ref_customer       = GETPOST('ref_client');
				$object->ref_client         = $object->ref_customer;
				$object->model_pdf          = GETPOST('model');
				$object->fk_project         = GETPOSTINT('projectid');
				$object->cond_reglement_id	= (GETPOSTINT('type') == 3 ? 1 : GETPOST('cond_reglement_id'));
				$object->mode_reglement_id	= GETPOSTINT('mode_reglement_id');
				$object->fk_account         = GETPOSTINT('fk_account');
				$object->amount             = price2num(GETPOST('amount'));
				//$object->remise_absolue		= price2num(GETPOST('remise_absolue'), 'MU');
				//$object->remise_percent		= price2num(GETPOST('remise_percent'), '', 2);
				$object->fk_incoterms       = GETPOSTINT('incoterm_id');
				$object->location_incoterms = GETPOST('location_incoterms', 'alpha');
				$object->multicurrency_code = GETPOST('multicurrency_code', 'alpha');
				$object->multicurrency_tx   = GETPOSTINT('originmulticurrency_tx');

				// Source facture
				$object->fac_rec = GETPOSTINT('fac_rec');

				$id = $object->create($user); // This include recopy of links from recurring invoice and recurring invoice lines
			}
		}

		// Standard or deposit invoice, not from a Predefined template invoice
		if ((GETPOST('type') == Facture::TYPE_STANDARD || GETPOST('type') == Facture::TYPE_DEPOSIT || GETPOST('type') == Facture::TYPE_PROFORMA || (GETPOST('type') == Facture::TYPE_SITUATION && !GETPOST('situations'))) && GETPOST('fac_rec') <= 0) {
			$typeamount = GETPOST('typedeposit', 'aZ09');
			$valuestandardinvoice = price2num(str_replace('%', '', GETPOST('valuestandardinvoice', 'alpha')), 'MU');
			$valuedeposit = price2num(str_replace('%', '', GETPOST('valuedeposit', 'alpha')), 'MU');

			if (GETPOSTINT('socid') < 1) {
				$error++;
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Customer")), null, 'errors');
				$action = 'create';
			}

			if (empty($dateinvoice)) {
				$error++;
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Date")), null, 'errors');
				$action = 'create';
			} elseif ($dateinvoice > (dol_get_last_hour(dol_now('tzuserrel')) + getDolGlobalInt('INVOICE_MAX_FUTURE_DELAY'))) {
				$error++;
				setEventMessages($langs->trans("ErrorDateIsInFuture"), null, 'errors');
				$action = 'create';
			}


			if (GETPOST('type') == Facture::TYPE_STANDARD) {
				if ($valuestandardinvoice < 0 || $valuestandardinvoice > 100) {
					setEventMessages($langs->trans("ErrorAPercentIsRequired"), null, 'errors');
					$error++;
					$action = 'create';
				}
			} elseif (GETPOST('type') == Facture::TYPE_DEPOSIT) {
				if ($typeamount && !empty($origin) && !empty($originid)) {
					if ($typeamount == 'amount' && $valuedeposit <= 0) {
						setEventMessages($langs->trans("ErrorAnAmountWithoutTaxIsRequired"), null, 'errors');
						$error++;
						$action = 'create';
					}
					if ($typeamount == 'variable' && $valuedeposit <= 0) {
						setEventMessages($langs->trans("ErrorAPercentIsRequired"), null, 'errors');
						$error++;
						$action = 'create';
					}
					if ($typeamount == 'variablealllines' && $valuedeposit <= 0) {
						setEventMessages($langs->trans("ErrorAPercentIsRequired"), null, 'errors');
						$error++;
						$action = 'create';
					}
				}
			}


			if (getDolGlobalInt('INVOICE_SUBTYPE_ENABLED') && empty(GETPOST("subtype"))) {
				$error++;
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("InvoiceSubtype")), null, 'errors');
				$action = 'create';
			}

			if (!$error) {
				$object->socid              = GETPOSTINT('socid');
				$object->type				= GETPOSTINT('type');
				$object->subtype            = GETPOSTINT('subtype');
				$object->ref                = GETPOST('ref');
				$object->date				= $dateinvoice;
				$object->date_pointoftax    = $date_pointoftax;
				$object->note_public		= trim(GETPOST('note_public', 'restricthtml'));
				$object->note_private       = trim(GETPOST('note_private', 'restricthtml'));
				$object->ref_client			= GETPOST('ref_client');
				$object->ref_customer		= GETPOST('ref_client');
				$object->model_pdf          = GETPOST('model');
				$object->fk_project			= GETPOSTINT('projectid');
				$object->cond_reglement_id	= (GETPOSTINT('type') == 3 ? 1 : GETPOST('cond_reglement_id'));
				$object->mode_reglement_id	= GETPOST('mode_reglement_id');
				$object->fk_account         = GETPOSTINT('fk_account');
				$object->amount             = price2num(GETPOST('amount'));
				//$object->remise_absolue		= price2num(GETPOST('remise_absolue'), 'MU');
				//$object->remise_percent		= price2num(GETPOST('remise_percent'), '', 2);
				$object->fk_incoterms       = GETPOSTINT('incoterm_id');
				$object->location_incoterms = GETPOST('location_incoterms', 'alpha');
				$object->multicurrency_code = GETPOST('multicurrency_code', 'alpha');
				$object->multicurrency_tx   = GETPOSTINT('originmulticurrency_tx');

				if (GETPOST('type') == Facture::TYPE_SITUATION) {
					$object->situation_counter = 1;
					$object->situation_final = 0;
					$object->situation_cycle_ref = $object->newCycle();
				}

				if (in_array($object->type, $retainedWarrantyInvoiceAvailableType)) {
					$object->retained_warranty = GETPOSTINT('retained_warranty');
					$object->retained_warranty_fk_cond_reglement = GETPOSTINT('retained_warranty_fk_cond_reglement');
				} else {
					$object->retained_warranty = 0;
					$object->retained_warranty_fk_cond_reglement = 0;
				}

				$retained_warranty_date_limit = GETPOST('retained_warranty_date_limit');
				if (!empty($retained_warranty_date_limit) && dol_stringtotime($retained_warranty_date_limit)) {
					$object->retained_warranty_date_limit = dol_stringtotime($retained_warranty_date_limit);
				}
				$object->retained_warranty_date_limit = !empty($object->retained_warranty_date_limit) ? $object->retained_warranty_date_limit : $object->calculate_date_lim_reglement($object->retained_warranty_fk_cond_reglement);

				$object->fetch_thirdparty();

				// If creation from another object of another module (Example: origin=propal, originid=1)
				if (!empty($origin) && !empty($originid)) {
					$regs = array();
					// Parse element/subelement (ex: project_task)
					$element = $subelement = $origin;
					if (preg_match('/^([^_]+)_([^_]+)/i', $origin, $regs)) {
						$element = $regs[1];
						$subelement = $regs[2];
					}

					// For compatibility
					if ($element == 'order') {
						$element = $subelement = 'commande';
					}
					if ($element == 'propal') {
						$element = 'comm/propal';
						$subelement = 'propal';
					}
					if ($element == 'contract') {
						$element = $subelement = 'contrat';
					}
					if ($element == 'inter') {
						$element = $subelement = 'ficheinter';
					}
					if ($element == 'shipping') {
						$element = $subelement = 'expedition';
					}

					$object->origin = $origin;		// deprecated
					$object->origin_type = $origin;
					$object->origin_id = $originid;

					// Possibility to add external linked objects with hooks
					$object->linked_objects[$object->origin_type] = $object->origin_id;
					// link with order if it is a shipping invoice
					if ($object->origin == 'shipping') {
						require_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';
						$exp = new Expedition($db);
						$exp->fetch($object->origin_id);
						$exp->fetchObjectLinked();
						if (is_array($exp->linkedObjectsIds['commande']) && count($exp->linkedObjectsIds['commande']) > 0) {
							foreach ($exp->linkedObjectsIds['commande'] as $key => $value) {
								$object->linked_objects['commande'] = $value;
							}
						}
					}

					if (GETPOSTISARRAY('other_linked_objects')) {
						$object->linked_objects = array_merge($object->linked_objects, GETPOST('other_linked_objects', 'array:int'));
					}

					$id = $object->create($user); // This include class to add_object_linked() and add add_contact()

					if ($id > 0) {
						dol_include_once('/'.$element.'/class/'.$subelement.'.class.php');

						$classname = ucfirst($subelement);
						$srcobject = new $classname($db);
						'@phan-var-force CommonObject $srcobject';

						dol_syslog("Try to find source object origin=".$object->origin." originid=".$object->origin_id." to add lines or deposit lines");
						$result = $srcobject->fetch($object->origin_id);

						// If deposit invoice - down payment with 1 line (fixed amount or percent)
						if (GETPOST('type') == Facture::TYPE_DEPOSIT && in_array($typeamount, array('amount', 'variable'))) {
							// Define the array $amountdeposit
							$amountdeposit = array();
							if (getDolGlobalString('MAIN_DEPOSIT_MULTI_TVA')) {	// We want to split the discount line into several lines, one per vat rate.
								if ($typeamount == 'amount') {
									$amount = (float) $valuedeposit;
								} else {
									$amount = $srcobject->total_ttc * ((float) $valuedeposit / 100);
								}

								$TTotalByTva = array();
								foreach ($srcobject->lines as &$line) {
									if (empty($line->qty)) {
										continue; // We discard qty=0, it is an option
									}
									if (!empty($line->special_code)) {
										continue;
									}
									$TTotalByTva[$line->tva_tx] += $line->total_ttc;
								}
								'@phan-var-force array<string,float> $TTotalByTva';

								$amount_ttc_diff = 0.;
								foreach ($TTotalByTva as $tva => &$total) {
									$coef = $total / $srcobject->total_ttc; // Calc coef
									$am = $amount * $coef;
									$amount_ttc_diff += $am;
									$amountdeposit[$tva] += $am / (1 + (float) $tva / 100); // Convert into HT for the addline
								}
							} else {
								if ($typeamount == 'amount') {
									$amountdeposit[0] = $valuedeposit;
								} elseif ($typeamount == 'variable') {
									if ($result > 0) {
										$totalamount = 0;
										$lines = $srcobject->lines;
										$numlines = count($lines);
										for ($i = 0; $i < $numlines; $i++) {
											$qualified = 1;
											if (empty($lines[$i]->qty)) {
												$qualified = 0; // We discard qty=0, it is an option
											}
											if (!empty($lines[$i]->special_code)) {
												$qualified = 0; // We discard special_code (frais port, ecotaxe, option, ...)
											}
											if ($qualified) {
												$totalamount += $lines[$i]->total_ht; // Fixme : is it not for the customer ? Shouldn't we take total_ttc ?
												$tva_tx = $lines[$i]->tva_tx;
												$amountdeposit[$tva_tx] += ($lines[$i]->total_ht * (float) $valuedeposit) / 100;
											}
										}

										if ($totalamount == 0) {
											$amountdeposit[0] = 0;
										}
									} else {
										setEventMessages($srcobject->error, $srcobject->errors, 'errors');
										$error++;
									}
								}

								$amount_ttc_diff = $amountdeposit[0];
							}

							foreach ($amountdeposit as $tva => $amount) {
								if (empty($amount)) {
									continue;
								}

								$arraylist = array(
									'amount' => 'FixAmount',
									'variable' => 'VarAmount'
								);
								$descline = '(DEPOSIT)';
								//$descline.= ' - '.$langs->trans($arraylist[$typeamount]);
								if ($typeamount == 'amount') {
									$descline .= ' ('.price($valuedeposit, 0, $langs, 0, - 1, - 1, (!empty($object->multicurrency_code) ? $object->multicurrency_code : $conf->currency)).')';
								} elseif ($typeamount == 'variable') {
									$descline .= ' ('.$valuedeposit.'%)';
								}

								$descline .= ' - '.$srcobject->ref;
								$result = $object->addline(
									$descline,
									$amount, // subprice
									1, // quantity
									$tva, // vat rate
									0, // localtax1_tx
									0, // localtax2_tx
									getDolGlobalInt('INVOICE_PRODUCTID_DEPOSIT'), // fk_product
									0, // remise_percent
									0, // date_start
									0, // date_end
									0,
									$lines[$i]->info_bits, // info_bits
									0,
									'HT',
									0,
									0, // product_type
									1,
									$lines[$i]->special_code,
									$object->origin,
									0,
									0,
									0,
									0,
									'',
									array(), // array_options
									100,
									0,
									null,
									0,
									'',
									(!empty($conf->global->MAIN_DEPOSIT_MULTI_TVA) ? 0 : 1)
								);
							}

							$diff = $object->total_ttc - $amount_ttc_diff;

							if (getDolGlobalString('MAIN_DEPOSIT_MULTI_TVA') && $diff != 0) {
								$object->fetch_lines();
								$subprice_diff = $object->lines[0]->subprice - $diff / (1 + $object->lines[0]->tva_tx / 100);
								$object->updateline($object->lines[0]->id, $object->lines[0]->desc, $subprice_diff, $object->lines[0]->qty, $object->lines[0]->remise_percent, $object->lines[0]->date_start, $object->lines[0]->date_end, $object->lines[0]->tva_tx, 0, 0, 'HT', $object->lines[0]->info_bits, $object->lines[0]->product_type, 0, 0, 0, $object->lines[0]->pa_ht, $object->lines[0]->label, 0, array(), 100);
							}
						}

						// standard invoice, credit note, or down payment from a percent of all lines
						if (GETPOST('type') != Facture::TYPE_DEPOSIT || (GETPOST('type') == Facture::TYPE_DEPOSIT && $typeamount == 'variablealllines')) {
							if ($result > 0) {
								$lines = $srcobject->lines;
								if (empty($lines) && method_exists($srcobject, 'fetch_lines')) {
									$srcobject->fetch_lines();
									$lines = $srcobject->lines;
								}

								// If we create a standard invoice with a percent, we change amount by changing the qty
								if (GETPOST('type') == Facture::TYPE_STANDARD && $valuestandardinvoice > 0 && $valuestandardinvoice < 100) {
									if (is_array($lines)) {
										foreach ($lines as $line) {
											// We keep ->subprice and ->pa_ht, but we change the qty
											$line->qty = price2num((float) $line->qty * (float) $valuestandardinvoice / 100, 'MS');
										}
									}
								}
								// If we create a down payment with a percent on all lines, we change amount by changing the qty
								if (GETPOST('type') == Facture::TYPE_DEPOSIT && $typeamount == 'variablealllines') {
									if (is_array($lines)) {
										foreach ($lines as $line) {
											// We keep ->subprice and ->pa_ht, but we change the qty
											$line->qty = price2num((float) $line->qty * (float) $valuedeposit / 100, 'MS');
										}
									}
								}

								$fk_parent_line = 0;
								$num = count($lines);

								for ($i = 0; $i < $num; $i++) {
									if (!in_array($lines[$i]->id, $selectedLines)) {
										continue; // Skip unselected lines
									}

									// Don't add lines with qty 0 when coming from a shipment including all order lines
									if ($srcobject->element == 'shipping' && getDolGlobalString('SHIPMENT_GETS_ALL_ORDER_PRODUCTS') && $lines[$i]->qty == 0) {
										continue;
									}
									// Don't add closed lines when coming from a contract (Set constant to '0,5' to exclude also inactive lines)
									if (!isset($conf->global->CONTRACT_EXCLUDE_SERVICES_STATUS_FOR_INVOICE)) {
										$conf->global->CONTRACT_EXCLUDE_SERVICES_STATUS_FOR_INVOICE = '5';
									}
									if ($srcobject->element == 'contrat' && in_array($lines[$i]->statut, explode(',', getDolGlobalString('CONTRACT_EXCLUDE_SERVICES_STATUS_FOR_INVOICE')))) {
										continue;
									}

									$label = (!empty($lines[$i]->label) ? $lines[$i]->label : '');
									$desc = (!empty($lines[$i]->desc) ? $lines[$i]->desc : '');

									if ($object->situation_counter == 1) {
										$lines[$i]->situation_percent = 0;
									}

									if ($lines[$i]->subprice < 0 && !getDolGlobalString('INVOICE_KEEP_DISCOUNT_LINES_AS_IN_ORIGIN')) {
										// Negative line, we create a discount line
										if (empty($desc)) {
											$desc = $label ? $label : $langs->trans('Discount');
										}

										$discount = new DiscountAbsolute($db);
										$discount->fk_soc = $object->socid;
										$discount->socid = $object->socid;
										$discount->amount_ht = abs($lines[$i]->total_ht);
										$discount->amount_tva = abs($lines[$i]->total_tva);
										$discount->amount_ttc = abs($lines[$i]->total_ttc);
										$discount->tva_tx = $lines[$i]->tva_tx;
										$discount->fk_user = $user->id;
										$discount->description = $desc;
										$discount->multicurrency_subprice = abs($lines[$i]->multicurrency_subprice);
										$discount->multicurrency_amount_ht = abs($lines[$i]->multicurrency_total_ht);
										$discount->multicurrency_amount_tva = abs($lines[$i]->multicurrency_total_tva);
										$discount->multicurrency_amount_ttc = abs($lines[$i]->multicurrency_total_ttc);

										$discountid = $discount->create($user);
										if ($discountid > 0) {
											$result = $object->insert_discount($discountid); // This include link_to_invoice
										} else {
											setEventMessages($discount->error, $discount->errors, 'errors');
											$error++;
											break;
										}
									} else {
										// Positive line
										// we keep first type from product if exist, otherwise we keep type from line (free line) and at last default Product
										$product_type = $lines[$i]->product_type ?? ($lines[$i]->type ?? Product::TYPE_PRODUCT);

										// Date start
										$date_start = false;
										if (isset($lines[$i]->date_debut_prevue)) {
											$date_start = $lines[$i]->date_debut_prevue;
										}
										if (isset($lines[$i]->date_debut_reel)) {
											$date_start = $lines[$i]->date_debut_reel;
										}
										if (isset($lines[$i]->date_start)) {
											$date_start = $lines[$i]->date_start;
										}

										// Date end
										$date_end = false;
										if (isset($lines[$i]->date_fin_prevue)) {
											$date_end = $lines[$i]->date_fin_prevue;
										}
										if (isset($lines[$i]->date_fin_reel)) {
											$date_end = $lines[$i]->date_fin_reel;
										}
										if (isset($lines[$i]->date_end)) {
											$date_end = $lines[$i]->date_end;
										}

										// Reset fk_parent_line for no child products and special product
										if (($lines[$i]->product_type != 9 && empty($lines[$i]->fk_parent_line)) || $lines[$i]->product_type == 9) {
											$fk_parent_line = 0;
										}

										// Extrafields
										if (method_exists($lines[$i], 'fetch_optionals')) {
											$lines[$i]->fetch_optionals();
											$array_options = $lines[$i]->array_options;
										}

										$tva_tx = $lines[$i]->tva_tx;
										if (!empty($lines[$i]->vat_src_code) && !preg_match('/\(/', $tva_tx)) {
											$tva_tx .= ' ('.$lines[$i]->vat_src_code.')';
										}

										// View third's localtaxes for NOW and do not use value from origin.
										// TODO Is this really what we want ? Yes if source is template invoice but what if proposal or order ?
										$localtax1_tx = get_localtax($tva_tx, 1, $object->thirdparty);
										$localtax2_tx = get_localtax($tva_tx, 2, $object->thirdparty);

										$result = $object->addline(
											$desc,
											$lines[$i]->subprice,
											$lines[$i]->qty,
											$tva_tx,
											$localtax1_tx,
											$localtax2_tx,
											$lines[$i]->fk_product,
											$lines[$i]->remise_percent,
											$date_start,
											$date_end,
											0,
											$lines[$i]->info_bits,
											isset($lines[$i]->fk_remise_except) ? $lines[$i]->fk_remise_except : null,
											'HT',
											0,
											$product_type,
											$lines[$i]->rang,
											$lines[$i]->special_code,
											$object->origin,
											$lines[$i]->rowid,
											$fk_parent_line,
											isset($lines[$i]->fk_fournprice) ? $lines[$i]->fk_fournprice : null,
											$lines[$i]->pa_ht,
											$label,
											$array_options,
											$lines[$i]->situation_percent ?? 100,
											$lines[$i]->fk_prev_id ?? 0,
											$lines[$i]->fk_unit,
											0,
											'',
											1
										);

										if ($result > 0) {
											$lineid = $result;
										} else {
											$lineid = 0;
											$error++;
											break;
										}

										// Defined the new fk_parent_line
										if ($result > 0 && $lines[$i]->product_type == 9) {
											$fk_parent_line = $result;
										}
									}
								}
							} else {
								setEventMessages($srcobject->error, $srcobject->errors, 'errors');
								$error++;
							}
						}

						$object->update_price(1, 'auto', 0, $mysoc);

						// Now we create same links to contact than the ones found on origin object
						/* Useless, already into the create
						if (getDolGlobalString('MAIN_PROPAGATE_CONTACTS_FROM_ORIGIN')) {
							$originforcontact = $object->origin;
							$originidforcontact = $object->origin_id;
							if ($originforcontact == 'shipping')     // shipment and order share the same contacts. If creating from shipment we take data of order
							{
								$originforcontact=$srcobject->origin;
								$originidforcontact=$srcobject->origin_id;
							}
							$sqlcontact = "SELECT code, fk_socpeople FROM ".MAIN_DB_PREFIX."element_contact as ec, ".MAIN_DB_PREFIX."c_type_contact as ctc";
							$sqlcontact.= " WHERE element_id = ".((int) $originidforcontact)." AND ec.fk_c_type_contact = ctc.rowid AND ctc.element = '".$db->escape($originforcontact)."'";

							$resqlcontact = $db->query($sqlcontact);
							if ($resqlcontact)
							{
								while($objcontact = $db->fetch_object($resqlcontact))
								{
									//print $objcontact->code.'-'.$objcontact->fk_socpeople."\n";
									$object->add_contact($objcontact->fk_socpeople, $objcontact->code);
								}
							}
							else dol_print_error($resqlcontact);
						}*/

						// Hooks
						$parameters = array('origin_type' => $object->origin_type, 'origin_id' => $object->origin_id, 'objFrom' => $srcobject);
						$reshook = $hookmanager->executeHooks('createFrom', $parameters, $object, $action); // Note that $action and $object may have been
						// modified by hook
						if ($reshook < 0) {
							setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
							$error++;
						}
					} else {
						setEventMessages($object->error, $object->errors, 'errors');
						$error++;
					}
				} else {   // If some invoice's lines coming from page
					$id = $object->create($user);

					for ($i = 1; $i <= $NBLINES; $i++) {
						if (GETPOSTINT('idprod'.$i)) {
							$product = new Product($db);
							$product->fetch(GETPOSTINT('idprod'.$i));
							$startday = dol_mktime(12, 0, 0, GETPOST('date_start'.$i.'month'), GETPOST('date_start'.$i.'day'), GETPOST('date_start'.$i.'year'));
							$endday = dol_mktime(12, 0, 0, GETPOST('date_end'.$i.'month'), GETPOST('date_end'.$i.'day'), GETPOST('date_end'.$i.'year'));
							$result = $object->addline($product->description, $product->price, price2num(GETPOST('qty'.$i), 'MS'), $product->tva_tx, $product->localtax1_tx, $product->localtax2_tx, GETPOSTINT('idprod'.$i), price2num(GETPOST('remise_percent'.$i), '', 2), $startday, $endday, 0, 0, 0, $product->price_base_type, $product->price_ttc, $product->type, -1, 0, '', 0, 0, 0, 0, '', array(), 100, 0, $product->fk_unit, 0, '', 1);
						}
					}

					$object->update_price(1, 'auto', 0, $mysoc);
				}
			}
		}

		// Situation invoices
		if (GETPOST('type') == Facture::TYPE_SITUATION && GETPOST('situations')) {
			if (empty($dateinvoice)) {
				$error++;
				$mesg = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Date"));
				setEventMessages($mesg, null, 'errors');
			} elseif ($dateinvoice > (dol_get_last_hour(dol_now('tzuserrel')) + getDolGlobalInt('INVOICE_MAX_FUTURE_DELAY'))) {
				$error++;
				setEventMessages($langs->trans("ErrorDateIsInFuture"), null, 'errors');
				$action = 'create';
			}

			if (!(GETPOSTINT('situations') > 0)) {
				$error++;
				$mesg = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("InvoiceSituation"));
				setEventMessages($mesg, null, 'errors');
				$action = 'create';
			}

			if (!$error) {
				$result = $object->fetch(GETPOSTINT('situations'));
				$object->fk_facture_source = GETPOSTINT('situations');
				$object->type = Facture::TYPE_SITUATION;

				if (!empty($origin) && !empty($originid)) {
					include_once DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php';

					$object->origin = $origin;		// deprecated
					$object->origin_type = $origin;
					$object->origin_id = $originid;

					// retained warranty
					if (getDolGlobalString('INVOICE_USE_RETAINED_WARRANTY')) {
						$retained_warranty = GETPOSTINT('retained_warranty');
						if (price2num($retained_warranty) > 0) {
							$object->retained_warranty = (float) price2num($retained_warranty);
						}

						if (GETPOSTINT('retained_warranty_fk_cond_reglement') > 0) {
							$object->retained_warranty_fk_cond_reglement = GETPOSTINT('retained_warranty_fk_cond_reglement');
						}

						$retained_warranty_date_limit = GETPOST('retained_warranty_date_limit');
						if (!empty($retained_warranty_date_limit) && $db->jdate($retained_warranty_date_limit)) {
							$object->retained_warranty_date_limit = $db->jdate($retained_warranty_date_limit);
						}
						$object->retained_warranty_date_limit = !empty($object->retained_warranty_date_limit) ? $object->retained_warranty_date_limit : $object->calculate_date_lim_reglement($object->retained_warranty_fk_cond_reglement);
					}

					foreach ($object->lines as $i => &$line) {
						$line->fk_prev_id = $line->id;
						$line->fetch_optionals();
						if (getDolGlobalInt('INVOICE_USE_SITUATION') == 2) {
							$line->situation_percent = $line->get_allprev_progress($object->id);; // get good progress including credit note
						} else {
							$line->situation_percent = $line->get_prev_progress($object->id); // get good progress including credit note
						}

						// The $line->situation_percent has been modified, so we must recalculate all amounts
						$tabprice = calcul_price_total($line->qty, $line->subprice, $line->remise_percent, $line->tva_tx, $line->localtax1_tx, $line->localtax2_tx, 0, 'HT', 0, $line->product_type, $mysoc, array(), $line->situation_percent);
						$line->total_ht = $tabprice[0];
						$line->total_tva = $tabprice[1];
						$line->total_ttc = $tabprice[2];
						$line->total_localtax1 = $tabprice[9];
						$line->total_localtax2 = $tabprice[10];
						$line->multicurrency_total_ht  = $tabprice[16];
						$line->multicurrency_total_tva = $tabprice[17];
						$line->multicurrency_total_ttc = $tabprice[18];

						// If fk_remise_except defined we check if the reduction has already been applied
						if ($line->fk_remise_except) {
							$discount = new DiscountAbsolute($line->db);
							$result = $discount->fetch($line->fk_remise_except);
							if ($result > 0) {
								// Check if discount not already affected to another invoice
								if ($discount->fk_facture_line > 0) {
									$line->fk_remise_except = 0;
								}
							}
						}
					}
				}

				$object->fetch_thirdparty();
				$object->date = $dateinvoice;
				$object->date_pointoftax = $date_pointoftax;
				$object->note_public = trim(GETPOST('note_public', 'restricthtml'));
				$object->note = trim(GETPOST('note', 'restricthtml'));
				$object->note_private = trim(GETPOST('note', 'restricthtml'));
				$object->ref_client = GETPOST('ref_client', 'alpha');
				$object->ref_customer = GETPOST('ref_client', 'alpha');
				$object->model_pdf = GETPOST('model', 'alpha');
				$object->fk_project = GETPOSTINT('projectid');
				$object->cond_reglement_id = GETPOSTINT('cond_reglement_id');
				$object->mode_reglement_id = GETPOSTINT('mode_reglement_id');
				//$object->remise_absolue =price2num(GETPOST('remise_absolue'), 'MU', 2);
				//$object->remise_percent = price2num(GETPOST('remise_percent'), '', 2);
				$object->fk_account = GETPOSTINT('fk_account');


				// Special properties of replacement invoice

				$object->situation_counter += 1;

				$id = $object->createFromCurrent($user);
				if ($id <= 0) {
					$mesg = $object->error;
				} else {
					$nextSituationInvoice = new Facture($db);
					$nextSituationInvoice->fetch($id);

					// create extrafields with data from create form
					$extrafields->fetch_name_optionals_label($nextSituationInvoice->table_element);
					$ret = $extrafields->setOptionalsFromPost(null, $nextSituationInvoice);
					if ($ret > 0) {
						$nextSituationInvoice->insertExtraFields();
					}

					// Hooks
					$parameters = array('origin_type' => $object->origin_type, 'origin_id' => $object->origin_id);
					$reshook = $hookmanager->executeHooks('createFrom', $parameters, $nextSituationInvoice, $action); // Note that $action and $object may have been
					// modified by hook
					if ($reshook < 0) {
						setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
						$error++;
					}
				}
			}
		}

		// End of object creation, we show it
		if ($id > 0 && !$error) {
			$db->commit();

			// Define output language
			if (!getDolGlobalString('MAIN_DISABLE_PDF_AUTOUPDATE') && count($object->lines)) {
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
					$outputlangs->load('products');
				}
				$model = $object->model_pdf;
				$ret = $object->fetch($id); // Reload to get new records

				$result = $object->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
				if ($result < 0) {
					setEventMessages($object->error, $object->errors, 'errors');
				}
			}

			header('Location: '.$_SERVER["PHP_SELF"].'?facid='.$id);
			exit();
		} else {
			$db->rollback();
			$action = 'create';
			$_GET["origin"] = $_POST["origin"];		// Keep GET and POST here ?
			$_GET["originid"] = $_POST["originid"]; // Keep GET and POST here ?
			setEventMessages($object->error, $object->errors, 'errors');
		}
	} elseif ($action == 'addline' && GETPOST('submitforalllines', 'aZ09') && (GETPOST('alldate_start', 'alpha') || GETPOST('alldate_end', 'alpha')) && $usercancreate) {
		// Define date start and date end for all line
		$alldate_start = dol_mktime(GETPOST('alldate_starthour'), GETPOST('alldate_startmin'), 0, GETPOST('alldate_startmonth'), GETPOST('alldate_startday'), GETPOST('alldate_startyear'));
		$alldate_end = dol_mktime(GETPOST('alldate_endhour'), GETPOST('alldate_endmin'), 0, GETPOST('alldate_endmonth'), GETPOST('alldate_endday'), GETPOST('alldate_endyear'));
		foreach ($object->lines as $line) {
			if ($line->product_type == 1) { // only service line
				$result = $object->updateline($line->id, $line->desc, $line->subprice, $line->qty, $line->remise_percent, $alldate_start, $alldate_end, $line->tva_tx, $line->localtax1_tx, $line->localtax2_tx, 'HT', $line->info_bits, $line->product_type, $line->fk_parent_line, 0, $line->fk_fournprice, $line->pa_ht, $line->label, $line->special_code, $line->array_options, $line->situation_percent, $line->fk_unit, $line->multicurrency_subprice);
			}
		}
	} elseif ($action == 'addline' && GETPOST('submitforalllines', 'alpha') && GETPOST('vatforalllines', 'alpha') !== '' && $usercancreate) {
		// Define vat_rate
		$vat_rate = (GETPOST('vatforalllines') ? GETPOST('vatforalllines') : 0);
		$vat_rate = str_replace('*', '', $vat_rate);
		$localtax1_rate = get_localtax($vat_rate, 1, $object->thirdparty, $mysoc);
		$localtax2_rate = get_localtax($vat_rate, 2, $object->thirdparty, $mysoc);
		foreach ($object->lines as $line) {
			$result = $object->updateline($line->id, $line->desc, $line->subprice, $line->qty, $line->remise_percent, $line->date_start, $line->date_end, $vat_rate, $localtax1_rate, $localtax2_rate, 'HT', $line->info_bits, $line->product_type, $line->fk_parent_line, 0, $line->fk_fournprice, $line->pa_ht, $line->label, $line->special_code, $line->array_options, $line->situation_percent, $line->fk_unit, $line->multicurrency_subprice);
		}
	} elseif ($action == 'addline' && GETPOST('submitforalllines', 'alpha') && GETPOST('remiseforalllines', 'alpha') !== '' && $usercancreate) {
		// Define vat_rate
		$remise_percent = (GETPOST('remiseforalllines') ? GETPOST('remiseforalllines') : 0);
		$remise_percent = str_replace('*', '', $remise_percent);
		foreach ($object->lines as $line) {
			$tvatx= $line->tva_tx;
			if (!empty($line->vat_src_code)) {
				$tvatx .= ' ('.$line->vat_src_code.')';
			}
			$result = $object->updateline($line->id, $line->desc, $line->subprice, $line->qty, $remise_percent, $line->date_start, $line->date_end, $tvatx, $line->localtax1_tx, $line->localtax2_tx, 'HT', $line->info_bits, $line->product_type, $line->fk_parent_line, 0, $line->fk_fournprice, $line->pa_ht, $line->label, $line->special_code, $line->array_options, $line->situation_percent, $line->fk_unit, $line->multicurrency_subprice);
		}
	} elseif ($action == 'addline' && !GETPOST('submitforalllines', 'alpha') && !GETPOST('submitforallmargins', 'alpha') && $usercancreate) {		// Add a new line
		$langs->load('errors');
		$error = 0;

		// Set if we used free entry or predefined product
		$predef = '';
		$product_desc = (GETPOSTISSET('dp_desc') ? GETPOST('dp_desc', 'restricthtml') : '');

		$price_ht = '';
		$price_ht_devise = '';
		$price_ttc = '';
		$price_ttc_devise = '';
		$price_min = '';
		$price_min_ttc = '';

		if (GETPOST('price_ht') !== '') {
			$price_ht = price2num(GETPOST('price_ht'), 'MU', 2);
		}
		if (GETPOST('multicurrency_price_ht') !== '') {
			$price_ht_devise = price2num(GETPOST('multicurrency_price_ht'), 'CU', 2);
		}
		if (GETPOST('price_ttc') !== '') {
			$price_ttc = price2num(GETPOST('price_ttc'), 'MU', 2);
		}
		if (GETPOST('multicurrency_price_ttc') !== '') {
			$price_ttc_devise = price2num(GETPOST('multicurrency_price_ttc'), 'CU', 2);
		}

		$prod_entry_mode = GETPOST('prod_entry_mode', 'aZ09');
		if ($prod_entry_mode == 'free') {
			$idprod = 0;
		} else {
			$idprod = GETPOSTINT('idprod');

			if (getDolGlobalString('MAIN_DISABLE_FREE_LINES') && $idprod <= 0) {
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("ProductOrService")), null, 'errors');
				$error++;
			}
		}

		$tva_tx = GETPOST('tva_tx', 'alpha');

		$qty = price2num(GETPOST('qty'.$predef, 'alpha'), 'MS', 2);
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
				unset($_POST["options_".$key.$predef]);
			}
		}

		if ((empty($idprod) || $idprod < 0) && ($price_ht < 0) && ($qty < 0)) {
			setEventMessages($langs->trans('ErrorBothFieldCantBeNegative', $langs->transnoentitiesnoconv('UnitPriceHT'), $langs->transnoentitiesnoconv('Qty')), null, 'errors');
			$error++;
		}
		if (!$prod_entry_mode) {
			if (GETPOST('type') < 0 && !GETPOST('search_idprod')) {
				setEventMessages($langs->trans('ErrorChooseBetweenFreeEntryOrPredefinedProduct'), null, 'errors');
				$error++;
			}
		}
		if ($prod_entry_mode == 'free' && (empty($idprod) || $idprod < 0) && GETPOST('type') < 0) {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Type')), null, 'errors');
			$error++;
		}
		if ($prod_entry_mode == 'free' && (empty($idprod) || $idprod < 0) && (($price_ht < 0 && !getDolGlobalString('FACTURE_ENABLE_NEGATIVE_LINES')) || $price_ht == '') && (($price_ht_devise < 0 && !getDolGlobalString('FACTURE_ENABLE_NEGATIVE_LINES')) || $price_ht_devise == '') && $price_ttc === '' && $price_ttc_devise === '' && $object->type != Facture::TYPE_CREDIT_NOTE) { 	// Unit price can be 0 but not ''
			if (($price_ht < 0 || $price_ttc < 0) && !getDolGlobalString('FACTURE_ENABLE_NEGATIVE_LINES')) {
				$langs->load("errors");
				if ($object->type == $object::TYPE_DEPOSIT) {
					// Using negative lines on deposit lead to headach and blocking problems when you want to consume them.
					setEventMessages($langs->trans("ErrorLinesCantBeNegativeOnDeposits"), null, 'errors');
				} else {
					setEventMessages($langs->trans("ErrorFieldCantBeNegativeOnInvoice", $langs->transnoentitiesnoconv("UnitPriceHT"), $langs->transnoentitiesnoconv("CustomerAbsoluteDiscountShort")), null, 'errors');
				}
				$error++;
			} else {
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("UnitPriceHT")), null, 'errors');
				$error++;
			}
		}
		if ($qty == '') {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Qty')), null, 'errors');
			$error++;
		}
		if ($prod_entry_mode == 'free' && empty($idprod) && empty($product_desc)) {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Description')), null, 'errors');
			$error++;
		}
		if ($qty < 0) {
			$langs->load("errors");
			setEventMessages($langs->trans('ErrorQtyForCustomerInvoiceCantBeNegative'), null, 'errors');
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

		if (!$error && ($qty >= 0) && (!empty($product_desc) || (!empty($idprod) && $idprod > 0))) {
			$ret = $object->fetch($id);
			if ($ret < 0) {
				dol_print_error($db, $object->error);
				exit();
			}
			$ret = $object->fetch_thirdparty();

			// Clean parameters
			$date_start = dol_mktime(GETPOST('date_start'.$predef.'hour'), GETPOST('date_start'.$predef.'min'), GETPOST('date_start'.$predef.'sec'), GETPOST('date_start'.$predef.'month'), GETPOST('date_start'.$predef.'day'), GETPOST('date_start'.$predef.'year'));
			$date_end = dol_mktime(GETPOST('date_end'.$predef.'hour'), GETPOST('date_end'.$predef.'min'), GETPOST('date_end'.$predef.'sec'), GETPOST('date_end'.$predef.'month'), GETPOST('date_end'.$predef.'day'), GETPOST('date_end'.$predef.'year'));
			$price_base_type = (GETPOST('price_base_type', 'alpha') ? GETPOST('price_base_type', 'alpha') : 'HT');
			$tva_npr = "";

			// Define special_code for special lines
			$special_code = 0;
			// if (!GETPOST(qty)) $special_code=3; // Options should not exists on invoices

			// Replaces $pu with that of the product
			// Replaces $desc with that of the product
			// Replaces $base_price_type with that of the product
			// Replaces $fk_unit with that of the product
			if (!empty($idprod) && $idprod > 0) {
				$prod = new Product($db);
				$prod->fetch($idprod);

				$label = ((GETPOST('product_label') && GETPOST('product_label') != $prod->label) ? GETPOST('product_label') : '');

				// Search the correct price into loaded array product_price_by_qty using id of array retrieved into POST['pqp'].
				$pqp = (GETPOSTINT('pbq') ? GETPOSTINT('pbq') : 0);

				$datapriceofproduct = $prod->getSellPrice($mysoc, $object->thirdparty, $pqp);

				$pu_ht = $datapriceofproduct['pu_ht'];
				$pu_ttc = $datapriceofproduct['pu_ttc'];
				$price_min = $datapriceofproduct['price_min'];
				$price_min_ttc = (isset($datapriceofproduct['price_min_ttc'])) ? $datapriceofproduct['price_min_ttc'] : null;
				$price_base_type = empty($datapriceofproduct['price_base_type']) ? 'HT' : $datapriceofproduct['price_base_type'];

				//$tva_tx = $datapriceofproduct['tva_tx'];
				//$tva_npr = $datapriceofproduct['tva_npr'];
				$tmpvat = (float) price2num(preg_replace('/\s*\(.*\)/', '', $tva_tx));
				$tmpprodvat = price2num(preg_replace('/\s*\(.*\)/', '', (string) $prod->tva_tx));

				// Set unit price to use
				// TODO We should not have this
				if (!empty($price_ht) || $price_ht === '0') {
					$pu_ht = price2num($price_ht, 'MU');
					$pu_ttc = price2num((float) $pu_ht * (1 + ($tmpvat / 100)), 'MU');
				} elseif (!empty($price_ht_devise) || $price_ht_devise === '0') {
					$pu_ht_devise = price2num($price_ht_devise, 'MU');
					$pu_ht = '';
					$pu_ttc = '';
				} elseif (!empty($price_ttc) || $price_ttc === '0') {
					$pu_ttc = price2num($price_ttc, 'MU');
					$pu_ht = price2num((float) $pu_ttc / (1 + ($tmpvat / 100)), 'MU');
				} elseif ($tmpvat != $tmpprodvat) {
					// Is this still used ?
					if ($price_base_type != 'HT') {
						$pu_ht = price2num($pu_ttc / (1 + ($tmpvat / 100)), 'MU');
					} else {
						$pu_ttc = price2num($pu_ht * (1 + ($tmpvat / 100)), 'MU');
					}
				}

				$desc = '';

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
						$outputlangs->load('products');
					}

					$desc = (!empty($prod->multilangs [$outputlangs->defaultlang] ["description"])) ? $prod->multilangs [$outputlangs->defaultlang] ["description"] : $prod->description;
				} else {
					$desc = $prod->description;
				}

				//If text set in desc is the same as product descpription (as now it's preloaded) we add it only one time
				if ($product_desc == $desc && getDolGlobalString('PRODUIT_AUTOFILL_DESC')) {
					$product_desc = '';
				}

				if (!empty($product_desc) && getDolGlobalString('MAIN_NO_CONCAT_DESCRIPTION')) {
					$desc = $product_desc;
				} else {
					$desc = dol_concatdesc($desc, $product_desc, false, getDolGlobalString('MAIN_CHANGE_ORDER_CONCAT_DESCRIPTION') ? true : false);
				}

				// Add custom code and origin country into description
				if (!getDolGlobalString('MAIN_PRODUCT_DISABLE_CUSTOMCOUNTRYCODE') && (!empty($prod->customcode) || !empty($prod->country_code))) {
					$tmptxt = '(';
					// Define output language
					if (getDolGlobalInt('MAIN_MULTILANGS') && getDolGlobalString('PRODUIT_TEXTS_IN_THIRDPARTY_LANGUAGE')) {
						$outputlangs = $langs;
						$newlang = '';
						if (empty($newlang) && GETPOST('lang_id', 'alpha')) {
							$newlang = GETPOST('lang_id', 'alpha');
						}
						if (empty($newlang)) {
							$newlang = $object->thirdparty->default_lang;
						}
						if (!empty($newlang)) {
							$outputlangs = new Translate("", $conf);
							$outputlangs->setDefaultLang($newlang);
							$outputlangs->load('products');
						}
						if (!empty($prod->customcode)) {
							$tmptxt .= $outputlangs->transnoentitiesnoconv("CustomCode").': '.$prod->customcode;
						}
						if (!empty($prod->customcode) && !empty($prod->country_code)) {
							$tmptxt .= ' - ';
						}
						if (!empty($prod->country_code)) {
							$tmptxt .= $outputlangs->transnoentitiesnoconv("CountryOrigin").': '.getCountry($prod->country_code, '', $db, $outputlangs, 0);
						}
					} else {
						if (!empty($prod->customcode)) {
							$tmptxt .= $langs->transnoentitiesnoconv("CustomCode").': '.$prod->customcode;
						}
						if (!empty($prod->customcode) && !empty($prod->country_code)) {
							$tmptxt .= ' - ';
						}
						if (!empty($prod->country_code)) {
							$tmptxt .= $langs->transnoentitiesnoconv("CountryOrigin").': '.getCountry($prod->country_code, '', $db, $langs, 0);
						}
					}
					$tmptxt .= ')';
					$desc = dol_concatdesc($desc, $tmptxt);
				}

				$type = $prod->type;
				$fk_unit = $prod->fk_unit;
			} else {
				if (!empty($price_ht)) {
					$pu_ht = price2num($price_ht, 'MU');
				} else {
					$pu_ht = '';
				}
				if (!empty($price_ttc)) {
					$pu_ttc = price2num($price_ttc, 'MU');
				} else {
					$pu_ttc = '';
				}
				$tva_npr = (preg_match('/\*/', $tva_tx) ? 1 : 0);
				$tva_tx = str_replace('*', '', $tva_tx);
				if (empty($tva_tx)) {
					$tva_npr = 0;
				}
				$label = (GETPOST('product_label') ? GETPOST('product_label') : '');
				$desc = $product_desc;
				$type = GETPOST('type');
				$fk_unit = GETPOST('units', 'alpha');

				if ($pu_ttc && !$pu_ht) {
					$price_base_type = 'TTC';
				}
			}

			// Define info_bits
			$info_bits = 0;
			if ($tva_npr) {
				$info_bits |= 0x01;
			}

			// Local Taxes
			$localtax1_tx = get_localtax($tva_tx, 1, $object->thirdparty, $mysoc, $tva_npr);
			$localtax2_tx = get_localtax($tva_tx, 2, $object->thirdparty, $mysoc, $tva_npr);

			$pu_ht_devise = price2num($price_ht_devise, '', 2);
			$pu_ttc_devise = price2num($price_ttc_devise, '', 2);

			// Prepare a price equivalent for minimum price check
			$pu_equivalent = $pu_ht;
			$pu_equivalent_ttc = $pu_ttc;

			$currency_tx = $object->multicurrency_tx;

			// Check if we have a foreign currency
			// If so, we update the pu_equiv as the equivalent price in base currency
			if ($pu_ht == '' && $pu_ht_devise != '' && $currency_tx != '') {
				$pu_equivalent = (float) $pu_ht_devise * $currency_tx;
			}
			if ($pu_ttc == '' && $pu_ttc_devise != '' && $currency_tx != '') {
				$pu_equivalent_ttc = (float) $pu_ttc_devise * $currency_tx;
			}

			// TODO $pu_equivalent or $pu_equivalent_ttc must be calculated from the one not null taking into account all taxes
			/*
			 if ($pu_equivalent) {
			 $tmp = calcul_price_total(1, $pu_equivalent, 0, $tva_tx, -1, -1, 0, 'HT', $info_bits, $type);
			 $pu_equivalent_ttc = ...
			 } else {
			 $tmp = calcul_price_total(1, $pu_equivalent_ttc, 0, $tva_tx, -1, -1, 0, 'TTC', $info_bits, $type);
			 $pu_equivalent_ht = ...
			 }
			 */

			// Margin
			$fournprice = price2num(GETPOST('fournprice'.$predef) ? GETPOST('fournprice'.$predef) : '');
			$buyingprice = price2num(GETPOST('buying_price'.$predef) != '' ? GETPOST('buying_price'.$predef) : ''); // If buying_price is '0', we must keep this value


			$price2num_pu_ht = price2num($pu_ht);
			$price2num_remise_percent = price2num($remise_percent);
			$price2num_price_min = price2num($price_min);
			$price2num_price_min_ttc = price2num($price_min_ttc);
			if (empty($price2num_pu_ht)) {
				$price2num_pu_ht = 0;
			}
			if (empty($price2num_remise_percent)) {
				$price2num_remise_percent = 0;
			}
			if (empty($price2num_price_min)) {
				$price2num_price_min = 0;
			}
			if (empty($price2num_price_min_ttc)) {
				$price2num_price_min_ttc = 0;
			}

			// Check price is not lower than minimum (check is done only for standard or replacement invoices)
			if ($usermustrespectpricemin && ($object->type == Facture::TYPE_STANDARD || $object->type == Facture::TYPE_REPLACEMENT)) {
				if ($pu_equivalent && $price_min && (((float) price2num($pu_equivalent) * (1 - $remise_percent / 100)) < (float) price2num($price_min)) && $price_base_type == 'HT') {
					$mesg = $langs->trans("CantBeLessThanMinPrice", price(price2num($price_min, 'MU'), 0, $langs, 0, 0, -1, $conf->currency));
					setEventMessages($mesg, null, 'errors');
					$error++;
				} elseif ($pu_equivalent_ttc && $price_min_ttc && (((float) price2num($pu_equivalent_ttc) * (1 - $remise_percent / 100)) < (float) price2num($price_min_ttc)) && $price_base_type == 'TTC') {
					$mesg = $langs->trans("CantBeLessThanMinPriceInclTax", price(price2num($price_min_ttc, 'MU'), 0, $langs, 0, 0, -1, $conf->currency));
					setEventMessages($mesg, null, 'errors');
					$error++;
				}
			}

			if (!$error) {
				'@phan-var-force array<string,mixed> $lines';
				// Add batchinfo if the detail_batch array is defined
				if (isModEnabled('productbatch') && !empty($lines[$i]->detail_batch) && is_array($lines[$i]->detail_batch) && getDolGlobalString('INVOICE_INCUDE_DETAILS_OF_LOTS_SERIALS')) {
					$langs->load('productbatch');
					foreach ($lines[$i]->detail_batch as $batchline) {
						$desc .= ' '.$langs->trans('Batch').' '.$batchline->batch.' '.$langs->trans('printQty', $batchline->qty).' ';
					}
				}

				// Insert line
				$result = $object->addline($desc, $pu_ht, $qty, $tva_tx, $localtax1_tx, $localtax2_tx, $idprod, $remise_percent, $date_start, $date_end, 0, $info_bits, 0, $price_base_type, $pu_ttc, $type, min($rank, count($object->lines) + 1), $special_code, '', 0, GETPOST('fk_parent_line'), $fournprice, $buyingprice, $label, $array_options, GETPOST('progress'), 0, $fk_unit, $pu_ht_devise);

				if ($result > 0) {
					// Define output language and generate document
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
							$outputlangs->load('products');
						}
						$model = $object->model_pdf;
						$ret = $object->fetch($id); // Reload to get new records

						$result = $object->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
						if ($result < 0) {
							setEventMessages($object->error, $object->errors, 'errors');
						}
					}

					unset($_POST['prod_entry_mode']);
					unset($_POST['qty']);
					unset($_POST['type']);
					unset($_POST['remise_percent']);
					unset($_POST['price_ht']);
					unset($_POST['multicurrency_price_ht']);
					unset($_POST['price_ttc']);
					unset($_POST['tva_tx']);
					unset($_POST['product_ref']);
					unset($_POST['product_label']);
					unset($_POST['product_desc']);
					unset($_POST['fournprice']);
					unset($_POST['buying_price']);
					unset($_POST['np_marginRate']);
					unset($_POST['np_markRate']);
					unset($_POST['dp_desc']);
					unset($_POST['idprod']);
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
					unset($_POST['situations']);
					unset($_POST['progress']);
				} else {
					setEventMessages($object->error, $object->errors, 'errors');
				}

				$action = '';
			}
		}
	} elseif ($action == 'updateline' && $usercancreate && !GETPOST('cancel', 'alpha')) {
		if (!$object->fetch($id) > 0) {
			dol_print_error($db);
		}
		$object->fetch_thirdparty();

		// Clean parameters
		$date_start = '';
		$date_end = '';
		$date_start = dol_mktime(GETPOST('date_starthour'), GETPOST('date_startmin'), GETPOST('date_startsec'), GETPOST('date_startmonth'), GETPOST('date_startday'), GETPOST('date_startyear'));
		$date_end = dol_mktime(GETPOST('date_endhour'), GETPOST('date_endmin'), GETPOST('date_endsec'), GETPOST('date_endmonth'), GETPOST('date_endday'), GETPOST('date_endyear'));
		$description = dol_htmlcleanlastbr(GETPOST('product_desc', 'restricthtml') ? GETPOST('product_desc', 'restricthtml') : GETPOST('desc', 'restricthtml'));
		$vat_rate = (GETPOST('tva_tx') ? GETPOST('tva_tx') : 0);
		$vat_rate = str_replace('*', '', $vat_rate);

		$pu_ht = price2num(GETPOST('price_ht'), '', 2);
		$pu_ttc = price2num(GETPOST('price_ttc'), '', 2);

		$pu_ht_devise = price2num(GETPOST('multicurrency_subprice'), '', 2);
		$pu_ttc_devise = price2num(GETPOST('multicurrency_subprice_ttc'), '', 2);

		$qty = price2num(GETPOST('qty', 'alpha'), 'MS');

		// Define info_bits
		$info_bits = 0;
		if (preg_match('/\*/', $vat_rate)) {
			$info_bits |= 0x01;
		}

		// Define vat_rate
		$vat_rate = str_replace('*', '', $vat_rate);
		$localtax1_rate = get_localtax($vat_rate, 1, $object->thirdparty);
		$localtax2_rate = get_localtax($vat_rate, 2, $object->thirdparty);

		// Add buying price
		$fournprice = price2num(GETPOST('fournprice') ? GETPOST('fournprice') : '');
		$buyingprice = price2num(GETPOST('buying_price') != '' ? GETPOST('buying_price') : ''); // If buying_price is '0', we must keep this value

		// Prepare a price equivalent for minimum price check
		$pu_equivalent = $pu_ht;
		$pu_equivalent_ttc = $pu_ttc;

		$currency_tx = $object->multicurrency_tx;

		// Check if we have a foreign currency
		// If so, we update the pu_equiv as the equivalent price in base currency
		if ($pu_ht == '' && $pu_ht_devise != '' && $currency_tx != '') {
			$pu_equivalent = (float) $pu_ht_devise * (float) $currency_tx;
		}
		if ($pu_ttc == '' && $pu_ttc_devise != '' && $currency_tx != '') {
			$pu_equivalent_ttc = (float) $pu_ttc_devise * (float) $currency_tx;
		}

		// TODO $pu_equivalent or $pu_equivalent_ttc must be calculated from the one not null taking into account all taxes
		/*
		 if ($pu_equivalent) {
		 $tmp = calcul_price_total(1, $pu_equivalent, 0, $vat_rate, -1, -1, 0, 'HT', $info_bits, $type);
		 $pu_equivalent_ttc = ...
		 } else {
		 $tmp = calcul_price_total(1, $pu_equivalent_ttc, 0, $vat_rate, -1, -1, 0, 'TTC', $info_bits, $type);
		 $pu_equivalent_ht = ...
		 }
		 */

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

		// Define special_code for special lines
		$special_code = GETPOSTINT('special_code');
		if ($special_code == 3) {
			$special_code = 0;	// Options should not exists on invoices
		}

		$line = new FactureLigne($db);
		$line->fetch(GETPOSTINT('lineid'));
		$percent = $line->get_prev_progress($object->id);
		$progress = price2num(GETPOST('progress', 'alpha'));

		if ($object->type == Facture::TYPE_CREDIT_NOTE && $object->situation_cycle_ref > 0) {
			// in case of situation credit note
			if ($progress >= 0) {
				$mesg = $langs->trans("CantBeNullOrPositive");
				setEventMessages($mesg, null, 'warnings');
				$error++;
				$result = -1;
			} elseif ($progress < $line->situation_percent) { // TODO : use a modified $line->get_prev_progress($object->id) result
				$mesg = $langs->trans("CantBeLessThanMinPercent");
				setEventMessages($mesg, null, 'warnings');
				$error++;
				$result = -1;
			} elseif ($progress < $percent) {
				$mesg = '<div class="warning">'.$langs->trans("CantBeLessThanMinPercent").'</div>';
				setEventMessages($mesg, null, 'warnings');
				$error++;
				$result = -1;
			}
		}

		$remise_percent = price2num(GETPOST('remise_percent'), '', 2);

		// Check minimum price
		$productid = GETPOSTINT('productid');
		if (!empty($productid)) {
			$product = new Product($db);
			$product->fetch($productid);

			$type = $product->type;

			$price_min = $product->price_min;
			if ((getDolGlobalString('PRODUIT_MULTIPRICES') || getDolGlobalString('PRODUIT_CUSTOMER_PRICES_BY_QTY_MULTIPRICES')) && !empty($object->thirdparty->price_level)) {
				$price_min = $product->multiprices_min[$object->thirdparty->price_level];
			}
			$price_min_ttc = $product->price_min_ttc;
			if ((getDolGlobalString('PRODUIT_MULTIPRICES') || getDolGlobalString('PRODUIT_CUSTOMER_PRICES_BY_QTY_MULTIPRICES')) && !empty($object->thirdparty->price_level)) {
				$price_min_ttc = $product->multiprices_min_ttc[$object->thirdparty->price_level];
			}

			$label = ((GETPOST('update_label') && GETPOST('product_label')) ? GETPOST('product_label') : '');

			// Check price is not lower than minimum (check is done only for standard or replacement invoices)
			if ($usermustrespectpricemin && ($object->type == Facture::TYPE_STANDARD || $object->type == Facture::TYPE_REPLACEMENT)) {
				if ($pu_equivalent && $price_min && (((float) price2num($pu_equivalent) * (1 - (float) $remise_percent / 100)) < (float) price2num($price_min)) && $price_base_type == 'HT') {
					$mesg = $langs->trans("CantBeLessThanMinPrice", price(price2num($price_min, 'MU'), 0, $langs, 0, 0, -1, $conf->currency));
					setEventMessages($mesg, null, 'errors');
					$error++;
					$action = 'editline';
				} elseif ($pu_equivalent_ttc && $price_min_ttc && (((float) price2num($pu_equivalent_ttc) * (1 - (float) $remise_percent / 100)) < (float) price2num($price_min_ttc)) && $price_base_type == 'TTC') {
					$mesg = $langs->trans("CantBeLessThanMinPriceInclTax", price(price2num($price_min_ttc, 'MU'), 0, $langs, 0, 0, -1, $conf->currency));
					setEventMessages($mesg, null, 'errors');
					$error++;
					$action = 'editline';
				}
			}
		} else {
			$type = GETPOST('type');
			$label = (GETPOST('product_label') ? GETPOST('product_label') : '');

			// Check parameters
			if (GETPOST('type') < 0) {
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Type")), null, 'errors');
				$error++;
			}
		}
		if ($qty < 0) {
			$langs->load("errors");
			setEventMessages($langs->trans('ErrorQtyForCustomerInvoiceCantBeNegative'), null, 'errors');
			$error++;
		}
		if (empty($productid) && (($pu_ht < 0 && !getDolGlobalString('FACTURE_ENABLE_NEGATIVE_LINES')) || $pu_ht == '') && (($pu_ht_devise < 0 && !getDolGlobalString('FACTURE_ENABLE_NEGATIVE_LINES')) || $pu_ht_devise == '') && $pu_ttc === '' && $pu_ttc_devise === '' && $object->type != Facture::TYPE_CREDIT_NOTE) { 	// Unit price can be 0 but not ''
			if (($pu_ht < 0 || $pu_ttc < 0) && !getDolGlobalString('FACTURE_ENABLE_NEGATIVE_LINES')) {
				$langs->load("errors");
				if ($object->type == $object::TYPE_DEPOSIT) {
					// Using negative lines on deposit lead to headach and blocking problems when you want to consume them.
					setEventMessages($langs->trans("ErrorLinesCantBeNegativeOnDeposits"), null, 'errors');
				} else {
					setEventMessages($langs->trans("ErrorFieldCantBeNegativeOnInvoice", $langs->transnoentitiesnoconv("UnitPriceHT"), $langs->transnoentitiesnoconv("CustomerAbsoluteDiscountShort")), null, 'errors');
				}
				$error++;
			} else {
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("UnitPriceHT")), null, 'errors');
				$error++;
			}
		}

		// Invoice situation
		if (getDolGlobalInt('INVOICE_USE_SITUATION') == 2) {
			$previousprogress = $line->get_allprev_progress($line->fk_facture);
			$fullprogress = price2num(GETPOST('progress', 'alpha'));

			if ($fullprogress < $previousprogress) {
				$error++;
				setEventMessages($langs->trans('CantBeLessThanMinPercent'), null, 'errors');
			}

			// Max 100%
			if ($fullprogress > 100) {
				$fullprogress = 100;
			}
			$addprogress = $fullprogress - $previousprogress;
		} else {
			$addprogress = price2num(GETPOST('progress', 'alpha'));
		}

		// Update line
		if (!$error) {
			if (empty($usercancreatemargin)) {
				foreach ($object->lines as &$line) {
					if ($line->id == GETPOSTINT('lineid')) {
						$fournprice = $line->fk_fournprice;
						$buyingprice = $line->pa_ht;
						break;
					}
				}
			}

			$price_base_type = 'HT';
			$pu = $pu_ht;
			if (empty($pu) && !empty($pu_ttc)) {
				$pu = $pu_ttc;
				$price_base_type = 'TTC';
			}

			$result = $object->updateline(
				GETPOSTINT('lineid'),
				$description,
				$pu,
				$qty,
				$remise_percent,
				$date_start,
				$date_end,
				$vat_rate,
				$localtax1_rate,
				$localtax2_rate,
				$price_base_type,
				$info_bits,
				$type,
				GETPOSTINT('fk_parent_line'),
				0,
				$fournprice,
				$buyingprice,
				$label,
				$special_code,
				$array_options,
				$addprogress,
				GETPOST('units', 'alpha'),
				$pu_ht_devise
			);

			if ($result >= 0) {
				if (!getDolGlobalString('MAIN_DISABLE_PDF_AUTOUPDATE')) {
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
						$outputlangs->load('products');
					}

					$ret = $object->fetch($id); // Reload to get new records
					$object->generateDocument($object->model_pdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
				}

				unset($_POST['qty']);
				unset($_POST['type']);
				unset($_POST['productid']);
				unset($_POST['remise_percent']);
				unset($_POST['price_ht']);
				unset($_POST['multicurrency_price_ht']);
				unset($_POST['price_ttc']);
				unset($_POST['tva_tx']);
				unset($_POST['product_ref']);
				unset($_POST['product_label']);
				unset($_POST['product_desc']);
				unset($_POST['fournprice']);
				unset($_POST['buying_price']);
				unset($_POST['np_marginRate']);
				unset($_POST['np_markRate']);
				unset($_POST['dp_desc']);
				unset($_POST['idprod']);
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
				unset($_POST['situations']);
				unset($_POST['progress']);
			} else {
				setEventMessages($object->error, $object->errors, 'errors');
			}
		}
	} elseif ($action == 'updatealllines' && $usercancreate && GETPOST('all_percent') == $langs->trans('Modifier')) {	// Update all lines of situation invoice
		if (!$object->fetch($id) > 0) {
			dol_print_error($db);
		}
		if (GETPOST('all_progress') != "") {
			$all_progress = GETPOSTINT('all_progress');
			foreach ($object->lines as $line) {
				if (getDolGlobalInt('INVOICE_USE_SITUATION') == 2) {
					$percent = $line->get_allprev_progress($object->id);
				} else {
					$percent = $line->get_prev_progress($object->id);
				}
				if ((float) $all_progress < (float) $percent) {
					$mesg = $langs->trans("Line").' '.$i.' : '.$langs->trans("CantBeLessThanMinPercent");
					setEventMessages($mesg, null, 'warnings');
					$result = -1;
				} else {
					$object->update_percent($line, GETPOST('all_progress'), false);
				}
			}
			$object->update_price(1);
		}
	} elseif ($action == 'updateline' && $usercancreate && !$cancel) {
		header('Location: '.$_SERVER["PHP_SELF"].'?facid='.$id); // To show again edited page
		exit();
	} elseif ($action == 'confirm_situationout' && $confirm == 'yes' && $usercancreate) {
		// Outing situation invoice from cycle
		$object->fetch($id, '', '', 0, true);

		if (in_array($object->status, array(Facture::STATUS_CLOSED, Facture::STATUS_VALIDATED))
			&& $object->type == Facture::TYPE_SITUATION
			&& $usercancreate
			&& !$objectidnext
			&& $object->is_last_in_cycle()
			&& $usercanunvalidate
		) {
			$outingError = 0;
			$newCycle = $object->newCycle(); // we need to keep the "situation behavior" so we place it on a new situation cycle
			if ($newCycle > 1) {
				// Search credit notes
				$lastCycle = $object->situation_cycle_ref;
				$lastSituationCounter = $object->situation_counter;
				$linkedCreditNotesList = array();

				if (count($object->tab_next_situation_invoice) > 0) {
					foreach ($object->tab_next_situation_invoice as $next_invoice) {
						if ($next_invoice->type == Facture::TYPE_CREDIT_NOTE
							&& $next_invoice->situation_counter == $object->situation_counter
							&& $next_invoice->fk_facture_source == $object->id
						) {
							$linkedCreditNotesList[] = $next_invoice->id;
						}
					}
				}

				$object->situation_cycle_ref = $newCycle;
				$object->situation_counter = 1;
				$object->situation_final = 0;
				if ($object->update($user) > 0) {
					$errors = 0;
					if (count($linkedCreditNotesList) > 0) {
						// now, credit note must follow
						$sql = 'UPDATE '.MAIN_DB_PREFIX.'facture';
						$sql .= ' SET situation_cycle_ref = '.((int) $newCycle);
						$sql .= ' , situation_final=0';
						$sql .= ' , situation_counter='.((int) $object->situation_counter);
						$sql .= ' WHERE rowid IN ('.$db->sanitize(implode(',', $linkedCreditNotesList)).')';

						$resql = $db->query($sql);
						if (!$resql) {
							$errors++;
						}

						// Change each progression percent on each lines
						foreach ($object->lines as $line) {
							// no traitement for special product
							if ($line->product_type == 9) {
								continue;
							}


							if (!empty($object->tab_previous_situation_invoice)) {
								// search the last invoice in cycle
								$lineIndex = count($object->tab_previous_situation_invoice) - 1;
								$searchPreviousInvoice = true;
								while ($searchPreviousInvoice) {
									if ($object->tab_previous_situation_invoice[$lineIndex]->type == Facture::TYPE_SITUATION || $lineIndex < 1) {
										$searchPreviousInvoice = false; // find, exit;
										break;
									} else {
										$lineIndex--; // go to previous invoice in cycle
									}
								}


								$maxPrevSituationPercent = 0;
								foreach ($object->tab_previous_situation_invoice[$lineIndex]->lines as $prevLine) {
									if ($prevLine->id == $line->fk_prev_id) {
										$maxPrevSituationPercent = max($maxPrevSituationPercent, $prevLine->situation_percent);
									}
								}


								$line->situation_percent -= $maxPrevSituationPercent;

								if ($line->update() < 0) {
									$errors++;
								}
							}
						}
					}

					if (!$errors) {
						setEventMessages($langs->trans('Updated'), null, 'mesgs');
						header("Location: ".$_SERVER['PHP_SELF']."?id=".$id);
					} else {
						setEventMessages($langs->trans('ErrorOutingSituationInvoiceCreditNote'), array(), 'errors');
					}
				} else {
					setEventMessages($langs->trans('ErrorOutingSituationInvoiceOnUpdate'), array(), 'errors');
				}
			} else {
				setEventMessages($langs->trans('ErrorFindNextSituationInvoice'), array(), 'errors');
			}
		}
	} elseif ($action == 'import_lines_from_object' && $usercancreate && $object->status == Facture::STATUS_DRAFT
		&& ($object->type == Facture::TYPE_STANDARD || $object->type == Facture::TYPE_REPLACEMENT || $object->type == Facture::TYPE_DEPOSIT || $object->type == Facture::TYPE_PROFORMA || $object->type == Facture::TYPE_SITUATION)) {
		// add lines from objectlinked
		$fromElement = GETPOST('fromelement');
		$fromElementid = GETPOST('fromelementid');
		$importLines = GETPOST('line_checkbox');

		if (!empty($importLines) && is_array($importLines) && !empty($fromElement) && ctype_alpha($fromElement) && !empty($fromElementid)) {
			if ($fromElement == 'commande') {
				dol_include_once('/'.$fromElement.'/class/'.$fromElement.'.class.php');
				$lineClassName = 'OrderLine';
			} elseif ($fromElement == 'propal') {
				dol_include_once('/comm/'.$fromElement.'/class/'.$fromElement.'.class.php');
				$lineClassName = 'PropaleLigne';
			}
			$nextRang = count($object->lines) + 1;
			$importCount = 0;
			$error = 0;
			foreach ($importLines as $lineId) {
				$lineId = intval($lineId);
				$originLine = new $lineClassName($db);
				if (intval($fromElementid) > 0 && $originLine->fetch($lineId) > 0) {
					$originLine->fetch_optionals();
					$desc = $originLine->desc;
					$pu_ht = $originLine->subprice;
					$qty = $originLine->qty;
					$txtva = $originLine->tva_tx;
					$txlocaltax1 = $originLine->localtax1_tx;
					$txlocaltax2 = $originLine->localtax2_tx;
					$fk_product = $originLine->fk_product;
					$remise_percent = $originLine->remise_percent;
					$date_start = $originLine->date_start;
					$date_end = $originLine->date_end;
					$fk_code_ventilation = 0;
					$info_bits = $originLine->info_bits;
					$fk_remise_except = $originLine->fk_remise_except;
					$price_base_type = 'HT';
					$pu_ttc = 0;
					$type = $originLine->product_type;
					$rang = $nextRang++;
					$special_code = $originLine->special_code;
					$origin = $originLine->element;
					$origin_id = $originLine->id;
					$fk_parent_line = 0;
					$fk_fournprice = $originLine->fk_fournprice;
					$pa_ht = $originLine->pa_ht;
					$label = $originLine->label;
					$array_options = $originLine->array_options;
					if ($object->type == Facture::TYPE_SITUATION) {
						$situation_percent = 0;
					} else {
						$situation_percent = 100;
					}
					$fk_prev_id = 0;
					$fk_unit = $originLine->fk_unit;
					$pu_ht_devise = $originLine->multicurrency_subprice;

					$res = $object->addline($desc, $pu_ht, $qty, $txtva, $txlocaltax1, $txlocaltax2, $fk_product, $remise_percent, $date_start, $date_end, $fk_code_ventilation, $info_bits, $fk_remise_except, $price_base_type, $pu_ttc, $type, $rang, $special_code, $origin, $origin_id, $fk_parent_line, $fk_fournprice, $pa_ht, $label, $array_options, $situation_percent, $fk_prev_id, $fk_unit, $pu_ht_devise);

					if ($res > 0) {
						$importCount++;
					} else {
						$error++;
					}
				} else {
					$error++;
				}
			}

			if ($error) {
				setEventMessages($langs->trans('ErrorsOnXLines', $error), null, 'errors');
			}
		}
	}

	// Actions when printing a doc from card
	include DOL_DOCUMENT_ROOT.'/core/actions_printing.inc.php';

	// Actions to send emails
	if (empty($id)) {
		$id = $facid;
	}
	$triggersendname = 'BILL_SENTBYMAIL';
	$paramname = 'id';
	$autocopy = 'MAIN_MAIL_AUTOCOPY_INVOICE_TO';
	$trackid = 'inv'.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';

	// Actions to build doc
	$upload_dir = $conf->invoice->multidir_output[!empty($object->entity) ? $object->entity : $conf->entity];
	$permissiontoadd = $usercancreate;
	include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';


	if ($action == 'update_extras' && $usercancreate) {
		$object->oldcopy = dol_clone($object, 2);
		$attribute_name = GETPOST('attribute', 'restricthtml');

		// Fill array 'array_options' with data from add form
		$ret = $extrafields->setOptionalsFromPost(null, $object, $attribute_name);
		if ($ret < 0) {
			$error++;
		}

		if (!$error) {
			// Actions on extra fields
			$result = $object->updateExtraField($attribute_name, 'BILL_MODIFY');
			if ($result < 0) {
				setEventMessages($object->error, $object->errors, 'errors');
				$error++;
			}
		}

		if ($error) {
			$action = 'edit_extras';
		}
	}

	if (getDolGlobalString('MAIN_DISABLE_CONTACTS_TAB')) {
		if ($action == 'addcontact' && $usercancreate) {
			$result = $object->fetch($id);

			if ($result > 0 && $id > 0) {
				$contactid = (GETPOST('userid') ? GETPOST('userid') : GETPOST('contactid'));
				$typeid = (GETPOST('typecontact') ? GETPOST('typecontact') : GETPOST('type'));
				$result = $object->add_contact($contactid, $typeid, GETPOST("source", 'aZ09'));
			}

			if ($result >= 0) {
				header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
				exit();
			} else {
				if ($object->error == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
					$langs->load("errors");
					setEventMessages($langs->trans("ErrorThisContactIsAlreadyDefinedAsThisType"), null, 'errors');
				} else {
					setEventMessages($object->error, $object->errors, 'errors');
				}
			}
		} elseif ($action == 'swapstatut' && $usercancreate) {
			// toggle the status of a contact
			if ($object->fetch($id)) {
				$result = $object->swapContactStatus(GETPOSTINT('ligne'));
			} else {
				dol_print_error($db);
			}
		} elseif ($action == 'deletecontact' && $usercancreate) {
			// Delete a contact
			$object->fetch($id);
			$result = $object->delete_contact($lineid);

			if ($result >= 0) {
				header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
				exit();
			} else {
				dol_print_error($db);
			}
		}

		if ($error) {
			$action = 'edit_extras';
		}
	}
}


/*
 * View
 */


$form = new Form($db);
$formother = new FormOther($db);
$formfile = new FormFile($db);
$formmargin = new FormMargin($db);
$soc = new Societe($db);
$paymentstatic = new Paiement($db);
$bankaccountstatic = new Account($db);
if (isModEnabled('project')) {
	$formproject = new FormProjets($db);
}

$now = dol_now();

$title = $object->ref." - ".$langs->trans('Card');
if ($action == 'create') {
	$title = $langs->trans("NewBill");
}
$help_url = "EN:Customers_Invoices|FR:Factures_Clients|ES:Facturas_a_clientes";

llxHeader('', $title, $help_url);

// Mode creation

if ($action == 'create') {
	$facturestatic = new Facture($db);
	$extrafields->fetch_name_optionals_label($facturestatic->table_element);

	print load_fiche_titre($langs->trans('NewBill'), '', 'bill');

	if ($socid > 0) {
		$res = $soc->fetch($socid);
	}

	$currency_code = $conf->currency;

	$cond_reglement_id = GETPOSTINT('cond_reglement_id');
	$mode_reglement_id = GETPOSTINT('mode_reglement_id');
	$fk_account = GETPOSTINT('fk_account');

	// Load objectsrc
	//$remise_absolue = 0;
	if (!empty($origin) && !empty($originid)) {
		// Parse element/subelement (ex: project_task)
		$element = $subelement = $origin;
		$regs = array();
		if (preg_match('/^([^_]+)_([^_]+)/i', $origin, $regs)) {
			$element = $regs[1];
			$subelement = $regs[2];
		}

		$dateinvoice = dol_mktime(0, 0, 0, GETPOSTINT('remonth'), GETPOSTINT('reday'), GETPOSTINT('reyear'), 'tzserver');	// If we enter the 02 january, we need to save the 02 january for server
		$date_pointoftax = dol_mktime(0, 0, 0, GETPOSTINT('date_pointoftaxmonth'), GETPOSTINT('date_pointoftaxday'), GETPOSTINT('date_pointoftaxyear'), 'tzserver');

		if ($element == 'project') {
			$projectid = $originid;

			if (empty($cond_reglement_id)) {
				$cond_reglement_id = $soc->cond_reglement_id;
			}
			if (empty($mode_reglement_id)) {
				$mode_reglement_id = $soc->mode_reglement_id;
			}
			if (empty($fk_account)) {
				$fk_account = $soc->fk_account;
			}
			if (empty($dateinvoice)) {
				// Do not set 0 here (0 for a date is 1970)
				$dateinvoice = getDolGlobalString('MAIN_AUTOFILL_DATE') ? '' : -1;
			}
		} else {
			// For compatibility
			if ($element == 'order' || $element == 'commande') {
				$element = $subelement = 'commande';
			}
			if ($element == 'propal') {
				$element = 'comm/propal';
				$subelement = 'propal';
			}
			if ($element == 'contract') {
				$element = $subelement = 'contrat';
			}
			if ($element == 'shipping') {
				$element = $subelement = 'expedition';
			}

			dol_include_once('/'.$element.'/class/'.$subelement.'.class.php');

			$classname = ucfirst($subelement);
			$objectsrc = new $classname($db);
			$objectsrc->fetch($originid);
			if (empty($objectsrc->lines) && method_exists($objectsrc, 'fetch_lines')) {
				$objectsrc->fetch_lines();
			}
			$objectsrc->fetch_thirdparty();

			$projectid = (!empty($projectid) ? $projectid : $objectsrc->fk_project);
			$ref_client = (!empty($objectsrc->ref_client) ? $objectsrc->ref_client : (!empty($objectsrc->ref_customer) ? $objectsrc->ref_customer : ''));

			// only if socid not filled else it's already done above
			if (empty($socid)) {
				$soc = $objectsrc->thirdparty;
			}

			$dateinvoice = (empty($dateinvoice) ? (!getDolGlobalString('MAIN_AUTOFILL_DATE') ? -1 : '') : $dateinvoice);

			if ($element == 'expedition') {
				$ref_client = (!empty($objectsrc->ref_customer) ? $objectsrc->ref_customer : '');

				$elem = $subelem = $objectsrc->origin;
				$expeoriginid = $objectsrc->origin_id;
				dol_include_once('/'.$elem.'/class/'.$subelem.'.class.php');
				$classname = ucfirst($subelem);

				$expesrc = new $classname($db);
				$expesrc->fetch($expeoriginid);

				$cond_reglement_id 	= (!empty($expesrc->cond_reglement_id) ? $expesrc->cond_reglement_id : (!empty($soc->cond_reglement_id) ? $soc->cond_reglement_id : 1));
				$mode_reglement_id 	= (!empty($expesrc->mode_reglement_id) ? $expesrc->mode_reglement_id : (!empty($soc->mode_reglement_id) ? $soc->mode_reglement_id : 0));
				$fk_account         = (!empty($expesrc->fk_account) ? $expesrc->fk_account : (!empty($soc->fk_account) ? $soc->fk_account : 0));

				if (isModEnabled('multicurrency')) {
					$currency_code 	= (!empty($expesrc->multicurrency_code) ? $expesrc->multicurrency_code : (!empty($soc->multicurrency_code) ? $soc->multicurrency_code : $objectsrc->multicurrency_code));
					$currency_tx 	= (!empty($expesrc->multicurrency_tx) ? $expesrc->multicurrency_tx : (!empty($soc->multicurrency_tx) ? $soc->multicurrency_tx : $objectsrc->multicurrency_tx));
				}

				//Replicate extrafields
				$expesrc->fetch_optionals();
				$object->array_options = $expesrc->array_options;
			} else {
				$cond_reglement_id 	= (!empty($objectsrc->cond_reglement_id) ? $objectsrc->cond_reglement_id : (!empty($soc->cond_reglement_id) ? $soc->cond_reglement_id : 0));
				$mode_reglement_id 	= (!empty($objectsrc->mode_reglement_id) ? $objectsrc->mode_reglement_id : (!empty($soc->mode_reglement_id) ? $soc->mode_reglement_id : 0));
				$fk_account         = (!empty($objectsrc->fk_account) ? $objectsrc->fk_account : (!empty($soc->fk_account) ? $soc->fk_account : 0));

				if (isModEnabled('multicurrency')) {
					if (!empty($objectsrc->multicurrency_code)) {
						$currency_code = $objectsrc->multicurrency_code;
					}
					if (getDolGlobalString('MULTICURRENCY_USE_ORIGIN_TX') && !empty($objectsrc->multicurrency_tx)) {
						$currency_tx = $objectsrc->multicurrency_tx;
					}
				}

				// Replicate extrafields
				$objectsrc->fetch_optionals();
				$object->array_options = $objectsrc->array_options;
			}
		}
	} else {
		$cond_reglement_id 	= empty($soc->cond_reglement_id) ? $cond_reglement_id : $soc->cond_reglement_id;
		$mode_reglement_id  = empty($soc->mode_reglement_id) ? $mode_reglement_id : $soc->mode_reglement_id;
		$fk_account         = empty($soc->fk_account) ? $fk_account : $soc->fk_account;

		$dateinvoice = (empty($dateinvoice) ? (!getDolGlobalString('MAIN_AUTOFILL_DATE') ? -1 : '') : $dateinvoice); // Do not set 0 here (0 for a date is 1970)

		if (isModEnabled('multicurrency') && !empty($soc->multicurrency_code)) {
			$currency_code = $soc->multicurrency_code;
		}
	}

	// If form was posted (but error returned), we must reuse the value posted in priority (standard Dolibarr behaviour)
	if (!GETPOST('changecompany')) {
		if (GETPOSTISSET('cond_reglement_id')) {
			$cond_reglement_id = GETPOSTINT('cond_reglement_id');
		}
		if (GETPOSTISSET('mode_reglement_id')) {
			$mode_reglement_id = GETPOSTINT('mode_reglement_id');
		}
		if (GETPOSTISSET('cond_reglement_id')) {
			$fk_account = GETPOSTINT('fk_account');
		}
	}

	// when payment condition is empty (means not override by payment condition form a other object, like third-party), try to use default value
	if (empty($cond_reglement_id)) {
		$cond_reglement_id = GETPOSTINT("cond_reglement_id");
	}

	// when payment mode is empty (means not override by payment mode form a other object, like third-party), try to use default value
	if (empty($mode_reglement_id)) {
		$mode_reglement_id = GETPOSTINT("mode_reglement_id");
	}

	// when bank account is empty (means not override by payment mode form a other object, like third-party), try to use default value
	// if ($socid > 0 && $fk_account) {	// A company has already been set and it has a default fk_account
	// 	$fk_account = GETPOSTISSET('fk_account') ? GETPOST("fk_account", 'int') : $fk_account;	// The GETPOST is used only if form was posted to avoid to take default value, because in such case, the default must be the one of the company
	// } else {	// No company forced
	// 	$fk_account = GETPOST("fk_account", 'int');
	// }

	if (!empty($soc->id)) {
		$absolute_discount = $soc->getAvailableDiscounts();
	}
	$note_public = $object->getDefaultCreateValueFor('note_public', ((!empty($origin) && !empty($originid) && is_object($objectsrc) && getDolGlobalString('FACTURE_REUSE_NOTES_ON_CREATE_FROM')) ? $objectsrc->note_public : null));
	$note_private = $object->getDefaultCreateValueFor('note_private', ((!empty($origin) && !empty($originid) && is_object($objectsrc) && getDolGlobalString('FACTURE_REUSE_NOTES_ON_CREATE_FROM')) ? $objectsrc->note_private : null));

	if (!empty($conf->use_javascript_ajax)) {
		require_once DOL_DOCUMENT_ROOT.'/core/lib/ajax.lib.php';
		print ajax_combobox('fac_replacement');
		print ajax_combobox('fac_avoir');
		print ajax_combobox('situations');
	}

	if ($origin == 'contrat') {
		$langs->load("admin");
		$text = $langs->trans("ToCreateARecurringInvoice");
		$text .= ' '.$langs->trans("ToCreateARecurringInvoiceGene", $langs->transnoentitiesnoconv("MenuFinancial"), $langs->transnoentitiesnoconv("BillsCustomers"), $langs->transnoentitiesnoconv("ListOfTemplates"));
		if (!getDolGlobalString('INVOICE_DISABLE_AUTOMATIC_RECURRING_INVOICE')) {
			$text .= ' '.$langs->trans("ToCreateARecurringInvoiceGeneAuto", $langs->transnoentitiesnoconv('Module2300Name'));
		}
		print info_admin($text, 0, 0, '0', 'opacitymedium').'<br>';
	}

	print '<form name="add" action="'.$_SERVER["PHP_SELF"].'" method="POST" id="formtocreate" name="formtocreate">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" id="formtocreateaction" value="add">';
	print '<input type="hidden" name="changecompany" value="0">';	// will be set to 1 by javascript so we know post is done after a company change
	if ($soc->id > 0) {
		print '<input type="hidden" name="socid" value="'.$soc->id.'">'."\n";
	}
	print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	print '<input name="ref" type="hidden" value="provisoire">';
	print '<input name="ref_client" type="hidden" value="'.$ref_client.'">';
	print '<input name="force_cond_reglement_id" type="hidden" value="0">';
	print '<input name="force_mode_reglement_id" type="hidden" value="0">';
	print '<input name="force_fk_account" type="hidden" value="0">';
	print '<input type="hidden" name="origin" value="'.$origin.'">';
	print '<input type="hidden" name="originid" value="'.$originid.'">';
	print '<input type="hidden" name="originentity" value="'.GETPOSTINT('originentity').'">';
	if (!empty($currency_tx)) {
		print '<input type="hidden" name="originmulticurrency_tx" value="'.$currency_tx.'">';
	}

	print dol_get_fiche_head();

	// Call Hook tabContentCreateInvoice
	$parameters = array();
	// Note that $action and $object may be modified by hook
	$reshook = $hookmanager->executeHooks('tabContentCreateInvoice', $parameters, $object, $action);
	if (empty($reshook)) {
		print '<table class="border centpercent">';

		$exampletemplateinvoice = new FactureRec($db);
		$invoice_predefined = new FactureRec($db);
		if (empty($origin) && empty($originid) && GETPOSTINT('fac_rec') > 0) {
			$invoice_predefined->fetch(GETPOSTINT('fac_rec'));
		}

		// Thirdparty
		if ($soc->id > 0 && (!GETPOSTINT('fac_rec') || !empty($invoice_predefined->frequency))) {
			// If thirdparty known and not a predefined invoiced without a recurring rule
			print '<tr><td class="fieldrequired">'.$langs->trans('Customer').'</td>';
			print '<td colspan="2">';
			print $soc->getNomUrl(1, 'customer');
			print '<input type="hidden" name="socid" value="'.$soc->id.'">';
			// Outstanding Bill
			$arrayoutstandingbills = $soc->getOutstandingBills();
			$outstandingBills = $arrayoutstandingbills['opened'];
			print ' - <span class="opacitymedium">'.$langs->trans('CurrentOutstandingBill').':</span> ';
			print '<span class="amount">'.price($outstandingBills, 0, $langs, 0, 0, -1, $conf->currency).'</span>';
			if ($soc->outstanding_limit != '') {
				if ($outstandingBills > $soc->outstanding_limit) {
					print img_warning($langs->trans("OutstandingBillReached"));
				}
				print ' / '.price($soc->outstanding_limit, 0, $langs, 0, 0, -1, $conf->currency);
			}
			print '</td>';
			print '</tr>'."\n";
		} else {
			print '<tr><td class="fieldrequired">'.$langs->trans('Customer').'</td>';
			print '<td colspan="2">';
			$filter = '((s.client:IN:1,2,3) AND (s.status:=:1))';
			print img_picto('', 'company', 'class="pictofixedwidth"').$form->select_company($soc->id, 'socid', $filter, 'SelectThirdParty', 1, 0, array(), 0, 'minwidth300 widthcentpercentminusxx maxwidth500');
			// Option to reload page to retrieve customer information.
			if (!getDolGlobalString('RELOAD_PAGE_ON_CUSTOMER_CHANGE_DISABLED')) {
				print '<script>
				$(document).ready(function() {
					$("#socid").change(function() {
						/*
						console.log("Submit page");
						$(\'input[name="action"]\').val(\'create\');
						$(\'input[name="force_cond_reglement_id"]\').val(\'1\');
						$(\'input[name="force_mode_reglement_id"]\').val(\'1\');
						$(\'input[name="force_fk_account"]\').val(\'1\');
						$("#formtocreate").submit(); */

						// For company change, we must submit page with action=create instead of action=add
						console.log("We have changed the company - Resubmit page");
						jQuery("input[name=changecompany]").val("1");
						jQuery("#formtocreateaction").val("create");
						jQuery("#formtocreate").submit();
					});
				});
				</script>';
			}
			if (!GETPOSTINT('fac_rec')) {
				print ' <a href="'.DOL_URL_ROOT.'/societe/card.php?action=create&client=3&fournisseur=0&backtopage='.urlencode($_SERVER["PHP_SELF"].'?action=create').'"><span class="fa fa-plus-circle valignmiddle paddingleft" title="'.$langs->trans("AddThirdParty").'"></span></a>';
			}
			print '</td>';
			print '</tr>'."\n";
		}

		// Overwrite some values if creation of invoice is from a predefined invoice
		if (empty($origin) && empty($originid) && GETPOSTINT('fac_rec') > 0) {
			$invoice_predefined->fetch(GETPOSTINT('fac_rec'));

			$dateinvoice = $invoice_predefined->date_when; // To use next gen date by default later
			if (empty($projectid)) {
				$projectid = $invoice_predefined->fk_project;
			}
			$cond_reglement_id = $invoice_predefined->cond_reglement_id;
			$mode_reglement_id = $invoice_predefined->mode_reglement_id;
			$fk_account = $invoice_predefined->fk_account;
			$note_public = $invoice_predefined->note_public;
			$note_private = $invoice_predefined->note_private;

			if (!empty($invoice_predefined->multicurrency_code)) {
				$currency_code = $invoice_predefined->multicurrency_code;
			}
			if (!empty($invoice_predefined->multicurrency_tx)) {
				$currency_tx = $invoice_predefined->multicurrency_tx;
			}

			$sql = 'SELECT r.rowid, r.titre as title, r.total_ttc';
			$sql .= ' FROM '.MAIN_DB_PREFIX.'facture_rec as r';
			$sql .= ' WHERE r.fk_soc = '.((int) $invoice_predefined->socid);

			$resql = $db->query($sql);
			if ($resql) {
				$num = $db->num_rows($resql);
				$i = 0;

				if ($num > 0) {
					print '<tr><td>'.$langs->trans('CreateFromRepeatableInvoice').'</td><td>';
					//print '<input type="hidden" name="fac_rec" id="fac_rec" value="'.GETPOST('fac_rec', 'int').'">';
					print '<select class="flat" id="fac_rec" name="fac_rec">'; // We may want to change the template to use
					print '<option value="0" selected></option>';
					while ($i < $num) {
						$objp = $db->fetch_object($resql);
						print '<option value="'.$objp->rowid.'"';
						if (GETPOSTINT('fac_rec') == $objp->rowid) {
							print ' selected';
							$exampletemplateinvoice->fetch(GETPOSTINT('fac_rec'));
						}
						print '>'.$objp->title.' ('.price($objp->total_ttc).' '.$langs->trans("TTC").')</option>';
						$i++;
					}
					print '</select>';

					print ajax_combobox("fac_rec");

					// Option to reload page to retrieve customer information. Note, this clear other input
					if (!getDolGlobalString('RELOAD_PAGE_ON_TEMPLATE_CHANGE_DISABLED')) {
						print '<script type="text/javascript">
						$(document).ready(function() {
							$("#fac_rec").change(function() {
								console.log("We have changed the template invoice - Reload page");
								var fac_rec = $(this).val();
								var socid = $(\'#socid\').val();
								// For template invoice change, we must reuse data of template, not input already done, so we call a GET with action=create, not a POST submit.
								window.location.href = "'.$_SERVER["PHP_SELF"].'?action=create&socid="+socid+"&fac_rec="+fac_rec;
							});
						});
						</script>';
					}
					print '</td></tr>';
				}
				$db->free($resql);
			} else {
				dol_print_error($db);
			}
		}

		print '<tr><td class="tdtop fieldrequired">'.$langs->trans('Type').'</td><td colspan="2">';
		print '<div class="tagtable">'."\n";

		// Standard invoice
		print '<div class="tagtr listofinvoicetype"><div class="tagtd listofinvoicetype">';
		$tmp = '<input type="radio" id="radio_standard" name="type" value="0"'.(GETPOSTINT('type') ? '' : ' checked').'> ';
		$tmp  = $tmp.'<label for="radio_standard" >'.$langs->trans("InvoiceStandardAsk").'</label>';
		// @phan-suppress-next-line PhanPluginSuspiciousParamOrder
		$desc = $form->textwithpicto($tmp, $langs->transnoentities("InvoiceStandardDesc"), 1, 'help', 'nowraponall', 0, 3, 'standardonsmartphone');
		print '<table class="nobordernopadding"><tr>';
		print '<td>';
		print $desc;
		print '</td>';
		if ((($origin == 'propal') || ($origin == 'commande')) && (!empty($originid))) {
			/*print '<td class="nowrap" style="padding-left: 5px">';
			$arraylist = array(
				//'amount' => $langs->transnoentitiesnoconv('FixAmount', $langs->transnoentitiesnoconv('Deposit')),
				//'variable' => $langs->transnoentitiesnoconv('VarAmountOneLine', $langs->transnoentitiesnoconv('Deposit')),
				'variablealllines' => $langs->transnoentitiesnoconv('VarAmountAllLines')
			);
			print $form->selectarray('typestandard', $arraylist, GETPOST('typestandard', 'aZ09'), 0, 0, 0, '', 1);
			print '</td>';*/
			print '<td class="nowrap" style="padding-left: 15px">';
			print '<span class="opacitymedium">'.$langs->trans('PercentOfOriginalObject').'</span>:<input class="right" placeholder="100%" type="text" id="valuestandardinvoice" name="valuestandardinvoice" size="3" value="'.(GETPOSTISSET('valuestandardinvoice') ? GETPOST('valuestandardinvoice', 'alpha') : '100%').'"/>';
			print '</td>';
		}
		print '</tr></table>';
		print '</div></div>';

		if ((empty($origin)) || ((($origin == 'propal') || ($origin == 'commande')) && (!empty($originid)))) {
			// Deposit - Down payment
			if (!getDolGlobalString('INVOICE_DISABLE_DEPOSIT')) {
				print '<div class="tagtr listofinvoicetype"><div class="tagtd listofinvoicetype">';
				$tmp = '<input type="radio" id="radio_deposit" name="type" value="3"'.(GETPOSTINT('type') == 3 ? ' checked' : '').'> ';
				print '<script type="text/javascript">
				jQuery(document).ready(function() {
					jQuery("#typestandardinvoice, #valuestandardinvoice").click(function() {
						jQuery("#radio_standard").prop("checked", true);
					});
					jQuery("#typedeposit, #valuedeposit").click(function() {
						jQuery("#radio_deposit").prop("checked", true);
					});
					jQuery("#typedeposit").change(function() {
						console.log("We change type of down payment");
						jQuery("#radio_deposit").prop("checked", true);
						setRadioForTypeOfInvoice();
					});
					jQuery("#radio_standard, #radio_deposit, #radio_replacement, #radio_creditnote, #radio_template").change(function() {
						setRadioForTypeOfInvoice();
					});
					function setRadioForTypeOfInvoice() {
						console.log("Change radio for type of invoice");
						if (jQuery("#radio_deposit").prop("checked") && (jQuery("#typedeposit").val() == \'amount\' || jQuery("#typedeposit").val() == \'variable\')) {
							jQuery("#checkforselects").prop("disabled", true);
							jQuery("#checkforselects").prop("checked", false);
							jQuery(".checkforselect").prop("disabled", true);
							jQuery(".checkforselect").prop("checked", false);
						} else {
							jQuery("#checkforselects").prop("disabled", false);
							jQuery("#checkforselects").prop("checked", true);
							jQuery(".checkforselect").prop("disabled", false);
							jQuery(".checkforselect").prop("checked", true);
						}
					}
				});
				</script>';

				print '<table class="nobordernopadding"><tr>';
				print '<td>';
				$tmp  = $tmp.'<label for="radio_deposit">'.$langs->trans("InvoiceDeposit").'</label>';
				// @phan-suppress-next-line PhanPluginSuspiciousParamOrder
				$desc = $form->textwithpicto($tmp, $langs->transnoentities("InvoiceDepositDesc"), 1, 'help', '', 0, 3, 'depositonsmartphone');
				print $desc;
				print '</td>';
				if (($origin == 'propal') || ($origin == 'commande')) {
					print '<td class="nowrap" style="padding-left: 15px">';
					$arraylist = array(
						'amount' => $langs->transnoentitiesnoconv('FixAmount', $langs->transnoentitiesnoconv('Deposit')),
						'variable' => $langs->transnoentitiesnoconv('VarAmountOneLine', $langs->transnoentitiesnoconv('Deposit')),
						'variablealllines' => $langs->transnoentitiesnoconv('VarAmountAllLines')
					);
					$typedeposit = GETPOST('typedeposit', 'aZ09');
					$valuedeposit = GETPOSTINT('valuedeposit');
					if (empty($typedeposit) && !empty($objectsrc->deposit_percent)) {
						$origin_payment_conditions_deposit_percent = getDictionaryValue('c_payment_term', 'deposit_percent', $objectsrc->cond_reglement_id);
						if (!empty($origin_payment_conditions_deposit_percent)) {
							$typedeposit = 'variable';
						}
					}
					if (empty($valuedeposit) && $typedeposit == 'variable' && !empty($objectsrc->deposit_percent)) {
						$valuedeposit = $objectsrc->deposit_percent;
					}
					print $form->selectarray('typedeposit', $arraylist, $typedeposit, 0, 0, 0, '', 1);
					print '</td>';
					print '<td class="nowrap" style="padding-left: 5px">';
					print '<span class="opacitymedium paddingleft">'.$langs->trans("AmountOrPercent").'</span><input type="text" id="valuedeposit" name="valuedeposit" class="width75 right" value="'.$valuedeposit.'"/>';
					print '</td>';
				}
				print '</tr></table>';

				print '</div></div>';
			}
		}

		if ($socid > 0) {
			if (getDolGlobalString('INVOICE_USE_SITUATION')) {
				// First situation invoice
				print '<div class="tagtr listofinvoicetype"><div class="tagtd listofinvoicetype">';
				$tmp = '<input id="radio_situation" type="radio" name="type" value="5"'.(GETPOST('type') == 5 ? ' checked' : '').'> ';
				$tmp  = $tmp.'<label for="radio_situation" >'.$langs->trans("InvoiceFirstSituationAsk").'</label>';
				// @phan-suppress-next-line PhanPluginSuspiciousParamOrder
				$desc = $form->textwithpicto($tmp, $langs->transnoentities("InvoiceFirstSituationDesc"), 1, 'help', '', 0, 3, 'firstsituationonsmartphone');
				print $desc;
				print '</div></div>';

				// Next situation invoice
				$opt = $form->selectSituationInvoices(GETPOSTINT('originid'), $socid);

				print '<div class="tagtr listofinvoicetype"><div class="tagtd listofinvoicetype">';
				$tmp = '<input type="radio" name="type" value="5"'.(GETPOST('type') == 5 && GETPOSTINT('originid') ? ' checked' : '');
				if ($opt == ('<option value ="0" selected>'.$langs->trans('NoSituations').'</option>') || (GETPOST('origin') && GETPOST('origin') != 'facture' && GETPOST('origin') != 'commande')) {
					$tmp .= ' disabled';
				}
				$tmp .= '> ';
				$text = $tmp.'<label>'.$langs->trans("InvoiceSituationAsk").'</label> ';
				$text .= '<select class="flat" id="situations" name="situations"';
				if ($opt == ('<option value ="0" selected>'.$langs->trans('NoSituations').'</option>') || (GETPOST('origin') && GETPOST('origin') != 'facture' && GETPOST('origin') != 'commande')) {
					$text .= ' disabled';
				}
				$text .= '>';
				$text .= $opt;
				$text .= '</select>';
				$desc = $form->textwithpicto($text, $langs->transnoentities("InvoiceSituationDesc"), 1, 'help', '', 0, 3);
				print $desc;
				print '</div></div>';
			}

			// Replacement
			if (!getDolGlobalString('INVOICE_DISABLE_REPLACEMENT')) {
				// Type de facture
				$facids = $facturestatic->list_replacable_invoices($soc->id);
				if ($facids < 0) {
					dol_print_error($db, $facturestatic->error, $facturestatic->errors);
					exit();
				}
				$options = "";
				if (is_array($facids)) {
					foreach ($facids as $facparam) {
						$options .= '<option value="'.$facparam ['id'].'"';
						if ($facparam['id'] == GETPOSTINT('fac_replacement')) {
							$options .= ' selected';
						}
						$options .= '>'.$facparam['ref'];
						$options .= ' ('.$facturestatic->LibStatut($facparam['paid'], $facparam['status'], 0, $facparam['alreadypaid']).')';
						$options .= '</option>';
					}
				}

				print '<!-- replacement line -->';
				print '<div class="tagtr listofinvoicetype"><div class="tagtd listofinvoicetype">';
				$tmp = '<input type="radio" name="type" id="radio_replacement" value="1"'.(GETPOST('type') == 1 ? ' checked' : '');
				if (!$options || $invoice_predefined->id > 0) {
					$tmp .= ' disabled';
				}
				$tmp .= '> ';
				print '<script type="text/javascript">
				jQuery(document).ready(function() {
					jQuery("#fac_replacement").change(function() {
						jQuery("#radio_replacement").prop("checked", true);
					});
				});
				</script>';
				$text = $tmp.'<label for="radio_replacement">'.$langs->trans("InvoiceReplacementAsk").'</label>';
				$text .= '<select class="flat" name="fac_replacement" id="fac_replacement"';
				if (!$options || $invoice_predefined->id > 0) {
					$text .= ' disabled';
				}
				$text .= '>';
				if ($options) {
					$text .= '<option value="-1">&nbsp;</option>';
					$text .= $options;
				} else {
					$text .= '<option value="-1">'.$langs->trans("NoReplacableInvoice").'</option>';
				}
				$text .= '</select>';
				$desc = $form->textwithpicto($text, $langs->transnoentities("InvoiceReplacementDesc"), 1, 'help', '', 0, 3);
				print $desc;
				print '</div></div>';
			}
		} else {
			if (getDolGlobalString('INVOICE_USE_SITUATION')) {
				print '<div class="tagtr listofinvoicetype"><div class="tagtd listofinvoicetype">';
				$tmp = '<input type="radio" name="type" id="radio_situation" value="0" disabled> ';
				$text = $tmp.'<label>'.$langs->trans("InvoiceSituationAsk").'</label> ';
				$text .= '<span class="opacitymedium">('.$langs->trans("YouMustCreateInvoiceFromThird").')</span> ';
				$desc = $form->textwithpicto($text, $langs->transnoentities("InvoiceFirstSituationDesc"), 1, 'help', 'nowraponall', 0, 3, 'firstsituationonsmartphone');
				print $desc;
				print '</div></div>';
			}

			print '<div class="tagtr listofinvoicetype"><div class="tagtd listofinvoicetype">';
			$tmp = '<input type="radio" name="type" id="radio_replacement" value="0" disabled> ';
			$text = $tmp.'<label for="radio_replacement" class="opacitymedium">'.$langs->trans("InvoiceReplacement").'</label> ';
			//$text .= '<span class="opacitymedium hideonsmartphone">('.$langs->trans("YouMustCreateInvoiceFromThird").')</span> ';
			$desc = $form->textwithpicto($text, $langs->transnoentities("InvoiceReplacementDesc").'<br><br>'.$langs->trans("YouMustCreateInvoiceFromThird"), 1, 'help', 'nowraponall', 0, 3, 'replacementonsmartphone');
			print $desc;
			print '</div></div>';
		}

		if (empty($origin)) {
			if ($socid > 0) {
				// Credit note
				if (!getDolGlobalString('INVOICE_DISABLE_CREDIT_NOTE')) {
					// Show link for credit note
					$facids = $facturestatic->list_qualified_avoir_invoices($soc->id);
					if ($facids < 0) {
						dol_print_error($db, $facturestatic->error, $facturestatic->errors);
						exit;
					}
					$optionsav = "";
					$newinvoice_static = new Facture($db);
					foreach ($facids as $key => $valarray) {
						$newinvoice_static->id = $key;
						$newinvoice_static->ref = $valarray ['ref'];
						$newinvoice_static->statut = $valarray ['status'];
						$newinvoice_static->status = $valarray ['status'];
						$newinvoice_static->type = $valarray ['type'];
						$newinvoice_static->paye = $valarray ['paye'];

						$optionsav .= '<option value="'.$key.'"';
						if ($key == GETPOST('fac_avoir')) {
							$optionsav .= ' selected';

							// pre-filled extra fields with selected credit note
							$newinvoice_static->fetch_optionals($key);
							$object->array_options = $newinvoice_static->array_options;
						}
						$optionsav .= '>';
						$optionsav .= $newinvoice_static->ref;
						$optionsav .= ' ('.$newinvoice_static->getLibStatut(1, $valarray ['paymentornot']).')';
						$optionsav .= '</option>';
					}

					print '<div class="tagtr listofinvoicetype"><div class="tagtd listofinvoicetype">';
					$tmp = '<input type="radio" id="radio_creditnote" name="type" value="2"'.(GETPOST('type') == 2 ? ' checked' : '');
					if ((!$optionsav && !getDolGlobalString('INVOICE_CREDIT_NOTE_STANDALONE')) || $invoice_predefined->id > 0) {
						$tmp .= ' disabled';
					}
					$tmp .= '> ';
					// Show credit note options only if we checked credit note and disable standard invoice if "create credit note" button is pressed
					print '<script type="text/javascript">
					jQuery(document).ready(function() {
						if (jQuery("#radio_creditnote").is(":checked"))
						{
							jQuery("#radio_standard").prop("disabled", true);
						} else {
							jQuery("#radio_standard").prop("disabled", false);
						}
						if (! jQuery("#radio_creditnote").is(":checked"))
						{
							jQuery("#credit_note_options").hide();
						}
						jQuery("#radio_creditnote").click(function() {
							jQuery("#credit_note_options").show();
						});
						jQuery("#radio_standard, #radio_replacement, #radio_deposit").click(function() {
							jQuery("#credit_note_options").hide();
						});
					});
					</script>';
					$text = '<label>'.$tmp.$langs->transnoentities("InvoiceAvoirAsk").'</label> ';
					$text .= '<select class="flat valignmiddle" name="fac_avoir" id="fac_avoir"';
					if (!$optionsav || $invoice_predefined->id > 0) {
						$text .= ' disabled';
					}
					$text .= '>';
					if ($optionsav) {
						$text .= '<option value="-1"></option>';
						$text .= $optionsav;
					} else {
						$text .= '<option value="-1">'.$langs->trans("NoInvoiceToCorrect").'</option>';
					}
					$text .= '</select>';
					$desc = $form->textwithpicto($text, $langs->transnoentities("InvoiceAvoirDesc"), 1, 'help', '', 0, 3);
					print $desc;

					print '<div id="credit_note_options" class="clearboth paddingtop marginbottomonly">';
					print '&nbsp;&nbsp;&nbsp; <input type="checkbox" name="invoiceAvoirWithLines" id="invoiceAvoirWithLines" value="1" onclick="$(\'#credit_note_options input[type=checkbox]\').not(this).prop(\'checked\', false);" '.(GETPOSTINT('invoiceAvoirWithLines') > 0 ? 'checked' : '').' /> <label for="invoiceAvoirWithLines">'.$langs->trans('invoiceAvoirWithLines')."</label>";
					print '<br>&nbsp;&nbsp;&nbsp; <input type="checkbox" name="invoiceAvoirWithPaymentRestAmount" id="invoiceAvoirWithPaymentRestAmount" value="1" onclick="$(\'#credit_note_options input[type=checkbox]\').not(this).prop(\'checked\', false);" '.(GETPOSTINT('invoiceAvoirWithPaymentRestAmount') > 0 ? 'checked' : '').' /> <label for="invoiceAvoirWithPaymentRestAmount">'.$langs->trans('invoiceAvoirWithPaymentRestAmount')."</label>";
					print '</div>';

					print '</div></div>';
				}
			} else {
				print '<div class="tagtr listofinvoicetype"><div class="tagtd listofinvoicetype">';
				if (!getDolGlobalString('INVOICE_CREDIT_NOTE_STANDALONE')) {
					$tmp = '<input type="radio" name="type" id="radio_creditnote" value="0" disabled> ';
				} else {
					$tmp = '<input type="radio" name="type" id="radio_creditnote" value="2" > ';
				}
				$text = $tmp.'<label class="opacitymedium" for="radio_creditnote">'.$langs->trans("InvoiceAvoir").'</label> ';
				//$text .= '<span class="opacitymedium hideonsmartphone">('.$langs->trans("YouMustCreateInvoiceFromThird").')</span> ';
				$desc = $form->textwithpicto($text, $langs->transnoentities("InvoiceAvoirDesc").'<br><br>'.$langs->trans("CreateCreditNoteWhenClientInvoiceExists"), 1, 'help', '', 0, 3, 'creditnoteonsmartphone');
				print $desc;
				print '</div></div>'."\n";
			}
		}

		// Template invoice
		print '<div class="tagtr listofinvoicetype"><div class="tagtd listofinvoicetype">';
		$tmp = '<input type="radio" name="type" id="radio_template" value="0" disabled> ';
		$text = $tmp.'<label class="opacitymedium" for="radio_template">'.$langs->trans("RepeatableInvoice").'</label> ';
		$desc = $form->textwithpicto($text, $langs->transnoentities("YouMustCreateStandardInvoiceFirstDesc"), 1, 'help', '', 0, 3, 'templateonsmartphone');
		print $desc;
		print '</div></div>';

		print '</div>';


		if (getDolGlobalString('INVOICE_USE_DEFAULT_DOCUMENT')) { // Hidden conf
			// Add auto select default document model
			$listtType = array(Facture::TYPE_STANDARD, Facture::TYPE_REPLACEMENT, Facture::TYPE_CREDIT_NOTE, Facture::TYPE_DEPOSIT, Facture::TYPE_SITUATION);
			$jsListType = '';
			foreach ($listtType as $type) {
				$thisTypeConfName = 'FACTURE_ADDON_PDF_'.$type;
				$current = getDolGlobalString($thisTypeConfName, getDolGlobalString('FACTURE_ADDON_PDF'));
				$jsListType .= (!empty($jsListType) ? ',' : '').'"'.$type.'":"'.$current.'"';
			}

			print '<script type="text/javascript">
					$(document).ready(function() {
						var listType = {'.$jsListType.'};
						$("[name=\'type\'").change(function() {
							console.log("change name=type");
							if ($( this ).prop("checked"))
							{
								if(($( this ).val() in listType))
								{
									$("#model").val(listType[$( this ).val()]);
								}
								else
								{
									$("#model").val("' . getDolGlobalString('FACTURE_ADDON_PDF').'");
								}
							}
						});
					});
					</script>';
		}


		print '</td></tr>';

		// Invoice Subtype
		if (getDolGlobalInt('INVOICE_SUBTYPE_ENABLED')) {
			print '<tr><td class="fieldrequired">'.$langs->trans('InvoiceSubtype').'</td><td colspan="2">';
			print $form->getSelectInvoiceSubtype(GETPOST('subtype'), 'subtype', 1, 0, '');
			print '</td></tr>';
		}

		// Discounts for the known third party
		if ($socid > 0) {
			print '<tr><td>'.$langs->trans('DiscountStillRemaining').'</td><td colspan="2">';

			$thirdparty = $soc;	// used by object_discounts.tpl.php
			$discount_type = 0;	// used by object_discounts.tpl.php
			$backtopage = $_SERVER["PHP_SELF"].'?socid='.$thirdparty->id.'&action='.$action.'&origin='.urlencode((string) (GETPOST('origin'))).'&originid='.urlencode((string) (GETPOSTINT('originid')));
			include DOL_DOCUMENT_ROOT.'/core/tpl/object_discounts.tpl.php';

			print '</td></tr>';
		}

		$newdateinvoice = dol_mktime(0, 0, 0, GETPOSTINT('remonth'), GETPOSTINT('reday'), GETPOSTINT('reyear'), 'tzserver');
		$date_pointoftax = dol_mktime(0, 0, 0, GETPOSTINT('date_pointoftaxmonth'), GETPOSTINT('date_pointoftaxday'), GETPOSTINT('date_pointoftaxyear'), 'tzserver');

		// Date invoice
		print '<tr><td class="fieldrequired">'.$langs->trans('DateInvoice').'</td><td colspan="2">';
		print img_picto('', 'action', 'class="pictofixedwidth"');
		print $form->selectDate($newdateinvoice ? $newdateinvoice : $dateinvoice, '', 0, 0, 0, "add", 1, 1);
		print '</td></tr>';

		// Date point of tax
		if (getDolGlobalString('INVOICE_POINTOFTAX_DATE')) {
			print '<tr><td class="fieldrequired">'.$langs->trans('DatePointOfTax').'</td><td colspan="2">';
			print img_picto('', 'action', 'class="pictofixedwidth"');
			print $form->selectDate($date_pointoftax ? $date_pointoftax : -1, 'date_pointoftax', 0, 0, 0, "add", 1, 1);
			print '</td></tr>';
		}

		// Payment term
		print '<tr><td class="nowrap fieldrequired">'.$langs->trans('PaymentConditionsShort').'</td><td colspan="2">';
		print img_picto('', 'payment', 'class="pictofixedwidth"');
		print $form->getSelectConditionsPaiements($cond_reglement_id, 'cond_reglement_id', -1, 1, 0, 'maxwidth500 widthcentpercentminusx');
		print '</td></tr>';

		// Warranty
		if (getDolGlobalString('INVOICE_USE_RETAINED_WARRANTY')) {
			$rwStyle = 'display:none;';
			if (in_array(GETPOSTINT('type'), $retainedWarrantyInvoiceAvailableType)) {
				$rwStyle = '';
			}

			$retained_warranty = GETPOSTINT('retained_warranty');
			if (empty($retained_warranty)) {
				if (!empty($objectsrc->retained_warranty)) { // use previous situation value
					$retained_warranty = $objectsrc->retained_warranty;
				}
			}
			$retained_warranty_js_default = !empty($retained_warranty) ? $retained_warranty : getDolGlobalString('INVOICE_SITUATION_DEFAULT_RETAINED_WARRANTY_PERCENT');

			print '<tr class="retained-warranty-line" style="'.$rwStyle.'" ><td class="nowrap">'.$langs->trans('RetainedWarranty').'</td><td colspan="2">';
			print '<input id="new-situation-invoice-retained-warranty" name="retained_warranty" type="number" value="'.$retained_warranty.'" step="0.01" min="0" max="100" />%';

			// Retained warranty payment term
			print '<tr class="retained-warranty-line" style="'.$rwStyle.'" ><td class="nowrap">'.$langs->trans('PaymentConditionsShortRetainedWarranty').'</td><td colspan="2">';
			$retained_warranty_fk_cond_reglement = GETPOSTINT('retained_warranty_fk_cond_reglement');
			if (empty($retained_warranty_fk_cond_reglement)) {
				$retained_warranty_fk_cond_reglement = getDolGlobalString('INVOICE_SITUATION_DEFAULT_RETAINED_WARRANTY_COND_ID');
				if (!empty($objectsrc->retained_warranty_fk_cond_reglement)) { // use previous situation value
					$retained_warranty_fk_cond_reglement = $objectsrc->retained_warranty_fk_cond_reglement;
				} else {
					$retained_warranty_fk_cond_reglement = getDolGlobalString('INVOICE_SITUATION_DEFAULT_RETAINED_WARRANTY_COND_ID');
				}
			}
			print $form->getSelectConditionsPaiements($retained_warranty_fk_cond_reglement, 'retained_warranty_fk_cond_reglement', -1, 1);
			print '</td></tr>';

			print '<script type="text/javascript">
			$(document).ready(function() {
			$("[name=\'type\']").change(function() {
					if($( this ).prop("checked") && $.inArray($( this ).val(), '.json_encode($retainedWarrantyInvoiceAvailableType).' ) !== -1)
					{
						$(".retained-warranty-line").show();
						$("#new-situation-invoice-retained-warranty").val("'.(float) $retained_warranty_js_default.'");
					}
					else{
						$(".retained-warranty-line").hide();
						$("#new-situation-invoice-retained-warranty").val("");
					}
				});

				$("[name=\'type\']:checked").trigger("change");
			});
			</script>';
		}

		// Payment mode
		print '<tr><td>'.$langs->trans('PaymentMode').'</td><td colspan="2">';
		print img_picto('', 'bank', 'class="pictofixedwidth"');
		print $form->select_types_paiements($mode_reglement_id, 'mode_reglement_id', 'CRDT', 0, 1, 0, 0, 1, 'maxwidth200 widthcentpercentminusx', 1);
		print '</td></tr>';

		// Bank Account
		if (isModEnabled("bank")) {
			print '<tr><td>'.$langs->trans('BankAccount').'</td><td colspan="2">';
			print img_picto('', 'bank_account', 'class="pictofixedwidth"');
			print $form->select_comptes($fk_account, 'fk_account', 0, '', 1, '', 0, 'maxwidth200 widthcentpercentminusx', 1);
			print '</td></tr>';
		}

		// Project
		if (isModEnabled('project')) {
			$langs->load('projects');
			print '<tr><td>'.$langs->trans('Project').'</td><td colspan="2">';
			print img_picto('', 'project', 'class="pictofixedwidth"').$formproject->select_projects(($socid > 0 ? $socid : -1), $projectid, 'projectid', 0, 0, 1, 1, 0, 0, 0, '', 1, 0, 'maxwidth500 widthcentpercentminusxx');
			print ' <a href="'.DOL_URL_ROOT.'/projet/card.php?socid='.$soc->id.'&action=create&status=1&backtopage='.urlencode($_SERVER["PHP_SELF"].'?action=create&socid='.$soc->id.($fac_rec ? '&fac_rec='.$fac_rec : '')).'"><span class="fa fa-plus-circle valignmiddle" title="'.$langs->trans("AddProject").'"></span></a>';
			print '</td></tr>';
		}

		// Incoterms
		if (isModEnabled('incoterm')) {
			print '<tr>';
			print '<td><label for="incoterm_id">'.$form->textwithpicto($langs->trans("IncotermLabel"), !empty($objectsrc->label_incoterms) ? $objectsrc->label_incoterms : '', 1).'</label></td>';
			print '<td colspan="2" class="maxwidthonsmartphone">';
			$incoterm_id = GETPOST('incoterm_id');
			$location_incoterms = GETPOST('location_incoterms');
			if (empty($incoterm_id)) {
				$incoterm_id = (!empty($objectsrc->fk_incoterms) ? $objectsrc->fk_incoterms : $soc->fk_incoterms);
				$location_incoterms = (!empty($objectsrc->location_incoterms) ? $objectsrc->location_incoterms : $soc->location_incoterms);
			}
			print img_picto('', 'incoterm', 'class="pictofixedwidth"');
			print $form->select_incoterms($incoterm_id, $location_incoterms);
			print '</td></tr>';
		}

		// Other attributes
		$parameters = array('objectsrc' => !empty($objectsrc) ? $objectsrc : 0, 'colspan' => ' colspan="2"', 'cols' => '2', 'socid' => $socid);
		$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		print $hookmanager->resPrint;
		if (empty($reshook)) {
			if (getDolGlobalString('THIRDPARTY_PROPAGATE_EXTRAFIELDS_TO_INVOICE') && !empty($soc->id)) {
				// copy from thirdparty
				$tpExtrafields = new ExtraFields($db);
				$tpExtrafieldLabels = $tpExtrafields->fetch_name_optionals_label($soc->table_element);
				if ($soc->fetch_optionals() > 0) {
					$object->array_options = array_merge($object->array_options, $soc->array_options);
				}
			}

			print $object->showOptionals($extrafields, 'create', $parameters);
		}

		// Template to use by default
		print '<tr><td>'.$langs->trans('Model').'</td>';
		print '<td colspan="2">';
		print img_picto('', 'pdf', 'class="pictofixedwidth"');
		include_once DOL_DOCUMENT_ROOT.'/core/modules/facture/modules_facture.php';
		$liste = ModelePDFFactures::liste_modeles($db);
		if (getDolGlobalString('INVOICE_USE_DEFAULT_DOCUMENT')) {
			$type = GETPOSTISSET('type') ? GETPOSTINT('type') : $object->type;
			// Hidden conf
			$paramkey = 'FACTURE_ADDON_PDF_'.$type;
			$preselected = getDolGlobalString($paramkey, getDolGlobalString('FACTURE_ADDON_PDF'));
		} else {
			$preselected = getDolGlobalString('FACTURE_ADDON_PDF');
		}
		print $form->selectarray('model', $liste, $preselected, 0, 0, 0, '', 0, 0, 0, '', 'maxwidth200 widthcentpercentminusx', 1);
		print "</td></tr>";

		// Multicurrency
		if (isModEnabled('multicurrency')) {
			print '<tr>';
			print '<td>'.$form->editfieldkey('Currency', 'multicurrency_code', '', $object, 0).'</td>';
			print '<td colspan="2" class="maxwidthonsmartphone">';
			print img_picto('', 'currency', 'class="pictofixedwidth"');
			print $form->selectMultiCurrency(((GETPOSTISSET('multicurrency_code') && !GETPOST('changecompany')) ? GETPOST('multicurrency_code') : $currency_code), 'multicurrency_code', 0, '', false, 'maxwidth100 widthcentpercentminusx');
			print '</td></tr>';
		}

		// Help of substitution key
		$htmltext = '';
		if (GETPOSTINT('fac_rec') > 0) {
			$dateexample = ($newdateinvoice ? $newdateinvoice : $dateinvoice);
			if (empty($dateexample)) {
				$dateexample = dol_now();
			}
			$substitutionarray = array(
				'__TOTAL_HT__' => $langs->trans("AmountHT").' ('.$langs->trans("Example").': '.price($exampletemplateinvoice->total_ht).')',
				'__TOTAL_TTC__' =>  $langs->trans("AmountTTC").' ('.$langs->trans("Example").': '.price($exampletemplateinvoice->total_ttc).')',
				'__INVOICE_PREVIOUS_MONTH__' => $langs->trans("PreviousMonthOfInvoice").' ('.$langs->trans("Example").': '.dol_print_date(dol_time_plus_duree($dateexample, -1, 'm'), '%m').')',
				'__INVOICE_MONTH__' =>  $langs->trans("MonthOfInvoice").' ('.$langs->trans("Example").': '.dol_print_date($dateexample, '%m').')',
				'__INVOICE_NEXT_MONTH__' => $langs->trans("NextMonthOfInvoice").' ('.$langs->trans("Example").': '.dol_print_date(dol_time_plus_duree($dateexample, 1, 'm'), '%m').')',
				'__INVOICE_PREVIOUS_MONTH_TEXT__' => $langs->trans("TextPreviousMonthOfInvoice").' ('.$langs->trans("Example").': '.dol_print_date(dol_time_plus_duree($dateexample, -1, 'm'), '%B').')',
				'__INVOICE_MONTH_TEXT__' =>  $langs->trans("TextMonthOfInvoice").' ('.$langs->trans("Example").': '.dol_print_date($dateexample, '%B').')',
				'__INVOICE_NEXT_MONTH_TEXT__' => $langs->trans("TextNextMonthOfInvoice").' ('.$langs->trans("Example").': '.dol_print_date(dol_time_plus_duree($dateexample, 1, 'm'), '%B').')',
				'__INVOICE_PREVIOUS_YEAR__' => $langs->trans("PreviousYearOfInvoice").' ('.$langs->trans("Example").': '.dol_print_date(dol_time_plus_duree($dateexample, -1, 'y'), '%Y').')',
				'__INVOICE_YEAR__' =>  $langs->trans("YearOfInvoice").' ('.$langs->trans("Example").': '.dol_print_date($dateexample, '%Y').')',
				'__INVOICE_NEXT_YEAR__' => $langs->trans("NextYearOfInvoice").' ('.$langs->trans("Example").': '.dol_print_date(dol_time_plus_duree($dateexample, 1, 'y'), '%Y').')'
			);

			$htmltext = '<i>'.$langs->trans("FollowingConstantsWillBeSubstituted").':<br>';
			foreach ($substitutionarray as $key => $val) {
				$htmltext .= $key.' = '.$langs->trans($val).'<br>';
			}
			$htmltext .= '</i>';
		}

		// Public note
		print '<tr>';
		print '<td class="tdtop">';
		print $form->textwithpicto($langs->trans('NotePublic'), $htmltext);
		print '</td>';
		print '<td valign="top" colspan="2">';
		$doleditor = new DolEditor('note_public', $note_public, '', 80, 'dolibarr_notes', 'In', 0, false, !getDolGlobalString('FCKEDITOR_ENABLE_NOTE_PUBLIC') ? 0 : 1, ROWS_3, '90%');
		print $doleditor->Create(1);

		// Private note
		if (empty($user->socid)) {
			print '<tr>';
			print '<td class="tdtop">';
			print $form->textwithpicto($langs->trans('NotePrivate'), $htmltext);
			print '</td>';
			print '<td valign="top" colspan="2">';
			$doleditor = new DolEditor('note_private', $note_private, '', 80, 'dolibarr_notes', 'In', 0, false, !getDolGlobalString('FCKEDITOR_ENABLE_NOTE_PRIVATE') ? 0 : 1, ROWS_3, '90%');
			print $doleditor->Create(1);
			// print '<textarea name="note_private" wrap="soft" cols="70" rows="'.ROWS_3.'">'.$note_private.'.</textarea>
			print '</td></tr>';
		}

		// Lines from source (TODO Show them also when creating invoice from template invoice)
		if (!empty($origin) && !empty($originid) && is_object($objectsrc)) {
			$langs->loadLangs(array('orders', 'propal'));

			// TODO for compatibility
			if ($origin == 'contrat') {
				// Calcul contrat->price (HT), contrat->total (TTC), contrat->tva
				$objectsrc->update_price(1, 'auto', 1);
			}

			print "\n<!-- Show ref of origin ".$classname." -->\n";
			print '<input type="hidden" name="amount"   value="'.$objectsrc->total_ht.'">'."\n";
			print '<input type="hidden" name="total"    value="'.$objectsrc->total_ttc.'">'."\n";
			print '<input type="hidden" name="tva"      value="'.$objectsrc->total_tva.'">'."\n";
			// The commented lines below are fields already added as hidden parameters before
			//print '<input type="hidden" name="origin"   value="'.$objectsrc->element.'">';
			//print '<input type="hidden" name="originid" value="'.$objectsrc->id.'">';

			switch (get_class($objectsrc)) {
				case 'Propal':
					$newclassname = 'CommercialProposal';
					break;
				case 'Commande':
					$newclassname = 'Order';
					break;
				case 'Expedition':
					$newclassname = 'Sending';
					break;
				case 'Contrat':
					$newclassname = 'Contract';
					break;
				case 'Fichinter':
					$newclassname = 'Intervention';
					break;
				default:
					$newclassname = get_class($objectsrc);
			}

			// Ref of origin
			print '<tr><td>'.$langs->trans($newclassname).'</td>';
			print '<td colspan="2">';
			print $objectsrc->getNomUrl(1);
			// We check if Origin document (id and type is known) has already at least one invoice attached to it
			$objectsrc->fetchObjectLinked($originid, $origin, null, 'facture');
			if (isset($objectsrc->linkedObjects['facture']) && is_array($objectsrc->linkedObjects['facture']) && count($objectsrc->linkedObjects['facture']) >= 1) {
				setEventMessages('WarningBillExist', null, 'warnings');
				echo ' - '.$langs->trans('LatestRelatedBill').' '.end($objectsrc->linkedObjects['facture'])->getNomUrl(1);
			}
			echo '</td></tr>';
			print '<tr><td>'.$langs->trans('AmountHT').'</td><td colspan="2">'.price($objectsrc->total_ht).'</td></tr>';
			print '<tr><td>'.$langs->trans('AmountVAT').'</td><td colspan="2">'.price($objectsrc->total_tva)."</td></tr>";
			if ($mysoc->localtax1_assuj == "1" || $objectsrc->total_localtax1 != 0) {		// Localtax1
				print '<tr><td>'.$langs->transcountry("AmountLT1", $mysoc->country_code).'</td><td colspan="2">'.price($objectsrc->total_localtax1)."</td></tr>";
			}

			if ($mysoc->localtax2_assuj == "1" || $objectsrc->total_localtax2 != 0) {		// Localtax2
				print '<tr><td>'.$langs->transcountry("AmountLT2", $mysoc->country_code).'</td><td colspan="2">'.price($objectsrc->total_localtax2)."</td></tr>";
			}
			print '<tr><td>'.$langs->trans('AmountTTC').'</td><td colspan="2">'.price($objectsrc->total_ttc)."</td></tr>";

			if (isModEnabled('multicurrency')) {
				print '<tr><td>'.$langs->trans('MulticurrencyAmountHT').'</td><td colspan="2">'.price($objectsrc->multicurrency_total_ht).'</td></tr>';
				print '<tr><td>'.$langs->trans('MulticurrencyAmountVAT').'</td><td colspan="2">'.price($objectsrc->multicurrency_total_tva)."</td></tr>";
				print '<tr><td>'.$langs->trans('MulticurrencyAmountTTC').'</td><td colspan="2">'.price($objectsrc->multicurrency_total_ttc)."</td></tr>";
			}
		}

		print "</table>\n";
	}
	print dol_get_fiche_end();

	print $form->buttonsSaveCancel("CreateDraft");

	// Show origin lines
	if (!empty($origin) && !empty($originid) && is_object($objectsrc)) {
		print '<br>';

		$title = $langs->trans('ProductsAndServices');
		print load_fiche_titre($title);

		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder centpercent">';

		$objectsrc->printOriginLinesList('', $selectedLines);

		print '</table>';
		print '</div>';
	}

	print "</form>\n";
} elseif ($id > 0 || !empty($ref)) {
	if (empty($object->id)) {
		$langs->load('errors');
		echo '<div class="error">'.$langs->trans("ErrorRecordNotFound").'</div>';
		llxFooter();
		exit;
	}

	/*
	 * Show object in view mode
	 */

	$result = $object->fetch($id, $ref);
	if ($result <= 0) {
		dol_print_error($db, $object->error, $object->errors);
		exit();
	}

	// fetch optionals attributes and labels
	$extrafields->fetch_name_optionals_label($object->table_element);

	if ($user->socid > 0 && $user->socid != $object->socid) {
		accessforbidden('', 0, 1);
	}

	$result = $object->fetch_thirdparty();

	$result = $soc->fetch($object->socid);
	if ($result < 0) {
		dol_print_error($db);
	}
	$selleruserevenustamp = $mysoc->useRevenueStamp();

	$totalpaid = $object->getSommePaiement();
	$totalcreditnotes = $object->getSumCreditNotesUsed();
	$totaldeposits = $object->getSumDepositsUsed();
	//print "totalpaid=".$totalpaid." totalcreditnotes=".$totalcreditnotes." totaldeposts=".$totaldeposits."
	// selleruserrevenuestamp=".$selleruserevenustamp;

	// We can also use bcadd to avoid pb with floating points
	// For example print 239.2 - 229.3 - 9.9; does not return 0.
	$resteapayer = price2num($object->total_ttc - $totalpaid - $totalcreditnotes - $totaldeposits, 'MT');

	// Multicurrency
	if (isModEnabled('multicurrency')) {
		$multicurrency_totalpaid = $object->getSommePaiement(1);
		$multicurrency_totalcreditnotes = $object->getSumCreditNotesUsed(1);
		$multicurrency_totaldeposits = $object->getSumDepositsUsed(1);
		$multicurrency_resteapayer = price2num($object->multicurrency_total_ttc - $multicurrency_totalpaid - $multicurrency_totalcreditnotes - $multicurrency_totaldeposits, 'MT');
		// Code to fix case of corrupted data
		// TODO We should not need this. Also data comes from a not reliable value of $object->multicurrency_total_ttc that may be wrong if it was
		// calculated by summing lines that were in a currency for some of them and into another for others (lines from discount/down payment into another currency for example)
		if ($resteapayer == 0 && $multicurrency_resteapayer != 0 && $object->multicurrency_code != $conf->currency) {
			$resteapayer = price2num((float) $multicurrency_resteapayer / $object->multicurrency_tx, 'MT');
		}
	}

	if ($object->paye) {
		$resteapayer = 0;
	}
	$resteapayeraffiche = $resteapayer;

	if (getDolGlobalString('FACTURE_DEPOSITS_ARE_JUST_PAYMENTS')) {	// Never use this
		$filterabsolutediscount = "fk_facture_source IS NULL"; // If we want deposit to be subtracted to payments only and not to total of final invoice
		$filtercreditnote = "fk_facture_source IS NOT NULL"; // If we want deposit to be subtracted to payments only and not to total of final invoice
	} else {
		$filterabsolutediscount = "fk_facture_source IS NULL OR (description LIKE '(DEPOSIT)%' AND description NOT LIKE '(EXCESS RECEIVED)%')";
		$filtercreditnote = "fk_facture_source IS NOT NULL AND (description NOT LIKE '(DEPOSIT)%' OR description LIKE '(EXCESS RECEIVED)%')";
	}

	$absolute_discount = $soc->getAvailableDiscounts(null, $filterabsolutediscount);
	$absolute_creditnote = $soc->getAvailableDiscounts(null, $filtercreditnote);
	$absolute_discount = price2num($absolute_discount, 'MT');
	$absolute_creditnote = price2num($absolute_creditnote, 'MT');

	$author = new User($db);
	if ($object->user_creation_id) {
		$author->fetch($object->user_creation_id);
	}

	$objectidnext = $object->getIdReplacingInvoice();

	$head = facture_prepare_head($object);

	print dol_get_fiche_head($head, 'compta', $langs->trans('InvoiceCustomer'), -1, 'bill');

	$formconfirm = '';

	// Confirmation of the conversion of the credit into a reduction
	if ($action == 'converttoreduc') {
		if ($object->type == Facture::TYPE_STANDARD || $object->type == Facture::TYPE_SITUATION) {
			$type_fac = 'ExcessReceived';
		} elseif ($object->type == Facture::TYPE_CREDIT_NOTE) {
			$type_fac = 'CreditNote';
		} elseif ($object->type == Facture::TYPE_DEPOSIT) {
			$type_fac = 'Deposit';
		}
		$text = $langs->trans('ConfirmConvertToReduc', strtolower($langs->transnoentities($type_fac)));
		$text .= '<br>'.$langs->trans('ConfirmConvertToReduc2');
		$formconfirm = $form->formconfirm($_SERVER['PHP_SELF'].'?facid='.$object->id, $langs->trans('ConvertToReduc'), $text, 'confirm_converttoreduc', '', "yes", 2);
	}

	// Confirmation to delete invoice
	if ($action == 'delete') {
		$text = $langs->trans('ConfirmDeleteBill', $object->ref);
		$formquestion = array();

		if ($object->type != Facture::TYPE_DEPOSIT && getDolGlobalString('STOCK_CALCULATE_ON_BILL') && $object->status >= 1) {
			$qualified_for_stock_change = 0;
			if (!getDolGlobalString('STOCK_SUPPORTS_SERVICES')) {
				$qualified_for_stock_change = $object->hasProductsOrServices(2);
			} else {
				$qualified_for_stock_change = $object->hasProductsOrServices(1);
			}

			if ($qualified_for_stock_change) {
				$langs->load("stocks");
				require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
				$formproduct = new FormProduct($db);
				$label = $object->type == Facture::TYPE_CREDIT_NOTE ? $langs->trans("SelectWarehouseForStockDecrease") : $langs->trans("SelectWarehouseForStockIncrease");
				$forcecombo = 0;
				if ($conf->browser->name == 'ie') {
					$forcecombo = 1; // There is a bug in IE10 that make combo inside popup crazy
				}
				$formquestion = array(
					// 'text' => $langs->trans("ConfirmClone"),
					// array('type' => 'checkbox', 'name' => 'clone_content', 'label' => $langs->trans("CloneMainAttributes"), 'value' => 1),
					// array('type' => 'checkbox', 'name' => 'update_prices', 'label' => $langs->trans("PuttingPricesUpToDate"), 'value' => 1),
					array('type' => 'other', 'name' => 'idwarehouse', 'label' => $label, 'value' => $formproduct->selectWarehouses(GETPOST('idwarehouse') ? GETPOST('idwarehouse') : 'ifone', 'idwarehouse', '', 1, 0, 0, $langs->trans("NoStockAction"), 0, $forcecombo))
				);
				$formconfirm = $form->formconfirm($_SERVER['PHP_SELF'].'?facid='.$object->id, $langs->trans('DeleteBill'), $text, 'confirm_delete', $formquestion, "yes", 1);
			} else {
				$formconfirm = $form->formconfirm($_SERVER['PHP_SELF'].'?facid='.$object->id, $langs->trans('DeleteBill'), $text, 'confirm_delete', '', 'no', 1);
			}
		} else {
			$formconfirm = $form->formconfirm($_SERVER['PHP_SELF'].'?facid='.$object->id, $langs->trans('DeleteBill'), $text, 'confirm_delete', '', 'no', 1);
		}
	}

	// Confirmation to remove invoice from cycle
	if ($action == 'situationout') {
		$text = $langs->trans('ConfirmRemoveSituationFromCycle', $object->ref);
		$label = $langs->trans("ConfirmOuting");
		$formquestion = array();
		// remove situation from cycle
		if (in_array($object->status, array(Facture::STATUS_CLOSED, Facture::STATUS_VALIDATED))
			&& $usercancreate
			&& !$objectidnext
			&& $object->is_last_in_cycle()
			&& $usercanunvalidate
		) {
			$formconfirm = $form->formconfirm($_SERVER['PHP_SELF'].'?facid='.$object->id, $label, $text, 'confirm_situationout', $formquestion, "yes", 1);
		}
	}

	// Confirmation of validation
	if ($action == 'valid') {
		// we check object has a draft number
		$objectref = substr($object->ref, 1, 4);
		if ($objectref == 'PROV') {
			$savdate = $object->date;
			if (getDolGlobalString('FAC_FORCE_DATE_VALIDATION')) {
				$object->date = dol_now();
				$object->date_lim_reglement = $object->calculate_date_lim_reglement();
			}
			$numref = $object->getNextNumRef($soc);
			// $object->date=$savdate;
		} else {
			$numref = $object->ref;
		}

		$text = $langs->trans('ConfirmValidateBill', $numref);
		if (isModEnabled('notification')) {
			require_once DOL_DOCUMENT_ROOT.'/core/class/notify.class.php';
			$notify = new Notify($db);
			$text .= '<br>';
			$text .= $notify->confirmMessage('BILL_VALIDATE', $object->socid, $object);
		}
		$formquestion = array();

		if ($object->type != Facture::TYPE_DEPOSIT && getDolGlobalString('STOCK_CALCULATE_ON_BILL')) {
			$qualified_for_stock_change = 0;
			if (!getDolGlobalString('STOCK_SUPPORTS_SERVICES')) {
				$qualified_for_stock_change = $object->hasProductsOrServices(2);
			} else {
				$qualified_for_stock_change = $object->hasProductsOrServices(1);
			}

			if ($qualified_for_stock_change) {
				$langs->load("stocks");
				require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
				require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
				$formproduct = new FormProduct($db);
				$warehouse = new Entrepot($db);
				$warehouse_array = $warehouse->list_array();
				if (count($warehouse_array) == 1) {
					$label = $object->type == Facture::TYPE_CREDIT_NOTE ? $langs->trans("WarehouseForStockIncrease", current($warehouse_array)) : $langs->trans("WarehouseForStockDecrease", current($warehouse_array));
					$value = '<input type="hidden" id="idwarehouse" name="idwarehouse" value="'.key($warehouse_array).'">';
				} else {
					$label = $object->type == Facture::TYPE_CREDIT_NOTE ? $langs->trans("SelectWarehouseForStockIncrease") : $langs->trans("SelectWarehouseForStockDecrease");
					$value = $formproduct->selectWarehouses(GETPOST('idwarehouse') ? GETPOST('idwarehouse') : 'ifone', 'idwarehouse', '', 1);
				}
				$formquestion = array(
									// 'text' => $langs->trans("ConfirmClone"),
									// array('type' => 'checkbox', 'name' => 'clone_content', 'label' => $langs->trans("CloneMainAttributes"), 'value' =>
									// 1),
									// array('type' => 'checkbox', 'name' => 'update_prices', 'label' => $langs->trans("PuttingPricesUpToDate"), 'value'
									// => 1),
									array('type' => 'other', 'name' => 'idwarehouse', 'label' => $label, 'value' => $value));
			}
		}
		if ($object->type != Facture::TYPE_CREDIT_NOTE && $object->total_ttc < 0) { 		// Can happen only if getDolGlobalString('FACTURE_ENABLE_NEGATIVE') is on
			$text .= '<br>'.img_warning().' '.$langs->trans("ErrorInvoiceOfThisTypeMustBePositive");
		}

		// mandatoryPeriod
		$nbMandated = 0;
		foreach ($object->lines as $line) {
			$res = $line->fetch_product();
			if ($res  > 0) {
				if ($line->product->isService() && $line->product->isMandatoryPeriod() && (empty($line->date_start) || empty($line->date_end))) {
					$nbMandated++;
					break;
				}
			}
		}
		if ($nbMandated > 0) {
			if (getDolGlobalString('SERVICE_STRICT_MANDATORY_PERIOD')) {
				setEventMessages($langs->trans("mandatoryPeriodNeedTobeSetMsgValidate"), null, 'errors');
				$error++;
			} else {
				$text .= '<div><span class="clearboth nowraponall warning">'.img_warning().$langs->trans("mandatoryPeriodNeedTobeSetMsgValidate").'</span></div>';
			}
		}

		if (!$error) {
			$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?facid='.$object->id, $langs->trans('ValidateBill'), $text, 'confirm_valid', $formquestion, (($object->type != Facture::TYPE_CREDIT_NOTE && $object->total_ttc < 0) ? "no" : "yes"), 2, 240);
		}
	}

	// Confirm back to draft status
	if ($action == 'modif') {
		$text = $langs->trans('ConfirmUnvalidateBill', $object->ref);
		$formquestion = array();

		if ($object->type != Facture::TYPE_DEPOSIT && getDolGlobalString('STOCK_CALCULATE_ON_BILL')) {
			$qualified_for_stock_change = 0;
			if (!getDolGlobalString('STOCK_SUPPORTS_SERVICES')) {
				$qualified_for_stock_change = $object->hasProductsOrServices(2);
			} else {
				$qualified_for_stock_change = $object->hasProductsOrServices(1);
			}

			if ($qualified_for_stock_change) {
				$langs->load("stocks");
				require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
				require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
				$formproduct = new FormProduct($db);
				$warehouse = new Entrepot($db);
				$warehouse_array = $warehouse->list_array();
				if (count($warehouse_array) == 1) {
					$label = $object->type == Facture::TYPE_CREDIT_NOTE ? $langs->trans("WarehouseForStockDecrease", current($warehouse_array)) : $langs->trans("WarehouseForStockIncrease", current($warehouse_array));
					$value = '<input type="hidden" id="idwarehouse" name="idwarehouse" value="'.key($warehouse_array).'">';
				} else {
					$label = $object->type == Facture::TYPE_CREDIT_NOTE ? $langs->trans("SelectWarehouseForStockDecrease") : $langs->trans("SelectWarehouseForStockIncrease");
					$value = $formproduct->selectWarehouses(GETPOST('idwarehouse') ? GETPOST('idwarehouse') : 'ifone', 'idwarehouse', '', 1);
				}
				$formquestion = array(
									// 'text' => $langs->trans("ConfirmClone"),
									// array('type' => 'checkbox', 'name' => 'clone_content', 'label' => $langs->trans("CloneMainAttributes"), 'value' =>
									// 1),
									// array('type' => 'checkbox', 'name' => 'update_prices', 'label' => $langs->trans("PuttingPricesUpToDate"), 'value'
									// => 1),
									array('type' => 'other', 'name' => 'idwarehouse', 'label' => $label, 'value' => $value));
			}
		}

		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?facid='.$object->id, $langs->trans('UnvalidateBill'), $text, 'confirm_modif', $formquestion, "yes", 1);
	}

	// Confirmation of payment classification
	if ($action == 'paid' && ($resteapayer <= 0 || (getDolGlobalString('INVOICE_CAN_SET_PAID_EVEN_IF_PARTIALLY_PAID') && $resteapayer == $object->total_ttc))) {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?facid='.$object->id, $langs->trans('ClassifyPaid'), $langs->trans('ConfirmClassifyPaidBill', $object->ref), 'confirm_paid', '', "yes", 1);
	}
	if ($action == 'paid' && $resteapayer > 0 && (!getDolGlobalString('INVOICE_CAN_SET_PAID_EVEN_IF_PARTIALLY_PAID') || $resteapayer != $object->total_ttc)) {
		$close = array();
		// Code
		$i = 0;
		$close[$i]['code'] = 'discount_vat'; // escompte
		$i++;
		$close[$i]['code'] = 'badcustomer';
		$i++;
		$close[$i]['code'] = 'bankcharge';
		$i++;
		$close[$i]['code'] = 'withholdingtax';
		$i++;
		$close[$i]['code'] = 'other';
		$i++;
		// Help
		$i = 0;
		$close[$i]['label'] = $langs->trans("HelpEscompte").'<br><br>'.$langs->trans("ConfirmClassifyPaidPartiallyReasonDiscountVatDesc");
		$i++;
		$close[$i]['label'] = $langs->trans("ConfirmClassifyPaidPartiallyReasonBadCustomerDesc");
		$i++;
		$close[$i]['label'] = $langs->trans("ConfirmClassifyPaidPartiallyReasonBankChargeDesc");
		$i++;
		$close[$i]['label'] = $langs->trans("ConfirmClassifyPaidPartiallyReasonWithholdingTaxDesc");
		$i++;
		$close[$i]['label'] = $langs->trans("Other");
		$i++;
		// Texte
		$i = 0;
		$close[$i]['reason'] = $form->textwithpicto($langs->transnoentities("ConfirmClassifyPaidPartiallyReasonDiscount", $resteapayer, $langs->trans("Currency".$conf->currency)), $close[$i]['label'], 1);
		$i++;
		$close[$i]['reason'] = $form->textwithpicto($langs->transnoentities("ConfirmClassifyPaidPartiallyReasonBadCustomer", $resteapayer, $langs->trans("Currency".$conf->currency)), $close[$i]['label'], 1);
		$i++;
		$close[$i]['reason'] = $form->textwithpicto($langs->transnoentities("ConfirmClassifyPaidPartiallyReasonBankCharge", $resteapayer, $langs->trans("Currency".$conf->currency)), $close[$i]['label'], 1);
		$i++;
		$close[$i]['reason'] = $form->textwithpicto($langs->transnoentities("ConfirmClassifyPaidPartiallyReasonWithholdingTax"), $close[$i]['label'], 1);
		$i++;
		$close[$i]['reason'] = $form->textwithpicto($langs->transnoentities("Other"), $close[$i]['label'], 1);
		$i++;
		// arrayreasons[code]=reason
		$arrayreasons = [];
		foreach ($close as $key => $val) {
			$arrayreasons[$close[$key]['code']] = $close[$key]['reason'];
		}

		// Create a form table
		$formquestion = array('text' => $langs->trans("ConfirmClassifyPaidPartiallyQuestion"), 0 => array('type' => 'radio', 'name' => 'close_code', 'label' => $langs->trans("Reason"), 'values' => $arrayreasons), 1 => array('type' => 'text', 'name' => 'close_note', 'label' => $langs->trans("Comment"), 'value' => '', 'morecss' => 'minwidth300'));
		// Incomplete payment. We ask if reason = discount or other
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?facid='.$object->id, $langs->trans('ClassifyPaid'), $langs->trans('ConfirmClassifyPaidPartially', $object->ref), 'confirm_paid_partially', $formquestion, "yes", 1, 380, 600);
	}

	// Confirmation of status abandoned
	if ($action == 'canceled') {
		// If there is a replacement invoice not yet validated (draft state),
		// it is not allowed to classify the invoice as abandoned.
		if ($objectidnext) {
			$facturereplacement = new Facture($db);
			$facturereplacement->fetch($objectidnext);
			$statusreplacement = $facturereplacement->status;
		}
		if ($objectidnext && $statusreplacement == 0) {
			print '<div class="error">'.$langs->trans("ErrorCantCancelIfReplacementInvoiceNotValidated").'</div>';
		} else {
			// Code
			$close[1]['code'] = 'badcustomer';
			$close[2]['code'] = 'abandon';
			// Help
			$close[1]['label'] = $langs->trans("ConfirmClassifyPaidPartiallyReasonBadCustomerDesc");
			$close[2]['label'] = $langs->trans("ConfirmClassifyAbandonReasonOtherDesc");
			// Text
			$close[1]['reason'] = $form->textwithpicto($langs->transnoentities("ConfirmClassifyPaidPartiallyReasonBadCustomer", $object->ref), $close[1]['label'], 1);
			$close[2]['reason'] = $form->textwithpicto($langs->transnoentities("ConfirmClassifyAbandonReasonOther"), $close[2]['label'], 1);
			// arrayreasons
			$arrayreasons = [];
			$arrayreasons[$close[1]['code']] = $close[1]['reason'];
			$arrayreasons[$close[2]['code']] = $close[2]['reason'];

			// Create a form table
			$formquestion = array('text' => $langs->trans("ConfirmCancelBillQuestion"), 0 => array('type' => 'radio', 'name' => 'close_code', 'label' => $langs->trans("Reason"), 'values' => $arrayreasons), 1 => array('type' => 'text', 'name' => 'close_note', 'label' => $langs->trans("Comment"), 'value' => '', 'morecss' => 'minwidth300'));

			$formconfirm = $form->formconfirm($_SERVER['PHP_SELF'].'?facid='.$object->id, $langs->trans('CancelBill'), $langs->trans('ConfirmCancelBill', $object->ref), 'confirm_canceled', $formquestion, "yes", 1, 270);
		}
	}

	if ($action == 'deletepayment') {
		$payment_id = GETPOST('paiement_id');
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&paiement_id='.$payment_id, $langs->trans('DeletePayment'), $langs->trans('ConfirmDeletePayment'), 'confirm_delete_paiement', '', 'no', 1);
	}

	// Confirmation de la suppression d'une ligne produit
	if ($action == 'ask_deleteline') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?facid='.$object->id.'&lineid='.$lineid, $langs->trans('DeleteProductLine'), $langs->trans('ConfirmDeleteProductLine'), 'confirm_deleteline', '', 'no', 1);
	}

	// Clone confirmation
	if ($action == 'clone') {
		$filter = '(s.client:IN:1,2,3)';
		// Create an array for form
		$formquestion = array(
			array('type' => 'other', 'name' => 'socid', 'label' => $langs->trans("SelectThirdParty"), 'value' => $form->select_company($object->socid, 'socid', $filter, 1)),
			array('type' => 'date', 'name' => 'newdate', 'label' => $langs->trans("Date"), 'value' => dol_now())
		);
		// Request confirmation to clone
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?facid='.$object->id, $langs->trans('ToClone'), $langs->trans('ConfirmCloneInvoice', $object->ref), 'confirm_clone', $formquestion, 'yes', 1, 250);
	}

	if ($action == "remove_file_comfirm") {
		$file = GETPOST('file', 'alpha');

		$formconfirm = $form->formconfirm(
			$_SERVER["PHP_SELF"].'?facid='.$object->id.'&file='.urlencode($file),
			$langs->trans('DeleteFileHeader'),
			$langs->trans('DeleteFileText')."<br><br>".$file,
			'remove_file',
			'',
			'no',
			1
		);
	}

	// Call Hook formConfirm
	$parameters = array('formConfirm' => $formconfirm, 'lineid' => $lineid, 'remainingtopay' => &$resteapayer);
	$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	if (empty($reshook)) {
		$formconfirm .= $hookmanager->resPrint;
	} elseif ($reshook > 0) {
		$formconfirm = $hookmanager->resPrint;
	}

	// Print form confirm
	print $formconfirm;

	// Invoice content

	$linkback = '<a href="'.DOL_URL_ROOT.'/compta/facture/list.php?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

	$morehtmlref = '<div class="refidno">';
	// Ref invoice
	if ($object->status == $object::STATUS_DRAFT && !$mysoc->isInEEC() && getDolGlobalString('INVOICE_ALLOW_FREE_REF')) {
		$morehtmlref .= $form->editfieldkey("Ref", 'ref', $object->ref, $object, $usercancreate, 'string', '', 0, 1);
		$morehtmlref .= $form->editfieldval("Ref", 'ref', $object->ref, $object, $usercancreate, 'string', '', null, null, '', 1);
		$morehtmlref .= '<br>';
	}
	// Ref customer
	$morehtmlref .= $form->editfieldkey("RefCustomer", 'ref_client', $object->ref_customer, $object, $usercancreate, 'string', '', 0, 1);
	$morehtmlref .= $form->editfieldval("RefCustomer", 'ref_client', $object->ref_customer, $object, $usercancreate, 'string'.(getDolGlobalString('THIRDPARTY_REF_INPUT_SIZE') ? ':' . getDolGlobalString('THIRDPARTY_REF_INPUT_SIZE') : ''), '', null, null, '', 1);
	// Thirdparty
	$morehtmlref .= '<br>'.$object->thirdparty->getNomUrl(1, 'customer');
	if (!getDolGlobalString('MAIN_DISABLE_OTHER_LINK') && $object->thirdparty->id > 0) {
		$morehtmlref .= ' (<a href="'.DOL_URL_ROOT.'/compta/facture/list.php?socid='.$object->thirdparty->id.'&search_societe='.urlencode($object->thirdparty->name).'">'.$langs->trans("OtherBills").'</a>)';
	}
	// Project
	if (isModEnabled('project')) {
		$langs->load("projects");
		$morehtmlref .= '<br>';
		if ($usercancreate) {
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

	$object->totalpaid = $totalpaid; // To give a chance to dol_banner_tab to use already paid amount to show correct status
	$object->totalcreditnotes = $totalcreditnotes;
	$object->totaldeposits = $totaldeposits;
	$object->remaintopay = price2num($object->invoice->total_ttc - $object->invoice->totalpaid - $object->invoice->totalcreditnotes - $object->invoice->totaldeposits, 'MT');

	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref, '', 0, '', '');

	// Call Hook tabContentViewInvoice
	$parameters = array();
	// Note that $action and $object may be modified by hook
	$reshook = $hookmanager->executeHooks('tabContentViewInvoice', $parameters, $object, $action);
	if (empty($reshook)) {
		print '<div class="fichecenter">';
		print '<div class="fichehalfleft">';
		print '<div class="underbanner clearboth"></div>';

		print '<table class="border centpercent tableforfield">';

		// Type
		print '<tr><td class="fieldname_type">'.$langs->trans('Type').'</td><td class="valuefield fieldname_type">';
		print $object->getLibType(2);
		if ($object->subtype > 0) {
			print ' '.$object->getSubtypeLabel('facture');
		}
		if ($object->module_source) {
			print ' <span class="opacitymediumbycolor paddingleft">('.$langs->trans("POS").' '.dol_escape_htmltag(ucfirst($object->module_source)).' - '.$langs->trans("Terminal").' '.dol_escape_htmltag($object->pos_source).')</span>';
		}
		if ($object->type == Facture::TYPE_REPLACEMENT) {
			$facreplaced = new Facture($db);
			$facreplaced->fetch($object->fk_facture_source);
			print ' <span class="opacitymediumbycolor paddingleft">'.$langs->transnoentities("ReplaceInvoice", $facreplaced->getNomUrl(1, '', 32)).'</span>';
		}
		if ($object->type == Facture::TYPE_CREDIT_NOTE && !empty($object->fk_facture_source)) {
			$facusing = new Facture($db);
			$facusing->fetch($object->fk_facture_source);
			print ' <span class="opacitymediumbycolor paddingleft">'.$langs->transnoentities("CorrectInvoice", $facusing->getNomUrl(1, '', 32)).'</span>';
		}

		$facidavoir = $object->getListIdAvoirFromInvoice();
		if (count($facidavoir) > 0) {
			print ' <span class="opacitymediumbycolor paddingleft">'.$langs->transnoentities("InvoiceHasAvoir");
			$i = 0;
			foreach ($facidavoir as $id) {
				if ($i == 0) {
					print ' ';
				} else {
					print ',';
				}
				$facavoir = new Facture($db);
				$facavoir->fetch($id);
				print $facavoir->getNomUrl(1, '', 32);
			}
			print '</span>';
		}
		if ($objectidnext > 0) {
			$facthatreplace = new Facture($db);
			$facthatreplace->fetch($objectidnext);
			print ' <span class="opacitymediumbycolor paddingleft">'.str_replace('{s1}', $facthatreplace->getNomUrl(1), $langs->transnoentities("ReplacedByInvoice", '{s1}')).'</span>';
		}

		if ($object->type == Facture::TYPE_CREDIT_NOTE || $object->type == Facture::TYPE_DEPOSIT) {
			$discount = new DiscountAbsolute($db);
			$result = $discount->fetch(0, $object->id);
			if ($result > 0) {
				print ' <span class="opacitymediumbycolor paddingleft">';
				$s = $langs->trans("CreditNoteConvertedIntoDiscount", '{s1}', '{s2}');
				$s = str_replace('{s1}', $object->getLibType(0), $s);
				$s = str_replace('{s2}', $discount->getNomUrl(1, 'discount'), $s);
				print $s;
				print '</span><br>';
			}
		}

		if ($object->fk_fac_rec_source > 0) {
			$tmptemplate = new FactureRec($db);
			$result = $tmptemplate->fetch($object->fk_fac_rec_source);
			if ($result > 0) {
				print ' <span class="opacitymediumbycolor paddingleft">';
				$s = $langs->transnoentities("GeneratedFromTemplate", '{s1}');
				$s = str_replace('{s1}', $tmptemplate->getNomUrl(1, '', 32), $s);
				print $s;
				print '</span>';
			}
		}
		print '</td></tr>';

		// Relative and absolute discounts
		print '<!-- Discounts -->'."\n";
		print '<tr><td>'.$langs->trans('DiscountStillRemaining').'</td>';
		print '<td>';
		$thirdparty = $soc;
		$discount_type = 0;
		$backtopage = $_SERVER["PHP_SELF"].'?facid='.$object->id;
		include DOL_DOCUMENT_ROOT.'/core/tpl/object_discounts.tpl.php';
		print '</td></tr>';

		// Date invoice
		print '<tr><td>';
		print '<table class="nobordernopadding centpercent"><tr><td>';
		print $langs->trans('DateInvoice');
		print '</td>';
		if ($action != 'editinvoicedate' && $object->status == $object::STATUS_DRAFT && $usercancreate && !getDolGlobalString('FAC_FORCE_DATE_VALIDATION')) {
			print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editinvoicedate&token='.newToken().'&facid='.$object->id.'">'.img_edit($langs->trans('SetDate'), 1).'</a></td>';
		}
		print '</tr></table>';
		print '</td><td>';

		if ($action == 'editinvoicedate') {
			$form->form_date($_SERVER['PHP_SELF'].'?facid='.$object->id, $object->date, 'invoicedate');
		} else {
			print '<span class="valuedate">'.dol_print_date($object->date, 'day').'</span>';
		}
		print '</td>';

		print '</tr>';

		if (getDolGlobalString('INVOICE_POINTOFTAX_DATE')) {
			// Date invoice point of tax
			print '<tr><td>';
			print '<table class="nobordernopadding centpercent"><tr><td>';
			print $langs->trans('DatePointOfTax');
			print '</td>';
			print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editdate_pointoftax&token='.newToken().'&facid='.$object->id.'">'.img_edit($langs->trans('SetDate'), 1).'</a></td>';
			print '</tr></table>';
			print '</td><td>';
			if ($action == 'editdate_pointoftax') {
				$form->form_date($_SERVER['PHP_SELF'].'?facid='.$object->id, $object->date_pointoftax, 'date_pointoftax');
			} else {
				print '<span class="valuedate">'.dol_print_date($object->date_pointoftax, 'day').'</span>';
			}
			print '</td></tr>';
		}

		// Payment term
		print '<tr><td>';
		print '<table class="nobordernopadding centpercent"><tr><td>';
		print $langs->trans('PaymentConditionsShort');
		print '</td>';
		if ($object->type != Facture::TYPE_CREDIT_NOTE && $action != 'editconditions' && $usercancreate) {
			print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editconditions&token='.newToken().'&facid='.$object->id.'">'.img_edit($langs->trans('SetConditions'), 1).'</a></td>';
		}
		print '</tr></table>';
		print '</td><td>';
		if ($object->type != Facture::TYPE_CREDIT_NOTE) {
			if ($action == 'editconditions') {
				$form->form_conditions_reglement($_SERVER['PHP_SELF'].'?facid='.$object->id, $object->cond_reglement_id, 'cond_reglement_id');
			} else {
				$form->form_conditions_reglement($_SERVER['PHP_SELF'].'?facid='.$object->id, $object->cond_reglement_id, 'none');
			}
		} else {
			print '&nbsp;';
		}
		print '</td></tr>';

		// Date payment term
		print '<tr><td>';
		print '<table class="nobordernopadding centpercent"><tr><td>';
		print $langs->trans('DateMaxPayment');
		print '</td>';
		if ($object->type != Facture::TYPE_CREDIT_NOTE && $action != 'editpaymentterm' && $usercancreate) {
			print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editpaymentterm&token='.newToken().'&facid='.$object->id.'">'.img_edit($langs->trans('SetDate'), 1).'</a></td>';
		}
		print '</tr></table>';
		print '</td><td>';
		if ($object->type != Facture::TYPE_CREDIT_NOTE) {
			if ($action == 'editpaymentterm') {
				$form->form_date($_SERVER['PHP_SELF'].'?facid='.$object->id, $object->date_lim_reglement, 'paymentterm');
			} else {
				print '<span class="valuedate">'.dol_print_date($object->date_lim_reglement, 'day').'</span>';
				if ($object->hasDelay()) {
					print img_warning($langs->trans('Late'));
				}
			}
		} else {
			print '&nbsp;';
		}
		print '</td></tr>';

		// Payment mode
		print '<tr><td>';
		print '<table class="nobordernopadding centpercent"><tr><td>';
		print $langs->trans('PaymentMode');
		print '</td>';
		if ($action != 'editmode' && $usercancreate) {
			print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editmode&token='.newToken().'&facid='.$object->id.'">'.img_edit($langs->trans('SetMode'), 1).'</a></td>';
		}
		print '</tr></table>';
		print '</td><td>';
		if ($action == 'editmode') {
			$form->form_modes_reglement($_SERVER['PHP_SELF'].'?facid='.$object->id, $object->mode_reglement_id, 'mode_reglement_id', 'CRDT', 1, 1);
		} else {
			$form->form_modes_reglement($_SERVER['PHP_SELF'].'?facid='.$object->id, $object->mode_reglement_id, 'none', 'CRDT');
		}
		print '</td></tr>';

		// Bank Account
		if (isModEnabled("bank")) {
			print '<tr><td class="nowrap">';
			print '<table class="nobordernopadding centpercent"><tr><td class="nowrap">';
			print $langs->trans('BankAccount');
			print '<td>';
			if (($action != 'editbankaccount') && $usercancreate) {
				print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editbankaccount&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->trans('SetBankAccount'), 1).'</a></td>';
			}
			print '</tr></table>';
			print '</td><td>';
			if ($action == 'editbankaccount') {
				$form->formSelectAccount($_SERVER['PHP_SELF'].'?id='.$object->id, $object->fk_account, 'fk_account', 1);
			} else {
				$form->formSelectAccount($_SERVER['PHP_SELF'].'?id='.$object->id, $object->fk_account, 'none');
			}
			print "</td>";
			print '</tr>';
		}

		// Incoterms
		if (isModEnabled('incoterm')) {
			print '<tr><td>';
			print '<table class="nobordernopadding centpercent"><tr><td>';
			print $langs->trans('IncotermLabel');
			print '<td><td class="right">';
			if ($usercancreate) {
				print '<a class="editfielda" href="'.DOL_URL_ROOT.'/compta/facture/card.php?facid='.$object->id.'&action=editincoterm&token='.newToken().'">'.img_edit().'</a>';
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



		if (!empty($object->retained_warranty) || getDolGlobalString('INVOICE_USE_RETAINED_WARRANTY')) {
			$displayWarranty = true;
			if (!in_array($object->type, $retainedWarrantyInvoiceAvailableType) && empty($object->retained_warranty)) {
				$displayWarranty = false;
			}

			if ($displayWarranty) {
				// Retained Warranty
				print '<tr class="retained-warranty-lines"  ><td>';
				print '<table id="retained-warranty-table" class="nobordernopadding centpercent"><tr><td>';
				print $langs->trans('RetainedWarranty');
				print '</td>';
				if ($action != 'editretainedwarranty' && $user->hasRight('facture', 'creer') && $object->status == Facture::STATUS_DRAFT) {
					print '<td align="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editretainedwarranty&token='.newToken().'&facid='.$object->id.'">'.img_edit($langs->trans('setRetainedWarranty'), 1).'</a></td>';
				}

				print '</tr></table>';
				print '</td><td>';
				if ($action == 'editretainedwarranty' && $object->status == Facture::STATUS_DRAFT) {
					print '<form  id="retained-warranty-form"  method="POST" action="'.$_SERVER['PHP_SELF'].'?facid='.$object->id.'">';
					print '<input type="hidden" name="action" value="setretainedwarranty">';
					print '<input type="hidden" name="token" value="'.newToken().'">';
					print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
					print '<input name="retained_warranty" type="number" step="0.01" min="0" max="100" value="'.$object->retained_warranty.'" >';
					print '<input type="submit" class="button valignmiddle smallpaddingimp" value="'.$langs->trans("Modify").'">';
					print '</form>';
				} else {
					print price($object->retained_warranty).'%';
				}
				print '</td></tr>';

				// Retained warranty payment term
				print '<tr class="retained-warranty-lines"  ><td>';
				print '<table id="retained-warranty-cond-reglement-table"  class="nobordernopadding" width="100%"><tr><td>';
				print $langs->trans('PaymentConditionsShortRetainedWarranty');
				print '</td>';
				if ($action != 'editretainedwarrantypaymentterms' && $user->hasRight('facture', 'creer') && $object->status == Facture::STATUS_DRAFT) {
					print '<td align="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editretainedwarrantypaymentterms&token='.newToken().'&facid='.$object->id.'">'.img_edit($langs->trans('setPaymentConditionsShortRetainedWarranty'), 1).'</a></td>';
				}

				print '</tr></table>';
				print '</td><td>';
				$defaultDate = !empty($object->retained_warranty_date_limit) ? $object->retained_warranty_date_limit : strtotime('-1 years', $object->date_lim_reglement);
				if ($object->date > $defaultDate) {
					$defaultDate = $object->date;
				}

				if ($action == 'editretainedwarrantypaymentterms' && $object->status == Facture::STATUS_DRAFT) {
					//date('Y-m-d',$object->date_lim_reglement)
					print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'?facid='.$object->id.'">';
					print '<input type="hidden" name="action" value="setretainedwarrantyconditions">';
					print '<input type="hidden" name="token" value="'.newToken().'">';
					print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
					$retained_warranty_fk_cond_reglement = GETPOSTINT('retained_warranty_fk_cond_reglement');
					$retained_warranty_fk_cond_reglement = !empty($retained_warranty_fk_cond_reglement) ? $retained_warranty_fk_cond_reglement : $object->retained_warranty_fk_cond_reglement;
					$retained_warranty_fk_cond_reglement = !empty($retained_warranty_fk_cond_reglement) ? $retained_warranty_fk_cond_reglement : getDolGlobalString('INVOICE_SITUATION_DEFAULT_RETAINED_WARRANTY_COND_ID');
					print $form->getSelectConditionsPaiements($retained_warranty_fk_cond_reglement, 'retained_warranty_fk_cond_reglement', -1, 1);
					print '<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
					print '</form>';
				} else {
					$form->form_conditions_reglement($_SERVER['PHP_SELF'].'?facid='.$object->id, $object->retained_warranty_fk_cond_reglement, 'none');
					if (!$displayWarranty) {
						print img_picto($langs->trans('RetainedWarrantyNeed100Percent'), 'warning.png', 'class="pictowarning valignmiddle" ');
					}
				}
				print '</td></tr>';

				// Retained Warranty payment date limit
				print '<tr class="retained-warranty-lines"  ><td>';
				print '<table id="retained-warranty-date-limit-table"  class="nobordernopadding" width="100%"><tr><td>';
				print $langs->trans('RetainedWarrantyDateLimit');
				print '</td>';
				if ($action != 'editretainedwarrantydatelimit' && $user->hasRight('facture', 'creer') && $object->status == Facture::STATUS_DRAFT) {
					print '<td align="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editretainedwarrantydatelimit&token='.newToken().'&facid='.$object->id.'">'.img_edit($langs->trans('setRetainedWarrantyDateLimit'), 1).'</a></td>';
				}

				print '</tr></table>';
				print '</td><td>';
				$defaultDate = !empty($object->retained_warranty_date_limit) ? $object->retained_warranty_date_limit : strtotime('-1 years', $object->date_lim_reglement);
				if ($object->date > $defaultDate) {
					$defaultDate = $object->date;
				}

				if ($action == 'editretainedwarrantydatelimit' && $object->status == Facture::STATUS_DRAFT) {
					//date('Y-m-d',$object->date_lim_reglement)
					print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'?facid='.$object->id.'">';
					print '<input type="hidden" name="action" value="setretainedwarrantydatelimit">';
					print '<input type="hidden" name="token" value="'.newToken().'">';
					print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
					print '<input name="retained_warranty_date_limit" type="date" step="1" min="'.dol_print_date($object->date, '%Y-%m-%d').'" value="'.dol_print_date($defaultDate, '%Y-%m-%d').'" >';
					print '<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
					print '</form>';
				} else {
					print dol_print_date($object->retained_warranty_date_limit, 'day');
				}
				print '</td></tr>';
			}
		}


		// Other attributes
		$cols = 2;
		include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

		print '</table>';

		print '</div>';
		print '<div class="fichehalfright">';

		print '<!-- amounts -->'."\n";
		print '<div class="underbanner clearboth"></div>'."\n";

		print '<table class="border tableforfield centpercent">';

		include DOL_DOCUMENT_ROOT.'/core/tpl/object_currency_amount.tpl.php';

		$sign = 1;
		if (getDolGlobalString('INVOICE_POSITIVE_CREDIT_NOTE_SCREEN') && $object->type == $object::TYPE_CREDIT_NOTE) {
			$sign = -1; // We invert sign for output
		}
		print '<tr>';
		// Amount HT
		print '<td class="titlefieldmiddle">' . $langs->trans('AmountHT') . '</td>';
		print '<td class="nowrap amountcard right">' . price($sign * $object->total_ht, 0, $langs, 0, -1, -1, $conf->currency) . '</td>';
		if (isModEnabled("multicurrency") && ($object->multicurrency_code && $object->multicurrency_code != $conf->currency)) {
			// Multicurrency Amount HT
			print '<td class="nowrap amountcard right">' . price($sign * $object->multicurrency_total_ht, 0, $langs, 0, -1, -1, $object->multicurrency_code) . '</td>';
		}
		print '</tr>';

		print '<tr>';
		// Amount VAT
		print '<td>' . $langs->trans('AmountVAT') . '</td>';
		print '<td class="nowrap amountcard right">' . price($sign * $object->total_tva, 0, $langs, 0, -1, -1, $conf->currency) . '</td>';
		if (isModEnabled("multicurrency") && ($object->multicurrency_code && $object->multicurrency_code != $conf->currency)) {
			// Multicurrency Amount VAT
			print '<td class="nowrap amountcard right">' . price($sign * $object->multicurrency_total_tva, 0, $langs, 0, -1, -1, $object->multicurrency_code) . '</td>';
		}
		print '</tr>';

		// Amount Local Taxes
		if (($mysoc->localtax1_assuj == "1" && $mysoc->useLocalTax(1)) || $object->total_localtax1 != 0) {
			print '<tr>';
			print '<td class="titlefieldmiddle">' . $langs->transcountry("AmountLT1", $mysoc->country_code) . '</td>';
			print '<td class="nowrap amountcard right">' . price($sign * $object->total_localtax1, 0, $langs, 0, -1, -1, $conf->currency) . '</td>';
			if (isModEnabled("multicurrency") && ($object->multicurrency_code && $object->multicurrency_code != $conf->currency)) {
				$object->multicurrency_total_localtax1 = (float) price2num($object->total_localtax1 * $object->multicurrency_tx, 'MT');

				print '<td class="nowrap amountcard right">' . price($sign * $object->multicurrency_total_localtax1, 0, $langs, 0, -1, -1, $object->multicurrency_code) . '</td>';
			}
			print '</tr>';
		}

		if (($mysoc->localtax2_assuj == "1" && $mysoc->useLocalTax(2)) || $object->total_localtax2 != 0) {
			print '<tr>';
			print '<td>' . $langs->transcountry("AmountLT2", $mysoc->country_code) . '</td>';
			print '<td class="nowrap amountcard right">' . price($sign * $object->total_localtax2, 0, $langs, 0, -1, -1, $conf->currency) . '</td>';
			if (isModEnabled("multicurrency") && ($object->multicurrency_code && $object->multicurrency_code != $conf->currency)) {
				$object->multicurrency_total_localtax2 = (float) price2num($object->total_localtax2 * $object->multicurrency_tx, 'MT');

				print '<td class="nowrap amountcard right">' . price($sign * $object->multicurrency_total_localtax2, 0, $langs, 0, -1, -1, $object->multicurrency_code) . '</td>';
			}
			print '</tr>';
		}

		// Add the revenue stamp
		if ($selleruserevenustamp) {
			print '<tr><td class="titlefieldmiddle">';
			print '<table class="nobordernopadding centpercent"><tr><td>';
			print $langs->trans('RevenueStamp');
			print '</td>';
			if ($action != 'editrevenuestamp' && $object->status == $object::STATUS_DRAFT && $usercancreate) {
				print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editrevenuestamp&token='.newToken().'&facid='.$object->id.'">'.img_edit($langs->trans('SetRevenuStamp'), 1).'</a></td>';
			}
			print '</tr></table>';
			print '</td><td class="nowrap amountcard right">';
			if ($action == 'editrevenuestamp') {
				print '<form action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="post">';
				print '<input type="hidden" name="token" value="'.newToken().'">';
				print '<input type="hidden" name="action" value="setrevenuestamp">';
				print '<input type="hidden" name="revenuestamp" id="revenuestamp_val" value="'.price2num($object->revenuestamp).'">';
				print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
				print $formother->select_revenue_stamp('', 'revenuestamp_type', $mysoc->country_code);
				print ' &rarr; <span id="revenuestamp_span"></span>';
				print ' <input type="submit" class="button buttongen button-save small" value="'.$langs->trans('Modify').'">';
				print '</form>';
				print " <script>
					$(document).ready(function(){
						js_recalculate_revenuestamp();
						$('select[name=revenuestamp_type]').on('change',function(){
							js_recalculate_revenuestamp();
						});
					});
					function js_recalculate_revenuestamp(){
						var valselected = $('select[name=revenuestamp_type]').val();
						console.log('Calculate revenue stamp from '+valselected);
						var revenue = 0;
						if (valselected.indexOf('%') == -1)
						{
							revenue = valselected;
						}
						else
						{
							var revenue_type = parseFloat(valselected);
							var amount_net = ".round($object->total_ht, 2).";
							revenue = revenue_type * amount_net / 100;
							revenue = revenue.toFixed(2);
						}
						$('#revenuestamp_val').val(revenue);
						$('#revenuestamp_span').html(revenue);
					}
				</script>";
			} else {
				print price($object->revenuestamp, 1, '', 1, -1, -1, $conf->currency);
			}
			print '</td></tr>';
		}

		print '<tr>';
		// Amount TTC
		print '<td>' . $langs->trans('AmountTTC') . '</td>';
		print '<td class="nowrap amountcard right">' . price($sign * $object->total_ttc, 0, $langs, 0, -1, -1, $conf->currency) . '</td>';
		if (isModEnabled("multicurrency") && ($object->multicurrency_code && $object->multicurrency_code != $conf->currency)) {
			// Multicurrency Amount TTC
			print '<td class="nowrap amountcard right">' . price($sign * $object->multicurrency_total_ttc, 0, $langs, 0, -1, -1, $object->multicurrency_code) . '</td>';
		}
		print '</tr>';

		print '</table>';

		$nbrows = 8;
		$nbcols = 3;
		if (isModEnabled('project')) {
			$nbrows++;
		}
		if (isModEnabled("bank")) {
			$nbrows++;
			$nbcols++;
		}
		if ($mysoc->localtax1_assuj == "1" || $object->total_localtax1 != 0) {
			$nbrows++;
		}
		if ($mysoc->localtax2_assuj == "1" || $object->total_localtax2 != 0) {
			$nbrows++;
		}
		if ($selleruserevenustamp) {
			$nbrows++;
		}
		if (isModEnabled('multicurrency')) {
			$nbrows += 5;
		}
		if (isModEnabled('incoterm')) {
			$nbrows += 1;
		}

		// List of previous situation invoices
		if (($object->situation_cycle_ref > 0) && getDolGlobalString('INVOICE_USE_SITUATION')) {
			print '<!-- List of situation invoices -->';
			print '<table class="noborder situationstable" width="100%">';

			print '<tr class="liste_titre">';
			print '<td>'.$langs->trans('ListOfSituationInvoices').'</td>';
			print '<td></td>';
			print '<td class="center">'.$langs->trans('Situation').'</td>';
			if (isModEnabled("bank")) {
				print '<td class="right"></td>';
			}
			print '<td class="right">'.$langs->trans('AmountHT').'</td>';
			print '<td class="right">'.$langs->trans('AmountTTC').'</td>';
			print '<td width="18">&nbsp;</td>';
			print '</tr>';

			$total_prev_ht = $total_prev_ttc = 0;
			$total_global_ht = $total_global_ttc = 0;

			if (count($object->tab_previous_situation_invoice) > 0) {
				// List of previous invoices

				$current_situation_counter = array();
				foreach ($object->tab_previous_situation_invoice as $prev_invoice) {
					$tmptotalpaidforthisinvoice = $prev_invoice->getSommePaiement();
					$total_prev_ht += $prev_invoice->total_ht;
					$total_prev_ttc += $prev_invoice->total_ttc;
					$current_situation_counter[] = (($prev_invoice->type == Facture::TYPE_CREDIT_NOTE) ? -1 : 1) * $prev_invoice->situation_counter;
					print '<tr class="oddeven">';
					print '<td>'.$prev_invoice->getNomUrl(1).'</td>';
					print '<td></td>';
					print '<td align="center" >'.(($prev_invoice->type == Facture::TYPE_CREDIT_NOTE) ? $langs->trans('situationInvoiceShortcode_AS') : $langs->trans('situationInvoiceShortcode_S')).$prev_invoice->situation_counter.'</td>';
					if (isModEnabled("bank")) {
						print '<td class="right"></td>';
					}
					print '<td class="right"><span class="amount">'.price($prev_invoice->total_ht).'</span></td>';
					print '<td class="right"><span class="amount">'.price($prev_invoice->total_ttc).'</span></td>';
					print '<td class="right">'.$prev_invoice->getLibStatut(3, $tmptotalpaidforthisinvoice).'</td>';
					print '</tr>';
				}
			}


			$total_global_ht += $total_prev_ht;
			$total_global_ttc += $total_prev_ttc;
			$total_global_ht += $object->total_ht;
			$total_global_ttc += $object->total_ttc;
			$current_situation_counter[] = (($object->type == Facture::TYPE_CREDIT_NOTE) ? -1 : 1) * $object->situation_counter;
			print '<tr class="oddeven">';
			print '<td>'.$object->getNomUrl(1).'</td>';
			print '<td></td>';
			print '<td class="center">'.(($object->type == Facture::TYPE_CREDIT_NOTE) ? $langs->trans('situationInvoiceShortcode_AS') : $langs->trans('situationInvoiceShortcode_S')).$object->situation_counter.'</td>';
			if (isModEnabled("bank")) {
				print '<td class="right"></td>';
			}
			print '<td class="right"><span class="amount">'.price($object->total_ht).'</span></td>';
			print '<td class="right"><span class="amount">'.price($object->total_ttc).'</span></td>';
			print '<td class="right">'.$object->getLibStatut(3, $object->getSommePaiement()).'</td>';
			print '</tr>';


			print '<tr class="oddeven">';
			print '<td colspan="2" class="left"><b>'.$langs->trans('CurrentSituationTotal').'</b></td>';
			print '<td>';
			$i = 0;
			foreach ($current_situation_counter as $sit) {
				$curSign = $sit > 0 ? '+' : '-';
				$curType = $sit > 0 ? $langs->trans('situationInvoiceShortcode_S') : $langs->trans('situationInvoiceShortcode_AS');
				if ($i > 0) {
					print ' '.$curSign.' ';
				}
				print $curType.abs($sit);
				$i++;
			}
			print '</td>';
			if (isModEnabled("bank")) {
				print '<td></td>';
			}
			print '<td class="right"><b>'.price($total_global_ht).'</b></td>';
			print '<td class="right"><b>'.price($total_global_ttc).'</b></td>';
			print '<td width="18">&nbsp;</td>';
			print '</tr>';


			if (count($object->tab_next_situation_invoice) > 0) {
				// List of next invoices
				/*print '<tr class="liste_titre">';
				 print '<td>' . $langs->trans('ListOfNextSituationInvoices') . '</td>';
				 print '<td></td>';
				 print '<td></td>';
				 if (isModEnabled('banque')) print '<td class="right"></td>';
				 print '<td class="right">' . $langs->trans('AmountHT') . '</td>';
				 print '<td class="right">' . $langs->trans('AmountTTC') . '</td>';
				 print '<td width="18">&nbsp;</td>';
				 print '</tr>';*/

				$total_next_ht = $total_next_ttc = 0;

				foreach ($object->tab_next_situation_invoice as $next_invoice) {
					$totalpaid = $next_invoice->getSommePaiement();
					$total_next_ht += $next_invoice->total_ht;
					$total_next_ttc += $next_invoice->total_ttc;

					print '<tr class="oddeven">';
					print '<td>'.$next_invoice->getNomUrl(1).'</td>';
					print '<td></td>';
					print '<td class="center">'.(($next_invoice->type == Facture::TYPE_CREDIT_NOTE) ? $langs->trans('situationInvoiceShortcode_AS') : $langs->trans('situationInvoiceShortcode_S')).$next_invoice->situation_counter.'</td>';
					if (isModEnabled("bank")) {
						print '<td class="right"></td>';
					}
					print '<td class="right"><span class="amount">'.price($next_invoice->total_ht).'</span></td>';
					print '<td class="right"><span class="amount">'.price($next_invoice->total_ttc).'</span></td>';
					print '<td class="right">'.$next_invoice->getLibStatut(3, $totalpaid).'</td>';
					print '</tr>';
				}

				$total_global_ht += $total_next_ht;
				$total_global_ttc += $total_next_ttc;

				print '<tr class="oddeven">';
				print '<td colspan="3" class="right"></td>';
				if (isModEnabled("bank")) {
					print '<td class="right"></td>';
				}
				print '<td class="right"><b>'.price($total_global_ht).'</b></td>';
				print '<td class="right"><b>'.price($total_global_ttc).'</b></td>';
				print '<td width="18">&nbsp;</td>';
				print '</tr>';
			}

			print '</table>';
		}

		$sign = 1;
		if ($object->type == $object::TYPE_CREDIT_NOTE) {
			$sign = -1;
		}

		// List of payments already done

		print '<!-- List of payments already done -->';
		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder paymenttable centpercent">';

		print '<tr class="liste_titre">';
		print '<td class="liste_titre">'.($object->type == Facture::TYPE_CREDIT_NOTE ? $langs->trans("PaymentsBack") : $langs->trans('Payments')).'</td>';
		print '<td class="liste_titre"><span class="hideonsmartphone">'.$langs->trans('Date').'</span></td>';
		print '<td class="liste_titre"><span class="hideonsmartphone">'.$langs->trans('Type').'</span></td>';
		if (isModEnabled("bank")) {
			print '<td class="liste_titre"><span class="hideonsmartphone">'.$langs->trans('BankAccount').'</span></td>';
		}
		print '<td class="liste_titre right">'.$langs->trans('Amount').'</td>';
		print '<td class="liste_titre" width="18">&nbsp;</td>';
		print '</tr>';

		// Payments already done (from payment on this invoice)
		$sql = 'SELECT p.datep as dp, p.ref, p.num_paiement as num_payment, p.rowid, p.fk_bank,';
		$sql .= ' c.code as payment_code, c.libelle as payment_label,';
		$sql .= ' pf.amount,';
		$sql .= ' ba.rowid as baid, ba.ref as baref, ba.label, ba.number as banumber, ba.account_number, ba.fk_accountancy_journal, ba.currency_code as bacurrency_code';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'paiement_facture as pf, '.MAIN_DB_PREFIX.'paiement as p';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_paiement as c ON p.fk_paiement = c.id';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'bank as b ON p.fk_bank = b.rowid';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'bank_account as ba ON b.fk_account = ba.rowid';
		$sql .= ' WHERE pf.fk_facture = '.((int) $object->id).' AND pf.fk_paiement = p.rowid';
		$sql .= ' AND p.entity IN ('.getEntity('invoice').')';
		$sql .= ' ORDER BY p.datep, p.tms';

		$result = $db->query($sql);
		if ($result) {
			$num = $db->num_rows($result);
			$i = 0;

			if ($num > 0) {
				while ($i < $num) {
					$objp = $db->fetch_object($result);

					$paymentstatic->id = $objp->rowid;
					$paymentstatic->datepaye = $db->jdate($objp->dp);
					$paymentstatic->ref = $objp->ref;
					$paymentstatic->num_payment = $objp->num_payment;
					$paymentstatic->paiementcode = $objp->payment_code;

					print '<tr class="oddeven"><td class="nowraponall">';
					print $paymentstatic->getNomUrl(1);
					print '</td>';
					print '<td>';
					$dateofpayment = $db->jdate($objp->dp);
					$tmparray = dol_getdate($dateofpayment);
					if ($tmparray['seconds'] == 0 && $tmparray['minutes'] == 0 && ($tmparray['hours'] == 0 || $tmparray['hours'] == 12)) {	// We set hours to 0:00 or 12:00 because we don't know it
						print dol_print_date($dateofpayment, 'day');
					} else {	// Hours was set to real date of payment (special case for POS for example)
						print dol_print_date($dateofpayment, 'dayhour', 'tzuser');
					}
					print '</td>';

					$label = ($langs->trans("PaymentType".$objp->payment_code) != "PaymentType".$objp->payment_code) ? $langs->trans("PaymentType".$objp->payment_code) : $objp->payment_label;
					print '<td class="tdoverflowmax80" title="'.dol_escape_htmltag($label.' '.$objp->num_payment).'">'.dol_escape_htmltag($label.' '.$objp->num_payment).'</td>';
					if (isModEnabled("bank")) {
						$bankaccountstatic->id = $objp->baid;
						$bankaccountstatic->ref = $objp->baref;
						$bankaccountstatic->label = $objp->baref;
						$bankaccountstatic->number = $objp->banumber;
						$bankaccountstatic->currency_code = $objp->bacurrency_code;

						if (isModEnabled('accounting')) {
							$bankaccountstatic->account_number = $objp->account_number;

							$accountingjournal = new AccountingJournal($db);
							$accountingjournal->fetch($objp->fk_accountancy_journal);
							$bankaccountstatic->accountancy_journal = $accountingjournal->getNomUrl(0, 1, 1, '', 1);
						}

						print '<td class="nowraponall">';
						if ($bankaccountstatic->id) {
							print $bankaccountstatic->getNomUrl(1, 'transactions');
						}
						print '</td>';
					}
					print '<td class="right"><span class="amount">'.price($sign * $objp->amount).'</span></td>';
					print '<td class="center">';

					$paiement = new Paiement($db);
					$paiement->fetch($objp->rowid);
					if ($object->status == Facture::STATUS_VALIDATED && $object->paye == 0 && $user->socid == 0 && !$paiement->isReconciled()) {
						print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=deletepayment&token='.newToken().'&paiement_id='.$objp->rowid.'">';
						print img_delete();
						print '</a>';
					}
					print '</td>';
					print '</tr>';
					$i++;
				}
			}

			$db->free($result);
		} else {
			dol_print_error($db);
		}

		if ($object->type != Facture::TYPE_CREDIT_NOTE) {
			// Total already paid
			print '<tr><td colspan="'.$nbcols.'" class="right">';
			print '<span class="opacitymedium">';
			if ($object->type != Facture::TYPE_DEPOSIT) {
				print $langs->trans('AlreadyPaidNoCreditNotesNoDeposits');
			} else {
				print $langs->trans('AlreadyPaid');
			}
			print '</span></td><td class="right'.(($totalpaid > 0) ? ' amountalreadypaid' : '').'">'.price($totalpaid).'</td><td>&nbsp;</td></tr>';

			$resteapayeraffiche = $resteapayer;
			$cssforamountpaymentcomplete = 'amountpaymentcomplete';

			// Loop on each credit note or deposit amount applied
			$creditnoteamount = 0;
			$depositamount = 0;
			$sql = "SELECT re.rowid, re.amount_ht, re.amount_tva, re.amount_ttc,";
			$sql .= " re.description, re.fk_facture_source";
			$sql .= " FROM ".MAIN_DB_PREFIX."societe_remise_except as re";
			$sql .= " WHERE fk_facture = ".((int) $object->id);
			$resql = $db->query($sql);
			if ($resql) {
				$num = $db->num_rows($resql);
				$i = 0;
				$invoice = new Facture($db);
				while ($i < $num) {
					$obj = $db->fetch_object($resql);
					$invoice->fetch($obj->fk_facture_source);
					print '<tr><td colspan="'.$nbcols.'" class="right">';
					print '<span class="opacitymedium">';
					if ($invoice->type == Facture::TYPE_CREDIT_NOTE) {
						print $langs->trans("CreditNote").' ';
					}
					if ($invoice->type == Facture::TYPE_DEPOSIT) {
						print $langs->trans("Deposit").' ';
					}
					print $invoice->getNomUrl(0);
					print '</span>';
					print '</td>';
					print '<td class="right"><span class="amount">'.price($obj->amount_ttc).'</span></td>';
					print '<td class="right">';
					print '<a href="'.$_SERVER["PHP_SELF"].'?facid='.$object->id.'&action=unlinkdiscount&token='.newToken().'&discountid='.$obj->rowid.'">';
					print img_picto($langs->transnoentitiesnoconv("RemoveDiscount"), 'unlink');
					print '</a>';
					print '</td></tr>';
					$i++;
					if ($invoice->type == Facture::TYPE_CREDIT_NOTE) {
						$creditnoteamount += $obj->amount_ttc;
					}
					if ($invoice->type == Facture::TYPE_DEPOSIT) {
						$depositamount += $obj->amount_ttc;
					}
				}
			} else {
				dol_print_error($db);
			}

			// Partially paid 'discount'
			if (($object->status == Facture::STATUS_CLOSED || $object->status == Facture::STATUS_ABANDONED) && $object->close_code == 'discount_vat') {
				print '<tr><td colspan="'.$nbcols.'" class="nowrap right">';
				print '<span class="opacitymedium">';
				print $form->textwithpicto($langs->trans("Discount"), $langs->trans("HelpEscompte"), - 1);
				print '</span>';
				print '</td><td class="right"><span class="amount">'.price(price2num($object->total_ttc - $creditnoteamount - $depositamount - $totalpaid, 'MT')).'</span></td><td>&nbsp;</td></tr>';
				$resteapayeraffiche = 0;
				$cssforamountpaymentcomplete = 'amountpaymentneutral';
			}
			// Partially paid or abandoned 'badcustomer'
			if (($object->status == Facture::STATUS_CLOSED || $object->status == Facture::STATUS_ABANDONED) && $object->close_code == 'badcustomer') {
				print '<tr><td colspan="'.$nbcols.'" class="nowrap right">';
				print '<span class="opacitymedium">';
				print $form->textwithpicto($langs->trans("Abandoned"), $langs->trans("HelpAbandonBadCustomer"), - 1);
				print '</span>';
				print '</td><td class="right">'.price(price2num($object->total_ttc - $creditnoteamount - $depositamount - $totalpaid, 'MT')).'</td><td>&nbsp;</td></tr>';
				// $resteapayeraffiche=0;
				$cssforamountpaymentcomplete = 'amountpaymentneutral';
			}
			// Partially paid or abandoned 'product_returned'
			if (($object->status == Facture::STATUS_CLOSED || $object->status == Facture::STATUS_ABANDONED) && $object->close_code == 'product_returned') {
				print '<tr><td colspan="'.$nbcols.'" class="nowrap right">';
				print '<span class="opacitymedium">';
				print $form->textwithpicto($langs->trans("ProductReturned"), $langs->trans("HelpAbandonProductReturned"), - 1);
				print '</span>';
				print '</td><td class="right"><span class="amount">'.price(price2num($object->total_ttc - $creditnoteamount - $depositamount - $totalpaid, 'MT')).'</span></td><td>&nbsp;</td></tr>';
				$resteapayeraffiche = 0;
				$cssforamountpaymentcomplete = 'amountpaymentneutral';
			}
			// Partially paid or abandoned 'abandoned'
			if (($object->status == Facture::STATUS_CLOSED || $object->status == Facture::STATUS_ABANDONED) && $object->close_code == 'abandon') {
				print '<tr><td colspan="'.$nbcols.'" class="nowrap right">';
				$text = $langs->trans("HelpAbandonOther");
				if ($object->close_note) {
					$text .= '<br><br><b>'.$langs->trans("Reason").'</b>:'.$object->close_note;
				}
				print '<span class="opacitymedium">';
				// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
				print $form->textwithpicto($langs->trans("Abandoned"), $text, - 1);
				print '</span>';
				print '</td><td class="right"><span class="amount">'.price(price2num($object->total_ttc - $creditnoteamount - $depositamount - $totalpaid, 'MT')).'</span></td><td>&nbsp;</td></tr>';
				$resteapayeraffiche = 0;
				$cssforamountpaymentcomplete = 'amountpaymentneutral';
			}

			// Billed
			print '<tr><td colspan="'.$nbcols.'" class="right">';
			print '<span class="opacitymedium">';
			print $langs->trans("Billed");
			print '</td><td class="right">'.price($object->total_ttc).'</td><td>&nbsp;</td></tr>';
			// Remainder to pay
			print '<tr><td colspan="'.$nbcols.'" class="right">';
			print '<span class="opacitymedium">';
			print $langs->trans('RemainderToPay');
			if ($resteapayeraffiche < 0) {
				print ' ('.$langs->trans('NegativeIfExcessReceived').')';
			}
			print '</span>';
			print '</td>';
			print '<td class="right'.($resteapayeraffiche ? ' amountremaintopay' : (' '.$cssforamountpaymentcomplete)).'">'.price($resteapayeraffiche).'</td><td>&nbsp;</td></tr>';

			// Remainder to pay Multicurrency
			if ($object->multicurrency_code != $conf->currency || $object->multicurrency_tx != 1) {
				print '<tr><td colspan="'.$nbcols.'" class="right">';
				print '<span class="opacitymedium">';
				print $langs->trans('RemainderToPayMulticurrency');
				if ($resteapayeraffiche < 0) {
					print ' ('.$langs->trans('NegativeIfExcessReceived').')';
				}
				print '</span>';
				print '</td>';
				print '<td class="right'.($resteapayeraffiche ? ' amountremaintopay' : (' '.$cssforamountpaymentcomplete)).'">';
				//print (empty($object->multicurrency_code) ? $conf->currency : $object->multicurrency_code).' ';
				print price(price2num($object->multicurrency_tx * $resteapayeraffiche, 'MT'), 1, $langs, 1, -1, -1, (empty($object->multicurrency_code) ? $conf->currency : $object->multicurrency_code)).'</td><td>&nbsp;</td></tr>';
			}

			// Retained warranty : usually use on construction industry
			if (!empty($object->situation_final) && !empty($object->retained_warranty) && $displayWarranty) {
				// Billed - retained warranty
				if ($object->type == Facture::TYPE_SITUATION) {
					$retainedWarranty = $total_global_ttc * $object->retained_warranty / 100;
				} else {
					// Because one day retained warranty could be used on standard invoices
					$retainedWarranty = $object->total_ttc * $object->retained_warranty / 100;
				}

				$billedWithRetainedWarranty = $object->total_ttc - $retainedWarranty;

				print '<tr><td colspan="'.$nbcols.'" align="right">'.$langs->trans("ToPayOn", dol_print_date($object->date_lim_reglement, 'day')).' :</td><td align="right">'.price($billedWithRetainedWarranty).'</td><td>&nbsp;</td></tr>';

				// retained warranty
				print '<tr><td colspan="'.$nbcols.'" align="right">';
				print $langs->trans("RetainedWarranty").' ('.$object->retained_warranty.'%)';
				print !empty($object->retained_warranty_date_limit) ? ' '.$langs->trans("ToPayOn", dol_print_date($object->retained_warranty_date_limit, 'day')) : '';
				print ' :</td><td align="right">'.price($retainedWarranty).'</td><td>&nbsp;</td></tr>';
			}
		} else { // Credit note
			$resteapayeraffiche = $resteapayer;
			$cssforamountpaymentcomplete = 'amountpaymentneutral';

			// Total already paid back
			print '<tr><td colspan="'.$nbcols.'" class="right">';
			print '<span class="opacitymedium">'.$langs->trans('AlreadyPaidBack').'</span>';
			print '</td><td class="right"><span class="amount">'.price($sign * $totalpaid).'</span></td><td>&nbsp;</td></tr>';

			// Billed
			print '<tr><td colspan="'.$nbcols.'" class="right"><span class="opacitymedium">'.$langs->trans("Billed").'</span></td><td class="right">'.price($sign * $object->total_ttc).'</td><td>&nbsp;</td></tr>';

			// Remainder to pay back
			print '<tr><td colspan="'.$nbcols.'" class="right">';
			print '<span class="opacitymedium">'.$langs->trans('RemainderToPayBack');
			if ($resteapayeraffiche > 0) {
				print ' ('.$langs->trans('NegativeIfExcessRefunded').')';
			}
			print '</span></td>';
			print '<td class="right'.($resteapayeraffiche ? ' amountremaintopayback' : (' '.$cssforamountpaymentcomplete)).'">'.price($sign * $resteapayeraffiche).'</td>';
			print '<td class="nowrap">&nbsp;</td></tr>';

			// Remainder to pay back Multicurrency
			if ($object->multicurrency_code != $conf->currency || $object->multicurrency_tx != 1) {
				print '<tr><td colspan="'.$nbcols.'" class="right">';
				print '<span class="opacitymedium">'.$langs->trans('RemainderToPayBackMulticurrency');
				if ($resteapayeraffiche > 0) {
					print ' ('.$langs->trans('NegativeIfExcessRefunded').')';
				}
				print '</span>';
				print '</td>';
				print '<td class="right'.($resteapayeraffiche ? ' amountremaintopayback' : (' '.$cssforamountpaymentcomplete)).'">'.(!empty($object->multicurrency_code) ? $object->multicurrency_code : $conf->currency).' '.price(price2num($sign * $object->multicurrency_tx * $resteapayeraffiche, 'MT')).'</td><td>&nbsp;</td></tr>';
			}

			// Sold credit note
			// print '<tr><td colspan="'.$nbcols.'" class="right">'.$langs->trans('TotalTTC').' :</td>';
			// print '<td class="right" style="border: 1px solid;" bgcolor="#f0f0f0"><b>'.price($sign *
			// $object->total_ttc).'</b></td><td>&nbsp;</td></tr>';
		}

		print '</table>';
		print '</div>';

		// Margin Infos
		if (isModEnabled('margin')) {
			$formmargin->displayMarginInfos($object);
		}

		print '</div>';
		print '</div>';

		print '<div class="clearboth"></div><br><br>';

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

		// Get object lines
		$result = $object->getLinesArray();

		// Add products/services form
		//$forceall = 1;
		global $inputalsopricewithtax;
		$inputalsopricewithtax = 1;

		// Show global modifiers for situation invoices
		if (getDolGlobalString('INVOICE_USE_SITUATION')) {
			if ($object->situation_cycle_ref && $object->status == 0) {
				print '<!-- Area to change globally the situation percent -->'."\n";
				print '<div class="div-table-responsive">';

				print '<form name="updatealllines" id="updatealllines" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'#updatealllines" method="POST">';
				print '<input type="hidden" name="token" value="'.newToken().'" />';
				print '<input type="hidden" name="action" value="updatealllines" />';
				print '<input type="hidden" name="id" value="'.$object->id.'" />';
				print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';

				print '<table id="tablelines_all_progress" class="noborder noshadow" width="100%">';

				print '<tr class="liste_titre nodrag nodrop">';

				// Adds a line numbering column
				if (getDolGlobalString('MAIN_VIEW_LINE_NUMBER')) {
					print '<td align="center" width="5">&nbsp;</td>';
				}
				print '<td class="minwidth500imp">'.$langs->trans('ModifyAllLines').'</td>';
				print '<td class="right">'.$langs->trans('Progress').'</td>';
				print '<td>&nbsp;</td>';
				print "</tr>\n";

				print '<tr class="nodrag nodrop">';
				// Adds a line numbering column
				if (getDolGlobalString('MAIN_VIEW_LINE_NUMBER')) {
					print '<td align="center" width="5">&nbsp;</td>';
				}
				print '<td>&nbsp;</td>';
				print '<td class="nowrap right"><input type="text" size="1" value="" name="all_progress">%</td>';
				print '<td class="right"><input type="submit" class="button" name="all_percent" value="Modifier" /></td>';
				print '</tr>';

				print '</table>';

				print '</form>';

				print '</div>';
			}
		}

		print '	<form name="addproduct" id="addproduct" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="POST">
		<input type="hidden" name="token" value="' . newToken().'">
		<input type="hidden" name="action" value="' . (($action != 'editline') ? 'addline' : 'updateline').'">
		<input type="hidden" name="mode" value="">
		<input type="hidden" name="page_y" value="">
		<input type="hidden" name="id" value="' . $object->id.'">
		<input type="hidden" name="backtopage" value="'.$backtopage.'">
		';

		if (!empty($conf->use_javascript_ajax) && $object->status == 0) {
			include DOL_DOCUMENT_ROOT.'/core/tpl/ajaxrow.tpl.php';
		}

		print '<div class="div-table-responsive-no-min">';
		print '<table id="tablelines" class="noborder noshadow" width="100%">';

		// Show object lines
		if (!empty($object->lines)) {
			$object->printObjectLines($action, $mysoc, $soc, $lineid, 1);
		}

		// Form to add new line
		if ($object->status == 0 && $usercancreate && $action != 'valid') {
			if ($action != 'editline' && $action != 'selectlines') {
				// Add free products/services

				$parameters = array();
				$reshook = $hookmanager->executeHooks('formAddObjectLine', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
				if ($reshook < 0) {
					setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
				}
				if (empty($reshook)) {
					$object->formAddObjectLine(1, $mysoc, $soc);
				}
			} else {
				$parameters = array();
				$reshook = $hookmanager->executeHooks('formEditObjectLine', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
			}
		}

		print "</table>\n";
		print "</div>";

		print "</form>\n";
	}
	print dol_get_fiche_end();


	// Actions buttons

	if ($action != 'prerelance' && $action != 'presend' && $action != 'valid' && $action != 'editline') {
		print '<div class="tabsAction">';

		$parameters = array();
		$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		if (empty($reshook)) {
			$params = array(
				'attr' => array(
					'class' => 'classfortooltip',
					'title' => ''
				)
			);
			// Edit a validated invoice without any payment and not transferred to accounting
			if ($object->status == Facture::STATUS_VALIDATED) {
				// We check if lines of invoice are not already transferred into accountancy
				$ventilExportCompta = $object->getVentilExportCompta();

				if ($ventilExportCompta == 0) {
					if (getDolGlobalString('INVOICE_CAN_BE_EDITED_EVEN_IF_PAYMENT_DONE') || ($resteapayer == price2num($object->total_ttc, 'MT', 1) && empty($object->paye))) {
						if (!$objectidnext && $object->is_last_in_cycle()) {
							if ($usercanunvalidate) {
								unset($params['attr']['title']);
								print dolGetButtonAction($langs->trans('Modify'), '', 'default', $_SERVER['PHP_SELF'].'?facid='.$object->id.'&action=modif&token='.newToken(), '', true, $params);
							} else {
								$params['attr']['title'] = $langs->trans('NotEnoughPermissions');
								print dolGetButtonAction($langs->trans('Modify'), '', 'default', $_SERVER['PHP_SELF'].'?facid='.$object->id.'&action=modif&token='.newToken(), '', false, $params);
							}
						} elseif (!$object->is_last_in_cycle()) {
							$params['attr']['title'] = $langs->trans('NotLastInCycle');
							print dolGetButtonAction($langs->trans('Modify'), '', 'default', '#', '', false, $params);
						} else {
							$params['attr']['title'] = $langs->trans('DisabledBecauseReplacedInvoice');
							print dolGetButtonAction($langs->trans('Modify'), '', 'default', '#', '', false, $params);
						}
					}
				} else {
					$params['attr']['title'] = $langs->trans('DisabledBecauseDispatchedInBookkeeping');
					print dolGetButtonAction($langs->trans('Modify'), '', 'default', '#', '', false, $params);
				}
			}

			$discount = new DiscountAbsolute($db);
			$result = $discount->fetch(0, $object->id);

			// Reopen an invoice
			if ((($object->type == Facture::TYPE_STANDARD || $object->type == Facture::TYPE_REPLACEMENT)
				|| ($object->type == Facture::TYPE_CREDIT_NOTE && empty($discount->id))
				|| ($object->type == Facture::TYPE_DEPOSIT && empty($discount->id))
				|| ($object->type == Facture::TYPE_SITUATION && empty($discount->id)))
				&& ($object->status == Facture::STATUS_CLOSED || $object->status == Facture::STATUS_ABANDONED || ($object->status == 1 && $object->paye == 1))   // Condition ($object->status == 1 && $object->paye == 1) should not happened but can be found due to corrupted data
				&& ((!getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && $usercancreate) || $usercanreopen)) {				// A paid invoice (partially or completely)
				if ($object->close_code != 'replaced' || (!$objectidnext)) { 				// Not replaced by another invoice or replaced but the replacement invoice has been deleted
					unset($params['attr']['title']);
					print dolGetButtonAction($langs->trans('ReOpen'), '', 'default', $_SERVER['PHP_SELF'].'?facid='.$object->id.'&action=reopen&token='.newToken(), '', true, $params);
				} else {
					$params['attr']['title'] = $langs->trans("DisabledBecauseReplacedInvoice");
					print dolGetButtonAction($langs->trans('ReOpen'), '', 'default', '#', '', false, $params);
				}
			}

			// Create contract
			if (getDolGlobalString('CONTRACT_CREATE_FROM_INVOICE')) {
				if (isModEnabled('contract') && $object->status == Facture::STATUS_VALIDATED) {
					$langs->load("contracts");

					if ($usercancreatecontract) {
						print '<a class="butAction" href="' . DOL_URL_ROOT . '/contrat/card.php?action=create&amp;origin=' . $object->element . '&amp;originid=' . $object->id . '&amp;socid=' . $object->socid . '">' . $langs->trans('AddContract') . '</a>';
					}
				}
			}

			// Validate
			if ($object->status == Facture::STATUS_DRAFT && count($object->lines) > 0 && ((($object->type == Facture::TYPE_STANDARD || $object->type == Facture::TYPE_REPLACEMENT || $object->type == Facture::TYPE_DEPOSIT || $object->type == Facture::TYPE_PROFORMA || $object->type == Facture::TYPE_SITUATION) && (getDolGlobalString('FACTURE_ENABLE_NEGATIVE') || $object->total_ttc >= 0)) || ($object->type == Facture::TYPE_CREDIT_NOTE && $object->total_ttc <= 0))) {
				if ($usercanvalidate) {
					unset($params['attr']['title']);
					print dolGetButtonAction($langs->trans('Validate'), '', 'default', $_SERVER["PHP_SELF"].'?facid='.$object->id.'&action=valid&token='.newToken(), '', true, $params);
				}
			}

			// Send by mail
			if (empty($user->socid)) {
				if (($object->status == Facture::STATUS_VALIDATED || $object->status == Facture::STATUS_CLOSED) || getDolGlobalString('FACTURE_SENDBYEMAIL_FOR_ALL_STATUS')) {
					if ($objectidnext) {
						print '<span class="butActionRefused classfortooltip" title="'.$langs->trans("DisabledBecauseReplacedInvoice").'">'.$langs->trans('SendMail').'</span>';
					} else {
						if ($usercansend) {
							unset($params['attr']['title']);
							print dolGetButtonAction('', $langs->trans('SendMail'), 'default', $_SERVER['PHP_SELF'].'?facid='.$object->id.'&action=presend&mode=init#formmailbeforetitle', '', true, $params);
						} else {
							unset($params['attr']['title']);
							print dolGetButtonAction('', $langs->trans('SendMail'), 'default', '#', '', false, $params);
						}
					}
				}
			}

			// Request a direct debit order
			if ($object->status > Facture::STATUS_DRAFT && $object->paye == 0 && $num == 0) {
				if ($resteapayer > 0) {
					if ($usercancreatewithdrarequest) {
						if (!$objectidnext && $object->close_code != 'replaced') { 				// Not replaced by another invoice
							print '<a class="butAction" href="'.DOL_URL_ROOT.'/compta/facture/prelevement.php?facid='.$object->id.'" title="'.dol_escape_htmltag($langs->trans("MakeWithdrawRequest")).'">'.$langs->trans("MakeWithdrawRequest").'</a>';
						} else {
							print '<span class="butActionRefused classfortooltip" title="'.$langs->trans("DisabledBecauseReplacedInvoice").'">'.$langs->trans('MakeWithdrawRequest').'</span>';
						}
					} else {
						//print '<a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans("MakeWithdrawRequest").'</a>';
					}
				} else {
					//print '<a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("AmountMustBePositive")).'">'.$langs->trans("MakeWithdrawRequest").'</a>';
				}
			}

			// POS Ticket
			if (isModEnabled('takepos') && $object->module_source == 'takepos') {
				$langs->load("cashdesk");
				$receipt_url = DOL_URL_ROOT."/takepos/receipt.php";
				print '<a target="_blank" rel="noopener noreferrer" class="butAction" href="'.$receipt_url.'?facid='.((int) $object->id).'">'.$langs->trans('POSTicket').'</a>';
			}

			// Create payment
			if ($object->type != Facture::TYPE_CREDIT_NOTE && $object->status == 1 && $object->paye == 0 && $usercanissuepayment) {
				if ($objectidnext) {
					print '<span class="butActionRefused classfortooltip" title="'.$langs->trans("DisabledBecauseReplacedInvoice").'">'.$langs->trans('DoPayment').'</span>';
				} else {
					if ($object->type == Facture::TYPE_DEPOSIT && $resteapayer == 0) {
						// For down payment, we refuse to receive more than amount to pay.
						$params['attr']['title'] = $langs->trans('DisabledBecauseRemainderToPayIsZero');
						print dolGetButtonAction($langs->trans('DoPayment'), '', 'default', '#', '', false, $params);
					} else {
						// Sometimes we can receive more, so we accept to enter more and will offer a button to convert into discount (but it is not a credit note, just a prepayment done)
						//print '<a class="butAction" href="'.DOL_URL_ROOT.'/compta/paiement.php?facid='.$object->id.'&amp;action=create&amp;accountid='.$object->fk_account.'">'.$langs->trans('DoPayment').'</a>';
						unset($params['attr']['title']);
						print dolGetButtonAction($langs->trans('DoPayment'), '', 'default', DOL_URL_ROOT.'/compta/paiement.php?facid='.$object->id.'&amp;action=create'.($object->fk_account > 0 ? '&amp;accountid='.$object->fk_account : ''), '', true, $params);
					}
				}
			}

			$sumofpayment = $totalpaid;
			$sumofpaymentall = $totalpaid + $totalcreditnotes + $totaldeposits;

			// Reverse back money or convert to reduction
			if ($object->type == Facture::TYPE_CREDIT_NOTE || $object->type == Facture::TYPE_DEPOSIT || $object->type == Facture::TYPE_STANDARD || $object->type == Facture::TYPE_SITUATION) {
				// For credit note only
				if ($object->type == Facture::TYPE_CREDIT_NOTE && $object->status == Facture::STATUS_VALIDATED && $object->paye == 0 && $usercanissuepayment) {
					if ($resteapayer == 0) {
						print '<span class="butActionRefused classfortooltip" title="'.$langs->trans("DisabledBecauseRemainderToPayIsZero").'">'.$langs->trans('DoPaymentBack').'</span>';
					} else {
						print '<a class="butAction" href="'.DOL_URL_ROOT.'/compta/paiement.php?facid='.$object->id.'&amp;action=create&amp;accountid='.$object->fk_account.'">'.$langs->trans('DoPaymentBack').'</a>';
					}
				}

				// For standard invoice with excess received
				if (($object->type == Facture::TYPE_STANDARD || $object->type == Facture::TYPE_SITUATION) && $object->status == Facture::STATUS_VALIDATED && empty($object->paye) && $resteapayer < 0 && $usercancreate && empty($discount->id)) {
					print '<a class="butAction'.($conf->use_javascript_ajax ? ' reposition' : '').'" href="'.$_SERVER["PHP_SELF"].'?facid='.$object->id.'&action=converttoreduc&token='.newToken().'">'.$langs->trans('ConvertExcessReceivedToReduc').'</a>';
				}
				// For credit note
				if ($object->type == Facture::TYPE_CREDIT_NOTE && $object->status == Facture::STATUS_VALIDATED && $object->paye == 0 && $usercancreate
					&& (getDolGlobalString('INVOICE_ALLOW_REUSE_OF_CREDIT_WHEN_PARTIALLY_REFUNDED') || $sumofpayment == 0) && $object->total_ht < 0
				) {
					print '<a class="butAction classfortooltip'.($conf->use_javascript_ajax ? ' reposition' : '').'" href="'.$_SERVER["PHP_SELF"].'?facid='.$object->id.'&action=converttoreduc&token='.newToken().'" title="'.dol_escape_htmltag($langs->trans("ConfirmConvertToReduc2")).'">'.$langs->trans('ConvertToReduc').'</a>';
				}
				// For down payment invoice (deposit)

				if ($object->type == Facture::TYPE_DEPOSIT && $usercancreate && $object->status > Facture::STATUS_DRAFT && empty($discount->id)) {
					// We can close a down payment only if paid amount is same than amount of down payment (by definition). We can bypass this if hidden and unstable option DEPOSIT_AS_CREDIT_AVAILABLE_EVEN_UNPAID is set.
					if (price2num($object->total_ttc, 'MT') == price2num($sumofpaymentall, 'MT') || getDolGlobalInt('DEPOSIT_AS_CREDIT_AVAILABLE_EVEN_UNPAID') || ($object->type == Facture::STATUS_ABANDONED && in_array($object->close_code, array('bankcharge', 'discount_vat', 'other')))) {
						print '<a class="butAction'.($conf->use_javascript_ajax ? ' reposition' : '').'" href="'.$_SERVER["PHP_SELF"].'?facid='.$object->id.'&action=converttoreduc&token='.newToken().'">'.$langs->trans('ConvertToReduc').'</a>';
					} else {
						print '<span class="butActionRefused classfortooltip" title="'.$langs->trans("AmountPaidMustMatchAmountOfDownPayment").'">'.$langs->trans('ConvertToReduc').'</span>';
					}
				}
			}

			// Classify paid
			if ($object->status == Facture::STATUS_VALIDATED && $object->paye == 0 && $usercanissuepayment && (
				($object->type != Facture::TYPE_CREDIT_NOTE && $object->type != Facture::TYPE_DEPOSIT && ($resteapayer <= 0 || (getDolGlobalString('INVOICE_CAN_SET_PAID_EVEN_IF_PARTIALLY_PAID') && $object->total_ttc == $resteapayer))) ||
				($object->type == Facture::TYPE_CREDIT_NOTE && $resteapayer >= 0) ||
				($object->type == Facture::TYPE_DEPOSIT && $object->total_ttc > 0)
			)
			) {
				if ($object->type == Facture::TYPE_DEPOSIT && price2num($object->total_ttc, 'MT') != price2num($sumofpaymentall, 'MT')) {
					// We can close a down payment only if paid amount is same than amount of down payment (by definition)
					$params['attr']['title'] = $langs->trans('AmountPaidMustMatchAmountOfDownPayment');
					print dolGetButtonAction($langs->trans('ClassifyPaid'), '', 'default', '#', '', false, $params);
				} else {
					unset($params['attr']['title']);
					print dolGetButtonAction($langs->trans('ClassifyPaid'), '', 'default', $_SERVER['PHP_SELF'].'?facid='.$object->id.'&action=paid&token='.newToken(), '', true, $params);
				}
			}

			// Classify 'closed not completely paid' (possible if validated and not yet filed paid)
			if ($object->status == Facture::STATUS_VALIDATED && $object->paye == 0 && $resteapayer > 0 && (!getDolGlobalString('INVOICE_CAN_SET_PAID_EVEN_IF_PARTIALLY_PAID') || $resteapayer != $object->total_ttc) && $usercanissuepayment) {
				if ($totalpaid > 0 || $totalcreditnotes > 0) {
					// If one payment or one credit note was linked to this invoice
					print '<a class="butAction'.($conf->use_javascript_ajax ? ' reposition' : '').'" href="'.$_SERVER['PHP_SELF'].'?facid='.$object->id.'&action=paid&token='.newToken().'">'.$langs->trans('ClassifyPaidPartially').'</a>';
				} else {
					if (!getDolGlobalString('INVOICE_CAN_NEVER_BE_CANCELED')) {
						if ($objectidnext) {
							print '<span class="butActionRefused classfortooltip" title="'.$langs->trans("DisabledBecauseReplacedInvoice").'">'.$langs->trans('ClassifyCanceled').'</span>';
						} else {
							print '<a class="butAction'.($conf->use_javascript_ajax ? ' reposition' : '').'" href="'.$_SERVER['PHP_SELF'].'?facid='.$object->id.'&amp;action=canceled">'.$langs->trans('ClassifyCanceled').'</a>';
						}
					}
				}
			}

			// Create a credit note
			if (($object->type == Facture::TYPE_STANDARD || ($object->type == Facture::TYPE_DEPOSIT && !getDolGlobalString('FACTURE_DEPOSITS_ARE_JUST_PAYMENTS')) || $object->type == Facture::TYPE_PROFORMA) && $object->status > 0 && $usercancreate) {
				if (!$objectidnext) {
					print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?socid='.$object->socid.'&amp;fac_avoir='.$object->id.'&amp;action=create&amp;type=2'.($object->fk_project > 0 ? '&amp;projectid='.$object->fk_project : '').($object->entity > 0 ? '&amp;originentity='.$object->entity : '').'">'.$langs->trans("CreateCreditNote").'</a>';
				}
			}

			// For situation invoice with excess received
			if ($object->status > Facture::STATUS_DRAFT
				&& $object->type == Facture::TYPE_SITUATION
				&& ($object->total_ttc - $totalpaid - $totalcreditnotes - $totaldeposits) > 0
				&& $usercancreate
				&& !$objectidnext
				&& $object->is_last_in_cycle()
				&& getDolGlobalInt('INVOICE_USE_SITUATION_CREDIT_NOTE')
			) {
				if ($usercanunvalidate) {
					print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?socid='.$object->socid.'&amp;fac_avoir='.$object->id.'&amp;invoiceAvoirWithLines=1&amp;action=create&amp;type=2'.($object->fk_project > 0 ? '&amp;projectid='.$object->fk_project : '').'">'.$langs->trans("CreateCreditNote").'</a>';
				} else {
					print '<span class="butActionRefused classfortooltip" title="'.$langs->trans("NotEnoughPermissions").'">'.$langs->trans("CreateCreditNote").'</span>';
				}
			}

			// Clone
			if (($object->type == Facture::TYPE_STANDARD || $object->type == Facture::TYPE_DEPOSIT || $object->type == Facture::TYPE_PROFORMA) && $usercancreate) {
				unset($params['attr']['title']);
				print dolGetButtonAction($langs->trans('ToClone'), '', 'default', $_SERVER['PHP_SELF'].'?facid='.$object->id.'&action=clone&object=invoice&token='.newToken(), '', true, $params);
			}

			// Clone as predefined / Create template
			if (($object->type == Facture::TYPE_STANDARD || $object->type == Facture::TYPE_DEPOSIT || $object->type == Facture::TYPE_PROFORMA) && $object->status == 0 && $usercancreate) {
				if (!$objectidnext && count($object->lines) > 0) {
					unset($params['attr']['title']);
					print dolGetButtonAction($langs->trans('ChangeIntoRepeatableInvoice'), '', 'default', DOL_URL_ROOT.'/compta/facture/card-rec.php?facid='.$object->id.'&amp;action=create', '', true, $params);
				}
			}

			// Remove situation from cycle
			if (in_array($object->status, array(Facture::STATUS_CLOSED, Facture::STATUS_VALIDATED))
				&& $object->type == Facture::TYPE_SITUATION
				&& $usercancreate
				&& !$objectidnext
				&& $object->situation_counter > 1
				&& $object->is_last_in_cycle()
				&& $usercanunvalidate
			) {
				if (($object->total_ttc - $totalcreditnotes) == 0) {
					print '<a id="butSituationOut" class="butAction" href="'.$_SERVER['PHP_SELF'].'?facid='.$object->id.'&amp;action=situationout">'.$langs->trans("RemoveSituationFromCycle").'</a>';
				} else {
					print '<a id="butSituationOutRefused" class="butActionRefused classfortooltip" href="#" title="'.$langs->trans("DisabledBecauseNotEnouthCreditNote").'" >'.$langs->trans("RemoveSituationFromCycle").'</a>';
				}
			}

			// Create next situation invoice
			if ($usercancreate && ($object->type == 5) && ($object->status == 1 || $object->status == 2)) {
				if ($object->is_last_in_cycle() && $object->situation_final != 1) {
					print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=create&amp;type=5&amp;origin=facture&amp;originid='.$object->id.'&amp;socid='.$object->socid.'" >'.$langs->trans('CreateNextSituationInvoice').'</a>';
				} elseif (!$object->is_last_in_cycle()) {
					print '<a class="butActionRefused classfortooltip" href="#" title="'.$langs->trans("DisabledBecauseNotLastInCycle").'">'.$langs->trans('CreateNextSituationInvoice').'</a>';
				} else {
					print '<a class="butActionRefused classfortooltip" href="#" title="'.$langs->trans("DisabledBecauseFinal").'">'.$langs->trans('CreateNextSituationInvoice').'</a>';
				}
			}

			// Delete
			$isErasable = $object->is_erasable();
			$htmltooltip = '';
			if ($isErasable == -4) {
				$htmltooltip = $langs->trans('DisabledBecausePayments');
			} elseif ($isErasable == -3) {
				$htmltooltip = $langs->trans('DisabledBecauseNotLastSituationInvoice');
			} elseif ($isErasable == -2) {
				$htmltooltip = $langs->trans('DisabledBecauseNotLastInvoice');
			} elseif ($isErasable == -1) {
				$htmltooltip = $langs->trans('DisabledBecauseDispatchedInBookkeeping');
			} elseif ($isErasable <= 0) {	// Any other cases
				$htmltooltip = $langs->trans('DisabledBecauseNotErasable');
			} elseif ($objectidnext) {
				$htmltooltip = $langs->trans('DisabledBecauseReplacedInvoice');
			}
			if ($usercandelete || ($usercancreate && $isErasable == 1)) {	// isErasable = 1 means draft with temporary ref (draft can always be deleted with no need of permissions)
				$enableDelete = false;
				$deleteHref = '#';
				if ($isErasable > 0 && ! $objectidnext) {
					$deleteHref = $_SERVER["PHP_SELF"].'?facid='.$object->id.'&action=delete&token='.newToken();
					$enableDelete = true;
				}
				unset($params['attr']['title']);
				print dolGetButtonAction($htmltooltip, $langs->trans('Delete'), 'delete', $deleteHref, '', $enableDelete, $params);
			} else {
				unset($params['attr']['title']);
				print dolGetButtonAction($htmltooltip, $langs->trans('Delete'), 'delete', '#', '', false);
			}
		}
		print '</div>';
	}

	// Select mail models is same action as presend
	if (GETPOST('modelselected', 'alpha')) {
		$action = 'presend';
	}
	if ($action != 'prerelance' && $action != 'presend') {
		print '<div class="fichecenter"><div class="fichehalfleft">';
		print '<a name="builddoc"></a>'; // ancre

		// Generated documents
		$filename = dol_sanitizeFileName($object->ref);
		$filedir = $conf->invoice->multidir_output[$object->entity].'/'.dol_sanitizeFileName($object->ref);
		$urlsource = $_SERVER['PHP_SELF'].'?facid='.$object->id;
		$genallowed = $usercanread;
		$delallowed = $usercancreate;

		print $formfile->showdocuments(
			'facture',
			$filename,
			$filedir,
			$urlsource,
			$genallowed,
			$delallowed,
			$object->model_pdf,
			1,
			0,
			0,
			28,
			0,
			'',
			'',
			'',
			$soc->default_lang,
			'',
			$object,
			0,
			'remove_file_comfirm'
		);

		$somethingshown = $formfile->numoffiles;

		// Show links to link elements
		$linktoelem = $form->showLinkToObjectBlock($object, array(), array('invoice'));

		$compatibleImportElementsList = false;
		if ($usercancreate
			&& $object->status == Facture::STATUS_DRAFT
			&& ($object->type == Facture::TYPE_STANDARD || $object->type == Facture::TYPE_REPLACEMENT || $object->type == Facture::TYPE_DEPOSIT || $object->type == Facture::TYPE_PROFORMA || $object->type == Facture::TYPE_SITUATION)) {
			$compatibleImportElementsList = array('commande', 'propal'); // import from linked elements
		}
		$somethingshown = $form->showLinkedObjectBlock($object, $linktoelem, $compatibleImportElementsList);

		// Show online payment link
		// The list can be complete by the hook 'doValidatePayment' executed inside getValidOnlinePaymentMethods()
		include_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';
		$validpaymentmethod = getValidOnlinePaymentMethods('');
		$useonlinepayment = count($validpaymentmethod);

		if ($object->status != Facture::STATUS_DRAFT && $useonlinepayment) {
			print '<br><!-- Link to pay -->'."\n";
			require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';
			print showOnlinePaymentUrl('invoice', $object->ref).'<br>';
		}

		print '</div><div class="fichehalfright">';

		$MAXEVENT = 10;

		$morehtmlcenter = '<div class="nowraponall">';
		$morehtmlcenter .= dolGetButtonTitle($langs->trans('FullConversation'), '', 'fa fa-comments imgforviewmode', DOL_URL_ROOT.'/compta/facture/messaging.php?id='.$object->id);
		$morehtmlcenter .= dolGetButtonTitle($langs->trans('FullList'), '', 'fa fa-bars imgforviewmode', DOL_URL_ROOT.'/compta/facture/agenda.php?id='.$object->id);
		$morehtmlcenter .= '</div>';

		// List of actions on element
		include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
		$formactions = new FormActions($db);
		$somethingshown = $formactions->showactions($object, 'invoice', $socid, 1, '', $MAXEVENT, '', $morehtmlcenter); // Show all action for thirdparty

		print '</div></div>';
	}


	// Presend form
	$modelmail = 'facture_send';
	$defaulttopic = 'SendBillRef';
	$diroutput = $conf->invoice->multidir_output[$object->entity];
	$trackid = 'inv'.$object->id;

	include DOL_DOCUMENT_ROOT.'/core/tpl/card_presend.tpl.php';
}

// End of page
llxFooter();
$db->close();
