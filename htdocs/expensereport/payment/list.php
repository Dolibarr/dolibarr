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
 * Copyright (C) 2017-2021  Alexandre Spangaro		<aspangaro@open-dsi.fr>
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
 *	\file		htdocs/expensereport/payment/list.php
*	\ingroup	expensereport
 *	\brief		Payment list for expense reports
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/expensereport/class/paymentexpensereport.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingjournal.class.php';

// Load translation files required by the page
$langs->loadLangs(array('expensereports', 'bills', 'banks', 'compta'));

$action = GETPOST('action', 'alpha');
$massaction = GETPOST('massaction', 'alpha');
$optioncss = GETPOST('optioncss', 'alpha');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'vendorpaymentlist';
$mode = GETPOST('mode', 'alpha');

$socid = GETPOST('socid', 'int');

// Security check
if ($user->socid) {
	$socid = $user->socid;
}

$search_ref				= GETPOST('search_ref', 'alpha');
$search_date_startday	= GETPOST('search_date_startday', 'int');
$search_date_startmonth	= GETPOST('search_date_startmonth', 'int');
$search_date_startyear	= GETPOST('search_date_startyear', 'int');
$search_date_endday		= GETPOST('search_date_endday', 'int');
$search_date_endmonth	= GETPOST('search_date_endmonth', 'int');
$search_date_endyear	= GETPOST('search_date_endyear', 'int');
$search_date_start		= dol_mktime(0, 0, 0, $search_date_startmonth, $search_date_startday, $search_date_startyear);	// Use tzserver
$search_date_end		= dol_mktime(23, 59, 59, $search_date_endmonth, $search_date_endday, $search_date_endyear);
$search_user			= GETPOST('search_user', 'alpha');
$search_payment_type	= GETPOST('search_payment_type');
$search_cheque_num		= GETPOST('search_cheque_num', 'alpha');
$search_bank_account	= GETPOST('search_bank_account', 'int');
$search_amount			= GETPOST('search_amount', 'alpha'); // alpha because we must be able to search on '< x'

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
	$sortfield = "pndf.datep";
}

$search_all = trim(GETPOSTISSET("search_all") ? GETPOST("search_all", 'alpha') : GETPOST('sall'));

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
	'pndf.rowid'=>"RefPayment",
	'u.login'=>"User",
	'pndf.num_payment'=>"Numero",
	'pndf.amount'=>"Amount",
);

$arrayfields = array(
	'pndf.rowid'				=>array('label'=>"RefPayment", 'checked'=>1, 'position'=>10),
	'pndf.datep'			=>array('label'=>"Date", 'checked'=>1, 'position'=>20),
	'u.login'				=>array('label'=>"User", 'checked'=>1, 'position'=>30),
	'c.libelle'			=>array('label'=>"Type", 'checked'=>1, 'position'=>40),
	'pndf.num_payment'	=>array('label'=>"Numero", 'checked'=>1, 'position'=>50, 'tooltip'=>"ChequeOrTransferNumber"),
	'ba.label'			=>array('label'=>"Account", 'checked'=>1, 'position'=>60, 'enable'=>(isModEnabled("banque"))),
	'pndf.amount'			=>array('label'=>"Amount", 'checked'=>1, 'position'=>70),
);
$arrayfields = dol_sort_array($arrayfields, 'position');

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('paymentexpensereportlist'));
$object = new PaymentExpenseReport($db);

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
if (!$user->hasRight('expensereport', 'lire')) {
	accessforbidden();
}


/*
 * Actions
 */

$childids = $user->getAllChildIds(1);

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
		$search_user = '';
		$search_payment_type = '';
		$search_cheque_num = '';
		$search_bank_account = '';
		$search_amount = '';
	}
}

/*
 * View
 */

llxHeader('', $langs->trans('ListPayment'));

$form = new Form($db);
$formother = new FormOther($db);
$accountstatic = new Account($db);
$userstatic = new User($db);
$paymentexpensereportstatic = new PaymentExpenseReport($db);

$sql = 'SELECT pndf.rowid, pndf.rowid as ref, pndf.datep, pndf.amount as pamount, pndf.num_payment';
$sql .= ', u.rowid as userid, u.login, u.lastname, u.firstname';
$sql .= ', c.code as paiement_type, c.libelle as paiement_libelle';
$sql .= ', ba.rowid as bid, ba.ref as bref, ba.label as blabel, ba.number, ba.account_number as account_number, ba.iban_prefix, ba.bic, ba.currency_code, ba.fk_accountancy_journal as accountancy_journal';
$sql .= ', SUM(pndf.amount)';
$sql .= ' FROM '.MAIN_DB_PREFIX.'payment_expensereport AS pndf';
$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'expensereport AS ndf ON ndf.rowid=pndf.fk_expensereport';
$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_paiement AS c ON pndf.fk_typepayment = c.id';
$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'user AS u ON u.rowid = ndf.fk_user_author';
$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'bank as b ON pndf.fk_bank = b.rowid';
$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'bank_account as ba ON b.fk_account = ba.rowid';
$sql .= ' WHERE ndf.entity IN ('.getEntity("expensereport").')';

// RESTRICT RIGHTS
if (!$user->hasRight('expensereport', 'readall') && !$user->hasRight('expensereport', 'lire_tous')
	&& (!getDolGlobalString('MAIN_USE_ADVANCED_PERMS') || !$user->hasRight('expensereport', 'writeall_advance'))) {
	$sql .= " AND ndf.fk_user_author IN (".$db->sanitize(join(',', $childids)).")\n";
}

if ($search_ref) {
	$sql .= natural_search('pndf.rowid', $search_ref);
}
if ($search_date_start) {
	$sql .= " AND pndf.datep >= '" . $db->idate($search_date_start) . "'";
}
if ($search_date_end) {
	$sql .=" AND pndf.datep <= '" . $db->idate($search_date_end) . "'";
}

if ($search_user) {
	$sql .= natural_search(array('u.login', 'u.lastname', 'u.firstname'), $search_user);
}
if ($search_payment_type != '') {
	$sql .= " AND c.code='".$db->escape($search_payment_type)."'";
}
if ($search_cheque_num != '') {
	$sql .= natural_search('pndf.num_payment', $search_cheque_num);
}
if ($search_amount) {
	$sql .= natural_search('pndf.amount', $search_amount, 1);
}
if ($search_bank_account > 0) {
	$sql .= ' AND b.fk_account = '.((int) $search_bank_account);
}
if ($search_all) {
	$sql .= natural_search(array_keys($fieldstosearchall), $search_all);
}

// Add where from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';

$sql .= ' GROUP BY pndf.rowid, pndf.datep, pndf.amount, pndf.num_payment, u.rowid, u.login, u.lastname, u.firstname, c.code, c.libelle,';
$sql .= ' ba.rowid, ba.ref, ba.label, ba.number, ba.account_number, ba.iban_prefix, ba.bic, ba.currency_code, ba.fk_accountancy_journal';

$sql .= $db->order($sortfield, $sortorder);

$nbtotalofrecords = '';
if (!getDolGlobalInt('MAIN_DISABLE_FULL_SCANLIST')) {
	$result = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($result);
	if (($page * $limit) > $nbtotalofrecords) {		// if total resultset is smaller then paging size (filtering), goto and load page 0
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
$i = 0;

$param = '';
if (!empty($mode)) {
	$param .= '&mode='.urlencode($mode);
}
if (!empty($contextpage) && $contextpage != $_SERVER['PHP_SELF']) {
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
if ($search_user) {
	$param .= '&search_user='.urlencode($search_user);
}
if ($search_payment_type) {
	$param .= '&search_payment_type='.urlencode($search_payment_type);
}
if ($search_cheque_num) {
	$param .= '&search_cheque_num='.urlencode($search_cheque_num);
}
if ($search_amount) {
	$param .= '&search_amount='.urlencode($search_amount);
}

if ($search_bank_account) {
	$param .= '&search_bank_account='.urlencode($search_bank_account);
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
print '<input type="hidden" name="mode" value="'.$mode.'">';

$newcardbutton = '';
$newcardbutton .= dolGetButtonTitle($langs->trans('ViewList'), '', 'fa fa-bars imgforviewmode', $_SERVER["PHP_SELF"].'?mode=common'.preg_replace('/(&|\?)*mode=[^&]+/', '', $param), '', ((empty($mode) || $mode == 'common') ? 2 : 1), array('morecss'=>'reposition'));
$newcardbutton .= dolGetButtonTitle($langs->trans('ViewKanban'), '', 'fa fa-th-list imgforviewmode', $_SERVER["PHP_SELF"].'?mode=kanban'.preg_replace('/(&|\?)*mode=[^&]+/', '', $param), '', ($mode == 'kanban' ? 2 : 1), array('morecss'=>'reposition'));

print_barre_liste($langs->trans('ExpenseReportPayments'), $page, $_SERVER['PHP_SELF'], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'expensereport', 0, $newcardbutton, '', $limit, 0, 0, 1);

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

$arrayofmassactions = array();

$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
$selectedfields = ($mode != 'kanban' ? $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage, getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN', '')) : ''); // This also change content of $arrayfields
$selectedfields .= (count($arrayofmassactions) ? $form->showCheckAddButtons('checkforselect', 1) : '');

print '<div class="div-table-responsive">';
print '<table class="tagtable nobottomiftotal liste'.($moreforfilter ? " listwithfilterbefore" : '').'">';

print '<tr class="liste_titre_filter">';

// Action column
if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print '<td class="liste_titre maxwidthsearch">';
	print $form->showFilterAndCheckAddButtons(0);
	print '</td>';
}

// Filter: Ref
if (!empty($arrayfields['pndf.rowid']['checked'])) {
	print '<td  class="liste_titre left">';
	print '<input class="flat" type="text" size="4" name="search_ref" value="'.dol_escape_htmltag($search_ref).'">';
	print '</td>';
}

// Filter: Date
if (!empty($arrayfields['pndf.datep']['checked'])) {
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
if (!empty($arrayfields['u.login']['checked'])) {
	print '<td class="liste_titre">';
	print '<input class="flat" type="text" size="6" name="search_user" value="'.dol_escape_htmltag($search_user).'">';
	print '</td>';
}

// Filter: Payment type
if (!empty($arrayfields['c.libelle']['checked'])) {
	print '<td class="liste_titre">';
	$form->select_types_paiements($search_payment_type, 'search_payment_type', '', 2, 1, 1);
	print '</td>';
}

// Filter: Cheque number (fund transfer)
if (!empty($arrayfields['pndf.num_payment']['checked'])) {
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
if (!empty($arrayfields['pndf.amount']['checked'])) {
	print '<td class="liste_titre right">';
	print '<input class="flat" type="text" size="4" name="search_amount" value="'.dol_escape_htmltag($search_amount).'">';
	print '</td>';
}

// Fields from hook
$parameters = array('arrayfields'=>$arrayfields);
$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

// Action column
if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print '<td class="liste_titre maxwidthsearch">';
	print $form->showFilterAndCheckAddButtons(0);
	print '</td>';
}

print '</tr>';

$totalarray = array();
$totalarray['nbfield'] = 0;

print '<tr class="liste_titre">';
// Action column
if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print_liste_field_titre($selectedfields, $_SERVER['PHP_SELF'], '', '', '', 'align="center"', $sortfield, $sortorder, 'maxwidthsearch ');
	$totalarray['nbfield']++;
}
if (getDolGlobalString('MAIN_VIEW_LINE_NUMBER_IN_LIST')) {
	print_liste_field_titre('#', $_SERVER['PHP_SELF'], '', '', $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['pndf.rowid']['checked'])) {
	print_liste_field_titre($arrayfields['pndf.rowid']['label'], $_SERVER["PHP_SELF"], 'pndf.rowid', '', $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['pndf.datep']['checked'])) {
	print_liste_field_titre($arrayfields['pndf.datep']['label'], $_SERVER["PHP_SELF"], 'pndf.datep', '', $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['u.login']['checked'])) {
	print_liste_field_titre($arrayfields['u.login']['label'], $_SERVER["PHP_SELF"], 'u.lastname', '', $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['c.libelle']['checked'])) {
	print_liste_field_titre($arrayfields['c.libelle']['label'], $_SERVER["PHP_SELF"], 'c.libelle', '', $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['pndf.num_payment']['checked'])) {
	print_liste_field_titre($arrayfields['pndf.num_payment']['label'], $_SERVER["PHP_SELF"], "pndf.num_payment", '', $param, '', $sortfield, $sortorder, '', $arrayfields['pndf.num_payment']['tooltip']);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['ba.label']['checked'])) {
	print_liste_field_titre($arrayfields['ba.label']['label'], $_SERVER["PHP_SELF"], 'ba.label', '', $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['pndf.amount']['checked'])) {
	print_liste_field_titre($arrayfields['pndf.amount']['label'], $_SERVER["PHP_SELF"], 'pndf.amount', '', $param, '', $sortfield, $sortorder, 'right ');
	$totalarray['nbfield']++;
}

// Hook fields
$parameters = array('arrayfields'=>$arrayfields, 'param'=>$param, 'sortfield'=>$sortfield, 'sortorder'=>$sortorder, 'totalarray'=>&$totalarray);
$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
// Action column
if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print_liste_field_titre($selectedfields, $_SERVER['PHP_SELF'], '', '', '', 'align="center"', $sortfield, $sortorder, 'maxwidthsearch ');
	$totalarray['nbfield']++;
}
print '</tr>';

$checkedCount = 0;
foreach ($arrayfields as $column) {
	if ($column['checked']) {
		$checkedCount++;
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

	$paymentexpensereportstatic->id = $objp->rowid;
	$paymentexpensereportstatic->ref = $objp->ref;
	$paymentexpensereportstatic->datep = $db->jdate($objp->datep);
	$paymentexpensereportstatic->amount = $objp->pamount;
	$paymentexpensereportstatic->fk_typepayment = $objp->paiement_type;

	if ($objp->bid) {
		$accountstatic->fetch($objp->bid);
		$paymentexpensereportstatic->fk_bank = $accountstatic->getNomUrl(1);
	} else {
		$paymentexpensereportstatic->fk_bank = null;
	}

	$userstatic->id = $objp->userid;
	$userstatic->lastname = $objp->lastname;
	$userstatic->firstname = $objp->firstname;

	if ($mode == 'kanban') {
		if ($i == 0) {
			print '<tr class="trkanban"><td colspan="'.$savnbfield.'">';
			print '<div class="box-flex-container kanban">';
		}
		// Output Kanban
		print $paymentexpensereportstatic->getKanbanView('', array('selected' => in_array($objp->id, $arrayofselected)));
		if ($i == ($imaxinloop - 1)) {
			print '</div>';
			print '</td></tr>';
		}
	} else {
		print '<tr class="oddeven">';

		// Action column
		if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			print '<td></td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// No
		if (getDolGlobalString('MAIN_VIEW_LINE_NUMBER_IN_LIST')) {
			print '<td>'.(($offset * $limit) + $i).'</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Ref
		if (!empty($arrayfields['pndf.rowid']['checked'])) {
			print '<td class="nowrap">'.$paymentexpensereportstatic->getNomUrl(1).'</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Date
		if (!empty($arrayfields['pndf.datep']['checked'])) {
			$dateformatforpayment = 'dayhour';
			print '<td class="nowrap center">'.dol_print_date($db->jdate($objp->datep), $dateformatforpayment).'</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Thirdparty
		if (!empty($arrayfields['u.login']['checked'])) {
			print '<td>';
			if ($userstatic->id > 0) {
				print $userstatic->getNomUrl(1);
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
		if (!empty($arrayfields['pndf.num_payment']['checked'])) {
			print '<td>'.$objp->num_payment.'</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Bank account
		if (!empty($arrayfields['ba.label']['checked'])) {
			print '<td>';
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
			} else {
				print '&nbsp;';
			}
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Amount
		if (!empty($arrayfields['pndf.amount']['checked'])) {
			print '<td class="right"><span class="amount">'.price($objp->pamount).'</span></td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
			$totalarray['pos'][$checkedCount] = 'amount';
			if (empty($totalarray['val']['amount'])) {
				$totalarray['val']['amount'] = $objp->pamount;
			} else {
				$totalarray['val']['amount'] += $objp->pamount;
			}
		}

		// Action column
		if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			print '<td></td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		print '</tr>';
	}
	$i++;
}

// Show total line
include DOL_DOCUMENT_ROOT.'/core/tpl/list_print_total.tpl.php';

print '</table>';
print '</div>';
print '</form>';

// End of page
llxFooter();
$db->close();
