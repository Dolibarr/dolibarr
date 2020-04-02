<?php
/* Copyright (C) 2019       Open-DSI    	    <support@open-dsi.fr>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

/**
 * \file 	    htdocs/accountancy/closure/validate.php
 * \ingroup     Accountancy
 * \brief 	    Validate entries page
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/accounting.lib.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/bookkeeping.class.php';

// Load translation files required by the page
$langs->loadLangs(array("compta", "bills", "other", "main", "accountancy"));

// Security check
if (empty($conf->accounting->enabled)) {
	accessforbidden();
}
if ($user->socid > 0)
	accessforbidden();
	if (!$user->rights->accounting->fiscalyear->write)
	accessforbidden();


$month_start = ($conf->global->SOCIETE_FISCAL_MONTH_START ? ($conf->global->SOCIETE_FISCAL_MONTH_START) : 1);
if (GETPOST("year", 'int')) $year_start = GETPOST("year", 'int');
else
{
	$year_start = dol_print_date(dol_now(), '%Y');
	if (dol_print_date(dol_now(), '%m') < $month_start) $year_start--; // If current month is lower that starting fiscal month, we start last year
}
$year_end = $year_start + 1;
$month_end = $month_start - 1;
if ($month_end < 1)
{
	$month_end = 12;
	$year_end--;
}
$search_date_start = dol_mktime(0, 0, 0, $month_start, 1, $year_start);
$search_date_end = dol_get_last_day($year_end, $month_end);
$year_current = $year_start;

/*
 * Actions
 */

if ($action == 'validate')
{
    $now = dol_now();

    // Update database
    $db->begin();
    $sql = "UPDATE ".MAIN_DB_PREFIX."accounting_bookkeeping as b";
    $sql .= " SET b.date_validated = '".$db->idate($now)."'";
    $sql .= ' WHERE b.date_validated IS NULL';

    dol_syslog("htdocs/accountancy/closure/validate.php validate", LOG_DEBUG);
    $resql = $db->query($sql);
    if (!$resql1) {
        $error++;
        $db->rollback();
        setEventMessages($db->lasterror(), null, 'errors');
    } else {
        $db->commit();
    }
    // End clean database
}


/*
 * View
 */

llxHeader('', $langs->trans("ValidateMovements"));

$textprevyear = '<a href="'.$_SERVER["PHP_SELF"].'?year='.($year_current - 1).'">'.img_previous().'</a>';
$textnextyear = '&nbsp;<a href="'.$_SERVER["PHP_SELF"].'?year='.($year_current + 1).'">'.img_next().'</a>';


print load_fiche_titre($langs->trans("ValidateMovements")." ".$textprevyear." ".$langs->trans("Year")." ".$year_start." ".$textnextyear, '', 'title_accountancy');

print $langs->trans("DescValidateMovements").'<br>';
print '<br>';


$y = $year_current;

print_barre_liste($langs->trans("SelectMonthAndValidate"), '', '', '', '', '', '', -1, '', '', 0, '', 'class="right"', 0, 1, 1);

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="oddeven">';
for ($i = 1; $i <= 12; $i++) {
	$j = $i + ($conf->global->SOCIETE_FISCAL_MONTH_START ? $conf->global->SOCIETE_FISCAL_MONTH_START : 1) - 1;
	if ($j > 12) $j -= 12;
	print '<td class="center">'.$langs->trans('MonthShort'.str_pad($j, 2, '0', STR_PAD_LEFT)).'</td>';
}
print '<td><b>'.$langs->trans("Total").'</b></td></tr>';

print '<tr class="oddeven">';
$sql = "SELECT COUNT(b.rowid) as detail,";
for ($i = 1; $i <= 12; $i++) {
	$j = $i + ($conf->global->SOCIETE_FISCAL_MONTH_START ? $conf->global->SOCIETE_FISCAL_MONTH_START : 1) - 1;
	if ($j > 12) $j -= 12;
	$sql .= "  SUM(".$db->ifsql('MONTH(b.doc_date)='.$j, '1', '0').") AS month".str_pad($j, 2, '0', STR_PAD_LEFT).",";
}
$sql .= " COUNT(b.rowid) as total";
$sql .= " FROM ".MAIN_DB_PREFIX."accounting_bookkeeping as b";
$sql .= " WHERE b.doc_date >= '".$db->idate($search_date_start)."'";
$sql .= " AND b.doc_date <= '".$db->idate($search_date_end)."'";
$sql .= " AND b.entity IN (".getEntity('bookkeeping', 0).")"; // We don't share object for accountancy

dol_syslog('htdocs/accountancy/closure/index.php sql='.$sql, LOG_DEBUG);
$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);

	while ($row = $db->fetch_row($resql)) {
		for ($i = 1; $i <= 12; $i++) {
			print '<td class="nowrap center">'.$row[$i].'<br><br>';
            print '<input id="cb'.$row[$i].'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$row[$i].'"'.($selected ? ' checked="checked"' : '').'>';
            print '</td>';
		}
		print '<td class="valigntop"><b>'.$row[13].'</b></td>';
	}

	$db->free($resql);
} else {
	print $db->lasterror(); // Show last sql error
}
print '</tr>';
print "</table>\n";

print '<br><div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER['PHP_SELF'].'?month='.$year_current.'&action=validate"">'.$langs->trans("ValidateMovements").'</a></div>';
print '</div>';

// End of page
llxFooter();
$db->close();
