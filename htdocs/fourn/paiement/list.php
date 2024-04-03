<?php
/* Copyright (C) 2003-2005	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004		Eric Seigne				<eric.seigne@ryxeo.com>
 * Copyright (C) 2004-2020	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2004		Christophe Combelles	<ccomb@free.fr>
 * Copyright (C) 2005		Marc Barilley / Ocebo	<marc@ocebo.com>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2014		Teddy Andreotti			<125155@supinfo.com>
 * Copyright (C) 2015		Marcos García			<marcosgdf@gmail.com>
 * Copyright (C) 2015		Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2017-2023  Alexandre Spangaro		<aspangaro@easya.solutions>
 * Copyright (C) 2018-2021	Frédéric France			<frederic.france@netlogic.fr>
 * Copyright (C) 2020		Tobias Sekan			<tobias.sekan@startmail.com>
 * Copyright (C) 2021		Ferran Marcet			<fmarcet@2byte.es>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	\file		htdocs/fourn/paiement/list.php
*	\ingroup	fournisseur,facture
 *	\brief		Payment list for supplier invoices
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/paiementfourn.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingjournal.class.php';

// Load translation files required by the page
$langs->loadLangs(array('companies', 'bills', 'banks', 'compta'));

$action = GETPOST('action', 'alpha');
$massaction = GETPOST('massaction', 'alpha');
$optioncss = GETPOST('optioncss', 'alpha');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'vendorpaymentlist';
$mode = GETPOST('mode', 'aZ');
$socid = GETPOST('socid', 'int');

$search_ref				= GETPOST('search_ref', 'alpha');
$search_date_startday	= GETPOST('search_date_startday', 'int');
$search_date_startmonth	= GETPOST('search_date_startmonth', 'int');
$search_date_startyear	= GETPOST('search_date_startyear', 'int');
$search_date_endday		= GETPOST('search_date_endday', 'int');
$search_date_endmonth	= GETPOST('search_date_endmonth', 'int');
$search_date_endyear	= GETPOST('search_date_endyear', 'int');
$search_date_start		= dol_mktime(0, 0, 0, $search_date_startmonth, $search_date_startday, $search_date_startyear);	// Use tzserver
$search_date_end		= dol_mktime(23, 59, 59, $search_date_endmonth, $search_date_endday, $search_date_endyear);
$search_company			= GETPOST('search_company', 'alpha');
$search_payment_type	= GETPOST('search_payment_type');
$search_cheque_num		= GETPOST('search_cheque_num', 'alpha');
$search_bank_account	= GETPOST('search_bank_account', 'int');
$search_amount			= GETPOST('search_amount', 'alpha'); // alpha because we must be able to search on '< x'
$search_sale            = GETPOST('search_sale', 'int');

$limit = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield				= GETPOST('sortfield', 'aZ09comma');
$sortorder				= GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST('page', 'int');

if (empty($page) || $page == -1) {
	$page = 0; // If $page is not defined, or '' or -1
}
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

if (!$sortorder) {
	$sortorder = "DESC";
}
if (!$sortfield) {
	$sortfield = "p.datep";
}

$search_all = trim(GETPOSTISSET("search_all") ? GETPOST("search_all", 'alpha') : GETPOST('sall'));

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
	'p.ref'=>"RefPayment",
	's.nom'=>"ThirdParty",
	'p.num_paiement'=>"Numero",
	'p.amount'=>"Amount",
);

$arrayfields = array(
	'p.ref'				=>array('label'=>"RefPayment", 'checked'=>1, 'position'=>10),
	'p.datep'			=>array('label'=>"Date", 'checked'=>1, 'position'=>20),
	's.nom'				=>array('label'=>"ThirdParty", 'checked'=>1, 'position'=>30),
	'c.libelle'			=>array('label'=>"Type", 'checked'=>1, 'position'=>40),
	'p.num_paiement'	=>array('label'=>"Numero", 'checked'=>1, 'position'=>50, 'tooltip'=>"ChequeOrTransferNumber"),
	'ba.label'			=>array('label'=>"Account", 'checked'=>1, 'position'=>60, 'enable'=>(isModEnabled("banque"))),
	'p.amount'			=>array('label'=>"Amount", 'checked'=>1, 'position'=>70),
);
$arrayfields = dol_sort_array($arrayfields, 'position');

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('paymentsupplierlist'));
$object = new PaiementFourn($db);

if (!$user->hasRight('societe', 'client', 'voir')) {
	$search_sale = $user->id;
}

// Security check
if ($user->socid) {
	$socid = $user->socid;
}

// doesn't work :-(
// restrictedArea($user, 'fournisseur');
// doesn't work :-(
// require_once DOL_DOCUMENT_ROOT.'/fourn/class/paiementfourn.class.php';
// $object = new PaiementFourn($db);
// restrictedArea($user, $object->element);
if (!isModEnabled('supplier_invoice')) {
	accessforbidden();
}
if ((!$user->hasRight("fournisseur", "facture", "lire") && !getDolGlobalString('MAIN_USE_NEW_SUPPLIERMOD'))
	|| (!$user->hasRight("supplier_invoice", "lire") && getDolGlobalString('MAIN_USE_NEW_SUPPLIERMOD'))) {
	accessforbidden();
}


/*
 * Actions
 */

$parameters = array('socid'=>$socid);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) {	// All tests are required to be compatible with all browsers
		$search_ref = '';
		$search_date_startday = '';
		$search_date_startmonth = '';
		$search_date_startyear = '';
		$search_date_endday = '';
		$search_date_endmonth = '';
		$search_date_endyear = '';
		$search_date_start = '';
		$search_date_end = '';
		$search_company = '';
		$search_payment_type = '';
		$search_cheque_num = '';
		$search_bank_account = '';
		$search_amount = '';
		$option = '';
		$toselect = array();
		$search_array_options = array();
	}
}

/*
 * View
 */

llxHeader('', $langs->trans('ListPayment'));

$form = new Form($db);
$formother = new FormOther($db);
$accountstatic = new Account($db);
$companystatic = new Societe($db);
$paymentfournstatic = new PaiementFourn($db);

$sql = 'SELECT p.rowid, p.ref, p.datep, p.fk_bank, p.statut, p.num_paiement as num_payment, p.amount';
$sql .= ', c.code as paiement_type, c.libelle as paiement_libelle';
$sql .= ', ba.rowid as bid, ba.ref as bref, ba.label as blabel, ba.number, ba.account_number as account_number, ba.iban_prefix, ba.bic, ba.currency_code, ba.fk_accountancy_journal as accountancy_journal';
$sql .= ', s.rowid as socid, s.nom as name, s.email';
// We need an aggregate because we added a left join to get the thirdparty. In real world, it should be the same thirdparty if payment is same (but not in database structure)
// so SUM(pf.amount) should be equal to p.amount but if we filter on $socid, it may differ
$sql .= ", SUM(pf.amount) as totalamount, COUNT(f.rowid) as nbinvoices";

$sqlfields = $sql; // $sql fields to remove for count total

$sql .= ' FROM '.MAIN_DB_PREFIX.'paiementfourn AS p';
$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_paiement AS c ON p.fk_paiement = c.id';
$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'bank as b ON p.fk_bank = b.rowid';
$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'bank_account as ba ON b.fk_account = ba.rowid';

$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'paiementfourn_facturefourn AS pf ON p.rowid = pf.fk_paiementfourn';
$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'facture_fourn AS f ON f.rowid = pf.fk_facturefourn';
$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'societe AS s ON s.rowid = f.fk_soc';

$sql .= ' WHERE f.entity IN ('.getEntity('supplier_invoice').')';		// TODO We should use p.entity that does not exists yet in this table
if ($socid > 0) {
	$sql .= " AND EXISTS (SELECT f.fk_soc FROM ".MAIN_DB_PREFIX."facture_fourn as f, ".MAIN_DB_PREFIX."paiementfourn_facturefourn as pf";
	$sql .= " WHERE p.rowid = pf.fk_paiementfourn AND pf.fk_facturefourn = f.rowid AND f.fk_soc = ".((int) $socid).")";
}

// Search criteria
if ($search_ref) {
	$sql .= natural_search('p.ref', $search_ref);
}
if ($search_date_start) {
	$sql .= " AND p.datep >= '" . $db->idate($search_date_start) . "'";
}
if ($search_date_end) {
	$sql .=" AND p.datep <= '" . $db->idate($search_date_end) . "'";
}

if ($search_company) {
	$sql .= natural_search('s.nom', $search_company);
}
if ($search_payment_type != '') {
	$sql .= " AND c.code = '".$db->escape($search_payment_type)."'";
}
if ($search_cheque_num != '') {
	$sql .= natural_search('p.num_paiement', $search_cheque_num);
}
if ($search_amount) {
	$sql .= " AND (".natural_search('p.amount', $search_amount, 1, 1);
	$sql .= " OR ";
	$sql .= natural_search('pf.amount', $search_amount, 1, 1);
	$sql .= ")";
}
if ($search_bank_account > 0) {
	$sql .= ' AND b.fk_account = '.((int) $search_bank_account);
}
if ($search_all) {
	$sql .= natural_search(array_keys($fieldstosearchall), $search_all);
}
// Search on sale representative
if ($search_sale && $search_sale != '-1') {
	if ($search_sale == -2) {
		$sql .= " AND NOT EXISTS (SELECT sc.fk_soc FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc WHERE sc.fk_soc = f.fk_soc)";
	} elseif ($search_sale > 0) {
		$sql .= " AND EXISTS (SELECT sc.fk_soc FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc WHERE sc.fk_soc = f.fk_soc AND sc.fk_user = ".((int) $search_sale).")";
	}
}

// Add where from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';

$sql .= ' GROUP BY p.rowid, p.ref, p.datep, p.fk_bank, p.statut, p.num_paiement, p.amount, s.rowid, s.nom, s.email, c.code, c.libelle,';
$sql .= ' ba.rowid, ba.ref, ba.label, ba.number, ba.account_number, ba.iban_prefix, ba.bic, ba.currency_code, ba.fk_accountancy_journal';

// Count total nb of records
$nbtotalofrecords = '';
if (!getDolGlobalInt('MAIN_DISABLE_FULL_SCANLIST')) {
	/* The fast and low memory method to get and count full list converts the sql into a sql count */
	$sqlforcount = preg_replace('/^'.preg_quote($sqlfields, '/').'/', 'SELECT COUNT(DISTINCT p.rowid) as nbtotalofrecords', $sql);
	$sqlforcount = preg_replace('/GROUP BY .*$/', '', $sqlforcount);
	$resql = $db->query($sqlforcount);
	if ($resql) {
		$objforcount = $db->fetch_object($resql);
		$nbtotalofrecords = $objforcount->nbtotalofrecords;
	} else {
		dol_print_error($db);
	}

	if (($page * $limit) > $nbtotalofrecords) {	// if total resultset is smaller then paging size (filtering), goto and load page 0
		$page = 0;
		$offset = 0;
	}
	$db->free($resql);
}

// Complete request and execute it with limit
$sql .= $db->order($sortfield, $sortorder);
if ($limit) {
	$sql .= $db->plimit($limit + 1, $offset);
}
//print $sql;

$resql = $db->query($sql);
if (!$resql) {
	dol_print_error($db);
	llxFooter();
	$db->close();
	exit;
}

$num = $db->num_rows($resql);
$i = 0;

$param = '';
if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
	$param .= '&contextpage='.urlencode($contextpage);
}
if ($limit > 0 && $limit != $conf->liste_limit) {
	$param .= '&limit='.((int) $limit);
}
if ($optioncss != '') {
	$param .= '&optioncss='.urlencode($optioncss);
}

if ($search_ref) {
	$param .= '&search_ref='.urlencode($search_ref);
}
if ($search_date_startday) {
	$param .= '&search_date_startday='.urlencode($search_date_startday);
}
if ($search_date_startmonth) {
	$param .= '&search_date_startmonth='.urlencode($search_date_startmonth);
}
if ($search_date_startyear) {
	$param .= '&search_date_startyear='.urlencode($search_date_startyear);
}
if ($search_date_endday) {
	$param .= '&search_date_endday='.urlencode($search_date_endday);
}
if ($search_date_endmonth) {
	$param .= '&search_date_endmonth='.urlencode($search_date_endmonth);
}
if ($search_date_endyear) {
	$param .= '&search_date_endyear='.urlencode($search_date_endyear);
}
if ($search_company) {
	$param .= '&search_company='.urlencode($search_company);
}
if ($search_payment_type) {
	$param .= '&search_company='.urlencode($search_payment_type);
}
if ($search_cheque_num) {
	$param .= '&search_cheque_num='.urlencode($search_cheque_num);
}
if ($search_amount) {
	$param .= '&search_amount='.urlencode($search_amount);
}

// Add $param from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

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

print_barre_liste($langs->trans('SupplierPayments'), $page, $_SERVER['PHP_SELF'], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'supplier_invoice', 0, '', '', $limit, 0, 0, 1);

if ($search_all) {
	foreach ($fieldstosearchall as $key => $val) {
		$fieldstosearchall[$key] = $langs->trans($val);
	}
	print '<div class="divsearchfieldfilter">'.$langs->trans("FilterOnInto", $search_all).join(', ', $fieldstosearchall).'</div>';
}

$moreforfilter = '';

$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters); // Note that $action and $object may have been modified by hook
if (empty($reshook)) {
	$moreforfilter .= $hookmanager->resPrint;
} else {
	$moreforfilter = $hookmanager->resPrint;
}

if ($moreforfilter) {
	print '<div class="liste_titre liste_titre_bydiv centpercent">';
	print $moreforfilter;
	print '</div>';
}

$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage, getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN', '')); // This also change content of $arrayfields
if (!empty($massactionbutton)) {
	$selectedfields .= $form->showCheckAddButtons('checkforselect', 1);
}

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

// #
if (getDolGlobalString('MAIN_VIEW_LINE_NUMBER_IN_LIST')) {
	print '<td class="liste_titre">';
	print '</td>';
}

// Filter: Ref
if (!empty($arrayfields['p.ref']['checked'])) {
	print '<td  class="liste_titre left">';
	print '<input class="flat" type="text" size="4" name="search_ref" value="'.dol_escape_htmltag($search_ref).'">';
	print '</td>';
}

// Filter: Date
if (!empty($arrayfields['p.datep']['checked'])) {
	print '<td class="liste_titre center">';
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
if (!empty($arrayfields['c.libelle']['checked'])) {
	print '<td class="liste_titre">';
	$form->select_types_paiements($search_payment_type, 'search_payment_type', '', 2, 1, 1);
	print '</td>';
}

// Filter: Cheque number (fund transfer)
if (!empty($arrayfields['p.num_paiement']['checked'])) {
	print '<td class="liste_titre">';
	print '<input class="flat" type="text" size="4" name="search_cheque_num" value="'.dol_escape_htmltag($search_cheque_num).'">';
	print '</td>';
}

// Filter: Bank account
if (!empty($arrayfields['ba.label']['checked'])) {
	print '<td class="liste_titre">';
	$form->select_comptes($search_bank_account, 'search_bank_account', 0, '', 1);
	print '</td>';
}

// Filter: Amount
if (!empty($arrayfields['p.amount']['checked'])) {
	print '<td class="liste_titre right">';
	print '<input class="flat" type="text" size="4" name="search_amount" value="'.dol_escape_htmltag($search_amount).'">';
	print '</td>';
}

// Fields from hook
$parameters = array('arrayfields'=>$arrayfields);
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
	print getTitleFieldOfList($selectedfields, 0, $_SERVER["PHP_SELF"], '', '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ')."\n";
	$totalarray['nbfield']++;
}
if (getDolGlobalString('MAIN_VIEW_LINE_NUMBER_IN_LIST')) {
	print_liste_field_titre('#', $_SERVER['PHP_SELF'], '', '', $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['p.ref']['checked'])) {
	print_liste_field_titre($arrayfields['p.ref']['label'], $_SERVER["PHP_SELF"], 'p.rowid', '', $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['p.datep']['checked'])) {
	print_liste_field_titre($arrayfields['p.datep']['label'], $_SERVER["PHP_SELF"], 'p.datep', '', $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['s.nom']['checked'])) {
	print_liste_field_titre($arrayfields['s.nom']['label'], $_SERVER["PHP_SELF"], 's.nom', '', $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['c.libelle']['checked'])) {
	print_liste_field_titre($arrayfields['c.libelle']['label'], $_SERVER["PHP_SELF"], 'c.libelle', '', $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['p.num_paiement']['checked'])) {
	print_liste_field_titre($arrayfields['p.num_paiement']['label'], $_SERVER["PHP_SELF"], "p.num_paiement", '', $param, '', $sortfield, $sortorder, '', $arrayfields['p.num_paiement']['tooltip']);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['ba.label']['checked'])) {
	print_liste_field_titre($arrayfields['ba.label']['label'], $_SERVER["PHP_SELF"], 'ba.label', '', $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['p.amount']['checked'])) {
	print_liste_field_titre($arrayfields['p.amount']['label'], $_SERVER["PHP_SELF"], 'p.amount', '', $param, '', $sortfield, $sortorder, 'right ');
	$totalarray['nbfield']++;
}

// Hook fields
$parameters = array('arrayfields'=>$arrayfields, 'param'=>$param, 'sortfield'=>$sortfield, 'sortorder'=>$sortorder);
$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

// Action column
if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print getTitleFieldOfList($selectedfields, 0, $_SERVER["PHP_SELF"], '', '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ')."\n";
	$totalarray['nbfield']++;
}
print '</tr>';

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
	$objp = $db->fetch_object($resql);
	if (empty($objp)) {
		break; // Should not happen
	}

	$paymentfournstatic->id = $objp->rowid;
	$paymentfournstatic->ref = $objp->ref;
	$paymentfournstatic->datepaye = $db->jdate($objp->datep);
	$paymentfournstatic->amount = $objp->amount;

	$companystatic->id = $objp->socid;
	$companystatic->name = $objp->name;
	$companystatic->email = $objp->email;

	if ($mode == 'kanban') {
		if ($i == 0) {
			print '<tr class="trkanban"><td colspan="'.$savnbfield.'">';
			print '<div class="box-flex-container kanban">';
		}
		// Output Kanban
		$selected = -1;
		if ($massactionbutton || $massaction) { // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
			$selected = 0;
			if (in_array($object->id, $arrayofselected)) {
				$selected = 1;
			}
		}
		//print $object->getKanbanView('', array('thirdparty'=>$object->thirdparty, 'selected' => $selected));
		print $object->getKanbanView('', array('selected' => $selected));
		if ($i == ($imaxinloop - 1)) {
			print '</div>';
			print '</td></tr>';
		}
	} else {
		// Show line of result
		$j = 0;
		print '<tr data-rowid="'.$object->id.'" class="oddeven">';

		// Action column
		if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			print '<td class="nowrap center">';
			if ($massactionbutton || $massaction) { // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
				$selected = 0;
				if (in_array($object->id, $arrayofselected)) {
					$selected = 1;
				}
				print '<input id="cb'.$object->id.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$object->id.'"'.($selected ? ' checked="checked"' : '').'>';
			}
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// No
		if (getDolGlobalString('MAIN_VIEW_LINE_NUMBER_IN_LIST')) {
			print '<td class="nowraponall">'.(($offset * $limit) + $i).'</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Ref
		if (!empty($arrayfields['p.ref']['checked'])) {
			print '<td class="nowraponall">'.$paymentfournstatic->getNomUrl(1).'</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Date
		if (!empty($arrayfields['p.datep']['checked'])) {
			$dateformatforpayment = 'dayhour';
			print '<td class="nowraponall center">'.dol_print_date($db->jdate($objp->datep), $dateformatforpayment).'</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Thirdparty
		if (!empty($arrayfields['s.nom']['checked'])) {
			print '<td class="tdoverflowmax125">';
			if ($objp->socid > 0) {
				print $companystatic->getNomUrl(1, '', 24);
			}
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Pyament type
		if (!empty($arrayfields['c.libelle']['checked'])) {
			$payment_type = $langs->trans("PaymentType".$objp->paiement_type) != "PaymentType".$objp->paiement_type ? $langs->trans("PaymentType".$objp->paiement_type) : $objp->paiement_libelle;
			print '<td>'.$payment_type.' '.dol_trunc($objp->num_payment, 32).'</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Cheque number (fund transfer)
		if (!empty($arrayfields['p.num_paiement']['checked'])) {
			print '<td>'.$objp->num_payment.'</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Bank account
		if (!empty($arrayfields['ba.label']['checked'])) {
			print '<td class="tdoverflowmax125">';
			if ($objp->bid) {
				$accountstatic->id = $objp->bid;
				$accountstatic->ref = $objp->bref;
				$accountstatic->label = $objp->blabel;
				$accountstatic->number = $objp->number;
				$accountstatic->iban = $objp->iban_prefix;
				$accountstatic->bic = $objp->bic;
				$accountstatic->currency_code = $objp->currency_code;
				$accountstatic->account_number = $objp->account_number;

				$accountingjournal = new AccountingJournal($db);
				$accountingjournal->fetch($objp->accountancy_journal);
				$accountstatic->accountancy_journal = $accountingjournal->code;

				print $accountstatic->getNomUrl(1);
			}
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Amount
		if (!empty($arrayfields['p.amount']['checked'])) {
			print '<td class="right">';
			if ($objp->nbinvoices > 1 || ($objp->totalamount && $objp->amount != $objp->totalamount)) {
				print $form->textwithpicto('', $langs->trans("PaymentMadeForSeveralInvoices"));
			}
			print '<span class="amount">'.price($objp->amount).'</span>';
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
				$totalarray['pos'][$totalarray['nbfield']] = 'amount';
			}
			if (empty($totalarray['val']['amount'])) {
				$totalarray['val']['amount'] = $objp->amount;
			} else {
				$totalarray['val']['amount'] += $objp->amount;
			}
		}

		if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			print '<td class="nowrap center">';
			if ($massactionbutton || $massaction) { // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
				$selected = 0;
				if (in_array($object->id, $arrayofselected)) {
					$selected = 1;
				}
				print '<input id="cb'.$object->id.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$object->id.'"'.($selected ? ' checked="checked"' : '').'>';
			}
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		print '</tr>'."\n";
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

// End of page
llxFooter();
$db->close();
