<?php
/* Copyright (C) 2005       Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2010-2020  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2010-2012  Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2018       Nicolas ZABOURI         <info@inovea-conseil.com>
 * Copyright (C) 2018-2023  Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2019       Markus Welters          <markus@welters.de>
 * Copyright (C) 2024       Alexandre Spangaro      <aspangaro@open-dsi.fr>
 * Copyright (C) 2024       Thomas Negre     		<tnegre@open-dsi.fr>
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
 *	\file       htdocs/compta/prelevement/create.php
 *  \ingroup    prelevement
 *	\brief      Page to create a direct debit order or a credit transfer order
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/prelevement/class/bonprelevement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/bank.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/prelevement.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

// Load translation files required by the page
$langs->loadLangs(array('banks', 'categories', 'withdrawals', 'companies', 'bills'));

$type = GETPOST('type', 'aZ09');

// Get supervariables
$action = GETPOST('action', 'aZ09');
$massaction = GETPOST('massaction', 'alpha'); // The bulk action (combo box choice into lists)
$toselect   = GETPOST('toselect', 'array'); // Array of ids of elements selected into a list
$contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'directdebitcreatecard';

// Security check
if ($user->socid > 0) {
	$action = '';
	$_GET["action"] = '';
	$socid = $user->socid;
}
$mode = GETPOST('mode', 'alpha') ?GETPOST('mode', 'alpha') : 'real';
$format = GETPOST('format', 'aZ09');
$id_bankaccount = GETPOST('id_bankaccount', 'int');
$searchsql = GETPOST('searchsql', 'alpha');
$executiondate = dol_mktime(0, 0, 0, GETPOST('remonth', 'int'), GETPOST('reday', 'int'), GETPOST('reyear', 'int'));

$search_all 					= trim((GETPOST('search_all', 'alphanohtml') != '') ?GETPOST('search_all', 'alphanohtml') : GETPOST('sall', 'alphanohtml'));
$search_ref 					= GETPOST('search_ref', 'alpha');
$search_ref_supplier 			= GETPOST('search_ref_supplier', 'alpha');
$search_datelimit_startday 		= GETPOST('search_datelimit_startday', 'int');
$search_datelimit_startmonth 	= GETPOST('search_datelimit_startmonth', 'int');
$search_datelimit_startyear 	= GETPOST('search_datelimit_startyear', 'int');
$search_datelimit_endday 		= GETPOST('search_datelimit_endday', 'int');
$search_datelimit_endmonth 		= GETPOST('search_datelimit_endmonth', 'int');
$search_datelimit_endyear 		= GETPOST('search_datelimit_endyear', 'int');
$search_datelimit_start 		= dol_mktime(0, 0, 0, $search_datelimit_startmonth, $search_datelimit_startday, $search_datelimit_startyear);
$search_datelimit_end 			= dol_mktime(23, 59, 59, $search_datelimit_endmonth, $search_datelimit_endday, $search_datelimit_endyear);
$search_company 				= GETPOST('search_company', 'alpha');
$search_account 				= GETPOST('search_account', 'alpha');
$toselect 						= GETPOST('toselect', 'array');
$search_btn 					= GETPOST('button_search', 'alpha');
$search_remove_btn 				= GETPOST('button_removefilter', 'alpha');

$option = GETPOST('search_option');

$filter = GETPOST('filtre', 'alpha');

$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if ($page == -1 || $page == null || !empty($search_btn) || !empty($search_remove_btn) || (empty($toselect) && $massaction === '0')) {
	$page = 0;
}     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortorder) {
	$sortorder = "DESC";
}
if (!$sortfield) {
	$sortfield = "f.date_lim_reglement,f.rowid";
}

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
	'f.ref'=>'Ref',
	's.nom'=>"ThirdParty",
);

$arrayfields = array(
	'f.ref'=>array('label'=>($type == 'bank-transfer' ? 'SupplierInvoice' : 'Invoice'), 'checked'=>1),
	'f.date_lim_reglement'=>array('label'=>"DateDue", 'checked'=>1),
	's.nom'=>array('label'=>"ThirdParty", 'checked'=>1),
	'f.fk_account'=>array('label'=>"BankAccount", 'checked'=>1),
	'pfd.fk_soc_rib'=>array('label'=>"SupplierIBAN", 'checked'=>1),
	'rum'=>array('label'=>"RUM", 'checked'=>1),
	'pfd.amount'=>array('label'=>"AmountTTC", 'checked'=>1),
	'pfd.date_demande'=>array('label'=>"DateRequest", 'checked'=>1)
);
if ($type == 'bank-transfer') {
	$arrayfields['f.ref_supplier'] = array('label'=>'RefSupplier', 'checked'=>1);
}

$hookmanager->initHooks(array('directdebitcreatecard', 'globalcard'));

if ($type == 'bank-transfer') {
	$result = restrictedArea($user, 'paymentbybanktransfer', '', '', '');
} else {
	$result = restrictedArea($user, 'prelevement', '', '', 'bons');
}

$error = 0;
$option = "";
$mesg = '';


/*
 * Actions
 */

if (GETPOST('cancel', 'alpha')) {
	$massaction = '';
}

$parameters = array('mode' => $mode, 'format' => $format, 'limit' => $limit, 'page' => $page, 'offset' => $offset);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter', 'alpha') || GETPOST('button_removefilter.x', 'alpha')) {		// All tests must be present to be compatible with all browsers
		$search_all = "";
		$search_ref = "";
		$search_company = "";
		$search_account = "";
		$search_datelimit_startday = '';
		$search_datelimit_startmonth = '';
		$search_datelimit_startyear = '';
		$search_datelimit_endday = '';
		$search_datelimit_endmonth = '';
		$search_datelimit_endyear = '';
		$search_datelimit_start = '';
		$search_datelimit_end = '';
		$toselect = '';
		$filter = '';
		$option = '';
		$socid = "";
	}

	// Change customer bank information to withdraw
	if ($action == 'modify') {
		for ($i = 1; $i < 9; $i++) {
			dolibarr_set_const($db, GETPOST("nom".$i), GETPOST("value".$i), 'chaine', 0, '', $conf->entity);
		}
	}
	if ($action == 'create') {
		$default_account = ($type == 'bank-transfer' ? 'PAYMENTBYBANKTRANSFER_ID_BANKACCOUNT' : 'PRELEVEMENT_ID_BANKACCOUNT');

		//var_dump($default_account);var_dump($conf->global->$default_account);var_dump($id_bankaccount);exit;

		if ($id_bankaccount != getDolGlobalInt($default_account)) {
			$res = dolibarr_set_const($db, $default_account, $id_bankaccount, 'chaine', 0, '', $conf->entity); // Set as default
		}

		require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
		$bank = new Account($db);
		$bank->fetch($conf->global->{$default_account});
		// ICS is not mandatory with payment by bank transfer
		/*if ((empty($bank->ics) && $type !== 'bank-transfer')
			|| (empty($bank->ics_transfer) && $type === 'bank-transfer')
		) {*/
		if (empty($bank->ics) && $type !== 'bank-transfer') {
			$errormessage = str_replace('{url}', $bank->getNomUrl(1, '', '', -1, 1), $langs->trans("ErrorICSmissing", '{url}'));
			setEventMessages($errormessage, null, 'errors');
			$action = '';
			$error++;
		}
		if (empty($toselect)) {
			$mesg = $langs->trans("NoInvoiceSelected");
			setEventMessages($mesg, null, 'errors');
			$action = '';
			$error++;
		}


		$bprev = new BonPrelevement($db);

		if (!$error) {
			// getDolGlobalString('PRELEVEMENT_CODE_BANQUE') and getDolGlobalString('PRELEVEMENT_CODE_GUICHET') should be empty (we don't use them anymore)
			$selected_invoices = array();
			foreach($toselect as $select) {
				$selected_invoices[] = (int) $select;
			}
			$result = $bprev->create(getDolGlobalString('PRELEVEMENT_CODE_BANQUE'), getDolGlobalString('PRELEVEMENT_CODE_GUICHET'), $mode, $format, $executiondate, 0, $type, $selected_invoices, $id_bankaccount);

			if ($result < 0) {
				setEventMessages($bprev->error, $bprev->errors, 'errors');
			} elseif ($result == 0) {
				$mesg = $langs->trans("NoInvoiceCouldBeWithdrawed", $format);
				setEventMessages($mesg, null, 'errors');
				$mesg .= '<br>'."\n";
				foreach ($bprev->invoice_in_error as $key => $val) {
					$mesg .= '<span class="warning">'.$val."</span><br>\n";
				}
			} else {
				if ($type != 'bank-transfer') {
					$texttoshow = $langs->trans("DirectDebitOrderCreated", '{s}');
					$texttoshow = str_replace('{s}', $bprev->getNomUrl(1), $texttoshow);
					setEventMessages($texttoshow, null);
				} else {
					$texttoshow = $langs->trans("CreditTransferOrderCreated", '{s}');
					$texttoshow = str_replace('{s}', $bprev->getNomUrl(1), $texttoshow);
					setEventMessages($texttoshow, null);
				}

				header("Location: ".DOL_URL_ROOT.'/compta/prelevement/card.php?id='.urlencode($bprev->id).'&type='.urlencode($type));
				exit;
			}
		}
	}
	$objectclass = "BonPrelevement";
	if ($type == 'bank-transfer') {
		$uploaddir = $conf->paymentbybanktransfer->dir_output;
	} else {
		$uploaddir = $conf->prelevement->dir_output;
	}
	include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';
	$arrayofselected = is_array($toselect) ? $toselect : array();
}

$sql = "SELECT f.ref, f.rowid, f.date_lim_reglement as datelimite, f.total_ttc, f.fk_account,";
$sql .= " s.nom as name, s.rowid as socid,";
$sql .= " pfd.rowid as request_row_id, pfd.date_demande, pfd.amount, pfd.fk_soc_rib";
if ($type == 'bank-transfer') {
	$sql .= " , ref_supplier";
	$sql .= " FROM ".MAIN_DB_PREFIX."facture_fourn as f";
} else {
	$sql .= " FROM ".MAIN_DB_PREFIX."facture as f";
}
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."bank_account AS ba ON f.fk_account = ba.rowid,";
$sql .= " ".MAIN_DB_PREFIX."societe as s,";
$sql .= " ".MAIN_DB_PREFIX."prelevement_demande as pfd";
$sql .= " WHERE s.rowid = f.fk_soc";
$sql .= " AND f.entity IN (".getEntity('invoice').")";
if (empty($conf->global->WITHDRAWAL_ALLOW_ANY_INVOICE_STATUS)) {
	$sql .= " AND f.fk_statut = ".Facture::STATUS_VALIDATED;
}
//$sql .= " AND pfd.amount > 0";
$sql .= " AND f.total_ttc > 0"; // Avoid credit notes
$sql .= " AND pfd.traite = 0";
$sql .= " AND pfd.ext_payment_id IS NULL";
if ($type == 'bank-transfer') {
	$sql .= " AND pfd.fk_facture_fourn = f.rowid";
} else {
	$sql .= " AND pfd.fk_facture = f.rowid";
}
if ($socid > 0) {
	$sql .= " AND f.fk_soc = ".((int) $socid);
}
$searchsql = '';
if ($search_ref) {
	if (is_numeric($search_ref)) {
		$searchsql .= natural_search(array('f.ref'), $search_ref);
	} else {
		$searchsql .= natural_search('f.ref', $search_ref);
	}
}
if ($search_ref) {
	$searchsql .= natural_search('f.ref', $search_ref);
}
if ($search_ref_supplier) {
	$searchsql .= natural_search('f.ref_supplier', $search_ref_supplier);
}
if ($search_company) {
	$searchsql .= natural_search('s.nom', $search_company);
}
if ($search_account) {
	$searchsql .= natural_search(array('ba.ref', 'ba.label', 'ba.bank'), $search_account);
}
if ($search_datelimit_start) {
	$searchsql .= " AND f.date_lim_reglement >= '" . $db->idate($search_datelimit_start) . "'";
}
if ($search_datelimit_end) {
	$searchsql .= " AND f.date_lim_reglement <= '" . $db->idate($search_datelimit_end) . "'";
}
if ($option == 'late') {
	$searchsql .= " AND f.date_lim_reglement < '".$db->idate(dol_now() - $conf->facture->fournisseur->warning_delay)."'";
}
if ($filter && $filter != -1) {
	$aFilter = explode(',', $filter);
	foreach ($aFilter as $fil) {
		$filt = explode(':', $fil);
		$searchsql .= ' AND '.$db->escape(trim($filt[0]))." = '".$db->escape(trim($filt[1]))."'";
	}
}
$sql .= !empty($searchsql) ? $searchsql : '';
if (!$search_all) {
	$sql .= " GROUP BY f.rowid, f.ref, f.date_lim_reglement,";
	$sql .= ' s.rowid, s.nom';
} else {
	$sql .= natural_search(array_keys($fieldstosearchall), $search_all);
	$searchsql .= natural_search(array_keys($fieldstosearchall), $search_all);
}

$sql .= $db->order($sortfield, $sortorder);


/*
 * View
 */

$form = new Form($db);

$thirdpartystatic = new Societe($db);
if ($type != 'bank-transfer') {
	$invoicestatic = new Facture($db);
} else {
	$invoicestatic = new FactureFournisseur($db);
}
$bprev = new BonPrelevement($db);

if (prelevement_check_config($type) < 0) {
	$langs->load("errors");
	$modulenametoshow = "Withdraw";
	if ($type == 'bank-transfer') {
		$modulenametoshow = "PaymentByBankTransfer";
	}
	setEventMessages($langs->trans("ErrorModuleSetupNotComplete", $langs->transnoentitiesnoconv($modulenametoshow)), null, 'errors');
}


$title = $langs->trans("NewStandingOrder");
if ($type == 'bank-transfer') {
	$title = $langs->trans("NewPaymentByBankTransfer");
}

llxHeader('', $title);

print load_fiche_titre($title);

print dol_get_fiche_head();

$nb = $bprev->nbOfInvoiceToPay($type);
$pricetowithdraw = $bprev->SommeAPrelever($type);
if ($nb < 0) {
	dol_print_error($bprev->error);
}
print '<table class="border centpercent tableforfield">';

$labeltoshow = $langs->trans("NbOfInvoiceToWithdraw");
if ($type == 'bank-transfer') {
	$labeltoshow = $langs->trans("NbOfInvoiceToPayByBankTransfer");
}

print '<tr><td class="titlefield">'.$labeltoshow.'</td>';
print '<td class="nowraponall">';
print dol_escape_htmltag($nb);
print '</td></tr>';

print '<tr><td>'.$langs->trans("AmountTotal").'</td>';
print '<td class="amount nowraponall">';
print price($pricetowithdraw, 0, $langs, 1, -1, -1, $conf->currency);
print '</td>';
print '</tr>';

print '</table>';
print '</div>';

if ($mesg) {
	print $mesg;
}




/*
 * Invoices waiting for withdraw
 */

$nbtotalofrecords = '';
if (!getDolGlobalInt('MAIN_DISABLE_FULL_SCANLIST')) {
	$result = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($result);
	if (($page * $limit) > $nbtotalofrecords) { // if total resultset is smaller then paging size (filtering), goto and load page 0
		$page = 0;
		$offset = 0;
	}
}

$sql .= $db->plimit($limit + 1, $offset);

$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);

	$arrayofselected = is_array($toselect) ? $toselect : array();

	if ($socid) {
		$soc = new Societe($db);
		$soc->fetch($socid);
		if (empty($search_company)) {
			$search_company = $soc->name;
		}
	}

	$param = '&socid='.$socid;
	if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
		$param .= '&contextpage='.urlencode($contextpage);
	}
	if ($limit > 0 && $limit != $conf->liste_limit) {
		$param .= '&limit='.((int) $limit);
	}
	if ($search_all) {
		$param .= '&search_all='.urlencode($search_all);
	}
	if ($search_datelimit_startday) {
		$param .= '&search_datelimit_startday='.urlencode($search_datelimit_startday);
	}
	if ($search_datelimit_startmonth) {
		$param .= '&search_datelimit_startmonth='.urlencode($search_datelimit_startmonth);
	}
	if ($search_datelimit_startyear) {
		$param .= '&search_datelimit_startyear='.urlencode($search_datelimit_startyear);
	}
	if ($search_datelimit_endday) {
		$param .= '&search_datelimit_endday='.urlencode($search_datelimit_endday);
	}
	if ($search_datelimit_endmonth) {
		$param .= '&search_datelimit_endmonth='.urlencode($search_datelimit_endmonth);
	}
	if ($search_datelimit_endyear) {
		$param .= '&search_datelimit_endyear='.urlencode($search_datelimit_endyear);
	}
	if ($search_ref) {
		$param .= '&search_ref='.urlencode($search_ref);
	}
	if ($search_company) {
		$param .= '&search_company='.urlencode($search_company);
	}
	if ($search_account) {
		$param .= '&search_account'.urlencode($search_account);
	}
	if ($option) {
		$param .= "&search_option=".urlencode($option);
	}

	// List of mass actions available
	$arrayofmassactions = array(
	);
	if (in_array($massaction, array('presend', 'predelete'))) {
		$arrayofmassactions = array();
	}
	$massactionbutton = $form->selectMassAction('', $arrayofmassactions);

	$i = 0;

	print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	print '<input type="hidden" name="type" value="'.$type.'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	// print '<input type="hidden" name="page" value="'.$page.'">';
	// if (!empty($limit)) {
	// 	print '<input type="hidden" name="limit" value="'.$limit.'"/>';
	// }
	// if ($type != '') {
	// 	print '<input type="hidden" name="type" value="'.$type.'">';
	// }

	$title = $langs->trans("InvoiceWaitingWithdraw");
	if ($type == 'bank-transfer') {
		$title = $langs->trans("InvoiceWaitingPaymentByBankTransfer");
	}
	print_barre_liste($title, $page, $_SERVER['PHP_SELF'], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'bill', 0, '', '', $limit, 0, 0, 1);

	// $tradinvoice = "Invoice";
	// if ($type == 'bank-transfer') {
	// 	$tradinvoice = "SupplierInvoice";
	// }

	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';

	if ($search_all) {
		foreach ($fieldstosearchall as $key => $val) {
			$fieldstosearchall[$key] = $langs->trans($val);
		}
		print '<div class="divsearchfieldfilter">'.$langs->trans("FilterOnInto", $search_all).join(', ', $fieldstosearchall).'</div>';
	}

	$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
	$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage); // This also change content of $arrayfields
	$selectedfields .= $form->showCheckAddButtons('checkforselect', 1);

	print '<table class="tagtable liste">';

	// Line for filters
	print '<tr class="liste_titre_filter">';
	// Ref
	if (!empty($arrayfields['f.ref']['checked'])) {
		print '<td class="liste_titre left">';
		print '<input class="flat maxwidth50" type="text" name="search_ref" value="'.$search_ref.'">';
		print '</td>';
	}
	// Ref suppllier
	if (!empty($arrayfields['f.ref_supplier']['checked'])) {
		print '<td class="liste_titre left">';
		print '<input class="flat maxwidth50" type="text" name="search_ref_supplier" value="'.$search_ref_supplier.'">';
		print '</td>';
	}
	// Date due
	if (!empty($arrayfields['f.date_lim_reglement']['checked'])) {
		print '<td class="liste_titre center">';
		print '<div class="nowrap">';
		print $form->selectDate($search_datelimit_end ? $search_datelimit_end : -1, 'search_datelimit_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("Before"));
		print '<br><input type="checkbox" name="search_option" value="late"'.($option == 'late' ? ' checked' : '').'> '.$langs->trans("Alert");
		print '</div>';
		print '</td>';
	}
	// Thirpdarty
	if (!empty($arrayfields['s.nom']['checked'])) {
		print '<td class="liste_titre"><input class="flat maxwidth50" type="text" name="search_company" value="'.$search_company.'"></td>';
	}
	// bank account
	if (!empty($arrayfields['f.fk_account']['checked'])) {
		print '<td class="liste_titre"><input class="flat maxwidth50" type="text" name="search_account" value="'.$search_account.'"></td>';
	}
	// RIB
	if (!empty($arrayfields['pfd.fk_soc_rib']['checked'])) {
		print '<td class="liste_titre">&nbsp;</td>';
	}
	// RUM
	if (!empty($arrayfields['rum']['checked'])) {
		print '<td class="liste_titre">&nbsp;</td>';
	}
	// Amount
	if (!empty($arrayfields['pfd.amount']['checked'])) {
		print '<td class="liste_titre">&nbsp;</td>';
	}
	// Date request
	if (!empty($arrayfields['pfd.date_demande']['checked'])) {
		print '<td class="liste_titre">&nbsp;</td>';
	}
	// Action column
	print '<td class="liste_titre middle">';
	$searchpicto = $form->showFilterButtons();
	print $searchpicto;
	print '</td>';

	print "</tr>\n";

	print '<tr class="liste_titre">';
	// Action column
	if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print_liste_field_titre($selectedfields, $_SERVER['PHP_SELF'], '', '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
	}

	if (!empty($arrayfields['f.ref']['checked'])) {
		print_liste_field_titre($arrayfields['f.ref']['label'], $_SERVER['PHP_SELF'], 'f.ref,f.rowid', '', $param, '', $sortfield, $sortorder);
	}
	if ($type == 'bank-transfer' && !empty($arrayfields['f.ref_supplier']['checked'])) {
		print_liste_field_titre($arrayfields['f.ref_supplier']['label'], $_SERVER['PHP_SELF'], 'f.ref_supplier,f.rowid', '', $param, '', $sortfield, $sortorder);
	}
	if (!empty($arrayfields['f.date_lim_reglement']['checked'])) {
		print_liste_field_titre($arrayfields['f.date_lim_reglement']['label'], $_SERVER['PHP_SELF'], 'f.date_lim_reglement', '', $param, '', $sortfield, $sortorder, 'center ');
	}
	if (!empty($arrayfields['s.nom']['checked'])) {
		print_liste_field_titre($arrayfields['s.nom']['label'], $_SERVER['PHP_SELF'], 's.nom', '', $param, '', $sortfield, $sortorder);
	}
	if (!empty($arrayfields['f.fk_account']['checked'])) {
		print_liste_field_titre($arrayfields['f.fk_account']['label'], $_SERVER['PHP_SELF'], 'f.fk_account', '', $param, '', $sortfield, $sortorder);
	}
	if (!empty($arrayfields['pfd.fk_soc_rib']['checked'])) {
		print_liste_field_titre($arrayfields['pfd.fk_soc_rib']['label'], $_SERVER['PHP_SELF'], 'pfd.fk_soc_rib', '', $param, '', $sortfield, $sortorder);
	}
	if (!empty($arrayfields['rum']['checked'])) {
		print_liste_field_titre($arrayfields['rum']['label'], $_SERVER['PHP_SELF'], '', '', $param, '', $sortfield, $sortorder);
	}
	if (!empty($arrayfields['pfd.amount']['checked'])) {
		print_liste_field_titre($arrayfields['pfd.amount']['label'], $_SERVER['PHP_SELF'], 'pfd.amount', '', $param, '', $sortfield, $sortorder, 'right');
	}
	if (!empty($arrayfields['pfd.date_demande']['checked'])) {
		print_liste_field_titre($arrayfields['pfd.date_demande']['label'], $_SERVER['PHP_SELF'], 'pfd.date_demande', '', $param, '', $sortfield, $sortorder, 'center ');
	}
	if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print_liste_field_titre($selectedfields, $_SERVER['PHP_SELF'], '', '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
	}

	print "</tr>\n";

	if ($num) {
		require_once DOL_DOCUMENT_ROOT.'/societe/class/companybankaccount.class.php';

		while ($i < $num && $i < $limit) {
			$obj = $db->fetch_object($resql);

			$bankaccountstatic = new Account($db);
			$bac = new CompanyBankAccount($db);	// Must include the new in loop so the fetch is clean
			// $bac->fetch(0, $obj->socid);
			$datelimit = $db->jdate($obj->datelimite);
			$invoicestatic->fetch($obj->rowid);
			$thirdpartystatic->fetch($obj->socid);

			if (!empty($obj->fk_soc_rib)) {
				$bac->fetch($obj->fk_soc_rib);
			} else {
				$bac->fetch(0, $obj->socid);
			}

			$invoicestatic->id = $obj->rowid;
			$invoicestatic->ref = $obj->ref;
			$invoicestatic->ref_supplier = $obj->ref_supplier;

			print '<tr class="oddeven">';

			// Action column
			if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
				print '<td class="nowrap center">';
				$selected = 0;
				if (in_array($obj->request_row_id, $arrayofselected) || empty($arrayofselected)) {
					$selected = 1;
				}
				print '<input id="cb'.$obj->request_row_id.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$obj->request_row_id.'"'.($selected ? ' checked="checked"' : '').' amount="'.$obj->amount.'">';
				print '</td>';
			}

			// Ref invoice
			if (!empty($arrayfields['f.ref']['checked'])) {
				print '<td class="tdoverflowmax150">';
				print $invoicestatic->getNomUrl(1, 'withdraw', 0, 0, '', 0, -1, 1);
				print "</td>\n";
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}

			// Ref supplier
			if (!empty($arrayfields['f.ref_supplier']['checked'])) {
				print '<td class="tdoverflowmax100" title="'.dol_escape_htmltag($invoicestatic->ref_supplier).'">';
				print dol_escape_htmltag($invoicestatic->ref_supplier);
				print "</td>\n";
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}

			// Date limit
			if (!empty($arrayfields['f.date_lim_reglement']['checked'])) {
				print '<td class="center nowraponall">'.dol_print_date($datelimit, 'day');
				if ($invoicestatic->hasDelay()) {
					print img_warning($langs->trans('Alert').' - '.$langs->trans('Late'));
				}
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}

			// Third party
			if (!empty($arrayfields['s.nom']['checked'])) {
				print '<td class="tdoverflowmax200">';
				$thirdpartystatic->fetch($obj->socid);
				print $thirdpartystatic->getNomUrl(1, 'ban');
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}

			// Bank account
			if (!empty($arrayfields['f.fk_account']['checked'])) {
				if (!empty($obj->fk_account)) {
					$bankaccountstatic->fetch($obj->fk_account);
					print '<td class="tdoverflowmax200">'.$bankaccountstatic->getNomUrl(1, '', 'reflabel');
					print '<input type="hidden" name="account_searched" value="'.$obj->fk_account.'">';
					print "</td>\n";
				}
				else	print '<td class="tdoverflowmax200">&nbsp;</td>'."\n";
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}

			// RIB
			if (!empty($arrayfields['pfd.fk_soc_rib']['checked'])) {
				print '<td>';
				if ($bac->id > 0) {
					if (!empty($bac->iban) || !empty($bac->bic)) {
						print (!empty($bac->label) ? $bac->label.' - ' : '').$bac->iban.(($bac->iban && $bac->bic) ? ' / ' : '').$bac->bic;
						if ($bac->verif() <= 0) {
							print img_warning('Error on default bank number for IBAN : '.$langs->trans($bac->error_message));
						}
					} else {
						print img_warning($langs->trans("IBANNotDefined"));
					}
				} else {
					print img_warning($langs->trans("NoBankAccountDefined"));
				}
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}

			// RUM
			if (!empty($arrayfields['rum']['checked'])) {
				print '<td>';
				$rumtoshow = $thirdpartystatic->display_rib('rum');
				if ($rumtoshow) {
					print $rumtoshow;
					$format = $thirdpartystatic->display_rib('format');
					if ($type != 'bank-transfer') {
						if ($format) {
							print ' ('.$format.')';
						}
					}
				} else {
					print img_warning($langs->trans("NoBankAccountDefined"));
				}
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}

			// Amount
			if (!empty($arrayfields['pfd.amount']['checked'])) {
				print '<td class="right nowrap"><span id="amount_'.$obj->request_row_id.'" class="amount">'.price($obj->amount)."</span></td>\n";
				if (!$i) {
					$totalarray['nbfield']++;
					$totalarray['pos'][$totalarray['nbfield']] = 'pfd.amount';
				}
				$totalarray['val']['pfd.amount'] += $obj->amount;
			}
			// Date
			if (!empty($arrayfields['pfd.date_demande']['checked'])) {
				print '<td class="center nowraponall">'.dol_print_date($db->jdate($obj->date_demande), 'day').'</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}
			// Action column
			if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
				print '<td class="nowrap center">';
				$selected = 0;
				if (in_array($obj->request_row_id, $arrayofselected) || empty($arrayofselected)) {
					$selected = 1;
				}
				print '<input id="cb'.$obj->request_row_id.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$obj->request_row_id.'"'.($selected ? ' checked="checked"' : '').' amount="'.$obj->amount.'">';
				print '</td>';
			}
			print '</tr>';
			$i++;
		}

		// Show total line
		include DOL_DOCUMENT_ROOT.'/core/tpl/list_print_total.tpl.php';

	} else {
		$colspan = 7;
		if ($type == 'bank-transfer') {
			$colspan++;
		}
		print '<tr class="oddeven"><td colspan="'.$colspan.'"><span class="opacitymedium">'.$langs->trans("None").'</span></td></tr>';
	}

	$db->free($resql);

	print "</table>";
	print "</div>";

	// print "</form>";
	print "<br>\n";
} else {
	dol_print_error($db);
}

print '<div class="tabsAction">'."\n";

print '<input type="hidden" name="searchsql" value="'.$searchsql.'">';
if ($nb) {
	if ($pricetowithdraw) {
		$title = $langs->trans('BankToReceiveWithdraw').': ';
		if ($type == 'bank-transfer') {
			$title = $langs->trans('BankToPayCreditTransfer').': ';
		}
		print '<span class="hideonsmartphone">'.$title.'</span>';
		print img_picto('', 'bank_account');

		$default_account = ($type == 'bank-transfer' ? 'PAYMENTBYBANKTRANSFER_ID_BANKACCOUNT' : 'PRELEVEMENT_ID_BANKACCOUNT');
		print $form->select_comptes(getDolGlobalInt($default_account), 'id_bankaccount', 0, "courant=1", 0, '', 0, 'widthcentpercentminusx maxwidth300', 1);
		print ' &nbsp; &nbsp; ';

		if (empty($executiondate)) {
			$delayindays = 0;
			if ($type != 'bank-transfer') {
				$delayindays = getDolGlobalInt('PRELEVEMENT_ADDDAYS');
			} else {
				$delayindays = getDolGlobalInt('PAYMENTBYBANKTRANSFER_ADDDAYS');
			}

			$executiondate = dol_time_plus_duree(dol_now(), $delayindays, 'd');
		}

		print $langs->trans('ExecutionDate').' ';
		$datere = $executiondate;
		print $form->selectDate($datere, 're');

		print '<span class="hideonsmartphone">'.$langs->trans('Total').' </span>';
		print '<input id="total_checked" value=0 disabled>';
		?>
		<script>

		function computeTotalChecked() {
			let total_checked = 0;
			let checked_pfd = Array.from($('[id^="cb"]').filter(':checked'));
			checked_pfd.forEach((pfd) => {
				let amount = Number(pfd.getAttribute('amount'));
				total_checked += amount;
			})
			let precision = Math.pow(10, <?= getDolGlobalInt('MAIN_MAX_DECIMALS_TOT') ?>);
			$('#total_checked').val(Math.round(total_checked * precision) / precision);
		}

		$('[id^="cb"]').change(computeTotalChecked);
		computeTotalChecked();

		</script>
		<?php
		if ($mysoc->isInEEC()) {
			$title = $langs->trans("CreateForSepa");
			if ($type == 'bank-transfer') {
				$title = $langs->trans("CreateSepaFileForPaymentByBankTransfer");
			}

			if ($type != 'bank-transfer') {
				print '<select name="format">';
				print '<option value="FRST"'.($format == 'FRST' ? ' selected="selected"' : '').'>'.$langs->trans('SEPAFRST').'</option>';
				print '<option value="RCUR"'.($format == 'RCUR' ? ' selected="selected"' : '').'>'.$langs->trans('SEPARCUR').'</option>';
				print '</select>';
			}
			print '<button type="submit" class="butAction margintoponly maringbottomonly" name="action" value="create">'.$title.'</button>'."\n";
		} else {
			$title = $langs->trans("CreateAll");
			if ($type == 'bank-transfer') {
				$title = $langs->trans("CreateFileForPaymentByBankTransfer");
			}
			print '<input type="hidden" name="format" value="ALL">'."\n";
			print '<button type="submit" class="butAction margintoponly maringbottomonly" name="action" value="create">'.$title.'</button>'."\n";
		}
	} else {
		if ($mysoc->isInEEC()) {
			$title = $langs->trans("CreateForSepaFRST");
			if ($type == 'bank-transfer') {
				$title = $langs->trans("CreateSepaFileForPaymentByBankTransfer");
			}
			print '<a class="butActionRefused classfortooltip margintoponly maringbottomonly" href="#" title="'.$langs->trans("AmountMustBePositive").'">'.$title."</a>\n";

			if ($type != 'bank-transfer') {
				$title = $langs->trans("CreateForSepaRCUR");
				print '<a class="butActionRefused classfortooltip margintoponly maringbottomonly" href="#" title="'.$langs->trans("AmountMustBePositive").'">'.$title."</a>\n";
			}
		} else {
			$title = $langs->trans("CreateAll");
			if ($type == 'bank-transfer') {
				$title = $langs->trans("CreateFileForPaymentByBankTransfer");
			}
			print '<a class="butActionRefused classfortooltip margintoponly maringbottomonly" href="#">'.$title."</a>\n";
		}
	}
} else {
	$titlefortab = $langs->transnoentitiesnoconv("StandingOrders");
	$title = $langs->trans("CreateAll");
	if ($type == 'bank-transfer') {
		$titlefortab = $langs->transnoentitiesnoconv("PaymentByBankTransfers");
		$title = $langs->trans("CreateFileForPaymentByBankTransfer");
	}
	print '<a class="butActionRefused classfortooltip margintoponly maringbottomonly" href="#" title="'.dol_escape_htmltag($langs->transnoentitiesnoconv("NoInvoiceToWithdraw", $titlefortab, $titlefortab)).'">';
	print $title;
	print "</a>\n";
}

print "</form>\n";

print "</div>\n";

// End of page
llxFooter();
$db->close();
