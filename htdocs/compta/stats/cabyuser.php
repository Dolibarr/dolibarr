<?php
/* Copyright (C) 2001-2003  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2016  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2013       Antoine Iauch           <aiauch@gpcsolutions.fr>
 * Copyright (C) 2018-2024	Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2022       Alexandre Spangaro      <aspangaro@open-dsi.fr>
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
 *      \file        htdocs/compta/stats/cabyuser.php
 *      \brief       Page reporting Salesover by user
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/report.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/tax.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

// Load translation files required by the page
$langs->load("accountancy");

$socid = GETPOSTINT('socid');

// Security check
if ($user->socid > 0) {
	$socid = $user->socid;
}
$hookmanager->initHooks(['cabyuserreportlist']);
if (isModEnabled('comptabilite')) {
	$result = restrictedArea($user, 'compta', '', '', 'resultat');
}
if (isModEnabled('accounting')) {
	$result = restrictedArea($user, 'accounting', '', '', 'comptarapport');
}
// Define modecompta ('CREANCES-DETTES' or 'RECETTES-DEPENSES')
$modecompta = getDolGlobalString('ACCOUNTING_MODE');
if (GETPOST("modecompta")) {
	$modecompta = GETPOST("modecompta");
}

$sortorder = GETPOST("sortorder", 'aZ09comma');
$sortfield = GETPOST("sortfield", 'aZ09comma');
if (!$sortorder) {
	$sortorder = "asc";
}
if (!$sortfield) {
	$sortfield = "name";
}

// Date range
$year = GETPOSTINT("year");
$month = GETPOSTINT("month");
$date_startyear = GETPOSTINT("date_startyear");
$date_startmonth = GETPOSTINT("date_startmonth");
$date_startday = GETPOSTINT("date_startday");
$date_endyear = GETPOSTINT("date_endyear");
$date_endmonth = GETPOSTINT("date_endmonth");
$date_endday = GETPOSTINT("date_endday");
if (empty($year)) {
	$year_current = dol_print_date(dol_now(), '%Y');
	$month_current = dol_print_date(dol_now(), '%m');
	$year_start = $year_current;
} else {
	$year_current = $year;
	$month_current = dol_print_date(dol_now(), '%m');
	$year_start = $year;
}
$date_start = dol_mktime(0, 0, 0, GETPOST("date_startmonth"), GETPOST("date_startday"), GETPOST("date_startyear"), 'tzserver');	// We use timezone of server so report is same from everywhere
$date_end = dol_mktime(23, 59, 59, GETPOST("date_endmonth"), GETPOST("date_endday"), GETPOST("date_endyear"), 'tzserver');		// We use timezone of server so report is same from everywhere
// Quarter
if (empty($date_start) || empty($date_end)) { // We define date_start and date_end
	$q = GETPOST("q") ? GETPOST("q") : 0;
	if ($q == 0) {
		// We define date_start and date_end
		$month_start = GETPOST("month") ? GETPOST("month") : getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1);
		$year_end = $year_start;
		$month_end = $month_start;
		if (!GETPOST("month")) {	// If month not forced
			if (!GETPOST('year') && $month_start > $month_current) {
				$year_start--;
				$year_end--;
			}
			$month_end = $month_start - 1;
			if ($month_end < 1) {
				$month_end = 12;
			} else {
				$year_end++;
			}
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
} else {
	// TODO We define q
}
// $date_start and $date_end are defined. We force $year_start and $nbofyear
$tmps = dol_getdate($date_start);
$year_start = $tmps['year'];
$tmpe = dol_getdate($date_end);
$year_end = $tmpe['year'];
$nbofyear = ($year_end - $year_start) + 1;

$commonparams = array();
$commonparams['modecompta'] = $modecompta;
$commonparams['sortorder'] = $sortorder;
$commonparams['sortfield'] = $sortfield;

$headerparams = array();
if (!empty($date_startyear)) {
	$headerparams['date_startyear'] = $date_startyear;
}
if (!empty($date_startmonth)) {
	$headerparams['date_startmonth'] = $date_startmonth;
}
if (!empty($date_startday)) {
	$headerparams['date_startday'] = $date_startday;
}
if (!empty($date_endyear)) {
	$headerparams['date_endyear'] = $date_endyear;
}
if (!empty($date_endmonth)) {
	$headerparams['date_endmonth'] = $date_endmonth;
}
if (!empty($date_endday)) {
	$headerparams['date_endday'] = $date_endday;
}
if (!empty($q)) {
	$headerparams['q'] = $q;
}

$tableparams = array();

// Adding common parameters
$allparams = array_merge($commonparams, $headerparams, $tableparams);
$headerparams = array_merge($commonparams, $headerparams);
$tableparams = array_merge($commonparams, $tableparams);

$paramslink = "";
foreach ($allparams as $key => $value) {
	$paramslink .= '&'.$key.'='.$value;
}

/*
 * View
 */

llxHeader();

$form = new Form($db);

// TODO Report from bookkeeping not yet available, so we switch on report on business events
if ($modecompta == "BOOKKEEPING") {
	$modecompta = "CREANCES-DETTES";
}
if ($modecompta == "BOOKKEEPINGCOLLECTED") {
	$modecompta = "RECETTES-DEPENSES";
}

$exportlink = "";
$namelink = "";

// Show report header
if ($modecompta == "CREANCES-DETTES") {
	$name = $langs->trans("Turnover").', '.$langs->trans("ByUserAuthorOfInvoice");
	$calcmode = $langs->trans("CalcModeDebt");
	//$calcmode.='<br>('.$langs->trans("SeeReportInInputOutputMode",'<a href="'.$_SERVER["PHP_SELF"].'?year='.$year_start.'&modecompta=RECETTES-DEPENSES">','</a>').')';
	$description = $langs->trans("RulesCADue");
	if (getDolGlobalString('FACTURE_DEPOSITS_ARE_JUST_PAYMENTS')) {
		$description .= $langs->trans("DepositsAreNotIncluded");
	} else {
		$description .= $langs->trans("DepositsAreIncluded");
	}
	$builddate = dol_now();
	//$exportlink=$langs->trans("NotYetAvailable");
} elseif ($modecompta == "RECETTES-DEPENSES") {
	$name = $langs->trans("TurnoverCollected").', '.$langs->trans("ByUserAuthorOfInvoice");
	$calcmode = $langs->trans("CalcModePayment");
	//$calcmode.='<br>('.$langs->trans("SeeReportInDueDebtMode",'<a href="'.$_SERVER["PHP_SELF"].'?year='.$year_start.'&modecompta=CREANCES-DETTES">','</a>').')';
	$description = $langs->trans("RulesCAIn");
	$description .= $langs->trans("DepositsAreIncluded");
	$builddate = dol_now();
	//$exportlink=$langs->trans("NotYetAvailable");
} elseif ($modecompta == "BOOKKEEPING") {
	// TODO
} elseif ($modecompta == "BOOKKEEPINGCOLLECTED") {
	// TODO
}
$period = $form->selectDate($date_start, 'date_start', 0, 0, 0, '', 1, 0, 0, '', '', '', '', 1, '', '', 'tzserver');
$period .= ' - ';
$period .= $form->selectDate($date_end, 'date_end', 0, 0, 0, '', 1, 0, 0, '', '', '', '', 1, '', '', 'tzserver');
if ($date_end == dol_time_plus_duree($date_start, 1, 'y') - 1) {
	$periodlink = '<a href="'.$_SERVER["PHP_SELF"].'?year='.($year_start - 1).'&modecompta='.$modecompta.'">'.img_previous().'</a> <a href="'.$_SERVER["PHP_SELF"].'?year='.($year_start + 1).'&modecompta='.$modecompta.'">'.img_next().'</a>';
} else {
	$periodlink = '';
}

$moreparam = array();
if (!empty($modecompta)) {
	$moreparam['modecompta'] = $modecompta;
}

report_header($name, $namelink, $period, $periodlink, $description, $builddate, $exportlink, $moreparam, $calcmode);

if (isModEnabled('accounting') && $modecompta != 'BOOKKEEPING') {
	print info_admin($langs->trans("WarningReportNotReliable"), 0, 0, '1');
}


$name = array();

// Show array
print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">'."\n";
// Extra parameters management
foreach ($headerparams as $key => $value) {
	print '<input type="hidden" name="'.$key.'" value="'.$value.'">';
}

$catotal = 0;
$catotal_ht = 0;

if ($modecompta == 'CREANCES-DETTES') {
	$sql = "SELECT u.rowid as rowid, u.lastname as name, u.firstname as firstname, sum(f.total_ht) as amount, sum(f.total_ttc) as amount_ttc";
	$sql .= " FROM ".MAIN_DB_PREFIX."user as u";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."facture as f ON f.fk_user_author = u.rowid";
	$sql .= " WHERE f.fk_statut in (1,2)";
	if (getDolGlobalString('FACTURE_DEPOSITS_ARE_JUST_PAYMENTS')) {
		$sql .= " AND f.type IN (0,1,2,5)";
	} else {
		$sql .= " AND f.type IN (0,1,2,3,5)";
	}
	if ($date_start && $date_end) {
		$sql .= " AND f.datef >= '".$db->idate($date_start)."' AND f.datef <= '".$db->idate($date_end)."'";
	}
} elseif ($modecompta == "RECETTES-DEPENSES") {
	/*
	 * List of payments (old payments are not seen by this query because on older versions,
	 * they were not linked via the table llx_paiement_facture. They are added later)
	 */
	$sql = "SELECT u.rowid as rowid, u.lastname as name, u.firstname as firstname, sum(pf.amount) as amount_ttc";
	$sql .= " FROM ".MAIN_DB_PREFIX."user as u";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."facture as f ON f.fk_user_author = u.rowid ";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."paiement_facture as pf ON pf.fk_facture = f.rowid";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."paiement as p ON p.rowid = pf.fk_paiement";
	$sql .= " WHERE 1=1";
	if ($date_start && $date_end) {
		$sql .= " AND p.datep >= '".$db->idate($date_start)."' AND p.datep <= '".$db->idate($date_end)."'";
	}
} elseif ($modecompta == "BOOKKEEPING") {
} elseif ($modecompta == "BOOKKEEPINGCOLLECTED") {
}
$sql .= " AND f.entity IN (".getEntity('invoice').")";
if ($socid) {
	$sql .= " AND f.fk_soc = ".((int) $socid);
}
$sql .= " GROUP BY u.rowid, u.lastname, u.firstname";
$sql .= " ORDER BY u.rowid";

$amount = array();
$amount_ht = array();

dol_syslog("cabyuser", LOG_DEBUG);
$result = $db->query($sql);
if ($result) {
	$num = $db->num_rows($result);
	$i = 0;
	while ($i < $num) {
		$obj = $db->fetch_object($result);

		$amount_ht[$obj->rowid] = (empty($obj->amount) ? 0 : $obj->amount);
		$amount[$obj->rowid] = $obj->amount_ttc;
		$name[$obj->rowid] = $obj->name.' '.$obj->firstname;

		$catotal_ht += (empty($obj->amount) ? 0 : $obj->amount);
		$catotal += $obj->amount_ttc;
		$i++;
	}
} else {
	dol_print_error($db);
}

// Adding old-version payments, non-bound by table llx_paiement_facture then without User
if ($modecompta == 'RECETTES-DEPENSES') {
	$sql = "SELECT -1 as rowidx, '' as name, '' as firstname, sum(DISTINCT p.amount) as amount_ttc";
	$sql .= " FROM ".MAIN_DB_PREFIX."bank as b";
	$sql .= ", ".MAIN_DB_PREFIX."bank_account as ba";
	$sql .= ", ".MAIN_DB_PREFIX."paiement as p";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."paiement_facture as pf ON p.rowid = pf.fk_paiement";
	$sql .= " WHERE pf.rowid IS NULL";
	$sql .= " AND p.fk_bank = b.rowid";
	$sql .= " AND b.fk_account = ba.rowid";
	$sql .= " AND ba.entity IN (".getEntity('bank_account').")";
	if ($date_start && $date_end) {
		$sql .= " AND p.datep >= '".$db->idate($date_start)."' AND p.datep <= '".$db->idate($date_end)."'";
	}
	$sql .= " GROUP BY rowidx, name, firstname";
	$sql .= " ORDER BY rowidx";

	$result = $db->query($sql);
	if ($result) {
		$num = $db->num_rows($result);
		$i = 0;
		while ($i < $num) {
			$obj = $db->fetch_object($result);
			$amount[$obj->rowidx] = $obj->amount_ttc;
			$name[$obj->rowidx] = $obj->name.' '.$obj->firstname;
			$catotal += $obj->amount_ttc;
			$i++;
		}
	} else {
		dol_print_error($db);
	}
}

$moreforfilter = '';

print '<div class="div-table-responsive">';
print '<table class="tagtable liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

print "<tr class=\"liste_titre\">";
print_liste_field_titre(
	$langs->trans("User"),
	$_SERVER["PHP_SELF"],
	"name",
	"",
	$paramslink,
	"",
	$sortfield,
	$sortorder
);
if ($modecompta == 'CREANCES-DETTES') {
	print_liste_field_titre(
		$langs->trans('AmountHT'),
		$_SERVER["PHP_SELF"],
		"amount_ht",
		"",
		$paramslink,
		'class="right"',
		$sortfield,
		$sortorder
	);
} else {
	print_liste_field_titre('');
}
print_liste_field_titre(
	$langs->trans("AmountTTC"),
	$_SERVER["PHP_SELF"],
	"amount_ttc",
	"",
	$paramslink,
	'class="right"',
	$sortfield,
	$sortorder
);
print_liste_field_titre(
	$langs->trans("Percentage"),
	$_SERVER["PHP_SELF"],
	"amount_ttc",
	"",
	$paramslink,
	'class="right"',
	$sortfield,
	$sortorder
);
print_liste_field_titre(
	$langs->trans("OtherStatistics"),
	$_SERVER["PHP_SELF"],
	"",
	"",
	"",
	'align="center" width="20%"'
);
print "</tr>\n";

if (count($amount)) {
	$arrayforsort = $name;

	// We define arrayforsort
	if ($sortfield == 'name' && $sortorder == 'asc') {
		asort($name);
		$arrayforsort = $name;
	}
	if ($sortfield == 'name' && $sortorder == 'desc') {
		arsort($name);
		$arrayforsort = $name;
	}
	if ($sortfield == 'amount_ht' && $sortorder == 'asc') {
		asort($amount_ht);
		$arrayforsort = $amount_ht;
	}
	if ($sortfield == 'amount_ht' && $sortorder == 'desc') {
		arsort($amount_ht);
		$arrayforsort = $amount_ht;
	}
	if ($sortfield == 'amount_ttc' && $sortorder == 'asc') {
		asort($amount);
		$arrayforsort = $amount;
	}
	if ($sortfield == 'amount_ttc' && $sortorder == 'desc') {
		arsort($amount);
		$arrayforsort = $amount;
	}

	$i = 0;

	foreach ($arrayforsort as $key => $value) {
		print '<tr class="oddeven">';

		// Third party
		$fullname = $name[$key];
		if ($key >= 0) {
			$linkname = '<a href="'.DOL_URL_ROOT.'/user/card.php?id='.$key.'">'.img_object($langs->trans("ShowUser"), 'user').' '.$fullname.'</a>';
		} else {
			$linkname = $langs->trans("PaymentsNotLinkedToUser");
		}
		print "<td>".$linkname."</td>\n";

		// Amount w/o VAT
		print '<td class="right">';
		if ($modecompta == 'RECETTES-DEPENSES') {
			if ($key > 0) {
				//print '<a href="'.DOL_URL_ROOT.'/compta/paiement/list.php?userid='.$key.'">';
			} else {
				//print '<a href="'.DOL_URL_ROOT.'/compta/paiement/list.php?userid=-1">';
			}
		} elseif ($modecompta == 'CREANCES-DETTES') {
			if ($key > 0) {
				print '<a href="'.DOL_URL_ROOT.'/compta/facture/list.php?userid='.$key.'">';
			} else {
				//print '<a href="#">';
			}
			print price($amount_ht[$key]);
			if ($key > 0) {
				print '</a>';
			}
		}
		print '</td>';

		// Amount with VAT
		print '<td class="right">';
		if ($modecompta == 'RECETTES-DEPENSES') {
			if ($key > 0) {
				//print '<a href="'.DOL_URL_ROOT.'/compta/paiement/list.php?userid='.$key.'">';
			} else {
				//print '<a href="'.DOL_URL_ROOT.'/compta/paiement/list.php?userid=-1">';
			}
		} elseif ($modecompta == 'CREANCES-DETTES') {
			if ($key > 0) {
				print '<a href="'.DOL_URL_ROOT.'/compta/facture/list.php?userid='.$key.'">';
			} else {
				//print '<a href="#">';
			}
		}
		print price($amount[$key]);
		if ($modecompta == 'RECETTES-DEPENSES') {
			if ($key > 0) {
				//print '</a>';
			} else {
				//print '</a>';
			}
		} elseif ($modecompta == 'CREANCES-DETTES') {
			if ($key > 0) {
				print '</a>';
			}
		}
		print '</td>';

		// Percent
		print '<td class="right">'.($catotal > 0 ? round(100 * $amount[$key] / $catotal, 2).'%' : '&nbsp;').'</td>';

		// Other stats
		print '<td class="center">';
		if (isModEnabled("propal") && $key > 0) {
			print '&nbsp;<a href="'.DOL_URL_ROOT.'/comm/propal/stats/index.php?userid='.$key.'">'.img_picto($langs->trans("ProposalStats"), "stats").'</a>&nbsp;';
		}
		if (isModEnabled('order') && $key > 0) {
			print '&nbsp;<a href="'.DOL_URL_ROOT.'/commande/stats/index.php?userid='.$key.'">'.img_picto($langs->trans("OrderStats"), "stats").'</a>&nbsp;';
		}
		if (isModEnabled('invoice') && $key > 0) {
			print '&nbsp;<a href="'.DOL_URL_ROOT.'/compta/facture/stats/index.php?userid='.$key.'">'.img_picto($langs->trans("InvoiceStats"), "stats").'</a>&nbsp;';
		}
		print '</td>';
		print "</tr>\n";
		$i++;
	}

	// Total
	print '<tr class="liste_total">';
	print '<td>'.$langs->trans("Total").'</td>';
	if ($modecompta != 'CREANCES-DETTES') {
		print '<td></td>';
	} else {
		print '<td class="right">'.price($catotal_ht).'</td>';
	}
	print '<td class="right">'.price($catotal).'</td>';
	print '<td>&nbsp;</td>';
	print '<td>&nbsp;</td>';
	print '</tr>';

	$db->free($result);
}

print "</table>";
print '</div>';
print '</form>';

// End of page
llxFooter();
$db->close();
