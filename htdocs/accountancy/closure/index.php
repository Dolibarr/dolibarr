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
 * \file 	    htdocs/accountancy/closure/index.php
 * \ingroup     Accountancy
 * \brief 	    Home closure page
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/accounting.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formaccounting.class.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/bookkeeping.class.php';

// Load translation files required by the page
$langs->loadLangs(array("compta", "bills", "other", "main", "accountancy"));

$socid = GETPOST('socid', 'int');

$action = GETPOST('action', 'aZ09');

// Security check
if (empty($conf->accounting->enabled)) {
	accessforbidden();
}
if ($user->socid > 0)
	accessforbidden();
if (!$user->rights->accounting->fiscalyear->write)
	accessforbidden();

$object = new BookKeeping($db);

$month_start = ($conf->global->SOCIETE_FISCAL_MONTH_START ? ($conf->global->SOCIETE_FISCAL_MONTH_START) : 1);
if (GETPOST("year", 'int')) $year_start = GETPOST("year", 'int');
else {
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
if ($action == 'validate_movements_confirm' && $user->rights->accounting->fiscalyear->write) {
	$result = $object->fetchAll();

	if ($result < 0)
	{
		setEventMessages($object->error, $object->errors, 'errors');
	} else {
		// Specify as export : update field date_validated on selected month/year
		$error = 0;
		$db->begin();

		$date_start = dol_mktime(0, 0, 0, GETPOST('date_startmonth', 'int'), GETPOST('date_startday', 'int'), GETPOST('date_startyear', 'int'));
		$date_end = dol_mktime(23, 59, 59, GETPOST('date_endmonth', 'int'), GETPOST('date_endday', 'int'), GETPOST('date_endyear', 'int'));

		if (is_array($object->lines))
		{
			foreach ($object->lines as $movement)
			{
				$now = dol_now();

				$sql = " UPDATE ".MAIN_DB_PREFIX."accounting_bookkeeping";
				$sql .= " SET date_validated = '".$db->idate($now)."'";
				$sql .= " WHERE rowid = ".$movement->id;
				$sql .= " AND doc_date >= '" . dol_print_date($date_start, 'dayrfc') . "'";
                $sql .= " AND doc_date <= '" . dol_print_date($date_end, 'dayrfc') . "'";

				dol_syslog("/accountancy/closure/index.php :: Function validate_movement_confirm Specify movements as validated sql=".$sql, LOG_DEBUG);
				$result = $db->query($sql);
				if (!$result)
				{
					$error++;
					break;
				}
			}
		}

		if (!$error)
		{
			$db->commit();
			setEventMessages($langs->trans("AllMovementsWereRecordedAsValidated"), null, 'mesgs');
		} else {
			$error++;
			$db->rollback();
			setEventMessages($langs->trans("NotAllMovementsCouldBeRecordedAsValidated"), null, 'errors');
		}
		header("Location: ".$_SERVER['PHP_SELF']."?year=".$year_start);
		exit;
	}
}

/*
 * View
 */

$form = new Form($db);
$formaccounting = new FormAccounting($db);

llxHeader('', $langs->trans("Closure"));

if ($action == 'validate_movements') {
	$form_question = array();

	$month = isset($conf->global->SOCIETE_FISCAL_MONTH_START) ? intval($conf->global->SOCIETE_FISCAL_MONTH_START) : 1;
	$date_start = new DateTime(sprintf('%04d-%02d-%02d', $year_start, $month, 1));
	$date_end = new DateTime(sprintf('%04d-%02d-%02d', $year_start, $month, 1));
	$date_end->add(new DateInterval('P1Y'));
	$date_end->sub(new DateInterval('P1D'));

	$form_question['date_start'] = array(
		'name' => 'date_start',
		'type' => 'date',
		'label' => $langs->trans('DateStart'),
		'value' => $date_start->format('Y-m-d')
	);
	$form_question['date_end'] = array(
		'name' => 'date_end',
		'type' => 'date',
		'label' => $langs->trans('DateEnd'),
		'value' => $date_end->format('Y-m-d')
	);

	$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?year='.$year_start, $langs->trans('ValidateMovements'), $langs->trans('DescValidateMovements', $langs->transnoentitiesnoconv("RegistrationInAccounting")), 'validate_movements_confirm', $form_question, '', 1, 300);
	print $formconfirm;
}

$textprevyear = '<a href="'.$_SERVER["PHP_SELF"].'?year='.($year_current - 1).'">'.img_previous().'</a>';
$textnextyear = '&nbsp;<a href="'.$_SERVER["PHP_SELF"].'?year='.($year_current + 1).'">'.img_next().'</a>';


print load_fiche_titre($langs->trans("Closure")." ".$textprevyear." ".$langs->trans("Year")." ".$year_start." ".$textnextyear, '', 'title_accountancy');

print '<span class="opacitymedium">'.$langs->trans("DescClosure").'</span><br>';
print '<br>';


$y = $year_current;

$buttonvalidate = '<a class="butAction" name="button_validate_movements" href="'.$_SERVER["PHP_SELF"].'?action=validate_movements&year='.$year_start.'">'.$langs->trans("ValidateMovements").'</a>';

print_barre_liste($langs->trans("OverviewOfMovementsNotValidated"), '', '', '', '', '', '', -1, '', '', 0, $buttonvalidate, '', 0, 1, 1);

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
for ($i = 1; $i <= 12; $i++) {
	$j = $i + ($conf->global->SOCIETE_FISCAL_MONTH_START ? $conf->global->SOCIETE_FISCAL_MONTH_START : 1) - 1;
	if ($j > 12) $j -= 12;
	print '<td width="60" class="right">'.$langs->trans('MonthShort'.str_pad($j, 2, '0', STR_PAD_LEFT)).'</td>';
}
print '<td width="60" class="right"><b>'.$langs->trans("Total").'</b></td></tr>';

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
$sql .= " AND date_validated IS NULL";

dol_syslog('htdocs/accountancy/closure/index.php sql='.$sql, LOG_DEBUG);
$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);

	while ($row = $db->fetch_row($resql)) {
		print '<tr class="oddeven">';
		for ($i = 1; $i <= 12; $i++) {
			print '<td class="right">'.$row[$i].'</td>';
		}
		print '<td class="right"><b>'.$row[13].'</b></td>';
		print '</tr>';
	}

	$db->free($resql);
} else {
	print $db->lasterror(); // Show last sql error
}
print "</table>\n";
print '</div>';

// End of page
llxFooter();
$db->close();
