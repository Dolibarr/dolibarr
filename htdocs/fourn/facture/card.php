<?php
/* Copyright (C) 2002-2005  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2020	Laurent Destailleur 	<eldy@users.sourceforge.net>
 * Copyright (C) 2004		Christophe Combelles	<ccomb@free.fr>
 * Copyright (C) 2005		Marc Barilley			<marc@ocebo.fr>
 * Copyright (C) 2005-2013	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2010-2019	Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2013-2015	Philippe Grand			<philippe.grand@atoo-net.com>
 * Copyright (C) 2013		Florian Henry			<florian.henry@open-concept.pro>
 * Copyright (C) 2014-2016  Marcos García			<marcosgdf@gmail.com>
 * Copyright (C) 2016-2021	Alexandre Spangaro		<aspangaro@open-dsi.fr>
 * Copyright (C) 2018-2021  Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2019       Ferran Marcet	        <fmarcet@2byte.es>
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
 *	\file       htdocs/fourn/facture/card.php
 *	\ingroup    facture, fournisseur
 *	\brief      Page for supplier invoice card (view, edit, validate)
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/supplier_invoice/modules_facturefournisseur.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/paiementfourn.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/discount.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/fourn.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
if (!empty($conf->product->enabled)) {
	require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
}
if (!empty($conf->projet->enabled)) {
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
}

if (!empty($conf->variants->enabled)) {
	require_once DOL_DOCUMENT_ROOT.'/variants/class/ProductCombination.class.php';
}
if (!empty($conf->accounting->enabled)) {
	require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingjournal.class.php';
}


$langs->loadLangs(array('bills', 'compta', 'suppliers', 'companies', 'products', 'banks', 'admin'));
if (!empty($conf->incoterm->enabled)) {
	$langs->load('incoterm');
}

$id = (GETPOST('facid', 'int') ? GETPOST('facid', 'int') : GETPOST('id', 'int'));
$socid = GETPOST('socid', 'int');
$action		= GETPOST('action', 'aZ09');
$confirm	= GETPOST("confirm");
$ref = GETPOST('ref', 'alpha');
$cancel		= GETPOST('cancel', 'alpha');
$lineid		= GETPOST('lineid', 'int');
$projectid = GETPOST('projectid', 'int');
$origin		= GETPOST('origin', 'alpha');
$originid = GETPOST('originid', 'int');
$fac_rec = GETPOST('fac_rec', 'int');

// PDF
$hidedetails = (GETPOST('hidedetails', 'int') ? GETPOST('hidedetails', 'int') : (!empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS) ? 1 : 0));
$hidedesc = (GETPOST('hidedesc', 'int') ? GETPOST('hidedesc', 'int') : (!empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DESC) ? 1 : 0));
$hideref = (GETPOST('hideref', 'int') ? GETPOST('hideref', 'int') : (!empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_REF) ? 1 : 0));

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('invoicesuppliercard', 'globalcard'));

$object = new FactureFournisseur($db);
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

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
}

// Security check
$socid = '';
if (!empty($user->socid)) {
	$socid = $user->socid;
}
$isdraft = (($object->statut == FactureFournisseur::STATUS_DRAFT) ? 1 : 0);
$result = restrictedArea($user, 'fournisseur', $id, 'facture_fourn', 'facture', 'fk_soc', 'rowid', $isdraft);

// Common permissions
$usercanread = ($user->rights->fournisseur->facture->lire || $user->rights->supplier_invoice->lire);
$usercancreate = ($user->rights->fournisseur->facture->creer || $user->rights->supplier_invoice->creer);
$usercandelete = ($user->rights->fournisseur->facture->supprimer || $user->rights->supplier_invoice->supprimer);

// Advanced permissions
$usercanvalidate = ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && !empty($usercancreate)) || (!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && !empty($user->rights->fournisseur->supplier_invoice_advance->validate)));
$usercansend = (empty($conf->global->MAIN_USE_ADVANCED_PERMS) || $user->rights->fournisseur->supplier_invoice_advance->send);

// Permissions for includes
$permissionnote = $usercancreate; // Used by the include of actions_setnotes.inc.php
$permissiondellink = $usercancreate; // Used by the include of actions_dellink.inc.php
$permissiontoedit = $usercancreate; // Used by the include of actions_lineupdown.inc.php
$permissiontoadd = $usercancreate; // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php

$error = 0;


/*
 * Actions
 */

$parameters = array('socid'=>$socid);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	if ($cancel) {
		if (!empty($backtopage)) {
			header("Location: ".$backtopage);
			exit;
		}
		$action = '';
	}

	include DOL_DOCUMENT_ROOT.'/core/actions_setnotes.inc.php'; // Must be include, not include_once

	include DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php'; // Must be include, not include_once

	include DOL_DOCUMENT_ROOT.'/core/actions_lineupdown.inc.php'; // Must be include, not include_once

	// Link invoice to order
	if (GETPOST('linkedOrder') && empty($cancel) && $id > 0) {
		$object->fetch($id);
		$object->fetch_thirdparty();
		$result = $object->add_object_linked('order_supplier', GETPOST('linkedOrder'));
	}

	// Action clone object
	if ($action == 'confirm_clone' && $confirm == 'yes' && $permissiontoadd) {
		$objectutil = dol_clone($object, 1); // To avoid to denaturate loaded object when setting some properties for clone. We use native clone to keep this->db valid.

		if (GETPOST('newsupplierref', 'alphanohtml')) {
			$objectutil->ref_supplier = GETPOST('newsupplierref', 'alphanohtml');
		}
		$objectutil->date = dol_mktime(12, 0, 0, GETPOST('newdatemonth', 'int'), GETPOST('newdateday', 'int'), GETPOST('newdateyear', 'int'));

		$result = $objectutil->createFromClone($user, $id);
		if ($result > 0) {
			header("Location: ".$_SERVER['PHP_SELF'].'?id='.$result);
			exit;
		} else {
			$langs->load("errors");
			setEventMessages($objectutil->error, $objectutil->errors, 'errors');
			$action = '';
		}
	} elseif ($action == 'confirm_valid' && $confirm == 'yes' && $usercanvalidate) {
		$idwarehouse = GETPOST('idwarehouse');

		$object->fetch($id);
		$object->fetch_thirdparty();

		$qualified_for_stock_change = 0;
		if (empty($conf->global->STOCK_SUPPORTS_SERVICES)) {
			$qualified_for_stock_change = $object->hasProductsOrServices(2);
		} else {
			$qualified_for_stock_change = $object->hasProductsOrServices(1);
		}

		// Check parameters
		if (!empty($conf->stock->enabled) && !empty($conf->global->STOCK_CALCULATE_ON_SUPPLIER_BILL) && $qualified_for_stock_change) {
			$langs->load("stocks");
			if (!$idwarehouse || $idwarehouse == -1) {
				$error++;
				setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("Warehouse")), null, 'errors');
				$action = '';
			}
		}

		if (!$error) {
			$result = $object->validate($user, '', $idwarehouse);
			if ($result < 0) {
				setEventMessages($object->error, $object->errors, 'errors');
			} else {
				// Define output language
				if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
					$outputlangs = $langs;
					$newlang = '';
					if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
						$newlang = GETPOST('lang_id', 'aZ09');
					}
					if ($conf->global->MAIN_MULTILANGS && empty($newlang)) {
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
						dol_print_error($db, $result);
					}
				}
			}
		}
	} elseif ($action == 'confirm_delete' && $confirm == 'yes') {
		$object->fetch($id);
		$object->fetch_thirdparty();

		$isErasable = $object->is_erasable();

		if (($usercandelete && $isErasable > 0) || ($usercancreate && $isErasable == 1)) {
			$result = $object->delete($user);
			if ($result > 0) {
				header('Location: list.php?restore_lastsearch_values=1');
				exit;
			} else {
				setEventMessages($object->error, $object->errors, 'errors');
			}
		}
	} elseif ($action == 'confirm_deleteline' && $confirm == 'yes' && $usercancreate) {
		// Remove a product line
		$result = $object->deleteline($lineid);
		if ($result > 0) {
			// Define output language
			/*$outputlangs = $langs;
			$newlang = '';
			if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id','aZ09'))
				$newlang = GETPOST('lang_id','aZ09');
			if ($conf->global->MAIN_MULTILANGS && empty($newlang))
				$newlang = $object->thirdparty->default_lang;
			if (! empty($newlang)) {
				$outputlangs = new Translate("", $conf);
				$outputlangs->setDefaultLang($newlang);
			}
			if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
				$ret = $object->fetch($object->id); // Reload to get new records
				$object->generateDocument($object->model_pdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
			}*/

			header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
			exit;
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
			/* Fix bug 1485 : Reset action to avoid asking again confirmation on failure */
			$action = '';
		}
	} elseif ($action == 'unlinkdiscount' && $usercancreate) {
		// Delete link of credit note to invoice
		$discount = new DiscountAbsolute($db);
		$result = $discount->fetch(GETPOST("discountid"));
		$discount->unlink_invoice();
	} elseif ($action == 'confirm_paid' && $confirm == 'yes' && $usercancreate) {
		$object->fetch($id);
		$result = $object->setPaid($user);
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	} elseif ($action == 'confirm_paid_partially' && $confirm == 'yes') {
		// Classif "paid partialy"
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
	} elseif ($action == 'confirm_canceled' && $confirm == 'yes') {
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
	}

	// Set supplier ref
	if ($action == 'setref_supplier' && $usercancreate) {
		$object->ref_supplier = GETPOST('ref_supplier', 'alpha');

		if ($object->update($user) < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		} else {
			// Define output language
			$outputlangs = $langs;
			$newlang = '';
			if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
				$newlang = GETPOST('lang_id', 'aZ09');
			}
			if ($conf->global->MAIN_MULTILANGS && empty($newlang)) {
				$newlang = $object->thirdparty->default_lang;
			}
			if (!empty($newlang)) {
				$outputlangs = new Translate("", $conf);
				$outputlangs->setDefaultLang($newlang);
			}
			if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
				$ret = $object->fetch($object->id); // Reload to get new records
				$object->generateDocument($object->model_pdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
			}
		}
	}

	// payments conditions
	if ($action == 'setconditions' && $usercancreate) {
		$object->fetch($id);
		$object->cond_reglement_code = 0; // To clean property
		$object->cond_reglement_id = 0; // To clean property

		$error = 0;

		$db->begin();

		if (!$error) {
			$result = $object->setPaymentTerms(GETPOST('cond_reglement_id', 'int'));
			if ($result < 0) {
				$error++;
				setEventMessages($object->error, $object->errors, 'errors');
			}
		}

		if (!$error) {
			$old_date_echeance = $object->date_echeance;
			$new_date_echeance = $object->calculate_date_lim_reglement();
			if ($new_date_echeance > $old_date_echeance) {
				$object->date_echeance = $new_date_echeance;
			}
			if ($object->date_echeance < $object->date) {
				$object->date_echeance = $object->date;
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
	} elseif ($action == 'set_incoterms' && !empty($conf->incoterm->enabled)) {
		// Set incoterm
		$result = $object->setIncoterms(GETPOST('incoterm_id', 'int'), GETPOST('location_incoterms', 'alpha'));
	} elseif ($action == 'setmode' && $usercancreate) {
		// payment mode
		$result = $object->setPaymentMethods(GETPOST('mode_reglement_id', 'int'));
	} elseif ($action == 'setmulticurrencycode' && $usercancreate) {
		// Multicurrency Code
		$result = $object->setMulticurrencyCode(GETPOST('multicurrency_code', 'alpha'));
	} elseif ($action == 'setmulticurrencyrate' && $usercancreate) {
		// Multicurrency rate
		$result = $object->setMulticurrencyRate(price2num(GETPOST('multicurrency_tx', 'alpha')), GETPOST('calculation_mode', 'int'));
	} elseif ($action == 'setbankaccount' && $usercancreate) {
		// bank account
		$result = $object->setBankAccount(GETPOST('fk_account', 'int'));
	}


	if ($action == 'settransportmode' && ($user->rights->fournisseur->facture->creer || $user->rights->supplier_invoice->creer)) {
		// transport mode
		$result = $object->setTransportMode(GETPOST('transport_mode_id', 'int'));
	} elseif ($action == 'setlabel' && $usercancreate) {
		// Set label
		$object->fetch($id);
		$object->label = GETPOST('label');
		$result = $object->update($user);
		if ($result < 0) {
			dol_print_error($db);
		}
	} elseif ($action == 'setdatef' && $usercancreate) {
		$newdate = dol_mktime(0, 0, 0, GETPOST('datefmonth', 'int'), GETPOST('datefday', 'int'), GETPOST('datefyear', 'int'), 'tzserver');
		if ($newdate > (dol_now('tzuserrel') + (empty($conf->global->INVOICE_MAX_FUTURE_DELAY) ? 0 : $conf->global->INVOICE_MAX_FUTURE_DELAY))) {
			if (empty($conf->global->INVOICE_MAX_FUTURE_DELAY)) {
				setEventMessages($langs->trans("WarningInvoiceDateInFuture"), null, 'warnings');
			} else {
				setEventMessages($langs->trans("WarningInvoiceDateTooFarInFuture"), null, 'warnings');
			}
		}

		$object->fetch($id);

		$object->date = $newdate;
		$date_echence_calc = $object->calculate_date_lim_reglement();
		if (!empty($object->date_echeance) && $object->date_echeance < $date_echence_calc) {
			$object->date_echeance = $date_echence_calc;
		}
		if ($object->date_echeance && $object->date_echeance < $object->date) {
			$object->date_echeance = $object->date;
		}

		$result = $object->update($user);
		if ($result < 0) {
			dol_print_error($db, $object->error);
		}
	} elseif ($action == 'setdate_lim_reglement' && $usercancreate) {
		$object->fetch($id);
		$object->date_echeance = dol_mktime(12, 0, 0, GETPOST('date_lim_reglementmonth', 'int'), GETPOST('date_lim_reglementday', 'int'), GETPOST('date_lim_reglementyear', 'int'));
		if (!empty($object->date_echeance) && $object->date_echeance < $object->date) {
			$object->date_echeance = $object->date;
			setEventMessages($langs->trans("DatePaymentTermCantBeLowerThanObjectDate"), null, 'warnings');
		}
		$result = $object->update($user);
		if ($result < 0) {
			dol_print_error($db, $object->error);
		}
	} elseif ($action == "setabsolutediscount" && $usercancreate) {
		// We use the credit to reduce amount of invoice
		if (GETPOST("remise_id", "int")) {
			$ret = $object->fetch($id);
			if ($ret > 0) {
				$result = $object->insert_discount(GETPOST("remise_id", "int"));
				if ($result < 0) {
					setEventMessages($object->error, $object->errors, 'errors');
				}
			} else {
				dol_print_error($db, $object->error);
			}
		}
		// We use the credit to reduce remain to pay
		if (GETPOST("remise_id_for_payment", "int")) {
			require_once DOL_DOCUMENT_ROOT.'/core/class/discount.class.php';
			$discount = new DiscountAbsolute($db);
			$discount->fetch(GETPOST("remise_id_for_payment", "int"));

			//var_dump($object->getRemainToPay(0));
			//var_dump($discount->amount_ttc);exit;
			if (price2num($discount->amount_ttc) > price2num($object->getRemainToPay(0))) {
				// TODO Split the discount in 2 automatically
				$error++;
				setEventMessages($langs->trans("ErrorDiscountLargerThanRemainToPaySplitItBefore"), null, 'errors');
			}

			if (!$error) {
				$result = $discount->link_to_invoice(0, $id);
				if ($result < 0) {
					setEventMessages($discount->error, $discount->errors, 'errors');
				}
			}
		}

		if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
			$outputlangs = $langs;
			$newlang = '';
			if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
				$newlang = GETPOST('lang_id', 'aZ09');
			}
			if ($conf->global->MAIN_MULTILANGS && empty($newlang)) {
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
	} elseif ($action == 'confirm_converttoreduc' && $confirm == 'yes' && $usercancreate) {
		// Convertir en reduc
		$object->fetch($id);
		$object->fetch_thirdparty();
		//$object->fetch_lines();	// Already done into fetch

		// Check if there is already a discount (protection to avoid duplicate creation when resubmit post)
		$discountcheck = new DiscountAbsolute($db);
		$result = $discountcheck->fetch(0, 0, $object->id);

		$canconvert = 0;
		if ($object->type == FactureFournisseur::TYPE_DEPOSIT && empty($discountcheck->id)) {
			$canconvert = 1; // we can convert deposit into discount if deposit is paid (completely, partially or not at all) and not already converted (see real condition into condition used to show button converttoreduc)
		}
		if (($object->type == FactureFournisseur::TYPE_CREDIT_NOTE || $object->type == FactureFournisseur::TYPE_STANDARD) && $object->paye == 0 && empty($discountcheck->id)) {
			$canconvert = 1; // we can convert credit note into discount if credit note is not refunded completely and not already converted and amount of payment is 0 (see also the real condition used as the condition to show button converttoreduc)
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

					$amount_ht[$line->tva_tx] += $line->total_ht;
					$amount_tva[$line->tva_tx] += $line->total_tva;
					$amount_ttc[$line->tva_tx] += $line->total_ttc;
					$multicurrency_amount_ht[$keyforvatrate] += $line->multicurrency_total_ht;
					$multicurrency_amount_tva[$keyforvatrate] += $line->multicurrency_total_tva;
					$multicurrency_amount_ttc[$keyforvatrate] += $line->multicurrency_total_ttc;
					$i++;
				}
			}

			// If some payments were already done, we change the amount to pay using same prorate
			if (!empty($conf->global->SUPPLIER_INVOICE_ALLOW_REUSE_OF_CREDIT_WHEN_PARTIALLY_REFUNDED) && $object->type == FactureFournisseur::TYPE_CREDIT_NOTE) {
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
			if ($object->type == FactureFournisseur::TYPE_CREDIT_NOTE) {
				$discount->description = '(CREDIT_NOTE)';
			} elseif ($object->type == FactureFournisseur::TYPE_DEPOSIT) {
				$discount->description = '(DEPOSIT)';
			} elseif ($object->type == FactureFournisseur::TYPE_STANDARD || $object->type == FactureFournisseur::TYPE_REPLACEMENT || $object->type == FactureFournisseur::TYPE_SITUATION) {
				$discount->description = '(EXCESS PAID)';
			} else {
				setEventMessages($langs->trans('CantConvertToReducAnInvoiceOfThisType'), null, 'errors');
			}
			$discount->discount_type = 1; // Supplier discount
			$discount->fk_soc = $object->socid;
			$discount->fk_invoice_supplier_source = $object->id;

			$error = 0;

			if ($object->type == FactureFournisseur::TYPE_STANDARD || $object->type == FactureFournisseur::TYPE_REPLACEMENT || $object->type == FactureFournisseur::TYPE_SITUATION) {
				// If we're on a standard invoice, we have to get excess paid to create a discount in TTC without VAT

				// Total payments
				$sql = 'SELECT SUM(pf.amount) as total_paiements';
				$sql .= ' FROM '.MAIN_DB_PREFIX.'paiementfourn_facturefourn as pf, '.MAIN_DB_PREFIX.'paiementfourn as p';
				$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_paiement as c ON p.fk_paiement = c.id AND c.entity IN ('.getEntity('c_paiement').')';
				$sql .= ' WHERE pf.fk_facturefourn = '.$object->id;
				$sql .= ' AND pf.fk_paiementfourn = p.rowid';
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
				$sql .= " re.description, re.fk_invoice_supplier_source";
				$sql .= " FROM ".MAIN_DB_PREFIX."societe_remise_except as re";
				$sql .= " WHERE fk_invoice_supplier = ".$object->id;
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
			if ($object->type == FactureFournisseur::TYPE_CREDIT_NOTE || $object->type == FactureFournisseur::TYPE_DEPOSIT) {
				foreach ($amount_ht as $tva_tx => $xxx) {
					$discount->amount_ht = abs($amount_ht[$tva_tx]);
					$discount->amount_tva = abs($amount_tva[$tva_tx]);
					$discount->amount_ttc = abs($amount_ttc[$tva_tx]);
					$discount->multicurrency_amount_ht = abs($multicurrency_amount_ht[$tva_tx]);
					$discount->multicurrency_amount_tva = abs($multicurrency_amount_tva[$tva_tx]);
					$discount->multicurrency_amount_ttc = abs($multicurrency_amount_ttc[$tva_tx]);

					// Clean vat code
					$reg = array();
					$vat_src_code = '';
					if (preg_match('/\((.*)\)/', $tva_tx, $reg)) {
						$vat_src_code = $reg[1];
						$tva_tx = preg_replace('/\s*\(.*\)/', '', $tva_tx); // Remove code into vatrate.
					}

					$discount->tva_tx = abs($tva_tx);
					$discount->vat_src_code = $vat_src_code;

					$result = $discount->create($user);
					if ($result < 0) {
						$error++;
						break;
					}
				}
			}

			if (empty($error)) {
				if ($object->type != FactureFournisseur::TYPE_DEPOSIT) {
					// Classe facture
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
	} elseif ($action == 'confirm_delete_paiement' && $confirm == 'yes' && $usercancreate) {
		// Delete payment
		$object->fetch($id);
		if ($object->statut == FactureFournisseur::STATUS_VALIDATED && $object->paye == 0) {
			$paiementfourn = new PaiementFourn($db);
			$result = $paiementfourn->fetch(GETPOST('paiement_id'));
			if ($result > 0) {
				$result = $paiementfourn->delete(); // If fetch ok and found
				header("Location: ".$_SERVER['PHP_SELF']."?id=".$id);
			}
			if ($result < 0) {
				setEventMessages($paiementfourn->error, $paiementfourn->errors, 'errors');
			}
		}
	} elseif ($action == 'add' && $usercancreate) {
		// Insert new invoice in database
		if ($socid > 0) {
			$object->socid = GETPOST('socid', 'int');
		}
		$selectedLines = GETPOST('toselect', 'array');

		$db->begin();

		$error = 0;

		// Fill array 'array_options' with data from add form
		$ret = $extrafields->setOptionalsFromPost(null, $object);
		if ($ret < 0) {
			$error++;
		}

		$dateinvoice = dol_mktime(0, 0, 0, GETPOST('remonth', 'int'), GETPOST('reday', 'int'), GETPOST('reyear', 'int'), 'tzserver');	// If we enter the 02 january, we need to save the 02 january for server
		$datedue = dol_mktime(0, 0, 0, GETPOST('echmonth', 'int'), GETPOST('echday', 'int'), GETPOST('echyear', 'int'), 'tzserver');
		/*var_dump($dateinvoice.' '.dol_print_date($dateinvoice, 'dayhour'));
		var_dump(dol_now('tzuserrel').' '.dol_get_last_hour(dol_now('tzuserrel')).' '.dol_print_date(dol_now('tzuserrel'),'dayhour').' '.dol_print_date(dol_get_last_hour(dol_now('tzuserrel')), 'dayhour'));
		var_dump($db->idate($dateinvoice));
		exit;*/

		// Replacement invoice
		if (GETPOST('type') == FactureFournisseur::TYPE_REPLACEMENT) {
			if (empty($dateinvoice)) {
				setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentities('DateInvoice')), null, 'errors');
				$action = 'create';
				$_GET['socid'] = $_POST['socid'];
				$error++;
			} elseif ($dateinvoice > (dol_get_last_hour(dol_now('tzuserrel')) + (empty($conf->global->INVOICE_MAX_FUTURE_DELAY) ? 0 : $conf->global->INVOICE_MAX_FUTURE_DELAY))) {
				$error++;
				setEventMessages($langs->trans("ErrorDateIsInFuture"), null, 'errors');
				$action = 'create';
			}

			if (!(GETPOST('fac_replacement', 'int') > 0)) {
				$error++;
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("ReplaceInvoice")), null, 'errors');
			}

			if (!$error) {
				// This is a replacement invoice
				$result = $object->fetch(GETPOST('fac_replacement', 'int'));
				$object->fetch_thirdparty();

				$object->ref = GETPOST('ref', 'nohtml');
				$object->ref_supplier = GETPOST('ref_supplier', 'alpha');
				$object->socid = GETPOST('socid', 'int');
				$object->libelle = GETPOST('label', 'nohtml');
				$object->date = $dateinvoice;
				$object->date_echeance = $datedue;
				$object->note_public = GETPOST('note_public', 'restricthtml');
				$object->note_private = GETPOST('note_private', 'restricthtml');
				$object->cond_reglement_id	= GETPOST('cond_reglement_id', 'int');
				$object->mode_reglement_id	= GETPOST('mode_reglement_id', 'int');
				$object->fk_account			= GETPOST('fk_account', 'int');
				$object->fk_project			= ($tmpproject > 0) ? $tmpproject : null;
				$object->fk_incoterms = GETPOST('incoterm_id', 'int');
				$object->location_incoterms	= GETPOST('location_incoterms', 'alpha');
				$object->multicurrency_code	= GETPOST('multicurrency_code', 'alpha');
				$object->multicurrency_tx = GETPOST('originmulticurrency_tx', 'int');
				$object->transport_mode_id	= GETPOST('transport_mode_id', 'int');

				// Proprietes particulieres a facture de remplacement
				$object->fk_facture_source = GETPOST('fac_replacement', 'int');
				$object->type = FactureFournisseur::TYPE_REPLACEMENT;

				$id = $object->createFromCurrent($user);
				if ($id <= 0) {
					$error++;
					setEventMessages($object->error, $object->errors, 'errors');
				}
			}
		}

		// Credit note invoice
		if (GETPOST('type') == FactureFournisseur::TYPE_CREDIT_NOTE) {
			$sourceinvoice = GETPOST('fac_avoir', 'int');
			if (!($sourceinvoice > 0) && empty($conf->global->INVOICE_CREDIT_NOTE_STANDALONE)) {
				$error++;
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("CorrectInvoice")), null, 'errors');
			}
			if (GETPOST('socid', 'int') < 1) {
				setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentities('Supplier')), null, 'errors');
				$action = 'create';
				$error++;
			}

			if (empty($dateinvoice)) {
				setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentities('DateInvoice')), null, 'errors');
				$action = 'create';
				$_GET['socid'] = $_POST['socid'];
				$error++;
			} elseif ($dateinvoice > (dol_get_last_hour(dol_now('tzuserrel')) + (empty($conf->global->INVOICE_MAX_FUTURE_DELAY) ? 0 : $conf->global->INVOICE_MAX_FUTURE_DELAY))) {
				$error++;
				setEventMessages($langs->trans("ErrorDateIsInFuture"), null, 'errors');
				$action = 'create';
			}

			if (!GETPOST('ref_supplier')) {
				setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentities('RefSupplier')), null, 'errors');
				$action = 'create';
				$_GET['socid'] = $_POST['socid'];
				$error++;
			}

			if (!$error) {
				$tmpproject = GETPOST('projectid', 'int');

				// Creation facture
				$object->ref = GETPOST('ref', 'nohtml');
				$object->ref_supplier = GETPOST('ref_supplier', 'nohtml');
				$object->socid				= GETPOST('socid', 'int');
				$object->libelle = GETPOST('label', 'nohtml');
				$object->label				= GETPOST('label', 'nohtml');
				$object->date = $dateinvoice;
				$object->date_echeance = $datedue;
				$object->note_public = GETPOST('note_public', 'restricthtml');
				$object->note_private = GETPOST('note_private', 'restricthtml');
				$object->cond_reglement_id	= GETPOST('cond_reglement_id');
				$object->mode_reglement_id	= GETPOST('mode_reglement_id');
				$object->fk_account			= GETPOST('fk_account', 'int');
				$object->fk_project			= ($tmpproject > 0) ? $tmpproject : null;
				$object->fk_incoterms = GETPOST('incoterm_id', 'int');
				$object->location_incoterms	= GETPOST('location_incoterms', 'alpha');
				$object->multicurrency_code	= GETPOST('multicurrency_code', 'alpha');
				$object->multicurrency_tx = GETPOST('originmulticurrency_tx', 'int');
				$object->transport_mode_id	= GETPOST('transport_mode_id', 'int');

				// Proprietes particulieres a facture avoir
				$object->fk_facture_source = $sourceinvoice > 0 ? $sourceinvoice : '';
				$object->type = FactureFournisseur::TYPE_CREDIT_NOTE;

				$id = $object->create($user);

				if ($id <= 0) {
					$error++;
				}

				if (GETPOST('invoiceAvoirWithLines', 'int') == 1 && $id > 0) {
					$facture_source = new FactureFournisseur($db); // fetch origin object
					if ($facture_source->fetch($object->fk_facture_source) > 0) {
						$fk_parent_line = 0;

						foreach ($facture_source->lines as $line) {
							// Reset fk_parent_line for no child products and special product
							if (($line->product_type != 9 && empty($line->fk_parent_line)) || $line->product_type == 9) {
								$fk_parent_line = 0;
							}

							$line->fk_facture_fourn = $object->id;
							$line->fk_parent_line = $fk_parent_line;

							$line->subprice = -$line->subprice; // invert price for object
							$line->pa_ht = -$line->pa_ht;
							$line->total_ht = -$line->total_ht;
							$line->total_tva = -$line->total_tva;
							$line->total_ttc = -$line->total_ttc;
							$line->total_localtax1 = -$line->total_localtax1;
							$line->total_localtax2 = -$line->total_localtax2;

							$result = $line->insert();

							$object->lines[] = $line; // insert new line in current object

							// Defined the new fk_parent_line
							if ($result > 0 && $line->product_type == 9) {
								$fk_parent_line = $result;
							}
						}

						$object->update_price(1);
					}
				}

				if (GETPOST('invoiceAvoirWithPaymentRestAmount', 'int') == 1 && $id > 0) {
					$facture_source = new FactureFournisseur($db); // fetch origin object if not previously defined
					if ($facture_source->fetch($object->fk_facture_source) > 0) {
						$totalpaye = $facture_source->getSommePaiement();
						$totalcreditnotes = $facture_source->getSumCreditNotesUsed();
						$totaldeposits = $facture_source->getSumDepositsUsed();
						$remain_to_pay = abs($facture_source->total_ttc - $totalpaye - $totalcreditnotes - $totaldeposits);

						$object->addline($langs->trans('invoiceAvoirLineWithPaymentRestAmount'), $remain_to_pay, 0, 0, 0, 1, 0, 0, '', '', 'TTC');
					}
				}
			}
		}

		// Standard invoice or Deposit invoice, not from a Predefined template invoice
		if (GETPOST('type') == FactureFournisseur::TYPE_STANDARD || GETPOST('type') == FactureFournisseur::TYPE_DEPOSIT) {
			if (GETPOST('socid', 'int') < 1) {
				setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentities('Supplier')), null, 'errors');
				$action = 'create';
				$error++;
			}

			if (empty($dateinvoice)) {
				setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentities('DateInvoice')), null, 'errors');
				$action = 'create';
				$_GET['socid'] = $_POST['socid'];
				$error++;
			} elseif ($dateinvoice > (dol_get_last_hour(dol_now('tzuserrel')) + (empty($conf->global->INVOICE_MAX_FUTURE_DELAY) ? 0 : $conf->global->INVOICE_MAX_FUTURE_DELAY))) {
				$error++;
				setEventMessages($langs->trans("ErrorDateIsInFuture"), null, 'errors');
				$action = 'create';
			}

			if (!GETPOST('ref_supplier')) {
				setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentities('RefSupplier')), null, 'errors');
				$action = 'create';
				$_GET['socid'] = $_POST['socid'];
				$error++;
			}

			if (!$error) {
				$tmpproject = GETPOST('projectid', 'int');

				// Creation invoice
				$object->socid				= GETPOST('socid', 'int');
				$object->type				= GETPOST('type');
				$object->ref				= GETPOST('ref', 'nohtml');
				$object->ref_supplier		= GETPOST('ref_supplier', 'nohtml');
				$object->socid				= GETPOST('socid', 'int');
				$object->libelle			= GETPOST('label', 'nohtml');	// deprecated
				$object->label				= GETPOST('label', 'nohtml');
				$object->date				= $dateinvoice;
				$object->date_echeance		= $datedue;
				$object->note_public		= GETPOST('note_public', 'restricthtml');
				$object->note_private		= GETPOST('note_private', 'restricthtml');
				$object->cond_reglement_id	= GETPOST('cond_reglement_id');
				$object->mode_reglement_id	= GETPOST('mode_reglement_id');
				$object->fk_account			= GETPOST('fk_account', 'int');
				$object->fk_project			= ($tmpproject > 0) ? $tmpproject : null;
				$object->fk_incoterms		= GETPOST('incoterm_id', 'int');
				$object->location_incoterms	= GETPOST('location_incoterms', 'alpha');
				$object->multicurrency_code	= GETPOST('multicurrency_code', 'alpha');
				$object->multicurrency_tx	= GETPOST('originmulticurrency_tx', 'int');
				$object->transport_mode_id	= GETPOST('transport_mode_id');

				// Auto calculation of date due if not filled by user
				if (empty($object->date_echeance)) {
					$object->date_echeance = $object->calculate_date_lim_reglement();
				}

				$object->fetch_thirdparty();

				// If creation from another object of another module
				if (!$error && GETPOST('origin', 'alpha') && GETPOST('originid')) {
					// Parse element/subelement (ex: project_task)
					$element = $subelement = GETPOST('origin', 'alpha');
					/*if (preg_match('/^([^_]+)_([^_]+)/i',$_POST['origin'],$regs))
					 {
					$element = $regs[1];
					$subelement = $regs[2];
					}*/

					// For compatibility
					if ($element == 'order') {
						$element = $subelement = 'commande';
					}
					if ($element == 'propal') {
						$element = 'comm/propal'; $subelement = 'propal';
					}
					if ($element == 'contract') {
						$element = $subelement = 'contrat';
					}
					if ($element == 'order_supplier') {
						$element = 'fourn'; $subelement = 'fournisseur.commande';
					}
					if ($element == 'project') {
						$element = 'projet';
					}
					$object->origin    = GETPOST('origin', 'alpha');
					$object->origin_id = GETPOST('originid', 'int');


					require_once DOL_DOCUMENT_ROOT.'/'.$element.'/class/'.$subelement.'.class.php';
					$classname = ucfirst($subelement);
					if ($classname == 'Fournisseur.commande') {
						$classname = 'CommandeFournisseur';
					}
					$objectsrc = new $classname($db);
					$objectsrc->fetch($originid);
					$objectsrc->fetch_thirdparty();

					if (!empty($object->origin) && !empty($object->origin_id)) {
						$object->linkedObjectsIds[$object->origin] = $object->origin_id;
					}

					// Add also link with order if object is reception
					if ($object->origin == 'reception') {
						$objectsrc->fetchObjectLinked();

						if (count($objectsrc->linkedObjectsIds['order_supplier']) > 0) {
							foreach ($objectsrc->linkedObjectsIds['order_supplier'] as $key => $value) {
								$object->linkedObjectsIds['order_supplier'] = $value;
							}
						}
					}

					$id = $object->create($user);

					// Add lines
					if ($id > 0) {
						require_once DOL_DOCUMENT_ROOT.'/'.$element.'/class/'.$subelement.'.class.php';
						$classname = ucfirst($subelement);
						if ($classname == 'Fournisseur.commande') {
							$classname = 'CommandeFournisseur';
						}
						$srcobject = new $classname($db);

						$result = $srcobject->fetch(GETPOST('originid', 'int'));

						// If deposit invoice - down payment with 1 line (fixed amount or percent)
						$typeamount = GETPOST('typedeposit', 'alpha');
						if (GETPOST('type') == FactureFournisseur::TYPE_DEPOSIT && in_array($typeamount, array('amount', 'variable'))) {
							$valuedeposit = price2num(GETPOST('valuedeposit', 'alpha'), 'MU');

							// Define the array $amountdeposit
							$amountdeposit = array();
							if (!empty($conf->global->MAIN_DEPOSIT_MULTI_TVA)) {
								if ($typeamount == 'amount') {
									$amount = $valuedeposit;
								} else {
									$amount = $srcobject->total_ttc * ($valuedeposit / 100);
								}

								$TTotalByTva = array();
								foreach ($srcobject->lines as &$line) {
									if (!empty($line->special_code)) {
										continue;
									}
									$TTotalByTva[$line->tva_tx] += $line->total_ttc;
								}

								foreach ($TTotalByTva as $tva => &$total) {
									$coef = $total / $srcobject->total_ttc; // Calc coef
									$am = $amount * $coef;
									$amount_ttc_diff += $am;
									$amountdeposit[$tva] += $am / (1 + $tva / 100); // Convert into HT for the addline
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
												$amountdeposit[$tva_tx] += ($lines[$i]->total_ht * $valuedeposit) / 100;
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
									$descline .= ' ('.price($valuedeposit, '', $langs, 0, - 1, - 1, (!empty($object->multicurrency_code) ? $object->multicurrency_code : $conf->currency)).')';
								} elseif ($typeamount == 'variable') {
									$descline .= ' ('.$valuedeposit.'%)';
								}

								$descline .= ' - '.$srcobject->ref;
								$result = $object->addline(
									$descline,
									$amount, // subprice
									$tva, // vat rate
									0, // localtax1_tx
									0, // localtax2_tx
									1, // quantity
									(empty($conf->global->INVOICE_PRODUCTID_DEPOSIT) ? 0 : $conf->global->INVOICE_PRODUCTID_DEPOSIT), // fk_product
									0, // remise_percent
									0, // date_start
									0, // date_end
									0,
									$lines[$i]->info_bits, // info_bits
									'HT',
									0, // product_type
									1,
									0,
									0,
									null,
									$object->origin,
									0,
									'',
									$lines[$i]->special_code,
									0,
									0
									//,$langs->trans('Deposit') //Deprecated
								);
							}

							$diff = $object->total_ttc - $amount_ttc_diff;

							if (!empty($conf->global->MAIN_DEPOSIT_MULTI_TVA) && $diff != 0) {
								$object->fetch_lines();
								$subprice_diff = $object->lines[0]->subprice - $diff / (1 + $object->lines[0]->tva_tx / 100);
								$object->updateline($object->lines[0]->id, $object->lines[0]->desc, $subprice_diff, $object->lines[0]->qty, $object->lines[0]->remise_percent, $object->lines[0]->date_start, $object->lines[0]->date_end, $object->lines[0]->tva_tx, 0, 0, 'HT', $object->lines[0]->info_bits, $object->lines[0]->product_type, 0, 0, 0, $object->lines[0]->pa_ht, $object->lines[0]->label, 0, array(), 100);
							}
						} elseif ($result > 0) {
							$lines = $srcobject->lines;
							if (empty($lines) && method_exists($srcobject, 'fetch_lines')) {
								$srcobject->fetch_lines();
								$lines = $srcobject->lines;
							}

							$num = count($lines);
							for ($i = 0; $i < $num; $i++) { // TODO handle subprice < 0
								if (!in_array($lines[$i]->id, $selectedLines)) {
									continue; // Skip unselected lines
								}

								$desc = ($lines[$i]->desc ? $lines[$i]->desc : $lines[$i]->libelle);
								$product_type = ($lines[$i]->product_type ? $lines[$i]->product_type : 0);

								// Extrafields
								if (method_exists($lines[$i], 'fetch_optionals')) {
									$lines[$i]->fetch_optionals();
								}

								// Dates
								// TODO mutualiser
								$date_start = $lines[$i]->date_debut_prevue;
								if ($lines[$i]->date_debut_reel) {
									$date_start = $lines[$i]->date_debut_reel;
								}
								if ($lines[$i]->date_start) {
									$date_start = $lines[$i]->date_start;
								}
								$date_end = $lines[$i]->date_fin_prevue;
								if ($lines[$i]->date_fin_reel) {
									$date_end = $lines[$i]->date_fin_reel;
								}
								if ($lines[$i]->date_end) {
									$date_end = $lines[$i]->date_end;
								}

								// FIXME Missing special_code  into addline and updateline methods
								$object->special_code = $lines[$i]->special_code;

								// FIXME If currency different from main currency, take multicurrency price
								if ($object->multicurrency_code != $conf->currency || $object->multicurrency_tx != 1) {
									$pu = 0;
									$pu_currency = $lines[$i]->multicurrency_subprice;
								} else {
									$pu = $lines[$i]->subprice;
									$pu_currency = 0;
								}

								// FIXME Missing $lines[$i]->ref_supplier and $lines[$i]->label into addline and updateline methods. They are filled when coming from order for example.
								$result = $object->addline(
									$desc,
									$pu,
									$lines[$i]->tva_tx,
									$lines[$i]->localtax1_tx,
									$lines[$i]->localtax2_tx,
									$lines[$i]->qty,
									$lines[$i]->fk_product,
									$lines[$i]->remise_percent,
									$date_start,
									$date_end,
									0,
									$lines[$i]->info_bits,
									'HT',
									$product_type,
									$lines[$i]->rang,
									0,
									$lines[$i]->array_options,
									$lines[$i]->fk_unit,
									$lines[$i]->id,
									$pu_currency,
									$lines[$i]->ref_supplier,
									$lines[$i]->special_code
								);

								if ($result < 0) {
									$error++;
									break;
								}
							}

							// Now reload line
							$object->fetch_lines();
						} else {
							$error++;
						}
					} else {
						$error++;
					}
				} elseif (!$error) {
					$id = $object->create($user);
					if ($id < 0) {
						$error++;
					}
				}
			}
		}

		if ($error) {
			$langs->load("errors");
			$db->rollback();

			setEventMessages($object->error, $object->errors, 'errors');
			$action = 'create';
			$_GET['socid'] = $_POST['socid'];
		} else {
			$db->commit();

			if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
				$outputlangs = $langs;
				$result = $object->generateDocument($object->model_pdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
				if ($result < 0) {
					dol_print_error($db, $object->error, $object->errors);
					exit;
				}
			}

			header("Location: ".$_SERVER['PHP_SELF']."?id=".$id);
			exit;
		}
	} elseif ($action == 'updateline' && $usercancreate) {
		// Edit line
		$db->begin();

		if (! $object->fetch($id) > 0)	dol_print_error($db);
		$object->fetch_thirdparty();

		$tva_tx = (GETPOST('tva_tx') ? GETPOST('tva_tx') : 0);

		if (GETPOST('price_ht') != '' || GETPOST('multicurrency_subprice') != '') {
			$up = price2num(GETPOST('price_ht'), '', 2);
			$price_base_type = 'HT';
		} else {
			$up = price2num(GETPOST('price_ttc'), '', 2);
			$price_base_type = 'TTC';
		}

		if (GETPOST('productid') > 0) {
			$productsupplier = new ProductFournisseur($db);
			if (!empty($conf->global->SUPPLIER_INVOICE_WITH_PREDEFINED_PRICES_ONLY)) {
				if (GETPOST('productid') > 0 && $productsupplier->get_buyprice(0, price2num(GETPOST('qty')), GETPOST('productid', 'int'), 'restricthtml', GETPOST('socid', 'int')) < 0) {
					setEventMessages($langs->trans("ErrorQtyTooLowForThisSupplier"), null, 'warnings');
				}
			}

			$prod = new Product($db);
			$prod->fetch(GETPOST('productid'));
			$label = $prod->description;
			if (trim(GETPOST('product_desc', 'restricthtml')) != trim($label)) {
				$label = GETPOST('product_desc', 'restricthtml');
			}

			$type = $prod->type;
		} else {
			$label = GETPOST('product_desc', 'restricthtml');
			$type = GETPOST("type") ? GETPOST("type") : 0;
		}

		$date_start = dol_mktime(GETPOST('date_starthour'), GETPOST('date_startmin'), GETPOST('date_startsec'), GETPOST('date_startmonth'), GETPOST('date_startday'), GETPOST('date_startyear'));
		$date_end = dol_mktime(GETPOST('date_endhour'), GETPOST('date_endmin'), GETPOST('date_endsec'), GETPOST('date_endmonth'), GETPOST('date_endday'), GETPOST('date_endyear'));

		// Define info_bits
		$info_bits = 0;
		if (preg_match('/\*/', $tva_tx)) {
			$info_bits |= 0x01;
		}

		// Define vat_rate
		$tva_tx = str_replace('*', '', $tva_tx);
		$localtax1_tx = get_localtax($tva_tx, 1, $mysoc, $object->thirdparty);
		$localtax2_tx = get_localtax($tva_tx, 2, $mysoc, $object->thirdparty);

		$remise_percent = price2num(GETPOST('remise_percent'), '', 2);
		$pu_ht_devise = price2num(GETPOST('multicurrency_subprice'), 'MU', 2);

		// Extrafields Lines
		$extralabelsline = $extrafields->fetch_name_optionals_label($object->table_element_line);
		$array_options = $extrafields->getOptionalsFromPost($object->table_element_line);
		// Unset extrafield POST Data
		if (is_array($extralabelsline)) {
			foreach ($extralabelsline as $key => $value) {
				unset($_POST["options_".$key]);
			}
		}

		$result = $object->updateline(GETPOST('lineid', 'int'), $label, $up, $tva_tx, $localtax1_tx, $localtax2_tx, price2num(GETPOST('qty'), 'MS'), GETPOST('productid', 'int'), $price_base_type, $info_bits, $type, $remise_percent, 0, $date_start, $date_end, $array_options, GETPOST('units'), $pu_ht_devise, GETPOST('fourn_ref', 'alpha'));
		if ($result >= 0) {
			unset($_POST['label']);
			unset($_POST['fourn_ref']);
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

			$db->commit();
		} else {
			$db->rollback();
			setEventMessages($object->error, $object->errors, 'errors');
		}
	} elseif ($action == 'addline' && $usercancreate) {
		$db->begin();

		$ret = $object->fetch($id);
		if ($ret < 0) {
			dol_print_error($db, $object->error);
			exit;
		}
		$ret = $object->fetch_thirdparty();

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
			$price_ht = price2num(GETPOST('price_ht'), 'MU', 2);
			$tva_tx = (GETPOST('tva_tx') ? GETPOST('tva_tx') : 0);
		} else {
			$idprod = GETPOST('idprod', 'int');
			$price_ht = price2num(GETPOST('price_ht'), 'MU', 2);
			$tva_tx = '';
		}

		$qty = price2num(GETPOST('qty'.$predef, 'alpha'), 'MS');
		$remise_percent = price2num(GETPOST('remise_percent'.$predef), 2);
		$price_ht_devise = price2num(GETPOST('multicurrency_price_ht'), 'MU', 2);

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
		if (!GETPOST('qty')) {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Qty')), null, 'errors');
			$error++;
		}

		if (!$error && !empty($conf->variants->enabled) && $prod_entry_mode != 'free') {
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
				if (!empty($conf->global->SUPPLIER_TAKE_FIRST_PRICE_IF_NO_PRICE_FOR_CURRENT_SUPPLIER)) {
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
				//$qtytosearch=-1;	       // We force qty to -1 to be sure to find if a supplier price exist
				$idprod = $productsupplier->get_buyprice(GETPOST('idprodfournprice', 'alpha'), $qtytosearch);
				$res = $productsupplier->fetch($idprod);
			}

			if ($idprod > 0) {
				$label = $productsupplier->label;

				// if we use supplier description of the products
				if (!empty($productsupplier->desc_supplier) && !empty($conf->global->PRODUIT_FOURN_TEXTS)) {
					$desc = $productsupplier->desc_supplier;
				} else {
					$desc = $productsupplier->description;
				}

				//If text set in desc is the same as product descpription (as now it's preloaded) whe add it only one time
				if ($product_desc==$desc && !empty($conf->global->PRODUIT_AUTOFILL_DESC)) {
					$product_desc='';
				}
				if (!empty($product_desc) && !empty($conf->global->MAIN_NO_CONCAT_DESCRIPTION)) {
					$desc = $product_desc;
				}
				if (!empty($product_desc) && trim($product_desc) != trim($desc)) {
					$desc = dol_concatdesc($desc, $product_desc, '', !empty($conf->global->MAIN_CHANGE_ORDER_CONCAT_DESCRIPTION));
				}

				$type = $productsupplier->type;
				if (GETPOST('price_ht') != '' || GETPOST('price_ht_devise') != '') {
					$price_base_type = 'HT';
					$pu = price2num($price_ht, 'MU');
					$pu_ht_devise = price2num($price_ht_devise, 'CU');
				} else {
					$price_base_type = ($productsupplier->fourn_price_base_type ? $productsupplier->fourn_price_base_type : 'HT');
					if (empty($object->multicurrency_code) || ($productsupplier->fourn_multicurrency_code != $object->multicurrency_code)) {	// If object is in a different currency and price not in this currency
						$pu = $productsupplier->fourn_pu;
						$pu_ht_devise = 0;
					} else {
						$pu = $productsupplier->fourn_pu;
						$pu_ht_devise = $productsupplier->fourn_multicurrency_unitprice;
					}
				}

				$ref_supplier = $productsupplier->ref_supplier;

				$tva_tx = get_default_tva($object->thirdparty, $mysoc, $productsupplier->id, GETPOST('idprodfournprice', 'alpha'));
				$tva_npr = get_default_npr($object->thirdparty, $mysoc, $productsupplier->id, GETPOST('idprodfournprice', 'alpha'));
				if (empty($tva_tx)) {
					$tva_npr = 0;
				}
				$localtax1_tx = get_localtax($tva_tx, 1, $mysoc, $object->thirdparty, $tva_npr);
				$localtax2_tx = get_localtax($tva_tx, 2, $mysoc, $object->thirdparty, $tva_npr);

				if (empty($pu)) {
					$pu = 0; // If pu is '' or null, we force to have a numeric value
				}

				$result = $object->addline(
					$desc,
					$pu,
					$tva_tx,
					$localtax1_tx,
					$localtax2_tx,
					$qty,
					$idprod,
					$remise_percent,
					$date_start,
					$date_end,
					0,
					$tva_npr,
					$price_base_type,
					$type,
					-1,
					0,
					$array_options,
					$productsupplier->fk_unit,
					0,
					$pu_ht_devise,
					$ref_supplier,
					''
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

			if (GETPOST('price_ht') != '' || GETPOST('price_ht_devise') != '') {
				$pu_ht = price2num($price_ht, 'MU'); // $pu_ht must be rounded according to settings
			} else {
				$pu_ttc = price2num(GETPOST('price_ttc'), 'MU');
				$pu_ht = price2num($pu_ttc / (1 + ($tva_tx / 100)), 'MU'); // $pu_ht must be rounded according to settings
			}
			$price_base_type = 'HT';
			$pu_ht_devise = price2num($price_ht_devise, 'CU');

			$result = $object->addline($product_desc, $pu_ht, $tva_tx, $localtax1_tx, $localtax2_tx, $qty, 0, $remise_percent, $date_start, $date_end, 0, $tva_npr, $price_base_type, $type, -1, 0, $array_options, $fk_unit, 0, $pu_ht_devise, $ref_supplier);
		}

		//print "xx".$tva_tx; exit;
		if (!$error && $result > 0) {
			$db->commit();

			// Define output language
			if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
				$outputlangs = $langs;
				$newlang = '';
				if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
					$newlang = GETPOST('lang_id', 'aZ09');
				}
				if ($conf->global->MAIN_MULTILANGS && empty($newlang)) {
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
					dol_print_error($db, $result);
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
	} elseif ($action == 'classin' && $usercancreate) {
		$object->fetch($id);
		$result = $object->setProject($projectid);
	} elseif ($action == 'confirm_edit' && $confirm == 'yes' && $usercancreate) {
		// Set invoice to draft status
		$object->fetch($id);

		$totalpaye = $object->getSommePaiement();
		$resteapayer = $object->total_ttc - $totalpaye;

		// We check that lines of invoices are exported in accountancy
		$ventilExportCompta = $object->getVentilExportCompta();

		if (!$ventilExportCompta) {
			// On verifie si aucun paiement n'a ete effectue
			if ($resteapayer == price2num($object->total_ttc, 'MT', 1) && $object->statut == FactureFournisseur::STATUS_VALIDATED) {
				$idwarehouse = GETPOST('idwarehouse');

				$object->fetch_thirdparty();

				$qualified_for_stock_change = 0;
				if (empty($conf->global->STOCK_SUPPORTS_SERVICES)) {
					$qualified_for_stock_change = $object->hasProductsOrServices(2);
				} else {
					$qualified_for_stock_change = $object->hasProductsOrServices(1);
				}

				// Check parameters
				if (!empty($conf->stock->enabled) && !empty($conf->global->STOCK_CALCULATE_ON_SUPPLIER_BILL) && $qualified_for_stock_change) {
					$langs->load("stocks");
					if (!$idwarehouse || $idwarehouse == -1) {
						$error++;
						setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("Warehouse")), null, 'errors');
						$action = '';
					}
				}

				$object->setDraft($user, $idwarehouse);

				// Define output language
				if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
					$outputlangs = $langs;
					$newlang = '';
					if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
						$newlang = GETPOST('lang_id', 'aZ09');
					}
					if ($conf->global->MAIN_MULTILANGS && empty($newlang)) {
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
						dol_print_error($db, $result);
					}
				}

				$action = '';
			}
		}
	} elseif ($action == 'reopen' && $usercancreate) {
		// Set invoice to validated/unpaid status
		$result = $object->fetch($id);
		if ($object->statut == FactureFournisseur::STATUS_CLOSED
		|| ($object->statut == FactureFournisseur::STATUS_ABANDONED && $object->close_code != 'replaced')) {
			$result = $object->setUnpaid($user);
			if ($result > 0) {
				header('Location: '.$_SERVER["PHP_SELF"].'?id='.$id);
				exit;
			} else {
				setEventMessages($object->error, $object->errors, 'errors');
			}
		}
	}

	// Actions when printing a doc from card
	include DOL_DOCUMENT_ROOT.'/core/actions_printing.inc.php';

	// Actions to send emails
	$triggersendname = 'BILL_SUPPLIER_SENTBYMAIL';
	$paramname = 'id';
	$autocopy = 'MAIN_MAIL_AUTOCOPY_SUPPLIER_INVOICE_TO';
	$trackid = 'sinv'.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';

	// Actions to build doc
	$upload_dir = $conf->fournisseur->facture->dir_output;
	$permissiontoadd = $usercancreate;
	include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';

	// Make calculation according to calculationrule
	if ($action == 'calculate') {
		$calculationrule = GETPOST('calculationrule');

		$object->fetch($id);
		$object->fetch_thirdparty();
		$result = $object->update_price(0, (($calculationrule == 'totalofround') ? '0' : '1'), 0, $object->thirdparty);
		if ($result <= 0) {
			dol_print_error($db, $result);
			exit;
		}
	}
	if ($action == 'update_extras') {
		$object->oldcopy = dol_clone($object);

		// Fill array 'array_options' with data from add form
		$ret = $extrafields->setOptionalsFromPost(null, $object, GETPOST('attribute', 'restricthtml'));
		if ($ret < 0) {
			$error++;
		}

		if (!$error) {
			// Actions on extra fields
			if (!$error) {
				$result = $object->insertExtraFields('BILL_SUPPLIER_MODIFY');
				if ($result < 0) {
					$error++;
				}
			}
		}

		if ($error) {
			$action = 'edit_extras';
		}
	}

	if (!empty($conf->global->MAIN_DISABLE_CONTACTS_TAB) && $usercancreate) {
		if ($action == 'addcontact') {
			$result = $object->fetch($id);

			if ($result > 0 && $id > 0) {
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
		} elseif ($action == 'swapstatut') {
			// bascule du statut d'un contact
			if ($object->fetch($id)) {
				$result = $object->swapContactStatus(GETPOST('ligne', 'int'));
			} else {
				dol_print_error($db);
			}
		} elseif ($action == 'deletecontact') {
			// Efface un contact
			$object->fetch($id);
			$result = $object->delete_contact(GETPOST("lineid", 'int'));

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
 *	View
 */

$form = new Form($db);
$formfile = new FormFile($db);
$bankaccountstatic = new Account($db);
$paymentstatic = new PaiementFourn($db);
if (!empty($conf->projet->enabled)) {
	$formproject = new FormProjets($db);
}

$now = dol_now();

$title = $langs->trans('SupplierInvoice')." - ".$langs->trans('Card');
$help_url = 'EN:Module_Suppliers_Invoices|FR:Module_Fournisseurs_Factures|ES:Módulo_Facturas_de_proveedores|DE:Modul_Lieferantenrechnungen';
llxHeader('', $title, $help_url);

// Mode creation
if ($action == 'create') {
	$facturestatic = new FactureFournisseur($db);

	print load_fiche_titre($langs->trans('NewBill'), '', 'supplier_invoice');

	dol_htmloutput_events();

	$currency_code = $conf->currency;

	$societe = '';
	if (GETPOST('socid') > 0) {
		$societe = new Societe($db);
		$societe->fetch(GETPOST('socid', 'int'));
		if (!empty($conf->multicurrency->enabled) && !empty($societe->multicurrency_code)) {
			$currency_code = $societe->multicurrency_code;
		}
	}

	if (!empty($origin) && !empty($originid)) {
		// Parse element/subelement (ex: project_task)
		$element = $subelement = $origin;

		if ($element == 'project') {
			$projectid = $originid;
			$element = 'projet';
		}

		// For compatibility
		if ($element == 'order') {
			$element = $subelement = 'commande';
		}
		if ($element == 'propal') {
			$element = 'comm/propal'; $subelement = 'propal';
		}
		if ($element == 'contract') {
			$element = $subelement = 'contrat';
		}
		if ($element == 'order_supplier') {
			$element = 'fourn'; $subelement = 'fournisseur.commande';
		}

		require_once DOL_DOCUMENT_ROOT.'/'.$element.'/class/'.$subelement.'.class.php';
		$classname = ucfirst($subelement);
		if ($classname == 'Fournisseur.commande') {
			$classname = 'CommandeFournisseur';
		}
		$objectsrc = new $classname($db);
		$objectsrc->fetch($originid);
		$objectsrc->fetch_thirdparty();

		$projectid = (!empty($objectsrc->fk_project) ? $objectsrc->fk_project : '');
		//$ref_client			= (!empty($objectsrc->ref_client)?$object->ref_client:'');

		$soc = $objectsrc->thirdparty;
		$cond_reglement_id 	= (!empty($objectsrc->cond_reglement_id) ? $objectsrc->cond_reglement_id : (!empty($soc->cond_reglement_supplier_id) ? $soc->cond_reglement_supplier_id : 0)); // TODO maybe add default value option
		$mode_reglement_id 	= (!empty($objectsrc->mode_reglement_id) ? $objectsrc->mode_reglement_id : (!empty($soc->mode_reglement_supplier_id) ? $soc->mode_reglement_supplier_id : 0));
		$fk_account         = (!empty($objectsrc->fk_account) ? $objectsrc->fk_account : (!empty($soc->fk_account) ? $soc->fk_account : 0));
		$remise_percent 	= (!empty($objectsrc->remise_percent) ? $objectsrc->remise_percent : (!empty($soc->remise_supplier_percent) ? $soc->remise_supplier_percent : 0));
		$remise_absolue 	= (!empty($objectsrc->remise_absolue) ? $objectsrc->remise_absolue : (!empty($soc->remise_absolue) ? $soc->remise_absolue : 0));
		$dateinvoice = empty($conf->global->MAIN_AUTOFILL_DATE) ?-1 : '';
		$transport_mode_id = (!empty($objectsrc->transport_mode_id) ? $objectsrc->transport_mode_id : (!empty($soc->transport_mode_id) ? $soc->transport_mode_id : 0));

		if (!empty($conf->multicurrency->enabled)) {
			if (!empty($objectsrc->multicurrency_code)) {
				$currency_code = $objectsrc->multicurrency_code;
			}
			if (!empty($conf->global->MULTICURRENCY_USE_ORIGIN_TX) && !empty($objectsrc->multicurrency_tx)) {
				$currency_tx = $objectsrc->multicurrency_tx;
			}
		}

		$datetmp = dol_mktime(12, 0, 0, GETPOST('remonth', 'int'), GETPOST('reday', 'int'), GETPOST('reyear', 'int'));
		$dateinvoice = ($datetmp == '' ? (empty($conf->global->MAIN_AUTOFILL_DATE) ?-1 : '') : $datetmp);
		$datetmp = dol_mktime(12, 0, 0, $_POST['echmonth'], $_POST['echday'], $_POST['echyear']);
		$datedue = ($datetmp == '' ?-1 : $datetmp);

		// Replicate extrafields
		$objectsrc->fetch_optionals();
		$object->array_options = $objectsrc->array_options;
	} else {
		$cond_reglement_id = $societe->cond_reglement_supplier_id;
		$mode_reglement_id = $societe->mode_reglement_supplier_id;
		$transport_mode_id = $societe->transport_mode_supplier_id;
		$fk_account = $societe->fk_account;
		$datetmp = dol_mktime(12, 0, 0, GETPOST('remonth', 'int'), GETPOST('reday', 'int'), GETPOST('reyear', 'int'));
		$dateinvoice = ($datetmp == '' ? (empty($conf->global->MAIN_AUTOFILL_DATE) ?-1 : '') : $datetmp);
		$datetmp = dol_mktime(12, 0, 0, $_POST['echmonth'], $_POST['echday'], $_POST['echyear']);
		$datedue = ($datetmp == '' ?-1 : $datetmp);

		if (!empty($conf->multicurrency->enabled) && !empty($soc->multicurrency_code)) {
			$currency_code = $soc->multicurrency_code;
		}
	}

	// when payment condition is empty (means not override by payment condition form a other object, like third-party), try to use default value
	if (empty($cond_reglement_id)) {
		$cond_reglement_id = GETPOST("cond_reglement_id");
	}

	// when payment mode is empty (means not override by payment condition form a other object, like third-party), try to use default value
	if (empty($mode_reglement_id)) {
		$mode_reglement_id = GETPOST("mode_reglement_id");
	}

	$note_public = $object->getDefaultCreateValueFor('note_public', ((!empty($origin) && !empty($originid) && is_object($objectsrc) && !empty($conf->global->FACTUREFOURN_REUSE_NOTES_ON_CREATE_FROM)) ? $objectsrc->note_public : null));
	$note_private = $object->getDefaultCreateValueFor('note_private', ((!empty($origin) && !empty($originid) && is_object($objectsrc) && !empty($conf->global->FACTUREFOURN_REUSE_NOTES_ON_CREATE_FROM)) ? $objectsrc->note_private : null));

	print '<form name="add" action="'.$_SERVER["PHP_SELF"].'" method="post">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="add">';
	if ($societe->id > 0) {
		print '<input type="hidden" name="socid" value="'.$societe->id.'">'."\n";
	}
	print '<input type="hidden" name="origin" value="'.$origin.'">';
	print '<input type="hidden" name="originid" value="'.$originid.'">';
	if (!empty($currency_tx)) {
		print '<input type="hidden" name="originmulticurrency_tx" value="'.$currency_tx.'">';
	}

	print dol_get_fiche_head();

	print '<table class="border centpercent">';

	// Ref
	print '<tr><td class="titlefieldcreate">'.$langs->trans('Ref').'</td><td>'.$langs->trans('Draft').'</td></tr>';

	// Third party
	print '<tr><td class="fieldrequired">'.$langs->trans('Supplier').'</td>';
	print '<td>';

	if ($societe->id > 0) {
		$absolute_discount = $societe->getAvailableDiscounts('', '', 0, 1);
		print $societe->getNomUrl(1);
		print '<input type="hidden" name="socid" value="'.$societe->id.'">';
	} else {
		print img_picto('', 'company').$form->select_company($societe->id, 'socid', 's.fournisseur=1', 'SelectThirdParty', 0, 0, null, 0, 'minwidth300 widthcentpercentminusxx');
		// reload page to retrieve supplier informations
		if (!empty($conf->global->RELOAD_PAGE_ON_SUPPLIER_CHANGE)) {
			print '<script type="text/javascript">
			$(document).ready(function() {
				$("#socid").change(function() {
					var socid = $(this).val();
					// reload page
					window.location.href = "'.$_SERVER["PHP_SELF"].'?action=create&socid="+socid;
				});
			});
			</script>';
		}
		print ' <a href="'.DOL_URL_ROOT.'/societe/card.php?action=create&client=0&fournisseur=1&backtopage='.urlencode($_SERVER["PHP_SELF"].'?action=create').'"><span class="fa fa-plus-circle valignmiddle paddingleft" title="'.$langs->trans("AddThirdParty").'"></span></a>';
	}
	print '</td></tr>';

	// Ref supplier
	print '<tr><td class="fieldrequired">'.$langs->trans('RefSupplier').'</td><td><input name="ref_supplier" value="'.(isset($_POST['ref_supplier']) ? $_POST['ref_supplier'] : $objectsrc->ref_supplier).'" type="text"';
	if ($societe->id > 0) {
		print ' autofocus';
	}
	print '></td>';
	print '</tr>';

	print '<tr><td class="tdtop fieldrequired">'.$langs->trans('Type').'</td><td>';

	print '<div class="tagtable">'."\n";

	// Standard invoice
	print '<div class="tagtr listofinvoicetype"><div class="tagtd listofinvoicetype">';
	$tmp = '<input type="radio" id="radio_standard" name="type" value="0"'.(GETPOST('type') == 0 ? ' checked' : '').'> ';
	$desc = $form->textwithpicto($tmp.$langs->trans("InvoiceStandardAsk"), $langs->transnoentities("InvoiceStandardDesc"), 1, 'help', '', 0, 3);
	print $desc;
	print '</div></div>';

	if (empty($origin) || ($origin == 'order_supplier' && !empty($originid))) {
		// Deposit - Down payment
		if (empty($conf->global->INVOICE_DISABLE_DEPOSIT)) {
			print '<div class="tagtr listofinvoicetype"><div class="tagtd listofinvoicetype">';
			$tmp='<input type="radio" id="radio_deposit" name="type" value="3"' . (GETPOST('type') == 3 ? ' checked' : '') . '> ';
			print '<script type="text/javascript" language="javascript">
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
    			jQuery("#radio_standard, #radio_deposit, #radio_replacement, #radio_template").change(function() {
					setRadioForTypeOfInvoice();
				});
				function setRadioForTypeOfInvoice() {
					console.log("Change radio");
					if (jQuery("#radio_deposit").prop("checked") && (jQuery("#typedeposit").val() == \'amount\' || jQuery("#typedeposit").val() == \'variable\')) {
						jQuery(".checkforselect").prop("disabled", true);
						jQuery(".checkforselect").prop("checked", false);
					} else {
						jQuery(".checkforselect").prop("disabled", false);
						jQuery(".checkforselect").prop("checked", true);
					}
				};
    		});
    		</script>';

			$tmp  = $tmp.'<label for="radio_deposit" >'.$langs->trans("InvoiceDeposit").'</label>';
			$desc = $form->textwithpicto($tmp, $langs->transnoentities("InvoiceDepositDesc"), 1, 'help', '', 0, 3);
			print '<table class="nobordernopadding"><tr>';
			print '<td>';
			print $desc;
			print '</td>';
			if ($origin == 'order_supplier') {
				print '<td class="nowrap" style="padding-left: 15px">';
				$arraylist = array(
					'amount' => $langs->transnoentitiesnoconv('FixAmount', $langs->transnoentitiesnoconv('Deposit')),
					'variable' => $langs->transnoentitiesnoconv('VarAmountOneLine', $langs->transnoentitiesnoconv('Deposit')),
					'variablealllines' => $langs->transnoentitiesnoconv('VarAmountAllLines')
				);
				print $form->selectarray('typedeposit', $arraylist, GETPOST('typedeposit', 'aZ09'), 0, 0, 0, '', 1);
				print '</td>';
				print '<td class="nowrap" style="padding-left: 5px">';
				print '<span class="opacitymedium paddingleft">'.$langs->trans("AmountOrPercent").'</span><input type="text" id="valuedeposit" name="valuedeposit" class="width75 right" value="' . GETPOST('valuedeposit', 'int') . '"/>';
				print '</td>';
			}
			print '</tr></table>';

			print '</div></div>';
		}
	}

	/* Not yet supported for supplier
	if ($societe->id > 0)
	{
		// Replacement
		if (empty($conf->global->INVOICE_DISABLE_REPLACEMENT))
		{
			// Type invoice
			$facids = $facturestatic->list_replacable_supplier_invoices($societe->id);
			if ($facids < 0) {
				dol_print_error($db, $facturestatic->error, $facturestatic->errors);
				exit();
			}
			$options = "";
			foreach ($facids as $facparam)
			{
				$options .= '<option value="' . $facparam ['id'] . '"';
				if ($facparam ['id'] == $_POST['fac_replacement'])
					$options .= ' selected';
				$options .= '>' . $facparam ['ref'];
				$options .= ' (' . $facturestatic->LibStatut(0, $facparam ['status']) . ')';
				$options .= '</option>';
			}

			print '<!-- replacement line -->';
			print '<div class="tagtr listofinvoicetype"><div class="tagtd listofinvoicetype">';
			$tmp='<input type="radio" name="type" id="radio_replacement" value="1"' . (GETPOST('type') == 1 ? ' checked' : '');
			if (! $options) $tmp.=' disabled';
			$tmp.='> ';
			print '<script type="text/javascript" language="javascript">
			jQuery(document).ready(function() {
				jQuery("#fac_replacement").change(function() {
					jQuery("#radio_replacement").prop("checked", true);
				});
			});
			</script>';
			$text = $tmp.$langs->trans("InvoiceReplacementAsk") . ' ';
			$text .= '<select class="flat" name="fac_replacement" id="fac_replacement"';
			if (! $options)
				$text .= ' disabled';
			$text .= '>';
			if ($options) {
				$text .= '<option value="-1">&nbsp;</option>';
				$text .= $options;
			} else {
				$text .= '<option value="-1">' . $langs->trans("NoReplacableInvoice") . '</option>';
			}
			$text .= '</select>';
			$desc = $form->textwithpicto($text, $langs->transnoentities("InvoiceReplacementDesc"), 1, 'help', '', 0, 3);
			print $desc;
			print '</div></div>';
		}
	}
	else
	{
		print '<div class="tagtr listofinvoicetype"><div class="tagtd listofinvoicetype">';
		$tmp='<input type="radio" name="type" id="radio_replacement" value="0" disabled> ';
		$text = $tmp.$langs->trans("InvoiceReplacement") . ' ';
		$text.= '('.$langs->trans("YouMustCreateInvoiceFromSupplierThird").') ';
		$desc = $form->textwithpicto($text, $langs->transnoentities("InvoiceReplacementDesc"), 1, 'help', '', 0, 3);
		print $desc;
		print '</div></div>';
	}
	*/

	if (empty($origin)) {
		if ($societe->id > 0) {
			// Credit note
			if (empty($conf->global->INVOICE_DISABLE_CREDIT_NOTE)) {
				// Show link for credit note
				$facids = $facturestatic->list_qualified_avoir_supplier_invoices($societe->id);
				if ($facids < 0) {
					dol_print_error($db, $facturestatic->error, $facturestatic->errors);
					exit;
				}
				$optionsav = "";
				$newinvoice_static = new FactureFournisseur($db);
				foreach ($facids as $key => $valarray) {
					$newinvoice_static->id = $key;
					$newinvoice_static->ref = $valarray ['ref'];
					$newinvoice_static->statut = $valarray ['status'];
					$newinvoice_static->type = $valarray ['type'];
					$newinvoice_static->paye = $valarray ['paye'];

					$optionsav .= '<option value="'.$key.'"';
					if ($key == GETPOST('fac_avoir', 'int')) {
						$optionsav .= ' selected';
					}
					$optionsav .= '>';
					$optionsav .= $newinvoice_static->ref;
					$optionsav .= ' ('.$newinvoice_static->getLibStatut(1, $valarray ['paymentornot']).')';
					$optionsav .= '</option>';
				}

				print '<div class="tagtr listofinvoicetype"><div class="tagtd listofinvoicetype">';
				$tmp = '<input type="radio" id="radio_creditnote" name="type" value="2"'.(GETPOST('type') == 2 ? ' checked' : '');
				if (!$optionsav) {
					$tmp .= ' disabled';
				}
				$tmp .= '> ';
				// Show credit note options only if we checked credit note
				print '<script type="text/javascript" language="javascript">
   				jQuery(document).ready(function() {
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
				$text = $tmp.$langs->transnoentities("InvoiceAvoirAsk").' ';
				// $text.='<input type="text" value="">';
				$text .= '<select class="flat valignmiddle" name="fac_avoir" id="fac_avoir"';
				if (!$optionsav) {
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

				print '<div id="credit_note_options" class="clearboth">';
				print '&nbsp;&nbsp;&nbsp; <input type="checkbox" name="invoiceAvoirWithLines" id="invoiceAvoirWithLines" value="1" onclick="if($(this).is(\':checked\') ) { $(\'#radio_creditnote\').prop(\'checked\', true); $(\'#invoiceAvoirWithPaymentRestAmount\').removeAttr(\'checked\');   }" '.(GETPOST('invoiceAvoirWithLines', 'int') > 0 ? 'checked' : '').' /> ';
				print '<label for="invoiceAvoirWithLines">'.$langs->trans('invoiceAvoirWithLines')."</label>";
				print '<br>&nbsp;&nbsp;&nbsp; <input type="checkbox" name="invoiceAvoirWithPaymentRestAmount" id="invoiceAvoirWithPaymentRestAmount" value="1" onclick="if($(this).is(\':checked\') ) { $(\'#radio_creditnote\').prop(\'checked\', true);  $(\'#invoiceAvoirWithLines\').removeAttr(\'checked\');   }" '.(GETPOST('invoiceAvoirWithPaymentRestAmount', 'int') > 0 ? 'checked' : '').' /> ';
				print '<label for="invoiceAvoirWithPaymentRestAmount">'.$langs->trans('invoiceAvoirWithPaymentRestAmount')."</label>";
				print '</div>';

				print '</div></div>';
			}
		} else {
			print '<div class="tagtr listofinvoicetype"><div class="tagtd listofinvoicetype">';
			$tmp = '<input type="radio" name="type" id="radio_creditnote" value="0" disabled> ';
			$text = $tmp.$langs->trans("InvoiceAvoir").' ';
			$text .= '<span class="opacitymedium">('.$langs->trans("YouMustCreateInvoiceFromSupplierThird").')</span> ';
			$desc = $form->textwithpicto($text, $langs->transnoentities("InvoiceAvoirDesc"), 1, 'help', '', 0, 3);
			print $desc;
			print '</div></div>'."\n";
		}
	}

	print '</div>';

	print '</td></tr>';

	if ($societe->id > 0) {
		// Discounts for third party
		print '<tr><td>'.$langs->trans('Discounts').'</td><td>';

		$thirdparty = $societe;
		$discount_type = 1;
		$backtopage = urlencode($_SERVER["PHP_SELF"].'?socid='.$societe->id.'&action='.$action.'&origin='.GETPOST('origin').'&originid='.GETPOST('originid'));
		include DOL_DOCUMENT_ROOT.'/core/tpl/object_discounts.tpl.php';

		print '</td></tr>';
	}

	// Label
	print '<tr><td>'.$langs->trans('Label').'</td><td><input class="minwidth200" name="label" value="'.dol_escape_htmltag(GETPOST('label')).'" type="text"></td></tr>';

	// Date invoice
	print '<tr><td class="fieldrequired">'.$langs->trans('DateInvoice').'</td><td>';
	print $form->selectDate($dateinvoice, '', '', '', '', "add", 1, 1);
	print '</td></tr>';

	// Due date
	print '<tr><td>'.$langs->trans('DateMaxPayment').'</td><td>';
	print $form->selectDate($datedue, 'ech', '', '', '', "add", 1, 1);
	print '</td></tr>';

	// Payment term
	print '<tr><td class="nowrap">'.$langs->trans('PaymentConditionsShort').'</td><td>';
	$form->select_conditions_paiements(GETPOSTISSET('cond_reglement_id') ?GETPOST('cond_reglement_id', 'int') : $cond_reglement_id, 'cond_reglement_id');
	print '</td></tr>';

	// Payment mode
	print '<tr><td>'.$langs->trans('PaymentMode').'</td><td>';
	print img_picto('', 'bank', 'class="pictofixedwidth"');
	$form->select_types_paiements(GETPOSTISSET('mode_reglement_id') ?GETPOST('mode_reglement_id', 'int') : $mode_reglement_id, 'mode_reglement_id', 'DBIT', 0, 1, 0, 0, 1, 'maxwidth200 widthcentpercentminusx');
	print '</td></tr>';

	// Bank Account
	if (!empty($conf->banque->enabled)) {
		print '<tr><td>'.$langs->trans('BankAccount').'</td><td>';
		print img_picto('', 'bank_account', 'class="pictofixedwidth"').$form->select_comptes((GETPOSTISSET('fk_account') ?GETPOST('fk_account', 'alpha') : $fk_account), 'fk_account', 0, '', 1, '', 0, 'maxwidth200 widthcentpercentminusx', 1);
		print '</td></tr>';
	}

	// Project
	if (!empty($conf->projet->enabled)) {
		$formproject = new FormProjets($db);

		$langs->load('projects');
		print '<tr><td>'.$langs->trans('Project').'</td><td>';
		print img_picto('', 'project', 'class="pictofixedwidth"').$formproject->select_projects((empty($conf->global->PROJECT_CAN_ALWAYS_LINK_TO_ALL_SUPPLIERS) ? $societe->id : -1), $projectid, 'projectid', 0, 0, 1, 1, 0, 0, 0, '', 1, 0, 'maxwidth500 widthcentpercentminusxx');
		print ' <a href="'.DOL_URL_ROOT.'/projet/card.php?socid='.$soc->id.'&action=create&status=1&backtopage='.urlencode($_SERVER["PHP_SELF"].'?action=create&socid='.$soc->id.($fac_rec ? '&fac_rec='.$fac_rec : '')).'"><span class="fa fa-plus-circle valignmiddle" title="'.$langs->trans("AddProject").'"></span></a>';
		print '</td></tr>';
	}

	// Incoterms
	if (!empty($conf->incoterm->enabled)) {
		print '<tr>';
		print '<td><label for="incoterm_id">'.$form->textwithpicto($langs->trans("IncotermLabel"), $objectsrc->label_incoterms, 1).'</label></td>';
		print '<td colspan="3" class="maxwidthonsmartphone">';
		print $form->select_incoterms(GETPOSTISSET('incoterm_id') ? GETPOST('incoterm_id', 'alphanohtml') : (!empty($objectsrc->fk_incoterms) ? $objectsrc->fk_incoterms : ''), GETPOSTISSET('location_incoterms') ? GETPOST('location_incoterms', 'alphanohtml') : (!empty($objectsrc->location_incoterms) ? $objectsrc->location_incoterms : ''));
		print '</td></tr>';
	}

	// Multicurrency
	if (!empty($conf->multicurrency->enabled)) {
		print '<tr>';
		print '<td>'.$form->editfieldkey('Currency', 'multicurrency_code', '', $object, 0).'</td>';
		print '<td class="maxwidthonsmartphone">';
		print $form->selectMultiCurrency((GETPOSTISSET('multicurrency_code') ?GETPOST('multicurrency_code', 'alpha') : $currency_code), 'multicurrency_code');
		print '</td></tr>';
	}

	// Intracomm report
	if (!empty($conf->intracommreport->enabled)) {
		$langs->loadLangs(array("intracommreport"));
		print '<tr><td>'.$langs->trans('IntracommReportTransportMode').'</td><td>';
		$form->selectTransportMode(isset($_POST['transport_mode_id']) ? $_POST['transport_mode_id'] : $transport_mode_id, 'transport_mode_id');
		print '</td></tr>';
	}

	if (empty($reshook)) {
		print $object->showOptionals($extrafields, 'create');
	}

	// Public note
	print '<tr><td>'.$langs->trans('NotePublic').'</td>';
	print '<td>';
	$doleditor = new DolEditor('note_public', (GETPOSTISSET('note_public') ?GETPOST('note_public', 'restricthtml') : $note_public), '', 80, 'dolibarr_notes', 'In', 0, false, empty($conf->global->FCKEDITOR_ENABLE_NOTE_PUBLIC) ? 0 : 1, ROWS_3, '90%');
	print $doleditor->Create(1);
	print '</td>';
	// print '<td><textarea name="note" wrap="soft" cols="60" rows="'.ROWS_5.'"></textarea></td>';
	print '</tr>';

	// Private note
	print '<tr><td>'.$langs->trans('NotePrivate').'</td>';
	print '<td>';
	$doleditor = new DolEditor('note_private', (GETPOSTISSET('note_private') ?GETPOST('note_private', 'restricthtml') : $note_private), '', 80, 'dolibarr_notes', 'In', 0, false, empty($conf->global->FCKEDITOR_ENABLE_NOTE_PRIVATE) ? 0 : 1, ROWS_3, '90%');
	print $doleditor->Create(1);
	print '</td>';
	// print '<td><textarea name="note" wrap="soft" cols="60" rows="'.ROWS_5.'"></textarea></td>';
	print '</tr>';


	if (is_object($objectsrc)) {
		print "\n<!-- ".$classname." info -->";
		print "\n";
		print '<input type="hidden" name="amount"         value="'.$objectsrc->total_ht.'">'."\n";
		print '<input type="hidden" name="total"          value="'.$objectsrc->total_ttc.'">'."\n";
		print '<input type="hidden" name="tva"            value="'.$objectsrc->total_tva.'">'."\n";
		print '<input type="hidden" name="origin"         value="'.$objectsrc->element.'">';
		print '<input type="hidden" name="originid"       value="'.$objectsrc->id.'">';

		$txt = $langs->trans($classname);
		if ($classname == 'CommandeFournisseur') {
			$langs->load('orders');
			$txt = $langs->trans("SupplierOrder");
		}
		print '<tr><td>'.$txt.'</td><td>'.$objectsrc->getNomUrl(1);
		// We check if Origin document (id and type is known) has already at least one invoice attached to it
		$objectsrc->fetchObjectLinked($originid, $origin, '', 'invoice_supplier');

		$invoice_supplier = $objectsrc->linkedObjects['invoice_supplier'];

		// count function need a array as argument (Note: the array must implement Countable too)
		if (is_array($invoice_supplier)) {
			$cntinvoice = count($invoice_supplier);

			if ($cntinvoice >= 1) {
				setEventMessages('WarningBillExist', null, 'warnings');
				echo ' ('.$langs->trans('LatestRelatedBill').end($invoice_supplier)->getNomUrl(1).')';
			}
		}

		print '</td></tr>';
		print '<tr><td>'.$langs->trans('AmountHT').'</td><td>'.price($objectsrc->total_ht).'</td></tr>';
		print '<tr><td>'.$langs->trans('AmountVAT').'</td><td>'.price($objectsrc->total_tva)."</td></tr>";
		if ($mysoc->localtax1_assuj == "1" || $object->total_localtax1 != 0) { //Localtax1
			print '<tr><td>'.$langs->transcountry("AmountLT1", $mysoc->country_code).'</td><td>'.price($objectsrc->total_localtax1)."</td></tr>";
		}

		if ($mysoc->localtax2_assuj == "1" || $object->total_localtax2 != 0) { //Localtax2
			print '<tr><td>'.$langs->transcountry("AmountLT2", $mysoc->country_code).'</td><td>'.price($objectsrc->total_localtax2)."</td></tr>";
		}
		print '<tr><td>'.$langs->trans('AmountTTC').'</td><td>'.price($objectsrc->total_ttc)."</td></tr>";

		if (!empty($conf->multicurrency->enabled)) {
			print '<tr><td>'.$langs->trans('MulticurrencyAmountHT').'</td><td>'.price($objectsrc->multicurrency_total_ht).'</td></tr>';
			print '<tr><td>'.$langs->trans('MulticurrencyAmountVAT').'</td><td>'.price($objectsrc->multicurrency_total_tva)."</td></tr>";
			print '<tr><td>'.$langs->trans('MulticurrencyAmountTTC').'</td><td>'.price($objectsrc->multicurrency_total_ttc)."</td></tr>";
		}
	}

	// Other options
	$parameters = array();
	$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;

	// Bouton "Create Draft"
	print "</table>\n";

	print dol_get_fiche_end();

	print '<div class="center">';
	print '<input type="submit" class="button" name="bouton" value="'.$langs->trans('CreateDraft').'">';
	print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	print '<input type="button" class="button button-cancel" value="'.$langs->trans("Cancel").'" onClick="javascript:history.go(-1)">';
	print '</div>';

	// Show origin lines
	if (is_object($objectsrc)) {
		print '<br>';

		$title = $langs->trans('ProductsAndServices');
		print load_fiche_titre($title);

		print '<table class="noborder centpercent">';

		$objectsrc->printOriginLinesList('', $selectedLines);

		print '</table>';
	}

	print "</form>\n";
} else {
	if ($id > 0 || !empty($ref)) {
		//
		// View or edit mode
		//

		$now = dol_now();

		$productstatic = new Product($db);

		$object->fetch($id, $ref);
		$result = $object->fetch_thirdparty();
		if ($result < 0) {
			dol_print_error($db);
		}

		$societe = new Fournisseur($db);
		$result = $societe->fetch($object->socid);
		if ($result < 0) {
			dol_print_error($db);
		}

		$totalpaye = $object->getSommePaiement();
		$totalcreditnotes = $object->getSumCreditNotesUsed();
		$totaldeposits = $object->getSumDepositsUsed();
		// print "totalpaye=".$totalpaye." totalcreditnotes=".$totalcreditnotes." totaldeposts=".$totaldeposits."
		// selleruserrevenuestamp=".$selleruserevenustamp;

		// We can also use bcadd to avoid pb with floating points
		// For example print 239.2 - 229.3 - 9.9; does not return 0.
		// $resteapayer=bcadd($object->total_ttc,$totalpaye,$conf->global->MAIN_MAX_DECIMALS_TOT);
		// $resteapayer=bcadd($resteapayer,$totalavoir,$conf->global->MAIN_MAX_DECIMALS_TOT);
		$resteapayer = price2num($object->total_ttc - $totalpaye - $totalcreditnotes - $totaldeposits, 'MT');

		// Multicurrency
		if (!empty($conf->multicurrency->enabled)) {
			$multicurrency_totalpaye = $object->getSommePaiement(1);
			$multicurrency_totalcreditnotes = $object->getSumCreditNotesUsed(1);
			$multicurrency_totaldeposits = $object->getSumDepositsUsed(1);
			$multicurrency_resteapayer = price2num($object->multicurrency_total_ttc - $multicurrency_totalpaye - $multicurrency_totalcreditnotes - $multicurrency_totaldeposits, 'MT');
			// Code to fix case of corrupted data
			if ($resteapayer == 0 && $multicurrency_resteapayer != 0) {
				$resteapayer = price2num($multicurrency_resteapayer / $object->multicurrency_tx, 'MT');
			}
		}

		if ($object->paye) {
			$resteapayer = 0;
		}
		$resteapayeraffiche = $resteapayer;

		if (!empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS)) {	// Never use this
			$filterabsolutediscount = "fk_invoice_supplier_source IS NULL"; // If we want deposit to be substracted to payments only and not to total of final invoice
			$filtercreditnote = "fk_invoice_supplier_source IS NOT NULL"; // If we want deposit to be substracted to payments only and not to total of final invoice
		} else {
			$filterabsolutediscount = "fk_invoice_supplier_source IS NULL OR (description LIKE '(DEPOSIT)%' AND description NOT LIKE '(EXCESS PAID)%')";
			$filtercreditnote = "fk_invoice_supplier_source IS NOT NULL AND (description NOT LIKE '(DEPOSIT)%' OR description LIKE '(EXCESS PAID)%')";
		}

		$absolute_discount = $societe->getAvailableDiscounts('', $filterabsolutediscount, 0, 1);
		$absolute_creditnote = $societe->getAvailableDiscounts('', $filtercreditnote, 0, 1);
		$absolute_discount = price2num($absolute_discount, 'MT');
		$absolute_creditnote = price2num($absolute_creditnote, 'MT');

		/*
		 *	View card
		 */
		$head = facturefourn_prepare_head($object);
		$titre = $langs->trans('SupplierInvoice');

		print dol_get_fiche_head($head, 'card', $titre, -1, 'supplier_invoice');

		$formconfirm = '';

		// Confirmation de la conversion de l'avoir en reduc
		if ($action == 'converttoreduc') {
			if ($object->type == FactureFournisseur::TYPE_STANDARD) {
				$type_fac = 'ExcessPaid';
			} elseif ($object->type == FactureFournisseur::TYPE_CREDIT_NOTE) {
				$type_fac = 'CreditNote';
			} elseif ($object->type == FactureFournisseur::TYPE_DEPOSIT) {
				$type_fac = 'Deposit';
			}
			$text = $langs->trans('ConfirmConvertToReducSupplier', strtolower($langs->transnoentities($type_fac)));
			$text .= '<br>'.$langs->trans('ConfirmConvertToReducSupplier2');
			$formconfirm = $form->formconfirm($_SERVER['PHP_SELF'].'?facid='.$object->id, $langs->trans('ConvertToReduc'), $text, 'confirm_converttoreduc', '', "yes", 2);
		}

		// Clone confirmation
		if ($action == 'clone') {
			// Create an array for form
			$formquestion = array(
				array('type' => 'text', 'name' => 'newsupplierref', 'label' => $langs->trans("RefSupplier"), 'value' => $langs->trans("CopyOf").' '.$object->ref_supplier),
				array('type' => 'date', 'name' => 'newdate', 'label' => $langs->trans("Date"), 'value' => dol_now())
			);
			// Ask confirmation to clone
			$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ToClone'), $langs->trans('ConfirmCloneInvoice', $object->ref), 'confirm_clone', $formquestion, 'yes', 1, 250);
		}

		// Confirmation of validation
		if ($action == 'valid') {
			// We check if number is temporary number
			if (preg_match('/^[\(]?PROV/i', $object->ref) || empty($object->ref)) {
				// empty should not happened, but when it occurs, the test save life
				$numref = $object->getNextNumRef($societe);
			} else {
				$numref = $object->ref;
			}

			if ($numref < 0) {
				setEventMessages($object->error, $object->errors, 'errors');
				$action = '';
			} else {
				$text = $langs->trans('ConfirmValidateBill', $numref);
				/*if (! empty($conf->notification->enabled))
				 {
				 require_once DOL_DOCUMENT_ROOT .'/core/class/notify.class.php';
				 $notify=new Notify($db);
				 $text.='<br>';
				 $text.=$notify->confirmMessage('BILL_SUPPLIER_VALIDATE',$object->socid, $object);
				 }*/
				$formquestion = array();

				$qualified_for_stock_change = 0;
				if (empty($conf->global->STOCK_SUPPORTS_SERVICES)) {
					$qualified_for_stock_change = $object->hasProductsOrServices(2);
				} else {
					$qualified_for_stock_change = $object->hasProductsOrServices(1);
				}

				if (!empty($conf->stock->enabled) && !empty($conf->global->STOCK_CALCULATE_ON_SUPPLIER_BILL) && $qualified_for_stock_change) {
					$langs->load("stocks");
					require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
					$formproduct = new FormProduct($db);
					$warehouse = new Entrepot($db);
					$warehouse_array = $warehouse->list_array();
					if (count($warehouse_array) == 1) {
						$label = $object->type == FactureFournisseur::TYPE_CREDIT_NOTE ? $langs->trans("WarehouseForStockDecrease", current($warehouse_array)) : $langs->trans("WarehouseForStockIncrease", current($warehouse_array));
						$value = '<input type="hidden" id="idwarehouse" name="idwarehouse" value="'.key($warehouse_array).'">';
					} else {
						$label = $object->type == FactureFournisseur::TYPE_CREDIT_NOTE ? $langs->trans("SelectWarehouseForStockDecrease") : $langs->trans("SelectWarehouseForStockIncrease");
						$value = $formproduct->selectWarehouses(GETPOST('idwarehouse') ?GETPOST('idwarehouse') : 'ifone', 'idwarehouse', '', 1);
					}
					$formquestion = array(
						array('type' => 'other', 'name' => 'idwarehouse', 'label' => $label, 'value' => $value)
					);
				}

				$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ValidateBill'), $text, 'confirm_valid', $formquestion, 1, 1);
			}
		}

		// Confirmation edit (back to draft)
		if ($action == 'edit') {
			$formquestion = array();

			$qualified_for_stock_change = 0;
			if (empty($conf->global->STOCK_SUPPORTS_SERVICES)) {
				$qualified_for_stock_change = $object->hasProductsOrServices(2);
			} else {
				$qualified_for_stock_change = $object->hasProductsOrServices(1);
			}
			if (!empty($conf->stock->enabled) && !empty($conf->global->STOCK_CALCULATE_ON_SUPPLIER_BILL) && $qualified_for_stock_change) {
				$langs->load("stocks");
				require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
				$formproduct = new FormProduct($db);
				$warehouse = new Entrepot($db);
				$warehouse_array = $warehouse->list_array();
				if (count($warehouse_array) == 1) {
					$label = $object->type == FactureFournisseur::TYPE_CREDIT_NOTE ? $langs->trans("WarehouseForStockIncrease", current($warehouse_array)) : $langs->trans("WarehouseForStockDecrease", current($warehouse_array));
					$value = '<input type="hidden" id="idwarehouse" name="idwarehouse" value="'.key($warehouse_array).'">';
				} else {
					$label = $object->type == FactureFournisseur::TYPE_CREDIT_NOTE ? $langs->trans("SelectWarehouseForStockIncrease") : $langs->trans("SelectWarehouseForStockDecrease");
					$value = $formproduct->selectWarehouses(GETPOST('idwarehouse') ?GETPOST('idwarehouse') : 'ifone', 'idwarehouse', '', 1);
				}
				$formquestion = array(
					array('type' => 'other', 'name' => 'idwarehouse', 'label' => $label, 'value' => $value)
				);
			}
			$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('UnvalidateBill'), $langs->trans('ConfirmUnvalidateBill', $object->ref), 'confirm_edit', $formquestion, 1, 1);
		}

		// Confirmation set paid
		if ($action == 'paid' && $resteapayer <= 0) {
			$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ClassifyPaid'), $langs->trans('ConfirmClassifyPaidBill', $object->ref), 'confirm_paid', '', 0, 1);
		}

		if ($action == 'paid' && $resteapayer > 0) {
			$close = array();
			// Code
			$i = 0;
			$close[$i]['code'] = 'discount_vat'; // escompte
			$i++;
			$close[$i]['code'] = 'badsupplier';
			$i++;
			$close[$i]['code'] = 'other';
			$i++;
			// Help
			$i = 0;
			$close[$i]['label'] = $langs->trans("HelpEscompte").'<br><br>'.$langs->trans("ConfirmClassifyPaidPartiallyReasonDiscountVatDesc");
			$i++;
			$close[$i]['label'] = $langs->trans("ConfirmClassifyPaidPartiallyReasonBadSupplierDesc");
			$i++;
			$close[$i]['label'] = $langs->trans("Other");
			$i++;
			// Text
			$i = 0;
			$close[$i]['reason'] = $form->textwithpicto($langs->transnoentities("ConfirmClassifyPaidPartiallyReasonDiscount", $resteapayer, $langs->trans("Currency".$conf->currency)), $close[$i]['label'], 1);
			$i++;
			$close[$i]['reason'] = $form->textwithpicto($langs->transnoentities("ConfirmClassifyPaidPartiallyReasonBadCustomer", $resteapayer, $langs->trans("Currency".$conf->currency)), $close[$i]['label'], 1);
			$i++;
			$close[$i]['reason'] = $form->textwithpicto($langs->transnoentities("Other"), $close[$i]['label'], 1);
			$i++;
			// arrayreasons[code]=reason
			foreach ($close as $key => $val) {
				$arrayreasons[$close[$key]['code']] = $close[$key]['reason'];
			}

			// Create a form table
			$formquestion = array('text' => $langs->trans("ConfirmClassifyPaidPartiallyQuestion"), array('type' => 'radio', 'name' => 'close_code', 'label' => $langs->trans("Reason"), 'values' => $arrayreasons), array('type' => 'text', 'name' => 'close_note', 'label' => $langs->trans("Comment"), 'value' => '', 'morecss' => 'minwidth300'));
			// Incomplete payment. We ask if the reason is discount or other
			$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?facid='.$object->id, $langs->trans('ClassifyPaid'), $langs->trans('ConfirmClassifyPaidPartially', $object->ref), 'confirm_paid_partially', $formquestion, "yes", 1, 310);
		}

		// Confirmation of the abandoned classification
		if ($action == 'canceled') {
			// Code
			$close[1]['code'] = 'badsupplier';
			$close[2]['code'] = 'abandon';
			// Help
			$close[1]['label'] = $langs->trans("ConfirmClassifyPaidPartiallyReasonBadSupplierDesc");
			$close[2]['label'] = $langs->trans("ConfirmClassifyAbandonReasonOtherDesc");
			// Text
			$close[1]['reason'] = $form->textwithpicto($langs->transnoentities("ConfirmClassifyPaidPartiallyReasonBadSupplier", $object->ref), $close[1]['label'], 1);
			$close[2]['reason'] = $form->textwithpicto($langs->transnoentities("ConfirmClassifyAbandonReasonOther"), $close[2]['label'], 1);
			// arrayreasons
			$arrayreasons[$close[1]['code']] = $close[1]['reason'];
			$arrayreasons[$close[2]['code']] = $close[2]['reason'];

			// Create a form table
			$formquestion = array('text' => $langs->trans("ConfirmCancelBillQuestion"), array('type' => 'radio', 'name' => 'close_code', 'label' => $langs->trans("Reason"), 'values' => $arrayreasons), array('type' => 'text', 'name' => 'close_note', 'label' => $langs->trans("Comment"), 'value' => '', 'morecss' => 'minwidth300'));

			$formconfirm = $form->formconfirm($_SERVER['PHP_SELF'].'?id='.$object->id, $langs->trans('CancelBill'), $langs->trans('ConfirmCancelBill', $object->ref), 'confirm_canceled', $formquestion, "yes", 1, 250);
		}

		// Confirmation de la suppression de la facture fournisseur
		if ($action == 'delete') {
			$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('DeleteBill'), $langs->trans('ConfirmDeleteBill'), 'confirm_delete', '', 0, 1);
		}
		if ($action == 'deletepayment') {
			$payment_id = GETPOST('paiement_id');
			$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&paiement_id='.$payment_id, $langs->trans('DeletePayment'), $langs->trans('ConfirmDeletePayment'), 'confirm_delete_paiement', '', 0, 1);
		}

		// Confirmation to delete line
		if ($action == 'ask_deleteline') {
			$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&lineid='.$lineid, $langs->trans('DeleteProductLine'), $langs->trans('ConfirmDeleteProductLine'), 'confirm_deleteline', '', 0, 1);
		}

		if (!$formconfirm) {
			$parameters = array('formConfirm' => $formconfirm, 'lineid'=>$lineid);
			$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
			if (empty($reshook)) {
				$formconfirm .= $hookmanager->resPrint;
			} elseif ($reshook > 0) {
				$formconfirm = $hookmanager->resPrint;
			}
		}

		// Print form confirm
		print $formconfirm;


		// Supplier invoice card
		$linkback = '<a href="'.DOL_URL_ROOT.'/fourn/facture/list.php?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

		$morehtmlref = '<div class="refidno">';
		// Ref supplier
		$morehtmlref .= $form->editfieldkey("RefSupplier", 'ref_supplier', $object->ref_supplier, $object, $usercancreate, 'string', '', 0, 1);
		$morehtmlref .= $form->editfieldval("RefSupplier", 'ref_supplier', $object->ref_supplier, $object, $usercancreate, 'string', '', null, null, '', 1);
		// Thirdparty
		$morehtmlref .= '<br>'.$langs->trans('ThirdParty').' : '.$object->thirdparty->getNomUrl(1);
		if (empty($conf->global->MAIN_DISABLE_OTHER_LINK) && $object->thirdparty->id > 0) {
			$morehtmlref .= ' (<a href="'.DOL_URL_ROOT.'/fourn/facture/list.php?socid='.$object->thirdparty->id.'&search_company='.urlencode($object->thirdparty->name).'">'.$langs->trans("OtherBills").'</a>)';
		}
		// Project
		if (!empty($conf->projet->enabled)) {
			$langs->load("projects");
			$morehtmlref .= '<br>'.$langs->trans('Project').' ';
			if ($usercancreate) {
				if ($action != 'classify') {
					$morehtmlref .= '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?action=classify&amp;id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetProject')).'</a> : ';
				}
				if ($action == 'classify') {
					//$morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'projectid', 0, 0, 1, 1);
					$morehtmlref .= '<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
					$morehtmlref .= '<input type="hidden" name="action" value="classin">';
					$morehtmlref .= '<input type="hidden" name="token" value="'.newToken().'">';
					$morehtmlref .= $formproject->select_projects((empty($conf->global->PROJECT_CAN_ALWAYS_LINK_TO_ALL_SUPPLIERS) ? $object->socid : -1), $object->fk_project, 'projectid', $maxlength, 0, 1, 0, 1, 0, 0, '', 1);
					$morehtmlref .= '<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
					$morehtmlref .= '</form>';
				} else {
					$morehtmlref .= $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, $object->socid, $object->fk_project, 'none', 0, 0, 0, 1);
				}
			} else {
				if (!empty($object->fk_project)) {
					$proj = new Project($db);
					$proj->fetch($object->fk_project);
					$morehtmlref .= '<a href="'.DOL_URL_ROOT.'/projet/card.php?id='.$object->fk_project.'" title="'.$langs->trans('ShowProject').'">';
					$morehtmlref .= $proj->ref;
					$morehtmlref .= '</a>';
				} else {
					$morehtmlref .= '';
				}
			}
		}
		$morehtmlref .= '</div>';

		$object->totalpaye = $totalpaye; // To give a chance to dol_banner_tab to use already paid amount to show correct status

		dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

		print '<div class="fichecenter">';
		print '<div class="fichehalfleft">';
		print '<div class="underbanner clearboth"></div>';

		print '<table class="border tableforfield" width="100%">';

		// Type
		print '<tr><td class="titlefield">'.$langs->trans('Type').'</td><td>';
		print '<span class="badgeneutral">';
		print $object->getLibType();
		print '</span>';
		if ($object->type == FactureFournisseur::TYPE_REPLACEMENT) {
			$facreplaced = new FactureFournisseur($db);
			$facreplaced->fetch($object->fk_facture_source);
			print ' ('.$langs->transnoentities("ReplaceInvoice", $facreplaced->getNomUrl(1)).')';
		}
		if ($object->type == FactureFournisseur::TYPE_CREDIT_NOTE) {
			$facusing = new FactureFournisseur($db);
			$facusing->fetch($object->fk_facture_source);
			print ' ('.$langs->transnoentities("CorrectInvoice", $facusing->getNomUrl(1)).')';
		}

		$facidavoir = $object->getListIdAvoirFromInvoice();
		if (count($facidavoir) > 0) {
			$invoicecredits = array();
			foreach ($facidavoir as $id) {
				$facavoir = new FactureFournisseur($db);
				$facavoir->fetch($id);
				$invoicecredits[] = $facavoir->getNomUrl(1);
			}
			print ' ('.$langs->transnoentities("InvoiceHasAvoir") . (count($invoicecredits) ? ' ' : '') . implode(',', $invoicecredits) . ')';
		}
		if (isset($facidnext) && $facidnext > 0) {
			$facthatreplace = new FactureFournisseur($db);
			$facthatreplace->fetch($facidnext);
			print ' ('.$langs->transnoentities("ReplacedByInvoice", $facthatreplace->getNomUrl(1)).')';
		}
		if ($object->type == FactureFournisseur::TYPE_CREDIT_NOTE || $object->type == FactureFournisseur::TYPE_DEPOSIT) {
			$discount = new DiscountAbsolute($db);
			$result = $discount->fetch(0, 0, $object->id);
			if ($result > 0) {
				print ' <span class="opacitymediumbycolor paddingleft">';
				$s = $langs->trans("CreditNoteConvertedIntoDiscount", '{s1}', '{s2}');
				$s = str_replace('{s1}', $object->getLibType(1), $s);
				$s = str_replace('{s2}', $discount->getNomUrl(1, 'discount'), $s);
				print $s;
				print '</span><br>';
			}
		}
		print '</td></tr>';


		// Relative and absolute discounts
		print '<!-- Discounts --><tr><td>'.$langs->trans('Discounts');
		print '</td><td>';

		$thirdparty = $societe;
		$discount_type = 1;
		$backtopage = urlencode($_SERVER["PHP_SELF"].'?facid='.$object->id);
		include DOL_DOCUMENT_ROOT.'/core/tpl/object_discounts.tpl.php';

		print '</td></tr>';

		// Label
		print '<tr>';
		print '<td>'.$form->editfieldkey("Label", 'label', $object->label, $object, $usercancreate).'</td>';
		print '<td>'.$form->editfieldval("Label", 'label', $object->label, $object, $usercancreate).'</td>';
		print '</tr>';

		$form_permission = ($object->statut < FactureFournisseur::STATUS_CLOSED) && $usercancreate && ($object->getSommePaiement() <= 0);
		$form_permission2 = ($object->statut < FactureFournisseur::STATUS_CLOSED) && $usercancreate;

		// Date
		print '<tr><td>';
		print $form->editfieldkey("DateInvoice", 'datef', $object->datep, $object, $form_permission, 'datepicker');
		print '</td><td colspan="3">';
		print $form->editfieldval("Date", 'datef', $object->datep, $object, $form_permission, 'datepicker');
		print '</td>';

		// Default terms of the settlement
		$langs->load('bills');
		print '<tr><td class="nowrap">';
		print '<table width="100%" class="nobordernopadding"><tr><td class="nowrap">';
		print $langs->trans('PaymentConditions');
		print '<td>';
		if ($action != 'editconditions' && $form_permission) {
			print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editconditions&amp;id='.$object->id.'">'.img_edit($langs->trans('SetConditions'), 1).'</a></td>';
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

		// Due date
		print '<tr><td>';
		print $form->editfieldkey("DateMaxPayment", 'date_lim_reglement', $object->date_echeance, $object, $form_permission2, 'datepicker');
		print '</td><td>';
		print $form->editfieldval("DateMaxPayment", 'date_lim_reglement', $object->date_echeance, $object, $form_permission2, 'datepicker');
		if ($action != 'editdate_lim_reglement' && $object->hasDelay()) {
			print img_warning($langs->trans('Late'));
		}
		print '</td>';

		// Mode of payment
		$langs->load('bills');
		print '<tr><td class="nowrap">';
		print '<table width="100%" class="nobordernopadding"><tr><td class="nowrap">';
		print $langs->trans('PaymentMode');
		print '</td>';
		if ($action != 'editmode' && $form_permission2) {
			print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editmode&amp;id='.$object->id.'">'.img_edit($langs->trans('SetMode'), 1).'</a></td>';
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
		if (!empty($conf->multicurrency->enabled)) {
			// Multicurrency code
			print '<tr>';
			print '<td>';
			print '<table class="nobordernopadding" width="100%"><tr><td>';
			print $form->editfieldkey('Currency', 'multicurrency_code', '', $object, 0);
			print '</td>';
			if ($action != 'editmulticurrencycode' && $object->statut == $object::STATUS_DRAFT) {
				print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editmulticurrencycode&amp;id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetMultiCurrencyCode'), 1).'</a></td>';
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
				print '<table class="nobordernopadding centpercent"><tr><td>';
				print $form->editfieldkey('CurrencyRate', 'multicurrency_tx', '', $object, 0);
				print '</td>';
				if ($action != 'editmulticurrencyrate' && $object->statut == $object::STATUS_DRAFT && $object->multicurrency_code && $object->multicurrency_code != $conf->currency) {
					print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editmulticurrencyrate&amp;id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetMultiCurrencyCode'), 1).'</a></td>';
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
		if (!empty($conf->banque->enabled)) {
			print '<tr><td class="nowrap">';
			print '<table width="100%" class="nobordernopadding"><tr><td class="nowrap">';
			print $langs->trans('BankAccount');
			print '<td>';
			if ($action != 'editbankaccount' && $usercancreate) {
				print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editbankaccount&amp;id='.$object->id.'">'.img_edit($langs->trans('SetBankAccount'), 1).'</a></td>';
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
		if (!empty($conf->incoterm->enabled)) {
			print '<tr><td>';
			print '<table width="100%" class="nobordernopadding"><tr><td>';
			print $langs->trans('IncotermLabel');
			print '<td><td class="right">';
			if ($usercancreate) {
				print '<a class="editfielda" href="'.DOL_URL_ROOT.'/fourn/facture/card.php?facid='.$object->id.'&action=editincoterm">'.img_edit().'</a>';
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

		// Intracomm report
		if (!empty($conf->intracommreport->enabled)) {
			$langs->loadLangs(array("intracommreport"));
			print '<tr><td>';
			print '<table width="100%" class="nobordernopadding"><tr><td>';
			print $langs->trans('IntracommReportTransportMode');
			print '</td>';
			if ($action != 'editmode' && ($user->rights->fournisseur->facture->creer || $user->rights->supplier_invoice->creer)) {
				print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editmode&amp;id='.$object->id.'">'.img_edit().'</a></td>';
			}
			print '</tr></table>';
			print '</td>';
			print '<td>';
			if ($action == 'editmode') {
				$form->formSelectTransportMode($_SERVER['PHP_SELF'].'?id='.$object->id, $object->transport_mode_id, 'transport_mode_id', 1, 1);
			} else {
				$form->formSelectTransportMode($_SERVER['PHP_SELF'].'?id='.$object->id, $object->transport_mode_id, 'none');
			}
			print '</td></tr>';
		}

		// Other attributes
		$cols = 2;
		include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

		print '</table>';
		print '</div>';

		print '<div class="fichehalfright">';
		print '<div class="ficheaddleft">';
		print '<div class="underbanner clearboth"></div>';

		print '<table class="border tableforfield centpercent">';

		if (!empty($conf->multicurrency->enabled) && ($object->multicurrency_code != $conf->currency)) {
			// Multicurrency Amount HT
			print '<tr><td class="titlefieldmiddle">'.$form->editfieldkey('MulticurrencyAmountHT', 'multicurrency_total_ht', '', $object, 0).'</td>';
			print '<td class="nowrap">'.price($object->multicurrency_total_ht, '', $langs, 0, - 1, - 1, (!empty($object->multicurrency_code) ? $object->multicurrency_code : $conf->currency)).'</td>';
			print '</tr>';

			// Multicurrency Amount VAT
			print '<tr><td>'.$form->editfieldkey('MulticurrencyAmountVAT', 'multicurrency_total_tva', '', $object, 0).'</td>';
			print '<td>'.price($object->multicurrency_total_tva, '', $langs, 0, - 1, - 1, (!empty($object->multicurrency_code) ? $object->multicurrency_code : $conf->currency)).'</td>';
			print '</tr>';

			// Multicurrency Amount TTC
			print '<tr><td>'.$form->editfieldkey('MulticurrencyAmountTTC', 'multicurrency_total_ttc', '', $object, 0).'</td>';
			print '<td>'.price($object->multicurrency_total_ttc, '', $langs, 0, - 1, - 1, (!empty($object->multicurrency_code) ? $object->multicurrency_code : $conf->currency)).'</td>';
			print '</tr>';
		}

		// Amount
		print '<tr><td class="titlefield">'.$langs->trans('AmountHT').'</td><td>'.price($object->total_ht, 1, $langs, 0, -1, -1, $conf->currency).'</td></tr>';
		print '<tr><td>'.$langs->trans('AmountVAT').'</td><td>'.price($object->total_tva, 1, $langs, 0, -1, -1, $conf->currency).'<div class="inline-block"> &nbsp; &nbsp; &nbsp; &nbsp; ';
		if (GETPOST('calculationrule')) {
			$calculationrule = GETPOST('calculationrule', 'alpha');
		} else {
			$calculationrule = (empty($conf->global->MAIN_ROUNDOFTOTAL_NOT_TOTALOFROUND) ? 'totalofround' : 'roundoftotal');
		}
		if ($calculationrule == 'totalofround') {
			$calculationrulenum = 1;
		} else {
			$calculationrulenum = 2;
		}
		// Show link for "recalculate"
		if ($object->getVentilExportCompta() == 0) {
			$s = $langs->trans("ReCalculate").' ';
			$s .= '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=calculate&calculationrule=totalofround">'.$langs->trans("Mode1").'</a>';
			$s .= ' / ';
			$s .= '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=calculate&calculationrule=roundoftotal">'.$langs->trans("Mode2").'</a>';
			print $form->textwithtooltip($s, $langs->trans("CalculationRuleDesc", $calculationrulenum).'<br>'.$langs->trans("CalculationRuleDescSupplier"), 2, 1, img_picto('', 'help'));
		}
		print '</div></td></tr>';

		// Amount Local Taxes
		//TODO: Place into a function to control showing by country or study better option
		if ($societe->localtax1_assuj == "1") { //Localtax1
			print '<tr><td>'.$langs->transcountry("AmountLT1", $societe->country_code).'</td>';
			print '<td>'.price($object->total_localtax1, 1, $langs, 0, -1, -1, $conf->currency).'</td>';
			print '</tr>';
		}
		if ($societe->localtax2_assuj == "1") { //Localtax2
			print '<tr><td>'.$langs->transcountry("AmountLT2", $societe->country_code).'</td>';
			print '<td>'.price($object->total_localtax2, 1, $langs, 0, -1, -1, $conf->currency).'</td>';
			print '</tr>';
		}
		print '<tr><td>'.$langs->trans('AmountTTC').'</td><td colspan="3">'.price($object->total_ttc, 1, $langs, 0, -1, -1, $conf->currency).'</td></tr>';

		print '</table>';


		// List of payments

		$totalpaye = 0;

		$sign = 1;
		if ($object->type == FactureFournisseur::TYPE_CREDIT_NOTE) {
			$sign = - 1;
		}

		$nbrows = 9; $nbcols = 3;
		if (!empty($conf->projet->enabled)) {
			$nbrows++;
		}
		if (!empty($conf->banque->enabled)) {
			$nbrows++; $nbcols++;
		}
		if (!empty($conf->incoterm->enabled)) {
			$nbrows++;
		}
		if (!empty($conf->multicurrency->enabled)) {
			$nbrows += 5;
		}

		// Local taxes
		if ($societe->localtax1_assuj == "1") {
			$nbrows++;
		}
		if ($societe->localtax2_assuj == "1") {
			$nbrows++;
		}

		$sql = 'SELECT p.datep as dp, p.ref, p.num_paiement as num_payment, p.rowid, p.fk_bank,';
		$sql .= ' c.id as paiement_type,';
		$sql .= ' pf.amount,';
		$sql .= ' ba.rowid as baid, ba.ref as baref, ba.label, ba.number as banumber, ba.account_number, ba.fk_accountancy_journal';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'paiementfourn as p';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'bank as b ON p.fk_bank = b.rowid';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'bank_account as ba ON b.fk_account = ba.rowid';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_paiement as c ON p.fk_paiement = c.id';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'paiementfourn_facturefourn as pf ON pf.fk_paiementfourn = p.rowid';
		$sql .= ' WHERE pf.fk_facturefourn = '.$object->id;
		$sql .= ' ORDER BY p.datep, p.tms';

		$result = $db->query($sql);
		if ($result) {
			$num = $db->num_rows($result);
			$i = 0;

			print '<div class="div-table-responsive-no-min">';
			print '<table class="noborder paymenttable" width="100%">';
			print '<tr class="liste_titre">';
			print '<td class="liste_titre">'.($object->type == FactureFournisseur::TYPE_CREDIT_NOTE ? $langs->trans("PaymentsBack") : $langs->trans('Payments')).'</td>';
			print '<td>'.$langs->trans('Date').'</td>';
			print '<td>'.$langs->trans('Type').'</td>';
			if (!empty($conf->banque->enabled)) {
				print '<td class="right">'.$langs->trans('BankAccount').'</td>';
			}
			print '<td class="right">'.$langs->trans('Amount').'</td>';
			print '<td width="18">&nbsp;</td>';
			print '</tr>';

			if ($num > 0) {
				while ($i < $num) {
					$objp = $db->fetch_object($result);

					$paymentstatic->id = $objp->rowid;
					$paymentstatic->datepaye = $db->jdate($objp->dp);
					$paymentstatic->ref = ($objp->ref ? $objp->ref : $objp->rowid);
					$paymentstatic->num_payment = $objp->num_payment;
					$paymentstatic->payment_code = $objp->payment_code;

					print '<tr class="oddeven">';
					print '<td>';
					print $paymentstatic->getNomUrl(1);
					print '</td>';
					print '<td>'.dol_print_date($db->jdate($objp->dp), 'day').'</td>';
					print '<td>';
					print $form->form_modes_reglement(null, $objp->paiement_type, 'none').' '.$objp->num_payment;
					print '</td>';
					if (!empty($conf->banque->enabled)) {
						$bankaccountstatic->id = $objp->baid;
						$bankaccountstatic->ref = $objp->baref;
						$bankaccountstatic->label = $objp->baref;
						$bankaccountstatic->number = $objp->banumber;

						if (!empty($conf->accounting->enabled)) {
							$bankaccountstatic->account_number = $objp->account_number;

							$accountingjournal = new AccountingJournal($db);
							$accountingjournal->fetch($objp->fk_accountancy_journal);
							$bankaccountstatic->accountancy_journal = $accountingjournal->getNomUrl(0, 1, 1, '', 1);
						}

						print '<td class="right">';
						if ($objp->baid > 0) {
							print $bankaccountstatic->getNomUrl(1, 'transactions');
						}
						print '</td>';
					}
					print '<td class="right">'.price($sign * $objp->amount).'</td>';
					print '<td class="center">';
					if ($object->statut == FactureFournisseur::STATUS_VALIDATED && $object->paye == 0 && $user->socid == 0) {
						print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=deletepayment&token='.newToken().'&paiement_id='.$objp->rowid.'">';
						print img_delete();
						print '</a>';
					}
					print '</td>';
					print '</tr>';
					$totalpaye += $objp->amount;
					$i++;
				}
			} else {
				print '<tr class="oddeven"><td colspan="'.$nbcols.'"><span class="opacitymedium">'.$langs->trans("None").'</span></td><td></td><td></td></tr>';
			}

			/*
			if ($object->paye == 0)
			{
				print '<tr><td colspan="'.$nbcols.'" class="right">'.$langs->trans('AlreadyPaid').' :</td><td class="right">'.price($totalpaye).'</td><td></td></tr>';
				print '<tr><td colspan="'.$nbcols.'" class="right">'.$langs->trans("Billed").' :</td><td class="right">'.price($object->total_ttc).'</td><td></td></tr>';

				$resteapayer = $object->total_ttc - $totalpaye;

				print '<tr><td colspan="'.$nbcols.'" class="right">'.$langs->trans('RemainderToPay').' :</td>';
				print '<td class="right'.($resteapayer?' amountremaintopay':'').'">'.price($resteapayer).'</td><td></td></tr>';
			}
			*/

			$db->free($result);
		} else {
			dol_print_error($db);
		}

		if ($object->type != FactureFournisseur::TYPE_CREDIT_NOTE) {
			// Total already paid
			print '<tr><td colspan="'.$nbcols.'" class="right">';
			print '<span class="opacitymedium">';
			if ($object->type != FactureFournisseur::TYPE_DEPOSIT) {
				print $langs->trans('AlreadyPaidNoCreditNotesNoDeposits');
			} else {
				print $langs->trans('AlreadyPaid');
			}
			print '</span>';
			print '</td><td class="right"'.(($totalpaye > 0) ? ' class="amountalreadypaid"' : '').'>'.price($totalpaye).'</td><td>&nbsp;</td></tr>';

			//$resteapayer = $object->total_ttc - $totalpaye;
			$resteapayeraffiche = $resteapayer;

			$cssforamountpaymentcomplete = 'amountpaymentcomplete';

			// Loop on each credit note or deposit amount applied
			$creditnoteamount = 0;
			$depositamount = 0;


			$sql = "SELECT re.rowid, re.amount_ht, re.amount_tva, re.amount_ttc,";
			$sql .= " re.description, re.fk_invoice_supplier_source";
			$sql .= " FROM ".MAIN_DB_PREFIX."societe_remise_except as re";
			$sql .= " WHERE fk_invoice_supplier = ".$object->id;
			$resql = $db->query($sql);
			if ($resql) {
				$num = $db->num_rows($resql);
				$i = 0;
				$invoice = new FactureFournisseur($db);
				while ($i < $num) {
					$obj = $db->fetch_object($resql);
					$invoice->fetch($obj->fk_invoice_supplier_source);
					print '<tr><td colspan="'.$nbcols.'" class="right">';
					if ($invoice->type == FactureFournisseur::TYPE_CREDIT_NOTE) {
						print $langs->trans("CreditNote").' ';
					}
					if ($invoice->type == FactureFournisseur::TYPE_DEPOSIT) {
						print $langs->trans("Deposit").' ';
					}
					print $invoice->getNomUrl(0);
					print ' :</td>';
					print '<td class="right">'.price($obj->amount_ttc).'</td>';
					print '<td class="right">';
					print '<a href="'.$_SERVER["PHP_SELF"].'?facid='.$object->id.'&action=unlinkdiscount&discountid='.$obj->rowid.'">'.img_delete().'</a>';
					print '</td></tr>';
					$i++;
					if ($invoice->type == FactureFournisseur::TYPE_CREDIT_NOTE) {
						$creditnoteamount += $obj->amount_ttc;
					}
					if ($invoice->type == FactureFournisseur::TYPE_DEPOSIT) {
						$depositamount += $obj->amount_ttc;
					}
				}
			} else {
				dol_print_error($db);
			}

			// Paye partiellement 'escompte'
			if (($object->statut == FactureFournisseur::STATUS_CLOSED || $object->statut == FactureFournisseur::STATUS_ABANDONED) && $object->close_code == 'discount_vat') {
				print '<tr><td colspan="'.$nbcols.'" class="right nowrap">';
				print '<span class="opacitymedium">';
				print $form->textwithpicto($langs->trans("Discount"), $langs->trans("HelpEscompte"), - 1);
				print '</span>';
				print '</td><td class="right">'.price($object->total_ttc - $creditnoteamount - $depositamount - $totalpaye).'</td><td>&nbsp;</td></tr>';
				$resteapayeraffiche = 0;
				$cssforamountpaymentcomplete = 'amountpaymentneutral';
			}
			// Paye partiellement ou Abandon 'badsupplier'
			if (($object->statut == FactureFournisseur::STATUS_CLOSED || $object->statut == FactureFournisseur::STATUS_ABANDONED) && $object->close_code == 'badsupplier') {
				print '<tr><td colspan="'.$nbcols.'" class="right nowrap">';
				print '<span class="opacitymedium">';
				print $form->textwithpicto($langs->trans("Abandoned"), $langs->trans("HelpAbandonBadCustomer"), - 1);
				print '</span>';
				print '</td><td class="right">'.price($object->total_ttc - $creditnoteamount - $depositamount - $totalpaye).'</td><td>&nbsp;</td></tr>';
				// $resteapayeraffiche=0;
				$cssforamountpaymentcomplete = 'amountpaymentneutral';
			}
			// Paye partiellement ou Abandon 'product_returned'
			if (($object->statut == FactureFournisseur::STATUS_CLOSED || $object->statut == FactureFournisseur::STATUS_ABANDONED) && $object->close_code == 'product_returned') {
				print '<tr><td colspan="'.$nbcols.'" class="right nowrap">';
				print '<span class="opacitymedium">';
				print $form->textwithpicto($langs->trans("ProductReturned"), $langs->trans("HelpAbandonProductReturned"), - 1);
				print '</span>';
				print '</td><td class="right">'.price($object->total_ttc - $creditnoteamount - $depositamount - $totalpaye).'</td><td>&nbsp;</td></tr>';
				$resteapayeraffiche = 0;
				$cssforamountpaymentcomplete = 'amountpaymentneutral';
			}
			// Paye partiellement ou Abandon 'abandon'
			if (($object->statut == FactureFournisseur::STATUS_CLOSED || $object->statut == FactureFournisseur::STATUS_ABANDONED) && $object->close_code == 'abandon') {
				print '<tr><td colspan="'.$nbcols.'" class="right nowrap">';
				$text = $langs->trans("HelpAbandonOther");
				if ($object->close_note) {
					$text .= '<br><br><b>'.$langs->trans("Reason").'</b>:'.$object->close_note;
				}
				print '<span class="opacitymedium">';
				print $form->textwithpicto($langs->trans("Abandoned"), $text, - 1);
				print '</span>';
				print '</td><td class="right">'.price($object->total_ttc - $creditnoteamount - $depositamount - $totalpaye).'</td><td>&nbsp;</td></tr>';
				$resteapayeraffiche = 0;
				$cssforamountpaymentcomplete = 'amountpaymentneutral';
			}

			// Billed
			print '<tr><td colspan="'.$nbcols.'" class="right">';
			print '<span class="opacitymedium">';
			print $langs->trans("Billed");
			print '</span>';
			print '</td><td class="right">'.price($object->total_ttc).'</td><td>&nbsp;</td></tr>';

			// Remainder to pay
			print '<tr><td colspan="'.$nbcols.'" class="right">';
			print '<span class="opacitymedium">';
			if ($resteapayeraffiche >= 0) {
				print $langs->trans('RemainderToPay');
			} else {
				print $langs->trans('ExcessPaid');
			}
			print '</span>';
			print '</td>';
			print '<td class="right'.($resteapayeraffiche ? ' amountremaintopay' : (' '.$cssforamountpaymentcomplete)).'">'.price($resteapayeraffiche).'</td>';
			print '<td class="nowrap">&nbsp;</td></tr>';
		} else // Credit note
		{
			$cssforamountpaymentcomplete = 'amountpaymentneutral';

			// Total already paid back
			print '<tr><td colspan="'.$nbcols.'" class="right">';
			print $langs->trans('AlreadyPaidBack');
			print ' :</td><td class="right">'.price($sign * $totalpaye).'</td><td>&nbsp;</td></tr>';

			// Billed
			print '<tr><td colspan="'.$nbcols.'" class="right">'.$langs->trans("Billed").' :</td><td class="right">'.price($sign * $object->total_ttc).'</td><td>&nbsp;</td></tr>';

			// Remainder to pay back
			print '<tr><td colspan="'.$nbcols.'" class="right">';
			print '<span class="opacitymedium">';
			if ($resteapayeraffiche <= 0) {
				print $langs->trans('RemainderToPayBack');
			} else {
				print $langs->trans('ExcessPaid');
			}
			print '</td>';
			print '</span>';
			print '<td class="right'.($resteapayeraffiche ? ' amountremaintopay' : (' '.$cssforamountpaymentcomplete)).'">'.price($sign * $resteapayeraffiche).'</td>';
			print '<td class="nowrap">&nbsp;</td></tr>';

			// Sold credit note
			// print '<tr><td colspan="'.$nbcols.'" class="right">'.$langs->trans('TotalTTC').' :</td>';
			// print '<td class="right" style="border: 1px solid;" bgcolor="#f0f0f0"><b>'.price($sign *
			// $object->total_ttc).'</b></td><td>&nbsp;</td></tr>';
		}

		print '</table>';
		print '</div>';

		print '</div>';
		print '</div>';
		print '</div>';

		print '<div class="clearboth"></div><br>';

		if (!empty($conf->global->MAIN_DISABLE_CONTACTS_TAB)) {
			$blocname = 'contacts';
			$title = $langs->trans('ContactsAddresses');
			include DOL_DOCUMENT_ROOT.'/core/tpl/bloc_showhide.tpl.php';
		}

		if (!empty($conf->global->MAIN_DISABLE_NOTES_TAB)) {
			$colwidth = 20;
			$blocname = 'notes';
			$title = $langs->trans('Notes');
			include DOL_DOCUMENT_ROOT.'/core/tpl/bloc_showhide.tpl.php';
		}


		/*
		 * Lines
		 */
		print '<form name="addproduct" id="addproduct" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.(($action != 'editline') ? '' : '#line_'.GETPOST('lineid', 'int')).'" method="POST">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="action" value="'.(($action != 'editline') ? 'addline' : 'updateline').'">';
		print '<input type="hidden" name="mode" value="">';
		print '<input type="hidden" name="page_y" value="">';
		print '<input type="hidden" name="id" value="'.$object->id.'">';
		print '<input type="hidden" name="socid" value="'.$societe->id.'">';

		if (!empty($conf->use_javascript_ajax) && $object->statut == FactureFournisseur::STATUS_DRAFT) {
			include DOL_DOCUMENT_ROOT.'/core/tpl/ajaxrow.tpl.php';
		}

		print '<div class="div-table-responsive-no-min">';
		print '<table id="tablelines" class="noborder noshadow" width="100%">';

		global $forceall, $senderissupplier, $dateSelector, $inputalsopricewithtax;
		$forceall = 1; $dateSelector = 0; $inputalsopricewithtax = 1;
		$senderissupplier = 2; // $senderissupplier=2 is same than 1 but disable test on minimum qty and disable autofill qty with minimum.
		//if (! empty($conf->global->SUPPLIER_INVOICE_WITH_NOPRICEDEFINED)) $senderissupplier=2;
		if (!empty($conf->global->SUPPLIER_INVOICE_WITH_PREDEFINED_PRICES_ONLY)) {
			$senderissupplier = 1;
		}

		// Show object lines
		if (!empty($object->lines)) {
			$ret = $object->printObjectLines($action, $societe, $mysoc, $lineid, 1);
		}

		$num = count($object->lines);

		// Form to add new line
		if ($object->statut == FactureFournisseur::STATUS_DRAFT && $usercancreate) {
			if ($action != 'editline') {
				// Add free products/services

				$parameters = array();
				$reshook = $hookmanager->executeHooks('formAddObjectLine', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
				if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
				if (empty($reshook))
					$object->formAddObjectLine(1, $societe, $mysoc);
			}
		}

		print '</table>';
		print '</div>';
		print '</form>';

		print dol_get_fiche_end();


		if ($action != 'presend') {
			/*
			 * Buttons actions
			 */

			print '<div class="tabsAction">';

			$parameters = array();
			$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been
																									  // modified by hook
			if (empty($reshook)) {
				// Modify a validated invoice with no payments
				if ($object->statut == FactureFournisseur::STATUS_VALIDATED && $action != 'confirm_edit' && $object->getSommePaiement() == 0 && $usercancreate) {
					// We check if lines of invoice are not already transfered into accountancy
					$ventilExportCompta = $object->getVentilExportCompta(); // Should be 0 since the sum of payments are zero. But we keep the protection.

					if ($ventilExportCompta == 0) {
						print '<a class="butAction'.($conf->use_javascript_ajax ? ' reposition' : '').'" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;action=edit">'.$langs->trans('Modify').'</a>';
					} else {
						print '<span class="butActionRefused classfortooltip" title="'.$langs->trans("DisabledBecauseDispatchedInBookkeeping").'">'.$langs->trans('Modify').'</span>';
					}
				}

				$discount = new DiscountAbsolute($db);
				$result = $discount->fetch(0, 0, $object->id);

				// Reopen a standard paid invoice
				if (($object->type == FactureFournisseur::TYPE_STANDARD || $object->type == FactureFournisseur::TYPE_REPLACEMENT
					|| ($object->type == FactureFournisseur::TYPE_CREDIT_NOTE && empty($discount->id))
					|| ($object->type == FactureFournisseur::TYPE_DEPOSIT && empty($discount->id)))
					&& ($object->statut == FactureFournisseur::STATUS_CLOSED || $object->statut == FactureFournisseur::STATUS_ABANDONED)) {				// A paid invoice (partially or completely)
					if (!$facidnext && $object->close_code != 'replaced' && $usercancreate) {	// Not replaced by another invoice
						print '<a class="butAction'.($conf->use_javascript_ajax ? ' reposition' : '').'" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;action=reopen">'.$langs->trans('ReOpen').'</a>';
					} else {
						if ($usercancreate) {
							print '<span class="butActionRefused classfortooltip" title="'.$langs->trans("DisabledBecauseReplacedInvoice").'">'.$langs->trans('ReOpen').'</span>';
						} elseif (empty($conf->global->MAIN_BUTTON_HIDE_UNAUTHORIZED)) {
							print '<span class="butActionRefused classfortooltip">'.$langs->trans('ReOpen').'</span>';
						}
					}
				}

				// Validate
				if ($action != 'confirm_edit' && $object->statut == FactureFournisseur::STATUS_DRAFT) {
					if (count($object->lines)) {
						if ($usercanvalidate) {
							print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=valid"';
							print '>'.$langs->trans('Validate').'</a>';
						} else {
							print '<a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("NotAllowed")).'"';
							print '>'.$langs->trans('Validate').'</a>';
						}
					}
				}

				// Send by mail
				if (empty($user->socid)) {
					if (($object->statut == FactureFournisseur::STATUS_VALIDATED || $object->statut == FactureFournisseur::STATUS_CLOSED)) {
						if ($usercansend) {
							print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=presend&mode=init#formmailbeforetitle">'.$langs->trans('SendMail').'</a>';
						} else {
							print '<span class="butActionRefused classfortooltip">'.$langs->trans('SendMail').'</span>';
						}
					}
				}

				// Create payment
				if ($object->type != FactureFournisseur::TYPE_CREDIT_NOTE && $object->statut == FactureFournisseur::STATUS_VALIDATED && $object->paye == 0) {
					print '<a class="butAction'.($conf->use_javascript_ajax ? ' reposition' : '').'" href="'.DOL_URL_ROOT.'/fourn/facture/paiement.php?facid='.$object->id.'&amp;action=create'.($object->fk_account > 0 ? '&amp;accountid='.$object->fk_account : '').'">'.$langs->trans('DoPayment').'</a>'; // must use facid because id is for payment id not invoice
				}

				// Reverse back money or convert to reduction
				if ($object->type == FactureFournisseur::TYPE_CREDIT_NOTE || $object->type == FactureFournisseur::TYPE_DEPOSIT || $object->type == FactureFournisseur::TYPE_STANDARD) {
					// For credit note only
					if ($object->type == FactureFournisseur::TYPE_CREDIT_NOTE && $object->statut == 1 && $object->paye == 0) {
						if ($resteapayer == 0) {
							print '<span class="butActionRefused classfortooltip" title="'.$langs->trans("DisabledBecauseRemainderToPayIsZero").'">'.$langs->trans('DoPaymentBack').'</span>';
						} else {
							print '<a class="butAction" href="'.DOL_URL_ROOT.'/fourn/facture/paiement.php?facid='.$object->id.'&amp;action=create&amp;accountid='.$object->fk_account.'">'.$langs->trans('DoPaymentBack').'</a>';
						}
					}

					// For standard invoice with excess paid
					if ($object->type == FactureFournisseur::TYPE_STANDARD && empty($object->paye) && ($object->total_ttc - $totalpaye - $totalcreditnotes - $totaldeposits) < 0 && $usercancreate && empty($discount->id)) {
						print '<a class="butAction'.($conf->use_javascript_ajax ? ' reposition' : '').'" href="'.$_SERVER["PHP_SELF"].'?facid='.$object->id.'&amp;action=converttoreduc">'.$langs->trans('ConvertExcessPaidToReduc').'</a>';
					}
					// For credit note
					if ($object->type == FactureFournisseur::TYPE_CREDIT_NOTE && $object->statut == 1 && $object->paye == 0 && $usercancreate
						&& (!empty($conf->global->SUPPLIER_INVOICE_ALLOW_REUSE_OF_CREDIT_WHEN_PARTIALLY_REFUNDED) || $object->getSommePaiement() == 0)
						) {
						print '<a class="butAction'.($conf->use_javascript_ajax ? ' reposition' : '').'" href="'.$_SERVER["PHP_SELF"].'?facid='.$object->id.'&amp;action=converttoreduc" title="'.dol_escape_htmltag($langs->trans("ConfirmConvertToReducSupplier2")).'">'.$langs->trans('ConvertToReduc').'</a>';
					}
					// For deposit invoice
					if ($object->type == FactureFournisseur::TYPE_DEPOSIT && $usercancreate && $object->statut > 0 && empty($discount->id)) {
						print '<a class="butAction'.($conf->use_javascript_ajax ? ' reposition' : '').'" href="'.$_SERVER["PHP_SELF"].'?facid='.$object->id.'&amp;action=converttoreduc">'.$langs->trans('ConvertToReduc').'</a>';
					}
				}

				// Classify paid
				if (($object->statut == FactureFournisseur::STATUS_VALIDATED && $object->paye == 0 && (($object->type != FactureFournisseur::TYPE_CREDIT_NOTE && $object->type != FactureFournisseur::TYPE_DEPOSIT && $resteapayer <= 0) || ($object->type == FactureFournisseur::TYPE_CREDIT_NOTE && $resteapayer >= 0)))
					|| ($object->type == FactureFournisseur::TYPE_DEPOSIT && $object->paye == 0 && $object->total_ttc > 0 && $resteapayer == 0 && empty($discount->id))
				) {
					print '<a class="butAction'.($conf->use_javascript_ajax ? ' reposition' : '').'" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=paid">'.$langs->trans('ClassifyPaid').'</a>';
				}

				// Classify 'closed not completely paid' (possible if validated and not yet filed paid)
				if ($object->statut == FactureFournisseur::STATUS_VALIDATED && $object->paye == 0 && $resteapayer > 0) {
					if ($totalpaye > 0 || $totalcreditnotes > 0) {
						// If one payment or one credit note was linked to this invoice
						print '<a class="butAction'.($conf->use_javascript_ajax ? ' reposition' : '').'" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;action=paid">'.$langs->trans('ClassifyPaidPartially').'</a>';
					} else {
						if (empty($conf->global->INVOICE_CAN_NEVER_BE_CANCELED)) {
							print '<a class="butAction'.($conf->use_javascript_ajax ? ' reposition' : '').'" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;action=canceled">'.$langs->trans('ClassifyCanceled').'</a>';
						}
					}
				}

				// Create event
				/*if ($conf->agenda->enabled && ! empty($conf->global->MAIN_ADD_EVENT_ON_ELEMENT_CARD)) 	// Add hidden condition because this is not a "workflow" action so should appears somewhere else on page.
				{
					print '<div class="inline-block divButAction"><a class="butAction" href="' . DOL_URL_ROOT . '/comm/action/card.php?action=create&amp;origin=' . $object->element . '&amp;originid=' . $object->id . '&amp;socid=' . $object->socid . '">' . $langs->trans("AddAction") . '</a></div>';
				}*/

				// Create a credit note
				if (($object->type == FactureFournisseur::TYPE_STANDARD || $object->type == FactureFournisseur::TYPE_DEPOSIT) && $object->statut > 0 && $usercancreate) {
					if (!$objectidnext) {
						print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?socid='.$object->socid.'&amp;fac_avoir='.$object->id.'&amp;action=create&amp;type=2'.($object->fk_project > 0 ? '&amp;projectid='.$object->fk_project : '').'">'.$langs->trans("CreateCreditNote").'</a>';
					}
				}

				// Clone
				if ($action != 'edit' && $usercancreate) {
					print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=clone&amp;socid='.$object->socid.'">'.$langs->trans('ToClone').'</a>';
				}

				// Delete
				$isErasable = $object->is_erasable();
				if ($action != 'confirm_edit' && ($user->rights->fournisseur->facture->supprimer || ($usercancreate && $isErasable == 1))) {	// isErasable = 1 means draft with temporary ref (draft can always be deleted with no need of permissions)
					//var_dump($isErasable);
					if ($isErasable == -4) {
						print '<a class="butActionRefused classfortooltip" href="#" title="'.$langs->trans("DisabledBecausePayments").'">'.$langs->trans('Delete').'</a>';
					} elseif ($isErasable == -3) {	// Should never happen with supplier invoice
						print '<a class="butActionRefused classfortooltip" href="#" title="'.$langs->trans("DisabledBecauseNotLastSituationInvoice").'">'.$langs->trans('Delete').'</a>';
					} elseif ($isErasable == -2) {	// Should never happen with supplier invoice
						print '<a class="butActionRefused classfortooltip" href="#" title="'.$langs->trans("DisabledBecauseNotLastInvoice").'">'.$langs->trans('Delete').'</a>';
					} elseif ($isErasable == -1) {
						print '<a class="butActionRefused classfortooltip" href="#" title="'.$langs->trans("DisabledBecauseDispatchedInBookkeeping").'">'.$langs->trans('Delete').'</a>';
					} elseif ($isErasable <= 0) {	// Any other cases
						print '<a class="butActionRefused classfortooltip" href="#" title="'.$langs->trans("DisabledBecauseNotErasable").'">'.$langs->trans('Delete').'</a>';
					} else {
						print '<a class="butActionDelete'.($conf->use_javascript_ajax ? ' reposition' : '').'" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=delete&amp;token='.newToken().'">'.$langs->trans('Delete').'</a>';
					}
				}
				print '</div>';

				if ($action != 'confirm_edit') {
					print '<div class="fichecenter"><div class="fichehalfleft">';

					/*
					 * Generated documents
					 */
					$ref = dol_sanitizeFileName($object->ref);
					$subdir = get_exdir($object->id, 2, 0, 0, $object, 'invoice_supplier').$ref;
					$filedir = $conf->fournisseur->facture->dir_output.'/'.$subdir;
					$urlsource = $_SERVER['PHP_SELF'].'?id='.$object->id;
					$genallowed = $usercanread;
					$delallowed = $usercancreate;
					$modelpdf = (!empty($object->model_pdf) ? $object->model_pdf : (empty($conf->global->INVOICE_SUPPLIER_ADDON_PDF) ? '' : $conf->global->INVOICE_SUPPLIER_ADDON_PDF));

					print $formfile->showdocuments('facture_fournisseur', $subdir, $filedir, $urlsource, $genallowed, $delallowed, $modelpdf, 1, 0, 0, 40, 0, '', '', '', $societe->default_lang);
					$somethingshown = $formfile->numoffiles;

					// Show links to link elements
					$linktoelem = $form->showLinkToObjectBlock($object, null, array('invoice_supplier'));
					$somethingshown = $form->showLinkedObjectBlock($object, $linktoelem);

					print '</div><div class="fichehalfright"><div class="ficheaddleft">';
					//print '</td><td valign="top" width="50%">';
					//print '<br>';

					// List of actions on element
					include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
					$formactions = new FormActions($db);
					$somethingshown = $formactions->showactions($object, 'invoice_supplier', $socid, 1, 'listaction'.($genallowed ? 'largetitle' : ''));

					print '</div></div></div>';
					//print '</td></tr></table>';
				}
			}
		}

		// Select mail models is same action as presend
		if (GETPOST('modelselected')) {
			$action = 'presend';
		}

		// Presend form
		$modelmail = 'invoice_supplier_send';
		$defaulttopic = 'SendBillRef';
		$diroutput = $conf->fournisseur->facture->dir_output;
		$autocopy = 'MAIN_MAIL_AUTOCOPY_SUPPLIER_INVOICE_TO';
		$trackid = 'sinv'.$object->id;

		include DOL_DOCUMENT_ROOT.'/core/tpl/card_presend.tpl.php';
	}
}


// End of page
llxFooter();
$db->close();
