<?php
/* Copyright (C) 2016/17		Jamal Elbaz			<jamelbaz@gmail.com>
 * Copyright (C) 2016 		Alexandre Spangaro	<aspangaro.dolibarr@gmail.com>
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
 * \file 		htdocs/accountancy/report/result.php
 * \ingroup 	Advanced accountancy
 * \brief 		Page for accounting result
 */
require '../../main.inc.php';

// Class
require_once DOL_DOCUMENT_ROOT . '/core/lib/accounting.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/report.lib.php';
require_once DOL_DOCUMENT_ROOT . '/accountancy/class/accountancycategory.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formaccounting.class.php';

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


// Filter
$year = GETPOST('year','int');
if ($year == 0) {
	$year_current = strftime("%Y", time());
	$year_start = $year_current;
} else {
	$year_current = $year;
	$year_start = $year;
}

if($cat_id == 0){
	$cat_id = null;
}

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

$nom = $langs->trans("ReportInOut").', '.$langs->trans("ByAccounts");
$nomlink = '';
$periodlink = '';
$exportlink = '';
$builddate = time();
$description = '';
$period = $langs->trans("Detail").' '. $form->selectyesno('simple_report',$simple_report,0) . " " .$textprevyear . " " . $langs->trans("Year") . " " . $year_start . " " . $textnextyear ;
report_header($nom, $nomlink, $period, $periodlink, $description, $builddate, $exportlink, array('action' => ''));

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