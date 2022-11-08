<?php
/* Copyright (C) 2001-2003	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2020	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2011-2019	Alexandre Spangaro		<aspangaro@open-dsi.fr>
 * Copyright (C) 2020		Tobias Sekan			<tobias.sekan@startmail.com>
 * Copyright (C) 2021       Gauthier VERDOL         <gauthier.verdol@atm-consulting.fr>
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
 *	\file		htdocs/compta/tva/list.php
 *	\ingroup	tax
 *	\brief		List of VAT payments
 */

require '../../main.inc.php';

require_once DOL_DOCUMENT_ROOT.'/compta/tva/class/tva.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingjournal.class.php';

// Load translation files required by the page
$langs->loadLangs(array('compta', 'bills'));

$action						= GETPOST('action', 'alpha');
$massaction					= GETPOST('massaction', 'alpha');
$confirm					= GETPOST('confirm', 'alpha');
$optioncss = GETPOST('optioncss', 'alpha');
$contextpage				= GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'salestaxeslist';

$search_ref					= GETPOST('search_ref', 'alpha');
$search_label = GETPOST('search_label', 'alpha');
$search_dateend_start = dol_mktime(0, 0, 0, GETPOST('search_dateend_startmonth', 'int'), GETPOST('search_dateend_startday', 'int'), GETPOST('search_dateend_startyear', 'int'));
$search_dateend_end = dol_mktime(23, 59, 59, GETPOST('search_dateend_endmonth', 'int'), GETPOST('search_dateend_endday', 'int'), GETPOST('search_dateend_endyear', 'int'));
$search_datepayment_start = dol_mktime(0, 0, 0, GETPOST('search_datepayment_startmonth', 'int'), GETPOST('search_datepayment_startday', 'int'), GETPOST('search_datepayment_startyear', 'int'));
$search_datepayment_end = dol_mktime(23, 59, 59, GETPOST('search_datepayment_endmonth', 'int'), GETPOST('search_datepayment_endday', 'int'), GETPOST('search_datepayment_endyear', 'int'));
$search_type = GETPOST('search_type', 'int');
$search_account				= GETPOST('search_account', 'int');
$search_amount 				= GETPOST('search_amount', 'alpha');
$search_status = GETPOST('search_status', 'int');

$limit = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield					= GETPOST('sortfield', 'aZ09comma');
$sortorder					= GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST('page', 'int');

if (empty($page) || $page == -1) {
	$page = 0; // If $page is not defined, or '' or -1
}
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

if (!$sortfield) {
	$sortfield = 't.datev';
}
if (!$sortorder) {
	$sortorder = 'DESC';
}

$arrayfields = array(
	't.rowid'			=>array('checked'=>1, 'position'=>10, 'label'=>"Ref",),
	't.label'			=>array('checked'=>1, 'position'=>20, 'label'=>"Label"),
	't.datev'			=>array('checked'=>1, 'position'=>30, 'label'=>"PeriodEndDate"),
	't.fk_typepayment'	=>array('checked'=>1, 'position'=>50, 'label'=>"DefaultPaymentMode"),
	't.amount'			=>array('checked'=>1, 'position'=>90, 'label'=>"Amount"),
	't.status'			=>array('checked'=>1, 'position'=>90, 'label'=>"Status"),
);

if (isModEnabled('banque')) {
	$arrayfields['t.fk_account'] = array('checked'=>1, 'position'=>60, 'label'=>"DefaultBankAccount");
}

$arrayfields = dol_sort_array($arrayfields, 'position');

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('salestaxeslist'));
$object = new Tva($db);

// Security check
$socid = GETPOST('socid', 'int');
if ($user->socid) {
	$socid = $user->socid;
}
$result = restrictedArea($user, 'tax', '', 'tva', 'charges');


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

	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // Both test are required to be compatible with all browsers
		$search_ref = '';
		$search_label = '';
		$search_dateend_start = '';
		$search_dateend_end = '';
		$search_datepayment_start = '';
		$search_datepayment_end = '';
		$search_type = '';
		$search_account = '';
		$search_amount = '';
		$search_status = '';
	}
}


/*
 * View
 */

$form = new Form($db);
$formother = new FormOther($db);
$formfile = new FormFile($db);
$tva_static = new Tva($db);
$bankstatic = new Account($db);
$accountingjournal = new AccountingJournal($db);
$bankline = new AccountLine($db);

llxHeader('', $langs->trans("VATDeclarations"));

$sql = 'SELECT t.rowid, t.amount, t.label, t.datev, t.datep, t.paye, t.fk_typepayment as type, t.fk_account,';
$sql.= ' ba.label as blabel, ba.ref as bref, ba.number as bnumber, ba.account_number, ba.iban_prefix as iban, ba.bic, ba.currency_code, ba.clos,';
$sql.= ' t.num_payment, pst.code as payment_code,';
$sql .= ' SUM(ptva.amount) as alreadypayed';
$sql .= ' FROM '.MAIN_DB_PREFIX.'tva as t';
$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_paiement as pst ON (t.fk_typepayment = pst.id)';
$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'bank_account as ba ON (t.fk_account = ba.rowid)';
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."payment_vat as ptva ON (ptva.fk_tva = t.rowid)";
$sql .= ' WHERE t.entity IN ('.getEntity($object->element).')';

if (!empty($search_ref)) {
	$sql .= natural_search('t.rowid', $search_ref);
}
if (!empty($search_label)) {
	$sql .= natural_search('t.label', $search_label);
}
if (!empty($search_dateend_start)) {
	$sql .= " AND t.datev >= '".$db->idate($search_dateend_start)."'";
}
if (!empty($search_dateend_end)) {
	$sql .= " AND t.datev <= '".$db->idate($search_dateend_end)."'";
}
if (!empty($search_datepayment_start)) {
	$sql .= " AND t.datep >= '".$db->idate($search_datepayment_start)."'";
}
if (!empty($search_datepayment_end)) {
	$sql .= " AND t.datep <= '".$db->idate($search_datepayment_end)."'";
}
if (!empty($search_type) && $search_type > 0) {
	$sql .= ' AND t.fk_typepayment = '.((int) $search_type);
}
if (!empty($search_account) && $search_account > 0) {
	$sql .= ' AND t.fk_account = '.((int) $search_account);
}
if (!empty($search_amount)) {
	$sql .= natural_search('t.amount', price2num(trim($search_amount)), 1);
}
if ($search_status != '' && $search_status >= 0) {
	$sql .= " AND t.paye = ".((int) $search_status);
}

$sql .= " GROUP BY t.rowid, t.amount, t.label, t.datev, t.datep, t.paye, t.fk_typepayment, t.fk_account, ba.label, ba.ref, ba.number, ba.account_number, ba.iban_prefix, ba.bic, ba.currency_code, ba.clos, t.num_payment, pst.code";
$sql .= $db->order($sortfield, $sortorder);

$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST)) {
	$resql = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($resql);

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
if (!empty($contextpage) && $contextpage != $_SERVER['PHP_SELF']) {
	$param .= '&contextpage='.$contextpage;
}
if ($limit > 0 && $limit != $conf->liste_limit) {
	$param .= '&limit='.$limit;
}
if ($optioncss != '') {
	$param .= '&optioncss='.urlencode($optioncss);
}

if (!empty($search_ref)) {
	$param .= '&search_ref="'.$search_ref.'"';
}
if (!empty($search_label)) {
	$param .= '&search_label="'.$search_label.'"';
}
if (!empty($search_dateend_start)) {
	$param .= '&search_dateend_startyear='.GETPOST('search_dateend_startyear', 'int');
}
if (!empty($search_dateend_start)) {
	$param .= '&search_dateend_startmonth='.GETPOST('search_dateend_startmonth', 'int');
}
if (!empty($search_dateend_start)) {
	$param .= '&search_dateend_startday='.GETPOST('search_dateend_startday', 'int');
}
if (!empty($search_dateend_end)) {
	$param .= '&search_dateend_endyear='.GETPOST('search_dateend_endyear', 'int');
}
if (!empty($search_dateend_end)) {
	$param .= '&search_dateend_endmonth='.GETPOST('search_dateend_endmonth', 'int');
}
if (!empty($search_dateend_end)) {
	$param .= '&search_dateend_endday='.GETPOST('search_dateend_endday', 'int');
}
if (!empty($search_datepayment_start)) {
	$param .= '&search_datepayment_startyear='.GETPOST('search_datepayment_startyear', 'int');
}
if (!empty($search_datepayment_start)) {
	$param .= '&search_datepayment_startmonth='.GETPOST('search_datepayment_startmonth', 'int');
}
if (!empty($search_datepayment_start)) {
	$param .= '&search_datepayment_startday='.GETPOST('search_datepayment_startday', 'int');
}
if (!empty($search_datepayment_end)) {
	$param .= '&search_datepayment_endyear='.GETPOST('search_datepayment_endyear', 'int');
}
if (!empty($search_datepayment_end)) {
	$param .= '&search_datepayment_endmonth='.GETPOST('search_datepayment_endmonth', 'int');
}
if (!empty($search_datepayment_end)) {
	$param .= '&search_datepayment_endday='.GETPOST('search_datepayment_endday', 'int');
}
if (!empty($search_type) && $search_type > 0) {
	$param .= '&search_type='.$search_type;
}
if (!empty($search_account) && $search_account > 0) {
	$param .= '&search_account='.$search_account;
}
if (!empty($search_amount)) {
	$param .= '&search_amount="'.$search_amount.'"';
}
if ($search_status != '' && $search_status != '-1') {
	$param .= '&search_status='.urlencode($search_status);
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

$url = DOL_URL_ROOT.'/compta/tva/card.php?action=create';
if (!empty($socid)) {
	$url .= '&socid='.$socid;
}
$newcardbutton = dolGetButtonTitle($langs->trans('NewVATPayment', ($ltt + 1)), '', 'fa fa-plus-circle', $url, '', $user->rights->tax->charges->creer);
print_barre_liste($langs->trans("VATDeclarations"), $page, $_SERVER['PHP_SELF'], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'title_accountancy', 0, $newcardbutton, '', $limit, 0, 0, 1);

$varpage = empty($contextpage) ? $_SERVER['PHP_SELF'] : $contextpage;
$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage); // This also change content of $arrayfields
if ($massactionbutton) {
	$selectedfields .= $form->showCheckAddButtons('checkforselect', 1);
}

print '<div class="div-table-responsive">';
print '<table class="tagtable liste'.($moreforfilter ? " listwithfilterbefore" : '').'">';

print '<tr class="liste_titre_filter">';

// Filters: Lines (placeholder)
if (!empty($conf->global->MAIN_VIEW_LINE_NUMBER_IN_LIST)) {
	print '<td class="liste_titre">';
	print '</td>';
}

// Filter: Ref
if (!empty($arrayfields['t.rowid']['checked'])) {
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" size="4" name="search_ref" value="'.dol_escape_htmltag($search_ref).'">';
	print '</td>';
}

// Filter: Label
if (!empty($arrayfields['t.label']['checked'])) {
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" size="10" name="search_label" value="'.dol_escape_htmltag($search_label).'">';
	print '</td>';
}

// Filter: Date end period
if (!empty($arrayfields['t.datev']['checked'])) {
	print '<td class="liste_titre center">';
	print '<div class="nowrap">';
	print $form->selectDate($search_dateend_start ? $search_dateend_start : -1, 'search_dateend_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("From"));
	print '</div>';
	print '<div class="nowrap">';
	print $form->selectDate($search_dateend_end ? $search_dateend_end : -1, 'search_dateend_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("to"));
	print '</div>';
	print '</td>';
}

// Filter: Date payment
/*if (!empty($arrayfields['t.datep']['checked'])) {
	print '<td class="liste_titre center">';
	print '<div class="nowrap">';
	print $form->selectDate($search_datepayment_start ? $search_datepayment_start : -1, 'search_datepayment_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("From"));
	print '</div>';
	print '<div class="nowrap">';
	print $form->selectDate($search_datepayment_end ? $search_datepayment_end : -1, 'search_datepayment_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("to"));
	print '</div>';
	print '</td>';
}*/

// Filter: Type
if (!empty($arrayfields['t.fk_typepayment']['checked'])) {
	print '<td class="liste_titre left">';
	$form->select_types_paiements($search_type, 'search_type', '', 0, 1, 1, 16);
	print '</td>';
}

// Filter: Bank Account
if (!empty($arrayfields['t.fk_account']['checked'])) {
	print '<td class="liste_titre left">';
	$form->select_comptes($search_account, 'search_account', 0, '', 1);
	print '</td>';
}

// Filter: Amount
if (!empty($arrayfields['t.amount']['checked'])) {
	print '<td class="liste_titre right">';
	print '<input name="search_amount" class="flat" type="text" size="8" value="'.$search_amount.'">';
	print '</td>';
}

// Status
if (!empty($arrayfields['t.status']['checked'])) {
	print '<td class="liste_titre maxwidthonsmartphone right">';
	$liststatus = array('0' => $langs->trans("Unpaid"), '1' => $langs->trans("Paid"));
	print $form->selectarray('search_status', $liststatus, $search_status, 1);
	print '</td>';
}

// Fields from hook
$parameters = array('arrayfields'=>$arrayfields);
$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

// Filter: Buttons
print '<td class="liste_titre maxwidthsearch">';
print $form->showFilterAndCheckAddButtons(0);
print '</td>';

print '</tr>';

print '<tr class="liste_titre">';
if (!empty($conf->global->MAIN_VIEW_LINE_NUMBER_IN_LIST)) {
	print_liste_field_titre('#', $_SERVER['PHP_SELF'], '', '', $param, '', $sortfield, $sortorder);
}
if (!empty($arrayfields['t.rowid']['checked'])) {
	print_liste_field_titre($arrayfields['t.rowid']['label'], $_SERVER['PHP_SELF'], 't.rowid', '', $param, '', $sortfield, $sortorder);
}
if (!empty($arrayfields['t.label']['checked'])) {
	print_liste_field_titre($arrayfields['t.label']['label'], $_SERVER['PHP_SELF'], 't.label', '', $param, 'align="left"', $sortfield, $sortorder);
}
if (!empty($arrayfields['t.datev']['checked'])) {
	print_liste_field_titre($arrayfields['t.datev']['label'], $_SERVER['PHP_SELF'], 't.datev', '', $param, 'align="center"', $sortfield, $sortorder);
}
if (!empty($arrayfields['t.fk_typepayment']['checked'])) {
	print_liste_field_titre($arrayfields['t.fk_typepayment']['label'], $_SERVER['PHP_SELF'], 't.fk_typepayment', '', $param, '', $sortfield, $sortorder, 'left ');
}
if (!empty($arrayfields['t.fk_account']['checked'])) {
	print_liste_field_titre($arrayfields['t.fk_account']['label'], $_SERVER['PHP_SELF'], 't.fk_account', '', $param, '', $sortfield, $sortorder, 'left ');
}
if (!empty($arrayfields['t.amount']['checked'])) {
	print_liste_field_titre($arrayfields['t.amount']['label'], $_SERVER['PHP_SELF'], 't.amount', '', $param, '', $sortfield, $sortorder, 'right ');
}
if (!empty($arrayfields['t.status']['checked'])) {
	print_liste_field_titre($arrayfields['t.status']['label'], $_SERVER["PHP_SELF"], "t.paye", "", $param, 'class="right"', $sortfield, $sortorder);
}

// Hook fields
$parameters = array('arrayfields'=>$arrayfields, 'param'=>$param, 'sortfield'=>$sortfield, 'sortorder'=>$sortorder);
$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

print_liste_field_titre($selectedfields, $_SERVER['PHP_SELF'], '', '', '', 'align="center"', $sortfield, $sortorder, 'maxwidthsearch ');
print '</tr>';

$i = 0;
$totalarray = array();
while ($i < min($num, $limit)) {
	$obj = $db->fetch_object($resql);

	$tva_static->id = $obj->rowid;
	$tva_static->ref = $obj->rowid;
	$tva_static->label = $obj->label;

	print '<tr class="oddeven">';

	// No
	if (!empty($conf->global->MAIN_VIEW_LINE_NUMBER_IN_LIST)) {
		print '<td>'.(($offset * $limit) + $i).'</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	// Ref
	if (!empty($arrayfields['t.rowid']['checked'])) {
		print '<td>';
		print $tva_static->getNomUrl(1);
		$filename = dol_sanitizeFileName($tva_static->ref);
		$filedir = $conf->tax->dir_output.'/vat/'.dol_sanitizeFileName($tva_static->ref);
		$urlsource = $_SERVER['PHP_SELF'].'?id='.$tva_static->id;
		print $formfile->getDocumentsLink($tva_static->element, $filename, $filedir, '', 'valignmiddle paddingleft2imp');
		print '</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	// Label
	if (!empty($arrayfields['t.label']['checked'])) {
		print '<td>'.dol_trunc($obj->label, 40).'</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	// Date end period
	if (!empty($arrayfields['t.datev']['checked'])) {
		print '<td class="center">'.dol_print_date($db->jdate($obj->datev), 'day').'</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	// Date payment
	/*if (!empty($arrayfields['t.datep']['checked'])) {
		print '<td class="center">'.dol_print_date($db->jdate($obj->datep), 'day').'</td>';
		if (!$i) $totalarray['nbfield']++;
	}*/

	// Type
	if (!empty($arrayfields['t.fk_typepayment']['checked'])) {
		print '<td>';
		if (!empty($obj->payment_code)) print $langs->trans("PaymentTypeShort".$obj->payment_code);
		print '</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	// Account
	if (!empty($arrayfields['t.fk_account']['checked'])) {
		print '<td>';
		if ($obj->fk_account > 0) {
			$bankstatic->id = $obj->fk_account;
			$bankstatic->ref = $obj->bref;
			$bankstatic->number = $obj->bnumber;
			$bankstatic->iban = $obj->iban;
			$bankstatic->bic = $obj->bic;
			$bankstatic->currency_code = $langs->trans("Currency".$obj->currency_code);
			$bankstatic->account_number = $obj->account_number;
			$bankstatic->clos = $obj->clos;

			//$accountingjournal->fetch($obj->fk_accountancy_journal);
			//$bankstatic->accountancy_journal = $accountingjournal->getNomUrl(0, 1, 1, '', 1);

			$bankstatic->label = $obj->blabel;
			print $bankstatic->getNomUrl(1);
		}
		print '</td>';
		if (!$i) $totalarray['nbfield']++;
	}

	// Amount
	if (!empty($arrayfields['t.amount']['checked'])) {
		$total = $total + $obj->amount;
		print '<td class="nowrap right"><span class="amount">' . price($obj->amount) . '</span></td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
		$totalarray['pos'][$totalarray['nbfield']] = 'amount';
		$totalarray['val']['amount'] += $obj->amount;
	}

	if (!empty($arrayfields['t.status']['checked'])) {
		print '<td class="nowrap right">' . $tva_static->LibStatut($obj->paye, 5, $obj->alreadypayed) . '</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
		if (!empty($arrayfields['t.amount']['checked'])) {
			$totalarray['pos'][$totalarray['nbfield']] = '';
		}
	}

	// Buttons
	print '<td></td>';

	print '</tr>';

	$i++;
}

// Add a buttons placeholder for the total line
$totalarray['nbfield']++;

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

$parameters = array('arrayfields'=>$arrayfields, 'sql'=>$sql);
$reshook = $hookmanager->executeHooks('printFieldListFooter', $parameters, $object); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

print '</table>'."\n";
print '</div>'."\n";

print '</form>'."\n";

// End of page
llxFooter();
$db->close();
