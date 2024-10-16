<?php
/* Copyright (C) 2013-2014	Olivier Geffroy		<jeff@jeffinfo.com>
 * Copyright (C) 2013-2014	Florian Henry		<florian.henry@open-concept.pro>
 * Copyright (C) 2013-2024	Alexandre Spangaro	<alexandre@inovea-conseil.com>
 * Copyright (C) 2014		Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2024		Frédéric France			<frederic.france@free.fr>
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
 * \file		htdocs/accountancy/expensereport/index.php
 * \ingroup		Accountancy (Double entries)
 * \brief		Home expense report ventilation
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/accounting.lib.php';
require_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport.class.php';

// Load translation files required by the page
$langs->loadLangs(array("compta", "bills", "other", "accountancy"));

$validatemonth = GETPOSTINT('validatemonth');
$validateyear = GETPOSTINT('validateyear');

$month_start = getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1);
if (GETPOSTINT("year")) {
	$year_start = GETPOSTINT("year");
} else {
	$year_start = dol_print_date(dol_now(), '%Y');
	if (dol_print_date(dol_now(), '%m') < $month_start) {
		$year_start--; // If current month is lower that starting fiscal month, we start last year
	}
}
$year_end = $year_start + 1;
$month_end = $month_start - 1;
if ($month_end < 1) {
	$month_end = 12;
	$year_end--;
}
$search_date_start = dol_mktime(0, 0, 0, $month_start, 1, $year_start);
$search_date_end = dol_get_last_day($year_end, $month_end);
$year_current = $year_start;

// Validate History
$action = GETPOST('action', 'aZ09');

$chartaccountcode = dol_getIdFromCode($db, getDolGlobalString('CHARTOFACCOUNTS'), 'accounting_system', 'rowid', 'pcg_version');

// Security check
if (!isModEnabled('accounting')) {
	accessforbidden();
}
if ($user->socid > 0) {
	accessforbidden();
}
if (!$user->hasRight('accounting', 'bind', 'write')) {
	accessforbidden();
}


/*
 * Actions
 */

if (($action == 'clean' || $action == 'validatehistory') && $user->hasRight('accounting', 'bind', 'write')) {
	// Clean database by removing binding done on non existing or no more existing accounts
	$db->begin();
	$sql1 = "UPDATE ".$db->prefix()."expensereport_det as erd";
	$sql1 .= " SET fk_code_ventilation = 0";
	$sql1 .= ' WHERE erd.fk_code_ventilation NOT IN';
	$sql1 .= '	(SELECT accnt.rowid ';
	$sql1 .= '	FROM '.$db->prefix().'accounting_account as accnt';
	$sql1 .= '	INNER JOIN '.$db->prefix().'accounting_system as syst';
	$sql1 .= '	ON accnt.fk_pcg_version = syst.pcg_version AND syst.rowid='.((int) getDolGlobalInt('CHARTOFACCOUNTS')).' AND accnt.entity = '.((int) $conf->entity).')';
	$sql1 .= ' AND erd.fk_expensereport IN (SELECT rowid FROM '.$db->prefix().'expensereport WHERE entity = '.((int) $conf->entity).')';
	$sql1 .= " AND fk_code_ventilation <> 0";
	dol_syslog("htdocs/accountancy/customer/index.php fixaccountancycode", LOG_DEBUG);
	$resql1 = $db->query($sql1);
	if (!$resql1) {
		$error++;
		$db->rollback();
		setEventMessages($db->lasterror(), null, 'errors');
	} else {
		$db->commit();
	}
	// End clean database
}

if ($action == 'validatehistory' && $user->hasRight('accounting', 'bind', 'write')) {
	$error = 0;
	$nbbinddone = 0;
	$nbbindfailed = 0;
	$notpossible = 0;

	$db->begin();

	// Now make the binding
	$sql1 = "SELECT erd.rowid, accnt.rowid as suggestedid";
	$sql1 .= " FROM ".MAIN_DB_PREFIX."expensereport_det as erd";
	$sql1 .= " LEFT JOIN ".MAIN_DB_PREFIX."c_type_fees as t ON erd.fk_c_type_fees = t.id";
	$sql1 .= " LEFT JOIN ".MAIN_DB_PREFIX."accounting_account as accnt ON t.accountancy_code = accnt.account_number AND accnt.active = 1 AND accnt.fk_pcg_version = '".$db->escape($chartaccountcode)."' AND accnt.entity =".((int) $conf->entity).",";
	$sql1 .= " ".MAIN_DB_PREFIX."expensereport as er";
	$sql1 .= " WHERE erd.fk_expensereport = er.rowid AND er.entity = ".((int) $conf->entity);
	$sql1 .= " AND er.fk_statut IN (".ExpenseReport::STATUS_APPROVED.", ".ExpenseReport::STATUS_CLOSED.") AND erd.fk_code_ventilation <= 0";
	if ($validatemonth && $validateyear) {
		$sql1 .= dolSqlDateFilter('erd.date', 0, $validatemonth, $validateyear);
	}

	dol_syslog('htdocs/accountancy/expensereport/index.php');

	$result = $db->query($sql1);
	if (!$result) {
		$error++;
		setEventMessages($db->lasterror(), null, 'errors');
	} else {
		$num_lines = $db->num_rows($result);

		$i = 0;
		while ($i < min($num_lines, 10000)) {	// No more than 10000 at once
			$objp = $db->fetch_object($result);

			$lineid = $objp->rowid;
			$suggestedid = $objp->suggestedid;

			if ($suggestedid > 0) {
				$sqlupdate = "UPDATE ".MAIN_DB_PREFIX."expensereport_det";
				$sqlupdate .= " SET fk_code_ventilation = ".((int) $suggestedid);
				$sqlupdate .= " WHERE fk_code_ventilation <= 0 AND rowid = ".((int) $lineid);

				$resqlupdate = $db->query($sqlupdate);
				if (!$resqlupdate) {
					$error++;
					setEventMessages($db->lasterror(), null, 'errors');
					$nbbindfailed++;
					break;
				} else {
					$nbbinddone++;
				}
			} else {
				$notpossible++;
				$nbbindfailed++;
			}

			$i++;
		}
		if ($num_lines > 10000) {
			$notpossible += ($num_lines - 10000);
		}
	}

	if ($error) {
		$db->rollback();
	} else {
		$db->commit();
		setEventMessages($langs->trans('AutomaticBindingDone', $nbbinddone, $notpossible), null, ($notpossible ? 'warnings' : 'mesgs'));
		if ($nbbindfailed) {
			setEventMessages($langs->trans('DoManualBindingForFailedRecord', $nbbindfailed), null, 'warnings');
		}
	}
}


/*
 * View
 */
$help_url = 'EN:Module_Double_Entry_Accounting|FR:Module_Comptabilit&eacute;_en_Partie_Double#Liaisons_comptables';

llxHeader('', $langs->trans("ExpenseReportsVentilation"), $help_url, '', 0, 0, '', '', '', 'mod-accountancy accountancy-expensereport page-list');

$textprevyear = '<a href="'.$_SERVER["PHP_SELF"].'?year='.($year_current - 1).'">'.img_previous().'</a>';
$textnextyear = '&nbsp;<a href="'.$_SERVER["PHP_SELF"].'?year='.($year_current + 1).'">'.img_next().'</a>';


print load_fiche_titre($langs->trans("ExpenseReportsVentilation")."&nbsp;".$textprevyear."&nbsp;".$langs->trans("Year")."&nbsp;".$year_start."&nbsp;".$textnextyear, '', 'title_accountancy');

print '<span class="opacitymedium">'.$langs->trans("DescVentilExpenseReport").'</span><br>';
print '<span class="opacitymedium hideonsmartphone">'.$langs->trans("DescVentilExpenseReportMore", $langs->transnoentitiesnoconv("ValidateHistory"), $langs->transnoentitiesnoconv("ToBind")).'<br>';
print '</span><br>';


$y = $year_current;

$buttonbind = '<a class="button small" href="'.$_SERVER['PHP_SELF'].'?action=validatehistory&token='.newToken().'&year='.$year_current.'">'.img_picto($langs->trans("ValidateHistory"), 'link', 'class="pictofixedwidth fa-color-unset"').$langs->trans("ValidateHistory").'</a>';


print_barre_liste(img_picto('', 'unlink', 'class="paddingright fa-color-unset"').$langs->trans("OverviewOfAmountOfLinesNotBound"), '', '', '', '', '', '', -1, '', '', 0, '', '', 0, 1, 1, 0, $buttonbind);
//print load_fiche_titre($langs->trans("OverviewOfAmountOfLinesNotBound"), $buttonbind, '');

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td class="minwidth100">'.$langs->trans("Account").'</td>';
for ($i = 1; $i <= 12; $i++) {
	$j = $i + getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1) - 1;
	if ($j > 12) {
		$j -= 12;
	}
	$cursormonth = $j;
	if ($cursormonth > 12) {
		$cursormonth -= 12;
	}
	$cursoryear = ($cursormonth < getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1)) ? $y + 1 : $y;
	$tmp = dol_getdate(dol_get_last_day($cursoryear, $cursormonth, 'gmt'), false, 'gmt');

	print '<td width="60" class="right">';
	if (!empty($tmp['mday'])) {
		$param = 'search_date_startday=1&search_date_startmonth='.$cursormonth.'&search_date_startyear='.$cursoryear;
		$param .= '&search_date_endday='.$tmp['mday'].'&search_date_endmonth='.$tmp['mon'].'&search_date_endyear='.$tmp['year'];
		$param .= '&search_month='.$tmp['mon'].'&search_year='.$tmp['year'];
		print '<a href="'.DOL_URL_ROOT.'/accountancy/expensereport/list.php?'.$param.'">';
	}
	print $langs->trans('MonthShort'.str_pad((string) $j, 2, '0', STR_PAD_LEFT));
	if (!empty($tmp['mday'])) {
		print '</a>';
	}
	print '</td>';
}
print '<td width="60" class="right"><b>'.$langs->trans("Total").'</b></td></tr>';

$sql = "SELECT ".$db->ifsql('aa.account_number IS NULL', "'tobind'", 'aa.account_number')." AS codecomptable,";
$sql .= " ".$db->ifsql('aa.label IS NULL', "'tobind'", 'aa.label')." AS intitule,";
for ($i = 1; $i <= 12; $i++) {
	$j = $i + getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1) - 1;
	if ($j > 12) {
		$j -= 12;
	}
	$sql .= "  SUM(".$db->ifsql("MONTH(er.date_debut) = ".((int) $j), "erd.total_ht", "0").") AS month".str_pad((string) $j, 2, "0", STR_PAD_LEFT).",";
	$sql .= "  SUM(".$db->ifsql("MONTH(er.date_debut) = ".((string) $j), "1", "0").") AS nbmonth".str_pad((string) $j, 2, "0", STR_PAD_LEFT).",";
}
$sql .= " SUM(erd.total_ht) as total, COUNT(erd.rowid) as nb";
$sql .= " FROM ".MAIN_DB_PREFIX."expensereport_det as erd";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."expensereport as er ON er.rowid = erd.fk_expensereport";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."accounting_account as aa ON aa.rowid = erd.fk_code_ventilation";
$sql .= " WHERE er.date_debut >= '".$db->idate($search_date_start)."'";
$sql .= " AND er.date_debut <= '".$db->idate($search_date_end)."'";
// Define begin binding date
if (getDolGlobalString('ACCOUNTING_DATE_START_BINDING')) {
	$sql .= " AND er.date_debut >= '".$db->idate(getDolGlobalString('ACCOUNTING_DATE_START_BINDING'))."'";
}
$sql .= " AND er.fk_statut IN (".ExpenseReport::STATUS_APPROVED.", ".ExpenseReport::STATUS_CLOSED.")";
$sql .= " AND er.entity IN (".getEntity('expensereport', 0).")"; // We don't share object for accountancy
$sql .= " AND aa.account_number IS NULL";
$sql .= " GROUP BY erd.fk_code_ventilation,aa.account_number,aa.label";
$sql .= ' ORDER BY aa.account_number';

dol_syslog('/accountancy/expensereport/index.php', LOG_DEBUG);
$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);

	while ($row = $db->fetch_row($resql)) {
		print '<tr class="oddeven">';
		print '<td>';
		if ($row[0] == 'tobind') {
			//print '<span class="opacitymedium">'.$langs->trans("Unknown").'</span>';
		} else {
			print length_accountg($row[0]).' - ';
		}
		//print '</td>';
		//print '<td>';
		if ($row[0] == 'tobind') {
			$startmonth = getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1);
			if ($startmonth > 12) {
				$startmonth -= 12;
			}
			$startyear = ($startmonth < getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1)) ? $y + 1 : $y;
			$endmonth = getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1) + 11;
			if ($endmonth > 12) {
				$endmonth -= 12;
			}
			$endyear = ($endmonth < getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1)) ? $y + 1 : $y;
			print $langs->trans("UseMenuToSetBindindManualy", DOL_URL_ROOT.'/accountancy/expensereport/list.php?search_date_startday=1&search_date_startmonth='.((int) $startmonth).'&search_date_startyear='.((int) $startyear).'&search_date_endday=&search_date_endmonth='.((int) $endmonth).'&search_date_endyear='.((int) $endyear), $langs->transnoentitiesnoconv("ToBind"));
		} else {
			print $row[1];
		}
		print '</td>';
		for ($i = 2; $i <= 13; $i++) {
			$cursormonth = (getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1) + $i - 2);
			if ($cursormonth > 12) {
				$cursormonth -= 12;
			}
			$cursoryear = ($cursormonth < getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1)) ? $y + 1 : $y;
			$tmp = dol_getdate(dol_get_last_day($cursoryear, $cursormonth, 'gmt'), false, 'gmt');

			print '<td class="right nowraponall amount" title="'.price($row[2*$i - 2]).' - '.$row[2*$i - 1].' lines">';
			print price($row[2*$i - 2]);
			// Add link to make binding
			if (!empty(price2num($row[2*$i - 2])) || !empty($row[2*$i - 1])) {
				print '<a href="'.$_SERVER['PHP_SELF'].'?action=validatehistory&year='.$y.'&validatemonth='.((int) $cursormonth).'&validateyear='.((int) $cursoryear).'&token='.newToken().'">';
				print img_picto($langs->trans("ValidateHistory").' ('.$langs->trans('Month'.str_pad((string) $cursormonth, 2, '0', STR_PAD_LEFT)).' '.$cursoryear.')', 'link', 'class="marginleft2"');
				print '</a>';
			}
			print '</td>';
		}
		print '<td class="right nowraponall amount"><b>'.price($row[26]).'</b></td>';
		print '</tr>';
	}
	$db->free($resql);

	if ($num == 0) {
		print '<tr class="oddeven"><td colspan="15">';
		print '<span class="opacitymedium">'.$langs->trans("NoRecordFound").'</span>';
		print '</td></tr>';
	}
} else {
	print $db->lasterror(); // Show last sql error
}
print "</table>\n";
print '</div>';


print '<br>';


print_barre_liste(img_picto('', 'link', 'class="paddingright fa-color-unset"').$langs->trans("OverviewOfAmountOfLinesBound"), '', '', '', '', '', '', -1, '', '', 0, '', '', 0, 1, 1);
//print load_fiche_titre($langs->trans("OverviewOfAmountOfLinesBound"), '', '');


print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td class="minwidth100">'.$langs->trans("Account").'</td>';
for ($i = 1; $i <= 12; $i++) {
	$j = $i + getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1) - 1;
	if ($j > 12) {
		$j -= 12;
	}
	print '<td width="60" class="right">'.$langs->trans('MonthShort'.str_pad((string) $j, 2, '0', STR_PAD_LEFT)).'</td>';
}
print '<td width="60" class="right"><b>'.$langs->trans("Total").'</b></td></tr>';

$sql = "SELECT ".$db->ifsql('aa.account_number IS NULL', "'tobind'", 'aa.account_number')." AS codecomptable,";
$sql .= "  ".$db->ifsql('aa.label IS NULL', "'tobind'", 'aa.label')." AS intitule,";
for ($i = 1; $i <= 12; $i++) {
	$j = $i + getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1) - 1;
	if ($j > 12) {
		$j -= 12;
	}
	$sql .= " SUM(".$db->ifsql("MONTH(er.date_debut) = ".((int) $j), "erd.total_ht", "0").") AS month".str_pad((string) $j, 2, "0", STR_PAD_LEFT).",";
}
$sql .= " ROUND(SUM(erd.total_ht),2) as total";
$sql .= " FROM ".MAIN_DB_PREFIX."expensereport_det as erd";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."expensereport as er ON er.rowid = erd.fk_expensereport";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."accounting_account as aa ON aa.rowid = erd.fk_code_ventilation";
$sql .= " WHERE er.date_debut >= '".$db->idate($search_date_start)."'";
$sql .= " AND er.date_debut <= '".$db->idate($search_date_end)."'";
// Define begin binding date
if (getDolGlobalString('ACCOUNTING_DATE_START_BINDING')) {
	$sql .= " AND er.date_debut >= '".$db->idate(getDolGlobalString('ACCOUNTING_DATE_START_BINDING'))."'";
}
$sql .= " AND er.fk_statut IN (".ExpenseReport::STATUS_APPROVED.", ".ExpenseReport::STATUS_CLOSED.")";
$sql .= " AND er.entity IN (".getEntity('expensereport', 0).")"; // We don't share object for accountancy
$sql .= " AND aa.account_number IS NOT NULL";
$sql .= " GROUP BY erd.fk_code_ventilation,aa.account_number,aa.label";

dol_syslog('htdocs/accountancy/expensereport/index.php');
$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);

	while ($row = $db->fetch_row($resql)) {
		print '<tr class="oddeven">';
		print '<td class="tdoverflowmax300"'.(empty($row[1]) ? '' : ' title="'.dol_escape_htmltag($row[1]).'"').'>';
		if ($row[0] == 'tobind') {
			//print '<span class="opacitymedium">'.$langs->trans("Unknown").'</span>';
		} else {
			print length_accountg($row[0]).' - ';
		}
		if ($row[0] == 'tobind') {
			print $langs->trans("UseMenuToSetBindindManualy", DOL_URL_ROOT.'/accountancy/expensereport/list.php?search_year='.((int) $y), $langs->transnoentitiesnoconv("ToBind"));
		} else {
			print $row[1];
		}
		print '</td>';
		for ($i = 2; $i <= 13; $i++) {
			print '<td class="right nowraponall amount">';
			print price($row[$i]);
			print '</td>';
		}
		print '<td class="right nowraponall amount"><b>'.price($row[14]).'</b></td>';
		print '</tr>';
	}
	$db->free($resql);

	if ($num == 0) {
		print '<tr class="oddeven"><td colspan="15">';
		print '<span class="opacitymedium">'.$langs->trans("NoRecordFound").'</span>';
		print '</td></tr>';
	}
} else {
	print $db->lasterror(); // Show last sql error
}
print "</table>\n";
print '</div>';



if (getDolGlobalString('SHOW_TOTAL_OF_PREVIOUS_LISTS_IN_LIN_PAGE')) { // This part of code looks strange. Why showing a report that should rely on result of this step ?
	print '<br>';
	print '<br>';

	print_barre_liste($langs->trans("OtherInfo"), '', '', '', '', '', '', -1, '', '', 0, '', '', 0, 1, 1);
	//print load_fiche_titre($langs->trans("OtherInfo"), '', '');

	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre"><td class="left">'.$langs->trans("Total").'</td>';
	for ($i = 1; $i <= 12; $i++) {
		$j = $i + getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1) - 1;
		if ($j > 12) {
			$j -= 12;
		}
		print '<td width="60" class="right">'.$langs->trans('MonthShort'.str_pad((string) $j, 2, '0', STR_PAD_LEFT)).'</td>';
	}
	print '<td width="60" class="right"><b>'.$langs->trans("Total").'</b></td></tr>';

	$sql = "SELECT '".$db->escape($langs->trans("TotalExpenseReport"))."' AS label,";
	for ($i = 1; $i <= 12; $i++) {
		$j = $i + getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1) - 1;
		if ($j > 12) {
			$j -= 12;
		}
		$sql .= " SUM(".$db->ifsql("MONTH(er.date_create) = ".((int) $j), "erd.total_ht", "0").") AS month".str_pad((string) $j, 2, "0", STR_PAD_LEFT).",";
	}
	$sql .= " SUM(erd.total_ht) as total";
	$sql .= " FROM ".MAIN_DB_PREFIX."expensereport_det as erd";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."expensereport as er ON er.rowid = erd.fk_expensereport";
	$sql .= " WHERE er.date_debut >= '".$db->idate($search_date_start)."'";
	$sql .= " AND er.date_debut <= '".$db->idate($search_date_end)."'";
	// Define begin binding date
	if (getDolGlobalString('ACCOUNTING_DATE_START_BINDING')) {
		$sql .= " AND er.date_debut >= '".$db->idate(getDolGlobalString('ACCOUNTING_DATE_START_BINDING'))."'";
	}
	$sql .= " AND er.fk_statut IN (".ExpenseReport::STATUS_APPROVED.", ".ExpenseReport::STATUS_CLOSED.")";
	$sql .= " AND er.entity IN (".getEntity('expensereport', 0).")"; // We don't share object for accountancy

	dol_syslog('htdocs/accountancy/expensereport/index.php');
	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);

		while ($row = $db->fetch_row($resql)) {
			print '<tr><td>'.$row[0].'</td>';
			for ($i = 1; $i <= 12; $i++) {
				print '<td class="right nowraponall amount">'.price($row[$i]).'</td>';
			}
			print '<td class="right nowraponall amount"><b>'.price($row[13]).'</b></td>';
			print '</tr>';
		}

		$db->free($resql);
	} else {
		print $db->lasterror(); // Show last sql error
	}
	print "</table>\n";
	print '</div>';
}

// End of page
llxFooter();
$db->close();
