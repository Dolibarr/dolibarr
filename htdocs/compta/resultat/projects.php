<?php
/* Copyright (C) 2002-2006	Rodolphe Quiedeville		<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2017	Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin				<regis.houssin@inodbox.com>
 * Copyright (C) 2012		Cédric Salvador				<csalvador@gpcsolutions.fr>
 * Copyright (C) 2012-2014	Raphaël Doursenaud			<rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2014-2016	Ferran Marcet				<fmarcet@2byte.es>
 * Copyright (C) 2014		Juanjo Menent				<jmenent@2byte.es>
 * Copyright (C) 2014		Florian Henry				<florian.henry@open-concept.pro>
 * Copyright (C) 2018		Frédéric France				<frederic.france@free.fr>
 * Copyright (C) 2020		Maxime DEMAREST				<maxime@indelog.fr>
 * Copyright (C) 2021-2024	Alexandre Spangaro			<alexandre@inovea-conseil.com>
 * Copyright (C) 2024		Yoan Mollard				<ymollard@users.noreply.github.com>
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
 *  \file       htdocs/compta/resultat/projects.php
 * 	\ingroup	compta, accountancy
 *	\brief      Page reporting, grouped by project
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/tva/class/tva.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/sociales/class/chargesociales.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/report.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/tax.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingaccount.class.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountancycategory.class.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingaccount.class.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langs->loadLangs(array('compta', 'bills', 'donation', 'salaries', 'accountancy', 'loan'));

$date_startmonth = GETPOSTINT('date_startmonth');
$date_startday = GETPOSTINT('date_startday');
$date_startyear = GETPOSTINT('date_startyear');
$date_endmonth = GETPOSTINT('date_endmonth');
$date_endday = GETPOSTINT('date_endday');
$date_endyear = GETPOSTINT('date_endyear');
$showaccountdetail = GETPOST('showaccountdetail', 'aZ09') ? GETPOST('showaccountdetail', 'aZ09') : 'yes';

$search_project_ref = GETPOST('search_project_ref', 'alpha');

$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
if (empty($page) || $page == -1) {
	$page = 0;
}     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
//if (! $sortfield) $sortfield='s.nom, s.rowid';
if (!$sortorder) {
	$sortorder = 'ASC';
}

// Date range
$year = GETPOSTINT('year');		// this is used for navigation previous/next. It is the last year to show in filter
if (empty($year)) {
	$year_current = dol_print_date(dol_now(), "%Y");
	$month_current = dol_print_date(dol_now(), "%m");
	$year_start = $year_current;
} else {
	$year_current = $year;
	$month_current = dol_print_date(dol_now(), "%m");
	$year_start = $year;
}
$date_start = dol_mktime(0, 0, 0, $date_startmonth, $date_startday, $date_startyear);
$date_end = dol_mktime(23, 59, 59, $date_endmonth, $date_endday, $date_endyear);

// We define date_start and date_end
if (empty($date_start) || empty($date_end)) { // We define date_start and date_end
	$q = GETPOST("q") ? GETPOSTINT("q") : 0;
	if ($q == 0) {
		// We define date_start and date_end
		$year_end = $year_start;
		$month_start = GETPOST("month") ? GETPOSTINT("month") : getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1);
		$month_end = "";
		if (!GETPOST('month')) {
			if (!$year && $month_start > $month_current) {
				$year_start--;
				$year_end--;
			}
			if (getDolGlobalInt('SOCIETE_FISCAL_MONTH_START') > 1) {
				$month_end = $month_start - 1;
				$year_end = $year_start + 1;
			}
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
	// $modecompta = 'BOOKKEEPING';
	$modecompta = 'CREANCES-DETTES';
}
if (GETPOST("modecompta", 'alpha')) {
	$modecompta = GETPOST("modecompta", 'alpha');
}

$AccCat = new AccountancyCategory($db);

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
$hookmanager->initHooks(['customersupplierreportlist']);


/*
 * View
 */

llxHeader();

$form = new Form($db);

$periodlink = '';
$exportlink = '';

$total_ht = 0;
$total_ttc = 0;

$name = $langs->trans("ReportInOut").', '.$langs->trans("ByProjects");
$period = $form->selectDate($date_start, 'date_start', 0, 0, 0, '', 1, 0).' - '.$form->selectDate($date_end, 'date_end', 0, 0, 0, '', 1, 0);
$builddate = dol_now();

// Display report header
if ($modecompta == "CREANCES-DETTES") {
	$name = $langs->trans("ReportInOut").', '.$langs->trans("ByProjects");
	$period = $form->selectDate($date_start, 'date_start', 0, 0, 0, '', 1, 0).' - '.$form->selectDate($date_end, 'date_end', 0, 0, 0, '', 1, 0);
	$periodlink = ($year_start ? "<a href='".$_SERVER["PHP_SELF"]."?year=".($tmps['year'] - 1)."&modecompta=".$modecompta."'>".img_previous()."</a> <a href='".$_SERVER["PHP_SELF"]."?year=".($tmps['year'] + 1)."&modecompta=".$modecompta."'>".img_next()."</a>" : "");
	$builddate = dol_now();
	//$exportlink=$langs->trans("NotYetAvailable");
} elseif ($modecompta == "RECETTES-DEPENSES") {
	$name = $langs->trans("ReportInOut").', '.$langs->trans("ByProjects");
	$period = $form->selectDate($date_start, 'date_start', 0, 0, 0, '', 1, 0).' - '.$form->selectDate($date_end, 'date_end', 0, 0, 0, '', 1, 0);
	$periodlink = ($year_start ? "<a href='".$_SERVER["PHP_SELF"]."?year=".($tmps['year'] - 1)."&modecompta=".$modecompta."'>".img_previous()."</a> <a href='".$_SERVER["PHP_SELF"]."?year=".($tmps['year'] + 1)."&modecompta=".$modecompta."'>".img_next()."</a>" : "");
	$builddate = dol_now();
	//$exportlink=$langs->trans("NotYetAvailable");
} elseif ($modecompta == "BOOKKEEPING") {
	$name = $langs->trans("ReportInOut").', '.$langs->trans("ByProjects");
	$period = $form->selectDate($date_start, 'date_start', 0, 0, 0, '', 1, 0).' - '.$form->selectDate($date_end, 'date_end', 0, 0, 0, '', 1, 0);
	$arraylist = array('no'=>$langs->trans("CustomerCode"), 'yes'=>$langs->trans("AccountWithNonZeroValues"), 'all'=>$langs->trans("All"));
	$period .= ' &nbsp; &nbsp; <span class="opacitymedium">'.$langs->trans("DetailBy").'</span> '.$form->selectarray('showaccountdetail', $arraylist, $showaccountdetail, 0);
	$periodlink = ($year_start ? "<a href='".$_SERVER["PHP_SELF"]."?year=".($tmps['year'] - 1)."&modecompta=".$modecompta."&showaccountdetail=".$showaccountdetail."'>".img_previous()."</a> <a href='".$_SERVER["PHP_SELF"]."?year=".($tmps['year'] + 1)."&modecompta=".$modecompta."&showaccountdetail=".$showaccountdetail."'>".img_next()."</a>" : "");
	$builddate = dol_now();
	//$exportlink=$langs->trans("NotYetAvailable");
}

// Define $calcmode line
$calcmode = '';
/*
if (isModEnabled('accounting')) {
	$calcmode .= '<input type="radio" name="modecompta" id="modecompta3" value="BOOKKEEPING"'.($modecompta == 'BOOKKEEPING' ? ' checked="checked"' : '').'><label for="modecompta3"> '.$langs->trans("CalcModeBookkeeping").'</label>';
	$calcmode .= '<br>';
}
*/
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

report_header($name, '', $period, $periodlink, "", $builddate, $exportlink, array('modecompta'=>$modecompta, 'showaccountdetail'=>$showaccountdetail), $calcmode);

// Show report array
$param = '&modecompta='.urlencode($modecompta).'&showaccountdetail='.urlencode($showaccountdetail);
$search_date_url = '';
if ($date_startday) {
	$param .= '&date_startday='.$date_startday;
	$search_date_url .= '&search_date_startday='.$date_startday;
}
if ($date_startmonth) {
	$param .= '&date_startmonth='.$date_startmonth;
	$search_date_url .= '&search_date_startmonth='.$date_startmonth;
}
if ($date_startyear) {
	$param .= '&date_startyear='.$date_startyear;
	$search_date_url .= '&search_date_startyear='.$date_startyear;
}
if ($date_endday) {
	$param .= '&date_endday='.$date_endday;
	$search_date_url .= '&search_date_endday='.$date_endday;
}
if ($date_endmonth) {
	$param .= '&date_endmonth='.$date_endmonth;
	$search_date_url .= '&search_date_endmonth='.$date_endmonth;
}
if ($date_endyear) {
	$param .= '&date_endyear='.$date_endyear;
	$search_date_url .= '&search_date_endyear='.$date_endyear;
}

print '<table class="liste noborder centpercent">';
print '<tr class="liste_titre">';

if ($modecompta == 'BOOKKEEPING') {
	print_liste_field_titre("ByProjects", $_SERVER["PHP_SELF"], 'f.thirdparty_code,f.rowid', '', $param, '', $sortfield, $sortorder, 'width200 ');
} else {
	print_liste_field_titre("", $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder, 'width200 ');
}
print_liste_field_titre('');
if ($modecompta == 'BOOKKEEPING') {
	print_liste_field_titre("Amount", $_SERVER["PHP_SELF"], 'amount', '', $param, 'class="right"', $sortfield, $sortorder);
} else {
	if ($modecompta == 'CREANCES-DETTES') {
		print_liste_field_titre("AmountHT", $_SERVER["PHP_SELF"], 'amount_ht', '', $param, 'class="right"', $sortfield, $sortorder);
	} else {
		print_liste_field_titre('');  // Make 4 columns in total whatever $modecompta is
	}
	print_liste_field_titre("AmountTTC", $_SERVER["PHP_SELF"], 'amount_ttc', '', $param, 'class="right"', $sortfield, $sortorder);
}
print "</tr>\n";


$total_ht_outcome = $total_ttc_outcome = $total_ht_income = $total_ttc_income = 0;

if ($modecompta == 'BOOKKEEPING') {
	echo "<p>BOOKKEEPING mode not implemented for this report type by project.</p>";
} else {
	/*
	 * Customer invoices
	 */
	print '<tr class="trforbreak"><td colspan="4">'.$langs->trans("CustomersInvoices").'</td></tr>';

	$sql = '';
	if ($modecompta == 'CREANCES-DETTES') {
		$sql = "SELECT p.rowid as rowid, p.ref as project_name, sum(f.total_ht) as amount_ht, sum(f.total_ttc) as amount_ttc";
		$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
		$sql .= ", ".MAIN_DB_PREFIX."facture as f";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet as p ON f.fk_projet = p.rowid";
		$sql .= " WHERE f.fk_soc = s.rowid";
		$sql .= " AND f.entity IN (".getEntity('invoice').")";
		$sql .= " AND f.fk_statut IN (1,2)";
		if (getDolGlobalString('FACTURE_DEPOSITS_ARE_JUST_PAYMENTS')) {
			$sql .= " AND f.type IN (0,1,2,5)";
		} else {
			$sql .= " AND f.type IN (0,1,2,3,5)";
		}
		if (!empty($date_start) && !empty($date_end)) {
			$sql .= " AND f.datef >= '".$db->idate($date_start)."' AND f.datef <= '".$db->idate($date_end)."'";
		}
		if ($socid) {
			$sql .= " AND f.fk_soc = ".((int) $socid);
		}
		$sql .= " GROUP BY p.rowid, project_name";
		$sql .= $db->order($sortfield, $sortorder);
	} elseif ($modecompta == 'RECETTES-DEPENSES') {
		$sql = "SELECT p.rowid as rowid, p.ref as project_name, sum(pf.amount) as amount_ttc";
		$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
		$sql .= ", ".MAIN_DB_PREFIX."facture as f";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet as p ON f.fk_projet = p.rowid";
		$sql .= ", ".MAIN_DB_PREFIX."paiement_facture as pf";
		$sql .= ", ".MAIN_DB_PREFIX."paiement as pa";
		$sql .= " WHERE pa.rowid = pf.fk_paiement";
		$sql .= " AND pf.fk_facture = f.rowid";
		$sql .= " AND f.fk_soc = s.rowid";
		$sql .= " AND f.entity IN (".getEntity('invoice').")";
		if (!empty($date_start) && !empty($date_end)) {
			$sql .= " AND pa.datep >= '".$db->idate($date_start)."' AND pa.datep <= '".$db->idate($date_end)."'";
		}
		if ($socid) {
			$sql .= " AND f.fk_soc = ".((int) $socid);
		}
		$sql .= " GROUP BY p.rowid, project_name";
		$sql .= $db->order($sortfield, $sortorder);
	}

	dol_syslog("by project, get customer invoices", LOG_DEBUG);
	$result = $db->query($sql);
	if ($result) {
		$num = $db->num_rows($result);
		$i = 0;
		while ($i < $num) {
			$objp = $db->fetch_object($result);
			echo '<tr class="oddeven">';
			echo '<td>&nbsp;</td>';
			echo "<td>".$langs->trans("Project")." ";
			if (!empty($objp->project_name)) {
				echo ' <a href="'.DOL_URL_ROOT.'/projet/card.php?id='.$objp->rowid.'">'.$objp->project_name.'</a>';
			} else {
				echo $langs->trans("None");
			}
			$detailed_list_url = '';
			//$detailed_list_url .= '?search_project_ref='.urlencode($search_project_ref);
			$detailed_list_url .= empty($objp->project_name)? "!*": $objp->project_name;
			$detailed_list_url .= $search_date_url;
			echo ' (<a href="'.DOL_URL_ROOT.'/compta/facture/list.php'.$detailed_list_url.'">'.$langs->trans("DetailedListLowercase")."</a>)\n";
			echo "</td>\n";
			echo '<td class="right">';
			if ($modecompta == 'CREANCES-DETTES') {
				echo '<span class="amount">'.price($objp->amount_ht)."</span>";
			}
			echo "</td>\n";
			echo '<td class="right"><span class="amount">'.price($objp->amount_ttc)."</span></td>\n";

			$total_ht += ($objp->amount_ht ?? 0);
			$total_ttc += $objp->amount_ttc;
			echo "</tr>\n";
			$i++;
		}
		$db->free($result);
	} else {
		dol_print_error($db);
	}

	if ($total_ttc == 0) {
		echo '<tr class="oddeven">';
		echo '<td>&nbsp;</td>';
		echo '<td colspan="3"><span class="opacitymedium">'.$langs->trans("None").'</span></td>';
		echo '</tr>';
	}

	$total_ht_income += $total_ht;
	$total_ttc_income += $total_ttc;

	echo '<tr class="liste_total">';
	echo '<td></td>';
	echo '<td></td>';
	echo '<td class="right">';
	if ($modecompta == 'CREANCES-DETTES') {
		echo price($total_ht);
	}
	echo '</td>';
	echo '<td class="right">'.price($total_ttc).'</td>';
	echo '</tr>';

	/*
	 * Donations
	 */

	if (isModEnabled('don')) {
		echo '<tr class="trforbreak"><td colspan="4">'.$langs->trans("Donations").'</td></tr>';

		if ($modecompta == 'CREANCES-DETTES' || $modecompta == 'RECETTES-DEPENSES') {
			if ($modecompta == 'CREANCES-DETTES') {
				$sql = "SELECT p.rowid as rowid, p.ref as project_name, sum(d.amount) as amount";
				$sql .= " FROM ".MAIN_DB_PREFIX."don as d";
				$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet as p ON d.fk_projet = p.rowid";
				$sql .= " WHERE d.entity IN (".getEntity('donation').")";
				$sql .= " AND d.fk_statut in (1,2)";
			} else {
				$sql = "SELECT p.rowid as rowid, p.ref as project_name, sum(d.amount) as amount";
				$sql .= " FROM ".MAIN_DB_PREFIX."don as d";
				$sql .= " INNER JOIN ".MAIN_DB_PREFIX."payment_donation as pe ON pe.fk_donation = d.rowid";
				$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet as p ON d.fk_projet = p.rowid";
				$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_paiement as c ON pe.fk_typepayment = c.id";
				$sql .= " WHERE d.entity IN (".getEntity('donation').")";
				$sql .= " AND d.fk_statut >= 2";
			}
			if (!empty($date_start) && !empty($date_end)) {
				$sql .= " AND d.datedon >= '".$db->idate($date_start)."' AND d.datedon <= '".$db->idate($date_end)."'";
			}
		}
		$sql .= " GROUP BY p.rowid, p.ref";
		$newsortfield = $sortfield;
		if ($newsortfield == 's.nom, s.rowid') {
			$newsortfield = 'p.ref';
		}
		if ($newsortfield == 'amount_ht') {
			$newsortfield = 'amount';
		}
		if ($newsortfield == 'amount_ttc') {
			$newsortfield = 'amount';
		}
		$sql .= $db->order($newsortfield, $sortorder);

		dol_syslog("by project, get dunning");
		$result = $db->query($sql);
		$subtotal_ht = 0;
		$subtotal_ttc = 0;
		if ($result) {
			$num = $db->num_rows($result);
			$i = 0;
			if ($num) {
				while ($i < $num) {
					$obj = $db->fetch_object($result);

					$total_ht += $obj->amount;
					$total_ttc += $obj->amount;
					$subtotal_ht += $obj->amount;
					$subtotal_ttc += $obj->amount;

					echo '<tr class="oddeven">';
					echo '<td>&nbsp;</td>';
					$project_name = empty($obj->project_name)? $langs->trans("None"): $obj->project_name;
					echo "<td>".$langs->trans("Project")." <a href=\"".DOL_URL_ROOT."/projet/card.php?id=".$obj->ref."\">".$project_name."</a></td>\n";

					echo '<td class="right">';
					if ($modecompta == 'CREANCES-DETTES') {
						echo '<span class="amount">'.price($obj->amount).'</span>';
					}
					echo '</td>';
					echo '<td class="right"><span class="amount">'.price($obj->amount).'</span></td>';
					echo '</tr>';
					$i++;
				}
			} else {
				echo '<tr class="oddeven"><td>&nbsp;</td>';
				echo '<td colspan="3"><span class="opacitymedium">'.$langs->trans("None").'</span></td>';
				echo '</tr>';
			}
		} else {
			dol_print_error($db);
		}

		$total_ht_income += $subtotal_ht;
		$total_ttc_income += $subtotal_ttc;

		echo '<tr class="liste_total">';
		echo '<td></td>';
		echo '<td></td>';
		echo '<td class="right">';
		if ($modecompta == 'CREANCES-DETTES') {
			echo price($subtotal_ht);
		}
		echo '</td>';
		echo '<td class="right">'.price($subtotal_ttc).'</td>';
		echo '</tr>';
	}

	/*
	 * Suppliers invoices
	 */
	if ($modecompta == 'CREANCES-DETTES') {
		$sql = "SELECT p.rowid as rowid, p.ref as project_name, sum(f.total_ht) as amount_ht, sum(f.total_ttc) as amount_ttc";
		$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
		$sql .= ", ".MAIN_DB_PREFIX."facture_fourn as f";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet as p ON f.fk_projet = p.rowid";
		$sql .= " WHERE f.fk_soc = s.rowid";
		$sql .= " AND f.fk_statut IN (1,2)";
		if (getDolGlobalString('FACTURE_SUPPLIER_DEPOSITS_ARE_JUST_PAYMENTS')) {
			$sql .= " AND f.type IN (0,1,2)";
		} else {
			$sql .= " AND f.type IN (0,1,2,3)";
		}
		if (!empty($date_start) && !empty($date_end)) {
			$sql .= " AND f.datef >= '".$db->idate($date_start)."' AND f.datef <= '".$db->idate($date_end)."'";
		}
	} elseif ($modecompta == 'RECETTES-DEPENSES') {
		$sql = "SELECT pr.rowid as rowid, pr.ref as project_name, sum(pf.amount) as amount_ttc";
		$sql .= " FROM ".MAIN_DB_PREFIX."paiementfourn as p";
		$sql .= ", ".MAIN_DB_PREFIX."paiementfourn_facturefourn as pf";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."facture_fourn as f ON pf.fk_facturefourn = f.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet as pr ON f.fk_projet = pr.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON f.fk_soc = s.rowid";
		$sql .= " WHERE p.rowid = pf.fk_paiementfourn ";
		if (!empty($date_start) && !empty($date_end)) {
			$sql .= " AND p.datep >= '".$db->idate($date_start)."' AND p.datep <= '".$db->idate($date_end)."'";
		}
	}

	$sql .= " AND f.entity = ".((int) $conf->entity);
	if ($socid) {
		$sql .= " AND f.fk_soc = ".((int) $socid);
	}
	$sql .= " GROUP BY rowid, project_name";
	$sql .= $db->order($sortfield, $sortorder);

	echo '<tr class="trforbreak"><td colspan="4">'.$langs->trans("SuppliersInvoices").'</td></tr>';

	$subtotal_ht = 0;
	$subtotal_ttc = 0;
	dol_syslog("by project, get suppliers invoices", LOG_DEBUG);
	$result = $db->query($sql);
	if ($result) {
		$num = $db->num_rows($result);
		$i = 0;
		if ($num > 0) {
			while ($i < $num) {
				$objp = $db->fetch_object($result);

				echo '<tr class="oddeven">';
				echo '<td>&nbsp;</td>';

				echo "<td>".$langs->trans("Project")." ";
				if (!empty($objp->project_name)) {
					echo ' <a href="'.DOL_URL_ROOT.'/projet/card.php?id='.$objp->rowid.'">'.$objp->project_name.'</a>';
				} else {
					echo $langs->trans("None");
				}
				$detailed_list_url = '';
				//$detailed_list_url .= '?search_project='.urlencode($search_project_ref);
				$detailed_list_url .= empty($objp->project_name)? "!*": $objp->project_name;
				$detailed_list_url .= $search_date_url;
				echo ' (<a href="'.DOL_URL_ROOT.'/fourn/facture/list.php'.$detailed_list_url.'">'.$langs->trans("DetailedListLowercase")."</a>)\n";
				echo "</td>\n";

				echo '<td class="right">';
				if ($modecompta == 'CREANCES-DETTES') {
					echo '<span class="amount">'.price(-$objp->amount_ht)."</span>";
				}
				echo "</td>\n";
				echo '<td class="right"><span class="amount">'.price(-$objp->amount_ttc)."</span></td>\n";

				$total_ht -= (isset($objp->amount_ht) ? $objp->amount_ht : 0);
				$total_ttc -= $objp->amount_ttc;
				$subtotal_ht += (isset($objp->amount_ht) ? $objp->amount_ht : 0);
				$subtotal_ttc += $objp->amount_ttc;

				echo "</tr>\n";
				$i++;
			}
		} else {
			echo '<tr class="oddeven">';
			echo '<td>&nbsp;</td>';
			echo '<td colspan="3"><span class="opacitymedium">'.$langs->trans("None").'</span></td>';
			echo '</tr>';
		}

		$db->free($result);
	} else {
		dol_print_error($db);
	}

	$total_ht_outcome += $subtotal_ht;
	$total_ttc_outcome += $subtotal_ttc;

	echo '<tr class="liste_total">';
	echo '<td></td>';
	echo '<td></td>';
	echo '<td class="right">';
	if ($modecompta == 'CREANCES-DETTES') {
		echo price(-$subtotal_ht);
	}
	echo '</td>';
	echo '<td class="right">'.price(-$subtotal_ttc).'</td>';
	echo '</tr>';

	/*
	 * Salaries
	 */

	if (isModEnabled('salaries')) {
		echo '<tr class="trforbreak"><td colspan="4">'.$langs->trans("Salaries").'</td></tr>';

		if ($modecompta == 'CREANCES-DETTES' || $modecompta == 'RECETTES-DEPENSES') {
			if ($modecompta == 'CREANCES-DETTES') {
				$column = 's.dateep';	// We use the date of end of period of salary

				$sql = "SELECT p.rowid as rowid, p.ref as project_name, sum(s.amount) as amount";
				$sql .= " FROM ".MAIN_DB_PREFIX."salary as s";
				$sql .= " INNER JOIN ".MAIN_DB_PREFIX."user as u ON u.rowid = s.fk_user";
				$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet as p ON s.fk_projet = p.rowid";
				$sql .= " WHERE s.entity IN (".getEntity('salary').")";
				if (!empty($date_start) && !empty($date_end)) {
					$sql .= " AND ".$db->sanitize($column)." >= '".$db->idate($date_start)."' AND $column <= '".$db->idate($date_end)."'";
				}
			} else {
				$column = 'ps.datep';

				$sql = "SELECT pr.rowid as rowid, pr.ref as project_name, sum(ps.amount) as amount";
				$sql .= " FROM ".MAIN_DB_PREFIX."payment_salary as ps";
				$sql .= " INNER JOIN ".MAIN_DB_PREFIX."salary as s ON s.rowid = ps.fk_salary";
				$sql .= " INNER JOIN ".MAIN_DB_PREFIX."user as u ON u.rowid = s.fk_user";
				$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet as pr ON s.fk_projet = pr.rowid";
				$sql .= " WHERE ps.entity IN (".getEntity('payment_salary').")";
				if (!empty($date_start) && !empty($date_end)) {
					$sql .= " AND ".$db->sanitize($column)." >= '".$db->idate($date_start)."' AND $column <= '".$db->idate($date_end)."'";
				}
			}


			$sql .= " GROUP BY rowid, project_name";
			$newsortfield = $sortfield;
			if ($newsortfield == 's.nom, s.rowid') {
				$newsortfield = 'project_name';
			}
			if ($newsortfield == 'amount_ht') {
				$newsortfield = 'amount';
			}
			if ($newsortfield == 'amount_ttc') {
				$newsortfield = 'amount';
			}
			$sql .= $db->order($newsortfield, $sortorder);
		}

		dol_syslog("by project, get salaries");
		$result = $db->query($sql);
		$subtotal_ht = 0;
		$subtotal_ttc = 0;
		if ($result) {
			$num = $db->num_rows($result);
			$i = 0;
			if ($num) {
				while ($i < $num) {
					$obj = $db->fetch_object($result);

					$project_name = !empty($obj->project_name) ? $obj->project_name : $langs->trans("None");

					$total_ht -= $obj->amount;
					$total_ttc -= $obj->amount;
					$subtotal_ht += $obj->amount;
					$subtotal_ttc += $obj->amount;

					echo '<tr class="oddeven"><td>&nbsp;</td>';
					echo "<td>".$langs->trans("Project")." ";
					if (!empty($objp->project_name)) {
						echo ' <a href="'.DOL_URL_ROOT.'/projet/card.php?id='.$objp->rowid.'">'.$objp->project_name.'</a>';
					} else {
						echo $langs->trans("None");
					}
					echo "</td>\n";
					echo '<td class="right">';
					if ($modecompta == 'CREANCES-DETTES') {
						echo '<span class="amount">'.price(-$obj->amount).'</span>';
					}
					echo '</td>';
					echo '<td class="right"><span class="amount">'.price(-$obj->amount).'</span></td>';
					echo '</tr>';
					$i++;
				}
			} else {
				echo '<tr class="oddeven">';
				echo '<td>&nbsp;</td>';
				echo '<td colspan="3"><span class="opacitymedium">'.$langs->trans("None").'</span></td>';
				echo '</tr>';
			}
		} else {
			dol_print_error($db);
		}

		$total_ht_outcome += $subtotal_ht;
		$total_ttc_outcome += $subtotal_ttc;

		echo '<tr class="liste_total">';
		echo '<td></td>';
		echo '<td></td>';
		echo '<td class="right">';
		if ($modecompta == 'CREANCES-DETTES') {
			echo price(-$subtotal_ht);
		}
		echo '</td>';
		echo '<td class="right">'.price(-$subtotal_ttc).'</td>';
		echo '</tr>';
	}


	/*
	 * Expense report
	 */

	if (isModEnabled('expensereport')) {
		if ($modecompta == 'CREANCES-DETTES' || $modecompta == 'RECETTES-DEPENSES') {
			$langs->load('trips');
			if ($modecompta == 'CREANCES-DETTES') {
				$sql = "SELECT ed.rowid as rowid, ed.fk_projet, p.rowid as project_rowid, p.ref as project_name, sum(ed.total_ht) as amount_ht, sum(ed.total_ttc) as amount_ttc";
				$sql .= " FROM ".MAIN_DB_PREFIX."expensereport_det as ed";
				$sql .= " INNER JOIN ".MAIN_DB_PREFIX."expensereport as e ON ed.fk_expensereport = e.rowid";
				$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet as p ON ed.fk_projet = p.rowid";
				$sql .= " WHERE e.entity IN (".getEntity('expensereport').")";
				$sql .= " AND e.fk_statut >= 5";

				$column = 'e.date_valid';
			} else {
				$sql = "SELECT ed.rowid as rowid, ed.fk_projet, p.rowid as project_rowid, p.ref as project_name, sum(DISTINCT pe.amount) as amount_ht, sum(DISTINCT pe.amount) as amount_ttc";
				$sql .= " FROM ".MAIN_DB_PREFIX."expensereport_det as ed";
				$sql .= " INNER JOIN ".MAIN_DB_PREFIX."expensereport as e ON ed.fk_expensereport = e.rowid";
				$sql .= " INNER JOIN ".MAIN_DB_PREFIX."payment_expensereport as pe ON pe.fk_expensereport = e.rowid";
				$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet as p ON ed.fk_projet = p.rowid";
				$sql .= " WHERE e.entity IN (".getEntity('expensereport').")";
				$sql .= " AND e.fk_statut >= 5";

				$column = 'pe.datep';
			}

			if (!empty($date_start) && !empty($date_end)) {
				$sql .= " AND ".$db->sanitize($column)." >= '".$db->idate($date_start)."' AND $column <= '".$db->idate($date_end)."'";
			}

			$sql .= " GROUP BY ed.rowid, ed.fk_projet, p.rowid, p.ref";
			$newsortfield = $sortfield;
			if ($newsortfield == 's.nom, s.rowid') {
				$newsortfield = 'project_name';
			}
			$sql .= $db->order($newsortfield, $sortorder);
		}

		echo '<tr class="trforbreak"><td colspan="4">'.$langs->trans("ExpenseReport").'</td></tr>';

		dol_syslog("by project, get expense report outcome");
		$result = $db->query($sql);
		$subtotal_ht = 0;
		$subtotal_ttc = 0;
		if ($result) {
			$num = $db->num_rows($result);
			if ($num) {
				while ($obj = $db->fetch_object($result)) {
					$project_name = !empty($obj->project_name) ? $obj->project_name : $langs->trans("None");

					$total_ht -= $obj->amount_ht;
					$total_ttc -= $obj->amount_ttc;
					$subtotal_ht += $obj->amount_ht;
					$subtotal_ttc += $obj->amount_ttc;

					echo '<tr class="oddeven">';
					echo '<td>&nbsp;</td>';

					echo "<td>".$langs->trans("Project")." ";
					if (!empty($obj->project_name)) {
						echo ' <a href="'.DOL_URL_ROOT.'/projet/card.php?id='.$obj->project_rowid.'">'.$obj->project_name.'</a>';
					} else {
						echo $langs->trans("None");
					}
					$detailed_list_url = '?id='.$obj->project_rowid;
					$detailed_list_url .= $search_date_url;
					echo ' (<a href="'.DOL_URL_ROOT.'/projet/element.php'.$detailed_list_url.'">'.$langs->trans("DetailedListLowercase")."</a>)\n";
					echo "</td>\n";

					echo '<td class="right">';
					if ($modecompta == 'CREANCES-DETTES') {
						echo '<span class="amount">'.price(-$obj->amount_ht).'</span>';
					}
					echo '</td>';
					echo '<td class="right"><span class="amount">'.price(-$obj->amount_ttc).'</span></td>';
					echo '</tr>';
				}
			} else {
				echo '<tr class="oddeven">';
				echo '<td>&nbsp;</td>';
				echo '<td colspan="3"><span class="opacitymedium">'.$langs->trans("None").'</span></td>';
				echo '</tr>';
			}
		} else {
			dol_print_error($db);
		}

		$total_ht_outcome += $subtotal_ht;
		$total_ttc_outcome += $subtotal_ttc;

		echo '<tr class="liste_total">';
		echo '<td></td>';
		echo '<td></td>';
		echo '<td class="right">';
		if ($modecompta == 'CREANCES-DETTES') {
			echo price(-$subtotal_ht);
		}
		echo '</td>';
		echo '<td class="right">'.price(-$subtotal_ttc).'</td>';
		echo '</tr>';
	}




	/*
	 * Various Payments
	 */
	//$conf->global->ACCOUNTING_REPORTS_INCLUDE_VARPAY = 1;

	if (getDolGlobalString('ACCOUNTING_REPORTS_INCLUDE_VARPAY') && isModEnabled("bank") && ($modecompta == 'CREANCES-DETTES' || $modecompta == "RECETTES-DEPENSES")) {
		$subtotal_ht = 0;
		$subtotal_ttc = 0;

		echo '<tr class="trforbreak"><td colspan="4">'.$langs->trans("VariousPayment").'</td></tr>';

		// Debit
		$sql = "SELECT p.rowid as rowid, p.ref as project_name, SUM(p.amount) AS amount FROM ".MAIN_DB_PREFIX."payment_various as p";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet as pj ON p.fk_projet = pj.rowid";
		$sql .= ' WHERE 1 = 1';
		if (!empty($date_start) && !empty($date_end)) {
			$sql .= " AND p.datep >= '".$db->idate($date_start)."' AND p.datep <= '".$db->idate($date_end)."'";
		}
		$sql .= ' GROUP BY p.rowid, project_name';
		$sql .= ' ORDER BY project_name';

		dol_syslog('get various payments', LOG_DEBUG);
		$result = $db->query($sql);
		if ($result) {
			$num = $db->num_rows($result);
			if ($num) {
				while ($obj = $db->fetch_object($result)) {
					$project_name = !empty($obj->project_name) ? $obj->project_name : $langs->trans("None");

					// Debit (payment of suppliers for example)
					if (isset($obj->amount)) {
						$subtotal_ht += -$obj->amount;
						$subtotal_ttc += -$obj->amount;

						$total_ht_outcome += $obj->amount;
						$total_ttc_outcome += $obj->amount;
					}
					echo '<tr class="oddeven">';
					echo '<td>&nbsp;</td>';
					echo "<td>".$langs->trans("Project")." <a href=\"".DOL_URL_ROOT."/projet/card.php?id=".urlencode($obj->project_id)."\">".$project_name."</a></td>\n";
					echo '<td class="right">';
					if ($modecompta == 'CREANCES-DETTES') {
						echo '<span class="amount">'.price(-$obj->amount).'</span>';
					}
					echo '</td>';
					echo '<td class="right"><span class="amount">'.price(-$obj->amount)."</span></td>\n";
					echo "</tr>\n";

					// Credit (payment received from customer for example)
					if (isset($obj->amount)) {
						$subtotal_ht += $obj->amount;
						$subtotal_ttc += $obj->amount;

						$total_ht_income += $obj->amount;
						$total_ttc_income += $obj->amount;
					}
					echo '<tr class="oddeven"><td>&nbsp;</td>';
					echo "<td>".$langs->trans("Project")." <a href=\"".DOL_URL_ROOT."/projet/card.php?id=".urlencode($obj->project_id)."\">".$project_name."</a></td>\n";
					echo '<td class="right">';
					if ($modecompta == 'CREANCES-DETTES') {
						echo '<span class="amount">'.price($obj->amount).'</span>';
					}
					echo '</td>';
					echo '<td class="right"><span class="amount">'.price($obj->amount)."</span></td>\n";
					echo "</tr>\n";
				}
			} else {
				echo '<tr class="oddeven">';
				echo '<td>&nbsp;</td>';
				echo '<td colspan="3"><span class="opacitymedium">'.$langs->trans("None").'</span></td>';
				echo '</tr>';
			}

			// Total
			$total_ht += $subtotal_ht;
			$total_ttc += $subtotal_ttc;
			echo '<tr class="liste_total">';
			echo '<td></td>';
			echo '<td></td>';
			echo '<td class="right">';
			if ($modecompta == 'CREANCES-DETTES') {
				echo price($subtotal_ht);
			}
			echo '</td>';
			echo '<td class="right">'.price($subtotal_ttc).'</td>';
			echo '</tr>';
		} else {
			dol_print_error($db);
		}
	}

	/*
	 * Payment Loan
	 */

	if (getDolGlobalString('ACCOUNTING_REPORTS_INCLUDE_LOAN') && isModEnabled('don') && ($modecompta == 'CREANCES-DETTES' || $modecompta == "RECETTES-DEPENSES")) {
		$subtotal_ht = 0;
		$subtotal_ttc = 0;

		echo '<tr class="trforbreak"><td colspan="4">'.$langs->trans("PaymentLoan").'</td></tr>';

		$sql = 'SELECT pj.rowid as rowid, pj.ref as project_name, SUM(p.amount_capital + p.amount_insurance + p.amount_interest) as amount FROM '.MAIN_DB_PREFIX.'payment_loan as p';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'loan AS l ON l.rowid = p.fk_loan';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'projet AS pj ON l.fk_projet = pj.rowid';
		$sql .= ' WHERE 1 = 1';
		if (!empty($date_start) && !empty($date_end)) {
			$sql .= " AND p.datep >= '".$db->idate($date_start)."' AND p.datep <= '".$db->idate($date_end)."'";
		}
		$sql .= ' GROUP BY pj.rowid, project_name';
		$sql .= ' ORDER BY project_name';

		dol_syslog('get loan payments', LOG_DEBUG);
		$result = $db->query($sql);
		if ($result) {
			require_once DOL_DOCUMENT_ROOT.'/loan/class/loan.class.php';
			$loan_static = new Loan($db);
			while ($obj = $db->fetch_object($result)) {
				$project_name = !empty($obj->project_name) ? $obj->project_name : $langs->trans("None");

				echo '<tr class="oddeven"><td>&nbsp;</td>';
				echo "<td>".$langs->trans("Project")." <a href=\"".DOL_URL_ROOT."/projet/card.php?id=".urlencode($obj->project_id)."\">".$project_name."</a></td>\n";
				if ($modecompta == 'CREANCES-DETTES') {
					echo '<td class="right"><span class="amount">'.price(-$obj->amount).'</span></td>';
				}
				echo '<td class="right"><span class="amount">'.price(-$obj->amount)."</span></td>\n";
				echo "</tr>\n";
				$subtotal_ht -= $obj->amount;
				$subtotal_ttc -= $obj->amount;
			}
			$total_ht += $subtotal_ht;
			$total_ttc += $subtotal_ttc;

			$total_ht_income += $subtotal_ht;
			$total_ttc_income += $subtotal_ttc;

			echo '<tr class="liste_total">';
			echo '<td></td>';
			echo '<td></td>';
			echo '<td class="right">';
			if ($modecompta == 'CREANCES-DETTES') {
				echo price($subtotal_ht);
			}
			echo '</td>';
			echo '<td class="right">'.price($subtotal_ttc).'</td>';
			echo '</tr>';
		} else {
			dol_print_error($db);
		}
	}
}

$action = "balanceclient";
$object = array(&$total_ht, &$total_ttc);
$parameters["mode"] = $modecompta;
$parameters["date_start"] = $date_start;
$parameters["date_end"] = $date_end;
// Initialize technical object to manage hooks of expenses. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('externalbalance'));
$reshook = $hookmanager->executeHooks('addBalanceLine', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
echo $hookmanager->resPrint;



// Total
echo '<tr>';
echo '<td colspan="'.($modecompta == 'BOOKKEEPING' ? 3 : 4).'">&nbsp;</td>';
echo '</tr>';

echo '<tr class="liste_total"><td class="left" colspan="2">'.$langs->trans("Income").'</td>';
if ($modecompta == 'CREANCES-DETTES') {
	echo '<td class="liste_total right nowraponall">'.price(price2num($total_ht_income, 'MT')).'</td>';
} elseif ($modecompta == 'RECETTES-DEPENSES') {
	echo '<td></td>';
}
echo '<td class="liste_total right nowraponall">'.price(price2num($total_ttc_income, 'MT')).'</td>';
echo '</tr>';
echo '<tr class="liste_total"><td class="left" colspan="2">'.$langs->trans("Outcome").'</td>';
if ($modecompta == 'CREANCES-DETTES') {
	echo '<td class="liste_total right nowraponall">'.price(price2num(-$total_ht_outcome, 'MT')).'</td>';
} elseif ($modecompta == 'RECETTES-DEPENSES') {
	echo '<td></td>';
}
echo '<td class="liste_total right nowraponall">'.price(price2num(-$total_ttc_outcome, 'MT')).'</td>';
echo '</tr>';
echo '<tr class="liste_total"><td class="left" colspan="2">'.$langs->trans("Profit").'</td>';
if ($modecompta == 'CREANCES-DETTES') {
	echo '<td class="liste_total right nowraponall">'.price(price2num($total_ht, 'MT')).'</td>';
} elseif ($modecompta == 'RECETTES-DEPENSES') {
	echo '<td></td>';
}
echo '<td class="liste_total right nowraponall">'.price(price2num($total_ttc, 'MT')).'</td>';
echo '</tr>';

echo "</table>";
echo '<br>';

// End of page
llxFooter();

$db->close();
