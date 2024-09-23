<?php
/* Copyright (C) 2005       Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2017  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2013-2023  Charlene BENKE          <charlene@patas-monkey.com>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024		Frédéric France			<frederic.france@free.fr>
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
 *		\file        htdocs/compta/bank/annuel.php
 *		\ingroup     banque
 *		\brief       Page to report input-output of a bank account
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/bank.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';

// Load translation files required by the page
$langs->loadLangs(array('banks', 'categories'));

$WIDTH = DolGraph::getDefaultGraphSizeForStats('width', '380'); // Large for one graph in a smarpthone.
$HEIGHT = DolGraph::getDefaultGraphSizeForStats('height', '160');

$id = GETPOST('account') ? GETPOST('account', 'alpha') : GETPOST('id');
$ref = GETPOST('ref');

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('bankannualreport', 'globalcard'));

// Security check
$fieldvalue = (!empty($id) ? $id : (!empty($ref) ? $ref : ''));
$fieldtype = (!empty($ref) ? 'ref' : 'rowid');
if ($user->socid) {
	$socid = $user->socid;
}
$result = restrictedArea($user, 'banque', $fieldvalue, 'bank_account&bank_account', '', '', $fieldtype);

$year_start = GETPOST('year_start');
//$year_current = strftime("%Y", time());
$year_current = (int) dol_print_date(time(), "%Y");
if (!$year_start) {
	$year_start = $year_current - 2;
	$year_end = $year_current;
} else {
	$year_end = $year_start + 2;
}



/*
 * View
 */

$form = new Form($db);

// Get account information
$object = new Account($db);
if ($id > 0 && !preg_match('/,/', $id)) {	// if for a particular account and not a list
	$result = $object->fetch($id);
	$id = $object->id;
}
if (!empty($ref)) {
	$result = $object->fetch(0, $ref);
	$id = $object->id;
}

$annee = '';
$totentrees = array();
$totsorties = array();

$title = $object->ref.' - '.$langs->trans("IOMonthlyReporting");
$helpurl = "";
llxHeader('', $title, $helpurl);

// Ce rapport de tresorerie est base sur llx_bank (car doit inclure les transactions sans facture)
// plutot que sur llx_paiement + llx_paiementfourn

$sql = "SELECT SUM(b.amount)";
$sql .= ", date_format(b.dateo,'%Y-%m') as dm";
$sql .= " FROM ".MAIN_DB_PREFIX."bank as b";
$sql .= ", ".MAIN_DB_PREFIX."bank_account as ba";
$sql .= " WHERE b.fk_account = ba.rowid";
$sql .= " AND ba.entity IN (".getEntity('bank_account').")";
$sql .= " AND b.amount >= 0";
if (!empty($id)) {
	$sql .= " AND b.fk_account IN (".$db->sanitize($db->escape($id)).")";
}
$sql .= " GROUP BY dm";

$resql = $db->query($sql);
$encaiss = array();
$decaiss = array();
if ($resql) {
	$num = $db->num_rows($resql);
	$i = 0;
	while ($i < $num) {
		$row = $db->fetch_row($resql);
		$encaiss[$row[1]] = $row[0];
		$i++;
	}
} else {
	dol_print_error($db);
}

$sql = "SELECT SUM(b.amount)";
$sql .= ", date_format(b.dateo,'%Y-%m') as dm";
$sql .= " FROM ".MAIN_DB_PREFIX."bank as b";
$sql .= ", ".MAIN_DB_PREFIX."bank_account as ba";
$sql .= " WHERE b.fk_account = ba.rowid";
$sql .= " AND ba.entity IN (".getEntity('bank_account').")";
$sql .= " AND b.amount <= 0";
if (!empty($id)) {
	$sql .= " AND b.fk_account IN (".$db->sanitize($db->escape($id)).")";
}
$sql .= " GROUP BY dm";

$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);
	$i = 0;
	while ($i < $num) {
		$row = $db->fetch_row($resql);
		$decaiss[$row[1]] = -$row[0];
		$i++;
	}
} else {
	dol_print_error($db);
}


// Onglets
$head = bank_prepare_head($object);
print dol_get_fiche_head($head, 'annual', $langs->trans("FinancialAccount"), 0, 'account');

$title = $langs->trans("FinancialAccount")." : ".$object->label;
$link = ($year_start ? '<a href="'.$_SERVER["PHP_SELF"].'?account='.$object->id.'&year_start='.($year_start - 1).'">'.img_previous('', 'class="valignbottom"')."</a> ".$langs->trans("Year").' <a href="'.$_SERVER["PHP_SELF"].'?account='.$object->id.'&year_start='.($year_start + 1).'">'.img_next('', 'class="valignbottom"').'</a>' : '');

$linkback = '<a href="'.DOL_URL_ROOT.'/compta/bank/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

$morehtmlref = '';

if (!empty($id)) {
	if (!preg_match('/,/', $id)) {
		dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref, '', 0, '', '', 1);
	} else {
		$bankaccount = new Account($db);
		$listid = explode(',', $id);
		foreach ($listid as $key => $aId) {
			$bankaccount->fetch($aId);
			$bankaccount->label = $bankaccount->ref;
			print $bankaccount->getNomUrl(1);
			if ($key < (count($listid) - 1)) {
				print ', ';
			}
		}
	}
} else {
	print $langs->trans("AllAccounts");
}

print dol_get_fiche_end();


// Affiche tableau
print load_fiche_titre('', $link, '');

print '<div class="div-table-responsive">'; // You can use div-table-responsive-no-min if you don't need reserved height for your table
print '<table class="noborder centpercent">';

print '<tr class="liste_titre"><td class="liste_titre">'.$langs->trans("Month").'</td>';
for ($annee = $year_start; $annee <= $year_end; $annee++) {
	print '<td align="center" width="20%" colspan="2" class="liste_titre borderrightlight">'.$annee.'</td>';
}
print '</tr>';

print '<tr class="liste_titre">';
print '<td class="liste_titre">&nbsp;</td>';
for ($annee = $year_start; $annee <= $year_end; $annee++) {
	print '<td class="liste_titre" align="center">'.$langs->trans("Debit").'</td><td class="liste_titre" align="center">'.$langs->trans("Credit").'</td>';
}
print '</tr>';

for ($annee = $year_start; $annee <= $year_end; $annee++) {
	$totsorties[$annee] = 0;
	$totentrees[$annee] = 0;
}

for ($mois = 1; $mois < 13; $mois++) {
	print '<tr class="oddeven">';
	print "<td>".dol_print_date(dol_mktime(1, 1, 1, $mois, 1, 2000), "%B")."</td>";

	for ($annee = $year_start; $annee <= $year_end; $annee++) {
		$case = sprintf("%04d-%02d", $annee, $mois);

		print '<td class="right" width="10%">&nbsp;';
		if (isset($decaiss[$case]) && $decaiss[$case] > 0) {
			print price($decaiss[$case]);
			$totsorties[$annee] += $decaiss[$case];
		}
		print "</td>";

		print '<td class="right borderrightlight" width="10%">&nbsp;';
		if (isset($encaiss[$case]) && $encaiss[$case] > 0) {
			print price($encaiss[$case]);
			$totentrees[$annee] += $encaiss[$case];
		}
		print "</td>";
	}
	print '</tr>';
}

// Total debit-credit
print '<tr class="liste_total"><td><b>'.$langs->trans("Total")."</b></td>";
for ($annee = $year_start; $annee <= $year_end; $annee++) {
	print '<td class="right nowraponall"><b>'. (isset($totsorties[$annee]) ? price($totsorties[$annee]) : '') .'</b></td>';
	print '<td class="right nowraponall"><b>'. (isset($totentrees[$annee]) ? price($totentrees[$annee]) : '') .'</b></td>';
}
print "</tr>\n";

print "</table>";
print "</div>";

print '<br>';


// Current balance
$balance = 0;

$sql = "SELECT SUM(b.amount) as total";
$sql .= " FROM ".MAIN_DB_PREFIX."bank as b";
$sql .= ", ".MAIN_DB_PREFIX."bank_account as ba";
$sql .= " WHERE b.fk_account = ba.rowid";
$sql .= " AND ba.entity IN (".getEntity('bank_account').")";
if (!empty($id)) {
	$sql .= " AND b.fk_account IN (".$db->sanitize($db->escape($id)).")";
}

$resql = $db->query($sql);
if ($resql) {
	$obj = $db->fetch_object($resql);
	if ($obj) {
		$balance = $obj->total;
	}
} else {
	dol_print_error($db);
}

print '<table class="noborder centpercent">';

$nbcol = '';
print '<tr class="liste_total"><td><b>'.$langs->trans("CurrentBalance")."</b></td>";
print '<td colspan="'.($nbcol).'" class="right">'.price($balance).'</td>';
print "</tr>\n";

print "</table>";

// BUILDING GRAPHICS

$year = $year_end;

$result = dol_mkdir($conf->bank->dir_temp);
if ($result < 0) {
	$langs->load("errors");
	$error++;
	setEventMessages($langs->trans("ErrorFailedToCreateDir"), null, 'errors');
} else {
	// Calcul de $min et $max
	$sql = "SELECT MIN(b.datev) as min, MAX(b.datev) as max";
	$sql .= " FROM ".MAIN_DB_PREFIX."bank as b";
	$sql .= ", ".MAIN_DB_PREFIX."bank_account as ba";
	$sql .= " WHERE b.fk_account = ba.rowid";
	$sql .= " AND ba.entity IN (".getEntity('bank_account').")";
	if ($id && GETPOST("option") != 'all') {
		$sql .= " AND b.fk_account IN (".$db->sanitize($id).")";
	}

	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		$obj = $db->fetch_object($resql);
		$min = $db->jdate($obj->min);
		$max = $db->jdate($obj->max);
	} else {
		dol_print_error($db);
	}
	$log = "graph.php: min=".$min." max=".$max;
	dol_syslog($log);

	// CRED PART
	// Chargement du tableau des années
	$tblyear = array();
	$tblyear[0] = array();
	$tblyear[1] = array();
	$tblyear[2] = array();

	for ($annee = 0; $annee < 3; $annee++) {
		$sql = "SELECT date_format(b.datev,'%m')";
		$sql .= ", SUM(b.amount)";
		$sql .= " FROM ".MAIN_DB_PREFIX."bank as b";
		$sql .= ", ".MAIN_DB_PREFIX."bank_account as ba";
		$sql .= " WHERE b.fk_account = ba.rowid";
		$sql .= " AND ba.entity IN (".getEntity('bank_account').")";
		$sql .= " AND b.datev >= '".($year - $annee)."-01-01 00:00:00'";
		$sql .= " AND b.datev <= '".($year - $annee)."-12-31 23:59:59'";
		$sql .= " AND b.amount > 0";
		if ($id && GETPOST("option") != 'all') {
			$sql .= " AND b.fk_account IN (".$db->sanitize($id).")";
		}
		$sql .= " GROUP BY date_format(b.datev,'%m');";

		$resql = $db->query($sql);
		if ($resql) {
			$num = $db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$row = $db->fetch_row($resql);
				$tblyear[$annee][$row[0]] = $row[1];
				$i++;
			}
			$db->free($resql);
		} else {
			dol_print_error($db);
		}
	}
	// Chargement de labels et data_xxx pour tableau 4 Movements
	$labels = array();
	$data_year_0 = array();
	$data_year_1 = array();
	$data_year_2 = array();
	$datamin = array();

	for ($i = 0; $i < 12; $i++) {
		$data_year_0[$i] = isset($tblyear[0][substr("0".($i + 1), -2)]) ? $tblyear[0][substr("0".($i + 1), -2)] : 0;
		$data_year_1[$i] = isset($tblyear[1][substr("0".($i + 1), -2)]) ? $tblyear[1][substr("0".($i + 1), -2)] : 0;
		$data_year_2[$i] = isset($tblyear[2][substr("0".($i + 1), -2)]) ? $tblyear[2][substr("0".($i + 1), -2)] : 0;
		$labels[$i] = $langs->transnoentitiesnoconv("MonthVeryShort".sprintf("%02d", $i + 1));
		$datamin[$i] = 0;
	}

	// Fabrication tableau 4b
	$file = $conf->bank->dir_temp."/credmovement".$id."-".$year.".png";
	$fileurl = DOL_URL_ROOT.'/viewimage.php?modulepart=banque_temp&file='."/credmovement".$id."-".$year.".png";
	$title = $langs->transnoentities("Credit").' - '.$langs->transnoentities("Year").': '.($year - 2).' - '.($year - 1)." - ".$year;
	$graph_datas = array();
	for ($i = 0; $i < 12; $i++) {
		$graph_datas[$i] = array($labels[$i], $data_year_0[$i], $data_year_1[$i], $data_year_2[$i]);
	}

	$px1 = new DolGraph();
	$px1->SetData($graph_datas);
	$px1->SetLegend(array(($year), ($year - 1), ($year - 2)));
	$px1->SetLegendWidthMin(180);
	$px1->SetMaxValue($px1->GetCeilMaxValue() < 0 ? 0 : $px1->GetCeilMaxValue());
	$px1->SetMinValue($px1->GetFloorMinValue() > 0 ? 0 : $px1->GetFloorMinValue());
	$px1->SetTitle($title);
	$px1->SetWidth($WIDTH);
	$px1->SetHeight($HEIGHT);
	$px1->SetType(array('line', 'line', 'line'));
	$px1->SetShading(3);
	$px1->setBgColor('onglet');
	$px1->setBgColorGrid(array(255, 255, 255));
	$px1->SetHorizTickIncrement(1);
	$px1->draw($file, $fileurl);

	$show1 = $px1->show();

	unset($graph_datas);
	unset($px1);
	unset($tblyear[0]);
	unset($tblyear[1]);
	unset($tblyear[2]);

	// DEDBT PART
	// Chargement du tableau des années
	$tblyear[0] = array();
	$tblyear[1] = array();
	$tblyear[2] = array();

	for ($annee = 0; $annee < 3; $annee++) {
		$sql = "SELECT date_format(b.datev,'%m')";
		$sql .= ", SUM(b.amount)";
		$sql .= " FROM ".MAIN_DB_PREFIX."bank as b";
		$sql .= ", ".MAIN_DB_PREFIX."bank_account as ba";
		$sql .= " WHERE b.fk_account = ba.rowid";
		$sql .= " AND ba.entity IN (".getEntity('bank_account').")";
		$sql .= " AND b.datev >= '".($year - $annee)."-01-01 00:00:00'";
		$sql .= " AND b.datev <= '".($year - $annee)."-12-31 23:59:59'";
		$sql .= " AND b.amount < 0";
		if ($id && GETPOST("option") != 'all') {
			$sql .= " AND b.fk_account IN (".$db->sanitize($id).")";
		}
		$sql .= " GROUP BY date_format(b.datev,'%m');";

		$resql = $db->query($sql);
		if ($resql) {
			$num = $db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$row = $db->fetch_row($resql);
				$tblyear[$annee][$row[0]] = abs($row[1]);
				$i++;
			}
			$db->free($resql);
		} else {
			dol_print_error($db);
		}
	}
	// Chargement de labels et data_xxx pour tableau 4 Movements
	$labels = array();
	$data_year_0 = array();
	$data_year_1 = array();
	$data_year_2 = array();

	for ($i = 0; $i < 12; $i++) {
		$data_year_0[$i] = isset($tblyear[0][substr("0".($i + 1), -2)]) ? $tblyear[0][substr("0".($i + 1), -2)] : 0;
		$data_year_1[$i] = isset($tblyear[1][substr("0".($i + 1), -2)]) ? $tblyear[1][substr("0".($i + 1), -2)] : 0;
		$data_year_2[$i] = isset($tblyear[2][substr("0".($i + 1), -2)]) ? $tblyear[2][substr("0".($i + 1), -2)] : 0;
		$labels[$i] = $langs->transnoentitiesnoconv("MonthVeryShort".sprintf("%02d", $i + 1));
		$datamin[$i] = 0;
	}

	$file = $conf->bank->dir_temp."/debmovement".$id."-".$year.".png";
	$fileurl = DOL_URL_ROOT.'/viewimage.php?modulepart=banque_temp&file='."/debmovement".$id."-".$year.".png";
	$title = $langs->transnoentities("Debit").' - '.$langs->transnoentities("Year").': '.($year - 2).' - '.($year - 1)." - ".$year;
	$graph_datas = array();
	for ($i = 0; $i < 12; $i++) {
		$graph_datas[$i] = array($labels[$i], $data_year_0[$i], $data_year_1[$i], $data_year_2[$i]);
	}

	$px2 = new DolGraph();
	$px2->SetData($graph_datas);
	$px2->SetLegend(array(($year), ($year - 1), ($year - 2)));
	$px2->SetLegendWidthMin(180);
	$px2->SetMaxValue($px2->GetCeilMaxValue() < 0 ? 0 : $px2->GetCeilMaxValue());
	$px2->SetMinValue($px2->GetFloorMinValue() > 0 ? 0 : $px2->GetFloorMinValue());
	$px2->SetTitle($title);
	$px2->SetWidth($WIDTH);
	$px2->SetHeight($HEIGHT);
	$px2->SetType(array('line', 'line', 'line'));
	$px2->SetShading(3);
	$px2->setBgColor('onglet');
	$px2->setBgColorGrid(array(255, 255, 255));
	$px2->SetHorizTickIncrement(1);
	$px2->draw($file, $fileurl);

	$show2 = $px2->show();

	unset($graph_datas);
	unset($px2);
	unset($tblyear[0]);
	unset($tblyear[1]);
	unset($tblyear[2]);

	print '<div class="fichecenter"><div class="fichehalfleft"><div align="center">'; // do not use class="center" here, it will have no effect for the js graph inside.
	print $show1;
	print '</div></div><div class="fichehalfright"><div align="center">'; // do not use class="center" here, it will have no effect for the js graph inside.
	print $show2;
	print '</div></div></div>';
	print '<div class="clearboth"></div>';
}


print "\n</div><br>\n";

// End of page
llxFooter();
$db->close();
