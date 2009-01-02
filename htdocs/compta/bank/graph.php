<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 \file       htdocs/compta/bank/graph.php
 \ingroup    banque
 \brief      Page graph des transactions bancaires
 \version    $Id$
 */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/bank.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/dolgraph.class.php");

$langs->load("banks");

if (!$user->rights->banque->lire)
accessforbidden();

$account = $_GET["account"];
$mode='standard';
if (isset($_GET["mode"]) && $_GET["mode"] == 'showalltime') $mode='showalltime';
$mesg = '';
$error=0;


llxHeader();

$form = new Form($db);

// If lib forced
if (! empty($_GET["lib"])) $conf->global->MAIN_GRAPH_LIBRARY=$_GET["lib"];


$datetime = time();
$year = dolibarr_print_date($datetime, "%Y");
$month = dolibarr_print_date($datetime, "%m");
$day = dolibarr_print_date($datetime, "%d");
if (! empty($_GET["year"]))  $year=sprintf("%04d",$_GET["year"]);
if (! empty($_GET["month"])) $month=sprintf("%02d",$_GET["month"]);


$acct = new Account($db);
if ($_GET["account"])
{
	$result=$acct->fetch($_GET["account"]);
}
if ($_GET["ref"])
{
	$result=$acct->fetch(0,$_GET["ref"]);
	$account=$acct->id;
}


$result=create_exdir($conf->banque->dir_temp);
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
	$sql = "SELECT min(".$db->pdate("datev")."), max(".$db->pdate("datev").")";
	$sql.= " FROM ".MAIN_DB_PREFIX."bank";
	if ($account) $sql.= " WHERE fk_account = ".$account;
	$resql = $db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);
		$row = $db->fetch_row($resql);
		$min = $row[0];
		$max = $row[1];
	}
	else
	{
		dolibarr_print_error($db);
	}
	$log="graph.php: min=".$min." max=".$max;
	dolibarr_syslog($log);


	// Tableau 1

	if ($mode == 'standard')
	{
		// Chargement du tableau $amounts
		// \todo peut etre optimise en virant les date_format
		$amounts = array();
		$sql = "SELECT date_format(datev,'%Y%m%d'), sum(amount)";
		$sql .= " FROM ".MAIN_DB_PREFIX."bank";
		$sql .= " WHERE date_format(datev,'%Y%m') = '".$year.$month."'";
		if ($account) $sql .= " AND fk_account = ".$account;
		$sql .= " GROUP BY date_format(datev,'%Y%m%d')";
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
			dolibarr_print_error($db);
		}

		// Calcul de $solde avant le debut du graphe
		$solde = 0;
		$sql = "SELECT SUM(amount)";
		$sql .= " FROM ".MAIN_DB_PREFIX."bank";
		$sql .= " WHERE datev < '".$year."-".sprintf("%02s",$month)."-01'";
		if ($account) $sql .= " AND fk_account = ".$account;
		$resql = $db->query($sql);
		if ($resql)
		{
			$row = $db->fetch_row($resql);
			$solde = $row[0];
			$db->free($resql);
		}
		else
		{
			dolibarr_print_error($db);
		}

		// Chargement de labels et datas pour tableau 1
		$labels = array();
		$datas = array();
		$datamin = array();

		$subtotal = 0;
		$day = dolibarr_mktime(12,0,0,$month,1,$year);
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
		// and this make artichow report a warning.
		//$datas[0]=100; KO
		//$datas[0]=100; $datas[1]=90; OK
		//var_dump($datas);
		//exit;

		// Fabrication tableau 1
		$file= $conf->banque->dir_temp."/balance".$account."-".$year.$month.".png";
		$title=$langs->transnoentities("Balance").' - '.$langs->transnoentities("Month").': '.$month.' '.$langs->transnoentities("Year").': '.$year;
		$graph_datas=array();
		foreach($datas as $i => $val)
		{
			if ($acct->min_desired) $graph_datas[$i]=array(isset($labels[$i])?$labels[$i]:'',$datas[$i],$datamin[$i]);
			else $graph_datas[$i]=array(isset($labels[$i])?$labels[$i]:'',$datas[$i]);
		}
	  
		$px = new DolGraph();
		$px->SetData($graph_datas);
		if ($acct->min_desired) $px->SetLegend(array($langs->transnoentities("Balance"),$langs->transnoentities("BalanceMinimalDesired")));
		else $px->SetLegend(array($langs->transnoentities("Balance")));
		$px->SetLegendWidthMin(180);
		$px->SetMaxValue($px->GetCeilMaxValue()<0?0:$px->GetCeilMaxValue());
		$px->SetMinValue($px->GetFloorMinValue()>0?0:$px->GetFloorMinValue());
		$px->SetTitle($title);
		$px->SetWidth($width);
		$px->SetHeight($height);
		$px->SetType('lines');
		$px->setBgColor('onglet');
		$px->setBgColorGrid(array(255,255,255));
		$px->SetHorizTickIncrement(1);
		$px->SetPrecisionY(0);
		$px->draw($file);

		unset($graph_datas);
		unset($px);
		unset($datas);
		unset($datamin);
		unset($labels);
		unset($amounts);
	}

	// Tableau 2

	if ($mode == 'standard')
	{
		// Chargement du tableau $amounts
		// \todo peut etre optimise en virant les date_format
		$amounts = array();
		$sql = "SELECT date_format(datev,'%Y%m%d'), sum(amount)";
		$sql .= " FROM ".MAIN_DB_PREFIX."bank";
		$sql .= " WHERE date_format(datev,'%Y') = '".$year."'";
		if ($account) $sql .= " AND fk_account = ".$account;
		$sql .= " GROUP BY date_format(datev,'%Y%m%d')";
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
			dolibarr_print_error($db);
		}

		// Calcul de $solde avant le debut du graphe
		$solde = 0;
		$sql = "SELECT sum(amount)";
		$sql .= " FROM ".MAIN_DB_PREFIX."bank";
		$sql .= " WHERE datev < '".$year."-01-01'";
		if ($account) $sql .= " AND fk_account = ".$account;
		$resql = $db->query($sql);
		if ($resql)
		{
			$row = $db->fetch_row($resql);
			$solde = $row[0];
			$db->free($resql);
		}
		else
		{
			dolibarr_print_error($db);
		}

		// Chargement de labels et datas pour tableau 2
		$labels = array();
		$datas = array();
		$datamin = array();

		$subtotal = 0;
		$now = time();
		$day = dolibarr_mktime(12,0,0,1,1,$year);
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
				$labels[$i] = dolibarr_print_date($day,"%b");
			}
			$day += 86400;
			$textdate = strftime("%Y%m%d",$day);
			$xyear = substr($textdate,0,4);
			$xday = substr($textdate,6,2);
			$i++;
		}

		// Fabrication tableau 2
		$file= $conf->banque->dir_temp."/balance".$account."-".$year.".png";
		$title=$langs->transnoentities("Balance").' - '.$langs->transnoentities("Year").': '.$year;
		$graph_datas=array();
		foreach($datas as $i => $val)
		{
			if ($acct->min_desired) $graph_datas[$i]=array(isset($labels[$i])?$labels[$i]:'',$datas[$i],$datamin[$i]);
			else $graph_datas[$i]=array(isset($labels[$i])?$labels[$i]:'',$datas[$i]);
		}
		$px = new DolGraph();
		$px->SetData($graph_datas);
		if ($acct->min_desired) $px->SetLegend(array($langs->transnoentities("Balance"),$langs->transnoentities("BalanceMinimalDesired")));
		else $px->SetLegend(array($langs->transnoentities("Balance")));
		$px->SetLegendWidthMin(180);
		$px->SetMaxValue($px->GetCeilMaxValue()<0?0:$px->GetCeilMaxValue());
		$px->SetMinValue($px->GetFloorMinValue()>0?0:$px->GetFloorMinValue());
		$px->SetTitle($title);
		$px->SetWidth($width);
		$px->SetHeight($height);
		$px->SetType('lines');
		$px->setBgColor('onglet');
		$px->setBgColorGrid(array(255,255,255));
		$px->SetHideXGrid(true);
		//$px->SetHorizTickIncrement(30.41);	// 30.41 jours/mois en moyenne
		$px->SetPrecisionY(0);
		$px->draw($file);

		unset($px);
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
		// \todo peut etre optimise en virant les date_format
		$amounts = array();
		$sql = "SELECT date_format(datev,'%Y%m%d'), sum(amount)";
		$sql .= " FROM ".MAIN_DB_PREFIX."bank";
		if ($account) $sql .= " WHERE fk_account = ".$account;
		$sql .= " GROUP BY date_format(datev,'%Y%m%d')";
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
			dolibarr_print_error($db);
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
		$title=$langs->transnoentities("Balance")." - ".$langs->transnoentities("AllTime");
		$graph_datas=array();
		foreach($datas as $i => $val)
		{
			if ($acct->min_desired) $graph_datas[$i]=array(isset($labels[$i])?$labels[$i]:'',$datas[$i],$datamin[$i]);
			else $graph_datas[$i]=array(isset($labels[$i])?$labels[$i]:'',$datas[$i]);
		}
		$px = new DolGraph();
		$px->SetData($graph_datas);
		if ($acct->min_desired) $px->SetLegend(array($langs->transnoentities("Balance"),$langs->transnoentities("BalanceMinimalDesired")));
		else $px->SetLegend(array($langs->transnoentities("Balance")));
		$px->SetLegendWidthMin(180);
		$px->SetMaxValue($px->GetCeilMaxValue()<0?0:$px->GetCeilMaxValue());
		$px->SetMinValue($px->GetFloorMinValue()>0?0:$px->GetFloorMinValue());
		$px->SetTitle($title);
		$px->SetWidth($width);
		$px->SetHeight($height);
		$px->SetType('lines');
		$px->setBgColor('onglet');
		$px->setBgColorGrid(array(255,255,255));
		$px->SetPrecisionY(0);
		$px->draw($file);

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
		$sql = "SELECT date_format(datev,'%d'), sum(amount)";
		$sql .= " FROM ".MAIN_DB_PREFIX."bank";
		$monthnext=$month+1; $yearnext=$year;
		if ($monthnext > 12) { $monthnext=1; $yearnext++; }
		$sql .= " WHERE datev >= '".$year."-".$month."-01 00:00:00'";
		$sql .= " AND datev < '".$yearnext."-".$monthnext."-01 00:00:00'";
		$sql .= " AND amount > 0";
		if ($account) $sql .= " AND fk_account = ".$account;
		$sql .= " GROUP BY date_format(datev,'%d')";
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
			dolibarr_print_error($db);
		}
		$sql = "SELECT date_format(datev,'%d'), sum(amount)";
		$sql .= " FROM ".MAIN_DB_PREFIX."bank";
		$monthnext=$month+1; $yearnext=$year;
		if ($monthnext > 12) { $monthnext=1; $yearnext++; }
		$sql .= " WHERE datev >= '".$year."-".$month."-01 00:00:00'";
		$sql .= " AND datev < '".$yearnext."-".$monthnext."-01 00:00:00'";
		$sql .= " AND amount < 0";
		if ($account) $sql .= " AND fk_account = ".$account;
		$sql .= " GROUP BY date_format(datev,'%d')";
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
			dolibarr_print_error($db);
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
		$title=$langs->transnoentities("BankMovements").' - '.$langs->transnoentities("Month").': '.$month.' '.$langs->transnoentities("Year").': '.$year;
		$graph_datas=array();
		foreach($data_credit as $i => $val)
		{
			$graph_datas[$i]=array($labels[$i],$data_credit[$i],$data_debit[$i]);
		}
		$px = new DolGraph();
		$px->SetData($graph_datas);
		$px->SetLegend(array($langs->transnoentities("Credit"),$langs->transnoentities("Debit")));
		$px->SetLegendWidthMin(180);
		$px->SetMaxValue($px->GetCeilMaxValue()<0?0:$px->GetCeilMaxValue());
		$px->SetMinValue($px->GetFloorMinValue()>0?0:$px->GetFloorMinValue());
		$px->SetTitle($title);
		$px->SetWidth($width);
		$px->SetHeight($height);
		$px->SetType('bars');
		$px->SetShading(3);
		$px->setBgColor('onglet');
		$px->setBgColorGrid(array(255,255,255));
		$px->SetHorizTickIncrement(1);
		$px->SetPrecisionY(0);
		$px->draw($file);

		unset($graph_datas);
		unset($px);
		unset($debits);
		unset($credits);
	}

	// Tableau 4b - Credit/Debit

	if ($mode == 'standard')
	{
		// Chargement du tableau $credits, $debits
		$credits = array();
		$debits = array();
		$sql = "SELECT date_format(datev,'%m'), sum(amount)";
		$sql .= " FROM ".MAIN_DB_PREFIX."bank";
		$sql .= " WHERE datev >= '".$year."-01-01 00:00:00'";
		$sql .= " AND datev <= '".$year."-12-31 23:59:59'";
		$sql .= " AND amount > 0";
		if ($account) $sql .= " AND fk_account = ".$account;
		$sql .= " GROUP BY date_format(datev,'%m');";
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
			dolibarr_print_error($db);
		}
		$sql = "SELECT date_format(datev,'%m'), sum(amount)";
		$sql .= " FROM ".MAIN_DB_PREFIX."bank";
		$sql .= " WHERE datev >= '".$year."-01-01 00:00:00'";
		$sql .= " AND datev <= '".$year."-12-31 23:59:59'";
		$sql .= " AND amount < 0";
		if ($account) $sql .= " AND fk_account = ".$account;
		$sql .= " GROUP BY date_format(datev,'%m');";
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
			dolibarr_print_error($db);
		}


		// Chargement de labels et data_xxx pour tableau 4 Mouvements
		$labels = array();
		$data_credit = array();
		$data_debit = array();
		for ($i = 0 ; $i < 12 ; $i++)
		{
			$data_credit[$i] = isset($credits[substr("0".($i+1),-2)]) ? $credits[substr("0".($i+1),-2)] : 0;
			$data_debit[$i] = isset($debits[substr("0".($i+1),-2)]) ? $debits[substr("0".($i+1),-2)] : 0;
			$labels[$i] = dolibarr_print_date(dolibarr_mktime(12,0,0,$i+1,1,2000),"%b");
			$datamin[$i] = $acct->min_desired;
		}

		// Fabrication tableau 4b
		$file= $conf->banque->dir_temp."/movement".$account."-".$year.".png";
		$title=$langs->transnoentities("BankMovements").' - '.$langs->transnoentities("Year").': '.$year;
		$graph_datas=array();
		foreach($data_credit as $i => $val)
		{
			$graph_datas[$i]=array($labels[$i],$data_credit[$i],$data_debit[$i]);
		}
		$px = new DolGraph();
		$px->SetData($graph_datas);
		$px->SetLegend(array($langs->transnoentities("Credit"),$langs->transnoentities("Debit")));
		$px->SetLegendWidthMin(180);
		$px->SetMaxValue($px->GetCeilMaxValue()<0?0:$px->GetCeilMaxValue());
		$px->SetMinValue($px->GetFloorMinValue()>0?0:$px->GetFloorMinValue());
		$px->SetTitle($title);
		$px->SetWidth($width);
		$px->SetHeight($height);
		$px->SetType('bars');
		$px->SetShading(3);
		$px->setBgColor('onglet');
		$px->setBgColorGrid(array(255,255,255));
		$px->SetHorizTickIncrement(1);
		$px->SetPrecisionY(0);
		$px->draw($file);

		unset($graph_datas);
		unset($px);
		unset($debits);
		unset($credits);
	}
}


// Onglets
$head=bank_prepare_head($acct);
dolibarr_fiche_head($head,'graph',$langs->trans("FinancialAccount"),0);

if ($mesg) print $mesg.'<br>';

print '<table class="border" width="100%">';

// Ref
print '<tr><td valign="top" width="25%">'.$langs->trans("Ref").'</td>';
print '<td colspan="3">';
if ($account)
{
	$moreparam='&month='.$month.'&year='.$year.($mode=='showalltime'?'&mode=showalltime':'');
	print $form->showrefnav($acct,'ref','',1,'ref','ref','',$moreparam);
}
else
{
	print $langs->trans("ALL");
}
print '</td></tr>';

// Label
print '<tr><td valign="top">'.$langs->trans("Label").'</td>';
print '<td colspan="3">';
if ($account)
{
	print $acct->label;
}
else
{
	print $langs->trans("AllAccounts");
}
print '</td></tr>';

print '</table>';

print '<br>';


print '<table class="notopnoleftnoright" width="100%">';

if ($mode == 'standard')
{
	$prevyear=$year;$nextyear=$year;
	$prevmonth=$month-1;$nextmonth=$month+1;
	if ($prevmonth < 1)  { $prevmonth=12; $prevyear--; }
	if ($nextmonth > 12) { $nextmonth=1; $nextyear++; }

	// For month
	$lien="<a href='".$_SERVER["PHP_SELF"]."?account=".$acct->id."&year=".$prevyear."&month=".$prevmonth."'>".img_previous()."</a> ".$langs->trans("Month")." <a href='".$_SERVER["PHP_SELF"]."?account=".$acct->id."&year=".$nextyear."&month=".$nextmonth."'>".img_next()."</a>";
	print '<tr><td align="right">'.$lien.'</td></tr>';

	print '<tr><td align="center">';
	$file = "movement".$account."-".$year.$month.".png";
	print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=bank&file='.$file.'" alt="" title="">';

	print '<tr><td align="center">';
	$file = "balance".$account."-".$year.$month.".png";
	print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=bank&file='.$file.'" alt="" title="">';
	print '</td></tr>';

	// For year
	$prevyear=$year-1;$nextyear=$year+1;
	$lien="<a href='".$_SERVER["PHP_SELF"]."?account=".$acct->id."&year=".($prevyear)."'>".img_previous()."</a> ".$langs->trans("Year")." <a href='".$_SERVER["PHP_SELF"]."?account=".$acct->id."&year=".($nextyear)."'>".img_next()."</a>";
	print '<tr><td align="right">'.$lien.'</td></tr>';

	print '<tr><td align="center">';
	$file = "movement".$account."-".$year.".png";
	print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=bank&file='.$file.'" alt="" title="">';
	print '</td></tr>';

	print '<tr><td align="center">';
	$file = "balance".$account."-".$year.".png";
	print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=bank&file='.$file.'" alt="" title="">';
	print '</td></tr>';
}

if ($mode == 'showalltime')
{
	print '<tr><td align="center">';
	$file = "balance".$account.".png";
	print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=bank&file='.$file.'" alt="" title="">';
	print '</td></tr>';
}

// Switch All time/Not all time
if ($mode == 'showalltime')
{
	print '<tr><td align="center"><br>';
	print '<a href="'.$_SERVER["PHP_SELF"].'?account='.$account.'">';
	print $langs->trans("GoBack");
	print '</a>';
	print '</td></tr>';
}
else
{
	print '<tr><td align="center"><br>';
	print '<a href="'.$_SERVER["PHP_SELF"].'?mode=showalltime&account='.$account.'">';
	print $langs->trans("ShowAllTimeBalance");
	print '</a>';
	print '</td></tr>';
}

print '</table>';

print "\n</div>\n";


$db->close();

llxFooter('$Date$ - $Revision$');

?>
