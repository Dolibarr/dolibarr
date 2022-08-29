<?php
/* Copyright (C) 2017-2022	Alexandre Spangaro      <aspangaro@open-dsi.fr>
 * Copyright (C) 2017       Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2018       Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2020       Tobias Sekan            <tobias.sekan@startmail.com>
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
 *  \file       htdocs/compta/bank/various_payment/list.php
 *  \ingroup    bank
 *  \brief      List of various payments
 */

require '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/paymentvarious.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formaccounting.class.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingaccount.class.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingjournal.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';

// Load translation files required by the page
$langs->loadLangs(array("compta", "banks", "bills", "accountancy"));

$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'directdebitcredittransferlist'; // To manage different context of search

// Security check
$socid = GETPOST("socid", "int");
if ($user->socid) {
	$socid = $user->socid;
}

$optioncss = GETPOST('optioncss', 'alpha');

$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
$search_ref = GETPOST('search_ref', 'int');
$search_user = GETPOST('search_user', 'alpha');
$search_label = GETPOST('search_label', 'alpha');
$search_datep_start = dol_mktime(0, 0, 0, GETPOST('search_date_startmonth', 'int'), GETPOST('search_date_startday', 'int'), GETPOST('search_date_startyear', 'int'));
$search_datep_end = dol_mktime(23, 59, 59, GETPOST('search_date_endmonth', 'int'), GETPOST('search_date_endday', 'int'), GETPOST('search_date_endyear', 'int'));
$search_datev_start = dol_mktime(0, 0, 0, GETPOST('search_date_value_startmonth', 'int'), GETPOST('search_date_value_startday', 'int'), GETPOST('search_date_value_startyear', 'int'));
$search_datev_end = dol_mktime(23, 59, 59, GETPOST('search_date_value_endmonth', 'int'), GETPOST('search_date_value_endday', 'int'), GETPOST('search_date_value_endyear', 'int'));
$search_amount_deb = GETPOST('search_amount_deb', 'alpha');
$search_amount_cred = GETPOST('search_amount_cred', 'alpha');
$search_bank_account = GETPOST('search_account', 'int');
$search_bank_entry = GETPOST('search_bank_entry', 'int');
$search_accountancy_account = GETPOST("search_accountancy_account");
if ($search_accountancy_account == - 1) {
	$search_accountancy_account = '';
}
$search_accountancy_subledger = GETPOST("search_accountancy_subledger");
if ($search_accountancy_subledger == - 1) {
	$search_accountancy_subledger = '';
}
if (empty($search_datep_start)) {
	$search_datep_start = GETPOST("search_datep_start", 'int');
}
if (empty($search_datep_end)) {
	$search_datep_end = GETPOST("search_datep_end", 'int');
}
if (empty($search_datev_start)) {
	$search_datev_start = GETPOST("search_datev_start", 'int');
}
if (empty($search_datev_end)) {
	$search_datev_end = GETPOST("search_datev_end", 'int');
}
$search_type_id = GETPOST('search_type_id', 'int');

$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page == -1) {
	$page = 0;
}	 // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortfield) {
	$sortfield = "v.datep,v.rowid";
}
if (!$sortorder) {
	$sortorder = "DESC,DESC";
}

$filtre = GETPOST("filtre", 'alpha');

if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All test are required to be compatible with all browsers
	$search_ref = '';
	$search_label = '';
	$search_datep_start = '';
	$search_datep_end = '';
	$search_datev_start = '';
	$search_datev_end = '';
	$search_amount_deb = '';
	$search_amount_cred = '';
	$search_bank_account = '';
	$search_bank_entry = '';
	$search_accountancy_account = '';
	$search_accountancy_subledger = '';
	$search_type_id = '';
}

$search_all = GETPOSTISSET("search_all") ? trim(GETPOST("search_all", 'alpha')) : trim(GETPOST('sall'));

/*
* TODO: fill array "$fields" in "/compta/bank/class/paymentvarious.class.php" and use
*
*
* $object = new PaymentVarious($db);
*
* $search = array();
* foreach ($object->fields as $key => $val)
* {
*	if (GETPOST('search_'.$key, 'alpha')) $search[$key] = GETPOST('search_'.$key, 'alpha');
* }

* $fieldstosearchall = array();
* foreach ($object->fields as $key => $val)
* {
*	if ($val['searchall']) $fieldstosearchall['t.'.$key] = $val['label'];
* }
*
*/

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
	'v.rowid'=>"Ref",
	'v.label'=>"Label",
	'v.datep'=>"DatePayment",
	'v.datev'=>"DateValue",
	'v.amount'=>$langs->trans("Debit").", ".$langs->trans("Credit"),
);

// Definition of fields for lists
$arrayfields = array(
	'ref'			=>array('label'=>"Ref", 'checked'=>1, 'position'=>100),
	'label'			=>array('label'=>"Label", 'checked'=>1, 'position'=>110),
	'datep'			=>array('label'=>"DatePayment", 'checked'=>1, 'position'=>120),
	'datev'			=>array('label'=>"DateValue", 'checked'=>-1, 'position'=>130),
	'type'			=>array('label'=>"PaymentMode", 'checked'=>1, 'position'=>140),
	'project'		=>array('label'=>"Project", 'checked'=>1, 'position'=>200, "enabled"=>!empty($conf->project->enabled)),
	'bank'			=>array('label'=>"BankAccount", 'checked'=>1, 'position'=>300, "enabled"=>isModEnabled('banque')),
	'entry'			=>array('label'=>"BankTransactionLine", 'checked'=>1, 'position'=>310, "enabled"=>isModEnabled('banque')),
	'account'		=>array('label'=>"AccountAccountingShort", 'checked'=>1, 'position'=>400, "enabled"=>isModEnabled('accounting')),
	'subledger'		=>array('label'=>"SubledgerAccount", 'checked'=>1, 'position'=>410, "enabled"=>isModEnabled('accounting')),
	'debit'			=>array('label'=>"Debit", 'checked'=>1, 'position'=>500),
	'credit'		=>array('label'=>"Credit", 'checked'=>1, 'position'=>510),
);

$arrayfields = dol_sort_array($arrayfields, 'position');

$object = new PaymentVarious($db);

$result = restrictedArea($user, 'banque', '', '', '');


/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	// Selection of new fields
	include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';
}

/*
 * View
 */

$form = new Form($db);
if ($arrayfields['account']['checked'] || $arrayfields['subledger']['checked']) {
	$formaccounting = new FormAccounting($db);
}
if ($arrayfields['bank']['checked'] && isModEnabled('accounting')) {
	$accountingjournal = new AccountingJournal($db);
}
if ($arrayfields['ref']['checked']) {
	$variousstatic		= new PaymentVarious($db);
}
if ($arrayfields['bank']['checked']) {
	$accountstatic		= new Account($db);
}
if ($arrayfields['project']['checked']) {
	$proj = new Project($db);
}
if ($arrayfields['entry']['checked']) {
	$bankline = new AccountLine($db);
}
if ($arrayfields['account']['checked']) {
	$accountingaccount = new AccountingAccount($db);
}

$sql = "SELECT v.rowid, v.sens, v.amount, v.label, v.datep as datep, v.datev as datev, v.fk_typepayment as type, v.num_payment, v.fk_bank, v.accountancy_code, v.subledger_account, v.fk_projet as fk_project,";
$sql .= " ba.rowid as bid, ba.ref as bref, ba.number as bnumber, ba.account_number as bank_account_number, ba.fk_accountancy_journal as accountancy_journal, ba.label as blabel,";
$sql .= " pst.code as payment_code";
$sql .= " FROM ".MAIN_DB_PREFIX."payment_various as v";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_paiement as pst ON v.fk_typepayment = pst.id";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."bank as b ON v.fk_bank = b.rowid";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."bank_account as ba ON b.fk_account = ba.rowid";
$sql .= " WHERE v.entity IN (".getEntity('payment_various').")";

// Search criteria
if ($search_ref) {
	$sql .= " AND v.rowid = ".((int) $search_ref);
}
if ($search_label) {
	$sql .= natural_search(array('v.label'), $search_label);
}
if ($search_datep_start) {
	$sql .= " AND v.datep >= '".$db->idate($search_datep_start)."'";
}
if ($search_datep_end) {
	$sql .= " AND v.datep <= '".$db->idate($search_datep_end)."'";
}
if ($search_datev_start) {
	$sql .= " AND v.datev >= '".$db->idate($search_datev_start)."'";
}
if ($search_datev_end) {
	$sql .= " AND v.datev <= '".$db->idate($search_datev_end)."'";
}
if ($search_amount_deb) {
	$sql .= natural_search("v.amount", $search_amount_deb, 1);
}
if ($search_amount_cred) {
	$sql .= natural_search("v.amount", $search_amount_cred, 1);
}
if ($search_bank_account > 0) {
	$sql .= " AND b.fk_account = ".((int) $search_bank_account);
}
if ($search_bank_entry > 0) {
	$sql .= " AND b.fk_account = ".((int) $search_bank_account);
}
if ($search_accountancy_account > 0) {
	$sql .= " AND v.accountancy_code = ".((int) $search_accountancy_account);
}
if ($search_accountancy_subledger > 0) {
	$sql .= " AND v.subledger_account = ".((int) $search_accountancy_subledger);
}
if ($search_type_id > 0) {
	$sql .= " AND v.fk_typepayment=".((int) $search_type_id);
}
if ($search_all) {
	$sql .= natural_search(array_keys($fieldstosearchall), $search_all);
}

$sql .= $db->order($sortfield, $sortorder);

$totalnboflines = 0;
$resql = $db->query($sql);
if ($resql) {
	$totalnboflines = $db->num_rows($resql);
}
$sql .= $db->plimit($limit + 1, $offset);

$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);

	// Direct jump if only one record found
	if ($num == 1 && !empty($conf->global->MAIN_SEARCH_DIRECT_OPEN_IF_ONLY_ONE) && $search_all) {
		$obj = $db->fetch_object($resql);
		$id = $obj->rowid;
		header("Location: ".DOL_URL_ROOT.'/compta/bank/various_payment/card.php?id='.$id);
		exit;
	}

	// must be place behind the last "header(...)" call
	llxHeader();

	$i = 0;
	$total = 0;

	$param = '';
	if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
		$param .= '&contextpage='.urlencode($contextpage);
	}
	if ($limit > 0 && $limit != $conf->liste_limit) {
		$param .= '&limit='.urlencode($limit);
	}
	if ($search_ref) {
		$param .= '&search_ref='.urlencode($search_ref);
	}
	if ($search_label) {
		$param .= '&search_label='.urlencode($search_label);
	}
	if ($search_datep_start) {
		$param .= '&search_datep_start='.urlencode($search_datep_start);
	}
	if ($search_datep_end) {
		$param .= '&search_datep_end='.urlencode($search_datep_end);
	}
	if ($search_datev_start) {
		$param .= '&search_datev_start='.urlencode($search_datev_start);
	}
	if ($search_datev_end) {
		$param .= '&search_datev_end='.urlencode($search_datev_end);
	}
	if ($search_type_id > 0) {
		$param .= '&search_type_id='.urlencode($search_type_id);
	}
	if ($search_amount_deb) {
		$param .= '&search_amount_deb='.urlencode($search_amount_deb);
	}
	if ($search_amount_cred) {
		$param .= '&search_amount_cred='.urlencode($search_amount_cred);
	}
	if ($search_bank_account > 0) {
		$param .= '&search_account='.urlencode($search_bank_account);
	}
	if ($search_accountancy_account > 0) {
		$param .= '&search_accountancy_account='.urlencode($search_accountancy_account);
	}
	if ($search_accountancy_subledger > 0) {
		$param .= '&search_accountancy_subledger='.urlencode($search_accountancy_subledger);
	}
	if ($optioncss != '') {
		$param .= '&optioncss='.urlencode($optioncss);
	}

	$url = DOL_URL_ROOT.'/compta/bank/various_payment/card.php?action=create';
	if (!empty($socid)) {
		$url .= '&socid='.urlencode($socid);
	}
	$newcardbutton = dolGetButtonTitle($langs->trans('MenuNewVariousPayment'), '', 'fa fa-plus-circle', $url, '', $user->rights->banque->modifier);

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';

	if ($optioncss != '') {
		print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	}
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="action" value="list">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';

	print_barre_liste($langs->trans("MenuVariousPayment"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $totalnboflines, 'object_payment', 0, $newcardbutton, '', $limit, 0, 0, 1);

	if ($search_all) {
		foreach ($fieldstosearchall as $key => $val) {
			$fieldstosearchall[$key] = $langs->trans($val);
		}
		print '<div class="divsearchfieldfilter">'.$langs->trans("FilterOnInto", $search_all).join(', ', $fieldstosearchall).'</div>';
	}

	$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
	$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage); // This also change content of $arrayfields
	$moreforfilter= '';

	print '<div class="div-table-responsive">';
	print '<table class="tagtable liste'.($moreforfilter ? " listwithfilterbefore" : "").'">';

	print '<tr class="liste_titre">';

	if (!empty($conf->global->MAIN_VIEW_LINE_NUMBER_IN_LIST)) {
		print '<td class="liste_titre">';
		print '</td>';
	}

	// Ref
	if ($arrayfields['ref']['checked']) {
		print '<td class="liste_titre left">';
		print '<input class="flat" type="text" size="3" name="search_ref" value="'.dol_escape_htmltag($search_ref).'">';
		print '</td>';
	}

	// Label
	if ($arrayfields['label']['checked']) {
		print '<td class="liste_titre">';
		print '<input type="text" class="flat" size="10" name="search_label" value="'.dol_escape_htmltag($search_label).'">';
		print '</td>';
	}

	// Payment date
	if ($arrayfields['datep']['checked']) {
		print '<td class="liste_titre center">';
		print '<div class="nowrap">';
		print $form->selectDate($search_datep_start ? $search_datep_start : -1, 'search_date_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
		print '</div>';
		print '<div class="nowrap">';
		print $form->selectDate($search_datep_end ? $search_datep_end : -1, 'search_date_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
		print '</div>';
		print '</td>';
	}

	// Value date
	if ($arrayfields['datev']['checked']) {
		print '<td class="liste_titre center">';
		print '<div class="nowrap">';
		print $form->selectDate($search_datev_start ? $search_datev_start : -1, 'search_date_value_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
		print '</div>';
		print '<div class="nowrap">';
		print $form->selectDate($search_datev_end ? $search_datev_end : -1, 'search_date_value_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
		print '</div>';
		print '</td>';
	}

	// Payment type
	if ($arrayfields['type']['checked']) {
		print '<td class="liste_titre center">';
		$form->select_types_paiements($search_type_id, 'search_type_id', '', 0, 1, 1, 16, 1, 'maxwidth100');
		print '</td>';
	}

	// Project
	if ($arrayfields['project']['checked']) {
		print '<td class="liste_titre">';
		// TODO
		print '</td>';
	}

	// Bank account
	if ($arrayfields['bank']['checked']) {
		print '<td class="liste_titre">';
		$form->select_comptes($search_bank_account, 'search_account', 0, '', 1, '', 0, 'maxwidth100');
		print '</td>';
	}

	// Bank entry
	if ($arrayfields['entry']['checked']) {
		print '<td class="liste_titre left">';
		print '<input name="search_bank_entry" class="flat maxwidth50" type="text" value="'.dol_escape_htmltag($search_bank_entry).'">';
		print '</td>';
	}

	// Accounting account
	if ($arrayfields['account']['checked']) {
		print '<td class="liste_titre">';
		print '<div class="nowrap">';
		print $formaccounting->select_account($search_accountancy_account, 'search_accountancy_account', 1, array(), 1, 1, 'maxwidth200');
		print '</div>';
		print '</td>';
	}

	// Subledger account
	if ($arrayfields['subledger']['checked']) {
		print '<td class="liste_titre">';
		print '<div class="nowrap">';
		print $formaccounting->select_auxaccount($search_accountancy_subledger, 'search_accountancy_subledger', 1, 'maxwidth200');
		print '</div>';
		print '</td>';
	}

	// Debit
	if ($arrayfields['debit']['checked']) {
		print '<td class="liste_titre right">';
		print '<input name="search_amount_deb" class="flat maxwidth50" type="text" value="'.dol_escape_htmltag($search_amount_deb).'">';
		print '</td>';
	}

	// Credit
	if ($arrayfields['credit']['checked']) {
		print '<td class="liste_titre right">';
		print '<input name="search_amount_cred" class="flat maxwidth50" type="text" size="8" value="'.dol_escape_htmltag($search_amount_cred).'">';
		print '</td>';
	}

	print '<td class="liste_titre maxwidthsearch">';
	$searchpicto = $form->showFilterAndCheckAddButtons(0);
	print $searchpicto;
	print '</td>';

	print '</tr>';

	print '<tr class="liste_titre">';

	if (!empty($conf->global->MAIN_VIEW_LINE_NUMBER_IN_LIST)) {
		print_liste_field_titre('#', $_SERVER['PHP_SELF'], '', '', $param, '', $sortfield, $sortorder);
	}

	if ($arrayfields['ref']['checked']) {
		print_liste_field_titre($arrayfields['ref']['label'], $_SERVER["PHP_SELF"], 'v.rowid', '', $param, '', $sortfield, $sortorder);
	}
	if ($arrayfields['label']['checked']) {
		print_liste_field_titre($arrayfields['label']['label'], $_SERVER["PHP_SELF"], 'v.label', '', $param, '', $sortfield, $sortorder);
	}
	if ($arrayfields['datep']['checked']) {
		print_liste_field_titre($arrayfields['datep']['label'], $_SERVER["PHP_SELF"], 'v.datep,v.rowid', '', $param, '', $sortfield, $sortorder, 'center ');
	}
	if ($arrayfields['datev']['checked']) {
		print_liste_field_titre($arrayfields['datev']['label'], $_SERVER["PHP_SELF"], 'v.datev,v.rowid', '', $param, '', $sortfield, $sortorder, 'center ');
	}
	if ($arrayfields['type']['checked']) {
		print_liste_field_titre($arrayfields['type']['label'], $_SERVER["PHP_SELF"], 'type', '', $param, '', $sortfield, $sortorder, 'center ');
	}
	if ($arrayfields['project']['checked']) {
		print_liste_field_titre($arrayfields['project']['label'], $_SERVER["PHP_SELF"], 'fk_project', '', $param, '', $sortfield, $sortorder);
	}
	if ($arrayfields['bank']['checked']) {
		print_liste_field_titre($arrayfields['bank']['label'], $_SERVER["PHP_SELF"], 'ba.label', '', $param, '', $sortfield, $sortorder);
	}
	if ($arrayfields['entry']['checked']) {
		print_liste_field_titre($arrayfields['entry']['label'], $_SERVER["PHP_SELF"], 'ba.label', '', $param, '', $sortfield, $sortorder);
	}
	if ($arrayfields['account']['checked']) {
		print_liste_field_titre($arrayfields['account']['label'], $_SERVER["PHP_SELF"], 'v.accountancy_code', '', $param, '', $sortfield, $sortorder, 'left ');
	}
	if ($arrayfields['subledger']['checked']) {
		print_liste_field_titre($arrayfields['subledger']['label'], $_SERVER["PHP_SELF"], 'v.subledger_account', '', $param, '', $sortfield, $sortorder, 'left ');
	}
	if ($arrayfields['debit']['checked']) {
		print_liste_field_titre($arrayfields['debit']['label'], $_SERVER["PHP_SELF"], 'v.amount', '', $param, '', $sortfield, $sortorder, 'right ');
	}
	if ($arrayfields['credit']['checked']) {
		print_liste_field_titre($arrayfields['credit']['label'], $_SERVER["PHP_SELF"], 'v.amount', '', $param, '', $sortfield, $sortorder, 'right ');
	}

	// Fields from hook
	$parameters = array('arrayfields'=>$arrayfields, 'param'=>$param, 'sortfield'=>$sortfield, 'sortorder'=>$sortorder);
	$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;

	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], '', '', '', '', $sortfield, $sortorder, 'maxwidthsearch center ');
	print '</tr>';


	$totalarray = array();
	$totalarray['nbfield'] = 0;
	$totalarray['val']['total_cred'] = 0;
	$totalarray['val']['total_deb'] = 0;

	while ($i < min($num, $limit)) {
		$obj = $db->fetch_object($resql);

		$variousstatic->id = $obj->rowid;
		$variousstatic->ref = $obj->rowid;
		$variousstatic->label = $obj->label;

		print '<tr class="oddeven">';

		// No
		if (!empty($conf->global->MAIN_VIEW_LINE_NUMBER_IN_LIST)) {
			print '<td>'.(($offset * $limit) + $i).'</td>';
		}

		// Ref
		if ($arrayfields['ref']['checked']) {
			print '<td>'.$variousstatic->getNomUrl(1)."</td>";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Label payment
		if ($arrayfields['label']['checked']) {
			print '<td class="tdoverflowmax150" title="'.$variousstatic->label.'">'.$variousstatic->label."</td>";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Date payment
		if ($arrayfields['datep']['checked']) {
			print '<td class="center">'.dol_print_date($obj->datep, 'day')."</td>";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}


		// Date value
		if ($arrayfields['datev']['checked']) {
			print '<td class="center">'.dol_print_date($obj->datev, 'day')."</td>";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Type
		if ($arrayfields['type']['checked']) {
			print '<td class="center">';
			if ($obj->payment_code) {
				print $langs->trans("PaymentTypeShort".$obj->payment_code);
				print ' ';
			}
			print $obj->num_payment;
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Project
		if ($arrayfields['project']['checked']) {
			print '<td class="nowraponall">';
			if ($obj->fk_project > 0) {
				$proj->fetch($obj->fk_project);
				print $proj->getNomUrl(1);
			}
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Bank account
		if ($arrayfields['bank']['checked']) {
			print '<td class="nowraponall">';
			if ($obj->bid > 0) {
				$accountstatic->id = $obj->bid;
				$accountstatic->ref = $obj->bref;
				$accountstatic->number = $obj->bnumber;

				if (isModEnabled('accounting')) {
					$accountstatic->account_number = $obj->bank_account_number;
					$accountingjournal->fetch($obj->accountancy_journal);
					$accountstatic->accountancy_journal = $accountingjournal->getNomUrl(0, 1, 1, '', 1);
				}

				$accountstatic->label = $obj->blabel;
				print $accountstatic->getNomUrl(1);
			} else {
				print '&nbsp;';
			}
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Bank entry
		if ($arrayfields['entry']['checked']) {
			$bankline->fetch($obj->fk_bank);
			print '<td>'.$bankline->getNomUrl(1).'</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Accounting account
		if ($arrayfields['account']['checked']) {
			$accountingaccount->fetch('', $obj->accountancy_code, 1);

			print '<td class="tdoverflowmax150" title="'.dol_escape_htmltag($obj->accountancy_code.' '.$accountingaccount->label).'">'.$accountingaccount->getNomUrl(0, 1, 1, '', 1).'</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Accounting subledger account
		if ($arrayfields['subledger']['checked']) {
			print '<td class="tdoverflowmax150">'.length_accounta($obj->subledger_account).'</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Debit
		if ($arrayfields['debit']['checked']) {
			print '<td class="nowrap right">';
			if ($obj->sens == 0) {
				print '<span class="amount">'.price($obj->amount).'</span>';
				$totalarray['val']['total_deb'] += $obj->amount;
			}
			if (!$i) {
				$totalarray['nbfield']++;
			}
			if (!$i) {
				$totalarray['pos'][$totalarray['nbfield']] = 'total_deb';
			}
			print '</td>';
		}

		// Credit
		if ($arrayfields['credit']['checked']) {
			print '<td class="nowrap right">';
			if ($obj->sens == 1) {
				print '<span class="amount">'.price($obj->amount).'</span>';
				$totalarray['val']['total_cred'] += $obj->amount;
			}
			if (!$i) {
				$totalarray['nbfield']++;
			}
			if (!$i) {
				$totalarray['pos'][$totalarray['nbfield']] = 'total_cred';
			}
			print '</td>';
		}

		print '<td></td>';

		if (!$i) {
			$totalarray['nbfield']++;
		}

		print '</tr>'."\n";

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

	$parameters = array('arrayfields'=>$arrayfields, 'sql'=>$sql);
	$reshook = $hookmanager->executeHooks('printFieldListFooter', $parameters, $object); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;

	print '</table>'."\n";
	print '</div>'."\n";

	print '</form>'."\n";
} else {
	dol_print_error($db);
}


// End of page
llxFooter();
$db->close();
