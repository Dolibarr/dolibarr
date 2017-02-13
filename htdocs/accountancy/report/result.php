<?php
/* Copyright (C) 2016		Jamal Elbaz			<jamelbaz@gmail.pro>
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
require_once DOL_DOCUMENT_ROOT . '/accountancy/class/accountancycategory.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formaccounting.class.php';

$error = 0;

// Langs
$langs->load("accountancy");
$langs->load("compta");

$mesg = '';
$action = GETPOST('action');
$cat_id = GETPOST('account_category');
$selectcpt = GETPOST('cpt_bk');
$id = GETPOST('id', 'int');
$rowid = GETPOST('rowid', 'int');
$cancel = GETPOST('cancel');

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

print load_fiche_titre($langs->trans('ReportInOut'), $textprevyear . " " . $langs->trans("Year") . " " . $year_start . " " . $textnextyear, 'title_accountancy');

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

$cats = $AccCat->getCatsCpts();
if ($cats < 0) dol_print_error($db, $AccCat->error, $AccCat->errors);

$catsCalcule = $AccCat->getCatsCal();
if ($catsCalcule < 0) dol_print_error($db, $AccCat->error, $AccCat->errors);

$j=1;
$sommes = array();

if (!empty($cats))
{
	foreach ($cats as $name_cat => $cpts)
	{
		print "<tr class='liste_titre'>";
		print '<td colspan="17">' . $name_cat . '</td>';
		print "</tr>\n";
		$position = -1;
		$code = -1;
		foreach($cpts as $i => $cpt){
			$var = ! $var;

			$position = $cpt['position'];
			$code = $cpt['code'];

			$return = $AccCat->getResult($cpt['account_number'], 0, $year_current -1, $cpt['dc']);
			if ($return < 0) {
				setEventMessages(null, $AccCat->errors, 'errors');
				$resultNP=0;
			} else {
				$resultNP=$AccCat->sdc;
			}

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
			print '<td>' . $cpt['account_number'] . '</td>';
			print '<td>' . $cpt['name_cpt'] . '</td>';
			print '<td>' . price($resultNP)  . '</td>';
			print '<td>' . price($resultN) . '</td>';

			foreach($months as $k => $v){
				$return = $AccCat->getResult($cpt['account_number'], $k+1, $year_current, $cpt['dc']);
				if ($return < 0) {
					setEventMessages(null, $AccCat->errors, 'errors');
					$resultM=0;
				} else {
					$resultM=$AccCat->sdc;
				}
				$sommes[$code]['M'][$k] += $resultM;
				print '<td align="right">' . price($resultM) . '</td>';
			}

			print "</tr>\n";
		}

		// If it's a calculated catgory
		$p = $position + 1;
		if(array_key_exists($p, $catsCalcule)){
			$formula = $catsCalcule[$p]['formula'];

			print "<tr class='liste_titre'>";
			print '<td colspan="2">' . $catsCalcule[$p]['label'] . '</td>';

			$vars = array();

			// Previous Fiscal year (N-1)
			foreach($sommes as $code => $det){
				$vars[$code] = $det['NP'];
			}
			$result = strtr($formula, $vars);
			eval( '$result = (' . $result . ');' );
			print '<td align="right">' . price($result) . '</td>';
			$code = $catsCalcule[$p]['code']; // code categorie de calcule
			$sommes[$code]['NP'] += $result;

			// Current fiscal year (N)
			foreach($sommes as $code => $det){
				$vars[$code] = $det['N'];
			}
			$result = strtr($formula, $vars);
			eval( '$result = (' . $result . ');' );
			print '<td align="right">' . price($result) . '</td>';
			$sommes[$code]['N'] += $result;

			// Detail by month
			foreach($months as $k => $v){
				foreach($sommes as $code => $det){
					$vars[$code] = $det['M'][$k];
				}
				$result = strtr($formula, $vars);
				eval( '$result = (' . $result . ');' );
				print '<td align="right">' . price($result) . '</td>';
				$sommes[$code]['M'][$k] += $result;
			}

			//print '<td colspan="15">' . $catsCalcule[$p]['formula'] . '</td>';
			print "</tr>\n";
			unset($catsCalcule[$p]); // j'élimine la catégorie calculée après affichage
		}
		$j++;
	}

	// Others calculed category
	foreach($catsCalcule as $p => $catc)
	{
		$formula = $catsCalcule[$p]['formula'];

		print "<tr class='liste_titre'>";
		print '<td colspan="2">' . $catsCalcule[$p]['label'] . '</td>';

		$vars = array();

		// Previous Fiscal year (N-1)
		foreach($sommes as $code => $det){
			$vars[$code] = $det['NP'];
		}
		$result = strtr($formula, $vars);
		eval( '$result = (' . $result . ');' );
		print '<td align="right">' . price($result) . '</td>';
		$code = $catsCalcule[$p]['code']; // code categorie de calcule
		$sommes[$code]['NP'] += $result;

		// Current fiscal year (N)
		foreach($sommes as $code => $det){
			$vars[$code] = $det['N'];
		}
		$result = strtr($formula, $vars);
		eval( '$result = (' . $result . ');' );
		print '<td align="right">' . price($result) . '</td>';
		$sommes[$code]['N'] += $result;

		// Detail by month
		foreach($months as $k => $v){
			foreach($sommes as $code => $det){
				$vars[$code] = $det['M'][$k];
			}
			$result = strtr($formula, $vars);
			eval( '$result = (' . $result . ');' );
			print '<td align="right">' . price($result) . '</td>';
			$sommes[$code]['M'][$k] += $result;
		}

		//print '<td colspan="15">' . $catsCalcule[$p]['formula'] . '</td>';
		print "</tr>\n";
	}
}

print "</table>";
print '</div>';

llxFooter();
$db->close();