<?php
/* Copyright (C) 2013-2016	Olivier Geffroy		<jeff@jeffinfo.com>
 * Copyright (C) 2013-2017	Alexandre Spangaro	<aspangaro@open-dsi.fr>
 * Copyright (C) 2014-2015	Ari Elbaz (elarifr)	<github@accedinfo.com>
 * Copyright (C) 2013-2016	Florian Henry		<florian.henry@open-concept.pro>
 * Copyright (C) 2014		Juanjo Menent		<jmenent@2byte.es>
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
 * \file 		htdocs/accountancy/expensereport/lines.php
 * \ingroup 	Accountancy (Double entries)
 * \brief 		Page of detail of the lines of ventilation of expense reports
 */
require '../../main.inc.php';

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formaccounting.class.php';
require_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport.class.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingaccount.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/accounting.lib.php';

// Load translation files required by the page
$langs->loadLangs(array("compta", "bills", "other", "accountancy", "trips", "productbatch", "hrm"));

$optioncss = GETPOST('optioncss', 'aZ'); // Option for the css output (always '' except when 'print')

$account_parent = GETPOST('account_parent', 'int');
$changeaccount = GETPOST('changeaccount');
// Search Getpost
$search_login = GETPOST('search_login', 'alpha');
$search_expensereport = GETPOST('search_expensereport', 'alpha');
$search_label = GETPOST('search_label', 'alpha');
$search_desc = GETPOST('search_desc', 'alpha');
$search_amount = GETPOST('search_amount', 'alpha');
$search_account = GETPOST('search_account', 'alpha');
$search_vat = GETPOST('search_vat', 'alpha');
$search_day = GETPOST("search_day", "int");
$search_month = GETPOST("search_month", "int");
$search_year = GETPOST("search_year", "int");

// Load variable for pagination
$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : (empty($conf->global->ACCOUNTING_LIMIT_LIST_VENTILATION) ? $conf->liste_limit : $conf->global->ACCOUNTING_LIMIT_LIST_VENTILATION);
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page < 0) {
	$page = 0;
}
$pageprev = $page - 1;
$pagenext = $page + 1;
$offset = $limit * $page;
if (!$sortfield) {
	$sortfield = "erd.date, erd.rowid";
}
if (!$sortorder) {
	if ($conf->global->ACCOUNTING_LIST_SORT_VENTILATION_DONE > 0) {
		$sortorder = "DESC";
	}
}

// Security check
if (empty($conf->accounting->enabled)) {
	accessforbidden();
}
if ($user->socid > 0) {
	accessforbidden();
}
if (empty($user->rights->accounting->mouvements->lire)) {
	accessforbidden();
}


$formaccounting = new FormAccounting($db);


/*
 * Actions
 */

// Purge search criteria
if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // Both test are required to be compatible with all browsers
	$search_login = '';
	$search_expensereport = '';
	$search_label = '';
	$search_desc = '';
	$search_amount = '';
	$search_account = '';
	$search_vat = '';
	$search_day = '';
	$search_month = '';
	$search_year = '';
}

if (is_array($changeaccount) && count($changeaccount) > 0 && $user->rights->accounting->bind->write) {
	$error = 0;

	if (!(GETPOST('account_parent', 'int') >= 0)) {
		$error++;
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Account")), null, 'errors');
	}

	if (!$error) {
		$db->begin();

		$sql1 = "UPDATE ".MAIN_DB_PREFIX."expensereport_det as erd";
		$sql1 .= " SET erd.fk_code_ventilation=".(GETPOST('account_parent', 'int') > 0 ? GETPOST('account_parent', 'int') : '0');
		$sql1 .= ' WHERE erd.rowid IN ('.$db->sanitize(implode(',', $changeaccount)).')';

		dol_syslog('accountancy/expensereport/lines.php::changeaccount sql= '.$sql1);
		$resql1 = $db->query($sql1);
		if (!$resql1) {
			$error++;
			setEventMessages($db->lasterror(), null, 'errors');
		}
		if (!$error) {
			$db->commit();
			setEventMessages($langs->trans("Save"), null, 'mesgs');
		} else {
			$db->rollback();
			setEventMessages($db->lasterror(), null, 'errors');
		}

		$account_parent = ''; // Protection to avoid to mass apply it a second time
	}
}


/*
 * View
 */

$form = new Form($db);
$formother = new FormOther($db);

llxHeader('', $langs->trans("ExpenseReportsVentilation").' - '.$langs->trans("Dispatched"));

print '<script type="text/javascript">
			$(function () {
				$(\'#select-all\').click(function(event) {
				    // Iterate each checkbox
				    $(\':checkbox\').each(function() {
				    	this.checked = true;
				    });
			    });
			    $(\'#unselect-all\').click(function(event) {
				    // Iterate each checkbox
				    $(\':checkbox\').each(function() {
				    	this.checked = false;
				    });
			    });
			});
			 </script>';

/*
 * Expense reports lines
 */
$sql = "SELECT er.ref, er.rowid as erid,";
$sql .= " erd.rowid, erd.fk_c_type_fees, erd.comments, erd.total_ht, erd.fk_code_ventilation, erd.tva_tx, erd.vat_src_code, erd.date,";
$sql .= " f.id as type_fees_id, f.code as type_fees_code, f.label as type_fees_label,";
$sql .= " u.rowid as userid, u.login, u.lastname, u.firstname, u.email, u.gender, u.employee, u.photo, u.statut,";
$sql .= " aa.label, aa.labelshort, aa.account_number";
$sql .= " FROM ".MAIN_DB_PREFIX."expensereport as er";
$sql .= " INNER JOIN ".MAIN_DB_PREFIX."expensereport_det as erd ON er.rowid = erd.fk_expensereport";
$sql .= " INNER JOIN ".MAIN_DB_PREFIX."accounting_account as aa ON aa.rowid = erd.fk_code_ventilation";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_type_fees as f ON f.id = erd.fk_c_type_fees";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as u ON u.rowid = er.fk_user_author";
$sql .= " WHERE erd.fk_code_ventilation > 0";
$sql .= " AND er.entity IN (".getEntity('expensereport', 0).")"; // We don't share object for accountancy
$sql .= " AND er.fk_statut IN (".ExpenseReport::STATUS_APPROVED.", ".ExpenseReport::STATUS_CLOSED.")";
// Add search filter like
if (strlen(trim($search_login))) {
	$sql .= natural_search("u.login", $search_login);
}
if (strlen(trim($search_expensereport))) {
	$sql .= natural_search("er.ref", $search_expensereport);
}
if (strlen(trim($search_label))) {
	$sql .= natural_search("f.label", $search_label);
}
if (strlen(trim($search_desc))) {
	$sql .= natural_search("er.comments", $search_desc);
}
if (strlen(trim($search_amount))) {
	$sql .= natural_search("erd.total_ht", $search_amount, 1);
}
if (strlen(trim($search_account))) {
	$sql .= natural_search("aa.account_number", $search_account);
}
if (strlen(trim($search_vat))) {
	$sql .= natural_search("erd.tva_tx", price2num($search_vat), 1);
}
$sql .= dolSqlDateFilter('erd.date', $search_day, $search_month, $search_year);
$sql .= " AND er.entity IN (".getEntity('expensereport', 0).")"; // We don't share object for accountancy

$sql .= $db->order($sortfield, $sortorder);

// Count total nb of records
$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST)) {
	$result = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($result);
	if (($page * $limit) > $nbtotalofrecords) {	// if total resultset is smaller then paging size (filtering), goto and load page 0
		$page = 0;
		$offset = 0;
	}
}

$sql .= $db->plimit($limit + 1, $offset);

dol_syslog('accountancy/expensereport/lines.php::list');
$result = $db->query($sql);

if ($result) {
	$num_lines = $db->num_rows($result);
	$i = 0;

	$param = '';
	if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
		$param .= '&contextpage='.urlencode($contextpage);
	}
	if ($limit > 0 && $limit != $conf->liste_limit) {
		$param .= '&limit='.urlencode($limit);
	}
	if ($search_login) {
		$param .= '&search_login='.urlencode($search_login);
	}
	if ($search_expensereport) {
		$param .= "&search_expensereport=".urlencode($search_expensereport);
	}
	if ($search_label) {
		$param .= "&search_label=".urlencode($search_label);
	}
	if ($search_desc) {
		$param .= "&search_desc=".urlencode($search_desc);
	}
	if ($search_account) {
		$param .= "&search_account=".urlencode($search_account);
	}
	if ($search_vat) {
		$param .= "&search_vat=".urlencode($search_vat);
	}
	if ($search_day) {
		$param .= '&search_day='.urlencode($search_day);
	}
	if ($search_month) {
		$param .= '&search_month='.urlencode($search_month);
	}
	if ($search_year) {
		$param .= '&search_year='.urlencode($search_year);
	}

	print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">'."\n";
	print '<input type="hidden" name="action" value="ventil">';
	if ($optioncss != '') {
		print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	}
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	print '<input type="hidden" name="page" value="'.$page.'">';

	print_barre_liste($langs->trans("ExpenseReportLinesDone"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num_lines, $nbtotalofrecords, 'title_accountancy', 0, '', '', $limit);

	print '<span class="opacitymedium">'.$langs->trans("DescVentilDoneExpenseReport").'</span><br>';

	print '<br><div class="inline-block divButAction">'.$langs->trans("ChangeAccount").'<br>';
	print $formaccounting->select_account($account_parent, 'account_parent', 2, array(), 0, 0, 'maxwidth300 maxwidthonsmartphone valignmiddle');
	print '<input type="submit" class="button valignmiddle" value="'.$langs->trans("ChangeBinding").'" /></div>';

	$moreforfilter = '';

	print '<div class="div-table-responsive">';
	print '<table class="tagtable liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

	print '<tr class="liste_titre_filter">';
	print '<td class="liste_titre"><input type="text" name="search_login" class="maxwidth50" value="'.$search_login.'"></td>';
	print '<td class="liste_titre"></td>';
	print '<td><input type="text" class="flat maxwidth50" name="search_expensereport" value="'.dol_escape_htmltag($search_expensereport).'"></td>';
	if (!empty($conf->global->ACCOUNTANCY_USE_EXPENSE_REPORT_VALIDATION_DATE)) {
		print '<td class="liste_titre"></td>';
	}
	print '<td class="liste_titre center">';
	if (!empty($conf->global->MAIN_LIST_FILTER_ON_DAY)) {
		print '<input class="flat valignmiddle maxwidth25" type="text" maxlength="2" name="search_day" value="'.$search_day.'">';
	}
	print '<input class="flat valignmiddle maxwidth25" type="text" maxlength="2" name="search_month" value="'.$search_month.'">';
	$formother->select_year($search_year, 'search_year', 1, 20, 5);
	print '</td>';
	print '<td class="liste_titre"><input type="text" class="flat maxwidth50" name="search_label" value="'.dol_escape_htmltag($search_label).'"></td>';
	print '<td class="liste_titre"><input type="text" class="flat maxwidth50" name="search_desc" value="'.dol_escape_htmltag($search_desc).'"></td>';
	print '<td class="liste_titre right"><input type="text" class="flat maxwidth50" name="search_amount" value="'.dol_escape_htmltag($search_amount).'"></td>';
	print '<td class="liste_titre center"><input type="text" class="flat maxwidth50" name="search_vat" size="1" placeholder="%" value="'.dol_escape_htmltag($search_vat).'"></td>';
	print '<td class="liste_titre"><input type="text" class="flat maxwidth50" name="search_account" value="'.dol_escape_htmltag($search_account).'"></td>';
	print '<td class="liste_titre center">';
	$searchpicto = $form->showFilterButtons();
	print $searchpicto;
	print '</td>';
	print "</tr>\n";

	print '<tr class="liste_titre">';
	print_liste_field_titre("Employees", $_SERVER['PHP_SELF'], "u.login", $param, "", "", $sortfield, $sortorder);
	print_liste_field_titre("LineId", $_SERVER["PHP_SELF"], "erd.rowid", "", $param, '', $sortfield, $sortorder);
	print_liste_field_titre("ExpenseReport", $_SERVER["PHP_SELF"], "er.ref", "", $param, '', $sortfield, $sortorder);
	if (!empty($conf->global->ACCOUNTANCY_USE_EXPENSE_REPORT_VALIDATION_DATE)) {
		print_liste_field_titre("DateValidation", $_SERVER["PHP_SELF"], "er.date_valid", "", $param, '', $sortfield, $sortorder, 'center ');
	}
	print_liste_field_titre("DateOfLine", $_SERVER["PHP_SELF"], "erd.date, erd.rowid", "", $param, '', $sortfield, $sortorder, 'center ');
	print_liste_field_titre("TypeFees", $_SERVER["PHP_SELF"], "f.label", "", $param, '', $sortfield, $sortorder);
	print_liste_field_titre("Description", $_SERVER["PHP_SELF"], "erd.comments", "", $param, '', $sortfield, $sortorder);
	print_liste_field_titre("Amount", $_SERVER["PHP_SELF"], "erd.total_ht", "", $param, '', $sortfield, $sortorder, 'right ');
	print_liste_field_titre("VATRate", $_SERVER["PHP_SELF"], "erd.tva_tx", "", $param, '', $sortfield, $sortorder, 'center ');
	print_liste_field_titre("AccountAccounting", $_SERVER["PHP_SELF"], "aa.account_number", "", $param, '', $sortfield, $sortorder);
	$checkpicto = $form->showCheckAddButtons();
	print_liste_field_titre($checkpicto, '', '', '', '', '', '', '', 'center ');
	print "</tr>\n";

	$expensereportstatic = new ExpenseReport($db);
	$accountingaccountstatic = new AccountingAccount($db);
	$userstatic = new User($db);

	$i = 0;
	while ($i < min($num_lines, $limit)) {
		$objp = $db->fetch_object($result);

		$expensereportstatic->ref = $objp->ref;
		$expensereportstatic->id = $objp->erid;

		$userstatic->id = $objp->userid;
		$userstatic->ref = $objp->label;
		$userstatic->login = $objp->login;
		$userstatic->statut = $objp->statut;
		$userstatic->email = $objp->email;
		$userstatic->gender = $objp->gender;
		$userstatic->firstname = $objp->firstname;
		$userstatic->lastname = $objp->lastname;
		$userstatic->employee = $objp->employee;
		$userstatic->photo = $objp->photo;

		$accountingaccountstatic->rowid = $objp->fk_compte;
		$accountingaccountstatic->label = $objp->label;
		$accountingaccountstatic->labelshort = $objp->labelshort;
		$accountingaccountstatic->account_number = $objp->account_number;

		print '<tr class="oddeven">';

		// Login
		print '<td class="nowraponall">';
		print $userstatic->getNomUrl(-1, '', 0, 0, 24, 1, 'login', '', 1);
		print '</td>';

		// Line id
		print '<td>'.$objp->rowid.'</td>';

		// Ref Expense report
		print '<td>'.$expensereportstatic->getNomUrl(1).'</td>';

		// Date validation
		if (!empty($conf->global->ACCOUNTANCY_USE_EXPENSE_REPORT_VALIDATION_DATE)) {
			print '<td class="center">'.dol_print_date($db->jdate($objp->date_valid), 'day').'</td>';
		}

		print '<td class="center">'.dol_print_date($db->jdate($objp->date), 'day').'</td>';

		// Fees label
		print '<td class="tdoverflow">'.($langs->trans($objp->type_fees_code) == $objp->type_fees_code ? $objp->type_fees_label : $langs->trans(($objp->type_fees_code))).'</td>';

		// Fees description -- Can be null
		print '<td>';
		$text = dolGetFirstLineOfText(dol_string_nohtmltag($objp->comments));
		$trunclength = empty($conf->global->ACCOUNTING_LENGTH_DESCRIPTION) ? 32 : $conf->global->ACCOUNTING_LENGTH_DESCRIPTION;
		print $form->textwithtooltip(dol_trunc($text, $trunclength), $objp->comments);
		print '</td>';

		// Amount without taxes
		print '<td class="right nowraponall amount">'.price($objp->total_ht).'</td>';

		// Vat rate
		print '<td class="center">'.vatrate($objp->tva_tx.($objp->vat_src_code ? ' ('.$objp->vat_src_code.')' : '')).'</td>';

		// Accounting account affected
		print '<td>';
		print $accountingaccountstatic->getNomUrl(0, 1, 1, '', 1);
		print ' <a class="editfielda reposition marginleftonly marginrightonly" href="./card.php?id='.$objp->rowid.'&backtopage='.urlencode($_SERVER["PHP_SELF"].($param ? '?'.$param : '')).'">';
		print img_edit();
		print '</a></td>';

		print '<td class="center"><input type="checkbox" class="checkforaction" name="changeaccount[]" value="'.$objp->rowid.'"/></td>';

		print "</tr>";
		$i++;
	}

	print "</table>";
	print "</div>";

	if ($nbtotalofrecords > $limit) {
		print_barre_liste('', $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num_lines, $nbtotalofrecords, '', 0, '', '', $limit, 1);
	}

	print '</form>';
} else {
	print $db->lasterror();
}

// End of page
llxFooter();
$db->close();
