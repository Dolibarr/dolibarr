<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Eric Seigne          <erics@rycks.com>
 * Copyright (C) 2004-2016 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2010-2015 Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2014      Jean Heimburger      <jean@tiaris.info>
 * Copyright (C) 2015      Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2015      Raphaël Doursenaud   <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2021       Frédéric France     <frederic.france@netlogic.fr>
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
 *	\file       htdocs/fourn/card.php
 *	\ingroup    fournisseur, facture
 *	\brief      Page for supplier third party card (view, edit)
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/supplier_proposal/class/supplier_proposal.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
if (isModEnabled('member')) {
	require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
}
if (isModEnabled('category')) {
	require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
}
if (!empty($conf->accounting->enabled)) {
	require_once DOL_DOCUMENT_ROOT . '/core/lib/accounting.lib.php';
}

// Load translation files required by page
$langs->loadLangs(array(
	'companies',
	'suppliers',
	'products',
	'bills',
	'orders',
	'commercial',
));

$action = GETPOST('action', 'aZ09');
$cancel = GETPOST('cancel', 'alpha');

// Security check
$id = (GETPOSTINT('socid') ? GETPOSTINT('socid') : GETPOSTINT('id'));
if ($user->socid) {
	$id = $user->socid;
}
$result = restrictedArea($user, 'societe&fournisseur', $id, '&societe', '', 'rowid');

$object = new Fournisseur($db);
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('thirdpartysupplier', 'globalcard'));

// Security check
$result = restrictedArea($user, 'societe', $id, '&societe', '', 'fk_soc', 'rowid', 0);

if ($object->id > 0) {
	if (!($object->fournisseur > 0) || !$user->hasRight("fournisseur", "lire")) {
		accessforbidden();
	}
}


/*
 * Action
 */

$parameters = array('id' => $id);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	if ($cancel) {
		$action = "";
	}

	// Set supplier accounting account
	if ($action == 'setsupplieraccountancycode' && $user->hasRight('societe', 'creer')) {
		$result = $object->fetch($id);
		$object->code_compta_fournisseur = GETPOST("supplieraccountancycode");
		$result = $object->update($object->id, $user, 1, 0, 1);
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
			$action = 'editsupplieraccountancycode';
		}
	}
	// Set vat number accounting account
	if ($action == 'settva_intra' && $user->hasRight('societe', 'creer')) {
		$result = $object->fetch($id);
		$object->tva_intra = GETPOST("tva_intra");
		$result = $object->update($object->id, $user, 1, 0, 0);
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}
	// Set payment terms of the settlement
	if ($action == 'setconditions' && $user->hasRight('societe', 'creer')) {
		$object->fetch($id);
		$result = $object->setPaymentTerms(GETPOSTINT('cond_reglement_supplier_id'));
		if ($result < 0) {
			dol_print_error($db, $object->error);
		}
	}
	// Payment mode
	if ($action == 'setmode' && $user->hasRight('societe', 'creer')) {
		$object->fetch($id);
		$result = $object->setPaymentMethods(GETPOSTINT('mode_reglement_supplier_id'));
		if ($result < 0) {
			dol_print_error($db, $object->error);
		}
	}

	// Bank account
	if ($action == 'setbankaccount' && $user->hasRight('societe', 'creer')) {
		$object->fetch($id);
		$result = $object->setBankAccount(GETPOSTINT('fk_account'));
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	// update supplier order min amount
	if ($action == 'setsupplier_order_min_amount' && $user->hasRight('societe', 'creer')) {
		$object->fetch($id);
		$object->supplier_order_min_amount = price2num(GETPOST('supplier_order_min_amount', 'alpha'));
		$result = $object->update($object->id, $user);
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	if ($action == 'update_extras' && $user->hasRight('societe', 'creer')) {
		$object->fetch($id);

		$object->oldcopy = dol_clone($object, 2);

		// Fill array 'array_options' with data from update form
		$ret = $extrafields->setOptionalsFromPost(null, $object, GETPOST('attribute', 'restricthtml'));

		if ($ret < 0) {
			$error++;
		}

		if (!$error) {
			$result = $object->insertExtraFields('COMPANY_MODIFY');
			if ($result < 0) {
				setEventMessages($object->error, $object->errors, 'errors');
				$error++;
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

$contactstatic = new Contact($db);
$form = new Form($db);

if ($id > 0 && empty($object->id)) {
	// Load data of third party
	$res = $object->fetch($id);
	if ($object->id <= 0) {
		dol_print_error($db, $object->error);
		exit(-1);
	}
}

if ($object->id > 0) {
	$title = $langs->trans("ThirdParty")." - ".$langs->trans('Supplier');
	if (getDolGlobalString('MAIN_HTML_TITLE') && preg_match('/thirdpartynameonly/', getDolGlobalString('MAIN_HTML_TITLE')) && $object->name) {
		$title = $object->name." - ".$langs->trans('Supplier');
	}
	$help_url = '';
	llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-fourn page-card');

	/*
	 * Show tabs
	 */
	$head = societe_prepare_head($object);

	print dol_get_fiche_head($head, 'supplier', $langs->trans("ThirdParty"), -1, 'company');

	$linkback = '<a href="'.DOL_URL_ROOT.'/societe/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

	dol_banner_tab($object, 'socid', $linkback, ($user->socid ? 0 : 1), 'rowid', 'nom');

	print '<div class="fichecenter"><div class="fichehalfleft">';

	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent tableforfield">';

	// Type Prospect/Customer/Supplier
	print '<tr><td class="titlefield">'.$langs->trans('NatureOfThirdParty').'</td><td>';
	print $object->getTypeUrl(1);
	print '</td></tr>';

	if (getDolGlobalString('SOCIETE_USEPREFIX')) {  // Old not used prefix field
		print '<tr><td>'.$langs->trans('Prefix').'</td><td colspan="3">'.$object->prefix_comm.'</td></tr>';
	}

	if ($object->fournisseur) {
		print '<tr>';
		print '<td class="titlefield">'.$langs->trans("SupplierCode").'</td><td>';
		print showValueWithClipboardCPButton(dol_escape_htmltag($object->code_fournisseur));
		$tmpcheck = $object->check_codefournisseur();
		if ($tmpcheck != 0 && $tmpcheck != -5) {
			print ' <span class="error">('.$langs->trans("WrongSupplierCode").')</span>';
		}
		print '</td>';
		print '</tr>';

		$langs->load('compta');
		print '<tr>';
		print '<td>';
		print $form->editfieldkey("SupplierAccountancyCode", 'supplieraccountancycode', $object->code_compta_fournisseur, $object, $user->hasRight('societe', 'creer'));
		print '</td><td>';
		print $form->editfieldval("SupplierAccountancyCode", 'supplieraccountancycode', $object->code_compta_fournisseur, $object, $user->hasRight('societe', 'creer'));
		print '</td>';
		print '</tr>';
	}

	// Assujetti a TVA ou pas
	print '<tr>';
	print '<td class="titlefield">';
	print $form->textwithpicto($langs->trans('VATIsUsed'), $langs->trans('VATIsUsedWhenSelling'));
	print '</td><td>';
	print yn($object->tva_assuj);
	print '</td>';
	print '</tr>';

	// Local Taxes
	if ($mysoc->useLocalTax(1)) {
		print '<tr><td>'.$langs->transcountry("LocalTax1IsUsed", $mysoc->country_code).'</td><td>';
		print yn($object->localtax1_assuj);
		print '</td></tr>';
	}
	if ($mysoc->useLocalTax(2)) {
		print '<tr><td>'.$langs->transcountry("LocalTax2IsUsed", $mysoc->country_code).'</td><td>';
		print yn($object->localtax2_assuj);
		print '</td></tr>';
	}

	// VAT reverse-charge by default on supplier invoice or not
	if (getDolGlobalString('ACCOUNTING_FORCE_ENABLE_VAT_REVERSE_CHARGE')) {
		print '<tr>';
		print '<td class="titlefield">';
		print $form->textwithpicto($langs->trans('VATReverseChargeByDefault'), $langs->trans('VATReverseChargeByDefaultDesc'));
		print '</td><td>';
		print '<input type="checkbox" name="vat_reverse_charge" ' . ($object->vat_reverse_charge == '1' ? ' checked' : '') . ' disabled>';
		print '</td>';
		print '</tr>';
	}

	// TVA Intra
	print '<tr><td class="nowrap">';
	//print $langs->trans('VATIntra').'</td><td>';
	$vattoshow = ($object->tva_intra ? showValueWithClipboardCPButton(dol_escape_htmltag($object->tva_intra)) : '');
	print $form->editfieldkey("VATIntra", 'tva_intra', $object->tva_intra, $object, $user->hasRight('societe', 'creer'));
	print '</td><td>';
	print $form->editfieldval("VATIntra", 'tva_intra', $vattoshow, $object, $user->hasRight('societe', 'creer'), 'string', $object->tva_intra, null, null, '', 1, '', 'id', 'auto', array('valuealreadyhtmlescaped' => 1));
	print '</td></tr>';

	// Default terms of the settlement
	$langs->load('bills');
	$form = new Form($db);
	print '<tr><td>';
	print '<table width="100%" class="nobordernopadding"><tr><td>';
	print $langs->trans('PaymentConditions');
	print '<td>';
	if (($action != 'editconditions') && $user->hasRight('societe', 'creer')) {
		print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editconditions&token='.newToken().'&socid='.$object->id.'">'.img_edit($langs->trans('SetConditions'), 1).'</a></td>';
	}
	print '</tr></table>';
	print '</td><td>';
	if ($action == 'editconditions') {
		$form->form_conditions_reglement($_SERVER['PHP_SELF'].'?socid='.$object->id, $object->cond_reglement_supplier_id, 'cond_reglement_supplier_id', 1);
	} else {
		$form->form_conditions_reglement($_SERVER['PHP_SELF'].'?socid='.$object->id, $object->cond_reglement_supplier_id, 'none');
	}
	print "</td>";
	print '</tr>';

	// Default payment mode
	print '<tr><td class="nowrap">';
	print '<table width="100%" class="nobordernopadding"><tr><td class="nowrap">';
	print $langs->trans('PaymentMode');
	print '<td>';
	if (($action != 'editmode') && $user->hasRight('societe', 'creer')) {
		print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editmode&token='.newToken().'&socid='.$object->id.'">'.img_edit($langs->trans('SetMode'), 1).'</a></td>';
	}
	print '</tr></table>';
	print '</td><td>';
	if ($action == 'editmode') {
		$form->form_modes_reglement($_SERVER['PHP_SELF'].'?socid='.$object->id, $object->mode_reglement_supplier_id, 'mode_reglement_supplier_id', 'DBIT', 1, 1);
	} else {
		$form->form_modes_reglement($_SERVER['PHP_SELF'].'?socid='.$object->id, $object->mode_reglement_supplier_id, 'none');
	}
	print "</td>";
	print '</tr>';

	if (isModEnabled("bank")) {
		// Default bank account for payments
		print '<tr><td class="nowrap">';
		print '<table width="100%" class="nobordernopadding"><tr><td class="nowrap">';
		print $langs->trans('PaymentBankAccount');
		print '<td>';
		if (($action != 'editbankaccount') && $user->hasRight('societe', 'creer')) {
			print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editbankaccount&token='.newToken().'&socid='.$object->id.'">'.img_edit($langs->trans('SetBankAccount'), 1).'</a></td>';
		}
		print '</tr></table>';
		print '</td><td>';
		if ($action == 'editbankaccount') {
			$form->formSelectAccount($_SERVER['PHP_SELF'].'?socid='.$object->id, $object->fk_account, 'fk_account', 1);
		} else {
			$form->formSelectAccount($_SERVER['PHP_SELF'].'?socid='.$object->id, $object->fk_account, 'none');
		}
		print "</td>";
		print '</tr>';
	}

	// Relative discounts (Discounts-Drawbacks-Rebates)
	print '<tr><td class="nowrap">';
	print '<table width="100%" class="nobordernopadding"><tr><td class="nowrap">';
	print $langs->trans("CustomerRelativeDiscountShort");
	print '<td><td class="right">';
	if ($user->hasRight('societe', 'creer') && !$user->socid > 0) {
		print '<a class="editfielda" href="'.DOL_URL_ROOT.'/comm/remise.php?id='.$object->id.'&backtopage='.urlencode($_SERVER["PHP_SELF"].'?socid='.$object->id).'&action=create&token='.newToken().'">'.img_edit($langs->trans("Modify")).'</a>';
	}
	print '</td></tr></table>';
	print '</td><td>'.($object->remise_supplier_percent ? '<a href="'.DOL_URL_ROOT.'/comm/remise.php?id='.$object->id.'">'.$object->remise_supplier_percent.'%</a>' : '').'</td>';
	print '</tr>';

	// Absolute discounts (Discounts-Drawbacks-Rebates)
	print '<tr><td class="nowrap">';
	print '<table width="100%" class="nobordernopadding">';
	print '<tr><td class="nowrap">';
	print $langs->trans("CustomerAbsoluteDiscountShort");
	print '<td><td class="right">';
	if ($user->hasRight('societe', 'creer') && !$user->socid > 0) {
		print '<a class="editfielda" href="'.DOL_URL_ROOT.'/comm/remx.php?id='.$object->id.'&backtopage='.urlencode($_SERVER["PHP_SELF"].'?socid='.$object->id).'&action=create&token='.newToken().'">'.img_edit($langs->trans("Modify")).'</a>';
	}
	print '</td></tr></table>';
	print '</td>';
	print '<td>';
	$amount_discount = $object->getAvailableDiscounts('', '', 0, 1);
	if ($amount_discount < 0) {
		dol_print_error($db, $object->error);
	}
	if ($amount_discount > 0) {
		print '<a href="'.DOL_URL_ROOT.'/comm/remx.php?id='.$object->id.'&backtopage='.urlencode($_SERVER["PHP_SELF"].'?socid='.$object->id).'&action=create&token='.newToken().'">'.price($amount_discount, 1, $langs, 1, -1, -1, $conf->currency).'</a>';
	}
	//else print $langs->trans("DiscountNone");
	print '</td>';
	print '</tr>';

	if (isModEnabled("supplier_order") && getDolGlobalString('ORDER_MANAGE_MIN_AMOUNT')) {
		print '<tr class="nowrap">';
		print '<td>';
		print $form->editfieldkey("OrderMinAmount", 'supplier_order_min_amount', $object->supplier_order_min_amount, $object, $user->hasRight("societe", "creer"));
		print '</td><td>';
		$limit_field_type = (getDolGlobalString('MAIN_USE_JQUERY_JEDITABLE')) ? 'numeric' : 'amount';
		print $form->editfieldval("OrderMinAmount", 'supplier_order_min_amount', $object->supplier_order_min_amount, $object, $user->hasRight("societe", "creer"), $limit_field_type, ($object->supplier_order_min_amount != '' ? price($object->supplier_order_min_amount) : ''));
		print '</td>';
		print '</tr>';
	}

	// Categories
	if (isModEnabled('category')) {
		$langs->load("categories");
		print '<tr><td>'.$langs->trans("SuppliersCategoriesShort").'</td>';
		print '<td>';
		print $form->showCategories($object->id, Categorie::TYPE_SUPPLIER, 1);
		print "</td></tr>";
	}

	// Other attributes
	$parameters = array('socid' => $object->id, 'colspan' => ' colspan="3"', 'colspanvalue' => '3');
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

	// Module Adherent
	if (isModEnabled('member')) {
		$langs->load("members");
		$langs->load("users");
		print '<tr><td>'.$langs->trans("LinkedToDolibarrMember").'</td>';
		print '<td>';
		$adh = new Adherent($db);
		$result = $adh->fetch('', '', $object->id);
		if ($result > 0) {
			$adh->ref = $adh->getFullName($langs);
			print $adh->getNomUrl(1);
		} else {
			print $langs->trans("ThirdpartyNotLinkedToMember");
		}
		print '</td>';
		print "</tr>\n";
	}

	print '</table>';


	print '</div><div class="fichehalfright">';

	$boxstat = '';

	// Nbre max d'elements des petites listes
	$MAXLIST = getDolGlobalString('MAIN_SIZE_SHORTLIST_LIMIT');

	print '<div class="underbanner underbanner-before-box clearboth"></div>';
	print '<br>';

	// Summary link
	$boxstat .= '<div class="box divboxtable box-halfright">';
	$boxstat .= '<table summary="'.dol_escape_htmltag($langs->trans("DolibarrStateBoard")).'" class="border boxtable boxtablenobottom boxtablenotop" width="100%">';
	$boxstat .= '<tr class="impair nohover"><td colspan="2" class="tdboxstats nohover">';

	if (isModEnabled('supplier_proposal')) {
		// Box proposals
		$tmp = $object->getOutstandingProposals('supplier');
		$outstandingOpened = $tmp['opened'];
		$outstandingTotal = $tmp['total_ht'];
		$outstandingTotalIncTax = $tmp['total_ttc'];
		$text = $langs->trans("OverAllSupplierProposals");
		$link = DOL_URL_ROOT.'/supplier_proposal/list.php?socid='.$object->id;
		$icon = 'bill';
		if ($link) {
			$boxstat .= '<a href="'.$link.'" class="boxstatsindicator thumbstat nobold nounderline">';
		}
		$boxstat .= '<div class="boxstats" title="'.dol_escape_htmltag($text).'">';
		$boxstat .= '<span class="boxstatstext">'.img_object("", $icon).' <span>'.$text.'</span></span><br>';
		$boxstat .= '<span class="boxstatsindicator">'.price($outstandingTotal, 1, $langs, 1, -1, -1, $conf->currency).'</span>';
		$boxstat .= '</div>';
		if ($link) {
			$boxstat .= '</a>';
		}
	}

	if (isModEnabled("supplier_order")) {
		// Box proposals
		$tmp = $object->getOutstandingOrders('supplier');
		$outstandingOpened = $tmp['opened'];
		$outstandingTotal = $tmp['total_ht'];
		$outstandingTotalIncTax = $tmp['total_ttc'];
		$text = $langs->trans("OverAllOrders");
		$link = DOL_URL_ROOT.'/fourn/commande/list.php?socid='.$object->id;
		$icon = 'bill';
		if ($link) {
			$boxstat .= '<a href="'.$link.'" class="boxstatsindicator thumbstat nobold nounderline">';
		}
		$boxstat .= '<div class="boxstats" title="'.dol_escape_htmltag($text).'">';
		$boxstat .= '<span class="boxstatstext">'.img_object("", $icon).' <span>'.$text.'</span></span><br>';
		$boxstat .= '<span class="boxstatsindicator">'.price($outstandingTotal, 1, $langs, 1, -1, -1, $conf->currency).'</span>';
		$boxstat .= '</div>';
		if ($link) {
			$boxstat .= '</a>';
		}
	}

	if (isModEnabled("supplier_invoice") && ($user->hasRight('fournisseur', 'facture', 'lire') || $user->hasRight('supplier_invoice', 'read'))) {
		$warn = '';
		$tmp = $object->getOutstandingBills('supplier');
		$outstandingOpened = $tmp['opened'];
		$outstandingTotal = $tmp['total_ht'];
		$outstandingTotalIncTax = $tmp['total_ttc'];

		$text = $langs->trans("OverAllInvoices");
		$link = DOL_URL_ROOT.'/fourn/facture/list.php?socid='.$object->id;
		$icon = 'bill';
		if ($link) {
			$boxstat .= '<a href="'.$link.'" class="boxstatsindicator thumbstat nobold nounderline">';
		}
		$boxstat .= '<div class="boxstats" title="'.dol_escape_htmltag($text).'">';
		$boxstat .= '<span class="boxstatstext">'.img_object("", $icon).' <span>'.$text.'</span></span><br>';
		$boxstat .= '<span class="boxstatsindicator">'.price($outstandingTotal, 1, $langs, 1, -1, -1, $conf->currency).'</span>';
		$boxstat .= '</div>';
		if ($link) {
			$boxstat .= '</a>';
		}

		// Box outstanding bill
		$text = $langs->trans("CurrentOutstandingBill");
		$link = DOL_URL_ROOT.'/fourn/recap-fourn.php?socid='.$object->id;
		$icon = 'bill';
		if ($link) {
			$boxstat .= '<a href="'.$link.'" class="boxstatsindicator thumbstat nobold nounderline">';
		}
		$boxstat .= '<div class="boxstats" title="'.dol_escape_htmltag($text).'">';
		$boxstat .= '<span class="boxstatstext">'.img_object("", $icon).' <span>'.$text.'</span></span><br>';
		$boxstat .= '<span class="boxstatsindicator'.($outstandingOpened > 0 ? ' amountremaintopay' : '').'">'.price($outstandingOpened, 1, $langs, 1, -1, -1, $conf->currency).$warn.'</span>';
		$boxstat .= '</div>';
		if ($link) {
			$boxstat .= '</a>';
		}

		$tmp = $object->getOutstandingBills('supplier', 1);
		$outstandingOpenedLate = $tmp['opened'];
		if ($outstandingOpened != $outstandingOpenedLate && !empty($outstandingOpenedLate)) {
			$text = $langs->trans("CurrentOutstandingBillLate");
			$link = DOL_URL_ROOT.'/fourn/recap-fourn.php?socid='.$object->id;
			$icon = 'bill';
			if ($link) {
				$boxstat .= '<a href="'.$link.'" class="boxstatsindicator thumbstat nobold nounderline">';
			}
			$boxstat .= '<div class="boxstats" title="'.dol_escape_htmltag($text).'">';
			$boxstat .= '<span class="boxstatstext">'.img_object("", $icon).' <span>'.$text.'</span></span><br>';
			$boxstat .= '<span class="boxstatsindicator'.($outstandingOpenedLate > 0 ? ' amountremaintopay' : '').'">'.price($outstandingOpenedLate, 1, $langs, 1, -1, -1, $conf->currency).$warn.'</span>';
			$boxstat .= '</div>';
			if ($link) {
				$boxstat .= '</a>';
			}
		}
	}


	$parameters = array();
	$reshook = $hookmanager->executeHooks('addMoreBoxStatsSupplier', $parameters, $object, $action);
	if (empty($reshook)) {
		$boxstat .= $hookmanager->resPrint;
	}

	$boxstat .= '</td></tr>';
	$boxstat .= '</table>';
	$boxstat .= '</div>';

	print $boxstat;


	$MAXLIST = getDolGlobalString('MAIN_SIZE_SHORTLIST_LIMIT');


	/*
	 * List of products prices
	 */
	if (isModEnabled("product") || isModEnabled("service")) {
		$langs->load("products");
		//Query from product/liste.php
		$sql = 'SELECT p.rowid, p.ref, p.label, p.fk_product_type, p.entity, p.tosell as status, p.tobuy as status_buy, p.tobatch as status_batch,';
		$sql .= ' pfp.tms, pfp.ref_fourn as supplier_ref, pfp.price, pfp.quantity, pfp.unitprice';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'product_fournisseur_price as pfp';
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product as p ON p.rowid = pfp.fk_product";
		$sql .= ' WHERE p.entity IN ('.getEntity('product').')';
		$sql .= ' AND pfp.fk_soc = '.((int) $object->id);
		$sql .= $db->order('pfp.tms', 'desc');
		$sql .= $db->plimit($MAXLIST);

		$query = $db->query($sql);
		if (!$query) {
			dol_print_error($db);
		}

		$num = $db->num_rows($query);

		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder centpercent lastrecordtable">';
		print '<tr class="liste_titre'.(($num == 0) ? ' nobottom' : '').'">';
		print '<td colspan="2">'.$langs->trans("ProductsAndServices").'</td><td class="right" colspan="2">';
		print '<a class="notasortlink" href="'.DOL_URL_ROOT.'/fourn/product/list.php?fourn_id='.$object->id.'"><span class="hideonsmartphone">'.$langs->trans("AllProductReferencesOfSupplier").'</span><span class="badge marginleftonlyshort">'.$object->nbOfProductRefs().'</span>';
		print '</a></td></tr>';

		$return = array();
		if ($num > 0) {
			$productstatic = new Product($db);

			while ($objp = $db->fetch_object($query)) {
				$productstatic->id = $objp->rowid;
				$productstatic->ref = $objp->ref;
				$productstatic->label = $objp->label;
				$productstatic->type = $objp->fk_product_type;
				$productstatic->entity = $objp->entity;
				$productstatic->status = $objp->status;
				$productstatic->status_buy = $objp->status_buy;
				$productstatic->status_batch = $objp->status_batch;

				print '<tr class="oddeven">';
				print '<td class="nowrap">';
				print $productstatic->getNomUrl(1);
				print '</td>';
				print '<td>';
				print dol_escape_htmltag($objp->supplier_ref);
				print '</td>';
				print '<td class="tdoverflowmax200">';
				print dol_trunc(dol_htmlentities($objp->label), 30);
				print '</td>';
				//print '<td class="right" class="nowrap">'.dol_print_date($objp->tms, 'day').'</td>';
				print '<td class="right">';
				//print (isset($objp->unitprice) ? price($objp->unitprice) : '');
				if (isset($objp->price)) {
					print '<span class="amount">'.price($objp->price).'</span>';
					if ($objp->quantity > 1) {
						print ' / ';
						print $objp->quantity;
					}
				}
				print '</td>';
				print '</tr>';
			}
		} else {
			print '<tr><td colspan="4"><span class="opacitymedium">'.$langs->trans("NoProductPriceDefinedForThisSupplier").'</span></td></tr>';
		}

		print '</table>';
		print '</div>';
	}


	/*
	 * Latest supplier proposal
	 */
	$proposalstatic = new SupplierProposal($db);

	if ($user->hasRight("supplier_proposal", "lire")) {
		$langs->loadLangs(array("supplier_proposal"));

		$sql = "SELECT p.rowid, p.ref, p.date_valid as dc, p.fk_statut, p.total_ht, p.total_tva, p.total_ttc";
		$sql .= " FROM ".MAIN_DB_PREFIX."supplier_proposal as p ";
		$sql .= " WHERE p.fk_soc = ".((int) $object->id);
		$sql .= " AND p.entity IN (".getEntity('supplier_proposal').")";
		$sql .= " ORDER BY p.date_valid DESC";
		$sql .= $db->plimit($MAXLIST);

		$resql = $db->query($sql);
		if ($resql) {
			$i = 0;
			$num = $db->num_rows($resql);

			if ($num > 0) {
				print '<div class="div-table-responsive-no-min">';
				print '<table class="noborder centpercent lastrecordtable">';

				print '<tr class="liste_titre">';
				print '<td colspan="3">';
				print '<table class="nobordernopadding centpercent"><tr><td>'.$langs->trans("LastSupplierProposals", ($num < $MAXLIST ? "" : $MAXLIST)).'</td>';
				print '<td class="right"><a class="notasortlink" href="'.DOL_URL_ROOT.'/supplier_proposal/list.php?socid='.$object->id.'"><span class="hideonsmartphone">'.$langs->trans("AllPriceRequests").'</span><span class="badge marginleftonlyshort">'.$num.'</span></td>';
				// print '<td width="20px" class="right"><a href="'.DOL_URL_ROOT.'/supplier_proposal/stats/index.php?mode=supplier&socid='.$object->id.'">'.img_picto($langs->trans("Statistics"), 'stats').'</a></td>';
				print '</tr></table>';
				print '</td></tr>';
			}

			while ($i < $num && $i <= $MAXLIST) {
				$obj = $db->fetch_object($resql);

				print '<tr class="oddeven">';
				print '<td class="nowrap">';
				$proposalstatic->id = $obj->rowid;
				$proposalstatic->ref = $obj->ref;
				$proposalstatic->total_ht = $obj->total_ht;
				$proposalstatic->total_tva = $obj->total_tva;
				$proposalstatic->total_ttc = $obj->total_ttc;
				print $proposalstatic->getNomUrl(1);
				print '</td>';
				print '<td class="center" width="80">';
				if ($obj->dc) {
					print dol_print_date($db->jdate($obj->dc), 'day');
				} else {
					print "-";
				}
				print '</td>';
				print '<td class="right" class="nowrap">'.$proposalstatic->LibStatut($obj->fk_statut, 5).'</td>';
				print '</tr>';
				$i++;
			}
			$db->free($resql);

			if ($num > 0) {
				print "</table></div>";
			}
		} else {
			dol_print_error($db);
		}
	}

	/*
	 * Latest supplier orders
	 */
	$orderstatic = new CommandeFournisseur($db);

	if ($user->hasRight("fournisseur", "commande", "lire")) {
		// TODO move to DAO class
		// Check if there are supplier orders billable
		$sql2 = 'SELECT s.nom, s.rowid as socid, s.client, c.rowid, c.ref, c.total_ht, c.ref_supplier,';
		$sql2 .= ' c.date_valid, c.date_commande, c.date_livraison, c.fk_statut';
		$sql2 .= ' FROM '.MAIN_DB_PREFIX.'societe as s';
		$sql2 .= ', '.MAIN_DB_PREFIX.'commande_fournisseur as c';
		$sql2 .= ' WHERE c.fk_soc = s.rowid';
		$sql2 .= " AND c.entity IN (".getEntity('commande_fournisseur').")";
		$sql2 .= ' AND s.rowid = '.((int) $object->id);
		// Show orders we can bill
		if (!getDolGlobalString('SUPPLIER_ORDER_TO_INVOICE_STATUS')) {
			$sql2 .= " AND c.fk_statut IN (".$db->sanitize(CommandeFournisseur::STATUS_RECEIVED_COMPLETELY).")"; //  Must match filter in htdocs/fourn/commande/list.php
		} else {
			// CommandeFournisseur::STATUS_ORDERSENT.", ".CommandeFournisseur::STATUS_RECEIVED_PARTIALLY.", ".CommandeFournisseur::STATUS_RECEIVED_COMPLETELY
			$sql2 .= " AND c.fk_statut IN (".$db->sanitize(getDolGlobalString('SUPPLIER_ORDER_TO_INVOICE_STATUS')).")";
		}
		$sql2 .= " AND c.billed = 0";
		// Find order that are not already invoiced
		// just need to check received status because we have the billed status now
		//$sql2 .= " AND c.rowid NOT IN (SELECT fk_source FROM " . MAIN_DB_PREFIX . "element_element WHERE targettype='invoice_supplier')";
		$resql2 = $db->query($sql2);
		if ($resql2) {
			$orders2invoice = $db->num_rows($resql2);
			$db->free($resql2);
		} else {
			setEventMessages($db->lasterror(), null, 'errors');
		}

		// TODO move to DAO class
		$sql = "SELECT count(p.rowid) as total";
		$sql .= " FROM ".MAIN_DB_PREFIX."commande_fournisseur as p";
		$sql .= " WHERE p.fk_soc = ".((int) $object->id);
		$sql .= " AND p.entity IN (".getEntity('commande_fournisseur').")";
		$resql = $db->query($sql);
		if ($resql) {
			$object_count = $db->fetch_object($resql);
			$num = $object_count->total;
		}

		$sql  = "SELECT p.rowid,p.ref, p.date_commande as date, p.fk_statut, p.total_ht, p.total_tva, p.total_ttc";
		$sql .= " FROM ".MAIN_DB_PREFIX."commande_fournisseur as p";
		$sql .= " WHERE p.fk_soc = ".((int) $object->id);
		$sql .= " AND p.entity IN (".getEntity('commande_fournisseur').")";
		$sql .= " ORDER BY p.date_commande DESC";
		$sql .= $db->plimit($MAXLIST);

		$resql = $db->query($sql);
		if ($resql) {
			$i = 0;

			if ($num > 0) {
				print '<div class="div-table-responsive-no-min">';
				print '<table class="noborder centpercent lastrecordtable">';

				print '<tr class="liste_titre">';
				print '<td colspan="4">';
				print '<table class="nobordernopadding" width="100%"><tr><td>'.$langs->trans("LastSupplierOrders", ($num < $MAXLIST ? "" : $MAXLIST)).'</td>';
				print '<td class="right"><a class="notasortlink" href="'.DOL_URL_ROOT.'/fourn/commande/list.php?socid='.$object->id.'"><span class="hideonsmartphone">'.$langs->trans("AllOrders").'</span><span class="badge marginleftonlyshort">'.$num.'</span></td>';
				print '<td width="20px" class="right"><a href="'.DOL_URL_ROOT.'/commande/stats/index.php?mode=supplier&socid='.$object->id.'">'.img_picto($langs->trans("Statistics"), 'stats').'</a></td>';
				print '</tr></table>';
				print '</td></tr>';
			}

			while ($i < $num && $i < $MAXLIST) {
				$obj = $db->fetch_object($resql);

				$orderstatic->id = $obj->rowid;
				$orderstatic->ref = $obj->ref;
				$orderstatic->total_ht = $obj->total_ht;
				$orderstatic->total_tva = $obj->total_tva;
				$orderstatic->total_ttc = $obj->total_ttc;
				$orderstatic->date = $db->jdate($obj->date);

				print '<tr class="oddeven">';
				print '<td class="nowraponall">';
				print $orderstatic->getNomUrl(1);
				print '</td>';
				print '<td class="center" width="80">';
				if ($obj->date) {
					print dol_print_date($orderstatic->date, 'day');
				}
				print '</td>';
				print '<td class="right nowrap"><span class="amount">'.price($orderstatic->total_ttc).'</span></td>';
				print '<td class="right" class="nowrap">'.$orderstatic->LibStatut($obj->fk_statut, 5).'</td>';
				print '</tr>';
				$i++;
			}
			$db->free($resql);

			if ($num > 0) {
				print "</table></div>";
			}
		} else {
			dol_print_error($db);
		}
	}

	/*
	 * Latest supplier invoices
	 */

	$langs->load('bills');
	$facturestatic = new FactureFournisseur($db);

	if ($user->hasRight('fournisseur', 'facture', 'lire')) {
		// TODO move to DAO class
		$sql = 'SELECT f.rowid, f.libelle as label, f.ref, f.ref_supplier, f.fk_statut, f.datef as df, f.total_ht, f.total_tva, f.total_ttc, f.paye,';
		$sql .= ' SUM(pf.amount) as am';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'facture_fourn as f';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'paiementfourn_facturefourn as pf ON f.rowid=pf.fk_facturefourn';
		$sql .= ' WHERE f.fk_soc = '.((int) $object->id);
		$sql .= " AND f.entity IN (".getEntity('facture_fourn').")";
		$sql .= ' GROUP BY f.rowid,f.libelle,f.ref,f.ref_supplier,f.fk_statut,f.datef,f.total_ht,f.total_tva,f.total_ttc,f.paye';
		$sql .= ' ORDER BY f.datef DESC';
		$resql = $db->query($sql);
		if ($resql) {
			$i = 0;
			$num = $db->num_rows($resql);
			if ($num > 0) {
				print '<div class="div-table-responsive-no-min">';
				print '<table class="noborder centpercent lastrecordtable">';

				print '<tr class="liste_titre">';
				print '<td colspan="4">';
				print '<table class="nobordernopadding" width="100%"><tr><td>'.$langs->trans('LastSuppliersBills', ($num <= $MAXLIST ? "" : $MAXLIST)).'</td>';
				print '<td class="right"><a class="notasortlink" href="'.DOL_URL_ROOT.'/fourn/facture/list.php?socid='.$object->id.'"><span class="hideonsmartphone">'.$langs->trans('AllBills').'</span><span class="badge marginleftonlyshort">'.$num.'</span></td>';
				print '<td width="20px" class="right"><a href="'.DOL_URL_ROOT.'/compta/facture/stats/index.php?mode=supplier&socid='.$object->id.'">'.img_picto($langs->trans("Statistics"), 'stats').'</a></td>';
				print '</tr></table>';
				print '</td></tr>';
			}

			while ($i < min($num, $MAXLIST)) {
				$obj = $db->fetch_object($resql);

				$facturestatic->id = $obj->rowid;
				$facturestatic->ref = ($obj->ref ? $obj->ref : $obj->rowid);
				$facturestatic->ref_supplier = $obj->ref_supplier;
				$facturestatic->libelle = $obj->label; // deprecated
				$facturestatic->label = $obj->label;
				$facturestatic->total_ht = $obj->total_ht;
				$facturestatic->total_tva = $obj->total_tva;
				$facturestatic->total_ttc = $obj->total_ttc;
				$facturestatic->date = $db->jdate($obj->df);

				print '<tr class="oddeven">';
				print '<td class="tdoverflowmax200">';
				print '<span class="nowraponall">'.$facturestatic->getNomUrl(1).'</span>';
				print $obj->ref_supplier ? ' - '.$obj->ref_supplier : '';
				print($obj->label ? ' - ' : '').dol_trunc($obj->label, 14);
				print '</td>';
				print '<td class="center nowrap">'.dol_print_date($facturestatic->date, 'day').'</td>';
				print '<td class="right nowrap"><span class="amount">'.price($facturestatic->total_ttc).'</span></td>';
				print '<td class="right nowrap">';
				print $facturestatic->LibStatut($obj->paye, $obj->fk_statut, 5, $obj->am);
				print '</td>';
				print '</tr>';
				$i++;
			}
			$db->free($resql);
			if ($num > 0) {
				print '</table></div>';
			}
		} else {
			dol_print_error($db);
		}
	}

	// Allow external modules to add their own shortlist of recent objects
	$parameters = array();
	$reshook = $hookmanager->executeHooks('addMoreRecentObjects', $parameters, $object, $action);
	if ($reshook < 0) {
		setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
	} else {
		print $hookmanager->resPrint;
	}

	print '</div></div>';
	print '<div class="clearboth"></div>';

	print dol_get_fiche_end();


	/*
	 * Action bar
	 */
	print '<div class="tabsAction">';

	$parameters = array();
	$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been
	// modified by hook
	if (empty($reshook)) {
		if ($object->status != 1) {
			print dolGetButtonAction($langs->trans('ThirdPartyIsClosed'), $langs->trans('ThirdPartyIsClosed'), 'default', $_SERVER['PHP_SELF'].'#', '', false);
		}

		if (isModEnabled('supplier_proposal') && $user->hasRight("supplier_proposal", "creer")) {
			$langs->load("supplier_proposal");
			if ($object->status == 1) {
				print dolGetButtonAction('', $langs->trans('AddSupplierProposal'), 'default', DOL_URL_ROOT.'/supplier_proposal/card.php?action=create&amp;socid='.$object->id, '');
			} else {
				print dolGetButtonAction($langs->trans('ThirdPartyIsClosed'), $langs->trans('AddSupplierProposal'), 'default', $_SERVER['PHP_SELF'].'#', '', false);
			}
		}

		if ($user->hasRight('fournisseur', 'commande', 'creer') || $user->hasRight('supplier_order', 'creer')) {
			$langs->load("orders");
			if ($object->status == 1) {
				print dolGetButtonAction('', $langs->trans('AddSupplierOrderShort'), 'default', DOL_URL_ROOT.'/fourn/commande/card.php?action=create&amp;token='.newToken().'&amp;socid='.$object->id, '');
			} else {
				print dolGetButtonAction($langs->trans('ThirdPartyIsClosed'), $langs->trans('AddSupplierOrderShort'), 'default', $_SERVER['PHP_SELF'].'#', '', false);
			}
		}

		if ($user->hasRight('fournisseur', 'facture', 'creer') || $user->hasRight('supplier_invoice', 'creer')) {
			if (!empty($orders2invoice) && $orders2invoice > 0) {
				if ($object->status == 1) {
					// Company is open
					print dolGetButtonAction('', $langs->trans('CreateInvoiceForThisSupplier'), 'default', DOL_URL_ROOT.'/fourn/commande/list.php?socid='.$object->id.'&amp;search_billed=0&amp;autoselectall=1', '');
				} else {
					print dolGetButtonAction('', $langs->trans('CreateInvoiceForThisCustomer'), 'default', $_SERVER['PHP_SELF'].'#', '', false);
				}
			} else {
				print dolGetButtonAction($langs->trans("NoOrdersToInvoice").' ('.$langs->trans("WithReceptionFinished").')', $langs->trans('CreateInvoiceForThisCustomer'), 'default', $_SERVER['PHP_SELF'].'#', '', false);
			}
		}

		if ($user->hasRight('fournisseur', 'facture', 'creer') || $user->hasRight('supplier_invoice', 'creer')) {
			$langs->load("bills");
			if ($object->status == 1) {
				print dolGetButtonAction('', $langs->trans('AddBill'), 'default', DOL_URL_ROOT.'/fourn/facture/card.php?action=create&amp;socid='.$object->id, '');
			} else {
				print dolGetButtonAction($langs->trans('ThirdPartyIsClosed'), $langs->trans('AddBill'), 'default', $_SERVER['PHP_SELF'].'#', '', false);
			}
		}

		// Add action
		if (isModEnabled('agenda') && getDolGlobalString('MAIN_REPEATTASKONEACHTAB') && $object->status == 1) {
			if ($user->hasRight("agenda", "myactions", "create")) {
				print dolGetButtonAction('', $langs->trans('AddAction'), 'default', DOL_URL_ROOT.'/comm/action/card.php?action=create&amp;socid='.$object->id, '');
			} else {
				print dolGetButtonAction($langs->trans('NotAllowed'), $langs->trans('AddAction'), 'default', $_SERVER['PHP_SELF'].'#', '', false);
			}
		}
	}

	print '</div>';


	if (getDolGlobalString('MAIN_DUPLICATE_CONTACTS_TAB_ON_MAIN_CARD')) {
		print '<br>';
		// List of contacts
		show_contacts($conf, $langs, $db, $object, $_SERVER["PHP_SELF"].'?socid='.$object->id);
	}

	if (getDolGlobalString('MAIN_REPEATTASKONEACHTAB')) {
		print load_fiche_titre($langs->trans("ActionsOnCompany"), '', '');

		// List of todo actions
		show_actions_todo($conf, $langs, $db, $object);

		// List of done actions
		show_actions_done($conf, $langs, $db, $object);
	}
} else {
	dol_print_error($db);
}

// End of page
llxFooter();
$db->close();
