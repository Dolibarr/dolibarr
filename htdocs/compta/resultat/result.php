<?php
/* Copyright (C) 2016-2017  Jamal Elbaz             <jamelbaz@gmail.com>
 * Copyright (C) 2016-2022  Alexandre Spangaro      <aspangaro@open-dsi.fr>
 * Copyright (C) 2018-2020  Laurent Destailleur     <eldy@destailleur.fr>
 * Copyright (C) 2018       Frédéric France         <frederic.france@netlogic.fr>
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
 * \file 		htdocs/compta/resultat/result.php
 * \ingroup 	compta, accountancy
 * \brief 		Page for accounting result
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/accounting.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/report.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formaccounting.class.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountancycategory.class.php';

// Load translation files required by the page
$langs->loadLangs(array('compta', 'bills', 'donation', 'salaries', 'accountancy'));

$error = 0;

$mesg = '';
$action = GETPOST('action', 'aZ09');
$cat_id = GETPOST('account_category');
$selectcpt = GETPOST('cpt_bk');
$id = GETPOST('id', 'int');
$rowid = GETPOST('rowid', 'int');
$cancel = GETPOST('cancel', 'alpha');
$showaccountdetail = GETPOST('showaccountdetail', 'aZ09') ? GETPOST('showaccountdetail', 'aZ09') : 'no';


$date_startmonth = GETPOST('date_startmonth', 'int');
$date_startday = GETPOST('date_startday', 'int');
$date_startyear = GETPOST('date_startyear', 'int');
$date_endmonth = GETPOST('date_endmonth', 'int');
$date_endday = GETPOST('date_endday', 'int');
$date_endyear = GETPOST('date_endyear', 'int');

$nbofyear = 1;

// Change this to test different cases of setup
//$conf->global->SOCIETE_FISCAL_MONTH_START = 7;

// Date range
$year = GETPOST('year', 'int');		// year with current month, is the month of the period we must show
if (empty($year)) {
	$year_current = dol_print_date(dol_now('gmt'), "%Y", 'gmt');
	$month_current = dol_print_date(dol_now(), "%m");
	$year_start = $year_current - ($nbofyear - 1);
} else {
	$year_current = $year;
	$month_current = dol_print_date(dol_now(), "%m");
	$year_start = $year - ($nbofyear - 1);
}
$date_start = dol_mktime(0, 0, 0, $date_startmonth, $date_startday, $date_startyear);
$date_end = dol_mktime(23, 59, 59, $date_endmonth, $date_endday, $date_endyear);

// We define date_start and date_end
if (empty($date_start) || empty($date_end)) { // We define date_start and date_end
	$q = GETPOST("q") ? GETPOST("q", 'int') : 0;
	if ($q == 0) {
		// We define date_start and date_end
		$year_end = $year_start + ($nbofyear - 1);
		$month_start = GETPOST("month", 'int') ? GETPOST("month", 'int') : getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1);
		$date_startmonth = $month_start;
		if (!GETPOST('month')) {
			if (!$year && $month_start > $month_current) {
				$year_start--;
				$year_end--;
			}
			$month_end = $month_start - 1;
			if ($month_end < 1) {
				$month_end = 12;
			} else {
				$year_end++;
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

if (($date_start < dol_time_plus_duree($date_end, -1, 'y')) || ($date_start > $date_end)) {
	$date_end = dol_time_plus_duree($date_start - 1, 1, 'y');
}

// $date_start and $date_end are defined. We force $start_year and $nbofyear
$tmps = dol_getdate($date_start);
$start_year = $tmps['year'];
$start_month = $tmps['mon'];
$tmpe = dol_getdate($date_end);
$year_end = $tmpe['year'];
$month_end = $tmpe['mon'];
$nbofyear = ($year_end - $start_year) + 1;

$date_start_previous = dol_time_plus_duree($date_start, -1, 'y');
$date_end_previous = dol_time_plus_duree($date_end, -1, 'y');

//var_dump($date_start." ".$date_end." ".$date_start_previous." ".$date_end_previous." ".$nbofyear);


if ($cat_id == 0) {
	$cat_id = null;
}

// Define modecompta ('CREANCES-DETTES' or 'RECETTES-DEPENSES' or 'BOOKKEEPING')
$modecompta = $conf->global->ACCOUNTING_MODE;
if (isModEnabled('accounting')) {
	$modecompta = 'BOOKKEEPING';
}
if (GETPOST("modecompta", 'alpha')) {
	$modecompta = GETPOST("modecompta", 'alpha');
}

$AccCat = new AccountancyCategory($db);

// Security check
$socid = GETPOST('socid', 'int');
if ($user->socid > 0) {
	$socid = $user->socid;
}
if (isModEnabled('comptabilite')) {
	$result = restrictedArea($user, 'compta', '', '', 'resultat');
}
if (isModEnabled('accounting')) {
	$result = restrictedArea($user, 'accounting', '', '', 'comptarapport');
}
$hookmanager->initHooks(['resultreportlist']);

/*
 * View
 */

$months = array(
	$langs->trans("MonthShort01"),
	$langs->trans("MonthShort02"),
	$langs->trans("MonthShort03"),
	$langs->trans("MonthShort04"),
	$langs->trans("MonthShort05"),
	$langs->trans("MonthShort06"),
	$langs->trans("MonthShort07"),
	$langs->trans("MonthShort08"),
	$langs->trans("MonthShort09"),
	$langs->trans("MonthShort10"),
	$langs->trans("MonthShort11"),
	$langs->trans("MonthShort12"),
);

llxheader('', $langs->trans('ReportInOut'));

$formaccounting = new FormAccounting($db);
$form = new Form($db);

$textprevyear = '<a href="'.$_SERVER["PHP_SELF"].'?year='.($start_year - 1).'&showaccountdetail='.urlencode($showaccountdetail).'">'.img_previous().'</a>';
$textnextyear = ' &nbsp; <a href="'.$_SERVER["PHP_SELF"].'?year='.($start_year + 1).'&showaccountdetail='.urlencode($showaccountdetail).'">'.img_next().'</a>';



// Affiche en-tete de rapport
if ($modecompta == "CREANCES-DETTES") {
	$name = $langs->trans("AnnualByAccountDueDebtMode");
	$calcmode = $langs->trans("CalcModeDebt");
	$calcmode .= '<br>('.$langs->trans("SeeReportInInputOutputMode", '<a href="'.$_SERVER["PHP_SELF"].'?year='.$start_year.(GETPOST("month") > 0 ? '&month='.GETPOST("month") : '').'&modecompta=RECETTES-DEPENSES">', '</a>').')';
	if (isModEnabled('accounting')) {
		$calcmode .= '<br>('.$langs->trans("SeeReportInBookkeepingMode", '<a href="'.$_SERVER["PHP_SELF"].'?year='.$start_year.'&modecompta=BOOKKEEPING">', '</a>').')';
	}
	$period = $form->selectDate($date_start, 'date_start', 0, 0, 0, '', 1, 0).' - '.$form->selectDate($date_end, 'date_end', 0, 0, 0, '', 1, 0);
	//$periodlink='<a href="'.$_SERVER["PHP_SELF"].'?year='.($year-1).'&modecompta='.$modecompta.'">'.img_previous().'</a> <a href="'.$_SERVER["PHP_SELF"].'?year='.($year+1).'&modecompta='.$modecompta.'">'.img_next().'</a>';
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
	$name = $langs->trans("AnnualByAccountInputOutputMode");
	$calcmode = $langs->trans("CalcModeEngagement");
	$calcmode .= '<br>('.$langs->trans("SeeReportInDueDebtMode", '<a href="'.$_SERVER["PHP_SELF"].'?year='.$year.(GETPOST("month") > 0 ? '&month='.GETPOST("month") : '').'&modecompta=CREANCES-DETTES">', '</a>').')';
	if (isModEnabled('accounting')) {
		$calcmode .= '<br>('.$langs->trans("SeeReportInBookkeepingMode", '<a href="'.$_SERVER["PHP_SELF"].'?year='.$year.'&modecompta=BOOKKEEPING">', '</a>').')';
	}
	$period = $form->selectDate($date_start, 'date_start', 0, 0, 0, '', 1, 0).' - '.$form->selectDate($date_end, 'date_end', 0, 0, 0, '', 1, 0);
	//$periodlink='<a href="'.$_SERVER["PHP_SELF"].'?year='.($year-1).'&modecompta='.$modecompta.'">'.img_previous().'</a> <a href="'.$_SERVER["PHP_SELF"].'?year='.($year+1).'&modecompta='.$modecompta.'">'.img_next().'</a>';
	$description = $langs->trans("RulesResultInOut");
	$builddate = dol_now();
	//$exportlink=$langs->trans("NotYetAvailable");
} elseif ($modecompta == "BOOKKEEPING") {
	$name = $langs->trans("ReportInOut").', '.$langs->trans("ByPersonalizedAccountGroups");
	$calcmode = $langs->trans("CalcModeBookkeeping");
	//$calcmode.='<br>('.$langs->trans("SeeReportInDueDebtMode",'<a href="'.$_SERVER["PHP_SELF"].'?year_start='.$year_start.'&modecompta=CREANCES-DETTES">','</a>').')';
	//$calcmode.='<br>('.$langs->trans("SeeReportInInputOutputMode",'<a href="'.$_SERVER["PHP_SELF"].'?year_start='.$year_start.'&modecompta=RECETTES-DEPENSES">','</a>').')';
	$period = $form->selectDate($date_start, 'date_start', 0, 0, 0, '', 1, 0).' - '.$form->selectDate($date_end, 'date_end', 0, 0, 0, '', 1, 0);
	$arraylist = array('no'=>$langs->trans("None"), 'yes'=>$langs->trans("AccountWithNonZeroValues"), 'all'=>$langs->trans("All"));
	$period .= ' &nbsp; &nbsp; <span class="opacitymedium">'.$langs->trans("DetailBy").'</span> '.$form->selectarray('showaccountdetail', $arraylist, $showaccountdetail, 0);
	$periodlink = $textprevyear.$textnextyear;
	$exportlink = '';
	$description = $langs->trans("RulesResultBookkeepingPersonalized");
	$description .= ' ('.$langs->trans("SeePageForSetup", DOL_URL_ROOT.'/accountancy/admin/categories_list.php?search_country_id='.$mysoc->country_id.'&mainmenu=accountancy&leftmenu=accountancy_admin', $langs->transnoentitiesnoconv("Accountancy").' / '.$langs->transnoentitiesnoconv("Setup").' / '.$langs->transnoentitiesnoconv("AccountingCategory")).')';
	$builddate = dol_now();
}

report_header($name, '', $period, $periodlink ?? '', $description, $builddate, $exportlink ?? '', array('modecompta'=>$modecompta, 'action' => ''), $calcmode);


if (isModEnabled('accounting') && $modecompta != 'BOOKKEEPING') {
	print info_admin($langs->trans("WarningReportNotReliable"), 0, 0, 1);
}


$moreforfilter = '';

print '<div class="div-table-responsive">';
print '<table class="tagtable liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

print '<tr class="liste_titre">';
print '<th class="liste_titre">'.$langs->trans("AccountingCategory").'</th>';
print '<th class="liste_titre"></th>';
print '<th class="liste_titre right">'.$langs->trans("PreviousPeriod").'</th>';
print '<th class="liste_titre right">'.$langs->trans("SelectedPeriod").'</th>';
foreach ($months as $k => $v) {
	if (($k + 1) >= $date_startmonth && $k < $date_endmonth) {
		print '<th class="liste_titre right width50">'.$langs->trans('MonthShort'.sprintf("%02s", ($k + 1))).'</th>';
	}
}
foreach ($months as $k => $v) {
	if (($k + 1) < $date_startmonth) {
		print '<th class="liste_titre right width50">'.$langs->trans('MonthShort'.sprintf("%02s", ($k + 1))).'</th>';
	}
}
print	'</tr>';

if ($modecompta == 'CREANCES-DETTES') {
	//if (!empty($date_start) && !empty($date_end))
	//	$sql.= " AND f.datef >= '".$db->idate($date_start)."' AND f.datef <= '".$db->idate($date_end)."'";
} elseif ($modecompta == "RECETTES-DEPENSES") {
	//if (!empty($date_start) && !empty($date_end))
	//	$sql.= " AND p.datep >= '".$db->idate($date_start)."' AND p.datep <= '".$db->idate($date_end)."'";
} elseif ($modecompta == "BOOKKEEPING") {
	// Get array of all report groups that are active
	$cats = $AccCat->getCats(); // WARNING: Computed groups must be after group they include
	$unactive_cats = $AccCat->getCats(-1, 0);

	/*
	$sql = 'SELECT DISTINCT t.numero_compte as nb FROM '.MAIN_DB_PREFIX.'accounting_bookkeeping as t, '.MAIN_DB_PREFIX.'accounting_account as aa';
	$sql.= " WHERE t.numero_compte = aa.account_number AND aa.fk_accounting_category = 0";
	if (!empty($date_start) && !empty($date_end))
		$sql.= " AND t.doc_date >= '".$db->idate($date_start)."' AND t.doc_date <= '".$db->idate($date_end)."'";
	if (!empty($month)) {
		$sql .= " AND MONTH(t.doc_date) = " . ((int) $month);
	}
	$resql = $db->query($sql);
	if ($resql)
	{
		$num_rows = $db->num_rows($resql);
		if ($num_rows) {

			print '<div class="warning">Warning: There is '.$num_rows.' accounts in your ledger table that are not set into a reporting group</div>';
			$i = 0;
			//while ($i < $num) {
			//	$obj = $db->fetch_object($resql);
			//	$i++;
			//}
		}
	}
	else dol_print_error($db);
	*/

	$j = 1;
	$sommes = array();
	$totPerAccount = array();
	if (!is_array($cats) && $cats < 0) {
		setEventMessages(null, $AccCat->errors, 'errors');
	} elseif (is_array($cats) && count($cats) > 0) {
		// Loop on each custom group of accounts
		foreach ($cats as $cat) {
			if (!empty($cat['category_type'])) {
				// category calculed
				// When we enter here, $sommes was filled by group of accounts

				$formula = $cat['formula'];

				print '<tr class="liste_total">';

				// Code and Label
				print '<td class="liste_total tdoverflowmax100" title="'.dol_escape_htmltag($cat['code']).'">';
				print dol_escape_htmltag($cat['code']);
				print '</td><td class="tdoverflowmax250 borderright" title="'.dol_escape_htmltag($cat['label']).'">';
				print dol_escape_htmltag($cat['label']);
				print '</td>';

				$vars = array();

				// Unactive categories have a total of 0 to be used in the formula.
				foreach ($unactive_cats as $un_cat) {
					$vars[$un_cat['code']] = 0;
				}

				// Previous Fiscal year (N-1)
				foreach ($sommes as $code => $det) {
					$vars[$code] = empty($det['NP']) ? 0 : $det['NP'];
				}

				$result = strtr($formula, $vars);
				$result = str_replace('--', '+', $result);

				if (preg_match('/[a-z]/i', $result)) {
					$r = 'Error bad formula: '.$result;
					$rshort = 'Err';
					print '<td class="liste_total right"><span class="amount" title="'.dol_escape_htmltag($r).'">'.$rshort.'</span></td>';
				} else {
					//var_dump($result);
					//$r = $AccCat->calculate($result);
					$r = dol_eval($result, 1, 1, '1');
					if (is_nan($r)) {
						$r = 0;
					}

					print '<td class="liste_total right"><span class="amount">'.price($r).'</span></td>';
				}

				// Year N
				$code = $cat['code']; // code of categorie ('VTE', 'MAR', ...)
				if (empty($sommes[$code]['NP'])) {
					$sommes[$code]['NP'] = $r;
				} else {
					$sommes[$code]['NP'] += $r;
				}

				// Current fiscal year (N)
				if (is_array($sommes) && !empty($sommes)) {
					foreach ($sommes as $code => $det) {
						$vars[$code] = empty($det['N']) ? 0 : $det['N'];
					}
				}

				$result = strtr($formula, $vars);
				$result = str_replace('--', '+', $result);

				//$r = $AccCat->calculate($result);
				$r = dol_eval($result, 1, 1, '1');
				if (is_nan($r)) {
					$r = 0;
				}

				print '<td class="liste_total right borderright"><span class="amount">'.price($r).'</span></td>';
				if (empty($sommes[$code]['N'])) {
					$sommes[$code]['N'] = $r;
				} else {
					$sommes[$code]['N'] += $r;
				}

				// Detail by month
				foreach ($months as $k => $v) {
					if (($k + 1) >= $date_startmonth && $k < $date_endmonth) {
						foreach ($sommes as $code => $det) {
							$vars[$code] = empty($det['M'][$k]) ? 0 : $det['M'][$k];
						}
						$result = strtr($formula, $vars);
						$result = str_replace('--', '+', $result);

						//$r = $AccCat->calculate($result);
						$r = dol_eval($result, 1, 1, '1');
						if (is_nan($r)) {
							$r = 0;
						}

						print '<td class="liste_total right"><span class="amount">'.price($r).'</span></td>';
						if (empty($sommes[$code]['M'][$k])) {
							$sommes[$code]['M'][$k] = $r;
						} else {
							$sommes[$code]['M'][$k] += $r;
						}
					}
				}

				foreach ($months as $k => $v) {
					if (($k + 1) < $date_startmonth) {
						foreach ($sommes as $code => $det) {
							$vars[$code] = empty($det['M'][$k]) ? 0 : $det['M'][$k];
						}
						$result = strtr($formula, $vars);
						$result = str_replace('--', '+', $result);

						//$r = $AccCat->calculate($result);
						$r = dol_eval($result, 1, 1, '1');
						if (is_nan($r)) {
							$r = 0;
						}

						print '<td class="liste_total right"><span class="amount">'.price($r).'</span></td>';
						if (empty($sommes[$code]['M'][$k])) {
							$sommes[$code]['M'][$k] = $r;
						} else {
							$sommes[$code]['M'][$k] += $r;
						}
					}
				}

				print "</tr>\n";

				//var_dump($sommes);
			} else { // normal category
				$code = $cat['code']; // Category code we process

				$totCat = array();
				$totCat['NP'] = 0;
				$totCat['N'] = 0;
				$totCat['M'] = array();
				foreach ($months as $k => $v) {
					$totCat['M'][$k] = 0;
				}

				// Set $cpts with array of accounts in the category/group
				$cpts = $AccCat->getCptsCat($cat['rowid']);
				// We should loop over empty $cpts array, else the category _code_ is used in the formula, which leads to wrong result if the code is a number.
				if (empty($cpts)) {
					$cpts[] = array();
				}

				$arrayofaccountforfilter = array();
				foreach ($cpts as $i => $cpt) {    // Loop on each account.
					if (isset($cpt['account_number'])) {
						$arrayofaccountforfilter[] = $cpt['account_number'];
					}
				}

				// N-1
				if (!empty($arrayofaccountforfilter)) {
					$return = $AccCat->getSumDebitCredit($arrayofaccountforfilter, $date_start_previous, $date_end_previous, empty($cat['dc']) ? 0 : $cat['dc']);
					if ($return < 0) {
						setEventMessages(null, $AccCat->errors, 'errors');
						$resultNP = 0;
					} else {
						foreach ($cpts as $i => $cpt) {    // Loop on each account found
							$resultNP = empty($AccCat->sdcperaccount[$cpt['account_number']]) ? 0 : $AccCat->sdcperaccount[$cpt['account_number']];

							if (empty($totCat['NP'])) {
								$totCat['NP'] = $resultNP;
							} else {
								$totCat['NP'] += $resultNP;
							}
							if (empty($sommes[$code]['NP'])) {
								$sommes[$code]['NP'] = $resultNP;
							} else {
								$sommes[$code]['NP'] += $resultNP;
							}
							$totPerAccount[$cpt['account_number']]['NP'] = $resultNP;
						}
					}
				}

				// Set value into column N and month M ($totCat)
				// This make 12 calls for each accountancy account (12 monthes M)
				foreach ($cpts as $i => $cpt) {    // Loop on each account.
					// We make 1 loop for each account because we may want detail per account.
					// @todo Optimize to ask a 'group by' account and a filter with account in (..., ...) in request

					// Each month
					$resultN = 0;
					foreach ($months as $k => $v) {
						$monthtoprocess = $k + 1; // ($k+1) is month 1, 2, ..., 12
						$yeartoprocess = $start_year;
						if (($k + 1) < $start_month) {
							$yeartoprocess++;
						}

						//var_dump($monthtoprocess.'_'.$yeartoprocess);
						if (isset($cpt['account_number'])) {
							$return = $AccCat->getSumDebitCredit($cpt['account_number'], $date_start, $date_end, empty($cat['dc']) ? 0 : $cat['dc'], 'nofilter', $monthtoprocess, $yeartoprocess);
							if ($return < 0) {
								setEventMessages(null, $AccCat->errors, 'errors');
								$resultM = 0;
							} else {
								$resultM = $AccCat->sdc;
							}
						} else {
							$resultM = 0;
						}
						if (empty($totCat['M'][$k])) {
							$totCat['M'][$k] = $resultM;
						} else {
							$totCat['M'][$k] += $resultM;
						}
						if (empty($sommes[$code]['M'][$k])) {
							$sommes[$code]['M'][$k] = $resultM;
						} else {
							$sommes[$code]['M'][$k] += $resultM;
						}
						if (isset($cpt['account_number'])) {
							$totPerAccount[$cpt['account_number']]['M'][$k] = $resultM;
						}

						$resultN += $resultM;
					}

					if (empty($totCat)) {
						$totCat['N'] = $resultN;
					} else {
						$totCat['N'] += $resultN;
					}
					if (empty($sommes[$code]['N'])) {
						$sommes[$code]['N'] = $resultN;
					} else {
						$sommes[$code]['N'] += $resultN;
					}
					if (isset($cpt['account_number'])) {
						$totPerAccount[$cpt['account_number']]['N'] = $resultN;
					}
				}


				// Now output columns for row $code ('VTE', 'MAR', ...)

				print '<tr'.($showaccountdetail != 'no' ? ' class="trforbreak"' : '').'>';

				// Column group
				print '<td class="tdoverflowmax100" title="'.dol_escape_htmltag($cat['code']).'">';
				print dol_escape_htmltag($cat['code']);
				print '</td>';

				// Label of group
				$labeltoshow = dol_escape_htmltag($cat['label']);
				if (count($cpts) > 0 && !empty($cpts[0])) {    // Show example of 5 first accounting accounts
					$i = 0;
					foreach ($cpts as $cpt) {
						if ($i > 5) {
							$labeltoshow .= '...)';
							break;
						}
						if ($i > 0) {
							$labeltoshow .= ', ';
						} else {
							$labeltoshow .= ' (';
						}
						$labeltoshow .= dol_escape_htmltag($cpt['account_number']);
						$i++;
					}
					if ($i <= 5) {
						$labeltoshow .= ')';
					}
				} else {
					$labeltoshow .= ' - <span class="warning">'.$langs->trans("GroupIsEmptyCheckSetup").'</span>';
				}
				print '<td class="tdoverflowmax250 borderright" title="'.dol_escape_htmltag(dol_string_nohtmltag($labeltoshow)).'">';
				print $labeltoshow;
				print '</td>';

				print '<td class="right"><span class="amount">'.price($totCat['NP']).'</span></td>';
				print '<td class="right borderright"><span class="amount">'.price($totCat['N']).'</span></td>';

				// Each month
				foreach ($totCat['M'] as $k => $v) {
					if (($k + 1) >= $date_startmonth && $k < $date_endmonth) {
						print '<td class="right nowraponall"><span class="amount">'.price($v).'</span></td>';
					}
				}
				foreach ($totCat['M'] as $k => $v) {
					if (($k + 1) < $date_startmonth) {
						print '<td class="right nowraponall"><span class="amount">'.price($v).'</span></td>';
					}
				}

				print "</tr>\n";

				// Loop on detail of all accounts to output the detail
				if ($showaccountdetail != 'no') {
					foreach ($cpts as $i => $cpt) {
						if (isset($cpt['account_number'])) {
							$resultNP = $totPerAccount[$cpt['account_number']]['NP'];
							$resultN = $totPerAccount[$cpt['account_number']]['N'];
						} else {
							$resultNP = 0;
							$resultN = 0;
						}

						if ($showaccountdetail == 'all' || $resultN != 0) {
							print '<tr>';
							print '<td></td>';

							if (isset($cpt['account_number'])) {
								$labeldetail = ' &nbsp; &nbsp; '.length_accountg($cpt['account_number']).' - '.$cpt['account_label'];
							} else {
								$labeldetail = '-';
							}

							print '<td class="tdoverflowmax250 borderright" title="'.dol_escape_htmltag($labeldetail).'">';
							print dol_escape_htmltag($labeldetail);
							print '</td>';
							print '<td class="right"><span class="amount">'.price($resultNP).'</span></td>';
							print '<td class="right borderright"><span class="amount">'.price($resultN).'</span></td>';

							// Make one call for each month
							foreach ($months as $k => $v) {
								if (($k + 1) >= $date_startmonth && $k < $date_endmonth) {
									if (isset($cpt['account_number'])) {
										$resultM = $totPerAccount[$cpt['account_number']]['M'][$k];
									} else {
										$resultM = 0;
									}
									print '<td class="right"><span class="amount">'.price($resultM).'</span></td>';
								}
							}
							foreach ($months as $k => $v) {
								if (($k + 1) < $date_startmonth) {
									if (isset($cpt['account_number'])) {
										$resultM = empty($totPerAccount[$cpt['account_number']]['M'][$k]) ? 0 : $totPerAccount[$cpt['account_number']]['M'][$k];
									} else {
										$resultM = 0;
									}
									print '<td class="right"><span class="amount">'.price($resultM).'</span></td>';
								}
							}
							print "</tr>\n";
						}
					}
				}
			}
		}
	}
}

print "</table>";
print '</div>';

// End of page
llxFooter();
$db->close();
