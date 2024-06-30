<?php
/* Copyright (C) 2001-2003  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2018  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2013       Cédric Salvador         <csalvador@gpcsolutions.fr>
 * Copyright (C) 2019       Thibault FOUCART        <support@ptibogxiv.net>
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
 *  \file       htdocs/don/list.php
 *  \ingroup    donations
 *  \brief      List of donations
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/don/class/don.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingjournal.class.php';

// Load translation files required by the page
$langs->loadLangs(array('companies', 'donations'));

$action     = GETPOST('action', 'aZ09') ? GETPOST('action', 'aZ09') : 'view'; // The action 'create'/'add', 'edit'/'update', 'view', ...
$massaction = GETPOST('massaction', 'alpha'); // The bulk action (combo box choice into lists)
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'sclist';
$mode = GETPOST('mode', 'alpha');

$paiementid				= GETPOSTINT('paiementid');

$search_ref = GETPOST("search_ref", "alpha");
$search_date_startday = GETPOSTINT('search_date_startday');
$search_date_startmonth = GETPOSTINT('search_date_startmonth');
$search_date_startyear = GETPOSTINT('search_date_startyear');
$search_date_endday = GETPOSTINT('search_date_endday');
$search_date_endmonth = GETPOSTINT('search_date_endmonth');
$search_date_endyear = GETPOSTINT('search_date_endyear');
$search_date_start = dol_mktime(0, 0, 0, $search_date_startmonth, $search_date_startday, $search_date_startyear);
$search_date_end = dol_mktime(23, 59, 59, $search_date_endmonth, $search_date_endday, $search_date_endyear);
$search_company = GETPOST("search_company", 'alpha');
$search_paymenttype = GETPOST("search_paymenttype", "intcomma");
$search_account = GETPOST("search_account", 'alpha');
$search_payment_num = GETPOST('search_payment_num', 'alpha');
$search_amount = GETPOST("search_amount", 'alpha');
$search_status = GETPOST('search_status', 'intcomma');

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
	$sortorder = "DESC";
}
if (!$sortfield) {
	$sortfield = "pd.rowid";
}

$search_all = trim(GETPOSTISSET("search_all") ? GETPOST("search_all", 'alpha') : GETPOST('sall'));

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
	'pd.rowid' => "RefPayment",
	's.nom' => "ThirdParty",
	'pd.num_paiement' => "Numero",
	'pd.amount' => "Amount",
);

$arrayfields = array(
	'pd.rowid'				=> array('label' => "RefPayment", 'checked' => 1, 'position' => 10),
	'pd.datep'			=> array('label' => "Date", 'checked' => 1, 'position' => 20),
	's.nom'				=> array('label' => "ThirdParty", 'checked' => 1, 'position' => 30),
	'c.code'			=> array('label' => "Type", 'checked' => 1, 'position' => 40),
	'pd.num_paiement'	=> array('label' => "Numero", 'checked' => 1, 'position' => 50, 'tooltip' => "ChequeOrTransferNumber"),
	'transaction'		=> array('label' => "BankTransactionLine", 'checked' => 1, 'position' => 60, 'enabled' => (isModEnabled("bank"))),
	'ba.label'			=> array('label' => "BankAccount", 'checked' => 1, 'position' => 70, 'enabled' => (isModEnabled("bank"))),
	'pd.amount'			=> array('label' => "Amount", 'checked' => 1, 'position' => 80),
);
$arrayfields = dol_sort_array($arrayfields, 'position');
'@phan-var-force array<string,array{label:string,checked?:int<0,1>,position?:int,help?:string}> $arrayfields';  // dol_sort_array looses type for Phan

$optioncss = GETPOST('optioncss', 'alpha');
$moreforfilter = GETPOST('moreforfilter', 'alpha');

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('donationlist'));

// Security check
$result = restrictedArea($user, 'don');

$permissiontoread = $user->hasRight('don', 'read');
$permissiontoadd = $user->hasRight('don', 'write');
$permissiontodelete = $user->hasRight('don', 'delete');

/*
 * Actions
 */

$parameters = array('socid' => $paiementid);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

	// All tests are required to be compatible with all browsers
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
		$search_ref = '';
		$search_date_startday = '';
		$search_date_startmonth = '';
		$search_date_startyear = '';
		$search_date_endday = '';
		$search_date_endmonth = '';
		$search_date_endyear = '';
		$search_date_start = '';
		$search_date_end = '';
		$search_account = '';
		$search_amount = '';
		$search_paymenttype = '';
		$search_payment_num = '';
		$search_company = '';
		$option = '';
		$toselect = array();
		$search_array_options = array();
	}
}

/*
 * View
 */

$form = new Form($db);
$donationstatic = new Don($db);
$companystatic = new Societe($db);
$bankline = new AccountLine($db);
$accountstatic = new Account($db);

$title = $langs->trans("Donations");
$help_url = 'EN:Module_Donations|FR:Module_Dons|ES:M&oacute;dulo_Donaciones|DE:Modul_Spenden';

// Build and execute select
// --------------------------------------------------------------------
$sql = "SELECT pd.rowid as payment_id, pd.amount, pd.datep, pd.fk_typepayment, pd.num_payment, pd.amount, pd.fk_bank, ";
$sql .= ' s.rowid as soc_id, s.nom, ';
$sql .= ' d.societe, ';
$sql .= ' c.code as paiement_code, ';
$sql .= ' d.rowid, ba.rowid as bid, ba.ref as bref, ba.label as blabel, ba.number, ba.account_number as account_number, ba.iban_prefix, ba.bic, ba.currency_code, ba.fk_accountancy_journal as accountancy_journal ';

// Add fields from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;

$sqlfields = $sql; // $sql fields to remove for count total

$sql .= " FROM ".MAIN_DB_PREFIX."payment_donation as pd";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."don as d ON (d.rowid = pd.fk_donation)";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."bank as b ON (b.rowid = pd.fk_bank)";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."bank_account as ba ON (ba.rowid = b.fk_account)";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON (s.rowid = d.fk_soc)";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_paiement as c ON (c.id = pd.fk_typepayment)";
$sql .= " WHERE d.entity IN (".getEntity('donation').")";

if ($search_ref) {
	$sql .= natural_search('pd.rowid', $search_ref);
}
if ($search_date_start) {
	$sql .= " AND pd.datep >= '" . $db->idate($search_date_start) . "'";
}
if ($search_date_end) {
	$sql .= " AND pd.datep <= '" . $db->idate($search_date_end) . "'";
}
if ($search_account > 0) {
	$sql .= " AND b.fk_account=".((int) $search_account);
}
if ($search_paymenttype != '') {
	$sql .= " AND c.code='".$db->escape($search_paymenttype)."'";
}
if ($search_payment_num != '') {
	$sql .= natural_search('pd.num_payment', $search_payment_num);
}
if ($search_amount) {
	$sql .= natural_search('pd.amount', $search_amount, 1);
}
if ($search_company) {
	$sql .= natural_search('s.nom', $search_company);
}
if ($search_all) {
	$sql .= natural_search(array_keys($fieldstosearchall), $search_all);
}

// Add where from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;

// Count total nb of records
$nbtotalofrecords = '';
if (!getDolGlobalInt('MAIN_DISABLE_FULL_SCANLIST')) {
	/* The fast and low memory method to get and count full list converts the sql into a sql count */
	$sqlforcount = preg_replace('/^'.preg_quote($sqlfields, '/').'/', 'SELECT COUNT(*) as nbtotalofrecords', $sql);
	$sqlforcount = preg_replace('/GROUP BY .*$/', '', $sqlforcount);
	$resql = $db->query($sqlforcount);
	if ($resql) {
		$objforcount = $db->fetch_object($resql);
		$nbtotalofrecords = $objforcount->nbtotalofrecords;
	} else {
		dol_print_error($db);
	}

	if (($page * $limit) > $nbtotalofrecords) {	// if total resultset is smaller than the paging size (filtering), goto and load page 0
		$page = 0;
		$offset = 0;
	}
	$db->free($resql);
}

$sql .= $db->order($sortfield, $sortorder);

// Complete request and execute it with limit
if ($limit) {
	$sql .= $db->plimit($limit + 1, $offset);
}

$resql = $db->query($sql);

if (!$resql) {
	dol_print_error($db);
	exit;
}

$num = $db->num_rows($resql);

// Output page
// --------------------------------------------------------------------

llxHeader('', $title, $help_url, '', 0, 0, $morejs, $morecss, '', 'bodyforlist');	// Can use also classforhorizontalscrolloftabs instead of bodyforlist for no horizontal scroll

$param = '';
if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
	$param .= '&contextpage='.urlencode($contextpage);
}
if ($limit > 0 && $limit != $conf->liste_limit) {
	$param .= '&limit='.((int) $limit);
}
if ($search_ref) {
	$param .= '&search_ref='.urlencode($search_ref);
}
if ($search_date_startday) {
	$param .= '&search_date_startday='.urlencode((string) ($search_date_startday));
}
if ($search_date_startmonth) {
	$param .= '&search_date_startmonth='.urlencode((string) ($search_date_startmonth));
}
if ($search_date_startyear) {
	$param .= '&search_date_startyear='.urlencode((string) ($search_date_startyear));
}
if ($search_date_endday) {
	$param .= '&search_date_endday='.urlencode((string) ($search_date_endday));
}
if ($search_date_endmonth) {
	$param .= '&search_date_endmonth='.urlencode((string) ($search_date_endmonth));
}
if ($search_date_endyear) {
	$param .= '&search_date_endyear='.urlencode((string) ($search_date_endyear));
}
if ($search_company) {
	$param .= '&search_company='.urlencode($search_company);
}
if ($search_amount != '') {
	$param .= '&search_amount='.urlencode($search_amount);
}
if ($search_paymenttype) {
	$param .= '&search_paymenttype='.urlencode($search_paymenttype);
}
if ($search_account) {
	$param .= '&search_account='.urlencode((string) ($search_account));
}
if ($search_payment_num) {
	$param .= '&search_payment_num='.urlencode($search_payment_num);
}
if ($optioncss != '') {
	$param .= '&optioncss='.urlencode($optioncss);
}

print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
if ($optioncss != '') {
	print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
}
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';

// @phan-suppress-next-line PhanPluginSuspiciousParamOrder
print_barre_liste($langs->trans("DonationPayments"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'bill', 0, '', '', $limit, 0, 0, 1);

if ($search_all) {
	foreach ($fieldstosearchall as $key => $val) {
		$fieldstosearchall[$key] = $langs->trans($val);
	}
	print '<div class="divsearchfieldfilter">'.$langs->trans("FilterOnInto", $search_all).implode(', ', $fieldstosearchall).'</div>';
}

$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage, getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')); // This also change content of $arrayfields
$massactionbutton = '';
if ($massactionbutton) {
	$selectedfields .= $form->showCheckAddButtons('checkforselect', 1);
}

$moreforfilter = '';
print '<div class="div-table-responsive">';
print '<table class="tagtable nobottomiftotal liste'.($moreforfilter ? " listwithfilterbefore" : '').'">';

// Fields title search
// --------------------------------------------------------------------
print '<tr class="liste_titre_filter">';
// Action column
if (getDolGlobalInt('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print '<td class="liste_titre center maxwidthsearch">';
	$searchpicto = $form->showFilterButtons('left');
	print $searchpicto;
	print '</td>';
}

if (getDolGlobalString('MAIN_VIEW_LINE_NUMBER_IN_LIST')) {
	print '<td class="liste_titre">';
	print '</td>';
}

if (!empty($arrayfields['pd.rowid']['checked'])) {
	print '<td class="liste_titre left">';
	print '<input class="flat" type="text" size="4" name="search_ref" value="'.dol_escape_htmltag($search_ref).'">';
	print '</td>';
}

// Filter: Date
if (!empty($arrayfields['pd.datep']['checked'])) {
	print '<td class="liste_titre">';
	print '<div class="nowrapfordate">';
	print $form->selectDate($search_date_start ? $search_date_start : -1, 'search_date_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
	print '</div>';
	print '<div class="nowrapfordate">';
	print $form->selectDate($search_date_end ? $search_date_end : -1, 'search_date_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
	print '</div>';
	print '</td>';
}

// Filter: Thirdparty
if (!empty($arrayfields['s.nom']['checked'])) {
	print '<td class="liste_titre">';
	print '<input class="flat" type="text" size="6" name="search_company" value="'.dol_escape_htmltag($search_company).'">';
	print '</td>';
}

// Filter: Payment type
if (!empty($arrayfields['c.code']['checked'])) {
	print '<td class="liste_titre">';
	print $form->select_types_paiements($search_paymenttype, 'search_paymenttype', '', 2, 1, 1, 0, 1, 'maxwidth100', 1);
	print '</td>';
}

// Filter: Bank transaction number
if (!empty($arrayfields['transaction']['checked'])) {
	print '<td class="liste_titre">';
	print '<input class="flat" type="text" size="4" name="search_payment_num" value="'.dol_escape_htmltag($search_payment_num).'">';
	print '</td>';
}

// Filter: Cheque number (fund transfer)
if (!empty($arrayfields['pd.num_paiement']['checked'])) {
	print '<td class="liste_titre">';
	print '</td>';
}

// Filter: Bank account
if (!empty($arrayfields['ba.label']['checked'])) {
	print '<td class="liste_titre">';
	$form->select_comptes($search_account, 'search_account', 0, '', 1);
	print '</td>';
}

// Filter: Amount
if (!empty($arrayfields['pd.amount']['checked'])) {
	print '<td class="liste_titre right">';
	print '<input class="flat" type="text" size="6" name="search_amount" value="'.dol_escape_htmltag($search_amount).'">';
	print '</td>';
}

// Fields from hook
$parameters = array('arrayfields' => $arrayfields);
$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

// Action column
if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print '<td class="liste_titre center maxwidthsearch">';
	$searchpicto = $form->showFilterButtons();
	print $searchpicto;
	print '</td>';
}

print '</tr>'."\n";

$totalarray = array();
$totalarray['nbfield'] = 0;

// Fields title label
// --------------------------------------------------------------------
print '<tr class="liste_titre">';
// Action column
if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', 'align="center"', $sortfield, $sortorder, 'maxwidthsearch ');
	$totalarray['nbfield']++;
}
if (getDolGlobalString('MAIN_VIEW_LINE_NUMBER_IN_LIST')) {
	print_liste_field_titre('#', $_SERVER['PHP_SELF'], '', '', $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['pd.rowid']['checked'])) {
	print_liste_field_titre($arrayfields['pd.rowid']['label'], $_SERVER["PHP_SELF"], "pd.rowid", '', $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['pd.datep']['checked'])) {
	print_liste_field_titre($arrayfields['pd.datep']['label'], $_SERVER["PHP_SELF"], "pd.datep", '', $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['s.nom']['checked'])) {
	print_liste_field_titre($arrayfields['s.nom']['label'], $_SERVER["PHP_SELF"], "s.nom", '', $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['c.code']['checked'])) {
	print_liste_field_titre($arrayfields['c.code']['label'], $_SERVER["PHP_SELF"], "c.code", '', $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['pd.num_paiement']['checked'])) {
	print_liste_field_titre($arrayfields['pd.num_paiement']['label'], $_SERVER["PHP_SELF"], "pd.num_payment", '', $param, '', $sortfield, $sortorder, '', $arrayfields['p.num_paiement']['tooltip']);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['transaction']['checked'])) {
	print_liste_field_titre($arrayfields['transaction']['label'], $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['ba.label']['checked'])) {
	print_liste_field_titre($arrayfields['ba.label']['label'], $_SERVER["PHP_SELF"], "ba.label", '', $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['pd.amount']['checked'])) {
	print_liste_field_titre($arrayfields['pd.amount']['label'], $_SERVER["PHP_SELF"], "pd.amount", '', $param, '', $sortfield, $sortorder, 'right ');
	$totalarray['nbfield']++;
}

// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';
// Hook fields
$parameters = array('arrayfields' => $arrayfields, 'param' => $param, 'sortfield' => $sortfield, 'sortorder' => $sortorder);
$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
// Action column
if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', 'align="center"', $sortfield, $sortorder, 'maxwidthsearch ');
	$totalarray['nbfield']++;
}
print '</tr>'."\n";

// Detect if we need a fetch on each output line
$needToFetchEachLine = 0;
if (isset($extrafields->attributes[$object->table_element]['computed']) && is_array($extrafields->attributes[$object->table_element]['computed']) && count($extrafields->attributes[$object->table_element]['computed']) > 0) {
	foreach ($extrafields->attributes[$object->table_element]['computed'] as $key => $val) {
		if (!is_null($val) && preg_match('/\$object/', $val)) {
			$needToFetchEachLine++; // There is at least one compute field that use $object
		}
	}
}

// Loop on record
// --------------------------------------------------------------------
$i = 0;
$savnbfield = $totalarray['nbfield'];
$totalarray = array();
$totalarray['nbfield'] = 0;
$imaxinloop = ($limit ? min($num, $limit) : $num);
while ($i < $imaxinloop) {
	$obj = $db->fetch_object($resql);

	$companystatic->id = $obj->soc_id;
	$companystatic->name = $obj->nom;

	$company = new Societe($db);
	$result = $company->fetch($obj->socid);

	print '<tr class="oddeven">';
	if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print '<td class="nowrap center">';
		if ($massactionbutton || $massaction) { // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
			$selected = 0;
			if (in_array($obj->id_paiement, $arrayofselected)) {
				$selected = 1;
			}
			print '<input id="cb'.$obj->id_paiement.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$obj->id_paiement.'"'.($selected ? ' checked="checked"' : '').'>';
		}
		print '</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	// Ref
	if (!empty($arrayfields['pd.rowid']['checked'])) {
		print '<td><a href="' . DOL_URL_ROOT . '/don/payment/card.php?id=' . $obj->payment_id . '">' . img_object($langs->trans("Payment"), "payment") . ' ' . $obj->payment_id . '</a></td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	// Date
	if (!empty($arrayfields['pd.datep']['checked'])) {
		$dateformatforpayment = 'dayhour';
		print '<td class="nowraponall">'.dol_print_date($db->jdate($obj->datep), $dateformatforpayment, 'tzuser').'</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	// Thirdparty
	if (!empty($arrayfields['s.nom']['checked'])) {
		print '<td class="nowraponall">';
		if ($obj->soc_id > 0) {
			print $companystatic->getNomUrl(1, '', 24);
		} else {
			print $donationstatic->societe = $obj->societe;
		}
		print '</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	// Payment type
	if (!empty($arrayfields['c.code']['checked'])) {
		print '<td>'.$langs->trans("PaymentTypeShort".$obj->paiement_code).'</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	// Filter: Cheque number (fund transfer)
	if (!empty($arrayfields['pd.num_paiement']['checked'])) {
		print '<td>'.$obj->num_payment.'</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	// Bank transaction
	if (!empty($arrayfields['transaction']['checked'])) {
		print '<td class="tdoverflowmax125">';
		if ($obj->fk_bank > 0) {
			$test = $bankline->fetch($obj->fk_bank);
			print $bankline->getNomUrl(1, 0);
		}
		print '</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	// Bank account
	if (!empty($arrayfields['ba.label']['checked'])) {
		print '<td>';
		if ($obj->bid > 0) {
			$accountstatic->id = $obj->bid;
			$accountstatic->ref = $obj->bref;
			$accountstatic->label = $obj->blabel;
			$accountstatic->number = $obj->number;
			$accountstatic->account_number = $obj->account_number;

			$accountingjournal = new AccountingJournal($db);
			$accountingjournal->fetch($obj->accountancy_journal);
			$accountstatic->accountancy_journal = $accountingjournal->code;

			print $accountstatic->getNomUrl(1);
		}
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	// Amount
	if (!empty($arrayfields['pd.amount']['checked'])) {
		print '<td class="right"><span class="amount">' . price($obj->amount) . '</span></td>';
		if (!$i) {
			$totalarray['nbfield']++;
			$totalarray['pos'][$totalarray['nbfield']] = 'amount';
		}
		if (empty($totalarray['val']['amount'])) {
			$totalarray['val']['amount'] = $obj->amount;
		} else {
			$totalarray['val']['amount'] += $obj->amount;
		}
	}

	$i++;
}

// Show total line
include DOL_DOCUMENT_ROOT.'/core/tpl/list_print_total.tpl.php';

// If no record found
if ($num == 0) {
	$colspan = 1;
	foreach ($arrayfields as $key => $val) {
		if (!empty($val['checked'])) {
			$colspan++;
		}
	}
	print '<tr><td colspan="'.$colspan.'"><span class="opacitymedium">'.$langs->trans("NoRecordFound").'</span></td></tr>';
}

$db->free($resql);

$parameters = array('arrayfields' => $arrayfields, 'sql' => $sql);
$reshook = $hookmanager->executeHooks('printFieldListFooter', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

print '</table>'."\n";
print '</div>'."\n";

print '</form>'."\n";

llxFooter();
$db->close();
