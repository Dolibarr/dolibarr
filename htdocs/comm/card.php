<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville        <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2020 Laurent Destailleur         <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne                 <eric.seigne@ryxeo.com>
 * Copyright (C) 2006      Andre Cianfarani            <acianfa@free.fr>
 * Copyright (C) 2005-2017 Regis Houssin               <regis.houssin@inodbox.com>
 * Copyright (C) 2008      Raphael Bertrand (Resultic) <raphael.bertrand@resultic.fr>
 * Copyright (C) 2010-2020 Juanjo Menent               <jmenent@2byte.es>
 * Copyright (C) 2013      Alexandre Spangaro          <aspangaro@open-dsi.fr>
 * Copyright (C) 2021-2024  Frédéric France             <frederic.france@free.fr>
 * Copyright (C) 2015      Marcos García               <marcosgdf@gmail.com>
 * Copyright (C) 2020      Open-Dsi         		   <support@open-dsi.fr>
 * Copyright (C) 2022      Anthony Berton     			<anthony.berton@bb2a.fr>
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
 *       \file       htdocs/comm/card.php
 *       \ingroup    commercial compta
 *       \brief      Page to show customer card of a third party
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/client.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
if (isModEnabled('invoice')) {
	require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
	require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture-rec.class.php';
}
if (isModEnabled("propal")) {
	require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
}
if (isModEnabled('order')) {
	require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
}
if (isModEnabled("shipping")) {
	require_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';
}
if (isModEnabled('contract')) {
	require_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';
}
if (isModEnabled('member')) {
	require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
}
if (isModEnabled('intervention')) {
	require_once DOL_DOCUMENT_ROOT.'/fichinter/class/fichinter.class.php';
}

// Load translation files required by the page
$langs->loadLangs(array('companies', 'banks'));

if (isModEnabled('contract')) {
	$langs->load("contracts");
}
if (isModEnabled('order')) {
	$langs->load("orders");
}
if (isModEnabled("shipping")) {
	$langs->load("sendings");
}
if (isModEnabled('invoice')) {
	$langs->load("bills");
}
if (isModEnabled('project')) {
	$langs->load("projects");
}
if (isModEnabled('intervention')) {
	$langs->load("interventions");
}
if (isModEnabled('notification')) {
	$langs->load("mails");
}

$action = GETPOST('action', 'aZ09');

$id = (GETPOSTINT('socid') ? GETPOSTINT('socid') : GETPOSTINT('id'));

$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
if (empty($page) || $page == -1) {
	$page = 0;
}     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortorder) {
	$sortorder = "ASC";
}
if (!$sortfield) {
	$sortfield = "nom";
}
$cancel = GETPOST('cancel', 'alpha');

$object = new Client($db);
$extrafields = new ExtraFields($db);
$formfile = new FormFile($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('thirdpartycomm', 'globalcard'));

$now = dol_now();

if ($id > 0 && empty($object->id)) {
	// Load data of third party
	$res = $object->fetch($id);
	if ($object->id < 0) {
		dol_print_error($db, $object->error, $object->errors);
	}
}
if ($object->id > 0) {
	if (!($object->client > 0) || !$user->hasRight('societe', 'lire')) {
		accessforbidden();
	}
}

// Security check
if ($user->socid > 0) {
	$id = $user->socid;
}
$result = restrictedArea($user, 'societe', $object->id, '&societe', '', 'fk_soc', 'rowid', 0);


/*
 * Actions
 */

$parameters = array('id' => $id, 'socid' => $id);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	if ($cancel) {
		$action = "";
	}

	// Set accountancy code
	if ($action == 'setcustomeraccountancycode' && $user->hasRight('societe', 'creer')) {
		$result = $object->fetch($id);
		$object->code_compta_client = GETPOST("customeraccountancycode");
		$object->code_compta = $object->code_compta_client; // For Backward compatibility
		$result = $object->update($object->id, $user, 1, 1, 0);
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
			$action = 'editcustomeraccountancycode';
		}
	}

	// Payment terms of the settlement
	if ($action == 'setconditions' && $user->hasRight('societe', 'creer')) {
		$object->fetch($id);
		$result = $object->setPaymentTerms(GETPOSTINT('cond_reglement_id'), GETPOSTINT('cond_reglement_id_deposit_percent'));
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	// Payment mode
	if ($action == 'setmode' && $user->hasRight('societe', 'creer')) {
		$object->fetch($id);
		$result = $object->setPaymentMethods(GETPOSTINT('mode_reglement_id'));
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	// Transport mode
	if ($action == 'settransportmode' && $user->hasRight('societe', 'creer')) {
		$object->fetch($id);
		$result = $object->setTransportMode(GETPOST('transport_mode_id', 'alpha'));
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
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

	// customer preferred shipping method
	if ($action == 'setshippingmethod' && $user->hasRight('societe', 'creer')) {
		$object->fetch($id);
		$result = $object->setShippingMethod(GETPOSTINT('shipping_method_id'));
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	// assujetissement a la TVA
	if ($action == 'setassujtva' && $user->hasRight('societe', 'creer')) {
		$object->fetch($id);
		$object->tva_assuj = GETPOSTINT('assujtva_value');
		$result = $object->update($object->id, $user);
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	// set prospect level
	if ($action == 'setprospectlevel' && $user->hasRight('societe', 'creer')) {
		$object->fetch($id);
		$object->fk_prospectlevel = GETPOST('prospect_level_id', 'alpha');
		$result = $object->update($object->id, $user);
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	// set communication status
	if ($action == 'setstcomm' && $user->hasRight('societe', 'creer')) {
		$object->fetch($id);
		$object->stcomm_id = dol_getIdFromCode($db, GETPOST('stcomm', 'alpha'), 'c_stcomm');
		$result = $object->update($object->id, $user);
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		} else {
			$result = $object->fetch($object->id);
		}
	}

	// update outstandng limit
	if ($action == 'setoutstanding_limit' && $user->hasRight('societe', 'creer')) {
		$object->fetch($id);
		$object->outstanding_limit = GETPOST('outstanding_limit');
		$result = $object->update($object->id, $user);
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	// update order min amount
	if ($action == 'setorder_min_amount' && $user->hasRight('societe', 'creer')) {
		$object->fetch($id);
		$object->order_min_amount = price2num(GETPOST('order_min_amount', 'alpha'));
		$result = $object->update($object->id, $user);
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	// Set sales representatives
	if ($action == 'set_salesrepresentatives' && $user->hasRight('societe', 'creer')) {
		$object->fetch($id);
		$result = $object->setSalesRep(GETPOST('commercial', 'array'));
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

	// warehouse
	if ($action == 'setwarehouse' && $user->hasRight('societe', 'creer')) {
		$result = $object->setWarehouse(GETPOSTINT('fk_warehouse'));
	}
}


/*
 * View
 */

$contactstatic = new Contact($db);
$userstatic = new User($db);
$form = new Form($db);
$formcompany = new FormCompany($db);
$project = new Project($db);

$title = $langs->trans("ThirdParty")." - ".$langs->trans('Customer');
if (getDolGlobalString('MAIN_HTML_TITLE') && preg_match('/thirdpartynameonly/', getDolGlobalString('MAIN_HTML_TITLE')) && $object->name) {
	$title = $object->name." - ".$langs->trans('Customer');
}

$help_url = 'EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas|DE:Modul_Geschäftspartner';

llxHeader('', $title, $help_url);

if ($object->id > 0) {
	$head = societe_prepare_head($object);

	print dol_get_fiche_head($head, 'customer', $langs->trans("ThirdParty"), -1, 'company');

	$linkback = '<a href="'.DOL_URL_ROOT.'/societe/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

	dol_banner_tab($object, 'socid', $linkback, ($user->socid ? 0 : 1), 'rowid', 'nom');

	print '<div class="fichecenter"><div class="fichehalfleft">';

	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent tableforfield">';

	// Type Prospect/Customer/Supplier
	print '<tr><td class="titlefield">'.$langs->trans('NatureOfThirdParty').'</td><td>';
	print $object->getTypeUrl(1);
	print '</td></tr>';

	// Prefix
	if (getDolGlobalString('SOCIETE_USEPREFIX')) {  // Old not used prefix field
		print '<tr><td>'.$langs->trans("Prefix").'</td><td>';
		print($object->prefix_comm ? $object->prefix_comm : '&nbsp;');
		print '</td></tr>';
	}

	if ($object->client) {
		$langs->load("compta");

		print '<tr><td>';
		print $langs->trans('CustomerCode').'</td><td>';
		print showValueWithClipboardCPButton(dol_escape_htmltag($object->code_client));
		$tmpcheck = $object->check_codeclient();
		if ($tmpcheck != 0 && $tmpcheck != -5) {
			print ' <span class="error">('.$langs->trans("WrongCustomerCode").')</span>';
		}
		print '</td></tr>';

		print '<tr>';
		print '<td>';
		print $form->editfieldkey("CustomerAccountancyCode", 'customeraccountancycode', $object->code_compta_client, $object, $user->hasRight('societe', 'creer'));
		print '</td><td>';
		print $form->editfieldval("CustomerAccountancyCode", 'customeraccountancycode', $object->code_compta_client, $object, $user->hasRight('societe', 'creer'));
		print '</td>';
		print '</tr>';
	}

	// This fields are used to know VAT to include in an invoice when the thirdparty is making a sale, so when it is a supplier.
	// We don't need them into customer profile.
	// Except for spain and localtax where localtax depends on buyer and not seller

	// VAT is used
	/*
	print '<tr>';
	print '<td class="nowrap">';
	print $form->textwithpicto($langs->trans('VATIsUsed'),$langs->trans('VATIsUsedWhenSelling'));
	print '</td>';
	print '<td>';
	print yn($object->tva_assuj);
	print '</td>';
	print '</tr>';
	*/

	if ($mysoc->country_code == 'ES') {
		// Local Taxes
		if ($mysoc->localtax1_assuj == "1") {
			print '<tr><td class="nowrap">'.$langs->transcountry("LocalTax1IsUsed", $mysoc->country_code).'</td><td>';
			print yn($object->localtax1_assuj);
			print '</td></tr>';
		}
		if ($mysoc->localtax1_assuj == "1") {
			print '<tr><td class="nowrap">'.$langs->transcountry("LocalTax2IsUsed", $mysoc->country_code).'</td><td>';
			print yn($object->localtax2_assuj);
			print '</td></tr>';
		}
	}

	// TVA Intra
	print '<tr><td class="nowrap">'.$langs->trans('VATIntra').'</td><td>';
	print showValueWithClipboardCPButton(dol_escape_htmltag($object->tva_intra));
	print '</td></tr>';

	// default terms of the settlement
	$langs->load('bills');
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
		$form->form_conditions_reglement($_SERVER['PHP_SELF'].'?socid='.$object->id, $object->cond_reglement_id, 'cond_reglement_id', 1, '', 1, $object->deposit_percent);
	} else {
		$form->form_conditions_reglement($_SERVER['PHP_SELF'].'?socid='.$object->id, $object->cond_reglement_id, 'none', 0, '', 1, $object->deposit_percent);
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
		$form->form_modes_reglement($_SERVER['PHP_SELF'].'?socid='.$object->id, $object->mode_reglement_id, 'mode_reglement_id', 'CRDT', 1, 1);
	} else {
		$form->form_modes_reglement($_SERVER['PHP_SELF'].'?socid='.$object->id, $object->mode_reglement_id, 'none');
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

	$isCustomer = ($object->client == 1 || $object->client == 3);

	// Relative discounts (Discounts-Drawbacks-Rebates)
	if ($isCustomer) {
		print '<tr><td class="nowrap">';
		print '<table width="100%" class="nobordernopadding"><tr><td class="nowrap">';
		print $langs->trans("CustomerRelativeDiscountShort");
		print '<td><td class="right">';
		if ($user->hasRight('societe', 'creer') && !$user->socid > 0) {
			print '<a class="editfielda" href="'.DOL_URL_ROOT.'/comm/remise.php?id='.$object->id.'&backtopage='.urlencode($_SERVER["PHP_SELF"].'?socid='.$object->id).'&action=create&token='.newToken().'">'.img_edit($langs->trans("Modify")).'</a>';
		}
		print '</td></tr></table>';
		print '</td><td>'.($object->remise_percent ? '<a href="'.DOL_URL_ROOT.'/comm/remise.php?id='.$object->id.'">'.$object->remise_percent.'%</a>' : '').'</td>';
		print '</tr>';

		// Absolute discounts (Discounts-Drawbacks-Rebates)
		print '<tr><td class="nowrap">';
		print '<table width="100%" class="nobordernopadding">';
		print '<tr><td class="nowrap">';
		print $langs->trans("CustomerAbsoluteDiscountShort");
		print '<td><td class="right">';
		if ($user->hasRight('societe', 'creer') && !$user->socid > 0) {
			print '<a class="editfielda" href="'.DOL_URL_ROOT.'/comm/remx.php?id='.$object->id.'&backtopage='.urlencode($_SERVER["PHP_SELF"].'?socid='.$object->id).'&action=create_remise&token='.newToken().'">'.img_edit($langs->trans("Modify")).'</a>';
		}
		print '</td></tr></table>';
		print '</td>';
		print '<td>';
		$amount_discount = $object->getAvailableDiscounts();
		if ($amount_discount < 0) {
			dol_print_error($db, $object->error);
		}
		if ($amount_discount > 0) {
			print '<a href="'.DOL_URL_ROOT.'/comm/remx.php?id='.$object->id.'&backtopage='.urlencode($_SERVER["PHP_SELF"].'?socid='.$object->id).'&action=create&token='.newToken().'">'.price($amount_discount, 1, $langs, 1, -1, -1, $conf->currency).'</a>';
		}
		//else print $langs->trans("DiscountNone");
		print '</td>';
		print '</tr>';
	}

	$limit_field_type = '';
	// Max outstanding bill
	if ($object->client) {
		print '<tr class="nowrap">';
		print '<td>';
		print $form->editfieldkey("OutstandingBill", 'outstanding_limit', $object->outstanding_limit, $object, $user->hasRight('societe', 'creer'));
		print '</td><td>';
		$limit_field_type = (getDolGlobalString('MAIN_USE_JQUERY_JEDITABLE')) ? 'numeric' : 'amount';
		print $form->editfieldval("OutstandingBill", 'outstanding_limit', $object->outstanding_limit, $object, $user->hasRight('societe', 'creer'), $limit_field_type, ($object->outstanding_limit != '' ? price($object->outstanding_limit) : ''));
		print '</td>';
		print '</tr>';
	}

	if ($object->client) {
		if (isModEnabled('order') && getDolGlobalString('ORDER_MANAGE_MIN_AMOUNT')) {
			print '<!-- Minimum amount for orders -->'."\n";
			print '<tr class="nowrap">';
			print '<td>';
			print $form->editfieldkey("OrderMinAmount", 'order_min_amount', $object->order_min_amount, $object, $user->hasRight('societe', 'creer'));
			print '</td><td>';
			print $form->editfieldval("OrderMinAmount", 'order_min_amount', $object->order_min_amount, $object, $user->hasRight('societe', 'creer'), $limit_field_type, ($object->order_min_amount != '' ? price($object->order_min_amount) : ''));
			print '</td>';
			print '</tr>';
		}
	}


	// Multiprice level
	if (getDolGlobalString('PRODUIT_MULTIPRICES') || getDolGlobalString('PRODUIT_CUSTOMER_PRICES_BY_QTY_MULTIPRICES') || getDolGlobalString('PRODUIT_CUSTOMER_PRICES_AND_MULTIPRICES')) {
		print '<tr><td class="nowrap">';
		print '<table width="100%" class="nobordernopadding"><tr><td class="nowrap">';
		print $langs->trans("PriceLevel");
		print '<td><td class="right">';
		if ($user->hasRight('societe', 'creer')) {
			print '<a class="editfielda" href="'.DOL_URL_ROOT.'/comm/multiprix.php?id='.$object->id.'">'.img_edit($langs->trans("Modify")).'</a>';
		}
		print '</td></tr></table>';
		print '</td><td>';
		print $object->price_level;
		$keyforlabel = 'PRODUIT_MULTIPRICES_LABEL'.$object->price_level;
		if (getDolGlobalString($keyforlabel)) {
			print ' - '.$langs->trans(getDolGlobalString($keyforlabel));
		}
		print "</td>";
		print '</tr>';
	}

	// Warehouse
	if (isModEnabled('stock') && getDolGlobalString('SOCIETE_ASK_FOR_WAREHOUSE')) {
		$langs->load('stocks');
		require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
		$formproduct = new FormProduct($db);
		print '<tr class="nowrap">';
		print '<td>';
		print $form->editfieldkey("Warehouse", 'warehouse', '', $object, $user->hasRight('societe', 'creer'));
		print '</td><td>';
		if ($action == 'editwarehouse') {
			$formproduct->formSelectWarehouses($_SERVER['PHP_SELF'].'?id='.$object->id, $object->fk_warehouse, 'fk_warehouse', 1);
		} else {
			if ($object->fk_warehouse > 0) {
				print img_picto('', 'stock', 'class="paddingrightonly"');
			}
			$formproduct->formSelectWarehouses($_SERVER['PHP_SELF'].'?id='.$object->id, $object->fk_warehouse, 'none');
		}
		print '</td>';
		print '</tr>';
	}

	// Preferred shipping Method
	if (getDolGlobalString('SOCIETE_ASK_FOR_SHIPPING_METHOD')) {
		print '<tr><td class="nowrap">';
		print '<table width="100%" class="nobordernopadding"><tr><td class="nowrap">';
		print $langs->trans('SendingMethod');
		print '<td>';
		if (($action != 'editshipping') && $user->hasRight('societe', 'creer')) {
			print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editshipping&token='.newToken().'&socid='.$object->id.'">'.img_edit($langs->trans('SetMode'), 1).'</a></td>';
		}
		print '</tr></table>';
		print '</td><td>';
		if ($action == 'editshipping') {
			$form->formSelectShippingMethod($_SERVER['PHP_SELF'].'?socid='.$object->id, $object->shipping_method_id, 'shipping_method_id', 1);
		} else {
			$form->formSelectShippingMethod($_SERVER['PHP_SELF'].'?socid='.$object->id, $object->shipping_method_id, 'none');
		}
		print "</td>";
		print '</tr>';
	}

	if (isModEnabled('intracommreport')) {
		// Transport mode by default
		print '<tr><td class="nowrap">';
		print '<table class="centpercent nobordernopadding"><tr><td class="nowrap">';
		print $langs->trans('IntracommReportTransportMode');
		print '<td>';
		if (($action != 'edittransportmode') && $user->hasRight('societe', 'creer')) {
			print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=edittransportmode&token='.newToken().'&socid='.$object->id.'">'.img_edit($langs->trans('SetMode'), 1).'</a></td>';
		}
		print '</tr></table>';
		print '</td><td>';
		if ($action == 'edittransportmode') {
			$form->formSelectTransportMode($_SERVER['PHP_SELF'].'?socid='.$object->id, (!empty($object->transport_mode_id) ? $object->transport_mode_id : ''), 'transport_mode_id', 1);
		} else {
			$form->formSelectTransportMode($_SERVER['PHP_SELF'].'?socid='.$object->id, (!empty($object->transport_mode_id) ? $object->transport_mode_id : ''), 'none');
		}
		print "</td>";
		print '</tr>';
	}

	// Categories
	if (isModEnabled('category') && $user->hasRight('categorie', 'lire')) {
		$langs->load("categories");
		print '<tr><td>'.$langs->trans("CustomersCategoriesShort").'</td>';
		print '<td>';
		print $form->showCategories($object->id, Categorie::TYPE_CUSTOMER, 1);
		print "</td></tr>";
	}

	// Other attributes
	$parameters = array('socid' => $object->id);
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

	// Sales representative
	include DOL_DOCUMENT_ROOT.'/societe/tpl/linesalesrepresentative.tpl.php';

	// Module Adherent
	if (isModEnabled('member')) {
		$langs->load("members");
		$langs->load("users");

		print '<tr><td class="titlefield">'.$langs->trans("LinkedToDolibarrMember").'</td>';
		print '<td>';
		$adh = new Adherent($db);
		$result = $adh->fetch(0, '', $object->id);
		if ($result > 0) {
			$adh->ref = $adh->getFullName($langs);
			print $adh->getNomUrl(-1);
		} else {
			print '<span class="opacitymedium">'.$langs->trans("ThirdpartyNotLinkedToMember").'</span>';
		}
		print '</td>';
		print "</tr>\n";
	}

	print "</table>";

	print '</div><div class="fichehalfright">';

	// Prospection level and status
	if ($object->client == 2 || $object->client == 3) {
		print '<div class="underbanner clearboth"></div>';
		print '<table class="border centpercent tableforfield">';

		// Level of prospection
		print '<tr><td class="titlefield nowrap">';
		print '<table class="nobordernopadding centpercent"><tr><td class="nowrap">';
		print $langs->trans('ProspectLevel');
		print '<td>';
		if ($action != 'editlevel' && $user->hasRight('societe', 'creer')) {
			print '<td class="right"><a class="editfielda reposition" href="'.$_SERVER["PHP_SELF"].'?action=editlevel&token='.newToken().'&socid='.$object->id.'">'.img_edit($langs->trans('Modify'), 1).'</a></td>';
		}
		print '</tr></table>';
		print '</td><td>';
		if ($action == 'editlevel') {
			$formcompany->form_prospect_level($_SERVER['PHP_SELF'].'?socid='.$object->id, $object->fk_prospectlevel, 'prospect_level_id', 1);
		} else {
			print $object->getLibProspLevel();
		}
		print "</td>";
		print '</tr>';

		// Status of prospection
		$object->loadCacheOfProspStatus();
		print '<tr><td>'.$langs->trans("StatusProsp").'</td><td>'.$object->getLibProspCommStatut(4, $object->cacheprospectstatus[$object->stcomm_id]['label']);
		print ' &nbsp; &nbsp; ';
		print '<div class="floatright">';
		foreach ($object->cacheprospectstatus as $key => $val) {
			$titlealt = 'default';
			if (!empty($val['code']) && !in_array($val['code'], array('ST_NO', 'ST_NEVER', 'ST_TODO', 'ST_PEND', 'ST_DONE'))) {
				$titlealt = $val['label'];
			}
			if ($object->stcomm_id != $val['id']) {
				print '<a class="pictosubstatus reposition" href="'.$_SERVER["PHP_SELF"].'?socid='.$object->id.'&stcomm='.$val['code'].'&action=setstcomm&token='.newToken().'">'.img_action($titlealt, $val['code'], $val['picto']).'</a>';
			}
		}
		print '</div></td></tr>';
		print "</table>";

		print '<br>';
	} else {
		print '<div class="underbanner underbanner-before-box clearboth"></div><br>';
	}

	$boxstat = '';

	// Max nb of elements in lists
	$MAXLIST = getDolGlobalString('MAIN_SIZE_SHORTLIST_LIMIT');

	// Link summary/status board
	$boxstat .= '<div class="box divboxtable box-halfright">';
	$boxstat .= '<table summary="'.dol_escape_htmltag($langs->trans("DolibarrStateBoard")).'" class="border boxtable boxtablenobottom boxtablenotop boxtablenomarginbottom centpercent">';
	$boxstat .= '<tr class="impair nohover"><td colspan="2" class="tdboxstats nohover">';

	if (isModEnabled("propal") && $user->hasRight('propal', 'lire')) {
		// Box proposals
		$tmp = $object->getOutstandingProposals();
		$outstandingOpened = $tmp['opened'];
		$outstandingTotal = $tmp['total_ht'];
		$outstandingTotalIncTax = $tmp['total_ttc'];
		$text = $langs->trans("OverAllProposals");
		$link = DOL_URL_ROOT.'/comm/propal/list.php?socid='.$object->id;
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

	if (isModEnabled('order') && $user->hasRight('commande', 'lire')) {
		// Box orders
		$tmp = $object->getOutstandingOrders();
		$outstandingOpened = $tmp['opened'];
		$outstandingTotal = $tmp['total_ht'];
		$outstandingTotalIncTax = $tmp['total_ttc'];
		$text = $langs->trans("OverAllOrders");
		$link = DOL_URL_ROOT.'/commande/list.php?socid='.$object->id;
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

	if (isModEnabled('invoice') && $user->hasRight('facture', 'lire')) {
		// Box invoices
		$tmp = $object->getOutstandingBills('customer', 0);
		$outstandingOpened = $tmp['opened'];
		$outstandingTotal = $tmp['total_ht'];
		$outstandingTotalIncTax = $tmp['total_ttc'];

		$text = $langs->trans("OverAllInvoices");
		$link = DOL_URL_ROOT.'/compta/facture/list.php?socid='.$object->id;
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
		$warn = '';
		if ($object->outstanding_limit != '' && $object->outstanding_limit < $outstandingOpened) {
			$warn = ' '.img_warning($langs->trans("OutstandingBillReached"));
		}
		$text = $langs->trans("CurrentOutstandingBill");
		$link = DOL_URL_ROOT.'/compta/recap-compta.php?socid='.$object->id;
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

		$tmp = $object->getOutstandingBills('customer', 1);
		$outstandingOpenedLate = $tmp['opened'];
		if ($outstandingOpened != $outstandingOpenedLate && !empty($outstandingOpenedLate)) {
			$warn = '';
			if ($object->outstanding_limit != '' && $object->outstanding_limit < $outstandingOpenedLate) {
				$warn = ' '.img_warning($langs->trans("OutstandingBillReached"));
			}
			$text = $langs->trans("CurrentOutstandingBillLate");
			$link = DOL_URL_ROOT.'/compta/recap-compta.php?socid='.$object->id;
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
	$reshook = $hookmanager->executeHooks('addMoreBoxStatsCustomer', $parameters, $object, $action);
	if (empty($reshook)) {
		$boxstat .= $hookmanager->resPrint;
	}

	$boxstat .= '</td></tr>';
	$boxstat .= '</table>';
	$boxstat .= '</div>';

	print $boxstat;


	/*
	 * Latest proposals
	 */
	if (isModEnabled("propal") && $user->hasRight('propal', 'lire')) {
		$langs->load("propal");

		$sql = "SELECT s.nom, s.rowid, p.rowid as propalid, p.fk_projet, p.fk_statut, p.total_ht";
		$sql .= ", p.total_tva";
		$sql .= ", p.total_ttc";
		$sql .= ", p.ref, p.ref_client, p.remise";
		$sql .= ", p.datep as dp, p.fin_validite as date_limit, p.entity";
		$sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."propal as p, ".MAIN_DB_PREFIX."c_propalst as c";
		$sql .= " WHERE p.fk_soc = s.rowid AND p.fk_statut = c.id";
		$sql .= " AND s.rowid = ".((int) $object->id);
		$sql .= " AND p.entity IN (".getEntity('propal').")";
		$sql .= " ORDER BY p.datep DESC";

		$resql = $db->query($sql);
		if ($resql) {
			$propal_static = new Propal($db);

			$num = $db->num_rows($resql);
			if ($num > 0) {
				print '<div class="div-table-responsive-no-min">';
				print '<table class="noborder centpercent lastrecordtable">';

				print '<tr class="liste_titre">';
				print '<td colspan="5"><table width="100%" class="nobordernopadding"><tr><td>'.$langs->trans("LastPropals", ($num <= $MAXLIST ? "" : $MAXLIST)).'</td><td class="right"><a class="notasortlink" href="'.DOL_URL_ROOT.'/comm/propal/list.php?socid='.$object->id.'"><span class="hideonsmartphone">'.$langs->trans("AllPropals").'</span><span class="badge marginleftonlyshort">'.$num.'</span></a></td>';
				print '<td width="20px" class="right"><a href="'.DOL_URL_ROOT.'/comm/propal/stats/index.php?socid='.$object->id.'">'.img_picto($langs->trans("Statistics"), 'stats').'</a></td>';
				print '</tr></table></td>';
				print '</tr>';
			}

			$i = 0;
			while ($i < $num && $i < $MAXLIST) {
				$objp = $db->fetch_object($resql);

				print '<tr class="oddeven">';
				print '<td class="nowraponall">';
				$propal_static->id = $objp->propalid;
				$propal_static->ref = $objp->ref;
				$propal_static->ref_client = $objp->ref_client;	// deprecated
				$propal_static->ref_customer = $objp->ref_client;
				$propal_static->fk_project = $objp->fk_projet;
				$propal_static->total_ht = $objp->total_ht;
				$propal_static->total_tva = $objp->total_tva;
				$propal_static->total_ttc = $objp->total_ttc;
				print $propal_static->getNomUrl(1);

				// Preview
				$filedir = $conf->propal->multidir_output[$objp->entity].'/'.dol_sanitizeFileName($objp->ref);
				$file_list = null;
				if (!empty($filedir)) {
					$file_list = dol_dir_list($filedir, 'files', 0, '', '(\.meta|_preview.*.*\.png)$', 'date', SORT_DESC);
				}
				if (is_array($file_list)) {
					// Defined relative dir to DOL_DATA_ROOT
					$relativedir = '';
					if ($filedir) {
						$relativedir = preg_replace('/^'.preg_quote(DOL_DATA_ROOT, '/').'/', '', $filedir);
						$relativedir = preg_replace('/^[\\/]/', '', $relativedir);
					}
					// Get list of files stored into database for same relative directory
					if ($relativedir) {
						completeFileArrayWithDatabaseInfo($file_list, $relativedir);

						//var_dump($sortfield.' - '.$sortorder);
						if (!empty($sortfield) && !empty($sortorder)) {	// If $sortfield is for example 'position_name', we will sort on the property 'position_name' (that is concat of position+name)
							$file_list = dol_sort_array($file_list, $sortfield, $sortorder);
						}
					}
					$relativepath = dol_sanitizeFileName($objp->ref).'/'.dol_sanitizeFileName($objp->ref).'.pdf';
					print $formfile->showPreview($file_list, $propal_static->element, $relativepath, 0);
				}
				print '</td><td class="left">';
				if ($propal_static->fk_project > 0) {
					$project->fetch($propal_static->fk_project);
					print $project->getNomUrl(1);
				}
				// $filename = dol_sanitizeFileName($objp->ref);
				// $filedir = $conf->propal->multidir_output[$objp->entity].'/'.dol_sanitizeFileName($objp->ref);
				// $urlsource = '/comm/propal/card.php?id='.$objp->cid;
				// print $formfile->getDocumentsLink($propal_static->element, $filename, $filedir);
				if (($db->jdate($objp->date_limit) < ($now - $conf->propal->cloture->warning_delay)) && $objp->fk_statut == $propal_static::STATUS_VALIDATED) {
					print " ".img_warning();
				}
				print '</td><td class="right" width="80px">'.dol_print_date($db->jdate($objp->dp), 'day')."</td>\n";
				print '<td class="right nowraponall">'.price($objp->total_ht).'</td>';
				print '<td class="right" style="min-width: 60px" class="nowrap">'.$propal_static->LibStatut($objp->fk_statut, 5).'</td></tr>';
				$i++;
			}
			$db->free($resql);

			if ($num > 0) {
				print "</table>";
				print '</div>';
			}
		} else {
			dol_print_error($db);
		}
	}

	$orders2invoice = null;
	$param = "";
	/*
	 * Latest orders
	 */
	if (isModEnabled('order') && $user->hasRight('commande', 'lire')) {
		$sql = "SELECT s.nom, s.rowid";
		$sql .= ", c.rowid as cid, c.entity, c.fk_projet, c.total_ht";
		$sql .= ", c.total_tva";
		$sql .= ", c.total_ttc";
		$sql .= ", c.ref, c.ref_client, c.fk_statut, c.facture";
		$sql .= ", c.date_commande as dc";
		$sql .= ", c.facture as billed";
		$sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."commande as c";
		$sql .= " WHERE c.fk_soc = s.rowid ";
		$sql .= " AND s.rowid = ".((int) $object->id);
		$sql .= " AND c.entity IN (".getEntity('commande').')';
		$sql .= " ORDER BY c.date_commande DESC";

		$resql = $db->query($sql);
		if ($resql) {
			$commande_static = new Commande($db);

			$num = $db->num_rows($resql);
			if ($num > 0) {
				// Check if there are orders billable
				$sql2 = 'SELECT s.nom, s.rowid as socid, s.client, c.rowid, c.ref, c.total_ht, c.ref_client,';
				$sql2 .= ' c.date_valid, c.date_commande, c.date_livraison, c.fk_statut, c.facture as billed';
				$sql2 .= ' FROM '.MAIN_DB_PREFIX.'societe as s';
				$sql2 .= ', '.MAIN_DB_PREFIX.'commande as c';
				$sql2 .= ' WHERE c.fk_soc = s.rowid';
				$sql2 .= ' AND s.rowid = '.((int) $object->id);
				// Show orders with status validated, shipping started and delivered (well any order we can bill)
				$sql2 .= " AND ((c.fk_statut IN (1,2)) OR (c.fk_statut = 3 AND c.facture = 0))";

				$resql2 = $db->query($sql2);
				$orders2invoice = $db->num_rows($resql2);
				$db->free($resql2);

				print '<div class="div-table-responsive-no-min">';
				print '<table class="noborder centpercent lastrecordtable">';

				print '<tr class="liste_titre">';
				print '<td colspan="5"><table width="100%" class="nobordernopadding"><tr><td>'.$langs->trans("LastCustomerOrders", ($num <= $MAXLIST ? "" : $MAXLIST)).'</td><td class="right"><a class="notasortlink" href="'.DOL_URL_ROOT.'/commande/list.php?socid='.$object->id.'"><span class="hideonsmartphone">'.$langs->trans("AllOrders").'</span><span class="badge marginleftonlyshort">'.$num.'</span></a></td>';
				print '<td width="20px" class="right"><a href="'.DOL_URL_ROOT.'/commande/stats/index.php?socid='.$object->id.'">'.img_picto($langs->trans("Statistics"), 'stats').'</a></td>';
				print '</tr></table></td>';
				print '</tr>';
			}

			$i = 0;
			while ($i < $num && $i < $MAXLIST) {
				$objp = $db->fetch_object($resql);

				$commande_static->id = $objp->cid;
				$commande_static->ref = $objp->ref;
				$commande_static->ref_client = $objp->ref_client;
				$commande_static->fk_project = $objp->fk_projet;
				$commande_static->total_ht = $objp->total_ht;
				$commande_static->total_tva = $objp->total_tva;
				$commande_static->total_ttc = $objp->total_ttc;
				$commande_static->billed = $objp->billed;

				print '<tr class="oddeven">';
				print '<td class="nowraponall">';
				print $commande_static->getNomUrl(1);
				// Preview
				$filedir = $conf->commande->multidir_output[$objp->entity].'/'.dol_sanitizeFileName($objp->ref);
				$file_list = null;
				if (!empty($filedir)) {
					$file_list = dol_dir_list($filedir, 'files', 0, '', '(\.meta|_preview.*.*\.png)$', 'date', SORT_DESC);
				}
				if (is_array($file_list)) {
					// Defined relative dir to DOL_DATA_ROOT
					$relativedir = '';
					if ($filedir) {
						$relativedir = preg_replace('/^'.preg_quote(DOL_DATA_ROOT, '/').'/', '', $filedir);
						$relativedir = preg_replace('/^[\\/]/', '', $relativedir);
					}
					// Get list of files stored into database for same relative directory
					if ($relativedir) {
						completeFileArrayWithDatabaseInfo($file_list, $relativedir);

						//var_dump($sortfield.' - '.$sortorder);
						if (!empty($sortfield) && !empty($sortorder)) {	// If $sortfield is for example 'position_name', we will sort on the property 'position_name' (that is concat of position+name)
							$file_list = dol_sort_array($file_list, $sortfield, $sortorder);
						}
					}
					$relativepath = dol_sanitizeFileName($objp->ref).'/'.dol_sanitizeFileName($objp->ref).'.pdf';
					print $formfile->showPreview($file_list, $commande_static->element, $relativepath, 0, $param);
				}
				print '</td><td class="left">';
				if ($commande_static->fk_project > 0) {
					$project->fetch($commande_static->fk_project);
					print $project->getNomUrl(1);
				}
				// $filename = dol_sanitizeFileName($objp->ref);
				// $filedir = $conf->order->multidir_output[$objp->entity].'/'.dol_sanitizeFileName($objp->ref);
				// $urlsource = '/commande/card.php?id='.$objp->cid;
				// print $formfile->getDocumentsLink($commande_static->element, $filename, $filedir);
				print '</td>';

				print '<td class="right" width="80px">'.dol_print_date($db->jdate($objp->dc), 'day')."</td>\n";
				print '<td class="right nowraponall">'.price($objp->total_ht).'</td>';
				print '<td class="right" style="min-width: 60px" class="nowrap">'.$commande_static->LibStatut($objp->fk_statut, $objp->facture, 5).'</td></tr>';
				$i++;
			}
			$db->free($resql);

			if ($num > 0) {
				print "</table>";
				print '</div>';
			}
		} else {
			dol_print_error($db);
		}
	}

	/*
	 *   Latest shipments
	 */
	if (isModEnabled("shipping") && $user->hasRight('expedition', 'lire')) {
		$sql = 'SELECT e.rowid as id';
		$sql .= ', e.ref, e.entity, e.fk_projet';
		$sql .= ', e.date_creation';
		$sql .= ', e.fk_statut as statut';
		$sql .= ', s.nom';
		$sql .= ', s.rowid as socid';
		$sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."expedition as e";
		$sql .= " WHERE e.fk_soc = s.rowid AND s.rowid = ".((int) $object->id);
		$sql .= " AND e.entity IN (".getEntity('expedition').")";
		$sql .= ' GROUP BY e.rowid';
		$sql .= ', e.ref, e.entity, e.fk_projet';
		$sql .= ', e.date_creation';
		$sql .= ', e.fk_statut';
		$sql .= ', s.nom';
		$sql .= ', s.rowid';
		$sql .= " ORDER BY e.date_creation DESC";

		$resql = $db->query($sql);
		if ($resql) {
			$sendingstatic = new Expedition($db);

			$num = $db->num_rows($resql);
			if ($num > 0) {
				print '<div class="div-table-responsive-no-min">';
				print '<table class="noborder centpercent lastrecordtable">';

				print '<tr class="liste_titre">';
				print '<td colspan="5"><table class="centpercent nobordernopadding"><tr><td>'.$langs->trans("LastSendings", ($num <= $MAXLIST ? "" : $MAXLIST)).'</td><td class="right"><a class="notasortlink" href="'.DOL_URL_ROOT.'/expedition/list.php?socid='.$object->id.'"><span class="hideonsmartphone">'.$langs->trans("AllSendings").'</span><span class="badge marginleftonlyshort">'.$num.'</span></a></td>';
				print '<td width="20px" class="right"><a href="'.DOL_URL_ROOT.'/expedition/stats/index.php?socid='.$object->id.'">'.img_picto($langs->trans("Statistics"), 'stats').'</a></td>';
				print '</tr></table></td>';
				print '</tr>';
			}

			$i = 0;
			while ($i < $num && $i < $MAXLIST) {
				$objp = $db->fetch_object($resql);

				$sendingstatic->id = $objp->id;
				$sendingstatic->ref = $objp->ref;
				$sendingstatic->fk_project = $objp->fk_projet;

				print '<tr class="oddeven">';
				print '<td class="nowraponall">';
				print $sendingstatic->getNomUrl(1);
				// Preview
				$filedir = $conf->expedition->multidir_output[$objp->entity].'/'.dol_sanitizeFileName($objp->ref);
				$file_list = null;
				if (!empty($filedir)) {
					$file_list = dol_dir_list($filedir, 'files', 0, '', '(\.meta|_preview.*.*\.png)$', 'date', SORT_DESC);
				}
				if (is_array($file_list)) {
					// Defined relative dir to DOL_DATA_ROOT
					$relativedir = '';
					if ($filedir) {
						$relativedir = preg_replace('/^'.preg_quote(DOL_DATA_ROOT, '/').'/', '', $filedir);
						$relativedir = preg_replace('/^[\\/]/', '', $relativedir);
					}
					// Get list of files stored into database for same relative directory
					if ($relativedir) {
						completeFileArrayWithDatabaseInfo($file_list, $relativedir);

						//var_dump($sortfield.' - '.$sortorder);
						if (!empty($sortfield) && !empty($sortorder)) {	// If $sortfield is for example 'position_name', we will sort on the property 'position_name' (that is concat of position+name)
							$file_list = dol_sort_array($file_list, $sortfield, $sortorder);
						}
					}
					$relativepath = dol_sanitizeFileName($objp->ref).'/'.dol_sanitizeFileName($objp->ref).'.pdf';
					print $formfile->showPreview($file_list, $sendingstatic->table_element, $relativepath, 0, $param);
				}
				print '</td><td class="left">';
				if ($sendingstatic->fk_project > 0) {
					$project->fetch($sendingstatic->fk_project);
					print $project->getNomUrl(1);
				}
				// $filename = dol_sanitizeFileName($objp->ref);
				// $filedir = $conf->expedition->multidir_output[$objp->entity].'/'.dol_sanitizeFileName($objp->ref);
				// $urlsource = '/expedition/card.php?id='.$objp->cid;
				// print $formfile->getDocumentsLink($sendingstatic->element, $filename, $filedir);
				print '</td>';
				if ($objp->date_creation > 0) {
					print '<td class="right" width="80px">'.dol_print_date($db->jdate($objp->date_creation), 'day').'</td>';
				} else {
					print '<td class="right"><b>!!!</b></td>';
				}

				print '<td class="nowrap right">'.$sendingstatic->LibStatut($objp->statut, 5).'</td>';
				print "</tr>\n";
				$i++;
			}
			$db->free($resql);

			if ($num > 0) {
				print "</table>";
				print '</div>';
			}
		} else {
			dol_print_error($db);
		}
	}

	/*
	 * Latest contracts
	 */
	if (isModEnabled('contract') && $user->hasRight('contrat', 'lire')) {
		$sql = "SELECT s.nom, s.rowid, c.rowid as id, c.ref as ref, c.fk_projet, c.statut as contract_status, c.datec as dc, c.date_contrat as dcon, c.ref_customer as refcus, c.ref_supplier as refsup, c.entity,";
		$sql .= " c.last_main_doc, c.model_pdf";
		$sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."contrat as c";
		$sql .= " WHERE c.fk_soc = s.rowid ";
		$sql .= " AND s.rowid = ".((int) $object->id);
		$sql .= " AND c.entity IN (".getEntity('contract').")";
		$sql .= " ORDER BY c.datec DESC";

		$resql = $db->query($sql);
		if ($resql) {
			$contrat = new Contrat($db);

			$num = $db->num_rows($resql);
			if ($num > 0) {
				print '<div class="div-table-responsive-no-min">';
				print '<table class="noborder centpercent lastrecordtable">';

				print '<tr class="liste_titre">';
				print '<td colspan="6"><table width="100%" class="nobordernopadding"><tr><td>'.$langs->trans("LastContracts", ($num <= $MAXLIST ? "" : $MAXLIST)).'</td>';
				print '<td class="right"><a class="notasortlink" href="'.DOL_URL_ROOT.'/contrat/list.php?socid='.$object->id.'">'.$langs->trans("AllContracts").'<span class="badge marginleftonlyshort">'.$num.'</span></a></td>';
				//print '<td width="20px" class="right"><a href="'.DOL_URL_ROOT.'/contract/stats/index.php?socid='.$object->id.'">'.img_picto($langs->trans("Statistics"),'stats').'</a></td>';
				print '</tr></table></td>';
				print '</tr>';
			}

			$i = 0;
			while ($i < $num && $i < $MAXLIST) {
				$objp = $db->fetch_object($resql);

				$contrat->id = $objp->id;
				$contrat->ref = $objp->ref ? $objp->ref : $objp->id;
				$contrat->ref_customer = $objp->refcus;
				$contrat->ref_supplier = $objp->refsup;
				$contrat->fk_project = $objp->fk_projet;
				$contrat->statut = $objp->contract_status;
				$contrat->status = $objp->contract_status;
				$contrat->last_main_doc = $objp->last_main_doc;
				$contrat->model_pdf = $objp->model_pdf;
				$contrat->fetch_lines();

				$late = '';
				foreach ($contrat->lines as $line) {
					if ($contrat->status == Contrat::STATUS_VALIDATED && $line->statut == ContratLigne::STATUS_OPEN) {
						if (((!empty($line->date_end) ? $line->date_end : 0) + $conf->contrat->services->expires->warning_delay) < $now) {
							$late = img_warning($langs->trans("Late"));
						}
					}
				}

				print '<tr class="oddeven">';
				print '<td class="nowraponall">';
				print $contrat->getNomUrl(1, 12);
				if (!empty($contrat->model_pdf)) {
					// Preview
					$filedir = $conf->contrat->multidir_output[$objp->entity].'/'.dol_sanitizeFileName($objp->ref);
					$file_list = null;
					if (!empty($filedir)) {
						$file_list = dol_dir_list($filedir, 'files', 0, '', '(\.meta|_preview.*.*\.png)$', 'date', SORT_DESC);
					}
					if (is_array($file_list)) {
						// Defined relative dir to DOL_DATA_ROOT
						$relativedir = '';
						if ($filedir) {
							$relativedir = preg_replace('/^'.preg_quote(DOL_DATA_ROOT, '/').'/', '', $filedir);
							$relativedir = preg_replace('/^[\\/]/', '', $relativedir);
						}
						// Get list of files stored into database for same relative directory
						if ($relativedir) {
							completeFileArrayWithDatabaseInfo($file_list, $relativedir);

							//var_dump($sortfield.' - '.$sortorder);
							if (!empty($sortfield) && !empty($sortorder)) {	// If $sortfield is for example 'position_name', we will sort on the property 'position_name' (that is concat of position+name)
								$file_list = dol_sort_array($file_list, $sortfield, $sortorder);
							}
						}
						$relativepath = dol_sanitizeFileName($objp->ref).'/'.dol_sanitizeFileName($objp->ref).'.pdf';
						print $formfile->showPreview($file_list, $contrat->element, $relativepath, 0);
					}
				}
				// $filename = dol_sanitizeFileName($objp->ref);
				// $filedir = $conf->contrat->multidir_output[$objp->entity].'/'.dol_sanitizeFileName($objp->ref);
				// $urlsource = '/contrat/card.php?id='.$objp->cid;
				// print $formfile->getDocumentsLink($contrat->element, $filename, $filedir);
				print $late;
				print '</td><td class="tdoverflowmax100">';
				if ($contrat->fk_project > 0) {
					$project->fetch($contrat->fk_project);
					print $project->getNomUrl(1);
				}
				print "</td>\n";
				print '<td class="nowrap">';
				print dol_trunc(strtolower(get_class($object)) == strtolower(Client::class) ? $objp->refcus : $objp->refsup, 12);
				print "</td>\n";
				//print '<td class="right" width="80px"><span title="'.$langs->trans("DateCreation").'">'.dol_print_date($db->jdate($objp->dc), 'day')."</span></td>\n";
				print '<td class="right" width="80px"><span title="'.$langs->trans("DateContract").'">'.dol_print_date($db->jdate($objp->dcon), 'day')."</span></td>\n";
				print '<td width="20">&nbsp;</td>';
				print '<td class="nowraponall right">';
				print $contrat->getLibStatut(4);
				print "</td>\n";
				print '</tr>';
				$i++;
			}
			$db->free($resql);

			if ($num > 0) {
				print "</table>";
				print '</div>';
			}
		} else {
			dol_print_error($db);
		}
	}

	/*
	 * Latest interventions
	 */
	if (isModEnabled('intervention') && $user->hasRight('ficheinter', 'lire')) {
		$sql = "SELECT s.nom, s.rowid, f.rowid as id, f.ref, f.fk_projet, f.fk_statut, f.duree as duration, f.datei as startdate, f.entity";
		$sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."fichinter as f";
		$sql .= " WHERE f.fk_soc = s.rowid";
		$sql .= " AND s.rowid = ".((int) $object->id);
		$sql .= " AND f.entity IN (".getEntity('intervention').")";
		$sql .= " ORDER BY f.tms DESC";

		$resql = $db->query($sql);
		if ($resql) {
			$fichinter_static = new Fichinter($db);

			$num = $db->num_rows($resql);
			if ($num > 0) {
				print '<div class="div-table-responsive-no-min">';
				print '<table class="noborder centpercent lastrecordtable">';

				print '<tr class="liste_titre">';
				print '<td colspan="4"><table class="centpercent nobordernopadding"><tr><td>'.$langs->trans("LastInterventions", ($num <= $MAXLIST ? "" : $MAXLIST)).'</td><td class="right"><a class="notasortlink" href="'.DOL_URL_ROOT.'/fichinter/list.php?socid='.$object->id.'"><span class="hideonsmartphone">'.$langs->trans("AllInterventions").'</span><span class="badge marginleftonlyshort">'.$num.'</span></td>';
				print '<td width="20px" class="right"><a href="'.DOL_URL_ROOT.'/fichinter/stats/index.php?socid='.$object->id.'">'.img_picto($langs->trans("Statistics"), 'stats').'</a></td>';
				print '</tr></table></td>';
				print '</tr>';
			}

			$i = 0;
			while ($i < $num && $i < $MAXLIST) {
				$objp = $db->fetch_object($resql);

				$fichinter_static->id = $objp->id;
				$fichinter_static->ref = $objp->ref;
				$fichinter_static->statut = $objp->fk_statut;
				$fichinter_static->fk_project = $objp->fk_projet;

				print '<tr class="oddeven">';
				print '<td class="nowraponall">';
				print $fichinter_static->getNomUrl(1);
				// Preview
				$filedir = $conf->ficheinter->multidir_output[$objp->entity].'/'.dol_sanitizeFileName($objp->ref);
				$file_list = null;
				if (!empty($filedir)) {
					$file_list = dol_dir_list($filedir, 'files', 0, '', '(\.meta|_preview.*.*\.png)$', 'date', SORT_DESC);
				}
				if (is_array($file_list)) {
					// Defined relative dir to DOL_DATA_ROOT
					$relativedir = '';
					if ($filedir) {
						$relativedir = preg_replace('/^'.preg_quote(DOL_DATA_ROOT, '/').'/', '', $filedir);
						$relativedir = preg_replace('/^[\\/]/', '', $relativedir);
					}
					// Get list of files stored into database for same relative directory
					if ($relativedir) {
						completeFileArrayWithDatabaseInfo($file_list, $relativedir);

						//var_dump($sortfield.' - '.$sortorder);
						if (!empty($sortfield) && !empty($sortorder)) {	// If $sortfield is for example 'position_name', we will sort on the property 'position_name' (that is concat of position+name)
							$file_list = dol_sort_array($file_list, $sortfield, $sortorder);
						}
					}
					$relativepath = dol_sanitizeFileName($objp->ref).'/'.dol_sanitizeFileName($objp->ref).'.pdf';
					print $formfile->showPreview($file_list, $fichinter_static->element, $relativepath, 0);
				}
				print '</td><td class="left">';
				if ($fichinter_static->fk_project > 0) {
					$project->fetch($fichinter_static->fk_project);
					print $project->getNomUrl(1);
				}
				// $filename = dol_sanitizeFileName($objp->ref);
				// $filedir = getMultidirOutput($fichinter_static).'/'.dol_sanitizeFileName($objp->ref);
				// $urlsource = '/fichinter/card.php?id='.$objp->cid;
				// print $formfile->getDocumentsLink($fichinter_static->element, $filename, $filedir);
				print '</td>'."\n";
				//print '<td class="right" width="80px">'.dol_print_date($db->jdate($objp->startdate)).'</td>'."\n";
				print '<td class="right" style="min-width: 60px">'.convertSecondToTime($objp->duration).'</td>'."\n";
				print '<td class="nowrap right" style="min-width: 60px">'.$fichinter_static->getLibStatut(5).'</td>'."\n";
				print '</tr>';

				$i++;
			}
			$db->free($resql);

			if ($num > 0) {
				print "</table>";
				print '</div>';
			}
		} else {
			dol_print_error($db);
		}
	}

	/*
	 *   Latest invoices templates
	 */
	if (isModEnabled('invoice') && $user->hasRight('facture', 'lire')) {
		$sql = 'SELECT f.rowid as id, f.titre as ref, f.fk_projet';
		$sql .= ', f.total_ht';
		$sql .= ', f.total_tva';
		$sql .= ', f.total_ttc';
		$sql .= ', f.datec as dc';
		$sql .= ', f.date_last_gen, f.date_when';
		$sql .= ', f.frequency';
		$sql .= ', f.unit_frequency';
		$sql .= ', f.suspended as suspended';
		$sql .= ', s.nom, s.rowid as socid';
		$sql .= " FROM ".MAIN_DB_PREFIX."societe as s,".MAIN_DB_PREFIX."facture_rec as f";
		$sql .= " WHERE f.fk_soc = s.rowid AND s.rowid = ".((int) $object->id);
		$sql .= " AND f.entity IN (".getEntity('invoice').")";
		$sql .= ' GROUP BY f.rowid, f.titre, f.fk_projet, f.total_ht, f.total_tva, f.total_ttc,';
		$sql .= ' f.date_last_gen, f.datec, f.frequency, f.unit_frequency,';
		$sql .= ' f.suspended, f.date_when,';
		$sql .= ' s.nom, s.rowid';
		$sql .= " ORDER BY f.date_last_gen, f.datec DESC";

		$resql = $db->query($sql);
		if ($resql) {
			$invoicetemplate = new FactureRec($db);

			$num = $db->num_rows($resql);
			if ($num > 0) {
				print '<div class="div-table-responsive-no-min">';
				print '<table class="noborder centpercent lastrecordtable">';
				print '<tr class="liste_titre">';
				$colspan = 5;
				if (getDolGlobalString('MAIN_SHOW_PRICE_WITH_TAX_IN_SUMMARIES')) {
					$colspan++;
				}
				print '<td colspan="'.$colspan.'">';
				print '<table class="centpercent nobordernopadding"><tr>';
				print '<td>'.$langs->trans("LatestCustomerTemplateInvoices", ($num <= $MAXLIST ? "" : $MAXLIST)).'</td><td class="right"><a class="notasortlink" href="'.DOL_URL_ROOT.'/compta/facture/invoicetemplate_list.php?socid='.$object->id.'"><span class="hideonsmartphone">'.$langs->trans("AllCustomerTemplateInvoices").'</span><span class="badge marginleftonlyshort">'.$num.'</span></a></td>';
				print '</tr></table>';
				print '</td>';
				print '</tr>';
			}

			$i = 0;
			while ($i < $num && $i < $MAXLIST) {
				$objp = $db->fetch_object($resql);

				$invoicetemplate->id = $objp->id;
				$invoicetemplate->ref = $objp->ref;
				$invoicetemplate->fk_project = $objp->fk_projet;
				$invoicetemplate->suspended = $objp->suspended;
				$invoicetemplate->frequency = $objp->frequency;
				$invoicetemplate->unit_frequency = $objp->unit_frequency;
				$invoicetemplate->total_ht = $objp->total_ht;
				$invoicetemplate->total_tva = $objp->total_tva;
				$invoicetemplate->total_ttc = $objp->total_ttc;
				$invoicetemplate->date_last_gen = $objp->date_last_gen;
				$invoicetemplate->date_when = $objp->date_when;

				print '<tr class="oddeven">';
				print '<td class="tdoverflowmax250">';
				print $invoicetemplate->getNomUrl(1);
				print '</td><td class="left">';
				if ($invoicetemplate->fk_project > 0) {
					$project->fetch($invoicetemplate->fk_project);
					print $project->getNomUrl(1);
				}
				print '</td>';

				if ($objp->frequency && $objp->date_last_gen > 0) {
					print '<td class="right" width="80px">'.dol_print_date($db->jdate($objp->date_last_gen), 'day').'</td>';
				} else {
					if ($objp->dc > 0) {
						print '<td class="right" width="80px">'.dol_print_date($db->jdate($objp->dc), 'day').'</td>';
					} else {
						print '<td class="right"><b>!!!</b></td>';
					}
				}
				print '<td class="right nowraponall">';
				print price($objp->total_ht);
				print '</td>';

				if (getDolGlobalString('MAIN_SHOW_PRICE_WITH_TAX_IN_SUMMARIES')) {
					print '<td class="right nowraponall">';
					print price($objp->total_ttc);
					print '</td>';
				}

				print '<td class="nowrap right" style="min-width: 60px">';
				print $langs->trans('FrequencyPer_'.$invoicetemplate->unit_frequency, $invoicetemplate->frequency).' - ';
				print($invoicetemplate->LibStatut($invoicetemplate->frequency, $invoicetemplate->suspended, 5, 0));
				print '</td>';
				print "</tr>\n";
				$i++;
			}
			$db->free($resql);

			if ($num > 0) {
				print "</table>";
				print '</div>';
			}
		} else {
			dol_print_error($db);
		}
	}

	/*
	 *   Latest invoices
	 */
	if (isModEnabled('invoice') && $user->hasRight('facture', 'lire')) {
		$sql = 'SELECT f.rowid as facid, f.ref, f.type, f.ref_client, f.fk_projet';
		$sql .= ', f.total_ht';
		$sql .= ', f.total_tva';
		$sql .= ', f.total_ttc';
		$sql .= ', f.entity';
		$sql .= ', f.datef as df, f.date_lim_reglement as dl, f.datec as dc, f.paye as paye, f.fk_statut as status';
		$sql .= ', s.nom, s.rowid as socid';
		$sql .= ', SUM(pf.amount) as am';
		$sql .= " FROM ".MAIN_DB_PREFIX."societe as s,".MAIN_DB_PREFIX."facture as f";
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'paiement_facture as pf ON f.rowid=pf.fk_facture';
		$sql .= " WHERE f.fk_soc = s.rowid AND s.rowid = ".((int) $object->id);
		$sql .= " AND f.entity IN (".getEntity('invoice').")";
		$sql .= ' GROUP BY f.rowid, f.ref, f.type, f.ref_client, f.fk_projet, f.total_ht, f.total_tva, f.total_ttc,';
		$sql .= ' f.entity, f.datef, f.date_lim_reglement, f.datec, f.paye, f.fk_statut,';
		$sql .= ' s.nom, s.rowid';
		$sql .= " ORDER BY f.datef DESC, f.datec DESC";

		$resql = $db->query($sql);
		if ($resql) {
			$facturestatic = new Facture($db);

			$num = $db->num_rows($resql);
			if ($num > 0) {
				print '<div class="div-table-responsive-no-min">';
				print '<table class="noborder centpercent lastrecordtable">';
				print '<tr class="liste_titre">';
				$colspan = 6;
				if (getDolGlobalString('MAIN_SHOW_PRICE_WITH_TAX_IN_SUMMARIES')) {
					$colspan++;
				}
				if (getDolGlobalString('MAIN_SHOW_REF_CUSTOMER_INVOICES')) {
					$colspan++;
				}
				print '<td colspan="'.$colspan.'">';
				print '<table class="centpercent nobordernopadding"><tr><td>'.$langs->trans("LastCustomersBills", ($num <= $MAXLIST ? "" : $MAXLIST)).'</td><td class="right"><a class="notasortlink" href="'.DOL_URL_ROOT.'/compta/facture/list.php?socid='.$object->id.'"><span class="hideonsmartphone">'.$langs->trans("AllBills").'</span><span class="badge marginleftonlyshort">'.$num.'</span></a></td>';
				print '<td width="20px" class="right"><a href="'.DOL_URL_ROOT.'/compta/facture/stats/index.php?socid='.$object->id.'">'.img_picto($langs->trans("Statistics"), 'stats').'</a></td>';
				print '</tr></table>';
				print '</td>';
				print '</tr>';
			}

			$i = 0;
			while ($i < $num && $i < $MAXLIST) {
				$objp = $db->fetch_object($resql);

				$facturestatic->id = $objp->facid;
				$facturestatic->ref = $objp->ref;
				$facturestatic->ref_client = $objp->ref_client;
				$facturestatic->fk_project = $objp->fk_projet;
				$facturestatic->type = $objp->type;
				$facturestatic->total_ht = $objp->total_ht;
				$facturestatic->total_tva = $objp->total_tva;
				$facturestatic->total_ttc = $objp->total_ttc;
				$facturestatic->statut = $objp->status;
				$facturestatic->status = $objp->status;
				$facturestatic->paye = $objp->paye;
				$facturestatic->alreadypaid = $objp->am;
				$facturestatic->totalpaid = $objp->am;
				$facturestatic->date = $db->jdate($objp->df);
				$facturestatic->date_lim_reglement = $db->jdate($objp->dl);

				print '<tr class="oddeven">';
				print '<td class="nowraponall">';
				print $facturestatic->getNomUrl(1);
				// Preview
				$filedir = $conf->facture->multidir_output[$objp->entity].'/'.dol_sanitizeFileName($objp->ref);
				$file_list = null;
				if (!empty($filedir)) {
					$file_list = dol_dir_list($filedir, 'files', 0, '', '(\.meta|_preview.*.*\.png)$', 'date', SORT_DESC);
				}
				if (is_array($file_list)) {
					// Defined relative dir to DOL_DATA_ROOT
					$relativedir = '';
					if ($filedir) {
						$relativedir = preg_replace('/^'.preg_quote(DOL_DATA_ROOT, '/').'/', '', $filedir);
						$relativedir = preg_replace('/^[\\/]/', '', $relativedir);
					}
					// Get list of files stored into database for same relative directory
					if ($relativedir) {
						completeFileArrayWithDatabaseInfo($file_list, $relativedir);

						//var_dump($sortfield.' - '.$sortorder);
						if (!empty($sortfield) && !empty($sortorder)) {	// If $sortfield is for example 'position_name', we will sort on the property 'position_name' (that is concat of position+name)
							$file_list = dol_sort_array($file_list, $sortfield, $sortorder);
						}
					}
					$relativepath = dol_sanitizeFileName($objp->ref).'/'.dol_sanitizeFileName($objp->ref).'.pdf';
					print $formfile->showPreview($file_list, $facturestatic->element, $relativepath, 0);
				}
				print '</td><td class="left">';
				if ($facturestatic->fk_project > 0) {
					$project->fetch($facturestatic->fk_project);
					print $project->getNomUrl(1);
				}
				// $filename = dol_sanitizeFileName($objp->ref);
				// $filedir = $conf->facture->multidir_output[$objp->entity].'/'.dol_sanitizeFileName($objp->ref);
				// $urlsource = '/compta/facture/card.php?id='.$objp->cid;
				//print $formfile->getDocumentsLink($facturestatic->element, $filename, $filedir);
				print '</td>';
				if (getDolGlobalString('MAIN_SHOW_REF_CUSTOMER_INVOICES')) {
					print '<td class="left nowraponall">';
					print $objp->ref_client;
					print '</td>';
				}
				if ($objp->df > 0) {
					print '<td width="80px" title="'.dol_escape_htmltag($langs->trans('DateInvoice')).'">'.dol_print_date($db->jdate($objp->df), 'day').'</td>';
				} else {
					print '<td><b>!!!</b></td>';
				}
				if ($objp->dl > 0) {
					print '<td width="80px" title="'.dol_escape_htmltag($langs->trans('DateMaxPayment')).'">'.dol_print_date($db->jdate($objp->dl), 'day').'</td>';
				} else {
					print '<td><b>!!!</b></td>';
				}

				print '<td class="right nowraponall">';
				print price($objp->total_ht);
				print '</td>';

				if (getDolGlobalString('MAIN_SHOW_PRICE_WITH_TAX_IN_SUMMARIES')) {
					print '<td class="right nowraponall">';
					print price($objp->total_ttc);
					print '</td>';
				}

				print '<td class="nowrap right" style="min-width: 60px">'.($facturestatic->LibStatut($objp->paye, $objp->status, 5, $objp->am)).'</td>';
				print "</tr>\n";
				$i++;
			}
			$db->free($resql);

			if ($num > 0) {
				print "</table>";
				print '</div>';
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

	if (empty($reshook)) {
		if ($object->status != 1) {
			print '<div class="inline-block divButAction"><a class="butActionRefused classfortooltip" title="'.dol_escape_js($langs->trans("ThirdPartyIsClosed")).'" href="#">'.$langs->trans("ThirdPartyIsClosed").'</a></div>';
		}

		if (isModEnabled("propal") && $user->hasRight('propal', 'creer') && $object->status == 1) {
			$langs->load("propal");
			print '<div class="inline-block divButAction"><a class="butAction" href="'.DOL_URL_ROOT.'/comm/propal/card.php?socid='.$object->id.'&amp;action=create">'.$langs->trans("AddProp").'</a></div>';
		}

		if (isModEnabled('order') && $user->hasRight('commande', 'creer') && $object->status == 1) {
			$langs->load("orders");
			print '<div class="inline-block divButAction"><a class="butAction" href="'.DOL_URL_ROOT.'/commande/card.php?socid='.$object->id.'&amp;action=create">'.$langs->trans("AddOrder").'</a></div>';
		}

		if ($user->hasRight('contrat', 'creer') && $object->status == 1) {
			$langs->load("contracts");
			print '<div class="inline-block divButAction"><a class="butAction" href="'.DOL_URL_ROOT.'/contrat/card.php?socid='.$object->id.'&amp;action=create">'.$langs->trans("AddContract").'</a></div>';
		}

		if (isModEnabled('intervention') && $user->hasRight('ficheinter', 'creer') && $object->status == 1) {
			$langs->load("interventions");
			print '<div class="inline-block divButAction"><a class="butAction" href="'.DOL_URL_ROOT.'/fichinter/card.php?socid='.$object->id.'&amp;action=create">'.$langs->trans("AddIntervention").'</a></div>';
		}

		// Add invoice
		if ($user->socid == 0) {
			if (isModEnabled('deplacement') && $object->status == 1) {
				$langs->load("trips");
				print '<div class="inline-block divButAction"><a class="butAction" href="'.DOL_URL_ROOT.'/compta/deplacement/card.php?socid='.$object->id.'&amp;action=create">'.$langs->trans("AddTrip").'</a></div>';
			}

			if (isModEnabled('invoice') && $object->status == 1) {
				if (!$user->hasRight('facture', 'creer')) {
					$langs->load("bills");
					print '<div class="inline-block divButAction"><a class="butActionRefused classfortooltip" title="'.dol_escape_js($langs->trans("NotAllowed")).'" href="#">'.$langs->trans("AddBill").'</a></div>';
				} else {
					$langs->loadLangs(array("orders", "bills"));

					if (isModEnabled('order')) {
						if ($object->client != 0 && $object->client != 2) {
							if (!empty($orders2invoice) && $orders2invoice > 0) {
								print '<div class="inline-block divButAction"><a class="butAction" href="'.DOL_URL_ROOT.'/commande/list.php?socid='.$object->id.'&search_billed=0&autoselectall=1">'.$langs->trans("CreateInvoiceForThisCustomer").'</a></div>';
							} else {
								print '<div class="inline-block divButAction"><a class="butActionRefused classfortooltip" title="'.dol_escape_js($langs->trans("NoOrdersToInvoice")).'" href="#">'.$langs->trans("CreateInvoiceForThisCustomer").'</a></div>';
							}
						} else {
							print '<div class="inline-block divButAction"><a class="butActionRefused classfortooltip" title="'.dol_escape_js($langs->trans("ThirdPartyMustBeEditAsCustomer")).'" href="#">'.$langs->trans("CreateInvoiceForThisCustomer").'</a></div>';
						}
					}

					if ($object->client != 0 && $object->client != 2) {
						print '<div class="inline-block divButAction"><a class="butAction" href="'.DOL_URL_ROOT.'/compta/facture/card.php?action=create&socid='.$object->id.'">'.$langs->trans("AddBill").'</a></div>';
					} else {
						print '<div class="inline-block divButAction"><a class="butActionRefused classfortooltip" title="'.dol_escape_js($langs->trans("ThirdPartyMustBeEditAsCustomer")).'" href="#">'.$langs->trans("AddBill").'</a></div>';
					}
				}
			}
		}

		// Add action
		if (isModEnabled('agenda') && getDolGlobalString('MAIN_REPEATTASKONEACHTAB') && $object->status == 1) {
			if ($user->hasRight('agenda', 'myactions', 'create')) {
				print '<div class="inline-block divButAction"><a class="butAction" href="'.DOL_URL_ROOT.'/comm/action/card.php?action=create&socid='.$object->id.'">'.$langs->trans("AddAction").'</a></div>';
			} else {
				print '<div class="inline-block divButAction"><a class="butAction" title="'.dol_escape_js($langs->trans("NotAllowed")).'" href="#">'.$langs->trans("AddAction").'</a></div>';
			}
		}
	}

	print '</div>';

	if (getDolGlobalString('MAIN_DUPLICATE_CONTACTS_TAB_ON_CUSTOMER_CARD')) {
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
	$langs->load("errors");
	print $langs->trans('ErrorRecordNotFound');
}

// End of page
llxFooter();
$db->close();
