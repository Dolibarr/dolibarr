<?php
/* Copyright (C) 2002-2006 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Eric Seigne           <eric.seigne@ryxeo.com>
 * Copyright (C) 2004-2016 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2015 Regis Houssin         <regis.houssin@capnetworks.com>
 * Copyright (C) 2006      Andre Cianfarani      <acianfa@free.fr>
 * Copyright (C) 2010-2015 Juanjo Menent         <jmenent@2byte.es>
 * Copyright (C) 2012-2013 Christophe Battarel   <christophe.battarel@altairis.fr>
 * Copyright (C) 2012-2013 Cédric Salvador       <csalvador@gpcsolutions.fr>
 * Copyright (C) 2012-2014 Raphaël Doursenaud    <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2013      Jean-Francois FERRY   <jfefe@aternatik.fr>
 * Copyright (C) 2013-2014 Florian Henry         <florian.henry@open-concept.pro>
 * Copyright (C) 2013      Cédric Salvador       <csalvador@gpcsolutions.fr>
 * Copyright (C) 2014-2018 Ferran Marcet	 	 <fmarcet@2byte.es>
 * Copyright (C) 2015-2016 Marcos García         <marcosgdf@gmail.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file 	htdocs/compta/facture/card.php
 * \ingroup facture
 * \brief 	Page to create/see an invoice
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture-rec.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/modules/facture/modules_facture.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/discount.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formmargin.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/invoice.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
if (! empty($conf->commande->enabled))
	require_once DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php';
if (! empty($conf->projet->enabled)) {
	require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
	require_once DOL_DOCUMENT_ROOT . '/core/class/html.formprojet.class.php';
}
require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';

if (!empty($conf->variants->enabled)) {
	require_once DOL_DOCUMENT_ROOT.'/variants/class/ProductCombination.class.php';
}
if (! empty($conf->accounting->enabled)) {
	require_once DOL_DOCUMENT_ROOT . '/accountancy/class/accountingjournal.class.php';
}

// Load translation files required by the page
$langs->loadLangs(array('bills','companies','compta','products','banks','main','withdrawals'));
if (! empty($conf->incoterm->enabled)) $langs->load('incoterm');
if (! empty($conf->margin->enabled)) $langs->load('margins');

$projectid = (GETPOST('projectid','int') ? GETPOST('projectid', 'int') : 0);

$id = (GETPOST('id', 'int') ? GETPOST('id', 'int') : GETPOST('facid', 'int')); // For backward compatibility
$ref = GETPOST('ref', 'alpha');
$socid = GETPOST('socid', 'int');
$action = GETPOST('action', 'alpha');
$confirm = GETPOST('confirm', 'alpha');
$cancel = GETPOST('cancel', 'alpha');
$lineid = GETPOST('lineid', 'int');
$userid = GETPOST('userid', 'int');
$search_ref = GETPOST('sf_ref','alpha') ? GETPOST('sf_ref', 'alpha') : GETPOST('search_ref', 'alpha');
$search_societe = GETPOST('search_societe', 'alpha');
$search_montant_ht = GETPOST('search_montant_ht', 'alpha');
$search_montant_ttc = GETPOST('search_montant_ttc', 'alpha');
$origin = GETPOST('origin', 'alpha');
$originid = (GETPOST('originid', 'int') ? GETPOST('originid', 'int') : GETPOST('origin_id', 'int')); // For backward compatibility
$fac_rec=GETPOST('fac_rec','int');

// PDF
$hidedetails = (GETPOST('hidedetails', 'int') ? GETPOST('hidedetails', 'int') : (! empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS) ? 1 : 0));
$hidedesc = (GETPOST('hidedesc', 'int') ? GETPOST('hidedesc', 'int') : (! empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DESC) ? 1 : 0));
$hideref = (GETPOST('hideref', 'int') ? GETPOST('hideref', 'int') : (! empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_REF) ? 1 : 0));

// Nombre de ligne pour choix de produit/service predefinis
$NBLINES = 4;

$usehm = (! empty($conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE) ? $conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE : 0);

$object = new Facture($db);
$extrafields = new ExtraFields($db);

// Load object
if ($id > 0 || ! empty($ref)) {
	$ret = $object->fetch($id, $ref, '', '', $conf->global->INVOICE_USE_SITUATION);
}

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('invoicecard','globalcard'));

$permissionnote = $user->rights->facture->creer; // Used by the include of actions_setnotes.inc.php
$permissiondellink=$user->rights->facture->creer;	// Used by the include of actions_dellink.inc.php
$permissiontoedit = $user->rights->facture->creer; // Used by the include of actions_lineupdonw.inc.php

// Security check
$fieldid = (! empty($ref) ? 'facnumber' : 'rowid');
if ($user->societe_id) $socid = $user->societe_id;
$isdraft = (($object->statut == Facture::STATUS_DRAFT) ? 1 : 0);
$result = restrictedArea($user, 'facture', $id, '', '', 'fk_soc', $fieldid, null, $isdraft);


/*
 * Actions
 */

$parameters = array('socid' => $socid);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
	if ($cancel)
	{
		if (! empty($backtopage))
		{
			header("Location: ".$backtopage);
			exit;
		}
		$action='';
	}

	include DOL_DOCUMENT_ROOT.'/core/actions_setnotes.inc.php'; // Must be include, not include_once

	include DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php';		// Must be include, not include_once

	include DOL_DOCUMENT_ROOT.'/core/actions_lineupdown.inc.php';	// Must be include, not include_once

	// Action clone object
	if ($action == 'confirm_clone' && $confirm == 'yes' && $user->rights->facture->creer) {
	//	if (1 == 0 && empty($_REQUEST["clone_content"]) && empty($_REQUEST["clone_receivers"])) {
	//		$mesgs [] = '<div class="error">' . $langs->trans("NoCloneOptionsSpecified") . '</div>';
	//	} else {
			if ($object->fetch($id) > 0) {
				$result = $object->createFromClone($socid);
				if ($result > 0) {
					header("Location: " . $_SERVER['PHP_SELF'] . '?facid=' . $result);
					exit();
				} else {
					setEventMessages($object->error, $object->errors, 'errors');
					$action = '';
				}
			}
	//	}
	}

	// Change status of invoice
	else if ($action == 'reopen' && $user->rights->facture->creer) {
		$result = $object->fetch($id);
		if ($object->statut == 2 || ($object->statut == 3 && $object->close_code != 'replaced') || ($object->statut == 1 && $object->paye == 1)) {    // ($object->statut == 1 && $object->paye == 1) should not happened but can be found when data are corrupted
			$result = $object->set_unpaid($user);
			if ($result > 0) {
				header('Location: ' . $_SERVER["PHP_SELF"] . '?facid=' . $id);
				exit();
			} else {
				setEventMessages($object->error, $object->errors, 'errors');
			}
		}
	}

	// Delete invoice
	else if ($action == 'confirm_delete' && $confirm == 'yes') {
		$result = $object->fetch($id);
		$object->fetch_thirdparty();

		$idwarehouse = GETPOST('idwarehouse');

		$qualified_for_stock_change = 0;
		if (empty($conf->global->STOCK_SUPPORTS_SERVICES)) {
			$qualified_for_stock_change = $object->hasProductsOrServices(2);
		} else {
			$qualified_for_stock_change = $object->hasProductsOrServices(1);
		}

		$isErasable=$object->is_erasable();

		if (($user->rights->facture->supprimer && $isErasable > 0)
			|| ($user->rights->facture->creer && $isErasable == 1))
		{
			$result = $object->delete($user, 0, $idwarehouse);
			if ($result > 0) {
				header('Location: ' . DOL_URL_ROOT . '/compta/facture/list.php?restore_lastsearch_values=1');
				exit();
			} else {
				setEventMessages($object->error, $object->errors, 'errors');
				$action='';
			}
		}
	}

	// Delete line
	else if ($action == 'confirm_deleteline' && $confirm == 'yes' && $user->rights->facture->creer)
	{
		$object->fetch($id);
		$object->fetch_thirdparty();

		$result = $object->deleteline(GETPOST('lineid'));
		if ($result > 0) {
			// Define output language
			$outputlangs = $langs;
			$newlang = '';
			if ($conf->global->MAIN_MULTILANGS && empty($newlang) && ! empty($_REQUEST['lang_id']))
				$newlang = $_REQUEST['lang_id'];
			if ($conf->global->MAIN_MULTILANGS && empty($newlang))
				$newlang = $object->thirdparty->default_lang;
			if (! empty($newlang)) {
				$outputlangs = new Translate("", $conf);
				$outputlangs->setDefaultLang($newlang);
				$outputlangs->load('products');
			}
			if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
				$ret = $object->fetch($id); // Reload to get new records
				$result = $object->generateDocument($object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
			}
			if ($result >= 0) {
				header('Location: ' . $_SERVER["PHP_SELF"] . '?facid=' . $id);
				exit();
			}
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
			$action = '';
		}
	}

	// Delete link of credit note to invoice
	else if ($action == 'unlinkdiscount' && $user->rights->facture->creer)
	{
		$discount = new DiscountAbsolute($db);
		$result = $discount->fetch(GETPOST("discountid"));
		$discount->unlink_invoice();
	}

	// Validation
	else if ($action == 'valid' && $user->rights->facture->creer)
	{
		$object->fetch($id);

		// On verifie signe facture
		if ($object->type == Facture::TYPE_CREDIT_NOTE) {
			// Si avoir, le signe doit etre negatif
			if ($object->total_ht >= 0) {
				setEventMessages($langs->trans("ErrorInvoiceAvoirMustBeNegative"), null, 'errors');
				$action = '';
			}
		} else {
			// Si non avoir, le signe doit etre positif
			if (empty($conf->global->FACTURE_ENABLE_NEGATIVE) && $object->total_ht < 0) {
				setEventMessages($langs->trans("ErrorInvoiceOfThisTypeMustBePositive"), null, 'errors');
				$action = '';
			}
		}
	}

	else if ($action == 'set_thirdparty' && $user->rights->facture->creer)
	{
		$object->fetch($id);
		$object->setValueFrom('fk_soc', $socid, '', null, 'int', '', $user, 'BILL_MODIFY');

		header('Location: ' . $_SERVER["PHP_SELF"] . '?facid=' . $id);
		exit();
	}

	else if ($action == 'classin' && $user->rights->facture->creer)
	{
		$object->fetch($id);
		$object->setProject($_POST['projectid']);
	}

	else if ($action == 'setmode' && $user->rights->facture->creer)
	{
		$object->fetch($id);
		$result = $object->setPaymentMethods(GETPOST('mode_reglement_id', 'int'));
		if ($result < 0)
			dol_print_error($db, $object->error);
	}

	// Multicurrency Code
	else if ($action == 'setmulticurrencycode' && $user->rights->facture->creer) {
		$result = $object->setMulticurrencyCode(GETPOST('multicurrency_code', 'alpha'));
	}

	// Multicurrency rate
	else if ($action == 'setmulticurrencyrate' && $user->rights->facture->creer) {
		$result = $object->setMulticurrencyRate(price2num(GETPOST('multicurrency_tx')), GETPOST('calculation_mode', 'int'));
	}

	else if ($action == 'setinvoicedate' && $user->rights->facture->creer)
	{
		$object->fetch($id);
		$old_date_lim_reglement = $object->date_lim_reglement;
		$date = dol_mktime(12, 0, 0, $_POST['invoicedatemonth'], $_POST['invoicedateday'], $_POST['invoicedateyear']);
		if (empty($date))
		{
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Date")), null, 'errors');
			header('Location: '.$_SERVER["PHP_SELF"].'?facid='.$id.'&action=editinvoicedate');
			exit;
		}
		$object->date=$date;
		$new_date_lim_reglement = $object->calculate_date_lim_reglement();
		if ($new_date_lim_reglement > $old_date_lim_reglement) $object->date_lim_reglement = $new_date_lim_reglement;
		if ($object->date_lim_reglement < $object->date) $object->date_lim_reglement = $object->date;
		$result = $object->update($user);
		if ($result < 0) dol_print_error($db, $object->error);
	}

	else if ($action == 'setdate_pointoftax' && $user->rights->facture->creer)
	{
		$object->fetch($id);
		$date_pointoftax = dol_mktime(12, 0, 0, $_POST['date_pointoftaxmonth'], $_POST['date_pointoftaxday'], $_POST['date_pointoftaxyear']);
		$object->date_pointoftax=$date_pointoftax;
		$result = $object->update($user);
		if ($result < 0) dol_print_error($db, $object->error);
	}

	else if ($action == 'setconditions' && $user->rights->facture->creer)
	{
		$object->fetch($id);
		$object->cond_reglement_code = 0; // To clean property
		$object->cond_reglement_id = 0; // To clean property
		$result = $object->setPaymentTerms(GETPOST('cond_reglement_id', 'int'));
		if ($result < 0) dol_print_error($db, $object->error);

		$old_date_lim_reglement = $object->date_lim_reglement;
		$new_date_lim_reglement = $object->calculate_date_lim_reglement();
		if ($new_date_lim_reglement > $old_date_lim_reglement) $object->date_lim_reglement = $new_date_lim_reglement;
		if ($object->date_lim_reglement < $object->date) $object->date_lim_reglement = $object->date;
		$result = $object->update($user);
		if ($result < 0) dol_print_error($db, $object->error);
	}

	else if ($action == 'setpaymentterm' && $user->rights->facture->creer)
	{
		$object->fetch($id);
		$object->date_lim_reglement = dol_mktime(12, 0, 0, $_POST['paymenttermmonth'], $_POST['paymenttermday'], $_POST['paymenttermyear']);
		if ($object->date_lim_reglement < $object->date) {
			$object->date_lim_reglement = $object->calculate_date_lim_reglement();
			setEventMessages($langs->trans("DatePaymentTermCantBeLowerThanObjectDate"), null, 'warnings');
		}
		$result = $object->update($user);
		if ($result < 0)
			dol_print_error($db, $object->error);
	}

	else if ($action == 'setrevenuestamp' && $user->rights->facture->creer)
	{
		$object->fetch($id);
		$object->revenuestamp = GETPOST('revenuestamp');
		$result = $object->update($user);
		$object->update_price(1);
		if ($result < 0)
			dol_print_error($db, $object->error);
	}

	// Set incoterm
	elseif ($action == 'set_incoterms' && !empty($conf->incoterm->enabled))
	{
		$result = $object->setIncoterms(GETPOST('incoterm_id', 'int'), GETPOST('location_incoterms', 'alpha'));
	}

	// bank account
	else if ($action == 'setbankaccount' && $user->rights->facture->creer)
	{
		$result=$object->setBankAccount(GETPOST('fk_account', 'int'));
	}

	else if ($action == 'setremisepercent' && $user->rights->facture->creer)
	{
		$object->fetch($id);
		$result = $object->set_remise($user, $_POST['remise_percent']);
	}

	else if ($action == "setabsolutediscount" && $user->rights->facture->creer)
	{
		// POST[remise_id] or POST[remise_id_for_payment]

		// We use the credit to reduce amount of invoice
		if (! empty($_POST["remise_id"])) {
			$ret = $object->fetch($id);
			if ($ret > 0) {
				$result = $object->insert_discount($_POST["remise_id"]);
				if ($result < 0) {
					setEventMessages($object->error, $object->errors, 'errors');
				}
			} else {
				dol_print_error($db, $object->error);
			}
		}
		// We use the credit to reduce remain to pay
		if (! empty($_POST["remise_id_for_payment"]))
		{
			require_once DOL_DOCUMENT_ROOT . '/core/class/discount.class.php';
			$discount = new DiscountAbsolute($db);
			$discount->fetch($_POST["remise_id_for_payment"]);

			//var_dump($object->getRemainToPay(0));
			//var_dump($discount->amount_ttc);exit;
			if (price2num($discount->amount_ttc) > price2num($object->getRemainToPay(0)))
			{
				// TODO Split the discount in 2 automatically
				$error++;
				setEventMessages($langs->trans("ErrorDiscountLargerThanRemainToPaySplitItBefore"), null, 'errors');
			}

			if (! $error)
			{
				$result = $discount->link_to_invoice(0, $id);
				if ($result < 0) {
					setEventMessages($discount->error, $discount->errors, 'errors');
				}
			}
		}

		if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE))
		{
			$outputlangs = $langs;
			$newlang = '';
			if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id','aZ09')) $newlang = GETPOST('lang_id','aZ09');
			if ($conf->global->MAIN_MULTILANGS && empty($newlang))	$newlang = $object->thirdparty->default_lang;
			if (! empty($newlang)) {
				$outputlangs = new Translate("", $conf);
				$outputlangs->setDefaultLang($newlang);
			}
			$ret = $object->fetch($id); // Reload to get new records

			$result = $object->generateDocument($object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
			if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	else if ($action == 'setref_client' && $user->rights->facture->creer)
	{
		$object->fetch($id);
		$object->set_ref_client(GETPOST('ref_client'));
	}

	// Classify to validated
	else if ($action == 'confirm_valid' && $confirm == 'yes' &&
		((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->facture->creer))
	   	|| (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->facture->invoice_advance->validate)))
	)
	{
		$idwarehouse = GETPOST('idwarehouse','int');

		$object->fetch($id);
		$object->fetch_thirdparty();

		// Check parameters

		// Check for mandatory fields defined into setup
		$array_to_check=array('IDPROF1','IDPROF2','IDPROF3','IDPROF4','IDPROF5','IDPROF6','EMAIL');
		foreach($array_to_check as $key)
		{
			$keymin=strtolower($key);
			$i=(int) preg_replace('/[^0-9]/','',$key);
			$vallabel=$object->thirdparty->$keymin;

			if ($i > 0)
			{
				if ($object->thirdparty->isACompany())
				{
					// Check for mandatory prof id (but only if country is other than ours)
					if ($mysoc->country_id > 0 && $object->thirdparty->country_id == $mysoc->country_id)
					{
						$idprof_mandatory ='SOCIETE_'.$key.'_INVOICE_MANDATORY';
						if (! $vallabel && ! empty($conf->global->$idprof_mandatory))
						{
							$langs->load("errors");
							$error++;
							setEventMessages($langs->trans('ErrorProdIdIsMandatory', $langs->transcountry('ProfId'.$i, $object->thirdparty->country_code)).' ('.$langs->trans("ForbiddenBySetupRules").')', null, 'errors');
						}
					}
				}
			}
			else
			{
				//var_dump($conf->global->SOCIETE_EMAIL_MANDATORY);
				if ($key == 'EMAIL')
				{
					// Check for mandatory
					if (! empty($conf->global->SOCIETE_EMAIL_INVOICE_MANDATORY) && ! isValidEMail($object->thirdparty->email))
					{
						$langs->load("errors");
						$error++;
						setEventMessages($langs->trans("ErrorBadEMail", $object->thirdparty->email).' ('.$langs->trans("ForbiddenBySetupRules").')', null, 'errors');
					}
				}
			}
		}

		$qualified_for_stock_change = 0;
		if (empty($conf->global->STOCK_SUPPORTS_SERVICES)) {
			$qualified_for_stock_change = $object->hasProductsOrServices(2);
		} else {
			$qualified_for_stock_change = $object->hasProductsOrServices(1);
		}

		// Check for warehouse
		if ($object->type != Facture::TYPE_DEPOSIT && ! empty($conf->global->STOCK_CALCULATE_ON_BILL) && $qualified_for_stock_change)
		{
			if (! $idwarehouse || $idwarehouse == - 1) {
				$error++;
				setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("Warehouse")), null, 'errors');
				$action = '';
			}
		}

		if (! $error)
		{
			$result = $object->validate($user, '', $idwarehouse);
			if ($result >= 0)
			{
				// Define output language
				if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE))
				{
					$outputlangs = $langs;
					$newlang = '';
					if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id','aZ09')) $newlang = GETPOST('lang_id','aZ09');
					if ($conf->global->MAIN_MULTILANGS && empty($newlang))	$newlang = $object->thirdparty->default_lang;
					if (! empty($newlang)) {
						$outputlangs = new Translate("", $conf);
						$outputlangs->setDefaultLang($newlang);
						$outputlangs->load('products');
					}
					$model=$object->modelpdf;
					$ret = $object->fetch($id); // Reload to get new records

					$result = $object->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
					if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
				}
			}
			else
			{
				if (count($object->errors)) setEventMessages(null, $object->errors, 'errors');
				else setEventMessages($object->error, $object->errors, 'errors');
			}
		}
	}

	// Go back to draft status (unvalidate)
	else if ($action == 'confirm_modif' &&
		((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->facture->creer))
	   	|| (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->facture->invoice_advance->unvalidate)))
	)
	{
		$idwarehouse = GETPOST('idwarehouse','int');

		$object->fetch($id);
		$object->fetch_thirdparty();

		$qualified_for_stock_change = 0;
		if (empty($conf->global->STOCK_SUPPORTS_SERVICES)) {
			$qualified_for_stock_change = $object->hasProductsOrServices(2);
		} else {
			$qualified_for_stock_change = $object->hasProductsOrServices(1);
		}

		// Check parameters
		if ($object->type != Facture::TYPE_DEPOSIT && ! empty($conf->global->STOCK_CALCULATE_ON_BILL) && $qualified_for_stock_change)
		{
			if (! $idwarehouse || $idwarehouse == - 1) {
				$error++;
				setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("Warehouse")), null, 'errors');
				$action = '';
			}
		}

		if (! $error) {
			// On verifie si la facture a des paiements
			$sql = 'SELECT pf.amount';
			$sql .= ' FROM ' . MAIN_DB_PREFIX . 'paiement_facture as pf';
			$sql .= ' WHERE pf.fk_facture = ' . $object->id;

			$result = $db->query($sql);
			if ($result) {
				$i = 0;
				$num = $db->num_rows($result);

				while ($i < $num) {
					$objp = $db->fetch_object($result);
					$totalpaye += $objp->amount;
					$i ++;
				}
			} else {
				dol_print_error($db, '');
			}

			$resteapayer = $object->total_ttc - $totalpaye;

			// On verifie si les lignes de factures ont ete exportees en compta et/ou ventilees
			$ventilExportCompta = $object->getVentilExportCompta();

			// On verifie si aucun paiement n'a ete effectue
			if ($ventilExportCompta == 0)
			{
				if (! empty($conf->global->INVOICE_CAN_ALWAYS_BE_EDITED) || ($resteapayer == $object->total_ttc && empty($object->paye)))
				{
					$result=$object->set_draft($user, $idwarehouse);
					if ($result<0) setEventMessages($object->error, $object->errors, 'errors');

					// Define output language
					if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE))
					{
						$outputlangs = $langs;
						$newlang = '';
						if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id','aZ09')) $newlang = GETPOST('lang_id','aZ09');
						if ($conf->global->MAIN_MULTILANGS && empty($newlang))	$newlang = $object->thirdparty->default_lang;
						if (! empty($newlang)) {
							$outputlangs = new Translate("", $conf);
							$outputlangs->setDefaultLang($newlang);
							$outputlangs->load('products');
						}
						$model=$object->modelpdf;
						$ret = $object->fetch($id); // Reload to get new records

						$object->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
					}
				}
			}
		}
	}

	// Classify "paid"
	else if ($action == 'confirm_paid' && $confirm == 'yes' && $user->rights->facture->paiement)
	{
		$object->fetch($id);
		$result = $object->set_paid($user);
		if ($result<0) setEventMessages($object->error, $object->errors, 'errors');
	} // Classif "paid partialy"
	else if ($action == 'confirm_paid_partially' && $confirm == 'yes' && $user->rights->facture->paiement)
	{
		$object->fetch($id);
		$close_code = GETPOST("close_code",'none');
		$close_note = GETPOST("close_note",'none');
		if ($close_code) {
			$result = $object->set_paid($user, $close_code, $close_note);
			if ($result<0) setEventMessages($object->error, $object->errors, 'errors');
		} else {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Reason")), null, 'errors');
		}
	} // Classify "abandoned"
	else if ($action == 'confirm_canceled' && $confirm == 'yes') {
		$object->fetch($id);
		$close_code = GETPOST("close_code",'none');
		$close_note = GETPOST("close_note",'none');
		if ($close_code) {
			$result = $object->set_canceled($user, $close_code, $close_note);
			if ($result<0) setEventMessages($object->error, $object->errors, 'errors');
		} else {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Reason")), null, 'errors');
		}
	}

	// Convertir en reduc
	else if ($action == 'confirm_converttoreduc' && $confirm == 'yes' && $user->rights->facture->creer)
	{
		$object->fetch($id);
		$object->fetch_thirdparty();
		//$object->fetch_lines();	// Already done into fetch

		// Check if there is already a discount (protection to avoid duplicate creation when resubmit post)
		$discountcheck=new DiscountAbsolute($db);
		$result=$discountcheck->fetch(0,$object->id);

		$canconvert=0;
		if ($object->type == Facture::TYPE_DEPOSIT && empty($discountcheck->id)) $canconvert=1;	// we can convert deposit into discount if deposit is payed (completely, partially or not at all) and not already converted (see real condition into condition used to show button converttoreduc)
		if (($object->type == Facture::TYPE_CREDIT_NOTE || $object->type == Facture::TYPE_STANDARD) && $object->paye == 0 && empty($discountcheck->id)) $canconvert=1;	// we can convert credit note into discount if credit note is not payed back and not already converted and amount of payment is 0 (see real condition into condition used to show button converttoreduc)
		if ($canconvert)
		{
			$db->begin();

			$amount_ht = $amount_tva = $amount_ttc = array();

			// Loop on each vat rate
			$i = 0;
			foreach ($object->lines as $line)
			{
				if ($line->product_type < 9 && $line->total_ht != 0) // Remove lines with product_type greater than or equal to 9
				{ 	// no need to create discount if amount is null
					$amount_ht[$line->tva_tx] += $line->total_ht;
					$amount_tva[$line->tva_tx] += $line->total_tva;
					$amount_ttc[$line->tva_tx] += $line->total_ttc;
					$multicurrency_amount_ht[$line->tva_tx] += $line->multicurrency_total_ht;
					$multicurrency_amount_tva[$line->tva_tx] += $line->multicurrency_total_tva;
					$multicurrency_amount_ttc[$line->tva_tx] += $line->multicurrency_total_ttc;
					$i ++;
				}
			}

			// Insert one discount by VAT rate category
			$discount = new DiscountAbsolute($db);
			if ($object->type == Facture::TYPE_CREDIT_NOTE)
				$discount->description = '(CREDIT_NOTE)';
			elseif ($object->type == Facture::TYPE_DEPOSIT)
				$discount->description = '(DEPOSIT)';
			elseif ($object->type == Facture::TYPE_STANDARD || $object->type == Facture::TYPE_REPLACEMENT || $object->type == Facture::TYPE_SITUATION)
				$discount->description = '(EXCESS RECEIVED)';
			else {
				setEventMessages($langs->trans('CantConvertToReducAnInvoiceOfThisType'), null, 'errors');
			}
			$discount->fk_soc = $object->socid;
			$discount->fk_facture_source = $object->id;

			$error = 0;

			if ($object->type == Facture::TYPE_STANDARD || $object->type == Facture::TYPE_REPLACEMENT || $object->type == Facture::TYPE_SITUATION)
			{
				// If we're on a standard invoice, we have to get excess received to create a discount in TTC without VAT

				$sql = 'SELECT SUM(pf.amount) as total_paiements';
				$sql.= ' FROM '.MAIN_DB_PREFIX.'paiement_facture as pf, '.MAIN_DB_PREFIX.'paiement as p';
				$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_paiement as c ON p.fk_paiement = c.id';
				$sql.= ' WHERE pf.fk_facture = '.$object->id;
				$sql.= ' AND pf.fk_paiement = p.rowid';
				$sql.= ' AND p.entity IN (' . getEntity('facture').')';

				$resql = $db->query($sql);
				if (! $resql) dol_print_error($db);

				$res = $db->fetch_object($resql);
				$total_paiements = $res->total_paiements;

				$discount->amount_ht = $discount->amount_ttc = $total_paiements - $object->total_ttc;
				$discount->amount_tva = 0;
				$discount->tva_tx = 0;

				$result = $discount->create($user);
				if ($result < 0)
				{
					$error++;
				}

			}
			if ($object->type == Facture::TYPE_CREDIT_NOTE || $object->type == Facture::TYPE_DEPOSIT)
			{
				foreach ($amount_ht as $tva_tx => $xxx)
				{
					$discount->amount_ht = abs($amount_ht[$tva_tx]);
					$discount->amount_tva = abs($amount_tva[$tva_tx]);
					$discount->amount_ttc = abs($amount_ttc[$tva_tx]);
					$discount->multicurrency_amount_ht = abs($multicurrency_amount_ht[$tva_tx]);
					$discount->multicurrency_amount_tva = abs($multicurrency_amount_tva[$tva_tx]);
					$discount->multicurrency_amount_ttc = abs($multicurrency_amount_ttc[$tva_tx]);
					$discount->tva_tx = abs($tva_tx);

					$result = $discount->create($user);
					if ($result < 0)
					{
						$error++;
						break;
					}
				}

			}

			if (empty($error))
			{
				if($object->type != Facture::TYPE_DEPOSIT) {
					// Classe facture
					$result = $object->set_paid($user);
					if ($result >= 0)
					{
						$db->commit();
					}
					else
					{
						setEventMessages($object->error, $object->errors, 'errors');
						$db->rollback();
					}
				} else {
					$db->commit();
				}
			}
			else
			{
				setEventMessages($discount->error, $discount->errors, 'errors');
				$db->rollback();
			}
		}
	}

	// Delete payment
	elseif ($action == 'confirm_delete_paiement' && $confirm == 'yes' && $user->rights->facture->creer)
	{
		$object->fetch($id);
		if ($object->statut == Facture::STATUS_VALIDATED && $object->paye == 0)
		{
			$paiement = new Paiement($db);
			$result=$paiement->fetch(GETPOST('paiement_id'));
			if ($result > 0) {
				$result=$paiement->delete(); // If fetch ok and found
				header("Location: ".$_SERVER['PHP_SELF']."?id=".$id);
			}
			if ($result < 0) {
				setEventMessages($paiement->error, $paiement->errors, 'errors');
			}
		}
	}

	/*
	 * Insert new invoice in database
	 */
	else if ($action == 'add' && $user->rights->facture->creer)
	{
		if ($socid > 0) $object->socid = GETPOST('socid', 'int');

		$db->begin();

		$error = 0;

		// Fill array 'array_options' with data from add form
		$extralabels = $extrafields->fetch_name_optionals_label($object->table_element);
		$ret = $extrafields->setOptionalsFromPost($extralabels, $object);
		if ($ret < 0) $error++;

		// Replacement invoice
		if ($_POST['type'] == Facture::TYPE_REPLACEMENT)
		{
			$dateinvoice = dol_mktime(12, 0, 0, $_POST['remonth'], $_POST['reday'], $_POST['reyear']);
			if (empty($dateinvoice))
			{
				$error++;
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Date")), null, 'errors');
			}

			if (! ($_POST['fac_replacement'] > 0)) {
				$error++;
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("ReplaceInvoice")), null, 'errors');
			}

			$date_pointoftax = dol_mktime(12, 0, 0, $_POST['date_pointoftaxmonth'], $_POST['date_pointoftaxday'], $_POST['date_pointoftaxyear']);

			if (! $error) {
				// This is a replacement invoice
				$result = $object->fetch($_POST['fac_replacement']);
				$object->fetch_thirdparty();

				$object->date				= $dateinvoice;
				$object->date_pointoftax	= $date_pointoftax;
				$object->note_public		= trim(GETPOST('note_public','none'));
				// We do not copy the private note
				$object->ref_client			= $_POST['ref_client'];
				$object->ref_int			= $_POST['ref_int'];
				$object->modelpdf			= $_POST['model'];
				$object->fk_project			= $_POST['projectid'];
				$object->cond_reglement_id	= $_POST['cond_reglement_id'];
				$object->mode_reglement_id	= $_POST['mode_reglement_id'];
				$object->fk_account         = GETPOST('fk_account', 'int');
				$object->remise_absolue		= $_POST['remise_absolue'];
				$object->remise_percent		= $_POST['remise_percent'];
				$object->fk_incoterms 		= GETPOST('incoterm_id', 'int');
				$object->location_incoterms = GETPOST('location_incoterms', 'alpha');
				$object->multicurrency_code = GETPOST('multicurrency_code', 'alpha');
				$object->multicurrency_tx   = GETPOST('originmulticurrency_tx', 'int');

				// Proprietes particulieres a facture de remplacement
				$object->fk_facture_source = $_POST['fac_replacement'];
				$object->type = Facture::TYPE_REPLACEMENT;

				$id = $object->createFromCurrent($user);
				if ($id <= 0) {
					setEventMessages($object->error, $object->errors, 'errors');
				}
			}
		}

		// Credit note invoice
		if ($_POST['type'] == Facture::TYPE_CREDIT_NOTE)
		{
			$sourceinvoice = GETPOST('fac_avoir');
			if (! ($sourceinvoice > 0) && empty($conf->global->INVOICE_CREDIT_NOTE_STANDALONE))
			{
				$error++;
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("CorrectInvoice")), null, 'errors');
			}

			$dateinvoice = dol_mktime(12, 0, 0, $_POST['remonth'], $_POST['reday'], $_POST['reyear']);
			if (empty($dateinvoice))
			{
				$error++;
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->trans("Date")), null, 'errors');
			}

			$date_pointoftax = dol_mktime(12, 0, 0, $_POST['date_pointoftaxmonth'], $_POST['date_pointoftaxday'], $_POST['date_pointoftaxyear']);

			if (! $error)
			{
				$object->socid				= GETPOST('socid','int');
				$object->number				= $_POST['facnumber'];
				$object->date				= $dateinvoice;
				$object->date_pointoftax	= $date_pointoftax;
				$object->note_public		= trim(GETPOST('note_public','none'));
				// We do not copy the private note
				$object->ref_client			= $_POST['ref_client'];
				$object->ref_int			= $_POST['ref_int'];
				$object->modelpdf			= $_POST['model'];
				$object->fk_project			= $_POST['projectid'];
				$object->cond_reglement_id	= 0;
				$object->mode_reglement_id	= $_POST['mode_reglement_id'];
				$object->fk_account         = GETPOST('fk_account', 'int');
				$object->remise_absolue		= $_POST['remise_absolue'];
				$object->remise_percent		= $_POST['remise_percent'];
				$object->fk_incoterms 		= GETPOST('incoterm_id', 'int');
				$object->location_incoterms = GETPOST('location_incoterms', 'alpha');
				$object->multicurrency_code = GETPOST('multicurrency_code', 'alpha');
				$object->multicurrency_tx   = GETPOST('originmulticurrency_tx', 'int');

				// Proprietes particulieres a facture avoir
				$object->fk_facture_source = $sourceinvoice > 0 ? $sourceinvoice : '';
				$object->type = Facture::TYPE_CREDIT_NOTE;

				$facture_source = new Facture($db); // fetch origin object
				if ($facture_source->fetch($object->fk_facture_source)>0)
				{
				    if ($facture_source->type == Facture::TYPE_SITUATION)
				    {
				        $object->situation_counter =  $facture_source->situation_counter;
				        $object->situation_cycle_ref = $facture_source->situation_cycle_ref;
				        $facture_source->fetchPreviousNextSituationInvoice();
				    }
				}
				$id = $object->create($user);

				if (GETPOST('invoiceAvoirWithLines', 'int')==1 && $id>0)
				{
					if (!empty($facture_source->lines))
					{
						$fk_parent_line = 0;

						foreach($facture_source->lines as $line)
						{
							// Extrafields
							if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED) && method_exists($line, 'fetch_optionals')) {
								// load extrafields
								$line->fetch_optionals();
							}

							// Reset fk_parent_line for no child products and special product
							if (($line->product_type != 9 && empty($line->fk_parent_line)) || $line->product_type == 9) {
								$fk_parent_line = 0;
							}




							if($facture_source->type == Facture::TYPE_SITUATION)
							{
							    $source_fk_prev_id = $line->fk_prev_id; // temporary storing situation invoice fk_prev_id
							    $line->fk_prev_id  = $line->id; // Credit note line need to be linked to the situation invoice it is create from

							    if(!empty($facture_source->tab_previous_situation_invoice))
							    {
							        // search the last invoice in cycle
							        $lineIndex = count($facture_source->tab_previous_situation_invoice) - 1;
							        $searchPreviousInvoice = true;
							        while( $searchPreviousInvoice )
							        {
							            if($facture_source->tab_previous_situation_invoice[$lineIndex]->type  == Facture::TYPE_SITUATION || $lineIndex < 1)
							            {
							                $searchPreviousInvoice=false; // find, exit;
							                break;
							            }
							            else
							            {
							                $lineIndex--; // go to previous invoice in cycle
							            }
							        }


							        $maxPrevSituationPercent = 0;
							        foreach($facture_source->tab_previous_situation_invoice[$lineIndex]->lines as $prevLine)
							        {
							            if($prevLine->id == $source_fk_prev_id)
							            {
							                $maxPrevSituationPercent = max($maxPrevSituationPercent,$prevLine->situation_percent);

							                //$line->subprice  = $line->subprice - $prevLine->subprice;
							                $line->total_ht  = $line->total_ht - $prevLine->total_ht;
							                $line->total_tva = $line->total_tva - $prevLine->total_tva;
							                $line->total_ttc = $line->total_ttc - $prevLine->total_ttc;
							                $line->total_localtax1 = $line->total_localtax1 - $prevLine->total_localtax1;
							                $line->total_localtax2 = $line->total_localtax2 - $prevLine->total_localtax2;

							                $line->multicurrency_subprice  = $line->multicurrency_subprice  - $prevLine->multicurrency_subprice;
							                $line->multicurrency_total_ht  = $line->multicurrency_total_ht  - $prevLine->multicurrency_total_ht;
							                $line->multicurrency_total_tva = $line->multicurrency_total_tva - $prevLine->multicurrency_total_tva;
							                $line->multicurrency_total_ttc = $line->multicurrency_total_ttc - $prevLine->multicurrency_total_ttc;


							            }
							        }

							        // prorata
							        $line->situation_percent = $maxPrevSituationPercent - $line->situation_percent;


							    }
							}

							$line->fk_facture = $object->id;
							$line->fk_parent_line = $fk_parent_line;

							$line->subprice = -$line->subprice; // invert price for object
							$line->pa_ht = $line->pa_ht;       // we choosed to have buy/cost price always positive, so no revert of sign here
							$line->total_ht = -$line->total_ht;
							$line->total_tva = -$line->total_tva;
							$line->total_ttc = -$line->total_ttc;
							$line->total_localtax1 = -$line->total_localtax1;
							$line->total_localtax2 = -$line->total_localtax2;

							$line->multicurrency_subprice = -$line->multicurrency_subprice;
							$line->multicurrency_total_ht = -$line->multicurrency_total_ht;
							$line->multicurrency_total_tva = -$line->multicurrency_total_tva;
							$line->multicurrency_total_ttc = -$line->multicurrency_total_ttc;

							$result = $line->insert(0, 1);     // When creating credit note with same lines than source, we must ignore error if discount alreayd linked

							$object->lines[] = $line; // insert new line in current object

							// Defined the new fk_parent_line
							if ($result > 0 && $line->product_type == 9) {
								$fk_parent_line = $result;
							}
						}

						$object->update_price(1);
					}

				}

				if(GETPOST('invoiceAvoirWithPaymentRestAmount', 'int')==1 && $id>0)
				{
					if ($facture_source->fetch($object->fk_facture_source)>0)
					{
						$totalpaye = $facture_source->getSommePaiement();
						$totalcreditnotes = $facture_source->getSumCreditNotesUsed();
						$totaldeposits = $facture_source->getSumDepositsUsed();
						$remain_to_pay = abs($facture_source->total_ttc - $totalpaye - $totalcreditnotes - $totaldeposits);

						$object->addline($langs->trans('invoiceAvoirLineWithPaymentRestAmount'),$remain_to_pay,1,0,0,0,0,0,'','','TTC');
					}
				}

				// Add link between credit note and origin
				if(! empty($object->fk_facture_source)) {
					$facture_source->fetch($object->fk_facture_source);
					$facture_source->fetchObjectLinked();

					if(! empty($facture_source->linkedObjectsIds)) {
						foreach($facture_source->linkedObjectsIds as $sourcetype => $TIds) {
							$object->add_object_linked($sourcetype, current($TIds));
						}
					}
				}
			}
		}

		// Standard invoice or Deposit invoice, created from a Predefined template invoice
		if (($_POST['type'] == Facture::TYPE_STANDARD || $_POST['type'] == Facture::TYPE_DEPOSIT) && GETPOST('fac_rec') > 0)
		{
			$dateinvoice = dol_mktime(12, 0, 0, $_POST['remonth'], $_POST['reday'], $_POST['reyear']);
			if (empty($dateinvoice))
			{
				$error++;
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Date")), null, 'errors');
			}

			$date_pointoftax = dol_mktime(12, 0, 0, $_POST['date_pointoftaxmonth'], $_POST['date_pointoftaxday'], $_POST['date_pointoftaxyear']);

			if (! $error)
			{
				$object->socid			 = GETPOST('socid','int');
				$object->type            = $_POST['type'];
				$object->number          = $_POST['facnumber'];
				$object->date            = $dateinvoice;
				$object->date_pointoftax = $date_pointoftax;
				$object->note_public	 = trim(GETPOST('note_public','none'));
				$object->note_private    = trim(GETPOST('note_private','none'));
				$object->ref_client      = $_POST['ref_client'];
				$object->ref_int     	 = $_POST['ref_int'];
				$object->modelpdf        = $_POST['model'];
				$object->fk_project		 = $_POST['projectid'];
				$object->cond_reglement_id	= ($_POST['type'] == 3?1:$_POST['cond_reglement_id']);
				$object->mode_reglement_id	= $_POST['mode_reglement_id'];
				$object->fk_account         = GETPOST('fk_account', 'int');
				$object->amount				= $_POST['amount'];
				$object->remise_absolue		= $_POST['remise_absolue'];
				$object->remise_percent		= $_POST['remise_percent'];
				$object->fk_incoterms 		= GETPOST('incoterm_id', 'int');
				$object->location_incoterms = GETPOST('location_incoterms', 'alpha');
				$object->multicurrency_code = GETPOST('multicurrency_code', 'alpha');
				$object->multicurrency_tx   = GETPOST('originmulticurrency_tx', 'int');

				// Source facture
				$object->fac_rec = GETPOST('fac_rec', 'int');

				$id = $object->create($user);       // This include recopy of links from recurring invoice and invoice lines
			}
		}

		// Standard or deposit or proforma invoice, not from a Predefined template invoice
		if (($_POST['type'] == Facture::TYPE_STANDARD || $_POST['type'] == Facture::TYPE_DEPOSIT || $_POST['type'] == Facture::TYPE_PROFORMA || ($_POST['type'] == Facture::TYPE_SITUATION && empty($_POST['situations']))) && GETPOST('fac_rec') <= 0)
		{
			if (GETPOST('socid', 'int') < 1)
			{
				$error++;
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Customer")), null, 'errors');
			}

			$dateinvoice = dol_mktime(12, 0, 0, $_POST['remonth'], $_POST['reday'], $_POST['reyear']);
			if (empty($dateinvoice))
			{
				$error++;
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Date")), null, 'errors');
			}

			$date_pointoftax = dol_mktime(12, 0, 0, $_POST['date_pointoftaxmonth'], $_POST['date_pointoftaxday'], $_POST['date_pointoftaxyear']);

			if (! $error)
			{
				// Si facture standard
				$object->socid				= GETPOST('socid','int');
				$object->type				= GETPOST('type');
				$object->number				= $_POST['facnumber'];
				$object->date				= $dateinvoice;
				$object->date_pointoftax	= $date_pointoftax;
				$object->note_public		= trim(GETPOST('note_public','none'));
				$object->note_private		= trim(GETPOST('note_private','none'));
				$object->ref_client			= $_POST['ref_client'];
				$object->ref_int			= $_POST['ref_int'];
				$object->modelpdf			= $_POST['model'];
				$object->fk_project			= $_POST['projectid'];
				$object->cond_reglement_id	= ($_POST['type'] == 3?1:$_POST['cond_reglement_id']);
				$object->mode_reglement_id	= $_POST['mode_reglement_id'];
				$object->fk_account         = GETPOST('fk_account', 'int');
				$object->amount				= $_POST['amount'];
				$object->remise_absolue		= $_POST['remise_absolue'];
				$object->remise_percent		= $_POST['remise_percent'];
				$object->fk_incoterms 		= GETPOST('incoterm_id', 'int');
				$object->location_incoterms = GETPOST('location_incoterms', 'alpha');
				$object->multicurrency_code = GETPOST('multicurrency_code', 'alpha');
				$object->multicurrency_tx   = GETPOST('originmulticurrency_tx', 'int');

				if (GETPOST('type') == Facture::TYPE_SITUATION)
				{
					$object->situation_counter = 1;
					$object->situation_final = 0;
					$object->situation_cycle_ref = $object->newCycle();
				}

				$object->fetch_thirdparty();

				// If creation from another object of another module (Example: origin=propal, originid=1)
				if (! empty($origin) && ! empty($originid))
				{
					// Parse element/subelement (ex: project_task)
					$element = $subelement = $origin;
					if (preg_match('/^([^_]+)_([^_]+)/i', $origin, $regs)) {
						$element = $regs [1];
						$subelement = $regs [2];
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

					$object->origin = $origin;
					$object->origin_id = $originid;

					// Possibility to add external linked objects with hooks
					$object->linked_objects[$object->origin] = $object->origin_id;
					// link with order if it is a shipping invoice
					if ($object->origin == 'shipping')
					{
						require_once DOL_DOCUMENT_ROOT . '/expedition/class/expedition.class.php';
						$exp = new Expedition($db);
						$exp->fetch($object->origin_id);
						$exp->fetchObjectLinked();
						if (is_array($exp->linkedObjectsIds['commande']) && count($exp->linkedObjectsIds['commande']) > 0) {
							foreach ($exp->linkedObjectsIds['commande'] as $key => $value){
								$object->linked_objects['commande'] = $value;
							}
						}
					}

					if (is_array($_POST['other_linked_objects']) && ! empty($_POST['other_linked_objects']))
					{
						$object->linked_objects = array_merge($object->linked_objects, $_POST['other_linked_objects']);
					}

					$id = $object->create($user);      // This include class to add_object_linked() and add add_contact()

					if ($id > 0)
					{
						dol_include_once('/' . $element . '/class/' . $subelement . '.class.php');

						$classname = ucfirst($subelement);
						$srcobject = new $classname($db);

						dol_syslog("Try to find source object origin=" . $object->origin . " originid=" . $object->origin_id . " to add lines or deposit lines");
						$result = $srcobject->fetch($object->origin_id);

						// If deposit invoice
						if ($_POST['type'] == Facture::TYPE_DEPOSIT)
						{
							$typeamount = GETPOST('typedeposit', 'alpha');
							$valuedeposit = GETPOST('valuedeposit', 'int');

							$amountdeposit = array();
							if (!empty($conf->global->MAIN_DEPOSIT_MULTI_TVA))
							{
								if ($typeamount == 'amount') $amount = $valuedeposit;
								else $amount = $srcobject->total_ttc * ($valuedeposit / 100);

								$TTotalByTva = array();
								foreach ($srcobject->lines as &$line)
								{
									if(! empty($line->special_code)) continue;
									$TTotalByTva[$line->tva_tx] += $line->total_ttc ;
								}

								$amount_to_diff = 0;
								foreach ($TTotalByTva as $tva => &$total)
								{
									$coef = $total / $srcobject->total_ttc; // Calc coef
									$am = $amount * $coef;
									$amount_ttc_diff += $am;
									$amountdeposit[$tva] += $am / (1 + $tva / 100); // Convert into HT for the addline
								}
							}
							else
							{
								if ($typeamount == 'amount')
								{
									$amountdeposit[0] = $valuedeposit;
								}
								else
								{
									if ($result > 0)
									{
										$totalamount = 0;
										$lines = $srcobject->lines;
										$numlines=count($lines);
										for ($i=0; $i<$numlines; $i++)
										{
											$qualified=1;
											if (empty($lines[$i]->qty)) $qualified=0;	// We discard qty=0, it is an option
											if (! empty($lines[$i]->special_code)) $qualified=0;	// We discard special_code (frais port, ecotaxe, option, ...)
											if ($qualified) $totalamount += $lines[$i]->total_ht; // Fixme : is it not for the customer ? Shouldn't we take total_ttc ?
										}

										if ($totalamount != 0) {
											if ($numlines > 0) $numlines = $numlines-1;
											$tva_tx = $lines[$numlines]->tva_tx;
											if (! empty($lines[$numlines]->vat_src_code) && ! preg_match('/\(/', $tva_tx)) $tva_tx .= ' ('.$lines[$numlines]->vat_src_code.')';
											$amountdeposit[$tva_tx] = ($totalamount * $valuedeposit) / 100;
										} else {
											$amountdeposit[0] = 0;
										}
									} else {
										setEventMessages($srcobject->error, $srcobject->errors, 'errors');
										$error++;
									}
								}

								$amount_ttc_diff = $amountdeposit[0];
							}

							foreach ($amountdeposit as $tva => $amount)
							{
								$arraylist = array('amount' => 'FixAmount','variable' => 'VarAmount');
								$descline = $langs->trans('Deposit');
								//$descline.= ' - '.$langs->trans($arraylist[$typeamount]);
								if ($typeamount=='amount') {
									$descline.= ' ('. price($valuedeposit, '', $langs, 0, - 1, - 1, (!empty($object->multicurrency_code) ? $object->multicurrency_code : $conf->currency)).')';
								} elseif ($typeamount=='variable') {
									$descline.= ' ('. $valuedeposit.'%)';
								}
								$descline.= ' - '.$srcobject->ref;
								$result = $object->addline(
										$descline,
										$amount,		 	// subprice
										1, 						// quantity
										$tva,     // vat rate
										0,                      // localtax1_tx
										0, 						// localtax2_tx
										(empty($conf->global->INVOICE_PRODUCTID_DEPOSIT)?0:$conf->global->INVOICE_PRODUCTID_DEPOSIT), 	// fk_product
										0, 						// remise_percent
										0, 						// date_start
										0, 						// date_end
										0,
										$lines[$i]->info_bits,  // info_bits
										0,
										'HT',
										0,
										0, 						// product_type
										1,
										$lines[$i]->special_code,
										$object->origin,
										0,
										0,
										0,
										0
										//,$langs->trans('Deposit') //Deprecated
									);
							}

							$diff = $object->total_ttc - $amount_ttc_diff;

							if (!empty($conf->global->MAIN_DEPOSIT_MULTI_TVA) && $diff != 0)
							{
								$object->fetch_lines();
								$subprice_diff = $object->lines[0]->subprice - $diff / (1 + $object->lines[0]->tva_tx / 100);
								$object->updateline($object->lines[0]->id, $object->lines[0]->desc, $subprice_diff, $object->lines[0]->qty, $object->lines[0]->remise_percent, $object->lines[0]->date_start, $object->lines[0]->date_end, $object->lines[0]->tva_tx, 0, 0, 'HT', $object->lines[0]->info_bits, $object->lines[0]->product_type, 0, 0, 0, $object->lines[0]->pa_ht, $object->lines[0]->label, 0, array(), 100);
							}

						}
						else
						{
							if ($result > 0)
							{
								$lines = $srcobject->lines;
								if (empty($lines) && method_exists($srcobject, 'fetch_lines'))
								{
									$srcobject->fetch_lines();
									$lines = $srcobject->lines;
								}

								$fk_parent_line=0;
								$num=count($lines);
								for ($i=0;$i<$num;$i++)
								{
									// Don't add lines with qty 0 when coming from a shipment including all order lines
									if($srcobject->element == 'shipping' && $conf->global->SHIPMENT_GETS_ALL_ORDER_PRODUCTS && $lines[$i]->qty == 0) continue;
									// Don't add closed lines when coming from a contract
									if($srcobject->element == 'contrat' && $lines[$i]->statut == 5) continue;

									$label=(! empty($lines[$i]->label)?$lines[$i]->label:'');
									$desc=(! empty($lines[$i]->desc)?$lines[$i]->desc:$lines[$i]->libelle);
									if ($object->situation_counter == 1) $lines[$i]->situation_percent =  0;

									if ($lines[$i]->subprice < 0)
									{
										// Negative line, we create a discount line
										$discount = new DiscountAbsolute($db);
										$discount->fk_soc = $object->socid;
										$discount->amount_ht = abs($lines[$i]->total_ht);
										$discount->amount_tva = abs($lines[$i]->total_tva);
										$discount->amount_ttc = abs($lines[$i]->total_ttc);
										$discount->tva_tx = $lines[$i]->tva_tx;
										$discount->fk_user = $user->id;
										$discount->description = $desc;
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
										$product_type = ($lines[$i]->product_type ? $lines[$i]->product_type : 0);

										// Date start
										$date_start = false;
										if ($lines[$i]->date_debut_prevue)
											$date_start = $lines[$i]->date_debut_prevue;
										if ($lines[$i]->date_debut_reel)
											$date_start = $lines[$i]->date_debut_reel;
										if ($lines[$i]->date_start)
											$date_start = $lines[$i]->date_start;

											// Date end
										$date_end = false;
										if ($lines[$i]->date_fin_prevue)
											$date_end = $lines[$i]->date_fin_prevue;
										if ($lines[$i]->date_fin_reel)
											$date_end = $lines[$i]->date_fin_reel;
										if ($lines[$i]->date_end)
											$date_end = $lines[$i]->date_end;

											// Reset fk_parent_line for no child products and special product
										if (($lines[$i]->product_type != 9 && empty($lines[$i]->fk_parent_line)) || $lines[$i]->product_type == 9) {
											$fk_parent_line = 0;
										}

										// Extrafields
										if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED) && method_exists($lines[$i], 'fetch_optionals')) {
											$lines[$i]->fetch_optionals($lines[$i]->rowid);
											$array_options = $lines[$i]->array_options;
										}

										$tva_tx = $lines[$i]->tva_tx;
										if (! empty($lines[$i]->vat_src_code) && ! preg_match('/\(/', $tva_tx)) $tva_tx .= ' ('.$lines[$i]->vat_src_code.')';

										// View third's localtaxes for NOW and do not use value from origin.
										// TODO Is this really what we want ? Yes if source if template invoice but what if proposal or order ?
										$localtax1_tx = get_localtax($tva_tx, 1, $object->thirdparty);
										$localtax2_tx = get_localtax($tva_tx, 2, $object->thirdparty);

										$result = $object->addline(
											$desc, $lines[$i]->subprice, $lines[$i]->qty, $tva_tx, $localtax1_tx, $localtax2_tx, $lines[$i]->fk_product,
											$lines[$i]->remise_percent, $date_start, $date_end, 0, $lines[$i]->info_bits, $lines[$i]->fk_remise_except,
											'HT', 0, $product_type, $lines[$i]->rang, $lines[$i]->special_code, $object->origin, $lines[$i]->rowid,
											$fk_parent_line, $lines[$i]->fk_fournprice, $lines[$i]->pa_ht, $label, $array_options,
											$lines[$i]->situation_percent, $lines[$i]->fk_prev_id, $lines[$i]->fk_unit
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

						// Now we create same links to contact than the ones found on origin object
						/* Useless, already into the create
						if (! empty($conf->global->MAIN_PROPAGATE_CONTACTS_FROM_ORIGIN))
						{
    						$originforcontact = $object->origin;
    						$originidforcontact = $object->origin_id;
    						if ($originforcontact == 'shipping')     // shipment and order share the same contacts. If creating from shipment we take data of order
    						{
    						    $originforcontact=$srcobject->origin;
    						    $originidforcontact=$srcobject->origin_id;
    						}
    						$sqlcontact = "SELECT code, fk_socpeople FROM ".MAIN_DB_PREFIX."element_contact as ec, ".MAIN_DB_PREFIX."c_type_contact as ctc";
    						$sqlcontact.= " WHERE element_id = ".$originidforcontact." AND ec.fk_c_type_contact = ctc.rowid AND ctc.element = '".$originforcontact."'";

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
						$parameters = array('objFrom' => $srcobject);
						$reshook = $hookmanager->executeHooks('createFrom', $parameters, $object, $action); // Note that $action and $object may have been
						// modified by hook
						if ($reshook < 0)
						{
							setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
							$error++;
						}

					} else {
						setEventMessages($object->error, $object->errors, 'errors');
						$error++;
					}
				}
				else
				{   // If some invoice's lines coming from page
					$id = $object->create($user);

					for ($i = 1; $i <= $NBLINES; $i ++) {
						if ($_POST['idprod' . $i]) {
							$product = new Product($db);
							$product->fetch($_POST['idprod' . $i]);
							$startday = dol_mktime(12, 0, 0, $_POST['date_start' . $i . 'month'], $_POST['date_start' . $i . 'day'], $_POST['date_start' . $i . 'year']);
							$endday = dol_mktime(12, 0, 0, $_POST['date_end' . $i . 'month'], $_POST['date_end' . $i . 'day'], $_POST['date_end' . $i . 'year']);
							$result = $object->addline($product->description, $product->price, $_POST['qty' . $i], $product->tva_tx, $product->localtax1_tx, $product->localtax2_tx, $_POST['idprod' . $i], $_POST['remise_percent' . $i], $startday, $endday, 0, 0, '', $product->price_base_type, $product->price_ttc, $product->type, -1, 0, '', 0, 0, null, 0, '', 0, 100, '', $product->fk_unit);
						}
					}
				}
			}
		}

		// Situation invoices
		if (GETPOST('type') == Facture::TYPE_SITUATION && (!empty($_POST['situations'])))
		{
			$datefacture = dol_mktime(12, 0, 0, $_POST['remonth'], $_POST['reday'], $_POST['reyear']);
			if (empty($datefacture)) {
				$error++;
				$mesg = '<div class="error">' . $langs->trans("ErrorFieldRequired", $langs->trans("Date")) . '</div>';
			}

			$date_pointoftax = dol_mktime(12, 0, 0, $_POST['date_pointoftaxmonth'], $_POST['date_pointoftaxday'], $_POST['date_pointoftaxyear']);

			if (!($_POST['situations'] > 0)) {
				$error++;
				$mesg = '<div class="error">' . $langs->trans("ErrorFieldRequired", $langs->trans("InvoiceSituation")) . '</div>';
			}

			if (!$error) {
				$result = $object->fetch($_POST['situations']);
				$object->fk_facture_source = $_POST['situations'];
				$object->type = Facture::TYPE_SITUATION;

				if (!empty($origin) && !empty($originid))
				{
					$object->origin = $origin;
					$object->origin_id = $originid;

					foreach ($object->lines as $i => &$line)
					{
						$line->origin = $object->origin;
						$line->origin_id = $line->id;
						$line->fk_prev_id = $line->id;
						$line->fetch_optionals($line->id);
						$line->situation_percent =  $line->get_prev_progress($object->id); // get good progress including credit note

						// Si fk_remise_except defini on vérifie si la réduction à déjà été appliquée
						if ($line->fk_remise_except)
						{
						    $discount=new DiscountAbsolute($line->db);
						    $result=$discount->fetch($line->fk_remise_except);
						    if ($result > 0)
						    {
						        // Check if discount not already affected to another invoice
						        if ($discount->fk_facture_line > 0)
						        {
						            $line->fk_remise_except = 0;
						        }
						    }
						}
					}
				}

				$object->fetch_thirdparty();
				$object->date = $datefacture;
				$object->date_pointoftax = $date_pointoftax;
				$object->note_public = trim(GETPOST('note_public','none'));
				$object->note = trim(GETPOST('note','none'));
				$object->ref_client = GETPOST('ref_client','alpha');
				$object->ref_int = GETPOST('ref_int','alpha');
				$object->modelpdf = GETPOST('model','alpha');
				$object->fk_project = GETPOST('projectid','int');
				$object->cond_reglement_id = GETPOST('cond_reglement_id','int');
				$object->mode_reglement_id = GETPOST('mode_reglement_id','int');
				$object->remise_absolue = GETPOST('remise_absolue','int');
				$object->remise_percent = GETPOST('remise_percent','int');

				// Proprietes particulieres a facture de remplacement

				$object->situation_counter = $object->situation_counter + 1;
				$id = $object->createFromCurrent($user);
				if ($id <= 0)
				{
					$mesg = $object->error;
				}
				else
				{
					$nextSituationInvoice = new Facture($db);
					$nextSituationInvoice->fetch($id);
					// create extrafields with data from create form
					$extralabels = $extrafields->fetch_name_optionals_label($nextSituationInvoice->table_element);
					$ret = $extrafields->setOptionalsFromPost($extralabels, $nextSituationInvoice);
					if ($ret > 0) {
						$nextSituationInvoice->insertExtraFields();
					}
				}
			}
		}

		// End of object creation, we show it
		if ($id > 0 && ! $error)
		{
			$db->commit();

			// Define output language
			if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE) && count($object->lines))
			{
				$outputlangs = $langs;
				$newlang = '';
				if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id','aZ09')) $newlang = GETPOST('lang_id','aZ09');
				if ($conf->global->MAIN_MULTILANGS && empty($newlang))	$newlang = $object->thirdparty->default_lang;
				if (! empty($newlang)) {
					$outputlangs = new Translate("", $conf);
					$outputlangs->setDefaultLang($newlang);
					$outputlangs->load('products');
				}
				$model=$object->modelpdf;
				$ret = $object->fetch($id); // Reload to get new records

				$result = $object->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
				if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
			}

			header('Location: ' . $_SERVER["PHP_SELF"] . '?facid=' . $id);
			exit();
		}
		else
		{
			$db->rollback();
			$action = 'create';
			$_GET["origin"] = $_POST["origin"];
			$_GET["originid"] = $_POST["originid"];
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	// Add a new line
	else if ($action == 'addline' && $user->rights->facture->creer)
	{
		$langs->load('errors');
		$error = 0;

		// Set if we used free entry or predefined product
		$predef='';
		$product_desc=(GETPOST('dp_desc','none')?GETPOST('dp_desc','none'):'');
		$price_ht = GETPOST('price_ht');
		$price_ht_devise = GETPOST('multicurrency_price_ht');
		$prod_entry_mode = GETPOST('prod_entry_mode','alpha');
		if ($prod_entry_mode == 'free')
		{
			$idprod=0;
			$tva_tx = (GETPOST('tva_tx','alpha') ? GETPOST('tva_tx','alpha') : 0);
		}
		else
		{
			$idprod=GETPOST('idprod', 'int');
			$tva_tx = '';
		}

		$qty = GETPOST('qty' . $predef);
		$remise_percent = GETPOST('remise_percent' . $predef);

		// Extrafields
		$extrafieldsline = new ExtraFields($db);
		$extralabelsline = $extrafieldsline->fetch_name_optionals_label($object->table_element_line);
		$array_options = $extrafieldsline->getOptionalsFromPost($extralabelsline, $predef);
		// Unset extrafield
		if (is_array($extralabelsline)) {
			// Get extra fields
			foreach ($extralabelsline as $key => $value) {
				unset($_POST["options_" . $key . $predef]);
			}
		}

		if (empty($idprod) && ($price_ht < 0) && ($qty < 0)) {
			setEventMessages($langs->trans('ErrorBothFieldCantBeNegative', $langs->transnoentitiesnoconv('UnitPriceHT'), $langs->transnoentitiesnoconv('Qty')), null, 'errors');
			$error++;
		}
		if (!$prod_entry_mode)
		{
			if (GETPOST('type') < 0 && ! GETPOST('search_idprod'))
			{
				setEventMessages($langs->trans('ErrorChooseBetweenFreeEntryOrPredefinedProduct'), null, 'errors');
				$error++;
			}
		}
		if ($prod_entry_mode == 'free' && empty($idprod) && GETPOST('type') < 0) {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Type')), null, 'errors');
			$error++;
		}
		if ($prod_entry_mode == 'free' && empty($idprod) && (($price_ht < 0 && empty($conf->global->FACTURE_ENABLE_NEGATIVE_LINES)) || $price_ht == '') && $price_ht_devise == '') 	// Unit price can be 0 but not ''
		{
			if ($price_ht < 0 && empty($conf->global->FACTURE_ENABLE_NEGATIVE_LINES))
			{
				$langs->load("errors");
				setEventMessages($langs->trans("ErrorFieldCantBeNegativeOnInvoice", $langs->transnoentitiesnoconv("UnitPriceHT")), null, 'errors');
				$error++;
			}
			else
			{
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

		if (!$error && !empty($conf->variants->enabled) && $prod_entry_mode != 'free') {
			if ($combinations = GETPOST('combinations', 'array')) {
				//Check if there is a product with the given combination
				$prodcomb = new ProductCombination($db);

				if ($res = $prodcomb->fetchByProductCombination2ValuePairs($idprod, $combinations)) {
					$idprod = $res->fk_product_child;
				} else {
					setEventMessage($langs->trans('ErrorProductCombinationNotFound'), 'errors');
					$error++;
				}
			}
		}

		if (! $error && ($qty >= 0) && (! empty($product_desc) || ! empty($idprod))) {

			$ret = $object->fetch($id);
			if ($ret < 0) {
				dol_print_error($db, $object->error);
				exit();
			}
			$ret = $object->fetch_thirdparty();

			// Clean parameters
			$date_start = dol_mktime(GETPOST('date_start' . $predef . 'hour'), GETPOST('date_start' . $predef . 'min'), GETPOST('date_start' . $predef . 'sec'), GETPOST('date_start' . $predef . 'month'), GETPOST('date_start' . $predef . 'day'), GETPOST('date_start' . $predef . 'year'));
			$date_end = dol_mktime(GETPOST('date_end' . $predef . 'hour'), GETPOST('date_end' . $predef . 'min'), GETPOST('date_end' . $predef . 'sec'), GETPOST('date_end' . $predef . 'month'), GETPOST('date_end' . $predef . 'day'), GETPOST('date_end' . $predef . 'year'));
			$price_base_type = (GETPOST('price_base_type', 'alpha') ? GETPOST('price_base_type', 'alpha') : 'HT');

			// Define special_code for special lines
			$special_code = 0;
			// if (empty($_POST['qty'])) $special_code=3; // Options should not exists on invoices

			// Ecrase $pu par celui du produit
			// Ecrase $desc par celui du produit
			// Ecrase $tva_tx par celui du produit
			// Ecrase $base_price_type par celui du produit
			// Replaces $fk_unit with the product's
			if (! empty($idprod))
			{
				$prod = new Product($db);
				$prod->fetch($idprod);

				$label = ((GETPOST('product_label') && GETPOST('product_label') != $prod->label) ? GETPOST('product_label') : '');

				// Update if prices fields are defined
				$tva_tx = get_default_tva($mysoc, $object->thirdparty, $prod->id);
				$tva_npr = get_default_npr($mysoc, $object->thirdparty, $prod->id);
				if (empty($tva_tx)) $tva_npr=0;

				$pu_ht = $prod->price;
				$pu_ttc = $prod->price_ttc;
				$price_min = $prod->price_min;
				$price_base_type = $prod->price_base_type;

				// If price per segment
				if (! empty($conf->global->PRODUIT_MULTIPRICES) && ! empty($object->thirdparty->price_level))
				{
					$pu_ht = $prod->multiprices[$object->thirdparty->price_level];
					$pu_ttc = $prod->multiprices_ttc[$object->thirdparty->price_level];
					$price_min = $prod->multiprices_min[$object->thirdparty->price_level];
					$price_base_type = $prod->multiprices_base_type[$object->thirdparty->price_level];
					if (! empty($conf->global->PRODUIT_MULTIPRICES_USE_VAT_PER_LEVEL))  // using this option is a bug. kept for backward compatibility
					{
						if (isset($prod->multiprices_tva_tx[$object->thirdparty->price_level])) $tva_tx=$prod->multiprices_tva_tx[$object->thirdparty->price_level];
						if (isset($prod->multiprices_recuperableonly[$object->thirdparty->price_level])) $tva_npr=$prod->multiprices_recuperableonly[$object->thirdparty->price_level];
						if (empty($tva_tx)) $tva_npr=0;
					}
				}
				// If price per customer
				elseif (! empty($conf->global->PRODUIT_CUSTOMER_PRICES))
				{
					require_once DOL_DOCUMENT_ROOT . '/product/class/productcustomerprice.class.php';

					$prodcustprice = new Productcustomerprice($db);

					$filter = array('t.fk_product' => $prod->id,'t.fk_soc' => $object->thirdparty->id);

					$result = $prodcustprice->fetch_all('', '', 0, 0, $filter);
					if ($result) {
						if (count($prodcustprice->lines) > 0) {
							$pu_ht = price($prodcustprice->lines[0]->price);
							$pu_ttc = price($prodcustprice->lines[0]->price_ttc);
							$price_base_type = $prodcustprice->lines[0]->price_base_type;
							$tva_tx = $prodcustprice->lines[0]->tva_tx;
							if ($prodcustprice->lines[0]->default_vat_code && ! preg_match('/\(.*\)/', $tva_tx)) $tva_tx.= ' ('.$prodcustprice->lines[0]->default_vat_code.')';
							$tva_npr = $prodcustprice->lines[0]->recuperableonly;
							if (empty($tva_tx)) $tva_npr=0;
						}
					}
				}
				// If price per quantity
				elseif (! empty($conf->global->PRODUIT_CUSTOMER_PRICES_BY_QTY))
				{
					if ($prod->prices_by_qty[0])	// yes, this product has some prices per quantity
					{
						// Search the correct price into loaded array product_price_by_qty using id of array retrieved into POST['pqp'].
						$pqp = GETPOST('pbq','int');

						// Search price into product_price_by_qty from $prod->id
						foreach($prod->prices_by_qty_list[0] as $priceforthequantityarray)
						{
							if ($priceforthequantityarray['rowid'] != $pqp) continue;
							// We found the price
							if ($priceforthequantityarray['price_base_type'] == 'HT')
							{
								$pu_ht = $priceforthequantityarray['unitprice'];
							}
							else
							{
								$pu_ttc = $priceforthequantityarray['unitprice'];
							}
							// Note: the remise_percent or price by qty is used to set data on form, so we will use value from POST.
							break;
						}
					}
				}
				// If price per quantity and customer
				elseif (! empty($conf->global->PRODUIT_CUSTOMER_PRICES_BY_QTY_MULTIPRICES))
				{
					if ($prod->prices_by_qty[$object->thirdparty->price_level]) // yes, this product has some prices per quantity
					{
						// Search the correct price into loaded array product_price_by_qty using id of array retrieved into POST['pqp'].
						$pqp = GETPOST('pbq','int');

						// Search price into product_price_by_qty from $prod->id
						foreach($prod->prices_by_qty_list[$object->thirdparty->price_level] as $priceforthequantityarray)
						{
							if ($priceforthequantityarray['rowid'] != $pqp) continue;
							// We found the price
							if ($priceforthequantityarray['price_base_type'] == 'HT')
							{
								$pu_ht = $priceforthequantityarray['unitprice'];
							}
							else
							{
								$pu_ttc = $priceforthequantityarray['unitprice'];
							}
							// Note: the remise_percent or price by qty is used to set data on form, so we will use value from POST.
							break;
						}
					}
				}

				$tmpvat = price2num(preg_replace('/\s*\(.*\)/', '', $tva_tx));
				$tmpprodvat = price2num(preg_replace('/\s*\(.*\)/', '', $prod->tva_tx));

				// if price ht was forced (ie: from gui when calculated by margin rate and cost price). TODO Why this ?
				if (! empty($price_ht))
				{
					$pu_ht = price2num($price_ht, 'MU');
					$pu_ttc = price2num($pu_ht * (1 + ($tmpvat / 100)), 'MU');
				}
				// On reevalue prix selon taux tva car taux tva transaction peut etre different
				// de ceux du produit par defaut (par exemple si pays different entre vendeur et acheteur).
				elseif ($tmpvat != $tmpprodvat)
				{
					if ($price_base_type != 'HT')
					{
						$pu_ht = price2num($pu_ttc / (1 + ($tmpvat / 100)), 'MU');
					}
					else
					{
						$pu_ttc = price2num($pu_ht * (1 + ($tmpvat / 100)), 'MU');
					}
				}

				$desc = '';

				// Define output language
				if (! empty($conf->global->MAIN_MULTILANGS) && ! empty($conf->global->PRODUIT_TEXTS_IN_THIRDPARTY_LANGUAGE)) {
					$outputlangs = $langs;
					$newlang = '';
					if (empty($newlang) && GETPOST('lang_id','aZ09'))
						$newlang = GETPOST('lang_id','aZ09');
					if (empty($newlang))
						$newlang = $object->thirdparty->default_lang;
					if (! empty($newlang)) {
						$outputlangs = new Translate("", $conf);
						$outputlangs->setDefaultLang($newlang);
						$outputlangs->load('products');
					}

					$desc = (! empty($prod->multilangs [$outputlangs->defaultlang] ["description"])) ? $prod->multilangs [$outputlangs->defaultlang] ["description"] : $prod->description;
				} else {
					$desc = $prod->description;
				}

				$desc = dol_concatdesc($desc, $product_desc);

				// Add custom code and origin country into description
				if (empty($conf->global->MAIN_PRODUCT_DISABLE_CUSTOMCOUNTRYCODE) && (! empty($prod->customcode) || ! empty($prod->country_code))) {
					$tmptxt = '(';
					// Define output language
					if (! empty($conf->global->MAIN_MULTILANGS) && ! empty($conf->global->PRODUIT_TEXTS_IN_THIRDPARTY_LANGUAGE)) {
						$outputlangs = $langs;
						$newlang = '';
						if (empty($newlang) && GETPOST('lang_id','alpha'))
							$newlang = GETPOST('lang_id','alpha');
						if (empty($newlang))
							$newlang = $object->thirdparty->default_lang;
						if (! empty($newlang)) {
							$outputlangs = new Translate("", $conf);
							$outputlangs->setDefaultLang($newlang);
							$outputlangs->load('products');
						}
						if (! empty($prod->customcode))
							$tmptxt .= $outputlangs->transnoentitiesnoconv("CustomCode") . ': ' . $prod->customcode;
						if (! empty($prod->customcode) && ! empty($prod->country_code))
							$tmptxt .= ' - ';
						if (! empty($prod->country_code))
							$tmptxt .= $outputlangs->transnoentitiesnoconv("CountryOrigin") . ': ' . getCountry($prod->country_code, 0, $db, $outputlangs, 0);
					} else {
						if (! empty($prod->customcode))
							$tmptxt .= $langs->transnoentitiesnoconv("CustomCode") . ': ' . $prod->customcode;
						if (! empty($prod->customcode) && ! empty($prod->country_code))
							$tmptxt .= ' - ';
						if (! empty($prod->country_code))
							$tmptxt .= $langs->transnoentitiesnoconv("CountryOrigin") . ': ' . getCountry($prod->country_code, 0, $db, $langs, 0);
					}
					$tmptxt .= ')';
					$desc = dol_concatdesc($desc, $tmptxt);
				}

				$type = $prod->type;
				$fk_unit = $prod->fk_unit;
			} else {
				$pu_ht = price2num($price_ht, 'MU');
				$pu_ttc = price2num(GETPOST('price_ttc'), 'MU');
				$tva_npr = (preg_match('/\*/', $tva_tx) ? 1 : 0);
				$tva_tx = str_replace('*', '', $tva_tx);
				if (empty($tva_tx)) $tva_npr=0;
				$label = (GETPOST('product_label') ? GETPOST('product_label') : '');
				$desc = $product_desc;
				$type = GETPOST('type');
				$fk_unit= GETPOST('units', 'alpha');
				$pu_ht_devise = price2num($price_ht_devise, 'MU');
			}

			// Margin
			$fournprice = price2num(GETPOST('fournprice' . $predef) ? GETPOST('fournprice' . $predef) : '');
			$buyingprice = price2num(GETPOST('buying_price' . $predef) != '' ? GETPOST('buying_price' . $predef) : '');    // If buying_price is '0', we must keep this value

			// Local Taxes
			$localtax1_tx = get_localtax($tva_tx, 1, $object->thirdparty, $mysoc, $tva_npr);
			$localtax2_tx = get_localtax($tva_tx, 2, $object->thirdparty, $mysoc, $tva_npr);

			$info_bits = 0;
			if ($tva_npr)
				$info_bits |= 0x01;

			if (! empty($price_min) && (price2num($pu_ht) * (1 - price2num($remise_percent) / 100) < price2num($price_min))) {
				$mesg = $langs->trans("CantBeLessThanMinPrice", price(price2num($price_min, 'MU'), 0, $langs, 0, 0, - 1, $conf->currency));
				setEventMessages($mesg, null, 'errors');
			} else {
				// Insert line
				$result = $object->addline($desc, $pu_ht, $qty, $tva_tx, $localtax1_tx, $localtax2_tx, $idprod, $remise_percent, $date_start, $date_end, 0, $info_bits, '', $price_base_type, $pu_ttc, $type, - 1, $special_code, '', 0, GETPOST('fk_parent_line'), $fournprice, $buyingprice, $label, $array_options, $_POST['progress'], '', $fk_unit, $pu_ht_devise);

				if ($result > 0)
				{
					// Define output language
					if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE))
					{
						$outputlangs = $langs;
						$newlang = '';
						if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id','aZ09')) $newlang = GETPOST('lang_id','aZ09');
						if ($conf->global->MAIN_MULTILANGS && empty($newlang))	$newlang = $object->thirdparty->default_lang;
						if (! empty($newlang)) {
							$outputlangs = new Translate("", $conf);
							$outputlangs->setDefaultLang($newlang);
							$outputlangs->load('products');
						}
						$model=$object->modelpdf;
						$ret = $object->fetch($id); // Reload to get new records

						$result = $object->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
						if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
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
	}

	elseif ($action == 'updateligne' && $user->rights->facture->creer && ! GETPOST('cancel','alpha'))
	{
		if (! $object->fetch($id) > 0)	dol_print_error($db);
		$object->fetch_thirdparty();

		// Clean parameters
		$date_start = '';
		$date_end = '';
		$date_start = dol_mktime(GETPOST('date_starthour'), GETPOST('date_startmin'), GETPOST('date_startsec'), GETPOST('date_startmonth'), GETPOST('date_startday'), GETPOST('date_startyear'));
		$date_end = dol_mktime(GETPOST('date_endhour'), GETPOST('date_endmin'), GETPOST('date_endsec'), GETPOST('date_endmonth'), GETPOST('date_endday'), GETPOST('date_endyear'));
		$description = dol_htmlcleanlastbr(GETPOST('product_desc','none') ? GETPOST('product_desc','none') : GETPOST('desc','none'));
		$pu_ht = GETPOST('price_ht');
		$vat_rate = (GETPOST('tva_tx') ? GETPOST('tva_tx') : 0);
		$qty = GETPOST('qty');
		$pu_ht_devise = GETPOST('multicurrency_subprice');

		// Define info_bits
		$info_bits = 0;
		if (preg_match('/\*/', $vat_rate)) $info_bits |= 0x01;

		// Define vat_rate
		$vat_rate = str_replace('*', '', $vat_rate);
		$localtax1_rate = get_localtax($vat_rate, 1, $object->thirdparty);
		$localtax2_rate = get_localtax($vat_rate, 2, $object->thirdparty);

		// Add buying price
		$fournprice = price2num(GETPOST('fournprice') ? GETPOST('fournprice') : '');
		$buyingprice = price2num(GETPOST('buying_price') != '' ? GETPOST('buying_price') : '');       // If buying_price is '0', we muste keep this value

		// Extrafields
		$extrafieldsline = new ExtraFields($db);
		$extralabelsline = $extrafieldsline->fetch_name_optionals_label($object->table_element_line);
		$array_options = $extrafieldsline->getOptionalsFromPost($extralabelsline);
		// Unset extrafield
		if (is_array($extralabelsline)) {
			// Get extra fields
			foreach ($extralabelsline as $key => $value) {
				unset($_POST["options_" . $key]);
			}
		}

		// Define special_code for special lines
		$special_code=GETPOST('special_code');
		if (! GETPOST('qty')) $special_code=3;

		$line = new FactureLigne($db);
		$line->fetch(GETPOST('lineid'));
		$percent = $line->get_prev_progress($object->id);

		if($object->type == Facture::TYPE_CREDIT_NOTE && $object->situation_cycle_ref>0)
		{
		    // in case of situation credit note
		    if(GETPOST('progress') >= 0 )
		    {
		        $mesg = $langs->trans("CantBeNullOrPositive");
		        setEventMessages($mesg, null, 'warnings');
		        $error++;
		        $result = -1;
		    }
		    elseif (GETPOST('progress') < $line->situation_percent) // TODO : use a modified $line->get_prev_progress($object->id) result
		    {
		        $mesg = $langs->trans("CantBeLessThanMinPercent");
		        setEventMessages($mesg, null, 'warnings');
		        $error++;
		        $result = -1;
		    }
		}
		elseif (GETPOST('progress') < $percent)
		{
			$mesg = '<div class="warning">' . $langs->trans("CantBeLessThanMinPercent") . '</div>';
			setEventMessages($mesg, null, 'warnings');
			$error++;
			$result = -1;
		}

		// Check minimum price
		$productid = GETPOST('productid', 'int');
		if (! empty($productid))
		{
			$product = new Product($db);
			$product->fetch($productid);

			$type = $product->type;

			$price_min = $product->price_min;
			if ((! empty($conf->global->PRODUIT_MULTIPRICES)  || ! empty($conf->global->PRODUIT_CUSTOMER_PRICES_BY_QTY_MULTIPRICES)) && ! empty($object->thirdparty->price_level))
				$price_min = $product->multiprices_min [$object->thirdparty->price_level];

			$label = ((GETPOST('update_label') && GETPOST('product_label')) ? GETPOST('product_label') : '');

			// Check price is not lower than minimum (check is done only for standard or replacement invoices)
			if (($object->type == Facture::TYPE_STANDARD || $object->type == Facture::TYPE_REPLACEMENT) && $price_min && (price2num($pu_ht) * (1 - price2num(GETPOST('remise_percent')) / 100) < price2num($price_min))) {
				setEventMessages($langs->trans("CantBeLessThanMinPrice", price(price2num($price_min, 'MU'), 0, $langs, 0, 0, - 1, $conf->currency)), null, 'errors');
				$error++;
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
		if (empty($productid) && (($pu_ht < 0 && empty($conf->global->FACTURE_ENABLE_NEGATIVE_LINES)) || $pu_ht == '') && $pu_ht_devise == '') 	// Unit price can be 0 but not ''
		{
			if ($pu_ht < 0 && empty($conf->global->FACTURE_ENABLE_NEGATIVE_LINES))
			{
				$langs->load("errors");
				setEventMessages($langs->trans("ErrorFieldCantBeNegativeOnInvoice", $langs->transnoentitiesnoconv("UnitPriceHT")), null, 'errors');
				$error++;
			}
			else
			{
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("UnitPriceHT")), null, 'errors');
				$error++;
			}
		}


		// Update line
		if (! $error) {
			if (empty($user->rights->margins->creer))
			{
				foreach ($object->lines as &$line)
				{
					if ($line->id == GETPOST('lineid'))
					{
						$fournprice = $line->fk_fournprice;
						$buyingprice = $line->pa_ht;
						break;
					}
				}
			}

			$result = $object->updateline(GETPOST('lineid'), $description, $pu_ht, $qty, GETPOST('remise_percent'),
				$date_start, $date_end, $vat_rate, $localtax1_rate, $localtax2_rate, 'HT', $info_bits, $type,
				GETPOST('fk_parent_line'), 0, $fournprice, $buyingprice, $label, $special_code, $array_options, GETPOST('progress'),
				$_POST['units'],$pu_ht_devise);

			if ($result >= 0) {
				if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
					// Define output language
					$outputlangs = $langs;
					$newlang = '';
					if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id','aZ09'))
						$newlang = GETPOST('lang_id','aZ09');
					if ($conf->global->MAIN_MULTILANGS && empty($newlang))
						$newlang = $object->thirdparty->default_lang;
					if (! empty($newlang)) {
						$outputlangs = new Translate("", $conf);
						$outputlangs->setDefaultLang($newlang);
						$outputlangs->load('products');
					}

					$ret = $object->fetch($id); // Reload to get new records
					$object->generateDocument($object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
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
	}

	else if ($action == 'updatealllines' && $user->rights->facture->creer && $_POST['all_percent'] == $langs->trans('Modifier'))
	{
		if (!$object->fetch($id) > 0) dol_print_error($db);
		if (!is_null(GETPOST('all_progress')) && GETPOST('all_progress') != "")
		{
			foreach ($object->lines as $line)
			{
				$percent = $line->get_prev_progress($object->id);
				if (GETPOST('all_progress') < $percent) {
					$mesg = '<div class="warning">' . $langs->trans("CantBeLessThanMinPercent") . '</div>';
					$result = -1;
				} else
					$object->update_percent($line, $_POST['all_progress']);
			}
		}
	}

	else if ($action == 'updateligne' && $user->rights->facture->creer && $_POST['cancel'] == $langs->trans('Cancel')) {
		header('Location: ' . $_SERVER["PHP_SELF"] . '?facid=' . $id); // Pour reaffichage de la fiche en cours d'edition
		exit();
	}

	// Outing situation invoice from cycle
	elseif ($action == 'confirm_situationout' && $confirm == 'yes' && $user->rights->facture->creer)
	{
	    $object->fetch($id,'', '','', true);

	    if ($object->statut == Facture::STATUS_VALIDATED
	        && $object->type == Facture::TYPE_SITUATION
	        && $user->rights->facture->creer
	        && !$objectidnext
	        && $object->is_last_in_cycle()
	        && ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->facture->creer))
	            || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->facture->invoice_advance->unvalidate)))
	        )
	    {
	        $outingError = 0;
	        $newCycle = $object->newCycle(); // we need to keep the "situation behavior" so we place it on a new situation cycle
	        if($newCycle > 1)
	        {
	            // Search credit notes
	            $lastCycle = $object->situation_cycle_ref;
	            $lastSituationCounter = $object->situation_counter;
	            $linkedCreditNotesList = array();

                if (count($object->tab_next_situation_invoice) > 0) {
                    foreach ($object->tab_next_situation_invoice as $next_invoice) {
                        if($next_invoice->type == Facture::TYPE_CREDIT_NOTE
                            && $next_invoice->situation_counter == $object->situation_counter
                            && $next_invoice->fk_facture_source == $object->id
                          )
                        {
                            $linkedCreditNotesList[] = $next_invoice->id ;
                        }
                    }
                }

	            $object->situation_cycle_ref = $newCycle;
	            $object->situation_counter = 1;
	            $object->situation_final = 0;
	            if($object->update($user) > 0)
	            {
	                $errors = 0;
	                if(count($linkedCreditNotesList) > 0)
	                {
	                    // now, credit note must follow
	                    $sql = 'UPDATE '.MAIN_DB_PREFIX.'facture ';
	                    $sql.= ' SET situation_cycle_ref='.$newCycle;
	                    $sql.= ' , situation_final=0';
	                    $sql.= ' , situation_counter='.$object->situation_counter;
	                    $sql.= ' WHERE rowid IN ('.implode(',',$linkedCreditNotesList).')';

	                    $resql=$db->query($sql);
	                    if (!$resql) $errors++;

	                    // Change each progression persent on each lines
	                    foreach($object->lines as $line)
	                    {

	                        // no traitement for special product
	                        if ($line->product_type == 9 )  continue;


	                        if(!empty($object->tab_previous_situation_invoice))
	                        {
                                // search the last invoice in cycle
	                            $lineIndex = count($object->tab_previous_situation_invoice) - 1;
                                $searchPreviousInvoice = true;
                                while( $searchPreviousInvoice )
                                {
                                    if($object->tab_previous_situation_invoice[$lineIndex]->type  == Facture::TYPE_SITUATION || $lineIndex < 1)
                                    {
                                        $searchPreviousInvoice=false; // find, exit;
                                        break;
                                    }
                                    else
                                    {
                                        $lineIndex--; // go to previous invoice in cycle
                                    }
                                }


                                $maxPrevSituationPercent = 0;
                                foreach($object->tab_previous_situation_invoice[$lineIndex]->lines as $prevLine)
                                {
                                    if($prevLine->id == $line->fk_prev_id)
                                    {
                                        $maxPrevSituationPercent = max($maxPrevSituationPercent,$prevLine->situation_percent);
                                    }
                                }


                                $line->situation_percent = $line->situation_percent - $maxPrevSituationPercent;

                                if($line->update()<0) $errors++;

	                        }
	                    }
	                }

                    if (!$errors)
                    {
                        setEventMessages($langs->trans('Updated'), '', 'mesgs');
                        header("Location: ".$_SERVER['PHP_SELF']."?id=".$id);
                    }
                    else
                    {
                        setEventMessages($langs->trans('ErrorOutingSituationInvoiceCreditNote'), array(), 'errors');
                    }
	            }
	            else
	            {
	                setEventMessages($langs->trans('ErrorOutingSituationInvoiceOnUpdate'), array(), 'errors');
	            }
	        }
	        else
	        {
	            setEventMessages($langs->trans('ErrorFindNextSituationInvoice'), array(), 'errors');
	        }
	    }
	}

	// add lines from objectlinked
	elseif($action == 'import_lines_from_object'
	    && $user->rights->facture->creer
	    && $object->statut == Facture::STATUS_DRAFT
	    && ($object->type == Facture::TYPE_STANDARD || $object->type == Facture::TYPE_REPLACEMENT || $object->type == Facture::TYPE_DEPOSIT || $object->type == Facture::TYPE_PROFORMA || $object->type == Facture::TYPE_SITUATION))
	{
	    $fromElement = GETPOST('fromelement');
	    $fromElementid = GETPOST('fromelementid');
	    $importLines = GETPOST('line_checkbox');

	    if(!empty($importLines) && is_array($importLines) && !empty($fromElement) && ctype_alpha($fromElement) && !empty($fromElementid))
	    {
	        if($fromElement == 'commande')
	        {
	            dol_include_once('/'.$fromElement.'/class/'.$fromElement.'.class.php');
	            $lineClassName = 'OrderLine';
	        }
	        elseif($fromElement == 'propal')
	        {
	            dol_include_once('/comm/'.$fromElement.'/class/'.$fromElement.'.class.php');
	            $lineClassName = 'PropaleLigne';
	        }
	        $nextRang = count($object->lines) + 1;
	        $importCount = 0;
	        $error = 0;
	        foreach($importLines as $lineId)
	        {
	            $lineId = intval($lineId);
                $originLine = new $lineClassName($db);
                if(intval($fromElementid) > 0 && $originLine->fetch( $lineId ) > 0)
                {
                    $originLine->fetch_optionals($lineId);
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
                    $ventil = 0;
                    $info_bits = $originLine->info_bits;
                    $fk_remise_except = $originLine->fk_remise_except;
                    $price_base_type='HT';
                    $pu_ttc=0;
                    $type = $originLine->product_type;
                    $rang=$nextRang++;
                    $special_code = $originLine->special_code;
                    $origin = $originLine->element;
                    $origin_id = $originLine->id;
                    $fk_parent_line=0;
                    $fk_fournprice=$originLine->fk_fournprice;
                    $pa_ht = $originLine->pa_ht;
                    $label = $originLine->label;
                    $array_options = $originLine->array_options;
                    $situation_percent = 100;
                    $fk_prev_id = '';
                    $fk_unit = $originLine->fk_unit;
                    $pu_ht_devise = $originLine->multicurrency_subprice;

                    $res = $object->addline($desc, $pu_ht, $qty, $txtva, $txlocaltax1, $txlocaltax2, $fk_product, $remise_percent, $date_start, $date_end, $ventil, $info_bits, $fk_remise_except, $price_base_type, $pu_ttc, $type, $rang, $special_code, $origin, $origin_id, $fk_parent_line, $fk_fournprice, $pa_ht, $label, $array_options, $situation_percent, $fk_prev_id, $fk_unit,$pu_ht_devise);

                    if($res > 0){
                        $importCount++;
                    }else{
                        $error++;
                    }
                }
                else{
                    $error++;
                }
	        }

	        if($error)
	        {
	            setEventMessage($langs->trans('ErrorsOnXLines',$error), 'errors');
	        }
	    }
	}

	// Actions when printing a doc from card
	include DOL_DOCUMENT_ROOT.'/core/actions_printing.inc.php';

	// Actions to send emails
	if (empty($id)) $id=$facid;
	$trigger_name='BILL_SENTBYMAIL';
	$paramname='id';
	$autocopy='MAIN_MAIL_AUTOCOPY_INVOICE_TO';
	$trackid='inv'.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';

	// Actions to build doc
	$upload_dir = $conf->facture->dir_output;
	$permissioncreate=$user->rights->facture->creer;
	include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';


	if ($action == 'update_extras') {
		$object->oldcopy = dol_clone($object);

		// Fill array 'array_options' with data from add form
		$extralabels = $extrafields->fetch_name_optionals_label($object->table_element);
		$ret = $extrafields->setOptionalsFromPost($extralabels, $object, GETPOST('attribute','none'));
		if ($ret < 0) $error++;

		if (! $error)
		{
			// Actions on extra fields
			$result = $object->insertExtraFields('BILL_MODIFY');
			if ($result < 0)
			{
				setEventMessages($object->error, $object->errors, 'errors');
				$error++;
			}
		}

		if ($error)
			$action = 'edit_extras';
	}

	if (! empty($conf->global->MAIN_DISABLE_CONTACTS_TAB) && $user->rights->facture->creer) {
		if ($action == 'addcontact') {
			$result = $object->fetch($id);

			if ($result > 0 && $id > 0) {
				$contactid = (GETPOST('userid') ? GETPOST('userid') : GETPOST('contactid'));
				$result = $object->add_contact($contactid, $_POST["type"], $_POST["source"]);
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
		} // bascule du statut d'un contact
		elseif ($action == 'swapstatut') {
			if ($object->fetch($id)) {
				$result = $object->swapContactStatus(GETPOST('ligne'));
			} else {
				dol_print_error($db);
			}
		} // Efface un contact
		elseif ($action == 'deletecontact') {
			$object->fetch($id);
			$result = $object->delete_contact($lineid);

			if ($result >= 0) {
				header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
				exit();
			} else {
				dol_print_error($db);
			}
		}

		if ($error)
			$action = 'edit_extras';
	}
}


/*
 * View
 */

$form = new Form($db);
$formother = new FormOther($db);
$formfile = new FormFile($db);
$formmargin = new FormMargin($db);
$paymentstatic=new Paiement($db);
$bankaccountstatic = new Account($db);
if (! empty($conf->projet->enabled)) { $formproject = new FormProjets($db); }

$now = dol_now();

$title = $langs->trans('InvoiceCustomer') . " - " . $langs->trans('Card');
$helpurl = "EN:Customers_Invoices|FR:Factures_Clients|ES:Facturas_a_clientes";
llxHeader('', $title, $helpurl);


// Mode creation

if ($action == 'create')
{
	$facturestatic = new Facture($db);
	$extralabels = $extrafields->fetch_name_optionals_label($facturestatic->table_element);

	print load_fiche_titre($langs->trans('NewBill'));

	$soc = new Societe($db);
	if ($socid > 0)
		$res = $soc->fetch($socid);

	$currency_code = $conf->currency;

	// Load objectsrc
	$remise_absolue = 0;

	if (! empty($origin) && ! empty($originid))
	{
		// Parse element/subelement (ex: project_task)
		$element = $subelement = $origin;
		if (preg_match('/^([^_]+)_([^_]+)/i', $origin, $regs)) {
			$element = $regs [1];
			$subelement = $regs [2];
		}

		if ($element == 'project') {
			$projectid = $originid;

			if (!$cond_reglement_id) {
				$cond_reglement_id = $soc->cond_reglement_id;
			}
			if (!$mode_reglement_id) {
				$mode_reglement_id = $soc->mode_reglement_id;
			}
			if (!$remise_percent) {
				$remise_percent = $soc->remise_percent;
			}
			if (!$dateinvoice) {
				// Do not set 0 here (0 for a date is 1970)
				$dateinvoice = (empty($dateinvoice)?(empty($conf->global->MAIN_AUTOFILL_DATE)?-1:''):$dateinvoice);
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

			dol_include_once('/' . $element . '/class/' . $subelement . '.class.php');

			$classname = ucfirst($subelement);
			$objectsrc = new $classname($db);
			$objectsrc->fetch($originid);
			if (empty($objectsrc->lines) && method_exists($objectsrc, 'fetch_lines'))
				$objectsrc->fetch_lines();
			$objectsrc->fetch_thirdparty();

			$projectid = (! empty($projectid) ? $projectid : $objectsrc->fk_project);
			$ref_client = (! empty($objectsrc->ref_client) ? $objectsrc->ref_client : (! empty($objectsrc->ref_customer) ? $objectsrc->ref_customer:''));
			$ref_int = (! empty($objectsrc->ref_int) ? $objectsrc->ref_int : '');

			// only if socid not filled else it's allready done upper
			if (empty($socid))
				$soc = $objectsrc->thirdparty;

			$cond_reglement_id 	= (! empty($objectsrc->cond_reglement_id)?$objectsrc->cond_reglement_id:(! empty($soc->cond_reglement_id)?$soc->cond_reglement_id:0));
			$mode_reglement_id 	= (! empty($objectsrc->mode_reglement_id)?$objectsrc->mode_reglement_id:(! empty($soc->mode_reglement_id)?$soc->mode_reglement_id:0));
			$fk_account         = (! empty($objectsrc->fk_account)?$objectsrc->fk_account:(! empty($soc->fk_account)?$soc->fk_account:0));
			$remise_percent 	= (! empty($objectsrc->remise_percent)?$objectsrc->remise_percent:(! empty($soc->remise_percent)?$soc->remise_percent:0));
			$remise_absolue 	= (! empty($objectsrc->remise_absolue)?$objectsrc->remise_absolue:(! empty($soc->remise_absolue)?$soc->remise_absolue:0));
			$dateinvoice		= (empty($dateinvoice)?(empty($conf->global->MAIN_AUTOFILL_DATE)?-1:''):$dateinvoice);

			if (!empty($conf->multicurrency->enabled))
			{
				if (!empty($objectsrc->multicurrency_code)) $currency_code = $objectsrc->multicurrency_code;
				if (!empty($conf->global->MULTICURRENCY_USE_ORIGIN_TX) && !empty($objectsrc->multicurrency_tx))	$currency_tx = $objectsrc->multicurrency_tx;
			}

			// Replicate extrafields
			$objectsrc->fetch_optionals($originid);
			$object->array_options = $objectsrc->array_options;
		}
	}
	else
	{
		$cond_reglement_id 	= $soc->cond_reglement_id;
		$mode_reglement_id 	= $soc->mode_reglement_id;
		$fk_account        	= $soc->fk_account;
		$remise_percent 	= $soc->remise_percent;
		$remise_absolue 	= 0;
		$dateinvoice		= (empty($dateinvoice)?(empty($conf->global->MAIN_AUTOFILL_DATE)?-1:''):$dateinvoice);		// Do not set 0 here (0 for a date is 1970)

		if (!empty($conf->multicurrency->enabled) && !empty($soc->multicurrency_code)) $currency_code = $soc->multicurrency_code;
	}

	if (!empty($soc->id)) $absolute_discount = $soc->getAvailableDiscounts();
	$note_public = $object->getDefaultCreateValueFor('note_public', (is_object($objectsrc)?$objectsrc->note_public:null));
	$note_private = $object->getDefaultCreateValueFor('note_private', ((! empty($origin) && ! empty($originid) && is_object($objectsrc))?$objectsrc->note_private:null));

	if (! empty($conf->use_javascript_ajax))
	{
		require_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
		print ajax_combobox('fac_replacement');
		print ajax_combobox('fac_avoir');
		print ajax_combobox('situations');

	}

	if ($origin == 'contrat')
	{
		$langs->load("admin");
		$text=$langs->trans("ToCreateARecurringInvoice");
		$text.=' '.$langs->trans("ToCreateARecurringInvoiceGene", $langs->transnoentitiesnoconv("MenuFinancial"), $langs->transnoentitiesnoconv("BillsCustomers"), $langs->transnoentitiesnoconv("ListOfTemplates"));
		if (empty($conf->global->INVOICE_DISABLE_AUTOMATIC_RECURRING_INVOICE))
		{
		   $text.=' '.$langs->trans("ToCreateARecurringInvoiceGeneAuto", $langs->transnoentitiesnoconv('Module2300Name'));
		}
		print info_admin($text, 0, 0, 0).'<br>';
	}

	print '<form name="add" action="' . $_SERVER["PHP_SELF"] . '" method="POST">';
	print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
	print '<input type="hidden" name="action" value="add">';
	if ($soc->id > 0) print '<input type="hidden" name="socid" value="' . $soc->id . '">' . "\n";
	print '<input name="facnumber" type="hidden" value="provisoire">';
	print '<input name="ref_client" type="hidden" value="' . $ref_client . '">';
	print '<input name="ref_int" type="hidden" value="' . $ref_int . '">';
	print '<input type="hidden" name="origin" value="' . $origin . '">';
	print '<input type="hidden" name="originid" value="' . $originid . '">';
	if (!empty($currency_tx)) print '<input type="hidden" name="originmulticurrency_tx" value="' . $currency_tx . '">';

	dol_fiche_head('');

	print '<table class="border" width="100%">';

	// Ref
	print '<tr><td class="titlefieldcreate fieldrequired">' . $langs->trans('Ref') . '</td><td colspan="2">' . $langs->trans('Draft') . '</td></tr>';

	// Thirdparty
	print '<td class="fieldrequired">' . $langs->trans('Customer') . '</td>';
	if ($soc->id > 0 && ! GETPOST('fac_rec','alpha'))
	{
		print '<td colspan="2">';
		print $soc->getNomUrl(1);
		print '<input type="hidden" name="socid" value="' . $soc->id . '">';
		// Outstanding Bill
		$outstandingBills = $soc->get_OutstandingBill();
		print ' (' . $langs->trans('CurrentOutstandingBill') . ': ';
		print price($outstandingBills, '', $langs, 0, 0, -1, $conf->currency);
		if ($soc->outstanding_limit != '')
		{
			if ($outstandingBills > $soc->outstanding_limit) print img_warning($langs->trans("OutstandingBillReached"));
			print ' / ' . price($soc->outstanding_limit, '', $langs, 0, 0, -1, $conf->currency);
		}
		print ')';
		print '</td>';
	}
	else
	{
		print '<td colspan="2">';
		print $form->select_company($soc->id, 'socid', '(s.client = 1 OR s.client = 3) AND status=1', 'SelectThirdParty', 0, 0, null, 0, 'minwidth300');
		// Option to reload page to retrieve customer informations. Note, this clear other input
		if (!empty($conf->global->RELOAD_PAGE_ON_CUSTOMER_CHANGE))
		{
			print '<script type="text/javascript">
			$(document).ready(function() {
				$("#socid").change(function() {
					var socid = $(this).val();
			        var fac_rec = $(\'#fac_rec\').val();
					// reload page
        			window.location.href = "'.$_SERVER["PHP_SELF"].'?action=create&socid="+socid+"&fac_rec="+fac_rec;
				});
			});
			</script>';
		}
		print ' <a href="'.DOL_URL_ROOT.'/societe/card.php?action=create&client=3&fournisseur=0&backtopage='.urlencode($_SERVER["PHP_SELF"].'?action=create').'">'.$langs->trans("AddThirdParty").'</a>';
		print '</td>';
	}
	print '</tr>' . "\n";

	$exampletemplateinvoice=new FactureRec($db);

	// Overwrite value if creation of invoice is from a predefined invoice
	if (empty($origin) && empty($originid) && GETPOST('fac_rec','int') > 0)
	{
		$invoice_predefined = new FactureRec($db);
		$invoice_predefined->fetch(GETPOST('fac_rec','int'));

		$dateinvoice = $invoice_predefined->date_when;     // To use next gen date by default later
		if (empty($projectid)) $projectid = $invoice_predefined->fk_project;
		$cond_reglement_id = $invoice_predefined->cond_reglement_id;
		$mode_reglement_id = $invoice_predefined->mode_reglement_id;
		$fk_account = $invoice_predefined->fk_account;
		$note_public = $invoice_predefined->note_public;
		$note_private = $invoice_predefined->note_private;

		$sql = 'SELECT r.rowid, r.titre, r.total_ttc';
		$sql .= ' FROM ' . MAIN_DB_PREFIX . 'facture_rec as r';
		$sql .= ' WHERE r.fk_soc = ' . $invoice_predefined->socid;

		$resql = $db->query($sql);
		if ($resql)
		{
			$num = $db->num_rows($resql);
			$i = 0;

			if ($num > 0)
			{
				print '<tr><td>' . $langs->trans('CreateFromRepeatableInvoice') . '</td><td>';
				print '<select class="flat" id="fac_rec" name="fac_rec">';
				print '<option value="0" selected></option>';
				while ($i < $num)
				{
					$objp = $db->fetch_object($resql);
					print '<option value="' . $objp->rowid . '"';
					if (GETPOST('fac_rec') == $objp->rowid)
					{
						print ' selected';
						$exampletemplateinvoice->fetch(GETPOST('fac_rec'));
					}
					print '>' . $objp->titre . ' (' . price($objp->total_ttc) . ' ' . $langs->trans("TTC") . ')</option>';
					$i ++;
				}
				print '</select>';
				// Option to reload page to retrieve customer informations. Note, this clear other input
				if (!empty($conf->global->RELOAD_PAGE_ON_TEMPLATE_CHANGE))
				{
					print '<script type="text/javascript">
        			$(document).ready(function() {
        				$("#fac_rec").change(function() {
        					var fac_rec = $(this).val();
        			        var socid = $(\'#socid\').val();
        					// reload page
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

	// Type de facture
	$facids = $facturestatic->list_replacable_invoices($soc->id);
	if ($facids < 0) {
		dol_print_error($db, $facturestatic);
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

	// Show link for credit note
	$facids=$facturestatic->list_qualified_avoir_invoices($soc->id);
	if ($facids < 0)
	{
		dol_print_error($db,$facturestatic);
		exit;
	}
	$optionsav = "";
	$newinvoice_static = new Facture($db);
	foreach ($facids as $key => $valarray)
	{
		$newinvoice_static->id = $key;
		$newinvoice_static->ref = $valarray ['ref'];
		$newinvoice_static->statut = $valarray ['status'];
		$newinvoice_static->type = $valarray ['type'];
		$newinvoice_static->paye = $valarray ['paye'];

		$optionsav .= '<option value="' . $key . '"';
		if ($key == GETPOST('fac_avoir'))
			$optionsav .= ' selected';
		$optionsav .= '>';
		$optionsav .= $newinvoice_static->ref;
		$optionsav .= ' (' . $newinvoice_static->getLibStatut(1, $valarray ['paymentornot']) . ')';
		$optionsav .= '</option>';
	}

	print '<tr><td class="tdtop fieldrequired">' . $langs->trans('Type') . '</td><td colspan="2">';

	print '<div class="tagtable">' . "\n";

	// Standard invoice
	print '<div class="tagtr listofinvoicetype"><div class="tagtd listofinvoicetype">';
	$tmp='<input type="radio" id="radio_standard" name="type" value="0"' . (GETPOST('type') == 0 ? ' checked' : '') . '> ';
	$desc = $form->textwithpicto($tmp.$langs->trans("InvoiceStandardAsk"), $langs->transnoentities("InvoiceStandardDesc"), 1, 'help', '', 0, 3);
	print $desc;
	print '</div></div>';

	if ((empty($origin)) || ((($origin == 'propal') || ($origin == 'commande')) && (! empty($originid))))
	{
		// Deposit
		if (empty($conf->global->INVOICE_DISABLE_DEPOSIT))
   		{
			print '<div class="tagtr listofinvoicetype"><div class="tagtd listofinvoicetype">';
			$tmp='<input type="radio" id="radio_deposit" name="type" value="3"' . (GETPOST('type') == 3 ? ' checked' : '') . '> ';
			print '<script type="text/javascript" language="javascript">
    		jQuery(document).ready(function() {
    			jQuery("#typedeposit, #valuedeposit").click(function() {
    				jQuery("#radio_deposit").prop("checked", true);
    			});
    		});
    		</script>';

			$desc = $form->textwithpicto($tmp.$langs->trans("InvoiceDeposit"), $langs->transnoentities("InvoiceDepositDesc"), 1, 'help', '', 0, 3);
			print '<table class="nobordernopadding"><tr><td>';
			print $desc;
			print '</td>';
			if (($origin == 'propal') || ($origin == 'commande'))
			{
				print '<td class="nowrap" style="padding-left: 5px">';
				$arraylist = array('amount' => $langs->transnoentitiesnoconv('FixAmount'), 'variable' => $langs->transnoentitiesnoconv('VarAmountOneLine', $langs->transnoentitiesnoconv('Deposit')));
				print $form->selectarray('typedeposit', $arraylist, GETPOST('typedeposit'), 0, 0, 0, '', 1);
				print '</td>';
				print '<td class="nowrap" style="padding-left: 5px">' . $langs->trans('Value') . ':<input type="text" id="valuedeposit" name="valuedeposit" size="3" value="' . GETPOST('valuedeposit', 'int') . '"/>';
			}
			print '</td></tr></table>';

			print '</div></div>';
   		}
	}

	if ($socid > 0)
	{
		if (! empty($conf->global->INVOICE_USE_SITUATION))
		{
			// First situation invoice
			print '<div class="tagtr listofinvoicetype"><div class="tagtd listofinvoicetype">';
			$tmp='<input type="radio" name="type" value="5"' . (GETPOST('type') == 5 ? ' checked' : '') . '> ';
			$desc = $form->textwithpicto($tmp.$langs->trans("InvoiceFirstSituationAsk"), $langs->transnoentities("InvoiceFirstSituationDesc"), 1, 'help', '', 0, 3);
			print $desc;
			print '</div></div>';

			// Next situation invoice
			$opt = $form->selectSituationInvoices(GETPOST('originid'), $socid);
			print '<div class="tagtr listofinvoicetype"><div class="tagtd listofinvoicetype">';
			$tmp='<input type="radio" name="type" value="5"' . (GETPOST('type') == 5 && GETPOST('originid') ? ' checked' : '');
			if ($opt == ('<option value ="0" selected>' . $langs->trans('NoSituations') . '</option>') || (GETPOST('origin') && GETPOST('origin') != 'facture' && GETPOST('origin') != 'commande')) $tmp.=' disabled';
			$tmp.= '> ';
			$text = $tmp.$langs->trans("InvoiceSituationAsk") . ' ';
			$text .= '<select class="flat" id="situations" name="situations">';
			$text .= $opt;
			$text .= '</select>';
			$desc = $form->textwithpicto($text, $langs->transnoentities("InvoiceSituationDesc"), 1, 'help', '', 0, 3);
			print $desc;
			print '</div></div>';
		}

		// Replacement
		if (empty($conf->global->INVOICE_DISABLE_REPLACEMENT))
		{
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
		$text.= '('.$langs->trans("YouMustCreateInvoiceFromThird").') ';
		$desc = $form->textwithpicto($text, $langs->transnoentities("InvoiceReplacementDesc"), 1, 'help', '', 0, 3);
		print $desc;
		print '</div></div>';
	}

	if (empty($origin))
	{
		if ($socid > 0)
		{
			// Credit note
			if (empty($conf->global->INVOICE_DISABLE_CREDIT_NOTE))
			{
				print '<div class="tagtr listofinvoicetype"><div class="tagtd listofinvoicetype">';
				$tmp='<input type="radio" id="radio_creditnote" name="type" value="2"' . (GETPOST('type') == 2 ? ' checked' : '');
				if (! $optionsav) $tmp.=' disabled';
				$tmp.= '> ';
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
				$text = $tmp.$langs->transnoentities("InvoiceAvoirAsk") . ' ';
				// $text.='<input type="text" value="">';
				$text .= '<select class="flat valignmiddle" name="fac_avoir" id="fac_avoir"';
				if (! $optionsav)
					$text .= ' disabled';
				$text .= '>';
				if ($optionsav) {
					$text .= '<option value="-1"></option>';
					$text .= $optionsav;
				} else {
					$text .= '<option value="-1">' . $langs->trans("NoInvoiceToCorrect") . '</option>';
				}
				$text .= '</select>';
				$desc = $form->textwithpicto($text, $langs->transnoentities("InvoiceAvoirDesc"), 1, 'help', '', 0, 3);
				print $desc;

				print '<div id="credit_note_options" class="clearboth">';
				print '&nbsp;&nbsp;&nbsp; <input type="checkbox" name="invoiceAvoirWithLines" id="invoiceAvoirWithLines" value="1" onclick="$(\'#credit_note_options input[type=checkbox]\').not(this).prop(\'checked\', false);" '.(GETPOST('invoiceAvoirWithLines','int')>0 ? 'checked':'').' /> <label for="invoiceAvoirWithLines">'.$langs->trans('invoiceAvoirWithLines')."</label>";
				print '<br>&nbsp;&nbsp;&nbsp; <input type="checkbox" name="invoiceAvoirWithPaymentRestAmount" id="invoiceAvoirWithPaymentRestAmount" value="1" onclick="$(\'#credit_note_options input[type=checkbox]\').not(this).prop(\'checked\', false);" '.(GETPOST('invoiceAvoirWithPaymentRestAmount','int')>0 ? 'checked':'').' /> <label for="invoiceAvoirWithPaymentRestAmount">'.$langs->trans('invoiceAvoirWithPaymentRestAmount')."</label>";
				print '</div>';

    			print '</div></div>';
    		}
		}
		else
		{
			print '<div class="tagtr listofinvoicetype"><div class="tagtd listofinvoicetype">';
			if (empty($conf->global->INVOICE_CREDIT_NOTE_STANDALONE)) $tmp='<input type="radio" name="type" id="radio_creditnote" value="0" disabled> ';
			else $tmp='<input type="radio" name="type" id="radio_creditnote" value="2" > ';
			$text = $tmp.$langs->trans("InvoiceAvoir") . ' ';
			$text.= '('.$langs->trans("YouMustCreateInvoiceFromThird").') ';
			$desc = $form->textwithpicto($text, $langs->transnoentities("InvoiceAvoirDesc"), 1, 'help', '', 0, 3);
			print $desc;
			print '</div></div>' . "\n";
		}
	}

	// Template invoice
	print '<div class="tagtr listofinvoicetype"><div class="tagtd listofinvoicetype">';
	$tmp='<input type="radio" name="type" id="radio_template" value="0" disabled> ';
	$text = $tmp.$langs->trans("RepeatableInvoice") . ' ';
	//$text.= '('.$langs->trans("YouMustCreateStandardInvoiceFirst").') ';
	$desc = $form->textwithpicto($text, $langs->transnoentities("YouMustCreateStandardInvoiceFirstDesc"), 1, 'help', '', 0, 3);
	print $desc;
	print '</div></div>';

	print '</div>';

	print '</td></tr>';

	if ($socid > 0)
	{
		// Discounts for third party
		print '<tr><td>' . $langs->trans('Discounts') . '</td><td colspan="2">';

		$thirdparty = $soc;
		$discount_type = 0;
		$backtopage = urlencode($_SERVER["PHP_SELF"] . '?socid=' . $thirdparty->id . '&action=' . $action . '&origin=' . GETPOST('origin') . '&originid=' . GETPOST('originid'));
		include DOL_DOCUMENT_ROOT.'/core/tpl/object_discounts.tpl.php';

		print '</td></tr>';
	}

	$datefacture = dol_mktime(12, 0, 0, $_POST['remonth'], $_POST['reday'], $_POST['reyear']);

	// Date invoice
	print '<tr><td class="fieldrequired">' . $langs->trans('DateInvoice') . '</td><td colspan="2">';
	print $form->select_date($datefacture?$datefacture:$dateinvoice, '', '', '', '', "add", 1, 1, 1);
	print '</td></tr>';

	// Date point of tax
	if (! empty($conf->global->INVOICE_POINTOFTAX_DATE))
	{
		print '<tr><td class="fieldrequired">' . $langs->trans('DatePointOfTax') . '</td><td colspan="2">';
		$date_pointoftax = dol_mktime(12, 0, 0, $_POST['date_pointoftaxmonth'], $_POST['date_pointoftaxday'], $_POST['date_pointoftaxyear']);
		print $form->select_date($date_pointoftax?$date_pointoftax:-1, 'date_pointoftax', '', '', '', "add", 1, 1, 1);
		print '</td></tr>';
	}

	// Payment term
	print '<tr><td class="nowrap fieldrequired">' . $langs->trans('PaymentConditionsShort') . '</td><td colspan="2">';
	$form->select_conditions_paiements(isset($_POST['cond_reglement_id']) ? $_POST['cond_reglement_id'] : $cond_reglement_id, 'cond_reglement_id');
	print '</td></tr>';

	// Payment mode
	print '<tr><td>' . $langs->trans('PaymentMode') . '</td><td colspan="2">';
	$form->select_types_paiements(isset($_POST['mode_reglement_id']) ? $_POST['mode_reglement_id'] : $mode_reglement_id, 'mode_reglement_id', 'CRDT');
	print '</td></tr>';

	// Bank Account
	if (isset($_POST['fk_account'])) {
		$fk_account = $_POST['fk_account'];
	}

	print '<tr><td>' . $langs->trans('BankAccount') . '</td><td colspan="2">';
	$form->select_comptes($fk_account, 'fk_account', 0, '', 1);
	print '</td></tr>';

	// Project
	if (! empty($conf->projet->enabled))
	{
		$langs->load('projects');
		print '<tr><td>' . $langs->trans('Project') . '</td><td colspan="2">';
		$numprojet = $formproject->select_projects(($socid > 0 ? $socid : -1), $projectid, 'projectid', 0, 0, 1, 1);
		print ' &nbsp; <a href="'.DOL_URL_ROOT.'/projet/card.php?socid=' . $soc->id . '&action=create&status=1&backtopage='.urlencode($_SERVER["PHP_SELF"].'?action=create&socid='.$soc->id.($fac_rec?'&fac_rec='.$fac_rec:'')).'">' . $langs->trans("AddProject") . '</a>';
		print '</td></tr>';
	}

	// Incoterms
	if (!empty($conf->incoterm->enabled))
	{
		print '<tr>';
		print '<td><label for="incoterm_id">'.$form->textwithpicto($langs->trans("IncotermLabel"), $objectsrc->libelle_incoterms, 1).'</label></td>';
		print '<td colspan="2" class="maxwidthonsmartphone">';
		$incoterm_id = GETPOST('incoterm_id');
		$incoterm_location = GETPOST('location_incoterms');
		if (empty($incoterm_id))
		{
			$incoterm_id = (!empty($objectsrc->fk_incoterms) ? $objectsrc->fk_incoterms : $soc->fk_incoterms);
			$incoterm_location = (!empty($objectsrc->location_incoterms) ? $objectsrc->location_incoterms : $soc->location_incoterms);
		}
		print $form->select_incoterms($incoterm_id, $incoterm_location);
		print '</td></tr>';
	}

	// Other attributes
	$parameters = array('objectsrc' => $objectsrc,'colspan' => ' colspan="2"', 'cols'=>2);
	$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	if (empty($reshook)) {
		print $object->showOptionals($extrafields, 'edit');
	}

	// Template to use by default
	print '<tr><td>' . $langs->trans('Model') . '</td>';
	print '<td colspan="2">';
	include_once DOL_DOCUMENT_ROOT . '/core/modules/facture/modules_facture.php';
	$liste = ModelePDFFactures::liste_modeles($db);
	print $form->selectarray('model', $liste, $conf->global->FACTURE_ADDON_PDF);
	print "</td></tr>";

	// Multicurrency
	if (! empty($conf->multicurrency->enabled))
	{
		print '<tr>';
		print '<td>'.fieldLabel('Currency','multicurrency_code').'</td>';
		print '<td colspan="2" class="maxwidthonsmartphone">';
		print $form->selectMultiCurrency($currency_code, 'multicurrency_code');
		print '</td></tr>';
	}

	// Help of substitution key
	$htmltext='';
	if (GETPOST('fac_rec','int') > 0)
	{
		$dateexample=($datefacture ? $datefacture : $dateinvoice);
		if (empty($dateexample)) $dateexample=dol_now();
		$substitutionarray=array(
			'__TOTAL_HT__' => $langs->trans("AmountHT").' ('.$langs->trans("Example").': '.price($exampletemplateinvoice->total_ht).')',
			'__TOTAL_TTC__' =>  $langs->trans("AmountTTC").' ('.$langs->trans("Example").': '.price($exampletemplateinvoice->total_ttc).')',
			'__INVOICE_PREVIOUS_MONTH__' => $langs->trans("PreviousMonthOfInvoice").' ('.$langs->trans("Example").': '.dol_print_date(dol_time_plus_duree($dateexample, -1, 'm'),'%m').')',
			'__INVOICE_MONTH__' =>  $langs->trans("MonthOfInvoice").' ('.$langs->trans("Example").': '.dol_print_date($dateexample,'%m').')',
			'__INVOICE_NEXT_MONTH__' => $langs->trans("NextMonthOfInvoice").' ('.$langs->trans("Example").': '.dol_print_date(dol_time_plus_duree($dateexample, 1, 'm'),'%m').')',
			'__INVOICE_PREVIOUS_MONTH_TEXT__' => $langs->trans("TextPreviousMonthOfInvoice").' ('.$langs->trans("Example").': '.dol_print_date(dol_time_plus_duree($dateexample, -1, 'm'),'%B').')',
			'__INVOICE_MONTH_TEXT__' =>  $langs->trans("TextMonthOfInvoice").' ('.$langs->trans("Example").': '.dol_print_date($dateexample,'%B').')',
			'__INVOICE_NEXT_MONTH_TEXT__' => $langs->trans("TextNextMonthOfInvoice").' ('.$langs->trans("Example").': '.dol_print_date(dol_time_plus_duree($dateexample, 1, 'm'), '%B').')',
			'__INVOICE_PREVIOUS_YEAR__' => $langs->trans("YearOfInvoice").' ('.$langs->trans("Example").': '.dol_print_date(dol_time_plus_duree($dateexample, -1, 'y'),'%Y').')',
			'__INVOICE_YEAR__' =>  $langs->trans("PreviousYearOfInvoice").' ('.$langs->trans("Example").': '.dol_print_date($dateexample,'%Y').')',
			'__INVOICE_NEXT_YEAR__' => $langs->trans("NextYearOfInvoice").' ('.$langs->trans("Example").': '.dol_print_date(dol_time_plus_duree($dateexample, 1, 'y'),'%Y').')'
		);

		$htmltext = '<i>'.$langs->trans("FollowingConstantsWillBeSubstituted").':<br>';
		foreach($substitutionarray as $key => $val)
		{
			$htmltext.=$key.' = '.$langs->trans($val).'<br>';
		}
		$htmltext.='</i>';
	}

	// Public note
	print '<tr>';
	print '<td class="tdtop">';
	print $form->textwithpicto($langs->trans('NotePublic'), $htmltext);
	print '</td>';
	print '<td valign="top" colspan="2">';
	$doleditor = new DolEditor('note_public', $note_public, '', 80, 'dolibarr_notes', 'In', 0, false, true, ROWS_3, '90%');
	print $doleditor->Create(1);

	// Private note
	if (empty($user->societe_id))
	{
		print '<tr>';
		print '<td class="tdtop">';
		print $form->textwithpicto($langs->trans('NotePrivate'), $htmltext);
		print '</td>';
		print '<td valign="top" colspan="2">';
		$doleditor = new DolEditor('note_private', $note_private, '', 80, 'dolibarr_notes', 'In', 0, false, true, ROWS_3, '90%');
		print $doleditor->Create(1);
		// print '<textarea name="note_private" wrap="soft" cols="70" rows="'.ROWS_3.'">'.$note_private.'.</textarea>
		print '</td></tr>';
	}

	// Lines from source
	if (! empty($origin) && ! empty($originid) && is_object($objectsrc))
	{
		// TODO for compatibility
		if ($origin == 'contrat') {
			// Calcul contrat->price (HT), contrat->total (TTC), contrat->tva
			$objectsrc->remise_absolue = $remise_absolue;
			$objectsrc->remise_percent = $remise_percent;
			$objectsrc->update_price(1, - 1, 1);
		}

		print "\n<!-- " . $classname . " info -->";
		print "\n";
		print '<input type="hidden" name="amount"         value="' . $objectsrc->total_ht . '">' . "\n";
		print '<input type="hidden" name="total"          value="' . $objectsrc->total_ttc . '">' . "\n";
		print '<input type="hidden" name="tva"            value="' . $objectsrc->total_tva . '">' . "\n";
		print '<input type="hidden" name="origin"         value="' . $objectsrc->element . '">';
		print '<input type="hidden" name="originid"       value="' . $objectsrc->id . '">';

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

		print '<tr><td>' . $langs->trans($newclassname) . '</td><td colspan="2">' . $objectsrc->getNomUrl(1);
		// We check if Origin document (id and type is known) has already at least one invoice attached to it
		$objectsrc->fetchObjectLinked($originid,$origin,'','facture');
		if (is_array($objectsrc->linkedObjects['facture']) && count($objectsrc->linkedObjects['facture']) >= 1)
		{
			setEventMessages('WarningBillExist', null, 'warnings');
			echo ' ('.$langs->trans('LatestRelatedBill').end($objectsrc->linkedObjects['facture'])->getNomUrl(1).')';
		}
		echo '</td></tr>';
		print '<tr><td>' . $langs->trans('TotalHT') . '</td><td colspan="2">' . price($objectsrc->total_ht) . '</td></tr>';
		print '<tr><td>' . $langs->trans('TotalVAT') . '</td><td colspan="2">' . price($objectsrc->total_tva) . "</td></tr>";
		if ($mysoc->localtax1_assuj == "1" || $objectsrc->total_localtax1 != 0) 		// Localtax1
		{
			print '<tr><td>' . $langs->transcountry("AmountLT1", $mysoc->country_code) . '</td><td colspan="2">' . price($objectsrc->total_localtax1) . "</td></tr>";
		}

		if ($mysoc->localtax2_assuj == "1" || $objectsrc->total_localtax2 != 0) 		// Localtax2
		{
			print '<tr><td>' . $langs->transcountry("AmountLT2", $mysoc->country_code) . '</td><td colspan="2">' . price($objectsrc->total_localtax2) . "</td></tr>";
		}
		print '<tr><td>' . $langs->trans('TotalTTC') . '</td><td colspan="2">' . price($objectsrc->total_ttc) . "</td></tr>";

		if (!empty($conf->multicurrency->enabled))
		{
			print '<tr><td>' . $langs->trans('MulticurrencyAmountHT') . '</td><td colspan="2">' . price($objectsrc->multicurrency_total_ht) . '</td></tr>';
			print '<tr><td>' . $langs->trans('MulticurrencyAmountVAT') . '</td><td colspan="2">' . price($objectsrc->multicurrency_total_tva) . "</td></tr>";
			print '<tr><td>' . $langs->trans('MulticurrencyAmountTTC') . '</td><td colspan="2">' . price($objectsrc->multicurrency_total_ttc) . "</td></tr>";
		}
	}

	print "</table>\n";

	dol_fiche_end();

	// Button "Create Draft"
	print '<div class="center">';
	print '<input type="submit" class="button" name="bouton" value="' . $langs->trans('CreateDraft') . '">';
	print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	print '<input type="button" class="button" value="' . $langs->trans("Cancel") . '" onClick="javascript:history.go(-1)">';
	print '</div>';

	print "</form>\n";

	// Show origin lines
	if (! empty($origin) && ! empty($originid) && is_object($objectsrc)) {
		print '<br>';

		$title = $langs->trans('ProductsAndServices');
		print load_fiche_titre($title);

		print '<table class="noborder" width="100%">';

		$objectsrc->printOriginLinesList();

		print '</table>';
	}

	print '<br>';
}
else if ($id > 0 || ! empty($ref))
{
	/*
	 * Show object in view mode
	 */

	$result = $object->fetch($id, $ref);
	if ($result <= 0) {
		dol_print_error($db, $object->error);
		exit();
	}

	// fetch optionals attributes and labels
	$extralabels = $extrafields->fetch_name_optionals_label($object->table_element);

	if ($user->societe_id > 0 && $user->societe_id != $object->socid)
		accessforbidden('', 0);

	$result = $object->fetch_thirdparty();

	$soc = new Societe($db);
	$result=$soc->fetch($object->socid);
	if ($result < 0) dol_print_error($db);
	$selleruserevenustamp = $mysoc->useRevenueStamp();

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

	if ($object->paye)
	{
		$resteapayer = 0;
	}
	$resteapayeraffiche = $resteapayer;

	if (! empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS)) {	// Never use this
		$filterabsolutediscount = "fk_facture_source IS NULL"; // If we want deposit to be substracted to payments only and not to total of final invoice
		$filtercreditnote = "fk_facture_source IS NOT NULL"; // If we want deposit to be substracted to payments only and not to total of final invoice
	} else {
		$filterabsolutediscount = "fk_facture_source IS NULL OR (description LIKE '(DEPOSIT)%' AND description NOT LIKE '(EXCESS RECEIVED)%')";
		$filtercreditnote = "fk_facture_source IS NOT NULL AND (description NOT LIKE '(DEPOSIT)%' OR description LIKE '(EXCESS RECEIVED)%')";
	}

	$absolute_discount = $soc->getAvailableDiscounts('', $filterabsolutediscount);
	$absolute_creditnote = $soc->getAvailableDiscounts('', $filtercreditnote);
	$absolute_discount = price2num($absolute_discount, 'MT');
	$absolute_creditnote = price2num($absolute_creditnote, 'MT');

	$author = new User($db);
	if ($object->user_author) {
		$author->fetch($object->user_author);
	}

	$objectidnext = $object->getIdReplacingInvoice();

	$head = facture_prepare_head($object);

	dol_fiche_head($head, 'compta', $langs->trans('InvoiceCustomer'), -1, 'bill');

	$formconfirm = '';

	// Confirmation de la conversion de l'avoir en reduc
	if ($action == 'converttoreduc') {
		if($object->type == Facture::TYPE_STANDARD) $type_fac = 'ExcessReceived';
		elseif($object->type == Facture::TYPE_CREDIT_NOTE) $type_fac = 'CreditNote';
		elseif($object->type == Facture::TYPE_DEPOSIT) $type_fac = 'Deposit';
		$text = $langs->trans('ConfirmConvertToReduc', strtolower($langs->transnoentities($type_fac)));
		$formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?facid=' . $object->id, $langs->trans('ConvertToReduc'), $text, 'confirm_converttoreduc', '', "yes", 2);
	}

	// Confirmation to delete invoice
	if ($action == 'delete') {
		$text = $langs->trans('ConfirmDeleteBill', $object->ref);
		$formquestion = array();

		$qualified_for_stock_change = 0;
		if (empty($conf->global->STOCK_SUPPORTS_SERVICES)) {
			$qualified_for_stock_change = $object->hasProductsOrServices(2);
		} else {
			$qualified_for_stock_change = $object->hasProductsOrServices(1);
		}

		if ($object->type != Facture::TYPE_DEPOSIT && ! empty($conf->global->STOCK_CALCULATE_ON_BILL) && $qualified_for_stock_change && $object->statut >= 1)
		{
			$langs->load("stocks");
			require_once DOL_DOCUMENT_ROOT . '/product/class/html.formproduct.class.php';
			$formproduct = new FormProduct($db);
			$label = $object->type == Facture::TYPE_CREDIT_NOTE ? $langs->trans("SelectWarehouseForStockDecrease") : $langs->trans("SelectWarehouseForStockIncrease");
			$forcecombo=0;
			if ($conf->browser->name == 'ie') $forcecombo = 1;	// There is a bug in IE10 that make combo inside popup crazy
			$formquestion = array(
				// 'text' => $langs->trans("ConfirmClone"),
				// array('type' => 'checkbox', 'name' => 'clone_content', 'label' => $langs->trans("CloneMainAttributes"), 'value' => 1),
				// array('type' => 'checkbox', 'name' => 'update_prices', 'label' => $langs->trans("PuttingPricesUpToDate"), 'value' => 1),
				array('type' => 'other','name' => 'idwarehouse','label' => $label,'value' => $formproduct->selectWarehouses(GETPOST('idwarehouse')?GETPOST('idwarehouse'):'ifone', 'idwarehouse', '', 1, 0, 0, $langs->trans("NoStockAction"), 0, $forcecombo))
			);
			$formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?facid=' . $object->id, $langs->trans('DeleteBill'), $text, 'confirm_delete', $formquestion, "yes", 1);
		} else {
			$formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?facid=' . $object->id, $langs->trans('DeleteBill'), $text, 'confirm_delete', '', 'no', 1);
		}
	}

	// Confirmation to remove invoice from cycle
	if ($action == 'situationout') {
	    $text = $langs->trans('ConfirmRemoveSituationFromCycle', $object->ref);
	    $label = $langs->trans("ConfirmOuting");
	    $formquestion = array();
	    // remove situation from cycle
	    if ($object->statut == Facture::STATUS_VALIDATED
	        && $user->rights->facture->creer
	        && !$objectidnext
	        && $object->is_last_in_cycle()
	        && ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->facture->creer))
	            || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->facture->invoice_advance->unvalidate)))
	        )
	    {
	        $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?facid=' . $object->id, $label, $text, 'confirm_situationout', $formquestion, "yes", 1);
		}
	}

	// Confirmation of validation
	if ($action == 'valid')
	{
		// we check object has a draft number
		$objectref = substr($object->ref, 1, 4);
		if ($objectref == 'PROV') {
			$savdate = $object->date;
			if (! empty($conf->global->FAC_FORCE_DATE_VALIDATION)) {
				$object->date = dol_now();
				$object->date_lim_reglement = $object->calculate_date_lim_reglement();
			}
			$numref = $object->getNextNumRef($soc);
			// $object->date=$savdate;
		} else {
			$numref = $object->ref;
		}

		$text = $langs->trans('ConfirmValidateBill', $numref);
		if (! empty($conf->notification->enabled)) {
			require_once DOL_DOCUMENT_ROOT . '/core/class/notify.class.php';
			$notify = new Notify($db);
			$text .= '<br>';
			$text .= $notify->confirmMessage('BILL_VALIDATE', $object->socid, $object);
		}
		$formquestion = array();

		$qualified_for_stock_change = 0;
		if (empty($conf->global->STOCK_SUPPORTS_SERVICES)) {
			$qualified_for_stock_change = $object->hasProductsOrServices(2);
		} else {
			$qualified_for_stock_change = $object->hasProductsOrServices(1);
		}

		if ($object->type != Facture::TYPE_DEPOSIT && ! empty($conf->global->STOCK_CALCULATE_ON_BILL) && $qualified_for_stock_change)
		{
			$langs->load("stocks");
			require_once DOL_DOCUMENT_ROOT . '/product/class/html.formproduct.class.php';
			require_once DOL_DOCUMENT_ROOT . '/product/stock/class/entrepot.class.php';
			$formproduct = new FormProduct($db);
			$warehouse = new Entrepot($db);
			$warehouse_array = $warehouse->list_array();
			if (count($warehouse_array) == 1) {
				$label = $object->type == Facture::TYPE_CREDIT_NOTE ? $langs->trans("WarehouseForStockIncrease", current($warehouse_array)) : $langs->trans("WarehouseForStockDecrease", current($warehouse_array));
				$value = '<input type="hidden" id="idwarehouse" name="idwarehouse" value="' . key($warehouse_array) . '">';
			} else {
				$label = $object->type == Facture::TYPE_CREDIT_NOTE ? $langs->trans("SelectWarehouseForStockIncrease") : $langs->trans("SelectWarehouseForStockDecrease");
				$value = $formproduct->selectWarehouses(GETPOST('idwarehouse')?GETPOST('idwarehouse'):'ifone', 'idwarehouse', '', 1);
			}
			$formquestion = array(
								// 'text' => $langs->trans("ConfirmClone"),
								// array('type' => 'checkbox', 'name' => 'clone_content', 'label' => $langs->trans("CloneMainAttributes"), 'value' =>
								// 1),
								// array('type' => 'checkbox', 'name' => 'update_prices', 'label' => $langs->trans("PuttingPricesUpToDate"), 'value'
								// => 1),
								array('type' => 'other','name' => 'idwarehouse','label' => $label,'value' => $value));
		}
		if ($object->type != Facture::TYPE_CREDIT_NOTE && $object->total_ttc < 0) 		// Can happen only if $conf->global->FACTURE_ENABLE_NEGATIVE is on
		{
			$text .= '<br>' . img_warning() . ' ' . $langs->trans("ErrorInvoiceOfThisTypeMustBePositive");
		}
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?facid=' . $object->id, $langs->trans('ValidateBill'), $text, 'confirm_valid', $formquestion, (($object->type != Facture::TYPE_CREDIT_NOTE && $object->total_ttc < 0) ? "no" : "yes"), 2);
	}

	// Confirm back to draft status
	if ($action == 'modif') {
		$text = $langs->trans('ConfirmUnvalidateBill', $object->ref);
		$formquestion = array();

		$qualified_for_stock_change = 0;
		if (empty($conf->global->STOCK_SUPPORTS_SERVICES)) {
			$qualified_for_stock_change = $object->hasProductsOrServices(2);
		} else {
			$qualified_for_stock_change = $object->hasProductsOrServices(1);
		}
		if ($object->type != Facture::TYPE_DEPOSIT && ! empty($conf->global->STOCK_CALCULATE_ON_BILL) && $qualified_for_stock_change) {
			$langs->load("stocks");
			require_once DOL_DOCUMENT_ROOT . '/product/class/html.formproduct.class.php';
			require_once DOL_DOCUMENT_ROOT . '/product/stock/class/entrepot.class.php';
			$formproduct = new FormProduct($db);
			$warehouse = new Entrepot($db);
			$warehouse_array = $warehouse->list_array();
			if (count($warehouse_array) == 1) {
				$label = $object->type == Facture::TYPE_CREDIT_NOTE ? $langs->trans("WarehouseForStockDecrease", current($warehouse_array)) : $langs->trans("WarehouseForStockIncrease", current($warehouse_array));
				$value = '<input type="hidden" id="idwarehouse" name="idwarehouse" value="' . key($warehouse_array) . '">';
			} else {
				$label = $object->type == Facture::TYPE_CREDIT_NOTE ? $langs->trans("SelectWarehouseForStockDecrease") : $langs->trans("SelectWarehouseForStockIncrease");
				$value = $formproduct->selectWarehouses(GETPOST('idwarehouse')?GETPOST('idwarehouse'):'ifone', 'idwarehouse', '', 1);
			}
			$formquestion = array(
								// 'text' => $langs->trans("ConfirmClone"),
								// array('type' => 'checkbox', 'name' => 'clone_content', 'label' => $langs->trans("CloneMainAttributes"), 'value' =>
								// 1),
								// array('type' => 'checkbox', 'name' => 'update_prices', 'label' => $langs->trans("PuttingPricesUpToDate"), 'value'
								// => 1),
								array('type' => 'other','name' => 'idwarehouse','label' => $label,'value' => $value));
		}

		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?facid=' . $object->id, $langs->trans('UnvalidateBill'), $text, 'confirm_modif', $formquestion, "yes", 1);
	}

	// Confirmation du classement paye
	if ($action == 'paid' && $resteapayer <= 0) {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?facid=' . $object->id, $langs->trans('ClassifyPaid'), $langs->trans('ConfirmClassifyPaidBill', $object->ref), 'confirm_paid', '', "yes", 1);
	}
	if ($action == 'paid' && $resteapayer > 0) {
		// Code
		$i = 0;
		$close [$i]['code'] = 'discount_vat';	// escompte
		$i ++;
		$close [$i]['code'] = 'badcustomer';
		$i ++;
		// Help
		$i = 0;
		$close [$i]['label'] = $langs->trans("HelpEscompte") . '<br><br>' . $langs->trans("ConfirmClassifyPaidPartiallyReasonDiscountVatDesc");
		$i ++;
		$close [$i]['label'] = $langs->trans("ConfirmClassifyPaidPartiallyReasonBadCustomerDesc");
		$i ++;
		// Texte
		$i = 0;
		$close [$i]['reason'] = $form->textwithpicto($langs->transnoentities("ConfirmClassifyPaidPartiallyReasonDiscount", $resteapayer, $langs->trans("Currency" . $conf->currency)), $close[$i]['label'], 1);
		$i ++;
		$close [$i]['reason'] = $form->textwithpicto($langs->transnoentities("ConfirmClassifyPaidPartiallyReasonBadCustomer", $resteapayer, $langs->trans("Currency" . $conf->currency)), $close[$i]['label'], 1);
		$i ++;
		// arrayreasons[code]=reason
		foreach ($close as $key => $val) {
			$arrayreasons[$close [$key]['code']] = $close[$key]['reason'];
		}

		// Cree un tableau formulaire
		$formquestion = array('text' => $langs->trans("ConfirmClassifyPaidPartiallyQuestion"),array('type' => 'radio','name' => 'close_code','label' => $langs->trans("Reason"),'values' => $arrayreasons),array('type' => 'text','name' => 'close_note','label' => $langs->trans("Comment"),'value' => '','morecss' => 'minwidth300'));
		// Paiement incomplet. On demande si motif = escompte ou autre
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?facid=' . $object->id, $langs->trans('ClassifyPaid'), $langs->trans('ConfirmClassifyPaidPartially', $object->ref), 'confirm_paid_partially', $formquestion, "yes", 1, 310);
	}

	// Confirmation du classement abandonne
	if ($action == 'canceled') {
		// S'il y a une facture de remplacement pas encore validee (etat brouillon),
		// on ne permet pas de classer abandonner la facture.
		if ($objectidnext) {
			$facturereplacement = new Facture($db);
			$facturereplacement->fetch($objectidnext);
			$statusreplacement = $facturereplacement->statut;
		}
		if ($objectidnext && $statusreplacement == 0) {
			print '<div class="error">' . $langs->trans("ErrorCantCancelIfReplacementInvoiceNotValidated") . '</div>';
		} else {
			// Code
			$close [1] ['code'] = 'badcustomer';
			$close [2] ['code'] = 'abandon';
			// Help
			$close [1] ['label'] = $langs->trans("ConfirmClassifyPaidPartiallyReasonBadCustomerDesc");
			$close [2] ['label'] = $langs->trans("ConfirmClassifyAbandonReasonOtherDesc");
			// Texte
			$close [1] ['reason'] = $form->textwithpicto($langs->transnoentities("ConfirmClassifyPaidPartiallyReasonBadCustomer", $object->ref), $close [1] ['label'], 1);
			$close [2] ['reason'] = $form->textwithpicto($langs->transnoentities("ConfirmClassifyAbandonReasonOther"), $close [2] ['label'], 1);
			// arrayreasons
			$arrayreasons [$close [1] ['code']] = $close [1] ['reason'];
			$arrayreasons [$close [2] ['code']] = $close [2] ['reason'];

			// Cree un tableau formulaire
			$formquestion = array('text' => $langs->trans("ConfirmCancelBillQuestion"),array('type' => 'radio','name' => 'close_code','label' => $langs->trans("Reason"),'values' => $arrayreasons),array('type' => 'text','name' => 'close_note','label' => $langs->trans("Comment"),'value' => '','morecss' => 'minwidth300'));

			$formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?facid=' . $object->id, $langs->trans('CancelBill'), $langs->trans('ConfirmCancelBill', $object->ref), 'confirm_canceled', $formquestion, "yes", 1, 250);
		}
	}

	if ($action == 'deletepaiement')
	{
		$payment_id = GETPOST('paiement_id');
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&paiement_id='.$payment_id, $langs->trans('DeletePayment'), $langs->trans('ConfirmDeletePayment'), 'confirm_delete_paiement', '', 'no', 1);

	}

	// Confirmation de la suppression d'une ligne produit
	if ($action == 'ask_deleteline') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?facid=' . $object->id . '&lineid=' . $lineid, $langs->trans('DeleteProductLine'), $langs->trans('ConfirmDeleteProductLine'), 'confirm_deleteline', '', 'no', 1);
	}

	// Clone confirmation
	if ($action == 'clone')
	{
		// Create an array for form
		$formquestion = array(
							// 'text' => $langs->trans("ConfirmClone"),
							// array('type' => 'checkbox', 'name' => 'clone_content', 'label' => $langs->trans("CloneMainAttributes"), 'value' => 1)
							array('type' => 'other','name' => 'socid','label' => $langs->trans("SelectThirdParty"),'value' => $form->select_company($object->socid, 'socid', '(s.client=1 OR s.client=2 OR s.client=3)', 1)));
		// Paiement incomplet. On demande si motif = escompte ou autre
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?facid=' . $object->id, $langs->trans('CloneInvoice'), $langs->trans('ConfirmCloneInvoice', $object->ref), 'confirm_clone', $formquestion, 'yes', 1);
	}

	if (! $formconfirm)
	{
		$parameters = array('lineid' => $lineid);
		$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		if (empty($reshook)) $formconfirm.=$hookmanager->resPrint;
		elseif ($reshook > 0) $formconfirm=$hookmanager->resPrint;
	}

	// Print form confirm
	print $formconfirm;

	// Invoice content

	$linkback = '<a href="' . DOL_URL_ROOT . '/compta/facture/list.php?restore_lastsearch_values=1' . (! empty($socid) ? '&socid=' . $socid : '') . '">' . $langs->trans("BackToList") . '</a>';

	$morehtmlref='<div class="refidno">';
	// Ref customer
	$morehtmlref.=$form->editfieldkey("RefCustomer", 'ref_client', $object->ref_client, $object, $user->rights->facture->creer, 'string', '', 0, 1);
	$morehtmlref.=$form->editfieldval("RefCustomer", 'ref_client', $object->ref_client, $object, $user->rights->facture->creer, 'string', '', null, null, '', 1);
	// Thirdparty
	$morehtmlref.='<br>'.$langs->trans('ThirdParty') . ' : ' . $object->thirdparty->getNomUrl(1,'customer');
	if (empty($conf->global->MAIN_DISABLE_OTHER_LINK) && $object->thirdparty->id > 0) $morehtmlref.=' (<a href="'.DOL_URL_ROOT.'/compta/facture/list.php?socid='.$object->thirdparty->id.'&search_societe='.urlencode($object->thirdparty->name).'">'.$langs->trans("OtherBills").'</a>)';
	// Project
	if (! empty($conf->projet->enabled))
	{
		$langs->load("projects");
		$morehtmlref.='<br>'.$langs->trans('Project') . ' ';
		if ($user->rights->facture->creer)
		{
			if ($action != 'classify')
				$morehtmlref.='<a href="' . $_SERVER['PHP_SELF'] . '?action=classify&amp;id=' . $object->id . '">' . img_edit($langs->transnoentitiesnoconv('SetProject')) . '</a> : ';
				if ($action == 'classify') {
					//$morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'projectid', 0, 0, 1, 1);
					$morehtmlref.='<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
					$morehtmlref.='<input type="hidden" name="action" value="classin">';
					$morehtmlref.='<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
					$morehtmlref.=$formproject->select_projects($object->socid, $object->fk_project, 'projectid', $maxlength, 0, 1, 0, 1, 0, 0, '', 1);
					$morehtmlref.='<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
					$morehtmlref.='</form>';
				} else {
					$morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'none', 0, 0, 0, 1);
				}
		} else {
			if (! empty($object->fk_project)) {
				$proj = new Project($db);
				$proj->fetch($object->fk_project);
				$morehtmlref.='<a href="'.DOL_URL_ROOT.'/projet/card.php?id=' . $object->fk_project . '" title="' . $langs->trans('ShowProject') . '">';
				$morehtmlref.=$proj->ref;
				$morehtmlref.='</a>';
			} else {
				$morehtmlref.='';
			}
		}
	}
	$morehtmlref.='</div>';

	$object->totalpaye = $totalpaye;   // To give a chance to dol_banner_tab to use already paid amount to show correct status

	dol_banner_tab($object, 'ref', $linkback, 1, 'facnumber', 'ref', $morehtmlref, '', 0, '', '');

	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';

	print '<table class="border" width="100%">';

	// Type
	print '<tr><td class="titlefield">' . $langs->trans('Type') . '</td><td>';
	print $object->getLibType();
	if ($object->type == Facture::TYPE_REPLACEMENT) {
		$facreplaced = new Facture($db);
		$facreplaced->fetch($object->fk_facture_source);
		print ' (' . $langs->transnoentities("ReplaceInvoice", $facreplaced->getNomUrl(1)) . ')';
	}
	if ($object->type == Facture::TYPE_CREDIT_NOTE && !empty($object->fk_facture_source)) {
		$facusing = new Facture($db);
		$facusing->fetch($object->fk_facture_source);
		print ' (' . $langs->transnoentities("CorrectInvoice", $facusing->getNomUrl(1)) . ')';
	}

	$facidavoir = $object->getListIdAvoirFromInvoice();
	if (count($facidavoir) > 0) {
		print ' (' . $langs->transnoentities("InvoiceHasAvoir");
		$i = 0;
		foreach ($facidavoir as $id) {
			if ($i == 0)
				print ' ';
			else
				print ',';
			$facavoir = new Facture($db);
			$facavoir->fetch($id);
			print $facavoir->getNomUrl(1);
		}
		print ')';
	}
	if ($objectidnext > 0) {
		$facthatreplace = new Facture($db);
		$facthatreplace->fetch($objectidnext);
		print ' (' . $langs->transnoentities("ReplacedByInvoice", $facthatreplace->getNomUrl(1)) . ')';
	}

	if ($object->type == Facture::TYPE_CREDIT_NOTE || $object->type == Facture::TYPE_DEPOSIT) {
		$discount = new DiscountAbsolute($db);
		$result = $discount->fetch(0, $object->id);
		if ($result > 0){
			print '. '.$langs->trans("CreditNoteConvertedIntoDiscount", $object->getLibType(1), $discount->getNomUrl(1, 'discount')).'<br>';
		}
	}
	print '</td></tr>';

	// Relative and absolute discounts
	print '<!-- Discounts --><tr><td>' . $langs->trans('Discounts');

	print '</td><td>';
	$thirdparty = $soc;
	$discount_type = 0;
	$backtopage = urlencode($_SERVER["PHP_SELF"] . '?facid=' . $object->id);
	include DOL_DOCUMENT_ROOT.'/core/tpl/object_discounts.tpl.php';

	print '</td></tr>';

	// Date invoice
	print '<tr><td>';
	print '<table class="nobordernopadding" width="100%"><tr><td>';
	print $langs->trans('DateInvoice');
	print '</td>';
	if ($object->type != Facture::TYPE_CREDIT_NOTE && $action != 'editinvoicedate' && ! empty($object->brouillon) && $user->rights->facture->creer && empty($conf->global->FAC_FORCE_DATE_VALIDATION))
		print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editinvoicedate&amp;facid=' . $object->id . '">' . img_edit($langs->trans('SetDate'), 1) . '</a></td>';
	print '</tr></table>';
	print '</td><td>';

	if ($object->type != Facture::TYPE_CREDIT_NOTE) {
		if ($action == 'editinvoicedate') {
			$form->form_date($_SERVER['PHP_SELF'] . '?facid=' . $object->id, $object->date, 'invoicedate');
		} else {
			print dol_print_date($object->date, 'day');
		}
	} else {
		print dol_print_date($object->date, 'day');
	}
	print '</td>';

	print '</tr>';

	if (! empty($conf->global->INVOICE_POINTOFTAX_DATE))
	{
		// Date invoice
		print '<tr><td>';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans('DatePointOfTax');
		print '</td>';
		print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editdate_pointoftax&amp;facid=' . $object->id . '">' . img_edit($langs->trans('SetDate'), 1) . '</a></td>';
		print '</tr></table>';
		print '</td><td>';
		if ($action == 'editdate_pointoftax') {
			$form->form_date($_SERVER['PHP_SELF'] . '?facid=' . $object->id, $object->date_pointoftax, 'date_pointoftax');
		} else {
			print dol_print_date($object->date_pointoftax, 'day');
		}
		print '</td></tr>';
	}

	// Payment term
	print '<tr><td>';
	print '<table class="nobordernopadding" width="100%"><tr><td>';
	print $langs->trans('PaymentConditionsShort');
	print '</td>';
	if ($object->type != Facture::TYPE_CREDIT_NOTE && $action != 'editconditions' && $user->rights->facture->creer)
		print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editconditions&amp;facid=' . $object->id . '">' . img_edit($langs->trans('SetConditions'), 1) . '</a></td>';
	print '</tr></table>';
	print '</td><td>';
	if ($object->type != Facture::TYPE_CREDIT_NOTE)
	{
		if ($action == 'editconditions') {
			$form->form_conditions_reglement($_SERVER['PHP_SELF'] . '?facid=' . $object->id, $object->cond_reglement_id, 'cond_reglement_id');
		} else {
			$form->form_conditions_reglement($_SERVER['PHP_SELF'] . '?facid=' . $object->id, $object->cond_reglement_id, 'none');
		}
	} else {
		print '&nbsp;';
	}
	print '</td></tr>';

	// Date payment term
	print '<tr><td>';
	print '<table class="nobordernopadding" width="100%"><tr><td>';
	print $langs->trans('DateMaxPayment');
	print '</td>';
	if ($object->type != Facture::TYPE_CREDIT_NOTE && $action != 'editpaymentterm' && $user->rights->facture->creer)
		print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editpaymentterm&amp;facid=' . $object->id . '">' . img_edit($langs->trans('SetDate'), 1) . '</a></td>';
	print '</tr></table>';
	print '</td><td>';
	if ($object->type != Facture::TYPE_CREDIT_NOTE)
	{
		if ($action == 'editpaymentterm') {
			$form->form_date($_SERVER['PHP_SELF'] . '?facid=' . $object->id, $object->date_lim_reglement, 'paymentterm');
		} else {
			print dol_print_date($object->date_lim_reglement, 'day');
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
	print '<table class="nobordernopadding" width="100%"><tr><td>';
	print $langs->trans('PaymentMode');
	print '</td>';
	if ($action != 'editmode' && $user->rights->facture->creer)
		print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editmode&amp;facid=' . $object->id . '">' . img_edit($langs->trans('SetMode'), 1) . '</a></td>';
	print '</tr></table>';
	print '</td><td>';
	if ($action == 'editmode')
	{
		$form->form_modes_reglement($_SERVER['PHP_SELF'].'?facid='.$object->id, $object->mode_reglement_id, 'mode_reglement_id', 'CRDT');
	}
	else
	{
		$form->form_modes_reglement($_SERVER['PHP_SELF'].'?facid='.$object->id, $object->mode_reglement_id, 'none', 'CRDT');
	}
	print '</td></tr>';

	// Multicurrency
	if (! empty($conf->multicurrency->enabled))
	{
		// Multicurrency code
		print '<tr>';
		print '<td>';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print fieldLabel('Currency','multicurrency_code');
		print '</td>';
		if ($action != 'editmulticurrencycode' && ! empty($object->brouillon))
			print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editmulticurrencycode&amp;id=' . $object->id . '">' . img_edit($langs->transnoentitiesnoconv('SetMultiCurrencyCode'), 1) . '</a></td>';
		print '</tr></table>';
		print '</td><td>';
		if ($action == 'editmulticurrencycode') {
			$form->form_multicurrency_code($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->multicurrency_code, 'multicurrency_code');
		} else {
			$form->form_multicurrency_code($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->multicurrency_code, 'none');
		}
		print '</td></tr>';

		print '<tr>';
		print '<td>';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print fieldLabel('CurrencyRate','multicurrency_tx');
		print '</td>';
		if ($action != 'editmulticurrencyrate' && ! empty($object->brouillon) && $object->multicurrency_code && $object->multicurrency_code != $conf->currency)
			print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editmulticurrencyrate&amp;id=' . $object->id . '">' . img_edit($langs->transnoentitiesnoconv('SetMultiCurrencyCode'), 1) . '</a></td>';
		print '</tr></table>';
		print '</td><td>';
		if ($action == 'editmulticurrencyrate' || $action == 'actualizemulticurrencyrate') {
			if($action == 'actualizemulticurrencyrate') {
				list($object->fk_multicurrency, $object->multicurrency_tx) = MultiCurrency::getIdAndTxFromCode($object->db, $object->multicurrency_code);
			}
			$form->form_multicurrency_rate($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->multicurrency_tx, 'multicurrency_tx', $object->multicurrency_code);
		} else {
			$form->form_multicurrency_rate($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->multicurrency_tx, 'none', $object->multicurrency_code);
			if($object->statut == $object::STATUS_DRAFT && $object->multicurrency_code && $object->multicurrency_code != $conf->currency) {
				print '<div class="inline-block"> &nbsp; &nbsp; &nbsp; &nbsp; ';
				print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=actualizemulticurrencyrate">'.$langs->trans("ActualizeCurrency").'</a>';
				print '</div>';
			}
		}
		print '</td></tr>';
	}

	// Bank Account
	print '<tr><td class="nowrap">';
	print '<table width="100%" class="nobordernopadding"><tr><td class="nowrap">';
	print $langs->trans('BankAccount');
	print '<td>';
	if (($action != 'editbankaccount') && $user->rights->facture->creer)
		print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editbankaccount&amp;id='.$object->id.'">'.img_edit($langs->trans('SetBankAccount'),1).'</a></td>';
	print '</tr></table>';
	print '</td><td>';
	if ($action == 'editbankaccount')
	{
		$form->formSelectAccount($_SERVER['PHP_SELF'].'?id='.$object->id, $object->fk_account, 'fk_account', 1);
	}
	else
	{
		$form->formSelectAccount($_SERVER['PHP_SELF'].'?id='.$object->id, $object->fk_account, 'none');
	}
	print "</td>";
	print '</tr>';






	// Incoterms
	if (!empty($conf->incoterm->enabled))
	{
		print '<tr><td>';
		print '<table width="100%" class="nobordernopadding"><tr><td>';
		print $langs->trans('IncotermLabel');
		print '<td><td align="right">';
		if ($user->rights->facture->creer) print '<a href="'.DOL_URL_ROOT.'/compta/facture/card.php?facid='.$object->id.'&action=editincoterm">'.img_edit().'</a>';
		else print '&nbsp;';
		print '</td></tr></table>';
		print '</td>';
		print '<td>';
		if ($action != 'editincoterm')
		{
			print $form->textwithpicto($object->display_incoterms(), $object->libelle_incoterms, 1);
		}
		else
		{
			print $form->select_incoterms((!empty($object->fk_incoterms) ? $object->fk_incoterms : ''), (!empty($object->location_incoterms)?$object->location_incoterms:''), $_SERVER['PHP_SELF'].'?id='.$object->id);
		}
		print '</td></tr>';
	}

	// Other attributes
	$cols = 2;
	include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';

	print '</table>';

	print '</div>';
	print '<div class="fichehalfright">';
	print '<div class="ficheaddleft">';
	print '<div class="underbanner clearboth"></div>';

	print '<table class="border centpercent">';

	if (!empty($conf->multicurrency->enabled) && ($object->multicurrency_code != $conf->currency))
	{
		// Multicurrency Amount HT
		print '<tr><td class="titlefieldmiddle">' . fieldLabel('MulticurrencyAmountHT','multicurrency_total_ht') . '</td>';
		print '<td class="nowrap amountcard">' . price($object->multicurrency_total_ht, '', $langs, 0, - 1, - 1, (!empty($object->multicurrency_code) ? $object->multicurrency_code : $conf->currency)) . '</td>';
		print '</tr>';

		// Multicurrency Amount VAT
		print '<tr><td>' . fieldLabel('MulticurrencyAmountVAT','multicurrency_total_tva') . '</td>';
		print '<td class="nowrap amountcard">' . price($object->multicurrency_total_tva, '', $langs, 0, - 1, - 1, (!empty($object->multicurrency_code) ? $object->multicurrency_code : $conf->currency)) . '</td>';
		print '</tr>';

		// Multicurrency Amount TTC
		print '<tr><td>' . fieldLabel('MulticurrencyAmountTTC','multicurrency_total_ttc') . '</td>';
		print '<td class="nowrap amountcard">' . price($object->multicurrency_total_ttc, '', $langs, 0, - 1, - 1, (!empty($object->multicurrency_code) ? $object->multicurrency_code : $conf->currency)) . '</td>';
		print '</tr>';
	}

	// Amount
	print '<tr><td class="titlefieldmiddle">' . $langs->trans('AmountHT') . '</td>';
	print '<td class="nowrap amountcard">' . price($object->total_ht, 1, '', 1, - 1, - 1, $conf->currency) . '</td></tr>';

	// Vat
	print '<tr><td>' . $langs->trans('AmountVAT') . '</td><td colspan="3" class="nowrap amountcard">' . price($object->total_tva, 1, '', 1, - 1, - 1, $conf->currency) . '</td></tr>';
	print '</tr>';

	// Amount Local Taxes
	if (($mysoc->localtax1_assuj == "1" && $mysoc->useLocalTax(1)) || $object->total_localtax1 != 0) 	// Localtax1
	{
		print '<tr><td>' . $langs->transcountry("AmountLT1", $mysoc->country_code) . '</td>';
		print '<td class="nowrap amountcard">' . price($object->total_localtax1, 1, '', 1, - 1, - 1, $conf->currency) . '</td></tr>';
	}
	if (($mysoc->localtax2_assuj == "1" && $mysoc->useLocalTax(2)) || $object->total_localtax2 != 0) 	// Localtax2
	{
		print '<tr><td>' . $langs->transcountry("AmountLT2", $mysoc->country_code) . '</td>';
		print '<td class=nowrap amountcard">' . price($object->total_localtax2, 1, '', 1, - 1, - 1, $conf->currency) . '</td></tr>';
	}

	// Revenue stamp
	if ($selleruserevenustamp) 	// Test company use revenue stamp
	{
		print '<tr><td>';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans('RevenueStamp');
		print '</td>';
		if ($action != 'editrevenuestamp' && ! empty($object->brouillon) && $user->rights->facture->creer)
		{
			print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editrevenuestamp&amp;facid=' . $object->id . '">' . img_edit($langs->trans('SetRevenuStamp'), 1) . '</a></td>';
		}
		print '</tr></table>';
		print '</td><td>';
		if ($action == 'editrevenuestamp') {
			print '<form action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
			print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
			print '<input type="hidden" name="action" value="setrevenuestamp">';
			print '<input type="hidden" name="revenuestamp" id="revenuestamp_val" value="'.price2num($object->revenuestamp).'">';
			print $formother->select_revenue_stamp('', 'revenuestamp_type', $mysoc->country_code);
			print ' &rarr; <span id="revenuestamp_span"></span>';
			print ' <input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
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
			print price($object->revenuestamp, 1, '', 1, - 1, - 1, $conf->currency);
		}
		print '</td></tr>';
	}

	// Total with tax
	print '<tr><td>' . $langs->trans('AmountTTC') . '</td><td class="nowrap amountcard">' . price($object->total_ttc, 1, '', 1, - 1, - 1, $conf->currency) . '</td></tr>';

	print '</table>';


	$sign = 1;
	if ($object->type == Facture::TYPE_CREDIT_NOTE) $sign = - 1;
	$nbrows = 8;
	$nbcols = 3;
	if (! empty($conf->projet->enabled))
		$nbrows ++;
	if (! empty($conf->banque->enabled)) {
		$nbrows ++;
		$nbcols ++;
	}
	if ($mysoc->localtax1_assuj == "1" || $object->total_localtax1 != 0)
		$nbrows ++;
	if ($mysoc->localtax2_assuj == "1" || $object->total_localtax2 != 0)
		$nbrows ++;
	if ($selleruserevenustamp)
		$nbrows ++;
	if (! empty($conf->multicurrency->enabled))
		$nbrows += 5;
	if (! empty($conf->incoterm->enabled))
		$nbrows += 1;

	// List of previous situation invoices
	if (($object->situation_cycle_ref > 0) && ! empty($conf->global->INVOICE_USE_SITUATION))
	{

	    print '<table class="noborder situationstable" width="100%">';


	    print '<tr class="liste_titre">';
	    print '<td>' . $langs->trans('ListOfSituationInvoices') . '</td>';
	    print '<td></td>';
	    print '<td align="center">' . $langs->trans('Situation') . '</td>';
	    if (! empty($conf->banque->enabled)) print '<td align="right"></td>';
	    print '<td align="right">' . $langs->trans('AmountHT') . '</td>';
	    print '<td align="right">' . $langs->trans('AmountTTC') . '</td>';
	    print '<td width="18">&nbsp;</td>';
	    print '</tr>';


	    $total_prev_ht = $total_prev_ttc = 0;
	    $total_global_ht = $total_global_ttc = 0;

	    if (count($object->tab_previous_situation_invoice) > 0) {
	        // List of previous invoices

	        $current_situation_counter = array();
	        foreach ($object->tab_previous_situation_invoice as $prev_invoice) {
	            $totalpaye = $prev_invoice->getSommePaiement();
	            $total_prev_ht += $prev_invoice->total_ht;
	            $total_prev_ttc += $prev_invoice->total_ttc;
	            $current_situation_counter[] = (($prev_invoice->type == Facture::TYPE_CREDIT_NOTE)?-1:1) * $prev_invoice->situation_counter;
	            print '<tr class="oddeven">';
	            print '<td>' . $prev_invoice->getNomUrl(1) . '</td>';
	            print '<td></td>';
	            print '<td align="center" >'.(($prev_invoice->type == Facture::TYPE_CREDIT_NOTE)?$langs->trans('situationInvoiceShortcode_AS'):$langs->trans('situationInvoiceShortcode_S')) . $prev_invoice->situation_counter.'</td>';
	            if (! empty($conf->banque->enabled)) print '<td align="right"></td>';
	            print '<td align="right">' . price($prev_invoice->total_ht) . '</td>';
	            print '<td align="right">' . price($prev_invoice->total_ttc) . '</td>';
	            print '<td align="right">' . $prev_invoice->getLibStatut(3, $totalpaye) . '</td>';
	            print '</tr>';
	        }
	    }


	    $total_global_ht += $total_prev_ht ;
	    $total_global_ttc += $total_prev_ttc ;
	    $total_global_ht += $object->total_ht;
	    $total_global_ttc += $object->total_ttc;
	    $current_situation_counter[] = (($object->type == Facture::TYPE_CREDIT_NOTE)?-1:1) * $object->situation_counter;
	    print '<tr class="oddeven">';
	    print '<td>' . $object->getNomUrl(1) . '</td>';
	    print '<td></td>';
	    print '<td align="center">'.(($object->type == Facture::TYPE_CREDIT_NOTE)?$langs->trans('situationInvoiceShortcode_AS'):$langs->trans('situationInvoiceShortcode_S')) . $object->situation_counter.'</td>';
	    if (! empty($conf->banque->enabled)) print '<td align="right"></td>';
	    print '<td align="right">' . price($object->total_ht) . '</td>';
	    print '<td align="right">' . price($object->total_ttc) . '</td>';
	    print '<td align="right">' . $object->getLibStatut(3, $object->getSommePaiement()) . '</td>';
	    print '</tr>';


	    print '<tr class="oddeven">';
	    print '<td colspan="2" align="left"><b>' . $langs->trans('CurrentSituationTotal') . '</b></td>';
	    print '<td>';
	    $i =0;
	    foreach ($current_situation_counter as $sit)
	    {
	        $curSign = $sit>0?'+':'-';
	        $curType = $sit>0?$langs->trans('situationInvoiceShortcode_S'):$langs->trans('situationInvoiceShortcode_AS');
	        if($i>0) print ' '.$curSign.' ';
	        print $curType . abs($sit);
	        $i++;
	    }
	    print '</td>';
	    if (! empty($conf->banque->enabled)) print '<td></td>';
	    print '<td align="right"><b>' . price($total_global_ht) . '</b></td>';
	    print '<td align="right"><b>' . price($total_global_ttc) . '</b></td>';
	    print '<td width="18">&nbsp;</td>';
	    print '</tr>';


	    if (count($object->tab_next_situation_invoice) > 0) {
	        // List of next invoices
	        /*print '<tr class="liste_titre">';
	         print '<td>' . $langs->trans('ListOfNextSituationInvoices') . '</td>';
	         print '<td></td>';
	         print '<td></td>';
	         if (! empty($conf->banque->enabled)) print '<td align="right"></td>';
	         print '<td align="right">' . $langs->trans('AmountHT') . '</td>';
	         print '<td align="right">' . $langs->trans('AmountTTC') . '</td>';
	         print '<td width="18">&nbsp;</td>';
	         print '</tr>';*/

	        $total_next_ht = $total_next_ttc = 0;

	        foreach ($object->tab_next_situation_invoice as $next_invoice) {
	            $totalpaye = $next_invoice->getSommePaiement();
	            $total_next_ht += $next_invoice->total_ht;
	            $total_next_ttc += $next_invoice->total_ttc;

	            print '<tr class="oddeven">';
	            print '<td>' . $next_invoice->getNomUrl(1) . '</td>';
	            print '<td></td>';
	            print '<td align="center">'.(($next_invoice->type == Facture::TYPE_CREDIT_NOTE)?$langs->trans('situationInvoiceShortcode_AS'):$langs->trans('situationInvoiceShortcode_S')) . $next_invoice->situation_counter.'</td>';
	            if (! empty($conf->banque->enabled)) print '<td align="right"></td>';
	            print '<td align="right">' . price($next_invoice->total_ht) . '</td>';
	            print '<td align="right">' . price($next_invoice->total_ttc) . '</td>';
	            print '<td align="right">' . $next_invoice->getLibStatut(3, $totalpaye) . '</td>';
	            print '</tr>';

	        }

	        $total_global_ht += $total_next_ht;
	        $total_global_ttc += $total_next_ttc;

	        print '<tr class="oddeven">';
	        print '<td colspan="3" align="right"></td>';
	        if (! empty($conf->banque->enabled)) print '<td align="right"></td>';
	        print '<td align="right"><b>' . price($total_global_ht) . '</b></td>';
	        print '<td align="right"><b>' . price($total_global_ttc) . '</b></td>';
	        print '<td width="18">&nbsp;</td>';
	        print '</tr>';
	    }

	    print '</table>';
	}


	// List of payments already done

	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder paymenttable" width="100%">';

	print '<tr class="liste_titre">';
	print '<td class="liste_titre">' . ($object->type == Facture::TYPE_CREDIT_NOTE ? $langs->trans("PaymentsBack") : $langs->trans('Payments')) . '</td>';
	print '<td class="liste_titre">' . $langs->trans('Date') . '</td>';
	print '<td class="liste_titre">' . $langs->trans('Type') . '</td>';
	if (! empty($conf->banque->enabled)) {
		print '<td class="liste_titre" align="right">' . $langs->trans('BankAccount') . '</td>';
	}
	print '<td class="liste_titre" align="right">' . $langs->trans('Amount') . '</td>';
	print '<td class="liste_titre" width="18">&nbsp;</td>';
	print '</tr>';

	// Payments already done (from payment on this invoice)
	$sql = 'SELECT p.datep as dp, p.ref, p.num_paiement, p.rowid, p.fk_bank,';
	$sql .= ' c.code as payment_code, c.libelle as payment_label,';
	$sql .= ' pf.amount,';
	$sql .= ' ba.rowid as baid, ba.ref as baref, ba.label, ba.number as banumber, ba.account_number, ba.fk_accountancy_journal';
	$sql .= ' FROM ' . MAIN_DB_PREFIX . 'paiement_facture as pf, ' . MAIN_DB_PREFIX . 'paiement as p';
	$sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'c_paiement as c ON p.fk_paiement = c.id' ;
	$sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'bank as b ON p.fk_bank = b.rowid';
	$sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'bank_account as ba ON b.fk_account = ba.rowid';
	$sql .= ' WHERE pf.fk_facture = ' . $object->id . ' AND pf.fk_paiement = p.rowid';
	$sql .= ' AND p.entity IN (' . getEntity('facture').')';
	$sql .= ' ORDER BY p.datep, p.tms';

	$result = $db->query($sql);
	if ($result) {
		$num = $db->num_rows($result);
		$i = 0;

		// if ($object->type != 2)
		// {
		if ($num > 0) {
			while ($i < $num) {
				$objp = $db->fetch_object($result);

				$paymentstatic->id = $objp->rowid;
				$paymentstatic->datepaye = $db->jdate($objp->dp);
				$paymentstatic->ref = $objp->ref;
				$paymentstatic->num_paiement = $objp->num_paiement;
				$paymentstatic->payment_code = $objp->payment_code;

				print '<tr class="oddeven"><td>';
				print $paymentstatic->getNomUrl(1);
				print '</td>';
				print '<td>' . dol_print_date($db->jdate($objp->dp), 'day') . '</td>';
				$label = ($langs->trans("PaymentType" . $objp->payment_code) != ("PaymentType" . $objp->payment_code)) ? $langs->trans("PaymentType" . $objp->payment_code) : $objp->payment_label;
				print '<td>' . $label . ' ' . $objp->num_paiement . '</td>';
				if (! empty($conf->banque->enabled))
				{
					$bankaccountstatic->id = $objp->baid;
					$bankaccountstatic->ref = $objp->baref;
					$bankaccountstatic->label = $objp->baref;
					$bankaccountstatic->number = $objp->banumber;

					if (! empty($conf->accounting->enabled)) {
						$bankaccountstatic->account_number = $objp->account_number;

						$accountingjournal = new AccountingJournal($db);
						$accountingjournal->fetch($objp->fk_accountancy_journal);
						$bankaccountstatic->accountancy_journal = $accountingjournal->getNomUrl(0,1,1,'',1);
					}

					print '<td align="right">';
					if ($bankaccountstatic->id)
						print $bankaccountstatic->getNomUrl(1, 'transactions');
					print '</td>';
				}
				print '<td align="right">' . price($sign * $objp->amount) . '</td>';
				print '<td align="center">';
				if ($object->statut == Facture::STATUS_VALIDATED && $object->paye == 0 && $user->societe_id == 0)
				{
					print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=deletepaiement&paiement_id='.$objp->rowid.'">';
					print img_delete();
					print '</a>';
				}
				print '</td>';
				print '</tr>';
				$i ++;
			}
		}
		/*else {
            print '<tr class="oddeven"><td colspan="' . $nbcols . '" class="opacitymedium">' . $langs->trans("None") . '</td><td></td><td></td></tr>';
        }*/
		// }
		$db->free($result);
	} else {
		dol_print_error($db);
	}

	if ($object->type != Facture::TYPE_CREDIT_NOTE) {
		// Total already paid
		print '<tr><td colspan="' . $nbcols . '" align="right">';
		if ($object->type != Facture::TYPE_DEPOSIT)
			print $langs->trans('AlreadyPaidNoCreditNotesNoDeposits');
		else
			print $langs->trans('AlreadyPaid');
		print ' :</td><td align="right"'.(($totalpaye > 0)?' class="amountalreadypaid"':'').'>' . price($totalpaye) . '</td><td>&nbsp;</td></tr>';

		$resteapayeraffiche = $resteapayer;
		$cssforamountpaymentcomplete = 'amountpaymentcomplete';

		// Loop on each credit note or deposit amount applied
		$creditnoteamount = 0;
		$depositamount = 0;
		$sql = "SELECT re.rowid, re.amount_ht, re.amount_tva, re.amount_ttc,";
		$sql .= " re.description, re.fk_facture_source";
		$sql .= " FROM " . MAIN_DB_PREFIX . "societe_remise_except as re";
		$sql .= " WHERE fk_facture = " . $object->id;
		$resql = $db->query($sql);
		if ($resql) {
			$num = $db->num_rows($resql);
			$i = 0;
			$invoice = new Facture($db);
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				$invoice->fetch($obj->fk_facture_source);
				print '<tr><td colspan="' . $nbcols . '" align="right">';
				if ($invoice->type == Facture::TYPE_CREDIT_NOTE)
					print $langs->trans("CreditNote") . ' ';
				if ($invoice->type == Facture::TYPE_DEPOSIT)
					print $langs->trans("Deposit") . ' ';
				print $invoice->getNomUrl(0);
				print ' :</td>';
				print '<td align="right">' . price($obj->amount_ttc) . '</td>';
				print '<td align="right">';
				print '<a href="' . $_SERVER["PHP_SELF"] . '?facid=' . $object->id . '&action=unlinkdiscount&discountid=' . $obj->rowid . '">' . img_delete() . '</a>';
				print '</td></tr>';
				$i ++;
				if ($invoice->type == Facture::TYPE_CREDIT_NOTE)
					$creditnoteamount += $obj->amount_ttc;
				if ($invoice->type == Facture::TYPE_DEPOSIT)
					$depositamount += $obj->amount_ttc;
			}
		} else {
			dol_print_error($db);
		}

		// Paye partiellement 'escompte'
		if (($object->statut == Facture::STATUS_CLOSED || $object->statut == Facture::STATUS_ABANDONED) && $object->close_code == 'discount_vat') {
			print '<tr><td colspan="' . $nbcols . '" align="right" class="nowrap">';
			print $form->textwithpicto($langs->trans("Discount") . ':', $langs->trans("HelpEscompte"), - 1);
			print '</td><td align="right">' . price(price2num($object->total_ttc - $creditnoteamount - $depositamount - $totalpaye, 'MT')) . '</td><td>&nbsp;</td></tr>';
			$resteapayeraffiche = 0;
			$cssforamountpaymentcomplete = 'amountpaymentneutral';
		}
		// Paye partiellement ou Abandon 'badcustomer'
		if (($object->statut == Facture::STATUS_CLOSED || $object->statut == Facture::STATUS_ABANDONED) && $object->close_code == 'badcustomer') {
			print '<tr><td colspan="' . $nbcols . '" align="right" class="nowrap">';
			print $form->textwithpicto($langs->trans("Abandoned") . ':', $langs->trans("HelpAbandonBadCustomer"), - 1);
			print '</td><td align="right">' . price(price2num($object->total_ttc - $creditnoteamount - $depositamount - $totalpaye, 'MT')) . '</td><td>&nbsp;</td></tr>';
			// $resteapayeraffiche=0;
			$cssforamountpaymentcomplete = 'amountpaymentneutral';
		}
		// Paye partiellement ou Abandon 'product_returned'
		if (($object->statut == Facture::STATUS_CLOSED || $object->statut == Facture::STATUS_ABANDONED) && $object->close_code == 'product_returned') {
			print '<tr><td colspan="' . $nbcols . '" align="right" class="nowrap">';
			print $form->textwithpicto($langs->trans("ProductReturned") . ':', $langs->trans("HelpAbandonProductReturned"), - 1);
			print '</td><td align="right">' . price(price2num($object->total_ttc - $creditnoteamount - $depositamount - $totalpaye, 'MT')) . '</td><td>&nbsp;</td></tr>';
			$resteapayeraffiche = 0;
			$cssforamountpaymentcomplete = 'amountpaymentneutral';
		}
		// Paye partiellement ou Abandon 'abandon'
		if (($object->statut == Facture::STATUS_CLOSED || $object->statut == Facture::STATUS_ABANDONED) && $object->close_code == 'abandon') {
			print '<tr><td colspan="' . $nbcols . '" align="right" class="nowrap">';
			$text = $langs->trans("HelpAbandonOther");
			if ($object->close_note)
				$text .= '<br><br><b>' . $langs->trans("Reason") . '</b>:' . $object->close_note;
			print $form->textwithpicto($langs->trans("Abandoned") . ':', $text, - 1);
			print '</td><td align="right">' . price(price2num($object->total_ttc - $creditnoteamount - $depositamount - $totalpaye, 'MT')) . '</td><td>&nbsp;</td></tr>';
			$resteapayeraffiche = 0;
			$cssforamountpaymentcomplete = 'amountpaymentneutral';
		}

		// Billed
		print '<tr><td colspan="' . $nbcols . '" align="right">' . $langs->trans("Billed") . ' :</td><td align="right">' . price($object->total_ttc) . '</td><td>&nbsp;</td></tr>';

		// Remainder to pay
		print '<tr><td colspan="' . $nbcols . '" align="right">';
		if ($resteapayeraffiche >= 0)
			print $langs->trans('RemainderToPay');
		else
			print $langs->trans('ExcessReceived');
		print ' :</td>';
		print '<td align="right"'.($resteapayeraffiche?' class="amountremaintopay"':(' class="'.$cssforamountpaymentcomplete.'"')).'>' . price($resteapayeraffiche) . '</td>';
		print '<td class="nowrap">&nbsp;</td></tr>';
	}
	else // Credit note
	{
		$cssforamountpaymentcomplete='amountpaymentneutral';

		// Total already paid back
		print '<tr><td colspan="' . $nbcols . '" align="right">';
		print $langs->trans('AlreadyPaidBack');
		print ' :</td><td align="right">' . price($sign * $totalpaye) . '</td><td>&nbsp;</td></tr>';

		// Billed
		print '<tr><td colspan="' . $nbcols . '" align="right">' . $langs->trans("Billed") . ' :</td><td align="right">' . price($sign * $object->total_ttc) . '</td><td>&nbsp;</td></tr>';

		// Remainder to pay back
		print '<tr><td colspan="' . $nbcols . '" align="right">';
		if ($resteapayeraffiche <= 0)
			print $langs->trans('RemainderToPayBack');
		else
			print $langs->trans('ExcessPaid');
		print ' :</td>';
		print '<td align="right"'.($resteapayeraffiche?' class="amountremaintopayback"':(' class="'.$cssforamountpaymentcomplete.'"')).'>' . price($sign * $resteapayeraffiche) . '</td>';
		print '<td class="nowrap">&nbsp;</td></tr>';

		// Sold credit note
		// print '<tr><td colspan="'.$nbcols.'" align="right">'.$langs->trans('TotalTTC').' :</td>';
		// print '<td align="right" style="border: 1px solid;" bgcolor="#f0f0f0"><b>'.price($sign *
		// $object->total_ttc).'</b></td><td>&nbsp;</td></tr>';
	}

	print '</table>';
	print '</div>';

	// Margin Infos
	if (! empty($conf->margin->enabled)) {
		$formmargin->displayMarginInfos($object);
	}

	print '</div>';
	print '</div>';
	print '</div>';

	print '<div class="clearboth"></div><br>';

	if (! empty($conf->global->MAIN_DISABLE_CONTACTS_TAB)) {
		$blocname = 'contacts';
		$title = $langs->trans('ContactsAddresses');
		include DOL_DOCUMENT_ROOT . '/core/tpl/bloc_showhide.tpl.php';
	}

	if (! empty($conf->global->MAIN_DISABLE_NOTES_TAB)) {
		$blocname = 'notes';
		$title = $langs->trans('Notes');
		include DOL_DOCUMENT_ROOT . '/core/tpl/bloc_showhide.tpl.php';
	}

	// Lines
	$result = $object->getLinesArray();

	// Show global modifiers
	if (! empty($conf->global->INVOICE_USE_SITUATION))
	{
		if ($object->situation_cycle_ref && $object->statut == 0) {
			print '<div class="div-table-responsive">';

			print '<form name="updatealllines" id="updatealllines" action="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '#updatealllines" method="POST">';
			print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '" />';
			print '<input type="hidden" name="action" value="updatealllines" />';
			print '<input type="hidden" name="id" value="' . $object->id . '" />';

			print '<table id="tablelines_all_progress" class="noborder noshadow" width="100%">';

			print '<tr class="liste_titre nodrag nodrop">';

			if (!empty($conf->global->MAIN_VIEW_LINE_NUMBER)) {
				print '<td align="center" width="5">&nbsp;</td>';
			}
			print '<td>' . $langs->trans('ModifyAllLines') . '</td>';
			print '<td align="right" width="50">&nbsp;</td>';
			print '<td align="right" width="80">&nbsp;</td>';
			if ($inputalsopricewithtax) print '<td align="right" width="80">&nbsp;</td>';
			print '<td align="right" width="50">&nbsp</td>';
			print '<td align="right" width="50">&nbsp</td>';
			print '<td align="right" width="50">' . $langs->trans('Progress') . '</td>';
			if (! empty($conf->margin->enabled) && empty($user->societe_id))
			{
				print '<td align="right" class="margininfos" width="80">&nbsp;</td>';
				if ((! empty($conf->global->DISPLAY_MARGIN_RATES) || ! empty($conf->global->DISPLAY_MARK_RATES)) && $user->rights->margins->liretous) {
					print '<td align="right" class="margininfos" width="50">&nbsp;</td>';
				}
			}
			print '<td align="right" width="50">&nbsp;</td>';
			print '<td>&nbsp;</td>';
			print '<td width="10">&nbsp;</td>';
			print '<td width="10">&nbsp;</td>';
			print "</tr>\n";

			if (!empty($conf->global->MAIN_VIEW_LINE_NUMBER)) {
				print '<td align="center" width="5">&nbsp;</td>';
			}
			print '<tr width="100%" class="nodrag nodrop">';
			print '<td>&nbsp;</td>';
			print '<td width="50">&nbsp;</td>';
			print '<td width="80">&nbsp;</td>';
			print '<td width="50">&nbsp;</td>';
			print '<td width="50">&nbsp;</td>';
			print '<td align="right" class="nowrap"><input type="text" size="1" value="" name="all_progress">%</td>';
			print '<td colspan="4" align="right"><input class="button" type="submit" name="all_percent" value="Modifier" /></td>';
			print '</tr>';

			print '</table>';

			print '</form>';

			print '</div>';
		}
	}

	print '	<form name="addproduct" id="addproduct" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . (($action != 'editline') ? '#addline' : '#line_' . GETPOST('lineid')) . '" method="POST">
	<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">
	<input type="hidden" name="action" value="' . (($action != 'editline') ? 'addline' : 'updateligne') . '">
	<input type="hidden" name="mode" value="">
	<input type="hidden" name="id" value="' . $object->id . '">
	';

	if (! empty($conf->use_javascript_ajax) && $object->statut == 0) {
		include DOL_DOCUMENT_ROOT . '/core/tpl/ajaxrow.tpl.php';
	}

	print '<div class="div-table-responsive-no-min">';
	print '<table id="tablelines" class="noborder noshadow" width="100%">';

	// Show object lines
	if (! empty($object->lines))
		$ret = $object->printObjectLines($action, $mysoc, $soc, $lineid, 1);

	// Form to add new line
	if ($object->statut == 0 && $user->rights->facture->creer && $action != 'valid' && $action != 'editline' && ($object->is_first() || !$object->situation_cycle_ref))
	{
	    if ($action != 'editline' && $action != 'selectlines')
		{
			// Add free products/services
			$object->formAddObjectLine(1, $mysoc, $soc);

			$parameters = array();
			$reshook = $hookmanager->executeHooks('formAddObjectLine', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		}
	}

	print "</table>\n";
	print "</div>";

	print "</form>\n";

	dol_fiche_end();


	// Actions buttons

	if ($action != 'prerelance' && $action != 'presend' && $action != 'valid' && $action != 'editline')
	{
		print '<div class="tabsAction">';

		$parameters = array();
		$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		if (empty($reshook)) {
			// Editer une facture deja validee, sans paiement effectue et pas exporte en compta
			if ($object->statut == Facture::STATUS_VALIDATED)
			{
				// On verifie si les lignes de factures ont ete exportees en compta et/ou ventilees
				$ventilExportCompta = $object->getVentilExportCompta();

				if ($ventilExportCompta == 0)
				{
					if (! empty($conf->global->INVOICE_CAN_ALWAYS_BE_EDITED) || ($resteapayer == $object->total_ttc && empty($object->paye)))
					{
						if (! $objectidnext && $object->is_last_in_cycle())
						{
							if ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->facture->creer))
		   						|| (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->facture->invoice_advance->unvalidate)))
							{
								print '<div class="inline-block divButAction"><a class="butAction'.($conf->use_javascript_ajax?' reposition':'').'" href="' . $_SERVER['PHP_SELF'] . '?facid=' . $object->id . '&amp;action=modif">' . $langs->trans('Modify') . '</a></div>';
							} else {
								print '<div class="inline-block divButAction"><span class="butActionRefused" title="' . $langs->trans("NotEnoughPermissions") . '">' . $langs->trans('Modify') . '</span></div>';
							}
						} else if (!$object->is_last_in_cycle()) {
							print '<div class="inline-block divButAction"><span class="butActionRefused" title="' . $langs->trans("NotLastInCycle") . '">' . $langs->trans('Modify') . '</span></div>';
						} else {
							print '<div class="inline-block divButAction"><span class="butActionRefused" title="' . $langs->trans("DisabledBecauseReplacedInvoice") . '">' . $langs->trans('Modify') . '</span></div>';
						}
					}
				}
				else
				{
					print '<div class="inline-block divButAction"><span class="butActionRefused" title="' . $langs->trans("DisabledBecauseDispatchedInAccounting") . '">' . $langs->trans('Modify') . '</span></div>';
				}
			}

			$discount = new DiscountAbsolute($db);
			$result = $discount->fetch(0, $object->id);

			// Reopen a standard paid invoice
			if ((($object->type == Facture::TYPE_STANDARD || $object->type == Facture::TYPE_REPLACEMENT)
				|| ($object->type == Facture::TYPE_CREDIT_NOTE && empty($discount->id))
				|| ($object->type == Facture::TYPE_DEPOSIT && empty($discount->id)))
				&& ($object->statut == 2 || $object->statut == 3 || ($object->statut == 1 && $object->paye == 1))   // Condition ($object->statut == 1 && $object->paye == 1) should not happened but can be found due to corrupted data
				&& ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && $user->rights->facture->creer) || (!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && $user->rights->facture->invoice_advance->reopen)))				// A paid invoice (partially or completely)
			{
				if (! $objectidnext && $object->close_code != 'replaced') 				// Not replaced by another invoice
				{
					print '<div class="inline-block divButAction"><a class="butAction'.($conf->use_javascript_ajax?' reposition':'').'" href="' . $_SERVER['PHP_SELF'] . '?facid=' . $object->id . '&amp;action=reopen">' . $langs->trans('ReOpen') . '</a></div>';
				} else {
					print '<div class="inline-block divButAction"><span class="butActionRefused" title="' . $langs->trans("DisabledBecauseReplacedInvoice") . '">' . $langs->trans('ReOpen') . '</span></div>';
				}
			}

			// Validate
			if ($object->statut == Facture::STATUS_DRAFT && count($object->lines) > 0 && ((($object->type == Facture::TYPE_STANDARD || $object->type == Facture::TYPE_REPLACEMENT || $object->type == Facture::TYPE_DEPOSIT || $object->type == Facture::TYPE_PROFORMA || $object->type == Facture::TYPE_SITUATION) && (! empty($conf->global->FACTURE_ENABLE_NEGATIVE) || $object->total_ttc >= 0)) || ($object->type == Facture::TYPE_CREDIT_NOTE && $object->total_ttc <= 0))) {
				if ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->facture->creer))
		  		|| (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->facture->invoice_advance->validate)))
				{
					print '<div class="inline-block divButAction"><a class="butAction'.($conf->use_javascript_ajax?' reposition':'').'" href="' . $_SERVER["PHP_SELF"] . '?facid=' . $object->id . '&amp;action=valid">' . $langs->trans('Validate') . '</a></div>';
				}
			}

			// Send by mail
			if (($object->statut == Facture::STATUS_VALIDATED || $object->statut == Facture::STATUS_CLOSED) || ! empty($conf->global->FACTURE_SENDBYEMAIL_FOR_ALL_STATUS)) {
				if ($objectidnext) {
					print '<div class="inline-block divButAction"><span class="butActionRefused" title="' . $langs->trans("DisabledBecauseReplacedInvoice") . '">' . $langs->trans('SendMail') . '</span></div>';
				} else {
					if (empty($conf->global->MAIN_USE_ADVANCED_PERMS) || $user->rights->facture->invoice_advance->send) {
						print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?facid=' . $object->id . '&action=presend&mode=init#formmailbeforetitle">' . $langs->trans('SendMail') . '</a></div>';
					} else
						print '<div class="inline-block divButAction"><a class="butActionRefused" href="#">' . $langs->trans('SendMail') . '</a></div>';
				}
			}

			// Request a direct debit order
			if ($object->statut > Facture::STATUS_DRAFT && $object->paye == 0 && $num == 0)
			{
				if ($resteapayer > 0)
				{
					if ($user->rights->prelevement->bons->creer)
					{
						if (! $objectidnext && $object->close_code != 'replaced') 				// Not replaced by another invoice
						{
							print '<a class="butAction" href="'.DOL_URL_ROOT.'/compta/facture/prelevement.php?facid='.$object->id.'" title="'.dol_escape_htmltag($langs->trans("MakeWithdrawRequest")).'">'.$langs->trans("MakeWithdrawRequest").'</a>';
						} else {
							print '<div class="inline-block divButAction"><span class="butActionRefused" title="' . $langs->trans("DisabledBecauseReplacedInvoice") . '">' . $langs->trans('MakeWithdrawRequest') . '</span></div>';
						}
					}
					else
					{
						//print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans("MakeWithdrawRequest").'</a>';
					}
				}
				else
				{
					//print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("AmountMustBePositive")).'">'.$langs->trans("MakeWithdrawRequest").'</a>';
				}
			}

			// Create payment
			if ($object->type != Facture::TYPE_CREDIT_NOTE && $object->statut == 1 && $object->paye == 0 && $user->rights->facture->paiement) {
				if ($objectidnext) {
					print '<div class="inline-block divButAction"><span class="butActionRefused" title="' . $langs->trans("DisabledBecauseReplacedInvoice") . '">' . $langs->trans('DoPayment') . '</span></div>';
				} else {
					//if ($resteapayer == 0) {		// Sometimes we can receive more, so we accept to enter more and will offer a button to convert into discount (but it is not a credit note, just a prepayment done)
					//	print '<div class="inline-block divButAction"><span class="butActionRefused" title="' . $langs->trans("DisabledBecauseRemainderToPayIsZero") . '">' . $langs->trans('DoPayment') . '</span></div>';
					//} else {
						print '<div class="inline-block divButAction"><a class="butAction" href="'. DOL_URL_ROOT .'/compta/paiement.php?facid=' . $object->id . '&amp;action=create&amp;accountid='.$object->fk_account.'">' . $langs->trans('DoPayment') . '</a></div>';
					//}
				}
			}

			// Reverse back money or convert to reduction
			if ($object->type == Facture::TYPE_CREDIT_NOTE || $object->type == Facture::TYPE_DEPOSIT || $object->type == Facture::TYPE_STANDARD) {
				// For credit note only
				if ($object->type == Facture::TYPE_CREDIT_NOTE && $object->statut == 1 && $object->paye == 0 && $user->rights->facture->paiement)
				{
					if ($resteapayer == 0)
					{
						print '<div class="inline-block divButAction"><span class="butActionRefused" title="'.$langs->trans("DisabledBecauseRemainderToPayIsZero").'">'.$langs->trans('DoPaymentBack').'</span></div>';
					}
					else
					{
						print '<div class="inline-block divButAction"><a class="butAction" href="'. DOL_URL_ROOT .'/compta/paiement.php?facid='.$object->id.'&amp;action=create&amp;accountid='.$object->fk_account.'">'.$langs->trans('DoPaymentBack').'</a></div>';
					}
				}

				// For standard invoice with excess received
				if ($object->type == Facture::TYPE_STANDARD && empty($object->paye) && ($object->total_ttc - $totalpaye - $totalcreditnotes - $totaldeposits) < 0 && $user->rights->facture->creer && empty($discount->id))
				{
					print '<div class="inline-block divButAction"><a class="butAction'.($conf->use_javascript_ajax?' reposition':'').'" href="'.$_SERVER["PHP_SELF"].'?facid='.$object->id.'&amp;action=converttoreduc">'.$langs->trans('ConvertExcessReceivedToReduc').'</a></div>';
				}
				// For credit note
				if ($object->type == Facture::TYPE_CREDIT_NOTE && $object->statut == 1 && $object->paye == 0 && $user->rights->facture->creer && $object->getSommePaiement() == 0) {
					print '<div class="inline-block divButAction"><a class="butAction'.($conf->use_javascript_ajax?' reposition':'').'" href="' . $_SERVER["PHP_SELF"] . '?facid=' . $object->id . '&amp;action=converttoreduc">' . $langs->trans('ConvertToReduc') . '</a></div>';
				}
				// For deposit invoice
				if ($object->type == Facture::TYPE_DEPOSIT && $user->rights->facture->creer && $object->statut > 0 && empty($discount->id))
				{
					print '<div class="inline-block divButAction"><a class="butAction'.($conf->use_javascript_ajax?' reposition':'').'" href="'.$_SERVER["PHP_SELF"].'?facid='.$object->id.'&amp;action=converttoreduc">'.$langs->trans('ConvertToReduc').'</a></div>';
				}
			}

			// Classify paid
			if ($object->statut == 1 && $object->paye == 0 && $user->rights->facture->paiement && (($object->type != Facture::TYPE_CREDIT_NOTE && $object->type != Facture::TYPE_DEPOSIT && $resteapayer <= 0) || ($object->type == Facture::TYPE_CREDIT_NOTE && $resteapayer >= 0))
				|| ($object->type == Facture::TYPE_DEPOSIT && $object->paye == 0 && $object->total_ttc > 0 && $resteapayer == 0 && $user->rights->facture->paiement && empty($discount->id))
			)
			{
				print '<div class="inline-block divButAction"><a class="butAction'.($conf->use_javascript_ajax?' reposition':'').'" href="'.$_SERVER['PHP_SELF'].'?facid='.$object->id.'&amp;action=paid">'.$langs->trans('ClassifyPaid').'</a></div>';
			}

			// Classify 'closed not completely paid' (possible si validee et pas encore classee payee)

			if ($object->statut == 1 && $object->paye == 0 && $resteapayer > 0 && $user->rights->facture->paiement)
			{
				if ($totalpaye > 0 || $totalcreditnotes > 0)
				{
					// If one payment or one credit note was linked to this invoice
					print '<div class="inline-block divButAction"><a class="butAction'.($conf->use_javascript_ajax?' reposition':'').'" href="' . $_SERVER['PHP_SELF'] . '?facid=' . $object->id . '&amp;action=paid">' . $langs->trans('ClassifyPaidPartially') . '</a></div>';
				}
				else
				{
					if ( empty($conf->global->INVOICE_CAN_NEVER_BE_CANCELED))
					{
						if ($objectidnext)
						{
							print '<div class="inline-block divButAction"><span class="butActionRefused" title="' . $langs->trans("DisabledBecauseReplacedInvoice") . '">' . $langs->trans('ClassifyCanceled') . '</span></div>';
						}
						else
						{
							print '<div class="inline-block divButAction"><a class="butAction'.($conf->use_javascript_ajax?' reposition':'').'" href="' . $_SERVER['PHP_SELF'] . '?facid=' . $object->id . '&amp;action=canceled">' . $langs->trans('ClassifyCanceled') . '</a></div>';
						}
					}
				}
			}

			// Clone
			if (($object->type == Facture::TYPE_STANDARD || $object->type == Facture::TYPE_DEPOSIT || $object->type == Facture::TYPE_PROFORMA) && $user->rights->facture->creer)
			{
				print '<div class="inline-block divButAction"><a class="butAction'.($conf->use_javascript_ajax?' reposition':'').'" href="' . $_SERVER['PHP_SELF'] . '?facid=' . $object->id . '&amp;action=clone&amp;object=invoice">' . $langs->trans("ToClone") . '</a></div>';
			}

			// Clone as predefined / Create template
			if (($object->type == Facture::TYPE_STANDARD || $object->type == Facture::TYPE_DEPOSIT || $object->type == Facture::TYPE_PROFORMA) && $object->statut == 0 && $user->rights->facture->creer)
			{
				if (! $objectidnext && count($object->lines) > 0)
				{
					print '<div class="inline-block divButAction"><a class="butAction" href="'.DOL_URL_ROOT.'/compta/facture/fiche-rec.php?facid=' . $object->id . '&amp;action=create">' . $langs->trans("ChangeIntoRepeatableInvoice") . '</a></div>';
				}
			}

			// Create a credit note
			if (($object->type == Facture::TYPE_STANDARD || $object->type == Facture::TYPE_DEPOSIT || $object->type == Facture::TYPE_PROFORMA) && $object->statut > 0 && $user->rights->facture->creer)
			{
				if (! $objectidnext)
				{
					print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?socid=' . $object->socid .'&amp;fac_avoir=' . $object->id . '&amp;action=create&amp;type=2'.($object->fk_project > 0 ? '&amp;projectid='.$object->fk_project : '').'">' . $langs->trans("CreateCreditNote") . '</a></div>';
				}
			}

			// For situation invoice with excess received
			if ($object->statut > Facture::STATUS_DRAFT
			    && ($object->total_ttc - $totalpaye - $totalcreditnotes - $totaldeposits) > 0
			    && $user->rights->facture->creer
			    && !$objectidnext
			    && $object->is_last_in_cycle()
			    && $conf->global->INVOICE_USE_SITUATION_CREDIT_NOTE
			    )
			{
			    if ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->facture->creer))
			        || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->facture->invoice_advance->unvalidate)))
			    {
			        print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?socid=' . $object->socid .'&amp;fac_avoir=' . $object->id . '&amp;invoiceAvoirWithLines=1&amp;action=create&amp;type=2'.($object->fk_project > 0 ? '&amp;projectid='.$object->fk_project : '').'">' . $langs->trans("CreateCreditNote") . '</a></div>';
			    } else {
			        print '<div class="inline-block divButAction"><span class="butActionRefused" title="' . $langs->trans("NotEnoughPermissions") . '">' . $langs->trans("CreateCreditNote") . '</span></div>';
			    }
			}

			// remove situation from cycle
			if ($object->statut > Facture::STATUS_DRAFT
			    && $object->type == Facture::TYPE_SITUATION
			    && $user->rights->facture->creer
			    && !$objectidnext
			    && $object->situation_counter > 1
			    && $object->is_last_in_cycle()
			    && ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->facture->creer))
			        || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->facture->invoice_advance->unvalidate)))
			    )
			{
			    if(($object->total_ttc - $totalcreditnotes  ) == 0 )
			    {
			        print '<div class="inline-block divButAction"><a id="butSituationOut" class="butAction" href="' . $_SERVER['PHP_SELF'] . '?facid=' . $object->id . '&amp;action=situationout">' . $langs->trans("RemoveSituationFromCycle") . '</a></div>';
			    }
			    else
			    {
			        print '<div class="inline-block divButAction"><a id="butSituationOutRefused" class="butActionRefused" href="#" title="' . $langs->trans("DisabledBecauseNotEnouthCreditNote") . '" >' . $langs->trans("RemoveSituationFromCycle") . '</a></div>';
			    }
			}

			// Create next situation invoice
			if ($user->rights->facture->creer && ($object->type == 5) && ($object->statut == 1 || $object->statut == 2)) {
				if ($object->is_last_in_cycle() && $object->situation_final != 1) {
					print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?action=create&amp;type=5&amp;origin=facture&amp;originid=' . $object->id . '&amp;socid=' . $object->socid . '" >' . $langs->trans('CreateNextSituationInvoice') . '</a></div>';
				} else if (!$object->is_last_in_cycle()) {
					print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="' . $langs->trans("DisabledBecauseNotLastInCycle") . '">' . $langs->trans('CreateNextSituationInvoice') . '</a></div>';
				} else {
					print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="' . $langs->trans("DisabledBecauseFinal") . '">' . $langs->trans('CreateNextSituationInvoice') . '</a></div>';
				}
			}

			// Delete
			$isErasable = $object->is_erasable();
			if ($user->rights->facture->supprimer || ($user->rights->facture->creer && $isErasable == 1))	// isErasable = 1 means draft with temporary ref (draft can always be deleted with no need of permissions)
			{
				//var_dump($isErasable);
				if ($isErasable == -4) {
					print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="' . $langs->trans("DisabledBecausePayments") . '">' . $langs->trans('Delete') . '</a></div>';
				}
				elseif ($isErasable == -3) {
					print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="' . $langs->trans("DisabledBecauseNotLastSituationInvoice") . '">' . $langs->trans('Delete') . '</a></div>';
				}
				elseif ($isErasable == -2) {
					print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="' . $langs->trans("DisabledBecauseNotLastInvoice") . '">' . $langs->trans('Delete') . '</a></div>';
				}
				elseif ($isErasable == -1) {
					print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="' . $langs->trans("DisabledBecauseDispatchedInBookkeeping") . '">' . $langs->trans('Delete') . '</a></div>';
				}
				elseif ($isErasable <= 0)	// Any other cases
				{
					print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="' . $langs->trans("DisabledBecauseNotErasable") . '">' . $langs->trans('Delete') . '</a></div>';
				}
				elseif ($objectidnext)
				{
					print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="' . $langs->trans("DisabledBecauseReplacedInvoice") . '">' . $langs->trans('Delete') . '</a></div>';
				}
				else
				{
					print '<div class="inline-block divButAction"><a class="butActionDelete'.($conf->use_javascript_ajax?' reposition':'').'" href="' . $_SERVER["PHP_SELF"] . '?facid=' . $object->id . '&amp;action=delete">' . $langs->trans('Delete') . '</a></div>';
				}
			} else {
				print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="' . $langs->trans("NotAllowed") . '">' . $langs->trans('Delete') . '</a></div>';
			}
		}
		print '</div>';
	}

	// Select mail models is same action as presend
	if (GETPOST('modelselected','alpha')) {
		$action = 'presend';
	}
	if ($action != 'prerelance' && $action != 'presend')
	{
		print '<div class="fichecenter"><div class="fichehalfleft">';
		print '<a name="builddoc"></a>'; // ancre

		// Documents generes
		$filename = dol_sanitizeFileName($object->ref);
		$filedir = $conf->facture->dir_output . '/' . dol_sanitizeFileName($object->ref);
		$urlsource = $_SERVER['PHP_SELF'] . '?facid=' . $object->id;
		$genallowed = $user->rights->facture->lire;
		$delallowed = $user->rights->facture->creer;

		print $formfile->showdocuments('facture', $filename, $filedir, $urlsource, $genallowed, $delallowed, $object->modelpdf, 1, 0, 0, 28, 0, '', '', '', $soc->default_lang);
		$somethingshown = $formfile->numoffiles;

		// Show links to link elements
		$linktoelem = $form->showLinkToObjectBlock($object, null, array('invoice'));

		$compatibleImportElementsList = false;
		if($user->rights->facture->creer
		    && $object->statut == Facture::STATUS_DRAFT
		    && ($object->type == Facture::TYPE_STANDARD || $object->type == Facture::TYPE_REPLACEMENT || $object->type == Facture::TYPE_DEPOSIT || $object->type == Facture::TYPE_PROFORMA || $object->type == Facture::TYPE_SITUATION) )
		{
		    $compatibleImportElementsList = array('commande','propal'); // import from linked elements
		}
		$somethingshown = $form->showLinkedObjectBlock($object, $linktoelem,$compatibleImportElementsList);


		// Show online payment link
		$useonlinepayment = (! empty($conf->paypal->enabled) || ! empty($conf->stripe->enabled) || ! empty($conf->paybox->enabled));

		if ($object->statut != Facture::STATUS_DRAFT && $useonlinepayment)
		{
			print '<br><!-- Link to pay -->'."\n";
			require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';
			print showOnlinePaymentUrl('invoice', $object->ref).'<br>';
		}

		// Show direct download link
		if ($object->statut != Facture::STATUS_DRAFT && ! empty($conf->global->INVOICE_ALLOW_EXTERNAL_DOWNLOAD))
		{
			print '<br><!-- Link to download main doc -->'."\n";
			print showDirectDownloadLink($object).'<br>';
		}

		print '</div><div class="fichehalfright"><div class="ficheaddleft">';

		// List of actions on element
		include_once DOL_DOCUMENT_ROOT . '/core/class/html.formactions.class.php';
		$formactions = new FormActions($db);
		$somethingshown = $formactions->showactions($object, 'invoice', $socid, 1);

		print '</div></div></div>';
	}


	// Presend form
	$modelmail='facture_send';
	$defaulttopic='SendBillRef';
	$diroutput = $conf->facture->dir_output;
	$trackid = 'inv'.$object->id;

	include DOL_DOCUMENT_ROOT.'/core/tpl/card_presend.tpl.php';
}

llxFooter();
$db->close();
