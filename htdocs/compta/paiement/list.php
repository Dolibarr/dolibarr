<?php
/* Copyright (C) 2001-2006	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2013		Cédric Salvador			<csalvador@gpcsolutions.fr>
 * Copyright (C) 2015		Jean-François Ferry		<jfefe@aternatik.fr>
 * Copyright (C) 2015		Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2017-2021	Alexandre Spangaro		<aspangaro@open-dsi.fr>
 * Copyright (C) 2018-2021	Ferran Marcet			<fmarcet@2byte.es>
 * Copyright (C) 2018		Charlene Benke			<charlie@patas-monkey.com>
 * Copyright (C) 2020		Tobias Sekan			<tobias.sekan@startmail.com>
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
 *	\file       htdocs/compta/paiement/list.php
 *  \ingroup    compta
 *  \brief      Payment page for customer invoices
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingjournal.class.php';

// Load translation files required by the page
$langs->loadLangs(array('bills', 'banks', 'compta', 'companies'));

$action				= GETPOST('action', 'alpha');
$massaction			= GETPOST('massaction', 'alpha');
$confirm			= GETPOST('confirm', 'alpha');
$optioncss = GETPOST('optioncss', 'alpha');
$contextpage		= GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'paymentlist';

$facid				= GETPOST('facid', 'int');
$socid				= GETPOST('socid', 'int');
$userid = GETPOST('userid', 'int');

// Security check
if ($user->socid) $socid = $user->socid;
$result = restrictedArea($user, 'facture', $facid, '');

$search_ref = GETPOST("search_ref", "alpha");
$search_date_startday = GETPOST('search_date_startday', 'int');
$search_date_startmonth = GETPOST('search_date_startmonth', 'int');
$search_date_startyear = GETPOST('search_date_startyear', 'int');
$search_date_endday = GETPOST('search_date_endday', 'int');
$search_date_endmonth = GETPOST('search_date_endmonth', 'int');
$search_date_endyear = GETPOST('search_date_endyear', 'int');
$search_date_start = dol_mktime(0, 0, 0, $search_date_startmonth, $search_date_startday, $search_date_startyear);	// Use tzserver
$search_date_end = dol_mktime(23, 59, 59, $search_date_endmonth, $search_date_endday, $search_date_endyear);
$search_company = GETPOST("search_company", 'alpha');
$search_paymenttype = GETPOST("search_paymenttype");
$search_account = GETPOST("search_account", "int");
$search_payment_num = GETPOST('search_payment_num', 'alpha');
$search_amount = GETPOST("search_amount", 'alpha'); // alpha because we must be able to search on "< x"
$search_status = GETPOST('search_status', 'intcomma');

$limit = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield			= GETPOST('sortfield', 'aZ09comma');
$sortorder			= GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');

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
	$sortfield = "p.ref";
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
	'p.ref'				=> array('label'=>"RefPayment", 'checked'=>1, 'position'=>10),
	'p.datep'			=> array('label'=>"Date", 'checked'=>1, 'position'=>20),
	's.nom'				=> array('label'=>"ThirdParty", 'checked'=>1, 'position'=>30),
	'c.libelle'			=> array('label'=>"Type", 'checked'=>1, 'position'=>40),
	'transaction'		=> array('label'=>"BankTransactionLine", 'checked'=>1, 'position'=>50, 'enabled'=>isModEnabled('banque')),
	'ba.label'			=> array('label'=>"Account", 'checked'=>1, 'position'=>60, 'enabled'=>(isModEnabled('banque'))),
	'p.num_paiement'	=> array('label'=>"Numero", 'checked'=>1, 'position'=>70, 'tooltip'=>"ChequeOrTransferNumber"),
	'p.amount'			=> array('label'=>"Amount", 'checked'=>1, 'position'=>80),
	'p.statut'			=> array('label'=>"Status", 'checked'=>1, 'position'=>90, 'enabled'=>(!empty($conf->global->BILL_ADD_PAYMENT_VALIDATION))),
);
$arrayfields = dol_sort_array($arrayfields, 'position');

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('paymentlist'));
$object = new Paiement($db);

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
		$search_status = '';
		$option = '';
		$toselect = array();
		$search_array_options = array();
	}
}

/*
 * View
 */

$form = new Form($db);
$formother = new FormOther($db);
$accountstatic = new Account($db);
$companystatic = new Societe($db);
$bankline = new AccountLine($db);

llxHeader('', $langs->trans('ListPayment'));

if (GETPOST("orphelins", "alpha")) {
	// Payments not linked to an invoice. Should not happend. For debug only.
	$sql = "SELECT p.rowid, p.ref, p.datep, p.amount, p.statut, p.num_paiement";
	$sql .= ", c.code as paiement_code";

	// Add fields from hooks
	$parameters = array();
	$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters); // Note that $action and $object may have been modified by hook
	$sql .= $hookmanager->resPrint;
	$sql .= " FROM ".MAIN_DB_PREFIX."paiement as p LEFT JOIN ".MAIN_DB_PREFIX."c_paiement as c ON p.fk_paiement = c.id";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."paiement_facture as pf ON p.rowid = pf.fk_paiement";
	$sql .= " WHERE p.entity IN (".getEntity('invoice').")";
	$sql .= " AND pf.fk_facture IS NULL";

	// Add where from hooks
	$parameters = array();
	$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters); // Note that $action and $object may have been modified by hook
	$sql .= $hookmanager->resPrint;
} else {
	// DISTINCT is to avoid duplicate when there is a link to sales representatives
	$sql = "SELECT DISTINCT p.rowid, p.ref, p.datep, p.fk_bank, p.amount, p.statut, p.num_paiement";
	$sql .= ", c.code as paiement_code";
	$sql .= ", ba.rowid as bid, ba.ref as bref, ba.label as blabel, ba.number, ba.account_number as account_number, ba.fk_accountancy_journal as accountancy_journal";
	$sql .= ", s.rowid as socid, s.nom as name, s.email";

	// Add fields from hooks
	$parameters = array();
	$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters); // Note that $action and $object may have been modified by hook
	$sql .= $hookmanager->resPrint;
	$sql .= " FROM ".MAIN_DB_PREFIX."paiement as p";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_paiement as c ON p.fk_paiement = c.id";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."bank as b ON p.fk_bank = b.rowid";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."bank_account as ba ON b.fk_account = ba.rowid";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."paiement_facture as pf ON p.rowid = pf.fk_paiement";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."facture as f ON pf.fk_facture = f.rowid";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON f.fk_soc = s.rowid";
	if (empty($user->rights->societe->client->voir) && !$socid) {
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON s.rowid = sc.fk_soc";
	}
	$sql .= " WHERE p.entity IN (".getEntity('invoice').")";
	if (empty($user->rights->societe->client->voir) && !$socid) {
		$sql .= " AND sc.fk_user = ".((int) $user->id);
	}
	if ($socid > 0) {
		$sql .= " AND f.fk_soc = ".((int) $socid);
	}
	if ($userid) {
		if ($userid == -1) {
			$sql .= " AND f.fk_user_author IS NULL";
		} else {
			$sql .= " AND f.fk_user_author = ".((int) $userid);
		}
	}

	// Search criteria
	if ($search_ref) {
		$sql .= natural_search('p.ref', $search_ref);
	}
	if ($search_date_start) {
		$sql .= " AND p.datep >= '" . $db->idate($search_date_start) . "'";
	}
	if ($search_date_end) {
		$sql .= " AND p.datep <= '" . $db->idate($search_date_end) . "'";
	}
	if ($search_account > 0) {
		$sql .= " AND b.fk_account=".((int) $search_account);
	}
	if ($search_paymenttype != '') {
		$sql .= " AND c.code='".$db->escape($search_paymenttype)."'";
	}
	if ($search_payment_num != '') {
		$sql .= natural_search('p.num_paiement', $search_payment_num);
	}
	if ($search_amount) {
		$sql .= natural_search('p.amount', $search_amount, 1);
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
}
$sql .= $db->order($sortfield, $sortorder);

$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST)) {
	$result = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($result);

	// if total resultset is smaller then paging size (filtering), goto and load page 0
	if (($page * $limit) > $nbtotalofrecords) {
		$page = 0;
		$offset = 0;
	}
}

$sql .= $db->plimit($limit + 1, $offset);

$resql = $db->query($sql);
if (!$resql) {
	dol_print_error($db);
	llxFooter();
	$db->close();
	exit;
}

$num = $db->num_rows($resql);

$param = '';
if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
	$param .= '&contextpage='.urlencode($contextpage);
}
if ($limit > 0 && $limit != $conf->liste_limit) {
	$param .= '&limit='.urlencode($limit);
}

if (GETPOST("orphelins")) {
	$param .= '&orphelins=1';
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
if ($search_amount != '') {
	$param .= '&search_amount='.urlencode($search_amount);
}
if ($search_paymenttype) {
	$param .= '&search_paymenttype='.urlencode($search_paymenttype);
}
if ($search_account) {
	$param .= '&search_account='.urlencode($search_account);
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

print_barre_liste($langs->trans("ReceivedCustomersPayments"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'bill', 0, '', '', $limit, 0, 0, 1);

if ($search_all) {
	foreach ($fieldstosearchall as $key => $val) {
		$fieldstosearchall[$key] = $langs->trans($val);
	}
	print '<div class="divsearchfieldfilter">'.$langs->trans("FilterOnInto", $search_all).join(', ', $fieldstosearchall).'</div>';
}

$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage); // This also change content of $arrayfields
$massactionbutton = '';
if ($massactionbutton) {
	$selectedfields .= $form->showCheckAddButtons('checkforselect', 1);
}

$moreforfilter = '';
print '<div class="div-table-responsive">';
print '<table class="tagtable liste'.($moreforfilter ? " listwithfilterbefore" : '').'">';

print '<tr class="liste_titre_filter">';

// Filters: Lines (placeholder)
print '<tr class="liste_titre_filter">';
if (!empty($conf->global->MAIN_VIEW_LINE_NUMBER_IN_LIST)) {
	print '<td class="liste_titre">';
	print '</td>';
}

// Filter: Ref
if (!empty($arrayfields['p.ref']['checked'])) {
	print '<td class="liste_titre left">';
	print '<input class="flat" type="text" size="4" name="search_ref" value="'.dol_escape_htmltag($search_ref).'">';
	print '</td>';
}

// Filter: Date
if (!empty($arrayfields['p.datep']['checked'])) {
	print '<td class="liste_titre center">';
	print '<div class="nowrap">';
	print $form->selectDate($search_date_start ? $search_date_start : -1, 'search_date_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
	print '</div>';
	print '<div class="nowrap">';
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
	$form->select_types_paiements($search_paymenttype, 'search_paymenttype', '', 2, 1, 1);
	print '</td>';
}

// Filter: Bank transaction number
if (!empty($arrayfields['transaction']['checked'])) {
	print '<td class="liste_titre">';
	print '<input class="flat" type="text" size="4" name="search_payment_num" value="'.dol_escape_htmltag($search_payment_num).'">';
	print '</td>';
}

// Filter: Cheque number (fund transfer)
if (!empty($arrayfields['p.num_paiement']['checked'])) {
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
if (!empty($arrayfields['p.amount']['checked'])) {
	print '<td class="liste_titre right">';
	print '<input class="flat" type="text" size="4" name="search_amount" value="'.dol_escape_htmltag($search_amount).'">';
	print '</td>';
}

// Filter: Status (only placeholder)
if (!empty($arrayfields['p.statut']['checked'])) {
	print '<td class="liste_titre right">';
	print '</td>';
}

// Fields from hook
$parameters = array('arrayfields'=>$arrayfields);
$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

print '<td class="liste_titre maxwidthsearch">';
print $form->showFilterAndCheckAddButtons(0);
print '</td>';

print "</tr>";

print '<tr class="liste_titre">';
if (!empty($conf->global->MAIN_VIEW_LINE_NUMBER_IN_LIST)) {
	print_liste_field_titre('#', $_SERVER['PHP_SELF'], '', '', $param, '', $sortfield, $sortorder);
}
if (!empty($arrayfields['p.ref']['checked'])) {
	print_liste_field_titre($arrayfields['p.ref']['label'], $_SERVER["PHP_SELF"], "p.ref", '', $param, '', $sortfield, $sortorder);
}
if (!empty($arrayfields['p.datep']['checked'])) {
	print_liste_field_titre($arrayfields['p.datep']['label'], $_SERVER["PHP_SELF"], "p.datep", '', $param, '', $sortfield, $sortorder, 'center ');
}
if (!empty($arrayfields['s.nom']['checked'])) {
	print_liste_field_titre($arrayfields['s.nom']['label'], $_SERVER["PHP_SELF"], "s.nom", '', $param, '', $sortfield, $sortorder);
}
if (!empty($arrayfields['c.libelle']['checked'])) {
	print_liste_field_titre($arrayfields['c.libelle']['label'], $_SERVER["PHP_SELF"], "c.libelle", '', $param, '', $sortfield, $sortorder);
}
if (!empty($arrayfields['p.num_paiement']['checked'])) {
	print_liste_field_titre($arrayfields['p.num_paiement']['label'], $_SERVER["PHP_SELF"], "p.num_paiement", '', $param, '', $sortfield, $sortorder, '', $arrayfields['p.num_paiement']['tooltip']);
}
if (!empty($arrayfields['transaction']['checked'])) {
	print_liste_field_titre($arrayfields['transaction']['label'], $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder);
}
if (!empty($arrayfields['ba.label']['checked'])) {
	print_liste_field_titre($arrayfields['ba.label']['label'], $_SERVER["PHP_SELF"], "ba.label", '', $param, '', $sortfield, $sortorder);
}
if (!empty($arrayfields['p.amount']['checked'])) {
	print_liste_field_titre($arrayfields['p.amount']['label'], $_SERVER["PHP_SELF"], "p.amount", '', $param, 'class="right"', $sortfield, $sortorder);
}
if (!empty($arrayfields['p.statut']['checked'])) {
	print_liste_field_titre($arrayfields['p.statut']['label'], $_SERVER["PHP_SELF"], "p.statut", '', $param, 'class="right"', $sortfield, $sortorder);
}

// Hook fields
$parameters = array('arrayfields'=>$arrayfields, 'param'=>$param, 'sortfield'=>$sortfield, 'sortorder'=>$sortorder);
$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], '', '', '', 'align="center"', $sortfield, $sortorder, 'maxwidthsearch ');
print "</tr>";

$checkedCount = 0;
foreach ($arrayfields as $column) {
	if ($column['checked']) {
		$checkedCount++;
	}
}

$i = 0;
$totalarray = array();
while ($i < min($num, $limit)) {
	$objp = $db->fetch_object($resql);

	$object->id = $objp->rowid;
	$object->ref = ($objp->ref ? $objp->ref : $objp->rowid);

	$companystatic->id = $objp->socid;
	$companystatic->name = $objp->name;
	$companystatic->email = $objp->email;

	print '<tr class="oddeven">';

	// No
	if (!empty($conf->global->MAIN_VIEW_LINE_NUMBER_IN_LIST)) {
		print '<td>'.(($offset * $limit) + $i).'</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	// Ref
	if (!empty($arrayfields['p.ref']['checked'])) {
		print '<td>'.$object->getNomUrl(1).'</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	// Date
	if (!empty($arrayfields['p.datep']['checked'])) {
		$dateformatforpayment = 'dayhour';
		print '<td class="center">'.dol_print_date($db->jdate($objp->datep), $dateformatforpayment, 'tzuser').'</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	// Thirdparty
	if (!empty($arrayfields['s.nom']['checked'])) {
		print '<td>';
		if ($objp->socid > 0) {
			print $companystatic->getNomUrl(1, '', 24);
		}
		print '</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	// Payment type
	if (!empty($arrayfields['c.libelle']['checked'])) {
		print '<td>'.$langs->trans("PaymentTypeShort".$objp->paiement_code).'</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	// Filter: Cheque number (fund transfer)
	if (!empty($arrayfields['p.num_paiement']['checked'])) {
		print '<td>'.$objp->num_paiement.'</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	// Bank transaction
	if (!empty($arrayfields['transaction']['checked'])) {
		print '<td>';
		if ($objp->fk_bank > 0) {
			$bankline->fetch($objp->fk_bank);
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
		if ($objp->bid > 0) {
			$accountstatic->id = $objp->bid;
			$accountstatic->ref = $objp->bref;
			$accountstatic->label = $objp->blabel;
			$accountstatic->number = $objp->number;
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
		print '<td class="right"><span class="amount">'.price($objp->amount).'</span></td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
		$totalarray['pos'][$checkedCount] = 'amount';
		$totalarray['val']['amount'] += $objp->amount;
	}

	// Status
	if (!empty($arrayfields['p.statut']['checked'])) {
		print '<td class="right">';
		if ($objp->statut == 0) {
			print '<a href="card.php?id='.$objp->rowid.'&amp;action=valide">';
		}
		print $object->LibStatut($objp->statut, 5);
		if ($objp->statut == 0) {
			print '</a>';
		}
		print '</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	// Buttons
	print '<td></td>';
	if (!$i) {
		$totalarray['nbfield']++;
	}

	print '</tr>';

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
	print '<tr><td colspan="'.$colspan.'" class="opacitymedium">'.$langs->trans("NoRecordFound").'</td></tr>';
}

print "</table>";
print "</div>";
print "</form>";

// End of page
llxFooter();
$db->close();
