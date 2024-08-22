<?php
/* Copyright (C) 2003       Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2014-2016  Ferran Marcet           <fmarcet@2byte.es>
 * Copyright (C) 2014       Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2014       Florian Henry           <florian.henry@open-concept.pro>
 * Copyright (C) 2018       Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2020       Maxime DEMAREST         <maxime@indelog.fr>
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
 *      \file       htdocs/compta/resultat/index.php
 * 	 	\ingroup	compta, accountancy
 *      \brief      Page reporting result
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

// Change this to test different cases of setup
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


/*
 * View
 */

llxHeader();

$form = new Form($db);

$exportlink = '';

$encaiss = array();
$encaiss_ttc = array();
$decaiss = array();
$decaiss_ttc = array();

// Affiche en-tete du rapport
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
	//$exportlink=$langs->trans("NotYetAvailable");
} elseif ($modecompta == "RECETTES-DEPENSES") {
	$name = $langs->trans("ReportInOut").', '.$langs->trans("ByYear");
	$period = $form->selectDate($date_start, 'date_start', 0, 0, 0, '', 1, 0).' - '.$form->selectDate($date_end, 'date_end', 0, 0, 0, '', 1, 0);
	$periodlink = ($year_start ? "<a href='".$_SERVER["PHP_SELF"]."?year=".($year_start + $nbofyear - 2)."&modecompta=".$modecompta."'>".img_previous()."</a> <a href='".$_SERVER["PHP_SELF"]."?year=".($year_start + $nbofyear)."&modecompta=".$modecompta."'>".img_next()."</a>" : "");
	$description = $langs->trans("RulesAmountWithTaxIncluded");
	$description .= '<br>'.$langs->trans("RulesResultInOut");
	$builddate = dol_now();
	//$exportlink=$langs->trans("NotYetAvailable");
} elseif ($modecompta == "BOOKKEEPING") {
	$name = $langs->trans("ReportInOut").', '.$langs->trans("ByYear");
	$period = $form->selectDate($date_start, 'date_start', 0, 0, 0, '', 1, 0).' - '.$form->selectDate($date_end, 'date_end', 0, 0, 0, '', 1, 0);
	$periodlink = ($year_start ? "<a href='".$_SERVER["PHP_SELF"]."?year=".($year_start + $nbofyear - 2)."&modecompta=".$modecompta."'>".img_previous()."</a> <a href='".$_SERVER["PHP_SELF"]."?year=".($year_start + $nbofyear)."&modecompta=".$modecompta."'>".img_next()."</a>" : "");
	$description = $langs->trans("RulesAmountOnInOutBookkeepingRecord");
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


report_header($name, '', $period, $periodlink, $description, $builddate, $exportlink, array(), $calcmode);

if (isModEnabled('accounting') && $modecompta != 'BOOKKEEPING') {
	print info_admin($langs->trans("WarningReportNotReliable"), 0, 0, 1);
}



/*
 * Factures clients
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
} elseif ($modecompta == "BOOKKEEPING") {
	// Nothing from this table
}

if (isModEnabled('invoice') && ($modecompta == 'CREANCES-DETTES' || $modecompta == "RECETTES-DEPENSES")) {
	// On ajoute les paiements clients anciennes version, non lies par paiement_facture
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
	} elseif ($modecompta == "RECETTES-DEPENSES") {
		// Nothing from this table
	}
} elseif ($modecompta == "BOOKKEEPING") {
	// Nothing from this table
}


/*
 * Frais, factures fournisseurs.
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
} elseif ($modecompta == "BOOKKEEPING") {
	// Nothing from this table
}



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
} elseif ($modecompta == "BOOKKEEPING") {
	// Nothing from this table
}

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
} elseif ($modecompta == "BOOKKEEPING") {
	// Nothing from this table
}


/*
 * Salaries
 */

if (isModEnabled('salaries') && ($modecompta == 'CREANCES-DETTES' || $modecompta == "RECETTES-DEPENSES")) {
	if ($modecompta == 'CREANCES-DETTES') {
		$column = 's.dateep';		// we use the date of end of period of salary

		$sql = "SELECT s.label as nom, date_format(".$column.",'%Y-%m') as dm, sum(s.amount) as amount";
		$sql .= " FROM ".MAIN_DB_PREFIX."salary as s";
		$sql .= " WHERE s.entity IN (".getEntity('salary').")";
		if (!empty($date_start) && !empty($date_end)) {
			$sql .= " AND ".$column." >= '".$db->idate($date_start)."' AND ".$column." <= '".$db->idate($date_end)."'";
		}
		$sql .= " GROUP BY s.label, dm";
	}
	if ($modecompta == "RECETTES-DEPENSES") {
		$column = 'p.datep';

		$sql = "SELECT p.label as nom, date_format(".$column.",'%Y-%m') as dm, sum(p.amount) as amount";
		$sql .= " FROM ".MAIN_DB_PREFIX."payment_salary as p";
		$sql .= " INNER JOIN ".MAIN_DB_PREFIX."salary as s ON p.fk_salary = s.rowid";
		$sql .= " WHERE p.entity IN (".getEntity('payment_salary').")";
		if (!empty($date_start) && !empty($date_end)) {
			$sql .= " AND ".$column." >= '".$db->idate($date_start)."' AND ".$column." <= '".$db->idate($date_end)."'";
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
} elseif ($modecompta == "BOOKKEEPING") {
	// Nothing from this table
}


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
			$sql .= " AND ".$column." >= '".$db->idate($date_start)."' AND ".$column." <= '".$db->idate($date_end)."'";
		}
	} elseif ($modecompta == 'RECETTES-DEPENSES') {
		$sql = "SELECT date_format(pe.datep,'%Y-%m') as dm, sum(p.total_ht) as amount_ht,sum(p.total_ttc) as amount_ttc";
		$sql .= " FROM ".MAIN_DB_PREFIX."expensereport as p";
		$sql .= " INNER JOIN ".MAIN_DB_PREFIX."user as u ON u.rowid=p.fk_user_author";
		$sql .= " INNER JOIN ".MAIN_DB_PREFIX."payment_expensereport as pe ON pe.fk_expensereport = p.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_paiement as c ON pe.fk_typepayment = c.id";
		$sql .= " WHERE p.entity IN (".getEntity('expensereport').")";
		$sql .= " AND p.fk_statut>=5";

		$column = 'pe.datep';
		if (!empty($date_start) && !empty($date_end)) {
			$sql .= " AND ".$column." >= '".$db->idate($date_start)."' AND ".$column." <= '".$db->idate($date_end)."'";
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
} elseif ($modecompta == 'BOOKKEEPING') {
	// Nothing from this table
}


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
} elseif ($modecompta == 'BOOKKEEPING') {
	// Nothing from this table
}

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
	$predefinedgroupwhere = "(";
	$predefinedgroupwhere .= " (aa.pcg_type = 'EXPENSE')";
	$predefinedgroupwhere .= " OR ";
	$predefinedgroupwhere .= " (aa.pcg_type = 'INCOME')";
	$predefinedgroupwhere .= ")";

	$charofaccountstring = getDolGlobalInt('CHARTOFACCOUNTS');
	$charofaccountstring = dol_getIdFromCode($db, getDolGlobalInt('CHARTOFACCOUNTS'), 'accounting_system', 'rowid', 'pcg_version');

	$sql = "SELECT b.doc_ref, b.numero_compte, b.subledger_account, b.subledger_label, aa.pcg_type, date_format(b.doc_date,'%Y-%m') as dm, sum(b.debit) as debit, sum(b.credit) as credit, sum(b.montant) as amount";
	$sql .= " FROM ".MAIN_DB_PREFIX."accounting_bookkeeping as b, ".MAIN_DB_PREFIX."accounting_account as aa";
	$sql .= " WHERE b.entity = ".$conf->entity;
	$sql .= " AND aa.entity = ".$conf->entity;
	$sql .= " AND b.numero_compte = aa.account_number";
	$sql .= " AND ".$predefinedgroupwhere;
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

				if ($obj->pcg_type == 'INCOME') {
					if (!isset($encaiss[$obj->dm])) {
						$encaiss[$obj->dm] = 0;	// To avoid warning of var not defined
					}
					$encaiss[$obj->dm] += $obj->credit;
					$encaiss[$obj->dm] -= $obj->debit;
				}
				if ($obj->pcg_type == 'EXPENSE') {
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
	print "<td>".dol_print_date(dol_mktime(12, 0, 0, $mois_modulo, 1, $annee), "%B")."</td>";
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
				print '<a href="clientfourn.php?year='.$annee_decalage.'&month='.$mois_modulo.($modecompta ? '&modecompta='.$modecompta : '').'">'.price(price2num($decaiss[$case], 'MT')).'</a>';
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
				print '<a href="clientfourn.php?year='.$annee_decalage.'&month='.$mois_modulo.($modecompta ? '&modecompta='.$modecompta : '').'">'.price(price2num($encaiss[$case], 'MT')).'</a>';
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
