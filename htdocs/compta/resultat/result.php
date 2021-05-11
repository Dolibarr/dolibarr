<?php
/* Copyright (C) 2016-2017		Jamal Elbaz			<jamelbaz@gmail.com>
 * Copyright (C) 2016 	    	Alexandre Spangaro	<aspangaro.dolibarr@gmail.com>
 * Copyright (C) 2018 	    	Laurent Destailleur <eldy@destailleur.fr>
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
 */

/**
 * \file 		htdocs/compta/resultat/result.php
 * \ingroup 	compta, accountancy
 * \brief 		Page for accounting result
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/accounting.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/report.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formaccounting.class.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountancycategory.class.php';

// Load translation files required by the page
$langs->loadLangs(array('compta','bills','donation','salaries','accountancy'));

$error = 0;

$mesg = '';
$action = GETPOST('action','aZ09');
$cat_id = GETPOST('account_category');
$selectcpt = GETPOST('cpt_bk');
$id = GETPOST('id', 'int');
$rowid = GETPOST('rowid', 'int');
$cancel = GETPOST('cancel','alpha');
$showaccountdetail = GETPOST('showaccountdetail','aZ09')?GETPOST('showaccountdetail','aZ09'):'no';


$date_startmonth=GETPOST('date_startmonth','int');
$date_startday=GETPOST('date_startday','int');
$date_startyear=GETPOST('date_startyear','int');
$date_endmonth=GETPOST('date_endmonth','int');
$date_endday=GETPOST('date_endday','int');
$date_endyear=GETPOST('date_endyear','int');

$nbofyear=1;

// Date range
$year=GETPOST('year','int');
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
$date_start=dol_mktime(0, 0, 0, $date_startmonth, $date_startday, $date_startyear);
$date_end=dol_mktime(23, 59, 59, $date_endmonth, $date_endday, $date_endyear);

// We define date_start and date_end
if (empty($date_start) || empty($date_end)) // We define date_start and date_end
{
	$q=GETPOST("q")?GETPOST("q"):0;
	if ($q==0)
	{
		// We define date_start and date_end
		$year_end=$year_start + ($nbofyear - 1);
		$month_start=GETPOST("month")?GETPOST("month"):($conf->global->SOCIETE_FISCAL_MONTH_START?($conf->global->SOCIETE_FISCAL_MONTH_START):1);
		$date_startmonth = $month_start;
		if (! GETPOST('month'))
		{
			if (! GETPOST("year") &&  $month_start > $month_current)
			{
				$year_start--;
				$year_end--;
			}
			$month_end=$month_start-1;
			if ($month_end < 1) $month_end=12;
			else $year_end++;
		}
		else $month_end=$month_start;
		$date_start=dol_get_first_day($year_start,$month_start,false); $date_end=dol_get_last_day($year_end,$month_end,false);
	}
	if ($q==1) { $date_start=dol_get_first_day($year_start,1,false); $date_end=dol_get_last_day($year_start,3,false); }
	if ($q==2) { $date_start=dol_get_first_day($year_start,4,false); $date_end=dol_get_last_day($year_start,6,false); }
	if ($q==3) { $date_start=dol_get_first_day($year_start,7,false); $date_end=dol_get_last_day($year_start,9,false); }
	if ($q==4) { $date_start=dol_get_first_day($year_start,10,false); $date_end=dol_get_last_day($year_start,12,false); }
}

if (($date_start < dol_time_plus_duree($date_end, -1, 'y')) || ($date_start > $date_end))
{
	$date_end = dol_time_plus_duree($date_start - 1, 1, 'y');
}

// $date_start and $date_end are defined. We force $start_year and $nbofyear
$tmps=dol_getdate($date_start);
$start_year = $tmps['year'];
$tmpe=dol_getdate($date_end);
$year_end = $tmpe['year'];
$nbofyear = ($year_end - $start_year) + 1;

$date_start_previous = dol_time_plus_duree($date_start, -1, 'y');
$date_end_previous = dol_time_plus_duree($date_end, -1, 'y');

//var_dump($date_start." ".$date_end." ".$date_start_previous." ".$date_end_previous." ".$nbofyear);


if($cat_id == 0){
	$cat_id = null;
}

// Define modecompta ('CREANCES-DETTES' or 'RECETTES-DEPENSES' or 'BOOKKEEPING')
$modecompta = $conf->global->ACCOUNTING_MODE;
if (! empty($conf->accounting->enabled)) $modecompta='BOOKKEEPING';
if (GETPOST("modecompta")) $modecompta=GETPOST("modecompta",'alpha');

// Security check
if ($user->societe_id > 0)
	accessforbidden();
if (! $user->rights->accounting->comptarapport->lire)
	accessforbidden();

$AccCat = new AccountancyCategory($db);


/*
 * View
 */

$months = array(
	$langs->trans("JanuaryMin"),
	$langs->trans("FebruaryMin"),
	$langs->trans("MarchMin"),
	$langs->trans("AprilMin"),
	$langs->trans("MayMin"),
	$langs->trans("JuneMin"),
	$langs->trans("JulyMin"),
	$langs->trans("AugustMin"),
	$langs->trans("SeptemberMin"),
	$langs->trans("OctoberMin"),
	$langs->trans("NovemberMin"),
	$langs->trans("DecemberMin"),
);

llxheader('', $langs->trans('ReportInOut'));

$formaccounting = new FormAccounting($db);
$form = new Form($db);

$textprevyear = '<a href="' . $_SERVER["PHP_SELF"] . '?year=' . ($start_year - 1) . '">' . img_previous() . '</a>';
$textnextyear = '&nbsp;<a href="' . $_SERVER["PHP_SELF"] . '?year=' . ($start_year + 1) . '">' . img_next() . '</a>';



// Affiche en-tete de rapport
if ($modecompta=="CREANCES-DETTES")
{
	$name=$langs->trans("AnnualByAccountDueDebtMode");
	$calcmode=$langs->trans("CalcModeDebt");
	$calcmode.='<br>('.$langs->trans("SeeReportInInputOutputMode",'<a href="'.$_SERVER["PHP_SELF"].'?year='.$start_year.(GETPOST("month")>0?'&month='.GETPOST("month"):'').'&modecompta=RECETTES-DEPENSES">','</a>').')';
	if (! empty($conf->accounting->enabled)) $calcmode.='<br>('.$langs->trans("SeeReportInBookkeepingMode",'<a href="'.$_SERVER["PHP_SELF"].'?year='.$start_year.'&modecompta=BOOKKEEPING">','</a>').')';
	$period=$form->select_date($date_start,'date_start',0,0,0,'',1,0,1).' - '.$form->select_date($date_end,'date_end',0,0,0,'',1,0,1);
	//$periodlink='<a href="'.$_SERVER["PHP_SELF"].'?year='.($year-1).'&modecompta='.$modecompta.'">'.img_previous().'</a> <a href="'.$_SERVER["PHP_SELF"].'?year='.($year+1).'&modecompta='.$modecompta.'">'.img_next().'</a>';
	$description=$langs->trans("RulesResultDue");
	if (! empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS)) $description.= $langs->trans("DepositsAreNotIncluded");
	else  $description.= $langs->trans("DepositsAreIncluded");
	$builddate=dol_now();
	//$exportlink=$langs->trans("NotYetAvailable");
}
else if ($modecompta=="RECETTES-DEPENSES") {
	$name=$langs->trans("AnnualByAccountInputOutputMode");
	$calcmode=$langs->trans("CalcModeEngagement");
	$calcmode.='<br>('.$langs->trans("SeeReportInDueDebtMode",'<a href="'.$_SERVER["PHP_SELF"].'?year='.$year.(GETPOST("month")>0?'&month='.GETPOST("month"):'').'&modecompta=CREANCES-DETTES">','</a>').')';
	if (! empty($conf->accounting->enabled)) $calcmode.='<br>('.$langs->trans("SeeReportInBookkeepingMode",'<a href="'.$_SERVER["PHP_SELF"].'?year='.$year.'&modecompta=BOOKKEEPING">','</a>').')';
	//$period=$form->select_date($date_start,'date_start',0,0,0,'',1,0,1).' - '.$form->select_date($date_end,'date_end',1,1,0,'',1,0,1);
	$period=$form->select_date($date_start,'date_start',0,0,0,'',1,0,1).' - '.$form->select_date($date_end,'date_end',0,0,0,'',1,0,1);
	//$periodlink='<a href="'.$_SERVER["PHP_SELF"].'?year='.($year-1).'&modecompta='.$modecompta.'">'.img_previous().'</a> <a href="'.$_SERVER["PHP_SELF"].'?year='.($year+1).'&modecompta='.$modecompta.'">'.img_next().'</a>';
	$description=$langs->trans("RulesResultInOut");
	$builddate=dol_now();
	//$exportlink=$langs->trans("NotYetAvailable");
}
else if ($modecompta=="BOOKKEEPING")
{
	$name = $langs->trans("ReportInOut").', '.$langs->trans("ByPersonalizedAccountGroups");
	$calcmode=$langs->trans("CalcModeBookkeeping");
	//$calcmode.='<br>('.$langs->trans("SeeReportInDueDebtMode",'<a href="'.$_SERVER["PHP_SELF"].'?year_start='.$year_start.'&modecompta=CREANCES-DETTES">','</a>').')';
	//$calcmode.='<br>('.$langs->trans("SeeReportInInputOutputMode",'<a href="'.$_SERVER["PHP_SELF"].'?year_start='.$year_start.'&modecompta=RECETTES-DEPENSES">','</a>').')';
	$period=$form->select_date($date_start,'date_start',0,0,0,'',1,0,1).' - '.$form->select_date($date_end,'date_end',0,0,0,'',1,0,1);
	$arraylist=array('no'=>$langs->trans("No"), 'yes'=>$langs->trans("AccountWithNonZeroValues"), 'all'=>$langs->trans("All"));
	$period.=' &nbsp; &nbsp; '.$langs->trans("DetailByAccount").' '. $form->selectarray('showaccountdetail', $arraylist, $showaccountdetail, 0);
	$periodlink = $textprevyear . $textnextyear ;
	$exportlink = '';
	$description=$langs->trans("RulesResultBookkeepingPersonalized").
	$description.=' ('.$langs->trans("SeePageForSetup", DOL_URL_ROOT.'/accountancy/admin/categories_list.php?search_country_id='.$mysoc->country_id.'&mainmenu=accountancy&leftmenu=accountancy_admin', $langs->transnoentitiesnoconv("Accountancy").' / '.$langs->transnoentitiesnoconv("Setup").' / '.$langs->transnoentitiesnoconv("AccountingCategory")).')';
	//if (! empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS)) $description.= $langs->trans("DepositsAreNotIncluded");
	//else  $description.= $langs->trans("DepositsAreIncluded");
	$builddate=dol_now();
}

report_header($name, '', $period, $periodlink, $description, $builddate, $exportlink, array('modecompta'=>$modecompta, 'action' => ''), $calcmode);


if (! empty($conf->accounting->enabled) && $modecompta != 'BOOKKEEPING')
{
    print info_admin($langs->trans("WarningReportNotReliable"), 0, 0, 1);
}


$moreforfilter='';

print '<div class="div-table-responsive">';
print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'">'."\n";

print '<tr class="liste_titre">';
print '<th class="liste_titre">'.$langs->trans("AccountingCategory").'</th>';
print '<th class="liste_titre"></th>';
print '<th class="liste_titre" align="right">'.$langs->trans("PreviousPeriod").'</th>';
print '<th class="liste_titre" align="right">'.$langs->trans("SelectedPeriod").'</th>';
foreach($months as $k => $v){
	if (($k+1) >= $date_startmonth)
	{
		print '<th class="liste_titre width50" align="right" >'.$langs->trans($v).'</th>';
	}
}
foreach($months as $k => $v){
	if (($k+1) < $date_startmonth)
	{
		print '<th class="liste_titre width50" align="right" >'.$langs->trans($v).'</th>';
	}
}
print	'</tr>';

if ($modecompta == 'CREANCES-DETTES')
{
	//if (! empty($date_start) && ! empty($date_end))
	//	$sql.= " AND f.datef >= '".$db->idate($date_start)."' AND f.datef <= '".$db->idate($date_end)."'";
}
else if ($modecompta=="RECETTES-DEPENSES")
{
	//if (! empty($date_start) && ! empty($date_end))
	//	$sql.= " AND p.datep >= '".$db->idate($date_start)."' AND p.datep <= '".$db->idate($date_end)."'";
}
else if ($modecompta=="BOOKKEEPING")
{

	// Get array of all report groups that are active
	$cats = $AccCat->getCats();		// WARNING: Computed groups must be after group they include

	/*
	$sql = 'SELECT DISTINCT t.numero_compte as nb FROM '.MAIN_DB_PREFIX.'accounting_bookkeeping as t, '.MAIN_DB_PREFIX.'accounting_account as aa';
	$sql.= " WHERE t.numero_compte = aa.account_number AND aa.fk_accounting_category = 0";
	if (! empty($date_start) && ! empty($date_end))
		$sql.= " AND t.doc_date >= '".$db->idate($date_start)."' AND t.doc_date <= '".$db->idate($date_end)."'";
	if (! empty($month)) {
		$sql .= " AND MONTH(t.doc_date) = " . $month;
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

	$j=1;
	$sommes = array();
	$totPerAccount = array();

	foreach ($cats as $cat)		// Loop on each group
	{
		if (!empty($cat['category_type']))		// category calculed
		{
			// When we enter here, $sommes was filled by group of accounts

			$formula = $cat['formula'];

			print '<tr class="liste_total">';

			// Year NP
			print '<td class="liste_total width200">';
			print $cat['code'];
			print '</td><td>';
			print $cat['label'];
			print '</td>';

			$vars = array();

			// Previous Fiscal year (N-1)
			foreach($sommes as $code => $det){
				$vars[$code] = $det['NP'];
			}


			$result = strtr($formula, $vars);

			//var_dump($result);
			//$r = $AccCat->calculate($result);
			$r = dol_eval($result, 1);
			//var_dump($r);

			print '<td class="liste_total right">' . price($r) . '</td>';

			// Year N
			$code = $cat['code']; 				// code of categorie ('VTE', 'MAR', ...)
			$sommes[$code]['NP'] += $r;

			// Current fiscal year (N)
			if (is_array($sommes) && ! empty($sommes)){
				foreach($sommes as $code => $det){
					$vars[$code] = $det['N'];
				}
			}

			$result = strtr($formula, $vars);

			//$r = $AccCat->calculate($result);
			$r = dol_eval($result, 1);

			print '<td class="liste_total right">' . price($r) . '</td>';
			$sommes[$code]['N'] += $r;

			// Detail by month
			foreach($months as $k => $v)
			{
				if (($k+1) >= $date_startmonth)
				{
					foreach($sommes as $code => $det){
						$vars[$code] = $det['M'][$k];
					}
					$result = strtr($formula, $vars);

					//$r = $AccCat->calculate($result);
					$r = dol_eval($result, 1);

					print '<td class="liste_total right">' . price($r) . '</td>';
					$sommes[$code]['M'][$k] += $r;
				}
			}
			foreach($months as $k => $v)
			{
				if (($k+1) < $date_startmonth)
				{
					foreach($sommes as $code => $det){
						$vars[$code] = $det['M'][$k];
					}
					$result = strtr($formula, $vars);

					//$r = $AccCat->calculate($result);
					$r = dol_eval($result, 1);

					print '<td class="liste_total right">' . price($r) . '</td>';
					$sommes[$code]['M'][$k] += $r;
				}
			}


			print "</tr>\n";

			//var_dump($sommes);
		}
		else			// normal category
		{
			$totCat = array();
			$totCat['NP'] = 0;
			$totCat['N'] = 0;
			$totCat['M'] = array();
			foreach($months as $k => $v)
			{
				$totCat['M'][$k] = 0;
			}

			// Set $cpts of with array of accounts in the category/group
			$cpts = $AccCat->getCptsCat($cat['rowid']);

			print "<tr>";

			// Column group
			print '<td class="width200">';
			print $cat['code'];
			print '</td>';

			// Label of group
			print '<td>';
			print $cat['label'];
			if (count($cpts) > 0)	// Show example of 5 first accounting accounts
			{
				$i=0;
				foreach($cpts as $cpt)
				{
					if ($i > 5)
					{
						print '...)';
						break;
					}
					if ($i > 0) print ', ';
					else print ' (';
					print $cpt['account_number'];
					$i++;
				}
				if ($i <= 5) print ')';
			}
			else
			{
				print ' - <span class="warning">'.$langs->trans("GroupIsEmptyCheckSetup").'</span>';
			}
			print '</td>';

			$code = $cat['code'];

			// Set value into column NPrevious, N and each month M ($totCat)
			// This make 14 calls for each detail of account (NP, N and month m)
			foreach($cpts as $i => $cpt)
			{
				// N-1
				$return = $AccCat->getResult($cpt['account_number'], 0, $date_start_previous, $date_end_previous, $cpt['dc']);

				if ($return < 0) {
					setEventMessages(null, $AccCat->errors, 'errors');
					$resultNP=0;
				} else {
					$resultNP=$AccCat->sdc;
				}

				//N
				$return = $AccCat->getResult($cpt['account_number'], 0, $date_start, $date_end, $cpt['dc']);
				if ($return < 0) {
					setEventMessages(null, $AccCat->errors, 'errors');
					$resultN=0;
				} else {
					$resultN=$AccCat->sdc;
				}

				$totCat['NP'] += $resultNP;
				$totCat['N'] += $resultN;
				$sommes[$code]['NP'] += $resultNP;
				$sommes[$code]['N'] += $resultN;
				$totPerAccount[$cpt['account_number']]['NP'] = $resultNP;
				$totPerAccount[$cpt['account_number']]['N'] = $resultN;

				foreach($months as $k => $v)
				{
					$return = $AccCat->getResult($cpt['account_number'], $k+1, $date_start, $date_end, $cpt['dc']);
					if ($return < 0) {
						setEventMessages(null, $AccCat->errors, 'errors');
						$resultM=0;
					} else {
						$resultM=$AccCat->sdc;
					}
					$totCat['M'][$k] += $resultM;
					$sommes[$code]['M'][$k] += $resultM;
					$totPerAccount[$cpt['account_number']]['M'][$k] = $resultM;
				}
			}

			// Now output columns for row $code ('VTE', 'MAR', ...)
			print '<td align="right">' . price($totCat['NP'])  . '</td>';
			print '<td align="right">' . price($totCat['N']) . '</td>';

			// Each month
			foreach($totCat['M'] as $k => $v){
				if (($k+1) >= $date_startmonth) print '<td align="right">' . price($v) . '</td>';
			}
			foreach($totCat['M'] as $k => $v){
				if (($k+1) < $date_startmonth) print '<td align="right">' . price($v) . '</td>';
			}

			print "</tr>\n";

			// Loop on detail of all accounts
			// This make 14 calls for each detail of account (NP, N and month m)
			if ($showaccountdetail != 'no')
			{
				foreach($cpts as $i => $cpt)
				{
					$resultNP=$totPerAccount[$cpt['account_number']]['NP'];
					$resultN=$totPerAccount[$cpt['account_number']]['N'];

					if ($showaccountdetail == 'all' || $resultN > 0)
					{
						print '<tr>';
						print '<td></td>';
						print '<td class="tdoverflowmax200">';
						print ' &nbsp; &nbsp; ' . length_accountg($cpt['account_number']);
						print ' - ';
						print $cpt['account_label'];
						print '</td>';
						print '<td align="right">' . price($resultNP)  . '</td>';
						print '<td align="right">' . price($resultN) . '</td>';

						// Make one call for each month
						foreach($months as $k => $v)
						{
							if (($k+1) >= $date_startmonth)
							{
								$resultM=$totPerAccount[$cpt['account_number']]['M'][$k];
								print '<td align="right">' . price($resultM) . '</td>';
							}
						}
						foreach($months as $k => $v)
						{
							if (($k+1) < $date_startmonth)
							{
								$resultM=$totPerAccount[$cpt['account_number']]['M'][$k];
								print '<td align="right">' . price($resultM) . '</td>';
							}
						}

						print "</tr>\n";
					}
				}
			}
		}
	}
}

print "</table>";
print '</div>';

llxFooter();
$db->close();
