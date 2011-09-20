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
 *		\file        htdocs/compta/bank/annuel.php
 *		\ingroup     banque
 *		\brief       Page reporting mensuel Entrees/Sorties d'un compte bancaire
 */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/bank.lib.php");
require_once(DOL_DOCUMENT_ROOT."/compta/bank/class/account.class.php");

// Security check
if (isset($_GET["account"]) || isset($_GET["ref"]))
{
	$id = isset($_GET["account"])?$_GET["account"]:(isset($_GET["ref"])?$_GET["ref"]:'');
}
$fieldid = isset($_GET["ref"])?'ref':'rowid';
if ($user->societe_id) $socid=$user->societe_id;
$result=restrictedArea($user,'banque',$id,'bank_account','','',$fieldid);

$year_start=isset($_GET["year_start"])?$_GET["year_start"]:$_POST["year_start"];
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
if ($_GET["account"] && ! preg_match('/,/',$_GET["account"]))	// if for a particular account and not a list
{
	$result=$acct->fetch($_GET["account"]);
}
if ($_GET["ref"])
{
	$result=$acct->fetch(0,$_GET["ref"]);
	$_GET["account"]=$acct->id;
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
if ($_GET["account"]) $sql .= " AND b.fk_account IN (".$_GET["account"].")";
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
if ($_GET["account"]) $sql.= " AND b.fk_account IN (".$_GET["account"].")";
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

// Ref
print '<tr><td valign="top" width="25%">'.$langs->trans("Ref").'</td>';
print '<td colspan="3">';
if ($_GET["account"])
{
	if (! preg_match('/,/',$_GET["account"]))
	{
		print $form->showrefnav($acct,'ref','',1,'ref');
	}
	else
	{
		$bankaccount=new Account($db);
		$listid=explode(',',$_GET["account"]);
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
	print $langs->trans("ALL");
}
print '</td></tr>';

// Label
print '<tr><td valign="top">'.$langs->trans("Label").'</td>';
print '<td colspan="3">';
if ($_GET["account"])
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
if ($_GET["account"]) $sql.= " AND b.fk_account IN (".$_GET["account"].")";

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

print "\n</div>\n";

$db->close();

llxFooter();

?>
