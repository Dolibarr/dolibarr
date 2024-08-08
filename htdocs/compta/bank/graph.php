<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
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
 *	\file       htdocs/compta/bank/graph.php
 *	\ingroup    banque
 *	\brief      Page graph des transactions bancaires
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/bank.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';

// Load translation files required by the page
$langs->loadLangs(array('banks', 'categories'));

$WIDTH = DolGraph::getDefaultGraphSizeForStats('width', 768);
$HEIGHT = DolGraph::getDefaultGraphSizeForStats('height', 200);

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('bankstats', 'globalcard'));

// Security check
if (GETPOST('account') || GETPOST('ref')) {
	$id = GETPOST('account') ? GETPOST('account') : GETPOST('ref');
}
$fieldid = GETPOST('ref') ? 'ref' : 'rowid';
if ($user->socid) {
	$socid = $user->socid;
}
$result = restrictedArea($user, 'banque', $id, 'bank_account&bank_account', '', '', $fieldid);

$account = GETPOST("account");
$mode = 'standard';
if (GETPOST("mode") == 'showalltime') {
	$mode = 'showalltime';
}
$error = 0;


/*
 * View
 */
$form = new Form($db);

$datetime = dol_now();
$year = dol_print_date($datetime, "%Y");
$month = dol_print_date($datetime, "%m");
$day = dol_print_date($datetime, "%d");
if (GETPOSTINT("year")) {
	$year = sprintf("%04d", GETPOSTINT("year"));
}
if (GETPOSTINT("month")) {
	$month = sprintf("%02d", GETPOSTINT("month"));
}


$object = new Account($db);
if (GETPOST('account') && !preg_match('/,/', GETPOST('account'))) {	// if for a particular account and not a list
	$result = $object->fetch(GETPOSTINT('account'));
}
if (GETPOST("ref")) {
	$result = $object->fetch(0, GETPOST("ref"));
	$account = $object->id;
}

$title = $object->ref.' - '.$langs->trans("Graph");
$helpurl = "";
llxHeader('', $title, $helpurl);

$result = dol_mkdir($conf->bank->dir_temp);
if ($result < 0) {
	$langs->load("errors");
	$error++;
	setEventMessages($langs->trans("ErrorFailedToCreateDir"), null, 'errors');
} else {
	// Calcul $min and $max
	$sql = "SELECT MIN(b.datev) as min, MAX(b.datev) as max";
	$sql .= " FROM ".MAIN_DB_PREFIX."bank as b";
	$sql .= ", ".MAIN_DB_PREFIX."bank_account as ba";
	$sql .= " WHERE b.fk_account = ba.rowid";
	$sql .= " AND ba.entity IN (".getEntity('bank_account').")";
	if ($account && GETPOST("option") != 'all') {
		$sql .= " AND b.fk_account IN (".$db->sanitize($account).")";
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
	if (empty($min)) {
		$min = dol_now() - 3600 * 24;
	}

	$log = "graph.php: min=".$min." max=".$max;
	dol_syslog($log);


	// Tableau 1

	if ($mode == 'standard') {
		// Loading table $amounts
		$amounts = array();

		$monthnext = (int) $month + 1;
		$yearnext = (int) $year;
		if ($monthnext > 12) {
			$monthnext = 1;
			$yearnext++;
		}
		$monthnext = sprintf('%02d', $monthnext);
		$yearnext = sprintf('%04d', $yearnext);

		$sql = "SELECT date_format(b.datev,'%Y%m%d')";
		$sql .= ", SUM(b.amount)";
		$sql .= " FROM ".MAIN_DB_PREFIX."bank as b";
		$sql .= ", ".MAIN_DB_PREFIX."bank_account as ba";
		$sql .= " WHERE b.fk_account = ba.rowid";
		$sql .= " AND ba.entity IN (".getEntity('bank_account').")";
		$sql .= " AND b.datev >= '".$db->escape($year)."-".$db->escape($month)."-01 00:00:00'";
		$sql .= " AND b.datev < '".$db->escape($yearnext)."-".$db->escape($monthnext)."-01 00:00:00'";
		if ($account && GETPOST("option") != 'all') {
			$sql .= " AND b.fk_account IN (".$db->sanitize($account).")";
		}
		$sql .= " GROUP BY date_format(b.datev,'%Y%m%d')";

		$resql = $db->query($sql);
		if ($resql) {
			$num = $db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$row = $db->fetch_row($resql);
				$amounts[$row[0]] = $row[1];
				$i++;
			}
			$db->free($resql);
		} else {
			dol_print_error($db);
		}

		// Calculation of $solde before the start of the graph
		$solde = 0;

		$sql = "SELECT SUM(b.amount)";
		$sql .= " FROM ".MAIN_DB_PREFIX."bank as b";
		$sql .= ", ".MAIN_DB_PREFIX."bank_account as ba";
		$sql .= " WHERE b.fk_account = ba.rowid";
		$sql .= " AND ba.entity IN (".getEntity('bank_account').")";
		$sql .= " AND b.datev < '".$db->escape($year)."-".sprintf("%02s", $month)."-01'";
		if ($account && GETPOST("option") != 'all') {
			$sql .= " AND b.fk_account IN (".$db->sanitize($account).")";
		}

		$resql = $db->query($sql);
		if ($resql) {
			$row = $db->fetch_row($resql);
			$solde = $row[0];
			$db->free($resql);
		} else {
			dol_print_error($db);
		}

		// Chargement de labels et datas pour tableau 1
		$labels = array();
		$datas = array();
		$datamin = array();

		$subtotal = 0;
		$day = dol_mktime(12, 0, 0, $month, 1, $year);
		//$textdate = strftime("%Y%m%d", $day);
		$textdate = dol_print_date($day, "%Y%m%d");
		$xyear = substr($textdate, 0, 4);
		$xday = substr($textdate, 6, 2);
		$xmonth = substr($textdate, 4, 2);

		$i = 0;
		$dataall = array();
		while ($xmonth == $month) {
			$subtotal += (isset($amounts[$textdate]) ? $amounts[$textdate] : 0);
			if ($day > time()) {
				$datas[$i] = ''; // Valeur speciale permettant de ne pas tracer le graph
			} else {
				$datas[$i] = $solde + $subtotal;
			}
			$datamin[$i] = $object->min_desired;
			$dataall[$i] = $object->min_allowed;
			//$labels[$i] = strftime("%d",$day);
			$labels[$i] = $xday;

			$day += 86400;
			//$textdate = strftime("%Y%m%d", $day);
			$textdate = dol_print_date($day, "%Y%m%d");
			$xyear = substr($textdate, 0, 4);
			$xday = substr($textdate, 6, 2);
			$xmonth = substr($textdate, 4, 2);

			$i++;
		}
		// If we are the first of month, only $datas[0] is defined to an int value, others are defined to ""
		// and this may make graph lib report a warning.
		//$datas[0]=100; KO
		//$datas[0]=100; $datas[1]=90; OK
		//var_dump($datas);
		//exit;

		// Fabrication tableau 1
		$file = $conf->bank->dir_temp."/balance".$account."-".$year.$month.".png";
		$fileurl = DOL_URL_ROOT.'/viewimage.php?modulepart=banque_temp&file='."/balance".$account."-".$year.$month.".png";
		$title = $langs->transnoentities("Balance").' - '.$langs->transnoentities("Month").': '.$month.' '.$langs->transnoentities("Year").': '.$year;
		$graph_datas = array();
		foreach ($datas as $i => $val) {
			$graph_datas[$i] = array(isset($labels[$i]) ? $labels[$i] : '', $datas[$i]);
			if ($object->min_desired) {
				array_push($graph_datas[$i], $datamin[$i]);
			}
			if ($object->min_allowed) {
				array_push($graph_datas[$i], $dataall[$i]);
			}
		}

		$px1 = new DolGraph();
		$px1->SetData($graph_datas);
		$arraylegends = array($langs->transnoentities("Balance"));
		if ($object->min_desired) {
			array_push($arraylegends, $langs->transnoentities("BalanceMinimalDesired"));
		}
		if ($object->min_allowed) {
			array_push($arraylegends, $langs->transnoentities("BalanceMinimalAllowed"));
		}
		$px1->SetLegend($arraylegends);
		$px1->SetLegendWidthMin(180);
		$px1->SetMaxValue($px1->GetCeilMaxValue() < 0 ? 0 : $px1->GetCeilMaxValue());
		$px1->SetMinValue($px1->GetFloorMinValue() > 0 ? 0 : $px1->GetFloorMinValue());
		$px1->SetTitle($title);
		$px1->SetWidth($WIDTH);
		$px1->SetHeight($HEIGHT);
		$px1->SetType(array('lines', 'lines', 'lines'));
		$px1->setBgColor('onglet');
		$px1->setBgColorGrid(array(255, 255, 255));
		$px1->SetHorizTickIncrement(1);
		$px1->draw($file, $fileurl);

		$show1 = $px1->show();

		$px1 = null;
		$graph_datas = null;
		$datas = null;
		$datamin = array();
		$dataall = null;
		$labels = null;
		$amounts = null;
	}

	// Graph Balance for the year

	if ($mode == 'standard') {
		// Loading table $amounts
		$amounts = array();
		$sql = "SELECT date_format(b.datev,'%Y%m%d')";
		$sql .= ", SUM(b.amount)";
		$sql .= " FROM ".MAIN_DB_PREFIX."bank as b";
		$sql .= ", ".MAIN_DB_PREFIX."bank_account as ba";
		$sql .= " WHERE b.fk_account = ba.rowid";
		$sql .= " AND ba.entity IN (".getEntity('bank_account').")";
		$sql .= " AND b.datev >= '".$db->escape($year)."-01-01 00:00:00'";
		$sql .= " AND b.datev <= '".$db->escape($year)."-12-31 23:59:59'";
		if ($account && GETPOST("option") != 'all') {
			$sql .= " AND b.fk_account IN (".$db->sanitize($account).")";
		}
		$sql .= " GROUP BY date_format(b.datev,'%Y%m%d')";

		$resql = $db->query($sql);
		if ($resql) {
			$num = $db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$row = $db->fetch_row($resql);
				$amounts[$row[0]] = $row[1];
				$i++;
			}
			$db->free($resql);
		} else {
			dol_print_error($db);
		}

		// Calculation of $solde before the start of the graph
		$solde = 0;

		$sql = "SELECT SUM(b.amount)";
		$sql .= " FROM ".MAIN_DB_PREFIX."bank as b";
		$sql .= ", ".MAIN_DB_PREFIX."bank_account as ba";
		$sql .= " WHERE b.fk_account = ba.rowid";
		$sql .= " AND ba.entity IN (".getEntity('bank_account').")";
		$sql .= " AND b.datev < '".$db->escape($year)."-01-01'";
		if ($account && GETPOST("option") != 'all') {
			$sql .= " AND b.fk_account IN (".$db->sanitize($account).")";
		}

		$resql = $db->query($sql);
		if ($resql) {
			$row = $db->fetch_row($resql);
			$solde = $row[0];
			$db->free($resql);
		} else {
			dol_print_error($db);
		}

		// Chargement de labels et datas pour tableau 2
		$labels = array();
		$datas = array();
		$datamin = array();
		$dataall = array();

		$subtotal = 0;
		$now = time();
		$day = dol_mktime(12, 0, 0, 1, 1, $year);
		//$textdate = strftime("%Y%m%d", $day);
		$textdate = dol_print_date($day, "%Y%m%d");
		$xyear = substr($textdate, 0, 4);
		$xday = substr($textdate, 6, 2);

		$i = 0;
		while ($xyear == $year && $day <= $datetime) {
			$subtotal += (isset($amounts[$textdate]) ? $amounts[$textdate] : 0);
			if ($day > $now) {
				$datas[$i] = ''; // Valeur speciale permettant de ne pas tracer le graph
			} else {
				$datas[$i] = $solde + $subtotal;
			}
			$datamin[$i] = $object->min_desired;
			$dataall[$i] = $object->min_allowed;
			/*if ($xday == '15')	// Set only some label for jflot
			{
				$labels[$i] = dol_print_date($day, "%b");
			}*/
			$labels[$i] = dol_print_date($day, "%Y%m");
			$day += 86400;
			//$textdate = strftime("%Y%m%d", $day);
			$textdate = dol_print_date($day, "%Y%m%d");
			$xyear = substr($textdate, 0, 4);
			$xday = substr($textdate, 6, 2);
			$i++;
		}

		// Fabrication tableau 2
		$file = $conf->bank->dir_temp."/balance".$account."-".$year.".png";
		$fileurl = DOL_URL_ROOT.'/viewimage.php?modulepart=banque_temp&file='."/balance".$account."-".$year.".png";
		$title = $langs->transnoentities("Balance").' - '.$langs->transnoentities("Year").': '.$year;
		$graph_datas = array();
		foreach ($datas as $i => $val) {
			$graph_datas[$i] = array(isset($labels[$i]) ? $labels[$i] : '', $datas[$i]);
			if ($object->min_desired) {
				array_push($graph_datas[$i], $datamin[$i]);
			}
			if ($object->min_allowed) {
				array_push($graph_datas[$i], $dataall[$i]);
			}
		}
		$px2 = new DolGraph();
		$px2->SetData($graph_datas);
		$arraylegends = array($langs->transnoentities("Balance"));
		if ($object->min_desired) {
			array_push($arraylegends, $langs->transnoentities("BalanceMinimalDesired"));
		}
		if ($object->min_allowed) {
			array_push($arraylegends, $langs->transnoentities("BalanceMinimalAllowed"));
		}
		$px2->SetLegend($arraylegends);
		$px2->SetLegendWidthMin(180);
		$px2->SetMaxValue($px2->GetCeilMaxValue() < 0 ? 0 : $px2->GetCeilMaxValue());
		$px2->SetMinValue($px2->GetFloorMinValue() > 0 ? 0 : $px2->GetFloorMinValue());
		$px2->SetTitle($title);
		$px2->SetWidth($WIDTH);
		$px2->SetHeight($HEIGHT);
		$px2->SetType(array('linesnopoint', 'linesnopoint', 'linesnopoint'));
		$px2->setBgColor('onglet');
		$px2->setBgColorGrid(array(255, 255, 255));
		$px2->SetHideXGrid(true);
		//$px2->SetHorizTickIncrement(30.41);	// 30.41 jours/mois en moyenne
		$px2->draw($file, $fileurl);

		$show2 = $px2->show();

		$px2 = null;
		$graph_datas = null;
		$datas = null;
		$datamin = null;
		$dataall = null;
		$labels = null;
		$amounts = null;
	}

	// Graph 3 - Balance for all time line

	if ($mode == 'showalltime') {
		// Loading table $amounts
		$amounts = array();

		$sql = "SELECT date_format(b.datev,'%Y%m%d')";
		$sql .= ", SUM(b.amount)";
		$sql .= " FROM ".MAIN_DB_PREFIX."bank as b";
		$sql .= ", ".MAIN_DB_PREFIX."bank_account as ba";
		$sql .= " WHERE b.fk_account = ba.rowid";
		$sql .= " AND ba.entity IN (".getEntity('bank_account').")";
		if ($account && GETPOST("option") != 'all') {
			$sql .= " AND b.fk_account IN (".$db->sanitize($account).")";
		}
		$sql .= " GROUP BY date_format(b.datev,'%Y%m%d')";

		$resql = $db->query($sql);
		if ($resql) {
			$num = $db->num_rows($resql);
			$i = 0;

			while ($i < $num) {
				$row = $db->fetch_row($resql);
				$amounts[$row[0]] = $row[1];
				$i++;
			}
		} else {
			dol_print_error($db);
		}

		// Calcul de $solde avant le debut du graphe
		$solde = 0;

		// Chargement de labels et datas pour tableau 3
		$labels = array();
		$datas = array();
		$datamin = array();
		$dataall = array();

		$subtotal = 0;

		$day = $min;
		//$textdate = strftime("%Y%m%d", $day);
		$textdate = dol_print_date($day, "%Y%m%d");
		//print "x".$textdate;
		$i = 0;
		while ($day <= ($max + 86400)) {	// On va au dela du dernier jour
			$subtotal += (isset($amounts[$textdate]) ? $amounts[$textdate] : 0);
			//print strftime ("%e %d %m %y",$day)." ".$subtotal."\n<br>";
			if ($day > ($max + 86400)) {
				$datas[$i] = ''; // Valeur speciale permettant de ne pas tracer le graph
			} else {
				$datas[$i] = $solde + $subtotal;
			}
			$datamin[$i] = $object->min_desired;
			$dataall[$i] = $object->min_allowed;
			/*if (substr($textdate, 6, 2) == '01' || $i == 0)	// Set only few label for jflot
			{
				$labels[$i] = substr($textdate, 0, 6);
			}*/
			$labels[$i] = substr($textdate, 0, 6);

			$day += 86400;
			//$textdate = strftime("%Y%m%d", $day);
			$textdate = dol_print_date($day, "%Y%m%d");
			$i++;
		}

		// Fabrication tableau 3
		$file = $conf->bank->dir_temp."/balance".$account.".png";
		$fileurl = DOL_URL_ROOT.'/viewimage.php?modulepart=banque_temp&file='."/balance".$account.".png";
		$title = $langs->transnoentities("Balance")." - ".$langs->transnoentities("AllTime");
		$graph_datas = array();
		foreach ($datas as $i => $val) {
			$graph_datas[$i] = array(isset($labels[$i]) ? $labels[$i] : '', $datas[$i]);
			if ($object->min_desired) {
				array_push($graph_datas[$i], $datamin[$i]);
			}
			if ($object->min_allowed) {
				array_push($graph_datas[$i], $dataall[$i]);
			}
		}

		$px3 = new DolGraph();
		$px3->SetData($graph_datas);
		$arraylegends = array($langs->transnoentities("Balance"));
		if ($object->min_desired) {
			array_push($arraylegends, $langs->transnoentities("BalanceMinimalDesired"));
		}
		if ($object->min_allowed) {
			array_push($arraylegends, $langs->transnoentities("BalanceMinimalAllowed"));
		}
		$px3->SetLegend($arraylegends);
		$px3->SetLegendWidthMin(180);
		$px3->SetMaxValue($px3->GetCeilMaxValue() < 0 ? 0 : $px3->GetCeilMaxValue());
		$px3->SetMinValue($px3->GetFloorMinValue() > 0 ? 0 : $px3->GetFloorMinValue());
		$px3->SetTitle($title);
		$px3->SetWidth($WIDTH);
		$px3->SetHeight($HEIGHT);
		$px3->SetType(array('linesnopoint', 'linesnopoint', 'linesnopoint'));
		$px3->setBgColor('onglet');
		$px3->setBgColorGrid(array(255, 255, 255));
		$px3->draw($file, $fileurl);

		$show3 = $px3->show();

		$px3 = null;
		$graph_datas = null;
		$datas = null;
		$datamin = null;
		$dataall = null;
		$labels = null;
		$amounts = null;
	}

	// Tableau 4a - Credit/Debit

	if ($mode == 'standard') {
		// Chargement du tableau $credits, $debits
		$credits = array();
		$debits = array();

		$monthnext = (int) $month + 1;
		$yearnext = (int) $year;
		if ($monthnext > 12) {
			$monthnext = 1;
			$yearnext++;
		}
		$monthnext = sprintf('%02d', $monthnext);
		$yearnext = sprintf('%04d', $yearnext);

		$sql = "SELECT date_format(b.datev,'%d')";
		$sql .= ", SUM(b.amount)";
		$sql .= " FROM ".MAIN_DB_PREFIX."bank as b";
		$sql .= ", ".MAIN_DB_PREFIX."bank_account as ba";
		$sql .= " WHERE b.fk_account = ba.rowid";
		$sql .= " AND ba.entity IN (".getEntity('bank_account').")";
		$sql .= " AND b.datev >= '".$db->escape($year)."-".$db->escape($month)."-01 00:00:00'";
		$sql .= " AND b.datev < '".$db->escape($yearnext)."-".$db->escape($monthnext)."-01 00:00:00'";
		$sql .= " AND b.amount > 0";
		if ($account && GETPOST("option") != 'all') {
			$sql .= " AND b.fk_account IN (".$db->sanitize($account).")";
		}
		$sql .= " GROUP BY date_format(b.datev,'%d')";

		$resql = $db->query($sql);
		if ($resql) {
			$num = $db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$row = $db->fetch_row($resql);
				$credits[$row[0]] = $row[1];
				$i++;
			}
			$db->free($resql);
		} else {
			dol_print_error($db);
		}

		$monthnext = (int) $month + 1;
		$yearnext = (int) $year;
		if ($monthnext > 12) {
			$monthnext = 1;
			$yearnext++;
		}
		$monthnext = sprintf('%02d', $monthnext);
		$yearnext = sprintf('%04d', $yearnext);

		$sql = "SELECT date_format(b.datev,'%d')";
		$sql .= ", SUM(b.amount)";
		$sql .= " FROM ".MAIN_DB_PREFIX."bank as b";
		$sql .= ", ".MAIN_DB_PREFIX."bank_account as ba";
		$sql .= " WHERE b.fk_account = ba.rowid";
		$sql .= " AND ba.entity IN (".getEntity('bank_account').")";
		$sql .= " AND b.datev >= '".$db->escape($year)."-".$db->escape($month)."-01 00:00:00'";
		$sql .= " AND b.datev < '".$db->escape($yearnext)."-".$db->escape($monthnext)."-01 00:00:00'";
		$sql .= " AND b.amount < 0";
		if ($account && GETPOST("option") != 'all') {
			$sql .= " AND b.fk_account IN (".$db->sanitize($account).")";
		}
		$sql .= " GROUP BY date_format(b.datev,'%d')";

		$resql = $db->query($sql);
		if ($resql) {
			while ($row = $db->fetch_row($resql)) {
				$debits[$row[0]] = abs($row[1]);
			}
			$db->free($resql);
		} else {
			dol_print_error($db);
		}


		// Chargement de labels et data_xxx pour tableau 4 Movements
		$labels = array();
		$data_credit = array();
		$data_debit = array();
		for ($i = 0; $i < 31; $i++) {
			$data_credit[$i] = isset($credits[substr("0".($i + 1), -2)]) ? $credits[substr("0".($i + 1), -2)] : 0;
			$data_debit[$i] = isset($debits[substr("0".($i + 1), -2)]) ? $debits[substr("0".($i + 1), -2)] : 0;
			$labels[$i] = sprintf("%02d", $i + 1);
			$datamin[$i] = $object->min_desired;
		}

		// Fabrication tableau 4a
		$file = $conf->bank->dir_temp."/movement".$account."-".$year.$month.".png";
		$fileurl = DOL_URL_ROOT.'/viewimage.php?modulepart=banque_temp&file='."/movement".$account."-".$year.$month.".png";
		$title = $langs->transnoentities("BankMovements").' - '.$langs->transnoentities("Month").': '.$month.' '.$langs->transnoentities("Year").': '.$year;
		$graph_datas = array();
		foreach ($data_credit as $i => $val) {
			$graph_datas[$i] = array($labels[$i], $data_credit[$i], $data_debit[$i]);
		}
		$px4 = new DolGraph();
		$px4->SetData($graph_datas);
		$px4->SetLegend(array($langs->transnoentities("Credit"), $langs->transnoentities("Debit")));
		$px4->SetLegendWidthMin(180);
		$px4->SetMaxValue($px4->GetCeilMaxValue() < 0 ? 0 : $px4->GetCeilMaxValue());
		$px4->SetMinValue($px4->GetFloorMinValue() > 0 ? 0 : $px4->GetFloorMinValue());
		$px4->SetTitle($title);
		$px4->SetWidth($WIDTH);
		$px4->SetHeight($HEIGHT);
		$px4->SetType(array('bars', 'bars'));
		$px4->SetShading(3);
		$px4->setBgColor('onglet');
		$px4->setBgColorGrid(array(255, 255, 255));
		$px4->SetHorizTickIncrement(1);
		$px4->draw($file, $fileurl);

		$show4 = $px4->show();

		$px4 = null;
		$graph_datas = null;
		$debits = null;
		$credits = null;
	}

	// Tableau 4b - Credit/Debit

	if ($mode == 'standard') {
		// Chargement du tableau $credits, $debits
		$credits = array();
		$debits = array();
		$sql = "SELECT date_format(b.datev,'%m')";
		$sql .= ", SUM(b.amount)";
		$sql .= " FROM ".MAIN_DB_PREFIX."bank as b";
		$sql .= ", ".MAIN_DB_PREFIX."bank_account as ba";
		$sql .= " WHERE b.fk_account = ba.rowid";
		$sql .= " AND ba.entity IN (".getEntity('bank_account').")";
		$sql .= " AND b.datev >= '".$db->escape($year)."-01-01 00:00:00'";
		$sql .= " AND b.datev <= '".$db->escape($year)."-12-31 23:59:59'";
		$sql .= " AND b.amount > 0";
		if ($account && GETPOST("option") != 'all') {
			$sql .= " AND b.fk_account IN (".$db->sanitize($account).")";
		}
		$sql .= " GROUP BY date_format(b.datev,'%m');";

		$resql = $db->query($sql);
		if ($resql) {
			$num = $db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$row = $db->fetch_row($resql);
				$credits[$row[0]] = $row[1];
				$i++;
			}
			$db->free($resql);
		} else {
			dol_print_error($db);
		}
		$sql = "SELECT date_format(b.datev,'%m')";
		$sql .= ", SUM(b.amount)";
		$sql .= " FROM ".MAIN_DB_PREFIX."bank as b";
		$sql .= ", ".MAIN_DB_PREFIX."bank_account as ba";
		$sql .= " WHERE b.fk_account = ba.rowid";
		$sql .= " AND ba.entity IN (".getEntity('bank_account').")";
		$sql .= " AND b.datev >= '".$db->escape($year)."-01-01 00:00:00'";
		$sql .= " AND b.datev <= '".$db->escape($year)."-12-31 23:59:59'";
		$sql .= " AND b.amount < 0";
		if ($account && GETPOST("option") != 'all') {
			$sql .= " AND b.fk_account IN (".$db->sanitize($account).")";
		}
		$sql .= " GROUP BY date_format(b.datev,'%m')";

		$resql = $db->query($sql);
		if ($resql) {
			while ($row = $db->fetch_row($resql)) {
				$debits[$row[0]] = abs($row[1]);
			}
			$db->free($resql);
		} else {
			dol_print_error($db);
		}


		// Chargement de labels et data_xxx pour tableau 4 Movements
		$labels = array();
		$data_credit = array();
		$data_debit = array();
		for ($i = 0; $i < 12; $i++) {
			$data_credit[$i] = isset($credits[substr("0".($i + 1), -2)]) ? $credits[substr("0".($i + 1), -2)] : 0;
			$data_debit[$i] = isset($debits[substr("0".($i + 1), -2)]) ? $debits[substr("0".($i + 1), -2)] : 0;
			$labels[$i] = dol_print_date(dol_mktime(12, 0, 0, $i + 1, 1, 2000), "%b");
			$datamin[$i] = $object->min_desired;
		}

		// Fabrication tableau 4b
		$file = $conf->bank->dir_temp."/movement".$account."-".$year.".png";
		$fileurl = DOL_URL_ROOT.'/viewimage.php?modulepart=banque_temp&file='."/movement".$account."-".$year.".png";
		$title = $langs->transnoentities("BankMovements").' - '.$langs->transnoentities("Year").': '.$year;
		$graph_datas = array();
		foreach ($data_credit as $i => $val) {
			$graph_datas[$i] = array($labels[$i], $data_credit[$i], $data_debit[$i]);
		}
		$px5 = new DolGraph();
		$px5->SetData($graph_datas);
		$px5->SetLegend(array($langs->transnoentities("Credit"), $langs->transnoentities("Debit")));
		$px5->SetLegendWidthMin(180);
		$px5->SetMaxValue($px5->GetCeilMaxValue() < 0 ? 0 : $px5->GetCeilMaxValue());
		$px5->SetMinValue($px5->GetFloorMinValue() > 0 ? 0 : $px5->GetFloorMinValue());
		$px5->SetTitle($title);
		$px5->SetWidth($WIDTH);
		$px5->SetHeight($HEIGHT);
		$px5->SetType(array('bars', 'bars'));
		$px5->SetShading(3);
		$px5->setBgColor('onglet');
		$px5->setBgColorGrid(array(255, 255, 255));
		$px5->SetHorizTickIncrement(1);
		$px5->draw($file, $fileurl);

		$show5 = $px5->show();

		$px5 = null;
		$graph_datas = null;
		$debits = null;
		$credits = null;
	}
}


// Onglets
$head = bank_prepare_head($object);
print dol_get_fiche_head($head, 'graph', $langs->trans("FinancialAccount"), 0, 'account');


$linkback = '<a href="'.DOL_URL_ROOT.'/compta/bank/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

if ($account) {
	if (!preg_match('/,/', $account)) {
		$moreparam = '&month='.$month.'&year='.$year.($mode == 'showalltime' ? '&mode=showalltime' : '');

		if (GETPOST("option") != 'all') {
			$morehtml = '<a href="'.$_SERVER["PHP_SELF"].'?account='.$account.'&option=all'.$moreparam.'">'.$langs->trans("ShowAllAccounts").'</a>';
			dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', '', $moreparam, 0, '', '', 1);
		} else {
			$morehtml = '<a href="'.$_SERVER["PHP_SELF"].'?account='.$account.$moreparam.'">'.$langs->trans("BackToAccount").'</a>';
			print $langs->trans("AllAccounts");
			//print $morehtml;
		}
	} else {
		$bankaccount = new Account($db);
		$listid = explode(',', $account);
		foreach ($listid as $key => $id) {
			$bankaccount->fetch($id);
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


print '<table class="notopnoleftnoright" width="100%">';

// Navigation links
print '<tr><td class="right">'.$morehtml.' &nbsp; &nbsp; ';
if ($mode == 'showalltime') {
	print '<a href="'.$_SERVER["PHP_SELF"].'?account='.$account.(GETPOST("option") != 'all' ? '' : '&option=all').'">';
	print $langs->trans("GoBack");
	print '</a>';
} else {
	print '<a href="'.$_SERVER["PHP_SELF"].'?mode=showalltime&account='.$account.(GETPOST("option") != 'all' ? '' : '&option=all').'">';
	print $langs->trans("ShowAllTimeBalance");
	print '</a>';
}
print '<br><br></td></tr>';

print '</table>';


// Graphs
if ($mode == 'standard') {
	$prevyear = (int) $year;
	$nextyear = (int) $year;
	$prevmonth = (int) $month - 1;
	$nextmonth = (int) $month + 1;
	if ($prevmonth < 1) {
		$prevmonth = 12;
		$prevyear--;
	}
	if ($nextmonth > 12) {
		$nextmonth = 1;
		$nextyear++;
	}
	$nextmonth = sprintf('%02d', $nextmonth);
	$prevmonth = sprintf('%02d', $prevmonth);
	$nextyear = sprintf('%04d', $nextyear);
	$prevyear = sprintf('%04d', $prevyear);

	// For month
	$link = "<a href='".$_SERVER["PHP_SELF"]."?account=".$account.(GETPOST("option") != 'all' ? '' : '&option=all')."&year=".$prevyear."&month=".$prevmonth."'>".img_previous('', 'class="valignbottom"')."</a> ".$langs->trans("Month")." <a href='".$_SERVER["PHP_SELF"]."?account=".$account.(GETPOST("option") != 'all' ? '' : '&option=all')."&year=".$nextyear."&month=".$nextmonth."'>".img_next('', 'class="valignbottom"')."</a>";
	print '<div class="right clearboth">'.$link.'</div>';

	print '<div class="center clearboth margintoponly">';
	$file = "movement".$account."-".$year.$month.".png";
	print $show4;
	print '</div>';

	print '<div class="center clearboth margintoponly">';
	print $show1;
	print '</div>';

	// For year
	$prevyear = (int) $year - 1;
	$nextyear = (int) $year + 1;
	$nextyear = sprintf('%04d', $nextyear);
	$prevyear = sprintf('%04d', $prevyear);
	$link = "<a href='".$_SERVER["PHP_SELF"]."?account=".$account.(GETPOST("option") != 'all' ? '' : '&option=all')."&year=".($prevyear)."'>".img_previous('', 'class="valignbottom"')."</a> ".$langs->trans("Year")." <a href='".$_SERVER["PHP_SELF"]."?account=".$account.(GETPOST("option") != 'all' ? '' : '&option=all')."&year=".($nextyear)."'>".img_next('', 'class="valignbottom"')."</a>";

	print '<div class="right clearboth margintoponly">'.$link.'</div>';

	print '<div class="center clearboth margintoponly">';
	print $show5;
	print '</div>';

	print '<div class="center clearboth margintoponly">';
	print $show2;
	print '</div>';
}

if ($mode == 'showalltime') {
	print '<div class="center clearboth margintoponly">';
	print $show3;
	print '</div>';
}


// End of page
llxFooter();
$db->close();
