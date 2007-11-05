<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *
 * $Id$
 */

/**
	  \file       htdocs/compta/bank/graph.php
	  \ingroup    banque
	  \brief      Page de détail des transactions bancaires
	  \version    $Revision$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/bank.lib.php");
require_once(DOL_DOCUMENT_ROOT."/dolgraph.class.php");

$langs->load("banks");

if (!$user->rights->banque->lire)
  accessforbidden();

$account = $_GET["account"];
$mesg = '';


llxHeader();

$form = new Form($db);

if ($_GET["account"] || $_GET["ref"])
{

    $datetime = time();
    $year = strftime("%Y", $datetime);
    $month = strftime("%m", $datetime);
    $day = strftime("%d", $datetime);
    
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

	
	create_exdir($conf->banque->dir_temp);
	

	// Definition de $width et $height
	$width = 800;
	$height = 200;

	// Calcul de $min et $max
	$sql = "SELECT min(".$db->pdate("datev")."), max(".$db->pdate("datev").")";
	$sql.= " FROM ".MAIN_DB_PREFIX."bank";
	$sql.= " WHERE fk_account = ".$account;
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
	

	// Chargement du tableau $amounts
	// \todo peut etre optimise en virant les date_format
	$amounts = array();
	$sql = "SELECT date_format(datev,'%Y%m%d'), sum(amount)";
	$sql .= " FROM ".MAIN_DB_PREFIX."bank";
	$sql .= " WHERE fk_account = ".$account;
	$sql .= " AND date_format(datev,'%Y%m') = '".$year.$month."'";
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
	$sql .= " WHERE fk_account = ".$account;
	$sql .= " AND datev < '".$year."-".sprintf("%02s",$month)."-01'";
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
	$xmonth = $month;

	$i = 0;
	while ($xmonth == $month)
	{
		$textdate = strftime("%Y%m%d",$day);
		$xyear = substr($textdate,0,4);
		$xday = substr($textdate,6,2);
		$xmonth = substr($textdate,4,2);

		//print strftime ("%e %d %m %y",$day)."\n";
		$subtotal = $subtotal + (isset($amounts[$textdate]) ? $amounts[$textdate] : 0);
		if ($day > time())
		{
			$datas[$i] = ''; // Valeur spéciale permettant de ne pas tracer le graph
		}
		else
		{
			$datas[$i] = $solde + $subtotal;
		}
		$datamin[$i] = $acct->min_desired;
		//$labels[$i] = strftime("%d",$day);
		$labels[$i] = $xday;
		$day += 86400;
		$i++;
	}

	// Fabrication tableau 1
	$file= $conf->banque->dir_temp."/balance.$account.$year.$month.png";
	$title=$langs->transnoentities("Balance").' '.$langs->transnoentities("Month").': '.$month.' '.$langs->transnoentities("Year").': '.$year;
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
	$px->SetHorizTickIncrement(1);
	$px->SetPrecisionY(0);
    $px->draw($file);
	
	unset($graph_datas);
	unset($px);
	unset($datas);
	unset($datamin);
	unset($labels);
	unset($amounts);

	
	// Tableau 2

	
	// Chargement du tableau $amounts
	// \todo peut etre optimise en virant les date_format
	$amounts = array();
	$sql = "SELECT date_format(datev,'%Y%m%d'), sum(amount)";
	$sql .= " FROM ".MAIN_DB_PREFIX."bank";
	$sql .= " WHERE fk_account = ".$account;
	$sql .= " AND date_format(datev,'%Y') = '".$year."'";
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
	$sql .= " WHERE fk_account = ".$account;
	$sql .= " AND datev < '".$year."-01-01'";
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
	$i = 0;
	while ($xyear == $year)
	{
		$textdate = strftime("%Y%m%d",$day);
		$xyear = substr($textdate,0,4);
		$xday = substr($textdate,6,2);

		$subtotal = $subtotal + (isset($amounts[$textdate]) ? $amounts[$textdate] : 0);
		if ($day > $now)
		{
			$datas[$i] = ''; // Valeur spéciale permettant de ne pas tracer le graph
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
		$i++;
	}

	// Fabrication tableau 2
	$file= $conf->banque->dir_temp."/balance.$account.$year.png";
	$title=$langs->transnoentities("Balance").' '.$langs->transnoentities("Year").': '.$year;
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
	$px->SetHorizTickIncrement(30.41);	// 30.41 jours/mois en moyenne
	$px->SetPrecisionY(0);
    $px->draw($file);

	unset($px);
	unset($graph_datas);
	unset($datas);
	unset($datamin);
	unset($labels);
	unset($amounts);


	// Tableau 3

	
	// Chargement du tableau $amounts
	// \todo peut etre optimise en virant les date_format
	$amounts = array();
	$sql = "SELECT date_format(datev,'%Y%m%d'), sum(amount)";
	$sql .= " FROM ".MAIN_DB_PREFIX."bank";
	$sql .= " WHERE fk_account = ".$account;
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
	$i = 0;
	while ($day <= ($max+86400))	// On va au dela du dernier jour
	{
		$textdate=strftime("%Y%m%d",$day);

		$subtotal = $subtotal + (isset($amounts[$textdate]) ? $amounts[$textdate] : 0);
		//print strftime ("%e %d %m %y",$day)." ".$subtotal."\n<br>";
		if ($day > ($max+86400))
		{
			$datas[$i] = ''; // Valeur spéciale permettant de ne pas tracer le graph
		}
		else
		{
			$datas[$i] = '' + $solde + $subtotal;
		}
		$datamin[$i] = $acct->min_desired;
		if (substr($textdate,6,2) == '01')
		{
			$labels[$i] = substr($textdate,4,2);
		}
		$day += 86400;
		$i++;
	}

	// Fabrication tableau 3
	$file= $conf->banque->dir_temp."/balance.$account.png";
	$title=$langs->transnoentities("Balance");
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
	$px->SetPrecisionY(0);
    $px->draw($file);

	unset($graph_datas);
	unset($datas);
	unset($datamin);
	unset($labels);
	unset($amounts);
	
	
	// Tableau 4
	
	
	// Chargement du tableau $credits, $debits
	$credits = array();
	$debits = array();
	$sql = "SELECT date_format(datev,'%m'), sum(amount)";
	$sql .= " FROM ".MAIN_DB_PREFIX."bank";
	$sql .= " WHERE fk_account = ".$account;
	$sql .= " AND date_format(datev,'%Y') = '".$year."'";
	$sql .= " AND amount > 0";
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
	$sql .= " WHERE fk_account = ".$account;
	$sql .= " AND date_format(datev,'%Y') = '".$year."'";
	$sql .= " AND amount < 0";
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
		$labels[$i] = strftime("%b",dolibarr_mktime(12,0,0,$i+1,1,2000));
		$datamin[$i] = $acct->min_desired;
	}

	// Fabrication tableau 4
	$file= $conf->banque->dir_temp."/movement.$account.$year.png";
	$title=$langs->transnoentities("BankMovements").' '.$langs->transnoentities("Year").': '.$year;
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
	$px->SetShading(8);
	$px->setBgColor('onglet');
	$px->SetHorizTickIncrement(1);
	$px->SetPrecisionY(0);
    $px->draw($file);

	unset($graph_datas);
	unset($px);
	unset($debits);
	unset($credits);

	
	
	// Onglets
	$head=bank_prepare_head($acct);
	dolibarr_fiche_head($head,'graph',$langs->trans("FinancialAccount"),0);

	print '<table class="border" width="100%">';

	// Ref
	print '<tr><td valign="top" width="25%">'.$langs->trans("Ref").'</td>';
	print '<td colspan="3">';
	print $form->showrefnav($acct,'ref','',1,'ref');
	print '</td></tr>';

	// Label
	print '<tr><td valign="top">'.$langs->trans("Label").'</td>';
	print '<td colspan="3">'.$acct->label.'</td></tr>';

	print '</table>';

	print '<br>';

	
	print '<table class="notopnoleftnoright" width="100%">';

    print '<tr><td align="center">';
    $file = "movement.$account.$year.png";
    print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=bank&file='.$file.'" alt="" title="">';
    print '</td></tr>';

    print '<tr><td align="center">';
    $file = "balance.$account.$year.$month.png";
	print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=bank&file='.$file.'" alt="" title="">';
    print '</td></tr>';

    print '<tr><td align="center">';
    $file = "balance.$account.$year.png";
    print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=bank&file='.$file.'" alt="" title="">';
    print '</td></tr>';
    
    print '<tr><td align="center">';
    $file = "balance.$account.png";
    print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=bank&file='.$file.'" alt="" title="">';
    print '</td></tr>';
    

    print '</table>';
    
	print "\n</div>\n";
    
}

$db->close();

llxFooter('$Date$ - $Revision$');

?>
