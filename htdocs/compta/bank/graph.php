<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 *	\file       htdocs/compta/bank/graph.php
 *	\ingroup    banque
 *	\brief      Page graph des transactions bancaires
 */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/bank.lib.php");
require_once(DOL_DOCUMENT_ROOT."/compta/bank/class/account.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/dolgraph.class.php");

$langs->load("banks");

// Security check
if (isset($_GET["account"]) || isset($_GET["ref"]))
{
	$id = isset($_GET["account"])?$_GET["account"]:(isset($_GET["ref"])?$_GET["ref"]:'');
}
$fieldid = isset($_GET["ref"])?'ref':'rowid';
if ($user->societe_id) $socid=$user->societe_id;
$result=restrictedArea($user,'banque',$id,'bank_account','','',$fieldid);

$account=$_GET["account"];
$mode='standard';
if (isset($_GET["mode"]) && $_GET["mode"] == 'showalltime') $mode='showalltime';
$mesg = '';
$error=0;


/*
 * View
 */

llxHeader();

$form = new Form($db);

$datetime = dol_now();
$year = dol_print_date($datetime, "%Y");
$month = dol_print_date($datetime, "%m");
$day = dol_print_date($datetime, "%d");
if (! empty($_GET["year"]))  $year=sprintf("%04d",$_GET["year"]);
if (! empty($_GET["month"])) $month=sprintf("%02d",$_GET["month"]);


$acct = new Account($db);
if ($_GET["account"] && ! preg_match('/,/',$_GET["account"]))	// if for a particular account and not a list
{
	$result=$acct->fetch($_GET["account"]);
}
if ($_GET["ref"])
{
	$result=$acct->fetch(0,$_GET["ref"]);
	$account=$acct->id;
}

$result=dol_mkdir($conf->banque->dir_temp);
if ($result < 0)
{
	$langs->load("errors");
	$error++;
	$mesg='<div class="error">'.$langs->trans("ErrorFailedToCreateDir").'</div>';
}
else
{
	// Definition de $width et $height
	$width = 768;
	$height = 200;

	// Calcul de $min et $max
	$sql = "SELECT MIN(b.datev) as min, MAX(b.datev) as max";
	$sql.= " FROM ".MAIN_DB_PREFIX."bank as b";
	$sql.= ", ".MAIN_DB_PREFIX."bank_account as ba";
	$sql.= " WHERE b.fk_account = ba.rowid";
	$sql.= " AND ba.entity = ".$conf->entity;
	if ($account && $_GET["option"]!='all') $sql.= " AND b.fk_account IN (".$account.")";

	$resql = $db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);
		$obj = $db->fetch_object($resql);
		$min = $db->jdate($obj->min);
		$max = $db->jdate($obj->max);
	}
	else
	{
		dol_print_error($db);
	}
	$log="graph.php: min=".$min." max=".$max;
	dol_syslog($log);


	// Tableau 1

	if ($mode == 'standard')
	{
		// Chargement du tableau $amounts
		$amounts = array();

		$monthnext = $month+1;
		$yearnext = $year;
		if ($monthnext > 12)
		{
			$monthnext=1;
			$yearnext++;
		}

		$sql = "SELECT date_format(b.datev,'%Y%m%d')";
		$sql.= ", SUM(b.amount)";
		$sql.= " FROM ".MAIN_DB_PREFIX."bank as b";
		$sql.= ", ".MAIN_DB_PREFIX."bank_account as ba";
		$sql.= " WHERE b.fk_account = ba.rowid";
		$sql.= " AND ba.entity = ".$conf->entity;
		$sql.= " AND b.datev >= '".$year."-".$month."-01 00:00:00'";
		$sql.= " AND b.datev < '".$yearnext."-".$monthnext."-01 00:00:00'";
		if ($account && $_GET["option"]!='all') $sql.= " AND b.fk_account IN (".$account.")";
		$sql.= " GROUP BY date_format(b.datev,'%Y%m%d')";

		$resql = $db->query($sql);
		if ($resql)
		{
			$num = $db->num_rows($resql);
			$i = 0;
			while ($i < $num)
			{
				$row = $db->fetch_row($resql);
				$amounts[$row[0]] = $row[1];
				$i++;
			}
			$db->free($resql);
		}
		else
		{
			dol_print_error($db);
		}

		// Calcul de $solde avant le debut du graphe
		$solde = 0;

		$sql = "SELECT SUM(b.amount)";
		$sql.= " FROM ".MAIN_DB_PREFIX."bank as b";
		$sql.= ", ".MAIN_DB_PREFIX."bank_account as ba";
		$sql.= " WHERE b.fk_account = ba.rowid";
		$sql.= " AND ba.entity = ".$conf->entity;
		$sql.= " AND b.datev < '".$year."-".sprintf("%02s",$month)."-01'";
		if ($account && $_GET["option"]!='all') $sql.= " AND b.fk_account IN (".$account.")";

		$resql = $db->query($sql);
		if ($resql)
		{
			$row = $db->fetch_row($resql);
			$solde = $row[0];
			$db->free($resql);
		}
		else
		{
			dol_print_error($db);
		}

		// Chargement de labels et datas pour tableau 1
		$labels = array();
		$datas = array();
		$datamin = array();

		$subtotal = 0;
		$day = dol_mktime(12,0,0,$month,1,$year);
		$textdate = strftime("%Y%m%d",$day);
		$xyear = substr($textdate,0,4);
		$xday = substr($textdate,6,2);
		$xmonth = substr($textdate,4,2);

		$i = 0;
		while ($xmonth == $month)
		{
			$subtotal = $subtotal + (isset($amounts[$textdate]) ? $amounts[$textdate] : 0);
			if ($day > time())
			{
				$datas[$i] = ''; // Valeur speciale permettant de ne pas tracer le graph
			}
			else
			{
				$datas[$i] = $solde + $subtotal;
			}
			$datamin[$i] = $acct->min_desired;
			//$labels[$i] = strftime("%d",$day);
			$labels[$i] = $xday;

			$day += 86400;
			$textdate = strftime("%Y%m%d",$day);
			$xyear = substr($textdate,0,4);
			$xday = substr($textdate,6,2);
			$xmonth = substr($textdate,4,2);

			$i++;
		}
		// If we are the first of month, only $datas[0] is defined to an int value, others are defined to ""
		// and this may make graph lib report a warning.
		//$datas[0]=100; KO
		//$datas[0]=100; $datas[1]=90; OK
		//var_dump($datas);
		//exit;

		// Fabrication tableau 1
		$file= $conf->banque->dir_temp."/balance".$account."-".$year.$month.".png";
		$fileurl=DOL_URL_ROOT.'/viewimage.php?modulepart=banque_temp&file='."/balance".$account."-".$year.$month.".png";
		$title=$langs->transnoentities("Balance").' - '.$langs->transnoentities("Month").': '.$month.' '.$langs->transnoentities("Year").': '.$year;
		$graph_datas=array();
		foreach($datas as $i => $val)
		{
			if ($acct->min_desired) $graph_datas[$i]=array(isset($labels[$i])?$labels[$i]:'',$datas[$i],$datamin[$i]);
			else $graph_datas[$i]=array(isset($labels[$i])?$labels[$i]:'',$datas[$i]);
		}

		$px1 = new DolGraph();
		$px1->SetData($graph_datas);
		if ($acct->min_desired) $px1->SetLegend(array($langs->transnoentities("Balance"),$langs->transnoentities("BalanceMinimalDesired")));
		else $px1->SetLegend(array($langs->transnoentities("Balance")));
		$px1->SetLegendWidthMin(180);
		$px1->SetMaxValue($px1->GetCeilMaxValue()<0?0:$px1->GetCeilMaxValue());
		$px1->SetMinValue($px1->GetFloorMinValue()>0?0:$px1->GetFloorMinValue());
		$px1->SetTitle($title);
		$px1->SetWidth($width);
		$px1->SetHeight($height);
		$px1->SetType(array('lines','lines'));
		$px1->setBgColor('onglet');
		$px1->setBgColorGrid(array(255,255,255));
		$px1->SetHorizTickIncrement(1);
		$px1->SetPrecisionY(0);
		$px1->draw($file,$fileurl);

		$show1=$px1->show();
		unset($graph_datas);
		unset($px1);
		unset($datas);
		unset($datamin);
		unset($labels);
		unset($amounts);
	}

	// Tableau 2

	if ($mode == 'standard')
	{
		// Chargement du tableau $amounts
		$amounts = array();
		$sql = "SELECT date_format(b.datev,'%Y%m%d')";
		$sql.= ", SUM(b.amount)";
		$sql.= " FROM ".MAIN_DB_PREFIX."bank as b";
		$sql.= ", ".MAIN_DB_PREFIX."bank_account as ba";
		$sql.= " WHERE b.fk_account = ba.rowid";
		$sql.= " AND ba.entity = ".$conf->entity;
		$sql.= " AND b.datev >= '".$year."-01-01 00:00:00'";
		$sql.= " AND b.datev <= '".$year."-12-31 23:59:59'";
		if ($account && $_GET["option"]!='all') $sql.= " AND b.fk_account IN (".$account.")";
		$sql .= " GROUP BY date_format(b.datev,'%Y%m%d')";

		$resql = $db->query($sql);
		if ($resql)
		{
			$num = $db->num_rows($resql);
			$i = 0;
			while ($i < $num)
			{
				$row = $db->fetch_row($resql);
				$amounts[$row[0]] = $row[1];
				$i++;
			}
			$db->free($resql);
		}
		else
		{
			dol_print_error($db);
		}

		// Calcul de $solde avant le debut du graphe
		$solde = 0;

		$sql = "SELECT SUM(b.amount)";
		$sql.= " FROM ".MAIN_DB_PREFIX."bank as b";
		$sql.= ", ".MAIN_DB_PREFIX."bank_account as ba";
		$sql.= " WHERE b.fk_account = ba.rowid";
		$sql.= " AND ba.entity = ".$conf->entity;
		$sql.= " AND b.datev < '".$year."-01-01'";
		if ($account && $_GET["option"]!='all') $sql.= " AND b.fk_account IN (".$account.")";

		$resql = $db->query($sql);
		if ($resql)
		{
			$row = $db->fetch_row($resql);
			$solde = $row[0];
			$db->free($resql);
		}
		else
		{
			dol_print_error($db);
		}

		// Chargement de labels et datas pour tableau 2
		$labels = array();
		$datas = array();
		$datamin = array();

		$subtotal = 0;
		$now = time();
		$day = dol_mktime(12,0,0,1,1,$year);
		$textdate = strftime("%Y%m%d",$day);
		$xyear = substr($textdate,0,4);
		$xday = substr($textdate,6,2);

		$i = 0;
		while ($xyear == $year && $day <= $datetime)
		{
			$subtotal = $subtotal + (isset($amounts[$textdate]) ? $amounts[$textdate] : 0);
			if ($day > $now)
			{
				$datas[$i] = ''; // Valeur speciale permettant de ne pas tracer le graph
			}
			else
			{
				$datas[$i] = $solde + $subtotal;
			}
			$datamin[$i] = $acct->min_desired;
			if ($xday == '15')
			{
				$labels[$i] = dol_print_date($day,"%b");
			}
			$day += 86400;
			$textdate = strftime("%Y%m%d",$day);
			$xyear = substr($textdate,0,4);
			$xday = substr($textdate,6,2);
			$i++;
		}

		// Fabrication tableau 2
		$file= $conf->banque->dir_temp."/balance".$account."-".$year.".png";
		$fileurl=DOL_URL_ROOT.'/viewimage.php?modulepart=banque_temp&file='."/balance".$account."-".$year.".png";
		$title=$langs->transnoentities("Balance").' - '.$langs->transnoentities("Year").': '.$year;
		$graph_datas=array();
		foreach($datas as $i => $val)
		{
			if ($acct->min_desired) $graph_datas[$i]=array(isset($labels[$i])?$labels[$i]:'',$datas[$i],$datamin[$i]);
			else $graph_datas[$i]=array(isset($labels[$i])?$labels[$i]:'',$datas[$i]);
		}
		$px2 = new DolGraph();
		$px2->SetData($graph_datas);
		if ($acct->min_desired) $px2->SetLegend(array($langs->transnoentities("Balance"),$langs->transnoentities("BalanceMinimalDesired")));
		else $px2->SetLegend(array($langs->transnoentities("Balance")));
		$px2->SetLegendWidthMin(180);
		$px2->SetMaxValue($px2->GetCeilMaxValue()<0?0:$px2->GetCeilMaxValue());
		$px2->SetMinValue($px2->GetFloorMinValue()>0?0:$px2->GetFloorMinValue());
		$px2->SetTitle($title);
		$px2->SetWidth($width);
		$px2->SetHeight($height);
		$px2->SetType(array('lines','lines'));
		$px2->setBgColor('onglet');
		$px2->setBgColorGrid(array(255,255,255));
		$px2->SetHideXGrid(true);
		//$px2->SetHorizTickIncrement(30.41);	// 30.41 jours/mois en moyenne
		$px2->SetPrecisionY(0);
		$px2->draw($file,$fileurl);

		$show2=$px2->show();

		unset($px2);
		unset($graph_datas);
		unset($datas);
		unset($datamin);
		unset($labels);
		unset($amounts);
	}

	// Tableau 3 - All time line

	if ($mode == 'showalltime')
	{
		// Chargement du tableau $amounts
		$amounts = array();

		$sql = "SELECT date_format(b.datev,'%Y%m%d')";
		$sql.= ", SUM(b.amount)";
		$sql.= " FROM ".MAIN_DB_PREFIX."bank as b";
		$sql.= ", ".MAIN_DB_PREFIX."bank_account as ba";
		$sql.= " WHERE b.fk_account = ba.rowid";
		$sql.= " AND ba.entity = ".$conf->entity;
		if ($account && $_GET["option"]!='all') $sql.= " AND b.fk_account IN (".$account.")";
		$sql.= " GROUP BY date_format(b.datev,'%Y%m%d')";

		$resql = $db->query($sql);
		if ($resql)
		{
			$num = $db->num_rows($resql);
			$i = 0;

			while ($i < $num)
			{
				$row = $db->fetch_row($resql);
				$amounts[$row[0]] = $row[1];
				$i++;
			}
		}
		else
		{
			dol_print_error($db);
		}

		// Calcul de $solde avant le debut du graphe
		$solde = 0;

		// Chargement de labels et datas pour tableau 3
		$labels = array();
		$datas = array();
		$datamin = array();

		$subtotal = 0;

		$day = $min;
		$textdate=strftime("%Y%m%d",$day);
		//print "x".$textdate;
		$i = 0;
		while ($day <= ($max+86400))	// On va au dela du dernier jour
		{
			$subtotal = $subtotal + (isset($amounts[$textdate]) ? $amounts[$textdate] : 0);
			//print strftime ("%e %d %m %y",$day)." ".$subtotal."\n<br>";
			if ($day > ($max+86400))
			{
				$datas[$i] = ''; // Valeur speciale permettant de ne pas tracer le graph
			}
			else
			{
				$datas[$i] = '' + $solde + $subtotal;
			}
			$datamin[$i] = $acct->min_desired;
			if (substr($textdate,6,2) == '01' || $i == 0)
			{
				$labels[$i] = substr($textdate,4,2);
			}

			$day += 86400;
			$textdate=strftime("%Y%m%d",$day);
			$i++;
		}

		// Fabrication tableau 3
		$file= $conf->banque->dir_temp."/balance".$account.".png";
		$fileurl=DOL_URL_ROOT.'/viewimage.php?modulepart=banque_temp&file='."/balance".$account.".png";
		$title=$langs->transnoentities("Balance")." - ".$langs->transnoentities("AllTime");
		$graph_datas=array();
		foreach($datas as $i => $val)
		{
			if ($acct->min_desired) $graph_datas[$i]=array(isset($labels[$i])?$labels[$i]:'',$datas[$i],$datamin[$i]);
			else $graph_datas[$i]=array(isset($labels[$i])?$labels[$i]:'',$datas[$i]);
		}
		$px3 = new DolGraph();
		$px3->SetData($graph_datas);
		if ($acct->min_desired) $px3->SetLegend(array($langs->transnoentities("Balance"),$langs->transnoentities("BalanceMinimalDesired")));
		else $px3->SetLegend(array($langs->transnoentities("Balance")));
		$px3->SetLegendWidthMin(180);
		$px3->SetMaxValue($px3->GetCeilMaxValue()<0?0:$px3->GetCeilMaxValue());
		$px3->SetMinValue($px3->GetFloorMinValue()>0?0:$px3->GetFloorMinValue());
		$px3->SetTitle($title);
		$px3->SetWidth($width);
		$px3->SetHeight($height);
		$px3->SetType(array('lines','lines'));
		$px3->setBgColor('onglet');
		$px3->setBgColorGrid(array(255,255,255));
		$px3->SetPrecisionY(0);
		$px3->draw($file,$fileurl);

		$show3=$px3->show();

		unset($px3);
		unset($graph_datas);
		unset($datas);
		unset($datamin);
		unset($labels);
		unset($amounts);
	}

	// Tableau 4a - Credit/Debit

	if ($mode == 'standard')
	{
		// Chargement du tableau $credits, $debits
		$credits = array();
		$debits = array();

		$monthnext = $month+1;
		$yearnext = $year;
		if ($monthnext > 12)
		{
			$monthnext=1;
			$yearnext++;
		}

		$sql = "SELECT date_format(b.datev,'%d')";
		$sql.= ", SUM(b.amount)";
		$sql .= " FROM ".MAIN_DB_PREFIX."bank as b";
		$sql.= ", ".MAIN_DB_PREFIX."bank_account as ba";
		$sql.= " WHERE b.fk_account = ba.rowid";
		$sql.= " AND ba.entity = ".$conf->entity;
		$sql.= " AND b.datev >= '".$year."-".$month."-01 00:00:00'";
		$sql.= " AND b.datev < '".$yearnext."-".$monthnext."-01 00:00:00'";
		$sql.= " AND b.amount > 0";
		if ($account && $_GET["option"]!='all') $sql.= " AND b.fk_account IN (".$account.")";
		$sql.= " GROUP BY date_format(b.datev,'%d')";

		$resql = $db->query($sql);
		if ($resql)
		{
			$num = $db->num_rows($resql);
			$i = 0;
			while ($i < $num)
			{
				$row = $db->fetch_row($resql);
				$credits[$row[0]] = $row[1];
				$i++;
			}
			$db->free($resql);
		}
		else
		{
			dol_print_error($db);
		}

		$monthnext = $month+1;
		$yearnext = $year;
		if ($monthnext > 12)
		{
			$monthnext=1;
			$yearnext++;
		}

		$sql = "SELECT date_format(b.datev,'%d')";
		$sql.= ", SUM(b.amount)";
		$sql .= " FROM ".MAIN_DB_PREFIX."bank as b";
		$sql.= ", ".MAIN_DB_PREFIX."bank_account as ba";
		$sql.= " WHERE b.fk_account = ba.rowid";
		$sql.= " AND ba.entity = ".$conf->entity;
		$sql.= " AND b.datev >= '".$year."-".$month."-01 00:00:00'";
		$sql.= " AND b.datev < '".$yearnext."-".$monthnext."-01 00:00:00'";
		$sql.= " AND b.amount < 0";
		if ($account && $_GET["option"]!='all') $sql.= " AND b.fk_account IN (".$account.")";
		$sql .= " GROUP BY date_format(b.datev,'%d')";

		$resql = $db->query($sql);
		if ($resql)
		{
			while ($row = $db->fetch_row($resql))
			{
				$debits[$row[0]] = abs($row[1]);
			}
			$db->free($resql);
		}
		else
		{
			dol_print_error($db);
		}


		// Chargement de labels et data_xxx pour tableau 4 Mouvements
		$labels = array();
		$data_credit = array();
		$data_debit = array();
		for ($i = 0 ; $i < 31 ; $i++)
		{
			$data_credit[$i] = isset($credits[substr("0".($i+1),-2)]) ? $credits[substr("0".($i+1),-2)] : 0;
			$data_debit[$i] = isset($debits[substr("0".($i+1),-2)]) ? $debits[substr("0".($i+1),-2)] : 0;
			$labels[$i] = sprintf("%02d",$i+1);
			$datamin[$i] = $acct->min_desired;
		}

		// Fabrication tableau 4a
		$file= $conf->banque->dir_temp."/movement".$account."-".$year.$month.".png";
		$fileurl=DOL_URL_ROOT.'/viewimage.php?modulepart=banque_temp&file='."/movement".$account."-".$year.$month.".png";
		$title=$langs->transnoentities("BankMovements").' - '.$langs->transnoentities("Month").': '.$month.' '.$langs->transnoentities("Year").': '.$year;
		$graph_datas=array();
		foreach($data_credit as $i => $val)
		{
			$graph_datas[$i]=array($labels[$i],$data_credit[$i],$data_debit[$i]);
		}
		$px4 = new DolGraph();
		$px4->SetData($graph_datas);
		$px4->SetLegend(array($langs->transnoentities("Credit"),$langs->transnoentities("Debit")));
		$px4->SetLegendWidthMin(180);
		$px4->SetMaxValue($px4->GetCeilMaxValue()<0?0:$px4->GetCeilMaxValue());
		$px4->SetMinValue($px4->GetFloorMinValue()>0?0:$px4->GetFloorMinValue());
		$px4->SetTitle($title);
		$px4->SetWidth($width);
		$px4->SetHeight($height);
		$px4->SetType(array('bars','bars'));
		$px4->SetShading(3);
		$px4->setBgColor('onglet');
		$px4->setBgColorGrid(array(255,255,255));
		$px4->SetHorizTickIncrement(1);
		$px4->SetPrecisionY(0);
		$px4->draw($file,$fileurl);

		$show4=$px4->show();

		unset($graph_datas);
		unset($px4);
		unset($debits);
		unset($credits);
	}

	// Tableau 4b - Credit/Debit

	if ($mode == 'standard')
	{
		// Chargement du tableau $credits, $debits
		$credits = array();
		$debits = array();
		$sql = "SELECT date_format(b.datev,'%m')";
		$sql.= ", SUM(b.amount)";
		$sql.= " FROM ".MAIN_DB_PREFIX."bank as b";
		$sql.= ", ".MAIN_DB_PREFIX."bank_account as ba";
		$sql.= " WHERE b.fk_account = ba.rowid";
		$sql.= " AND ba.entity = ".$conf->entity;
		$sql.= " AND b.datev >= '".$year."-01-01 00:00:00'";
		$sql.= " AND b.datev <= '".$year."-12-31 23:59:59'";
		$sql.= " AND b.amount > 0";
		if ($account && $_GET["option"]!='all') $sql.= " AND b.fk_account IN (".$account.")";
		$sql .= " GROUP BY date_format(b.datev,'%m');";

		$resql = $db->query($sql);
		if ($resql)
		{
			$num = $db->num_rows($resql);
			$i = 0;
			while ($i < $num)
			{
				$row = $db->fetch_row($resql);
				$credits[$row[0]] = $row[1];
				$i++;
			}
			$db->free($resql);
		}
		else
		{
			dol_print_error($db);
		}
		$sql = "SELECT date_format(b.datev,'%m')";
		$sql.= ", SUM(b.amount)";
		$sql.= " FROM ".MAIN_DB_PREFIX."bank as b";
		$sql.= ", ".MAIN_DB_PREFIX."bank_account as ba";
		$sql.= " WHERE b.fk_account = ba.rowid";
		$sql.= " AND ba.entity = ".$conf->entity;
		$sql.= " AND b.datev >= '".$year."-01-01 00:00:00'";
		$sql.= " AND b.datev <= '".$year."-12-31 23:59:59'";
		$sql.= " AND b.amount < 0";
		if ($account && $_GET["option"]!='all') $sql.= " AND b.fk_account IN (".$account.")";
		$sql .= " GROUP BY date_format(b.datev,'%m')";

		$resql = $db->query($sql);
		if ($resql)
		{
			while ($row = $db->fetch_row($resql))
			{
				$debits[$row[0]] = abs($row[1]);
			}
			$db->free($resql);
		}
		else
		{
			dol_print_error($db);
		}


		// Chargement de labels et data_xxx pour tableau 4 Mouvements
		$labels = array();
		$data_credit = array();
		$data_debit = array();
		for ($i = 0 ; $i < 12 ; $i++)
		{
			$data_credit[$i] = isset($credits[substr("0".($i+1),-2)]) ? $credits[substr("0".($i+1),-2)] : 0;
			$data_debit[$i] = isset($debits[substr("0".($i+1),-2)]) ? $debits[substr("0".($i+1),-2)] : 0;
			$labels[$i] = dol_print_date(dol_mktime(12,0,0,$i+1,1,2000),"%b");
			$datamin[$i] = $acct->min_desired;
		}

		// Fabrication tableau 4b
		$file= $conf->banque->dir_temp."/movement".$account."-".$year.".png";
		$fileurl=DOL_URL_ROOT.'/viewimage.php?modulepart=banque_temp&file='."/movement".$account."-".$year.".png";
		$title=$langs->transnoentities("BankMovements").' - '.$langs->transnoentities("Year").': '.$year;
		$graph_datas=array();
		foreach($data_credit as $i => $val)
		{
			$graph_datas[$i]=array($labels[$i],$data_credit[$i],$data_debit[$i]);
		}
		$px5 = new DolGraph();
		$px5->SetData($graph_datas);
		$px5->SetLegend(array($langs->transnoentities("Credit"),$langs->transnoentities("Debit")));
		$px5->SetLegendWidthMin(180);
		$px5->SetMaxValue($px5->GetCeilMaxValue()<0?0:$px5->GetCeilMaxValue());
		$px5->SetMinValue($px5->GetFloorMinValue()>0?0:$px5->GetFloorMinValue());
		$px5->SetTitle($title);
		$px5->SetWidth($width);
		$px5->SetHeight($height);
		$px5->SetType(array('bars','bars'));
		$px5->SetShading(3);
		$px5->setBgColor('onglet');
		$px5->setBgColorGrid(array(255,255,255));
		$px5->SetHorizTickIncrement(1);
		$px5->SetPrecisionY(0);
		$px5->draw($file,$fileurl);

		$show5=$px5->show();

		unset($graph_datas);
		unset($px5);
		unset($debits);
		unset($credits);
	}
}


// Onglets
$head=bank_prepare_head($acct);
dol_fiche_head($head,'graph',$langs->trans("FinancialAccount"),0,'account');

if ($mesg) print $mesg.'<br>';

print '<table class="border" width="100%">';

// Ref
print '<tr><td valign="top" width="25%">'.$langs->trans("Ref").'</td>';
print '<td colspan="3">';
if ($account)
{
	if (! preg_match('/,/',$account))
	{
		$moreparam='&month='.$month.'&year='.$year.($mode=='showalltime'?'&mode=showalltime':'');
		if ($_GET["option"]!='all')
		{
			$morehtml='<a href="'.$_SERVER["PHP_SELF"].'?account='.$account.'&option=all'.$moreparam.'">'.$langs->trans("ShowAllAccounts").'</a>';
			print $form->showrefnav($acct,'ref','',1,'ref','ref','',$moreparam);
		}
		else
		{
			$morehtml='<a href="'.$_SERVER["PHP_SELF"].'?account='.$account.$moreparam.'">'.$langs->trans("BackToAccount").'</a>';
			print $langs->trans("All");
			//print $morehtml;
		}
	}
	else
	{
		$bankaccount=new Account($db);
		$listid=explode(',',$account);
		foreach($listid as $key => $id)
		{
			$bankaccount->fetch($id);
			$bankaccount->label=$bankaccount->ref;
			print $bankaccount->getNomUrl(1);
			if ($key < (count($listid)-1)) print ', ';
		}
	}
}
else
{
	print $langs->trans("All");
}
print '</td></tr>';

// Label
print '<tr><td valign="top">'.$langs->trans("Label").'</td>';
print '<td colspan="3">';
if ($account && $_GET["option"]!='all')
{
	print $acct->label;
}
else
{
	print $langs->trans("AllAccounts");
}
print '</td></tr>';

print '</table>';

print '<table class="notopnoleftnoright" width="100%">';

// Navigation links
print '<tr><td align="right">'.$morehtml.' &nbsp; &nbsp; ';
if ($mode == 'showalltime')
{
	print '<a href="'.$_SERVER["PHP_SELF"].'?account='.$account.'">';
	print $langs->trans("GoBack");
	print '</a>';
}
else
{
	print '<a href="'.$_SERVER["PHP_SELF"].'?mode=showalltime&account='.$account.'">';
	print $langs->trans("ShowAllTimeBalance");
	print '</a>';
}
print '<br><br></td></tr>';


// Graphs
if ($mode == 'standard')
{
	$prevyear=$year;$nextyear=$year;
	$prevmonth=$month-1;$nextmonth=$month+1;
	if ($prevmonth < 1)  { $prevmonth=12; $prevyear--; }
	if ($nextmonth > 12) { $nextmonth=1; $nextyear++; }

	// For month
	$lien="<a href='".$_SERVER["PHP_SELF"]."?account=".$account.($_GET["option"]!='all'?'':'&option=all')."&year=".$prevyear."&month=".$prevmonth."'>".img_previous()."</a> ".$langs->trans("Month")." <a href='".$_SERVER["PHP_SELF"]."?account=".$account."&year=".$nextyear."&month=".$nextmonth."'>".img_next()."</a>";
	print '<tr><td align="right">'.$lien.'</td></tr>';

	print '<tr><td align="center">';
	$file = "movement".$account."-".$year.$month.".png";
	print $show4;
	print '</td></tr>';

	print '<tr><td align="center">';
	print $show1;
	print '</td></tr>';

	// For year
	$prevyear=$year-1;$nextyear=$year+1;
	$lien="<a href='".$_SERVER["PHP_SELF"]."?account=".$account.($_GET["option"]!='all'?'':'&option=all')."&year=".($prevyear)."'>".img_previous()."</a> ".$langs->trans("Year")." <a href='".$_SERVER["PHP_SELF"]."?account=".$account."&year=".($nextyear)."'>".img_next()."</a>";
	print '<tr><td align="right">'.$lien.'</td></tr>';

	print '<tr><td align="center">';
	print $show5;
	print '</td></tr>';

	print '<tr><td align="center">';
	print $show2;
	print '</td></tr>';
}

if ($mode == 'showalltime')
{
	print '<tr><td align="center">';
	print $show3;
	print '</td></tr>';
}

print '</table>';

print "\n</div>\n";


llxFooter();

$db->close();
?>
