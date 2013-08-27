<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2013      Charles-Fr BENKE     <charles.fr@benke.fr>
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
 *		\file        htdocs/compta/bank/annuel.php
 *		\ingroup     banque
 *		\brief       Page to report input-output of a bank account
 */

require('../../main.inc.php');
require_once DOL_DOCUMENT_ROOT.'/core/lib/bank.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';

$langs->load("banks");
$langs->load("categories");

$id=GETPOST('account');
$ref=GETPOST('ref');

// Security check
$fieldid = (! empty($ref)?$ref:$id);
$fieldname = isset($ref)?'ref':'rowid';
if ($user->societe_id) $socid=$user->societe_id;
$result=restrictedArea($user,'banque',$fieldid,'bank_account','','',$fieldname);

$year_start=GETPOST('year_start');
$year_current = strftime("%Y",time());
if (! $year_start)
{
	$year_start = $year_current - 2;
	$year_end = $year_current;
}
else
{
	$year_end=$year_start+2;
}


llxHeader();

$form = new Form($db);

// Get account informations
$acct = new Account($db);
if ($id > 0 && ! preg_match('/,/', $id))	// if for a particular account and not a list
{
	$result=$acct->fetch($id);
	$id=$acct->id;
}
if (! empty($ref))
{
	$result=$acct->fetch(0, $ref);
	$id=$acct->id;
}


// Ce rapport de tresorerie est base sur llx_bank (car doit inclure les transactions sans facture)
// plutot que sur llx_paiement + llx_paiementfourn

$sql = "SELECT SUM(b.amount)";
$sql.= ", date_format(b.dateo,'%Y-%m') as dm";
$sql.= " FROM ".MAIN_DB_PREFIX."bank as b";
$sql.= ", ".MAIN_DB_PREFIX."bank_account as ba";
$sql.= " WHERE b.fk_account = ba.rowid";
$sql.= " AND ba.entity = ".$conf->entity;
$sql.= " AND b.amount >= 0";
if (! empty($id))
	$sql .= " AND b.fk_account IN (".$db->escape($id).")";
$sql.= " GROUP BY dm";

$resql=$db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	$i = 0;
	while ($i < $num)
	{
		$row = $db->fetch_row($resql);
		$encaiss[$row[1]] = $row[0];
		$i++;
	}
}
else
{
	dol_print_error($db);
}

$sql = "SELECT SUM(b.amount)";
$sql.= ", date_format(b.dateo,'%Y-%m') as dm";
$sql.= " FROM ".MAIN_DB_PREFIX."bank as b";
$sql.= ", ".MAIN_DB_PREFIX."bank_account as ba";
$sql.= " WHERE b.fk_account = ba.rowid";
$sql.= " AND ba.entity = ".$conf->entity;
$sql.= " AND b.amount <= 0";
if (! empty($id))
	$sql .= " AND b.fk_account IN (".$db->escape($id).")";
$sql.= " GROUP BY dm";

$resql=$db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	$i = 0;
	while ($i < $num)
	{
		$row = $db->fetch_row($resql);
		$decaiss[$row[1]] = -$row[0];
		$i++;
	}
}
else
{
	dol_print_error($db);
}


// Onglets
$head=bank_prepare_head($acct);
dol_fiche_head($head,'annual',$langs->trans("FinancialAccount"),0,'account');

$title=$langs->trans("FinancialAccount")." : ".$acct->label;
$lien=($year_start?"<a href='".$_SERVER["PHP_SELF"]."?account=".$acct->id."&year_start=".($year_start-1)."'>".img_previous()."</a> ".$langs->trans("Year")." <a href='".$_SERVER["PHP_SELF"]."?account=".$acct->id."&year_start=".($year_start+1)."'>".img_next()."</a>":"");

print '<table class="border" width="100%">';

$linkback = '<a href="'.DOL_URL_ROOT.'/compta/bank/index.php">'.$langs->trans("BackToList").'</a>';

// Ref
print '<tr><td valign="top" width="25%">'.$langs->trans("Ref").'</td>';
print '<td colspan="3">';
if ($_GET["account"])
{
	if (! preg_match('/,/', $id))
	{
		print $form->showrefnav($acct, 'ref', $linkback, 1, 'ref');
	}
	else
	{
		$bankaccount=new Account($db);
		$listid=explode(',', $id);
		foreach($listid as $key => $aId)
		{
			$bankaccount->fetch($aId);
			$bankaccount->label=$bankaccount->ref;
			print $bankaccount->getNomUrl(1);
			if ($key < (count($listid)-1)) print ', ';
		}
	}
}
else
{
	print $langs->trans("ALL");
}
print '</td></tr>';

// Label
print '<tr><td valign="top">'.$langs->trans("Label").'</td>';
print '<td colspan="3">';
if (! empty($id))
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

// Affiche tableau
print '<table class="notopnoleftnoright" width="100%">';

print '<tr><td colspan="'.(1+($year_end-$year_start+1)*2).'" align="right">'.$lien.'</td></tr>';

print '<tr class="liste_titre"><td class="liste_titre">'.$langs->trans("Month").'</td>';
for ($annee = $year_start ; $annee <= $year_end ; $annee++)
{
	print '<td align="center" width="20%" colspan="2">'.$annee.'</td>';
}
print '</tr>';

print '<tr class="liste_titre">';
print '<td class="liste_titre">&nbsp;</td>';
for ($annee = $year_start ; $annee <= $year_end ; $annee++)
{
	print '<td align="right">'.$langs->trans("Debit").'</td><td align="right">'.$langs->trans("Credit").'</td>';
}
print '</tr>';

$var=true;
for ($mois = 1 ; $mois < 13 ; $mois++)
{
	$var=!$var;
	print '<tr '.$bc[$var].'>';
	print "<td>".dol_print_date(dol_mktime(1,1,1,$mois,1,2000),"%B")."</td>";
	for ($annee = $year_start ; $annee <= $year_end ; $annee++)
	{
		$case = sprintf("%04s-%02s",$annee,$mois);

		print '<td align="right" width="10%">&nbsp;';
		if ($decaiss[$case]>0)
		{
			print price($decaiss[$case]);
			$totsorties[$annee]+=$decaiss[$case];
		}
		print "</td>";

		print '<td align="right" width="10%">&nbsp;';
		if ($encaiss[$case]>0)
		{
			print price($encaiss[$case]);
			$totentrees[$annee]+=$encaiss[$case];
		}
		print "</td>";
	}
	print '</tr>';
}

// Total debit-credit
print '<tr class="liste_total"><td><b>'.$langs->trans("Total")."</b></td>";
for ($annee = $year_start ; $annee <= $year_end ; $annee++)
{
	print '<td align="right"><b>'.price($totsorties[$annee]).'</b></td><td align="right"><b>'.price($totentrees[$annee]).'</b></td>';
}
print "</tr>\n";

// Ligne vierge
print '<tr><td>&nbsp;</td>';
$nbcol=0;
for ($annee = $year_start ; $annee <= $year_end ; $annee++)
{
	$nbcol+=2;
}
print "</tr>\n";

// Solde actuel
$balance=0;

$sql = "SELECT SUM(b.amount) as total";
$sql.= " FROM ".MAIN_DB_PREFIX."bank as b";
$sql.= ", ".MAIN_DB_PREFIX."bank_account as ba";
$sql.= " WHERE b.fk_account = ba.rowid";
$sql.= " AND ba.entity = ".$conf->entity;
if (! empty($id))
	$sql.= " AND b.fk_account IN (".$db->escape($id).")";

$resql=$db->query($sql);
if ($resql)
{
	$obj = $db->fetch_object($resql);
	if ($obj) $balance=$obj->total;
}
else {
	dol_print_error($db);
}
print '<tr class="liste_total"><td><b>'.$langs->trans("CurrentBalance")."</b></td>";
print '<td colspan="'.($nbcol).'" align="right">'.price($balance).'</td>';
print "</tr>\n";

print "</table>";

// BUILDING GRAPHICS

$year = $year_end;

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
	$width = 480;
	$height = 300;

	// Calcul de $min et $max
	$sql = "SELECT MIN(b.datev) as min, MAX(b.datev) as max";
	$sql.= " FROM ".MAIN_DB_PREFIX."bank as b";
	$sql.= ", ".MAIN_DB_PREFIX."bank_account as ba";
	$sql.= " WHERE b.fk_account = ba.rowid";
	$sql.= " AND ba.entity = ".$conf->entity;
	if ($id && $_GET["option"]!='all') $sql.= " AND b.fk_account IN (".$id.")";

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

// CRED PART
	// Chargement du tableau des années
	$tblyear[0] = array();
	$tblyear[1] = array();
	$tblyear[2] = array();

	for ($annee=0;$annee<3;$annee++)
	{
		$sql = "SELECT date_format(b.datev,'%m')";
		$sql.= ", SUM(b.amount)";
		$sql.= " FROM ".MAIN_DB_PREFIX."bank as b";
		$sql.= ", ".MAIN_DB_PREFIX."bank_account as ba";
		$sql.= " WHERE b.fk_account = ba.rowid";
		$sql.= " AND ba.entity = ".$conf->entity;
		$sql.= " AND b.datev >= '".($year-$annee)."-01-01 00:00:00'";
		$sql.= " AND b.datev <= '".($year-$annee)."-12-31 23:59:59'";
		$sql.= " AND b.amount > 0";
		if ($id && $_GET["option"]!='all') $sql.= " AND b.fk_account IN (".$id.")";
		$sql .= " GROUP BY date_format(b.datev,'%m');";

		$resql = $db->query($sql);
		if ($resql)
		{
			$num = $db->num_rows($resql);
			$i = 0;
			while ($i < $num)
			{
				$row = $db->fetch_row($resql);
				$tblyear[$annee][$row[0]] = $row[1];
				$i++;
			}
			$db->free($resql);

		}
		else
		{
			dol_print_error($db);
		}
	}
	// Chargement de labels et data_xxx pour tableau 4 Mouvements
	$labels = array();
	$data_year_0 = array();
	$data_year_1 = array();
	$data_year_2 = array();

	for ($i = 0 ; $i < 12 ; $i++)
	{
		$data_year_0[$i] = isset($tblyear[0][substr("0".($i+1),-2)]) ? $tblyear[0][substr("0".($i+1),-2)] : 0;
		$data_year_1[$i] = isset($tblyear[1][substr("0".($i+1),-2)]) ? $tblyear[1][substr("0".($i+1),-2)] : 0;
		$data_year_2[$i] = isset($tblyear[2][substr("0".($i+1),-2)]) ? $tblyear[2][substr("0".($i+1),-2)] : 0;
		$labels[$i] = dol_print_date(dol_mktime(12,0,0,$i+1,1,2000),"%b");
		$datamin[$i] = 0;
	}

	// Fabrication tableau 4b
	$file= $conf->banque->dir_temp."/credmovement".$id."-".$year.".png";
	$fileurl=DOL_URL_ROOT.'/viewimage.php?modulepart=banque_temp&file='."/credmovement".$id."-".$year.".png";
	$title=$langs->transnoentities("Credit").' - '.$langs->transnoentities("Year").': '.($year-2).' - '.($year-1)." - ".$year;
	$graph_datas=array();
	for($i=0;$i<12;$i++)
	{
		$graph_datas[$i]=array($labels[$i], $data_year_0[$i], $data_year_1[$i], $data_year_2[$i]);
	}

	$px1 = new DolGraph();
	$px1->SetData($graph_datas);
	$px1->SetLegend(array(($year),($year-1),($year-2)));
	$px1->SetLegendWidthMin(180);
	$px1->SetMaxValue($px1->GetCeilMaxValue()<0?0:$px1->GetCeilMaxValue());
	$px1->SetMinValue($px1->GetFloorMinValue()>0?0:$px1->GetFloorMinValue());
	$px1->SetTitle($title);
	$px1->SetWidth($width);
	$px1->SetHeight($height);
	$px1->SetType(array('line','line','line'));
	$px1->SetShading(3);
	$px1->setBgColor('onglet');
	$px1->setBgColorGrid(array(255,255,255));
	$px1->SetHorizTickIncrement(1);
	$px1->SetPrecisionY(0);
	$px1->draw($file,$fileurl);

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

	for ($annee=0;$annee<3;$annee++)
	{
		$sql = "SELECT date_format(b.datev,'%m')";
		$sql.= ", SUM(b.amount)";
		$sql.= " FROM ".MAIN_DB_PREFIX."bank as b";
		$sql.= ", ".MAIN_DB_PREFIX."bank_account as ba";
		$sql.= " WHERE b.fk_account = ba.rowid";
		$sql.= " AND ba.entity = ".$conf->entity;
		$sql.= " AND b.datev >= '".($year-$annee)."-01-01 00:00:00'";
		$sql.= " AND b.datev <= '".($year-$annee)."-12-31 23:59:59'";
		$sql.= " AND b.amount < 0";
		if ($id && $_GET["option"]!='all') $sql.= " AND b.fk_account IN (".$id.")";
		$sql .= " GROUP BY date_format(b.datev,'%m');";

		$resql = $db->query($sql);
		if ($resql)
		{
			$num = $db->num_rows($resql);
			$i = 0;
			while ($i < $num)
			{
				$row = $db->fetch_row($resql);
				$tblyear[$annee][$row[0]] = abs($row[1]);
				$i++;
			}
			$db->free($resql);
		}
		else
		{
			dol_print_error($db);
		}
	}
	// Chargement de labels et data_xxx pour tableau 4 Mouvements
	$labels = array();
	$data_year_0 = array();
	$data_year_1 = array();
	$data_year_2 = array();

	for ($i = 0 ; $i < 12 ; $i++)
	{
		$data_year_0[$i] = isset($tblyear[0][substr("0".($i+1),-2)]) ? $tblyear[0][substr("0".($i+1),-2)] : 0;
		$data_year_1[$i] = isset($tblyear[1][substr("0".($i+1),-2)]) ? $tblyear[1][substr("0".($i+1),-2)] : 0;
		$data_year_2[$i] = isset($tblyear[2][substr("0".($i+1),-2)]) ? $tblyear[2][substr("0".($i+1),-2)] : 0;
		$labels[$i] = dol_print_date(dol_mktime(12,0,0,$i+1,1,2000),"%b");
		$datamin[$i] = 0;
	}

	$file= $conf->banque->dir_temp."/debmovement".$id."-".$year.".png";
	$fileurl= DOL_URL_ROOT.'/viewimage.php?modulepart=banque_temp&file='."/debmovement".$id."-".$year.".png";
	$title=$langs->transnoentities("Debit").' - '.$langs->transnoentities("Year").': '.($year-2).' - '.($year-1)." - ".$year;
	$graph_datas=array();
	for($i=0;$i<12;$i++)
	{
		$graph_datas[$i]=array($labels[$i], $data_year_0[$i], $data_year_1[$i], $data_year_2[$i]);
	}

	$px2 = new DolGraph();
	$px2->SetData($graph_datas);
	$px2->SetLegend(array(($year),($year-1),($year-2)));
	$px2->SetLegendWidthMin(180);
	$px2->SetMaxValue($px2->GetCeilMaxValue()<0?0:$px2->GetCeilMaxValue());
	$px2->SetMinValue($px2->GetFloorMinValue()>0?0:$px2->GetFloorMinValue());
	$px2->SetTitle($title);
	$px2->SetWidth($width);
	$px2->SetHeight($height);
	$px2->SetType(array('line','line','line'));
	$px2->SetShading(3);
	$px2->setBgColor('onglet');
	$px2->setBgColorGrid(array(255,255,255));
	$px2->SetHorizTickIncrement(1);
	$px2->SetPrecisionY(0);
	$px2->draw($file,$fileurl);

	$show2 = $px2->show();

	unset($graph_datas);
	unset($px2);
	unset($tblyear[0]);
	unset($tblyear[1]);
	unset($tblyear[2]);

	print '<div class="fichecenter"><div class="fichehalfleft"><center>';
	print $show1;
	print '</center></div><div class="fichehalfright"><div class="ficheaddleft"><center>';
	print $show2;
	print '</center></div></div></div>';
	print '<div style="clear:both"></div>';
}


print "\n</div>\n";

llxFooter();
$db->close();
?>
