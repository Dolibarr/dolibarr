<?php
/* Copyright (C) 2020       Maxime Kohlhaas         <maxime@atm-consulting.fr>
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
 *	\file        htdocs/compta/stats/supplier_ca.php
 *	\brief       Page reporting purchase turnover
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/report.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

// Load translation files required by the page
$langs->loadLangs(array('compta', 'bills'));

$date_startday = GETPOST('date_startday', 'int');
$date_startmonth = GETPOST('date_startmonth', 'int');
$date_startyear = GETPOST('date_startyear', 'int');
$date_endday = GETPOST('date_endday', 'int');
$date_endmonth = GETPOST('date_endmonth', 'int');
$date_endyear = GETPOST('date_endyear', 'int');

$nbofyear = 4;

// Date range
$year = GETPOST('year', 'int');
if (empty($year))
{
	$year_current = strftime("%Y", dol_now());
	$month_current = strftime("%m", dol_now());
	$year_start = $year_current - ($nbofyear - 1);
} else {
	$year_current = $year;
	$month_current = strftime("%m", dol_now());
	$year_start = $year - ($nbofyear - 1);
}
$date_start = dol_mktime(0, 0, 0, $date_startmonth, $date_startday, $date_startyear);
$date_end = dol_mktime(23, 59, 59, $date_endmonth, $date_endday, $date_endyear);

// We define date_start and date_end
if (empty($date_start) || empty($date_end)) // We define date_start and date_end
{
	$q = GETPOST("q") ?GETPOST("q") : 0;
	if ($q == 0)
	{
		// We define date_start and date_end
		$year_end = $year_start + ($nbofyear - 1);
		$month_start = GETPOST("month") ?GETPOST("month") : ($conf->global->SOCIETE_FISCAL_MONTH_START ? ($conf->global->SOCIETE_FISCAL_MONTH_START) : 1);
		if (!GETPOST('month'))
		{
			if (!GETPOST("year") && $month_start > $month_current)
			{
				$year_start--;
				$year_end--;
			}
			$month_end = $month_start - 1;
			if ($month_end < 1) $month_end = 12;
		}
		else $month_end = $month_start;
		$date_start = dol_get_first_day($year_start, $month_start, false); $date_end = dol_get_last_day($year_end, $month_end, false);
	}
	if ($q == 1) { $date_start = dol_get_first_day($year_start, 1, false); $date_end = dol_get_last_day($year_start, 3, false); }
	if ($q == 2) { $date_start = dol_get_first_day($year_start, 4, false); $date_end = dol_get_last_day($year_start, 6, false); }
	if ($q == 3) { $date_start = dol_get_first_day($year_start, 7, false); $date_end = dol_get_last_day($year_start, 9, false); }
	if ($q == 4) { $date_start = dol_get_first_day($year_start, 10, false); $date_end = dol_get_last_day($year_start, 12, false); }
}

$userid = GETPOST('userid', 'int');
$socid = GETPOST('socid', 'int');

$tmps = dol_getdate($date_start);
$year_start = $tmps['year'];
$tmpe = dol_getdate($date_end);
$year_end = $tmpe['year'];
$nbofyear = ($year_end - $year_start) + 1;

// Define modecompta ('CREANCES-DETTES' or 'RECETTES-DEPENSES' or 'BOOKKEEPING')
$modecompta = $conf->global->ACCOUNTING_MODE;
if (!empty($conf->accounting->enabled)) $modecompta = 'BOOKKEEPING';
if (GETPOST("modecompta")) $modecompta = GETPOST("modecompta", 'alpha');

// Security check
if ($user->socid > 0) $socid = $user->socid;
if (!empty($conf->comptabilite->enabled)) $result = restrictedArea($user, 'compta', '', '', 'resultat');
if (!empty($conf->accounting->enabled)) $result = restrictedArea($user, 'accounting', '', '', 'comptarapport');




/*
 * View
 */

llxHeader();

$form = new Form($db);

// Affiche en-tete du rapport
if ($modecompta == "CREANCES-DETTES")
{
	$name = $langs->trans("PurchaseTurnover");
	$calcmode = $langs->trans("CalcModeDebt");
	//$calcmode.='<br>('.$langs->trans("SeeReportInInputOutputMode",'<a href="'.$_SERVER["PHP_SELF"].'?year_start='.$year_start.'&modecompta=RECETTES-DEPENSES">','</a>').')';
	$calcmode .= '<br>('.$langs->trans("SeeReportInBookkeepingMode", '<a href="'.$_SERVER["PHP_SELF"].'?year_start='.$year_start.'&modecompta=BOOKKEEPING">', '</a>').')';
	$period = $form->selectDate($date_start, 'date_start', 0, 0, 0, '', 1, 0).' - '.$form->selectDate($date_end, 'date_end', 0, 0, 0, '', 1, 0);
	$periodlink = ($year_start ? "<a href='".$_SERVER["PHP_SELF"]."?year=".($year_start + $nbofyear - 2)."&modecompta=".$modecompta."'>".img_previous()."</a> <a href='".$_SERVER["PHP_SELF"]."?year=".($year_start + $nbofyear)."&modecompta=".$modecompta."'>".img_next()."</a>" : "");
	$description = $langs->trans("RulesPurchaseTurnoverDue");
	$builddate = dol_now();
	//$exportlink=$langs->trans("NotYetAvailable");
}
elseif ($modecompta == "RECETTES-DEPENSES")
{
	$name = $langs->trans("PurchaseTurnoverCollected");
	$calcmode = $langs->trans("CalcModeEngagement");
	//$calcmode.='<br>('.$langs->trans("SeeReportInDueDebtMode",'<a href="'.$_SERVER["PHP_SELF"].'?year_start='.$year_start.'&modecompta=CREANCES-DETTES">','</a>').')';
	//$calcmode.='<br>('.$langs->trans("SeeReportInBookkeepingMode",'<a href="'.$_SERVER["PHP_SELF"].'?year_start='.$year_start.'&modecompta=BOOKKEEPINGCOLLECTED">','</a>').')';
	$period = $form->selectDate($date_start, 'date_start', 0, 0, 0, '', 1, 0).' - '.$form->selectDate($date_end, 'date_end', 0, 0, 0, '', 1, 0);
	$periodlink = ($year_start ? "<a href='".$_SERVER["PHP_SELF"]."?year=".($year_start + $nbofyear - 2)."&modecompta=".$modecompta."'>".img_previous()."</a> <a href='".$_SERVER["PHP_SELF"]."?year=".($year_start + $nbofyear)."&modecompta=".$modecompta."'>".img_next()."</a>" : "");
	$description = $langs->trans("RulesPurchaseTurnoverIn");
	$builddate = dol_now();
	//$exportlink=$langs->trans("NotYetAvailable");
}
elseif ($modecompta == "BOOKKEEPING")
{
	$name = $langs->trans("PurchaseTurnover");
	$calcmode = $langs->trans("CalcModeBookkeeping");
	$calcmode .= '<br>('.$langs->trans("SeeReportInDueDebtMode", '<a href="'.$_SERVER["PHP_SELF"].'?year_start='.$year_start.'&modecompta=CREANCES-DETTES">', '</a>').')';
	//$calcmode.='<br>('.$langs->trans("SeeReportInInputOutputMode",'<a href="'.$_SERVER["PHP_SELF"].'?year_start='.$year_start.'&modecompta=RECETTES-DEPENSES">','</a>').')';
	$period = $form->selectDate($date_start, 'date_start', 0, 0, 0, '', 1, 0).' - '.$form->selectDate($date_end, 'date_end', 0, 0, 0, '', 1, 0);
	$periodlink = ($year_start ? "<a href='".$_SERVER["PHP_SELF"]."?year=".($year_start + $nbofyear - 2)."&modecompta=".$modecompta."'>".img_previous()."</a> <a href='".$_SERVER["PHP_SELF"]."?year=".($year_start + $nbofyear)."&modecompta=".$modecompta."'>".img_next()."</a>" : "");
	$description = $langs->trans("RulesPurchaseTurnoverTotalPurchaseJournal");
	$builddate = dol_now();
	//$exportlink=$langs->trans("NotYetAvailable");
}

$moreparam = array();
if (!empty($modecompta)) $moreparam['modecompta'] = $modecompta;
report_header($name, $namelink, $period, $periodlink, $description, $builddate, $exportlink, $moreparam, $calcmode);

if (!empty($conf->accounting->enabled) && $modecompta != 'BOOKKEEPING')
{
    print info_admin($langs->trans("WarningReportNotReliable"), 0, 0, 1);
}


if ($modecompta == 'CREANCES-DETTES')
{
	$sql = "SELECT date_format(f.datef,'%Y-%m') as dm, sum(f.total_ht) as amount, sum(f.total_ttc) as amount_ttc";
	$sql .= " FROM ".MAIN_DB_PREFIX."facture_fourn as f";
	$sql .= " WHERE f.fk_statut in (1,2)";
	$sql .= " AND f.type IN (0,2)";
	$sql .= " AND f.entity IN (".getEntity('supplier_invoice').")";
	if ($socid) $sql .= " AND f.fk_soc = ".$socid;
}
elseif ($modecompta == "RECETTES-DEPENSES")
{
	$sql = "SELECT date_format(p.datep,'%Y-%m') as dm, sum(pf.amount) as amount_ttc";
	$sql .= " FROM ".MAIN_DB_PREFIX."facture_fourn as f";
	$sql .= ", ".MAIN_DB_PREFIX."paiementfourn_facturefourn as pf";
	$sql .= ", ".MAIN_DB_PREFIX."paiementfourn as p";
	$sql .= " WHERE p.rowid = pf.fk_paiementfourn";
	$sql .= " AND pf.fk_facturefourn = f.rowid";
	$sql .= " AND f.entity IN (".getEntity('supplier_invoice').")";
	if ($socid) $sql .= " AND f.fk_soc = ".$socid;
}
elseif ($modecompta == "BOOKKEEPING")
{
	$sql = "SELECT date_format(b.doc_date,'%Y-%m') as dm, sum(b.debit) as amount_ttc";
	$sql .= " FROM ".MAIN_DB_PREFIX."accounting_bookkeeping as b, ".MAIN_DB_PREFIX."accounting_journal as aj";
	$sql .= " WHERE b.entity = ".$conf->entity; // In module double party accounting, we never share entities
	$sql .= " AND aj.entity = ".$conf->entity;
	$sql .= " AND b.code_journal = aj.code AND aj.nature = 3"; // @todo currently count amount in sale journal, but we need to define a category group for turnover
}

$sql .= " GROUP BY dm";
$sql .= " ORDER BY dm";
// TODO Add a filter on $date_start and $date_end to reduce quantity on data
//print $sql;

$minyearmonth = $maxyearmonth = 0;

$result = $db->query($sql);
if ($result)
{
	$num = $db->num_rows($result);
	$i = 0;
	while ($i < $num)
	{
		$obj = $db->fetch_object($result);
		$cum_ht[$obj->dm] = !empty($obj->amount) ? $obj->amount : 0;
		$cum[$obj->dm] = $obj->amount_ttc;
		if ($obj->amount_ttc)
		{
			$minyearmonth = ($minyearmonth ? min($minyearmonth, $obj->dm) : $obj->dm);
			$maxyearmonth = max($maxyearmonth, $obj->dm);
		}
		$i++;
	}
	$db->free($result);
}
else {
	dol_print_error($db);
}

$moreforfilter = '';

print '<div class="div-table-responsive">';
print '<table class="tagtable liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

print '<tr class="liste_titre"><td>&nbsp;</td>';

for ($annee = $year_start; $annee <= $year_end; $annee++)
{
	if ($modecompta == 'CREANCES-DETTES') print '<td align="center" width="10%" colspan="3">';
	else print '<td align="center" width="10%" colspan="2" class="borderrightlight">';
	if ($modecompta != 'BOOKKEEPING') print '<a href="supplier_turnover_by_thirdparty.php?year='.$annee.($modecompta ? '&modecompta='.$modecompta : '').'">';
	print $annee;
    if ($conf->global->SOCIETE_FISCAL_MONTH_START > 1) print '-'.($annee + 1);
    if ($modecompta != 'BOOKKEEPING') print '</a>';
    print '</td>';
	if ($annee != $year_end) print '<td width="15">&nbsp;</td>';
}
print '</tr>';

print '<tr class="liste_titre"><td class="liste_titre">'.$langs->trans("Month").'</td>';
for ($annee = $year_start; $annee <= $year_end; $annee++)
{
	if ($modecompta == 'CREANCES-DETTES') print '<td class="liste_titre right">'.$langs->trans("AmountHT").'</td>';
	print '<td class="liste_titre right">'.$langs->trans("AmountTTC").'</td>';
	print '<td class="liste_titre right borderrightlight">'.$langs->trans("Delta").'</td>';
	if ($annee != $year_end) print '<td class="liste_titre" width="15">&nbsp;</td>';
}
print '</tr>';

$now_show_delta = 0;
$minyear = substr($minyearmonth, 0, 4);
$maxyear = substr($maxyearmonth, 0, 4);
$nowyear = strftime("%Y", dol_now());
$nowyearmonth = strftime("%Y-%m", dol_now());
$maxyearmonth = max($maxyearmonth, $nowyearmonth);
$now = dol_now();
$casenow = dol_print_date($now, "%Y-%m");

// Loop on each month
$nb_mois_decalage = $conf->global->SOCIETE_FISCAL_MONTH_START ? ($conf->global->SOCIETE_FISCAL_MONTH_START - 1) : 0;
for ($mois = 1 + $nb_mois_decalage; $mois <= 12 + $nb_mois_decalage; $mois++)
{
	$mois_modulo = $mois; // ajout
	if ($mois > 12) {$mois_modulo = $mois - 12; } // ajout

	if ($year_start == $year_end) {
		if ($mois > $date_endmonth && $year_end >= $date_endyear) {
			break;
		}
	}

	print '<tr class="oddeven">';

	// Month
	print "<td>".dol_print_date(dol_mktime(12, 0, 0, $mois_modulo, 1, 2000), "%B")."</td>";

	for ($annee = $year_start - 1; $annee <= $year_end; $annee++)	// We start one year before to have data to be able to make delta
	{
		$annee_decalage = $annee;
		if ($mois > 12) {$annee_decalage = $annee + 1; }
		$case = dol_print_date(dol_mktime(1, 1, 1, $mois_modulo, 1, $annee_decalage), "%Y-%m");
		$caseprev = dol_print_date(dol_mktime(1, 1, 1, $mois_modulo, 1, $annee_decalage - 1), "%Y-%m");

		if ($annee >= $year_start)	// We ignore $annee < $year_start, we loop on it to be able to make delta, nothing is output.
		{
			if ($modecompta == 'CREANCES-DETTES') {
				// Valeur CA du mois w/o VAT
				print '<td class="right">';
				if ($cum_ht[$case])
				{
					$now_show_delta = 1; // On a trouve le premier mois de la premiere annee generant du chiffre.
					print '<a href="supplier_turnover_by_thirdparty.php?year='.$annee_decalage.'&month='.$mois_modulo.($modecompta ? '&modecompta='.$modecompta : '').'">'.price($cum_ht[$case], 1).'</a>';
				}
				else
				{
					if ($minyearmonth < $case && $case <= max($maxyearmonth, $nowyearmonth)) { print '0'; }
					else { print '&nbsp;'; }
				}
				print "</td>";
			}

			// Valeur CA du mois
			print '<td class="right">';
			if ($cum[$case])
			{
				$now_show_delta = 1; // On a trouve le premier mois de la premiere annee generant du chiffre.
				if ($modecompta != 'BOOKKEEPING') print '<a href="supplier_turnover_by_thirdparty.php?year='.$annee_decalage.'&month='.$mois_modulo.($modecompta ? '&modecompta='.$modecompta : '').'">';
				print price($cum[$case], 1);
				if ($modecompta != 'BOOKKEEPING') print '</a>';
			}
			else
			{
				if ($minyearmonth < $case && $case <= max($maxyearmonth, $nowyearmonth)) { print '0'; }
				else { print '&nbsp;'; }
			}
			print "</td>";

			// Pourcentage du mois
			if ($annee_decalage > $minyear && $case <= $casenow)
			{
				if ($cum[$caseprev] && $cum[$case])
				{
					$percent = (round(($cum[$case] - $cum[$caseprev]) / $cum[$caseprev], 4) * 100);
					//print "X $cum[$case] - $cum[$caseprev] - $cum[$caseprev] - $percent X";
					print '<td class="borderrightlight right">'.($percent >= 0 ? "+$percent" : "$percent").'%</td>';
				}
				if ($cum[$caseprev] && !$cum[$case])
				{
					print '<td class="borderrightlight right">-100%</td>';
				}
				if (!$cum[$caseprev] && $cum[$case])
				{
					//print '<td class="right">+Inf%</td>';
					print '<td class="borderrightlight right">-</td>';
				}
				if (isset($cum[$caseprev]) && !$cum[$caseprev] && !$cum[$case])
				{
					print '<td class="borderrightlight right">+0%</td>';
				}
				if (!isset($cum[$caseprev]) && !$cum[$case])
				{
					print '<td class="borderrightlight right">-</td>';
				}
			}
			else
			{
				print '<td class="borderrightlight right">';
				if ($minyearmonth <= $case && $case <= $maxyearmonth) { print '-'; }
				else { print '&nbsp;'; }
				print '</td>';
			}

			if ($annee_decalage < $year_end || ($annee_decalage == $year_end && $mois > 12 && $annee < $year_end)) print '<td width="15">&nbsp;</td>';
		}

		$total_ht[$annee] += ((!empty($cum_ht[$case])) ? $cum_ht[$case] : 0);
		$total[$annee] += $cum[$case];
	}

	print '</tr>';
}

// Affiche total
print '<tr class="liste_total"><td>'.$langs->trans("Total").'</td>';
for ($annee = $year_start; $annee <= $year_end; $annee++)
{
	if ($modecompta == 'CREANCES-DETTES') {
		// Montant total HT
		if ($total_ht[$annee] || ($annee >= $minyear && $annee <= max($nowyear, $maxyear)))
		{
			print '<td class="nowrap right">'.($total_ht[$annee] ?price($total_ht[$annee]) : "0")."</td>";
		}
		else
		{
			print '<td>&nbsp;</td>';
		}
	}

	// Montant total
	if ($total[$annee] || ($annee >= $minyear && $annee <= max($nowyear, $maxyear)))
	{
		print '<td class="nowrap right">'.($total[$annee] ?price($total[$annee]) : "0")."</td>";
	}
	else
	{
		print '<td>&nbsp;</td>';
	}

	// Pourcentage total
	if ($annee > $minyear && $annee <= max($nowyear, $maxyear))
	{
		if ($total[$annee - 1] && $total[$annee]) {
			$percent = (round(($total[$annee] - $total[$annee - 1]) / $total[$annee - 1], 4) * 100);
			print '<td class="nowrap borderrightlight right">'.($percent >= 0 ? "+$percent" : "$percent").'%</td>';
		}
		if ($total[$annee - 1] && !$total[$annee])
		{
			print '<td class="borderrightlight right">-100%</td>';
		}
		if (!$total[$annee - 1] && $total[$annee])
		{
			print '<td class="borderrightlight right">+'.$langs->trans('Inf').'%</td>';
		}
		if (!$total[$annee - 1] && !$total[$annee])
		{
			print '<td class="borderrightlight right">+0%</td>';
		}
	}
	else
	{
		print '<td class="borderrightlight right">';
		if ($total[$annee] || ($minyear <= $annee && $annee <= max($nowyear, $maxyear))) { print '-'; }
		else { print '&nbsp;'; }
		print '</td>';
	}

	if ($annee != $year_end) print '<td width="15">&nbsp;</td>';
}
print "</tr>\n";
print "</table>";
print '</div>';

// End of page
llxFooter();
$db->close();
