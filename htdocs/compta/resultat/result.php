<?php
/* Copyright (C) 2016-2017		Jamal Elbaz			<jamelbaz@gmail.com>
 * Copyright (C) 2016 	    	Alexandre Spangaro	<aspangaro.dolibarr@gmail.com>
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
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountancycategory.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formaccounting.class.php';

$error = 0;

// Langs
$langs->load("accountancy");
$langs->load("compta");

$mesg = '';
$action = GETPOST('action','aZ09');
$cat_id = GETPOST('account_category');
$selectcpt = GETPOST('cpt_bk');
$id = GETPOST('id', 'int');
$rowid = GETPOST('rowid', 'int');
$cancel = GETPOST('cancel');
$simple_report = GETPOST('simple_report');


$date_startmonth=GETPOST('date_startmonth');
$date_startday=GETPOST('date_startday');
$date_startyear=GETPOST('date_startyear');
$date_endmonth=GETPOST('date_endmonth');
$date_endday=GETPOST('date_endday');
$date_endyear=GETPOST('date_endyear');

$nbofyear=1;

// Date range
$year=GETPOST('year','int');
if (empty($year))
{
	$year_current = strftime("%Y",dol_now());
	$month_current = strftime("%m",dol_now());
	$year_start = $year_current - ($nbofyear - 1);
} else {
	$year_current = $year;
	$month_current = strftime("%m",dol_now());
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

llxheader('', $langs->trans('ReportInOut'));

$formaccounting = new FormAccounting($db);
$form = new Form($db);

$textprevyear = '<a href="' . $_SERVER["PHP_SELF"] . '?year=' . ($year_current - 1) . '">' . img_previous() . '</a>';
$textnextyear = '&nbsp;<a href="' . $_SERVER["PHP_SELF"] . '?year=' . ($year_current + 1) . '">' . img_next() . '</a>';



// Affiche en-tete de rapport
if ($modecompta=="CREANCES-DETTES")
{
	$name=$langs->trans("AnnualByAccountDueDebtMode");
	$calcmode=$langs->trans("CalcModeDebt");
	$calcmode.='<br>('.$langs->trans("SeeReportInInputOutputMode",'<a href="'.$_SERVER["PHP_SELF"].'?year='.$year.(GETPOST("month")>0?'&month='.GETPOST("month"):'').'&modecompta=RECETTES-DEPENSES">','</a>').')';
	$calcmode.='<br>('.$langs->trans("SeeReportInBookkeepingMode",'<a href="'.$_SERVER["PHP_SELF"].'?year_start='.$year_start.'&modecompta=BOOKKEEPING">','</a>').')';
	$period=$form->select_date($date_start,'date_start',0,0,0,'',1,0,1).' - '.$form->select_date($date_end,'date_end',0,0,0,'',1,0,1);
	//$periodlink='<a href="'.$_SERVER["PHP_SELF"].'?year='.($year-1).'&modecompta='.$modecompta.'">'.img_previous().'</a> <a href="'.$_SERVER["PHP_SELF"].'?year='.($year+1).'&modecompta='.$modecompta.'">'.img_next().'</a>';
	$description=$langs->trans("RulesResultDue");
	if (! empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS)) $description.= $langs->trans("DepositsAreNotIncluded");
	else  $description.= $langs->trans("DepositsAreIncluded");
	$builddate=time();
	//$exportlink=$langs->trans("NotYetAvailable");
}
else if ($modecompta=="RECETTES-DEPENSES") {
	$name=$langs->trans("AnnualByAccountInputOutputMode");
	$calcmode=$langs->trans("CalcModeEngagement");
	$calcmode.='<br>('.$langs->trans("SeeReportInDueDebtMode",'<a href="'.$_SERVER["PHP_SELF"].'?year='.$year.(GETPOST("month")>0?'&month='.GETPOST("month"):'').'&modecompta=CREANCES-DETTES">','</a>').')';
	$calcmode.='<br>('.$langs->trans("SeeReportInBookkeepingMode",'<a href="'.$_SERVER["PHP_SELF"].'?year_start='.$year_start.'&modecompta=BOOKKEEPING">','</a>').')';
	//$period=$form->select_date($date_start,'date_start',0,0,0,'',1,0,1).' - '.$form->select_date($date_end,'date_end',1,1,0,'',1,0,1);
	$period=$form->select_date($date_start,'date_start',0,0,0,'',1,0,1).' - '.$form->select_date($date_end,'date_end',0,0,0,'',1,0,1);
	//$periodlink='<a href="'.$_SERVER["PHP_SELF"].'?year='.($year-1).'&modecompta='.$modecompta.'">'.img_previous().'</a> <a href="'.$_SERVER["PHP_SELF"].'?year='.($year+1).'&modecompta='.$modecompta.'">'.img_next().'</a>';
	$description=$langs->trans("RulesResultInOut");
	$builddate=time();
	//$exportlink=$langs->trans("NotYetAvailable");
}
else if ($modecompta=="BOOKKEEPING")
{
	$name = $langs->trans("ReportInOut").', '.$langs->trans("ByAccounts");
	$calcmode=$langs->trans("CalcModeBookkeeping");
	$calcmode.='<br>('.$langs->trans("SeeReportInDueDebtMode",'<a href="'.$_SERVER["PHP_SELF"].'?year_start='.$year_start.'&modecompta=CREANCES-DETTES">','</a>').')';
	$calcmode.='<br>('.$langs->trans("SeeReportInInputOutputMode",'<a href="'.$_SERVER["PHP_SELF"].'?year_start='.$year_start.'&modecompta=RECETTES-DEPENSES">','</a>').')';
	$nomlink = '';
	$period=$form->select_date($date_start,'date_start',0,0,0,'',1,0,1).' - '.$form->select_date($date_end,'date_end',0,0,0,'',1,0,1);
	$period.=' &nbsp; '.$langs->trans("Detail").' '. $form->selectyesno('simple_report',$simple_report,0);
	$periodlink = $textprevyear . " " . $langs->trans("Year") . " " . $year_start . " " . $textnextyear ;
	$exportlink = '';
	$description=$langs->trans("RulesResultDue");
	if (! empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS)) $description.= $langs->trans("DepositsAreNotIncluded");
	else  $description.= $langs->trans("DepositsAreIncluded");
	$builddate = time();
}

report_header($name, $nomlink, $period, $periodlink, $description, $builddate, $exportlink, array('modecompta'=>$modecompta, 'action' => ''), $calcmode);


if (! empty($conf->accounting->enabled) && $modecompta != 'BOOKKEEPING')
{
    print info_admin($langs->trans("WarningReportNotReliable"), 0, 0, 1);
}


$moreforfilter='';

print '<div class="div-table-responsive">';
print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'">'."\n";

$months = array( $langs->trans("JanuaryMin"),
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

print '<tr class="liste_titre">';
print '<th class="liste_titre">'.$langs->trans("Account").'</th>';
print '<th class="liste_titre">'.$langs->trans("Description").'</th>';
print '<th class="liste_titre" align="center">N-1</th>';
print '<th class="liste_titre" align="center">'.$langs->trans("NReal").'</th>';
foreach($months as $k => $v){
	print '<th class="liste_titre"  align="center">'.$langs->trans($v).'</th>';
}
print	'</tr>';



//All categories
$cats = $AccCat->getCats();
if ($catsCalcule < 0) dol_print_error($db, $AccCat->error, $AccCat->errors);

$j=1;
$sommes = array();

foreach($cats as $cat ){
	if(!empty($cat['category_type'])){ // category calculed

		$formula = $cat['formula'];

		print "<tr class='liste_titre'>";
		print '<td colspan="2">' . $cat['label'] . '</td>';

		$vars = array();

		// Previous Fiscal year (N-1)
		foreach($sommes as $code => $det){
			$vars[$code] = $det['NP'];
		}


		$result = strtr($formula, $vars);


		$r = $AccCat->calculate($result);

		print '<td align="right"><font color="blue">' . price($r) . '</td>';
		$code = $cat['code']; // code categorie de calcule
		$sommes[$code]['NP'] += $r;

		// Current fiscal year (N)
		if (is_array($sommes) && ! empty($sommes)){
			foreach($sommes as $code => $det){
				$vars[$code] = $det['N'];
			}
		}

		$result = strtr($formula, $vars);

		$r = $AccCat->calculate($result);

		print '<td align="right"><font color="blue">' . price($r) . '</td>';
		$sommes[$code]['N'] += $r;

		// Detail by month
		foreach($months as $k => $v){
			foreach($sommes as $code => $det){
				$vars[$code] = $det['M'][$k];
			}
			$result = strtr($formula, $vars);
			$r = $AccCat->calculate($result);
			print '<td align="right"><font color="blue">' . price($r) . '</td>';
			$sommes[$code]['M'][$k] += $r;
		}


		print "</tr>\n";


	}else{ // normal category

		$totCat = array();
		$totCat['M'] = array();

		// get cpts of category
		$cpts = $AccCat->getCptsCat($cat['rowid']);


		print "<tr class='liste_titre'>";
		print '<td colspan="2">' . $cat['label'] . '</td>';

		foreach($cpts as $i => $cpt){
			$var = ! $var;

			$code = $cat['code'];

			// N-1
			$return = $AccCat->getResult($cpt['account_number'], 0, $year_current -1, $cpt['dc']);

			if ($return < 0) {
				setEventMessages(null, $AccCat->errors, 'errors');
				$resultNP=0;
			} else {
				$resultNP=$AccCat->sdc;
			}

			//N
			$return = $AccCat->getResult($cpt['account_number'], 0, $year_current, $cpt['dc']);
			if ($return < 0) {
				setEventMessages(null, $AccCat->errors, 'errors');
				$resultN=0;
			} else {
				$resultN=$AccCat->sdc;
			}

			$totCat['NP'] += $resultNP;
			$totCat['N'] += $resultN;

			foreach($months as $k => $v){
				$return = $AccCat->getResult($cpt['account_number'], $k+1, $year_current, $cpt['dc']);
				if ($return < 0) {
					setEventMessages(null, $AccCat->errors, 'errors');
					$resultM=0;
				} else {
					$resultM=$AccCat->sdc;
				}
				$totCat['M'][$k] += $resultM;

			}
		}

		print '<td align="right">' . price($totCat['NP'])  . '</td>';
		print '<td align="right">' . price($totCat['N']) . '</td>';

		foreach($totCat['M'] as $k => $v){
			print '<td align="right">' . price($v) . '</td>';
		}
		print "</tr>\n";

		foreach($cpts as $i => $cpt){
			$var = ! $var;

			$code = $cat['code'];

			// N-1
			$return = $AccCat->getResult($cpt['account_number'], 0, $year_current -1, $cpt['dc']);

			if ($return < 0) {
				setEventMessages(null, $AccCat->errors, 'errors');
				$resultNP=0;
			} else {
				$resultNP=$AccCat->sdc;
			}

			//N
			$return = $AccCat->getResult($cpt['account_number'], 0, $year_current, $cpt['dc']);
			if ($return < 0) {
				setEventMessages(null, $AccCat->errors, 'errors');
				$resultN=0;
			} else {
				$resultN=$AccCat->sdc;
			}

			$sommes[$code]['NP'] += $resultNP;
			$sommes[$code]['N'] += $resultN;
			print '<tr'. $bc[$var].'>';
			if ($simple_report == 'yes') {
			print '<td>' . length_accountg($cpt['account_number']) . '</td>';
			print '<td>' . $cpt['name_cpt'] . '</td>';
			print '<td align="right">' . price($resultNP)  . '</td>';
			print '<td align="right">' . price($resultN) . '</td>';
			}

			foreach($months as $k => $v){
				$return = $AccCat->getResult($cpt['account_number'], $k+1, $year_current, $cpt['dc']);
				if ($return < 0) {
					setEventMessages(null, $AccCat->errors, 'errors');
					$resultM=0;
				} else {
					$resultM=$AccCat->sdc;
				}
				$sommes[$code]['M'][$k] += $resultM;
				if ($simple_report == 'yes') {
				print '<td align="right">' . price($resultM) . '</td>';
				}
			}

			print "</tr>\n";
		}
	}

}

print "</table>";
print '</div>';

llxFooter();
$db->close();