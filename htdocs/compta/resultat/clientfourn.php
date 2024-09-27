<?php
/* Copyright (C) 2002-2006  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2017  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2012       Cédric Salvador         <csalvador@gpcsolutions.fr>
 * Copyright (C) 2012-2014  Raphaël Dourseanud      <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2014-2106  Ferran Marcet           <fmarcet@2byte.es>
 * Copyright (C) 2014       Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2014       Florian Henry           <florian.henry@open-concept.pro>
 * Copyright (C) 2018-2024	Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2020       Maxime DEMAREST         <maxime@indelog.fr>
 * Copyright (C) 2021       Alexandre Spangaro      <aspangaro@open-dsi.fr>
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
 *  \file       htdocs/compta/resultat/clientfourn.php
 * 	\ingroup	compta, accountancy
 *	\brief      Page reporting
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

// Load translation files required by the page
$langs->loadLangs(array('compta', 'bills', 'donation', 'salaries', 'accountancy', 'loan'));

$date_startmonth = GETPOSTINT('date_startmonth');
$date_startday = GETPOSTINT('date_startday');
$date_startyear = GETPOSTINT('date_startyear');
$date_endmonth = GETPOSTINT('date_endmonth');
$date_endday = GETPOSTINT('date_endday');
$date_endyear = GETPOSTINT('date_endyear');
$showaccountdetail = GETPOST('showaccountdetail', 'aZ09') ? GETPOST('showaccountdetail', 'aZ09') : 'yes';

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
	$modecompta = 'BOOKKEEPING';
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

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(['customersupplierreportlist']);

if (isModEnabled('comptabilite')) {
	$result = restrictedArea($user, 'compta', '', '', 'resultat');
}
if (isModEnabled('accounting')) {
	$result = restrictedArea($user, 'accounting', '', '', 'comptarapport');
}

/*
 * View
 */

llxHeader();

$form = new Form($db);

$periodlink = '';
$exportlink = '';

$total_ht = 0;
$total_ttc = 0;

// Affiche en-tete de rapport
if ($modecompta == "CREANCES-DETTES") {
	$name = $langs->trans("ReportInOut").', '.$langs->trans("ByPredefinedAccountGroups");
	$period = $form->selectDate($date_start, 'date_start', 0, 0, 0, '', 1, 0).' - '.$form->selectDate($date_end, 'date_end', 0, 0, 0, '', 1, 0);
	$periodlink = ($year_start ? "<a href='".$_SERVER["PHP_SELF"]."?year=".($tmps['year'] - 1)."&modecompta=".$modecompta."'>".img_previous()."</a> <a href='".$_SERVER["PHP_SELF"]."?year=".($tmps['year'] + 1)."&modecompta=".$modecompta."'>".img_next()."</a>" : "");
	$description = $langs->trans("RulesResultDue");
	if (getDolGlobalString('FACTURE_DEPOSITS_ARE_JUST_PAYMENTS')) {
		$description .= $langs->trans("DepositsAreNotIncluded");
	} else {
		$description .= $langs->trans("DepositsAreIncluded");
	}
	if (getDolGlobalString('FACTURE_SUPPLIER_DEPOSITS_ARE_JUST_PAYMENTS')) {
		$description .= $langs->trans("SupplierDepositsAreNotIncluded");
	}
	$builddate = dol_now();
	//$exportlink=$langs->trans("NotYetAvailable");
} elseif ($modecompta == "RECETTES-DEPENSES") {
	$name = $langs->trans("ReportInOut").', '.$langs->trans("ByPredefinedAccountGroups");
	$period = $form->selectDate($date_start, 'date_start', 0, 0, 0, '', 1, 0).' - '.$form->selectDate($date_end, 'date_end', 0, 0, 0, '', 1, 0);
	$periodlink = ($year_start ? "<a href='".$_SERVER["PHP_SELF"]."?year=".($tmps['year'] - 1)."&modecompta=".$modecompta."'>".img_previous()."</a> <a href='".$_SERVER["PHP_SELF"]."?year=".($tmps['year'] + 1)."&modecompta=".$modecompta."'>".img_next()."</a>" : "");
	$description = $langs->trans("RulesResultInOut");
	$builddate = dol_now();
	//$exportlink=$langs->trans("NotYetAvailable");
} elseif ($modecompta == "BOOKKEEPING") {
	$name = $langs->trans("ReportInOut").', '.$langs->trans("ByPredefinedAccountGroups");
	$period = $form->selectDate($date_start, 'date_start', 0, 0, 0, '', 1, 0).' - '.$form->selectDate($date_end, 'date_end', 0, 0, 0, '', 1, 0);
	$arraylist = array('no'=>$langs->trans("CustomerCode"), 'yes'=>$langs->trans("AccountWithNonZeroValues"), 'all'=>$langs->trans("All"));
	$period .= ' &nbsp; &nbsp; <span class="opacitymedium">'.$langs->trans("DetailBy").'</span> '.$form->selectarray('showaccountdetail', $arraylist, $showaccountdetail, 0);
	$periodlink = ($year_start ? "<a href='".$_SERVER["PHP_SELF"]."?year=".($tmps['year'] - 1)."&modecompta=".$modecompta."&showaccountdetail=".$showaccountdetail."'>".img_previous()."</a> <a href='".$_SERVER["PHP_SELF"]."?year=".($tmps['year'] + 1)."&modecompta=".$modecompta."&showaccountdetail=".$showaccountdetail."'>".img_next()."</a>" : "");
	$description = $langs->trans("RulesResultBookkeepingPredefined");
	$description .= ' ('.$langs->trans("SeePageForSetup", DOL_URL_ROOT.'/accountancy/admin/account.php?mainmenu=accountancy&leftmenu=accountancy_admin', $langs->transnoentitiesnoconv("Accountancy").' / '.$langs->transnoentitiesnoconv("Setup").' / '.$langs->transnoentitiesnoconv("Chartofaccounts")).')';
	$builddate = dol_now();
	//$exportlink=$langs->trans("NotYetAvailable");
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


report_header($name, '', $period, $periodlink, $description, $builddate, $exportlink, array('modecompta'=>$modecompta, 'showaccountdetail'=>$showaccountdetail), $calcmode);

if (isModEnabled('accounting') && $modecompta != 'BOOKKEEPING') {
	print info_admin($langs->trans("WarningReportNotReliable"), 0, 0, '1');
}

// Show report array
$param = '&modecompta='.urlencode($modecompta).'&showaccountdetail='.urlencode($showaccountdetail);
if ($date_startday) {
	$param .= '&date_startday='.$date_startday;
}
if ($date_startmonth) {
	$param .= '&date_startmonth='.$date_startmonth;
}
if ($date_startyear) {
	$param .= '&date_startyear='.$date_startyear;
}
if ($date_endday) {
	$param .= '&date_endday='.$date_endday;
}
if ($date_endmonth) {
	$param .= '&date_endmonth='.$date_endmonth;
}
if ($date_endyear) {
	$param .= '&date_endyear='.$date_endyear;
}

print '<table class="liste noborder centpercent">';
print '<tr class="liste_titre">';

if ($modecompta == 'BOOKKEEPING') {
	print_liste_field_titre("PredefinedGroups", $_SERVER["PHP_SELF"], 'f.thirdparty_code,f.rowid', '', $param, '', $sortfield, $sortorder, 'width200 ');
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
	$predefinedgroupwhere = "(";
	$predefinedgroupwhere .= " (pcg_type = 'EXPENSE')";
	$predefinedgroupwhere .= " OR ";
	$predefinedgroupwhere .= " (pcg_type = 'INCOME')";
	$predefinedgroupwhere .= ")";

	$charofaccountstring = getDolGlobalInt('CHARTOFACCOUNTS');
	$charofaccountstring = dol_getIdFromCode($db, getDolGlobalString('CHARTOFACCOUNTS'), 'accounting_system', 'rowid', 'pcg_version');

	$sql = "SELECT -1 as socid, aa.pcg_type, SUM(f.credit - f.debit) as amount";
	if ($showaccountdetail == 'no') {
		$sql .= ", f.thirdparty_code as name";
	}
	$sql .= " FROM ".MAIN_DB_PREFIX."accounting_bookkeeping as f";
	$sql .= ", ".MAIN_DB_PREFIX."accounting_account as aa";
	$sql .= " WHERE f.numero_compte = aa.account_number";
	$sql .= " AND ".$predefinedgroupwhere;
	$sql .= " AND fk_pcg_version = '".$db->escape($charofaccountstring)."'";
	$sql .= " AND f.entity = ".$conf->entity;
	if (!empty($date_start) && !empty($date_end)) {
		$sql .= " AND f.doc_date >= '".$db->idate($date_start)."' AND f.doc_date <= '".$db->idate($date_end)."'";
	}
	$sql .= " GROUP BY pcg_type";
	if ($showaccountdetail == 'no') {
		$sql .= ", name, socid";	// group by "accounting group" (INCOME/EXPENSE), then "customer".
	}
	$sql .= $db->order($sortfield, $sortorder);

	$oldpcgtype = '';

	dol_syslog("get bookkeeping entries", LOG_DEBUG);
	$result = $db->query($sql);
	if ($result) {
		$num = $db->num_rows($result);
		$i = 0;
		if ($num > 0) {
			while ($i < $num) {
				$objp = $db->fetch_object($result);

				if ($showaccountdetail == 'no') {
					if ($objp->pcg_type != $oldpcgtype) {
						print '<tr class="trforbreak"><td colspan="3" class="tdforbreak">'.dol_escape_htmltag($objp->pcg_type).'</td></tr>';
						$oldpcgtype = $objp->pcg_type;
					}
				}

				if ($showaccountdetail == 'no') {
					print '<tr class="oddeven">';
					print '<td></td>';
					print '<td>';
					print dol_escape_htmltag($objp->pcg_type);
					print($objp->name ? ' ('.dol_escape_htmltag($objp->name).')' : ' ('.$langs->trans("Unknown").')');
					print "</td>\n";
					print '<td class="right nowraponall"><span class="amount">'.price($objp->amount)."</span></td>\n";
					print "</tr>\n";
				} else {
					print '<tr class="oddeven trforbreak">';
					print '<td colspan="2" class="tdforbreak">';
					print dol_escape_htmltag($objp->pcg_type);
					print "</td>\n";
					print '<td class="right nowraponall tdforbreak"><span class="amount">'.price($objp->amount)."</span></td>\n";
					print "</tr>\n";
				}

				$total_ht += (isset($objp->amount) ? $objp->amount : 0);
				$total_ttc += (isset($objp->amount) ? $objp->amount : 0);

				if ($objp->pcg_type == 'INCOME') {
					$total_ht_income += (isset($objp->amount) ? $objp->amount : 0);
					$total_ttc_income += (isset($objp->amount) ? $objp->amount : 0);
				}
				if ($objp->pcg_type == 'EXPENSE') {
					$total_ht_outcome -= (isset($objp->amount) ? $objp->amount : 0);
					$total_ttc_outcome -= (isset($objp->amount) ? $objp->amount : 0);
				}

				// Loop on detail of all accounts
				// This make 14 calls for each detail of account (NP, N and month m)
				if ($showaccountdetail != 'no') {
					$tmppredefinedgroupwhere = "pcg_type = '".$db->escape($objp->pcg_type)."'";
					$tmppredefinedgroupwhere .= " AND fk_pcg_version = '".$db->escape($charofaccountstring)."'";
					//$tmppredefinedgroupwhere .= " AND thirdparty_code = '".$db->escape($objp->name)."'";

					// Get cpts of category/group
					$cpts = $AccCat->getCptsCat(0, $tmppredefinedgroupwhere);

					foreach ($cpts as $j => $cpt) {
						$return = $AccCat->getSumDebitCredit($cpt['account_number'], $date_start, $date_end, (empty($cpt['dc']) ? 0 : $cpt['dc']));
						if ($return < 0) {
							setEventMessages(null, $AccCat->errors, 'errors');
							$resultN = 0;
						} else {
							$resultN = $AccCat->sdc;
						}


						if ($showaccountdetail == 'all' || $resultN != 0) {
							print '<tr>';
							print '<td></td>';
							print '<td class="tdoverflowmax200"> &nbsp; &nbsp; '.length_accountg($cpt['account_number']).' - '.$cpt['account_label'].'</td>';
							print '<td class="right nowraponall"><span class="amount">'.price($resultN).'</span></td>';
							print "</tr>\n";
						}
					}
				}

				$i++;
			}
		} else {
			print '<tr><td colspan="3" class="opacitymedium">'.$langs->trans("NoRecordFound").'</td></tr>';
		}
	} else {
		dol_print_error($db);
	}
} else {
	/*
	 * Customer invoices
	 */
	print '<tr class="trforbreak"><td colspan="4">'.$langs->trans("CustomersInvoices").'</td></tr>';

	if ($modecompta == 'CREANCES-DETTES') {
		$sql = "SELECT s.nom as name, s.rowid as socid, sum(f.total_ht) as amount_ht, sum(f.total_ttc) as amount_ttc";
		$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
		$sql .= ", ".MAIN_DB_PREFIX."facture as f";
		$sql .= " WHERE f.fk_soc = s.rowid";
		$sql .= " AND f.fk_statut IN (1,2)";
		if (getDolGlobalString('FACTURE_DEPOSITS_ARE_JUST_PAYMENTS')) {
			$sql .= " AND f.type IN (0,1,2,5)";
		} else {
			$sql .= " AND f.type IN (0,1,2,3,5)";
		}
		if (!empty($date_start) && !empty($date_end)) {
			$sql .= " AND f.datef >= '".$db->idate($date_start)."' AND f.datef <= '".$db->idate($date_end)."'";
		}
	} elseif ($modecompta == 'RECETTES-DEPENSES') {
		/*
		 * List of payments (old payments are not seen by this query because, on older versions, they were not linked via payment_invoice.
		 * old versions, they were not linked via payment_invoice. They are added later)
		 */
		$sql = "SELECT s.nom as name, s.rowid as socid, sum(pf.amount) as amount_ttc";
		$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
		$sql .= ", ".MAIN_DB_PREFIX."facture as f";
		$sql .= ", ".MAIN_DB_PREFIX."paiement_facture as pf";
		$sql .= ", ".MAIN_DB_PREFIX."paiement as p";
		$sql .= " WHERE p.rowid = pf.fk_paiement";
		$sql .= " AND pf.fk_facture = f.rowid";
		$sql .= " AND f.fk_soc = s.rowid";
		if (!empty($date_start) && !empty($date_end)) {
			$sql .= " AND p.datep >= '".$db->idate($date_start)."' AND p.datep <= '".$db->idate($date_end)."'";
		}
	}
	$sql .= " AND f.entity IN (".getEntity('invoice').")";
	if ($socid) {
		$sql .= " AND f.fk_soc = ".((int) $socid);
	}
	$sql .= " GROUP BY name, socid";
	$sql .= $db->order($sortfield, $sortorder);

	dol_syslog("get customer invoices", LOG_DEBUG);
	$result = $db->query($sql);
	if ($result) {
		$num = $db->num_rows($result);
		$i = 0;
		while ($i < $num) {
			$objp = $db->fetch_object($result);

			print '<tr class="oddeven">';
			print '<td>&nbsp;</td>';
			print "<td>".$langs->trans("Bills").' <a href="'.DOL_URL_ROOT.'/compta/facture/list.php?socid='.$objp->socid.'">'.$objp->name."</td>\n";

			print '<td class="right">';
			if ($modecompta == 'CREANCES-DETTES') {
				print '<span class="amount">'.price($objp->amount_ht)."</span>";
			}
			print "</td>\n";
			print '<td class="right"><span class="amount">'.price($objp->amount_ttc)."</span></td>\n";

			$total_ht += (isset($objp->amount_ht) ? $objp->amount_ht : 0);
			$total_ttc += $objp->amount_ttc;
			print "</tr>\n";
			$i++;
		}
		$db->free($result);
	} else {
		dol_print_error($db);
	}

	// We add the old customer payments, not linked by payment_invoice
	if ($modecompta == 'RECETTES-DEPENSES') {
		$sql = "SELECT 'Autres' as name, '0' as idp, sum(p.amount) as amount_ttc";
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
		$sql .= " GROUP BY name, idp";
		$sql .= " ORDER BY name";

		dol_syslog("get old customer payments not linked to invoices", LOG_DEBUG);
		$result = $db->query($sql);
		if ($result) {
			$num = $db->num_rows($result);
			$i = 0;
			if ($num) {
				while ($i < $num) {
					$objp = $db->fetch_object($result);


					print '<tr class="oddeven">';
					print '<td>&nbsp;</td>';
					print "<td>".$langs->trans("Bills")." ".$langs->trans("Other")." (".$langs->trans("PaymentsNotLinkedToInvoice").")\n";

					print '<td class="right">';
					if ($modecompta == 'CREANCES-DETTES') {
						print '<span class="amount">'.price($objp->amount_ht)."</span></td>\n";
					}
					print '</td>';
					print '<td class="right"><span class="amount">'.price($objp->amount_ttc)."</span></td>\n";

					$total_ht += (isset($objp->amount_ht) ? $objp->amount_ht : 0);
					$total_ttc += $objp->amount_ttc;

					print "</tr>\n";
					$i++;
				}
			}
			$db->free($result);
		} else {
			dol_print_error($db);
		}
	}

	if ($total_ttc == 0) {
		print '<tr class="oddeven">';
		print '<td>&nbsp;</td>';
		print '<td colspan="3"><span class="opacitymedium">'.$langs->trans("None").'</span></td>';
		print '</tr>';
	}

	$total_ht_income += $total_ht;
	$total_ttc_income += $total_ttc;

	print '<tr class="liste_total">';
	print '<td></td>';
	print '<td></td>';
	print '<td class="right">';
	if ($modecompta == 'CREANCES-DETTES') {
		print price($total_ht);
	}
	print '</td>';
	print '<td class="right">'.price($total_ttc).'</td>';
	print '</tr>';

	/*
	 * Donations
	 */

	if (isModEnabled('don')) {
		print '<tr class="trforbreak"><td colspan="4">'.$langs->trans("Donations").'</td></tr>';

		if ($modecompta == 'CREANCES-DETTES' || $modecompta == 'RECETTES-DEPENSES') {
			if ($modecompta == 'CREANCES-DETTES') {
				$sql = "SELECT p.societe as name, p.firstname, p.lastname, date_format(p.datedon,'%Y-%m') as dm, sum(p.amount) as amount";
				$sql .= " FROM ".MAIN_DB_PREFIX."don as p";
				$sql .= " WHERE p.entity IN (".getEntity('donation').")";
				$sql .= " AND fk_statut in (1,2)";
			} else {
				$sql = "SELECT p.societe as nom, p.firstname, p.lastname, date_format(p.datedon,'%Y-%m') as dm, sum(p.amount) as amount";
				$sql .= " FROM ".MAIN_DB_PREFIX."don as p";
				$sql .= " INNER JOIN ".MAIN_DB_PREFIX."payment_donation as pe ON pe.fk_donation = p.rowid";
				$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_paiement as c ON pe.fk_typepayment = c.id";
				$sql .= " WHERE p.entity IN (".getEntity('donation').")";
				$sql .= " AND fk_statut >= 2";
			}
			if (!empty($date_start) && !empty($date_end)) {
				$sql .= " AND p.datedon >= '".$db->idate($date_start)."' AND p.datedon <= '".$db->idate($date_end)."'";
			}
		}
		$sql .= " GROUP BY p.societe, p.firstname, p.lastname, dm";
		$newsortfield = $sortfield;
		if ($newsortfield == 's.nom, s.rowid') {
			$newsortfield = 'p.societe, p.firstname, p.lastname, dm';
		}
		if ($newsortfield == 'amount_ht') {
			$newsortfield = 'amount';
		}
		if ($newsortfield == 'amount_ttc') {
			$newsortfield = 'amount';
		}
		$sql .= $db->order($newsortfield, $sortorder);

		dol_syslog("get dunning");
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

					print '<tr class="oddeven">';
					print '<td>&nbsp;</td>';

					print "<td>".$langs->trans("Donation")." <a href=\"".DOL_URL_ROOT."/don/list.php?search_company=".$obj->name."&search_name=".$obj->firstname." ".$obj->lastname."\">".$obj->name." ".$obj->firstname." ".$obj->lastname."</a></td>\n";

					print '<td class="right">';
					if ($modecompta == 'CREANCES-DETTES') {
						print '<span class="amount">'.price($obj->amount).'</span>';
					}
					print '</td>';
					print '<td class="right"><span class="amount">'.price($obj->amount).'</span></td>';
					print '</tr>';
					$i++;
				}
			} else {
				print '<tr class="oddeven"><td>&nbsp;</td>';
				print '<td colspan="3"><span class="opacitymedium">'.$langs->trans("None").'</span></td>';
				print '</tr>';
			}
		} else {
			dol_print_error($db);
		}

		$total_ht_income += $subtotal_ht;
		$total_ttc_income += $subtotal_ttc;

		print '<tr class="liste_total">';
		print '<td></td>';
		print '<td></td>';
		print '<td class="right">';
		if ($modecompta == 'CREANCES-DETTES') {
			print price($subtotal_ht);
		}
		print '</td>';
		print '<td class="right">'.price($subtotal_ttc).'</td>';
		print '</tr>';
	}

	/*
	 * Suppliers invoices
	 */
	if ($modecompta == 'CREANCES-DETTES') {
		$sql = "SELECT s.nom as name, s.rowid as socid, sum(f.total_ht) as amount_ht, sum(f.total_ttc) as amount_ttc";
		$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
		$sql .= ", ".MAIN_DB_PREFIX."facture_fourn as f";
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
		$sql = "SELECT s.nom as name, s.rowid as socid, sum(pf.amount) as amount_ttc";
		$sql .= " FROM ".MAIN_DB_PREFIX."paiementfourn as p";
		$sql .= ", ".MAIN_DB_PREFIX."paiementfourn_facturefourn as pf";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."facture_fourn as f";
		$sql .= " ON pf.fk_facturefourn = f.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s";
		$sql .= " ON f.fk_soc = s.rowid";
		$sql .= " WHERE p.rowid = pf.fk_paiementfourn ";
		if (!empty($date_start) && !empty($date_end)) {
			$sql .= " AND p.datep >= '".$db->idate($date_start)."' AND p.datep <= '".$db->idate($date_end)."'";
		}
	}

	$sql .= " AND f.entity = ".((int) $conf->entity);
	if ($socid) {
		$sql .= " AND f.fk_soc = ".((int) $socid);
	}
	$sql .= " GROUP BY name, socid";
	$sql .= $db->order($sortfield, $sortorder);

	print '<tr class="trforbreak"><td colspan="4">'.$langs->trans("SuppliersInvoices").'</td></tr>';

	$subtotal_ht = 0;
	$subtotal_ttc = 0;
	dol_syslog("get suppliers invoices", LOG_DEBUG);
	$result = $db->query($sql);
	if ($result) {
		$num = $db->num_rows($result);
		$i = 0;
		if ($num > 0) {
			while ($i < $num) {
				$objp = $db->fetch_object($result);

				print '<tr class="oddeven">';
				print '<td>&nbsp;</td>';
				print "<td>".$langs->trans("Bills").' <a href="'.DOL_URL_ROOT."/fourn/facture/list.php?socid=".$objp->socid.'">'.$objp->name.'</a></td>'."\n";

				print '<td class="right">';
				if ($modecompta == 'CREANCES-DETTES') {
					print '<span class="amount">'.price(-$objp->amount_ht)."</span>";
				}
				print "</td>\n";
				print '<td class="right"><span class="amount">'.price(-$objp->amount_ttc)."</span></td>\n";

				$total_ht -= (isset($objp->amount_ht) ? $objp->amount_ht : 0);
				$total_ttc -= $objp->amount_ttc;
				$subtotal_ht += (isset($objp->amount_ht) ? $objp->amount_ht : 0);
				$subtotal_ttc += $objp->amount_ttc;

				print "</tr>\n";
				$i++;
			}
		} else {
			print '<tr class="oddeven">';
			print '<td>&nbsp;</td>';
			print '<td colspan="3"><span class="opacitymedium">'.$langs->trans("None").'</span></td>';
			print '</tr>';
		}

		$db->free($result);
	} else {
		dol_print_error($db);
	}

	$total_ht_outcome += $subtotal_ht;
	$total_ttc_outcome += $subtotal_ttc;

	print '<tr class="liste_total">';
	print '<td></td>';
	print '<td></td>';
	print '<td class="right">';
	if ($modecompta == 'CREANCES-DETTES') {
		print price(-$subtotal_ht);
	}
	print '</td>';
	print '<td class="right">'.price(-$subtotal_ttc).'</td>';
	print '</tr>';


	/*
	 * Social / Fiscal contributions who are not deductible
	 */

	print '<tr class="trforbreak"><td colspan="4">'.$langs->trans("SocialContributionsNondeductibles").'</td></tr>';

	if ($modecompta == 'CREANCES-DETTES') {
		$sql = "SELECT c.id, c.libelle as label, c.accountancy_code, sum(cs.amount) as amount";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_chargesociales as c";
		$sql .= ", ".MAIN_DB_PREFIX."chargesociales as cs";
		$sql .= " WHERE cs.fk_type = c.id";
		$sql .= " AND c.deductible = 0";
		if (!empty($date_start) && !empty($date_end)) {
			$sql .= " AND cs.date_ech >= '".$db->idate($date_start)."' AND cs.date_ech <= '".$db->idate($date_end)."'";
		}
	} elseif ($modecompta == 'RECETTES-DEPENSES') {
		$sql = "SELECT c.id, c.libelle as label, c.accountancy_code, sum(p.amount) as amount";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_chargesociales as c";
		$sql .= ", ".MAIN_DB_PREFIX."chargesociales as cs";
		$sql .= ", ".MAIN_DB_PREFIX."paiementcharge as p";
		$sql .= " WHERE p.fk_charge = cs.rowid";
		$sql .= " AND cs.fk_type = c.id";
		$sql .= " AND c.deductible = 0";
		if (!empty($date_start) && !empty($date_end)) {
			$sql .= " AND p.datep >= '".$db->idate($date_start)."' AND p.datep <= '".$db->idate($date_end)."'";
		}
	}
	$sql .= " AND cs.entity = ".$conf->entity;
	$sql .= " GROUP BY c.libelle, c.id, c.accountancy_code";
	$newsortfield = $sortfield;
	if ($newsortfield == 's.nom, s.rowid') {
		$newsortfield = 'c.libelle, c.id';
	}
	if ($newsortfield == 'amount_ht') {
		$newsortfield = 'amount';
	}
	if ($newsortfield == 'amount_ttc') {
		$newsortfield = 'amount';
	}

	$sql .= $db->order($newsortfield, $sortorder);

	dol_syslog("get social contributions deductible=0", LOG_DEBUG);
	$result = $db->query($sql);
	$subtotal_ht = 0;
	$subtotal_ttc = 0;
	if ($result) {
		$num = $db->num_rows($result);
		$i = 0;
		if ($num) {
			while ($i < $num) {
				$obj = $db->fetch_object($result);

				$total_ht -= $obj->amount;
				$total_ttc -= $obj->amount;
				$subtotal_ht += $obj->amount;
				$subtotal_ttc += $obj->amount;

				$titletoshow = '';
				if ($obj->accountancy_code) {
					$titletoshow = $langs->trans("AccountingCode").': '.$obj->accountancy_code;
					$tmpaccountingaccount = new AccountingAccount($db);
					$tmpaccountingaccount->fetch(0, $obj->accountancy_code, 1);
					$titletoshow .= ' - '.$langs->trans("AccountingCategory").': '.$tmpaccountingaccount->pcg_type;
				}

				print '<tr class="oddeven">';
				print '<td>&nbsp;</td>';
				print '<td'.($obj->accountancy_code ? ' title="'.dol_escape_htmltag($titletoshow).'"' : '').'>'.dol_escape_htmltag($obj->label).'</td>';
				print '<td class="right">';
				if ($modecompta == 'CREANCES-DETTES') {
					print '<span class="amount">'.price(-$obj->amount).'</span>';
				}
				print '</td>';
				print '<td class="right"><span class="amount">'.price(-$obj->amount).'</span></td>';
				print '</tr>';
				$i++;
			}
		} else {
			print '<tr class="oddeven">';
			print '<td>&nbsp;</td>';
			print '<td colspan="3"><span class="opacitymedium">'.$langs->trans("None").'</span></td>';
			print '</tr>';
		}
	} else {
		dol_print_error($db);
	}

	$total_ht_outcome += $subtotal_ht;
	$total_ttc_outcome += $subtotal_ttc;

	print '<tr class="liste_total">';
	print '<td></td>';
	print '<td></td>';
	print '<td class="right">';
	if ($modecompta == 'CREANCES-DETTES') {
		print price(-$subtotal_ht);
	}
	print '</td>';
	print '<td class="right">'.price(-$subtotal_ttc).'</td>';
	print '</tr>';


	/*
	 * Social / Fiscal contributions who are deductible
	 */

	print '<tr class="trforbreak"><td colspan="4">'.$langs->trans("SocialContributionsDeductibles").'</td></tr>';

	if ($modecompta == 'CREANCES-DETTES') {
		$sql = "SELECT c.id, c.libelle as label, c.accountancy_code, sum(cs.amount) as amount";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_chargesociales as c";
		$sql .= ", ".MAIN_DB_PREFIX."chargesociales as cs";
		$sql .= " WHERE cs.fk_type = c.id";
		$sql .= " AND c.deductible = 1";
		if (!empty($date_start) && !empty($date_end)) {
			$sql .= " AND cs.date_ech >= '".$db->idate($date_start)."' AND cs.date_ech <= '".$db->idate($date_end)."'";
		}
		$sql .= " AND cs.entity = ".$conf->entity;
	} elseif ($modecompta == 'RECETTES-DEPENSES') {
		$sql = "SELECT c.id, c.libelle as label, c.accountancy_code, sum(p.amount) as amount";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_chargesociales as c";
		$sql .= ", ".MAIN_DB_PREFIX."chargesociales as cs";
		$sql .= ", ".MAIN_DB_PREFIX."paiementcharge as p";
		$sql .= " WHERE p.fk_charge = cs.rowid";
		$sql .= " AND cs.fk_type = c.id";
		$sql .= " AND c.deductible = 1";
		if (!empty($date_start) && !empty($date_end)) {
			$sql .= " AND p.datep >= '".$db->idate($date_start)."' AND p.datep <= '".$db->idate($date_end)."'";
		}
		$sql .= " AND cs.entity = ".$conf->entity;
	}
	$sql .= " GROUP BY c.libelle, c.id, c.accountancy_code";
	$newsortfield = $sortfield;
	if ($newsortfield == 's.nom, s.rowid') {
		$newsortfield = 'c.libelle, c.id';
	}
	if ($newsortfield == 'amount_ht') {
		$newsortfield = 'amount';
	}
	if ($newsortfield == 'amount_ttc') {
		$newsortfield = 'amount';
	}
	$sql .= $db->order($newsortfield, $sortorder);

	dol_syslog("get social contributions deductible=1", LOG_DEBUG);
	$result = $db->query($sql);
	$subtotal_ht = 0;
	$subtotal_ttc = 0;
	if ($result) {
		$num = $db->num_rows($result);
		$i = 0;
		if ($num) {
			while ($i < $num) {
				$obj = $db->fetch_object($result);

				$total_ht -= $obj->amount;
				$total_ttc -= $obj->amount;
				$subtotal_ht += $obj->amount;
				$subtotal_ttc += $obj->amount;

				$titletoshow = '';
				if ($obj->accountancy_code) {
					$titletoshow = $langs->trans("AccountingCode").': '.$obj->accountancy_code;
					$tmpaccountingaccount = new AccountingAccount($db);
					$tmpaccountingaccount->fetch(0, $obj->accountancy_code, 1);
					$titletoshow .= ' - '.$langs->trans("AccountingCategory").': '.$tmpaccountingaccount->pcg_type;
				}

				print '<tr class="oddeven">';
				print '<td>&nbsp;</td>';
				print '<td'.($obj->accountancy_code ? ' title="'.dol_escape_htmltag($titletoshow).'"' : '').'>'.dol_escape_htmltag($obj->label).'</td>';
				print '<td class="right">';
				if ($modecompta == 'CREANCES-DETTES') {
					print '<span class="amount">'.price(-$obj->amount).'</span>';
				}
				print '</td>';
				print '<td class="right"><span class="amount">'.price(-$obj->amount).'</span></td>';
				print '</tr>';
				$i++;
			}
		} else {
			print '<tr class="oddeven">';
			print '<td>&nbsp;</td>';
			print '<td colspan="3"><span class="opacitymedium">'.$langs->trans("None").'</span></td>';
			print '</tr>';
		}
	} else {
		dol_print_error($db);
	}

	$total_ht_outcome += $subtotal_ht;
	$total_ttc_outcome += $subtotal_ttc;

	print '<tr class="liste_total">';
	print '<td></td>';
	print '<td></td>';
	print '<td class="right">';
	if ($modecompta == 'CREANCES-DETTES') {
		print price(-$subtotal_ht);
	}
	print '</td>';
	print '<td class="right">'.price(-$subtotal_ttc).'</td>';
	print '</tr>';


	/*
	 * Salaries
	 */

	if (isModEnabled('salaries')) {
		print '<tr class="trforbreak"><td colspan="4">'.$langs->trans("Salaries").'</td></tr>';

		if ($modecompta == 'CREANCES-DETTES' || $modecompta == 'RECETTES-DEPENSES') {
			if ($modecompta == 'CREANCES-DETTES') {
				$column = 's.dateep';	// We use the date of end of period of salary

				$sql = "SELECT u.rowid, u.firstname, u.lastname, s.fk_user as fk_user, s.label as label, date_format($column,'%Y-%m') as dm, sum(s.amount) as amount";
				$sql .= " FROM ".MAIN_DB_PREFIX."salary as s";
				$sql .= " INNER JOIN ".MAIN_DB_PREFIX."user as u ON u.rowid = s.fk_user";
				$sql .= " WHERE s.entity IN (".getEntity('salary').")";
				if (!empty($date_start) && !empty($date_end)) {
					$sql .= " AND $column >= '".$db->idate($date_start)."' AND $column <= '".$db->idate($date_end)."'";
				}
				$sql .= " GROUP BY u.rowid, u.firstname, u.lastname, s.fk_user, s.label, dm";
			} else {
				$column = 'p.datep';

				$sql = "SELECT u.rowid, u.firstname, u.lastname, s.fk_user as fk_user, p.label as label, date_format($column,'%Y-%m') as dm, sum(p.amount) as amount";
				$sql .= " FROM ".MAIN_DB_PREFIX."payment_salary as p";
				$sql .= " INNER JOIN ".MAIN_DB_PREFIX."salary as s ON s.rowid = p.fk_salary";
				$sql .= " INNER JOIN ".MAIN_DB_PREFIX."user as u ON u.rowid = s.fk_user";
				$sql .= " WHERE p.entity IN (".getEntity('payment_salary').")";
				if (!empty($date_start) && !empty($date_end)) {
					$sql .= " AND $column >= '".$db->idate($date_start)."' AND $column <= '".$db->idate($date_end)."'";
				}
				$sql .= " GROUP BY u.rowid, u.firstname, u.lastname, s.fk_user, p.label, dm";
			}

			$newsortfield = $sortfield;
			if ($newsortfield == 's.nom, s.rowid') {
				$newsortfield = 'u.firstname, u.lastname';
			}
			if ($newsortfield == 'amount_ht') {
				$newsortfield = 'amount';
			}
			if ($newsortfield == 'amount_ttc') {
				$newsortfield = 'amount';
			}
			$sql .= $db->order($newsortfield, $sortorder);
		}

		dol_syslog("get salaries");
		$result = $db->query($sql);
		$subtotal_ht = 0;
		$subtotal_ttc = 0;
		if ($result) {
			$num = $db->num_rows($result);
			$i = 0;
			if ($num) {
				while ($i < $num) {
					$obj = $db->fetch_object($result);

					$total_ht -= $obj->amount;
					$total_ttc -= $obj->amount;
					$subtotal_ht += $obj->amount;
					$subtotal_ttc += $obj->amount;

					print '<tr class="oddeven"><td>&nbsp;</td>';

					$userstatic = new User($db);
					$userstatic->fetch($obj->fk_user);

					print "<td>".$langs->trans("Salary")." <a href=\"".DOL_URL_ROOT."/salaries/list.php?search_user=".urlencode($userstatic->getFullName($langs))."\">".$obj->firstname." ".$obj->lastname."</a></td>\n";
					print '<td class="right">';
					if ($modecompta == 'CREANCES-DETTES') {
						print '<span class="amount">'.price(-$obj->amount).'</span>';
					}
					print '</td>';
					print '<td class="right"><span class="amount">'.price(-$obj->amount).'</span></td>';
					print '</tr>';
					$i++;
				}
			} else {
				print '<tr class="oddeven">';
				print '<td>&nbsp;</td>';
				print '<td colspan="3"><span class="opacitymedium">'.$langs->trans("None").'</span></td>';
				print '</tr>';
			}
		} else {
			dol_print_error($db);
		}

		$total_ht_outcome += $subtotal_ht;
		$total_ttc_outcome += $subtotal_ttc;

		print '<tr class="liste_total">';
		print '<td></td>';
		print '<td></td>';
		print '<td class="right">';
		if ($modecompta == 'CREANCES-DETTES') {
			print price(-$subtotal_ht);
		}
		print '</td>';
		print '<td class="right">'.price(-$subtotal_ttc).'</td>';
		print '</tr>';
	}


	/*
	 * Expense report
	 */

	if (isModEnabled('expensereport')) {
		if ($modecompta == 'CREANCES-DETTES' || $modecompta == 'RECETTES-DEPENSES') {
			$langs->load('trips');
			if ($modecompta == 'CREANCES-DETTES') {
				$sql = "SELECT p.rowid, p.ref, u.rowid as userid, u.firstname, u.lastname, date_format(date_valid,'%Y-%m') as dm, p.total_ht as amount_ht, p.total_ttc as amount_ttc";
				$sql .= " FROM ".MAIN_DB_PREFIX."expensereport as p";
				$sql .= " INNER JOIN ".MAIN_DB_PREFIX."user as u ON u.rowid=p.fk_user_author";
				$sql .= " WHERE p.entity IN (".getEntity('expensereport').")";
				$sql .= " AND p.fk_statut>=5";

				$column = 'p.date_valid';
			} else {
				$sql = "SELECT p.rowid, p.ref, u.rowid as userid, u.firstname, u.lastname, date_format(pu.datep,'%Y-%m') as dm, sum(pe.amount) as amount_ht, sum(pe.amount) as amount_ttc";
				$sql .= " FROM ".MAIN_DB_PREFIX."expensereport as p";
				$sql .= " INNER JOIN ".MAIN_DB_PREFIX."user as u ON u.rowid=p.fk_user_author";
				$sql .= " INNER JOIN ".MAIN_DB_PREFIX."payment_expensereport as pe ON pe.fk_expensereport = p.rowid";
				$sql .= " INNER JOIN ".MAIN_DB_PREFIX."payment_expensereport as pu ON pe.fk_paiementuser = pu.rowid";
				$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_paiement as c ON pe.fk_typepayment = c.id";
				$sql .= " WHERE p.entity IN (".getEntity('expensereport').")";
				$sql .= " AND p.fk_statut>=5";

				$column = 'pu.datep';
			}

			if (!empty($date_start) && !empty($date_end)) {
				$sql .= " AND $column >= '".$db->idate($date_start)."' AND $column <= '".$db->idate($date_end)."'";
			}

			if ($modecompta == 'CREANCES-DETTES') {
				//No need of GROUP BY
			} else {
				$sql .= " GROUP BY u.rowid, p.rowid, p.ref, u.firstname, u.lastname, dm";
			}
			$newsortfield = $sortfield;
			if ($newsortfield == 's.nom, s.rowid') {
				$newsortfield = 'p.ref';
			}
			$sql .= $db->order($newsortfield, $sortorder);
		}

		print '<tr class="trforbreak"><td colspan="4">'.$langs->trans("ExpenseReport").'</td></tr>';

		dol_syslog("get expense report outcome");
		$result = $db->query($sql);
		$subtotal_ht = 0;
		$subtotal_ttc = 0;
		if ($result) {
			$num = $db->num_rows($result);
			if ($num) {
				while ($obj = $db->fetch_object($result)) {
					$total_ht -= $obj->amount_ht;
					$total_ttc -= $obj->amount_ttc;
					$subtotal_ht += $obj->amount_ht;
					$subtotal_ttc += $obj->amount_ttc;

					print '<tr class="oddeven">';
					print '<td>&nbsp;</td>';
					print "<td>".$langs->trans("ExpenseReport")." <a href=\"".DOL_URL_ROOT."/expensereport/list.php?search_user=".$obj->userid."\">".$obj->firstname." ".$obj->lastname."</a></td>\n";
					print '<td class="right">';
					if ($modecompta == 'CREANCES-DETTES') {
						print '<span class="amount">'.price(-$obj->amount_ht).'</span>';
					}
					print '</td>';
					print '<td class="right"><span class="amount">'.price(-$obj->amount_ttc).'</span></td>';
					print '</tr>';
				}
			} else {
				print '<tr class="oddeven">';
				print '<td>&nbsp;</td>';
				print '<td colspan="3"><span class="opacitymedium">'.$langs->trans("None").'</span></td>';
				print '</tr>';
			}
		} else {
			dol_print_error($db);
		}

		$total_ht_outcome += $subtotal_ht;
		$total_ttc_outcome += $subtotal_ttc;

		print '<tr class="liste_total">';
		print '<td></td>';
		print '<td></td>';
		print '<td class="right">';
		if ($modecompta == 'CREANCES-DETTES') {
			print price(-$subtotal_ht);
		}
		print '</td>';
		print '<td class="right">'.price(-$subtotal_ttc).'</td>';
		print '</tr>';
	}


	/*
	 * Various Payments
	 */
	//$conf->global->ACCOUNTING_REPORTS_INCLUDE_VARPAY = 1;

	if (getDolGlobalString('ACCOUNTING_REPORTS_INCLUDE_VARPAY') && isModEnabled("bank") && ($modecompta == 'CREANCES-DETTES' || $modecompta == "RECETTES-DEPENSES")) {
		$subtotal_ht = 0;
		$subtotal_ttc = 0;

		print '<tr class="trforbreak"><td colspan="4">'.$langs->trans("VariousPayment").'</td></tr>';

		// Debit
		$sql = "SELECT SUM(p.amount) AS amount FROM ".MAIN_DB_PREFIX."payment_various as p";
		$sql .= ' WHERE 1 = 1';
		if (!empty($date_start) && !empty($date_end)) {
			$sql .= " AND p.datep >= '".$db->idate($date_start)."' AND p.datep <= '".$db->idate($date_end)."'";
		}
		$sql .= ' GROUP BY p.sens';
		$sql .= ' ORDER BY p.sens';

		dol_syslog('get various payments', LOG_DEBUG);
		$result = $db->query($sql);
		if ($result) {
			// Debit (payment of suppliers for example)
			$obj = $db->fetch_object($result);
			if (isset($obj->amount)) {
				$subtotal_ht += -$obj->amount;
				$subtotal_ttc += -$obj->amount;

				$total_ht_outcome += $obj->amount;
				$total_ttc_outcome += $obj->amount;
			}
			print '<tr class="oddeven">';
			print '<td>&nbsp;</td>';
			print "<td>".$langs->trans("AccountingDebit")."</td>\n";
			print '<td class="right">';
			if ($modecompta == 'CREANCES-DETTES') {
				print '<span class="amount">'.price(-$obj->amount).'</span>';
			}
			print '</td>';
			print '<td class="right"><span class="amount">'.price(-$obj->amount)."</span></td>\n";
			print "</tr>\n";

			// Credit (payment received from customer for example)
			$obj = $db->fetch_object($result);
			if (isset($obj->amount)) {
				$subtotal_ht += $obj->amount;
				$subtotal_ttc += $obj->amount;

				$total_ht_income += $obj->amount;
				$total_ttc_income += $obj->amount;
			}
			print '<tr class="oddeven"><td>&nbsp;</td>';
			print "<td>".$langs->trans("AccountingCredit")."</td>\n";
			print '<td class="right">';
			if ($modecompta == 'CREANCES-DETTES') {
				print '<span class="amount">'.price($obj->amount).'</span>';
			}
			print '</td>';
			print '<td class="right"><span class="amount">'.price($obj->amount)."</span></td>\n";
			print "</tr>\n";

			// Total
			$total_ht += $subtotal_ht;
			$total_ttc += $subtotal_ttc;
			print '<tr class="liste_total">';
			print '<td></td>';
			print '<td></td>';
			print '<td class="right">';
			if ($modecompta == 'CREANCES-DETTES') {
				print price($subtotal_ht);
			}
			print '</td>';
			print '<td class="right">'.price($subtotal_ttc).'</td>';
			print '</tr>';
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

		print '<tr class="trforbreak"><td colspan="4">'.$langs->trans("PaymentLoan").'</td></tr>';

		$sql = 'SELECT l.rowid as id, l.label AS label, SUM(p.amount_capital + p.amount_insurance + p.amount_interest) as amount FROM '.MAIN_DB_PREFIX.'payment_loan as p';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'loan AS l ON l.rowid = p.fk_loan';
		$sql .= ' WHERE 1 = 1';
		if (!empty($date_start) && !empty($date_end)) {
			$sql .= " AND p.datep >= '".$db->idate($date_start)."' AND p.datep <= '".$db->idate($date_end)."'";
		}
		$sql .= ' GROUP BY p.fk_loan';
		$sql .= ' ORDER BY p.fk_loan';

		dol_syslog('get loan payments', LOG_DEBUG);
		$result = $db->query($sql);
		if ($result) {
			require_once DOL_DOCUMENT_ROOT.'/loan/class/loan.class.php';
			$loan_static = new Loan($db);
			while ($obj = $db->fetch_object($result)) {
				$loan_static->id = $obj->id;
				$loan_static->ref = $obj->id;
				$loan_static->label = $obj->label;
				print '<tr class="oddeven"><td>&nbsp;</td>';
				print "<td>".$loan_static->getNomUrl(1).' - '.$obj->label."</td>\n";
				if ($modecompta == 'CREANCES-DETTES') {
					print '<td class="right"><span class="amount">'.price(-$obj->amount).'</span></td>';
				}
				print '<td class="right"><span class="amount">'.price(-$obj->amount)."</span></td>\n";
				print "</tr>\n";
				$subtotal_ht -= $obj->amount;
				$subtotal_ttc -= $obj->amount;
			}
			$total_ht += $subtotal_ht;
			$total_ttc += $subtotal_ttc;

			$total_ht_income += $subtotal_ht;
			$total_ttc_income += $subtotal_ttc;

			print '<tr class="liste_total">';
			print '<td></td>';
			print '<td></td>';
			print '<td class="right">';
			if ($modecompta == 'CREANCES-DETTES') {
				print price($subtotal_ht);
			}
			print '</td>';
			print '<td class="right">'.price($subtotal_ttc).'</td>';
			print '</tr>';
		} else {
			dol_print_error($db);
		}
	}

	/*
	 * VAT
	 */

	print '<tr class="trforbreak"><td colspan="4">'.$langs->trans("VAT").'</td></tr>';
	$subtotal_ht = 0;
	$subtotal_ttc = 0;

	if (isModEnabled('tax') && ($modecompta == 'CREANCES-DETTES' || $modecompta == 'RECETTES-DEPENSES')) {
		if ($modecompta == 'CREANCES-DETTES') {
			// VAT to pay
			$amount = 0;
			$sql = "SELECT date_format(f.datef,'%Y-%m') as dm, sum(f.total_tva) as amount";
			$sql .= " FROM ".MAIN_DB_PREFIX."facture as f";
			$sql .= " WHERE f.fk_statut IN (1,2)";
			if (getDolGlobalString('FACTURE_DEPOSITS_ARE_JUST_PAYMENTS')) {
				$sql .= " AND f.type IN (0,1,2,5)";
			} else {
				$sql .= " AND f.type IN (0,1,2,3,5)";
			}
			if (!empty($date_start) && !empty($date_end)) {
				$sql .= " AND f.datef >= '".$db->idate($date_start)."' AND f.datef <= '".$db->idate($date_end)."'";
			}
			$sql .= " AND f.entity IN (".getEntity('invoice').")";
			$sql .= " GROUP BY dm";
			$newsortfield = $sortfield;
			if ($newsortfield == 's.nom, s.rowid') {
				$newsortfield = 'dm';
			}
			if ($newsortfield == 'amount_ht') {
				$newsortfield = 'amount';
			}
			if ($newsortfield == 'amount_ttc') {
				$newsortfield = 'amount';
			}
			$sql .= $db->order($newsortfield, $sortorder);

			dol_syslog("get vat to pay", LOG_DEBUG);
			$result = $db->query($sql);
			if ($result) {
				$num = $db->num_rows($result);
				$i = 0;
				if ($num) {
					while ($i < $num) {
						$obj = $db->fetch_object($result);

						$amount -= $obj->amount;
						//$total_ht -= $obj->amount;
						$total_ttc -= $obj->amount;
						//$subtotal_ht -= $obj->amount;
						$subtotal_ttc -= $obj->amount;
						$i++;
					}
				}
			} else {
				dol_print_error($db);
			}

			$total_ht_outcome -= 0;
			$total_ttc_outcome -= $amount;

			print '<tr class="oddeven">';
			print '<td>&nbsp;</td>';
			print "<td>".$langs->trans("VATToPay")."</td>\n";
			print '<td class="right">&nbsp;</td>'."\n";
			print '<td class="right"><span class="amount">'.price($amount)."</span></td>\n";
			print "</tr>\n";

			// VAT to retrieve
			$amount = 0;
			$sql = "SELECT date_format(f.datef,'%Y-%m') as dm, sum(f.total_tva) as amount";
			$sql .= " FROM ".MAIN_DB_PREFIX."facture_fourn as f";
			$sql .= " WHERE f.fk_statut IN (1,2)";
			if (getDolGlobalString('FACTURE_SUPPLIER_DEPOSITS_ARE_JUST_PAYMENTS')) {
				$sql .= " AND f.type IN (0,1,2)";
			} else {
				$sql .= " AND f.type IN (0,1,2,3)";
			}
			if (!empty($date_start) && !empty($date_end)) {
				$sql .= " AND f.datef >= '".$db->idate($date_start)."' AND f.datef <= '".$db->idate($date_end)."'";
			}
			$sql .= " AND f.entity = ".$conf->entity;
			$sql .= " GROUP BY dm";
			$newsortfield = $sortfield;
			if ($newsortfield == 's.nom, s.rowid') {
				$newsortfield = 'dm';
			}
			if ($newsortfield == 'amount_ht') {
				$newsortfield = 'amount';
			}
			if ($newsortfield == 'amount_ttc') {
				$newsortfield = 'amount';
			}
			$sql .= $db->order($newsortfield, $sortorder);

			dol_syslog("get vat received back", LOG_DEBUG);
			$result = $db->query($sql);
			if ($result) {
				$num = $db->num_rows($result);
				$i = 0;
				if ($num) {
					while ($i < $num) {
						$obj = $db->fetch_object($result);

						$amount += $obj->amount;
						//$total_ht += $obj->amount;
						$total_ttc += $obj->amount;
						//$subtotal_ht += $obj->amount;
						$subtotal_ttc += $obj->amount;

						$i++;
					}
				}
			} else {
				dol_print_error($db);
			}

			$total_ht_income += 0;
			$total_ttc_income += $amount;

			print '<tr class="oddeven">';
			print '<td>&nbsp;</td>';
			print '<td>'.$langs->trans("VATToCollect")."</td>\n";
			print '<td class="right">&nbsp;</td>'."\n";
			print '<td class="right"><span class="amount">'.price($amount)."</span></td>\n";
			print "</tr>\n";
		} else {
			// VAT really already paid
			$amount = 0;
			$sql = "SELECT date_format(t.datev,'%Y-%m') as dm, sum(t.amount) as amount";
			$sql .= " FROM ".MAIN_DB_PREFIX."tva as t";
			$sql .= " WHERE amount > 0";
			if (!empty($date_start) && !empty($date_end)) {
				$sql .= " AND t.datev >= '".$db->idate($date_start)."' AND t.datev <= '".$db->idate($date_end)."'";
			}
			$sql .= " AND t.entity = ".$conf->entity;
			$sql .= " GROUP BY dm";
			$newsortfield = $sortfield;
			if ($newsortfield == 's.nom, s.rowid') {
				$newsortfield = 'dm';
			}
			if ($newsortfield == 'amount_ht') {
				$newsortfield = 'amount';
			}
			if ($newsortfield == 'amount_ttc') {
				$newsortfield = 'amount';
			}
			$sql .= $db->order($newsortfield, $sortorder);

			dol_syslog("get vat really paid", LOG_DEBUG);
			$result = $db->query($sql);
			if ($result) {
				$num = $db->num_rows($result);
				$i = 0;
				if ($num) {
					while ($i < $num) {
						$obj = $db->fetch_object($result);

						$amount -= $obj->amount;
						$total_ht -= $obj->amount;
						$total_ttc -= $obj->amount;
						$subtotal_ht -= $obj->amount;
						$subtotal_ttc -= $obj->amount;

						$i++;
					}
				}
				$db->free($result);
			} else {
				dol_print_error($db);
			}

			$total_ht_outcome -= 0;
			$total_ttc_outcome -= $amount;

			print '<tr class="oddeven">';
			print '<td>&nbsp;</td>';
			print "<td>".$langs->trans("VATPaid")."</td>\n";
			print '<td <class="right"></td>'."\n";
			print '<td class="right"><span class="amount">'.price($amount)."</span></td>\n";
			print "</tr>\n";

			// VAT really received
			$amount = 0;
			$sql = "SELECT date_format(t.datev,'%Y-%m') as dm, sum(t.amount) as amount";
			$sql .= " FROM ".MAIN_DB_PREFIX."tva as t";
			$sql .= " WHERE amount < 0";
			if (!empty($date_start) && !empty($date_end)) {
				$sql .= " AND t.datev >= '".$db->idate($date_start)."' AND t.datev <= '".$db->idate($date_end)."'";
			}
			$sql .= " AND t.entity = ".$conf->entity;
			$sql .= " GROUP BY dm";
			$newsortfield = $sortfield;
			if ($newsortfield == 's.nom, s.rowid') {
				$newsortfield = 'dm';
			}
			if ($newsortfield == 'amount_ht') {
				$newsortfield = 'amount';
			}
			if ($newsortfield == 'amount_ttc') {
				$newsortfield = 'amount';
			}
			$sql .= $db->order($newsortfield, $sortorder);

			dol_syslog("get vat really received back", LOG_DEBUG);
			$result = $db->query($sql);
			if ($result) {
				$num = $db->num_rows($result);
				$i = 0;
				if ($num) {
					while ($i < $num) {
						$obj = $db->fetch_object($result);

						$amount += -$obj->amount;
						$total_ht += -$obj->amount;
						$total_ttc += -$obj->amount;
						$subtotal_ht += -$obj->amount;
						$subtotal_ttc += -$obj->amount;

						$i++;
					}
				}
				$db->free($result);
			} else {
				dol_print_error($db);
			}

			$total_ht_income += 0;
			$total_ttc_income += $amount;

			print '<tr class="oddeven">';
			print '<td>&nbsp;</td>';
			print "<td>".$langs->trans("VATCollected")."</td>\n";
			print '<td class="right"></td>'."\n";
			print '<td class="right"><span class="amount">'.price($amount)."</span></td>\n";
			print "</tr>\n";
		}
	}

	if ($mysoc->tva_assuj != '0') {	// Assujetti
		print '<tr class="liste_total">';
		print '<td></td>';
		print '<td></td>';
		print '<td class="right">&nbsp;</td>';
		print '<td class="right">'.price(price2num($subtotal_ttc, 'MT')).'</td>';
		print '</tr>';
	}
}

$action = "balanceclient";
$object = array(&$total_ht, &$total_ttc);
$parameters["mode"] = $modecompta;
$parameters["date_start"] = $date_start;
$parameters["date_end"] = $date_end;
// Initialize a technical object to manage hooks of expenses. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('externalbalance'));
$reshook = $hookmanager->executeHooks('addBalanceLine', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
print $hookmanager->resPrint;



// Total
print '<tr>';
print '<td colspan="'.($modecompta == 'BOOKKEEPING' ? 3 : 4).'">&nbsp;</td>';
print '</tr>';

print '<tr class="liste_total"><td class="left" colspan="2">'.$langs->trans("Income").'</td>';
if ($modecompta == 'CREANCES-DETTES') {
	print '<td class="liste_total right nowraponall">'.price(price2num($total_ht_income, 'MT')).'</td>';
} elseif ($modecompta == 'RECETTES-DEPENSES') {
	print '<td></td>';
}
print '<td class="liste_total right nowraponall">'.price(price2num($total_ttc_income, 'MT')).'</td>';
print '</tr>';
print '<tr class="liste_total"><td class="left" colspan="2">'.$langs->trans("Outcome").'</td>';
if ($modecompta == 'CREANCES-DETTES') {
	print '<td class="liste_total right nowraponall">'.price(price2num(-$total_ht_outcome, 'MT')).'</td>';
} elseif ($modecompta == 'RECETTES-DEPENSES') {
	print '<td></td>';
}
print '<td class="liste_total right nowraponall">'.price(price2num(-$total_ttc_outcome, 'MT')).'</td>';
print '</tr>';
print '<tr class="liste_total"><td class="left" colspan="2">'.$langs->trans("Profit").'</td>';
if ($modecompta == 'CREANCES-DETTES') {
	print '<td class="liste_total right nowraponall">'.price(price2num($total_ht, 'MT')).'</td>';
} elseif ($modecompta == 'RECETTES-DEPENSES') {
	print '<td></td>';
}
print '<td class="liste_total right nowraponall">'.price(price2num($total_ttc, 'MT')).'</td>';
print '</tr>';

print "</table>";
print '<br>';

// End of page
llxFooter();
$db->close();
