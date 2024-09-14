<?php
/* Copyright (C) 2001-2004  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2017       Olivier Geffroy         <jeff@jeffinfo.com>
 * Copyright (C) 2018-2024	Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2024       Benjamin B.             <b.crozon@trebisol.com>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 *	\file        htdocs/compta/stats/index.php
 *	\brief       Page reporting sell turnover
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/report.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

// Load translation files required by the page
$langs->loadLangs(array('compta', 'bills', 'donation', 'salaries'));

$date_startday = GETPOSTINT('date_startday');
$date_startmonth = GETPOSTINT('date_startmonth');
$date_startyear = GETPOSTINT('date_startyear');
$date_endday = GETPOSTINT('date_endday');
$date_endmonth = GETPOSTINT('date_endmonth');
$date_endyear = GETPOSTINT('date_endyear');

$nbofyear = 4;

// Date range
$year = GETPOSTINT('year');
if (empty($year)) {
	$year_current = (int) dol_print_date(dol_now(), "%Y");
	$month_current = (int) dol_print_date(dol_now(), "%m");
	$year_start = $year_current - ($nbofyear - 1);
} else {
	$year_current = $year;
	$month_current = (int) dol_print_date(dol_now(), "%m");
	$year_start = $year - $nbofyear + (getDolGlobalInt('SOCIETE_FISCAL_MONTH_START') > 1 ? 0 : 1);
}
$date_start = dol_mktime(0, 0, 0, $date_startmonth, $date_startday, $date_startyear, 'tzserver');	// We use timezone of server so report is same from everywhere
$date_end = dol_mktime(23, 59, 59, $date_endmonth, $date_endday, $date_endyear, 'tzserver');		// We use timezone of server so report is same from everywhere

// We define date_start and date_end
if (empty($date_start) || empty($date_end)) { // We define date_start and date_end
	$q = GETPOSTINT("q");
	if (empty($q)) {
		// We define date_start and date_end
		$year_end = $year_start + $nbofyear - (getDolGlobalInt('SOCIETE_FISCAL_MONTH_START') > 1 ? 0 : 1);
		$month_start = GETPOSTISSET("month") ? GETPOSTINT("month") : getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1);
		if (!GETPOST('month')) {	// If month not forced
			if (!$year && $month_start > $month_current) {
				$year_start--;
				$year_end--;
			}
			$month_end = $month_start - 1;
			if ($month_end < 1) {
				$month_end = 12;
			}
		} else {
			$month_end = $month_start;
		}
		$date_start = dol_get_first_day($year_start, $month_start, false);
		$date_end = dol_get_last_day($year_end, $month_end, false);
	}
	if ($q == 1) {
		$date_start = dol_get_first_day($year_start, 1, false);
		$date_end = dol_get_last_day($year_start, 3, false);
	}
	if ($q == 2) {
		$date_start = dol_get_first_day($year_start, 4, false);
		$date_end = dol_get_last_day($year_start, 6, false);
	}
	if ($q == 3) {
		$date_start = dol_get_first_day($year_start, 7, false);
		$date_end = dol_get_last_day($year_start, 9, false);
	}
	if ($q == 4) {
		$date_start = dol_get_first_day($year_start, 10, false);
		$date_end = dol_get_last_day($year_start, 12, false);
	}
}

$userid = GETPOSTINT('userid');
$socid = GETPOSTINT('socid');

$tmps = dol_getdate($date_start);
$month_start = $tmps['mon'];
$year_start = $tmps['year'];
$tmpe = dol_getdate($date_end);
$month_end = $tmpe['mon'];
$year_end = $tmpe['year'];
$nbofyear = ($year_end - $year_start) + 1;

// Define modecompta ('CREANCES-DETTES' or 'RECETTES-DEPENSES' or 'BOOKKEEPING')
$modecompta = getDolGlobalString('ACCOUNTING_MODE');
if (isModEnabled('accounting')) {
	$modecompta = 'BOOKKEEPING';
}
if (GETPOST("modecompta", 'alpha')) {
	$modecompta = GETPOST("modecompta", 'alpha');
}

// Security check
if ($user->socid > 0) {
	$socid = $user->socid;
}
if (isModEnabled('comptabilite')) {
	$result = restrictedArea($user, 'compta', '', '', 'resultat');
}
if (isModEnabled('accounting')) {
	$result = restrictedArea($user, 'accounting', '', '', 'comptarapport');
}




/*
 * View
 */

$param = '';
if ($date_startday && $date_startmonth && $date_startyear) {
	$param .= '&date_startday='.$date_startday.'&date_startmonth='.$date_startmonth.'&date_startyear='.$date_startyear;
}
if ($date_endday && $date_endmonth && $date_endyear) {
	$param .= '&date_endday='.$date_endday.'&date_endmonth='.$date_endmonth.'&date_endyear='.$date_endyear;
}

llxHeader();

$form = new Form($db);

$exportlink = '';
$namelink = '';
$builddate = dol_now();

// Affiche en-tete du rapport
if ($modecompta == "CREANCES-DETTES") {
	$name = $langs->trans("Turnover");
	$periodlink = ($year_start ? "<a href='".$_SERVER["PHP_SELF"]."?year=".($year_start + $nbofyear - 2)."&modecompta=".$modecompta."'>".img_previous()."</a> <a href='".$_SERVER["PHP_SELF"]."?year=".($year_start + $nbofyear)."&modecompta=".$modecompta."'>".img_next()."</a>" : "");
	$description = $langs->trans("RulesCADue");
	if (getDolGlobalString('FACTURE_DEPOSITS_ARE_JUST_PAYMENTS')) {
		$description .= $langs->trans("DepositsAreNotIncluded");
	} else {
		$description .= $langs->trans("DepositsAreIncluded");
	}
	//$exportlink=$langs->trans("NotYetAvailable");
} elseif ($modecompta == "RECETTES-DEPENSES") {
	$name = $langs->trans("TurnoverCollected");
	$periodlink = ($year_start ? "<a href='".$_SERVER["PHP_SELF"]."?year=".($year_start + $nbofyear - 2)."&modecompta=".$modecompta."'>".img_previous()."</a> <a href='".$_SERVER["PHP_SELF"]."?year=".($year_start + $nbofyear)."&modecompta=".$modecompta."'>".img_next()."</a>" : "");
	$description = $langs->trans("RulesCAIn");
	$description .= $langs->trans("DepositsAreIncluded");
	//$exportlink=$langs->trans("NotYetAvailable");
} elseif ($modecompta == "BOOKKEEPING") {
	$name = $langs->trans("Turnover");
	$periodlink = ($year_start ? "<a href='".$_SERVER["PHP_SELF"]."?year=".($year_start + $nbofyear - 2)."&modecompta=".$modecompta."'>".img_previous()."</a> <a href='".$_SERVER["PHP_SELF"]."?year=".($year_start + $nbofyear)."&modecompta=".$modecompta."'>".img_next()."</a>" : "");
	$description = $langs->trans("RulesSalesTurnoverOfIncomeAccounts");
	//$exportlink=$langs->trans("NotYetAvailable");
}
$period = $form->selectDate($date_start, 'date_start', 0, 0, 0, '', 1, 0, 0, '', '', '', '', 1, '', '', 'tzserver');
$period .= ' - ';
$period .= $form->selectDate($date_end, 'date_end', 0, 0, 0, '', 1, 0, 0, '', '', '', '', 1, '', '', 'tzserver');

$moreparam = array();
if (!empty($modecompta)) {
	$moreparam['modecompta'] = $modecompta;
}

// Define $calcmode line
$calcmode = '';
if ($modecompta == "RECETTES-DEPENSES" || $modecompta == "BOOKKEEPINGCOLLECTED") {
	/*if (isModEnabled('accounting')) {
		$calcmode .= '<input type="radio" name="modecompta" id="modecompta3" value="BOOKKEEPINGCOLLECTED"'.($modecompta == 'BOOKKEEPINGCOLLECTED' ? ' checked="checked"' : '').'><label for="modecompta3"> '.$langs->trans("CalcModeBookkeeping").'</label>';
		$calcmode .= '<br>';
	}*/
	$calcmode .= '<input type="radio" name="modecompta" id="modecompta2" value="RECETTES-DEPENSES"'.($modecompta == 'RECETTES-DEPENSES' ? ' checked="checked"' : '').'><label for="modecompta2"> '.$langs->trans("CalcModePayment");
	if (isModEnabled('accounting')) {
		$calcmode .= ' <span class="opacitymedium hideonsmartphone">('.$langs->trans("CalcModeNoBookKeeping").')</span>';
	}
	$calcmode .= '</label>';
} else {
	if (isModEnabled('accounting')) {
		$calcmode .= '<input type="radio" name="modecompta" id="modecompta3" value="BOOKKEEPING"'.($modecompta == 'BOOKKEEPING' ? ' checked="checked"' : '').'><label for="modecompta3"> '.$langs->trans("CalcModeBookkeeping").'</label>';
		$calcmode .= ' <span class="opacitymedium hideonsmartphone">('.$langs->trans("DataMustHaveBeenTransferredInAccounting").')</span>';
		$calcmode .= '<br>';
	}
	$calcmode .= '<input type="radio" name="modecompta" id="modecompta2" value="CREANCES-DETTES"'.($modecompta == 'CREANCES-DETTES' ? ' checked="checked"' : '').'><label for="modecompta2"> '.$langs->trans("CalcModeDebt");
	if (isModEnabled('accounting')) {
		$calcmode .= ' <span class="opacitymedium hideonsmartphone">('.$langs->trans("CalcModeNoBookKeeping").')</span>';
	}
	$calcmode .= '</label>';
}

report_header($name, $namelink, $period, $periodlink, $description, $builddate, $exportlink, $moreparam, $calcmode);

if (isModEnabled('accounting')) {
	if ($modecompta != 'BOOKKEEPING') {
		print info_admin($langs->trans("WarningReportNotReliable"), 0, 0, '1');
	} else {
		// Test if there is at least one line in bookkeeping
		$pcgverid = getDolGlobalString('CHARTOFACCOUNTS');
		$pcgvercode = dol_getIdFromCode($db, $pcgverid, 'accounting_system', 'rowid', 'pcg_version');
		if (empty($pcgvercode)) {
			$pcgvercode = $pcgverid;
		}

		$sql = "SELECT b.rowid ";
		$sql .= " FROM ".MAIN_DB_PREFIX."accounting_bookkeeping as b,";
		$sql .= " ".MAIN_DB_PREFIX."accounting_account as aa";
		$sql .= " WHERE b.entity = ".$conf->entity; // In module double party accounting, we never share entities
		$sql .= " AND b.numero_compte = aa.account_number";
		$sql .= " AND aa.entity = ".$conf->entity;
		$sql .= " AND aa.fk_pcg_version = '".$db->escape($pcgvercode)."'"; // fk_pcg_version is varchar(32)
		$sql .= $db->plimit(1);

		$resql = $db->query($sql);
		$nb = $db->num_rows($resql);
		if ($nb == 0) {
			$langs->load("errors");
			print info_admin($langs->trans("WarningNoDataTransferedInAccountancyYet"), 0, 0, '1');
		}
	}
}


if ($modecompta == 'CREANCES-DETTES') {
	$sql = "SELECT date_format(f.datef,'%Y-%m') as dm, sum(f.total_ht) as amount, sum(f.total_ttc) as amount_ttc";
	$sql .= " FROM ".MAIN_DB_PREFIX."facture as f";
	$sql .= " WHERE f.fk_statut in (1,2)";
	if (getDolGlobalString('FACTURE_DEPOSITS_ARE_JUST_PAYMENTS')) {
		$sql .= " AND f.type IN (0,1,2,5)";
	} else {
		$sql .= " AND f.type IN (0,1,2,3,5)";
	}
	$sql .= " AND f.entity IN (".getEntity('invoice').")";
	if ($socid) {
		$sql .= " AND f.fk_soc = ".((int) $socid);
	}
} elseif ($modecompta == "RECETTES-DEPENSES") {
	/*
	 * Liste des paiements (les anciens paiements ne sont pas vus par cette requete car, sur les
	 * vieilles versions, ils n'etaient pas lies via paiement_facture. On les ajoute plus loin)
	 */
	$sql = "SELECT date_format(p.datep, '%Y-%m') as dm, sum(pf.amount) as amount_ttc";
	$sql .= " FROM ".MAIN_DB_PREFIX."facture as f";
	$sql .= ", ".MAIN_DB_PREFIX."paiement_facture as pf";
	$sql .= ", ".MAIN_DB_PREFIX."paiement as p";
	$sql .= " WHERE p.rowid = pf.fk_paiement";
	$sql .= " AND pf.fk_facture = f.rowid";
	$sql .= " AND f.entity IN (".getEntity('invoice').")";
	if ($socid) {
		$sql .= " AND f.fk_soc = ".((int) $socid);
	}
} elseif ($modecompta == "BOOKKEEPING") {
	$pcgverid = getDolGlobalString('CHARTOFACCOUNTS');
	$pcgvercode = dol_getIdFromCode($db, $pcgverid, 'accounting_system', 'rowid', 'pcg_version');
	if (empty($pcgvercode)) {
		$pcgvercode = $pcgverid;
	}

	$sql = "SELECT date_format(b.doc_date, '%Y-%m') as dm, sum(b.credit - b.debit) as amount_ttc";
	$sql .= " FROM ".MAIN_DB_PREFIX."accounting_bookkeeping as b,";
	$sql .= " ".MAIN_DB_PREFIX."accounting_account as aa";
	$sql .= " WHERE b.entity = ".$conf->entity; // In module double party accounting, we never share entities
	$sql .= " AND b.numero_compte = aa.account_number";
	$sql .= " AND b.doc_type = 'customer_invoice'";
	$sql .= " AND aa.entity = ".$conf->entity;
	$sql .= " AND aa.fk_pcg_version = '".$db->escape($pcgvercode)."'"; // fk_pcg_version is varchar(32)
	$sql .= " AND aa.pcg_type = 'INCOME'";		// TODO Be able to use a custom group
}
$sql .= " GROUP BY dm";
$sql .= " ORDER BY dm";
// TODO Add a filter on $date_start and $date_end to reduce quantity on data
//print $sql;

$minyearmonth = $maxyearmonth = 0;

$cumulative = array();
$cumulative_ht = array();
$total_ht = array();
$total = array();

$result = $db->query($sql);
if ($result) {
	$num = $db->num_rows($result);
	$i = 0;
	while ($i < $num) {
		$obj = $db->fetch_object($result);
		$cumulative_ht[$obj->dm] = empty($obj->amount) ? 0 : $obj->amount;
		$cumulative[$obj->dm] = empty($obj->amount_ttc) ? 0 : $obj->amount_ttc;
		if ($obj->amount_ttc) {
			$minyearmonth = ($minyearmonth ? min($minyearmonth, $obj->dm) : $obj->dm);
			$maxyearmonth = max($maxyearmonth, $obj->dm);
		}
		$i++;
	}
	$db->free($result);
} else {
	dol_print_error($db);
}

// On ajoute les paiements anciennes version, non lies par paiement_facture (very old versions)
if ($modecompta == 'RECETTES-DEPENSES') {
	$sql = "SELECT date_format(p.datep,'%Y-%m') as dm, sum(p.amount) as amount_ttc";
	$sql .= " FROM ".MAIN_DB_PREFIX."bank as b";
	$sql .= ", ".MAIN_DB_PREFIX."bank_account as ba";
	$sql .= ", ".MAIN_DB_PREFIX."paiement as p";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."paiement_facture as pf ON p.rowid = pf.fk_paiement";
	$sql .= " WHERE pf.rowid IS NULL";
	$sql .= " AND p.fk_bank = b.rowid";
	$sql .= " AND b.fk_account = ba.rowid";
	$sql .= " AND ba.entity IN (".getEntity('bank_account').")";
	$sql .= " GROUP BY dm";
	$sql .= " ORDER BY dm";

	$result = $db->query($sql);
	if ($result) {
		$num = $db->num_rows($result);
		$i = 0;
		while ($i < $num) {
			$obj = $db->fetch_object($result);
			if (empty($cumulative[$obj->dm])) {
				$cumulative[$obj->dm] = $obj->amount_ttc;
			} else {
				$cumulative[$obj->dm] += $obj->amount_ttc;
			}
			if ($obj->amount_ttc) {
				$minyearmonth = ($minyearmonth ? min($minyearmonth, $obj->dm) : $obj->dm);
				$maxyearmonth = max($maxyearmonth, $obj->dm);
			}
			$i++;
		}
	} else {
		dol_print_error($db);
	}
}

$moreforfilter = '';

print '<div class="div-table-responsive">';
print '<table class="tagtable liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

print '<tr class="liste_titre"><td>&nbsp;</td>';

for ($annee = $year_start; $annee <= $year_end; $annee++) {
	if ($modecompta == 'CREANCES-DETTES') {
		print '<td align="center" width="10%" colspan="3">';
	} else {
		print '<td align="center" width="10%" colspan="2" class="borderrightlight">';
	}
	if ($modecompta != 'BOOKKEEPING') {
		print '<a href="casoc.php?year='.$annee.($modecompta ? '&modecompta='.$modecompta : '').'">';
	}
	print $annee;
	if (getDolGlobalInt('SOCIETE_FISCAL_MONTH_START') > 1) {
		print '-'.($annee + 1);
	}
	if ($modecompta != 'BOOKKEEPING') {
		print '</a>';
	}
	print '</td>';
	if ($annee != $year_end) {
		print '<td width="15">&nbsp;</td>';
	}
}
print '</tr>';

print '<tr class="liste_titre"><td class="liste_titre">'.$langs->trans("Month").'</td>';
for ($annee = $year_start; $annee <= $year_end; $annee++) {
	if ($modecompta == 'CREANCES-DETTES') {
		print '<td class="liste_titre right">'.$langs->trans("AmountHT").'</td>';
	}
	print '<td class="liste_titre right">';
	if ($modecompta == "BOOKKEEPING") {
		print $langs->trans("Amount");
	} else {
		print $langs->trans("AmountTTC");
	}
	print '</td>';
	print '<td class="liste_titre right borderrightlight">'.$langs->trans("Delta").'</td>';
	if ($annee != $year_end) {
		print '<td class="liste_titre" width="15">&nbsp;</td>';
	}
}
print '</tr>';

$now_show_delta = 0;
$minyear = substr($minyearmonth, 0, 4);
$maxyear = substr($maxyearmonth, 0, 4);
$nowyear = dol_print_date(dol_now('gmt'), "%Y", 'gmt');
$nowyearmonth = dol_print_date(dol_now(), "%Y%m");
$maxyearmonth = max($maxyearmonth, $nowyearmonth);
$now = dol_now();
$casenow = dol_print_date($now, "%Y-%m");

// Loop on each month
$nb_mois_decalage = GETPOSTISSET('date_startmonth') ? (GETPOSTINT('date_startmonth') - 1) : (!getDolGlobalInt('SOCIETE_FISCAL_MONTH_START') ? 0 : (getDolGlobalInt('SOCIETE_FISCAL_MONTH_START') - 1));
for ($mois = 1 + $nb_mois_decalage; $mois <= 12 + $nb_mois_decalage; $mois++) {
	$mois_modulo = $mois; // ajout
	if ($mois > 12) {
		$mois_modulo = $mois - 12;
	} // ajout

	if ($year_start == $year_end) {
		// If we show only one year or one month, we do not show month before the selected month
		if ($mois < $date_startmonth && $year_start <= $date_startyear) {
			continue;
		}
		// If we show only one year or one month, we do not show month after the selected month
		if ($mois > $date_endmonth && $year_end >= $date_endyear) {
			break;
		}
	}

	print '<tr class="oddeven">';

	// Month
	print "<td>".dol_print_date(dol_mktime(12, 0, 0, $mois_modulo, 1, 2000), "%B")."</td>";

	for ($annee = $year_start - 1; $annee <= $year_end; $annee++) {	// We start one year before to have data to be able to make delta
		$annee_decalage = $annee;
		if ($mois > 12) {
			$annee_decalage = $annee + 1;
		}
		$case = dol_print_date(dol_mktime(1, 1, 1, $mois_modulo, 1, $annee_decalage), "%Y-%m");
		$caseprev = dol_print_date(dol_mktime(1, 1, 1, $mois_modulo, 1, $annee_decalage - 1), "%Y-%m");

		if ($annee >= $year_start) {	// We ignore $annee < $year_start, we loop on it to be able to make delta, nothing is output.
			if ($modecompta == 'CREANCES-DETTES') {
				// Value turnover of month w/o VAT
				print '<td class="right">';
				if ($annee < $year_end || ($annee == $year_end && $mois <= $month_end)) {
					if (!empty($cumulative_ht[$case])) {
						$now_show_delta = 1; // On a trouve le premier mois de la premiere annee generant du chiffre.
						print '<a href="casoc.php?year='.$annee_decalage.'&month='.$mois_modulo.($modecompta ? '&modecompta='.$modecompta : '').'">'.price($cumulative_ht[$case], 1).'</a>';
					} else {
						if ($minyearmonth < $case && $case <= max($maxyearmonth, $nowyearmonth)) {
							print '0';
						} else {
							print '&nbsp;';
						}
					}
				}
				print "</td>";
			}

			// Value turnover of month
			print '<td class="right">';
			if ($annee < $year_end || ($annee == $year_end && $mois <= $month_end)) {
				if (!empty($cumulative[$case])) {
					$now_show_delta = 1; // On a trouve le premier mois de la premiere annee generant du chiffre.
					if ($modecompta != 'BOOKKEEPING') {
						print '<a href="casoc.php?year='.$annee_decalage.'&month='.$mois_modulo.($modecompta ? '&modecompta='.$modecompta : '').'">';
					}
					print price($cumulative[$case], 1);
					if ($modecompta != 'BOOKKEEPING') {
						print '</a>';
					}
				} else {
					if ($minyearmonth < $case && $case <= max($maxyearmonth, $nowyearmonth)) {
						print '0';
					} else {
						print '&nbsp;';
					}
				}
			}
			print "</td>";

			// Percentage of month
			print '<td class="borderrightlight right"><span class="opacitymedium">';
			//var_dump($annee.' '.$year_end.' '.$mois.' '.$month_end);
			if ($annee < $year_end || ($annee == $year_end && $mois <= $month_end)) {
				if ($annee_decalage > $minyear && $case <= $casenow) {
					if (!empty($cumulative[$caseprev]) && !empty($cumulative[$case])) {
						$percent = (round(($cumulative[$case] - $cumulative[$caseprev]) / $cumulative[$caseprev], 4) * 100);
						//print "X $cumulative[$case] - $cumulative[$caseprev] - $cumulative[$caseprev] - $percent X";
						print($percent >= 0 ? "+$percent" : "$percent").'%';
					}
					if (!empty($cumulative[$caseprev]) && empty($cumulative[$case])) {
						print '-100%';
					}
					if (empty($cumulative[$caseprev]) && !empty($cumulative[$case])) {
						//print '<td class="right">+Inf%</td>';
						print '-';
					}
					if (isset($cumulative[$caseprev]) && empty($cumulative[$caseprev]) && empty($cumulative[$case])) {
						print '+0%';
					}
					if (!isset($cumulative[$caseprev]) && empty($cumulative[$case])) {
						print '-';
					}
				} else {
					if ($minyearmonth <= $case && $case <= $maxyearmonth) {
						print '-';
					} else {
						print '&nbsp;';
					}
				}
			}
			print '</span></td>';

			if ($annee_decalage < $year_end || ($annee_decalage == $year_end && $mois > 12 && $annee < $year_end)) {
				print '<td width="15">&nbsp;</td>';
			}
		}

		if ($annee < $year_end || ($annee == $year_end && $mois <= $month_end)) {
			if (empty($total_ht[$annee])) {
				$total_ht[$annee] = (empty($cumulative_ht[$case]) ? 0 : $cumulative_ht[$case]);
			} else {
				$total_ht[$annee] += (empty($cumulative_ht[$case]) ? 0 : $cumulative_ht[$case]);
			}
			if (empty($total[$annee])) {
				$total[$annee] = empty($cumulative[$case]) ? 0 : $cumulative[$case];
			} else {
				$total[$annee] += empty($cumulative[$case]) ? 0 : $cumulative[$case];
			}
		}
	}

	print '</tr>';
}

/*
 for ($mois = 1 ; $mois < 13 ; $mois++)
 {

 print '<tr class="oddeven">';

 print "<td>".dol_print_date(dol_mktime(12,0,0,$mois,1,2000),"%B")."</td>";
 for ($annee = $year_start ; $annee <= $year_end ; $annee++)
 {
 $casenow = dol_print_date(dol_now(),"%Y-%m");
 $case = dol_print_date(dol_mktime(1,1,1,$mois,1,$annee),"%Y-%m");
 $caseprev = dol_print_date(dol_mktime(1,1,1,$mois,1,$annee-1),"%Y-%m");

 // Valeur CA du mois
 print '<td class="right">';
 if ($cumulative[$case])
 {
 $now_show_delta=1;  // On a trouve le premier mois de la premiere annee generant du chiffre.
 print '<a href="casoc.php?year='.$annee.'&month='.$mois.'">'.price($cumulative[$case],1).'</a>';
 }
 else
 {
 if ($minyearmonth < $case && $case <= max($maxyearmonth,$nowyearmonth)) { print '0'; }
 else { print '&nbsp;'; }
 }
 print "</td>";

 // Pourcentage du mois
 if ($annee > $minyear && $case <= $casenow) {
 if ($cumulative[$caseprev] && $cumulative[$case])
 {
 $percent=(round(($cumulative[$case]-$cumulative[$caseprev])/$cumulative[$caseprev],4)*100);
 //print "X $cumulative[$case] - $cumulative[$caseprev] - $cumulative[$caseprev] - $percent X";
 print '<td class="right">'.($percent>=0?"+$percent":"$percent").'%</td>';

 }
 if ($cumulative[$caseprev] && ! $cumulative[$case])
 {
 print '<td class="right">-100%</td>';
 }
 if (! $cumulative[$caseprev] && $cumulative[$case])
 {
 print '<td class="right">+Inf%</td>';
 }
 if (! $cumulative[$caseprev] && ! $cumulative[$case])
 {
 print '<td class="right">+0%</td>';
 }
 }
 else
 {
 print '<td class="right">';
 if ($minyearmonth <= $case && $case <= $maxyearmonth) { print '-'; }
 else { print '&nbsp;'; }
 print '</td>';
 }

 $total[$annee]+=$cumulative[$case];
 if ($annee != $year_end) print '<td width="15">&nbsp;</td>';
 }

 print '</tr>';
 }
 */

// Show total
print '<tr class="liste_total"><td>'.$langs->trans("Total").'</td>';
for ($annee = $year_start; $annee <= $year_end; $annee++) {
	if ($modecompta == 'CREANCES-DETTES') {
		// Montant total HT
		if ($total_ht[$annee] || ($annee >= $minyear && $annee <= max($nowyear, $maxyear))) {
			print '<td class="nowrap right">';
			print(empty($total_ht[$annee]) ? '0' : price($total_ht[$annee]));
			print "</td>";
		} else {
			print '<td>&nbsp;</td>';
		}
	}

	// Total amount
	if (!empty($total[$annee]) || ($annee >= $minyear && $annee <= max($nowyear, $maxyear))) {
		print '<td class="nowrap right">';
		print(empty($total[$annee]) ? '0' : price($total[$annee]));
		print "</td>";
	} else {
		print '<td>&nbsp;</td>';
	}

	// Pourcentage total
	if ($annee > $minyear && $annee <= max($nowyear, $maxyear)) {
		if (!empty($total[$annee - 1]) && !empty($total[$annee])) {
			$percent = (round(($total[$annee] - $total[$annee - 1]) / $total[$annee - 1], 4) * 100);
			print '<td class="nowrap borderrightlight right">';
			print($percent >= 0 ? "+$percent" : "$percent").'%';
			print '</td>';
		}
		if (!empty($total[$annee - 1]) && empty($total[$annee])) {
			print '<td class="borderrightlight right">-100%</td>';
		}
		if (empty($total[$annee - 1]) && !empty($total[$annee])) {
			print '<td class="borderrightlight right">+'.$langs->trans('Inf').'%</td>';
		}
		if (empty($total[$annee - 1]) && empty($total[$annee])) {
			print '<td class="borderrightlight right">+0%</td>';
		}
	} else {
		print '<td class="borderrightlight right">';
		if (!empty($total[$annee]) || ($minyear <= $annee && $annee <= max($nowyear, $maxyear))) {
			print '-';
		} else {
			print '&nbsp;';
		}
		print '</td>';
	}

	if ($annee != $year_end) {
		print '<td width="15">&nbsp;</td>';
	}
}
print "</tr>\n";
print "</table>";
print '</div>';


/*
 * En mode recettes/depenses, on complete avec les montants factures non regles
 * et les propales signees mais pas facturees. En effet, en recettes-depenses,
 * on comptabilise lorsque le montant est sur le compte donc il est interessant
 * d'avoir une vision de ce qui va arriver.
 */

/*
 Je commente toute cette partie car les chiffres affichees sont faux - Eldy.
 En attendant correction.

 if ($modecompta != 'CREANCES-DETTES')
 {

 print '<br><table width="100%" class="noborder">';

 // Unpaid invoices
 // There is a bug here.  We need to use the remaining to pay and not the total of unpaid invoices!

 $sql = "SELECT f.ref, f.rowid, s.nom, s.rowid as socid, f.total_ttc, sum(pf.amount) as am";
 $sql .= " FROM ".MAIN_DB_PREFIX."societe as s,".MAIN_DB_PREFIX."facture as f left join ".MAIN_DB_PREFIX."paiement_facture as pf on f.rowid=pf.fk_facture";
 $sql .= " WHERE s.rowid = f.fk_soc AND f.paye = 0 AND f.fk_statut = 1";
 if ($socid)
 {
 $sql .= " AND f.fk_soc = $socid";
 }
 $sql .= " GROUP BY f.ref,f.rowid,s.nom, s.rowid, f.total_ttc";

 $resql=$db->query($sql);
 if ($resql)
 {
 $num = $db->num_rows($resql);
 $i = 0;

 if ($num)
 {
 $total_ttc_Rac = $totalam_Rac = $total_Rac = 0;
 while ($i < $num)
 {
 $obj = $db->fetch_object($resql);
 $total_ttc_Rac +=  $obj->total_ttc;
 $totalam_Rac +=  $obj->am;
 $i++;
 }

 print "<tr class="oddeven"><td class=\"right\" colspan=\"5\"><i>Facture a encaisser : </i></td><td class=\"right\"><i>".price($total_ttc_Rac)."</i></td><td colspan=\"5\"><-- bug ici car n'exclut pas le deja r?gl? des factures partiellement r?gl?es</td></tr>";
 }
 $db->free($resql);
 }
 else
 {
 dol_print_error($db);
 }
 */

/*
 *
 * Propales signees, et non facturees
 *
 */

/*
 Je commente toute cette partie car les chiffres affichees sont faux - Eldy.
 En attendant correction.

 $sql = "SELECT sum(f.total_ht) as tot_fht,sum(f.total_ttc) as tot_fttc, p.rowid, p.ref, s.nom, s.rowid as socid, p.total_ht, p.total_ttc
 FROM ".MAIN_DB_PREFIX."commande AS p, ".MAIN_DB_PREFIX."societe AS s
 LEFT JOIN ".MAIN_DB_PREFIX."co_fa AS co_fa ON co_fa.fk_commande = p.rowid
 LEFT JOIN ".MAIN_DB_PREFIX."facture AS f ON co_fa.fk_facture = f.rowid
 WHERE p.fk_soc = s.rowid
 AND p.fk_statut >=1
 AND p.facture =0";
 if ($socid)
 {
 $sql .= " AND f.fk_soc = ".((int) $socid);
 }
 $sql .= " GROUP BY p.rowid";

 $resql=$db->query($sql);
 if ($resql)
 {
 $num = $db->num_rows($resql);
 $i = 0;

 if ($num)
 {
 $total_pr = 0;
 while ($i < $num)
 {
 $obj = $db->fetch_object($resql);
 $total_pr +=  $obj->total_ttc-$obj->tot_fttc;
 $i++;
 }

 print "<tr class="oddeven"><td class=\"right\" colspan=\"5\"><i>Signe et non facture:</i></td><td class=\"right\"><i>".price($total_pr)."</i></td><td colspan=\"5\"><-- bug ici, ca devrait exclure le deja facture</td></tr>";
 }
 $db->free($resql);
 }
 else
 {
 dol_print_error($db);
 }
 print "<tr class="oddeven"><td class=\"right\" colspan=\"5\"><i>Total CA previsionnel : </i></td><td class=\"right\"><i>".price($total_CA)."</i></td><td colspan=\"3\"><-- bug ici car bug sur les 2 precedents</td></tr>";
 }
 print "</table>";

 */

// End of page
llxFooter();
$db->close();
