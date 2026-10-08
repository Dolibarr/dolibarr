<?php
/* Copyright (C) 2003       Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2026		Jose Martinez				<jose.martinez@pichinov.com>
 * Copyright (C) 2005-2012  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2014-2016  Ferran Marcet           <fmarcet@2byte.es>
 * Copyright (C) 2014       Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2014       Florian Henry           <florian.henry@open-concept.pro>
 * Copyright (C) 2018-2026  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2020       Maxime DEMAREST         <maxime@indelog.fr>
 * Copyright (C) 2024-2026	MDW						<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2026		Nick Fragoulis
 * Copyright (C) 2026		Christos Kanotidis		<christoskanotidis@gmail.com>
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
 *      \file       htdocs/compta/resultat/index.php
 * 	 	\ingroup	compta, accountancy
 *      \brief      Page reporting result
 */

// Load Dolibarr environment
require '../../main.inc.php';
/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Societe $mysoc
 * @var Translate $langs
 * @var User $user
 */
require_once DOL_DOCUMENT_ROOT.'/core/lib/report.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

// Load translation files required by the page
$langs->loadLangs(array('compta', 'bills', 'donation', 'accountancy', 'salaries'));

$date_startday = GETPOSTINT('date_startday');
$date_startmonth = GETPOSTINT('date_startmonth');
$date_startyear = GETPOSTINT('date_startyear');
$date_endday = GETPOSTINT('date_endday');
$date_endmonth = GETPOSTINT('date_endmonth');
$date_endyear = GETPOSTINT('date_endyear');

$nbofyear = 4;

// Change this to test different cases of setup.
//$conf->global->SOCIETE_FISCAL_MONTH_START = 7;


// Date range
$year = GETPOSTINT('year');		// this is used for navigation previous/next. It is the last year to show in filter
if (empty($year)) {
	$year_current = (int) dol_print_date(dol_now(), "%Y");
	$month_current = (int) dol_print_date(dol_now(), "%m");
	$year_start = $year_current - ($nbofyear - 1);
} else {
	$year_current = $year;
	$month_current = (int) dol_print_date(dol_now(), "%m");
	$year_start = $year - $nbofyear + (getDolGlobalInt('SOCIETE_FISCAL_MONTH_START') > 1 ? 0 : 1);
}
$date_start = dol_mktime(0, 0, 0, $date_startmonth, $date_startday, $date_startyear, 'tzserver');
$date_end = dol_mktime(23, 59, 59, $date_endmonth, $date_endday, $date_endyear, 'tzserver');

// We define date_start and date_end
if (empty($date_start) || empty($date_end)) { // We define date_start and date_end
	$q = GETPOST("q") ? GETPOSTINT("q") : 0;
	if ($q == 0) {
		// We define date_start and date_end
		$year_end = $year_start + $nbofyear - (getDolGlobalInt('SOCIETE_FISCAL_MONTH_START') > 1 ? 0 : 1);
		$month_start = GETPOST("month") ? GETPOSTINT("month") : getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1);
		if (!GETPOST('month')) {
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

// $date_start and $date_end are defined. We force $year_start and $nbofyear
$tmps = dol_getdate($date_start);
$year_start = $tmps['year'];
$tmpe = dol_getdate($date_end);
$year_end = $tmpe['year'];
$nbofyear = ($year_end - $year_start) + 1;
//var_dump("year_start=".$year_start." year_end=".$year_end." nbofyear=".$nbofyear." date_start=".dol_print_date($date_start, 'dayhour')." date_end=".dol_print_date($date_end, 'dayhour'));

// Define modecompta ('CREANCES-DETTES' or 'RECETTES-DEPENSES' or 'BOOKKEEPING')
$modecompta = getDolGlobalString('ACCOUNTING_MODE');
if (isModEnabled('accounting')) {
	$modecompta = 'BOOKKEEPING';
}
if (GETPOST("modecompta", 'alpha')) {
	$modecompta = GETPOST("modecompta", 'alpha');
}

// Security check
$socid = GETPOSTINT('socid');
if ($user->socid > 0) {
	$socid = $user->socid;
}
if (isModEnabled('comptabilite')) {
	$result = restrictedArea($user, 'compta', '', '', 'resultat');
}
if (isModEnabled('accounting')) {
	$result = restrictedArea($user, 'accounting', '', '', 'comptarapport');
}

// Export of the result table. Test on permission already done
$exportaction = GETPOST('action', 'aZ09');
$isexport = (in_array($exportaction, array('exportpdf', 'exportxlsx')) && in_array($modecompta, array('CREANCES-DETTES', 'RECETTES-DEPENSES', 'BOOKKEEPING')));


/**
 * Return the result table as shown on screen, for exports.
 *
 * @param	Translate				$outputlangs		Output language
 * @param	string					$modecompta			Calculation mode
 * @param	int						$year_start			First year
 * @param	int						$year_end_for_table	Last year
 * @param	array<string,float>		$encaiss			Income excl. tax by year-month
 * @param	array<string,float>		$encaiss_ttc		Income incl. tax by year-month
 * @param	array<string,float>		$decaiss			Expense excl. tax by year-month
 * @param	array<string,float>		$decaiss_ttc		Expense incl. tax by year-month
 * @return	array{years:array<int,string>,months:array<int,array{label:string,cells:array<int,array{out:?float,in:?float}>}>,totals:array<int,array{out:?float,in:?float}>,result:array<int,?float>}
 */
function resultatExportGrid($outputlangs, $modecompta, $year_start, $year_end_for_table, $encaiss, $encaiss_ttc, $decaiss, $decaiss_ttc)
{
	$ht = ($modecompta == 'CREANCES-DETTES' || $modecompta == 'BOOKKEEPING');
	$in = ($ht ? $encaiss : $encaiss_ttc);
	$out = ($ht ? $decaiss : $decaiss_ttc);
	$monthstart = getDolGlobalInt('SOCIETE_FISCAL_MONTH_START') ? getDolGlobalInt('SOCIETE_FISCAL_MONTH_START') : 1;

	$grid = array('years' => array(), 'months' => array(), 'totals' => array(), 'result' => array());
	$totin = array();
	$totout = array();
	for ($annee = $year_start; $annee <= $year_end_for_table; $annee++) {
		$grid['years'][$annee] = $annee.($monthstart > 1 ? '-'.($annee + 1) : '');
	}

	// Same rules as the table on screen: expense shown when not zero, income shown when set
	for ($mois = $monthstart; $mois <= 11 + $monthstart; $mois++) {
		$mois_modulo = ($mois > 12 ? $mois - 12 : $mois);
		$cells = array();
		for ($annee = $year_start; $annee <= $year_end_for_table; $annee++) {
			$case = dol_print_date(dol_mktime(12, 0, 0, $mois_modulo, 1, ($mois > 12 ? $annee + 1 : $annee)), "%Y-%m");
			$cell = array('out' => null, 'in' => null);
			if (isset($out[$case]) && $out[$case] != 0) {
				$cell['out'] = (float) price2num($out[$case], 'MT');
				$totout[$annee] = (isset($totout[$annee]) ? $totout[$annee] : 0) + $out[$case];
			}
			if (isset($in[$case])) {
				$cell['in'] = (float) price2num($in[$case], 'MT');
				$totin[$annee] = (isset($totin[$annee]) ? $totin[$annee] : 0) + $in[$case];
			}
			$cells[$annee] = $cell;
		}
		$grid['months'][] = array('label' => dol_print_date(dol_mktime(12, 0, 0, $mois_modulo, 1, $year_start), "%B", 'tzserver', $outputlangs), 'cells' => $cells);
	}

	for ($annee = $year_start; $annee <= $year_end_for_table; $annee++) {
		$grid['totals'][$annee] = array(
			'out' => (isset($totout[$annee]) ? (float) price2num($totout[$annee], 'MT') : null),
			'in' => (isset($totin[$annee]) ? (float) price2num($totin[$annee], 'MT') : null)
		);
		$grid['result'][$annee] = null;
		if (isset($totin[$annee]) || isset($totout[$annee])) {
			$grid['result'][$annee] = (float) price2num((float) price2num(isset($totin[$annee]) ? $totin[$annee] : 0, 'MT') - (float) price2num(isset($totout[$annee]) ? $totout[$annee] : 0, 'MT'), 'MT');
		}
	}

	return $grid;
}

/**
 * Print the table header rows into the PDF.
 *
 * @param	TCPDF					$pdf			PDF instance
 * @param	Translate				$outputlangs	Output language
 * @param	array<int,string>		$years			Year labels
 * @param	float					$wmonth			Width of month column
 * @param	float					$wcol			Width of an amount column
 * @param	int						$fontsize		Default font size
 * @return	void
 */
function resultatPdfTableHeader($pdf, $outputlangs, $years, $wmonth, $wcol, $fontsize)
{
	$pdf->SetFont('', 'B', $fontsize - 1);
	$pdf->SetFillColor(230, 230, 230);
	$x = $pdf->GetX();
	$pdf->Cell($wmonth, 12, $outputlangs->transnoentities("Month"), 1, 0, 'L', true);
	foreach ($years as $yearlabel) {
		$pdf->Cell(2 * $wcol, 6, $yearlabel, 1, 0, 'C', true);
	}
	$pdf->Ln();
	$pdf->SetX($x + $wmonth);
	foreach ($years as $yearlabel) {
		$pdf->Cell($wcol, 6, $outputlangs->transnoentities("Outcome"), 1, 0, 'R', true, '', 1);
		$pdf->Cell($wcol, 6, $outputlangs->transnoentities("Income"), 1, 0, 'R', true, '', 1);
	}
	$pdf->Ln();
	$pdf->SetFont('', '', $fontsize - 1);
}


/*
 * View
 */

if (!$isexport) {
	llxHeader();
}

$form = new Form($db);


$builddate = 0;
$name = '';
$period = '';
$periodlink = '';
$exportlink = '';
$description = '';

$encaiss = array();
$encaiss_ttc = array();
$decaiss = array();
$decaiss_ttc = array();

// Display report header
if ($modecompta == 'CREANCES-DETTES') {
	$name = $langs->trans("ReportInOut").', '.$langs->trans("ByYear");
	$period = $form->selectDate($date_start, 'date_start', 0, 0, 0, '', 1, 0).' - '.$form->selectDate($date_end, 'date_end', 0, 0, 0, '', 1, 0);
	$periodlink = ($year_start ? "<a href='".$_SERVER["PHP_SELF"]."?year=".($year_start + $nbofyear - 2)."&modecompta=".$modecompta."'>".img_previous()."</a> <a href='".$_SERVER["PHP_SELF"]."?year=".($year_start + $nbofyear)."&modecompta=".$modecompta."'>".img_next()."</a>" : "");
	$description = $langs->trans("RulesAmountWithTaxExcluded");
	$description .= '<br>'.$langs->trans("RulesResultDue");
	if (getDolGlobalString('FACTURE_DEPOSITS_ARE_JUST_PAYMENTS')) {
		$description .= "<br>".$langs->trans("DepositsAreNotIncluded");
	} else {
		$description .= "<br>".$langs->trans("DepositsAreIncluded");
	}
	if (getDolGlobalString('FACTURE_SUPPLIER_DEPOSITS_ARE_JUST_PAYMENTS')) {
		$description .= $langs->trans("SupplierDepositsAreNotIncluded");
	}
	$builddate = dol_now();
} elseif ($modecompta == "RECETTES-DEPENSES") {
	$name = $langs->trans("ReportInOut").', '.$langs->trans("ByYear");
	$period = $form->selectDate($date_start, 'date_start', 0, 0, 0, '', 1, 0).' - '.$form->selectDate($date_end, 'date_end', 0, 0, 0, '', 1, 0);
	$periodlink = ($year_start ? "<a href='".$_SERVER["PHP_SELF"]."?year=".($year_start + $nbofyear - 2)."&modecompta=".$modecompta."'>".img_previous()."</a> <a href='".$_SERVER["PHP_SELF"]."?year=".($year_start + $nbofyear)."&modecompta=".$modecompta."'>".img_next()."</a>" : "");
	$description = $langs->trans("RulesAmountWithTaxIncluded");
	$description .= '<br>'.$langs->trans("RulesResultInOut");
	$builddate = dol_now();
} elseif ($modecompta == "BOOKKEEPING") {
	$name = $langs->trans("ReportInOut").', '.$langs->trans("ByYear");
	$period = $form->selectDate($date_start, 'date_start', 0, 0, 0, '', 1, 0).' - '.$form->selectDate($date_end, 'date_end', 0, 0, 0, '', 1, 0);
	$periodlink = ($year_start ? "<a href='".$_SERVER["PHP_SELF"]."?year=".($year_start + $nbofyear - 2)."&modecompta=".$modecompta."'>".img_previous()."</a> <a href='".$_SERVER["PHP_SELF"]."?year=".($year_start + $nbofyear)."&modecompta=".$modecompta."'>".img_next()."</a>" : "");
	$description = $langs->trans("RulesAmountOnInOutBookkeepingRecord");
	$description .= ' ('.$langs->trans("SeePageForSetup", DOL_URL_ROOT.'/accountancy/admin/account.php?mainmenu=accountancy&leftmenu=accountancy_admin', $langs->transnoentitiesnoconv("Accountancy").' / '.$langs->transnoentitiesnoconv("Setup").' / '.$langs->transnoentitiesnoconv("Chartofaccounts")).')';
	$builddate = dol_now();
}

// Define $calcmode line
$calcmode = '';
if (isModEnabled('accounting')) {
	$calcmode .= '<input type="radio" name="modecompta" id="modecompta3" value="BOOKKEEPING"'.($modecompta == 'BOOKKEEPING' ? ' checked="checked"' : '').'><label for="modecompta3"> '.$langs->trans("CalcModeBookkeeping").'</label>';
	$calcmode .= '<br>';
}
$calcmode .= '<input type="radio" name="modecompta" id="modecompta1" value="RECETTES-DEPENSES"'.($modecompta == 'RECETTES-DEPENSES' ? ' checked="checked"' : '').'><label for="modecompta1"> '.$langs->trans("CalcModePayment");
if (isModEnabled('accounting')) {
	$calcmode .= ' <span class="opacitymedium hideonsmartphone">('.$langs->trans("CalcModeNoBookKeeping").')</span>';
}
$calcmode .= '</label>';
$calcmode .= '<br><input type="radio" name="modecompta" id="modecompta2" value="CREANCES-DETTES"'.($modecompta == 'CREANCES-DETTES' ? ' checked="checked"' : '').'><label for="modecompta2"> '.$langs->trans("CalcModeDebt");
if (isModEnabled('accounting')) {
	$calcmode .= ' <span class="opacitymedium hideonsmartphone">('.$langs->trans("CalcModeNoBookKeeping").')</span>';
}
$calcmode .= '</label>';

// Export keeps the period and calculation mode shown
$exportparam = '&modecompta='.urlencode($modecompta);
$exportparam .= '&date_startday='.dol_print_date($date_start, '%d').'&date_startmonth='.dol_print_date($date_start, '%m').'&date_startyear='.dol_print_date($date_start, '%Y');
$exportparam .= '&date_endday='.dol_print_date($date_end, '%d').'&date_endmonth='.dol_print_date($date_end, '%m').'&date_endyear='.dol_print_date($date_end, '%Y');
if ($name !== '') {
	$exportlink = reportExportButtons($exportparam, 'resultat');
}

if (!$isexport) {
	report_header($name, '', $period, $periodlink, $description, $builddate, $exportlink, array(), $calcmode);

	if (isModEnabled('accounting') && $modecompta != 'BOOKKEEPING') {
		print info_admin($langs->trans("WarningReportNotReliable"), 0, 0, '1');
	}
}



/*
 * Customers invoices
 */

$subtotal_ht = 0;
$subtotal_ttc = 0;
if (isModEnabled('invoice') && ($modecompta == 'CREANCES-DETTES' || $modecompta == "RECETTES-DEPENSES")) {
	if ($modecompta == 'CREANCES-DETTES') {
		$sql = "SELECT sum(f.total_ht) as amount_ht, sum(f.total_ttc) as amount_ttc, date_format(f.datef,'%Y-%m') as dm";
		$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
		$sql .= ", ".MAIN_DB_PREFIX."facture as f";
		$sql .= " WHERE f.fk_soc = s.rowid";
		$sql .= " AND f.fk_statut IN (1,2)";
		if (getDolGlobalString('FACTURE_DEPOSITS_ARE_JUST_PAYMENTS')) {
			$sql .= " AND f.type IN (0,1,2,5)";
		} else {
			$sql .= " AND f.type IN (0,1,2,3,5)";
		}
		// Add SQL restrictions from hooks (context turnoverreport), e.g. a deposit pivot date restricting deposits by their date
		$hookmanager->initHooks(array('turnoverreport'));
		$parameters = array('invoicealias' => 'f', 'issupplier' => 0, 'datefield' => 'datef');
		$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters); // Note that $action and $object may have been modified by some hooks
		$sql .= $hookmanager->resPrint;
		if (!empty($date_start) && !empty($date_end)) {
			$sql .= " AND f.datef >= '".$db->idate($date_start)."' AND f.datef <= '".$db->idate($date_end)."'";
		}
	} elseif ($modecompta == "RECETTES-DEPENSES") {
		/*
		 * Liste des paiements (les anciens paiements ne sont pas vus par cette requete car, sur les
		 * vieilles versions, ils n'etaient pas lies via paiement_facture. On les ajoute plus loin)
		 */
		$sql = "SELECT sum(pf.amount) as amount_ttc, date_format(p.datep,'%Y-%m') as dm";
		$sql .= " FROM ".MAIN_DB_PREFIX."facture as f";
		$sql .= ", ".MAIN_DB_PREFIX."paiement_facture as pf";
		$sql .= ", ".MAIN_DB_PREFIX."paiement as p";
		$sql .= " WHERE p.rowid = pf.fk_paiement";
		$sql .= " AND pf.fk_facture = f.rowid";
		if (!empty($date_start) && !empty($date_end)) {
			$sql .= " AND p.datep >= '".$db->idate($date_start)."' AND p.datep <= '".$db->idate($date_end)."'";
		}
	}
	$sql .= " AND f.entity IN (".getEntity('invoice').")";
	if ($socid) {
		$sql .= " AND f.fk_soc = ".((int) $socid);
	}
	$sql .= " GROUP BY dm";
	$sql .= " ORDER BY dm";

	//print $sql;
	dol_syslog("get customers invoices", LOG_DEBUG);
	$result = $db->query($sql);
	if ($result) {
		$num = $db->num_rows($result);
		$i = 0;
		while ($i < $num) {
			$row = $db->fetch_object($result);
			$encaiss[$row->dm] = (isset($row->amount_ht) ? $row->amount_ht : 0);
			$encaiss_ttc[$row->dm] = $row->amount_ttc;
			$i++;
		}
		$db->free($result);
	} else {
		dol_print_error($db);
	}
} // elseif ($modecompta == "BOOKKEEPING") {
// Nothing from this table
//}

if (isModEnabled('invoice') && ($modecompta == 'CREANCES-DETTES' || $modecompta == "RECETTES-DEPENSES")) {
	// Adding legacy client payments not linked via 'paiement_facture'.
	if ($modecompta != 'CREANCES-DETTES') {
		$sql = "SELECT sum(p.amount) as amount_ttc, date_format(p.datep,'%Y-%m') as dm";
		$sql .= " FROM ".MAIN_DB_PREFIX."bank as b";
		$sql .= ", ".MAIN_DB_PREFIX."bank_account as ba";
		$sql .= ", ".MAIN_DB_PREFIX."paiement as p";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."paiement_facture as pf ON p.rowid = pf.fk_paiement";
		$sql .= " WHERE pf.rowid IS NULL";
		$sql .= " AND p.fk_bank = b.rowid";
		$sql .= " AND b.fk_account = ba.rowid";
		$sql .= " AND ba.entity IN (".getEntity('bank_account').")";
		if (!empty($date_start) && !empty($date_end)) {
			$sql .= " AND p.datep >= '".$db->idate($date_start)."' AND p.datep <= '".$db->idate($date_end)."'";
		}
		$sql .= " GROUP BY dm";
		$sql .= " ORDER BY dm";

		dol_syslog("get old customers payments not linked to invoices", LOG_DEBUG);
		$result = $db->query($sql);
		if ($result) {
			$num = $db->num_rows($result);
			$i = 0;
			while ($i < $num) {
				$row = $db->fetch_object($result);

				if (!isset($encaiss[$row->dm])) {
					$encaiss[$row->dm] = 0;
				}
				$encaiss[$row->dm] += (isset($row->amount_ht) ? $row->amount_ht : 0);

				if (!isset($encaiss_ttc[$row->dm])) {
					$encaiss_ttc[$row->dm] = 0;
				}
				$encaiss_ttc[$row->dm] += $row->amount_ttc;

				$i++;
			}
		} else {
			dol_print_error($db);
		}
	} //elseif ($modecompta == "RECETTES-DEPENSES") {
	// Nothing from this table
	//}
} //elseif ($modecompta == "BOOKKEEPING") {
// Nothing from this table
//}


/*
 * Expenses, supplier invoices.
 */
$subtotal_ht = 0;
$subtotal_ttc = 0;

if (isModEnabled('invoice') && ($modecompta == 'CREANCES-DETTES' || $modecompta == "RECETTES-DEPENSES")) {
	if ($modecompta == 'CREANCES-DETTES') {
		$sql = "SELECT sum(f.total_ht) as amount_ht, sum(f.total_ttc) as amount_ttc, date_format(f.datef,'%Y-%m') as dm";
		$sql .= " FROM ".MAIN_DB_PREFIX."facture_fourn as f";
		$sql .= " WHERE f.fk_statut IN (1,2)";
		if (getDolGlobalString('FACTURE_SUPPLIER_DEPOSITS_ARE_JUST_PAYMENTS')) {
			$sql .= " AND f.type IN (0,1,2)";
		} else {
			$sql .= " AND f.type IN (0,1,2,3)";
		}
		// Add SQL restrictions from hooks (context turnoverreport), e.g. a deposit pivot date restricting deposits by their date
		$hookmanager->initHooks(array('turnoverreport'));
		$parameters = array('invoicealias' => 'f', 'issupplier' => 1, 'datefield' => 'datef');
		$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters); // Note that $action and $object may have been modified by some hooks
		$sql .= $hookmanager->resPrint;
		if (!empty($date_start) && !empty($date_end)) {
			$sql .= " AND f.datef >= '".$db->idate($date_start)."' AND f.datef <= '".$db->idate($date_end)."'";
		}
	} elseif ($modecompta == "RECETTES-DEPENSES") {
		$sql = "SELECT sum(pf.amount) as amount_ttc, date_format(p.datep,'%Y-%m') as dm";
		$sql .= " FROM ".MAIN_DB_PREFIX."paiementfourn as p";
		$sql .= ", ".MAIN_DB_PREFIX."facture_fourn as f";
		$sql .= ", ".MAIN_DB_PREFIX."paiementfourn_facturefourn as pf";
		$sql .= " WHERE f.rowid = pf.fk_facturefourn";
		$sql .= " AND p.rowid = pf.fk_paiementfourn";
		if (!empty($date_start) && !empty($date_end)) {
			$sql .= " AND p.datep >= '".$db->idate($date_start)."' AND p.datep <= '".$db->idate($date_end)."'";
		}
	}
	$sql .= " AND f.entity IN (".getEntity('supplier_invoice').")";

	if ($socid) {
		$sql .= " AND f.fk_soc = ".((int) $socid);
	}
	$sql .= " GROUP BY dm";

	dol_syslog("get suppliers invoices", LOG_DEBUG);
	$result = $db->query($sql);
	if ($result) {
		$num = $db->num_rows($result);
		$i = 0;
		while ($i < $num) {
			$row = $db->fetch_object($result);

			if (!isset($decaiss[$row->dm])) {
				$decaiss[$row->dm] = 0;
			}
			$decaiss[$row->dm] = (isset($row->amount_ht) ? $row->amount_ht : 0);

			if (!isset($decaiss_ttc[$row->dm])) {
				$decaiss_ttc[$row->dm] = 0;
			}
			$decaiss_ttc[$row->dm] = $row->amount_ttc;

			$i++;
		}
		$db->free($result);
	} else {
		dol_print_error($db);
	}
} //elseif ($modecompta == "BOOKKEEPING") {
// Nothing from this table
//}



/*
 * TVA
 */

$subtotal_ht = 0;
$subtotal_ttc = 0;
if (isModEnabled('tax') && ($modecompta == 'CREANCES-DETTES' || $modecompta == "RECETTES-DEPENSES")) {
	if ($modecompta == 'CREANCES-DETTES') {
		// TVA collected to pay
		$sql = "SELECT sum(f.total_tva) as amount, date_format(f.datef,'%Y-%m') as dm";
		$sql .= " FROM ".MAIN_DB_PREFIX."facture as f";
		$sql .= " WHERE f.fk_statut IN (1,2)";
		if (getDolGlobalString('FACTURE_DEPOSITS_ARE_JUST_PAYMENTS')) {
			$sql .= " AND f.type IN (0,1,2,5)";
		} else {
			$sql .= " AND f.type IN (0,1,2,3,5)";
		}
		// Add SQL restrictions from hooks (context turnoverreport), e.g. a deposit pivot date restricting deposits by their date
		$hookmanager->initHooks(array('turnoverreport'));
		$parameters = array('invoicealias' => 'f', 'issupplier' => 0, 'datefield' => 'datef');
		$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters); // Note that $action and $object may have been modified by some hooks
		$sql .= $hookmanager->resPrint;
		$sql .= " AND f.entity IN (".getEntity('invoice').")";
		if (!empty($date_start) && !empty($date_end)) {
			$sql .= " AND f.datef >= '".$db->idate($date_start)."' AND f.datef <= '".$db->idate($date_end)."'";
		}
		$sql .= " GROUP BY dm";

		dol_syslog("get vat to pay", LOG_DEBUG);
		$result = $db->query($sql);
		if ($result) {
			$num = $db->num_rows($result);
			$i = 0;
			if ($num) {
				while ($i < $num) {
					$obj = $db->fetch_object($result);

					/*if (!isset($decaiss[$obj->dm])) {
						$decaiss[$obj->dm] = 0;
					}
					$decaiss[$obj->dm] += $obj->amount;*/

					if (!isset($decaiss_ttc[$obj->dm])) {
						$decaiss_ttc[$obj->dm] = 0;
					}
					$decaiss_ttc[$obj->dm] += $obj->amount;

					$i++;
				}
			}
		} else {
			dol_print_error($db);
		}
		// TVA paid to get
		$sql = "SELECT sum(f.total_tva) as amount, date_format(f.datef,'%Y-%m') as dm";
		$sql .= " FROM ".MAIN_DB_PREFIX."facture_fourn as f";
		$sql .= " WHERE f.fk_statut IN (1,2)";
		if (getDolGlobalString('FACTURE_SUPPLIER_DEPOSITS_ARE_JUST_PAYMENTS')) {
			$sql .= " AND f.type IN (0,1,2)";
		} else {
			$sql .= " AND f.type IN (0,1,2,3)";
		}
		// Add SQL restrictions from hooks (context turnoverreport), e.g. a deposit pivot date restricting deposits by their date
		$hookmanager->initHooks(array('turnoverreport'));
		$parameters = array('invoicealias' => 'f', 'issupplier' => 1, 'datefield' => 'datef');
		$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters); // Note that $action and $object may have been modified by some hooks
		$sql .= $hookmanager->resPrint;
		$sql .= " AND f.entity IN (".getEntity('supplier_invoice').")";
		if (!empty($date_start) && !empty($date_end)) {
			$sql .= " AND f.datef >= '".$db->idate($date_start)."' AND f.datef <= '".$db->idate($date_end)."'";
		}
		$sql .= " GROUP BY dm";

		dol_syslog("get vat to receive back", LOG_DEBUG);
		$result = $db->query($sql);
		if ($result) {
			$num = $db->num_rows($result);
			$i = 0;
			if ($num) {
				while ($i < $num) {
					$obj = $db->fetch_object($result);

					/*if (!isset($encaiss[$obj->dm])) {
						$encaiss[$obj->dm] = 0;
					}
					$encaiss[$obj->dm] += $obj->amount;*/

					if (!isset($encaiss_ttc[$obj->dm])) {
						$encaiss_ttc[$obj->dm] = 0;
					}
					$encaiss_ttc[$obj->dm] += $obj->amount;

					$i++;
				}
			}
		} else {
			dol_print_error($db);
		}
	} elseif ($modecompta == "RECETTES-DEPENSES") {
		// TVA really already paid
		$sql = "SELECT sum(t.amount) as amount, date_format(t.datev,'%Y-%m') as dm";
		$sql .= " FROM ".MAIN_DB_PREFIX."tva as t";
		$sql .= " WHERE amount > 0";
		$sql .= " AND t.entity IN (".getEntity('vat').")";
		if (!empty($date_start) && !empty($date_end)) {
			$sql .= " AND t.datev >= '".$db->idate($date_start)."' AND t.datev <= '".$db->idate($date_end)."'";
		}
		$sql .= " GROUP BY dm";

		dol_syslog("get vat really paid", LOG_DEBUG);
		$result = $db->query($sql);
		if ($result) {
			$num = $db->num_rows($result);
			$i = 0;
			if ($num) {
				while ($i < $num) {
					$obj = $db->fetch_object($result);

					/*if (!isset($decaiss[$obj->dm])) {
						$decaiss[$obj->dm] = 0;
					}
					$decaiss[$obj->dm] += $obj->amount;*/

					if (!isset($decaiss_ttc[$obj->dm])) {
						$decaiss_ttc[$obj->dm] = 0;
					}
					$decaiss_ttc[$obj->dm] += $obj->amount;

					$i++;
				}
			}
		} else {
			dol_print_error($db);
		}
		// TVA retrieved
		$sql = "SELECT sum(t.amount) as amount, date_format(t.datev,'%Y-%m') as dm";
		$sql .= " FROM ".MAIN_DB_PREFIX."tva as t";
		$sql .= " WHERE amount < 0";
		$sql .= " AND t.entity IN (".getEntity('vat').")";
		if (!empty($date_start) && !empty($date_end)) {
			$sql .= " AND t.datev >= '".$db->idate($date_start)."' AND t.datev <= '".$db->idate($date_end)."'";
		}
		$sql .= " GROUP BY dm";

		dol_syslog("get vat really received back", LOG_DEBUG);
		$result = $db->query($sql);
		if ($result) {
			$num = $db->num_rows($result);
			$i = 0;
			if ($num) {
				while ($i < $num) {
					$obj = $db->fetch_object($result);

					/*if (!isset($encaiss[$obj->dm])) {
						$encaiss[$obj->dm] = 0;
					}
					$encaiss[$obj->dm] += -$obj->amount;*/

					if (!isset($encaiss_ttc[$obj->dm])) {
						$encaiss_ttc[$obj->dm] = 0;
					}
					$encaiss_ttc[$obj->dm] += -$obj->amount;

					$i++;
				}
			}
		} else {
			dol_print_error($db);
		}
	}
}// elseif ($modecompta == "BOOKKEEPING") {
// Nothing from this table
//}

/*
 * Social contributions
 */

$subtotal_ht = 0;
$subtotal_ttc = 0;
if (isModEnabled('tax') && ($modecompta == 'CREANCES-DETTES' || $modecompta == "RECETTES-DEPENSES")) {
	if ($modecompta == 'CREANCES-DETTES') {
		$sql = "SELECT c.libelle as nom, date_format(cs.date_ech,'%Y-%m') as dm, sum(cs.amount) as amount";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_chargesociales as c";
		$sql .= ", ".MAIN_DB_PREFIX."chargesociales as cs";
		$sql .= " WHERE cs.fk_type = c.id";
		if (!empty($date_start) && !empty($date_end)) {
			$sql .= " AND cs.date_ech >= '".$db->idate($date_start)."' AND cs.date_ech <= '".$db->idate($date_end)."'";
		}
	} elseif ($modecompta == "RECETTES-DEPENSES") {
		$sql = "SELECT c.libelle as nom, date_format(p.datep,'%Y-%m') as dm, sum(p.amount) as amount";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_chargesociales as c";
		$sql .= ", ".MAIN_DB_PREFIX."chargesociales as cs";
		$sql .= ", ".MAIN_DB_PREFIX."paiementcharge as p";
		$sql .= " WHERE p.fk_charge = cs.rowid";
		$sql .= " AND cs.fk_type = c.id";
		if (!empty($date_start) && !empty($date_end)) {
			$sql .= " AND p.datep >= '".$db->idate($date_start)."' AND p.datep <= '".$db->idate($date_end)."'";
		}
	}

	$sql .= " AND cs.entity IN (".getEntity('social_contributions').")";
	$sql .= " GROUP BY c.libelle, dm";

	dol_syslog("get social contributions", LOG_DEBUG);
	$result = $db->query($sql);
	if ($result) {
		$num = $db->num_rows($result);
		$i = 0;
		if ($num) {
			while ($i < $num) {
				$obj = $db->fetch_object($result);

				if (!isset($decaiss[$obj->dm])) {
					$decaiss[$obj->dm] = 0;
				}
				$decaiss[$obj->dm] += $obj->amount;

				if (!isset($decaiss_ttc[$obj->dm])) {
					$decaiss_ttc[$obj->dm] = 0;
				}
				$decaiss_ttc[$obj->dm] += $obj->amount;

				$i++;
			}
		}
	} else {
		dol_print_error($db);
	}
} //elseif ($modecompta == "BOOKKEEPING") {
// Nothing from this table
//}


/*
 * Salaries
 */

if (isModEnabled('salaries') && ($modecompta == 'CREANCES-DETTES' || $modecompta == "RECETTES-DEPENSES")) {
	$sql = '';
	if ($modecompta == 'CREANCES-DETTES') {
		$column = 's.dateep';		// we use the date of end of period of salary

		$sql = "SELECT s.label as nom, date_format(".$db->sanitize($column).",'%Y-%m') as dm, sum(s.amount) as amount";
		$sql .= " FROM ".MAIN_DB_PREFIX."salary as s";
		$sql .= " WHERE s.entity IN (".getEntity('salary').")";
		if (!empty($date_start) && !empty($date_end)) {
			$sql .= " AND ".$db->sanitize($column)." >= '".$db->idate($date_start)."' AND ".$db->sanitize($column)." <= '".$db->idate($date_end)."'";
		}
		$sql .= " GROUP BY s.label, dm";
	}
	if ($modecompta == "RECETTES-DEPENSES") {
		$column = 'p.datep';

		$sql = "SELECT p.label as nom, date_format(".$db->sanitize($column).",'%Y-%m') as dm, sum(p.amount) as amount";
		$sql .= " FROM ".MAIN_DB_PREFIX."payment_salary as p";
		$sql .= " INNER JOIN ".MAIN_DB_PREFIX."salary as s ON p.fk_salary = s.rowid";
		$sql .= " WHERE p.entity IN (".getEntity('payment_salary').")";
		if (!empty($date_start) && !empty($date_end)) {
			$sql .= " AND ".$db->sanitize($column)." >= '".$db->idate($date_start)."' AND ".$db->sanitize($column)." <= '".$db->idate($date_end)."'";
		}
		$sql .= " GROUP BY p.label, dm";
	}

	$subtotal_ht = 0;
	$subtotal_ttc = 0;

	dol_syslog("get social salaries payments");
	$result = $db->query($sql);
	if ($result) {
		$num = $db->num_rows($result);
		$i = 0;
		if ($num) {
			while ($i < $num) {
				$obj = $db->fetch_object($result);

				if (!isset($decaiss[$obj->dm])) {
					$decaiss[$obj->dm] = 0;
				}
				$decaiss[$obj->dm] += $obj->amount;

				if (!isset($decaiss_ttc[$obj->dm])) {
					$decaiss_ttc[$obj->dm] = 0;
				}
				$decaiss_ttc[$obj->dm] += $obj->amount;

				$i++;
			}
		}
	} else {
		dol_print_error($db);
	}
} //elseif ($modecompta == "BOOKKEEPING") {
// Nothing from this table
//}


/*
 * Expense reports
 */

if (isModEnabled('expensereport') && ($modecompta == 'CREANCES-DETTES' || $modecompta == "RECETTES-DEPENSES")) {
	$langs->load('trips');

	if ($modecompta == 'CREANCES-DETTES') {
		$sql = "SELECT date_format(date_valid,'%Y-%m') as dm, sum(p.total_ht) as amount_ht,sum(p.total_ttc) as amount_ttc";
		$sql .= " FROM ".MAIN_DB_PREFIX."expensereport as p";
		$sql .= " INNER JOIN ".MAIN_DB_PREFIX."user as u ON u.rowid=p.fk_user_author";
		$sql .= " WHERE p.entity IN (".getEntity('expensereport').")";
		$sql .= " AND p.fk_statut>=5";

		$column = 'p.date_valid';
		if (!empty($date_start) && !empty($date_end)) {
			$sql .= " AND ".$db->sanitize($column)." >= '".$db->idate($date_start)."' AND ".$db->sanitize($column)." <= '".$db->idate($date_end)."'";
		}
	} elseif ($modecompta == 'RECETTES-DEPENSES') {
		$sql = "SELECT date_format(pe.datep,'%Y-%m') as dm, sum(p.total_ht) as amount_ht,sum(p.total_ttc) as amount_ttc";
		$sql .= " FROM ".MAIN_DB_PREFIX."expensereport as p";
		$sql .= " INNER JOIN ".MAIN_DB_PREFIX."user as u ON u.rowid=p.fk_user_author";
		$sql .= " INNER JOIN ".MAIN_DB_PREFIX."payment_expensereport as pe ON pe.fk_expensereport = p.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_paiement as c ON pe.fk_typepayment = c.id";
		$sql .= " WHERE p.entity IN (".getEntity('expensereport').")";
		$sql .= " AND p.fk_statut >= 5";

		$column = 'pe.datep';
		if (!empty($date_start) && !empty($date_end)) {
			$sql .= " AND ".$db->sanitize($column)." >= '".$db->idate($date_start)."' AND ".$db->sanitize($column)." <= '".$db->idate($date_end)."'";
		}
	}

	$sql .= " GROUP BY dm";

	dol_syslog("get expense report outcome");
	$result = $db->query($sql);
	$subtotal_ht = 0;
	$subtotal_ttc = 0;
	if ($result) {
		$num = $db->num_rows($result);
		if ($num) {
			while ($obj = $db->fetch_object($result)) {
				if (!isset($decaiss[$obj->dm])) {
					$decaiss[$obj->dm] = 0;
				}
				$decaiss[$obj->dm] += $obj->amount_ht;

				if (!isset($decaiss_ttc[$obj->dm])) {
					$decaiss_ttc[$obj->dm] = 0;
				}
				$decaiss_ttc[$obj->dm] += $obj->amount_ttc;
			}
		}
	} else {
		dol_print_error($db);
	}
} //elseif ($modecompta == 'BOOKKEEPING') {
// Nothing from this table
//}


/*
 * Donation get dunning payments
 */

if (isModEnabled('don') && ($modecompta == 'CREANCES-DETTES' || $modecompta == "RECETTES-DEPENSES")) {
	$subtotal_ht = 0;
	$subtotal_ttc = 0;

	if ($modecompta == 'CREANCES-DETTES') {
		$sql = "SELECT p.societe as nom, p.firstname, p.lastname, date_format(p.datedon,'%Y-%m') as dm, sum(p.amount) as amount";
		$sql .= " FROM ".MAIN_DB_PREFIX."don as p";
		$sql .= " WHERE p.entity IN (".getEntity('donation').")";
		$sql .= " AND fk_statut in (1,2)";
		if (!empty($date_start) && !empty($date_end)) {
			$sql .= " AND p.datedon >= '".$db->idate($date_start)."' AND p.datedon <= '".$db->idate($date_end)."'";
		}
	} elseif ($modecompta == 'RECETTES-DEPENSES') {
		$sql = "SELECT p.societe as nom, p.firstname, p.lastname, date_format(pe.datep,'%Y-%m') as dm, sum(p.amount) as amount";
		$sql .= " FROM ".MAIN_DB_PREFIX."don as p";
		$sql .= " INNER JOIN ".MAIN_DB_PREFIX."payment_donation as pe ON pe.fk_donation = p.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_paiement as c ON pe.fk_typepayment = c.id";
		$sql .= " WHERE p.entity IN (".getEntity('donation').")";
		$sql .= " AND fk_statut >= 2";
		if (!empty($date_start) && !empty($date_end)) {
			$sql .= " AND pe.datep >= '".$db->idate($date_start)."' AND pe.datep <= '".$db->idate($date_end)."'";
		}
	}

	$sql .= " GROUP BY p.societe, p.firstname, p.lastname, dm";

	dol_syslog("get donation payments");
	$result = $db->query($sql);
	if ($result) {
		$num = $db->num_rows($result);
		$i = 0;
		if ($num) {
			while ($i < $num) {
				$obj = $db->fetch_object($result);

				if (!isset($encaiss[$obj->dm])) {
					$encaiss[$obj->dm] = 0;
				}
				$encaiss[$obj->dm] += $obj->amount;

				if (!isset($encaiss_ttc[$obj->dm])) {
					$encaiss_ttc[$obj->dm] = 0;
				}
				$encaiss_ttc[$obj->dm] += $obj->amount;

				$i++;
			}
		}
	} else {
		dol_print_error($db);
	}
} //elseif ($modecompta == 'BOOKKEEPING') {
// Nothing from this table
//}

/*
 * Various Payments
 */

if (getDolGlobalString('ACCOUNTING_REPORTS_INCLUDE_VARPAY') && isModEnabled("bank") && ($modecompta == 'CREANCES-DETTES' || $modecompta == "RECETTES-DEPENSES")) {
	// decaiss

	$sql = "SELECT date_format(p.datep, '%Y-%m') AS dm, SUM(p.amount) AS amount FROM ".MAIN_DB_PREFIX."payment_various as p";
	$sql .= " WHERE p.entity IN (".getEntity('variouspayment').")";
	$sql .= ' AND p.sens = 0';
	if (!empty($date_start) && !empty($date_end)) {
		$sql .= " AND p.datep >= '".$db->idate($date_start)."' AND p.datep <= '".$db->idate($date_end)."'";
	}
	$sql .= ' GROUP BY dm';

	dol_syslog("get various payments");
	$result = $db->query($sql);
	if ($result) {
		$num = $db->num_rows($result);
		$i = 0;
		if ($num) {
			while ($i < $num) {
				$obj = $db->fetch_object($result);
				if (!isset($decaiss_ttc[$obj->dm])) {
					$decaiss_ttc[$obj->dm] = 0;
				}
				if (isset($obj->amount)) {
					$decaiss_ttc[$obj->dm] += $obj->amount;
				}
				$i++;
			}
		}
	} else {
		dol_print_error($db);
	}

	// encaiss

	$sql = "SELECT date_format(p.datep, '%Y-%m') AS dm, SUM(p.amount) AS amount FROM ".MAIN_DB_PREFIX."payment_various AS p";
	$sql .= " WHERE p.entity IN (".getEntity('variouspayment').")";
	$sql .= ' AND p.sens = 1';
	if (!empty($date_start) && !empty($date_end)) {
		$sql .= " AND p.datep >= '".$db->idate($date_start)."' AND p.datep <= '".$db->idate($date_end)."'";
	}
	$sql .= ' GROUP BY dm';

	dol_syslog("get various payments");
	$result = $db->query($sql);
	if ($result) {
		$num = $db->num_rows($result);
		$i = 0;
		if ($num) {
			while ($i < $num) {
				$obj = $db->fetch_object($result);
				if (!isset($encaiss_ttc[$obj->dm])) {
					$encaiss_ttc[$obj->dm] = 0;
				}
				if (isset($obj->amount)) {
					$encaiss_ttc[$obj->dm] += $obj->amount;
				}
				$i++;
			}
		}
	} else {
		dol_print_error($db);
	}
}
// Useless with BOOKKEEPING
//elseif ($modecompta == 'BOOKKEEPING') {
//}

/*
 * Payment Loan
 */

if (getDolGlobalString('ACCOUNTING_REPORTS_INCLUDE_LOAN') && isModEnabled('loan') && ($modecompta == 'CREANCES-DETTES' || $modecompta == "RECETTES-DEPENSES")) {
	$sql = "SELECT date_format(p.datep, '%Y-%m') AS dm, SUM(p.amount_capital + p.amount_insurance + p.amount_interest) AS amount";
	$sql .= " FROM ".MAIN_DB_PREFIX."payment_loan AS p, ".MAIN_DB_PREFIX."loan as l";
	$sql .= " WHERE l.entity IN (".getEntity('variouspayment').")";
	$sql .= " AND p.fk_loan = l.rowid";
	if (!empty($date_start) && !empty($date_end)) {
		$sql .= " AND p.datep >= '".$db->idate($date_start)."' AND p.datep <= '".$db->idate($date_end)."'";
	}
	$sql .= ' GROUP BY dm';

	dol_syslog("get loan payments");
	$result = $db->query($sql);
	if ($result) {
		$num = $db->num_rows($result);
		$i = 0;
		if ($num) {
			while ($i < $num) {
				$obj = $db->fetch_object($result);
				if (!isset($decaiss_ttc[$obj->dm])) {
					$decaiss_ttc[$obj->dm] = 0;
				}
				if (isset($obj->amount)) {
					$decaiss_ttc[$obj->dm] += $obj->amount;
				}
				$i++;
			}
		}
	} else {
		dol_print_error($db);
	}
}
// Useless with BOOKKEEPING
//elseif ($modecompta == 'BOOKKEEPING') {
//}


/*
 * Request in mode BOOKKEEPING
 */

if (isModEnabled('accounting') && ($modecompta == 'BOOKKEEPING')) {
	// Some shipped charts of accounts (e.g. US-BASE) split income and expense
	// accounts across more than one pcg_type value (COGS, OTHER_REVENUE,
	// OTHER_EXPENSES), unlike FR/GB-style charts which only use INCOME/EXPENSE.
	// Include those here so this report does not silently omit them.
	$sanitizedpredefinedgroupwhere = "(";
	$sanitizedpredefinedgroupwhere .= " (aa.pcg_type IN ('EXPENSE', 'COGS', 'OTHER_EXPENSES'))";
	$sanitizedpredefinedgroupwhere .= " OR ";
	$sanitizedpredefinedgroupwhere .= " (aa.pcg_type IN ('INCOME', 'OTHER_REVENUE'))";
	$sanitizedpredefinedgroupwhere .= ")";

	$charofaccountstring = getDolGlobalInt('CHARTOFACCOUNTS');
	$charofaccountstring = dol_getIdFromCode($db, getDolGlobalString('CHARTOFACCOUNTS'), 'accounting_system', 'rowid', 'pcg_version');

	$sql = "SELECT b.doc_ref, b.numero_compte, b.subledger_account, b.subledger_label, aa.pcg_type, date_format(b.doc_date,'%Y-%m') as dm, sum(b.debit) as debit, sum(b.credit) as credit, sum(b.montant) as amount";
	$sql .= " FROM ".MAIN_DB_PREFIX."accounting_bookkeeping as b, ".MAIN_DB_PREFIX."accounting_account as aa";
	$sql .= " WHERE b.entity = ".((int) $conf->entity);
	$sql .= " AND aa.entity = ".((int) $conf->entity);
	$sql .= " AND b.numero_compte = aa.account_number";
	$sql .= " AND ".$sanitizedpredefinedgroupwhere;
	$sql .= " AND fk_pcg_version = '".$db->escape($charofaccountstring)."'";
	if (!empty($date_start) && !empty($date_end)) {
		$sql .= " AND b.doc_date >= '".$db->idate($date_start)."' AND b.doc_date <= '".$db->idate($date_end)."'";
	}
	$sql .= " GROUP BY b.doc_ref, b.numero_compte, b.subledger_account, b.subledger_label, pcg_type, dm";
	//print $sql;

	$subtotal_ht = 0;
	$subtotal_ttc = 0;

	dol_syslog("get bookkeeping record");
	$result = $db->query($sql);
	if ($result) {
		$num = $db->num_rows($result);
		$i = 0;
		if ($num) {
			while ($i < $num) {
				$obj = $db->fetch_object($result);

				if (in_array($obj->pcg_type, array('INCOME', 'OTHER_REVENUE'))) {
					if (!isset($encaiss[$obj->dm])) {
						$encaiss[$obj->dm] = 0;	// To avoid warning of var not defined
					}
					$encaiss[$obj->dm] += $obj->credit;
					$encaiss[$obj->dm] -= $obj->debit;
				}
				if (in_array($obj->pcg_type, array('EXPENSE', 'COGS', 'OTHER_EXPENSES'))) {
					if (!isset($decaiss[$obj->dm])) {
						$decaiss[$obj->dm] = 0;	// To avoid warning of var not defined
					}
					$decaiss[$obj->dm] += $obj->debit;
					$decaiss[$obj->dm] -= $obj->credit;
				}

				// ???
				if (!isset($encaiss_ttc[$obj->dm])) {
					$encaiss_ttc[$obj->dm] = 0;
				}
				if (!isset($decaiss_ttc[$obj->dm])) {
					$decaiss_ttc[$obj->dm] = 0;
				}
				$encaiss_ttc[$obj->dm] += 0;
				$decaiss_ttc[$obj->dm] += 0;

				$i++;
			}
		}
	} else {
		dol_print_error($db);
	}
}



$action = "balance";
$object = array(&$encaiss, &$encaiss_ttc, &$decaiss, &$decaiss_ttc);
$parameters = array();
$parameters["mode"] = $modecompta;
// Initialize a technical object to manage hooks of expenses. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('externalbalance'));
$reshook = $hookmanager->executeHooks('addReportInfo', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks



/*
 * Show result array
 */

$totentrees = array();
$totsorties = array();
$year_end_for_table = ($year_end - (getDolGlobalInt('SOCIETE_FISCAL_MONTH_START') > 1 ? 1 : 0));

if ($isexport) {
	$outputlangs = $langs;
	$grid = resultatExportGrid($outputlangs, $modecompta, $year_start, $year_end_for_table, $encaiss, $encaiss_ttc, $decaiss, $decaiss_ttc);
	$ht = ($modecompta == 'CREANCES-DETTES' || $modecompta == 'BOOKKEEPING');
	$reporttitle = $outputlangs->transnoentities("ReportInOut").', '.$outputlangs->transnoentities("ByYear");
	$modelabel = $outputlangs->transnoentities($modecompta == 'BOOKKEEPING' ? "CalcModeBookkeeping" : ($ht ? "CalcModeDebt" : "CalcModePayment"));
	$periodlabel = reportPeriodLabel($outputlangs, $date_start, $date_end);
	$totallabel = $outputlangs->transnoentities($ht ? "Total" : "TotalTTC");
	$nbyears = count($grid['years']);

	if ($exportaction == 'exportpdf') {
		$pdfinit = reportPdfInit($outputlangs, $reporttitle, $reporttitle, 'L');
		$pdf = $pdfinit['pdf'];
		$margin = $pdfinit['margin'];
		$pageheight = $pdfinit['pageheight'];
		$usablewidth = $pdfinit['usablewidth'];
		$fontsize = $pdfinit['fontsize'];
		$wmonth = round($usablewidth * 0.15, 2);
		$wcol = round(($usablewidth - $wmonth) / max(1, 2 * $nbyears), 2);

		$posy = reportPdfCompanyHeader($pdf, $outputlangs, $mysoc, $margin, $fontsize);

		// Title block
		$pdf->SetXY($margin, $posy);
		$pdf->SetFont('', 'B', $fontsize + 3);
		$pdf->Cell($usablewidth, 8, $outputlangs->convToOutputCharset($reporttitle), 0, 1, 'L');
		$pdf->SetFont('', '', $fontsize - 1);
		$pdf->Cell($usablewidth, 5, $outputlangs->transnoentities("CalculationMode").': '.$outputlangs->convToOutputCharset($modelabel), 0, 1, 'L');
		$pdf->Cell($usablewidth, 5, $outputlangs->transnoentities("ReportPeriod").': '.$outputlangs->convToOutputCharset($periodlabel), 0, 1, 'L');
		$pdf->Cell($usablewidth, 5, $outputlangs->transnoentities("GeneratedOn").': '.dol_print_date(dol_now(), 'dayhour', 'tzuser', $outputlangs), 0, 1, 'L');
		$pdf->Ln(3);

		resultatPdfTableHeader($pdf, $outputlangs, $grid['years'], $wmonth, $wcol, $fontsize);

		$rowheight = 5;
		$pdfrows = $grid['months'];
		$pdfrows[] = array('label' => $totallabel, 'cells' => $grid['totals'], 'bold' => 1);
		foreach ($pdfrows as $pdfrow) {
			if ($pdf->GetY() + $rowheight > $pageheight - $margin) {
				$pdf->AddPage();
				resultatPdfTableHeader($pdf, $outputlangs, $grid['years'], $wmonth, $wcol, $fontsize);
			}
			$pdf->SetFont('', (empty($pdfrow['bold']) ? '' : 'B'), $fontsize - 1);
			$pdf->Cell($wmonth, $rowheight, $outputlangs->convToOutputCharset($pdfrow['label']), 1, 0, 'L', false, '', 1);
			foreach ($pdfrow['cells'] as $cell) {
				$pdf->Cell($wcol, $rowheight, ($cell['out'] !== null ? price($cell['out'], 0, $outputlangs) : ''), 1, 0, 'R');
				$pdf->Cell($wcol, $rowheight, ($cell['in'] !== null ? price($cell['in'], 0, $outputlangs) : ''), 1, 0, 'R');
			}
			$pdf->Ln();
		}

		// Accounting result
		if ($pdf->GetY() + $rowheight > $pageheight - $margin) {
			$pdf->AddPage();
		}
		$pdf->SetFont('', 'B', $fontsize - 1);
		$pdf->Cell($wmonth, $rowheight, $outputlangs->transnoentities("AccountingResult"), 1, 0, 'L', false, '', 1);
		foreach ($grid['result'] as $result) {
			$pdf->Cell(2 * $wcol, $rowheight, ($result !== null ? price($result, 0, $outputlangs) : ''), 1, 0, 'R');
		}
		$pdf->Ln();

		$pdf->Output(reportFileName($reporttitle, '', 'pdf'), 'I');

		$db->close();
		exit;
	}

	if ($exportaction == 'exportxlsx') {
		if (!class_exists('ZipArchive')) {
			$langs->load("errors");
			setEventMessages($langs->trans('ErrorPHPNeedModule', 'zip'), null, 'errors');
			header('Location: '.$_SERVER["PHP_SELF"].'?'.ltrim($exportparam, '&'));
			exit;
		}

		$spreadsheet = reportXlsxInit($outputlangs, $reporttitle, $outputlangs->transnoentitiesnoconv("ReportInOut"));
		$sheet = $spreadsheet->getActiveSheet();
		$lastcol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(1 + 2 * $nbyears);

		// Header: logo on the left, company info on the right (same as the PDF)
		$headrows = reportXlsxCompanyHeader($sheet, $outputlangs, $mysoc, 'D', ($nbyears > 1 ? $lastcol : 'G'));

		// Title block
		$row = $headrows + 2;
		$sheet->setCellValueExplicit('A'.$row, $reporttitle, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
		$sheet->getStyle('A'.$row)->getFont()->setBold(true)->setSize(14);
		$row++;
		foreach (array("CalculationMode" => $modelabel, "ReportPeriod" => $periodlabel, "GeneratedOn" => dol_print_date(dol_now(), 'dayhour', 'tzuser', $outputlangs)) as $key => $value) {
			$sheet->setCellValueExplicit('A'.$row, $outputlangs->transnoentitiesnoconv($key).': '.$value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
			$row++;
		}
		$row++;

		// Table header: years, then expense and income per year
		$headrow = $row;
		$sheet->setCellValue('A'.$headrow, $outputlangs->transnoentitiesnoconv("Month"));
		$sheet->mergeCells('A'.$headrow.':A'.($headrow + 1));
		$i = 0;
		foreach ($grid['years'] as $yearlabel) {
			$c0 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(2 + 2 * $i);
			$c1 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(3 + 2 * $i);
			$sheet->setCellValueExplicit($c0.$headrow, $yearlabel, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
			$sheet->mergeCells($c0.$headrow.':'.$c1.$headrow);
			$sheet->getStyle($c0.$headrow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
			$sheet->setCellValue($c0.($headrow + 1), $outputlangs->transnoentitiesnoconv("Outcome"));
			$sheet->setCellValue($c1.($headrow + 1), $outputlangs->transnoentitiesnoconv("Income"));
			$sheet->getColumnDimension($c0)->setWidth(14);
			$sheet->getColumnDimension($c1)->setWidth(14);
			$i++;
		}
		$sheet->getColumnDimension('A')->setWidth(16);
		$sheet->getStyle('A'.$headrow.':'.$lastcol.($headrow + 1))->getFont()->setBold(true);
		$sheet->getStyle('B'.($headrow + 1).':'.$lastcol.($headrow + 1))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
		$sheet->freezePane('B'.($headrow + 2));

		$firstrow = $headrow + 2;
		$row = $firstrow;
		foreach ($grid['months'] as $monthrow) {
			$sheet->setCellValueExplicit('A'.$row, $monthrow['label'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
			$i = 0;
			foreach ($monthrow['cells'] as $cell) {
				if ($cell['out'] !== null) {
					$sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(2 + 2 * $i).$row, $cell['out']);
				}
				if ($cell['in'] !== null) {
					$sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(3 + 2 * $i).$row, $cell['in']);
				}
				$i++;
			}
			$row++;
		}
		$last = $row - 1;

		// Totals and accounting result as formulas
		$totalrow = $row;
		$resultrow = $row + 1;
		$sheet->setCellValueExplicit('A'.$totalrow, $totallabel, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
		$sheet->setCellValueExplicit('A'.$resultrow, $outputlangs->transnoentitiesnoconv("AccountingResult"), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
		$i = 0;
		foreach ($grid['totals'] as $annee => $total) {
			$c0 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(2 + 2 * $i);
			$c1 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(3 + 2 * $i);
			// Empty like on screen when there is nothing for the year
			if ($total['out'] !== null) {
				$sheet->setCellValue($c0.$totalrow, '=SUM('.$c0.$firstrow.':'.$c0.$last.')');
			}
			if ($total['in'] !== null) {
				$sheet->setCellValue($c1.$totalrow, '=SUM('.$c1.$firstrow.':'.$c1.$last.')');
			}
			if ($grid['result'][$annee] !== null) {
				$sheet->setCellValue($c0.$resultrow, '='.$c1.$totalrow.'-'.$c0.$totalrow);
			}
			$sheet->mergeCells($c0.$resultrow.':'.$c1.$resultrow);
			$i++;
		}
		$sheet->getStyle('A'.$totalrow.':'.$lastcol.$resultrow)->getFont()->setBold(true);
		$sheet->getStyle('B'.$firstrow.':'.$lastcol.$resultrow)->getNumberFormat()->setFormatCode('#,##0.00');

		// Print landscape on one page width
		$sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
		$sheet->getPageSetup()->setFitToPage(true);
		$sheet->getPageSetup()->setFitToWidth(1);
		$sheet->getPageSetup()->setFitToHeight(0);

		reportXlsxOutput($spreadsheet, reportFileName($reporttitle, '', 'xlsx'));

		$db->close();
		exit;
	}
}

print '<div class="div-table-responsive">';
print '<table class="tagtable liste">'."\n";

print '<tr class="liste_titre"><td class="liste_titre">&nbsp;</td>';

for ($annee = $year_start; $annee <= $year_end_for_table; $annee++) {
	print '<td align="center" colspan="2" class="liste_titre borderrightlight">';
	print '<a href="clientfourn.php?year='.((int) $annee).'">';
	print $annee;
	if (getDolGlobalInt('SOCIETE_FISCAL_MONTH_START') > 1) {
		print '-'.($annee + 1);
	}
	print '</a></td>';
}
print '</tr>';
print '<tr class="liste_titre"><td class="liste_titre">'.$langs->trans("Month").'</td>';
// Loop on each year to output
for ($annee = $year_start; $annee <= $year_end_for_table; $annee++) {
	print '<td class="liste_titre" align="center">';
	$htmlhelp = '';
	// if ($modecompta == 'RECETTES-DEPENSES') $htmlhelp=$langs->trans("PurchasesPlusVATEarnedAndDue");
	print $form->textwithpicto($langs->trans("Outcome"), $htmlhelp);
	print '</td>';
	print '<td class="liste_titre" align="center" class="borderrightlight">';
	$htmlhelp = '';
	// if ($modecompta == 'RECETTES-DEPENSES') $htmlhelp=$langs->trans("SalesPlusVATToRetrieve");
	print $form->textwithpicto($langs->trans("Income"), $htmlhelp);
	print '</td>';
}
print '</tr>';


// Loop on each month
$nb_mois_decalage = $conf->global->SOCIETE_FISCAL_MONTH_START ? ($conf->global->SOCIETE_FISCAL_MONTH_START - 1) : 0;
for ($mois = 1 + $nb_mois_decalage; $mois <= 12 + $nb_mois_decalage; $mois++) {
	$mois_modulo = $mois;
	if ($mois > 12) {
		$mois_modulo = $mois - 12;
	}

	print '<tr class="oddeven">';
	print "<td>".dol_print_date(dol_mktime(12, 0, 0, $mois_modulo, 1, $year_start), "%B")."</td>";
	for ($annee = $year_start; $annee <= $year_end_for_table; $annee++) {
		$annee_decalage = $annee;
		if ($mois > 12) {
			$annee_decalage = $annee + 1;
		}
		//$case = strftime("%Y-%m", dol_mktime(12, 0, 0, $mois_modulo, 1, $annee_decalage));
		$case = dol_print_date(dol_mktime(12, 0, 0, $mois_modulo, 1, $annee_decalage), "%Y-%m");
		print '<td class="right">';
		if ($modecompta == 'CREANCES-DETTES' || $modecompta == 'BOOKKEEPING') {
			if (isset($decaiss[$case]) && $decaiss[$case] != 0) {
				print '<a href="clientfourn.php?year='.$annee_decalage.'&month='.$mois_modulo.'&modecompta='.$modecompta.'">'.price(price2num($decaiss[$case], 'MT')).'</a>';
				if (!isset($totsorties[$annee])) {
					$totsorties[$annee] = 0;
				}
				$totsorties[$annee] += $decaiss[$case];
			}
		} else {
			if (isset($decaiss_ttc[$case]) && $decaiss_ttc[$case] != 0) {
				print '<a href="clientfourn.php?year='.$annee_decalage.'&month='.$mois_modulo.($modecompta ? '&modecompta='.$modecompta : '').'">'.price(price2num($decaiss_ttc[$case], 'MT')).'</a>';
				if (!isset($totsorties[$annee])) {
					$totsorties[$annee] = 0;
				}
				$totsorties[$annee] += $decaiss_ttc[$case];
			}
		}
		print "</td>";

		print '<td class="borderrightlight nowrap right">';
		if ($modecompta == 'CREANCES-DETTES' || $modecompta == 'BOOKKEEPING') {
			if (isset($encaiss[$case])) {
				print '<a href="clientfourn.php?year='.$annee_decalage.'&month='.$mois_modulo.'&modecompta='.$modecompta.'">'.price(price2num($encaiss[$case], 'MT')).'</a>';
				if (!isset($totentrees[$annee])) {
					$totentrees[$annee] = 0;
				}
				$totentrees[$annee] += $encaiss[$case];
			}
		} else {
			if (isset($encaiss_ttc[$case])) {
				print '<a href="clientfourn.php?year='.$annee_decalage.'&month='.$mois_modulo.($modecompta ? '&modecompta='.$modecompta : '').'">'.price(price2num($encaiss_ttc[$case], 'MT')).'</a>';
				if (!isset($totentrees[$annee])) {
					$totentrees[$annee] = 0;
				}
				$totentrees[$annee] += $encaiss_ttc[$case];
			}
		}
		print "</td>";
	}

	print '</tr>';
}

// Total

$nbcols = 0;
print '<tr class="liste_total impair"><td>';
if ($modecompta == 'CREANCES-DETTES' || $modecompta == 'BOOKKEEPING') {
	print $langs->trans("Total");
} else {
	print $langs->trans("TotalTTC");
}
print '</td>';
for ($annee = $year_start; $annee <= $year_end_for_table; $annee++) {
	$nbcols += 2;
	print '<td class="nowrap right">'.(isset($totsorties[$annee]) ? price(price2num($totsorties[$annee], 'MT')) : '&nbsp;').'</td>';
	print '<td class="nowrap right" style="border-right: 1px solid #DDD">'.(isset($totentrees[$annee]) ? price(price2num($totentrees[$annee], 'MT')) : '&nbsp;').'</td>';
}
print "</tr>\n";

// Empty line
print '<tr class="impair"><td>&nbsp;</td>';
print '<td colspan="'.$nbcols.'">&nbsp;</td>';
print "</tr>\n";

// Balance

print '<tr class="liste_total"><td>'.$langs->trans("AccountingResult").'</td>';
for ($annee = $year_start; $annee <= $year_end_for_table; $annee++) {
	print '<td colspan="2" class="borderrightlight right"> ';
	if (isset($totentrees[$annee]) || isset($totsorties[$annee])) {
		$in = (isset($totentrees[$annee]) ? price2num($totentrees[$annee], 'MT') : 0);
		$out = (isset($totsorties[$annee]) ? price2num($totsorties[$annee], 'MT') : 0);
		print price(price2num($in - $out, 'MT')).'</td>';
		//  print '<td>&nbsp;</td>';
	}
}
print "</tr>\n";

print "</table>";
print '</div>';

// End of page
llxFooter();
$db->close();
